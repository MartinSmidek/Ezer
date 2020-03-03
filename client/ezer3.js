// Ezer3.1 - část nezávislá na jQuery 
/* global Ezer, Object, Function, google, gapi, args, CKEDITOR */ // pro práci s Netbeans
"use strict";
// 'DOM' je vlastnost se kterou se smí pracovat jen jako s celkem
// (aby v některé implementaci mohla být objektem)
// ====================================================================================> Dokumentace
// značka c: třída
// značka f: interní metoda, dvojpísmenné jsou i informací pro kompilátor - funkce volané z Ezer
//           fm: metoda, ff: funkce, fs: struktura, fx: metoda s voláním ajax
//           jméno může být ve tvaru: ezer_jméno/js_jméno (např.owner/_owner)
// značka o: interní options, dvojpísmenné jsou i informací pro kompilátor - atributy bloků Ezer
//           os: string, on: number, oi: element (options/atribut)
// značka t: seznam děděných tříd
// značka a: argumenty
// značka r: výsledek
// značka s: sekce v dokumentaci
// vytvoření třídy: constructor;initialize | constructor;super
//                  kde v initialize se nastavují počáteční hodnoty vlastností
//                  (ve volání super by došlo k jejich přepsání)
//                  a volá super.initialize()
// ==========================================================================================> Block
class Block {
// common block members - there are visible from ezerscript
  constructor (owner,desc,DOM,id,skill) {
    this.initialize();
    this.file_drop_info= null;                          // stavový objekt

    // procedural part of contructor
    if ( DOM ) this.DOM= DOM;
    if ( this instanceof MenuMain )  Ezer._MenuMain= this;
    else if ( this instanceof PanelMain )  Ezer._PanelMain= this;
    this.owner= owner;
    this.skill= skill;
    if ( id ) this.id= this._id= id;
    if ( id && owner && owner.part ) owner.part[id]= this;
    this.type= desc.type;
    this.desc= desc;
    if ( desc.options!==undefined ) this.options= desc.options;
    if ( owner ) this._coord();
    this._check();
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    if ( this.DOM_initialize )
      this.DOM_initialize();
//o: Block-DOM.DOM - DOM kořen celého bloku (s tím se například posunuje při Drag)
//-
//o: Block-DOM.DOM_Block - prvek DOM do které jsou vnořeny Parts
    this.DOM_Block= null;
//os: Block._id - object identifier  (mapped to id)
    this._id= '';
    this.id= '';
//os: Block._l - left
    this._l= 0;
//os: Block._t - top
    this._t= 0;
//os: Block._w - width
    this._w= 0;
    this._w_max= 0;                                    // šířka bloku dostatečná pro vnořené bloky
//os: Block._h - height
    this._h= 0;
    this._h_max= 0;                                    // šířka bloku dostatečná pro vnořené bloky
//os: Block.tag - tag(s) for bulk operations (display,...)
//-
//os: Block.value - object value
//-
//os: Block.type - object type
    this._type= 0;
//os: Block.css - CSS class(es)
//-
//os: Block.style - CSS styles
//-
// ------------------------------------------------------------------------------------ skill
//os: Block.skill - potřebná úroveň zkušenosti uživatele pro zobrazení resp. změnu bloku.
//      Pokud je skill uváděn jako jedno slovo => definuje přístup pro změnu,
//      Pokud skill je uváděn jako 2 slova oddělená | => definuje přístup pro: čtení|zápis.
//      skill může být uváděn jako varianty oddělené středníkem - použije se varianta lepší pro uživatele
//      za znakem # mohou být naopak zákazy (oddělené středníkem) - použije se varianta horší pro uživatele
//      Základní hodnoty jsou:
//      ; 'r' : <i>redaktor</i> základní přístup
//      ; 'a' : <i>admin</i> změny nastavení aplikace, správa uživatelů
//      ; 'm' : <i>maintainer</i> programátor
    this.skill= true;                     // uživatel má oprávnění k bloku
// other private properties
    this.owner= null;
    this.options= {};
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _check
// test integrity bloku po jeho dokončení
  _check () {
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _const
// zjistí hodnotu konstanty
  _const (id) {
    // zkusíme najít konstantu v nadblocích
    var val;
    for (var o= this; o.owner; o= o.owner) {
      if ( o.part && o.part[id] && o.part[id].type=='const' ) {
        val= o.part[id].value;
        break;
      }
    }
    if ( val==undefined )
      Ezer.error("konstanta '"+id+"' nebyla nalezena",'S',this);
    return val;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _coord
// přepočítá symbolicky zadané souřadnice bloku na čísla
  _coord () {
    var b, s, xi;
    for (var x in {_l:0,_t:1,_w:2,_h:3}) {
      if ( typeof(this.options[x])=='object' ) {
//                                                 Ezer.debug(this.options[x],x);
        this[x]= 0;
        for (var i in this.options[x]) {
          xi= this.options[x][i];
          switch ((s= xi[0])) {
          case 'k':                               // jméno konstanty [k,value,id[,-]]
            this[x]+= (xi[3] && xi[3]=='-') ? -this._const(xi[2]) : this._const(xi[2]);
            break;
          case 'n':                               // číselný literál [n,value]
            this[x]+= xi[1];
            break;
          case 'l': case 't': case 'w': case 'h':
            Ezer.assert(b= this.owner.part[xi[1]],'chybný odkaz '+s,this);
            this[x]+= b['_'+s];
            break;
          case 'r':
            Ezer.assert(b= this.owner.part[xi[1]],'chybný odkaz '+s,this);
            this[x]+= b._l+b._w;
            break;
          case 'b':
            Ezer.assert(b= this.owner.part[xi[1]],'chybný odkaz '+s,this);
            this[x]+= b._t+b._h;
            break;
          case '*':
            this[x]= '100%';
            break;
          }
        }
      }
      else {
        this[x]= this.options[x];
      }
    }
  }
// ------------------------------------------------------------------------------------ coord
// dopočet hodnot souřadnic - záporně vlevo=kladně vpravo
  coord (ext) {
    var c= {};
    if ( this._l>=0 ) c.left= this._l;
    if ( this._l<0 )  c.right= -this._l;
    if ( this._t>=0 ) c.top= this._t;
    if ( this._t<0 )  c.bottom= -this._t;
    c.width= this._w;
    c.height= this._h;
    if ( ext ) {
      Object.assign(c,ext);
    }
    return c;
  }
// ------------------------------------------------------------------------------------ owner
//fm: Block.owner/_owner ()
//      vlastník objektu
  _owner () {
    return this.owner;
  }
// ------------------------------------------------------------------------------------ delete
//fm: Block.delete ()
//      vlastník objektu
  delete () {
    this.owner.part[this.id]= null;
    delete this;
    return 1;
  }
// ------------------------------------------------------------------------------------ attach_code
//fm: Block.attach_code (o)
  attach_code (o) {
    // odstraň všechny mimo procedur a proměnných
    for (var i in this.part) {
      var p= this.part[i];
      if ( p instanceof Block && p.type!='proc' ) {
        p.delete();
      }
    }
    this.DOM_destroy();                                   // vymaž viditelné prvky
    if ( this.DOM_re1 ) this.DOM_re1();                   //
    this.subBlocks(o,this.DOM,null,'rewrite');            // true => doplnění a přepis
    if ( this.DOM_re2 ) this.DOM_re2();                   // specificky doplní menu
    // zajištění šíření událostí pro onready a onbusy
    var oneval= this.oneval;
    if ( this.part.onready||this.part.onbusy ) {
      this.evals= 0;
      oneval= this;
    }
    this.start([],oneval);
    if ( this.excite ) {
      this.excited= false;
      this.excite();
    }
    if ( window.top.dbg && window.top.dbg.show_run ) window.top.dbg.show_run();
    return true;
  }
// ------------------------------------------------------------------------------------ self_sys
//fm: Block.self_sys ([excluding_root=0])
//      vrátí objekt {sys:...,title:...} kde sys je vytvořené zřetězením atributu _sys
//      od this ke kořenu aplikace a title je zřetězením odpovídajících title
//      s odstraněnými formátovacími znaky;
//      pokud je excluding_root=1 bude vynechána nejvyšší úroveň (root aplikace)
  self_sys (excluding_root) {
    excluding_root= excluding_root||0;
    var id= '', tit= '';
    for (var o= this; o.owner; o= o.owner) {
      if ( excluding_root && !o.owner.owner )
        break;
      if ( o.options._sys ) {
        id= (o.options._sys=='*'?o.id:o.options._sys)+(id ? '.'+id : '');
        tit= (Ezer.fce.strip_tags(o.options.title||'')) + (tit ? '|'+tit : '');
        tit= Ezer.fce.replace(tit,"\\[fa-[^\\]]+\\]",'');
      }
    }
    if ( id=='' ) id= '@';
    return {sys:id,title:tit};
  }
// ------------------------------------------------------------------------------------ self
//fm: Block.self ()
//      vrátí absolutní jméno this
//r: $.test....
  self () {
    var id= '';
    for (var o= this; o.owner; o= o.owner) {
      if ( o.type!='form' )
        id= o.id+(id ? '.'+id : '');
    }
    return '$.'+id;
  }
// ------------------------------------------------------------------------------------ set attrib
//fm: Block.set_attrib (name,val[,desc=])       nedokumentováno, může být změněno
//      změní hodnotu atributu 'name' na 'val'
//      name může být složeným jménem
//      pokud je val objekt, bude jím nahrazena celá hodnota - narozdíl od add_attrib
//      pokud je definováno desc bude změna provedena v popisu (ve this.desc, ne v this)
//a: name - jméno atributu
//   val - nová hodnota atributu
  set_attrib (name,val,desc) {
    Ezer.assert(typeof(name)=='string','první parametr není jménem atributu',this);
    var ids= name.split('.');
    Ezer.assert(desc===undefined || this.desc.part[desc],desc+" není popsáno v "+this.id,this);
    var o= desc===undefined ? this.options : this.desc.part[desc].options;
    for (var i= 0; i<ids.length-1; i++) {
      Ezer.assert(o[ids[i]],name+" je chybné jméno v set_attrib",this);
      o= o[ids[i]];
    }
    o[ids[i]]= val;
    // promítni změnu do DOM pro: help
    if ( name=='help' && this.DOM_Block ) {
      if ( this.DOM_Block instanceof jQuery )
        this.DOM_Block.attr('title',val);
      else
        this.DOM_Block.set('title',val);
    }
    // promítni změnu do DOM pro: popup.title (nesmí být prázdné)
    if ( name=='title' && this instanceof PanelPopup ) {
      this.DOM.find('div.pop_head span').first().html(val);
    }
    else if ( name=='title' && this.DOM_Label ) {
      this.DOM_Label.html(val);
    }
    else if ( name=='title' && this instanceof MenuGroup ) {
      this.DOM_Block.find('a').html(val);
    }
    // oživ show.map_pipe
    if ( name=='map_pipe' && this instanceof Show ) {
      this.start();
      this._start2();
    }
    // oživ select.options
    if ( name=='options' && this instanceof SelectMap ) {
      this._options_load();
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ add attrib
//fm: Block.add_attrib (name,val)
//      pokud val není objekt, je funkce stejná jako set_attrib;
//      pokud je val objekt, budou jeho položky přidány k dosavadním,
//      narozdíl od set_attrib, které nahradí
//a: name - jméno atributu
//   val - nová hodnota atributu
  add_attrib (name,val) {
    Ezer.assert(typeof(name)=='string','první parametr není jménem atributu',this);
    var ids= name.split('.');
    var o= this.options;
    for (var i= 0; i<ids.length-1; i++) {
      Ezer.assert(o[ids[i]],name+" je chybné jméno v add_attrib",this);
      o= o[ids[i]];
    }
    if ( typeof(val)=='object' ) {
      for (let p in val) {
        o[ids[i]][p]= val[p];
      }
    }
    else {
      o[ids[i]]= val;
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ _part
//fm: Block.part/_part (name[,n,[attr,value]])
//      pokud není uvedeno n vrátí podblok daného složeného jména
//        (pokud podblok začíná $ pak se chápe jako absolutní cesta);
//      pokud je uvedeno n vrátí n-tý podblok jehož typ vyhovuje podmínce '^value'
//        (pokud name neni reg.expr, je to vlastně dotaz na shodu s prefixem name);
//      pokud je uvedeno attr a value, hledá podblok jehož atribut 'attr' vyhovuje podmínce 'value'
//      při nenalezení vrací 0
//      (pokud je blok proměnnou aplikuje postup na hodnotu)
//r: objekt
  _part (name,n,attr,value) {
    var o= 0, k= 1;
    var b= (this.type=='var'||this.type=='view') ? this.value : this;
    if ( attr ) {
      for (let i in b.part) {
        let p= b.part[i];
        if ( p.options[attr] ) {
          let re= new RegExp(value);
          if ( re.test(p.options[attr]) ) {
            o= p;
            break;
          }
        }
      }
    }
    else if ( n ) {
      for (let i in b.part) {
        var p= b.part[i];
        if ( p.type ) {
          let re= new RegExp('^'+name);
          if ( re.test(p.type) ) {
            if ( n==k++ ) {
              o= p;
              break;
            }
          }
        }
      }
    }
    else {
      var ids= typeof(name)=='string' ? name.split('.') : [name];
      if ( ids[0]=='$' ) {
        // pokud name je absolutní
        o= Ezer.run.$;
        ids.shift();
      }
      else {
        o= b;
      }
      for (let i= 0; i<ids.length; i++) {
        if ( o.part && o.part[ids[i]] )
          o= o.part[ids[i]];
        else if ( (o.type=='var'||o.type=='view') && o.value && o.value.part && o.value.part[ids[i]]) {
          o= o.value.part[ids[i]];
        }
        else {
          o= 0;
          break;
        }
      }
    }
    return o;
  }
// ------------------------------------------------------------------------------------ call
//fm: Block.call/_call (name,a1,...)
//      asynchronně zavolá proceduru daného složeného jména vnořenou do bloku a předá argumenty
//      (interně má jméno _call)
//r: objekt
  _call (lc,name,...args) {
    var o= 0, ok= 0;
    var b= this.type=='var' ? this.value : this;
    var ids= typeof(name)=='string' ? name.split('.') : [name];
    o= b;
    for (var i= 0; i<ids.length; i++) {
      if ( o.part && o.part[ids[i]] )
        o= o.part[ids[i]];
      else if ( o.type=='var' && o.value && o.value.part && o.value.part[ids[i]]) {
        o= o.value.part[ids[i]];
      }
      else {
        o= 0;
        break;
      }
    }
    // pokud se jméno povedlo vyřešit
    if ( o && o.type=='proc' ) {
      var ret= new Eval([{o:'c',i:o.id,a:args.length,s:lc}],o.context,args,o.id);
      ok= ret.value;
    }
    return ok;
  }
// ------------------------------------------------------------------------------------ file_drop
//fm: Block.file_drop (goal,[options])                                                  OBSOLETE
//      aktivuje element goal pro příjem souboru pomocí File Api
//      po přečtení obsahu souboru zavolá
//      zavolá proceduru daného složeného jména vnořenou do bloku a předá ji argument
//      {name,size,type,text};
//      options.transfer může mít hodnoty: url,binary a určuje kódování položky text;
//      pro obrázky lze zadat maximální rozměr (max_width,max_height) na který bude
//      provedeno resample před odesláním na server;
//      OMEZENÍ: pokud bylo do oblasti přetaženo najednou více souborů, reaguje pouze na první
//a: options - default je
//             {goal:'drop',css_hover:'drop_area_hover',css_run:'drop_area_run',handler:'ondrop',
//              transfer:'url',max_width:null,max_height:null}
//r: 1 - pokud se inicializace oblasti a ovladače povedla; 0 - pokud došlo k chybě
  file_drop (goal,user_options) {
    OBSOLETE('Block.file_drop',"label.drop"); return 0;
/*
    var options= Object.assign(
      {goal:'drop',css_hover:'drop_area_hover',css_run:'drop_area_run',
       handler:'ondrop',transfer:'url'}
      , user_options||{});
    this.file_drop_obj= {state:'wait'};
    var ctx= [];
    var area= this.DOM_Block||this.value.DOM_Block;
    var goal= goal.DOM_Block;
    var ok= window.File && goal && area ? 1 : 0;
    if ( ok && 1==Ezer.run_name(options.handler,this,ctx) && ctx[0].type=='proc' ) {
      goal.removeClass(options.css_hover).removeClass(options.css_run);
      area.addEventListener('dragover', function(evt) {
        evt.preventDefault();
        goal.addClass(options.css_hover);
      }, true);
      area.addEventListener('dragleave', function(evt) {
        evt.preventDefault();
        goal.removeClass(options.css_hover);
      }, true);
      area.addEventListener('drop', function(evt) {
        evt.preventDefault();
      }, true);
      goal.addEventListener('drop', function(evt) {
        if ( this.file_drop_obj.state=='wait' ) {
          this.file_drop_obj.state= 'busy';
          goal.removeClass(options.css_hover).addClass(options.css_run);
          evt.stopPropagation();
          evt.preventDefault();
          var f= evt.dataTransfer.files[0]; // first from FileList object
          if ( f ) {
            this.file_drop_info= {name:f.name,size:f.size,type:f.type,text:null};
            var r= new FileReader();
            r.onload= function(e) {
              var x= e.target.result;
              // pokud je definováno omezení velkosti, zmenši obrázek
              if ( this.file_drop_info.type.substr(0,5)=='image'
                && (options.max_width || options.max_height) ) {
                Resample(x,options.max_width,options.max_height, function(data64){
                  this.file_drop_info.text= data64; // výstup je base 64 encoded
                  //$("StatusIcon_idle").src= data64;
                  this._call(0,options.handler,this.file_drop_info)
                  //$("StatusIcon_idle").src= null;
                }.bind(this));
              }
              else {
                if ( options.transfer=='base64' )
                  x= base64_encode(x);
                this.file_drop_info.text= x;
              }
              this._call(0,options.handler,this.file_drop_info);  // uživatelská funkce ondrop
            }.bind(this);
            switch(options.transfer) {
            case 'base64':
              r.readAsBinaryString(f); break;
            case 'text':
              r.readAsText(f); break;
            case 'url':
              r.readAsDataURL(f); break;
            }
          }
        }
      }.bind(this),false);
    }
    else
      ok= 0;
    return ok;
*/
  }
//------------------------------------------------------------------------------------- dump
//fm: Block.dump ([opt])
//      vytvoří objekt obsahující názvy a hodnoty proměnných, výpis lze ovlivnit řetězem opt:
//      o: hodnoty objektů
  dump (opt) {
    function dump_form(f) {
      var dmp= {};
      dmp['key '+(f._key_id||'?')]= f.key();
      Object.assign(dmp,f.dump());
      return dmp;
    }
    function dump_area(a) {
      return a.dump();
    }
    var v= {};
    // projdi proměnné
    for(var i in this.part) {
      var part= this.part[i];
      if ( part instanceof Var ) {
        if ( part._of=='form' && opt && opt.indexOf('F')>=0 )
          v['form '+part.id]= part.value ? dump_form(part.value) : null;
        else if ( part._of=='area' && opt && opt.indexOf('A')>=0 )
          v['area '+part.id]= part.value ? dump_area(part.value) : null;
        else if ( part._of!='form' && part._of!='area' )
          v[part._of+' '+part.id]=
            typeof(part.value)=='object' ? (part.value==null ? null :
              ( opt && opt.indexOf('O')>=0 ? part.value : '<i>object</i>' ) ) :
            part.value;
      }
    }
    return v;
  }
// ------------------------------------------------------------------------------------ enable
//fm: Block.enable ([on[,tags]])
//      Nastaví vlastnost enable podle on;
//      pokud je uveden regulární výraz tags, provede se pro přímé podbloky (a hodnoty use)
//      s atributem tag vyhovujícím dotazu;
//      v bezparametrické podobě vrací 1, pokud je blok ve stavu enabled
//a: on - 0 | 1
//   tags - regulární výraz popisující vyhovující tagy (např. 'f.|g')
  enable (enabled,tags) {
    var ok= 1;
    if ( enabled===undefined ) {
      ok= this.options.enabled||0;
    }
    else {
      enabled= enabled=="0" ? 0 : enabled;
      if ( tags ) {
        var re= new RegExp(tags);
        // proveď změnu enable pro podbloky s atributem tag vyhovujícím dotazu
        var block= this instanceof Var && this.value ? this.value : this;
        for(var i in block.part) {
          var part= block.part[i];
          if ( part.DOM_Block && part.options.tag && re.test(part.options.tag) ) {
            part.options.enabled= enabled;
            part.DOM_enabled(enabled);
          }
        }
      }
      else if ( this.DOM_Block ) {
        this.options.enabled= enabled;
        this.DOM_enabled(enabled);
      }
    }
    return ok;
  }
// ------------------------------------------------------------------------------------ display
//fm: Block.display ([on[,tags]])
//      zobrazí pokud on=1 resp. skryje blok pokud on=0;
//      na skryté bloky (např. kvůli skill) nemá vliv;
//      pokud je uveden regulární výraz tags, provede se pro přímé podbloky (a hodnoty use)
//      s atributem tag vyhovujícím dotazu.
//      V tom případě lze nastavením on=2 zobrazit vybrané a skrýt ty jejichž tag nevyhovuje;
//      v bezparametrické podobě vrací 1, pokud je blok viditelný.
//      Pokud je atribut tag ve formě seznamu (čárkou oddělené hodnoty) metoda se uplatní pokud
//      alespoň jedna hodnota vyhoví regulárnímu výrazu.
//      Je-li on=2 uplatní se, pokud nevyhovuje ani jedna hodnota.
//a: on - 0 | 1 | 2
//   tags - regulární výraz popisující vyhovující tagy (např. 'f.|g')
  display (on,tags) {
    function displ(parts) {
      for (var i in parts) {
        var part= parts[i];
        var block= part instanceof Var && part.value ? part.value.DOM_Block : (
                   part instanceof MenuGroup ? part.DOM : part.DOM_Block);
        if ( block && part.options.tag ) {
          var tag_list= part.options.tag.toString().split(',');
          var some= tag_list.some(function(tag){
            return re.test(tag);
          });
          if ( some )
            DOM_display(block,on);
//             block.setStyles({display:on ? 'block' : 'none'});
          else if ( on==2 )
            DOM_display(block,0);
//             block.setStyles({display:'none'});
        }
      }
    }
    var ok= 1;
    if ( on===undefined ) {
      var block= this instanceof PanelPopup        ? this.DOM : (
                 this instanceof Var && this.value ? this.value.DOM_Block
                                                        : this.DOM_Block);
      ok= block && DOM_display(block);
    }
    else {
      on= on=="0"||on===null ? 0 : on;
      if ( tags ) {
        var re= new RegExp(tags);
        // proveď změnu display pro podbloky s atributem tag vyhovujícím dotazu
        displ(this.part);
        if ( this instanceof Var && this.value && this.value.part )
          displ(this.value.part);
      }
      else if ( this instanceof Var ) {
        if ( this.value && this.value.DOM_Block ) {
          DOM_display(this.value.DOM_Block,on);
//           this.value.DOM_Block.setStyles({display:on ? 'block' : 'none'});
        }
      }
      else if ( this instanceof MenuGroup ) {
        DOM_display(this.DOM,on);
//         this.DOM.setStyles({display:on ? 'block' : 'none'});
      }
      else if ( this.DOM_Block ) {
        DOM_display(this.DOM_Block,on);
//         this.DOM_Block.setStyles({display:on ? 'block' : 'none'});
      }
    }
    return ok;
  }
//------------------------------------------------------------------------------------- set_css
//fm: Block.set_css (id1,id2[,tags])
//      objektu v kontextu je id2 je ubráno a id1 přidáno jako css-třída (id1, id2 mohou být prázdné
//      nebo seznam jmen oddělených mezerou). Pokud je uveden seznam tags (oddělovač je čárka),
//      provede se pro přímé podbloky s atributem tag vyhovujícím regulárnímu dotazu v tags
//a: id1 - přidávané třídy
//   od2 - ubírané třídy
//   tags - regulární výraz popisující vyhovující tagy (např. 'f.|g')
  set_css (id1,id2,tags) {
    if ( tags ) {
      var re= new RegExp(tags);
      // proveď změnu enable pro podbloky s atributem tag vyhovujícím dotazu
      var parts= this instanceof Var && this.value ? this.value.part : this.part;
      for(const i in parts) {
        const part= parts[i];
        if ( part.DOM_Block && part.options.tag && re.test(part.options.tag) ) {
//           if ( id2 ) id2.split(' ').each(function(id){part.DOM_Block.removeClass(id)});
//           if ( id1 ) id1.split(' ').each(function(id){part.DOM_Block.addClass(id)});
          if ( id2 ) part.DOM_Block.removeClass(id2);
          if ( id1 ) part.DOM_Block.addClass(id1);
        }
      }
    }
    else {
      var dom= jQuery(this instanceof Var && this.value ? this.value.DOM_Block : this.DOM_Block);
      if ( dom.length ) {
//         if ( id2 ) id2.split(' ').each(function(id){dom.removeClass(id)}.bind(this));
//         if ( id1 ) id1.split(' ').each(function(id){dom.addClass(id)}.bind(this));
        if ( id2 ) dom.removeClass(id2);
        if ( id1 ) dom.addClass(id1);
      }
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ property
//fm: Block.property (props[,tags])
// změní styly bloku podle parametru, pro šířku a výšku lze pro místo hodnoty dát *
// EXPERIMENTÁLNÍ:
// pokud je tags, pak se změna týká elementů v bloku, jejichž tag vyhovuje tags (jako pro display)
// mohou být použity tyto pseudo-vlastnosti:
//      down:n resp. aside:n posunou element proti originální poloze dolů resp. do strany
//      smooth:1 transformaci provede funkce morph z mootools s defaultním options
//      smooth:x funkci morph bude předáno x.transition, x.duration do options
//               pokud bude definováno x.onproperty jako ezer-objekt bude v něm po skončení
//               transition zavolána procedura onproperty
//      return:'bounds' metoda vrátí rozměry ohraničujícího obdélníku (před případnou transformací)
//      reset:
//a: height:*   - upraví výšku podle nejvyššího obsaženého elementu (s opravou u panelu na levé menu)
//   min_height:n - minimální výška
//   width:*    - upraví šířku podle nejširšího obsaženého elementu (s opravou u panelu na levé menu)
//   min_width:n - minimální šířka
//r: object - pozice a velikost ohraničujícího obdélníku, pokud props.return='bounds'
  property (props,tags) {
    var rect= {_l:undefined,_t:undefined,_r:undefined,_b:undefined};
    // pomocné funkce
    function mmax(a,b) { return (a==undefined || a<b) ? b : a; }
    function mmin(a,b) { return (a==undefined || a>b) ? b : a; }
    function bounds(block) {
      var dom= block.DOM_Block;
      var _l= Number.parseInt(dom.css('left')),
          _t= Number.parseInt(dom.css('top'));
      var size= dom.measure(function(){
        var rsiz= {x:this.width(),y:this.height()};  // x, y
        var label= this.find('div.Label3');
        if ( label.length ) {
          // pokud je u elementů použito ^title, je třeba opravit hranice rect
          var lpos= {x:label.actual('position').left,y:label.actual('position').top};     // x, y
          var lsiz= {x:label.actual('width'),y:label.actual('height')};  // x, y
          // opravy rect (_l, _r, _t, _b)
          rect._l= mmin(rect._l,_l+lpos.x);
          rect._r= mmax(rect._r,_l+lpos.x+lsiz.x);
          rect._t= mmin(rect._t,_t+lpos.y);
          rect._b= mmax(rect._b,_t+lpos.y+lsiz.y);
        }
        return rsiz;
      });
      rect._l= mmin(rect._l,_l);
      rect._t= mmin(rect._t,_t);
      rect._r= mmax(rect._r,_l+size.x); // = right
      rect._b= mmax(rect._b,_t+size.y);
    }
    function rects(block) {
      var dom= block.DOM_Block;
      var _l= Number.parseInt(dom.css('left')),
          _t= Number.parseInt(dom.css('top'));
      var size= dom.getSize();                 // x, y
      rect._l= mmin(rect._l,_l);
      rect._t= mmin(rect._t,_t);
      rect._r= mmax(rect._r,_l+size.x); // = right
      rect._b= mmax(rect._b,_t+size.y);
    }
    // výpočet
    if ( tags ) {
      var re= new RegExp(tags);
      var parts= this instanceof Var && this.value ? this.value.part : this.part;
      // proveď změnu pro podbloky s atributem tag vyhovujícím dotazu
      for (var i in parts) {
        var part= parts[i];
        var block= part instanceof Var && part.value ? part.value : part;
        if ( block && block.DOM_Block && part.options.tag ) {
          var tag_list= part.options.tag.split(',');
          var some= tag_list.some(function(tag){
            return re.test(tag);
          });
          if ( some ) {
            if ( props.return=='bounds' ) bounds(block);
            else if ( props.return=='rects' ) rects(block);
            block.DOM_set_properties(props);
            // zabráníme vícenásobnému volání onproperty
            if ( props.smooth && props.smooth.onproperty )
              props.smooth.onproperty= undefined;
          }
        }
      }
    }
    else if ( this instanceof Var ) {
      if ( this.value && this.value.DOM_Block ) {
        if ( props.return=='bounds' ) bounds(this.value);
        else if ( props.return=='rects' ) rects(this.value);
        this.value.DOM_set_properties(props);
      }
    }
    else if ( this.DOM_Block ) {
      if ( props.return=='bounds' ) bounds(this);
      else if ( props.return=='rects' ) rects(this);
      this.DOM_set_properties(props);
    }
    if ( props.return=='bounds' ) {
      rect._w= rect._l==undefined ? 0 : rect._r - rect._l;
      rect._h= rect._t==undefined ? 0 : rect._b - rect._t;
    }
    else if ( props.return=='rects' ) {
      rect._w= rect._l==undefined ? 0 : rect._r - rect._l;
      rect._h= rect._t==undefined ? 0 : rect._b - rect._t;
    }
    else rect= 1;
    return rect;
  }
// -------------------------------------------------------------------------------------- raise
//fm: Block.raise (event_name[,arg])
  raise (event_name,arg) {
    this.fire(event_name,[arg]);
    return 1;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  . subBlocks
//Block.subBlocks (desc,DOM,wrap_fce,extend)
//      zapojí části bloku do bloku
//   extend=='rewrite' pro přepsání bloků
//   extend=='include' pro přidání vnitřních bloků
//   extend=='dom_only' pro pouhý přepis DOM - volá se reinitialize
//a: wrap_fce - nepovinná funkce, která je volána po zapojení části do celku
//s: system
  subBlocks (desc0,DOM,wrap_fce,extend) {
    // vložení případných vnořených částí, pokud na to je dostatečné oprávnění
    var us= Ezer.sys.user ? Ezer.sys.user.skills : '';
    if ( Ezer.options.autoskill )
      us+= ' '+Ezer.options.autoskill;
    if ( desc0 && desc0.part ) {
      if ( !extend || this.part===undefined ) this.part= {};
      for (var name in desc0.part) {
        var desc= desc0.part[name];
//                                                 Ezer.trace('L','subBlocks of '+this.type+' '+this.id+': '+desc.type+' '+name);
        if ( this.value && this.value.part && this.value.part[name] && this.value.part[name].type=='proc') {
          // přepis kódu procedury v use
          this.value.part[name].code= desc0.part[name].code;
        }
        else if ( this.part && this.part[name] ) {
          let part= this.part[name];
          if ( extend=='include' ) {
            part.subBlocks(desc,part.DOM,null,extend);
            if ( part.reinitialize )
              part.reinitialize(desc);
          }
          else if ( extend=='dom_only' ) { // využívá jen browse pro show
            part.subBlocks(desc,part.DOM,null,extend);
            if ( part instanceof Show )
              part.reinitialize();
          }
        }
        else {
          // vytvoř ještě nevytvořené (liší se při include)
          // zjistí, jaké skill má přihlášený uživatel pro blok popsaný desc
          // skill je uváděn jako jedno slovo => definuje přístup pro změnu
          // skill je uváděn jako slova oddělená | => definuje přístup pro: čtení|zápis|specifické...
          // za znakem # mohou být zákazy pro nositele daného oprávnění (oddělené středníkem)
          // 0 - nemá právo, 1 - smí jen vidět, 2 - smí i měnit
          var nok, ok= 1, a= desc.options?desc.options.skill:null, skill= 2;
          if ( a ) {
//             var as, bs= a.clean().split('#');
            var as, bs= a.replace(/\s+/g, ' ').trim().split('#');
            if ( bs.length>1 ) {
              a= bs[0];
              as= bs[1].replace(/\s+/g, ' ').trim().split(';');
              for (let ai= 0; ai<as.length; ai++) {
                // probereme všechny zákazy skill
                nok= us.split(' ').includes(as[ai]) ? 1 : 0;
                if ( nok ) {
                  ok= skill= 0;
                  break;
                }
              }
            }
            // pokud nebyl explicitní zákaz
            if ( a && skill ) {
              ok= 1;
              as= a.replace(/\s+/g, ' ').trim().split(';');
              for (let ai= 0; ai<as.length; ai++) {
                // probereme všechny varianty skill
                var aa= as[ai].replace(/\s+/g, ' ').trim().split('|');
                ok= us && us.split(' ').includes(aa[0]) ? 1 : 0;
                if ( ok && (aa.length==1 || (aa.length==2 && !us.split(' ').includes(aa[1]))) )
                  skill= 1;
                // spokojíme se s první pro uživatele úspěšnou
                if ( ok )
                  break;
              }
            }
          }
          if ( ok ) {
            var id= name, context= this;
            if ( name.indexOf('.')>0 ) {
              // složené jméno => zjistíme kontext opravy
              var corr= [], known, ids= name.split('.');
              known= Ezer.run_name(name,this,corr,ids);
              switch (known) {
              case 1:
                id= corr[0].id;
                context= corr[1];
                break;
              case 2:
                id= ids[ids.length-1];
                context= corr[0];
                break;
              case 3:
                Ezer.error('složené jméno '+name+' obsahuje jméno objektové proměnné');
                break;
              default:
                continue;
  //               Ezer.error('složené jméno '+name+' nelze v '+this.type+' '+this.id+' pochopit');
              }
              if ( DOM ) DOM= context.DOM;
            }
            let part= null;
            switch (desc.type) { // ==> SWITCH
              // s vizualizací
//               case 'browse':
//               case 'browse.smart':  part= new Ezer.Browse(this,desc,DOM,id,skill); break;
              case 'browse':
              case 'browse.smart':  part= new Browse(this,desc,DOM,id,skill); break;
//               case 'button':        part= Ezer.options.awesome & 2
//                                         ? new Ezer.ButtonHtml(this,desc,DOM,id,skill)
//                                         : new Ezer.Button(this,desc,DOM,id,skill); break;
              case 'button':        part= new Button(this,desc,DOM,id,skill); break;
              case 'button.html':   part= new Button(this,desc,DOM,id,skill); break;
//               case 'button.html':   part= new Ezer.ButtonHtml(this,desc,DOM,id,skill); break;
              case 'button.submit': part= new Button(this,desc,DOM,id,skill); break;
//               case 'button.submit': part= new Ezer.Button(this,desc,DOM,id,skill); break;
              case 'button.reset':  part= new Button(this,desc,DOM,id,skill); break;
//               case 'button.reset':  part= new Ezer.Button(this,desc,DOM,id,skill); break;
//               case 'button.upload': part= new Ezer.Button(this,desc,DOM,id,skill); break;
//               case 'case':          part= new Ezer.Case(this,desc,DOM,id,skill); break;
              case 'case':          part= new Case(this,desc,DOM,id,skill); break;
//               case 'chat':          part= new Ezer.Chat(this,desc,DOM,id,skill); break;
              case 'chat':          part= new Chat(this,desc,DOM,id,skill); break;
//               case 'check':         part= new Ezer.Check(this,desc,DOM,id,skill); break;
              case 'check':         part= new Check(this,desc,DOM,id,skill); break;
//               case 'edit':          part= new Ezer.Edit(this,desc,DOM,id,skill); break;
//               case 'edit.html':     part= new Ezer.EditHtml(this,desc,DOM,id,skill); break;
              case 'edit':          part= new Edit(this,desc,DOM,id,skill); break;
              case 'edit.html':     part= new EditHtml(this,desc,DOM,id,skill); break;
              case 'edit.auto':     part= new EditAuto(this,desc,DOM,id,skill); break;
//               case 'field':         part= new Ezer.Field(this,desc,DOM,id,skill); break;
              case 'field':         part= new Field(this,desc,DOM,id,skill); break;
//               case 'field.date':    part= new Ezer.FieldDate(this,desc,DOM,id,skill); break;
              case 'field.date':    part= new FieldDate(this,desc,DOM,id,skill); break;
//               case 'field.list':    part= new Ezer.FieldList(this,desc,DOM,id,skill); break;
              case 'field.list':    part= new FieldList(this,desc,DOM,id,skill); break;
//               case 'item':          part= new Ezer.Item(this,desc,DOM,id,skill); break;
              case 'item':          part= new Item(this,desc,DOM,id,skill); break;
              case 'item.clipboard':part= new Item(this,desc,DOM,id,skill); break;
//               case 'list':          part= new Ezer.List(this,desc,DOM,id,skill); break;
              case 'list':          part= new List(this,desc,DOM,id,skill); break;
//               case 'label':         part= new Ezer.Label(this,desc,DOM,id,skill); break;
              case 'label':         part= new Label(this,desc,DOM,id,skill); break;
//               case 'label.drop':    part= new Ezer.LabelDrop(this,desc,DOM,id,skill); break;
              case 'label.drop':    part= new LabelDrop(this,desc,DOM,id,skill); break;
//               case 'label.map':     part= new Ezer.LabelMap(this,desc,DOM,id,skill); break;
              case 'label.map':     part= new LabelMap(this,desc,DOM,id,skill); break;
//               case 'menu.main':     part= new Ezer.MenuMain(this,desc,DOM,id,skill); break;
              case 'menu.main':     part= new MenuMain(this,desc,DOM,id,skill); break;
//               case 'menu.left':     part= new Ezer.MenuLeft(this,desc,DOM,id,skill); break;
              case 'menu.left':     part= new MenuLeft(this,desc,DOM,id,skill); break;
//               case 'menu.group':    part= new Ezer.MenuGroup(this,desc,DOM,id,skill); break;
              case 'menu.group':    part= new MenuGroup(this,desc,DOM,id,skill); break;
//               case 'menu.context':  part= new Ezer.MenuContext(this,desc,DOM,id,skill); break;
              case 'menu.context':  part= new MenuContext(this,desc,DOM,id,skill); break;
//               case 'panel':         part= new Ezer.Panel(this,desc,DOM,id,skill); break;
              case 'panel':         part= new Panel(this,desc,DOM,id,skill); break;
//               case 'panel.main':    part= new Ezer.PanelMain(this,desc,DOM,id,skill); break;
              case 'panel.main':    part= new PanelMain(this,desc,DOM,id,skill); break;
//               case 'panel.plain':   part= new Ezer.PanelPlain(this,desc,DOM,id,skill); break;
              case 'panel.plain':   part= new PanelPlain(this,desc,DOM,id,skill); break;
//               case 'panel.popup':   part= new Ezer.PanelPopup(this,desc,DOM,id,skill); break;
              case 'panel.popup':   part= new PanelPopup(this,desc,DOM,id,skill); break;
//               case 'panel.free':    part= new Ezer.PanelFree(this,desc,DOM,id,skill); break;
              case 'panel.free':    part= new PanelFree(this,desc,DOM,id,skill); break;
//               case 'panel.right':   part= new Ezer.PanelRight(this,desc,DOM,id,skill); break;
              case 'panel.right':   part= new PanelRight(this,desc,DOM,id,skill); break;
//               case 'radio':         part= new Ezer.Radio(this,desc,DOM,id,skill); break;
              case 'radio':         part= new Radio(this,desc,DOM,id,skill); break;
//               case 'select':        part= new Ezer.Select(this,desc,DOM,id,skill); break;
              case 'select':        part= new Select(this,desc,DOM,id,skill); break;
//               case 'select.multi':  part= new Ezer.Select(this,desc,DOM,id,skill,true); break;
              case 'select.multi':  part= new Select(this,desc,DOM,id,skill,true); break;
//               case 'select.auto':   part= new Ezer.SelectAuto(this,desc,DOM,id,skill); break;
              case 'select.auto':   part= new SelectAuto(this,desc,DOM,id,skill); break;
//               case 'select.map':    part= new Ezer.SelectMap(this,desc,DOM,id,skill); break;
              case 'select.map':    part= new SelectMap(this,desc,DOM,id,skill); break;
//               case 'select.map+':   part= new Ezer.SelectMap(this,desc,DOM,id,skill,true); break;
              case 'select.map+':   part= new SelectMap(this,desc,DOM,id,skill,true); break;
//               case 'select.map0':   part= new Ezer.SelectMap0(this,desc,DOM,id,skill); break;
              case 'select.map0':   part= new SelectMap0(this,desc,DOM,id,skill); break;
//               case 'select.map0+':  part= new Ezer.SelectMap0(this,desc,DOM,id,skill,true); break;
              case 'select.map0+':  part= new SelectMap0(this,desc,DOM,id,skill,true); break;
//               case 'show':
//               case 'show.smart':    part= new Ezer.Show(this,desc,DOM,id,skill); break;
              case 'show':
              case 'show.smart':    part= new Show(this,desc,DOM,id,skill); break;
//               case 'tabs':          part= new Ezer.Tabs(this,desc,DOM,id,skill); break;
              case 'tabs':          part= new Tabs(this,desc,DOM,id,skill); break;
//               case 'tabs.logoff':   part= new Ezer.Tabs(this,desc,DOM,id,skill); break;
              case 'tabs.logoff':   part= new Tabs(this,desc,DOM,id,skill); break;
              // s potenciální vizualizací
//               case 'var':           part= new Ezer.Var(this,desc,DOM,id); break;
              case 'var':           part= new Var(this,desc,DOM,id); break;
              // objekt bez vizualizace (ale vložený jako part)
//               case 'view':          part= new Ezer.View(this,desc,DOM,id); break;
              case 'view':          part= new View(this,desc,DOM,id); break;
//               case 'group':         part= new Ezer.Group(this,desc,null,id); break;
              case 'group':         part= new Group(this,desc,null,id); break;
//               case 'const':         part= new Ezer.Const(this,desc,null,id); break;
              case 'const':         part= new Const(this,desc,null,id); break;
//               case 'report':        part= new Ezer.Report(this,desc,null,id); break;
              case 'report':        part= new Report(this,desc,null,id); break;
//               case 'box':           part= new Ezer.Box(this,desc,null,id); break;
              case 'box':           part= new Box(this,desc,null,id); break;
//               case 'table':         part= new Ezer.Table(this,desc,null,id); break;
              case 'table':         part= new Table(this,desc,null,id); break;
//               case 'map':           part= new Ezer.Map(this,desc,null,id); break;
              case 'map':           part= new EzerMap(this,desc,null,id); break;
//               case 'proc':          part= new Ezer.Proc(this,desc,this); break;
              case 'proc':          part= new Proc(this,desc,this); break;
              // přeskakované (informace dostupné přes Ezer.code)
              case 'area':          break;
              case 'form':          break;
              case 'number':        break;
              case 'text':          break;
              case 'date':          break;
              default:
                Ezer.error('neimplementovaný blok '+desc.type,'C');
            }
            if ( desc.library ) {
              part._library= 1;
            }
            if ( part ) {
              // nově vložená část
              part.id= id;
              if ( !context.part ) context.part= {};
              context.part[id]= part;
              if ( wrap_fce )
                wrap_fce(this,part);
            }
          }
        }
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  include2
// funkce načte do Ezer.code blok s options.include=onclick
// po skončeném načtení a spojení provede
//  1. subBlock na kořen
//  2. start na kořen (volá se ve start_code)
//  3. map+select
//  4. zřetězené procedury onstart
//  5. změní options.include=loaded
//  6. případnou metodu this[method]
  include2 (method) {
    // zjisti jméno modulu podle formátu zápisu include:onload[ ',' app ':' file]
    var format= this.options.include.split(',');
    this.parm= {own:true, app:'', name:''};
    if ( format.length==1 ) {
      this.parm.name= this.id;
      for (var o= this;o.owner.owner;o= o.owner)  {
        this.parm.name= o.owner.id+(this.parm.name?'.'+this.parm.name:'');
      }
    }
    else {
      this.parm.name= format[1];
      this.parm.own= false;
    }
    this.parm.app= this.parm.name.split('.')[0];
    this.parm.method= method;
//                                                 Ezer.trace('L','including '+this.parm.name+' then '+method);
    this.ask({cmd:'load_code2',file:this.parm.app+'/'+this.parm.name,
      block:this.self(),i:3},'include2_');
  }
  include2_ (y) {
//                                                 Ezer.trace('L','queued '+y.file);
    Ezer.app.calls_queue(this,'include3',[y]);
  }
  include3 (y) {
    // jde o vlastní rozšíření nebo cizí?
    var name, sender_name= this.id, o;
    for (o= this;o.owner;o= o.owner) {
      sender_name= o.owner.id+(sender_name?'.'+sender_name:'');
    }
    name= this.parm.own ? this.parm.name : sender_name;
    var file= this.parm.own ? name : this.parm.name;
    var app= this.parm.app;
    if ( y.error )
      Ezer.error(y.error,'C');
    // rozšíření Ezer.code v místě definovaném  'name' o y.app, pokud nebyla chyba
//                                                 Ezer.trace('L','loaded2 '+y.file+' '+y.msg);
    var i, desc= null;
    var ids= name.split('.');
    for (i= ids[0]=='$'?1:0, desc= Ezer.code.$; i<ids.length; i++) {
      // nalezení desc rozšiřovaného bloku
      Ezer.assert(desc.part[ids[i]],name+' je chybné jméno v include');
      desc= desc.part[ids[i]];
    }
    if ( desc ) {
      // přidání popisu nových částí
      if ( desc.options.include ) {
//         var pos= this.app_file();
        var pos= {file:y.app._file,app:y.app._app};
        desc._file= pos.file; //this.parm.own ? name : this.parm.name;
        desc._app= pos.app; // this.parm.app;
        if ( desc.part ) {
//           $each(desc.part,function(p,pid) {
          for (let pid in desc.part) { let p= desc.part[pid];
            p._file= pos.file;
            p._app= pos.app;
          }
//           $each(y.app.part,function(p,pid) {
          for (let pid in y.app.part) { let p= y.app.part[pid];
            p._file= file;
            p._app= app;
            desc.part[pid]= p;
          }
        }
        else
          desc.part= y.app.part;
        Object.assign(desc.options,y.app.options);
      }
      // rozšíření Ezer.run
      if ( y.app.library ) {
        this._library= true;                // poznamenej do Ezer_run info o knihovním kořenu
      }
      this.subBlocks(desc,this.DOM,null,'include');
      desc.options.include= 'loaded';
      this.options.include= 'loaded';
//                                                   Ezer.trace('L','included '+y.file);
      Ezer.app.start_code(this);
//                                                   Ezer.trace('L','started '+y.file);
      if ( this.parm.method )
        this[this.parm.method]();
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  app_file
// zjistí {app:app,file:file,root:root} identifikaci zdrojového textu
  app_file () {
    var pos= {app:'',file:'',root:null};
    for (var o= this; o; o= o.owner) {
      if ( o.desc ) {
        if ( (pos.file= o.desc._file) ) {
          pos.app= o.desc._app||'';
          pos.root= o;
          break;
        }
      }
    }
    return pos;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  ask
  // ask(x,then): dotaz na server se jménem funkce po dokončení
  ask (x,then) {
    x.root= Ezer.root;                  // název/složka aplikace
    x.app_root= Ezer.app_root;          // {root].inc je ve složce aplikace
    x.session= Ezer.options.session;    // způsob práce se SESSION
    Ezer.ajax({data:x,
      success: function(y) {
        Ezer.App._ajax(-1);
        if ( !y  )
          Ezer.error('ASK: syntaktická chyba v PHP na serveru:'+y,'C');
        else if ( y.error )
          Ezer.error(y.error,'C');
        else if ( y.cmd=='load_code2' && !y.app )
          Ezer.error('LOAD: server vrátil prázdný kód pro '+this.parm.name,'C');
        else {
          if ( y.trace ) Ezer.trace('u',y.trace);
          this[then].bind(this)(y);
        }
      }.bind(this),
      error: function(xhr) {
        Ezer.error('SERVER failure (2)','C');
      }
    });
    Ezer.App._ajax(1);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  runScript
//fm: Block.runScript (ezercsript)
//                                                                              EXPERIMENTÁLNÍ
// kompilace Ezerscriptu zadaného řetězcem a jeho zahájení v kontextu this.
// Metoda je určena především pro ladění programu z trasovacího okna,
// pokud je voláno z programu, vrací hodnotu 1 - nečeká na ukončení ezescriptu.
  runScript (script) {
    var s= this.app_file();     // zjistí {app:app,file:file,root:root}
    var self= '';
    for (var o= this; o.owner; o= o.owner) {
      if ( o._library )
        break;
      if ( o.type!='var' )
        self= o._id+(self ? '.'+self : '');
    }
    self= self ? (o._library ? '#.' : '$.')+self : '$';
//                                                 Ezer.trace('*','self='+self);
    var x= {cmd:'dbg_compile',context:{self:self,app:s.app,file:s.file},script:script};
    this.ask(x,'runScript_');
    return 1;
  }
  runScript_ (y) {
    var val= '';
//                                                         Ezer.debug(y.ret);
    if ( typeof(y)=='object' ) {
      if ( y.ret.code ) {
        var self= this.type=='var' && this.value ? this.value : this;
        var v= new Eval(y.ret.code,self,[],'dbg');
        val= v.simple ? v.value : '';
      }
      if ( y.ret.err ) {
        Ezer.fce.warning(y.ret.err);
      }
      if ( y.ret.trace ) {
        Ezer.trace('C',y.ret.trace);
      }
    }
    else {
      Ezer.fce.warning(y);
    }
    return val;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  start
//f: Block.start (code,oneval)
//      interní metoda - projde celou strukturu po jejím úplném (znovu)zavedení
//      zřetězí všechny příspěvky start_code podle jejich level a na závěr je Ezer.App
//      provede v pořadí: (maps),select,onstart
//a: codes - kódy nadřazených bloků
//   oneval - nejbližší nadřazený blok s onready nebo onbusy
//s: system
  start (codes,oneval) {
    if ( this.part) {
      // zajištění šíření událostí pro onready a onbusy
      this.oneval= oneval;
      if ( this.part.onready||this.part.onbusy ) {
        this.evals= 0;
        oneval= this;
      }
      // řetězení onstart
      if ( this.part && this.part.onstart ) {
//         codes.onstart.extend([{o:'d',i:this.self()}]).extend(this.part['onstart'].code).extend([{o:'z',i:0}]);
        codes.onstart.push({o:'d',i:this.self()},...this.part.onstart.code,{o:'z',i:0});
      }
      // start podbloků
      for(var i in this.part) {
        if ( this.part[i].start && typeof this.part[i].start=='function' ) {
          this.part[i].start(codes,oneval);
      }
    }
    }
    if ( this.start_code ) {
//       codes[this.start_code.level].extend(this.start_code.code).extend([{o:'z',i:0}]);
      codes[this.start_code.level].push(...this.start_code.code,{o:'z',i:0});
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  dragBlock
//   dragBlock (on,root_too) {
//     Ezer.design= on;
//     if ( root_too && this._dragThis )
//       this._dragThis(on);
//     if ( this.part)
//       $each(this.part,function(desc,id) {
//         if ( desc.type=='var' && desc.value && desc.value.type=='form' )
//           desc.value.dragBlock(on,true);
//         else
//           desc.dragBlock(on,false);
//         if ( desc._dragThis ) desc._dragThis(on);
//       });
//   }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  helpBlock
// zahájení a ukončení help modu tzn. zviditelňování programátorských informací
//   helpBlock (on,root_too) {
//     Ezer.help_mode= on;
//     if ( root_too && this._helpThis )
//       this._helpThis(on);
//     if ( this.part)
//       $each(this.part,function(desc,id) {
//         if ( desc.type=='var' && desc.value && desc.value.type=='form' )
//           desc.value.helpBlock(on,true);
//         else
//           desc.helpBlock(on,false);
//         if ( desc._helpThis ) desc._helpThis(on);
//       });
//   }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  fire
// provede obsluhu přerušení
// el obsahuje kontext přerušení
//   pokud je el.control=true pokusíme se zobrazit zdrojový kód místo spuštění obsluhy
// vrací
//   false   - pokud je obsluha fire asynchronní fce (došlo k volání serveru nebo jinému přerušení)
//   true    - pokud obsluha neexistuje
//   num|str - hodnota volané funkce
// onchanged se dědí z položky do jejího formuláře, pokud její formát neobsahuje 'T'
// fire('onchanged'): pokud procedura onchanged má právě jeden parametr je v něm předán 
//                    element, který fire způsobil
  fire (event_name,args,el) {
    // trasování události ovlivněné fcí set_trace
    function trace_event (event_type,id,event_name,fce) {
      // nejprve vyřešíme selektivní trasování
      if ( typeof(Ezer.is_trace.e)=='object' ) {  // v mootools je [x] objekt
        if ( !Ezer.is_trace.e.some(function(x){
          var ok= false;
          var xs= x.split('.');
          if ( xs.length==1 )
            ok= x==event_name;
          else if ( xs.length==2 )
            ok= (xs[0]==id||xs[0]=='*') && (xs[1]==event_name||xs[1]=='*');
          return ok;
        }) )
          return;
      }
      if ( Ezer.to_trace ) {
        let from= '';
        if ( Ezer.is_trace['*'] ) {
          // pokud je trasováno *e zobraz i odkud je v ezer3.js událost spuštěna
          // UGLY a jen pro FireFox ... ale jinak to asi nejde
          let x= new Error().stack.split("\n"), re= /@.*\.js|:\d*$/g;
          from+= ' from '+x[2].replace(re,'');
          from+= ' from '+x[3].replace(re,'');
        }
        Ezer.trace('e',(fce? 'EVENT:' : 'event ')+event_type+'.'+id+'.'+event_name
          +(fce ? ' in '+Ezer.App.block_info(fce) : '')+from
        ,fce);
      }
    }

    args= args||[];
    var fce= null, res= true, v;
    if ( Ezer.to_trace && Ezer.is_trace['*'] && Ezer.is_trace['e'] ) {
      trace_event(this.type,this.id,event_name);
    }
    if ( this.part ) {
      if ( (fce= this.part[event_name]) ) {
          trace_event(this.type,this.id,event_name,fce);
          v= new Eval([{o:'c',i:fce.desc._init||event_name,a:args.length}],
            fce.context||this,args,event_name,false,false,fce);
          res= v.simple ? v.value : false;
//         }
      }
    }
    if ( this instanceof Elem ) {
      var form= this.owner instanceof ListRow ? this.owner.owner.owner : this.owner;
      if ( event_name=='onchanged' || !form._changed && event_name=='onchange' ) {
        // některá přerušení se z elementu přenášejí do formuláře: elem.onchange => form.onchanged
        if ( !this._fc('T') ) {
          form._changed= true;
          if ( form.part && (fce= form.part.onchanged) ) {
            var narg= fce.desc.npar==1 ? 1 : 0;
            args= narg ? [this] : [];    
            trace_event('form',form.id,'onchanged',fce);
            //Ezer.trace('e','EVENT:form.'+form.id+'.onchanged in '+Ezer.App.block_info(fce),fce);
            v= new Eval([{o:'c',i:fce.desc._init||'onchanged',a:narg}],
              fce.context||form,args,'onchanged',false,false,fce);
            res= res && (v.simple ? v.value : false);
          }
        }
      }
    }
    return res;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  callProc
// provedení procedury, ex-li
  callProc (id,args) {
    var fce= null;
    if ( this.part ) {
      if ( (fce= this.part[id]) ) {
        new Eval(fce.code,fce.context||this,args||[],id,false,false,fce,fce.desc.nvar);
      }
    }
    return fce;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  findProc
// nalezení procedury
  findProc (id) {
    var fce= null, obj;
    if ( this.part && (obj= this.part[id]) ) {
      fce= function() {
        var EzerEval= new Eval(obj.code,this,[],id,false,false,obj,obj.desc.nvar);
        return EzerEval.value;
      }.bind(this);
    }
    return fce;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  findProcArg
// nalezení procedury s 1 argumentem
  findProcArg (id) {
    var fce= null, obj;
    if ( this.part && (obj= this.part[id]) ) {
      fce= function(arg) {
        var EzerEval= new Eval(obj.code,this,[arg],id,false,false,obj,obj.desc.nvar);
        return EzerEval.value;
      }.bind(this);
    }
    return fce;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _f
// pokud je v atributu 'format' obsažen kod, vrátí jeho pozici, pokud není vrátí -1
// část formátu za ':' ignoruje
// pokud kod==':' vrací část za ':' nebo prázdný string
  _f (kod) {
    var f= this.options.format, i= -1;
    if ( f ) {
      f= f.split(':');
      i= kod==':' ? f[1]||'' : f[0].indexOf(kod);
    }
    return i;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _fc
// pokud je v atributu 'format' obsažen kod, vrátí true jinak false; část formátu za ':' ignoruje
  _fc (kod) {
    var f= this.options.format, ok= false;
    if ( f ) {
      f= f.split(':');
      ok= f[0].indexOf(kod)>=0;
    }
    return ok;
  }
// ======================================================================================> Block DOM
// ------------------------------------------------------------------------------------- DOM destroy
//f: Block-DOM.DOM_destroy ()
//      zruší DOM-elementů vnořených bloků
  DOM_destroy () {
    if ( this.DOM_Block )
      this.DOM_Block.empty();
    else
      for (var o in this.part) {
        if ( this.part[o].DOM_destroy ) {
          this.part[o].DOM_destroy();
        }
      }
  }
// ------------------------------------------------------------------------------------ DOM enabled
//f: Block-DOM.DOM_enabled (on)
//      změní vzhled na enabled/disabled podle parametru nebo this.options.enabled
  DOM_enabled (on) {
    if ( this.DOM_Block ) {
      if (on!==false && this.options.enabled) {
        if (this.DOM_Input)
          this.DOM_Input.disabled= false;
      }
      else {
        if (this.DOM_Input)
          this.DOM_Input.disabled= true;
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_optStyle
// doplní případný styl, css-třídu a title
// předpony title: ^ umístit nad, - umístit napravo, jinak nalevo
  DOM_optStyle (dom,title_as_label,ignore_right) {
    // atribut style definuje styly pro parametr
    if ( this.options.style ) {
      const oss= this.options.style.split(';');
      for (var io= 0; io < oss.length; io++ ) {
        var os= oss[io].split(':');
        Ezer.assert(os[0],'prázdný název stylu',this);
        jQuery(dom).css(os[0],os[1]);
      }
    }
    // atribut css definuje jméno css třídy pro this.DOM_Block
    if ( this.options.css ) {
      jQuery(this.DOM_Block).addClass(this.options.css);
    }
    if ( title_as_label ) {
      // případný atribut title jako label
      const label= title_as_label[0]=='^' ? title_as_label.substr(1) : (
                   title_as_label[0]=='-' ? title_as_label.substr(1) : title_as_label );
      const up= title_as_label[0]=='^';
      const up_left= ignore_right || !this._fc('r');
      const right= title_as_label[0]=='-';
      this.DOM_Label= jQuery(`<div class="Label3">${label}</div>`)
        .css(up ? (up_left ? {top:-14,left:2}       : {top:-14,right:0})
                : (right   ? {top:3,left:this._w+3} : {top:3,right:this._w+2}));
      if ( right )
        this.DOM_Block.append(this.DOM_Label);
      else
        this.DOM_Block.prepend(this.DOM_Label);
    }
    else if (this.DOM_Input ) {
      // nepovinná hodnota title
      if ( this.title )
        jQuery(this.DOM_Input).attr('title',this.title);
      if ( this._fc('h') )
        jQuery(this.DOM_Input).prop('type','hidden');
    }
    if (this.DOM_Block ) {
      if ( this._fc('n') )
        DOM_display(this.DOM_Block,0);
    }
  }
// ------------------------------------------------------------------------------------ DOM_owner
// nalezne takového vlastníka, který má definované zobrazení
  DOM_owner () {
    var o= null;
    for (o= this; o.owner; o= o.owner)  {
      if ( o.DOM_Block ) break;
    }
    return o;
  }
// ------------------------------------------------------------------------------ DOM set_properties
// změní styly DOM_Block podle parametru, pokud je prop.smooth=1 použije transition z mootools
// pro šířku a výšku lze pro místo hodnoty dát * označující rozumné maximum
// pseudo-vlastnosti down resp. aside posunou element proti originální poloze dolů resp. do strany
// pseudo-vlastnosti smooth a onproperty využijí mootools
  DOM_set_properties (prop) {
    var div= this.DOM_Block;
    //var smooth= prop.smooth;
    var style= {};
    if ( div ) {
      if ( prop.left!==undefined )                                  // left
        style.left= Number(prop.left);
      else if ( prop.aside!==undefined ) {                          // nebo aside
        style.left= this._l + Number(prop.aside);
      }
      if ( prop.top!==undefined )                                   // top
        style.top= Number(prop.top);
      else if ( prop.down!==undefined ) {                           // nebo down
        style.top= this._t + Number(prop.down);
      }
      if ( prop.width!==undefined ) {                               // width
        style.width= Number(prop.width);
      }
      if ( prop.widen!==undefined ) {                               // nebo widen
        style.width= this._w + Number(prop.widen);
      }
      if ( prop.height!==undefined ) {                              // height
        style.height= Number(prop.height);
      }
      else if ( prop.stretch!==undefined ) {                       // nebo stretch
        style.height= this._h + Number(prop.stretch);
      }
      // vlastní změna
      jQuery(div).css(style);
    }
  }
}

// ======================================================================================> BlockMain
//c: BlockMain ()
//      $ kořen aplikace
//t: Block
//s: Block
class BlockMain extends Block {
//   Extends: Ezer.Block,
//   Extends: Ezer.BlockMain
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//   initialize: function(desc) {
  constructor (desc) {
    super(null,desc,null,'.main.');
    this.owner= null;
    this.type= '.main.';
    this.id= '$';
    this.desc= desc;
    this._file= '$';
    this.part= {};
    for (var id in desc.part) {
      var idesc= desc.part[id], part= null;
      switch (idesc.type) {
        case 'menu.main': part= new MenuMain(this,idesc,null,id); break;
        case 'panel.main':part= new PanelMain(this,idesc,null,id); break;
        case 'panel.popup':part= new PanelPopup(this,idesc,null,id); break;
        case 'table':     part= new Table(this,idesc,null,id);    break;
        case 'map':       part= new EzerMap(this,idesc,null,id);      break;
        // s potenciální vizualizací
        case 'var':       part= new Var(this,idesc,null,id);      break;
        // objekt bez vizualizace (ale vložený jako part)
        case 'view':      part= new View(this,idesc,null,id);     break;
        case 'group':     part= new Group(this,idesc,null,id);    break;
        case 'proc':      part= new Proc(this,desc,this);         break;
        // přeskakované (informace dostupné přes Ezer.code)
        case 'form':      break;
        default:
          Ezer.error('neimplementovaný hlavní blok '+idesc.type+' '+id,'C');
      }
      if ( part ) {
        // nově vložená část
        part.id= id;
        this.part[id]= part;
      }
    }
    return this;
  }
}

// ==========================================================================================> Group
//c: Group ([options])
//      obecný blok bez vizualizace, může obsahovat volně vnořené bloky
//t: Block
//s: Block
class Group extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  constructor (owner,desc,DOM,id) {
    super(owner,desc,DOM,id);
    // vložení podčástí
    this.subBlocks(desc,this.DOM_Block);
  }
}

// ===========================================================================================> Menu
//c: Menu ([options])
//      varianty implementace zobrazení Menu
//t: Block
//s: Block
class Menu extends Block {
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    if ( this.DOM_add1 ) this.DOM_add1();               // napřed rozvrhne plochu na menu a obsah
    this.subBlocks(desc,this.DOM);                      // vytvoří (příp. vloží) části
    if ( this.DOM_add2 ) this.DOM_add2();               // specificky doplní menu
  }
  start (codes,oneval) {
    super.start(codes,oneval);
  }
}

// ======================================================================================> Menu Main
//c: MenuMain ([options])
//      hlavní menu aplikace, obsahuje Tabs+
//t: Menu,Block
//s: Block
class MenuMain extends Menu {
//oi: MenuMain.active - vnořený Tabs, který má být aktivní hned po startu,
//      hvězdička aktivuje Tabs podle poslední volby uživatele
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  start
//f: MenuMain.start (code,oneval)
//   start: function(codes,oneval) {
//                                                 Ezer.trace('L','starting '+this.type);
//     this.parent(codes,oneval);
//     this.excite();                           // je spuštěno z Ezer.app po načtení map
//                                                 Ezer.trace('L','started  '+this.type);
//   },
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  excite
//f: MenuMain.excite ()
//      zajistí prvotní zobrazení menu s vyznačením aktivního
//      podle options.start nebo active, nebo prvního.
//      excituje příslušné submenu - Tabs
//   excited: false,
  excite () {
    if ( !this.excited ) {
      this.excited= true;
      // najdi aktivní záložku
      var tabs= null, id= null;
      if ( Ezer.options && Ezer.options.start /*&& Ezer.excited<1*/ ) { // 160509 upřednostnit start
        var ids= Ezer.options.start.split('.');
        id= ids[0];
        tabs= this._part(id);
        Ezer.excited= 1;
      }
      else if ( this.options.active ) {
        if ( this.options.active=='*' ) {
          if ( Ezer.sys.user.options && Ezer.sys.user.options.context &&
               Ezer.sys.user.options.context[Ezer.root]) {
            id= Ezer.sys.user.options.context[Ezer.root][0];
            tabs= this._part(id);
          }
        }
        else {
          const ids= this.options.active.split('.');
          id= ids[ids.length-1];
          tabs= this._part(id);
        }
      }
      if ( !tabs ) {
        tabs= this._part('tabs',1);
      }
//                                         Ezer.trace('L','1. exciting '+this.type+' '+this.id+' at '+tabs.id+' ('+Ezer.app.ajax+')');
      // zobraz aktivní záložku
      tabs._focus();
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ enable
//fm: MenuMain.enable (enabled)
//      parametr enabled=0 znecitliví hlavní menu a submenu, enabled=1 je opět povolí
  enable (enabled) {
    this.enabled= enabled=="0" ? 0 : enabled;
    if ( this.enabled )
      jQuery('#menu,#submenu').removeClass('disabled3');
    else
      jQuery('#menu,#submenu').addClass('disabled3');
    return true;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM initialize
  DOM_initialize () {
    this.DOM_SelectedTabs= null;
  }
// ------------------------------------------------------------------------------------ DOM add1
//f: MenuMain-DOM.DOM_add1 ()
//      zobrazí hlavní menu
  DOM_add1 () {
    this.activeTabs= null;
    this.DOM_Block= jQuery('#menu');
  }
// ------------------------------------------------------------------------------------ DOM destroy
//f: MenuMain-DOM.DOM_destroy ()
//      zruší zobrazení hlavního menu
  DOM_destroy () {
  }
// ------------------------------------------------------------------------------------ DOM setSelectedTabs
//f: MenuMain-DOM.DOM_setSelectedTabs (id)
//      zobrazí dané Tabs jako vybrané
  DOM_setSelectedTabs (tabs_id) {
  }
}
// ======================================================================================> Menu Left
//c: MenuLeft ([options])
//      levostranné menu, obsahuje MenuGroup, je vnořeno do Tabs
//t: Menu,Block
//s: Block
class MenuLeft extends Menu {
//oi: MenuLeft.active - vnořený Item, který má být aktivní hned po startu, hvězdička aktivuje první item
//-
//i: MenuLeft.onresize - volá se při změně šíře minimalizovatelného menu, parametr udává šíři v px
//-
//os: MenuLeft.format - vzhled
//  ; 'f' : 'foldable' umožní skrývat menu a rozšiřovat pravý panel, lze užít metodu click,
//                     při změně šířky je volána metoda menu.onresize(aktuální šířka v px);
//                     f- zobrazí menu jako minimalizované
//   Extends: Ezer.Menu,
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
   super.initialize();
   this.enabled= true;                        // akce myší jsou povoleny
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  start
//f: MenuLeft.start (code,oneval)
  start (codes,oneval) {
//     this.parent(codes,oneval);
    super.start(codes,oneval);
    this.owner.menuleft= this;
    this.DOM_start();
  }
// ------------------------------------------------------------------------------------ attach_code
//fm: MenuLeft.attach_code (o)
  attach_code (o) {
    // odstraň všechny mimo procedur a proměnných
    for (var i in this.part) {
      var p= this.part[i];
      if ( p instanceof Block && p.type!='proc' ) {
        p.delete();
      }
    }
    this.DOM_destroy();                                   // vymaž viditelné prvky
    if ( this.DOM_re1 ) this.DOM_re1();                   //
    this.subBlocks(o,this.DOM,null,'rewrite');            // true => doplnění a přepis
    this.DOM_excite();
//     if ( this.DOM_re2 ) this.DOM_re2();                   // specificky doplní menu
    return 1;
  }
// ------------------------------------------------------------------------------------ click
//fm: MenuLeft.click ([stav=0,quiet=0])
//      změní stav minimalizovatelného menu (format:'f'), u jiného typu se ignoruje;
//      je-li zadán stav=2 minimalizuje menu, je-li stav=1 zobrazí menu v plné šíři;
//      pro quiet=1 nevolá onresize
  click (stav,quiet) {
    this.excited= true;
    this.DOM_click(stav,quiet);
    return 1;
  }
// ------------------------------------------------------------------------------------ enable
//fm: MenuLeft.enable (enabled)
//      parametr enabled=0 znecitliví levé menu, enabled=1 je opět povolí
  enable (enabled) {
    this.enabled= enabled=="0" ? 0 : enabled;
    if ( this.enabled )
      this.DOM_Block.removeClass('disabled3');
    else
      this.DOM_Block.addClass('disabled3');
    return true;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  excite
//f: MenuLeft.excite ()
//      zajistí prvotní zobrazení levého menu, vyznačí item s active, nebo první item
//   excited: false, !!!!!!!!!!!!!
  excite () {
    if ( !this.excited ) {
      this.excited= true;
      var ctx= [], obj= null, id;
      if ( Ezer.options && Ezer.options.start && Ezer.excited<3 ) {
        Ezer.excited= 3;
        var ids= Ezer.options.start.split('.');
        if ( ids.length==5 ) {
          var ok= Ezer.run_name(ids[2]+'.'+ids[3]+'.'+ids[4],this,ctx);
          if ( ok )
            obj= ctx[0];
        }
      }
      if ( !obj && (id= this.options.active) ) {
        if ( id=='*' ) {
          // hvězdička aktivuje první item první skupiny
          var gr= this._part('menu.group',1);
          obj= gr ? gr._part('item',1) : 0;
        }
        else {
          Ezer.assert(1==Ezer.run_name(this.options.active,this,ctx),
            'LOAD: atribut active neoznačuje item menu');
          obj= ctx[0];
        }
      }
//                                                 Ezer.trace('L','3. exciting '+this.type+' '+obj.id);
      this.DOM_excite(obj);
      if (obj) {
        obj.click(null);
      }
    }
    return 1;
  }
// ===================================================================================> MenuLeft DOM
// levostranné menu, obsahuje MenuGroup+ je vnořeno do Panel
// awesome: 0,       // 1|2 pro minimalizovatelné menu (1=plné,2=minimalizované), jinak 0
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM initialize
  DOM_initialize () {
    this.awesome= 0;       // 1|2 pro minimalizovatelné menu (1=plné,2=minimalizované), jinak 0
  }
// ------------------------------------------------------------------------------------ DOM add1
// první zobrazení obalu levého menu
  DOM_add1 () {
    Ezer.assert(this.owner.type=='panel.right' || this.owner.type=='panel.popup',
      "menu typu 'left' může být pouze v panelu typu 'right' nebo 'popup'");
    // detection of initial folding
    // 1|2 for minimized menu (1=full,2=folded), otherwise 0
    this.awesome= this._f('f')<0 ? 0 : (this.options.format.substr(this._f('f')+1,1)=='-' ? 2 : 1);
    // looking for right owner
    this.DOM_Block= jQuery('<div>')
      .prependTo(this.owner.DOM_Block.parent())
      .data('ezer',this)
      .addClass(this.awesome==2 ? 'MenuLeft3 MenuLeftFolded3' : 'MenuLeft3');
//     rozšíří obsahující panel o 210
//    var panel_dom= jQuery(this.owner.DOM);
//    panel_dom.css('width',panel_dom.width()+210);
    if ( this.awesome ) {
      // remember menu status
      this.owner._folded= this.awesome==2;
      // add folding icon for format:'f' (foldable + font icons)
      this.fold= jQuery('<i></i>')
        .addClass(this.awesome==2 ? 'fa fa-caret-square-o-right' : 'fa fa-caret-square-o-left')
        .appendTo(this.DOM_Block)
        .click( e => {
          this.DOM_click(this.awesome==2 ? 1 : 2);
        });
    }
  }
// ------------------------------------------------------------------------------------ DOM re1
//f: MenuLeft-DOM.DOM_re1 ()
//      další zobrazení obalu levého menu
  DOM_re1 () {
    Ezer.assert(this.owner.type=='panel.right' || this.owner.type=='panel.popup',
      "menu typu 'left' může být pouze v panelu typu 'right' nebo 'popup'");
  }
// ------------------------------------------------------------------------------------ DOM start
//f: MenuLeft-DOM.DOM_start ()
//      oživení levého menu po naplnění všemi Group a Item
  DOM_start () {
    jQuery(this.DOM_Block).find('ul').slideUp(0);
  }
// ------------------------------------------------------------------------------------ DOM click
//f: MenuLeft-DOM.DOM_click ([stav=0,quiet=0])
//   změna stavu minimalizovatelného menu, pro stav=1 na plné, 2 na stažené, 0 na opak
//   pro quiet=1 nevolá onresize
  DOM_click (stav,quiet) {
    if ( this.awesome && (!stav || stav!=this.awesome) ) {
      var panel= this.owner; // panel_w= jQuery(panel.DOM_Block).width();
      panel._folded= !panel._folded;
      this.awesome= this.awesome==2 ? 1 : 2;
      this.DOM_Block.toggleClass('MenuLeftFolded3');
      this.fold.toggleClass('fa-caret-square-o-left').toggleClass('fa-caret-square-o-right');
      // fire events of width change
      if ( !quiet ) {
        this.fire('onresize',[this.awesome==2 ? 30 : 210]);
      }
    }
  }
// ------------------------------------------------------------------------------------ DOM excite
//f: MenuLeft-DOM.DOM_excite ()
//      prvotní zobrazení levého menu
  DOM_excite (active) {
    // nalezení aktivního
    if ( active && active.type=='item' ) {
      active._focus();
    }
    else {
      jQuery(this.DOM_Block).find('ul').first().slideDown(0);
    }
  }
}

// =====================================================================================> Menu Group
//c: MenuGroup ([options])
//      obsahuje Item+ je vnořeno do MenuLeft
//t: Menu,Block
//s: Block
class MenuGroup extends Menu {
// ======================================================================================> MenuGroup
// Ezer.MenuGroup.implement({
//   Implements: [Ezer.Help],
//   _enabled: 1,
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM initialize
  DOM_initialize () {
    this._enabled= 1;
  }
// ------------------------------------------------------------------------------------ DOM add
  DOM_add1 () {
    Ezer.assert(this.owner.type=='menu.left','chybné menu - group mimo accordion');
    var title= this.options.title||this.id;
    this.DOM_Block= jQuery(`<div class="MenuGroup3"><a>${title}</a><ul></ul></div>`)
      .appendTo(this.owner.DOM_Block)
      .data('ezer',this);
    this.DOM_Block.find('a')
      .click(e => { this.DOM_Block.find('ul').slideToggle(); return false; });
  }
// MenuGroup.prototype.DOM_add2= null;
// ------------------------------------------------------------------------------------ _fold
  _fold () {
    this.DOM_Block.find('ul').slideUp();
  }
// ------------------------------------------------------------------------------------ _unfold
  _unfold () {
    this.DOM_Block.find('ul').slideDown();
  }
// ------------------------------------------------------------------------------------ DOM enabled
//f: MenuGroup-DOM.DOM_enabled (on)
//      změní vzhled na enabled/disabled podle parametru nebo this.options.enabled
  DOM_enabled (on) {
    this._enabled= on ? 1 : 0;
    if ( !this._enabled ) {
      this._fold();
    }
  }
}

// ===================================================================================> Menu Context
//c: MenuContext ([options])
//      obsahuje Item+ je vnořeno do libovolného bloku, vyvolává se levým klikem nebo dvojklikem
//t: Menu,Block
//s: Block
class MenuContext extends Menu {
// ------------------------------------------------------------------------------------ enable
//fm: MenuContext.enable (enabled)
//      parametr enabled=0 potlačí vyvolání kontextového menu, enabled=1 je opět povolí
  enable (enabled) {
    if ( enabled )
      this.ContextMenu.enable();
    else
      this.ContextMenu.disable();
    return true;
  }
  DOM_add1 () {
    this.DOM= jQuery('<ul>')
      .appendTo(jQuery('body'))
      .data('ezer',this)
      .addClass('ContextMenu3');
  }
  DOM_add2 () {
    var owner= this.owner.DOM_Block || this.owner.value.DOM_Block;
    if ( this.options.par && this.options.par.trigger=='click' ) {
      this.options.trigger='click';
    }
    if ( this._f('m')>=0 ) { OBSOLETE("ContextMenu - format:'m'");
      // zvýraznit oblast kontextového menu pomocí masky - musí existovat element s id='mask'
    }
    if ( this.options.join ) {
      var name= this.options.join;
      var ctx= Ezer.code_name(name,null,this);
      Ezer.assert(ctx && ctx[0],name+' je neznámé jméno pro označení contextmenu');
      if ( ctx[0].DOM_Block ) {
        this.options.focus= ctx[0].DOM_Block;
      }
    }
    else {
      this.options.focus= owner;
    }
    this.options.persistent= true;
    jQuery(owner).contextPopup(this.DOM);
  }
}

// ===========================================================================================> Item
//c: Item ([options])
//      Item je vnořitelný do Menu
//t: Block
//s: Block
//i: Item.onclick - item byl vybrán
class Item extends Block {
//oo: Item.par - parametr itemu
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    if ( this.type=='item.clipboard' ) {
      OBSOLETE('item.clipboard',"function clipboard"); this.type= 'item';
    }
    this.DOM_add1();
    this.subBlocks(desc,DOM);
    this.DOM_add2();
  }
// ------------------------------------------------------------------------------------ click
//fm: Item.click ([only=0,quiet=0])
//      nastavení položky jakoby kliknuté vč. vyvolání onclick (pokud není quiet=1);
//      pokud je only tak zavře jiné skupiny
  click (only,quiet) {
    if ( only ) {
//       $each(this.owner.owner.part,function(group,id) {        // projdi skupiny
      for (let ig in this.owner.owner.part) {  // projdi skupiny
        let group= this.owner.owner.part[ig];
        if ( group.type=='menu.group' ) {
          group._fold();
        }
      }
    }
    this._show();               // zajisti zobrazení itemu
    if ( quiet )
      this._focus();            // jen zvýrazní
    else
      this._click();            // jinak taky a provede onclick
    return 1;
  }
// =======================================================================================> Item DOM
// ------------------------------------------------------------------------------------  DOM_add1
  DOM_add1 () {
  }
// ------------------------------------------------------------------------------------  DOM_add2
  DOM_add2 () {
    switch (this.owner.type) {

    case 'menu.group': {
      let href= make_url_menu([this.owner.owner.owner.owner.id,this.owner.owner.owner.id,
            this.owner.owner.id,this.owner.id,this.id]),
          title= ''+(this.options.title||this.id),
          text= title.replace(/\[fa-([^\]]+)\]/g,''),
          help= this.options.help ? ` title="${this.options.help}"` : '';
      title= title.replace(/\[fa-([^\]]+)\]/g,`<i class='fa fa-fw fa-$1' title='${text}'></i>`);
      this.DOM_Block= jQuery(`<li${help}>${title}</li>`)
        .addClass(this._fc('d') ? 'disabled3' : '')
        .appendTo(this.owner.DOM_Block.find('ul'))
        .click( e => {
          if ( !this.DOM_Block.hasClass('disabled3') && this.owner.owner.enabled ) {
            if ( this.owner.owner.owner.type!='panel.popup' )
              Ezer.pushState(href);
            this._click(e);
            Ezer.fce.touch('block',this,'click');     // informace do _touch na server
          }
          return false;
        });
      break;
    }
    case 'menu.context': {
      let title= this.options.title||this.id,
          del= title.match(/^[-=]/);
      title= del ? title.substr(1) : title;
      title= title.replace(/\[fa-([^\]]+)\]/g,"<i class='fa fa-fw fa-$1'></i>");
      this.DOM_Block= jQuery(`<li>${title}</li>`)
        .appendTo(this.owner.DOM)
        .data('ezer',this)
        .css({borderTop: del ? (del=='-' ? "1px solid #AAAAAA" : "3px double #AAAAAA") : ''})
        .click( el => {
            if ( el.shiftKey ) return dbg_onshiftclick(this);  /* context.item */
            if ( !jQuery(el.target).hasClass('disabled3') ) {
              Ezer.fce.touch('block',this,'click');       // informace do _touch na server
              this.fire('onclick',[],el);
            }
          }
        );
      if ( this._fc('d') ) {
        this.DOM_Block.addClass('disabled3');
      }
      break;
    }
    default:
      Ezer.error('chybné menu - item mimo group nebo context');
    }
  }
// ------------------------------------------------------------------------------------ DOM enabled
//f: Item-DOM.DOM_enabled (on)
//      změní vzhled na enabled/disabled podle parametru nebo this.options.enabled
  DOM_enabled (on) {
    if ( on )
      this.DOM_Block.removeClass('disabled3');
    else
      this.DOM_Block.addClass('disabled3');
  }
// ------------------------------------------------------------------------------------  _click
  _click (el) {
    this._focus();
    if ( this.findProc('onclick') )
      this.fire('onclick',[this],el);
    else if ( this.owner.findProc('onclick') )
      this.owner.fire('onclick',[this],el);
    else if ( this.owner.owner.findProc('onclick') )
      this.owner.owner.fire('onclick',[this],el);
    return this;
  }
// ------------------------------------------------------------------------------------  _focus
  _focus () {
    Ezer.assert(this.owner.type=='menu.group','_focus');
    var s= jQuery(this.owner.owner.DOM_Block).find('.selected3');
    if ( s ) s.removeClass('selected3');
    this.DOM_Block.addClass('selected3');
  }
// ------------------------------------------------------------------------------------  _show
  _show (el) {
    this.owner.owner.excite();
    this.owner._unfold();
    this._focus();
  }
}
// ===========================================================================================> Tabs
//c: Tabs ([options])
//      Tabs jsou záložky obsahující vzájemně se skrývající panely (lze i bez panelů - logoff)
//      má varianty podle elementu, ve kterém je obsažen
//      pokud je v Menu typu main  -- zobrazí se v základní ploše
//      pokud je v Panel           -- zobrazí se v daném panelu
//t: Block
//s: Block
class Tabs extends Block {
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    this.excited= false;
    this.DOM_add1();
    // vložení podčástí
    this.subBlocks(desc,this.DOM_Block,this.addTabDom);
    this.DOM_add2();
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
//os: Tabs.title - název záložky
    this.active= false;                        // this je aktivní (viz _show,_hide)
//oi: Tabs.active - vnořený panel, který má být aktivní hned po startu
    this.activePanel= null;                    // aktivní Panel v Tabs
  }
// ------------------------------------------------------------------------------------ focus
//fm: Tabs.focus ()
//      nastavení záložky jako zvolené
  focus () {
    this._focus();
    return true;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  excite
//f: Tabs.excite ()
//      zajistí prvotní zobrazení submenu, excituje panel s active, nebo první panel
  excite () {
    if ( !this.excited ) {
      this.excited= true;
      // zobraz aktivní podmenu
      this.DOM_excite();
      // pokud je definován atribut active a tab je aktivní
      var panel= null;
      if ( Ezer.options && Ezer.options.start && Ezer.excited<2 ) {
        var ids= Ezer.options.start.split('.');
        if ( ids.length>1 ) {
          var id= ids[1];
          panel= this._part(id);
          Ezer.excited= 2;
          Ezer.assert(panel instanceof Panel,'"'+ids[0]+'.'+id+'" v parametru menu neoznačuje panel');
        }
      }
      else if ( this.options.active /*&& this.active*/ ) {
        let path;
        if ( this.options.active=='*' ) {
          if ( Ezer.sys.user.options && Ezer.sys.user.options.context &&
            (path= Ezer.sys.user.options.context[Ezer.root]) ) {
            if ( this.id==path[0] && this.part && this.part[path[1]] ) {
              panel= this.part[path[1]];
            }
          }
        }
        else {
          var ctx= [];
          Ezer.assert(1==Ezer.run_name(this.options.active,this,ctx),
            'LOAD: atribut active neoznačuje panel tabs');
          panel= ctx[0];
        }
      }
      if ( !panel ) {
        panel= this._part('panel.plain|panel.right',1);
        if ( this instanceof Tabs && !panel )
          Ezer.error('v menu '+(this.options.title||this.id)+' není přístupné žádné podmenu');
      }
//                         Ezer.trace('L','2. exciting '+this.type+' '+this.id+' at '+panel.id+' ('+Ezer.app.ajax+')');
      Ezer.assert(panel,'problém při excite pro '+this.type+' '+this.id);
      panel.focus();
      panel.excite();                          // pro první zobrazení
    }
    return 1;
  }
// =======================================================================================> Tabs DOM
//c: Tabs-DOM ([options])
//      realizace vzhledu Tabs vnořených do Menu typu main
//s: Block-DOM
// Ezer.Tabs.implement({
//   Implements: [Ezer.Help],
//   _tabsDom: null,                       // ul-element pro submenu
// ------------------------------------------------------------------------------------ DOM add1
  DOM_add1 () {
    // lišta pro názvy panelů
    this.DOM_Block= jQuery('<ul>')
      .appendTo('#submenu')
      .hide();
    // položka v hlavním menu
    var title= this.options.title||this.id,
        href= make_url_menu([this.id]), // 'ezer://'+id;
        key= this.self_sys().sys, sub;
    // zvýraznění nadpisu, pokud právě k němu existuje _help - help pro tabs nelze vynutit
    sub= key && this.options._sys && Ezer.sys.ezer.help_keys
      && Ezer.sys.ezer.help_keys.split(',').includes(key)
      ? "<sub> ?</sub>" : '';
    title= title.replace(/\[fa-([^\]]+)\]/g,"<i class='fa fa-$1'></i>");
    this.DOM_li= jQuery(`<li class='Pasive'><a>${title}${sub}</a></li>`)
      .appendTo(this.owner.DOM_Block)
      .click( event => {
        if ( this.type=='tabs' ) {
          Ezer.pushState(href);
          Ezer.fce.touch('block',this,'click');     // informace do _touch a trail na server
          Ezer.fce.DOM.help_hide();
          this._focus();
        }
        else if ( this.type=='tabs.logoff' ) {
          jQuery('#submenu').empty();
          jQuery('#menu').empty();
          jQuery('#work').empty();
          Ezer.fce.logout();
        }
        return false;
      });
  }
// ------------------------------------------------------------------------------------ DOM add2
  DOM_add2 () {
  }
// --------------------------------------------------------------------------- fce pro panely v Tabs
// vytvoří záložku panelu a podle stavu helpu ji označí, pokud je help pro tuto záložku
// a přihlášeného uživatele vynucený, naplní panel.force_help
  _addPanel (panel) {
    var href= make_url_menu([panel.owner.id,panel.id]); 
    var title= panel.options.title||panel.id;
    var key= panel.owner.id+'.'+panel.id, sub;
    title= title.replace(/\[fa-([^\]]+)\]/g,"<i class='fa fa-$1'></i>");
    sub= key && Ezer.sys.ezer.help_keys.split(',').includes(key)
      ? "<sub> ?</sub>" : '';
    panel.DOM_li= jQuery(`<li class='Pasive'><a>${title}${sub}</a></li>`)
      .appendTo(this.DOM_Block)
      .click( el => {
        if ( el.shiftKey ) return dbg_onshiftclick(panel); /* panel */
        if ( !this.activePanel
          || (this.activePanel && !this.activePanel.is_fixed) ) {
          // pokud panel není blokován proti ztrátě focusu
          Ezer.pushState(href);
          Ezer.fce.DOM.help_hide();
          panel._focus();
        }
        return false;
      });
  }
// ----------------------------------------------------------------------------- _setActivePanel
// pro Menu typu main: zviditelni submenu
  _setActivePanel (panel_id) {
  }
// ------------------------------------------------------------------------------------ _hide
  _hide () {
    this.DOM_Block.hide();
    this.DOM_li.removeClass('Active').addClass('Pasive');
    if ( this.activePanel )
      this.activePanel._hide();
  }
// ------------------------------------------------------------------------------------ _show
  _show () {
    if ( this.options.include && this.options.include.substr(0,7)=='onclick' ) {
      this.include2('_show');
    }
    else {
      if ( this.owner.activeTabs )
        this.owner.activeTabs._hide();
      this.owner.activeTabs= this;
      // zobraz lištu záložek
      this.DOM_Block.show();
      this.DOM_li.addClass('Active').removeClass('Pasive');
      if ( this.activePanel )
        this.activePanel._show();
    }
    return this;
  }
// ------------------------------------------------------------------------------------ _ focus
  _focus () {
    if ( this.options.where ) {
      location.replace(this.options.where);
    }
    if ( this.options.include && this.options.include.substr(0,7)=='onclick' ) {
      this.include2('_focus');
    }
    else {
      this._show();
      this.excite();
    }
  }
// ------------------------------------------------------------------------------------ _ loaded
// po zavedení pomocí include:onclick
  loaded () {
  }
// ------------------------------------------------------------------------------------ addTabDom
  addTabDom (tabs,part) {
  }
// ------------------------------------------------------------------------------------ DOM excite
//f: Tabs-DOM.DOM_excite ()
//      vyznačení aktivní položky hlavního menu
  DOM_excite () {
  }
}
// ==========================================================================================> Panel
//c: Panel
//      Panel přímo vnořený do Tabs, reaguje na události Tabs (onfocus,onblur)
//t: Block,Panel
//s: Block
//i: Panel.onfirstfocus - panel se poprvé stal viditelný, v tomto případě nenastane onfocus
//i: Panel.onfocus - panel se stal viditelný
//i: Panel.onblur - panel přestal být viditelný
//i: Panel.onresize - volá se při změně rozměru okna, parametry dávají šířku a výšku (okna) v px
class Panel extends Block {
//   Extends: Ezer.Block,
//   virgin: true,                         // stav před prvním focusem
//os: Panel.include - zdrojový kód je v samostatném souboru
// ; onload  : kód bude zaveden při startu
// ; onclick : až při prvním kliknutí
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    this.DOM_add1();
    this.subBlocks(desc,DOM);
    this.DOM_add2();
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
    this.virgin= true;                         // stav před prvním focusem
  }
// ------------------------------------------------------------------------------------ focus
//fm: Panel.focus ([par])
//      bez parametru nastaví panel jako aktivní vč. vyvolání onfocus
//        (par=1 způsobí totéž ale bez onfocus).
//      Pokud je par='fix' resp. 'unfix' zablokuje resp. odblokuje panel proti ztrátě fokusu při
//         pokusu vybrat myší jiný panel v témže Tabs.
//      Pokud par='fixed' pak funkce vrátí takto stav jako 1 pro 'fix' resp. 0 pro 'unfix'.
//      Všechny funkce se týkají pouze panelů zanořených do bloku Tabs.
//a: par - 1|'fix'|'unfix'
//   is_fixed: 0,
  focus (par) {
    var value= 1;
    if ( this.owner.type=='tabs' ) {
      if ( par=='fix' )
        this.is_fixed= 1;
      else if ( par=='unfix' )
        this.is_fixed= 0;
      else if ( par=='fixed' )
        value= this.is_fixed;
      else {
        this.owner._focus();
        this._focus(par);
      }
    }
    return value;
  }
// ------------------------------------------------------------------------------------ popup
//fm: Panel.popup (l,t,noevent=0)
//      Ukáže panel.
  popup (l,t,noevent=0) {
    this._show(l,t,noevent);
    return 1;
  }
// ------------------------------------------------------------------------------------ hide
//fm: Panel.hide ([value])
  hide (value) {
    this._hide();
    return 1;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  excite
//f: Panel.excite ()
//      zajistí prvotní zobrazení panelu
//   excited: false,
  excite () {
    if ( !this.excited ) {
      this.excited= true;
    }
    return 1;
  }
// ======================================================================================> Panel DOM
// panel vnořený do Tabs případně obsahující MenuLeft
// chování overflow pro flex + height viz
// https://stackoverflow.com/questions/28636832/firefox-overflow-y-not-working-with-nested-flexbox
// ------------------------------------------------------------------- DOM add1
  DOM_add1 () {
    Ezer.assert(this.owner.DOM_Block,'panel '+this.id+' nelze vnořit do '+this.owner.id);
    this.DOM= this.DOM_Block= jQuery('<div>')
      .addClass('Panel3')
      .css({width:this._w,height:this._h})     //  min..,max.. je kvůli flex
      .data('Ezer',this)
      .appendTo(this.owner.DOM_Block)
      .hide();

  }
// ------------------------------------------------------------------- DOM add2
  DOM_add2 () {
    if ( this.options.css )
      jQuery(this.DOM_Block).addClass(this.options.css);
    if ( this.options.style ) {
      for (const style of this.options.style.split(';') ) {
        var os= style.split(':');
        Ezer.assert(os[0],'prázdný název stylu',this);
        jQuery(this.DOM_Block).css(os[0],os[1]);
      }
    }
  }
// ------------------------------------------------------------------- _onresize
  _onresize (w,h) {
    if ( this.part.onresize ) {
      if ( this.w != w || this.h != h ) {
        this.w= w;
        this.h= h;
        this.fire('onresize',[w,h]);
      }
    }
  }
// ------------------------------------------------------------------- _show
  _show (l,t,noevent) {
    this.DOM.show();
    if ( this.DOM_li ) this.DOM_li.addClass('Active').removeClass('Pasive');
    if ( l!==undefined ) this.DOM.css('left',l);
    if ( t!==undefined ) this.DOM.css('top',t);
    if ( !noevent && this.part ) {
      // rozhodneme, zda volat onfirstfocus nebo onfocus
      if ( this.virgin ) {
        this.virgin= false;
        Ezer.app.onfirstfocus(this);
        if ( this.part.onfirstfocus )
          this.fire('onfirstfocus',[]);
        else
          this.fire('onfocus',[]);
      }
      else
        this.fire('onfocus',[]);
    }
    return this;
  }
// ------------------------------------------------------------------- _hide
  _hide () {
    if ( this.DOM_li ) this.DOM_li.removeClass('Active').addClass('Pasive');
    this.fire('onblur',[]);
    this.DOM.hide();
    return this;
  }
// ------------------------------------------------------------------- _focus
  _focus (noevent) {
    if ( this.options.include && this.options.include.substr(0,7)=='onclick' ) {
      this.include2('_focus');
    }
    else {
      if ( this.owner.activePanel )
        this.owner.activePanel._hide();
      this.owner.activePanel= this;
      Ezer.panel= this;
      Ezer.fce.touch('block',this,'focus');   // informace do _touch na server
      Ezer.fce.touch('panel',this);           // informace do _touch na server
      this.excite();
      this._show();
    }
  }
}

// ======================================================================================> PanelMain
//c: PanelMain
//      Panel jako hlavní blok aplikace vnořený do HTML elementu s id=work
//t: Block,Panel
//s: Block
class PanelMain extends Panel {
//   Extends: Ezer.Panel
// ------------------------------------------------------------------- DOM add1
  DOM_add1 () {
    this.DOM_Block= jQuery('<div>')
      .addClass('Panel3')
      .css({display:'block'})
      .data('Ezer',this)
      .appendTo(jQuery('#work'))
  }
}
// =====================================================================================> PanelPlain
//c: PanelPlain
//      Panel přímo vnořený do Tabs, reaguje na události Tabs (onfocus,onblur),obsahuje MenuLeft
//t: Block,Panel
//s: Block
//i: PanelPlain.onfirstfocus - panel se poprvé stal viditelný, v tomto případě nenastane onfocus
//i: PanelPlain.onfocus - panel se stal viditelný
//i: PanelPlain.onblur - panel přestal být viditelný
class PanelPlain extends Panel {
//   Extends: Ezer.Panel
// ------------------------------------------------------------------- DOM add1
  DOM_add1 () {
    Ezer.assert(this.owner.DOM_Block,'panel '+this.id+' nelze vnořit do '+this.owner.id);
    this.owner._addPanel(this);                 // položka v Tabs
    this.DOM= jQuery('<section>')
      .css({width:this._w})
      .hide()
      .appendTo('#work');
    this.DOM_Block= jQuery('<div>')
      .addClass('Panel3')
      .css({minWidth:this._w,maxHeight:this._h})     //  min..,max.. je kvůli flex
      .data('Ezer',this)
      .appendTo(this.DOM)
      ;
  }
}

// ====================================================================================> Panel Right
//c: PanelRight
//      Panel přímo vnořený do Tabs, reaguje na události Tabs (onfocus,onblur),obsahuje MenuLeft
//t: Block,Panel
//s: Block
//i: PanelRight.onfirstfocus - panel se poprvé stal viditelný, v tomto případě nenastane onfocus
//i: PanelRight.onfocus - panel se stal viditelný
//i: PanelRight.onblur - panel přestal být viditelný
class PanelRight extends Panel {
//   menuleft: null,                                       // levostranné menu
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
    this.menuleft= null;                                 // levostranné menu
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  excite
//f: PanelRight.excite ()
//      zajistí prvotní zobrazení panelu a levého menu
  excite () {
    if ( !this.excited ) {
      super.excite();
//       this.parent();
      if ( this.menuleft )
        this.menuleft.excite();
    }
    return 1;
  }
// ------------------------------------------------------------------- DOM add1
  DOM_add1 () {
    Ezer.assert(this.owner.DOM_Block,'panel '+this.id+' nelze vnořit do '+this.owner.id);
    this.owner._addPanel(this);                 // položka v Tabs
    this.DOM= jQuery('<section>')
//      .css({width:this._w})
      .hide()
      .appendTo('#work');
    this.DOM_Block= jQuery('<div>')
      .addClass('PanelRight3')
      .css({minWidth:this._w,maxHeight:this._h})     //  min..,max.. je kvůli flex
      .data('Ezer',this)
      .appendTo(this.DOM)
      ;
  }
}
// =====================================================================================> PanelPopup
//c: PanelPopup
//      pokud je Panel vnořen do Tabs reaguje i na události
//t: Block
//s: Block
//i: PanelPopup.onfirstfocus - panel se poprvé stal viditelný, v tomto případě nenastane onfocus
//i: PanelPopup.onfocus - panel se stal viditelný
//i: PanelPopup.onblur - panel přestal být viditelný
//oo: PanelPopup.par{} - close:'no' zakáže zavírací tlačítko
//                       min_top:n minimální vzdálenost od horního okraje okna    
class PanelPopup extends Panel {
//os: PanelPopup.format - zarovnání nadpisu
//  ; 'c' : 'center' doprostřed
//  ; 'r' : 'right' doprava
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
    this.continuation= null;                     // bod pokračování pro modální dialog
  }
// -------------------------------------------------------------------------------------- modal
//fi: PanelPopup.modal ([l,t[,title,noevent=0,nomodal=1]])
//      ukáže panel jako modální dialog. Další příkaz bude interpretován až po uzavření dialogu.
//      Uzavření dialogu je provedeno funkcí hide, jehož argument se stane
//      hodnotou modal.
//a: l,t - poloha, pokud je vynechána bude dialog vycentrovám
//   title - volitelný nadpis, pokud má být odlišný od panel.title
//   nomodal - panel nebude modální, lze jej zavřít metodou close
  modal (l,t,title,noevent=0,nomodal=0) {
    this._show(l,t,noevent,title);
    if ( !nomodal ) this.DOM_modal(1);
    // pokud vrátí false pokračuje interpret další instrukcí; pokud vrátí objekt, uloží
    // do jeho continuation interpret stav, metody tohoto objektu mohou pokračovat ve výpočtu
    return this;
  }
// -------------------------------------------------------------------------------------- hide
//fm: PanelPopup.hide ([value])
  hide (value) {
    super.hide(value);
    if ( this.continuation ) {
      // konec modálního dialogu
      this.DOM_modal(0);
      this.continuation.stack[++this.continuation.top]= value;
      this.continuation.eval.apply(this.continuation,[0,1]);
      this.continuation= null;
    }
    return 1;
  }
// =================================================================================> PanelPopup DOM
//   DOM_shown: false,                           // true - pokud bylo poprvé ukázáno
// ---------------------------------------------------------------------------------- DOM add1
  DOM_add1 () {
    let min_top= this.options.par ? this.options.par.min_top || 0 : 0,
        close= this.options.par && this.options.par.close=='no' 
              ? '' : '<div class="pop_close"></div>';
    this.DOM= jQuery(`
        <div class="Popup3" tabindex="0">
          <div class="pop_head"><span></span></div>
          ${close}
          <div class="pop_body"></div>
        </div>`)
      .css({width:this._w,height:this._h})
      .data('Ezer',this)
      .appendTo(this.owner.DOM||'#work')
      .drags({handle:'div.pop_head',top:min_top})
      .hide();
    this.DOM_Block= this.DOM.find('div.pop_body')
//      .css({width:this._w,height:this._h})
      ;
    this.DOM_optStyle(this.DOM);
  }
// ---------------------------------------------------------------------------------- DOM add2
  DOM_add2 () {
    this.DOM.find('div.pop_close').first()
      .click( e => this.hide(0) );
  }
// ---------------------------------------------------------------------------------- _show
  _show (l,t,noevent,title) {
    // panel position
    if ( l!==undefined && t!==undefined )
      this.DOM.css({left:Number(l),top:Number(t),marginLeft:0,marginTop:0});
    else
      this.DOM.css({left:'50%',top:'50%',marginLeft:-this._w/2-5,marginTop:-this._h/2-15});
    // panel title
    if ( title!==undefined )
      this.DOM.find('div.pop_head span').first().html(title);
    else {
      var title2= (this.options.title||this.id)
        .replace(/\[fa-([^\]]+)\]/g,"<i class='fa fa-fw fa-$1'></i>");
      this.DOM.find('div.pop_head span').first().html(title2);
    }
    this.DOM.fadeIn(Ezer.options.fade_speed);
    // event when wanted
    if ( !noevent && this.part ) {
      if ( this.virgin ) {
        this.virgin= false;
        Ezer.app.onfirstfocus(this);
        if ( this.part.onfirstfocus )
          this.fire('onfirstfocus',[]);
        else
          this.fire('onfocus',[]);
      }
      else
        this.fire('onfocus',[]);
    }
    return this;
  }
// ---------------------------------------------------------------------------------- _hide
  _hide () {
    this.fire('onblur',[]);
    this.DOM.fadeOut(Ezer.options.fade_speed);
    return this;
  }
// ---------------------------------------------------------------------------------- _hide
  close () {
    return this.hide();
  }
// ---------------------------------------------------------------------------------- DOM modal
  DOM_modal (on) {
    var mask= jQuery('#popup_mask3');
    if ( on ) mask.show(); else mask.hide();
    return this;
  }
}
// aliasy
//fm: PanelPopup.close ([value])
//PanelPopup.prototype.close= PanelPopup.prototype.hide;

// ======================================================================================> PanelFree
//c: PanelFree
//      pokud je Panel vnořen do Tabs reaguje i na události
//t: Block
//s: Block
class PanelFree extends PanelPopup {
// ======================================================================================> PanelFree
// panel vnořený přímo do pracovního prostoru bez vazby na menu
// ---------------------------------------------------------------------------------- DOM add1
  DOM_add1 () {
    this.DOM_Block= this.DOM= jQuery(`<div class="Panel3" style="display:none">`)
      .appendTo('#work');
  }
// ---------------------------------------------------------------------------------- _show
  _show (l,t) {
    this.DOM.css({display:'block',left:Number(l),top:Number(t)});
    if ( this.virgin ) {
      this.virgin= false;
      Ezer.app.onfirstfocus(this);
      if ( this.part && this.part.onfirstfocus )
        this.fire('onfirstfocus',[]);
      else
        this.fire('onfocus',[]);
    }
    else
      this.fire('onfocus',[]);
    return this;
  }
// ---------------------------------------------------------------------------------- _hide
  _hide () {
    this.fire('onblur',[]);
    this.DOM.css({display:'none'});
    return this;
  }
}

// ============================================================================================> Var
//c: Var
//      proměnná si ponechává pouze jméno - ostatní znaky přejímá ze své hodnoty
//t: Block
//s: Block
class Var extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
//os: Var.format - vzhled pro use form
//  ; 'n' : display=none
    this.value= null;
  }
  constructor (owner,desc,DOM,id) {
    super(owner,desc,DOM,id);
    this._of= desc._of;
    if ( this.options.value!=undefined ) {
      // proměnná má počáteční hodnotu
      this.value= this.options.value;
    }
    else if ( desc._init ) {
      if ( desc._of=='form' ) {
        let name= desc._init,
            ctx= Ezer.code_name(name,null,this);
        Ezer.assert(ctx,name+' je neznámé jméno - očekává se jméno form');
        Ezer.assert(ctx[0].type=='form',name+' není jméno form');
        var form= new Form(this,ctx[0],DOM,this.options,ctx[0].id);
        this.set(form);
        this.value.id= id;
      }
      else if ( desc._of=='area' && typeof Area==="function" ) {
        let name= desc._init,
            ctx= Ezer.code_name(name,null,this);
        Ezer.assert(ctx,name+' je neznámé jméno - očekává se jméno area');
        Ezer.assert(ctx[0].type=='area',name+' není jméno area');
        // nalezneme panel
        var panel= null;
        for (var o= this.owner; o; o= o.owner) {
          if ( o.type.substr(0,5)=='panel' ) {
            panel= o;
            break;
          }
        }
        if ( panel && panel.DOM_Block ) {
          // vyvoření area bez události area_oncreate
          var area= new Area(panel,ctx[0],panel.DOM_Block,this.options,ctx[0].id,[],true);
          this.set(area);
          this.value.id= id;
        }
        else Ezer.error("area není vnořena do panelu");
      }
    }
    // vložení případných podčástí (např. přepisu těl procedur)
    this.subBlocks(desc,this.DOM_Block);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  start
//f: Var.start (code,oneval)
  start (codes,oneval) {
    super.start(codes,oneval);
    if ( this._of=='form' && this.value ) {
      this.value.start(codes,oneval);
    }
  }
// ------------------------------------------------------------------------------------ set
//fm: Var.set (val[,part])
//      nastaví hodnotu proměnné, pokud je typu object pak part určuje podsložku
  set (val,part) {
    if ( part!==undefined ) {
      if ( Array.isArray(this.value) ) {
        let v= this.value,
            n= Number(part);
        Ezer.assert(!isNaN(n),'set: index pole musí být číslo',this);
        v[n]= val;
      }
      else {
        Ezer.assert(this.value===null || typeof(this.value)=='object',
          'set s 2.parametrem lze použít jen na objekty nebo pole',this);
        let is= typeof(part)=='string' ? part.split('.') : [part],
            v= this.value||{};
        for (var i= 0; i<is.length-1; i++) {
          if ( typeof(v[is[i]])!='object' )
            v= v[is[i]]= {};
          else
            v= v[is[i]];
        }
        v[is[i]]= val;
      }
    }
    else {
      this.value= val;
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ get
//fm: Var.get ([part])
//      vrátí hodnotu proměnné, part může být složené jméno je-li hodnotou objekt
//      (pokud part neurčuje složku objektu, funkce vrátí '')
  get (part) {
    var v;
    if ( this.value===null )
      v= 0;
    else if ( part!==undefined ) {
      if ( Array.isArray(this.value) ) {
        v= this.value;
        var n= Number(part);
        Ezer.assert(!isNaN(n),'get: index pole musí být číslo',this);
        v= v[n];
      }
      else {
        Ezer.assert(typeof(this.value)=='object',
          'get s parametrem lze použít jen na objekty nebo pole',this);
        v= this.value;
        if ( Number.isInteger(part) ) {
          if ( v[part]=='' )
            v= '';
          else
            v= v[part]===undefined ? '' : v[part];
        }
        else {
          for (const i of part.split('.')) {
            if ( v[i]=='' )
              v= '';
            else
              v= v[i]===undefined ? '' : v[i];
          }
        }
      }
    }
    else
      v= this.value;
    return v===false ? 0 : v;
  }
}

// ==========================================================================================> Const
//c: Const ()
//      compiled "const" Ezer block
//t: Block
//s: Block
class Const extends Block {
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    if ( this.options._expr ) {
      // compiling of constant defined by expression
      this.value= 0;
      for (let i in this.options._expr) {
        var x= this.options._expr[i];
        switch (x[0]) {
        case 'k':                               // constant name [k,value,id,-]
          this.value+= (x[3] && x[3]=='-') ? -this._const(x[2]) : this._const(x[2]);
          break;
        case 'n':                               // number literal [n,value,-]
          this.value+= (x[2] && x[2]=='-') ? -x[1] : x[1];
          break;
        }
      }
    }
    else {
      this.value= Ezer.const_value(id,this.options.value);
    }
  }
  get() {
    return this.value;
  }
}

// ===========================================================================================> Proc
//c: Proc
//      procedura, obsluha událostí (zatím onstart) může mít uvedenu prioritu
//t: Block
//s: Block
class Proc extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
//oc: Proc.code - kód procedury
//oc: Proc.prior - priorita procedury (jen pro onstart)
//oc: Proc.context - kontext procedury (pro řešení významu jmen) tj. místo definice
  constructor (owner,desc,context) {
    super(owner,desc);
    this.code= desc.code;
    this.prior= this.options && this.options.prior ? this.options.prior : 0;
    this.context= context;
    this.stop= 0;
    this.trace= 0;
  }
  proc_stop (on) {
    this.stop= on;
  }
  proc_trace (on) {
    this.trace= on;
  }
  reinitialize (desc) {
    this.code= desc.code;
  }
}

// ==========================================================================================> Table
//c: Table ([options])
//      MySQL tabulka
//t: Block
//s: Block
class Table extends Block {
//os: Table.db - jméno databáze, pokud se liší od hlavní
//-
//os: Table.key_id - primární klíč, pokud má jiný tvar než 'id_'+jméno tabulky
// -------------------------------------------------------------------------------- delete record
//fx: Table.delete_record (cond[,count=1])
// smazání 1 záznamu z tabulky v kontextu (hlásí chybu pokud podmínka cond specifikuje více záznamů - a nesmaže)
//a: cond - podmínka
//r: y - ok
  delete_record (cond,count) {
    var x= {cmd:'delete_record', db:this.options.db||'', table:this.id, cond:cond, count:count||1};
    return x;
  }
  delete_record_ (y) {
    return y.ok;
  }
// -------------------------------------------------------------------------------- insert record
//fx: Table.insert_record ({id:val,...})
//      přidá do tabulky nový záznam naplněný podle objektu
//a: couples - objekt s dvojicemi název_polozky:hodnota_polozky
//r: y - klíč vytvořeného záznamu, vznikl-li formou auto_increment, nebo 1; 0 při neúspěchu
  insert_record (couples) {
    var x= {cmd:'insert_record', db:this.options.db||'', table:this.id, par:couples};
    return x;
  }
  insert_record_ (y) {
    return y.ok;
  }
// -------------------------------------------------------------------------------- update record
//fx: Table.update_record (cond,set)
// update 1 záznamu z tabulky podle hodnot předaných v objektu set
// (hlásí chybu pokud podmínka cond specifikuje více záznamů - a nesmaže)
//a: cond - podmínka
//   set - {field:value,..}
//r: y - ok
  update_record (cond,set) {
    var x= {cmd:'update_record', db:this.options.db||'', table:this.id, cond:cond, set:set};
    return x;
  }
  update_record_ (y) {
    return y.ok;
  }
}

// ============================================================================================> Map
//c: Map ([options])
//      map m: table t {where: ... order:... key:...}
//      zpřístupnění obsahu tabulky v klientovi, používá se zpravidla pro číselníky
//t: Block
//s: Block
class EzerMap extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
    this.options= {
//os: Map.where  - výběrová podmínka, default 1
      where:1,
//os: Map.order  - pořadí, default ''
      order:'',
//os: Map.key_id - vybírající položka (klíč), default je první pole tabulky
//os: Map.db - databáze obsahujíc danou tabulku
      key_id:''
    };
    this.data= {};
    this.start_code= {level:'map',code:[{o:'o',i:'?'},{o:'x',i:'map_load'}]};
  }
  constructor (owner,desc,context,id) {
    super(owner,desc,context,id);
    this.start_code.code[0].i= this.self();
    var ctx= Ezer.code_name(desc._init,null,this);
    Ezer.assert(ctx && ctx[0].type=='table',desc._init+' je chybné jméno table v map '+this.id);
    this.table= ctx[0];
    this.db= this.options.db || null;
    if ( !this.options.key_id )
      this.options.key_id= firstPropertyId(this.table.part);
  }
// ------------------------------------------------------------------------------------ map load
//fx: Map.map_load ([cond])
//      interní metoda spouštěná přes onstart (podle start_code) a z metody SelectMap.selects;
//      cond je nepovinná podmínka na položky tabulky _cis
//a: x - {table:..,cond:...,order:...}
//   y - {values:[[id1:val1,...]...],rows:...}
  map_load (cond) {
    // vytvoř parametry dotazu
    var where= this.options.where + (cond ? ' AND '+cond : '');
    var x= {cmd:'map_load',table:this.table.id,where:where,order:this.options.order};
    if ( this.table.options.db ) x.db= this.table.options.db;
    if ( this.db ) x.db= this.db;
    return x;
  }
  map_load_ (y) {
    // zpracování výsledku dotazu do tabulky data: key -> data
    this.data= {};                              // vyprázdni starý obsah
    this.data_order= {};
    for (var i= 1; i<=y.rows; i++) {
      for (var vi in y.values[i]) {
        if ( !this.data[vi] ) this.data[vi]= {};
        var key= y.values[i][this.options.key_id];
        this.data_order[i]= key;
        this.data[vi][key]= y.values[i][vi];
      }
    }
    return y.rows;
  }
// ------------------------------------------------------------------------------------ get
//fm: Map.get (key[,položka='hodnota'])
//      vrátí textovou hodnotu klíče podle dané mapy (resp. udanou položku tabulky mapy)
  get (key,map_field) {
    map_field= map_field||'hodnota';
    var ret= '';
    if ( this.data[map_field] )
      ret= this.data[map_field][key] || '';
    else
      Ezer.error("map.get '"+map_field+"' je neznámá položka mapy "+this.id);
    return ret;
  }
}

// ===========================================================================================> View
//c: View
//      proměnná si ponechává pouze jméno - ostatní znaky přejímá ze své hodnoty
//t: Block
//s: Block
class View extends Block {
//os: View.join_type  - volba typu JOIN, nejčastěji LEFT
//-
//os: View.join - fráze za JOIN včetně ON nebo USING
//-
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    this._of= desc._of;
    if ( this._of!='expr' && desc._init ) {
      let id= desc._init,
          ctx= Ezer.code_name(id,null,this);
      Ezer.assert(ctx && ctx[0],id+' je neznámé jméno  - očekává se jméno table');
      Ezer.assert(ctx[0].type=='table',id+' není jméno table');
      this.value= ctx[0];
      this.value.id= id.split('.').pop();
    }
  }
// ------------------------------------------------------------------------------------ key
//fm: View.key ([key_val])
//      pokud je key definováno, tak nastaví view.key, pokud je nedefinováno, vrátí aktuální hodnotu
//      (lze použít jen pro Var typu view)
  key (key) {
    if ( key!==undefined ) {
      // definuj hodnotu klíče
      this._key= typeof(key)=='string' ? Number(key) : key;
      key= 1;
    }
    else {
      key= this._key;
    }
    return key;
  }
// ------------------------------------------------------------------------------------ json
//fm: View.json ([obj][changed_only])
//      jako getter navrátí objekt obsahující hodnoty elementů (pro select klíče), které mají některý
//      z atributů data,expr,value - pokud je changed_only==1 pak vrací jen změněné hodnoty;
//      jako setter nastaví podle parametru (který musí být typu objekt) hodnoty (pro select klíče)
//      view
//r: y - {name:value,...} pro getter; 1/0 pro setter (0 při selhání)
  json (obj) {
     return Form.prototype.json.call(this,obj);    // využijeme společný kód z Ezer.Form
  }
// ------------------------------------------------------------------------------------ copy
//fm: View.copy ()
//      vynuluje klíč použití formuláře a nastaví všechny položky jako změněné (nevyvolává onchange)
//      pokud mají v atributu data použito toto view
  copy () {
    this._key= null;                      // vynuluj klíč view => místo save bude insert
    for (var ie in this.owner.part) {     // projdi elementy fomuláře a nastav je jako změněné
      var field= this.owner.part[ie];
      if ( field.view==this && field.change && field.data ) {
//       if ( field._load && field.data && field.view==this ) {
//         if ( ['field','field.date','edit','select.map','check','radio','chat'].contains(field.type) )
          field.change(1);
      }
    }
    return true;
  }
// ------------------------------------------------------------------------------------ init
//fm: View.init ([init_values=0])
//      nastaví elementy svázané s daty použití formuláře na prázdné
//      nebo pro init_values==1 na defaultní hodnoty
//      nebo pro init_values==2 na defaultní hodnoty s nastavením elementů jako change
//a: init_values==1 : nastaví hodnoty podle atributu value
  init (init_values) {
    this._key= null;                      // vynuluj klíč => místo save bude insert
    this._changed= false;                 // bude true po změně nějaké položky
    for (var ie in this.owner.part) {           // projdi elementy, které mají toto view
      var elem= this.owner.part[ie];
      if ( elem.view==this && elem.skill && elem.init ) {
        elem.init(init_values);
      }
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ plain
//fm: View.plain ()
//      odstraní příznak změny ze všech přístupných elementů view
  plain () {
    this._changed= false;                 // bude true po změně nějaké položky
    for (var ie in this.owner.part) {           // projdi elementy
      var elem= this.owner.part[ie];
      if ( elem.view==this && elem.skill && elem.plain ) {
        elem.plain();
      }
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ load
//fx: View.load ([key_val=view.key,[cond]])
//      přečte položky formuláře, které mají v atributu data použito toto view
//      ostatní položky formuláře zůstanou nezměněné (položky s expr jsou vynechány
//      i když používají pouze toto view).
//      Pokud je není key_val definováno, použije aktuální hodnotu klíče
//      nebo vyhledá záznam podle nepovinné podmínky (key_val v tom případě ignoruje)
//      (metoda nevyvolává onload)
  load (key_val,cond) {
    Ezer.assert(this.value.options.key_id,'table referované přes view chybí definice key_id',this);
    // vytvoř parametry dotazu
    var key= key_val||this._key, pipe;
    Ezer.assert(cond || !isNaN(key),'view.load nemá číselný klíč věty',this);
    var table= this.value;
    var x= {cmd:'form_load', key:key, key_id:table.options.key_id,
      db:table.options.db||'', table:table.id, fields:[], joins:{}};
//     $each(this.owner.part,function(field,id) {
    for (const id in this.owner.part) { const field= this.owner.part[id];
      if ( field._load && field.data && field.view==this ) {
        var desc= {id:field.id,field:field.data.id};
        if ( field.options && field.options.sql_pipe!=='' &&
            ((pipe= field.options.sql_pipe) || (pipe= field.data.options.sql_pipe)) ) {
          desc.pipe= pipe;
        }
        x.fields.push(desc);
      }
    }
    if ( !x.fields.length )
      Ezer.error('chybný kontext pro view_load');
    if ( cond ) x.cond= cond;
    return x;
  }
  load_ (y) {
    // zpracování výsledku dotazu
    this._key= y.key;
    if ( this._key ) {
//       $each(this.owner.part,function(field,id) {
      for (const id in this.owner.part) { const field= this.owner.part[id];
        if ( field.data && field.view==this ) {
          if ( y.values[id]===undefined || y.values[id]===null )
            field.init();                               // inicializuj hodnoty
          else
            field._load(y.values[id],this._key);        // ulož hodnoty
        }
      }
    }
    return this._key ? 1 : 0;
  }
// ------------------------------------------------------------------------------------ save
//fx: View.save ()
//      zapíše změněné položky formuláře, které mají v atributu data použito toto view,
//      pokud nejsou takové položky vrátí 0, jinak vrátí 1
//      (metoda nevyvolává onsave)
// Pozn. Odlišné posouzení 'changed' u elementů typu EditHtml je upřesněno u EditHtml.changed
  save () {
    var x= null, table= this.value, changes= 0;
    if ( this._key ) {
      var fields= [], pipe;
//       $each(this.owner.part,function(field,id) {
      for (const id in this.owner.part) { const field= this.owner.part[id];
        if ( field.changed && field.changed() && field.data && field._save && field.view==this ) {
          // pošli jen změněné položky tohoto view
          let vmo= field._save();
          let desc= {id:field.data.id,val:vmo.val};
          if ( vmo.mode) desc.mode= vmo.mode;
          if ( vmo.old) desc.old= vmo.old;
          if ( field.options.sql_pipe!=='' &&
               (pipe= field.options.sql_pipe) || (pipe= field.data.options.sql_pipe) )
            desc.pipe= pipe;
          fields.push(desc);
          changes++;
        }
      }
      if ( changes ) {
        x= {cmd:'form_save', db:table.options.db||'', table:table.id,
          key_id:table.options.key_id, key:this._key, fields:fields};
      }
      else
        x= null;
    }
    else
      Ezer.error("RUN ERROR 'view.save' - nulový klíč");
    return x;
  }
  save_ () {
    return 1;
  }
// ------------------------------------------------------------------------------------ insert
//fx: View.insert ([all])
//      vytvoření nového záznamu ze změněných položek (pokud all=1 pak ze všech )
//      které mají v atributu data použito toto view
//      (metoda nevyvolává onsave)
//r: y - klíč vytvořeného záznamu, vznikl-li formou auto_increment, nebo 1; 0 při neúspěchu
//a: all - 1 vynutí uložení všech položek
  insert (all) {
    var x= null, table= this.value;
    var fields= [], pipe;
//     $each(this.owner.part,function(field,id) {
    for (const id in this.owner.part) { const field= this.owner.part[id];
      if ( (all || field.changed && field.changed()) && field.data && field._save && field.view==this ) {
        // pošli jen položky tohoto view
        let vmo= field._save();
        let desc= {id:field.data.id,val:vmo.val};
        if ( vmo.mode) desc.mode= vmo.mode;
        if ( field.options.sql_pipe!=='' &&
            (pipe= field.options.sql_pipe) || (pipe= field.data.options.sql_pipe) )
          desc.pipe= pipe;
        fields.push(desc);
      }
    }
    x= {cmd:'form_insert', db:table.options.db||'', table:table.id, fields:fields};
    return x;
  }
  insert_ (y) {
    this._key= y.key;
    return y.key;
  }
}

// ===========================================================================================> Form
//c: Form ([options])
//      formulář
//t: Block
//s: Block
//i: Form.onload - po načtení formuláře (metodou load)
//i: Form.onsave - před uložením formuláře (v těle nesmí být asynchronní funkce)
class Form extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
    this._key= null;                           // klíč aktivního záznamu
    this._key_id= null;                        // jméno klíče
    this._changed= false;                      // po init.load,insert byl změněn nějaký element
    this._option= {};                          // stav formuláře, ovládaný z EzerScriptu
  }
  constructor (owner,desc,DOM,options,id) {
    super(owner,desc,DOM,id);
    Object.assign(this.options,options); // moo: this.setOptions(options);
    if ( this.options.key_id )
      this._key_id= this.options.key_id;
    this._coord();
    this.DOM_add1();
    // vložení podčástí
    this.subBlocks(desc,this.DOM_Block);
    this.DOM_add2();
  }
// ------------------------------------------------------------------------------------ tagged
//fm: Form.tagged (tags[,inlist=0])
//      vrátí pole elementů vyhovujících podmínce tags, pole lze zpracovávat například příkazem
//      foreach(form.tagged('x'),procx); kde procx je jednoparametrická procedura
//      pokud je inlist=1 projde i elementy vnořené do List
  tagged (tags,inlist) {
    var list= [];
    var re= new RegExp(tags);
    // projdi elementy
    for (var ie in this.part) {           
      var elem= this.part[ie];
      if ( elem instanceof List ) {
        if ( inlist ) {
          // projdi elementy vnořené do List
          for (var irow in elem.part) {   
            var row= elem.part[irow];
            if ( row instanceof ListRow ) {
              for (var ile in row.part) {   
                var listelem= row.part[ile];
                if ( listelem.options.tag && re.test(listelem.options.tag) ) {
                  list.push(listelem);
                }
              }
            }
          }
        }
      }
      if ( elem.options.tag && re.test(elem.options.tag) ) {
        list.push(elem);
      }
    }
    return list;
  }
// ------------------------------------------------------------------------------------ changed
//fm: Form.changed ([on])
//      nastaví příznak změny formuláře podle 'on' nebo jej zjistí, nevyvolává událost onchanged
  changed (on) {
    var ok= 1;
    if ( on==undefined )
      ok= this._changed ? 1 : 0;
    else
      this._changed= on;
    return ok;
  }
// ------------------------------------------------------------------------------------ init
//fm: Form.init ([init_values=0])
//      nastaví elementy svázané s daty použití formuláře na prázdné
//      nebo pro init_values==1 na defaultní hodnoty
//      nebo pro init_values==2 na defaultní hodnoty s nastavením elementů jako change
//a: init_values==1 : nastaví hodnoty podle atributu value
  init (init_values) {
    this._key= null;                      // vynuluj klíč => místo save bude insert
    this._changed= false;                 // bude true po změně nějaké položky
    for (var ie in this.part) {           // projdi elementy
      var elem= this.part[ie];
      if ( elem.skill && elem.init ) {
        elem.init(init_values);
      }
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ plain
//fm: Form.plain ()
//      odstraní příznak změny ze všech přístupných  elementů formuláře
  plain () {
    this._changed= false;                 // bude true po změně nějaké položky
    for (var ie in this.part) {           // projdi elementy
      var elem= this.part[ie];
      if ( elem.skill && elem.plain ) {
        elem.plain();
      }
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ focus
//fm: Form.focus ()
//      označení prvního elementu formuláře
  focus () {
    for (var ie in this.part) {           // projdi elementy a najdi první s funkcí focus
      if ( this.part[ie].focus ) {
        this.part[ie].focus();            // a proveď ji
        break;
      }
    }
    return 1;
  }
// ----------------------------------------------------------------------------------- stacks
//      fm: Form.stacks (tag_list[,smer='down',space_h=0,space_i=0,space_b=0])       OBSOLETE
//      přeskládá formulář podle seznamu tagů, podle smer - down:shora dolů | up:zdola nahoru;
//      prázdné tagy ignoruje;
//      nepovinné parametry obsahují postupně mezeru přidávanou na začátek, mezi tagy a na konec
//      upraví rozměry a polohu form, navrací výslednou výšku
  stacks_old (tag_list,smer,space_h,space_i,space_b) {
    OBSOLETE('form.stacks_old',"form.stacks"); return 0;
/*
    smer= smer || 'down';
    space_h= space_h||0;
    space_i= space_i||0;
    space_b= space_b||0;
    var sum_h= space_h;
    var tags= tag_list.split(','), tags_= del= '';
    for (var i= 0; i<tags.length; i++) if ( tags[i] ) {
      var tag= tags[i];
      this.display(1,tag);
      this.property({down:sum_h},tag);
      tags_+= del+tag; del= '|';
      var bounds= this.property({return:'bounds'},tags_);
      sum_h= (bounds._t||0) + bounds._h + space_i;
    }
    sum_h+= space_b;
    this.DOM_Block.setStyles({height:sum_h,top:this.options._t+(smer=='up'?this.options._h-sum_h:0)});
    return sum_h;
*/
  }
// ----------------------------------------------------------------------------------- stacks
//fm: Form.stacks (tag_list[,smer='down',space_h=0,space_i=0,space_b=0])
//      přeskládá formulář podle seznamu tagů, podle smer - down:shora dolů | up:zdola nahoru;
//      prázdné tagy ignoruje;
//      nepovinné parametry obsahují postupně mezeru přidávanou na začátek, mezi tagy a na konec
//      upraví rozměry a polohu form, navrací výslednou výšku
  stacks (tag_list,smer,space_h,space_i,space_b) {
    smer= smer || 'down';
    space_h= space_h||0;
    space_i= space_i||0;
    space_b= space_b||0;
    var sum_h= space_h;
    var tags= tag_list.split(','), tag= '';
    var rect= {_l:0,_t:0,_w:0,_h:0};
    for (var i= 0; i<tags.length; i++) if ( tags[i] ) {
      tag= tags[i];
      this.display(1,tag);
      this.property({down:0},tag);
      rect= this.property({return:'bounds'},tag); rect.tag= tag;
      this.property({down:sum_h},tag);
      sum_h+= (rect._t||0) + rect._h + space_i;
    }
    sum_h+= space_b;
    if ( smer=='down' )  
      this.DOM_Block.css({height:sum_h});
    else
      this.DOM_Block.css({height:sum_h,top:this.DOM_Block.css('top')-sum_h});
    return sum_h;
  }
// ------------------------------------------------------------------------------------ json
//fm: Form.json ([obj][changed_only])
//      jako getter navrátí objekt obsahující hodnoty elementů (pro select klíče), které mají některý
//      z atributů data,expr,value - pokud je changed_only==1 pak vrací jen změněné hodnoty;
//      jako setter nastaví podle parametru (který musí být typu objekt) hodnoty (pro select klíče)
//      form
//r: y - {name:value,...} pro getter; 1/0 pro setter (0 při selhání)
  json (obj) {
    // kód je společný i pro View.json
    var top_part= this instanceof Form ? this.part : this.owner.part;
    var top_view= this instanceof Form ? null : this;
    if ( obj && obj!=1 ) {
      // setter
      var ok= 1;                                // 1 => setter uspěl
      for (var ie in obj) {
        var elem= top_part[ie];
        if ( top_view && elem.view!=top_view )  // pokud this=view testuj shodu
          continue;
        if ( elem instanceof List ) {      // najdi podformulář List
          if ( typeof(obj[ie])!='object' || elem.last+1<obj[ie].length ) {
            ok= 0;                              // error: element není List, nebo je moc krátký
            break;
          }
          // obj[ie] je správně dlouhé pole, vlož je do List
          for (var ies=0; ies<obj[ie].length; ies++) {
            var sublist= elem.part[ies],
                subobj= obj[ie][ies];
            for (var iese in subobj) {          // najdi element podformuláře List
              var lelem= sublist.part[iese];
              if ( top_view && elem.view!=top_view )  // pokud this=view testuj shodu
                continue;
              if ( !lelem || !lelem.set ) {     // error: neznámý nebo nevhodný element
                ok= 0;                          // pokud délka není dostatečná vrať 0 = error
                break;
              }
              if ( lelem instanceof Select )
                lelem.key(subobj[iese]);
              else
                lelem.set(subobj[iese]);
            }
          }
        }
        else {
          if ( !elem || !elem.set ) {           // error: neznámý nebo nevhodný element
            ok= 0;                              // pokud délka není dostatečná vrať 0 = error
            break;
          }
          if ( elem instanceof Select )
            elem.key(obj[ie]);
          else
            elem.set(obj[ie]);
        }
      }
      obj= ok;
    }
    else {
      // getter
      let changed= obj;
      obj= {};
      for (let ie in top_part) {                // projdi elementy
        let elem= top_part[ie];
        if ( top_view && elem.view!=top_view )  // pokud this=view testuj shodu
          continue;
        if ( elem instanceof List ) {
          obj[elem.id]= [];
          for (let ies in elem.part) {          // projdi podformuláře List
            let subform= elem.part[ies],
                subobj= {};
            for (let iese in subform.part) {    // projdi elementy podformuláře List
              let lelem= subform.part[iese];
              if ( top_view && lelem.view!=top_view )
                continue;                       // pokud this=view testuj shodu
              if ( lelem.skill && lelem.get && (changed ? lelem.changed && lelem.changed() : 1) &&
                  (lelem.data || lelem.options.expr || lelem.options.value!==undefined) ) {
                subobj[lelem.id]= lelem instanceof Select?lelem.key():lelem.get();
              }
            }
            obj[elem.id].push(subobj);
          }
        }
        else if ( elem.skill && elem.get && (changed ? elem.changed && elem.changed() : 1) &&
            (elem.data || elem.options.expr || elem.options.value!==undefined) ) {
          obj[elem.id]= elem instanceof Select?elem.key():elem.get();
        }
      }
    }
    return obj;
  }
// ------------------------------------------------------------------------------------ copy
//fm: Form.copy ()
//      vynuluje klíč použití formuláře a nastaví všechny položky jako změněné (nevyvolává onchange)
  copy () {
    this._key= null;                      // vynuluj klíč => místo save bude insert
    for (var ie in this.part) {           // projdi elementy a nastav je jako změněné
      if ( this.part[ie].change )
        this.part[ie].change(1);
    }
    return true;
  }
// ------------------------------------------------------------------------------------ option
//fm: Form.option (key_val)
//      opraví option pro položku 'x' formátu
//a: key_val - key:val
  option (key_val) {
    var x= key_val.split(':'), key= x[0], val= Number.parseInt(x[1]);
    this._option[key]= val||0;
    for (let i in this.part) if ( this.type ) {
      var elem= this.part[i];
      if ( elem._f && elem._f('x')>=0 ) {
        // naplň nebo zruš zapamatované hodnoty
        if ( val ) {
          elem._fixed_save();
          elem.DOM_fixed(1);
        }
        else {
          elem.fixed_value= null;
          elem.DOM_fixed(0);
        }
      }
    }
    return true;
  }
// ------------------------------------------------------------------------------------ key
//fm: Form.key ([key])
//      pokud je definováno key tak jej nastaví jako klíč formuláře, vrátí 1
//      pokud není pak vrátí aktuální hodnotu
  key (key) {
    if ( key!==undefined ) {
      // definuj hodnotu klíče
      this._key= typeof(key)=='string' ? Number(key) : key;
      key= 1;
    }
    else {
      key= this._key;
    }
    return key;
  }
// ------------------------------------------------------------------------------------ id_key
//fm: Form.id_key ()
//      jméno primárního SQL klíče, je definováno voláním metod load, save, insert
  id_key () {
    return this._key_id;
  }
// ------------------------------------------------------------------------------------ same
//fm: Form.same ([all=0])
//      vrátí true, pokud v použití formuláře není žádný element s atributem data ve stavu 'changed';
//      pokud je all=1 pak metoda prochází všechny elementy
// Pozn. Odlišné posouzení 'changed' u elementů typu EditHtml je upřesněno u EditHtml.changed
  same (all) {
    var same= 1;
    all= all||false;
   parts:
    for (var ie in this.part) {           // projdi elementy
      var elem= this.part[ie];
      if ( elem instanceof List ) {
        for (var ies in elem.part) {      // projdi podformuláře List
          var subform= elem.part[ies];
          for (var iese in subform.part) {// projdi elementy podformuláře List
            var lelem= subform.part[iese];
            if ( lelem.changed && lelem.changed() ) {
              if ( !all && lelem.data===undefined )
                continue;
              same= 0;
              break parts;
            }
          }
        }
      }
      else if ( elem.changed && elem.changed() ) {
        if ( !all && elem.data===undefined )
          continue;
        same= 0;
        break parts;
      }
    }
    return same;
  }
// ------------------------------------------------------------------------------------ load
//fx: Form.load ([key_val=form.key,[cond]])
//      načtení dat do skalárních polí formuláře podle hodnoty primárního klíče tabulky
//      nebo podle nepovinné podmínky (key_val v tom případě ignoruje)
//a: key_val - hodnota primárního klíče
//   cond - mysql podminka
  load (key_val,cond) {
    // vytvoř parametry dotazu
    var key= key_val||this._key;
    Ezer.assert(cond || !isNaN(key),'form.load nemá číselný klíč věty',this);
    var x= {cmd:'form_load', key:key_val||this._key, fields:[], joins:{}};
    this._changed= false;                 // bude true po změně nějaké položky
//     $each(this.part,function(field,id) {
    for (const id in this.part) { const field= this.part[id];
      if ( field._load && (field.data || field.options.expr) )
        this._fillx(field,x);
    }
    if ( !x.fields.length )
      Ezer.error('chybný kontext pro form_load');
    this._key_id= x.key_id;
    if ( cond ) x.cond= cond;
    return x;
  }
  load_ (y) {
    // zpracování výsledku dotazu
    this._key= y.key;
    if ( this._key ) {
//       $each(this.part,function(field,id) {
      for (const id in this.part) { const field= this.part[id];
        if ( field.data || field.options.expr ) {
          if ( y.values[id]===undefined || y.values[id]===null )
            field.init();                               // inicializuj hodnoty
          else
            field._load(y.values[id],this._key);        // ulož hodnoty
        }
      }
      this.fire('onload');                              // proveď akci formuláře po naplnění daty
    }
    return this._key ? 1 : 0;
  }
// ------------------------------------------------------------------------------------ save
//fx: Form.save ()
//      uložení změněných elementů formuláře do záznamu s klíčem form.key.
// Pozn. Odlišné posouzení 'changed' u elementů typu EditHtml je upřesněno u EditHtml.changed
//e: onsave - před uložením formuláře (test nesmí být asynchronní funkce)
  save (omitt) {
    var ok= this.fire('onsave');                        // proveď akci před uložením dat
    Ezer.assert(ok!==false,'form.save: test formuláře nesmí být asynchronní funkce');
    var x= null, changes= 0;
    // pokud kontrola není, nebo skončila úspěšně, pokračujeme v ukládání
    if ( ok ) {
      ok= false;
      if ( this._key ) {
        let fields= [], table, pipe;
//         $each(this.part,function(field,id) {
        for (const id in this.part) { const field= this.part[id];
          if ( !table ) table= field.table;
          if ( field.changed && field.changed() && field.data && field._save ) {
            // pošli jen změněné položky s ošetřenou vazbou na položku
            let vmo= field._save();
            let desc= {id:field.data.id,val:vmo.val};
            if ( vmo.mode) desc.mode= vmo.mode;
            if ( vmo.row) desc.row= vmo.row;            // jen pro chat
            if ( vmo.old) desc.old= vmo.old;
            if ( field.options.sql_pipe!=='' &&
                (pipe= field.options.sql_pipe) || (pipe= field.data.options.sql_pipe) )
              desc.pipe= pipe;
            fields.push(desc);
            changes++;
          }
        }
        if ( changes ) {
          x= {cmd:'form_save', db:table.options.db||'', table:table.id,
            key_id:table.options.key_id, key:this._key, fields:fields};
        }
        else Ezer.fce.warning("'save' - nebyla provedena žádná změna");
      }
      else Ezer.error("RUN ERROR 'save' - nulový klíč");
      this._key_id= x ? x.key_id : '';
    }
    return x;
  }
  save_ () {
    return 1;
  }
// ------------------------------------------------------------------------------------ insert
//fx: Form.insert ([all])
//      vytvoření nového záznamu ze změněných položek (pokud all=1 pak ze všech )
//      s atributem data (metoda předpokládá, že všechna data pocházejí z jedné tabulky,
//      jinak je třeba použít view.insert)
//a: all - 1 vynutí uložení všech položek
//r: y - klíč vytvořeného záznamu, vznikl-li formou auto_increment, nebo 1; 0 při neúspěchu
//e: onsave - před uložením formuláře (test nesmí být asynchronní funkce)
  insert (all) {
    var x= null;
    var ok= this.fire('onsave');                        // proveď akci před uložením dat
    this._changed= false;                 // bude true po změně nějaké položky
    Ezer.assert(ok!==false,'form.insert: test formuláře nesmí být asynchronní funkce');
    // pokud kontrola není, nebo skončila úspěšně, pokračujeme v ukládání
    if ( ok ) {
      var fields= [], table, pipe;
//       $each(this.part,function(field,id) {
      for (const id in this.part) { const field= this.part[id];
        if ( !table ) {
          table= field.table;
        }
        if ( (all || field.changed && field.changed()) && field.data && field._save ) {
          // pošli (i nezměněné položky) s ošetřenou vazbou na položku
          let vmo= field._save();
          let desc= {id:field.data.id,val:vmo.val};
          if ( vmo.mode) desc.mode= vmo.mode;
          if ( field.options.sql_pipe!=='' &&
              (pipe= field.options.sql_pipe) || (pipe= field.data.options.sql_pipe) )
            desc.pipe= pipe;
          fields.push(desc);
        }
      }
      x= {cmd:'form_insert', db:table.options.db||'', table:table.id, fields:fields};
      this._key_id= x.key_id;
    }
    return x;
  }
  insert_ (y) {
    this._key= y.key;
    return y.key;
  }
// ------------------------------------------------------------------------------------ make
//fx: Form.make (fce,"operace1:field1,field2,...;operace2:...",args...)
// kde operace mohou být
//   save -- položky budou funkcí zapsány
//   load -- data přečtěná funkcí
//   seek -- data budou předána do výběrového seznamu select
//   init -- položky budou naplněny počátečními hodnotami
//   plain -- bude odstraněno (případné) grafické zvýraznění změny hodnoty
// zavolání funkce 'fce' na serveru, která vrací data pro formulář
  make () {
//  ... save:pozn;   ... options:jmeno
// args.shift           => fce
// args.split(;)        => sekce
// sekce.split(:)       => operace : fields
// fields.split(,)      => field např.  pozn  ... nest[pozn].options.data
//                                                      save -> {_role.poznamka:'něco'} ... nic
//                                                      load -> {pozn:_role.poznamka} ... {pozn:'něco'}
//                                                      seek -> {jmeno:'concat--'} ... {jmeno:"<ul>--"}
// field.split(.)       =>       např.  firma.key ... nest[firma][key]
//                                                      save -> {firmy.id_firmy:6035} ... nic
// předá se {init:[e:tf,...],
//      save:{e:{tbl:t,fld:f,val:v},...},               -- jen změněné položky
//      put:{e:v,...},                                  -- i nezměněné elementy (bez udání tabulky a položky)
//      load:{e:{tbl:t,fld:f[,prp:p][,exp:x][,pip:p]},...}, -- kde prp je vlastnost (např. 'key'),
//                                                      -- exp výraz obsahující SQL s tbl.fld
//                                                      -- pip je identifikátor pipe
//      seek:{e:ex,...}}
// server vrátí {init:[e:tf,...],load:{e:value,...},seek:{e:value,...}
//      get:{e:value}                                   -- položka dostane hodnotu a je označena jako změněná
//   kde tf=table.table_field, e=elem_field, ex=výraz
    var args= [...arguments], fce= args.shift(), fields= args.shift();
    var x= {cmd:'form_make', fce:fce, save:{}, put:{}, load:{}, seek:{}, init:{}, plain:{},
      db:'', args:args, nargs:args.length};
    if ( fields ) {
//       fields.split(';').each(function(sekce) {
      for (let sekce of fields.split(';')) {
        var s= sekce.split(':');
//         s[1].split(',').each(function(fid) {
        for (let fid of s[1].split(',')) {
          var f= {elem:null,info:null,prop:null,tbl:null,fld:null};
          this._field_info(fid,x,f);
          switch (s[0] ) {
          case 'init':
            x.init[f.elem.id]= f.info;
            break;
          case 'put':
            x.put[f.elem.id]= f.prop ? f.elem[f.prop] : f.elem.get();
            break;
          case 'save':
            if ( f.elem._changed ) {
              x.save[f.elem.id]= {tbl:f.tbl,fld:f.fld,val:f.prop ? f.elem[f.prop] : f.elem.get()};
              if ( f.pip ) x.save[f.elem.id].pip= f.pip;
            }
            break;
          case 'plain':
            x.plain[f.elem.id]= 1;
            break;
          case 'load':
            x.load[f.elem.id]= f.exp ? {exp:f.exp} : {tbl:f.tbl,fld:f.fld};
            if ( f.prop ) x.load[f.elem.id].prp= f.prop;
            if ( f.pip ) x.load[f.elem.id].pip= f.pip;
            break;
          case 'seek':
            x.seek[f.elem.id]= f.info;
            break;
          }
        }
      }
    }
    return x;
  }
  make_ (y) {
    for (let id in y.plain)
      this.part[id].plain();
    for (let id in y.init)
      this.part[id].init();
    for (let id in y.seek) {
      Ezer.assert(this.part[id].type=='select','fráze seek v make vyžaduje element typu select');
      this.part[id].selects(y.seek[id]);
    }
    for (let id in y.load) {
      if ( y.load[id] ) this.part[id].set(y.load[id]); else this.part[id].init();
    }
    for (let id in y.get) {
      this.part[id].set(y.get[id]); this.part[id].change();
    }
    if ( y.key!==undefined )
      this._key= y.key;
    return 1;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _field_info
// fid je id elementu ve form [nebo id.prop], x je vstupně/výstupní objekt,
// e je nalezený element, fi jeho atributy table.data nebo expr, prop je předaná vlastnost e
// pokud prop='key' je v f.info vráceno table.key_id pokud má element atributy data nebo expr
//   jinak je vráceno elem.key
// do f je vrácen f.tbl=název tabulky resp. f.flt=název položky resp. f.exp=výraz resp. f.pip=výraz
  _field_info (fid,x,f) {
    var data= null, fp= fid.split('.'), pipe= null; // fid může mít formu elem_id.part
    f.elem= this.part[fp[0]];
    Ezer.assert(f.elem,fp[0]+' je neznámá položka',this);
    if ( (data= f.elem.data) ) {
      if ( f.elem.table ) {
        if ( f.elem.table.options.db ) x.db= f.elem.table.options.db;
        f.tbl= f.elem.table.id;
        f.fld= data.id;
        f.info= f.tbl+'.'+f.fld;
        // zjisti jestli ve form nebo table není požadavek na aplikaci pipe
        if ( f.elem.options.sql_pipe!=='' &&
            (pipe= f.elem.options.sql_pipe) || (pipe= data.options.sql_pipe) )
          f.pip= pipe;
      }
    }
    if ( (f.exp= f.elem.options.expr) )
      f.info= f.exp;
    // ošetření spec. vlastností
    f.prop= fp[1];
    if ( f.prop=='key' ) {  // předpokládá atributy data nebo expr
      if ( data ) {
        f.info= data.owner.id+'.'+data.owner.key_id;
      }
      else {
        f.info= fid;
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _fillx
// doplní do x seznam joins potřebných pro dotaz obsahující data
// x musí mít x.table a x.join:{}
// pokud to_map=true přidá pro server desc.map={field:id,table:id,t_options:..,m_options:..}
  _fillx (field,x,to_map) {
    var pipe, desc, expr;
    if ( field.data ) {                         // je atribut data
      desc= {id:field.id};
      if ( !x.table ) {                         // info o table, pokud již v x není
        x.table= field.table.id + (field.view ? ' AS '+field.view.id : '');
        if ( !x.key_id ) {
          x.key_id= field.table.options.key_id||'id_'+field.table.id;
        }
        x.db= field.table.options.db||'';
      }
      if ( field.view ) {                       // s odkazem přes view
        if ( field.view.options.join ) {
          var xx= x.joins[field.view.id]||false;
          if (!xx ) {
            x.joins[field.view.id]= (field.view.options.join_type||'')+' JOIN '
              + (field.view.options.expr
                ? '('+field.view.options.expr+')'
                : (field.table.options.db ? field.table.options.db+'.' : '') + field.table.id
                )
              +' AS '+field.view.id+' '+field.view.options.join;
            this._fillx2(field.view.options.join,x);      // doplní potřebná view/join
          }
        }
        desc.field= field.view.id+'.'+field.data.id;
      }
      else {                                    // s odkazem přes table
        desc.field= field.data.id;
      }
      if ( field.options && field.options.sql_pipe!==''
        && ((pipe= field.options.sql_pipe) || (pipe= field.data.options.sql_pipe)) )
        desc.pipe= pipe;
      if ( to_map && field.map_pipe ) {
        let map= field.map_pipe.map;
        desc.map= {field:field.map_pipe.field,table:map.table.id,
          t_options:map.table.options,m_options:map.options};
      }
      x.fields.push(desc);
    }
    else if ( (expr= field.options.expr) ) {
      this._fillx2(expr,x);                     // doplní potřebná view/join
      desc= {id:field.id,expr:expr};
      if ( (pipe= field.options.sql_pipe) )
        desc.pipe= pipe;
      if ( to_map && field.map_pipe ) {
        let map= field.map_pipe.map;
        desc.map= {field:field.map_pipe.field,table:map.table.id,
          t_options:map.table.options,m_options:map.options};
      }
      x.fields.push(desc);
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _fillx2
  // doplní do x seznam joins potřebných pro dotaz obsahující expr
  // x musí mít x.table a x.join:{}, formtype=='use'
  // view se poznají podle vzoru \w+\.
  _fillx2 (expr,x) {
    var re, m, view;
    re= new RegExp('(\\w+)\\.','g');
    while ( (m= re.exec(expr)) ) {
      for ( var iv in this.part ) {
        view= this.part[iv];
        if ( view.type=='view' && view.id==m[1] ) {
          if ( view.options.join ) {
            // je to view s join
            if ( !x.joins[view.id] ) {
              x.joins[view.id]= (view.options.join_type||'')+' JOIN '
              + (view.options.expr
                ? '('+view.options.expr+')'
                : (view.value.options.db ? view.value.options.db+'.' : '') + view.value.id
                )
                +' AS '+view.id+' '+view.options.join;
              this._fillx2(view.options.join,x); // přidej view použitá v join
            }
          }
          else {
            // je to řídící tabulka
            if ( !x.table ) {
              x.db= view.value.options.db||'';
              x.table= view.value.id+' AS '+view.id;
              x.view= view.id;
              if ( !x.key_id ) {
                x.key_id= view.value.key_id;
              }
            }
          }
        }
      }
    }
  }
// =======================================================================================> Form DOM
  DOM_add1 () {
    // nalezení nadřazeného bloku (vynechání var,group)
    var owner= this.DOM_owner().DOM_Block;
    // zobrazení rámečku
    this.DOM_Block= jQuery(`<div class="Form3">`)
      .css(this.coord())
      .appendTo(owner);
    this.DOM_optStyle(this.DOM_Block);
  }
  DOM_add2 () {
    if ( this.part && this.part.onclick ) {
      this.DOM_Block
        .click( el => {
          if ( !Ezer.design && (this.options.enabled || this.options.enabled===undefined) ) {
            Ezer.DOM_clearDropLists();           // schovej případné rozvinuté selecty
            Ezer.fce.touch('block',this,'click');       // informace do _touch na server
            this.fire('onclick',[],el);                 // signál do ezerscriptu
          }
        });
    }
  }
}

// =======================================================================================> Form ...
// specifické části formuláře (typicky nenesou hodnotu a události s nimi související)

// ==========================================================================================> Label
//c: Label ()
//      textové návěští
//t: Block
//s: Block
//i: Label.onclick - kliknutí na text (nebo obrázek)
class Label extends Block {
//os: Label.title - zobrazovaný text
//   options: {},  ... je v Block
//oo: Label.par - parametr pro přenos nezobrazených informací
//-
//os: Label.format - vzhled
//  ; 'c' : 'center' zarovnávat doprostřed
//  ; 'n' : display=none
//  ; 'r' : 'right' zarovnávat doprava
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    this.DOM_add1();
    this.subBlocks(desc,this.DOM_Block);
    this.DOM_add2();
  }
// ------------------------------------------------------------------------------------ set
//fm: Label.set (val)
  set (val) {
    this.DOM_set(val);
    return 1;
  }
// ------------------------------------------------------------------------------------ get
//fm: Label.get ()
  get () {
    return this.DOM_get();
  }
// ======================================================================================> Label DOM
// ------------------------------------------------------- Label.DOM_add1
  DOM_add1 () {
    // zobrazení label
    this.DOM_Block= this.DOM_Input= jQuery('<div>')
      .appendTo(this.owner.DOM_Block ? this.owner.DOM_Block : this.owner.value.DOM_Block)
      .css(this.coord({textAlign: this._fc('c') ? 'center' : this._fc('r') ? 'right' : ''}))
      .html(this.options.title||'')
      .data('ezer',this)
      .addClass('Label3')
      .attr('title',this._fc('t')?(this.options.title||''):(this.options.help||''));
  //     [0];
    this.DOM_optStyle(this.DOM_Block);
  }
// ------------------------------------------------------- Label.DOM_add2
  DOM_add2 () {
    if ( this.part && (this.part.onclick || this.part.onctrlclick)) {
      // label with onclick or onctrlclick
      jQuery(this.DOM_Block).on({
        click: el => {
          if ( el.shiftKey ) {
            if ( Ezer.options.dbg ) {
              // shift + click, pokud je &dbg=1, ukáže zdrojový text 
              return dbg_onshiftclick(this);       
            }
            else if ( this.part.onshiftclick ) {
              // shift + click se neuplatní, pokud je &dbg=1
              Ezer.fce.touch('block',this,'shiftclick');  
              this.fire('onshiftclick',[],el);
            }
          }
          else if ( !Ezer.design && (this.options.enabled || this.options.enabled===undefined) ) {
            // ctrl + click
            if ( el.ctrlKey && this.part.onctrlclick ) {
              Ezer.fce.touch('block',this,'ctrlclick');   
              this.fire('onctrlclick',[],el);
            }
            else {
              // onclick se zavolá, pokud není on{x}click a stisknuto {x}
              Ezer.fce.touch('block',this,'click');       
              this.fire('onclick',[],el);
            }
          }
        }
      });
    }
    else if ( Ezer.options.dbg ) {
      jQuery(this.DOM_Block).on({
        click: el => {
          if ( el.shiftKey ) return dbg_onshiftclick(this);       // label (dbg only)
        }
      });
    }
  }
// ------------------------------------------------------- DOM get
   DOM_get () {
    return jQuery(this.DOM_Block).html();
  }
// ------------------------------------------------------- DOM set
  DOM_set (str) {
    jQuery(this.DOM_Block).html(str);
    if ( this._fc('t') )
      jQuery(this.DOM_Block).prop('title',str);
    // add click event for nested elements <a href='ezer://....'>
    // (see similar Ezer.App.start_href_modify)
    jQuery('a',this.DOM_Block).each((i,el) => {
      if ( el.href && el.href.substr(0,7)=='ezer://' ) {
        jQuery(el).click( ev => {
          if ( Ezer.dragged ) return false;
          Ezer.fce.href(ev.target.href.substr(7));
          return false;
        });
      }
    });
  }
// --------------------------------------------------------- DOM enabled
// toggle enabled/disabled by parameter or this.options.enabled
  DOM_enabled (on) {
    if ( this.DOM_Block ) {
      if (on!==false && this.options.enabled) {
        jQuery(this.DOM_Block).removeClass('disabled3');
      }
      else {
        jQuery(this.DOM_Block).addClass('disabled3');
      }
    }
  }
}

// ======================================================================================> LabelDrop
//c: LabelDrop ()
//      prvek pro kontrolovaný upload souborů na server, kliknutí přeruší přenos
//t: Block,Label
//s: Block
//i: LabelDrop.ondrop - funkce zavolaná po dokončení vložení souboru
//i: LabelDrop.onload - funkce zavolaná po dokončení přenosu na server
class LabelDrop extends Label {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
//os: LabelDrop.title - text zobrazovaný v záhlaví DropBoxu
    this.cloud= null;           // S:,H: pro souborový systém na serveru nebo G: pro Google Disk
    this.folder= '/';           // relativní cesta na disku vzhledem k 'files/{root}' nebo ID složky cloudu
    this.mask= '';              // regulární výraz pro omezení jmen souborů (pro preg_match)
    this.par= {utf8:0,list:1,href:null,mime:null}; // defaultní hodnoty par - viz init
    this.continuation= null;    // bod pokračování pro fi
  }
// ------------------------------------------------------------------------------- LabelDrop.init
//fm: LabelDrop.init (folder[,cloud=S:[,mask='',par={utf8:0,list:1,href:u-folder,mime:-}]])
// inicializace oblasti pro drop souborů, definice cesty pro soubory
// (začínající jménem a končící lomítkem a relativní k $ezer_root)
// NEBO definice sloužky a cloudu (zatím jen S: pro docs/{root}, H: pro ../files/{root} na serveru
//   a G: pro Google Disk a U: pro uživatelské nastavení)
// nepovinná maska se používá pro výběr a zobrazení v lsdir - pokud je ve výrazu skupina (),
//   použije se v lsdir pro zobrazení souboru
// nepovinné par - utf8=1 povolí UTF8 znaky ve jménech souborů, list=0 potlačí zobrazení souborů,
//  href=odkaz na složku se soubory - použije se jen pro U:, mime=img povolí metodu resample
  init (folder,cloud,mask,par) {
    this.cloud= cloud||'S:';            // S: je pro soubory viditelné přes http
    this.folder= folder;
    this.mask= mask||'';
    // hodnoty par
    this.par.utf8= par && par.utf8!==undefined ? par.utf8 : 0;
    this.par.list= par && par.list!==undefined ? par.list : 1;
    this.par.href= par && par.href!==undefined ? par.href : null;
    this.par.mime= par && par.mime!==undefined ? par.mime : null;
    // inicializace elementu
    this.DOM_init();
    return 1;
  }
// -------------------------------------------------------------------------------- LabelDrop.set
//fm: LabelDrop.set (lst)
// do oblasti přidá jména souborů podle parametru
// lst může být string ve formě seznamu jmen oddělených čárkou,
// za jménem souboru může následovat po dvojtečce status (např. délka);
// nebo pole objektů obsahujících složky title a filesize
// např. pole interních representací dokumentů Google Disk
//a: lst - seznam jmen souborů
  set (lst) {
    if ( lst && typeof(lst)=='string' ) {
//       lst.split(',').each(function(lst_i) {
      for (let lst_i of lst.split(',')) {
        var alst_i= lst_i.split(':');
        this.DOM_addFile({name:alst_i[0],status:alst_i[1]||'ok'});
      }
    }
    else if ( lst && Array.isArray(lst) ) {
//       lst.each(function(f) {
      for (let f of lst) {
        if ( this.cloud=='G:' )
          this.DOM_addFile_Disk(f);
        else if ( ['S:','H:','U:'].includes(this.cloud) )
          this.DOM_addFile({name:f.name,title:f.title,status:f.size});
        else
          Ezer.error("'"+this.cloud+"' není podporovaný cloud pro upload");
      }
    }
    return 1;
  }
// -------------------------------------------------------------------------------- LabelDrop.get
//fm: LabelDrop.get ()
// vrátí seznam jmen souborů oddělených čárkou (po dvojtečce je vždy status)
  get () {
    var lst= '', del= '';
//     this.DOM_files.each(function(f) {
    for (let f of this.DOM_files) {
      lst+= del+(this.cloud=='G:' 
        ? f.title+':'+(f.fileSize||'doc') 
        : (f.newname ? f.newname : f.name) + ':'+f.status);
      del= ',';
    }
    return lst;
  }
// --------------------------------------------------------------------------- LabelDrop.resample
//fi: LabelDrop.resample ()
// upraví velikost přečteného obrázku před odesláním na server, může být voláno pouze z ondrop
  resample (f,max_width,max_height) {
    if ( this.par.mime!='img' ) Ezer.error("metoda resample vyžaduje init(...{mime:img})");
    if ( f.type.substr(0,5)=='image' ) {
      Resample(f.text,f.type,max_width,max_height, function(data64){
        f.data= dataURItoBlob(data64); 
        this._resample(f.data.size);
      }.bind(this));
    }
    return this;
  }
  _resample (size) {
    this.continuation.stack[++this.continuation.top]= size;
    this.continuation.eval.apply(this.continuation,[0,1]);
  }
// ------------------------------------------------------------------------------ LabelDrop.lsdir
//fi: LabelDrop.lsdir ([subdir='',mask=''])
// zobrazí obsah složky, pro server může být dán parametr=jméno podsložky pro interaktivní změnu
// pro U: musí být nastavené Ezer.options.path_files_u
  lsdir (subdir) {
    this.DOM_init();
    if ( this.cloud=='G:' ) {
      Ezer.Google.authorize();
      if ( Ezer.Google.authorized ) {
        Ezer.Google.files_list(
          "'"+this.folder+"' in parents and trashed=false",this._lsdir.bind(this));
        return this;
      }
      else
        return null;
    }
    else { // složka na serveru
      var path_files= 
        this.cloud=='S:' ? Ezer.options.path_files_s : (
        this.cloud=='U:' ? Ezer.options.path_files_u : Ezer.options.path_files_h);
      if ( subdir )
        this.folder+= this.folder + (this.folder.substr(-1)=='/' ? '' : '/') + subdir;
      this.ask({cmd:'lsdir',base:path_files,folder:this.folder,mask:this.mask},'_lsdir');
      return this;
    }
  }
  _lsdir (xs) {
    if ( this.cloud!='G:' ) {
      xs= xs.files;
    }
    xs.sort(function(a,b){ return a.title>b.title ? 1 : a.title<b.title ? -1 : 0; });
    this.set(xs);
    this.continuation.stack[++this.continuation.top]= 1;
    this.continuation.eval.apply(this.continuation,[0,1]);
  }
// ------------------------------------------------------------------------------ LabelDrop.isdir
//fi: LabelDrop.isdir (name)
// pokud v kořenu definovaném init existuje takto pojmenovaná složka vrátí jeji FileId (pro GD)
// nebo 1; pokud neexistuje vrátí 0
  isdir (name) {
    if ( this.cloud=='G:' ) {
      Ezer.Google.authorize();
      if ( Ezer.Google.authorized ) {
        Ezer.Google.files_list("title='"+name+"' and '"+this.folder
          +"' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false",
          this._isdir.bind(this));
        return this;
      }
      else
        return null;
    }
    else { // složka S nebo H na serveru
      var path_files= 
        this.cloud=='S:' ? Ezer.options.path_files_s : (
        this.cloud=='U:' ? Ezer.options.path_files_u : Ezer.options.path_files_h);
      this.ask({cmd:'isdir',base:path_files,folder:this.folder+name},'_isdir');
      return this;
    }
  }
  _isdir (xs) {
    this.continuation.stack[++this.continuation.top]=
      ['S:','H:','U:'].includes(this.cloud) ? xs.ok : (xs.length>0 ? xs[0].id : 0);
    this.continuation.eval.apply(this.continuation,[0,1]);
  }
// ------------------------------------------------------------------------------ LabelDrop.mkdir
//fi: LabelDrop.mkdir (name)
// v kořenu definovaném init vytvoří takto pojmenovanou složku a vrátí její FileId resp. 1
  mkdir (name) {
    if ( this.cloud=='G:' ) {
      Ezer.Google.authorize();
      if ( Ezer.Google.authorized ) {
        this._name= name;
        Ezer.Google.files_list("title='"+name+"' and '"+this.folder
          +"' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false",
          this._is_mkdir.bind(this));
        return this;
      }
      else
        return null;
    }
    else { // složka S nebo H na serveru
      var path_files= this.cloud=='S:' ? Ezer.options.path_files_s : Ezer.options.path_files_h;
      this.ask({cmd:'mkdir',base:path_files,folder:this.folder,subfolder:name},'_mkdir');
      return this;
    }
  }
  _is_mkdir (xs) {    // jen pro Google Disk - po zjištění, zda name existuje
    if ( xs.length>0 ) {        // složka už existuje
      this._mkdir(xs[0]);
    }
    else {                      // neexistuje
      var request= gapi.client.drive.files.insert({
        resource: {
          title: this._name,
          parents: [{id:this.folder}],
          mimeType: 'application/vnd.google-apps.folder'
      }});
      request.execute(this._mkdir.bind(this));
    }
  }
  _mkdir (resp) {
    var folder= this.cloud=='G:' ? resp.id : resp.folder;
    this.continuation.stack[++this.continuation.top]= folder;
    this.init(folder,this.cloud);       // přepnutí na nově vytvořenou složku
    this.continuation.eval.apply(this.continuation,[0,1]);
  }
// ==================================================================================> LabelDrop DOM
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM initialize
  DOM_initialize () {
    this.DOM_files= [];
    this.DOM_BlockRows= null;
  }
// --------------------------------------------------------- DOM add1
  DOM_add1 () {
    // zobrazení prázdného label.drop
    var h= this.options.title ? 15 : 0;
    this.DOM_Block= jQuery(
      `<div class="LabelDrop3">
         <div style="height:${h}px">${this.options.title||''}</div>
         <div style="height:${this._h-h}px">
           <table cellspacing="0">
             <tbody></tbody>
           </table>
         </div>
       </div>`)
      .css(this.coord())
      .appendTo(this.owner.DOM_Block||this.owner.value.DOM_Block)
      .data('ezer',this)
      .on({
        dragover: evt => {
          evt.preventDefault();
          this.DOM_Block.addClass('LabelDropHover3');
        },
        dragleave: evt => {
          evt.preventDefault();
          this.DOM_Block.removeClass('LabelDropHover3');
        },
        drop: evt => {
          evt.stopPropagation();
          evt.preventDefault();
          this.DOM_Block.removeClass('LabelDropHover3');
          for (var i= 0; i<evt.originalEvent.dataTransfer.files.length; i++) {
            var f= evt.originalEvent.dataTransfer.files[i];
            if ( this.par.list ) 
              this.DOM_addFile(f);
            var r= new FileReader();
            r.Ezer= {file:f,folder:this.folder,bind:this};
            if ( this.cloud=='G:' ) { 
              // Google Disk
              if ( this.par.list ) 
                f.td2.html("načítání");
              r.readAsBinaryString(f);
              r.onload= function(e) {
                var tf= this.Ezer.file;
                tf.folder= this.Ezer.folder;
                tf.data= btoa(r.result);
                this.Ezer.bind.DOM_ondrop_Disk(tf);
              };
            }
            else if ( ['S:','H:','U:'].includes(this.cloud) ) { 
              // server file system
              if ( this.par.mime=='img') {
                r.onload= function(e) {
                  var tf= this.Ezer.file;
//                  tf.data= new Blob([e.target.result],{type:tf.type});
                  tf.text= 'data:'+tf.type+';base64,'+btoa(e.target.result);
                  tf.orig= 'drop';
                  this.Ezer.bind.DOM_ondrop(tf);
                };
                r.readAsBinaryString(f);
//                r.readAsArrayBuffer(f);
              }
              else {
                r.onload= function(e) {
                  var tf= this.Ezer.file;
                  tf.data= new Blob([e.target.result],{type:tf.type});
                  tf.orig= 'drop';
                  this.Ezer.bind.DOM_ondrop(tf);
                };
                r.readAsArrayBuffer(f);
              }
            }
            else
              Ezer.error("'"+this.cloud+"' není podporovaný cloud pro upload");
          }
        }
      });
    this.DOM_BlockRows= this.DOM_Block.find("tbody");
    this.DOM_optStyle(this.DOM_Block);
    if ( this.options.help )
      this.DOM_Block.attr('title',this.options.help);
  }
  DOM_add2 () {
  }
// ------------------------------------------------------------------------------ DOM init
// inicializace dat a oblasti pro drop - set(0) ji deaktivuje, set(1) aktivuje
  DOM_init () {
    this.DOM_files= [];
    this.DOM_BlockRows.empty();
  }
// ------------------------------------------------------------------------------ DOM addFile
// přidá řádek pro informaci o vkládaném souboru {name,title,status}
// obohatí f o td1,td2 a volitelně td3
  DOM_addFile (f) {
    if ( this.par.list ) {
      var td3w= 0; // nebo volitelně šířka třetího informačního sloupce
      var td2w= 60;
      var td1w= this._w - (td2w + td3w + (td3w?16:14) + 16);
      var tr= jQuery(`<tr>`).appendTo(this.DOM_BlockRows);
      f.td1= jQuery(`<td style="width:${td1w}px">${f.status ? this.DOM_href(f) : f.name}</td>`)
        .appendTo(tr);
      f.td2= jQuery(`<td style="width:${td2w}px;text-align:right">${f.status||"čekám"}</td>`)
        .appendTo(tr);
      if ( td3w )
        f.td3= jQuery(`<td style="width:60px">`)
          .appendTo(tr);
    }
    f.newname= '';
    this.DOM_files.push(f);
  }
// ------------------------------------------------------- DOM addFile_Disk
// přidá řádek pro informaci o souboru vloženém na Google Disk
// obohatí f o td1,td2 a volitelně td3
  DOM_addFile_Disk (f) {
    if ( this.par.list ) {
      var td3w= 0; // nebo volitelně šířka třetího informačního sloupce
      var td2w= 60;
      var td1w= this._w - (td2w + td3w + (td3w?16:14) + 16);
      var tr= jQuery(`<tr>`).appendTo(this.DOM_BlockRows);
      f.td1= jQuery(`<td style="width:${td1w}px">${this.DOM_href_Disk(f)}</td>`)
        .appendTo(tr);
      f.td2= jQuery(`<td style="width:${td2w}px;text-align:right">${f.fileSize||(
          f.mimeType=='application/vnd.google-apps.folder' ? 'složka' : 'dokument')}</td>`)
        .appendTo(tr);
      if ( td3w )
        f.td3= jQuery(`<td style="width:60px">`)
          .appendTo(tr);
    }
    this.DOM_files.push(f);
  }
// ------------------------------------------------------------------------------ DOM href
// přidá odkaz na soubor s případným kontextovým menu, pokud je přítomna procedura onmenu
// pro S:,U: je f.name vždy definováno, f.title je vynecháno
// pro H: je f.name vždy definováno, f.title jen pokud bylo vytvořeno pomocí this.mask v lsdir
  DOM_href (f) {
    var m='', href, title;
    if ( ['S:','U:'].includes(this.cloud) ) {   // úložiště viditelné protokolem http
      // kontextové menu, pokud je přítomna procedura onremove
      var obj, mopt= '';
      if ( this.part && (obj= this.part.onmenu) ) {
        if ( this.options.par && this.options.par.contextmenu ) {
          var mopts= this.options.par.contextmenu.split(';');
  //         mopts.each(function(mo){
          for (let mo of mopts){
            mopt+= ",['"+mo+"',function(el){obj.callProc('onmenu',['"+mo+"','"+f.name+"',''])}]";
          }
        }
        m= " oncontextmenu=\"var event= arguments[0];event.stopPropagation();"
        + "var obj=[];if(Ezer.run_name('"+this.self()+"',null,obj)==1){"
        + "obj=obj[0].value||obj[0];Ezer.fce.contextmenu(["
          + "['přejmenovat',function(el){obj.callProc('onmenu',['rename','"+f.name+"',''])}],"
          + "['vyjmout',function(el){obj.callProc('onmenu',['remove','"+f.name+"',''])}],"
          + "['vyjmout vše',function(el){obj.callProc('onmenu',['remove-all','',''])}]"
          + mopt
        + "],arguments[0])};return false;\"";
      }
      let folder= this.folder;
      if ( folder.substr(-1)!='/' )
        folder+= '/';
      if ( this.cloud=='S:' ) {
        href= "<a target='docs' href='"+(Ezer.options.path_files_href||'')+folder+f.name+"'"+m+">"
          + f.name+"</a>";
      }
      else if ( this.cloud=='U:' && this.par.href ) {
        href= "<a target='docs' href='"+this.par.href+folder+f.name+"'"+m+">"
          + f.name+"</a>";
      }
      else {
        href= "<span"+m+">"+ f.name+"</span>";
      }
    }
    if ( this.cloud=='H:' ) {   // úložiště neviditelné protokolem http: ../files/{root}
      if ( f.name[0]=='[' ) {
        // pokud je to složka
        m= " onclick=\"var obj=[];if(Ezer.run_name('"+this.self()+"',null,obj)==1){"
        + "obj=obj[0].value||obj[0]; obj.lsdir('"+Ezer.fce.replace(f.name,'\\[','',']','')+"');}\"";
        href= "<a style='cursor:pointer' "+m+">"+f.name+"</a>";
      }
      else if ( this.part && (obj= this.part.onmenu) ) {
        title= f.title||f.name;
        var ref= Ezer.version + "/server/file_send.php?name="
            + this.folder + (this.folder.substr(-1)=='/' ? '' : '/') + f.name
  //           + ( f.title ? "&title=" + f.title : '' )
            + "&title=" + title
            + "&root=" + Ezer.root;
        // pokud existuje script onmenu
        m= " oncontextmenu=\"var obj=[];if(Ezer.run_name('"+this.self()+"',null,obj)==1){"
        + "obj=obj[0].value||obj[0];Ezer.fce.contextmenu(["
          + "['prohlížet',function(el){obj.callProc('onmenu',['viewer','"+title+"','"+ref+"'])}],"
          + "['vyjmout', function(el){obj.callProc('onmenu',['remove','"+title+"','"+f.name+"'])}],"
        + "],arguments[0])};return false;\"";
        href= "<a style='cursor:pointer' " + m + ">" + title + "</a>";
      }
      else {
        href= title;
      }
    }
    return href;
  }
// ------------------------------------------------------------------------------ DOM_href_Disk
// přidá odkaz na soubor na Google Disk s kontextovým menu, pokud je přítomna procedura onmenu
  DOM_href_Disk (f) {
    var fileId, href, m= '';
    href= f.fileSize ? f.webContentLink : (
      f.exportLinks && f.exportLinks['application/pdf'] ? f.exportLinks['application/pdf'] : null);
    href= href ? " href='"+href+"'" : '';
    fileId= f.selfLink.split('/');
    fileId= fileId[fileId.length-1];
    if ( this.part && (obj= this.part.onmenu) ) {
      m= " oncontextmenu=\"var obj=[];if(Ezer.run_name('"+this.self()+"',null,obj)==1){"
      + "obj=obj[0].value||obj[0];Ezer.fce.contextmenu(["
        + "['zobrazit',function(el){obj.callProc('onmenu',['viewer','"+f.title+"','"+f.alternateLink+"'])}],"
        + "['vyjmout',function(el){obj.callProc('onmenu',['remove','"+f.title+"','"+fileId+"'])}]"
      + "],arguments[0])};return false;\"";
    }
    return "<a target='docs'"+href+m+">"+f.title+"</a>";
  }
// ------------------------------------------------------------------------------- DOM ondrop_Disk
// zavolá proc ondrop, pokud existuje - vrátí-li 0 bude upload zrušen,
// jinak jej provede s předaným jménem (možnost odstranit diakritiku)
// pokud proc ondrop neexistuje, zahájí upload na Google Disk
  DOM_ondrop_Disk (f) {
    // zavolání funkce ondrop ex-li
    if ( this.part && (obj= this.part.ondrop) ) {
      var continuation= {fce:this.DOM_upload_Disk,args:[f],stack:true,obj:this};
      new Eval(obj.code,this,[f],'ondrop',continuation,false,obj,obj.desc.nvar);
    }
    else {
      // nebo přímo zavolat upload
      this.DOM_upload_Disk(f,1);
    }
  }
// ------------------------------------------------------------------------------- DOM_upload_Disk
// konec vkládání a případný upload na Google Disk
  DOM_upload_Disk (f,do_upload) {
    if ( do_upload ) {
      // ==> . upload G:
      if ( this.par.list ) 
        f.td2.html("přenášení");
      // konstanty boundery, delimiter, close_delim, CHUNK nahrazeny var kvůli IE
      var boundary = '-------314159265358979323846';
      var delimiter = "\r\n--" + boundary + "\r\n";
      var close_delim = "\r\n--" + boundary + "--";
      var contentType = f.type || 'application/octet-stream';
      var metadata= {
        title: f.name,
        mimeType: contentType,
        parents:[{id:f.folder}]
      };
      var multipartRequestBody= delimiter + 'Content-Type: application/json\r\n\r\n'
        + JSON.stringify(metadata) + delimiter + 'Content-Type: ' + contentType + '\r\n'
        + 'Content-Transfer-Encoding: base64\r\n' + '\r\n' + f.data + close_delim;
      var request= gapi.client.request({
          path: '/upload/drive/v2/files',
          method: 'POST',
          params: {'uploadType': 'multipart'},
          headers: {'Content-Type': `multipart/mixed; boundary="${boundary}"`},
          body: multipartRequestBody});
      var end = function(gf) {
        //console.log(f)
        var size= gf.fileSize||'doc';
        if ( this.par.list ) {
          f.td1.html(this.DOM_href_Disk(gf));
          f.td2.html(size);
        }
        if ( this.part && (obj= this.part.onload) ) {
          // zavolání funkce onload ex-li s kopií f - po dokončení přenosu
          var ff= {name:gf.title, folder:'', size:size, status:1};
          new Eval(obj.code,this,[ff],'onload',null,false,obj,obj.desc.nvar);
        }
      }.bind(this);
      request.execute(end);
    }
    else if ( this.par.list ) {
      // zrušení progress
      f.td2.html("zrušeno");
    }
  }
// -------------------------------------------------------- DOM ondrop
// zavolá proc ondrop, pokud existuje - vrátí-li 0 bude upload zrušen,
// jinak jej provede s předaným jménem (možnost odstranit diakritiku)
// pokud proc ondrop neexistuje, zahájí upload
  DOM_ondrop (f) {
    // zavolání funkce ondrop ex-li
    let obj;
    if ( this.part && (obj= this.part.ondrop) ) {
      var continuation= {fce:this.DOM_upload,args:[f],stack:true,obj:this};
      new Eval(obj.code,this,[f],'ondrop',continuation,false,obj,obj.desc.nvar);
    }
    else {
      // nebo přímo zavolat upload
      this.DOM_upload(f,1);
    }
  }
// -------------------------------------------------------- DOM upload
// konec vkládání a případný upload
  DOM_upload (f,do_upload) {
    if ( do_upload ) {
      f.newname= do_upload==1 ? '' : do_upload;
      // upload rozdělený na části 
      var CHUNK= 100000; //512 * 1024; // 0.5MB chunk sizes.
      if (bar) bar.attr('value',0);
      if ( this.par.mime=='img' && f.data==undefined ) {
        f.data= dataURItoBlob(f.text); 
      }
      var max= Math.ceil(f.data.size/CHUNK);
      if ( this.par.list ) {
        // s referováním do <progrress>
        f.td2.html("");
        var bar= jQuery(`<progress max="${max}" value="0" title="kliknutí přeruší přenos">`)
          .click( evt => {
            f.cancel= true;
          })
          .appendTo(f.td2);
      }
      Ezer.App._ajax(1);
      this.DOM_upload_chunk(1,max,CHUNK,f,bar);
    }
    else if ( this.par.list ) {
      // zrušení progress
      f.td2.html("zrušeno");
    }
  }
// --------------------------------------------------- DOM upload_chunk
// konec vkládání a případný upload s volání funkce onload po ukončení přesunu na server
  DOM_upload_chunk (n,max,CHUNK,f,bar) {
    if ( f.cancel ) {
      if ( this.par.list )
        f.td2.html("přerušeno");
      return 0;
    }
    // ==> . upload S:,H:
    var data= f.data.slice((n-1)*CHUNK,n*CHUNK);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', Ezer.version+'/server/file_send.php', true);
    xhr.setRequestHeader("EZER-FILE-NAME", encodeURIComponent(f.newname ? f.newname : f.name));
    xhr.setRequestHeader("EZER-FILE-CHUNK", n);
    xhr.setRequestHeader("EZER-FILE-CHUNKS", max);
    if ( this.cloud=='S:' && !Ezer.options.path_files_s )   // S: relativní (zpětná kompatibilita)
      xhr.setRequestHeader("EZER-FILE-RELPATH", this.folder);
    else {                                                  // S:,H: absolutní
      let path=
        (this.cloud=='S:' ? Ezer.options.path_files_s : (
         this.cloud=='H:' ? Ezer.options.path_files_h : (
         this.cloud=='U:' ? Ezer.options.path_files_u : '::'))) + this.folder;
      path= encodeURI(path);
      xhr.setRequestHeader("EZER-FILE-ABSPATH",path);
    }
    if ( this.par.utf8 ) {
      xhr.setRequestHeader("EZER-FILE-NAME-UTF-8",1);
    }
    xhr.onload = function(e) {
      if (e.target.status == 200) {
        // vraci pole:name|chunk/chunks|path|strlen
  //                                                         Ezer.trace('*',e.target.response);
        var resp= e.target.response.split('|');
        if ( n < max ) {
          var value= Math.round(100*(n*CHUNK/f.size));
          if (bar) bar.attr('value',value);
          this.DOM_upload_chunk(n+1,max,CHUNK,f,bar);
        }
        else {
          Ezer.App._ajax(-1);
//          if (bar) bar.attr('value',100);
          // záměna jména souboru za vrácené, obohacení o odkaz a délku
          f.status= resp[5] ? "error" : resp[6] ? "warning" : resp[3];
          if ( this.par.list ) {
            f.td2.html(f.status);
            f.td1.html(this.DOM_href({name:resp[0]}));
          }
          var obj, ff= {name:resp[0], folder:this.folder, size:f.size, status:f.status};
          // kontrola korektnosti
          if ( resp[5] ) Ezer.error(resp[5],'S',this);
          else if ( resp[6] ) Ezer.fce.warning(resp[6]);
          else if ( this.part && (obj= this.part.onload) ) {
            // zavolání funkce onload ex-li s kopií f
            new Eval(obj.code,this,[ff],'onload',null,false,obj,obj.desc.nvar);
          }
          // zavolání případného this.onUploaded ... využívá se v pluginu ezer v CKEditor
          if ( this.onUploaded && typeof this.onUploaded === "function" ) {
            this.onUploaded(ff);
          }
        }
      }
    }.bind(this);
    xhr.send(data);
    return 1;
  }
}

// =======================================================================================> LabelMap
//c: LabelMap ()
//      prvek pro práci s GoogleMaps a s geo-objekty
//t: Block,Label
//s: Block
class LabelMap extends Label {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
//   options: {},
    this.continuation= null;   // bod pokračování pro geocode,...
    this.geocoder= null;       // Google objekt
    this.geo= null;            // běžný gobjekt pro asynchronní metody
    this.map= null;            // Google mapa
    // prvky v mapě
    this.clustering= false,    // sdružovat značky (nastavuje se v init)
    this.poly= null;           // seznam aktuálních polygonů
    this.mark= null;           // pole aktuálních značek indexovaných předaným id
    this.zoom= null;           // aktivní výřez mapy (LatLngBounds)
    this.rect= null;           // zobrazený obdélník (Polygon)
    // pro metody
    this.geocode_counter= 1;   // geocode
  }
// ---------------------------------------------------------------------------------------- init
//fm: LabelMap.init ([TERRAIN|ROADMAP][,options])
// inicializace oblasti se zobrazením mapy ČR
// pokud mapy google nejsou dostupné vrací 0
  init (type,options) {
    this.map= null;
    this.clustering= options && options.clustering==1 ? true : false;
    var ok= typeof google!="undefined" && google.maps ? 1 : 0;
    if ( ok ) {
      var stredCR= new google.maps.LatLng(49.8, 15.6);
      var map_id= google.maps.MapTypeId[type||'TERRAIN'];
      var g_options= {zoom:7, center:stredCR, mapTypeId:map_id,
        mapTypeControlOptions:{position: google.maps.ControlPosition.RIGHT_BOTTOM},
        zoomControlOptions:{position: google.maps.ControlPosition.LEFT_BOTTOM}
      };
      if ( options )
        Object.assign(g_options,options);
      this.map= new google.maps.Map(this.DOM_Block[0],g_options);
    }
    this.poly= null;
    this.rect= null;
    this.mark= {};
    return ok;
  }
// ---------------------------------------------------------------------------------------- dump
//fm: LabelMap.dump ()
// vytvoří objekt obsahující informaci o počtu značek, polygonů, ...
  dump () {
    let ans= {};
    if ( this.map ) {
      var visible= 0;
      var viewPort= this.map ? this.map.getBounds() : null;
      if ( viewPort ) {
        for (var i in this.mark) {
          var point= this.mark[i];
          if ( viewPort.contains(point.getPosition()) ) {
            visible++;
          }
        }
      }
      ans= {
        marks: this.mark ? Object.keys(this.mark).length : 0,
        visible: visible,
        polys: this.poly ? this.poly.length : 0,
        bounds: viewPort ? this.get_bounds() : ",;,"
      };
    }
    return ans;
  }
// ----------------------------------------------------------------------------------------- get
//fm: LabelMap.get (op[,id])
// get('count') vrátí počet zobrazených značek
// get('ids') vrátí seznam zobrazených značek
// get('id') vrátí značku s daným id nebo null
  get (op,id) {
    let ret= '', del= '';
    if ( this.map ) {
      switch (op) {
      case 'count':
        ret= 0;
        for (let i in this.mark) {
          ret++;
        }
        break;
      case 'titles':
        for (let i in this.mark) {
          ret+= del+this.mark[i].title;
          del= ',';
        }
        break;
      case 'ids':
        for (let i in this.mark) {
          if ( this.mark[i].id && this.mark[i].id!==undefined ) {
            ret+= del+this.mark[i].id;
            del= ',';
          }
        }
        break;
      case 'id':
        ret= null;
        for (let i in this.mark) {
          if ( this.mark[i].id==id ) {
            ret= this.mark[i];
            break;
          }
        }
        break;
      }
    }
    return ret;
  }
// ----------------------------------------------------------------------------------------- set
//fm: LabelMap.set (gobject)
// zobrazí v mapě informace předané objektem geo
//   set({mark:'mark*'[,ezer]...) - doplní do mapy značky s informacemi podle popisu
//                                  k vytvořeným značkám přidá případně objekt ezer
//   set({poly:'bod+',...})    - doplní do mapy polygon podle seznamu bodů oddělovaných středníky
//   set({zoom:'bod;bod',...}) - zvětší mapu aby byl právě vidět (nezobrazený) obdélník SW;NE
//   set({rect:'bod;bod',...}) - zobrazí ohraničující obdélník SW;NE
// prázdný řetezec předaný pro mark, zoom, rect, poly se interpretuje jako žádost o smazání
// mark = id,lat,ltd[,title[,icon]]
// id   = nenulový klíč
// bod  = lat,ltd
// icon = CIRCLE[,scale:1-10][,ontop:1]|cesta k bitmapě
  set (geo) {
    var ret= 1, mark;
    if ( this.map ) {
      // -------------------------------------------- MARK
      if ( geo.mark == '' && this.mark ) {                // zruš všechny značky
  //       Object.each(this.mark,function(m){m.setMap(null);});
        for (let im in this.mark) { this.mark[im].setMap(null); }
        this.mark= {};
      }
      else if ( geo.mark ) {                              // přidej nové značky
        ret= null; // vrátíme vytvořený marker, pokud se to povede
        Ezer.assert(geo && typeof(geo.mark)=='string',
          "LabelMap.set má chybný argument mark "+typeof(geo.mark)+" místo string");
        var label= this;
        geo.mark.split(';').map(function(xy) {
          var p= xy.split(',');
          var id= p[0];
          var ll= new google.maps.LatLng(p[1],p[2]);
          var map_opts= {position:ll,map:this.map};
          if ( p[3] ) map_opts.title= p[3];               // přidá label
          if ( p[4] ) {
            // přidá ikonu - buď bitmapa, nebo CIRCLE a následuje barva fill a barva border
            if ( p[4]=='CIRCLE' ) {
              map_opts.icon= {
                path: google.maps.SymbolPath.CIRCLE, scale: 7,
                fillColor: p[5], fillOpacity: 0.3, strokeColor: p[6], strokeWeight: 1
              };
              if ( p[7] )
                map_opts.zIndex= google.maps.Marker.MAX_ZINDEX + 1;
              if ( p[8] )
                map_opts.icon.scale= Number.parseInt(p[8]);
            }
            else {
              map_opts.icon= p[4];
            }
          }
          if ( geo.ezer ) map_opts.ezer= geo.ezer;        // přidá hodnoty složky ezer
          ret= mark= new google.maps.Marker(map_opts);    // vrací se vytvořený marker
          if ( this.mark[id] ) {
            this.mark[id].setMap(null);                   // případný marker se stejným id vymaž
          }
          this.mark[id]= mark;
          mark.id= id;
          // pokud existuje obsluha onmarkclick, přidej listener
          if ( this.part && this.part.onmarkclick ) {
            google.maps.event.addListener(mark,'click', function() {
              if ( typeof label.part.onmarkclick === 'function' )
                label.part.onmarkclick(this);
              else
                label._call(0,'onmarkclick',this);
            });
          }
        }.bind(this));
        // volitelné sdružování značek (marker clustering)
        // https://developers.google.com/maps/documentation/javascript/marker-clustering
        if ( this.clustering ) {
          let gridSize= 40;
          new MarkerClusterer(this.map, this.mark, {imagePath:
              'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m',
            gridSize:gridSize
          });
        }
      }
      // -------------------------------------------- ZOOM
      if ( geo.zoom == '' && this.zoom ) {                // zruš ohraničení
        this.zoom= null;
      }
      else if ( geo.zoom ) {                              // definuj ohraničení
        var ps= geo.zoom.split(';');
        var _sw, _ne;
        var SW= ps[0].split(','), NE= ps[1].split(',');
        _sw= new google.maps.LatLng(SW[0],SW[1]);
        _ne= new google.maps.LatLng(NE[0],NE[1]);
        this.zoom= new google.maps.LatLngBounds(_sw,_ne);
        this.map.fitBounds(this.zoom);
      }
      // -------------------------------------------- RECT
      if ( geo.rect == '' && this.rect ) {                // zruš obdélník
        this.rect.setMap(null);
        this.rect= null;
      }
      else if ( geo.rect ) {                              // zobraz obdélník
        if ( this.rect ) this.rect.setMap(null);          // zruš napřed starý
        let paths = [],
            ps= geo.rect.split(';'),
            SW= ps[0].split(','), NE= ps[1].split(',');
        paths.push(new google.maps.LatLng(SW[0],SW[1]));
        paths.push(new google.maps.LatLng(SW[0],NE[1]));
        paths.push(new google.maps.LatLng(NE[0],NE[1]));
        paths.push(new google.maps.LatLng(NE[0],SW[1]));
        this.rect= new google.maps.Polygon({
          paths: paths, fillOpacity: 0, strokeWeight: 1, strokeColor: 'grey'
        });
        this.rect.setMap(this.map);
      }
      // -------------------------------------------- POLY
      if ( geo.poly == '' && this.poly ) {                // zruš polygon
        this.poly.setMap(null);
        this.poly= null;
      }
      else if ( geo.poly ) {                              // zobraz polygon
        if ( this.poly ) this.poly.setMap(null);          // zruš napřed starý
        let paths = [];
        geo.poly.split(';').map(function(xy) {
          let p= xy.split(',');
          paths.push(new google.maps.LatLng(p[0],p[1]));
        });
        this.poly= new google.maps.Polygon({
          paths: paths, fillOpacity: 0, strokeWeight: 1, strokeColor: 'red'
        });
        this.poly.setMap(this.map);
      }
    }
    return ret;
  }
// ------------------------------------------------------------------------------------ set_mark
//fm: LabelMap.set_mark (mark,option)
// zpřístupní vlastnosti dané značky
  set_mark (mark,ids,value) {
    let res= 1,
        id= ids.split('.');
    if ( this.map ) {
      switch (id[0]) {
      // set_mark(x,'distance.dir',dist_m) - vrátí bod vzdálený dist_m ve směru dir (0=N,90=E,...)
      case 'distance':
        var point= mark.getPosition();
        point= google.maps.geometry.spherical.computeOffset(point,value,id[1]);
        res= point.lat()+','+point.lng();
        break;
      // set_mark(x,'delete') - vymaže marker x
      case 'delete':
        if ( mark.id && this.mark[mark.id]==mark ) {
          this.mark[mark.id].setMap(null);
          delete this.mark[mark.id];
        }
        break;
      case 'ezer':
        mark.ezer[id[1]]= value;
        break;
      case 'fill':
        mark.icon.fillColor= value;
        mark.setOptions({icon:mark.icon});
        break;
      }
    }
    return res;
  }
// ------------------------------------------------------------------------------------ get_bounds
//fm: LabelMap.get_bounds ()
// vrátí souřadnice severovýchodního a jihozápadního rohu mapy spojené středníkem
  get_bounds () {
    let rect= "";
    if ( this.map ) {
      let bounds= this.map.getBounds();
      if ( bounds ) {
        let point= bounds.getSouthWest();
        rect+= point.lat()+','+point.lng()+';';
        point= bounds.getNorthEast();
        rect+= point.lat()+','+point.lng();
      }
    }
    return rect;
  }
// ------------------------------------------------------------------------------------ fit_Bounds
//fm: LabelMap.fit_bounds ()
// zvolí měřítko a polohu mapy tak, aby byly vidět všechny nastavené značky
  fit_bounds () {
    if ( this.map ) {
      if ( Object.keys(this.mark).length ) {
        var box= new google.maps.LatLngBounds();
        for (let ip in this.mark) {
          box.extend(this.mark[ip].getPosition());
        }
        this.map.fitBounds(box);
      }
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ geocode
//fi: LabelMap.geocode (gobject)
// doplní do gobjektu souřadnice obsažené adresy nebo je vymaže,
// pokud adresa nebyla poznána
//   geocode({id,address:x,...}) => {mark:'id,lat,ltd',...}
  geocode (geo) {
    if ( !this.geocoder ) this.geocoder= new google.maps.Geocoder();
    this.geo= geo;
    this.geocode_counter++;
    var ms= 0;
    if ( (this.geocode_counter % 10) == 0 ) {
      ms= 10000;
    }
    var addr= {address:geo.address};
    if ( geo.region ) addr.region= geo.region;
    if ( ms )
      this.geocoder.geocode.delay(ms,this,[addr,this._geocode.bind(this)]);
    else
      this.geocoder.geocode(addr,this._geocode.bind(this));
    // pokud google vrátí chybu nebude nastavené continuation a geocode vrátí 0
    return this;
  }
  _geocode (results, status) {
    if ( !this.continuation
      || (status!=google.maps.GeocoderStatus.OK && status!=google.maps.GeocoderStatus.ZERO_RESULTS)) {
      // návrat po chybě ... nemůžeme se vrátit do eval - zkusíme zavolat onerror
      Ezer.error("geocode "+status,'user',this);
      return 0;
    }
    // regulérní návrat z asynchronní funkce
    this.geo.mark= '';
    if (status == google.maps.GeocoderStatus.OK) {
      // navrácení výsledku: jednoznačnost, psč, poloha první volby
      this.geo.found= {diff:results.length,addr:results[0].formatted_address};
      for (var i in results[0].address_components) {
        var c= results[0].address_components[i];
          if ( c.types && c.types[0]=="postal_code" ) {
            this.geo.found.psc= c.long_name.replace(/\s/,'');
          }
      }
      var ll= results[0].geometry.location;
      delete this.geo.address;
      this.geo.lat= ll.lat();
      this.geo.lng= ll.lng();
      this.geo.mark= this.geo.id+','+this.geo.lat+','+this.geo.lng;
    }
    this.continuation.stack[++this.continuation.top]= this.geo;
    this.continuation.eval.apply(this.continuation,[0,1]);
    this.continuation= null;
    // v případě úspěchu vrátíme 1
    return 1;
  }
};

// =========================================================================================> Button
//c: Button ()
//      tlačítko
//t: Button,Block
//s: Block
//i: Button.onclick - kliknutí na tlačítko

class Button extends Block {
//os: Button.help - nápovědný text
//os: Button.format - vzhled hodnotového prvku
//  ; 'd' : disabled
//  ; 'n' : display=none
//oo: Button.par - {path:podsložka na serveru,mask:'název masky|seznam masek'} pro type:'upload'
//      path udává cílovou podsložku na serveru, v souboru logs/uploads.log je doplněn záznam
//      každém uploadu. Maska je tvořena podle vzoru: 'Obrázky|*.jpg;*.gif'
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    this.title= this.options.help||null;
    this.value= this.options.title||'';
    this.DOM_add(DOM);
    if ( this.skill==1 )
      this.enable(false);
    // vložení podčástí
    this.subBlocks(desc,this.DOM_Block);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
//os: Button.title - název
    this.title= null;                                    // nápověda položky
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _help
// specifické vlastnosti pro help mode, obecné jsou předány parametrem
  _help (x) {
    return x;
  }
// ------------------------------------------------------------------------------------ set
//fm: Button.set (val)
//      změní nápis tlačítka
//a: val - hodnota
  set (val) {
    this.value= val;
    this.DOM_set();              // zobrazení v DOM z this.value
    return 1;
 }
// ------------------------------------------------------------------------------------ get
//fm: Button.get ()
//      přečte nápis tlačítka
//r: val - hodnota
  get () {
    this.DOM_get();             // převzetí hodnoty z DOM do this.value
    return this.value;
  }
// =====================================================================================> Button DOM
  DOM_add () {
    this.DOM_Block= jQuery(`<button class="Button3">`)
      .appendTo(this.owner.DOM_Block||this.owner.value.DOM_Block)
      .css(this.coord())
      .data('ezer',this)
      .on({
        click: el => {
          if ( Ezer.dragged ) return false;
          if ( el.shiftKey ) return dbg_onshiftclick(this); /* button */
          if ( this.DOM_Block.hasClass('disabled3') ) return false;
          Ezer.fce.touch('block',this,'click');     // informace do _touch na server
          this.fire('onclick',[],el);               // zdržení aby se uplatnilo napřed blur
        }
      })
      ;
    this.DOM_set();
    this.DOM_optStyle(this.DOM_Block);
    if ( this.options.help )
      this.DOM_Block.attr('title',this.options.help);
    if ( this._fc('d') )
      this.DOM_enabled(0);
  }
// ------------------------------------------------------------------------------------ DOM enabled
// ovládá css pro button
  DOM_enabled (on) {
    if ( !on ) {
      jQuery(this.DOM_Block).addClass('disabled3');
    }
    else if ( this.skill!=1 ) {
      jQuery(this.DOM_Block).removeClass('disabled3');
    }
  }
// ------------------------------------------------------------------------------------ DOM set
// zobrazí this.value v DOM
  DOM_set () {
    jQuery(this.DOM_Block).html(this.value.replace(/\[fa-([^\]]+)\]/g,"<i class='fa fa-$1'></i>"));
  }
// ------------------------------------------------------------------------------------ DOM get
// nechá hodnotu v this.value
  DOM_get () {
  }
}

// ===========================================================================================> Elem
//c: Elem ()
//      abstraktní třída pro části formuláře mající hodnotu a podporující události
//t: Block
//s: Block
//i: Elem.onfocus - položka získala focus
//i: Elem.onchange - změna položky (vznikne ihned při změně)
//i: Elem.onblur - položka ztratila focus
//i: Elem.onchanged - změna položky (vznikne po události 'blur' položky)
//on: Elem.tabindex - pořadí pro procházení tabulátorem
//-
//oi: Elem.data - odkaz na položku tabulky přímo nebo přes view
//-
//os: Elem.expr - SQL výraz
//-
//os: Elem.help - zkratka prázdné položky | nápovědný text
//-
//os: Elem.format - vzhled hodnotového prvku
//  ; 'c' : 'center' zarovnávat doprostřed
//  ; 'd' : disabled
//  ; 'h' : hidden (pro prvky typu input)
//  ; 'n' : display=none
//  ; 'o' : 'read<u>o</u>nly' nelze změnit ani označit tabulátorem
//  ; 'p' : 'password' zobrazit hvězdičky
//  ; 'r' : 'right' zarovnávat doprava
//  ; 't' : 'tiše' nezobrazuje se rámeček při změně
//  ; 'T' : 'Tiše' změna vyvolá Elem.onchange(d) ale nikoliv Form.onchanged (rámeček udělá)
//  ;     : po dvojtečce
//  ; 'e' : místo 0 se zobrazuje ''
//  ; 'F' : první písmeno zobrazit jako velké 

class Elem extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    this.data= this.table= this.view= null;       // inicializace pro _data
    this._data();
    // atribut help - pro text prázdné položky a její title - před zobrazením
    var help= this.options.help||(this.data?this.data.options.help:null);
    if ( help ) {
      help= help.split('|');
      this.help= help[0];
      this.title= help[1]||help[0];
    }
    if ( !this.help )
      this.help= this.id[0]=='$' && this.data ? this.data.id : this.id;
    // zobrazení pokud je definován rozměr (šířka)
    if ( this.options._w!==undefined || this instanceof FieldDate )
      this.DOM_add(DOM);
    // zpracování ostatních atributů - po zobrazení
    if ( this.init ) this.init(1);
    // element bude disabled podle atributu 'd' a stavu skill
    this.enable(this.skill==1 || this._fc('d') ? false : (this.options.enabled||true));
    // vložení podčástí
    this.subBlocks(desc,this.DOM_Block);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
    this.skill= 2;                                // uživatel má plné oprávnění k položce (1 => readonly)
    this.value= null;                             // hodnota položky
    this.original= {                              // hodnota elementu - nastavuje: _load, init
      value:null,                                 // -- jak byla načtena
      key:null};                                  // -- s daným klíčem
    this.fixed_value= null;                       // pro operace form.option a elem.init
    this._changed= 0;                             // příznak změny (odpovídá zobrazení)
                                                  // 2=programatická změna, nelze ji zrušit uživatelem
    this.help= null;                              // nápovědní jméno položky
    this.title= null;                             // nápověda položky
  }
// ------------------------------------------------------------------------------------ init
//fm: Elem.init ([init_values=0])
//      nastaví hodnotu na prázdnou nebo fixovanou hodnotu nebo pro init_values==1 na defaultní
//      nebo pro init_values==2 na defaultní s nastavením elementu jako change
//a: init_values : >0 nastaví hodnotu podle atributu value, ==2 označí jako změněné
  init (init_values) {
    this.value= '';
    if ( this.owner._option && this.owner._option.x && this.owner._option.x==1 && this._f('x')>=0 ) {
      this._fixed_load();
      this.DOM_changed(1,1);
    }
    else if ( init_values ) {
      if ( this.options.value!==undefined ) {
        this.set(this.options.value||'');
        if ( init_values==2 ) {
          this.change(1);
        }
        this.DOM_set();
      }
      else {
        this.DOM_empty(true);
      }
    }
    else {
      this.DOM_empty(true);
      if ( this._changed ) {
        this.plain();
      }
    }
    this.original.value= this.value;
    this.original.key= null;
    return 1;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _fixed_save
// uschovej hodnotu do fixed_value
  _fixed_save () {
    this.fixed_value= this.get();
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _fixed_load
// vrať fixovanou hodnotu
  _fixed_load () {
    this.set(this.fixed_value);
  }
// ------------------------------------------------------------------------------------ set
//fm: Elem.set (val)
//      změní hodnotu elementu a zruší příznak změny
//a: val - hodnota
  set (val) {
    this.value= val;
    this._changed= 0;
    this.DOM_set();              // zobrazení v DOM z this.value
    this.DOM_changed(0);
    return 1;
 }
// ------------------------------------------------------------------------------------ let
// fm: Elem.let (val)
//      změní zobrazenou hodnotu elementu (bez vyvolání onchange(d), bez změny orámování)
//a: val - hodnota
  let (val) {
    this.value= val;
    this.DOM_set();              // zobrazení v DOM z this.value
    return 1;
 }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _load
// interní hodnota uschovávaná na serveru je shodná se zobrazovanou hodnotou
  _load (val,key) {
    this.original.value= val;
    this.original.key= key;
    this.set(val);
  }
// ------------------------------------------------------------------------------------ get
//fm: Elem.get ()
//r: val - hodnota
  get () {
    this.DOM_get();             // převzetí hodnoty z DOM do this.value
    return this.value;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _save
// interní hodnota uschovávaná na serveru je shodná se zobrazovanou hodnotou
  _save () {
    var vmo= {val:this.get()};
    if ( this.original.key ) {
      vmo.old= this.original.value;
    }
    return vmo;
  }
// ------------------------------------------------------------------------------------ changed
//fm: Elem.changed ()
//      zjistí příznak změny
  changed () {
    return this._changed ? 1 : 0;
  }
// ------------------------------------------------------------------------------------ change
//fm: Elem.change ([silent=0])
//      nastaví příznak programatické změny a způsobí onchange, pokud není silent=1
//a: silent - 0 | 1
//e: onchange
  change (silent) {
    this._changed= 2;       // nelze vynulovat uživatelsky jen programově plain, init, ...
//     this.DOM_empty(false);
    this.DOM_changed(2,this._fc('t'));     // když není format:'t' bez rámečku
    if ( !silent )
      this.fire('onchange');
    return 1;
  }
// ------------------------------------------------------------------------------------ plain
//fm: Elem.plain ()
//      odstranění příznaku změny
  plain () {
    this._changed= 0;
    this.DOM_changed(0);
    return 1;
  }
// ------------------------------------------------------------------------------------ focus
//fm: Elem.focus ()
//      nastavení a označení focus elementu formuláře
  focus () {
    this.DOM_focus();
    return true;
  }
// ------------------------------------------------------------------------------------ blur
//fm: Elem.blur ()
//      vyvolá událost onblur a pokud došlo ke změně tak i událost onchanged (s 'd' na konci)
//      (i když element má format=='t')
//e: onblur, onchanged
  blur () {
      this.fire('onblur');     // když není format:'t'
      if ( this._changed )
        this.fire('onchanged');
    return true;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _data
// nastavení data, view, table pokud mají smysl
  _data () {
    var name, x;
    if ( (name= this.options.data) ) {
      var ctx= [],
          ids= name.split('.'),
          ok= Ezer.code_run_name(name,this.owner,ctx,ids);
      if ( ok==1 && ctx.length>1 ) {
        this.data= ctx[0];
        x= ctx[1];
        if ( x.type=='view' && x.value.type=='table' ) {
          // name = položka view odkazujícího na tabulku
          this.view= x;
          this.table= this.view.value;
        }
        else if ( x.type=='table' )
          // name = položka tabulky
          this.table= x;
        else
          Ezer.error('jméno '+name+' nelze ve field '+this.owner.id+' pochopit');
      }
      else if ( ok==2 ) {
        x= ctx[0];
        if ( x.type=='view' && x._of=='expr' ) {
          // name = položka view zadaného výrazem
          this.data= {id:ids[ids.length-1],options:{}};
          this.view= x;
          this.table= null;
        }
        else
          Ezer.error('jméno '+name+' nelze ve field '+this.owner.id+' pochopit');
      }
      else {
        Ezer.error(name+' je neznámé jméno položky v '+this.owner.id,'S',this,this.desc._lc);
      }
    }
  }
// =======================================================================================> Elem DOM
//c: Elem-DOM ()
//      abstraktní třída pro části formuláře mající hodnotu v DOM_Input a podporující události
//t: Block-DOM
//s: Block-DOM
//o: Elem-DOM.DOM_Input - DOM element INPUT
//   DOM_Input: null,                      // prvek <input ...>
// ------------------------------------------------------------------------------------ DOM enabled
// ovládá html-atribut disabled ve vloženém input
  DOM_enabled (on) {
    if ( !on ) {
      jQuery(this.DOM_Input)
        .prop('disabled',true);
    }
    else if ( this.skill!=1 ) {
      jQuery(this.DOM_Input)
        .prop('disabled',false);
    }
  }
// ------------------------------------------------------------------------------------ DOM set
// zobrazí this.value v DOM
  DOM_set () {
    if ( this.DOM_Input ) {
      // pokud má element zobrazení
      var value= this.value, spec= this._f(':');
      if ( value==0 && spec=='e' ) value= '';
      this.DOM_Input.val(value);
    }
  }
// ------------------------------------------------------------------------------------ DOM get
// přenese hodnotu z DOM do this.value
  DOM_get () {
    if ( this.DOM_Input ) {
      // pokud má element zobrazení
      this.value= this.DOM_Input.val();
    }
  }
// ------------------------------------------------------------------------------------ DOM changed
// označení příznaku změny elementu formuláře, pokud je quiet=0; 
// on= 0|1|2 ... nastaví _changed na 2 na on - pokud je on=0 a _changed=2 tak _changed nechá
  DOM_changed (on,quiet) {
    if ( this.DOM_Input ) {
      // pokud má element zobrazení
      if ( on ) {
        Ezer.fce.touch('block',this,'changed');     // informace do _touch na server
        if ( !quiet )
          this.DOM_Input.addClass('changed');
        this._changed= on;
      }
      else if ( this._changed!==2 ) {
        if ( !quiet )
          this.DOM_Input.removeClass('changed');
        this._changed= 0;
      }
    }
  }
// ------------------------------------------------------------------------------------ DOM fixed
// označení příznaku fixování hodnoty elementu formuláře
  DOM_fixed (on) {
    if ( this.DOM_Input ) {
      // pokud má element zobrazení
      if ( on )
        this.DOM_Input.addClass('fixed');
      else
        this.DOM_Input.removeClass('fixed');
    }
  }
// ------------------------------------------------------------------------------------ DOM focus
// označení focus elementu formuláře (s uvážením prázdnosti)
  DOM_focus (inside_event=false) {
    if ( this.DOM_Input ) {
      if ( !inside_event ) this.DOM_Input.focus();
    }
  }
// ------------------------------------------------------------------------------------ DOM blur
// odznačení focus elementu formuláře (s uvážením prázdnosti)
  DOM_blur (inside_event=false) {
    if ( this.DOM_Input ) {
      if ( !inside_event ) this.DOM_Input.blur();
      if ( this._changed && this.DOM_Input.val()!=this.value ) {
        this.fire('onchanged');
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_empty
// označí/odznačí DOM_Input jako prázdný
  DOM_empty (on) {
    if ( this.DOM_Input ) {
      if ( on ) {
        this.DOM_Input.val('');
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM ElemEvents
// doplní společné události pro DOM_Input
// při zvednutí klávesy Enter resp. Esc zavolá button.submit resp. button.reset formuláře
  DOM_ElemEvents (no_focus_blur) {
    this.DOM_Input.on({
      click: event => {
        if ( event.shiftKey ) return dbg_onshiftclick(this); /* element */
        if ( this.type=='check' ) {
          this.change();
          return true;
        }
        else if ( this instanceof Field && this._fc('f') ) {
          this.DOM_Input.select();
        }
        return false;
      },
      change: event => {
        event.stopPropagation();
        if ( this.DOM_Input.val()!=this.original.value ) {
          // když není format:'t' se zvýrazněním změny - zachovej její programatičnost 
          this.DOM_changed(Math.max(this._changed,1),this._fc('t')); 
        }
        return false;
      },
      keyup: (ev) => {    // musí být key-up aby hodnota byla již změněna
        switch(ev.keyCode) {
        case 9: // Tab
          if ( this._changed ) {
            if ( event.target.value!=this.value )
              this.change();
            else if ( this._changed!==2 )
              this.DOM_changed(0);     // když byla neprogramatická změna vrácena
          }
          break;
        case 13: // Enter
          if ( this._changed ) {
            // pokud byl Enter a pole bylo změněno, vznikne událost onchanged
            this.fire('onchanged');
          }
          for (let ifield in this.owner.part) {
            let field= this.owner.part[ifield];
            if ( field instanceof Button && field.type=='button.submit' ) {
              field.fire('onclick');
            }
          }
          if ( this instanceof Field && this._fc('f') ) {
            // najdeme další 'tabbable' element 
            let tabbables= jQuery(':tabbable');
            let i= tabbables.index(this.DOM_Input);
            for (; i>0 && i<tabbables.length; i++) {
              let next= jQuery(tabbables[i+1]).data('ezer');
              if ( next instanceof Field && next._fc('f') ) {
                next.focus();
                next.DOM_Input.select();
                break;
              }
            }
          }
          break;
        case 27: // Esc
          for (let ifield in this.owner.part) {
            let field= this.owner.part[ifield];
            if ( field instanceof Button && field.type=='button.reset' ) {
              field.fire('onclick');
            }
          }
          break;
        default:
          this.fire('onchange',[]);
          if ( this._fc('F') &&  this.DOM_Input.val().length>0 ) {
            var first= this.DOM_Input.val()[0];
            if ( first==first.toLowerCase() ) {
              this.DOM_Input.val(first.toUpperCase()+this.DOM_Input.val().substr(1));
  //             this.DOM_Input.removeClass('empty_focus');
            }
          }
          if ( !this._fc('t') ) {
            if ( this.original.value==this.DOM_Input.val() ) {
              if ( this._changed!==2 ) 
                this.DOM_Input.removeClass('changed');
            }
            else {
              this.DOM_Input.addClass('changed');
            }
          }
        }
      }
    });
    if ( !no_focus_blur ) {
      this.DOM_Input.on({
        focus: () => {
          this.DOM_focus(true);
          this.fire('onfocus');
        },
        blur: () => {
          this.DOM_blur(true);
          this.fire('onblur');
        }
      });
    }
    if ( this.title ) this.DOM_Input.prop('title',this.title);
    // společné formáty
    if ( this._fc('o') && this.DOM_Block ) {
      this.DOM_Block.addClass('readonly').attr('tabindex',-1);
      if ( this.DOM_Input )
        this.DOM_Input.prop('readonly','readonly');
    }
    if ( this._fc('r') && this.DOM_Input ) this.DOM_Input.css('text-align','right');
    if ( this._fc('c') && this.DOM_Input ) this.DOM_Input.css('text-align','center');
  }
}

// ==========================================================================================> Field
//c: Field ()
//      vstupní část formuláře
//t: Block,Elem
//s: Block
//-
//os: Field.format - vzhled a chování hodnotového prvku
//  ; 'f' : při kliknutí field získá fokus a po Enter fokus přejde na další element (jako při Tab)
//-
//os: Field.title - jmenovka pole (pokud začíná ^ resp. - bude umístěná nad resp. za, jinak před)
class Field extends Elem {
// ==========================================================================================> Field
// ------------------------------------------------------------------------------------------------- Field-DOM
//c: Field-DOM ()
//      prvek nesoucí textovou nebo číselnou hodnotu
//t: Block-DOM,Elem-DOM
//s: Block-DOM
// ------------------------------------------------------------------------------------ DOM add
//f: Field-DOM.DOM_add ()
//      zobrazí prvek field
  DOM_add () {
    var hlp= this.options.help||this.help;
    if (hlp && hlp.indexOf("|")<0) hlp = hlp+'|'+hlp;
    const props= {
      type: this._f('p')==-1?'text':'password',
      id: this.options.id,
      tabindex: this.options.tabindex,
      placeholder: hlp.slice(0,hlp.indexOf("|"))
    };
    this.DOM_Input= this.DOM_Block= jQuery(`<input class="Field3">`)
      .prop(props);
    if ( this.options.title ) {
      this.DOM_Block= jQuery(`<div class="Element3"></div>`)
        .append(this.DOM_Input);
    }
    this.DOM_Block
      .appendTo(this.owner.DOM_Block||this.owner.value.DOM_Block)
      .data('ezer',this)
      .css(this.coord({height:this._h||16}));
    this.DOM_optStyle(this.DOM_Block,this.options.title);
    this.DOM_ElemEvents();
  }
}

// ======================================================================================> FieldDate
//c: FieldDate ()
//      vstupní část formuláře
//t: Block,Elem,Field
//s: Block
//-
//os: FieldDate.format - poloha dialogu data
//  ; 'R' : 'right' zobrazit zarovnaný na pravou hranu
//  ; 'U' : 'upper' zobrazit nad hodnotou
//  ;     : po dvojtečce
//  ; 'y' : dialog nabídne výběr roku, který se vrací jako číslo
class FieldDate extends Field {
// ======================================================================================> FieldDate
//c: FieldDate-DOM ()
//      prvek nesoucí datovou hodnotu
//t: Block-DOM,Elem-DOM
//s: Block-DOM
// ------------------------------------------------------------------------------------ DOM add
//f: FieldDate-DOM.DOM_add ()
//      zobrazí prvek field
  DOM_add () {
    var hlp= this.options.help||this.help;
    if (hlp && hlp.indexOf("|")<0) hlp = hlp+'|'+hlp;
    const props= {
      type: 'text',
      id: this.options.id,
      tabindex: this.options.tabindex,
      placeholder: hlp.slice(0,hlp.indexOf("|"))
    };
    this.DOM_Input= this.DOM_Block= jQuery(`<input class="Field3">`)
      .prop(props)
      .css({width:(this._w||87)-20,height:this._h||16});
    this.DOM_Button= jQuery(`<button class="fa" tabindex="-1"><i class="fa fa-calendar"></i>`)
      .click( (event,own) => {
        event.stopPropagation();
        if ( !this.picker ) {
          this.DOM_picker();
          if ( !own )
            this.DOM_Button.trigger('click',true);
        }
      });
    this.DOM_Block= jQuery(`<div class="FieldDate3">`)
      .append(this.DOM_Button)
      .append(this.DOM_Input)
      .appendTo(this.owner.DOM_Block||this.owner.value.DOM_Block)
      .data('ezer',this)
      .css(this.coord());
    this.DOM_optStyle(this.DOM_Block,this.options.title);
    this.DOM_ElemEvents();
    this.picker= null;
  }

// ------------------------------------------------------------------------------------ DOM picker
// zobrazí vybírač data podle https://github.com/dbushell/Pikaday
  DOM_picker () {
    // nastavení
    let options= {
      //ezer: true, // pokus s ul-li místo select-options
      field: this.DOM_Input[0], trigger:this.DOM_Button[0],
      firstDay: 1,
      showDaysInNextAndPreviousMonths: true,
      yearRange: [1920,2021], // minDate: new Date(), maxDate: new Date(2020, 12, 31),
      showTime: false,
      i18n: {
        previousMonth : 'předchozí měsíc',
        nextMonth     : 'další měsíc',
        months        : ['leden','únor','březen','duben','květen','červen','červenec','srpen','září','říjen','listopad','prosinec'],
        weekdays      : ['neděle','pondělí','úterý','středa','čtvrtek','pátek','sobota'],
        weekdaysShort : ['ne','po','út','st','čt','pá','so']
      },
      format: 'D.M.YYYY',
      toString(date, format) {
        return date ? `${date.getDate()}.${date.getMonth() + 1}.${date.getFullYear()}` : '';
      },
      parse(dateString, format) { // dateString is the result of `toString` method
        const parts= dateString.split('.');
        return new Date(parseInt(parts[2],10), parseInt(parts[1]-1,10), parseInt(parts[0],10));
      },
      onClose: function() {
        this.picker.destroy();
        this.picker= null;
        this.DOM_get();
        this.fire('onchanged',[]);
      }.bind(this)
    };
    let value= this.DOM_Input.val();
    Object.assign(options,value ? {
        setDefaultDate: true,
        defaultDate: new Date(ae_time2ymd(value))
      } : {
        defaultDate: new Date()
      }
    );
    this.picker= new Pikaday(options);
    return this.picker;
  }
// ------------------------------------------------------------------------------------ DOM enabled
// ovládá css pro button
  DOM_enabled (on) {
    super.DOM_enabled(on);
    if ( !on ) {
      jQuery(this.DOM_Button).addClass('disabled3');
    }
    else if ( this.skill!=1 ) {
      jQuery(this.DOM_Button).removeClass('disabled3');
    }
  }
}
// ======================================================================================> FieldList
//c: FieldList ()
//      vstupní část formuláře - rozbalení obsahu podle oddělovače
//t: Block,Elem,Field
//s: Block
class FieldList extends Elem {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    this._split= new RegExp(this.options.par ? this.options.par.delim||'[,;]' : '[,;]');
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
//oo: FieldList.par - delim: oddělovač jako regulární výraz, - width: šířka rozbaleného pole
//     this.options= {};
    this._split= null;                          // z par.delim nebo default [,;]
  }
// ======================================================================================> FieldList
//c: FieldList-DOM ()
//      prvek nesoucí datovou hodnotu s volitelným rozbalením obsahu podle oddělovače
//t: Block-DOM,Elem-DOM
//s: Block-DOM
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM initialize
  DOM_initialize () {
    this._values= [];                          // rozložené hodnoty
    this._focus= 0;                            // 0 když rozbalené prvky nemají focus
  }
// ------------------------------------------------------------------------------------ DOM add
//      zobrazí prvek field
  DOM_add () {
    var img= true;
    this._h= this._h||16;                                       // defaultní výška prvku
    var dl_w= this.options.par && this.options.par.width        // obal pro jednotlivé řádky
      ? this.options.par.width : this._w-1;
    var hlp= this.options.help||this.help;
    if (hlp && hlp.indexOf("|")<0) hlp = hlp+'|'+hlp;

    this.DOM= this.DOM_Block= jQuery(
      `<div class="Select3 FieldList3">
         <div>
           <button class="fa" tabindex="-1"><i class="fa fa-ellipsis-h"></i></button>
           <input type="text" style="width:${this._w-(img ? 20 : 0)}px;height:${this._h-4}px" />
         </div>
         <div style="display:none;width:${dl_w}" class="SelectDrop3"></div>
       </div>`)
      .css(this.coord())
      .data('ezer',this)
      .appendTo(this.owner.DOM_Block ? this.owner.DOM_Block : this.owner.value.DOM_Block);

    this.DOM_Button=   this.DOM.find("button");
    // definice obsluhy událostí
    this.DOM_Input=    this.DOM.find("input")
      .prop({
        tabindex: this.options.tabindex,
        placeholder: hlp.slice(0,hlp.indexOf("|"))
      })
      .focus ( event => {
        if ( !this._focus ) {
          this._focus++;
          this.DOM_focus();
          this.fire('onfocus');
        }
        this.DOM_hide();
      })
      .blur ( event => {
        this._focus--;
        this.DOM_hide();
        this.DOM_blur(true);
        this.fire('onblur');
      })
      .change ( () => {
        // když není format:'t' se zvýrazněním změny - zachovej její programatičnost 
        this.DOM_changed(Math.max(this._changed,1),this._fc('t')); 
      })
      .keydown ( event => {
        event.stopPropagation();
        if (event.keyCode==13) event.stop();
      })
      .keyup ( event => {
        if ( event.keyCode==27 )
          this.DOM_Input.trigger('blur');
      });
    // pokud je format:'u' budou řádky nad field jinak pod field
    this.DOM_DropList= this.DOM.find("div.SelectDrop3")
      .css( this._fc('u') ? {bottom:this._h} : {top:this._h+3});
    this.DOM_Button
      .click ( el => {
        if ( el.shiftKey ) return dbg_onshiftclick(this);  /* filedlist */
        if ( this.DOM_Input.hasClass('empty') ) {
          this.DOM_Input.value= this.value;
          this.DOM_Input.removeClass('empty').addClass('empty_focus');
        }
        this._focus++;
        this.fire('onfocus');
        this.DOM_show();
      });
    this.DOM_ElemEvents(true);
    this.DOM_optStyle(this.DOM_Input,this.options.title,true); // u title ignorovat zarovnání
  }
// ------------------------------------------------------------------------------------ DOM show
//      zobrazí hodnoty
  DOM_show () {
    // odstraň předchozí hodnoty
    this.DOM_DropList.empty();
    // rozbal hodnotu s oddělovačem a vytvoř seznam
    var values= this.DOM_Input.val().split(this._split);
    var theFocus= null;
    this._values= [];
    for (let value of values) {
      var li= jQuery(`<input class="FieldList3" value="${value}">`)
        .focus ( event => {
          this._focus++;
        })
        .blur ( event => {
          this._focus--;
          // počkáme chvilku a pak otestujeme _fokus (mohl být zvýšen klikem na jinou podhodnotu)
          setTimeout(function() {
            if ( this._focus==1 ) {
              this.DOM_hide();
              this.DOM_blur(true);
              this.fire('onblur');
            }
          }.bind(this), 50);
        })
        .change ( () => {
          // když není format:'t' se zvýrazněním změny - zachovej její programatičnost 
          this.DOM_changed(Math.max(this._changed,1),this._fc('t')); 
          this.DOM_refresh();
        })
        .keyup ( event => {
          this.DOM_refresh();
        })
        .appendTo(this.DOM_DropList);
      if ( !theFocus )
        theFocus= li;
      this._values.push(li);
    }
    // zobraz seznam
    this.DOM_DropList.css('display','block');
    this.DOM_Block.css('zIndex',999);
    if ( theFocus )
      theFocus.focus();
  }
// ------------------------------------------------------------------------------------ DOM hide
//      skryje hodnoty
  DOM_hide () {
    this.DOM_DropList.css('display','none');
    this.DOM_Block.css('zIndex',1);
    this._focus= 0;
  }
// ------------------------------------------------------------------------------------ DOM refresh
//      obnoví hodnotu ze složek
  DOM_refresh () {
    this.DOM_Input.val('');
    var del= '';
    for (let li of this._values) {
      this.DOM_Input.val(this.DOM_Input.val() + del + li.val());
      del= this.options.par ? this.options.par.delim||',' : ',';
    }
  }
// ------------------------------------------------------------------------------------ DOM enabled
// ovládá css pro button
  DOM_enabled (on) {
    super.DOM_enabled(on);
    if ( !on ) {
      jQuery(this.DOM_Button).addClass('disabled3');
    }
    else if ( this.skill!=1 ) {
      jQuery(this.DOM_Button).removeClass('disabled3');
    }
  }
}

// ===========================================================================================> Edit
//c: Edit ()
//      vstupní část formuláře
//t: Block,Elem
//s: Block
class Edit extends Elem {
//   options: {}
// ===========================================================================================> Edit
// prvek nesoucí dlouhou textovou hodnotu
// ------------------------------------------------------------------------------------ DOM add
//      zobrazí prvek field
  DOM_add () {
    let corr= Ezer.browser=='CH' ? {height:this._h-2,width:this._w-2} : {height:this._h-2};
    var hlp= this.options.help||this.help;
    if (hlp && hlp.indexOf("|")<0) hlp = hlp+'|'+hlp;
    const props= {
      id: this.options.id,
      tabindex: this.options.tabindex,
      placeholder: hlp.slice(0,hlp.indexOf("|"))
    };
    this.DOM_Block= this.DOM_Input= jQuery(`<textarea class="Edit3">`)
      .prop(props);
    if ( this.options.title ) {
      this.DOM_Block= jQuery(`<div class="Element3">`)  // div na obal a návěští
        .append(this.DOM_Input);
    }
    this.DOM_Block
      .appendTo(this.owner.DOM_Block ? this.owner.DOM_Block : this.owner.value.DOM_Block)
      .data('ezer',this)
      .css(this.coord(corr));
    this.DOM_ElemEvents();
    this.DOM_optStyle(this.DOM_Input,this.options.title,true);    // u title ignorovat zarovnání
  }
}

// =======================================================================================> EditHtml
//c: EditHtml ()
//      vstupní část formuláře s wysiwyg editorem CKeditor
//t: Block,Elem,Edit
//s: Block
class EditHtml extends Elem {
//   options: {}
// ------------------------------------------------------------------------------------ changed
// -- fm: EditHtml.changed ()
//      zjistí zda došlo ke změně obsahu
// -- Pozn. U elementu typu EditHtml se netestuje příznak změny (vizuální podobou je obarvení rámečku)
//      ale to, zda je současný obsah CKeditor změněný proti načtenému stavu. Pokud tedy byl
//      po změně takového elementu programově zrušen příznak změny (např. operací plain) bude
//      jeho hodnota přesto odevzdána k uložení na disk. Důvodem k tomuto chování je asynchronní
//      časování události blur v CKeditoru.
//   changed: function() {
//     if ( this.ckeditor && Ezer.options.CKEditor.version[0]=='4' ) {
//       return this.ckeditor.checkDirty();
//     }
//     if ( !this._changed && this.ckeditor ) {
//       this._changed= this.original.value!=this.ckeditor.getData();
//     }
//     return this._changed ? 1 : 0;
//   }
// =======================================================================================> EditHtml
// prvek nesoucí dlouhou textovou hodnotu s WYSIVYG editorem
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM initialize
  DOM_initialize () {
    this.ckeditor= null;                               // instance CKeditoru
    this.focused= false;
    this._value= '';                                   // pomocná hodnota iniciovaná při focus
  }
// ------------------------------------------------------------------------------------ DOM add
//      zobrazí prvek field
//      Ezer.options.CKEditor.version prázdné nebo 4
  DOM_add () {
    if ( window.CKEDITOR ) {
      // v aplikaci je použit CKeditor
      var options;
      this.DOM_outline= this.DOM_Block= jQuery(`<div class="EditHtml3"><textarea>`)
        .appendTo(this.owner.DOM_Block ? this.owner.DOM_Block : this.owner.value.DOM_Block)
        .data('ezer',this)
        .css(this.coord());
      this.DOM_Input= this.DOM_Block.find("textarea");
      if ( window.CKEDITOR.version.substr(0,3) >= "4.5" ) {
      // ---------------------------------------------- verze 4.5 a vyšší s widgetem 'ezer' v lib.js
        options= {
          height:this._h-60, entities:false, entities_latin:false, language:'cs', contentsLanguage:'cs'
        };
        Object.assign(options,this.options.par||{});
        Object.assign(options,options.toolbar && Ezer.options.CKEditor[options.toolbar]
          ? Ezer.options.CKEditor[options.toolbar]
          : {toolbar:[['Maximize','Styles','-','Bold','Italic','RemoveFormat',
            '-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock',
            '-','Outdent', 'Indent', 'Blockquote',
            '-','NumberedList','BulletedList',
            '-','Link','Unlink','Image',
            '-','Source']]});
        this.ckeditor= CKEDITOR.replace(this.DOM_Input[0],options);
      }
      else {
        // --------------------------------- ošetření rozdílu mezi staršími verzemi před startem
        if ( Ezer.options.CKEditor.version[0]=='4' ) {
          // základní nastavení editoru verze 4.0.1
          options= {
            width:this._w, height:this._h-60, resize_enabled:false,
            entities:false, entities_latin:false, language:'cs', contentsLanguage:'cs',
            skin:'kama'
          };
        }
        else {
          // základní nastavení editoru verze do 3.6.2
          options= {
            width:this._w, height:this._h-60, resize_enabled:false,
            entities:false, entities_latin:false, language:'cs', contentsLanguage:'cs',
            skin:'office2003'
          };
        }
        // ---------------------------------------------- společná část pro verze 3 i 4 do 4.5
        // úprava options z nastavení aplikace podle options.toolbar z Ezerscriptu
        Object.assign(options,this.options.par||{});
        Object.assign(options,options.toolbar && Ezer.options.CKEditor[options.toolbar]
          ? Ezer.options.CKEditor[options.toolbar]
          : {toolbar:[[ 'Find','Replace',    // nebo jednoduchý default
              '-','Bold','Italic','Subscript','Superscript',
              '-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock',
              '-','Link','Unlink',
              '-','NumberedList', 'BulletedList',
              '-','Source','ShowBlocks','RemoveFormat' ]]});
        this.ckeditor= CKEDITOR.replace(this.DOM_Input[0],options);
      }
      // ----------------------------------------------- ošetření focus, blur, change
      if ( this.ckeditor ) this.ckeditor.on('focus', function(ev) {
        this._value= this.ckeditor.getData();
        this.focused= true;
        this.DOM_outline.addClass(this._changed ? 'changed_focus' : 'focus');
        this.fire('onfocus');
      }.bind(this));
      if ( this.ckeditor ) this.ckeditor.on('change', function(ev) {
        if ( this.focused ) {
          if ( !this._changed ) {
            this.DOM_outline.removeClass('focus').addClass('changed_focus');
          }
          // když není format:'t' se zvýrazněním změny - zachovej její programatičnost 
          this.DOM_changed(Math.max(this._changed,1),this._fc('t')); 
          this.fire('onchange');
        }
      }.bind(this));
      if ( this.ckeditor ) this.ckeditor.on('blur', function(ev) {
        this.focused= false;
        this.DOM_outline.removeClass('focus').removeClass('changed_focus');
        if ( this._changed ) {
          this.fire('onchanged');
        }
        this.fire('onblur');
      }.bind(this));
      // ----------------------------------------------- dokončení nastavení po startu
      CKEDITOR.on('instanceReady', function(ev) {
        var tags= ['div', 'p', 'ol', 'ul', 'li', 'table', 'tr', 'td', 'h1', 'h2', 'h3']; // etc.
        for (var key in tags) {
          ev.editor.dataProcessor.writer.setRules(tags[key],{
            indent: false,
            breakBeforeOpen: false,
            breakAfterOpen: false,
            breakBeforeClose: false,
            breakAfterClose: false
          });
        }
        if ( Ezer.browser=='CH' ) {
          this.DOM_outline= this.DOM_Block.find('div.cke_chrome');
        }
      }.bind(this));
      this._value= '';
      // oprava výšky DOM_Block podle prohlížeče
      this.DOM_Block.css('height','');
    }
    else {
      // balík CKEditor není dostupný
      this.DOM_Block= this.DOM_Input= jQuery(`<textarea class="Edit3">`)
        .css(this.coord())
        .appendTo(this.owner.DOM_Block ? this.owner.DOM_Block : this.owner.value.DOM_Block)
        .data('ezer',this);
    }
  }
// ----------------------------------------------------------------------------- DOM_set_properties
// změní jen některé vlastnosti: left, top, width, height podle parametru, smooth se ignooruje
  DOM_set_properties (prop) {
    if ( this.ckeditor ) {
      var wdiv= this.DOM_Block.find('.cke');
      var hdiv= this.DOM_Block.find('.cke_contents');
      if ( prop.left!==undefined )                                  // left
        this.DOM_Block.css('left',Number(prop.left));
      if ( prop.top!==undefined )                                   // top
        this.DOM_Block.css('top',Number(prop.top));
      if ( prop.width!==undefined )                                 // width
        wdiv.css('width',Number(prop.width));
      if ( prop.height!==undefined )                                // height
        hdiv.css('height',Number(prop.height));
    }
    else {
      this.__proto__.__proto__.DOM_set_properties.call(this,prop); // aka super.DOM_set_properties(prop)
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM empty
//      voláno z this.init
  DOM_empty (on) {
    if ( this.ckeditor && on ) {
      this.DOM_set();
    }
  }
// ------------------------------------------------------------------------------------ DOM changed
//      označení příznaku změny elementu formuláře, pokud je quiet=0
  DOM_changed (on,quiet) {
    // pokud má element zobrazení
    if ( on ) {
      this._changed= on;
      Ezer.fce.touch('block',this,'changed');     // informace do _touch na server
      if ( !quiet && this.DOM_outline )
        this.DOM_outline.addClass('changed');
    }
    else if ( this._changed!==2 && !quiet && this.DOM_outline )
      this.DOM_outline.removeClass('changed');
  }
// ------------------------------------------------------------------------------------ DOM set
//      zobrazí this.value v DOM
  DOM_set () {
    if ( this.DOM_Input ) {
      // pokud má element zobrazení
      if ( this.ckeditor ) {
        // v aplikaci je použit CKeditor
        this.ckeditor.setData(this.value);
      }
      else {
        var value= this.value;
        this.DOM_Input.value= value;
        this.DOM_empty(!value);
      }
    }
  }
// ------------------------------------------------------------------------------------ DOM get
//      přenese hodnotu z DOM do this.value
  DOM_get () {
    if ( this.DOM_Input ) {
      // pokud má element zobrazení
      if ( this.ckeditor ) {
        // v aplikaci je použit CKeditor
        this.value= this.ckeditor.getData();
      }
      else {
        this.value= this.DOM_Input.hasClass('empty') ? '' : this.DOM_Input.value;
      }
    }
  }
}

// =======================================================================================> EditAuto
//c: EditAuto ()
//      Textarea s našeptávačem
//t: Block,Elem,Edit
//s: Block
class EditAuto extends Edit {
//   options: {}
// =======================================================================================> EditAuto
// prvek nesoucí dlouhou textovou hodnotu s našeptávačem
// ------------------------------------------------------------------------------------ init
//fm: EditAuto.init (init_values=array|string, [delimiter=', '])
//      inicializuje našeptávač pro element edit, našeptávané hodnoty jsou předány buďto v poli,
//      nebo ve stringu odděleny čárkami. Delimiter je oddělovač hodnot vkládaným výběrem 
//      z našeptaných hodnot
  selects (init_values,delimiter) {
    this.keywords= 
      typeof init_values==='object' ? init_values :(
      typeof init_values==='string' ? init_values.split( /,\s*/ ) : []);
    delimiter= delimiter||', ';
    jQuery(this.DOM_Input)
      .autocomplete({
        minLength: 0,
        source: function (request, response) {
          // delegate back to autocomplete, but extract the last term
          response( jQuery.ui.autocomplete.filter(
            this.keywords, request.term.split(/,\s*/).pop()));
        }.bind(this),
        focus: function () {
          // prevent value inserted on focus
          return false;
        },
        select: function (event, ui) {
          var terms= this.value.split(/,\s*/);
          // remove the current input
          terms.pop();
          // add the selected item
          terms.push(ui.item.value);
          // add placeholder to get the comma-and-space at the end
          terms.push("");
          this.value= terms.join(delimiter);
          return false;
        }
      });
    return 1;
  }
}

// ==========================================================================================> Check
//c: Check ()
//      zaškrtávací políčko
//t: Block,Elem
//s: Block
class Check extends Elem {
//   options: {},
  // metody
// ------------------------------------------------------------------------------------ init
//fm: Check.init ([init_values=0])
//      naplní element hodnotou atributu 'value' nebo 0
//      pro init_values==2 s nastavením elementu jako change
  init (init_values) {
    this.value= this.options.value||0;
    this._changed= 0;
    this.DOM_set();
    this.DOM_empty(true);
    if ( init_values==2 )
      this.change(1);
    else
      this.DOM_changed(0);
    this.original.value= this.value;
    this.original.key= null;
    return 1;
  }
// ==========================================================================================> Check
// zaškrtávací políčko
// ------------------------------------------------------------------------------------ DOM add
//      zobrazí prvek field, label zobrazí podle atributu format: c-centrovaně, r-doprava
  DOM_add (DOM) {
    this.DOM= this.DOM_Block= jQuery(
      `<label class="Check3">
         <input type="checkbox" tabindex="${this.options.tabindex||-1}">${this.options.title||''}</label>`)
      .css(this.coord({textAlign:this._fc('c') ? 'center' : this._fc('r') ? 'right' : 'left'}))
      .appendTo(DOM ? DOM : (this.owner.DOM_Block ? this.owner.DOM_Block : this.owner.value.DOM_Block))
      .data('ezer',this);
    this.DOM_Input= this.DOM.find("input");
    this.DOM_ElemEvents();
    this.DOM_optStyle(this.DOM_Block);
  }
// ------------------------------------------------------------------------------------ DOM set
//      zobrazí this.value v DOM
  DOM_set () {
    this.DOM_Input.prop('checked',this.value!=0);
  }
// ------------------------------------------------------------------------------------ DOM get
//      přenese hodnotu z DOM do this.value
  DOM_get () {
    this.value= this.DOM_Input.prop('checked') ? 1 : 0;
  }
}

// ==========================================================================================> Radio
//c: Radio ()
//      radio buttons
//t: Block,Elem
//s: Block
class Radio extends Elem {
//   options: {},
//   value: null,
//   _changed: false,
// ------------------------------------------------------------------------------------ init
//fm: Radio.init ([init_values=0])
//      naplní element hodnotou atributu 'value'
//      pro init_values==2 s nastavením elementu jako change
  init (init_values) {
    this._changed= 0;
    if ( this.options.value!==undefined ) {
      this.value= this.options.value;
      this.DOM_set();
      if ( init_values==2 )
        this.change(1);
      else
        this.DOM_changed(0);
    }
    this.original.value= this.value;
    this.original.key= null;
    return 1;
  }
// ------------------------------------------------------------------------------------ set
// fm: Radio.set (val) == Elem.set
// a: val - hodnota
//   set: function (val) {
//     this.value= val;
//     this._changed= false;
//     this.DOM_set();              // zobrazení v DOM z this.value
//     this.DOM_changed(0);
//     return 1;
//  },
// ------------------------------------------------------------------------------------ get
//fm: Radio.get ()
//r: val - hodnota
  get () {
    return this.value;
 }
// ------------------------------------------------------------------------------------ enable
//fm: Radio.enable (enabled)
//      provede case.enable pro všechny vnořené case,
//      nelze používat bez parametru
  enable (enabled) {
    for (var i in this.part) {
      var part= this.part[i];
      if ( part instanceof Case ) {
        part.options.enabled= enabled;
        part.DOM_enabled(enabled);
      }
    }
    return 1;
  }
// ==========================================================================================> Radio
// seskupení políček volby
// ------------------------------------------------------------------------------------ DOM add
// zobrazí prvek field
  DOM_add () {
    this.DOM= this.DOM_Block= jQuery(`<div class="Radio3">`)
      .css(this.coord())
      .appendTo(this.owner.DOM_Block ? this.owner.DOM_Block : this.owner.value.DOM_Block)
      .data('ezer',this);
    this.DOM_optStyle(this.DOM_Block);
  }
// ------------------------------------------------------------------------------------ DOM set
// přepne na volbu s hodnotou this.value
  DOM_set () {
    let checked= null, found= false;
    for (var ic in this.part) {
      var c= this.part[ic];
      if ( c instanceof Case && c.DOM_Input ) {
        if ( c.DOM_Input.prop('checked') )      // zapamatujeme si ten zvolený
          checked= c.DOM_Input;
        if ( this.value==c.options.expr || this.value==c.options.value ) {
          c.DOM_Input.prop('checked',true);
          found= true;                          // zapamatujeme úspěch
          break;
        }
      }
    }
    if ( checked && !found )                    // pokud jsme hodnotu nenalezli a je definován nějaký stav
      checked.prop('checked',false);            // nastavíme nedefinovaný stav
    return true;
  }
// -------------------------------------------------------------------------------- DOM changed
// označení příznaku změny elementu formuláře, pokud je quiet=0
  DOM_changed (on,quiet) {
    if ( on ) {
      Ezer.fce.touch('block',this,'changed');     // informace do _touch na server
      if ( !quiet )
        this.DOM_Block.addClass('changed');
      this._changed= on;
    }
    else if ( this._changed!==2 ) {
      if ( !quiet )
      this.DOM_Block.removeClass('changed');
      this._changed= 0;
    }
  }
}

// ===========================================================================================> Case
//c: Case ()
//      radio button
//t: Block,Elem
//s: Block
class Case extends Elem {
//   options: {}
  // metody
// ===========================================================================================> Case
// políčko volby
// ------------------------------------------------------------------------------------ DOM add
//      zobrazí prvek field
  DOM_add () {
    this.DOM= this.DOM_Block= jQuery(
      `<label class="Case3">
         <input type="radio" name="${this.owner.self()}">${this.options.title}</label>`)
      .css(this.coord())
      .appendTo(this.owner.DOM_Block)
      .data('ezer',this)
      .change( el => {
        // když není format:'t' se zvýrazněním změny - zachovej její programatičnost 
        this.owner.DOM_changed(Math.max(this.owner._changed,1),this.owner._fc('t')); 
        this.owner.value= this.options.expr||this.options.value;
        this.owner.fire('onchange',[],el);
      });
    this.DOM_Input= this.DOM.find("input");
    this.DOM_optStyle(this.DOM_Block);
  }
}

// ===========================================================================================> Chat
//c: Chat ()
//      přeložený blok chat (element formuláře historie)
//      řádky lze interaktivně měnit: po dvojkliku s obsluhou onrowclick a funkcí let.
//      Nepovinné 3.slovo ve skill povoluje interaktivní změnu (standardně zakázanou).
//t: Block,Elem
//s: Block
//i: Chat.onrowclick - dvojklik na řádku (parametrem je index řádku, první má index 1)
class Chat extends Elem {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    // diskuse povolení interaktivních změn
    if ( this.options.skill ) {
      var aa= this.options.skill.replace(/\s+/g, ' ').trim().split('|');
      this.changeable= aa[2] ? Ezer.fce.has_skill(aa[2]) : false ;
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
    this.append=  0;
    this.changeable= true;     // s povolením interaktvní změny
    this._changedRow= {};      // .row je pořadí řádku, .op=a|p|d|c pro append, delete, change
  }
// ------------------------------------------------------------------------------------ init
//fm: Chat.init ()
//      inicializace
  init () {
    //Ezer.Elem.prototype.init.call(this,'');
    this.DOM_clear();
    this.value= '';
    this._changed= 0;
    this._changedRow= {};
    this.DOM_changed(0);
    return true;
  }
// ------------------------------------------------------------------------------------ on
//fm: Chat.enable (on)
//      enable pro chat
//   enable: function (on) {
//     QE_elem.prototype.enable.call(this);
//     if ( on ) this.his.removeClass('disable');
//     else this.his.addClass('disable');
//     return true;
//   },
// ------------------------------------------------------------------------------------ add
//fm: Chat.add (val)
//      přidá řádky do chat podle oddělovačů '|'
  add (val) {
    // nastaví value a zruší případný příznak změny
    this.DOM_clear();
    this.value= val;
    this._changed= 0;
    this._changedRow= {};
    this.DOM_changed(0);
    // zobraz historii chatu po řádcích - v případě nedodržení formátu zobraz to co je
    var aval= val ? val.split('|') : [];
    if ( aval.length>1 ) {
      for (var i=0; i<aval.length-1; i+=2 ) {
        this.DOM_append(i/2+1,aval[i],aval[i+1]);
      }
    }
    else {
      this.DOM_Hist.html(`<div tabIndex="-1" class="Chat2">${val}</div>`);
    }
    // pokud je format='r' nastav chat na konec - jinak na začátek
    this.DOM_Hist.scrollTop= this.append==1 ? this.DOM_Hist.scrollHeight : 0;
    this.DOM_Input.val('');
    return 1;
  }
// ------------------------------------------------------------------------------------ let
//fm: Chat.let (n,value)
//      změní hodnotu n-tého řádku v chat, pokud je hodnotou prázdný řetězec,
//      bude řádek zrušen
  let (n,value) {
    var chs= this.DOM_Hist.find('div');
    var ns= 2*n-2;
    if ( chs && chs.length>ns ) {
      var ch= jQuery(chs[2*n-2]);
      this._changed= 2;
      this._changedRow= {row:n};
      if ( value ) {
        // prvek bude změněn
        ch.next().html(value);
        ch.next().addClass('changed');
        this.DOM_Input.addClass('changed');
        this._changedRow.op= 'c';
      }
      else {
        // prvek bude vymazán
        ch.next().remove();
        ch.remove();
        this._changedRow.op= 'd';
      }
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ set
//fm: Chat.set (val,is_original)
//      set pro chat
  set (val) {
    // nastaví value a zruší případný příznak změny
    this.value= val;
    this._changed= 0;
    this.DOM_changed(0);
    // zobraz historii chatu po řádcích - v případě nedodržení formátu zobraz to co je
    var aval= val ? val.split('|') : [];
    var html= '';
    if ( aval.length>1 ) {
      for (var i=0; i<aval.length-1; i+=2 ) {
        var j= i%2;
        html+= "<div tabIndex='-1' class='Chat_"+(j+1)+"'>"+aval[i]+"</div>";
        html+= "<div tabIndex='-1' class='Chat_"+(j+2)+"'>"+aval[i+1]+"</div>";
      }
    }
    else {
      html+= "<div tabIndex='-1' class='Chat2'>"+val+"</div>";
    }
    this.DOM_Hist.html(html);
    // pokud je format='r' nastav chat na konec - jinak na začátek
    this.DOM_Hist.scrollTop= this.append==1 ? this.DOM_Hist.scrollHeight : 0;
    this.DOM_Input.val('');
    return 1;
  }
// ------------------------------------------------------------------------------------ get
//fm: Chat.get ([mode=0])
//      pro mode=0 (default) vrátí obsah vstupního pole označeného značkou uživatele a datem
//      pro mode=1 vrátí pouze obsah vstupního pole
  get (mode) {
    return mode
      ? this.DOM_Input.val()
      : Ezer.sys.user.abbr + ' ' + ae_datum(1) + '|' + this.DOM_Input.val() + '|';
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _load
// interní hodnota uschovávaná na serveru je shodná se zobrazovanou hodnotou
  _load (val,key) {
    this.add(val);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _save
// interní hodnota uschovávaná na serveru je shodná se zobrazovanou hodnotou
  _save () {
    var vmo;
    if ( this._changedRow.op ) {
      // pro delete a change přidáme celou hodnotu (ochrana proti současné opravě jiným uživatelem)
      vmo= {row:this._changedRow.row, mode:this._changedRow.op, val:this.get(1), old:this.value};
    }
    else {
      vmo= {row:0, val:this.get(), mode:this.append?'a':'p'};
    }
    return vmo;
  }
// ------------------------------------------------------------------------------------ DOM add
// zobrazí prvek chat
  DOM_add () {
    let h1= this._h*(this.options.divide||50)/100 - 1;
    let h2= Math.max(this._h - h1 - 3,0);
    this.DOM= this.DOM_Block= jQuery(
      `<div class="Chat3">
         <div class="Chat_hist3" tabindex="-1"></div>
         <textarea></textarea>
       </div>`)
      .css(this.coord())
      .appendTo(this.owner.DOM_Block)
      .data('ezer',this);
    this.DOM_Hist= this.DOM.find("div")
      .css('height',h1);
    this.DOM_Input= this.DOM.find("textarea")
      .css('height',h2);
    this.append= this._f('r')>=0 ? 1 : 0;
    if ( this.skill==1 ) this.enable(0);
    this.DOM_ElemEvents();
    this.DOM_optStyle(this.DOM_Block,this.options.title);
  }
// ------------------------------------------------------------------------------------ DOM clear
//      zobrazí prvek chat
  DOM_clear () {
      this.DOM_Hist.empty();
      this.DOM_Input.val('');
  }
// ------------------------------------------------------------------------------------ DOM append
//      přidá řádek do chat
  DOM_append (index,head,tail) {
    // trik k rozlišení click a dblclk pomocí timeru
    // viz http://groups.google.com/group/mootools-users/browse_thread/thread/f873371716d338c9
    var timer, node, elem;
    elem= jQuery(
      `<div class="Chat_1" tabIndex="-1">${head}</div>
       <div class="Chat_2" tabIndex="-1">${tail}</div>`)
      .appendTo(this.DOM_Hist);
    if ( this.changeable ) {
      elem
        .click ( el => {
          if ( el.shiftKey ) return dbg_onshiftclick(this);  /* chat */
          clearTimeout(timer);
          clearInterval(timer);
          timer= (function(){
          }).delay(200, this);
        })
        .dblclick ( event => {
          event.stopPropagation();
          clearTimeout(timer);
          clearInterval(timer);
          if ( !this._changed ) {
            if ( (node= event.target.parent()) )
              if ( (node= node.find('.focus')) )
                node.removeClass('focus');
            event.target.next().addClass('focus');
            this.DOM_Input.val(event.target.next().html());
            this.DOM_Input.removeClass('empty');
            this._changedRow= {row:index};
            this.fire('onrowclick',[index,head,tail],event);
          }
        });
    }
  }
// ------------------------------------------------------------------------------------ DOM focus
// označení focus elementu formuláře (s uvážením prázdnosti)
  DOM_focus (inside_event=false) {
    if ( this.DOM_Input ) {
      if ( !inside_event ) this.DOM_Input.focus();
      if ( this.DOM_Input.hasClass('empty') ) {
        this.DOM_Input.val('');
        this.DOM_Input.removeClass('empty').addClass('empty_focus');
      }
    }
  }
// ------------------------------------------------------------------------------------ DOM changed
// provede Elem-DOM.DOM_changed (označení příznaku změny elementu formuláře - přitom
// ignoruje formát 't' tedy quiet);
// potom zajistí zápis operace c nebo d
  DOM_changed (on,quiet) {
    if ( on ) {
      this.DOM_Input.addClass('changed');
      this.DOM_Input.removeClass('empty').removeClass('empty_focus');
      this._changed= on;
    }
    else if ( this._changed!==2 ) {
      if ( !quiet )
      this.DOM_Input.removeClass('changed');
      this._changed= 0;
    }
    if ( this._changedRow.row ) {
      // pokud byla započata oprava řádku, poznač opravu či smazání
      this._changedRow.op= this.DOM_Input.val() ? 'c' : 'd';
    }
  }
}

// =========================================================================================> Select ...
//c: Select
//      výběrová položka formuláře
//      Pozn. metoda form.save použije klíč zobrazené hodnoty
//t: Block,Elem
//s: Block
//i: Select.onchange - změna vybraného itemu
// =====================================================================================> Select DOM
//c: Select-DOM ()
//      Select má společné zobrazení a implementuje třídu Drag
//t: Block-DOM,Elem-DOM
//s: Block-DOM
Ezer.DOM_currMulti= null;                // aktivní multi select
// -------------------------------------------------------------------------- DOM clearDropLists
// schová rozvinutý DropList při kliknutí mimo něj
Ezer.DOM_clearDropLists= function() {
  if ( Ezer.DOM_currMulti && Ezer.DOM_currMulti._drop_status==2 )
    Ezer.DOM_currMulti.DOM_drop_hide();
  Ezer.DOM_currMulti= null;
};

class Select extends Elem {
//os: Select.format - vzhled hodnotového prvku
//  ; 'u' : 'up' seznam hodnot bude zobrazen nad select
//  ; 'w' : 'wide' seznam hodnot bude zobrazen v plné šířce
//   Extends: Ezer.Elem,
//   Items: {},
//   Css: null,                    // css pro daný klíč (lze definovat jen fcí selects)
//   lastCss: '',                  // aktuální nastavené css (jen je-li definované Css)
//   _key: null,                   // klíč - pro multiselect pole klíčů
//   _values: 0,
//   multi: false,
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
// multi=true pokud je možné volit více hodnot se stisknutým Ctrl (nebo dotykem - šoupáním doleva)
// metody get a key přijímají a vracejí čárkami oddělený seznam hodnot
  constructor (owner,desc,DOM,id,skill,multi) {
    desc.multi= multi || 0;
    super(owner,desc,DOM,id,skill);
  }
  initialize () {
    super.initialize();
    this.Items= {};
    this.Css= null;                    // css pro daný klíč (lze definovat jen fcí selects)
    this.lastCss= '';                  // aktuální nastavené css (jen je-li definované Css)
    this._key= null;                   // klíč - pro multiselect pole klíčů
    this._values= 0;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _data
// nastavení this.multi je zařazeno zde, aby proběhlo před DOM_add
  _data () {
    this.multi= this.desc.multi;
    super._data();
  }
// ------------------------------------------------------------------------------------ selects
//fm: Select.selects (list[,delimiters=',:'][,values:0)
//a: list - seznam volitelných hodnot pro select ve tvaru: hodnota[:klíč:css],...
//   delimiters - řetězec definující 2 znaky použité jako oddělovače
//   values - 1:funkce key,_save,_load bude vracet/číst místo klíče hodnotu
  selects(list,delimiters,values) {
    this._values= values?1:0;
    this.Items= {};
    this.Css= {};               // lastCss necháme kvůli jeho odstranění
    var del1= ',', del2= ':';
    if ( delimiters ) {
      del1= delimiters[0]||',';
      del2= delimiters[1]||':';
    }
    for (let [i,val] of list.split(del1).entries()) {
      var desc= val.split(del2);
      if ( desc.length==3 ) {
        this.Items[desc[1]]= desc[0];
        this.Css[desc[1]]= desc[2];
      }
      else if ( desc.length==2 ) {
        this.Items[desc[1]]= desc[0];
      }
      else {
        this.Items[i]= val;
      }
    }
    this.init(); // místo pouhého this.DOM_addItems();
    return true;
  }
// ------------------------------------------------------------------------------------ init
//fm: Select.init ([init_values=0])
//      nastaví hodnotu na prázdnou nebo fixovanou hodnotu nebo pro init_values==1 na defaultní
//      nebo pro init_values==2 na defaultní s nastavením elementu jako change;
//      vymaže seznam hodnot
//a: init_values : >0 nastaví hodnotu podle atributu value, ==2 označí jako změněné
  init  (init_values) {
    this._key= this.multi ? [] : 0;
    this.DOM_drop_hide();
    this.DOM_addItems();
    this.plain();
    super.init(init_values);
    return true;
  }
// ------------------------------------------------------------------------------------ change
//fm: Select.change ([silent=0])
//      nastaví příznak změny a způsobí onchanged, pokud není silent=1
//a: silent - 0 | 1
//e: onchanged
  change (silent) {
    this._changed= 2;       // nelze vynulovat uživatelsky jen programově plain, init, ...
//     this.DOM_empty(false);
    this.DOM_changed(2,this._fc('t'));     // když není format:'t' bez rámečku
    if ( !silent ) {
      this.fire('onchange');
      this.fire('onchanged');
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ key
//fm: Select.key ([key])
//      lze použít jako setter nebo getter pro key - pro multiselect key je seznam čísel
  key  (key) {
    var ret= 1, del= '';
    if ( key!==undefined ) {
      Ezer.assert(this.Items,'nedefinované položky v select',this);
      this._changed= 0;
      this.DOM_changed(0);
      if ( this.multi ) {       // multi select
        // oprav hodnotu klíče z čísla na string
        key= typeof(key)=='string' ? key : (key ? key.toString() : '');
        this._key= [];
        this.value= '';
        if ( key ) {
          for (let k of key.split(',')) {
            k= Number(k);
            this._key.push(k);
            this.value+= del+this.Items[k];
            del= ',';
          }
        }
      }
      else {                    // single select
        // oprav hodnotu klíče se stringu na číslo
        this._key= typeof(key)=='string' ? (isNaN(Number(key)) ? key : Number(key))  : key;
        this.value= this.Items[this._key];
      }
      if ( this.value===undefined ) {
        this.value= '';
      }
      this.DOM_set();
    }
    else {
      ret= key= this._values ? this.value : this._key;
    }
    return ret;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _load
// interní hodnota uschovávaná na serveru
  _load  (val,key) {
    this.original.value= val;
    this.original.key= key;
    this.set(val);
    this._changed= 0;
    this.DOM_changed(0);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _save
// interní hodnota uschovávaná na serveru je klíč zobrazené hodnoty
  _save  () {
    var vmo= {val:this.value};
    if ( this.original.key ) {
      vmo.old= this.original.value;
    }
    return vmo;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _fixed_save
// uschovej hodnotu do fixed_value
  _fixed_save () {
    this.fixed_value= this.value;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _fixed_load
// vrať fixovanou hodnotu
  _fixed_load () {
    this.set(this.fixed_value);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM initialize
  DOM_initialize () {
    this._value= '';            // pomocná hodnota iniciovaná při focus
    this._drop_status= 0;       // 0=skrytý, 1=viditelný, 2=změněný (multi i single)
  }
// ------------------------------------------------------------------------------------ DOM add
//f: Select-DOM.DOM_add ()
//      zobrazí prvek select - pokud multi=true dovoluje vybrat více hodnot
//      pokud atribut par obsahuje noimg:1 pak se nezobrazí obrázek šipky
//      pokud atribut par.subtype='browse' pak se jedná o select vnořený do Show
//o: Select-DOM.DOM_Closure - obal pro input a ikonu
//o: Select-DOM.DOM_DropList - obal pro jednotlivé Items (options)
  DOM_add () {
    // obecné zobrazení select
    this._h= this._h||16;         // defaultní výška prvku
    let img= this.options.par && this.options.par.noimg==1 ? false : true;
    let dl_w= this.options.par && this.options.par.subtype=='keys' && this.options.par.width
      ? this.options.par.width : this._w-1;
    var hlp= this.options.help||this.help;
    if (hlp && hlp.indexOf("|")<0) hlp = hlp+'|'+hlp;
    let ro= this instanceof SelectAuto ? '' : 'readonly ';
    this.DOM= this.DOM_Block= jQuery(
      `<div class="Select3">
         <div>
           <input type="text" ${ro}style="width:${this._w-(img ? 20 : 0)}px;height:${this._h-4}px"/>
         </div>
         <ul style="display:none" ${this.multi ? "title='použij CTRL pro změnu'" : ''}></ul>
       </div>`)
      .css(this.coord())
      .appendTo(this.owner.DOM_Block ? this.owner.DOM_Block : this.owner.value.DOM_Block)
      .data('ezer',this);

    this.DOM_Button=   null;
    this.DOM_Closure=  this.DOM.find("div");
    this.DOM_Input=    this.DOM.find("input")
      .prop({
        tabindex: this.options.tabindex,
        title: hlp.slice(hlp.indexOf("|")+1),
        placeholder: hlp.slice(0,hlp.indexOf("|"))
      });
    this.DOM_DropList= this.DOM.find("ul");

    if ( img ) {
      // varianta s awesome ikonami
      let fa= this.type=='select.auto' ? 'fa-eject fa-flip-vertical' :
        ( this.multi ? 'fa-backward fa-rotate-270' : 'fa-chevron-down');
      this.DOM_Button= jQuery(
        `<button class="fa" tabindex="-1"><i class="fa ${fa}"></i></button>`)
        .prependTo(this.DOM_Closure);
      if ( this.skill==2 ) {
        this.DOM_Button.click ( el => {
          if ( el.shiftKey ) return dbg_onshiftclick(this); /* select */
          this.DOM_Input.focus();
        });
      }
    }

    this.DOM_optStyle(this.DOM_Input,this.options.title,true); // u title ignorovat zarovnání
    if ( this.options.help )
      this.DOM_Closure.attr('title',this.options.help);

    if ( this.options.par && this.options.par.subtype && this.options.par.subtype=='browse' ) {
      // select jako výběr v rámci browse
      this.DOM_DropList.css({'min-width':dl_w,top:16});
    }
    else {
      if ( !this._fc('w') ) {        // pokud je format:'w' bude seznam v neomezené šířce
        this.DOM_DropList.css('width',dl_w);
      }
      else {
        this.DOM_DropList.css('min-width',dl_w);
      }
      if ( this._fc('u') ) {         // pokud je format:'u' budout options nad select
        this.DOM_DropList.css('bottom',this._h);
      }
      else {                         // jinak pod select
        this.DOM_DropList.css('top',this._h+3);
      }
    }

    this.DOM_Input
      .click ( event => {
        this.DOM_Input.trigger('focus');
      })
      .focus ( event => {
        if ( this.DOM_DropList.css('display')=='none') {
          if ( this.options.par && this.options.par.subtype=='browse' && this.Items[0]=='?' )
            this.owner._start2();         // owner obsahuje Show pokud je do něj vnořeno
          Ezer.fce.touch('block',this,'focus');   // informace do _touch na server
          event.target.select();
          this.DOM_usedkeys= false;
          this.DOM_drop_show(true);
          this.DOM_Block.css('zIndex',999);
          this.DOM_focus(true);
          this.fire('onfocus',[]);
          this.value= this._value= this.DOM_Input.val();  // pro změny klávesnicí
          // vytvoření možností autoselect musí být na konci
          if ( this instanceof SelectAuto ) {
            this.ask({cmd:'ask',fce:this.options.par.fce,
              args:[this.DOM_Input.val(),this.options.par],nargs:2},'DOM_newItems');
          }
        }
      })
      .blur (event => {
        this.DOM_drop_hide();
        this.DOM_Block.css('zIndex',2);
        this.DOM_blur();
      })
      .change ( () => {
        event.stopPropagation();
        if ( this._fc('t') )
          this.DOM_changed(Math.max(this._changed,1),1);     // když není format:'t' se zvýrazněním změny
        else {
          this.DOM_changed(Math.max(this._changed,1),0);
          this.fire('onchanged');
        }
      })
      .keydown ( event => {
        event.stopPropagation();
        if ( event.keyCode==9 )
          this.DOM_drop_hide();
      })
      .keyup (event => {
        event.preventDefault();
        event.stopPropagation();
        // up down enter insert
        if ([38,40,13,45].includes(event.keyCode) ) {
          let li, li0= this.DOM_DropList.find('li.selected');
          li= li0;
          this.DOM_usedkeys= true;
          switch (event.keyCode) {
            case 45: {                    // 'insert':
              if ( this.multi ) {
                this._drop_status= 2;
                // když není format:'t' se zvýrazněním změny - zachovej její programatičnost 
                this.DOM_changed(Math.max(this._changed,1),this._fc('t')); 
                li.toggleClass('li-sel');
                this.DOM_seekItems(true);
              }
              break;
            }
            case 38: {                    // ----- up
              li= li.prev();
              if (li0.length && li.length /*&& (li.prev().value!=0 || this.options.typ=='map+')*/ ) {
                li0.removeClass('selected');
                li.addClass('selected');
                li.Ezer_scrollIntoView();
              }
              else if (!li0.length) {
                let lis= this.DOM_DropList.find('li');
                if ( lis.length ) {
                  li= jQuery(lis[lis.length-1]).addClass('selected');
                  li.Ezer_scrollIntoView();
                }
              }
              if ( this instanceof SelectAuto )
                this.DOM_showItem(li);
              else if ( !this.multi )
                this.DOM_seekItem(li,0);
              break;
            }
            case 40: {                     // ----- down
              li= li.next();
              if (li0.length && li.length /*&& li.next().value!=0*/ ) {
                li0.removeClass('selected');
                li.addClass('selected');
                li.Ezer_scrollIntoView();
              }
              else if (!li0.length) {
                let lis= this.DOM_DropList.find('li');
                if ( lis.length ) {
                  li= jQuery(lis[0]).addClass('selected');
                  li.Ezer_scrollIntoView();
                }
              }
              if ( this instanceof SelectAuto )
                this.DOM_showItem(li);
              else if ( !this.multi )
                this.DOM_seekItem(li,0);
              break;
            }
            case 13: {                     // ----- enter
              if ( this.multi ) {
                this.DOM_drop_hide();
              }
              else {
                if (li)
                  this.DOM_seekItem(li);
                else {
                  this.value= this._value;
                  this._key=  0;
                  this.DOM_noneItem();
                }
  //              this.fire('onchanged');
                this._changed= this._changed==2 ? 2 : 0;
              }
              break;
            }
          }
        }
        else if ( event.keyCode==9 ) {  // ----- tab
          if ( this._drop_status>1 )
            this.DOM_drop_hide();
        }
        else if ( event.keyCode==27 ) { // ----- esc navrať původní hodnoty
          this.value= this.original.value;
          if ( this.multi ) {
            this.key(this.original.key);
          }
          else {
            this.DOM_Input.val(this.value);
            this._key= this.original.key;
            if ( this._changed!==2 )
              this.DOM_changed(0);     
          }
          this._drop_status= 1;
          this.DOM_drop_hide();
          this.DOM_Input.trigger('blur');
        }
        else if ( this instanceof SelectAuto ) { // ----- znaky pro SelectAuto
          if ( this._value!=this.DOM_Input.val() ) {
            // uprav vzor a získej nový droplist
            this._key= 0;
            this._value= this.DOM_Input.val();
            this._patt= this._value;
            this.ask({cmd:'ask',fce:this.options.par.fce,
              args:[this._value,this.options.par],nargs:2},'DOM_newItems');
          }
        }
      });
  }
// ----------------------------------------------------------------------------------  DOM drop_show
// ukázání seznamu
  DOM_drop_show () {
    this.DOM_DropList.css('display','block');
    Ezer.DOM_currMulti= this.multi ? this : null;
    this._drop_status= 1;
    // zobrazení vybraných položek
    if ( this.multi ) {     // multiselect
      for (let li of this.DOM_DropList.find('li')) {
        li= jQuery(li);
        if ( this._key.indexOf(li.prop('value'))!=-1 )
          li.addClass('li-sel');
        else {
          li.removeClass('li-sel');
        }
      }
    }
    else {
      this.DOM_DropList.find(`li`).removeClass('selected');
      let li= this.DOM_DropList.find(`li[value=${this._key}]`);
      if ( li.length ) {
        li.addClass('selected');
        li.Ezer_scrollIntoView();
      }
    }
  }
// ----------------------------------------------------------------------------------- DOM drop_hide
// skrytí seznamu a případný signál změny
  DOM_drop_hide (nochange) {
    this.DOM_DropList.css('display','none');
    if ( !nochange && this._drop_status==2 ) {
      this._drop_status= 0;
      this.fire('onchanged');
      this.change(1);
    }
    this._drop_status= 0;
  }
// ------------------------------------------------------------------------------------ DOM showItem
// změní input podle droplist, pro nemulti poznačí změnu
  DOM_showItem (li) {
    if ( li ) {
      if ( this.options.par && this.options.par.subtype=='keys' ) {
        this.DOM_Input.val(li.prop('ivalue'));
      }
      else {
        this.DOM_Input.val(this.Items[li.prop('value')]);
      }
      this._drop_status= 2;
    }
  }
// ------------------------------------------------------------------------------------ DOM noneItem
//      konec select bez zvolené hodnoty
  DOM_noneItem (sel) {
    this.DOM_Input.val(this.value);
    this.DOM_drop_hide(1); // bez change
  }
// ----------------------------------------------------------------------------------- DOM seekItems
//      konec select výběrem hodnot - jen pro multi
  DOM_seekItems (while_changing) { 
    var del='';
    this._key= [];
    this.value= '';
    this.DOM_DropList.find('li.li-sel').each( (i,li) => {
      li= jQuery(li);
      this._key.push(li.val());
      this.value+= del+li.text();
      del= ',';
    });
    this.DOM_set();
    this._drop_status= 2;
  }
// ------------------------------------------------------------------------------------ DOM seekItem
//      konec select výběrem hodnoty
  DOM_seekItem (sel,hide=1) {
    if ( this.options.par && this.options.par.subtype=='keys' ) {
      let txt= sel.prop('ivalue');
      this.value= txt;
      this._key=  sel.val()==999998 ? 0 : txt;
    }
    else if ( this.options.par && this.options.par.subtype=='info' ) {
      let txt= sel.text(), val= sel.val();
      this.options.par.info= sel.prop('info');
      this.value= val ? txt : '';
      this.DOM_set();
      this._key=  val==999998 ? 0 : sel.val();
    }
    else {
      let txt= sel.text(), val= sel.val();
      this.value= txt;
      this.DOM_set();
      this._key=  val==999998 ? 0 : sel.val();
    }
    this.DOM_setCss();
    // když není format:'t' se zvýrazněním změny - zachovej její programatičnost 
    this.DOM_changed(Math.max(this._changed,1),this._fc('t')); 
    this._drop_status= 2;
    if ( hide ) 
      this.DOM_drop_hide();
  }
// ------------------------------------------------------------------------------------ DOM setCss
// upraví CSS podle klíče - jen v součinnosti s fcí selects
  DOM_setCss () {
    if ( this.Css ) {
      this.DOM_Input.removeClass(this.lastCss);
      if ( this.Css[this._key] ) {
        this.lastCss= this.Css[this._key];
        this.DOM_Input.addClass(this.lastCss);
      }
    }
  }
// ------------------------------------------------------------------------------------ DOM set
//      zobrazí hodnotu
  DOM_set () {
    var value= this.options.par && this.options.par.subtype=='keys' ? this.key : this.value;
    var spec= this._f(':');
    if ( value==0 && spec=='e' ) value= '';
    this.DOM_Input.val(value);
    this.DOM_setCss();
  }
// ------------------------------------------------------------------------------------ DOM addItems
//      zobrazí hodnoty z this.Items
  DOM_addItems () {
    var create= function(item,key,css) {
      css= this.Css && this.Css[key] ? this.Css[key] : '';
      let name= this.options.par && this.options.par.subtype=='info' ? item.name : item;
      let li= jQuery(`<li class="${css}" name="${name}">`)
        .appendTo(this.DOM_DropList)
        .mouseover ( event => {
          if (this.DOM_usedkeys) {
            // po použití klávesnice odstraň zvýraznění
            this.DOM_DropList.find('li.selected').removeClass('selected');
          }
          this.DOM_usedkeys= false;
          let li= jQuery(event.target);
          li.addClass('selected');
        })
        .mouseout ( event => {
          let li= jQuery(event.target);
          li.removeClass('selected');
        })
        .mousedown ( event => {
          let li= jQuery(event.target);
          if ( this.multi ) {
            this.DOM_Input.focus();
            if ( event.ctrlKey ) {
              this._drop_status= 2;
              // když není format:'t' se zvýrazněním změny - zachovej její programatičnost 
              this.DOM_changed(Math.max(this._changed,1),this._fc('t')); 
              li.toggleClass('li-sel');
              this.DOM_seekItems(true);
            }
          }
          else {
            this.DOM_seekItem(li);
          }
          return false;
        });
      if ( this.options.par && this.options.par.subtype=='keys' )
        li.prop({ivalue:key}).text(key+' : '+name);
      else if ( this.options.par && this.options.par.subtype=='info' )
        li.prop({value:key,info:item.info}).text(name);
      else
        li.prop('value',key).text(name);
    }.bind(this);
    if ( this.DOM_DropList )
      this.DOM_DropList.empty();
    if ( this.map_options && this.map_options.data_order ) {
      if ( this instanceof SelectMap0 ) {
        create.bind(this)(this.Items[0],0);
      }
      for (var i in this.map_options.data_order) {
        var key= this.map_options.data_order[i];
        create.bind(this)(this.Items[key],key);
      }
    }
    else {
      for(let key in this.Items)
        create(this.Items[key],key);
    }
  }
// ------------------------------------------------------------------------------------ DOM blur
//      odznačení focus elementu formuláře (s uvážením prázdnosti) - bez onchanged
//      které u select vzniká už při výběru alternativy
  DOM_blur () {
    this.fire('onblur');
  }
// ------------------------------------------------------------------------------------ DOM enabled
// ovládá css pro button
  DOM_enabled (on) {
    super.DOM_enabled(on);
    if ( !on ) {
      jQuery(this.DOM_Button).addClass('disabled3');
    }
    else if ( this.skill!=1 ) {
      jQuery(this.DOM_Button).removeClass('disabled3');
    }
  }
}

// =====================================================================================> SelectAuto
//c: SelectAuto
//      Pozn. metoda form.save použije klíč zobrazené hodnoty nebo zobrazenou hodnotu
//      v závislosti na hodnotě atributu par.save='value'|'key'|'key_only'. Defaultní je 'value'.
//      ZMĚNA 151002: po Enter při nenalezeném vzoru vrací napsanou hodnotu a klíč 0
//t: Block,Elem,Select
//s: Block
//i: SelectAuto.onfocus - položka má focus
class SelectAuto extends Select {
//   options: {
//oo: SelectAuto.par - parametry pro autocompleter,
//      obsahující mj. složky (celý objekt je předán funkci par.fce):
//      ; 'fce' : název PHP funkce plnící dynamicky nabídku
//      ; 'save' : má hodnotu 'value' resp. 'key' resp. 'key_only' pokud má vracet hodnotu (default)
//                 resp. klíč resp. klíč a ponechat text vyplněného vzoru
//      ; 'subtype' : rezervované jméno
//   }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _save
// interní hodnota uschovávaná na serveru je klíč zobrazené hodnoty nebo zobrazená hodnota
// v závislosti na hodnotě atributu par.save='key'|'value'. Defaultní je 'value'
  _save  () {
    var vmo;
    if ( this.options.par && this.options.par.save
      && (this.options.par.save=='key' || this.options.par.save=='key_only') ) {
      vmo= {val:this._key};
      if ( this.original.key ) {
        vmo.old= this.original.value;
      }
    }
    else {
      vmo= {val:this.value};
      if ( this.original.key ) {
        vmo.old= this.original.value;
      }
    }
    return vmo;
  }
// ------------------------------------------------------------------------------------ select_set
// SelectAuto.select_set (val)
//      nastaví hodnotu na val, provede dotaz na server a nastaví i klíč a případně info;
//      způsobí onchanged
//e: onchanged
//a: val - hodnota
  select_set  (val) {
    this.value= val;
    this.DOM_set();
    // dotaz na server a nastavení klíče při shodě s nějakou hodnotou
    var x= {cmd:'ask',fce:this.options.par.fce,args:[val,this.options.par],nargs:2};
    return x;
  }
  select_set_  (y) {
    this._key= 0;
    this.Items= y.value;
    this.DOM_addItems();                // může nastavit this._empty nejsou-li nabídky
    // nalezení klíče k hodnotě při první shodě
    if ( this.options.par && this.options.par.subtype=='info' ) {
      for (let key in this.Items) {
        if ( this.Items[key].name==this.DOM_Input.value ) {
          this._key= key;
          this.options.par.info= this.Items[key].info;
          break;
    } } }
    else {
      for (let key in this.Items) {
        if ( this.Items[key]==this.DOM_Input.value ) {
          this._key= key;
          break;
    } } }
    this.DOM_changed(0);
    this.fire('onchanged');
    return 1;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  SelectMap DOM initialize
  DOM_initialize () {
    this._patt= '';                                    // hodnota zadaná jako vzor
  }
// ------------------------------------------------------------------------- SelectAuto DOM showItem
//      konec select bez zvolené hodnoty
  DOM_showItem (li) {
    if ( li ) {
      this.DOM_Input.val(li.val()>999990 ? this._patt : li.text());
    }
  }
// ------------------------------------------------------------------------- SelectAuto DOM seekItem
//      konec select výběrem hodnoty
  DOM_seekItem (sel) {
      this.value= !sel.val() || sel.val()>999990 ? '' : sel.text();
      this._key=  !sel.val() || sel.val()>999990 ? 0 : sel.val();
      if ( this.options.par && this.options.par.save!='key_only') {
        this.DOM_set();
      }
      this._drop_status= 2;
      this.DOM_drop_hide();
  }
// ------------------------------------------------------------------------- SelectAuto DOM addItems
//f: SelectAuto-DOM.DOM_addItems
//      zobrazí hodnoty z this.Items a nastaví _empty=true pokud je jen jedna a to s nulovým klíčem
  DOM_addItems () {
    this.__proto__.__proto__.DOM_addItems.call(this); // aka super.DOM_addItems()
    this._empty= this.Items[0]!==undefined;
  }
// -------------------------------------------------------------------------- SelectAuto DOM changed
//f: SelectAuto-DOM.DOM_changed (on[,quiet=0))
//      označení příznaku změny elementu formuláře, pokud je quiet=0
//      pokud má element klíč (tzn. byl nalezen na serveru) je příznak zelený
  DOM_changed (on,quiet) {
    if ( this.DOM_Input ) {
      // pokud má element zobrazení
      if ( on ) {
        Ezer.fce.touch('block',this,'changed');     // informace do _touch na server
        if ( !quiet ) {
          this.DOM_Input.addClass(this._key ? 'changed_ok' : 'changed');
          this.DOM_Input.removeClass(this._key ? 'changed' : 'changed_ok');
        }
        this._changed= on;
      }
      else if ( this._changed!==2 ) {
        if ( !quiet )
        this.DOM_Input.removeClass('changed');
        this.DOM_Input.removeClass('changed_ok');
        this._changed= 0;
      }
    }
  }
// ------------------------------------------------------------------------- SelectAuto DOM newItems
//f: SelectAuto-DOM.DOM_newItems
//      zobrazí hodnoty podle informace ze serveru
  DOM_newItems (y) {
    this.Items= y.value;
    this.DOM_addItems();
    this.DOM_drop_show();
  }
}

// ======================================================================================> SelectMap
//c: SelectMap
//      Pozn. metoda form.save použije zobrazenou hodnotu
//t: Block,Elem,Select
//s: Block
class SelectMap extends Select {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  constructor (owner,desc,DOM,id,skill,multi) {
    super(owner,desc,DOM,id,skill,multi);
//     var nm= this.start_code.code[0].i= this.self();
    this.start_code.code[0].v= this.owner;
    this.start_code.code[1].v= this.id;
  }
  initialize () {
    super.initialize();
    this.options= {
//oi: SelectMap.options   - mapa.položka
      options: null,
//oi: SelectMap.map_pipe   - mapa.položka
      map_pipe: null
    };
    this.sel_options= null;
    this.start_code=
      {level:'select',code:[{o:'v',v:'?'},{o:'v',v:'?'},{o:'m',i:'_part',a:1},{o:'m',i:'_options_load'}]};
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _options_load
  _options_load () {
    this.value= '';
    this._key= this.multi ? [] : 0;
    // vytvoř z mapy seznam možností
    var m= [];
    Ezer.assert(1==Ezer.run_name(this.options.options,this.owner.owner,m)
      ,'options:'+this.options.options+' je chybné jméno map',this);
    this.map_options= m[1];
    this.Items= this instanceof SelectMap0 ? {0:''} : {};
    for (var im in m[0]) {
      this.Items[im]= m[0][im];
    }
    this.DOM_addItems();
    if ( this.options.map_pipe ) {
      // zpracuj atribut map_pipe
      Ezer.assert(1==Ezer.run_name(this.options.map_pipe,this.owner.owner,m)
        ,'map_pipe:'+this.options.map_pipe+' je chybné jméno map',this);
      this.map_pipe= m[0];
    }
    for (var key in this.Items) {
      this._key= this.multi ? [key] : key;
      this.key(this._key);
      break;
    }
    return true;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _check
// test integrity bloku po jeho dokončení
  _check  () {
    Ezer.assert(this.options.options,"Blok select typu map musí obsahovat atribut options",this);
  }
// -------------------------------------------------------------------------- SelectMap selects
//fm: SelectMap.selects ([key,[cond]])
//      obnoví seznam volitelných hodnot z mapy uvedené v options, pokud je definován
//      argument key bude select nastaveno na tuto hodnotu, jinak bude mít hodnotu 0;
//      Jako vedlejší efekt obnoví mapy uvedené v příkazu select;
//      cond je nepovinná dodatečná podmínka na položky tabulky _cis
//      POZOR: metoda jen zahájí asynchronní operaci, nečeká na ukončení
  selects (key,cond) {
    key= key||0;
    cond= cond||1;
    // najdi mapu uvedenou v options
    var m= [];
    Ezer.run_name(this.options.options,this.owner.owner,m); // m[1] je mapa
    var code= [
      {o:'v',v:m[1]}, {o:'v',v:cond}, {o:'x',i:'map_load',a:1},
      {o:'v',v:this}, {o:'m',i:'_options_load'},
      {o:'v',v:this}, {o:'v',v:key}, {o:'m',i:'key',a:1}
    ];
    new Eval(code,this,[this,null,null,null,null,-1],'selects');
    return true;
  }
// -------------------------------------------------------------------------- SelectMap set
//fm: SelectMap.set (val)
//a: val - hodnota
  set  (val) {
    this.value= val;
    if ( this.multi ) {
      // nalezení klíčů k hodnotám
      this._key= [];
      var values= this.value.split(',');
      for (let value of values) {
        for (var key in this.Items) {
          if ( this.Items[key]==value ) {
            this._key.push(key);
            break;
          }
        }
      }
    }
    else {
      // nalezení klíče k hodnotě
      for (var key in this.Items) {
        if ( this.Items[key]==val ) {
          this._key= key;
          break;
        }
      }
    }
    this._changed= 0;
    this.DOM_set();              // zobrazení v DOM z this.value
    this.DOM_changed(0);
    return 1;
 }
// -------------------------------------------------------------------------- SelectMap get
//fm: SelectMap.get ([options=0])
//      vrátí hodnotu podle nastavení map_pipe, pokud je options=1 tak podle options
//a: options - 0|1
  get  (options) {
    var val= this.value;
    if ( options ) {
      if ( this.Items ) {
        val= this.Items[this._key]||'';
      }
    }
    else {
      if ( this.map_pipe && val ) {
        val= this.map_pipe[this._key]||'';
      }
    }
    return val;
 }
// -------------------------------------------------------------------------- SelectMap init
//fm: SelectMap.init ([init_values=0])
//      nastaví hodnotu na prázdnou nebo pro init_values==1 na defaultní hodnotu
//      nebo pro init_values==2 na defaultní s nastavením elementu jako change bez onchange;
//      funkce nevymaže seznam hodnot - jsou stále dány atributem map
//a: init_values : >0 nastaví hodnotu podle atributu value, ==2 označí jako změněné
  init  (init_values) {
    this.value= '';
    this._changed= 0;
    if ( init_values ) {
      if ( this.owner._option && this.owner._option.x && this.owner._option.x==1
        && this._f('x')>=0 ) {
        this._key= this.fixed_value;
      }
      else {
        this._key= this.options.value||(this.multi ? '' : 0);
      }
      this.key(this._key);      // pro multi provede normalizaci tj. string->array
      this.DOM_set();
      if ( init_values==2 && this.options.value!==undefined)
        this.change(1);
      else
        this.DOM_changed(0);
    }
    else {
      this._key= this.multi ? [] : 0;
      this.DOM_empty(true);
      if ( this._changed ) {
        this.plain();
      }
    }
    this.original.value= this._key;
    this.original.key= null;
    return 1;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  SelectMap _load
// interní hodnota uschovávaná na serveru je klíč zobrazené hodnoty
  _load  (val,key) {
    this.original.value= val;
    this.original.key= key;
    this.key(val);
    this._changed= 0;
    this.DOM_changed(0);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  SelectMap _save
// interní hodnota uschovávaná na serveru je klíč zobrazené hodnoty
  _save  () {
    var vmo= {val: this.multi ? this._key.join(',') : this._key};
    if ( this.original.key ) {
      vmo.old= this.original.value;
    }
    return vmo;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  SelectMap _fixed_save
// uschovej klíč do fixed_value
  _fixed_save () {
    this.fixed_value= this.key();
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  SelectMap _fixed_load
// vrať fixovaný klíč
  _fixed_load () {
    this.key(this.fixed_value);
  }
}
// aliasy
//fm: SelectMap.select_key ([key])  (obsolete)
SelectMap.prototype.select_key= SelectMap.prototype.key;

// =====================================================================================> SelectMap0
//c: SelectMap0
//      výběr s prázdnou hodnotou pro klíč 0
//t: Block,Elem,Select
//s: Block
class SelectMap0 extends SelectMap {
}

// ===========================================================================================> List
//c: List ()
//      řádkový seznam elementů
//t: Block,Elem
//s: Block
class List extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
//on: List.rows   - výška řádku v px
//-
    this.last= -1;                                             // index poslední group
  }
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    // bez vložení podčástí
    this.part= {};
    this.DOM_add(DOM);
  }
// ------------------------------------------------------------------------------------ init
//fm: List.init ()
//      vyprázdní seznam
  init () {
    this.DOM_destroy_rows();
    this.part= {};
    this.last= -1;
    return 1;
  }
// ------------------------------------------------------------------------------------ get
//fm: List.get ()
//      vrátí počet řádků elementů v seznamu
  get () {
    return this.last+1;
  }
// ------------------------------------------------------------------------------------ add
//fm: List.add ()
//      přidá na konec seznamu nový řádek elementů a posune ukazatel pro další řádek
//r: index přidaného řádku
  add () {
    // vložení group
    this.last++;
    var group, desc= {type:'list.row',options:{}};
    group= new ListRow(this,desc,this.DOM_Block,this.last);
    this.part[this.last]= group;
    // vložení podčástí do group
    group.subBlocks(this.desc,group.DOM_Block);
    Ezer.app.start_code(group);
    return this.last;
  }
// ------------------------------------------------------------------------------------ load
//fx: List.load (fce[,arg,..])
//      přidá do seznamu podle informací ze serveru
  load () {
    return 1;
  }
// ===========================================================================================> List
//c: List-DOM
//   řádkové zobrazení dat
// ------------------------------------------------------------------------------------ DOM add
// vytvoří kontejner na řádky
  DOM_add () {
    this.DOM= this.DOM_Block= jQuery(`<div class="List3">`)
      .css(this.coord())
      .appendTo(this.owner.DOM_Block);
  }
// ------------------------------------------------------------------------------------ DOM destroy_rows
// zruší všechny řádky
  DOM_destroy_rows () {
    this.DOM_Block.empty();
  }
}

// ========================================================================================> ListRow
//c: ListRow ()
//      řádek seznamu elementů
//t: Block
//s: Block
class ListRow extends Block {
//   options: {},
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    // bez vložení podčástí
    this.DOM_add(DOM);
  }
// ========================================================================================> ListRow
//c: ListRow-DOM
//   části v řádkovém zobrazení dat
// ------------------------------------------------------------------------------------ DOM add
// vytvoří kontejner na nový řádek v List
// z-index musí tvořit klesající posloupnost kvůli překryvům SelectDrop
  DOM_add () {
    var h= this.owner.options.rows||20;
    this.DOM= this.DOM_Block= jQuery(`<div class="ListRow3">`)
      .css({left:0,top:h*this.owner.last,width:this.owner._w,height:h,zIndex:-this.owner.last})
      .appendTo(this.owner.DOM_Block);
  }
}

// =========================================================================================> Browse
//c: Browse
//      tabulkové zobrazení dat s mezipamětí - implementace 2
//t: Block
//s: Block
//i: Browse.onclick - kliknutí na výběrový element (pravý horní rožek)
class Browse extends Block {
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
//on: Browse.rows   - počet datových řádků načtených do paměti
//-
//os: Browse.format  - úprava zobrazení browse ('n': nezobrazovat, 'd': potlačení akcí myší)
//-
//on: Browse.qry_rows   - počet dotazových řádků
//-
//on: Browse.buf_rows   - počet řádků načítaných do bufferu (má-li být větší než rows)
//-
//os: Browse.group_by   - fráze MySQL
//-
//on: Browse.wheel      - počet řádků přeskočených kolečkem myši (default=počet řádků/2)
//-
//oo: Browse.optimize   - objekt předávaný na server, popis je v ezer3.php
//-
//i: Browse.onrowclick - klik na řádku (parametrem je index řádku, první má index 1)
//-
//i: Browse.onchange - interaktivní změna dotazu (v qry_rows)
//-
//i: Browse.onchoice - výběr řádku klávesou Ins
//-
//os: Browse.key_id   - jméno sloupce s klíčem pro browse_load ap. (pokud není udáno, odvozuje se z použité tabulky)
//-
    // Ezer.Block
    this.DOM= DOM;
    this.owner= owner;
    this.skill= skill;
    if ( id ) this.id= this._id= id;
    if ( id && owner && owner.part ) owner.part[id]= this;
    this.type= desc.type;
    this.desc= desc;
    Object.assign(this.options,desc.options);
    if ( isNaN(this.options.rows) ) {
      // rows je zadáno konstantou
      var m= [], x= Ezer.code_name(this.options.rows,m,this.owner);
      if ( x && x[0] && x[0].type=='const' ) {
        this.options.rows= this._const(m[m.length-1]);
      }
      else Ezer.error("ERROR RUN pro atribut rows nelze určit konstantu "+this.options.rows);
    }
    this._coord();
    this._check();
    // pak bude parent(owner,desc,DOM,id,skill)
    this.options.wheel= this.options.wheel||Math.round(this.options.rows/2);
    this.bmax= Math.max(this.options.buf_rows||0,this.options.rows);
    this.tmax= this.options.rows;
    this.DOM_add1();
    // vložení podčástí
    this.subBlocks(desc,this.DOM_Block);
    this.DOM_add2();
    this.DOM_addEvents();
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
    this.selected_op= 'ignore';                // co budeme s klíči dělat ... viz fce selected
    this.query_seek= 0;                        // 1 = podmínka v get_query je pro seek (Enter+shift)
    this.cond= null;                           // aktuální pro WHERE ...    expr
    this.order= null;                          // aktuální pro ORDER BY ... id [ASC|DESC]
    this.order_by= null;                       // objekt browse_clmn podle kterého se řadí
//os: Browse.css_cell  - viz css_rows
    this.css= {};                              // objekt vytvořený podle atributu css_rows
//os: Browse.css_rows   - hodnota 'clmn,[v1:]s1,[v2:]s2,...' určuje styl vybraný podle
//      hodnoty sloupce clmn (neuvedené vi je defaultně rovno i, prázdné vi určuje defaultní styl)
//      pokud má řádek ve sloupci hodnotu vi má řádek resp. buňka přidánu css-třídu cssi
//      pokud i není v css_rows resp. v css_cell žádná třída se nepřidává
    this.css_clmn= null;                       // clmn řídící obarvení
    this.css_default= null;                    // defaultní styl
    this.first_query= null;                    // první dotazový input
    // stavové informace pro scroll
    this.s= 0;                                 // počátek SELECT                               0
    this.slen= 0;                              // délka SELECT
    this.buf= [];                              // buffer načtených řádků (dekódovaných) - pro Show
    this.keys= [];                             // pole klíčů načtených řádků
    this.keys_sel= [];                         // seznam klíčů vybraných INS
    this.b= -1;                                // záznam na začátku bufferu                   -1..slen-1
    this.blen= 0;                              // naplněná délka bufferu
    this.bmax= 0;                              // maximální délka bufferu = atribut buf_rows
    this.t= -1;                                // záznam na začátku tabulky                   -1..slen-1
    this.r= -1;                                // aktivní záznam tabulky                      -1..slen-1
    this.tact= 0;                              // aktivní řádek tabulky                        0..tmax
    this.tlen= 0;                              // naplněná délka tabulky                       0..tmax
    this.tmax= 0;                              // maximální délka tabulky = atribut rows
    this.scrolling= false;                     // probíhá čtení fcí _browse_scroll
    this.enabled= true;                        // akce myší jsou povoleny
    // stavové informace pro další funkce
    this.get_query_pipe='';                    // případné modifikátory pro formát q@
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  start
  start (codes,oneval) {
    super.start(codes,oneval);
//     this.parent(codes,oneval);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  browse_snap+
//fm: Browse.browse_snap ([data=1])
//      snapshot do trace, pokud data=0 bude vynechán výpis obsahu keys a buf
  browse_snap (data) {
    if ( data )
      Ezer.debug({
        tact:this.tact,scroll:this.scrolling?'true':'false',
        slen:this.slen,b:this.b,blen:this.blen,bmax:this.bmax,t:this.t,r:this.r,tlen:this.tlen,
        tmax:this.tmax,keys_sel:this.keys_sel,keys:this.keys,buf:this.buf});
    else
      Ezer.debug({
        tact:this.tact,scroll:this.scrolling?'true':'false',
        slen:this.slen,b:this.b,blen:this.blen,bmax:this.bmax,t:this.t,r:this.r,tlen:this.tlen,
        tmax:this.tmax});
    return 1;
  }
// ------------------------------------------------------------------------------------ set attrib
//fm: Browse.set_attrib (name,val[,desc=])       nedokumentováno, může být změněno
//      pokud name='rows' změní počet řádků browse, pro jiná jména volá Block.set_atrib
//a: name - 'rows'
//   val - nový počet řádků
  set_attrib (name,val,desc) {
    if ( name=='rows' ) {                                     //Ezer.trace('*','browse.set_attrib');
      if ( val>0 ) {    // počet řádků musí být kladný
        var old_key= this.browse_key();
        // změň atribut
        super.set_attrib(name,val,desc);
        // odstraň starý obraz
        this.DOM_remove(true);
        // inicializuj stavové promenné
//         this.s= this.slen= this.blen= this.bmax= this.tact= this.tlen= this.tmax= 0;
//         this.buf= this.keys= this.keys_sel= [];
//         this.b= this.t= this.r= -1;
        // definuj nové hodnoty
        this.options.rows= +val;
        this.bmax= Math.max(this.options.buf_rows||0,this.options.rows);
        this.tmax= this.options.rows;
        this.tact= 0;
        // vybuduj nový browse
        this.DOM_add1(true);
        this.subBlocks(this.desc,this.DOM_Block,null,'dom_only');
        this.DOM_add2(true);
        this.DOM_addEvents();
        // zobrazení viditelné části
        this.t= this.b;
        this.r= this.b;
        this.tlen= Math.min(this.tmax,this.blen);
        this.tact= this.tlen ? 1 : 0;
        // pokus nastavit původní řádek
        var key= old_key;
        for (var iv= 0; iv<this.blen; iv++) {
          if ( this.keys[iv]==key ) {             // projdi klíče přečtených řádků browse
            this._row_move(this.b+iv);
            break;
          }
        }
        this.DOM_show();
      }
    }
    else
      super.set_attrib(name,val,desc);
    return 1;
  }
// ------------------------------------------------------------------------------------ selected+
//fm: Browse.selected (op[,param[,option]])
//      ovládá chování browse vzhledem vybraným řádkům
//a: set        - nastaví klíče podle daného seznamu (string s klíči oddělenými čárkou)
//   unset      - zruší výběr klíčů podle daného seznamu
//   get        - vrátí seznam param prvních (pro option='D' posledních) klíčů  nebo všech klíčů, pokud je param 0
//   clear      - zruší výběr param prvních klíčů nebo všech klíčů, pokud je param 0
//   set_page   - zruší výběr a nastaví jako vybrané ty viditelné
//   add_page   - přidá k výběru ty viditelné
//   refresh    - obnoví zobrazení výběru
//   toggle     - změní stav aktivního řádku, pokud je param=1 nebude vyvoláno onrowclick
//   use        - operace browse_load, browse_seek budou vracet jen vybrané řádky
//   ignore     - operace browse_load, browse_seek se budou chovat jakoby nic
//   key        - vrátí param tý klíč (pro option='D' od konce)
//   this       - vrátí stav aktivního řádku
  selected (op,param,option) {
    var result= 1;
    switch ( op ) {
      case 'refresh': { // obnoví označení výběru
        for (let i= 1; i<=this.tlen; i++) {
          let key_i= Number(this.keys[this.t+i-1-this.b]);
          this.DOM_selected(i,this.keys_sel.includes(key_i));
        }
        this._set_css_rows();
        this.DOM_show_status();
        break;
      }
      case 'toggle': { // změní stav aktivního řádku
        let key= this.keys[this.t+this.tact-1-this.b],
            ikey= this.keys_sel.indexOf(key);
        if ( ikey>=0 )
          this.keys_sel.splice(ikey,1);
        else
          this.keys_sel.push(key);
        this._css_row(this.tact);
        this.DOM_hi_row(this.t+this.tact-1,param||0);
        break;
      }
      case 'this': { // vrátí stav aktivního řádku
        let key= this.keys[this.t+this.tact-1-this.b],
            ikey= this.keys_sel.indexOf(key);
        if ( ikey<0 )
          result= 0;
        break;
      }
      case 'set': { // nastaví klíče podle daného seznamu (string s klíči oddělenými čárkou)
        if ( param )
          this.keys_sel= typeof(param)=='string' ? param.split(',').map(Number) : [param];
        else
          this.keys_sel= [];
        this.selected('refresh');
        break;
      }
      case 'unset': { // zruší klíče podle daného seznamu
        if ( !param ) break;
        let ikey, p= param.split(',').map(Number);
        for (let i= 0; i < p.length; i++) {
          ikey= this.keys_sel.indexOf(p[i]);
          if ( ikey >= 0 ) {
            this.keys_sel.splice(ikey,1);
          }
        }
        this.selected('refresh');
        break;
      }
      case 'get': { // vrátí seznam param prvních (pro záporné posledních) klíčů nebo všech klíčů, pokud je param vynecháno nebo 0
        let n, desc, del= '';
        desc= option ? option=='D' : false;
        n= param ? Math.min(parseInt(param),this.keys_sel.length) : this.keys_sel.length;
        result= '';
        for (var i= 0; i<n; i++) {
          var k= desc ? this.keys_sel.length-1 - i : i;
          result+= del+this.keys_sel[k];
          del= ',';
        }
        break;
      }
      case 'set_page': { // zruší výběr a nastaví jako vybrané ty viditelné
        this.keys_sel= [];
        for (let i= 1; i<=this.tlen; i++) {
          this.DOM_selected(i,true);
          if ( this.keys[this.t+i-1-this.b] )
            this.keys_sel.push(this.keys[this.t+i-1-this.b]);
        }
        this._set_css_rows();
        this.DOM_show_status();
        break;
      }
      case 'add_page': { // přidá k výběru ty viditelné
        for (let i= 1; i<=this.tlen; i++) {
          this.DOM_selected(i,true);
          var key= this.keys[this.t+i-1-this.b];
          if ( key && this.keys_sel.indexOf(key)<0 )
            this.keys_sel.push(key);
        }
        this._set_css_rows();
        this.DOM_show_status();
        break;
      }
      case 'clear': { // zruší výběr param prvních klíčů nebo všech klíčů, pokud je param 0
        if ( param ) {
          this.keys_sel.splice(0,parseInt(param));
          this._set_css_rows();
        }
        else {
          this.keys_sel= [];
          for (let i= 1; i<=this.tlen; i++) {
            this.DOM_selected(i,false);
          }
        }
        this._set_css_rows();
        this.DOM_show_status();
        break;
      }
      case 'use':      // operace browse_load, browse_seek budou vracet jen vybrané řádky
      case 'ignore': { // operace browse_load, browse_seek se budou chovat jakoby nic
        this.selected_op= op;
        break;
      }
      case 'key': {   // vrátí param-tý klíč nebo 0
        let desc= option ? option=='D' : false,
            i= parseInt(param),
            k= desc ? this.keys_sel.length-1 - i : i;
        result= this.keys_sel[k]||0;
        break;
      }
    }
    return result;
  }
// ------------------------------------------------------------------------------------ browse init+
//fm: Browse.browse_init ()
//      vynuluj klíče a tabulku
//e: onblur
  browse_init  () {
    this.browse_fill('','',0,'','','');  // vyprázdnění
    //this.css= {};
    this.blur();
    this.keys_sel= [];
    return 1;
  }
// ------------------------------------------------------------------------------------ browse focus+
//fm: Browse.browse_focus ()
//      označení řádku - vyvolej událost onfocus ale ne onrowclick
  browse_focus  () {
    if ( this.tact )
      this.DOM_hi_row(this.t+this.tact-1,true,true);
    return 1;
  }
// ------------------------------------------------------------------------------------ focus+
//fm: Browse.focus ()
//      označení browse jako aktivní
//e: onfocus - byl označen browse
  focus  () {
    this.DOM_focus();
    return 1;
  }
// ------------------------------------------------------------------------------------ blur+
//fm: Browse.blur ([row_blur==0])
//      zrušení označení browse, vyvolej událost ONBLUR
//      pokud je row_blur==1 odznačí se i aktivní řádek
//a: row_blur - 1 : odznačí se aktivní řádek
//e: onblur - bylo zrušeno označení řádku
  blur  (row_blur) {
    this.fire('onblur');
    this.DOM_blur();
    if ( row_blur==1 )
      this.DOM_clear_focus();
    return 1;
  }
// ------------------------------------------------------------------------------------ keydown
//fm: Browse.keydown (page_up|page_down|insert)
//      simulace stisku dané klávesy
  keydown (key) {
    this.DOM_riseEvent('keydown_'+key);
    return 1;
  }
// ------------------------------------------------------------------------------------ enable
//fm: Browse.enable ([on])
//      pokud je on=0 potlačí citlivost na klik, dvojklik a kolečko myši na datový řádek
//      a skryje řádek, pokud je on=1 obnoví normální citlivost a ukazatel řádku
//      bezparametrická metoda vrátí stav enable
  enable (enabled) {
    var ok= 1;
    enabled= enabled=="0" ? 0 : enabled;
    if ( enabled===undefined ) {
      ok= this.enabled;
    }
    else {
      this.enabled= enabled;
      if ( enabled ) {
        this.DOM_show_focus();
//         this.slider.attach();
      }
      else {
        this.DOM_clear_focus();
//         this.slider.detach();
      }
      this.DOM_enabled(enabled);
    }
    return ok;
  }
// ------------------------------------------------------------------------------------ browse count+
//fm: Browse.browse_count ()
//      vrací celkový počet záznamů
  browse_count  () {
    return this.slen;
  }
// ------------------------------------------------------------------------------------ browse active+
//fm: Browse.browse_active ([tact])
//      pokud má browse aktivní řádek, vrátí jej (1..tlen), jinak 0
//      je-li voláno s parametrem, nastaví daný řádek jako aktivní
  browse_active  (tact) {
    var ret;
    if ( tact==undefined )
      ret= this.tact;
    else {
      Ezer.assert(1<=tact && tact<=this.tlen,"browse_active: chybné číslo řádku");
      this.DOM_clear_focus(1);
      this.tact= tact;
      this.DOM_hi_row(this.t+this.tact-1,1,1);
      ret= 1;
    }
    return ret;
  }
// ------------------------------------------------------------------------------------ browse key+
//fm: Browse.browse_key ()
//      klíč aktivního řádku nebo 0
  browse_key  () {
    return this.tact ? this.keys[this.r-this.b]||0 : 0;
  }
// ---------------------------------------------------------------------------------==> . raise+
//fm: Browse.raise (event[,key[,info[,row[,noevent=false]]]])
//      simulace kliknutí na řádek se zadaným klíčem, nebo na první zobrazený řádek;
//      klíč vyznačeného řádku lze získat funkcí browse_key;
//      pokud je noevent=1 nevyvolává se event;
//      pokud je browse prázdný nebo je definováno key a řádek není načtený tak raise selže
//      (toho lze využít v alternativě k volání browse_seek)
//a: event - onrowclick
//   key - primární klíč záznamu (nepovinně)
//   info - uživatelská informace, předaná onrowclick (nepovinně)
//   row - řádek v tabulce (nepovině) - pokud je udán (1..tlen), má přednost před klíčem
//   noevent - pokud je 1 nevyvolá se uživatelská obsluha onrowclick
  raise  (event,key,info,row,noevent) {
//                                                 Ezer.trace('*','onrowclick:'+row);
    var irow, ok= 1;
   raised:
    switch ( event ) {
    case 'onrowclick':
      if ( !this.blen ) {                       // pokud je browse prázdný
        ok= 0;
      }
      else if ( row ) {
        this.DOM_hi_row(this.t+row-1,noevent,true);
      }
      else {
        irow= 0;
        key= key||this.keys[this.t-this.b];
        info= info||0;
       with_key:
        if ( key ) { // je požadováno nastavení řádku s určitým klíčem, nebo nic
          for (var ib= 0; ib<this.blen; ib++) {
            if ( this.keys[ib]==key ) {  // projdi klíče načtených řádků browse v bufferu
              this.owner._key= key;      // nastav aktiální klíč formuláře (jako po kliknutí)
              irow= ib;
              if ( ib != (this.r-this.b) ) {
                // funkce _row_move zajistí viditelnost záznamu r (0..slen-1) včetně onrowclick
                this._row_move(this.b+ib,noevent);
              }
              else {
                // zajisti provedení onrowclik i při neposunutí záznamu
                this.DOM_hi_row(this.b+ib,noevent,true);
              }
              break with_key;
            }
          }
          if ( !irow ) {
            // řádek není načtený - raise selže - lze aplikovat např. browse_seek
            ok= 0;
          }
        }
        else {
          this.DOM_hi_row(this.t,noevent,true);
        }
//           // stará implementace pro buf_rows=rows
//           for (var ir= 1; ir<=this.tlen; ir++) {
//             if ( this.keys[this.t+ir-1-this.b]==key ) {  // projdi klíče zobrazených řádků browse
//               this.owner._key= key;
//               irow= ir;
//               break with_key;
//             }
//           }
//         }
//         // pokud je browse neprázdný označ požadovaný nebo první řádek a zavolej uživatelskou obsluhu
//         if ( irow <= this.tlen ) {
//           this.DOM_hi_row(this.t+irow-1,noevent,true);
//         }
      }
      break;
    default: Ezer.error("ERROR RUN 'raise' v browse nelze '"+event+"'");
    }
    return ok;
  }
// ------------------------------------------------------------------------------------ init_queries+
//fm: Browse.init_queries ([reload=1])
//      zruš všechny výběrové podmínky a provede základní výběr, pokud není reload=0
//a: reload - pokud je reload=0 nebude po zrušení pomínek proveden dotaz
  init_queries  (reload) {
    reload= reload===undefined ? 1 : reload;
    this.query_seek= 0;       // výběr bez shiftu
    this.DOM_focus();
    for ( var ic in this.part ) {
      var clmn= this.part[ic];
      if ( clmn.skill && clmn.qry_type ) {
        for ( var iq= 1; iq<=this.options.qry_rows; iq++ ) {
          clmn.DOM_qry_set(iq,'');
        }
      }
    }
    if ( reload )
      this._ask_queries();
    return 1;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _ask_queries+
// proveď výběr
// pokud je no_focus, jde o volání kvůli refresh a oldkey může obsahovat žádaný browse_key
// pokud je seek_only, zavolá browse_seek s podmínkou místo browse_load
  _ask_queries (no_focus,oldkey,seek_only) {
    if ( no_focus ) {  // voláno kvůli refresh
      let continuation=  this.findProc('onrefreshed')
        ? {fce:this._ask_queries_,args:[],stack:true,obj:this} : null;
      let code= [{o:'x',i:'browse_refresh',a:1}];
      new Eval(code,this,[this,oldkey],'refresh',continuation);
    }
    else if ( seek_only ) {
      this.DOM_focus();
      var code= [{o:'x',i:'browse_seek',a:1}];
      if ( this.findProc('onchange') ) code.push({o:'c',i:'onchange'});
      var wcond= this.get_query(false);           // podmínky za WHERE
      this.query_seek= 1;       // výběr se shiftem
      new Eval(code,this,[this,wcond],'seek');
    }
    else {
      this.DOM_focus();
      let code= [{o:'x',i:'browse_load',a:5}];
      if ( this.findProc('onchange') ) code.push({o:'c',i:'onchange'});
      this.query_seek= 0;       // výběr bez shiftu
      new Eval(code,this,[this,null,null,null,null,-1],'query');
    }
    return true;
  }
  _ask_queries_ () {
    new Eval([{o:'c',i:'onrefreshed'}],this,[],'refreshed');
  }
// ------------------------------------------------------------------------------------ get_query+
//fm: Browse.get_query ([having=false])
//      vrátí aktuální dotaz v browse ve tvaru: clmn1-qry AND clmn2-qry AND ...
//      (nezohledňuje sql_pipe pro formát q@)
//   get_query_pipe:'',                            // případné modifikátory pro formát q@
  get_query (having) {
    having= having ? true : false;
    var qry= '', q, del= '', part;
    this.get_query_pipe= '';
    for ( var ic in this.part ) {
      part= this.part[ic];
      if ( part instanceof Show && (q= part.get_query(having)) ) {
        qry+= del+q;
        this.get_query_pipe+= part.get_query_pipe;
        del= ' AND ';
      }
    }
    return qry;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _get_query
// vrátí objekt {polozka:[formát,vzor/1,...],...} pro položky s neprázdným vzorem
// kde formát=q|q/... podle atributu show.format, vzor/i je text vzoru na i-tém řádku
  _get_query  () {
    var cond= {};
    for ( var ic in this.part ) {
      let q, part= this.part[ic];
      if ( part instanceof Show && (q= part._get_query()) ) {
        cond[ic]= q;
      }
    }
    return cond;
  }
// ------------------------------------------------------------------------------------ browse map+
//fx: Browse.browse_map (fce)
//      zavolání funkce 'fce' na serveru: fce(keys), kde 'keys' jsou klíče vybraných řádků
//a: fce - jméno funkce v PHP modulu
//r: hodnota - vrácená funkcí
  browse_map  (fce) {
    var x= {cmd:'browse_map', fce:fce, keys:this.keys_sel};
    return x;
  }
  browse_map_  (y) {
    return y.value;
  }
// ------------------------------------------------------------------------------------ browse select+
//fx: Browse.browse_select (cond[,quiet=false)
//      nastavení všech vybraných řádků do keys_sel
//a: cond - MySQL podmínka umístěná za WHERE
  browse_select  (cond,quiet) {
    // zapomeň podmínku
    var selected_op= this.selected_op;          // vypni nastavené selected
    this.selected_op= 'ignore';
    var x= this._params({cmd:'browse_select'},cond,null,null,null,null,1);
    x.quiet= quiet||0;
    this.selected_op= selected_op;
    return x;
  }
  browse_select_  (y) {
    this.keys_sel= y.keys ? y.keys.split(',').map(Number) : [];
    this.selected('refresh');
    if ( !y.quiet )
      this.fire('onchoice',[0]);
    return true;
  }
// ------------------------------------------------------------------------------------- browse keys
//fx: Browse.browse_keys ()
//      navrácení seznamu klíčů aktuálního stavu browse
  browse_keys  (cond) {
    // zapomeň podmínku
    var selected_op= this.selected_op;          // vypni nastavené selected
    this.selected_op= 'ignore';
    var x= this._params({cmd:'browse_select'},null,null,null,null,null,1);
    this.selected_op= selected_op;
    return x;
  }
  browse_keys_  (y) {
    return y.keys;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - _browse_init1+
// počáteční hodnoty souboru, buferu, tabulky
// známe index prvního záznamu
  _browse_init1 (source) {
    this._source= 'fill';                       // metoda získání záznamů
    this.slen= 0;
    this.b= -1;
    this.blen= this.bmax;
    this.t= -1;
    this.r= -1;
    this.tlen= this.tmax;
    // staré hodnoty buferů budou zapomenuty
    this.buf= [];
    this.keys= [];
    // zrušení případných title
    jQuery(this.DOM_tbody).find('td').removeAttr('title');
  }
// ------------------------------------------------------------------------------------ browse fill+
//fm: Browse.browse_fill (data[,del='|',first=0[,cond,order,having]])
//      načtení dat do řádků browse, první hodnota na řádku bude použita jako klíč;
//      na začátku mohou být data nevložená do browse (lze je zpřístupnit funkcí split)
//      pokud je first>0; vrací 1 (počet řádků viz fce browse_count)
//a:    data - posloupnost hodnot oddělená oddělovačem
//      del - omezovače dat
//      first - první z dat načtený do browse
//      cond,order,having - budou zapamatovány pro případný následný browse_load
  browse_fill (data,del,first,cond,order,having) {
    this.DOM_enable_reload(false);
    del= del||'|';
    first= first||0;
    this.cond= cond||this.cond||'';
    this.order= order||this.order||'';
    this.having= having||this.having||'';
    var d= data ? data.split(del) : [];
    var di= first, dn= d.length, count= 0;
    this._browse_init1('fill');                 // inicializace bufferu
    while (di<dn) {
      this.keys[count]= Number(d[di]);
      this.buf[count]= {};
      for (var vi in this.part) {               // vi je identifikátor show
        if ( this.part[vi] instanceof Show ) {
          // hodnota bude do buf transformována show._load
          this.buf[count][vi]= this.part[vi]._load(d[di++]);
        }
      }
      count++;
    }
    // definice stavu
    this.slen= this.blen= count;
    this.tlen= Math.min(this.tmax,count);
    this.tact= count ? 1 : 0;
    if ( count ) {
      this.b= this.t= this.r= 0;
    }
    // zobrazení
    this.DOM_show();
    return 1;
  }
// ------------------------------------------------------------------------------------ browse row+
//fx: Browse.browse_row ([row=active|1])
//      přečte aktivní řádek browse a obnoví jeho zobrazení, nevyvolá onrowclick
  browse_row () {
    var x= {};
    // vytvoř parametry dotazu
    if ( this._source=='ask' ) {
      x= this._params({cmd:'browse_load',subcmd:'browse_row'},null,null,null,this.r,1,0);
      x.oldkey= this.keys[this.t+this.tact-1-this.b];
      x.rows= 1;
    }
    else {
      Ezer.assert(this._source=='load',"browse_row lze pouzit jen po browse_load");
      x= this._params({cmd:'browse_load'},null,null,null,this.r,1,0);
      x.rows= 1;
    }
    return x;
  }
  // x - {table:..,cond:...,order:...}
  // y - {values:[[id1:val1,...]...],rows:...}
  browse_row_ (y) {
//                                                         Ezer.debug(y,'browse_row_');
    // načtení dat řádku
    var rows= Number(y.rows), bi= this.r-this.b;
    if ( rows ) {
      // naplň řádek daty
      this.buf[bi]= {};
      for (var vi in y.values[1]) {             // vi je identifikátor show
        // hodnota bude do buf transformována show._load
        this.buf[bi][vi]= this.part[vi]._load(y.values[1][vi]);
        if ( this.keys[bi]===undefined && this.part[vi].data && this.part[vi].data.id==y.key_id ) {
          // klíč je zapsán jen podle první položky, která jej má v data.id
          this.owner._key= this.keys[bi]= this.buf[bi][vi];
        }
        this.part[vi].DOM_show(this.r);
      }
      this._css_row(this.tact);
    }
    return rows;
  }
// ------------------------------------------------------------------------------------ browse export
//fx: Browse.browse_export (par,[cond[,order[,having]]])
//      export dat podle zadaných parametrů, pokud je v par.show obsažen seznam jmen,
//      budou exportovány jen takto pojmenované sloupce
//a:    par - {[dir:podsložka docs,]file:jméno souboru v docs,type:csv|xls|xlsx,
//             [,show:seznam exportovaných show]}
//      cond - MySQL podmínka umístěná za WHERE
//      order - nepovinná část za ORDER BY
//      having - nepovinná část umístěná za HAVING v GROUP BY klauzuli
//r:    - počet přečtených řádků
  browse_export (par,cond,order,having,sql) {
    // vytvoř parametry dotazu
    var x= this._params({cmd:'browse_export',par:par},cond,order||null,having||null);
    // pokud je požadováno selected, předej klíče
    if ( par.selected ) {
      x.selected= this.selected('get');
    }
    return x;
  }
  browse_export_ (y) {
    // vrací doplněné par
    return y.par;
  }
// ------------------------------------------------------------------------------------ browse status
//fm: Browse.browse_status ()
//      vrátí stavové informace o browse jako objekt se složkami: cond, order, having
  browse_status () {
    var x= this._params({cmd:''});
    return x;
  }
// ------------------------------------------------------------------------------------ browse load
//fx: Browse.browse_load ([cond[,order[,having[,from,[len[,quiet[,sql]]]]]]])
//      načtení dat do buferu browse podle podmínky
//      a jejich dynamické obarvení, pokud je definováno css_rows.
//      Nastaví první řádek jako aktivní a vyvolá na něm onrowclick
//      Buffer má délku danou atributem buf_rows
//    pokud je optimize/ask přenechává se zpracování na uživatelské funkci pro všechna show
//a:    cond - MySQL podmínka umístěná za WHERE
//      order - nepovinná část za ORDER BY
//      having - nepovinná část umístěná za HAVING v GROUP BY klauzuli
//      from - pořadí prvního řádku
//      len - počet žádaných řádků
//      quiet - po načtení nemá být vyvoláno onrowclick
//      sql - nepovinný MYSQL dotaz vykonaný před hlavním dotazem
//r:    - počet přečtených řádků
  browse_load (cond,order,having,from,len,quiet,sql) {
    // vytvoř parametry dotazu
    this.DOM_enable_reload(true);
    var x= {cmd:'browse_load'};
    x= this._params({cmd:'browse_load'},
      //                                            zapomen_podminku,sql
      cond,order||null,having||null,from||null,len||null,null,sql||null);
    x.quiet= quiet||0;
    if ( sql ) x.sql= sql;
    return x;
  }
  // x - {table:..,cond:...,order:...}
  // y - {values:[[id1:val1,...]...],rows:...}
  // rec = -1 pokud nemá být změněn form.key
  browse_load_ (y,rec) {
//                                                         Ezer.debug(y,'browse_load_');
    // načtení výsledku dotazu do buferu v Browse.buf
    // pokud je y.x.smart==1 bude dotaz doplněn, jinak jej nahradí
    var from= Number(y.from),
        asked= this.options.optimize && this.options.optimize.ask;
    this._browse_init1('load');                 // inicializace bufferu
    // inicializace bufferu
    this._source= asked ? 'ask' : 'load';       // metoda získání záznamů
    this.slen= Number(y.count);
    this.blen= Number(y.rows);
    this.b= this.blen>0 ? from : -1;
    this.tact= 0;
    if ( this.blen>0 ) {
      // naplň buf a keys daty
      if ( rec!=-1 )                            // pokud není blokováno
        this.owner._key= null;                  // nastav klíč prvního řádku
      for (var bi= 0; bi<this.blen; bi++) {     // bi ukazuje do buf a keys
        this.buf[bi]= {};
        for (var vi in y.values[bi+1]) {        // vi je identifikátor show
          // hodnota bude do buf transformována show._load
          this.buf[bi][vi]= this.part[vi]._load(y.values[bi+1][vi]);
          if ( asked ) {
            if ( vi==y.key_id ) {
              // klíč je zapsán podle stejnojmenné položky
              this.keys[bi]= Number(this.buf[bi][vi]);
              if ( rec!=-1 )                      // pokud není blokováno
                this.owner._key= this.keys[bi];   // změň běžný klíč
            }
          }
          else if ( this.keys[bi]===undefined
            && this.part[vi].data && this.part[vi].data.id==y.key_id ) {
            // klíč je zapsán jen podle první položky, která jej má v data.id
            this.keys[bi]= Number(this.buf[bi][vi]);
            if ( rec!=-1 )                      // pokud není blokováno
              this.owner._key= this.keys[bi];   // změň běžný klíč
          }
          if ( !this.tact && y.browse_seek && y.seek==this.keys[bi] ) {
            this.tact= bi;
          }
        }
      }
    }
    // zobrazení viditelné části
    this.t= this.b;
    this.tlen= Math.min(this.tmax,this.blen);
    if ( y.browse_seek ) {
      this.r= this.b+this.tact;
      this.tact++;
    }
    else {
      this.r= this.b;
      this.tact= this.tlen ? 1 : 0;
    }
    if ( rec!=-1 )                              // pokud není blokováno
      this.DOM_show();                          // zobrazení
    if ( y.quiet==0 )                           // pokud nebylo zakázáno onrowclick
      this.DOM_hi_row(this.r,false,true);       // pak focus jen řádku a s onrowclick
    // vrací počet přečtených řádků
    this.scrolling= false;
    return this.blen;
  }
// --------------------------------------------------------------------------------==> . browse_seek
//fx: Browse.browse_seek ([seek_cond [,cond[,having[,sql[,quiet]]]]])
//      naplnění browse daty z tabulky;
//      pro správnou funkci musí browse obsahovat show s klíčem řídící tabulky
//    1.pokud není definováno seek_cond, zopakuje předchozí browse_load včetně nastavení záznamu
//      zobrazí tedy řádek s klíčem browse_key
//      (i pokud nedošlo ke změně v datech dojde k překreslení browse);
//    2.pokud je definováno seek_cond: když není definováno cond, tak současné browse posune tak,
//      aby byl zobrazen řádek vyhovující seek_cond;
//      pokud je cond definováno tak zobrazí vyhovující řádky tak aby byl vidět řádek vyhovující i seek_cond;
//      pokud řádek vyhovující seek_cond neexistuje, ponechá zobrazení beze změny a vrátí false,
//      pokud řádek existuje, vrátí jeho klíč
//a: seek_cond   - podmínka pro zviditelněný řádek
//   cond        - podmínka pro všechny řádky browse, je-li vynechána bude užita stávající
//   having      - podmínka pro všechny řádky browse umístěná za HAVING v GROUP BY
//   sql         - nepovinný MYSQL dotaz vykonaný před hlavním dotazem
//   quiet       - nepovinný po načtení nemá být vyvoláno onrowclick
  browse_seek (seek,cond,having,sql,quiet) {
    var x;
    if ( seek ) {
      x= this._params({cmd:'browse_seek', seek:seek, tmax:this.tmax},
         cond||null, null, having||null,null,null,null,sql||null);
    }
    else {
      x= this._params({cmd:'browse_load'},null,null,null,this.b,-1,0,sql||null);
    }
    x.quiet= quiet||0;
    return x;
  }
  browse_seek_  (y) {
    var seek= 0;
    if ( y.cmd=='browse_load' ) {
      // volání browse_seek bez parametrů
      var oldkey= this.browse_key();
//                                                         Ezer.trace('*','browse_seek() key:'+oldkey);
      this.browse_load_(y,-1);  // nebude provedeno _row_move
      var indx= this.keys.indexOf(oldkey);
      if ( indx!=-1 ) {
        // obsluha funkce browse_seek bez parametrů => nastavíme původní tab_act
        this._row_move( this.b+indx);
        seek= oldkey;
      }
      this.DOM_show();
    }
    else if ( y.seek ) {
      y.seek= Number(y.seek);
      seek= y.seek;
//                                                         Ezer.trace('*','browse_seek(...) key:'+seek);
      // volání browse_seek s parametry
      y.browse_seek= 1;
      this.browse_load_(y,-1);  // nebude provedeno _row_move
//       this.raise('onrowclick',Number(y.seek),0,0,1);
      for (var iv= 0; iv<this.blen; iv++) {
        if ( this.keys[iv]==y.seek ) {          // projdi klíče přečtených řádků browse
          this._row_move(this.b+iv,y.quiet);    // NOEVENT! pokud bylo zakázáno
          break;
        }
      }
      this.DOM_show();
    }
    return seek;      // vrací 0 a nemění zobrazení, pokud záznam nebyl nalezen
  }
// ---------------------------------------------------------------------------------- browse refresh
//fx: Browse.browse_refresh ([key])
//  zopakuje předchozí browse_load včetně nastavení záznamu s klíčem key
//a: key   - klíč
  browse_refresh (oldkey) {
    var x= this._params({cmd:'browse_load',subcmd:'refresh'},null,null,null,this.b,-1,0,null);
    x.oldkey= oldkey || this.browse_key();
    x.quiet= 1;
    return x;
  }
  browse_refresh_  (y) {
    this.browse_load_(y,-1);  // nebude provedeno _row_move
    let oldkey= Number(y.oldkey), 
        indx= this.keys.indexOf(oldkey);
    if ( indx!=-1 ) {
      // obsluha funkce browse_seek bez parametrů => nastavíme původní tab_act
      this._row_move(this.b+indx,true);
    }
    this.DOM_show();
    this.DOM_hi_row(this.r,false,true);         // focus řádku s onrowclick
    return 1;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _row_submit+
// vrácení klíče aktivního řádku po Enter a DblClick
  _row_submit (control) {
    this.fire('onsubmit',[this.keys[this.r-this.b],control]);
    return true;
  }
// ------------------------------------------------------------------------------------ browse next+
//fm: Browse.browse_next ([r,[rowclick])
//      nastaví jako aktivní další řádek v browse, nebo r-tý řádek (1..délka souboru)
//      a vrátí jeho klíč; nevyvolá onrowclick pokud není rowclick=1;
//      pokud není uvedeno r a současný řádek je poslední, vrátí 0
//      jinak pokud požadovaný řádek není načtený v bufferu nastane chyba: 'browse_next mimo rozsah'
//      Pozn.: metoda nenačítá řádky ze serveru, pro její použití je tedy třeba definovat
//             dostatečně velký atribut buf_rows
//a: r - pokud je nenulové, nastaví řádek jako aktivní (první je 1)
  browse_next (r) {
    var _key= 0;
    if ( r ) {
      Ezer.assert(this.b<=r-1 && r-1<this.b+this.blen,'browse_next('+r+') je mimo rozsah');
      // skok na řádek bez onrowclick
      this._row_move(r-1,true);                 // posune this.r
      _key= this.keys[this.r-this.b];
    }
    else if ( this.r+1<this.b+this.blen ) {
      // posun na další řádek než je aktivní bez onrowclick
      this._row_move(this.r+1,true);            // posune this.r
      _key= this.keys[this.r-this.b];
    }
    return _key;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _row_seek
// funkce projde načtené řádky a pokud dojde ke shodě prvního písmena v r-tém sloupci
// se vzorem nastaví řádek jako aktivní - procházení je kruhové
  _row_seek  (patt) {
    if ( this.order_by ) {
      // pokud je nastaveno řazení
      var ishow= null;
      for (var vi in this.part) {
        // najdeme sloupec s nastaveným řazením
        if ( this.part[vi]==this.order_by ) {
          ishow= vi;
          break;
        }
      }
      // najdeme řádek
      if ( ishow ) {
        for (var i= 0; i<this.buf.length; i++) {
          if ( this.buf[i][ishow] && this.buf[i][ishow][0].toUpperCase()==patt ) {
//                                   Ezer.trace('U','row_seek['+vi+','+i+']='+this.buf[i][ishow]);
            this._row_move(i);
            break;
          }
        }
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _row_move+
// funkce zajistí viditelnost záznamu r (0..slen-1)
// pokud je noevent=1 a nedojde ke čtení ze serveru nebude vyvolána událost onrowclick
// pokud je scrollLock=1 zůstane běžný řádek nezměněný, pokud je ve viditelné oblasti               ToDo
  _row_move  (r,noevent,scrollLock) {
    var b= this.b, blen= this.blen, t= this.t, tlen= this.tlen, slen= this.slen;
    r= Math.min(Math.max(r,0),slen-1);
    if ( r!=this.r ) {
      // pokud je pohyb uvnitř souboru a nová poloha je jiná než současná
      if ( t<=r && r<t+tlen ) {
        // pohyb v rámci tabulky                        // Ezer.trace('*','smarter row_move ['+b+'['+t+'[*'+r+'*]'+(t+tlen)+']'+(this.b+this.blen)+']'+slen+' - g');
        this.DOM_hi_row(r,noevent,true);
      }
      else if ( b<=r && r<t ) {
        // pohyb v rámci bufferu - k začátku            // Ezer.trace('*','smarter row_move ['+b+'[*'+t+'[*'+r+']'+(t+tlen)+']'+(b+blen)+']'+slen+' - b');
        this.t= r;
        this.tlen= Math.min(this.tmax,slen-t);
        this.DOM_show(r);
        this.DOM_hi_row(r,noevent,true);
      }
      else if ( t+tlen<=r && r<b+blen ) {
        // pohyb v rámci bufferu - ke konci             // Ezer.trace('*','smarter row_move ['+b+'['+t+'['+r+'*]'+(t+tlen)+'*]'+(b+blen)+']'+slen+' - d');
        this.t= r-tlen+1;
        this.r= r;
        this.DOM_show(r);
        this.DOM_hi_row(r,noevent,true);
      }
      else if ( !this.scrolling ) {
        this.scrolling= true;
        var from, len, code, mode;
        blen= Math.min(this.bmax,slen);
        tlen= Math.min(this.tmax,slen);
        if ( r+tlen<b ) {                               // blok je celý před buferem
          mode= 1;                                      // Ezer.trace('*','smarter row_move [**'+b+'['+t+'['+r+']'+(t+tlen)+']'+(b+blen)+']'+slen+' - a'+mode);
          b= t= r;
          code= [{o:'x',i:'_browse_scroll',a:8}];
          new Eval(code,this,[this,mode,r,b,blen,r,tlen,b,blen],'smarter_scroll');
        }
        else if ( r<b) {                                // blok je částečně před buferem
          mode= 2;                                      // Ezer.trace('*','smarter row_move [*'+b+'[*'+t+'['+r+']'+(t+tlen)+']'+(b+blen)+']'+slen+' - a'+mode);
          len= Math.min(b-r,blen);
          b= t= r;
          code= [{o:'x',i:'_browse_scroll',a:8}];
          new Eval(code,this,[this,mode,r,b,blen,r,tlen,b,len],'smarter_scroll');
        }
        else if ( b+2*blen<=r ) {                       // blok je celý za buferem
          mode= 3;                                      // Ezer.trace('*','smarter row_move ['+b+'['+t+'['+r+']'+(t+tlen)+']'+(b+blen)+'**]'+slen+' - e'+mode);
          from= r-blen+1;
          b= r-blen+1;
          t= r-tlen+1;
          code= [{o:'x',i:'_browse_scroll',a:8}];
          new Eval(code,this,[this,mode,r,b,blen,t,tlen,from,blen],'smarter_scroll');
        }
        else {                                          // blok je částečně za buferem
          mode= 4;                                      // Ezer.trace('*','smarter row_move ['+b+'['+t+'['+r+']'+(t+tlen)+'*]'+(b+blen)+'*]'+slen+' - e'+mode);
          len= Math.min(r-b-blen+1,blen);
          from= b+blen;
          b= r-blen+1;
          t= r-tlen+1;
          code= [{o:'x',i:'_browse_scroll',a:8}];
          new Eval(code,this,[this,mode,r,b,blen,t,tlen,from,len],'smarter_scroll');
        }
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - _browse_scroll+
// načtení pokračování buferu za/před stávající obsah
//   t je začátek tabulky, r bude aktivní, len je potřeba načíst
  _browse_scroll (mode,r,b,blen,t,tlen,from,len) {
//                                                         Ezer.trace('*','smarter _scroll r:'+r+',b:'+b+','+blen+',t:'+t+','+tlen+',S:'+from+','+len+' - '+mode);
    var x= this._params({cmd:'browse_scroll'},null,null,null,from,1);
    x.count= this.slen;                 // celkový počet záznamů select již známe
    //x.active= r;                      // záznam, který bude aktivní v tabulce
    //x.init= 0;                        // 1 pokud se bude inicializovat buffer
    x.rows= len;
    x.r= r; x.b= b; x.blen= blen; x.t= t; x.tlen= tlen; x.mode= mode;
    return x;
  }
  // x - {table:..,cond:...,order:...}
  // y - {values:[[id1:val1,...]...],rows:...}
  _browse_scroll_ (y) {
                                                        //Ezer.debug(y,'_browse_scroll_');
    var rows= Number(y.rows), mode= Number(y.mode),
      r= Number(y.r), b= Number(y.b), blen= Number(y.blen), t= Number(y.t), tlen= Number(y.tlen);
    // načtení bloku do nových polí
    var buf= [], keys= [], key= null;           // pro nová data
    if ( rows>0 ) {
      // naplň buf a keys daty
      for (var bi= 0; bi<rows; bi++) {          // bi ukazuje do buf a keys
        buf[bi]= {};
        for (var vi in y.values[bi+1]) {        // vi je identifikátor show
          // hodnota bude do buf transformována show._load
          buf[bi][vi]= this.part[vi]._load(y.values[bi+1][vi]);
          if ( keys[bi]===undefined && this.part[vi].data && this.part[vi].data.id==y.key_id ) {
            // klíč je zapsán jen podle první položky, která jej má v data.id
            key= keys[bi]= Number(buf[bi][vi]);
          }
        }
      }
    }
    // vložení do buf a keys podle mode
    switch (mode) {
    case 1:                                     // celý blok je před buf => nahradit
    case 3:                                     // celý blok je za buf   => nahradit
      this.buf= buf;
      this.keys= keys;
      break;
    case 2:                                     // část bloku je před buf => překrýt
      // data je třeba vložit před začátek buferu
//       if ( this.blen+rows > this.bmax ) {
//         // posledních rows zapomeneme
//         var smazat= this.blen + rows - this.bmax;
//         this.buf.splice(this.blen-1,smazat);
//         this.keys.splice(this.blen-1,smazat);
//       }
      this.buf.splice(this.blen-rows,rows); // původní řešení: bylo místo předchozího if
      this.keys.splice(this.blen-rows,rows);
      Array.prototype.splice.apply(this.buf,[0,0].concat(buf));
      Array.prototype.splice.apply(this.keys,[0,0].concat(keys));
      break;
    case 4:                                     // část bloku je za buf => překrýt
      // data je třeba vložit za konec buferu - prvních rows zapomeneme
      this.buf.splice(0,rows);
      this.buf= this.buf.concat(buf);
      this.keys.splice(0,rows);
      this.keys= this.keys.concat(keys);
      break;
    }
    this.owner._key= key;
    // obnovení stavových hodnot
    this.r= r; this.b= b; this.blen= blen; this.t= t; this.tlen= tlen;
    // zobrazení tabulky
    this.DOM_show(true);                        // zobrazení bez scroll
    this.DOM_hi_row(this.r,false,true);         // focus vč. onrowclick
    this.scrolling= false;
    return rows;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _css_def+
// atributy css_rows a css_cell určují styl řádku resp. buňky
// -- funkce definuje atributy css, css_clmn objektu me (browse nebo clmn)
  _css_def  (me,browse,opt) {
    var css= me.options[opt];
    var as, aas, is;
    me.css_default= null;
    me.css= {};
    me.css_clmn= null;                       // css_clmn = sloupec určující barvu
    if ( css ) {
      // nalezení sloupce podle jména v css[0]
      as= css.split(',');
      for (var ic in browse.part) {            // projdi zobrazené sloupce
        if ( browse.part[ic].skill && as[0]==browse.part[ic].id ) {
          me.css_clmn= browse.part[ic];
          break;
        }
      }
      if ( !me.css_clmn ) {
        Ezer.error('browse '+browse.owner.id+'.'+browse.id+'.css nemá jako první člen jméno sloupce');
        return false;
      }
      for (is= 1; is<as.length; is++) {
        aas= as[is].split(':');
        if ( aas.length>1 ) {
          if ( aas[0].length )
            me.css[aas[0]]= aas[1];
          else                          // syntaxe tvaru  ,:styl,  určuje defaultní styl
            me.css_default= aas[1];
        }
        else
          me.css[is]= as[is];
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _set_css_rows+
// funkce pro zobrazení obarvení řádků
  _set_css_rows  () {
    if ( this.css!={} ) {
      for (var i= 1; i<=this.tmax; i++) {
        this._css_row(i);
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _css_row-
// obarvení i-tého řádku případně dynamicky podle css_rows
  _css_row  (i) {
    var css= i%2 ? 'tr-odd' : 'tr-even', icss;
    if ( i<=this.tlen ) {
      if ( this.css_clmn ) {
        // obarvení řádku podle algoritmu css_alg (sloupec musí mít číselnou hodnotu)
        icss= Number(this.buf[this.t+i-1-this.b][this.css_clmn.id]);
        if ( this.css[icss]===undefined ) {            // indexující styly
          // defaultní styl, pokud je definován
          if ( this.css_default ) css+= ' '+this.css_default;
        }
        else
          css+= ' '+this.css[icss];
      }
      this.DOM_row[i].attr('class',css+(i==this.tact?' tr-form':''));
      if ( i==this.tact )
        this.DOM_tag[this.tact].removeClass('tag0').addClass('tag1');
      for (var ic in this.part) {
        // projití sloupců obsahujících barvení buněk
        var clmn= this.part[ic];
        if ( clmn.DOM_cell ) {
          // barvíme jen zobrazené buňky
          if ( clmn instanceof Show ) {
            var ccss= css;
            if ( clmn.css_clmn ) {
              // pokud je požadavek na barvení, najdi sloupec, který ho určuje a doplň barvu
              icss= Number(this.buf[this.t+i-1-this.b][clmn.css_clmn.id]);
              if ( clmn.css[icss]===undefined ) {            // indexující styly
                // defaultní styl, pokud je definován
                if ( clmn.css_default ) ccss+= ' '+clmn.css_default;
              }
              else if ( clmn.css[icss] )
                ccss+= ' '+clmn.css[icss];
            }
            clmn.DOM_cell[i].attr('class',ccss);
          }
        }
      }
      // obarvení řádků vybraných INS
      let key_i= this.keys[this.t+i-1-this.b];
      if ( this.keys_sel.includes(key_i) )
        this.DOM_row[i].addClass('tr-sel');
    }
    else {
      this.DOM_row[i].attr('class',css);
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _params+
// pokud je definován atribut optimize, předá jej beze změny
  _params  (x,cond,order,having,from,cursor,zapomen_podminku,sql) {
    Ezer.fce.touch('block',this);     // informace do _touch na server
    var to_map= x.cmd=='browse_export';   // pro browse_export doplň pro server info o map_pipe
    var ignore= [];                       // seznam vynechaných sloupců definovaný optimize.ignore
    x.cond= cond||this.cond||1;
    if ( !zapomen_podminku ) {
      // zapamatuj si podmínku
      this.cond= x.cond;
    }
    x.order= order||this.order||'';       this.order= x.order;
    x.having= having||this.having||'';    this.having= x.having;
    x.sql= sql||this.sql||'';             this.sql= x.sql;
    x.from= from||0;
    x.cursor= cursor||0;
    x.rows= this.bmax;
    x.fields= [];
    if ( this.options.group_by )
      x.group= this.options.group_by;
    // explicitní nastavení jména klíče  (120131_MS)
    if ( this.options.key_id )
      x.key_id= this.options.key_id;
    // řešení optimize
    if ( this.options.optimize )  {
      x.optimize= this.options.optimize;
      if ( this.options.optimize.ignore ) { // seznam vynechaných show
        ignore= this.options.optimize.ignore.split(',');
      }
      if ( this.options.optimize.ask ) {
        // ---------------- browse/ask: zjednodušené předání parametrů - bez join, data, ...
        for (let ic in this.part) {
          // předej id od všech show
          let field= this.part[ic];
          if ( field.type=='show' && field.skill && !ignore.includes(ic) ) {
            x.fields.push(this.part[ic].id);
            // a řazení
            if ( field.sorting && field.sorting!='n' ) {
              x.order= field.sorting+' '+ic;
            }
          }
        }
        // a technickou podobu dotazu pro browse/ask
        x.show= this._get_query();
        // pokud bylo selected(use) předej vybrané klíče
        x.selected= this.selected_op=='use' ? this.keys_sel.toString() : null;
        return x;
      }
    }
    if ( !this.query_seek ) {
      // doplň podmínku o dotazy zadané v zobrazených sloupcích browse
      var wcond= this.get_query(false);           // podmínky za WHERE
      if ( this.get_query_pipe )
        x.pipe= this.get_query_pipe;
      x.cond+= (x.cond && wcond ? " AND " : '' ) + wcond;
      var hcond= this.get_query(true);            // podmínky za HAVING
      x.having+= (x.having && hcond ? " AND " : '' ) + hcond;
    }
    // vytvoř parametry dotazu
    // x: table, cond, order, fields:{id:label,field|expr}, from, cursor, rows, key_id, {joins...} [, group]
    // y: from, rows, values**, key_id
    x.joins= {};
    for (let ic in this.part) { // načti jen zobrazené sloupce použité v browse, vybírej použitá view
      let field= this.part[ic];
      if ( field._load && (field.data || field.options.expr) && field.skill && !ignore.includes(ic) ) {
        this.owner._fillx(field,x,to_map);
      }
    }
    this._fillx2(x.cond+x.order,x); // s možnou explicitní definicí x.key_id
    // změň podmínku na "jen vybrané", pokud je požadováno
    if ( this.selected_op=='use' ) {
      if ( this.keys_sel.length>0 ) {
        var as= x.table ? x.table.split('AS') : null;
        var key_id= as && as[1] ? as[1].trim()+'.'+x.key_id : x.key_id;
        x.cond+= ' AND ' + key_id + ' IN (';
        for (var i= 0, del= ''; i<this.keys_sel.length; i++, del= ',')
          x.cond+= del+this.keys_sel[i];
        x.cond+= ' )';
      }
      else x.cond= ' 0';
    }
    if ( !x.table )
      Ezer.error("RUN ERROR '"+x.cmd+"' chybi ridici tabulka pro browse "+this.id);
    return x;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _fillx+
// doplní do x seznam joins potřebných pro dotaz obsahující data
// x musí mít x.table a x.join:{}
  _fillx (field,x) {
    var pipe, desc, expr;
    if ( field.data ) {                         // je atribut data
      desc= {id:field.id};
      if ( !x.table ) {                         // info o table, pokud již v x není
        x.table= field.table.id + (field.view ? ' AS '+field.view.id : '');
        x.key_id= this.options.key_id ? this.options.key_id
          : field.table.options.key_id||'id_'+field.table.id;
        x.db= field.table.options.db||'';
      }
      if ( field.view ) {                       // s odkazem přes view
        if ( field.view.options.join ) {
          var xx= x.joins[field.view.id]||false;
          if (!xx ) {
            x.joins[field.view.id]= (field.view.options.join_type||'')+' JOIN '
              + (field.table.options.db ? field.table.options.db+'.' : '')
              + field.table.id
              +' AS '+field.view.id+' '+field.view.options.join;
            this._fillx2(field.view.options.join,x);      // doplní potřebná view/join
          }
        }
        desc.field= field.view.id+'.'+field.data.id;
      }
      else {                                    // s odkazem přes table
        desc.field= field.data.id;
      }
      if ( field.options && field.options.sql_pipe!==''
        && ((pipe= field.options.sql_pipe) || (pipe= field.data.options.sql_pipe)) )
        desc.pipe= pipe;
      x.fields.push(desc);
    }
    else if ( (expr= field.options.expr) ) {
      this._fillx2(expr,x);                     // doplní potřebná view/join
      desc= {id:field.id,expr:expr};
      if ( (pipe= field.options.sql_pipe) )
        desc.pipe= pipe;
      x.fields.push(desc);
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _fillx2+
  // doplní do x seznam joins potřebných pro dotaz obsahující expr
  // x musí mít x.table a x.join:{}, formtype=='use'
  // view se poznají podle vzoru \w+\.
  _fillx2  (expr,x) {
    var re, m, view;
    re= new RegExp('(\\w+)\\.','g');
    while ( (m= re.exec(expr)) ) {
      for ( var iv in this.part ) {
        view= this.part[iv];
        if ( view.type=='view' && view.id==m[1] ) {
          if ( view.options.join ) {
            // je to view s join
            if ( !x.joins[view.id] ) {
              x.joins[view.id]= (view.options.join_type||'')+' JOIN '
                + (view.value.options.db ? view.value.options.db+'.' : '')
                + view.value.id
                +' AS '+view.id+' '+view.options.join;
              this._fillx2(view.options.join,x); // přidej view použitá v join
            }
          }
          else {
            // je to řídící tabulka
            if ( !x.table ) {
              x.db= view.value.options.db||'';
              x.table= view.value.id+' AS '+view.id;
              x.view= view.id;
              x.key_id= view.value.key_id;
            }
          }
        }
      }
    }
  }
// =====================================================================================> Browse DOM
//c: Browse-DOM
//      tabulkové zobrazení dat s mezipamětí
//t: Block-DOM
//s: Block-DOM

// Slider.implement({   ... mootools
//   reset: function(steps){ //GN
//     this.options.steps= steps;
//     this.setRange(this.options.range);
//     this.autosize();
//     this.set(0);
//     this.knob.fade(this.steps?'show':'hide');
// }
// });

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM initialize
  DOM_initialize () {
    this._clmns= 0;
    this._opened= null;                        // buňka otevřená k editaci po Show.DblClk
    this._opened_value= null;                  // původní hodnota této buňky
  }
// ------------------------------------------------------------------------------------ DOM remove
//f: Browse-DOM.DOM_remove ()
//      odstraní obraz tabulky
  DOM_remove (data_only) {
    if ( data_only ) {
      // odpojení událostí
      for (let i= 1; i<=this.tmax; i++) {
        this.DOM_row[i].off();
      }
      this.DOM_input.off();
      this.DOM_table.off();
      // odpojení posuvníku pro qry_rows=0
      if ( !this.options.qry_rows )
        this.DOM_tr_posun= this.DOM_tr_posun.find('td.BrowsePosuv3').detach();
      // odstranění pouze datové části browse
      for (let i= 1; i<=this.tmax; i++) {
          this.DOM_row[i].remove();
      }
      this.DOM_row= [];
      this.DOM_tag= [];
    }
    else {
      // celkové odstranění obrazu browse
      this.slider.destroy();
      this.DOM.remove();
      this.DOM_Block= DOM_table= this.DOM_head= this.DOM_foot= this.DOM_status= null;
      this.DOM_tbody= this.DOM_input= this.DOM_tr_posun= null;
      this.DOM_qry_row= this.DOM_row= this.DOM_tag= [];
    }
  }
// ------------------------------------------------------------------------------------ DOM add1+
//f: Browse-DOM.DOM_add1 ()
//      zobrazí tabulku
  DOM_add1 (data_only) {
    if ( !data_only ) {
      // základní struktura zobrazení browse - úplné vybudování
      let foot_keys= 
        `<i class="fa fa-caret-down" style="position:absolute;left: 10px;" title="PageDown"></i>
         <i class="fa fa-caret-up"   style="position:absolute;left: 18px;" title="PageUp"></i>
         <i class="fa fa-genderless" style="position:absolute;left: 26px;" title="Insert"></i>
        `;
      this.DOM= this.DOM_Block= jQuery(
        `<div class="BrowseSmart">
          <table cellspacing=1>
            <thead><tr><td class="tag0" style="width:8px"></td></tr></thead>
            <tfoot><tr><th colspan=1>
              ${foot_keys}
              <div style="display:block;text-align:center">
                <span>-</span></div></th></tr></tfoot>
            <tbody></tbody>
          </table>` + 
          //pro odchytávání událostí klávesnice (ovládání browse klávesnicí, na mobilních zařízeních ale stále vyskakovala sw klávesnice)
          (Ezer.platform!=='A'&&Ezer.platform!=='I'&&Ezer.platform!=='P' 
            ? `<input class="BrowseFocus" type="text">` : ``)
        )
        .css(this.coord())
        .appendTo(this.owner.DOM_Block)
        .data('ezer',this);
      this.DOM_input=  this.DOM.find('input');
      this.DOM_table=  this.DOM.find('table');
      this.DOM_head=   this.DOM_table.find('thead tr');
      this.DOM_tbody=  this.DOM_table.find('tbody');
      this.DOM_foot=   this.DOM_table.find('tfoot th');       // patička s přehledem stavu
      this.DOM_status= this.DOM_foot.find('span');
      this.DOM_reload= this.DOM_head.find('td')         // tady budou hlavičky sloupců
        .click( el => {
          if ( el.shiftKey ) return dbg_onshiftclick(this); /* browse */
          // znovu načti obsah, pokud je povoleno
          if ( this.DOM_reload.hasClass('BrowseReload') )
            this._ask_queries(true,this.browse_key());
        });
      // doplnění začátku řádků s dotazy
      this.DOM_qry_row= [];
      for (let i= 1; i<=this.options.qry_rows; i++) {
        this.DOM_tbody.append(
          this.DOM_qry_row[i]= jQuery(`<tr><td class="tag0">`)
            .dblclick( event => {
              event.stopPropagation();
              if ( this.enabled && event.target.tagName=="INPUT") {
                this.init_queries();
              }
            })
        );
      }
      // scroll bar začíná pod hlavičkou
      if ( this.options.qry_rows>0 )
        this.DOM_tr_posun= this.DOM_qry_row[1];     // scrollbar již na úrovni dotazu
    }
    // doplnění začátku datových řádků a přidání události pro mouse.click
    this.DOM_row= [];
    this.DOM_tag= [];
    for (let i= 1; i<=this.tmax; i++) {
      this.DOM_tbody.append(
        this.DOM_row[i]= jQuery(`<tr>`)
          .data('i',i)
          .append(this.DOM_tag[i]= jQuery(`<td class="tag0"> </td>`))
      );
    }
    // přidání obsluhy simulace kláves PgDn, PgUp, Ins
    this.DOM_foot.find('i.fa-caret-down').click( () => { this.keydown('page_down') });
    this.DOM_foot.find('i.fa-caret-up')  .click( () => { this.keydown('page_up') });
    this.DOM_foot.find('i.fa-genderless').click( () => { this.keydown('insert') });
    // scrollbar na úrovni dat
    if ( !this.DOM_tr_posun )
      this.DOM_tr_posun= this.DOM_row[1];         
  }
// ------------------------------------------------------------------------------- DOM enable_reload
//f: Browse-DOM.DOM_enable_reload ()
//      připojí (nebo odpojí) události
  DOM_enable_reload (on) {
    if ( on )
      this.DOM_reload.addClass('BrowseReload');
    else
      this.DOM_reload.removeClass('BrowseReload');
  }
// ----------------------------------------------------------------------------------- DOM riseEvent
//f: Browse-DOM.DOM_riseEvent ()
//      vyvolá (některou) událost - volá se z obsluhy DOM_addEvents nebo odjinud
  DOM_riseEvent (id,par) {
    switch(id) {
    case 'keydown_page_up':
      this._row_move(Math.max(0,this.r-this.tmax));
      break;
    case 'keydown_page_down':
      this._row_move(Math.min(this.r+this.tmax,this.slen-1));
      break;
    case 'keydown_insert':
      var key= this.keys[this.r-this.b];
      var ikey= this.keys_sel.indexOf(key);
      if ( ikey>=0 )
        this.keys_sel.splice(ikey,1);
      else
        this.keys_sel.push(key);
      this.fire('onchoice',[ikey>=0?0:1]);
      this._css_row(this.tact);
      this.DOM_hi_row(this.t+this.tact-1,1);
      break;
    }
  }
// ------------------------------------------------------------------------------------ DOM addEvents+
//f: Browse-DOM.DOM_addEvents ()
//      připojí (nebo odpojí) události
  DOM_addEvents () {
    // přidání událostí myši
    for (var i= 1; i<=this.tmax; i++) {
      this.DOM_row[i]
        .click( el => {
          if ( el.shiftKey ) return dbg_onshiftclick(this); /* browse */
          if ( this.enabled ) {
            Ezer.fce.touch('block',this,'click');         // informace do _touch na server
            var tr= el.target.tagName=='TD' ? el.target.parentNode : el.target;
            var i= jQuery(tr).data('i');
            if ( i && i <= this.tlen ) {
              this.DOM_focus();
              this.DOM_hi_row(this.t+i-1,0,0,el.ctrlKey);
              if ( el.ctrlKey ) {
                this.DOM_riseEvent('keydown_insert');
              }
            }
          }
        });
    }
    // přidání událostí klávesnice
    this.DOM_input
      .blur( event => {
        this.DOM_blur();
      })
      // ovládání tabulky klávesnicí
      .keydown( event => {
        event.stopPropagation();
        if ( event.keyCode==9 )                                    // tab
          return true;
        if ( event.altKey && event.keyCode!=18 ) {
          // ovládání hledání přes Alt+první písmeno
          var key= 0<=event.keyCode && event.keyCode<=9 ? "É.ĚŠČŘŽÝÁÍ"[event.key] : event.key.toUpperCase();
          this._row_seek(key);
        }
        Ezer.fce.touch('block',this,'keydown');     // informace do _touch na server
        switch (event.keyCode) {
        case 45:                                                // 'insert':
          this.DOM_riseEvent('keydown_insert');
          break;
        case 33:                                                // 'page up':
          this.DOM_riseEvent('keydown_page_up');
          break;
        case 34:                                                // 'page down':
          this.DOM_riseEvent('keydown_page_down');
          break;
        case 36:                                                // 'home':
          if ( event.ctrlKey )
            this._row_move(0);
          else {
            this._row_move(this.t);
          }
          break;
        case 35:                                                // 'end':
          if ( event.ctrlKey )
            this._row_move(this.slen-1);
          else {
            this._row_move(this.t+this.tlen-1);
          }
          break;
        case 38:                                                // 'up':
          if ( event.ctrlKey ) {                                // skok na dotazy
            if ( this.first_query )
              this.first_query.focus();
          }
          else this._row_move(Math.max(0,this.r-1));
          break;
        case 40:                                                // 'down':
          this._row_move(Math.min(this.r+1,this.slen-1));
          break;
        case 13:                                                // 'enter'
          this._row_submit(event.ctrlKey?1:0);
          break;
        }
        return false;
      });
    this.DOM_table
      .dblclick( el => { // dvojklik na datovém řádku vyvolá onsubmit
        el.stopPropagation();
        if ( this.enabled ) {
          Ezer.fce.touch('block',this,'dblclick');     // informace do _touch na server
          var tr= el.target.tagName=='TD' ? el.target.parentNode : el.target;
          var i= jQuery(tr).data('i');
          if ( i && i <= this.tlen ) {
            // dblclick na datovém řádku
            this.tact= i;
            this.DOM_focus();
            this.DOM_hi_row(this.t+i-1,1);
            this.fire('onsubmit',[this.keys[this.t+i-1-this.b],el.ctrlKey?1:0]);
          }
        }
      })
      .contextmenu( el => { // kliknutí pravým tlačítkem na datovém řádku označí řádek a zobrazí contextmenu
        if ( this.enabled ) {
          Ezer.fce.touch('block',this,'contextmenu');     // informace do _touch na server
          var tr= el.target.tagName=='TD' ? el.target.parentNode : el.target;
          var i= jQuery(tr).data('i');
          if ( i && i <= this.tlen ) {
            // right click na datovém řádku
            this.tact= i;
            this.DOM_focus();
			this.DOM_clear_focus(1)
            this.DOM_hi_row(this.t+i-1,0); // a vyvolá onrowclick
          }
        }
      });
  }
// ------------------------------------------------------------------------------------ DOM add2+
//f: Browse-DOM.DOM_add2 ()
//      zobrazí sloupce tabulky, pokud je tabulka dostatečně definována
  DOM_add2 (data_only) {
    // získání rozměrů
    Ezer.assert(this.DOM_tr_posun,'browse nemá korektní vlastnosti',this);
    this._rows= (this.options.qry_rows||0)+this.tmax;
    // vložení přepínače sloupců
    var browse= this, clicked;
    if ( !data_only ) {
      this.DOM_head.append(clicked= jQuery(`<td class="th" style="width:16px">`));
      if ( this.findProc('onclick') ) {
        clicked
          .addClass('BrowseSet')
          .click(el => {
            browse.fire('onclick',[browse],el);
          });
      }
      // vložení sloupce s posuvníkem -----------------------------------------------
      this.DOM_tr_posun.append(
        jQuery(`
          <td class="BrowsePosuv3" rowspan="${this._rows}">
            <div class="BrowseUp"></div>
            <div class="BrowsePosuv3"></div>
            <div class="BrowseDn"></div>
          </td>
        `)
          .mouseover( ev => {
            if ( this.enabled && this.slen ) {
              this.DOM_posuv_up.addClass('act');
              this.DOM_posuv_dn.addClass('act');
            }
          })
          .mouseout( ev => {
            if ( this.enabled ) {
              this.DOM_posuv_up.removeClass('act');
              this.DOM_posuv_dn.removeClass('act');
            }
          })
      );
      this.DOM_posuv_up= this.DOM_tr_posun.find('div.BrowseUp')
        .click( el => {
          if ( this.enabled ) {
            this._row_move(this.r-1);               // o řádek
          }
        });
      this.DOM_posuv= this.DOM_posuv_up.next();
      this.DOM_posuv_dn= this.DOM_tr_posun.find('div.BrowseDn')
        .click( el => {
          if ( this.enabled ) {
            this._row_move(this.r+1);               // o řádek
          }
        });
      var br = this;
      this.DOM_slider();
      jQuery(this.DOM_tbody).bind('wheel', function(e) {
        e.preventDefault();
        var data = e.data;
        var ewh= e.originalEvent.deltaY>0 ? -br.options.wheel : br.options.wheel;
        br._row_move(br.r-ewh,0,1);
        return false;
      });
      if ( jQuery.fn.swipe ) {
        jQuery(this.DOM_tbody).swipe({
          swipe: function(e, direction, distance, duration, fingerCount, fingerData) {
            e.preventDefault();
            if(direction=="up") br._row_move(br.r+br.options.wheel);
            if(direction=="down") br._row_move(br.r-br.options.wheel);
            if(direction=="right") br.DOM_riseEvent('keydown_insert');
          },
          doubleTap: function(e, target) {
              Ezer.fce.touch('block',br,'dblclick');     // informace do _touch na server
              var tr= e.target.tagName=='TD' ? e.target.parentNode : e.target;
              var i= jQuery(tr).data('i');
              if ( i && i <= this.tlen ) {
                // dblclick na datovém řádku
                br.tact= i;
                br.DOM_focus();
                br.DOM_hi_row(br.t+i-1,1);
                br.fire('onsubmit',[br.keys[br.t+i-1-br.b],e.ctrlKey?1:0]);
              }
          }
        })
      }
    }
    else {
      // pouze úprava pro data_only => vrácení posuvníku pokud qry_row=0
      if ( !this.options.qry_rows ) {
        this.DOM_row[1].append(this.DOM_tr_posun);
        this.DOM_tr_posun= this.DOM_row[1];
      }
    }
    let rows= this.options.rows+(this.options.qry_rows||0);
    this.DOM_tr_posun.find('td.BrowsePosuv3').attr('rowspan',rows);
    this.slider.init(rows*17-32);
    // úprava patičky
    this.DOM_foot.attr('colSpan',this._clmns+2);
    // dynamické styly pro řádek
    this._css_def(this,this,'css_rows');
    for (var o in this.part) {
      var clmn= this.part[o];
      if ( clmn instanceof Show && clmn.options.css_cell ) {
        // dynamický styl pro sloupce
        this._css_def(clmn,this,'css_cell');
      }
    }
    // pokud je format:'n' potlač zobrazení
    if ( this._fc('n') ) {
      this.DOM_Block.css({display:'none'});
    }
    // pokud je format:'d' potlač zobrazení
    if ( this._fc('d') ) {
      this.enable(false);
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_enabled
//      změní vzhled na enabled/disabled podle parametru nebo this.options.enabled
  DOM_enabled (on) {
    this.enabled= on;
    if ( this.enabled )
      this.DOM_Block.removeClass('disabled3');
    else
      this.DOM_Block.addClass('disabled3');
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM slider
  DOM_slider () {
    var browse= this;
    this.slider= this.DOM_posuv.slider({
      classes:{
        "ui-slider": "BrowsePosuv3",
        "ui-slider-handle": "BrowseHandle3"
      },
      min_handle:16,
      ezer_stop: function() {
        let step= this.slider.getPosition()-1;
        browse._row_move(step);
        this.last= step;
      }.bind(this)
    }).slider('instance');
  }

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_selected+
//f: Browse-DOM.DOM_selected ()
//      označení i-tého řádku browse jako vybraného, pokud on=true nebo jeho odznačení
  DOM_selected (i,on) {
    if ( on )
      this.DOM_row[i].addClass('tr-sel');
    else
      this.DOM_row[i].removeClass('tr-sel');
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_focus+
//f: Browse-DOM.DOM_focus ([silent=false])
//      označení browse jako aktivní, pokud je silent nevyvolá se onfocus
  DOM_focus (silent) {
    if ( !this.DOM_table.hasClass('focus') ) {
      this.DOM_table.addClass('focus');
      if ( !silent )
        this.fire('onfocus',[]);
    }
    this.DOM_input.focus();
    return true;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_blur
//f: Browse-DOM.DOM_blur ()
//      označení browse jako pasivní
  DOM_blur () {
    this.DOM_table.removeClass('focus');
    return true;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_hi_row+
//f: Browse-DOM.DOM_hi_row (r,noevent,nofocus,control=false)
//      nastavení a označení aktivního řádku r=t..t+tlen
//      vyvolá onrowclick, pokud není noevent=true
//      nastaví focus, pokud není nofocus=true
  DOM_hi_row (r,noevent,nofocus,control) {
    Ezer.assert(this.t<=r&&r<=this.t+this.tlen,"Browse.DOM_hi_row("+r+") - mimo rozsah");
    if ( this.tact && this.DOM_row[this.tact] ) {
      // pokud je změna zhasni starý řádek
      this.DOM_row[this.tact].removeClass('tr-form');
      this.DOM_tag[this.tact].removeClass('tag1').addClass('tag0');
    }
    // rožni nový, je-li
    if ( r!=-1 ) {
      this.tact= r-this.t+1;
      this.r= r;
      this.DOM_row[this.tact].addClass('tr-form');
      this.DOM_tag[this.tact].removeClass('tag0').addClass('tag1');
      if ( !noevent ) {
        this.fire('onrowclick',[this.keys[r-this.b],control?1:0]);
      }
    }
    this.DOM_show_status();
    if ( !nofocus ) {
      this.DOM_input.addClass('focus');      // MOBILE: focus() zobrazí klávesnici
    }
    return true;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - DOM clear_focus
// odznačení aktivního řádku
  DOM_clear_focus (deep) {
    if ( deep ) {
      // projdi tabulku a zruš všude případné označení aktivního řádku
      for (let i= 1; i<=this.tmax; i++) {
        this.DOM_row[i].removeClass('tr-form');
        this.DOM_tag[i].removeClass('tag1').addClass('tag0');
      }
    }
    else {
      // spolehni se na označení aktivního řádku
      if ( this.tact ) {
        this.DOM_row[this.tact].removeClass('tr-form');
        this.DOM_tag[this.tact].removeClass('tag1').addClass('tag0');
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_show_focus
// označení aktivního řádku
  DOM_show_focus () {
    // spolehni se na označení aktivního řádku
    if ( this.tact ) {
      this.DOM_row[this.tact].removeClass('tr-form');
      this.DOM_tag[this.tact].removeClass('tag0').addClass('tag1');
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_show+
//f: Browse-DOM.DOM_show (noscroll)
//      zobrazí buffer od this.t
//      pokud je noscroll=1 nebude upravována poloha posuvníku
  DOM_show (noscroll) {
    for (let it= 0; it<this.tlen; it++) {
      this.DOM_row[it+1].removeClass('tr-form');
      this.DOM_tag[it+1].removeClass('tag1').addClass('tag0');
      for (let vi in this.part) {
        if ( this.part[vi] instanceof Show ) {
          // zobrazení hodnoty řádku ve sloupci
          this.part[vi].DOM_show(it+this.t);
        }
      }
    }
    for (let it= this.tlen; it<this.tmax; it++) {
      this.DOM_row[it+1].removeClass('tr-form');
      this.DOM_tag[it+1].removeClass('tag1').addClass('tag0');
      for (let vi in this.part) {
        if ( this.part[vi] instanceof Show && this.part[vi].DOM_cell ) {
          // vymazání hodnoty řádku ve sloupci
          this.part[vi].DOM_cell[it+1].text('');
          this.part[vi].DOM_cell[it+1].attr('class',it%2 ? 'tr-even' : 'tr-odd');
        }
      }
    }
    // stavový řádek
    this._set_css_rows();
    this.DOM_show_status(noscroll);
    return true;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_show_status+
// funkce pro zobrazení aktuální polohy
//      pokud je noscroll=1 nebude upravována poloha posuvníku
  DOM_show_status (noscroll) {
    var text= '-';
    if ( this.slen ) {
      text= this.t+1;                                                           // první viditelný
      text+= ' < '+(this.r+1)+' > ';                                            // aktivní
      text+= (this.t+this.tlen);                                                // poslední viditelný
      text+= ' / '+this.slen;                                                   // celkem vůbec
      if ( this.bmax!=this.tmax )
        text+= ' ['+this.blen + '] ';                                           // přečtených
      var keys_sel= this.keys_sel.length;
      if ( keys_sel ) text+= ' ('+keys_sel+')';                                 // vybraných
    }
    this.slider.reset(this.slen,this.tlen,this.r+1);
    this.DOM_status.text(text);
  }
}

// ===========================================================================================> Show
//c: Show
//      sloupec tabulkového zobrazení dat
//t: Block,Elem
//s: Block
class Show extends Elem {
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
//os: Show.help - nápovědný text sloupce, který má přednost před textem získaným přes data
//-
//os: Show.format - vzhled prvků sloupce
//  ; 'c' : 'center' zarovnávat doprostřed
//  ; 'h' : nezobrazí se HTML a PHP tagy
//  ; 'r' : 'right' zarovnávat doprava
//  ; 's[x]' : u sloupce bude možné ovládat řazení, x může (ale jen u jediného sloupce) doplnit
//             počáteční řazení jako '+' (vzestupně) nebo '-' (sestupně)
//  ; 'q[x]' : u sloupce bude možné vyhledávat podle vzoru, x může doplnit způsob hledání
//            'q*' regulárním výrazem (default), 'q$' regulárním výrazem bez diakritiky,
//            'q%' regulární výraz s levostranným %,
//            'q=' na shodu,
//            'q#' na shodu s hodnotou vybranou z nabídka (a la select.map),
//            'q/' intervalem (musí být 2 dotazové řádky, další se ignorují)
//  ; 't' : hodnota bude zobrazena i jako title
//  ; 'u' : EXPERIMENTÁLNÍ hodnotu lze interaktvně změnit po dvojklik
//  ;     : po dvojtečce
//  ; 'e' : místo 0 se zobrazuje ''
//  ; 'i' : čísla se ukazují zaokrouhlená na integer (fcí Math.round)
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
//   initialize (owner,desc,DOM,id,skill) {
//     this.parent(owner,desc,DOM,id,skill);
    if ( this.qry_type ) {
      for (var i= 1; i<=this.owner.options.qry_rows||0; i++)
        this.DOM_qry_set(i,'');
    }
    if ( this.options.js_pipe ) {
      Ezer.assert(typeof(Ezer.fce[this.options.js_pipe])=='function',
        'Ezer.fce.'+this.options.js_pipe+' je neznámé jméno funkce',this);
      this.js_pipe= Ezer.fce[this.options.js_pipe];
    }
  }
  initialize () {
    super.initialize();
//oi: Show.map_pipe - transformace zobrazených hodnot pomoci Map
    this.map_pipe= null;                       // význam atributu map_pipe (tabulka hodnot)
//os: Show.sql_pipe - transformace zobrazených hodnot pomocí funkce v PHP
    this.js_pipe= null;                        // null nebo jednoparametrická funkce
//os: Show.js_pipe - transformace zobrazených hodnot pomocí funkce v Javascriptu (člena Ezer.fce)
    this.qry_type= null;                       // typ dotazu: / = # $ % @ * . nebo null
    this.css= {};                              // objekt vytvořený podle atributu css_rows
    this.css_clmn= null;                       // clmn řídící obarvení
    // stavové informace pro další funkce
    this.get_query_pipe='';                    // případné modifikátory pro formát q@
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  reinitialize
// doplní obrazy datových řádků
  reinitialize () {
//                                                         Ezer.trace('*','reshow');
  // zobrazení pokud je definován rozměr (šířka)
  if ( this.options._w!==undefined )
    this.DOM_add(null,true);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  start
//f: Show.start (code,oneval)
  start  (codes,oneval) {
    super.start(codes,oneval);
    var id= this.options.map_pipe, m= [];
    if ( id ) {
      // pokud je definován atribut map_pipe
      var ids= id.split('.');
      Ezer.assert(1==Ezer.run_name(id,this.owner,m,ids),
        this.options.map_pipe+' je neznámé jméno map',this);
      this.map_pipe= {map:m[1],field:ids[ids.length-1]};
//                                                 Ezer.trace('L','map_pipe '+this.options.map_pipe);
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _start2
// po načtení všech map - volá se z
  _start2 () {
    var m= this.map_pipe.map.data[this.map_pipe.field];
    // načtení options pro show-select
    for (var i= 0; i<this.DOM_qry_select.length; i++) {
      // vytvoř z mapy seznam možností pro případný výběrový select
      var sel= this.DOM_qry_select[i];
      if ( sel ) {
        sel.Items[0]= '';
        for (var k in m) {
          sel.Items[k]= m[k];
        }
        sel.DOM_addItems();
      }
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _load+
// interpretuje hodnotu podle nastavení tohoto sloupce
  _load  (val) {
    // zpracování map_pipe
    var pipe= this.map_pipe;
    if ( pipe && val ) {
      var map_data= pipe.map.data[pipe.field];
      if ( map_data ) {
        let aval= typeof(val)=='string' ? val.split(',') : [val];    // hodnotou může být seznam klíčů
        val= '';
        let del= '';
        for (var ia= 0; ia < aval.length; ia++) {
          val+= del+(map_data[aval[ia]]||''); del= ',';
        }
      }
    }
    else if ( this.js_pipe ) {
      // pipe je napsána jako Ezer.fce[...]
      val= this.js_pipe(val);
    }
    return val;
  }
// ------------------------------------------------------------------------------------ init+
//fm: Show.init (ti)
//      inicializace ti-tého zobrazeného řádku (1...)
  _init  (ti) {
    if ( arguments.length==1 ) {
      this.owner.buf[this.owner.t+ti-1][this.id]= '';
      this.DOM_set(ti);
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ set_attrib
//fm: Show.set_attrib (name,val)       nedokumentováno, může být změněno
//      pokud name='css_cell' změní tento atribut a aplikuje jej
//a: name - 'rows'
//   val - nová hodnota
  set_attrib (name,val) {
    if ( name=='css_cell' ) {
      var browse= this.owner;
      // změň atribut
      super.set_attrib(name,val);
      browse._css_def(this,browse,'css_cell');
    }
    else
      super.set_attrib(name,val);
    return 1;
  }
// ------------------------------------------------------------------------------------ set+
//fm: Show.set (ti,val)
//      nastaví hodnotu ti-tého řádku (1..tlen) na val s případnou transformací podle map_pipe
  set  (ti,val) {
    // zpracování map_pipe
    var pipe= this.map_pipe;
    if ( pipe && val ) {
      var map_data= pipe.map.data[pipe.field];
      if ( map_data ) {
        aval= val.split(',');       // hodnotou může být seznam klíčů
        val= ''; var del= '';
        for (var ia= 0; ia < aval.length; ia++) {
          val+= del+(map_data[aval[ia]]||''); del= ',';
        }
      }
    }
    this.owner.buf[this.owner.r-this.owner.b][this.id]= val;
    this.DOM_set(ti);            // zobrazení s uvážením případné specifikace za ':'
    return 1;
  }
// ------------------------------------------------------------------------------------ let+
//fm: Show.let (val)
//      nastaví hodnotu aktivního řádku (1..tlen) na val s případnou změnou obarvení řádku
  let  (val) {
    Ezer.assert(this.owner.r>=0,'let: nastavení neaktivního řádku browse '+this.owner.id);
    this.owner.buf[this.owner.r-this.owner.b][this.id]= val;
    this.DOM_set(this.owner.r-this.owner.t+1);                  // řádek tabulky
    this.owner._css_row(this.owner.r-this.owner.t+1);
    return 1;
  }
// ------------------------------------------------------------------------------------ get++
//fm: Show.get ([ti])
//      vrátí hodnotu na aktivním řádku nebo
//      EXPERIMENTÁLNÍ na zadaném ti-tém řádku (není-li obsazen, vrací prázdný řetězec)
  get  (ti) {
    var val= '';
    if ( arguments.length==1 ) {
      ti= Number(ti);
      val= this.owner.tlen>=ti ? this.owner.buf[this.owner.t+ti-1][this.id] : '';
    }
    else {
      Ezer.assert(this.owner.r>=0,
        "get: dotaz na sloupec '"+this.id+"' neaktivního řádku browse '"+this.owner.id+"'");
      val= this.owner.buf[this.owner.r-this.owner.b][this.id];
    }
    return val;
  }
// ------------------------------------------------------------------------------------ save
//fx: Show.save ()  EXPERIMENTÁLNÍ
//      zapíše změněnou hodnotu zpět do MySQL tabulky; lze volat jen po předchozím dvojkliku
//      a interaktivní změně hodnoty následované Enter, nesmí přitom dojít ke změně aktivního
//      řádku; show musí mít atribut 'data'.
//x: show [,,20,] { data:table.clmn, format:'u', proc onsubmit() { this.save } }
  save  () {
    var browse= this.owner, x= {cmd:'show_save', fields:[], joins:{}};
    x.key= browse.keys[browse.t+browse.tact-1-browse.b];
    x.val= browse.buf[browse.r-browse.b][this.id];
    x.old= this.original.value;
    browse._fillx(this,x);
    x.field= x.fields[0].field;
    return x;
  }
  save_  (y) {
    this.owner._opened_value= null;
    return y.rows ? 1 : 0;
  }
// ------------------------------------------------------------------------------------ save
//fx: Show.load (php_funkce,table,table_id,...)                                       EXPERIMENTÁLNÍ
//      zavolá php_funkci(table,table_id,seznam klíčů,...)
//      funkce vrátí pole klíč->hodnota
//      hodnoty budou zapsány jako hodnoty tohoto sloupce
  load  (php_fce,table,key_id,...args0) {
    let browse= this.owner, args= [table,key_id,browse.keys];
    for (const arg of args0) {
      args.push(arg);
    }
    this.ask({cmd:'ask',fce:php_fce,nargs:args.length,args:args},'load_');
    return this;
  }
  load_  (y) {
    let browse= this.owner, vi= this._id;
    for (let bi= 0; bi<browse.blen; bi++) {     // bi ukazuje do buf a keys
      browse.buf[bi][vi]= y.value[bi];
    }
    browse.DOM_show();                          // zobrazení
    return 1;
  }
// ------------------------------------------------------------------------------------ width+
//fm: Show.width ([w])
//      pokud je definováno w nastaví šířku sloupce, jinak ji vrátí
  width  (w) {
    var width_set= function (w) {
      this.DOM_th.width(w);
      for (let i= 1; i<this.DOM_qry.length; i++) {
        this.DOM_qry[i].width(w);
        if ( this.DOM_qry_select[i] )
          jQuery(this.DOM_qry_select[i].DOM).width(w);
        else
          this.DOM_qry[i].parent().width(w);
      }
      for (let i= 1; i<this.DOM_cell.length; i++) {
        this.DOM_cell[i].width(w);
      }
      // úprava případného show-select
      for (let i= 0; i<this.DOM_qry_select.length; i++) {
        // vytvoř z mapy seznam možností pro případný výběrový select
        let sel= this.DOM_qry_select[i];
        if ( sel ) {
          jQuery(sel.DOM_Block).width(w);
          jQuery(sel.DOM_Closure).width(w);
          jQuery(sel.DOM_Input).width(w);
          jQuery(sel.DOM_DropList).width(w);
        }
      }
    };
    var val= 0;
    if ( this.DOM_th ) {
      if ( w===undefined )
        // vrácení šířky
        val= this.DOM_th.width();
      else if ( Number(w)==0 ) {
        // změna šířky
        width_set.call(this,0);
        this.DOM_th.addClass('BrowseNoClmn');
        val= 1;
      }
      else {
        // nulová šířka
        width_set.call(this,w);
        this.DOM_th.removeClass('BrowseNoClmn');
        val= 1;
      }
    }
    return val;
  }
// ------------------------------------------------------------------------------------ compute+
//fm: Show.compute (fce_name[,init=0])
//      aplikuje funkci postupně na všechny načtené (tj. obecně více než na zobrazené) hodnoty
//      jednotlivých řádků sloupce.
//r: y[n]= fce(values[n],y[n-1]) pro n=min..max, kde y[min-1]= init a min..max jsou indexy values
  compute  (fce_name,init) {
    var x= init ? (isNaN(Number(init)) ? init : Number(init)) : 0;
    Ezer.assert(typeof(Ezer.fce[fce_name])=='function',"compute: '"+fce_name+"' neni jmenem funkce");
    for (var i=0; i<this.owner.blen; i++) {
      x= Ezer.fce[fce_name](x,this.owner.buf[i][this.id]);
    }
    return x;
  }
// ------------------------------------------------------------------------------------ set_query
//fx: Show.set_query (i,val[,reload=1])
//      nastaví dotazovací vzor do sloupce a provede jej, pokud není reload=0
//a: i - řádek (horní má 1)
//   val - vzor
//   reload - pokud je reload=0 nebude po změně pomínek proveden dotaz. POZOR fce ale vrací null
//r: reload - tzn. vrátí nulu, pokud se neprovádí dotaz
  set_query  (i,val,reload) {
    var x= null;
    if ( i>0 && i<=this.owner.options.qry_rows ) {
      this.DOM_qry_set(i,val);
    }
    if ( reload===undefined || reload==1 )
      x= this.owner.browse_load(null,null,null,null,-1);
    return x;
  }
  set_query_  (y) {
    return this.owner.browse_load_(y);
  }
// ------------------------------------------------------------------------------------ get_query
//fm: Show.get_query ([having=false[,i=0]])
//  pokud i>0 tak vrátí text vzoru na i-tém řádku (having se ignoruje)
//  pokud i=0 tak
//    pokud je having=false vrátí aktuální dotaz ve sloupci browse.show pro atribut data ve tvaru:
//    pokud je formát q/ tak jedna z variant
//        field BETWEEN 'value1%' AND 'value2%'
//        field >= 'value1%'
//        field <= 'value2%'
//      nebo pokud je formát q= nebo q#
//        field='value1%' OR field='value2%' OR ...
//      nebo pokud je formát q$
//        field LIKE 'value1%' COLLATE utf8_general_ci OR ...
//      nebo pokud je formát q%
//        field LIKE '%value1%' COLLATE utf8_general_ci OR ...
//      nebo pokud je formát q@ - vzor se ztransformuje atributem sql_pipe,
//        přípustné jsou jen ? a *, nakonec se nepřidává %
//        field LIKE BINARY 'value1' OR field LIKE BINARY 'value2' OR ...
//      nebo pokud je formát q* nebo pouze q
//        field LIKE BINARY 'value1%' OR field LIKE BINARY 'value2%' OR ...
//        pokud vzor končí na $ a pro q@ nepřidává se do vzorů koncové %
//        pokud vzor začíná - vyhledá se negace
//      nebo pokud je formát q. (pouze pro _file_)
//        field value1*  - povoleny jsou žolíky ? a *
//    pokud je sloupec s atributem data typu date, je možno použít varianty q/ q= q*
//        vzory musí mít tvar d.m.r (den.měsíc.rok)
//        pro variantu q* je možné použít místo d,m,y také *
//
//    Pro atribut expr lze použít pouze formát q*
//
//    Pokud je having=false a expr obsahuje agregační funkci pak je dotaz ignorován
//        pokud je having=true a expr obsahuje agregační funkci pak je dotaz vrácen
//          ostatní jsou ignorovány
//   get_query_pipe:'',                            // případné modifikátory pro formát q@
  get_query  (having,i) {
    if ( i ) {
      // pouze vrať text vzoru na i-tém řádku
      Ezer.assert(this.DOM_qry[i]!==undefined,"get_query má neexistující číslo vzoru "+i);
      return this.DOM_qry_get(i);
    }
    having= having ? true : false;
    var qry= '', q, qq, q1, q2, del= '', typ, id, not, end, files=false, pipes= '';
    if ( this.skill && this.qry_type ) {
      // pokud výraz obsahuje agregační funkci
      var agregate= this.options.expr && typeof(this.options.expr)=='string'
        && this.options.expr.match(/(GROUP_CONCAT|COUNT|SUM|MAX|MIN)\(/i) ? true : false;
      if ( having==agregate ) {
        id= this.options.data ? (this.view ? this.view.id : this.table.id) + '.' + this.data.id :
            agregate          ? this.id : this.options.expr;
        if ( this._fc('h') ) {
          // pokud je sloupec s formátem 'h' použijeme xml funkci 
          // viz https://mariadb.com/kb/en/library/extractvalue/
          id= `ExtractValue(${id},'//text()')`;
        }
        // datumy je třeba konvertovat, pro data zjistíme z tabulky, pro expr heuristikou z sql_pipe
        typ= this.options.data ? this.data.type :
             this.options.sql_pipe && this.options.sql_pipe.match(/sql_date/) ? 'date' : false;
        if ( this.qry_type=='/' && this.owner.options.qry_rows>1 ) {
          // 'q/' předpokládá nejméně dvouřádkový vzor určující interval hodnot (další řádky ignoruje)
          q1= this.DOM_qry_get(1);
          if ( q1 && typ=='date' ) q1= Ezer.fce.date2sql(q1);
          q2= this.DOM_qry_get(2);
          if ( q2 && typ=='date' ) q2= Ezer.fce.date2sql(q2);
          if ( q1 && q2 )
            qry= id+" BETWEEN '"+q1+"' AND '"+q2+"'";
          else if ( q1 )
            qry= id+">='"+q1+"'";
          else if ( q2 )
            qry= id+"<='"+q2+"'";
          else
            qry= '';
        }
        else if ( this.qry_type=='=' || this.qry_type=='#' ) {
          // 'q=' test na rovnost, 'q#' test na rovnost s číselníkovou hodnotou
          for (let iq= 1; iq<=this.owner.options.qry_rows; iq++ ) {
            if (( q= this.DOM_qry_get(iq) )) {
              if ( typ=='date' ) q= Ezer.fce.date2sql(q);
              qry+= del+id+"='"+q+"'";
              del= ' OR ';
            }
          }
        }
        else if ( this.qry_type=='$' || this.qry_type=='%' ) {
          // 'q$' a 'q%' test na vzor bez diakritiky, % hledá s levostranným %
          for (let iq= 1; iq<=this.owner.options.qry_rows; iq++ ) {
            if (( qq= this.DOM_qry_get(iq) )) {
              not= qq.substr(0,1)=='-' ? ' NOT' : '';
              qq= not ? qq.substr(1) : qq;
              end= qq.substr(-1,1)=='$' ? '' : '%';
              qq= end ? qq : qq.substr(0,qq.length-1);
              q= qq.replace(/\*/g,'%').replace(/\?/g,'_');
              if ( this.qry_type=='%' ) {
                q= '%'+q;
              }
              qry+= del+id+not+" LIKE '"+q+end+"' COLLATE utf8_general_ci";
              del= ' OR ';
            }
          }
        }
        else if ( this.qry_type=='.' ) {
          // 'q.' vyhledávání ve jménech souborů: lze použít ? a *
          files= true;
          for (let iq= 1; iq<=this.owner.options.qry_rows; iq++ ) {
            if (( q= this.DOM_qry_get(iq) )) {
              qry+= del+id+"="+q+'*';
              del= '|';
            }
          }
        }
        else if ( this.qry_type=='@' ) {
          // 'q@' - přípustné jsou jen ? a *, nakonec se nepřidává %
          for (let iq= 1; iq<=this.owner.options.qry_rows; iq++ ) {
            if (( qq= this.DOM_qry_get(iq) )) {
              q= qq.replace(/\*/g,'%').replace(/\?/g,'_');
              qry+= del+"UPPER("+id+") LIKE /*BINARY*/ UPPER('"+q+"')";
              var pid= this.options.sql_pipe;
              if ( !pid )
                Ezer.error('format q@ v show '+this.owner.id+'.'+this.id+' vyžaduje sql_pipe','C');
              pipes+= pid+"|"+q+"|";
              del= ' OR ';
            }
          }
        }
        else if ( this.qry_type=='*' ) {
          // 'q*' nebo 'q' standardní varianta
          for (let iq= 1; iq<=this.owner.options.qry_rows; iq++ ) {
            if (( qq= this.DOM_qry_get(iq) )) {
              not= qq.substr(0,1)=='-' ? ' NOT' : '';
              qq= not ? qq.substr(1) : qq;
              if ( typ=='date' ) {
                qq= Ezer.fce.date2sql(qq,1);
                qry+= del+id+not+" LIKE ('"+qq+"')";
              }
              else if ( agregate && qq=='$' ) {
                qry+= del+not+" ISNULL("+id+")";
              }
              else {
                end= qq.substr(-1,1)=='$' ? '' : (this.qry_type=='@' ? '' : '%');
                qq= end ? qq : qq.substr(0,qq.length-1);
                q= qq.replace(/\*/g,'%').replace(/\?/g,'_');
                qry+= del+"UPPER("+id+")"+not+" LIKE /*BINARY*/ UPPER('"+q+end+"')";
              }
              del= ' OR ';
            }
          }
        }
        else
          Ezer.error('show '+this.owner.id+'.'+this.id+' má chybu ve formátu '+"'q"+this.qry_type+"'");
      }
    }
    this.get_query_pipe= pipes; // předej žádost o modifikaci dotazu
    return qry ? (files ? qry : '('+qry+')') : '';
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _get_query
// vrátí pole [formát,vzor/1,...] nebo null
//   kde formát=q|q/... podle atributu format, vzor/i je text vzoru na i-tém řádku
// null vrací, pokud show má všechny vzory prázdné
  _get_query  () {
    var empty= true, cond;
    if ( this.qry_type ) {
      cond= [this.qry_type];
      for ( var iq= 1; iq<=this.owner.options.qry_rows; iq++ ) {
        if ( !this.DOM_qry_empty(iq) )
          empty= false;
        cond[iq]= this.DOM_qry_get(iq);
      }
    }
    return empty ? null : cond;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_sort
// změní značku řazení podle this.sorting
  DOM_sort  () {
    var path= Ezer.app.skin();
    this.DOM_img.prop('src',path+'/sort_'+this.sorting+'.png');
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  _sort
// změní řazení a provede je
  _sort  () {
    this.DOM_sort();
    var browse= this.owner;
    if ( browse.order_by && browse.order_by!=this ) { // zruš příp. řazení podle jiného sloupce
      browse.order_by.sorting= 'n';
      browse.order_by.DOM_sort();
    }
    if ( this.sorting=='n' ) {               // a zruš řazení
      browse.order= null;
      browse.order_by= null;
    }
    else {
      // definuj řazení podle tohoto sloupce - viz též v browse_clmn.start
      var id= this.data ? this.data.id : this.options.expr;
      if ( this._fc('h') ) {
        // pokud je sloupec s formátem 'h' použijeme xml funkci 
        // viz https://mariadb.com/kb/en/library/extractvalue/
        id= `ExtractValue(${id},'//text()')`;
      }
      this.owner.order= this.view ? this.view.id+'.' : '';
      this.owner.order+= id;
      this.owner.order+= this.sorting=='a' ? ' ASC' : ' DESC';
      browse.order_by= this;
    }
    browse.DOM_clear_focus();   // odstraň označení aktivního řádku
    // je třeba znovu načíst záznamy
    var code= [{o:'x',i:'browse_load',a:5}];
    new Eval(code,browse,[browse,null,null,null,null,-1],'sort');
  }
// =======================================================================================> Show DOM
//c: Show-DOM
//      řádky tabulkového zobrazení dat
//t: Block-DOM
//s: Block-DOM
// Ezer.Show.implement({
//   DOM_cell: null,                                 // pole prvků td
//   DOM_qry: [],                                    // pole prvků input pro části dotazu
//   DOM_qry_select: [],                             // Select, pokud je dotaz typu #
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM initialize
  DOM_initialize () {
    this.DOM_cell= null;                                 // pole prvků td
    this.DOM_qry= [];                                    // pole prvků input pro části dotazu
    this.DOM_qry_select= [];                             // Select, pokud je dotaz typu #
  }
// ------------------------------------------------------------------------------------ DOM add+
// f: Show-DOM.DOM_add ()
//      zobrazení sloupce tabulky, pokud má šířku>0
  DOM_add (DOM,data_only) {
    if ( !data_only ) {
      // kompletní přidání záhlaví, dotazů, ...
      this.owner._clmns++;
      // přidání záhlaví sloupce
      var w= this._w;
      var title= this.options.title||'';
      this.owner.DOM_head.append(
        this.DOM_th= jQuery(`
          <td class="th" title="${this.help}" style="width:${w}px">
            <img class="resize" src="${Ezer.version+'/client/img/browse_resize.png'}">
            <span>${title}</span>
        `)
      );
      // změny šířky sloupců
      this.DOM_th.resizable({
        handle: this.DOM_th.find('img.resize'),
        resizeHeight: false,
        onDrag: (e, $el, newWidth, newHeight, opt) => {
          Ezer.fce.echo(newWidth);
          let next= this.DOM_th.next();
          if ( !next.hasClass('resizable') ) return false;
          next.width(next.width() - (newWidth-this.DOM_th.width()));
        }
      });
      if ( !this._w )
        this.DOM_th.addClass('BrowseNoClmn');
      var sort= this._f('s');
      if ( sort>=0 ) {
        var fs= this.options.format.substr(sort+1,1);
        // požadavek na řazení: format: s s+ s-
        // (lze dynamicky ovlivnit funkcí set_sort(x) kde x=a|d|n pro ASC, DESC a vynechat
        // pokud po s následuje modifikátor + nebo - požaduje se počáteční seřazení
        this.sorting= fs=='+' ? 'a' : (fs=='-' ? 'd' : 'n');
        if ( this.sorting!='n' ) {
          if ( this.data ) {
            this.owner.order= this.view ? this.view.id+'.' : '';
            this.owner.order+= this.data.id + (this.sorting=='a' ? ' ASC' : ' DESC');
            this.owner.order_by= this;
          }
          else if ( this.options.expr ) {
            this.owner.order= this.options.expr + (this.sorting=='a' ? ' ASC' : ' DESC');
            this.owner.order_by= this;
          }
          else {
            this.owner.order= this._id + (this.sorting=='a' ? ' ASC' : ' DESC');
            this.owner.order_by= this;
          }
        }
        this.DOM_th.prepend(this.DOM_img= jQuery(`<img class="sort">`));
        this.DOM_sort();
        this.DOM_th
          .addClass('ShowSort')
          .click( () => {
            if ( !this._resizing ) {
              this.sorting= this.sorting=='n' ? 'a' : (this.sorting=='a' ? 'd' : 'n');
              this._sort();
              this.owner.DOM.addClass('focus');      // MOBILE: focus() zobrazí klávesnici
            }
          });
      }
      // přidání dotazových řádků sloupce
      var qry= this._f('q'), fq= 0;
      if ( qry>=0 ) {
        fq= this.options.format.substr(qry+1,1);
        if ( !fq || '/=#$%@*.'.indexOf(fq)<0 )
          fq= '*';
      }
      this.qry_type= fq;
      for (let i= 1; i<=this.owner.options.qry_rows||0; i++) {
        if ( qry<0 )
          // bez výběrového pole
          this.owner.DOM_qry_row[i]
            .append(jQuery(`<td class="BrowseNoQry">`));
        else {
          if ( fq=='#' ) {
            // výběr z číselníkových hodnot - musí být definován atribut map_pipe
            Ezer.assert(this.options.map_pipe,"formát 'q#' předpokládá atribut 'map_pipe'",this);
            let td;
            this.owner.DOM_qry_row[i].append(td= jQuery(`<td class="BrowseQry">`));
            // vytvoření procedury onchange
            var code= [{o:'t'},{o:'m',i:'_owner'},{o:'m',i:'_owner'},{o:'x',i:'browse_load'}];
            var sel_desc= {type:'select.map0',options:{_w:this._w,par:{noimg:1,subtype:'browse'},
                format:'wt',map_pipe:this.options.map_pipe,options:this.options.map_pipe,
                help:'výběr z číselníkových hodnot'},
              part:{onchanged:{type:'proc',par:{},code:code}}};
            var sel_owner= {DOM_Block:td,_option:{}};
            var sel= new SelectMap0(sel_owner,sel_desc,td,'','');
            sel.Items[0]= '?';
            sel.owner= this;
            sel.DOM_Block.css({marginTop:1});
            td.append(sel.DOM_Block);
            this.DOM_qry[i]= jQuery(sel.DOM_Input);
            this.DOM_qry_select[i]= sel;
          }
          else {
            // ostatní výběrová pole
            let hlp=  fq=='=' ? 'výběr zadaných hodnot' :
                      fq=='@' ? 'výběr podle vzorů s ?*' :
                      fq=='*' ? 'výběr podle vzorů včetně diakritiky s ?*-$' :
                      fq=='$' ? 'výběr podle vzorů bez diakritiky s ?*-$' :
                      fq=='%' ? 'výběr podle vzorů bez diakritiky s ?*-$ s počáteční *' :
                      fq=='/' ? 'výběr všeho od-do' 
                              : 'výběrové pole',
                alg= this._f('r')>=0 ? 'right' : this._f('c')>=0 ? 'center' : 'left';
            this.DOM_qry_select[i]= null;
            this.owner.DOM_qry_row[i].append(
              jQuery(`<td class="BrowseQry">`).append(
                this.DOM_qry[i]= jQuery(`<input title="${hlp}" style="text-align:${alg}">`
            )));
          }
          if ( !this.owner.first_query )
            this.owner.first_query= this.DOM_qry[i];
          if ( this.DOM_qry[i] ) {
            this.DOM_qry[i]
              .click( event => {
                  event.target.focus(); // kvůli Chrome - FF focus vyvolává i při neošetřeném click
              })
              .focus( event => {
                  this.owner.DOM_table.addClass('changed');
              })
              .blur(event => {
                  this.owner.DOM_table.removeClass('changed');
              })
              // ovládání pásu dotaz; klávesnicí
              .keypress( event => {
                  switch (event.keyCode) {
                  case 27:   // Esc - zrušit hledací vzory
                    event.stopPropagation();
                    this.owner.init_queries();
                    break;
                  case 13: // Enter - provést hledání
                    event.stopPropagation();
                    this.owner._ask_queries(0,0,event.shiftKey);
                    break;
                  }
               });
          }
        }
      }
    }
    // přidání datových řádků sloupce
    this.DOM_cell= [];
    for (let i= 1; i<=this.owner.tmax; i++) {
      this.owner.DOM_row[i].append(
        this.DOM_cell[i]= jQuery(`
          <td class="${i%2?'tr-odd':'tr-even'}"
            style="text-align:${this._f('r')>=0 ? 'right' : this._f('c')>=0 ? 'center' : 'left'}">
        `)
      );
      // pokud je ve format 'u' pak dvojklik vyvolá onsubmit,
      // pokud má user pro show skill na zápis
      if ( this._fc('u') && this.skill==2 ) {
        this.DOM_cell[i]
          .dblclick( el => {
            el.stopPropagation();
            var td= jQuery(el.target), tr= td.parent(), show= this, browse= this.owner;
            if ( browse.enabled ) {
              let i= jQuery(tr).data('i');
              if ( i && i <= browse.tlen ) {
                // uzavření případně předchozí otevřené - jakoby bylo blur
                if ( browse._opened ) {
                  var td1= browse._opened.parent();
                  if ( td1 )
                    td1.text(browse._opened_value);
                  browse._opened_value= null;
                  browse._opened.empty();
                  browse._opened= null;
                }
                // dblclick na datovém řádku
                browse.tact= i;
                var val= td.text();
                browse._opened_value= val;
                td.text('');
                td.append( 
                  browse._opened= jQuery(`<input class="td_input" type="text">`)
                    .val(val)
                    .css({width:w})
                    .keypress( event => {
                      if (event.key=='Enter') {   // vrátit původní hodnotu
                        event.stopPropagation();
                        show.let(browse._opened.val());
                        show.fire('onsubmit',[browse.keys[browse.t+i-1-browse.b],event.ctrlKey?1:0]);
                      }
                    })
                    .blur( () => {
                      td.text(val);
                      browse._opened_value= null;
                      browse._opened.empty();
                      browse._opened= null;
                    })
                );
                browse._opened.focus();
              }
            }
            return false;
          });
      }
    }
  };
// ------------------------------------------------------------------------------------ DOM set+
//f: Show-DOM.DOM_set (i)
//      zobrazí hodnotu i-tého řádku (1..tlen) s uvážením případné specifikace za ':'
//      pro datové hodnoty lze uvádět: d.m, pro zaokrouhlení čísel: i;
//      pokud format obsahuje 't' bude hodnota zobrazena i v title
//      pokud format obsahuje 'h' nebudou zobrazeny HTML a PHP tagy
  DOM_set (ti) {
    if ( this.DOM_cell ) {
      var val= this.owner.buf[ti-1][this.id], spec= this._f(':');
      if ( this._fc('t') ) {
        // zobrazení hodnoty i jako title pro format:'t' - před případným zaokrouhlením
        this.DOM_cell[ti].prop('title',val);
      }
      if ( val && spec=='i' )
        val= Math.round(val);
      if ( val==0 && spec=='e' )
        val= '';
      if ( val && spec=='d.m' ) {
        var dmy= val.split('.');
        val= dmy[0]+'.'+dmy[1];
      }
      if ( val && Ezer.browser=='IE' ) {           // IE8 nezvládá white-space:nowrap
        val= val.replace(/\n/g,' ').replace(/\r/g,'');
      }
      if ( this._fc('h') ) {
        // potlačení zobrazení HTML a PHP tagů
        val= Ezer.fce.strip_tags(val);
      }
      val= val==null ? '' : val;
      this.DOM_cell[ti].text(val);
    }
  }
// ------------------------------------------------------------------------------------ DOM show+
//f: Show-DOM.DOM_show (r)
//      zobrazí hodnotu svého sloupce záznamu r (b..b+blen) v řádku tabulky
//      s uvážením případné specifikace za ':' - pro datové hodnoty lze uvádět: d.m,
//      pro zaokrouhlení čísel: i ;
//      pokud format obsahuje 't' bude hodnota zobrazena i v title
//      pokud format obsahuje 'h' nebudou zobrazeny HTML a PHP tagy
  DOM_show (r) {
    if ( this.DOM_cell ) {
      var browse= this.owner;
      Ezer.assert(browse.b<=r&&r<=browse.b+browse.blen && browse.buf[r-browse.b],
        "Show.DOM_show("+r+") - mimo rozsah");
      // získání transformovanou hodnotu ti-tého řádku v buferu
      var val= browse.buf[r-browse.b][this.id];
      if ( this._fc('t') ) {
        // zobrazení hodnoty i jako title pro format:'t' - před případným zaokrouhlením
        this.DOM_cell[r-browse.t+1].attr('title',val);
      }
      // zjištění případných transformací
      var spec= this._f(':');
      if ( val && spec=='i' )
        val= Math.round(val);
      if ( ( val==0 || val=='00:00:00' ) && spec=='e' )
        val= '';
      if ( val && spec=='d.m' ) {
        var dmy= val.split('.');
        val= dmy[0]+'.'+dmy[1];
      }
      if ( this._fc('h') ) {
        // potlačení zobrazení HTML a PHP tagů
        val= Ezer.fce.strip_tags(val);
      }
      val= val==null ? '' : val;
      this.DOM_cell[r-browse.t+1].text(val);
    }
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_qry_set+
// funkce pro nastavení hodnoty dotazu na i-tém qry-řádku
  DOM_qry_set (i,val) {
    if ( this.DOM_qry_select[i] ) {
      this.DOM_qry_select[i]._key= 0;
    }
    this.DOM_qry[i].val(val);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_qry_get+
// funkce pro vrácení hodnoty dotazu na i-tém qry-řádku
  DOM_qry_get(i) {
    return this.DOM_qry_select[i]
      ? this.DOM_qry_select[i]._key
      : this.DOM_qry[i].val();
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -- - -  DOM_qry_empty
// funkce vrací true. pokud je dotaz na i-tém qry-řádku definován
  DOM_qry_empty (i) {
    return this.DOM_qry_select[i]
      ? this.DOM_qry_select[i]._key==0
      : this.DOM_qry[i].val()=='';
  }
}

