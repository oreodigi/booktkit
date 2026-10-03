var map;
var geocoder;
var currentMarker = null;

function initMap() {
  map = new google.maps.Map(document.getElementById('map'), {
    zoom: 18
  });

  geocoder = new google.maps.Geocoder();

  // Set the initial address to geocode and center the map
  geocodeAddress(address);//get address from edit page

  var input = document.getElementById('search-address_' + defaultLang);
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

function geocodeAddress(address) {
  geocoder.geocode({ 'address': address }, function (results, status) {
    if (status === 'OK') {
      map.setCenter(results[0].geometry.location);
      setMarker(results[0].geometry.location, address);
    } else {
      console.log('Geocode was not successful for the following reason: ' + status);
    }
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
          $('#search-address_' + defaultLang).val(results[0].formatted_address);
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
    var input = $('#search-address_' + defaultLang).val();
    $('#search-address_' + defaultLang).val('');

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
