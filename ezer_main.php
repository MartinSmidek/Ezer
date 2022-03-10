<?php // ezer 3.1
/**
 * $app_name      - zobrazený název aplikace
 * $app_login     - username/password (pouze pro automatické přihlášení)
 * $ezer_root     - podsložka aplikace
 * $kontakt       - kontakt zobrazený na přihlašovací stránce
 * $app_js        - seznam *.js umístěných v $ezer_root
 * $app_css       - seznam *.css umístěných v $ezer_root
 * $skin=default  - počáteční skin aplikace 
 * $abs_roots     - [server,local]
 * $rel_roots     - [server,local]
 * $add_pars      - (array) doplní resp. přepíše obsah $pars
 */
//  echo("ezer_main.php start, ezer_server=$ezer_server");

  global $app_root, $ezer_root, $api_key, $appl_version;
  $ezer_root= $app_root;
  
  // platí buďto isnull($ezer_local) nebo isnull($ezer_server)
  global $ezer_local, $ezer_server;
  if ( is_null($ezer_local) && is_null($ezer_server) ) 
    fce_error("inconsistent server setting (2)");
  $is_local= is_null($ezer_local) ? !$ezer_server : $ezer_local;
  
  // nastavení zobrazení PHP-chyb a výjimek v ezer2.php
  // err=0 potlačí zobrazení chyb při inicializaci (tj. mimo kód v ezer2.php), jinak jako err=1
  // err=1 ... (default) standardní handler: E_ALL & ~E_NOTICE 
  // err=2 ... v PHP7 vlastní handler: E_ALL & ~E_NOTICE     
  // err=3 ... v PHP7 vlastní handler: E_ALL
  if ( isset($_GET['err']) && ($err= $_GET['err']) ) {
    error_reporting($err==3 ? E_ALL : E_ALL & ~E_NOTICE);
    ini_set('display_errors', 'On');
  }
  else {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 'Off');
  }
  
  // verze 5 awesome fontu?
  $awe= isset($_GET['awe']) && $_GET['awe']==5 ? 5 : 4;

  // parametry aplikace
  $app= $app_root;
  $CKEditor= isset($_GET['editor'])  ? $_GET['editor']  : (isset($_COOKIE['editor']) ? $_COOKIE['editor']  : '4.6');
  $jQuery=   isset($_GET['jquery'])  ? $_GET['jquery']  : (isset($_COOKIE['jquery']) ? $_COOKIE['jquery']  : '3.3.1');
  $dbg=      isset($_GET['dbg'])     ? $_GET['dbg']     : 0;
  $gapi=     isset($_GET['gapi'])    ? $_GET['gapi']    : 0; //!($ezer_local || $ezer_ksweb);
  $gmap=     isset($_GET['gmap'])    ? $_GET['gmap']    : (isset($_COOKIE['gmap']) ? $_COOKIE['gmap']  : ($is_local?0:1));
  $smap=     isset($_GET['smap'])    ? $_GET['smap']    : (isset($_COOKIE['smap']) ? $_COOKIE['smap']  : ($is_local?0:1));
  $touch=    isset($_GET['touch'])   ? $_GET['touch']   : 1;

  // inicializace SESSION
  if ( !isset($_SESSION) ) {
    session_unset();
    if ( isset($add_options->gc_maxlifetime) ) {
      // nastavení ini_set musí být od PHP7.2 před session_start
      $gc_maxlifetime= isset($add_options->gc_maxlifetime) ? $add_options->gc_maxlifetime : 12*60*60;
      ini_set('session.gc_maxlifetime',$gc_maxlifetime);
      session_start();
      $_SESSION['gc_maxlifetime']= $gc_maxlifetime;
    }
    else {
      session_start();
    }
  }
  $_SESSION[$app]['GET']= $_GET;
  $_SESSION[$app]['ezer']= '3.1';
  $_SESSION[$app]['ezer_server']= $ezer_server;
  $_SESSION[$app]['appl_version']= $appl_version;

  // přepínač pro fáze migrace pod PDO - const EZER_PDO_PORT=1|2|3
  if ( isset($_GET['pdo']) && $_GET['pdo']==2 ) {
    require_once("pdo.inc.php");
    $_SESSION[$app]['pdo']= 2;
  }
  else {
    require_once("mysql.inc.php");
    $_SESSION[$app]['pdo']= 1;
  }

//  // doplnění jména aplikace o verzi ezer a pdo
//  $app_name.=  " 3.1.".EZER_PDO_PORT;

  // nastavení cest
  $abs_root= isset($ezer_server) ? $abs_roots[$ezer_server] : $abs_roots[$ezer_local];
  $_SESSION[$app]['abs_root']= $abs_root;

  $http_rel_root= isset($ezer_server) ? $rel_roots[$ezer_server] : $rel_roots[$ezer_local];
  list($http,$rel_root)= explode('://',$http_rel_root);
  $_SESSION[$app]['rel_root']= $rel_root;
  
  $_SESSION[$app]['app_path']= "";
  
  // kořeny pro LabelDrop
  $path_files_href= "$http://$rel_root/docs/$app";
  $path_files_s= "$abs_root/docs/$app";
  $path_files_h= substr($abs_root,0,strrpos($abs_root,'/'))."/files/$app/";

  set_include_path(get_include_path().PATH_SEPARATOR.$abs_root);
  $_POST['root']= $ezer_root;

  require_once("$abs_root/ezer3.1/server/ezer_pdo.php");
  require_once("$app.inc.php");
  
  $cms= "$http://$rel_root/$ezer_root";
  $ezer31= "$http://$rel_root/ezer3.1";
  $client= "$http://$rel_root/ezer3.1/client";
  $licensed= "$client/licensed";

  // klíče, pokud jsou dostupné
  $deep_root= "../files/ezer3.1";
  if ( @file_exists("$deep_root/ezer.keys.php"))
    require_once("$deep_root/ezer.keys.php");
  
  // pokud existují soubory $app/version.php (resp. $app_version_in/version.php) a $ezer31/version.php 
  // použij proměnnou $version pro výběr aktuální verze *.js
  $v_sys= '';
  if (file_exists("$abs_root/ezer3.1/version.php")) {
    require "$abs_root/ezer3.1/version.php";
    $v_sys= "?v=$version";
  }
  $v_app= '';
  if (file_exists("$abs_root/$app/version.php")) {
    require "$abs_root/$app/version.php";
    $v_app= "?v=$version";
  }
  elseif (isset($app_version_in) && file_exists("$abs_root/$app_version_in/version.php")) {
    require "$abs_root/$app_version_in/version.php";
    $v_app= "?v=$version";
  }
    
  $app_js= array_values(array_filter($app_js)); // vynechání všech false
  $js= array_merge(
    // ckeditor 
    array("$licensed/ckeditor$CKEditor/ckeditor.js"),
    // kalendářový prvek
    array("$licensed/pikaday/pikaday.js"),
    // jQuery
    array("$licensed/jquery-$jQuery.min.js","$licensed/jquery-noconflict.js","$client/licensed/jquery-ui.min.js"),
    // podpora dotykového ovládání
    array($touch ? "$licensed/jquery.touchSwipe.min.js" : ''),
    // jádro Ezer3.1
    array(
      "$client/ezer_app3.js$v_sys","$client/ezer3.js$v_sys","$client/ezer_area3.js$v_sys",
      "$client/ezer_rep3.js$v_sys","$client/ezer_lib3.js$v_sys","$client/ezer_tree3.js$v_sys"
    ),
    // rozhodnout zda používat online mapy 
    $gmap==1 ? array(
      "https://maps.googleapis.com/maps/api/js?libraries=places&key=$api_key") : array(),
    $smap==1 ? array(
      "https://api.mapy.cz/loader.js") : array(),
    // uživatelské skripty
      array_map(function($x) use ($http_rel_root,$v_app) {
        return "$http_rel_root/$x$v_app";
      },$app_js)
  );
  $app_css= array_values(array_filter($app_css)); // vynechání všech false
  $css= array_merge(
    array("$client/ezer3.css$v_sys","$client/ezer3.css.php=skin",  
    $awe==5
      ? "$client/licensed/font-awesome-5/css/all.min.css"
      : "$client/licensed/font-awesome/css/font-awesome.min.css",
    "$client/licensed/pikaday/pikaday.css","$client/licensed/jquery-ui.min.css"),
    // uživatelské styly
    array_map(function($x) use ($http_rel_root,$v_app) {
      return "$http_rel_root/$x$v_app";
    },$app_css)
  );
  
  // nastavení jádra
  $options= (object)array(              // přejde do Ezer.options...
    'gmap' => $gmap,                    // zda používat mapy Google
    'watch_git'    => 0,                // sleduj git-verzi aplikace a jádra, při změně navrhni restart
    'curr_version' => 0,                // při přihlášení je nahrazeno nejvyšší ezer_kernel.version
    'curr_users' => $is_local ? 0 : 1,  // zobrazovat v aktuální hodině aktivní uživatele
    'path_files_href' => "'$path_files_href'",  // relativní cesta do složky docs/{root}
    'path_files_s' => "'$path_files_s'",        // absolutní cesta do složky docs/{root}
    'path_files_h' => "'$path_files_h'",        // absolutní cesta do složky ../files/{root}
    'server_url'   => "'$ezer31/server/ezer2.php'",
    'kernel_url'   => "'$ezer31'"
  );

  $pars= (object)array(
    'favicon' => isset($ezer_server) 
      ? ($ezer_server ? "{$app}.png" : "{$app}_local.png")
      : ($ezer_local ? "{$app}_local.png" : "{$app}.png"),
    'app_root' => "$rel_root",      // startovní soubory app.php a app.inc.php jsou v kořenu
    'dbg' => $dbg,                                              
    'watch_ip' => false,
    'watch_key' => false,
    'log_login' => true,        // jádro standardně zapisuje login do _touch (jako ve verzi 2.2)
    'CKEditor' => "{
        version:'$CKEditor',
        Minimal:{toolbar:[['Bold','Italic','Source']]},
        EzerHelp2:{
          toolbar:[['PasteFromWord','-','Bold','Italic','TextColor','BGColor',
            '-','JustifyLeft','JustifyCenter','JustifyRight',
            '-','Link','Unlink','HorizontalRule','Image',
            '-','NumberedList','BulletedList',
            '-','Outdent','Indent',
            '-','Source','ShowBlocks','RemoveFormat']],
          extraPlugins:'ezersave,imageresize', removePlugins:'image'
        }
      }"
  );
  if ( isset($kontakt) ) 
    $pars->contact= $kontakt;
    
  // případná úprava $pars podle $add_pars
  if ( isset($add_pars) ) {
    foreach ($add_pars as $key=>$val) {
      $pars->$key= $val;
    }
  }
    
  // případná úprava $options podle $add_options
  if ( isset($add_options) ) {
    foreach ($add_options as $key=>$val) {
      $options->$key= $val;
    }
  }

  // způsob přihlášení  
  if ( isset($app_login) && $app_login ) {
    $pars->autologin= $app_login;   
    $options->must_log_in= 0;
  }
  else {
    $options->must_log_in= 1;
  }
//  echo("ezer_main.php end<br>");
  root_php3($app,$app_name,'chngs',$skin,$options,$js,$css,$pars);
?>
