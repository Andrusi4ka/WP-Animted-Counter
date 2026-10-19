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
	echo "PASS: live WordPress database, native publish/save, gradient settings, trash link and draft privacy\n";
} finally {
	$_POST = $original_post;
	foreach ($created as $id) { wp_delete_post($id, true); }
	wp_set_current_user($original_user);
}
