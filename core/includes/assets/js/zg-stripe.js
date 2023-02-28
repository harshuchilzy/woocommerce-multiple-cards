jQuery(document).ready(function ($) {
  var syncData = {}
  syncData.totalSteps = 4;
  syncData.currentStep = 1;
  // var numbers = new NumberSwiper('myNumberSwiper');
  // console.log(numbers)

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
    let index = $(parent).parents('.step').data('index');
    // let thisEle = $(parent).parents('.step').next();
    $(parent).find('.verify-card').prop('disabled', true);
    console.log({
      action: "create_setup_intention",
      email: email,
      cardNo : cardNo,
      expiry: expiry,
      csv: csv,
      amount: amount,
      nonce: $('#zg-nonce').val()
    })
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
          $(parent).append('<input class="intentionData" type="hidden" name="card['+index+'][payment_method]" value="'+response.data.intention.payment_method+'"/>')
          $(parent).append('<input class="intentionData" type="hidden" name="card['+index+'][customer]" value="'+response.data.intention.customer+'"/>')
          $(parent).append('<input class="intentionData" type="hidden" name="card['+index+'][amount]" value="'+amount+'"/>')
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
    $(this).parents('.step').next().find('.totalSteps').html(syncData.totalSteps);
    syncData.currentStep += 1;
    let width = Math.ceil(Math.floor((syncData.currentStep / syncData.totalSteps) * 100)/ 10) * 10;
    $(this).parents('.step').next().find('.stepWidth').addClass('w-'+width);
    $(this).parents('.step').next().find('.currentStep').html(syncData.currentStep);

    if($(this).parents('.step').find('.card-val').length > 0){
      let cardVal = $(this).parents('.step').find('.card-val').val();
      let amountToPay = syncData.amountToPay - cardVal;
      syncData.amountToPay = amountToPay.toFixed(2)
      $(this).parents('.step').nextAll('.card-amount-wrap').first().find('.card-val').attr('max', syncData.amountToPay)
    }

    console.log("next click");
    $(this).parents(".step").hide();
    let next = $(this).parents(".step").nextAll(".step").first();
    if($(next).hasClass('last-step')){
      $('.cards-list > li').remove();

      $('.card-element-wrap').each(function(i, ele){
        let cardNo = $(ele).find('.card_ccNo').val();
        let cardIcon = creditCardType(cardNo);
        let lastDigits = $.trim(cardNo.substr(cardNo.length - 5));
        let index = $(ele).data('index');
        let amount = $('.card-amount-wrap[data-index="'+index+'"]').find('.card-val').val();
        $('<li data-card="'+lastDigits+'"><div><span>'+cardIcon+' **** **** **** '+lastDigits+'</span><span class="card-list-amount">'+zg.currency + amount+' <i data-index="'+index+'" class="editCard fas fa-pencil-alt"></i></span></div></li>').appendTo('.cards-list');
      });
    }
    $(next).show();
    $(next).find('.amount-to-pay').html(syncData.amountToPay)

    $('.list-cards').removeClass('list-cards');
    $('.card-element-wrap').last().find('.next-btn').addClass('list-cards');
  });

  $(document).on('change', '.card_ccNo, .card_expdate, .card_cvv', function () {
   if($(this).parents('.step').find('.intentionData').length > 0){
    $(this).parents('.step').find('.intentionData').remove();
    $(this).parents('.step').find('.card-next-btn').addClass('verify-card').removeClass('next-btn')
   }
 });

  $(document).on('click', '.editCard', function(e){
    let index = $(this).data('index');
    $('.card-element-wrap[data-index="'+index+'"]').find('.process-elements').hide();
    $('.card-element-wrap[data-index="'+index+'"]').find('.card-element').show();
    $('.card-element-wrap[data-index="'+index+'"]').show();
    $(this).parents('.step').hide();
  })

  $(document).on("click", ".assign-value", function () {
    $(this).parents(".step-inner").find(".card-val").val($(this).val()).trigger("change");
  });

  syncData.amountToPay = zg.orderTotal;

  $(document).on("change", ".card-val", function (e) {
    var amountToPay = syncData.amountToPay;
    amountToPay = amountToPay - $(this).val();
    amountToPay = zg.currency + amountToPay.toFixed(2);

    $(this).parents(".step-inner").find(".amount-to-pay").text(amountToPay);
    $(this).parents(".step").next().find(".card-amount-to-pay").text(amountToPay);
    $(this).parents(".step").find('.next-btn').prop('disabled', false);
    $(this).parents(".step").next().find(".card-chargable").text(zg.currency + $(this).val());
  });

 $("form.woocommerce-checkout").on("click", ".prev-btn", function (e) {
    e.preventDefault();
    $(this).parents('.step').prevAll().first().find('.totalSteps').html(syncData.totalSteps);
    syncData.currentStep -= 1;
    let width = Math.ceil(Math.floor((syncData.currentStep / syncData.totalSteps) * 100)/ 10) * 10;
    $(this).parents('.step').prevAll().first().find('.stepWidth').addClass('w-'+width);
    $(this).parents('.step').prevAll().first().find('.currentStep').html(syncData.currentStep);

    if($(this).parents('.step-inner').find('.process-elements').length > 0 && $(this).parents('.step-inner').find('.process-elements').is(':visible')){
      $(this).parents('.step-inner').find('.process-elements').hide();
      $(this).parents('.step-inner').find('.card-element').show();
      $(this).parents('.step-inner').find('.verify-card').prop('disabled', false);
    }
    if($(this).parents('.step-inner').find('.card-elements').length > 0 && !$(this).parents('.step-inner').find('.process-elements').is(':visible')){
      $(this).parents(".step").hide("slow");
      $(this).parents(".step").prevAll(".step").first().show();
    }
    else{
      $(this).parents(".step").hide("slow");
      $(this).parents(".step").prevAll(".step").first().show();
    }
  });

  let cardsCount = 1;
  $(document).on("updated_checkout", function (e) {
    $('.card_ccNo').payform('formatCardNumber');
    $('.card_expdate').payform('formatCardExpiry');
    $('.card_cvv').payform('formatCardCVC');

    $('.card-element-wrap').last().find('.next-btn').addClass('verify-cards').text('Verify cards');

    $(document).on('change', '.card_count', function(){
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
            if(name == 'card_number'){
              $(ele).payform('formatCardNumber');
            }else if(name == 'card_expiry'){
              $(ele).payform('formatCardExpiry');
            }else if(name == 'card_csv'){
              $(ele).payform('formatCardCVC');
            }

            $(ele).attr('name', 'card[' + i + '][' + name + ']')
          })
          console.log(i + 'and ' + index)
          
          $(cloneCardEle).insertBefore('.last-step');
          $(document).trigger('card-cloned', cloneCardEle);
          syncData.totalSteps += 2;
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
          syncData.totalSteps -= 2;
        }
      }

      $('.next-btn').removeClass('verify-cards').text('Next')
      $('.list-cards').removeClass('list-cards');
      $('.card-element-wrap').last().find('.next-btn').addClass('list-cards');
    });
  });

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
