<?php # (c) 2007-2009 Martin Smidek <martin@smidek.eu>
# ================================================================================================== DEFINICE
function compiler_init () {
global $wiki, $ezer_version;
global $blocs, $specs, $attribs, $uni_attribs, $blocs_help, $attribs_type, $tab_symb_obj;
global $tab_id_symb, $cmd_ajax, $cmd_gen, $cmd_lib, $gen_prefix, $use_list, $js1, $js2, $js3, $js3_ic;
global $id_anonymous, $first_panel, $attribs1, $attribs2, $keywords, $dels, $tok2lex;
global $pragma_attrs;
global $blocs2, $blocs3,$block_get, $ezer_path_code, $ezer_path_root, $names;
if ( file_exists("$ezer_path_code/comp2tab.php") )
  require_once("$ezer_path_code/comp2tab.php");
else
  require_once("$ezer_path_root/ezer$ezer_version/code$ezer_version/comp2tab.php");
// doplnění interních jmen
$names['apply']= (object)array('op'=>'ff');
# možné události
#   onblur  oncancel  onclick  onfocus     onchange  onchanged  onchoice
#   onload  onready   onbusy   onrowclick  onsave    onstart    onsubmit
# parametrizace chování
$wiki= 0;                                           // 0 => překlad, 1 => dokumentace
$blocs3 = array (                                    // universálně zanořitelné bloky
  'table','report','map','group','form','area','panel.popup','panel.free','menu.context'
);
$blocs2 = array (                                    // překládají se na AE_<part>
  ''            =>  explode(",",'pragma,group,system,module,ezer,table,report,form,area,map,panel.main,'
                               .'panel.plain,panel.right,panel.popup,menu.main,menu.left,tabs,var,use,proc,func'),
# pragma a souvislosti
  'pragma'      =>  explode(",",''),
  'module'      =>  explode(",",'panel,panel.right,panel.popup'),
#  block
  'group'       =>  explode(",",'proc,var'),
#  menu
  'menu.main'   =>  explode(",",'proc,menu,item,tabs,tabs.logoff,use,map,table'),
  'menu.ham'    =>  explode(",",'proc,menu.group,item'),
  'tabs'        =>  explode(",",'var,panel.plain,panel.right,form,module,table,report,map'),
  'menu.left'   =>  explode(",",'proc,menu.group'),
  'menu.group'  =>  explode(",",'proc,item,use'),
  'menu.context'  =>  explode(",",'proc,item,item.clipboard'),
  'item'        =>  explode(",",'proc'),
#  top
  'panel'       =>  explode(",",'use,proc,func,var,form,panel.popup'),
  'panel.main'  =>  explode(",",'use,proc,func,var,form,panel.popup,menu.ham'),
  'panel.popup' =>  explode(",",'use,proc,var,form,menu.left'),
  'panel.free'  =>  explode(",",'use,proc,var,form'),
  'panel.plain' =>  explode(",",'use,proc,var,form,panel'),
  'panel.right' =>  explode(",",'use,proc,var,form,menu.left,panel'),
//  'use'         =>  explode(",",'form,group'),
#  map
  'map'         =>  explode(",",'proc'),
#  table
  'table'       =>  explode(",",'text,number,date,time,object'),
  'text'        =>  explode(",",''),
  'number'      =>  explode(",",''),
  'date'        =>  explode(",",''),
  'time'        =>  explode(",",''),
#  report
  'report'      =>  explode(",",'box,const'),
  'const'       =>  explode(",",''),
  'box'         =>  explode(",",'box'),
#  area
  'area'        =>  explode(",",'var,proc,panel.popup'),
#  form
  'form'        =>  explode(",",'view,var,use,proc,browse,browse.smart,radio,label,label.drop,label.map,edit,edit.html,edit.auto,'
                               .'button,button.html,button.submit,button.reset,button.upload,'
                               .'select,select.multi,select.auto,select.map,select.map+,select.map0,'
                               .'field,field.date,field.list,menu,chat,check,list'
                               .($pragma_attrs?',select':'')),
  'view'        =>  explode(",",''),
  'use'         =>  explode(",",'view,var,proc,browse,browse.smart,radio,label,label.drop,label.map,edit,edit.html,edit.auto,'
                               .'button,button.html,button.submit,button.reset,button.upload,'
                               .'select,select.multi,select.auto,select.map,select.map+,select.map0,'
                               .'field,field.date,field.list,menu,chat,check,list'),
  'field'       =>  explode(",",'proc,menu'),
  'field.date'  =>  explode(",",'proc,menu'),
  'field.list'  =>  explode(",",'proc,menu'),
  'label'       =>  explode(",",'proc'),
  'button'      =>  explode(",",'proc,label'),
  'edit'        =>  explode(",",'proc'),
  'edit.html'   =>  explode(",",'proc'),
  'select'      =>  explode(",",'proc,menu'),
  'chat'        =>  explode(",",''),
  'check'       =>  explode(",",'proc,menu'),
#  browse
  'browse'      =>  explode(",",'proc,show,view'),
  'browse.smart'=>  explode(",",'proc,show'),
  'show'        =>  explode(",",''),
#  radio
  'radio'       =>  explode(",",'proc,case'),
  'case'        =>  explode(",",''),
#  list
  'list'   =>  explode(",",'proc,func,label,button,select,select.auto,select.map,select.map0,field,'
                          .'field.date,field.list,menu,check,radio'),
#  proc
  'proc'        =>  explode(",",''),
  'func'        =>  explode(",",''),
  'function'    =>  explode(",",''),
);
# definice povolených vnořených bloků
$blocs = array (                                    // překládají se na AE_<part>
  ''       =>  explode(",",'pragma,system,group,module,ezer,table,report,form,area,map,panel,menu,tabs,var,use,proc'),
  'pragma' =>  explode(",",''),
  'system' =>  explode(",",'module'),
#  ezer
  'ezer'   =>  explode(",",'panel,proc,menu,var,use'),
  'module' =>  explode(",",'panel,proc,menu,var,use'),
  'group'  =>  explode(",",'proc,var'),
  'tabs'   =>  explode(",",'panel,var,form'),
  'panel'  =>  explode(",",'use,proc,menu,var,form'),
  'use'    =>  explode(",",'form,group'),
#  map
  'map'    =>  explode(",",'proc'),
#  table
  'table'  =>  explode(",",'text,number,date,time'),
  'text'   =>  explode(",",''),
  'number' =>  explode(",",''),
  'date'   =>  explode(",",''),
  'time'   =>  explode(",",''),
#  report
  'report' =>  explode(",",'box,const'),
  'const'  =>  explode(",",''),
  'box'    =>  explode(",",'box'),
#  area
  'area'   =>  explode(",",'var,proc,area'),
#  form
  'form'   =>  explode(",",'view,var,proc,browse,radio,label,edit.html,button,select,field,menu,chat,check,list'),
  'view'   =>  explode(",",''),
  'var'    =>  explode(",",''),
  'field'  =>  explode(",",'proc,menu'),
  'label'  =>  explode(",",'proc'),
  'button' =>  explode(",",'proc,label'),
  'edit'   =>  explode(",",'proc'),
  'select' =>  explode(",",'proc,menu'),
  'chat'   =>  explode(",",''),
  'check'  =>  explode(",",'proc,menu'),
#  menu
  'menu'   =>  explode(",",'proc,menu,item,tabs,use'),
  'item'   =>  explode(",",'proc'),
#  browse
  'browse' =>  explode(",",'proc,show'),
  'show'   =>  explode(",",''),
#  radio
  'radio'  =>  explode(",",'proc,case'),
  'case'   =>  explode(",",''),
#  list
  'list'   =>  explode(",",'proc,label,button,select,field,menu,check'),
#  proc
  'proc'   =>  explode(",",''),
  'func'   =>  explode(",",''),
  'function'=>  explode(",",''),
);
# definice povolených speciálních bloků
$specs = array (
//   'vnější bloky'
//            =>  array(),
  'pragma' =>  explode(",",'part'),
  'system' =>  explode(",",'part,note'),
#  ezer
  'group'  =>  explode(",",'part,note'),                                                            //group
  'module' =>  explode(",",'part,coord,note'),
  'ezer'   =>  explode(",",'part,coord,note'),
  'tabs'   =>  explode(",",'part,coor+,note'),
  'panel'  =>  explode(",",'part,coor+,proc,js,note'),
  'use'    =>  explode(",",'use_form,coor+,part,arg,note'),
#  map
  'map'    =>  explode(",",'map_table,part,proc,note'),
#  table
  'table'  =>  explode(",",'part,note'),
  'text'   =>  explode(",",'part'),
  'number' =>  explode(",",'part'),
  'date'   =>  explode(",",'part'),
  'time'   =>  explode(",",'part'),
#  report
  'report' =>  explode(",",'part,coor+,note'),
  'const'  =>  explode(",",'const,cmnt'),
  'box'    =>  explode(",",'part,coor+,cmnt'),
#  area
  'area'   =>  explode(",",'part,proc,note'),
#  form
  'form'   =>  explode(",",'part,coor+,par,view,proc,note'),
  'view'   =>  explode(",",'part,use_table,cmnt'),
  'var'    =>  explode(",",'type,cmnt'),
  'field'  =>  explode(",",'part,coor+,cmnt,proc'),
  'label'  =>  explode(",",'part,coor+,cmnt'),
  'button' =>  explode(",",'part,coor+,cmnt'),
  'edit'   =>  explode(",",'part,coor+,cmnt,proc'),
  'html'   =>  explode(",",'part,coor+,cmnt,proc'),
  'select' =>  explode(",",'part,coor+,cmnt,proc'),
  'chat'   =>  explode(",",'part,coor+,cmnt'),
  'check'  =>  explode(",",'part,coor+,cmnt,proc'),
#  menu
  'menu'   =>  explode(",",'part'),
  'item'   =>  explode(",",'part'),
#  browse
  'browse' =>  explode(",",'part,coor+,cmnt,proc'),
  'show'   =>  explode(",",'part,coor+,cmnt'),
#  radio
  'radio'  =>  explode(",",'part,coor+,cmnt,proc'),
  'case'   =>  explode(",",'part,coor+,cmnt'),
#  list
  'list'   =>  explode(",",'part,coor+,cmnt,proc'),
#  proc
  'proc'   =>  explode(",",'par,code,note'),
  'func'   =>  explode(",",'par2,code2,note'),
  'function'=>  explode(",",'par2,code2,note')
);
# definice bloků s implementovanou metodou get
$block_get= array(
  'const'  => 1,
  'var'    => 1,
  'field'  => 1, 'field.date' => 1, 'field.list' => 1,
  'label'  => 1, 'label.drop' => 1, 'label.map' => 1,
  'button' => 1,
  'edit'   => 1, 'edit.html' => 1, 'edit.auto' => 1,
  'html'   => 1,
  'select' => 1, 'select.auto' => 1, 'select.map' => 1, 'select.map0' => 1,
  'chat'   => 1,
  'check'  => 1,
  'show'   => 1,
  'radio'  => 1,
  'case'   => 1
);
# definice univerzálních atributů
$uni_attribs = explode(",",'_sys:s,tag:s');
# definice povolených atributů
$attribs = array (
  'pragma' =>  explode(",",'expr:s'),
  'system' =>  explode(",",'active:i,title:s'),
#  ezer
  'group'  =>  explode(",",'skill:s,include:s'),
  'ezer'   =>  explode(",",'active:i,units:s'),
  'module' =>  explode(",",'active:i,units:s,title:s,help:s,skill:s'),
  'panel'  =>  explode(",",'title:s,type:s,under:i,style:s,css:s,skill:s,include:s,value:s,par:o,format:s'),
  'use'    =>  explode(",",'tabindex:n,skill:s,format:s,par:o,style:s'),
#  map
  'map'    =>  explode(",",'db:s,where:s,order:s,key_id:s'),
#  table
  'table'  =>  explode(",",'db:s,key_id:s'),
  'text'   =>  explode(",",'map_pipe:m,sql_pipe:s,fkeys:i,help:s'),
  'number' =>  explode(",",'key:s,map_pipe:m,sql_pipe:s,fkey:i,help:s'),
  'date'   =>  explode(",",'map_pipe:m,sql_pipe:s,help:s'),
  'time'   =>  explode(",",'key:s,map_pipe:m,sql_pipe:s,help:s'),
#  report
  'report' =>  explode(",",'format:s'),
  'box'    =>  explode(",",'title:s,xactive:n,pagebreak:s,css:s,style:s'),
#  area
  'area'   =>  explode(",",'title:s,style:s,css:s'),
#  form
  'form'   =>  explode(",",'style:s,css:s,tabindex:n,title:s,key:s,key_id:s,par:o'),
  'view'   =>  explode(",",'order:s,join:s,join_type:s,expr:s'),
  'label'  =>  explode(",",'type:s,data:i,expr:s,title:s,style:s,css:s,help:s,format:s,attribute:s,skill:s,par:o'),
  'field'  =>  explode(",",'title:s,data:i,par:o,expr:s,map_pipe:m,sql_pipe:s,style:s,format:s,help:s,css:s,'
                          .'type:s,attribute:s,value:s,skill:s,tabindex:n,id:s'),  // help_bg:s
  'button' =>  explode(",",'type:s,title:s,help:s,style:s,css:s,format:s,skill:s,par:o'),
  'edit'   =>  explode(",",'title:s,type:s,data:i,value:s,expr:s,help:s,style:s,css:s,format:s,skill:s,par:o,tabindex:n,id:s'),
  'select' =>  explode(",",'title:s,type:s,par:o,data:i,sql_pipe:s,options:m,labels:m,map_pipe:m,fkeys:i,fkey:i,expr:s,format:s,'
                          .'help:s,style:s,css:s,help_bg:s,auto:s,value:s,skill:s,tabindex:n'),
  'chat'   =>  explode(",",'title:s,data:i,par:o,divide:n,format:s,skill:s'),
  'check'  =>  explode(",",'data:i,style:s,par:o,css:s,help:s,title:s,format:s,skill:s,value:s,tabindex:n'),
#  tabs
  'tabs'   =>  explode(",",'type:s,skill:s,title:s,active:i,include:s,where:s'),
#  menu
  'menu'   =>  explode(",",'type:s,skill:s,title:s,par:o,active:i,format:s,join:i'),
  'item'   =>  explode(",",'type:s,title:s,skill:s,par:o,format:s,help:s,active:i'),
#  browse
  'browse' =>  explode(",",'key_id:s,type:s,buf_rows:n,qry_rows:n,wheel:n,css_rows:s,rows:n,group_by:s,optimize:o,format:s'),
  'show'   =>  explode(",",'order:s,title:s,css_cell:s,data:i,expr:s,map_pipe:m,bgcolor:m,js_pipe:s,sql_pipe:s,format:s,help:s,skill:s'),
#  radio
  'radio'  =>  explode(",",'data:i,expr:s,style:s,css:s,help:s,title:s,value:s,format:s'),
  'case'   =>  explode(",",'title:s,expr:s,value:s,css:s'),
#  list
  'list'   =>  explode(",",'rows:n'),
);
$attribs_type = array (
  's' => 'string', 'n' => 'number', 'i' => 'name', 'm' => 'map name', 'o' => 'object'
);
# tabulky symbolů
$tab_symb_obj= array();                   // zobrazení: symb => obj
$tab_id_symb= array();                    // zobrazení: id => symb
# definice jmen knihovních funkcí
$cmd_ajax= ',form_load,form_save,form_insert,browse_load,browse_seek,map_load,code_load,code_save'
         . ',browse_export,browse_map,browse_select'
         . ',user_login,user_logout,log_load,select_load,ask,group_fill,menu_fill,menu_fill2,delete_record'
         . ',form_make,group_make,panel_modal,set_query,ajax,_ajax';
# pseudofunkce realizované přímo interpretem
$cmd_gen=  ',val,wait,return';                      // val=vyzvednutí parametru, wait=zastavení interpreta,čekání
# funkce realizované objektem 'ae'
$cmd_lib= // funkce nad stringy
           ',conc,cconc,cset,echo,clipboard,clear,sys,now,now_sql,fdate,sql2date,date2sql,replace,substr,browse_cond,confirm,prompt,htmlentities'
          // aritmetika
         . ',sum,minus,castka_slovy'
          // obecné příkazy
         . ',skip,start,stop,last,refresh,eq,lt,gt,set_cookie,get_cookie,error,alert,call,and,or,not,make,set_css,_call'
          // meta-příkazy
         . ',meta_save,code_dump,code_code,code_start,code_run,code_design,code_file,panel_close'
         . ',panel_wide,panel_hide,panel_show,formsave,form_self,'
          // příkazy nějaké třídy, ale realizované ae
         . 'set_sort'
;
# funkce realizované některou třídou QE_... (this=env)
// $cmd_metd= ',form_show,form_init,form_copy,form_focus,form_browse,form_key,form_key_id,form_same,browse_blur,browse_focus,browse_key,browse_init'
// 	 . ',browse_count,selected,init_queries,get_query,raise,fire,set,let,init,plain,change,get,self,popup,active,fold,html_edit'
//          . ',group_add,group_init,curr,elem_key,form_option,form_wide,add_key,enable,select_key,select_init'
//          . ',get_html,get_json,get_page,print,debug,report_init,report_check,report_repage,report_batch,'
//          . 'graft,button_display,button_enable,ref_attr,def_attr,display';
# inicializace skalárních proměnných (kvůli dávkové kompilaci)
$first_panel= '';
//$cmd_type= array();                                 // pomocné: op_id -> ajax|
$gen_prefix= '';                                    // prefix všech generovaných symbolů
$use_list= array();                                 // zobrazeni form_id -> use_symbol*
$js1= $js2= $js3= '';                               // první a druhá posloupnost JS
$js3_ic= 0;
# pomocná pole
$id_anonymous= 0;                                   // poslední index anonymního identifikátoru
$attribs1= array();                                 // pomocné pole - attribs před : tj. jména
$attribs2= array();                                 // pomocné pole - attribs po : tj. typy
$keywords= array();                                 // pomocné pole - sjednocení blocs
# definice povolených omezovačů
$dels= '{[(|)]};:,*';                   // specifickou roli má tečka, apostrof, uvozovka
// úpravy od PHP
if (!defined('T_OLD_FUNCTION'))  define('T_OLD_FUNCTION', 'T_FUNCTION');
if (!defined('T_ML_COMMENT'))    define('T_ML_COMMENT',   'T_COMMENT');
else                             define('T_DOC_COMMENT',  'T_ML_COMMENT');
if (!defined('T_SMALLER_OR_EQUAL')) 
  define('T_SMALLER_OR_EQUAL',PHP_VERSION<7 ? 287 : 290); // definice v PHP prostě chyběla
  
if (!defined('T_MATCH'))         define('T_MATCH',-2); // definice až  od PHP 8
$tok2lex= array(
  T_AND_EQUAL => 'del', T_ARRAY => 'id', T_ARRAY_CAST => 'x', T_AS => 'id', //T_BAD_CHARACTER => 'x',
  T_BOOL_CAST => 'x', T_BOOLEAN_AND => 'del', T_BOOLEAN_OR => 'del', T_BREAK => 'id', T_CASE => 'id',
  T_CLASS => 'id', T_CLASS_C => 'x', T_CLOSE_TAG => 'x', T_COMMENT => 'cmnt', T_CONCAT_EQUAL => 'x',
  T_CONST => 'id', T_CONSTANT_ENCAPSED_STRING => 'str', T_CONTINUE => 'id', T_CURLY_OPEN => 'del',
  T_DEC => 'del', T_DECLARE => 'id', T_DEFAULT => 'id', T_DIV_EQUAL => 'x', T_DNUMBER => 'num',
  T_DO => 'id', T_DOLLAR_OPEN_CURLY_BRACES => 'del', T_DOUBLE_ARROW => 'x', T_DOUBLE_CAST => 'x',
  T_ECHO => 'id', T_ELSE => 'id', T_ELSEIF => 'id', T_EMPTY => 'id',
  T_ENCAPSED_AND_WHITESPACE => 'blank', T_END_HEREDOC => 'x', T_ENDDECLARE => 'id',
  T_ENDFOR => 'id', T_ENDFOREACH => 'id', T_ENDIF => 'id', T_ENDSWITCH => 'id', T_ENDWHILE => 'id',
  T_EVAL => 'id', T_EXIT => 'id', T_EXTENDS => 'id', T_FILE => 'x', T_FOR => 'id',
  T_FOREACH => 'id', T_FUNC_C => 'x', T_FUNCTION => 'id', T_GLOBAL => 'id', //T_CHARACTER => 'del',
  T_IF => 'id', T_INC => 'del', T_INCLUDE => 'id', T_INCLUDE_ONCE => 'id', T_INLINE_HTML => 'x',
  T_INT_CAST => 'x', T_IS_EQUAL => 'del', T_IS_GREATER_OR_EQUAL => 'del', T_IS_IDENTICAL => 'x',
  T_IS_NOT_EQUAL => 'del', T_IS_NOT_IDENTICAL => 'x', T_ISSET => 'id', T_LINE => 'x', T_LIST => 'id',
  T_LNUMBER => 'num', T_LOGICAL_AND => 'id', T_LOGICAL_OR => 'id', T_LOGICAL_XOR => 'id',
  T_MATCH => 'id',
  T_MINUS_EQUAL => 'x', T_ML_COMMENT => 'cmnt', T_MOD_EQUAL => 'x', T_MUL_EQUAL => 'x',
  T_NEW => 'id', T_NUM_STRING => 'num', T_OBJECT_CAST => 'x', T_OBJECT_OPERATOR => 'x',
  T_OLD_FUNCTION => 'id', T_OPEN_TAG => 'x', T_OPEN_TAG_WITH_ECHO => 'x', T_OR_EQUAL => 'x',
  T_PAAMAYIM_NEKUDOTAYIM => 'x', T_PLUS_EQUAL => 'x', T_PRINT => 'id', T_REQUIRE => 'id',
  T_REQUIRE_ONCE => 'id', T_RETURN => 'id', T_SL => 'x', T_SL_EQUAL => 'x',
  T_SMALLER_OR_EQUAL => 'del', T_SR => 'x', T_SR_EQUAL => 'x', T_START_HEREDOC => 'x',
  T_STATIC => 'id', T_STRING => 'id', T_STRING_CAST => 'str', T_STRING_VARNAME => 'id',
  T_SWITCH => 'id', T_UNSET => 'id', T_UNSET_CAST => 'x', T_USE => 'id', T_VAR => 'id',
  T_VARIABLE => 'id', T_WHILE => 'id', T_WHITESPACE => 'blank',  T_XOR_EQUAL => 'x',
  T_ABSTRACT => 'id',
  -1 => 'del'
);
$func_help= <<<__EOT
    Funkce tvořená příkazy if, while, for, for-of, switch, break, continue
__EOT;
$blocs_help = array (                                     // popis pro dokumentaci
  ''       =>  "Nevnořitelné základní bloky modulu.",
#  ezer
  'module' =>  "Definice modulu, jeho panelů, proměnných a procedur. Jeho jméno ''modul'' určuje
také jméno souboru ''prog_modul.ezer'' se zdrojovým textem a
jméno souboru ''prog_modul.wiki'' s dokumentací (''prog'' je jméno programu).",
  'ezer'   =>  "Definice systémového modulu ''meta'', který běží samostatně jako ladící prostředí programu",
  'panel'  =>  "Samostatně zobrazitelná část modulu, např. stránka nebo její část, modální dialog, ...",
  'use'    =>  "Deklarace statického nebo dynamického použití formuláře.",
#  map
  'map'    =>  "Blok pro práci s číselníky.",
#  table
  'table'  =>  "Popis MySQL tabulky, včetně popisů položek a popisů transformací dat na serveru.",
  'text'   =>  "Textová položka (char, varchar, text)",
  'number' =>  "Číselná položka.",
  'date'   =>  "Datová položka (date)",
  'time'   =>  "Časová položka (datetime)",
#  report
  'report' =>  "Popis reportu - tiskové sestavy.",
  'box'    =>  "Obdélníková část reportu, naplněná a zobrazená podle typu.",
#  area
  'area'   =>  "Popis oblasti.",
#  form
  'form'   =>  "Popis formuláře.",
  'view'   =>  "Popis použití tabulky ve formuláři, včetně vztahu k jiným použitým tabulkám.",
  'var'    =>  "Proměnná",
  'field'  =>  "Položka tabulky nebo výraz",
  'label'  =>  "Jednořádkový text",
  'button' =>  "Tlačítko",
  'edit'   =>  "Textové pole s více řádky",
  'select' =>  "Výběrová položka",
  'chat'   =>  "Kombinovaný prvek pro zaznamenání historie záznamů",
  'check'  =>  "Zaškrtávací pole",
#  menu
  'menu'   =>  "Kontextové menu",
  'item'   =>  "Položka kontextového menu",
#  browse
  'browse' =>  "Kombinovaný prvek pro zobrazení množiny řádků tabulky, mezi sloupci musí být (třeba se šířkou 0) vřazen primární klíč MySQL tabulky (jinak nebudou korektně fungovat funkce s prefixem browse_)",
  'show'   =>  "Jeden řádek browse",
#  proc
  'proc'   =>  "Procedura s kódem zapsaným jako logický výraz - středník je (c)and, svislítko je (c)or ...",
  'func'   =>  "Funkce s kódem zapsaným v rozšířené podmnožině javascriptu",
  'function' =>  "Funkce s kódem zapsaným v rozšířené podmnožině javascriptu"
);
}
