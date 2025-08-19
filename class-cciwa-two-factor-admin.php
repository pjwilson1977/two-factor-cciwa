<?php
/**
 * CCIWA Two Factor Admin Settings
 *
 * @package Two_Factor
 */

/**
 * Class for CCIWA Two Factor admin settings management.
 *
 * @since 0.1-dev
 *
 * @package Two_Factor
 */
class CCIWA_Two_Factor_Admin {

	/**
	 * The admin settings option key.
	 *
	 * @type string
	 */
	const SETTINGS_OPTION_KEY = 'cciwa_two_factor_settings';

	/**
	 * Initialize admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'two_factor_providers', array( __CLASS__, 'filter_enabled_providers' ) );
		add_filter( 'two_factor_primary_provider_for_user', array( __CLASS__, 'set_default_provider' ), 10, 2 );
		
		// Add 2FA enforcement
		add_action( 'wp_login', array( __CLASS__, 'enforce_2fa_on_login' ), 5, 2 );
		add_action( 'admin_init', array( __CLASS__, 'enforce_2fa_admin_access' ), 5 );
		add_action( 'template_redirect', array( __CLASS__, 'enforce_2fa_frontend_access' ), 5 );
		add_action( 'admin_notices', array( __CLASS__, 'show_2fa_setup_notices' ) );
		add_action( 'show_user_profile', array( __CLASS__, 'show_profile_notices' ), 1 );
		add_action( 'edit_user_profile', array( __CLASS__, 'show_profile_notices' ), 1 );
	}

	/**
	 * Add admin menu item.
	 */
	public static function add_admin_menu() {
		add_options_page(
			__( 'CCIWA Two-Factor Settings', 'two-factor' ),
			__( 'Two-Factor Auth', 'two-factor' ),
			'manage_options',
			'cciwa-two-factor',
			array( __CLASS__, 'settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting( 'cciwa_two_factor_settings_group', self::SETTINGS_OPTION_KEY );

		add_settings_section(
			'cciwa_two_factor_methods_section',
			__( 'Available Two-Factor Methods', 'two-factor' ),
			array( __CLASS__, 'methods_section_callback' ),
			'cciwa-two-factor'
		);

		add_settings_field(
			'enabled_methods',
			__( 'Enabled Methods', 'two-factor' ),
			array( __CLASS__, 'enabled_methods_callback' ),
			'cciwa-two-factor',
			'cciwa_two_factor_methods_section'
		);

		add_settings_field(
			'require_2fa',
			__( 'Require Two-Factor Authentication', 'two-factor' ),
			array( __CLASS__, 'require_2fa_callback' ),
			'cciwa-two-factor',
			'cciwa_two_factor_methods_section'
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array(
			'enabled_methods' => array(
				'Two_Factor_Totp',
				'Two_Factor_Backup_Codes',
				'Two_Factor_Email',
			),
			'require_2fa' => false,
		);
	}

	/**
	 * Get current settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = get_option( self::SETTINGS_OPTION_KEY, self::get_default_settings() );
		return wp_parse_args( $settings, self::get_default_settings() );
	}

	/**
	 * Methods section callback.
	 */
	public static function methods_section_callback() {
		echo '<p>' . esc_html__( 'Choose which two-factor authentication methods are available to your users.', 'two-factor' ) . '</p>';
	}

	/**
	 * Enabled methods callback.
	 */
	public static function enabled_methods_callback() {
		$settings = self::get_settings();
		$enabled_methods = $settings['enabled_methods'];

		$available_methods = array(
			'Two_Factor_Totp' => __( 'Authentication App (Recommended)', 'two-factor' ),
			'Two_Factor_Backup_Codes' => __( 'Recovery Codes (Required)', 'two-factor' ),
			'Two_Factor_Email' => __( 'Email', 'two-factor' ),
		);

		foreach ( $available_methods as $method_key => $method_label ) {
			$checked = in_array( $method_key, $enabled_methods, true );
			$disabled = 'Two_Factor_Backup_Codes' === $method_key ? 'disabled' : '';
			?>
			<label>
				<input type="checkbox" 
					   name="<?php echo esc_attr( self::SETTINGS_OPTION_KEY ); ?>[enabled_methods][]" 
					   value="<?php echo esc_attr( $method_key ); ?>"
					   <?php checked( $checked ); ?>
					   <?php echo esc_attr( $disabled ); ?> />
				<?php echo esc_html( $method_label ); ?>
				<?php if ( 'Two_Factor_Backup_Codes' === $method_key ) : ?>
					<em><?php esc_html_e( '(Always enabled for security)', 'two-factor' ); ?></em>
				<?php endif; ?>
			</label><br />
			<?php
		}
	}

	/**
	 * Require 2FA callback.
	 */
	public static function require_2fa_callback() {
		$settings = self::get_settings();
		$require_2fa = $settings['require_2fa'];
		?>
		<label>
			<input type="checkbox" 
				   name="<?php echo esc_attr( self::SETTINGS_OPTION_KEY ); ?>[require_2fa]" 
				   value="1"
				   <?php checked( $require_2fa ); ?> />
			<?php esc_html_e( 'Require all users to set up two-factor authentication', 'two-factor' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, users will be required to set up 2FA before they can access the site.', 'two-factor' ); ?>
		</p>
		<?php
	}

	/**
	 * Filter enabled providers based on admin settings.
	 *
	 * @param array $providers List of available providers.
	 * @return array Filtered list of providers.
	 */
	public static function filter_enabled_providers( $providers ) {
		$settings = self::get_settings();
		$enabled_methods = $settings['enabled_methods'];

		// Always ensure backup codes are enabled for security
		if ( ! in_array( 'Two_Factor_Backup_Codes', $enabled_methods, true ) ) {
			$enabled_methods[] = 'Two_Factor_Backup_Codes';
		}

		// Filter providers to only include enabled ones
		$filtered_providers = array();
		foreach ( $providers as $provider_key => $provider_path ) {
			if ( in_array( $provider_key, $enabled_methods, true ) ) {
				$filtered_providers[ $provider_key ] = $provider_path;
			}
		}

		// Always exclude Dummy and FIDO U2F as they're not in our allowed list
		unset( $filtered_providers['Two_Factor_Dummy'] );
		unset( $filtered_providers['Two_Factor_FIDO_U2F'] );

		// Ensure we have at least one provider available (fallback to email if nothing else)
		if ( empty( $filtered_providers ) ) {
			$filtered_providers['Two_Factor_Email'] = $providers['Two_Factor_Email'];
			$filtered_providers['Two_Factor_Backup_Codes'] = $providers['Two_Factor_Backup_Codes'];
		}

		return $filtered_providers;
	}

	/**
	 * Set default provider to TOTP (Authentication App) if none is selected.
	 *
	 * @param string $provider The current provider.
	 * @param int    $user_id  The user ID.
	 * @return string The provider to use.
	 */
	public static function set_default_provider( $provider, $user_id ) {
		// If no provider is currently selected, try to set a sensible default
		if ( empty( $provider ) ) {
			$available_providers = Two_Factor_Core::get_available_providers_for_user( $user_id );
			
			// Prefer TOTP (Authentication App) as it's the most secure
			if ( isset( $available_providers['Two_Factor_Totp'] ) ) {
				return 'Two_Factor_Totp';
			}
			
			// Fall back to email if TOTP is not available
			if ( isset( $available_providers['Two_Factor_Email'] ) ) {
				return 'Two_Factor_Email';
			}
			
			// Last resort: backup codes (though they shouldn't be the primary method)
			if ( isset( $available_providers['Two_Factor_Backup_Codes'] ) ) {
				return 'Two_Factor_Backup_Codes';
			}
		}
		
		return $provider;
	}

	/**
	 * Check if 2FA enforcement is enabled.
	 *
	 * @return bool True if 2FA is required.
	 */
	public static function is_2fa_required() {
		$settings = self::get_settings();
		return ! empty( $settings['require_2fa'] );
	}

	/**
	 * Check if user should be exempted from 2FA requirement.
	 *
	 * @param WP_User $user User object.
	 * @return bool True if user is exempted.
	 */
	public static function is_user_exempted_from_2fa( $user ) {
		// Exempt users with 'manage_options' capability from enforcement
		// This prevents lockout of admins during setup
		return user_can( $user, 'manage_options' ) || apply_filters( 'cciwa_2fa_user_exempted', false, $user );
	}

	/**
	 * Enforce 2FA on user login.
	 *
	 * @param string  $user_login User login name.
	 * @param WP_User $user       User object.
	 */
	public static function enforce_2fa_on_login( $user_login, $user ) {
		if ( ! self::is_2fa_required() ) {
			return;
		}

		if ( self::is_user_exempted_from_2fa( $user ) ) {
			return;
		}

		if ( ! Two_Factor_Core::is_user_using_two_factor( $user ) ) {
			// Set a user meta flag to indicate 2FA setup is required
			update_user_meta( $user->ID, '_cciwa_2fa_setup_required', true );
		}
	}

	/**
	 * Enforce 2FA for admin access.
	 */
	public static function enforce_2fa_admin_access() {
		if ( ! is_user_logged_in() || ! self::is_2fa_required() ) {
			return;
		}

		$user = wp_get_current_user();
		
		if ( self::is_user_exempted_from_2fa( $user ) ) {
			return;
		}

		// Allow access to profile page for 2FA setup
		$allowed_pages = array( 'profile.php', 'user-edit.php' );
		$current_page = basename( $_SERVER['SCRIPT_NAME'] );
		
		if ( in_array( $current_page, $allowed_pages, true ) ) {
			return;
		}

		if ( ! Two_Factor_Core::is_user_using_two_factor( $user ) ) {
			self::redirect_to_2fa_setup( $user );
		}
	}

	/**
	 * Enforce 2FA for frontend access.
	 */
	public static function enforce_2fa_frontend_access() {
		if ( ! is_user_logged_in() || ! self::is_2fa_required() || is_admin() ) {
			return;
		}

		$user = wp_get_current_user();
		
		if ( self::is_user_exempted_from_2fa( $user ) ) {
			return;
		}

		if ( ! Two_Factor_Core::is_user_using_two_factor( $user ) ) {
			self::redirect_to_2fa_setup( $user );
		}
	}

	/**
	 * Redirect user to 2FA setup page.
	 *
	 * @param WP_User $user User object.
	 */
	public static function redirect_to_2fa_setup( $user ) {
		// Use frontend setup URL instead of admin
		$setup_url = CCIWA_Two_Factor_Frontend::get_setup_url();
		
		// Add notice to user meta
		$notices = get_user_meta( $user->ID, '_cciwa_2fa_notices', true );
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}
		
		if ( ! in_array( 'setup_required', $notices, true ) ) {
			$notices[] = 'setup_required';
			update_user_meta( $user->ID, '_cciwa_2fa_notices', $notices );
		}
		
		wp_redirect( $setup_url );
		exit;
	}

	/**
	 * Show 2FA setup notices in admin.
	 */
	public static function show_2fa_setup_notices() {
		if ( ! is_user_logged_in() || ! self::is_2fa_required() ) {
			return;
		}

		$user = wp_get_current_user();
		
		if ( self::is_user_exempted_from_2fa( $user ) ) {
			return;
		}

		if ( ! Two_Factor_Core::is_user_using_two_factor( $user ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Two-Factor Authentication Required', 'two-factor' ); ?></strong><br>
					<?php esc_html_e( 'You must set up two-factor authentication to secure your account.', 'two-factor' ); ?>
					<a href="<?php echo esc_url( admin_url( 'profile.php#two-factor-options' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
						<?php esc_html_e( 'Set Up Now', 'two-factor' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Show 2FA notices on profile page.
	 *
	 * @param WP_User $user User object.
	 */
	public static function show_profile_notices( $user ) {
		if ( ! self::is_2fa_required() ) {
			return;
		}

		if ( self::is_user_exempted_from_2fa( $user ) ) {
			return;
		}

		if ( ! Two_Factor_Core::is_user_using_two_factor( $user ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Two-Factor Authentication Required', 'two-factor' ); ?></strong><br>
					<?php esc_html_e( 'You must configure at least one two-factor authentication method below to secure your account and continue using this site.', 'two-factor' ); ?>
				</p>
			</div>
			<?php
		} else {
			?>
			<div class="notice notice-success">
				<p>
					<strong><?php esc_html_e( 'Two-Factor Authentication Active', 'two-factor' ); ?></strong><br>
					<?php esc_html_e( 'Your account is secured with two-factor authentication. You can manage your settings below.', 'two-factor' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Settings page content.
	 */
	public static function settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CCIWA Two-Factor Authentication Settings', 'two-factor' ); ?></h1>
			
			<form method="post" action="options.php">
				<?php
				settings_fields( 'cciwa_two_factor_settings_group' );
				do_settings_sections( 'cciwa-two-factor' );
				submit_button();
				?>
			</form>
			
			<h2><?php esc_html_e( 'About Two-Factor Authentication Methods', 'two-factor' ); ?></h2>
			<ul>
				<li><strong><?php esc_html_e( 'Authentication App:', 'two-factor' ); ?></strong> <?php esc_html_e( 'Users can use apps like Google Authenticator, Authy, or similar to generate time-based codes.', 'two-factor' ); ?></li>
				<li><strong><?php esc_html_e( 'Recovery Codes:', 'two-factor' ); ?></strong> <?php esc_html_e( 'One-time backup codes that users can use if they lose access to their primary 2FA method.', 'two-factor' ); ?></li>
				<li><strong><?php esc_html_e( 'Email:', 'two-factor' ); ?></strong> <?php esc_html_e( 'Verification codes sent to the user\'s registered email address.', 'two-factor' ); ?></li>
			</ul>
		</div>
		<?php
	}
}