<?php
defined('ABSPATH') || exit;

function wpcunter_can_duplicate($post) {
	$type = get_post_type_object('wpcunter');
	return $post && $post->post_type === 'wpcunter' && !in_array($post->post_status, array('trash', 'auto-draft'), true)
		&& $type && current_user_can($type->cap->create_posts) && current_user_can('edit_post', $post->ID);
}

function wpcunter_duplicate_row_action($actions, $post) {
	if (wpcunter_can_duplicate($post)) {
		$url = wp_nonce_url(add_query_arg(array('action'=>'wpcunter_duplicate', 'counter_id'=>$post->ID), admin_url('admin-post.php')), 'wpcunter_duplicate_' . $post->ID);
		$actions['wpcunter_duplicate'] = '<a href="' . esc_url($url) . '" aria-label="' . esc_attr('Duplicate ' . $post->post_title) . '">Duplicate</a>';
	}
	return $actions;
}
add_filter('post_row_actions', 'wpcunter_duplicate_row_action', 10, 2);

/** Copy only counter data, excluding edit locks, publication state and unrelated metadata. */
function wpcunter_duplicate_counter($id) {
	$post = get_post($id);
	if (!wpcunter_can_duplicate($post)) {
		return new WP_Error('wpcunter_duplicate_forbidden', 'This counter cannot be duplicated.');
	}
	$settings = get_post_meta($post->ID, '_wpcunter_settings', true);
	return wp_insert_post(wp_slash(array(
		'post_type'=>'wpcunter',
		'post_status'=>'draft',
		'post_title'=>$post->post_title . ' (Copy)',
		'post_author'=>get_current_user_id(),
		'meta_input'=>is_array($settings) ? array('_wpcunter_settings'=>$settings) : array(),
	)), true);
}

function wpcunter_duplicate_action() {
	$id = isset($_GET['counter_id']) && is_scalar($_GET['counter_id']) ? absint($_GET['counter_id']) : 0;
	check_admin_referer('wpcunter_duplicate_' . $id);
	$result = $id ? wpcunter_duplicate_counter($id) : new WP_Error('wpcunter_duplicate_missing', 'Counter not found.');
	if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message())); }
	wp_safe_redirect(get_edit_post_link($result, 'raw'));
	exit;
}
add_action('admin_post_wpcunter_duplicate', 'wpcunter_duplicate_action');
