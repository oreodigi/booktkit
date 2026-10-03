$(document).ready(function () {
  'use strict';

  function processResults(data) {
    return {
      results: data.results.map(function (item) {
        return { text: item.name, id: item.id };
      }),
      pagination: { more: data.more }
    };
  }

  function initSelect2Country($select) {
    $select.select2({
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
            lang: $select.attr('data-lang')
          };
        },
        processResults: processResults,
        cache: true
      }
    });
  }

  function initSelect2State($select, $countrySelect) {
    $select.select2({
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
            country: $countrySelect.val() || '',
            lang: $select.attr('data-lang')
          };
        },
        processResults: processResults,
        cache: true
      }
    });
  }

  function initSelect2City($select, $stateSelect) {
    $select.select2({
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
            state: $stateSelect.val() || '',
            lang: $select.attr('data-lang')
          };
        },
        processResults: processResults,
        cache: true
      }
    });
  }

  // Country change
  $('body').on('change', '.country_select', function () {
    let $wrapper = $(this).closest('.version-body');
    let $stateSelect = $wrapper.find('.state_select');
    let $citySelect = $wrapper.find('.city_select');
    let country_id = $(this).val();

    $('.request-loader').addClass('show');

    // Reset state & city
    $stateSelect.html('<option value="">Select State</option>').val('').trigger('change.select2');
    $citySelect.html('<option value="">Select City</option>').val('').trigger('change.select2');

    $.ajax({
      url: getStateUrl,
      type: 'get',
      data: { country_id: country_id },
      success: function (data) {
        $('.request-loader').removeClass('show');

        if (isActiveState == 0) {
          if (data.cities == false) {
            $wrapper.find('.city_div').hide();
            return;
          }
          if (data.cities == true) {
            $wrapper.find('.city_div').show();
            initSelect2City($citySelect, $stateSelect);
          }
          return;
        }

        if (data.states == false) {
          $wrapper.find('.state_div').hide();
          return;
        }
        if (data.states == true) {
          $wrapper.find('.state_div').show();
          initSelect2State($stateSelect, $(this));
        }
      }.bind(this)
    });
  });

  // State change
  $('body').on('change', '.state_select', function () {
    let $wrapper = $(this).closest('.version-body');
    let $citySelect = $wrapper.find('.city_select');
    let state_id = $(this).val();

    // Reset city
    $citySelect.html('<option value="">Select City</option>').val('').trigger('change.select2');

    if (typeof getCityUrl == 'undefined' || !state_id) {
      $wrapper.find('.city_div').hide();
      return;
    }

    $('.request-loader').addClass('show');

    $.ajax({
      url: getCityUrl,
      type: 'get',
      data: { state_id: state_id },
      success: function (data) {
        $('.request-loader').removeClass('show');

        if (data.cities == 'no_data_found') {
          $wrapper.find('.city_div').hide();
          return;
        }
        if (data.cities.length > 0) {
          $wrapper.find('.city_div').show();
          initSelect2City($citySelect, $(this));
        }
      }.bind(this)
    });
  });

  // Initial select2 bindings
  $('.countryDropdown').each(function () {
    initSelect2Country($(this));
  });

  $('.stateDropdown').each(function () {
    let $wrapper = $(this).closest('.version-body');
    initSelect2State($(this), $wrapper.find('.countryDropdown'));
  });

  $('.cityDropdown').each(function () {
    let $wrapper = $(this).closest('.version-body');
    initSelect2City($(this), $wrapper.find('.stateDropdown'));
  });
});
