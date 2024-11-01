jQuery(document).ready(function($) {
    "use strict";
    
    $('#the-list.wpcalendars-list-table').on('click', '.delete', function(){
        if (confirm(WPCalendarsAdmin.warnDelete)) {
            return true;
        }
        
        return false;
    });
});