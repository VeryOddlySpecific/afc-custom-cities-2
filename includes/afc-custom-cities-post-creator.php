 <?php 

class AFC_CC_Post_Creator {

    private $post_type;
    private $post_title;
    private $post_content;
    private $post_meta;

    public function __construct( $type, $title, $content, $meta ) {
        $this->post_type    = $type;
        $this->post_title   = $title;
        $this->post_content = $content;
        $this->post_meta    = $meta;
    }

    private function insert_post() {
        $post_id = wp_insert_post( array(
            'post_type'     => $this->post_type,
            'post_title'    => $this->post_title,
            'post_content'  => $this->post_content,
            'post_status'   => 'publish',
        ) );

        if ( $post_id ) {
            foreach ( $this->post_meta as $meta_key => $meta_value ) {
                update_post_meta( $post_id, $meta_key, $meta_value );
            }
        }
    }

    private function filter_content() {
        $filtered_content = '';

        return $filtered_content;
    }
}