<?php
/**
 * CCIWA Two Factor MemberPress Integration
 *
 * @package Two_Factor
 */

/**
 * Class for integrating Two Factor with MemberPress.
 *
 * @since 0.1-dev
 *
 * @package Two_Factor
 */
class CCIWA_Two_Factor_MemberPress {

	/**
	 * Initialize MemberPress integration if MemberPress is active.
	 */
	public static function init() {
		// Check if MemberPress is active
		if ( ! class_exists( 'MeprAccountCtrl' ) && ! class_exists( 'MeprUser' ) ) {
			return;
		}

		// Add 2FA management to MemberPress account page
		add_action( 'mepr_account_nav', array( __CLASS__, 'add_account_nav' ), 15 );
		add_action( 'mepr_account_nav_content', array( __CLASS__, 'account_nav_content' ), 15 );
		
		// Handle 2FA actions from MemberPress account page
		add_action( 'template_redirect', array( __CLASS__, 'handle_account_actions' ) );
	}

	/**
	 * Add Two-Factor Auth tab to MemberPress account navigation.
	 *
	 * @param array $nav_items Current navigation items.
	 * @return array Modified navigation items.
	 */
	public static function add_account_nav( $nav_items = array() ) {
		// Only show to logged-in users
		if ( ! is_user_logged_in() ) {
			return $nav_items;
		}

		?>
		<span class="mepr-nav-item two-factor-nav">
			<a href="<?php echo esc_url( add_query_arg( 'action', 'two-factor', get_permalink() ) ); ?>" class="<?php echo esc_attr( self::get_nav_class() ); ?>">
				<i class="mp-icon mp-icon-lock mp-16"></i>
				<?php esc_html_e( 'Two-Factor Auth', 'two-factor' ); ?>
			</a>
		</span>
		<?php
	}

	/**
	 * Get the navigation class for the two-factor tab.
	 *
	 * @return string CSS classes.
	 */
	private static function get_nav_class() {
		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
		return ( 'two-factor' === $action ) ? 'mepr-account-row mepr-active-nav' : 'mepr-account-row';
	}

	/**
	 * Display Two-Factor Auth content in MemberPress account page.
	 */
	public static function account_nav_content() {
		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
		
		if ( 'two-factor' !== $action ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$user = wp_get_current_user();
		$providers = Two_Factor_Core::get_supported_providers_for_user( $user );
		$enabled_providers = Two_Factor_Core::get_enabled_providers_for_user( $user );
		$available_providers = Two_Factor_Core::get_available_providers_for_user( $user );

		?>
		<div class="mp_wrapper">
			<div class="mepr-account-row">
				<div class="mepr-row">
					<div class="mepr-col-sm-12">
						<h3><?php esc_html_e( 'Two-Factor Authentication', 'two-factor' ); ?></h3>
						
						<?php if ( empty( $available_providers ) ) : ?>
							<div class="mepr-alert mepr-alert-info">
								<p><?php esc_html_e( 'Two-Factor Authentication is not configured for your account. Please contact the site administrator.', 'two-factor' ); ?></p>
							</div>
						<?php else : ?>
							<p><?php esc_html_e( 'Manage your two-factor authentication settings below to keep your account secure.', 'two-factor' ); ?></p>
							
							<form method="post" action="<?php echo esc_url( add_query_arg( 'action', 'two-factor' ) ); ?>">
								<?php
								wp_nonce_field( 'two_factor_memberpress_update', '_wpnonce', true );
								self::render_user_options( $user, $providers );
								?>
								<div class="mepr-submit">
									<input type="submit" value="<?php esc_attr_e( 'Update Two-Factor Settings', 'two-factor' ); ?>" class="mepr-btn" />
								</div>
							</form>
							
							<?php if ( Two_Factor_Core::is_user_using_two_factor( $user ) ) : ?>
								<div class="mepr-alert mepr-alert-success">
									<p><strong><?php esc_html_e( 'Two-Factor Authentication is enabled for your account.', 'two-factor' ); ?></strong></p>
								</div>
							<?php else : ?>
								<div class="mepr-alert mepr-alert-warning">
									<p><strong><?php esc_html_e( 'Two-Factor Authentication is not yet configured.', 'two-factor' ); ?></strong> <?php esc_html_e( 'Please set up at least one authentication method to secure your account.', 'two-factor' ); ?></p>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render user two-factor options (simplified version of core functionality).
	 *
	 * @param WP_User $user The user object.
	 * @param array   $providers Available providers.
	 */
	private static function render_user_options( $user, $providers ) {
		$enabled_providers = Two_Factor_Core::get_enabled_providers_for_user( $user );
		$primary_provider_key = get_user_meta( $user->ID, Two_Factor_Core::PROVIDER_USER_META_KEY, true );

		?>
		<table class="form-table" role="presentation">
			<tbody>
				<?php foreach ( $providers as $provider_key => $provider ) : ?>
					<?php if ( ! in_array( $provider_key, $enabled_providers, true ) ) continue; ?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $provider_key ); ?>">
								<input type="checkbox" 
									   name="<?php echo esc_attr( Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY ); ?>[]" 
									   id="<?php echo esc_attr( $provider_key ); ?>"
									   value="<?php echo esc_attr( $provider_key ); ?>"
									   <?php checked( in_array( $provider_key, $enabled_providers, true ) ); ?> />
								<?php echo esc_html( $provider->get_label() ); ?>
							</label>
						</th>
						<td>
							<?php
							if ( $provider->is_available_for_user( $user ) ) {
								$provider->user_options( $user );
							} else {
								echo '<p class="description">' . esc_html__( 'Not configured', 'two-factor' ) . '</p>';
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<?php if ( count( $enabled_providers ) > 1 ) : ?>
			<hr />
			<h4><?php esc_html_e( 'Primary Method', 'two-factor' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Select your preferred two-factor authentication method.', 'two-factor' ); ?></p>
			<select name="<?php echo esc_attr( Two_Factor_Core::PROVIDER_USER_META_KEY ); ?>">
				<option value=""><?php esc_html_e( 'Default', 'two-factor' ); ?></option>
				<?php foreach ( $providers as $provider_key => $provider ) : ?>
					<?php if ( in_array( $provider_key, $enabled_providers, true ) && $provider->is_available_for_user( $user ) ) : ?>
						<option value="<?php echo esc_attr( $provider_key ); ?>" <?php selected( $provider_key, $primary_provider_key ); ?>>
							<?php echo esc_html( $provider->get_label() ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<?php
	}

	/**
	 * Handle two-factor actions from MemberPress account page.
	 */
	public static function handle_account_actions() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
		
		if ( 'two-factor' !== $action || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'two_factor_memberpress_update' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'two-factor' ) );
		}

		$user = wp_get_current_user();
		
		// Process the form submission using core functionality
		Two_Factor_Core::user_two_factor_options_update( $user->ID );
		
		// Redirect to avoid resubmission
		wp_redirect( add_query_arg( 'action', 'two-factor', get_permalink() ) );
		exit;
	}
}