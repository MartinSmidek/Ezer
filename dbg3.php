<?php # (c) 2008-2022 Martin Smidek <martin@smidek.eu>

// ============================================================================================> PHP

  error_reporting(E_ALL ^ E_NOTICE);
  session_start();
  
  $CodeMirror= 1;

  // nastavení zobrazení PHP-chyb klientem při &err=1
  if ( isset($_GET['err']) && $_GET['err'] ) {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 'On');
  }
  
  // AJAX volání 
  if ( count($_POST) && !isset($_POST['post']) ) {
    $x= array2object($_POST);
    $y= dbg_server($x);
    header('Content-type: application/json; charset=UTF-8');
    $yjson= json_encode($y);
    echo $yjson;
    exit;
  }
  
  // parametry aplikace DBG
  $app=      'dbg';
  $app_name= 'Debugger pro framework Ezer';
  $skin=     'default';

  $src= $_GET['src'];
  $start= isset($_GET['start']) ? $_GET['start'] : '';
  $pick= isset($_GET['pick']) ? $_GET['pick'] : '';
  $file= isset($_GET['file']) ? $_GET['file'] : '';

  $app= $_GET['app'];
  $rel_root= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https://' : 'http://';
  $rel_root.= $_SESSION[$app]['rel_root'];
  $ezer_version= $_SESSION[$app]['ezer'];
  
  $html= "";
  $background= 'oldlace';
  $scripts= '';
  if ($CodeMirror) {
    $scripts= <<<__EOD
    <script src="$rel_root/ezer$ezer_version/client/licensed/codemirror/lib/codemirror.js"></script>
    <link rel="stylesheet" href="$rel_root/ezer$ezer_version/client/licensed/codemirror/lib/codemirror.css">
    <script src="$rel_root/ezer$ezer_version/client/licensed/codemirror/mode/clike/clike.js"></script>
    <script src="$rel_root/ezer$ezer_version/client/licensed/codemirror/mode/php/php.js"></script>
    <script src="$rel_root/ezer$ezer_version/client/licensed/codemirror/addon/edit/matchbrackets.js"></script>
    <script src="$rel_root/ezer$ezer_version/client/licensed/codemirror/addon/edit/closebrackets.js"></script>
    <script src="$rel_root/ezer$ezer_version/client/licensed/codemirror/addon/selection/active-line.js"></script>
__EOD;
  }
  if (1)
  $html= <<<__EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="cs" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <link rel="shortcut icon" href="client/img/dbg.ico" />
    <title>$src</title>
    $scripts
    <script src="client/licensed/jquery-3.3.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="client/licensed/jquery-noconflict.js" type="text/javascript" charset="utf-8"></script>
    <script src="client/licensed/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript">
      Ezer= {fce:{},obj:{}};
    </script>
    <script src="client/ezer_lib3.js" type="text/javascript" charset="utf-8"></script>
    <script src="client/ezer_tree3.js" type="text/javascript" charset="utf-8"></script>
    <script src="dbg3.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="client/licensed/font-awesome/css/font-awesome.min.css" type="text/css" media="screen" charset="utf-8">
    <script type="text/javascript">
// =====================================================================================> JAVASCRIPT
  var name= "$src";         // GET src
  var start= '$start';      // GET start
  var pick= '$pick';        // GET pick
  var src= not= [];         // array of DOM ezer, array of DOM poznámek, array of php function lines
  var help, help_div, log, prompt,    // DOM elements
      wphp, lines, notes, files, 
      wcg, wcg_hdr, wcg_grf,
      editor;
  var doc, dbg;             // document aplikace a debuggeru
  var app= '$app';          // ajax
  var cg= null;             // poslední call graph 
  //-var url= "$src";
  //-var open= false;       // editor

 jQuery(function(){
    // rozlišení dvou oken-dokumentů
    opener.doc= doc= opener;
    opener.dbg= dbg= window;
    // zapamatované elementy DOM
    log=    jQuery('#log');
    header= jQuery('#header');
    prompt= jQuery('#prompt');
    help=   jQuery('#help');
    wcg=     jQuery('#cg');
    wcg_hdr= jQuery('#cg_hdr');
    wcg_grf= jQuery('#cg_grf');
    lines=  jQuery('#lines');
    editor= jQuery('#editor');
    php_editor= jQuery('#php_editor');
    wphp=  jQuery('#php');
    notes=  jQuery('#notes');
    files=  jQuery('#files');
    // reakce na zavření dbg okna 
    window.addEventListener('beforeunload', function (e) {
      dbg.dbg_onunload('ezer');
    });
    // inicializace 
    dbg_onclick_start('$file');
  });
// =========================================================================================> STYLES
    </script>
    <style>
      body, select {
        font-size: 8pt; font-family: monospace,consolas; overflow: hidden; margin: 0; }
      li {
        white-space: pre; list-style-type: none; height:13px; }
      .blur { filter: blur(5px); }
      /* ----------------------- help */
      div#help {
        position: fixed; display: none; right: 30px; top: 25px; width: 300px; 
        background-color: #eee; border: 1px solid #aaa; z-index: 2;
        overflow-y: auto; min-height: 100px; max-height: calc(50% - 30px); 
        box-shadow: 5px 5px 10px #567; }
      /* ----------------------- cg */
      div#cg {
        position:fixed; display:none; right: 30px; top: 25px; width: 300px; 
        min-height: 100px; height: calc(50% - 30px); max-height: 300px; 
        background-color: #eee; border: 1px solid #aaa; z-index: 2;
        box-shadow: 5px 5px 10px #567; }
      div#cg_hdr {
        height:27px; border-bottom: 3px double #aaa; padding:0 80px 0 3px; }
      button.cg_but {
        position: absolute; margin: 3px 3px 0 0; width: 20px; padding: 0; }
      div#cg_div {
        overflow-y: auto; height: calc(100% - 30px); }
      div#cg_grf {
        overflow-y: auto; width:100%; }
      
      #sources {
        position: fixed; right: 10px; top: 2px; font-size: 16px; color: lightgray; }
      /* ----------------------- notes */
      div#filnot {
        padding: 0; height: 100%; left: 0; width: 120px; position: absolute; }
      select#files {
        background-color: silver; position:absolute; height:20px; width:120px; }
      ul#notes  {
        overflow-y: scroll; padding: 0; margin-top:20px; margin-bottom: 0; height:calc(100% - 20px); }
      ul#notes li {
        cursor: alias; }
      /* ----------------------- php source */
      div#php {
        padding: 0; top:50%; height: 50%;
        left: 120px; right: 0px; position: absolute; 
        background-color:#e5f2ff; margin-top: 5px; border-top: 3px double black; }
      body div.CodeMirror {
        padding: 0; top: calc(50% + 21px); height: calc(50% - 21px); 
        left: 120px; right: 0px; position: absolute; }
      div#php-border {
        width: 100%; top: 0; height: 13px; background-color:#cce; 
        padding-left: 30px; border-right: 1px solid #ff00004a; }
      div#php-border span.edit {
        color:yellow; font-weight:bold; }
      #php ul {
        overflow-x: auto; overflow-y: scroll; position:relative;
        padding: 0; scroll-behavior: smooth; margin:0; height: calc(100% - 19px);}
      #php li span.line {
        background-color:#cce; }
      #php span.call {
        background-color:#cce; cursor:pointer; font-weight: bold; }
      /* ----------------------- source */
      textarea#editor, body div#work div.CodeMirror {
        top:14px; height: calc(100% - 14px); left: 120px; width: calc(100% - 120px); position: absolute; }
      div#header {
        position:fixed; width: 100%; top: 0; left: 120px; height: 14px; background-color:silver; 
        padding-left: 30px; }
      div#header span.edit {
        color:yellow; font-weight:bold; }
      div#lines {
        padding: 0; overflow-y: scroll; height: calc(100% - 14px); top: 14px;
        left: 120px; right: 0px; position: absolute; padding-top: 4px; }
      div#lines.upper {
        height: calc(50% - 14px); top: 14px; }
      div#gutter {
        position: fixed; left: 120px; width: 29px; top: 0; height: 100%; background: silver; }
      div#border {
        position: fixed; left: 747px; width: 0; top: 14px; height: 100%; 
        border-right: 1px solid #ff00004a; }
      #lines ul {
        padding: 0; margin-top: 0; scroll-behavior: smooth;}
      #work li b {
        text-shadow:0 0 black; }
      #work li i {
        text-shadow:0 0 black; background: lightgreen; }
      #work li u {
        text-shadow:0 0 black; background: lightsalmon; text-decoration: none}
      li span.notext {
        margin-left:34px; display: block; color:#999; }
      li span.text {
        margin-left:34px; display: block; }
      li span.text[contenteditable=true] {
        word-wrap: inherit; outline: none; }
      li span.text[contenteditable=true]:focus {
        background-color:#ffa; }
      /* ----------------------- lines */
      li span.line {
        position: absolute;
        background-color: silver; vertical-align: top; padding-right: 3px; margin-right: 5px;
        width: 26px; text-align: right;  }
      /* ----------------------- cg */
      li span.go {
        background-color: #ffdf6b; cursor:pointer;   }
      li span.cg {
        background-color: #e5f2ff; cursor:pointer;   }
      /* ----------------------- uzly CG */
      span.fce_php {
        background-color:#e5f2ff; } 
      span.fce_ezer {
        background-color:#ffdf6b; } 
      span.elem_ezer {
        background-color:lightgreen; } 
      /* ----------------------- break */
      /*li.break span {
        background-color: #ff244861;
        color: black; }*/
      span.break {
        background-color: #ff244861 !important;
        color: black; }
      li.stop span {
        background-color: #ff2448eb;
        color: yellow; }
      /* ----------------------- trace */
      li.trace span {
        background-color: #c0c0c0a6; }
      li.curr {
        background-color: orange; }
      li.pick, span.pick {
        background-color: yellow; }
      /* ----------------------- debug */
      #log {
        position:absolute; display: none; background-color:#eee; box-shadow:5px 5px 10px #567;
        padding: 5px; z-index: 4; max-height: 300px; overflow: auto; }
      #prompt {
        position:absolute; display: none; background-color:#eee; box-shadow:5px 5px 10px #567;
        padding: 5px; z-index: 3; }
      #prompt span {
        display:block; }
      #prompt input {
        width:200px; font-size: 8pt; font-family: monospace,consolas; }
      div.dbg {
        font-size:8pt; line-height:13px; position:relative;}
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
      /* ----------------------==> mooTree */
.mooTree_node {
  font-family: Verdana, Arial, Helvetica; font-size: 10px; white-space: nowrap; }
.mooTree_text {
  padding-top: 3px; height: 15px; cursor: pointer; }
.mooTree_img {
  float: left; width: 18px; height: 18px; overflow: hidden; }
.mooTree_selected {
  background-color: #e0f0ff; font-weight: bold; margin-right: 10px; }
      /* ----------------------- inverzní CG */
div.inverzniCG .mooTree_node {
  transform: scaleX(-1); }
div.inverzniCG .mooTree_text {
  transform: scaleX(-1); direction: rtl; display: flex; }
div.inverzniCG div.mooTree_selected {
  margin-right:0; }
      /* ----------------------- context menu */
.ContextMenu3 { border:1px solid #ccc; padding:2px; background:#fff; width:200px; list-style-type:none;
  display:none; position:absolute; box-shadow:5px 5px 10px #567,inset 20px 0 0 0px #ccc; cursor:default; }
.ContextMenu3 li { margin:0; padding:0; color:#000; }
.ContextMenu3 li { display:block; padding:2px 2px 0px 16px; text-decoration:none; }
.ContextMenu3 li i { margin-left:-15px; }
.ContextMenu3 li:hover { background-color:#b2b4bf; }
.ContextMenu3 li.disabled3 { color:#ccc; font-style:italic; }
.ContextMenu3 li.disabled3:hover { background-color:#eee; }
.ContextMenu3 li span { float: right; font-style: italic; }
.ContextFocus3 { background-color:#ffa !important;
}
      /* ----------------------- CodeMirror ---------------------- Ezer */
.cm-s-ezer span.cm-meta { color: #808000; }
.cm-s-ezer span.cm-number { color: #0000FF; }
.cm-s-ezer span.cm-keyword { font-weight: bold; text-shadow: 0 0 black; }
.cm-s-ezer span.cm-keyword-event { font-style: italic; background: lightgreen; text-shadow: 0 0 black; }
.cm-s-ezer span.cm-keyword-func { background: #ffdf6b; }
.cm-s-ezer span.cm-keyword-skill { background: lightsalmon; }
.cm-s-ezer span.cm-atom { font-weight: bold; color: #000080; }
.cm-s-ezer span.cm-def { color: #000000; }
.cm-s-ezer span.cm-variable { color: black; }
.cm-s-ezer span.cm-variable-2 { color: black; }
.cm-s-ezer span.cm-variable-3, .cm-s-ezer span.cm-type { color: black; }
.cm-s-ezer span.cm-property { color: black; }
.cm-s-ezer span.cm-operator { color: black; }
.cm-s-ezer span.cm-comment { color: #999999; }
.cm-s-ezer span.cm-string { color: #008000; }
.cm-s-ezer span.cm-string-2 { color: #008000; }
.cm-s-ezer span.cm-qualifier { color: #555; }
.cm-s-ezer span.cm-error { color: #FF0000; }
.cm-s-ezer span.cm-attribute { color: #0000FF; }
.cm-s-ezer span.cm-tag { color: #000080; }
.cm-s-ezer span.cm-link { color: #0000FF; }

.cm-s-ezer.CodeMirror { background: oldlace; }
.cm-s-ezer .CodeMirror-gutters { background: silver; }
.cm-s-ezer .CodeMirror-linenumber { color:black; }

.cm-s-ezer span.cm-builtin { color: #30a; }
.cm-s-ezer span.cm-bracket { color: #cc7; }

.cm-s-ezer  { font-size: 8pt; font-family: monospace,consolas; }

.cm-s-ezer .CodeMirror-matchingbracket { outline:1px solid cyan; color:black !important; }
.cm-s-ezer .CodeMirror-nonmatchingbracket { outline:1px solid red; color:black !important; }
.cm-s-ezer .CodeMirror-activeline-gutter { background: #ffff00; }
.cm-s-ezer .CodeMirror-activeline-background { background: #ffffaa; }

.CodeMirror-hints.ezer { font-family: Consolas; color: #616569; background-color: #ebf3fd !important; }
.CodeMirror-hints.ezer .CodeMirror-hint-active { background-color: #a2b8c9 !important; color: #5c6065 !important; }      
      
      /* ----------------------- CodeMirror ---------------------- PHP */
.cm-s-php span.cm-meta { color: #808000; }
.cm-s-php span.cm-number { color: #0000FF; }
.cm-s-php span.cm-keyword { font-weight: bold; text-shadow: 0 0 black; }
.cm-s-php span.cm-keyword-event { font-style: italic; background: lightgreen; text-shadow: 0 0 black; }
.cm-s-php span.cm-keyword-func { background: #ffdf6b; }
.cm-s-php span.cm-keyword-skill { background: lightsalmon; }
.cm-s-php span.cm-atom { font-weight: bold; color: #000080; }
.cm-s-php span.cm-def { color: #000000; }
.cm-s-php span.cm-variable { color: black; }
.cm-s-php span.cm-variable-2 { color: black; }
.cm-s-php span.cm-variable-3, .cm-s-php span.cm-type { color: black; }
.cm-s-php span.cm-property { color: black; }
.cm-s-php span.cm-operator { color: black; }
.cm-s-php span.cm-comment { color: #999999; }
.cm-s-php span.cm-string { color: #008000; }
.cm-s-php span.cm-string-2 { color: #008000; }
.cm-s-php span.cm-qualifier { color: #555; }
.cm-s-php span.cm-error { color: #FF0000; }
.cm-s-php span.cm-attribute { color: #0000FF; }
.cm-s-php span.cm-tag { color: #000080; }
.cm-s-php span.cm-link { color: #0000FF; }

body .cm-s-php.CodeMirror { background: #e5f2ff; }
.cm-s-php .CodeMirror-gutters { background: #cce; }
.cm-s-php .CodeMirror-linenumber { color:black; }
.cm-s-php .CodeMirror-activeline-gutter { background: #ffff00; }
.cm-s-php .CodeMirror-activeline-background { background: #ffffaa; }

.cm-s-php span.cm-builtin { color: #30a; }
.cm-s-php span.cm-bracket { color: #cc7; }

.cm-s-php  { font-size: 8pt; font-family: monospace,consolas; }

.cm-s-php .CodeMirror-matchingbracket { outline:1px solid cyan; color:black !important; }
.cm-s-php .CodeMirror-nonmatchingbracket { outline:1px solid red; color:black !important; }

 </style>
  </head>
  <body id='body' style="background-color:$background;">
    <div id="help" style='display:none'></div>
    <div id="cg">
      <button class="cg_but" title="expand" style="right:0px" onclick="dbg_cg_gc(99);">
        <i class="fa fa-asterisk"></i>
      </button>
      <button class="cg_but" title="inverzní" style="right:25px" onclick="dbg_cg_gc(1);">
        <i class="fa fa-long-arrow-left"></i>
      </button>
      <button class="cg_but" title="call graf" style="right:50px" onclick="dbg_cg_gc(0);">
        <i class="fa fa-long-arrow-right"></i>
      </button>
      <div id="cg_hdr"></div>
      <div id="cg_div">   
        <div id="cg_grf">
      </div>
    </div>
    </div>
    <div id='work'>
      <div id='filnot'>
        <select id='files' onchange="dbg_reload(this.value);">
          <option selected>$file.ezer</option>
        </select>
        <ul id="notes"><li>notes</li></ul>
      </div>
      <div id='header'></div>
      <textarea id='editor' style="display:none"></textarea>
      <div id='lines'>
        <div id='gutter'></div>
        <div id='border'></div>
        <ul><li>lines</li></ul>
      </div>
      <span id='log'></span>
      <span id='prompt'><span></span><input></span>
    </div>
    <textarea id='php_editor' style="display:none"></textarea>
    <div id='php' style='display:none'>
      <div id='php-border'></div>
      <ul><li>lines</li></ul>
    </div>
  </body>
</html>
__EOD;
  echo $html;
// =========================================================================================> SERVER
// -------------------------------------------------------------------------------------- dbg server
// AJAX volání z dbg3_ask
// na vstupu je definováno: x.app
function dbg_server($x) {
  global $ezer_path_root, $ezer_root, $trace, $dbg_info, $ezer_php_libr, $ezer_php, 
      $ezer_ezer, $ezer_version;
  $trace= '';
  $ezer_path_root= $_SESSION[$x->app]['abs_root'];
  $ezer_root= $x->app;
  chdir($ezer_path_root);
  if (file_exists("$ezer_root.inc.php"))
    require_once("$ezer_root.inc.php");
  else
    require_once("$ezer_root/$ezer_root.inc.php");
  $y= $x;
  switch ($x->cmd) {
  case 'source_php': // -------------------------------- get PHP
    $before= 12;
    $start= 0;
    $ezer_root= $x->app;
    $fce= $x->fce;
    $cg= $_SESSION[$ezer_root]['CG'];
    if (isset($cg->cg_calls) && isset($cg->cg_calls[$fce])) {
      // zjištění seznamu bezprostředně volaných funkcí
      $y->calls= array();
      foreach ($cg->cg_calls[$fce][0] as $call) {
        list($y->calls[])= explode(';',$call);
      }
      // získání řádků s textem funkce
      $fname= $cg->cg_phps[$cg->cg_calls[$fce][1]];
      $line1= $cg->cg_calls[$fce][2];
      $line2= $cg->cg_calls[$fce][3];
      $y->header= array("<b>$fce<b> in $fname ($line1-$line2)");
      $y->path= $fname;
      $y->mtime= filemtime($fname);
      $file= new SplFileObject($fname);
      $file->setFlags(SplFileObject::DROP_NEW_LINE);
      $lines= array();
      $before= min($before,$line1)-1;
      for ($ln= $line1-$before; $ln<=$line2; $ln++) {
        $file->seek($ln-1); 
        $txt= $file->current();
        if ($txt===false) break;
        $lines[$ln]= $txt;
      }
      // zrušení řádků před blokem komentářů před začátkem funkce
      $mazat= false;
      for ($ln= $line1-1; $ln>=$line1-$before; $ln--) {
        if (preg_match("~^(#|//|/\*)~",$lines[$ln]) && !$mazat) {
          $start++;
        }
        elseif (!$mazat) {
          $mazat= true;
        }
        if ($mazat)
          unset($lines[$ln]);
      }
      // zrušení řádků s komentáři na konci funkce
      $senil= array_reverse($lines,true);
      foreach ($senil as $ln=>$line) {
        if (!preg_match("~^(#|//|/\*)~",$line)) break;
        unset($lines[$ln]);
      }
      foreach ($lines as $ln=>$line) {
        $y->lines[$ln]= $line;
      }
      $y->begin= $line1-$start;
      $y->func= $line1;
      $y->end= $line2;
    }
    else {
      $y->lines= array("zdrojový modul PHP funkce '$fce' nelze najít");
    }
    break;
  case 'source': // ------------------------------------ get Ezer + CG
    $roots= isset($dbg_info) ? $dbg_info->src_path : array($x->app,"ezer$ezer_version");
    foreach ($roots as $root) {
      $file= "{$x->file}.ezer";
      $name= "$root/$file";
      $path= "$ezer_path_root/$name";
      $y->lines= null;
      if ( file_exists($path) ) {
        $y->lines= file($path,FILE_IGNORE_NEW_LINES);
        $y->mtime= filemtime($path);
        $y->name= $name;
        $y->path= $path;
        break;
      }
    }
    if ($y->lines===null) {
      $y->lines= array("modul {$x->file} se nepodařilo najít");
    }
    // získáme překlad a z něj CG
    $cg= null;
    $cpath= "$ezer_path_root/$root/code$ezer_version/{$x->file}.json";
    if ( file_exists($cpath)) {
      $loads= json_decode(file_get_contents($cpath));
      $cg= $loads->info->ezer;
//      $y->lines[]= "CG ok - $path - $cpath";
    }
    else {
      $y->msg[]= "CG ko - $path - $cpath";
    }
    // předáme CG
    $y->cg= $cg;
    // předáme seznam všech ezer modulů kvůli odkazům mezi nimi
    require_once("ezer$ezer_version/server/sys_doc.php");
    $cg_list= doc_php_cg();
    $y->app_ezer=  $cg_list->app_ezer;
    break;
  case 'save_source': // ---------------------------------- save file (file, type, value)
    global $ezer_php, $ezer_php_libr, $ezer_ezer, $err;
    $file= "{$x->file}.{$x->type}";
    $name= "{$x->app}/$file";
    $path= $x->path; //"$ezer_path_root/$name";
    $root= $ezer_root= $x->app;
    if ( file_exists($path) ) {
      $mtime= filemtime($path);
      // uložíme pouze, pokud nedošlo k externí změně
      if ($mtime==$x->mtime) {
        // napřed uložíme kopii do *.bak
        $bak= file_get_contents($path);
        file_put_contents("$path.bak",$bak);
        // potom je to jiné pro EZER a PHP
        switch ($x->type) {
          case 'ezer': // uložení zdroje EZER
            // potom uložíme změněný stav
            file_put_contents($path,$x->value);
            // a zkompilujeme 
            require_once("ezer$ezer_version/server/ae_slib.php");
            require_once("ezer$ezer_version/server/comp2.php");
            $state= comp_file($x->file,$root);
            $ok= substr($state,0,2);
            $y->msg= "'$name' uložen, kompilace $ok";
            break;
          case 'php': // výměna těla funkce v PHP
            require_once("ezer$ezer_version/server/ae_slib.php");
            $file= file($path,FILE_IGNORE_NEW_LINES);
            $new_fce= explode("\n",$y->value);
//            debug($new_fce,"$y->fce");
            array_splice($file,$y->begin-1,$y->end-$y->begin+1,$new_fce);
            $text= implode("\n",$file);
            file_put_contents($path,$text);
//            display('jsem display'); 
//            debug($file,"po výměně");
            $ok= 'ok';
            break;
        }
        if ($ok=='ok') {
          // restaurace CG
          require_once("ezer$ezer_version/server/sys_doc.php");
          doc_php_cg('*','*',1); // vždy přepočítat, nebrat ze SESSION
//          doc_php_cg('*','server/ae_slib.php',1); // vždy přepočítat, nebrat ze SESSION
          $cg_ok= isset($_SESSION[$root]['CG']) ? 'ok' : 'ko';
          $y->msg.= ", CG $cg_ok";
        }
        else {
          $y->err= 1;
          $y->msg.= "<br>$err<hr>$state<hr>$trace";
        }
      }
      else 
        $y->msg= "'$name' byl během editace změněn externím programem - vaše změny nebyly provedeny";
    }
    else 
      $y->msg= "'$name' (už) neexistuje";
    break;
  case 'reload_cg': // ---------------------------------- přepočítat CG pro: item, file
    // restaurace CG
    require_once("ezer$ezer_version/server/sys_doc.php");
    if ($x->sys_fce) {
      $y->cg= doc_php_tree($x->item,'*','*',$x->inverzni,true);
    }
    else {
      $y->cg= doc_php_tree($x->item,'*','',$x->inverzni,true);
    }
    break;
  }
  if ($trace) $y->trace= $trace;
  return $y;
}
# ------------------------------------------------------------------------------------- array2object
function array2object(array $array) {
  $object= new stdClass();
  foreach($array as $key => $value) {
    if(is_array($value)) {
      $object->$key= array2object($value);
    }
    else {
      $object->$key= $value;
    }
  }
  return $object;
}