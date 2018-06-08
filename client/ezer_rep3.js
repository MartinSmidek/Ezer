// Tento modul obsahuje implementaci bloků REPORT a BOX jako doplněk k ezer.js
// =========================================================================================> REPORT
//c: Report (typ,id,owner,root,symb)
//      přeložený blok report (tisková sestava)
//t: Block
//s: Block
class Report extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
//   options: {},
    this.status= 'start';      // 'start', 'init', 'check'
    this.ctx= {};              // kontext pro výpočet rozměrů
    this.zoom= 100;            // zoom v procentech zvětšení (100 odpovídá 1:1)
    this.outline= 0;           // 0 nic, 1 pojmenované boxy, 2 všechny boxy
    this.pages= [];            // pole výšek jednotlivých stránek reportu
    this.type= '';             // 'batch' ovlivní funkci report_check a report_print
    this.batch= '';            // zpracovaná dávka (pokud type=='batch') pro print
  }
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    this.subBlocks(desc,DOM);
  }
// ------------------------------------------------------------------------------------
//fm: Report.report_batch (op)
//      inicializuje report pro tisk dávky
  report_batch (op) {
    switch ( op ) {
    case 'single':                // tisk po jednom
      this.type= '';
      this.batch= '';
      break;
    case 'init':                  // inicializace dávky
      this.type= 'batch';
      this.batch= '';
      break;
    case 'add':                   // zařazení reportu do dávky
      if ( this.status=='check' || this.status=='pages' ) {

        var style= this.batch ? " style='page-break-before:always;'" : '';
        this.batch+= "\n  <div class='page'"+style+">&nbsp;";  // &nbsp; = aspoň jeden neabsolutní prvek
        this.batch+= '\n'+this.print_page();
        this.batch+= "\n  </div>";

//         this.batch+= '\n'+this.print_page();  = původní řešení místo 4 řádků výše

        this.report_init();
      }
      else Ezer.error(this.id+".report_batch('add'): report nebyl připraven funkcí report_check",0);
      break;
    }
    return true;
  }
// ------------------------------------------------------------------------------------
//fm: Report.report_init ()
//      inicializuje report s dynamickými boxy
  report_init () {
    this.ctx= {};
    this.pages= [];
    var sb= null;
    for (var ib in this.part) if ( this.part[ib].type=='box' ) {
      var box= this.part[ib], bob= box.open;
      // definuj pevný dynamický box
      if ( !bob ) {
        bob= new Bob(box,null,sb);
        bob.value= box.value;
        box.open= bob;
      }
      bob.init();                // inicializuj počáteční bob
      this.ctx[box.id]= bob;     // a dej do jinak prázdného kontextu
      sb= bob;
      // provede přidání jedné instance boxům označeným jako xactive
      if ( box.options.xactive )
        box.init(0);
    }
    this.status= 'init';
    return true;
  }
// ------------------------------------------------------------------------------------
//fm: Report.report_check ([n_max=2])
//      přepočítá n_max krát rozměry boxů, připraví report k tisku
//a: n_max - povolený počet opakování výpočtu
//r: 1 - pokud byly v reportu vyřešeny všechny rozměry
//   0 - pokud zůstal nejaký výraz nerozřešen - viz manuál
  report_check (n_max) {
    var ok= 0, pass= 0;
    n_max= n_max||2;
    if ( this.status!='start' ) {
      for (var n= 1; n<=n_max; n++) {
        pass++;
        var changes= 0;
        for (var ib in this.part) if ( this.part[ib].type=='box' ) {
          var bob= this.part[ib].open;
          changes+= bob.check(this.ctx);
        }
        // pokud bylo vše spočteno, ukonči cyklus
        if ( !changes ) {
          this.status= 'check';
          ok= 1;
          break;
        }
      }
    }
    else Ezer.error(this.id+".report_check: report nebyl inicializován ",0);
  //                                                         trace("report_check:"+ok+",changes="+changes+",passes="+pass);
    return ok;
  }
// ------------------------------------------------------------------------------------
//fm: Report.report_repage ([n_max=2])
//      přestránkuje report, vrátí počet stránek (je omezitelný parametrem)
//a: n_max - povolený počet stránek
//r: n - počet stránek reportu
//   0 - pokud nastala chyba
  report_repage (n_max) {
    // nb určuje spodek stránky, vynucující předchod na n-tou stránku
    n_max= n_max||2;
    var pages= 0;
    var nb= this.options._t+this.options._h;
    this.pages[0]= 0;
    if ( this.status=='check' ) {
      for (var n= 1; n<=n_max; n++) {
  //                                 trace('report_repage: strana '+n+' od '+this.pages[n-1]);
        var b= 0;
        for (var ib in this.part) if ( this.part[ib].type=='box' ) {
          var bob= this.part[ib].open;
          b= bob.check_page(null,0,this.pages[n-1]+nb);
          if ( b ) break;
        }
        this.pages[n]= b ? b : this.pages[n-1]+nb;
        // pokud má poslední stránka nulovou výšku, je konec reportu
        if ( !b ) {
          this.status= 'pages';
          pages= n-1;
          break;
        }
        var checked= this.report_check(5);
        if ( !checked ) Ezer.error(this.id+".report_repage: report je příliš složitý ",0);
      }
    }
    else Ezer.error(this.id+".report_repage: report nebyl zkontrolován nebo je už stránkovaný",0);
    return pages;
  }
// ------------------------------------------------------------------------------------
//fm: Report.get_html ([zoom [,outline [,pretty]]])
//      vrátí vnořené boxy jako HTML řetězec
//a: zoom - měřítko v procentech, default je 100 (zatím lze jen 100%)
//   outline - 1 pojmenované boxy, 2 všechny boxy
//   pretty - 1 odsaď vygenerované html
  get_html (zoom,outline,pretty) {
    var html= "";
    if ( this.status=='check' || this.status=='pages' ) {
      this.outline= outline||0;
      this.zoom= 100; //zoom||100;
      var c= this.options, style= EZER_style_bob(c._l,c._t,c._w,c._h,this.zoom);
      html= "<div class='report' style='"+style+"'>\n";
      // projdi statické boxy na nejvyšší úrovni
      for (var ib in this.part) if ( this.part[ib].type=='box' ) {
        var bob= this.part[ib].open;
        html+= bob.get_html(pretty?'&nbsp;':'',0)+(pretty ? "\n" : '');
      }
      html+= "</div>";
    }
    else Ezer.error(this.id+".get_html: report nebyl připraven funkcí report_check",0);
    return html;
  }
// ------------------------------------------------------------------------------------
//fm: Report.get_json ()
//      vrátí report a jeho vnořené boxy jako JSON řetězec
//      {format:...,boxes:[{type:id,top....,[id]},...]}
  get_json () {
    var json, del= '';
    json= "{'format':'"+(this.options.format||'')+"','boxes':[";
    if ( this.status=='check' || this.status=='pages' ) {
      for (var ib in this.part) if ( this.part[ib].type=='box' ) {
        var bob= this.part[ib].open;
        json+= del+bob.get_json();
        del= ',';
      }
    }
    else Ezer.error(this.id+".get_box: report nebyl připraven funkcí report_check",0);
    json+= ']}';
    return json;
  }
// ------------------------------------------------------------------------------------
//fm: Report.get_page (n)
//      vrátí vnořené boxy n-té stánky jako HTML řetězec
//a: n - pořadí stránky
  get_page (n) {
    var html= '';
    if ( this.status=='check' || this.status=='pages' ) {
//       var nb= this.options._t+this.options._h;
      this.outline= 0; // outline||0;
      this.zoom= 100;  // zoom||100;
      var c= this.options, style= EZER_style_bob(c._l,c._t,c._w,c._h,this.zoom);
      html= "\n   <div class='report' style='"+style+"'>";
      // projdi statické boxy na nejvyšší úrovni
  //                                 trace('get_page: strana '+n+' od '+this.pages[n-1]+' do '+this.pages[n]);
      for (let ib in this.part) if ( this.part[ib].type=='box' ) {
        var bob= this.part[ib].open;
        html+= bob.get_page(this.pages[n-1],this.pages[n],0);
      }
      html+= "\n   </div>";
    }
    else Ezer.error(this.id+".get_page: report nebyl připraven funkcí report_repage",0);
    return html;
  }
// ------------------------------------------------------------------------------------
// pomocná funkce převádějící zkontrolovaný report na text
  print_page () {
    var html= '';
    if ( !this.pages.length ) {
      // pokud nebyl dokument stránkován, bude vcelku
      html+= this.get_html(100);
    }
    else {
      // jinak bude vložen po stránkách
      var style= '';
      for (var n= 1; n<this.pages.length; n++) {
        html+= "\n  <div class='page'"+style+">&nbsp;";  // &nbsp; = aspoň jeden neabsolutní prvek
        html+= this.get_page(n);
        html+= "\n  </div>";
        style= " style='page-break-before:always;'";
      }
    }
    return html;
  }
// ------------------------------------------------------------------------------------
//fm: Report.print ([preview=0])
//      vrátí vnořené boxy jako HTML řetězec
  print (preview) {
    if ( this.status=='check' || this.status=='pages' || this.type=='batch' ) {
      preview= preview||0;
      var w= 840, h=500, html= '';
      var pw= window.open("", 'PreviewPage'
            , "width="+w+",height="+h+",menubar=1,toolbar=0,status=0,scrollbars=1,resizable=1");
      if ( !pw ) {
        alert("Nelze otevřít okno s náhledem tisku, nejsou zakázána 'vyskakovací' okna?");
      }
      else {
        pw.document.open();
        html+= "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
        html+= "<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>\n";
        html+= " <head><title>Náhled tisku</title>\n";
        html+= "  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />\n";
        html+= "  <link type='text/css' rel='stylesheet' href='ezer3.1/client/report.css.css' />\n";
        html+= "  <script>function keyPressHandler(e) {var kC= (window.event) ? event.keyCode : e.keyCode;var Esc= (window.event) ? 27 : e.DOM_VK_ESCAPE;if ( kC==Esc ) {window.close();}}\n";
        html+= " </script></head><body onkeypress='keyPressHandler(event)'>";
        html+= "\n";
        html+= this.type=='batch' ? this.batch : this.print_page();
        html+= "\n</body></html>";
        pw.focus();
        pw.document.write(html);
        pw.document.close();
        pw.focus();
        if ( !preview) pw.print();
      }
    }
    else Ezer.error(this.id+".print: report nebyl připraven funkcí report_check",0);
    return true;
  }
// ------------------------------------------------------------------------------------
//fm: Report.report_debug ()
//      zobrazí formulář v symbolickém tvaru
  report_debug (option) {
    var x= [], nx= 0, nest;
    if ( this.status!='start' ) {
      for (var ib in this.part) {
        nest= this.part[ib];
        switch ( nest.type ) {
        case 'box':
          nx++;
          x[nx]= nest.open.debug();
          break;
        case 'const':
          x[nest.id]= nest.value;
          break;
        }
      }
    }
    x.pages= this.pages;
    Ezer.debug(x,this.id+" - "+this.status);
    return true;
  }
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
function EZER_style_bob (l,t,w,h,zoom) {
  l= l*zoom/100; t= t*zoom/100; w= w*zoom/100; h= h*zoom/100;
  var s= 'left:'+l+'mm;top:'+t+'mm;width:'+w+'mm;height:'+h+'mm;';
  return s;
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
function EZER_copy_bob (bob,up,sb) {
  var copy= new Bob(bob.box,up,sb);
  copy.value= bob.value;
  copy.l= bob.l; copy.t= bob.t;
  copy.r= bob.r; copy.b= bob.b;
  copy.w= bob.h; copy.h= bob.h;
  copy.max.r= bob.max.r; copy.max.b= bob.max.b;
  sb= null;
  for (var ib= 0; ib<bob.tree.length; ib++) {
    sb= copy.tree[ib]= EZER_copy_bob(bob.tree[ib],copy,sb);
  }
  return copy;
}
// ============================================================================================> Bob
//   Bob (box,up,sb)
//      dynamický box-blok reportu - nemá přímý protějšek ve zdrojovém textu
class Bob {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  constructor (box,up,sb) {
    this.box= null;            // statický vzor boxu - QE_box
    this.up= null;             // otec
    this.sb= null;             // starší bratr (uzel vlevo||null)
    this.value= '';            // hodnota
    this.tree= [];             // strom podboxů vytvořených funkcí graft
    this.pagebreak= false;     // true vyvolá přechod na novou stránku pro tento bob
    // spočtené polohové atributy
    this.l= -1; this.t= -1; this.r= -1; this.b= -1; this.w= -1; this.h= -1;
    // spočtený pravý dolní roh obsahujícího potenciálního boxu
    this.max= {r:0,b:0};
    this.box= box;
    this.up= up;
    this.sb= sb;
  }
// ------------------------------------------------------------------------------------
// provede vymazání celého podstromu
  init () {
    for (var ib= 0; ib<this.tree.length; ib++) {
      this.tree[ib].init();
      delete this.tree[ib];
    }
    this.tree= [];
    this.max.r= this.max.b= 0;
    this.value= this.box.value;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// a = L|t|w|h ... pro l|t se velikost počítá relativně k nadřazenému bobu
  AE_check (bob,a,ctx,sum) {
    var relative= true, s= 0, changes= 0;
    scitani:
    for (var i= 0; i<sum.length; i++) {
      var x= sum[i][0], v= sum[i][1];
      switch ( x ) {
      case 'k':
      case 'n': s+= v;
                break;
      case 'l':
      case 't':
      case 'w':
      case 'h': if ( ctx[v][x]==-1 ) {
//   //                 back_refs++;
//                   trace(bob.box.id+'.AE_check: '+v+'.'+x+' is undefined');
                }
                s+= ctx[v][x] ? ctx[v][x] : 0;
                break;
      case 'r': s+= ctx[v].l+ctx[v].w;
                break;
      case 'b': s+= ctx[v].t+ctx[v].h;
                break;
      // ^ rozděluje výraz na poloviny
      //   1. sčítance před ^ se přidají jen pokud bob nemá staršího bratra
      //   2. sčítance po ^ se přidají jen pokud má bob staršího bratra
      case '^': if ( !bob.sb ) break scitani;
                var b= a=='l' ? 'w' : 'h';
                s= bob.sb[a] + bob.sb[b];
                relative= false;
                break;
      // stejná výška jako u předchozího
      case '~': s+= bob.sb ? bob.sb.t : 0;
                relative= false;
                break;
      // maximální šířka a výška
      case '!':                   // bude po výpočtu vnucena elementům uvnitř boxu
      case '$': if ( a=='w' ) s+= bob.max.r>bob.l ? bob.max.r-bob.l : 0;
                if ( a=='h' ) s+= bob.max.b>bob.t ? bob.max.b-bob.t : 0;
                break;
      // aktuální výška nebo šířka textu -- je třeba bob zobrazit, změřit, zrušit
      case '*': var trep= jQuery('#report'), wpx, hpx;
                var tbob= jQuery(`<div class="${bob.box.css}">${bob.value}</div>`).appendTo(trep);
                if ( a=='h' )
                  tbob.css('width',bob.w+'mm');
                else
                  tbob.css('height',bob.h+'mm');
                wpx= tbob.offsetWidth;
                hpx= tbob.offsetHeight;
                s= a=='h' ? (hpx+3)*bob.w/wpx : (wpx+3)*bob.h/hpx;
                relative= false;
                trep.removeChild(tbob);
                break;
      }
    }
    if ( relative && bob.up && (a=='l'||a=='t') )
      s+=  bob.up[a];
    // došlo ke změně
    if ( bob[a]!=s ) changes++;
    bob[a]= s;
    return changes;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// provede přepočet rozměrů podstromu
// ctx :: { box.id : {l:... t:...}, ...}
// ctx :: { box.id : bob, ...}
  check (ctx) {
    // vypočítej left, top
    var changes= 0;
    ctx[this.box.id]= this;
    changes+= this.AE_check(this,'l',ctx,this.box.options._l);
    changes+= this.AE_check(this,'t',ctx,this.box.options._t);
    // projdi podstrom
    for (var ib= 0; ib<this.tree.length; ib++) {
      var bob= this.tree[ib];
      changes+= bob.check(ctx);
      this.max.r= Math.max(this.max.r,bob.l+bob.w);
      this.max.b= Math.max(this.max.b,bob.t+bob.h);
    }
    // vypočítej width, height
    changes+= this.AE_check(this,'w',ctx,this.box.options._w);
    changes+= this.AE_check(this,'h',ctx,this.box.options._h);
    return changes;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// provede vložení přechodů na novou stránku a vrátí maximální výšku té staré
  check_page (tree,it,nb) {
    var end= 0;
    // vynechej vložené boby
    if ( !this.pagebreak ) {
      // spočítej spodní okraj
      var b= this.t+this.h, pb= this.box.options.pagebreak, id;
      // zkontroluj, zda stránka nepřetéká
      if ( (b > nb) && pb ) {
        // pokud ano ...
  //       trace('check_page: nb='+nb+' pro bob '+this.box.id+'/'+this.value+': b='+b+' pagebreak:'+pb);
        // diskuse formátu pagebreak: zatím jen 'copy:X' kde X je box staršího bratra nebo this
        var apb= pb.split(':'), bob= null;
        if ( tree && (id= apb[1]) ) {
          // hledej doleva shodu - může být už pro this.sb
          for (var sb= this.sb; sb; sb= sb.sb) {
            if ( id==sb.box.id ) {
              end= this.sb.t+this.sb.h;
              // udělej kopii a vlož mezi this.sb a this
              bob= EZER_copy_bob(sb,this.up,this.sb);
              this.sb= bob;
              bob.pagebreak= true;
              tree.splice(it,0,bob);
              break;
            }
          }
          if ( !bob ) Ezer.error("check_page: box '"+id+"' nebyl před zlomem stránky nalezen",0);
        }
        else Ezer.error("check_page: formát pagebreak:'"+pb+"' není implementován ",0);
      }
      else {
        // projdi podstrom
        for (var ib= 0; ib<this.tree.length; ib++) {
          end= this.tree[ib].check_page(this.tree,ib,nb);
          if ( end ) break;
        }
      }
    }
    return end;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//      vrátí HTML řetězec dynamického boxu, !height je vnucená výška vnořených elementů
  get_html (pretty,forced_h) {
    var zoom= this.box.report.zoom;
    var style= EZER_style_bob(this.l,this.t,this.w,forced_h?forced_h:this.h,zoom);
    var css= this.box.css;
    var title= '', outline;
    if ( (outline= this.box.report.outline) ) {
      if ( this.box.id.substr(0,1)=='$' ) {
        css+= outline==2 ? ' box_outline2' : '';
      }
      else {
        title+= " title='"+this.box.id+"'";
        css+= ' box_outline1';
      }
    }
    var html= pretty+"<div class='"+css+"' style='"+style+"'"+title+">"+this.value+"</div>";
    // projdi strom dynamických boxů
    var h= forced_h ? forced_h : (this.box.options._h=='!,' ? this.h : 0);
    for (var ib= 0; ib<this.tree.length; ib++) {
      var bob= this.tree[ib];
      html+= bob.get_html(pretty ? pretty+'&nbsp;' : pretty,h);
    }
    return html;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//      vrátí JSON řetězec dynamického boxu
  get_json () {
    var type= this.box.options.css||'text';
  //   var other= this.box.id.substr(0,1)=='$' ? "" : ",'id':'"+this.box.id+"'";
    var other= ",'id':'"+this.box.id+"'";
    other+= this.value ? ",'txt':'"+this.value+"'" : '';
    other+= this.box.options.css ? ",'css':'"+this.box.options.css+"'" : "";
    other+= this.box.options.style ? ",'style':'"+this.box.options.style+"'" : "";
    var json= "{'type':'"+type+"','left':"+this.l+",'top':"+this.t+",'width':"+this.w
      +",'height':"+this.h+other+"}";
    // projdi strom dynamických boxů
    for (var ib= 0; ib<this.tree.length; ib++) {
      var bob= this.tree[ib];
      json+= ','+bob.get_json();
    }
    return json;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//      vrátí HTML řetězec dynamického boxu pokud je na n-té straně
//      tzn. t1 <= bob.t && bob.b <= t2
//      !height je vnucená výška vnořených elementů
  get_page (t1,t2,forced_h) {
    var html= '', b= this.t+this.h;
    if ( t1 <= this.t && b <= t2 ) {
      var style= EZER_style_bob(this.l,this.t-t1,this.w,forced_h?forced_h:this.h,100);
      var css= this.box.css;
      var title= '';
      html= "\n    <div class='"+css+"' style='"+style+"'"+title+">"+this.value+"</div>";
    }
    // projdi strom dynamických boxů
    var h= forced_h ? forced_h : (this.box.options._h=='!,' ? this.h : 0);
    for (var ib= 0; ib<this.tree.length; ib++) {
      var bob= this.tree[ib];
      html+= bob.get_page(t1,t2,h);
    }
    return html;
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//      zobrazí box v symbolickém tvaru
  debug () {
    var x= [], y= {}, nx= 0, attr, l, t, w, h;
    l= this.l<0 ? '?' : this.l;
    t= this.t<0 ? '?' : this.t;
    w= this.w<0 ? '?' : this.w;
    h= this.h<0 ? '?' : this.h;
    attr= ' ['+l+','+t+','+w+','+h+']  -- ';
    attr+= this.value||'';
    attr+= ' / '+this.max.r+','+this.max.b;
    // projdi strom dynamických boxů
    for (var ib= 0; ib<this.tree.length; ib++) {
      var bob= this.tree[ib];
      x[++nx]= bob.debug();
    }
    if ( !nx )
      y= attr;
    else {
      if ( attr ) x[0]= attr;
      y[this.box.id]= x;
    }
    return y;
  }
}

// ============================================================================================> Box
//c: Box (typ,id,owner,root,symb)
//      přeložený box-blok reportu
//t: Block
//s: Block
class Box extends Block {
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  initialize () {
    super.initialize();
//   options: {},
    this.value= '';         // nastavuje se attr.title nebo let
    this.css= '';           // nastavuje se jako box_<attr.css|text>
    this.style= '';         // obsahuje coord jako html atribut
    this.open= null;        // otevřená větev pro roubování - QE_bob
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  initialize
  constructor (owner,desc,DOM,id,skill) {
    super(owner,desc,DOM,id,skill);
    // report boxu
    this.report= owner.type=='report' ? owner : owner.report;
    // coord= {left:function(){return val('n','10'),...;}, ...}
    // kde první parametr val je
    //   'n' -- druhý parametr je číslo
    //   'a' -- hvězdička
    //   'x' -- kde x:lrtbwh, druhý parametr je jméno položky reportu jejíž je x coord-atributem
    this.value= this.options.title||'';
    this.css= 'box box_'+(this.options.css||'text');
    this.subBlocks(desc,DOM);
  }
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// provede přidání jedné instance boxům vnořeným do boxu označeného jako xactive
  init (graft) {
    if ( graft )
      this.graft();
    if ( this.options.xactive ) {
      for (var ib in this.part) {
        this.part[ib].init(1);
      }
    }
  }
// ------------------------------------------------------------------------------------
//fm: Box.graft (title)
//      přidá (naroubuje) do vlastnického boxu nový výskyt tohoto boxu
//a: title - hodnota pro nový box
  graft (title) {
    var open, sb= null, len;
    if (( open= this.owner.open )) {    // open obsahuje QE_bob
      if ((len= open.tree.length )) {
        sb= open.tree[len-1];
      }
      var bob= new Bob(this,open,sb);
      open.tree.push(bob);              // přidej do otevřeného podstromu statického vlastníka
      this.open= bob;                   // stane se otevřeným pro statický vzor
      bob.value= title||this.options.title||'';
    }
    else Ezer.error("box.graft pro '"+this.id+"' nemá otevřenou větev v "+this.owner.id,0);
    return true;
  }
// ------------------------------------------------------------------------------------
//fm: Box.let (val)
//      nastaví value v box
  let (val) {
    this.value= val;
    return true;
  }
// ------------------------------------------------------------------------------------
//fm: Box.set (val)
//      nastaví value v bob
  set (val) {
    if ( this.open )
      this.open.value= val;
    else
      Ezer.error("box.set pro '"+this.id+"' nemá otevřenou větev v "+this.owner.id,0);
    return true;
  }
// ------------------------------------------------------------------------------------
//fm: Box.get ()
//      vrátí hodnotu value
  get () {
    return this.value;
  }
}
