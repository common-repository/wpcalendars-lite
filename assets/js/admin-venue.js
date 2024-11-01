jQuery(document).ready(function($) {
    "use strict";
    
    $('.wpcalendars-close-button').on('click', function(){
        $.magnificPopup.close();
    });
    
    $('#country').on('change', function(){
        var data = { 
            action: 'wpcalendars_get_states',
            nonce : WPCalendarsAdmin.nonce,
            data  : {
                country: $(this).val()
            }
        };
            
        $.post(WPCalendarsAdmin.ajaxurl, data, function(res){
            if (res.success) {
                $('#states-container').html(res.data.states);
            } else {
                console.log(res);
            }
        }).fail(function(xhr, textStatus, e) {
            console.log(xhr.responseText);
        });
    });
});