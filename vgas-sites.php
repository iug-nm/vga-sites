<?php

/*
Plugin Name: VGA Sites+
Plugin URI:   https://www.valdegaronne.fr
Description:  Un plugin personnalisé intégrant plusieurs petites fonctionnalités pour sécurier et améliorer les sites internets de l'agglomération
Requires at least: 5.8
Requires PHP: 7.0
Version:      1.2.0
Author:       <a target="_blank" href="mailto:gmanchon@vg-agglo.com">Guilhem Manchon</a> | <a href="./admin.php?page=vga-sites%2Fincludes%2Fvgas-settings.php">Réglages</a>
License:      GPL-2.0-or-later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  vga
*/

if (!defined('ABSPATH')) {
    exit;
}

function randvers() {
    return rand(11, 9999);
}

add_action('init', function () {

    foreach(Modules::get_modules() as $r) {
        // Suppression de vga-
        $slug = substr($r->option_name, 4);
        if ($r->option_value == 'true' && !WP_Block_Type_Registry::get_instance()->get_registered('vga/'.$slug)) {
            try {
                register_block_type(__DIR__.'/build/'.strtolower($slug));
            } catch (Error $e) {}

            switch($slug) {
                case "accordion":
                    // On enregistre l'enfant du bloc accordeon si celui-ci est activé
                    register_block_type(__DIR__.'/build/'.strtolower($slug.'-item'));
                    // On lie le script js permettant de controler l'ouverture de l'accordeon
                    wp_register_script('vga-accordion-js', plugin_dir_url(__FILE__).'build/accordion/script.js', null, '0.1.0', true);
                    break;
                case "touslesarticles":
                        // do_shortcode("[touslesarticles tag='category' slug='non-classe']");
                        add_shortcode("touslesarticles", function ($atts) {
                            $atts = shortcode_atts(array(
                                'tag' => 'category',
                                'slug' => 'non-classe'
                            ), $atts, 'touslesarticles');
                        
                            foreach(Post::get_posts_by_tag_slug(filter_var($atts['tag']), filter_var($atts['slug'])) as $post) {
                                echo "<div>".$post->post_title."</div>";
                            }
                        });
                    break;
            }

        } else if($r->option_value == 'false') { // && not registered
            if (WP_Block_Type_Registry::get_instance()->get_registered($slug)) {

                
                try {
                    unregister_block_type('vga/'.$slug);
                } catch (Error $e) {}

                switch ($slug) {
                    case "accordion":
                        // unregister l'accordion enfant le script et le shortcode pour alléger le chargement de la page
                        unregister_block_type('vga/accordion-item');
                        wp_deregister_scripts('vga-accordion.js');
                        break;
                }
            }
        }
    }
});

require_once plugin_dir_path(__FILE__).'includes/vgas-functions.php';

define('VGA_VERSION', '1.2.5');
define('VGA_FILE_VERSION', randvers());
define('VGA_PLUGIN_NAME', 'Val de Garonne développement');