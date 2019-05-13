<?php # (c) 2007-2018 Martin Smidek (martin@smidek.eu)
/**
 * @par přihlášky na akce
 * 
 * objekt $EZER musí mít v rootu aplikace nastaveny následující parametry 
 *   
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
# ------------------------------------------------------------------------------------- cms def_test
/**
 * nastavení testovacího modu
 * @param bool $on = 1 zapne testovací větve
 */
function cms_def_test($on) {
  global $EZER;
  $EZER->CMS->TEST= $on;
  return 1;
}
# --------------------------------------------------------------------------------------- cms server
/**
 * AJAX server pro dotazy s nastaveným cms=1
 * @param object $y dotaz a zároveň odpověď, *y.cmd* určuje operaci serveru 
 */
function cms_server(&$y) {
  global $EZER, $ezer_root, $ezer_db;
  $FORM= $EZER->CMS->FORM[$y->par->form];
//  $TEXT= $FORM['TEXT'];
  $ELEM= $FORM['ELEM'];
  $SQL=  $FORM['SQL'];
  
  $TEXT= function ($i) use ($EZER,&$FORM) {
    $tst= $EZER->CMS->TEST;
    $txt= isset($FORM['TEXT'][$i]) ? $FORM['TEXT'][$i] : "undefined";
    $txt= $tst ? "TEXT '$i'=> $txt" : $txt;
    return $txt;
  };

  switch ( $y->cmd ) {
    
  // ------------------------------------------------------ full
  /// @par cmd=CMS_full
  /// zjistí, zda je akce plná
  case 'CMS_full': 
    $y->par->full= call_user_func($FORM['CALL']['full_A'],$y->par->ida);
    break;

  // ------------------------------------------------------ mail
  /// @par cmd=CMS_mail
  /// zjistí, zda existuje mail
  case 'CMS_mail': 
    $y->ok= $y->mail!=''; 
    if ( !$y->ok ) {
      $y->info= $TEXT('CMS_mail_error_1');
      goto end;
    }
    $err='';
    $y->ok= $EZER->CMS->TEST|| cms_mail_valid($y->mail,$err) ? 1 : 0;
    if ( $y->ok ) {
      // zjisti, zda známe mail a zda je jednoznačný
      ezer_connect($SQL['mail'][0]);
      $qry= strtr($SQL['mail'][1],array('{MAIL}'=>$y->mail));
      $res= pdo_query($qry);
      $y->qry= $qry;
      $y->res= $res?1:0;
      if ( !$res ) {
        $y->state= 'error';
        $y->info= "chyba při předání mailu";
      }
      if ( $res ) {
        $num= pdo_num_rows($res);
        if ( $num==0 ) {
          // mail neznáme
          $y->ido= 0;
        }
        elseif ( $num==1 ) {
          // mail je jednoznačný
          list($y->ido)= pdo_fetch_row($res);
        }
        else {
          // nejednoznačný mail
          $y->ok= 0;
          $y->state= 'warning';
          $y->info= $TEXT('CMS_mail_error_2');
        }
        if ( $y->ok ) {
          // vytvoř pin a zapamatuj i s mailem v session
          $pin= rand(1000,9999);
          $_SESSION[$ezer_root]['pin']= $pin;
          $_SESSION[$ezer_root]['mail']= $y->mail;
          // odešli mail
          $body= strtr($TEXT('CMS_mail_txt'),array('{PIN}'=>$pin,'{AKCE}'=>$y->par->akce));
          if ( $y->test ) { // *DBG*
            $y->state= 'wait'; // *jako* čekáme na zadání PINu z mailu
            $y->pin= $pin;
            $y->info= 'Mail se v TESTu neposílá. ';
          }
          else {        
            $m= cms_mail_send($y->mail, "Přihlášení na {$y->par->akce}",$body); 
                                                                                           goto end;
            $y->ok= $m ? $m->ok : 0;
          }
          if ( $y->ok ) {
            $y->state= 'wait'; // čekáme na zadání PINu z mailu
            $y->info.= $TEXT('CMS_mail_1');
          }
          else {
            $y->state= 'error';
            $y->info= strtr($TEXT('CMS_mail_error_3'),array('{SMTP}'=>$m->msg));
          }
        }
      }
    }
    else {
      $y->info= strtr($TEXT('CMS_mail_error_4'),array('{MAIL}'=>$y->mail,'{MSG}'=>$err));
    }
    break;

  // ------------------------------------------------------ pin
  /// @par cmd=CMS_pin
  /// otestuje shodu pinu a 
  /// * pro známého účastníka vyzve k úpravám osobních údajů a vrátí state=old
  /// * pro neznámého vrátí state=new (je připravená výzva k vyplnění údajů)
  case 'CMS_pin': 
    $y->state= 'error';
    $y->ok= $y->pin!=''; 
    if ( !$y->ok ) { $y->info= $TEXT('CMS_pin_error_1'); goto end; }
    $y->ok= $_SESSION[$ezer_root]['pin']==$y->pin;
    if ( !$y->ok ) { $y->info= $TEXT('CMS_pin_error_2'); goto end; }
    if ( $y->ido ) {
      ezer_connect($SQL['select_O'][0]);
      $y->data= (object)array();
      foreach (array('O','R') as $X) {
        $fields= ''; $del= '';
        foreach (array_keys($ELEM) as $Xname) {
          if ( substr($Xname,0,1)===$X ) {
            $name= substr($Xname,1);
            $fields.= "$del$name"; $del= ',';
          }
        }
        $qry= strtr($SQL["select_$X"][1],array('{IDO}'=>$y->ido,'{IDA}'=>$y->par->ida,'{*}'=>$fields));
        $res= pdo_query($qry);
        foreach (pdo_fetch_object($res) as $name=>$value) {
          $Xname= "$X$name";
          // případná transformace dat podle typu
          switch ( $ELEM[$Xname][0] ) {
            case 'h':  $y->data->$Xname= $value; break;
            case 'c':  $y->data->$Xname= $value; break;
            case 't':  $y->data->$Xname= $value; break;
            case 'd':  $y->data->$Xname= sql_date1($value); break;
          }
        }
      }
      $y->state= 'old';
      $y->info= $TEXT('CMS_pin_1');
    }
    else {
      $y->state= 'new';
      $y->info= $TEXT('CMS_pin_2');
    }
    break;

  // ------------------------------------------------------ family
  /// @par cmd=CMS_family
  /// otestuje shodu pinu a 
  /// * pro známého účastníka vyzve k úpravám osobních údajů a vrátí state=old
  /// * pro neznámého vrátí state=new (je připravená výzva k vyplnění údajů)
  case 'CMS_family': 
    $y->state= 'error';
    $ido= $y->ido;
    $ida= $y->par->ida;
    $cleni= call_user_func($FORM['CALL']['family_?'],$ido,$ida);
    $y->info= $cleni ? $TEXT('CMS_family_2') : $TEXT('CMS_family_3');
    $y->memb= $cleni;
    break;

  // ------------------------------------------------------ submit
  /// @par cmd=CMS_submit
  /// převezme vyplněná data, zapíše změny do databáze
  case 'CMS_submit': 
    $O= $SQL['O'];
    $R= $SQL['R'];
    $ido= $y->ido;
    $ida= $y->par->ida;
    $changes= 0; // pro zápis pomocí 'detail_R'
    ezer_connect($SQL['select_O'][0]);
    if ( $ido ) {
      // vyzvednutí starých údajů
      $old_o= mysql_row(strtr($SQL["select_O"][1],array('{IDO}'=>$ido,'{IDA}'=>$ida,'{*}'=>'*')));
    }
    // příprava seznamu změn
    $chngs_o= $chngs_r= array();
    $real_chngs= array('O'=>0,'R'=>0);
    $op= $ido ? 'u' : 'i';
    $chngs= (array)$y->chngs;
    if ( count($chngs) ) {
      // posbírej změny osoby a relace
      foreach ($chngs as $nv ) {
        $Xname= $nv->name;
        $X= substr($Xname,0,1);
        $name= substr($Xname,1);
        $type= $ELEM[$Xname][0];
        // případná transformace dat podle typu
        switch ( $type ) {
          case 'h':  $value= $nv->value; break;
          case 'c':  $value= $nv->value; $real_chngs[$X]++; break;
          case 't':  $value= $nv->value; $real_chngs[$X]++; break;
          case 'd':  $value= sql_date1($nv->value,1); $real_chngs[$X]++; break;
        }
        // vložení do změnového objektu
        if ( $X==='O' ) {
          if ( $ido  ) 
            $chngs_o[]= (object)array('fld'=>$name,'op'=>$op,'old'=>$old_o[$name],'val'=>$value);
          elseif ( !$ido )
            $chngs_o[]= (object)array('fld'=>$name,'op'=>$op,'val'=>$value);
        }
        else if ( $X==='R' )
          $chngs_r[]= (object)array('fld'=>$name,'op'=>$op,'val'=>$value);
      }
    }
    // OSOBA - oprava nebo vložení osoby
    if ( $ido && $real_chngs['O']  ) {
      // pokud se zaznamenává den změny 
      $changes|= 8;
      if ( isset($SQL['Ochange']) )  
        $chngs_o[]= (object)array('fld'=>$SQL['Ochange'],'op'=>'u','val'=>date('Y-m-d'));
      // pokud je mezi CALL update_O předej parametry 
      if ( isset($FORM['CALL']['update_O']) ) {
        $ok= call_user_func($FORM['CALL']['update_O'],"UPDATE",$O[0],$ido,$chngs_o,$O[1]);
        if ( !$ok ) { $y->info.= $TEXT('CMS_submit_error_2').' '; goto end; } 
      }
      else {
        // jinak proveď update standardně
        ezer_connect($SQL['select_O'][0]);
        ezer_qry("UPDATE",$O[0],$ido,$chngs_o,$O[1]);
      }
      $y->info.= $TEXT('CMS_submit_1').' ';
    }  
    else if ( !$ido ) {
      // OSOBA - k vytvářené osobě přidej mezi vyplněné položky také vyplněnou mailovou adresu
      $changes|= 4;
      $chngs_o[]= (object)array('fld'=>$O[2],'op'=>'i','val'=>$y->mail);
      // pokud se zaznamenává den změny 
      if ( isset($SQL['Ochange']) ) { 
        $chngs_o[]= (object)array('fld'=>$SQL['Ochange'],'op'=>'i','val'=>date('Y-m-d'));
      }
      // pokud je mezi CALL insert_O předej parametry 
      if ( isset($FORM['CALL']['insert_O']) ) {
        $ido= call_user_func($FORM['CALL']['insert_O'],"INSERT",$O[0],0,$chngs_o,$O[1]);
        if ( !$ido ) { $y->info.= $TEXT('CMS_submit_error_4').' '; goto end; } 
      }
      else {
        ezer_connect($SQL['select_O'][0]);
        $ido= ezer_qry("INSERT",$O[0],0,$chngs_o,$O[1]);
      }
      $y->info.= $TEXT('CMS_submit_2').' ';
    }
    // doplň případné další osoby z y.join
    if ( count($y->join) ) {
      
    }
    // pokud je zpracováván souhlas, zapiš jej
    if ( in_array('confirm',$FORM['TYPE']) && isset($FORM['CALL']['confirm_O']) ) {
      $changes|= 8;
      call_user_func($FORM['CALL']['confirm_O'],$ido,$ida);
    }
    // RELACE - otestování, zda na akci již není přihlášen
    $qry= strtr($SQL['select_R'][1],array('{IDO}'=>$ido,'{IDA}'=>$ida,'{*}'=>$R[1]));
    list($idr)= pdo_fetch_array(pdo_query($qry));
    if ( $idr && count($chngs_r) ) {
      $changes|= 2;
      // zjisti staré údaje kvůli zápisu do _track
      $old_r= mysql_row("SELECT * FROM $R[0] WHERE $R[1]=$idr");
      for ($i= 0; $i<count($chngs_r); $i++) {
        $chngs_r[$i]->old= $old_r[$chngs_r[$i]->fld];
      }
      // pokud se zaznamenává den změny 
      if ( isset($SQL['Rchange']) ) { 
        $chngs_r[]= (object)array('fld'=>$SQL['Rchange'],'op'=>'u','val'=>date('Y-m-d'));
      }
      // oprava údajů v relaci
      ezer_connect($SQL['select_R'][0]);
      ezer_qry("UPDATE",$R[0],$idr,$chngs_r,$R[1]);
      $y->info.= $TEXT('CMS_submit_3').' ';
    }
    else if ( $idr && !count($chngs_r) ) {
      $y->info.= $TEXT('CMS_submit_4').' ';
    }
    else if ( !$idr ) {
      // vytvoření relace R - přihlášení osoby na akci
      $changes|= 1;
      $chngs_r[]= (object)array('fld'=>$R[2],'op'=>'i','val'=>$ido);
      $chngs_r[]= (object)array('fld'=>$R[3],'op'=>'i','val'=>$ida);
      // pokud se zaznamenává den změny 
      if ( isset($SQL['Rchange']) ) { 
        $chngs_r[]= (object)array('fld'=>$SQL['Rchange'],'op'=>'i','val'=>date('Y-m-d'));
      }
      // pokud je mezi CALL insert_R předej parametry 
      if ( isset($FORM['CALL']['insert_R']) ) {
        $idr= call_user_func($FORM['CALL']['insert_R'],"INSERT",$R[0],0,$chngs_r,$R[1]);
        if ( !$idr ) { $y->info.= $TEXT('CMS_submit_error_2').' '; goto end; } 
      }
      else {
        // jinak proveď insert standardně
        ezer_connect($SQL['select_R'][0]);
        $idr= ezer_qry("INSERT",$R[0],0,$chngs_r,$R[1]);
      }
      $y->info.= $TEXT('CMS_submit_5').' ';
    }
    // pokud jsou přihlašování i rodinní příslušníci
    if ( in_array('family',$FORM['TYPE']) ) {
      $ok= call_user_func($FORM['CALL']['family_!'],$ido,$ida,$y->join);
      if ( !$ok ) { $y->info.= $TEXT('CMS_family_error_1').' '; goto end; } 
    }
    // pokud se mají zaznamenávat detaily změn do R
    if ( isset($FORM['CALL']['changes_R']) ) {
      $ok= call_user_func($FORM['CALL']['changes_R'],$idr,$changes);
    }
    // pokud se má odeslat potvrzující mail
    if ( in_array('send_mail',$FORM['TYPE']) ) {
      $obj_mail= call_user_func($FORM['CALL']['sendmail_OA'],$y->mail,$ido,$ida);
      if ( !$obj_mail->ok ) { 
        $msg= isset($obj_mail->msg) ? $obj_mail->msg : '';
        $y->info.= $TEXT('CMS_send_mail_error')." $msg"; 
        goto end; 
      } 
    }
    $y->ok= 1;
    break;
    
  }
end:  
  return 1;
}
# ------------------------------------------------------------------------------------- cms form_ref
/**
 * @brief Vygeneruje odkaz, po kliknutí bude zobrazena přihláška - viz prihlaska_body()
 * @param string $title - text odkazu
 * @param string $form - název formuláře v poli $EZER->CMS
 * @param string $ida - id akce
 * @param string $akce - název akce
 * @return html
 */
function cms_form_ref($title,$form=null,$ida=0,$akce=0) { trace();
  $html= '';
  if ( !$form ) {
    $html.= "<span class='cms_form'>$title</span>";
  }
  else {
    // identifikace prohlížeče a platformy prohlížeče: pro IE to nepůjde
    $ua= $_SERVER['HTTP_USER_AGENT'];
    ezer_browser($browser,$browser_version,$platform,$ua);
    if ( $browser=='IE' ) {
      $omluva= 'Omlouváme se, ale z prohlížeče Internet Exlorer není online přihlášení možné. '
          . 'Přihlašte se prosím náhradním způsobem, uvedeným na stránce. '
          . 'Nebo k přihlášení použijte prohlížeč Chrome či Firefox či Edge.';
      $html.= "<span class='cms_form' 
                onclick=\"alert('$omluva');\">
              $title</span>";
    }
    else {
      // generování odkazu na přihlášku
      $asgn= cms_form_def($form);
      $html.= "<script>$asgn</script>";
      $html.= "<span class='cms_form' 
                onclick=\"cms_form('cms_create',{form:'$form',ida:'$ida',akce:'$akce',title:'$title'});\">
              $title</span>";
    }
  }
  return $html;
}
# ------------------------------------------------------------------------------------- cms form_def
/**
 * @brief vygeneruje přiřazovací příkaz pro přesun informace z $EZER->CMS->FORM do Ezer.cms.form
 * @param string $form - název formuláře v poli $EZER->CMS
 * @return string
 */
function cms_form_def($form) {
  global $EZER;
  $asgn= '';//"fe_init();";
  $asgn.= "Ezer.cms.form.$form=".json_encode($EZER->CMS->FORM[$form]).";";
  $asgn.= "Ezer.cms.test={$EZER->CMS->TEST};";
  return $asgn;
}
# ----------------------------------------------------------------------------------- cms mail_valid
/**
 * tells you if an email is in the correct form or not 
 * http://www.kirupa.com/forum/showthread.php?t=323018
 * @param string $email proposed email address
 * @param string $reason error description (Czech)
 * @return boolean
 */
function cms_mail_valid($email,&$reason) {
  $ok= true;
  $reasons= array();
  $atIndex= strrpos($email, "@");
  if (is_bool($atIndex) && !$atIndex) {
    $ok= false; $reasons[]= "chybí @";
  }
  else {
    $domain= substr($email, $atIndex+1);
    $local= substr($email, 0, $atIndex);
    $localLen= strlen($local);
    $domainLen= strlen($domain);
    if ($localLen < 1 || $localLen > 64) {
      $ok= false; $reasons[]= "dlouhé jméno";
    }
    else if ($domainLen < 1 || $domainLen > 255) {
      $ok= false; $reasons[]= "dlouhá doména";
    }
    else if ($local[0] == '.' || $local[$localLen-1] == '.') {
      $ok= false; $reasons[]= "tečka na kraji";
    }
    else if (preg_match('/\\.\\./', $local)) {
      $ok= false; $reasons[]= "dvě tečky ve jménu";
    }
    else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
      $ok= false; $reasons[]= "chybný znak v doméně";
    }
    else if (preg_match('/\\.\\./', $domain)) {
      $ok= false; $reasons[]= "dvě tečky v doméně";
    }
    else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', 
        str_replace("\\\\","",$local))) {
      $reasons[]= "chybný znak ve jménu";
      if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))) {
        $ok= false;
      }
    }
    if ( $domain!='proglas.cz' && $domain!='setkani.org' ) {
      if ($ok && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
        $reasons[]= "$domain je neznámá doména";
        $ok= false;
      }
    }
  }
  $reason= count($reasons) ? implode(', ',$reasons) : '';
  return $ok;
}
# ------------------------------------------------------------------------------------ cms mail_send
/**
 * Pošle mail přes SMTP službu pod gmailem
 * @param string $address adresa příjemnce mailu
 * @param string $subject předmět mailu
 * @param string $body text mailu
 * @param string $reply_to nepovinná adresa pro odpověď
 * @return object {ok:0/1,msg:''/popis chyby}
 */
function cms_mail_send($address,$subject,$body,$reply='') { 
  global $EZER;
  $ret= (object)array('ok'=>1,'msg'=>'');
  require 'ezer3.1/server/vendor/autoload.php';
  // nastavení phpMail
  $mail= new PHPMailer(true);
  try {
    $mail->SMTPDebug = 0;
    $mail->SetLanguage('cs');//,"$phpmailer_path/language/");
    $mail->IsSMTP();
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->SMTPSecure= "ssl"; // sets the prefix to the server
    $mail->Host= "smtp.gmail.com"; // sets GMAIL as the SMTP server
    $mail->Port= 465; // set the SMTP port for the GMAIL server
    $mail->Username= $EZER->CMS->GMAIL->mail;
    $mail->Password= $EZER->CMS->GMAIL->pswd;
    $mail->CharSet= "UTF-8";
    $mail->IsHTML(true);
    // zpětné adresy
    $mail->ClearReplyTos();
    $mail->AddReplyTo($reply ? $reply : $EZER->CMS->GMAIL->mail);
    $mail->SetFrom($EZER->CMS->GMAIL->mail, $EZER->CMS->GMAIL->name);
    // vygenerování mailu
    $mail->Subject= $subject;
    $mail->Body= $body;
    // přidání příloh
    $mail->ClearAttachments();
    // přidání adresy
    $mail->ClearAddresses();
    $mail->AddAddress($address);
    // přidání kopií
    $mail->ClearCCs();
    if ( $reply )
      $mail->AddCC($reply);
    if ( $EZER->CMS->TEST ) {
      $ret->msg= "TESTOVÁNÍ - vlastní mail.send je vypnuto";
    }
    else {
    // odeslání mailu
      $mail->send();
      $mail->SmtpClose();
    }
  }
  catch (Exception $e) {
    $ret->msg= $mail->ErrorInfo;
    $ret->ok= $e;
    $ret->ok= 0;
  }
  return $ret;
}
?>
