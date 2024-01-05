<?php

class AFC_CC_Content_Controller {

    private $post_id;
    private $content_sections;

    private $content_ids;
    private $faq_ids;

    public function __construct() {
        $this->set_post_id();
        $this->load_dependencies();
        //$this->set_content();
    }

    private function load_dependencies() {
        require_once AFC_CC_PLUGIN_DIR . 'includes/afc-custom-cities-structure.php';
    }

    private function set_post_id() {
        $this->post_id = get_the_ID();
    }

    private function get_posts( $type, $tag = null ) {
        $posts = get_posts( array(
            'post_type'         => $type,
            'posts_per_page'    => -1,
            'fields'            => 'ids',
            'tag'               => $tag,
        ) );

        return $posts;
    }

    private function get_terms( $term ) {
        return get_terms( array(
            'taxonomy'      => $term,
            'fields'        => 'slugs',
        ) );
    }

    private function get_random_posts( $posts, $limit, $sections = null ) {
        $random_posts = array();

        // if no sections, add $limit random posts to $random_posts
        if ( !$sections ) {
            $rand_idx = array_rand( $posts, $limit );

            foreach ( $rand_idx as $idx ) {
                $random_posts[] = $posts[$idx];
            }
        }

        // if sections, add $limit random posts from each section to $random_posts
        if ( $sections ) {
           
            foreach ( $sections as $section ) {
                $random_content = array();

                foreach ( $posts as $post_id ) {

                    if ( has_term( $section, 'content_sections', $post_id ) ) {
                        $random_content[] = $post_id;
                    }
                }

                if ( !empty( $random_content ) ) {
                    $rand_idx = array_rand( $random_content, $limit );

                    if ( is_array( $rand_idx ) ) {

                        foreach ( $rand_idx as $idx ) {
                            $random_posts[$section][] = $random_content[$idx];
                        }

                        continue;
                    }

                    $random_posts[$section] = $random_content[$rand_idx];
                }
            }
        }

        return $random_posts;
    }

    private function construct_content_array( $content, $faq ) {
        $compiled_array = array();

        // set first content post to section 'american-fence-company'
        $compiled_array['american-fence-company'] = $content['american-fence-company'];
        unset( $content['american-fence-company'] );

        $content['faqs'] = $faq;

        // find sections where the section name has "split"
        // add items to $split_sections array
        // remove items from $content array
        // add $split_sections array to $compiled_array
        $split_sections = array();
        foreach ( $content as $section => $post_id ) {
            if ( strpos( $section, 'split' ) !== false ) {
                $split_sections[$section] = $post_id;
                unset( $content[$section] );
            }
        }
        $content['split-section'] = $split_sections; 

        // randomize the order of sections in $content
        // add $content to $compiled_array
        $keys = array_keys( $content );
        shuffle( $keys );
        foreach ( $keys as $key ) {
            $compiled_array[$key] = $content[$key];
        }

        return $compiled_array;
    }

    private function get_content( $service_area ) {

        if ( $service_area != null ) {
            $content_posts = $this->get_posts( 'afc-content', 'install' );
        } else {
            $content_posts = $this->get_posts( 'afc-content', 'sales' );
        }

        $content_sections  = $this->get_terms( 'content_sections' );
        $faq_posts         = $this->get_posts( 'afc-faq' );

        // select 3 random faq posts
        // select 1 random content post from each section
        $faq_selection      = $this->get_random_posts( $faq_posts, 3 );
        $content_selection  = $this->get_random_posts( $content_posts, 1, $content_sections );

        // construct assoc array of $section => $post_id
        // construct faq array
        $compiled_array = $this->construct_content_array( $content_selection, $faq_selection );

        $structure = new AFC_CC_Structure( $compiled_array );

        if ( !wp_is_block_theme() ) {
            $content = $structure->get_content();
            return $content;
            /*
            echo "<pre>";
            print_r(esc_html($content));
            echo "</pre>";
            exit;
            $classic_content = get_post_meta( get_the_ID(), '_afc_cc_classic_content', true );
            if ( !$classic_content ) {
                update_post_meta( get_the_ID(), '_afc_cc_classic_content', $content );
            }
            return $classic_content;
            */
        }

        return $structure->get_content();
    }

    public function check_content( $content ) {

        // check if single city
        if ( !is_singular( 'afc-city' ) ) {
            return $content;
        }

        // then, check if content exists
        //    if so, return content
        //    if no, create content
        if ( $content ) {
            return $content;
        }

        $service_area = get_post_meta( get_the_ID(), '_afc_cc_service_area', true );
        
        //$content = apply_filters( 'the_content', $content, $service_area );
        $content = $this->get_content( $service_area );
        $update = wp_update_post( 
            array(
                'ID'            => get_the_ID(),
                'post_content'  => $content,
            ),
            true
        );
        return $content;
    }    

    private function set_post_ids( $posts ) {
        $post_ids = array();
    }

    private function set_content() {

        $sections = get_terms( array(
            'taxonomy'      => 'content_sections',
            'fields'        => 'slugs',
        ) );
        $content_posts = $this->get_posts( 'afc-content' );
        $faq_posts = $this->get_posts( 'afc-faq' );

        
        $faq_ids = array();
        $faqs = array_rand( $faq_posts, min( 3, count( $faq_posts ) ) );
        foreach ( $faqs as $idx ) {
            $faq_ids[] = $faq_posts[$idx];
        }

        $content_ids = array();
        foreach ( $sections as $section ) {
            $content = array();
            
            foreach ( $content_posts as $post_id ) {
                if ( has_term( $section, 'content_sections', $post_id ) ) {
                    $content[] = $post_id;
                }
            }

            if ( count( $content ) > 1 ) {
                $rand_idx = array_rand( $content, 1 );
                $content_ids[$section] = $content[$rand_idx];
            } else if ( count( $content ) == 1 ) {
                $content_ids[$section] = $content[0];
            }
        }

        $faq_pos = rand( 0, count( $content_ids ) - 1 );

        $before = array_slice( $content_ids, 0, $faq_pos );
        $after = array_slice( $content_ids, $faq_pos );

        $compiled_content = array_merge( $before, ['faqs' => $faq_ids], $after );

        $post_content = $this->construct_post_content( $compiled_content );

        wp_update_post( array(
            'ID'            => $this->post_id,
            'post_content'  => $post_content,
        ) );
        //update_post_meta( get_the_ID(), '_afc_cc_content', $post_content );
    }

    private function construct_post_content( $content ) {
        $post_content = '';
        

        foreach ( $content as $section => $post_id ) {

            if ( !is_array( $post_id ) ) {
                $post_content .= '<section id="section-post-id-' . $post_id . '" class="' . get_post_field( 'post_name', $post_id ) . ' content-section-' . $section . '">';
                //$post_content .= '<h2>' . get_the_title( $post_id ) . '</h2>';
                $post_content .= get_post_field( 'post_content', $post_id );
                $post_content .= '</section>';
                continue;
            }

            if ( $section === 'faqs' ) {
                $post_content .= '<section class="content-section-faqs">';
                $post_content .= '<h2>FAQs</h2>';
                $post_content .= '<div class="faq-row">';
                foreach ( $post_id as $faq_id ) {
                    $post_content .= '<div class="faq-container">';
                    $post_content .= '<h3>' . get_the_title( $faq_id ) . '</h3>';
                    $post_content .= get_post_field( 'post_content', $faq_id );
                    $post_content .= '</div>';
                }
                $post_content .= '</div>';
                $post_content .= '</section>';
            }
        }

        return $post_content;
    }
}