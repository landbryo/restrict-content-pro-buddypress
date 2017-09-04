<?php

RCPBP_Setup::get_instance();
class RCPBP_Setup {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the RCPBP_Setup
	 *
	 * @return RCPBP_Setup
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof RCPBP_Setup ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'maybe_setup' ), -9999 );

		add_action( 'admin_init', array( $this, 'activate_license'   ), 100 );
		add_action( 'admin_init', array( $this, 'deactivate_license' ), 100 );
		add_action( 'admin_init', array( $this, 'register_settings' ), 50 );
		add_action( 'admin_menu', array( $this, 'admin_menu'        ), 50 );
		add_action( 'admin_notices', array( $this, 'license_admin_notice' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
	}

	public function maybe_setup() {
		if ( ! $this->check_required_plugins() ) {
			return;
		}

		$this->includes();
	}

	protected function includes() {
//		include_once( RCPBP_PATH . '/includes/components.php' );
		include_once( RCPBP_PATH . '/includes/member-types.php' );
		include_once( RCPBP_PATH . '/includes/profile.php' );
		include_once( RCPBP_PATH . '/includes/restricted-content.php' );
		include_once( RCPBP_PATH . '/includes/groups.php' );
		include_once( RCPBP_PATH . '/includes/admin/terms.php' );
	}

	/**
	 * Initialize admin scripts and styles
	 * 
	 * @param $hook
	 */
	public function admin_styles( $hook ) {
		global $rcp_members_page, $rcp_subscriptions_page, $rcp_discounts_page, $rcp_payments_page, $rcp_reports_page, $rcp_settings_page, $rcp_export_page, $rcp_logs_page, $rcp_help_page, $rcp_tools_page, $rcp_add_ons_page;
		$pages = array(
			$rcp_members_page,
			$rcp_subscriptions_page,
			$rcp_discounts_page,
			$rcp_payments_page,
			$rcp_reports_page,
			$rcp_settings_page,
			$rcp_export_page,
			$rcp_logs_page,
			$rcp_help_page,
			$rcp_tools_page,
			$rcp_add_ons_page,
			'post.php',
			'edit.php',
			'post-new.php'
		);

		wp_register_style( 'rcpbp-admin-css', RCPBP_URL . 'assets/css/admin-styles.css', array(), RCPBP_VERSION );
		wp_enqueue_script( 'rcpbp-admin-js', RCPBP_URL . 'assets/js/admin.js', array( 'jquery' ), RCPBP_VERSION );

		if ( in_array( $hook, $pages ) ) {
			wp_enqueue_style( 'rcpbp-admin-css' );
			wp_enqueue_script( 'rcpbp-admin-js' );
		}
	}

	/**
	 * Make sure RCP and BuddyPress are active
	 * @return bool
	 */
	protected function check_required_plugins() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_active( 'restrict-content-pro/restrict-content-pro.php' ) && is_plugin_active( 'buddypress/bp-loader.php' ) ) {
			return true;
		}

		add_action( 'admin_notices', array( $this, 'required_plugins' ) );
		return false;
	}

	/**
	 * Required Plugins notice
	 */
	public function required_plugins() {
		printf( '<div class="error"><p>%s</p></div>', __( 'Restrict Content Pro and BuddyPress are both required for the Restrict Content Pro BuddyPress add-on to function.', 'rcpbp' ) );
	}

	/**
	 * Register the RCP BP settings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function register_settings() {
		register_setting( 'rcpbp_settings_group', 'rcpbp_license_key', array( $this, 'sanitize_license' ) );
	}

	/**
	 * Add the BuddyPress menu item
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function admin_menu() {
		add_submenu_page( 'rcp-members', __( 'BuddyPress Settings', 'rcpbp' ), __( 'BuddyPress', 'rcpbp' ), 'manage_options', RCPBP_PLUGIN_LICENSE_PAGE, array( $this, 'settings_page' ) );
	}

	public function settings_page() {
		$license  = get_option( 'rcpbp_license_key', '' );
		$status   = get_option( 'rcpbp_license_status', '' );

		if ( isset( $_REQUEST['updated'] ) && $_REQUEST['updated'] !== false ) : ?>
			<div class="updated fade"><p><strong><?php _e( 'Options saved', 'rcpbp' ); ?></strong></p></div>
		<?php endif; ?>

		<div class="rcpbp-wrap">

			<h2 class="rcpbp-settings-title"><?php echo esc_html( get_admin_page_title() ); ?></h2><hr>

			<form method="post" action="options.php" class="rcp_options_form">
				<?php settings_fields( 'rcpbp_settings_group' ); ?>

				<table class="form-table">
					<tr>
						<th>
							<label for="rcpbp_license_key"><?php _e( 'License Key', 'rcpbp' ); ?></label>
						</th>
						<td>
							<p><input class="regular-text" type="text" id="rcpbp_license_key" name="rcpbp_license_key" value="<?php echo esc_attr( $license ); ?>" />
							<?php if( $status == 'valid' ) : ?>
								<?php wp_nonce_field( 'rcpbp_deactivate_license', 'rcpbp_deactivate_license' ); ?>
								<?php submit_button( 'Deactivate License', 'secondary', 'rcpbp_license_deactivate', false ); ?>
								<span style="color:green">&nbsp;&nbsp;<?php _e( 'active', 'rcpbp' ); ?></span>
							<?php else : ?>
								<?php submit_button( 'Activate License', 'secondary', 'rcpbp_license_activate', false ); ?>
							<?php endif; ?></p>

							<p class="description"><?php printf( __( 'Enter your Restrict Content Pro - BuddyPress license key. This is required for automatic updates and <a href="%s">support</a>.', 'rcpbp' ), 'https://tannermoushey.com/support' ); ?></p>
						</td>
					</tr>

				</table>

				<?php settings_fields( 'rcpbp_settings_group' ); ?>
				<?php wp_nonce_field( 'rcpbp_nonce', 'rcpbp_nonce' ); ?>
				<?php submit_button( 'Save Options' ); ?>

			</form>
		</div>

	<?php
	}


	public function sanitize_license( $new ) {
		$old = get_option( 'rcpbp_license_key' );
		if ( $old && $old != $new ) {
			delete_option( 'rcpbp_license_key' ); // new license has been entered, so must reactivate
		}

		return $new;
	}

	public function activate_license() {

		// listen for our activate button to be clicked
		if ( ! isset( $_POST['rcpbp_license_activate'], $_POST['rcpbp_nonce'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'rcpbp_nonce', 'rcpbp_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'rcpbp_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( RCPBP_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( RCPBP_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.' );
			}

		} else {

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch( $license_data->error ) {

					case 'expired' :

						$message = sprintf(
							__( 'Your license key expired on %s.' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'revoked' :

						$message = __( 'Your license key has been disabled.' );
						break;

					case 'missing' :

						$message = __( 'Invalid license.' );
						break;

					case 'invalid' :
					case 'site_inactive' :

						$message = __( 'Your license is not active for this URL.' );
						break;

					case 'item_name_mismatch' :

						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), RCPBP_ITEM_NAME );
						break;

					case 'no_activations_left':

						$message = __( 'Your license key has reached its activation limit.' );
						break;

					default :

						$message = __( 'An error occurred, please try again.' );
						break;
				}

			}
		}

		delete_transient( 'rcpbp_license_check' );

		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
			$base_url = admin_url( 'admin.php?page=' . RCPBP_PLUGIN_LICENSE_PAGE );
			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

			wp_redirect( $redirect );
			exit();
		}

		// $license_data->license will be either "valid" or "invalid"
		update_option( 'rcpbp_license_status', $license_data->license );
		wp_redirect( admin_url( 'admin.php?page=' . RCPBP_PLUGIN_LICENSE_PAGE ) );
		exit();

	}

	public function deactivate_license() {

		// listen for our activate button to be clicked
		if ( ! isset( $_POST['rcpbp_license_deactivate'], $_POST['rcpbp_nonce'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'rcpbp_nonce', 'rcpbp_nonce' ) ) {
			return;
		}

		// retrieve the license from the database
		$license = trim( get_option( 'rcpbp_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( RCPBP_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( RCPBP_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.' );
			}

			$base_url = admin_url( 'admin.php?page=' . RCPBP_PLUGIN_LICENSE_PAGE );
			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

			wp_redirect( $redirect );
			exit();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' ) {
			delete_option( 'rcpbp_license_status' );
			set_transient( 'rcpbp_license_check', $license_data->license, DAY_IN_SECONDS );
		}

		wp_redirect( admin_url( 'admin.php?page=' . RCPBP_PLUGIN_LICENSE_PAGE ) );
		exit();

	}

	/**
	 * This is a means of catching errors from the activation method above and displaying it to the customer
	 */
	public function license_admin_notice() {
		if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

			switch ( $_GET['sl_activation'] ) {

				case 'false':
					$message = urldecode( $_GET['message'] );
					?>
					<div class="error">
						<p><?php echo $message; ?></p>
					</div>
					<?php
					break;

				case 'true':
				default:
					// Developers can put a custom success message here for when activation is successful if they way.
					break;

			}
		}
	}

}

/**
 * Check license
 *
 * @since       1.0.0
 */
function rcpbp_check_license() {
	// Don't fire when saving settings
	if( ! empty( $_POST['rcpbp_nonce'] ) ) {
		return;
	}

	$license = get_option( 'rcpbp_license_key' );
	$status  = get_transient( 'rcpbp_license_check' );

	if( $status === false && $license ) {

		$api_params = array(
			'edd_action'    => 'check_license',
			'license'       => trim( $license ),
			'item_name'     => urlencode( RCPBP_ITEM_NAME ),
			'url'           => home_url()
		);

		$response = wp_remote_post( RCPBP_STORE_URL, array( 'timeout' => 35, 'sslverify' => false, 'body' => $api_params ) );

		if( is_wp_error( $response ) ) {
			return;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		$status = $license_data->license;

		update_option( 'rcpbp_license_status', $status );

		set_transient( 'rcpbp_license_check', $license_data->license, DAY_IN_SECONDS );

		if( $status !== 'valid' ) {
			delete_option( 'rcpbp_license_status' );
		}
	}

}
add_action( 'admin_init', 'rcpbp_check_license' );