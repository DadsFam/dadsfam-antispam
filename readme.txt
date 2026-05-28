=== DadsFam Anti-Spam ===
Contributors: dadsfam
Tags: anti-spam, spam protection, contact form, honeypot, blocklist
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.4.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pro-grade form & email spam protection. Honeypots, time checks, rate limiting, blocklists, content scoring, DNSBL, geo-blocking — all on your server.

== Description ==

**DadsFam Anti-Spam** stops contact-form spam before it hits your inbox. Every check runs locally — no data is sent to any third-party service.

**Free Features**

* 🍯 **Honeypot Trap** — invisible fields injected into CF7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, and all generic forms via JS
* ⏱️ **Time-Based Check** — blocks bots that submit in under N seconds
* 🚦 **Rate Limiter** — per-IP submission throttling with configurable lockout
* 🚫 **IP / Email / Domain / Keyword Blocklist** — manual block rules (up to 100 IPs, 100 emails/domains, 50 keywords on Free)
* 📊 **Content Filter** — scoring engine: excessive links, HTML injection, spam phrases, suspicious TLDs
* ✉️ **Email Validator** — MX record check + 50 known disposable email domains
* 📋 **Spam Log** — last 200 blocked submissions with reason, score, and IP

**PRO Features**

* 🌐 **DNSBL IP Reputation** — real-time check against Spamhaus, SpamCop, SORBS
* 🗺️ **Geo-Blocking** — block entire countries by ISO code
* 📧 **1 500+ Disposable Email Domains** — extended throwaway-email database
* ♾️ **Unlimited Blocklist Entries** — no Free-tier caps
* 🔀 **CIDR & Wildcard IP Blocking** — block IP ranges
* ✅ **Whitelist** — always-allow specific IPs and emails
* 📊 **CSV Log Export**
* 📬 **Email Digest** — daily or weekly spam summary
* 🧹 **Auto Log Cleanup** — delete logs older than N days
* 🔑 **DF Licensing integration** — hooks straight into your DadsFam License plugin

**Supported Form Plugins**

Contact Form 7 · WPForms · Ninja Forms · Gravity Forms · Fluent Forms · Any HTML form (generic JS injection) · WordPress Registration

== Installation ==

1. Upload the `dadsfam-antispam` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Go to **Anti-Spam → Settings** to configure

== Frequently Asked Questions ==

= Does this affect site performance? =
No. All checks use fast PHP logic and WordPress transients. The DNSBL module does one DNS lookup per unique IP per request (cached).

= Will it break my contact forms? =
No. It hooks into each form plugin's validation API — forms are not modified other than the invisible honeypot fields.

= Where is my data stored? =
Everything stays on your server in the `{prefix}dfsas_spam_log` table. Nothing is sent externally.

= How do I integrate my DF Licensing plugin later? =
Add this filter: `add_filter('dfsas_is_pro', fn() => df_license_is_valid('dadsfam-antispam'));`

== Changelog ==

= 1.4.2 =
* FIXED: Invoice plugin emails (and all admin-initiated wp_mail calls) no longer blocked by Content Filter or Blocklist modules
* FIXED: filter_wp_mail now skips emails sent by admin/shop-manager users and during WordPress cron
* FIXED: Emails blocked by content filter now cancelled cleanly via pre_wp_mail instead of setting invalid TO address (blocked@localhost) which caused PHPMailer "no recipient" error
* FIXED: Whitelist IP now saves permanently to the whitelisted_ips option — previously only cleared transients so the IP would re-lock immediately
* FIXED: Whitelisted IPs now respected by the rate limiter (previously whitelist only worked on free tier for blocklist, not rate limiter)
* FIXED: IP whitelist now works on free tier — previously gated behind PRO check

= 1.4.1 =
* Fixed: Upload & Import button did nothing — AJAX action was registered in the wrong place and never fired on admin pages

= 1.4.0 =
* New (PRO): Upload domain list directly from your browser — no FTP, no cPanel needed. Pick a .txt file, click Upload & Import, done. Accepts up to 5 MB, validates every line, shows count and status immediately.

= 1.3.2 =
* Fixed: Support email corrected to support@dadsfam.co.za

= 1.3.1 =
* Fixed: Support banner and PRO feature grid now always visible on the PRO page — whether licensed or not
* Fixed: ⭐ PRO nav button was being clipped on the right edge
* Improved: PRO page now shows "Your PRO Features" heading and "all unlocked" message when licensed

= 1.3.0 =
* Fixed: "Sorry, you are not allowed to access this page" error after activating PRO license — PRO page is now always registered regardless of license status
* Fixed: After license activation, redirects cleanly to dashboard instead of reloading the PRO page
* Improved: PRO page redesigned with warm support banner — makes clear PRO is purely to support development, not to lock out features
* Improved: Contact Support email button added to PRO page alongside Get PRO button
* Improved: ⭐ PRO nav link now always visible in header (shows active state when licensed)
* Fixed: Blocklist card description text was being cut off — card title now wraps correctly

= 1.2.0 =
* New: Full PRO licensing system — verify key against dadsfam.co.za, two-factor HMAC integrity check, hourly background re-verify, force-lock REST endpoint
* New: License activate/deactivate from PRO page with live status display
* Fixed: Settings turning OFF after saving the Blocklist page (critical bug — forms now use context markers so each form only updates its own fields)
* Fixed: Blocklist entries being wiped after saving the Settings page (same root cause)
* Improved: PRO page redesigned — shows full license status when active, clean activation form when not

= 1.1.0 =
* New (PRO): Auto-updating disposable email domain list
* New: Changelog admin page

= 1.0.0 =
* Initial release
