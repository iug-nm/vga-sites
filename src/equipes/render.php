<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
?>
<div <?php echo get_block_wrapper_attributes(); ?> tabindex=0>
    <?php 
    if (isset($attributes['name']) && $attributes['name']) {
        echo $attributes['name'];
    }
    if (isset($attributes['rank']) && $attributes['rank']) {
        echo $attributes['rank'];
    }
    if (isset($attributes['picture']) && $attributes['picture']) {
        echo wp_get_attachment_image($attributes['picture'], 'full');
    }
?> </div>