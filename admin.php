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
    add_submenu_page(
            'coder-sandbox', // parent slug (must match main menu)
            __('Sandbox Settings', 'coder_sandbox'),
            __('Settings', 'coder_sandbox'),
            'manage_options',
            'coder-sandbox-settings', // submenu slug
            function () {
                \CODERS\SandBox\Controller::run('settings');
            }
    );
});

add_action('admin_post_sandbox', function () {
    
    
    return \CODERS\SandBox\Controller::run(filter_input(INPUT_GET, 'context') ?? 'main');
    
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


interface ContentProvider{
    public function get($name = '') : string;
    public function list($name = '') : array;
    public function is($name = '') : bool;
    public function has($name = '') : bool;
    public function content() : array;
}

/**
 * 
 */
class SandboxContent extends \CoderBox implements ContentProvider{
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
            $content->populate($box->data());
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
    /**
     * @return \CoderBox[]
     */
    static public function listBoxes() {
        return self::Sandbox()->list();
    }
    /**
     * @param string $id
     * @return \CoderBox
     */
    public static function import( $id = '' ) {
        foreach (self::listBoxes() as $box ){
            if( $box->id === $id ){
                return self::create($box);
            }
        }
        return null;
    }
}
/**
 * 
 */
class SettingsContent implements ContentProvider{
    
    function __construct() {
        
    }


    public function content(): array {
        return array();
    }

    public function get($name = ''): string {
        
        return '';
    }

    public function has($name = ''): bool {
        return false;
    }

    public function is($name = ''): bool {
        return false;
    }

    public function list($name = ''): array {
        return array();
    }
}
/**
 * 
 */
abstract class Controller {

    /**
     * @var String
     */
    //private $_context = '';
    /**
     * @var array
     */
    private static $_mailbox = array();

    /**
     * 
     * @param String $context
     */
    protected function __construct( ) {
        //$this->_context = $context;
    }
    /**
     * @param string $context
     * @return \Controller
     */
    public static function create( $context = 'main'){
        
        $class = sprintf('\CODERS\SandBox\%sController', ucfirst($context));
        return class_exists($class) ? new $class( ) : new ErrorController();
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
     */
    protected static function notify($content = '' , $type = 'info' ) {
        Controller::$_mailbox[] = array('content'=>$content,'type'=>$type);
    }
    
    /**
     * @return array
     */
    public static function mailbox(){
        return Controller::$_mailbox;
    }
    /**
     * @param String $action
     * @return bool
     */
    public function action( $action = 'default' ){
        $command = sprintf('%sAction', $action ?? $this->context() );
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
        self::notify(sprintf('Invaild action %s',$action));
        View::create('error')->render();
        return $this;
    }
    /**
     * @param array $input
     * @return bool
     */
    abstract protected function defaultAction( array $input = array() );    
    
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
     * @return bool
     */
    static function run($context = 'main' ) {
        $id = filter_input(INPUT_GET, 'id') ?? '';
        $action = filter_input(INPUT_GET, 'action') ?? 'default';
        if(strlen($id) && $context === 'main'){
            $context = 'sandbox';
        }
        $controller =  Controller::create( $context );
        return !is_null($controller) ? $controller->action($action) ?? false : false;
    }
}

class ErrorController extends Controller{
    /**
     * @param array $input
     * @return bool
     */
    protected function defaultAction(array $input = []): bool {
        $this->error();
        return false;
    }
}

/**
 * 
 */
class MainController extends Controller{
    
    /**
     * @param array $input
     * @return bool
     */
    protected function defaultAction( array $input = array() ){
        
        //$this->notify('Default message');
        
        View::create()
                //->setContent($content)
                ->view('list');
        
        return true;
    }
}
/**
 * 
 */
class SandboxController extends Controller{
    
    /**
     * @param array $input
     * @return bool
     */
    protected function defaultAction(array $input = []): bool {
        
        $id = array_key_exists('id', $input) ? $input['id'] : '';
        $box = SandboxContent::import($id);
        View::create('sandbox')->setContent($box)->view('box');
        return true;
    }
}
/**
 * 
 */
class SettingsController extends Controller{
    /**
     * 
     * @param array $input
     * @return bool
     */
    protected function defaultAction(array $input = []): bool {

        View::create('settings')->setContent(new SettingsContent())->view('settings');

        return true;
    }
}





/**
 * 
 */
class View{
    /**
     * @var ContentProvider
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
     * @param ContentProvider $content
     * @return \View Description
     */
    public function setContent(ContentProvider $content = null ){
        $this->_content = $content;
        return $this;
    }
    /**
     * @return ContentProvider
     */
    protected function content(){
        return $this->_content;
    }
    
    /**
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call(string $name, array $arguments) {
        $args = is_array($arguments) ? $arguments : array();
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
                return $this->action(
                        substr($name, 7),
                        isset($args[0]) ? $args[0]: array());
            case preg_match('/^link/', $name):
                return $this->link(
                        substr($name, 5),
                        isset($args[0]) ? $args[0] : array());
            case preg_match('/^url/', $name):
                return $this->url(
                    explode( '_', substr($name, 4)),
                    isset($args[0]) ? $args[0] : array() );
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
     * 
     * @param string $link
     * @param array $args
     * @return string|url
     */
    protected function link($link = '', array $args = array()) {
        $call = sprintf('link%s', ucfirst($link));
        return method_exists($this, $call) ? $this->$call($args) : $this->url($link,$args);
    }
    /**
     * @param array $path
     * @param array $args
     * @return string
     */
    private function url( $path = array() , array $args = array() ){
        $base_url = site_url( count($path) ? implode('/', $path) : '' );

        $get = array();

        foreach( $args as $var => $val ){
            $get[] = sprintf('%s=%s',$var,$val);
        }

        if( count( $get )){
            $base_url .=  '?' . implode('&', $get);
        }

        return $base_url;
    }
    /**
     * @param array $args
     * @return string|url
     */
    private function adminurl( array $args = array()){
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
        $call = sprintf('action%s', ucfirst($action));
        if(method_exists($this, $call)){
            return $this->$call($args);
        }
        if(strlen($action)){
            $args['action'] = $action;
        }
        return $this->adminurl($args);
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
        $call = sprintf('get%s', ucfirst($name));
        if(method_exists($this, $call)){
            return $this->$call();
        }
        return $this->hasContent() ? $this->content()->$name : '';
    }
    /**
     * @param string $view
     * @return bool
     */
    public function view($view = ''){
        $this->viewMessages(Controller::mailbox() );
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
    protected function viewMessages( array $messages = array() ){
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
    /**
     * @param array $args
     * @return string
     */
    protected function linkSandbox( array $args = array()){
        $path = array('sandbox');
        if( count($args)){
            $path = array_merge($path,$args);
        }
        return $this->url($path);
    }
    /**
     * @param string $id
     * @return string|url
     */
    protected function actionSandbox( array $args = array() ) {
        $args['context'] = 'sandbox';
        return $this->adminurl($args);
    }
}









