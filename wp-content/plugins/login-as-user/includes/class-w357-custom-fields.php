<?php
/* ======================================================
 # Login as User for WordPress - v1.6.7 (free version)
 # -------------------------------------------------------
 # Author: Web357
 # Copyright © 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, http://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com/login-as-user-wordpress-plugin
 # Demo: https://login-as-user-wordpress-demo.web357.com/wp-admin/
 # Support: https://www.web357.com/support
 # Last modified: Thursday 04 December 2025, 09:03:58 PM
 ========================================================= */
/**
 * Define the internationalization functionality
 */
class LoginAsUser_fields {

	function textField($args) 
	{ 
		$options = get_option('login_as_user_options');
		$class = (isset($args['_class'])) ? $args['_class'] : '';
		$placeholder = (isset($args['placeholder'])) ? $args['placeholder'] : '';
		$size = (isset($args['size'])) ? $args['size'] : 10;
		$maxlength = (isset($args['maxlength'])) ? $args['maxlength'] : 50;
		$default_value = (isset($args['default_value'])) ? $args['default_value'] : '';
		$desc = (isset($args['desc'])) ? $args['desc'] : '';
		$prefix = (isset($args['prefix'])) ? $args['prefix'] : '';
		?>
		<fieldset><?php echo (!empty($prefix) ? $prefix : ''); ?>
		<input 
			type='text' 
			name='login_as_user_options[<?php echo esc_attr($args['name']); ?>]' 
			id='<?php echo esc_attr($args['label-for']); ?>' 
			class='<?php echo esc_attr($class); ?>' 
			placeholder='<?php echo esc_html__($placeholder); ?>'
			value='<?php echo esc_attr(isset($options[$args['name']]) ? $options[$args['name']] : $default_value); ?>'
			size='<?php echo absint($size); ?>'
			maxlength='<?php echo absint($maxlength); ?>'
			>
		</fieldset>
		<?php if (!empty($desc)): ?>
        <p class="description">
			<?php echo wp_kses( __( $desc, 'login-as-user' ), array( 'strong' => array(), 'br' => array() ) ); ?>
		</p>
		<?php endif; ?>
		<?php
	}
	

	function imageField($args) 
	{ 
		$options = get_option( 'login_as_user_options' );
		$name = $args['id'];
		$width = $args['width'];
		$height = $args['height'];
		$img_id = $args['img_id'];
		$default_image = '';

		// Set variables
		if ( !empty( $options[$name] ) ) {
			$image_attributes = wp_get_attachment_image_src( $options[$name], array( $width, $height ) );
			$src = $image_attributes[0];
			$value = $options[$name];
		} else {
			$src = $default_image;
			$value = '';
		}
		?>

		<div class="w357-imageField">

			<?php if (!empty($src)): ?>
					<img data-src="<?php echo esc_url($default_image); ?>" src="<?php echo esc_url($src); ?>" width="<?php echo absint($width); ?>px" height="<?php echo absint($height); ?>px" />		
			<?php else: ?>
				<img data-src="<?php echo esc_url($default_image); ?>" src="<?php echo esc_url($src); ?>" width="<?php echo absint($width); ?>px" height="<?php echo absint($height); ?>px" style="display:none" />		
			<?php endif; ?>

			<div>
				<input type="hidden" name="login_as_user_options[<?php echo $name; ?>]" id="login_as_user_options[<?php echo $name; ?>]" value="<?php echo esc_attr($value); ?>" />
				<button type="submit" class="upload_image_button button">Upload image</button>

				<?php if (!empty($src)): ?>
					<button type="submit" class="remove_image_button button">&times;</button>
				<?php else: ?>
					<button type="submit" class="remove_image_button button" style="display:none">&times;</button>
				<?php endif; ?>

			</div>
		</div>
		
		<?php
	}

	function hiddenField($args) 
	{ 
		$options = get_option('login_as_user_options');
		$default_value = (isset($args['default_value'])) ? $args['default_value'] : '';
		?>
		<input 
			type='hidden' 
			name='login_as_user_options[<?php echo esc_attr($args['name']); ?>]' 
			value='<?php echo esc_attr(isset($options[$args['name']]) ? $options[$args['name']] : $default_value); ?>'
			>
		<?php
	}

	function textareaWordpressEditorField($args) 
	{ 
		$options = get_option('login_as_user_options');
	    $editor_id = $args['name']; 
		$class = (isset($args['_class'])) ? $args['_class'] : '';
		$editor_settings = array('textarea_name' => 'login_as_user_options['.$args['name'].']', 'editor_class' => $class);
		$default_value = (isset($args['default_value'])) ? $args['default_value'] : '';
		$content = (isset($options[$args['name']])) ? $options[$args['name']] : $default_value;
		wp_editor( $content, $editor_id, $editor_settings );
	}

	function textareaField($args) 
	{ 
		$options = get_option('login_as_user_options');
		$class = (isset($args['_class'])) ? $args['_class'] : '';
		$default_value = (isset($args['default_value'])) ? $args['default_value'] : '';
		?>
		
		<textarea 
			id="<?php echo esc_attr($args['name']); ?>" 
			name="login_as_user_options[<?php echo esc_attr($args['name']); ?>]" 
			rows="<?php echo absint($args['rows']); ?>" 
			cols="<?php echo absint($args['cols']); ?>" 
			class="<?php echo esc_attr($class); ?>"
			placeholder="<?php echo esc_html__($args['placeholder']); ?>"><?php echo esc_textarea(isset($options[$args['name']]) && !empty($options[$args['name']]) ? $options[$args['name']] : $default_value); ?></textarea>

		<?php if (!empty($args['desc'])): ?>
			<p class="description"><?php echo wp_kses($args['desc'], array('strong' => array(), 'br' => array(), 'code' => array())); ?></p>
		<?php endif; ?>
		<?php
	}
	

	function selectField($args)
	{ 
		$name = $args['id'];
		$default_value = $args['default_value'];
		$select_options = $args['options'];
		$options = get_option('login_as_user_options');
		$desc = (isset($args['desc'])) ? $args['desc'] : '';
		?>
		<select name="login_as_user_options[<?php echo $name; ?>]">

		<?php for ($i=0;$i<count($select_options);$i++): ?>

			<option value="<?php echo esc_attr($select_options[$i]['value']); ?>" <?php echo (($select_options[$i]['value'] == (isset($options[$name]) ? $options[$name] : $default_value) ) ? 'selected' : ''); ?>><?php echo $select_options[$i]['label']; ?></option>

		<?php endfor; ?>
		</select>
		<?php if (!empty($desc)): ?>
        <p class="description">
			<?php echo wp_kses( __( $desc, 'login-as-user' ), array( 'strong' => array(), 'br' => array() ) ); ?>
		</p>
		<?php endif; ?>
		<?php
	}

	function radioField($args)
	{ 
		$name = $args['id'];
		$default_value = $args['default_value'];
		$radio_options = $args['options'];
		$field_description = (isset($args['field_description'])) ? $args['field_description'] : '';
		$options = get_option('login_as_user_options');

		for ($i=0;$i<count($radio_options);$i++): ?>

			<input 
				type='radio' 
				id='<?php echo $radio_options[$i]['id']; ?>' 
				name='login_as_user_options[<?php echo $name; ?>]' 
				value='<?php echo esc_attr($radio_options[$i]['value']); ?>'
				<?php if ( $radio_options[$i]['value'] == (isset($options[$name]) ? $options[$name] : $default_value) ) echo 'checked="checked"'; ?>
			>
			<label for="<?php echo $radio_options[$i]['id']; ?>" style="margin-right: 10px;"><?php echo $radio_options[$i]['label']; ?></label>

		<?php endfor; ?>

		<?php if (!empty($field_description)): ?>
			<div class="w357_settings_field_description"><?php echo $field_description; ?></div>
		<?php endif; ?>
		<?php
	}

	function checkboxField($args)
	{
		$name = $args['id'];
		$default_value = $args['default_value'];
		$ckeckbox_options = $args['options'];
		$field_description = (isset($args['field_description'])) ? $args['field_description'] : '';
		$options = get_option('login_as_user_options');

		for ($i=0;$i<count($ckeckbox_options);$i++):
		?>

			<input 
				type='checkbox' 
				id='<?php echo $ckeckbox_options[$i]['id']; ?>' 
				name='login_as_user_options[<?php echo $name; ?>][]' 
				value='<?php echo esc_attr($ckeckbox_options[$i]['value']); ?>'
				<?php if (in_array($ckeckbox_options[$i]['value'], (isset($options[$name]) ? $options[$name] : $default_value))) echo 'checked="checked"'; ?>
			>
			<label for="<?php echo $ckeckbox_options[$i]['id']; ?>" style="margin-right: 10px;"><?php echo $ckeckbox_options[$i]['label']; ?></label>

		<?php endfor; ?>

		<?php if (!empty($field_description)): ?>
			<div class="w357_settings_field_description"><?php echo $field_description; ?></div>
		<?php endif; ?>
		<?php
	}

    function numberField($args)
    {
        $name = $args['id'];
        $options = get_option('login_as_user_options');
        $desc = isset($args['desc']) ? $args['desc'] : '';
        $current_value = isset($options[$name]) ? $options[$name] : '';
        $last_column = $current_value === 'last';
        ?>
        <div class="w357-number-field-container">
            <input
                    type="number"
                    name="login_as_user_options[<?= esc_attr($name); ?>]"
                    id="<?= esc_attr($name); ?>"
                    value="<?= $last_column ? '' : esc_attr($current_value); ?>"
                    min="1"
                    step="1"
                    class="small-text"
                <?php echo $last_column ? 'disabled' : ''; ?>
            >
            <label style="margin-left: 10px;">
                <input
                        type="checkbox"
                        name="login_as_user_options[<?php echo esc_attr($name . '_last_column'); ?>]"
                        id="<?php echo esc_attr($name . '_last_column'); ?>"
                        value="1"
                    <?php checked($last_column, true); ?>
                        onchange="document.getElementById('<?php echo esc_attr($name); ?>').disabled = this.checked;"
                >
                <?php esc_html_e('Last column', 'login-as-user'); ?>
            </label>
        </div>
        <?php if (!empty($desc)): ?>
        <p class="description"><?php echo wp_kses($desc, ['strong' => [], 'br' => []]); ?></p>
    <?php endif; ?>
        <?php
    }

    function roleCapabilityCheckboxField($args)
    {
        $name = $args['id'];
        $options = get_option('login_as_user_options');
        $desc = isset($args['desc']) ? $args['desc'] : '';
        $capability = isset($args['capability']) ? $args['capability'] : 'edit_users';
        
        // Get all WordPress roles
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters('editable_roles', $all_roles);
        
        // Get current saved values (fallback)
        $saved_roles = isset($options[$name]) ? $options[$name] : array();
        
        ?>
        <div class="w357-role-capability-checkboxes">
            <?php foreach ($editable_roles as $role_key => $role_data): ?>
                <?php 
                // Use saved values from database as the source of truth for checkbox state
                $is_checked = in_array($role_key, $saved_roles);
                
                // Administrator is always enabled regardless of saved settings
                if ($role_key === 'administrator') {
                    $is_checked = true;
                }
                $checkbox_id = $name . '_' . $role_key;
                ?>
                <div class="role-checkbox-item" style="display: flex; align-items: center; margin-bottom: 10px;">
                    <input 
                        type="checkbox" 
                        id="<?php echo esc_attr($checkbox_id); ?>" 
                        name="login_as_user_options[<?php echo esc_attr($name); ?>][]" 
                        value="<?php echo esc_attr($role_key); ?>"
                        <?php checked($is_checked, true); ?>
                        <?php if ($role_key === 'administrator'): ?>disabled<?php endif; ?>
                        style="margin-right: 8px;"
                    >
                    <label for="<?php echo esc_attr($checkbox_id); ?>" style="margin: 0; font-weight: normal; <?php if ($role_key === 'administrator'): ?>color: #666;<?php endif; ?>">
                        <?php echo esc_html($role_data['name']); ?>
                        <?php if ($role_key === 'administrator'): ?>
                            <em>(<?php echo esc_html__('always enabled', 'login-as-user'); ?>)</em>
                        <?php endif; ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($desc)): ?>
            <p class="description"><?php echo wp_kses($desc, ['strong' => [], 'br' => [], 'code' => [], 'em' => []]); ?></p>
        <?php endif; ?>
        <?php
    }
}