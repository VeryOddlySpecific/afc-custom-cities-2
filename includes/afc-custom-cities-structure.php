<?php 

class AFC_CC_Structure {

    private $content;

    public function __construct( $content ) {
        $this->content = $content;
    }

    private function process_content() {
        $content = '';

        foreach ( $this->content as $section => $data ) {
            $content .= $this->process_section( $section, $data );
        }

        $content = $this->filter_meta( $content );

        return $content;
    }

    private function process_section( $section, $data, $element = 'section' ) {
        $content = '';

        if ( is_array( $data ) ) {
            $content .= "<$element id='$section' class='afc-cc-$element'>";

            foreach ( $data as $value ) {
                $content .= $this->process_section( $section, $value, 'div' );
            }
        } else {
            
            $post_content = apply_filters( 'the_content', get_post_field( 'post_content', $data ) );
            $content .= "<$element id='$section-$data' class='afc-cc-$element'>";
            $content .= '<div class="afc-cc-section-content">';
            if ( $section === 'faqs' ) {
                $content .= "<h3 class='afc-cc-faq-title'>" . get_the_title( $data ) . "</h3>";
            }
            $content .= $post_content . '</div>';
        }

        $content .= '</'. $element . '>';

        return $content;
    }

    private function filter_meta( $content ) {
        $meta = get_post_meta( get_the_ID() );

        $service_area = get_post_meta( get_the_ID(), '_afc_cc_service_area', true );

        if ( $service_area ) {
            $city_slug = sanitize_title( $service_area );
            $area_meta = get_option( '_afc_cc_service_area_' . $city_slug );
        } else {
            $area_meta = get_option( '_afc_cc_service_area_la-vista-ne');
        }

        $meta['_afc_cc_phone'][]   = '<a class="afc-cc-tel-link" href="tel:' . $area_meta['phone'] . '">' . $area_meta['phone'] . '</a>';
        $meta['_afc_cc_address'][] = $area_meta['address'];
        $meta['_afc_cc_zip'][]     = $area_meta['zip'];
        $meta['_afc_cc_email'][]   = $area_meta['email'];

        foreach ( $meta as $key => $val ) {
            if ( strpos( $key, 'afc_cc_' ) !== false ) {
                $mod_key = str_ireplace( '_afc_cc_', '', $key );
                $content = str_ireplace( '{{' . $mod_key . '}}', $val[0], $content );
                $content = str_ireplace( '{_' . $mod_key . '}', $val[0], $content);
            }
        }

        return $content;
    }

    public function get_content() {
        return $this->process_content();
    }
}