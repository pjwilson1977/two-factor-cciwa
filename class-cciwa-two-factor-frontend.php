<?php
/**
 * CCIWA Two Factor Frontend Setup
 *
 * @package Two_Factor
 */

/**
 * Class for handling frontend Two Factor setup.
 *
 * @since 0.1-dev
 *
 * @package Two_Factor
 */
class CCIWA_Two_Factor_Frontend {

	/**
	 * Initialize frontend setup hooks.
	 */
	public static function init() {
		// Add shortcode for frontend 2FA setup
		add_shortcode( 'cciwa_two_factor_setup', array( __CLASS__, 'render_setup_shortcode' ) );
		
		// Handle frontend form submissions
		add_action( 'template_redirect', array( __CLASS__, 'handle_setup_actions' ) );
		
		// Enqueue styles and scripts
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		
		// Create virtual page for 2FA setup
		add_action( 'init', array( __CLASS__, 'setup_virtual_page' ) );
		
		// Handle the template loading
		add_action( 'template_redirect', array( __CLASS__, 'handle_template_redirect' ) );
	}

	/**
	 * Setup virtual page for 2FA setup.
	 */
	public static function setup_virtual_page() {
		global $wp;
		
		// Add our custom endpoint
		add_rewrite_rule( '^two-factor-setup/?', 'index.php?two_factor_setup=1', 'top' );
		
		// Register query var
		$wp->add_query_var( 'two_factor_setup' );
		
		// Check if we need to flush rewrite rules (for first-time setup)
		if ( ! get_option( 'cciwa_2fa_rewrite_flushed' ) ) {
			flush_rewrite_rules();
			update_option( 'cciwa_2fa_rewrite_flushed', true );
		}
	}

	/**
	 * Handle template redirect for 2FA setup page.
	 */
	public static function handle_template_redirect() {
		if ( get_query_var( 'two_factor_setup' ) ) {
			self::load_setup_template( '' );
		}
	}

	/**
	 * Load the setup template.
	 *
	 * @param string $template Current template.
	 * @return string Template to use.
	 */
	public static function load_setup_template( $template ) {
		$template_file = plugin_dir_path( __FILE__ ) . 'templates/two-factor-setup.php';
		
		if ( file_exists( $template_file ) ) {
			include $template_file;
			exit;
		}
		
		// Fallback to shortcode rendering if template doesn't exist
		if ( ! is_user_logged_in() ) {
			wp_redirect( wp_login_url( self::get_setup_url() ) );
			exit;
		}
		
		// Simple HTML fallback
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Two-Factor Authentication Setup', 'two-factor' ); ?></title>
			<?php wp_head(); ?>
			<style>
				body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f1f1f1; }
				.container { max-width: 800px; margin: 0 auto; }
			</style>
		</head>
		<body class="two-factor-setup-body">
			<div class="container">
				<h1><?php esc_html_e( 'Two-Factor Authentication Setup', 'two-factor' ); ?></h1>
				<?php echo self::render_setup_shortcode(); ?>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Get the frontend 2FA setup URL.
	 *
	 * @return string Setup URL.
	 */
	public static function get_setup_url() {
		return home_url( '/two-factor-setup/' );
	}

	/**
	 * Enqueue necessary scripts and styles.
	 */
	public static function enqueue_scripts() {
		// Only enqueue on 2FA setup pages
		if ( self::is_setup_page() ) {
			wp_enqueue_style( 
				'cciwa-two-factor-frontend', 
				plugins_url( 'assets/frontend-2fa.css', __FILE__ ), 
				array(), 
				TWO_FACTOR_VERSION 
			);
			
			wp_enqueue_script(
				'cciwa-two-factor-frontend',
				plugins_url( 'assets/frontend-2fa.js', __FILE__ ),
				array( 'jquery' ),
				TWO_FACTOR_VERSION,
				true
			);
		}
	}

	/**
	 * Check if we're on a 2FA setup page.
	 *
	 * @return bool True if on setup page.
	 */
	private static function is_setup_page() {
		return get_query_var( 'two_factor_setup' ) || 
			   ( is_page() && has_shortcode( get_post()->post_content, 'cciwa_two_factor_setup' ) );
	}

	/**
	 * Render the 2FA setup shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered content.
	 */
	public static function render_setup_shortcode( $atts = array() ) {
		// Only show to logged-in users
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'You must be logged in to set up two-factor authentication.', 'two-factor' ) . '</p>';
		}

		$user = wp_get_current_user();
		$providers = Two_Factor_Core::get_supported_providers_for_user( $user );
		$enabled_providers = Two_Factor_Core::get_enabled_providers_for_user( $user );
		$available_providers = Two_Factor_Core::get_available_providers_for_user( $user );

		// Check if user already has 2FA setup
		$already_setup = Two_Factor_Core::is_user_using_two_factor( $user );

		ob_start();
		?>
		<div class="cciwa-two-factor-setup">
			<div class="two-factor-container">
				<?php if ( $already_setup ) : ?>
					<div class="two-factor-success">
						<h3><?php esc_html_e( 'Two-Factor Authentication Active', 'two-factor' ); ?></h3>
						<p><?php esc_html_e( 'Your account is already secured with two-factor authentication.', 'two-factor' ); ?></p>
						<a href="<?php echo esc_url( home_url() ); ?>" class="button button-primary">
							<?php esc_html_e( 'Continue to Site', 'two-factor' ); ?>
						</a>
					</div>
				<?php else : ?>
					<div class="two-factor-setup-form">
						<h3><?php esc_html_e( 'Set Up Two-Factor Authentication', 'two-factor' ); ?></h3>
						
						<?php if ( isset( $_GET['setup_error'] ) ) : ?>
							<div class="two-factor-error">
								<p><strong><?php esc_html_e( 'Setup Error:', 'two-factor' ); ?></strong> <?php esc_html_e( 'Please ensure you have selected and properly configured at least one two-factor authentication method.', 'two-factor' ); ?></p>
							</div>
						<?php endif; ?>
						
						<p><?php esc_html_e( 'Secure your account by setting up two-factor authentication. Choose at least one method below:', 'two-factor' ); ?></p>
						
						<?php if ( empty( $available_providers ) ) : ?>
							<div class="two-factor-error">
								<p><?php esc_html_e( 'No two-factor authentication methods are available for your account. Please contact the site administrator.', 'two-factor' ); ?></p>
							</div>
						<?php else : ?>
							<form method="post" action="<?php echo esc_url( self::get_setup_url() ); ?>">
								<?php wp_nonce_field( 'user_two_factor_options', '_nonce_user_two_factor_options', true ); ?>
								<input type="hidden" name="action" value="setup_2fa" />
								
								<?php self::render_provider_options( $user, $providers ); ?>
								
								<div class="two-factor-submit">
									<input type="submit" value="<?php esc_attr_e( 'Save Two-Factor Settings', 'two-factor' ); ?>" class="button button-primary" />
									<p class="description"><?php esc_html_e( 'You must set up at least one two-factor method to continue using this site.', 'two-factor' ); ?></p>
								</div>
							</form>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render provider options for frontend setup.
	 *
	 * @param WP_User $user The user object.
	 * @param array   $providers Available providers.
	 */
	private static function render_provider_options( $user, $providers ) {
		$enabled_providers = Two_Factor_Core::get_enabled_providers_for_user( $user );
		$primary_provider_key = get_user_meta( $user->ID, Two_Factor_Core::PROVIDER_USER_META_KEY, true );

		?>
		<div class="two-factor-providers">
			<?php foreach ( $providers as $provider_key => $provider ) : ?>
				<?php if ( ! $provider->is_supported() ) continue; ?>
				<div class="two-factor-provider">
					<div class="provider-header">
						<label for="<?php echo esc_attr( $provider_key ); ?>">
							<input type="checkbox" 
								   name="<?php echo esc_attr( Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY ); ?>[]" 
								   id="<?php echo esc_attr( $provider_key ); ?>"
								   value="<?php echo esc_attr( $provider_key ); ?>"
								   <?php checked( in_array( $provider_key, $enabled_providers, true ) ); ?> />
							<strong><?php echo esc_html( $provider->get_label() ); ?></strong>
						</label>
					</div>
					
					<div class="provider-options">
						<?php
						if ( $provider->is_available_for_user( $user ) ) {
							$provider->user_options( $user );
						} else {
							echo '<p class="description">' . esc_html__( 'Click the checkbox above to enable this method and configure it.', 'two-factor' ) . '</p>';
						}
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		
		<?php if ( count( $providers ) > 1 ) : ?>
			<div class="two-factor-primary-method">
				<h4><?php esc_html_e( 'Primary Method', 'two-factor' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Select your preferred two-factor authentication method.', 'two-factor' ); ?></p>
				<select name="<?php echo esc_attr( Two_Factor_Core::PROVIDER_USER_META_KEY ); ?>">
					<option value=""><?php esc_html_e( 'Default', 'two-factor' ); ?></option>
					<?php foreach ( $providers as $provider_key => $provider ) : ?>
						<?php if ( $provider->is_supported() ) : ?>
							<option value="<?php echo esc_attr( $provider_key ); ?>" <?php selected( $provider_key, $primary_provider_key ); ?>>
								<?php echo esc_html( $provider->get_label() ); ?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Handle frontend setup actions.
	 */
	public static function handle_setup_actions() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Only process on our setup page
		if ( ! get_query_var( 'two_factor_setup' ) ) {
			return;
		}

		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		
		if ( 'setup_2fa' !== $action || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_nonce_user_two_factor_options'], 'user_two_factor_options' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'two-factor' ) );
		}

		$user = wp_get_current_user();
		
		// Process the form submission using core functionality
		Two_Factor_Core::user_two_factor_options_update( $user->ID );
		
		// Check if 2FA is now properly set up
		if ( Two_Factor_Core::is_user_using_two_factor( $user ) ) {
			// Clear any setup required flags
			delete_user_meta( $user->ID, '_cciwa_2fa_setup_required' );
			$notices = get_user_meta( $user->ID, '_cciwa_2fa_notices', true );
			if ( is_array( $notices ) ) {
				$notices = array_diff( $notices, array( 'setup_required' ) );
				update_user_meta( $user->ID, '_cciwa_2fa_notices', $notices );
			}
			
			// Redirect to success page or original destination
			$redirect_url = home_url();
			if ( isset( $_GET['redirect_to'] ) ) {
				$redirect_url = esc_url_raw( $_GET['redirect_to'] );
			}
			
			wp_redirect( $redirect_url );
			exit;
		} else {
			// Redirect back to setup with error message
			wp_redirect( add_query_arg( 'setup_error', '1', self::get_setup_url() ) );
			exit;
		}
	}
}