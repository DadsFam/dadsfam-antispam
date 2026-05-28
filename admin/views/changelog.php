<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap dfsas-wrap">
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div class="dfsas-content">

        <div class="dfsas-card">
            <h2 class="dfsas-card__title">📋 <?php esc_html_e( 'Changelog', 'dadsfam-antispam' ); ?></h2>
            <p class="dfsas-card__desc"><?php printf(
                esc_html__( 'Current version: %s', 'dadsfam-antispam' ),
                '<strong>' . esc_html( DFSAS_VERSION ) . '</strong>'
            ); ?></p>

            <div class="dfsas-changelog">


                <!-- ── v1.4.2 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.4.2</span>
                        <span class="dfsas-changelog__date">23 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>Invoice &amp; plugin emails no longer blocked</strong> — both the Content Filter and Blocklist modules were intercepting admin-initiated <code>wp_mail()</code> calls (invoice resends, WooCommerce order emails, etc.) and silently diverting them to an invalid address. All <code>wp_mail</code> calls from admin or shop-manager users are now skipped by both filters.</li>
                                <li><strong>PHPMailer "no recipient" error resolved</strong> — when content was flagged, the TO address was set to <code>blocked@localhost</code> (invalid), causing PHPMailer to throw an exception. Blocking now uses the <code>pre_wp_mail</code> hook to cancel cleanly with no error.</li>
                                <li><strong>Whitelist IP now saves permanently</strong> — clicking the 🔓 unblock icon previously only cleared the rate-limit transient, so the IP would re-lock on the very next request. It now also writes the IP to the permanent <code>whitelisted_ips</code> option.</li>
                                <li><strong>Whitelist respected by rate limiter</strong> — whitelisted IPs are now checked before any rate-limit logic runs, preventing them from ever being locked again.</li>
                                <li><strong>IP whitelist available on free tier</strong> — the whitelist bypass was previously gated behind a PRO check, meaning free-tier users could save IPs but they were silently ignored.</li>
                                <li>WordPress cron-initiated emails (scheduled WooCommerce notifications, digest emails) also excluded from both filters.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.4.1 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.4.1</span>
                        <span class="dfsas-changelog__date">21 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li>Upload &amp; Import button did nothing when clicked — the AJAX action was registered inside a class that only loads on front-end requests, so it was never available on admin pages. Moved to the admin AJAX handler where all other actions live.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.4.0 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.4.0</span>
                        <span class="dfsas-changelog__date">21 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Feature Update</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--pro">PRO</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--new">New</span>
                            <ul>
                                <li><strong>(PRO)</strong> Upload domain list directly from your browser — no FTP, no cPanel, no hosting knowledge needed. Go to Settings → Auto-Update section, click "Choose .txt File", select your file, click "Upload &amp; Import". Done.</li>
                                <li>File is validated before import: must be <code>.txt</code>, max 5 MB, one domain per line. Lines starting with <code>#</code> are treated as comments and skipped. Invalid lines are silently ignored.</li>
                                <li>Upload result shows immediately: domain count loaded, last updated time, and any errors with plain-English explanation</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.3.2 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.3.2</span>
                        <span class="dfsas-changelog__date">21 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li>Support email corrected to <code>support@dadsfam.co.za</code></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.3.1 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.3.1</span>
                        <span class="dfsas-changelog__date">21 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li>Support banner and PRO feature grid now always visible on the PRO page — whether licensed or not, so the page is always useful</li>
                                <li>⭐ PRO nav button was being clipped on the right edge of the header</li>
                            </ul>
                        </div>
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--improved">Improved</span>
                            <ul>
                                <li>PRO page shows "✅ Your PRO Features — all unlocked" heading when licensed, and "What PRO Adds" when not</li>
                                <li>Contact Support and dadsfam.co.za buttons always visible regardless of license status</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.3.0 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.3.0</span>
                        <span class="dfsas-changelog__date">21 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Feature Update</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li>"Sorry, you are not allowed to access this page" error after activating PRO license — PRO page is now always registered regardless of license status, so reloading never hits a dead end</li>
                                <li>After license activation, page now redirects cleanly to the dashboard instead of reloading the PRO page</li>
                                <li>Blocklist card description text was being cut off by the count badge — card title now wraps correctly on smaller screens</li>
                            </ul>
                        </div>
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--improved">Improved</span>
                            <ul>
                                <li>PRO page redesigned with a warm yellow support banner — makes clear that PRO is purely to support development, not to lock out basic features, no pressure, no catch</li>
                                <li>Contact Support email button added alongside the Get PRO button</li>
                                <li>⭐ PRO nav link is now always visible in the header regardless of license status — shows "⭐ PRO" when active, "⭐ Go PRO" when not</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.2.0 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.2.0</span>
                        <span class="dfsas-changelog__date">21 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Feature Update</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--new">New</span>
                            <ul>
                                <li>Full PRO licensing system — matches Invoice Manager two-factor pattern exactly: stored status + HMAC fingerprint derived from key, site URL, and <code>wp_salt()</code>. Flipping the status option alone cannot unlock PRO.</li>
                                <li>Force-lock REST endpoint <code>POST /wp-json/dflm/v1/force-lock</code> — dadsfam.co.za license server can instantly suspend PRO features without waiting for the hourly cron</li>
                                <li>Hourly background license re-verify via WP-Cron. Network errors preserve the existing active status rather than locking out</li>
                                <li>PRO page: shows full license info (masked key, status, expiry) when active; clean activation form when not. Deactivate button to remove key from site</li>
                            </ul>
                        </div>
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>Critical:</strong> All module toggles turning OFF after saving the Blocklist page — root cause was that the blocklist form only submits its own fields; unchecked checkboxes absent from POST were interpreted as OFF. Fixed by adding a form context marker so each form only updates its own fields and preserves everything else</li>
                                <li>Blocklist entries being wiped when saving the Settings page — same root cause, same fix</li>
                            </ul>
                        </div>
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--improved">Improved</span>
                            <ul>
                                <li>PRO page redesigned — dedicated license status display when active, upgrade flow when not</li>
                                <li>Uninstall cleanup now removes all license option keys and hash</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.1.0 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.1.0</span>
                        <span class="dfsas-changelog__date">21 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Feature Update</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--pro">PRO</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--new">New</span>
                            <ul>
                                <li><strong>(PRO)</strong> Auto-updating disposable email domain list — fetches a fresh list from a remote URL on a configurable daily or weekly schedule</li>
                                <li><strong>(PRO)</strong> Manual "Update Now" button in Settings with live feedback: domain count loaded, last updated time, next scheduled check</li>
                                <li>Changelog admin page — full version history now visible inside the plugin</li>
                                <li>Domain list format guide and direct link to the community disposable-domains list built into settings</li>
                                <li>Update status indicator (green ● / orange ●) shows whether a fetched list is active or the plugin is still using the built-in default</li>
                            </ul>
                        </div>
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--improved">Improved</span>
                            <ul>
                                <li>PRO disposable email check now uses the auto-fetched list first, then falls back to a bundled file, then the built-in 50-domain free list</li>
                                <li>Uninstall cleanup now removes auto-update option keys (<code>dfsas_disposable_domains</code>, <code>dfsas_domains_last_updated</code>, <code>dfsas_domains_last_count</code>)</li>
                                <li>Admin nav updated to include Changelog link across all pages</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.0.0 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.0.0</span>
                        <span class="dfsas-changelog__date">21 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--initial">Initial Release</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--new">New</span>
                            <ul>
                                <li>Honeypot trap — invisible fields injected into Contact Form 7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, and all generic HTML forms via JavaScript (including dynamically loaded popup forms via MutationObserver)</li>
                                <li>Time-based submission check — blocks bots that submit in under a configurable number of seconds (default 3s). Timestamp is HMAC-signed with <code>wp_salt()</code> to prevent tampering</li>
                                <li>Rate limiter — per-IP throttle with configurable time window and lockout duration, stored via WordPress transients (no extra DB table)</li>
                                <li>IP / email / domain / keyword blocklist — manual block rules with Free-tier caps (100 IPs, 100 emails/domains, 50 keywords). Upgrade to PRO for unlimited</li>
                                <li>Content filter scoring engine — detects excessive links, HTML injection, all-caps subjects, spam phrases, suspicious TLDs, repeated characters, income/prize/urgency language</li>
                                <li>Email validator — MX record verification and 50 built-in disposable/throwaway email domains on Free</li>
                                <li><strong>(PRO)</strong> DNSBL IP reputation check — real-time lookup against Spamhaus, SpamCop, SORBS, and custom configurable servers</li>
                                <li><strong>(PRO)</strong> Geo-blocking — block form submissions from specific countries by ISO code. Supports Cloudflare headers, local MaxMind GeoLite2 DB, and ip-api.com fallback</li>
                                <li><strong>(PRO)</strong> Whitelist / always-allow rules — bypass all checks for trusted IPs and emails. Supports CIDR ranges</li>
                                <li><strong>(PRO)</strong> Unlimited blocklist entries + CIDR and wildcard IP matching (e.g. <code>192.168.1.*</code> or <code>10.0.0.0/24</code>)</li>
                                <li><strong>(PRO)</strong> CSV log export</li>
                                <li><strong>(PRO)</strong> Email digest — daily or weekly spam summary sent to a configurable address</li>
                                <li><strong>(PRO)</strong> Auto log cleanup — deletes entries older than a configurable number of days</li>
                                <li>Spam log — paginated table showing reason, score, IP, email, name, form type. Filter by reason or search by IP/email/name. Unblock IP directly from the log</li>
                                <li>Free plan retains last 200 log entries automatically</li>
                                <li>Dashboard with stats grid (total/today/week/top reason), module status dots, block-reason breakdown bar chart, and quick actions</li>
                                <li>Full settings panel with toggle switches, per-integration honeypot controls, and locked PRO sections with upgrade prompts</li>
                                <li>Send Test Email button to verify outgoing mail is working</li>
                                <li>DF Licensing integration hook ready — one filter enables all PRO features: <code>add_filter('dfsas_is_pro', fn() => df_license_is_valid('dadsfam-antispam'))</code></li>
                                <li>Plugin action links in Plugins list (Settings + Upgrade to PRO)</li>
                                <li>Clean uninstall — removes all options, transients, and the custom <code>dfsas_spam_log</code> table on plugin deletion</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div><!-- .dfsas-changelog -->
        </div>

    </div>
</div>
