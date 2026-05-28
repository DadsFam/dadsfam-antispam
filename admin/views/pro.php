<?php if ( ! defined( 'ABSPATH' ) ) exit;
$is_pro   = DFSAS_Helpers::is_pro();
$lic_info = DFSAS_License::get_display_status();
$features = [
    ['🌐', __('DNSBL IP Reputation','dadsfam-antispam'),            __('Real-time check against Spamhaus, SpamCop, SORBS and custom DNS blacklists.','dadsfam-antispam')],
    ['🗺️', __('Geo-Blocking','dadsfam-antispam'),                    __('Block form submissions from specific countries by ISO code.','dadsfam-antispam')],
    ['📧', __('1 500+ Disposable Email Domains','dadsfam-antispam'), __('Extended throwaway-email domain database, auto-updated weekly from your hosted list.','dadsfam-antispam')],
    ['🔄', __('Auto-Updating Domain List','dadsfam-antispam'),       __('Fetches a fresh disposable-email list from dadsfam.co.za on a daily or weekly schedule.','dadsfam-antispam')],
    ['♾️', __('Unlimited Blocklist Entries','dadsfam-antispam'),     __('No caps — add as many IPs, emails, domains, and keywords as you need.','dadsfam-antispam')],
    ['🔀', __('CIDR & Wildcard IP Blocking','dadsfam-antispam'),     __('Block entire IP ranges: 192.168.1.0/24 or 10.0.0.* — free plan does exact IPs only.','dadsfam-antispam')],
    ['✅', __('Whitelist (Always Allow)','dadsfam-antispam'),         __('Bypass all checks for trusted IPs and email addresses.','dadsfam-antispam')],
    ['📊', __('CSV Log Export','dadsfam-antispam'),                   __('Export your full spam log to CSV for analysis or archiving.','dadsfam-antispam')],
    ['📬', __('Email Digest','dadsfam-antispam'),                     __('Daily or weekly spam summary sent to any email address you choose.','dadsfam-antispam')],
    ['🧹', __('Auto Log Cleanup','dadsfam-antispam'),                 __('Automatically delete log entries older than a configurable number of days.','dadsfam-antispam')],
];
?>
<div class="wrap dfsas-wrap">
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div class="dfsas-content">

        <?php if ( $is_pro ) : ?>
        <!-- ── PRO Active ───────────────────────────────────────────────────── -->
        <div class="dfsas-card dfsas-pro-active-card">
            <div class="dfsas-pro-active-card__icon">⭐</div>
            <h2><?php esc_html_e( 'PRO License Active — Thank You!', 'dadsfam-antispam' ); ?></h2>
            <p><?php esc_html_e( 'All features are unlocked. Your support keeps this plugin alive and growing.', 'dadsfam-antispam' ); ?></p>
            <div class="dfsas-license-info">
                <div class="dfsas-license-info__row">
                    <span><?php esc_html_e( 'Key', 'dadsfam-antispam' ); ?></span>
                    <code><?php echo esc_html( $lic_info['key'] ); ?></code>
                </div>
                <div class="dfsas-license-info__row">
                    <span><?php esc_html_e( 'Status', 'dadsfam-antispam' ); ?></span>
                    <span class="dfsas-lic-badge dfsas-lic-badge--active">✅ <?php esc_html_e( 'Active', 'dadsfam-antispam' ); ?></span>
                </div>
                <div class="dfsas-license-info__row">
                    <span><?php esc_html_e( 'Expires', 'dadsfam-antispam' ); ?></span>
                    <span><?php echo esc_html( $lic_info['expires'] === 'never' ? __( 'Never (lifetime)', 'dadsfam-antispam' ) : $lic_info['expires'] ); ?></span>
                </div>
            </div>
            <button class="dfsas-btn dfsas-btn--ghost dfsas-btn--sm" id="dfsas-license-deactivate" style="margin-top:16px">
                <?php esc_html_e( 'Remove License Key From This Site', 'dadsfam-antispam' ); ?>
            </button>
            <p id="dfsas-license-msg" class="dfsas-license-msg"></p>
        </div>

        <?php else : ?>
        <!-- ── Not Licensed: Activate Card ─────────────────────────────────── -->
        <div class="dfsas-card dfsas-license-card">
            <h2 class="dfsas-card__title">🔑 <?php esc_html_e( 'Activate Your PRO License', 'dadsfam-antispam' ); ?></h2>

            <?php if ( $lic_info['status'] === 'suspended' ) : ?>
            <div class="dfsas-notice dfsas-notice--warning"><?php esc_html_e( '⚠️ Your license key is suspended. Please contact support@dadsfam.co.za.', 'dadsfam-antispam' ); ?></div>
            <?php elseif ( $lic_info['status'] === 'invalid' ) : ?>
            <div class="dfsas-notice dfsas-notice--warning"><?php esc_html_e( 'License key not recognised. Please double-check it or contact support.', 'dadsfam-antispam' ); ?></div>
            <?php endif; ?>

            <p class="dfsas-card__desc"><?php esc_html_e( 'Already have a key? Paste it below and click Activate. Your key is verified securely against dadsfam.co.za.', 'dadsfam-antispam' ); ?></p>
            <div class="dfsas-license-input-row">
                <input type="text" id="dfsas-license-key"
                    placeholder="DFEM-XXXX-XXXX-XXXX-XXXX"
                    class="regular-text dfsas-license-key-input"
                    value="<?php echo esc_attr( $lic_info['key_full'] ); ?>"
                    autocomplete="off" />
                <button class="dfsas-btn dfsas-btn--primary" id="dfsas-license-activate">
                    <?php esc_html_e( 'Activate License', 'dadsfam-antispam' ); ?>
                </button>
            </div>
            <p id="dfsas-license-msg" class="dfsas-license-msg"></p>
            <p class="dfsas-muted" style="margin-top:10px;font-size:12px;">
                <?php esc_html_e( 'Get your key: ', 'dadsfam-antispam' ); ?>
                <strong><?php esc_html_e( 'DadsFam License Manager', 'dadsfam-antispam' ); ?></strong>
                <?php esc_html_e( ' → DF Licenses → Add New Key → Product: 🛡️ DadsFam Anti-Spam', 'dadsfam-antispam' ); ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- ── Support Banner — ALWAYS VISIBLE ──────────────────────────────── -->
        <div class="dfsas-support-banner">
            <div class="dfsas-support-banner__emoji">💛</div>
            <div class="dfsas-support-banner__body">
                <strong><?php esc_html_e( 'PRO is purely to support our work — not to lock you out of anything essential.', 'dadsfam-antispam' ); ?></strong>
                <p><?php esc_html_e( 'The free version fully protects your forms. PRO unlocks power-user extras for people who want more. 100% of proceeds go directly into plugin development and keeping dadsfam.co.za running. No pressure, no catch, no shady upsells.', 'dadsfam-antispam' ); ?></p>
                <div class="dfsas-support-banner__links">
                    <?php if ( ! $is_pro ) : ?>
                    <a href="https://dadsfam.co.za/plugins/anti-spam" target="_blank" rel="noopener" class="dfsas-btn dfsas-btn--pro dfsas-btn--sm">
                        <?php esc_html_e( '⭐ Get PRO License', 'dadsfam-antispam' ); ?>
                    </a>
                    <?php endif; ?>
                    <a href="mailto:support@dadsfam.co.za" class="dfsas-btn dfsas-btn--secondary dfsas-btn--sm">
                        📧 <?php esc_html_e( 'Contact Support', 'dadsfam-antispam' ); ?>
                    </a>
                    <a href="https://dadsfam.co.za" target="_blank" rel="noopener" class="dfsas-btn dfsas-btn--ghost dfsas-btn--sm">
                        🌐 <?php esc_html_e( 'dadsfam.co.za', 'dadsfam-antispam' ); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- ── PRO Features — ALWAYS VISIBLE ────────────────────────────────── -->
        <div class="dfsas-card">
            <h2 class="dfsas-card__title">
                <?php echo $is_pro
                    ? esc_html__( '✅ Your PRO Features', 'dadsfam-antispam' )
                    : esc_html__( 'What PRO Adds', 'dadsfam-antispam' );
                ?>
            </h2>
            <p class="dfsas-card__desc">
                <?php echo $is_pro
                    ? esc_html__( 'All of the following are active and unlocked on this site.', 'dadsfam-antispam' )
                    : esc_html__( 'These are genuine extras — the free plan already protects your forms fully.', 'dadsfam-antispam' );
                ?>
            </p>
            <div class="dfsas-pro-grid">
                <?php foreach ( $features as [$icon, $title, $desc] ) : ?>
                <div class="dfsas-pro-feature">
                    <div class="dfsas-pro-feature__icon"><?php echo $icon; ?></div>
                    <h3><?php echo esc_html( $title ); ?></h3>
                    <p><?php echo esc_html( $desc ); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>
