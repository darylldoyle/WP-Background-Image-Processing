# WP Background Image Processing

WP Background Image Processing is a basic plugin that moves than handling of resizing images into a background process.
This hopefully reduces a huge amount of load on the upload handler meaning less timeouts.

Built upon on the [WP Background Processing](https://github.com/A5hleyRich/wp-background-processing) library.

## How it works?

This plugin works by hooking into the `intermediate_image_sizes_advanced` filter which is ran right before WordPress starts resizing images, 
if this filter returns false, images won't be resized.

Knowing this, we can hook in using this filter. Add our image resizing to the queue and then return false, effectively telling WordPress not to resize in the upload script.

After each image size is created from the queue, we update the image metadata so WordPress knows we have the sizes available.