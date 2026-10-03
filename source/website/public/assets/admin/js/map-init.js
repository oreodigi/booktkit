
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
      setLatLng(latitude, longitude)

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
