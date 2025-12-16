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
// Settings page
?>
<div class="wrap">
	<h1><?php echo $this->plugin_name; ?> v<?php echo $this->version; ?></h1>
    <div class="lau-settings">
        <div class="lau-about">
            <h2>
                <?php echo esc_html__( 'About', 'login-as-user' ); ?> Login as User   (Free Version)    
            </h2>

            <div style="margin-bottom: 20px; display: flex; justify-content: center; text-align: center;">
                <a href="https://www.web357.com/login-as-user-wordpress-plugin?utm_source=SettingsPage&utm_medium=ReadMoreLink&utm_content=loginasuserwp&utm_campaign=read-more" target="_blank">
                <img src="<?php echo esc_url( plugins_url( 'img', (__FILE__) ) ); ?>/login-as-user-wordpress-plugin-settings-page.png" alt="Login as User WordPress plugin by Web357" />
                </a>
            </div>

            <div style="margin-bottom: 20px;">
            <p><?php echo esc_html__( 'Login as a User is a powerful WordPress plugin that lets you instantly access any user’s account with just one click — no password needed. Perfect for support, QA, and admin teams, it allows you to see exactly what your users see, making issue resolution faster and easier. Seamlessly integrated with WooCommerce and supporting role-based restrictions, it ensures both flexibility and security. Say goodbye to guesswork and endless email threads, support users in real time, from their exact perspective.', 'login-as-user' ); ?> <a href="https://www.web357.com/login-as-user-wordpress-plugin?utm_source=SettingsPage&utm_medium=ReadMoreLink&utm_content=loginasuserwp&utm_campaign=read-more" target="_blank"><?php echo esc_html__( 'Read more &raquo;', 'login-as-user' ); ?></a></p>
            </div>

            <div class="lau-free-vs-pro" style="margin-top: 20px;">
            <hr> 
                <h4>Unlock Premium Features with Login as User Pro</h4>
                 
                <p>Enhance your WordPress site management with premium features available only in the Pro version of the Login as User plugin. Upgrade to gain advanced capabilities and superior control for seamless administration.</p>
                 
                
                <table>
                    <tr>
                        <th>Features</th>
                          <th>Free</th> 
                        <th>Pro</th>
                    </tr>
                    <tr>
                        <td class="lau-feature-info">
                            <div class="lau-feature-title">Display the Login as User in All Users Page in Admin</div>
                            <div class="lau-feature-desc">In the Admin area, you select a user from the list and click the ‘Login as User’ link to switch to that user.</div>
                        </td>
                         <td><span class="lau-icon lau-icon-tick"></span></td> 
                        <td><span class="lau-icon lau-icon-tick"></span></td>
                    </tr>
                    <tr>
                        <td class="lau-feature-info">
                            <div class="lau-feature-title">User’s Profile Page</div>
                            <div class="lau-feature-desc">Are you in a user’s profile and want to login as this user? Just click the button Login as:… at the top left-hand side and you will be able to check data and help this specific user with any problem.</div>
                        </td>
                         <td><span class="lau-icon lau-icon-tick"></span></td> 
                        <td><span class="lau-icon lau-icon-tick"></span></td>
                    </tr>
                    <tr>
                        <td class="lau-feature-info">
                            <div class="lau-feature-title">View WooCommerce Orders Page</div>
                            <div class="lau-feature-desc">Are you using the WooCommerce plugin? In the WooCommerce orders page, the Login as user button appears besides each customer to help you provide better customer support.</div>
                        </td>
                         <td><span class="lau-icon lau-icon-x"></span></td> 
                        <td><span class="lau-icon lau-icon-tick"></span></td>
                    </tr>
                    <tr>
                        <td class="lau-feature-info">
                            <div class="lau-feature-title">Check WooCommerce Order Details</div>
                            <div class="lau-feature-desc">Is one of your customers having trouble with their order? Do you want to check the details of a customer’s order? You can easily check the customer’s problem from his/her perspective by switching with the Login as User button in the WooCommerce order details page.</div>
                        </td>
                         <td><span class="lau-icon lau-icon-x"></span></td> 
                        <td><span class="lau-icon lau-icon-tick"></span></td>
                    </tr>
                    <tr>
                        <td class="lau-feature-info">
                            <div class="lau-feature-title">Full View of the WooCommerce Subscriptions Page</div>
                            <div class="lau-feature-desc">The Login as User button of each subscriber appears next to their name in the WooCommerce Subscriptions Page. Just click on it to switch.</div>
                        </td>
                         <td><span class="lau-icon lau-icon-x"></span></td> 
                        <td><span class="lau-icon lau-icon-tick"></span></td>
                    </tr>
                    <tr>
                        <td class="lau-feature-info">
                            <div class="lau-feature-title">WooCommerce Subscription Details Page</div>
                            <div class="lau-feature-desc">You can easily control every subscriber’s data by switching accounts on the WooCommerce subscription details page. You simply click the Login as User button displayed at the right sidebar as a metabox to see the subscriber’s details and make any changes necessary.</div>
                        </td>
                         <td><span class="lau-icon lau-icon-x"></span></td> 
                        <td><span class="lau-icon lau-icon-tick"></span></td>
                    </tr>
                    <tr>
                        <td class="lau-feature-info">
                            <div class="lau-feature-title">Shortcode for “Login as User”</div>
                            <div class="lau-feature-desc">The &#91;login_as_user&#93; shortcode allows you to add a "Login as User" button to any post, page, or widget on your WordPress site. This feature facilitates easy and direct login as a specific user, which is particularly useful for administrators who need to quickly view or manage the site from another user's perspective. Learn more <a target="_blank" href="https://docs.web357.com/article/102-shortcode-login-as-user">here</a>.</div>
                        </td>
                         <td><span class="lau-icon lau-icon-x"></span></td> 
                        <td><span class="lau-icon lau-icon-tick"></span></td>
                    </tr>
                    <tr>
                        <td class="lau-feature-info">
                            <div class="lau-feature-title">Role Management Permissions in Login as User Plugin</div>
                            <div class="lau-feature-desc">Define which roles can log in as users of other roles, enhancing security and control by limiting this capability to specific roles. Learn more <a target="_blank" href="https://docs.web357.com/article/118-role-management-permissions-in-login-as-user-plugin-pro-only">here</a>.</div>
                        </td>
                         <td><span class="lau-icon lau-icon-x"></span></td> 
                        <td><span class="lau-icon lau-icon-tick"></span></td>
                    </tr>
                </table>

                
                 
                <div class="lac-buy-pro-btn-container">
                    <a href="https://www.web357.com/login-as-user-wordpress-plugin?utm_source=SettingsPage&utm_medium=BuyProLink&utm_content=loginasuserwp&utm_campaign=upgrade-pro" class="button lac-buy-pro-btn" target="_blank">Upgrade to PRO</a>
                </div>
                 
            </div>

            <div style="margin-top: 20px;">
            <hr> 
                <h4><?php echo esc_html__( 'Need support?', 'login-as-user'); ?></h4>
                <?php
                echo sprintf(
                    __( '<p>If you are having problems with this plugin, please <a href="%1$s" target="_blank">contact us</a> and we will reply as soon as possible.</p>', 'login-as-user' ),
                    esc_url( 'https://www.web357.com/support/?utm_source=SettingsPage&utm_medium=SupportLink&utm_content=loginasuserwp&utm_campaign=support-link' )
                );
                ?>
            </div>

            <div style="margin-top: 20px;" class="lac-developed-by">
            <hr> 
                <span><?php echo __('Developed by', 'login-as-user'); ?></span>
                <a href="<?php echo esc_url('https://www.web357.com/?utm_source=SettingsPage&utm_medium=LogoLink&utm_content=loginasuserwp&utm_campaign=logo-link'); ?>" target="_blank">
                    <img src="<?php echo esc_url( plugins_url( 'img', (__FILE__) ) ); ?>/web357-logo.png" alt="Web357 logo" />
                </a>
            </div>

        </div>
        <div class="lau-form">
            <h2>
                <?php echo esc_html__( 'How it works?', 'login-as-user' ); ?>
            </h2>
            <?php echo wp_kses( __( '<p style="color:red">You have to navigate to the <a href="users.php"><strong>Users page</strong></a> and then you will see a button with the name "<strong>Login as: `username`</strong>", at the right side of each username. If you click on this button you will login at the front-end of the website as this User.</p>', 'login-as-user' ), array( 'strong' => array(), 'br' => array(), 'p' => array(), 'a' => array('href'=>array()) ) ); ?>
            <div class="wrap">

                <h2 class="nav-tab-wrapper">
                    <a href="?page=login-as-user&tab=settings" class="nav-tab <?= $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e('Settings', 'login-as-user'); ?>
                    </a>
                    <a href="?page=login-as-user&tab=appearance" class="nav-tab <?= $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e('Appearance', 'login-as-user'); ?>
                    </a>
                </h2>

                <?php if ($active_tab == 'appearance'): ?>
                    <div>
                        <ul class="subsubsub">
                            <li>
                                <a href="?page=login-as-user&tab=appearance&subtab=login-button" class="subtab <?= $active_subtab == 'login-button' ? 'current' : ''; ?>">
                                    <?php esc_html_e('Login Button', 'login-as-user'); ?>
                                </a>
                            </li>
                            <li>|</li>
                            <li>
                                <a href="?page=login-as-user&tab=appearance&subtab=frontend-bar" class="subtab <?= $active_subtab == 'frontend-bar' ? 'current' : ''; ?>">
                                    <?php esc_html_e('Message', 'login-as-user'); ?>
                                </a>
                            </li>
                            
                        </ul>
                    </div>
                    <br class="clear">
                <?php endif; ?>

                <form method="post" action="options.php">
                    <?php
                    settings_fields('login-as-user');

                    if ($active_tab == 'settings') {
                        do_settings_sections('login-as-user-settings');
                    } else {
                        do_settings_sections('login-as-user-appearance-'.$active_subtab);
                    }

                    submit_button(esc_html__('Save Settings', 'login-as-user'));
                    ?>
                </form>
            </div>
        </div>
    </div>
</div>