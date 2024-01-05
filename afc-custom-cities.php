<?php 
/**
 * Plugin Name: AFC Custom Cities
 * Plugin URI:
 * Description: Custom cities for AFC
 * Version: 1.0.0
 * Author: Alexander Steadman
 * Author URI:
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: VeryOddlySpecific/afc-custom-cities
 * GitHub Plugin URI: https://github.com/VeryOddlySpecific/afc-custom-cities
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require 'defines.php';

register_activation_hook( __FILE__, 'afc_custom_cities_activation' );
register_deactivation_hook( __FILE__, 'afc_custom_cities_deactivation' );

function afc_custom_cities_activation() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/afc-custom-cities-activator.php';
    $activator = new AFC_CC_Activator();
    $activator->activate();
}

function afc_custom_cities_deactivation() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/afc-custom-cities-deactivator.php';
}

function afc_run_plugin() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-afc-custom-cities.php';
    $plugin = new AFC_CC();
    $plugin->run();
}
afc_run_plugin();