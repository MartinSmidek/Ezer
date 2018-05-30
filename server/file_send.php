<?php # (c) 2008 Martin Smidek <martin@smidek.eu>
error_reporting(E_ALL & ~E_NOTICE);
session_start();
if ( isset($_GET['name']) ) {
  # --------------------------------------------------------------------------------------- download
  $filename= $_GET['name'];
  $title= isset($_GET['title']) ? $_GET['title'] : $filename;
  // test přihlášení
  $er= 0;
  $ezer_root= isset($_GET['root']) ? $_GET['root'] : null;
  if ( !$ezer_root ) {
    $er= "soubory lze stahnout jen z prostredi Ezeru"; goto err;
  }
  $user_id= isset($_SESSION[$ezer_root]) && isset($_SESSION[$ezer_root]['user_id'])
          ? $_SESSION[$ezer_root]['user_id'] : 0;
  if ( !$user_id ) {
    $er= "uzivatel neni prihlasen"; goto err;
  }
  $path_files= trim($_SESSION[$ezer_root]['path_files_h']," '");
  $path_files= rtrim($path_files,"/");
  // zjištění cesty a existence souboru uloženého do úložiště H
  if ( !file_exists($path_files)) {
    $er= "cesta '$path_files' ke slozce neni dostupna"; goto err;
  };
  $file= str_replace('//','/',"$path_files/$filename");
  if ( !file_exists($file)) {
    $er= "soubor '$file' neni dostupny"; goto err;
  };
  // zjištění, zda $file je odkaz tzn. title končí na *
  if ( substr($title,-1,1)=='*' ) {
    $file2= trim(file_get_contents($file));
    $dir2= substr($path_files,0,strrpos($path_files,'/'));
    // náhrada cesty k souboru podle odkazu
    $file= "$dir2/$file2";
  }
  // zjištění typu
  $f= pathinfo($file);
  $fext= substr(strtolower($f['extension']),0,3);
  // poslání souboru
//   header('Content-Description:File Transfer');
  switch ($fext) {
  case 'pdf':
              header('Content-Type:application/pdf');
//               header('Content-Type:application/x-google-chrome-pdf');
              header("Content-Disposition: inline; filename=\"$title\";");
              header('Content-Transfer-Encoding:binary');
              break;
  case 'png': header('Content-Type:image/png');
              header("Content-Disposition: inline; filename=\"$title\";");
              break;
  case 'jpe':
  case 'jpg': header('Content-Type:image/jpeg');
              header("Content-Disposition: inline; filename=\"$title\";");
              break;
  default:    header('Content-Type: application/octet-stream');
              header("Content-Disposition: attachment; filename=\"$title\";");
              break;
  }
//   header('Expires: 0');
//   header('Cache-Control: must-revalidate');
//   header('Pragma: public');
  header('Content-Length:' . filesize($file));
  readfile($file);
//   echo("$fext*$title");
  exit;
err:
  echo("soubor '$filename' nelze zobrazit: $er");
}
else {
  # ----------------------------------------------------------------------------------------- upload
  $name=   $_SERVER['HTTP_EZER_FILE_NAME'];
  $name=   utf2ascii(urldecode($name));
  $chunk=  $_SERVER['HTTP_EZER_FILE_CHUNK'];
  $chunks= $_SERVER['HTTP_EZER_FILE_CHUNKS'];
  $path=   isset($_SERVER['HTTP_EZER_FILE_RELPATH'])
         ? $_SERVER['DOCUMENT_ROOT'].'/'.$_SERVER['HTTP_EZER_FILE_RELPATH']  // S: relativní
         : $_SERVER['HTTP_EZER_FILE_ABSPATH'];                               // S: H: absolutní
  $path= str_replace('//','/',$path);
  $pname= stripslashes("$path/$name");

  $data= file_get_contents("php://input");
  $end= "";
  // nastavení začátku
  $err= $war= '';
  $size= 0;
  if ( $chunk==1 ) {
    $_SESSION['upload'][$name]= array();
    if ( file_exists($pname) ) {
      $war= "WARNING soubor $pname již existuje, lze smazat přes kontextové menu."
          . " Pokud mezi soubory není, změň jméno souboru.";
      goto end;
    }
  }
  // test konce
  if ( count($_SESSION['upload'][$name])==($chunks-1) ) {
    // poskládej a ulož soubor
    $errmsg0= "ERROR soubor $name byl přenesen ale nelze vytvořit složku '$path' na serveru";
    $errmsg1= "ERROR soubor $name byl přenesen ale nelze zapsat do složky '$path' na serveru";
    $errmsg2= "ERROR soubor $name byl přenesen ale má nulovou délku";
    if ( !file_exists($path) ) {
      // založení složky
      $dir= rtrim($path,"/");
      $ok= 1;
      if ( !file_exists($dir) ) {
        $ok= mkdir($dir,0777,1);
      }
      if ( !$ok ) { $err= $errmsg0; goto end; }
    }
    // zapsání souboru
    $f= @fopen($pname,'w');
    if ( !$f ) { $err= $errmsg1; goto end; }
    for  ($i= 1; $i<=$chunks; $i++) {
      if ( $i==$chunk) {
        $stat= fwrite($f,$data);
        if ( $stat===false ) { $err= $errmsg1; goto end; }
      }
      else {
        $x= $_SESSION['upload'][$name][$i];
        $stat= fwrite($f,$x);
        if ( $stat===false ) { $err= $errmsg1; goto end; }
      }
    }
    fclose($f);
    if ( file_exists($pname) ) {
      $size= filesize($pname);
      if ( !$size ) { $err= $errmsg2; goto end; }
    }
    else { $err= $errmsg1; goto end; }
  }
  else {
    // uložení části
    $_SESSION['upload'][$name][$chunk]= $data;
  }
  // návrat hodnoty
end:
  //     0     1              2     3     4    5    6
  $ret= "$name|$chunk/$chunks|$path|$size|$end|$err|$war";
  echo $ret;
  exit;
}
# ---------------------------------------------------------------------------------------- utf2ascii
# konverze z UTF-8 do písmen, číslic, teček a podtržítka, konvertují se i html entity
function utf2ascii($val) {
  $txt= preg_replace('~&(.)(?:acute|caron);~u', '\1', $val);
  $txt= preg_replace('~&(?:nbsp|amp);~u', '_', $txt);
  $ref= preg_replace('~[^\\pL0-9_\-\.]+~u', '_', $txt);
  $ref= trim($ref, "_");
//     setLocale(LC_CTYPE, "cs_CZ.utf-8");                      bohužel nebývá nainstalováno
//     $url= iconv("utf-8", "us-ascii//TRANSLIT", $url);
  $ref= strtr($ref,array('ě'=>'e','š'=>'s','č'=>'c','ř'=>'r','ž'=>'z','ý'=>'y','á'=>'a','í'=>'i',
                         'é'=>'e','ů'=>'u','ú'=>'u','ó'=>'o','ď'=>'d','ť'=>'t','ň'=>'n'));
  $ref= strtr($ref,array('Ě'=>'E','Š'=>'S','Č'=>'C','Ř'=>'R','Ž'=>'Z','Ý'=>'Y','Á'=>'A','Í'=>'I',
                         'É'=>'E','Ů'=>'U','Ú'=>'U','Ó'=>'O','Ď'=>'D','Ť'=>'T','Ň'=>'N'));
  $ref= mb_strtolower($ref);
  $ref= preg_replace('~[^-a-z0-9_\.]+~', '', $ref);
  return $ref;
}
?>
