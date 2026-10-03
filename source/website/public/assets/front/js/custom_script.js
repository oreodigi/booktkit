"use strict";
// 06. Price Range Fliter jQuery UI
if ($('.price-slider-range').length) {
  $(".price-slider-range").slider({
    range: true,
    min: min_price,
    max: max_price,
    values: [curr_min, curr_max],
    slide: function (event, ui) {
      if (position == 'left') {
        $("#price").val(symbol + ui.values[0] + " - " + symbol + ui.values[1]);
      } else {
        $("#price").val(ui.values[0] + symbol + " - " + ui.values[1] + symbol);
      }

      $('#min-id').val(ui.values[0]);
      $('#max-id').val(ui.values[1]);
    }
  });
  if (position == 'left') {
    $("#price").val(symbol + $(".price-slider-range").slider("values", 0) +
      " - " + symbol + $(".price-slider-range").slider("values", 1));
  } else {
    $("#price").val($(".price-slider-range").slider("values", 0) + symbol +
      " - " + $(".price-slider-range").slider("values", 1) + symbol);
  }
}


$('#countryDropdown').select2({
  placeholder: 'Select Country',
  allowClear: true,
  minimumInputLength: 0,
  ajax: {
    url: countryUrl,
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return {
        search: params.term || '',
        page: params.page || 1
      };
    },
    processResults: function (data) {
      return {
        results: data.results.map(function (item) {
          return {
            text: item.name,
            id: item.slug
          };
        }),
        pagination: { more: data.more }
      };
    },
    cache: true
  }
});


$('#stateDropdown').select2({
  placeholder: 'Select State',
  allowClear: true,
  minimumInputLength: 0,
  ajax: {
    url: stateUrl,
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return {
        search: params.term || '',
        page: params.page || 1,
        country: $('#countryDropdown').val() || ''
      };
    },
    processResults: function (data) {
      return {
        results: data.results.map(function (item) {
          return {
            text: item.name,
            id: item.slug
          };
        }),
        pagination: { more: data.more }
      };
    },
    cache: true
  }
});


$('#cityDropdown').select2({
  placeholder: 'Select City',
  allowClear: true,
  minimumInputLength: 0,
  ajax: {
    url: cityUrl,
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return {
        search: params.term || '',
        page: params.page || 1,
        state: $('#stateDropdown').val() || ''
      };
    },
    processResults: function (data) {
      return {
        results: data.results.map(function (item) {
          return {
            text: item.name,
            id: item.slug
          };
        }),
        pagination: { more: data.more }
      };
    },
    cache: true
  }
});



$('body').on('change', '#countryDropdown, #stateDropdown, #cityDropdown', updateUrl);


function updateUrl() {
  $('#countryCityForm').submit();
}
$('body').on('change','#event_sort',function(){
    $('#countryCityForm').submit();
});

$('#event_sort').select2();
