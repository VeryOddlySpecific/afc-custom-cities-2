<?php 

class AFC_CC_City {

    private $service_areas;
    private $post_args;
    private $post_meta;

    public function __construct( $row ) {

        $this->set_service_areas();
        $this->set_post_args( $row );
        $this->set_post_meta( $row );

    }

    private function set_service_areas() {
        $sa_json = file_get_contents( AFC_CC_PLUGIN_DIR . 'includes/json/service_areas.json' );
        $sa_data = json_decode( $sa_json, true );

        

        $this->service_areas = $sa_data;
    }

    private function set_post_args( $row ) {
        $this->post_args = array(
            'post_title'    => $row[0] . ', ' . $row[1],
            'post_type'     => 'afc-city',
            'post_status'   => 'publish'
        );
    }

    private function set_post_meta( $row ) {
        $this->post_meta = array(
            'city'              => $row[0],
            'state'             => $row[1],
            'latitude'          => $row[2],
            'longitude'         => $row[3],
            'is_service_area'   => $this->is_service_area( $row[0], $row[1] ),
        );
    }    

    private function is_service_area( $city, $state ) {

        foreach ( $this->service_areas as $service_area ) {
            if ( $service_area['city'] == $city && $service_area['state'] == $state ) {
                return true;
            }
        }

        return false;
    }

    public function get_post_meta() {
        return $this->post_meta;
    }

    public function insert_meta( $post_id ) {
        foreach ( $this->post_meta as $key => $value ) {
            $key = '_afc_cc_' . $key; // '_afc_cc_city
            update_post_meta( $post_id, $key, $value );
        }
    }

    public function insert() {
        $post_id = wp_insert_post( $this->post_args );

        if ( $post_id ) {
            $this->insert_meta( $post_id );
        }
    }
}