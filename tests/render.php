<?php
// Intentionally runs without Woodmart, WordPress bootstrap or WPBakery.
define('ABSPATH', __DIR__);
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
function selected($current,$value,$echo=false) { return (string)$current===(string)$value?' selected="selected"':''; }
function checked($current,$value,$echo=false) { return $current===$value?' checked="checked"':''; }
function sanitize_hex_color($value) { return preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i',$value)?$value:null; }
function get_post($id) { return (object)array('post_type'=>'wpcunter','post_status'=>$id===2?'draft':'publish'); }
function get_post_meta(...$args) { return array('label'=>'Saved counter','value'=>'250'); }
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
check(strpos($layout,'animation-position-right vertical-align-bottom')!==false,'Layout classes');
check(strpos($layout,'wpcunter-animation-placeholder')!==false && strpos($layout,'wpcunter-content')!==false,'Animation and content groups');
check(substr_count($layout,'<div')===substr_count($layout,'</div>'),'Balanced layout markup');
check(strpos($first,'wpcunter-animation-placeholder')===false,'No empty animation slot by default');
ob_start();
wpcunter_layout_editor(wpcunter_values(1));
$editor=ob_get_clean();
check(substr_count($editor,'type="radio"')===9,'Nine visual layout options');
preg_match_all('/src="([^"]+)"/',$editor,$matches);
foreach ($matches[1] as $image) { check(file_exists(dirname(__DIR__) . '/includes/' . $image),'Local layout asset exists'); }
$responsive=wpcunter_render_counter(array('size'=>'custom','font_size'=>48,'font_size_tablet'=>32,'font_size_mobile'=>24,'padding'=>20,'padding_tablet'=>0));
check(strpos($responsive,'--wpcunter-number-size-tablet:32px')!==false && strpos($responsive,'--wpcunter-number-size-mobile:24px')!==false,'Device sizes');
check(strpos($responsive,'--wpcunter-padding-tablet:0px')!==false && strpos($responsive,'--wpcunter-padding-mobile:0px')!==false,'Zero override and mobile inheritance');
check(strpos($responsive,'--wpcunter-number-weight-mobile:600')!==false,'Desktop inheritance');
$pixels=wpcunter_render_counter(array('font_size'=>42,'font_size_mobile'=>24,'label_font_size'=>18,'label_font_size_mobile'=>14));
check(strpos($pixels,'--wpcunter-number-size-desktop:42px')!==false && strpos($pixels,'--wpcunter-label-size-mobile:14px')!==false,'Direct numeric sizes');
$legacy=wpcunter_normalize_sizes(array('size'=>'large','size_mobile'=>'small','label_size'=>'default'));
check($legacy['font_size']==='44' && $legacy['font_size_mobile']==='28' && $legacy['label_font_size']==='20.68','Legacy preset conversion');
check(!isset(wpcunter_fields()['size']) && !isset(wpcunter_fields()['label_size']),'No size dropdowns');
$invalid=wpcunter_render_counter(array('font_size'=>array('bad'),'size'=>array('bad'),'align'=>'invalid','value'=>'INF','color_scheme'=>'custom','color'=>'red;display:none'));
check(strpos($invalid,'text-center')!==false && strpos($invalid,'display:none')===false,'Reject malformed fields and injected CSS');
check(strpos($invalid,'data-final="100"')!==false,'Non-numeric final uses default');
$save=$GLOBALS['test_actions']['save_post_wpcunter'][0];
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
echo "PASS: independent rendering, saved records, privacy, escaping, styles and compatibility\n";
