# DadsFam Anti-Spam

**Pro-grade spam protection for WordPress** — contact forms, comments, logins, registrations, and WooCommerce, all in one plugin. No subscriptions.

**Version:** 1.8.3  
**Contributors:** dadsfam  
**Tags:** anti-spam, spam protection, contact form, honeypot, recaptcha, comment spam, blocklist  
**Requires at least:** 5.8  
**Tested up to:** 6.7  
**Requires PHP:** 7.4  
**Stable tag:** 1.8.3  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

---

### Description

**DadsFam Anti-Spam** stops spam across your entire WordPress site. Every check runs locally — no data is sent to any third-party service.

**What it protects:**

- 📝 Contact forms — CF7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, Pagelayer, and any generic HTML form
- 💬 WordPress comment forms — honeypot, time check, content scoring, blocklist
- 🔐 WordPress login and registration
- 🔑 WooCommerce My Account login and checkout
- 🔄 Lost password form
- 🌐 Any other HTML form on your site via JavaScript injection

### Free Features

- 🍯 Honeypot trap — invisible fields injected into all forms
- ⏱️ Time-based check — blocks submissions under your minimum seconds threshold
- 🚦 Rate limiter — per-IP throttle with configurable window and lockout
- 🚫 IP / email / domain / keyword blocklist
- 📊 Content filter scoring — excessive links, HTML injection, spam phrases, suspicious TLDs
- ✉️ Email validator — MX record check + 50 built-in disposable email domains
- 💬 Comment spam protection — honeypot, time check, rate limiting, blocklist, and content scoring
- 🔑 Google reCAPTCHA v2 / v2 Invisible / v3 support
- 🐙 Pull from GitHub — one-click update of disposable email domain list
- 📋 Spam log — last 200 blocked submissions with Quick Block and expandable details
- 🔌 DF Licensing integration hook ready

### Pro Features

- 🌐 DNSBL IP reputation — real-time check against Spamhaus, SpamCop, SORBS
- 🗺️ Geo-blocking — block by country ISO code
- 📧 1,500+ disposable email domains — extended database with auto-update
- ♾️ Unlimited blocklist entries — no Free-tier caps
- 🔀 CIDR & wildcard IP blocking
- ✅ Whitelist — always-allow specific IPs and emails
- 📊 CSV log export
- 📬 Email digest — daily or weekly spam summary
- 🧹 Auto log cleanup

**Supported Integrations:**  
Contact Form 7 · WPForms · Ninja Forms · Gravity Forms · Fluent Forms · Pagelayer · WooCommerce · WordPress Comments, Login, Registration, Lost Password · Any generic HTML form

---

### Installation

1. Upload the `dadsfam-antispam` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Go to **Anti-Spam → Settings** — all core modules are ON by default
4. That's it. Your forms, comments, and logins are protected immediately.

---

### Frequently Asked Questions

**= Does this replace Akismet?**  
Yes. The comment protection covers everything Akismet does plus adds honeypot, time check, rate limiting, and blocklist checks.

**= Does it affect site performance?**  
No. All checks use fast PHP logic and WordPress transients.

**= Does it filter incoming emails to my mailbox?**  
No — this plugin protects forms on your site, not incoming mailbox email.

**= Where is my data stored?**  
Everything stays on your server in the spam log table.

---

### Changelog

**= 1.8.3 =**  
* Fixed: wp_mail content and blocklist filters were diverting legitimate contact form emails (including Gmail addresses) to blocked@localhost. These filters now LOG suspicious mail only — never block. Blocking is handled exclusively by form-specific hooks (CF7, WPForms etc.)

**= 1.8.2 =**  
* Fixed: Auto-update frequency change had no effect  
* Fixed: wp_clear_scheduled_hook() used instead of wp_unschedule_event()  
* Improved: Label changed to "Enable Automatic Updates"

**= 1.8.1 =**  
* Removed: Internal admin tool not intended for public distribution  
* Improved: Settings and changelog cleaned up

**= 1.8.0 =**  
* New (FREE): Full comment spam protection — honeypot, time check, rate limiting, blocklist, content scoring on all WordPress comment forms

**= 1.7.1 =**  
* New: Extended auto-update frequency options

**= 1.6.5 =**  
* Production release — full audit, all debug statements removed

**Older versions** — see full changelog in `readme.txt`

---

### License
GPLv2 or later

Made with ❤️ by [DadsFam](https://dadsfam.co.za) — South Africa
