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
	 *
	 * @var array
	 *
	 * @since 0.1.0
	 */
	protected $sitemap_post_types;

	/**
	 * @var boolean
	 *
	 * @since 0.1.0
	 */
	protected $is_index_sitemap;

	/**
	 * @var boolean
	 *
	 * @since 0.1.0
	 */
	protected $is_post_type_sitemap;	

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

		add_action( 'init', array($this, 'wp_init'), 10, 1 );

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

		$this->sitemap_post_types = array('post','page');

		$this->is_index_sitemap = false;

		$this->is_post_type_sitemap = false;		

	}

	/**
	 * 
	 *
	 *
	 * @since 0.1.0
	 */
	public function wp_init() {

		$this->sitemap_post_types = apply_filters('sitemap_post_types', $this->sitemap_post_types);

	}	

	/**
	 *
	 *	
	 *
	 * @since 0.1.0
	 */
	protected function build_sitemap( $query_vars ) {
		global $wpdb, $wp_rewrite;

		$where = '';
		if ( $this->is_index_sitemap = (isset($query_vars['post_type']) && empty($query_vars['post_type'])) ) {
			$query_vars['post_type'] = $this->sitemap_post_types;
			$orderby = 't1.post_type ASC, t2.year DESC, t2.month DESC';
			$groupby = 't2.month, t2.year';
			$post_modified = 'max(t1.post_modified)';			
		} elseif ( $this->is_post_type_sitemap = true ) {
  			$wpdb->escape_by_ref($query_vars['post_type']);
			$query_vars['post_type'] = (array) $query_vars['post_type'];
			$query_vars['month'] = intval($query_vars['month']);
			$where = "year(post_date) = {$query_vars['year']} AND month(post_date) = {$query_vars['month']} AND ";
			$orderby = 't1.post_modified DESC';
			$groupby = 't1.ID';
			$post_modified = 't1.post_modified';
		}

		$post_type_query = [];
		if ( $this->is_index_sitemap || $this->is_post_type_sitemap ):
			$post_type_query = " 
				SELECT distinct
					t1.ID, t1.post_name, t2.month, t2.year, t1.post_type, $post_modified as last_modified
				FROM 
					wp_posts as t1
				JOIN 
					( select ID, post_type, month(post_date) as month, year(post_date) as year from wp_posts where $where post_status = 'publish' and post_type in ('".implode("','", $query_vars['post_type'])."') ) 
				AS
					t2
				ON 
					t1.ID = t2.ID
				WHERE
					t1.post_type = t2.post_type
				GROUP BY 
					$groupby
				ORDER BY 
					$orderby
			";

			$post_type_query = $wpdb->get_results($post_type_query, ARRAY_A);
		endif;

		if ( $this->is_index_sitemap ): ?> 
		<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
			<?php foreach ( (array) $post_type_query as $sitemap ): ?>
			<sitemap> 
				<loc><?=home_url( $wp_rewrite->root )?>sitemap-<?php echo $sitemap['post_type']; ?>-<?php echo $sitemap['year']; ?>-<?php echo (strlen($sitemap['month'])>1)?$sitemap['month']:'0'.$sitemap['month']; ?>.xml</loc>
				<lastmod><?php echo date("Y-m-d\Th:m:s+00:00",strtotime($sitemap['last_modified'])); ?></lastmod>
			</sitemap>
			<?php endforeach; ?>				
		</sitemapindex>	
		<?php else:  ?>
		<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
			<?php foreach ( (array) $post_type_query as $sitemap ): ?>		
			<url>
				<loc><?=get_the_permalink($sitemap['ID'])?></loc>
				<lastmod><?php echo date("Y-m-d\Th:m:s+00:00",strtotime($sitemap['last_modified'])); ?></lastmod>
				<changefreq>weekly</changefreq>
				<priority>0.6</priority>
			</url>
			<?php endforeach; ?>								
		</urlset>
		<?php endif; 
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

	                $this->build_sitemap($query_vars);

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
        	"sitemap(?:-(".implode('|', $this->sitemap_post_types).")-([0-9]{4})-([0-9]{2}))?\.xml/?$" => 'post_type=$matches[1]&year=$matches[2]&month=$matches[3]'
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