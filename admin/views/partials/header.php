<?php if ( ! defined( 'ABSPATH' ) ) exit;
$is_pro   = DFSAS_Helpers::is_pro();
$cur_page = $_GET['page'] ?? 'dadsfam-antispam';
?>
<div class="dfsas-header">
    <div class="dfsas-header__inner">
        <div class="dfsas-logo">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="#1a5e9a"/><line x1="12" y1="8" x2="12" y2="12" stroke="white" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16" r="1" fill="white"/></svg>
            <div>
                <h1><?php esc_html_e('DadsFam Anti-Spam','dadsfam-antispam'); ?></h1>
                <?php if ($is_pro) : ?><span class="dfsas-badge dfsas-badge--pro">PRO</span><?php endif; ?>
            </div>
        </div>
        <nav class="dfsas-header__nav">
            <?php
            $nav = [
                'dadsfam-antispam' => __('Dashboard', 'dadsfam-antispam'),
                'dfsas-settings'   => __('Settings',  'dadsfam-antispam'),
                'dfsas-blocklist'  => __('Blocklist',  'dadsfam-antispam'),
                'dfsas-logs'       => __('Spam Log',   'dadsfam-antispam'),
                'dfsas-changelog'  => __('Changelog',  'dadsfam-antispam'),
            ];
            foreach ($nav as $slug => $label) :
            ?>
            <a href="<?php echo admin_url('admin.php?page='.esc_attr($slug)); ?>" class="<?php echo $cur_page === $slug ? 'active' : ''; ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
            <a href="<?php echo admin_url('admin.php?page=dfsas-pro'); ?>"
               class="<?php echo $is_pro ? '' : 'dfsas-nav--pro'; ?> <?php echo $cur_page === 'dfsas-pro' ? 'active' : ''; ?>">
                <?php echo $is_pro ? esc_html__('⭐ PRO','dadsfam-antispam') : esc_html__('⭐ Go PRO','dadsfam-antispam'); ?>
            </a>
        </nav>
    </div>
</div>
