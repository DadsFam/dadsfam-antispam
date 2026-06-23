<?php if ( ! defined( 'ABSPATH' ) ) exit;
$opts   = DFSAS_Core::instance()->get_options();
$is_pro = DFSAS_Helpers::is_pro();
$ip_count      = count( DFSAS_Helpers::textarea_to_array( $opts['blocked_ips'] ) );
$email_count   = count( DFSAS_Helpers::textarea_to_array( $opts['blocked_emails'] ) );
$domain_count  = count( DFSAS_Helpers::textarea_to_array( $opts['blocked_domains'] ) );
$keyword_count = count( DFSAS_Helpers::textarea_to_array( $opts['blocked_keywords'] ) );
$username_count = count( DFSAS_Helpers::textarea_to_array( $opts['blocked_usernames'] ?? '' ) );
$free_cap_ip    = !$is_pro && $ip_count >= 100;
$free_cap_email = !$is_pro && $email_count >= 100;
?>
<div class="wrap dfsas-wrap">
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div class="dfsas-content">

        <?php if ( isset($_GET['settings-updated']) ) : ?>
        <div class="dfsas-notice dfsas-notice--success"><?php esc_html_e('Blocklists saved.','dadsfam-antispam'); ?></div>
        <?php endif; ?>

        <?php if (!$is_pro) : ?>
        <div class="dfsas-notice dfsas-notice--info"><?php printf(
            esc_html__('Free plan: up to 100 IPs, 100 emails/domains, and 50 keywords. %sUpgrade to PRO%s for unlimited entries + wildcard/CIDR support.','dadsfam-antispam'),
            '<a href="' . admin_url('admin.php?page=dfsas-pro') . '">',
            '</a>'
        ); ?></div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('dfsas_options_group'); ?>
            <input type="hidden" name="dfsas_options[_context]" value="blocklist" />

                <div class="dfsas-card">
                    <h2 class="dfsas-card__title">
                        <?php esc_html_e('Blocked IPs','dadsfam-antispam'); ?>
                        <span class="dfsas-count-badge"><?php echo esc_html($ip_count); ?><?php if (!$is_pro) echo '/100'; ?></span>
                    </h2>
                    <p class="dfsas-card__desc"><?php echo $is_pro
                        ? esc_html__('One per line. Supports exact IPs, CIDR (192.168.1.0/24), and wildcards (192.168.1.*).','dadsfam-antispam')
                        : esc_html__('One per line. Exact IPs only on free plan. CIDR and wildcards require PRO.','dadsfam-antispam');
                    ?></p>
                    <?php if ($free_cap_ip) : ?>
                    <div class="dfsas-notice dfsas-notice--warning dfsas-notice--sm"><?php esc_html_e('Cap reached (100). Upgrade PRO for unlimited.','dadsfam-antispam'); ?></div>
                    <?php endif; ?>
                    <textarea name="dfsas_options[blocked_ips]" rows="10" class="large-text dfsas-blocklist-ta" placeholder="192.168.1.1&#10;10.0.0.0/24&#10;203.0.113.*"><?php echo esc_textarea($opts['blocked_ips']); ?></textarea>

                    <div class="dfsas-setting-row" style="margin-top:12px;padding-top:12px;border-top:1px solid var(--df-gray-2)">
                        <label class="dfsas-toggle"><input type="checkbox" name="dfsas_options[block_login_ip]" value="1" <?php checked(!empty($opts['block_login_ip'])); ?> /><span class="dfsas-toggle__slider"></span></label>
                        <div class="dfsas-setting__text">
                            <strong><?php esc_html_e('Also block these IPs at login','dadsfam-antispam'); ?></strong>
                            <p><?php esc_html_e('Stop blocklisted IPs from attempting to log in to WordPress, not just submitting forms. Respects your whitelist. (Failed-attempt / brute-force lockout is best handled by a dedicated login-security plugin.)','dadsfam-antispam'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="dfsas-card">
                    <h2 class="dfsas-card__title">
                        <?php esc_html_e('Blocked Emails','dadsfam-antispam'); ?>
                        <span class="dfsas-count-badge"><?php echo esc_html($email_count); ?><?php if (!$is_pro) echo '/100'; ?></span>
                    </h2>
                    <p class="dfsas-card__desc"><?php esc_html_e('Full email addresses, one per line.','dadsfam-antispam'); ?></p>
                    <?php if ($free_cap_email) : ?>
                    <div class="dfsas-notice dfsas-notice--warning dfsas-notice--sm"><?php esc_html_e('Cap reached (100). Upgrade PRO for unlimited.','dadsfam-antispam'); ?></div>
                    <?php endif; ?>
                    <textarea name="dfsas_options[blocked_emails]" rows="10" class="large-text dfsas-blocklist-ta" placeholder="spammer@example.com&#10;bot@fakeinbox.com"><?php echo esc_textarea($opts['blocked_emails']); ?></textarea>
                </div>

                <div class="dfsas-card">
                    <h2 class="dfsas-card__title">
                        <?php esc_html_e('Blocked Domains','dadsfam-antispam'); ?>
                        <span class="dfsas-count-badge"><?php echo esc_html($domain_count); ?><?php if (!$is_pro) echo '/100'; ?></span>
                    </h2>
                    <p class="dfsas-card__desc"><?php esc_html_e('Block all emails from a domain. E.g. spamsite.com — no @ needed.','dadsfam-antispam'); ?></p>
                    <textarea name="dfsas_options[blocked_domains]" rows="10" class="large-text dfsas-blocklist-ta" placeholder="spamsite.com&#10;fakemailer.net"><?php echo esc_textarea($opts['blocked_domains']); ?></textarea>
                </div>

                <div class="dfsas-card">
                    <h2 class="dfsas-card__title">
                        <?php esc_html_e('Spam Keywords','dadsfam-antispam'); ?>
                        <span class="dfsas-count-badge"><?php echo esc_html($keyword_count); ?><?php if (!$is_pro) echo '/50'; ?></span>
                    </h2>
                    <p class="dfsas-card__desc"><?php esc_html_e('Block messages containing these words/phrases. Case-insensitive. One per line.','dadsfam-antispam'); ?></p>
                    <textarea name="dfsas_options[blocked_keywords]" rows="10" class="large-text dfsas-blocklist-ta" placeholder="casino&#10;buy cheap seo&#10;viagra"><?php echo esc_textarea($opts['blocked_keywords']); ?></textarea>
                </div>

                <div class="dfsas-card">
                    <h2 class="dfsas-card__title">
                        <?php esc_html_e('Blocked Usernames','dadsfam-antispam'); ?>
                        <span class="dfsas-count-badge"><?php echo esc_html($username_count); ?><?php if (!$is_pro) echo '/50'; ?></span>
                    </h2>
                    <p class="dfsas-card__desc"><?php esc_html_e('Block these usernames at WordPress registration. Case-insensitive. One per line. Use * as a wildcard — e.g. spam* blocks spam123, *bot blocks chatbot.','dadsfam-antispam'); ?></p>
                    <textarea name="dfsas_options[blocked_usernames]" rows="10" class="large-text dfsas-blocklist-ta" placeholder="admin&#10;seo&#10;spam*&#10;*bot"><?php echo esc_textarea($opts['blocked_usernames'] ?? ''); ?></textarea>
                </div>

            </div>

            <div class="dfsas-save-row">
                <?php submit_button(__('Save Blocklists','dadsfam-antispam'), 'primary dfsas-btn dfsas-btn--primary dfsas-btn--lg', 'submit', false); ?>
            </div>
        </form>
    </div>
</div>
