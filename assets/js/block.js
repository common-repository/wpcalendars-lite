(function(wp) {
    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    
    var InspectorControls = wp.editor.InspectorControls;
    var SelectControl     = wp.components.SelectControl;
    var PanelBody         = wp.components.PanelBody;
    var ServerSideRender  = wp.components.ServerSideRender;
    
    wp.blocks.registerBlockType(
        'wpcalendars/wpcalendars', {
            title: __( 'WPCalendars', 'wpcalendars' ),
            description: __( 'Display events calendar.', 'wpcalendars' ),
            icon: 'calendar-alt',
            category: 'widgets',
            attributes: {
                id: {
                    type: 'string'
                }
            },
            edit: function( props ) {
                return [
                    el(
                        InspectorControls,
                        {},
                        el(
                            PanelBody,
                            {
                                'title': __( 'Calendar Settings', 'wpcalendars' )
                            },
                            el(
                                SelectControl,
                                {
                                    label: __('Choose The Calendar', 'wpcalendars'),
                                    value: props.attributes.id ? parseInt(props.attributes.id) : '',
                                    onChange: function(value){
                                        props.setAttributes({
                                            id: value
                                        });
                                    },
                                    options: wpcalendars.calendars
                                }
                            )
                        )
                    ),
                    el(ServerSideRender, {
                        block: "wpcalendars/wpcalendars",
                        attributes: props.attributes
                    })
                ];
            },
            save: function() {
                return null;
            }
        }
    );

})(
    window.wp
);