<?php if ( ! defined( 'ABSPATH' ) ) exit;
$stats  = DFSAS_Logger::get_stats();
$is_pro = DFSAS_Helpers::is_pro();
$opts   = DFSAS_Core::instance()->get_options();

// ── Trend chart (30-day) ──────────────────────────────────────────────────────
$chart = DFSAS_Helpers::build_trend_chart( $stats['by_day'] ?? [], 30 );

// ── Day-over-day delta ────────────────────────────────────────────────────────
$today     = (int) $stats['today'];
$yesterday = (int) ( $stats['yesterday'] ?? 0 );
$delta     = $today - $yesterday;

// ── Protection health score ───────────────────────────────────────────────────
$core_modules = [ 'enable_honeypot', 'enable_time_check', 'enable_rate_limiter', 'enable_blocklist', 'enable_content_filter', 'enable_email_validator', 'enable_comments' ];
$active = 0;
foreach ( $core_modules as $m ) { if ( ! empty( $opts[ $m ] ) ) $active++; }
$recaptcha_on = ! empty( $opts['enable_recaptcha'] ) && ! empty( $opts['recaptcha_site_key'] );
$pro_modules_on = $is_pro && ( ! empty( $opts['enable_dnsbl'] ) || ! empty( $opts['enable_geo_block'] ) );

$score = round( ( $active / count( $core_modules ) ) * 80 );  // core = up to 80%
if ( $recaptcha_on )   $score += 12;                          // reCAPTCHA = +12%
if ( $pro_modules_on ) $score += 8;                           // PRO modules = +8%
$score = min( 100, $score );

if ( $score >= 90 )      { $score_label = __( 'Excellent', 'dadsfam-antispam' ); $score_color = 'var(--df-green)'; }
elseif ( $score >= 65 )  { $score_label = __( 'Good', 'dadsfam-antispam' );      $score_color = 'var(--df-blue)'; }
elseif ( $score >= 40 )  { $score_label = __( 'Basic', 'dadsfam-antispam' );     $score_color = 'var(--df-orange)'; }
else                     { $score_label = __( 'Weak', 'dadsfam-antispam' );      $score_color = 'var(--df-red)'; }

$ring_circ   = 2 * 3.14159 * 52;  // r=52
$ring_offset = $ring_circ - ( $score / 100 * $ring_circ );

// Friendly labels for form types
$form_labels = [
    'contact-form-7' => 'Contact Form 7', 'wpforms' => 'WPForms', 'ninja-forms' => 'Ninja Forms',
    'gravity-forms' => 'Gravity Forms', 'fluent-forms' => 'Fluent Forms', 'pagelayer' => 'Pagelayer',
    'comment' => 'Comments', 'wp-login' => 'WP Login', 'woo-login' => 'WooCommerce Login',
    'wp-registration' => 'Registration', 'wp-lostpassword' => 'Lost Password', 'woo-checkout' => 'WooCommerce Checkout',
    'wp-mail' => 'Email (flagged)', 'generic' => 'Generic Form',
];
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

        <?php
        $hr    = (int) wp_date('G');
        $greet = $hr < 12 ? __('Good morning','dadsfam-antispam') : ( $hr < 18 ? __('Good afternoon','dadsfam-antispam') : __('Good evening','dadsfam-antispam') );
        ?>
        <!-- ── Hero ──────────────────────────────────────────────────────────── -->
        <div class="dfsas-hero">
            <div class="dfsas-hero__inner">
                <div class="dfsas-hero__left">
                    <svg class="dfsas-hero__shield" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="#ffffff" fill-opacity="0.95"/><path d="M9.2 12.3l1.9 1.9 3.7-3.9" stroke="#1a5e9a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <div>
                        <div class="dfsas-hero__greet"><?php echo esc_html($greet); ?></div>
                        <h2 class="dfsas-hero__title"><?php esc_html_e('Your site is protected','dadsfam-antispam'); ?></h2>
                        <div class="dfsas-hero__sub"><?php printf(esc_html__('%s spam attempts blocked all-time across your forms, comments and logins.','dadsfam-antispam'), '<strong>' . number_format($stats['total']) . '</strong>'); ?></div>
                    </div>
                </div>
                <div class="dfsas-hero__right" style="display:flex;align-items:center;gap:22px;flex-wrap:wrap">
                    <span class="dfsas-hero__status"><span class="dfsas-hero__dot"></span><?php esc_html_e('Active &amp; Monitoring','dadsfam-antispam'); ?></span>
                    <div class="dfsas-hero__big">
                        <div class="dfsas-hero__big-num"><?php echo number_format($today); ?></div>
                        <div class="dfsas-hero__big-lbl"><?php esc_html_e('Blocked Today','dadsfam-antispam'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Stat cards ──────────────────────────────────────────────────── -->
        <div class="dfsas-stats-grid">
            <div class="dfsas-stat dfsas-stat--blue">
                <div class="dfsas-stat__icon">🛡️</div>
                <div class="dfsas-stat__val"><?php echo number_format($stats['total']); ?></div>
                <div class="dfsas-stat__lbl"><?php esc_html_e('Total Blocked','dadsfam-antispam'); ?></div>
            </div>
            <div class="dfsas-stat dfsas-stat--green">
                <div class="dfsas-stat__icon">📅</div>
                <div class="dfsas-stat__val"><?php echo number_format($today); ?></div>
                <div class="dfsas-stat__lbl">
                    <?php esc_html_e('Blocked Today','dadsfam-antispam'); ?>
                    <?php if ($delta !== 0) : ?>
                        <span class="dfsas-delta dfsas-delta--<?php echo $delta > 0 ? 'up' : 'down'; ?>"><?php echo $delta > 0 ? '▲' : '▼'; ?> <?php echo abs($delta); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dfsas-stat dfsas-stat--orange">
                <div class="dfsas-stat__icon">📆</div>
                <div class="dfsas-stat__val"><?php echo number_format($stats['this_week']); ?></div>
                <div class="dfsas-stat__lbl"><?php esc_html_e('Last 7 Days','dadsfam-antispam'); ?></div>
            </div>
            <div class="dfsas-stat dfsas-stat--purple">
                <div class="dfsas-stat__icon">🗓️</div>
                <div class="dfsas-stat__val"><?php echo number_format($stats['this_month'] ?? 0); ?></div>
                <div class="dfsas-stat__lbl"><?php esc_html_e('Last 30 Days','dadsfam-antispam'); ?></div>
            </div>
        </div>

        <!-- ── Trend chart + health score ──────────────────────────────────── -->
        <div class="dfsas-row">
            <div class="dfsas-card dfsas-card--chart" style="flex:2;min-width:340px">
                <div class="dfsas-chart-head">
                    <h2 class="dfsas-card__title" style="margin:0">📈 <?php esc_html_e('Spam Blocked — Last 30 Days','dadsfam-antispam'); ?></h2>
                    <?php if (($chart['peak'] ?? 0) > 0) : ?>
                    <span class="dfsas-chart-peak"><?php printf(esc_html__('Peak: %1$s on %2$s','dadsfam-antispam'), number_format($chart['peak']), esc_html(wp_date('d M', strtotime($chart['peak_day'])))); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (($chart['total'] ?? 0) > 0) : ?>
                    <div class="dfsas-chart-wrap"><?php echo $chart['svg']; // phpcs:ignore — generated inline SVG ?></div>
                <?php else : ?>
                    <div class="dfsas-chart-empty">
                        <div class="dfsas-chart-empty__icon">🌱</div>
                        <p><?php esc_html_e('No spam blocked in the last 30 days. Your forms are clean — or protection was just enabled. The chart fills in as spam is caught.','dadsfam-antispam'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dfsas-card dfsas-card--score" style="flex:1;min-width:240px">
                <h2 class="dfsas-card__title">💚 <?php esc_html_e('Protection Health','dadsfam-antispam'); ?></h2>
                <div class="dfsas-score-ring-wrap">
                    <svg width="130" height="130" viewBox="0 0 130 130" class="dfsas-score-ring">
                        <circle cx="65" cy="65" r="52" fill="none" stroke="var(--df-gray-2)" stroke-width="11"/>
                        <circle cx="65" cy="65" r="52" fill="none" stroke="<?php echo esc_attr($score_color); ?>" stroke-width="11"
                                stroke-linecap="round" stroke-dasharray="<?php echo esc_attr($ring_circ); ?>"
                                stroke-dashoffset="<?php echo esc_attr($ring_offset); ?>"
                                transform="rotate(-90 65 65)" class="dfsas-score-ring__bar"/>
                        <text x="65" y="60" text-anchor="middle" class="dfsas-score-ring__num" fill="<?php echo esc_attr($score_color); ?>"><?php echo $score; ?>%</text>
                        <text x="65" y="82" text-anchor="middle" class="dfsas-score-ring__lbl"><?php echo esc_html($score_label); ?></text>
                    </svg>
                </div>
                <div class="dfsas-score-meta">
                    <div class="dfsas-score-meta__row"><span><?php esc_html_e('Active modules','dadsfam-antispam'); ?></span><strong><?php echo $active; ?> / <?php echo count($core_modules); ?></strong></div>
                    <div class="dfsas-score-meta__row"><span>reCAPTCHA</span><strong class="<?php echo $recaptcha_on ? 'dfsas-ok' : 'dfsas-off-text'; ?>"><?php echo $recaptcha_on ? esc_html__('On','dadsfam-antispam') : esc_html__('Off','dadsfam-antispam'); ?></strong></div>
                </div>
                <?php if ($score < 90) : ?>
                <div class="dfsas-card__footer"><a href="<?php echo admin_url('admin.php?page=dfsas-settings'); ?>" class="dfsas-btn dfsas-btn--sm dfsas-btn--primary"><?php esc_html_e('Improve Score','dadsfam-antispam'); ?></a></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Modules + breakdown ─────────────────────────────────────────── -->
        <div class="dfsas-row">
            <div class="dfsas-card" style="flex:1.1;min-width:300px">
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
                    'enable_comments'        => [__('Comment Protection',     'dadsfam-antispam'), false],
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

            <div class="dfsas-card" style="flex:1;min-width:260px">
                <h2 class="dfsas-card__title"><?php esc_html_e('Top Block Reasons','dadsfam-antispam'); ?></h2>
                <?php if (!empty($stats['by_reason'])) :
                    $total_r = array_sum(array_column($stats['by_reason'],'cnt'));
                    foreach ($stats['by_reason'] as $r) :
                        $pct = $total_r > 0 ? round($r['cnt']/$total_r*100) : 0;
                ?>
                <div class="dfsas-reason">
                    <div class="dfsas-reason__row"><span><?php echo esc_html(ucwords(str_replace(['_','-'],' ',$r['reason']))); ?></span><strong><?php echo number_format($r['cnt']); ?></strong></div>
                    <div class="dfsas-bar"><div class="dfsas-bar__fill" style="width:<?php echo $pct; ?>%"></div></div>
                </div>
                <?php endforeach; else : ?>
                    <p class="dfsas-muted"><?php esc_html_e('No spam caught yet — either your forms are clean or this is freshly installed!','dadsfam-antispam'); ?></p>
                <?php endif; ?>
                <div class="dfsas-card__footer"><a href="<?php echo admin_url('admin.php?page=dfsas-logs'); ?>" class="dfsas-btn dfsas-btn--sm"><?php esc_html_e('View Full Log','dadsfam-antispam'); ?></a></div>
            </div>

            <div class="dfsas-card" style="flex:1;min-width:260px">
                <h2 class="dfsas-card__title"><?php esc_html_e('Spam by Source','dadsfam-antispam'); ?></h2>
                <?php if (!empty($stats['by_form'])) :
                    $total_f = array_sum(array_column($stats['by_form'],'cnt'));
                    foreach ($stats['by_form'] as $f) :
                        $pct = $total_f > 0 ? round($f['cnt']/$total_f*100) : 0;
                        $label = $form_labels[$f['form_type']] ?? ucwords(str_replace(['_','-'],' ',$f['form_type']));
                ?>
                <div class="dfsas-reason">
                    <div class="dfsas-reason__row"><span><?php echo esc_html($label); ?></span><strong><?php echo number_format($f['cnt']); ?></strong></div>
                    <div class="dfsas-bar"><div class="dfsas-bar__fill dfsas-bar__fill--alt" style="width:<?php echo $pct; ?>%"></div></div>
                </div>
                <?php endforeach; else : ?>
                    <p class="dfsas-muted"><?php esc_html_e('No data yet. Once spam is blocked, you will see which forms attract the most.','dadsfam-antispam'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Quick actions ───────────────────────────────────────────────── -->
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
