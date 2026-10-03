$(document).ready(function () {
  'use strict';

  $('body').on('change', '.country_select', function () {
    $('.request-loader').addClass('show');
    let country_id = $(this).val();

    $.ajax({
      url: getStateUrl,
      type: 'get',
      data: { country_id: country_id },
      success: function (res) {
        $('.request-loader').removeClass('show');
        if (res.states == true) {
          $('.state_div').show();
          let $stateSelect = $('.state_select');
          $stateSelect.empty().trigger('change');

          $stateSelect.select2({
            dropdownParent: $('#createModal'),
            placeholder: 'Select Country',
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
                  country: $('select[name="country_id"]').val() || '',
                  lang: $('input[name="language_id"]').val() ?? $('select[name="language_id"]').val()
                };
              },
              processResults: function (data) {
                return {
                  results: data.results.map(function (item) {
                    return {
                      text: item.name,
                      id: item.id
                    };
                  }),
                  pagination: { more: data.more }
                };
              },
              cache: true
            }
          });
        }
      }
    });
  });


  $('.countryDropdown').each(function () {
    $('.countryDropdown').select2({
      dropdownParent: $('#createModal'),
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
            page: params.page || 1,
            lang: $('input[name="language_id"]').val(),
          };
        },
        processResults: function (data) {
          return {
            results: data.results.map(function (item) {
              return {
                text: item.name,
                id: item.id
              };
            }),
            pagination: { more: data.more }
          };
        },
        cache: true
      }
    });
  });

  $('.stateDropdown').each(function () {
    $('.stateDropdown').select2({
      dropdownParent: $('#createModal'),
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
            lang: $('select[name="language_id"]').val(),
            country: $('select[name="country_id"]').val()
          };
        },
        processResults: function (data) {
          return {
            results: data.results.map(function (item) {
              return {
                text: item.name,
                id: item.id
              };
            }),
            pagination: { more: data.more }
          };
        },
        cache: true
      }
    });
  });
});
