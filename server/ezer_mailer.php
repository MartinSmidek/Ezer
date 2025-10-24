<?php
/**
 * (c) 2025 Martin Smidek <martin@smidek.eu> - rozšíření PHPMailer pro framework Ezer3.3
 *                                                                                ------- 
 * $mail= new Ezer_PHPMailer($x)
 *   pro gmail 
 *     $x= {Host:smtp.google.com,Username,files_path:cesta k creditals a tokens}
 *   pro seznam a jiné
 *     $x= {Host:smtp server,Port,Username,Password,[SMTPOptions]}
 *       pokud SMTPOptions='-'
 *       bude přidáno [ssl => [verify_peer=>false,verify_peer_name=>false,allow_self_signed=>true]]
 * 
 * $mail->Ezer_Send() odešle mail a vrátí 'ok' nebo text chyby
 *   pro gmail službou Google_Service_Gmail_Message 
 *   jinak $mail->Send
 */

spl_autoload_register(function ($class) {
  global $abs_root;
  $server= "$abs_root/ezer3.3/server";
  $phpmailer_path= "$server/licensed/phpmailer";
  $map = [
      'PHPMailer' => "$phpmailer_path/class.phpmailer.php",
      'SMTP'      => "$phpmailer_path/class.smtp.php",
      'Ezer_PHPMailer' => "$server/ezer_mailer.php", // Tento soubor obsahuje definici třídy Ezer_PHPMailer
  ];
  if (isset($map[$class])) {
      require_once $map[$class];
  }
});


class Ezer_PHPMailer extends PHPMailer {
  protected $serverConfig;
  protected $log_errors=1;
  // Cache pro autorizace jednotlivých serverů
  protected static $oauthClientsCache= [];
  // konstruktor
  public function __construct($serverConfig) {
    parent::__construct(true); // true = umožní výjimky
    $this->Ezer_error= '';
    // Nastavení serveru
    $this->isSMTP();
    $this->SMTPAuth= 1;
    $this->Host= $serverConfig->Host;
    $this->Port= 465;
    $this->SMTPSecure= 'ssl';
    $this->Username= $serverConfig->Username;
    $this->From= $serverConfig->Username;
    //$this->SetLanguage('cs',"$phpmailer_path/language/");
    $this->CharSet= "UTF-8";
    $this->IsHTML(true);
    if ($this->Host === 'smtp.gmail.com') { // -------------------- Řešení pro gmail
      // Klíč pro cache - může být třeba hostname serveru
      $cacheKey= $this->Host;
      if (!isset(self::$oauthClientsCache[$cacheKey])) {
        try {
          self::$oauthClientsCache[$cacheKey]= $this->createOAuthClient($serverConfig);
        } 
        catch (Exception $e) {
          $this->log_e($e);
          $this->Ezer_error= 'CHYBA gmail: ' . $e->getMessage();
          return;
        }
      }      
      // Použít existujícího klienta
      $this->oauthClient= self::$oauthClientsCache[$cacheKey];
    } 
    else { // ---------------------------- Klasické SMTP přihlašování - jméno a heslo
      $this->Password= $serverConfig->Password;
      $this->Mailer= "smtp";
      foreach ($serverConfig as $part=>$value) {
        if ($part=="SMTPOptions" && $value=="-")
          $this->SMTPOptions= array('ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true));
        else
          $this->$part= $value;
      }
    }
  }

  // Vytvoří a nastaví nový Google_Client pro OAuth2.
  protected function createOAuthClient($serverConfig) {
    global $abs_root, $ezer_version;
    $server= "$abs_root/ezer$ezer_version/server";
    $gmail_api_library= "$server/licensed/google_api/vendor/autoload.php";
    require_once $gmail_api_library;
    // získání údajů pro autentizaci
    $credentials_path= "$serverConfig->files_path/credential.json";
    if (!is_file($credentials_path) || !is_readable($credentials_path)) {
      throw new Exception("nepřístupný creditals");
    }
    $tokenPath= "$serverConfig->files_path/token_$serverConfig->Username.json";
    if (!is_file($tokenPath) || !is_readable($tokenPath)) {
      throw new Exception("nepřístupný token");
    }
    $required_privileges= array("https://mail.google.com/"); //global privilege
    $client= new Google_Client();
    $client->setAuthConfig($credentials_path);
    $client->setPrompt("consent");
    $client->setScopes($required_privileges);
    $client->setAccessType('offline');
    $client->setIncludeGrantedScopes(true);
    // access token
    $accessToken= json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);
    // refresh token automatically if necessary
    if ($client->isAccessTokenExpired()) {
      $refreshToken= $client->getRefreshToken();
      if ($refreshToken) {
        $client->fetchAccessTokenWithRefreshToken($refreshToken);
      } 
      else {
        throw new Exception("nelze obnovit token");
      }
    }
    return $client;
  }
  
  public function Ezer_Send() {
    $msg= 'ok';
    if ($this->Host === 'smtp.gmail.com') { // -------------------------- OAuth2
      $message= new Google_Service_Gmail_Message();
      if ($this->preSend()) {
        $mime= $this->getSentMIMEMessage();
        $data= base64_encode($mime);
        $data= str_replace(array('+','/','='),array('-','_',''),$data); // url safe
        $message->setRaw($data);
      } 
      else {
        $msg= "CHYBA gmail/pS: " . $this->ErrorInfo;
        goto end;
      }
      $service= new Google_Service_Gmail($this->oauthClient);
      try {
//        $result= 
        $service->users_messages->send('me', $message);
//        file_put_contents("email-logs.txt", $result, FILE_APPEND);
        $msg= "ok";
      } 
      catch (Google_Service_Exception $e) {
        $msg= "CHYBA gmail/G: ".$e->getCode().' = '.$e->getMessage();
        $this->log_e($e);
      } 
      catch (Exception $e) {
        $msg= "CHYBA gmail/E: ".$e->getMessage();
        $this->log_e($e);
      }
      return $msg;
    }
    else { // ----------------------------------------------------------- SMTP
      try {
        if (!$this->Send()) {
          $msg= "CHYBA smtp: " . $this->ErrorInfo;
          goto end;
        }
      } 
      catch (Exception $e) {
        $msg= "CHYBA smtp/E: ".$e->getMessage();
        $this->log_e($e);
      }
    }
  end:
    return $msg;
  }
  
  protected function log_e($e) {
    if ($this->log_errors) {
      $msg= 'code:'.$e->getCode().' message:'.$e->getMessage();
      file_put_contents("email-logs.txt", $msg, FILE_APPEND);
    }
  }
}
