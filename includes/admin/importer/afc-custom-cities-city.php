<?php 

class AFC_CC_City {

    private $post_meta;
    private $service_areas;

    public function __construct( $row ) {

        $this->set_service_areas();

        $this->post_meta = array(
            'city'              => $row[0],
            'state'             => $row[1],
            'zip_code'          => $row[2],
            'latitude'          => $row[3],
            'longitude'         => $row[4],
            'service_area'      => '',
            'is_service_area'   => $this->is_service_area( $row[0], $row[1] ) ? true : false,
            'service_area_dist' => ''
        );

    }

    private function is_service_area( $city, $state ) {
        foreach ( $this->service_areas as $sa ) {
            if ( $sa['city'] === $city && $sa['state'] === $state ) {
                return true;
            }
        }

        return false;
    }

    private function set_service_areas() {
        $sa_json = file_get_contents( AFC_CC_PLUGIN_DIR . 'includes/json/service_areas.json' );
        $sa_data = json_decode( $sa_json, true );

        $this->service_areas = $sa_data;
    }

    private function get_post_args() {
        return array(
            'post_title'    => $this->city,
            'post_type'     => 'afc-city',
            'post_status'   => 'publish'
        );
    }

    public function insert_meta( $post_id ) {
        foreach ( $this->post_meta as $key => $value ) {
            $key = '_afc_cc_' . $key; // '_afc_cc_city
            update_post_meta( $post_id, $key, $value );
        }
    }

    public function insert() {
        $post_id = wp_insert_post( $this->get_post_args() );

        if ( $post_id ) {
            $this->insert_meta( $post_id );
        }
    }
}