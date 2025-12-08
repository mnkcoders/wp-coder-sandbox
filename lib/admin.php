<?php namespace CODERS\Sandbox\Admin;

defined('ABSPATH') or exit;

add_action('admin_menu', function () {
    add_menu_page(
            __('Coder Sandbox', 'coder_sandbox'),
            __('Sandbox', 'coder_sandbox'),
            'manage_options',
            'coder-sandbox',
            function () {
                \CODERS\Sandbox\Admin\Controller::redirect(
                        filter_input(INPUT_GET, 'context') ?? 'admin');
            }, 'dashicons-screenoptions', 40
    );
    add_submenu_page(
            'coder-sandbox', // parent slug (must match main menu)
            __('Sandbox Settings', 'coder_sandbox'),
            __('Settings', 'coder_sandbox'),
            'manage_options',
            'coder-sandbox-settings', // submenu slug
            function () {
                \CODERS\Sandbox\Admin\Controller::redirect('settings');
            }
    );
});
add_action('admin_enqueue_scripts',function(){
    \CODERS\Sandbox\Admin\View::load(filter_input(INPUT_GET, 'page') ?? '');
});
add_action('admin_post_coder_sandbox', function () {
    \CODERS\Sandbox\Admin\Controller::redirect( 'form' , INPUT_POST );
    wp_redirect(add_query_arg(array('page'=>'coder-sandbox'), admin_url('admin.php')));
});
add_action('wp_ajax_coder_sandbox', function(){
    $server = \CODERS\Sandbox\Admin\Controller::redirect('ajax',INPUT_POST);
    wp_send_json($server->response());
    exit;
});
// if non-logged-in allowed:
add_action('wp_ajax_nopriv_coder_sandbox', function(){
    $server = \CODERS\Sandbox\Admin\Controller::redirect('ajax',INPUT_POST);
    wp_send_json($server->response());
    exit;
});


/**
 * 
 */
class Content extends \CODERS\Sandbox\Box{
    /**
     * @param string $name
     * @param string $endpoint
     */
    public function __construct($name = '' , $endpoint = '') {
        parent::__construct($name, $endpoint);
    }
    public function __get($name){
        switch($name){
            case 'state': return $this->getState();
        }
        return parent::__get($name);
    }

    /**
     * @return \CODERS\Sandbox\Data
     */
    public function data(){
        return self::sandbox()->data();
    }
    /**
     * @return array
     */
    public function listMetadata(){
        return $this->metadata ?? array();
    }
    /**
     * @return boolean
     */
    public function isReady(){
        return Content::sandbox()->check($this->name);
    }
    /**
     * @return string
     */
    public function getState(){
        return $this->isReady() ?
                __('ready','coder_sandbox') :
                __('empty','coder_sandbox') ;
    }

    /**
     * @return String
     */
    public function getName(){
        return $this->name;
    }
    /**
     * @return String
     */
    public function getTitle(){
        return $this->title;
    }
    /**
     * @return String
     */
    public function getTier(){
        return $this->tier;
    }
    /**
     * @return string
     */
    public function getCreated(){
        return $this->created;
    }

    /**
     * @return Boolean
     */
    public function isNew(){
        return false;
    }
    /**
     * @return String
     */
    public function getId(){
        return $this->id;
    }
    /**
     * @return String
     */
    public function getEndpoint(){
        return $this->endpoint;
    }
    /**
     * @return String
     */
    public function getUrl(){
        return self::sandbox()->link($this->name);
    }

    
    /**
     * @param \CODERS\Sandbox\Box $box
     * @return \CODERS\Sandbox\Admin\Content
     */
    public static function create(\CODERS\Sandbox\Box $box = null ){
        if( !is_null($box) && get_class($box) === \CODERS\Sandbox\Box::class){
            $content = new Content($box->name, $box->endpoint);
            $content->populate($box->content());
            return $content;
        }
        return null;
    }
            
    /**
     * @return \CODERS\Sandbox\CoderSandbox
     */
    public static final function sandbox(){
        return \CODERS\Sandbox\CoderSandbox::instance();
    }

    /**
     * @return \CODERS\Sandbox\Admin\Content[]
     */
    static public function listBoxes() {
        return array_map( function( $box ){
            return \CODERS\Sandbox\Admin\Content::create($box);
        },self::sandbox()->list(true));
    }
    /**
     * @param string $id
     * @return \CODERS\Sandbox\Admin\Content
     */
    public static function import( $id = '' ) {
        foreach (self::listBoxes() as $content ){
            if( $content->id === $id ){
                return $content;
            }
        }
        return null;
    }
}

/**
 * 
 */
class Controller {
    
    const POST = INPUT_POST;
    const GET = INPUT_GET;
    const COOKIE = INPUT_COOKIE;
    const REQUEST = 3;
    //const AJAX = 4;
    const SERVER = INPUT_SERVER;
    
    /**
     * @var array
     */
    private $_content = array();
    /**
     * @var array
     */
    private $_response = array();
    
    /**
     * @param array $input
     */
    protected function __construct( array $input = array() ) {
        $this->_content = $input;
    }
    /**
     * @param String $name
     * @return String
     */
    public function __get($name) {
        return $this->content()[$name] ?? '';
    }
    /**
     * @return String
     */
    public function type(){
        $type = explode('\\',get_called_class());
        return $type[count($type)-1];
    }

    /**
     * @return array
     */
    private function content(){
        return $this->_content;
    }
    /**
     * @return String
     */
    protected function action(){
        return $this->content()['action'] ?? 'main';
    }

    /**
     * @return Array
     */
    public function response() { return $this->_response; }
    
    /**
     * @param string $att
     * @param string $value
     * @return \CODERS\Sandbox\Admin\Controller
     */
    protected function put($att = '' , $value = ''){
        if(strlen($att)){
            $this->_response[$att] = $value;
        }
        return $this;
    }
    /**
     * @param array $data
     * @return \CODERS\Sandbox\Admin\Controller
     */
    protected function fill( array $data = array()) {
        foreach($data as $var => $val ){
            $this->_response[$var] = $val;
        }
        return $this;
    }
    /**
     * @param string $context main as default
     * @return \CODERS\Sandbox\Admin\View
     */
    protected function layout( $context = 'main' ){
        return View::create( strlen($context) ? $context : $this->action());
    }

    /**
     * @return string
     */
    public static function log(){
        return self::manager()->log();
    }
    /**
     * @param string $content
     * @param string $type
     * @return \CODERS\Sandbox\Admin\Controller
     */
    public function notify($content = '' , $type = 'info'){
        self::manager()->notify($content,$type);
        return $this;
    }

    /**
     * @return \CODERS\Sandbox\CoderSandbox
     */
    public static function manager(){
        return Content::sandbox();
    }
    /**
     * @return \CODERS\Tiers\Data
     */
    protected function data(){
        return self::manager()->db();
    }


    /**
     * @param string $action
     * @return \CODERS\Sandbox\Admin\Controller
     */
    protected function run(){
        try{
            $action = $this->action();
            $call = sprintf('%sAction', $action );
            $this->put('_type',$this->type())->put('_action',$action);
            $response = method_exists($this, $call) ?
                $this->$call( ) :
                    $this->error($action);
            return $this->put('_response',$response);
        }
        catch (\Exception $ex) {
            $this->notify($ex->getMessage(),'error');
        }
        return $this->put('_response',false);
    }    
    /**
     * @return bool
     */
    protected function error( ){
        $this->notify(sprintf('Invalid action <strong>[ %s ]</strong>',$this->action()), 'error');
        return false;
    }
    /**
     * @return boolean
     */
    protected function mainAction(){
        //implement in subclasses ;)
        $this->notify('Implement Controller subclass ;)');
        return true;
    }
    /**
     * Redirect to a new controller with custom inputs
     * @param string $context
     * @param array $input
     * @return \CODERS\Sandbox\Admin\Controller
     */
    public function forward( $context = '' , array $input = array()){
        return self::create($context, $input);
    }


    /**
     * @param String $context
     * @param array $input
     * @return \CODERS\Sandbox\Admin\Controller
     */
    private static function create( $context = '' ,array $input = array()){
        $class = sprintf('\CODERS\Sandbox\Admin\%sController', ucfirst($context));
        return class_exists($class) && is_subclass_of($class, self::class,true) ?
                new $class( $input ) :
                    new Controller($input);
    }

    /**
     * @param String $context
     * @param int $type
     * @return \CODERS\Sandbox\Admin\Controller
     */
    public static final function redirect( $context = 'admin' ,$type = self::REQUEST ) {
        return self::create($context, self::input($type, $type === self::POST))->run();
    }
    
   /**
    * @param Int $type POST,GET,REQUEST,SERVER,COOKIE
    * @param bool $maskaction parse task to action
    * @return array
    */
   public static function input($type = self::REQUEST , $maskaction = false ){
        switch($type){
            case self::COOKIE:
                return filter_input_array(INPUT_COOKIE,FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
            case self::POST:
                $input = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
                if( $maskaction ){
                    $input['action'] = $input['task'] ?? 'main';
                    unset($input['task']);
                }
                return $input;
            case self::GET:
                return filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [];
            case self::REQUEST:
                return array_merge(
                    filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: [],
                    filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: []
            );
            default:
                return array();
        }
   }
}




/**
 * 
 */
class AdminController extends Controller{
    
    /**
     * @return bool
     */
    protected function mainAction( ){
        
        $this->layout('sandbox')
                //->setData($content)
                ->view('list');
        
        return true;
    }
}
/**
 * 
 */
class SandboxController extends Controller{
    
    protected function error() {
        parent::error();
        $this->layout()->view('error');
        return false;
    }
    /**
     * @return bool
     */
    protected function mainAction() : bool{
        return $this->sandboxAction();
    }

    /**
     * @param array $input
     * @return bool
     */
    protected function sandboxAction(): bool {
        $box = Content::import($this->id);
        $this->layout('sandbox')
                ->setData($box)
                ->view('box');
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
    protected function mainAction(): bool {
        $this->layout()
                //->setContent(new SettingsContent())
                ->view('settings');

        return true;
    }
}
/**
 * 
 */
class FormController extends Controller{
    /**
     * @return bool
     */
    protected function mainAction() {
        $this->put('items', array());
        return false;
    }
    /**
     * @return bool
     */
    protected function saveAction() {
        return false;
    }
    /**
     * @return bool
     */
    protected function removeAction() {
        return false;
    }
    /**
     * @return bool
     */
    protected function updateAction() {
        return false;
    }
    /**
     * @return bool
     */
    protected function createAction() {
        return false;
    }
}
/**
 * 
 */
class AjaxController extends Controller{
    
}


/**
 * 
 */
class View{
    /**
     * @var string
     */
    private $_context = '';
    /**
     * @var \Object
     */
    private $_data = null;
    
    /**
     * @var array
     */
    private $_attributes = array(
        //define controller-view attributes here
    );
    
    /**
     * @param string $context
     */
    protected function __construct( $context = 'main' ) {
        $this->_context = $context;
    }
    /**
     * @param string $context
     * @return \CODERS\Sandbox\Admin\View
     */
    public static function create( $context = '' ){
        $class = sprintf('\CODERS\Sandbox\Admin\%sView', ucfirst($context));
        if(class_exists($class) && is_subclass_of($class, self::class)){
            return new $class($context);
        }
        return new View($context);
    }
    /**
     * @param \Object $data
     * @return \CODERS\Admin\View
     */
    public function setData($data = null ){
        $this->_data = is_object($data) ? $data : null;
        return $this;
    }
    /**
     * @return \Object
     */
    public function data() {
        return $this->_data;
    }
    /**
     * @return string
     */
    public function context(){
        return $this->_context;
    }

    /**
     * @param String $view
     * @return String
     */
    private function path($view = '') {
        return !empty($view) ?
            sprintf('%s/html/%s.php', preg_replace('/\\\\/', '/', CODER_SANDBOX_DIR), $view) : '';
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        $source = $this->data() ?? $this;
        switch(true){
            case preg_match('/^get_/', $name):
                $get = sprintf('get%s', ucfirst(substr($name, 4)));
                return method_exists($source, $get) ? $source->$get() : '';
            case preg_match('/^list_/', $name):
                $list = sprintf('list%s', ucfirst(substr($name,5)));
                return method_exists($source, $list) ? $source->$list() : array();
            case preg_match('/^is_/', $name):
                $is = sprintf('is%s', ucfirst(substr($name, 3)));
                return method_exists($source, $is) ? $source->$is() : false;
            case preg_match('/^has_/', $name):
                $has = sprintf('has%s', ucfirst(substr($name, 4)));
                return method_exists($source, $has) ? $source->$has() : false;
            default:
                return '';
                //return $this->_attributes[$name] ?? '';
        }
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name , $arguments ) {
        $args = is_array($arguments) ? $arguments : array();
        switch(true){
            case preg_match('/^get_/', $name):
                $get = sprintf('get%s', ucfirst(substr($name, 4)));
                return method_exists($this, $get) ? $this->$get(...$args) : '';
            case preg_match('/^list_/', $name):
                $list = sprintf('list%s', ucfirst(substr($name,5)));
                return method_exists($this, $list) ? $this->$list(...$args) : array();
            case preg_match('/^is_/', $name):
                $is = sprintf('is%s', ucfirst(substr($name, 3)));
                return method_exists($this, $is) ? $this->$is(...$args) : false;
            case preg_match('/^has_/', $name):
                $has = sprintf('has%s', ucfirst(substr($name, 4)));
                return method_exists($this, $has) ? $this->$has(...$args) : false;
            case preg_match('/^action_/', $name):
                return $this->action(substr($name, 7),...$args);
            case preg_match('/^show_/', $name):
                $show = $this->path(sprintf('templates/%s',substr($name, 5)) );
                if(file_exists($show)) {
                    require $show;
                    printf('<!-- %s -->',$name);
                }
                break;
        }
        return '';
    }
    /**
     * @param string $action
     * @param array $args
     * @return string
     */
    protected function action( $action = '' , array $args = array()){
        $query = array('page'=>'coder-sandbox');
        if(strlen($action)){
            $query['action'] = $action;            
        }
        if(strlen($this->_context)){
            $query['context'] = $this->_context;            
        }
        foreach( $args as $var => $val ){
            $query[$var] = $val;
        }
        return add_query_arg($query);
    }
    /**
     * @return string
     */
    protected function getNonce(){
        return wp_nonce_field('coder_nonce');
    }
    /**
     * @return String
     */
    protected function getFormurl(){
        return esc_url(admin_url('admin-post.php'));
    }

    /**
     * @return array
     */
    protected function listMessages(){
        return Controller::log();
    }

    /**
     * @param string $name
     * @return bool Description
     */
    public function view($name = ''){
        $view = $this->path( strlen($name ) ? $name : $this->context());
        if(!empty($view) && file_exists($view)){
            $this->viewMessages();
            require $view;
            return true;
        }
        printf('<!-- INVALID VIEW %s -->',$name);
        return false;
    }
    /**
     * 
     */
    public static function load($page = '') {
        if ($page === 'coder-sandbox') {
            $script = sprintf('%shtml/content/script.js', CODER_SANDBOX_URL);
            $script_path = sprintf('%shtml/content/script.js', CODER_SANDBOX_DIR);
            // Register and enqueue JS
            wp_enqueue_script('sandbox-admin-script', $script, ['jquery'], filemtime($script_path), true);

            // Optional: Pass variables to JS
            wp_localize_script('sandbox-admin-script', 'CoderSandboxApi', [
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('coder_nonce')
            ]);
        }
    }
}

/**
 * 
 */
class SandboxView extends \CODERS\Sandbox\Admin\View{
    /**
     * @return \CODERS\Sandbox\Admin\Content[
     */
    protected function listBoxes(){
        return Content::listBoxes();
    }
    /**
     * @return array
     */
    protected function listTiers(){
        $tiers = apply_filters('coder_tiers',array());
        return is_array($tiers) ? $tiers : array();
    }
    /**
     * @param string $box
     * @return boolean
     */
    protected function isReady( $box = '' ){
        return Content::sandbox()->check($box);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getUrl( $name ){
        return Content::sandbox()->link($name);
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






