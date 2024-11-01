jQuery(document).ready(function($) {
    "use strict";
    
    $('.color-picker').wpColorPicker();
    
    var startDate = $('#start-datepicker').datepicker({
        dateFormat: "d MM yy",
        altField  : "#start-datepicker-alt",
        altFormat : "yy-mm-dd",
        showOn    : "button",
        buttonText: WPCalendarsAdmin.datepickerButton
    }).on('change', function(){
        endDate.datepicker("option", "minDate", getDate(this));
    });

    var endDate = $('#end-datepicker').datepicker({
        dateFormat: "d MM yy",
        altField  : "#end-datepicker-alt",
        altFormat : "yy-mm-dd",
        showOn    : "button",
        buttonText: WPCalendarsAdmin.datepickerButton
    });
    
    function getDate(element) {
        var date;
        
        try {
            date = $.datepicker.parseDate("d MM yy", element.value);
        } catch( error ) {
            date = null;
        }
 
        return date;
    }
    
    try {
        var strStartDate = $('#start-datepicker-alt').val();
        var strEndDate   = $('#end-datepicker-alt').val();
    
        var arrDate = strStartDate.split('-');
        var date     = new Date(parseInt(arrDate[0]), parseInt(arrDate[1]) - 1, parseInt(arrDate[2]));
        
        $('#start-datepicker').datepicker("setDate", $.datepicker.parseDate("yy-mm-dd", strStartDate));
        
        if (strEndDate !== '') {
            $('#end-datepicker').datepicker("setDate", $.datepicker.parseDate("yy-mm-dd", strEndDate));
        }
        
        $("#end-datepicker").datepicker("option", "minDate", date);
    } catch(e) {}
    
    $('#all-day').on('click', function(){
        if ($(this).prop('checked')) {
            $('#start-time').removeClass('active');
            $('#end-time').removeClass('active');
        } else {
            $('#start-time').addClass('active');
            $('#end-time').addClass('active');
        }
    });
    
    $('#wpcalendars-new-category-button').on('click', function(){
        $('#wpcalendars-new-category').toggle(function(){
            $('#wpcalendars-new-category-name').focus();
        });
        
        return false;
    });
    
    $('#wpcalendars-add-category-button').on('click', function(){
        var t       = $(this);
        var text    = t.text();
        var name    = $('#wpcalendars-new-category-name').val();
        var bgcolor = $('#wpcalendars-new-category-bgcolor').val();
        
        if (name !== '' && bgcolor !== '') {
            t.text(WPCalendarsAdmin.ajaxSaving);

            var data = { 
                action: 'wpcalendars_add_category',
                nonce : WPCalendarsAdmin.nonce,
                data  : {
                    category_name: name,
                    bgcolor      : bgcolor
                }
            };

            $.post(WPCalendarsAdmin.ajaxurl, data, function(res){
                if (res.success) {
                    t.text(text);
                    $('#wpcalendars-new-category').hide();
                    $('#wpcalendars-new-category-name').val('');
                    $('#wpcalendars-new-category-bgcolor').val('');
                    
                    $('#wpcalendars-event-category-select').append('<option value="' + res.data.category_id + '" selected>' + name + '</option>');
                } else {
                    console.log(res);
                }
            }).fail(function(xhr, textStatus, e) {
                console.log(xhr.responseText);
            });
        } else {
            $('#wpcalendars-new-category-name').focus();
        }
    });
    
    $('#wpcalendars-new-venue-button').on('click', function(){
        $('#wpcalendars-new-venue').toggle(function(){
            $('#wpcalendars-new-venue-name').focus();
        });
        
        return false;
    });
    
    $('#wpcalendars-new-organizer-button').on('click', function(){
        $('#wpcalendars-new-organizer').toggle(function(){
            $('#wpcalendars-new-organizer-name').focus();
        });
        
        return false;
    });
    
    $('#wpcalendars-add-venue-button').on('click', function(){
        var t    = $(this);
        var text = t.text();
        var name = $('#wpcalendars-new-venue-name').val();
        
        if (name !== '') {
            t.text(WPCalendarsAdmin.ajaxSaving);

            var data = { 
                action: 'wpcalendars_add_venue',
                nonce : WPCalendarsAdmin.nonce,
                data  : name
            };

            $.post(WPCalendarsAdmin.ajaxurl, data, function(res){
                if (res.success) {
                    t.text(text);
                    $('#wpcalendars-new-venue').hide();
                    $('#wpcalendars-new-venue-name').val('');
                    $('#wpcalendars-event-venue-select').append('<option value="' + res.data.venue_id + '" selected>' + name + '</option>');
                } else {
                    console.log(res);
                }
            }).fail(function(xhr, textStatus, e) {
                console.log(xhr.responseText);
            });
        } else {
            $('#wpcalendars-new-venue-name').focus();
        }
    });
    
    $('#wpcalendars-add-organizer-button').on('click', function(){
        var t    = $(this);
        var text = t.text();
        var name = $('#wpcalendars-new-organizer-name').val();
        
        if (name !== '') {
            t.text(WPCalendarsAdmin.ajaxSaving);

            var data = { 
                action: 'wpcalendars_add_organizer',
                nonce : WPCalendarsAdmin.nonce,
                data  : name
            };

            $.post(WPCalendarsAdmin.ajaxurl, data, function(res){
                if (res.success) {
                    t.text(text);
                    $('#wpcalendars-new-organizer').hide();
                    $('#wpcalendars-new-organizer-name').val('');
                    $('#wpcalendars-event-organizer-select').append('<option value="' + res.data.organizer_id + '" selected>' + name + '</option>');
                } else {
                    console.log(res);
                }
            }).fail(function(xhr, textStatus, e) {
                console.log(xhr.responseText);
            });
        } else {
            $('#wpcalendars-new-organizer-name').focus();
        }
    });
});