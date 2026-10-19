<?php
defined( 'ABSPATH' ) || exit;

function wpcunter_fields() {
	$fields = array(
		'start_value' => array( 'Start value', 'number', '0', 'general' ),
		'value' => array( 'Final value', 'number', '100', 'general' ),
		'suffix' => array( 'Suffix', 'text', '', 'general' ),
		'font_size' => array( 'Number size, px', 'number', '36', 'general' ),
		'font_weight' => array( 'Number font weight', array( '100'=>'100', '200'=>'200', '300'=>'300', '400'=>'400', '500'=>'500', '600'=>'600', '700'=>'700', '800'=>'800', '900'=>'900' ), '600', 'general' ),
		'label' => array( 'Label', 'text', '', 'general' ),
		'label_font_size' => array( 'Text size, px', 'number', '17', 'general' ),
		'label_weight' => array( 'Text font weight', array(''=>'Default','100'=>'100','200'=>'200','300'=>'300','400'=>'400','500'=>'500','600'=>'600','700'=>'700','800'=>'800','900'=>'900'), '', 'general' ),
		'color_scheme' => array( 'Color scheme', array( ''=>'Inherited', 'light'=>'Light', 'dark'=>'Dark', 'custom'=>'Custom' ), '', 'general' ),
		'color' => array( 'Custom color', 'color', '#333333', 'general' ),
		'el_class' => array( 'Extra CSS classes', 'text', '', 'general' ),
		'counter_animation' => array( 'Animation', array( 'none'=>'None — number only', 'ring'=>'Ring fill', 'ring_marker'=>'Arc with marker', 'ring_band'=>'Gradient arc with marker' ), 'none', 'general' ),
		'duration' => array( 'Animation duration, ms', 'number', '2000', 'general' ),
		'slow_down' => array( 'Slow down at the end', 'checkbox', '1', 'general' ),
		'ring_size' => array( 'Ring diameter, px', 'number', '160', 'general' ),
		'ring_thickness' => array( 'Ring thickness, px', 'number', '2', 'general' ),
		'ring_color' => array( 'Ring color', 'color', '#cb943e', 'general' ),
		'marker_size' => array( 'Marker diameter, px', 'number', '22', 'general' ),
		'marker_color' => array( 'Marker color', 'color', '#ffffff', 'general' ),
		'padding' => array( 'Padding, px', 'number', '0', 'design' ),
		'margin' => array( 'Margin, px', 'number', '0', 'design' ),
		'background' => array( 'Background color', 'text', '', 'design' ),
		'radius' => array( 'Border radius, px', 'number', '0', 'design' ),
	);
	foreach (wpcunter_responsive_keys() as $key) {
		foreach (array('tablet','mobile') as $device) {
			$field=$fields[$key];
			$field[0].=' — ' . ucfirst($device);
			$field[2]='';
			$field[3]='responsive';
			if (is_array($field[1])) { $field[1]=array(''=>'Inherit')+$field[1]; }
			$fields[$key . '_' . $device]=$field;
		}
	}
	return $fields;
}

function wpcunter_responsive_keys() {
	return array('font_size','font_weight','label_font_size','label_weight','padding','margin','radius','ring_size','ring_thickness','marker_size');
}

function wpcunter_responsive_field($key,$field,$values) {
	echo '<div class="wpcunter-field wpcunter-responsive" data-field="' . esc_attr($key) . '"><div class="wpcunter-field-head"><label for="wpcunter-' . esc_attr($key) . '">' . esc_html($field[0]) . '</label><span class="wpcunter-devices" role="group" aria-label="Device">';
	$devices=array('desktop'=>array('Desktop (1025px and wider)','desktop'),'tablet'=>array('Tablet (768–1024px)','tablet'),'mobile'=>array('Mobile (below 768px)','smartphone'));
	foreach ($devices as $device=>$meta) {
		echo '<button type="button" data-device="' . esc_attr($device) . '" title="' . esc_attr($meta[0]) . '" aria-label="' . esc_attr($meta[0]) . '" aria-pressed="' . ($device==='desktop'?'true':'false') . '"><span aria-hidden="true" class="dashicons dashicons-' . esc_attr($meta[1]) . '"></span></button>';
	}
	echo '</span></div>';
	foreach ($devices as $device=>$meta) {
		$storage=$key . ($device==='desktop'?'':'_' . $device);
		$attrs=' id="wpcunter-' . esc_attr($storage) . '" name="wpcunter_settings[' . esc_attr($storage) . ']" data-device-input="' . esc_attr($device) . '" aria-label="' . esc_attr($field[0] . ' — ' . $meta[0]) . '"' . ($device==='desktop'?'':' hidden');
		if (is_array($field[1])) {
			echo '<select' . $attrs . '>';
			$choices=$device==='desktop'?$field[1]:array(''=>'Inherit')+$field[1];
			foreach ($choices as $value=>$label) { echo '<option value="' . esc_attr($value) . '"' . selected($values[$storage],$value,false) . '>' . esc_html($label) . '</option>'; }
			echo '</select>';
		} else {
			$is_size=in_array($key,array('font_size','label_font_size'),true);
			if ($key==='ring_size') { $attrs .= ' min="80" max="1000"'; }
			if ($key==='ring_thickness') { $attrs .= ' min="1" max="20"'; }
			if ($key==='marker_size') { $attrs .= ' min="4" max="80"'; }
			echo '<input type="number" step="' . ($is_size?'0.01':'1') . '"' . ($is_size?' min="1"':'') . $attrs . ' value="' . esc_attr($values[$storage]) . '"' . ($device==='desktop'?' required':' placeholder="Inherit"') . '>';
		}
	}
	echo '</div>';
}

function wpcunter_values( $id ) {
	$saved = get_post_meta( $id, '_wpcunter_settings', true );
	return wpcunter_sanitize_settings($saved);
}

add_action( 'init', function() {
	register_post_type( 'wpcunter', array(
		'labels'=>array( 'name'=>'Counters', 'singular_name'=>'Counter', 'menu_name'=>'Animated Counter', 'add_new'=>'Add counter', 'add_new_item'=>'New counter', 'edit_item'=>'Edit counter', 'all_items'=>'All counters' ),
		'public'=>false, 'show_ui'=>true, 'show_in_rest'=>false, 'supports'=>array('title'), 'menu_icon'=>'dashicons-performance', 'rewrite'=>false,
	) );
} );

add_action( 'add_meta_boxes_wpcunter', function() {
	remove_meta_box('submitdiv', 'wpcunter', 'side');
	add_meta_box('wpcunter-save', 'Save', function($post) {
		echo '<div class="wpcunter-save-actions"><button type="submit" name="publish" id="wpcunter-save-button" class="button button-primary button-large" value="Save">Save</button>';
		if ($post->post_status !== 'auto-draft' && current_user_can('delete_post', $post->ID)) {
			$url = get_delete_post_link($post->ID);
			if ($url) { echo '<a class="button button-large wpcunter-delete-button" href="' . esc_url($url) . '">Delete</a>'; }
		}
		echo '</div>';
	}, 'wpcunter', 'side', 'high');
	add_meta_box( 'wpcunter-editor', 'Animated Counter — Settings', 'wpcunter_editor', 'wpcunter', 'normal', 'high' );
	add_meta_box( 'wpcunter-shortcode', 'Embed counter', function( $post ) {
		echo '<p>Save the counter, then insert this shortcode:</p><input class="widefat" readonly aria-label="Shortcode" value="' . esc_attr( '[wpcunter_counter id="' . $post->ID . '"]' ) . '">';
	}, 'wpcunter', 'side' );
} );

function wpcunter_editor( $post ) {
	wp_nonce_field( 'wpcunter_save', 'wpcunter_nonce' );
	$values = wpcunter_values( $post->ID );
	echo '<div class="wpcunter-editor"><div class="wpcunter-panel" id="wpcunter-panel-general"><div class="wpcunter-grid"><div class="wpcunter-column wpcunter-column-left"><fieldset class="wpcunter-settings-block"><legend>Numbers</legend><div class="wpcunter-settings-content">';
		foreach ( wpcunter_fields() as $key=>$field ) {
			if ( $field[3] !== 'general' ) { continue; }
			if ($key==='color_scheme') {
				echo '</div></fieldset>';
				echo '<fieldset class="wpcunter-settings-block"><legend>Appearance</legend><div class="wpcunter-settings-content">';
			}
			if ($key==='el_class') { echo '</div></fieldset>'; }
			if ($key==='label') { echo '</div></fieldset><fieldset class="wpcunter-settings-block"><legend>Text</legend><div class="wpcunter-settings-content">'; }
			if ($key==='counter_animation') { echo '</div><div class="wpcunter-column wpcunter-column-right"><fieldset class="wpcunter-settings-block"><legend>Animations</legend><div class="wpcunter-settings-content">'; }
			if ($key==='start_value') { echo '<div class="wpcunter-value-fields">'; }
			if ($key==='font_size') { echo '<div class="wpcunter-type-fields">'; }
			if ($key==='label_font_size') { echo '<div class="wpcunter-text-fields">'; }
			if ($key==='ring_size') { echo '<div class="wpcunter-ring-fields">'; }
			if ($key==='marker_size') { echo '<div class="wpcunter-marker-fields">'; }
			if ($key==='counter_animation') { echo '<div class="wpcunter-animation-fields">'; }
			if ($field[1]==='checkbox') {
				echo '<div class="wpcunter-field" data-field="' . esc_attr($key) . '"><input type="hidden" name="wpcunter_settings[' . esc_attr($key) . ']" value="0"><label class="wpcunter-checkbox"><input type="checkbox" id="wpcunter-' . esc_attr($key) . '" name="wpcunter_settings[' . esc_attr($key) . ']" value="1"' . checked($values[$key], '1', false) . '> ' . esc_html($field[0]) . '</label></div>';
				if ($key==='slow_down') { echo '</div>'; }
				continue;
			}
			if (in_array($key,wpcunter_responsive_keys(),true)) {
				wpcunter_responsive_field($key,$field,$values);
				if (in_array($key,array('font_weight','label_weight'),true)) { echo '</div>'; }
				continue;
			}
			echo '<div class="wpcunter-field" data-field="' . esc_attr($key) . '"><label for="wpcunter-' . esc_attr($key) . '">' . esc_html($field[0]) . '</label>';
			$attrs = ' id="wpcunter-' . esc_attr($key) . '" name="wpcunter_settings[' . esc_attr($key) . ']"';
			if ($key==='duration') { $attrs .= ' min="100" max="60000"'; }
			if ($key==='ring_size') { $attrs .= ' min="80" max="1000"'; }
			if ($key==='ring_thickness') { $attrs .= ' min="1" max="20"'; }
			if ( is_array($field[1]) ) {
				echo '<select' . $attrs . '>';
				foreach ( $field[1] as $value=>$name ) { echo '<option value="' . esc_attr($value) . '"' . selected($values[$key],$value,false) . '>' . esc_html($name) . '</option>'; }
				echo '</select>';
			} else {
				echo '<input type="' . esc_attr($field[1]) . '"' . $attrs . ' value="' . esc_attr($values[$key]) . '"' . ($field[1]==='number'?' step="1"':'') . '>';
			}
			if ($key==='suffix') { echo '<p class="description">Shown after the number, for example: units, m, +, %.</p>'; }
			if ($key==='counter_animation') { echo '<p class="description" data-effect-description>Choose a visual effect to accompany the number.</p>'; }
			if ($key==='duration') { echo '<p class="description">1000 ms = 1 second. Sets the total time for the number and effect.</p>'; }
			if ($key==='color') { echo '<p class="description">Applies to the number, suffix and label. Effect colors are set under Animations.</p>'; }
			echo '</div>';
			if ($key==='suffix') { echo '</div>'; }
			if ($key==='ring_color') { echo '</div>'; }
			if ($key==='marker_color') { echo '</div>'; }
		}
	echo '</div></fieldset></div></div></div></div>';
}

add_action( 'admin_enqueue_scripts', function() {
	$screen = get_current_screen();
	if ( !$screen || $screen->post_type !== 'wpcunter' ) { return; }
	wp_enqueue_style('wpcunter-admin', plugins_url('../assets/admin.css', __FILE__), array('dashicons'), wpcunter_asset_version('assets/admin.css'));
	wp_enqueue_script('wpcunter-admin', plugins_url('../assets/admin.js', __FILE__), array(), wpcunter_asset_version('assets/admin.js'), true);
} );

add_action( 'save_post_wpcunter', function( $id ) {
	if ( !isset($_POST['wpcunter_nonce']) || !is_string($_POST['wpcunter_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpcunter_nonce'])), 'wpcunter_save') || !current_user_can('edit_post', $id) || wp_is_post_revision($id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ) { return; }
	$input = isset($_POST['wpcunter_settings']) && is_array($_POST['wpcunter_settings']) ? wp_unslash($_POST['wpcunter_settings']) : array();
	// Hidden legacy design settings must not reset when the remaining fields are saved.
	$previous = get_post_meta($id, '_wpcunter_settings', true);
	if (is_array($previous)) {
		foreach ($previous as $key=>$value) {
			if (preg_match('/^(padding|margin|radius)(_|$)|^background$/', $key)) { $input[$key]=$value; }
		}
	}
	update_post_meta($id, '_wpcunter_settings', wpcunter_sanitize_settings($input));
} );

function wpcunter_render_saved( $id ) {
	if ($id <= 0) { return ''; }
	$post = get_post($id);
	if (!$post || $post->post_type!=='wpcunter' || $post->post_status!=='publish') { return ''; }
	$values = wpcunter_values($id);
	return wpcunter_render_counter($values);
}

add_action( 'vc_before_init', function() {
	if (!function_exists('vc_lean_map')) { return; }
	vc_lean_map('wpcunter_saved', function() {
		$options = array('— Select counter —'=>'');
		foreach ( get_posts(array('post_type'=>'wpcunter','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC')) as $post ) { $options[($post->post_title ?: 'Untitled') . ' (#' . $post->ID . ')'] = (string)$post->ID; }
		return array('name'=>'Animated Counter', 'base'=>'wpcunter_saved', 'category'=>function_exists('woodmart_get_tab_title_category_for_wpb')?woodmart_get_tab_title_category_for_wpb(esc_html__('Theme elements','woodmart')):'Content', 'icon'=>plugins_url('assets/images/counter-icon.svg', dirname(__DIR__) . '/wp-cunter.php'), 'description'=>'Saved counter from Animated Counter', 'params'=>array(array('type'=>'dropdown','heading'=>'Counter','param_name'=>'id','value'=>$options,'admin_label'=>true,'description'=>'Create and edit counters under Animated Counter.')));
	});
}, 40 );
add_shortcode('wpcunter_saved', function($atts) { return wpcunter_render_saved(is_array($atts) && isset($atts['id']) && is_scalar($atts['id'])?absint($atts['id']):0); });

add_filter('manage_wpcunter_posts_columns', function($columns) { $columns['wpcunter_shortcode']='Shortcode'; return $columns; });
add_action('manage_wpcunter_posts_custom_column', function($column,$id) { if ($column==='wpcunter_shortcode') { echo '<code>' . esc_html('[wpcunter_counter id="' . $id . '"]') . '</code>'; } },10,2);
