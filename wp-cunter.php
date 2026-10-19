<?php
/**
 * Plugin Name: WP Animated Counter
 * Description: Theme-independent counters with an admin editor, shortcodes and optional WPBakery integration.
 * Version: 1.8.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: wp-cunter
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Author: Andrii Boiko
 * Update URI: https://github.com/Andrusi4ka/WP-Animted-Counter
 */

defined( 'ABSPATH' ) || exit;
define('WPCUNTER_VERSION', '1.8.0');
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/responsive.php';
require_once __DIR__ . '/includes/settings.php';

/** WPBakery is optional; no theme APIs are required. */
function wpcunter_register_element() {
	if ( ! function_exists( 'vc_map' ) ) {
		return;
	}
	$map = array('name'=>'Animated Counter — Custom', 'base'=>'wpcunter_counter', 'category'=>'Content', 'description'=>'Counter with inline settings', 'params'=>array());
	foreach (wpcunter_fields() as $key=>$field) {
		if ($field[3]==='responsive') { continue; }
		$param = array('type'=>is_array($field[1])?'dropdown':'textfield', 'heading'=>$field[0], 'param_name'=>$key, 'std'=>$field[2], 'group'=>array('general'=>'General', 'design'=>'Design options', 'animations'=>'Animations')[$field[3]]);
		if (is_array($field[1])) { $param['value']=array_flip($field[1]); }
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
	$classes='wpcunter-counter text-' . $values['align'] . ' animation-position-' . $values['animation_position'] . ' vertical-align-' . $values['vertical_align'] . ' color-scheme-' . $values['color_scheme'] . ' ' . $values['el_class'];
	if (function_exists('vc_shortcode_custom_css_class') && isset($atts['css']) && is_string($atts['css'])) { $classes.=' ' . vc_shortcode_custom_css_class($atts['css']); }
	wp_enqueue_script('wpcunter-frontend', plugins_url('assets/frontend.js',__FILE__), array(), WPCUNTER_VERSION, true);
	$suffix = $values['suffix'];
	if (preg_match('/^[\p{L}\p{N}]/u', $suffix)) { $suffix = ' ' . $suffix; }
	$animation = '';
	if ($values['counter_animation'] !== 'none') {
		$animation = '<span class="wpcunter-animation-placeholder" aria-hidden="true">' . esc_html(substr($values['counter_animation'], -1)) . '</span>';
	}
	$number = '<div class="counter-value" data-start="' . esc_attr($start) . '" data-final="' . esc_attr($final) . '" data-duration="3000"><span class="wpcunter-number" aria-hidden="true">' . esc_html($final) . '</span><span class="wpcunter-suffix" aria-hidden="true">' . esc_html($suffix) . '</span><span class="wpcunter-sr-only">' . esc_html($final . $suffix) . '</span></div>';
	$label = $values['label'] !== '' ? '<div class="counter-label">' . esc_html($values['label']) . '</div>' : '';
	return '<div id="' . esc_attr(wp_unique_id('wpcunter-')) . '" class="' . esc_attr($classes) . '" style="' . esc_attr($style) . '" data-counter-animation="' . esc_attr($values['counter_animation']) . '">' . $animation . '<div class="wpcunter-content">' . $number . $label . '</div></div>';
}

function wpcunter_register_shortcode() {
	add_shortcode( 'wpcunter_counter', 'wpcunter_render_counter' );
}
add_action( 'init', 'wpcunter_register_shortcode', 30 );

// A small shared stylesheet in the head also covers shortcodes in widgets/templates.
add_action('wp_enqueue_scripts', function() {
	wp_enqueue_style('wpcunter-frontend', plugins_url('assets/frontend.css',__FILE__), array(), WPCUNTER_VERSION);
});
