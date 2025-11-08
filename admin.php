<?php namespace CODERS\SandBox;

defined('ABSPATH') or exit;

add_action('admin_menu', function () {
    add_menu_page(
            __('Coder Sandbox', 'coder_sandbox'),
            __('Sandbox', 'coder_sandbox'),
            'manage_options',
            'coder-sandbox',
            function () {
                \CODERS\SandBox\Controller::run();
            }, 'dashicons-screenoptions', 40
    );
});

add_action('admin_post_sandbox', function () {
    
    \CODERS\SandBox\Controller::run(filter_input(INPUT_GET, 'action') ?? 'default');
    
    return;
    
    $id = filter_input(INPUT_GET, 'id') ?? '';

    if (!$id) {
        wp_die('Invalid sandbox ID.');
    }

    // Build URL to plugin settings page with parameters
    $url = add_query_arg(
        [
            'page'   => 'coder-sandbox',
            'action' => 'sandbox',
            'id'     => $id
        ],
        admin_url('admin.php')
    );

    wp_redirect($url);
    exit;
});


interface Content{
    public function get($name = '') : string;
    public function list($name = '') : array;
    public function is($name = '') : bool;
    public function has($name = '') : bool;
    public function content() : array;
}

/**
 * 
 */
class SandboxContent extends \CoderBox implements Content{
    /**
     * @param string $name
     * @param string $endpoint
     */
    public function __construct($name = '' , $endpoint = '') {
        parent::__construct($name, $endpoint);
    }
    /**
     * @param \CoderBox $box
     * @return \CODERS\SandBox\SandboxContent
     */
    public static function create( \CoderBox $box = null ){
        if( !is_null($box) && get_class($box) === \CoderBox::class){

            $content = new SandboxContent($box->name, $box->endpoint);
            $content->populate($box->content());
            return $content;
        }
        return null;
    }
            
    /**
     * @return \CoderSandbox
     */
    public static final function Sandbox(){
        
        return \CoderSandbox::instance();
        
    }
    /**
     * @return array
     */
    public function content() : array {
        return $this->data();
    }
    /**
     * @param string $get
     * @return string
     */
    public function get($get = ''): string {
        return $this->$get;
    }
    /**
     * @param string $has
     * @return bool
     */
    public function has($has = ''): bool {
        $call = sprintf('has%s', ucfirst($has));
        return method_exists($this, $call) ? $this->$call() : array_key_exists($has, $this->content());
    }
    /**
     * @param string $is
     * @return bool
     */
    public function is($is = ''): bool {
        $call = sprintf('is%s', ucfirst($is));
        return method_exists($this, $call) ? $this->$call() : false;
    }
    /**
     * @param string $list
     * @return array
     */
    public function list($list = ''): array {
        $call = sprintf('list%s', ucfirst($list));
        return method_exists($this, $call) ?  $this->$call() : array();
    }
}


/**
 * 
 */
class Controller {

    /**
     * @var String
     */
    private $_context = '';
    /**
     * @var array
     */
    private $_mailbox = array();

    /**
     * 
     * @param String $context
     */
    private function __construct($context = '') {
        $this->_context = $context;
    }
    /**
     * @param string $context
     * @return \Controller
     */
    public static function create( $context = 'default'){
        return new Controller($context);
    }
    
    /**
     * @return String
     */
    public function context(){
        return $this->_context;
    }
    /**
     * @param String $content
     * @param String $type
     * @return \Controller
     */
    protected function notify($content = '' , $type = 'info' ) {
        $this->_mailbox[] = array('content'=>$content,'type'=>$type);
        return $this;
    }
    
    /**
     * @return array
     */
    private function mailbox(){
        return $this->_mailbox;
    }
    /**
     * @param String $action
     * @return bool
     */
    public function action( $action = '' ){
        $command = sprintf('%s_action', $action ?? $this->context() );
        if(method_exists($this, $command)){
            return $this->$command(self::input());
        }
        return $this->error($action);
    }
    /**
     * @param string $action
     * @return \Controller
     */
    protected function error( $action = '' ){
        $this->notify(sprintf('Invaild action %s',$action));
        View::create('error')->render();
        return $this;
    }
    /**
     * @param array $input
     * @return bool
     */
    private function error_action( array $input = array() ){
        $this->error($this->context());
        return false;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function default_action( array $input = array() ){
        
        $this->notify('Default message');
        
        View::create()
                //->setContent($content)
                ->viewMessages($this->mailbox())
                ->view();
        
        return true;
    }
    
    
    
    
    
    /**
     * @return String[]
     */
    static function input() {
        return array_merge(
                filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [],
                filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: []
        );
    }

    /**
     * @param String $context
     */
    static function run($context = 'default' ) {
        Controller::create()->action($context);
    }
}


class MainController extends Controller{
    
}

class BoxController extends Controller{
    
    
}

class SettingsController extends Controller{
    
}





/**
 * 
 */
class View{
    /**
     * @var \CoderBox
     */
    private $_content = null;
    /**
     * @var string
     */
    private $_context = '';
    
    /**
     * @param string $context
     */
    function __construct( $context = '' ) {
        $this->_context = $context;
    }
    /**
     * @param string $context
     * @return \View
     */
    static public function create($context = 'default') {
        return new View($context);
    }
    /**
     * @param \CoderBox $content
     * @return \View Description
     */
    public function setContent(\CoderBox $content = null ){
        $this->_content = $content;
        return $this;
    }
    /**
     * @return \CoderBox
     */
    protected function content(){
        return $this->_content;
    }
    
    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments) {
        $arguments = is_array($arguments) ? $arguments : array();
        switch(true){
            case preg_match('/^get_/', $name):
                return $this->get(substr($name, 5));
            case preg_match('/^list_/', $name):
                return $this->__list(substr($name, 5));
            case preg_match('/^is_/', $name):
                return $this->__is(substr($name, 3));
            case preg_match('/^has_/', $name):
                return $this->__has(substr($name, 4));
            case preg_match('/^show_/', $name):
                return $this->__show(substr($name, 5));
            case preg_match('/^action_/', $name):
                return $this->action(substr($name, 7),
                    isset($arguments[0]) ? $arguments[0]: array());
            case preg_match('/^url/', $name):
                return $this->link(
                    substr($name, 4),
                    isset($arguments[0]) ? $arguments[0] : array() ,
                    isset($arguments[1]) ? $arguments[1] : array() );
        }
        return $this->get($name);
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        return $this->$name();
    }
    /**
     * @param string $path
     * @param array $args
     * @param array $nodes
     * @return string
     */
    protected function link( $path = '' , array $args = array() , array $nodes = array()){
        $get = array();
        foreach( $args as $var => $val ){
            $get[] = sprintf('%s=%s',$var,$val);
        }
        if(count($nodes)){
            $path .=  '/' . implode('/', $nodes);
        }
        $base_url = site_url($path);
        if( count( $get )){
            $base_url .=  '?' . implode('&', $get);
        }
        return $base_url;
    }
    /**
     * @param array $args
     * @return string|url
     */
    protected function adminlink( array $args = array()){
        //$admin_url = menu_page_url('coder-sandbox');
        $admin_url = admin_url('admin.php?page=coder-sandbox');
        $get = array();
        foreach ($args as $var => $val ){
            $get[] = $var . '=' . $val;
        }
        return count($args) ? $admin_url . '&' . implode('&', $get) : $admin_url;
    }
    /**
     * @param string $action
     * @param array $args
     * @return string
     */
    protected function action( $action = '' , array $args = array()){
        if(strlen($action)){
            $args['action'] = $action;
        }
        return $this->adminlink($args);
    }
    /**
     * @param string $name
     * @return string
     */
    protected function path($name = ''){
        return sprintf('%s/html/%s',CODER_SANDBOX_DIR,$name);
    }
    /**
     * @param string $name
     * @return string
     */
    protected function get($name) {
        $call = sprintf('get%', ucfirst($name));
        return  method_exists($this, $call) ? $this->$call() : $this->content()->$name;
    }
    /**
     * @param string $view
     * @return bool
     */
    public function view($view = ''){
        $path = $this->path(sprintf('%s.php', strlen($view) ? $view : $this->_context));
        if(file_exists($path)){
            require $path;
        }
        else{
            require $this->path('error.php');
        }
        return $this;
    }
    /**
     * @param array $messages
     * @return \View
     */
    public function viewMessages( array $messages = array() ){
        foreach( $messages as $message ){
            printf('<div class="notice is-dismissible %s">%s</div>',$message['type'],$message['content']);
        }
        return $this;
    }    
    /**
     * @param string $action
     * @param array $args
     * @return string
     */
    protected function __action($action = '' , $args = array() ) {
        return $this->action($action, is_array($args) ? $args : array());
    }
    /**
     * @param string $show
     * @return bool
     */
    protected function __show($show = ''){
        return strlen($show) ? $this->view(sprintf('parts/%s.php',$show)) : false;
    }
    /**
     * @param string $list
     * @return array
     */
    protected function __list($list = ''){
        $call = sprintf('list%s', ucfirst($list));
        return method_exists($this, $call) ? $this->$call() : array();
    }
    /**
     * @param string $has
     * @return bool
     */
    protected function __has($has = '') {
        $call = sprintf('has%s', ucfirst($has));
        return method_exists($this, $call) ? $this->$call() : false;
    }
    /*
     * @param string $has
     * @return bool
     */
    protected function __is( $is = '' ){
        $call = sprintf('is%s', ucfirst($is));
        return method_exists($this, $call) ? $this->$call() : false;
    }
    /**
     * @return bool
     */
    protected function hasContent(){
        return !is_null( $this->content());
    }
    /**
     * @return \CoderBox[]
     */
    protected function listBoxes() {
        return SandboxContent::Sandbox()->list();
    }
}


class MainView extends View{
    
}

class BoxView extends View{
    
    
}

class SettingsView extends View{
    
}








