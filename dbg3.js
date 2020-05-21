// (c) 2020 Martin Smidek <martin@smidek.eu>

/* global source, url, name, Cookie, Ezer, Browser, log, help, pick, typ */

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
// ------------------------------------------------------------------------------------==> . ON load
//   window.addEvent('load', function() {
jQuery.when(jQuery.ready).then( function() {
  log= jQuery('#log');
  prompt= jQuery('#prompt');
  help= jQuery('#help');
  dbg_show_text(source);
  if ( pick )
    dbg_show_line(pick,'pick');
  jQuery('body').click( () => {
    log.css({display:'none'});
    prompt.css({display:'none'});
    help.css({display:'none'});
  })
});
// ----------------------------------------------------------------------------------==> . ON unload
if ( window.MooTools!==undefined )
window.addEvent('unload', function() {
  var w= jQuery(window);
  var p= window.screenX+','+window.screenY+','+w.width()+','+w.height();
  opener.dbg_onunload(typ,p);
//     if ( typ=='php' ) {
//       Cookie.write('ezer_dbg_win2',p,{duration:100});
//       opener.Ezer.sys.dbg.win_php= null;
//     }
})
// --------------------------------------------------------------------------------------- dbg clear
function dbg_clear() {
  
}
// ----------------------------------------------------------------------------------- dbg_show_help
function dbg_show_help(ret) {
  if ( ret.typ=='php' ) {
    if ( !opener.Ezer.sys.dbg.win_php ) {
      dbg_php_open(ret.php,ret.item);
    }
    opener.Ezer.sys.dbg.win_php.dbg_php_item(ret.item);
  }
  else {
    help.html(ret.html);
  }
}
// ----------------------------------------------------------------------------------- dbg_save_text
function dbg_save_text(source) {
  opener.dbg_onsave(url,source);
}
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
  opener.Ezer.sys.dbg.win_php= opener.open(path,'php',arg);
  opener.Ezer.sys.dbg.typ= 'php';
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
// ------------------------------------------------------------------------------==> . dbg show_text
// zobrazení textu ve struktuře
function dbg_show_text(ln) {
  // odstraň staré src
  var ul= jQuery('#src').find('ul');
  ul.empty();
  var notes= jQuery('#notes');
  notes.empty();
  // vytvoř text
  src= [];
  not= [];
  for (i= 0; i<ln.length; i++) {
    var i1= i+1, lni= ln[i];
    lni= htmlentities(ln[i]);
    src[i1]= jQuery(`<li id="${i1}"><span class="line">${i1}</span><span class="text">${lni}</span></li>`)
      .appendTo(ul);
    // detekce dokumentace
    var note= ln[i].indexOf('=='+'>');
    if ( note!=-1 ) {
      not[i1]= jQuery(`<li id="${'N_'+i1}">${i}${ln[i].substr(note+3)}</li>`)
        .appendTo(notes)
    }
  }
}
function htmlentities(h) {
  // jednoduchá varianta php funkce
  return typeof(h)=='string' ? h.replace(/[<]/g,'&lt;').replace(/[>]/g,'&gt;') : h.toString();
}
// ------------------------------------------------------------------------------==> . dbg show_line
// zobrazení textu ve struktuře
function dbg_show_line(ln,css) {
  //opener.console.log(ln);
  jQuery('#src li.pick').removeClass('pick');
  src[ln]
    .addClass(css)
    .scrollIntoViewIfNeeded();
  // označení poznámek
  jQuery('#notes li.pick').removeClass('pick');
  for (var i= 1; i<src.length; i++ ) {
    if ( not[i] && ( i>=ln || i==not.length-1 )) {
      not[i]
        .addClass('pick')
        .scrollIntoViewIfNeeded();
      if ( i>ln ) {
        for (var j= i-1; j>0; j-- ) {
          if ( not[j] ) {
            not[j].addClass('pick');
            break;
          }
        }
      }
      break;
    }
  }
}
//--------------------------------------------------------------------------------------- contextmenu
//ff: fce.contextmenu (menu,el[,id,up=0])
//      zobrazení kontextového menu
//a: menu - [[text_položky_menu,funkce],...]
//   event - událost vyvolaná pravým tlačítkem myši
//   id - nepovinné id
//s: funkce
// Ezer.obj.contextmenu= {DOM:null,menu:null};
Ezer.fce.contextmenu= function (menu,event,id,up) {
event= event||window.event;
var elem= id || event.target;
var menu= jQuery(elem).contextPopup({
  persistent: id ? true : false,
  up: up,
  items: menu
},event,id);
return 1;
}
// ------------------------------------------------------------------------------------- dbg_context
function dbg_context(el) {
  el= jQuery(el);
  var li= el.parent(),
      ul= li.parent();
  for (var i=1; i<src.length; i++) {
    if ( src[i][0]==li[0] ) {
      x= i;
      break;
    }
  }
  return i;
}
// --------------------------------------------------------------------------------------- dbg_touch
function dbg_touch(value,e) {
  log.css({display:'block',top:e.pageY||e.page.y,left:e.pageX||e.page.x})
     .html(value);
}
// --------------------------------------------------------------------------------------- dbg_touch
function dbg_prompt(txt,deflt,ret_fce,e) {
  prompt.css({display:'block',top:e.pageY||e.page.y,left:e.pageX||e.page.x});
  prompt.find('span').html(txt);
  let input= prompt.find('input');
  input.val(deflt).focus().keyup(function(e){
    if (e.keyCode == 13) {
      input.unbind();
      prompt.hide();
      ret_fce(input.val());
    }
  });
}
// ------------------------------------------------------------------------------- dbg_onclick_start
function dbg_onclick_start(win) {
  win= win ? win : window;
// /*
  // -----------------------------------==> .. click na poznámku
  jQuery('#notes')
    .click( el => {
      jQuery('li.pick').removeClass('pick');
      var ln= el.target.id.substr(2),
          line= jQuery('#'+ln);
      // zvýraznění poznámky
      not[ln]
        .addClass('pick');
      // zvýraznění v textu
      line
        .addClass('pick')
        .scrollIntoViewIfNeeded();
  })
  var found= null;
  jQuery('#src')
    // -----------------------------------==> .. click na zdrojový text
    .click( el => {
      var l= dbg_context(el.target);
      dbg_show_line(l,'pick');
    })
    // -----------------------------------==> .. dvojclick na zdrojový text
    .dblclick( el => {
      console.log(2);
      var //x= dbg_context(el.target),
          sel= window.getSelection(),
          range= sel.getRangeAt(0);
      var text= range ? range.startContainer.data.substring(range.startOffset,range.endOffset) : '';
      if ( text ) {
        help
          .css({display:'block'})
          .html(text);
        opener.dbg_help(typ,text);
      }
    })
    // -----------------------------------==> .. kontextové menu pro zdrojový text
    .contextmenu( menu_el => {
      var l= dbg_context(menu_el.target),
          c= get_caret(),
          text= "lc="+l+','+c; 
      dbg_show_line(l,'pick');
      let y= opener.dbg_find_block(name,l,c), elem= y.elem, block= y.block;
      if ( block ) 
        text+= '<br><br> ... block '+block.id+' / '+block.type+' _lc-lc_='+block.desc._lc+'-'+block.desc.lc_;
      if ( elem && elem.type=='proc') 
        text+= '<br><br> ... elem '+elem.id+' / '+elem.type+' _lc-lc_='+elem.desc._lc+'-'+elem.desc.lc_;
      else if ( elem ) 
        text+= '<br><br> ... elem '+elem.id+' / '+elem.type+' _lc='+elem.desc._lc;
      help
        .css({display:'block'})
        .html(y.msg+'<br><br>'+text);
      
      // zobraz kontextové menu podle kontextu elem
      switch (elem.type) {
        case 'var':
          Ezer.fce.contextmenu([
            ['zjisti hodnotu', function(el) {
                let value= elem.get();
                if ( typeof value == "object" )
                  value= opener.Ezer.fce.debug(value,elem.id,3);
                else
                  value= elem.id+'='+value;
                dbg_touch(value,menu_el)
                return false;
            }],
            ['změň hodnotu', function(el) {
                let value= elem.get();
                if ( typeof value == "object" ) {
                  value= opener.Ezer.fce.debug(value,elem.id+" ... NELZE ZMĚNIT",3);
                  dbg_touch(value,menu_el)
                }
                else {
                  value= dbg_prompt(elem.id,value,function(val){elem.set(val);return false;},menu_el);
                }
                return false;
            }]
          ],arguments[0]);
          break;
        case 'proc':
          Ezer.fce.contextmenu([
            ['nastav trasování', function(el) {
                elem.proc_trace(1);
                dbg_touch('proc '+elem.id,menu_el)
                src[l].addClass('trace');
                return false;
            }],
            ['zruš trasování', function(el) {
                elem.proc_trace(0);
                dbg_touch('proc '+elem.id,menu_el)
                src[l].removeClass('trace');
                return false;
            }],
            ['-zastopuj proceduru', function(el) {
                elem.proc_stop(1);
                dbg_touch('proc '+elem.id,menu_el)
                src[l].addClass('break');
                return false;
            }],
            ['uvolni proceduru', function(el) {
                elem.proc_stop(0);
                dbg_touch('proc '+elem.id,menu_el)
                src[l].removeClass('break');
                return false;
            }]
          ],arguments[0]);
          break;
      }
      return false;
    })
};
// ------------------------------------------------------------------------------------------- caret
function get_caret() {
  return window.getSelection().getRangeAt(0).startOffset;
}
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
