<div class="wrap">
<h2><?php _e('Bol.com Partnerprogramma settings', 'wp_bolcom_affiliates') ?></h2>

<form action="options.php" method="post">
<?php 
settings_fields( 'wpbol_settings' ); 
do_settings_sections( 'wpbol_settings_page' ); 
echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="'.__('Save Changes', 'wp_bolcom_affiliates').'"  /></p>';
?>
</form>

</div>
