<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
?>
<div <?php echo get_block_wrapper_attributes(); ?> tabindex=0>
    <?php 
    if (isset($attributes['name']) && $attributes['name']) {
        echo "<h4>".$attributes['name']."</h4>";
    }
    if (isset($attributes['rank']) && $attributes['rank']) {
        echo "<p>".$attributes['rank']."</p>";
    }
    if (isset($attributes['picture']) && $attributes['picture']) {
        echo wp_get_attachment_image($attributes['picture'], 'full');
    }
?> </div>