<?php # (c) 2008 Martin Smidek <martin@smidek.eu>

// ============================================================================================> PHP
  // nastavení zobrazení PHP-chyb klientem při &err=1
  if ( isset($_GET['err']) && $_GET['err'] ) {
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 'On');
  }

  // parametry aplikace DBG
  $app=      'dbg';
  $app_name= 'Debugger pro framework Ezer';
  $skin=     'default';

  $src= $_GET['src'];
  $typ= isset($_GET['typ']) ? $_GET['typ'] : 'ezer';
  $start= isset($_GET['start']) ? $_GET['start'] : '';
  $pick= isset($_GET['pick']) ? $_GET['pick'] : '';
  $mootools= isset($_GET['mootools']) ? $_GET['mootools'] : 'on';  // off vypne clientcide

  session_start();
  $app= $_GET['app'];
  $rel_root= $_SESSION[$app]['rel_root'];
  
  $url= "http://$rel_root/$src";
  $html= $notes= $lines= "";
  $lns= file($url,FILE_IGNORE_NEW_LINES);
  foreach($lns as $i=>$ln) {
    $ln= str_replace('</script','<\/script',$ln);
    $lines.= "\n\"".addslashes($ln).'",';
  }
  switch($typ) {
  case 'ezer':
    $background= 'oldlace';
    break;
  case 'php':
    $background= '#fafaff';
    break;
  }
  $html= html_closure($src,$notes,$html,$src,$lines,$typ,$start,$background,$pick);
  echo $html;
// ------------------------------------------------------------------------------------ html_closure
function html_closure($win_name,$notes,$source,$url,$lines,$typ,$start,$background,$pick) {
  global $mootools;
  $clientcide= $mootools=='on'
    ? "<script src='client/licensed/clientcide.js' type='text/javascript' charset='utf-8'></script>"
    : '';
  $html= <<<__EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <link rel="shortcut icon" href="./ezer3/client/img/dbg.ico" />
    <title>$win_name</title>
    <script src="client/licensed/jquery-3.2.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="client/licensed/jquery-noconflict.js" type="text/javascript" charset="utf-8"></script>
    $clientcide
    <script type="text/javascript">
      Ezer= {fce:{},obj:{}};
    </script>
    <script src="client/ezer_lib3.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript">
// =====================================================================================> JAVASCRIPT
  var typ= '$typ';
  var start= '$start';
  var pick= '$pick';
  var log;
  var open= false;
  var help;
  var src= not= [];
//   var win_php= win_js= null;
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
    help= jQuery('#help');
    dbg_show_text(source);
    if ( pick )
      dbg_show_line(pick,'pick');
    jQuery('body').click( () => {
      log.css({display:'none'});
      help.css({display:'none'});
    })
  });
// ----------------------------------------------------------------------------------==> . ON unload
  if ( window.MooTools!==undefined )
  window.addEvent('unload', function() {
    var pos= window.getCoordinates();
    var p= window.screenX+','+window.screenY+','+pos.width+','+pos.height;
    opener.dbg_onunload(typ,p);
//     if ( typ=='php' ) {
//       Cookie.write('ezer_dbg_win2',p,{duration:100});
//       opener.Ezer.sys.dbg.win_php= null;
//     }
  })
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
    var path= './ezer3/dbg3.php?err=1&typ=php&start='+item+'&src='+fname;
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
      src[i1]= jQuery(`<li id="\${i1}"><span class="line">\${i1}</span><span class="text">\${lni}</span></li>`)
        .appendTo(ul);
      // detekce dokumentace
      var note= ln[i].indexOf('=='+'>');
      if ( note!=-1 ) {
        not[i1]= jQuery(`<li id="\${'N_'+i1}">\${i}\${ln[i].substr(note+3)}</li>`)
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
/*
// ------------------------------------------------------------------------------------- contextmenu
  Ezer.obj.contextmenu= {DOM:null,menu:null};
  Ezer.fce.contextmenu= function (menu,event,id,up) {
    event= event||window.event;
    if ( Ezer.obj.contextmenu.DOM ) {
      Ezer.obj.contextmenu.DOM.getChildren().destroy();
    }
    else {
      Ezer.obj.contextmenu.DOM= new Element('ul',{'class':'ContextMenu'}).inject($('body'));
    }
    if ( id )
      Ezer.obj.contextmenu.DOM.set('id',id);
    menu.each(function(item) {
      if ( item ) {
        var a, title= item[0], fce= item[1];
        new Element('li').adopt(
          a= new Element('a',{html:title[0]=='-' ? title.substr(1) : title})
        ).inject(Ezer.obj.contextmenu.DOM);
        if ( title[0]=='-' ) {
          a.setStyles({borderTop:"1px solid #AAAAAA"});
        }
        a.addEvents({
          click: function(el) {
            fce(event.originalTarget||event.target);
            Ezer.obj.contextmenu.menu.hide();
            return false;
          }
        })
      }
    });
    if ( Ezer.obj.contextmenu.menu ) {
      Ezer.obj.contextmenu.menu.reinitialize({event:event,target:event.originalTarget||event.target,
        menu:Ezer.obj.contextmenu.DOM,own_listener:true,up:up});
    }
    else {
      Ezer.obj.contextmenu.menu=
        new ContextMenu({event:event,target:event.originalTarget||event.target,
          menu:Ezer.obj.contextmenu.DOM,own_listener:true,up:up});
    }
    return 1;
  }
*/
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

//         var line= win.document.getElementById(ln);
//         line.scrollIntoViewIfNeeded();
//         // zvýraznění poznámky
//         not.each(function(s){s.removeClass('pick')});
//         not[ln].addClass('pick');
//         // zvýraznění v textu
//         src.each(function(s){s.removeClass('pick')});
//         line.addClass('pick');
    })
    var found= null;
    jQuery('#src')
      // -----------------------------------==> .. click na zdrojový text
      .click( el => {
        var x= dbg_context(el.target);
        dbg_show_line(x,'pick');
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
        var x= dbg_context(menu_el.target);
        if ( x ) {
          dbg_show_line(x,'pick');
          Ezer.fce.contextmenu([
            ['zjisti hodnotu', function(el) {
                found= opener.dbg_oncontextmenu(x,'dump');
                dbg_touch(found ? found.value : '?',menu_el)
                return false;
            }],
            ['-nastav trasování', function(el) {
                found= opener.dbg_oncontextmenu(x,'trace+');
                if ( found ) {
                  dbg_touch('proc '+found.id,menu_el)
                  src[x].addClass('trace');
                }
                return false;
            }],
            ['zruš trasování', function(el) {
                found= opener.dbg_oncontextmenu(x,'trace-');
                if ( found ) src[x].removeClass('trace');
                return false;
            }],
            ['-zastopuj proceduru', function(el) {
                found= opener.dbg_oncontextmenu(x,'stop+');
                if ( found ) {
                  dbg_touch('proc '+found.id,menu_el)
                  src[x].addClass('break');
                }
                return false;
            }],
            ['uvolni proceduru', function(el) {
                found= opener.dbg_oncontextmenu(x,'stop-');
                if ( found ) src[x].removeClass('break');
                return false;
            }],
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
// */
  };
  function myTrimRight(x) {
    return x.replace(/ +$/gm,'');
  }
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
  var url= "$url";
  source= [
    $lines
  ];
// =========================================================================================> STYLES
    </script>
    <style>
      body {
        font-size: 8pt; font-family: monospace,consolas; overflow: hidden; }
      li {
        white-space: pre; list-style-type: none; text-overflow: ellipsis; overflow: hidden; }
      /* ----------------------- help */
      div#help {
        position: fixed; right: 30px; top: 25px; width: 300px; min-height: 100px;
        background-color: #eee; border: 1px solid #aaa; z-index: 2;
        overflow-y: auto; max-height: 50%; display: none; }
      #sources {
        position: fixed; right: 10px; top: 2px; font-size: 16px; color: lightgray; }
      /* ----------------------- notes */
      div#notes {
        padding: 0; margin-top: 5px; overflow-y: scroll; height: 100%;
        left: 0; width: 120px; position: absolute; }
      #notes ul {
        padding: 0; margin-top: 5px; }
      #notes li {
        cursor: alias; }
      /* ----------------------- source */
      #source {
        position: fixed; right: 10px; top: 2px; font-size: 16px; color: lightgray;}
      div#src {
        padding: 0; margin-top: 5px; overflow-y: scroll; height: 100%;
        left: 120px; right: 0px; position: absolute;}
      #src ul {
        padding: 0; margin-top: 5px; scroll-behavior: smooth;}
      li span.text {
        margin-left:40px; display: block; }
      li span.text[contenteditable=true] {
        word-wrap: inherit; outline: none; }
      li span.text[contenteditable=true]:focus {
        background-color:#ffa; }
      /* ----------------------- lines */
      li span.line {
        position: absolute;
        background-color: silver; vertical-align: top; padding-right: 5px; margin-right: 5px;
        width: 24px; text-align: right;  }
      /* ----------------------- break */
      li span.line {
        position: absolute;
        background-color: silver; vertical-align: top; padding-right: 5px; margin-right: 5px;
        width: 24px; text-align: right;  }
      li.break span {
        background-color: darkred;
        color: yellow; }
      /* ----------------------- trace */
      li span.line {
        position: absolute;
        background-color: silver; vertical-align: top; padding-right: 5px; margin-right: 5px;
        width: 24px; text-align: right;  }
      li.trace {
        background-color: silver; }
      li.curr {
        background-color: orange; }
      li.pick, span.pick {
        background-color: yellow; }
      /* ----------------------- debug */
      #log {
        position:absolute; display: none; background-color:#eee; box-shadow:5px 5px 10px #567;
        padding: 5px; }
      .dbg {
        margin:0; overflow-y:auto; font-size:8pt; line-height:13px; }
      table.dbg {
        border-collapse:collapse; margin:1px 0;}
      .dbg td {
        border:1px solid #aaa; font:x-small Arial;color:#777;padding:1px 3px; line-height:11px; }
      .dbg td.title {
        color:#000; background-color:#aaa; }
      .dbg td.label {
        color:#a33;}
      .dbg table.dbg_array {
        background-color:#ddeeff; }
      .dbg table.dbg_object {
        background-color:#ffffaa; }
      /* ----------------------- context menu */
.ContextMenu3 { border:1px solid #ccc; padding:2px; background:#fff; width:200px; list-style-type:none;
  display:none; position:absolute; box-shadow:5px 5px 10px #567; cursor:default; }
.ContextMenu3 li { margin:0; padding:0; color:#000; }
.ContextMenu3 li { display:block; padding:2px 2px 0px 25px; width:173px; text-decoration:none; }
.ContextMenu3 li i { margin-left:-15px; }
.ContextMenu3 li:hover { background-color:#b2b4bf; }
.ContextMenu3 li.disabled { color:#ccc; font-style:italic; }
.ContextMenu3 li.disabled:hover { background-color:#eee; }
.ContextFocus3 { background-color:#ffa !important;
}
/*
      .ContextMenu   {
        border:1px solid #ccc; padding:2px; background:#fff; width:200px; list-style-type:none;
        display:none; position:static; box-shadow:5px 5px 10px #567; cursor:default; }
      .ContextMenu .separator   {
        border-top:1px solid #999; }
      .ContextMenu li   {
        margin:0; padding:0; }
      .ContextMenu li a {
        display:block; padding:2px 2px 0px 25px; width:173px; text-decoration:none; color:#000; }
      .ContextMenu li a:hover   {
        background-color:#b2b4bf; }
      .ContextMenu li a.disabled {
        color:#ccc; font-style:italic; }
      .ContextMenu li a.disabled:hover {
        background-color:#eee; }
      .ContextFocus {
        background-color:#ffa !important; }
*/
    </style>
  </head>
  <body id='body' onload="dbg_onclick_start()" style="background-color:$background;">
    <div id="help">...</div>
    <div id="source">$win_name</div>
    <div id='notes'>
      <ul>$notes</ul>
    </div>
    <div id='src'>
      <ul>$source</ul>
    </div>
    <span id='log'></span>
  </body>
</html>
__EOD;
  return $html;
}

?>

