<?php
/**
 * Plugin Name: WP Animated Counter
 * Description: Theme-independent counters with an admin editor, shortcodes and optional WPBakery integration.
 * Version: 1.9.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: wp-cunter
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Author: Andrii Boiko
 * Update URI: https://github.com/Andrusi4ka/WP-Animted-Counter
 */

defined( 'ABSPATH' ) || exit;
define('WPCUNTER_VERSION', '1.9.1');
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/responsive.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/updates.php';
require_once __DIR__ . '/includes/duplicates.php';

/** Refresh browser caches after local asset changes without changing release metadata. */
function wpcunter_asset_version($path) {
	return WPCUNTER_VERSION . '.' . filemtime(__DIR__ . '/' . $path);
}

/** WPBakery is optional; no theme APIs are required. */
function wpcunter_register_element() {
	if ( ! function_exists( 'vc_map' ) ) {
		return;
	}
	$map = array('name'=>'Animated Counter — Custom', 'base'=>'wpcunter_counter', 'category'=>'Content', 'description'=>'Counter with inline settings', 'params'=>array());
	$map['icon'] = plugins_url('assets/images/counter-icon.svg', __FILE__);
	foreach (wpcunter_fields() as $key=>$field) {
		if ($field[3]!=='general') { continue; }
		$param = array('type'=>is_array($field[1])?'dropdown':'textfield', 'heading'=>$field[0], 'param_name'=>$key, 'std'=>$field[2], 'group'=>'General');
		if (is_array($field[1])) { $param['value']=array_flip($field[1]); }
		if ($field[1]==='checkbox') { $param['type']='checkbox'; $param['value']=array('Enable'=>'1'); }
		$map['params'][]=$param;
	}
	vc_map( $map );
}
add_action( 'vc_before_init', 'wpcunter_register_element', 30 );

/** Render independently; retain the existing shortcode names and stored settings. */
function wpcunter_render_counter( $atts ) {
	if ( is_array( $atts ) && ! empty( $atts['id'] ) ) {
		return is_scalar($atts['id']) ? wpcunter_render_saved( absint( $atts['id'] ) ) : '';
	}
	$atts      = is_array( $atts ) ? $atts : array();
	$values = wpcunter_sanitize_settings($atts);
	$start = is_numeric($values['start_value']) ? max(-1000000000,min(1000000000,(float)$values['start_value'])) : 0;
	$final = is_numeric($values['value']) ? max(-1000000000,min(1000000000,(float)$values['value'])) : 100;
	$style = wpcunter_responsive_style($values);
	foreach (array('background'=>'background-color', 'color'=>'--wpcunter-color') as $key=>$property) {
		if ($key==='color' && $values['color_scheme']!=='custom') { continue; }
		$color=sanitize_hex_color($values[$key]);
		if ($color) { $style.=$property . ':' . $color . ';'; }
	}
	$is_ring = in_array($values['counter_animation'], array('ring','ring_marker','ring_band'), true);
	$position = $is_ring ? 'center' : 'none';
	$classes='wpcunter-counter text-center animation-position-' . $position . ' color-scheme-' . $values['color_scheme'] . ' ' . $values['el_class'];
	if (function_exists('vc_shortcode_custom_css_class') && isset($atts['css']) && is_string($atts['css'])) { $classes.=' ' . vc_shortcode_custom_css_class($atts['css']); }
	wp_enqueue_script('wpcunter-frontend', plugins_url('assets/frontend.js',__FILE__), array(), wpcunter_asset_version('assets/frontend.js'), true);
	$suffix = $values['suffix'];
	if (preg_match('/^[\p{L}\p{N}]/u', $suffix)) { $suffix = ' ' . $suffix; }
	$number = '<div class="counter-value" data-start="' . esc_attr($start) . '" data-final="' . esc_attr($final) . '" data-slow-down="' . esc_attr($values['slow_down']) . '" data-duration="' . esc_attr($values['duration']) . '"><span class="wpcunter-number" aria-hidden="true">' . esc_html($final) . '</span><span class="wpcunter-suffix" aria-hidden="true">' . esc_html($suffix) . '</span><span class="wpcunter-sr-only">' . esc_html($final . $suffix) . '</span></div>';
	$label = $values['label'] !== '' ? '<div class="counter-label">' . esc_html($values['label']) . '</div>' : '';
	$content = '<div class="wpcunter-content">' . $number . $label . '</div>';
	if ($is_ring) {
		$with_band = $values['counter_animation'] === 'ring_band';
		$with_marker = in_array($values['counter_animation'], array('ring_marker','ring_band'), true);
		$diameter = (int) $values['ring_size'];
		$thickness = (int) $values['ring_thickness'];
		$marker_size = min((int)$values['marker_size'], $diameter/2);
		$stroke = $thickness * 100 / $diameter;
		$radius = 49 - max($stroke, $with_marker ? $marker_size * 100 / $diameter : 0) / 2;
		$style .= '--wpcunter-ring-color:' . (sanitize_hex_color($values['ring_color']) ?: '#cb943e') . ';';
		$circle = ' cx="50" cy="50" r="' . esc_attr($radius) . '" stroke-width="' . esc_attr($stroke) . '"';
		$sweep = $with_marker ? 83.333333 : 100;
		$start_angle = $with_marker ? 120 : -90;
		$style .= '--wpcunter-ring-start:' . $start_angle . 'deg;--wpcunter-marker-color:' . (sanitize_hex_color($values['marker_color']) ?: '#ffffff') . ';';
		$marker = $with_marker ? '<g class="wpcunter-marker-orbit" transform="rotate(420 50 50)"><circle class="wpcunter-ring-marker" cx="' . esc_attr(50+$radius) . '" cy="50" r="' . esc_attr($marker_size * 50 / $diameter) . '"/></g>' : '';
		$band = '';
		if ($with_band) {
			$mask_id = wp_unique_id('wpcunter-band-mask-');
			$hex = ltrim(sanitize_hex_color($values['ring_color']) ?: '#cb943e', '#');
			if (strlen($hex)===3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
			$style .= '--wpcunter-band-start:rgba(' . hexdec(substr($hex,0,2)) . ',' . hexdec(substr($hex,2,2)) . ',' . hexdec(substr($hex,4,2)) . ',0);';
			$band = '<defs><mask id="' . esc_attr($mask_id) . '" maskUnits="userSpaceOnUse" x="0" y="0" width="100" height="100"><circle class="wpcunter-ring-band wpcunter-ring-arc"' . $circle . ' pathLength="100" data-sweep="' . esc_attr($sweep) . '" data-start-angle="' . esc_attr($start_angle) . '" stroke-dasharray="' . esc_attr($sweep) . ' 100" stroke-dashoffset="0"/></mask></defs><foreignObject x="0" y="0" width="100" height="100" mask="url(#' . esc_attr($mask_id) . ')"><div xmlns="http://www.w3.org/1999/xhtml" class="wpcunter-band-gradient"></div></foreignObject>';
		}
		$lines = $with_band ? '' : '<circle class="wpcunter-ring-track"' . $circle . ' pathLength="100" stroke-dasharray="' . esc_attr($sweep) . ' 100"/><circle class="wpcunter-ring-arc"' . $circle . ' pathLength="100" data-sweep="' . esc_attr($sweep) . '" data-start-angle="' . esc_attr($start_angle) . '" stroke-dasharray="' . esc_attr($sweep) . ' 100" stroke-dashoffset="0"/>';
		$svg = '<svg class="wpcunter-ring-svg" viewBox="0 0 100 100" aria-hidden="true" focusable="false">' . $band . $lines . $marker . '</svg>';
		$text = '<div class="wpcunter-content">' . $number . $label . '</div>';
		$content = '<div class="wpcunter-ring-frame">' . $svg . $text . '</div>';
	}
	// inline-element also lets Woodmart flex columns keep this group's natural width.
	return '<div class="wpcunter-placement inline-element"><div id="' . esc_attr(wp_unique_id('wpcunter-')) . '" class="' . esc_attr($classes) . '" style="' . esc_attr($style) . '" data-counter-animation="' . esc_attr($values['counter_animation']) . '">' . $content . '</div></div>';
}

function wpcunter_register_shortcode() {
	add_shortcode( 'wpcunter_counter', 'wpcunter_render_counter' );
}
add_action( 'init', 'wpcunter_register_shortcode', 30 );

// A small shared stylesheet in the head also covers shortcodes in widgets/templates.
add_action('wp_enqueue_scripts', function() {
	wp_enqueue_style('wpcunter-frontend', plugins_url('assets/frontend.css',__FILE__), array(), wpcunter_asset_version('assets/frontend.css'));
});
