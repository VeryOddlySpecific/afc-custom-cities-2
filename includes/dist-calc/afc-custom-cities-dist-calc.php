<?php
/**
 * Calculates Service Areas for cities, based on driving distance
 * 
 * Gets all city posts, and all service areas
 * Uses openrouteservice to calculate driving distance between each city and each service area
 * If the driving distance is less than a preset amount, the city's service_area meta is set to the service area
 * If a city has more than one service area, the service area with the shortest distance is set
 */

class AFC_CC_DistCalc {

    //private $city_posts;
    private $serv_areas;
    private $batches;
    //private $requests;
    private $responses;

    public function __construct() {
        $this->load_dependencies();
        //$this->set_city_posts();
        $this->set_serv_areas();
        //$this->set_batches();
        //$this->set_requests();
    }

    private function load_dependencies() {
        require_once AFC_CC_PLUGIN_DIR . 'includes/dist-calc/afc-custom-cities-request.php';
    }

    private function get_city_posts() {
        return $this->get_cities( false );
    }

    private function set_serv_areas() {
        $this->serv_areas = $this->get_cities( true );
    }

    private function get_batches( $city_posts ) {
        $max_routes = 3500;
        $num_serv_areas = count( $this->serv_areas );
        $max_dests = floor( $max_routes / $num_serv_areas ) - $num_serv_areas;
        return array_chunk( $city_posts, $max_dests );
    }

    private function get_requests( $batches ) {
        $requests = array();
        foreach ( $batches as $batch ) {
            $body = $this->get_body( $batch );
            $request = new AFC_CC_Request( $body );

            $requests[] = $request;
        }

        return $requests;
    }

    private function get_body( $cities ) {
        $locations = array();
        $sources = array();

        foreach ( $this->serv_areas as $idx => $area ) {
            $area_name  = get_the_title( $area );
            $area_lat   = get_post_meta( $area, '_afc_cc_latitude', true );
            $area_lon   = get_post_meta( $area, '_afc_cc_longitude', true );
            
            $locations[$area_name] = [ $area_lon, $area_lat ];
            $sources[] = $idx;
        }

        foreach ( $cities as $city ) {
            $city_name  = get_the_title( $city );
            $city_lat   = get_post_meta( $city, '_afc_cc_latitude', true );
            $city_lon   = get_post_meta( $city, '_afc_cc_longitude', true );

            $locations[$city_name] = [ $city_lon, $city_lat ];
        }

        $body = array(
            'locations' => $locations,
            'sources'   => $sources,
            'metrics'   => array( 'distance' ),
            'units'     => 'mi'
        );

        return $body;
    }

    private function get_cities( $service_areas = false ) {
        return get_posts( array(
            'post_type' => 'afc-city',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_afc_cc_is_service_area',
                    'value' => $service_areas
                )
            )
        ) );
    }

    private function get_post_id( $title ) {
        $post = get_posts( array(
            'post_type' => 'afc-city',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'title' => $title
        ) );

        return $post[0];
    }

    public function calc_distances() {
        $city_posts = $this->get_city_posts();
        $batches    = $this->get_batches( $city_posts );
        $requests   = $this->get_requests( $batches );

        $api_call_per_min = 40;
        $sleep_time = ceil( 60 / $api_call_per_min );
        $total_est_time = $sleep_time * count( $requests ) . ' seconds, or' . "<br>" . $sleep_time * count( $requests ) / 60 . " minutes";

        $responses = array();
        foreach ( $requests as $idx => $request ) {
            $body = $request->get_body();
            $responses[] = $request->get_response();
            sleep( $sleep_time );
        }

        foreach ( $responses as $idx => $response ) {

            foreach ( $response as $city => $dists ) {
                $post_id = $this->get_post_id( $city );

                update_post_meta( $post_id, '_afc_cc_service_area_dists', $dists );
            }
        }
    }

    public function set_city_service_area() {
        $city_posts = $this->get_city_posts();

        foreach ( $city_posts as $city ) {
            $max_dist   = get_option( '_afc_cc_max_dist' ) ? get_option( '_afc_cc_max_dist' ) : 300;
            $dists      = get_post_meta( $city, '_afc_cc_service_area_dists', true );
            $city_name  = get_the_title( $city );
            
            
            if ( is_array( $dists ) ) {
                $min_dist = min( $dists );
                if ( $min_dist <= $max_dist ) {
                    $min_dist_idx = array_search( $min_dist, $dists );
                    update_post_meta( $city, '_afc_cc_service_area', $min_dist_idx );
                }
            } else {
                update_post_meta( $city, '_afc_cc_service_area', null );
            }
        }

        wp_redirect( admin_url( 'edit.php?post_type=afc-city' ) );
        exit;
    }

    public function set_city_coords() {
        $cities = $this->get_city_posts();
        $loc_data = array();
        foreach ( $cities as $city ) {
            $city_name = sanitize_title( get_the_title( $city ) );
            $lat = get_post_meta( $city, '_afc_cc_latitude', true );
            $lon = get_post_meta( $city, '_afc_cc_longitude', true );
            
            $loc_data[$city_name] = [$lon, $lat];
        }
        update_option( 'afc_cc_city_coords', $loc_data );
    }

    public function set_local_service_dists() {
        $src_lon = get_post_meta( get_the_ID(), '_afc_cc_longitude', true );
        $src_lat = get_post_meta( get_the_ID(), '_afc_cc_latitude', true );

        $city_coords = get_option( 'afc_cc_city_coords' );

        if ( !$city_coords ) {
            $this->set_city_coords();
            $city_coords = get_option( 'afc_cc_city_coords' );
        }

        unset( $city_coords[get_the_ID()] );

        $data = array(
            'source' => array( $src_lon, $src_lat ),
            'dests' => $city_coords,
            'apiKey' => '5b3ce3597851110001cf6248169de42231d04693ae887c830b408ed2'
        );

        update_post_meta(
            get_the_ID(),
            'afc_cc_local_service_dists',
            $data
        );

        return $data;
    }

    public function enqueue_scripts() {
        $script_tag = 'afc-cc-dist-calc';
        wp_enqueue_script( $script_tag, AFC_CC_PLUGIN_URL . 'build/index.js', array( 'jquery' ), '1.0', true );

        if( is_singular( 'afc-city' ) ) {
            $local_serv_dists = get_post_meta( get_the_ID(), '_afc_cc_local_service_dists', true );

            if ( !$local_serv_dists ) {
                $local_serv_dists = $this->set_local_service_dists();
            }
        }

        wp_localize_script( 
            $script_tag,
            'cityData',
            $local_serv_dists
        );
    }
}