<?php # (c) 2007-2018 Martin Smidek (martin@smidek.eu)
/**
 * verze jádra pro PHP7
 */

/**
# ============================================================================================= ROOT
# skeleton hlavního programu aplikace, obsluha příkazového řádku
#   menu=m[.s[.l.g.i]]  -- tabs, panel, menu.left, menu.group, menu.item
#   trace=ssxxxx        -- ++UTu
#   theight=x           -- výška trasovacího pruhu v px
#   skin                -- ck|blue
#   session=1           -- zobrazení $_SESSION v informačním přihlašovacím okně
# funkce předpokládá natažení/require souboru $app.inc
# parametry
#   $app                -- jméno podložky aplikace
#   $app_name           -- jméno aplikace
#   $welcome            -- typ=('test'|'news'|'info')
#                          může být i pole, kde pole[0]=typ, pole[1]=(info|kontakt|info)
#   $options: object    -- položky předané do Ezer.options a $EZER->options
#     curr_version         -- při přihlášení je nahrazeno nejvyšší hodnotou ezer_kernel.version
#     group_db             -- databáze s tabulkami společnými pro skupinu aplikací např. _help
#     group_login
#     skill
#     autoskill
#     skin
#     login_interval       -- povolená nečinnost v minutách (default 2 hodiny)
#     tabu_db              -- databáze, se kterou se nesmí pracovat (např. "ostrá")
#     path_files           -- absolutní cesta do složky files/{root}
#   $js                 -- seznam skriptů
#   $css                -- seznam stylů
#   $pars: object       -- položky parametrizující aplikaci
#     app_root             -- bool: startovní soubory $app.php a $app.inc jsou ve složce $app
#     title_right          -- string: zobrazovat formátované jméno aplikace nebo selektor aplikace
#     no_local             -- bool: nezohledňovat lokální přístup pro watch_key,watch_ip
#     watch_key            -- bool: povolit přístup jen po vložení klíče
#     watch_ip             -- bool: povolit přihlášení pouze z IP adres v tabulce _user (default false)
#     autologin            -- string: dvojice uname/pword použitá pro automatické přihlášení
#     contact              -- string: alternativní kontaktní údaje při přihlášení
#     news_days            -- int: počet dnů pro novinky (default 12)
#     news_cond            -- string: výběrová relace pro novinky (default 'cast!=1')
#     template             -- string: menu|panel|user typ hlavního objektu aplikace (MenuMain,Panel)
#     template_user        -- string: pokud template=user - může obsahovat %header,%login,%info,%chngs
#     post_server          -- array: server ze kterého jsou volány dialogy (ostrý,lokální)
#     gc_maxlifetime       -- int: životnost SESSION v sec (default 12 hodin)
#     ondomready           -- bool: na startu volat fci 'ondomready'
#   $const: object      -- definice hodnot nedefinovaných konstant
# globální proměnné na vstupu
#   $ezer_local         -- bool: ladící běh
#   $ezer_path_serv     -- string: cesta ke skriptům
*/
# ----------------------------------------------------------------------------------------- root_php
function root_php3($app,$app_name,$welcome,$skin,$options,$js,$css,$pars=null,$const=null,$start_sess=true) {
  global $EZER, $app_root, $ezer_root, $ezer_path_serv, $ezer_path_docs, $ezer_local, $ezer_system,
    $gc_maxlifetime, $ezer_db, $http;
  // převzetí url-parametrů
  $menu=    isset($_GET['menu']) ? $_GET['menu'] : '';
  $xtrace=  isset($_GET['trace']) ? $_GET['trace'] : '';
  $skin=    isset($_GET['skin']) ? $_GET['skin'] : $skin;
  $theight= isset($_GET['theight']) ? $_GET['theight'] : 240;
  $dbg=     isset($_GET['dbg']) ? $_GET['dbg'] : '';
  if ( !isset($http) ) $http= 'http';
  // identifikace prohlížeče a platformy prohlížeče: Android => Ezer.client == 'A'
  $ua= $_SERVER['HTTP_USER_AGENT'];
  ezer_browser($browser,$browser_version,$platform,$ua);
  $_SESSION['platform']= $platform;
  $_SESSION['browser']= $browser;
  // doplnění meta tagů pro mobilní platformy
  $meta_link= "";
//                                                 $platform= 'I';
  if ( isset($pars->template_meta) ) {
    $meta_link= $pars->template_meta;
  }
  elseif ( $platform=='A' ) {
    $s= 1280/1024;
    $meta_link= <<<__EOD
  <meta name="viewport" content="user-scalable=yes,initial-scale=0.5,minimum-scale=0.1,maximum-scale=1">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="format-detection" content="telephone=no">
__EOD;
  }
  elseif ( $platform=='I' ) {
    $s= "1";
    $meta_link= <<<__EOD
  <meta name="viewport" content="user-scalable=1.0,initial-scale=$s,minimum-scale=$s,maximum-scale=$s">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="format-detection" content="telephone=no">
__EOD;
//   <meta name="viewport" content="user-scalable=no, initial-scale=1.2, width=device-width" />
//   <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
//   <meta name="apple-mobile-web-app-capable" content="yes" />
  }
  // interpretace parametrů
  $head= "";
  $minify= false;
  $autologin= isset($pars->autologin) ? explode('/',$pars->autologin) : null;
  $app_root=  isset($pars->app_root) ? $pars->app_root : 0;
//                                                 echo("app_root=$app_root");
  if ( $app_root ) 
    chdir($_SESSION[$ezer_root]['abs_root']);
  $ezer_root= $app;
//                                                 echo("ezer_root/1=$ezer_root");
  $title= isset($pars->title) ? $pars->title : '';
  $title_right= isset($pars->title_right) ? $pars->title_right : $app_name;
  $ezer_template= $browser=='IE' ? 'IE' : (isset($pars->template) ? $pars->template : 'menu');
  $post_server= isset($pars->post_server) ? $pars->post_server[$ezer_local] : null;
  // ikona aplikace
  if ( isset($pars->favicon ) )
    $favicon= $pars->favicon;
  else {
    $favicon= isset($pars->favicon ) ? $pars->favicon
      : ($ezer_local ? "favicon_local.ico" : "favicon.ico");
    $favicon= file_exists("./$app/img/{$favicon}") ? $favicon
      : ($ezer_local ? "{$app}_local.png" : "{$app}.png");
  }
  if ( $start_sess ) {
    // promítnutí nastavení do SESSION
    $gc_maxlifetime= isset($pars->gc_maxlifetime) ? $pars->gc_maxlifetime : 12*60*60;
    $session= "php";                      // standardní práce se SESSION
    //You cannot change the session module's ini settings at this time i                      //TODO
//    ini_set('session.gc_maxlifetime',$gc_maxlifetime);
//    if ( !isset($_SESSION) ) session_start();
//    $_SESSION['gc_maxlifetime']= $gc_maxlifetime;
    if ( isset($_GET['session']) ) {             // zobraz stav session hned po startu
      $info= $_SESSION;
    }
  }
  // přenesení GET parametrů do SESSION aby byly přístupné i v root.ini volané z ezer2.php
  // a do Ezer.get aby byly přístupné v klientu, klíč menu je vynechán
  $gets= ''; $del= '';
  foreach($_GET as $key => $val) {
    $_SESSION[$ezer_root]['GET'][$key]= $val;
    if ( $key!='menu' ) {
      $gets.= "$del$key:'$val'"; $del= ',';
    }
  }
  $_SESSION['trace_height']= $theight;
  $_SESSION[$ezer_root]['skin']= $skin;
  $_SESSION[$ezer_root]['app_name']= $app_name;
  if ( $app_root )
    $_SESSION[$ezer_root]['app_root']= $app_root;
  $_SESSION['skin']= $skin;
  // refresh je buď definován parametrem nebo odvozen ze session
  $refresh= isset($options->refresh) ? $options->refresh :(
    isset($_SESSION[$ezer_root]['sess_state']) && $_SESSION[$ezer_root]['sess_state']=='on'
    ? 'true' : 'false');
  if ( $ezer_local && isset($_GET['skin']) ) {
    $_SESSION['skin']= $skin;
    $title.= "/$skin";
  }
//                                                                                 ZRUŠENO 31.1.2015
//   // zjištění a zapamatování svn-verze (jen na serveru, kam je ukládáno pomocí svn.update)
//   $verze= root_svn("{$EZER->version},$ezer_root");
//   if ( $verze ) {
//     $_SESSION[$ezer_root]['svn_version']= $verze;
//   }
  // ZPRACOVANÍ OPTIONS
  //   přenesení informace do klienta
  //     skill: oprávnění, který uživatel musí mít, aby aplikaci vůbec spustil
  //     autoskill: oprávnění, které dostává automaticky ten, kdo aplikaci spustí
  $js_options= (object)array(
    'debug'             => $ezer_template=='menu' ? "window.parent!=window" : 'false',
    'refresh'           => $refresh,
    'skin'              => "'$skin'",
    'start_datetime'    => date("'Y-m-d H:i:s'"),
    'login_interval'    => 2*60,                // povolená nečinnost v minutách - default=2 hodiny
    'mini_debug'        => isset($options->mini_debug) ? $options->mini_debug
                         : ($ezer_template=='menu' ? 'true' : 'false'),
    'status_bar'        => $ezer_template=='menu' ? 'true' : 'false',
    'to_speed'          => 1,
    'to_trace'          => $ezer_template=='menu' ? 'true' : 'false',
    'path_docs'         => "'$ezer_path_docs'",  // složka pro upload skrze LabelDrop      OBSOLETE!
    'theight'           => $theight
  );
  if ( isset($options->path_files_h) )
    $_SESSION[$ezer_root]['path_files_h']= $options->path_files_h;
  if ( isset($options->path_files_s) )
    $_SESSION[$ezer_root]['path_files_s']= $options->path_files_s;
  if ( isset($options->group_db) )
    $_SESSION[$ezer_root]['group_db']= strtr($options->group_db,array('"'=>'',"'"=>''));
  //  pokud je definováno $options->curr_version a dostupná db ezer_kernel přečte verzi jádra
  if ( isset($options->curr_version) && $options->curr_version ) {
    $version= 0;
    // verze podle db jádra - je, když je curr_version
    $o= select_object("MAX(version) AS _max","_help","kind='v' GROUP BY kind",'ezer_kernel');
    if ( $o ) $version= max($version,$o->_max);
    // verze podle db skupiny - je-li group_db
    if ( isset($options->group_db) ) {
      $o= select_object("MAX(version) AS _max","_help","kind='v' GROUP BY kind",'ezer_group');
      if ( $o ) $version= max($version,$o->_max);
    }
    // verze podle db aplikace
    $o= select_object("MAX(version) AS _max","_help","kind='v' GROUP BY kind");
    if ( $o ) $version= max($version,$o->_max);
    // zapamatování verze
    $_SESSION['curr_version']= $options->curr_version= $version;
  }
  // ošetření autologin
  if ( $autologin ) {
    $js_options->must_log_in= 'false';
    $js_options->uname= "'$autologin[0]'";
    $js_options->pword= "'$autologin[1]'";
  }
  elseif ( !isset($js_options->must_log_in) ) {
    $js_options->must_log_in= $ezer_template=='menu' ? 'true' : 'false';
  }
  $js_options->watch_ip= $EZER->options->watch_ip= isset($pars->watch_ip) ? '1' : '0';
  $js_options->watch_key= $EZER->options->watch_key= isset($pars->watch_key) ? '1' : '0';
  $js_options->CKEditor= isset($pars->CKEditor) ? $pars->CKEditor : '{}';
  $js_options->dbg=      isset($pars->dbg) ? $pars->dbg : '0';
  $js_options->ondomready= isset($pars->ondomready) ? $pars->ondomready : '0';
  if ( $menu ) $js_options->start= "'$menu'";
  if ( $xtrace ) {
    $js_options->to_trace= 1;
    $js_options->show_trace= 1;
    $js_options->ae_trace= "'{$_GET['trace']}'";
  }
  foreach ($options as $id=>$val) {
    $EZER->options->$id= $val;
    $js_options->$id= $val;
    if ( $id=='group_login' ) {
      $_SESSION[$id]= strtr($val,array('"'=>'',"'"=>''));
    }
  }
  $options_txt= ''; $del= '';
  foreach ($js_options as $id => $val) {
    $options_txt.= "$del\n      $id: $val";
    $del= ',';
  }
  $const_txt= ''; $del= '';
  if ( $const ) {
    foreach ($const as $id => $val) {
      $const_txt.= "$del\n      $id: $val";
      $del= ',';
    }
  }
  // klíčová slova pro debugger
  $head_jush= '';
  if ( isset($pars->dbg) && $pars->dbg ) {
    require_once("$ezer_path_serv/sys_doc.php");
    pspad_keys($res,$key1,$key2,$key3);
    $k= strtr(substr($key2,0,-1),array("="=>'|',"\n"=>''));
    $head_jush= <<<__EOD
  <script type="text/javascript">
//     if ( jush )
//       jush.links2.sql=/(\b)(($k))\b(\s*)/g;
  </script>
__EOD;
  }

  // SLEDOVÁNÍ IP ADRESY
  $ip_ok= true;
  $ip_msg= '';
  $key_msg= '';
  if ( (isset($pars->watch_ip) || isset($pars->watch_key))
    && (isset($pars->no_local) && $pars->no_local || !$ezer_local ) ) {
    // ověření přístupu - externí přístup hlídat vždy, lokální jen je-li  no_local=true
    if ( $pars->watch_key && ($watch_key= isset($_POST['watch_try']) ? $_POST['watch_try'] : '') ) {
      $watch_lock= 
          @file_get_contents("{$_SESSION[$ezer_root]['abs_root']}/$ezer_root/code/$ezer_root.key");
      $ip_ok= $watch_lock==$watch_key;
      $key_msg= $ip_ok ? '' : '<u>správného</u>';
    }
    elseif ( $pars->watch_ip ) {
      // nejprve zkusíme ověřit známost počítače - zjištění klientské IP
      $my_ip= isset($_SERVER['HTTP_X_FORWARDED_FOR'])
        ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
      // zjištění dosud povolených IP
      ezer_connect(".main.",false,true);
      $ips= select("GROUP_CONCAT(ips)","_user","ips!=''",'ezer_system');
      // kontrola
      $ips= str_replace(' ','',$ips);
      $ip_ok= strpos(",$ips,",",$my_ip,")!==false;
      if ( !$ip_ok ) {
        // zapiš pokus o neautorizovaný přístup
        $day= date('Y-m-d'); $time= date('H:i:s');
        $browser= $_SERVER['HTTP_USER_AGENT'];
        $qry= "INSERT _touch (day,time,user,module,menu,msg)
               VALUES ('$day','$time','','error','ip?','|$my_ip||$browser')";
        $res= pdo_qry($qry);
      }
    }
  }
  $login= $browser=='IE'
    ? <<<__EOD
        <form id='watch_key' action='$app.php' method='post'>
          <input id='watch_try' name='watch_try' type='hidden' value='nic' />
          Z tohoto počítače se do aplikace <b>$app_name</b> není možné přihlásit
          bez vložení $key_msg klíče.
          <br/><br/>O zřízení přístupu je možné v&nbsp;oprávněných případech
          požádat správce systému.
          <br/><br/>Kontaktní údaje jsou uvedeny vpravo.<br><small>$ip_msg</small>
        </form>
__EOD
    : ( $ip_ok
    ? <<<__EOD
        <form id='logme' method="post" onsubmit="return false;" enctype="multipart/form-data">
          <span>uživatelské jméno</span><br />
          <input id="username" type="text" tabindex="1" title="jméno" name="name" value="" /><br />
          <span>heslo</span><br />
          <input id="password" type="password" tabindex="2" title="heslo" name="pass"  value="" /><br />
          <span id="login_msg"></span><br />
          <button id="login_on" tabindex="3">
          	Přihlásit se&nbsp;&nbsp;<i class="fa fa-thumbs-o-up fa-flip-horizontal"></i>
          </button>
        </form>
__EOD
    : ( $pars->watch_key ? <<<__EOD
        <form id='watch_key' action='$app.php' method='post'>
          <input id='watch_try' name='watch_try' type='hidden' value='' />
          Z tohoto počítače se do aplikace <b>$app_name</b> není možné přihlásit
          bez vložení $key_msg klíče.
          <br/><br/>O zřízení přístupu je možné v&nbsp;oprávněných případech
          požádat správce systému.
          <br/><br/>Kontaktní údaje jsou uvedeny vpravo.<br><small>$ip_msg</small>
        </form>
__EOD
    : <<<__EOD
        Z tohoto počítače se do aplikace <b>$app_name</b> není možné přihlásit.
        <br/><br/>O zřízení přístupu je možné v&nbsp;oprávněných případech
        požádat správce systému.
        <br/><br/>Kontaktní údaje jsou uvedeny vpravo.<br><small>$ip_msg</small>
__EOD
  ));
  // PŘIHLAŠOVACÍ DIALOG
  $chngs= "";
  $css_login= "";
  $kontakt= '';
  if ( isset($pars->contact) ) {
    $kontakt= $pars->contact;
  }
  else {
    $napiste= isset($EZER->options->mail)
      ? "napište na mail <a href='mailto:{$EZER->options->mail}'>{$EZER->options->mail}</a>" : '';
    $napiste.= isset($EZER->options->phone) 
      ? ($napiste ? " nebo " : ''). " zavolejte na {$EZER->options->phone}" : '';
    $kontakt.= $napiste 
      ? " V případě zjištění problému nebo potřeby konzultace prosím $napiste." : '';
    $kontakt.= $napiste && isset($EZER->options->author)
      ? "<br><br>Za spolupráci děkuje {$EZER->options->author}" : '';
  }
  $type= $ip_ok ? (is_array($welcome) ? $welcome[0] : $welcome) : 'kontakt';
  switch ($type) {
  case 'kontakt':
    $info= $kontakt;
    $info= "<div class='login_a_msg'><br>$info</div>";
    break;
  case 'test':                          // zobraz stav SESSION, pokud je url parametr
//    $info= isset($_GET['session'])
//      ? "<div class='dbg'>".debugx($_SESSION,'$_SESSION:')."</div>"
//      : "...&amp;session=1 zobrazí \$_SESSION<br/><br/>".
//        (is_array($welcome) ? $welcome[1] : $kontakt);
//    break;
  case 'info':
    $info= is_array($welcome) ? $welcome[1] : $kontakt;
    $info= "<div class='login_a_msg'>$info</div>";
    break;
  case 'chngs':
    $css_login= "login_chngs";
    $info= "<div class='login_a_msg'><br>$kontakt</div>";
    $chng= doc_chngs_show('ak',30,$app_name);
    $chngs= "<div id='login_chngs'><h1>Přehled posledních změn aplikace</h1><div>$chng</div></div>";
    break;
  case 'news':
    require_once("$ezer_path_serv/reference.php");
    require_once("$ezer_path_serv/sys_doc.php");
    $kontakt= is_array($welcome) ? $welcome[1] : $kontakt;
    $db_err= ezer_connect(".main.",false,true);
    if ( !$db_err ) {
      $cond= isset($pars->news_cond) ? $pars->news_cond : 'cast!=1';
      $cond= $cond ? "$cond AND" : '';
      $dnu= isset($pars->news_days) ? $pars->news_days : 12;
      $info= doc_todo_show("$cond SUBDATE(NOW(),$dnu)<=kdy_skoncil AND kdy_skoncil!='0000-00-00' ");
      if ( !$info )
        $info= "<div class='login_a_msg'>
          Během posledních $dnu dnů nedošlo v&nbsp;systému k&nbsp;podstatným změnám.<br/><br/>
          $kontakt</div>";
      else
        $info.= "<hr/>$kontakt";
    }
    else {
      $info= "<hr/>databáze není přístupná";
    }
    // přidání NOTIFY informace dodané nepovinnou funkcí ezer_login_notify
    if ( function_exists("ezer_login_notify") ) {
      $notify= ezer_login_notify();
      if ( $notify ) {
        $info= "<div id='login_notify' class='login_notify'>$notify</div>$info";
      }
    }
    $info.=  <<<__EOD
      <script>
        // přidá obsluhu vnořeným elementům <a href='help://....'>
        jQuery('login_2').find('a').each(function(i,el) {
          if ( el.href && el.href.substr(0,7)=='help://' ) {
            jQuery(el)
              .click: function(ev) {
                Ezer.app.help_text({sys:ev.target.href.substr(7)});
                return false;
              }
            })
          }
        });
      </script>
__EOD;
    break;
  }
  // spojení všech CSS a JS do jediného souboru pokud je $minify==true a $_GET['dbg'] je prázdné
  if ( $browser!='IE' && $minify && !$dbg ) {
//     if ( !$ezer_local ) define('MINIFY_BASE_DIR',$ezer_path_serv);
//     require_once('ezer2.2/server/licensed/minify.php');
//     $minifyCSS= new Minify(TYPE_CSS);
//     $minifyJS= new Minify(TYPE_JS);
//     $minifyCSS->addFile($css);
//     $minifyJS->addFile($js);
//     file_put_contents("$ezer_root.css",$css= $minifyCSS->combine());
//     file_put_contents("$ezer_root.js",$js= $minifyJS->combine());
//     // header pro běh bez laděni
//     $head.= <<<__EOD
//     <script src="$ezer_root.js" type="text/javascript" charset="utf-8"></script>
//     <link rel="stylesheet" href="$ezer_root.css" type="text/css" media="screen" charset="utf-8" />
// __EOD;
  }
  else {
    // header pro běh s laděním
    if ( $browser!='IE' ) {
      foreach($js as $x) {
        $x= trim($x);
        $head.= substr($x,0,1)=='<'
          ? "\n  $x\n"
          : "\n  <script src='$x' type='text/javascript' charset='utf-8'></script>";
      }
    }
    foreach($css as $x) {
      // rozklad zápisu href=id
      $id= $get= '';
      list($href,$id)= explode('=',"$x=");
      if ( $id ) {
        $id= "id='$id'";
        $get= "?root=$ezer_root";
      }
      $head.= "\n  <link $id rel='stylesheet' href='$href$get' type='text/css' media='screen' charset='utf-8' />";
    }
  }
  // definice povinného začátku a konce HTML stránky
  $html_footer= '';
  //$html_base= $app_root ? "\n  <base href=\"http://".$_SERVER["HTTP_HOST"].'">' : '';
  $html_base= $app_root ? "\n  <base href=\"$http://$app_root/\">" : '';
  $html_header= '';
//   $html_header.= "\xEF\xBB\xBF";    // DOM pro UTF-8
  $html_header.= <<<__EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>$html_base
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=9" /> $meta_link
  <link rel="shortcut icon" href="$ezer_root/img/$favicon" />
  <title>$app_name</title>
  <script type="text/javascript">
    'use strict';
    var Ezer= {};
    Ezer.get= { $gets };
    Ezer.parm= location.hash.split(',');
    Ezer.fce= {};
    Ezer.str= {};
    Ezer.obj= {}; 
    Ezer.root= '$app';
    Ezer.app_root= '$app_root';
    Ezer.version= '{$EZER->version}';
    Ezer.options= { $options_txt
    };
    Ezer.browser= '$browser';
    Ezer.platform= '$platform';
    Ezer.ua= '$ua';
    Ezer.konst= { $const_txt
    };
    Ezer.stop= function(status) {
      // ok:hodnota nebo ko:chyba
      top.postMessage(status,'$post_server');
    }
  </script>
  $head
$head_jush
</head>
__EOD;
  $html_footer.= <<<__EOD
</html>
__EOD;
  // definice možných HTML template stránky
  $version= "title='jádro {$EZER->version}'";
  switch ($ezer_template) {
  case 'IE':
# ------------------------------------------------------------------------------- HTML IE
# template pro zobrazení zprávy o nepodpoře IE
$template= <<<__EOD
$html_header
<body id="body">
<!-- menu a submenu -->
IE!!!
  <div id='horni' class="MainBar">
    <div id="appl" $version>$title_right</div>
    <div id='logo'>
      <img class="StatusIcon" id="StatusIcon_server" src="./$ezer_root/img/+logo.gif" />
    </div>
    <ul id="menu" class="MainMenu"></ul>
    <ul id="submenu" class="MainTabs"><li id="_help" style="display:block;float:right">HELP</li></ul>
  </div>
<!-- upozornění -->
  <div id="login" style="display:block;enabled:false;">
    <div id="login_1">
      <h1>Přihlášení ...</h1>
      <div class="login_a">
        Tuto aplikaci je možné používat v jakémkoliv prohlížeči respektujícím mezinárodní
        standardy - například
        <a href='https://www.google.com/intl/cs/chrome/browser/'>Chrome</a>,
        <a href='http://www.mozilla.org/cs/firefox/new/'>FireFox</a>,
        <a href='http://www.opera.com'>Opera</a> nebo
        <a href='http://www.apple.com/safari'>Safari</a>.
        <br><br><br><br>
        Prohlížeč Microsoft Internet Explorer pro práci v této aplikace bohužel používat nelze.
      </div>
    </div>
    <div id="login_2">
      <h1 style='text-align:right'>... informace</h1>
      <div class="login_a">
        $info
      </div>
    </div>
  </div>
<!-- paticka -->
  <div id="dolni">
    <div id="status_bar" style="width:100%;height:16px;padding: 1px 0pt 0pt;"></div>
  </div>
<!-- konec -->
</body>
$html_footer
__EOD;
    break;
  case 'menu':
# template pro zobrazení Ezer.MenuMain jako hlavního objektu aplikace
$dolni= /*$xtrace ? '' :*/ " style='height:0'";
$dbg_script= isset($_SESSION[$ezer_root]['dbg_script'])
  ? trim($_SESSION[$ezer_root]['dbg_script'])
  : "echo('Ahoj!');";
$debugger= isset($js_options->dbg) ? <<<__EOD
    <form action="" method="post" enctype="multipart/form-data" id="form">
      <textarea id="dbg" name='query' class='sqlarea jush-sql' spellcheck='false' wrap='off'
      >$dbg_script</textarea>
      <script type='text/javascript'>focus(document.getElementsByTagName('textarea')[0]);</script>
    </form>
__EOD
 : '';
// ------------------------------------------------------------==> template aplikace
$template= <<<__EOD
$html_header
<body id="body" x="1" x-ms-format-detection="none">
<!-- menu a submenu -->
  <div id="maskContinue" class="mask3" style='z-index:2011'></div>
  <div id='horni' class="MainBar">
    <div id="appl" $version>$title_right</div>
    <div id='logo'>
      <button id='logoContinue' style='display:none;outline:3px solid orange;z-index:2011;margin:7px'>
        continue</button>
      <img class="StatusIcon" id="StatusIcon_idle" src="$app/img/-logo.gif" />
      <img class="StatusIcon" id="StatusIcon_server" src="$app/img/+logo.gif" />
    </div>
    <ul id="menu" class="MainMenu"></ul>
    <div id="submenu" class="MainTabs">
      <span id="_help"><a>HELP<sub>&hearts;</sub></a></span>
    </div>
    <div id='ajax_bar3'></div>
  </div>
<!-- pracovní plocha -->
  <div id="work">
    <div id="tip" class="tip"></div>
    <!-- login -->
    <div id="login" style="display:none">
      <div id="login_1" class="$css_login">
        <h1>Přihlášení ...</h1>
        <div class="login_a">
          $login
        </div>
      </div>
      <div id="login_2" class="$css_login">
        <h1 style='text-align:right'>... informace</h1>
        <div class="login_a">
          $info
        </div>
      </div>$chngs
    </div>
  </div>
<!-- pata -->
  <div id="status_bar">
    <div id='status_left'></div>
    <div id='status_center'>zpráva</div>
    <div id='status_right'></div>
  </div>
  <div id="popup_mask3"></div>
  <div id="top_mask3">
    <div id="popup3">
      <div class="pop_head"></div>
      <div class="pop_body"></div>
      <div class="pop_tail"></div>
    </div>
  </div>
  <div id="dolni"$dolni>
    <div id="trace">
      $debugger
      <pre id="kuk"></pre>
    </div>
  </div>
  <div id="paticka"><div id="warning"></div><div id="error"></div></div>
  <div id="report" class="report"></div>
  <input id="drag" type="input" />
  <!-- form><input id="drag" type="button" /></form>
  <form id="drag_form" class="ContextMenu" style="display:none;position:absolute;width:200px">
    <input id="drag_title" type="text" style="float:right;width:165px" />
    <div style="padding:3px 0 0 2px;width:30px">title:</div>
  </form -->
  <div id="wait_mask"><div id="wait" onclick="waiting(0);"></div></div>
<!-- konec -->
</body>
$html_footer
__EOD;
    break;
  case 'panel':
# ------------------------------------------------------------------------------- HTML panel
# template pro zobrazení Ezer.PanelPlain jako hlavního objektu aplikace
$template= <<<__EOD
$html_header
<body id="body">
<!-- bez menu a submenu -->
  <div id='horni' class="MainBar">
    <div id="StatusIcon">$title</div>
  </div>
  <!--div id='ajax_bar'></div-->
<!-- pracovní plocha -->
  <div id="stred" style="top:35px">
    <!-- div id="shield"></div -->
    <div id="work"></div>
  </div>
<!-- paticka -->
  <div id="dolni">
    <div id="warning"></div>
    <div id="kuk_err"></div>
    <div id="paticka">
      <div id="error"></div>
    </div>
    <div id="status_bar" style="width:100%;height:16px;padding: 1px 0pt 0pt;">
      <div id='status_left' style="float:left;"></div>
      <div id='status_center' style="float:left;">zpráva</div>
      <div id='status_right' style="float:right;"></div>
    </div>
    <div id="trace">
      $debugger
      <pre id="kuk"></pre>
    </div>
  </div>
<!-- konec -->
</body>
$html_footer
__EOD;
    break;
# ------------------------------------------------------------------------------- HTML user
# uživatelský template hlavního objektu aplikace
  case 'user':
    if ( !$ip_ok ) session_destroy();
    $template= strtr($pars->template_body,array(
      '%header'=>$html_header,'%login'=>$login,'%info'=>$info,'%chngs'=>$chngs,
      '%html_footer'=>$html_footer));
    break;
  default:
# ------------------------------------------------------------------------------- HTML prázdný
# template pro zobrazení Ezer.PanelPlain jako hlavního objektu aplikace
$template= <<<__EOD
$html_header
<body>
</body>
$html_footer
__EOD;
    break;
  }
  echo $template;
  return 1;
//   echo nl2br(htmlentities($template));
}
# ----------------------------------------------------------------------------------------- root_inc
# skeleton hlavní konfigurace aplikace
# parametry označené * jsou ve formátu pole - první hodnota je platná pro server, druhá pro local
#  *$db                 -- jméno hlavní databáze aplikace s tabulkami: _cis, _todo, _touch, _track
#  *$dbs                -- údaje pro přístup k databázím
#                          databáze => (,server,username,userpass,kódování,[jméno databáze])
#                          musí být obsažena databáze ezer_system s tabulkou _user
#   $tracking           -- tabulka pro logování změn
#   $tracked            -- tabulky s logovanými změnami (seznam zacina a konci carkou)
#  *$path_root          -- kořen ve filesystemu
#  *$path_pspad         -- cesta k editoru PSPad
# vstup/výstupní globální proměnné
#   $ezer_system        -- jméno databáze s tabulkou _user
function root_inc3($db,$dbs,$tracking,$tracked,$path_root,$path_pspad) {
  global $ezer_root,$ezer_local,
    $ezer_path_root,$sess_save_path,$ezer_path_appl,$ezer_path_libr,$ezer_path_docs,
    $ezer_path_code,$ezer_path_serv,$ezer_path_svn,$ezer_path_todo,$ezer_path_pspad,
    $mysql_db,$mysql_dbi,$ezer_db,$ezer_system,
    $ezer_mysql_cp,$ezer_html_cp,$ezer_sylk_cp,
    $mysql_db_track,$mysql_tracked,
    $EZER;
  // nastavení verze jádra na 'toto'
  $EZER->version= "ezer".EZER_version; //'ezer3';
  //$ezer_root= $ezer_root ?: $ezer_root0; - vzniklo 14.4.2015
  // nastavení databází
  $sada= $ezer_local ? 1 : 0;
  $mysql_dbi= $db[$sada];
  $mysql_db= isset($dbs[$sada][$db[$sada]][5]) ? $dbs[$sada][$db[$sada]][5] : $db[$sada];
  $ezer_db= $dbs[$sada];
  $ezer_sdb= $dbs[$sada];
  $ezer_system= $ezer_system ? $ezer_system : 'ezer_system';
  $ezer_system= isset($dbs[$sada][$ezer_system][5]) ? $dbs[$sada][$ezer_system][5] : $ezer_system;
  $mysql_db_track= $tracking;
  $mysql_tracked= $tracked;  // seznam začíná a končí čárkou
  // nastavení kódování
  $ezer_mysql_cp= 'UTF8';
  $ezer_html_cp=  'UTF-8';
  $ezer_sylk_cp=  'windows-1250';
  // cesty
//   $ezer_path_root= $path_root[$sada];
  if ( isset($_SESSION[$ezer_root]['abs_root']) ) {
    $ezer_path_root= $_SESSION[$ezer_root]['abs_root'];
  }
  else {
    $ezer_path_root= $_SERVER['DOCUMENT_ROOT'].$_SESSION[$ezer_root]['app_path'];
  }
//                                                 echo("<hr>ezer_path_root=$ezer_path_root ($ezer_root)");
  $sess_save_path= "$ezer_path_root/sess";
  $ezer_path_appl= "$ezer_path_root/$ezer_root";
  $ezer_path_libr= "$ezer_path_root/$ezer_root";
  $ezer_path_docs= "$ezer_path_root/docs";
  $ezer_path_code= "$ezer_path_appl/code";
  $ezer_path_serv= "$ezer_path_root/{$EZER->version}/server";
  $ezer_path_svn= null;
  $ezer_path_todo= "$ezer_path_root/wiki";
  $ezer_path_pspad= $path_pspad[$sada];
  // parametrizace standardních modulů
  if ( !isset($EZER->options) ) $EZER->options= (object)array();
  $EZER->options->root= $ezer_root;
  $EZER->options->app=  $ezer_root;
  $EZER->options->index= "$ezer_root.php";
  $EZER->options->docs_ref= "docs";
  if ( !isset($EZER->activity) ) $EZER->activity= (object)array();
  $EZER->activity->touch_limit= 50; // počet dotyků (ae_hits) po kterých je uskutečněn zápis do _touch
  $EZER->activity->colors= "80:#f0d7e4,40:#e0d7e4,20:#dce7f4,0:#e7e7e7";  // viz system.php::sys_table
  // knihovní moduly
  require_once("$ezer_path_root/{$EZER->version}/ezer2_fce.php");
}
?>
