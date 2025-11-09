<?php namespace CODERS\Sandbox;

defined('ABSPATH') or exit;


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
    private function __construct( $preload = false ) {
        if( $preload ){
            $this->list(true);
        }
    }
    /**
     * @param bool $refresh
     * @return \CODERS\Sandbox\CoderSandbox
     */
    public function list(  $refresh = false ){
        if( $refresh ){
            $data = new SandboxData();
            $this->_boxes = array_map(function( $boxdata ){
                return CoderBox::load($boxdata);
            }, $data->list());
        }
        return $this->_boxes;
    }
    /**
     * @param string $box
     * @return \CODERS\Sandbox\CoderBox
     */
    public function load($box = '') {
        if(strlen($box)){
            $db = new SandboxData();
            return CoderBox::load( $db->load($box) );
        }
        return null;
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
     * 
     */
    public static function install( ){
        $data = new SandboxData();
        $data->install();
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
        'id' => '',
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
     * @param String $name
     * @return String
     */
    public function __get($name){
        $key = strtolower($name);
        return array_key_exists($key, $this->_content) ? $this->_content[$key] : '';
    }
    /**
     * @param string $name
     * @return string
     */
    protected static function generateid($name) {
        return md5($name);
    }
    /**
     * @return array
     */
    protected function data(){
        return $this->_content;
    }
    /**
     * @param array $input
     * @return \CoderBox
     */
    protected function populate( array $input  = array() ) {
        foreach($input as $var => $val ){
            $this->_content[$var ] = $val;
        }
        return $this;
    }
    /**
     * @return bool
     */
    protected function isNew() {
        return strlen($this->id) > 0;
    }
    /**
     * @return bool
     */
    public function save() {
        $db = new SandboxData();
        if($this->isNew()){
            $this->_content['id'] = self::generateid($this->name);
            return $db->create($this->data());            
        }
        else{
            return $db->update($this->data());
        }
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
     * @param \CoderBox $box
     * @return bool
     */
    public function build( ){
            $path = $this->local();
            if (!file_exists($path)) {
                return wp_mkdir_p($path);
            }
        return false;
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
        return $fullpath ? $this->local() . $this->endpoint : $this->endpoint;
    }
    /**
     * @param string $content
     * @param string $baseurl
     * @param bool $attachclient
     * @return string
     */
    private function parse( $content = '' , $baseurl = '' , $attachclient = false ) {
        
        // Replace src, href, and data-* attributes that start with ./ or without http
        $patterns = [
            '/(src|href)=["\'](?!https?:\/\/|\/\/|data:|#)([^"\']+)["\']/i'
        ];

        $parsed = preg_replace_callback($patterns, function ($matches) use ($baseurl) {
            $attr = $matches[1];
            $url = ltrim($matches[2], '/');
            $new_url = trailingslashit($baseurl) . $url;
            return sprintf('%s="%s"', $attr, esc_url($new_url));
        }, $content);

        if ($attachclient) {
            $client = sprintf('<script type="text/javascript" src="%s/sandbox/client.js"></script>',
                    CODER_SANDBOX_URL);
            $parsed = preg_replace("/\<\/body\>/", sprintf('%s</body>',$client), $parsed);
        }
        $parsed .= sprintf('<!-- LOADED ON %s -->', date('Y-m-d H:i:s'));

        return $parsed;
    }

    /**
     * @return bool
     */
    public function run() {
        $route = $this->endpoint(true);
        if (file_exists($route)) {
            //load sandbox client script wrapper
            $client = true;
            $type = strtolower(pathinfo($route, PATHINFO_EXTENSION));
            if($type === 'php'){
                require $route;
            }
            else{
                $content = file_get_contents($route);
                // Optionally inject dynamic data or user tier validation
                echo $this->parse( $content , $this->container(true) , $client );
            }
            return true;
        }
        else {
            wp_die( sprintf('<p>%s: <strong>%s</strong></p>',
                    __('Sandbox application not found', 'coder_sandbox'),
                    $this->container()));
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

/**
 * 
 */
class SandboxData{
    
    private $_log = array();
    
    /**
     * @param string $message
     * @param string $type
     * @return \CODERS\Sandbox\SandboxData
     */
    protected function notify($message = '',$type='info') {
        if(strlen($message)){
            $this->_log[] = array(
                'content' => $message,
                'type' => $type
            );
        }
        return $this;
    }
    /**
     * @return array
     */
    public function log( ) {
        return $this->_log;
    }

    /**
     * @global \wpdb $wpdb
     * @return String
     */
    public static function table(){
        global $wpdb;
        return $wpdb->prefix . 'coder_sandbox';
    }
    /**
     * @param array $data
     * @return bool
     */
    public function update( array $data = array( ) ) {
     
        
        
        return false;
    }
    /**
     * @param array $data
     * @return bool
     */
    public function create( array $data = array() ){
        
        
        return false;
    }

    /**
     * @global \wpdb $wpdb
     * @return array
     */
    public function load( $box = '' ){
        global $wpdb;
        $table = self::table();
        $data = $wpdb->get_row("SELECT * FROM {$table} WHERE `name`='$box'", ARRAY_A);
        if ($data) {
            return $data;
        }
        $this->notify($wpdb->error,'error');
        return array();
    }    

    /**
     * @global \wpdb $wpdb
     * @return array
     */
    public function list(){
        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY `name`,`created` ASC", ARRAY_A);
        if ($rows) {
            return $rows;
        }
        $this->notify($wpdb->error,'error');
        return array();
    }    
    /**
     * @global \wpdb $wpdb
     */
    public function install( ){
        
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
    }
}









