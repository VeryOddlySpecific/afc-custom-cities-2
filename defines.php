<?php 

/**
 * Set const definitions
 */

define( 'AFC_CC_VERSION', '1.0.0' );
define( 'AFC_CC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFC_CC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AFC_CC_SETTINGS_GROUP', 'afc_cc_settings_group' );

if ( strpos( $_SERVER['SERVER_NAME'], '.local' ) !== false ) {
    define( 'AFC_CC_ENV', 'local' );
    define( 'DISALLOW_FILE_EDIT', false );
} else {
    define( 'AFC_CC_ENV', 'prod' );
    define( 'DISALLOW_FILE_EDIT', true );
}