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

  global $app_root, $ezer_root;
  $ezer_root= $app_root;
  
  // platí buďto isnull($ezer_local) nebo isnull($ezer_server)
  global $ezer_local, $ezer_server;
  if ( is_null($ezer_local) && is_null($ezer_root) ) 
    fce_error("inconsistent server setting");
  $is_local= is_null($ezer_local) ? !$ezer_server : $ezer_local;
  
  // nastavení zobrazení PHP-chyb klientem při &err=1
  if ( isset($_GET['err']) && $_GET['err'] ) {
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 'On');
  }

  // parametry aplikace
  $app= $app_root;
  $CKEditor= isset($_GET['editor'])  ? $_GET['editor']  : '4.6';
  $dbg=      isset($_GET['dbg'])     ? $_GET['dbg']     : 1;                          /* debugger */
  $gapi=     isset($_GET['gapi'])    ? $_GET['gapi']    : 0; //!($ezer_local || $ezer_ksweb);
  $gmap=     isset($_GET['gmap'])    ? $_GET['gmap']    : 0; //!($ezer_local || $ezer_ksweb);

  // inicializace SESSION
  if ( !isset($_SESSION) ) {
    session_unset();
    session_start();
  }
  $_SESSION[$app]['GET']= $_GET;
  $_SESSION[$app]['ezer']= '3.1';

  // přepínač pro fáze migrace pod PDO - const EZER_PDO_PORT=1|2|3
  if ( isset($_GET['pdo']) && $_GET['pdo']==2 ) {
    require_once("pdo.inc.php");
    $_SESSION[$app]['pdo']= 2;
  }
  else {
    require_once("mysql.inc.php");
    $_SESSION[$app]['pdo']= 1;
  }

  // doplnění jména aplikace o verzi ezer a db
  $app_name.=  " 3.1.".EZER_PDO_PORT;

  // nastavení cest
  $abs_root= isset($ezer_server) ? $abs_roots[$ezer_server] : $abs_roots[$ezer_local];
  $_SESSION[$app]['abs_root']= $abs_root;

  $http_rel_root= isset($ezer_server) ? $rel_roots[$ezer_server] : $rel_roots[$ezer_local];
  list($http,$rel_root)= explode('://',$http_rel_root);
  $_SESSION[$app]['rel_root']= $rel_root;
  
  $_SESSION[$app]['app_path']= "";
  
  // kořeny pro LabelDrop
  $path_files_href= "$rel_root/docs/$app";
  $path_files_s= "$abs_root/docs/$app";
  $path_files_h= substr($abs_root,0,strrpos($abs_root,'/'))."/files/$app/";

  set_include_path(get_include_path().PATH_SEPARATOR.$abs_root);
  $_POST['root']= $ezer_root;

  require_once("$abs_root/ezer3.1/server/ezer_pdo.php");
  require_once("$app.inc.php");
  
  $cms= "$http://$rel_root/$ezer_root";
  $client= "$http://$rel_root/{$EZER->version}/client";
  $licensed= "$client/licensed";

  // -------------------------------------------------------------------------------------- Ezer 3
  $api_key= "AIzaSyAq3lB8XoGrcpbCKjWr8hJijuDYzWzImXo"; // Google Maps JavaScript API 'answer-test'
  $app_js= array_values(array_filter($app_js)); // vynechání všech false
  $js= array_merge(
    // ckeditor 
    array("$licensed/ckeditor$CKEditor/ckeditor.js"),
    // kalendářový prvek
    array("$licensed/pikaday/pikaday.js"),
    // jQuery
    array("$licensed/jquery-3.3.1.min.js","$licensed/jquery-noconflict.js","$client/licensed/jquery-ui.min.js"),
    // podpora dotykového ovládání
    array("$licensed/jquery.touchSwipe.min.js"),
    // jádro Ezer3.1
    array(
      "$client/ezer_app3.js","$client/ezer3.js","$client/ezer_area3.js",
      "$client/ezer_rep3.js","$client/ezer_lib3.js","$client/ezer_tree3.js"
    ),
    // rozhodnout zda používat online mapy
    $gmap ? array(
      "https://maps.googleapis.com/maps/api/js?libraries=places&key=$api_key") : array(),
//    $gmap ? array(
//      "https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js",
//      "https://maps.googleapis.com/maps/api/js?sensor=false") : array(),
    // uživatelské skripty
//    $app_js
      array_map(function($x){global $http_rel_root; return $http_rel_root.$x;},$app_js)
  );
  $app_css= array_values(array_filter($app_css)); // vynechání všech false
  $css= array_merge(
    array("$client/ezer3.css.php=skin",      
    "$client/licensed/font-awesome/css/font-awesome.min.css",
    "$client/licensed/pikaday/pikaday.css","$client/licensed/jquery-ui.min.css"),
    // uživatelské styly
    $app_css  
  );
  
  // nastavení jádra
  $options= (object)array(              // přejde do Ezer.options...
    'gmap' => $gmap,                    // zda používat mapy Google
    'curr_version' => 0,                // při přihlášení je nahrazeno nejvyšší ezer_kernel.version
    'curr_users' => $is_local ? 0 : 1,  // zobrazovat v aktuální hodině aktivní uživatele
    'path_files_href' => "'$path_files_href'",  // relativní cesta do složky docs/{root}
    'path_files_s' => "'$path_files_s'",        // absolutní cesta do složky docs/{root}
    'path_files_h' => "'$path_files_h'",        // absolutní cesta do složky ../files/{root}
    'server_url'   => "'$http_rel_root/{$EZER->version}/server/ezer2.php'"
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
  root_php3($app,$app_name,'chngs',$skin,$options,$js,$css,$pars);
?>
