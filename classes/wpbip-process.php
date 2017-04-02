<?php

if ( ! class_exists( 'Wpbip_Process' ) ) {

	/**
	 * Wpbip Process Class
	 *
	 * @package WP_Background_Image_Processing
	 */

	/**
	 * Background resize processing class for images in WordPress
	 *
	 * @since 1.0.0
	 * @package WP_Background_Image_Processing
	 * @subpackage Image_Editor
	 * @uses WP_Image_Editor Extends class
	 */
	class Wpbip_Process extends WP_Background_Process {

		/**
		 * Background resizing task.
		 *
		 * This gets called on each of the items in the queue.
		 * Return the modified item for further processing
		 * in the next pass through. Or, return false to remove the
		 * item from the queue.
		 *
		 * @param mixed $item Queue item to iterate over
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @return mixed
		 */
		protected function task( $item ) {
			$errored = false;
			$editor  = wp_get_image_editor( $item['file'] );

			if ( ! is_wp_error( $editor ) ) {
				$image = $editor->resize( $item['width'], $item['height'], $item['crop'] );

				if ( ! is_wp_error( $image ) ) {
					$result = $editor->save();

					if ( ! is_wp_error( $result ) ) {
						// Update the image meta
						$meta = wp_get_attachment_metadata( $item['attachment_id'], true );

						$meta['sizes'][ $item['size_name'] ] = array(
							'file'      => $result['file'],
							'width'     => $result['width'],
							'height'    => $result['height'],
							'mime-type' => $result['mime-type'],
						);

						wp_update_attachment_metadata( $item['attachment_id'], $meta );
					} else {
						$errored = true;
					}

				} else {
					$errored = true;
				}
			} else {
				$errored = true;
			}

			/**
			 * Everything was successful - remove from queue
			 */
			if ( false === $errored ) {
				return false;
			}

			/**
			 * If we're here, something errored.
			 *
			 * If we've already tried this image 5 times, remove it.
			 * If not, bump the attempts and add it back onto the queue
			 */
			if ( $item['attempts'] == 5 ) {
				return false;
			}

			$item['attempts'] ++;

			return $item;
		}
	}
}