=== Netlinking SEO ===
Contributors: edouardchelbi
Tags: internal links, seo, netlinking, backlinks, link building, search console
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Internal linking automation, keyword expansion via OpenAI, and Google Search Console backlink monitor.

== Description ==

**Netlinking SEO** automates internal link building and helps you monitor external backlinks directly from your WordPress dashboard.

= Free Features =
* Automatic internal linking from a keyword → URL mapping (up to 50 keywords)
* Smart content masking — never double-links an existing anchor
* Tracks external links found in your content
* Dashboard with link stats (internal / external / sponsored)
* Quota: 500 pages, 50 keywords

= Pro Features (license key required) =
* Unlimited keywords and pages
* Sponsored/netlinking link management
* Google Search Console integration — monitor which external domains link to which pages
* OpenAI keyword expansion — automatically generates semantic variants of your keywords (uses your own API key)
* CSV export
* Weekly email report

= OpenAI Integration =
Bring your own OpenAI API key. The plugin sends keyword text to OpenAI to generate synonyms and semantic variants. Supported models: GPT-4.1 Nano (default), GPT-4.1 Mini, GPT-4o Mini, GPT-4.1, GPT-4o.

= Google Search Console =
Connect via OAuth 2.0 to pull backlink and query data daily. Data stays local in your database — never sent externally.

== Installation ==

1. Upload to `/wp-content/plugins/netlinking-seo/`
2. Activate via Plugins menu
3. Go to **Netlinking SEO → Keywords** to add your first keyword → URL mapping
4. Optionally add your license key and OpenAI key in Settings

== External Services ==

This plugin connects to **api.culture-dev.eu** to:
- Register your site on activation (domain, WP version, PHP version, locale, admin email)
- Sync published page data (slug, title, post type, URL) on each publication
- Verify your license key

Data sent: domain name, admin email (only if provided), WordPress/PHP version, post title, slug, post type.
This connection occurs only on plugin activation, page publication, and license verification — **never on frontend page loads**.
Service privacy policy: https://culture-dev.eu/privacy

If you use the OpenAI feature, keyword text is sent to api.openai.com using your own API key.
OpenAI privacy policy: https://openai.com/policies/privacy-policy

If you connect Google Search Console, OAuth tokens are stored locally. Search analytics data is fetched from googleapis.com.
Google privacy policy: https://policies.google.com/privacy

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
