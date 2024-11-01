<?php
return array(
    'general' => array(
        'default_event_category' => array(
            'type'          => 'text',
            'default_value' => ''
        ),
        'weekday' => array(
            'type'          => 'multiple',
            'default_value' => array( 1, 2, 3, 4, 5 ),
            'options'       => array( 0, 1, 2, 3, 4, 5, 6 )
        ),
        'date_format' => array(
            'type'          => 'select',
            'default_value' => 'medium',
            'options'       => array_keys( wpcalendars_get_date_format_options() )
        ),
        'time_format' => array(
            'type'          => 'select',
            'default_value' => 'medium',
            'options'       => array_keys( wpcalendars_get_time_format_options() )
        ),
        'uninstall_remove_data' => array(
            'type'          => 'checkbox',
            'default_value' => 'N'
        ),
        'map_provider' => array(
            'type'          => 'select',
            'default_value' => 'google',
            'options'       => array( 'google' )
        ),
        'map_zoom' => array(
            'type'          => 'text',
            'default_value' => 17
        ),
    ),
    'api' => array(
        'google_maps_apikey' => array(
            'type'          => 'text',
            'default_value' => ''
        )
    )
);
