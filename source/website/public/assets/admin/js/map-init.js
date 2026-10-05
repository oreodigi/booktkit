
"use strict";
var map;
var geocoder;
var currentMarker = null;
var iconUrl = null;

function initMap() {

  map = new google.maps.Map(document.getElementById('map'), {
    center: {
      lat: 31.7311295,
      lng: 35.0163795
    },
    zoom: 12
  });

  geocoder = new google.maps.Geocoder();

  var input = document.getElementById('search-address');
  var searchBox = new google.maps.places.SearchBox(input);

  map.addListener('bounds_changed', function () {
    searchBox.setBounds(map.getBounds());
  });

  searchBox.addListener('places_changed', function () {
    var places = searchBox.getPlaces();
    if (places.length === 0) {
      return;
    }

    if (currentMarker) {
      currentMarker.setMap(null);
    }

    var bounds = new google.maps.LatLngBounds();
    places.forEach(function (place) {
      if (!place.geometry) {
        console.log("Returned place contains no geometry");
        return;
      }

      var latitude = place.geometry.location.lat();
      var longitude = place.geometry.location.lng();
      setLatLng(latitude, longitude);
      applyEventAddress(place);

      var icon = {
        url: place.icon,
        size: new google.maps.Size(71, 71),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(17, 34),
        scaledSize: new google.maps.Size(25, 25)
      };

      currentMarker = new google.maps.Marker({
        map: map,
        icon: icon,
        title: place.name,
        position: place.geometry.location
      });

      if (place.geometry.viewport) {
        bounds.union(place.geometry.viewport);
      } else {
        bounds.extend(place.geometry.location);
      }
    });
    map.fitBounds(bounds);

    // Set zoom level to 18 after fitting bounds
    map.setZoom(18);
  });

  // Add click event listener to the map
  google.maps.event.addListener(map, 'click', function (event) {
    var clickedLocation = event.latLng;

    var latitude = clickedLocation.lat();
    var longitude = clickedLocation.lng();

    setLatLng(latitude, longitude)
    geocodeLatLng(geocoder, map, clickedLocation);
  });
}

function geocodeLatLng(geocoder, map, latLng) {

  geocoder.geocode({
    location: latLng
  }, function (results, status) {
    if (status === 'OK') {
      if (results[0]) {
        var placeName = getPlaceName(results);
        if (placeName) {
          $('#search-address').val(results[0].formatted_address);
          applyEventAddress(results[0]);
          setMarker(latLng, placeName);
        } else {
          console.log('No place name found');
        }
      } else {
        console.log('No results found');
      }
    } else {
      console.log('Geocoder failed due to: ' + status);
    }
  });
}

function getPlaceName(results) {
  for (var i = 0; i < results.length; i++) {
    for (var j = 0; j < results[i].address_components.length; j++) {
      var types = results[i].address_components[j].types;
      if (types.indexOf('locality') !== -1 || types.indexOf('sublocality') !== -1 || types.indexOf(
        'neighborhood') !== -1) {
        return results[i].address_components[j].long_name;
      }
    }
  }
  return null;
}

function setMarker(location, title) {
  if (currentMarker) {
    currentMarker.setMap(null);
  }
  currentMarker = new google.maps.Marker({
    position: location,
    map: map,
    title: title
  });
}

$(document).ready(function () {
  $('#search-button').click(function () {
    var input = $('#search-address').val();
    $('#search-address').val('');

    var request = {
      query: input,
      fields: ['name', 'geometry']
    };

    var service = new google.maps.places.PlacesService(map);
    service.findPlaceFromQuery(request, function (results, status) {
      if (status === google.maps.places.PlacesServiceStatus.OK) {
        var bounds = new google.maps.LatLngBounds();
        results.forEach(function (place) {
          if (place.geometry.viewport) {
            bounds.union(place.geometry.viewport);
          } else {
            bounds.extend(place.geometry.location);
          }
        });
        map.fitBounds(bounds);
      } else {
        console.error('Search failed with status: ' + status);
      }
    });
  });
});

function getCurrentLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function (position) {
      var latitude = position.coords.latitude;
      var longitude = position.coords.longitude;

      var latlng = { lat: latitude, lng: longitude };

      // Update the geocode based on the latitude and longitude
      geocodeLatLng(geocoder, map, latlng);

      // Update latitude and longitude fields
      setLatLng(latitude, longitude)

      // If the marker already exists, move it, otherwise create a new one
      if (currentMarker) {
        currentMarker.setPosition(latlng); // Update position of existing marker
      } else {
        // Create the marker if it doesn't exist
        var icon = {
          url: iconUrl, // URL of the custom icon image
          size: new google.maps.Size(71, 71), // Size of the icon (change as needed)
          origin: new google.maps.Point(0, 0), // The origin of the icon (top-left corner)
          anchor: new google.maps.Point(17, 34), // Anchor point of the icon (bottom-center)
          scaledSize: new google.maps.Size(25, 25) // Scaled size (adjust as needed)
        };

        currentMarker = new google.maps.Marker({
          position: latlng,
          map: map,
          icon: icon, // Custom icon
          title: 'Custom Location' // Marker title (optional)
        });
      }

      // Optionally, you can zoom the map to the current location
      map.setCenter(latlng);
      map.setZoom(18);

    }, function (error) {
      alert("Unable to retrieve your location. Error: " + error.message);
    });
  } else {
    alert("Geolocation is not supported by this browser.");
  }
}

function setLatLng(latitude, longitude) {
  // Update all latitude inputs
  document.querySelectorAll('.latitude').forEach(function (input) {
    input.value = latitude;
  });

  // Update all longitude inputs
  document.querySelectorAll('.longitude').forEach(function (input) {
    input.value = longitude;
  });
}

/**
 * Populate the event venue fields from a Google Place/Geocoder result.
 * Country/state/city selects are dependent AJAX dropdowns, so each level
 * waits for the next dropdown to finish loading before selecting its value.
 */
function applyEventAddress(place) {
  if (!place || !place.address_components) return;
  var parts = {};
  place.address_components.forEach(function (component) {
    (component.types || []).forEach(function (type) {
      parts[type] = component.long_name;
    });
  });
  var country = parts.country || '';
  var state = parts.administrative_area_level_1 || '';
  var city = parts.locality || parts.administrative_area_level_2 || parts.sublocality_level_1 || parts.sublocality || '';
  var pin = parts.postal_code || '';
  var formatted = place.formatted_address || (document.getElementById('search-address') || {}).value || '';

  // Keep the visible address in sync with the Google selection.
  document.querySelectorAll('[name$="_address"]').forEach(function (input) {
    if (formatted) input.value = formatted;
  });
  document.querySelectorAll('[name$="_zip_code"]').forEach(function (input) {
    input.value = pin;
    $(input).trigger('change');
  });

  function choose($select, label) {
    if (!$select.length || !label) return false;
    var wanted = label.trim().toLowerCase();
    var found = false;
    $select.find('option').each(function () {
      var text = ($(this).text() || '').trim().toLowerCase();
      if (text === wanted || text.indexOf(wanted) !== -1 || wanted.indexOf(text) !== -1) {
        $select.val($(this).val());
        found = true;
        return false;
      }
    });
    if (found) $select.trigger('change');
    return found;
  }

  $('.countryDropdown').each(function () {
    var $country = $(this);
    if (!choose($country, country)) return;
    var $scope = $country.closest('.version-body');
    var $state = $scope.find('.stateDropdown').first();
    var $city = $scope.find('.cityDropdown').first();
    var triesState = 0;
    var stateTimer = setInterval(function () {
      triesState++;
      if (choose($state, state) || triesState >= 30) {
        clearInterval(stateTimer);
        if (!$state.length) {
          choose($city, city);
          return;
        }
        var triesCity = 0;
        var cityTimer = setInterval(function () {
          triesCity++;
          if (choose($city, city) || triesCity >= 30) clearInterval(cityTimer);
        }, 150);
      }
    }, 150);
  });

  // Notify autosave/other form integrations after Google fills the venue.
  $('#eventForm').trigger('change');
}
