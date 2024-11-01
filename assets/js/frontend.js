jQuery(document).ready(function($) {
    "use strict";
    
    $(document).on('click', '.wpcalendars-sample-menu', function(){
        var type = $(this).attr('data-type');
        $('.wpcalendars-sample-menu').removeClass('wpcalendars-sample-menu-current');
        $('.wpcalendars-sample-calendar').hide();
        $(this).addClass('wpcalendars-sample-menu-current');
        $('.' + type).show();
        return false;
    });
    
    $(document).on('click', '.wpcalendars-prevnext-nav-button', function(){
        var t = $(this);
        
        var container = $(this).parents('.wpcalendars-container');
        
        var block = $(this).parents('.wpcalendars-block');

        var data = { 
            action      : 'wpcalendars_apply_prevnext',
            nonce       : WPCalendars.nonce,
            start_date  : t.attr('data-startdate'),
            end_date    : t.attr('data-enddate'),
            calendar_id : container.find('.wpcalendars-calendarid').val(),
            categories  : container.find('.wpcalendars-categories').val(),
            tags        : container.find('.wpcalendars-tags').val(),
            venues      : container.find('.wpcalendars-venues').val(),
            organizers  : container.find('.wpcalendars-organizers').val()
        };
        
        $.magnificPopup.open({
            items: {
                src: '#wpcalendars-loading-panel',
                type: 'inline'
            },
            preloader: false,
            modal: true
        });
        
        $.post(WPCalendars.ajaxurl, data, function(res){
            if (res.success) {
                block.html(res.data.calendar);
            } else {
                console.log(res);
            }
            
            $.magnificPopup.close();
        }).fail(function(xhr, textStatus, e) {
            console.log(xhr.responseText);
        });
        
        return false;
    });
    
    $(document).on('click', '.wpcalendars-popup', function(){
        var t = $(this);
        
        var data = { 
            action   : 'wpcalendars_get_popup',
            nonce    : WPCalendars.nonce,
            page_id  : t.attr('data-page'),
            calendar : t.attr('data-calendar'),
            events   : t.attr('data-events')
        };
        
        $.magnificPopup.open({
            items: {
                src  : '#wpcalendars-loading-panel',
                type : 'inline'
            },
            preloader : false,
            modal     : true
        });
        
        $.post(WPCalendars.ajaxurl, data, function(res){
            if (res.success) {
                $('#wpcalendars-popup-panel').html(res.data.content);
                
                $.magnificPopup.close();
                
                $.magnificPopup.open({
                    items: {
                        src: '#wpcalendars-popup-panel',
                        type: 'inline'
                    },
                    showCloseBtn: true
                });
            } else {
                console.log(res);
            }
        }).fail(function(xhr, textStatus, e) {
            console.log(xhr.responseText);
        });
        
        return false;
    });
    
    $(document).on('click', '.wpcalendars-tooltip-item-nav button', function(){
        var tItem = $(this).parents('.wpcalendars-tooltip-item');
        var tContainer = $(this).parents('.wpcalendars-tooltip-container');
        tContainer.find('.wpcalendars-tooltip-details').hide();
        tItem.find('.wpcalendars-tooltip-details').show();
    });
    
    $(document).on('mouseover', '.wpcalendars-tooltip', function(){
        var t = $(this);
        
        if ($(this).is('.tooltipstered')) {
            // do nothing
        } else {
            $(this).tooltipster({
                contentCloning : true,
                contentAsHTML  : true,
                theme          : ['tooltipster-' + t.attr('data-theme'), 'tooltipster-wpcalendars'],
                trigger        : t.attr('data-trigger'),
                interactive    : true,
                minWidth       : 300,
                maxWidth       : 400,
                content        : '<div class="wpcalendars-tooltip-loading">' + WPCalendars.loading + '</div>',
                functionBefore: function(instance, helper) {
                    var $origin = $(helper.origin);
                    if ($origin.data('loaded') !== true) {
                        var data = { 
                            action      : 'wpcalendars_get_tooltip',
                            nonce       : WPCalendars.nonce,
                            page_id     : t.attr('data-page'),
                            calendar    : t.attr('data-calendar'),
                            events      : t.attr('data-events')
                        };
        
                        $.post(WPCalendars.ajaxurl, data, function(res){
                            if (res.success) {
                                instance.content(res.data.tooltip);
                                $origin.data('loaded', true);
                            } else {
                                console.log(res);
                            }
                        }).fail(function(xhr, textStatus, e) {
                            console.log(xhr.responseText);
                        });
                    }
                }
            });
            
            $(this).mouseover();
        }
    });
    
    $(document).on('click', '.wpcalendars-detail-tooltip-link', function(){
        return false;
    });
    
    $(document).on('mouseenter', '.wpcalendars-detail-tooltip', function(){
        var t = $(this);
        
        $(t.find('.wpcalendars-detail-tooltip-link')).each(function(){
            if ($(this).is('.tooltipstered')) {
                // do nothing
            } else {
                $(this).tooltipster({
                    contentCloning : true,
                    contentAsHTML  : true,
                    theme          : ['tooltipster-punk', 'tooltipster-wpcalendars'],
                    interactive    : true,
                    minWidth       : 500,
                    maxWidth       : 500,
                    zIndex         : 1099999,
                    trigger        : 'click',
                    content        : $(this).parents('.wpcalendars-detail-tooltip').find('.wpcalendars-detail-tooltip-content').html()
                });
            }
        });
    });
    
    $(document).on('click', '.wpcalendars-more-less-detail a', function(){
        var target = $(this).attr('href');
        $(this).parents('.wpcalendars-detail-content-wrapper').hide();
        $(target).show();
        return false;
    });
    
    $('.wpcalendars-close-button').on('click', function(){
        $.magnificPopup.close();
    });
});