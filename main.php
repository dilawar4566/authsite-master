<?php

/**
 * Plugin Name: AuthSite Master
 * Description: Enhance website security and authentication by facilitating seamless integration with Website.
 * Version: 1.0
 * Author: Dilawar Abbas
 * Author URI: https://www.github.com/
 * Text Domain: authsite-master
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

class AuthSiteMaster
{
    public function __construct()
    {
        add_action('init', array($this, 'init_hook'));
        add_action('admin_menu', array($this, 'authentication_settings_page'));
        add_action('admin_init', array($this, 'authentication_requestor'));
        add_action('admin_init', array($this, 'authentication_requestor_save_user_info'));
        add_action('init', array($this, 'authentication_login_user'));
    }

    public function init_hook()
    {
        wp_register_style('authsite_css', plugins_url('assets/css/auth_site.css', __FILE__), array(), time());
        wp_enqueue_script('jquery');
        wp_register_script('authsite_js', plugins_url('/assets/js/auth_site.js', __FILE__), array(), time());
        wp_enqueue_script('authsite_js');
        wp_localize_script('authsite_js', 'jquery_main_before', ['ajax_url' => admin_url('admin-ajax.php')]);
    }

    public function authentication_settings_page()
    {
        add_menu_page('Authentication Settings', 'Authentication', 'manage_options', 'authentication-settings', array($this, 'authentication_settings_content'));
    }

    public function authentication_settings_content()
    {
        wp_enqueue_style('authsite_css');

        include(plugin_dir_path(__FILE__) . 'template/auth_form.php');
    }

    public function authentication_requestor()
    {
        if (isset($_POST['submit'])) {
            $authentication_code = $_POST['authentication_data'];
            if ($authentication_code === '') {
                $_POST['authentication_status'] = 'failed';
                return;
            }
            update_option('user_authentication_code', $authentication_code);

            $api_key = $authentication_code;
            $site_name = get_bloginfo('name');
            $site_url = site_url();
            $core_updates = get_core_updates();
            $theme_updates = get_theme_updates();
            $plugin_updates = get_plugin_updates();

            $api_url = 'https://staging.ketre.com/api/add_site';

            $request_data = array(
                'key' => $api_key,
                'site_name' => $site_name,
                'site_url' => $site_url,
                'login_url' => $site_url . '/?token=' . $authentication_code,
                'updates' => count($core_updates),
                'theme_updates' => count($theme_updates),
                'plugin_updates' => count($plugin_updates),
                'site_backup' => '1',
                'risks' => '1',
                'issues' => '1',
                'google_speed' => '1',
                'page_speed' => '1',
            );

            $response = wp_safe_remote_post($api_url, array(
                'body' => json_encode($request_data),
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
            ));

            if (is_wp_error($response)) {
                $_POST['authentication_status'] = 'failed';
                exit;
            } else {
                $response_body = json_decode(wp_remote_retrieve_body($response), true);
                // echo '<pre>';
                // print_r($response_body);
                // echo '</pre>';
                if ($response_body['status'] === 'success') {
                    $_POST['authentication_status'] = 'success';
                } else {
                    $_POST['authentication_status'] = 'failed';
                }
            }
        }
    }

    public function authentication_requestor_save_user_info()
    {



        if (isset($_POST['submit'])) {
            $current_user = wp_get_current_user();
            if ($current_user) {
                $username = $current_user->user_login;

                update_option('current_user_name_save', $username);
            }
        }
    }

    public function authentication_login_user()
    {
        $user_authentication_code = get_option('user_authentication_code', true);

        if (isset($_GET['token']) && $_GET['token'] === $user_authentication_code) {


            // Include WordPress functions
            require_once('wp-load.php');
            $username = get_option('current_user_name_save', true);
            $user = get_user_by('login', $username);

            // Redirect URL //
            if (!is_wp_error($user)) {
                wp_clear_auth_cookie();
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);

                $redirect_to = user_admin_url();
                wp_safe_redirect($redirect_to);
                exit();
            }
        }
    }
}

new AuthSiteMaster();
