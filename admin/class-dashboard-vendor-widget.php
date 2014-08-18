<?php
/**
 * WPMovieLibrary Dashboard Class extension.
 * 
 * Create a Promotional Widget.
 *
 * @package   WPMovieLibrary
 * @author    Charlie MERLAND <charlie@caercam.org>
 * @license   GPL-3.0
 * @link      http://www.caercam.org/
 * @copyright 2014 CaerCam.org
 */

if ( ! class_exists( 'WPML_Dashboard_Vendor_Widget' ) ) :

	class WPML_Dashboard_Vendor_Widget extends WPML_Dashboard {

		/**
		 * Widget ID
		 * 
		 * @since    1.0.0
		 * 
		 * @var      string
		 */
		protected $widget_id = 'wpml_dashboard_vendor_widget';

		/**
		 * Constructor
		 *
		 * @since   1.0.0
		 */
		public function __construct() {}

		/**
		 * The Widget content.
		 * 
		 * @since    1.0.0
		 */
		public function dashboard_widget() {

			$links = array();
			$list = array(
				'facebook' => array(
					'url'   => 'http://facebook.com/wpmovielibrary',
					'title' => 'Facebook',
					'icon'  => 'dashicons dashicons-facebook-alt'
				),
				'twitter' => array(
					'url'   => 'https://twitter.com/WPMovieLibrary',
					'title' => 'Twitter',
					'icon'  => 'dashicons dashicons-twitter'
				),
				'google' => array(
					'url'   => 'https://www.google.com/+Wpmovielibraryplugin',
					'title' => 'Google+',
					'icon'  => 'dashicons dashicons-googleplus'
				)
			);

			foreach ( $list as $slug => $data )
				$links[] = sprintf( '<li><a href="%s"><span class="%s"></span><span class="link">%s</span></a></li>', $data['url'], $data['icon'], $data['title'] );

			$links = implode( '', $links );

			echo self::render_template( '/dashboard-vendor/vendor.php', array( 'links' => $links ) );
		}

		/**
		 * Widget's configuration callback
		 * 
		 * @since    1.0.0
		 * 
		 * @param    string    $context box context
		 * @param    mixed     $object gets passed to the box callback function as first parameter
		 */
		public function dashboard_widget_handle( $context, $object ) {}

	}

endif;