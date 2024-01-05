<?php 

class AFC_CC {

    private $admin;
    private $content;

    public function __construct() {
        $this->load_dependencies();
        $this->set_controllers();
    }

    private function load_dependencies() {
        require_once AFC_CC_PLUGIN_DIR . 'includes/admin/afc-custom-cities-admin.php';
        require_once AFC_CC_PLUGIN_DIR . 'includes/afc-custom-cities-content-controller.php';
    }

    private function set_controllers() {
        $this->admin = new AFC_CC_Admin();
        $this->content = new AFC_CC_Content_Controller();
    }

    public function register_post_types() {
        $cpt_json = file_get_contents( AFC_CC_PLUGIN_DIR . 'includes/json/cpt.json' );
        $cpt_data = json_decode( $cpt_json, true );

        foreach ( $cpt_data as $name => $args ) {
            register_post_type( $name, $args );
        }
    }

    public function register_taxonomies() {
        $tax_json = file_get_contents( AFC_CC_PLUGIN_DIR . 'includes/json/tax.json' );
        $tax_data = json_decode( $tax_json, true );

        foreach ( $tax_data as $name => $args ) {
            register_taxonomy( $name, $args['post_type'], $args['args'] );
        }
    }

    public function register_patterns() {
        $patterns = json_decode( file_get_contents( AFC_CC_PLUGIN_DIR . 'includes/json/patterns.json' ), true );
        foreach ( $patterns as $name => $props ) {
            unregister_block_pattern( $name );
            register_block_pattern( $name, $props );
        }
        $registry = WP_Block_Patterns_Registry::get_instance();
        $all_patterns = $registry->get_all_registered();
        
        echo '<pre>';
        foreach ( $all_patterns as $pattern ) {
            if ( $pattern[ 'name' ] == 'afc-cc-content-heading' ) {
                print_r( $pattern );
            }
        }
        echo '</pre>';
        exit;
        

        // add post title to content portion of pattern

        foreach ( $patterns as $name => $props ) {
            unregister_block_pattern( $name );
            register_block_pattern( $name, $props );
        }
    }

    public function load_scripts() {
        if ( is_singular( 'afc-city' ) && !wp_is_block_theme() ) {
            wp_enqueue_style(
                'afc-cc-old-theme-styles',
                AFC_CC_PLUGIN_URL . 'old-theme.css',
                array(),
                '1.0.0',
                'all'
            );
        }
        
        wp_enqueue_style( 
            'afc-cc-styles', 
            AFC_CC_PLUGIN_URL . 'style.css', 
            array(), 
            '1.0.0', 
            'all' 
        );

        
    }

    public function redirect_city_to_page() {
        if ( is_singular( 'afc-city' ) ) {
            $curr_url = $_SERVER['REQUEST_URI'];
            $city_slug = end( explode( '/', trim( $curr_url, '/' ) ) );
            $service_areas = json_decode( 
                file_get_contents(
                    AFC_CC_PLUGIN_DIR . 'includes/json/service-areas.json' 
                ), 
                true 
            );

            foreach ( $service_areas as $city ) {
                $slug = sanitize_title( $city['city'] . '-' . $city['state'] );
                if ( $slug == $city_slug ) {
                    $page_slug = substr( $slug, 0, -3 );
                    $page = get_page_by_path( $page_slug, OBJECT, 'page' );
                    wp_redirect( get_permalink( $page->ID ), 301 );
                    exit();
                }
            }
        }
    }

    public function run() {

        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
        add_action( 'template_redirect', array( $this, 'redirect_city_to_page' ) );
        add_filter( 'the_content', array( $this->content, 'check_content' ) );
        $this->admin->run();
    }
}