<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Lite_Builder {
    
    private static $_instance = NULL;
    
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'wpcalendars_builder_monthly_calendar_settings_navigation',         array( $this, 'monthly_calendar_navigation_settings' ) );
        add_action( 'wpcalendars_builder_monthly_calendar_settings_events',             array( $this, 'events_settings' ) );
        add_action( 'wpcalendars_builder_multiple_months_calendar_settings_navigation', array( $this, 'multiple_months_calendar_navigation_settings' ) );
        add_action( 'wpcalendars_builder_multiple_months_calendar_settings_events',     array( $this, 'events_settings' ) );
        add_action( 'wpcalendars_builder_weekly_calendar_settings_navigation',          array( $this, 'weekly_calendar_navigation_settings' ) );
        add_action( 'wpcalendars_builder_weekly_calendar_settings_events',              array( $this, 'events_settings' ) );
        add_action( 'wpcalendars_builder_daily_calendar_settings_navigation',           array( $this, 'daily_calendar_navigation_settings' ) );
        add_action( 'wpcalendars_builder_daily_calendar_settings_events',               array( $this, 'events_settings' ) );
        add_action( 'wpcalendars_builder_list_calendar_settings_navigation',            array( $this, 'list_calendar_navigation_settings' ) );
        add_action( 'wpcalendars_builder_list_calendar_settings_events',                array( $this, 'events_settings' ) );
    }
    
    /**
     * retrieve singleton class instance
     * @return instance reference to plugin
     */
    public static function instance() {
        if ( NULL === self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Render navigation settings for daily calendar
     * @param type $settings
     */
    public function daily_calendar_navigation_settings( $settings ) {
        $this->date_picker_navigation();
        $this->filter_navigation();
        $this->download_navigation();
        $this->gcal_navigation();
        $this->ical_subscription_navigation();
    }
    
    /**
     * Render navigation settings for weekly calendar
     * @param type $settings
     */
    public function weekly_calendar_navigation_settings( $settings ) {
        $this->week_picker_navigation();
        $this->filter_navigation();
        $this->download_navigation();
        $this->gcal_navigation();
        $this->ical_subscription_navigation();
    }
    
    /**
     * Render navigation settings for monthly calendar
     * @param type $settings
     */
    public function monthly_calendar_navigation_settings( $settings ) {
        $this->month_picker_navigation();
        $this->filter_navigation();
        $this->download_navigation();
        $this->gcal_navigation();
        $this->ical_subscription_navigation();
    }
    
    /**
     * Render navigation settings for multiple months calendar
     * @param type $settings
     */
    public function multiple_months_calendar_navigation_settings( $settings ) {
        $this->month_picker_navigation();
        $this->filter_navigation();
        $this->download_navigation();
        $this->gcal_navigation();
        $this->ical_subscription_navigation();
    }
    
    /**
     * Render navigation settings for list calendar
     * @param type $settings
     */
    public function list_calendar_navigation_settings( $settings ) {
        $this->month_picker_navigation();
        $this->filter_navigation();
        $this->download_navigation();
        $this->gcal_navigation();
        $this->ical_subscription_navigation();
    }
    
    /**
     * Render month picker navigation
     */
    private function month_picker_navigation() {
        printf( '<h3>%s</h3>', esc_html__( 'Month Picker Navigation', 'wpcalendars' ) );
        printf( '<p>%s</p>', esc_html__( "We're sorry, this feature is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.", 'wpcalendars' ) );
        printf( '<div class="wpcalendars-button"><a href="%s" target="_blank">%s</a></div>', '//wpcalendars.com/pricing/', esc_html__( 'Upgrade to Pro', 'wpcalendars' ) );
    }
    
    /**
     * Render week picker navigation
     */
    private function week_picker_navigation() {
        printf( '<h3>%s</h3>', esc_html__( 'Week Picker Navigation', 'wpcalendars' ) );
        printf( '<p>%s</p>', esc_html__( "We're sorry, this feature is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.", 'wpcalendars' ) );
        printf( '<div class="wpcalendars-button"><a href="%s" target="_blank">%s</a></div>', '//wpcalendars.com/pricing/', esc_html__( 'Upgrade to Pro', 'wpcalendars' ) );
    }
    
    /**
     * Render date picker navigation
     */
    private function date_picker_navigation() {
        printf( '<h3>%s</h3>', esc_html__( 'Date Picker Navigation', 'wpcalendars' ) );
        printf( '<p>%s</p>', esc_html__( "We're sorry, this feature is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.", 'wpcalendars' ) );
        printf( '<div class="wpcalendars-button"><a href="%s" target="_blank">%s</a></div>', '//wpcalendars.com/pricing/', esc_html__( 'Upgrade to Pro', 'wpcalendars' ) );
    }
    
    /**
     * Render filter navigation
     */
    private function filter_navigation() {
        printf( '<h3>%s</h3>', esc_html__( 'Filter Navigation', 'wpcalendars' ) );
        printf( '<p>%s</p>', esc_html__( "We're sorry, this feature is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.", 'wpcalendars' ) );
        printf( '<div class="wpcalendars-button"><a href="%s" target="_blank">%s</a></div>', '//wpcalendars.com/pricing/', esc_html__( 'Upgrade to Pro', 'wpcalendars' ) );
    }
    
    /**
     * Render download navigation
     */
    private function download_navigation() {
        printf( '<h3>%s</h3>', esc_html__( 'Download Navigation', 'wpcalendars' ) );
        printf( '<p>%s</p>', esc_html__( "We're sorry, this feature is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.", 'wpcalendars' ) );
        printf( '<div class="wpcalendars-button"><a href="%s" target="_blank">%s</a></div>', '//wpcalendars.com/pricing/', esc_html__( 'Upgrade to Pro', 'wpcalendars' ) );
    }
    
    /**
     * Render Google Calendar navigation
     */
    private function gcal_navigation() {
        printf( '<h3>%s</h3>', esc_html__( 'Google Calendar Navigation', 'wpcalendars' ) );
        printf( '<p>%s</p>', esc_html__( "We're sorry, this feature is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.", 'wpcalendars' ) );
        printf( '<div class="wpcalendars-button"><a href="%s" target="_blank">%s</a></div>', '//wpcalendars.com/pricing/', esc_html__( 'Upgrade to Pro', 'wpcalendars' ) );
    }
    
    /**
     * Render iCal subscription navigation
     */
    private function ical_subscription_navigation() {
        printf( '<h3>%s</h3>', esc_html__( 'iCal Subscription Navigation', 'wpcalendars' ) );
        printf( '<p>%s</p>', esc_html__( "We're sorry, this feature is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.", 'wpcalendars' ) );
        printf( '<div class="wpcalendars-button"><a href="%s" target="_blank">%s</a></div>', '//wpcalendars.com/pricing/', esc_html__( 'Upgrade to Pro', 'wpcalendars' ) );
    }
    
    /**
     * Render event type settings
     */
    public function events_settings() {
        printf( '<h3>%s</h3>', esc_html__( 'Event Type Settings', 'wpcalendars' ) );
        printf( '<p>%s</p>', esc_html__( "We're sorry, this feature is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.", 'wpcalendars' ) );
        printf( '<div class="wpcalendars-button"><a href="%s" target="_blank">%s</a></div>', '//wpcalendars.com/pricing/', esc_html__( 'Upgrade to Pro', 'wpcalendars' ) );
    }
}

WPCalendars_Lite_Builder::instance();