<?php # (c) 2009-2018 Martin Smidek <martin@smidek.eu>
$tcpdf_path= "tcpdf-6.2.13corr";
require_once("$tcpdf_path/config/lang/eng.php");
require_once("$tcpdf_path/tcpdf.php");
# ================================================================================================== TC_PDF
define('FIS_font','dejavuserif');
define('FIS_font_sanscondensed','dejavuserifcondensed');
class TC_PDF extends TCPDF {
  public $FIS_header;
  public $FIS_header_type;                      // 1 je header první strany, 2 těch ostatních
  public $FIS_footer;
  public function FIS_set($h,$f) {
    $this->FIS_header= $h;
    $this->FIS_footer= $f;
  }
  public function Header() {
    switch ($this->FIS_header_type) {
    case 1:
      $this->FIS_write($this->FIS_header);
      break;
    case 2:
      $this->FIS_write($this->FIS_header);
      break;
    }
  }
  public function Footer() {
    $this->FIS_write($this->FIS_footer);
  }
  public function FIS_write($casti) {
//                                                 debug($casti,"FIS_write");
    $this->setListIndentWidth(6);
		if ( $casti )
    foreach($casti as $cast) {
//                                         if ( $cast->id_dopis_cast!=2 ) continue;
//                                                 debug($cast,"FIS_write ...");
      switch ($cast->typ) {
      case 'I':
//                 debug(array($cast->l,$cast->t),$cast->obsah);
        $this->Image($cast->obsah,$cast->l,$cast->t,$cast->w);
        break;
      case 'X':
      case 'T':
//         $tagvs= array('br' => array(0 => array('h' => 20, 'n' => 1), 1 => array('h' => 30, 'n' => 1)));
//         $this->setHtmlVSpace($tagvs);
        if ( $cast->ln ) {
          $this->Ln((integer)$cast->ln);
//           $this->setHtmlVSpace((integer)$cast->ln);
//                                                 debug($cast,"je string:".is_string((integer)$cast->ln),(object)array('gettype'=>1,'html'=>1));
        }
        if ( isset($cast->fsize) ) $this->SetFont(FIS_font,'',$cast->fsize?$cast->fsize:'');
        $this->SetXY($cast->l?$cast->l:'',$cast->t?$cast->t:'');
//                 debug(array($cast->l,$cast->t),$cast->obsah);
        $this->writeHTMLCell($cast->w,$cast->h,'','', $cast->obsah,$cast->bord,0,0,true,$cast->fattr,true);
// if ( $cast->w==170)       debug(array(array($cast->l,$cast->t),array($cast->w,$cast->h,0,0, $cast->obsah,$cast->bord,0,0,true,$cast->fattr,true)));
        break;
      case 'F':
        $this->SetFont(FIS_font,'',$cast->fsize);
        $this->SetY($cast->t);
//                 debug(array($cast->t,$cast->fsize),$cast->obsah);
        $this->writeHTML($cast->obsah, true, 0, true, true);
        break;
      }
    }
  }
}
# ================================================================================================== HTML
# -------------------------------------------------------------------------------------------------- tc_html
# funkce vytvoří PDF podle html-obsahu
function tc_html($fname,$html,$foot) { trace();
  global $pdf, $USER;
  tc_default();
  $foot= strtr($foot,array('##'=>$pdf->getAliasNbPages(),'#'=>$pdf->getAliasNumPage()));
  $footer= array((object)array(
    'typ'  =>'T',
    'l'    =>25,
    't'    =>-10,
    'fsize'=>8,
    'align'=>'C',
    'obsah'=>$foot
  ));
  $pdf->FIS_set(null,$footer);
  $pdf->SetMargins(10, 15, 10);
  $pdf->AddPage('','',true);
  $pdf->writeHTML($html, true, false, false, false, '');
  // zapiš soubor
  $pdf->lastPage();
  $pdf->Output($fname, 'F');
}
# ================================================================================================== TC_PDF_PAGES
class TC_PDF_PAGES extends TC_PDF {
  public function Header() {
  }
  public function Footer() {
    $this->SetFont(FIS_font,'',8);
    $this->SetXY(25,-10);
    $foot= strtr($this->FIS_foot,array('#'=>$this->FIS_page++));
    $this->writeHTMLCell('','','','', $foot,'',0,0,true,'',true);
  }
}
# -------------------------------------------------------------------------------------------------- tc_html_open
# funkce vytvoří vícestránkové PDF podle html-obsahu - začátek
function tc_html_open() { trace();
  global $pdf, $ezer_path_root;
  chdir($ezer_path_root);
  $pdf= new TC_PDF_PAGES(PDF_PAGE_ORIENTATION,'mm',PDF_PAGE_FORMAT,true,'UTF-8',false);
  $pdf->SetHeaderMargin(15);
  $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
  $pdf->SetAutoPageBreak(true,20);// PDF_MARGIN_BOTTOM); // PDF_MARGIN_BOTTOM==25
  $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
  $pdf->SetFont(FIS_font, '', 10);
  $pdf->setTopMargin(40);
  $pdf->setLineWidth(0.1);
  $pdf->SetMargins(10, 15, 10);
}
# -------------------------------------------------------------------------------------------------- tc_html_write
# funkce vytvoří vícestránkové PDF podle html-obsahu - přidání stránky
function tc_html_write($html,$foot) { trace();
  global $pdf, $USER;
  $pdf->AddPage('','',true);
  $pdf->FIS_foot= $foot;
  $pdf->FIS_page= 1;
  $pdf->writeHTML($html, true, false, false, false, '');
}
# -------------------------------------------------------------------------------------------------- tc_html_close
# funkce vytvoří vícestránkové PDF podle html-obsahu - konec
function tc_html_close($fname) { trace();
  global $pdf, $USER;
  // zapiš soubor
  $pdf->lastPage();
  $pdf->Output($fname, 'F');
}
# ================================================================================================== REPORT
# -------------------------------------------------------------------------------------------------- tc_report
# funkce tranformuje $report na $pdf
# $parss je NULL nebo pole substitučních tabulek
# pokud $report->format='A4:l,t,w,h' mají být na stránce tištěny štítky podle tabulky
# jinak bude vytištěna 1 stránka na 1 řádek tabulky
# $texty :: [ {cast:text,...}, ... ]
function tc_report($report,$texty,$fname) { trace();
//                                                 debug($texty,'texty');
//                                                 debug($report,'report');
  global $pdf, $USER;
  // transformace $report...->type pro vyšší efektivitu
  foreach($report->boxes as $box) {
    list($css,$tabs)= explode(';',$box->style);                   // tabs viz fis_pdf ř.262
    list($box->fsize,$box->fattr,$bord_wsc)= explode(',',$css);
    // for example: array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)))
    // LTRB[:width ['dotted' [0..255]]]
    list($bord,$wsc)= explode(':',$bord_wsc);
    if ( $wsc ) {
      list($w,$s,$c)= explode(' ',$wsc);
      $box->bord= array($bord=>array('width'=>$w));
      if ( $s=='dotted' ) $box->bord[$bord]['dash']= '1,3';
      if ( $c ) $box->bord[$bord]['color']= array(130);
    }
    else {
      $box->bord= $bord;
    }
  }
//                                                 debug($report,'report');
  tc_default();
  list($page,$margins)= explode(':',$report->format);
  $page_or= substr($page,2,1) ? substr($page,2,1) : 'P';
  $page_sz= substr($page,0,2) ? substr($page,0,2) : 'A4';
  if ( $texty && $margins ) {
    // maticový tisk -- na stránku bude vytištěno $nw na šířku a $nh na výšku
    $pdf->AddPage($page_or,$page_sz,true);
    $pdf->SetAutoPageBreak(false,5);
                                        display("AddPage($page_or,$page_sz,true) - $page");
    list($ml,$mt,$mw,$mh)= explode(',',$margins);
//                                                         debug($margins,1);
    $nw= floor($pdf->getPageWidth()/$mw);  // počet na šířku
    $nh= floor($pdf->getPageHeight()/$mh);  // počet na výšku
//                                                         display("štítky:$nw/$nh");
    $saved= $pdf->getMargins();
//                                                         debug($saved,2);
    $ip= 0;
    while ( $ip < count($texty) ) {
              for ($ih= 0; $ih<$nh; $ih++) {
        for ($iw= 0; $iw<$nw; $iw++) {
          // převeď pars na list
          if ( $ip==count($texty) ) break 3;
          $txt= $texty[$ip++];
          $mr= $pdf->getPageWidth()-($ml+$mw*($iw+1));
          $pdf->SetXY($ml+$mw*$iw,$mt+$mh*$ih);
          $lmarg= $ml+$mw*$iw;
          $tmarg= $mt+$mh*$ih;
          $pdf->SetMargins($lmarg,$tmarg,$mr);
//                                                         debug($pdf->getMargins(),"$ih/$iw");
          foreach ($report->boxes as $i=>$box) {
            if ( $box->fsize ) $pdf->SetFont(FIS_font_sanscondensed,'',$box->fsize);
            $id= $box->id;
            $left= $lmarg+$box->left;
            $top= $tmarg+$box->top;
            if ( $txt->$id )
              //                 ($w,        $h,          $x,    $y,  $html,    $border=0, $ln,$fill,$reseth,$align,     $autopadding)
              $pdf->writeHTMLCell($box->width,$box->height,$left,$top,$txt->$id,$box->bord,0,  0,    true,   $box->fattr,true);
          }
        }
      }
      if ( $ip < count($texty) ) {
        $pdf->AddPage($page_or,$page_sz,true);
      }
    }
    $pdf->SetMargins($saved->left,$saved->top,$saved->right);
  }
  else if ( $texty ) {
    $ip= 0;
    while ( $ip < count($texty) ) {
      $pdf->AddPage('','',true);
//                                                 display("AddPage($or,$pg,true)");
      $txt= $texty[$ip++];
      foreach ($report->boxes as $i=>$box) {
        if ( $box->fsize ) $pdf->SetFont(FIS_font_sanscondensed,'',$box->fsize);
        $id= $box->id;
        $left= $box->left;
        $top= $box->top;
        $pdf->writeHTMLCell($box->width,$box->height,$left,$top,$txt->$id,$box->bord,0,0,true,$box->fattr,true);
      }
    }
  }
  $stran= $pdf->getNumPages();
  if ( $stran > 0 ) {
    // zapiš soubor
    $pdf->lastPage();
    $pdf->Output($fname, 'F');
  }
  else throw new Exception('nebyla vygenerována ani jedna strana');
}
# ================================================================================================== ŠABLONA
# -------------------------------------------------------------------------------------------------- tc_sablona
# vygenerování šablony
function tc_sablona($fname,$pro='rozesilani',$druh='D') { trace();
  global $pdf;
  $text= null;
//   $text= str_repeat('AHOJ',3000);
  tc_dopisy(array($text),$fname,$pro,'_user',$pages,$druh);
}
# -------------------------------------------------------------------------------------------------- tc_dopisy_end
# ukončení vygenerování dopisů podle šablony ...
# pokud bylo předešlé volání tc_dopisy(....,0)
function tc_dopisy_end($fname,&$listu) { trace();
  global $pdf;
  $pdf->lastPage();
  $listu= $pdf->getNumPages();
  if ( $listu > 0 ) {
    // zapiš soubor
    $pdf->Output($fname, 'F');
    time_mark("eof tc_dopisy - $listu pages");
  }
}
# -------------------------------------------------------------------------------------------------- tc_dopisy
# vygenerování dopisů podle šablony
#   $texty :: [ {cast:text,...}, ... ]
#   $druh  :: D | N | R   -- jsou jména druhů šablon
#   před začátek každého textu jsou ze šablony přidány příslušné části [WHERE umisteni=='S']
#   $vyrizuje='davka' pokud se jméno do proměnné šablony {vyrizuje} má vzít z tabulky davka.vyrizuje
#   $vyrizuje='_user' pokud se jméno do proměnné šablony {vyrizuje} má vzít z tabulky _user.options.vyrizuje
#   $max   -- určuje maximální délku dopisu, pokud je překročena, vrací se počet takových výskytů
#   $new   -- 0:na začátku nebude inicializováno $pdf, 1:na začátku bude inicializováno $pdf
#   $save  -- 0:na konci nebude proveden zápis, 1:na konci bude proveden zápis;
#
function tc_dopisy($texty,$fname,$pro,$vyrizuje,&$listu,$druh='D',$max=1,$new=1,$save=1) { //trace();
//                                                 debug($texty,'texty');
  global $pdf, $USER, $dop_rozesilani;
  if ( !$pro ) $pro= 'rozesilani';
  if ( !$texty ) throw new Exception('nebyla požadována ani jedna strana');
  if ( $new )
    tc_default();
  $dlouhe= 0;                   // počet příliš dlouhých dopisů
  // načtení šablony
  $sablona= array();
  $cond= $pro ? " OR pro='$pro'" : '';
  $qry= "SELECT * FROM dopis_cast WHERE druh='$druh' AND (pro='' $cond)";
  $res= mysql_qry($qry);
  while ( $res && $c= mysql_fetch_object($res) ) {
//                                                 debug($c,"c je string:".is_string($c->ln),(object)array('gettype'=>1));
    // substituce ve vzorech
    $vyrizuje_name=  $vyrizuje=='_user' ? $USER->options->vyrizuje  : $dop_rozesilani['vyrizuje'];
    $potvrzuje_name= $vyrizuje=='_user' ? $USER->options->potvrzuje : $dop_rozesilani['potvrzuje'];
    $c->obsah= str_replace('{vyrizuje}',$vyrizuje_name,$c->obsah);
    $c->obsah= str_replace('{potvrzuje}',$potvrzuje_name,$c->obsah);
//                                                     display("$vyrizuje,{$c->name},{$c->obsah}");
    $sablona[$c->umisteni][]= $c;
  }
//                                                 debug($sablona,'$sablona');
  if ( $new ) {
    $pdf->FIS_footer= $sablona['F'];
    $pdf->FIS_header= $sablona['H'];
  }
  else {
    $pdf->FIS_header= $sablona['H'];
    $pdf->AddPage();
    $pdf->FIS_footer= $sablona['F'];
  }
  $first= true;
  // vytiskni všechny texty - na jejich první stránku předřaď šablonu
  if ( $texty ) {
    foreach($texty as $text) {
      $pdf->FIS_header_type= 1;
//                                                 display("tc_dopisy/$fname: 1 -".$pdf->PageNo());
      if ( !$first || $new )
        $pdf->AddPage();
//                                                 display("tc_dopisy/$fname: 2 -".$pdf->PageNo());
      $strana= $pdf->PageNo();
      if ( $sablona['X'] ) foreach ($sablona['X'] as $i => $cast) {
        $name= $cast->name;
        $sablona['X'][$i]->obsah= $text->$name;
      }
      $pdf->FIS_write($sablona['S']);
      if ( $sablona['T'] ) foreach ($sablona['T'] as $i => $cast) {
        $name= $cast->name;
        $sablona['T'][$i]->obsah= $text->$name;
      }
//                                                 debug($sablona['T'],'sablona');
      $pdf->FIS_header_type= 2;
      $pdf->FIS_write($sablona['X']);
      $pdf->FIS_write($sablona['T']);
//                                                 display("tc_dopisy/$fname: 3 -".$pdf->PageNo());
      if ( $max && $pdf->PageNo()-$strana+1>$max ) {
        $dlouhe++;
//                                                 display("tc_dopisy/$fname: dlouhé $strana -".$pdf->PageNo());
      }
      $first= false;
    }
  }
  $pdf->lastPage();
  if ( $save ) {
    $listu= $pdf->getNumPages();
    if ( $listu > 0 ) {
      // zapiš soubor
      $pdf->Output($fname, 'F');
      time_mark("eof tc_dopisy - $listu pages");
    }
    else throw new Exception('nebyla vygenerována ani jedna strana');
  }
  return $dlouhe;
}
# -------------------------------------------------------------------------------------------------- tc_default
# vytvoření základního objektu $pdf
function tc_default() {
  global $pdf;
  global $ezer_path_root;
  chdir($ezer_path_root);
  $pdf= new TC_PDF(PDF_PAGE_ORIENTATION,'mm',PDF_PAGE_FORMAT,true,'UTF-8',false);
  $pdf->SetMargins(24, 50, 15);
  $pdf->SetHeaderMargin(15);
  $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
  $pdf->SetAutoPageBreak(TRUE,20);// PDF_MARGIN_BOTTOM); // PDF_MARGIN_BOTTOM==25
  $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
  $pdf->setLanguageArray($l);
  $pdf->SetFont(FIS_font, '', 10);
  $pdf->setTopMargin(40);
  $pdf->setLineWidth(0.1);
}
?>
