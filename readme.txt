=== CCIWA Two-Factor Authentication ===
Contributors: georgestephanis, valendesigns, stevenkword, extendwings, sgrant, aaroncampbell, johnbillion, stevegrunwell, netweb, kasparsd, alihusnainarshad, passoniate
Tags:         2fa, mfa, totp, authentication, security, cciwa, memberpress
Tested up to: 6.8
Stable tag:   0.14.0
License:      GPL-2.0-or-later
License URI:  https://spdx.org/licenses/GPL-2.0-or-later.html

CCIWA customized Two-Factor Authentication with simplified setup, enhanced security, and MemberPress integration.

== Description ==

CCIWA Two-Factor Authentication provides enhanced security for WordPress websites with a focus on user-friendly setup and seamless MemberPress integration.

**Key Features:**

- **Authentication App (TOTP)**: Secure time-based codes using apps like Google Authenticator, Authy, or similar
- **Recovery Codes**: One-time backup codes for emergency access
- **Email Verification**: Backup method using email-delivered codes
- **Admin Controls**: Centralized settings to manage available authentication methods
- **2FA Enforcement**: Option to require all users to set up two-factor authentication
- **MemberPress Integration**: Seamless 2FA management from MemberPress account pages
- **Simplified Interface**: Streamlined setup focusing on recommended secure methods

**Admin Management:**
Navigate to "Settings" → "Two-Factor Auth" to configure available authentication methods and enforcement settings.

**User Setup:**
Users can configure their two-factor authentication from "Users" → "Your Profile" or through their MemberPress account page when available.

For more history, see [this post](https://georgestephanis.wordpress.com/2013/08/14/two-cents-on-two-factor/).

= Actions & Filters =

Here is a list of action and filter hooks provided by the plugin:

- `two_factor_providers` filter overrides the available two-factor providers such as email and time-based one-time passwords. Array values are PHP classnames of the two-factor providers.
- `two_factor_providers_for_user` filter overrides the available two-factor providers for a specific user. Array values are instances of provider classes and the user object `WP_User` is available as the second argument.
- `two_factor_enabled_providers_for_user` filter overrides the list of two-factor providers enabled for a user. First argument is an array of enabled provider classnames as values, the second argument is the user ID.
- `two_factor_user_authenticated` action which receives the logged in `WP_User` object as the first argument for determining the logged in user right after the authentication workflow.
- `two_factor_user_api_login_enable` filter restricts authentication for REST API and XML-RPC to application passwords only. Provides the user ID as the second argument.
- `two_factor_email_token_ttl` filter overrides the time interval in seconds that an email token is considered after generation. Accepts the time in seconds as the first argument and the ID of the `WP_User` object being authenticated.
- `two_factor_email_token_length` filter overrides the default 8 character count for email tokens.
- `two_factor_backup_code_length` filter overrides the default 8 character count for backup codes. Providers the `WP_User` of the associated user as the second argument.

== Frequently Asked Questions ==

= What PHP and WordPress versions does the Two-Factor plugin support? =

This plugin supports the last two major versions of WordPress and <a href="https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/">the minimum PHP version</a> supported by those WordPress versions.

= How can I send feedback or get help with a bug? =

The best place to report bugs, feature suggestions, or any other (non-security) feedback is at <a href="https://github.com/WordPress/two-factor/issues">the Two Factor GitHub issues page</a>. Before submitting a new issue, please search the existing issues to check if someone else has reported the same feedback.

= Where can I report security bugs? =

The plugin contributors and WordPress community take security bugs seriously. We appreciate your efforts to responsibly disclose your findings, and will make every effort to acknowledge your contributions.

To report a security issue, please visit the [WordPress HackerOne](https://hackerone.com/wordpress) program.

== Screenshots ==

1. Two-factor options under User Profile.
2. U2F Security Keys section under User Profile.
3. Login with authentication app code.
4. Login with recovery code.
5. Login with email code.

== Changelog ==

See the [release history](https://github.com/wordpress/two-factor/releases).
