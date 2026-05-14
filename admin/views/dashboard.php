<?php if ( ! defined('ABSPATH') ) exit;
global $wpdb;
$plan      = NL_License::get_plan();
$kw_count  = NL_Keywords::count();
$lk_int    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nl_links WHERE type='internal'");
$lk_ext    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nl_links WHERE type='external'");
$lk_spo    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nl_links WHERE type='sponsored'");
$last_sync = get_option('nl_gsc_last_sync','—');
?>
<div class="wrap">
<h1>Netlinking SEO — Dashboard</h1>
<div style="display:flex;gap:16px;flex-wrap:wrap;margin:20px 0">
<?php foreach([
    ['Plan',       $plan['type'] === 'pro' ? '⭐ PRO' : 'FREE', '#e8f5e9'],
    ['Keywords',   $kw_count . ' / ' . ($plan['kw'] ?? NL_FREE_KW), '#e3f2fd'],
    ['Int. Links', $lk_int, '#f3e5f5'],
    ['Ext. Links', $lk_ext, '#fff3e0'],
    ['Sponsored',  $lk_spo, '#fce4ec'],
    ['GSC Sync',   $last_sync, '#e0f7fa'],
] as [$label,$val,$bg]): ?>
<div style="background:<?=$bg?>;padding:16px 24px;border-radius:6px;min-width:140px">
  <div style="font-size:11px;text-transform:uppercase;color:#666"><?=esc_html($label)?></div>
  <div style="font-size:24px;font-weight:700"><?=esc_html($val)?></div>
</div>
<?php endforeach; ?>
</div>
<?php if($plan['type']==='free'): ?>
<div class="notice notice-info inline"><p>
  Free plan: <?=NL_FREE_PAGES?> pages · <?=NL_FREE_KW?> keywords.
  <a href="<?=admin_url('admin.php?page=netlinking-settings')?>"><strong>Enter license key to upgrade →</strong></a>
</p></div>
<?php endif; ?>
</div>
