<?php
if (!defined('WPINC')) {
    die;
}

class LoginAsUser_MemberPress_Integration extends LoginAsUser_Integration_Abstract{
    
    public function init() {

        // Members list hooks
        add_filter('mepr-admin-members-cols', array($this, 'loginasuser_col'));
        add_filter('mepr_members_list_table_row', array($this, 'loginasuser_members_cell'), 10, 4);
        
        // Subscriptions list hooks
        add_filter('mepr-admin-subscriptions-cols', array($this, 'loginasuser_col'));
        add_filter('mepr-admin-subscriptions-cell', array($this, 'loginasuser_subscriptions_cell'), 10, 4);

        // Transactions list hooks
        add_filter('mepr-admin-transactions-cols', array($this, 'loginasuser_col'));
        add_filter('mepr-admin-transactions-cell', array($this, 'loginasuser_transactions_cell'), 10, 3);
        
    }
    
    public function loginasuser_col($cols) {
        $new_cols = [];
        foreach ($cols as $key => $value) {
            $new_cols[$key] = $value;
            if ($key === 'col_email' || $key === 'col_member' || $key === 'col_user_login') {
                $new_cols['col_login_as_user'] = __('Login as User', 'login-as-user');
            }
        }
        return $new_cols;
    }

    /**
     * Common function to render login as user cell content
     * 
     * @param array|object $rec Record containing user information
     * @param string $attributes HTML attributes for the cell
     * @param string $user_identifier_type 'login' or 'id'
     * @return string HTML cell content
     */
    private function render_login_cell($rec, $attributes, $user_identifier_type = 'login')
    {
        // Get user based on identifier type
        $user = ($user_identifier_type === 'login')
            ? get_user_by('login', $rec->username)
            : get_user_by('id', $rec->user_id);

        return '<td ' . $attributes . '>' . $this->loginAsUserButtonGenerator->generateButton($user->ID ?? null) . '</td>';
    }

    public function loginasuser_transactions_cell($column_name, $rec, $attributes) {
        if ($column_name === 'col_login_as_user') {
            echo $this->render_login_cell($rec, $attributes, 'id');
        }
    }

    public function loginasuser_subscriptions_cell($column_name, $rec, $table, $attributes) {
        if ($column_name === 'col_login_as_user') {
            echo $this->render_login_cell($rec, $attributes, 'id');
        }
    }

    public function loginasuser_members_cell($attributes, $rec, $column_name, $column_display_name) {
        if ($column_name === 'col_login_as_user') {
            echo $this->render_login_cell($rec, $attributes, 'login');
        }
    }

    public function loginasuser_col_style() {
        $options = (object) get_option('login_as_user_options');
        if (!empty($options->login_as_type) && $options->login_as_type == 'only_icon') {
            $css = <<<CSS
            th#col_login_as_user {
                width: 8% !important;
            }
            CSS;
        } else {
            $css = <<<CSS
            th#col_login_as_user {
                width: 20% !important; 
            }
            CSS;
        }

        echo "<style>{$css}</style>";
        
        wp_add_inline_style('woocommerce_admin_styles', $css);
    }
}