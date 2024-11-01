jQuery(document).ready(function($) {
    "use strict";
    
    $('.color-picker').wpColorPicker();
    
    $('#the-list.wpcalendars-list-table').on('click', '.delete', function(){
        if (confirm(WPCalendarsAdmin.warnDelete)) {
            return true;
        }
        
        return false;
    });
});