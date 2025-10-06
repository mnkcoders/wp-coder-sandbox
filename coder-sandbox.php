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

    private $_boxes = array();

    private function __construct() {
        
    }
}

/**
 * 
 */
class CoderBox {

    /**
     * @var String[]
     */
    private $_container = '';

    /**
     * 
     * @param String $name
     */
    private function __construct($name = '') {

        $this->_container = $name;
    }
}
/**
 * 
 */
class CoderSandboxAdmin {

    private function __construct() {
        
    }
}
