jQuery(document).ready(function ($) {
  console.log(zg);

  $("form.woocommerce-checkout").on("click", ".next-btn:not(.verify-cards)", function (e) {
    e.preventDefault();
    console.log("next click");
    $(this).parents(".step").hide("slow");
    let next = $(this).parents(".step").nextAll(".step").first();
    $(next).show("slow");
    $(next).find('.amount-to-pay').html(syncData.amountToPay)

  });

  $(document).on("click", ".assign-value", function () {
    $(this).parents(".step-inner").find(".card-val").val($(this).val()).trigger("change");
  });
  var syncData = {}
  syncData.amountToPay = zg.orderTotal;

  $(document).on("change", ".card-val", function (e) {
    var amountToPay = syncData.amountToPay;
    console.log(amountToPay)
    amountToPay = amountToPay - $(this).val();
    // syncData.amountToPay = amountToPay.toFixed(2);

    amountToPay = zg.currency + amountToPay.toFixed(2);

    $(this).parents(".step-inner").find(".amount-to-pay").text(amountToPay);
    $(this).parents(".step").next().find(".card-amount-to-pay").text(amountToPay);
    $(this)
      .parents(".step")
      .next()
      .find(".card-chargable")
      .text(zg.currency + $(this).val());
  });

  $(document).on("blur", ".card-val", function (e) {
    let amountToPay = syncData.amountToPay - $(this).val();
    syncData.amountToPay = amountToPay.toFixed(2)
  })

  $(document).on('keydown', '.card_ccNo', function (e) {
    if ($(this).val().length > 0) {

      if ($(this).val().replace(/\s/g, '').length % 4 == 0) {
        let val = $(this).val() + " ";
        $(this).val(val)
      }
    }
  });

  $(document).on('keydown', '.card_expdate', function (e) {
    if ($(this).val().length > 0 && $(this).val().length < 5) {

      if ($(this).val().replace('/', '').length % 2 == 0) {
        let val = $(this).val() + "/";
        $(this).val(val)
      }
    }
  });

  $("form.woocommerce-checkout").on("click", ".prev-btn", function (e) {
    e.preventDefault();
    console.log("prev click");
    $(this).parents(".step").hide("slow");
    $(this).parents(".step").prevAll(".step").first().show("slow");
  });

  $(document).on('change', '.card_ccNo', function () {
    let type = creditCardType($(this).val());
    $(this).after('<input type="hidden" class="ccType" value="' + type + '"/>')
  })

  let cardsCount = 1;
  $(document).on("updated_checkout", function (e) {
    var onchanged = function (index) {
      cardsCount = index;
      let cloneCardAmountEle = $('.card-amount-wrap').first().clone();
      let cloneCardEle = $('.card-element-wrap').first().clone();

      for (let i = 1; i <= index; i++) {
        if ($('.card-amount-wrap[data-index="' + i + '"]').length <= 0) {
          $(cloneCardAmountEle).attr('data-index', i);

          $(cloneCardAmountEle).find('input').each(function (key, ele) {
            let name = $(ele).data('name');
            if(name == 'card_amount'){
              $(ele).attr('max', syncData.amountToPay);
            }
            $(ele).attr('name', 'card[' + i + '][' + name + ']');
          })

          $(cloneCardAmountEle).insertBefore('.last-step');
          $(cloneCardEle).attr('data-index', i);
          $(cloneCardEle).find('input').each(function (key, ele) {
            let name = $(ele).data('name');
            $(ele).attr('name', 'card[' + i + '][' + name + ']')
          })
          console.log(i + 'and ' + index)
          
          $(cloneCardEle).insertBefore('.last-step');
          $(document).trigger('card-cloned', cloneCardEle);

        }
      }
      if (index < $('.card-amount-wrap').length) {
        console.log($('.card-amount-wrap').length + 'lala' + index)

        for (let j = 0; j <= $('.card-amount-wrap').length; j++) {
          if (j <= index) {
            continue;
          }
          console.log('delete' + j)
          $('.step[data-index=' + j + ']').remove()
        }
      }

      $('.next-btn').removeClass('verify-cards').text('Next')
      $('.card-element-wrap').last().find('.next-btn').addClass('verify-cards').text('Verify cards');

    };
    new SpinnerPicker(document.getElementById("cards-spinner"), function (index) {
      // Check if the index is below zero or above 10 - Return null in this case
      if (index < 0 || index > 10) {
        return null;
      }
      if (index == 0) {
        return 1;
      }
      return index;
    }, {
      index: 1,
      width: 10,
      height: 15,
    }, onchanged
    );

    // $('.zg-stripe-main-wrapper').steps({
    //   headerTag: "h4",
    //   bodyTag: "div",
    //   transitionEffect: "slideLeft",
    //   autoFocus: true
    // });
  });

  $(document).on('card-cloned', function (ele) {
    $(this).parents('.step-inner').card({

      // number formatting
      formatting: true,

      // selectors
      formSelectors: {
        numberInput: $(ele).find('.card_ccNo'),
        expiryInput: $(ele).find('.card_expiry'),
        cvcInput: $(ele).find('.card_cvv'),
      }
    });
  });

  $(document).on('click', '.verify-cards', function(){
    console.log($('#zg-nonce').val())
    var form = $("form.checkout").serialize();
    console.log(form)
    $.ajax({
      method: 'POST',
      url : zg.ajaxurl,
      data : {action: "ajaxify_cards", form : form, nonce: $('#zg-nonce').val()},
      success: function(response) {
        console.log(response)
        //  if(response.type == "success") {
        //     jQuery("#like_counter").html(response.like_count);
        //  }
        //  else {
        //     alert("Your like could not be added");
        //  }
      }
   });
  })

  function creditCardType(cc) {
    let amex = new RegExp("^3[47][0-9]{13}$");
    let visa = new RegExp("^4[0-9]{12}(?:[0-9]{3})?$");
    let cup1 = new RegExp("^62[0-9]{14}[0-9]*$");
    let cup2 = new RegExp("^81[0-9]{14}[0-9]*$");

    let mastercard = new RegExp("^5[1-5][0-9]{14}$");
    let mastercard2 = new RegExp("^2[2-7][0-9]{14}$");

    let disco1 = new RegExp("^6011[0-9]{12}[0-9]*$");
    let disco2 = new RegExp("^62[24568][0-9]{13}[0-9]*$");
    let disco3 = new RegExp("^6[45][0-9]{14}[0-9]*$");

    let diners = new RegExp("^3[0689][0-9]{12}[0-9]*$");
    let jcb = new RegExp("^35[0-9]{14}[0-9]*$");

    if (visa.test(cc)) {
      return "VISA";
    }
    if (amex.test(cc)) {
      return "AMEX";
    }
    if (mastercard.test(cc) || mastercard2.test(cc)) {
      return "MASTERCARD";
    }
    if (disco1.test(cc) || disco2.test(cc) || disco3.test(cc)) {
      return "DISCOVER";
    }
    if (diners.test(cc)) {
      return "DINERS";
    }
    if (jcb.test(cc)) {
      return "JCB";
    }
    if (cup1.test(cc) || cup2.test(cc)) {
      return "CHINA_UNION_PAY";
    }
    return undefined;
  }
});
