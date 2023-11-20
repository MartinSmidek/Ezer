<?php # Ezer3 (c) 2017 Martin Smidek <martin@smidek.eu>
  error_reporting(E_ALL & ~E_NOTICE);
  # --------------------------------------------------------------------------------- paths, globals
  # globální objekty ($json bude v PHP6 zrušeno)
  global $ezer_root, $ezer_path_root, $USER, $EZER;
  # --------------------------------------------------------------------------------------- requires
  # vložení a inicializace balíků
  $ezer_root= $_POST['root'];                        // jméno adresáře a hlavního objektu aplikace
  session_start(); // defaultní práce se session
  $USER= $_SESSION[$ezer_root]['USER'];
//   # --------------------------------------------------------------------------------- root.inc[.php]
//   $path= $_POST['app_root'] ? "./../../$ezer_root" : "./../../";
//   $ezer_root_inc= file_exists("$path/$ezer_root.inc.php") ? "$ezer_root.inc.php" : "$ezer_root.inc";
//   require_once("$path/$ezer_root_inc");
  $php_start= getmicrotime();                        // měření času
  # ----------------------------------------------------------------------------------------- params
  # cmd    - příkaz
  # x      - parametry
//  if (get_magic_quotes_gpc()) 
  $_POST= stripSlashes_r($_POST);
  $x= array2object($_POST);
  // vlastní knihovny
  $_SESSION[$ezer_root]['touch']= date("j.n.Y H:i:s");
//   chdir($ezer_path_root);
  $y= (object)array();
  $y->cmd= $x->cmd;
  // kopie ae_trace: používá se k omezení trasovacích informací
  $totrace= isset($x->totrace) ? $x->totrace : '';   
  $y->qry_ms= 0;
  switch ( $x->cmd ) {
  # ================================================================================== VOLÁNÍ z EZER
  # ------------------------------------------------------------------------------------------- time
  case 'time':
    $y->value= date('H:i:s');
    break;
  # ---------------------------------------------------------------------------------------- session
  # zapiše resp. přečte z lokálního nastavení SESSION klíč
  case 'session':
    if ( isset($x->get) ) {
      $y->value= $x->get ? $_SESSION[$ezer_root][$x->get] : $_SESSION;
    }
    elseif ( isset($x->set) ) {
      $_SESSION[$ezer_root][$x->set]= $x->value;
//                                                 display("_SESSION[$x->set]= $x->value;");
    }
    break;
  default:
    $y->error= "SERVER: command '{$x->cmd}' is not (yet) implemented";
    break;
  }
# ==========================================================================================> answer
end_switch:
  global $trace, $warning;
  if ( $trace && strpos($x->totrace,'u')!==false )
    $y->trace= $trace;
  if ( $warning ) $y->warning= $warning;
  $y->lc= isset($x->lc) ? $x->lc : '';                 // redukce informace místo $y->x= $x;
  header('Content-type: application/json; charset=UTF-8');
  $y->php_ms= round(getmicrotime() - $php_start,4);
  $yjson= json_encode($y);
  echo $yjson;
  exit;

# ----------------------------------------------------------------------------------- stripSlashes_r
# odstraní slashes ze superglobálních polí -- kvůli magic_quotes_gpc do PHP 5.3
function stripSlashes_r($array) {
  return is_array($array) ? array_map('stripSlashes_r', $array) : stripslashes($array);
}
# ------------------------------------------------------------------------------------- getmicrotime
function getmicrotime() {
  return round(microtime(true)*1000);
}
# ------------------------------------------------------------------------------------- array2object
function array2object(array $array) {
  $object = new stdClass();
  foreach($array as $key => $value) {
    if(is_array($value)) {
      $object->$key = array2object($value);
    }
    else {
      $object->$key = $value;
    }
  }
  return $object;
}
?>
