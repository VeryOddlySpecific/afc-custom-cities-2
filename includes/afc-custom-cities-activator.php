<?php 

class AFC_CC_Activator {

    

    public function activate() {

        flush_rewrite_rules();
    }
}