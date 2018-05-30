<?php # (c) 2010 Martin Smidek <martin@smidek.eu>
header("Content-type: text/css");
session_start();
$skin= $_SESSION['skin'] ? $_SESSION['skin'] : 'default';
# pokud je v root-adresáři aplikace složka skins se souborem colors.php
# musí v něm být obsažen příkaz global spřístupňující změněné barvy a cestu k obrázkům
global $skin, $path, $c, $b, $ab, $c_appl, $ezer_root,
  $c_menu, $b_menu, $c_main, $b_main,
  $c_group, $b_group, $s_group, $c_item, $b_item, $bd_item, $fb_item, $fc_item, $s_item, $s2_item,
  $b_brow, $b2_brow, $b3_brow, $b4_brow, $b5_brow, $b6_brow, $c_brow, $s1_brow, $s2_brow,
  $c_kuk, $c2_kuk, $b_kuk, $s_kuk, $b_doc_modul, $b_doc_menu, $b_doc_form;
# c_=color, b_=background-color, a?_=aktivní, f?_=focus, s_=speciál
# ------------------------------------------------------------------- default barvy podle Office2007
  $path_img= "../client/img";                     // cesta k default background-image
  $path= "./skins/default";                       // cesta k background-image
  $bila= '#ffffff'; $cerna= '#000000'; $seda= '#4d4d4d'; $zelena= '#2c8931'; // základní barvy
  // prvky
  $c= '#333'; $b= '#6f93c3'; $ab= $bila;
  $c_appl= $bila;
  $c_menu= 'navy'; $b_menu= $seda;
  $c_main= $cerna; $b_main= $bila;
  $c_group= $bila; $b_group= '#7389ae'; $s_group= '#5e708e';
  $c_item= '#333'; $b_item= '#cde'; $bd_item= '#ccc'; $fb_item= '#3e4043'; $fc_item= '#faec8f';
    $s_item= '#9ab'; $s2_item= '#303234';
  $b_brow= '#ccc'; $b2_brow= '#f2f8ff'; $b3_brow= '#E5E5E6'; $b4_brow= '#d1e4ff';
    $b5_brow= '#f0f0f0'; $b6_brow= '#f2f8ff'; $b7_brow= '#d1e4ff'; $b8_brow= '#d1e4ff';
    $c_brow= '#777'; $s1_brow= '#6593cf'; $s2_brow= '#d30';
  $c_kuk= 'navy'; $c2_kuk= $c_kuk; $b_kuk= '#fb6'; $s_kuk= '#FBC84F';
  $b_warn= $s_kuk; $c_warn= '#000000';
  $b_doc_modul= '#c17878'; $b_doc_menu= '#7389ae'; $b_doc_form= '#80a2cf';
  $w_right= 720;        // šířka panel.right
  $h_kuk= $_SESSION['trace_height']?$_SESSION['trace_height']:240;

//if ( file_exists("/skins/colors.php") ) {

$browser=
  preg_match('/MSIE/',$_SERVER['HTTP_USER_AGENT'])?'IE':(
  preg_match('/Opera/',$_SERVER['HTTP_USER_AGENT'])?'OP':(
  preg_match('/Firefox/',$_SERVER['HTTP_USER_AGENT'])?'FF':(
  preg_match('/Chrome/',$_SERVER['HTTP_USER_AGENT'])?'CH':(
  '?'))));

$browse_td_h= 15;
$button=     'padding:2px 5px 0 5px;';
switch ($browser) {
case 'IE':
  $button= 'padding:2px 3px 0 3px;';
  break;
case 'OP':
  $button= 'padding:2px 4px 0 4px;';
  $browse_td_h= 17;
  break;
case 'FF':
  $button= 'padding:0;';
  break;
case 'CH':
  $browse_td_h= 16;
  break;
}
echo <<<__EOD

/* endora */

.grss {
  position:absolute; left:245px; font-size:5pt; color:silver; }
.grss a {
  color:silver; text-decoration:none; }

/* styly pro drag */

#drag {
  left:0px; top:-9px; width:1px; height:1px; position:absolute; }
.dragged {
  outline:1px dotted #0a0 !important; color:#0a0; cursor:move; }
.dragging {
  outline-width:2px !important; }
.drag_changed {
  outline-color:#f00 !important; }

.drop_envelope {
  background-color:$b_work; border:2px dashed white; }
.drop_area {
  outline:2px dashed #fff; outline-radius:5px;  }
.drop_area_hover {
  background-color:$b_parm; cursor:move; }
.drop_area_run {
  background:url($path/ajax_wait.gif) no-repeat center center;
  color:transparent !important; cursor:wait; }

/* obecné barvy a hodnoty pro atributy css */

.red    { background-color:#faa !important; }
.red2   { background-color:#f00 !important; }
.green  { background-color:#afa !important; }
.green2 { background-color:#3f3 !important; }
.blue   { background-color:#abf !important; }
.yellow { background-color:#ffa !important; }
.yellow2{ background-color:#ff0 !important; }
.orange { background-color:#fa0 !important; }
.white  { background-color:#fff !important; }
.grey   { background-color:#aaa !important; }
.silver { background-color:#ddd !important; }

.red_text   { color:#f00 !important; }
.green_text { color:#0f0 !important; }
.blue_text  { color:#00f !important; }

.selected { color:$s2_brow !important; font-weight:bold; }

.none   { display:none; }

.parm {
  background-color:$b_parm; border:1px solid #f5f5f5; z-index:0;
  border-radius:5px; }

/* rámečky formulářů */

.info        {
  background-color:#f5f5f5; border:1px solid #f5f5f5; z-index:-1;
  border-radius:5px; }
.work        {
  background-color:$b_work; z-index:0;
  border-radius:5px; }
.parm        {
  background-color:$b_parm; border:1px solid #f5f5f5; z-index:0;
  border-radius:5px; }

/* layout */

/* login */

/* úpravy Clintcide a Mootools */
.errorMsg {
  background-color:transparent; max-height:500px; overflow:auto; }
.SWclearfix:after {
  display:none !important; }

/* hlavní Menu a Tabs */

/* levé menu (accordion) */

/* context menu */

/* dokumentace */

div.doc_elem {
  border:1px solid #ccc; margin:0; padding:1px; width:140px; height:16px;
  color:#ccc; font-family:Courier New, monospace; font-size:8pt; }
.todo dt {
  background-color:#eee; }
.todo dd {
  margin:7px; color:#000; }
.todo_plus {
  font-weight:bold; color:#47668f !important; border-left:5px solid #47668f; padding-left:5px; }

/* aktivity */

div.systable {
  overflow:auto; }
.systable {
  border-collapse:collapse; }
.systable th {
  font-weight:bold; background-color:#98B4E0; text-align:center; border-left:1px dotted #69f; }
.systable td {
  border-right:1px dotted #bbf; }
.systable tr {
  border-bottom:1px solid gray; }

/* panely */

.Panel {
  position:absolute; }
.PanelRight {
  position:absolute; left:210px; top:0; background-color:#fff; width:{$w_right}px; height:100%;
  overflow-x:hidden; overflow-y:auto; }
.inAccordion {
  left:210px; top:0; background-color:#fff; width:720px; /*height:2000px;*/ }
.PanelPopup {
  position:absolute; z-index:5001; }
div.PanelPopup div.body{
  font-size:11px; line-height: 13px;}
div.PanelPopup div.top_ul {
  background:url($path/clientcide/stickyWinHTML/full.png) top left no-repeat; _background:url($path/clientcide/stickyWinHTML/full.gif) top left no-repeat;
  height:26px; width:15px; float:left}
div.PanelPopup div.top_ur {
  position:relative; left:0px !important; left:-4px; height:26px;
  background:url($path/clientcide/stickyWinHTML/full.png) top right !important; _background:url($path/clientcide/stickyWinHTML/full.gif) top right !important;
  margin:0px 0px 0px 15px !important; margin-right:-4px; padding:0px}
div.PanelPopup h1.caption {
  clear: none !important; margin:0px !important; overflow: hidden; padding:0 !important;
  font-weight:bold; color:#555; font-size:14px !important; position:relative; top:8px !important;
  left:5px !important; float: left; height: 19px !important;}
div.PanelPopup div.middle, div.PanelPopup div.closeBody {
  background:url($path/clientcide/stickyWinHTML/body.png) top left repeat-y; _background:url($path/clientcide/stickyWinHTML/body.gif) top left repeat-y;
  margin:0px 20px 0px 0px !important;
  margin-bottom: -3px; position: relative; top: 0px !important; top: -3px;}
div.PanelPopup div.body {
  background:url($path/clientcide/stickyWinHTML/body.png) top right repeat-y; _background:url($path/clientcide/stickyWinHTML/body.gif) top right repeat-y;
  padding:8px 30px 8px 0px !important; margin-left:5px !important; position:relative; right:-20px !important;}
div.PanelPopup div.bottom {
  clear:both }
div.PanelPopup div.bottom_ll {
  background:url($path/clientcide/stickyWinHTML/full.png) bottom left no-repeat; _background:url($path/clientcide/stickyWinHTML/full.gif) bottom left no-repeat;
  width:15px; height:15px; float:left}
div.PanelPopup div.bottom_lr {
  background:url($path/clientcide/stickyWinHTML/full.png) bottom right; _background:url($path/clientcide/stickyWinHTML/full.gif) bottom right;
  position:relative; left:0px !important; left:-4px; margin:0px 0px 0px 15px !important; margin-right:-4px; height:15px}
div.PanelPopup div.closeButtons {
  background:url($path/clientcide/stickyWinHTML/body.png) top right repeat-y; _background:url($path/clientcide/stickyWinHTML/body.gif) top right repeat-y;
  text-align: center; padding: 0px 30px 8px 0px; margin-left:5px; position:relative; right:-20px}
/*
div.PanelPopup a.button:hover {
  background:url($path/clientcide/stickyWinHTML/big_button_over.gif) repeat-x}
div.PanelPopup a.button {
  background:url($path/clientcide/stickyWinHTML/big_button.gif) repeat-x; margin: 2px 8px 2px 8px;
  padding: 2px 12px; cursor:pointer; border: 1px solid #999 !important; text-decoration:none;
  color: #000 !important;}
*/
div.PanelPopup div.closeButton {
  width:13px; height:13px; background:url($path/clientcide/stickyWinHTML/closebtn.gif) no-repeat;
  position: absolute; right: 0px; margin:10px 15px 0px 0px !important; cursor:pointer;top:0px}
div.PanelPopup div.dragHandle {
  width:11px; height:22px; position:relative; top:5px; left:-3px; cursor: move;
  background:url($path/clientcide/stickyWinHTML/drag_corner.gif); float: left;}

/* klávesnice a změny */

.disabled {
  color:#ccc !important; }
.changed {
  outline:#f88 solid 2px !important; }
.changed_ok {
  outline:#8f8 solid 2px !important; }
.changed_focus {
  outline:#ff3333 dotted 2px !important; }
.Chat .changed {
  margin:0 2px; outline-style:solid !important; }
.fixed {
  outline:#008 solid 2px !important;}
.empty {
  color:#ccc; font-family:Courier New, monospace !important; font-size:8pt !important; }
.empty_focus {
  color:#000; }
.readonly, .readonly * {
  border:0px !important; background-color:transparent !important; }
.Form input:focus, .Form textarea:focus, .focus {
  outline:#ff0 dotted 2px !important; }
.Chat .focus {
  outline-color:#f88 !important;; }
table.focus {
  outline:#ff0 dotted 2px !important; }
input.changed:focus, textarea.changed:focus {
  outline:#ff3333 dotted 2px !important; }

/* form, elem */

/*
.Form {
  position:absolute; width:100%; height:100%; }
*/
.Label {
  position:absolute; z-index:1; }
.Label h1 {
  font-size:12pt; margin:0; padding:0 }
.Label a {
  color:$c_kuk /*!important;*/ }
.Label a:hover, .href:hover {
  background-color:$b_kuk !important; }
.href {
  text-decoration:underline; color:$c_kuk; cursor:pointer; }

div.Element {
  position:absolute; }
div.Element .Label, div.Select .Label, div.FieldDate .Label {
  white-space: pre; }

div.LabelDrop {
  position:absolute; z-index:1; border:1px dashed #fff;
  outline:3px solid $b_doc_form; background-color:$b_doc_form; }
div.LabelDropHover {
  border:3px dashed #fff; outline:1px solid $b_doc_form; }
div.LabelDrop > div {
  color:#fff; padding:0 5px; }
div.LabelDrop > div + div {
  height:100%; overflow-x:hidden; overflow-y:auto; color:#000; }
div.LabelDrop table {
  width:100%; table-layout: fixed; }
div.LabelDrop tr > td {
  overflow:hidden; text-overflow:ellipsis; }
div.LabelDrop progress {
  width:100%; }
div.LabelDrop td, div.LabelDrop a {
  color:$fc_item }
div.LabelDrop .ContextFocus {
  background-color:$b_kuk !important; color:$c2_kuk; }

.Field {
  position:absolute; height:16px !important; width:100%; border:1px solid #aaa; z-index:1; }
.FieldDate {
  position:absolute; z-index:1; }
.FieldDate img {
  margin-top:1px; }
.FieldDate input {
  border:1px solid #aaa; }
.FieldList input.FieldList {
  border:0; border-bottom:1px solid #aaa; }
.FieldList div.SelectDrop {
  border:1px solid #aaa; position:absolute; z-index:1; box-shadow:5px 5px 10px #567;
  overflow-y:auto; overflow-x:hidden; max-height:192px; }
.Edit {
  position:absolute; font-family:Arial,Helvetica,sans-serif; font-size:9pt; z-index:1;
  border:1px solid #aaa; overflow-y:scroll; _overflow:scroll; width:100%; height:100%; }
.EditHtml {  /*border:1px dotted green;*/
  position:absolute; }

/*
button {
  position:absolute; font-size:9pt; white-space:nowrap; z-index:1; padding:1px 4px; }
@-moz-document url-prefix() { button { padding:0px 4px; } }
button::-moz-focus-inner { border:0; padding:0; }
*/

.fa {
  font-family:FontAwesome,sans-serif !important; }
button i.fa-red, a i.fa-red  { color:red }
button i.fa-grey, a i.fa-grey  { color:grey }
button:disabled i.fa-red { color:grey }
input.Button {
  position:absolute; font-size:9pt; z-index:1; text-align:center; $button }
.ButtonSubmit {
  position:absolute; font-size:9pt; text-decoration:underline; z-index:1;
  $button; text-align:center; }
.Check {
  position:absolute; display:block; z-index:1; }
.Check input {
  /*vertical-align:bottom;*/ position:relative; top:2px; }
.Radio {
  position:absolute; display:block; /*border:1px solid #aaa;*/ z-index:1; }
.Case {
  position:absolute; }
.Chat {
  position:absolute; border:1px solid #aaa; z-index:1; }
.Chat_hist {
  background-color:#fff; overflow-y:scroll; overflow-x:hidden;
  border-bottom:3px double #ccc; font-size:9pt; }
.Chat textarea {
  font-size:9pt; width:100%; border:none; padding:0; margin:0; }
.Chat_1 {
  background-color:#eee; }
.Chat_2 {
  background-color:#fff; }

.Select {
  position:absolute; /*border:1px solid #ccc; width:100%;*/ z-index:2; }
.SelectClosure {
  /*background-color:#fff; margin:0; padding:1px 1px 0 0; */ }
.SelectClosure img {
  margin-top:1px; background-color:#fff; }
.SelectClosure button, .FieldDate button, .Form .button {
  width:16px; height:16px; position:absolute; right:1px; padding:0; margin-top:2px; font-size:8pt; }
@-moz-document url-prefix() {
  .SelectClosure button, .FieldDate button, .Form .button {
    width:18px; height:19px; right:-2px; margin-top:1px; }
}
.SelectClosure button .fa, .FieldDate button .fa {
  display:block; }
.Select input {
  width:100%; height:16px !important; border:1px solid #aaa; /*padding:1px 0px;*/ margin:0;
  display:block; /*background-color:#fff;*/ overflow:hidden; cursor:default; }
/*.SelectDrop {
  position:absolute; z-index:1; -moz-box-shadow:5px 5px 10px #567;
  color:#000; border:1px solid #ccc; background-color:#fff; margin:0; padding:0; }*/
ul.SelectDrop {
  position:absolute; z-index:1; box-shadow:5px 5px 10px #567; list-style-type:none;
  color:#000; border:1px solid #ccc; margin:0; padding:0;
  overflow-y:auto; overflow-x:hidden; max-height:192px; }
.SelectDrop li {
  list-style-type:none; margin:0; padding:0; border-bottom:1px dotted #ccc; overflow:hidden;
  background-color:#f2f8ff; display:block; cursor:default; width:100%; height:16px; white-space:nowrap; }
.SelectDrop li span {
  color:#696; }
.SelectDrop li.selected {
  background-color:#b2b4bf; }
.SelectDrop li.li-sel {
  color:$s2_brow !important; background-image:url($path_img/srafa.png); background-repeat:repeat-x; }
.List {
  position:absolute; z-index:1; /*outline:1px dotted #fff;*/ overflow:auto; overflow-x:hidden; }
.ListRow {
  position:absolute; z-index:1; /*outline:1px dotted #000;*/ }

/* BROWSE.SMART */

.BrowseSmart {
  position:absolute; z-index:1; }
.BrowseSmart table {
  border-spacing:0px;
  z-index:2; position:absolute; empty-cells:show; padding:0; width:0;
  margin:0; table-layout:fixed; border-collapse:separate; background-color:$b_brow;
  font-size:9pt; border:1px solid $s1_brow; overflow:hidden; }
.BrowseSmart input.BrowseFocus  {
  width:10px; padding:0; margin:0; height:10px; border:0; outline:0px !important; }
/* hlavička */
.BrowseSmart td.th {
  background:url($path/browse_header.png) repeat-x center -1px;
  color:$c_brow; font-size:9pt; font-weight:bold; vertical-align:middle; cursor:default;
  height:17px; line-height:12px; text-align:left; overflow:hidden; padding:0; text-indent:5px; }
.BrowseSmart td.ShowSort:hover {
  background:transparent url($path/browse_sort_hover.png) repeat-x scroll 0 -1px !important;  }
.BrowseSmart td.th span {
  display:block; margin-left:5px; margin-right:5px; overflow:hidden; }
.BrowseSmart td img.sort {
  position:absolute; }
td.BrowseNoClmn img.sort {
  display:none; }
.BrowseSmart td img.resize {
  float:right !important; padding:0; cursor: w-resize; z-index:200;
  margin-bottom:-10px; height:10px; width:5px; }
.BrowseSmart.disabled td {
  color:#545454; }
/* dotazy */
.BrowseSmart td.BrowseNoQry {
  padding:0; background-color:$b6_brow;
  border-left:1px solid $b_brow; border-bottom:1px solid $b_brow;  }
.BrowseSmart td.BrowseQry {
  padding:0; padding:0 !important;
  border-left:1px solid $b_brow; border-bottom:1px solid $b_brow;
  vertical-align:top; }
.BrowseSmart .BrowseQry input {
  background-color:$b8_brow; border:0; padding:0px; width:100%; height:16px; font:inherit;
  line-height:14px; margin:-1px 0; }
/* řádky */
.BrowseSmart tr {
  /*height:17px !important;*/ }
.BrowseSmart td {
  height:{$browse_td_h}px; white-space:nowrap; overflow:hidden;
  vertical-align:bottom; cursor:default; padding:0 2px; /*line-height:14px;*/
  border-left:1px solid $b_brow; border-bottom:1px solid $b_brow;  }
.BrowseSmart td.tag0 {
  background-color:$b7_brow; padding:0; }
.BrowseSmart td.tag1 {
  background-image:url($path/browse_mark2.gif); width:10px; }
.BrowseSmart td.tr-even {
  background-color:$b2_brow; }
.BrowseSmart td.tr-odd {
  background-color:$b4_brow; }
.BrowseSmart .tr-form {
  font-weight:bold; }
.BrowseSmart .tr-sel {
  color:$s2_brow !important; }
.BrowseSmart td.BrowseNoClmn {
  padding-left:0; border-left:0; width:0; }
.BrowseSmart .tr-sel td.tr-odd, .BrowseSmart .tr-sel td.tr-even {
  background-image:url($path_img/srafa.png); background-repeat:repeat-x;  }
/* input */
.BrowseSmart input.td_input {
  height:15px; position:relative; margin:-1px 2px -1px -3px; padding-left:2px;
  outline:2px dotted yellow; border:0px; font:inherit; background-color:#ffffaa}
/* reload */
.BrowseSmart td.BrowseReload {
  background:url($path/browse_reload.png) no-repeat !important; cursor:pointer !important;
  padding:0; width:8px; }
/* posuvník */
.BrowseSmart td.BrowseSet {
  background:url($path/browse_set.png) no-repeat !important; cursor:pointer !important;
  padding:0; width:16px; }
.BrowseSmart div.BrowsePosuv {
  z-index:1; width:16px; background-color:$b5_brow; }
.BrowseSmart td.BrowsePosuv {
  padding:0px !important; }
.BrowseSmart .BrowseUp, .BrowseSmart .BrowseDn, .BrowseSmart .BrowseHandle {
  height: 16px; width: 15px;
  background-color:$b5_brow; background-repeat:no-repeat; background-position:center; }
.BrowseSmart .BrowseUp       { background-image:url($path/browse_pgup0.png); }
.BrowseSmart .BrowseUp.act   { background-image:url($path/browse_pgup.png); }
.BrowseSmart .BrowseUp.act:hover { background-image:url($path/browse_pgup_act.png); }
.BrowseSmart .BrowseDn       { background-image:url($path/browse_pgdn0.png); }
.BrowseSmart .BrowseDn.act   { background-image:url($path/browse_pgdn.png); }
.BrowseSmart .BrowseDn.act:hover { background-image:url($path/browse_pgdn_act.png); }
.BrowseSmart .BrowseHandleUp   {
  background-image:url($path/browse_handle_up.png); height:6px;
  background-position: 1px top; background-repeat: no-repeat; }
.BrowseSmart .BrowseHandleMi   {
  background-image:url($path/browse_handle_mi.png); height:100%;
  background-position: 1px center; background-repeat: repeat-y; }
.BrowseSmart .BrowseHandleMi:hover {
  background-image:url($path/browse_handle_act_mi.png); }
.BrowseSmart .BrowseHandleDn   {
  background-image:url($path/browse_handle_dn.png); height:6px; bottom:0px;
  background-position: 1px bottom; background-repeat: no-repeat; }
.BrowseSmart div.BrowseHandle:hover { background-image:url($path/browse_handle_act.png); }
/* patička */
.BrowseSmart th {
  background:url($path/browse_header.png) repeat-x center center; color:$c_brow; font-size:8pt;
  height:14px; text-align:left; border:0; white-space:nowrap; overflow:hidden; cursor:default; }

/* patička */

/* seznam změn */
div.chng {
    width:100%; }
span.chng_day {
    background-color:$b_item; color:$c_item;padding:1px 5px; }
span.chng_hlp {
    padding:1px 5px; }

/* debugger */
#form {
  float:right; background-color:#ffa; width:400px; height:100%; margin:0; border:0; display:none; }
#form pre {
  overflow:auto; height:100%; margin:0; padding:2px 5px; }

/* echo, error, dbg */

/* tree */

.mooTree_node {
  font-family: Verdana, Arial, Helvetica; font-size: 10px; white-space: nowrap; }
.mooTree_text {
  padding-top: 3px; height: 15px; cursor: pointer; }
.mooTree_img {
  float: left; width: 18px; height: 18px; overflow: hidden; }
.mooTree_selected {
  background-color: #e0f0ff; font-weight: bold; margin-right: 10px; }

/* dokumentace */

#Content, #Index{ background: #fff; /*margin-left: 10px; margin-left: 210px;*/ padding:15px;
                /*font-size: 13px;*/ /*min-height: 400px;*/}
#MainTopic .CTitle, .IPageTitle
         	{ padding: 5px 10px; background: #78ba91; color: #fff; margin-bottom: 10px;
                  /*text-shadow: 2px 2px 1px #679956;*/ }
.CTitle,.STitle { white-space:nowrap; }
.CClass .CTitle, .CSection .CTitle, .CModule .CTitle
         	{ background-color:$b_doc_modul; padding: 5px 10px; background-repeat: no-repeat;
                  background-position: center left; color: #fff; /*text-shadow: 2px 2px 1px #935b5c;*/ }
.CSection .CTitle
         	{ background-color: #ddd; color: #666; /*text-shadow: none;*/}
.CGroup .CTitle, .INavigationBar
         	{ background: #ccc; padding: 3px 10px; font-size: 18px; text-transform: uppercase;
                  font-size: 15px;}
.CProperty .CTitle, .CFunction .CTitle, .CMenu .CTitle, .CForm .CTitle, #Content h1
         	{ color: #fff; background-color:$b_doc_menu; padding: 6px 10px;
                   background-repeat: no-repeat; background-position: center left;
                   /*text-shadow: 2px 2px 1px #5e708e;*/ }
.CMenu .CTitle, #Content h1
         	{ padding-left: 50px; background-image: url($path_img/doc_menu.gif);}
.CProperty .CTitle
         	{ padding-left: 50px; background-image: url($path_img/method.png);}
.CFunction .CTitle
         	{ padding-left: 60px; background-image: url($path_img/function.png);}
.CClass .CTitle
         	{ padding-left: 40px; background-image: url($path_img/class.png);}
.CModule .CTitle
         	{ padding-left: 40px; background-image: url($path_img/doc_module.gif);}
.CForm .CTitle
         	{ padding-left: 40px; background-image: url($path_img/doc_form.gif);
                  background-color:$b_doc_form ; }
#Content pre    { font: 12px "Monaco", "Courier New", Monospace; background: #f5f5f5;
                  border: 1px solid #ddd; padding: 10px 15px; color: #444; margin-left: 30px;
                  overflow: auto !important; overflow: scroll; line-height: 1.5;}
.CParagraph, .CDescriptionList
         	{ padding: 5px 5px 5px 30px;}
.CDescriptionList tr
         	{ padding: 2px 0;}
.CDLEntry 	{ vertical-align:top; padding: 0 10px 0 0; color: #666;
                  border-top:1px solid #eee;
                  font: 12px "Monaco", "Courier New", Monospace;}
.CDLDescription { border-top:1px solid #eee; }
h4.CHeading, .STitle
         	{ padding-left: 10px; border-bottom: 1px solid #eee; border-top: 1px solid #eee;
                  background: #fdfdfd; color: #54545d;}
.CProperty .CTitle, .CFunction .CTitle, .CGroup .CTitle, .CClass .CTitle, .CSection .CTitle,
.CMenu .CTitle, .CModule .CTitle, .CForm .CTitle
         	{ margin: 15px 0 10px;}

/*summary*/

.SBody   	{ margin-left: 30px;}
.STitle 	{ font-size: 14px; padding: 2px 10px; font-weight: bold;}
.STable 	{ width: 100%; border-collapse: collapse; font-size: 12px;}
.STable a:link, .STable a:visited
         	{ color: #875938;}
.STable a:hover, .STable a:active
         	{ color: #342113;}
.SBody   	{ padding: 10px; background: #fff9f0;}
.SMarked 	{ background: #ffeed6;}
.STable td 	{ border: 2px solid #fff; padding: 3px 6px; vertical-align:top;}
h4.CHeading, .STitle
         	{ margin: 10px 0 7px;}
.SMain   	{ font-size: 16px; background: #ffca7f;}
.SMain td 	{ padding: 4px 6px;}
.SProperty .SEntry, .SFunction .SEntry
         	{ text-align: right;}
.SEntry 	{ width: 170px;}
.SClass, .SSection
         	{ font-size: 14px; background: #ffd599;}
.SGroup 	{ font-size: 13px; background: #ffdfb2;}

/*help*/

div.Form>div.ContextHelp {
                  background:url($path/clientcide/stickyWinHTML/body.png) scroll center bottom repeat-y;
                  padding:10px 0 10px 10px; color:black; overflow:auto }
.ContextHelp img{ padding:5px; overflow: auto; }
.ContextHelp hr { clear:both; color:transparent }
.ContextHelp div.foot {
                  position:absolute; bottom:-3px; right:20px; color:grey; }
div.Help   	{ position: absolute; z-index:2; width:300px; height:150px; right:30px; top:15px;
                  background-color:silver; box-shadow:5px 5px 10px #567; }
.Help input     { float:right; font-size: 8pt; margin-top:3px; }
.Help span      { position:absolute; background-color: silver;
                  border: 3px solid silver; height: 17px; padding-top: 3px; }
.Help textarea  { position:absolute; width:294px; height:120px; top:23px;
                  font-family:Arial,Helvetica,sans-serif; font-size:9pt }
div.HelpList    { float:right; background-color:$b2_brow; padding:10px;
                  max-width: 230px; word-wrap:break-word; }
.HelpList ul    { margin:0; padding:0 10px; }
      
__EOD
;

?>

