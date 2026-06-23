=== DadsFam Anti-Spam ===
Contributors: dadsfam
Tags: anti-spam, spam protection, contact form, honeypot, recaptcha, comment spam, blocklist
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.9.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pro-grade spam protection for WordPress — contact forms, comments, logins, registrations, and WooCommerce, all in one plugin. No subscriptions.

== Description ==

**DadsFam Anti-Spam** stops spam across your entire WordPress site. Every check runs locally — no data is sent to any third-party service.

**What it protects:**

* 📝 Contact forms — CF7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, Pagelayer, and any generic HTML form
* 💬 WordPress comment forms — honeypot, time check, content scoring, blocklist
* 🔐 WordPress login and registration
* 🔑 WooCommerce My Account login and checkout
* 🔄 Lost password form
* 🌐 Any other HTML form on your site via JavaScript injection

**Free Features**

* 🍯 Honeypot trap — invisible fields injected into all forms. Bots fill them; humans never see them
* ⏱️ Time-based check — blocks submissions under your minimum seconds threshold
* 🚦 Rate limiter — per-IP throttle with configurable window and lockout
* 🚫 IP / email / domain / keyword blocklist — manual block rules (up to 100 IPs, 100 emails/domains, 50 keywords on Free)
* 📊 Content filter scoring — excessive links, HTML injection, spam phrases, suspicious TLDs
* ✉️ Email validator — MX record check + 50 built-in disposable email domains
* 💬 Comment spam protection — honeypot, time check, rate limiting, blocklist, and content scoring on comment forms. Bot submissions hard-blocked; suspicious comments go to spam queue
* 🔑 Google reCAPTCHA v2 / v2 Invisible / v3 — all versions, all major form plugins
* 🐙 Pull from GitHub — one-click update of disposable email domain list from the community repository
* 📋 Spam log — last 200 blocked submissions with Quick Block (add IP/email/domain to blocklist in one click) and expandable details
* 🔌 DF Licensing integration hook ready

**PRO Features**

* 🌐 DNSBL IP reputation — real-time check against Spamhaus, SpamCop, SORBS
* 🗺️ Geo-blocking — block by country ISO code
* 📧 1 500+ disposable email domains — extended database with auto-update
* ♾️ Unlimited blocklist entries — no Free-tier caps
* 🔀 CIDR & wildcard IP blocking — e.g. 192.168.1.0/24 or 10.0.0.*
* ✅ Whitelist — always-allow specific IPs and emails
* 📊 CSV log export
* 📬 Email digest — daily or weekly spam summary
* 🧹 Auto log cleanup — delete entries older than N days

**Supported Integrations**

Contact Form 7 · WPForms · Ninja Forms · Gravity Forms · Fluent Forms · Pagelayer (Softaculous) · WooCommerce Checkout · WooCommerce My Account Login · WordPress Login · WordPress Registration · WordPress Lost Password · WordPress Comments · Any generic HTML form (JS injection)

== Installation ==

1. Upload the `dadsfam-antispam` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Go to **Anti-Spam → Settings** — all core modules are ON by default
4. That's it. Your forms, comments, and logins are protected immediately.

== Frequently Asked Questions ==

= Does this replace Akismet? =
Yes. The comment protection covers everything Akismet does plus adds honeypot, time check, rate limiting, and blocklist checks on top.

= Does it affect site performance? =
No. All checks use fast PHP logic and WordPress transients. No external calls except DNSBL (PRO) and reCAPTCHA (optional).

= Does it filter incoming emails to my mailbox? =
No — no WordPress plugin can do this. Incoming email filtering (SpamAssassin etc.) is done at the mail server level via cPanel. This plugin protects spam generated through your site.

= Where is my data stored? =
Everything stays on your server in the `{prefix}dfsas_spam_log` table. Nothing is sent externally except reCAPTCHA verification (to Google) and DNSBL lookups (to DNS servers) if those features are enabled.

= How do I integrate with DF Licensing? =
Add: `add_filter('dfsas_is_pro', fn() => df_license_is_valid('dadsfam-antispam'));`

== Changelog ==

= 1.9.5 =
* New: Dark / Light mode toggle with saved preference (follows your system on first visit)
* New: Redesigned animated dashboard hero with live status, all-time and today's blocked counts
* Improved: Full dark-theme styling across every screen; smoother fluid motion (respects reduce-motion)

= 1.9.4 =
* New: Premium admin UI overhaul — glass cards, animated gradient backdrop, 3D tilt, count-up stats, gradient headings, glowing toggles, shimmer buttons, smooth entrance animations (respects reduce-motion)

= 1.9.3 =
* New: Block blocklisted IPs at WordPress login (not just forms) — on by default, respects whitelist, toggleable on the Blocklist page

= 1.9.2 =
* Fixed: Quick Block on the spam log now correctly saves IPs/emails/domains to the blocklist
* Improved: Removed a confusing server-specific help note on the Settings page

= 1.9.1 =
* New: Blocked Usernames list for registration (supports * wildcards)
* New: Backup & Restore — export/import your full settings as a JSON file
* Improved: Added form-type database index for faster dashboard queries (auto-applied on update)

= 1.9.0 =
* New: Visual dashboard with 30-day spam trend chart, Protection Health score, and Spam by Source breakdown
* New: Comment author-link detection (flags URLs in the author name field)
* New: Non-Latin script detection for comments (opt-in — Cyrillic/CJK/Arabic)
* Improved: Every PHP file linted before release; refreshed dashboard styling

= 1.8.3 =
* Fixed: wp_mail content and blocklist filters were diverting legitimate contact form emails (including Gmail addresses) to blocked@localhost. These filters now LOG suspicious mail only — never block. Blocking is handled exclusively by form-specific hooks (CF7, WPForms etc.) which are targeted and reliable.

= 1.8.2 =
* Fixed: Auto-update frequency change had no effect — rescheduling now fires immediately when settings are saved via update_option hook, no page reload needed
* Fixed: wp_clear_scheduled_hook() used instead of wp_unschedule_event() for reliable clearing of all instances
* Improved: Label changed from "Enable automatic weekly update" to "Enable Automatic Updates"

= 1.8.1 =
* Removed: Internal admin tool not intended for public distribution
* Improved: Settings and changelog cleaned up accordingly

= 1.8.0 =
* New (FREE): Full comment spam protection — honeypot, time check, rate limiting, blocklist, content scoring on all WordPress comment forms

= 1.7.1 =
* New: Extended auto-update frequency — Every Hour, 6 Hours, 12 Hours, Twice Daily, Daily, Every 3 Days, Weekly

= 1.6.5 =
* Production release — full audit, all debug statements removed

= 1.6.4 =
* Fixed: reCAPTCHA token never populated — inline JS ran before Google's script loaded

= 1.6.3 =
* Fixed: Removed duplicate WooCommerce login field causing empty token

= 1.6.2 =
* Fixed: WooCommerce login double-verification consuming single-use token

= 1.6.1 =
* Fixed: reCAPTCHA on wp-admin and WooCommerce My Account login

= 1.6.0 =
* New: Quick Block button on spam log, expandable details panel, stats strip

= 1.5.8 =
* Fixed: WooCommerce order emails being blocked and diverted

= 1.5.7 =
* Fixed: PHP fatal error (orphaned comment fragment) causing site crash

= 1.5.0 =
* New (FREE): Google reCAPTCHA v2/v3 integration across all form plugins

= 1.4.0 =
* New (PRO): Upload domain list directly from browser

= 1.3.0 =
* Fixed: Page error after license activation; PRO page always visible

= 1.2.0 =
* New: Full DF Licensing system; Fixed: settings wiping blocklist on save

= 1.1.0 =
* New (PRO): Auto-updating disposable domain list; Changelog page

= 1.0.0 =
* Initial release
