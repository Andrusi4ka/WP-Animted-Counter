<?php
defined('ABSPATH') || exit;

/** Translate legacy presets on read; the next save stores only pixel sizes. */
function wpcunter_normalize_sizes($input) {
	if (!isset($input['size']) && !isset($input['label_size'])) { return $input; }
	$presets=array('default'=>36,'small'=>28,'large'=>44,'extra-large'=>54);
	$mode=$input['size']??'default';
	$label_mode=$input['label_size']??'default';
	$number=$input['font_size']??36;
	$label=$input['label_font_size']??17;
	foreach (array('','_tablet','_mobile') as $suffix) {
		if ($suffix!=='') {
			foreach (array('size'=>'mode','label_size'=>'label_mode','font_size'=>'number','label_font_size'=>'label') as $key=>$variable) {
				if (isset($input[$key.$suffix]) && $input[$key.$suffix]!=='') { ${$variable}=$input[$key.$suffix]; }
			}
		}
		$actual=$mode==='custom'?max(1,(float)$number):($presets[$mode]??36);
		$input['font_size'.$suffix]=(string)$actual;
		$input['label_font_size'.$suffix]=(string)($label_mode==='custom'?max(1,(float)$label):round($actual*.47,2));
	}
	return $input;
}

/** Resolve empty tablet/mobile overrides in order, preserving explicit zeroes. */
function wpcunter_device_values($values,$device) {
	foreach (wpcunter_responsive_keys() as $key) {
		foreach (array('tablet','mobile') as $step) {
			if ($device==='desktop') { break; }
			$override=$values[$key . '_' . $step];
			if ($override!=='') { $values[$key]=$override; }
			if ($device===$step) { break; }
		}
	}
	return $values;
}

function wpcunter_responsive_style($values) {
	$style='';
	foreach (array('desktop','tablet','mobile') as $device) {
		$v=wpcunter_device_values($values,$device);
		$number=max(1,(float)$v['font_size']);
		$properties=array(
			'number-size'=>$number . 'px',
			'label-size'=>max(1,(float)$v['label_font_size']) . 'px',
			'number-weight'=>$v['font_weight'],
			'label-weight'=>$v['label_weight']!==''?$v['label_weight']:'inherit',
			'padding'=>max(0,(int)$v['padding']) . 'px',
			'margin'=>(int)$v['margin'] . 'px',
			'radius'=>max(0,(int)$v['radius']) . 'px',
		);
		foreach ($properties as $key=>$value) { $style.='--wpcunter-' . $key . '-' . $device . ':' . $value . ';'; }
	}
	return $style;
}
