<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://enshrined.co.uk
 * @since             1.0.0
 * @package           WP_Background_Image_Processing
 *
 * @wordpress-plugin
 * Plugin Name:       WP Background Image Processing
 * Plugin URI:        https://enshrined.co.uk
 * Description:       Handle image resizing in the background rather than on upload, reducing the load on the WP Upload handler
 * Version:           1.0.0
 * Author:            Daryll Doyle
 * Author URI:        https://enshrined.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpbip
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Wpbip' ) ) {
	/**
	 * Class Wpbip
	 *
	 * @since 1.0.0
	 */
	class Wpbip {

		/**
		 * The wpbip queue
		 *
		 * @var null
		 */
		protected $wpbip_queue = null;


		/**
		 * wpbip constructor.
		 *
		 * Add our filters
		 */
		public function __construct() {
			require 'classes/wp-async-request.php';
			require 'classes/wp-background-process.php';
			require 'classes/wpbip-process.php';

			$this->wpbip_queue = new Wpbip_Process();

			add_filter( 'intermediate_image_sizes_advanced', array( $this, 'wpbip_queue_bg_resizing' ), 999, 2 );
		}

		/**
		 * Skip automatic resizing of WordPress images and queue background resizing.
		 *
		 * @param $sizes
		 * @param $metadata
		 *
		 * @since 1.0.0
		 *
		 * @return bool
		 */
		public function wpbip_queue_bg_resizing( $sizes, $metadata ) {
			$upload_dir    = wp_get_upload_dir();
			$file_path     = sprintf( '%s/%s', $upload_dir['basedir'], $metadata['file'] );
			$file_url      = sprintf( '%s/%s', $upload_dir['baseurl'], $metadata['file'] );
			$attachment_id = $this->get_image_id_from_url( $file_url );

			foreach ( $sizes as $size => $size_data ) {

				if ( ! isset( $size_data['width'] ) && ! isset( $size_data['height'] ) ) {
					continue;
				}

				if ( ! isset( $size_data['width'] ) ) {
					$size_data['width'] = null;
				}
				if ( ! isset( $size_data['height'] ) ) {
					$size_data['height'] = null;
				}

				if ( ! isset( $size_data['crop'] ) ) {
					$size_data['crop'] = false;
				}

				$this->wpbip_queue->push_to_queue( array(
					'attachment_id' => $attachment_id,
					'file'          => $file_path,
					'width'         => $size_data['width'],
					'height'        => $size_data['height'],
					'crop'          => $size_data['crop'],
					'size_name'     => $size,
					'attempts'      => 0,
				) );
			}

			$this->wpbip_queue->save()->dispatch();

			return false;
		}

		/**
		 * Get the attachment ID from it's URL
		 *
		 * @param $image_url
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @return mixed
		 */
		protected function get_image_id_from_url( $image_url ) {
			global $wpdb;
			$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';",
				$image_url ) );

			return $attachment[0];
		}
	}
}

new Wpbip();