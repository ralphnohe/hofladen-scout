<?php

if (!defined('WPINC')) {
    die;
}

class LoginAsUser_WooCommerce_Subscriptions_Integration extends LoginAsUser_Integration_Abstract
{

    public function init()
    {
        /* High Performance Order Storage */
        add_filter('woocommerce_shop_subscription_list_table_columns', [$this, 'loginasuser_col'], 1000);
        add_action('woocommerce_shop_subscription_list_table_custom_column', [$this, 'loginasuser_col_content'], 10, 2);

        /* default */
        add_filter('manage_edit-shop_subscription_columns', [$this, 'loginasuser_col'], 1000);
        add_action('manage_shop_subscription_posts_custom_column', [$this, 'loginasuser_col_content'], 10, 2);

        /* User metabox */
        add_action('add_meta_boxes', [$this, 'add_login_as_user_metabox']);
        $this->loginAsUserButtonGenerator->messages['noUserAvailable'] = __('No user exists for this order.', 'login-as-user');
    }

    public function loginasuser_col($columns)
    {
        $new_columns = [];
        if (w357LoginAsUser::$pluginSettings->listConfiguration['woocommerce-subscriptions']['buttonPosition'] === 'last') {
            $new_columns = $columns;
            $new_columns['loginasuser_col'] = __('Login as User', 'login-as-user');
        } elseif (w357LoginAsUser::$pluginSettings->listConfiguration['woocommerce-subscriptions']['buttonPosition']) {
            $i = 0;
            foreach ($columns as $column_name => $column_info) {
                if ($column_name !== 'cb' && (++$i === w357LoginAsUser::$pluginSettings->listConfiguration['woocommerce-subscriptions']['buttonPosition'])) {
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
                if ('order_title' === $column_name) {
                    $new_columns['loginasuser_col'] = __('Login as User', 'login-as-user');
                }
            }
        }

        return $new_columns;
    }

    public function loginasuser_col_content($column, $order_id)
    {
        if ('loginasuser_col' === $column) {
            

             
            echo $this->main->onlyInProTextLink();
             
        }
    }

    public function add_login_as_user_metabox()
    {
        add_meta_box('login_as_user_metabox', __('Login as User'), [$this, 'login_as_user_metabox'], 'woocommerce_page_wc-orders--shop_subscription', 'side', 'core');
    }

    public function login_as_user_metabox($post)
    {
        

         
        echo $this->main->onlyInProTextLink();
         
    }
}