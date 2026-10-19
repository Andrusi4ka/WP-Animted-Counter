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
		'align' => array( 'Alignment', array( 'left'=>'Left', 'center'=>'Center', 'right'=>'Right' ), 'center', 'general' ),
		'animation_position' => array( 'Animation position', array( 'left'=>'Left', 'top'=>'Top', 'right'=>'Right' ), 'top', 'general' ),
		'vertical_align' => array( 'Vertical alignment', array( 'top'=>'Top', 'middle'=>'Middle', 'bottom'=>'Bottom' ), 'middle', 'general' ),
		'color_scheme' => array( 'Color scheme', array( ''=>'Inherited', 'light'=>'Light', 'dark'=>'Dark', 'custom'=>'Custom' ), '', 'general' ),
		'color' => array( 'Custom color', 'color', '#333333', 'general' ),
		'el_class' => array( 'Extra CSS classes', 'text', '', 'general' ),
		'padding' => array( 'Padding, px', 'number', '0', 'design' ),
		'margin' => array( 'Margin, px', 'number', '0', 'design' ),
		'background' => array( 'Background color', 'text', '', 'design' ),
		'radius' => array( 'Border radius, px', 'number', '0', 'design' ),
		'counter_animation' => array( 'Animation', array( 'none'=>'No additional animation', 'placeholder_1'=>'Animation 1 (placeholder)', 'placeholder_2'=>'Animation 2 (placeholder)', 'placeholder_3'=>'Animation 3 (placeholder)' ), 'none', 'animations' ),
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
	return array('font_size','font_weight','label_font_size','label_weight','padding','margin','radius');
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
	add_meta_box( 'wpcunter-editor', 'Animated Counter — Settings', 'wpcunter_editor', 'wpcunter', 'normal', 'high' );
	add_meta_box( 'wpcunter-shortcode', 'Embed counter', function( $post ) {
		echo '<p>Publish the counter and insert this shortcode:</p><input class="widefat" readonly aria-label="Shortcode" value="' . esc_attr( '[wpcunter_counter id="' . $post->ID . '"]' ) . '"><p>Or select it in the <strong>Animated Counter</strong> element under WPBakery → Content (Theme elements with Woodmart).</p>';
	}, 'wpcunter', 'side' );
} );

function wpcunter_editor( $post ) {
	wp_nonce_field( 'wpcunter_save', 'wpcunter_nonce' );
	$values = wpcunter_values( $post->ID );
	$tabs = array( 'general'=>'General', 'design'=>'Design options', 'animations'=>'Animations' );
	echo '<div class="wpcunter-editor"><div class="wpcunter-tabs" role="tablist" aria-label="Counter settings">';
	foreach ( $tabs as $tab=>$label ) {
		echo '<button type="button" role="tab" id="wpcunter-tab-' . esc_attr($tab) . '" aria-controls="wpcunter-panel-' . esc_attr($tab) . '" aria-selected="' . ($tab==='general'?'true':'false') . '" data-tab="' . esc_attr($tab) . '">' . esc_html($label) . '</button>';
	}
	echo '</div>';
	foreach ( $tabs as $tab=>$label ) {
		echo '<div class="wpcunter-panel" role="tabpanel" id="wpcunter-panel-' . esc_attr($tab) . '" aria-labelledby="wpcunter-tab-' . esc_attr($tab) . '"' . ($tab==='general'?'':' hidden') . '><div class="wpcunter-grid">';
		if ($tab==='general') { echo '<div class="wpcunter-column wpcunter-column-left"><fieldset class="wpcunter-settings-block"><legend>Numbers</legend><div class="wpcunter-settings-content">'; }
		foreach ( wpcunter_fields() as $key=>$field ) {
			if ( $field[3] !== $tab || in_array($key, array('align','animation_position','vertical_align'), true) ) { continue; }
			if ($tab==='general' && $key==='color_scheme') {
				echo '</div></fieldset></div><div class="wpcunter-column wpcunter-column-right">';
				wpcunter_layout_editor($values);
				echo '<fieldset class="wpcunter-settings-block"><legend>Appearance</legend><div class="wpcunter-settings-content">';
			}
			if ($tab==='general' && $key==='el_class') { echo '</div></fieldset>'; }
			if ($tab==='general' && $key==='label') { echo '</div></fieldset><fieldset class="wpcunter-settings-block"><legend>Text</legend><div class="wpcunter-settings-content">'; }
			if ($key==='start_value') { echo '<div class="wpcunter-value-fields">'; }
			if ($key==='font_size') { echo '<div class="wpcunter-type-fields">'; }
			if ($key==='label_font_size') { echo '<div class="wpcunter-text-fields">'; }
			if (in_array($key,wpcunter_responsive_keys(),true)) {
				wpcunter_responsive_field($key,$field,$values);
				if (in_array($key,array('font_weight','label_weight'),true)) { echo '</div>'; }
				continue;
			}
			echo '<div class="wpcunter-field" data-field="' . esc_attr($key) . '"><label for="wpcunter-' . esc_attr($key) . '">' . esc_html($field[0]) . '</label>';
			$attrs = ' id="wpcunter-' . esc_attr($key) . '" name="wpcunter_settings[' . esc_attr($key) . ']"';
			if ( is_array($field[1]) ) {
				echo '<select' . $attrs . '>';
				foreach ( $field[1] as $value=>$name ) { echo '<option value="' . esc_attr($value) . '"' . selected($values[$key],$value,false) . '>' . esc_html($name) . '</option>'; }
				echo '</select>';
			} else {
				echo '<input type="' . esc_attr($field[1]) . '"' . $attrs . ' value="' . esc_attr($values[$key]) . '"' . ($field[1]==='number'?' step="1"':'') . '>';
			}
			if ($key==='background') { echo '<p class="description">HEX color, for example #f5f5f5. Leave empty for a transparent background.</p>'; }
			if ($key==='suffix') { echo '<p class="description">Shown after the number, for example: units, m, +, %.</p>'; }
			echo '</div>';
			if ($key==='suffix') { echo '</div>'; }
		}
		if ($tab==='general') { echo '</div>'; }
		echo '</div>';
		if ( $tab==='animations' ) { echo '<p class="description">Placeholders save your selection without adding effects. The standard counting animation remains active.</p>'; }
		echo '</div>';
	}
	echo '</div>';
}

function wpcunter_layout_editor($values) {
	$groups = array('align'=>array('Text alignment','text','jpg'), 'animation_position'=>array('Animation position','animation','png'), 'vertical_align'=>array('Vertical alignment','vertical','png'));
	$fields = wpcunter_fields();
	echo '<fieldset class="wpcunter-layout"><legend>Layout</legend><div class="wpcunter-layout-grid">';
	foreach ($groups as $key=>$group) {
		echo '<fieldset class="wpcunter-layout-group" data-layout="' . esc_attr($key) . '"><legend>' . esc_html($group[0]) . '</legend><div class="wpcunter-image-options">';
		foreach ($fields[$key][1] as $value=>$label) {
			$url = plugins_url('../assets/images/layout/' . $group[1] . '-' . $value . '.' . $group[2], __FILE__);
			echo '<label class="wpcunter-image-option" title="' . esc_attr($label) . '"><input type="radio" name="wpcunter_settings[' . esc_attr($key) . ']" value="' . esc_attr($value) . '"' . checked($values[$key],$value,false) . '><span><img src="' . esc_url($url) . '" alt=""><span class="screen-reader-text">' . esc_html($label) . '</span></span></label>';
		}
		echo '</div></fieldset>';
	}
	echo '</div><p class="description">The image represents the animation, the heading represents the number, and the text represents the label.</p></fieldset>';
}

add_action( 'admin_enqueue_scripts', function() {
	$screen = get_current_screen();
	if ( !$screen || $screen->post_type !== 'wpcunter' ) { return; }
	wp_enqueue_style('wpcunter-admin', plugins_url('../assets/admin.css', __FILE__), array('dashicons'), WPCUNTER_VERSION);
	wp_enqueue_script('wpcunter-admin', plugins_url('../assets/admin.js', __FILE__), array(), WPCUNTER_VERSION, true);
} );

add_action( 'save_post_wpcunter', function( $id ) {
	if ( !isset($_POST['wpcunter_nonce']) || !is_string($_POST['wpcunter_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpcunter_nonce'])), 'wpcunter_save') || !current_user_can('edit_post', $id) || wp_is_post_revision($id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ) { return; }
	$input = isset($_POST['wpcunter_settings']) && is_array($_POST['wpcunter_settings']) ? wp_unslash($_POST['wpcunter_settings']) : array();
	update_post_meta($id, '_wpcunter_settings', wpcunter_sanitize_settings($input));
} );

function wpcunter_render_saved( $id ) {
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
		return array('name'=>'Animated Counter', 'base'=>'wpcunter_saved', 'category'=>function_exists('woodmart_get_tab_title_category_for_wpb')?woodmart_get_tab_title_category_for_wpb(esc_html__('Theme elements','woodmart')):'Content', 'icon'=>'icon-wpb-vc-animate', 'description'=>'Saved counter from Animated Counter', 'params'=>array(array('type'=>'dropdown','heading'=>'Counter','param_name'=>'id','value'=>$options,'admin_label'=>true,'description'=>'Create and edit counters under Animated Counter.')));
	});
}, 40 );
add_shortcode('wpcunter_saved', function($atts) { return wpcunter_render_saved(is_array($atts) && isset($atts['id']) && is_scalar($atts['id'])?absint($atts['id']):0); });

add_filter('manage_wpcunter_posts_columns', function($columns) { $columns['wpcunter_shortcode']='Shortcode'; return $columns; });
add_action('manage_wpcunter_posts_custom_column', function($column,$id) { if ($column==='wpcunter_shortcode') { echo '<code>' . esc_html('[wpcunter_counter id="' . $id . '"]') . '</code>'; } },10,2);
