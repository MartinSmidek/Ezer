/* global Ezer, dbg */

// ===========================================================================================> LIB3
"use strict";
// =================================================================================> DEBUGGER LOCAL
// funkce debuggeru - volané z aplikace
// --------------------------------
// --------------------------------------------- isElementInViewport
function isElementInViewport(el) {
  var rect= el.getBoundingClientRect();
  return rect.bottom > 0 &&
    rect.right > 0 &&
    rect.left < (window.innerWidth || document. documentElement.clientWidth) /*or $(window).width() */ &&
    rect.top < (window.innerHeight || document. documentElement.clientHeight) /*or $(window).height() */;
}
// -------------------------------------------------------------------------------- dbg_onshiftclick
function dbg_onshiftclick(block) {
  if ( !Ezer.options.dbg ) return false;
  if ( !Ezer.sys.dbg )
    Ezer.sys.dbg= {
      win_ezer:null,    // podřízené okno s debugrem
      file:'',          // aktuálně zobrazený soubor
      files:{},         // všechny soubory se stavem
      win_php:null};
  var pos= block.app_file(),
      state0= {stops:[],stop:0,traces:[],pick:0}; // stopadresy - stop, trasování, aktuální ř.
  if ( pos.file ) {
    var lc= block.desc._lc;
    lc= lc.split(',');
    let ln= lc[0];
    var show= function() {
      var lc= block.desc._lc.split(',');
      dbg.focus();
      dbg.dbg_show_line(lc[0],'pick');
    };
    // zobrazení
    Ezer.sys.dbg.start= block.self();
    if ( pos.file==Ezer.sys.dbg.file ) {
      show();
    }
    else if ( Ezer.sys.dbg.files[pos.file] ) {
      dbg.dbg_reload(pos.file,ln);
//      show();
    }
    else if ( Ezer.sys.dbg.win_ezer ) {
      Ezer.sys.dbg.files[pos.file]= state0;
      dbg.dbg_reload(pos.file,ln);
//      show();
    }
    else {
//      if ( Ezer.sys.dbg.win_ezer ) {
//        // zavření zobrazeného
//        Ezer.sys.dbg.win_ezer.noevent= true;
//        Ezer.sys.dbg.win_ezer.close();
//      }
//      if ( !Ezer.sys.dbg.files[pos.file] ) {
      Ezer.sys.dbg.files[pos.file]= state0;
//      }
      var line= block.desc._lc.split(',')[0];
      var fname= pos.app+'/'+pos.file+'.ezer';
      //fname= pos.app+'/tut.the.php';  -- test otevření PHP
      //fname= pos.app+'/i_fce.js';     -- test otevření JS
      // pokud je poloha a rozměr v cookies ezer_dbg_win=l,t,w,h ==> . dbg open
      var ltwh= Ezer.fce.get_cookie('ezer_dbg_win','1*1*770*500');
      var x= ltwh.split('*'), 
          l= x[0], t= x[1], w= x[2]-16, h= x[3]-67;
      var position= `left=${l},top=${t},width=${w},height=${h}`;
      Ezer.sys.dbg.win_ezer= window.open(
        `./ezer3.1/dbg3.php?err=1&app=${Ezer.root}&src=${fname}&file=${pos.file}&pick=${line}`,'dbg',
        position+',resizable=1,titlebar=0,menubar=0');
      if ( Ezer.sys.dbg.win_ezer ) {
//        dbg_reload(pos.file);
        Ezer.sys.dbg.file= pos.file;
        Ezer.sys.dbg.typ= 'ezer';
        Ezer.sys.dbg.noevent= false;
      }
    }
  }
  return false;
}
//// -------------------------------------------------------------------------------- dbg_onclick_text
//function dbg_onclick_text(el) {
//  return 1;
//}
// ----------------------------------------------------------------------------------- dbg proc_stop
// DBG - voláno z intepreta po vytvoření aktivačního záznamu v zásobníku
// Ezer.continuation obsahuje aktuální stav interpreta
function dbg_proc_stop(on_off) {
    if ( Ezer.sys.dbg.win_ezer ) {
      Ezer.sys.dbg.win_ezer.dbg_show_proc(Ezer.continuation,on_off);
    }
}
// ------------------------------------------------------------------------------- dbg_onclick_start
//function dbg_onclick_start(win) {
//  win= win ? win : window;
//  var dbg_src= win.document.getElementById('dbg_src');
//  if ( dbg_src ) {
//    dbg_src.addEvents({
//      click: function(el) {
//        var chs= el.target.getParent().getChildren(), x= 0;
//        for (var i=0; i<chs.length; i++) {
//          if ( chs[i]==el.target ) {
//            x= i+1;
//            break;
//          }
//        }
//        Ezer.fce.echo("line=",x);
//        return x;
//      }
//    });
//  }
//}
// ===================================================================================> Užitečné fce
// heap s oddělovači sep obsahuje string
function contains(heap, string, sep){
  return (sep) ? (sep + heap + sep).indexOf(sep + string + sep) > -1
    : heap.indexOf(string) > -1;
}
// ----------------------------------------------------------------------------------------- waiting
// pokud je použito 'wait_mask' tak ji zobraz/skryj podle parametru
function waiting(on) {
  jQuery('#wait_mask')
    .css('display',on?'block':'none');
}
// ----------------------------------------------------------------------------------- make_url_menu
// sestavení url aplikace se změněným odkazem na menu
// menu = pole pro parametr menu
function make_url_menu(menu) {
  var url= location.protocol+'//'+location.hostname+(location.port?':'+location.port:'');
  url+= location.pathname;
  // přidání menu
  var del= '';
  url+= '?menu=';
  for (let id of menu) {
    url+= del+id;
    del= '.';
  }
  // přidání původních $_GET parametrů mimo trace+theight, které je doplněno v pushState
  for (var tag in Ezer.get ) {
    if ( tag!='trace' && tag!='theight' && tag!='app' ) {
      var val= Ezer.get[tag];
      url+= '&'+tag+'='+val;
    }
  }
//                                                 Ezer.trace('*',url);
  return url;
}

// ----------------------------------------------------------------------------------- get_url_param
// zjištění hodnoty parametru v url
// see http://www.netlobo.com/url_query_string_javascript.html
function get_url_param(name) {
  name= name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regexS= "[\\?&]"+name+"=([^&#]*)";
  var regex= new RegExp( regexS );
  var results= regex.exec( window.location.href );
  if( results == null )
    return "";
  else
    return results[1];
}

// ------------------------------------------------------------------------------------------ padNum
// dorovnání čísla nulami do dané délky
function padNum(number, numDigits) {
  var str= number ? number.toString() : '0';
  while (str.length < numDigits) str= '0' + str;
  return str;
}
// ------------------------------------------------------------------------------------------ padStr
// dorovnání stringu mezerami resp. omezení do dané délky
function padStr(str, len) {
  str= htmlentities(str);
  if ( str.length>len ) {
    str= str.substr(0,len-1)+'…'; //'&hellip;';
  }
  else {
    while (str.length < len) str+= ' ';
  }
  return str;
}
// ------------------------------------------------------------------------------------------- datum
// vrátí dnešní datum a pokud je time=1 i čas ve tvaru používaném v položkách date
// pokud je time==2 zobrazují se i sekundy
// pokud je sql==1 ve formátu pro SQL
function ae_datum(time,sql) {
  var t= new Date(), td= t.getDate(), tm= 1+t.getMonth(), ty= t.getFullYear(), th, tn, ts, dat;
  if ( time ) { th= t.getHours(); tn= t.getMinutes(); ts= t.getSeconds(); }
  if ( sql ) {
    dat= ty+'-'+padNum(tm,2)+'-'+padNum(td,2);
    if ( time ) {
      dat+= ' '+padNum(th,2)+':'+padNum(tn,2)+':'+padNum(ts,2);
    }
  }
  else {
    dat= td+'.'+tm+'.'+ty;
    if ( time ) {
      dat+= ' '+th+':'+padNum(tn,2)+(time==2 ? ':'+padNum(ts,2) : '');
    }
  }
  return dat;
}
// ---------------------------------------------------------------------------------------- time2ymd
// převede datum typu "d.m.y h:m:s" na pole [y,m,d,h,m,s]
function ae_time2ymd (dmy) {
  var y, yh, m, d, s= [];
  if ( dmy.length > 0 ) {
    dmy= dmy.split('.');
    if ( dmy.length==3 ) {
      // den může být předeslán jménem dne v týdnu
      d= dmy[0].split(' ');
      d= parseInt(d[d.length-1],10);
      m= parseInt(dmy[1],10);
      // rok může být následován časem
      yh= dmy[2].split(' ');
      y= parseInt(yh[0]);
      if (yh[1]) {
        var hms= yh[1].split(':');
        if ( hms.length==3 )
          s= [y,m,d,parseInt(hms[0]),parseInt(hms[1]),parseInt(hms[2])];
        else
          s= [y,m,d,parseInt(hms[0]),parseInt(hms[1])];
      }
      else
        s= [y,m,d];
    }
  }
  return s;
}
// -------------------------------------------------------------------------------------------- time
// vrátí čas,pokud je time==2 zobrazují se i sekundy
function ae_time(time) {
  var t= new Date();
  var th= t.getHours(), tn= t.getMinutes(), ts= t.getSeconds();
  var tim= ' '+th+':'+padNum(tn,2)+(time==2 ? ':'+padNum(ts,2) : '');
  return tim;
}
// ------------------------------------------------------------------------------------ htmlentities
// jednoduchá varianta php funkce
function htmlentities(h,breaks) {
  var t= typeof(h)=='string' ? h.replace(/[<]/g,'&lt;').replace(/[>]/g,'&gt;') : h.toString();
  // pokus o pretty printing pro breaks==2:
  if ( breaks==2 ) t= t.replace(/&lt;([^/])/g,"<br/>&nbsp;&lt;$1");
  t= breaks ? t.replace(/[\n]/g,'<br/>') : t.replace(/[\n]/g,'\\n');
  return t;
}
// --------------------------------------------------------------------------------- firstPropertyId
// vrátí klíč první vlatnosti objektu (podle for...in)
function firstPropertyId(o) {
  var i= null;
  if ( o )
    for (i in o)
      break;
  return i;
}
// ------------------------------------------------------------------------------------------- debug
// zobrazení struktury objektu nebo pole
function debug (gt,label,depth) {
  var x= gt;
  label= label||'';
  depth= depth||5;
  if ( Array.isArray(gt) || typeof(gt)==='object' ) {
    x= debugx(gt,label,depth);
  }
  else {
    x= "<table class='dbg' style='background-color:#ddeeff'><tr><td valign='top' class='title'>"+
      label+"</td></tr><tr><td>"+x+"</td></tr></table>";
  }
  return x;
}
function debugx (gt,label,depth) {
  var x= gt, c;
  if ( depth < 0 ) return "<table class='dbg_over'><tr><td>...</td></tr></table>";
  if ( Array.isArray(gt) || typeof(gt)==='object' ) {
    c= Array.isArray(gt) ? '#ddeeff' : '#eeeeaa';
    x= "<table class='dbg' style='background-color:"+c+"'>";
    x+= label!==undefined ? "<tr><td valign='top' colspan='2' class='title'>"+label+"</td></tr>" : '';
//     Object.each(gt,function(t,g){
    for (let g in gt) { let t= gt[g];
      x+= "<tr><td valign='top' color='label'>"+g+"</td><td>"+debugx(t,undefined,depth-1)+"</td></tr>";
    }
    x+= "</table>";
  }
  else if ( typeof(gt)==='string' ) {
    x= "'"+x+"'";
  }
  return x;
}
// --------------------------------------------------------------------------------- remap_fields_db
function remap_fields_db (block,new_db) {
  for (var ic in block.part) {
    let field= block.part[ic];
    if ( field.table ) {
      field.table.options.db= new_db;
    }
  }
  return 1;
}
// ====================================================================================> Ezer Slider
// DOM before:       <div id="x">
// initialization:   var slider= $("#x").slider({ezer_stop:info}).slider('init',h).slider('instance')
//                   kde h>0 změní #x.height
// reseting view:    slider.reset(all,visibled,position=1)
// setting position: slider.setPosition(position)
// getting position: slider.getPosition()
// DOM after:        <div id="x" class='ezer-slider'><span class='ezer-handle'></span></div>
jQuery.widget('ezer.slider',jQuery.ui.slider,{
  options:{
    min_handle: 30,                             // minimal handle height in px
    ezer_stop: function(rec) {},                // callback when new position is set
    classes: {
      "ui-slider": "ezer-slider",
      "ui-slider-handle": "ezer-handle"
    },
    // private options
    orientation:"vertical",min:1,step:1,
    stop:function (ev,ui) {
      jQuery(ev.target).trigger('slidestop');
    }
  },
  getPosition() { return this._position; },      // get position
  setPosition(curr) {                // set position
    this._position= curr;
    this.value(1+this._slen-curr);
  },
  init(height=0) { // musí být zavolané hned po vytvořeni
    if ( height ) {
      this.element.css({height:height,borderTopWidth:0});
    }
    this._height= this.element.outerHeight();
    this.reset(0,0);
    // kolečko myši
    this.element
      .on('slidestop',function(ev) {
        this._position= 1 + this._slen - this.value();
        this.options.ezer_stop(this._position);
      }.bind(this))
      .on('mousewheel DOMMouseScroll', function(e) {
        var o = e.originalEvent;
        var delta = o && (o.wheelDelta || (o.detail && -o.detail));
        if ( delta ) {
          e.preventDefault();
          let step= this.options.step;
          step*= delta < 0 ? -1 : +1;
          this.value(this.value()+step);
          this.element.trigger('slidestop');
        }
      }.bind(this));
  },
  reset(slen,tlen,curr=1) {
    this._slen= slen;
    this._tlen= tlen;
    this.option('min',1);
    this.option('max',slen);
    // úprava css
    this._handle_height= tlen==slen ? 0 : this._height*tlen/slen;
    if ( tlen>2 ) {
      if ( this._handle_height && this._handle_height<this.options.min_handle )
        this._handle_height= this.options.min_handle;
      this.handle.height(this._handle_height);
      this.element.css({height:this._height-this._handle_height,borderTopWidth:this._handle_height});
      this.handle.show();
    }
    else {
      this.handle.hide();
    }
    this.setPosition(curr);
  },
  // private
  _height:0,
  _handle_height:0,
  _slen:0, _tlen:0,
  _position:0
});

// =========================================================================> scrollIntoViewIfNeeded
jQuery.fn.extend({
  // ------------------------------------------------- + scrollIntoViewIfNeeded
  Ezer_scrollIntoView: function() {
    var target= this[0];
    let rect = target.getBoundingClientRect(),
        bound= this.parent()[0].getBoundingClientRect();
    if (rect.bottom > bound.bottom) {
        target.scrollIntoView(false);
    }
    else if (rect.top < bound.top) {
        target.scrollIntoView(true);
    }
  },
  // ------------------------------------------------- + measure
  measure: function(fn) {
    var clone= jQuery(this).clone(), result;
    clone.css({
      visibility: 'hidden',
      position: 'absolute'
    });
//    clone.appendTo(document.body); -- zbytečné, navíc rušilo hodnotu radio
    result= fn.apply(clone);
    clone.remove();
    return result;
  },
  // ------------------------------------------------- + actual
  // Copyright 2012, Ben Lin (http://dreamerslab.com/), MIT License
  actual: function ( method, options ){
    var defaults = {
      absolute      : false,
      clone         : false,
      includeMargin : false,
      display       : 'block'
    };
    var configs= jQuery.extend( defaults, options );
    var $target= this.eq( 0 );
    var fix, restore;
    if ( configs.clone === true ) {
      fix= function (){
        var style = 'position: absolute !important; top: -1000 !important; ';
        // this is useful with css3pie
        $target= $target
          .clone()
          .attr('style', style)
          .appendTo('body');
      };
      restore = function (){
        // remove DOM element after getting the width
        $target.remove();
      };
    }
    else {
      var tmp= [];
      var style= '';
      var $hidden;
      fix= function (){
        // get all hidden parents
        $hidden= $target.parents().addBack().filter(':hidden');
        style+= 'visibility: hidden !important; display: ' + configs.display + ' !important; ';
        if ( configs.absolute === true ) style += 'position: absolute !important; ';
        // save the origin style props
        // set the hidden el css to be got the actual value later
        $hidden.each( function () {
          // Save original style. If no style was set, attr() returns undefined
          var $this= jQuery( this );
          var thisStyle= $this.attr('style');
          tmp.push(thisStyle);
          // Retain as much of the original style as possible, if there is one
          $this.attr('style', thisStyle ? thisStyle + ';' + style : style);
        });
      };
      restore= function (){
        // restore origin style values
        $hidden.each( function ( i ) {
          var $this= jQuery( this );
          var _tmp= tmp[ i ];
          if ( _tmp === undefined ) {
            $this.removeAttr('style');
          }
          else {
            $this.attr('style', _tmp);
          }
        });
      };
    }
    fix();
    // get the actual value with user specific methed
    // it can be 'width', 'height', 'outerWidth', 'innerWidth'... etc
    // configs.includeMargin only works for 'outerWidth' and 'outerHeight'
    var actual= /(outer)/.test(method) ?
      $target[method](configs.includeMargin) :
      $target[method]();
    restore();
    // IMPORTANT, this plugin only return the value of the first element
    return actual;
  }

});
// ===================================================================================> ContextPopup
/**
 * jQuery plugin for Ezer3  context menu
 *
 * Usage:
 *
 *   jQuery(...).contextPopup({
 *     items: [
 *       ['title',function() { ... } ],
 *       ...
 *     ]
 *   });
 *
 * (c) Martin Šmídek, 2017  based on https://github.com/joewalnes/jquery-simple-context-menu
 *
 */
jQuery.fn.contextPopup = function(menuData,e) {
  // Define default options
  var options = {
    menuClass: 'ContextMenu3',
    focusClass: 'ContextFocus3',
    focus: null,                                // element receiving focusClass
    up: false,
    persistent: false,                          // persistent are menu menu called from ezerscript
    items: []
  };
  // for Ezer element merge options from options otherwise from menuData
  jQuery.extend(options, menuData.data ? menuData.data().ezer.options : menuData);
  // Build popup menu HTML
  function createMenu(e) {
    var menu = jQuery('<ul class="' + options.menuClass + '"></ul>')
      .appendTo(document.body);
    options.focus= options.focus || e.target;
    options.items.forEach( item => {
      if ( !item ) return;
      var del= item[0].match(/^[-=]/) ? item[0][0] : '',
          label= del ? item[0].substr(1) : item[0];
      jQuery('<li>'+label+'</li>')
        .appendTo(menu)
        .css({borderTop: del ? (del=='-' ? "1px solid #AAAAAA" : "3px double #AAAAAA") : ''})
        .click( () => {
          item[1](options.focus); return false;
        })
    })
    return menu;
  }
  // On contextmenu event (right click)
  function showMenu(e) {
    e.preventDefault();
    var menu = menuData.data ? menuData.show() : createMenu(e).show();
    menu.hideMenu= function() {
      if ( options.focusClass ) jQuery(options.focus).removeClass(options.focusClass);
      if ( options.persistent ) this.hide(); else this.remove();
    };
    // place menu
    var height= menu.height(),
        width= menu.width(),
        left = (e.pageX||e.page.x),
        top = e.pageY||e.page.y;
    if ( options.up ) { top-= height + 16; }
    if (top + height >= jQuery(window).height()) { top -= height; }
    if (left + width >= jQuery(window).width()) { left -= width; }
    menu.css({zIndex:1000001, left:left, top:top});
    // highlight context area
    if ( options.focusClass ) jQuery(options.focus).addClass(options.focusClass);
    // Cover rest of page with invisible div that when clicked will cancel the popup.
    var bg = jQuery('<div></div>')
      .css({left:0, top:0, width:'100%', height:'100%', position:'absolute', zIndex:1000000})
      .appendTo(document.body)
      .on('contextmenu click', e => {
        // If click or right click anywhere else on page: remove clean up.
        bg.remove();
        menu.hideMenu();
        return false;
      });
    // When clicking on a link in menu: hide ezerscript menu or clean up (in addition to handlers on link already)
    menu.find('li').click(e => {
      bg.remove();
      menu.hideMenu();
      return false;
    });
    // Cancel event, so real browser popup doesn't appear.
    return false;
  }
  if ( options.persistent ) {
    // On contextmenu event (right click)
    this.on('contextmenu', e => {
      e.stopPropagation(); showMenu(e);
    });
  }
  else {
    showMenu(e);
//     this.off('contextmenu');
  }
  return this;
};

// ======================================================================================> resizable
/*
jquery-resizable
Version 0.20 - 3/10/2017
© 2015-2017 Rick Strahl, West Wind Technologies
www.west-wind.com
Licensed under MIT License
https://github.com/RickStrahl/jquery-resizable
*/
(function($, undefined) {
    function getHandle(selector, el) {
        if (selector && selector.trim()[0] === ">") {
            selector = selector.trim().replace(/^>\s*/, "");
            return el.find(selector);
        }
        return selector ? $(selector) : el;
    }
    if ($.fn.resizable)
        return;
    $.fn.resizable = function fnResizable(options) {
        var opt = {
            handle: null,                      // handle that starts dragging
            handleSelector: null,              // selector for handle that starts dragging
            resizeWidth: true,                 // resize the width
            resizeHeight: true,                // resize the height
            resizeWidthFrom: 'right',          // the side that the width resizing is relative to
            resizeHeightFrom: 'bottom',        // the side that the height resizing is relative to
            onDragStart: null,                 // hook into start drag operation (event passed)
            onDragEnd: null,                   // hook into stop drag operation (event passed)
            onDrag: null,                      // hook into each drag operation (event passed)
            touchActionNone: true              // disable touch-action on $handle
                                               // prevents browser level actions like forward back gestures
        };
        if (typeof options == "object") opt = $.extend(opt, options);
        return this.each(function () {
            var startPos, startTransition;
            var $el = $(this);
            var $handle = opt.handle ? opt.handle : getHandle(opt.handleSelector, $el);
            if (opt.touchActionNone)
                $handle.css("touch-action", "none");
            $el.addClass("resizable");
            $handle.bind('mousedown.rsz touchstart.rsz', startDragging);
            function noop(e) {
                e.stopPropagation();
                e.preventDefault();
            }
            function startDragging(e) {
                // Prevent dragging a ghost image in HTML5 / Firefox and maybe others
                if ( e.preventDefault ) {
                  e.preventDefault();
                }
                startPos = getMousePos(e);
                startPos.width = parseInt($el.width(), 10);
                startPos.height = parseInt($el.height(), 10);
                startTransition = $el.css("transition");
                $el.css("transition", "none");
                if (opt.onDragStart) {
                    if (opt.onDragStart(e, $el, opt) === false)
                        return;
                }
                opt.dragFunc = doDrag;
                $(document).bind('mousemove.rsz', opt.dragFunc);
                $(document).bind('mouseup.rsz', stopDragging);
                if (window.Touch || navigator.maxTouchPoints) {
                    $(document).bind('touchmove.rsz', opt.dragFunc);
                    $(document).bind('touchend.rsz', stopDragging);
                }
                $(document).bind('selectstart.rsz', noop); // disable selection
            }
            function doDrag(e) {
                var pos = getMousePos(e), newWidth, newHeight;
                if (opt.resizeWidthFrom === 'left')
                    newWidth = startPos.width - pos.x + startPos.x;
                else
                    newWidth = startPos.width + pos.x - startPos.x;
                if (opt.resizeHeightFrom === 'top')
                    newHeight = startPos.height - pos.y + startPos.y;
                else
                    newHeight = startPos.height + pos.y - startPos.y;
                if (!opt.onDrag || opt.onDrag(e, $el, newWidth, newHeight, opt) !== false) {
                    if (opt.resizeHeight)
                        $el.height(newHeight);
                    if (opt.resizeWidth)
                        $el.width(newWidth);
                }
            }
            function stopDragging(e) {
                e.stopPropagation();
                e.preventDefault();
                $(document).unbind('mousemove.rsz', opt.dragFunc);
                $(document).unbind('mouseup.rsz', stopDragging);
                if (window.Touch || navigator.maxTouchPoints) {
                    $(document).unbind('touchmove.rsz', opt.dragFunc);
                    $(document).unbind('touchend.rsz', stopDragging);
                }
                $(document).unbind('selectstart.rsz', noop);
                // reset changed values
                $el.css("transition", startTransition);
                if (opt.onDragEnd)
                    opt.onDragEnd(e, $el, opt);
                return false;
            }
            function getMousePos(e) {
                var pos = { x: 0, y: 0, width: 0, height: 0 };
                if (typeof e.clientX === "number") {
                    pos.x = e.clientX;
                    pos.y = e.clientY;
                } else if (e.originalEvent.touches) {
                    pos.x = e.originalEvent.touches[0].clientX;
                    pos.y = e.originalEvent.touches[0].clientY;
                } else
                    return null;
                return pos;
            }
        });
    };
})(jQuery,undefined);
// =================================================================> Simple jQuery Draggable Plugin
// http://tovic.github.io/dte-project/jquery-draggable/index.html
// Usage: $(selector).drags();
// Options:
// handle            => your dragging handle.
//                      If not defined, then the whole body of the
//                      selected element will be draggable
// cursor            => define your draggable element cursor type
// draggableClass    => define the draggable class
// activeHandleClass => define the active handle class
// top               => minimal distance from top 
//
// Update: 26 February 2013
// 1. Move the `z-index` manipulation from the plugin to CSS declaration
// 2. Fix the laggy effect, because at the first time I made this plugin,
//    I just use the `draggable` class that's added to the element
//    when the element is clicked to select the current draggable element. (Sorry about my bad English!)
// 3. Move the `draggable` and `active-handle` class as a part of the plugin option
// Next update?? NEVER!!! Should create a similar plugin that is not called `simple`!

(function($) {
  $.fn.drags = function(opt) {
    opt= $.extend({
      handle: "",
      cursor: "move",
      draggableClass: "draggable",
      activeHandleClass: "active-handle",
      top: 0              
    }, opt);
    var $selected= null;
    var $elements= (opt.handle === "") ? this : this.find(opt.handle);
    $elements
      .css('cursor', opt.cursor)
      .on("mousedown", function(e) {
        if (opt.handle === "") {
          $selected= $(this);
          $selected.addClass(opt.draggableClass);
        }
        else {
          $selected= $(this).parent();
          $selected
            .addClass(opt.draggableClass)
            .find(opt.handle)
            .addClass(opt.activeHandleClass);
        }
        var drg_h= $selected.outerHeight(),
            drg_w= $selected.outerWidth(),
            max_x= $('body').width() - drg_w,
            max_y= $('body').height() - drg_h,
            pos_y= $selected.offset().top + drg_h - e.pageY,
            pos_x= $selected.offset().left + drg_w - e.pageX;
        $(document)
          .on("mousemove", function(e) {
            let x= e.pageX + pos_x - drg_w,
                y= e.pageY + pos_y - drg_h;
            $selected.offset({
              left: Math.max(0,Math.min(x,max_x)),
              top:  Math.max(opt.top,Math.min(y,max_y))
            });
          })
          .on("mouseup", function() {
            $(this).off("mousemove"); // Unbind events from document
            if ($selected !== null) {
              $selected.removeClass(opt.draggableClass);
              $selected= null;
            }
          });
        e.preventDefault(); // disable selection
      })
      .on("mouseup", function() {
        if (opt.handle === "") {
          $selected.removeClass(opt.draggableClass);
        }
        else {
          $selected
            .removeClass(opt.draggableClass)
            .find(opt.handle)
            .removeClass(opt.activeHandleClass);
        }
        $selected= null;
      });
    return this;
  };
})(jQuery);

// =========================================================================================> Base64
// ----------------------------------------------------------------------------------- base64_decode
function base64_decode (data) {
    // http://kevin.vanzonneveld.net
    // +   original by: Tyler Akins (http://rumkin.com)
    // +   improved by: Thunder.m
    // +      input by: Aman Gupta
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   bugfixed by: Pellentesque Malesuada
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
    // *     returns 1: 'Kevin van Zonneveld'
    // mozilla has this native
    // - but breaks in 2.0.0.12!
    //if (typeof this.window['atob'] == 'function') {
    //    return atob(data);
    //}
    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
        ac = 0,
        dec = "",
        tmp_arr = [];

    if (!data) {
        return data;
    }

    data += '';

    do { // unpack four hexets into three octets using index points in b64
        h1 = b64.indexOf(data.charAt(i++));
        h2 = b64.indexOf(data.charAt(i++));
        h3 = b64.indexOf(data.charAt(i++));
        h4 = b64.indexOf(data.charAt(i++));

        bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

        o1 = bits >> 16 & 0xff;
        o2 = bits >> 8 & 0xff;
        o3 = bits & 0xff;

        if (h3 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1);
        } else if (h4 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1, o2);
        } else {
            tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
        }
    } while (i < data.length);

    dec = tmp_arr.join('');

    return dec;
}
// ----------------------------------------------------------------------------------- base64_encode
function base64_encode (data) {
    // Encodes string using MIME base64 algorithm
    // discuss at: http://phpjs.org/functions/base64_encode
    // +   original by: Tyler Akins (http://rumkin.com)
    // -   binary input: Martin Šmídek
    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
        ac = 0,
        enc = "",
        tmp_arr = [];
    if (!data) {
        return data;
    }
//     data = this.utf8_encode(data + '');      //-MŠ
    do { // pack three octets into four hexets
        o1 = data.charCodeAt(i++);
        o2 = data.charCodeAt(i++);
        o3 = data.charCodeAt(i++);
        bits = o1 << 16 | o2 << 8 | o3;
        h1 = bits >> 18 & 0x3f;
        h2 = bits >> 12 & 0x3f;
        h3 = bits >> 6 & 0x3f;
        h4 = bits & 0x3f;
        // use hexets to index into b64, and append result to encoded string
        tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
    } while (i < data.length);
    enc = tmp_arr.join('');
    var r = data.length % 3;
    return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
}
// =======================================================================================> Resample
// convert a dataURI to a Blob
//   http://stackoverflow.com/questions/4998908/convert-data-uri-to-file-then-append-to-formdata
function dataURItoBlob(dataURI) {
  // convert base64/URLEncoded data component to raw binary data held in a string
  var byteString;
  if (dataURI.split(',')[0].indexOf('base64') >= 0)
    byteString = atob(dataURI.split(',')[1]);
  else
    byteString = unescape(dataURI.split(',')[1])
  ;
  // separate out the mime component
  var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
  // write the bytes of the string to a typed array
  var ia = new Uint8Array(byteString.length);
  for (var i = 0; i < byteString.length; i++) {
    ia[i] = byteString.charCodeAt(i);
  }
  return new Blob([ia], {type:mimeString});
}
var Resample =
Ezer.browser=='IE' ? null :
(function (canvas) {
 // (C) WebReflection Mit Style License
 // Resample function, accepts an image as url, base64 string, or Image/HTMLImgElement
 // optional width or height, and a callback to invoke on operation complete
  var $Resample= function (img, type, width, height, onresample) {
  var
   // check the image type
   load = typeof img == "string",
   // Image pointer
   i = load || img
  ;
  // if string, a new Image is needed
  if (load) {
   i = new Image();
   // with propers callbacks
   i.onload = onload;
   i.onerror = xonerror;
  }
  // easy/cheap way to store info
  i._onresample = onresample;
  i._width= width;
  i._height= height;
  i._mime= type;
  // if string, we trust the onload event otherwise we call onload directly
  // with the image as callback context
//  load ? ((i.src = img)) : onload.call(img);
  if ( load ) {
    i.src= img;
  }
  else {
    onload.call(img);
  }
 }
 // just in case something goes wrong
 function xonerror() {
  throw ("not found: " + this.src);
 }
 // called when the Image is ready

//   if ( !$maxWidth ) $maxWidth= $origWidth;
//     if ( !$maxHeight ) $maxHeight= $origHeight;
//     // nyni vypocitam pomer změny
//     $pw= $maxWidth / $origWidth;
//     $ph= $maxHeight / $origHeight;
//     $p= min($pw, $ph);
//     // vypocitame vysku a sirku změněného obrazku - vrátíme ji do výstupních parametrů
//     $newWidth = (int)($origWidth * $p);
//     $newHeight = (int)($origHeight * $p);
//     $width= $newWidth;
//     $height= $newHeight;


 function onload() {
  var img= this,                               // minifier friendly
    max_width= img._width || img.width,        // maximální povolená šířka
    max_height= img._height || img.height,     // maximální povolená výška
    onresample= img._onresample,               // the callback
    pw= max_width / img.width,
    ph= max_height / img.height,
    mime= img._mime,
    p= Math.min(pw,ph);                         // poměr změny
  // vypocitame vysku a sirku změněného obrazku - vrátíme ji do výstupních parametrů
  let width= p>=1 ? img.width : round(img.width * p);
  let height= p>=1 ? img.height : round(img.height * p);
  // remove (hopefully) stored info
  delete img._onresample;
  delete img._width;
  delete img._height;
  // when we reassign a canvas size this clears automatically the size should be exactly the same
  // of the final image so that toDataURL ctx method will return the whole canvas as png
  // without empty spaces or lines
  canvas.width= width;
  canvas.height= height;
  // drawImage has different overloads in this case we need the following one ...
  context.drawImage(
   img,         // original image
   0,           // starting x point
   0,           // starting y point
   img.width,   // image width
   img.height,  // image height
   0,           // destination x point
   0,           // destination y point
   width,       // destination width
   height       // destination height
  );
  // retrieve the canvas content as base4 encoded PNG image and pass the result to the callback
  onresample(canvas.toDataURL(mime));
 }
 var context = canvas.getContext("2d"), // point one, use every time ...
  round = Math.round                    // local scope shortcut
 ;
 return $Resample;
}(
 // lucky us we don't even need to append and render anything on the screen
 // let's keep this DOM node in RAM for all resizes we want
 this.document.createElement("canvas"))
);
// ===========================================================================================> AJAX
// ------------------------------------------------------------------------------------------- ask 3
// ask3(x,then,context): after running x on server call then
function ask3(x,then) {
  x.root= Ezer.root;                  // název/složka aplikace
  x.app_root= Ezer.app_root;          // {root].inc.php je ve složce aplikace
  jQuery.ajax({
    url: 'ezer3.1/server/ezer3.php',
    method: 'POST',
    data: x
  })
    .done(y => {
        if ( typeof(y)==='string' )
          Ezer.error(`SERVER3: error for cmd='${x.cmd}':${y}`,'C');
        else if ( y.error )
          Ezer.error(y.error,'C');
        else {
          if ( y.trace ) Ezer.trace('u',y.trace);
          if ( then )
            then(y);
        }
    })
    .fail( (xhr) => {
       Ezer.error('SERVER3 failure (1)'+(xhr.responseText||''),'C');
    });
}
