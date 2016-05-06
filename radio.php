<?php
/*
Plugin Name: Contact Form 7 Custom Radio Button Hook
Description: Adds an extra element for making a custom CSS-only Radio Button
Author: goganlov3
Author URI: http://lockdev.io
Version: 0.666
*/

/*  Copyright 2016  Logan Guthrie  (email : logan@lockdev.io)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/* Also based a lot (99%) of this code off of the excellent contact-form-7-radio plugin combined with the checkbox.php file for contact-form-7.

Good coders borrow, coders with little time steal

Please check it out the radio plugin and download here: http://www.nocean.ca/plugins/radio-module-for-contact-form-7-wordpress-plugin/
*/

/**
 * 
 * Check if CF7 is installed and activated.
 * 		Deliver a message to install CF7 if not.
 * 
 */
add_action( 'admin_init', 'wpcf7_radio_has_parent_plugin' );
function wpcf7_radio_has_parent_plugin() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
        add_action( 'admin_notices', 'wpcf7_radio_nocf7_notice' );

        deactivate_plugins( plugin_basename( __FILE__ ) ); 

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

function wpcf7_radio_nocf7_notice() { ?>
    <div class="error">
    	<p>
    		<?php printf(
				__('%s must be installed and activated for this plugin to work', 'contact-form-7-custom-radio'),
				'<a href="'.admin_url('plugin-install.php?tab=search&s=contact+form+7').'">Contact Form 7</a>'
			); ?>
		</p>
    </div>
    <?php
}

/**
 *
 * Initialize the shortcode
 * 		This lets CF7 know about the radio extension.
 * 
 */
// add_action('wpcf7_init', 'wpcf7_add_shortcode_radio', 10);
// function wpcf7_add_shortcode_() {
// 	wpcf7_add_shortcode( 'radio', 'wpcf7_radio_shortcode_handler', true );
// }

/* Shortcode handler */


// initialize
add_action('wpcf7_init', 'wpcf7_add_shortcode_radio_custom', 10);
function wpcf7_add_shortcode_radio_custom() {
	wpcf7_add_shortcode( 'radiocustom', 'wpcf7_radio_custom_shortcode_handler', true );
}

// handler
function wpcf7_radio_custom_shortcode_handler( $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->name );

	if ( $validation_error )
		$class .= ' wpcf7-not-valid';

	$label_first = $tag->has_option( 'label_first' );
	$use_label_element = $tag->has_option( 'use_label_element' );
	$extra_span_element = $tag->has_option( 'extra_span_element' );
	//$exclusive = $tag->has_option( 'exclusive' );
	// $free_text = $tag->has_option( 'checkbox' );
	$multiple = false;
	$exclusive = false;

	//if ( 'checkbox' == $tag->basetype )
	//	$multiple = ! $exclusive;
	//else // radio
	//$exclusive = false;

	//if ( $exclusive )
	//	$class .= ' wpcf7-exclusive-checkbox';

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();

	$tabindex = $tag->get_option( 'tabindex', 'int', true );

	if ( false !== $tabindex )
		$tabindex = absint( $tabindex );

	$html = '';
	$count = 0;

	$values = (array) $tag->values;
	$labels = (array) $tag->labels;

	if ( $data = (array) $tag->get_data_option() ) {
		if ( $free_text ) {
			$values = array_merge(
				array_slice( $values, 0, -1 ),
				array_values( $data ),
				array_slice( $values, -1 ) );
			$labels = array_merge(
				array_slice( $labels, 0, -1 ),
				array_values( $data ),
				array_slice( $labels, -1 ) );
		} else {
			$values = array_merge( $values, array_values( $data ) );
			$labels = array_merge( $labels, array_values( $data ) );
		}
	}

	$defaults = array();

	$default_choice = $tag->get_default_option( null, 'multiple=1' );

	foreach ( $default_choice as $value ) {
		$key = array_search( $value, $values, true );

		if ( false !== $key ) {
			$defaults[] = (int) $key + 1;
		}
	}

	if ( $matches = $tag->get_first_match_option( '/^default:([0-9_]+)$/' ) ) {
		$defaults = array_merge( $defaults, explode( '_', $matches[1] ) );
	}

	$defaults = array_unique( $defaults );

	$hangover = wpcf7_get_hangover( $tag->name, $multiple ? array() : '' );

	foreach ( $values as $key => $value ) {

		$class = 'wpcf7-list-item';

		$checked = false;

		if ( $hangover ) {
			if ( $multiple ) {
				$checked = in_array( esc_sql( $value ), (array) $hangover );
			} else {
				$checked = ( $hangover == esc_sql( $value ) );
			}
		} else {
			$checked = in_array( $key + 1, (array) $defaults );
		}

		if ( isset( $labels[$key] ) )
			$label = $labels[$key];
		else
			$label = $value;

		$item_atts = array(
			'type' => 'radio',
			'name' => $tag->name . ( $multiple ? '[]' : '' ),
			'value' => $value,
			'checked' => $checked ? 'checked' : '',
			'tabindex' => $tabindex ? $tabindex : '' );

		$item_atts = wpcf7_format_atts( $item_atts );

		if ( $label_first ) { // put label first, input last
			$item = sprintf(
				'<span class="wpcf7-list-item-label">%1$s</span>&nbsp;<input %2$s />',
				esc_html( $label ), $item_atts );
		} else {
			$item = sprintf(
				'<input %2$s />&nbsp;<span class="wpcf7-list-item-label">%1$s</span>',
				esc_html( $label ), $item_atts );
		}

		if ( $use_label_element && $extra_span_element ) {
			$item = '<label>' . $item . '<span class="contact-pseudo"></span></label>'; 
		} elseif ( $use_label_element ) {
			$item = '<label>' . $item . '</label>';
		} elseif ( $extra_span_element ) {
			$item = $item . '<span class="contact-pseudo"></span>';
		}

		if ( false !== $tabindex )
			$tabindex += 1;

		$count += 1;

		if ( 1 == $count ) {
			$class .= ' first';
		}

		if ( count( $values ) == $count ) { // last round
			$class .= ' last';

			if ( $free_text ) {
				$free_text_name = sprintf(
					'_wpcf7_%1$s_free_text_%2$s', $tag->basetype, $tag->name );

				$free_text_atts = array(
					'name' => $free_text_name,
					'class' => 'wpcf7-free-text',
					'tabindex' => $tabindex ? $tabindex : '' );

				if ( wpcf7_is_posted() && isset( $_POST[$free_text_name] ) ) {
					$free_text_atts['value'] = wp_unslash(
						$_POST[$free_text_name] );
				}

				$free_text_atts = wpcf7_format_atts( $free_text_atts );

				$item .= sprintf( ' <input type="text" %s />', $free_text_atts );

				$class .= ' has-free-text';
			}
		}

		$item = '<span class="' . esc_attr( $class ) . '">' . $item . '</span>';
		$html .= $item;
	}

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><span %2$s>%3$s</span>%4$s</span>',
		sanitize_html_class( $tag->name ), $atts, $html, $validation_error );

	return $html;
}


/* Validation filter */

// add_filter( 'wpcf7_validate_checkbox', 'wpcf7_radio_validation_filter', 10, 2 );
// add_filter( 'wpcf7_validate_checkbox*', 'wpcf7_radio_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_radio', 'wpcf7_radio_validation_filter', 10, 2 );

function wpcf7_radio_validation_filter( $result, $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	$type = $tag->type;
	$name = $tag->name;

	$value = isset( $_POST[$name] ) ? (array) $_POST[$name] : array();

	if ( $tag->is_required() && empty( $value ) ) {
		$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
	}

	return $result;
}


/* Adding free text field */

add_filter( 'wpcf7_posted_data', 'wpcf7_radio_posted_data' );

function wpcf7_radio_posted_data( $posted_data ) {
	$tags = wpcf7_scan_shortcode(
		//array( 'type' => array( 'checkbox', 'checkbox*', 'radio' ) ) );
		array( 'type' => 'radiocustom' ) );

	if ( empty( $tags ) ) {
		return $posted_data;
	}

	foreach ( $tags as $tag ) {
		$tag = new WPCF7_Shortcode( $tag );

		if ( ! isset( $posted_data[$tag->name] ) ) {
			continue;
		}

		$posted_items = (array) $posted_data[$tag->name];

		if ( $tag->has_option( 'free_text' ) ) {
			if ( WPCF7_USE_PIPE ) {
				$values = $tag->pipes->collect_afters();
			} else {
				$values = $tag->values;
			}

			$last = array_pop( $values );
			$last = html_entity_decode( $last, ENT_QUOTES, 'UTF-8' );

			if ( in_array( $last, $posted_items ) ) {
				$posted_items = array_diff( $posted_items, array( $last ) );

				$free_text_name = sprintf(
					'_wpcf7_%1$s_free_text_%2$s', $tag->basetype, $tag->name );

				$free_text = $posted_data[$free_text_name];

				if ( ! empty( $free_text ) ) {
					$posted_items[] = trim( $last . ' ' . $free_text );
				} else {
					$posted_items[] = $last;
				}
			}
		}

		$posted_data[$tag->name] = $posted_items;
	}

	return $posted_data;
}



/**
 * 
 * Tag generator
 * 		Adds radio to the CF7 form editor
 * 
 */
add_action( 'wpcf7_admin_init', 'wpcf7_add_tag_generator_radio', 30 );

function wpcf7_add_tag_generator_radio() {
	if (class_exists('WPCF7_TagGenerator')) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'radiocustom', __( 'Radio Extension', 'contact-form-7' ), 'wpcf7_tg_pane_radio' );
	} else if (function_exists('wpcf7_add_tag_generator')) {
		wpcf7_add_tag_generator( 'radiocustom', __( 'Radio Custom', 'wpcf7' ), 'wpcf7-tg-pane-radio', 'wpcf7-tg-pane-radio' );
	}
}

function wpcf7_tg_pane_radio($contact_form, $args = '') {
	if (class_exists('WPCF7_TagGenerator')) {
		$args = wp_parse_args( $args, array() );
		$description = __( "Creates a Radio Button selection like the original, but with an extra <span> element for adding a custom type of button using CSS", 'contact-form-7-radio' );
		$desc_link = '<a href="https://github.com/lguthrie490/wpcf7-custom-radio-buttons" target="_blank">'.__( 'CF7 Radio', 'contact-form-7-radio' ).'</a>';
		?>
		<div class="control-box">
			<fieldset>
				<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

				<table class="form-table"><tbody>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label>
						</th>
						<td>
							<input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /><br>
							<em><?php echo esc_html( __( 'Required field', 'contact-form-7-radio' ) ); ?></em>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?>
								</legend>
								<textarea name="values" class="values" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>"></textarea>
								<label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>">
									<span class="description"><?php echo esc_html( __( "One option per line.", 'contact-form-7' ) ); ?></span>
								</label>
								<br>
								<label>
									<input type="checkbox" name="label_first" class="option" />
									<?php echo esc_html( __( 'Put a label first and the radio button last', 'contact-form-7' ) ); ?>
								</label>
								<label>
									<input type="checkbox" name="use_label_element" class="option" />
									<?php echo esc_html( __( 'Wrap each item with a <label> element', 'contact-form-7' ) ); ?>
								</label>
								<label>
									<input type="checkbox" name="extra_span_element" class="option" />
									<?php echo esc_html( __( 'Adds an extra <span> element before the <input> radio appears for adding a pseudo check' ) ); ?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>">
								<?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?>
							</label>
						</th>
						<td>
							<input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" />
						</td>
					</tr>

					<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
					<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
					</tr>

				</tbody></table>
			</fieldset>
		</div>

		<div class="insert-box">
			<input type="text" name="radio-custom" class="tag code" readonly="readonly" onfocus="this.select()" />

			<div class="submitbox">
				<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
			</div>

			<br class="clear" />
		</div>
	<?php } else { ?>
		<div id="wpcf7-tg-pane-radio" class="hidden">
			<form action="">
				<table>
					<tr>
						<td>
							<?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?><br />
							<input type="text" name="name" class="tg-name oneline" /><br />
							<em><small><?php echo esc_html( __( 'For better security, change "radio" to something less bot-recognizable.', 'contact-form-7-radio' ) ); ?></small></em>
						</td>
						<td></td>
					</tr>
					
					<tr>
						<td colspan="2"><hr></td>
					</tr>

					<tr>
						<td>
							<?php echo esc_html( __( 'ID (optional)', 'contact-form-7' ) ); ?><br />
							<input type="text" name="id" class="idvalue oneline option" />
						</td>
						<td>
							<?php echo esc_html( __( 'Class (optional)', 'contact-form-7' ) ); ?><br />
							<input type="text" name="class" class="classvalue oneline option" />
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="checkbox" name="nomessage:true" id="nomessage" class="messagekillvalue option" /> <label for="nomessage"><?php echo esc_html( __( 'Don\'t Use Accessibility Message (optional)', 'contact-form-7' ) ); ?></label><br />
							<em><?php echo __('If checked, the accessibility message will not be generated. <strong>This is not recommended</strong>. If you\'re unsure, leave this unchecked.','contact-form-7-radio'); ?></em>
						</td>
					</tr>

					<tr>
						<td colspan="2"><hr></td>
					</tr>			
				</table>
				
				<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'contact-form-7-radio' ) ); ?><br /><input type="text" name="radio-custom" class="tag" readonly="readonly" onfocus="this.select()" /></div>
			</form>
		</div>
	<?php }
}