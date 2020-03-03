// Tento modul obsahuje podpůrné funkce pro CMS
/* global Ezer */
// ---------------------------------------------------------------------------------------- cms form
// zobrazí online přihlášku na akci s daným id
/**
 * Realizace operací nad přihláškou viz její [HTML skeleton](procesy-prihlasky.html#form-html)
 * 
 * @param cmd akce nad přihláškou - viz dokumentace uvnitř funkce
 * @param par parametry 
 */
function cms_form(cmd,par) {
  function TEXT(i) {
    desc= Ezer.cms.form[par.form];
    return Ezer.cms.test
        ? `TEXT '${i}'=>${desc.TEXT[i]}`
        : desc.TEXT[i] ? desc.TEXT[i] : `TEXT '${i}'=>undefined`;
  }
  var form= jQuery("div.cms_form"), 
      mail= form.find("[name=mail] input"),
      pin= form.find("[name=pin] input"),
      info= form.find(">div>div:first-child"),
      data= form.find(">div>div:nth-child(3)"),
      conf= form.find("[name=confirm] input"),
      famy= form.find("[name=family]"),
      desc, ido
    ;
  switch (cmd) {
    
    // ------------------------------------------------------ create
    /// @par cmd=cms_create
    /// zahájení přihlášky, zjištění stavu akce nebo přechod na cms_create
    case 'cms_create': {
      desc= Ezer.cms.form[par.form];
      if ( desc.CALL && desc.CALL.full_A ) {
        // zjistíme jestli je akce naplněná
        jQuery.post(Ezer.web.index,{cms:1,cmd:'CMS_full',par:par})
          .done(y=>{
            cms_form('cms_show',y.par);
          })
          .fail(e=>{ cms_form('cms_show',par); });
      }
      else {
        // pokud není definováno rozpoznávání naplněnosti akce, přejdeme dál
        cms_form('cms_show',par);
      }
      break;
    }

    // ------------------------------------------------------ show
    /// @par cmd=cms_show
    /// prvotní zobrazení a žádost o zapsání mailu
    /// *DBG* pokud Ezer.cms.test=1 mail se neposílá
    case 'cms_show': {
      desc= Ezer.cms.form[par.form];
      conf= desc.TYPE.includes('confirm') ? TEXT('cms_confirm') : '';
      famy= desc.TYPE.includes('family') ? TEXT('cms_family_1') : '';
      // vytvoření položek formuláře
      let items= '', must= "<b class='cms_must'>*</b>";
      for (let i in desc.ELEM ) {
        let e= desc.ELEM[i], t= e[0], w= e[3], h= e[4],
            lab= `<span>${e[2]} ${e[1]=='*' || e[1]=='+' ? must : ''}</span>`;
        if ( t=='h' )
          items+= `<input name='${i}' type='hidden'>`;
        else if ( t=='c' )
          items+= 
            `<label>${lab}<input name='${i}' type='checkbox'></label>`;
        else
          items+= h 
            ? `<label>${lab}<textarea name='${i}' style='width:${w}px;height:${h}px'/></label>`
            : `<label>${lab}<input name='${i}' type='text' style='width:${w}px'></label>`;
      }
      // případné vyžádání potvrzení
      if ( conf ) {
        conf= `<div name='confirm'><label>
                <input type='checkbox' value='0' onchange="cms_form('cms_confirm');">${conf}
               </label><hr></div>`;
      }
      // případné přidání rodinných příslušníků
      if ( famy ) {
        famy= `<div name='family'><label>
                <input type='checkbox' value='0' onchange="cms_form('cms_family');">${famy}
               </label><hr></div>`;      
      }
      // vytvoření formuláře
      let test= Ezer.cms.test ? `<div>TEST</div>` : '';
      form= jQuery(`
        <div class='cms_form ${par.form}'>
          <span>${par.title}${test}</span>
          <div>
            <div>${par.full ? TEXT('cms_create_2') : TEXT('cms_create_1')}<hr></div>
            <div>
              <label name="mail"><span>mailová adresa ${must}</span>
                  <input type="text" style='width:190px'></label>
              <label name="mail">&nbsp;
                  <button onclick="cms_form('cms_mail');">Odeslat mail</button></label>
              <label name="pin">PIN
                  <input type="text" style='width:40px'></label>
              <label name="pin">&nbsp;
                  <button onclick="cms_form('cms_pin');">Potvrdit PIN</button></label>
              <hr>
            </div>
            <div name='items'>${items}<hr></div>  
            ${conf}
            ${famy}
            <div>
              <button name='submit' onclick="cms_form('cms_submit');">Odeslat a přihlásit</button>
              <button name='quit' onclick="cms_form('cms_destroy')">Nepřihlašovat</button>
              <hr>
            </div>      
          </div>      
        </div>
      `).appendTo(jQuery('body'))
        .fadeIn()
        .data('par',par);
      form.find("[name=mail] input").val(''); 
      form.find("[name=pin] input").val('');
      if ( Ezer.cms.test ) form.find("[name=mail] input").val('martin@smidek.eu'); // *DBG*
      form.find("[name=pin],[name=items],[name=confirm],[name=submit],[name=family]")
        .addClass('disabled3');
      break;
    }

    // ------------------------------------------------------ mail
    /// @par cmd=cms_mail
    /// zaslání žádosti, zjištění známosti mailu, zapamatování ido resp. 0
    /// *DBG* pokud Ezer.cms.test=1 přímo zobrazí pin
    case 'cms_mail': {
      par= form.data('par');
      jQuery.post(Ezer.web.index,{cms:1,cmd:'CMS_mail',par:par,test:Ezer.cms.test,
          mail:mail.val().trim()})
        .done(y=>{
          if ( Ezer.cms.test ) pin.val(y.pin); // *DBG*
          info.html(y.info);
          if ( y.ok ) {
            if ( y.ido!==undefined ) 
              form.data('ido',y.ido);
            form.find("[name=pin]").removeClass('disabled3');
            pin.focus();
          }
          else {
            form.find("[name=mail]").removeClass('disabled3');
          }
        })
        .fail(e=>{ 
          info.html(TEXT('cms_error')); 
        });
        form.find("[name=mail]").addClass('disabled3');
      break;
    }

    // ------------------------------------------------------ pin
    /// @par cmd=cms_pin
    /// zaslání zapsaného pinu a pokud
    /// * je osoba známá (state=old) zobrazí data a požádá o opravu
    /// * je osoba neznámá (state=new) 
    case 'cms_pin': {
      ido= form.data('ido');
      par= form.data('par');
      desc= Ezer.cms.form[par.form];
      jQuery.post(Ezer.web.index,{cms:1,cmd:'CMS_pin',par:par,test:Ezer.cms.test,
          mail:mail.val().trim(),pin:pin.val(),ido:ido})
        .done(y=>{ 
          if ( y.state=='error' ) {
            // chybně vrácený pin
            info.html(y.info); 
          }
          else if ( y.state=='old' ) {
            // je to známá osoba, dostali jsme data
            info.html(y.info); 
            for (name in y.data) {
              let field= data.find(`input[name=${name}],textarea[name=${name}]`),
                  t= desc.ELEM[name][0],
                  x= desc.ELEM[name][1];
              switch ( t ) {
                case 'c':  // check 
                  field.prop('checked',Number(y.data[name])).data('orig',y.data[name]);
                  break;
                default:
                  field.val(y.data[name]).data('orig',y.data[name].trim());
              }
              // zakaž položky s '+'
              if ( x=='+' ) {
                form.find(`[name=${name}]`).prop('disabled',true );
              }
            }
            // příprava formuláře pro vstup dat
            form.find("[name=pin]").addClass('disabled3');
            form.find("[name=items],[name=confirm],[name=submit],[name=family]")
              .removeClass('disabled3');
          }
          else if ( y.state=='new' && desc.TYPE.includes('allow_unknown') ) {
            // neznámá osoba, ale definice formuláře to dovoluje - nicméně rodinu přidat nelze
            info.html(y.info); 
            // příprava formuláře pro vstup dat
            form.find("[name=pin]").addClass('disabled3');
            form.find("[name=items],[name=confirm],[name=submit]").removeClass('disabled3');
          }
          else {
            // neznámá osoba, což formulář nedovoluje - vysvětluje to textem cms_pin_no
            info.html(TEXT('cms_pin_no')); 
          }
        })
        .fail(e=>{ info.html(TEXT('cms_error')); });
      break;
    }

    // ------------------------------------------------------ family
    /// @par cmd=cms_family
    /// v případě přidávání rodinných příslušníků
    case 'cms_family': {
      ido= form.data('ido');
      par= form.data('par');
      desc= Ezer.cms.form[par.form];
      if ( famy.find('input').prop('checked') ) {
        famy.find('label').addClass('disabled3');
        // požádej o členy rodiny
        jQuery.post(Ezer.web.index,{cms:1,cmd:'CMS_family',par:par,test:Ezer.cms.test,
            ido:ido})
          .done(y=>{ 
            info.html(y.info); 
//            for (let clen of y.memb) {
//              let check= clen.spolu=='1' ? ' checked' : '';
//              jQuery(                                                                         TODO
//                `<div name='member'><label>
//                  <input type='checkbox' value='${clen.id}');"${check}>${clen.name}
//                 </label></div>`
//              ).appendTo(famy);
//            }
          })
          .fail(e=>{ info.html(TEXT('cms_error')); });
      }
      break;
    }

    // ------------------------------------------------------ confirm
    /// @par cmd=cms_confirm
    /// v případě odsouhlasení předání osobních dat zruš hlášku
    case 'cms_confirm': {
      par= form.data('par');
      desc= Ezer.cms.form[par.form];
      if ( conf.prop('checked') ) {
        if ( TEXT('cms_confirm_missing_2') )
          info.html(TEXT('cms_confirm_missing_2'));
        conf.removeClass('missing');
      }
      break;
    }

    // ------------------------------------------------------ submit
    /// @par cmd=cms_submit
    /// zaslání zapsaných dat s žádostí o přihlášení na akci
    case 'cms_submit': {
      ido= form.data('ido');
      par= form.data('par');
      desc= Ezer.cms.form[par.form];
      // vytvoř seznam ze změněných hodnot
      let chngs= [], join= [], missing= 0, bad_date= 0;
      data.find('input,textarea').each((i,e) => {
        let elem= jQuery(e).removeClass('missing'),
            name= elem.prop('name'),
            typ= desc.ELEM[name][0];
        let val= typ=='c'  
            ? (elem.prop('checked') ? 1 : 0)
            : elem.val().trim();
        // zkontroluj vyplnění povinných a povinných při nové osobě
        if ( (desc.ELEM[name][1]=='*' || desc.ELEM[name][1]=='+' && !ido) && !val ) {
          elem.addClass('missing');
          missing++;
        }
        // zkontroluj tvar datumů
        if ( desc.ELEM[name][0]=='d' && val ) {
          dmr= val.match(/\d{1,2}\.\d{1,2}\.\d{4}/);
          if ( !dmr ) {
            elem.addClass('missing');
            bad_date++;
          }
        }
        // zapiš změněná a skrytá data 
        let orig= elem.data('orig');
        if ( orig==undefined && val!='' 
          || orig!=undefined && val!=orig 
          || elem.attr('type')=='hidden' )
          chngs.push({name:name,value:val});
      });
      // pokud zpracováváme i rodinné příslušníky, předej účast
      famy.find('input').each((i,e) => {
        let elem= jQuery(e);
        if ( elem.val()!=0 ) {
          join.push({id:elem.val(),spolu:elem.prop('checked')?1:0});
        }        
      });
      // 1. nejsou zadána povinná data?
      if ( missing ) {
        info.html(TEXT('cms_submit_missing'))
      }
      else if ( bad_date ) {
        info.html(TEXT('cms_submit_bad_date'))
      }
      else {
        // 2. je požadováno potvrzení?
        if ( conf.length ) {
          if ( !conf.prop('checked') ) {
            info.html(TEXT('cms_confirm_missing_1'));
            conf.addClass('missing');
            break;
          }
        }
        // disable zaslaných položek
        form.find("[name=items],[name=member]").addClass('disabled3');
        // pošli změněná data
        jQuery.post(Ezer.web.index,{cms:1,cmd:'CMS_submit',par:par,test:Ezer.cms.test,
            mail:mail.val().trim(),pin:pin.val(),ido:ido,chngs:chngs,join:join})
          .done(y=>{ 
            info.html(y.info); 
            form.find("[name=data],[name=confirm],[name=submit]").addClass('disabled3');
            form.find('[name=quit]').html('Hotovo');
          })
          .fail(e=>{ info.html(TEXT('cms_error')); });
      }
      break;
    }

    // ------------------------------------------------------ destroy
    /// @par cmd=cms_destroy
    /// zrušení formuláře
    case 'cms_destroy': {
      form.fadeOut(() => form.remove());
      break;
    }
  }
}
