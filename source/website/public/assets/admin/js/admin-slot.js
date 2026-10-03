$(document).ready(function () {
  "use strict"
  $(document).on('change', '.seat_mapping_btn', function (event) {
    $('.request-loader').addClass('show');
    let btn = $(this);
    let is_checked = btn.is(':checked');
    let slot_unique_id = btn.data('slot_unique_id');
    let pricing_type = btn.data('pricing_type');
    let form_url = btn.data('url');

    $(this).siblings(".slot_enable_input").val(is_checked ? 1 : 0);
    $('.request-loader').removeClass('show');
  });
});
