<?php if ( ! defined('ABSPATH') ) exit;
global $wpdb;
$rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nl_keywords ORDER BY weight DESC");
$opts = get_option('nl_options',[]);
$has_openai = !empty($opts['openai_key']);
?>
<div class="wrap">
<h1>Keywords <a href="#add-kw" class="page-title-action">+ Add</a></h1>
<?php if(!empty($_GET['saved'])): ?><div class="notice notice-success"><p>Saved.</p></div><?php endif; ?>

<table class="wp-list-table widefat fixed striped" style="margin-top:16px">
<thead><tr><th>Keyword</th><th>Target URL</th><th>Type</th><th>Weight</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><strong><?=esc_html($r->keyword)?></strong></td>
  <td><a href="<?=esc_url($r->target_url)?>" target="_blank"><?=esc_html($r->target_url)?></a></td>
  <td><?=esc_html($r->type)?></td>
  <td><?=(int)$r->weight?>/10</td>
  <td>
    <?php if($has_openai): ?>
    <button class="button button-small nl-expand" data-id="<?=(int)$r->id?>" data-nonce="<?=wp_create_nonce('nl_expand')?>">AI Expand</button>
    <?php endif; ?>
    <form method="post" action="<?=admin_url('admin-post.php')?>" style="display:inline">
      <?php wp_nonce_field('nl_del_kw_'.(int)$r->id); ?>
      <input type="hidden" name="action" value="nl_delete_keyword">
      <input type="hidden" name="id" value="<?=(int)$r->id?>">
      <button class="button button-small button-link-delete" onclick="return confirm('Delete?')">Delete</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table>

<h2 id="add-kw" style="margin-top:32px">Add / Edit Keyword</h2>
<form method="post" action="<?=admin_url('admin-post.php')?>">
  <?php wp_nonce_field('nl_keyword'); ?>
  <input type="hidden" name="action" value="nl_save_keyword">
  <table class="form-table"><tbody>
  <tr><th>Keyword</th><td><input name="keyword" class="regular-text" required></td></tr>
  <tr><th>Target URL</th><td><input name="target_url" type="url" class="regular-text" required></td></tr>
  <tr><th>Type</th><td><select name="type"><option value="internal">Internal</option><option value="sponsored">Sponsored</option></select></td></tr>
  <tr><th>Weight (1-10)</th><td><input name="weight" type="number" min="1" max="10" value="5" style="width:60px"></td></tr>
  </tbody></table>
  <?php submit_button('Save Keyword'); ?>
</form>
</div>
<script>
document.querySelectorAll('.nl-expand').forEach(btn=>{
  btn.addEventListener('click',()=>{
    btn.disabled=true; btn.textContent='...';
    fetch(ajaxurl,{method:'POST',body:new URLSearchParams({action:'nl_expand_kw',kw_id:btn.dataset.id,_ajax_nonce:btn.dataset.nonce})})
    .then(()=>location.reload());
  });
});
</script>
