<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

Post::get_posts($attributes['orderType'], $attributes['click'], [
    "post_title" => $attributes['displayTitle'],
    "post_content" => $attributes['displayContent'],
    "post_date" => $attributes['displayDate'],
], [
    "tags" => $attributes['displayTags'],
    "cats" => $attributes['displayCategories'],
]);

// les attributs ne sont pas initialisés dès l'insertion du bloc donc ça peut faire buguer la requête