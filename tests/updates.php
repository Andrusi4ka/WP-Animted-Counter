<?php
// Exercise remote metadata without a network request or a WordPress installation.
define('ABSPATH', __DIR__);
define('WPCUNTER_VERSION', '1.9.0');
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
function add_filter(...$args) {}
function add_action(...$args) {}
function plugin_basename($file) { return 'wp-cunter/' . basename($file); }
function get_site_transient($key) { return $GLOBALS['cache'] ?? false; }
function set_site_transient($key, $value, $ttl) { $GLOBALS['cache']=$value; $GLOBALS['ttl']=$ttl; }
function wp_remote_get($url, $args) { $GLOBALS['calls']=($GLOBALS['calls']??0)+1; return $GLOBALS['remote']; }
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code($value) { return $value['code']; }
function wp_remote_retrieve_body($value) { return $value['body']; }
class WP_Error { public function __construct(...$args) {} }
require dirname(__DIR__) . '/includes/updates.php';
function verify($value, $message) { if (!$value) { throw new Exception($message); } }
function remote_release($data, $code=200) { unset($GLOBALS['cache']); $GLOBALS['remote']=array('code'=>$code, 'body'=>json_encode($data)); }
$zip='https://github.com/Andrusi4ka/WP-Animted-Counter/releases/download/v2.0.0/wp-animated-counter-2.0.0.zip';
$data=array('tag_name'=>'v2.0.0','draft'=>false,'prerelease'=>false,'assets'=>array(array('browser_download_url'=>$zip)));
remote_release($data);
$update=wpcunter_plugin_update(false,array(),'wp-cunter/wp-cunter.php',array());
verify($update['version']==='2.0.0' && $update['package']===$zip, 'Valid release supplies native update metadata');
verify($update['slug']==='wp-cunter' && $update['autoupdate']===false, 'Stable plugin slug and no forced automatic updates');
verify($GLOBALS['ttl']===21600, 'Successful metadata cached six hours');
$calls=$GLOBALS['calls']; wpcunter_latest_release();
verify($GLOBALS['calls']===$calls, 'Cached metadata avoids another request');
verify(wpcunter_plugin_update('untouched',array(),'other/other.php',array())==='untouched', 'Other GitHub plugins unaffected');
$info=wpcunter_update_information(false,'plugin_information',(object)array('slug'=>'wp-cunter'));
verify($info->download_link===$zip && $info->version==='2.0.0', 'View details uses matching release');
verify(wpcunter_update_information('original','query_plugins',(object)array('slug'=>'wp-cunter'))==='original', 'Other API actions unaffected');
foreach (array(array_merge($data,array('draft'=>true)),array_merge($data,array('prerelease'=>true)),array_merge($data,array('tag_name'=>'v2.0.0-beta')),array_merge($data,array('assets'=>array(array('browser_download_url'=>'https://evil.example/plugin.zip')))),array_merge($data,array('assets'=>array())),null) as $invalid) {
	remote_release($invalid);
	verify(wpcunter_latest_release()===array(), 'Invalid, draft, prerelease and missing ZIP rejected');
}
remote_release(array(),403);
verify(wpcunter_latest_release()===array() && $GLOBALS['ttl']===300, 'Rate limits fail safely with short cache');
$calls=$GLOBALS['calls']; wpcunter_latest_release();
verify($GLOBALS['calls']===$calls, 'Failures cached to prevent request storms');
unset($GLOBALS['cache']); $GLOBALS['remote']=new WP_Error();
verify(wpcunter_plugin_update(false,array(),'wp-cunter/wp-cunter.php',array())===false, 'Network failures do not advertise an update');
verify(wpcunter_update_information(false,'plugin_information',(object)array('slug'=>'wp-cunter')) instanceof WP_Error, 'Details failure does not fall back to unrelated WordPress.org plugin');
echo "PASS: release metadata, ZIP allowlist, cache, network errors, prereleases and plugin isolation\n";
