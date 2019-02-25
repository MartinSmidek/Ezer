<?php # (c) 2007-2018 Martin Smidek (martin@smidek.eu)

/** 
 * upgrade do verze Ezer3.1
 * ------------------------
 * - substituce mysql_* na pdo_*
 * - založení ezer_pdo.php pro funkce: ezer_connect, pdo_*
 * - úprava funkce Excel5 pro PHPExcel 1.8.1
 */

# ================================================================================================== ROOT
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
#   $const: object      -- definice hodnot nedefinovaných konstant
# globální proměnné na vstupu
#   $ezer_local         -- bool: ladící běh
#   $ezer_path_serv     -- string: cesta ke skriptům
# -------------------------------------------------------------------------------------------------- root_php
function root_php($app,$app_name,$welcome,$skin,$options,$js,$css,$pars=null,$const=null,$start_sess=true) {
  global $EZER, $app_root, $ezer_root, $ezer_path_serv, $ezer_path_docs, $ezer_local,
    $gc_maxlifetime, $http;
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
  if ( $app_root ) chdir("..");
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
    ini_set('session.gc_maxlifetime',$gc_maxlifetime);
    session_start();                      // defaultní práce se session, $session=='php'
    $_SESSION['gc_maxlifetime']= $gc_maxlifetime;
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
      $watch_lock= @file_get_contents("$ezer_root/code/$ezer_root.key");
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
        $res= pdo_query($qry);
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
          <input id='watch_try' name='watch_try' type='hidden' value='nic' />
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
  $kontakt= $pars->contact ? $pars->contact
    : " V případě zjištění problému nebo potřeby konzultace mi prosím:<br/>
       <ul><li>napište na mail&nbsp;<a href='mailto:{$EZER->options->mail}{$EZER->options->mail_subject}'>{$EZER->options->mail}</a></li>"
     . ($EZER->options->phone ? "<li>případně zavolejte&nbsp;{$EZER->options->phone}</li>" : '')
     . ($EZER->options->skype ? "<li>nebo použijte Skype&nbsp;<a href='skype:{$EZER->options->skype}?chat'>{$EZER->options->skype}</a></li>" : '')
     . "</ul>Za spolupráci děkuje <b>{$EZER->options->author}</b>";
  $type= $ip_ok ? (is_array($welcome) ? $welcome[0] : $welcome) : 'kontakt';
  switch ($type) {
  case 'kontakt':
    $info= $kontakt;
    $info= "<div class='login_a_msg'><br>$info</div>";
    break;
  case 'test':                          // zobraz stav SESSION, pokud je url parametr
    $info= isset($_GET['session'])
      ? "<div class='dbg'>".debugx($_SESSION,'$_SESSION:')."</div>"
      : "...&amp;session=1 zobrazí \$_SESSION<br/><br/>".
        (is_array($welcome) ? $welcome[1] : $kontakt);
    break;
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
        $('login_2').getElements('a').each(function(el) {
          if ( el.href && el.href.substr(0,7)=='help://' ) {
            el.addEvents({
              click: function(ev) {
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
    if ( !$ezer_local ) define('MINIFY_BASE_DIR',$ezer_path_serv);
    require_once('ezer3.1/server/licensed/minify.php');
    $minifyCSS= new Minify(TYPE_CSS);
    $minifyJS= new Minify(TYPE_JS);
    $minifyCSS->addFile($css);
    $minifyJS->addFile($js);
    file_put_contents("$ezer_root.css",$css= $minifyCSS->combine());
    file_put_contents("$ezer_root.js",$js= $minifyJS->combine());
    // header pro běh bez laděni
    $head.= <<<__EOD
    <script src="$ezer_root.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="$ezer_root.css" type="text/css" media="screen" charset="utf-8" />
__EOD;
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
      list($href,$id)= explode('=',"$x=");
      if ( $id ) $id= "id='$id'";
      $head.= "\n  <link $id rel='stylesheet' href='$href' type='text/css' media='screen' charset='utf-8' />";
    }
  }
  // definice povinného začátku a konce HTML stránky
  $html_footer= '';
  //$html_base= $app_root ? "\n  <base href=\"http://".$_SERVER["HTTP_HOST"].'">' : '';
  $html_base= $app_root ? "\n  <base href=\"$http://$app_root\">" : '';
  $html_header= '';
//   $html_header.= "\xEF\xBB\xBF";    // DOM pro UTF-8
  $html_header.= <<<__EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="cs" dir="ltr">
<head>$html_base
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=9" /> $meta_link
  <link rel="shortcut icon" href="./$ezer_root/img/$favicon" />
  <title>$app_name</title>
  <script type="text/javascript">
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
  : "set_trace('m',1,'init,set,key');";
$debugger= isset($js_options->dbg) ? <<<__EOD
    <form action="" method="post" enctype="multipart/form-data" id="form">
      <textarea id="dbg" name='query' class='sqlarea jush-sql' spellcheck='false' wrap='off'
      >$dbg_script</textarea>
      <script type='text/javascript'>focus(document.getElementsByTagName('textarea')[0]);</script>
    </form>
__EOD
 : '';
$template= <<<__EOD
$html_header
<body id="body" x="1" x-ms-format-detection="none">
<!-- menu a submenu -->
  <div id='horni' class="MainBar">
    <div id="appl" $version>$title_right</div>
    <div id='logo'>
      <button id='logoContinue' style='display:none;outline:3px solid orange;'>continue</button>
      <img class="StatusIcon" id="StatusIcon_idle" src="./$app/img/-logo.gif" />
      <img class="StatusIcon" id="StatusIcon_server" src="./$app/img/+logo.gif" />
    </div>
    <ul id="menu" class="MainMenu"></ul>
    <ul id="submenu" class="MainTabs">
      <li id="_help" style="display:block;float:right"><a>HELP<sub>&hearts;</sub></a></li>
    </ul>
  </div>
  <div id='ajax_bar'></div>
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
<!-- pracovní plocha -->
  <div id="stred">
    <!-- div id="shield"></div -->
    <div id="work"></div>
  </div>
  <div id="uzivatel"></div>
<!-- paticka -->
  <div id="paticka">
    <div id="warning"></div>
    <div id="kuk_err"></div>
    <div id="error" style="margin:-30px 0px 0px;"></div>
  </div>
  <div id="dolni"$dolni>
    <div id="status_bar">
      <div id='status_left' style="float:left;"></div>
      <div id='status_center' style="float:left;">zpráva</div>
      <div id='status_right' style="float:right;"></div>
    </div>
    <div id="trace">
      $debugger
      <pre id="kuk"></pre>
    </div>
  </div>
  <div id="report" class="report"></div>
  <form><input id="drag" type="button" /></form>
  <form id="drag_form" class="ContextMenu" style="display:none;position:absolute;width:200px">
    <input id="drag_title" type="text" style="float:right;width:165px" />
    <div style="padding:3px 0 0 2px;width:30px">title:</div>
  </form>
  <div id="wait_mask">
    <div id="wait" onclick="waiting(0);"></div>
  </div>
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
# -------------------------------------------------------------------------------------------------- root_inc
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
function root_inc($db,$dbs,$tracking,$tracked,$path_root,$path_pspad) {
  global $ezer_root,$ezer_local,
    $ezer_path_root,$sess_save_path,$ezer_path_appl,$ezer_path_libr,$ezer_path_docs,
    $ezer_path_code,$ezer_path_serv,$ezer_path_svn,$ezer_path_todo,$ezer_path_pspad,
    $mysql_db,$ezer_db,$ezer_system,
    $ezer_mysql_cp,$ezer_html_cp,$ezer_sylk_cp,
    $mysql_db_track,$mysql_tracked,
    $EZER;
  // nastavení verze jádra na 'toto'
  $EZER->version= 'ezer3.1';
  //$ezer_root= $ezer_root ?: $ezer_root0; - vzniklo 14.4.2015
  // nastavení databází
  $sada= $ezer_local ? 1 : 0;
//   $mysql_db= $db[$sada];
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
# -------------------------------------------------------------------------------------------------- root_svn
# zjistí svn-verzi běžící aplikace pro $app=1 nebo jádra pro $app=0
function root_svn($app=0) {
  global $EZER, $ezer_root, $ezer_path_root;
  $verze= "?";
  if ( defined("SQLITE3_ASSOC") ) {
    $sub_root= $_SESSION[$ezer_root]['app_root'] ? "/$ezer_root": '';
    $sub= $app ? ($sub_root ?: '') : "/$EZER->version";
    $db_file= "$ezer_path_root$sub/.svn/wc.db";
    if ( file_exists($db_file) ) {
      $db=@ new SQLite3($db_file);
      if ( $db ) {
        $verze=@ $db->querySingle("SELECT MAX(revision) from NODES");
      }
    }
  }
  return $verze;
}
# ------------------------------------------------------------------------------------- ezer browser
# identifikace prohlížeče a operačního systému
function ezer_browser(&$abbr,&$version,&$platform,$agent=null ) {
  if ( !$agent ) $agent= $_SERVER['HTTP_USER_AGENT'];
  // identifikace prohlížeče
  if     ( preg_match('/Edge\/([\d\.])*/',   $agent,$m) ) { $abbr='EG'; $version= $m[0]; }
  elseif ( preg_match('/MSIE\/([\d\.])*/',   $agent,$m) ) { $abbr='IE'; $version= $m[0]; }
  elseif ( preg_match('/Vivaldi\/([\d\.])*/',$agent,$m) ) { $abbr='VI'; $version= $m[0]; }
  elseif ( preg_match('/Opera\/([\d\.])*/',  $agent,$m) ) { $abbr='OP'; $version= $m[0]; }
  elseif ( preg_match('/Firefox\/([\d\.])*/',$agent,$m) ) { $abbr='FF'; $version= $m[0]; }
  elseif ( preg_match('/Chrome\/([\d\.])*/', $agent,$m) ) { $abbr='CH'; $version= $m[0]; }
  elseif ( preg_match('/Safari\/([\d\.])*/', $agent,$m) ) { $abbr='SF'; $version= $m[0]; }
  else { $abbr='?'; $version= '?/?'; }
  // identifikace platformy prohlížeče: Android => Ezer.client == 'A'
  $platform =          // x11 hlásí Chrome při vzdáleném ladění (chrome://inspect/#devices)
	preg_match('/Windows Phone|Windows Mobile/i',$agent)      ? 'P' : (
	preg_match('/Android/i',$agent)                           ? 'A' : (
	preg_match('/iPad|iPhone/i',$agent)                       ? 'I' : (
	preg_match('/linux/i',$agent)                             ? 'L' : (
	preg_match('/macintosh|Mac OS X|Power_PC|PPC/i',$agent)   ? 'M' : (
	preg_match('/Windows|win32|Windows NT/i',$agent)          ? 'W' : '?'
  )))));
}
# ----------------------------------------------------------------------------------- doc chngs_show
# type = a-aplikace, g-group, k-kernel
function doc_chngs_show($type='ak',$days=30,$app_name='') { trace();
  global $ezer_db, $ezer_root;
  list($grp_name)= preg_split("/[\s-_]/",$app_name);
  $lines= array();
  $s2u= function($d) { return substr($d,8,2).'.'.substr($d,5,2).'.'.substr($d,0,4); };
  $db_name= function($db) use ($ezer_db) {
    // pro 1. databázi tj. .main.
    if ( $db=='.main.' ) {
      foreach ( $ezer_db as $db1=>$desc) { $db= $db1; break;  }
    }
    $name= (isset($ezer_db[$db][5]) && $ezer_db[$db][5]!='') ? $ezer_db[$db][5] : $db;
    return $name;
  };
  $header= function($d,$w,$a='') use ($s2u) {
    $d= $s2u($d);
    $w= trim($w);
    return "<span class='chng_day' title='$a'>$d $w</span>";
  };
  $get_help= function($db='.main.',$level='a',$abbr) use (&$lines,$ezer_db,$days,$header) {
    if ( $db=='.main.' || isset($ezer_db[$db]) ) {
      ezer_connect($db);
      $qh= "SELECT datum, version, name, help FROM /*$db*/ _help
            WHERE kind='v' AND SUBDATE(NOW(),$days)<=datum";
      $rh= mysql_qry($qh);
      while ( $rh && ($h= pdo_fetch_object($rh)) ) {
        $n= $h->name;
        if ( $n )
          list($n)= array_reverse(explode('|',$h->name));
        else
          $n= $level=='k' ? 'Ezer' : ' ';
        $hdr= $header($h->datum,$n,"oprava $abbr");
        $version= str_pad($h->version,10,'0',STR_PAD_LEFT);
        $tit= addslashes($h->help);
        $lines[]= "$h->datum $version"
                . "<div class='chng'>$hdr<span class='chng_hlp' title='$tit'>$h->help</span></div>";
      }
    }
  };
  // zhromáždění změn z trojice databází s tabulkou _help
  if ( strstr($type,'k') !== false && $db_name('ezer_kernel')!=$db_name('.main.') ) {
    // pro ezer_kernel pouze, pokud je odlišnou databází od hlavní databáze
    $get_help('ezer_kernel','k',"Ezer");
  }
  if ( strstr($type,'a') !== false ) {
    if ( isset($_SESSION[$ezer_root]['group_db']) ) {
      $get_help('ezer_group','g',$grp_name);
    }
    $get_help('.main.','a',$ezer_root);
  }
  // přidání změn z _todo
  $cond= strstr($type,'a')===false ? "cast=1" : "1";
  $cond.= strstr($type,'k')===false ? " OR cast!=1" : "";
  ezer_connect('.main.');
  $qh= "SELECT kdy_skoncil, zprava, zkratka, abbr AS kdo, kdy_zadal FROM _todo
        JOIN _cis ON druh='s_todo_cast' AND data=cast
        LEFT JOIN _user ON id_user=kdo_zadal
        WHERE kdy_skoncil!='0000-00-00' AND SUBDATE(NOW(),$days)<=kdy_skoncil AND ($cond)";
  $rh= mysql_qry($qh);
  while ( $rh && ($h= pdo_fetch_object($rh)) ) {
    $who= "požadavek {$h->kdo} ".$s2u($h->kdy_zadal);
    $hdr= $header($h->kdy_skoncil,$h->zkratka,$who);
    $tit= addslashes($h->zprava);
    $lines[]= "$h->kdy_skoncil 00:00:00 9876543210"
            . "<div class='chng'>$hdr<span class='chng_hlp' title='$tit'>$h->zprava</span></div>";
  }
  // redakce
  rsort($lines);
  foreach($lines as $i=>$line) $lines[$i]= substr($line,30);
  $html= implode('<br>',$lines);
  return $html;
}
/** ================================================================================================ SYSTEM */
# knihovna funkcí pro moduly server, compiler, reference
# -------------------------------------------------------------------------------------------------- fce_error
# $send_mail může obsahovat doplňkové informace zaslané správci aplikace mailem
function fce_error ($msg,$send_mail='') { trace();
  global $ezer_root;
  if ( $send_mail ) {
    // poslat mail
    send_mail("Ezer/$ezer_root ERROR:$msg",$send_mail,"","",'error');
  }
  throw new Exception($msg);
  return false;
}
# -------------------------------------------------------------------------------------------------- fce_warning
# $send_mail může obsahovat doplňkové informace zaslané správci aplikace mailem
function fce_warning ($msg,$send_mail='') { trace();
  global $warning, $ezer_root;
  $warning.= "<br>$msg";
  if ( $send_mail ) {
    // poslat mail
    send_mail("Ezer/$ezer_root WARNING:$msg",$send_mail,"","",'warning');
  }
  return false;
}
# -------------------------------------------------------------------------------------------------- send_error
# pošle chybovou hlášku správci aplikace mailem
function send_error ($msg) { trace();
  global $ezer_root, $USER;
  $user= $USER->abbr;
  $body= "Error message sent at ".date('j.n.Y H:i:s').", user=$user<hr>$msg";
  send_mail("Ezer/$ezer_root ERROR",$body,"","",'error');
  return false;
}
# -------------------------------------------------------------------------------------------------- set_limits
# nastaví limity pro upload (MB,sec)
function set_limits ($max_size=10,$max_time=300) { trace();
  ini_set('upload_max_filesize', "{$max_size}M");
  ini_set('post_max_size', "{$max_size}M");
  ini_set('max_input_time', $max_time);
  ini_set('max_execution_time', $max_time);
  return true;
}
# -------------------------------------------------------------------------------------------------- send_mail
# pošle systémový mail, pokud není určen adresát či odesílatel jde o mail správci aplikace
# $to může být seznam adres oddělený čárkou
function send_mail($subject,$html,$from='',$to='',$fromname='') { trace();
  global $ezer_path_serv, $ezer_root, $EZER;
  $from= $from ? $from : ($EZER->smtp->from ? $EZER->smtp->from : $EZER->options->mail);
  $fromname= $fromname ? $fromname : $ezer_root;
  $to= $to ? $to : $EZER->options->mail;
  // poslání mailu
  $phpmailer_path= "$ezer_path_serv/licensed/phpmailer";
  require_once("$phpmailer_path/class.phpmailer.php");
  // napojení na mailer
  $mail= new PHPMailer;
  $mail->SetLanguage('cs',"$phpmailer_path/language/");
  $mail->IsSMTP();
  $mail->Host= isset($EZER->smtp->host) ? $EZER->smtp->host : "192.168.1.1";
  $mail->Port= isset($EZER->smtp->port) ? $EZER->smtp->port : 25;
  $mail->CharSet = "utf-8";
  $mail->From= $from;
  $mail->FromName= $fromname;
  foreach (explode(',',$to) as $to1) {
    $mail->AddAddress($to1);
  }
  $mail->Subject= $subject;
  $mail->Body= $html;
  $mail->IsHTML(true);
//   $mail->Mailer= "smtp";
  // pošli
  $ok= $mail->Send();
                                                display("send_mail=$ok,".$mail->ErrorInfo);
  if ( !$ok )
    fce_warning("Selhalo odeslání mailu: $mail->ErrorInfo");
  else {
                                                $mail->Subject= $mail->Body= $mail->language= "---";
                                                debug($mail,"send_mail(..,..,$from,$to)=$ok");
  }
  return $ok;
}
# -------------------------------------------------------------------------------------------------- recursive_mkdir
// vytvoří adresář
function recursive_mkdir($path, $sep="\\", $mode = 0777) {
  $dirs= explode($sep, $path);
  $count= count($dirs);
  $path= '';
  for ($i= 0; $i < $count; ++$i) if ( $dirs[$i] ) {
    $path.= strchr($dirs[$i],':') ? $dirs[$i] : $sep . $dirs[$i];
    if (!is_dir($path) && !mkdir($path, $mode)) {
      return false;
    }
  }
  return true;
}
# -------------------------------------------------------------------------------------- kolik_1_2_5
# výběr správného tvaru slova podle množství a tabulky tvarů pro 1,2-4,5 a více
# např. kolik_1_2_5(dosp,"dospělý,dospělí,dospělých")
function kolik_1_2_5($kolik,$tvary) {
  $tvar= explode(',',$tvary);
  return "$kolik ".($kolik>4 ? $tvar[2] : ($kolik>1 ? $tvar[1] : ($kolik>0 ? $tvar[0] : $tvar[2])));
}
# -------------------------------------------------------------------------------------------------- lorem_ipsum
# vrátí požadovaný počet odstavců výplňového textu
function lorem_ipsum($repeat=1) {
  $lorem= str_repeat(
      "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt "
    . "ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco "
    . "laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in "
    . "voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat "
    . "non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. "
    ,$repeat);
  return $lorem;
}
# -------------------------------------------------------------------------------------------------- display
function display ($msg) {
  global $trace;
//   $msg= win2utf($msg);
//   if ( $trace )
  $trace.= ($trace?"<div />":'').$msg;
//   else $trace= $msg;
}
# -------------------------------------------------------------------------------------------------- display_
function display_ ($msg) {
  global $trace;
//   $msg= win2utf($msg);
  $trace.= $msg;
}
# -------------------------------------------------------------------------------------------------- trace
# $note je poznámka uvedená za trasovací informací
function trace($note='',$coding='') {
  global $trace, $trace_parm;
  $time= date("H:i:s");
  $act= debug_backtrace();
  $x= ($trace ? "<br/>" : '')."$time ".call_stack($act,1).($note?" / $note":'');
  $x.= $trace_parm;
  $trace_parm= '';
  if ( $coding=='win1250' ) $x= wu($x);
  $trace.= $x;
}
# -------------------------------------------------------------------------------------------------- time_mark
# kvůli časování operací
function time_mark($msg) { trace();
}
# -------------------------------------------------------------------------------------------------- call_stack
function call_stack($act,$n,$hloubka=2,$show_call=1) { #$this->debug($act,'call_stack');
  $fce= isset($act[$n]['class'])
    ? "{$act[$n]['class']}{$act[$n]['type']}{$act[$n]['function']}" : $act[$n]['function'];
  $del= '';
  $max_string= 36;
  $args= '';
  if ( $show_call and isset($act[$n]['args']) )
  foreach ( $act[$n]['args'] as $arg ) {
    if ( is_string($arg) ) {
      $arg= mb_substr(htmlspecialchars($arg,ENT_NOQUOTES,'UTF-8'),0,$max_string).(mb_strlen($arg)>$max_string?'...':'');
    }
    $typ= gettype($arg);
    $val= '';
    switch ( $typ ) {
    case 'boolean': case 'integer': case 'double': case 'string': case 'NULL':
      $val= $arg; break;
    case 'array':
      $val= count($arg); break;
    case 'object':
      $val= get_class($arg); break;
    }
    $args.= "$del<u>$typ</u>:$val";
    $del= ',';
  }
  $from= '';
  for ($k= $n; $k<$n+$hloubka; $k++) {
    if ( isset($act[$k]) )
    switch ( key($act[$k]) ) {
    case 'file':
      $from_file= str_replace('.php','',$act[$k]['file']);
      $from.= " &lt;&nbsp;".substr(strrchr($from_file,'\\'),1);
      $from.= "/{$act[$k]['line']}";
      break;
    case 'function':
      $from.= " &lt;&nbsp;".($act[$k]['class']?"{$act[$k]['class']}.":'').$act[$k]['function'];
      break;
    default:
      $from.= " &lt; ? ";
      break;
    }
  }
  return $show_call ? "$fce($args)$from" : $from;
}
# -------------------------------------------------------------------------------------------------- debug
# vygeneruje čitelný obraz pole nebo objektu
# pokud jsou data v kódování win1250 je třeba použít  debug($s,'s',(object)array('win1250'=>1));
# options:
#   gettype=1 -- ve třetím sloupci bude gettype(hodnoty)
function debug($gt,$label=false,$options=null) {
  global $trace, $debug_level;
  $debug_level= 0;
  $html= ($options && isset($options->html)) ? $options->html : 0;
  $depth= ($options && isset($options->depth)) ? $options->depth : 64;
  $length= ($options && isset($options->length)) ? $options->length : 64;
  $win1250= ($options && isset($options->win1250)) ? $options->win1250 : 0;
  $gettype= ($options && isset($options->gettype)) ? 1 : 0;
  if ( is_array($gt) || is_object($gt) ) {
    $x= debugx($gt,$label,$html,$depth,$length,$win1250,$gettype);
  }
  else {
//     $x= $html ? htmlentities($gt) : $gt;
    $x= $html ? htmlspecialchars($gt,ENT_NOQUOTES,'UTF-8') : $gt;
    $x= "<table class='dbg_array'><tr>"
      . "<td valign='top' class='title'>$label</td></tr><tr><td>$x</td></tr></table>";
  }
  if ( $win1250 ) $x= wu($x);
//   $x= strtr($x,'<>','«»'); //$x= str_replace('{',"'{'",$x);
  $trace.= $x;
  return $x;
}
function debugx(&$gt,$label=false,$html=0,$depth=64,$length=64,$win1250=0,$gettype=0) {
  global $debug_level;
  if ( $debug_level > $depth ) return "<table class='dbg_over'><tr><td>...</td></tr></table>";
  if ( is_array($gt) ) {
    $debug_level++;
    $x= "<table class='dbg_array'>";
    $x.= $label!==false
      ? "<tr><td valign='top' colspan='".($gettype?3:2)."' class='title'>$label</td></tr>" : '';
    foreach($gt as $g => $t) {
      $x.= "<tr><td valign='top' class='label'>$g</td><td>"
      . debugx($t,NULL,$html,$depth,$length,$win1250,$gettype) //TEST==1 ? $t : htmlspecialchars($t)
      .($gettype ? "</td><td>".gettype($t) : '')                      //+typ
      ."</td></tr>";
    }
    $x.= "</table>";
    $debug_level--;
  }
  else if ( is_object($gt) ) {
    $debug_level++;
    $x= "<table class='dbg_object'>";
    $x.= $label!==false ? "<tr><td valign='top' colspan='".($gettype?3:2)."' class='title'>$label</td></tr>" : '';
//     $obj= get_object_vars($gt);
    $len= 0;
    foreach($gt as $g => $t) {
      $len++;
      if ( $len>$length ) break;
//       if ( is_string($t) ) {
//         $x.= "<td>$g:$t</td>";
//       }
//       if ( $g=='parent' ) {
//         $td= $t==null ? "<td class='label'>nil</td>" : (
//           is_object($t) && isset($t->id) ? "<td class='label'>{$t->id}</td>" : (
//           is_string($t) ? "<td>$t</td>" :
//           "<td class='label'>?</td>"));
//         $x.= "<tr><td class='dbg_over'>$g:</td>$td</tr>";
//       }
//       else {
        $x.= "<tr><td valign='top' class='label'>$g:</td><td>"
        . debugx($t,NULL,$html,$depth,$length,$win1250,$gettype) //TEST==1 ? $t : htmlspecialchars($t)
        .($gettype ? "</td><td>".gettype($t) : '')                      //+typ
        ."</td></tr>";
//       }
    }
    $x.= "</table>";
    $debug_level--;
  }
  else {
    if ( is_object($gt) )
      $x= "object:".get_class($gt);
    else
//       $x= $html ? htmlentities($gt) : $gt;
      $x= $html ? htmlspecialchars($gt,ENT_NOQUOTES,'UTF-8') : $gt;
//       if ( is_string($x) ) $x= "'$x'";
  }
  return $x;
}
# ---------------------------------------------------------------------------------------------- PHP
# ASK test PHP kódu
# Příklad: echo(ask('PHP','global $USER;display("ok");debug($USER);return $USER->options->email;'));
# (používat výhradně pro účely ladění v debugeru! - eval nevrací paměť - viz informace na webu)
function PHP($expr) {
  global $USER,$EZER;
  display($expr);
//   debug($USER,'$USER');
//   debug($EZER,'$EZER');
  $fce= eval($expr);
  return $fce;
}
/*** =============================================================================================== MySQL */
# -------------------------------------------------------------------------------------------------- select
# navrácení hodnoty jednoduchého dotazu
# pokud $expr obsahuje čárku, vrací pole hodnot, pokud $expr je hvězdička vrací objekt
# příklad 1: $id= select("id","tab","x=13")
# příklad 2: list($id,$x)= select("id,x","tab","x=13")
function select($expr,$table,$cond=1,$db='.main.') {
  if ( strstr($expr,",") ) {
    $result= array();
    $qry= "SELECT $expr FROM $table WHERE $cond";
    $res= mysql_qry($qry,0,0,0,$db);
    if ( !$res ) fce_error(wu("chyba funkce select:$qry/".pdo_error()));
    $result= pdo_fetch_row($res);
  }
  elseif ( $expr=='*' ) {
    $qry= "SELECT * FROM $table WHERE $cond";
    $res= mysql_qry($qry,0,0,0,$db);
    if ( !$res ) fce_error(wu("chyba funkce select:$qry/".pdo_error()));
    $result= pdo_fetch_object($res);
  }
  else {
    $result= '';
    $qry= "SELECT $expr AS _result_ FROM $table WHERE $cond";
    $res= mysql_qry($qry,0,0,0,$db);
    if ( !$res ) fce_error(wu("chyba funkce select:$qry/".pdo_error()));
    $o= pdo_fetch_object($res);
    $result= $o->_result_;
  }
//                                                 debug($result,"select");
  return $result;
}
# -------------------------------------------------------------------------------------------------- select1
# navrácení hodnoty jednoduchého dotazu - $expr musí vracet jednu hodnotu
function select1($expr,$table,$cond=1,$db='.main.') {
  $result= '';
  $qry= "SELECT $expr AS _result_ FROM $table WHERE $cond";
  $res= mysql_qry($qry,0,0,0,$db);
  if ( !$res ) fce_error(wu("chyba funkce select1:$qry/".pdo_error()));
  $o= pdo_fetch_object($res);
  $result= $o->_result_;
  return $result;
}
# -------------------------------------------------------------------------------------------------- select_object
# navrácení hodnot jednoduchého jednoznačného dotazu jako objektu (funkcí pdo_fetch_object)
function select_object($expr,$table,$cond=1,$db='.main.') {
  $qry= "SELECT $expr FROM $table WHERE $cond";
  $res= mysql_qry($qry,0,0,0,$db);
  if ( !$res ) fce_error(wu("chyba funkce select_object:$qry/".pdo_error()));
  $result= pdo_fetch_object($res);
  return $result;
}
# -------------------------------------------------------------------------------------------------- query
# provedení MySQL dotazu
function query($qry,$db='.main.') {
  $res= mysql_qry($qry,0,0,0,$db);
  if ( !$res ) fce_error(wu("chyba funkce query:$qry/".pdo_error()));
  return $res;
}
# -------------------------------------------------------------------------------------------------- sql_query
# provedení MySQL dotazu
function sql_query($qry,$db='.main.') {
  $obj= (object)array();
  $res= mysql_qry($qry,0,0,0,$db);
  if ( $res ) {
    $obj= pdo_fetch_object($res);
  }
  return $obj;
}
/** ================================================================================================ OSVĚDČENÉ FUNKCE */
# -------------------------------------------------------------------------------------------------- dph_koeficienty
# vrátí tabulku koeficientů DPH podle zákona o DPH
function dph_koeficienty() {
  return array(
     0 => 0,
     1 => 0.0909,
    15 => 0.1304,
    21 => 0.1736
  );
}
# -------------------------------------------------------------------------------------------------- test_session
# vypíše session
function test_session() {
  global $ezer_root;
  $html= "";
  $html.= "<br>session_id=".session_id()." :{$_SESSION[$ezer_root]['user_state']}";
                                                debug($_SESSION,'$_SESSION');
  return $html;
}
# -------------------------------------------------------------------------------------------------- simple_glob
/** Jednodušší náhrada funkce glob()
* @param string $mask vyhledávací maska může v názvu souboru obsahovat znak * a ?
* @return array pole obsahující všecny nalezené soubory/adresáře
* @copyright Jakub Vrána, http://php.vrana.cz
*/
function simple_glob($mask) {
    $dirname = preg_replace('~[^/]*$~', '', $mask);
    $dir = opendir(strlen($dirname) ? $dirname : ".");
    $return = array();
    if ($dir) {
        $pattern = '~^' . strtr(preg_quote($mask, '~'), array('\\*' => '.*', '\\?' => '.')) . '$~';
        while (($filename = readdir($dir)) !== false) {
            if ($filename != "." && $filename != ".." && preg_match($pattern, "$dirname$filename")) {
                $return[] = "$dirname$filename";
            }
        }
        closedir($dir);
        sort($return);
    }
    return $return;
}
# -------------------------------------------------------------------------------------------------- map_cis
# zjištění hodnot číselníku a vrácení jako překladového pole
#   array (data => $val, ...)
function map_cis($druh,$val='zkratka',$order='poradi',$db='') {
  global $mysql_db;
  $db= $db?:$mysql_db;
  $cis= array();
  $qry= "SELECT * FROM $db._cis WHERE druh='$druh' ORDER BY $order";
  $res= mysql_qry($qry);
  while ( $res && $row= pdo_fetch_assoc($res) ) {
    $cis[$row['data']]= $row[$val];
  }
  return $cis;
}
# -------------------------------------------------------------------------------------------------- json_encode_simple
function json_encode_simple($ao) {
//                                         debug($ca);
  $js= ''; $ad= '';
  foreach ($ao as $o) {
    $js.= "$ad{"; $ad= ',';
    foreach ($o as $k => $v) {
      if ( !mb_check_encoding($v, 'UTF-8') )
        fce_error("json_encode_simple: invalid UTF string for $k:".urlencode($v));
      $js.= "$od\"$k\":\"".pdo_real_escape_string($v)."\""; $od= ',';
    }
    $js.=  '}';
  }
  return "[$js]";
}
# -------------------------------------------------------------------------------------------------- ezer_json_encode
function ezer_json_encode($ao) {
//                                         debug($ca);
  $js= ''; $ad= '';
  if ( is_array($ao) ) {
    $js.=  '['; $del= '';
    $n= 0; $indexy= false;
    foreach ($ao as $k => $v) {
      if ( !mb_check_encoding($v, 'UTF-8') )
        fce_error("json_encode_simple: invalid UTF string for $k:".urlencode($v));
      if ( !$indexy && $n!=$k ) $indexy= true;
      $i= $indexy ? (is_numeric($k) ? "$k=>" : "\"$k\"=>") : '';
      $js.= "$del$i".ezer_json_encode($v);
      $del= ',';
      $n++;
    }
    $js.=  ']';
  }
  elseif ( is_object($ao) ) {
    $js.=  '{'; $del= '';
    foreach ((array)$ao as $k => $v) {
      $js.= "$del\"$k\":".ezer_json_encode($v);
      $del= ',';
    }
    $js.=  '}';
  }
  else {
    $js= is_numeric($ao) ? $ao : '"'.pdo_real_escape_string($ao).'"';
  }
  return $js;
}
# -------------------------------------------------------------------------------------------------- emailIsValid
# tells you if an email is in the correct form or not
# emailIsValid - http://www.kirupa.com/forum/showthread.php?t=323018
# args:  string - proposed email address
# ret:   bool
function emailIsValid($email,&$reason) {
   $isValid= true;
   $reasons= array();
   $atIndex= strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)    {
      $isValid= false;
      $reasons[]= "chybí @";
   }
   else    {
      $domain= substr($email, $atIndex+1);
      $local= substr($email, 0, $atIndex);
      $localLen= strlen($local);
      $domainLen= strlen($domain);
      if ($localLen < 1 || $localLen > 64)       {
         $isValid= false;
         $reasons[]= "dlouhé jméno";
      }
      else if ($domainLen < 1 || $domainLen > 255)       {
         $isValid= false;
         $reasons[]= "dlouhá doména";
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')       {
         $reasons[]= "tečka na kraji";
         $isValid= false;
      }
      else if (preg_match('/\\.\\./', $local))  {
         $reasons[]= "dvě tečky ve jménu";
         $isValid= false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))   {
         $reasons[]= "chybný znak v doméně";
         $isValid= false;
      }
      else if (preg_match('/\\.\\./', $domain))  {
         $reasons[]= "dvě tečky v doméně";
         $isValid= false;
      }
      else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))   {
         $reasons[]= "chybný znak ve jménu";
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))            {
            $isValid= false;
         }
      }
      if ( $domain!='proglas.cz' && $domain!='setkani.org' ) {
        if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))      {
           $reasons[]= "$domain je neznámá doména";
           $isValid= false;
        }
      }
   }
   $reason= count($reasons) ? implode(', ',$reasons) : '';
   return $isValid;
}
/** ================================================================================================ AJAX - kontroly */
# v této sekci jsou testy korektnosti dat
# -------------------------------------------------------------------------------------------------- verify_rodcis
# podle http://latrine.dgx.cz/jak-overit-platne-ic-a-rodne-cislo
function verify_rodcis($rc) {
  $ok= false;
  // "be liberal in what you receive"
  if (preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches)) {
    list(, $year, $month, $day, $ext, $c) = $matches;
    // do roku 1954 přidělovaná devítimístná RČ nelze ověřit
    if ($c === '') {
      $ok= $year < 54;
    }
    else {
      // kontrolní číslice
      $mod= ($year . $month . $day . $ext) % 11;
      if ($mod === 10) $mod= 0;
      if ($mod === (int) $c) {
        // kontrola data
        $year+= $year < 54 ? 2000 : 1900;
        // k měsíci může být připočteno 20, 50 nebo 70
        if ($month > 70 && $year > 2003) $month-= 70;
        elseif ($month > 50) $month-= 50;
        elseif ($month > 20 && $year > 2003) $month-= 20;
        $ok= checkdate($month, $day, $year);
      }
    }
  }
  return $ok;
}
# -------------------------------------------------------------------------------------------------- verify_datum
# akceptuje d.m.yyyy
function verify_datum($datum,&$d,&$m,&$y,&$timestamp) {
  $ymd= sql_date1($datum,1);
  $y=substr($ymd,0,4);
  $m=substr($ymd,5,2);
  $d=substr($ymd,8,2);
  $ok= checkdate($m,$d,$y);
  $timestamp= mktime(0,0,0,$m,$d,$y);
  return $ok;
}
# -------------------------------------------------------------------------------------------------- datum_rc
/** Zjištění data narození z rodného čísla
* @param string $rodne_cislo rodné číslo ve formátu rrmmdd/xxxx
* @return string datum ve formátu rrrr-mm-dd
* @copyright Jakub Vrána, http://php.vrana.cz
*/
function datum_rc($rodne_cislo) {
  if (preg_match('~^([0-9]{2})([0-9]{2})([0-9]{2})/([0-9]{3,4})$~', $rodne_cislo, $match)) {
    return (strlen($match[4]) < 4 || $match[1] >= 54 ? "19" : "20")
      . "$match[1]-" . sprintf("%02d", $match[2] % 50) . "-$match[3]";
  }
  return false;
}
# -------------------------------------------------------------------------------------------------- rc2time
# převod rodného čísla na datum narození
# (zjednodušené)
function rc2time($rodcis) {
  $t= 0;
  if (preg_match('~^([0-9]{2})([0-9]{2})([0-9]{2})~', $rodcis, $match)) {
    //$y= ($match[1] >= 12 ? "19" : "20") . $match[1];
    $y= (strlen($rodcis)==9 && $match[1]<54 ? "19" : "20") . $match[1];
    $m= $match[2] % 50;  $m= $m ? $m : 1;
    $d= $match[3];       $d= $d ? $d : 1;
    $t= mktime(0,0,0,$m,$d,$y)+1;
  }
  return $t;
}
# -------------------------------------------------------------------------------------------------- rc2dmy
# převod rodného čísla na datum narození ve formátu d.m.Y s opravou chyb
# (zjednodušené)
function rc2dmy($rodcis) {
  $dmy= '';
  if (preg_match('~^([0-9]{2})([0-9]{2})([0-9]{2})~', $rodcis, $match)) {
    $y= ($match[1] >= 12 ? "19" : "20") . $match[1];
    $m= $match[2] % 50; $m= $m ? substr("0$m",-2) : 1;
    $d= $match[3];      $d= $d ? substr("0$d",-2) : 1;
    $dmy= "$d.$m.$y";
  }
  return $dmy;
}
# -------------------------------------------------------------------------------------------------- rc2ymd
# převod rodného čísla na datum narození ve formátu YYYY-mm-dd s opravou chyb
# (zjednodušené)
function rc2ymd($rodcis) {
  $dmy= '0000-00-00';
  if ( (int)$rodcis!=0 && preg_match('~^([0-9]{2})([0-9]{2})([0-9]{2})~', $rodcis, $match)) {
    $y= ($match[1] >= 12 ? "19" : "20") . $match[1];
    $m= $match[2] % 50; $m= $m=='00' ? '01' : $m;
    $d= $match[3];      $d= $d=='00' ? '01' : $d;
    $ymd= "$y-$m-$d";
  }
  return $ymd;
}
# -------------------------------------------------------------------------------------------------- rc2man
# zjistí pohlaví podle rodného čísla (zjednodušené)
function rc2man($rc) {
  $man= strpos(" 56",substr($rc,2,1)) ? false : true;
  return $man;
}
# -------------------------------------------------------------------------------------------------- rc2roky
# zjistí aktuální věk v rocích podle rodného čísla (zjednodušené)
function rc2roky($rc,$now=0) {
//   return $rc;
  if ( !$now ) $now= time();
  $roky= $rc && $rc!="0000000000" ? floor(($now-rc2time($rc))/(60*60*24*365.2425)) : 0;
  return $roky;
}
# -------------------------------------------------------------------------------------------------- narozeni2roky
# zjistí aktuální věk v rocích z data narození (typu mktime) zjištěného třeba rc2time          ?????
# pokud je předáno $now(jako timestamp) bere se věk k tomu
# $roky= narozeni2roky(rc2time($rodcis))
function narozeni2roky($time,$now=0) {
  if ( !$now ) $now= time();
  $roky= floor((date("Ymd",$now) - date("Ymd", $time)) / 10000);
  return $roky;
}
// ------------------------------------------------------------------------------------------------- roku_k
// roku_k (dat[,kdatu=now])
// vrací počet roku uplynulých od daného data do daného data (neni-li uvedeno tak od běžného)
function roku_k($dat,$kdatu='') {
  if ( $kdatu=='' ) $kdatu= date('Y-m-d');
  $roku= '';
//   $k= str2date($kdatu,$kd,$km,$ky);
//   $d= str2date($dat,$dd,$dm,$dy);
//   // přibližně
//   if ( $d && $k ) {
//     $roku= ($km<$dm || ($km==$dm && $kd<$dd)) ? $ky-$dy-1 : $ky-$dy;
//   }
  // přesně
  if ( $dat && $kdatu ) {
    $kd= substr($kdatu,8,2); $dd= substr($dat,8,2);
    $km= substr($kdatu,5,2); $dm= substr($dat,5,2);
    $ky= substr($kdatu,0,4); $dy= substr($dat,0,4);
    $roku= ($km<$dm || ($km==$dm && $kd<$dd)) ? $ky-$dy-1 : $ky-$dy;
  }
  return $roku;
};
// function str2date($s,&$d,&$m,&$y) {
//   list($d,$m,$y)= explode('.',$s);        // formát d.m.Y
//   if ( $y )
//     $d= mktime(0,0,0,$m,$d,$y);
//   else {
//     list($y,$m,$d)= explode('-',$s);      // sql-formát Y-m-d
//     $d= $y ? mktime(0,0,0,$m,$d,$y) : 0;
//   }
//   return $d;
// }
/** ================================================================================================ AJAX - filtry */
// v této sekci jsou oboustranné filtry pro transformaci mezi sql/user podobou dat
# -------------------------------------------------------------------------------------------------- sql_week
// datum bez dne v týdnu
function sql_week ($datum) {
  // převeď sql tvar na uživatelskou podobu (default)
  $text= '';
  if ( $datum && substr($datum,0,10)!='0000-00-00' ) {
/*    $y=substr($datum,0,4);
    $m=substr($datum,5,2);
    $d=substr($datum,8,2);*/
    //$h=substr($datum,11,2);
    //$n=substr($datum,14,2);
    $w= (int)date("W",strtotime($datum));
    $text.= "$w";
//                                                 display("$datum:$text");
  }
  return $text;
}
# -------------------------------------------------------------------------------------------------- sql_date1
// datum bez dne v týdnu
function sql_date1 ($datum,$user2sql=0,$del='.') {
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $text= '';
    if ( $datum ) {
      $datum= str_replace(' ','',$datum);
      list($d,$m,$y)= explode('.',$datum);
      $text= $y.'-'.str_pad($m,2,'0',STR_PAD_LEFT).'-'.str_pad($d,2,'0',STR_PAD_LEFT);
    }
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $text= '';
    if ( $datum && substr($datum,0,10)!='0000-00-00' ) {
      $y=substr($datum,0,4);
      $m=substr($datum,5,2);
      $d=substr($datum,8,2);
      //$h=substr($datum,11,2);
      //$n=substr($datum,14,2);

      $text.= date("j{$del}n{$del}Y",strtotime($datum));
//      $text.= "$d.$m.$y";
//                                                 display("$datum:$text");
    }
  }
  return $text;
}
# -------------------------------------------------------------------------------------------------- sql_date
// datum
function sql_date ($datum,$user2sql=0) {
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $text= '';
    if ( $datum ) {
      $datum= trim($datum);
      list($d,$m,$y)= explode('.',$datum);
      $text= $y.'-'.str_pad($m,2,'0',STR_PAD_LEFT).'-'.str_pad($d,2,'0',STR_PAD_LEFT);
    }
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $dny= array('ne','po','út','st','čt','pá','so');
    $text= '';
    if ( $datum && substr($datum,0,10)!='0000-00-00' ) {
      $y= 0+substr($datum,0,4);
      $m= 0+substr($datum,5,2);
      $d= 0+substr($datum,8,2);
      //$h=substr($datum,11,2);
      //$n=substr($datum,14,2);
      $t= mktime(0,0,1,$m,$d,$y)+1;
//                                                 display("$datum:$m,$d,$y:$text:$t");
      $text= $dny[date('w',$t)];
      $text.= " $d.$m.$y";
    }
  }
  return $text;
}
# -------------------------------------------------------------------------------------------------- sql_yymmdd
// uživatelské datum je ve formě yymmrr, pokud je yy>date('y') chápej rok jako 19yy jinam 20yy
function sql_yymmdd ($datum,$user2sql=0) {
  $text= '';
  if ( $user2sql ) {                            // převeď uživatelskou podobu na sql tvar
    if ( $datum ) {
      $datum= trim($datum);
      $datum= str_pad($datum,6,'_');
      $y= substr($datum,0,2);
      $y= $y>date('y') ? "19$y" : "20$y";
      $text= $y.'-'.substr($datum,2,2).'-'.substr($datum,4,2);
    }
  }
  else {                                        // převeď sql tvar na uživatelskou podobu (default)
    if ( $datum && substr($datum,0,10)!='0000-00-00' ) {
      $text= substr($datum,2,2).substr($datum,5,2).substr($datum,8,2);
    }
  }
  return $text;
}
# -------------------------------------------------------------------------------------------------- sql_time
// datum
function sql_time ($datetime,$user2sql=0,$del=' ') {
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $text= '';
    if ( $datetime ) {
      $wdt= explode($del,trim($datetime));
      $i= count($wdt)>2 ? 1 : 0;
      list($d,$m,$y)= explode('.',$wdt[$i]);
      list($h,$i,$s)= explode(':',$wdt[$i+1]);
      $text= $y.'-'.str_pad($m,2,'0',STR_PAD_LEFT).'-'.str_pad($d,2,'0',STR_PAD_LEFT);
      $text.= ' '.str_pad($h,2,'0',STR_PAD_LEFT).':'.str_pad($i,2,'0',STR_PAD_LEFT).
        ':'.str_pad(($s?$s:0),2,'0',STR_PAD_LEFT);
    }
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $dny= array('ne','po','út','st','čt','pá','so');
    $text= '';
    if ( $datetime && substr($datetime,0,10)!='0000-00-00' ) {
      $y= 0+substr($datetime,0,4);
      $m= 0+substr($datetime,5,2);
      $d= 0+substr($datetime,8,2);
      $h=substr($datetime,11,2);
      $i=substr($datetime,14,2);
      $t= mktime($h,$i,0,$m,$d,$y)+1;
//                                                 display("$datetime:$m,$d,$y:$text:$t");
      $text= $dny[date('w',$t)];
      $text.= " $d.$m.$y $h:$i";
    }
  }
  return $text;
}
# -------------------------------------------------------------------------------------------------- sql_time1
// datum
function sql_time1 ($datetime,$user2sql=0,$del=' ') {
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $text= '';
    if ( $datetime ) {
      $wdt= explode($del,trim($datetime));
      $i= count($wdt)>2 ? 1 : 0;
      list($d,$m,$y)= explode('.',$wdt[$i]);
      list($h,$i,$s)= explode(':',$wdt[$i+1]);
      $text= $y.'-'.str_pad($m,2,'0',STR_PAD_LEFT).'-'.str_pad($d,2,'0',STR_PAD_LEFT);
      $text.= ' '.str_pad($h,2,'0',STR_PAD_LEFT).':'.str_pad($i,2,'0',STR_PAD_LEFT).
        ':'.str_pad(($s?$s:0),2,'0',STR_PAD_LEFT);
    }
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $text= '';
    if ( $datetime && substr($datetime,0,10)!='0000-00-00' ) {
      $y= 0+substr($datetime,0,4);
      $m= 0+substr($datetime,5,2);
      $d= 0+substr($datetime,8,2);
      $h=substr($datetime,11,2);
      $i=substr($datetime,14,2);
      $t= mktime($h,$i,0,$m,$d,$y)+1;
//                                                 display("$datetime:$m,$d,$y:$text:$t");
      $text= " $d.$m.$y $h:$i";
    }
  }
  return $text;
}
# -------------------------------------------------------------------------------------------------- sql_min
// datum
function sql_min ($datetime,$user2sql=0) {
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $text= '';
    if ( $datetime ) {
      $hod= $datetime / 60;
      $min= $datetime % 60;
      $text.= "{$hod}:{$min}:00";
    }
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $text= '';
    if ( $datetime ) {
      $time= explode(':',$datetime);
      $hod= $time[0]*60;
      $min= $time[1];
      $text.= $hod+$min;
    }
  }
  return $text;
}
# -------------------------------------------------------------------------------------------------- sql_time_hmm
// datum
function sql_time_hmm ($datetime,$user2sql=0) {
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $text= '';
    if ( $datetime ) {
      $datetime= strtr($datetime,'.',':');
      $time= explode(':',$datetime);
      $hod= $time[0];
      $min= $time[1];
      $text.= "{$hod}:{$min}:00";
    }
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $text= '';
    if ( $datetime ) {
      $time= explode(':',$datetime);
      $hod= $time[0];
      $min= $time[1];
      $sec= $time[2];
      if ($sec>0)
       $min+= ceil($sec/60);
      $text.= (int)$hod.".".str_pad(substr($min,-2),2,'0',STR_PAD_LEFT);
    }
  }
  return $text;
}
# -------------------------------------------------------------------------------------------------- sql_time_mss
// datum
function sql_time_mss ($datetime,$user2sql=0) {
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $text= '';
    if ( $datetime ) {
      $datetime= strtr($datetime,'.',':');
      $time= explode(':',$datetime);
      $min= $time[0];
      $sec= $time[1];
      if ($min>59) {
        $hod= floor($min/60);
        $min= $min%60;
      } else $hod= 0;
      $text.= "{$hod}:".str_pad(substr($min,-2),2,'0',STR_PAD_LEFT).":".str_pad(substr($sec,-2),2,'0',STR_PAD_LEFT);
    }
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $text= '';
    if ( $datetime ) {
      $time= explode(':',$datetime);
      $hod= $time[0];
      $hod= strtr($hod,['-'=>'']);
      $min= $time[1];
      $sec= $time[2];
      if ($datetime[0]=="-")
        $zn= "-";
      if ($hod>0)
        $min+= $hod*60;
      $text.= "{$zn}".(int)$min.".{$sec}";
    }
  }
  return $text;
}
# -------------------------------------------------------------------------------------------------- stamp_date
# na datum na stránce z timestamp v tabulce
function stamp_date($x,$user2sql=0) { #trace();
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $z= sql_date1($x,1);
    $y= sql2stamp($z);
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $y= date("j.n.Y", $x);
  }
  return $y;
}
# -------------------------------------------------------------------------------------------------- sql2stamp
# na datum z tabulky na timestamp
function sql2stamp($ymd) { #trace();
  if ( $ymd=='0000-00-00' )
    $t= 0;
  else {
    $y= 0+substr($ymd,0,4);
    $m= 0+substr($ymd,5,2);
    $d= 0+substr($ymd,8,2);
    $t= mktime(0,0,0,$m,$d,$y)+1;
  }
  return $t;
}
# -------------------------------------------------------------------------------------------------- win2utf
# konverze z CP-1250 do UTF-8
function win2utf($val,$always=false) { #trace();
  global $ezer_mysql_cp;
  if ( $always || !$ezer_mysql_cp || $ezer_mysql_cp=='cp1250' ) {
    $val= strtr($val, "\x9E\x9A\x9D\x8E\x8A\x8D", "\xBE\xB9\xBB\xAE\xA9\xAB");
    $val= mb_convert_encoding($val,'UTF-8','ISO-8859-2');
  }
  return $val;
}
# -------------------------------------------------------------------------------------------------- utf2win
# konverze z UTF-8 do CP-1250
function utf2win($val,$always=false) {
  global $ezer_mysql_cp;
  if ( $always || !$ezer_mysql_cp || $ezer_mysql_cp=='cp1250' ) {
    $val= iconv("utf-8", "windows-1250", urldecode(pdo_real_escape_string($val)));
  }
  return $val;
}
# -------------------------------------------------------------------------------------------------- utf2winsylk
# konverze z UTF-8 do CP-1250 pro export do SYLKu
function utf2win_sylk($val,$always=false) {
  global $ezer_mysql_cp;
  if ( $always || !$ezer_mysql_cp || $ezer_mysql_cp=='cp1250' ) {
    $val= iconv("utf-8", "windows-1250", $val);
  }
  return $val;
}
# -------------------------------------------------------------------------------------------------- utf2ascii
# konverze z UTF-8 do písmen, číslic a podtržítka, konvertují se i html entity
function utf2ascii($val,$allow='') {
  $txt= preg_replace('~&(.)(?:acute|caron);~u', '\1', $val);
  $txt= preg_replace('~&(?:nbsp|amp);~u', '_', $txt);
  $ref= preg_replace("~[^\\pL0-9_$allow]+~u", '_', $txt);
  $ref= trim($ref, "_");
//     setLocale(LC_CTYPE, "cs_CZ.utf-8");                      bohužel nebývá nainstalováno
//     $url= iconv("utf-8", "us-ascii//TRANSLIT", $url);
  $ref= strtr($ref,array('ě'=>'e','š'=>'s','č'=>'c','ř'=>'r','ž'=>'z','ý'=>'y','á'=>'a','í'=>'i',
                         'é'=>'e','ů'=>'u','ú'=>'u','ó'=>'o','ď'=>'d','ť'=>'t','ň'=>'n'));
  $ref= strtr($ref,array('Ě'=>'E','Š'=>'S','Č'=>'C','Ř'=>'R','Ž'=>'Z','Ý'=>'Y','Á'=>'A','Í'=>'I',
                         'É'=>'E','Ů'=>'U','Ú'=>'U','Ó'=>'O','Ď'=>'D','Ť'=>'T','Ň'=>'N'));
  $ref= mb_strtolower($ref);
  $ref= preg_replace("~[^-a-z0-9_$allow]+~", '', $ref);
  return $ref;
}
# -------------------------------------------------------------------------------------------------- array_values_recursive
# seznam prvků polí
function array_values_recursive($array) {
  $flat= array();
  foreach ($array as $value) {
    if (is_array($value))
      $flat= array_merge($flat, array_values_recursive($value));
    else
      $flat[] = $value;
  }
  return $flat;
}
# -------------------------------------------------------------------------------------------------- menu_definition
# transformace textu v json na definici menu, včetně zohlednění skill
function menu_definition($k1,$k2,$k3,$def) {
  global $y, $json, $USER;
//  $menu= $json->decode(win2utf($def));
  $menu= json_decode(win2utf($def));
  // projdi menu a použij jen dovolené
  $skills= explode(' ',$USER->skills);
  $y->values= array();
  foreach ($menu as $group) {
    // použij group, jen pokud uživatel oprávnění
    if ( !isset($group->skill) || in_array($group->skill,$skills) ) {
      $g= (object)array('group'=>$group->group,'entries'=>array());
      foreach ($group->entries as $entry) {
        // použij entry, jen pokud uživatel oprávnění
        if ( !isset($entry->skill) || in_array($entry->skill,$skills) ) {
          $g->entries[]= $entry;
        }
      }
      if ( count($g->entries) ) $y->values[]= $g;
    }
  }
  $y->selected= array($k2,$k3);
}
# -------------------------------------------------------------------------------------------------- file_between
# vrátí úsek textu ze $soubor mezi \n$ods a $dos
function file_between($soubor,$ods,$dos) {
  $text= '';
  $file= @file_get_contents($soubor);
  if ( $file ) {
    $f1= strpos($file,"\n$ods") + strlen($ods) + 3;
    $f2= $dos ? strpos($file,"\n$dos") : 999999;
    $text= substr($file,$f1,$f2-$f1);
  }
  else fce_error("soubor $soubor neexistuje");
  return $text;
}
# -------------------------------------------------------------------------------------------------- source_text
# vrátí zdrojový text
function source_text ($file,$app='') {
  global $ezer_path_appl, $ezer_path_root;
  $fdir= $app ? "$ezer_path_root/$app" : $ezer_path_appl;
  $fpath= "$fdir/$file.ezer";
  $text= @file_get_contents($fpath);
  return $text;
}
# -------------------------------------------------------------------------------------------------- edit_source
# nastaví zdrojový text v PSPad
function edit_source ($file,$app,$line) { trace();
  global $ezer_path_appl, $ezer_path_root;
  $ok= "?";
  $fdir= "$ezer_path_root/$app";
  $fpath= "$fdir/$file.ezer";
  $pspad= "c:\\Program Files (x86)\\PSPad editor\\pspad.exe";
  if ( file_exists($pspad) && file_exists($fpath) )  {
//     $cmd= "\"$pspad\" /$line $fpath";
    $cmd= "notepad.exe";
//     $cmd= "c:\\copy\\install\\PSTools\\psexec.exe \\\\EZER -u Martin -p g -d notepad.exe";
//     $cmd= "c:\\copy\\install\\PSTools\\psexec.exe \\\\EZER -s -x notepad.exe";
//     $cmd= "c:\\copy\\install\\PSTools\\psexec.exe \\\\EZER -d -s -x notepad.exe";

//     $status= shell_exec($cmd);

//     $status= shell_exec("runas /user:EZER\\Martin $cmd");

//     $status= popen("runas /user:EZER\\Martin $cmd","a");
//     $x= fwrite($status,"g\n");

//        exec("start c:\\winnt\\notepad.exe");

//     pclose(popen("start /I $cmd", "r"));

    pclose(popen("start /B $cmd", "r"));

//     pclose(popen("$cmd", "r"));

//     pclose(popen("start $cmd", "r"));

//     exec('"C:\Program Files (x86)\Notepad++\notepad++.exe" "C:\foo.php"');

//     $WshShell = new COM("WScript.Shell");
//     $oExec= $WshShell->Run($cmd, 0, false);

//     pclose($status= popen($cmd,'r'));

//     $status= popen($cmd,'r');

    $ok= "$status,$x=$cmd";
  }
  return $ok;
}
# -------------------------------------------------------------------------------------------------- source_line
# vrátí úsek zdrojového textu $line (+-1) a označí sloupec
function source_line ($file,$app,$line,$clmn) {
  global $ezer_path_root, $ezer_name, $ezer_root;
  $app= $app ? $app : $ezer_root;
  $fpath= "$ezer_path_root/$app/$file.ezer";
  $f= @fopen($fpath,'r');
  if ( $f ) {
    for ($i= 1; $i<$line-1; $i++) fgets($f);
    $text= '';
    for ($del= ''; $i<$line+2; $i++) {
      $ftext= fgets($f);
      if ( $i==$line )
        $ftext= str_replace(' ','&nbsp;',substr($ftext,0,$clmn-1))
          ."<b style='color:white'>&dagger;</b>"
          .str_replace(' ','&nbsp;',substr($ftext,$clmn-1));
      else
        $ftext= str_replace(' ','&nbsp;',$ftext);
      $text.= "$del$i: $ftext";
      $del= "<br>";
    }
    fclose($f);
  }
  return $text;
}
# ================================================================================================== EXPORT
# sada funkcí pro export z příkazu browse_export ve formátech: csv
#   export_head
#   export_row
#   export_tail
# $export_par je kontext export obsahující na vstupu (+dočasné informace ve složkách začínajících _)
#   dir         -- nepovinné jméno složky pod docs
#   file        -- jméno souboru ve složce docs/dir
#   type        -- 'csv'|'xls'|'xlsx'
#   title       -- pro 'xls(x)': nadpis od A1, hlavička pak začne od A3
#   color       -- pro 'xls(x)': podložení hlavičky, default=aabbbbbb (4 barvy)
# -------------------------------------------------------------------------------------------------- export_head
# otevření exportovaného souboru, $clmns je seznam jmen sloupců
function export_head($par,$clmns,$fmt='') { #trace();
  global $export_par, $ezer_path_docs;
  $export_par= $par;
  $export_par->rows= 0;
  $fpath= $ezer_path_docs.(isset($export_par->dir)?"/{$export_par->dir}":'');
  switch ($export_par->type) {
  case 'csv':
    $export_par->_f= fopen("$fpath/{$export_par->file}.{$export_par->type}",'w');
    $export_par->ok= $export_par->_f ? true : false;
    fputcsv($export_par->_f,explode(',',uw($clmns)),";",'"');
    break;
  case 'xls':
  case 'xlsx':
    $export_par->_xls= "open {$export_par->file}|sheet export;;P;page\n";
    $c= 0;
    $n= 1;
    $export_par->_xls.= "|columns ";
    $color= $export_par->color ? $export_par->color : 'aabbbbbb';
    $del= '';
    $header= '';
    if ( $export_par->title ) {
      $header.= "A1 {$export_par->title}::bold";
      $export_par->rows= 2;
      $n= 3;
    }
    foreach(explode(',',$clmns) as $clmn) {
      $A= Excel5_n2col($c++);
      $export_par->_xls.= "$del{$A}=*";
      $header.= "|$A$n $clmn $fmt";
      $del= ',';
    }
    if ( $export_par->title ) {
      $header.= "|A1:{$A}1 merge center";
    }
    $export_par->_xls.= "\n$header";
    $export_par->_xls.= $fmt ? '' : "\n|A$n:$A$n bcolor=$color";
    break;
  }
}
# -------------------------------------------------------------------------------------------------- export_row
# zápis řádku do exportovaného souboru
function export_row($row,$fmt='') { #trace();
  global $export_par;
  $export_par->rows++;
  switch ($export_par->type) {
  case 'csv':
    if ( $export_par->ok ) {
      fputcsv($export_par->_f,array_map(uw,$row),";",'"');
    }
    break;
  case 'xls':
  case 'xlsx':
    $c= 0;
    $n= 1+$export_par->rows;
    $export_par->_xls.= "\n";
    foreach($row as $val) {
      $A= Excel5_n2col($c++);
      $val= strtr($val,"\n\r","  ");
      $export_par->_xls.= "|$A$n $val $fmt";
    }
    break;
  }
}
# -------------------------------------------------------------------------------------------------- export_tail
# uzavření exportovaného souboru
function export_tail($show_xls=0) { #trace();
  global $export_par;
  $ret= '';
  switch ($export_par->type) {
  case 'csv':
    if ( $export_par->ok )
      fclose($export_par->_f);
    unset($export_par->_f);
    break;
  case 'xls':
  case 'xlsx':
    $export_par->_xls.= "\n|close";
    if ( $show_xls )
      $ret= $export_par->_xls;
    else {
      $wb= null;
      $inf= Excel5($export_par->_xls,1,$wb,$export_par->dir,$export_par->type);
//       $inf= Excel5($export_par->_xls);
      $export_par->ok= $inf ? 0 : 1;
//                                                         debug($export_par,$inf);
      if ( $inf ) fce_warning($inf);
    }
    unset($export_par->_xls);
    break;
  }
  return $ret;
}
# ================================================================================================== EXPORT EXCEL
# -------------------------------------------------------------------------------------------------- wu
# na UTF8 na win1250
function wu($x,$user2sql=0) { #trace();
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $y= utf2win($x,true);
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $y= win2utf($x,true);
  }
  return $y;
}
# -------------------------------------------------------------------------------------------------- uw
# z UTF8 do win1250
function uw($x) {
  return utf2win($x,true);
}
# ================================================================================================== EXCEL5
# -------------------------------------------------------------------------------------------------- Excel5_date
# Excel5_date převede timestamp na excelovské datum
function Excel5_date($tm) {  #trace();
  global $ezer_path_serv;
  require_once "$ezer_path_serv/licensed/xls2/Classes/PHPExcel/Shared/Date.php";
  return PHPExcel_Shared_Date::PHPToExcel($tm);
}
# -------------------------------------------------------------------------------------------------- Excel5
# definice Excelovského souboru verze před Excel2007
# PARAMETRY
#       pokud je $table==null je vytvořena tabulka příkazem 'BOOK table_name'
#       pokud je $table= {wb:otevřená kniha,formats:formáty}
#       dir je nepovinné jméno podsložky docs
# příkazy jsou od sebe odděleny novým řádkem nebo |
# příkazy začínající // jsou ignorovány (// musí být na začátku nepokračovacího řádku nebo po |)
# řádek je možno ukončit ||, nový řádek pak tvoří pokračování stávajícího
# OPEN
#       open name                               -- definuje jméno nového sešitu (pro table==null)
# SHEET
#       sheet name[;printarea[;page]]           -- vytvoří a pojmenuje list
#       printarea=an:an[:o[:clear]]             -- clear nezobrazuje mřížka, o=L|P
#       rowcol=písmena:šířka|číslo:výška
# COLUMNS|ROWS
#       columns def(,def)*                      -- šířky sloupců
#       rows    def(,def)*                      -- výšky řádků
#       def= X [-Y] : ([-]n|*)                  -- kde XY jsou písmena nebo čísla
# FORMAT
#       format od-do:merge,...
# IMAGE
#       image path,výška,buňka[,x[,y]]
# CELL
#       an hodnota
#       an:an formát
#       formát=(s|n|d)(r|b|i|t|d)   (string|number|date)(right|bold|italics|title|decimal)
# CLOSE
#       close name                              -- zapíše table do souboru
function Excel2007($desc) {
  $wb= null;
  return Excel5($desc,1,$wb,'','xlsx');
}
function Excel5($desc,$gen=1,&$wb=null,$dir='',$excel='xls') {  #trace();
  global $ezer_path_serv, $ezer_path_root;
  // pro testování a vývoj
  $list= false;
  if (!$desc || $desc=='0') {
    $list= true;
    $desc= <<<__XLS
      open test
      sheet one;;L;page
      columns A:B=20,||
              C=10
      rows 1:2=30
//       image img/husy.png,80,B3,100,200
      A1 Ahoj :: italic size=16 vert
//       A1:B1 bold merge
//       A2 Red |B2 Blue
//       A2:A2 color=ffff0000
//       B2:B2 bcolor=ffaaaaff bold
//       C4:D5 border=h
//       B1 ěščřžý{}áíéěščý :: wrap |B1:C2 merge middle center
//       C7:D8 border=h,d,,T
      close
__XLS;
  }
  // pro ostrý běh natáhneme knihovny
  if ( $gen ) {
    require_once "$ezer_path_serv/licensed/xls2/Classes/PHPExcel.php";
  }
  $html= "";
  $ws= null;
  $err= 0;
  $desc= str_replace("||\r\n",'',$desc);
  // komponenty
  $id= "(?<id>[-_\w]+)";
  $name= "(?<name>[-_\w\s]+)";
  $name= "(?<name>[^\s;]+)";
  $fit= "(?<fit>page)|)";
  $area= "(?:;(?<area>[A-Z\d:]*)|)";
  $context= '';
  // parser
  foreach (explode("\n",$desc) as $lines) {
    if ( strlen($lines)<2 || substr($lines,0,2)=='//' ) continue;
    foreach (explode("|",$lines) as $line) {
      $line= trim($line);
      if ( strlen($line)<2 || substr($line,0,2)=='//' ) continue;
      if ( $list ) $html.= "<br>$line<br>=&gt;";
      # -------------------------------------------------------------------------  OPEN
      # open name
      if ( preg_match("/^open\s+(?<id>[-_\w]+)$/",$line,$m) ) {
        $bid= $m['id'];
        if ( $list ) $html.= "OPEN $bid";
        if ( $gen ) {
          if ( $wb ) fce_error("XLS: 'open' pouzito pro existujici tabulku {$wb->name}");
          $wb= (object)array();
          $wb->wb= new PHPExcel();
          $wb->name= $bid;
          $wb->active_ws= -1;

          $wb->wb->getDefaultStyle()->getFont()->setName('Arial');
        }
      }
      # ------------------------------------------------------------------------- SHEET
      # sheet name[;printarea[:o[:clear]][;page]]       kde o=L|P
      elseif ( preg_match(
        "/^sheet\s+$name\s*$area\s*(?:;(?<lpc>[\w:]+)|)\s*(?:;$fit\s*$/",$line,$m) ) {
//                                                         debug($m);
        list($lp,$clear)= explode(':',$m['lpc']);
        if ( $list ) $html.= "SHEET {$m['name']}";
        if ( $gen ) {
          if ( !$wb ) fce_error("XLS: 'sheet' pouzito pro neexistujici tabulku");
          if ( $wb->active_ws<0 ) {
            $wb->active_ws= 0;
            $wb->wb->setActiveSheetIndex($wb->active_ws);
          }
          else {
            $wb->active_ws++;
            $wb->wb->createSheet();
            $wb->wb->setActiveSheetIndex($wb->active_ws);
          }
          $ws= $wb->wb->getActiveSheet();
          $ws->setTitle($m['name']);
          $ws->getSheetView()->setZoomScale(75);
          if ( $clear=='clear' )
            $ws->setShowGridlines(false);
          $wp= $ws->getPageSetup();
          $wp->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
          $wp->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
          $wm= $ws->getPageMargins();
          $wm->setTop(0.4)->setRight(0.4)->setBottom(0.4)->setLeft(0.4); // 1cm okraje
          if ( $m['area'] ) {
            $wp->setPrintArea($m['area']);
          }
          if ( $lp=='L' ) {
            $wp->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
          }
          if ( $m['fit']=='page' ) {
            $wp->setFitToPage(true);
          }
        }
      }
      # ------------------------------------------------------------------------- COLUMNS, ROWS
      # columns def(,def)*   -- šířky sloupců
      # rows    def(,def)*   -- výšky řádků
      elseif ( preg_match("/^(columns|rows)\s+(?<def>[\s\w=:.,\*-]+)$/",$line,$m) ) {
        // def= X [-Y] : [-]n    --- kde XY jsou písmena nebo čísla nebo znak *
        if ( $m['def'] ) {
          foreach(explode(',',$m['def']) as $def) {
            list($co,$widthx)= explode('=',trim($def));
            list($co1,$co2)= explode(':',$co);

            if ( is_numeric($co1) ) {
              $x= $co1;
              $y= $co2 ? $co2 : $x;
              if ( $x<=$y ) {
                for ($i= $x; $i<= $y; $i++) {
                  if ( $ws )
                    $ws->getRowDimension($i)->setRowHeight($width);
                    if ( $list ) $html.= " R-$i:$width";
                }
              }
            }
            else {
              $x= Excel5_col2n($co1);
              $y= $co2 ? Excel5_col2n($co2) : $x;
              if ( is_numeric($widthx) ) {
                $width= 0+$widthx;
              }
              if ( $x<=$y ) {
                if ( $list ) $html.= " C-$x-$y:$widthx";
                for ($i= $x; $i<= $y; $i++) {
                  if ( $ws ) {
                    if ( $widthx=='*' )
                      $ws->getColumnDimensionByColumn($i)->setAutoSize(true);
                    else
                      $ws->getColumnDimensionByColumn($i)->setWidth(abs($width))->setVisible($width>0);
                  }
                }
              }
            }
          }
        }
      }
      # ------------------------------------------------------------------------- IMAGE
      # image path,výška,buňka[,x[,y]]
      elseif ( preg_match(
          "/^image\s+(?<path>[\w\/\.]+),(?<height>[0-9\.]+),(?<an>[A-Z]+[0-9]+)".
          "(?:,(?<x>[0-9]+)|)(?:,(?<y>[0-9]+)|)$/",$line,$m) ) {
        $path= $m['path']; $height= $m['height']; $an= $m['an']; $x= $m['x']; $y= $m['y'];
        if ( file_exists("$ezer_path_root/$path") ) {
          if ( $list ) $html.= "IMAGE $path,$height,$an,$x,$y";
          if ( $ws ) {
            $wi= new PHPExcel_Worksheet_Drawing();
            $wi->setPath("$ezer_path_root/$path");
            $wi->setHeight($height);
            $wi->setCoordinates($an);
            if ( $x ) $wi->setOffsetX($x);
            if ( $y ) $wi->setOffsetY($y);
            $wi->setWorksheet($ws);
          }
        }
        else
          $html.= "XLS ERROR Neexistujici  obrazek $path in $line";
      }
      // CELL|FORMAT
      elseif ( preg_match("/^(?<an>[A-Z]+[0-9]+)(?::(?<an2>[A-Z]+[0-9]+)|)\s*(?<v>[^\r]*)/u",$line,$m) ) {
        $an= $m['an']; $an2= $m['an2']; $v= $m['v'];
        if ( $an2 ) {
          # --------------------------------------------------------------------- FORMAT
          # adr:adr format+
          $range= "$an:$an2";
          if ( $list ) $html.= "FORMAT $range";
          if ( $ws ) {
            $html.= Excel5_f($ws,$range,$v,$err);
          }
        }
        else {
          # --------------------------------------------------------------------- CELL
          # adr value [::format]
          if ( $list ) $html.= "CELL-$an-$val-$fmt";
          list($val,$fmt)= explode('::',$v);
          if ( preg_match("/^\s*[-+]{0,1}[0-9]+\.{0,1}[0-9]*\s*$/u",$val) ) {
            if ( $list )     $html.= "NUMBER $an-$val-$fmt";
            if ( $ws )
              $ws->getCell($an)->setValueExplicit($val,PHPExcel_Cell_DataType::TYPE_NUMERIC);
          }
          else {
            if ( $list ) $html.= "TEXT $an-$val-$fmt";
            if ( $ws ) {
              $val= str_replace("{}","\n",$val);
              $ws->setCellValue($an,$val);
            }
          }
          if ( $ws )
            $html.= Excel5_f($ws,$an,$fmt,$err);
        }
      }
      # ------------------------------------------------------------------------- CLOSE
      # close
      elseif ( preg_match("/^close\s*(?<n>[0-9]*)$/",$line,$m) ) {
        $bid= $m['id'];
        $active= $m['n'] ?: 0;
        if ( $gen ) {
          $wb->wb->setActiveSheetIndex($active);
          $objWriter= PHPExcel_IOFactory::createWriter($wb->wb, $excel=='xls' ? 'Excel5' : 'Excel2007');
          $fpath= "$ezer_path_root/docs/".($dir?"$dir/":'')."{$wb->name}.{$excel}";
          $objWriter->save($fpath);
          if ( $list ) $html.= "CLOSE $fpath";
        }
      }
      else {
        $html.= "XLS ERROR Chybna syntaxe radky in $context | $line";
        $err++;
      }
      $context= $line;
    }
  }
  $html= $list ? "<hr>".nl2br($desc)."<hr>$html" : ($err ? "err=$err:$html" : '');
  return $html;
}
# -------------------------------------------------------------------------------------------------- Excel5_col2n
# převedení názvu sloupce na pořadí A -> 0
function Excel5_col2n($c) {
  $c= strtoupper($c);
  if ( strlen($c)==2 )
    $x= (ord($c[0])-ord('A')+1)*(ord('Z')-ord('A')+1) + ord($c[1])-ord('A');
  else
    $x= ord($c)-ord('A');
//                                                         display("$c--$x");
  return $x;
}
# -------------------------------------------------------------------------------------------------- Excel5_n2col
# převedení pořadí sloupce na název 0 -> A
function Excel5_n2col($n) {
  $az= ord('Z')-ord('A')+1;
  $n1= floor($n/$az); $n2= $n % $az;
  $c= ($n1>0 ? chr(ord('A')+$n1-1) : '').chr($n2+ord('A'));
//                                                         display("$n--$c");
  return $c;
}
# -------------------------------------------------------------------------------------------------- Excel5_f
# vytvoření formátu pro Excel5 (bez rich text)
function Excel5_f(&$ws,$range,$v,&$err) {
  $html= '';
  $wcs= $ws->getStyle($range);
  foreach(explode(' ',trim($v)) as $f) if ( $f ) {
    list($f,$x)= explode('=',$f);
    switch ($f) {
    // numerické
    case 'kc':   $wcs->getNumberFormat()->setFormatCode('#,##0.00 Kč'); break;
    case 'proc': $wcs->getNumberFormat()->setFormatCode('0%'); break;
//     case 'date': $wcs->getNumberFormat()->setFormatCode('d. m. yyyy'); break;
//     case 'date': $wcs->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY); break;
    case 'date': $wcs->getNumberFormat()->setFormatCode('dd/mm/yyyy'); break;
    // border=t,r,b,l | border=o   -- 1 je tečkovaná, 2 tenká, 3 tlustá
    case 'border':
      list($t,$r,$b,$l)= explode(',',$x);
      $tl= array('h'=>'hair','d'=>'dotted','t'=>'thin','T'=>'thick');
      $borders= array();
      if ( isset($r) ) {
        $borders['top']=    array('style'=>isset($tl[$t]) ? $tl[$t] : 'none');
        $borders['right']=  array('style'=>isset($tl[$r]) ? $tl[$r] : 'none');
        $borders['bottom']= array('style'=>isset($tl[$b]) ? $tl[$b] : 'none');
        $borders['left']=   array('style'=>isset($tl[$l]) ? $tl[$l] : 'none');
      }
      elseif ( $t[0]=='+' ) {
        $borders['inside']= array('style'=>isset($tl[$t[1]]) ? $tl[$t[1]] : 'none');
      }
      else {
        $borders['outline']= array('style'=>isset($tl[$t]) ? $tl[$t] : 'none');
      }
      $wcs->applyFromArray(array('borders'=>$borders));
      break;
    // barvy
    case 'color':
      $wcs->getFont()->getColor()->setARGB($x); break;
    case 'bcolor':
      $wcs->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($x);
      break;
    // text
    case 'bold':   $wcs->getFont()->setBold(true); break;
    case 'italic': $wcs->getFont()->setItalic(true); break;
    case 'merge':  $ws->mergeCells($range); break;
    case 'size':   $wcs->getFont()->setSize($x); break;
    // rich text -- EXCEL5 NEUMÍ
//     case text:
//       $wc= $ws->getCell($range);
//       $text= $wc->getValue();
//                                                         display("xls2_rich($range)...$text");
//       global $xls2_wr;
//       $xls2_wr= new PHPExcel_RichText($wc);
//       $xls2_wr->createText("AHOJ");
//
//       $text= preg_replace_callback("/{[^}]*}|[^{}]*/u","xls2_rich",$text);
//                                                         display("xls2_rich:$text");
//       break;
    // zarovnání
    case 'left':   $wcs->getAlignment()->setHorizontal('left'); break;
    case 'right':  $wcs->getAlignment()->setHorizontal('right'); break;
    case 'center': $wcs->getAlignment()->setHorizontal('center'); break;
    case 'top':    $wcs->getAlignment()->setVertical('top'); break;
    case 'middle': $wcs->getAlignment()->setVertical('center'); break;
    case 'wrap':   $wcs->getAlignment()->setWrapText(true); break;
    case 'vert':   $wcs->getAlignment()->setTextRotation(90); break;
    default:
      $html.= "XLS ERROR Chybný formát:$f in $line";
      $err++;
    }
  }
  return $html;
}
// function xls2_rich($m) {
//   global $xls2_wr;
//                                                         debug($m,'xls2_rich');
//   if ( $m[0][0]=='{' ) {
//     $xls2_wr->createTextRun("\r\n");
//   }
//   else {
//     $xls2_wr->createTextRun($m[0]);
//   }
//   return '';
// }
# ================================================================================================== EZER2
# -------------------------------------------------------------------------------------------------- mysql_row
# provedení dotazu v $y->qry="..." a vrácení pdo_fetch_assoc (případně doplnění $y->err)
function mysql_row($qry,$err=null) {
  $res= mysql_qry($qry,1);
  $row= $res ? pdo_fetch_assoc($res) : array();
  if ( !$res ) mysql_err($qry);
  return $row;
}
# -------------------------------------------------------------------------------------------------- mysql_object
# provedení dotazu v $y->qry="..." a vrácení pdo_fetch_object (případně doplnění $y->err)
function mysql_object($qry,$err=null) {
  $res= mysql_qry($qry,1);
  $x= $res ? pdo_fetch_object($res) : array();
  if ( !$res ) mysql_err($qry);
  return $x;
}
# -------------------------------------------------------------------------------------------------- getmicrotime
function getmicrotime() {
//   list($usec, $sec) = explode(" ", microtime());
//   return ((float)$usec + (float)$sec);
  return round(microtime(true)*1000);
}
# -------------------------------------------------------------------------------------------------- mysql_err
# ošetření chyby a doplnění $y->error, $y->ok
function mysql_err($qry) {
  global $y;
  $msg= '';
  $merr= pdo_error();
  $serr= "You have an error in your SQL";
  if ( $merr && substr($merr,0,strlen($serr))==$serr ) {
    $msg.= "SQL error ".substr($merr,strlen($serr))." in:$qry";
  }
  else {
    $myerr= $err ? $err : $merr;
    $myerr= str_replace('"',"U",$myerr);
    $msg.= win2utf("\"$myerr\" ")."\nQRY:$qry";
  }
  $y->ok= 'ko';
  $y->error.= $msg;
}
# ---------------------------------------------------------------------------------------- mysql_qry
# provedení dotazu a textu v $y->qry="..." a případně doplnění $y->err
#   $qry      -- SQL dotaz
#   $pocet    -- pokud je uvedeno, testuje se a při nedodržení se ohlásí chyba
#   $err      -- text chybové hlášky, která se použije místo standardní ... pokud končí znakem':'
#                bude za ni doplněna standardní chybová hláška;
#                pokud $err=='-' nebude generována chyba a funkce vrátí false
#   $to_throw -- chyba způsobí výjimku
#   $db       -- před dotazem je přepnuto na databázi daného jména v tabulce $ezer_db nebo na hlavní
function mysql_qry($qry,$pocet=null,$err=null,$to_throw=false,$db='') {
  global $y, $totrace, $qry_del, $qry_count, $ezer_db;
  if ( !isset($y) ) $y= (object)array();
  $msg= ''; $abbr= '';
  $qry_count++;
  $myqry= strtr($qry,array('"'=>"'","<="=>'&le;',"<"=>'&lt;'));
//                                                         display($myqry);
  // dotaz s měřením času
  $time_start= getmicrotime();
  // přepnutí na databázi
  if ( $db ) ezer_connect($db);
  $res= @pdo_query($qry);
  $time= round(getmicrotime() - $time_start,4);
  $ok= $res ? 'ok' : '--';
  if ( !$res ) {
    if ( $err=='-' ) goto end;
    $merr= pdo_error();
    $serr= "You have an error in your SQL";
    if ( $merr && substr($merr,0,strlen($serr))==$serr ) {
      $msg.= "SQL error ".substr($merr,strlen($serr))." in:$qry";
      $abbr= '/S';
    }
    else {
      $myerr= $merr;
      if ( $err ) {
        $myerr= $err;
        if ( substr($err,-1,1)==':' )
          $myerr.= $merr;
      }
      $myerr= str_replace('"',"U",$myerr);
//       $msg.= win2utf("\"$myerr\" ")."\nQRY:$qry";
      $msg.= "\"$myerr\" \nQRY:$qry";
      $abbr= '/E';
    }
    $y->ok= 'ko';
  }
  // pokud byl specifikován očekávaný počet, proveď kontrolu
  else if ( $pocet  ) {
    if ( substr($qry,0,6)=='SELECT' )
      $num= pdo_num_rows($res);
    elseif ( in_array(substr($qry,0,6),array('INSERT','UPDATE','REPLAC','DELETE')) )
      $num= pdo_affected_rows(); // INSERT, UPDATE, REPLACE or DELETE
    else
      fce_error("mysql_qry: neznámá operace v $qry");
    if ( $pocet!=$num ) {
      if ( $num==0 ) {
        $msg.= "nenalezen záznam " . ($err ? ", $err" : ""). " v $qry";
        $abbr= '/0';
      }
      else {
        $msg.= "vraceno $num zaznamu misto $pocet" . ($err ? ", $err" : ""). " v $qry";
        $annr= "/$num";
      }
      $y->ok= 'ko';
      $ok= "ko [$num]";
      $res= null;
    }
  }
  if ( strpos($totrace,'M')!==false ) {
    $pretty= $myqry;
    if ( strpos($pretty,"\n")===false )
      $pretty= preg_replace("/(FROM|LEFT JOIN|JOIN|WHERE|GROUP|HAVING|ORDER)/","\n\t\$1",$pretty);
    $y->qry.= (isset($y->qry)?"\n":'')."$ok $time \"$pretty\" ";
//    $y->qry.= (isset($y->qry)?"\n":'')."$ok $time \"$myqry\" ";
  }
  $y->qry_ms= isset($y->qry_ms) ? $y->qry_ms+$time : $time;
  $qry_del= "\n: ";
  if ( $msg ) {
    if ( $to_throw ) throw new Exception($err ? "$err$abbr" : $msg);
    else $y->error= (isset($y->error) ? $y->error : '').$msg;
  }
end:
  return $res;
}
# -------------------------------------------------------------------------------------------------- ezer_qry
# záznam změn do tabulky _track
# 1. ezer_qry("INSERT",$table,$x->key,$zmeny[,$key_id]);       -- vložení 1 záznamu
# 2. ezer_qry("UPDATE",$table,$x->key,$zmeny[,$key_id]);       -- oprava 1 záznamu
#     zmeny= [ zmena,...]
#     zmena= { fld:field, op:a|p|d|c, val:value, row:n }          -- pro chat
#          | { fld:field, op:u,   val:value, [old:value] }        -- pro opravu
#          | { fld:field, op:i,   val:value }                     -- pro vytvoření
# 3. ezer_qry("UPDATE_keys",$table,$keys,$zmeny[,$key_id]);    -- hromadná oprava pro key IN ($keys)
#     zmeny= { fld:field, op:m|p|a, val:value}                    -- SET fld=value
function user_test() {
  global $mysql_db_track, $USER;
  if ( $mysql_db_track && !$USER->abbr ) {
    fce_error("Vaše přihlášení již vypršelo - přihlaste se prosím znovu a operaci opakujte");
  }
}
function ezer_qry ($op,$table,$cond_key,$zmeny,$key_id='') {
  global $json, $mysql_db, $mysql_db_track, $mysql_tracked, $USER;
//                                                         debug($zmeny,"qry_update($op,$table,$cond_key)");
  $result= 0;
  $tracked= array();
  $keys= '???';                 // seznam klíčů
  $tab= str_replace("$mysql_db.",'',$table);
  if ( !$key_id ) $key_id= $tab=='pdenik' ? 'id_pokl' : str_replace('__','_',"id_$tab");
  $user= $USER->abbr;
  user_test();
  // zpracování parametrů -- jen pro UPDATE
  switch ( $op ) {
  case 'INSERT':
    // vytvoření INTO a VALUES
    $flds= ''; $vals= ''; $del= '';
    $tracked[0]= array();
    foreach ($zmeny as $zmena) {
      $fld= $zmena->fld;
      if ( $fld!='zmena_kdo' && $fld!='zmena_kdy' ) $tracked[0][]= $zmena;
      if ( $fld=='id_cis' ) $id_cis= $zmena->val;
      $val= pdo_real_escape_string($zmena->val);
      $flds.= "$del$fld";
      $vals.= "$del'$val'";
      $del= ',';
    }
    // provedení INSERT
    $key_val= 0;
    $qry= "INSERT INTO $table ($flds) VALUES ($vals)";
    $res= mysql_qry($qry);
    $result= $tab=="_cis" ?  $id_cis : pdo_insert_id();
    $keys= $result;
    break;
  case 'UPDATE':
    // vytvoření SET a doplnění WHERE
    $set= ''; $and= ''; $del= '';
    $tracked[0]= array();
    foreach ($zmeny as $zmena) {
      $fld= $zmena->fld;
      if ( $fld!='zmena_kdo' && $fld!='zmena_kdy' ) $tracked[0][]= $zmena;
      $val= pdo_real_escape_string($zmena->val);
      switch ( $zmena->op ) {
      case 'a':
        $set.= "$del$fld=concat($fld,'$val')";
        break;
      case 'p':
        $set.= "$del$fld=concat('$val',$fld)";
        break;
      case 'd': // delete záznam row v chat
        $va= explode('|',$zmena->old);
        $old= pdo_real_escape_string($zmena->old);
        $zmena->old_val= "{$va[2*$zmena->row-2]}|{$va[2*$zmena->row-1]}";
        unset($va[2*$zmena->row-2],$va[2*$zmena->row-1]);
        $vn= pdo_real_escape_string(implode('|',$va));
        $set.= "$del$fld='$vn'";
        $and.= " AND $fld='$old'";
        break;
      case 'c': // change záznam row v chat
        $old= pdo_real_escape_string($zmena->old);
        $va= explode('|',$old);
        $zmena->old_val= "{$va[2*$zmena->row-2]}|{$va[2*$zmena->row-1]}";
        $va[2*$zmena->row-1]= $val;
        $vn= implode('|',$va);
        $set.= "$del$fld='$vn'";
        $and.= " AND $fld='$old'";
        break;
      case 'u':
      case 'U': // určeno pro hromadné změny
        $set.= "$del$fld='$val'";
        if ( isset($zmena->old) ) {
          $old= pdo_real_escape_string($zmena->old);
          $and.= " AND $fld='$old'";
        }
        break;
      case 'i':
        $set.= "$del$fld='$val'";
        break;
      }
      $del= ',';
    }
    // provedení UPDATE pro jeden záznam s kontrolou starých hodnot položek
    $key_val= $cond_key;
    $qry= "SELECT $key_id FROM $table WHERE $key_id=$key_val $and ";
    if ( mysql_qry($qry,1) )  {
      $qry= "UPDATE $table SET $set WHERE $key_id=$key_val $and ";
      mysql_qry($qry);
      $result= 1;
    }
    $keys= $key_val;
    break;
  case 'UPDATE_keys':
//                                                         debug($zmeny,"qry_update($op,$table,$cond_key)");
    $akeys= explode(',',$cond_key);
    sort($akeys);
    foreach ($akeys as $i => $key) {
      $tracked[$i][0]= $zmeny;
      $tracked[$i][0]->key= $key;
    }
    $keys= implode(',',$akeys);
    $fld= $zmeny->fld;
    $val= pdo_real_escape_string($zmeny->val);
    switch ( $zmeny->op ) {
    case 'm':
      // zjištění starých hodnot podle seznamu klíčů
      $qry= "SELECT GROUP_CONCAT($fld SEPARATOR '|') as $fld FROM $table WHERE $key_id IN ($keys)";
      $res= mysql_qry($qry);
      if ( $res ) {
        $row= pdo_fetch_assoc($res);
        foreach (explode('|',$row[$fld]) as $i => $old) {
          $tracked[$i][0]->old= $old;
        }
      }
      $qry= "UPDATE $table SET $fld='$val' WHERE $key_id IN ($keys)";
      break;
    case 'a':
    case 'p':
      $concat= $zmeny->op=='a' ? "concat($fld,'$val')" : "concat('$val',$fld)";
      $qry= "UPDATE $table SET $fld=$concat WHERE $key_id IN ($keys)";
      break;
    case 'd':
    case 'c':
      fce_error("ezer_qry: hromadná operace {$zmeny->op} neimplementována");
      break;
    }
    // provedení UPDATE pro záznamy podle seznamu klíčů
//                                                         display($qry);
    mysql_qry($qry);
    break;
  default:
    fce_error("ezer_qry: operace $op neimplementována");
  }
  // zápis změn do _track
  if (strpos($table,".")!==false) {
    $table= explode('.',$table);
    $table= $table[count($table)-1];
  }
  if ( $mysql_db_track && count($tracked)>0 && strpos($mysql_tracked,",$table,")!==false ) {
    $qry= "";
    $now= date("Y-m-d H:i:s");
    $del= '';
    foreach (explode(',',$keys) as $i => $key) {
      $qry_prefix= "INSERT INTO _track (kdy,kdo,kde,klic,fld,op,old,val) VALUES ('$now','$user','$tab',$key";
      foreach ($tracked[$i] as $zmena) {
        $fld= $zmena->fld;
        $op= $zmena->op;
        switch ($op) {
        case 'd':
          $val= '';
          $old= pdo_real_escape_string($zmena->old_val);
          break;
        case 'c':
          $val= pdo_real_escape_string($zmena->val);
          $old= pdo_real_escape_string($zmena->old_val);
          break;
        default:
          // zmena->pip je definovaná ve form_save v případech zápisu hodnoty přes sql_pipe
          $val= pdo_real_escape_string($zmena->val);
          $old= $zmena->old ? pdo_real_escape_string($zmena->old) : (
                $zmena->pip ? pdo_real_escape_string($zmena->pip) : '');
          break;
        }
        $qry= "$qry_prefix,'$fld','$op','$old','$val'); ";
        $res= mysql_qry($qry);
//                                                 display("TRACK: $qry");
      }
    }
  }
end:
  return $result;
}
?>
