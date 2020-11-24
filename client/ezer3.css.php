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

/* ----------------------------------------------------------------------------------==> chyby PHP */

 /* -------------------------------------------------------------------------------------==> popup */

div#popup3 { background-color: $b_item; color: $c_item; 
  border: 1px solid $b_group; }
#popup3 div.pop_head { 
  background: $b_group; border-radius: 5px 5px 0 0;
  color: $c_group; }

/* --------------------------------------------------------------------------------------==> skin */

/* rámečky formulářů */

.work { background-color:$b_work; }
.parm { background-color:$b_parm; }
.karta { background:$b_group url($path/doc_menu.gif) no-repeat left center; color:$c_group; 
}

.systable th {
  background-color:$b_item; }

/* --------------------------------------------------------------------------- pomocné konstrukce */

/* -------------------------------------------------------------------------------------==> login */

#login_1, #login_2  { border:1px solid $b_group; background-color:$b_item; }
#login_2 div.login_notify { background-color:$b_kuk; color:$c2_kuk; }
#login h1 { background-color:$b_group; }
#login_chngs { margin-top:193px; border:1px solid $b_group; background-color:$b_item; }
#login_chngs span.chng_day { color:$b_group; }
#doc_chngs span.chng_day { color:$b_group; }

/* styly pro drag */

.drop_envelope {
  background-color:$b_work; }
  background-color:$b_parm; }
.drop_area_run {
  background:url($path/ajax_wait.gif) no-repeat center center;
  }

/* ------------------------------------------------------------------------------------==> layout */

html,body {  background: $b url($path/body_bg.png) repeat-x; }

/* --------------------------------------------------------------------------------------- header */

/* -------------------------------------------------------------------------------------- paticka */

/* ----------------------------------------------------------------------------------- status_bar */

#status_bar { background:#eee url($path/foot_bg.png) repeat-x; color:$c2_kuk;
  }

/* ---------------------------------------------------------------------------------------- dolni */

#kuk span.trace_on { background:url($path/tree.png) no-repeat 55px -104px; color:#777;
  }
#kuk span.trace_click { background-color:#ddd;
}

/* ------------------------------------------------------------------------------------- debugger */

/* ------------------------------------------------------------------------==> klávesnice a změny */

/* ----------------------------------------------------------------------------------==> MenuMain */

.MainMenu .Active a { color:$c_menu; }
.MainMenu li.Active { 
  background:
    url($path/menu_on1.png) left top no-repeat,
    url($path/menu_on3.png) right top no-repeat,
    url($path/menu_on2.png) repeat-x; }
.MainMenu .Pasive a:hover { background-image:url($path/menu_of2_hover.png); }
.MainMenu .Active a:hover {
  background:transparent url($path/menu_of3_hover.png) repeat-x scroll 0 0 !important; }
.MainMenu .Pasive a { color:$b_menu;
}

/* --------------------------------------------------------------------------------------==> Tabs */

div.MainTabs { 
  background:transparent url($path/submenu_bg.png) repeat-x scroll center top; }
.MainTabs a { color:$c_menu !important; }
.MainTabs .Active a { background:transparent url($path/menu_on_hover.png) repeat-x scroll 0 0 !important;
  }
.MainTabs a:hover { background:transparent url($path/menu_off_hover.png) repeat-x scroll 0 0 !important; }

/* -------------------------------------------------------------------------------------==> Panel */

/* --------------------------------------------------------------------------------==> PanelPopup */

div.Popup3 {
  background-color: $b_item; color: $c_item;
  border: 1px solid $b_group; }
div.Popup3 div.pop_head { 
  background: $b_group; 
  color: $c_group; }

/* --------------------------------------------------------------------------------------==> Help */

div.Form3>div.ContextHelp { background-color:$b_item; 
  }

/* ----------------------------------------------------------------------------------==> MenuLeft */

div.MenuGroup3 > a { background-color:$b_group; color:$c_group; 
  }
div.MenuGroup3 li { background-color:$b_item; color:$c_item; border-right: 4px solid $s_item;
 }
div.MenuGroup3 li:hover { background-color:$fb_item; color:$fc_item; border-right: 4px solid $s2_item; }
div.MenuGroup3 li.selected3:hover { background-color:$ab; color:$c !important; }

/* -------------------------------------------------------------------------------==> MenuContext */

/* --------------------------------------------------------------------------------------==> Form */

/* -------------------------------------------------------------------------------------==> Label */

.Label3 a {
  color:$c_kuk }
.Label3 a:hover {
  background-color:$b_kuk
}

/* ---------------------------------------------------------------------------------==> LabelDrop */

div.LabelDrop3 {
  outline:3px solid $b_doc_form; background-color:$b_doc_form; }
div.LabelDropHover3 {
  outline:1px solid $b_doc_form; }
div.LabelDrop3 td, div.LabelDrop3 a {
  color:$fc_item }
div.LabelDrop3 .ContextFocus3 {
  background-color:$b_kuk !important; color:$c2_kuk; }

/* ------------------------------------------------------------------------------------==> Button */

/* -------------------------------------------------------------------------------------==> Field */

/* ---------------------------------------------------------------------------------==> FieldDate */

/* ---------------------------------------------------------------------------------==> FieldList */

/* --------------------------------------------------------------------------------------==> Edit */

/* -------------------------------------------------------------------------------------==> Check */

/* -------------------------------------------------------------------------------==> Radio, Case */

/* --------------------------------------------------------------------------------------==> Chat */

/* -----------------------------------------------------------------------------==> List, ListRow */

/* ------------------------------------------------------------------------------------==> Select */

.Select3 li.li-sel {
  color:$s2_brow !important; background-image:url($path_img/srafa.png); background-repeat:repeat-x;
}

/* ------------------------------------------------------------------------------------==> Browse */

div.BrowseSmart div.BrowsePosuv3 span.BrowseHandle3 {
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

.BrowseSmart table {
  background-color:$b_brow;
  border:1px solid $s1_brow; }
/* hlavička */
.BrowseSmart td.th {
  background:url($path/browse_header.png) repeat-x center -1px;
  color:$c_brow;  }
.BrowseSmart td.ShowSort:hover {
  background:transparent url($path/browse_sort_hover.png) repeat-x scroll 0 -1px !important;  }
/* dotazy */
.BrowseSmart td.BrowseNoQry {
  background-color:$b6_brow;
  border-left:1px solid $b_brow; border-bottom:1px solid $b_brow;  }
.BrowseSmart td.BrowseQry {
  border-left:1px solid $b_brow; border-bottom:1px solid $b_brow;
  }
.BrowseSmart .BrowseQry input {
  background-color:$b8_brow;  }
/* řádky */
.BrowseSmart td {
  border-left:1px solid $b_brow; border-bottom:1px solid $b_brow;  }
.BrowseSmart td.tag0 {
  background-color:$b7_brow; }
.BrowseSmart td.tag1 {
  background-image:url($path/browse_mark2.gif); width:10px; }
.BrowseSmart td.tr-even {
  background-color:$b2_brow; }
.BrowseSmart td.tr-odd {
  background-color:$b4_brow; }
.BrowseSmart .tr-sel {
  color:$s2_brow !important; }
.BrowseSmart .tr-sel td.tr-odd, .BrowseSmart .tr-sel td.tr-even {
  background-image:url($path_img/srafa.png); background-repeat:repeat-x;  }
/* input */
/* reload */
.BrowseSmart td.BrowseReload {
  background:url($path/browse_reload.png) no-repeat !important; cursor:pointer !important;
  }
/* posuvník */
.BrowseSmart td.BrowseSet {
  background:url($path/browse_set.png) no-repeat !important; cursor:pointer !important;
  }
.BrowseSmart div.BrowsePosuv {
  background-color:$b5_brow; }
.BrowseSmart .BrowseUp, .BrowseSmart .BrowseDn, .BrowseSmart .BrowseHandle {
  background-color:$b5_brow; background-repeat:no-repeat; background-position:center; }
.BrowseSmart .BrowseUp       { background-image:url($path/browse_pgup0.png); }
.BrowseSmart .BrowseUp.act   { background-image:url($path/browse_pgup.png); }
.BrowseSmart .BrowseUp.act:hover { background-image:url($path/browse_pgup_act.png); }
.BrowseSmart .BrowseDn       { background-image:url($path/browse_pgdn0.png); }
.BrowseSmart .BrowseDn.act   { background-image:url($path/browse_pgdn.png); }
.BrowseSmart .BrowseDn.act:hover { background-image:url($path/browse_pgdn_act.png); }
.BrowseSmart .BrowseHandleUp   {
  background-image:url($path/browse_handle_up.png); 
  background-position: 1px top; background-repeat: no-repeat; }
.BrowseSmart .BrowseHandleMi   {
  background-image:url($path/browse_handle_mi.png); 
  background-position: 1px center; background-repeat: repeat-y; }
.BrowseSmart .BrowseHandleMi:hover {
  background-image:url($path/browse_handle_act_mi.png); }
.BrowseSmart .BrowseHandleDn   {
  background-image:url($path/browse_handle_dn.png); 
  background-position: 1px bottom; background-repeat: no-repeat; }
.BrowseSmart div.BrowseHandle:hover { background-image:url($path/browse_handle_act.png); }
/* patička */
.BrowseSmart th {
  background:url($path/browse_header.png) repeat-x center center; color:$c_brow; font-size:8pt;
  }

/* ------------------------------------------------------------------------------------==> Area */

__EOD;
?>
