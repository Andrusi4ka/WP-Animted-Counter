<?php
/** Shared validation for saved settings and shortcode attributes. */
defined('ABSPATH') || exit;

function wpcunter_sanitize_settings($input) {
	$input = is_array($input) ? $input : array();
	$input = array_filter($input, 'is_scalar');
	$input = wpcunter_normalize_sizes($input);
	$clean = array();
	foreach (wpcunter_fields() as $key => $field) {
		$value = isset($input[$key]) ? (string) $input[$key] : $field[2];
		if (is_array($field[1])) {
			$value = array_key_exists($value, $field[1]) ? $value : $field[2];
		} elseif ($field[1] === 'checkbox') {
			$value = in_array($value, array('1','true','yes','on'), true) ? '1' : '0';
		} elseif ($field[1] === 'number') {
			if ($field[3] === 'responsive' && $value === '') {
				$clean[$key] = '';
				continue;
			}
			$is_size = preg_match('/^(font_size|label_font_size)(_|$)/', $key);
			$is_count = in_array($key, array('value', 'start_value'), true);
			$is_signed = $is_count || preg_match('/^margin(_|$)/', $key);
			$number = is_numeric($value) && is_finite((float) $value) ? (float) $value : (float) $field[2];
			$number = max($is_size ? 1 : ($is_signed ? -1000000000 : 0), min(1000000000, $number));
			if (preg_match('/^ring_size(_|$)/', $key)) { $number = max(80, min(1000, $number)); }
			if (preg_match('/^ring_thickness(_|$)/', $key)) { $number = max(1, min(20, $number)); }
			if ($key === 'duration') { $number = max(100, min(60000, $number)); }
			if (preg_match('/^marker_size(_|$)/', $key)) { $number = max(4, min(80, $number)); }
			$value = (string) ($is_size ? round($number, 2) : ($is_count ? $number : (int) $number));
		} elseif (in_array($key, array('color', 'background', 'ring_color','marker_color'), true)) {
			$value = sanitize_hex_color($value) ?: '';
		} else {
			$value = sanitize_text_field($value);
		}
		$clean[$key] = $value;
	}
	return $clean;
}
