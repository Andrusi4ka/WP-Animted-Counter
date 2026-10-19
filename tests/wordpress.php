<?php
/** Optional live integration test: WP_ROOT must point to a local WordPress install. */
$root = getenv('WP_ROOT');
if (!$root || !is_file($root . '/wp-load.php')) {
	fwrite(STDERR, "Set WP_ROOT to a WordPress installation.\n");
	exit(1);
}
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'creativity.local';
$_SERVER['REQUEST_METHOD'] = 'GET';
require $root . '/wp-load.php';
if (!function_exists('wpcunter_render_counter')) { require dirname(__DIR__) . '/wp-cunter.php'; }
if (!post_type_exists('wpcunter')) { do_action('init'); }
$created = array();
$original_user = get_current_user_id();
$original_post = $_POST;
try {
	foreach (array('publish', 'draft') as $status) {
		$id = wp_insert_post(array('post_type'=>'wpcunter', 'post_status'=>$status, 'post_title'=>'WP Animated Counter integration fixture'), true);
		if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
		$created[] = $id;
		update_post_meta($id, '_wpcunter_settings', array('value'=>'123.5', 'suffix'=>'%', 'font_size'=>'44', 'font_size_mobile'=>'24', 'label'=>'Integration check'));
		$html = do_shortcode('[wpcunter_counter id="' . $id . '"]');
		if ($status==='publish' && (strpos($html,'data-final="123.5"')===false || strpos($html,'Integration check')===false || strpos($html,'--wpcunter-number-size-mobile:24px')===false)) { throw new RuntimeException('Published shortcode rendering failed'); }
		if ($status==='draft' && $html!=='') { throw new RuntimeException('Draft counter was exposed'); }
	}
	$admins = get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
	if (!$admins) { throw new RuntimeException('An administrator is needed to verify native saving'); }
	wp_set_current_user($admins[0]);
	$source_settings = get_post_meta($created[0], '_wpcunter_settings', true);
	$source_settings['label'] = 'Quoted "label" and C:\\counter';
	$source_settings['ring_size_mobile'] = '140';
	$source_settings['legacy_custom'] = 'Preserved setting';
	update_post_meta($created[0], '_wpcunter_settings', wp_slash($source_settings));
	update_post_meta($created[0], '_edit_lock', 'test-lock');
	$source = get_post($created[0]);
	$actions = apply_filters('post_row_actions', array('edit'=>'Existing edit action'), $source);
	if (!isset($actions['wpcunter_duplicate']) || strpos($actions['wpcunter_duplicate'], '_wpnonce=')===false || !isset($actions['edit'])) { throw new RuntimeException('Duplicate row action missing or replaced existing actions'); }
	$copy_id = wpcunter_duplicate_counter($created[0]);
	if (is_wp_error($copy_id)) { throw new RuntimeException($copy_id->get_error_message()); }
	$created[] = $copy_id;
	$copy = get_post($copy_id);
	if ($copy_id===$source->ID || $copy->post_status!=='draft' || $copy->post_title!==$source->post_title . ' (Copy)' || (int)$copy->post_author!==get_current_user_id()) { throw new RuntimeException('Duplicate identity, draft status or title incorrect'); }
	if (get_post_meta($copy_id, '_wpcunter_settings', true)!==$source_settings || get_post_meta($copy_id, '_edit_lock', true)!=='') { throw new RuntimeException('Duplicate settings were altered or editor lock was copied'); }
	if (get_post_meta($source->ID, '_wpcunter_settings', true)!==$source_settings || get_post($source->ID)->post_status!=='publish') { throw new RuntimeException('Duplicate changed original counter'); }
	if (!is_wp_error(wpcunter_duplicate_counter(0))) { throw new RuntimeException('Missing source accepted'); }
	$other = clone $source; $other->post_type='post';
	$trashed = clone $source; $trashed->post_status='trash';
	if (wpcunter_can_duplicate($other) || wpcunter_can_duplicate($trashed)) { throw new RuntimeException('Non-counter or trashed source accepted'); }
	wp_set_current_user(0);
	if (!is_wp_error(wpcunter_duplicate_counter($source->ID)) || isset(wpcunter_duplicate_row_action(array(), $source)['wpcunter_duplicate'])) { throw new RuntimeException('Unauthorized duplication allowed'); }
	wp_set_current_user($admins[0]);
	$release_fixture = static function() { return array('version'=>'99.0.0','package'=>'https://github.com/Andrusi4ka/WP-Animted-Counter/releases/download/v99.0.0/wp-animated-counter-99.0.0.zip'); };
	add_filter('pre_site_transient_wpcunter_github_release', $release_fixture);
	try {
		$update = apply_filters('update_plugins_github.com', false, array(), plugin_basename(dirname(__DIR__) . '/wp-cunter.php'), array());
		if (!is_array($update) || $update['version']!=='99.0.0') { throw new RuntimeException('Native update filter failed'); }
		$links = apply_filters('plugin_action_links_' . plugin_basename(dirname(__DIR__) . '/wp-cunter.php'), array());
		if (count($links)!==1 || strpos($links[0], 'wpcunter_check_updates')===false || strpos($links[0], '_wpnonce=')===false) { throw new RuntimeException('Manual update link missing nonce'); }
		$details = apply_filters('plugins_api', false, 'plugin_information', (object)array('slug'=>'wp-cunter'));
		if (!is_object($details) || $details->version!=='99.0.0') { throw new RuntimeException('Plugin details hook failed'); }
		wp_set_current_user(0);
		if (wpcunter_update_link(array())!==array()) { throw new RuntimeException('Unauthorized update link exposed'); }
		wp_set_current_user($admins[0]);
	} finally {
		remove_filter('pre_site_transient_wpcunter_github_release', $release_fixture);
	}
	require_once $root . '/wp-admin/includes/post.php';
	$translated = _wp_translate_postdata(true, array('ID'=>$created[1],'post_ID'=>$created[1],'post_type'=>'wpcunter','post_title'=>'WP Animated Counter integration fixture','post_status'=>'draft','publish'=>'Save'));
	if (is_wp_error($translated) || $translated['post_status']!=='publish') { throw new RuntimeException('Save button no longer publishes through WordPress'); }
	$_POST = array('wpcunter_nonce'=>wp_create_nonce('wpcunter_save'),'wpcunter_settings'=>array('counter_animation'=>'ring_band','value'=>'75.5','duration'=>'2000','slow_down'=>'0','ring_size'=>'240','ring_size_mobile'=>'160','ring_thickness'=>'8','ring_color'=>'#0099ee'));
	wp_update_post($translated);
	$saved = get_post_meta($created[1], '_wpcunter_settings', true);
	if ($saved['value']!=='75.5' || $saved['counter_animation']!=='ring_band' || $saved['slow_down']!=='0') { throw new RuntimeException('Native save did not persist effect settings'); }
	$html=do_shortcode('[wpcunter_counter id="' . $created[1] . '"]');
	if (strpos($html,'wpcunter-band-gradient')===false || strpos($html,'data-duration="2000"')===false) { throw new RuntimeException('Saved gradient did not render'); }
	$url=get_delete_post_link($created[1]);
	if (!$url || strpos($url,'action=trash')===false || strpos($url,'_wpnonce=')===false) { throw new RuntimeException('Delete link does not use protected WordPress trash'); }
	echo "PASS: live WordPress database, publish/save, duplication, permissions, settings, updates, trash link and draft privacy\n";
} finally {
	$_POST = $original_post;
	foreach ($created as $id) { wp_delete_post($id, true); }
	wp_set_current_user($original_user);
}
