<?php
/**
 * @package wp_bolcom_affiliates
 * Plugin Name: Bol.com Partnerprogramma by Biz2Web
 * Plugin URI: http://wordpress.org/extend/plugins/wp-bolcom-affiliates
 * Description: Integrates the Bol.com Partnerprogramma into your Wordpress website.
 * Author: Biz2Web (niet Bol.com)
 * Version: 1.0.2
 * Author URI: http://plugins.biz2web.nl
 */

define ('WP_BOLCOM_BASE_PATH', plugin_dir_path(__FILE__) );
define ('WP_BOLCOM_LOAD_BASE', home_url( '/?pagename=bolcom-load&file=', __FILE__) );

class WP_Bolcom_Affiliates {

	static private $_instance;

	protected $shortcodes = Array();

	static function &get_instance() {
		if( !isset(self::$_instance) ) {
			self::$_instance = new WP_Bolcom_Affiliates();	
		}
		return self::$_instance;
	}

	/**
	 * Constructor. Add initialization functions and menu items
	 */
	public function __construct () {
		register_activation_hook( __FILE__, Array(&$this, 'activate') );
		register_deactivation_hook( __FILE__, Array(&$this, 'deactivate') );

		add_action( 'init', Array( &$this, 'init' ) );
		add_action( 'plugins_loaded', Array( &$this, 'load_translations' ) );
		add_action( 'template_redirect', Array( &$this, 'get_load_page' ) );
		add_action( 'wp_footer', Array( &$this, 'load_shortcode_script' ) );
		if ( is_admin() ) {
			add_action( 'admin_init', Array( &$this, 'admin_init' ) );
			add_action( 'admin_menu', Array( &$this, 'admin_menu' ) );
		}
	}

	/**
	 * Executed on plugin activation. 
	 */
	public function activate () {
		return;
	}

	/**
	 * Executed on plugin deactivation. 
	 */
	public function deactivate () {
		return;
	}

	/**
	 * Initialization. Add necessary functions for [bolcom] shortcode.
	 */
	public function init () {
	        // Shortcode
		add_shortcode( 'bcproduct', Array( &$this, 'bolcom_shortcode' ) );
		add_shortcode( 'bclink', Array( &$this, 'bolcom_shortcode' ) );

		// Short shortcodes?
		add_shortcode( 'bcp', Array( &$this, 'bolcom_shortcode' ) );
		add_shortcode( 'bcl', Array( &$this, 'bolcom_shortcode' ) );
	}

	/**
	 * Load translations.
	 */
	public function load_translations() {
		$path = basename(dirname(__FILE__)).'/lang/';
		load_plugin_textdomain( 'wp_bolcom_affiliates', false, $path );
	}

	/**
	 * Change template for bolcom-load page.
	 */
	public function get_load_page() {
		global $wp_query;
		if( get_query_var('pagename') == 'bolcom-load' ) {
			header('HTTP/1.1 200 OK');
			set_query_var('is_404', false);
			include(WP_BOLCOM_BASE_PATH.'/load.php');
			die();
		}
	}

	/**
	 * Load the script that processes the shortcodes.
	 */
	public function load_shortcode_script() {
		$shortcodes = $this->shortcodes;

		$data = Array();
		foreach( $shortcodes as $key => $value ) {
			$data[$key] = json_encode($value);
		}
		wp_enqueue_script(
			'wpbol_shortcode',
			WP_BOLCOM_LOAD_BASE.'shortcode.js',
			Array('jquery', 'json2')
		);
		wp_localize_script(
			'wpbol_shortcode',
			'wpbol_shortcode_data',
			$data
		);
	}

	/**
	 * Add a shortcode to the list of shortcodes to process
	 */
	public function add_shortcode( $atts ) {
		$data = shortcode_atts( Array(
			'product_id' 	=> null,
			'id' 		=> 'wpbol_'.substr(hash('md5', rand()), 0, 8),
			'htmlid'	=> '',
			'class' 	=> '',
			'type' 		=> 'link',
			'partner_id' 	=> '0',
			'content'	=> '',
		), $atts);

		if( $data['product_id'] === null )
			return false;

		$this->shortcodes[$data['id']] = $data;

		return $data['id'];
	}
	
	/**
	 * Process the [bcproduct] and [bclink] shortcodes.
	 * @param Array $atts Required. 
	 * @param String $content Required.
	 * @param String $tag Required.
	 * @return String. Output that will be parsed in the document.
	 */
	public function bolcom_shortcode( $atts, $content = null, $tag = null ) {
		$partner_id = get_option( 'wpbol_partner_id', '' );

		switch( $tag ) {
		case 'bcproduct':
		case 'bcp':
			extract( shortcode_atts( Array(
				'pid' => '0',
				'type' => 'link',
				'id' => null,
				'class' => '',
			), $atts ) );
			$pid = esc_attr($pid);
			$type = esc_attr($type);
			$id = sanitize_html_class($id);
			$class = sanitize_html_class($class);

			$content = do_shortcode( $content );

			$data = Array(
				'file' 		=> 'data.php',
				'pagename'	=> 'bolcom-load',
				'partner_id' 	=> $partner_id,
				'product_id' 	=> $pid,
				'type'		=> $type,
				'htmlid'	=> $id,
				'class' 	=> $class,
				'content' 	=> $content,
			);

			$id = $this->add_shortcode($data);

			if( $id !== false ) {
				$out = "<div id='{$id}'>{$content}</div>";
			} else {
				$out = $content;
			}

			break;	
		case 'bclink':
		case 'bcl':
		default:
			extract( shortcode_atts( Array(
				'href' => 'http://www.bol.com',
				'title' => '',
				'id' => '',
				'class' => '',
			), $atts ) );

			$href = esc_url($href);
			$id = sanitize_html_class($id);
			$class = sanitize_html_class($class);

			if( empty( $content ) )
				$content = $href;
			$content = do_shortcode( $content );

			$out = "<a href='{$href}' target='_blank'";
			$out .= (empty($id)) ? '' : " id='{$id}'";
			$out .= (empty($class)) ? '' : " class='{$class}'";
			$out .= (empty($title)) ? '' : " title='{$title}'";
			$out .= '>'.$content.'</a>';

			break;
		}

		return wptexturize($out);
	}

	/**
	 * Admin initialization. Add necessary functions for the settings page, TinyMCE plugin and quicktags plugin. Show message if the plugin is not yet configured. 
	 */
	public function admin_init () {
		// TinyMCE and quicktags plugins
		if (  current_user_can( 'edit_posts' ) ||  current_user_can( 'edit_pages' ) ) {
			add_action( 'admin_enqueue_scripts', Array( &$this, 'add_quicktag' ) );
			add_filter( 'mce_buttons', Array( &$this, 'register_button' ) );
			add_filter( 'mce_external_plugins', Array( &$this, 'add_plugin' ) );
		}

	        // Settings page
		add_settings_section ( 'wpbol_settings', __('General settings', 'wp_bolcom_affiliates'), Array( &$this, 'admin_settings_section' ), 'wpbol_settings_page' );
		register_setting ( 'wpbol_settings', 'wpbol_partner_id', Array( &$this, 'sanitize_partner_id' ) );
		add_settings_field ( 'wpbol_partner_id', __('Site ID', 'wp_bolcom_affiliates'), Array( &$this, 'admin_settings_partner_id' ), 'wpbol_settings_page', 'wpbol_settings' );
		//add_settings_section ( 'wpbol_premium_settings', __('Premium settings', 'wp_bolcom_affiliates'), Array( &$this, 'admin_premium_settings_section' ), 'wpbol_settings_page' );
		register_setting ( 'wpbol_settings', 'wpbol_auth_key', Array( &$this, 'sanitize_auth_key' ) );
		add_settings_field ( 'wpbol_auth_key', __('Authentication key', 'wp_bolcom_affiliates'), Array( &$this, 'admin_settings_auth_key' ), 'wpbol_settings_page', 'wpbol_premium_settings' );

		// Messages
		if( 0 == get_option( 'wpbol_partner_id', 0 ) )  {
			add_action( 'admin_notices', Array( &$this, 'configure_plugin_message' ) );
		}	
	}

	/**
	 * Add quicktag plugin script. Enqueue after main quicktags script.
	 */
	public function add_quicktag() {
		wp_enqueue_script(
			'bolcom_quicktags',
			WP_BOLCOM_LOAD_BASE.'quicktags.js',
			array( 'quicktags' )
		);
		wp_localize_script('bolcom_quicktags', 'wp_url', Array( 'bolcom_plugin' => plugins_url('', __FILE__)) );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-tabs' );	
	}

	/**
	 * Add Bol.com button to TinyMCE toolbar.
	 * @param Array $buttons. Previous toolbar.
	 * @return Array. Toolbar with Bol.com button added.
	 */
	public function register_button( $buttons ) {
		array_splice( $buttons, 17, 0, Array( "|", "bolcomSplitButton", "|" ) );
 		return $buttons;
	}

	/**
	 * Tell TinyMCE to include the Bol.com plugin.
	 * @param Array $plugin_array. Array of plugins.
	 * @return Array. Array of plugins with Bol.com plugin added.
	 */
	public function add_plugin( $plugin_array ) {
   		$plugin_array['bolcom'] = WP_BOLCOM_LOAD_BASE.'tinymce.js';
   		return $plugin_array;
	}

	/**
	 * Text after section header. Not used yet.
	 */
	public function admin_settings_section () {
		_e('If you do not have a Bol.com Parterprogramma account yet you can register at:', 'wp_bolcom_affiliates');
		echo '<br/><a href="https://partnerprogramma.bol.com/partner/register.do" target="_blank">https://partnerprogramma.bol.com/partner/register.do</a>';
		echo '<br/><br/>';
		_e('Your site ID can be found at:', 'wp_bolcom_affiliates');
		echo '<br/><a href="https://partnerprogramma.bol.com/partner/affiliate/account.do" target="_blank">https://partnerprogramma.bol.com/partner/affiliate/account.do</a>';
	}

	/**
	 * Text after section header. Not used yet.
	 */
	public function admin_premium_settings_section () {
		return;
	}

	/**
	 * Sanitize the site ID, don't update and display a message if it isn't validated.
	 * @param String $input Required. The value to be sanitized and validated.
	 * @return String. The input value, or the current value of the setting if input is not valid.
	 */
	public function sanitize_partner_id( $input ) {
		$partner_id = get_option( 'wpbol_partner_id' );
		$input = trim($input);
		$regex = "/^[0-9]{1,10}$/";
		if (preg_match($regex, $input)) {
			return $input;
		}
		else {
			add_settings_error( 'wpbol_partner_id', 'wpbol_error', __('Invalid Site ID entered. The Site ID should only contain numbers. Not updated.', 'wp_bolcom_affiliates') );
			return $partner_id;
		}
	}

	/**
	 * Sanitize the authentication key, don't update and display a message if it isn't validated.
	 * @param String $input Required. The value to be sanitized and validated.
	 * @return String. The input value, or the current value of the setting if input is not valid.
	 */
	public function sanitize_auth_key( $input ) {
		$auth_key = get_option( 'wpbol_auth_key' );
		$input = trim($input);
		$regex = "/^[a-zA-Z0-9]*$/";
		if (preg_match($regex, $input)) {
			return $input;
		}
		else {
			add_settings_error( 'wpbol_auth_key', 'wpbol_error', __('Invalid authentication key entered. Not updated.', 'wp_bolcom_affiliates') );
			return $auth_key;
		}
	}

	/**
	 * Input field for partner ID setting.
	 */
	public function admin_settings_partner_id () {
		$partner_id = get_option( 'wpbol_partner_id', '' );
		echo "<input type='text' id='wpbol_bolcom_id' name='wpbol_partner_id' value='{$partner_id}' />";
	}

	/**
	 * Input field for partner ID setting.
	 */
	public function admin_settings_auth_key () {
		$auth_key = get_option( 'wpbol_auth_key', '' );
		echo "<input type='text' id='wpbol_bolcom_auth_key' name='wpbol_auth_key' value='{$auth_key}' />";
	}

	/**
	 * Display a message if the plugin is not yet configured.
	 */
	public function configure_plugin_message() {
		$href = admin_url( 'options-general.php?page=wpbol_affiliates' );
		echo '<div class="updated"><p>';
		printf( __( 'Bol.com Partnerprogramma by Biz2Web is not yet configured. Configure it <a href="%1$s">right here</a>.', 'wp_bolcom_affiliates' ), $href );
		echo '</p></div>';
	}

	/**
	 * Add the settings page to the settings menu.
	 */
	public function admin_menu () {
		add_options_page( __('Bol.com Partnerprogramma Settings', 'wp_bolcom_affiliates'), __('Bol.com Partnerprogramma', 'wp_bolcom_affiliates' ), 'manage_options', 'wpbol_affiliates', Array( &$this, 'admin_settings' ) );
	}

	/**
	 * Require settings.php when the settings page is loaded.
	 */
	public function admin_settings () {
		require_once( WP_BOLCOM_BASE_PATH.'admin/settings.php' );
	}
}

WP_Bolcom_Affiliates::get_instance();
