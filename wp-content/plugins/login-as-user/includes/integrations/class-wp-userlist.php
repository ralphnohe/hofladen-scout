<?php

if (!defined('WPINC')) {
    die;
}

class LoginAsUser_WP_Userlist_Integration extends LoginAsUser_Integration_Abstract
{

    public function init()
    {
        add_filter('manage_users_columns', [$this, 'loginasuser_col'], 1000);
        add_filter('manage_users_custom_column', [$this, 'loginasuser_col_content'], 15, 3);
        $this->loginAsUserButtonGenerator->messages['isActiveUser'] = __('It\'s me.', 'login-as-user');
    }

    public function loginasuser_col($columns)
    {
        $new_columns = [];
        if (w357LoginAsUser::$pluginSettings->listConfiguration['w357-users-list']['buttonPosition'] === 'last') {
            $new_columns = $columns;
            $new_columns['loginasuser_col'] = __('Login as User', 'login-as-user');
        } elseif (w357LoginAsUser::$pluginSettings->listConfiguration['w357-users-list']['buttonPosition']) {
            $i = 0;
            foreach ($columns as $column_name => $column_info) {
                if ($column_name !== 'cb' && (++$i === w357LoginAsUser::$pluginSettings->listConfiguration['w357-users-list']['buttonPosition'])) {
                    $new_columns['loginasuser_col'] = __('Login as User', 'login-as-user');
                }
                $new_columns[$column_name] = $column_info;
            }
            if (!isset($new_columns['loginasuser_col'])) {
                $new_columns['loginasuser_col'] = __('Login as User', 'login-as-user');
            }
        } else {
            foreach ($columns as $column_name => $column_info) {
                $new_columns[$column_name] = $column_info;
                if ('username' === $column_name || 'order_number' === $column_name || 'order_title' === $column_name) {
                    $new_columns['loginasuser_col'] = __('Login as User', 'login-as-user');
                }
            }
        }

        return $new_columns;
    }

    public function loginasuser_col_content($val, $column_name, $user_id)
    {
        if ('loginasuser_col' === $column_name) {
            $buttonClass = 'w357-login-as-user-col-btn';
            
            return $this->loginAsUserButtonGenerator->generateButton($user_id, [
                'additionalButtonClass' => $buttonClass
            ]);
        }
        return $val;
    }
}