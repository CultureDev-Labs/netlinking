<?php if ( ! defined('ABSPATH') ) exit;
global $wpdb;
$filter   = sanitize_text_field($_GET['type'] ?? 'all');
$where    = $filter !== 'all' ? $wpdb->prepare("WHERE type=%s",$filter) : '';
$rows     = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nl_links $where ORDER BY created_at DESC LIMIT 200");
$gsc_ok   = NL_GSC::is_connected();
$auth_url = NL_GSC::get_auth_url();
?>
<div class="wrap">
<h1>Backlink Monitor — External Links</h1>

<?php if(!$gsc_ok): ?>
<div class="notice notice-warning inline"><p>
  <strong>Google Search Console not connected.</strong>
  <?php if($auth_url): ?>
  <a href="<?=esc_url($auth_url)?>" class="button button-primary" style="margin-left:8px">Connect GSC →</a>
  <?php else: ?>
  <a href="<?=admin_url('admin.php?page=netlinking-settings')?>">Add GSC credentials in Settings first.</a>
  <?php endif; ?>
</p></div>
<?php else: ?>
<div class="notice notice-success inline"><p>✅ GSC connected — last sync: <?=esc_html(get_option('nl_gsc_last_sync','never'))?></p></div>
<?php endif; ?>

<ul class="subsubsub" style="margin:12px 0">
  <?php foreach(['all'=>'All','internal'=>'Internal','external'=>'External','sponsored'=>'Sponsored'] as $k=>$l): ?>
  <li><a href="<?=admin_url('admin.php?page=netlinking-backlinks&type='.$k)?>" <?=$filter===$k?'style="font-weight:700"':''?>><?=esc_html($l)?></a> |</li>
  <?php endforeach; ?>
</ul>

<table class="wp-list-table widefat fixed striped">
<thead><tr><th>Source Page</th><th>Target URL</th><th>Anchor</th><th>Type</th><th>Status</th><th>Check</th></tr></thead>
<tbody>
<?php foreach($rows as $r):
  $src = $r->source_post_id ? get_the_title($r->source_post_id).' (#'.$r->source_post_id.')' : '—';
?>
<tr>
  <td><?=esc_html($src)?></td>
  <td style="word-break:break-all"><a href="<?=esc_url($r->target_url)?>" target="_blank"><?=esc_html($r->target_url)?></a></td>
  <td><?=esc_html($r->anchor)?></td>
  <td><span class="nl-badge nl-<?=esc_attr($r->type)?>"><?=esc_html($r->type)?></span></td>
  <td><?=esc_html($r->status)?></td>
  <td><button class="button button-small nl-check" data-url="<?=esc_attr($r->target_url)?>" data-nonce="<?=wp_create_nonce('nl_check')?>">Check</button></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<style>
.nl-badge{padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600}
.nl-internal{background:#e3f2fd;color:#1565c0}.nl-external{background:#fff3e0;color:#e65100}.nl-sponsored{background:#fce4ec;color:#c62828}
</style>
<script>
document.querySelectorAll('.nl-check').forEach(btn=>{
  btn.addEventListener('click',()=>{
    btn.textContent='...';
    fetch(ajaxurl,{method:'POST',body:new URLSearchParams({action:'nl_check_link',url:btn.dataset.url,_ajax_nonce:btn.dataset.nonce})})
    .then(r=>r.json()).then(d=>{btn.textContent=d.data?.code||'ERR'});
  });
});
</script>
