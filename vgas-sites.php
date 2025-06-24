<?php

/*
Plugin Name: VGA Sites+
Plugin URI:   https://www.valdegaronne.fr
Description:  Un plugin personnalisé intégrant plusieurs petites fonctionnalités pour sécurier et améliorer les sites internets de l'agglomération
Requires at least: 5.8
Requires PHP: 7.0
Version:      1.5.6
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

$maximum_tries = 3;
$lock_duration = 1800;

$old_path = "wp-login.php";
$new_path = "wp-connexion.php";

function prevent_login_attempts() {
    global $maximum_tries;
    global $lock_duration;

    $duree = Utils::seconds_to_time($lock_duration);
    $ip = get_transient("vga_attempted_login_block_ip_".Utils::get_client_ip());

    if ($ip && $ip["ip"] === Utils::get_client_ip()) {
        // On vide les données du formulaire pour empêcher une re soumissions de celui-ci
        if (!empty($_POST) && $_SERVER["REQUEST_METHOD"] == "POST") {
            unset($_POST['pwd']);
            unset($_POST['log']);
        }

        wp_die("Trop de tentatives de connexions effectuées, vous êtes bloqués pendant {$duree}");
    }
}

function limit_login_attempts() {
    global $lock_duration;
    global $maximum_tries;

    // On récupère le nombre d'essais que nous avons enregistré sur cette ip
    $duree = Utils::seconds_to_time($lock_duration);
    $data = get_transient('vga_attempted_login_'.Utils::get_client_ip()) ?: ["tried" => 0];
    $data["tried"]++;

    if ($data["tried"] >= $maximum_tries) {
        echo "<div style='margin:1rem;' class='notice notice-error'><p>Vous avez dépassé le nombre de tentatives de connexions autorisées ({$maximum_tries}), ressayez dans {$duree}</p></div>";
        // Si les tentatives réalisées sur cette ip excèdent le nombre max on créer un transient avec l'ip pour dire de ne pas accepter de tentatives de sa part
        set_transient("vga_attempted_login_block_ip_".Utils::get_client_ip(), ["ip" => Utils::get_client_ip()], $lock_duration);
    }
    set_transient("vga_attempted_login_".Utils::get_client_ip(), $data, $lock_duration);
}

function prevent_iframe_trap() {
    header("X-FRAME-OPTIONS: DENY");
    header("Content-Security-Policy: frame-ancestors 'none'", true);
}

function prevent_multiple_sessions() {
    new Session();
}

function hide_logout_redirect () {
    wp_redirect(home_url());
    exit();
}

function hide_logout_url () {
    global $new_path;
    // create nonce permet de confirmer l'origine de la requete de deconnexion, et donc de ne pas demander de confirmation
    return home_url("/{$new_path}?action=logout&_wpnonce=".wp_create_nonce("log-out"));
}

function hide_lostpassword () {
    global $new_path;
    return home_url("/{$new_path}?action=lostpassword");
}

function vga_hide_login() {
    global $old_path;
    global $new_path;

    //récupération du contenu de wp-login.php, on search replace les valeurs et on les insères dans un nouveau fichier
    $old_file = file_get_contents("../".$old_path);
    $new_file = fopen("../".$new_path, "w");
    
    fwrite($new_file, str_replace($old_path, $new_path, $old_file));
    fclose($new_file);

    rename("../".$old_path, "../wp-old-login.php");
}

add_action('init', function () {

    global $old_path;
    global $new_path;

    foreach(Modules::get_modules_and_params() as $r) {
        // Suppression de vga- ou sss-
        $slug = strtolower(substr($r->option_name, 4));
        if ($r->option_value == 'true') {
            switch($slug) {
                case "accordion":
                    // On enregistre l'enfant du bloc accordeon si celui-ci est activé
                    register_block_type(__DIR__.'/build/'.$slug.'-item');
                    // On lie le script js permettant de controler l'ouverture de l'accordeon
                    wp_register_script('vga-accordion-js', plugin_dir_url(__FILE__).'build/accordion/script.js', null, '0.1.0', true);
                    break;
                case "iframe":
                    add_action("send_headers", "prevent_iframe_trap", 10);
                    continue 2;
                case "bruteforce":
                    add_action("wp_authenticate", "prevent_login_attempts");
                    add_action('wp_login_failed', "limit_login_attempts");
                    continue 2;
                case "token":
                    add_action("setup_theme", "prevent_multiple_sessions", 0);
                    continue 2;
                case "version":
                    define('DISALLOW_FILE_EDIT', true);
                    define('DISALLOW_FILE_MODS', true);
                    continue 2;
                case "hide":
                    // if we can find wp-login.php
                    if (file_exists("../".$old_path)) {
                        vga_hide_login();
                    }

                    add_filter("logout_url", "hide_logout_url");
                    add_filter("lostpassword_url", "hide_lostpassword");
                    add_action("wp_logout", "hide_logout_redirect");
                    continue 2;
                // case "touslesarticles":
                //     // ? un shortcode pour faire les pages par défaut mais pas de page créée
                //     // do_shortcode("[touslesarticles tag='category' slug='non-classe']");
                //     add_shortcode("touslesarticles", function ($atts) {
                //         $atts = shortcode_atts(array(
                //             'tag' => 'category',
                //             'slug' => 'non-classe'
                //         ), $atts, 'touslesarticles');
                    
                //         foreach(Post::get_posts_by_tag_slug(filter_var($atts['tag']), filter_var($atts['slug'])) as $post) {
                //             echo "<div>".$post->post_title."</div>";
                //         }
                //     });
                //     break;
            }

            if (!WP_Block_Type_Registry::get_instance()->get_registered('vga/'.$slug)) {
                try {
                    register_block_type(__DIR__.'/build/'.$slug);
                } catch (Error $e) {}
            }
        } else if($r->option_value == 'false') { // = not registered
            if (WP_Block_Type_Registry::get_instance()->get_registered($slug)) {
                
                try {
                    unregister_block_type('vga/'.$slug);
                } catch (Error $e) {}

            }
            switch ($slug) {
                case "accordion":
                    // unregister l'accordion enfant le script et le shortcode pour alléger le chargement de la page
                    unregister_block_type('vga/accordion-item');
                    wp_deregister_scripts('vga-accordion.js');
                    break;
                case "iframe":
                    remove_action("send_headers", "prevent_iframe_trap");
                    break;
                case "bruteforce":
                    remove_action("wp_authenticate", "prevent_login_attempts");
                    remove_action("wp_login_failed", "limit_login_attempts");
                    break;
                case "token":
                    remove_action("setup_theme", "prevent_multiple_sessions");
                    break;
                case "version":
                    define('DISALLOW_FILE_EDIT', false);
                    define('DISALLOW_FILE_MODS', false);
                    break;
                case "hide":
                    if (file_exists("../".$new_path)) {
                        // on supprime wp-connexions.php
                        unlink("../".$new_path);
                        // on renomme wp-login.php dans sa version initiale
                        rename("../wp-old-login.php", "../".$old_path);
                    }

                    remove_filter("logout_url", "hide_logout_url");
                    remove_filter("lostpassword_url", "hide_lostpassword");
                    remove_action("wp_logout", "hide_logout_redirect");
                    break;
            }
        }
    }
});

require_once plugin_dir_path(__FILE__).'includes/vgas-functions.php';

define('VGA_VERSION', '1.3.1');
define('VGA_FILE_VERSION', randvers());
define('VGA_PLUGIN_NAME', 'Val de Garonne développement');