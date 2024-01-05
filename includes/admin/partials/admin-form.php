<form method="post" action="">
    <?php settings_fields( AFC_CC_SETTINGS_GROUP ); ?>
    <?php do_settings_sections( 'afc-custom-cities' ); ?>
    <?php submit_button("Save Settings"); ?>
</form>