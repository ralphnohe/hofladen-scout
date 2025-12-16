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
require_once __DIR__ . '/helpers/class-plugin-settings.php';
require_once __DIR__ . '/integrations/class-login-as-user-integration-abstract.php';
require_once __DIR__ . '/integrations/class-wp-userlist.php';
require_once __DIR__ . '/class-w357-login-btn.php';

class w357LoginAsUser
{
    /** @var \LoginAsUser_Plugin_Settings */
    public static $pluginSettings;
    
	private $memberpress;
	private $woocommerce;
    private $woocommerce_subscriptions;

    /**
	 * Sets up all the filters and actions.
	 */
	public function run()
	{
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }


        if (!static::$pluginSettings) {
            static::$pluginSettings = new LoginAsUser_Plugin_Settings();
        }
        
		add_filter('user_has_cap', array($this, 'filter_user_has_cap'), 10, 4);
		add_filter('map_meta_cap', array($this, 'filter_map_meta_cap'), 10, 4);
		add_action('init', array($this, 'action_init'));
		add_action('wp_logout', array($this, 'login_as_user_clear_olduser_cookie'));
		add_action('wp_login', array($this, 'login_as_user_clear_olduser_cookie'));
		add_filter('wp_head', array($this, 'login_message_style'), 1);
		add_filter('wp_footer', array($this, 'add_login_message'), 1);
		add_action('admin_bar_menu', array($this, 'login_as_user_link_back_link_on_toolbar'), 999);
		add_filter('removable_query_args', array($this, 'filter_removable_query_args'));
        add_action('personal_options', [$this, 'w357_personal_options']);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		add_filter('usin_user_db_data', array($this, 'usin_user_db_loginasuser'), 1000);
		add_filter('usin_single_user_db_data', array($this, 'usin_user_db_loginasuser'), 1000);
		add_filter('usin_fields', array($this, 'usin_fields_loginasuser'), 1000);
		add_shortcode('login_as_user', array($this, 'loginasuserShortcode'));
         
        if (static::$pluginSettings->isPluginActive('login-as-user-pro')) {
            add_action('admin_notices', [$this, 'disableFreeVersionNotice']);
            remove_action('personal_options', [$this, 'w357_personal_options']);
            remove_filter('wp_head', array($this, 'login_message_style'), 1);
            remove_filter('wp_footer', array($this, 'add_login_message'), 1);
        } 
         

        (new LoginAsUser_WP_Userlist_Integration($this))->init();
        
        // Initialize WooCommerce cart preservation if enabled
        if (static::$pluginSettings->preserveWooCart) {
            $this->initWooCommerceCartPreservation();
        }
		
		// WooCommerce integration
        if (static::$pluginSettings->isPluginActive('woocommerce')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-woocommerce.php';
            $this->woocommerce = new LoginAsUser_WooCommerce_Integration($this);
            $this->woocommerce->init();
        }

        // WooCommerce Subscriptions integration
        if (static::$pluginSettings->isPluginActive('woocommerce-subscriptions')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-woocommerce-subscriptions.php';
            $this->woocommerce_subscriptions = new LoginAsUser_WooCommerce_Subscriptions_Integration($this);
            $this->woocommerce_subscriptions->init();
        }

		// MemberPress integration
        if (static::$pluginSettings->isPluginActive('memberpress')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-memberpress.php';
            $this->memberpress = new LoginAsUser_MemberPress_Integration($this);
            $this->memberpress->init();
        }
        
		// SureCart integration
        if (static::$pluginSettings->isPluginActive('surecart')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-surecart.php';
            (new LoginAsUser_SureCart_Integration($this))->init();
        }
    }

	/**
	 * Manually clear WordPress authentication cookies without triggering clear_auth_cookie action
	 * This prevents conflicts with other plugins that hook into the action
	 * 
	 * @return void
	 */
	private function manual_clear_auth_cookies()
	{
		/** This filter is documented in wp-includes/pluggable.php */
		if (!apply_filters('send_auth_cookies', true, 0, 0, 0, '', '')) {
			return;
		}
		
		$user_id = get_current_user_id();
		
		// Auth cookies.
		setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
		setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
		setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN);
		setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN);
		setcookie(LOGGED_IN_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
		setcookie(LOGGED_IN_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
		
		// Settings cookies.
		setcookie('wp-settings-' . $user_id, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH);
		setcookie('wp-settings-time-' . $user_id, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH);
		
		// Old cookies.
		setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
		setcookie(AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
		setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
		setcookie(SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
		
		// Even older cookies.
		setcookie(USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
		setcookie(PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
		setcookie(USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
		setcookie(PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
		
		// Post password cookie.
		setcookie('wp-postpass_' . COOKIEHASH, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
	}

     
    
    /**
     * Deactivate the FREE version after installing the PRO.
     */
    public function disableFreeVersionNotice()
    {
        if (static::$pluginSettings->isPluginActive('login-as-user-pro')) {
            $deactivateUrl = current_user_can('deactivate_plugins') ? wp_nonce_url(
                admin_url('plugins.php?action=deactivate&plugin=login-as-user/login-as-user.php'),
                'deactivate-plugin_login-as-user/login-as-user.php'
            ) : '';

            printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>',
                __('You need to deactivate and delete the old <b>Login as User (Free) version of plugin</b> on the plugins page.', 'login-as-user') .
                ($deactivateUrl ? '&nbsp<a href="' . esc_url($deactivateUrl) . '">' . __('Click here to Deactivate it', 'login-as-user') . '</a>' : '')
            );
        }
    }

     


	/**
	 * Returns whether or not the current logged in user is being remembered in the form of a persistent browser cookie
	 * (ie. they checked the 'Remember Me' check box when they logged in). This is used to persist the 'remember me'
	 * value when the user switches to another user.
	 *
	 * @return bool Whether the current user is being 'remembered' or not.
	 */
	public static function remember_me()
	{
		/** This filter is documented in wp-includes/pluggable.php */
		$cookie_life = apply_filters('auth_cookie_expiration', 259200, get_current_user_id(), false);
		$current     = wp_parse_auth_cookie('', 'logged_in');

		// Here we calculate the expiration length of the current auth cookie and compare it to the default expiration.
		// If it's greater than this, then we know the user checked 'Remember Me' when they logged in.
		return (($current['expiration'] - time()) > $cookie_life);
	}

	/**
	 * Loads localisation files and routes actions depending on the 'action' query var.
	 */
	public function action_init()
	{
		if (!isset($_REQUEST['action'])) {
			return;
		}

		$current_user = (is_user_logged_in()) ? wp_get_current_user() : null;

		switch ($_REQUEST['action']) {

				// We're attempting to switch to another user:
			case 'login_as_user':
				if (isset($_REQUEST['user_id'])) {
					$user_id = absint($_REQUEST['user_id']);
				} else {
					$user_id = 0;
				}
                $target_wp_user = $user_id ? get_userdata($user_id) : '';

                // Check authentication:
                if (!current_user_can('login_as_user', $user_id)) {
                    error_log(sprintf(__('Web357LoginAsUser: User "%s" (%d) is not allowed to login as user "%s" (%d).' . 'login-as-user'), $current_user ? $current_user->user_login : '', $current_user ? $current_user->ID : 0, $target_wp_user ? $target_wp_user->user_login : $target_wp_user, $target_wp_user ? $target_wp_user->ID : 0));
                    wp_die(esc_html__('Could not login as user.', 'login-as-user'));
                }

				// Check intent:
				check_admin_referer("login_as_user_{$user_id}");

				// Prevent WooCommerce from clearing carts during the impersonation switch
                if (static::$pluginSettings->preserveWooCart) {
                    add_filter('woocommerce_clear_cart_on_logout', '__return_false', 999);
                    add_filter('woocommerce_clear_cart_on_login', '__return_false', 999);
                    add_filter('woocommerce_clear_cart_on_new_login', '__return_false', 999);
                } else {
                    add_filter('woocommerce_clear_cart_on_logout', function ($clear) use ($user_id, $current_user) {
                        return apply_filters('web357_login_as_user_clear_cart_on_logout', false, $user_id, $current_user ? $current_user->ID : 0);
                    }, 999);
                }

				// Switch user:
				$user = $this->login_as_user($user_id, self::remember_me());
				if ($user) {
					$redirect_to = self::get_redirect($user, $current_user);

					// Redirect to the dashboard or the home URL depending on capabilities:
					$args = [];

					if ($redirect_to) 
					{
						// check if the home url exists in redirect to
						if (strpos($redirect_to, home_url('/')) !== false) 
						{
							wp_safe_redirect(add_query_arg($args, $redirect_to), 302, 'Login as User - WordPress Plugin');
						}
						else
						{
							wp_safe_redirect(add_query_arg($args, home_url('/') . $redirect_to), 302, 'Login as User - WordPress Plugin');
						}
					} 
					elseif (!current_user_can('read')) 
					{
						wp_safe_redirect(add_query_arg($args, home_url('/')), 302, 'Login as User - WordPress Plugin');
					} 
					else 
					{
						// Modify the redirect logic to check for the redirect_to value from the shortcode
						$shortcode_redirect = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : '';
						
						// When determining the redirect_to URL, prioritize the shortcode redirect if available
						if (!empty($shortcode_redirect)) {
							$redirect_to = home_url('/') . ltrim($shortcode_redirect, '/');
						} else {
							// Fallback to the default plugin redirect_to logic
							$options = (object) get_option('login_as_user_options');
							$redirect_to = (!empty($options->redirect_to)) ? home_url('/') . $options->redirect_to : home_url('/');
						}

						wp_safe_redirect(add_query_arg($args, $redirect_to), 302, 'Login as User - WordPress Plugin');
					}
					exit;
				} else {
                    error_log(sprintf(__('Web357LoginAsUser: Could not login as user, target user "%s" (%d) not found' . 'login-as-user'), (string)($target_wp_user ? $target_wp_user->user_login : $target_wp_user), (int)($target_wp_user ? $target_wp_user->ID : 0)));
                    wp_die(esc_html__('Could not login as user.', 'login-as-user'));
				}
				break;

				// We're attempting to switch back to the originating user:
			case 'login_as_olduser':
				// Fetch the originating user data:
				$old_user = $this->get_old_user();
				if (!$old_user) {
                    error_log(__('Web357LoginAsUser: Old user not found' . 'login-as-user'));
                    wp_die(esc_html__('Could not login as user.', 'login-as-user'));
				}

				// Check authentication:
				if (!self::authenticate_old_user($old_user)) {
                    error_log(sprintf(__('Web357LoginAsUser: Authentication failed for old user "%s" (%d)' . 'login-as-user'), (string)($old_user->user_login), $old_user->ID));
                    wp_die(esc_html__('Could not login as user.', 'login-as-user'));
				}

				// Check intent:
				check_admin_referer("login_as_olduser_{$old_user->ID}");

				// Prevent WooCommerce from clearing carts during the switch-back
                if (static::$pluginSettings->preserveWooCart) {
                    add_filter('woocommerce_clear_cart_on_logout', '__return_false', 999);
                    add_filter('woocommerce_clear_cart_on_login', '__return_false', 999);
                    add_filter('woocommerce_clear_cart_on_new_login', '__return_false', 999);
                } else {
                    add_filter('woocommerce_clear_cart_on_logout', function ($clear) use ($old_user, $current_user) {
                        return apply_filters('web357_login_as_user_clear_cart_on_logout', false, $old_user ? $old_user->ID : 0, $current_user ? $current_user->ID : 0);
                    }, 999);
                }

				// Switch user:
				if ($this->login_as_user($old_user->ID, self::remember_me(), false)) {

					if (!empty($_REQUEST['interim-login'])) {
						$GLOBALS['interim_login'] = 'success'; // @codingStandardsIgnoreLine
						login_header('', '');
						exit;
					}

					$redirect_to = self::get_redirect($old_user, $current_user);
					$args = [];
					if ($redirect_to) {
						wp_safe_redirect(add_query_arg($args, $redirect_to), 302, 'Login as User - WordPress Plugin');
					} else {
						// redirect the user to the correct page
						$login_as_user_get_back_url_cookie = $this->login_as_user_get_back_url_cookie();
						$back_url = (!empty($login_as_user_get_back_url_cookie)) ? urldecode($login_as_user_get_back_url_cookie) : admin_url('users.php');
						wp_safe_redirect(add_query_arg($args, $back_url), 302, 'Login as User - WordPress Plugin');
					}
                    exit;
				} else {
					wp_die(esc_html__('Could not switch users.', 'login-as-user'));
				}
				break;
		}
	}

	/**
	 * Fetches the URL to redirect to for a given user (used after switching).
	 *
	 * @param  WP_User $new_user Optional. The new user's WP_User object.
	 * @param  WP_User $old_user Optional. The old user's WP_User object.
	 * @return string The URL to redirect to.
	 */
	protected static function get_redirect(?WP_User $new_user = null, ?WP_User $old_user = null)
	{
		if (!empty($_REQUEST['redirect_to'])) {
			$redirect_to           = self::remove_query_args(wp_unslash($_REQUEST['redirect_to']));
			$requested_redirect_to = wp_unslash($_REQUEST['redirect_to']);
		} else {
			$redirect_to           = '';
			$requested_redirect_to = '';
		}

		if (!$new_user) {
			$redirect_to = apply_filters('web357_login_as_user_logout_redirect', $redirect_to, $requested_redirect_to, $old_user);
		} else {
			$redirect_to = apply_filters('web357_login_as_user_login_redirect', $redirect_to, $requested_redirect_to, $new_user);
		}

		return $redirect_to;
	}

	/**
	 * Validates the old user cookie and returns its user data.
	 *
	 * @return false|WP_User False if there's no old user cookie or it's invalid, WP_User object if it's present and valid.
	 */
	public function get_old_user()
	{
		$cookie = $this->login_as_user_get_olduser_cookie();
		if (!empty($cookie)) {
			$old_user_id = wp_validate_auth_cookie($cookie, 'logged_in');

			if ($old_user_id) {
				return get_userdata($old_user_id);
			}
		}
		return false;
	}

	/**
	 * Authenticates an old user by verifying the latest entry in the auth cookie.
	 *
	 * @param WP_User $user A WP_User object (usually from the logged_in cookie).
	 * @return bool Whether verification with the auth cookie passed.
	 */
	public function authenticate_old_user(WP_User $user)
	{
		$cookie = $this->login_as_user_get_auth_cookie();
		if (!empty($cookie)) {
			if (self::secure_auth_cookie()) {
				$scheme = 'secure_auth';
			} else {
				$scheme = 'auth';
			}

			$old_user_id = wp_validate_auth_cookie(end($cookie), $scheme);

			if ($old_user_id) {
				return ($user->ID === $old_user_id);
			}
		}
		return false;
	}

	/**
	 * Adds a 'Switch back to {user}' link to the WordPress frontend
	 *
	 * @param  string $message The login screen message.
	 * @return string The login screen message.
	 */
	public function login_message_style($message)
	{
		$options = (object) get_option( 'login_as_user_options' );
		$message_display_position_option = (!empty($options->message_display_position)) ? $options->message_display_position : 'bottom';

		// If the message display position is set to none, then return
		if ($message_display_position_option == 'none') {
			return;
		}
		
		$old_user = $this->get_old_user();

		if ($old_user instanceof WP_User) {		

			
			if (is_admin_bar_showing() && $message_display_position_option == 'top') 
			{
				$css = <<<CSS
				body { 
					margin-top: 60px !important; 
					padding-top: 70px !important; 
				}
				.login-as-user-top { 
					top: 32px!important; 
				}
				@media only screen and (max-width: 782px) {
					.login-as-user-top { 
						top: 46px!important; 
					}
				}
CSS;
			}
			elseif (is_admin_bar_showing() && $message_display_position_option == 'bottom') 
			{
				$css = <<<CSS
				body { 
					margin-bottom: 60px !important; 
					padding-bottom: 70px !important; 
				}
				.login-as-user-bottom { 
					bottom: 0; 
				}
CSS;
			} 
			elseif ( $message_display_position_option == 'top') 
			{
				$css = <<<CSS
				body { 
					margin-top: 60px !important; 
					padding-top: 70px !important; 
				}
				.login-as-user-top { 
					top: 32px!important; 
				}
				@media only screen and (max-width: 782px) {
					.login-as-user-top { 
						top: 46px!important; 
					}
				}
CSS;
			}
			elseif ($message_display_position_option == 'bottom') 
			{
				$css = <<<CSS
				body { 
					margin-bottom: 60px !important; 
					padding-bottom: 70px !important; 
				}
				.login-as-user-bottom { 
					bottom: 0; 
				}
CSS;
			} 
			else 
			{
				$css = <<<CSS
				body { 
					padding-top: 70px !important; 
				}
				@media only screen and (max-width: 420px) {
					body { 
						padding-top: 120px !important; 
					}
				}
CSS;
			}

			// Inline CSS
			wp_add_inline_style('login-as-user-inline-style', $css);

			// Add the class to the body
			add_filter('body_class', function($classes) use ($message_display_position_option) {
				$classes[] = 'login-as-user-' . $message_display_position_option;
				return $classes;
			});

		}
	}

    function add_login_message()
    {

        if (static::$pluginSettings->messageDisplayPosition == 'none') {
            return;
        }
        
        $old_user = $this->get_old_user();
        if ($old_user instanceof WP_User) {
            $link = sprintf(
            /* Translators: 1: user display name; 2: username; */
                __('go back to admin as %1$s (%2$s)', 'login-as-user'),
                $old_user->display_name,
                $old_user->user_email
            );
            $url = self::back_url($old_user);

            if (!empty($_REQUEST['interim-login'])) {
                $url = add_query_arg([
                    'interim-login' => '1',
                ], $url);
            } elseif (!empty($_REQUEST['redirect_to'])) {
                $url = add_query_arg([
                    'redirect_to' => urlencode(wp_unslash($_REQUEST['redirect_to'])),
                ], $url);
            }

            $current_user = (is_user_logged_in()) ? wp_get_current_user() : null;
            $current_user_name = '';
            if (is_object($current_user) && !empty($current_user->display_name) && !empty($current_user->user_login)) {
                $current_user_name = sprintf(
                /* Translators: 1: user display name; 2: username; */
                    __('%1$s (%2$s)', 'login-as-user'),
                    $current_user->display_name,
                    $current_user->user_login
                );
            }
            // Output the message
            $message = '';
            $message .= '<div class="login-as-user login-as-user-' . esc_attr(static::$pluginSettings->messageDisplayPosition) . '">';
            $message .= '<div class="login-as-user-inner">';
            $message .= '<div class="login-as-user-content">';
            $message .= '<div class="login-as-user-msg">' . sprintf(__('You have been logged in as the user <strong>%1$s</strong>', 'login-as-user'), esc_html($current_user_name)) . '</div>';
            $message .= '<a class="button w357-login-as-user-btn w357-login-as-user-frontend-btn" href="' . esc_url($url) . '">' . esc_html($link) . '</a>';
            $message .= '</div>';
            $message .= '</div>';
            $message .= '</div>';
            echo $message;
        }
    }

    function login_as_user_link_back_link_on_toolbar($wp_admin_bar) {

		$options = (object) get_option( 'login_as_user_options' );
		$show_admin_link_in_topbar_option = (!empty($options->show_admin_link_in_topbar)) ? $options->show_admin_link_in_topbar : 'yes';

		if (is_admin_bar_showing() && $show_admin_link_in_topbar_option == 'yes') {

			$old_user = $this->get_old_user();

			if ($old_user instanceof WP_User) {
				$url = self::back_url($old_user);

				if (!empty($_REQUEST['interim-login'])) {
					$url = add_query_arg(array(
						'interim-login' => '1',
					), $url);
				} elseif (!empty($_REQUEST['redirect_to'])) {
					$url = add_query_arg(array(
						'redirect_to' => urlencode(wp_unslash($_REQUEST['redirect_to'])),
					), $url);
				}

				$current_user = (is_user_logged_in()) ? wp_get_current_user() : null;
				$current_user_name = '';
				if (is_object($current_user) && !empty($current_user->display_name) && !empty($current_user->user_login)) {
					$current_user_name = sprintf(
						/* Translators: 1: user display name; 2: username; */
						__('%1$s (%2$s)', 'login-as-user'),
						$current_user->display_name,
						$current_user->user_login
					);
				}

				// Add a new top-level item with a back arrow icon
				$args = array(
					'id'    => 'lau-back-to-admin-dashboard',
					'title' => sprintf(__('Go back as %1$s', 'login-as-user'), $old_user->display_name),
					'href'  => esc_url($url),
					'meta'  => array(
						'class' => 'logged-in-successfully',
						'title' => sprintf(__('You have been logged in as the user "%1$s".', 'login-as-user'), esc_html__($current_user_name)) . ' ' . sprintf(
									__('Click here to go back to admin dashboard as %1$s (%2$s).', 'login-as-user'), $old_user->display_name, $old_user->user_email
						),
					)
				);
				$wp_admin_bar->add_node($args);
			}
		}
	}
	
	public function addBodyClass( $classes ) 
	{
		$classes[] = 'admin-has-been-logged-in-as-a-user';
		return $classes;
	}

	public function enqueue_styles()
	{
		$options = get_option('login_as_user_options', array());
		$message_display_position_option = (!empty($options['message_display_position'])) ? $options['message_display_position'] : 'bottom';
		$show_admin_link_in_topbar_option = (!empty($options['show_admin_link_in_topbar'])) ? $options['show_admin_link_in_topbar'] : 'yes';
		$enable_ping_animation = (!empty($options['enable_ping_animation'])) ? $options['enable_ping_animation'] : 'no';

		// do not proceed if user is not logged in
		$old_user = $this->get_old_user();
		if ($old_user instanceof WP_User && ($message_display_position_option !== 'none' || $show_admin_link_in_topbar_option === 'yes')) {

			wp_enqueue_style('login-as-user', plugin_dir_url(dirname(__FILE__)) . 'public/css/public.min.css', array(), LOGINASUSER_VERSION, 'all');
			wp_enqueue_script('login-as-user', plugin_dir_url(dirname(__FILE__)) . 'public/js/public.min.js', array('jquery'), LOGINASUSER_VERSION, false);
			// Add localization for the ping animation setting
			wp_localize_script('login-as-user', 'w357LoginAsUser', array(
				'enablePing' => $enable_ping_animation === 'yes' ? '1' : '0'
			));
			wp_register_style('login-as-user-inline-style', false);
			wp_enqueue_style('login-as-user-inline-style');

            
		}
	}

	/**
	 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
	 *
	 * @link https://core.trac.wordpress.org/ticket/23367
	 *
	 * @param string[] $args List of removable query arguments.
	 * @return string[] Updated list of removable query arguments.
	 */
	public function filter_removable_query_args(array $args)
	{
		return array_merge($args, array('logged_in_as_user'));
	}

	/**
	 * Returns the switch to or switch back URL for a given user.
	 *
	 * @param  WP_User $user The user to be switched to.
	 * @return string|false The required URL, or false if there's no old user or the user doesn't have the required capability.
	 */
	public function build_the_login_as_user_url(WP_User $user, array $params =[])
	{
		$old_user = $this->get_old_user();

		if ($old_user && ($old_user->ID === $user->ID)) {
			return self::back_url($old_user);
		} elseif (current_user_can('login_as_user', $user->ID)) {
            return self::loginasuser_url($user, $params);
		} else {
			return false;
		}
	}
 
	/**
	 * Returns the nonce-secured URL needed to switch to a given user ID.
	 *
	 * @param  WP_User $user The user to be switched to.
	 * @return string The required URL.
	 */
	public static function loginasuser_url(WP_User $user, array $params =[])
	{
        // Check if HTTPS or HTTP
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';

        // Build the current URL with the correct protocol
        if (!empty($params['logout_redirect_url'])) {
            $current_url = $params['logout_redirect_url'];
        } elseif (static::$pluginSettings->logoutRedirectUrl) {
            $current_url = static::$pluginSettings->logoutRedirectUrl;
        } else {
            $current_url = $protocol . $_SERVER['HTTP_HOST'] . wp_unslash($_SERVER['REQUEST_URI']);
            if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') !== false) {
                $current_url = wp_unslash($_SERVER['HTTP_REFERER']);
            }
        }
        
		return wp_nonce_url(add_query_arg(array(
			'action'  => 'login_as_user',
			'user_id' => $user->ID,
			'back_url' => urlencode($current_url),
		), wp_login_url()), "login_as_user_{$user->ID}");
	}

	/**
	 * Returns the nonce-secured URL needed to switch back to the originating user.
	 *
	 * @param  WP_User $user The old user.
	 * @return string        The required URL.
	 */
	public static function back_url(WP_User $user)
	{
		return wp_nonce_url(add_query_arg(array(
			'action' => 'login_as_olduser',

		), wp_login_url()), "login_as_olduser_{$user->ID}");
	}

	/**
	 * Returns the current URL.
	 *
	 * @return string The current URL.
	 */
	public static function current_url()
	{
		return (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // @codingStandardsIgnoreLine
	}

	/**
	 * Removes a list of common confirmation-style query args from a URL.
	 *
	 * @param  string $url A URL.
	 * @return string The URL with query args removed.
	 */
	public static function remove_query_args($url)
	{
		if (function_exists('wp_removable_query_args')) {
			$url = remove_query_arg(wp_removable_query_args(), $url);
		}

		return $url;
	}

	/**
	 * Returns whether or not User Switching's equivalent of the 'logged_in' cookie should be secure.
	 *
	 * This is used to set the 'secure' flag on the old user cookie, for enhanced security.
	 *
	 * @link https://core.trac.wordpress.org/ticket/15330
	 *
	 * @return bool Should the old user cookie be secure?
	 */
	public static function secure_olduser_cookie()
	{
		return (is_ssl() && ('https' === parse_url(home_url(), PHP_URL_SCHEME)));
	}

	public static function secure_back_url_cookie()
	{
		return (is_ssl() && ('https' === parse_url(home_url(), PHP_URL_SCHEME)));
	}

	/**
	 * Returns whether or not User Switching's equivalent of the 'auth' cookie should be secure.
	 *
	 * This is used to determine whether to set a secure auth cookie or not.
	 *
	 * @return bool Should the auth cookie be secure?
	 */
	public static function secure_auth_cookie()
	{
		return (is_ssl() && ('https' === parse_url(wp_login_url(), PHP_URL_SCHEME)));
	}

	/**
	 * Filters a user's capabilities so they can be altered at runtime.
	 *
	 * This is used to:
	 *  - Grant the 'login_as_user' capability to the user if they have the ability to edit the user they're trying to
	 *    switch to (and that user is not themselves).
	 *  - Grant the 'switch_off' capability to the user if they can edit other users.
	 *
	 * Important: This does not get called for Super Admins. See filter_map_meta_cap() below.
	 *
	 * @param bool[]   $user_caps     Array of key/value pairs where keys represent a capability name and boolean values
	 *                                represent whether the user has that capability.
	 * @param string[] $required_caps Required primitive capabilities for the requested capability.
	 * @param array    $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type string    $0 Requested capability.
	 *     @type int       $1 Concerned user ID.
	 *     @type mixed  ...$2 Optional second and further parameters.
	 * }
	 * @param WP_User  $user          Concerned user object.
	 * @return bool[] Concerned user's capabilities.
	 */ 
    public function filter_user_has_cap(array $user_caps, array $required_caps, array $args, WP_User $user)
    {
        if (isset($args[2]) && 'login_as_user' === $args[0] && (bool)$args[2]) {
            

             
            if (!static::$pluginSettings->isPluginActive('login-as-user-pro')) {
                $user_caps['login_as_user'] = (user_can($user->ID, 'edit_user', $args[2]) && ($args[2] !== $user->ID));
            }
             
        }

        return $user_caps;
    }

    /**
     * Filters the required primitive capabilities for the given primitive or meta capability.
     *
     * This is used to:
     *  - Add the 'do_not_allow' capability to the list of required capabilities when a Super Admin is trying to switch
     *    to themselves.
     *
     * It affects nothing else as Super Admins can do everything by default.
     *
     * @param string[] $required_caps Required primitive capabilities for the requested capability.
     * @param string $cap Capability or meta capability being checked.
     * @param int $user_id Concerned user ID.
     * @param array $args {
     *     Arguments that accompany the requested capability check.
     *
     * @type mixed ...$0 Optional second and further parameters.
     * }
     * @return string[] Required capabilities for the requested action.
     */
    public function filter_map_meta_cap(array $required_caps, $cap, $user_id, array $args)
    {
        if (('login_as_user' === $cap) && ($args[0] === $user_id)) {
            $required_caps[] = 'do_not_allow';
        }
        return $required_caps;
    }

    

	// Get the string type for the Login as ... button.
	public function login_as_type($user, $allow_trim_name = true)
	{
		$options = (object) get_option( 'login_as_user_options' );
		if (!empty($options->login_as_type))
		{
			switch ($options->login_as_type) 
			{
				case 'user_login':
					$login_as_type = esc_html__($user->user_login, 'login-as-user');
					break;
					
				case 'user_firstname':
					$login_as_type = (!empty($user->user_firstname)) ? esc_html__($user->user_firstname, 'login-as-user') : esc_html__($user->user_login, 'login-as-user');
					break;

				case 'user_lastname':
					$login_as_type = (!empty($user->user_lastname)) ? esc_html__($user->user_lastname, 'login-as-user') : esc_html__($user->user_login, 'login-as-user');
					break;

				case 'user_fullname':
					$login_as_type = (!empty($user->user_firstname) || !empty($user->user_lastname)) ? esc_html__($user->user_firstname . ' ' . $user->user_lastname, 'login-as-user') : esc_html__($user->user_login, 'login-as-user');
					break;
				
				case 'only_icon':
					$login_as_type = esc_html__($user->user_login, 'login-as-user');
					break;
			
				default:
					$login_as_type = esc_html__($user->user_login, 'login-as-user');
					break;
			}
		}
		else
		{
			$login_as_type = esc_html__($user->user_login, 'login-as-user');
		}

		$login_as_type_characters_limit = (isset($options->login_as_type_characters_limit)) ? $options->login_as_type_characters_limit : 0;
		if (is_numeric($login_as_type_characters_limit) && $login_as_type_characters_limit > 0 && $allow_trim_name === TRUE)
		{
			if(strlen($login_as_type) > $login_as_type_characters_limit)
			{
				$login_as_type = trim(substr($login_as_type, 0, $login_as_type_characters_limit)) . '&hellip;';
			}
		}

		return $login_as_type;
	}

	public function w357_personal_options( WP_User $user ) 
	{
		$login_as_user_url = $this->build_the_login_as_user_url($user);

		if (get_current_user_id() != $user->ID && !empty($user->user_login))
		{
			echo '<a class="button w357-login-as-user-btn w357-login-as-user-personal-options-btn" href="' . esc_url($login_as_user_url) . '" title="'.esc_html__('Login as', 'login-as-user').': ' . $this->login_as_type($user, false) . '"><span class="dashicons dashicons-admin-users"></span> '.esc_html__('Login as', 'login-as-user').': <strong>' . $this->login_as_type($user) . '</strong></a>';
		}
		else
		{
			if (!current_user_can('login_as_user', $user->ID)) {
				echo __('Could not login as this user.', 'login-as-user');
			}
			else
			{
				echo __('You are already logged in.', 'login-as-user');
			}
		}
	}

     
    function onlyInProTextLink()
    {
        return static::$pluginSettings->isPluginActive('login-as-user-pro') ? '' : '<a title="' . __('The Login as User functionality for WooCommerce is only available in the PRO version.', 'login-as-user') . '" href="https://www.web357.com/login-as-user-wordpress-plugin?utm_source=buyprolink-loginasuserwp&utm_medium=CLIENT-WP-Backend-BuyProLink-Web357-loginasuserwp&utm_campaign=buyprolink-loginasuserwp#pricing" target="_blank"><small>Only in PRO version</small></a>';
    }
     

	/**
	 * Sets authorisation cookies containing the originating user information.
	 *
	 * @since 1.4.0 The `$token` parameter was added.
	 *
	 * @param int    $old_user_id The ID of the originating user, usually the current logged in user.
	 * @param bool   $pop         Optional. Pop the latest user off the auth cookie, instead of appending the new one. Default false.
	 * @param string $token       Optional. The old user's session token to store for later reuse. Default empty string.
	 */
	public function login_as_user_set_olduser_cookie($old_user_id, $pop = false, $token = '')
	{
		$secure_auth_cookie    = w357LoginAsUser::secure_auth_cookie();
		$secure_olduser_cookie = w357LoginAsUser::secure_olduser_cookie();
		$secure_back_url_cookie = w357LoginAsUser::secure_back_url_cookie();
		$expiration            = time() + 259200; // 3 days
		$auth_cookie           = $this->login_as_user_get_auth_cookie();
		$olduser_cookie        = wp_generate_auth_cookie($old_user_id, $expiration, 'logged_in', $token);

		if ($secure_auth_cookie) {
			$auth_cookie_name = 'wp_loginasuser_secure_'.COOKIEHASH;
			$scheme           = 'secure_auth';
		} else {
			$auth_cookie_name = 'wp_loginasuser_'.COOKIEHASH;
			$scheme           = 'auth';
		}

		if ($pop) {
			array_pop($auth_cookie);
		} else {
			array_push($auth_cookie, wp_generate_auth_cookie($old_user_id, $expiration, $scheme, $token));
		}

		$auth_cookie = json_encode($auth_cookie);

		/** This filter is documented in wp-includes/pluggable.php */
		if (!apply_filters('send_auth_cookies', true)) {
			return;
		}

		setcookie($auth_cookie_name, $auth_cookie, $expiration, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_auth_cookie, true);
		setcookie('wp_loginasuser_olduser_'.COOKIEHASH, $olduser_cookie, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure_olduser_cookie, true);
		$get_back_url = isset( $_GET['back_url'] ) ? esc_url_raw( $_GET['back_url'] ) : '';

		setcookie('wp_loginasuser_backurl_'.COOKIEHASH, $get_back_url, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure_back_url_cookie, true);
	}

	/**
	 * Clears the cookies containing the originating user, or pops the latest item off the end if there's more than one.
	 *
	 * @param bool $clear_all Optional. Whether to clear the cookies (as opposed to just popping the last user off the end). Default true.
	 */
	public function login_as_user_clear_olduser_cookie($clear_all = true)
	{
		$auth_cookie = $this->login_as_user_get_auth_cookie();
		if (!empty($auth_cookie)) {
			array_pop($auth_cookie);
		}
		if ($clear_all || empty($auth_cookie)) {
			/**
			 * Fires just before the user switching cookies are cleared.
			 *
			 * @since 1.4.0
			 */
			//do_action('clear_olduser_cookie');

			/** This filter is documented in wp-includes/pluggable.php */
			if (!apply_filters('send_auth_cookies', true)) {
				return;
			}

			$expire = time() - 31536000;
			setcookie('wp_loginasuser_'.COOKIEHASH,         ' ', $expire, SITECOOKIEPATH, COOKIE_DOMAIN);
			setcookie('wp_loginasuser_secure_'.COOKIEHASH,  ' ', $expire, SITECOOKIEPATH, COOKIE_DOMAIN);
			setcookie('wp_loginasuser_olduser_'.COOKIEHASH, ' ', $expire, COOKIEPATH, COOKIE_DOMAIN);
			setcookie('wp_loginasuser_backurl_'.COOKIEHASH, ' ', $expire, COOKIEPATH, COOKIE_DOMAIN);
		} else {
			if (w357LoginAsUser::secure_auth_cookie()) {
				$scheme = 'secure_auth';
			} else {
				$scheme = 'auth';
			}

			$old_cookie = end($auth_cookie);

			$old_user_id = wp_validate_auth_cookie($old_cookie, $scheme);
			if ($old_user_id) {
				$parts = wp_parse_auth_cookie($old_cookie, $scheme);
				$this->login_as_user_set_olduser_cookie($old_user_id, true, $parts['token']);
			}
		}
	}

	/**
	 * Gets the value of the cookie containing the originating user.
	 *
	 * @return string|false The old user cookie, or boolean false if there isn't one.
	 */
	public function login_as_user_get_olduser_cookie()
	{
		if (isset($_COOKIE['wp_loginasuser_olduser_'.COOKIEHASH])) {
			return wp_unslash($_COOKIE['wp_loginasuser_olduser_'.COOKIEHASH]);
		} else {
			return false;
		}
	}

	/**
	 * Gets the value of the cookie containing the originating user.
	 *
	 * @return string|false The old user cookie, or boolean false if there isn't one.
	 */
	public function login_as_user_get_back_url_cookie()
	{
		if (isset($_COOKIE['wp_loginasuser_backurl_'.COOKIEHASH])) {
			return wp_unslash($_COOKIE['wp_loginasuser_backurl_'.COOKIEHASH]);
		} else {
			return false;
		}
	}

	/**
	 * Gets the value of the auth cookie containing the list of originating users.
	 *
	 * @return string[] Array of originating user authentication cookie values. Empty array if there are none.
	 */
	public function login_as_user_get_auth_cookie()
	{
		if (w357LoginAsUser::secure_auth_cookie()) {
			$auth_cookie_name = 'wp_loginasuser_secure_'.COOKIEHASH;
		} else {
			$auth_cookie_name = 'wp_loginasuser_'.COOKIEHASH;
		}

		if (isset($_COOKIE[$auth_cookie_name]) && is_string($_COOKIE[$auth_cookie_name])) {
			$cookie = json_decode(wp_unslash($_COOKIE[$auth_cookie_name]));
		}
		if (!isset($cookie) || !is_array($cookie)) {
			$cookie = array();
		}
		return $cookie;
	}

	/**
	 * Switches the current logged in user to the specified user.
	 *
	 * @param  int  $user_id      The ID of the user to switch to.
	 * @param  bool $remember     Optional. Whether to 'remember' the user in the form of a persistent browser cookie. Default false.
	 * @param  bool $set_old_user Optional. Whether to set the old user cookie. Default true.
	 * @return false|WP_User WP_User object on success, false on failure.
	 */
	public function login_as_user($user_id, $remember = false, $set_old_user = true)
	{
		$user = get_userdata($user_id);

		if (!$user) 
		{
			return false;
		}

		$old_user_id  = (is_user_logged_in()) ? get_current_user_id() : false;
		$old_token    = function_exists('wp_get_session_token') ? wp_get_session_token() : '';
		$auth_cookie  = $this->login_as_user_get_auth_cookie();
		$cookie_parts = wp_parse_auth_cookie(end($auth_cookie));

		if ($set_old_user && $old_user_id) {
			// Switching to another user
			$new_token = '';
			$this->login_as_user_set_olduser_cookie($old_user_id, false, $old_token);
		} else {
			// Switching back, either after being switched off or after being switched to another user
			$new_token = isset($cookie_parts['token']) ? $cookie_parts['token'] : '';
			$this->login_as_user_clear_olduser_cookie(false);
		}

		/**
		 * Attaches the original user ID and session token to the new session when a user switches to another user.
		 *
		 * @param array $session Array of extra data.
		 * @param int   $user_id User ID.
		 * @return array Array of extra data.
		 */
		$session_filter = function (array $session, $user_id) use ($old_user_id, $old_token) {
			$session['logged_in_from_id']      = $old_user_id;
			$session['logged_in_from_session'] = $old_token;
			return $session;
		};

		add_filter('attach_session_information', $session_filter, 99, 2);

		// Manually clear WordPress auth cookies without triggering the action hook
		// This prevents conflicts with other plugins that hook into clear_auth_cookie
		$this->manual_clear_auth_cookies(); // wp_clear_auth_cookie(); 

		wp_set_auth_cookie($user_id, $remember, '', $new_token);
		wp_set_current_user($user_id);

		remove_filter('attach_session_information', $session_filter, 99);

		if ($set_old_user) {
			/**
			 * Fires when a user switches to another user account.
			 *
			 * @since 0.6.0
			 * @since 1.4.0 The `$new_token` and `$old_token` parameters were added.
			 *
			 * @param int    $user_id     The ID of the user being switched to.
			 * @param int    $old_user_id The ID of the user being switched from.
			 * @param string $new_token   The token of the session of the user being switched to. Can be an empty string
			 *                            or a token for a session that may or may not still be valid.
			 * @param string $old_token   The token of the session of the user being switched from.
			 */
			//do_action('login_as_user', $user_id, $old_user_id, $new_token, $old_token);
		} else {
			/**
			 * Fires when a user switches back to their originating account.
			 *
			 * @since 0.6.0
			 * @since 1.4.0 The `$new_token` and `$old_token` parameters were added.
			 *
			 * @param int       $user_id     The ID of the user being switched back to.
			 * @param int|false $old_user_id The ID of the user being switched from, or false if the user is switching back
			 *                               after having been switched off.
			 * @param string    $new_token   The token of the session of the user being switched to. Can be an empty string
			 *                               or a token for a session that may or may not still be valid.
			 * @param string    $old_token   The token of the session of the user being switched from.
			 */
			//do_action('switch_back_user', $user_id, $old_user_id, $new_token, $old_token);
		}

		if ($old_token && $old_user_id && !$set_old_user) {
			// When switching back, destroy the session for the old user
			$manager = WP_Session_Tokens::get_instance($old_user_id);
			$manager->destroy($old_token);
		}

		// When switching, instruct WooCommerce to forget about the current user's session
        if (function_exists('WC') && static::$pluginSettings->isPluginActive('woocommerce')) {
			// Resolve WooCommerce instance without directly calling WC() to satisfy static analyzers.
			$wc = function_exists('WC') ? call_user_func('WC') : (isset($GLOBALS['woocommerce']) ? $GLOBALS['woocommerce'] : null);
			// Allow disabling via filter to preserve persistent carts/sessions.
			if ($wc && !static::$pluginSettings->preserveWooCart && apply_filters('web357_login_as_user_forget_wc_session', true, $user_id, $old_user_id)) {
				LoginAsUser_WooCommerce_Integration::forget_woocommerce_session($wc);
			}
		}

		return $user;
	}

	function loginasuser_individual_btn($user_data)
	{
		$user = new WP_User($user_data->ID);

		$login_as_user_url = $this->build_the_login_as_user_url($user);

		if (!current_user_can('login_as_user', $user_data->ID)) {
			return __('Could not login as this user.', 'login-as-user');
		}
		
		if (!$login_as_user_url || empty($user->user_login)) 
		{
			return __('Already logged in.', 'login-as-user');
		}

		return ('<a class="button w357-login-as-user-btn w357-login-as-user-woo-individual-btn" href="' . esc_url($login_as_user_url) . '" title="'.esc_html__('Login as', 'login-as-user').': ' . $this->login_as_type($user, false) . '"><span class="dashicons dashicons-admin-users"></span> individual '.esc_html__('Login as', 'login-as-user').': <strong>' . $this->login_as_type($user) . '</strong></a>');
	}

	/**
	 * Compatible with User Insights WordPress plugin
	 *
	 * @param [type] $user_data
	 * @return void
	 */
	function usin_user_db_loginasuser($user_data)
	{
		$user_data->usin_meta_loginasuser = $this->loginasuser_individual_btn($user_data);
		return $user_data;
	}

	/**
	 * Compatible with User Insights WordPress plugin
	 *
	 * @param [type] $fields
	 * @return void
	 */
	function usin_fields_loginasuser($fields)
	{
		foreach ($fields as $i => $field) {
			if(isset($field['id']) && $field['id'] == 'usin_meta_loginasuser'){

				$fields[$i]['isEditableField'] = false;
				$fields[$i]['allowHtml'] = true;
			}
		}
		
		return $fields;
	}

	// Usage: [login_as_user user_id="1" redirect_to="/my-account" button_name="Login as $USER"]
	function loginasuserShortcode($atts)
	{
		

		 
		ob_start(); 
		echo "<div>".$this->onlyInProTextLink()."</div>";
		return ob_get_clean();
		 
	}
	
	

	/**
	 * Initialize WooCommerce cart preservation functionality
	 */
	protected function initWooCommerceCartPreservation()
	{
		if (!static::$pluginSettings->isPluginActive('woocommerce')) {
			return;
		}

		// Prevent WooCommerce from clearing carts on login/logout/new login
		add_filter('woocommerce_clear_cart_on_logout', '__return_false', 9999);
		add_filter('woocommerce_clear_cart_on_login', '__return_false', 9999);
		add_filter('woocommerce_clear_cart_on_new_login', '__return_false', 9999);

		// Force persistent cart to be enabled for logged-in users
		add_filter('woocommerce_persistent_cart_enabled', '__return_true', 9999);

		// Ensure WC session/cart initializes right after login
		add_action('wp_login', [$this, 'initWooCommerceCartAfterLogin'], 1, 2);

		// Backup cart to user meta after any change
		add_action('woocommerce_cart_updated', [$this, 'backupWooCommerceCart']);

		// Restore cart if empty after a switch (runs on frontend before output)
		add_action('template_redirect', [$this, 'restoreWooCommerceCartIfEmpty']);
	}

	/**
	 * Initialize WooCommerce cart after login
	 */
	public function initWooCommerceCartAfterLogin($login, $user)
	{
		if (function_exists('WC')) {
			$wc = call_user_func('WC');
			if ($wc && isset($wc->cart) && $wc->cart) {
				$wc->cart->get_cart(); // triggers load if needed
			}
		}
	}

	/**
	 * Backup cart to user meta after any change
	 */
	public function backupWooCommerceCart()
	{
		if (!is_user_logged_in() || !function_exists('WC')) {
			return;
		}
		
		$wc = call_user_func('WC');
		if (!$wc || !isset($wc->cart) || !$wc->cart) {
			return;
		}
		
		$cart = $wc->cart->get_cart();
		update_user_meta(get_current_user_id(), '_lau_cart_backup', $cart);
	}

	/**
	 * Restore cart if empty after a switch
	 */
	public function restoreWooCommerceCartIfEmpty()
	{
		if (!is_user_logged_in() || !function_exists('WC')) {
			return;
		}
		
		$wc = call_user_func('WC');
		if (!$wc || !isset($wc->cart) || !$wc->cart) {
			return;
		}

		// Force load
		$wc->cart->get_cart();
		$count = (int) $wc->cart->get_cart_contents_count();
		
		if ($count > 0) {
			return;
		}

		$backup = get_user_meta(get_current_user_id(), '_lau_cart_backup', true);
		if (is_array($backup) && !empty($backup)) {
			foreach ($backup as $key => $item) {
				$product_id = isset($item['product_id']) ? (int)$item['product_id'] : 0;
				$variation_id = isset($item['variation_id']) ? (int)$item['variation_id'] : 0;
				$quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
				$variation = isset($item['variation']) && is_array($item['variation']) ? $item['variation'] : array();
				$cart_item_data = isset($item['cart_item_data']) && is_array($item['cart_item_data']) ? $item['cart_item_data'] : array();
				
				if ($product_id > 0) {
					$wc->cart->add_to_cart($product_id, max(1, $quantity), $variation_id, $variation, $cart_item_data);
				}
			}
		}
	}
}

// Initialize the Login as User functionality on init hook to avoid early loading issues
function initialize_w357_login_as_user() {
    
    if (!isset($w357_login_as_user_instance)) {
        $w357_login_as_user_instance = new w357LoginAsUser();
        $w357_login_as_user_instance->run();
    }
    
    return $w357_login_as_user_instance;
}

// Hook the initialization to init
add_action('init', 'initialize_w357_login_as_user', 5);
