# DadsFam Anti-Spam

**Pro-grade form & email spam protection.**  
Honeypots, time checks, rate limiting, IP/email/keyword blocklists, content scoring, disposable email detection, DNSBL, geo-blocking, and a full spam log — all running on **your own server**. No subscriptions. No data sent anywhere.

**Version:** 1.6.5  
**Author:** DadsFam  
**Website:** [dadsfam.co.za](https://www.dadsfam.co.za/plugins-dadsfam-co-za/)

---

### Features

#### ✅ Free Features
- 🍯 **Honeypot Trap** – Works with Contact Form 7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, Pagelayer & generic forms
- ⏱️ **Time-Based Check** – Blocks bots that submit too quickly
- 🚦 **Rate Limiter** – Per-IP throttling with configurable lockout
- 🚫 **IP / Email / Domain / Keyword Blocklist**
- 📊 **Content Filter** – Smart scoring engine (links, HTML, spam phrases, suspicious TLDs)
- ✉️ **Email Validator** – MX record check + disposable email detection
- 📋 **Spam Log** – View last 200 blocked submissions with reason and score

#### ⭐ Pro Features (License Key)
- 🌐 **DNSBL IP Reputation** – Real-time checks (Spamhaus, SpamCop, SORBS, etc.)
- 🗺️ **Geo-Blocking** – Block entire countries
- 📧 **1,500+ Disposable Email Domains**
- ♾️ **Unlimited Blocklist Entries** + CIDR & Wildcard support
- ✅ **Whitelist** – Always allow specific IPs and emails
- 📊 **CSV Log Export**
- 📬 **Email Digest** – Daily or weekly summary
- 🧹 **Auto Log Cleanup**
- 🔑 Full DadsFam Licensing integration

---

### Installation

1. Download the latest release from this repository
2. Upload the `dadsfam-antispam` folder to `/wp-content/plugins/`
3. Activate **DadsFam Anti-Spam** in WordPress
4. Go to **Anti-Spam → Settings** to configure

### Quick Start

1. Activate the plugin — basic protection (honeypot + time check) works immediately
2. Visit **Anti-Spam → Settings** to enable additional protections
3. Monitor blocked attempts in **Anti-Spam → Spam Log**

---

### ❤️ A Note from the Developer

This plugin is and will always remain **100% FREE** for core features.

I’m a Dad from South Africa building tools that help small businesses. If this plugin saves you time and reduces spam headaches, consider buying a **Pro License**. It’s not just a key — it’s real support that helps me keep developing quality free plugins for the WordPress community.

Thank you for your support ❤️  
Love from South Africa 🇿🇦

### 🌐 Connect With Us
- **Website**: [dadsfam.co.za](https://www.dadsfam.co.za/plugins-dadsfam-co-za/)
- **Support**: support@dadsfam.co.za
- **X / Twitter**: [@DADSFAM](https://x.com/DADSFAM)
- **Instagram**: [@dadsfamshop](https://www.instagram.com/dadsfamshop/)
- **GitHub**: [DadsFam](https://github.com/DadsFam)

---

### Changelog

**1.6.5** (2026-06-03)
- Production release — removed all debug `console.log`, `console.warn`, and `console.error` statements
- Full code audit completed: fixed orphaned comment fragments, brace mismatches, and autoloader issues
- Improved `uninstall.php` cleanup
- Updated plugin headers and readme files
- General stability improvements

**1.6.4**
- Fixed reCAPTCHA v3 token loading issue (script timing)

**1.6.3**
- Fixed duplicate WooCommerce login hook causing blank reCAPTCHA token
- Improved reCAPTCHA error handling and logging

**1.6.2**
- Fixed reCAPTCHA double verification on WooCommerce My Account login

**1.6.1**
- Added proper reCAPTCHA support for wp-login.php and WooCommerce My Account

**1.6.0**
- New: Quick Block button in spam log
- New: Expandable log details with full score breakdown
- New: Statistics bar (total, today, this week, top IP)

**Older versions**
- See [changelog.txt](changelog.txt) for full history

---

### License
GPLv2 or later

---

Made with ❤️ by [DadsFam](https://www.dadsfam.co.za/) — A South African Dad building tools for busy store owners.
