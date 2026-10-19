<?php
// Intentionally runs without Woodmart, WordPress bootstrap or WPBakery.
define('ABSPATH', __DIR__);
if (in_array('--autosave', $argv, true)) { define('DOING_AUTOSAVE', true); }
function add_action($hook,$callback,...$args) { $GLOBALS['test_actions'][$hook][]=$callback; }
function add_filter(...$args) {}
function add_shortcode($name,$callback) { $GLOBALS['test_shortcodes'][$name]=$callback; }
function absint($value) { return abs((int)$value); }
function plugins_url($path, $file) { return $path; }
function wp_enqueue_script(...$args) {}
function wp_unique_id($prefix) { static $id=0; return $prefix . ++$id; }
function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES); }
function esc_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES); }
function esc_url($value) { return esc_attr($value); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function wp_unslash($value) { return is_array($value)?array_map('wp_unslash',$value):stripslashes($value); }
function wp_verify_nonce($nonce,$action) { return $nonce==='valid'; }
function current_user_can(...$args) { return $GLOBALS['test_can_edit']??true; }
function wp_is_post_revision($id) { return $id===99; }
function update_post_meta($id,$key,$value) { $GLOBALS['test_saved']=$value; }
function wp_nonce_field(...$args) {}
function remove_meta_box($id,...$args) { $GLOBALS['test_removed_boxes'][]=$id; }
function add_meta_box($id,$title,$callback,...$args) { $GLOBALS['test_boxes'][$id]=$callback; }
function get_delete_post_link($id) { return '/wp-admin/post.php?action=trash&post=' . $id . '&_wpnonce=test'; }
function selected($current,$value,$echo=false) { return (string)$current===(string)$value?' selected="selected"':''; }
function checked($current,$value,$echo=false) { return $current===$value?' checked="checked"':''; }
function sanitize_hex_color($value) { return preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i',$value)?$value:null; }
function get_post($id) { return (object)array('post_type'=>'wpcunter','post_status'=>$id===2?'draft':'publish'); }
function get_post_meta(...$args) { return $GLOBALS['test_metadata'] ?? array('label'=>'Saved counter','value'=>'250'); }
require dirname(__DIR__) . '/wp-cunter.php';
function check($condition,$message) { if (!$condition) throw new Exception($message); }
$first=wpcunter_render_counter(array('id'=>1));
check(strpos($first,'Saved counter')!==false && strpos($first,'data-final="250"')!==false,'Saved rendering');
check($first!==wpcunter_render_counter(array('id'=>1)),'Unique IDs');
check(wpcunter_render_counter(array('id'=>2))==='','Draft privacy');
$html=wpcunter_render_counter(array('label'=>'<script>bad</script>','color_scheme'=>'custom','color'=>'#123456','size'=>'custom','font_size'=>48,'start_value'=>100,'value'=>-5,'counter_animation'=>'invalid'));
check(strpos($html,'<script>')===false && strpos($html,'bad')!==false,'Sanitize labels');
check(strpos($html,'--wpcunter-color:#123456')!==false && strpos($html,'--wpcunter-number-size-desktop:48px')!==false,'Custom styles');
check(strpos($html,'data-start="100"')!==false && strpos($html,'data-final="-5"')!==false,'Descending values');
check(strpos($html,'data-counter-animation="none"')!==false,'Validate placeholders');
check(strpos($html,'woodmart-counter')===false,'Avoid native theme animation');
wpcunter_register_element();
$layout=wpcunter_render_counter(array('counter_animation'=>'placeholder_2','animation_position'=>'right','vertical_align'=>'bottom','label'=>'Projects'));
check(strpos($layout,'animation-position-none')!==false,'Removed placeholder falls back to number only');
check(strpos($layout,'wpcunter-animation-placeholder')===false && strpos($layout,'wpcunter-content')!==false,'No placeholder block is rendered');
check(substr_count($layout,'<div')===substr_count($layout,'</div>'),'Balanced layout markup');
check(strpos($first,'wpcunter-animation-placeholder')===false,'No empty animation slot by default');
$responsive=wpcunter_render_counter(array('size'=>'custom','font_size'=>48,'font_size_tablet'=>32,'font_size_mobile'=>24,'padding'=>20,'padding_tablet'=>0));
check(strpos($responsive,'--wpcunter-number-size-tablet:32px')!==false && strpos($responsive,'--wpcunter-number-size-mobile:24px')!==false,'Device sizes');
check(strpos($responsive,'--wpcunter-padding-tablet:0px')!==false && strpos($responsive,'--wpcunter-padding-mobile:0px')!==false,'Zero override and mobile inheritance');
check(strpos($responsive,'--wpcunter-number-weight-mobile:600')!==false,'Desktop inheritance');
$pixels=wpcunter_render_counter(array('font_size'=>42,'font_size_mobile'=>24,'label_font_size'=>18,'label_font_size_mobile'=>14));
check(strpos($pixels,'--wpcunter-number-size-desktop:42px')!==false && strpos($pixels,'--wpcunter-label-size-mobile:14px')!==false,'Direct numeric sizes');
$legacy=wpcunter_normalize_sizes(array('size'=>'large','size_mobile'=>'small','label_size'=>'default'));
check($legacy['font_size']==='44' && $legacy['font_size_mobile']==='28' && $legacy['label_font_size']==='20.68','Legacy preset conversion');
check(!isset(wpcunter_fields()['size']) && !isset(wpcunter_fields()['label_size']),'No size dropdowns');
$ring=wpcunter_render_counter(array('counter_animation'=>'ring','ring_color'=>'#ab1234','ring_thickness'=>4,'ring_size'=>200,'label'=>'Seconds'));
check(strpos($ring,'<svg')!==false && substr_count($ring,'<circle')===2,'Ring uses track and progress arc');
check(strpos($ring,'stroke-dasharray="100 100"')!==false && strpos($ring,'wpcunter-animation-placeholder')===false,'Real ring instead of placeholder');
$marker=wpcunter_render_counter(array('counter_animation'=>'ring_marker','marker_color'=>'#fefefe','marker_size'=>24,'marker_size_mobile'=>16));
check(substr_count($marker,'<circle')===3 && strpos($marker,'wpcunter-marker-orbit')!==false,'Marker has separate orbit and disk');
check(strpos($marker,'data-sweep="83.333333"')!==false && strpos($marker,'--wpcunter-marker-color:#fefefe')!==false,'Marker follows an open arc');
check(wpcunter_sanitize_settings(array('marker_size'=>200))['marker_size']==='80','Marker size bounded');
$band=wpcunter_render_counter(array('counter_animation'=>'ring_band','ring_size'=>200,'ring_thickness'=>2));
check(substr_count($band,'<circle')===2 && strpos($band,'wpcunter-ring-track')===false,'Band variant has only gradient mask and marker, no visible line');
check(strpos($band,'--wpcunter-ring-stroke-desktop:1;')!==false,'Gradient thickness matches the configured pixel width');
check(strpos($band,'wpcunter-band-gradient')!==false && strpos($band,'<mask')!==false,'Angular gradient is clipped to animated band');
$blue_band=wpcunter_render_counter(array('counter_animation'=>'ring_band','ring_color'=>'#0af'));
check(strpos($blue_band,'--wpcunter-band-start:rgba(0,170,255,0)')!==false,'Gradient begins fully transparent in the selected color');
preg_match('/<mask id="([^"]+)"/',$band,$mask_one);
preg_match('/<mask id="([^"]+)"/',$blue_band,$mask_two);
check($mask_one[1]!==$mask_two[1],'Gradient mask IDs are unique across counters');
check(strpos($ring,'--wpcunter-ring-size-desktop:200px')!==false && strpos($ring,'--wpcunter-ring-color:#ab1234')!==false,'Ring settings render');
$adaptive_ring=wpcunter_render_counter(array('counter_animation'=>'ring','ring_size'=>200,'ring_size_tablet'=>160,'ring_size_mobile'=>120,'ring_thickness'=>4,'ring_thickness_tablet'=>2));
check(strpos($adaptive_ring,'--wpcunter-ring-size-mobile:120px')!==false && strpos($adaptive_ring,'--wpcunter-ring-thickness-mobile:2px')!==false,'Responsive ring size and inherited thickness');
$ring_limits=wpcunter_sanitize_settings(array('ring_size_mobile'=>-1,'ring_thickness_tablet'=>100));
check($ring_limits['ring_size_mobile']==='80' && $ring_limits['ring_thickness_tablet']==='20','Responsive ring limits');
$grouped=wpcunter_render_counter(array('counter_animation'=>'ring','animation_position'=>'left','align'=>'right'));
check(strpos($grouped,'class="wpcunter-placement inline-element"')!==false,'Column-aligned intrinsic-width outer wrapper');
check(strpos($grouped,'wpcunter-counter text-center animation-position-center')!==false,'Ring always uses centered content');
$plain=wpcunter_render_counter(array('counter_animation'=>'none','animation_position'=>'right','align'=>'left'));
check(strpos($plain,'wpcunter-counter text-center animation-position-none')!==false,'Plain counter always centers its text');
foreach (array('left','top','right','center') as $position) {
	$positioned=wpcunter_render_counter(array('counter_animation'=>'ring','animation_position'=>$position,'label'=>'Seconds'));
	check(strpos($positioned,'animation-position-center')!==false,'Legacy position cannot override ring preset');
	$dom=new DOMDocument();
	@$dom->loadHTML($positioned);
	$xpath=new DOMXPath($dom);
	$inside=$xpath->query('//div[@class="wpcunter-ring-frame"]/div[@class="wpcunter-content"]')->length;
	check($inside===1,'Ring content is always inside the circle');
	check($xpath->query('//div[contains(@class,"counter-value")]')->length===1,'One counter per ring layout');
}
$bounded=wpcunter_sanitize_settings(array('ring_size'=>-5,'ring_thickness'=>100,'ring_color'=>'red;display:none'));
check($bounded['ring_size']==='80' && $bounded['ring_thickness']==='20' && $bounded['ring_color']==='','Ring settings are bounded and sanitized');
$timed=wpcunter_render_counter(array('counter_animation'=>'ring','duration'=>1500));
check(strpos($timed,'data-duration="1500"')!==false,'Configured duration reaches animation');
check(strpos($timed,'data-slow-down="1"')!==false,'Slowdown enabled by default');
check(strpos(wpcunter_render_counter(array('slow_down'=>'0')),'data-slow-down="0"')!==false,'Slowdown can be disabled');
check(wpcunter_sanitize_settings(array('duration'=>-1))['duration']==='100' && wpcunter_sanitize_settings(array('duration'=>100000))['duration']==='60000','Duration bounds');
$invalid=wpcunter_render_counter(array('font_size'=>array('bad'),'size'=>array('bad'),'align'=>'invalid','value'=>'INF','color_scheme'=>'custom','color'=>'red;display:none'));
check(strpos($invalid,'text-center')!==false && strpos($invalid,'display:none')===false,'Reject malformed fields and injected CSS');
check(strpos($invalid,'data-final="100"')!==false,'Non-numeric final uses default');
$save=$GLOBALS['test_actions']['save_post_wpcunter'][0];
if (in_array('--autosave', $argv, true)) {
	$_POST=array('wpcunter_nonce'=>'valid','wpcunter_settings'=>array('value'=>'999'));
	$save(1);
	check(!isset($GLOBALS['test_saved']),'Autosave must not overwrite counter settings');
	echo "PASS: autosave preserves counter settings\n";
	exit;
}
$_POST=array('wpcunter_nonce'=>'invalid','wpcunter_settings'=>array('label'=>'Blocked'));
$save(1); check(!isset($GLOBALS['test_saved']),'Reject invalid nonce');
$_POST['wpcunter_nonce']='valid'; $GLOBALS['test_can_edit']=false;
$save(1); check(!isset($GLOBALS['test_saved']),'Reject unauthorized save');
$GLOBALS['test_can_edit']=true;
$save(99); check(!isset($GLOBALS['test_saved']),'Ignore revisions');
$_POST['wpcunter_settings']=array('value'=>'12.5','label'=>'<b>Projects</b>','font_size'=>'20.68','font_size_tablet'=>'','padding_tablet'=>'0','color'=>'invalid');
$save(1); $saved=$GLOBALS['test_saved'];
check($saved['value']==='12.5' && $saved['font_size']==='20.68','Save decimal values');
check($saved['font_size_tablet']==='' && $saved['padding_tablet']==='0','Save inheritance and zero');
check($saved['label']==='Projects' && $saved['color']==='','Sanitize saved text and colors');
$counts=wpcunter_sanitize_settings(array('value'=>1e30,'font_size'=>-10,'margin'=>-5));
check($counts['value']==='1000000000' && $counts['font_size']==='1' && $counts['margin']==='-5','Clamp invalid ranges');
ob_start(); wpcunter_editor((object)array('ID'=>1)); $admin=ob_get_clean();
check(substr_count($admin,'<fieldset')===substr_count($admin,'</fieldset>'),'Balanced editor fieldsets');
check(substr_count($admin,'<div')===substr_count($admin,'</div>'),'Balanced editor divs');
if (in_array('--html', $argv, true)) { echo $admin; exit; }
if (in_array('--browser', $argv, true)) {
	foreach (array('ring','ring_marker','ring_band') as $effect) {
		echo '<section data-fixture="' . $effect . '">' . wpcunter_render_counter(array('counter_animation'=>$effect,'value'=>100,'suffix'=>'%','label'=>'Test','ring_color'=>'#0099ee','ring_size'=>240,'ring_size_mobile'=>160,'ring_thickness'=>10,'ring_thickness_mobile'=>4,'marker_size'=>22,'marker_size_mobile'=>16,'font_size'=>44,'font_size_mobile'=>30,'duration'=>2000,'slow_down'=>0)) . '</section>';
	}
	exit;
}
$GLOBALS['test_actions']['add_meta_boxes_wpcunter'][0]();
check(in_array('submitdiv',$GLOBALS['test_removed_boxes'],true),'Remove default publishing UI');
ob_start(); $GLOBALS['test_boxes']['wpcunter-save']((object)array('ID'=>1,'post_status'=>'publish')); $button=ob_get_clean();
check(strpos($button,'name="publish"')!==false && strpos($button,'>Save</button>')!==false,'Save button submits through native WordPress publishing');
check(strpos($button,'wpcunter-delete-button')!==false,'Saved counters have a delete action');
ob_start(); $GLOBALS['test_boxes']['wpcunter-save']((object)array('ID'=>1,'post_status'=>'auto-draft')); $new_buttons=ob_get_clean();
check(strpos($new_buttons,'wpcunter-delete-button')===false,'Unsaved counters do not have a delete action');
$GLOBALS['test_can_edit']=false;
ob_start(); $GLOBALS['test_boxes']['wpcunter-save']((object)array('ID'=>1,'post_status'=>'publish')); $restricted=ob_get_clean();
check(strpos($restricted,'wpcunter-delete-button')===false,'Delete action respects capabilities');
$GLOBALS['test_can_edit']=true;
$GLOBALS['test_metadata']=array('padding'=>'12','padding_tablet'=>'4','margin'=>'-5','radius'=>'8','background'=>'#abcdef');
$_POST=array('wpcunter_nonce'=>'valid','wpcunter_settings'=>array('value'=>'33','slow_down'=>'0'));
$save(1);
check($GLOBALS['test_saved']['padding']==='12' && $GLOBALS['test_saved']['background']==='#abcdef','Saving retains hidden legacy design settings');
check($GLOBALS['test_saved']['slow_down']==='0','Unchecked slowdown persists');
unset($GLOBALS['test_metadata']);
check(wpcunter_sanitize_settings(array())['duration']==='2000','Default duration is 2000ms');
check(array_keys(wpcunter_fields()['counter_animation'][1])===array('none','ring','ring_marker','ring_band'),'Only supported effects are offered');
check(strpos($admin,'Design options')===false && strpos($admin,'Text alignment')===false,'Removed controls stay absent');
wpcunter_register_shortcode();
check(strpos($GLOBALS['test_shortcodes']['wpcunter_counter'](array('id'=>1)),'Saved counter')!==false,'Primary shortcode is registered');
check(strpos($GLOBALS['test_shortcodes']['wpcunter_saved'](array('id'=>1)),'Saved counter')!==false,'Alias shortcode is registered');
check($GLOBALS['test_shortcodes']['wpcunter_saved'](array())==='' && wpcunter_render_saved(0)==='','Missing ID must not use the global WordPress post');
echo "PASS: independent rendering, saved records, privacy, escaping, styles and compatibility\n";
