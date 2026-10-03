"use strict";
var geocoder;
let isSubmitting = false;

window.initMap = function (equipment_id = null) {
  geocoder = new google.maps.Geocoder();
  let input = document.getElementById('location'); //get input element

  if (input) {
    input.addEventListener('keyup', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        handleSearch();
      }
    });

    let searchBox = new google.maps.places.SearchBox(input);
    // Listen for place changes in the search box
    searchBox.addListener('places_changed', function () {
      const $sortSelect = $('#event_sort');
      $sortSelect.removeClass('d-none');

      if ($sortSelect.length && $sortSelect.find('option[value="close-by"]').length === 0) {
        $sortSelect.prepend(`
                    <option value="close-by" selected>
                    ${$sortSelect.data('close-text') || 'Distance: Closest first'}
                    </option>
                    <option value="distance-away">
                    ${$sortSelect.data('far-text') || 'Distance: Farthest first'}
                    </option>
                `);
      }

      const places = searchBox.getPlaces();
      if (places.length === 0) {
        return;
      }

      // Get the last selected place
      const place = places[places.length - 1];

      if (!place.geometry) {
        alert("Returned place contains no geometry");
        return;
      }

      const formattedAddress = decodeURIComponent(place.formatted_address);
      document.getElementById('location').value = formattedAddress;
      handleSearch();
    });
  }
}


function handleSearch() {
  const locationValue = $('#location').val().trim();
  const $sortSelect = $('#event_sort');
  $sortSelect.removeClass('d-none');

  if ($sortSelect.length && $sortSelect.find('option[value="close-by"]').length === 0) {
    $sortSelect.prepend(`
                <option value="close-by" selected>
                ${$sortSelect.data('close-text') || 'Distance: Closest first'}
                </option>
                <option value="distance-away">
                ${$sortSelect.data('far-text') || 'Distance: Farthest first'}
                </option>
            `);
  }
  // Check if the form is already submitting
  if (isSubmitting) {
    return;
  }

  if (!locationValue && !isSubmitting) {
    $('#location').val('');
    updateUrl();
    isSubmitting = true;
  } else if (locationValue && !isSubmitting) {
    document.getElementById('location').value = locationValue;
    updateUrl("location");
  }
}


/**
 * Function to update URL and submit form
 */
function updateUrl(data) {
  let newUrl = new URL(window.location);
  if (data === "location") {
    newUrl.searchParams.set('location', $('#location').val());
    newUrl.searchParams.set('sort', 'nearest');
  } else {
    newUrl.searchParams.delete('location');
    newUrl.searchParams.delete('sort');
  }
  window.history.replaceState({}, '', newUrl);

  // Submit the form and prevent multiple submissions
  if (!isSubmitting) {
    isSubmitting = true;
    $('#locationForm').submit();
  }
}


// Get the user's current location
function getCurrentLocation() {
  if (navigator.geolocation) {
    const $sortSelect = $('#event_sort');
    $sortSelect.removeClass('d-none');

    if ($sortSelect.length && $sortSelect.find('option[value="close-by"]').length === 0) {
      $sortSelect.prepend(`
    <option value="close-by" selected>
      ${$sortSelect.data('close-text') || 'Distance: Closest first'}
    </option>
    <option value="distance-away">
      ${$sortSelect.data('far-text') || 'Distance: Farthest first'}
    </option>
  `);
    }
    navigator.geolocation.getCurrentPosition(function (position) {

      const latLng = { lat: position.coords.latitude, lng: position.coords.longitude };
      geocodeLatLng(latLng);
    }, function (error) {
      alert("Unable to retrieve your location. Error: " + error.message);
    });
  } else {
    alert("Geolocation is not supported by this browser.");
  }
}
// Geocode latitude and longitude to get the address
function geocodeLatLng(latLng) {
  geocoder.geocode({ location: latLng }, function (results, status) {
    if (status === 'OK') {
      if (results[0]) {
        $('#location').val(results[0].formatted_address);
        updateUrl("location");
      } else {
        console.log('No results found');
      }
    } else {
      console.log('Geocoder failed due to: ' + status);
    }
  });
}
