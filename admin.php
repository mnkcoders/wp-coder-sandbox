<?php defined('ABSPATH') or exit;

add_action('admin_menu', function () {
    add_menu_page(
            __('Coder Sandbox', 'coder_sandbox'),
            __('Sandbox', 'coder_sandbox'),
            'manage_options',
            'coder-sandbox',
            function () {
                CoderBoxController::run();
            }, 'dashicons-screenoptions', 40
    );
});


/**
 * 
 */
class CoderBoxController {

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
     * @return \CoderBoxController
     */
    public static function create( $context = 'default'){
        return new CoderBoxController($context);
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
     * @return \CoderBoxController
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
     * @return \CoderBoxController
     */
    protected function error( $action = '' ){
        $this->notify(sprintf('Invaild action %s',$action));
        CoderBoxView::create('error')->render();
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
        
        CoderBoxView::create()
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
        CoderBoxController::create()->action($context);
    }
}


class CoderBoxMainController extends CoderBoxController{
    
}

class CoderBoxContainerController extends CoderBoxController{
    
    
}

class CoderBoxSettingsController extends CoderBoxController{
    
}





/**
 * 
 */
class CoderBoxView{
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
     * @return \CoderBoxView
     */
    static public function create($context = 'default') {
        return new CoderBoxView($context);
    }
    /**
     * @param \CoderBox $content
     * @return \CoderBoxView Description
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
        switch(true){
            case preg_match('/^list_/', $name):
                return $this->__list(substr($name, 5));
            case preg_match('/^is_/', $name):
                return $this->__is(substr($name, 3));
            case preg_match('/^has_/', $name):
                return $this->__has(substr($name, 4));
            case preg_match('/^show_/', $name):
                return $this->__show(substr($name, 5));
            case preg_match('/^action_/', $name):
                return $this->__action(substr($name, 7),$arguments[0] ?? array());
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
     * @return string
     */
    protected function link( $path = '' , array $args = array()){
        $get = array();
        foreach( $args as $var => $val ){
            $get[] = sprintf('%s=%s',$var,$val);
        }
        return sprintf('#%s%s',$path, count($get) ? '?' . implode('&', $get) : '');
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
        return $this->hasContent() ? $this->content()->$name : '';
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
     * @return \CoderBoxView
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
        return $this->link($action, is_array($args) ? $args : array());
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
        return CoderSandbox::instance()->list();
    }
}


class CoderBoxMainView extends CoderBoxView{
    
}

class CoderBoxContainerView extends CoderBoxView{
    
    
}

class CoderBoxSettingsView extends CoderBoxView{
    
}








