<?php
/**
 * Plugin Name: Coder Sandbox
 * Description: App container for coders extensions. 
 * Version:     0.1.0
 * Author:      Coder#1
 * Text Domain: coder_sandbox
 */
defined('ABSPATH') or exit;
define('CODER_SANDBOX_DIR', plugin_dir_path(__FILE__));
define('CODER_SANDBOX_URL', plugin_dir_url(__FILE__));
require_once sprintf('%s/lib/classes.php', CODER_SANDBOX_DIR);

// Bootstrap plugin
add_action('plugins_loaded', function () {
    \CODERS\Sandbox\CoderSandbox::instance(); }
);

add_action('init', function () {
    if (is_admin()) {
        require_once sprintf('%s/lib/admin.php', CODER_SANDBOX_DIR);
    } else {
        \CODERS\Sandbox\CoderSandbox::rewrite();
    }
});

add_action('template_redirect', function () {
    $endpoint = get_query_var('sandbox_app');
    if ($endpoint) {
        $box = new \CODERS\Sandbox\Box($endpoint);
        $box->run();
        exit;
    }
});

register_activation_hook(__FILE__, function(){
    $sandbox = \CODERS\Sandbox\CoderSandbox::instance();
    $sandbox->data()->install();
    $sandbox->rewrite(true);
});

register_deactivation_hook(__FILE__, function(){
    flush_rewrite_rules();        
    //$sandbox = \CODERS\Sandbox\CoderSandbox::instance();
    //$sandbox->uninstall();
});






