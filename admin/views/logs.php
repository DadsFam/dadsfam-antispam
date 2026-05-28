<?php if ( ! defined( 'ABSPATH' ) ) exit;
$is_pro  = DFSAS_Helpers::is_pro();
$per_page = 25;
$paged    = max(1, absint($_GET['paged'] ?? 1));
$offset   = ($paged - 1) * $per_page;
$search   = sanitize_text_field($_GET['s'] ?? '');
$reason   = sanitize_text_field($_GET['reason'] ?? '');

$args    = compact('per_page','offset','search','reason') + ['per_page'=>$per_page];
$entries = DFSAS_Logger::get_entries($args);
$total   = DFSAS_Logger::count_entries(compact('search','reason'));
$pages   = max(1, ceil($total / $per_page));

$reasons_all = ['honeypot_filled','submitted_too_fast','timestamp_invalid','rate_limited',
                'blocked_ip','blocked_email','blocked_keyword','content_filter',
                'disposable_email','no_mx_record','dnsbl_blocked','geo_blocked'];
?>
<div class="wrap dfsas-wrap">
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div class="dfsas-content">

        <?php if (!$is_pro) : ?>
        <div class="dfsas-notice dfsas-notice--info"><?php printf(esc_html__('Free plan keeps the last 200 log entries. %sUpgrade PRO%s for unlimited history, auto-cleanup, and CSV export.','dadsfam-antispam'),'<a href="'.admin_url('admin.php?page=dfsas-pro').'">','</a>'); ?></div>
        <?php endif; ?>

        <div class="dfsas-card">
            <div class="dfsas-log-toolbar">
                <div class="dfsas-log-toolbar__left">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="dfsas-logs" />
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search IP, email, name...','dadsfam-antispam'); ?>" class="dfsas-search-input" />
                        <select name="reason">
                            <option value=""><?php esc_html_e('All Reasons','dadsfam-antispam'); ?></option>
                            <?php foreach ($reasons_all as $r) : ?>
                            <option value="<?php echo esc_attr($r); ?>" <?php selected($reason,$r); ?>><?php echo esc_html(str_replace('_',' ',$r)); ?></option>
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
                            <th><?php esc_html_e('IP','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Email','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Name','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Reason','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Score','dadsfam-antispam'); ?></th>
                            <th><?php esc_html_e('Actions','dadsfam-antispam'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $row) : ?>
                        <tr data-id="<?php echo absint($row['id']); ?>">
                            <td class="dfsas-td--date"><?php echo esc_html(wp_date('d M Y H:i', strtotime($row['blocked_at']))); ?></td>
                            <td><span class="dfsas-form-badge"><?php echo esc_html($row['form_type']); ?></span></td>
                            <td class="dfsas-td--ip">
                                <code><?php echo esc_html($row['ip_address']); ?></code>
                                <button class="dfsas-icon-btn dfsas-unblock-ip" title="<?php esc_attr_e('Unblock IP','dadsfam-antispam'); ?>" data-ip="<?php echo esc_attr($row['ip_address']); ?>">🔓</button>
                            </td>
                            <td><?php echo $row['email'] ? esc_html($row['email']) : '<span class="dfsas-muted">—</span>'; ?></td>
                            <td><?php echo $row['name'] ? esc_html($row['name']) : '<span class="dfsas-muted">—</span>'; ?></td>
                            <td><span class="dfsas-reason-tag dfsas-reason-tag--<?php echo esc_attr($row['reason']); ?>"><?php echo esc_html(str_replace('_',' ',$row['reason'])); ?></span></td>
                            <td class="dfsas-td--score"><span class="dfsas-score-badge"><?php echo absint($row['score']); ?></span></td>
                            <td>
                                <button class="dfsas-icon-btn dfsas-delete-log" data-id="<?php echo absint($row['id']); ?>" title="<?php esc_attr_e('Delete','dadsfam-antispam'); ?>">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1) : ?>
            <div class="dfsas-pagination">
                <?php for ($p = 1; $p <= $pages; $p++) : ?>
                <a href="<?php echo add_query_arg(['paged'=>$p,'s'=>$search,'reason'=>$reason], admin_url('admin.php?page=dfsas-logs')); ?>" class="dfsas-page-btn <?php echo $p == $paged ? 'active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php else : ?>
            <div class="dfsas-empty-state">
                <div class="dfsas-empty-state__icon">🛡️</div>
                <h3><?php esc_html_e('No spam entries found','dadsfam-antispam'); ?></h3>
                <p><?php esc_html_e($search || $reason ? 'No entries match your filter.' : 'Your log is empty — either nothing has been blocked yet, or you cleared the log.', 'dadsfam-antispam'); ?></p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
