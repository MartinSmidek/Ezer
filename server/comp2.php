<?php # (c) 2007-2015 Martin Smidek <martin@smidek.eu>

global $x, $y, $trace, $err,$ezer_path_code, $debugger;

# zaslepení funkcí
function note_time() {}
# ================================================================================================== COMPILER
# --------------------------------------------------------------------------------------------- comp
# přeloží $aname do $cname pokud je překlad bez chyby
# v případě chyby nechá $cname beze změny
function comp ($src) {
  global $ezer, $code;
  $ezer= $src;
  $yobj= (object)array();
  $dbgobj= null;
  $ycomp= get_ezer($yobj,$dbgobj) ? 'ok' : 'ko';
  $code= $yobj;
  return $ycomp;
}
# ---------------------------------------------------------------------------------------- comp_file
# přeloží $aname do $cname pokud je překlad bez chyby
# v případě chyby nechá $cname beze změny
# $root je jméno hlavního objektu aplikace a může být uvedeno jen pro $name='$'
# $list_only omezí listing kódu procedur na daná jména (oddělená čárkou)
# $comp_php znamená volání z comp.php
function comp_file ($name,$root='',$list_only='',$_comp_php=false) {  #trace();
  global $ezer, $ezer_path_root, $err, $comp_php,
    $code, $module, $procs, $context, $ezer_name, $ezer_app, $errors, $includes, $onloads;
  global $pragma_library, $pragma_syntax, $pragma_attrs, $pragma_names, $pragma_get, $pragma_prefix,
    $pragma_group, $pragma_box, $pragma_if, $pragma_switch;
  global $call_php;
  global $doxygen;    // $doxygen=1 pokud se má do složky data generovat *.cpp pro doxygen
  $comp_php= $_comp_php;
  $doxygen= 1;
  $errors= 0;
  try {
    $call_php= $includes= $including= $onloads= array();
    $is_library= false;
    $ezer_name= $name;
    $ezer_app= $root;
    $module= "$.$name.";
//     $ename= "$ezer_path_appl/$name.ezer";
    $ename= "$ezer_path_root/$root/$name.ezer";
    // vlastní kompilace
    $ezer= file_get_contents($ename);
//                                                                 display("ezer=$ezer");
    // oddělení případného #pragma names,syntax,prefix
    // (musí být na začátku souboru)
    $pragma_library= $pragma_syntax= $pragma_attrs= $pragma_names= $pragma_get= $pragma_prefix=
    $pragma_group= $pragma_box= $pragma_if= $pragma_switch= false;
    if ( substr($ezer,0,7)=='#pragma' ) {
      $pragma= explode(',',trim(substr($ezer,8,strpos($ezer,"\n")-8)));
//                                                             debug($pragma,"pragma");
      if ( in_array('library',$pragma)) $pragma_library= true;
      if ( in_array('names',$pragma) )  $pragma_names= array('');
      if ( in_array('syntax',$pragma) ) $pragma_syntax= true;
      if ( in_array('attrs',$pragma) )  $pragma_attrs= true;
      if ( in_array('prefix',$pragma) ) $pragma_prefix= true;
      if ( in_array('get',$pragma) )    $pragma_get= true;
      if ( in_array('box',$pragma) )    $pragma_box= true;
      if ( in_array('test',$pragma) )   $pragma_if= $pragma_switch= true;
      if ( in_array('if',$pragma) )     $pragma_if= true;
      if ( in_array('switch',$pragma) ) $pragma_switch= true;
//       if ( in_array('using',$pragma) ) {
//         $i= array_search('using',$pragma);
//         $pragma_using= $pragma[$i+1];
//       }
      if ( in_array('group',$pragma) ) {
        $pragma_group= true;
        $ezer= strtr($ezer,array('group_add'=>'self'));
      }
      // převeď form_x na form.x a panel_x na panel.x
      if ( $pragma_prefix ) {
        $tr= array(
          "form"  => 'init,copy,key,load,make,same,save,self',
          "panel" => 'close,modal,hide'
        );
        $subst= array();
        foreach($tr as $obj=>$list) {
          foreach(explode(',',$list) as $fce) {
            $subst["{$obj}_$fce"]= "{$obj}.$fce";
          }
        }
//                                                              debug($subst,"pragma");
                                                      display("pragma: transformace form_* na form.*");
        $ezer= strtr($ezer,$subst);
      }
    }
    $procs= array();
    $top= $pragma_library ? '#' : "\$.$name";
    if ( $pragma_library ) {
      $code= (object)array();
      $start= $code;
      $context= array((object)array('id'=>'#','ctx'=>$code));
      $is_library= true;
    }
//     elseif ( $pragma_using ) {
//     }
    else
    if ( $name!='$' ) {             // implementace 090825
      $context= array();
      $ids= explode('.',$name);
      $n= count($ids);
      $k= $kend= $n-1;
      while ( $k>=0 ) {
        $try= $k>0 ? implode('.',array_slice($ids,0,$k)) : '$';
        $cname= "$ezer_path_root/$root/code/$try.json";
        if ( file_exists($cname) ) {
          $cntx= file_get_contents($cname);
          $load= json_decode($cntx);
//                                                         debug($load,$cname);
//                                                         display($cname);
          $code= $load->code;
          if ( $code->library )
            $is_library= true;
//                                                         display("$try includes ".($code->library?'is library':''));
          $includes[$try]= $code;
          $level= array();
          if ( $try=='$' ) {
            $level[]= (object)array('id'=>'$','ctx'=>$code);
            $id= '$';
          }
          elseif ( $code->library ) {
            $level[]= (object)array('id'=>'#','ctx'=>$code);
            $id= '#';
          }
          elseif ( $k>0 ) {
            $id= $ids[$k-1];
          }
          else comp_error("LINK: chyba pro $name",0);
          // test na přítomnost $ids[$k...$kend] v $cntx
          $goal_obj= null;
          for ($i= $k, $ci= $code, $idi= $ids[$k]; $i<=$kend; $i++, $ci= $cid, $idi= $ids[$i]) {
//                                                         display("for $idi ... $k,$i,$kend");
            $cid= $ci->part->$idi;
            if ( !$cid ) comp_error("LINK: '$name' ($idi) nelze nalézt v '$cname' ($root,$idi)",0);
            $level[]= (object)array('id'=>$idi,'ctx'=>$cid);
            $goal_obj= $cid;
            if ( $i==$n-1 ) {
              $start= $cid;
              $sid= $idi;
            }
          }
          $including[]= (object)array('in'=>$try,'obj'=>$goal_obj);
          for($i= count($level)-1; $i>=0; $i--) {
            array_unshift($context,$level[$i]);
          }
          // pokud jsme narazili na knihovnu, další moduly nejsou potřeba
          if ( $code->library ) {
            $top= "#.$sid";
            break;
          }
          $k--;
          $kend= $k;
        }
        else {
          $k--;
        }
        for ($i= 1; $i<count($including); $i++) {
          $including[$i]->includes= $including[$i-1]->in;
        }
        for ($i= 1; $i<count($including); $i++) {
          $including[$i]->obj->part= $includes[$including[$i]->includes]->part;
        }
        for ($i= 0; $i<count($including); $i++) {
          unset($including[$i]->obj->options->include);
        }
      }
    }
    else {
      $code= (object)array();
      $start= $code;
      $context= array((object)array('id'=>'$','ctx'=>$code));
    }
    // označkuj zděděné
    if ( $start->part ) foreach ($start->part as $id=>$spart) {
      $start->part->$id->_old= true;
    }
    // vlastní překlad
                                                        if ($_GET['trace']==3) debug($start,'před get_ezer');
    $dbgobj= null;
    $ok= get_ezer($start,$dbgobj) ? 'ok' : 'ko';
                                                        if ($_GET['trace']==2) debug($start,"po get_ezer = $ok");
    // pokud je pragma.names, připrav doplnění jednoznačných jmen
    if ( $pragma_syntax ) $start= pragma_syntax($start);        // provedení pragma.syntax
    if ( $pragma_attrs ) pragma_attrs($start);                  // provedení pragma.attrs
    if ( $pragma_names ) {
      pragma_names($code);
    }
    // vyřeš reference a kompiluj procedury
    $myname= '';
    if ( $name=='$' ) {
      $myname= ($is_library?'#':'$');
      if ( !$root ) comp_error("COMP: pro '\$.ezer' není definován hlavní objekt",0);
      if ($start->part) foreach ($start->part as $id=>$spart) {
        link_code($spart,"\$.$id",true,"$id");
      }
      if ($start->part) foreach ($start->part as $id=>$spart) {
        proc($spart,'');
      }
    }
    else {
      $myname= $is_library?'#':"\$.$name";
      if ( $start->part ) {
        foreach ($start->part as $id=>$spart) {
          link_code($spart,"$top.$id",false,$id);
        }
        foreach ($start->part as $id=>$spart) {
          proc($spart,"$top.$id");
        }
      }
    }
    // export pro doxygen
    if ( $doxygen ) {
      global $doxy_cpp, $doxy_ln;
      $doxy_cpp= ''; $doxy_ln= 1;
      doxygen($start);
      $xname= "$ezer_path_root/$root/code/$name.cpp";
      file_put_contents($xname,$doxy_cpp);
    }
    $code= export($start,'$');
    if ( $pragma_library ) {             // doplnění o informaci, že se jedná o knihovnu
      $code->library= 1;
    }
    if ( !file_exists("$ezer_path_root/$root/code") ) {
      mkdir("$ezer_path_root/$root/code");
    }
    $cname= "$ezer_path_root/$root/code/$name.json";
//     file_put_contents($cname,$json->encode($code));
    $used= explode('.',$myname);
    $id= array_pop($used);
    $loads= (object)array();
    if ( $onloads) $loads->includes= $onloads;
    // doplnění jména souboru a aplikace
    $code->_app= $root;
    $code->_file= $name;
    $loads->code= $code;
                                                        if ($_GET['trace']==4) debug($loads,"kód");
    // informace o kódu pro informaci o struktuře aplikace
    $loads->info= (object)array('php'=>$call_php);
    $json_loads= json_encode($loads,JSON_HEX_AMP);
    // zabezpečení přenosy vnořených uvozovek a zpětných lomítek
    file_put_contents($cname,$json_loads);
  }
  catch (Exception $e) {
    $code= (object)array();
    $ok= 'ko';
    $errors++;
    $err= $e->getMessage().' in '.$e->getFile().';'.$e->getLine();
    $cname= "$ezer_path_root/$root/code/$name.json";
    if ( file_exists($cname) )
      unlink($cname);
    if ( !$comp_php ) goto end;
    display($e->getMessage());
  }
  // listing modulu pro trace=7
  if ( isset($_GET['trace']) && ($_GET['trace']==7 || $_GET['trace']==1) ) {
    $lst= $dbg= '';
//     $dbg= debugx($loads->code);
    $lst= xlist($loads->code,0,$list_only);
    $lst= "<pre>$lst</pre><hr>";
    display($dbg.$lst);
  }
  unset($loads->code);
//                                                         debug($loads,"ENVIRONMENT $myname");
end:
  return "$ok = kompilace a link $ename => $cname";
}
# -------------------------------------------------------------------------------------------- xlist
# listing modulu
function xlist($x,$ind,$list_only='') {
  $lst= '';
  $sp= str_repeat('  ',$ind);
  if ( $x->part ) foreach ($x->part as $id=>$desc) {
    $type= $desc->type;
    if ( $list_only==''
      || preg_match("/$list_only/",$id) ) {
      $lst.= "\n$sp$type $id";
      if ( $type=='proc' ) {
  //                                                 debug($desc);
        $npars= count((array)$desc->par);
        $nvars= count((array)$desc->var);
        $lst.= "($npars) $nvars";
      }
      if ( $desc->options ) {
        $lst.= xattr($desc->options);
      }
      if ( $desc->code ) {
        $lst.= xcode($desc->code,$ind+2,$list_only);
      }
    }
    if ( $desc->part ) {
      $lst.= xlist($desc,$ind+2,$list_only);
    }
  }
  return $lst;
}
# -------------------------------------------------------------------------------------------- xcode
# listing kódu procedury
function xcode($x,$ind=0) {
  $tr= '';
  $sp= str_repeat('  ',$ind);
  foreach ($x as $ic=>$cc) {
    $sc= str_pad($ic,2,'0',STR_PAD_LEFT);
    $o= $cc->o;
    $tr.= "\n$sp$sc: $o";
    if ($cc) foreach ($cc as $i => $cci) {
      if ( $i=='iff' || $i=='ift' || $i=='jmp' || $i=='go' ) {
        $sc= str_pad($ic+$cci,2,'0',STR_PAD_LEFT);
        $tr.= " $i=$sc";
      }
      elseif ( $i=='v' ) {
        $tr.= is_string($cci) ? " '$cci'" : ' '.json_encode($cci);
      }
      elseif ( $i=='c' ) {
        $tr.= ' code:{';
        $tr.= xcode($cci,$ind+2);
        $tr.= ' }';
      }
//       elseif ( $i=='nojmp')
//         $tr.= " nojmp=$cci";
      elseif ( $i=='trace')
        $tr.= " trace=$cci";
      elseif ( $i!='s' && $i!='o' && (is_string($cci)||is_int($cci)))
        $tr.= " $cci";
    }
  }
  return $tr;
}
# -------------------------------------------------------------------------------------------- xattr
# listing atributů
function xattr($x) {
  $tr= '';
  foreach ($x as $i=>$o) {
    if ( is_string($o) )
      $tr.= " $i:'$o'";
    elseif ( is_object($o) ) {
      $tr.= " $i:°";
      $tr.= json_encode($o);
    }
  }
  return $tr;
}
# --------------------------------------------------------------------------------------------- list
# listing částí
function list_parts($x) {
  $lst= 'xxx';
  $lst= debugx($x);
  return $lst;
}
# ================================================================================================== DEBUGGER
# --------------------------------------------------------------------------------- dbg_context_load
# pro natažení kontextu překladu pro debugger
function dbg_context_load ($ctx) {  #trace();
  $log= "";
  // části funkce comp2:comp_file
  require_once("ezer3.1/server/comp2.php");
  global $ezer_path_root, $including, $code, $context, $errors, $includes;
  global $call_php;
  $call_php= array();
  $errors= 0;
  try {
    // definice kompilačního prostředí
    $name= $ctx->file;
    $root= $ctx->app;
    // natažení kontextu
    $context= array();
    $context_id= array();
    $ids= explode('.',$name);
    $n= count($ids);
    $k= $kend= $n;
    $prefix= "$ezer_path_root/$root/code";
    while ( $k>=0 ) {
      // postupně zkracujeme složené jméno
      $try= $k>0 ? implode('.',array_slice($ids,0,$k)) : '$';
      $cname= "$prefix/$try.json";
      if ( file_exists($cname) ) {
        // pokud je jménem přeloženého modulu, vložíme do $includes
        $cntx= file_get_contents($cname);
        $load= json_decode($cntx);
        $code= $load->code;
        $includes[$try]= $code;
        $level= array();
        if ( $try=='$' ) {
          $level[]= (object)array(id=>'$','ctx'=>$code);
          $id= '$';
        }
        elseif ( $code->library ) {
          $level[]= (object)array(id=>'#','ctx'=>$code);
          $id= '#';
        }
        elseif ( $k>0 ) {
          $id= $ids[$k-1];
          $level[]= (object)array(id=>$id,'ctx'=>$code);
        }
        else $log.= "LINK: chyba pro $name";
        $includes[$try]->id= $id;
        // test na přítomnost $ids[$k...$kend] v $cntx
        $goal_obj= null;
        for ($i= $k, $ci= $code, $idi= $ids[$k]; $i<=$kend; $i++, $ci= $cid, $idi= $ids[$i]) {
          if ( !isset($ci->part) || !$idi ) continue;
          $cid= $ci->part->$idi;
          if ( $cid ) $level[]= (object)array('id'=>$idi,'ctx'=>$cid);
          $goal_obj= $cid;
        }
        $including[]= (object)array('in'=>$try,'obj'=>$goal_obj);
        for($i= count($level)-1; $i>=0; $i--) {
          if ( !isset($context_id[$level[$i]->id]) ) {
            $context_id[$level[$i]->id]= true;
            array_unshift($context,$level[$i]);
          }
        }
        $k--;
        $kend= $k;
      }
      else {
        $k--;
      }
      for ($i= 1; $i<count($including); $i++) {
        $including[$i]->includes= $including[$i-1]->in;
      }
      for ($i= 1; $i<count($including); $i++) {
        if ( !isset($including[$i]->obj->part) ) $including[$i]->obj= (object)array();
        $including[$i]->obj->part= $includes[$including[$i]->includes]->part;
      }
      for ($i= 0; $i<count($including); $i++) {
        if ( isset($including[$i]->obj->options->include) )
          unset($including[$i]->obj->options->include);
      }
    }
    // případně doplň strom
    $names= array_keys($includes);
    for ($i= 0; $i<count($includes); $i++) {
      $ids= explode('.',$names[$i]);
      $modul= $includes[$names[$i]];
      for ($j= $i+1; $j<count($includes); $j++) {
        $owner= $includes[$names[$j]];
        for ($k= 0; $k<count($ids) && $owner; $k++) {
          $idk= $ids[$k];
          if ( isset($owner->part->$idk) ) {
            $owner= $owner->part->$idk;
          }
        }
        if ( $owner ) {
          $owner->part= $modul->part;
        }
      }

    }
  }
  catch (Exception $e) {
    $log= "ERROR ".$e->getMessage();
  }
end:
  return $log;
}
# ------------------------------------------------------------------------------------------ doxygen
# export souboru pro doxygen
function doxygen($x) {
  global $doxy_cpp, $doxy_ln;
  $out= function($lc,$str) use (&$doxy_cpp,&$doxy_ln) {
    list($l)= explode(',',$lc);
    if ( $l > $doxy_ln ) {
      $doxy_cpp.= str_repeat("\n",$l-$doxy_ln);
      $doxy_ln= $l;
    }
    $doxy_cpp.= $str;
  };
  if ( $x->part ) foreach ($x->part as $id=>$desc) {
    $id= str_replace('$','_',$id);
    if ( $desc->type=='var' ) {
      $out($desc->_lc,"$desc->_of $id;");
    }
    elseif ( $desc->type=='proc' ) {
      $out($desc->_lc,"bool $id(){");
      $out($desc->lc_,"}");
    }
    else {
      $out($desc->_lc,"typedef struct {");
      if ( $desc->part ) {
        $cpp.= doxygen($desc);
      }
      $out($desc->lc_,"}$id;");
    }
  }
  return $cpp;
}
# ================================================================================================== LINK
# ------------------------------------------------------------------------------------- pragma_names
# připrav opravy při pragma
# tabulka jednoznačných jmen:
#   $pragma_names[id]= {name:full.id,obj:...}   -- pokud je jednoznačné a dosažitelné
#   $pragma_names[id]= null                     -- pokud není jednoznačné
function pragma_names($c,$path= '$') {
  global $pragma_names;
  if ( $c->part ) {
    foreach ($c->part as $cid=>$cpart) {
      if ( substr($cid,0,1)!='$' && strpos($cid,'.')===false ) {
        if ( !isset($pragma_names[$cid]) ) {
          $pragma_names[$cid]= (object)array('name'=>$path,'obj'=>$cpart);
        }
        else {
          $pragma_names[$cid]= 0;            // nejednoznačné jméno
        }
      }
      pragma_names($cpart,"$path.$cid");
    }
  }
}
# ------------------------------------------------------------------------------------ pragma_syntax
# opravy při pragma
# pragma_syntax
#       - zruší module
function pragma_syntax($c) {
  // vypuštění module
  foreach ($c->part as $cid=>$cpart) {
    if ( $cpart->type=='module' ) {
                                                    display("pragma: vypuštění module");
      foreach ($cpart->part as $cpid=>$cppart) {
        $c->part->$cpid= $cppart;
      }
      unset($c->part->$cid);
    }
  }
  return $c;
}
# ------------------------------------------------------------------------------------- pragma_attrs
# opravy při pragma - úpravy atributů
# pragma_attrs
#       - select: přidání type:map
#       - table:  přidání key_id:'x' pokud obsahuje x { key:'primary' }
#       - map:    změna key na key_id
function pragma_attrs($c) {
  if ( $c->part )
  foreach ($c->part as $cid=>$cpart) {
    if ( $cpart->type=='select' && !$cpart->options->type ) {
                                                    display("pragma: {$cpart->_lc}: doplnění select.type");
      $cpart->options->type= 'map';
      $cpart->type= 'select.map';
    }
    if ( $cpart->type=='number' && $cpart->options->key && $cpart->options->key=='primary' ) {
                                                    display("pragma: {$cpart->_lc}: doplnění table.key_id");
      $c->options->key_id= $cid;
    }
    if ( $cpart->type=='map' && $cpart->options->key ) {
                                                    display("pragma: {$cpart->_lc}: přejmenování key na key_id");
      $cpart->options->key_id= $cpart->options->key;
      unset($cpart->options->key);
    }
    pragma_attrs($cpart);
  }
}
# ---------------------------------------------------------------------------------------- link_code
# řeší některé reference v kódu a řeší options:_l,_t,_w,_h
#   var <i> : form <f>  -- pokud je <f> lokální
#   var <i> : table <f> -- pokud je <t> lokální
# a atributy typu 'i'
#  -- hodnotu atributu sql_pipe doplní do seznamu $call_php
# doplní _abs (absolutní jména)
# $context= [id=>objekt,...]
# a naváže includované části
function link_code(&$c,$name,$isroot,$block) {
  global $context, $error_code_lc, $call_php;
  $c->_abs= $name;
  if ( $c->type=='view' ) {
    $error_code_lc= $c->_lc;
    // pokud není table bezejmenné, ověří existenci
    if ( $c->_of=='table' ) {
      if ( $c->_init[0]=='$' ) {
        $c->_init= '';
        $c->_of= 'expr';
      }
      else {
        $fullname= null;
        $table= find_part_abs($c->_init,$fullname,$c->_of);
        if ( $table && $table->type=='table' ) {
          $c->_init= $fullname;
        }
        else comp_error("CODE: '{$c->_init}' není jménem {$c->_of} (1)",0);
      }
    }
  }
  else if ( $c->type=='var' && $c->_of=='form' && $c->_init) {
    $form= find_part_abs($c->_init,$fullname,$c->_of);
    if ( $form && $form->type=='form' ) {
      $c->_init= $fullname;
    }
    else {
      comp_error("CODE: '{$c->_init}' není jménem {$c->_of} (2)",0);
    }
  }
  else if ( $c->type=='var' && $c->_of=='area' && $c->_init) {
    $area= find_part_abs($c->_init,$fullname,$c->_of);
    if ( $area && $area->type=='area' ) {
      $c->_init= $fullname;
    }
    else {
      comp_error("CODE: '{$c->_init}' není jménem {$c->_of}  (3)",0);
    }
  }
  else if ( $c->type=='proc' ) {
  }
  else if ( $c->type=='map' ) {
    $table= find_part_abs($c->table,$fullname,$c->_of);
    $c->_init= $fullname;
    if ( !$table ) {
      $error_code_lc= $c->_lc;
      comp_error("CODE: '{$c->_init}' není jménem {$c->_of}  (4)",0);
    }
    unset($c->table);
  }
  if ( $c->part ) {
    array_push($context,(object)array('id'=>$c->id,'ctx'=>$c));
    foreach ($c->part as $id=>$cpart) {
      link_code($cpart,"$name.$id",$isroot,"$block.$id");
    }
    array_pop($context);
  }
  // procházení options
  if ( $c->options ) {
    foreach ($c->options as $id=>$desc) {
      // řešení symbolicky zadaných rozměrů - nahrazuje jména konstant jejich hodnotou
      if ( in_array($id,array('_l','_t','_w','_h')) ) {
        if ( is_array($desc) ) {
          foreach($desc as $p=>$part) {
            if ( $part[0]=='k' ) {
              $const= find_part_rel($part[1],$fullname);
//                                                                 debug($const,$fullname);
              if ( $const && $const->type=='const' ) {
                $c->options->{$id}[$p][0]= 'k';
                $c->options->{$id}[$p][1]= $const->options->value;
                $c->options->{$id}[$p][2]= $part[1];
                if ( isset($part[2]) && $part[2]=='-' ) $c->options->{$id}[$p][3]= '-';
              }
              else comp_error("CODE: '{$part[1]}' není jménem konstanty",0);
            }
          }
          // pokud prvek není BOX a rozměr je dán jedním číslem, odstraň pole
          if ( $c->type!='box' && count($c->options->{$id})==1 && $c->options->{$id}[0][0]=='n') {
            $c->options->{$id}= $c->options->{$id}[0][1];
          }
        }
      }
      // sběr include:onload
      else if ( $id=='include' ) {
        list($typ,$iname)= explode(',',$desc);
        if ( $typ=='onload' ) {
          // jména z include:onload dávej do pole $onloads
          global $onloads, $ezer_app;
          if ( $iname ) {
            $ids= explode('.',$iname);
            $inc= (object)array('file'=>"{$ids[0]}/$iname",'block'=>$block);
          }
          else {
//             $iname= $isroot ? substr($name,2) : $ezer_app.substr($name,1);
            $iname= substr($name,2);
            $inc= (object)array('file'=>"$ezer_app/$iname",'block'=>$block);
          }
          array_push($onloads,$inc);
        }
      }
      else if ( $id=='sql_pipe' ) {
        $fce= $desc;
        if ( !in_array($fce,$call_php) )
          $call_php[]= $fce;
      }
    }
  }
}
# --------------------------------------------------------------------------------------------- proc
# volá kompilátor procedur a převádí relativní na absolutní cesty pro table, map, report
# $context= [id=>objekt,...]
function proc(&$c,$name) { #trace();
  global $trace_me;
  global $context, $procs, $error_code_lc, $names, $full;
//                                                 if ( $name='dbg' || $name=='$.test.fce.dbg._d.test' ) debug($context,"proc($name)",(object)array('depth'=>3));
  if ( $c->type=='proc' ) {
    $trace_me= $_GET['trace']==1; //&& $c->id=='xonclick';
    if ($trace_me) $before= debugx($c);
    $desc= (object)array('id'=>$name);
    $procs[]= $desc;
    $PROC= strtoupper($c->options->code);
    try {
      if ( $c->options->code=='proc')
        gen_proc($c,$desc,$name);
      elseif ( $c->options->code=='func')
        gen_func($c,$desc,$name);
      else comp_error("CODE: '$name' nemá jasný typ kódu");
      $c->par= $desc->par;
      $c->npar= count((array)$c->par);
      $c->nvar= count((array)$c->vars);
      $c->code= $desc->code;
      if ( strpos($c->id,'.')!==false )            // bude se volat plným jménem
        $c->_init= $name;
    } catch(Exception $e) {
      if ($trace_me) display("<table class='proc'><tr><td colspan=2>$PROC $name</td></tr>".
        "<tr><td valign='top'>$before</td><td valign='top'>ERROR</td></tr></table>");
      throw $e;
    }
    if ($trace_me) display("<table class='proc'><tr><td colspan=2>$PROC $name</td></tr>".
      "<tr><td valign='top'>$before</td><td valign='top'>".debugx($c)."</td></tr></table>");
  }
  else if ( $c->part ) {
    array_push($context,(object)array('id'=>$c->id,'ctx'=>$c));
    foreach ($c->part as $id=>$cpart) {
      proc($cpart,"$name.$id");
    }
    array_pop($context);
  }
  // vyřešení atributů typu ai .. relativní cesta pro view a absolutní pro table a map a report
  if ( $c->options )
  foreach ($c->options as $id=>$name) {
    if ( $id=='rows' && !is_numeric($c->options->rows) ) {
      $error_code_lc= $c->_lc;
      $full= '';
      $obj= find_part_abs($c->options->rows,$full,'const');
      if ( $obj ) {
        $c->options->$id= $full;
      }
      else comp_error("CODE: atribut $id má neznámou konstantu {$c->options->rows}");
    }
    if ( $names[$id]->op=='oi' ) {
      if ( $name=='*' ) {
      }
      else {
        $ids= explode('.',$name);
        $error_code_lc= $c->_lc;
        if ( $id=='data' ) {
          // ids==x.položka kde x je buďto view tohoto formuláře nebo cesta k tabulce
          if ( count($ids)==2 ) {
            // může to být table
            $full= '';
            $obj= find_part_abs($ids[0],$full,'table');
            if ( $obj ) {
              // je to table
              if ( isset($obj->part->{$ids[1]}) ) {
                $full= "$full.{$ids[1]}";
              }
              else comp_error("CODE: tabulka $full nemá pole {$ids[1]}");
            }
            else {
              // může to být view
              $full= '';
              $obj= find_part_rel($ids[0],$full,'view');
              if ( $obj && $obj->_of=='table' ) {
                // je to view
                $full= "$full.{$ids[1]}";
              }
              else if ( $obj && $obj->_of=='expr' ) {
                // je to view dané výrazem
                $full= "$full.{$ids[1]}";
              }
            }
          }
          else {
            // je to cesta k table - necháme to na interpretu
            $full= $name;
          }
          $c->options->$id= $full;
        }
        else {
          $obj= find_part_rel($ids[0],$full);
          if ( $obj->type!='var' ) {
            $obj= find_part_abs($ids[0],$full);
          }
          for ($k= 1; $k<count($ids); $k++) {
            $full.= ".{$ids[$k]}";
          }
          $c->options->$id= $full;
        }
      }
    }
  }
}
# ------------------------------------------------------------------------------------------- export
# kopíruje pouze informace pro interpreta
# vynech části označené jako _old
# $type slouží ke zdědění typu 'smart' z browse na vnořené show
function export(&$c,$id) {
  global $ezer_name, $ezer_app;
  $e= null;
  if ( !$c->_old ) {
  $e= (object)array();
    foreach (get_object_vars($c) as $i => $o) {
      switch ( $i ) {
      case 'type':
      case '_of':
      case '_init':
      case 'var':       case 'nvar':
      case 'par':       case 'npar':
      case 'code':
      case '_old':
        $e->$i= $o;
        break;
      case 'options':
        $e->$i= $o ? $o : (object)array();
        break;
      case 'part':
        $e->part= (object)array();
        foreach ($c->part as $cid=>$cpart) {
          $ee= export($cpart,$cid);
          if ( $ee ) {
            $e->part->$cid= $ee;
            if ( substr($e->type,0,12)=='browse.smart' && $e->part->$cid->type=='show') {
              $e->part->$cid->type= 'show.smart';
            }
            if ( $e->part->$cid->type=='var' && $e->part->$cid->_of=='table') {
              $e->part->$cid->type= 'view';
            }
            if ( $e->type=='table' ) {
              $e->id= $id;
              $e->part->$cid->id= $cid;
            }
            else if ( $e->type=='form' ) {
              $e->id= $id;
            }
            else if ( $e->type=='area' ) {
              $e->id= $id;
            }
//             else if ( $e->type=='group' ) {
//               $e->id= 'tab';
//             }
          }
        }
        break;
      }
    }
    $e->_lc= $c->_lc;
    if ( isset($c->lc_) )
      $e->lc_= $c->lc_;
    if ( $c->_c )
      $e->_c= $c->_c;
    if ( $c->type=='form' ) {
      // form si pamatuje svůj soubor
      $e->_app= $ezer_app;
      $e->_file= $ezer_name;
    }
  }
  return $e;
}
# ================================================================================================== NAMES
# ----------------------------------------------------------------------------------------- find_obj
// included = array ( // soubor -> code
# najde objekt pojmenovaný úplným jménem tzn. začínajícím kořenem '$' nebo '#'
function find_obj($full) {
  global $context;
  $obj= $context[0]->ctx;
//                                         debug($context,"find_obj($full) -- context",0,3);
  $ids= explode('.',$full);
  $id0= array_shift($ids);
//                                         display("find_obj ... $id0");
  if ( $id0=='$' || $id0=='#' ) {
    for ($i= 0; $i<count($ids); $i++) {
      $id= $ids[$i];
//                                         display("find_obj/$i ... $id");
//       if ( !$obj->part->$id ) debug($obj,"find_obj($full) -- context",0,3);
      if ( $id && $obj->part->$id ) {
        $obj= $obj->part->$id;
      }
      else if ($obj->options->include) {
//                                                 display("find_obj/$i: included $id => {$obj->type}");
//                                                 debug($includes,'includes',0,2);
        $obj= null; //$includes[implode('.',array_splice($ids,0,$i))];
      }
      else comp_error("CODE: chybné absolutní jmeno $full");
//                                         display("find_obj/$i: $id => {$obj->type}");
    }
  }
  else comp_error("CODE: chybné absolutní jméno $full");
//                                         display("find_obj: $full => {$obj->type} {$obj->id}");
  return $obj;
}
# ------------------------------------------------------------------------------------ find_part_abs
# najde pojmenovaný objekt podle aktuálního kontextu a vrátí úplné bezkontextové jméno
# tzn. začínající kořenem '$' nebo '#'
# context :: [ id:part, ... ]
# obor hledání lze zúžit zadáním požadovaného typu
function find_part_abs($name,&$full,$type='') {
  global $context;
//                                                 debug($context,"find_part_abs: $name - $full - $type",
//                                                   (object)array('depth'=>5));
//                                                 display("find_part_abs: $name - $full - $type");
  $full= '';
  $obj= null;
  $ids= explode('.',$name);
  $id= $ids[0];
  $end_id= count($ids)-1;
  for ($i= count($context)-1; $i>=0; $i--) {
    if ( $context[$i]->id==$id && ($type && !$end_id ? $context[$i]->type==$type : true) ) {
//                                                 display("find_part_abs A/$i-$end_id: $name - {$context[$i]->id}");
      for ($k= 0; $k<=$i; $k++) {
        $full.= ($full ? '.' : '') . $context[$k]->id;
      }
      $obj= $context[$i]->ctx;
      break;
    }
    else {
      if ( isset($context[$i]->ctx->part->$id)
        && ($type && !$end_id ? $context[$i]->ctx->part->$id->type==$type : true) ) {
//                                                 display("find_part_abs B/$i-$end_id: $name - {$context[$i]->id} - ?$id ");
        for ($k= 0; $k<=$i; $k++)  {
          $full.= ($full ? '.' : '') . $context[$k]->id;
        }
        $full.= ".$id";
        $obj= $context[$i]->ctx->part->$id;
        break;
      }
    }
  }
  // pokračování může jen upřesňovat objekt
  for ($k= 1; $k<=$end_id; $k++) {
    $id= $ids[$k];
    if ( ($obj= $obj->part->$id) ) {
      $full.= ".$id";
    }
    else comp_error("CODE: chybné jméno '$id' ve jménu '$name'");
  }
  return $obj;
}
# ------------------------------------------------------------------------------------ find_part_rel
# najde pojmenovaný objekt podle aktuálního kontextu a vrátí úplné kontextové jméno
# tzn. jméno relativní vzhledem k místu dotazu
# context :: [ id:part, ... ]
# obor hledání lze zúžit zadáním požadovaného typu
#   to se týká první složky jména - neúspěch se pak nehlásí jako chyba, vrátí se null
function find_part_rel($name,&$full,$type='') { #trace();
  global $context;
  $obj= null;
  $ids= explode('.',$name);
  $id= $ids[0];
  $end_id= count($ids)-1;
  $end_cx= count($context)-1;
  $full= '';
  for ($i= $end_cx; $i>=0; $i--) {
    if ( $context[$i]->id==$id && ($type && !$end_id ? $context[$i]->type==$type : true) ) {
      // id je jméno nadřazeného bloku
      $full.= '';
      $obj= $context[$i]->ctx;
      break;
    }
    elseif ( isset($context[$i]->ctx->part->$id)
        && ($type && !$end_id ? $context[$i]->ctx->part->$id->type==$type : true) ) {
      // id je jméno přímo vnořené do nadřazeného bloku
      if ( $full ) $full= substr($full,0,-1);
      $full.= ($i==$end_cx?'':'.').$id;
      $obj= $context[$i]->ctx->part->$id;
      break;
    }
    else /*if ($i!=$end_cx)*/ {
      // postup o úroveň výš
      $full.= '.';
    }
  }
  if ( $i<0 || !$obj ) {
    if ( $type )
      $obj= null;
    else
      comp_error("CODE: neznámé jméno '$name' (1,$i)",0);
  }
  else {
    // pokud je to proměnná  - přidej k ní případné pokračování
    if ( $obj->type=='var' ) {
      for ($k= 1; $k<=$end_id; $k++) {
        $full.= ".{$ids[$k]}";
      }
    }
    else {
      // pokračování může jen upřesňovat objekt
      for ($k= 1; $k<=$end_id; $k++) {
        $id= $ids[$k];
        if ( ($obj= $obj->part->$id) ) {
          $full.= ".$id";
        }
        else comp_error("CODE: chybné jméno '$id' ve jménu '$name'");
      }
    }
  }
  return $obj;
}
# ================================================================================================== GEN func
# ----------------------------------------------------------------------------------------- gen func
# generuje kód funkcí
function gen_func($c,&$desc,$name) {
  global $error_code_context, $error_code_lc, $code_top;
  global $pragma_names, $proc_path;
//                                                 debug($c,"gen_proc: $name");
  $error_code_context= " ve funkci $name";  $error_code_lc= $c->_lc;
  $desc->par= $c->par;
  if ( $pragma_names ) {
    // připrav doplnění jednoznačných jmen
    $proc_path= explode('.',substr($name,0,strrpos($name,'.')));
//                                                 debug($proc_path,"gen_proc: $name");
  }
  $code_top= 0;                 // výška zásobníku
  // úprava seznamu lokálních proměnných a parametrů
  $n= 0;
  $c->var= (object)array();
  if ( isset($c->vars) && count($c->vars) )
  foreach($c->vars as $id=>$typ) {
    $c->var->{$id}= $n;
    $n++;
  }
  if ( count((array)$c->par) )
  foreach($c->par as $id=>$typ) {
    $c->par->{$id}+= $n;
  }
// prázdná procedura obsahuje jen return
  $c= $c->code ? gen2($c->par,$c->var,$c->code,0) : array((object)array('o'=>'f','i'=>'stop'));
  $desc->code= $c;
}
# --------------------------------------------------------------------------------------------- gen2
# generuje kód příkazů
#   $i je použit pro překladu call
function gen2($pars,$vars,$c,$icall) {
  global $code_top, $call_php, $block_get;
  $obj= null;
  switch ( $c->expr ) {
  // -------------------------------------- value
  case 'value':
    $code= $c->type=='this'
      ? (object)array('o'=>'t','i'=>$c->value[0])
      : (object)array('o'=>'v','v'=>$c->value);
    $code_top++;
    break;
  // -------------------------------------- ` string ${expr} string ... `
  case 'templ':
    // {expr:templ,par:[G(templ),...]}
    $npar= count($c->par);
    foreach($c->par as $par) {
      $code[]= gen2($pars,$vars,$par,0);
    }
    $conc= (object)array('o'=>'f','i'=>'conc','a'=>$npar);
    $code[]= $conc;
    break;
  // -------------------------------------- id ( '.' id )*
  case 'name':
    $code= gen_name($c->name,$pars,$vars,$obj,true,$c);
    if ( $obj && isset($obj->type) && isset($block_get[$obj->type]) ) {
      $code[]= (object)array('o'=>'m','i'=>'get');
    }
    break;
  // -------------------------------------- id ( expr1, ... ) ? value
  case 'call':
    $code= array();
    $npar= count($c->par);
    if ( $c->op=='ask' ) {
      if ( $c->par[0] && $c->par[0]->value && $c->par[0]->type=='s' ) {
        $ask= $c->par[0]->value;
        if ( !in_array($ask,$call_php) )
          $call_php[]= $ask;
      }
      else comp_error("CODE: příkaz ask má chybné jméno funkce na serveru");
      for ($i= 1; $i<$npar; $i++) {
        $code[]= gen2($pars,$vars,$c->par[$i],0);
      }
      $call= (object)array('o'=>'e','i'=>$c->par[0]->value,'a'=>count($c->par)-1);
      if ( $c->lc ) $call->s= $c->lc;
      $code[]= $call;
      $code_top-= $npar;
    }
    else {
      if ( ($cname= substr($c->op,-5))=='.make' || ($cname= substr($c->op,-11))=='.browse_map') {
        if ( $c->par[0] && $c->par[0]->value && $c->par[0]->type=='s' ) {
          $make= $c->par[0]->value;
          if ( !in_array($make,$call_php) )
            $call_php[]= $make;
        }
        else comp_error("CODE: metoda '$cname' má chybné jméno funkce na serveru");
      }
      $code= gen_name($c->op,$pars,$vars,$obj,$icall==0,$c,$npar);
      $cend= count($code)-1;
      if ( $code[$cend-1]->o=='a' && substr($c->op,-4)=='.set' && $npar==1 ) {
        // překládáme příkaz objekt.atribut=výraz
        // změna atributu se provede metodou set_attrib místo set
        $code= array(
            $code[0],
            (object)array('o'=>'v','v'=>$code[1]->i),
            gen2($pars,$vars,$c->par[0],0),
            (object)array('o'=>'m','i'=>'set_attrib','a'=>2)
        );
        $cend= count($code)-1;
      }
      else {
        $call= $code[$cend];
        for ($i= 0; $i<$npar; $i++) {
          $code[$i+$cend]= gen2($pars,$vars,$c->par[$i],0);
        }
        if ( $call->o!='w' )
          $call->a= $npar;
        $code[$cend+$npar]= $call;
      }
      if ( $c->lc ) $call->s= $c->lc;
      $code_top-= $npar;
    }
    if ( !$c->value )
      $code[]= (object)array('o'=>'z','i'=>1);
    break;
  // -------------------------------------- st1 ; st2 ; ...
  case 'slist':
    $code= array();
    $len= array();
    $l= 0;
    for ($i= 0; $i<count($c->body); $i++) {
      $cc= gen2($pars,$vars,$c->body[$i],0);
      $l= count($cc);                   // $l = skutečná délka kódu $cc
      $len[$i]= $l;
      $code[$i]= $cc;
    }
    $l= 1;
    for ($i= count($c->body)-1; $i>=0; $i--) {
      $l+= $len[$i];
    }
    break;
  // -------------------------------------- if ( e ) st1 [ else st2 ]
  case 'if':
    $ctest= gen2($pars,$vars,$c->test,0);
    $cthen= gen2($pars,$vars,$c->then,0);
    if ( $c->else ) { // if then else
      $iff= (object)array('o'=>0,'iff'=>count($cthen)+2);
      $celse= gen2($pars,$vars,$c->else,0);
      $go= (object)array('o'=>0,'go'=>count($celse)+1);
      $code[]= array($ctest,$iff,$cthen,$go,$celse);
    }
    else { // if then
      $iff= (object)array('o'=>0,'iff'=>count($cthen)+1);
      $code[]= array($ctest,$iff,$cthen);
    }
    break;
  // -------------------------------------- e ? e : e
  case 'tern':
    # G(expr:tern,par:[G(expr3),G(expr3),G(expr3)]
    $ctest= gen2($pars,$vars,$c->par[0],0);
    $cthen= gen2($pars,$vars,$c->par[1],0);
    $iff= (object)array('o'=>0,'iff'=>count($cthen)+2);
    $celse= gen2($pars,$vars,$c->par[2],0);
    $go= (object)array('o'=>0,'go'=>count($celse)+1);
    $code[]= array($ctest,$iff,$cthen,$go,$celse);
    break;
  // -------------------------------------- for ( var of expr ) { stmnts }
  case 'for':
    // {expr:for,var:id,of:G(expr),stmnt:(slist)}
    // překlad složek
    $var= gen_name($c->var,$pars,$vars,$obj,true,$c->var);
    $expr= gen2($pars,$vars,$c->of,0);
    $stmnt= gen2($pars,$vars,$c->stmnt,0);
    // pomocné instrukce
    $inic= (object)array('o'=>'K');
    $test= (object)array('o'=>'F','i'=>$var[0]->i,'go'=>count((array)$stmnt)+2);
    $go= (object)array('o'=>0,'go'=>-count($stmnt)-1);
    $code[]= array($expr,$inic,$test,$stmnt,$go);      // pro pole i objekty
    break;
  // -------------------------------------- switch ( expr ) { case val: stmnt ... break .. }
  case 'switch':
    // switch  = {expr:switch,of:G(expr2),cases:G(cases)}
    // cases   = [G(case),..G(default)]
    // case    = {case:value,body:G(slist),break:0/1}
    // default = {body:G(slist),break:0/1}
    // překlad složek
    $expr= gen2($pars,$vars,$c->of,0);
    $code[]= $expr;
    $ncase= count($c->cases);
    $cases= array();
    for ($i= 0; $i<$ncase; $i++) {
      $case= $c->cases[$i];
      $stmnt= gen2($pars,$vars,$case,0);
      $cases[$i]= (object)array('slist'=>$stmnt,'case'=>$case->case,'break'=>$case->break);
    }
    // konstrukce skoků
    for ($i= 0; $i<$ncase; $i++) {
      $case= $cases[$i];
      $test= $case->case
          ? (object)array('o'=>'S','v'=>$case->case,
              'go'=>count($case->slist)+($case->break ? 1 : 0)+1)
          : array();
      $block= array($test,$case->slist);
      if ( $case->break ) {
        $n= 1;
        for ($k= $i+1; $k<$ncase; $k++) {
          $n+= count($cases[$k]->slist) + ($cases[$k]->case ? 1 : 0) + ($cases[$k]->break ? 1 : 0);
        }
        $go= (object)array('o'=>0,'go'=>$n);
        $block[]= $go;
      }
      $code[]= $block;
    }
    $code[]= (object)array('o'=>'z','i'=>1);  // pop expr
    break;
  }
  $pc= array();
  plain($code,$pc);
  return $pc;
}
# ================================================================================================== GEN proc
# ----------------------------------------------------------------------------------------- gen proc
# generuje kód procedur
function gen_proc($c,&$desc,$name) {
  global $error_code_context, $error_code_lc, $code_top;
  global $pragma_names, $proc_path;
//                                                 debug($c,"gen_proc: $name");
  $error_code_context= " v procedure $name";  $error_code_lc= $c->_lc;
  $desc->par= $c->par;
  if ( $pragma_names ) {
    // připrav doplnění jednoznačných jmen
    $proc_path= explode('.',substr($name,0,strrpos($name,'.')));
//                                                 debug($proc_path,"gen_proc: $name");
  }
  $code_top= 0;                 // výška zásobníku
  // úprava seznamu lokálních proměnných a parametrů
  $n= 0;
  $c->var= (object)array();
  if ( isset($c->vars) && count($c->vars) )
  foreach($c->vars as $id=>$typ) {
    $c->var->{$id}= $n;
    $n++;
  }
  if ( count((array)$c->par) )
  foreach($c->par as $id=>$typ) {
    $c->par->{$id}+= $n;
  }
// prázdná procedura obsahuje jen return
  $struct= null;
  $c= $c->code ? gen($c->par,$c->var,$c->code,0,$struct) : array((object)array('o'=>'f','i'=>'stop'));
  $desc->code= $c;
  walk_struct($struct,$desc->code,0,$struct->len,$struct->len,$struct->len);
  walk_y($desc->code);
  clean_code($desc->code);
}
# ------------------------------------------------------------------------------------------- walk_y
# definuje v kódu vygenerovaném z $down pole ift, iff jako pokračování pro úspěch či neúspěch
# pro části kódu interpretované strukturami tj. o.y=code
function walk_y($code) {
  if ( is_array($code) ) {
    foreach ($code as $c) {
      walk_y($c);
    }
  }
  elseif ( $code->o=='y' ) {
    if ( $code->str_c ) {
//                                          debug($code,"$i");
      $len= $code->str_s->len;
      walk_struct($code->str_s,$code->c,0,$len,$len,$len);
      walk_y($code->c);
    }
  }
}
# --------------------------------------------------------------------------------------- clean_code
# odstraní atributy skoku, pokud je přítomno nojmp (tzn. výpočet argumentu)
function clean_code($code) {
  if ( is_array($code) ) {
    foreach ($code as $i => $c) {
      if ( $c->nojmp ) {
        unset($code[$i]->ift);
        unset($code[$i]->iff);
        unset($code[$i]->jmp);
        unset($code[$i]->go);
      }
      unset($code[$i]->nojmp);
      unset($code[$i]->str_c);
      unset($code[$i]->str_s);
      clean_code($c);
    }
  }
  elseif ( $code->o=='y' ) {
    clean_code($code->c);
  }
}
# -------------------------------------------------------------------------------------- walk_struct
# definuje v kódu vygenerovaném z $down pole ift, iff jako pokračování pro úspěch či neúspěch
# down - struktura
# pcode - celé pole kódu
# beg - index začátku kódu
# end - index konce kódu
# ift, iff - indexy pro skok na nejvyšší úroveň
function walk_struct($down,$pcode,$beg,$end,$ift,$iff,$is_arg=0) {
  if (!$down) $down= (object)array();
  $icode= $beg;
  $i_next= $icode;
  $typ= $down->typ;
  $down->i= $icode;
  $down->ift= $ift;
  $down->iff= $iff;

  if ( $typ=='sw' ) {  // switch
    $expr= $down->arr[0]; $e_len= $expr->len;
    walk_struct($expr,$pcode,$icode,$end,0,0,0);

    $n= count($down->arr);
    $icode+= $e_len;
    for ($i= 1; $i<$n-1; $i+=3) {
      $label= $down->arr[$i];   $l_len= $label->len;
      $test=  $down->arr[$i+1]; $t_len= $test->len;
      $stmnt= $down->arr[$i+2]; $s_len= $stmnt->len;  $stmnt->is_go= 1;
      $ts_len= $t_len + $s_len;
      walk_struct($label,$pcode,$icode,$end,0,0,1);
      $icode+= $l_len;
      walk_struct($test,$pcode,$icode,$end,$icode+$t_len,$icode+$ts_len,0);
      $icode+= 1;
      walk_struct($stmnt,$pcode,$icode,$end,$ift,$ift,1); // switch skončí vždy úspěchem
      $icode+= $s_len;
    }
    if ($i<$n) {
      $stmnt= $down->arr[$n-1]; $s_len= $stmnt->len;
      walk_struct($stmnt,$pcode,$icode,$end,$ift,$ift,1);
    }
    def_jumps($down,$pcode);
  }
  elseif ( $typ=='if' ) {  // if - then - else (v proc)
    $test= $down->arr[0];
    $then= $down->arr[1]; $then->is_go= 1;
    $else= $down->arr[2]; $else->is_go= 1;
    $t_len= $test->len;
    $tt_len= $t_len + $then->len;
    $te_len= $then->len + $else->len;
//    $tte_len= $tt_len + $else->len;
    walk_struct($test,$pcode,$icode,$end,$icode+$t_len,$icode+$tt_len,0);
    $icode+= $t_len;
    walk_struct($then,$pcode,$icode,$end,
      $is_arg ? $icode+$te_len : $ift,$is_arg ? $icode+$te_len : $ift,1);
    $icode+= $tt_len;
    walk_struct($else,$pcode,$icode,$end,
      $is_arg ? $icode+$te_len : $ift,$is_arg ? $icode+$te_len : $ift,1);
    def_jumps($down,$pcode);
  }
  elseif ( $down->arr ) {
    $last= count($down->arr) - 1;
    foreach ($down->arr as $i => $sub) {
      $i_next+= $sub->len;
      if ( $i < $last ) {
        // podstruktury uprostřed nadřazené struktury
        switch ( $typ ) { // podle typu nadřazené struktury
        case 'alt': $t= $ift;    $f= $i_next; break;
        case 'seq': $t= $i_next; $f= $iff;    break;
        default:    $t= 0; $f= 0; break;
        }
      }
      else {
        // poslední podstruktura
        switch ( $typ ) { // podle typu nadřazené struktury
        case 'alt': $t= $ift; $f= $iff; break;
        case 'seq': $t= $ift; $f= $iff;  break;
        case 'may': $t= $ift; $f= $ift; break;
        default:    $t= 0; $f= 0; break;
        }
      }
      walk_struct($sub,$pcode,$icode,$end,$t,$f,$typ=='call'?1:0);
      $icode+= $sub->len;
      if ( $typ!='alt' && $typ!='seq' && $typ!='may' )
        def_jumps($down,$pcode);
//                                         display("<pre>".xcode($pcode)."</pre>");
    }
  }
  else {
    def_jumps($down,$pcode);
  }
//                                         display("walk_struct($down->typ,,$beg,$end,$ift,$iff)=$i_next");
  return $i_next;
}//walk_struct
# ---------------------------------------------------------------------------------------- def_jumps
# definuje v kódu iff, ift, jmp
function def_jumps($c,$pcode) {
  $t= $c->ift;
  $f= $c->iff;
  $len= count($pcode);
  $beg= $c->argx ? $c->argx-1 : 0;
  // definice skoků s optimalizací
  if ( !isset($pcode[$c->i + $c->len + $beg - 1]) )
    $pcode[$c->i + $c->len + $beg - 1]= (object)array();
  $pcode[$c->i + $c->len + $beg - 1]->nojmp+= $c->argx ? 1 : 0; // zabráníme testu skoku po výpočty argumentu
  if ( $t || $f ) {
    $i= $c->i + $c->len - 1;
    if ( !$pcode[$i] ) $pcode[$i]= (object)array();
    // úprava skoku pro kódy, které nedávají stavovou hodnotu na zásobník: 'w'
    if ( $pcode[$i]->o=='w' && $i+1!=$len ) {
      if ( $t ) {
        if ( $t != $i+1 )
          $pcode[$i]->go= $t-$i;
      }
    }
    // potlačení skoku pro kódy, které nedávají stavovou hodnotu na zásobník: pro vnořený kód
    // a pro poslední instrukci kódu
    elseif ( $pcode[$i]->o!='y' && $i+1!=$len ) {
      if ( $t==$f ) {
        if ( $c->is_go )
          $pcode[$i]->go= $t-$i;
        else
          $pcode[$i]->jmp= $t-$i;
//                                         display("včil $i,{$pcode[$i]->o}");
      }
      else if ( $f && $t ) {
        if ( $f != $i+1 )
          $pcode[$i]->iff= $f-$i;
        if ( $t != $i+1 )
          $pcode[$i]->ift= $t-$i;
      }
      else {
        if ( $f )
          $pcode[$i]->iff= $f-$i;
        if ( $t )
          $pcode[$i]->ift= $t-$i;
      }
    }
  }
//                                         debug($pcode,"def_jumps B");
//                                         display("<pre>".xcode($pcode)."</pre>");
}
# -------------------------------------------------------------------------------------------- plain
# odstraní vnořenost polí
function plain($c,&$pc) {
  if ( is_array($c) ) {
    foreach ($c as $i => $ci) {
      if ( is_int($i) ) {
        plain($ci,$pc);
      }
    }
  }
  else {
    $pc[]= $c;
  }
}
# ----------------------------------------------------------------------------------------- gen_name
# přeloží výraz utvořený (složeným) jménem (bez argumentů)
# name :: id ( '.' id )*
function gen_name($name,$pars,$vars,&$obj,$first,$c=null,$nargs=null) {  #trace();
  global $context, $names, $code_top, $error_code_lc, $pragma_names;
  if ( $c && $c->lc ) $error_code_lc= $c->lc;
  $code= array();
  $obj= null;
  $ids= explode('.',$name);
  $id= $ids[0];
  $end_id= count($ids)-1;
  $end_cx= count($context)-1;
  $is_this= false;
  $is_par= false;
  $is_var= false;
  $is_desc= false;
  $k1= 1;
  if ( $id && isset($vars->$id) ) {
    // je to lokální proměnná - má přednost přede vším
    $ivar= $vars->$id;
    $code[0]= (object)array('o'=>'p','i'=>$ivar);
    $is_var= true;
    $code_top++;
  }
  elseif ( $id && isset($pars->$id) ) {
    // je to parametr
    $ipar= /*$code_top+*/$pars->$id;
    $code[0]= (object)array('o'=>'p','i'=>$ipar);
    $is_par= true;
    $code_top++;
  }
  else if ( $id=='this' ) {
    $code[0]= (object)array('o'=>'t');
    $is_this= true;
  }
  else if ( $id=='panel' ) {
    $code[0]= (object)array('o'=>'t','i'=>'p');
    $is_this= true;
  }
  else if ( $id=='area' ) {
    $code[0]= (object)array('o'=>'t','i'=>'a');
    for ($i= $end_cx; $i>=0; $i--) {
      if ( $context[$i]->ctx->type=='area'
        || $context[$i]->ctx->type=='var' && $context[$i]->ctx->_of=='area' ) {
        $obj= $context[$i]->ctx;
        if ( count($ids)>1 && $ids[1]=='desc' ) {
          $is_desc= true;
          $k1= 2;
        }
        break;
      }
    }
    $is_this= true;
  }
  else if ( $id=='form' ) {
    $code[0]= (object)array('o'=>'t','i'=>'f');
    for ($i= $end_cx; $i>=0; $i--) {
      if ( $context[$i]->ctx->type=='form'
        || $context[$i]->ctx->type=='var' && $context[$i]->ctx->_of=='form' ) {
        $obj= $context[$i]->ctx;
        if ( count($ids)>1 && $ids[1]=='desc' ) {
          $is_desc= true;
          $k1= 2;
        }
        break;
      }
    }
    $is_this= true;
  }
  else if ( $id=='return' ) {
    $code[0]= (object)array('o'=>'u');
  }
  else if ( ($type= $names[$id]->op) ) {
    // na začátku ($first==true) nesmí být metoda - jinak může, její objekt je na zásobníku
    if ($first && ($type=='fm'||$type=='fx'||$type=='fi'))
      comp_error("CODE: volání metody '$id' bez objektu",0);
    if ($first && $type[0]=='o' )
      comp_error("CODE: hodnota atributu bez objektu",0);
    $o= $type[0]=='o' ? 'a' : $type[1];
    $code[0]= (object)array('o'=>$o,'i'=>isset($names[$id]->js) ? $names[$id]->js : $id);
    if ( $c && $c->lc ) $code[0]->s= $c->lc;
  }
  else {
    $full= ''; $abs= $id; $in_form= false;
//                                                                                         $note.= 'a';
    // najdeme význam prvního id
    for ($i= $end_cx; $i>=0; $i--) {
//                                                                                         $note.= '.';
      if ( $context[$i]->id==$id ) {
//                                                                                         $note.= '1';
        // id je jméno nadřazeného bloku
        $full.= '';
        $abs= '';
        $obj= $context[$i]->ctx;
        for ($k= $i; $k>=0; $k--) {
          $abs= $context[$k]->id.($abs ? '.'.$abs : '');
        }
        break;
      }
      elseif ( isset($context[$i]->ctx->part->$id) ) {
//                                                                                         $note.= '2';
        // id je jméno přímo vnořené do nadřazeného bloku
        if ( $full ) $full= substr($full,0,-1);
        $full.= ($i==$end_cx?'':'.').$id;
        $obj= $context[$i]->ctx->part->$id;
//                                                                         debug($context[$i],$note);
        if ( $context[$i]->id=='#' ) {
          $full= "#.$abs";
//                                                                                         $note.= "[$abs]";
        }
        else {
          for ($k= $i; $k>=0; $k--) {
            $abs= $context[$k]->id.'.'.$abs;
          }
        }
        break;
      }
      else {
//                                                                                         $note.= '3';
        // postup o úroveň výš
        $full.= '.';
      }
//                                                                                         $note.= "({$context[$i]->ctx->type})";
      if ( $context[$i]->ctx->type=='form'
        || $context[$i]->ctx->type=='var'
      ) {  //????????????????????????????????????????????????????????????????????????????????
        $in_form= true;
      }
    }
//                                                                                         $note.= "[$full]";
    // pokud se nepovedl, a je pragma_names zkusíme tabulku jednoznačných jmen a adresujeme absolutně
    if ( $i<0 ) {
      if ( $pragma_names && $pid= $pragma_names[$id] ) {
        $abs= "{$pid->name}.$id"; $in_form= true;
        $obj= $pid->obj;
      }
      else {
        comp_error("CODE: neznámé jméno '$name' (2)",0);
      }
    }
    // první id je jasný
    if ( $in_form ) {
      // pokud jsme prošli přes blok 'form' budeme adresovat absolutně
        $full= $abs;
    }
    if ( $obj->type=='proc' ) {
      $code[0]= (object)array('o'=>($is_desc?'C':'c'),'i'=>$full);
      // test počtu argumentů
      $npars= count((array)$obj->par);
      if ( $nargs!==null && $nargs!=$npars ) {
        comp_error("CODE: funkce '$name' je volána s $nargs argumenty místo $npars");
      }
      $nvars= count((array)$obj->vars);
      if ( $nvars ) $code[0]->v= $nvars;
      if ( $c && $c->lc ) $code[0]->s= $c->lc;
    }
    else {
      $code[0]= (object)array('o'=>'o','i'=>$full);
      if ( $c && $c->lc ) $code[0]->s= $c->lc;
      $code_top++;
    }
  }
  // pokračování může upřesňovat objekt - dokud o něm něco víme
  for ($k= $k1; $k<=$end_id; $k++) {                // k1=1, pouze pro form.desc k1=2
    $id= $ids[$k];
    if ( $is_var && $id=='set' ) {
      unset($code[0]);
      $code[]= (object)array('o'=>'w','i'=>$ivar);  // je to koncový kód s výjimkou - nedává stav na zásobník
    }
    elseif ( $is_var && $id=='get' ) {
    }
    elseif ( ($type= $names[$id]->op) ) {
      // další jméno je funkce nebo atribut
      $obj= null;
      if ( $type[0]=='o' && $type[1]=='o' && $k<$end_id ) {
        // pokud je to atribut typu objekt a následují další jména, jde o upřesnění hodnoty atributu
        $code[]= (object)array('o'=>'a','i'=>$id);
        $code[]= (object)array('o'=>'r','i'=>implode('.',array_slice($ids,$k+1)));
        break;
      }
      else {
        $o= $type[0]=='o' ? 'a' : ($type=='fm' ? 'm' : ( $type=='fx' ? 'x' : ( $type=='fi' ? 'i' : null)));
        if ( !$o ) comp_error("CODE: chybná funkce '$id' v řetězení '$name'");
        $call= (object)array('o'=>$o,'i'=>isset($names[$id]->js) ? $names[$id]->js : $id);
        if ( $c && $c->lc ) $call->s= $c->lc;
        $code[]= $call;
      }
    }
    else {
      if ( $obj->type=='var' ) {
        if ( $obj->_init && in_array($obj->_of,array('table','form','area') ) ) {
          $obj= find_obj($obj->_init);
        }
        else {
          // pokud je to proměnná převezmeme jméno až na případnou ukončující funkci
          $full.= ".$id";
          $op= '';
          for ($k++; $k<=$end_id; $k++) {
            $id= $ids[$k];
            if ( $names[$id] ) {
              $op= $id;
              if ( $k<$end_id )
                comp_error("CODE: v neurčitém jméně '$name' nesmí po funkci '$op' následovat tečka");
            }
            else {
              $full.= ".$id";
            }
          }
          // jde buďto o referenci 'o': op!='' || nargs==null
          // nebo o volání metody  'c': op=='' && nargs>=0
          $code[0]= (object)array('o'=>!$op && $nargs!==null ?'c':'o','i'=>$full);
          if ( $op ) {
            $type= $names[$op]->op;
            $o= $type[0]=='o' ? 'a' : ($type=='fm' ? 'm' : ( $type=='fx' ? 'x' : ( $type=='fi' ? 'i' : null)));
            if ( !$o ) comp_error("CODE: chybná funkce '$op' v řetězení '$name'");
            $call= (object)array('o'=>$o,'i'=>isset($names[$op]->js) ? $names[$op]->js : $op);
            if ( $c && $c->lc ) $call->s= $c->lc;
            $code[]= $call;
          }
          break;
        }
      }
      if ( $obj ) {
        // stále upřesňujeme první jméno
        $obj= $obj->part->$id;
        $full.= ($is_this && $k==$k1) ? $id : ".$id";
        if ( $obj->type=='proc' ) {
          $code[0]= (object)array('o'=>($is_desc?'C':'c'),'i'=>$full);
          $nvars= count((array)$obj->vars);
          if ( $nvars ) $code[0]->v= $nvars;
          if ( $c && $c->lc ) $code[0]->s= $c->lc;
        }
        else {
          $code[$is_this?1:0]= (object)array('o'=>$is_this?'q':'o','i'=>$full);
        }
      }
      elseif ( $is_par ) {
        $code[]= (object)array('o'=>'r','i'=>$id);
      }
      elseif ( $is_var ) {
        $code[]= (object)array('o'=>'r','i'=>$id);
      }
      else {
        comp_error("CODE: chybné jméno '$id' v řetězení '$name', i=$i");
      }
    }
  }
  $pc= array();
  plain($code,$pc);
  return $pc;
}
# ---------------------------------------------------------------------------------------------- gen
# generuje kód výrazu
#   $i je použit pro překladu call
#   $struct = {...} výstup
function gen($pars,$vars,$c,$icall,&$struct) { #trace();
  global $names, $code_top, $pragma_get, $pragma_if, $pragma_switch, $call_php;
  $struct= (object)array('typ'=>$c->expr,'i'=>-1,'ift'=>-1,'iff'=>-1,'len'=>-1);
  $obj= null;
  switch ( $c->expr ) {
  case 'value':
    $code= $c->type=='this'
      ? (object)array('o'=>'t','i'=>$c->value[0])
      : (object)array('o'=>'v','v'=>$c->value);
    $code_top++;
    break;
  case 'par':
    $par= $c->par;
    $ipar= /*$code_top+*/$pars->$par;
//                                                 display("code: p($ipar) pro {$c->par}");
    $code= (object)array('o'=>'p','i'=>$ipar);
    $code_top++;
    break;
  case 'ops':                                                // call+ [ name ]
    $code= array();
    for ($i= 0; $i<count($c->body); $i++) {
      $ci= $c->body[$i];
      if ( $ci->expr=='call' ) {
        $struct1= null;
        $cc= gen($pars,$vars,$ci,$i,$struct1);
        $struct->arr[]= $struct1;
      }
      else {
        $cc= gen_name($ci->name,$pars,$vars,$obj,false,$ci);
      }
      $code[$i]= $cc;
    }
    break;
  case 'name':                                               // id ( '.' id )*
    if ( $pragma_get && $c->name=='get' ) {
      $c->name= 'this.get';
                                                    display("pragma: osamocené get");
    }
    $code= gen_name($c->name,$pars,$vars,$obj,true,$c);
    break;
  case 'call':
    $code= array();
    $npar= count($c->par);
    if ( $c->op=='ask' ) {
      if ( $c->par[0] && $c->par[0]->value && $c->par[0]->type=='s' ) {
        $ask= $c->par[0]->value;
        if ( !in_array($ask,$call_php) )
          $call_php[]= $ask;
      }
      else comp_error("CODE: příkaz ask má chybné jméno funkce na serveru");
      for ($i= 1; $i<$npar; $i++) {
        $code[]= gen($pars,$vars,$c->par[$i],0,$struct1);
        $struct->arr[]= $struct1;
      }
      $call= (object)array('o'=>'e','i'=>$c->par[0]->value,'a'=>count($c->par)-1);
      if ( $c->lc ) $call->s= $c->lc;
      $code[]= $call;
      $code_top-= $npar;
    }
    // -------------------------------------- if e {s1} [{s2}]
    elseif ( $pragma_if && $c->op=='if' ) {
      // {expr:'call',op:'if',par:[e,s1[,s2]]}
      if ( count($c->par)>1 ) {
        $expr= gen($pars,$vars,$c->par[0],0,$struct1);
        $struct->arr[]= $struct1;
        $then= gen($pars,$vars,$c->par[1],0,$struct1);
        $struct->arr[]= $struct1;
      }
      if ( count($c->par)==2 ) {
        $false= (object)array('o'=>'v','v'=>0);
        $code[]= array($expr,$then,$false);
      }
      elseif ( count($c->par)==3 ) {
        $else= gen($pars,$vars,$c->par[2],0,$struct1);
        $struct->arr[]= $struct1;
        $code[]= array($expr,$then,$else);
      }
      else comp_error("CODE: if musí mít 2-3 parametry");
      $struct->typ= 'if';
    }
    // -------------------------------------- switch e l1 {s1}
    elseif ( $pragma_switch && $c->op=='switch' ) {
      // {expr:'call',op:'switch',par:[e,l1,s1,...]}
      if ( count($c->par)>2 ) {
        $n= count($c->par);
        $expr= gen($pars,$vars,$c->par[0],0,$struct1);
        $struct->arr[]= $struct1;
        $code[]= $expr;
        $stmnt= array();
        for ($i= 1; $i<$n-1; $i+=2) {
          $label= gen($pars,$vars,$c->par[$i],0,$struct1);
          $struct->arr[]= $struct1;
          $test= (object)array('o'=>'S','iff'=>count((array)$stmnt)+2);
          $struct->arr[]= (object)array('typ'=>'?','i'=>-1,'ift'=>-1,'iff'=>-1,'len'=>1);
          $stmnt= gen($pars,$vars,$c->par[$i+1],0,$struct1);
          $struct->arr[]= $struct1;
          $code[]= array($label,$test,$stmnt);
        }
        if ($i<$n) {
          $stmnt= gen($pars,$vars,$c->par[$n-1],0,$struct1);
          $len+= count($stmnt);
          $struct->arr[]= $struct1;
          $code[]= $stmnt;
        }
        $struct->typ= 'sw';
      }
      else comp_error("CODE: switch musí mít aspoň 3 parametry");
    }
    // -------------------------------------- while p {s}
    elseif ( $c->op=='while' ) {
      // {expr:'call',op:'while',par:[p,s]}
      if ( count($c->par)==2 ) {
        $p= gen($pars,$vars,$c->par[0],0,$struct1);
        $s= gen($pars,$vars,$c->par[1],0,$struct1);
        $iff= (object)array('o'=>0,'iff'=>count($s)+2,'trace'=>count($s));
        $go= (object)array('o'=>0,'go'=>-count($p)-count($s)-1,'trace'=>count($p));
        $code[]= array($p,$iff,$s,$go);
      }
      else comp_error("CODE: while musí mít 2 parametry");
    }
    // -------------------------------------- foreach x fce
    // foreach(pole,procedura s jedním parametrem: hodnota)
    // foreach(objekt,procedura s dvěma parametry: hodnota,klíč)
    elseif ( $c->op=='foreach' ) {
      // {expr:'call',op:'foreach',par:[x,{expr:'name',name:fce}]}
      if ( count($c->par)==2 ) {
        $x= gen($pars,$vars,$c->par[0],0,$struct1);
        if ( $c->par[1]->expr=='name' ) {
          $fullname= null;
          $fce= find_part_abs($c->par[1]->name,$fullname,'proc');
          if ( $fce && $fce->type=='proc' ) {
            $nfpar= count((array)$fce->par);
            if ( $nfpar==1 || $nfpar==2 ) {
              $x= gen($pars,$vars,$c->par[0],0,$struct1);
              $f= array();
              $inic= (object)array('o'=>'K');
              $test= (object)array('o'=>'L','i'=>$nfpar,'go'=>count((array)$f)+3);
              $f= gen_name($c->par[1]->name,$pars,$vars,$obj,true,$c->par[1]);
              $f[count($f)-1]->a= $nfpar;
              $f[count($f)-1]->ift= -count($f);
              $popx= (object)array('o'=>'z','i'=>1,'nojmp'=>1);
                $code[]= array($x,$inic,$test,$f,$popx);      // pro pole i objekty
              $struct->arr[]= $struct1;
            }
            else comp_error("CODE: procedura použitá ve foreach musí mít jeden nebo dva parametry");
          }
          else comp_error("CODE: druhý parametr ve foreach musí být procedura");
        }
        else comp_error("CODE: foreach musí mít 2 parametry: seznam a jméno procedury");
      }
      else comp_error("CODE: foreach musí mít 2 parametry: seznam a jméno procedury");
    }
    // -------------------------------------- each x fce
    elseif ( $c->op=='each' ) {
      $np_each= count($c->par);
      if ( $c->par[1]->expr=='name' ) {
        $fce= find_part_abs($c->par[1]->name,$fullname,'proc');
        $np_fce= count((array)$fce->par);
        if ( $fce && $fce->type=='proc' && $np_fce==$np_each ) {
          $code[]= (object)array('o'=>'y','c'=>gen($pars,$vars,$c->par[0],0,$struct1),
            'str_c'=>$c->par[0],'str_s'=>$struct1);
          $struct->arr[]= $struct1;
          $code[]= (object)array('o'=>'y','c'=>gen_name($c->par[1]->name,$pars,$vars,$obj,true,$c->par[1]));
          for ($i= 2; $i<count($c->par); $i++) {
            $code[]= gen($pars,$vars,$c->par[$i],0,$struct1);
            $struct->arr[]= $struct1;
          }
          $code[]= (object)array('o'=>'s','i'=>'each','a'=>count($c->par));
        }
        else comp_error("CODE: procedura použitá v each nemá správný počet parametrů ($np_each)");
      }
      else comp_error("CODE: each má chybné parametry");
    }
    // -------------------------------------- new_form
    elseif ( $c->op=='new_form' ) {
      if ( ($npar==3 || $npar==4 ) && $c->par[0]->expr=='name' ) {
        $form= find_part_abs($c->par[0]->name,$fullname,'form');
        if ( $form && $form->type=='form' ) {
          $code[]= (object)array('o'=>'y','c'=>array((object)array('o'=>'v','v'=>$fullname)));
          $code[]= (object)array('o'=>'y','c'=>gen($pars,$vars,$c->par[1],0,$struct1),
            'str_c'=>$c->par[1],'str_s'=>$struct1);
          $struct->arr[]= $struct1;
          $code[]= (object)array('o'=>'y','c'=>gen($pars,$vars,$c->par[2],0,$struct1),
            'str_c'=>$c->par[2],'str_s'=>$struct1);
          $struct->arr[]= $struct1;
          if ( isset($c->par[3]) ) {
            $code[]= (object)array('o'=>'y','c'=>gen($pars,$vars,$c->par[3],0,$struct1),
              'str_c'=>$c->par[3],'str_s'=>$struct1);
            $struct->arr[]= $struct1;
          }
          $code[]= (object)array('o'=>'s','i'=>'new_form','a'=>$npar);
        }
        else comp_error("CODE: výraz new_form nemá jako 1 parametr form");
      }
      else comp_error("CODE: new_form má chybné parametry");
    }
    // -------------------------------------- new_area
    elseif ( $c->op=='new_area' ) {
      if ( count($c->par)>=2  ) {
        $area_ok= false;
        if ( $c->par[0]->expr=='name' ) {
          $area= find_part_abs($c->par[0]->name,$fullname,'area');
          if ( $area && $area->type=='area' ) {
            $code[]= (object)array('o'=>'y','c'=>array((object)array('o'=>'o','i'=>$fullname)));
            $area_ok= true;
          }
        }
        if ( !$area_ok ) {
          $code[]= (object)array('o'=>'y','c'=>gen($pars,$vars,$c->par[$i],0,$struct1),
            'str_c'=>$c->par[$i],'str_s'=>$struct1);
          $struct->arr[]= $struct1;
        }
        for ($i= 1; $i<$npar; $i++) {
          $code[]= (object)array('o'=>'y','c'=>gen($pars,$vars,$c->par[$i],0,$struct1),
            'str_c'=>$c->par[$i],'str_s'=>$struct1);
          $struct->arr[]= $struct1;
        }
        $code[]= (object)array('o'=>'s','i'=>'new_area','a'=>$npar);
      }
      else comp_error("CODE: new_area má chybné parametry");
    }
    // -------------------------------------- call fs
    elseif ( $names[$c->op]->op=='fs' ) {
      $code_top0= $code_top;
      for ($i= 0; $i<$npar; $i++) {
        $code_top= 0;
        $code[]= (object)array('o'=>'y','c'=>gen($pars,$vars,$c->par[$i],0,$struct1),
          'str_c'=>$c->par[$i],'str_s'=>$struct1);
//         $struct->arr[]= $struct1;     // revize 3175 - viz test.stx.ezer 130913
      }
      $code_top= $code_top0;
      $call= (object)array('o'=>'s','i'=>$c->op,'a'=>$npar);
      $struct->typ= 'struct';
      if ( $c->lc ) $call->s= $c->lc;
      $code[]= $call;
    }
    else {
//                                                     display("*** {$c->op}");
      if ( ($cname= substr($c->op,-5))=='.make' || ($cname= substr($c->op,-11))=='.browse_map') {
        if ( $c->par[0] && $c->par[0]->value && $c->par[0]->type=='s' ) {
          $make= $c->par[0]->value;
          if ( !in_array($make,$call_php) )
            $call_php[]= $make;
        }
        else comp_error("CODE: metoda '$cname' má chybné jméno funkce na serveru");
      }
      $code= gen_name($c->op,$pars,$vars,$obj,$icall==0,$c,$npar);
//                                                     debug($code,"** call");
      $cend= count($code)-1;
      $call= $code[$cend];
      for ($i= 0; $i<$npar; $i++) {
        $code[$i+$cend]= gen($pars,$vars,$c->par[$i],0,$struct1);
        $struct1->argx= $cend+1;                       // zabránění vložení IFT nebo IFF do kódu
        $struct->arr[]= $struct1;
//                                                     debug(array($code[$i+$cend],$struct1),"* argx $i");
      }
      if ( $call->o!='w' )
        $call->a= $npar;
      if ( $c->lc ) $call->s= $c->lc;
      $code[$cend+$npar]= $call;
      $code_top-= $npar;
//       if ( $call->o=='w' )
//         $code[]= (object)array('o'=>'v','v'=>7);
    }
    break;
  case 'may':
    $code= gen($pars,$vars,$c->body,0,$struct1);
    $struct->arr[]= $struct1;
    $end= count($code)-1;
    ###$code[$end]->jmp= 1;
    break;
  case 'seq':
  case 'alt':
    $code= array();
    $len= array();
    $l= 0;
    for ($i= 0; $i<count($c->body); $i++) {
      $cc= gen($pars,$vars,$c->body[$i],0,$struct1);
      $struct->arr[]= $struct1;
//       $struct->code[]= $cc;
      $l= count($cc);                   // $l = skutečná délka kódu $cc
      $len[$i]= $l;
      $code[$i]= $cc;
    }
    $l= 1;
    for ($i= count($c->body)-1; $i>=0; $i--) {
      $end= count($code[$i])-1;
      if ( $c->expr=='seq' ) {
        ###$code[$i][$end]->iff= $l;
      }
      if ( $c->expr=='alt' ) {
        ###$code[$i][$end]->ift= $l;
      }
      $l+= $len[$i];
    }
//                     if ($trace_me) debug($struct,"str=$l",(object)array('depth'=>1));
    break;
  }
  $pc= array();
  plain($code,$pc);
  $struct->len= count($pc);
  return $pc;
}
# ================================================================================================== SYNTAX
# lexikální a syntaktická analýza
# ----------------------------------------------------------------------------------------- get ezer
# top  - prázdný objekt, pro library neprázdný ale označkovaný jako _old
# top2 - pro běžný překlad null pro debugger objekt pro vložení procedury resp. funkce _dbg_
# dbg = false|'proc'|func' - specifikace zda jde o proceduru nebo funkci
function get_ezer (&$top,&$top2,$dbg=false) {
  global $lex, $head, $attribs1, $attribs2, $keywords, $errors, $const_list, $debugger;
  $const_list= array();
  get_ezer_keys($keywords,$attribs1,$attribs2);
  note_time('tables');
  $debugger= $dbg;
  $ok= lex_analysis2($dbg);
  note_time('lexical');
  if ( $ok ) {
    $head= 0;
    $ok= true;
//     $top= new stdClass;
    while ( $ok && !$errors ) {
      $block= $id= null;
      $ok= get_if_block($top,$block,$id);
      if ( $ok ) {
        if ( $top2 ) {
          if ( !$top2->part ) $top2->part= (object)array();
          $top2->part->$id= $block;
          break;
        }
        else {
          if ( !$top->part ) $top->part= (object)array();
          if ( is_array($block) ) {
            foreach($block as $idx=>$blockx) {
              $top->part->$idx= $blockx;
            }
          }
          else
            $top->part->$id= $block;
        }
      }
    }
    $nlex= count($lex);
    $ok= true;
    if ( $head < $nlex) $ok= comp_error("SYNTAX nedočteno do konce ");
  }
  else $ok= comp_error("SYNTAX chyba pri lexikalni analyze ");
  note_time('syntax');
  return $ok;
}
# --------------------------------------------------------------------------------------------- ezer
# naplní pole $attribs1, $attribs2
function get_ezer_keys (&$keywords,&$attribs1,&$attribs2) {
  global $ezer_path_serv, $blocs, $attribs, $uni_attribs;
  $keywords= array();
  require_once("$ezer_path_serv/comp2def.php");
  compiler_init();
  foreach ($blocs as $k1 => $keys) {
    if ( $k1 && !in_array($k1,$keywords) ) $keywords[]= $k1;
    foreach ($keys as $k2) {
      if ( $k2 && !in_array($k2,$keywords) ) $keywords[]= $k2;
    }
  }
  sort($keywords);
  foreach ($attribs as $p => $def) {
    foreach ($def as $i => $vt) {
      list($id,$typ)= explode(':',$vt);
      $attribs1[$p][$i]= $id;
      $attribs2[$p][$i]= $typ;
    }
    foreach ($uni_attribs as $vt) {
      list($id,$typ)= explode(':',$vt);
      $attribs1[$p][]= $id;
      $attribs2[$p][]= $typ;
    }
  }
}
# -------------------------------------------------------------------------------------------- block
# $root je nadřazený blok
# block  :: vars | 'func' pars body2 | key [ id ] [':' key id] [pars|args] [coord] [code] [struct]
# struct :: '{' part (',' part)* '}' ]
# part   :: block | attr
function get_if_block ($root,&$block,&$id) {
//                                                 debug($root,"get_if_block",(object)array('depth'=>2));
  global $doxygen, $pos, $head;
  global $blocs2, $blocs3, $specs, $last_lc;
  global $pragma_syntax, $pragma_group, $pragma_box, $call_php;
  global $errors; if ( $errors ) return false;
  $TEST_NEW_VAR= 1;  // ------------------------------------------------- testování var !!!!!!!
  $block= null; $nt= null; $key= $lc= $skip= 0;
  $ok= get_if_keyed_name ($key,$id,$lc,$nt);
  $lc_= '';
  if ( $ok ) {
    if ( $TEST_NEW_VAR && $key=='var' ) {
      get_vars($block,$id,$lc);
    }
    else {
      $block= new stdClass;
      $block->type= $key;
      $block->options= (object)array();
      if ( $block->type=='func' ) {
        $block->type= 'proc';
      }
      if ( isset($specs[$key]) ) {
        $copy= $fg= $typ= $pars= $code= $vars= $prior= $args= $value= $is_expr= null;
        if ( in_array('map_table' ,$specs[$key])
             && get_delimiter(':') && get_keyed_name('table',$copy,$lc,$nt) ) $block->table= $copy;
        if ( in_array('use_form' ,$specs[$key])
             && get_delimiter(':') && get_if_keyed_name($fg,$copy,$lc,$nt) ) {
          if ( $fg=='form' || ($pragma_group && $fg=='group') ) {
            $block->type= 'var';
            $block->_of= 'form';
            $block->_init= $copy;
          }
          elseif ( $fg=='area' ) {
            $block->type= 'var';
            $block->_of= 'area';
            $block->_init= $copy;
          }
          else $ok= comp_error("SYNTAX: po 'use' smí následovat jen form nebo area"); // nebo group");
        }
        if ( in_array('use_table' ,$specs[$key])
             && get_delimiter(':') && get_keyed_name('table',$copy,$lc,$nt) ) {
          $block->type= 'view';
          $block->_of= 'table';
          $block->_init= $copy;
        }
        if ( in_array('type' ,$specs[$key])
             && get_delimiter(':') && get_type($typ)               ) $block->_of= $typ;
        if ( in_array('par'  ,$specs[$key]) && get_if_pars($pars)  ) $block->par= $pars;
        if ( in_array('code' ,$specs[$key])
             && get_code($pars,$code,$vars,$prior,$lc_)          ) { $block->code= $code;
                                                                     $block->options->code= 'proc';
                                                                     $block->vars= $vars;
                                                     if ( $doxygen ) $block->lc_= $lc_;
                                                       if ( $prior ) $block->options->prior= $prior;
        }
        if ( in_array('code2',$specs[$key])
             && get_code2($pars,$code,$vars,$prior,$lc_)         ) { $block->code= $code;
                                                                     $block->options->code= 'func';
                                                                     $block->vars= $vars;
                                                     if ( $doxygen ) $block->lc_= $lc_;
                                                       if ( $prior ) $block->options->prior= $prior;
        }
        if ( in_array('arg'  ,$specs[$key]) && get_if_args($args)  ) $block->arg= $args;
        if ( in_array('coord',$specs[$key]) && get_if_coord($block) )  $skip= 0;
        if ( in_array('coor+',$specs[$key]) && get_if_coorp($block) )  $skip= 0;
        if ( in_array('const',$specs[$key]) && get_def($id,$value,$is_expr) ) {
          if ( $is_expr )
            $block->options->_expr= $value;
          else
            $block->options->value= $value;
          if ( !isset($root->part) ) $root->part= (object)array();
          $root->part->$id= $block;
          $cid= null;
          $ok= get_if_delimiter(';') || get_if_comma_id($cid);
          // další konstanty
          while ( $ok ) {
            if ( !$cid ) get_id($cid);
            get_def($cid,$value,$is_expr);
            $cblock= new stdClass;
            $cblock->type= 'const';
            if ( !isset($cblock->options) ) $cblock->options= (object)array();
            if ( $is_expr )
              $cblock->options->_expr= $value;
            else
              $cblock->options->value= $value;
            $cblock->_lc= $last_lc;
            $cblock->id= $id;
            $root->part->$cid= $cblock;
            $cid= null;
            $ok= get_if_delimiter(';') || get_if_comma_id($cid);
          }
          $ok= true;
        }
        if ( in_array('note' ,$specs[$key]) && $nt                   ) $block->note= $nt;
        if ( in_array('cmnt' ,$specs[$key]) && $nt                   ) $block->note= $nt;
        if ( in_array('part' ,$specs[$key]) ) {
          $ok= get_if_delimiter('{');
          if ( $ok ) {
            // atributy
            while ( $ok ) {
              $aid= $aval= null;
              if ( ($ok= get_if_attrib($key,$aid,$aval)) ) {
                if ( !$block->options ) $block->options= (object)array();
                $block->options->$aid= $aval;
              }
              get_if_delimiter(',');
            }
            $ok= true;
            // kontrola přípustnosti vnoření $key.$type do $root a úprava $block->type
            if ( $block->options->type ) {
              if ( $pragma_box && $block->type=='box' ) {
                $block->options->css= $block->options->type;
                                                  display("pragma: box - css místo type ");
              }
              else {
                $block->type.= ".{$block->options->type}";
              }
              unset($block->options->type);
            }
            // bylo-li pragma.syntax - je něco k automatické opravě?
            if ( $pragma_syntax ) {
              switch ( $block->type ) {
              case 'panel.subpanel':       // panel.subpanel => panel
              case 'panel.panel':          // panel.panel => panel
                                                  display("pragma: {$block->type} => panel");
                $block->type= 'panel';
                break;
              }
            }
            $root_tt= $root ? $root->type : '';
            $block_tt= $block->type;
            if ( (!$blocs2[$root_tt] || !in_array($block_tt,$blocs2[$root_tt]) )
              && !in_array($block_tt,$blocs3) ) {
  //                                                                 debug($blocs2);
              comp_error("SYNTAX: blok '$block_tt' není povolený uvnitř bloku '$root_tt' (1)");
            }
            // vnořené bloky
            while ( $ok ) {
              $xblock= $xid= null;
              $ok= get_if_block($block,$xblock,$xid);
              if ( $ok ) {
                if ( !$block->part ) $block->part= (object)array();
                if ( is_array($xblock) ) {
                  foreach($xblock as $xidx=>$xblockx) {
                    $block->part->$xidx= $xblockx;
                  }
                }
                else
                  $block->part->$xid= $xblock;
              }
              get_if_delimiter(',');
            }
            if ( $doxygen ) $lc_= $pos[$head];
            get_delimiter('}');
          }
          $ok= true;
        }
        $block->_lc= $lc;
        if ( $doxygen && $lc_ ) $block->lc_= $lc_;
        $block->id= $id;
        // analýza select.auto - přidání par.fce do seznamu volaných php-funkcí
        if ( $block->type=='select.auto' ) {
//                                                                 debug($block);
          if ( isset($block->options->par->fce) ) {
            $ask= $block->options->par->fce;
            if ( !in_array($ask,$call_php) )
              $call_php[]= $ask;
          }
        }
        // analýza browse - přidání optimize.ask do seznamu volaných php-funkcí
        if ( $block->type=='browse' ) {
//                                                                 debug($block);
          if ( isset($block->options->optimize->ask) ) {
            $ask= $block->options->optimize->ask;
            if ( !in_array($ask,$call_php) )
              $call_php[]= $ask;
          }
        }
      }
    }
//                                                 debug($root,"vars old");
  }
  return $ok;
}
# ------------------------------------------------------------------------------------------- attrib
# $root je nadřazený blok
# attr :: id [':' val | ':' id]         -- id musí být jméno konstanty, typ atributu musí mít c
# defaultní val=1
function get_if_attrib ($root,&$id,&$val) {
  global $attribs1, $attribs2, $pragma_box, $errors;
  if ( $errors ) return false;
  $val= 1;
  $ok= get_if_id_not_keyword($id);
  if ( $ok ) {
    if ( $pragma_box && $root=='box' ) {
      if ( $id=='active' ) {
        $id= 'xactive';
                                                display("pragma: box - xactive místo active");
      }
    }
    if ( isset($attribs1[$root]) && (false!==($i= array_search($id,$attribs1[$root]))) ) {
      $typ= $attribs2[$root][$i];
      if ( $typ!='b' ) {
        $cid= $typval= null;
        get_delimiter(':');
        if ( $typ=='i' || $typ=='m' ) {
          // jméno položky tabulky nebo mapy
          get_id($val);
        }
        else if ( strpos($typ,'c') && get_if_id($cid) ) {
          // jméno konstanty
          $val= $cid;
        }
        else {
//                                                 display("atribut $id");
          if ( look_value() ) {
            // literál
            get_value($val,$typval);
            if ( strpos($typ,$typval)===false ) {
              comp_error("SYNTAX hodnota atributu $id smí mít typy $typ");
              return false;
            }
          }
          else comp_error("SYNTAX po jménu hodnotového atributu $id nenásleduje konstanta");
        }
      }
    }
    else { comp_error("SYNTAX atribut '$id' není povolený v bloku '$root' (2)"); return false; }
  }
  return $ok;
}
# ---------------------------------------------------------------------------------------------- par
# pars  :: '(' par (',' par)* ')'       -- vrací pole
function get_if_pars (&$opars) {
  $ok= get_if_delimiter('(');
  if ( $ok ) {
    $pars= array();
    while ( $ok ) {
      $id= null;
      $ok= get_if_id($id);
      if ( $ok ) {
        $pars[]= $id;
        $ok= get_if_delimiter(',');
      }
    }
    get_delimiter(')');
    $ok= true;
  }
  // přeložení pars do opars {id:offset,...}
  $opars= (object)array();
  if ( $pars )
  foreach($pars as $i=>$p) {
    $opars->$p= count($pars)-$i-1;
  }
  return $ok;
}
function get_pars (&$pars) {
  $ok= get_if_pars($pars);
  if ( !$ok ) comp_error("SYNTAX: byl očekáván seznam parametrů");
  return true;
}
# ---------------------------------------------------------------------------------------------- arg
# args  :: '(' val (',' val)* ')'      -- vrací pole
function get_if_args (&$args) {
  $ok= get_if_delimiter('(');
  if ( $ok ) {
    while ( $ok ) {
      if ( get_if_delimiter(')') ) break;
      $val= $typ= null;
      get_value($val,$typ);
      $args[]= $val;
      $ok= get_if_delimiter(',');
      if ( !$ok ) get_delimiter(')');
    }
    $ok= true;
  }
  return $ok;
}
# ----------------------------------------------------------------------------------------- numvalue
# numvalue :: [-]num | constant_name   --> $value
# vrací 1.písmeno typu
function get_numvalue (&$val,&$id) {
  global $head, $lex, $typ, $const_list;
  $ok= false;
  $val= $lex[$head];
  if ( $typ[$head]=='del' && $val=='-' ) {
    $head++;
    $val.= $lex[$head];
    $ok= $typ[$head]=='num';
  }
  else {
    $ok= $typ[$head]=='num';
  }
  if ( $ok ) {
    if ( $typ[$head]!='num' ) comp_error("SYNTAX: po + byla očekávána numerická hodnota");
    $id= null;
    $val= 0+$val;
    $head++;
  }
  else if ( $typ[$head]=='id' ) {     // jméno konstanty
    $id= $val;
    $ok= true;
    $head++;
    $val= $const_list[$id]['value'];
  }
  if ( !$ok ) comp_error("SYNTAX: bylo očekávána číslo nebo konstanta místo {$typ[$head]} $val");
  return true;
}
# --------------------------------------------------------------------------------------------- vars
# vars    :: 'var' varlist
# varlist :: vardef | vardef ',' varlist
# vardef  :: id ':' type | id '=' value
function get_vars (&$root,$id,$lc) {
  // připojí proměnné do bloku, id je identifikátor první proměnné
  global $last_lc;
  $types= array('n'=>'number','s'=>'text','o'=>'object','a'=>'array');
  $root= array();
  while (1) {
    $block= new stdClass;
    $block->type= 'var';
    $block->_lc= $lc;
    $block->id= $id;
    // proměnná s inicializací, určující její typ
    if ( get_if_delimiter('=') ) {
      $val= $typval= null;
      get_value($val,$typval);
      $block->options= (object)array();
      $block->options->value= $val;
      $block->_of= $types[$typval];
    }
    // proměnná bez inicializace a s typem
    else {
      get_delimiter(':');
      $typ= null;
      get_type($typ);
      $block->_of= $typ;
    }
    $root[$id]= $block;
    // případné pokračování po čárce a identifikátoru
    if ( get_if_comma_id($id) ) {
      $lc= $last_lc;
      continue;
    }
    break;
  }
//                                                 debug($root,"vars");
  return true;
}
# -------------------------------------------------------------------------------------------- const
# (a) const :: 'const' id '=' cvalue            -- začátek
# (b) const :: (';'|',') id '=' cvalue          -- pokračování
#     cvalue :: const | nvalue
#     nvalue :: number | nid | nvalue [ ('+'|'-') nvalue ] -- kde nid je jméno kontrolované za běhu
function get_def ($id,&$value,&$is_expr) {
  global $const_list;
  $value= null; $type= 'global';
  $id1= null;
  $ok= get_if_delimiter('=');
  if ( $ok ) {
    $ok= get_if_id_not_keyword($id1);
    if ( $ok ) {
      $value= $const_list[$id1]['value'];
      $type= $const_list[$id1]['type'];
    }
    else {
      get_value($value,$type);
      $ok= true;
    }
  }
  // případné rozšíření?
  $op= get_if_delimiter('+') ? '+' : (get_if_delimiter('-') ? '-' : false);
  if ( $op ) {
    $is_expr= true;
    $value= array($id1 ? array('k',$value,$id1) : array('n',$value));
    while ( $op ) {
      // další sčítanec
      $value2= $id2= null;
      get_numvalue ($value2,$id2);
      $expr= $id2
        ? ($op==='-' ? array('k',$value2,$id2,'-') : array('k',$value2,$id2))
        : ($op==='-' ? array('n',-$value2) : array('n',$value2));
      $value[]= $expr;
      $const_list[$id]= array('_expr'=>$value,'type'=>$type);
      $op= get_if_delimiter('+') ? '+' : (get_if_delimiter('-') ? '-' : false);
    }
  }
  // přidání do seznamu konstant (povoluje se přepsání stejnou hodnotou)
  elseif ( !isset($const_list[$id])
    || $const_list[$id]['value']==$value && $const_list[$id]['type']==$type ) {
    $const_list[$id]= array('value'=>$value,'type'=>$type);
    $is_expr= false;
  }
  else
    comp_error("SYNTAX: konstanta $id má duplicitní definici ($id={$const_list[$id]['value']})");
  return true;
}
# ------------------------------------------------------------------------------------------- coord+
# coord_plus+ :: '[' cexpr ',' cexpr ',' cexpr ',' cexpr ']'
# cexpr       :: ( '^' | '$' | '$v' | '*' | '~' | const_id | id '.' ('l'|'r'|'t'|'b'|'w'|'h') | num )
#                [ ('+'|'-') cexpr ]
function get_if_coorp ($block) {
  global $pos, $head;
  $ok= get_if_delimiter('[');
  $block->_c= $pos[$head-1];
  if ( $ok ) {
    if ( !$block->options ) $block->options= (object)array();
    $cexpr= null;
    if ( get_cexpr($cexpr,'^') ) $block->options->_l= $cexpr;
    get_delimiter(',');
    if ( get_cexpr($cexpr,'^','~') ) $block->options->_t= $cexpr;
    get_delimiter(',');
    if ( get_cexpr($cexpr,'$','*') ) $block->options->_w= $cexpr;
    get_delimiter(',');
    if ( get_cexpr($cexpr,'$','!','*') ) $block->options->_h= $cexpr;
    get_delimiter(']');
    $block->_c.= ",".$pos[$head-1];
  }
  return $ok;
}
function get_cexpr (&$cexpr,$rel1,$rel2='',$rel3='') {
  $cexpr= array();
  $op= true;
  $op= get_if_delimiter('+') ? '+' : (get_if_delimiter('-') ? '-' : true);
  while ( $op ) {
    $num= $id= null;
    if ( get_if_number($num) ) {         // číslo
      if ( $op==='-' ) $num= -$num;
      $x= array('n',$num);
    }
    else if ( get_if_delimiter($rel1) )
      $x= array($rel1);
    else if ( $rel2 && get_if_delimiter($rel2) )
      $x= array($rel2);
    else if ( $rel3 && get_if_delimiter($rel3) )
      $x= array($rel3);
    else if ( get_if_id($id) )  {
      $ids= explode('.',$id);
      switch ( count($ids) ) {
      case 1:
        $x= $op==='-' ? array('k',$id,'-') : array('k',$id);
        break;
      case 2:
        $x= array_reverse($ids);
        break;
      default:
        comp_error("SYNTAX: lze jen jméno konstaty nebo jméno boxu následované l,r,t,b,w,h");
      }
    }
//     $ok= get_if_delimiter('+');
    if ( $x ) {
      $cexpr[]= $x;
    }
    $op= get_if_delimiter('+') ? '+' : (get_if_delimiter('-') ? '-' : false);
  }
//                                                 debug($cexpr,'cexpr');
  return count($cexpr);
}
# -------------------------------------------------------------------------------------------- coord
# coord :: '[' [num|*] ',' [num|*] ',' [num|*] ',' [num|*] ']'
function get_if_coord ($block) {
  $ok= get_if_delimiter('[');
  if ( $ok ) {
    $num= null;
    if ( get_if_number($num) ) $block->options->_l= $num;
    else if ( get_if_delimiter ('*') ) $block->options->_l= "'*'";
    get_delimiter(',');
    if ( get_if_number($num) ) $block->options->_t= $num;
    else if ( get_if_delimiter ('*') ) $block->options->_t= "'*'";
    get_delimiter(',');
    if ( get_if_number($num) ) $block->options->_w= $num;
    else if ( get_if_delimiter ('*') ) $block->options->_w= "'*'";
    get_delimiter(',');
    if ( get_if_number($num) ) $block->options->_h= $num;
    else if ( get_if_delimiter ('*') ) $block->options->_h= "'*'";
    get_delimiter(']');
  }
  return $ok;
}
# --------------------------------------------------------------------------------------------- type
# type  :: number | text | form | area | object | array
function get_type (&$type) {
  global $head, $lex;
  $type= $lex[$head];
  $ok= ($type=='number'||$type=='text'||$type=='array'||$type=='object'||$type=='form'||$type=='area');
  $head++;
  if ( !$ok ) comp_error("SYNTAX: bylo očekáváno jméno typu");
  return $ok;
}
# ------------------------------------------------------------------------------------------- key id
# key [ id ] -- pokud je id vynecháno je vrácen anonymní idntifikátor _n
function get_if_keyed_name (&$key,&$id,&$lc,&$note) {
  global $head, $lex, $typ, $pos, $not, $id_anonymous;
//                                           display(":: {$typ[$head]} {$lex[$head]}");
  $ok= $typ[$head]=='key_id';
  if ( $ok ) {
    $key= $lex[$head]->key;
    $id= $lex[$head]->id;
    $lc= $pos[$head];
    $note= isset($not[$head]) ? $not[$head] : null;
    $head++;
  }
  if ( !$ok ) {
    $ok= $typ[$head]=='key';
    if ( $ok ) {
      $lc= $pos[$head];
      $note= isset($not[$head]) ? $not[$head] : null;
      $key= $lex[$head]; $head++;
      $ok= $typ[$head]=='id' || $typ[$head]=='key' ;
      if ( $ok ) {
        $lc= $pos[$head];
        $id= $lex[$head]; $head++;
      }
      else {
        $id_anonymous++;
        $id= '$'.$id_anonymous;
        $ok= true;
      }
    }
  }
//                                           display(":: get_if_keyed_name($key,$id)");
  return $ok;
}
function get_keyed_name ($should,&$id,&$lc,&$note) {
  $key= null;
  $ok= get_if_keyed_name($key,$id,$lc,$note);
  $ok= $ok && ($key==$should);
  if ( !$ok ) comp_error("SYNTAX: bylo očekáváno '$should'");
  return true;
}
# -------------------------------------------------------------------------------------------- 'key'
# 'key' -- klíčové slovo je omezeno hodnotou
function get_if_the_key ($key,&$lc) {
  global $head, $lex, $typ, $pos;
//                                           display(":: {$typ[$head]} {$lex[$head]->key}");
  $ok= $typ[$head]=='key' && $key==$lex[$head];
  if ( $ok ) {
    $lc= $pos[$head];
    $head++;
  }
  return $ok;
}
# ---------------------------------------------------------------------------------------- delimiter
# zjistí následuje-li v textu oddělovač, jestli ano přečte jej
function get_if_delimiter ($del) {
  global $head, $lex, $typ;
  $ok= $typ[$head]=='del' && $lex[$head]==$del;
  if ( $ok ) {
    $head++;
  }
  return $ok;
}
# --------------------------------------------------------------------------------------------------
# přečte oddělovač, není-li, ohlásí chybu
function get_delimiter ($del) {
  global $head, $lex, $typ;
  $ok= $typ[$head]=='del' && $lex[$head]==$del;
  if ( $ok ) { $head++; }
  if ( !$ok ) comp_error("SYNTAX: byl očekáván oddělovač '$del'");
  return true;
}
# --------------------------------------------------------------------------------------------------
# přečte očekávané klíčové slovo, není-li, ohlásí chybu
function get_key ($key) {
  global $head, $lex;
  $ok= $lex[$head]==$key;
  if ( $ok ) { $head++; }
  if ( !$ok ) comp_error("SYNTAX: bylo očekáván '$key'");
  return true;
}
# --------------------------------------------------------------------------------------------------
# zjistí následuje-li v textu oddělovač (neposunuje čtecí hlavu)
function look_delimiter ($del) {
  global $head, $lex, $typ;
  $ok= $typ[$head]=='del' && $lex[$head]==$del;
  return $ok;
}
# --------------------------------------------------------------------------------------------------
# zjistí následuje-li v textu daný identifikátor resp. klíčové slovo
function look_id_or_key ($id) {
  global $head, $lex, $typ;
  $ok= $lex[$head]==$id && ($typ[$head]=='id'|| $typ[$head]=='key');
  return $ok;
}
# --------------------------------------------------------------------------------------------------
# zjistí následuje-li v textu daný identifikátor resp. klíčové slovo, pokud ano posune hlavu
function get_if_id_or_key ($id) {
  global $head, $lex, $typ;
  $ok= $lex[$head]==$id && ($typ[$head]=='id'|| $typ[$head]=='key');
  if ( $ok ) {
    $head++;
  }
  return $ok;
}
# ------------------------------------------------------------------------------------------- number
# num :: [-] <num>
function get_if_number (&$number) {
  global $head, $lex, $typ;
  $number= '';
  $ok= $typ[$head]=='del' && $lex[$head]=='-';
  if ( $ok ) { $head++; $number= '-'; }
  $ok= $typ[$head]=='num';
  if ( $ok ) {
    $number.= $lex[$head];
    $head++;
    // kontrola levostranné nuly
    $number= 0+$number;
  }
  return $ok;
}
# ---------------------------------------------------------------------------------------------- ,id
function get_if_comma_id (&$id) {
  global $head, $lex, $typ, $pos, $last_lc;
  $ok= $typ[$head]=='del' && $lex[$head]==',';
  if ( $ok ) {
    $ok= $typ[$head+1]=='id';
    if ( $ok ) {
      $head++;
      $id= $lex[$head];
      $last_lc= $pos[$head];
      $head++;
    }
  }
  return $ok;
}
# ----------------------------------------------------------------------------------------------- id
# identifikátorem může být i hvězdička - se speciálním významem, podle sémantického kontextu
# v režimu debuggeru lze použít na začátku i dolar
function get_if_id (&$id) {
  global $head, $lex, $typ, $pos, $last_lc;
  $ok= $typ[$head]=='id'
    || ($typ[$head]=='key' && $lex[$head]=='form')
    || $typ[$head]=='del' && $lex[$head]=='*';
  if ( $ok ) {
    $id= $lex[$head];
    $last_lc= $pos[$head];
    $head++;
  }
  return $ok;
}
# --------------------------------------------------------------------------------------------------
# jen identifikátor, který není klíčovým slovem
function get_if_id_not_keyword (&$id) {
  global $head, $lex, $typ, $pos, $last_lc;
  $ok= $typ[$head]=='id';
  if ( $ok ) {
    $id= $lex[$head];
    $last_lc= $pos[$head];
    $head++;
  }
  return $ok;
}
# --------------------------------------------------------------------------------------------------
function get_id (&$id) {
  global $head, $lex, $typ, $pos, $last_lc;
  $ok= $typ[$head]=='id' || $typ[$head]=='del' && $lex[$head]=='*';
  if ( $ok ) {
    $id= $lex[$head];
    $last_lc= $pos[$head];
    $head++;
  }
  if ( !$ok ) comp_error("SYNTAX: byl očekáván identifikátor");
  return true;
}
# --------------------------------------------------------------------------------------------------
function get_id_or_key (&$id) {
  global $head, $lex, $typ, $pos, $last_lc;
  $ok= $typ[$head]=='id' || $typ[$head]=='key';
  if ( $ok ) {
    $id= $lex[$head];
    $last_lc= $pos[$head];
    $head++;
  }
  if ( !$ok ) comp_error("SYNTAX: byl očekáván identifikátor nebo klíčové slovo");
  return true;
}
# -------------------------------------------------------------------------------------------- value
# value :: [-]num | str | object | array | constant_name   --> $value
# vrací 1.písmeno typu
function get_value (&$val,&$type) {
  global $head, $lex, $typ, $const_list;
  $ok= false;
  $val= $lex[$head];
  if ( $typ[$head]=='del' && $val=='-' ) {
    $head++;
    $val.= $lex[$head];
    $ok= $typ[$head]=='num';
  }
  else {
    $ok= $typ[$head]=='num' || $typ[$head]=='str';
  }
  if ( $ok ) {
//    if ( $pragma_strings && $typ[$head]=='str' ) {
//      // zpracování vnitřku stringu
//    }
    $type= substr($typ[$head],0,1);
    $val= $type=='s'
        ? substr(substr($val,1),0,-1)
        : 0+$val;
    $head++;
  }
  else if ( $val=='°' ) {         // objektová konstanta
    $ok= true;
    $head++;
    if ( $typ[$head]=='del' && $lex[$head]=='{' )
      get_object($val,$type);
    elseif ( $typ[$head]=='del' && $lex[$head]=='[' )
      get_array($val,$type);
    else
      comp_error("SYNTAX: byl očekáván objekt nebo pole");
  }
  else if ( $typ[$head]=='key' && ($val=='this' || $val=='panel' || $val=='area') ) {
    $ok= true;
    $head++;
    $type= 'this';
  }
  else if ( $typ[$head]=='id' && isset($const_list[$id= $val]) ) {     // jméno konstanty
    $ok= true;
    $head++;
    $val= $const_list[$id]['value'];
    $type= $const_list[$id]['type'];
  }
  if ( !$ok ) comp_error("SYNTAX: byla očekávána hodnota místo {$typ[$head]} $val");
  return true;
}
# --------------------------------------------------------------------------------------------------
# zjistí, zda následuje hodnota
function look_value () {
  global $head, $lex, $typ;
  $ok= $typ[$head]=='num' || $typ[$head]=='str'
      || ($typ[$head]=='del' && $lex[$head]=='°')
      || ($typ[$head]=='del' && $lex[$head]=='-')
      ;
  return $ok;
}
# -------------------------------------------------------------------------------------------- array
# array :: '[' value ( ',' value )* ']'          --> $array
function get_array (&$obj,&$type) {
  get_delimiter('[');
  $obj= array();
  $type= 'o';
  $ok= true;
  while ( true ) {
    $val= $tp= null;
    get_value($val,$tp);
    $obj[]= $val;
    $comma= get_if_delimiter(',');
    if ( !$comma ) break;
  }
  get_delimiter(']');
  if ( !$ok ) comp_error("SYNTAX: byl očekáván literál pole");
  return true;
}
# ------------------------------------------------------------------------------------------- object
# object :: '{' pair ( ',' pair )* '}'          --> $object
# pair   :: id ':' value
function get_object (&$obj,&$type) {
  get_delimiter('{');
  $obj= (object)array();
  $type= 'o';
  $ok= true;
  while ( true ) {
    $id= $val= $tp= null;
    get_id_or_key($id);
    get_delimiter(':');
    get_value($val,$tp);
    $obj->$id= $val;
    $comma= get_if_delimiter(',');
    if ( !$comma ) break;
  }
  get_delimiter('}');
  if ( !$ok ) comp_error("SYNTAX: byl očekáván objektový literál");
  return true;
}
# ================================================================================================== FUNC code
# body2 :: '{' [ 'var' varlist ] slist '}'
# vlist :: vdef | vdef (','|'var') vlist
# vdef  :: id ':' type
# -------------------------------------------------------------------------------------------- code2
# $context je objekt se jmény formálních parametrů - překládaných jako {id:offset,...}
function get_code2($context,&$code,&$vars,&$prior,&$lc_) {
  global $pos, $head;
  $code= null;
  $prior= 0;
  $vars= array();
  get_delimiter('{');
  // případné lokální proměnné
  $lc= null;
  $ok= get_if_the_key('var',$lc);
  while ( $ok ) {
    $id= $typ= null;
    get_id($id);
    get_delimiter(':');
    get_type($typ);
    $vars[$id]= $typ;
    $ok= get_if_delimiter(',') || get_if_the_key('var',$lc);
  }
  if ( !look_delimiter('}') ) {       // je povolena prázdná procedura
    get_slist($context,$code);
  }
  $lc_= $pos[$head];
  get_delimiter('}');
  return true;
}
# -------------------------------------------------------------------------------------------- slist
# slist   :: stmnt ( ';' stmnt )*               --> {expr:slist,body:[G(stmnt),...]}
function get_slist($context,&$st) {
  $st= (object)array('expr'=>'slist');
  $st->body= array();
  $ok= true;
  while ( true ) {
    $seq= null;
    $ok= get_stmnt($context,$seq);
    if ( !$ok ) break;
    $st->body[]= $seq;
    $ok= get_if_delimiter(';');
    if ( !$ok ) { $ok= true; break; }
  }
  $st= count($st->body)==1 ? $st->body[0] : $st;
  return $ok;
}
# -------------------------------------------------------------------------------------------- stmnt
# stmnt   :: '{' slist '}'                      --> G(slist)
#          | id '=' expr2                       --> {expr:call,op:id.set,par:[G(expr2)]}
#          | 'if' '(' expr2 ')' stmnt [ 'else' stmnt ]
#                                               --> {expr:if,test:G(expr2),then:G(st1),else:G(st2)}
#          | 'for' '(' 'let' id 'of' expr ')' '{' slist '}'
#                                               --> {expr:for,var:id,of:G(expr),stmnt:(slist)}
#          | 'switch (' expr2 ') {' cases '}'   --> {expr:switch,of:G(expr2),cases:G(cases)}
#          | call2                              --> G(call2)
#          |
function get_stmnt($context,&$st) {
  $ok= false;
  $id= '';
  # '{' slist '}' --> G(slist)
  if ( get_if_delimiter('{') ) {
    $ok= get_slist($context,$st);
    get_delimiter('}');
  }
  elseif ( get_if_id($id) ) {
    # id '=' expr2 --> {expr:call,op:id.set,par:[G(expr2)]}
    if ( get_if_delimiter('=') ) {
      $expr='';
      $ok= get_expr2($context,$expr);
      $st= (object)array('expr'=>'call','op'=>"$id.set",'par'=>array($expr));
    }
    elseif ( get_if_delimiter('(') ) {
      # 'if' '(' expr2 ')' stmnt [ 'else' stmnt ]
      #      --> {expr:if,test:G(expr2),then:G(stmnt/1),else:G(stmnt/2)}
      if ( $id=='if' ) {
        $test= $then= $else= null;
        $ok= get_expr2($context,$test);
        get_delimiter(')');
        get_stmnt($context,$then);
        $st= (object)array('expr'=>'if','test'=>$test,'then'=>$then);
        if ( get_if_id_or_key('else') ) {
          get_stmnt($context,$else);
          $st->else= $else;
        }
      }
      elseif ( $id=='switch' ) {
        # 'switch' '(' expr2 ')' '{' cases '}' --> {expr:switch,of:G(expr2),cases:G(cases)}
        $expr= $cases= null;
        $ok= get_expr2($context,$expr);
        get_delimiter(')');
        get_delimiter('{');
        get_cases($context,$cases);
        get_delimiter('}');
        $st= (object)array('expr'=>'switch','of'=>$expr,'cases'=>$cases);
      }
      elseif ( $id=='for' ) {
        # 'for' '(' 'let' id 'of' expr ')' '{' slist '}' --> {expr:for,var:id,of:G(expr),stmnt:(slist)}
        $stmnts= $var= $expr= null;
        get_id($var);
        get_key('of');
        $ok= get_expr2($context,$expr);
        get_delimiter(')');
        get_delimiter('{');
        get_slist($context,$stmnts);
        get_delimiter('}');
        $st= (object)array('expr'=>'for','var'=>$var,'of'=>$expr,'stmnt'=>$stmnts);
      }
      # call2 --> G(call2)
      else {
        $expr='';
        $ok= get_call2_id($context,$st,$id,0);
      }
    }
    else {
      # prázdný příkaz
      $ok= true;
    }
  }
  return $ok;
}
# -------------------------------------------------------------------------------------------- cases
# cases   :: case* [ default ]                  --> [G(case),..G(default)]
# case    :: 'case' value ':' slist ['break;']  --> {case:value,body:G(slist),break:0/1}
# default :: 'default'    ':' slist ['break;']  --> {body:G(slist),break:0/1}
function get_cases($context,&$cs) {
  $cs= array();
  $ok= true;
  # case* [ default ] --> [G(case),..G(default)]
  while ( $ok ) {
    $ok= get_if_id_or_key('case');
    if ( $ok ) {
      # 'case' value ':' slist ['break;'] --> {case:value,body:G(slist),break:0/1}
      $val= $type= $slist= null;
      get_value ($val,$type);
      get_delimiter(':');
      $case= (object)array('expr'=>'slist','body'=>array(),'case'=>$val,'break'=>0);
      while ( $ok ) {
        if ( look_id_or_key('break') || look_id_or_key('case')
          || look_id_or_key('default') || look_delimiter('}')) {
          break;
        }
        $stmnt= null;
        $ok= get_stmnt($context,$stmnt);
        if ( $ok ) {
          $case->body[]= $stmnt;
        }
        $ok= get_if_delimiter(';');
      }
      if ( get_if_id_or_key('break') ) {
        get_delimiter(';');
        $case->break= 1;
      }
      $cs[]= $case;
    }
  }
  $ok= get_if_id_or_key('default');
  if ( $ok ) {
    # 'default' ':' slist ['break;'] --> {body:G(slist),break:0/1}
    get_delimiter(':');
    $default= (object)array('expr'=>'slist','body'=>array(),'break'=>0);
    while ( $ok ) {
      if ( look_id_or_key('break') || look_delimiter('}') ) {
        break;
      }
      $stmnt= null;
      $ok= get_stmnt($context,$stmnt);
      if ( $ok ) {
        $default->body[]= $stmnt;
      }
      $ok= get_if_delimiter(';');
    }
    if ( get_if_id_or_key('break') ) {
      get_delimiter(';');
      $default->break= 1;
    }
    $cs[]= $default;
  }
  return true;
}
# -------------------------------------------------------------------------------------------- expr2
# expr2   :: expr3                              --> G(expr3)
#          | expr3 op expr3                     --> G(expr:call,op:G(op),par:[G(expr3),G(expr3)]
#          | expr3 ? expr2 : expr2              --> G(expr:tern,par:[G(e/1),G(e/2),G(e/3)]
# op      :: '+' | '-' | '*' | '/'              --> sum | minus | multiply | divide | gt | eq
#          | '>' | '<' | '=='                   --> gt | lt | eq
#          | '&&' | '||'                        --> and | or
# expr3   :: call2                              --> G(call2)
#          | id                                 --> {expr:par,par:id} | {expr:name,name:id}
#          | '`' template* '`'                  --> {expr:templ,par:[G(templ),...]}
#          | value                              --> {expr:value,value:v,type:t}
#          | '(' expr2 ')'                      --> G(expr2)
# templ   :: string                             --> {expr:value,value:v,type:t}
#          | '${' ( id | call2 ) '`'            --> G(id) | G(call2)
function get_expr2($context,&$expr) {
  global $last_lc;
  $ok= get_expr3($context,$expr);
  $op= $expr2= $expr3= null;
  if (     get_if_delimiter('+') )  $op= 'sum';
  elseif ( get_if_delimiter('-') )  $op= 'minus';
  elseif ( get_if_delimiter('*') )  $op= 'multiply';
  elseif ( get_if_delimiter('/') )  $op= 'divide';
  elseif ( get_if_delimiter('>') )  $op= 'gt';
  elseif ( get_if_delimiter('<') )  $op= 'lt';
  elseif ( get_if_delimiter('==') ) $op= 'eq';
  elseif ( get_if_delimiter('&&') ) $op= 'and';
  elseif ( get_if_delimiter('||') ) $op= 'or';
  elseif ( get_if_delimiter('?') )  {
    # G(expr:tern,par:[G(expr3),G(expr3),G(expr3)]
    get_expr2($context,$expr2);
    get_delimiter(':');
    $ok= get_expr2($context,$expr3);
    $expr= (object)array('expr'=>'tern','lc'=>$last_lc,'par'=>array($expr,$expr2,$expr3),'value'=>1);
  }
  if ( $op ) {
    # expr3 op expr3 --> G(expr:call,op:G(op),par:[G(expr3),G(expr3)]
    $ok= get_expr3($context,$expr2);
    $expr= (object)array('expr'=>'call','op'=>$op,'lc'=>$last_lc,
        'par'=>array($expr,$expr2),'value'=>1);
  }
  return $ok;
}
function get_expr3($context,&$expr) {
  global $last_lc, $typ, $lex, $head;
  $id= '';
  if ( get_if_id($id) ) {
    # call2 --> G(call2)
    if ( get_if_delimiter('(') ) {
      get_call2_id($context,$expr,$id,1);
    }
    else {
      # id --> {expr:name,name:id}              // id znamená vlastně id.get
      $expr= (object)array('expr'=>'name','name'=>$id);
//      $expr= (object)array('expr'=>'call','op'=>"$id.get");
      $expr->lc= $last_lc;
      $ok= true;
    }
  }
  else if ( get_if_delimiter('(') ) {
    get_expr2($context,$expr);
    get_delimiter(')');
  }
  else if ( get_if_delimiter('`') ) {
    # '`' template* '`' --> {expr:templ,par:[G(templ),...]}
    $expr= (object)array('expr'=>'templ','par'=>array());
    $ok= true;
    while ( $ok && !look_delimiter('`') ) {
      $ok= $typ[$head]=='str';
      if ( $ok ) {
        $expr->par[]= (object)array('expr'=>'value','value'=>$lex[$head],'type'=>'s');
        $head++;
      }
      elseif ( get_if_delimiter('${') ) {
        $par= null;
        $ok= get_expr2($context,$par);
        if ( $ok ) {
          $expr->par[]= $par;
          get_delimiter('}');
        }
      }
    }
    get_delimiter('`');
  }
  else {
    # value --> {expr:value,value:v,type:t}
    $expr= (object)array('expr'=>'value');
    get_value($expr->value,$expr->type);
    $ok= true;
  }
  return true;
}
# -------------------------------------------------------------------------------------------- call2
# call2   :: id  args                           --> {expr:call,op:id,par:G(args),value:$valued} 
#          | 'php' '.' id args                  --> {expr:call,op:ask,par:G("id")+G(args),value:$valued} 
#          | 'js' '.' id args                   --> {expr:call,op:apply,par:G("id")+G(args),value:$valued} 
#                                                   valued=0 => clear stack
# args    :: '(' [ arg ( ',' arg )* ] ')'       --> [G(arg),...]
# arg     :: expr2 | &id                        --> G(expr) | {expr:name,name:id,ref:1}
function get_call2_id($context,&$expr,$id,$valued) {
  global $last_lc;
  // volání funkce $id s parametry
  # id '(' ')' | id '(' expr2 ( ',' expr2 )* ')' --> {expr:call,op:id,par:[G(expr2),...]}
  $ok= true;
  $expr= (object)array('expr'=>'call','op'=>$id,'lc'=>$last_lc,'par'=>array(),'value'=>$valued);
  $fce= explode('.',$id);
  if ( $fce[0]=='php' ) {
    if ( $fce[2] ) comp_error("SYNTAX: jméno funkce v PHP nesmí být složené ");
    $expr->op= 'ask';
    $expr->par[]= (object)array('expr'=>'value','value'=>$fce[1],'type'=>'s');
  }
  elseif ( $fce[0]=='js' ) {
    if ( $fce[2] ) comp_error("SYNTAX: jméno funkce v javascriptu nesmí být složené ");
    $expr->op= 'apply';
    $expr->par[]= (object)array('expr'=>'value','value'=>$fce[1],'type'=>'s');
  }
  if ( !get_if_delimiter(')') ) {
    while ( $ok ) {
      $subexpr= null;
      get_expr2($context,$subexpr);
      $expr->par[]= $subexpr;
      $ok= get_if_delimiter(',');
    }
    get_delimiter(')');
  }
  return true;
}
# ================================================================================================== PROC code
# body  :: [ 'var' varlist ] code
# vlist :: vdef | vdef (','|'var') vlist
# vdef  :: id ':' type
# code  :: '{' alt '}'                          --> G(alt)
# alt   :: seq ( '|' seq )*                     --> {alt:[G(expr),...]}
# seq   :: expr ( ';' expr )*                   --> {seq:[G(expr),...]}
# call  :: id '(' expr ( ',' expr* ) ')'        --> {op:$id,par:[G(expr),...]}
# expr  :: call ( '.' call )*                   --> {ops:[G(call)...]}
#        | id                                   --> $id.val
#        | '°' object
#        | value                                --> $value
#        | '{' alt '}'                          --> G(alt)
#        | '[' alt ']'                          --> G(alt)
# --------------------------------------------------------------------------------------------- code
# $context je objekt se jmény formálních parametrů - překládaných jako {id:offset,...}
function get_code($context,&$code,&$vars,&$prior,&$lc_) {
  global $pos, $head;
  $code= null;
  $prior= 0;
  $vars= array();
  if ( get_if_delimiter ('/') ) {
    // priorita onstart
    $ok= get_if_number ($prior);
  }
//   else {
    get_delimiter('{');
    // případné lokální proměnné
    $lc= null;
    $ok= get_if_the_key('var',$lc);
    while ( $ok ) {
      $id= $typ= null;
      get_id($id);
      get_delimiter(':');
      get_type($typ);
      $vars[$id]= $typ;
      $ok= get_if_delimiter(',') || get_if_the_key('var',$lc);
    }
    if ( !look_delimiter('}') ) {       // je povolena prázdná procedura
      get_alt($context,$code);
    }
    $lc_= $pos[$head];
    get_delimiter('}');
//   }
  return true;
}
# ---------------------------------------------------------------------------------------------- alt
# alt   :: seq ( '|' seq )*                     --> {alt:[G(expr),...]}
function get_alt($context,&$code) {
  $alt= (object)array('expr'=>'alt');
  $alt->body= array();
  $ok= true;
  while ( true ) {
    $seq= null;
    $ok= get_seq($context,$seq);
    if ( !$ok ) break;
    $alt->body[]= $seq;
    $ok= get_if_delimiter('|');
    if ( !$ok ) { $ok= true; break; }
  }
  $code= count($alt->body)==1 ? $alt->body[0] : $alt;
  return $ok;
}
# ---------------------------------------------------------------------------------------------- seq
# seq   :: expr ( ';' expr )*                   --> {seq:[G(expr),...]}
function get_seq($context,&$code) {
  $seq= (object)array('expr'=>'seq');
  $seq->body= array();
  $ok= true;
  while ( true ) {
    $expr= null;
    $ok= get_expr($context,$expr);
    if ( !$ok ) break;
    $seq->body[]= $expr;
    $ok= get_if_delimiter(';');
    // jestliže není středník, nebo je středník následovaný } ] | je to konec sekvence
    if ( !$ok ) { $ok= true; break; }
    if ( look_delimiter('}') || look_delimiter(']') || look_delimiter('|') ) { $ok= true; break; }
  }
  $code= count($seq->body)==1 ? $seq->body[0] : $seq;
  return $ok;
}
# --------------------------------------------------------------------------------------------- call
# call  :: id '(' expr ( ',' expr* ) ')'        --> {op:$id,par:[G(expr),...]}
function get_call($context,&$expr,$id) {
  global $last_lc;
  // id => volání funkce s parametry
  $ok= true;
  $expr= (object)array();
  $expr->expr= 'call';
  $expr->op= $id;
  $expr->lc= $last_lc;
  $expr->par= array();
  while ( $ok ) {
    if ( get_if_delimiter(')') ) break;
    $subexpr= null;
    get_expr($context,$subexpr);
    $expr->par[]= $subexpr;
    $ok= get_if_delimiter(',');
    if ( !$ok ) get_delimiter(')');
  }
  return true;
}
# --------------------------------------------------------------------------------------------- expr
# expr  :: id '(' expr ( ',' expr* ) ')' | id | value | '{' alt '}' | '[' alt ']'
function get_expr($context,&$expr) {
  global $last_lc;
  $ok= get_if_delimiter('{');
  if ( $ok ) {
    # expr  :: '{' alt '}'                      --> G(alt)
    get_alt($context,$expr);
    $ok= get_if_delimiter('}');
  }
  else {
    $ok= get_if_delimiter('[');
    if ( $ok ) {
      # expr  :: '[' alt ']'                    --> {may:G(alt)}
      $expr= new stdClass();
      $expr->expr= 'may';
      get_alt($context,$expr->body);
      $ok= get_if_delimiter(']');
    }
    else {
      $id= null;
      $ok= get_if_id($id);
      if ( $ok ) {
        $ok= get_if_delimiter('(');
        if ( $ok ) {
          # expr  :: call ( '.' call )*         --> {ops:[G(call)...]}
          $n= 0;
          $calls= array();
          $expr1= null;
          get_call($context,$expr1,$id);
          $calls[]= $expr1;
          while ( get_if_delimiter('.') ) {
            $n++;
            get_id($id);
            $ok= get_if_delimiter('(');
            if ( $ok ) {
              get_call($context,$expr1,$id);
              $calls[]= $expr1;
            }
            else {
              // výběr prvku objektu
              $calls[]= (object)array('expr'=>'name','name'=>$id,'lc'=>$last_lc);
            }
          }
          if ( $n ) {
            $expr= (object)array('expr'=>'ops');
            $expr->body= $calls;
          }
          else {
            $expr= $calls[0];
          }
        }
        else if ( isset($context->$id) ) {
          # expr  :: id                         --> {par:id}
          // id => vyzvednutí hodnoty parametru
          $expr= (object)array();
          $expr->expr= 'par';
          $expr->par= $id;
          $ok= true;
        }
        else {
          # expr  :: id                         --> {name:id}
          // rozebere se v link
          $expr= (object)array();
          $expr->expr= 'name';
          $expr->name= $id;
          $expr->lc= $last_lc;
          $ok= true;
        }
      }
      else {
        # expr  :: value                        --> {value:v,type:t}
        $expr= (object)array('expr'=>'value');
        get_value($expr->value,$expr->type);
        $ok= true;
      }
    }
  }
  return true;
}
# ================================================================================================== LEXICAL
# ------------------------------------------------------------------------------------ lex_analysis2
# $dbg = false nebo pro debugger proc|func
function lex_analysis2 ($dbg=false) {
  global $tok2lex, $ezer, $keywords, $specs, $lex, $typ, $pos, $not, $gen_source, $debugger;

  // rozbor na tokeny podle PHP
  $tok= token_get_all( $dbg
    ? ("<"."?php\n $dbg _dbg_() ".'{'."$ezer \n} ?".">")
    : ("<"."?php\n $ezer ?".">"));
  $inside_template= false;
//                                                             debug($tok,'tok');
  note_time('lexical1');
  tok_positions($tok);
  note_time('lexical2');
//  if ( $pragma_strings ) tok_strings($tok);
  note_time('lexical3');
//                                                             debug($tok,'tok');
  $lex= $typ= $pos= $not= $str= array(); $k= 0;
  // poznámky začínající #$ se pokládají za vygenerované a jsou ignorovány
  // poznámky začínající # a mezerou se připojí k prvnímu klíčovému slovu s nastaveným note
  // komentáře začínající // se připojují k předcházejícímu klíčovému slovu s nastaveným cmnt
  $notes= '';
  $cmnt= 0;
  array_shift($tok);
  array_pop($tok);
  $count= count($tok);//-1;
//                                                             debug($tok,"tok $count");
  for ($i= 0; $i<$count; $i++) {
    $t= $tok[$i];
    $tp= $tok2lex[$tok[$i][0]];
    if ( $debugger ) {
      // v debuggeru může identifikátor začínat dolarem následovaným číslem
      if ( $t[1]=='$' ) {
        $tp= 'id';
        if ( $tok2lex[$tok[$i+1][0]]=='num' ) {
          $id= $t[1].$tok[$i+1][1];
          if ( substr($id,-1)=='.' ) {
            $id= substr($id,0,-1);
            $tok[$i+1][1]= '.';
          }
          else {
            $i++;
          }
          $t[1]= $id;
        }
      }
    }
    switch ( $tp ) {
    case 'blank':
      if ( $inside_template ) {
        $typ[$k]= 'str'; $lex[$k]= $t[1]; $pos[$k]= "{$t[2]},{$t[3]}"; $k++;
      }
      break;
    case 'cmnt':
      if ( $gen_source ) {
        if ( substr($t[1],0,2)=='#$' ) break;
        if ( substr($t[1],0,1)=='#' ) $notes.= $t[1];
        elseif ( substr($t[1],0,2)=='//' ) $not[$cmnt].= $t[1];
      }
      break;
    case 'id':
      $ident= $t[1];
      if ( $ident=='°' ) {              // příznak objektové konstanty
        $tp= 'del';
      }
      else {
        while ( $tok[$i+1][1]=='.' ) {
          $ident.= '.'; $i+= 2;
          if ( $tok2lex[$tok[$i][0]]!='id' ) {
            comp_error("LEXICAL ř.{$t[2]},{$t[3]} po tečce nenásleduje identifikátor ($ident)",0);
            break 3;
          }
          $ident.= $tok[$i][1];
        }
        if ( in_array($ident,$keywords) ) {
          $tp= 'key';
          if ( isset($specs[$ident]) && in_array('note' ,$specs[$ident]) ) {
            $not[$k]= $notes; $notes= '';
          }
          if ( isset($specs[$ident]) && in_array('cmnt' ,$specs[$ident]) ) $cmnt= $k;
        }
      }
      $typ[$k]= $tp; $lex[$k]= $ident; $pos[$k]= "{$t[2]},{$t[3]}"; $k++;
      break;
    case 'del':
      if ( $t[1]=='`' ) {
        $inside_template= !$inside_template;
      }
    case 'num':
      $typ[$k]= $tp; $lex[$k]= $t[1]; $pos[$k]= "{$t[2]},{$t[3]}"; $k++;
      break;
    case 'str':
      $typ[$k]= $tp; $lex[$k]= $t[1];
//      if ( $pragma_strings && isset($tok[$i][5]) ) {
//        // složený string
//        $str[$k]= array();
//        foreach($tok[$i][5] as $x) {
//          if ( $x[1]!='{' && $x[1]!='}' )
//            $str[$k][]= $x[1];
//        }
//      }
      $pos[$k]= "{$t[2]},{$t[3]}"; $k++;
      break;
    default:
      comp_error("LEXICAL ř.{$t[2]},{$t[3]}: '{$t[1]}' je nedovolený znak");
      break;
    }
  }
//                                                             debug($lex,'lex');
//                                                             debug($str,'str');
//                                                             debug($typ,'typ');
//                                                             debug($pos,'pos');
//                                                             debug($not,'not');
  return true;
}
# --------------------------------------------------------------------------------------------- ezer
function tok_positions(&$tok) {
  $line= 0; $col= 1; $count= count($tok);
  for ($i= 0; $i<$count; $i++) {
    if (is_array($tok[$i])) {
      $tok[$i][4]= token_name($tok[$i][0]);
      $c= $tok[$i][1];
    }
    else if (is_string($tok[$i])) {
      $c= $tok[$i];
      $tok[$i]= array();
      $tok[$i][0]= -1;
      $tok[$i][1]= $c;
    }
    // update line count
    $numNewLines= substr_count($c, "\n");
    if ( 1 <= $numNewLines ) {
      // have new lines, add them in
      $line+= $numNewLines;
      $col= 1;
      // skip to right past the last new line, as it won't affect the column position
      $c= substr($c, strrpos($c, "\n") + 1);
      if ($c === false) $c = '';
    }
    $tok[$i][2]= $line;
    $tok[$i][3]= $col;
    // update column count
    $col+= strlen($c);
  }
}
# --------------------------------------------------------------------------------------- get_source
# generování úseku zdrojového kódu
function get_source($start,$stop) {
  global $lex;
  $src= '';
  for ($i= $start; $i<$stop; $i++) {
    $src.= $lex[$i];
  }
  return $src;
}
# ================================================================================================== ERROR
# --------------------------------------------------------------------------------------- comp_error
function comp_error ($msg) {
  global $pos, $head, $errors, $err, $error_code_lc, $ezer_name, $ezer_app;
  $errors++;
  // zobraz řádek $line s okolím
  $in_code= preg_match("/CODE/",$msg);
  list($line,$clmn)= explode(',',$in_code ? $error_code_lc : $pos[$head]);
  $msg2= "<b>".($in_code ? "SYNTAX " : '')."$msg</b> in $ezer_name;$line,$clmn<br>";
  if ( $ezer_name ) {
    $msg2.= source_line($ezer_name,$ezer_app,$line,$clmn);
  }
  $err.= $msg2;
  throw new Exception($msg2);
}
?>
