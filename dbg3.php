<?php # (c) 2008 Martin Smidek <martin@smidek.eu>

// ============================================================================================> PHP
  // nastavení zobrazení PHP-chyb klientem při &err=1
  if ( isset($_GET['err']) && $_GET['err'] ) {
    error_reporting(E_ALL & ~E_NOTICE);
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
  $html= <<<__EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="cs" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <link rel="shortcut icon" href="client/img/dbg.ico" />
    <title>$win_name</title>
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
  var typ= '$typ';
  var start= '$start';
  var pick= '$pick';
  var log, prompt;
  var open= false;
  var help;
  var src= not= [];
  var url= "$url";
  var name= "$win_name";
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
      #prompt {
        position:absolute; display: none; background-color:#eee; box-shadow:5px 5px 10px #567;
        padding: 5px; }
      #prompt span {
        display:block; }
      #prompt input {
        width:100px; }
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
      .ContextMenu li a.disabled3 {
        color:#ccc; font-style:italic; }
      .ContextMenu li a.disabled3:hover {
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
    <span id='prompt'><span></span><input></span>
  </body>
</html>
__EOD;
  return $html;
}

?>

