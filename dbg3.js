/* global dbg, doc, Cookie, Ezer */

// (c) 2020 Martin Smidek <martin@smidek.eu>

// ================================================================================> DEBUGGER REMOTE
// funkce debuggeru - volané z dbg3.php
// ------------------------------------------------------------------------------- dbg onclick_start
var dbg_last_script= '';
function dbg_onclick_start(file) {
  dbg_reload(file,dbg.pick);
  // -----------------------------------==> .. click na poznámku
  dbg.notes
    .click( el => {
      dbg.jQuery('li.pick').removeClass('pick');
      var ln= el.target.id.substr(2);
      dbg_show_line(ln,'pick');
  })
  dbg.lines
    // -----------------------------------==> .. click na zdrojový text
    .click( el => {
      var l= dbg_context(el.target);
      dbg_show_line(l,'pick');
      dbg_clear();
    })
    // -----------------------------------==> .. dvojclick na zdrojový text
    .dblclick( el => {
      console.log(2);
      var //x= dbg_context(el.target),
          sel= window.getSelection(),
          range= sel.getRangeAt(0);
      var text= range ? range.startContainer.data.substring(range.startOffset,range.endOffset) : '';
      if ( text ) {
        dbg.help
          .css({display:'block'})
          .html(text);
        dbg.dbg_help(dbg.typ,text);
      }
    })
    // -----------------------------------==> .. kontextové menu pro zdrojový text
    .contextmenu( menu_el => {
      var l= dbg_context(menu_el.target),
          c= get_caret(),
          text= "lc="+l+','+c; 
      dbg_show_line(l,'pick');
      let y= dbg_find_block(dbg.name,l,c), elem= y.elem, block= y.block;
      if ( block ) 
        text+= '<br><br> ... block '+block.id+' / '+block.type+' _lc-lc_='+block.desc._lc+'-'+block.desc.lc_;
      if ( elem && elem.type=='proc') 
        text+= '<br><br> ... elem '+elem.id+' / '+elem.type+' _lc-lc_='+elem.desc._lc+'-'+elem.desc.lc_;
      else if ( elem ) 
        text+= '<br><br> ... elem '+elem.id+' / '+elem.type+' _lc='+elem.desc._lc;
      dbg.help
        .css({display:'block'})
        .html(y.msg+'<br><br>'+text);
      
      // zobraz kontextové menu podle kontextu elem
      switch (elem ? elem.type : null) {
        case 'var':
        case 'const':
        case 'field': case 'field.date': case 'field.list':
        case 'label':
        case 'radio':
        case 'check':
        case 'select': case 'select.map': case 'select.map0': case 'select.auto':{
          dbg_contextmenu([
            ['zjisti hodnotu', function(el) {
                let value= elem.get();
                if ( typeof value == "object" )
                  value= doc.Ezer.fce.debug(value,elem.id,3);
                else
                  value= elem.id+'='+value;
                dbg_touch(value,menu_el)
                return false;
            }],
            ['změň hodnotu', function(el) {
                let value= elem.get();
                if ( typeof value == "object" ) {
                  value= doc.Ezer.fce.debug(value,elem.id+" ... NELZE ZMĚNIT",3);
                  dbg_touch(value,menu_el)
                }
                else {
                  value= dbg_prompt(elem.id,value,function(val){elem.set(val);return false;},menu_el);
                }
                return false;
            }]
          ],menu_el);
          break;}
        case 'case':{
          dbg_contextmenu([
            ['zjisti hodnotu', function(el) {
                let value= elem.owner.get();
                value= 'radio of '+elem.id+'='+value;
                dbg_touch(value,menu_el)
                return false;
            }]
          ],menu_el);
          break;}
        case 'proc':{
          let lc= elem.desc._lc ? elem.desc._lc.split(',') : null;
          l= lc ? lc[0] : l;
          let touch= function(type,on) {x
            let elem= type=='break' ? 'stops' : 'traces',
                list= doc.Ezer.sys.dbg.files[doc.Ezer.sys.dbg.file][elem];
            dbg_touch('proc '+elem.id,menu_el);
            if ( on ) {
              dbg.src[l].addClass(type);
              list.push(l);
            }
            else {
              dbg.src[l].removeClass(type);
              let i= list.indexOf(l);
              if ( i>-1 ) list.splice(i);
            }
          }
          dbg_contextmenu([
            ['nastav trasování', function(el) {
                elem.proc_trace(1);
                touch('trace',1);
                return false;
            }],
            ['zruš trasování', function(el) {
                elem.proc_trace(0);
                touch('trace',0);
                return false;
            }],
            ['-zastopuj proceduru', function(el) {
                elem.proc_stop(1);
                touch('break',1);
                return false;
            }],
            ['uvolni proceduru', function(el) {
                elem.proc_stop(0);
                touch('break',0);
                return false;
            }],
//            ["-alert('ahoj!')", function(el) {
//                dbg_script("alert('ahoj!');return('Ahoj šéfe ...');",block);
//                return false;
//            }],
//            ["zalozka.get", function(el) {
//                dbg_script("zalozka.get",block);
//                return false;
//            }],
//            ["string.get", function(el) {
//                dbg_script("string.get",block);
//                return false;
//            }],
            ["-vyhodnoť výraz", function(el) {
                dbg_prompt(`výraz je v kontextu procedury ${elem.id}`,dbg_last_script,
                    function(script){
                      dbg_last_script= script;
                      dbg_script(script,block);
                      return false;
                    },menu_el);
                return false;
            }]
          ],menu_el);
          break;}
      }
      return false;
    })
};
// ------------------------------------------------------------------------------------ dbg_onunload
// DBG - voláno z dbg3.php
// zavření okna
function dbg_onunload(typ,position) {
  if ( typ==='ezer' ) {
    Ezer.sys.dbg.win_ezer= null;
    Ezer.sys.dbg.file= '';
    // zápis polohy a rozměru do cookies ezer_dbg_win=l,t,w,h ==> . dbg unload
    Ezer.fce.set_cookie('ezer_dbg_win',position);
  }
  else if ( typ=='php' )  {
    Ezer.sys.dbg.win_php= null;
    Ezer.fce.set_cookie('ezer_dbg_win2',position);
  }
}
// ------------------------------------------------------------------------------- dbg_oncontextmenu
// DBG - voláno z dbg3.php
// akce kontextového menu na určitém řádku
// op= stop+ | stop- | trace+ | trace- | dump
function dbg_oncontextmenu(line,op) {
  var found= null;
  var type= op=='dump' ? 'var' : 'proc';
  if ( doc.Ezer.sys.dbg ) {
    var walk = function(o,ln) {
      if ( o.part ) for (var i in o.part) {
        if ( found ) break;
        var oo= o.part[i];
        if ( oo.desc && oo.desc._lc && oo.type==type && oo.desc._lc.includes(ln) ) {
          found= {id:i,block:oo};
          break;
        }
        found= walk(oo,ln);
        if ( !found && oo instanceof Ezer.Var && oo._of=='form' && oo.value ) {
          found= walk(oo.value,ln);
        }
      }
      return found;
    };
    var dbg= doc.Ezer.sys.dbg;
    dbg.line= line;
    // nalezení Ezer.Block podle dbg.start
    var ctx= [], known;
    known= doc.Ezer.run_name(dbg.start,null,ctx);
    if ( known ) for (var i=ctx.length-1; i>=0; i--) {
      var o= ctx[i];
      if ( o.desc._file==dbg.file ) { // nejvyšší blok - budeme hledat řádek
        found= walk(o,line+',');
        if ( found ) {
          dbg.id= found.id;
          dbg.block= found.block;
          break;
        }
      }
    }
    // upravení found - jen hodnotové var
    if ( found && type=='var' && (found.block._of=='form' || found.block._of=='area')) {
      found= null;
    }
    // vlastní ladící akce
    if ( found ) switch (op) {
      case 'dump':
        if ( typeof dbg.block.value == "object" )
          found.value= doc.Ezer.debug(dbg.block.value,dbg.id,3);
        else
          found.value= dbg.id+'='+dbg.block.value;
        break;
      case 'stop+':
        dbg.block.proc_stop(1);
        break;
      case 'stop-':
        dbg.block.proc_stop(0);
        break;
      case 'trace+':
        dbg.block.proc_trace(1);
        break;
      case 'trace-':
        dbg.block.proc_trace(0);
        break;
    }
  }
  return found;
}
// --------------------------------------------------------------------------------- dbg find_block
// DBG - voláno z dbg3.php
// akce kontextového menu na určitém řádku
// op= stop+ | stop- | trace+ | trace- | dump
function dbg_find_block(name,l,c) {
  var find_block, find_elem, lc_code, // vnitřní funkce
      block_file, block= null, elem= null, msg= '', 
      elems= [
        'var','const',
        'label','radio','check',
        'field','field.list','field.date',
        'select','select.map','select.map0','select.auto'
      ];
  // -------------------------------- lc code
  lc_code= function(b_lc) { 
    b_lc= b_lc.split(',');
    let code= b_lc[0].padStart(4,'0')+b_lc[1].padStart(4,'0');
    return code;
  } 
  let lc= lc_code(`${l},${c}`);
  // -------------------------------- find_block
  find_block= function(b) { 
    var found= false,
        file= b._file || b.desc ? b.desc._file : null;
    if ( file==block_file ) {
      found= b;
    }
    else if ( b.part ) {
      for ( let bi in b.part ) {
        found= find_block(b.part[bi]);
        if ( found ) break;
      }
    }
    return found;
  }
  // ------------------------------- find elem
  var found_elem= null,
      found_block= null;
  find_elem= function(top) { 
    if ( !found_elem && top.part ) {
      for (let ti in top.part) {
        let b= top.part[ti];
        if ( b.type=='var' && b._of=='form' && b.value ) {
          // nejprve projdeme rozšíření, pokud existuje
          if ( b.part && lc_inside(b) ) {
            let br= find_elem(b);
            if ( br ) {
              found_elem= found_elem ? found_elem : br;
              found_block= found_block ? found_block : b;
              break;
            }
          }
          // až potom odkázanou form
          let form= b.value;
          // pokud leží v našem souboru
          let form_file= form.app_file();
          if ( form_file.file!=block_file ) {
            continue;
          }
          if ( form.part && lc_inside(form) ) {
            b= find_elem(form);
            if ( b ) {
              found_elem= found_elem ? found_elem : b;
              found_block= found_block ? found_block : form;
              break;
            }
          }
        }
        else if ( elems.includes(b.type) && lc_inside(b) ) {
          // našli jsme element ... projdeme případné složky
          if ( b.part ) {
            for (let pi in b.part) {
              let p= b.part[pi];
              if ( lc_inside(p) ) {
                found_elem= found_elem ? found_elem : p;
                found_block= found_block ? found_block : b;
                break;
              }
            }            
          }
          found_elem= found_elem ? found_elem : b;
          break;
        }
        else if ( b.part ) {
          let b1= find_elem(b);
          if ( b1 ) {
            found_elem= found_elem ? found_elem : b1;
            found_block= found_block ? found_block : b;
            break;
          }
        }
        else {
          if ( lc_inside(b) ) {
            found_elem= found_elem ? found_elem : b;
            found_block= found_block ? found_block : b;
          }
        }
      }
    }
  }
  // --------------------------------- lc inside b
  lc_inside= function(b) { 
    let ok= false,
        _lc= b.desc._lc, 
        lc_= b.desc.lc_;
    if ( _lc ) {
      _lc= lc_code(b.desc._lc);
      if ( lc_ ) {
        lc_= lc_code(b.desc.lc_);
      }
      else if ( b.id ) {
        let l= _lc.substr(0,4), 
            c= b.id.length+Number(_lc.substr(4,4));
        lc_= lc_code(`${l},${c}`);
      }
      else {
        lc_= "00000000";
      }
      ok= _lc<=lc && lc<=lc_;
    }
    return ok;
  }
  // --------------------------------- main
  var root_file_ezer= name.match(/(.*\/)(.*)\.(ezer)/);
  if ( root_file_ezer[3]=='ezer' ) {
    block_file= root_file_ezer[2];
    block= find_block(doc.Ezer.run.$);
    if ( block ) {
      find_elem(block);
      if ( found_elem ) {
        elem= found_elem;
        msg+= elem.type+' '+elem.id;
        switch ( elem.type ) {
          case 'var':
          case 'const':
          case 'field': case 'field.date': case 'field.list':
          case 'label':
          case 'check':
          case 'case':
            msg+= ' (value='+elem.get()+')';
            break;
          case 'select': case 'select.map': case 'select.map0': case 'select.auto':
            msg+= ' (key='+elem.key()+', value='+elem.get()+')';
            break;
          case 'proc':
            let del= '';
            msg+= ' (';
            for (let par in elem.desc.par) {
              msg+= del+par;
              del= ',';
            }
            msg+= ')';
            del= ' var ';
            for (let par in elem.desc.var) {
              msg+= del+par;
              del= ',';
            }
            break;
        }
      }
    }
  }
  return {block:found_block,elem:found_elem,msg:msg};
}
// =======================================================================================> DEBUGGER
jQuery.fn.extend({
  // ------------------------------------------------- + scrollIntoViewIfNeeded
  scrollIntoViewIfNeeded: function() {
    elem= this[0];
    var rect= elem.getBoundingClientRect();
    if (rect.bottom > window.innerHeight) {
      elem.scrollIntoView(false);
      elem.parentElement.parentElement.scrollTop+= 10*13;
    }
    if (rect.top < 0) {
      elem.scrollIntoView(true);
      elem.parentElement.parentElement.scrollTop-= 5*13;
    }
  }
})
// --------------------------------------------------------------------------------------- dbg clear
function dbg_clear() {
  dbg.log.css({display:'none'});
  dbg.prompt.css({display:'none'});
  dbg.help.css({display:'none'});
}
// -------------------------------------------------------------------------------------- dbg reload
function dbg_reload(file,ln=0) {
  let app= opener ? opener.Ezer.root : window.Ezer.root;
  dbg_ask({cmd:'source',app:app,file:file,line:ln},dbg_reload_);
}
function dbg_reload_(y) {
  dbg.name= y.name;
  doc.Ezer.sys.dbg.file= y.file;
  let files= doc.Ezer.sys.dbg.files;
  // -----------------------------------==> .. doplnění seznamu modulů
  dbg.files.empty();
  for (let file in files) {
    let selected= file==y.file ? ' selected' : '';
    dbg.jQuery(`<option${selected}>${file}</option>`).appendTo(dbg.files);
  }
  dbg_show_text(y.lines); // obnoví src a not
  dbg.focus();
  // -----------------------------------==> .. obnovení stavu
  for (let ln of files[y.file].stops) {
    dbg.src[ln].addClass('break');
  }
  for (let ln of files[y.file].traces) {
    dbg.src[ln].addClass('trace');
  }
  if ( files[y.file].stop ) {
    dbg.src[files[y.file].stop].addClass('stop');
  }
  // pokud není definovaná line použij zapamatovanou
  let line= Number(y.line) ? Number(y.line) : files[y.file].pick;
  dbg.dbg_show_line(line,'pick');
}
// ==========================================================================> Komunikace s aplikací
// ----------------------------------------------------------------------------------- dbg proc_stop
// ukáže informaci a zastopované proceduře
//   cnt=Ezer.continuation, cnti= aktivační záznam
function dbg_show_proc(cnt,on) {
  let args= function(cnti,cnt) {
    let a= '(', 
        del= '',
        proc= cnti.proc,
        top= cnti.act,
        bottom= top-proc.desc.nvar-proc.desc.npar+1,
        par= proc.desc.par ? Object.keys(proc.desc.par) : null;
    for (let i= 0; i<proc.desc.npar; i++) {
      let offset= proc.desc.par[par[i]],
          val= cnt.stack[bottom+offset];
      a+= del+par[i]+':'+cnt.val(val);
      del= ',';
    }
    return a+')';
  }
  let msg= '?',
      lc= cnt.proc.desc._lc ? cnt.proc.desc._lc.split(',') : null;
  if ( lc ) {
    let pos= cnt.proc.app_file();
    if ( on ) {
      // stopnutí procedury
      msg= 'proc '+cnt.proc.id+args(cnt,cnt)+' stopped';
      doc.Ezer.sys.dbg.files[pos.file].stop= lc[0];
      // pokus o trace-back
      for (let i= cnt.calls.length-1; i>0; i--  ) {
        let proc= cnt.calls[i].proc,
            lc= proc.desc._lc ? proc.desc._lc.split(',') : null,
            obj= proc.owner.type+' '+proc.owner.id+'.';
        msg+= '<br>called from '+`<span onclick='dbg_show_line(${lc[0]});'>`+obj
            +proc.id+"</span>"+args(cnt.calls[i],cnt);
      }
      if ( doc.Ezer.sys.dbg.file==pos.file ) {
        // pokud je procedura v otevřeném souboru
        dbg.src[lc[0]].addClass('stop').scrollIntoViewIfNeeded();
      }
      else {
        // jinak zobraz její soubor a nastav pick na stop
        dbg.dbg_reload(pos.file,lc[0]);
      }
    }
    else {
      // uvolnění procedury
      dbg.src[lc[0]].removeClass('stop');
      doc.Ezer.sys.dbg.files[pos.file].stop= 0;
      msg= 'proc '+cnt.proc.id+' continued';
    }
  }
  dbg.help.html(msg);
  dbg.help.show();
}
// ----------------------------------------------------------------------------------- dbg_show_help
// ukáže HELP - voláno z App.dbg_help
function dbg_show_help(ret) {
  if ( ret.typ=='php' ) {
    if ( !doc.Ezer.sys.dbg.win_php ) {
      dbg_php_open(ret.php,ret.item);
    }
    doc.Ezer.sys.dbg.win_php.dbg_php_item(ret.item);
  }
  else {
    dbg.help.html(ret.html);
  }
}
// ------------------------------------------------------------------------------==> . dbg show_text
// zobrazení textu ve struktuře
function dbg_show_text(ln) {
//  // najdi dokument debuggeru
//  var dbg= Ezer.sys ? Ezer.sys.dbg.win_ezer.document : document;
  // odstraň staré src
  var ul= dbg.lines.find('ul');
  ul.empty();
  dbg.notes.empty();
  // vytvoř text
  dbg.src= [];
  dbg.not= [];
  for (i= 0; i<ln.length; i++) {
    var i1= i+1, lni= ln[i];
    lni= htmlentities(ln[i]);
    dbg.src[i1]= dbg.jQuery(
      `<li id="${i1}"><span class="line">${i1}</span><span class="text">${lni}</span></li>`)
      .appendTo(ul);
    // detekce dokumentace
    var note= ln[i].indexOf('=='+'>');
    if ( note!=-1 ) {
      dbg.not[i1]= dbg.jQuery(`<li id="${'N_'+i1}">${i}${ln[i].substr(note+3)}</li>`)
        .appendTo(dbg.notes)
    }
  }
}
function htmlentities(h) {
  // jednoduchá varianta php funkce
  return typeof(h)=='string' ? h.replace(/[<]/g,'&lt;').replace(/[>]/g,'&gt;') : h.toString();
}
// ------------------------------------------------------------------------------==> . dbg show_line
// zobrazení textu ve struktuře
function dbg_show_line(ln,css='pick') {
  dbg.dbg_clear();
  dbg.lines.find('li.pick').removeClass('pick');
  if ( dbg.src[ln] ) {
    dbg.src[ln]
      .addClass(css)
      .scrollIntoViewIfNeeded();
    doc.Ezer.sys.dbg.files[doc.Ezer.sys.dbg.file].pick= ln;
  }
  // označení poznámek
  dbg.notes.find('li.pick').removeClass('pick');
  for (var i= 1; i<dbg.src.length; i++ ) {
    if ( dbg.not[i] && ( i>=ln || i==dbg.not.length-1 )) {
      dbg.not[i]
        .addClass('pick')
        .scrollIntoViewIfNeeded();
      if ( i>ln ) {
        for (var j= i-1; j>0; j-- ) {
          if ( dbg.not[j] ) {
            dbg.not[j].addClass('pick');
            break;
          }
        }
      }
      break;
    }
  }
}
//---------------------------------------------------------------------------------- dbg contextmenu
//ff: fce.contextmenu (menu,el[,id,up=0])
//      zobrazení kontextového menu
//a: menu - [[text_položky_menu,funkce],...]
//   event - událost vyvolaná pravým tlačítkem myši
//   id - nepovinné id
//s: funkce
// Ezer.obj.contextmenu= {DOM:null,menu:null};
function dbg_contextmenu (menu,event,id,up) {
event= event||window.event;
var elem= id || event.target;
var menu= jQuery(elem).contextPopup({
  persistent: id ? true : false,
  up: up,
  items: menu
},event,id);
return 1;
}
// ------------------------------------------------------------------------------------- dbg context
function dbg_context(el) {
  el= jQuery(el);
  var li= el.parent(),
      ul= li.parent();
  for (var i=1; i<dbg.src.length; i++) {
    if ( dbg.src[i][0]==li[0] ) {
      x= i;
      break;
    }
  }
  return i;
}
// --------------------------------------------------------------------------------------- dbg touch
function dbg_touch(value,e) {
  dbg.log.css({display:'block',top:e.pageY||e.page.y,left:e.pageX||e.page.x})
     .html(value);
}
// -------------------------------------------------------------------------------------- dbg prompt
function dbg_prompt(txt,deflt,ret_fce,e) {
  dbg.prompt.css({display:'block',top:e.pageY||e.page.y,left:e.pageX||e.page.x});
  dbg.prompt.find('span').html(txt);
  let input= dbg.prompt.find('input');
  input.val(deflt).focus().keyup(function(e){
    if (e.keyCode == 13) {
      input.unbind();
      dbg.prompt.hide();
      ret_fce(input.val());
    }
  });
}
// ---------------------------------------------------------------------------------------- dbg help
// dotaz na server o help pro daný item
function dbg_help (typ,item) {
  if ( typ=='ezer' ) {
    dbg.doc_ask('item_help',[item],_dbg_help);
  }
}
function _dbg_help(y) { //Ezer.fce.echo(Ezer.fce.debug(y,"help"));
//  Ezer.fce.echo(y.value);
  doc.Ezer.sys.dbg.win_ezer.dbg_show_help(y.value);
  return y.value;
}
// --------------------------------------------------------------------------------------- get caret
function get_caret() {
  return window.getSelection().getRangeAt(0).startOffset;
}
// --------------------------------------------------------------------------------------- set caret
function set_caret(node,caret) {
  node.focus();
  var textNode= node.firstChild;
  if ( textNode ) {
    var clmn= Math.min(caret,textNode.length);
    var range= document.createRange();
    range.setStart(textNode, clmn);
    range.setEnd(textNode, clmn);
    var sel= window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }
}
// ======================================================================================> EVAL EXPR
// -------------------------------------------------------------------------------------- dbg script
// kompilace Ezerscriptu zadaného řetězcem a jeho zahájení v kontextu this.
// Metoda je určena především pro ladění programu z trasovacího okna,
// pokud je voláno z programu, vrací hodnotu 1 - nečeká na ukončení ezescriptu.
var dbg_script_block= null;
function dbg_script (script,block) {
  dbg_script_block= block;
  var s= block.app_file();     // zjistí {app:app,file:file,root:root}
  var self= '';
  for (var o= block; o.owner; o= o.owner) {
    if ( o._library ) {
      break;
    }
    if ( o.type!='var' ) {
      self= o._id+(self ? '.'+self : '');
    }
  }
  self= self ? (o._library ? '#.' : '$.')+self : '$';
  var x= {cmd:'dbg_compile',context:{self:self,app:s.app,file:s.file},script:script};
  doc_ask('','',dbg_script_,x);
  return 1;
}
function dbg_script_ (y) {
  var val= '';
  if ( typeof(y)=='object' ) {
    if ( y.ret.code ) {
      new doc.Ezer.EvalClass(y.ret.code,dbg_script_block,[],'dbg',
          {fce:dbg_script_end,args:[],stack:[]});
    }
    if ( y.ret.err ) {
      dbg.help.html(y.ret.err);
      dbg.help.show();
    }
    if ( y.ret.trace ) {
      doc.Ezer.trace('C',y.ret.trace);
    }
  }
  else {
    doc.Ezer.fce.warning(y);
  }
  return val;
}
function dbg_script_end (value) {
  dbg.help.html(`returns ${value}`);
  dbg.help.show();
}
// ===========================================================================================> AJAX
// --------------------------------------------------------------------------------------- dbg error
function dbg_error(msg) {
   doc.Ezer.error(msg,'C');
}
// ----------------------------------------------------------------------------------------- dbg ask
function dbg_ask(x,then) {
  x.root= doc.Ezer.root;                  // název/složka aplikace
  x.app_root= doc.Ezer.app_root;          // {root].inc.php je ve složce aplikace
  jQuery.ajax({
    url: '../ezer3.1/dbg3.php',
//    url: 'dbg3.php',
    method: 'POST',
    data: x
  })
    .done(y => {
        if ( typeof(y)==='string' ) 
          dbg_error(`SERVER3: error for cmd='${x.cmd}':${y}`,'C');
        else if ( y.error )
          dbg_error(y.error,'C');
        else {
          if ( y.trace ) doc.Ezer.trace('u',y.trace);
          if ( then )
            then(y);
        }
    })
    .fail( (xhr) => {
      dbg_error('SERVER3 failure (dbg)'+(xhr.responseText||''),'C');
    });
}
// ----------------------------------------------------------------------------------------- doc ask
// dotaz na server se jménem funkce po dokončení
function doc_ask (fce,args,then,x) {
  x= x ? x : {cmd:'ask',fce:fce,args:args,nargs:args.length};
  x.root= doc.Ezer.root;                  // název/složka aplikace
  x.app_root= doc.Ezer.app_root;          // {root].inc je ve složce aplikace
//  x.session= Ezer.options.session;    // způsob práce se SESSION
  doc.Ezer.ajax({data:x,
    success: function(y) {
      doc.Ezer.App._ajax(-1);
      if ( !y  )
        doc.Ezer.error('ASK(dbg): syntaktická chyba v PHP na serveru:'+y,'C');
      else if ( y.error )
        doc.Ezer.error(y.error,'C');
      else {
        if ( y.trace ) doc.Ezer.trace('u',y.trace);
        then(y);
      }
    },
    error: function(xhr) {
      doc.Ezer.error('SERVER failure (dbg)'+(xhr.responseText||''),'C');
    }
  });
//   ajax.send();
  doc.Ezer.App._ajax(1);
}
/*
// ========================================================================================> DBG PHP
// -------------------------------------------------------------------------------==> . dbg php_open
// zobrazení textu ve struktuře
function dbg_php_open(fname,item) {
  var ltwh= Cookie.read('ezer_dbg_win2');
  ltwh= ltwh ? ltwh : '0,0,770,500';
  var x= ltwh.split(',');
  var position= 'left='+x[0]+',top='+x[1]+',width='+x[2]+',height='+x[3];
  var path= './ezer3.1/dbg3.php?err=1&typ=php&start='+item+'&src='+fname;
  var arg= position+',resizable=1,titlebar=0,menubar=0';
  doc.Ezer.sys.dbg.win_php= doc.open(path,'php',arg);
  doc.Ezer.sys.dbg.typ= 'php';
}
// -------------------------------------------------------------------------------==> . dbg php_item
// nalezení itemu v PHP
function dbg_php_item(item) {
  for (var ln= 0; ln<source.length; ln++) {
    if ( source[ln].indexOf(item)>=0 ) {
      dbg_show_line(ln+1,'pick');
      break;
    }
  }
}
*/
/*
// ------------------------------------------------------------------------------------==> . ON load
//   window.addEvent('load', function() {
//jQuery.when(jQuery.ready).then( function() {
//  log= jQuery('#log');
//  prompt= jQuery('#prompt');
//  help= jQuery('#help');
////  dbg_show_text(source);
//  if ( pick )
//    dbg_show_line(pick,'pick');
//  jQuery('#work').click( () => { dbg_clear() })
//});
// ----------------------------------------------------------------------------------==> . ON unload
//if ( window.MooTools!==undefined )
//window.addEvent('unload', function() {
//  var w= jQuery(window);
//  var p= window.screenX+','+window.screenY+','+w.width()+','+w.height();
//  opener.dbg_onunload(typ,p);
//     if ( typ=='php' ) {
//       Cookie.write('ezer_dbg_win2',p,{duration:100});
//       opener.Ezer.sys.dbg.win_php= null;
//     }
//})
//// -------------------------------------------------------------------------------------- dbg_onsave
//// DBG - voláno z dbg3.php
//function dbg_onsave(url,source) { 
////  Ezer.fce.echo("save "+url);//+"<hr>"+source);
//  dbg_ask('save_file',[url,source],_dbg_onsave);
//}
//function _dbg_onsave(y) { //Ezer.fce.echo(Ezer.fce.debug(y,"saved"));
////  Ezer.fce.echo(y.value);
//  return y.value;
//}
// ----------------------------------------------------------------------------------- dbg_save_text
//function dbg_save_text(source) {
//  opener.dbg_onsave(url,source);
//}
*/
/*
      var x= dbg_context(menu_el.target);
      if ( x ) {
        dbg_show_line(x,'pick');
        Ezer.fce.contextmenu([
          ['-oprav text', function(el) {
              if ( !open ) {
                for (var i=0; i<x.chs.length; i++) {
                  var text= x.chs[i].getElement('span.text');
                  text.contentEditable= true;
                }
                open= true;
                $('src').focus();
              }
              return false;
          }],
          ['ulož text', function(el) {
              if ( open ) {
                var ln= [], iln= 0, source= '';
                for (var i=0; i<x.chs.length; i++) {
                  var text= x.chs[i].getElement('span.text');
                  text.contentEditable= false;
                  var childs= text.childNodes;
                  for (var j= 0; j<childs.length; j++ ) {
                    if ( Browser.name=='chrome' || childs[j].nodeType==3 ) {
                      ln[iln++]= childs[j].textContent;
                    }
                  }
                }
                source= ln.join("\\n");
                open= false;
                dbg_save_text(source);
                dbg_show_text(ln);
              }
              return false;
          }]
        ],arguments[0]);
      }
      return false;
    })
    // ----------------------------==> .. klávesnice při opravě zdrojového textu
    .keydown( event => {
      var line= event.target.getParent(), clmn= get_caret(), text;
      switch (event.key) {
      case 'up':              // arrow-up:    předchozí řádek, stejný sloupec
        line= line.previousElementSibling;
        if ( line ) {
          text= line.getElement('span.text')
          line.focus();
          set_caret(text,clmn);
          event.stop();
        }
        break;
      case 'down':            // arrow-down:  další řádek, stejný sloupec
        line= line.nextElementSibling;
        if ( line ) {
          text= line.getElement('span.text')
          line.focus();
          set_caret(text,clmn);
          event.stop();
        }
        break;
      case 'backspace':       // backspace:   na začátku řádku spojit s předchozím
        if ( clmn==0 && line.id!='1' ) {
          var line2= line.previousElementSibling;
          var text2= line2.getElement('span.text');
          var clmn2= text2.innerText.length;
          text= line.getElement('span.text')
          text2.innerText+= text.innerText;
          line.destroy();
          set_caret(text2,clmn2);
          event.stop();
        }
        break;
      }
      return true;
    })
// 
};
function myTrimRight(x) {
  return x.replace(/ +$/gm,'');
}
*/
