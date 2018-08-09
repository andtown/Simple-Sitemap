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
	 * @var boolean
	 *
	 * @since 0.1.0
	 */
	protected $is_taxonomy_sitemap;			

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

		$this->is_taxonomy_sitemap = false;		

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
	 * @since 0.1.0
	 */
	private function get_taxonomy_name_by_slug( $slug ) {
		global $wp_taxonomies;
		foreach ( (array) $wp_taxonomies as $taxonomy ) {
			if ( isset($taxonomy->rewrite['slug']) && ($slug == $taxonomy->rewrite['slug']) ) {
				return $taxonomy->name;
				break;
			}
		}
	}

	/**
	 *
	 *	
	 *
	 * @since 0.1.0
	 */
	protected function build_sitemap( $query_vars ) {
		global $wpdb, $wp_rewrite, $wp_taxonomies, $wp_post_types;

		$where = '';
		if ( $this->is_index_sitemap = (isset($query_vars['post_type']) && empty($query_vars['post_type'])) ) {
			$query_vars['post_type'] = $this->sitemap_post_types;
			$orderby = 't1.post_type ASC, t2.year DESC, t2.month DESC';
			$groupby = 't1.post_type, t2.month, t2.year';
			$post_modified = 'max(t1.post_modified)';			
		} elseif ( $this->is_post_type_sitemap = ( !empty($query_vars['post_type']) && !empty($query_vars['month']) && !empty($query_vars['year']) ) ) {
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

		$where = '';
		if ( $this->is_index_sitemap = $this->is_index_sitemap && !isset($query_vars['taxonomy']) ) {
			$groupby = 't2.taxonomy';
		} elseif ( $this->is_taxonomy_sitemap = ( !empty($query_vars['taxonomy']) ) ) {
			$wpdb->escape_by_ref($query_vars['taxonomy']);	
			$query_vars['taxonomy'] = $this->get_taxonomy_name_by_slug($query_vars['taxonomy']);
			$where = "taxonomy = '{$query_vars['taxonomy']}' AND ";			
			$groupby = 't2.term_id';
		}

		$taxonomies_terms_query = [];
		if ( $this->is_index_sitemap || $this->is_taxonomy_sitemap ):
			$taxonomies_terms_query = "
				SELECT DISTINCT
					t1.term_id, t1.slug, t2.taxonomy
				FROM
					wp_terms as t1
				JOIN
					( SELECT term_id, taxonomy FROM wp_term_taxonomy where $where 1=1 ) as t2
				ON
					t2.term_id = t1.term_id
				WHERE 
				    t1.term_id != '' AND t1.term_id = t2.term_id
				GROUP BY
					$groupby
			";

			$taxonomies_terms_query = $wpdb->get_results($taxonomies_terms_query, ARRAY_A);
		endif;

		if ( $this->is_index_sitemap ): ?> 
		<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
			<?php 
				foreach ( (array) $post_type_query as $sitemap ): 
					if ( !isset($wp_post_types[$sitemap['post_type']]) || empty($wp_post_types[$sitemap['post_type']]) ) continue;
			?>
			<sitemap> 
				<loc><?=home_url( $wp_rewrite->root )?>sitemap-<?php echo $sitemap['post_type']; ?>-<?php echo $sitemap['year']; ?>-<?php echo (strlen($sitemap['month'])>1)?$sitemap['month']:'0'.$sitemap['month']; ?>.xml</loc>
				<lastmod><?php echo date("Y-m-d\Th:m:s+00:00",strtotime($sitemap['last_modified'])); ?></lastmod>
			</sitemap>
			<?php endforeach; ?>
			<?php 
				foreach ( (array) $taxonomies_terms_query as $sitemap ): 
					if ( !(isset($wp_taxonomies[$sitemap['taxonomy']]) && ($wp_taxonomies[$sitemap['taxonomy']] instanceof WP_Taxonomy) && $wp_taxonomies[$sitemap['taxonomy']]->publicly_queryable) ) continue;
					if ( isset($wp_taxonomies[$sitemap['taxonomy']]->object_type) ) {
						foreach ( (array) $wp_taxonomies[$sitemap['taxonomy']]->object_type as $post_type )
							if ( in_array($post_type, $this->sitemap_post_types) && isset($wp_post_types[$post_type]) && !empty($wp_post_types[$post_type]) ) goto move_on1;						
					}
					continue;
					move_on1:
					if ( isset($wp_taxonomies[$sitemap['taxonomy']]->rewrite['slug']) && !empty($wp_taxonomies[$sitemap['taxonomy']]->rewrite['slug']) )
						$sitemap['taxonomy'] = $wp_taxonomies[$sitemap['taxonomy']]->rewrite['slug'];
			?>		
			<sitemap> 
				<loc><?=home_url( $wp_rewrite->root )?>sitemap-taxonomy/<?=$sitemap['taxonomy']?>.xml</loc>
			</sitemap>		
			<?php endforeach; ?>				
		</sitemapindex>	
		<?php else:  ?>
		<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
			<?php 
				foreach ( (array) $post_type_query as $sitemap ): 
					if ( !isset($wp_post_types[$sitemap['post_type']]) || empty($wp_post_types[$sitemap['post_type']]) ) continue;
			?>		
			<url>
				<loc><?=get_the_permalink($sitemap['ID'])?></loc>
				<lastmod><?php echo date("Y-m-d\Th:m:s+00:00",strtotime($sitemap['last_modified'])); ?></lastmod>
				<changefreq>weekly</changefreq>
				<priority>0.6</priority>
			</url>
			<?php endforeach; ?>	
			<?php 
				foreach ( (array) $taxonomies_terms_query as $sitemap ): 
					if ( !(isset($wp_taxonomies[$sitemap['taxonomy']]) && ($wp_taxonomies[$sitemap['taxonomy']] instanceof WP_Taxonomy) && $wp_taxonomies[$sitemap['taxonomy']]->publicly_queryable) ) continue;
					if ( isset($wp_taxonomies[$sitemap['taxonomy']]->object_type) ) {
						foreach ( (array) $wp_taxonomies[$sitemap['taxonomy']]->object_type as $post_type )
							if ( in_array($post_type, $this->sitemap_post_types) && isset($wp_post_types[$post_type]) && !empty($wp_post_types[$post_type]) ) goto move_on2;						
					}
					continue;
					move_on2:			
					if ( isset($wp_taxonomies[$sitemap['taxonomy']]->rewrite['slug']) && !empty($wp_taxonomies[$sitemap['taxonomy']]->rewrite['slug']) )
						$sitemap['taxonomy'] = $wp_taxonomies[$sitemap['taxonomy']]->rewrite['slug'];					
			?>		
			<url>
				<loc><?=home_url( $wp_rewrite->root )?><?=$sitemap['taxonomy']?>/<?=$sitemap['slug']?></loc>
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
        	"sitemap(?:-(".implode('|', $this->sitemap_post_types).")-([0-9]{4})-([0-9]{2}))?\.xml/?$" => 'post_type=$matches[1]&year=$matches[2]&month=$matches[3]',
        	"sitemap-taxonomy\/(.+?)(?:\/(.+?))?\.xml/?$" => 'taxonomy=$matches[1]&term=$matches[2]'        	
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