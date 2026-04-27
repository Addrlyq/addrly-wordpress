# Addrly – Email Validation for WordPress

Block disposable and spam emails on your WordPress site. Works out of the box — no API key required.

## What it does

Addrly validates email addresses in real time against a database of 10M+ known disposable and spam domains. When a visitor tries to use a throwaway email, it gets blocked before it ever hits your database.

**Protected surfaces:**
- WordPress user registration
- Comment forms
- WooCommerce checkout
- Contact Form 7 submissions

## Installation

1. Download the latest release
2. Upload the `addrly` folder to `/wp-content/plugins/`
3. Activate through **Plugins → Installed Plugins**
4. Done — your site is protected

Or search for **Addrly** in the WordPress plugin directory (pending approval).

## Configuration

**No configuration is needed.** The plugin works immediately with 60 free requests per hour.

For higher limits, add an API key in **Settings → Addrly**:

| Plan | Limit |
|------|-------|
| No API key | 60 requests/hour |
| Free | 2,500 requests/month |
| Pro | 100,000 requests/month |
| Ultra | 250,000 requests/month |

Get a free API key at [addrly.app/signup](https://addrly.app/signup).

## How it works

1. A visitor submits a form with an email address
2. Addrly checks the email against the API (`api.addrly.app`)
3. If the domain is disposable or flagged as spam, the submission is rejected
4. If the API is unreachable or rate-limited, the email is allowed through (fail-open)

All errors are logged in the Addrly admin panel for visibility.

## Requirements

- WordPress 5.2+
- PHP 7.4+

## License

GPL v2 or later — [LICENSE](http://www.gnu.org/licenses/gpl-2.0.txt)
