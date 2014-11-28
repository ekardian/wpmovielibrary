<?php
/**
 * WPMovieLibrary Archives Class extension.
 * 
 * This class contains the Custom Archives pages methods and hooks.
 * 
 * @package   WPMovieLibrary
 * @author    Charlie MERLAND <charlie@caercam.org>
 * @license   GPL-3.0
 * @link      http://www.caercam.org/
 * @copyright 2014 CaerCam.org
 */

if ( ! class_exists( 'WPMOLY_Archives' ) ) :

	class WPMOLY_Archives extends WPMOLY_Module {

		/**
		 * Constructor
		 *
		 * @since    2.1
		 */
		public function __construct() {

			$this->register_hook_callbacks();
		}

		/**
		 * Register callbacks for actions and filters
		 * 
		 * @since    2.1
		 */
		public function register_hook_callbacks() {

			add_filter( 'the_content', __CLASS__ . '::set_pages', 10, 1 );
		}

		/**
		 * Filter post content to render Movies and Taxonomies Custom
		 * Archives pages.
		 * 
		 * @since    2.1
		 * 
		 * @param    string    $content Current page content
		 * 
		 * @return   string    HTML markup
		 */
		public static function set_pages( $content ) {

			global $wp_query;

			if ( ! isset( $wp_query->queried_object_id ) )
				return $content;

			$id = $wp_query->queried_object_id;

			$archives = array(
				'movie'      => intval( wpmoly_o( 'movie-archives' ) ),
				'collection' => intval( wpmoly_o( 'collection-archives' ) ),
				'genre'      => intval( wpmoly_o( 'genre-archives' ) ),
				'actor'      => intval( wpmoly_o( 'actor-archives' ) )
			);

			if ( ! in_array( $id, $archives ) )
				return $content;

			extract( $archives );
			$archive = '';
			if ( $movie && $movie == $id )
				$archive = self::movie_archives();
			elseif ( $collection && $collection == $id )
				$archive = self::taxonomy_archives( 'collection' );
			elseif ( $genre && $genre == $id )
				$archive = self::taxonomy_archives( 'genre' );
			elseif ( $actor && $actor == $id )
				$archive = self::taxonomy_archives( 'actor' );

			return $archive . $content;
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                           Movie Archives
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Render Custom Movie Archives pages.
		 * 
		 * This is basically a call the to movie grid method with a few
		 * preset options.
		 * 
		 * @since    2.1
		 * 
		 * @return   string    HTML markup
		 */
		public static function movie_archives() {

			$letter  = get_query_var( 'letter' );
			$paged   = (int) get_query_var( '_page' );
			$number  = (int) get_query_var( 'number' );
			$columns = (int) get_query_var( 'columns' );
			$order   = get_query_var( 'order' );

			if ( ! isset( $_GET['order'] ) || '' == $_GET['order'] )
				$order = 'ASC';

			if ( 'DESC' != $order )
				$order = 'ASC';

			if ( ! $number )
				$number = -1;

			if ( ! $columns )
				$columns = 4;

			$args = compact( 'columns', 'number', 'order' );
			$grid_menu = WPMOLY_Movies::get_grid_menu( $args );

			$args    = compact( 'number', 'paged', 'order', 'columns', 'letter' );
			$grid    = WPMOLY_Movies::get_the_grid( $args );
			$content = $grid_menu . $grid;

			return $content;
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                         Taxonomy Archives
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Render Custom Taxonomies Archives pages.
		 * 
		 * This method is a bit complex because it can handle a couple of
		 * things. If a letter param is set, will get the list of terms
		 * starting with that letter, plus sorting/pagination options.
		 * 
		 * If no letter is set, simply render a paginated list of all
		 * taxonomy' terms.
		 * 
		 * @since    2.1
		 * 
		 * @param    string    $taxonomy Taxonomy slug
		 * 
		 * @return   string    HTML markup
		 */
		public static function taxonomy_archives( $taxonomy ) {

			global $wpdb;

			$term_title = '';
			if ( 'collection' == $taxonomy )
				$term_title = __( 'View all movies from collection &laquo; %s &raquo;', 'wpmovielibrary' );
			else if ( 'genre' == $taxonomy )
				$term_title = __( 'View all &laquo; %s &raquo; movies', 'wpmovielibrary' );
			else if ( 'actor' == $taxonomy )
				$term_title = __( 'View all movies staring &laquo; %s &raquo;', 'wpmovielibrary' );

			$name = WPMOLY_Cache::wpmoly_cache_name( "{$taxonomy}_archive" );
			$content = WPMOLY_Cache::output( $name, function() use ( $wpdb, $taxonomy, $term_title ) {

				$letter  = get_query_var( 'letter' );
				$order   = get_query_var( 'order' );
				$orderby = get_query_var( 'orderby' );
				$paged   = (int) get_query_var( '_page' );
				$number  = (int) get_query_var( 'number' );

				if ( ! isset( $_GET['order'] ) )
					$order = 'ASC';
				if ( '' == $orderby )
					$orderby = 'title';

				$_orderby = 't.name';
				if ( 'count' == $orderby )
					$_orderby = 'tt.count';

				// Limit the maximum number of terms to get
				$number = min( $number, 999 );
				if ( ! $number )
					$number = 50;

				// Calculate offset
				$offset = 0;
				if ( $paged )
					$offset = ( $number * ( $paged - 1 ) );

				// Don't use LIMIT with weird values
				$limit = '';
				if ( $offset < $number )
					$limit = sprintf( 'LIMIT %d,%d', $offset, $number );

				// This is actually a hard rewriting of get_terms()
				// to get exactly what we want without getting into
				// trouble with multiple filters and stuff.
				if ( '' != $letter ) {
					$like  = wpmoly_esc_like( $letter ) . '%';
					$query = "SELECT SQL_CALC_FOUND_ROWS t.*, tt.*
						    FROM {$wpdb->terms} AS t
						   INNER JOIN {$wpdb->term_taxonomy} AS tt
						      ON t.term_id = tt.term_id
						   WHERE tt.count > 0
						     AND tt.taxonomy = %s
						     AND t.name LIKE %s
						   ORDER BY {$_orderby} {$order}
						   {$limit}";
					$query = $wpdb->prepare( $query, $taxonomy, $like );
					$terms = $wpdb->get_results( $query );
				}
				else {
					$query = "SELECT SQL_CALC_FOUND_ROWS t.*, tt.*
						    FROM {$wpdb->terms} AS t
						   INNER JOIN {$wpdb->term_taxonomy} AS tt
						      ON t.term_id = tt.term_id
						   WHERE tt.count > 0
						     AND tt.taxonomy = %s
						   ORDER BY {$_orderby} {$order}
						   {$limit}";
					$query = $wpdb->prepare( $query, $taxonomy );
					$terms = $wpdb->get_results( $query );
				}

				$total = $wpdb->get_var( 'SELECT FOUND_ROWS() AS total' );
				$terms = apply_filters( 'get_terms', $terms, (array) $taxonomy, array() );
				$links = array();

				// Setting up the terms list...
				if ( is_wp_error( $terms ) )
					$links = $terms;
				else 
					foreach ( $terms as $term )
						$links[] = array(
							'url'        => get_term_link( $term ),
							'attr_title' => sprintf( $term_title, $term->name ),
							'title'      => $term->name,
							'count'      => sprintf( _n( '%d movie', '%d movies', $term->count, 'wpmovielibrary' ), $term->count )
						);

				// ... the main menu...
				$args = array(
					'order'   => $order,
					'orderby' => $orderby,
					'number'  => $number
				);
				$menu = self::taxonomy_archive_menu( $taxonomy, $args );

				$args['letter'] = $letter;
				$url = add_query_arg( $args, get_permalink() );

				// ... and the pagination menu.
				$args = array(
					'type'    => 'list',
					'total'   => ceil( ( $total - 1 ) / $number ),
					'current' => max( 1, $paged ),
					'format'  => $url . '&_page=%#%',
				);
				$pagination = WPMOLY_Utils::paginate_links( $args );
				$pagination = '<div id="wpmoly-movies-pagination">' . $pagination . '</div>';

				$attributes = array( 'taxonomy' => $taxonomy, 'links' => $links );
				$content = WPMovieLibrary::render_template( 'archives/archives.php', $attributes, $require = 'always' );

				$content = $menu . $content . $pagination;

				return $content;
			});

			return $content;
		}

		/**
		 * Generate Custom Taxonome Archives pages menu.
		 * 
		 * Similar to the version 2.0 grid shortcode, this generated a
		 * double menu: alphabetical selection of taxonomies, and basic
		 * sorting menu including asc/descending alphabetical/numeric
		 * sorting, number limitation and pagination.
		 * 
		 * @since    2.1
		 * 
		 * @param    string    $taxonomy Taxonomy type: collection, genre or actor
		 * @param    array     $args Taxonomy Menu arguments
		 * 
		 * @return   string    HTML content
		 */
		public static function taxonomy_archive_menu( $taxonomy, $args ) {

			global $wpdb;

			$defaults = array(
				'order'   => 'ASC',
				'orderby' => 'title',
				'number'  => 50
			);
			$args = wp_parse_args( $args, $defaults );
			extract( $args );

			$default = str_split( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' );
			$letters = array();
			$letter  = get_query_var( 'letter' );
			
			$result = $wpdb->get_results( "SELECT DISTINCT LEFT(t.name, 1) as letter FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('collection') ORDER BY t.name ASC" );
			foreach ( $result as $r )
				$letters[] = $r->letter;

			$letter_url  = add_query_arg( compact( 'order', 'orderby', 'number' ), get_permalink() );
			$default_url = add_query_arg( compact( 'order', 'orderby', 'number', 'letter' ), get_permalink() );

			$attributes = compact( 'letters', 'default', 'letter', 'order', 'orderby', 'number', 'letter_url', 'default_url' );

			$content = self::render_template( 'archives/menu.php', $attributes );

			return $content;
		}

		/**
		 * Prepares sites to use the plugin during single or network-wide activation
		 *
		 * @since    1.0
		 *
		 * @param    bool    $network_wide
		 */
		public function activate( $network_wide ) {}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 *
		 * @since    1.0
		 */
		public function deactivate() {}

		/**
		 * Set the uninstallation instructions
		 *
		 * @since    1.0
		 */
		public static function uninstall() {}

		/**
		 * Initializes variables
		 *
		 * @since    1.0
		 */
		public function init() {}

	}

endif;
