=== Addrly - Email Validation ===
Contributors: addrly
Tags: email validation, disposable email, spam protection, email verification, woocommerce
Requires at least: 5.2
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Block disposable and spam emails. Works out of the box - no API key required.

== Description ==

Addrly automatically blocks disposable and spam email addresses from your WordPress site. It works immediately after activation with no configuration required.

= Works Out of the Box =

Unlike other email validation plugins, Addrly works without an API key. You get 60 free requests per hour immediately. Need more? Get a free API key for higher limits.

= What Gets Blocked =

* **Disposable Emails** - Temporary email services like Guerrilla Mail, Temp Mail, 10MinuteMail, and 800+ others
* **Spam Domains** - Domains known for spam and abuse

= Automatic Protection =

Once activated, Addrly protects:

* WordPress user registration
* Comment forms
* WooCommerce checkout
* Contact Form 7 forms

= Fail-Safe Design =

If the API is unavailable or you hit the rate limit, emails are allowed through. Your site's functionality is never disrupted. Errors are logged for your reference.

== Installation ==

1. Upload the `addrly` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. That's it! Your site is now protected

= Optional: Get Higher Limits =

1. Visit [addrly.app/signup](https://addrly.app/signup)
2. Create a free account
3. Copy your API key
4. Paste it in Addrly settings

== Frequently Asked Questions ==

= Do I need an API key to use this plugin? =

No. The plugin works out of the box with 60 requests per hour. An API key is only needed for higher limits.

= What happens if the API is unavailable? =

Emails are allowed through to prevent disruption. An error is logged for your reference.

= What happens if I exceed the rate limit? =

Same as above - emails are allowed through. Consider getting a free API key for higher limits.

= How can I increase the request limit? =

Get a free API key at [addrly.app](https://addrly.app). Free accounts get 2,500 requests/month.

= Does it work with WooCommerce? =

Yes, it automatically validates emails during checkout.

= Does it work with Contact Form 7? =

Yes, email fields in CF7 forms are automatically validated.

== Screenshots ==

1. Simple settings page with optional API key configuration
2. Error logs showing rate limit and connection issues

== Changelog ==

= 1.0.0 =
* Initial release
* Disposable email detection
* Spam domain detection
* Works without API key (60 req/hour)
* WooCommerce integration
* Contact Form 7 integration
* Error logging

== Upgrade Notice ==

= 1.0.0 =
Initial release.
