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
class LoginAsUser_settings {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * This fields
	 *
	 * @var [class]
	 */
	public $fields;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->fields = new LoginAsUser_fields();
	}

	/**
	 * Adds the option in WordPress Admin menu
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function options_page() 
	{
		add_options_page( 
			esc_html__( 'Login as User settings', 'login-as-user'),
			'Login as User',
			'manage_options', 
			'login-as-user',
			array($this, 'options_page_content') 
		);
	}

    /**
     * Adds the admin page content
     *
     * @since    1.0.0
     * @access   public
     */
    public function options_page_content()
    {
        // Get the active tab
        $active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['appearance', 'settings']) ? sanitize_text_field($_GET['tab']) : 'settings';
        $active_subtab = $active_tab === 'appearance' && isset($_GET['subtab']) && in_array($_GET['subtab'], ['login-button', 'frontend-bar', 'css']) ? sanitize_text_field($_GET['subtab']) : 'login-button';
        if ($active_subtab === 'css') {
            /* enqueue CSS editor */
            $cmsSettings['codeEditor'] = wp_enqueue_code_editor(['type' => 'text/css']);
            wp_add_inline_script('login-as-user-pro', sprintf(
                'jQuery( function( $ ) {
				$(".w357-css-editor-field-login-as-user").each( function() {
				    wp.codeEditor.initialize($(this), %s );
				});
			} );',
                wp_json_encode($cmsSettings)
            ));
            wp_enqueue_style('wp-codemirror');
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings-view.php';
    }

    /**
     * Function that will validate all fields.
     */
    public function validateSettings($fields)
    {
        $default_settings = get_option('login_as_user_options') ?: [];
        try {
            $valid_fields = [];
            $errors = [];
            
            // Handle checkbox fields that might not be present when unchecked
            // Only process checkbox fields if they are expected to be in the current form submission
            $checkbox_fields = ['preserve_wc_cart_on_switch', 'roles_with_edit_users_capability'];
            foreach ($checkbox_fields as $checkbox_field) {
                // Only set to empty array if the field is expected in this form context
                // For roles_with_edit_users_capability, only process if we have other settings tab fields
                if ($checkbox_field === 'roles_with_edit_users_capability') {
                    // Only process this field if we're on settings tab (indicated by presence of settings tab fields)
                    $settings_tab_fields = ['redirect_to', 'logout_redirect_url', 'license_key', 'role_management_assignments'];
                    $has_settings_fields = false;
                    foreach ($settings_tab_fields as $settings_field) {
                        if (isset($fields[$settings_field])) {
                            $has_settings_fields = true;
                            break;
                        }
                    }
                    if ($has_settings_fields && !isset($fields[$checkbox_field])) {
                        $fields[$checkbox_field] = [];
                    }
                } else {
                    // For other checkbox fields, handle normally
                    if (!isset($fields[$checkbox_field])) {
                        $fields[$checkbox_field] = [];
                    }
                }
            }
            
            foreach ($fields as $fieldName => $fieldValue) {
                try {
                    switch ($fieldName) {
                        case 'redirect_to':
                            $redirect_to = trim($fields['redirect_to']);
                            $redirect_to = strip_tags(stripslashes($redirect_to));
                            $redirect_to_frontend_url = esc_url_raw(home_url('/') . $redirect_to);
                            if (wp_http_validate_url($redirect_to_frontend_url) && substr($redirect_to, 0, 1) != '/' && substr($redirect_to, 0, 1) != '\\' && substr($redirect_to, 0, 8) != 'wp-admin' && substr($redirect_to, 0, 8) != 'wp-login') {
                                $valid_fields['redirect_to'] = $redirect_to;
                            } else {
                                $valid_fields['redirect_to'] = '';
                                $message = __('Error. The URL is not valid: ' . home_url('/') . $fields['redirect_to'] . '.', 'login-as-user');
                                $message .= (substr($redirect_to, 0, 1) == '/' || substr($redirect_to, 0, 1) == '\\') ? __('<br>Please remove the slash in front of URL.', 'login-as-user') : '';
                                $message .= (substr($redirect_to, 0, 8) == 'wp-admin') ? __('<br>You can\'t redirect the user to the admin page.', 'login-as-user') : '';
                                $message .= (substr($redirect_to, 0, 8) == 'wp-login') ? __('<br>You can\'t redirect the user to the login page.', 'login-as-user') : '';
                                throw new \Exception($message);
                            }
                            break;
                        case 'logout_redirect_url':
                            $logout_redirect_url = ltrim(trim($fields['logout_redirect_url']), '/');
                            if (wp_http_validate_url(home_url('/') . $logout_redirect_url)) {
                                $valid_fields['logout_redirect_url'] = $logout_redirect_url;
                            } else {
                                $valid_fields['logout_redirect_url'] = '';
                                throw new \Exception(__('Error. The URL is not valid: ' . home_url('/') . $fields['logout_redirect_url'] . '.', 'login-as-user'));
                            }
                            break;
                        case 'login_as_type':
                        case 'login_as_type_characters_limit':
                        case 'message_display_position':
                        case 'show_admin_link_in_topbar':
                        case 'enable_ping_animation':
                        case 'license_key':
                            $value = trim($fields[$fieldName]);
                            $value = strip_tags(stripslashes($value));
                            $valid_fields[$fieldName] = $value;
                            break;
                        case 'preserve_wc_cart_on_switch':
                            // Expect array with 'yes' when checked
                            $val = isset($fields[$fieldName]) ? $fields[$fieldName] : [];
                            if (!is_array($val)) { $val = []; }
                            $val = array_values(array_intersect($val, ['yes']));
                            $valid_fields[$fieldName] = $val;
                            break;
                        
                    }
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            

            if ($errors) {
                throw new \Exception(implode('<br />', $errors));
            }
            
            // Preserve roles_with_edit_users_capability if it wasn't processed (e.g., when saving from appearance tab)
            if (!array_key_exists('roles_with_edit_users_capability', $valid_fields) && isset($default_settings['roles_with_edit_users_capability'])) {
                $valid_fields['roles_with_edit_users_capability'] = $default_settings['roles_with_edit_users_capability'];
            }
            
            add_settings_error('my_option_notice', 'my_option_notice', __('Settings saved successfully!', 'login-as-user'), 'success');
            $valid_fields = array_merge($default_settings, $valid_fields);
        } catch (\Exception $e) {
            $valid_fields = $default_settings;
            add_settings_error('my_option_notice', 'my_option_notice', $e->getMessage(), 'error');
        }

        return apply_filters('validateSettings', $valid_fields, array_keys($valid_fields));
    }

	/**
	 * Initialize the settings link
	 *
	 * @access   public
	 */
	public function settings_link($links) 
	{
		$link = 'options-general.php?page=' . 'login-as-user';
		$settings_link = '<a href="'.esc_url($link).'">'.esc_html__( 'Settings', 'login-as-user' ).'</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Initialize the settings page
	 *
	 * @since    3.2.0
	 * @access   public
	 */
	public function settings_init() 
	{
		/**
		 * REGISTER SETTINGS
		 */
		register_setting( 'login-as-user', 'login_as_user_options', array($this, 'validateSettings'));

        /**
         * SECTIONS - Settings Tab
         */
        add_settings_section(
            'base_settings_section',
            esc_html__('General Settings', 'login-as-user'),
            '',
            'login-as-user-settings'
        );

		/**
		 * Define Vars
		 */

		/**
		 * FIELDS
		 */		
		// Link to redirect after login as user
		add_settings_field( 
			'redirect_to', 
			__( 'URL Redirection', 'login-as-user' ),
			array($this->fields, 'textField'),
			'login-as-user-settings', 
			'base_settings_section',
			[
				'label-for' => 'redirect_to',
				'name' => 'redirect_to',
				'class' => 'license_key',
				'default_value' => '',
				'size' => 60,
				'maxlength' => 250,
				'placeholder' => __('example: my-account/orders', 'login-as-user'),
				'desc' => __('This is the URL path that you (Admin) will be redirected after logging in as a User.<br> For example: maybe you want to be redirected in user\'s orders page to see if an order has been completed successfully or cancelled, or just editing his profile details.<br>Leave it blank if you would like to be redirected to the home page (default)', 'login-as-user'),
				'prefix' => '<span class="lac-input-prefix">'.home_url('/').'</span>'
			]
		);

        // Link to redirect after login as user
        add_settings_field(
            'logout_redirect_url',
            __('Logout Redirect URL', 'login-as-user'),
            [$this->fields, 'textField'],
            'login-as-user-settings',
            'base_settings_section',
            [
                'label-for' => 'logout_redirect_url',
                'name' => 'logout_redirect_url',
                'class' => 'logout_redirect_url',
                'default_value' => '',
                'size' => 60,
                'maxlength' => 250,
                'placeholder' => __('example: my-account/orders', 'login-as-user'),
                'desc' => __('Enter a custom URL to redirect to after logging out from a "Login as User" session. Leave blank to use the previous page.', 'login-as-user'),
                'prefix' => '<span class="lac-input-prefix">' . home_url('/') . '</span>'
            ]
        );
	
		// License Key
		 
		add_settings_field( 
			'license_key', 
			esc_html__( 'License Key', 'login-as-user' ),
			array($this->fields, 'hiddenField'),
			'login-as-user-settings', 
			'base_settings_section',
			[
				'label-for' => 'license_key',
				'name' => 'license_key',
				'class' => 'license_key hidden',
				'default_value' => '',
				'size' => 60,
				'maxlength' => 60,
				'placeholder' => __('Enter your license key from web357.com', 'login-as-user'),
				'desc' => __('In order to update commercial Web357 plugins, you have to enter the Web357 License Key.<br>You can find the License Key in your account settings at Web357.com, in the <a href="//www.web357.com/my-account/web357-license-manager" target="_blank"><strong>Web357 License Key Manager</strong></a> section.', 'login-as-user')
			]
		);
		 

		

        // Preserve WooCommerce cart on switch
        add_settings_field(
            'preserve_wc_cart_on_switch',
            esc_html__('Preserve WooCommerce cart on switch', 'login-as-user'),
            [$this->fields, 'checkboxField'],
            'login-as-user-settings',
            'base_settings_section',
            [
                'id' => 'preserve_wc_cart_on_switch',
                'default_value' => [],
                'options' => [
                    [
                        'id' => 'preserve_wc_cart_on_switch_yes',
                        'label' => esc_html__('Do not clear/forget carts during Login as User flows', 'login-as-user'),
                        'value' => 'yes'
                    ],
                ],
                'field_description' => ''
            ]
        );

        /**
         * SECTIONS - Appearance Tab
         */


        /**
         * Tab Section - Login Button
         */
        add_settings_section(
            'button_display_section',
            esc_html__('Login button', 'login-as-user'),
            '',
            'login-as-user-appearance-login-button'
        );
        
        // Login as... button
        add_settings_field(
            'login_as_type',
            esc_html__( '"Login as...«option»" button', 'login-as-user' ),
            array($this->fields, 'selectField'),
            'login-as-user-appearance-login-button',
            'button_display_section',
            [
                'id' => 'login_as_type',
                'default_value' => 'user_login',
                'options' => [
                    ['id' => '0', 'label' => esc_html__( 'Nickname (username)', 'login-as-user' ), 'value' => 'user_login'],
                    ['id' => '1', 'label' => esc_html__( 'First name', 'login-as-user' ), 'value' => 'user_firstname'],
                    ['id' => '2', 'label' => esc_html__( 'Last name', 'login-as-user' ), 'value' => 'user_lastname'],
                    ['id' => '3', 'label' => esc_html__( 'Full name (first & last)', 'login-as-user' ), 'value' => 'user_fullname'],
                    ['id' => '4', 'label' => esc_html__( 'None (display only the user icon)', 'login-as-user' ), 'value' => 'only_icon'],
                ],
                'desc' => __('Choose which string will be displayed on the "Login as User" button.<br>For example Login as «Yiannis», or Login as «Christodoulou», or Login as «Johnathan99», or Login as «Yiannis Christodoulou».', 'login-as-user'),
            ]
        );

        // Characters limit of login as name
        add_settings_field(
            'login_as_type_characters_limit',
            esc_html__( 'Show only the first X characters on the "Login as...«option»" button', 'login-as-user' ),
            array($this->fields, 'selectField'),
            'login-as-user-appearance-login-button',
            'button_display_section',
            [
                'id' => 'login_as_type_characters_limit',
                'default_value' => '0',
                'options' => [
                    ['id' => '0', 'label' => esc_html__('All characters (default)', 'login-as-user'), 'value' => '0'],
                    ['id' => '1', 'label' => '1', 'value' => '1'],
                    ['id' => '2', 'label' => '2', 'value' => '2'],
                    ['id' => '3', 'label' => '3', 'value' => '3'],
                    ['id' => '4', 'label' => '4', 'value' => '4'],
                    ['id' => '5', 'label' => '5', 'value' => '5'],
                    ['id' => '6', 'label' => '6', 'value' => '6'],
                    ['id' => '7', 'label' => '7', 'value' => '7'],
                    ['id' => '8', 'label' => '8', 'value' => '8'],
                    ['id' => '9', 'label' => '9', 'value' => '9'],
                    ['id' => '10', 'label' => '10', 'value' => '10'],
                    ['id' => '11', 'label' => '11', 'value' => '11'],
                    ['id' => '12', 'label' => '12', 'value' => '12'],
                    ['id' => '13', 'label' => '13', 'value' => '13'],
                    ['id' => '14', 'label' => '14', 'value' => '14'],
                    ['id' => '15', 'label' => '15', 'value' => '15'],
                ],
                'desc' => __('Show only the first X characters of the username, or first/last name, or full name, on the "Login as...«option»" button. <br>For example, if you choose 5, the button will be displayed as Login as «Yiann...», or Login as «Chris...», or Login as «Johna...», or Login as «Yiann...».', 'login-as-user'),
            ]
        );

        

        /**
         * Tab Section - Frontend Message
         */
        add_settings_section(
            'frontend_message_display_section',
            esc_html__('Frontend Message', 'login-as-user'),
            '',
            'login-as-user-appearance-frontend-bar'
        );

        // Message Display Position
        add_settings_field(
            'message_display_position',
            esc_html__( 'Message Display Position', 'login-as-user' ),
            array($this->fields, 'selectField'),
            'login-as-user-appearance-frontend-bar',
            'frontend_message_display_section',
            [
                'id' => 'message_display_position',
                'default_value' => 'bottom',
                'options' => [
                    ['id' => 'top', 'label' => esc_html__('Top', 'login-as-user'), 'value' => 'top'],
                    ['id' => 'bottom', 'label' => esc_html__('Bottom', 'login-as-user'), 'value' => 'bottom'],
                    ['id' => 'none', 'label' => esc_html__('None', 'login-as-user'), 'value' => 'none']
                ],
                'desc' => __('Choose where the login message should appear on the frontend. Only Admins and Managers with "login-as-a-user" privileges can see this message.', 'login-as-user'),
            ]
        );

        // Show Admin Link in Topbar
        add_settings_field(
            'show_admin_link_in_topbar',
            esc_html__( 'Show Admin Link in Topbar', 'login-as-user' ),
            array($this->fields, 'selectField'),
            'login-as-user-appearance-frontend-bar',
            'frontend_message_display_section',
            [
                'id' => 'show_admin_link_in_topbar',
                'default_value' => '1',
                'options' => [
                    ['id' => 'yes', 'label' => esc_html__('Yes', 'login-as-user'), 'value' => 'yes'],
                    ['id' => 'no', 'label' => esc_html__('No', 'login-as-user'), 'value' => 'no'],
                ],
                'desc' => __('Display a link in the WordPress topbar to navigate back to dashboard as Admin.', 'login-as-user'),
            ]
        );

        add_settings_field(
            'enable_ping_animation',
            esc_html__( 'Enable Attention Animation', 'login-as-user' ),
            array($this->fields, 'selectField'),
            'login-as-user-appearance-frontend-bar',
            'frontend_message_display_section',
            [
                'id' => 'enable_ping_animation',
                'default_value' => 'no',
                'options' => [
                    ['id' => 'yes', 'label' => esc_html__('Yes', 'login-as-user'), 'value' => 'yes'],
                    ['id' => 'no', 'label' => esc_html__('No', 'login-as-user'), 'value' => 'no'],
                ],
                'desc' => __('Show ping animation on the admin button after logging in as a user.', 'login-as-user'),
            ]
        );

        

        
    }

    /**
     * Appearance section callback
     */
    public function appearance_section_callback()
    {
        echo '<p>' . esc_html__('Configure the \'Login as User\' button size and table column position (leave empty for defaults).', 'login-as-user') . '</p>';
    }

}