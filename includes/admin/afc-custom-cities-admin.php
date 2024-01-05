<?php 
/**
 * Runs Admin functionality
 */

class AFC_CC_Admin {

    private $admin_display;
    private $admin_importer;
    private $admin_dist_calc;
    private $cont_controller;
    
    public function __construct() {
        $this->load_dependencies();
        $this->set_controllers();
    }

    private function load_dependencies() {
        require_once AFC_CC_PLUGIN_DIR . 'includes/admin/afc-custom-cities-admin-display.php';
        require_once AFC_CC_PLUGIN_DIR . 'includes/afc-custom-cities-importer.php';
        require_once AFC_CC_PLUGIN_DIR . 'includes/dist-calc/afc-custom-cities-dist-calc.php';
    }

    private function set_controllers() {
        $this->admin_display = new AFC_CC_Admin_Display();
        $this->admin_importer = new AFC_CC_Importer();
        $this->admin_dist_calc = new AFC_CC_DistCalc();
    }

    public function add_top_level_menu() {
        add_menu_page(
            'AFC Custom Cities',
            'AFC Custom Cities',
            'manage_options',
            'afc-custom-cities',
            '',
            'dashicons-location-alt',
            6
        );
    }

    public function add_settings_page() {
        add_submenu_page(
            'afc-custom-cities',
            'Settings',
            'Settings',
            'manage_options',
            'afc-custom-cities-settings',
            array( $this, 'afc_custom_cities_page_settings' )
        );
    }

    public function register_settings() {
        $settings_json = file_get_contents( AFC_CC_PLUGIN_DIR . 'includes/json/settings.json' );
        $settings_data = json_decode( $settings_json, true );
        $service_areas = file_get_contents( AFC_CC_PLUGIN_DIR . 'includes/json/service-areas.json' );
        $service_areas = json_decode( $service_areas, true );

        foreach ( $settings_data as $name => $args ) {
            register_setting( AFC_CC_SETTINGS_GROUP, $name, $args );
        }

        foreach ( $service_areas as $city ) {
            $slug = sanitize_title( $city['city'] . ', ' . $city['state'] );
            update_option( '_afc_cc_service_area_' . $slug, $city );
        }
    }

    public function afc_custom_cities_page_settings() {
        $this->admin_display->render();
    }

    public function afc_cc_import_cities() {
        $this->admin_importer->import( $_FILES['afc_cc_import_file'] );
        wp_redirect( admin_url( 'edit.php?post_type=afc-city' ) );
        exit;
    }

    public function run() {
        add_action( 'admin_menu', array( $this, 'add_top_level_menu' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_enqueue_scripts', array( $this->admin_dist_calc, 'enqueue_scripts' ) );

        add_action( 'admin_post_afc_cc_import_cities', array( $this, 'afc_cc_import_cities' ) );
        add_action( 'admin_post_afc_cc_calc_distances', array( $this->admin_dist_calc, 'calc_distances' ) );
        add_action( 'admin_post_afc_cc_refresh_service_areas', array( $this->admin_dist_calc, 'set_city_service_area' ) );
    }
}