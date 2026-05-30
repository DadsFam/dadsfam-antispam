<?php if ( ! defined( 'ABSPATH' ) ) exit;
$stats  = DFSAS_Logger::get_stats();
$is_pro = DFSAS_Helpers::is_pro();
$opts   = DFSAS_Core::instance()->get_options();
?>
<div class="wrap dfsas-wrap">

    <div class="dfsas-header">
        <div class="dfsas-header__inner">
            <div class="dfsas-logo">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="#1a5e9a"/><line x1="12" y1="8" x2="12" y2="12" stroke="white" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16" r="1" fill="white"/></svg>
                <div>
                    <h1><?php esc_html_e('DadsFam Anti-Spam','dadsfam-antispam'); ?></h1>
                    <span class="dfsas-version">v<?php echo esc_html(DFSAS_VERSION); ?><?php if ($is_pro) echo ' <span class="dfsas-badge dfsas-badge--pro">PRO</span>'; ?></span>
                </div>
            </div>
            <nav class="dfsas-header__nav">
                <a href="<?php echo admin_url('admin.php?page=dadsfam-antispam'); ?>" class="active"><?php esc_html_e('Dashboard','dadsfam-antispam'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=dfsas-settings'); ?>"><?php esc_html_e('Settings','dadsfam-antispam'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=dfsas-blocklist'); ?>"><?php esc_html_e('Blocklist','dadsfam-antispam'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=dfsas-logs'); ?>"><?php esc_html_e('Spam Log','dadsfam-antispam'); ?></a>
                <?php if (!$is_pro) : ?><a href="<?php echo admin_url('admin.php?page=dfsas-pro'); ?>" class="dfsas-nav--pro">⭐ PRO</a><?php endif; ?>
            </nav>
        </div>
    </div>

    <div class="dfsas-content">

        <div class="dfsas-stats-grid">
            <div class="dfsas-stat dfsas-stat--blue"><div class="dfsas-stat__icon">🛡️</div><div class="dfsas-stat__val"><?php echo number_format($stats['total']); ?></div><div class="dfsas-stat__lbl"><?php esc_html_e('Total Blocked','dadsfam-antispam'); ?></div></div>
            <div class="dfsas-stat dfsas-stat--green"><div class="dfsas-stat__icon">📅</div><div class="dfsas-stat__val"><?php echo number_format($stats['today']); ?></div><div class="dfsas-stat__lbl"><?php esc_html_e('Blocked Today','dadsfam-antispam'); ?></div></div>
            <div class="dfsas-stat dfsas-stat--orange"><div class="dfsas-stat__icon">📆</div><div class="dfsas-stat__val"><?php echo number_format($stats['this_week']); ?></div><div class="dfsas-stat__lbl"><?php esc_html_e('This Week','dadsfam-antispam'); ?></div></div>
            <div class="dfsas-stat dfsas-stat--red"><div class="dfsas-stat__icon">🏆</div><div class="dfsas-stat__val dfsas-stat__val--sm"><?php echo esc_html(str_replace('_',' ',$stats['top_reason']) ?: '—'); ?></div><div class="dfsas-stat__lbl"><?php esc_html_e('Top Block Reason','dadsfam-antispam'); ?></div></div>
        </div>

        <div class="dfsas-row">
            <div class="dfsas-card" style="flex:1.2">
                <h2 class="dfsas-card__title"><?php esc_html_e('Protection Modules','dadsfam-antispam'); ?></h2>
                <?php
                $modules = [
                    'enable_honeypot'        => [__('Honeypot Trap',          'dadsfam-antispam'), false],
                    'enable_time_check'      => [__('Time-Based Check',       'dadsfam-antispam'), false],
                    'enable_rate_limiter'    => [__('Rate Limiter',           'dadsfam-antispam'), false],
                    'enable_blocklist'       => [__('IP / Email Blocklist',   'dadsfam-antispam'), false],
                    'enable_content_filter'  => [__('Content Filter',         'dadsfam-antispam'), false],
                    'enable_email_validator' => [__('Email Validator',        'dadsfam-antispam'), false],
                    'enable_recaptcha'       => [__('Google reCAPTCHA',       'dadsfam-antispam'), false],
                    'enable_dnsbl'           => [__('DNSBL IP Reputation',    'dadsfam-antispam'), true ],
                    'enable_geo_block'       => [__('Geo-Blocking',           'dadsfam-antispam'), true ],
                ];
                foreach ($modules as $key => [$label, $pro_only]) :
                    $on = !empty($opts[$key]);
                    $available = !$pro_only || $is_pro;
                ?>
                <div class="dfsas-module">
                    <span class="dfsas-module__dot <?php echo ($on && $available) ? 'on' : 'off'; ?>"></span>
                    <span class="dfsas-module__name"><?php echo esc_html($label); ?></span>
                    <span class="dfsas-module__state">
                        <?php if ($pro_only && !$is_pro) : ?>
                            <span class="dfsas-badge dfsas-badge--pro">PRO</span>
                        <?php else : ?>
                            <span class="dfsas-toggle-badge <?php echo $on ? 'on' : 'off'; ?>"><?php echo $on ? 'ON' : 'OFF'; ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <div class="dfsas-card__footer"><a href="<?php echo admin_url('admin.php?page=dfsas-settings'); ?>" class="dfsas-btn dfsas-btn--sm"><?php esc_html_e('Configure','dadsfam-antispam'); ?></a></div>
            </div>

            <div class="dfsas-card" style="flex:1">
                <h2 class="dfsas-card__title"><?php esc_html_e('Block Reasons','dadsfam-antispam'); ?></h2>
                <?php if (!empty($stats['by_reason'])) :
                    $total_r = array_sum(array_column($stats['by_reason'],'cnt'));
                    foreach ($stats['by_reason'] as $r) :
                        $pct = $total_r > 0 ? round($r['cnt']/$total_r*100) : 0;
                ?>
                <div class="dfsas-reason">
                    <div class="dfsas-reason__row"><span><?php echo esc_html(str_replace('_',' ',$r['reason'])); ?></span><strong><?php echo number_format($r['cnt']); ?></strong></div>
                    <div class="dfsas-bar"><div class="dfsas-bar__fill" style="width:<?php echo $pct; ?>%"></div></div>
                </div>
                <?php endforeach; else : ?>
                    <p class="dfsas-muted"><?php esc_html_e('No spam caught yet — either your forms are clean or this is freshly installed!','dadsfam-antispam'); ?></p>
                <?php endif; ?>
                <div class="dfsas-card__footer"><a href="<?php echo admin_url('admin.php?page=dfsas-logs'); ?>" class="dfsas-btn dfsas-btn--sm"><?php esc_html_e('View Full Log','dadsfam-antispam'); ?></a></div>
            </div>
        </div>

        <div class="dfsas-card">
            <h2 class="dfsas-card__title"><?php esc_html_e('Quick Actions','dadsfam-antispam'); ?></h2>
            <div class="dfsas-actions-row">
                <button class="dfsas-btn dfsas-btn--primary" id="dfsas-test-email"><?php esc_html_e('Send Test Email','dadsfam-antispam'); ?></button>
                <?php if ($is_pro) : ?><button class="dfsas-btn dfsas-btn--secondary" id="dfsas-export-csv"><?php esc_html_e('Export CSV','dadsfam-antispam'); ?></button><?php endif; ?>
                <button class="dfsas-btn dfsas-btn--danger" id="dfsas-clear-logs"><?php esc_html_e('Clear All Logs','dadsfam-antispam'); ?></button>
            </div>
            <div id="dfsas-msg"></div>
        </div>

        <?php if (!$is_pro) : ?>
        <div class="dfsas-upsell-banner">
            <span class="dfsas-upsell-banner__star">⭐</span>
            <div>
                <strong><?php esc_html_e('DadsFam Anti-Spam PRO','dadsfam-antispam'); ?></strong><br>
                <span><?php esc_html_e('DNSBL, Geo-Blocking, 1 500+ disposable email domains, unlimited blocklists, CSV export, whitelist, email digests, auto log cleanup.','dadsfam-antispam'); ?></span>
            </div>
            <a href="<?php echo admin_url('admin.php?page=dfsas-pro'); ?>" class="dfsas-btn dfsas-btn--pro"><?php esc_html_e('See PRO Features','dadsfam-antispam'); ?></a>
        </div>
        <?php endif; ?>

    </div>
</div>
