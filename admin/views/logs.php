<?php if ( ! defined( 'ABSPATH' ) ) exit;
$is_pro   = DFSAS_Helpers::is_pro();
$per_page = 25;
$paged    = max( 1, absint( $_GET['paged']  ?? 1 ) );
$offset   = ( $paged - 1 ) * $per_page;
$search   = sanitize_text_field( $_GET['s']      ?? '' );
$reason   = sanitize_text_field( $_GET['reason'] ?? '' );

$args    = [ 'per_page' => $per_page, 'offset' => $offset, 'search' => $search, 'reason' => $reason ];
$entries = DFSAS_Logger::get_entries( $args );
$total   = DFSAS_Logger::count_entries( [ 'search' => $search, 'reason' => $reason ] );
$pages   = max( 1, ceil( $total / $per_page ) );
$stats   = DFSAS_Logger::get_stats();

$reasons_all = [
    'honeypot_filled','honeypot_filled_generic','submitted_too_fast','timestamp_invalid',
    'rate_limited','blocked_ip','blocked_email','blocked_keyword','blocked_domain',
    'content_filter','disposable_email','no_mx_record','dnsbl_blocked','geo_blocked',
    'recaptcha_failed',
];
?>
<div class="wrap dfsas-wrap">
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div class="dfsas-content">

        <?php if ( ! $is_pro ) : ?>
        <div class="dfsas-notice dfsas-notice--info"><?php printf(
            esc_html__( 'Free plan keeps the last 200 entries. %sUpgrade PRO%s for unlimited history, auto-cleanup, and CSV export.', 'dadsfam-antispam' ),
            '<a href="' . admin_url('admin.php?page=dfsas-pro') . '">', '</a>'
        ); ?></div>
        <?php endif; ?>

        <!-- ── Stats strip ────────────────────────────────────────────────── -->
        <div class="dfsas-log-stats-strip">
            <div class="dfsas-log-stat"><strong><?php echo number_format($stats['total']); ?></strong><span><?php esc_html_e('Total','dadsfam-antispam'); ?></span></div>
            <div class="dfsas-log-stat"><strong><?php echo number_format($stats['today']); ?></strong><span><?php esc_html_e('Today','dadsfam-antispam'); ?></span></div>
            <div class="dfsas-log-stat"><strong><?php echo number_format($stats['this_week']); ?></strong><span><?php esc_html_e('This Week','dadsfam-antispam'); ?></span></div>
            <?php if ($stats['top_ip']) : ?>
            <div class="dfsas-log-stat"><strong><?php echo esc_html($stats['top_ip']); ?></strong><span><?php esc_html_e('Top IP','dadsfam-antispam'); ?></span></div>
            <?php endif; ?>
        </div>

        <div class="dfsas-card">
            <!-- ── Toolbar ────────────────────────────────────────────────── -->
            <div class="dfsas-log-toolbar">
                <div class="dfsas-log-toolbar__left">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="dfsas-logs" />
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search IP, email, name…','dadsfam-antispam'); ?>" class="dfsas-search-input" />
                        <select name="reason">
                            <option value=""><?php esc_html_e('All Reasons','dadsfam-antispam'); ?></option>
                            <?php foreach ($reasons_all as $r) : ?>
                            <option value="<?php echo esc_attr($r); ?>" <?php selected($reason,$r); ?>><?php echo esc_html(ucwords(str_replace('_',' ',$r))); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php submit_button(__('Filter','dadsfam-antispam'),'secondary','',false,['class'=>'dfsas-btn dfsas-btn--sm']); ?>
                        <?php if ($search || $reason) : ?><a href="<?php echo admin_url('admin.php?page=dfsas-logs'); ?>" class="dfsas-btn dfsas-btn--sm dfsas-btn--ghost"><?php esc_html_e('Clear','dadsfam-antispam'); ?></a><?php endif; ?>
                    </form>
                </div>
                <div class="dfsas-log-toolbar__right">
                    <span class="dfsas-log-count"><?php printf(esc_html__('%s entries','dadsfam-antispam'), number_format($total)); ?></span>
                    <?php if ($is_pro) : ?><button class="dfsas-btn dfsas-btn--sm dfsas-btn--secondary" id="dfsas-export-csv"><?php esc_html_e('Export CSV','dadsfam-antispam'); ?></button><?php endif; ?>
                    <button class="dfsas-btn dfsas-btn--sm dfsas-btn--danger" id="dfsas-clear-logs"><?php esc_html_e('Clear All','dadsfam-antispam'); ?></button>
                </div>
            </div>

            <?php if ($entries) : ?>
            <div class="dfsas-table-wrap">
                <table class="dfsas-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Form','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('IP Address','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Email','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Reason','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Score','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Actions','dadsfam-antispam'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $row) :
                            $details = json_decode($row['details'] ?? '{}', true) ?: [];
                            $domain  = $row['email'] ? DFSAS_Helpers::email_domain($row['email']) : '';
                            $has_details = ! empty($row['name']) || ! empty($row['subject']) || ! empty($row['page_url']) || ! empty($details);
                        ?>
                        <tr class="dfsas-log-row" data-id="<?php echo absint($row['id']); ?>">
                            <td class="dfsas-td--date"><?php echo esc_html(wp_date('d M Y H:i', strtotime($row['blocked_at']))); ?></td>
                            <td><span class="dfsas-form-badge"><?php echo esc_html($row['form_type']); ?></span></td>
                            <td class="dfsas-td--ip">
                                <?php if ($row['ip_address']) : ?>
                                <code><?php echo esc_html($row['ip_address']); ?></code>
                                <button class="dfsas-icon-btn dfsas-unblock-ip" title="<?php esc_attr_e('Remove rate-limit lockout','dadsfam-antispam'); ?>" data-ip="<?php echo esc_attr($row['ip_address']); ?>">🔓</button>
                                <?php else : ?><span class="dfsas-muted">—</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['email']) : ?>
                                <span class="dfsas-email-cell"><?php echo esc_html($row['email']); ?></span>
                                <?php else : ?><span class="dfsas-muted">—</span><?php endif; ?>
                            </td>
                            <td>
                                <span class="dfsas-reason-tag dfsas-reason-tag--<?php echo esc_attr($row['reason']); ?>">
                                    <?php echo esc_html(ucwords(str_replace('_',' ',$row['reason']))); ?>
                                </span>
                            </td>
                            <td class="dfsas-td--score"><span class="dfsas-score-badge"><?php echo absint($row['score']); ?></span></td>
                            <td class="dfsas-td--actions">
                                <div class="dfsas-action-group">
                                    <?php if ($has_details) : ?>
                                    <button class="dfsas-icon-btn dfsas-toggle-details" data-id="<?php echo absint($row['id']); ?>" title="<?php esc_attr_e('View Details','dadsfam-antispam'); ?>">🔍</button>
                                    <?php endif; ?>
                                    <!-- Quick Block dropdown -->
                                    <?php if ($row['ip_address'] || $row['email']) : ?>
                                    <div class="dfsas-qb-wrap">
                                        <button class="dfsas-icon-btn dfsas-qb-trigger" title="<?php esc_attr_e('Quick Block','dadsfam-antispam'); ?>">🚫</button>
                                        <div class="dfsas-qb-menu">
                                            <div class="dfsas-qb-menu__title"><?php esc_html_e('Add to Blocklist:','dadsfam-antispam'); ?></div>
                                            <?php if ($row['ip_address']) : ?>
                                            <button class="dfsas-qb-btn" data-type="ip" data-value="<?php echo esc_attr($row['ip_address']); ?>">
                                                🌐 <?php echo esc_html__('Block IP: ','dadsfam-antispam') . esc_html($row['ip_address']); ?>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($row['email']) : ?>
                                            <button class="dfsas-qb-btn" data-type="email" data-value="<?php echo esc_attr($row['email']); ?>">
                                                ✉️ <?php echo esc_html__('Block Email: ','dadsfam-antispam') . esc_html($row['email']); ?>
                                            </button>
                                            <?php if ($domain) : ?>
                                            <button class="dfsas-qb-btn" data-type="domain" data-value="<?php echo esc_attr($domain); ?>">
                                                🌍 <?php echo esc_html__('Block Domain: ','dadsfam-antispam') . esc_html($domain); ?>
                                            </button>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                            <div class="dfsas-qb-menu__msg"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <button class="dfsas-icon-btn dfsas-delete-log" data-id="<?php echo absint($row['id']); ?>" title="<?php esc_attr_e('Delete entry','dadsfam-antispam'); ?>">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <?php if ($has_details) : ?>
                        <tr class="dfsas-details-row" id="dfsas-details-<?php echo absint($row['id']); ?>" style="display:none">
                            <td colspan="7">
                                <div class="dfsas-details-panel">
                                    <?php if ($row['name']) : ?>
                                    <div class="dfsas-detail-item"><span class="dfsas-detail-item__label"><?php esc_html_e('Name','dadsfam-antispam'); ?></span><span><?php echo esc_html($row['name']); ?></span></div>
                                    <?php endif; ?>
                                    <?php if ($row['subject']) : ?>
                                    <div class="dfsas-detail-item"><span class="dfsas-detail-item__label"><?php esc_html_e('Subject','dadsfam-antispam'); ?></span><span><?php echo esc_html($row['subject']); ?></span></div>
                                    <?php endif; ?>
                                    <?php if ($row['page_url']) : ?>
                                    <div class="dfsas-detail-item"><span class="dfsas-detail-item__label"><?php esc_html_e('Page','dadsfam-antispam'); ?></span><a href="<?php echo esc_url($row['page_url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($row['page_url']); ?></a></div>
                                    <?php endif; ?>
                                    <?php if (!empty($details)) :
                                        foreach ($details as $dk => $dv) : ?>
                                    <div class="dfsas-detail-item"><span class="dfsas-detail-item__label"><?php echo esc_html(ucwords(str_replace('_',' ',$dk))); ?></span><span><?php echo esc_html(is_array($dv) ? implode(', ',$dv) : $dv); ?></span></div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1) : ?>
            <div class="dfsas-pagination">
                <?php for ($p = 1; $p <= min($pages,10); $p++) : ?>
                <a href="<?php echo add_query_arg(['paged'=>$p,'s'=>$search,'reason'=>$reason],admin_url('admin.php?page=dfsas-logs')); ?>" class="dfsas-page-btn <?php echo $p==$paged?'active':''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
                <?php if ($pages > 10) : ?><span class="dfsas-muted" style="padding:4px 8px">…<?php echo $pages; ?></span><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else : ?>
            <div class="dfsas-empty-state">
                <div class="dfsas-empty-state__icon">🛡️</div>
                <h3><?php esc_html_e('No spam entries found','dadsfam-antispam'); ?></h3>
                <p><?php esc_html_e($search || $reason ? 'No entries match your filter.' : 'Log is empty — either nothing has been blocked yet or the log was cleared.','dadsfam-antispam'); ?></p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
