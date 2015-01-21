<?php
/**
 * WPMovieLibrary Movie Headbox Class extension.
 *
 * @package   WPMovieLibrary
 * @author    Charlie MERLAND <charlie@caercam.org>
 * @license   GPL-3.0
 * @link      http://www.caercam.org/
 * @copyright 2014 CaerCam.org
 */

if ( ! class_exists( 'WPMOLY_Headbox' ) ) :

	class WPMOLY_Headbox extends WPMOLY_Movies {

		protected $themes = array(
			'wpmoly',
			'allocine',
			'imdb'
		);

		/**
		 * Show WPMOLY 2.0 modern metadata/details headbox content.
		 *
		 * @since    2.1.4
		 * 
		 * @param    string      $content The original post content
		 *
		 * @return   string      The filtered content containing original content plus movie infos if available, the untouched original content else.
		 */
		public function render( $content = null ) {

			$theme = wpmoly_o( 'headbox-theme' );
			if ( ! in_array( $theme, $this->themes ) )
				$theme = 'wpmoly';

			return call_user_func( array( $this, "get_{$theme}_headbox" ), $content );
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                      Allocine Movie Headbox
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		private function get_allocine_headbox( $content = null ) {

			$id = get_the_ID();

			$meta = self::get_movie_meta( $id, 'meta' );
			$details = self::get_movie_meta( $id, 'details' );
			
			$poster = get_the_post_thumbnail( $id, 'medium' );

			$images = $this->get_imdb_headbox_images( $id );

			$collections = get_the_terms( $id, 'collection' );
			if ( $collections && ! is_wp_error( $collections ) ) {
				foreach ( $collections as $i => $c ) {
					$collections[ $i ] = $c->name;
				}
			}

			if ( is_array( $collections ) )
				$collections = implode( ',', $collections );

			$meta['collections'] = WPMOLY_Utils::format_movie_terms_list( $collections, 'collection' );

			$meta['year']  = apply_filters( 'wpmoly_format_movie_year', $meta['release_date'], 'Y' );
			$meta['_year'] = date_i18n( 'Y', strtotime( $meta['release_date'] ) );
			$meta['_runtime'] = $meta['runtime'];

			$meta['_cast'] = array_slice( explode( ', ', $meta['cast'] ), 0, 3 );
			$meta['_cast'] = implode( ', ', $meta['_cast'] );
			$meta['_cast'] = apply_filters( "wpmoly_format_movie_cast", $meta['_cast'] );

			$_meta = array( 'title', 'director', 'writer', 'certification', 'runtime', 'genres', 'cast', 'composer', 'local_release_date', 'release_date', 'overview', 'tagline', 'genres', 'homepage', 'producer', 'production_countries', 'spoken_languages', 'budget', 'revenue', 'production_companies' );
			foreach ( $_meta as $m )
				$meta[ $m ] = apply_filters( "wpmoly_format_movie_$m", $meta[ $m ] );

			$details['rating_stars'] = apply_filters( 'wpmoly_movie_rating_stars', $details['rating'], $id, $base = 10 );

			$attributes = compact( 'id', 'meta', 'details', 'poster', 'images' );

			$content = WPMovieLibrary::render_template( 'movies/movie-allocine-headbox.php', $attributes, $require = 'always' );

			return $content;
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                        IMDb Movie Headbox
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		private function get_imdb_headbox( $content = null ) {

			$id = get_the_ID();

			$meta = self::get_movie_meta( $id, 'meta' );
			$details = self::get_movie_meta( $id, 'details' );
			
			$poster = get_the_post_thumbnail( $id, 'medium' );

			$images = $this->get_imdb_headbox_images( $id );

			$collections = get_the_terms( $id, 'collection' );
			if ( $collections && ! is_wp_error( $collections ) ) {
				foreach ( $collections as $i => $c ) {
					$collections[ $i ] = $c->name;
				}
			}

			if ( is_array( $collections ) )
				$collections = implode( ',', $collections );

			$meta['collections'] = WPMOLY_Utils::format_movie_terms_list( $collections, 'collection' );

			$meta['year']  = apply_filters( 'wpmoly_format_movie_year', $meta['release_date'], 'Y' );
			$meta['_year'] = date_i18n( 'Y', strtotime( $meta['release_date'] ) );
			$meta['_runtime'] = $meta['runtime'];

			$_meta = array( 'title', 'director', 'writer', 'certification', 'runtime', 'genres', 'cast', 'release_date', 'overview', 'tagline', 'genres', 'homepage', 'production_countries', 'spoken_languages', 'budget', 'revenue', 'production_companies' );
			foreach ( $_meta as $m )
				$meta[ $m ] = apply_filters( "wpmoly_format_movie_$m", $meta[ $m ] );

			$details['rating_stars'] = apply_filters( 'wpmoly_movie_rating_stars', $details['rating'], $id, $base = 10 );

			$attributes = compact( 'id', 'meta', 'details', 'poster', 'images' );

			$content = WPMovieLibrary::render_template( 'movies/movie-imdb-headbox.php', $attributes, $require = 'always' );

			return $content;
		}

		private function get_imdb_headbox_images( $post_id ) {

			$attachments = get_posts( array(
				'post_type'   => 'attachment',
				'orderby'     => 'title',
				'numberposts' => -1,
				'post_status' => null,
				'post_parent' => $post_id,
				'meta_key'    => '_wpmoly_image_related_tmdb_id',
				'exclude'     => get_post_thumbnail_id( $post_id )
			) );

			$images = array();
			$content = __( 'No images were imported for this movie.', 'wpmovielibrary' );
			
			if ( $attachments ) {

				foreach ( $attachments as $attachment )
					$images[] = array(
						'thumbnail' => wp_get_attachment_image_src( $attachment->ID, 'thumbnail' ),
						'full'      => wp_get_attachment_image_src( $attachment->ID, 'full' )
					);

				$content = WPMovieLibrary::render_template( 'shortcodes/images.php', array( 'size' => 'thumbnail', 'movie_id' => get_the_ID(), 'images' => $images ), $require = 'always' );
			}

			$attributes = array(
				'images' => $content
			);

			$content = WPMovieLibrary::render_template( 'movies/headbox/tabs/images.php', $attributes, $require = 'always' );

			return $content;
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                       Default Movie Headbox
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Show WPMOLY 2.0 modern metadata/details default headbox content.
		 *
		 * @since    2.0
		 * 
		 * @param    string      $content The original post content
		 *
		 * @return   string      The filtered content containing original content plus movie infos if available, the untouched original content else.
		 */
		public function get_wpmoly_headbox( $content = null ) {

			$theme = wp_get_theme();
			if ( ! is_null( $theme->stylesheet ) )
				$theme = 'theme-' . $theme->stylesheet;
			else
				$theme = '';

			$id      = get_the_ID();
			$poster  = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'large' );
			$poster  = $poster[0];

			$headbox = array(
				'title'     => wpmoly_o( 'headbox-title' ),
				'subtitle'  => wpmoly_o( 'headbox-subtitle' ),
				'details_1' => wpmoly_o( 'headbox-details-1' ),
				'details_2' => wpmoly_o( 'headbox-details-2' ),
				'details_3' => wpmoly_o( 'headbox-details-3' )
			);

			foreach ( $headbox as $slug => $content ) {

				if ( ! $content || empty( $content ) )
					continue;

				$line = '';
				foreach ( $content as $item ) {

					$_item = '';
					switch ( $item ) {
						case 'rating':
							$_item = apply_filters( 'wpmoly_movie_rating_stars', self::get_movie_meta( $id, 'rating' ) );
							$item  = 'rating starlined';
							break;
						case 'media':
						case 'status':
							$_item = apply_filters( "wpmoly_format_movie_$item", self::get_movie_meta( $id, $item ), $format = 'html', $icon = true );
							break;
						case 'release_date':
							$_item = apply_filters( 'wpmoly_format_movie_year', self::get_movie_meta( $id, 'release_date' ), 'Y' );
							break;
						default:
							$_item = apply_filters( "wpmoly_format_movie_$item", self::get_movie_meta( $id, $item ) );
							break;
					}

					if ( '' == $_item )
						$item = 'empty';

					$line .=  '<span class="wpmoly headbox movie ' . $item . '">' . $_item . '</span>';
				}
				$headbox[ $slug ] = $line;
			}

			$headbox['poster'] = $poster;

			$attributes = array(
				'id'      => get_the_ID(),
				'headbox' => $headbox,
				'menu'    => $this->get_wpmoly_headbox_menu(),
				'tabs'    => $this->get_wpmoly_headbox_tabs(),
				'theme'   => $theme,
			);
			$content = WPMovieLibrary::render_template( 'movies/movie-headbox.php', $attributes, $require = 'always' );

			return $content;
		}

		/**
		 * Modern headbox menu.
		 *
		 * @since    2.0
		 * 
		 * @return   string    Headbox Menu HTML markup
		 */
		public function get_wpmoly_headbox_menu() {

			$links = array(
				'overview' => array(
					'title' => __( 'Overview', 'wpmovielibrary' ),
					'icon'  => 'overview'
				),
				'meta' => array(
						'title' => __( 'Metadata', 'wpmovielibrary' ),
						'icon'  => 'meta'
					),
				'details' => array(
						'title' => __( 'Details', 'wpmovielibrary' ),
						'icon'  => 'details'
					),
				'actors' => array(
					'title' => __( 'Actors', 'wpmovielibrary' ),
					'icon'  => 'actor'
				),
				'images' => array(
					'title' => __( 'Images', 'wpmovielibrary' ),
					'icon'  => 'images'
				)
			);

			/**
			 * Filter the Headbox menu links before applying settings.
			 * 
			 * @since    2.1
			 * 
			 * @param    array    $links default menu links
			 */
			$links = apply_filters( 'wpmoly_pre_filter_headbox_menu_link', $links );

			$_links = array();
			$select  = wpmoly_o( 'headbox-tabs' );
			if ( is_array( $select ) )
				foreach ( $select as $s )
					if ( isset( $links[ $s ] ) )
						$_links[ $s ] = $links[ $s ];

			/**
			 * Filter the Headbox menu links.
			 * 
			 * @since    2.0
			 * 
			 * @param    array    $links default menu links
			 */
			$_links = apply_filters( 'wpmoly_filter_headbox_menu_link', $_links );

			$attributes = array(
				'id'    => get_the_ID(),
				'links' => $_links
			);
			$content = WPMovieLibrary::render_template( 'movies/headbox/menu.php', $attributes, $require = 'always' );

			return $content;
		}

		/**
		 * Modern headbox tabs content.
		 *
		 * @since    2.0
		 * 
		 * @return   string    Headbox Tabs content HTML markup
		 */
		public function get_wpmoly_headbox_tabs() {

			$tabs = array(
				'overview' => array(
					'title'   => __( 'Overview', 'wpmovielibrary' ),
					'icon'    => 'overview',
					'content' => $this->get_wpmoly_headbox_overview_tab()
				),
				'meta' => array(
					'title'   => __( 'Metadata', 'wpmovielibrary' ),
					'icon'    => 'meta',
					'content' => $this->get_wpmoly_headbox_meta_tab()
				),
				'details' => array(
					'title'   => __( 'Details', 'wpmovielibrary' ),
					'icon'    => 'details',
					'content' => $this->get_wpmoly_headbox_details_tab()
				),
				'actors' => array(
					'title'   => __( 'Actors', 'wpmovielibrary' ),
					'icon'    => 'actor',
					'content' => $this->get_wpmoly_headbox_actors_tab()
				),
				'images' => array(
					'title'   => __( 'Images', 'wpmovielibrary' ),
					'icon'    => 'images',
					'content' => $this->get_wpmoly_headbox_images_tab()
				)
			);

			/**
			 * Filter the Headbox tabs.
			 * 
			 * @since    2.0
			 * 
			 * @param    array    $tabs default headbox tabs
			 */
			$tabs = apply_filters( 'wpmoly_filter_headbox_menu_tabs', $tabs );

			$attributes = array(
				'id'   => get_the_ID(),
				'tabs' => $tabs
			);
			$content = WPMovieLibrary::render_template( 'movies/headbox/tabs.php', $attributes, $require = 'always' );

			return $content;
		}

		/**
		 * Modern headbox overview tab content callback.
		 * 
		 * @since    2.0
		 * 
		 * @return   string    Tab content HTML markup
		 */
		public function get_wpmoly_headbox_overview_tab() {

			$attributes = array(
				'overview' => wpmoly_get_movie_meta( get_the_ID(), 'overview' )
			);

			$content = WPMovieLibrary::render_template( 'movies/headbox/tabs/overview.php', $attributes, $require = 'always' );

			return $content;
		}

		/**
		 * Modern headbox meta tab content callback.
		 * 
		 * @since    2.0
		 * 
		 * @return   string    Tab content HTML markup
		 */
		public function get_wpmoly_headbox_meta_tab() {

			// TODO: better filtering/formatting
			$metadata = wpmoly_get_movie_meta();
			$metadata = wpmoly_filter_undimension_array( $metadata );

			$fields = wpmoly_o( 'sort-meta' );
			$default_fields = WPMOLY_Settings::get_supported_movie_meta();

			if ( '' == $metadata || empty( $fields ) || ! isset( $fields['used'] ) )
				return null;

			$fields = $fields['used'];
			if ( isset( $fields['placebo'] ) )
				unset( $fields['placebo'] );
			unset( $fields['cast'], $fields['overview'], $fields['genres'] );

			$items = array();

			foreach ( $fields as $slug => $field ) {

				$_field = $metadata[ $slug ];

				// Custom filter if available
				if ( has_filter( "wpmoly_format_movie_{$slug}" ) )
					$_field = apply_filters( "wpmoly_format_movie_{$slug}", $_field );

				// Filter empty field
				$_field = apply_filters( "wpmoly_format_movie_field", $_field );

				$fields[ $slug ] = $_field;
				$items[] = array( 'slug' => $slug, 'title' => __( $default_fields[ $slug ]['title'], 'wpmovielibrary' ), 'value' => $_field );
			}

			$attributes = array(
				'meta' => $items
			);

			$content = WPMovieLibrary::render_template( 'movies/headbox/tabs/meta.php', $attributes, $require = 'always' );

			return $content;
		}

		/**
		 * Modern headbox details tab content callback.
		 * 
		 * @since    2.0
		 * 
		 * @return   string    Tab content HTML markup
		 */
		public function get_wpmoly_headbox_details_tab() {

			// TODO: better filtering/formatting
			$details = wpmoly_get_movie_details();

			$fields = wpmoly_o( 'sort-details' );
			$default_fields = WPMOLY_Settings::get_supported_movie_details();

			if ( empty( $fields ) || ! isset( $fields['used'] ) )
				return null;

			$fields = $fields['used'];
			if ( isset( $fields['placebo'] ) )
				unset( $fields['placebo'] );
			$post_id = get_the_ID();

			$items = array();

			foreach ( $fields as $slug => $field ) {

				$detail = $details[ $slug ];

				if ( ! is_array( $detail ) )
					$detail = array( $detail );

				foreach ( $detail as $i => $d ) {

					if ( '' != $d ) {

						$value = $default_fields[ $slug ]['options'][ $d ];
						if ( 'rating' == $slug ) {
							$d = apply_filters( "wpmoly_movie_meta_link", 'rating', array_search( $value, $default_fields[ $slug ]['options'] ), 'detail', $value );
						} else {
							$d = apply_filters( "wpmoly_movie_meta_link", $slug, $value, 'detail', $value );
						}
					}

					$detail[ $i ] = apply_filters( "wpmoly_format_movie_field", $d );

				}

				if ( empty( $detail ) )
					$detail[] = apply_filters( "wpmoly_format_movie_field", '' );

				$title = '';
				if ( isset( $default_fields[ $slug ] ) )
					$title = __( $default_fields[ $slug ]['title'], 'wpmovielibrary' );

				$items[] = array( 'slug' => $slug, 'title' => $title, 'value' => $detail );
			}

			$attributes = array(
				'details' => $items
			);

			$content = WPMovieLibrary::render_template( 'movies/headbox/tabs/details.php', $attributes, $require = 'always' );

			return $content;
		}

		/**
		 * Modern headbox actors tab content callback.
		 * 
		 * @since    2.0
		 * 
		 * @return   string    Tab content HTML markup
		 */
		public function get_wpmoly_headbox_actors_tab() {

			$actors = wpmoly_get_movie_meta( get_the_ID(), 'cast' );
			$actors = apply_filters( 'wpmoly_format_movie_actors', $actors );

			$attributes = array(
				'actors' => $actors
			);

			$content = WPMovieLibrary::render_template( 'movies/headbox/tabs/actors.php', $attributes, $require = 'always' );

			return $content;
		}

		/**
		 * Modern headbox images tab content callback.
		 * 
		 * @since    2.0
		 * 
		 * @return   string    Tab content HTML markup
		 */
		public function get_wpmoly_headbox_images_tab() {

			$attachments = get_posts( array(
				'post_type'   => 'attachment',
				'orderby'     => 'title',
				'numberposts' => -1,
				'post_status' => null,
				'post_parent' => get_the_ID(),
				'exclude'     => get_post_thumbnail_id( get_the_ID() )
			) );
			$images = array();
			$content = __( 'No images were imported for this movie.', 'wpmovielibrary' );
			
			if ( $attachments ) {

				foreach ( $attachments as $attachment )
					$images[] = array(
						'thumbnail' => wp_get_attachment_image_src( $attachment->ID, 'thumbnail' ),
						'full'      => wp_get_attachment_image_src( $attachment->ID, 'full' )
					);

				$content = WPMovieLibrary::render_template( 'shortcodes/images.php', array( 'size' => 'thumbnail', 'movie_id' => get_the_ID(), 'images' => $images ), $require = 'always' );
			}

			$attributes = array(
				'images' => $content
			);

			$content = WPMovieLibrary::render_template( 'movies/headbox/tabs/images.php', $attributes, $require = 'always' );

			return $content;
		}

	}

endif;
