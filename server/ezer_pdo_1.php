<?php # (c) 2018 Martin Smidek (martin@smidek.eu)

const EZER_PDO_PORT=2;    // přepínač pro fáze migrace pod PDO

/** návrh postupu migrace z mysql_ do PDO
 * 
 * 1) je třeba provést mechanické přejmenování mysql_X na pdo_X 
 * 
 * 2) EZER_PDO_PORT=1 
 *    kontrola pod PHP5, zda po přejmenování vše funguje
 *    aplikace stále běží pod mysql_
 * 
 * 3) překódování samostatně se vyskytujících pdo_connect, pdo_connect, pdo_select_db
 *    je třeba je převést na ezer_connect, udržující $curr_db pro pdo_insert_id aj.
 * 
 * 4) EZER_PDO_PORT=2 
 *    kontrola funkcionality pod PHP5 
 *    úprava ostatního kódu pod PHP7 a kontrola syntaxe 
 *      - nahrazení $json->* funkcí json_*
 * 
 * 5) postupná optimalizace vytížených částí s využitím potence PDO  např. 
 *      quote místo pdo_real_escape_string
 *      exec  místo query(INSERT ...)
 *      prepare
 *      ...
 */
# ------------------------------------------------------------------------------------- driver mysql   
if ( EZER_PDO_PORT==1 ) {
# ------------------------------------------------- ezer_connect
# spojení s databází
# $db = jméno databáze uvedené v konfiguraci aplikace
# $db = .main. pokud má být připojena první databáze z konfigurace
# $initial=1 pokud není ještě aktivní fce_error
function ezer_connect ($db0='.main.',$even=false,$initial=0) {
  global $ezer_db;
  $err= '';
  $db= $db0;
  if ( $db=='.main.' ) {
    reset($ezer_db);
    $db= key($ezer_db);
  }
  // vlastní připojení, pokud nebylo ustanoveno
  $db_name= (isset($ezer_db[$db][5]) && $ezer_db[$db][5]!='') ? $ezer_db[$db][5] : $db;
  if ( !$ezer_db[$db][0] || $even ) {
    $ezer_db[$db][0]= @mysql_pconnect($ezer_db[$db][1],$ezer_db[$db][2],$ezer_db[$db][3]);
    if ( !$ezer_db[$db][0] ) {
      fce_error("db=$db|connect: server '{$ezer_db[$db][1]}' s databazi '"
        . ($ezer_db[$db][5] ? "$db/$db_name" : $db)."' neni pristupny:").pdo_error();
    }
  }
  $res= @mysql_select_db($db_name,$ezer_db[$db][0]);
  if ( !$res ) {
    $err= "databaze '$db_name' je nepristupna";
    if ( !$initial ) fce_error("connect: $err".pdo_error());
    else die("connect: $err".pdo_error());
  }
  if ( $ezer_db[$db][4] ) {
    pdo_query("SET NAMES '{$ezer_db[$db][4]}'");
  }
  return $err;
}
# ------------------------------------------------- pdo funkce
function pdo_num_rows($rs) {
  return mysql_num_rows($rs);
}
function pdo_result($rs,$cnum) {
  return mysql_result($rs,$cnum);
}
function pdo_fetch_object($rs) {
  return mysql_fetch_object($rs);
}
function pdo_fetch_assoc($rs) {
  return mysql_fetch_assoc($rs);
}
function pdo_fetch_row($rs) {
  return mysql_fetch_row($rs);
}
function pdo_fetch_array($rs) {
  return mysql_fetch_array($rs);
}
function pdo_real_escape_string($inp) {
  return mysql_real_escape_string($inp);
}
function pdo_query($query) {
  return mysql_query($query);
}
function pdo_insert_id() {
  return mysql_insert_id();
}
function pdo_error() {    
  return mysql_error();
}
function pdo_affected_rows() {
  return mysql_affected_rows();
}
function pdo_qry($qry,$pocet=null,$err=null,$to_throw=false,$db='.main.') {
  return mysql_qry($qry,$pocet,$err,$to_throw,$db);
}
}
# --------------------------------------------------------------------------------------- driver PDO
if ( EZER_PDO_PORT==2 ) {
# ------------------------------------------------- ezer_connect
# spojení s databází
# $db = jméno databáze uvedené v konfiguraci aplikace
# $db = .main. pokud má být připojena první databáze z konfigurace
# $initial=1 pokud není ještě aktivní fce_error
function ezer_connect ($db0='.main.',$even=false,$initial=0) {
  global $curr_db, $ezer_db;
  $err= '';
  $db= $db0;
  if ( $db=='.main.' ) {
    reset($ezer_db);
    $db= key($ezer_db);
  }
  // ------------------------------------------- připojení PDO - return vrací PDO objekt!
  if ( !$ezer_db[$db][0] || $even ) {
    // vlastní připojení, pokud nebylo ustanoveno
    $db_name= (isset($ezer_db[$db][5]) && $ezer_db[$db][5]!='') ? $ezer_db[$db][5] : $db;
    $dsn= "mysql:host={$ezer_db[$db][1]};dbname=$db_name;charset={$ezer_db[$db][4]}";
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_SILENT, //PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
      $ezer_db[$db][0]= new PDO($dsn, $ezer_db[$db][2], $ezer_db[$db][3], $opt);
    } 
    catch(PDOException $ex) {
      $err= "connect: databaze '$db_name' je nepristupna: ".$ex->getMessage();
      if ( !$initial ) fce_error($err);
      else die($err);
    }
  }
  $curr_db= $db;
  return $ezer_db[$db][0];
}
# ------------------------------------------------- pdo funkce
function pdo_num_rows($rs) {
  $num= $rs->rowCount();
  return $num;
}
function pdo_result($rs,$cnum) {
  $mix= $rs->fetchColumn($cnum);
  return $mix;
}
function pdo_fetch_object($rs) {
  $row= $rs->fetch(PDO::FETCH_OBJ);
  return $row;
}
function pdo_fetch_assoc($rs) {
  $row= $rs->fetch(PDO::FETCH_ASSOC);
  return $row;
}
function pdo_fetch_row($rs) {
  $row= $rs->fetch(PDO::FETCH_NUM);
  return $row;
}
function pdo_fetch_array($rs) {
  $row= $rs->fetch(PDO::FETCH_BOTH);
  return $row;
}
function pdo_real_escape_string($inp) {
  return str_replace(
      array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), 
      array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 
}
function pdo_query($query) {
  global $ezer_db, $curr_db;
  $pdo= $ezer_db[$curr_db][0];
  if ( preg_match('/^\s*(SET|INSERT|UPDATE)/',$query) ) 
    return $pdo->exec($query);
  else
    return $pdo->query($query);
}
function pdo_insert_id() {              
  global $ezer_db, $curr_db;
  $pdo= $ezer_db[$curr_db][0];
  $id= $pdo->lastInsertId();
  return $id;
}
function pdo_error() {                
  global $ezer_db, $curr_db;
  $pdo= $ezer_db[$curr_db][0];
  $err= $pdo->errorInfo();
  return "You have an error in your SQL:".$err[2];
}
function pdo_affected_rows() {          
  global $ezer_db, $curr_db;
  $pdo= $ezer_db[$curr_db][0];
  return $pdo->rowCount();
}
function pdo_qry($qry,$pocet=null,$err=null,$to_throw=false,$db='.main.') {
  return mysql_qry($qry,$pocet,$err,$to_throw,$db);
}
}
?>
