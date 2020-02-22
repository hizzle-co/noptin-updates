<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://noptin.com/
 * @since             1.0.0
 * @package           Noptin_Updates
 *
 * @wordpress-plugin
 * Plugin Name:       Noptin Updates
 * Plugin URI:        https://noptin.com/noptin-updates/
 * Description:       Update plugins and themes provided by Noptin.
 * Version:           1.0.2
 * Author:            Noptin Support
 * Author URI:        https://noptin.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       noptin-updates
 * Domain Path:       /languages
 * Requires at least: 4.9
 * Tested up to: 5.3
 * 
 * Text Domain: noptin-updates
 * Domain Path: /languages/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class.
 */
class Noptin_Updates {

	/**
	 * Noptin updates checker url.
	 * @var string
	 */
	public static $api_url = 'http://localhost/wpi/wp-json/api/v1/';//'https://noptin.com/wp-json/api/v1/';

	/**
	 * Noptin.com user key.
	 * @var string|null
	 */
	public $user_key = null;

	/**
	 * Main Plugin instance.
	 *
	 * @access      private
	 * @var         Noptin_Updates $instance The main plugin instance
	 * @since       1.0.0
	 */
	private static $instance = null;

	/**
	 * Get active instance
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      Noptin_Updates
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set up hooks.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->user_key = get_option( 'noptin_updates_user_key' );

		add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
		add_filter( 'themes_api', array( $this, 'themes_api_filter' ), 10, 3 );
		add_filter( 'extra_plugin_headers', array( $this, 'add_extra_package_headers' ) );
		add_filter( 'extra_theme_headers', array( $this, 'add_extra_package_headers' ) );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_plugin_updates' ) );
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'check_for_theme_updates' ) );
		add_action( 'plugin_action_links', array( $this, 'render_plugin_action_links' ), 10, 4 );
		add_filter( 'wp_prepare_themes_for_js', array( $this, 'add_theme_licence_actions' ) );
		add_filter( 'site_transient_' . 'update_plugins', array( $this, 'change_update_information' ) );
		add_action( 'plugins_loaded', array( $this, 'add_notice_unlicensed_product' ), 10, 4 );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ), 10, 4 );

	}

	/**
	 * Load the plugin textdomain from the main WordPress "languages" folder.
	 * @since  1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'noptin-updates';
	    // The "plugin_locale" filter is also used in load_plugin_textdomain()
	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Filters the response for the current WordPress.org Plugin Installation API request.
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The type of information being requested from the Plugin Installation API.
	 * @param object             $args   ( [slug] => [is_ssl] => [fields] => Array ( [banners] => 1 [reviews] => 1 [downloaded] => [active_installs] => 1 ) [per_page] => 24 [locale] => en_US )
	 */
	public function plugins_api_filter( $result, $action, $args ) {

		// do nothing if this is not about getting plugin information.
		if ( $action != 'plugin_information' || ! isset( $args->slug ) ) {
			return $result;
		}

		// Ensure this is a noptin product.
		$noptin_id = $this->get_noptin_product_id( 'plugin', $args->slug );
		if ( empty( $noptin_id ) ) {
			return $result;
		}

		$noptin_id= trim( $noptin_id );
		$url      = $this->get_api_url( "plugin-info/$noptin_id" );

		if ( $this->has_product_license( $noptin_id ) ) {
			$url  = add_query_arg( 'license_key', $this->get_product_license( $noptin_id ), $url );
		}

		// Retrieve product information.
		$response = $this->process_api_response( wp_remote_get( $url ) );

		// Did it work?
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
                'plugins_api_failed',
                sprintf(
                    /* translators: %s: contact page URL. */
                    __( 'An unexpected error occurred. Something may be wrong with Noptin.com or this server&#8217;s configuration. If you continue to have problems, please try <a href="%s">contacting us</a>.', 'noptin-updates' ),
                    __( 'https://noptin.com/contact/' )
                ),
                $response->get_error_message()
            );
		}

		// Prepare the response.
		$res = json_decode( $response, true );

		if ( null === $res ) {
			return new WP_Error(
				'plugins_api_failed',
				sprintf(
					/* translators: %s: contact page URL. */
					__( 'An unexpected error occurred. Something may be wrong with Noptin.com or this server&#8217;s configuration. If you continue to have problems, please try <a href="%s">contacting us</a>.', 'noptin-updates' ),
					__( 'https://noptin.com/contact/' )
				),
				$response
			);
		}

		if ( is_array( $res ) ) {
			$res = (object) $res;
		}

		$res->slug = $args->slug;
		return $res;


	}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @uses api_request()
	 *
	 * @param mixed $_data
	 * @param string $_action
	 * @param object $_args
	 *
	 * @return object $_data
	 */
	public function themes_api_filter( $_data, $_action = '', $_args = null ) {

		// do nothing if this is not about getting plugin information.
		if ( $_action != 'theme_information' || ! isset( $_args->slug ) ) {
			return $_data;
		}

		// Ensure this is a noptin product.
		$noptin_id = $this->get_noptin_product_id( 'theme', $_args->slug );
		if ( empty( $noptin_id ) ) {
			return $_data;
		}

		$url      = $this->get_api_url( "theme-info/$noptin_id" );

		if ( $this->has_product_license( $noptin_id ) ) {
			$url  = add_query_arg( 'license_key', $this->get_product_license( $noptin_id ), $url );
		}

		// Retrieve product information.
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'themes_api_failed',
                sprintf(
                    /* translators: %s: contact page URL. */
                    __( 'An unexpected error occurred. Something may be wrong with Noptin.com or this server&#8217;s configuration. If you continue to have problems, please try <a href="%s">contacting us</a>.', 'noptin-updates' ),
                    __( 'https://noptin.com/contact/' )
                ),
                $response->get_error_message()
            );
        }

		// Prepare the response.
		$res = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( null === $res ) {
			return new WP_Error(
				'themes_api_failed',
				sprintf(
					/* translators: %s: contact page URL. */
					__( 'An unexpected error occurred. Something may be wrong with Noptin.com or this server&#8217;s configuration. If you continue to have problems, please try <a href="%s">contacting us</a>.', 'noptin-updates' ),
					__( 'https://noptin.com/contact/' )
				),
				wp_remote_retrieve_body( $response )
			);
		}

		if ( is_array( $res ) ) {
			$res = (object) $res;
		}

		if ( isset( $res->error ) ) {
			return new WP_Error( 'themes_api_failed', $res->error );
		}

		$res->slug = $_args->slug;
		return $res;
	}

	/**
	 * Retrieves Noptin plugins and themes product ids.
	 *
	 */
	public function get_noptin_product_id( $type = 'plugin', $slug ) {
		$noptin_packages    = $this->get_packages( $type );

		// If slug is plugin-folder/plugin-file.php
		if ( isset( $noptin_packages[ $slug ] ) ) {
			return $noptin_packages[ $slug ]['Noptin ID'];
		}

		// If slug is plugin-folder
		foreach ( array_keys( $noptin_packages ) as $key ) {
			if ( strtok( wp_normalize_path( $key ), '/' ) == $slug ) {
				return $noptin_packages[ $key ]['Noptin ID'];
			}
		}

		return false;
	}

	/**
	 * Retrieves Noptin plugins or themes.
	 *
	 */
	public function get_packages( $type = 'plugin' ) {

		$all_packages    = ( $type === 'theme' ) ? wp_get_themes() : get_plugins();
		$noptin_packages = array();
		foreach ( $all_packages as $key => $package ) {
			if ( ! empty( $package['Noptin ID'] ) ) {
				$noptin_packages[$key] = $package;
			}
		}

		return $noptin_packages;
	}

	/** 
	 * Returns the user's noptin.com user key.
	 *
	 */
	public function get_noptin_user_key() {
		return $this->user_key;
	}

	/**
	 * Updates the user's noptin.com user key.
	 *
	 */
	public function update_noptin_user_key( $new_key ) {
		$this->user_key = $new_key;
		update_option( 'noptin_updates_user_key', $new_key );
	}

	/**
	 * Checks if there is a valid noptin.com user key.
	 *
	 */
	public function has_noptin_user_key() {
		return ! empty( $this->user_key );
	}

	/**
	 * Returns api url
	 *
	 */
	public function get_api_url( $append = '', $args = array() ) {
		
		$args['home_url'] = get_home_url();
		if ( $this->has_noptin_user_key() ) {
			$args['api_key'] = $this->user_key;
		}
		return add_query_arg( $args, self::$api_url . $append );

	}

	/**
	 * Return's all licenses for the current user.
	 * 
	 * @return WP_Error|null|array
	 */
	public function get_all_licenses() {
		$licenses = get_transient( 'noptin_updates_license_cache' );

		if ( is_array ( $licenses ) ) {
			return $licenses;
		}

		$response = wp_remote_get( $this->get_api_url( 'licenses' ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		
		$licenses = json_decode( wp_remote_retrieve_body( $response ) );
		set_transient( 'noptin_updates_license_cache', $licenses, 10 * MINUTE_IN_SECONDS );
		return $licenses;

	}

	/**
	 * Return's all licenses that have been activated on this website.
	 *
	 */
	public function get_active_licenses() {
		$licenses = get_option( 'noptin_updates_licenses' );
		return is_array( $licenses ) ? $licenses : array();
	}

	/**
	 * Store license
	 *
	 */
	public function store_activated_license( $license, $product ) {
		$licenses           = $this->get_active_licenses();
		$licenses[$product] = $license;
		update_option( 'noptin_updates_licenses', $licenses );
	}

	/**
	 * Returns a given product's license
	 *
	 */
	public function get_product_license( $product ) {
		$licenses = $this->get_active_licenses();
		return isset( $licenses[ $product ] ) ? $licenses[ $product ] : '';
	}

	/**
	 * Checks if a product's license is active.
	 *
	 */
	public function has_product_license( $product ) {
		$license = $this->get_product_license( $product );
		return ! empty( $license );
	}

	/**
	 * Returns a given product's license
	 *
	 * @return WP_Error|bool
	 */
	public function activate_product_license( $product, $license ) {

		// Skip if the product is already activated on this site.
		if ( $this->has_product_license( $product ) ) {
			return true;
		}

		// Activate the product remotely.
		$url      = $this->get_api_url( "licenses/activate/$license/$product" );
		$response = wp_remote_get( add_query_arg( 'home_url', urlencode( get_home_url() ), $url ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$this->store_activated_license( $license, $product );
		
		return true;
	}

	/**
	 * Adds our own paramiters to the plugin header DocBlock info.
	 *
	 * @since 1.0.0
	 * @param array $headers The plugin header info array.
	 * @return array The plugin header array info.
	 */
	public function  add_extra_package_headers( $headers ){
		$headers[] = 'Noptin ID';
		return $headers;
	}

	/**
	 * Check for plugin updates.
	 *
	 * @param $_transient_data
	 *
	 * @return array|stdClass
	 */
	public function check_for_plugin_updates( $_transient_data ) {
		return $this->check_for_updates( $_transient_data, 'plugin' );
	}

	/**
	 * Check for theme updates.
	 *
	 * @param $_transient_data
	 *
	 * @return array|stdClass
	 */
	public function check_for_theme_updates( $_transient_data ) {
		return $this->check_for_updates( $_transient_data, 'theme' );
	}

	/**
	 * Check for plugin updates by source.
	 *
	 * @param array $_transient_data
	 *
	 * @return array|stdClass
	 */
	public function check_for_updates( $_transient_data, $type ) {

		$packages = $this->get_packages( $type );
		$licenses = $this->get_active_licenses();

		// Maybe abort early.
		if ( empty( $packages ) || ! is_object( $_transient_data )  ) {
			return $_transient_data;
		}

		$request_args   = array(
			'home_url'  => home_url(),
			'products'  => wp_list_pluck( $packages, 'Noptin ID' ),
			'licenses'  => $licenses,
		);

		// Retrieve version information.
		$response = $this->process_api_response( wp_remote_get( $this->get_api_url( "version-info", $request_args ) ) );

		if ( is_wp_error( $response ) ) {
			return $_transient_data;
		}

		$versions = (array) json_decode( $response );

		foreach ( $versions as $name => $details ) {
			if ( isset( $details->new_version ) && version_compare( $packages[ $name ]['Version'], $details->new_version, '<' ) ) {
				$_transient_data->response[ $name ] = ( $type == 'theme' ) ? (array) $details : $details;
				$_transient_data->checked[ $name ]  = $packages[ $name ]['Version'];
			}
		}

		return $_transient_data;
	}

	/**
	 * Renders the link for the row actions on the plugins page.
	 *
	 * @since 1.0
	 *
	 * @param array $actions An array of row action links.
	 *
	 * @return array
	 */
	public function render_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {

		if ( ! empty( $plugin_data['Noptin ID'] ) ) {
			$actions[] = $this->render_licence_actions($plugin_file, 'plugin');
		}

		return $actions;
	}

	/**
	 * Adds the theme licence actions to the theme description.
	 *
	 * @since 1.1.0
	 * @param array $prepared_themes The array of theme info.
	 * @return array The modified theme array info.
	 */
	public function add_theme_licence_actions( $prepared_themes ){

		$themes  = array_keys( $this->get_packages( 'theme' ) );

		foreach ( $themes as $key ){
			if ( isset( $prepared_themes[$key] ) ){
				$prepared_themes[$key]['description'] = $this->render_licence_actions( $key, 'theme' ). $prepared_themes[$key]['description'];
			}
		}

		return $prepared_themes;
	}

	/**
	 * Builds the frontend html code to activate and deactivate licences.
	 *
	 * @param string $slug The plugin/theme slug or filename.
	 * @param string $type The type of package, `plugin` or `theme`.
	 * @return string The html to output.
	 */
	public function render_licence_actions($slug, $type, $item_ids = array()){

		$ajax_nonce = wp_create_nonce( 'noptin_updates' );
		$licenses   = $this->get_active_licenses();

		if ( isset( $licenses[ $slug ] ) && $licenses[ $slug ]->key ) {

			$key                = sanitize_text_field( $licenses[ $slug ]->key );
			$deactivate_display = "";
			$activate_display   = " display:none;";
			$key_disabled       = "disabled";
			$licence_class      = "external-updates-active";
			$licence_notice_class = "";

		} else {
			$deactivate_display = " display:none; ";
			$activate_display   = "";
			$key                = '';
			$key_disabled       = '';
			$licence_class      = '';
			$licence_notice_class = "notice-warning";
		}

		$html = '';

		if($type=='plugin'){
			// activate link
			$html .= '<a href="javascript:void(0);" class="external-updates-licence-toggle ' . $licence_class . '" onclick="exup_enter_licence_key(this);" >' . _x( 'Licence key', 'Plugin action link label.', 'external-updates' ) . '</a>';

			// add licence activation html
			$html .= '<div class="external-updates-key-input" style="display:none;">';
			$html .= '<p>';
			$html .= '<input ' . $key_disabled . ' type="text" value="' . $key . '" class="external-updates-key-value" placeholder="' . __( 'Enter your licence key', 'external-updates' ) . '" />';
			$html .= '<span style="' . $deactivate_display . '" class="button-primary" onclick="exup_deactivate_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\');">' . __( 'Deactivate', 'external-updates' ) . '</span>';
			$html .= '<span style="' . $activate_display . '" class="button-primary" onclick="exup_activate_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\');">' . __( 'Activate', 'external-updates' ) . '</span>';
			$html .= '</p>';
			$html .= '</div>';
		}elseif($type=='theme'){


			$html .= '<div class="notice '.$licence_notice_class.' notice-success notice-alt notice-large wpeu-theme-notice">';
			$html .= '<p>'. __( 'A valid licence key is required to enable automatic updates.', 'external-updates' ) .'</p>';

			// add licence activation html
			$html .= '<div class="external-updates-key-input" >';
			$html .= '<p>';
			$html .= '<input ' . $key_disabled . ' type="text" value="' . $key . '" class="external-updates-key-value" placeholder="' . __( 'Enter your licence key', 'external-updates' ) . '" />';
			$html .= '<span style="' . $deactivate_display . '" class="button-primary" onclick="exup_deactivate_theme_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\');">' . __( 'Deactivate', 'external-updates' ) . '</span>';
			$html .= '<span style="' . $activate_display . '" class="button-primary" onclick="exup_activate_theme_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\');">' . __( 'Activate', 'external-updates' ) . '</span>';
			$html .= '</p>';
			$html .= '</div>';

			$html .= '</div>';

		}elseif($type=='membership'){
			// activate link
			//$html .= '<a href="javascript:void(0);" class="external-updates-licence-toggle ' . $licence_class . '" onclick="exup_enter_licence_key(this);" >' . _x( 'Licence key', 'Plugin action link label.', 'external-updates' ) . '</a>';

			// add licence activation html
			$html .= '<p>';
			$html .= '<input ' . $key_disabled . ' type="text" value="' . $key . '" class="external-updates-key-value" placeholder="' . __( 'Enter your licence key', 'external-updates' ) . '" />';
			$html .= '<span style="' . $deactivate_display . '" class="button-primary" onclick="exup_deactivate_membership_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\',\'' . implode(",",$item_ids) . '\');">' . __( 'Deactivate', 'external-updates' ) . '</span>';
			$html .= '<span style="' . $activate_display . '" class="button-primary" onclick="exup_activate_membership_licence_key(this,\'' . $slug . '\',\'' . $ajax_nonce . '\',\'' . implode(",",$item_ids) . '\');">' . __( 'Activate', 'external-updates' ) . '</span>';
			$html .= '</p>';
		}


		return $html;


	}

	/**
	 * Add action for queued products to display message for unlicensed products.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function add_notice_unlicensed_product () {
		if ( function_exists( 'get_plugins' ) ) {
			foreach ( array_keys( $this->get_packages() ) as $key ) {
				add_action( 'in_plugin_update_message-' . $key, array( $this, 'need_license_message' ), 10, 2 );
			}
		}
	} // End add_notice_unlicensed_product()

	/**
	 * Message displayed if license not activated
	 * @param  array $plugin_data
	 * @param  object $r
	 * @return void
	 */
	public function need_license_message ( $plugin_data, $r ) {
		if ( empty( $r->package ) ) {
			echo wp_kses_post( '<div class="noptin-updates-plugin-upgrade-notice">' . __( 'To update please enter your license by visiting the Dashboard > Noptin Updates screen.', 'noptin-updates' ) . '</div>' );
		}
	} // End need_license_message()

	/**
	 * Processes API responses
	 * @return WP_Error
	 */
	public function process_api_response ( $response ) {

		if( is_wp_error( $response ) ) {
			return $response;
		}
	
		$res = json_decode( wp_remote_retrieve_body( $response ) );
		if( isset( $res->code ) && isset( $res->message ) ) {
			return new WP_Error( $res->code, $res->message, $res );
		}

		return  wp_remote_retrieve_body( $response );
	} // End need_license_message()

	/**
	 * Change the update information for unlicense Noptin products
	 * @param  object $transient The update-plugins transient
	 * @return object
	 */
	public function change_update_information ( $transient ) {
		//If we are on the update core page, change the update message for unlicensed products
		global $pagenow;
		if ( ( 'update-core.php' == $pagenow ) && $transient && isset( $transient->response ) && ! isset( $_GET['action'] ) ) {

			$notice_text = __( 'To update please enter your license by visiting the Dashboard > Noptin Updates screen.' , 'noptin-updates' );

			foreach ( $this->get_packages() as $key => $value ) {
				if( isset( $transient->response[ $key ] ) && empty( $transient->response[ $key ]->package ) ){
					$message = '<div class="noptin-updates-plugin-upgrade-notice">' . $notice_text . '</div>';
					$transient->response[ $key ]->upgrade_notice = wp_kses_post( $message );
				}
			}
		}

		return $transient;
	} // End change_update_information()

}

if ( is_admin() ) {
	Noptin_Updates::instance();
}

// /repos/:owner/:repo/releases/latest https://developer.github.com/v3/repos/releases/#get-the-latest-release

// body created_at tag_name zipball_url author->avatar_url author->html_url assets[0]->browser_download_url 