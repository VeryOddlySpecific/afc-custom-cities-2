<?php 

class AFC_CC_Importer {
    
    // imports $_POST data
    public function import( $data ) {
        $test_data = array();
        $path = $data['tmp_name'];
        $handle = fopen( $path, 'r' );
        $first_row = true;

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( $first_row ) {
                $first_row = false;
                continue;
            }
            
            if ( !post_exists( $row[0] . ', ' . $row[1] ) ) {
                $this->insert_city( $row );
            }
        }
    }

    public function insert_city( $row ) {
        require_once AFC_CC_PLUGIN_DIR . 'includes/afc-custom-cities-city.php';
        $city = new AFC_CC_City( $row );
        $city->insert();
    }
}