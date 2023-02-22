jQuery(document).ready(function ($) {

  $(document).on('click', '.next-btn', function(){
    $(this).parents('steps').hide('slow');
    $(this).parents('steps').siblings('steps'),show('slow');
  });

  let cardsCount = 1;
  $(document).on('updated_checkout', function (e) {
    var onchanged = function (index) {
      cardsCount = index
    };
    new SpinnerPicker(
      document.getElementById("cards-spinner"),
      function (index) {
        // Check if the index is below zero or above 10 - Return null in this case
        if (index < 0 || index > 10) {
          return null;
        }
        return index;
      }, {
      index: 1,
      width: 10,
      height: 15
    }, onchanged
    );

    // $('.zg-stripe-main-wrapper').steps({
    //   headerTag: "h4",
    //   bodyTag: "div",
    //   transitionEffect: "slideLeft",
    //   autoFocus: true
    // });
  })
})
