<?php

if (!defined('WPINC')) {
    die;
}

class LoginAsUser_SureCart_Integration extends LoginAsUser_Integration_Abstract
{

    public function init()
    {
        /* orders list view */
        add_filter('manage_sc-orders_columns', [$this, 'loginasuser_col'], 10, 2);
        add_filter('manage_sc-orders_custom_column', [$this, 'loginasuser_col_content_order'], 10, 2);

        /* customers list view */
        add_filter('manage_sc-customers_columns', [$this, 'loginasuser_col'], 10, 2);
        add_filter('manage_sc-customers_custom_column', [$this, 'loginasuser_col_content'], 10, 2);
    }

    /**
     * @param $columns
     * @return array
     */
    public function loginasuser_col($columns)
    {
        $columns['loginasuser_col'] = __('Login as User', 'login-as-user');
        return $columns;
    }

    /**
     * @param string $column
     * @param \SureCart\Models\Order $orderDB
     * @return void
     */
    public function loginasuser_col_content_order($column, $orderDB)
    {
        $this->loginAsUserButtonGenerator->messages['noUserAvailable'] = __('No user exists for this order.', 'login-as-user');
        $this->loginasuser_col_content($column, isset($orderDB->checkout->customer) ? $orderDB->checkout->customer : null);
    }

    /**
     * @param string $column
     * @param \SureCart\Models\Customer $customerDB
     * @return void
     */
    public function loginasuser_col_content($column, $customerDB)
    {
        if ('loginasuser_col' === $column) {
            

             
            echo $this->main->onlyInProTextLink();
             
        }
    }

}