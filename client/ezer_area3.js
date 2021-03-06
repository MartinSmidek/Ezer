// Tento modul doplňuje ezer.js o Ezer.Area a o funkce debuggeru
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
// ===========================================================================================> Area
//c: Area ([options])
//      základní třída
//s: Block
//e: area_onstart - (this) po vytvoření area, parametrem je vytvořený objekt
//e: area_onclick - (p) pokud dojde ke kliknutí na element <a href=p ...> v area (viz metoda set)
class Area extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - initialize
  initialize () {
    super.initialize();
    this.tree= null;                   // vnořený objekt MooTreeControl
  }
// quiet způsobí potlačení události area_onstart
  constructor (owner,desc,DOM,options,id,par,quiet) {
    super(owner,desc,DOM,id);
    Object.assign(this.options,options); // moo: this.setOptions(options);
    // substituce předaných parametrů do title
    var subst= {};
    if ( desc.options.title ) {
      var i= 0;
      var title= desc.options.title.replace(/\\?\{([\w]+)\}/g, function(match, name) {
        var value= '';
        if ( i<par.length ) {
          value= subst[name]= par[i++];
        }
        return value;
      }.bind(this));
      this.DOM_add(DOM,title);
    }
    else {
      // area bude "přilepena" na DOM element pro který je ID=par[0]
      this.DOM_attach(par[0]);
    }
    // vložení podčástí
    this.subBlocks(desc,this.DOM_Area);
    // definice hodnot proměnných podle substituovaných hodnot
    for (var name in subst) {
      var area_var= this.part[name];
      if ( area_var && area_var instanceof Ezer.Var ) {
        area_var.set(subst[name]);
      }
    }
    if ( !quiet )
      this.fire('area_onstart',[this]);
  }
  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_attach
  // DOM - DOM_Block - DOM_Area - id je hledáno v rámci panel.DOM
  DOM_attach (id) {
    // nalezneme panel
    var panel= null;
    for (var o= this.owner; o; o= o.owner) {
      if ( o.type.substr(0,5)=='panel' ) { panel= o; break; }
    }
    if ( panel && panel.DOM_Block ) {
      // nalezení DOM elementu a připojení událostí
      this.DOM_Block= this.DOM_Block= panel.getElement(id);
      // obsluha podporovaných událostí
      var fce= this.desc.part ? this.desc.part.onclick : null;
      if ( fce ) {
        this.DOM_Block.addEvent('click', function(ev) {
          new Eval(fce.code,this,[],'onclick',false,false,fce,0);
          return false;
        }.bind(this));
      }
    }
  }
  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  DOM_add
  // DOM - DOM_Block - DOM_Area
  DOM_add (DOM,title) {
    // vytvoření instance area
    this.DOM_Block= jQuery(`<div class="Area3">`)
      .appendTo(this.DOM);
    this.DOM_optStyle(this.DOM_Block);
    // obsluha podporovaných událostí
    var fce= this.desc.part ? this.desc.part.onclick : null;
    if ( fce ) {
      this.DOM_Block
        .click( ev => {
        new Eval(fce.code,this,[],'onclick',false,false,fce,0);
        return false;
      });
    }
    this.DOM_Block.html(title);
    this.DOM_Area= this.DOM_Block.children().first() ? this.DOM_Block.children().first() : null;
  }
// ----------------------------------------------------------------------------------------- delete
//fm: Area.delete ()
//      vlastník objektu
  'delete' () {
    this.DOM_Block.destroy();
    super.delete();     // this.parent();
    return 1;
  }
// ------------------------------------------------------------------------------------------ empty
//fm: Area.empty ()
//      vymaže obsah area
  empty (all) {
    if ( this.DOM_Area instanceof Element ) {
      this.DOM_Area.empty();
    }
    else if ( this.DOM_Area ) {
      this.DOM_Block.html('');
      this.DOM_Area= null;
    }
    return 1;
  }
// ------------------------------------------------------------------------------------------- init
//fm: Area.init (values)
//      obnoví obsah area podle title a obsahu objektu values={ai:vi,...}
//      substitucí {ai} za vi, reference <a href='url'> uvnitř title budou generovat událost
//      area_onclick(url,id) pokud x začíná stejně jako url aplikace samé
  init (values) {
    var title= this.desc.options.title;
    if ( typeof(values)=='object' ) {
      title= title.replace(/\\?\{([\w]+)\}/g, function(match, name) {
        return values[name]||'';
      }.bind(this));
    }
    else {
      title= title.replace(/\\?\{([\w]+)\}/g, function(match, name) {
        var value= '';
        var area_var= this.part[name];
        if ( area_var && area_var instanceof Ezer.Var ) {
          value= area_var.get();
        }
        return value;
      }.bind(this));
    }
    this.DOM_Block.html(title);

    var path= location.protocol+'//'+location.hostname+(location.port?':'+location.port:'');
    path+= location.pathname;
    this._set_onclick(this.DOM_Block);
    this.DOM_Area= this.DOM_Block.firstChild ? this.DOM_Block.firstChild : null;
    return 1;
  }
// ------------------------------------------------------------------- _set_onclick
// odkazům dovnitř aplikace zamění defaultní událost za area_onclick
  _set_onclick (jElem) {
    var path= location.protocol+'//'+location.hostname+(location.port?':'+location.port:'');
    path+= location.pathname;
    jElem.find('a').each(function(i,el){
      var href= el.href, prefix= href.substr(0,path.length);
      if ( prefix==path ) {
        el.addEvent('click',function(ev){
          Ezer.pushState(href);
          // ------------------------------------------------- událost area_onclick
          this.fire('area_onclick',[href,ev.target.id],ev);
          return false;
        }.bind(this));
      }
      else if ( !el.target ) {
        el.target= 'panel';
      }
    }.bind(this));
  }
// ------------------------------------------------------------------------------------------ focus
//fm: Area.focus (id_element)
//      označí element a jeho rodičovský element jako aktivní tzn. definuje
//      jim jako jediným v Area class='active'
  focus (id_elem) {
    var oblast= this.DOM_Block; //.getElementById(id_oblast);
    if ( oblast ) {
      oblast.find('.active').each(function(i,ael){
        jQuery(ael).removeClass('active');
      });
      var elem= oblast.find('#'+id_elem);
      if ( elem ) {
        elem.addClass('active');
        elem.parentNode.addClass('active');
      }
    }
    return 1;
  }
// ======================================================================================> AREA TREE
// ------------------------------------------------------------------------------------ tree_expand
//fm: Area.tree_expand (n)
//      zobrazí n úrovní stromu, tree_expand(0) jej svine
  tree_expand (n) {
    this.tree.collapse();
    if ( n )
      this.tree.root.toggle(true, true, n-1);
    return 1;
  }
// ------------------------------------------------------------------------------------ tree_select
//fm: Area.tree_select (id)
//      nastaví uzel jako aktivní
  tree_select (id) {
    var node= this.tree.get(id);
    this.tree.select(node);
    return 1;
  }
// ------------------------------------------------------------------------------------ tree_insert
//fm: Area.tree_insert (id)
//      vloží uzel pod daný uzel
  tree_insert (id) {
    var node= null, old= this.tree.get(id);
    if ( old ) {
      var node_id= id+'.*';
      node= old.insert({id:node_id,text:'*'});
    }
    return node;
  }
// -------------------------------------------------------------------------------------- tree_stub
//fm: Area.tree_stub (id)
//      odstraní všechny následníky
  tree_stub (id) {
    var node= this.tree.get(id);
    if ( node ) {
      node.clear();
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ tree_remove
//fm: Area.tree_remove (id)
//      odstraní uzel, aktivní bude předchůdce
  tree_remove (id) {
    var node= this.tree.get(id);
    if ( node && node.parent ) {
      var p= node.parent;
      node.remove();
      this.tree.select(p);
    }
    return 1;
  }
// ------------------------------------------------------------------------------------- tree_shift
//fm: Area.tree_shift (id,down)
//      posune uzel pokud down=1 dolů, down=0 nahoru
  tree_shift (id,down) {
    var node= this.tree.get(id);
    if ( node && node.parent ) {
      var p= node.parent;
      var len= p.nodes.length;
      for (var i= 0; i<len; i++) {
        if ( p.nodes[i].id==id ) {
          break;
        }
      }
      if ( down && i<len-1 ) {
        p.nodes[i]= p.nodes[i+1];
        p.nodes[i+1]= node;
      }
      else if ( !down && i>0 ) {
        p.nodes[i]= p.nodes[i-1];
        p.nodes[i-1]= node;
      }
      p.update();
    }
    return 1;
  }
// ------------------------------------------------------------------------------------ tree_update
//fm: Area.tree_update (id,new_idn,data)
//      zamění obsah uzlu daného id, poslední část se zamění za new_idn pouze, je-li neprázdné
  tree_update (id,new_idn,data) {
    var node= this.tree.get(id);
    if ( node ) {
      node.data= data;
      if ( new_idn ) {
        delete this.tree.index[id];
        var fid= id.split('.');
        fid[fid.length-1]= new_idn;
        node.text= new_idn;
        node.id= fid.toString();
        this.tree.index[node.id]= node;
        node.update();
      }
    }
    return 1;
  }
// -------------------------------------------------------------------------------------- tree_dump
//fm: Area.tree_dump ()
//      vytvoří obraz celého stromu ve formátu JSON
  tree_dump () {
    var js= '';
    function walk(root) {
      var id= root.id.split('.');
      js+= '{"prop":{"id":"'+id[id.length-1]+'","data":'+JSON.stringify(root.data, undefined, 2)+'}';
      if ( root.nodes.length ) {
        js+= ',\n "down":[';
        var n= 0;
        for (var i= 0; i<root.nodes.length; i++) {
          js+= n ? ',' : '';
          walk(root.nodes[i]);
          n++;
        }
        js+= ']';
      }
      js+= '}';
    }
    walk(this.tree.root);
    return js;
  }
// -------------------------------------------------------------------------------------- tree_show
//fm: Area.tree_show (desc[,id,clone=0])
//      zobrazí v area strom pomocí balíku MooTree, desc je popis ve formátu
//      pokud je dáno id, bude strom pod tímto elementem.
//      Zadání clone=1 způsobí zkopírování desc do vntřní kopie před konstrukcí stromu (to je
//        zapotřebí, pokud je desc zadáné jako konstanta Ezert skriptu)
//      Zadání options je objekt, který umožní pozměnit vzhled, s následujícími složkami
//        mode:'folders' zobrazí všechny uzly jako ikony složek 
//        mode:'files' (default) zobrazí uzly bez následníků jako soubory
//        theme:'mootree_white.gif'  - světlý motiv
//        theme:'mootree.gif'  - tmavý motiv
//   nodes: [ node, ... ]
//   node:  {prop:{text:<string>,down:nodes}}
//e: tree_onclick - (id,node,node.data.json,merge.data.json)
  tree_show (desc0,id,clone,options) {
    // načte další generaci pod root podle popisu v desc
    function load(root,desc) {
      if ( desc.down ) {
        for (var i= 0; i<desc.down.length; i++) {
          var down= desc.down[i];
          if ( !down.prop.text )
            down.prop.text= down.prop.data && down.prop.data.name||down.prop.id;
          // úprava down.prop.id na složené jméno
          down.prop.id= root.id+'.'+down.prop.id;
          var node= root.insert(down.prop);
          load(node,down);
        }
      }
    }
    var active= null;
    var desc= desc0;
    if ( clone )
      desc= jQuery.extend({},desc); // moo Object.clone(desc);
    if ( !this.tree ) {
      var root= {text:'site',open:true};
      this.tree= new MooTreeControl({div:id ? jQuery(`#${id}`) : this.DOM_Block,grid:true,
        mode:options&&options.mode?options.mode:'files',             // folders|files
        path:Ezer.paths.images_lib,     // cesta k mootree.gif
        theme:options&&options.theme?options.theme:'mootree_white.gif',
        // ----------------------------------------------------------------- onclick
        onClick: function(node,context) { // při kliknutí na libovolný uzel context=true/undefined
          // spočítáme sumu data - shora dolů
          if ( node ) {
            var data= {}, datas= [], texts= '', del= '';
            for (var x= node; x; x= x.parent) {
              datas.unshift(x.data);
              texts= (x.text||'')+del+texts; del= '|';
            }
//            datas.each(function(d){
//              Object.merge(data,d);
//            });
            for (let d of datas) {
              Object.assign(data,d);
            }
            var ndata= JSON.stringify(node.data, undefined, 2);
            var adata= JSON.stringify(data, undefined, 2);
            var fid= node.id.split('.');
            var idn= fid[fid.length-1];
//             var node_id= node.id.split('.');
//             if ( node.data.pos ) {
//               Ezer.sys.dbg= {app:node.data.pos.app,file:node.data.pos.file,
//                 start:node.data.pos.start,start_lc:node.data.pos.lc||''};
//             }
            if ( context )
              this.fire('tree_oncontextmenu',[node.id,idn,node.data,ndata,adata,texts,node.text]);
            else
              this.fire('tree_onclick',[node.id,idn,node.data,ndata,adata,texts,node.text]);
          }
          return false;
        }.bind(this)
      }, root);
    }
    else {
      active= this.tree.selected ? this.tree.selected.id : null;
      this.tree.root.clear();
    }
    this.tree.disable(); // potlačí zobrazení
    if ( desc && desc.prop ) {
      Object.assign(this.tree.root,desc.prop);
      this.tree.root.text= this.tree.root.data && this.tree.root.data.name||this.tree.root.id;
      this.tree.index[this.tree.root.id]= this.tree.root;
      load(this.tree.root,desc);
      this.tree.expand();
    }
    if ( active && this.tree.get(active) )
      this.tree.select(this.tree.get(active));
    this.tree.enable(); // zviditelní
    return 1;
  }
}

// ======================================================================================> STRUKTURY
// --------------------------------------------------------------------------------------- new_area
//fs: str.new_area (name,parent[,par])
//      vytvoření instance area podle name, obsahujícím buďto string s úplným jménem area
//      nebo Ezer objekt
//      A) vnořené do parent zadaného jako ID (string) nebo jak Ezer objekt
//         nebo jako DOM element (například výsledek volání new_area)
//  ?   B) pokud není zadán parent, dojde k napojení nové area na element s ID=par1
//      - volá se výrazem new_area
//s: funkce
Ezer.str.new_area= function() {
  var that= arguments[0];       // volající objekt Eval
  var args= arguments[1];       // hodnoty parametrů a proměnných volajícího objektu Eval
  var name= new Eval(arguments[2],that.context,args,'new_area-name');
  var area_desc= name.value;
  var parent= new Eval(arguments[3],that.context,args,'new_area-parent');
  var npar= arguments.length-4, par= [];
  for (var i=0; i<npar; i++) {
    var val= new Eval(arguments[4+i],that.context,args,'new_area-par-'+i);
    par[i]= val.value;
  }
  var ezer_area= null;
  var DOM= null;
  if ( typeof(area_desc)=='string' ) {
    // jméno musí být úplné
    area_desc= Ezer.code_name(area_desc,null,that.context);
    if ( area_desc ) area_desc= area_desc[0];
  }
  if ( area_desc && area_desc.type=='area' ) {
    area_desc= area_desc.desc ? area_desc.desc : area_desc;
    // nalezení instance vlastnícího panelu
    var panel= null;
    for (var o= that.context; o; o= o.owner) {
      if ( o.type.substr(0,5)=='panel' ) {
        panel= o;
        break;
      }
    }
    if ( !panel )
      Ezer.error('výraz new_area není zanořen do panelu','S');
    if ( !parent.value ) {
    }
    else {
      if ( typeof(parent.value)=='string' ) {
        DOM= panel.DOM_Block.getElementById(parent.value);
        if ( !DOM ) Ezer.error(name.value+" nelze najít id='"+parent.value+"' ");
      }
      else if ( typeof(parent.value)=='object' ) {
        if ( parent.value instanceof Element )
          DOM= parent.value;
        else if ( parent.value.DOM_Block || parent.value.DOM ) {
          DOM= parent.value.DOM_Block || parent.value.DOM;
        }
      }
      if ( !DOM )
        Ezer.error(name.value+' nelze napojit na 2.parametr');
    }
    // vytvoření Ezer representace s událostí area_oncreate
    ezer_area= new Area(panel,area_desc,DOM,{},area_desc.id,par,false);
  }
  else
    Ezer.error(name.value+' je neznámé jméno - očekává se jméno area');
  that.stack[++that.top]= ezer_area;
  that.eval();
};

// ============================================================================================> FCE
// ---------------------------------------------------------------------------------------- url_get
//ff: fce.url_get ([get])
//   vrátí aktuální url z window.history, pokud je definován parametr get
//   vrátí se jen hodnota GET parametru jehož je jménem
//s: funkce
Ezer.fce.url_get= function (get) {
  return get ? get_url_param(get) : location.href;
};
// --------------------------------------------------------------------------------------- url_push
//ff: fce.url_push (url)
//   vloží url do zásobníku window.history
//s: funkce
Ezer.fce.url_push= function (url) {
  Ezer.pushState(url);
  return 1;
};
// ------------------------------------------------------------------------------------ json_decode
//ff: fce.json_decode (string)
//   převede JSON zápis objektu na objekt
//s: funkce
Ezer.fce.json_decode= function (string) {
  var obj= null;
  try {
    obj= JSON.parse(string);
  }
  catch (e) {
    Ezer.fce.warning("json_decode: chybná syntaxe");
  }
  return obj;
};
// ------------------------------------------------------------------------------------ json_encode
//ff: fce.json_encode (obj)
//   převede objekt na jeho JSON zápis
//s: funkce
Ezer.fce.json_encode= function (obj) {
  return JSON.stringify(obj);
};

// ===========================================================================================> META
// --------------------------------------------------------------------------------------- meta_tree
//ff: meta_tree ([id_type=id])
//      vrátí strom aplikace, uzly budou označeny podle id (default) nebo podle _sys
Ezer.fce.meta_tree= function (id_type) {
  var tree;
  function walk(root) {
    var title= (root.options.include ? ' '+root.options.include : '')
             + (root._library        ? ' #' : '');
    var data= {include:root.options.include||'',library:root._library ? '#' : '',pos:null};
    var id= id_type=='_sys'
          ? (root.options._sys ? (root.options._sys=='*' ? root.id : root.options._sys) : '')
          : root.id;
    var name= root.options.title ? Ezer.fce.replace_fa(root.options.title) : '';
    var node= {prop:{id:id,text:name,title:title,data:data},down:[]};
    // zjištění zdrojového textu do data.pos
    var pos= root.app_file();
    node.prop.data.pos= {app:pos.app,file:pos.file,start:root.self()};
    if ( root.desc && root.desc._lc ) {
      node.prop.data.pos.lc= root.desc._lc;
    }
    // projdeme podstrom
    if ( root.part ) {
//       $each(root.part,function(x) {
      for (const ix in root.part) { const x= root.part[ix];
        if ( x instanceof MenuLeft ) {
//           $each(x.part,function(g) {
          for (const ig in x.part) { const g= x.part[ig];
            if ( g instanceof MenuGroup ) {
              node.down.push(walk(g));
            }
          }
        }
        else if ( x instanceof MenuMain || x instanceof Tabs || x instanceof Panel
          || x instanceof MenuGroup || x instanceof Item ) {
          node.down.push(walk(x));
        }
      }
    }
    return node;
  }
  tree= walk(Ezer.run.$).down[0];
  return tree;
};
