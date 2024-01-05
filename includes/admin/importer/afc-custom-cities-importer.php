<?php 

class AFC_CC_Importer {
    
    // imports $_POST data
    public function import( $data ) {
        
        $path = $data['tmp_name'];
        $handle = fopen( $path, 'r' );
        $first_row = true;

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( $first_row ) {
                $first_row = false;
                continue;
            }

            $this->insert_city( $row );
        }
    }

    public function insert_city( $row ) {
        require_once AFC_CC_PLUGIN_DIR . 'admin/importer/afc-custom-cities-city.php';
        $city = new AFC_CC_City( $row );
        $city->insert();
    }
}