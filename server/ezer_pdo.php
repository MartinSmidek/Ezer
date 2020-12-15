<?php # (c) 2018 Martin Smidek (martin@smidek.eu)

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
    $err= "databaze '$db_name' je nepristupna pro ";
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
function pdo_qry($qry,$pocet=null,$err=null,$to_throw=null,$db=null) {
  return mysql_qry($qry,$pocet,$err,$to_throw,$db);
}
function pdo_object($qry) {
  return mysql_object($qry);
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
  $res= @mysql_query($qry);
  $time= round(getmicrotime() - $time_start,4);
  $ok= $res ? 'ok' : '--';
  if ( !$res ) {
    if ( $err==='-' ) goto end;
    $merr= mysql_error();
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
      $num= mysql_num_rows($res);
    elseif ( in_array(substr($qry,0,6),array('INSERT','UPDATE','REPLAC','DELETE')) )
      $num= mysql_affected_rows(); // INSERT, UPDATE, REPLACE or DELETE
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
  if ( strpos($totrace,'M')!==false )
    $y->qry.= (isset($y->qry)?"\n":'')."$ok $time \"$myqry\" ";
  $y->qry_ms= isset($y->qry_ms) ? $y->qry_ms+$time : $time;
  $qry_del= "\n: ";
  if ( $msg ) {
    if ( $to_throw ) throw new Exception($err ? "$err$abbr" : $msg);
    else $y->error= (isset($y->error) ? $y->error : '').$msg;
  }
end:
  return $res;
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
  if ( $db=='.main.' || !$db ) {
    reset($ezer_db);
    $db= key($ezer_db);
  }
  // ------------------------------------------- připojení PDO - return vrací PDO objekt!
  if ( isset($ezer_db[$db]) ) {
    if ( !$ezer_db[$db][0] || $even ) {
      // vlastní připojení, pokud nebylo ustanoveno
      $db_name= (isset($ezer_db[$db][5]) && $ezer_db[$db][5]!='') ? $ezer_db[$db][5] : $db;
      $dsn= "mysql:host={$ezer_db[$db][1]};dbname=$db_name;charset={$ezer_db[$db][4]}";
      $opt = [
          PDO::ATTR_ERRMODE            => PDO::ERRMODE_SILENT, //PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES   => false,
          PDO::ATTR_STRINGIFY_FETCHES  => true,
      ];
      try {
                        $errmode= isset($_COOKIE['error_reporting']) ? $_COOKIE['error_reporting'] : 1;
                        if ( $errmode==333) {
                          if (!defined('PDO::ATTR_DRIVER_NAME')) {
                            echo 'PDO unavailable ... ';
                          }
                          else {
                            echo '('.print_r(PDO::getAvailableDrivers(),true).')';
                            echo "$db,$dsn,...";
                          }
                        }
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
  fce_error("connect: nezname jmeno '$db' databaze");
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
function pdo_fetch_all($rs) {
  $rows= $rs->fetchAll();
  return $rows;
}
function pdo_real_escape_string($inp) {
  return str_replace(
      array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), 
      array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 
}
// pdo_query je netrasovaný dotaz - náhrada mysql_query
// pro INSERT|UPDATE|DELETE vrací počet modifikovaných řádků
// pro SELECT vrací PDOStatement
// jinak vrací chybu
function pdo_query($query) {
  global $ezer_db, $curr_db;
  $pdo= $ezer_db[$curr_db][0];
  if ( preg_match('/^\s*(SET|INSERT|UPDATE|REPLACE|DELETE|TRUNCATE|DROP|CREATE|ALTER)/',$query) ) {
    $res= $pdo->exec($query);
    if ( $res===false ) fce_error($pdo->errorInfo()[2]);
  }
  else if ( preg_match('/^\s*(SELECT|SHOW)/',$query) ) {
    $res= $pdo->query($query);
    if ( $res===false ) fce_error($pdo->errorInfo()[2]);
  }
  else {
    fce_error("pdo_query nelze použít pro ".substr($query,0,6).' ...');
  }
  return $res;
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
function pdo_object($qry) {
  $res= pdo_qry($qry,1);
  $x= $res ? pdo_fetch_object($res) : array();
  if ( !$res ) pdo_err($qry);
  return $x;
}
function pdo_affected_rows($res) {       
  // pro kompatibilitu s mysql_affcted_rows
  return ($res===false || is_int($res)) ? $res : $res->rowCount();
}
function mysql_qry($qry,$pocet=null,$err=null,$to_throw=null,$db=null) {
  return pdo_qry($qry,$pocet,$err,$to_throw,$db);
}
# ------------------------------------------------------------------------------------------ pdo qry
# pdo_qry je trasovaný dotaz - náhrada mysql_qry
# pro INSERT|UPDATE|DELETE vrací počet modifikovaných řádků
# pro SELECT vrací PDOStatement
# jinak vrací chybu
# 
# provedení dotazu a textu v $y->qry="..." a případně doplnění $y->err
#   $qry      -- SQL dotaz
#   $pocet    -- pokud je uvedeno, testuje se a při nedodržení se ohlásí chyba
#   $err      -- text chybové hlášky, která se použije místo standardní ... pokud končí znakem':'
#                bude za ni doplněna standardní chybová hláška;
#                pokud $err=='-' nebude generována chyba a funkce vrátí false
#   $to_throw -- chyba způsobí výjimku
#   $db       -- před dotazem je přepnuto na databázi daného jména v tabulce $ezer_db nebo na hlavní
function pdo_qry($qry,$pocet=null,$err=null,$to_throw=false,$db='') {
  global $y, $totrace, $qry_del, $qry_count, $curr_db, $ezer_db;
//  if ( !isset($y) ) $y= (object)array();
  $msg= ''; $abbr= $ok= '';
  $qry_count++;
  $myqry= strtr($qry,array('"'=>"'","<="=>'&le;',"<"=>'&lt;'));
//                                                         display($myqry);
  // dotaz s měřením času
  $time_start= getmicrotime();
  // přepnutí na databázi
  if ( $db ) ezer_connect($db);
  $pdo= $ezer_db[$curr_db][0];
  if ( preg_match('/^\s*(SET|INSERT|UPDATE|REPLACE|DELETE|TRUNCATE|DROP|CREATE|ALTER)/',$qry) ) {
    // pro INSERT|UPDATE|DELETE vrací počet modifikovaných řádků
    $res= $pdo->exec($qry);
    if ( $res===false ) {
      $msg.= $pdo->errorInfo()[2];
    }
    $time= round(getmicrotime() - $time_start,4);
    if ( $pocet  ) {
//      fce_error("pdo_qry: OBSOLETE - 2.parametr (počet záznamů & PHP7/PDO)");
      if ( $pocet!=$res ) {
        if ( $res==0 ) {
          $msg.= "nezmenen zadny zaznam " . ($err ? ", $err" : ""). " v $qry";
          $abbr= '/0';
        }
        else {
          $msg.= "zmeneno $res zaznamu misto $pocet" . ($err ? ", $err" : ""). " v $qry";
          $annr= "/$res";
        }
        if ( isset($y) ) $y->ok= 'ko';
        $ok= "ko [$res]";
        $res= null;
      }
    }
  }
  else if ( preg_match('/^\s*(SELECT|SHOW)/',$qry) ) {
    // pro SELECT vrací PDOStatement
    $res= $pdo->query($qry);
    $time= round(getmicrotime() - $time_start,4);
    $ok= $res ? 'ok' : '--';
    if ( !$res ) {
      if ( $err==='-' ) goto end;
      $merr= $pdo->errorInfo()[2];
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
        $msg.= "\"$myerr\" \nQRY:$qry";
        $abbr= '/E';
      }
      if ( isset($y) ) $y->ok= 'ko';
    }
    else if ( $pocet  ) {
//      fce_error("pdo_qry: OBSOLETE - 2.parametr (počet záznamů & PHP7/PDO)");
      $num= pdo_num_rows($res);
      if ( $pocet!=$num ) {
        if ( $num==0 ) {
          $msg.= "nenalezen záznam " . ($err ? ", $err" : ""). " v $qry";
          $abbr= '/0';
        }
        else {
          $msg.= "vraceno $num zaznamu misto $pocet" . ($err ? ", $err" : ""). " v $qry";
          $annr= "/$num";
        }
        if ( isset($y) ) $y->ok= 'ko';
        $ok= "ko [$num]";
        $res= null;
      }
    }
  }
  else {
    fce_error("pdo_qry nelze použít pro ".substr($qry,0,6).' ...');
  }
  if ( strpos($totrace,'M')!==false ) {
    $pretty= trim($myqry);
    if ( strpos($pretty,"\n")===false )
      $pretty= preg_replace("/(FROM|LEFT JOIN|JOIN|WHERE|GROUP|HAVING|ORDER)/","\n\t\$1",$pretty);
    if ( isset($y) ) $y->qry= (isset($y->qry)?"$y->qry\n":'')."$ok $time \"$pretty\" ";
  }
  if ( isset($y) ) $y->qry_ms= isset($y->qry_ms) ? $y->qry_ms+$time : $time;
  $qry_del= "\n: ";
  if ( $msg ) {
    if ( $to_throw ) throw new Exception($err ? "$err$abbr" : $msg);
    elseif ( isset($y) ) $y->error= (isset($y->error) ? $y->error : '').$msg;
    else fce_error($msg);
  }
end:
  return $res;
}
}
