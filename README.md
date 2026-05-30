# DadsFam Anti-Spam

**Pro-grade form & email spam protection.** Honeypots, time checks, IP/email/keyword blocklists, rate limiting, disposable email detection, DNSBL, geo-blocking, and a full spam log — all on your own server. No subscriptions. No data sent anywhere.

**Version:** 1.6.0  
**Author:** DadsFam  
**Website:** [dadsfam.co.za](https://www.dadsfam.co.za/plugins-dadsfam-co-za/)

---

### Features

#### ✅ Free Features
- 🍯 Honeypot Trap (works with CF7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms + generic forms)
- ⏱️ Time-Based Check (blocks super-fast bot submissions)
- 🚦 Rate Limiter (per-IP throttling with lockout)
- 🚫 IP / Email / Domain / Keyword Blocklist
- 📊 Content Filter (scoring engine for spam patterns)
- ✉️ Email Validator (MX check + disposable domains)
- 📋 Spam Log (last 200 blocked attempts with reason & score)

#### ⭐ Pro Features (License Key)
- 🌐 DNSBL IP Reputation checks (Spamhaus, SpamCop, etc.)
- 🗺️ Geo-Blocking (block entire countries)
- 📧 1,500+ Disposable Email Domains
- ♾️ Unlimited Blocklist Entries + CIDR/Wildcard support
- ✅ Whitelist (always allow specific IPs/emails)
- 📊 CSV Log Export
- 📬 Email Digest (daily/weekly summary)
- 🧹 Auto Log Cleanup
- Full DF Licensing integration

---

### Installation
1. Download the latest release from this repository
2. Upload the `dadsfam-antispam` folder to `/wp-content/plugins/`
3. Activate **DadsFam Anti-Spam** in WordPress
4. Go to **Anti-Spam → Settings** to configure protection level

### Quick Start
1. Activate the plugin
2. The honeypot and basic protections start working immediately
3. Visit **Anti-Spam → Settings** to fine-tune time checks, rate limits, and blocklists
4. Check the **Spam Log** to see what’s being blocked

---

### ❤️ A Note from the Developer
This plugin is and will always remain **100% FREE** for personal and commercial use (core features).

I built it for fun — a Dad from South Africa just trying to make ends meet. Yes, we know AI is advanced and there are bigger plugins out there. I do this because I love it.

If you really like what I do and it helps your business, please consider purchasing a **Pro License Key**. It’s purely a donation/support that helps put food on the table and lets me keep building awesome free plugins for the WordPress community.

Thank you for understanding ❤️  
Love from South Africa 🇿🇦

### 🌐 Connect With Us
- **Website**: [www.dadsfam.co.za](https://www.dadsfam.co.za/plugins-dadsfam-co-za/)
- **Threads**: [@dadsfamshop](https://www.threads.com/@dadsfamshop)
- **WhatsApp Community**: [Join here](https://chat.whatsapp.com/IQUhr0zoiO42Y9pXgLMQQz)
- **Instagram**: [@dadsfamshop](https://www.instagram.com/dadsfamshop/)
- **X / Twitter**: [@DADSFAM](https://x.com/DADSFAM)
- **WordPress Plugins**: [Browse all plugins](https://www.dadsfam.co.za/plugins-dadsfam-co-za/)
- **GitHub**: [DadsFam](https://github.com/DadsFam)

### 📢 WordPress.org Submission
We are actively submitting all our plugins to the official [WordPress.org Plugin Directory](https://wordpress.org/plugins/).  

Contact us anytime if you need help: **support@dadsfam.co.za**

### Known Issues
- None currently.

---

### Changelog

**1.6.0** (2026-05-28)
- Boot and initialization improvements: license initialization now runs before core modules to ensure Pro filters are registered early
- Autoloader and class mapping cleanup for clearer file structure
- Activation and deactivation fixes: spam log table creation and scheduled task cleanup handled reliably
- Miscellaneous bug fixes and stability improvements

**1.4.2** (2026-05-28)
- Major fixes for admin emails and wp_mail compatibility (no longer blocked)
- Improved whitelist handling (now works properly with rate limiter on free tier)
- Fixed permanent saving of whitelisted IPs
- Multiple bug fixes and stability improvements

**1.4.1**
- Fixed Upload & Import button not working

**1.4.0**
- New (PRO): Upload domain list directly from browser

**1.3.x - 1.2.x**
- Full PRO licensing system, UI improvements, blocklist fixes

**1.0.0**
- Initial release

---

### License
GPLv2 or later

---

Made with ❤️ by [DadsFam](https://www.dadsfam.co.za/plugins-dadsfam-co-za/) - South African Dad building tools for busy store owners
