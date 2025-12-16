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
class LoginAsUser_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;
	private $plugin_name_clean = 'login-as-user-pro';

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
	}

    /**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name_clean, plugin_dir_url(__FILE__) . 'css/admin.min.css', [], $this->version, 'all');

        // Enqueue Select2 CSS on settings page
        if ($this->is_settings_page()) {
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0', 'all');
 
        }

        $css = w357LoginAsUser::$pluginSettings->loginButtonDisplayAs == 'only_icon' ? '.column-loginasuser_col { --column-loginasuser_col-width: 7%} ' : '';
        
        if ($css) {
            wp_add_inline_style($this->plugin_name_clean, $css);
        }
    }

    /**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() 
	{
		wp_enqueue_script( $this->plugin_name_clean, plugin_dir_url( __FILE__ ) . 'js/admin.min.js', array( 'jquery', ), $this->version, true );
		wp_localize_script( $this->plugin_name_clean, 'loginasuserAjax', array( 'loginasuser_ajaxurl' => admin_url( 'admin-ajax.php' )));
		
		// Enqueue Select2 JS on settings page
		if ($this->is_settings_page()) {
			wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
		}
	}

	/**
	 * Check if we're on the plugin settings page
	 *
	 * @return bool
	 */
	private function is_settings_page() {
		$screen = get_current_screen();
		return $screen && $screen->id === 'settings_page_login-as-user';
	}

	
}