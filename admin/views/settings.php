<?php if ( ! defined('ABSPATH') ) exit;
$opts  = get_option('nl_options',[]);
$plan  = NL_License::get_plan();
$models = ['gpt-4.1-nano'=>'GPT-4.1 Nano (cheapest)','gpt-4.1-mini'=>'GPT-4.1 Mini','gpt-4o-mini'=>'GPT-4o Mini','gpt-4.1'=>'GPT-4.1','gpt-4o'=>'GPT-4o'];
?>
<div class="wrap"><h1>Netlinking SEO — Settings</h1>
<?php if(!empty($_GET['saved'])): ?><div class="notice notice-success"><p>Settings saved.</p></div><?php endif; ?>
<form method="post" action="<?=admin_url('admin-post.php')?>">
<?php wp_nonce_field('nl_settings'); ?>
<input type="hidden" name="action" value="nl_save_settings">
<h2>License</h2>
<table class="form-table"><tbody>
<tr><th>Email</th><td><input name="email" type="email" class="regular-text" value="<?=esc_attr($opts['email']??get_option('admin_email'))?>"></td></tr>
<tr><th>License Key</th><td><input name="license_key" type="password" class="regular-text" value="<?=esc_attr($opts['license_key']??'')?>">
  <p class="description">Plan: <strong><?=esc_html($plan['type'])?></strong> — Pages: <?=esc_html($plan['pages']??NL_FREE_PAGES)?> — Keywords: <?=esc_html($plan['kw']??NL_FREE_KW)?></p></td></tr>
</tbody></table>

<h2>OpenAI <span style="font-size:12px;color:#888">(optional — keyword expansion)</span></h2>
<table class="form-table"><tbody>
<tr><th>API Key</th><td><input name="openai_key" type="password" class="regular-text" value="<?=esc_attr($opts['openai_key']??'')?>"></td></tr>
<tr><th>Model</th><td><select name="openai_model">
  <?php foreach($models as $v=>$l): ?>
  <option value="<?=esc_attr($v)?>" <?=selected($opts['openai_model']??'gpt-4.1-nano',$v,false)?>><?=esc_html($l)?></option>
  <?php endforeach; ?>
</select></td></tr>
</tbody></table>

<h2>Google Search Console <span style="font-size:12px;color:#888">(optional — backlink monitor)</span></h2>
<table class="form-table"><tbody>
<tr><th>OAuth Client ID</th><td><input name="gsc_client_id" class="regular-text" value="<?=esc_attr($opts['gsc_client_id']??'')?>"></td></tr>
<tr><th>OAuth Client Secret</th><td><input name="gsc_client_secret" type="password" class="regular-text" value="<?=esc_attr($opts['gsc_client_secret']??'')?>">
  <p class="description">Create credentials at <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> → OAuth 2.0 → redirect URI: <code><?=admin_url('admin.php?page=netlinking-backlinks')?></code></p></td></tr>
</tbody></table>
<?php submit_button('Save Settings'); ?>
</form></div>
