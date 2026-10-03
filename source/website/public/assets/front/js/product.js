"use strict";

if ($('.price-slider-range').length) {
  $(".price-slider-range").slider({
    range: true,
    min: min_price,
    max: max_price,
    values: [curr_min, curr_max],
    slide: function (event, ui) {
      $("#price").val(currency_symbol +'' + ui.values[0] + " - " + currency_symbol +'' + ui.values[1]);
      $('#min-id').val(ui.values[0]);
      $('#max-id').val(ui.values[1]);
    }
  });
  $("#price").val(currency_symbol + '' + $(".price-slider-range").slider("values", 0) +
    " - " + currency_symbol + '' + $(".price-slider-range").slider("values", 1));
}
