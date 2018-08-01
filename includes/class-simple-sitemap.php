<?php
/**
 * Simple Sitemap Base Class
 *
 * @since 0.1.0
 */

class Simple_Sitemap {

	/**
	 * @var object
	 *
	 * @since 0.1.0
	 */
	protected static $instance;


	/**
	 * Flush rewrite rules
	 *
	 * @since 0.1.0
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 *
	 *
	 * @since 0.1.0
	 *
	 * @return object 
	 */
	public static function get_instance() {
        if ( !static::$instance ) new static;
        return static::$instance;
	}

    /**
     * Class Constructor
     *
     * @since 0.1.0
     */
	public function __construct() {

		$this->init();

	}

	/**
	 * 
	 *
	 * Initializing class properties
	 *
	 * @since 0.1.0
	 */
	protected function init() {
		static::$instance = $this;
	}

	/**
	 * 
	 *
	 * @since 0.1.0
	 */
	public static function activate_plugin() {			
		static::flush_rewrite_rules();
	}

	/**
	 * 
	 *
	 * @since 0.1.0
	 */
	public static function deactivate_plugin() {
		static::flush_rewrite_rules();
	}

}