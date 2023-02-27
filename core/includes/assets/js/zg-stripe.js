jQuery(document).ready(function ($) {
  console.log(zg);

  var numbers = new NumberSwiper('myNumberSwiper');
  console.log(numbers)

  $(document).on('click', '.verify-card', function(e){
    e.preventDefault();
    let validate = $('#billing_email').trigger('validate');
    if($('#billing_email_field').hasClass('woocommerce-validated') == false){
      e.preventDefault();
      return false;
    }
    let email = $('#billing_email').val();
    let parent = $(this).parents('.step-inner');
    let cardNo = $(parent).find('.card_ccNo').val();
    let expiry = $(parent).find('.card_expdate').val();
    let csv = $(parent).find('.card_cvv').val();
    let amount = $(this).parents('.step').prev('.step').find('.card-val').val();
    $(parent).find('.card-element').hide();
    $(parent).find('.zg-card-processing').show();
    $(parent).find('.process-elements').show();
    $('.zg-card-stat-note').hide();
    // let thisEle = $(parent).parents('.step').next();
    $(parent).find('.verify-card').prop('disabled', true);
    console.log('Amount ' + amount);
    $.ajax({
      method: 'POST',
      url : zg.ajaxurl,
      data : {
        action: "create_setup_intention",
        email: email,
        cardNo : cardNo,
        expiry: expiry,
        csv: csv,
        amount: amount,
        nonce: $('#zg-nonce').val()
      },
      success: function(response) {
        console.log(response)
        $('.zg-card-processing').hide();
        if(response.data.type == 'success'){
          $(parent).find('.zg-card-success').show();
          $(parent).find('.verify-card').addClass('next-btn').removeClass('verify-card').prop('disabled', false);;

        }else{
          $(parent).find('#error-msg').text(response.data.message)
          $(parent).find('.zg-card-error').show();
        }
      },
      error: function(e){
        console.log(e)
      }
    });

  });

  $("form.woocommerce-checkout").on("click", ".next-btn:not(.verify-cards)", function (e) {
    e.preventDefault();

    if($(this).parents('.step').find('.card-val').length > 0){
      let cardVal = $(this).parents('.step').find('.card-val').val();
      let amountToPay = syncData.amountToPay - cardVal;
      syncData.amountToPay = amountToPay.toFixed(2)
      $(this).parents('.step').nextAll('.card-amount-wrap').first().find('.card-val').attr('max', syncData.amountToPay)
    }

    console.log("next click");
    $(this).parents(".step").hide();
    let next = $(this).parents(".step").nextAll(".step").first();
    $(next).show();
    $(next).find('.amount-to-pay').html(syncData.amountToPay)

    $('.list-cards').removeClass('list-cards');
    $('.card-element-wrap').last().find('.next-btn').addClass('list-cards');
  });

  $(document).on("click", ".assign-value", function () {
    $(this).parents(".step-inner").find(".card-val").val($(this).val()).trigger("change");
  });
  var syncData = {}
  syncData.amountToPay = zg.orderTotal;

  $(document).on("change", ".card-val", function (e) {

    if($(this).val() > $(this).attr('max')){
      // $(this).val($(this).attr('max'))
    }

    var amountToPay = syncData.amountToPay;
    amountToPay = amountToPay - $(this).val();
    amountToPay = zg.currency + amountToPay.toFixed(2);

    $(this).parents(".step-inner").find(".amount-to-pay").text(amountToPay);
    $(this).parents(".step").next().find(".card-amount-to-pay").text(amountToPay);
    $(this).parents(".step").find('.next-btn').prop('disabled', false);
    $(this).parents(".step").next().find(".card-chargable").text(zg.currency + $(this).val());
  });

  $(document).on("blur", ".card-val", function (e) {
    // let amountToPay = syncData.amountToPay - $(this).val();
    // syncData.amountToPay = amountToPay.toFixed(2)
    // $(this).parents('.step').nextAll('.card-amount-wrap').first().find('.card-val').attr('max', syncData.amountToPay)
  });

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
    // let type = creditCardType($(this).val());
    // $(this).after('<input type="hidden" class="ccType" value="' + type + '"/>')
  })

  let cardsCount = 1;
  $(document).on("updated_checkout", function (e) {
    // var numbers = new NumberSwiper('card-count');
    $('.card-element-wrap').last().find('.next-btn').addClass('verify-cards').text('Verify cards');

    $(document).on('change', '.card_count', function(){
    // var onchanged = function (index) {
      let index = $(this).val()
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
          $(cloneCardAmountEle).find('.card-val').val('');
          console.log('max' + syncData.amountToPay)
          $(cloneCardAmountEle).find('.next-btn').prop('disabled', true);
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
      $('.list-cards').removeClass('list-cards');
      $('.card-element-wrap').last().find('.next-btn').addClass('list-cards');
    });

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

  $(document).on('click', '.verify-cards', function(e){
    let validate = $('#billing_email').trigger('validate');
    console.log(validate)
    if(syncData.amountToPay > 0){
      console.log('You have more remaining balance');
      e.preventDefault();
      return false;
    }

    // verify-step
    $('.cards-list > li').remove();

    $('.card-element-wrap').each(function(i, ele){
      let cardNo = $(ele).find('.card_ccNo').val();
      let cardIcon = creditCardType(cardNo);
      let lastDigits = $.trim(cardNo.substr(cardNo.length - 5));
      let index = $(ele).data('index');
      let amount = $('.card-amount-wrap[data-index="'+index+'"]').find('.card-val').val();
      $('<li data-card="'+lastDigits+'"><div><span>'+cardIcon+' **** **** **** '+lastDigits+'</span><span class="card-list-amount">'+zg.currency + amount+' <i data-index="'+index+'" class="fal fas far fa-pencil"></i></span></div></li>').appendTo('.cards-list');
    });


    if($('#billing_email_field').hasClass('woocommerce-validated') == false){
      e.preventDefault();
      return false;
    }

    $('.verify-step').show();


    // return false;
    console.log($('#zg-nonce').val())
    var form = $("form.checkout").serialize();
    console.log(form)
    $.ajax({
      method: 'POST',
      url : zg.ajaxurl,
      data : {action: "ajaxify_cards", form : form, nonce: $('#zg-nonce').val()},
      success: function(response) {
        console.log(response)
        $('.last-step').show()
        $.each(response.data, function(i, data){
          console.log(data)
          if(data.type == "success"){
            $('li[data-card="'+data.card+'"]').addClass('validated');
          }else if(data.type == 'error'){
            let digits = $.trim(data.last4.substr(data.last4.length - 4));
            $('li[data-card="'+digits+'"]').append('<p class="text-error">'+data.message+'</p>')
          }
        });
        //  if(response.type == "success") {
        //     jQuery("#like_counter").html(response.like_count);
        //  }
        //  else {
        //     alert("Your like could not be added");
        //  }
      },
      error: function(e){
        console.log(e)
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
      return '<i class="fab fa-cc-visa"></i>';
      // return "VISA";
    }
    if (amex.test(cc)) {
      return '<i class="fab fa-cc-amex"></i>';
      // return "AMEX";
    }
    if (mastercard.test(cc) || mastercard2.test(cc)) {
      return '<i class="fab fa-cc-mastercard"></i>';
      // return "MASTERCARD";
    }
    if (disco1.test(cc) || disco2.test(cc) || disco3.test(cc)) {
      return '<i class="fab fa-cc-discover"></i>';
      // return "DISCOVER";
    }
    if (diners.test(cc)) {
      return '<i class="fab fa-cc-diners-club"></i>';
      // return "DINERS";
    }
    if (jcb.test(cc)) {
      return '<i class="fab fa-cc-jcb"></i>';
      // return "JCB";
    }
    if (cup1.test(cc) || cup2.test(cc)) {
      return "CHINA_UNION_PAY";
    }
    return '<i class="fab fa-cc-visa"></i>';
  }
});
