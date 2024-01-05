<?php

class AFC_CC_DistCalc {

    private $service_areas;
    private $cities;

    public function __construct() {
        $this->service_areas    = $this->get_cities( true );
        $this->cities           = $this->get_cities( false );
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



    private function set_distance_meta( $distances ) {
        foreach ( $distances as $city_id => $distance ) {
            update_post_meta( $city_id, '_afc_cc_service_area_dist', $distance );
        }
    }

    private function set_service_area( $city, $srv_area ) {
        update_post_meta( $city, '_afc_cc_service_area', $srv_area );
    }

    private function get_id_from_coords( $lat, $lon ) {
        $post = get_posts( array(
            'post_type' => 'afc-city',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_afc_cc_latitude',
                    'value' => $lat
                ),
                array(
                    'key' => '_afc_cc_longitude',
                    'value' => $lon
                )
            )
        ) );

        return $post[0];
    }

    private function set_distances() {
        $batches   = array_chunk( $this->cities, 3400 );

        // for each batch, construct request body
        // body = assoc array
        // body['locations'] = array of coord arrays [ [lon, lat], [lon, lat], ... ]
        // body['metrics'] = array of metrics ['distance']
        // body['sources'] = array of source ids [1, 2, 3, ...]
        // body['units'] = 'mi'
        foreach ( $batches as $batch ) {
            $body = array();

            foreach ( $this->service_areas as $idx => $src ) {
                $src_lon = get_post_meta( $src, '_afc_cc_longitude', true );
                $src_lat = get_post_meta( $src, '_afc_cc_latitude', true );

                $body['locations'][] = [ $src_lat, $src_lon ];
                $body['sources'][] = $idx;
            }
            
            foreach ( $batch as $city ) {
                $city_lat = get_post_meta( $city, '_afc_cc_latitude', true );
                $city_lng = get_post_meta( $city, '_afc_cc_longitude', true );

                $body['locations'][] = [ $city_lat, $city_lng ];
            }
            $body['metrics'] = array( 'distance' );
            $body['units'] = 'mi';

            $distances = $this->process_request( $body );
        }

        foreach ( $cities_array as $batch ) {
            $locations = array();
            
            foreach ( $this->service_areas as $src ) {
                $src_lon = get_post_meta( $src, '_afc_cc_longitude', true );
                $src_lat = get_post_meta( $src, '_afc_cc_latitude', true );

                $locations[] = [ $src_lat, $src_lon ];
            }

            foreach ( $batch as $city ) {
                $city_lat = get_post_meta( $city, '_afc_cc_latitude', true );
                $city_lng = get_post_meta( $city, '_afc_cc_longitude', true );

                $locations[] = [ $city_lat, $city_lng ];
            }
        }
    }

    private function process_request( $body ) {
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, 'https://api.openrouteservice.org/v2/matrix/driving-car' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, false );

        curl_setopt( $ch, CURLOPT_POST, true );

        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $body ) );

        curl_setopt( $ch, CULROPT_HTTPHEADER, array(
            "Accept: application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8",
            "Authorization: 5b3ce3597851110001cf6248169de42231d04693ae887c830b408ed2",
            "Content-Type: application/json; charset=utf-8"
        ));

        $response = curl_exec( $ch );
        curl_close( $ch );

        return json_decode( $response, true );
    }
}