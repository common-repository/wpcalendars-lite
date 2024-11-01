jQuery(document).ready(function($) {
    "use strict";
    
    var updateQueryString = function (key, value, url) {
        if (!url)
            url = window.location.href;

        var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
                hash;

        if (re.test(url)) {
            if (typeof value !== 'undefined' && value !== null)
                return url.replace(re, '$1' + key + "=" + value + '$2$3');
            else {
                hash = url.split('#');
                url = hash[0].replace(re, '$1$3').replace(/(&|\?)$/, '');
                if (typeof hash[1] !== 'undefined' && hash[1] !== null)
                    url += '#' + hash[1];
                return url;
            }
        } else {
            if (typeof value !== 'undefined' && value !== null) {
                var separator = url.indexOf('?') !== -1 ? '&' : '?';
                hash = url.split('#');
                url = hash[0] + separator + key + '=' + value;
                if (typeof hash[1] !== 'undefined' && hash[1] !== null)
                    url += '#' + hash[1];
                return url;
            }
            else
                return url;
        }
    };
    
    var isSaved = function(){
        var isSave = $('#wpcalendars-save-state').val();
        if (isSave === 'Y') {
            return true;
        } else {
            return false;
        }
    };
    
    $('.wpcalendars-menu button').on('click', function(){
        var t       = $(this);
        var section = t.data('section');
        var sidebar = $('#wpcalendars-sidebar-' + section);
        var btn     = $('.wpcalendars-section-' + section + '-button');
        
        if (!btn.hasClass('active')) {
            $('.wpcalendars-menu').find('button').removeClass('active');
            
            if (section === 'setup') {
                $('.wpcalendars-sidebar-wrap').removeClass('active');
                $('.wpcalendars-preview-wrap').removeClass('active');
                $('.wpcalendars-setup-wrap').addClass('active');
            } else {
                $('.wpcalendars-setup-wrap').removeClass('active');
                $('.wpcalendars-sidebar-wrap').removeClass('active');
                $('.wpcalendars-preview-wrap').addClass('active');
                sidebar.addClass('active');
            }
            
            btn.addClass('active');
            history.replaceState({}, null, updateQueryString('section', section));
        }
        
        return false;
    });
    
    $('.wpcalendars-calendar-type-select').on('click', function(){
        var t                = $(this);
        var calendarName     = $('#wpcalendars-setup-name').val();
        var oldCalendarType  = $('#wpcalendars-calendar-type').val();
        var calendarType     = t.data('calendar-type');
        var calendarTypeName = t.data('calendar-type-name');
        
        if (oldCalendarType === ''){
            $('#wpcalendars-calendar-name').val(calendarName);
            $('#wpcalendars-calendar-type').val(calendarType);
            $('#wpcalendars-calendar-type-name').val(calendarTypeName);
            $('#wpcalendars-form').submit();
        } else {
            $('#wpcalendars-new-calendar-name').val(calendarName);
            $('#wpcalendars-new-calendar-type').val(calendarType);
            $('#wpcalendars-new-calendar-type-name').val(calendarTypeName);
            
            $.magnificPopup.open({
                items: {
                    src: $('#wpcalendars-builder-change-type-panel'),
                    type: 'inline'
                },
                preloader: false,
                modal: true
            });
        }
        
        return false;
    });
    
    $('#wpcalendars-setup-name').on('keyup', function(){
        var t = $(this);
        $('.wpcalendars-calendar-name').text(t.val());
        $('#wpcalendars-calendar-name').val(t.val());
    });
    
    $('#wpcalendars-get-shortcode').on('click', function(){
        $.magnificPopup.open({
            items: {
                src: $('#wpcalendars-builder-shortcode-panel'),
                type: 'inline'
            },
            preloader: false,
            modal: true
        });
        return false;
    });
    
    $('#wpcalendars-builder-shortcode-panel-btn-close').on('click', function(){
        $.magnificPopup.close();
        return false;
    });
    
    $('.wpcalendars-select').select2({
        minimumResultsForSearch: Infinity,
        width: "100%"
    });
    
    $('#wpcalendars-display-selection').on('change', function(){
        var selected = $(this).val();
        
        if (selected === 'custom') {
            $('#wpcalendars-display-custom').show();
            $('#wpcalendars-display-non-custom').hide();
        } else {
            $('#wpcalendars-display-custom').hide();
            $('#wpcalendars-display-non-custom').show();
        }
    });
    
    $('#wpcalendars-event-nav-selection').on('change', function(){
        var selected = $(this).val();
        
        if (selected === 'tooltip') {
            $('#wpcalendars-daily-event-nav-tooltip').show();
        } else {
            $('#wpcalendars-daily-event-nav-tooltip').hide();
        }
    });
    
    $(document).on('change', '.wpcalendars-select-multiple', function(){
        var s = "";
        var t = $(this);
        
        $("option:selected", t).each(function() {
            s += (s.length > 0 ? "," : "") + $(this).val();
        });
        
        $(t).siblings().val(s);
    });
    
    var updatePreview = function(){
        $.magnificPopup.open({
            items: {
                src: '#wpcalendars-loading-panel',
                type: 'inline'
            },
            preloader: false,
            modal: true
        });
        
        var data = { 
            action: 'wpcalendars_update_preview',
            nonce : WPCalendarsAdmin.nonce,
            data  : JSON.stringify($('#wpcalendars-form').serializeArray()) 
        };

        $.post(WPCalendarsAdmin.ajaxurl, data, function(res){
            if (res.success) {
                $('.wpcalendars-preview').html(res.data.calendar);
                $('#wpcalendars-rev-calendar-id').val(res.data.rev_calendar_id);
                $('#wpcalendars-save-state').val('N');
            } else {
                console.log(res);
            }

            $.magnificPopup.close();
        }).fail(function(xhr, textStatus, e) {
            console.log(xhr.responseText);
        });
    };
    
    $(document).on('change', '.wpcalendars-form-input select', function(){
        updatePreview();
    });
    
    var timeout = null;
    
    $(document).on('keyup', '.wpcalendars-form-input-text', function(){
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            updatePreview();
        }, 3000);
    });
    
    $(document).on('click', '.wpcalendars-form-input-checkbox', function(){
        updatePreview();
    });
    
    $('#wpcalendars-save').on('click', function(){
        var t    = $(this);
        var text = t.text();
        
        t.text(WPCalendarsAdmin.ajaxSaving);
        
        var data = { 
            action: 'wpcalendars_save_calendar',
            nonce : WPCalendarsAdmin.nonce,
            data  : JSON.stringify($('#wpcalendars-form').serializeArray()) 
        };
        
        $.post(WPCalendarsAdmin.ajaxurl, data, function(res){
            if (res.success) {
                t.text(text);
                $('#wpcalendars-save-state').val('Y');
                $('#wpcalendars-rev-calendar-id').val('');
            } else {
                console.log(res);
            }
        }).fail(function(xhr, textStatus, e) {
            console.log(xhr.responseText);
        });
        
        return false;
    });
    
    $('.wpcalendars-toolbar .wpcalendars-toolbar-center').on('click', function(){
        $('.wpcalendars-sidebar-wrap').removeClass('active');
        $('.wpcalendars-preview-wrap').removeClass('active');
        $('.wpcalendars-menu').find('button').removeClass('active');
        $('.wpcalendars-setup-wrap').addClass('active');
        $('.wpcalendars-section-setup-button').addClass('active');
        $('#wpcalendars-setup-name').focus();
        history.replaceState({}, null, updateQueryString('section', 'setup'));
    });
    
    $('#wpcalendars-exit').on('click', function(){
        if (isSaved()) {
            window.location.href = WPCalendarsAdmin.builderExitUrl;
        } else {
            $.magnificPopup.open({
                items: {
                    src: $('#wpcalendars-builder-exit-panel'),
                    type: 'inline'
                },
                preloader: false,
                modal: true
            });
        }
        
        return false;
    });
    
    $('#wpcalendars-builder-exit-panel-btn-cancel').on('click', function(){
        $.magnificPopup.close();
        return false;
    });
    
    $('#wpcalendars-builder-exit-panel-btn-exit').on('click', function(){
        window.location.href = WPCalendarsAdmin.builderExitUrl;
        return false;
    });
    
    $('#wpcalendars-builder-change-type-panel-btn-no').on('click', function(){
        $.magnificPopup.close();
        return false;
    });
    
    $('#wpcalendars-builder-change-type-panel-btn-yes').on('click', function(){
        var calendarName     = $('#wpcalendars-new-calendar-name').val();
        var calendarType     = $('#wpcalendars-new-calendar-type').val();
        var calendarTypeName = $('#wpcalendars-new-calendar-type-name').val();
        
        $('#wpcalendars-calendar-name').val(calendarName);
        $('#wpcalendars-calendar-type').val(calendarType);
        $('#wpcalendars-calendar-type-name').val(calendarTypeName);
        
        $.magnificPopup.close();
        $('#wpcalendars-form').submit();
        
        return false;
    });

    $('.wpcalendars-close-button').on('click', function(){
        $.magnificPopup.close();
    });
});