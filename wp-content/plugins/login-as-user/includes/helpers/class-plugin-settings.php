<?php

class LoginAsUser_Plugin_Settings
{
    /** @var string */
    public $loginRedirectUrl = '';

    /** @var string */
    public $logoutRedirectUrl = '';

    /** @var string */
    public $loginButtonDisplayAs = 'user_login';

    /** @var int */
    public $loginAsTypeCharactersLimit = 0;

    /** @var string */
    public $messageDisplayPosition = 'top';

    /** @var bool */
    public $showAdminLinkInTopbar = true;

    /** @var bool */
    public $enableAttentionAnimation = true;

    /** @var bool */
    public $preserveWooCart = false;

    
    /** @var string */
    public $licenseKey = '';

    /** @var string */
    public $roleManagementAssignments = [];

    /** @var array[] */
    public $listConfiguration = [
        'w357-users-list' => [
            'label' => 'Users List',
            'buttonPosition' => '',
            'buttonSize' => ''
        ],
        'woocommerce' => [
            'label' => 'WooCommerce',
            'buttonPosition' => '',
            'buttonSize' => ''
        ],
        'woocommerce-subscriptions' => [
            'label' => 'WooCommerce Subscriptions',
            'buttonPosition' => '',
            'buttonSize' => ''
        ],
    ];

    protected static $isPluginActive = [
        'w357-users-list' => true,
        'woocommerce' => false,
        'woocommerce-subscriptions' => false,
        'memberpress' => false,
        'login-as-user-pro' => false,
        'surecart' => false,
    ];

    public function __construct()
    {
        foreach (static::$isPluginActive as $pluginName => $pluginStatus) {
            static::$isPluginActive[$pluginName] = is_plugin_active($pluginName . '/' . $pluginName . '.php');
        }
        static::$isPluginActive['w357-users-list'] = true;

        $options = (array)get_option('login_as_user_options');
        $this->loginRedirectUrl = $options['redirect_to'] ?? '';
        $this->logoutRedirectUrl = !empty($options['logout_redirect_url']) ? home_url('/' . $options['logout_redirect_url']) : '';
        $this->loginButtonDisplayAs = $options['login_as_type'] ?? 'user_login';
        $this->loginAsTypeCharactersLimit = (int)($options['login_as_type_characters_limit'] ?? 0);
        $this->messageDisplayPosition = $options['message_display_position'] ?? 'bottom';
        $this->showAdminLinkInTopbar = !empty($options['show_admin_link_in_topbar']) && $options['show_admin_link_in_topbar'] === 'yes';
        $this->enableAttentionAnimation = !empty($options['enable_ping_animation']) && $options['enable_ping_animation'] === 'yes';
        $this->preserveWooCart = !empty($options['preserve_wc_cart_on_switch']) && in_array('yes', (array)$options['preserve_wc_cart_on_switch'], true);
        
        $this->licenseKey = $options['license_key'] ?? '';
        $this->roleManagementAssignments = $options['role_management_assignments'] ?? [];
    }

    public function isPluginActive(string $pluginName): bool
    {
        if (!array_key_exists($pluginName, static::$isPluginActive)) {
            throw new \Exception('Plugin has not name');
        }
        return static::$isPluginActive[$pluginName];
    }
}
