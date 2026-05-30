<?php if ( ! defined( 'ABSPATH' ) ) exit;
$opts   = DFSAS_Core::instance()->get_options();
$is_pro = DFSAS_Helpers::is_pro();
?>
<div class="wrap dfsas-wrap">
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div class="dfsas-content">

        <?php if ( isset($_GET['settings-updated']) ) : ?>
        <div class="dfsas-notice dfsas-notice--success"><?php esc_html_e('Settings saved.','dadsfam-antispam'); ?></div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('dfsas_options_group'); ?>
            <input type="hidden" name="dfsas_options[_context]" value="settings" />

            <!-- ── Protection Modules ──────────────────────────────────────── -->
            <div class="dfsas-card">
                <h2 class="dfsas-card__title"><?php esc_html_e('Protection Modules','dadsfam-antispam'); ?></h2>
                <div class="dfsas-settings-grid">

                    <?php
                    $modules = [
                        'enable_honeypot'        => [__('Honeypot Trap','dadsfam-antispam'),         __('Injects an invisible field into forms. Bots fill it; humans never see it.','dadsfam-antispam'), false],
                        'enable_time_check'      => [__('Time-Based Check','dadsfam-antispam'),       __('Blocks submissions that arrive faster than your minimum seconds threshold.','dadsfam-antispam'), false],
                        'enable_rate_limiter'    => [__('Rate Limiter','dadsfam-antispam'),           __('Blocks IPs that submit more than N times per hour.','dadsfam-antispam'), false],
                        'enable_blocklist'       => [__('IP / Email Blocklist','dadsfam-antispam'),   __('Block specific IPs (supports CIDR/wildcard), emails, domains, and keywords.','dadsfam-antispam'), false],
                        'enable_content_filter'  => [__('Content Filter','dadsfam-antispam'),         __('Scoring engine: detects excessive links, HTML injection, spam phrases, and more.','dadsfam-antispam'), false],
                        'enable_email_validator' => [__('Email Validator','dadsfam-antispam'),        __('Checks MX records and blocks disposable/throwaway email addresses.','dadsfam-antispam'), false],
                        'enable_dnsbl'           => [__('DNSBL IP Reputation','dadsfam-antispam'),    __('Checks the submitter\'s IP against real-time DNS blacklists (Spamhaus, SpamCop, SORBS).','dadsfam-antispam'), true],
                        'enable_geo_block'       => [__('Geo-Blocking','dadsfam-antispam'),           __('Block form submissions from specific countries by ISO code.','dadsfam-antispam'), true],
                    ];
                    foreach ($modules as $key => [$label, $desc, $pro_only]) :
                        $disabled = $pro_only && !$is_pro;
                    ?>
                    <div class="dfsas-setting-row <?php echo $disabled ? 'dfsas-setting-row--locked' : ''; ?>">
                        <label class="dfsas-toggle">
                            <input type="checkbox" name="dfsas_options[<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($opts[$key])); ?> <?php disabled($disabled); ?> />
                            <span class="dfsas-toggle__slider"></span>
                        </label>
                        <div class="dfsas-setting__text">
                            <strong><?php echo esc_html($label); ?><?php if ($pro_only) echo ' <span class="dfsas-badge dfsas-badge--pro">PRO</span>'; ?></strong>
                            <p><?php echo esc_html($desc); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── Honeypot Integrations ───────────────────────────────────── -->
            <div class="dfsas-card">
                <h2 class="dfsas-card__title"><?php esc_html_e('Honeypot Integrations','dadsfam-antispam'); ?></h2>
                <p class="dfsas-card__desc"><?php esc_html_e('Enable honeypot injection for each form plugin you use. Generic JS covers any other HTML form.','dadsfam-antispam'); ?></p>
                <div class="dfsas-settings-grid dfsas-settings-grid--compact">
                    <?php
                    $integrations = [
                        'honeypot_cf7'          => 'Contact Form 7',
                        'honeypot_wpforms'      => 'WPForms',
                        'honeypot_ninjaforms'   => 'Ninja Forms',
                        'honeypot_gravityforms' => 'Gravity Forms',
                        'honeypot_fluentforms'  => 'Fluent Forms',
                        'honeypot_generic'      => __('Generic (JS — all forms)','dadsfam-antispam'),                    ];
                    foreach ($integrations as $key => $label) :
                    ?>
                    <div class="dfsas-setting-row">
                        <label class="dfsas-toggle">
                            <input type="checkbox" name="dfsas_options[<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($opts[$key])); ?> />
                            <span class="dfsas-toggle__slider"></span>
                        </label>
                        <div class="dfsas-setting__text"><strong><?php echo esc_html($label); ?></strong></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── Time Check ─────────────────────────────────────────────── -->
            <div class="dfsas-card">
                <h2 class="dfsas-card__title"><?php esc_html_e('Time Check','dadsfam-antispam'); ?></h2>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Minimum seconds before submission is valid','dadsfam-antispam'); ?></label>
                    <input type="number" name="dfsas_options[time_check_min_seconds]" value="<?php echo absint($opts['time_check_min_seconds']); ?>" min="1" max="30" class="small-text" />
                    <span class="dfsas-hint"><?php esc_html_e('Recommended: 3–5 seconds. Humans take longer; bots are instant.','dadsfam-antispam'); ?></span>
                </div>
            </div>

            <!-- ── Rate Limiter ───────────────────────────────────────────── -->
            <div class="dfsas-card">
                <h2 class="dfsas-card__title"><?php esc_html_e('Rate Limiter','dadsfam-antispam'); ?></h2>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Max submissions per IP','dadsfam-antispam'); ?></label>
                    <input type="number" name="dfsas_options[rate_limit_max]" value="<?php echo absint($opts['rate_limit_max']); ?>" min="1" max="100" class="small-text" />
                </div>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Time window (seconds)','dadsfam-antispam'); ?></label>
                    <input type="number" name="dfsas_options[rate_limit_window]" value="<?php echo absint($opts['rate_limit_window']); ?>" min="60" class="small-text" />
                    <span class="dfsas-hint"><?php esc_html_e('3600 = 1 hour. The submission counter resets after this window.','dadsfam-antispam'); ?></span>
                </div>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Lockout duration (seconds)','dadsfam-antispam'); ?></label>
                    <input type="number" name="dfsas_options[rate_limit_lockout]" value="<?php echo absint($opts['rate_limit_lockout']); ?>" min="60" class="small-text" />
                    <span class="dfsas-hint"><?php esc_html_e('86400 = 24 hours. How long the IP stays blocked after exceeding the limit.','dadsfam-antispam'); ?></span>
                </div>
            </div>

            <!-- ── Content Filter ─────────────────────────────────────────── -->
            <div class="dfsas-card">
                <h2 class="dfsas-card__title"><?php esc_html_e('Content Filter','dadsfam-antispam'); ?></h2>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Spam score threshold','dadsfam-antispam'); ?></label>
                    <input type="number" name="dfsas_options[spam_score_threshold]" value="<?php echo absint($opts['spam_score_threshold']); ?>" min="1" max="20" class="small-text" />
                    <span class="dfsas-hint"><?php esc_html_e('Each spam signal adds points. Block when total ≥ this value. Recommended: 5.','dadsfam-antispam'); ?></span>
                </div>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Max links allowed in message','dadsfam-antispam'); ?></label>
                    <input type="number" name="dfsas_options[max_links_allowed]" value="<?php echo absint($opts['max_links_allowed']); ?>" min="0" max="20" class="small-text" />
                </div>
                <div class="dfsas-field-row">
                    <label class="dfsas-toggle">
                        <input type="checkbox" name="dfsas_options[block_html_in_message]" value="1" <?php checked(!empty($opts['block_html_in_message'])); ?> />
                        <span class="dfsas-toggle__slider"></span>
                    </label>
                    <div class="dfsas-setting__text"><strong><?php esc_html_e('Block HTML tags in message body','dadsfam-antispam'); ?></strong></div>
                </div>
            </div>

            <!-- ── Email Validator ────────────────────────────────────────── -->
            <div class="dfsas-card">
                <h2 class="dfsas-card__title"><?php esc_html_e('Email Validator','dadsfam-antispam'); ?></h2>
                <div class="dfsas-field-row">
                    <label class="dfsas-toggle">
                        <input type="checkbox" name="dfsas_options[block_disposable_emails]" value="1" <?php checked(!empty($opts['block_disposable_emails'])); ?> />
                        <span class="dfsas-toggle__slider"></span>
                    </label>
                    <div class="dfsas-setting__text">
                        <strong><?php esc_html_e('Block disposable/throwaway email addresses','dadsfam-antispam'); ?></strong>
                        <p><?php echo $is_pro ? esc_html__('PRO: 1 500+ known disposable domains checked.','dadsfam-antispam') : esc_html__('Free: 50 common disposable domains. PRO has 1 500+.','dadsfam-antispam'); ?></p>
                    </div>
                </div>
                <div class="dfsas-field-row">
                    <label class="dfsas-toggle">
                        <input type="checkbox" name="dfsas_options[check_mx_records]" value="1" <?php checked(!empty($opts['check_mx_records'])); ?> />
                        <span class="dfsas-toggle__slider"></span>
                    </label>
                    <div class="dfsas-setting__text">
                        <strong><?php esc_html_e('Verify email domain has MX/A DNS record','dadsfam-antispam'); ?></strong>
                        <p><?php esc_html_e('Rejects emails from non-existent domains.','dadsfam-antispam'); ?></p>
                    </div>
                </div>
            </div>

            <!-- ── PRO: DNSBL ─────────────────────────────────────────────── -->
            <div class="dfsas-card <?php echo !$is_pro ? 'dfsas-card--locked' : ''; ?>">
                <h2 class="dfsas-card__title"><?php esc_html_e('DNSBL Servers','dadsfam-antispam'); ?> <span class="dfsas-badge dfsas-badge--pro">PRO</span></h2>
                <?php if (!$is_pro) : ?><div class="dfsas-lock-overlay"><span><?php esc_html_e('Upgrade to PRO to use DNSBL checking','dadsfam-antispam'); ?></span></div><?php endif; ?>
                <p class="dfsas-card__desc"><?php esc_html_e('One server per line. The submitter IP is checked against each.','dadsfam-antispam'); ?></p>
                <textarea name="dfsas_options[dnsbl_servers]" rows="4" class="large-text" <?php disabled(!$is_pro); ?>><?php echo esc_textarea($opts['dnsbl_servers']); ?></textarea>
            </div>

            <!-- ── PRO: Geo Block ─────────────────────────────────────────── -->
            <div class="dfsas-card <?php echo !$is_pro ? 'dfsas-card--locked' : ''; ?>">
                <h2 class="dfsas-card__title"><?php esc_html_e('Geo-Blocking','dadsfam-antispam'); ?> <span class="dfsas-badge dfsas-badge--pro">PRO</span></h2>
                <?php if (!$is_pro) : ?><div class="dfsas-lock-overlay"><span><?php esc_html_e('Upgrade to PRO to use Geo-Blocking','dadsfam-antispam'); ?></span></div><?php endif; ?>
                <p class="dfsas-card__desc"><?php esc_html_e('ISO 3166-1 alpha-2 country codes, one per line. E.g: RU, CN, KP','dadsfam-antispam'); ?></p>
                <textarea name="dfsas_options[geo_blocked_countries]" rows="4" class="large-text" <?php disabled(!$is_pro); ?>><?php echo esc_textarea($opts['geo_blocked_countries']); ?></textarea>
            </div>

            <!-- ── PRO: Whitelist ─────────────────────────────────────────── -->
            <div class="dfsas-card <?php echo !$is_pro ? 'dfsas-card--locked' : ''; ?>">
                <h2 class="dfsas-card__title"><?php esc_html_e('Whitelist (Always Allow)','dadsfam-antispam'); ?> <span class="dfsas-badge dfsas-badge--pro">PRO</span></h2>
                <?php if (!$is_pro) : ?><div class="dfsas-lock-overlay"><span><?php esc_html_e('Upgrade to PRO to use whitelisting','dadsfam-antispam'); ?></span></div><?php endif; ?>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Whitelisted IPs (one per line, supports CIDR)','dadsfam-antispam'); ?></label>
                    <textarea name="dfsas_options[whitelisted_ips]" rows="3" class="large-text" <?php disabled(!$is_pro); ?>><?php echo esc_textarea($opts['whitelisted_ips']); ?></textarea>
                </div>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Whitelisted Emails (one per line)','dadsfam-antispam'); ?></label>
                    <textarea name="dfsas_options[whitelisted_emails]" rows="3" class="large-text" <?php disabled(!$is_pro); ?>><?php echo esc_textarea($opts['whitelisted_emails']); ?></textarea>
                </div>
            </div>

            <!-- ── PRO: Email Digest ──────────────────────────────────────── -->
            <div class="dfsas-card <?php echo !$is_pro ? 'dfsas-card--locked' : ''; ?>">
                <h2 class="dfsas-card__title"><?php esc_html_e('Email Digest','dadsfam-antispam'); ?> <span class="dfsas-badge dfsas-badge--pro">PRO</span></h2>
                <?php if (!$is_pro) : ?><div class="dfsas-lock-overlay"><span><?php esc_html_e('Upgrade to PRO for digest emails','dadsfam-antispam'); ?></span></div><?php endif; ?>
                <div class="dfsas-field-row">
                    <label class="dfsas-toggle"><input type="checkbox" name="dfsas_options[enable_email_digest]" value="1" <?php checked(!empty($opts['enable_email_digest'])); disabled(!$is_pro); ?> /><span class="dfsas-toggle__slider"></span></label>
                    <div class="dfsas-setting__text"><strong><?php esc_html_e('Send daily spam digest to admin','dadsfam-antispam'); ?></strong></div>
                </div>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Digest recipient email','dadsfam-antispam'); ?></label>
                    <input type="email" name="dfsas_options[digest_email]" value="<?php echo esc_attr($opts['digest_email'] ?: get_option('admin_email')); ?>" class="regular-text" <?php disabled(!$is_pro); ?> />
                </div>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Frequency','dadsfam-antispam'); ?></label>
                    <select name="dfsas_options[digest_frequency]" <?php disabled(!$is_pro); ?>>
                        <option value="daily" <?php selected($opts['digest_frequency'],'daily'); ?>><?php esc_html_e('Daily','dadsfam-antispam'); ?></option>
                        <option value="weekly" <?php selected($opts['digest_frequency'],'weekly'); ?>><?php esc_html_e('Weekly','dadsfam-antispam'); ?></option>
                    </select>
                </div>
            </div>

            <!-- ── PRO: Log Cleanup ───────────────────────────────────────── -->
            <div class="dfsas-card <?php echo !$is_pro ? 'dfsas-card--locked' : ''; ?>">
                <h2 class="dfsas-card__title"><?php esc_html_e('Log Cleanup','dadsfam-antispam'); ?> <span class="dfsas-badge dfsas-badge--pro">PRO</span></h2>
                <?php if (!$is_pro) : ?><div class="dfsas-lock-overlay"><span><?php esc_html_e('Upgrade to PRO for automatic log cleanup','dadsfam-antispam'); ?></span></div><?php endif; ?>
                <div class="dfsas-field-row">
                    <label class="dfsas-toggle"><input type="checkbox" name="dfsas_options[enable_log_cleanup]" value="1" <?php checked(!empty($opts['enable_log_cleanup'])); disabled(!$is_pro); ?> /><span class="dfsas-toggle__slider"></span></label>
                    <div class="dfsas-setting__text"><strong><?php esc_html_e('Automatically delete old log entries','dadsfam-antispam'); ?></strong></div>
                </div>
                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Keep logs for (days)','dadsfam-antispam'); ?></label>
                    <input type="number" name="dfsas_options[log_retention_days]" value="<?php echo absint($opts['log_retention_days']); ?>" min="1" max="365" class="small-text" <?php disabled(!$is_pro); ?> />
                </div>
            </div>

            <!-- ── PRO: Auto-Update Domain List ──────────────────────────────── -->
            <?php $status = DFSAS_Helpers::is_pro() ? DFSAS_ListUpdater::get_status() : null; ?>
            <div class="dfsas-card <?php echo !$is_pro ? 'dfsas-card--locked' : ''; ?>">
                <h2 class="dfsas-card__title"><?php esc_html_e('Auto-Update: Disposable Email List','dadsfam-antispam'); ?> <span class="dfsas-badge dfsas-badge--pro">PRO</span></h2>
                <?php if (!$is_pro) : ?><div class="dfsas-lock-overlay"><span><?php esc_html_e('Upgrade to PRO for auto-updating lists','dadsfam-antispam'); ?></span></div><?php endif; ?>

                <p class="dfsas-card__desc"><?php esc_html_e('Automatically fetches a fresh disposable-email domain list on a schedule. Host the plain text file on dadsfam.co.za — one domain per line. Lines starting with # are ignored.','dadsfam-antispam'); ?></p>

                <?php if ($is_pro && $status) : ?>
                <div class="dfsas-update-status">
                    <div class="dfsas-update-status__row">
                        <span class="dfsas-update-status__label"><?php esc_html_e('Status','dadsfam-antispam'); ?></span>
                        <span class="dfsas-update-status__val">
                            <?php if ($status['has_list']) : ?>
                                <span class="dfsas-indicator dfsas-indicator--green">●</span>
                                <?php printf(esc_html__('%s domains loaded','dadsfam-antispam'), '<strong id="dfsas-domain-count">' . number_format($status['count']) . '</strong>'); ?>
                            <?php else : ?>
                                <span class="dfsas-indicator dfsas-indicator--orange">●</span>
                                <?php esc_html_e('Using built-in list (not yet fetched)','dadsfam-antispam'); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="dfsas-update-status__row">
                        <span class="dfsas-update-status__label"><?php esc_html_e('Last updated','dadsfam-antispam'); ?></span>
                        <span class="dfsas-update-status__val" id="dfsas-last-updated"><?php echo esc_html($status['last_updated_h']); ?></span>
                    </div>
                    <div class="dfsas-update-status__row">
                        <span class="dfsas-update-status__label"><?php esc_html_e('Next scheduled','dadsfam-antispam'); ?></span>
                        <span class="dfsas-update-status__val"><?php echo esc_html($status['next_check_h']); ?></span>
                    </div>
                    <div class="dfsas-update-status__row">
                        <button type="button" class="dfsas-btn dfsas-btn--secondary dfsas-btn--sm" id="dfsas-update-list-now"><?php esc_html_e('🔄 Update Now','dadsfam-antispam'); ?></button>
                        <span id="dfsas-update-msg" style="font-size:13px;margin-left:10px;"></span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="dfsas-field-row" style="margin-top:14px">
                    <label class="dfsas-toggle"><input type="checkbox" name="dfsas_options[enable_auto_update]" value="1" <?php checked(!empty($opts['enable_auto_update'])); disabled(!$is_pro); ?> /><span class="dfsas-toggle__slider"></span></label>
                    <div class="dfsas-setting__text"><strong><?php esc_html_e('Enable automatic weekly update','dadsfam-antispam'); ?></strong></div>
                </div>

                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Remote list URL','dadsfam-antispam'); ?></label>
                    <input type="url" name="dfsas_options[domain_list_url]" value="<?php echo esc_attr($opts['domain_list_url'] ?? 'https://dadsfam.co.za/anti-spam/disposable-domains.txt'); ?>" class="large-text" <?php disabled(!$is_pro); ?> />
                    <span class="dfsas-hint"><?php esc_html_e('Plain text file, one domain per line. Host it anywhere — your own server is best. Lines starting with # are treated as comments.','dadsfam-antispam'); ?></span>
                </div>

                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Update frequency','dadsfam-antispam'); ?></label>
                    <select name="dfsas_options[domain_list_frequency]" <?php disabled(!$is_pro); ?>>
                        <option value="daily"  <?php selected($opts['domain_list_frequency'] ?? 'weekly','daily');  ?>><?php esc_html_e('Daily','dadsfam-antispam'); ?></option>
                        <option value="weekly" <?php selected($opts['domain_list_frequency'] ?? 'weekly','weekly'); ?>><?php esc_html_e('Weekly (recommended)','dadsfam-antispam'); ?></option>
                    </select>
                </div>

                <div class="dfsas-notice dfsas-notice--info dfsas-notice--sm" style="margin-top:8px">
                    💡 <?php printf(
                        esc_html__('Create the file at %s and paste in domain names. A good free source: %s','dadsfam-antispam'),
                        '<code>dadsfam.co.za/anti-spam/disposable-domains.txt</code>',
                        '<a href="https://github.com/disposable-email-domains/disposable-email-domains" target="_blank">disposable-email-domains on GitHub</a>'
                    ); ?>
                </div>

                <!-- ── Upload button ── -->
                <div class="dfsas-upload-box" style="margin-top:16px">
                    <p class="dfsas-card__desc" style="margin-bottom:8px">
                        <strong><?php esc_html_e('Or just upload a .txt file directly — no FTP or cPanel needed:','dadsfam-antispam'); ?></strong>
                    </p>
                    <div class="dfsas-upload-row">
                        <label class="dfsas-btn dfsas-btn--secondary dfsas-btn--sm" for="dfsas-file-input" style="cursor:pointer">
                            📂 <?php esc_html_e('Choose .txt File','dadsfam-antispam'); ?>
                        </label>
                        <input type="file" id="dfsas-file-input" accept=".txt,text/plain" style="display:none" />
                        <span id="dfsas-file-name" class="dfsas-muted" style="font-size:12px"><?php esc_html_e('No file chosen','dadsfam-antispam'); ?></span>
                        <button type="button" class="dfsas-btn dfsas-btn--primary dfsas-btn--sm" id="dfsas-upload-list" disabled>
                            ⬆️ <?php esc_html_e('Upload & Import','dadsfam-antispam'); ?>
                        </button>
                    </div>
                    <p id="dfsas-upload-msg" style="font-size:13px;margin-top:8px;min-height:18px"></p>
                    <p class="dfsas-muted" style="font-size:11px;margin-top:4px"><?php esc_html_e('Plain .txt file, one domain per line (e.g. mailinator.com). Lines starting with # are ignored. Max 5 MB.','dadsfam-antispam'); ?></p>
                </div>
            </div>

            <!-- ── Google reCAPTCHA (FREE) ───────────────────────────────────── -->
            <div class="dfsas-card">
                <h2 class="dfsas-card__title">🤖 <?php esc_html_e('Google reCAPTCHA','dadsfam-antispam'); ?> <span class="dfsas-badge" style="background:#4285f4;color:#fff">FREE</span></h2>
                <p class="dfsas-card__desc"><?php esc_html_e('Adds Google reCAPTCHA to your forms. Get your free keys at google.com/recaptcha — supports v2 Checkbox, v2 Invisible, and v3 (score-based, fully invisible).','dadsfam-antispam'); ?></p>

                <div class="dfsas-field-row">
                    <label class="dfsas-toggle"><input type="checkbox" name="dfsas_options[enable_recaptcha]" value="1" <?php checked(!empty($opts['enable_recaptcha'])); ?> /><span class="dfsas-toggle__slider"></span></label>
                    <div class="dfsas-setting__text"><strong><?php esc_html_e('Enable Google reCAPTCHA','dadsfam-antispam'); ?></strong></div>
                </div>

                <div class="dfsas-field-row">
                    <label><?php esc_html_e('reCAPTCHA Version','dadsfam-antispam'); ?></label>
                    <select name="dfsas_options[recaptcha_version]">
                        <option value="v3"           <?php selected($opts['recaptcha_version']??'v3','v3'); ?>><?php esc_html_e('v3 — Invisible, score-based (recommended)','dadsfam-antispam'); ?></option>
                        <option value="v2_invisible" <?php selected($opts['recaptcha_version']??'','v2_invisible'); ?>><?php esc_html_e('v2 Invisible — no user interaction','dadsfam-antispam'); ?></option>
                        <option value="v2_checkbox"  <?php selected($opts['recaptcha_version']??'','v2_checkbox'); ?>><?php esc_html_e('v2 Checkbox — "I\'m not a robot" tick box','dadsfam-antispam'); ?></option>
                    </select>
                </div>

                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Site Key (public)','dadsfam-antispam'); ?></label>
                    <input type="text" name="dfsas_options[recaptcha_site_key]" value="<?php echo esc_attr($opts['recaptcha_site_key']??''); ?>" class="large-text" placeholder="6Lc..." autocomplete="off" />
                </div>

                <div class="dfsas-field-row">
                    <label><?php esc_html_e('Secret Key (private)','dadsfam-antispam'); ?></label>
                    <input type="password" name="dfsas_options[recaptcha_secret_key]" value="<?php echo esc_attr($opts['recaptcha_secret_key']??''); ?>" class="large-text" placeholder="6Lc..." autocomplete="off" />
                    <span class="dfsas-hint"><?php printf(esc_html__('Get your free keys at %s — register your domain for the version you choose.','dadsfam-antispam'),'<a href="https://www.google.com/recaptcha/admin" target="_blank">google.com/recaptcha/admin</a>'); ?></span>
                </div>

                <div class="dfsas-field-row" id="dfsas-v3-threshold-row" <?php echo ($opts['recaptcha_version']??'v3') !== 'v3' ? 'style="display:none"' : ''; ?>>
                    <label><?php esc_html_e('v3 Score Threshold','dadsfam-antispam'); ?></label>
                    <input type="number" name="dfsas_options[recaptcha_v3_threshold]" value="<?php echo esc_attr($opts['recaptcha_v3_threshold']??0.5); ?>" min="0.1" max="0.9" step="0.1" class="small-text" />
                    <span class="dfsas-hint"><?php esc_html_e('0.0 = definitely bot, 1.0 = definitely human. Block if score is below this. Recommended: 0.5','dadsfam-antispam'); ?></span>
                </div>

                <h3 style="font-size:13px;font-weight:700;margin:16px 0 8px;color:var(--df-text)"><?php esc_html_e('Apply reCAPTCHA to:','dadsfam-antispam'); ?></h3>
                <div class="dfsas-settings-grid dfsas-settings-grid--compact">
                    <?php
                    $rc_locations = [
                        'recaptcha_cf7'             => 'Contact Form 7',
                        'recaptcha_wpforms'         => 'WPForms',
                        'recaptcha_ninjaforms'      => 'Ninja Forms',
                        'recaptcha_gravityforms'    => 'Gravity Forms',
                        'recaptcha_fluentforms'     => 'Fluent Forms',
                        'recaptcha_wp_login'        => __('WordPress Login','dadsfam-antispam'),
                        'recaptcha_wp_registration' => __('WordPress Registration','dadsfam-antispam'),
                        'recaptcha_wp_lostpassword' => __('WordPress Lost Password','dadsfam-antispam'),
                        'recaptcha_woo_checkout'    => __('WooCommerce Checkout','dadsfam-antispam'),
                        'recaptcha_generic'         => __('Generic HTML Forms (JS)','dadsfam-antispam'),
                    ];
                    foreach ($rc_locations as $key => $label) :
                    ?>
                    <div class="dfsas-setting-row">
                        <label class="dfsas-toggle"><input type="checkbox" name="dfsas_options[<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($opts[$key])); ?> /><span class="dfsas-toggle__slider"></span></label>
                        <div class="dfsas-setting__text"><strong><?php echo esc_html($label); ?></strong></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="dfsas-notice dfsas-notice--info dfsas-notice--sm" style="margin-top:14px">
                    💡 <?php esc_html_e('If a form plugin (e.g. CF7, WPForms) already has its own reCAPTCHA enabled, disable it there first to avoid double verification.','dadsfam-antispam'); ?>
                </div>

                <?php if (!empty($opts['recaptcha_site_key']) && !empty($opts['recaptcha_secret_key'])) : ?>
                <div class="dfsas-field-row" style="margin-top:12px">
                    <button type="button" class="dfsas-btn dfsas-btn--secondary dfsas-btn--sm" id="dfsas-test-recaptcha">🧪 <?php esc_html_e('Test reCAPTCHA Keys','dadsfam-antispam'); ?></button>
                    <span id="dfsas-recaptcha-test-msg" style="font-size:13px;margin-left:10px"></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Logging ────────────────────────────────────────────────── -->
            <div class="dfsas-card">
                <h2 class="dfsas-card__title"><?php esc_html_e('Logging','dadsfam-antispam'); ?></h2>
                <div class="dfsas-field-row">
                    <label class="dfsas-toggle"><input type="checkbox" name="dfsas_options[log_blocked]" value="1" <?php checked(!empty($opts['log_blocked'])); ?> /><span class="dfsas-toggle__slider"></span></label>
                    <div class="dfsas-setting__text">
                        <strong><?php esc_html_e('Log all blocked submissions','dadsfam-antispam'); ?></strong>
                        <p><?php echo $is_pro ? esc_html__('PRO: unlimited log history with auto cleanup.','dadsfam-antispam') : esc_html__('Free: last 200 entries retained. Upgrade PRO for unlimited.','dadsfam-antispam'); ?></p>
                    </div>
                </div>
            </div>

            <div class="dfsas-save-row">
                <?php submit_button(__('Save Settings','dadsfam-antispam'), 'primary dfsas-btn dfsas-btn--primary dfsas-btn--lg', 'submit', false); ?>
            </div>

        </form>
    </div>
</div>
