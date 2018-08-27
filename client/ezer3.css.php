<?php # styly pro jádro Ezer verze 3  (c) 2010 Martin Smidek <martin@smidek.eu>
header("Content-type: text/css");
if ( !isset($_SESSION) ) session_start();
$ezer_root= $_GET['root'];
$skin= $_SESSION[$ezer_root]['skin'] ? $_SESSION[$ezer_root]['skin'] : 'default';
# pokud je v root-adresáři aplikace složka skins se souborem colors.php
# musí v něm být obsažen příkaz global spřístupňující změněné barvy a cestu k obrázkům
global $skin, $path, $c, $b, $ab, $c_appl, $ezer_root,
  $c_menu, $b_menu, $c_main, $b_main,
  $c_group, $b_group, $s_group, $c_item, $b_item, $bd_item, $fb_item, $fc_item, $s_item, $s2_item,
  $b_brow, $b2_brow, $b3_brow, $b4_brow, $b5_brow, $b6_brow, $c_brow, $s1_brow, $s2_brow,
  $c_kuk, $c2_kuk, $b_kuk, $s_kuk, $b_doc_modul, $b_doc_menu, $b_doc_form;
# c_=color, b_=background-color, a?_=aktivní, f?_=focus, s_=speciál
$abs_root=  $_SESSION[$ezer_root]['abs_root'];
require_once("$abs_root/skins/colors.php");
echo <<<__EOD

/* -------------------------------------------------------------------------------------==> popup */

.mask3 { position:absolute; left:0; top:0; width:100%; height:100%; z-index:2000; display:none; }

#wait_mask, div#popup_mask3, #top_mask3 { position:absolute; left:0; top:0; width:100%; height:100%;
  z-index:1000; display:none; background-color: rgba(51,51,51,0.2); }
#wait { position: absolute; left: 0; right: 0; top: 0; bottom: 0; margin:auto; width:64px;
  height:64px; z-index:997; background-color:transparent; background-image:url(img/spinner.gif); }
#top_mask3 { z-index:2000;
}

div#popup3 { position: absolute; left: 0; right: 0; bottom: 40%; margin:auto; width:300px;
  display:none; z-index:2001; background-color: $b_item; color: $c_item; border-radius: 7px;
  padding: 30px 5px 5px 5px; border: 1px solid $b_group; box-shadow: black 4px 6px 20px; }
#popup3 div.pop_head { position: absolute; top: 0; left: 0; width: 100%; text-align: center;
  line-height: 24px; height: 24px; background: $b_group; border-radius: 5px 5px 0 0;
  color: $c_group; font-weight: bold; }
#popup3 div.pop_body { padding: 5px; }
#popup3 div.pop_tail { text-align:center; padding: 0px 10px 0px 5px; }
#popup3 div.pop_tail input { width:100%; display:block; margin-bottom: 7px; }
#popup3 div.pop_tail button { position: inherit; margin:0 5px 5px 5px;
}

/* --------------------------------------------------------------------------------------==> skin */

/* rámečky formulářů */

.info { background-color:#f5f5f5; border:1px solid #f5f5f5; z-index:-1; border-radius:5px; }
.work { background-color:$b_work; z-index:0; border-radius:5px; }
.parm { background-color:$b_parm; border:1px solid #f5f5f5; z-index:0; border-radius:5px; }
.karta { background:$b_group url($path/doc_menu.gif) no-repeat left center; color:$c_group; overflow:hidden;
  font-size:14px; font-weight:bold; margin:2px 0; padding:5px 50px; clear:both; white-space:nowrap;
}

/* --------------------------------------------------------------------------- pomocné konstrukce */

#drag { left:0px; top:-9px; width:1px; height:1px; position:absolute; }
.dragged { outline:1px dotted #0a0 !important; color:#0a0; cursor:move; opacity:0.7; }
.dragging { outline-width:2px !important; }
.drag_changed { outline-color:#f00 !important;
}

div.tip { position:absolute; display:none; padding:2px 5px; z-index:999999; cursor:default;
  background:lightyellow; outline:1px solid silver; box-shadow:5px 5px 10px #567; }
.helped { outline:2px dotted grey; cursor:default;
}

/* -------------------------------------------------------------------------------------==> login */

#login  { display:none; margin: 0 auto; width:520px; padding-top:33px; position:relative; z-index:1; }
#login_1  { float:left; }
#login_1.login_chngs, #login_2.login_chngs { height:180px; }
#login_2  { float:right; }
#login_1, #login_2  { width:250px; border:1px solid $b_group; height:220px; background-color:$b_item; }
#login_1 div.login_a { padding:15px; }
#login_2 div.login_a { padding:5px; height:180px; overflow-y:auto; }
#login_2 div.login_a_msg { padding:5px; }
#login_2 div.login_notify { padding:5px; background-color:$b_kuk; color:$c2_kuk; }
#login form { margin:0; padding:0;  }
#login span { margin:0; padding:0;  }
#login input { margin:2px 0 10px; padding:0 2px; width:auto; overflow:visible;}
#login h1 { background-color:$b_group; color:#FFFFFF; font-size:9pt; margin:0; padding:2px 5px;}
#login_on { text-decoration:none; position:absolute; }
#login_no { position:absolute; left:110px; }
#watch_key   { padding:5px !important; }
#login_chngs { margin-top:193px; border:1px solid $b_group; height:180px; background-color:$b_item; }
#login_chngs > div { padding:10px 5px; overflow-x:auto; height:141px; }
#login_chngs span { padding:1px 5px; }
#login_chngs span.chng_day { color:$b_group; font-weight:bold; }
#login_chngs div.chng { margin:-5px 0px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
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

/* ------------------------------------------------------------------------------------==> layout */

html,body {  background: $b url($path/body_bg.png) repeat-x; height: 100%; }

body { font-family: Arial,Helvetica,sans-serif; padding: 0; margin: 0;
  font-size: 9pt; position: static; overflow: hidden; }
#body { display: flex; flex-direction: column; justify-content: space-between; height: 100%; }
#horni { flex-basis: 67px; flex-shrink: 0; flex-grow: 0; width:100%; overflow:hidden; }
#ajax_bar3 { background-color:orange; left:0; position:absolute; z-index:11002;
  top:0; height:4px; width:0px; box-shadow: 2px 2px 7px #a60; transition:width 400ms linear; }
#work { flex-grow: 1; display: inline-flex; overflow:hidden; }
#status_bar { flex-basis: 14px; flex-shrink: 0; flex-grow: 0; width:100%; z-index:999; }
#dolni {flex-shrink: 0; flex-grow: 0; width:100%; }
#paticka { position:absolute; bottom:15px; width:100%;
}

/* --------------------------------------------------------------------------------------- header */

.MainBar a { text-decoration:none; color:navy; white-space:nowrap; cursor:pointer; }
.MainBar a:hover { color:;  }
.MainBar .Active a:hover { color: !important; }
.MainBar li { border:medium none; float:left; list-style-type:none;
}

.StatusIcon { position:absolute; z-index:2010; cursor:pointer; color:#FFAD39; font-size:20pt;
  font-weight:bold; }
#appl { color:#ffffff; font-size:20pt; font-weight:bold; position:absolute; right:6px; top:1px; }

/* -------------------------------------------------------------------------------------- paticka */

#paticka { z-index: 999; font-size:8pt; overflow:auto; }
#paticka td { border:1px solid #aaa;font:x-small Arial;color:#777;padding:0 3px; }
#paticka td.title { color:#33a;}
#paticka td.label { color:#a33;
}

/* ----------------------------------------------------------------------------------- status_bar */

#status_bar { background:#eee url($path/foot_bg.png) repeat-x; color:$c2_kuk;
  padding:1px 0 0; font-size:8pt; }
#status_bar span { padding:1px 4px; margin:0 2px; }
#status, #status_left, #status_right { cursor:default; }
#status_left { position:absolute; left:0; }
#status_right { position:absolute; right:0; }
#status_center { position:absolute; bottom:15px; background-color:orange;}
#status_right span.measures { position:absolute; top:-14px; right:4px; z-index:999;
  background-color:#fb6; color:navy;
}

/* ---------------------------------------------------------------------------------------- dolni */

#dolni a { text-decoration:none; color:navy; }
#dolni pre { font-family:Consolas; }
#dolni .line { color:grey;  }
#dolni a:hover { text-decoration:underline; }
#dolni table { border-collapse:collapse; }

#trace { height:100%; width:100%; }

#kuk, #kuk_err, #error, #dbg { position:relative; z-index:2; height:100%; margin:0;
  background-color:#eee; overflow-y:auto; font-size:8pt; line-height:13px; }
#kuk_err, #error { display:none; }
#dbg { float:right; background-color:#ffa; width:400px; border:0; padding:0; resize:none; }
.dbg_context { outline:2px dotted #ff00ff; }
.dbg { margin:0; overflow-y:auto; font-size:8pt; line-height:13px; }
#warning { background-color:#FBC84F; color:#000000; opacity:.8; font-weight:bold;
  padding:15px; cursor:default; display:none; }
#kuk_err, #error { background-color:#FBC84F; height:75px; }
#kuk table, .dbg table { border-collapse:collapse; margin:1px 0;}
#kuk td, .dbg td { border:1px solid #aaa; font:x-small Arial;color:#777;padding:1px 3px; line-height:11px; }
#kuk td.title, .dbg td.title { color:#000; background-color:#aaa; }
#kuk td.label, .dbg td.label { color:#a33;}
.dbg table.dbg_array { background-color:#ddeeff; }
.dbg table.dbg_object { background-color:#ffffaa; }
.ae_switch_on { background-color:#fb6; color:navy;  }
.ae_switch_sel { background-color:#fb6; color:navy;  }
#kuk div.trace { margin-left:70px; color:#000; padding:0; white-space:pre-wrap; }
#kuk div.trace_hide { margin-left:70px; color:#777; height:13px; overflow:hidden; white-space:pre-line; }
#kuk span.trace_on { background:url($path/tree.png) no-repeat 55px -104px; color:#777;
  width:69px; margin:0; padding:0; float:left; left:0; }
#kuk span.trace_click { background-color:#ddd;
}

/* ------------------------------------------------------------------------------------- debugger */
#form {
  float:right; background-color:#ffa; width:400px; height:100%; margin:0; border:0; display:none; }
#form pre {
  overflow:auto; height:100%; margin:0; padding:2px 5px; }

/* ------------------------------------------------------------------------==> klávesnice a změny */

::placeholder {
  opacity:0.5; font-size:8pt; }
.disabled3 {
  pointer-events: none; opacity:0.6; }
.changed {
  outline:#f88 solid 2px !important; }
.changed_ok {
  outline:#8f8 solid 2px !important; }
.changed_focus {
  outline:#ff3333 dotted 2px !important; }
.Chat3 .changed {
  margin:0 2px; outline-style:solid !important; }
.fixed {
  outline:#008 solid 2px !important;}
.readonly, .readonly * {
  border:0px !important; background-color:transparent !important; }
.Form input:focus, .Form textarea:focus, .focus {
  outline:#ff0 dotted 2px !important; }
.Chat3 .focus {
  outline-color:#f88 !important;; }
table.focus {
  outline:#ff0 dotted 2px !important; }
input.changed:focus, textarea.changed:focus {
  outline:#ff3333 dotted 2px !important; }

/* ----------------------------------------------------------------------------------==> MenuMain */

ul.MainMenu { position:relative; top:11px; z-index:1; margin:0 0 0 80px; padding:0; overflow:hidden;
  height: 24px; }
.MainMenu a { padding:3px 10px 1px 10px; display:block; color:$c_menu;margin:0 0 0 6px; height:20px; }
.MainMenu li { float:left; list-style-type:none; }
.MainMenu li.Active { padding:0 6px 0 0; min-height:25px;
  background:
    url($path/menu_on1.png) left top no-repeat,
    url($path/menu_on3.png) right top no-repeat,
    url($path/menu_on2.png) repeat-x; }
.MainMenu .Pasive a:hover { background-image:url($path/menu_of2_hover.png); color:#000000; }
.MainMenu .Active a:hover {
  background:transparent url($path/menu_of3_hover.png) repeat-x scroll 0 0 !important; }
.MainMenu .Pasive a { color:$b_menu;
}

/* --------------------------------------------------------------------------------------==> Tabs */

div.MainTabs { position: absolute; top:34px; width:100%; height:32px;
  background:transparent url($path/submenu_bg.png) repeat-x scroll center top; }
div.MainTabs ul { position:relative; top:2px; margin:0; height: 30px; padding:0; overflow:hidden; }
.MainTabs li { margin:0; padding:0; height:100%; display:block; float:left; list-style-type:none; }
.MainTabs a { display:block; padding:7px; margin:0; }
.MainTabs a { color:$c_menu !important; }
.MainTabs .Active a { background:transparent url($path/menu_on_hover.png) repeat-x scroll 0 0 !important;
  color:navy; }
.MainTabs a:hover { background:transparent url($path/menu_off_hover.png) repeat-x scroll 0 0 !important; }
div.MainTabs span#_help  { position:absolute; right:0; top:2px; z-index:1; }
div.MainTabs #_help a { color:#ef7f13 !important; font-weight:bold !important;
}

/* -------------------------------------------------------------------------------------==> Panel */

section { display:flex; flex-direction: row; justify-content: space-between;
  height: inherit; width: 100%; }
div.Panel3 { position:relative; width:100%; height:100%; overflow-y: auto; }
div.PanelRight3 { position:relative; width:100%; height:100%; background-color: #fff;
  overflow-x: hidden; overflow-y: auto;
}

/* --------------------------------------------------------------------------------==> PanelPopup */

div.Popup3 {
  position:absolute; z-index:1001; left:50%; top:50%; display:none;
  background-color: $b_item; color: $c_item;
  border-radius: 7px; padding: 30px 19px 12px 15px; border: 1px solid $b_group;
  box-shadow: black 4px 6px 20px; }
div.Popup3 div.pop_head { position: absolute; top: 0; left: 0; width: 100%;
  line-height: 22px; height: 22px; background: $b_group; border-radius: 5px 5px 0
  0; color: $c_group; font-weight: bold; font-size:10pt; text-indent: 20px; }
div.Popup3 div.pop_head button { float:right; }
div.Popup3 div.pop_close { position: absolute; right: 0; top: 0; cursor: pointer;
  margin: 5px; width: 13px; height: 13px; background: url(img/closebtn.gif) no-repeat; }
div.Popup3 div.pop_body { position: absolute; overflow: auto;
}

/* --------------------------------------------------------------------------------------==> Help */

div.Form3>div.ContextHelp { background-color:$b_item; color:black;
  padding:10px 0 10px 10px; overflow:auto; }
.ContextHelp img { padding:5px; overflow: auto; }
.ContextHelp hr { clear:both; color:transparent }
.ContextHelp div.foot { position:absolute; bottom:-3px; right:20px; color:grey; }
div.Help { position: absolute; z-index:2; width:300px; height:150px; right:30px; top:15px;
  background-color:silver; box-shadow:5px 5px 10px #567; }
.Help input { float:right; font-size: 8pt; margin-top:3px; }
.Help span { position:absolute; background-color: silver;
  border: 3px solid silver; height: 17px; padding-top: 3px; }
.Help textarea { position:absolute; width:294px; height:120px; top:23px;
  font-family:Arial,Helvetica,sans-serif; font-size:9pt }
div.HelpList { float:right; background-color:#f2f8ff; padding:10px;
  max-width: 230px; word-wrap:break-word; }
.HelpList ul    { margin:0; padding:0 10px; }
#cke_editable { margin-top:-20px }

/* ----------------------------------------------------------------------------------==> MenuLeft */

div.MenuLeft3 { flex-basis: 210px; flex-shrink: 0; flex-grow: 0; font-weight: bold;
  overflow-y: auto; overflow-x: hidden; transition: flex-basis 400ms linear; }
div.MenuLeftFolded3 { flex-basis: 30px; transition: flex-basis 400ms linear; }
div.MenuLeft3 > i { float: left; color: white; font-size: 15px; margin: 4px; }
div.MenuGroup3 { font-size: 11px; padding: 0 0 5px; }
div.MenuGroup3 > a { background-color:$b_group; color:$c_group; display: block;
  padding: 5px 10px 6px 10px; border-right: 4px solid #5e708e; white-space: nowrap;
  text-transform: uppercase; text-decoration: none; text-align: right; cursor: pointer; }
div.MenuGroup3 ul { margin: 7px 0 0 0; padding: 0; }
div.MenuGroup3 li { background-color:$b_item; color:$c_item; border-right: 4px solid $s_item;
    padding: 4px 5px 6px 33px; margin: 2px 0; height: 9px; cursor: pointer; white-space: nowrap; }
div.MenuGroup3 li i:first-of-type { font-size: 14px; margin-left: -28px; width: 25px; }
div.MenuGroup3 li:hover { background-color:$fb_item; color:$fc_item; border-right: 4px solid $s2_item; }
div.MenuGroup3 li.selected3 { background-color: #ffffff; border-right: 4px solid #ffffff; }
div.MenuGroup3 li.selected3:hover { background-color:$ab; color:$c !important; }
div.MenuGroup3 li.disabled3:hover { background-color:#777;
}

/* -------------------------------------------------------------------------------==> MenuContext */

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

/* --------------------------------------------------------------------------------------==> Form */

.Form3 {
  position:absolute; width:100%; height:100%;
}

/* -------------------------------------------------------------------------------------==> Label */

.Label3 {
  position:absolute; z-index:1; }
/*.Label3 h1 {
  font-size:12pt; margin:0; padding:0 }*/
.Label3 a {
  color:$c_kuk }
.Label3 a:hover {
  background-color:$b_kuk
}

/* ---------------------------------------------------------------------------------==> LabelDrop */

div.LabelDrop3 {
  position:absolute; z-index:1; border:1px dashed #fff;
  outline:3px solid $b_doc_form; background-color:$b_doc_form; }
div.LabelDropHover3 {
  border:3px dashed #fff; outline:1px solid $b_doc_form; }
div.LabelDrop3 > div {
  color:#fff; padding:0 5px; }
div.LabelDrop3 > div + div {
  height:100%; overflow-x:hidden; overflow-y:auto; color:#000; }
div.LabelDrop3 table {
  width:100%; table-layout: fixed; }
div.LabelDrop3 tr > td {
  overflow:hidden; text-overflow:ellipsis; }
div.LabelDrop3 progress {
  width:100%; }
div.LabelDrop3 td, div.LabelDrop3 a {
  color:$fc_item }
div.LabelDrop3 .ContextFocus3 {
  background-color:$b_kuk !important; color:$c2_kuk; }

/* ------------------------------------------------------------------------------------==> Button */

.Button3 { position:absolute; font-size:9pt; white-space:nowrap; z-index:1; padding:1px 4px; }
.fa {
  font-family:FontAwesome,sans-serif !important; }
.Button3 i.fa-red, a i.fa-red  { color:red }
button.Button3:disabled i.fa-red { color:grey }
    
/* korekce Mozilla */
@-moz-document url-prefix() {
button { padding:0px 4px !important; }
}

/* -------------------------------------------------------------------------------------==> Field */

.Field3 {
  position:absolute; width:100%; border:1px solid #aaa; z-index:1; height: 16px;
}
div.Element3 {
  position:absolute; }
div.Element3 .Label3, div.Select3 .Label3, div.FieldDate3 .Label3 {
  white-space: pre;
}
*::placeholder { font-size:8pt; font-family:Courier New,monospace; }

/* ---------------------------------------------------------------------------------==> FieldDate */

.FieldDate3 { position:absolute; z-index:1; }
.FieldDate3 input { border:1px solid #aaa; }
.FieldDate3 button.fa { width:16px; height:16px; position:absolute; right:1px;
  padding:0; margin-top:2px; font-size:8pt; cursor:pointer; }
.FieldDate3 button .fa { display:block; }

/* ---------------------------------------------------------------------------------==> FieldList */

.FieldList3 input.FieldList3 {
  border:0; border-bottom:1px solid #aaa; }
.FieldList3 div.SelectDrop3 {
  border:1px solid #aaa; position:absolute; z-index:1; box-shadow:5px 5px 10px #567;
  overflow-y:auto; overflow-x:hidden; max-height:192px; }

/* --------------------------------------------------------------------------------------==> Edit */

.Edit3 { position:absolute; font-family:Arial,Helvetica,sans-serif; font-size:9pt; z-index:1;
  border:1px solid #aaa; overflow-y:scroll; width:100%; height:100%; resize:none; }
.EditHtml3 { position:absolute;
}

/* -------------------------------------------------------------------------------------==> Check */

.Check3 { position:absolute; display:block; z-index:1; }
.Check3 input { position:relative; top:2px;
}

/* -------------------------------------------------------------------------------==> Radio, Case */

.Radio3 { position:absolute; display:block; z-index:1; }
.Case3 { position:absolute;
}

/* --------------------------------------------------------------------------------------==> Chat */

div.Chat3 { position:absolute; border:1px solid #aaa; z-index:1; }
div.Chat_hist3 { background-color:#fff; overflow-y:scroll; overflow-x:hidden;
  border-bottom:3px double #ccc; font-size:9pt; }
div.Chat3 textarea { font-size:9pt; width:100%; border:none; padding:0; margin:0; }
.Chat_1 {
  background-color:#eee; }
.Chat_2 {
  background-color:#fff; }

/* -----------------------------------------------------------------------------==> List, ListRow */

.List3 {
  position:absolute; z-index:1; overflow:auto; overflow-x:hidden; }
.ListRow3 {
  position:absolute; z-index:1; }

/* ------------------------------------------------------------------------------------==> Select */

.Select3 {
  position:absolute; /*border:1px solid #ccc; width:100%;*/ z-index:2; }
.Select3 button {
  width:16px; height:16px; position:absolute; right:1px; padding:0; margin-top:2px; font-size:8pt; }
.Select3 button .fa {
  display:block; }
.Select3 input {
  width:100%; height:16px !important; border:1px solid #aaa; margin:0;
  display:block; overflow:hidden; cursor:default; }
.Select3 ul {
  position:absolute; z-index:1; box-shadow:5px 5px 10px #567; list-style-type:none;
  color:#000; border:1px solid #ccc; margin:0; padding:0;
  overflow-y:auto; overflow-x:hidden; max-height:153px; }
.Select3 li {
  list-style-type:none; margin:0; padding:0; border-bottom:1px dotted #ccc; overflow:hidden;
  background-color:#f2f8ff; display:block; cursor:default; width:100%; height:16px; white-space:nowrap; }
.Select3 li span {
  color:#696; }
.Select3 li.selected {
  background-color:#b2b4bf; }
.Select3 li.li-sel {
  color:$s2_brow !important; background-image:url($path_img/srafa.png); background-repeat:repeat-x;
}

/* ------------------------------------------------------------------------------------==> Browse */

div.BrowseSmart div.BrowsePosuv3 {
  width:15px; border:none; border-top: 0px solid #f0f0f0; background: #f0f0f0; }
div.BrowseSmart div.BrowsePosuv3 span.BrowseHandle3 {
  width:14px; padding:0; margin:0; border:none; left:1px;
  background:
    url($path/browse_handle_dn.png) bottom no-repeat,
    url($path/browse_handle_up.png) top no-repeat,
    url($path/browse_handle_mi.png) repeat;
 }
div.BrowseSmart div.BrowsePosuv3 span.BrowseHandle3:hover {
  background:
    url($path/browse_handle_dn.png) bottom no-repeat,
    url($path/browse_handle_up.png) top no-repeat,
    url($path/browse_handle_act_mi.png) repeat;
}
div.BrowseSmart td.BrowsePosuv3 { padding:0; }

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
.BrowseSmart tr { height:17px }
.BrowseSmart td {
  white-space:nowrap; overflow:hidden;
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

/* ==> ... korekce Mozilla */
@-moz-document url-prefix() {
.FieldDate3 button.fa, .Select3 button.fa, .FieldList3 button.fa   {
  width:18px; height:19px; right:-2px; margin-top: 1px; padding-left:1px !important; }
}

/* ------------------------------------------------------------------------------------==> Area */

.mooTree_node {
  font-family: Verdana, Arial, Helvetica; font-size: 10px; white-space: nowrap; }
.mooTree_text {
  padding-top: 3px; height: 15px; cursor: pointer; }
.mooTree_img {
  float: left; width: 18px; height: 18px; overflow: hidden; }
.mooTree_selected {
  background-color: #e0f0ff; font-weight: bold; margin-right: 10px; }


__EOD;
?>
