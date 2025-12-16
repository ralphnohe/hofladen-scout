<?php

if (!defined('WPINC')) {
    die;
}

class LoginAsUser_WooCommerce_Integration extends LoginAsUser_Integration_Abstract
{

    public function init()
    {
        /* High Performance Order Storage */
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'loginasuser_col'], 1000);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'loginasuser_col_content_hp'], 10, 2);

        /* Default */
        add_filter('manage_edit-shop_order_columns', [$this, 'loginasuser_col'], 1000);
        add_action('manage_shop_order_posts_custom_column', [$this, 'loginasuser_col_content'], 10, 1);

        /* User metabox */
        add_action('add_meta_boxes', [$this, 'add_login_as_user_metabox']);
        $this->loginAsUserButtonGenerator->messages['noUserAvailable'] = __('No user exists for this order.', 'login-as-user');
    }

    public function loginasuser_col($columns)
    {
        $new_columns = [];
        if (w357LoginAsUser::$pluginSettings->listConfiguration['woocommerce']['buttonPosition'] === 'last') {
            $new_columns = $columns;
            $new_columns['loginasuser_col'] = __('Login as User', 'login-as-user');
        } elseif (w357LoginAsUser::$pluginSettings->listConfiguration['woocommerce']['buttonPosition']) {
            $i = 0;
            foreach ($columns as $column_name => $column_info) {
                if ($column_name !== 'cb' && (++$i === w357LoginAsUser::$pluginSettings->listConfiguration['woocommerce']['buttonPosition'])) {
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
                if ('order_number' === $column_name || 'order_title' === $column_name) {
                    $new_columns['loginasuser_col'] = __('Login as User', 'login-as-user');
                }
            }
        }

        return $new_columns;
    }

    public function loginasuser_col_content($column)
    {
        if ('loginasuser_col' === $column) {
            global $post;
            $order = wc_get_order($post->ID);
            echo $this->loginasuser_col_content_hp($column, $order);
        }
    }

    public function loginasuser_col_content_hp($column, $orderDB)
    {
        if ('loginasuser_col' === $column) {
            

             
            echo $this->main->onlyInProTextLink();
             
        }
    }

    public function add_login_as_user_metabox()
    {
        add_meta_box('login_as_user_metabox', __('Login as User'), [$this, 'login_as_user_metabox'], 'shop_order', 'side', 'core');
        add_meta_box('login_as_user_metabox', __('Login as User'), [$this, 'login_as_user_metabox'], 'woocommerce_page_wc-orders', 'side', 'core');
    }

    public function login_as_user_metabox($post)
    {
        

         
        echo $this->main->onlyInProTextLink();
         
    }

    /**
     * Instructs WooCommerce to forget the session for the current user, without deleting it.
     *
     * @param WooCommerce $wc The WooCommerce instance.
     */
    public static function forget_woocommerce_session(WooCommerce $wc)
    {
        if (!property_exists($wc, 'session')) {
            return false;
        }

        if (!method_exists($wc->session, 'forget_session')) {
            return false;
        }

        $wc->session->forget_session();
    }
}