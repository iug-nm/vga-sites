<?php (!defined('ABSPATH') ? exit : null);

$sites = "vga-sites/includes/";

add_action('admin_menu', function () {

    $svg = '<svg height="20" width="20" viewBox="0 0 576 512"><path fill="black" d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>';
    $svg_encoded = base64_encode($svg);

    add_menu_page(
        'Menu',
        'VGA Sites+', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'vga-sites/includes/vgas-main.php', // The 'slug' - file to display when clicking the link
        null,
        // 'data:image/svg+xml;base64, '.$svg_encoded
    );
    
    //Si le module carte est activé :
    if (Modules::get_module('carteinteractive')[0]->option_value == 'true') {
        add_submenu_page(
            'vga-sites/includes/vgas-main.php', //slug de la page parente
            'Carte',
            'Carte',
            'manage_options',
            'vga-sites/includes/vgas-map.php', //slug de la page de sous menu
            '',
            null     
        );
    } else {
        remove_submenu_page(
            'vga-sites/includes/vgas-main.php',
            'vga-sites/includes/vgas-carteinteractive.php',
        );
    }
    // if (Modules::get_module('autopost')[0]->option_value == 'true') {
    //     add_submenu_page(
    //         'vga-sites/includes/vgas-main.php',
    //         'Facebook',
    //         'Facebook',
    //         'manage_options',
    //         'vga-sites/includes/vgas-autopost.php',
    //         '',
    //         null
    //     );
    // } else {
    //     remove_submenu_page(
    //         'vga-sites/includes/vgas-main.php',
    //         'vga-sites/includes/vgas-autopost.php',
    //     );
    // }

    // //Ensemble de fonctions permettant d'ajouter une catégorie personalisée pour les blocks de vga
    function register_block_categories($existingCategories) {
        $customCategories = [
            [
                'slug' => 'vga',
                'title' => 'Val de Garonne Agglomération',
            ],
        ];

        return array_merge($customCategories, $existingCategories);
    }

    global $wp_version;

    add_filter(
        'block_categories'.(version_compare($wp_version, '5,8', '>=') ? '_all' : ''),
        'register_block_categories',
        99
    );
});

add_action('activate_plugin', function () {
    // insertion des paramètres suivant dans la bdd pour init
    global $wpdb;
    $modules = [
        'accordion',
        'equipes',
        'carteinteractive',
        'touslesarticles',
        'plandusite',
        // 'autopost',
    ];
    
    foreach ($modules as $m) {
        //si le tuple n'existe pas déjà (reactivation du plugin)
        if (!Modules::get_module($m)) {
            $wpdb->insert($wpdb->prefix."options", [
                'option_id' => '',
                'option_name' => 'vga-'.$m,
                'option_value' => 'false',
                'autoload' => 'no',
            ]);
        }
    }

    //On créer la table relative à la carte interactive et on insère des données dans celle-ci
    Map::create_map_tables();
    Map::populate_map_tables();

    // On met à jour la structure du permalien
    Utils::update_structure();
});

class Utils {

    /**
     * Fournit une méthode simple de créer un lien jusqu'à l'extension
     * 
     * @return string
     */
    public static function admin_page($slug) {
        return "admin.php?page=vga-sites/includes/".$slug;
    }


    /**
     * Détermine si le string passé en paramètre est bien de type float ou non (en son sein)
     * 
     * @param string $float
     * 
     * @return boolean
     */
    public static function is_float_from_string(String $float) {
        //On récupère les coordonnées sous forme de string, on les renvoie en mode float et on teste si ceux ci appartiennent bien à ce type
        //Sinon on renvoie false (là ou la fonction peut renvoyer true à 0 alors qu'il n'y a rien)
        $float_val = floatval($float);
        if ($float_val != 0 || $float_val != null) {
            return is_float($float_val);
        } else {
            return false;
        }
    }

    /**
     * Remplace les apostrophe par des caractères unicode
     * 
     * @param string $string
     * @param boolean $operation
     * 
     * @return string
     */
    public static function clean_string(String $string, bool $operation) {
        if ($operation) {
            // On encode le texte (' -> &#39;)
            $string = str_replace("'", "&#39;", $string);
        } else {
            $string = str_replace("&#39;", "'", $string);
        }
        return stripslashes($string); // Stripslashes permet de retirer les \ ajoutés automatiquement par le textarea ?, sachant que le string est deja clean par d'autre fonctions
    }

    /**
     * Met à jour la structuration des liens et la nomenclature des nommage
     */
    public static function update_structure() {
        global $wpdb;

        // Possible de faire une update en bulk ?
        $wpdb->update("{$wpdb->prefix}options", [
            "option_value" => "/%category%/%postname%/"
        ], [
            "option_name" => "permalink_structure"
        ]);

        $wpdb->update("{$wpdb->prefix}options", [
            "option_value" => "/etiquette"
        ], [
            "option_name" => "tag_base"
        ]);

        $wpdb->update("{$wpdb->prefix}options", [
            "option_value" => "/categorie"
        ], [
            "option_name" => "category_base"
        ]);
    }

    /**
     * Parcours les informations de la variable $_SERVER pour récupérer l'ip du client avec ou sans proxy
     * 
     * @return string
     */
    public static function get_client_ip() {
        foreach([
            // PArcours de tous les types d'adresses que le server peut renvoyer
            "HTTP_CLIENT",
            "HTTP_X_FORWARDED_FOR",
            "HTTP_X_FORWARDED",
            "HTTP_X_CLUSTER_CLIENTIP",
            "HTTP_FORWARDED_FOR",
            "HTTP_FORWARDED",
            "REMOTE_ADDR"
        ] as $key) {
            // On check que l'un des membres du foreach existe dans server, pour en extraire l'ip
            if (array_key_exists($key, $_SERVER) === true) {
                // Si scinde tous les strings qu'on récupère pour les tester individuellements
                foreach(explode(",", $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                    !== false)) {
                        return $ip;
                    }
                }
            }
        }
    }

    /**
     * Renvoie un string sous format d'heure, la fonction accorde automatiquement le pluriel des valeurs et l'affichage des valeurs qui correspondent à 0
     * 
     * @param integer $seconds
     * @return string
     */
    public static function seconds_to_time($seconds) {
        $d = [
            "hours" => floor($seconds / 3600),
            "minutes" => floor(($seconds % 3600) / 60)
        ];

        if ($d["hours"] <= 0) {
            $res = (($d["minutes"] == 1) ? $d["minutes"]." minute" : $d["minutes"]." minutes");
        } else if ($d["minutes"] <= 0) {
            $res = (($d["hours"] == 1) ? $d["hours"]." heure" : $d["hours"]." heures");
        } else {
            $res = (($d["hours"] == 1) ? $d["hours"]." heure " : $d["hours"]." heures ")." et ".(($d["minutes"] == 1) ? $d["minutes"]." minute" : $d["minutes"]." minutes");
        }
        return $res;
    }
}

class Modules {

    public static function get_modules() {
        global $wpdb;
        $res = $wpdb->get_results(
            "SELECT *
            FROM `{$wpdb->prefix}options` 
            WHERE `option_name`
            LIKE 'vga-%'
            ", OBJECT);

        if (!empty($res)) {
            return $res;
        }
    }

    public static function get_modules_and_params() {
        global $wpdb;
        $res = $wpdb->get_results(
            "SELECT *
            FROM `{$wpdb->prefix}options` 
            WHERE `option_name`
            REGEXP 'vga-|sss-'
            ", OBJECT);

        if (!empty($res)) {
            return $res;
        }
    }

    public static function get_module($name) {
        global $wpdb;
        $res = $wpdb->get_results(
        "SELECT *
        FROM `{$wpdb->prefix}options`
        WHERE `option_name`
        LIKE '%{$name}%'
        ", OBJECT);

        return $res;
    }

    public static function update_module() {
        global $wpdb;
        $res = Modules::get_modules_and_params();

        if (!empty($_POST)) {
            foreach ($res as $r) {
                $wpdb->update($wpdb->prefix."options", array(
                    'option_value' => (isset($_POST[$r->option_name]) && ($_POST[$r->option_name] == 'on') ? 'true' : 'false'),
                ), array('option_id' => $r->option_id));
            }
        }
    }
}

class Post {
    /**
     * Retourne une page unique
     * 
     * @return array
     */
    public static function get_page($params = []) {
        global $wpdb;
        $r = null;
        //on joint les paramètres par défault avec ceux entrées depuis la fonction
        $params = array_merge($params, [
            "post_type" => "page",
            "post_status" => "publish",
        ]);

        // if (!is_array($res)) { //??????
            foreach ($params as $qp_col => $qp_value) {
                // Si c'est le premier paramètre, on ne met pas de AND
                if ($qp_col !== array_key_first($params)) {
                    $r .= " AND ";
                }
                $r .= "`$qp_col` LIKE '%$qp_value'";
            }
            $res = $wpdb->get_results("SELECT p.ID, p.guid, p.post_title FROM {$wpdb->prefix}posts AS p WHERE $r", OBJECT);
        // }
        return $res;
    }

    /**
     * Récupère toutes les catégories et les publications pour créer un plan du site
     * 
     * @return string
     */
    public static function sitemap() {
        $autre = array();
        $sitemap = "<div class='sitemap' style='display:flex;flex-wrap:wrap;width:100%;'>";

        foreach (Post::get_page(["post_parent" => 0]) as $page) {

            // Si la page ne possède pas d'enfant on la stocke dans un array $autre pour l'afficher dans une rubrique h3 Autres
            if (get_pages(array("child_of" => $page->ID))) { 
                $sitemap .= "<ul class='sitemap-category'><h3 class='sitemap-category-title'>$page->post_title</h3>";
        
                //On stocke l'itération précedente pour comparer l'id du petit fils et ne pas l'afficher deux fois
                $previous = array(); 
        
                //on récupère les pages enfants de niveau 1
                foreach (get_pages(array( 
                    "child_of" => $page->ID
                )) as $el) {
        
                    if (!in_array($el->ID, $previous)) {
                        $sitemap .= "<li><a href='$el->guid'>$el->post_title</a></li>";
                    }
        
                    // Si la page enfant possède des enfants de niveau 2 on les affiches en priorité par rapport à la suite
                    $childs = get_pages(array(
                        "child_of" => $el->ID
                    ));
        
                    if ($childs) {
                        $sitemap .= "<ul class='children'>";
                        foreach ($childs as $c) {
                            array_push($previous, $c->ID);
                            $sitemap .= "<li><a href='$c->guid'>$c->post_title</a></li>";
                        }
                        $sitemap .= "</ul>";
                    }
                }
                $sitemap .= "</ul>";
            } else {
                array_push($autre, $page);
            }
        }
        
        // On regarde si la liste est vide
        if ($autre) { 
            $sitemap .= "<ul class='sitemap-category'><h3 class='sitemap-category-title'>Autres</h3>";
            // On parse le tableau des pages sans parents pour les afficher
            foreach ($autre as $a) {
                $sitemap .= "<li><a href='$a->guid'>$a->post_title</a></li>";
            }
            $sitemap .= "</ul>";
        }
        $sitemap .= "</div>";
        return $sitemap;
    }

    /**
     * Retournes tous les posts (ni article ni evenements) et les affiche (echo) sous forme de liste avec les options d'affichage
     * 
     * @param string $orderType
     * @param boolean $click
     * @param array @params
     * @param array $options
     */
    public static function get_posts($orderType, $click, $params = [], $options = []) {
        global $wpdb;
        $select = null;

        foreach($params as $k => $v) {
            if ($v) {
                //On compare la valeur actuelle avec la dernière valeur true du tableau (reverse car il n'existe pas de array_search_last ?!)
                if ($k == array_search(true, array_reverse($params))) {
                    $select .= "p.".$k;
                } else {
                    $select .= "p.".$k.", ";
                }
            }
        }

        //Si $select contient des données autre que null, c'est qu'un parametre est vrai, donc on ajoute la virgule et on l'affiche
        $res = $wpdb->get_results("SELECT p.guid, p.ID".(($select != null) ? ", ".$select : null)." FROM {$wpdb->prefix}posts AS p WHERE post_type = 'post' AND post_status = 'publish' ORDER BY p.post_date {$orderType}", OBJECT);

        echo "<div class='tla-posts'>";
        foreach($res as $r) {
            //on remet le contenu de l'article à jour à chaque nouvelle itération
            $article = null;
            $cats = null;
            $tags = null;

             //avant l'article, afficher la catégorie correspondante à l'article si le paramètre est activé
            if ($options['cats']) {
                foreach(Post::get_posts_tags($r->ID, "category") as $cat) {
                    $cats .= "<span><a href='../categorie/".sanitize_title($cat->name)."'>".$cat->name."</a></span>";
                }
            }
            if ($options['tags']) {
                //foreach des etiquettes et création d'une chaine de string qu'on affiche dans l'article
                foreach(Post::get_posts_tags($r->ID, "post_tag") as $tag) {
                    $tags .= "<span><a href='../etiquette/".sanitize_title($tag->name)."'>#".$tag->name."</a></span>";
                }
            }
            $article .= "<article class='tla-post'>";

            if ($click) {
                $click_element = "<a href='$r->guid' target='_blank' rel='post link'><h4>$r->post_title</h4></a>";
            } else {
                $click_element = "<h4>$r->post_title</h4>";
            }

            $f_event = datefmt_create('fr_FR', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT, 'Europe/Paris', IntlDateFormatter::GREGORIAN, "d MMMM");

            //N'inclure la variable que si elle existe
            (isset($r->post_title)) ? $article .= $click_element : null;
            (isset($r->post_content)) ? $article .= $r->post_content : null;
            (isset($r->post_date)) ? $article .= "<date>".datefmt_format($f_event, strtotime($r->post_date))."</date>" : null;
            ($cats != null) ? $article .= "<div class='tla-post-categories'>$cats</div>" : null;
            ($tags != null) ? $article .= "<div class='tla-post-tags'>$tags</div>" : null;
            $article .= "</article>";
            echo $article;
        }
        echo "</div>";
    }

    /**
     * Retourn toutes les etiquettes correspondant à un post
     * 
     * @param integer $id
     * @param string $type
     */
    public static function get_posts_tags(Int $id, String $type) {
        global $wpdb;

        $res = $wpdb->get_results(
            "SELECT t.name 
            FROM {$wpdb->prefix}terms AS t, {$wpdb->prefix}posts AS p, {$wpdb->prefix}term_taxonomy AS tt, {$wpdb->prefix}term_relationships AS tr 
            WHERE tt.taxonomy = '$type' 
            AND p.post_type = 'post' 
            AND p.post_status = 'publish' 
            AND p.ID = tr.object_id 
            AND tr.term_taxonomy_id = tt.term_taxonomy_id 
            AND tt.term_id = t.term_id 
            AND p.ID = $id");

        return $res;
    }

    /**
     * Retourne tous les posts correspondant à un slug spécifique
     * 
     * @param string $tag
     * @param string $slug
     * 
     * @return array
     */
    public static function get_posts_by_tag_slug(String $tag, String $slug) {
        // Tag : post_tag | category
        global $wpdb;

        $res = $wpdb->get_results(
            "SELECT p.post_title
            FROM {$wpdb->prefix}posts AS p, {$wpdb->prefix}term_relationships AS tr, {$wpdb->prefix}terms AS t, {$wpdb->prefix}term_taxonomy AS tt
            WHERE p.ID = tr.object_id
            AND tr.term_taxonomy_id = tt.term_taxonomy_id
            AND tt.term_id = t.term_id
            AND tt.taxonomy = '".sanitize_title($tag)."'
            AND p.post_type = 'post'
            AND p.post_status = 'publish'
            AND t.slug = '".sanitize_title($slug)."';");

            return $res;
    }

    // public static function get_last_tag() {
    //     global $wpdb;

    //     $res = $wpdb->get_results(
    //         "SELECT MAX(t.term_id), t.name, t.slug
    //         FROM {$wpdb->prefix}terms
    //         AS t");

    //     return $res;
    // }

    // public static function get_tag_type_by_id(Int $id) {
    //     global $wpdb;

    //     $res = $wpdb->get_results(
    //         "SELECT tt.taxonomy
    //         FROM {$wpdb->prefix}term_taxonomy AS tt, {$wpdb->prefix}terms AS t
    //         WHERE tt.term_id = t.term_id
    //         AND t.term_id = $id
    //         AND tt.term_id = $id");

    // }

    // public static function get_tags() {
    //     global $wpdb;

    //     $res = $wpdb->get_results(
    //         "SELECT t.term_id, t.name, tt.taxonomy
    //         FROM {$wpdb->prefix}terms AS t, {$wpdb->prefix}term_taxonomy AS tt
    //         WHERE tt.taxonomy IN ('category', 'post_tag')
    //         AND tt.term_id = t.term_id;");
    //     return $res;
    // }
}

class Map {

    //Obliger d'ajouter "static" devant les variables, sinon nous devrions instancié un objet pour pouvoir y accéder
    protected static $plugin_prefix = "ce_"; // custom extension
    protected static $marmande_latitude = 44.49746979806061;
    protected static $marmande_longitude = 0.16549998285813156;

    /**
     * Création de deux tables accueillant les marqueurs et la carte de l'extension
     */
    public static function create_map_tables() {
        global $wpdb;

        $table_map = $wpdb->prefix.Map::$plugin_prefix."map";
        $table_markers = $wpdb->prefix.Map::$plugin_prefix."markers";

        $ce_map = 
        "CREATE TABLE $table_map (
            id mediumint (9) NOT NULL AUTO_INCREMENT,
            latitude float (15) NOT NULL,
            longitude float (15) NOT NULL,
            title text (20) NOT NULL,
            PRIMARY KEY (id)
        );
        ";

        $ce_markers = 
        "CREATE TABLE $table_markers (
            id mediumint (9) NOT NULL AUTO_INCREMENT,
            latitude float (15) NOT NULL,
            longitude float (15) NOT NULL,
            title text (20) NOT NULL,
            content text (50) NOT NULL,
            PRIMARY KEY (id)
        );
        ";

        try {
            $wpdb->query($ce_map);
            $wpdb->query($ce_markers);
        } catch(Exception $e) {}
    }

    /**
     * Regarde si la table est vide ou non et renvoie un bool
     * 
     * @param string $table nom de la table SQL
     * @return boolean
     */
    public static function is_table_empty($table) {
        global $wpdb;

        try {
            $q = "SELECT * FROM ".$wpdb->prefix.Map::$plugin_prefix.$table;
            $res = $wpdb->get_results($q, ARRAY_A);
        } catch(Exception $e) {}

        if ($res == null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Insertion d'un jeu de données par défaut dans la bdd
     */
    public static function populate_map_tables() {
        global $wpdb;
        $prefix = $wpdb->prefix.Map::$plugin_prefix;

        $lat = Map::$marmande_latitude;
        $long = Map::$marmande_longitude;

        if (self::is_table_empty("map")) { //$this n'est possible que dans un objet instancié
            $wpdb->insert($prefix."map", [
                "id" => 5,
                "latitude" => $lat,
                "longitude" => $long,
                "title" => "Ma carte de Marmande",
            ]);
        }
        
        if (self::is_table_empty("markers")) {
            $wpdb->insert($prefix."markers", [
                "id" => "",
                "latitude" => $lat,
                "longitude" => $long,
                "title" => "Mon premier marqueur",
                "content" => "La description de mon premier marqueur",
            ]);
        }
    }
    
    /**
     * Mise à jour des coordonnées de la carte
     * 
     * @param float $lat latitude
     * @param float $lng longitude
     */
    public static function update_map($lat, $lng) {
        //si le valeurs transmises sont nulles (par exemple quand le $_Post n'est pas lancé, l'update ne se fait pas)
        global $wpdb;

        $lat2 = ($lat !== false) ? $lat : exit;
        $lng2 = ($lng !== false) ? $lng : exit;

        $wpdb->update($wpdb->prefix.Map::$plugin_prefix."map", array(
            "latitude" => $lat,
            "longitude" => $lng,
        ), array("id" => 5) //essayer de récupérer l'id de la carte ?
        );
    }

    /**
     * Récupèration des coordonnées de la carte
     * 
     * @param integer $id identifiant de la carte, qui est à 5 par défaut
     * @return array
     */
    public static function get_map_coordinates($id = 5) {
        global $wpdb;

        $res = $wpdb->get_results(
            "SELECT *
            FROM ".$wpdb->prefix.Map::$plugin_prefix."map
            WHERE id = $id
            ", ARRAY_A);

            if ($res != null) {
                //[0] De cette façon on évite d'avoir une indentation supplémentaire inutile dans le tableau
                return $res[0];
            }
    }

    /**
     * Retourne tous les marqueurs présent dans la bdd
     * 
     * @return array
     */
    public static function get_markers() {
        global $wpdb;

        $res = $wpdb->get_results(
            "SELECT *
            FROM ".$wpdb->prefix.Map::$plugin_prefix."markers
            ", ARRAY_A);

            if ($res != null) {
                return $res;
            }
    }

    /**
     * Mise à jour du marqueur
     * 
     * @param integer $id identifiant du marqueur
     * @param array $params les valeurs du marqueur que nous voulons mettre à jour
     */
    public static function update_markers($id, Array $params) {
        global $wpdb;
        $query = [];

        foreach($params as $p => $v) {
    
            //On ne conserve que les inputs possédants une valeur
            if ($v !== false) {
                $query[$p] = $v;
            } 
        }

        if ($id !== false) {
            $wpdb->update(
                $wpdb->prefix.Map::$plugin_prefix."markers", 
                $query, 
                ["id" => $id]
            );
        }
    }

    /**
     * Supprime le marqueur de la bdd
     * 
     * @param integer $id identifiant du marqueur
     */
    public static function delete_marker($id) {
        global $wpdb;

        //On teste si la valeur envoyée est une confirmation de l'utilisateur et donc un id tangible
        if ($id !== false) {
            //delete marker by id
            $wpdb->delete($wpdb->prefix.Map::$plugin_prefix."markers", array(
                "id" => $id
            ));
        };
    }

    /**
     * Insère un marqueur dans la bdd
     * 
     * @param Array $params Les informations du marqueur (titre, description, coordonnées)
     */
    public static function add_marker(Array $params) {
        global $wpdb;
        $state = true;

        foreach($params as $p) {
            if (!$p) {
                //Si un seul des paramètres du tableau est faux on arrête tout
                $state = false;
            }
        }
        if ($state) {
            $wpdb->insert($wpdb->prefix.Map::$plugin_prefix."markers", $params);
        }
    }
}

class Session {
    /**
     * Classe gérant les sessions
     * 
     * Classe permettant de gérer le partage de sessions, et de limiter l'authentification d'un utilisateur à une seule sessions
     * @link    https://gist.github.com/stephenscaff/eaebcf7a507e47abd47d552f356b546f
     */
    function __construct() {
        add_action("init", [$this, "only_one"]);
    }

    /**
     * Détermine si l'utilisateur actuel possède des sessions concurrentes
     * 
     * @return boolean
     */
    function has_current_session() {
        return (is_user_logged_in() && count(wp_get_all_sessions()) > 1);
    }

    /**
     * Récupère un tableau de sessions de l'utilisateur actuel
     * 
     * @return array
     */
    function get_current_session() {
        $sessions = WP_Session_Tokens::get_instance(get_current_user_id());
        return $sessions->get(wp_get_session_token());
    }

    /**
     * Algo pour détruire les sessions/identifants concurrent(e)s
     */
    function only_one() {
        if (!$this->had_current_session()) {
            return;
        }
        $user_id = get_current_user_id();
        $newest = max(wp_list_pluck(wp_get_all_sessions(), "login"));
        $session = $this->get_current_session();

        if ($session["login"] === $newest) {
            wp_destroy_other_sessions();
        } else {
            wp_destroy_current_session();
        }
    }
}