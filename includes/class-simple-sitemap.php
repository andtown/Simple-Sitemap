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

		add_action('wp_loaded', array($this, 'parse_request'), 10, 1);		

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
	 *
	 * @since 0.1.0
	 */
	public function parse_request() {

        $pathinfo = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
        list( $pathinfo ) = explode( '?', $pathinfo ); 
        $pathinfo = str_replace( "%", "%25", $pathinfo );

        list( $req_uri ) = explode( '?', $_SERVER['REQUEST_URI'] );
        $home_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' ); 
        $home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

        $req_uri = str_replace($pathinfo, '', $req_uri);
        $req_uri = trim($req_uri, '/');
        $req_uri = preg_replace( $home_path_regex, '', $req_uri );
        $req_uri = trim($req_uri, '/');
        $pathinfo = trim($pathinfo, '/');
        $pathinfo = preg_replace( $home_path_regex, '', $pathinfo );
        $pathinfo = trim($pathinfo, '/');        

        if ( ! empty($pathinfo) && !preg_match('|^.*index.php$|', $pathinfo) ) {
            $requested_path = $pathinfo;
        } else {
            if ( $req_uri == 'index.php' )
                $req_uri = '';
            $requested_path = $req_uri;
        }

        $requested_file = $req_uri;

        $request_match = $requested_path;

        if ( !empty( $request_match ) ) {
	        foreach ( (array) $this->rewrite_rules() as $match => $query ) { 

	            if ( ! empty($requested_file) && strpos($match, $requested_file) === 0 && $requested_file != $requested_path )
	                $request_match = $requested_file . '/' . $requested_path;

	            if ( preg_match("#^$match#", $request_match, $matches) || preg_match("#^$match#", urldecode($request_match), $matches) ) {

	                $query_vars = addslashes(WP_MatchesMapRegex::apply($query, $matches));

	                parse_str( $query_vars, $query_vars );

	                header('Content-Type: application/xml');

	                die();
	            }
	            
	        }	
        }	

	}	

	/**
	 *
	 *
	 * @since 0.1.0
	 */
    protected function rewrite_rules() { 
        return apply_filters('simple_sitemap_rewrite_rules', [
        	"sitemap(?:-(page|post)-([0-9]{4})-([0-9]{2}))?\.xml/?$" => 'post_type=$matches[1]&year=$matches[2]&month=$matches[3]'
        ]);
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