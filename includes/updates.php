<?php
/** Updates use the release ZIP, whose root is the existing wp-cunter directory. */
defined('ABSPATH') || exit;

function wpcunter_latest_release() {
	$cached = get_site_transient('wpcunter_github_release');
	if ($cached !== false) { return $cached; }
	$response = wp_remote_get('https://api.github.com/repos/Andrusi4ka/WP-Animted-Counter/releases/latest', array(
		'timeout'=>10,
		'headers'=>array('Accept'=>'application/vnd.github+json', 'User-Agent'=>'WP-Animated-Counter/' . WPCUNTER_VERSION),
	));
	$release = array();
	if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
		$data = json_decode(wp_remote_retrieve_body($response), true);
		if (is_array($data) && empty($data['draft']) && empty($data['prerelease']) && isset($data['tag_name']) && is_string($data['tag_name']) && preg_match('/^v?(\d+\.\d+\.\d+)$/D', $data['tag_name'], $matches)) {
			$version = $matches[1];
			$expected = 'https://github.com/Andrusi4ka/WP-Animted-Counter/releases/download/' . $data['tag_name'] . '/wp-animated-counter-' . $version . '.zip';
			foreach (isset($data['assets']) && is_array($data['assets']) ? $data['assets'] : array() as $asset) {
				if (is_array($asset) && isset($asset['browser_download_url']) && $asset['browser_download_url'] === $expected) {
					$release = array('version'=>$version, 'package'=>$expected);
					break;
				}
			}
		}
	}
	// Briefly cache failures too, avoiding repeated requests when GitHub is unavailable.
	set_site_transient('wpcunter_github_release', $release, $release ? 6 * HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS);
	return $release;
}

function wpcunter_plugin_update($update, $plugin_data, $plugin_file, $locales) {
	if ($plugin_file !== plugin_basename(dirname(__DIR__) . '/wp-cunter.php')) { return $update; }
	$release = wpcunter_latest_release();
	if (!$release) { return false; }
	return array_merge($release, array('id'=>'https://github.com/Andrusi4ka/WP-Animted-Counter', 'slug'=>'wp-cunter', 'url'=>'https://github.com/Andrusi4ka/WP-Animted-Counter/releases', 'requires'=>'6.0', 'requires_php'=>'7.4', 'autoupdate'=>false));
}
add_filter('update_plugins_github.com', 'wpcunter_plugin_update', 10, 4);

function wpcunter_update_information($result, $action, $args) {
	if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== 'wp-cunter') { return $result; }
	$release = wpcunter_latest_release();
	if (!$release) { return new WP_Error('wpcunter_updates_unavailable', 'Could not retrieve WP Animated Counter release information. Please try again later.'); }
	return (object) array('name'=>'WP Animated Counter', 'slug'=>'wp-cunter', 'version'=>$release['version'], 'author'=>'Andrii Boiko', 'homepage'=>'https://github.com/Andrusi4ka/WP-Animted-Counter', 'requires'=>'6.0', 'requires_php'=>'7.4', 'download_link'=>$release['package'], 'sections'=>array('description'=>'Theme-independent animated counters with responsive settings and optional WPBakery integration.', 'changelog'=>'<a href="https://github.com/Andrusi4ka/WP-Animted-Counter/releases" target="_blank" rel="noopener noreferrer">Read release notes on GitHub</a>'));
}
add_filter('plugins_api', 'wpcunter_update_information', 10, 3);

function wpcunter_update_link($links) {
	if (current_user_can('update_plugins')) {
		$url = wp_nonce_url(admin_url('admin-post.php?action=wpcunter_check_updates'), 'wpcunter_check_updates');
		$links[] = '<a href="' . esc_url($url) . '">Check for updates</a>';
	}
	return $links;
}
add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__) . '/wp-cunter.php'), 'wpcunter_update_link');

function wpcunter_check_updates() {
	if (!current_user_can('update_plugins')) { wp_die('You are not allowed to update plugins.'); }
	check_admin_referer('wpcunter_check_updates');
	delete_site_transient('wpcunter_github_release');
	$release = wpcunter_latest_release();
	delete_site_transient('update_plugins');
	wp_update_plugins();
	$status = !$release ? 'unavailable' : (version_compare($release['version'], WPCUNTER_VERSION, '>') ? 'available' : 'current');
	wp_safe_redirect(add_query_arg('wpcunter_update', $status, self_admin_url('plugins.php')));
	exit;
}
add_action('admin_post_wpcunter_check_updates', 'wpcunter_check_updates');

function wpcunter_update_notice() {
	if (!current_user_can('update_plugins') || !isset($_GET['wpcunter_update']) || !is_string($_GET['wpcunter_update'])) { return; }
	$messages = array('available'=>'A new WP Animated Counter version is available. Use Update now in the plugin list.', 'current'=>'WP Animated Counter is up to date.', 'unavailable'=>'Could not check WP Animated Counter updates. Please try again later.');
	$status = $_GET['wpcunter_update'];
	if (isset($messages[$status])) {
		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($messages[$status]) . '</p></div>';
	}
}
add_action('admin_notices', 'wpcunter_update_notice');
