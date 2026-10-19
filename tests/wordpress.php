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
	echo "PASS: live WordPress database, published shortcode, responsive metadata and draft privacy\n";
} finally {
	foreach ($created as $id) { wp_delete_post($id, true); }
}
