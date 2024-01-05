<?php 
/**
 * Sets up the plugin settings page
 */

class AFC_CC_Admin_Display {

    public function render() {    
        ?>
        <div class="wrap">
            <h1>AFC Custom Cities</h1>
            <form method="post" action="">
                <?php settings_fields( AFC_CC_SETTINGS_GROUP ); ?>
                <?php do_settings_sections( 'afc-custom-cities' ); ?>
                <?php submit_button("Save Settings"); ?>
            </form>

            <h1>Import Cities</h1>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="afc_cc_import_cities">
                <input type="file" name="afc_cc_import_file" accept=".csv">
                <?php submit_button("Import Cities"); ?>
            </form>

            <h1>Calculate Distances</h1>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="afc_cc_calc_distances">
                <?php submit_button("Calculate Distances"); ?>
            </form>

            <h1>Refresh Service Areas</h1>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="afc_cc_refresh_service_areas">
                <?php submit_button("Refresh Service Areas"); ?>
            </form>
        </div>
        <?php
    }
}