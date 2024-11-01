var map;
var service;
var infowindow;
var infowindowContent;
var marker;

function initMap() {
    var latitude = -33.8688;
    var longitude = 151.2195;
        
    map = new google.maps.Map(document.getElementById('map'), {
        center: {lat: latitude, lng: longitude},
        zoom: parseInt(WPCalendarsAdmin.mapZoom)
    });

    var input = document.getElementById('venue-search');

    var autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo('bounds', map);

    // Specify just the place data fields that you need.
    autocomplete.setFields(['place_id', 'geometry', 'name', 'formatted_address']);

    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    infowindow = new google.maps.InfoWindow();
    infowindowContent = document.getElementById('infowindow-content');
    infowindow.setContent(infowindowContent);

    marker = new google.maps.Marker({map: map});

    marker.addListener('click', function () {
        infowindow.open(map, marker);
    });
    
    service = new google.maps.places.PlacesService(map);
    
    autocomplete.addListener('place_changed', function () {
        infowindow.close();

        var place = autocomplete.getPlace();

        if (!place.geometry) {
            return;
        }

        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(parseInt(WPCalendarsAdmin.mapZoom));
        }

        // Set the position of the marker using the place ID and location.
        marker.setPlace({
            placeId: place.place_id,
            location: place.geometry.location
        });

        marker.setVisible(true);
        
        document.getElementById('latitude').value = place.geometry.location.lat();
        document.getElementById('longitude').value = place.geometry.location.lng();
        document.getElementById('place_id').value = place.place_id;

        infowindowContent.children['place-name'].textContent = place.name;
        infowindowContent.children['place-address'].textContent = place.formatted_address;
        infowindow.open(map, marker);
    });
}


jQuery(document).ready(function ($) {
    "use strict";
    
    try {
        initMap();
    } catch(e) {}
    
    $('#wpcalendars-get-location-button').on('click', function () {
        var title   = $('#title').val();
        var address = $('#address').val();
        var country = $('#country option:selected').text();
        var state   = $('#state option:selected').text();
        var city    = $('#city').val();
        
        if (state === '') {
            state = $('#state').val();
        }
        
        var str_query = title;
        
        if (address !== '') {
            str_query += ', ' + address;
        }
        
        if (city !== '') {
            str_query += ', ' + city;
        }
        
        if (state !== '') {
            str_query += ', ' + state;
        }
        
        if (country !== '') {
            str_query += ', ' + country;
        }
        
        var request = {
            query: str_query,
            fields: ['name', 'formatted_address', 'place_id', 'geometry']
        };

        try {
            service.findPlaceFromQuery(request, function(places, status) {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    marker.setPlace({
                        placeId: places[0].place_id,
                        location: places[0].geometry.location
                    });

                    marker.setVisible(true);

                    $('#latitude').val(places[0].geometry.location.lat());
                    $('#longitude').val(places[0].geometry.location.lng());
                    $('#place_id').val(places[0].place_id);

                    infowindowContent.children['place-name'].textContent = places[0].name;
                    infowindowContent.children['place-address'].textContent = places[0].formatted_address;

                    map.setCenter(places[0].geometry.location);
                    infowindow.open(map, marker);
                }
            });
        } catch (e) {}
        
        $.magnificPopup.open({
            items: {
                src: '#wpcalendars-location-map',
                type: 'inline'
            },
            preloader: false,
            modal: true
        });
    });

    $('#wpcalendars-update-location-button').on('click', function () {
        var latitude = document.getElementById('latitude').value;
        var longitude = document.getElementById('longitude').value;
        $('#wpcalendars-location-static-map').html('<img src="https://maps.googleapis.com/maps/api/staticmap?center=' + latitude + ',' + longitude + '&markers=color:red%7Clabel:S%7C' + latitude + ',' + longitude + '&zoom=' + WPCalendarsAdmin.mapZoom + '&size=640x400&key=' + WPCalendarsAdmin.googleMapsApikey + '" />');
        $('#wpcalendars-remove-location-button').show();
        $.magnificPopup.close();
    });
    
    $('#wpcalendars-remove-location-button').on('click', function(){
        $('#latitude').val('');
        $('#longitude').val('');
        $('#wpcalendars-location-static-map').html('');
        $(this).hide();
    });
});
