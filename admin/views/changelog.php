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

                <!-- ── v1.8.2 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.8.2</span>
                        <span class="dfsas-changelog__date">5 Jun 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>Auto-update frequency change had no effect</strong> — the reschedule logic only ran on page load and compared against a separately stored option that often didn't match. Replaced with a direct <code>update_option_dfsas_options</code> action hook so WP-Cron is rescheduled the instant you click Save — no page reload, no delay.</li>
                                <li>Replaced <code>wp_unschedule_event()</code> with <code>wp_clear_scheduled_hook()</code> which reliably removes all instances of a recurring event, not just the next one.</li>
                            </ul>
                        </div>
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--improved">Improved</span>
                            <ul>
                                <li>Label updated from "Enable automatic weekly update" to "Enable Automatic Updates" — reflects that the frequency is now configurable, not always weekly.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.8.1 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.8.1</span>
                        <span class="dfsas-changelog__date">5 Jun 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--improved">Improved</span>
                            <ul>
                                <li>Settings and changelog cleaned up — removed an internal admin tool not intended for public distribution</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.8.0 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.8.0</span>
                        <span class="dfsas-changelog__date">5 Jun 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Feature Update</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--new">New</span>
                            <ul>
                                <li><strong>💬 Comment Spam Protection (FREE)</strong> — full protection on all WordPress comment forms, no separate plugin needed:
                                    <ul style="margin-top:6px">
                                        <li><strong>Honeypot trap</strong> — invisible field injected into comment form. Bots fill it; humans never see it. Hard-blocked immediately.</li>
                                        <li><strong>Time check</strong> — bots submit in milliseconds. Submissions under the minimum time are hard-blocked.</li>
                                        <li><strong>Rate limiting</strong> — same per-IP throttle as contact forms. Repeat offenders get locked out.</li>
                                        <li><strong>Blocklist</strong> — IPs, emails, domains, and keywords checked against your blocklist. Hard-blocked.</li>
                                        <li><strong>Content scoring</strong> — excessive links, HTML injection, repeated characters, suspicious URLs, disposable emails, keyword matches. Scores above threshold go to your <em>Comments → Spam</em> queue for review — not deleted permanently.</li>
                                    </ul>
                                </li>
                                <li>Trackbacks, pingbacks, and logged-in users with <code>moderate_comments</code> capability are always skipped</li>
                                <li>Comment protection toggle in Settings → Protection Modules and its own dedicated settings card</li>
                                <li>Blocked/flagged comments logged to Spam Log with form type "comment"</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.7.1 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.7.1</span>
                        <span class="dfsas-changelog__date">4 Jun 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Feature Update</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--new">New</span>
                            <ul>
                                <li><strong>Extended auto-update frequency options</strong> — the domain list schedule now supports: Every Hour, Every 6 Hours, Every 12 Hours, Twice Daily, Daily, Every 3 Days, Weekly. Changing the frequency and saving settings reschedules the cron immediately. Weekly remains the default and recommended setting since the GitHub community list rarely changes more often.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.6.5 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.6.5</span>
                        <span class="dfsas-changelog__date">30 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Production Release</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--improved">Improved</span>
                            <ul>
                                <li>Full production audit — removed all debug <code>console.log</code>, <code>console.warn</code>, and <code>console.error</code> statements from all JS and PHP files</li>
                                <li>Verified: no orphaned PHP comment fragments, no brace mismatches across all 16 class files</li>
                                <li>Verified: all 16 autoloaded class files exist and are correctly mapped</li>
                                <li>Verified: all admin views, assets, and uninstall cleanup present and correct</li>
                                <li>Fail-open on reCAPTCHA network errors — if <code>grecaptcha.execute()</code> fails (network issue, script blocked), the form submits normally rather than blocking the user</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.6.4 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.6.4</span>
                        <span class="dfsas-changelog__date">30 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>Root cause of missing reCAPTCHA token found and fixed</strong> — our inline JS ran at <code>wp_footer</code> priority 10, but WordPress prints enqueued scripts (including Google reCAPTCHA) at priority 20. This meant <code>grecaptcha</code> was always <code>undefined</code> when our script ran, hitting the early-exit, and never populating any token field. Fixed by moving to priority 25 (after scripts print) AND wrapping the entire init in <code>window.addEventListener('load')</code> which waits for all external scripts — including Google's — to fully initialise before running.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.6.3 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.6.3</span>
                        <span class="dfsas-changelog__date">30 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li>Removed <code>woocommerce_login_form_end</code> hook that injected a second empty token field — PHP picks the last value for duplicate field names, so the token was always blank on submit</li>
                                <li>JS now blocks form submission if the token is not yet ready, fetches it first, then submits — prevents race conditions on slow connections</li>
                            </ul>
                        </div>
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--improved">Improved</span>
                            <ul>
                                <li>Browser console now shows a clear warning if the reCAPTCHA script fails to load or <code>execute()</code> errors — helps diagnose domain registration and key type issues</li>
                                <li>Spam log now records Google's specific error code (<code>invalid-input-secret</code>, <code>invalid-input-response</code>, <code>hostname-mismatch</code> etc.) and whether the token was present — open the 🔍 details on a blocked login entry to see why Google rejected it</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.6.2 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.6.2</span>
                        <span class="dfsas-changelog__date">30 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>reCAPTCHA still failing on WooCommerce My Account login</strong> — the token was being verified twice. WooCommerce fires <code>woocommerce_process_login_errors</code> first (our <code>verify_woo_login</code>), consuming the token, then calls <code>wp_signon()</code> which triggers <code>wp_authenticate_user</code> (our <code>verify_wp_login</code>) and tries to verify the now-spent token — which always fails. Fixed by detecting the WooCommerce login nonce and skipping the <code>wp_authenticate_user</code> check when WooCommerce has already handled it.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.6.1 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.6.1</span>
                        <span class="dfsas-changelog__date">30 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>reCAPTCHA failing on wp-admin login</strong> — the v3 token JS was hooked to <code>wp_footer</code> which does not fire on the native <code>wp-login.php</code> page. Added <code>login_footer</code> hook so the token is generated and injected before the form is submitted.</li>
                                <li><strong>reCAPTCHA failing on WooCommerce My Account login</strong> — the token field was only injected via the <code>login_form</code> action (native WP login), not <code>woocommerce_login_form</code>. Added dedicated WooCommerce login injection and a <code>woocommerce_process_login_errors</code> verify method.</li>
                                <li><strong>v3 token JS now creates the hidden field itself</strong> — previously it only populated existing <code>.dfsas-rc-v3-token</code> elements. Now it creates the field in any form that does not already have one, making it work for WooCommerce and any other front-end form. Token is also refreshed on form submit since v3 tokens expire after 2 minutes.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.6.0 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.6.0</span>
                        <span class="dfsas-changelog__date">30 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Feature Update</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--new">New</span>
                            <ul>
                                <li><strong>Quick Block</strong> — every spam log entry now has a 🚫 button that opens a dropdown. From there you can instantly add the IP, the full email address, or just the domain to the appropriate blocklist — all in one click without leaving the page. Detects duplicates and tells you if an entry is already blocked.</li>
                                <li><strong>Expandable Details Panel</strong> — click the 🔍 button on any log entry to see the full details: submitter name, email subject, page URL the form was on, and the specific spam signals that triggered the block (links found, matched keyword, DNSBL server, country code, score breakdown etc.).</li>
                                <li><strong>Stats Strip</strong> — at the top of the log page, a quick summary showing total blocked, blocked today, blocked this week, and the top offending IP address.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.5.8 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.5.8</span>
                        <span class="dfsas-changelog__date">30 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>WooCommerce order emails being blocked</strong> — the content filter and blocklist <code>wp_mail</code> hooks were intercepting all outgoing emails, including WooCommerce new order notifications, password resets, and other system emails. WooCommerce order emails contain HTML, links, and order data that tripped our spam scorer, causing them to be diverted to <code>blocked@localhost</code>. This also caused double emails (SMTP delivery failure notifications). Fixed by only running the <code>wp_mail</code> filter when our injected timestamp field is present in POST — which proves it is a monitored contact form submission. System emails fire outside any form context and will never have this field.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.5.7 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.5.7</span>
                        <span class="dfsas-changelog__date">29 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>Root cause of site crash found and fixed</strong> — a PHP fatal parse error in <code>class-honeypot.php</code>. A code edit accidentally stripped the opening <code>/**</code> from a docblock comment, leaving orphaned <code>*</code> lines sitting outside any comment block. PHP treats these as a syntax error and refuses to load the file, crashing every page. Fixed by restoring the complete docblock. All PHP files scanned — no other orphaned fragments found.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.5.6 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.5.6</span>
                        <span class="dfsas-changelog__date">29 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>Critical: Plugin caused site crash</strong> — the Pagelayer integration class was instantiating other module classes (RateLimiter, Blocklist, ContentFilter, EmailValidator, reCAPTCHA) inside its check method. Each constructor registers WordPress hooks, so they were being registered twice — once by Core on page load and again during the form check. This double-registration caused the crash. Rewrote Pagelayer to perform all checks inline using direct logic and static helpers, with zero class instantiation.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.5.5 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.5.5</span>
                        <span class="dfsas-changelog__date">29 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--improved">Improved</span>
                            <ul>
                                <li>Plugin description and readme updated to list all supported form integrations: Contact Form 7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, Pagelayer (Softaculous), WooCommerce Checkout, WordPress Login, WordPress Registration, WordPress Lost Password, and generic HTML forms</li>
                                <li>If a listed plugin is not installed, its hooks simply never fire — zero performance impact, zero errors</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.5.4 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.5.4</span>
                        <span class="dfsas-changelog__date">29 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Feature Update</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>Definitive fix for hidden fields in emails</strong> — the <code>wp_mail</code> body cleanup is now registered in Core at priority 1, guaranteed to run before any email leaves the server regardless of which form builder sent it, which modules are active, or what request type it is. Pattern <code>wp_[0-9a-f]{8}</code> cleanly matches our rotating field names only.</li>
                            </ul>
                        </div>
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--new">New</span>
                            <ul>
                                <li><strong>Pagelayer (Softaculous) contact form integration</strong> — hooks into Pagelayer's AJAX form action at priority 1, before the form processes. Runs all active spam checks: honeypot, time check, rate limiter, blocklist, content filter, email validator, reCAPTCHA. Returns a proper JSON error response if spam is detected so Pagelayer can display it to the user.</li>
                                <li>Also covers <code>pagelayer_send_email</code> (Pagelayer Pro) action</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.5.3 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.5.3</span>
                        <span class="dfsas-changelog__date">29 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>Root cause found:</strong> CF7 submits forms via <code>admin-ajax.php</code>, which means <code>is_admin()</code> returns true. Our earlier fix had an early-exit condition for admin AJAX requests (added to protect other plugins), which meant our email cleanup never ran for CF7. Fixed by adding a dedicated <code>wp_mail</code> filter that strips our field names unconditionally. Pattern <code>wp_[0-9a-f]{8}</code> is specific to our rotating field names and safe to remove from any email body.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.5.2 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.5.2</span>
                        <span class="dfsas-changelog__date">29 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li>Hidden field names still appearing in CF7 emails — added a second cleanup layer using <code>wpcf7_mail_components</code> that strips our field names directly from the built email body using regex. This fires right before CF7 sends the email and is guaranteed to work regardless of CF7 version or configuration.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.5.1 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.5.1</span>
                        <span class="dfsas-changelog__date">29 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li>Honeypot field, timestamp field, and reCAPTCHA token were appearing in CF7 email bodies when using the <code>[all-fields]</code> tag — CF7 picks up all POST data including injected hidden inputs. All plugin-injected fields are now stripped from CF7's posted data before the email is built, so they never show up in messages sent to the site admin.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.5.0 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.5.0</span>
                        <span class="dfsas-changelog__date">29 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--feature">Feature Update</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--new">New</span>
                            <ul>
                                <li><strong>FREE: Full Google reCAPTCHA integration</strong> — all three versions supported:
                                    <ul style="margin-top:4px">
                                        <li><strong>v3</strong> — fully invisible, score-based (0.0–1.0). Configurable threshold. Recommended for most sites.</li>
                                        <li><strong>v2 Invisible</strong> — no user interaction, fires silently on form submit</li>
                                        <li><strong>v2 Checkbox</strong> — classic "I'm not a robot" tick box</li>
                                    </ul>
                                </li>
                                <li>reCAPTCHA applied across all major integrations: Contact Form 7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, WordPress Login, WordPress Registration, WordPress Lost Password, WooCommerce Checkout, and generic HTML forms</li>
                                <li>Per-location toggles — enable reCAPTCHA only where you need it</li>
                                <li>All failed reCAPTCHA attempts are logged to the Spam Log with score</li>
                                <li>Graceful network error handling — if Google is unreachable, the user is not blocked</li>
                                <li>Google reCAPTCHA module now appears on the Dashboard module status list</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── v1.4.2 ──────────────────────────────────────────────── -->
                <div class="dfsas-changelog__version">
                    <div class="dfsas-changelog__header">
                        <span class="dfsas-changelog__num">1.4.2</span>
                        <span class="dfsas-changelog__date">21 May 2026</span>
                        <span class="dfsas-changelog__tag dfsas-changelog__tag--fix">Patch</span>
                    </div>
                    <div class="dfsas-changelog__body">
                        <div class="dfsas-changelog__group">
                            <span class="dfsas-changelog__group-label dfsas-changelog__group-label--fixed">Fixed</span>
                            <ul>
                                <li><strong>Important:</strong> The <code>wp_mail</code> filter was intercepting emails sent by other DadsFam plugins (booking notifications, invoice emails, etc.) during admin AJAX requests — causing "Network error" on their Save buttons. All three <code>wp_mail</code> hooks (blocklist, content filter, honeypot) now skip admin AJAX requests entirely and only run on front-end form submissions.</li>
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
