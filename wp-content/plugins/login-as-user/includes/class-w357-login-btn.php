<?php

class LoginAsUserButton_Generator
{
    /** @var w357LoginAsUser */
    protected $main;

    /** @var bool */
    protected $displayAsIcon = false;

    /** @var string */
    public $messages = [
        'isActiveUser' => '',
        'isNotAllowed' => '',
        'noUserAvailable' => '',
    ];

    public function __construct($main)
    {
        $this->main = $main;
        $this->messages = [
            'isActiveUser' => __('You are already logged in.', 'login-as-user'),
            'isNotAllowed' => __('Could not login as this user.', 'login-as-user'),
            'noUserAvailable' => __('No user exists', 'login-as-user'),
        ];
        $options = (object)get_option('login_as_user_options');
        $this->displayAsIcon = !empty($options->login_as_type) && $options->login_as_type == 'only_icon';
    }

    /**
     * @param int|null $userId
     * @param array $params "column" displays icon if its selected settings or "other" displays text always (useful for metaboxes and inner user pages)
     * @return string
     */
    public function generateButton(?int $userId, array $params = []): string
    {
        $params = wp_parse_args($params, [
            'scope' => 'column',
            'additionalButtonClass' => '',
        ]);

        if (!in_array($params['scope'], ['metabox', 'column'])) {
            throw new \InvalidArgumentException('Invalid scope provided. Allowed values are: default, metabox, column.');
        }

        $userDB = $userId ? new \WP_User($userId) : null;

        $return = [];
        if (!$userDB || !$userDB->ID) {
            $return = [
                'icon' => 'dashicons dashicons-info-outline',
                'text' => $this->messages['noUserAvailable'],
            ];
        } elseif (get_current_user_id() === $userDB->ID) {
            $return = [
                'icon' => 'dashicons dashicons-saved',
                'text' => $this->messages['isActiveUser'],
            ];
        } elseif (!current_user_can('login_as_user', $userId)) {
            $return = [
                'icon' => 'dashicons dashicons-remove',
                'text' => $this->messages['isNotAllowed'],
            ];
        }

        if ($return) {
            if ($params['scope'] === 'column' && $this->displayAsIcon) {
                return '<span class="web357-login-as-user-msg"><span class="web357-tooltip" tabindex="0" aria-label="' . esc_attr($return['text']) . '"><span class="' . esc_attr($return['icon']) . '"></span><span class="tooltip-text">' . esc_html($return['text']) . '</span></span></span>';
            } else {
                return '<span class="web357-login-as-user-msg web357-no-padding">' . $return['text'] . '</span>';
            }
        }

        $loginAsUserUrl = $this->main->build_the_login_as_user_url($userDB);
        $btnClass = esc_attr(trim('button w357-login-as-user-btn ' . ($params['scope'] === 'metabox' ? 'w357-login-as-user-metabox-btn ' : '') . $params['additionalButtonClass']));
        if ($params['scope'] === 'column' && $this->displayAsIcon) {
            return '<a class="' . $btnClass . '" href="' . esc_url($loginAsUserUrl) . '" title="' . esc_html__('Login as', 'login-as-user') . ': ' . $this->main->login_as_type($userDB, false) . '"><span class="dashicons dashicons-admin-users"></span></a>';
        } else {
            return '<a class="' . $btnClass . '" href="' . esc_url($loginAsUserUrl) . '" title="' . esc_html__('Login as', 'login-as-user') . ': ' . $this->main->login_as_type($userDB, false) . '"><span class="dashicons dashicons-admin-users"></span> ' . esc_html__('Login as', 'login-as-user') . ': <strong>' . $this->main->login_as_type($userDB) . '</strong></a>';
        }
    }

}
