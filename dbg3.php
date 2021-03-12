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
    <script src="client/ezer_tree3.js" type="text/javascript" charset="utf-8"></script>
    <script src="dbg3.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="client/licensed/font-awesome/css/font-awesome.min.css" type="text/css" media="screen" charset="utf-8">
    <script type="text/javascript">
// =====================================================================================> JAVASCRIPT
  var name= "$src";         // GET src
  var typ= '$typ';          // GET type=ezer 
  var start= '$start';      // GET start
  var pick= '$pick';        // GET pick
  var src= not= [];         // array of DOM ezer, array of DOM poznámek, array of php function lines
  var help, help_div, log, prompt,    // DOM elements
      php, lines, notes, files;
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
    prompt= jQuery('#prompt');
    help=   jQuery('#help');
    wcg=     jQuery('#cg');
    wcg_hdr= jQuery('#cg_hdr');
    wcg_grf= jQuery('#cg_grf');
    lines=  jQuery('#lines');
    php=  jQuery('#php');
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
        white-space: pre; list-style-type: none; /*text-overflow: ellipsis; overflow: hidden;*/ }
      /* ----------------------- help */
      div#help {
        position: fixed; display: none; right: 30px; top: 25px; width: 300px; 
        background-color: #eee; border: 1px solid #aaa; z-index: 2;
        overflow-y: auto; min-height: 100px; max-height: calc(50% - 30px); 
        box-shadow: 5px 5px 10px #567; }
      /* ----------------------- cg */
      div#cg {
        position:fixed; right: 30px; top: 25px; width: 300px; 
        min-height: 100px; height: calc(50% - 30px); max-height: 500px; 
        background-color: #eee; border: 1px solid #aaa; z-index: 2;
        box-shadow: 5px 5px 10px #567; }
      div#cg_hdr {
        height:27px; border-bottom: 3px double #aaa; }
      button.cg_but {
        position: absolute; margin: 3px 3px 0 0; width: 20px; padding: 0; }
      div#cg_div {
        overflow-y: auto; height: calc(100% - 30px); }
      div#cg_grf {
        overflow-y: auto; width:100%; }
      
      div#xxxhelp_div span {
        text-decoration: underline; color: blue; cursor: alias;}
      div#xxxhelp_div span.go {
        text-decoration: none; color: black; cursor: pointer; }
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
      div#php-border {
        width: 100%; top: 0; height: 13px; background-color:#cce; 
        padding-left: 30px; border-right: 1px solid #ff00004a; }
      #php ul {
        overflow-x: auto; overflow-y: scroll; position:relative;
        padding: 0; scroll-behavior: smooth; margin:0; height: calc(100% - 19px);}
      #php li span.line {
        background-color:#cce; }
      #php span.call {
        background-color:#cce; cursor:pointer; font-weight: bold; }
      /* ----------------------- source */
      div#lines {
        padding: 0; overflow-y: scroll; height: 100%;
        left: 120px; right: 0px; position: absolute; }
      div#border {
        position: fixed; left: 747px; width: 0; top: 0; height: 100%; 
        border-right: 1px solid #ff00004a; }
      #lines ul {
        padding: 0; margin-top: 0; scroll-behavior: smooth;}
      li b {
        text-shadow:0 0 black; }
      li i {
        text-shadow:0 0 black; background: lightgreen; }
      li u {
        text-shadow:0 0 black; background: lightsalmon; text-decoration: none}
      li span.notext {
        margin-left:40px; display: block; color:#999; }
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
      /* ----------------------- cg */
      li span.go {
        background-color: #ffdf6b; cursor:pointer;   }
      li span.cg {
        background-color: #e5f2ff; cursor:pointer;   }
      /* ----------------------- break */
      li.break span {
        background-color: #ff244861;
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
      <div id='lines'>
        <div id='border'></div>
        <ul><li>lines</li></ul>
      </div>
      <span id='log'></span>
      <span id='prompt'><span></span><input></span>
    </div>
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
  $y= $x;
  switch ($x->cmd) {
  case 'editor': // ------------------------------------ edit {file,line}
    $file= "{$x->file}.ezer";
    $name= "{$x->app}/$file";
    $path= "{$_SESSION[$x->app]['abs_root']}/$name";
    $notepad= 'C:\Program Files (x86)\Notepad++\notepad++';
    $dbg3_bat= 'C:\Ezer\beans\ezer3.1\dbg3.bat';
    $subpath= $x->app;
    if ( file_exists($path) ) {
      $y->name= $name;
    }
    else {
      $name= "ezer3.1/$file";
      $subpath= 'ezer3.1';
      $path= "{$_SESSION[$x->app]['abs_root']}/$name";
      if ( file_exists($path) ) {
        $y->name= $name;
      }
      else {
        $y->msg= "modul {$x->file} se nepodařilo najít";
      }
    }
    if (!isset($y->msg)) {
      $cmd= 'start "'.$notepad.'" '.$name." -n{$x->line}";
      $cmd= "start cmd.exe";
      $cmd= $dbg3_bat;
      $y->msg= $cmd;
//      exec("start /B notepad");
      exec($cmd);
//      $pid= popen("start /B ". $cmd, "r");
//      if ( $pid ) {
//        $msg= fread($pid, 2096);
//        $y->msg= "start editoru ($pid,$msg)";
//        pclose($pid);
//      }
//      else {
//        $y->msg= "$file -- start editoru zhavaroval";
//      }
    }
    break;
  case 'source_php': // -------------------------------- get PHP
    $before= 12;
    $start= 0;
    $ezer_root= $x->app;
    $fce= $x->fce;
//    $abs_root= $_SESSION[$ezer_root]['abs_root'];
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
      $y->header= array("$fce: $fname ($line1-$line2)");
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
      $y->start= $start;
    }
    else {
      $y->lines= array("zdrojový modul PHP funkce '$fce' nelze najít");
    }
    break;
  case 'source': // ------------------------------------ get Ezer + CG
    $file= "{$x->file}.ezer";
    $name= "{$x->app}/$file";
    $path= "{$_SESSION[$x->app]['abs_root']}/$name";
    $subpath= $x->app;
    if ( file_exists($path) ) {
      $y->lines= file($path,FILE_IGNORE_NEW_LINES);
      $y->name= $name;
    }
    else {
      $name= "ezer3.1/$file";
      $subpath= 'ezer3.1';
      $path= "{$_SESSION[$x->app]['abs_root']}/$name";
      if ( file_exists($path) ) {
        $y->lines= file($path,FILE_IGNORE_NEW_LINES);
        $y->name= $name;
      }
      else {
        $y->lines= array("modul {$x->file} se nepodařilo najít");
      }
    }
    // získáme překlad a z něj CG
    $cg= null;
    $cpath= "{$_SESSION[$x->app]['abs_root']}/$subpath/code/{$x->file}.json";
    if ( file_exists($cpath)) {
      $loads= json_decode(file_get_contents($cpath));
      $cg= $loads->info->ezer;
//      $y->lines[]= "CG ok - $path - $cpath";
    }
    else {
      $y->lines[]= "CG ko - $path - $cpath";
    }
    // předáme CG
    $y->cg= $cg;
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

