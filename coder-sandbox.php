<?php

defined('ABSPATH') or exit;
/**
 * Plugin Name: Coder Sandbox
 * Description: App container for coders extensions. 
 * Version:     0.1.0
 * Author:      Coder#1
 * Text Domain: coder_sandbox
 */
define('CODER_SANDBOX_DIR', plugin_dir_path(__FILE__));

/**
 * 
 */
class CoderSandbox {

    /**
     * @var \CoderSandbox
     */
    private static $_instance = null;

    /**
     * @var String[]
     */
    private $_boxes = array();

    /**
     * 
     */
    private function __construct() {
        
    }
    
    /**
     * @return \CoderBox[]
     */
    public function list(){
        return array_map(function( $boxdata ){
            return CoderBox::load($boxdata);
        }, $this->load());
    }
    /**
     * @param \CoderBox $box
     * @return bool
     */
    public function save(\CoderBox $box = null){
        if($box){
            $path = $box->local();
            if (!file_exists($path)) {
                wp_mkdir_p($path);
            }
            return true;
        }
        return false;
    }
    
    /**
     * @global wpdb $wpdb
     * @return array
     */
    private static function load(){
        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY `name`,`created` ASC", ARRAY_A);
        if ($rows) {
            return $rows;
        }
        return array();
    }

    /**
     * @global wpdb $wpdb
     * @return String
     */
    private static function table(){
        global $wpdb;
        return $wpdb->prefix . 'coder_sandbox';
    }
    
    /**
     * @param bool $flush
     */
    public static function rewrite( $flush = false ){
        add_rewrite_rule('^sandbox/([^/]*)/?', 'index.php?sandbox_app=$matches[1]', 'top');
        add_rewrite_tag('%sandbox_app%', '([^&]+)');
        if($flush){
            flush_rewrite_rules();
        }
    }
    /**
     * @global wpdb $wpdb
     */
    public static function install( ){
        
        //register new install and database deltas
        global $wpdb;
        $table = self::table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id CHAR(32) NOT NULL,
            name VARCHAR(64) NOT NULL,
            title VARCHAR(128) NOT NULL,
            endpoint VARCHAR(128) NOT NULL DEFAULT 'index.html',
            tier VARCHAR(32) DEFAULT '',
            metadata LONGTEXT DEFAULT NULL,
            created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);        
        
        self::rewrite(true);
    }
    /**
     * 
     */
    public static function uninstall(){
        //update rules to remove endpoint
        flush_rewrite_rules();        
    }
    /**
     * @return \CoderSandbox
     */
    public static function instance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}

/**
 * 
 */
class CoderBox {
    /**
     * @var String[]
     */
    private $_content = array(
        //input data
        'name' => '',
        'endpoint' => 'index.html',
    );
    /**
     * @param String $name
     * @param String $endpoint
     */
    public function __construct($name = '' , $endpoint = '') {
        $this->_content['name'] = sanitize_title($name);
        $this->_content['endpoint'] = !empty($endpoint) ? sanitize_title($endpoint) : 'index.html';
        $this->_content['created'] = date('Y-m-d H:i:s');
        //$this->_id = md5($this->name . $this->created);
    }
    /**
     * @param array $input
     * @return \CoderBox
     */
    private function populate( array $input  = array() ) {
        foreach($input as $var => $val ){
            $this->_content[$var ] = $val;
        }
        return $this;
    }
    /**
     * @return array
     */
    public function export(){
        $data = $this->_content;
        if(!array_key_exists('id', $data)){
            //Id's won't be creted in box data, but in save process (empty ID = new box / unexisting in database)
            //$data['id'] = md5($data['name'].$data['created']);
        }
        if(!array_key_exists('tier', $data)){
            //$data['tier'] = '';
        }
        if(!array_key_exists('title', $data)){
            //$data['title'] = $data['name'];
        }
        return $data;
    }
    /**
     * @param String $name
     * @return String
     */
    public function __get($name){
        $key = strtolower($name);
        return array_key_exists($key, $this->_content) ? $this->_content[$key] : '';
    }
    /**
     * @param bool $fullpath
     * @return String
     */
    public function container( $fullpath = false){
        return $fullpath ? self::uploads($this->name) : $this->name;
    }
    /**
     * @return string
     */
    public function local( ){
        return self::uploads($this->name,true);
    }
    /**
     * @return String
     */
    public function title(){
        return $this->title ?? $this->name;
    }
    /**
     * @return String
     */
    public function created(){
        return $this->created;
    }
    /**
     * @param bool $fullpath
     * @return String
     */
    public function endpoint( $fullpath = false ){
        return $fullpath ? $this->local(true) . $this->endpoint : $this->endpoint;
    }
    /**
     * @return bool
     */
    public function run() {
        $route = $this->endpoint(true);
        if (file_exists($route)) {
            $type = explode('.', strtolower( $this->endpoint()) );
            if($type[count($type)-1] === 'php'){
                require $route;
            }
            else{
                $content = file_get_contents($route);
                // Optionally inject dynamic data or user tier validation
                echo $content;
            }
            return true;
        }
        else {
            wp_die(__('Sandbox application not found', 'coder_sandbox') . ' :: ' . $this->container());
        }
        return false;
    }
    /**
     * @param String $container
     * @param bool $getdir
     * @return String
     */
    static function uploads( $container = '' ,$getdir = false){
        $upload_dir = wp_upload_dir();
        return sprintf('%ssandbox/%s',
                trailingslashit( $getdir ? $upload_dir['basedir'] : $upload_dir['baseurl']),
                !empty($container) ? $container . '/' : '');
    }
    /**
     * @param array $data
     * @return \CoderBox|null
     */
    static function load( array $data = array()) {
        if(array_key_exists('name', $data)){
            $box = new CoderBox($data['name']);
            $box->populate($data);
            return $box;
        }
        return null;
    }
}

// Bootstrap plugin
add_action('plugins_loaded', function () { CoderSandbox::instance(); });

add_action('init', function () {
    if (is_admin()) {
        require CODER_SANDBOX_DIR . 'admin.php';
    } else {
        CoderSandbox::rewrite();
    }
});


add_action('template_redirect', function () {
    $endpoint = get_query_var('sandbox_app');
    if ($endpoint) {
        $box = new CoderBox($endpoint);
        $box->run();
        exit;
    }
});

// Activation hook
register_activation_hook(__FILE__, function(){
    CoderSandbox::install();
});
register_deactivation_hook(__FILE__, function(){
    CoderSandbox::uninstall();
});




