<?php # (c) 2008 Martin Smidek <martin@smidek.eu>

// ============================================================================================> PHP

  session_start();

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
  $typ= isset($_GET['typ']) ? $_GET['typ'] : 'ezer';
  $start= isset($_GET['start']) ? $_GET['start'] : '';
  $pick= isset($_GET['pick']) ? $_GET['pick'] : '';
  $file= isset($_GET['file']) ? $_GET['file'] : '';

  $app= $_GET['app'];
  
  $html= "";
  switch($typ) {
  case 'ezer':
    $background= 'oldlace';
    break;
  case 'php':
    $background= '#fafaff';
    break;
  }
  $html= <<<__EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="cs" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <link rel="shortcut icon" href="client/img/dbg.ico" />
    <title>$src</title>
    <script src="client/licensed/jquery-3.2.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="client/licensed/jquery-noconflict.js" type="text/javascript" charset="utf-8"></script>
    <script src="client/licensed/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript">
      Ezer= {fce:{},obj:{}};
    </script>
    <script src="client/ezer_lib3.js" type="text/javascript" charset="utf-8"></script>
    <script src="dbg3.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript">
// =====================================================================================> JAVASCRIPT
  var name= "$src";         // GET src
  var typ= '$typ';          // GET type=ezer 
  var start= '$start';      // GET start
  var pick= '$pick';        // GET pick
  var src= not= [];         // array of DOM ezer, array of DOM poznámek
  var help, log, prompt,    // DOM elements
      lines, notes, files;
  var doc, dbg;             // document aplikace a debuggeru
  var app= '$app';          // ajax
  //-var url= "$src";
  //-var open= false;       // editor

 jQuery(function(){
    // rozlišení dvou oken-dokumentů
    opener.doc= doc= opener;
    opener.dbg= dbg= window;
    // zapamatované elementy DOM
    log=    jQuery('#log');
    prompt= jQuery('#prompt');
    help=   jQuery('#help');
    lines=  jQuery('#lines');
    notes=  jQuery('#notes');
    files=  jQuery('#files');
    // inicializace 
    dbg_onclick_start('$file');
  });
// =========================================================================================> STYLES
    </script>
    <style>
      body, select {
        font-size: 8pt; font-family: monospace,consolas; overflow: hidden; /*margin: 0;*/ }
      li {
        white-space: pre; list-style-type: none; text-overflow: ellipsis; overflow: hidden; }
      /* ----------------------- help */
      div#help {
        position: fixed; right: 30px; top: 25px; width: 300px; min-height: 100px;
        background-color: #eee; border: 1px solid #aaa; z-index: 2;
        overflow-y: auto; max-height: 50%; display: none; }
      div#help span {
        text-decoration: underline; color: blue; cursor: alias;}
      #sources {
        position: fixed; right: 10px; top: 2px; font-size: 16px; color: lightgray; }
      /* ----------------------- notes */
      div#filnot {
        padding: 0; height: 100%; left: 0; width: 120px; position: absolute; }
      select#files {
        background-color: silver; position:absolute; height:20px; width:120px; }
      ul#notes  {
        overflow-y: scroll; padding: 0; margin-top:20px; height:calc(100% - 20px); }
      ul#notes li {
        cursor: alias; }
      /* ----------------------- source */
      div#lines {
        padding: 0; overflow-y: scroll; height: 100%;
        left: 120px; right: 0px; position: absolute;}
      #lines ul {
        padding: 0; margin-top: 0; scroll-behavior: smooth;}
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
        background-color: #ff244861;
        color: black; }
      li.stop span {
        background-color: #ff2448eb;
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
      #prompt {
        position:absolute; display: none; background-color:#eee; box-shadow:5px 5px 10px #567;
        padding: 5px; }
      #prompt span {
        display:block; }
      #prompt input {
        width:200px; }
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
.ContextMenu3 li.disabled3 { color:#ccc; font-style:italic; }
.ContextMenu3 li.disabled3:hover { background-color:#eee; }
.ContextFocus3 { background-color:#ffa !important;
}
    </style>
  </head>
  <body id='body' style="background-color:$background;">
    <div id="help">...</div>
    <div id='work'>
      <div id='filnot'>
        <select id='files' onchange="dbg_reload(this.value);">
          <option selected>$file.ezer</option>
        </select>
        <ul id="notes"><li>notes</li></ul>
      </div>
      <div id='lines'>
        <ul><li>lines</li></ul>
      </div>
      <span id='log'></span>
      <span id='prompt'><span></span><input></span>
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
  $y= $x;
  switch ($x->cmd) {
  case 'source':
    $file= "{$x->file}.ezer";
    $name= "{$x->app}/$file";
    $path= "{$_SESSION[$x->app]['abs_root']}/$name";
    if ( file_exists($path) ) {
      $y->lines= file($path,FILE_IGNORE_NEW_LINES);
      $y->name= $name;
    }
    else {
      $name= "ezer3.1/$file";
      $path= "{$_SESSION[$x->app]['abs_root']}/$name";
      if ( file_exists($path) ) {
        $y->lines= file($path,FILE_IGNORE_NEW_LINES);
        $y->name= $name;
      }
      else {
        $y->lines= array("modul {$x->file} se nepodařilo najít");
      }
    }
    break;
  }
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
?>

