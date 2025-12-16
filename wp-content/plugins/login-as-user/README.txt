=== Login as User ===
Contributors: yiannistaos, johnbillion
Tags: user, login, admin, login as user, web357
Donate link: https://www.paypal.me/web357
Requires at least: 5.3
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.6.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Login as User is a free WordPress plugin that helps admins switch user accounts instantly to check data.

== Description ==
[Login as a User WordPress plugin](https://www.web357.com/login-as-user-wordpress-plugin?utm_source=wp_plugin_repo&utm_medium=wp_listing&utm_campaign=wp_repo_link&utm_content=wp_repo_link_to_plugin_page) **allows admins to have easy access to the frontend as a specific user** and thus solve problems or provide better and faster customer support. **With one click**, the admin logs in as the external user or customer and handles any situation without wasting any time at all. If you want a WordPress plugin to switch accounts in an instant, Login as User is for you.

👉 [Live Demo](https://login-as-user-wordpress-demo.web357.com/wp-admin/)
`
    Username: demo_admin
    Password: demo_password
`

== Video Description ==

[youtube https://www.youtube.com/watch?v=IJa-MnM6RaU]

== Basic Features ==
- In the Admin area, you select Users in the left-hand side menu and click All Users in the sub-menu. Now, all users of your website appear on the screen along with the Login as... button besides each name. You can click the button of the user you want to switch account.
- Are you in a user’s profile and want to login as this user? Just click the button Login as:... at the top left-hand side and you will be able to check data and help this specific user with any problem.
- **Positioning**: You can choose the position of the "Login as user" toolbar. There are two available positions, the top and the bottom.
- **Multisite support**: Allows administrators to log in as any user across the network without needing SuperAdmin privileges. 
- Compatible with the "User Insights" WordPress plugin. You just have to add a custom field with the name "loginasuser".

== PRO Features ==
- **WooCommerce Integration**: Are you using the WooCommerce plugin? In the orders page, the Login as user button appears besides each customer to help you provide better customer support.
- **WooCommerce Subscriptions Integration**: Is one of your customers having trouble with their order? Do you want to check the details of a customer’s order? You can easily check the customer’s problem from his/her perspective by switching with the Login as User button in the WooCommerce order details page.
- **[Role Management Permissions](https://docs.web357.com/article/118-role-management-permissions-in-login-as-user-plugin-pro-only?utm_source=wp_plugin_repo&utm_medium=wp_listing&utm_campaign=wp_repo_link&utm_content=wp_repo_link_to_plugin_page)**: The "Role Management Permissions" parameter in the "Login as User" plugin allows administrators to define which roles have the ability to manage (log in as) users of other roles. This feature enhances security and control by limiting the login-as-user capability to specific roles.
- **[Shortcode](https://docs.web357.com/article/102-shortcode-login-as-user?utm_source=wp_plugin_repo&utm_medium=wp_listing&utm_campaign=wp_repo_link&utm_content=wp_repo_link_to_plugin_page)**: You can use the shortcode [login_as_user user_id="357"] to display the login as user button everywhere, event at the frontend. You just have to specify the user ID in the attribute.

== Installation ==
The plugin is simple to install:

1. Download the file `login-as-user.zip`.
2. Unzip it.
3. Upload `login-as-user` directory to your `/wp-content/plugins` directory.
4. Go to the plugin management page and enable the plugin.
5. Configure the options from the `Settings > Login as User` page

== Frequently Asked Questions ==
= Why would I want to use this plugin? =

This plugin is helpful if you are an admin and you want to sign in as any user, or if you would like to check and confirm if the users see the correct data into their account page. This plugin prevent admins to ask for login details (username and password).

= Is it safe to use this plugin on my website? =
Yes, the "Login as User" plugin is designed with security in mind. It only allows administrators with the appropriate permissions to log in as other users. No user passwords are exposed or required, ensuring that sensitive information remains secure.

= Will the user know if I log in as them? =
By default, users will not be notified if an admin logs in as them using this plugin. However, it's always best practice to inform users if you plan to access their accounts to maintain transparency and trust.

= Can I log in as users with different roles (e.g., subscriber, editor, etc.)? =
Yes, the "Login as User" plugin allows administrators to log in as any user, regardless of their role. This flexibility makes it easy to troubleshoot and support users across different levels of access.

= Does this plugin work with third-party plugins and custom user roles? =
Yes, "Login as User" is designed to be compatible with most third-party plugins and custom user roles. It integrates seamlessly with the WordPress user management system, so you can log in as users created by other plugins or with custom roles.

== Screenshots ==
1. In the Admin area, you select Users in the left-hand side menu and click All Users in the sub-menu. Now, all users of your website appear on the screen along with the Login as… button besides each name. You can click the button of the user you want to switch account.
2. Are you in a user’s profile and want to login as this user? Just click the button Login as:… at the top left-hand side and you will be able to check data and help this specific user with any problem.
3. Are you using the WooCommerce plugin? In the WooCommerce orders page, the Login as user button appears besides each customer to help you provide better customer support.
4. Is one of your customers having trouble with their order? Do you want to check the details of a customer’s order? You can easily check the customer’s problem from his/her perspective by switching with the Login as User button in the WooCommerce order details page.
5. Settings.

== Changelog ==
= 04-Dec-2025 : v1.6.7 =
* Fix: Resolved issue causing thousands of audit log entries per second by checking role capabilities directly instead of creating temporary WP_User objects with set_role() calls
* Fix: Deprecated PHP warnings. Updated method signatures to use nullable type hints (e.g., ?WP_User, ?Update) in login and update checker classes. This improves code clarity and compatibility with modern PHP standards.
* Fix: preserve role capability settings across tab saves. Fix roleCapabilityCheckboxField to use saved database values instead of actual WordPress capabilities for checkbox state, and prevent roles_with_edit_users_capability from being reset when saving from Appearance tab. Resolves issue where SureCart roles would appear unchecked despite being saved in database.

= 25-Oct-2025 : v1.6.6 =
* New: Simple setting to control which roles can edit users. We also made role handling consistent so the plugin always uses the correct list of roles.
* Improved: More reliable multi-select on the settings page when choosing roles. We cleaned up how these selections are saved to avoid issues.
* Updated: New Web357 logo on the settings page.

= 03-Oct-2025 : v1.6.5 =
* Bug Fixed: The "Go back to admin..." is not displayed after logging in as a user.  
* Manually clear WordPress authentication cookies without triggering clear_auth_cookie action. This prevents conflicts with other plugins (e.g. WP Activity Log) that hook into the action.

= 17-Sep-2025 : v1.6.4 =
* By default, set the Message Display Position to the bottom instead of top, but you can always change it from the Settings > Login as User > Appearance > Message > Message Display Position > Top or Bottom.

= 12-Sep-2025 : v1.6.3 =
* Remove debug logging for WooCommerce cart preservation.

= 08-Sep-2025 : v1.6.2 =
* Added WooCommerce Cart Preservation, ensuring customer carts remain intact when admins log in as users — preventing lost items and improving checkout support. More: https://docs.web357.com/article/161-woocommerce-cart-preservation-keep-customer-carts-intact
* Fixed invalid HTML output by moving the filter_login_message hook from wp_head to wp_footer, preventing layout issues caused by injected markup inside the head element.
* Minor fixes and improvements

= 27-May-2025 : v1.6.1 =
* Feature: Add Column Position Settings for "Login as User" buttons in WooCommerce Orders, Subscriptions, and Users lists. Allows display in default or last column.
* Feature: Add CSS Editor Settings for Frontend and Backend. Allows custom CSS overrides for the login button and toolbar without editing plugin files.
* Feature: Integrate "Login as User" with Surecart. Adds login button support in Surecart Orders and Customers views.
* Feature: Replace verbose login status messages with icons and tooltips in WooCommerce Orders Table. Enhances layout and improves readability.
* Feature: Add custom redirect URL after logging out from "Login as User" session. Allows admins to define where to be redirected post-impersonation.
* Refactor: Improve Role-Based Login Permissions Logic. Enforces stricter permission checks and prevents unauthorized role impersonation.
* Fix: Improve Compatibility with Tutor LMS Login Flow. Prevents forced logout and broken redirects when logging in as users with limited roles under Tutor LMS.
* Fix: Prevent conflict when both free and pro versions are active. Displays admin notice and avoids critical functionality issues.
* Fix: Resolve conflict with LoginWP plugin in Login as User (WordPress). Ensures session does not revert to admin after impersonation.
* Fix: Conflict with ASE plugin on redirect after using "Login as User". Resolves broken login redirection when ASE is active.
* Bug: Fix column width behavior for "Login as User" in WooCommerce Orders Table. Ensures layout is responsive and doesn't break with more columns or smaller screens.

= 03-Apr-2025 : v1.6.0 =
* Added: New setting "Enable Attention Animation" to allow turning off the pulsing (ping) effect on the admin button after logging in as a user. Disabled by default for a cleaner interface. [Docs »](https://docs.web357.com/article/144-how-to-enable-or-disable-the-attention-animation-in-the-admin-button)

= 29-Jan-2025 : v1.5.9 =
* Fix callback method login_as_user_metabox in WooCommerce Subscriptions integration

= 28-Jan-2025 : v1.5.8 =
* Added: Improved integrations architecture by moving integrations to separate files.
* Added: Full integration with MemberPress plugin. Now you can login as any MemberPress user. The buttons are displayed in the Members, Subscriptions and Transactions pages in WordPress Admin panel.
* Minify Existing *.min.css and *.min.js Files for Free and Pro Versions.
* Minor fixes and improvements.

= 04-Jan-2025 : v1.5.7 =
* Fully compatible with WordPress v6.7.x
* Fully compatible with WooCommerce v9.5.x
* Introduce the new demo site: https://login-as-user-wordpress-demo.web357.com/wp-admin

= 17-Oct-2024 : v1.5.6 =
* Fixed: PHP notices due to accessing properties of a non-object in certain cases when there is no logged-in user. Improved validation of the current user object before accessing display_name and user_login properties.
* Minor stability and performance improvements.

= 03-Oct-2024 : v1.5.5 =
* PHP message: PHP Warning: Undefined variable  in /wp-content/plugins/login-as-user/includes/class-w357-login-as-user.php on line 667
* Other Minor fixes and improvements.

= 02-Oct-2024 : v1.5.4 =
* Optimized Asset Loading: Prevent CSS and JavaScript files from being enqueued when no user is logged into the site, improving performance. (Thanks to "ss88_uk" for the suggestion!)
* Update: Removed the includes/plugin-update-checker folder from the free version.
* Fully compatible with WooCommerce v9.3.x.
* Role-Based Login Management: Added functionality to define which user roles have the ability to log in as users of other roles. For example, Administrators can be configured to log in as any user with the roles of Customer or Shop Manager, while Office Managers can log in as users with the Author role. [Pro version]
* Minor stability and performance improvements.

= 19-Aug-2024 : v1.5.3 =
* Fully compatible with WordPress v6.6.x
* Fully compatible with WooCommerce v9.1.x

= 01-Jul-2024 : v1.5.2 =
* [Bug Fixed] PHP Fatal error: Uncaught Error: Call to undefined method WP_Post::get_customer_id() in /wp-content/plugins/login-as-user-pro/includes/class-w357-login-as-user.php:796
        
= 28-Jun-2024 : v1.5.1 =
* Fully compatible with WooCommerce v9.0.x and WooCommerce Subscriptions v6.4.x
* [New Feature]: Role Management Permissions in Login as User Plugin. Define which roles can log in as users of other roles, enhancing security and control by limiting this capability to specific roles. Learn more https://docs.web357.com/article/118-role-management-permissions-in-login-as-user-plugin-pro-only
* [New Feature]: Added option to display the admin link in the topbar.
* Fixed 'Login as User' column compatibility with WooCommerce Subscriptions 6.4.
* [Bug Fix] Enhanced order column compatibility using WooCommerce's get_customer_id method.
* [UI] Implemented minor fixes and improvements for a smoother user experience, including a go-back link in the WordPress top navbar.
* Added button_name parameter to [login_as_user] shortcode for customizable button text, including support for dynamic user type placeholder  (e.g., [ login_as_user user_id="357" redirect_to="/my-account" button_name="Login as "]). [Thank you, Giorgos Iordanidis] Documentation updated: https://docs.web357.com/article/102-shortcode-login-as-user
* [Improvement] The default value for the message_display_position should be "bottom", not "top".
* Minor fixes and Improvements.

= 26-Apr-2024 : v1.5.0 =
* Improved handling of user_id and redirect_to parameters in the [login_as_user] shortcode for enhanced functionality. Documentation updated: https://docs.web357.com/article/102-shortcode-login-as-user

= 18-Apr-2024 : v1.4.9 =
* Fully compatible with WordPress v6.5.x and WooCommerce v8.8.x
* CSS Bug fixed: The "Login as User" button is currently overflowing its column within the table. [Thank you, Anthony Grullon]
* Bug fixed: After clicking the button “go back to admin as..” a 404 error occurred. It does not give the correct URL. It mostly happens when the WordPress is in a subdirectory [Many thanks to )]
* Introduced a new attribute for the [login_as_user] shortcode, enabling administrators to redirect users to a specific page after logging in. Example usage: [login_as_user user_id="1" redirect_to="/my-account"].
* Now supports WooCommerce High-Performance Order Storage (HPOS) [Thank you, Rein Ridder]
* Ensured functionality remains intact when WordPress is given its own directory, following the guidelines provided https://wordpress.org/documentation/article/giving-wordpress-its-own-directory/ [Thanks, James]
* Implemented minor fixes and improvements for a smoother user experience.

= 23-Oct-2023 : v1.4.8 =
* Fully compatible with WordPress v6.3.x and WooCommerce v8.2.x
* Minor fixes and improvements

= 27-Jul-2023 : v1.4.7 =
* Added PHP 8.2 Compatibility to the Plugin Update Checker and fix deprecation notices regarding PHP 8.2.x.
* Fix CSS issues regarding z-index on Divi themes.

= 26-Jul-2023 : v1.4.6 =
* [PHP 8.2 Deprecated Warning]: Creation of dynamic property LoginAsUser_AdminPro:: is deprecated in /wp-content/plugins/login-as-user-pro/admin/class-admin.php on line 51 on PHP 8.2, and WordPress 6.2.2.

= 07-Jun-2023 : v1.4.5 =
* [Compatibility] Fully compatible with WordPress v6.2.x and WooCommerce v7.7.x
* Compatible with the WooCommerce Mobile App
* After login as a user, in the notification bar show the email instead of username. Example: "go back to admin as Yiannis Christodoulou (yiannis [@] web357 [.] com)"
* Bug fixed after going back to admin dashboard. Bug message: "The link you followed as expired." 
* PHP Warning fixed: Undefined array key "SERVER_NAME" in .../wp-content/plugins/login-as-user-pro/login-as-user-pro.php on line 67
* Minor fixes and improvements

= 14-Jun-2022 : v1.4.4 =
* Minor bugfix: The "Login as User" button is missing for specific user roles.

= 14-Jun-2022 : v1.4.3 =
* [Compatibility] Fully compatible with WordPress v6.0 and WooCommerce v6.5+
* Minor fixes and improvements

= 11-Feb-2022 : v1.4.2 =
* [New Feature] You can use the shortcode [login_as_user user_id="357"] to display the login as user button everywhere, event at the frontend. You just have to specify the user_id. Do not forget to replace the 357 with the user ID you want. (This feature included only in the Premium version)
* [Compatibility] Fully compatible with WordPress v5.9 and WooCommerce v6.2
* Minor fixes and improvements

= 28-Jul-2021 : v1.4.1 =
* [Styling improvement] To fix the display of the login as user button when you have a lot of columns in Users/Orders area, we 've created a new option to decrease the width of "Login as User" column. Navigate to: Settings > Login as User > "Login as...«option»" button	> None (display only the user icon). [Thank you, Robert]
* [Compatibility] Fully compatible with WordPress v5.8 and WooCommerce v5.5
* Minor fixes and improvements

= 08-Apr-2021 : v1.4.0 =
* [Compatibility] Fully compatible with the User Insights WordPress plugin.
* [Compatibility] Fully compatible with WordPress v5.7 and WooCommerce v5.1.x
* Minor fixes and improvements...

= 09-Jan-2021 : v1.3.0 =
* [Compatibility] Fully compatible with Loco Translate plugin.
* [New feature] Validate website license key in the plugin settings  (only for the premium version).
* [Compatibility] Fully compatible with WordPress v5.6 and WooCommerce v4.8.

= 19-Oct-2020 : v1.2.2 =
* [New option] You can now choose the position of the "Login as user" toolbar. There are two available positions, the TOP and the BOTTOM.
* [Compatibility] Fully compatible with WordPress 5.5 and WooCommerce 4.6.

= 15-Apr-2020 : v1.2.1 =
* [Bug Fixed] Error with the redirection URL after a successful login attempt. Admin is redirected to a URL that contains twice the value of the function "home_url()", and gives a 404 error.

= 30-Mar-2020 : v1.2.0 =
* [Style Improvement] Remove any margin of the button that displayed on the toolbar at frontend.
* [Bug Fixed] The "login as user" button is not displayed. There was a filter conflict because a 3rd plugin calls the "manage_users_custom_column" filter too. We have change the priority and the issue has been resolved. [Many thanks to Michael Kuhlman for his help]
* [Bug Fixed] Error with the redirection URL. Admin is not redirected to the correct page from settings, after logged in as a user.

= 14-Feb-2020 : v1.1.0 =
* [New feature] You can now choose from the settings, which string will be displayed on the "Login as User" button. You can choose one from the following, nickname, or first name, or last name, or full name. For example Login as «Yiannis», or log in as «Christodoulou», or log in as «Johnathan99», or log in as «Yiannis Christodoulou».
* [New Feature] Show only the first X characters of the username, or first/last name, or full name, on the "Login as...«option»" button. For example, if you choose 5, the button will be displayed like this: Login as «Yiann...», or Login as «Chris...», or Login as «Johna...», or Login as «Yiann...».
* [Bug Fixed] Do not load the files "public.min.css" and "public.min.js" if the Login as User functionality is not enabled.
* Minor bug fixes and improvements.

= 29-Jul-2019 : v1.0.1 =
* If an admin is already logged in, a short message replaces the button. Example: "Already logged in" instead of "---".
* The language files have been updated with new strings.

= 11-Jul-2019 : v1.0.0 =
* First beta release