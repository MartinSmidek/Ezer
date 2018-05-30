<?php # (c) 2017 Martin Smidek <martin@smidek.eu>
/**
 * $db      - [server,local] jméno hlavní databáze
 * $dbs     - [server,local] seznam databází
 *            databáze => (,server,username,userpass,kódování,[fyzické jméno databáze])
 *                        databáze 'ezer_system' obsahuje platnou tabulku _user
 * $app_php - seznam *.php umístěných v $ezer_root
 * $tracked
*/
 
  const EZER_version= 3.1;
  
  global $ezer_root;

  // nastavení zobrazení PHP-chyb klientem při &err=1
  if ( isset($_GET['err']) && $_GET['err'] ) {
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 'On');
  }

  // test přístupu z jádra
  if ( $_POST['root']!=$ezer_root ) die('POST PROBLEM'); 

  // identifikace ladícího serveru
  $ezer_local= preg_match('/^\w+\.bean$/',$_SERVER["SERVER_NAME"])?1:0;

  // cesty
  $abs_root= $_SESSION[$ezer_root]['abs_root'];
  $rel_root= $_SESSION[$ezer_root]['rel_root'];

  chdir($abs_root);//("../..");

  require_once("$abs_root/ezer".EZER_version."/server/ae_slib.php");
  require_once("$abs_root/ezer".EZER_version."/server/ezer_lib3.php");

  $path_root=  array($abs_root,$abs_root);
  $path_pspad= null;
  
  // ostatní parametry
  $tracking= '_track';
  $tracked= isset($tracked) ? "$tracked,_user," : ',_user,';
  root_inc3($db,$dbs,$tracking,$tracked,$path_root,$path_pspad,$ezer_root);

  // PARAMETRY SPECIFICKÉ PRO APLIKACI

  // specifické cesty

  // moduly interpreta zahrnuté do aplikace - budou zpracovány i reference.i_doc pro tabulky kompilátoru
  $ezer_comp_ezer= "ezer_app3,ezer3,ezer_area3,ezer_rep3,ezer_fdom3";
  
  // moduly v Ezerscriptu mimo složku aplikace
  $ezer_ezer= array();
  
  // standardní moduly v PHP obsažené v $ezer_path_root/ezer2 - vynechané v dokumentaci
  $ezer_php_libr= array(
    'server/ae_slib.php',
    'server/ezer_lib3.php',
    'server/reference.php',
    'ezer2_fce.php',
    'server/sys_doc.php',
    'server/ezer2.php'
  );
  
  // uživatelské i knihovní moduly v PHP obsažené v $ezer_path_root
  $ezer_php= array_merge(
    array("ezer".EZER_version."/ezer2_fce.php"),
    $app_php
  );

  // vložení modulů
  foreach($ezer_php as $php) {
    require_once("$ezer_path_root/$php");
  }

//function show_session() {
//  debug($_SESSION);
//  return 1;
//}
?>
