<?php
/**
 * Template for Two-Factor Authentication Setup Page
 *
 * @package Two_Factor
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure user is logged in
if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( home_url( '/two-factor-setup/' ) ) );
	exit;
}

get_header(); ?>

<div class="wrap">
	<div class="container">
		<main class="main-content">
			<div class="two-factor-setup-page">
				<?php echo do_shortcode( '[cciwa_two_factor_setup]' ); ?>
			</div>
		</main>
	</div>
</div>

<style>
.two-factor-setup-page {
	max-width: 800px;
	margin: 0 auto;
	padding: 20px;
}

.cciwa-two-factor-setup {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 30px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.13);
}

.two-factor-container h3 {
	color: #23282d;
	margin-bottom: 20px;
	font-size: 24px;
}

.two-factor-success {
	text-align: center;
	padding: 20px 0;
}

.two-factor-success .button {
	margin-top: 15px;
}

.two-factor-provider {
	margin-bottom: 30px;
	border: 1px solid #e1e1e1;
	border-radius: 4px;
	padding: 20px;
}

.provider-header label {
	display: flex;
	align-items: center;
	font-size: 16px;
	margin-bottom: 10px;
}

.provider-header input[type="checkbox"] {
	margin-right: 10px;
}

.provider-options {
	margin-top: 15px;
	padding-top: 15px;
	border-top: 1px solid #f1f1f1;
}

.two-factor-primary-method {
	margin-top: 30px;
	padding-top: 20px;
	border-top: 2px solid #e1e1e1;
}

.two-factor-primary-method h4 {
	margin-bottom: 10px;
}

.two-factor-primary-method select {
	width: 100%;
	max-width: 400px;
	padding: 8px 12px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.two-factor-submit {
	margin-top: 30px;
	text-align: center;
	padding-top: 20px;
	border-top: 2px solid #e1e1e1;
}

.two-factor-submit .button {
	padding: 12px 24px;
	font-size: 16px;
}

.two-factor-error {
	background: #fef7f0;
	border: 1px solid #d63638;
	border-radius: 4px;
	padding: 15px;
	margin-bottom: 20px;
}

.description {
	color: #666;
	font-style: italic;
	margin-top: 5px;
}

/* Responsive design */
@media (max-width: 768px) {
	.two-factor-setup-page {
		padding: 10px;
	}
	
	.cciwa-two-factor-setup {
		padding: 20px;
	}
	
	.two-factor-container h3 {
		font-size: 20px;
	}
}
</style>

<?php get_footer(); ?>