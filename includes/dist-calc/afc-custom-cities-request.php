<?php 

class AFC_CC_Request {

    private $url;
    private $headers;
    private $raw_body;
    private $raw_locs;
    private $body;
    private $response;

    public function __construct( $body) {
        $this->set_url();
        $this->set_headers();
        $this->set_body( $body );
    }

    private function set_url() {
        $url = get_option( '_afc_cc_api_url' ) ? get_option( '_afc_cc_api_url' ) : 'https://api.openrouteservice.org/v2/matrix/driving-car';
        $this->url = $url;
    }

    private function set_headers() {
        $api_key = get_option( '_afc_cc_api_key' ) ? get_option( '_afc_cc_api_key' ) : '5b3ce3597851110001cf6248169de42231d04693ae887c830b408ed2';
        $this->headers = array(
            "Accept: application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8",
            "Authorization: " . $api_key,
            "Content-Type: application/json; charset=utf-8"
        );
    }

    private function set_body( $body ) {
        $this->raw_body = $body;
        $this->raw_locs = $body['locations'];
        $loc_array = array();
        foreach ( $body['locations'] as $city => $data ) {
            $lon = $data[0];
            $lat = $data[1];
            $loc_array[] = array( $lon, $lat );
        }
        $body['locations'] = $loc_array;
        $this->body = json_encode( $body );
    }

    private function run_request() {
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $this->url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, false );

        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->body );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->headers );

        $response = curl_exec( $ch );

        curl_close( $ch );

        $this->response = json_decode( $response, true );
    }

    private function filter_response() {
        $distances = $this->response['distances'];
        $serv_areas = array();
        $serv_area_count = count( $this->raw_body['sources'] );
        $serv_areas = array_keys( array_slice( $this->raw_locs, 0, $serv_area_count ) );
        $filtered = array();

        foreach ( $distances as $idx => $serv_area ) {
            $area_name  = $serv_areas[$idx];
            $dist_slice = array_slice( $serv_area, $serv_area_count );
            $cities     = array_keys( $this->raw_locs );
            $city_names = array_slice( $cities, $serv_area_count );

            foreach ( $dist_slice as $idx => $dist ) {
                $city_name = $city_names[$idx];
                $filtered[$city_name][$area_name] = $dist;
            }
        }

        return $filtered;
    }

    public function get_body() {
        return $this->body;
    }

    public function get_location_count() {
        return count( $this->raw_body['locations'] );
    }

    public function get_response() {
        $this->run_request();
        $filtered = $this->filter_response();
        return $filtered;
    }
}