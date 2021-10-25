<?php # (c) 2021 Martin Smidek <martin@smidek.eu>
/** ======================================================================================== RUIAN */
# ---------------------------------------------------------------------------------------- ruian_adr
# zjisti pomocí VZD z RUIAN atributy zadané adresy
# vstup:  {[ulice],cislo,[cast],psc,obec}  kde cislo může obsahovat lomítko => dům/or
# výstup: {ok:b,kod_mista:n,kod_obec:n,psc:n,jtsk:{y,x},wgs:{lat:d,ltg:d},adresa:[t,t,t]}
function ruian_adresa($adr) {  //debug($adr,"ruian_adresa");
  $geo= (object)array('ok'=>0,'error'=>'');
//  $geo->query= $adr;
  $ulice= '';
  if (isset($adr->ulice)) {
    $ulice= preg_replace("~\snám\.~"," náměstí ",trim($adr->ulice));
    $ulice= preg_replace("~č\.pop\.|č\.p\.~iu",'',$ulice);
    $ulice= urlencode($ulice);
  }
  $cast= isset($adr->cast) ? urlencode(trim($adr->cast)) : '';
  $cis= trim($adr->cislo);
  list($cis,$cis_or)= preg_split('~\s*/\s*~',$cis);
  $psc= str_replace(' ','',$adr->psc);
  $obec= '';
  if (isset($adr->obec)) {
    $obec= preg_replace("~\s(n\.)~"," nad ",trim($adr->obec));
    $obec= preg_replace('~^([^\s]+)\su\s(\w+)$~','$1',$obec);
    $obec= urlencode($obec);
  }
  // ověření adresy
  $OvereniAdresy= function($ulice,$cis_dom,$cis_or,$cast,$psc,$obec) {
    $server= "https://vdp.cuzk.cz/vdp/ruian/overeniadresy/vyhledej";
    return "$server?as.nazevUl={$ulice}&as.cisDom={$cis_dom}"
    . "&as.cisOr.cisloOrientacniText={$cis_or}&as.nazevCo={$cast}&as.nazevOb={$obec}&as.psc={$psc}"
    . "&asg.sort=UZEMI&search=Vyhledat";
  };
  $geo->url= $OvereniAdresy($ulice,$cis,'',$cast,$psc,$obec);
  display("URL 1: {$geo->url}");
  $html= curl_get_contents($geo->url);
  $m= array();
  $ok= preg_match_all('~href="/vdp/ruian/adresnimista/(\d+)"~',$html,$m);
//  debug($m,"ok/misto=$ok");
  if (!$ok && $ulice && $cis && $psc && !$cast && strpos($obec,'-')!==false ) {
    list($obec,$cast)= preg_split('~\s*-\s*~',$obec);
    $geo->url= $OvereniAdresy($ulice,$cis,'',$cast,$psc,$obec);
    display("URL 2: {$geo->url}");
    $html= curl_get_contents($geo->url);
    $m= array();
    $ok= preg_match_all('~href="/vdp/ruian/adresnimista/(\d+)"~',$html,$m);
  }
  if (!$ok) {
    // zkusíme to s pomocí mapy.cz
    $addr= "$ulice%20$cis,$cast,$psc%20$obec";
    $geo->url= "http://api4.mapy.cz/geocode?query=$addr";
    display("URL 3: {$geo->url}");
    $xml= curl_get_contents($geo->url);
    display(htmlentities("XML $xml"));
    $isaddr= preg_match('~type="addr"~',$xml);
    if ($isaddr) {
      $hastitle= preg_match('~title="([^,\n]*)(?:,([^,\n]*)|)(?:,([^,\n]*)|)*"~um',$xml,$m);
      debug($m,$hastitle?1:0);
      $line= urlencode($m[1].$m[2]);
      $server= "http://ags.cuzk.cz/arcgis/rest/services/RUIAN/Vyhledavaci_sluzba_nad_daty_RUIAN"
          . "/MapServer/exts/GeocodeSOE/findAddressCandidates";
      $geo->url= "$server?SingleLine=$line&magicKey=&outSR=&maxLocations=&outFields=&searchExtent=&f=json";
      display("URL 4: {$geo->url}");
      $json= curl_get_contents($geo->url);
//      display("JSON $json");
      $ags= json_decode($json);
//      debug($ags);
      // zkusíme, jestli se identifikovalo adresní místo
      $adresa= '';
      if (isset($ags->candidates)) {
        foreach ($ags->candidates as $adr) {
          if ($adr->attributes->Type=='AdresniMisto') {
            $adresa= $adr->attributes->Match_addr;
            $psc_ok= preg_match("~$psc~",$adresa);
            if ($psc_ok) break;
          }
        }
        display("ADRESA $adresa");
        if ($adresa) {
          $m= null;
          $ok= preg_match('~(?:č.p.|)([^\d]*)\s*(\d+)(?:\/(\d+)|),\s*(?:(.*),|)\s*(\d{5})\s+(.+)~',$adresa,$m);
          debug($m,"ADRESA");
          if ($ok) {
            $geo->url= $OvereniAdresy(urlencode($m[1]),$m[2],$m[3],urlencode($m[4]),$m[5],urlencode($m[6]));
            display("URL 5: {$geo->url}");
            $html= curl_get_contents($geo->url);
            $m= array();
            $ok= preg_match_all('~href="/vdp/ruian/adresnimista/(\d+)"~',$html,$m);
            if ($ok) {
              $ok= preg_match_all('~href="/vdp/ruian/adresnimista/(\d+)"~',$html,$m);
            }
          }
        }
      }
    }
  }
  if ($ok) {
    // detail adresního místa
    $kod= $m[1][0];
    $geo->kod_mista= $kod;
    $Detail= "https://vdp.cuzk.cz/vdp/ruian/adresnimista";
    $url= "$Detail/{$kod}";
//    display("URL=$url");
    $html= curl_get_contents($url);
    // získání kódu obce
    $ok= preg_match('~href="/vdp/ruian/obce/(\d+)"~',$html,$m);
//    debug($m,"ok/obec=$ok");
    if ($ok) {
      $geo->kod_obce= $m[1];
    }
    else {
      $geo->error= 2;
      goto end;
    }
    // získání PSČ
    $ok= preg_match('~<tr><td>PSČ:</td><td>(\d+)</td></tr>~',$html,$m);
//    debug($m,"ok/psc=$ok");
    if ($ok) {
      $geo->psc= $m[1];
    }
    else {
      $geo->error= 3;
      goto end;
    }
    // získání geolokace
    $ok= preg_match('~<tr><td>Adresní bod:</td><td>Y:\s*([\d,]+)\s*X:\s*([\d,]+)\s*</td></tr>~',$html,$m);
//    debug($m,"ok/xy=$ok");
    if ($ok) {
      $y= round($m[1]);
      $x= round($m[2]);
      $geo->jtsk= (object)array('y'=>$y,'x'=>$x);
      $Converter= new Converter; 
      $wgs= $Converter->JTSKtoWGS84($x,$y);
//      $geo->wgs= "{$wgs['lat']}N, {$wgs['lon']}E";
      $geo->wgs= (object)array('lat'=>$wgs['lat'],'lng'=>$wgs['lon']);
    }
    else {
      $geo->error= 4;
      goto end;
    }
    // získání zápisu adresy podle vyhlášky č.359/2011 Sb.
    $ok= preg_match('~'
        . '<tr><td>Řádek 1:</td><td>([^<]*)</td></tr>'
        . '<tr><td>Řádek 2:</td><td>([^<]*)</td></tr>'
        . '<tr><td>Řádek 3:</td><td>([^<]*)</td></tr>~',$html,$m);
//    debug($m,"ok/adresa=$ok");
    if ($ok) {
      $geo->adresa= array($m[1],$m[2],$m[3]);
    }
    else {
      $geo->error= 5;
      goto end;
    }
  }
  else $geo->error= 1;
end:
  $geo->ok= $ok;
//                                                        debug($geo);
  return $geo;
}
# -------------------------------------------------------------------------------- curl_get_contents
function curl_get_contents($url) {
  global $ezer_server;
  switch ($ezer_server) {
    case 0:
      $html= file_get_contents($url); 
      break;
    default:
      $ctx= stream_context_create(['ssl' => 
          ['crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT]]);
      $ch= curl_init(); 
      curl_setopt($ch, CURLOPT_URL, $url); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
      $html= curl_exec($ch); 
      curl_close($ch);
      break;
  }
  return $html;
}
# ---------------------------------------------------------------------------------------- Converter
/**
 * Class Converter
 * @package JTSK
 * @author Josef Zamrzla
 * https://github.com/josefzamrzla/JTSK_Converter/blob/master/Converter.php
 * JTSK coordinates converter
 * This is a PHP port of Pascal code that was originally published at
 * http://www.geospeleos.com/Mapovani/WGS84toSJTSK/WGS84toSJTSK.htm
 * Original author: Jakub Kerhat
 */
class Converter {

    const EPS = 1e-4; // relative accuracy

    /**
     * Conversion from JTSK to WGS-84 (by iteration)
     *
     * @param $x
     * @param $y
     * @return array
     */
    public function JTSKtoWGS84($x, $y)
    {
        if (!($x && $y)) {
            return array('lat' => 0, 'lon' => 0);
        }

        $delta = 5;
        $latitude = 49;
        $longitude = 14;
        $steps = 0;

        do {
            $jtsk = $this->WGS84toJTSK($latitude - $delta, $longitude - $delta);
            if ($jtsk['x'] && $jtsk['y']) {
                $v1 = $this->distPoints($jtsk['x'], $jtsk['y'], $x, $y);
            } else {
                $v1 = 1e32;
            }

            $jtsk = $this->WGS84toJTSK($latitude - $delta, $longitude + $delta);
            if ($jtsk['x'] && $jtsk['y']) {
                $v2 = $this->distPoints($jtsk['x'], $jtsk['y'], $x, $y);
            } else {
                $v2 = 1e32;
            }

            $jtsk = $this->WGS84toJTSK($latitude + $delta, $longitude - $delta);
            if ($jtsk['x'] && $jtsk['y']) {
                $v3 = $this->distPoints($jtsk['x'], $jtsk['y'], $x, $y);
            } else {
                $v3 = 1e32;
            }

            $jtsk = $this->WGS84toJTSK($latitude + $delta, $longitude + $delta);
            if ($jtsk['x'] && $jtsk['y']) {
                $v4 = $this->distPoints($jtsk['x'], $jtsk['y'], $x, $y);
            } else {
                $v4 = 1e32;
            }

            if (($v1 <= $v2) && ($v1 <= $v3) && ($v1 <= $v4)) {
                $latitude = $latitude - $delta / 2;
                $longitude = $longitude - $delta / 2;
            }

            if (($v2 <= $v1) && ($v2 <= $v3) && ($v2 <= $v4)) {
                $latitude = $latitude - $delta / 2;
                $longitude = $longitude + $delta / 2;
            }

            if (($v3 <= $v1) && ($v3 <= $v2) && ($v3 <= $v4)) {
                $latitude = $latitude + $delta / 2;
                $longitude = $longitude - $delta / 2;
            }

            if (($v4 <= $v1) && ($v4 <= $v2) && ($v4 <= $v3)) {
                $latitude = $latitude + $delta / 2;
                $longitude = $longitude + $delta / 2;
            }

            $delta *= 0.55;
            $steps += 4;

        } while (!(($delta < 0.00001) || ($steps > 1000)));

        return array('lat' => $latitude, 'lon' => $longitude);

    }

    /**
     * Conversion from WGS-84 to JTSK
     *
     * @param $latitude
     * @param $longitude
     * @return array
     */
    public function WGS84toJTSK($latitude, $longitude)
    {
        if (($latitude < 40) || ($latitude > 60) || ($longitude < 5) || ($longitude > 25)) {
            return array('x' => 0, 'y' => 0);
        } else {
            list($latitude, $longitude) = $this->WGS84toBessel($latitude, $longitude);
            return $this->BesseltoJTSK($latitude, $longitude);
        }
    }

    /**
     * Conversion from ellipsoid WGS-84 to Bessel's ellipsoid
     *
     * @param $latitude
     * @param $longitude
     * @param int $altitude
     * @return array
     */
    public function WGS84toBessel($latitude, $longitude, $altitude = 0)
    {
        $B = deg2rad($latitude);
        $L = deg2rad($longitude);
        $H = $altitude;

        list($x1, $y1, $z1) = $this->BLHToGeoCoords($B, $L, $H);
        list($x2, $y2, $z2) = $this->transformCoords($x1, $y1, $z1);
        list($B, $L, $H) = $this->geoCoordsToBLH($x2, $y2, $z2);

        $latitude = rad2deg($B);
        $longitude = rad2deg($L);
        //$Altitude = $H;

        return array($latitude, $longitude);
    }

    /**
     * Conversion from Bessel's lat/lon to WGS-84
     *
     * @param $latitude
     * @param $longitude
     * @return array
     */
    public function BesseltoJTSK($latitude, $longitude)
    {
        $a     = 6377397.15508;
        $e     = 0.081696831215303;
        $n     = 0.97992470462083;
        $rho_0 = 12310230.12797036;
        $sinUQ = 0.863499969506341;
        $cosUQ = 0.504348889819882;
        $sinVQ = 0.420215144586493;
        $cosVQ = 0.907424504992097;
        $alfa  = 1.000597498371542;
        $k_2   = 1.00685001861538;

        $B = deg2rad($latitude);
        $L = deg2rad($longitude);

        $sinB = sin($B);
        $t = (1 - $e * $sinB) / (1 + $e * $sinB);
        $t = pow(1 + $sinB, 2) / (1 - pow($sinB, 2)) * exp($e * log($t));
        $t = $k_2 * exp($alfa * log($t));

        $sinU  = ($t - 1) / ($t + 1);
        $cosU  = sqrt(1 - $sinU * $sinU);
        $V     = $alfa * $L;
        $sinV  = sin($V);
        $cosV  = cos($V);
        $cosDV = $cosVQ * $cosV + $sinVQ * $sinV;
        $sinDV = $sinVQ * $cosV - $cosVQ * $sinV;
        $sinS  = $sinUQ * $sinU + $cosUQ * $cosU * $cosDV;
        $cosS  = sqrt(1 - $sinS * $sinS);
        $sinD  = $sinDV * $cosU / $cosS;
        $cosD  = sqrt(1 - $sinD * $sinD);

        $eps = $n * atan($sinD / $cosD);
        $rho = $rho_0 * exp(-$n * log((1 + $sinS) / $cosS));

        return array('x' => $rho * cos($eps), 'y' => $rho * sin($eps));
    }

    /**
     * Conversion from geodetic coordinates to Cartesian coordinates
     *
     * @param $B
     * @param $L
     * @param $H
     * @return array
     */
    public function BLHToGeoCoords($B, $L, $H)
    {
        // WGS-84 ellipsoid parameters
        $a   = 6378137.0;
        $f_1 = 298.257223563;
        $e2  = 1 - pow(1 - 1 / $f_1, 2);
        $rho = $a / sqrt(1 - $e2 * pow(sin($B), 2));
        $x = ($rho + $H) * cos($B) * cos($L);
        $y = ($rho + $H) * cos($B) * sin($L);
        $z = ((1 - $e2) * $rho + $H) * sin($B);

        return array($x, $y, $z);
    }

    /**
     * Conversion from Cartesian coordinates to geodetic coordinates
     *
     * @param $x
     * @param $y
     * @param $z
     * @return array
     */
    public function geoCoordsToBLH($x, $y, $z)
    {
        // Bessel's ellipsoid parameters
        $a   = 6377397.15508;
        $f_1 = 299.152812853;
        $a_b = $f_1 / ($f_1-1);
        $p   = sqrt(pow($x, 2) + pow($y, 2));
        $e2  = 1 - pow(1 - 1 / $f_1, 2);
        $th  = atan($z * $a_b / $p);
        $st  = sin($th);
        $ct  = cos($th);
        $t   = ($z + $e2 * $a_b * $a * pow($st, 3)) / ($p - $e2 * $a * pow($ct, 3));

        $B = atan($t);
        $H = sqrt(1 + $t * $t) * ($p - $a / sqrt(1 + (1 - $e2) * $t * $t));
        $L = 2 * atan($y / ($p + $x));

        return array($B, $L, $H);
    }

    /**
     * Distance between two points
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @return float|int
     */
    private function distPoints($x1, $y1, $x2, $y2)
    {
        $dist = hypot($x1 - $x2, $y1 - $y2);
        if ($dist < self::EPS) {
            return 0;
        }

        return $dist;
    }

    /**
     * Coordinates transformation
     *
     * @param $xs
     * @param $ys
     * @param $zs
     * @return array
     */
    private function transformCoords($xs, $ys, $zs)
    {
        // coeficients of transformation from WGS-84 to JTSK
        $dx = -570.69; $dy = -85.69; $dz = -462.84; // shift
        $wx = 4.99821/3600 * pi() / 180; $wy = 1.58676/3600 * pi() / 180; $wz = 5.2611/3600 * pi() / 180; // rotation
        $m  = -3.543e-6; // scale

        $xn = $dx + (1 + $m) * (+$xs + $wz * $ys - $wy * $zs);
        $yn = $dy + (1 + $m) * (-$wz * $xs + $ys + $wx * $zs);
        $zn = $dz + (1 + $m) * (+$wy * $xs - $wx * $ys + $zs);

        return array($xn, $yn, $zn);
    }
}
