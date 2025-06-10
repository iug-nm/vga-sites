<?php echo "<h1>".VGA_PLUGIN_NAME."</h1>"; ?>
<p>Configurez la carte de votre commune ici !</p>
<?php 

    // On récupère les données de la db sous plusieurs formes
    $map_coords = Map::get_map_coordinates();
    $markers_list = Map::get_markers();

    $my_json_array = [
        "map" => [
            "title" => $map_coords['title'],
            "latitude" => $map_coords['latitude'],
            "longitude" => $map_coords['longitude'],
        ],
        "markers" => []
    ];

    // On insère à la suite du tableau markers, présent dans my_json_array les marqueurs qui sont présent dans la bdd
    foreach ($markers_list as $marker) {
        array_push($my_json_array['markers'], [
                "id" => $marker['id'],
                "latitude" => $marker['latitude'],
                "longitude" => $marker['longitude'],
                "title" => $marker['title'],
                "content" => $marker['content']
        ]);
    }
    $json = json_encode($my_json_array, JSON_FORCE_OBJECT);

?> 
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<h2>Glossaire des emoticones</h2>
<div class="glossaire">
    <p><span class="dashicons dashicons-plus-alt2"></span> Ajouter un nouveau marqueur</p>
    <p><span class="dashicons dashicons-admin-links"></span> Insérer un lien</p>
    <p><span class="dashicons dashicons-admin-customizer"></span> Modifier un marqueur</p>
    <p><span class="dashicons dashicons-trash"></span> Supprimer un marqueur</p>
    <p><span class="dashicons dashicons-no-alt"></span> Fermer la fenêtre</p>
    <p><span class="dashicons dashicons-post-status"></span> Géolocalisation</p>
    <p><span class="dashicons dashicons-search"></span> Chercher une adresse pour placer un marqueur</p>
    <p><span class="dashicons dashicons-visibility"></span> Centrer la vue de la carte sur le marqueur que vous visualisez</p>
</div>
<ul id="toast-list"></ul>
<div id="map-container">
    <div id="map-bg"></div>
    <div id="markers">
        <!-- un clic ajoute un nouveau marqueur à la liste -->
        <div>
            <h3>Configurer mes marqueurs</h3>
            <div>
                <a id="marker-link" disabled title="Insérer un lien (pas encore disponible)"><span class="dashicons dashicons-admin-links"></span></a>
                <a id="marker-add" title="Ajouter un nouveau marqueur"><span class="dashicons dashicons-plus-alt2"></span></a>
            </div>
        </div>
        <ul class="markers-list">
            <?php 
            // Nous sommes obligé de mettre la text area en mode valeur car le placeholder ne supportait pas l'apostrophe
            foreach($markers_list as $marker) {
                echo "
                    <li name='".$marker['id']."'>
                        <div class='markers-options'>
                            <p class='marker-title'>".Utils::clean_string($marker['title'], false)."</p>
                            <div class='markers-settings'>
                                <span class='edit dashicons dashicons-admin-customizer' title='Modifier le marqueur'></span>
                                <span class='delete dashicons dashicons-trash' title='Supprimer le marqueur'>
                                    <form method='post' class='delete-form'>
                                        <input name='id-delete' style='display:none' value='".$marker['id']."'/>
                                    </form>
                                </span>
                            </div>
                        </div>
                        <form method='post' class='markers-edit'>
                            <input name='id-edit' style='display:none;' value='".$marker['id']."'/>
                            <input name='title-edit' class='title-edit' style='display:none;' value='".Utils::clean_string($marker['title'], true)."'/>
                            <div class='marker-coords'>
                                <input title='Latitude' name='latitude-edit' placeholder='".$marker['latitude']."'/>
                                <input title='Longitude' name='longitude-edit'  placeholder='".$marker['longitude']."'/>
                                <span class='center dashicons dashicons-visibility' title='Centrer la vue sur le marqueur'></span>
                                <span class='geo dashicons dashicons-post-status' title='Géolocaliser le marqueur sur votre position'></span>
                            </div>
                            <textarea name='content-edit'>".stripslashes(Utils::clean_string($marker['content'], false))."</textarea>
                            ".get_submit_button('Mettre le marqueur à jour', 'primary', 'submit', false, '')."
                        </form>
                    </li>";
            }
            ?>
        </ul>
    </div>
</div>
<form class='map-form' method="post">
    <div id="map-coordinates">
        <label for="lng">Longitude : </label>
        <input readonly onclick="get_map_coords_on_click(this);" type="text" id="lng" name="lng" value="<?php echo $map_coords['longitude'] ?>"/>
        <label for="lat">Latitude : </label>
        <input readonly onclick="get_map_coords_on_click(this);" type="text" id="lat"name="lat" value="<?php echo $map_coords['latitude'] ?>"/>
        <span class='geo dashicons dashicons-post-status' title='Géolocaliser votre position pour centrer la carte'></span>
    </div>
<?php echo get_submit_button("Mettre à jour la carte", "primary large", "submit", false, ""); ?></form>
<script type="text/javascript">

    $.ajax({
        url: "admin?php?page=vga-sites/includes/vgas-map.php",
        dataType: "json",
        success: (data, textStatus, jqXHR) => {
            console.log("ajax response");
        }
    });

    // On transforme l'objet PHP en objet javascript pour pouvoir l'utiliser directement avec Leaflet
    var json_obj = jQuery.parseJSON('<?php echo $json;?>');

    const unsafe_site = "Impossible de réaliser cette action car vous ne naviguez actuellement pas sur un site de confiance";
    const unsafe_title = "🚫 Attention 🚫";

    const toast = (title, content, color) => {
        const main = document.getElementById("toast-list");
        if (main) {
            const toast = document.createElement("li");
            const autoHide = setTimeout(() => {
                $(toast).fadeOut(200, () => {
                    main.removeChild(toast);
                });
            }, 3500);
            toast.classList.add("toast-element", color);
            toast.innerHTML = `<h3>${title}</h3><p>${content}</p>`;
            main.appendChild(toast);
        }
    }

    const markers_test = Object.entries(json_obj.markers);
    const tileLayer = "https://tile.openstreetmap.org/{z}/{x}/{y}.png";

    var map = L.map('map-bg').setView([json_obj.map.latitude, json_obj.map.longitude], 14);

    // Ajout des marqueurs récupérés depuis la base de données
    for (let i = 0; i < markers_test.length; i++) {
        try  {
            L.marker(
                [
                    markers_test[i][1].latitude,
                    markers_test[i][1].longitude,
                ], {
                    title: markers_test[i][1].title,
                    alt: "Marqueur indiquant : " + markers_test[i][1].title,
                    draggable: true
                }
            )
            .bindPopup(
                "<h4>" 
                + markers_test[i][1].title
                + "</h4><span>"
                + markers_test[i][1].content
                + "</span>"
            )
            .on('moveend', (event) => {
                // On remplis les champs du marqueurs avec les valeurs ?
                $('.markers-list .flex .marker-coords input[name="latitude-edit"').val(event.target._latlng.lat);
                $('.markers-list .flex .marker-coords input[name="longitude-edit"').val(event.target._latlng.lng);
            })
            .addTo(map);
        } catch(e) {}
    }

    L.tileLayer(tileLayer, {
        maxZoom: 91,
        attribution: '&copy; <a target="_blank" href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // https://stackoverflow.com/questions/36037178/leaflet-loads-incomplete-map
    setInterval(() => {
        map.invalidateSize();
    }, 100);

    $(document).ready(() => {
        map.on("moveend", (event) => {

            $('#lat').val(map.getCenter().lat);
            $('#lng').val(map.getCenter().lng);

            //pour empêcher l'ouverture du popup par défaut
            return false;
        });

        // On cherche le marqueur ouvert par son titre puis on le rend draggable
        // impossible de ne selectionner qu'un seul marqueur alors ils seront tous draggable en admin
        // $('.leaflet-pane.leaflet-marker-pane > img[title="'+e.target.parentNode.previousElementSibling.placeholder+'"]');
    })

    $('#marker-link').on('cliiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiick', () => { // créer un lien au sein d'un marqueur
        $('#marker-link').before("<div id='marker-link-add'><div><h3>Insérer un lien</h3><span class='dashicons dashicons-no-alt'></span></div><input type='text' placeholder='Text du lien que vous voulez insérer'/><input type='text' placeholder='Addresse du lien que vous voulez insérer'/><div class='option'><label class='toggler-wrapper'><input type='checkbox'/><div class='toggler-slider'><div class='toggler-knob'></div></div></label><span> Voulez-vous que le lien s'ouvre dans une autre page ?</span></div><button>Insérer le lien ..</button></div>");

        $('#marker-link-add .dashicons').on('click', (e) => {
            $(e.target.parentNode.parentNode).remove();
        })

        $('#marker-link-add button').on('click', () => {
            console.log('insert');
        });
    });

    $('#marker-add').on('click', () => {
        let tempMarker = null;

        if (!$('#markers > ul > li:first-child').hasClass("new-marker")) {
            $('#markers > ul > li:first-child').before("<li class='new-marker'><div class='marker-add-delete'><h4>Ajouter un nouveau marqueur</h4><span class='dashicons dashicons-no-alt'></span></div><form method='post' class='markers-add'><input required name='title-add' placeholder='Titre'/><div class='marker-coords'><input title='Latitude' name='latitude-add' placeholder='Latitude'/><input title='Longitude' name='longitude-add'  placeholder='Longitude'/><span class='geo dashicons dashicons-post-status' title='Géolocaliser le marqueur sur votre position (ne marche que si vous autorisez la géolocalisation, sinon utiliser les coordonnées sous la carte)'></span><span class='address-search dashicons dashicons-search' title='Rechercher une position à l&#39;aide d&#39;une adresse'></span></div><textarea required name='content-add' placeholder='Description ..'></textarea><input type='submit' class='button button-primary' name='submit' value='Ajouter le marqueur'/></form></li>");
        
            // On scroll jusqu'au nouvel element pour l'avoir en visu
            $('.markers-list').animate({
                scrollTop: $('.markers-list .new-marker')
            }, 1000);

            //Nous sommes obligé de créer l'écouteur d'évènement dans la même fonction car l'element n'existe pas sinon
            $('.marker-add-delete span').on('click', (e) => {
                $(e.target.parentNode.parentNode).remove();
                if (tempMarker !== null) {
                    map.removeLayer(tempMarker);
                }
            });

            $('.new-marker .geo').on('click', (e) => {
            //Geolocalisation pour récupérer les coordonnées pour ajouter un marqueur
                if (window.isSecureContext) {
                    navigator.geolocation.getCurrentPosition((position) => {
                        $(e.target.parentNode.children[0]).val(position.coords.latitude);
                        $(e.target.parentNode.children[1]).val(position.coords.longitude);

                        // Ajout d'un marqueur temporaire stocké dans tempMarker, qui permet de le supprimé si celui ci ne nous convient pas
                        tempMarker = L.marker(
                            [position.coords.latitude, position.coords.longitude],
                            {
                                title: "Titre", 
                                alt: "Marqueur temporaire",
                                draggable: true
                            }
                        )
                        .bindPopup(
                            "<h4>"
                            +"Titre temporaire"+
                            "</h4><span>"
                            +"Description temporaire"+
                            "</span>"
                        )
                        .on('moveend', (event) => {
                            // On remplis les champs du marqueurs avec les valeurs ?
                            $('.markers-list .new-marker .marker-coords input[name="latitude-edit"').val(event.target._latlng.lat);
                            $('.markers-list .new-marker .marker-coords input[name="longitude-edit"').val(event.target._latlng.lng);
                        })
                        .addTo(map);
                        map.flyTo([position.coords.latitude, position.coords.longitude]);
                    });
                } else {
                    toast(unsafe_title, unsafe_site, "red");
                }
            });

            // https://stackoverflow.com/questions/15919227/get-latitude-longitude-as-per-address-given-for-leaflet
            $('.new-marker .address-search').on('click', () => {
                let pr = prompt("Renseignez l'addresse permettant de localiser le marqueur que vous souhaitez ajouter à la carte");
                if (pr) { // On check si le prompt est vide ou non
                    $.get(location.protocol + '//nominatim.openstreetmap.org/search?format=json&q='+pr, (data) => {

                        if (data !== undefined) {
                            // On parses les villes récupèrées par la recherche openstreet pour créer la liste de resultats ..
                            let towns = "";
                            data.forEach((e) => {
                                towns += "<li class='map-search-result' title='En cliquant vous séléctionnerez les coordonnées du marqueur que vous souhaitez ajouter'><p>"
                                +e.display_name+
                                "</p></li>";
                            })

                            $('.new-marker .marker-coords').before(
                            "<ul class='map-search-results'><div><h3>Sélectionnez l'adresse voulu</h3><span class='map-search-close dashicons dashicons-no-alt'></span></div>"
                            +towns+
                            "</ul>");

                            $('.map-search-result').on('click', (e) => {
                                let townID;
                                // On récupère le display name entier et on le compare avec ce qu'on a dans data pour l'ajouter au marqueur
                                data.forEach((ev) => {
                                    if (ev.display_name === e.currentTarget.innerText) {
                                        // Présenter les options possibles sous formes de dropdown et choisir l'id au clic ?
                                        $('.new-marker .marker-coords input[name="latitude-add"]').val(ev.lat);
                                        $('.new-marker .marker-coords input[name="longitude-add"]').val(ev.lon);
                                        $('.new-marker textarea[name="content-add"]').val(ev.display_name);

                                        // Création d'un marqueur temporaire pour affiner la recherche
                                        tempMarker = L.marker(
                                            [ev.lat,ev.lon],
                                            {
                                                title:"Titre",
                                                alt:"Marqueur temporaire en vue d'un ajout",
                                                draggable: true
                                            }
                                        )
                                        .bindPopup(
                                            "<h4>"
                                            +"Mon Nouveau Marqueur"+
                                            "</h4><span>"
                                            +ev.display_name+
                                            "</span>"
                                        )
                                        .on('moveend', (event) => {
                                            // On remplis les champs du marqueurs avec les valeurs ?
                                            $('.markers-list .new-marker .marker-coords input[name="latitude-edit"').val(event.target._latlng.lat);
                                            $('.markers-list .new-marker .marker-coords input[name="longitude-edit"').val(event.target._latlng.lng);
                                        })
                                        .addTo(map);

                                        map.flyTo([ev.lat, ev.lon], 14);
                                    }
                                });

                                // On supprime l'interface de sélection des addresses ensuite
                                $('.map-search-results').remove();
                            });

                            $('.map-search-close').on('click', () => {
                                $('.map-search-results').remove();
                            });
                        } else {
                            toast('Erreur', "Le service ne semble pas disponnible, veuillez nous excuser pour la gêne occasionnée", "red");
                        }
                    });
                }
            });
        } else {
            toast("Erreur", "Vous ne pouvez pas ajouter plusieurs marqueurs en même temps !", "yellow");
        }
    });

    $('.edit').on('click', (e) => {
        // On récupère la valeur du titre pour l'utiliser plus tard
        let previous_title = e.target.parentNode.parentNode.nextElementSibling.children[1].value;
        if ($(e.target.parentNode.previousElementSibling).is('p')) {
            $(e.target.parentNode.previousElementSibling).replaceWith('<input id="marker-title-edit" class="marker-title" placeholder="'+e.target.parentNode.previousElementSibling.innerText+'"/>');

            $('#marker-title-edit').on('keyup', (ev) => {
                // A chaque changement du champs titre on modifie le paragraphe (ou input) titre au dessus
                $(ev.target.parentNode.parentNode.childNodes[3][1]).val(ev.target.value);
            });
        } else if ($(e.target.parentNode.previousElementSibling).is('input')) {
            $(e.target.parentNode.previousElementSibling).replaceWith("<p class='marker-title'>"+previous_title+"</p>");
            // Suppression de l'écouteur d'évènement
            $('#marker-title-edit').off();
        }
        // Animation de l'apparition du formulaire d'edition
        $(e.target.parentNode.parentNode.nextElementSibling).toggleClass("flex");
    });

    $('.delete').on('click', (e) => {
        if (confirm("Voulez-vous supprimer ce marqueur ?!")) {
            //Submit the deletion form
            $(e.target.childNodes[1]).submit();
        }
    });

    $('#map-coordinates .geo').on('click', () => {
        //Geolocalisation pour centrer la carte principale
        if (window.isSecureContext) {
            navigator.geolocation.getCurrentPosition((position) => {
                //on remplace les valeurs lat & lng et setView par les valeurs qu'on récupère
                
                try {
                    $('#lat').val(position.coords.latitude);
                    $('#lng').val(position.coords.longitude);

                    // On recentre la carte https://stackoverflow.com/questions/12735303/how-to-change-the-map-center-in-leaflet-js
                    map.flyTo([position.coords.latitude, position.coords.longitude], 14);
                } catch (e) {}
            });
        } else {
            toast(unsafe_title, unsafe_site, "red")
        }
        
    });
    $('.markers-edit .geo').on('click', (e) => {
        //Geolocalisation pour récupérer les coordonnées pour modifier un marqueur
        if (window.isSecureContext) {
            navigator.geolocation.getCurrentPosition((position) => {
                $(e.target.parentNode.children[0]).val(position.coords.latitude);
                $(e.target.parentNode.children[1]).val(position.coords.longitude);
                map.flyTo([position.coords.latitude, position.coords.longitude]);
            });
        } else {
            toast(unsafe_title, unsafe_site, "red");
        }
    });

    $('.markers-edit .center').on('click', (e) => {
        map.flyTo([e.target.parentNode.children[0].placeholder, e.target.parentNode.children[1].placeholder], 14);
    });

    const get_map_coords_on_click = (e) => {
        if (window.isSecureContext) {
            navigator.clipboard.writeText(e.value);
            toast("Succès !", "Les coordonnées ont bien été copié dans votre presser papier", "green");
        } else {
            toast(unsafe_title, unsafe_site, "red");
        }
    }
</script>
<style>
    .green {background-color: #d3f4c0;border: 1px solid rgb(160, 209, 132)}
    .yellow {background-color:#fff0b6;border: 1px solid rgb(189, 176, 125)}
    .orange {background-color:#ffcb9e;border: 1px solid rgb(204, 160, 122)}
    .red {background-color:#ffc8c8;border: 1px solid rgb(214, 161, 161)}
    #toast-list {position:absolute;top:0;right:1rem;z-index: 999;}
    .toast-element > * {margin:0.5rem 0}
    .toast-element {width:300px;border-radius:5px;padding:0.1rem 0.5rem;}
    .map-search-results {position:absolute;background:#ddd;padding:0 1rem;border-radius:5px;margin-left:-10px;margin-top:13rem;height:300px;overflow-x:scroll;box-shadow:0 0 50px #bbb}
    .map-search-results > div {display:flex;align-items:center;justify-content:space-between;}
    .map-search-result {background-color:#eee !important;cursor: pointer;}

    .geo, .address-search, .map-search-close, .center {cursor:pointer;}
    .flex {display: flex !important;}
    .glossaire {display:flex;flex-wrap:wrap;gap:1rem;width:75%;}
    .glossaire > p {margin:0;}
    #map-container {display:flex;flex-direction:row;}
    @media screen and (max-device-width: 1024px) {
        #map-container {display:block !important;}
        #map-container #map-bg {width:95% !important;}
        #markers {position:relative !important;left:unset !important;width:fit-content !important;}
        .markers-list {width:fit-content !important;}
        .map-form {flex-direction:column !important;width:95% !important}
        #map-coordinates {display:flex !important;justify-content:space-between !important;}
    }
    #map-bg {height: 400px;width: 50%;margin: 1rem;margin-left: 0;box-shadow:0 0 30px #ccc;}

    #markers {position:absolute;left:52%;}
    #markers > div {display:flex; align-items:baseline;justify-content:space-between;width:96%;}
    #markers li {padding: 0.3rem 1rem; border-radius: 7px; background-color: #FFF;width: 370px}
    
    #markers li .markers-options {display:flex;flex-direction:row;align-items:center;justify-content:space-between;}
    #markers li .markers-options .markers-settings span {color:#2271b1;}
    #markers li .markers-options .markers-settings span:hover,
    #markers #marker-add,
    #markers .marker-add-delete span,
    #marker-link-add > div .dashicons {cursor:pointer;}
    
    #markers li .markers-edit {display:none;align-items:stretch;flex-direction:column;}
    #markers li .markers-edit input,
    #markers li .markers-edit textarea {margin: 5px 0;}
    .marker-title {width: 85%}
    #markers li .markers-edit input:not(input[type="submit"]),
    #markers li .markers-edit textarea,
    input.marker-title,
    #marker-link-add input {border-radius:5px;padding: 0.5rem;margin:5px 0;border:none;outline:none;background-color:#EEE;}
    .markers-edit > .marker-coords, .markers-add .marker-coords {display:flex;align-items:center;justify-content:space-between;gap:0.5rem;}
    .markers-edit > .marker-coords input, .markers-add .marker-coords input {width:45%;}
    .markers-edit > .marker-coords span, .markers-add .marker-coords span {font-size:24px;color:#2271b1}

    #marker-link-add {padding:0.5rem 1rem;display:flex;flex-direction:column;width:20vw;top:2rem;right:2rem;border-radius:7px;background:white;position:absolute;z-index: 999;box-shadow: #00000069 7px 7px 20px 0px;}
    #marker-link-add > div {display:flex;flex-direction:row;align-items:center;justify-content:space-between;}
    #marker-link-add > div > .dashicons {color: #2271b1}
    #marker-link-add button {color: #FFF;background:#2271b1;outline:none;border:none;border-radius:3px;padding:0.4rem;margin-top:10px;}
    #marker-link-add button:hover {cursor:pointer;background-color:#0A4B78;}
     
    #markers #marker-link {cursor:not-allowed;opacity:0.3;}
    #markers .markers-list .new-marker .markers-add {display:flex;flex-direction:column;align-items:stretch;}
    #markers li .markers-add input[type="submit"] {margin: 5px 0;}
    #markers li .markers-add input:not(input[type="submit"]),
    #markers li .markers-add textarea {border-radius:5px;padding: 0.5rem;margin:5px 0;border:none;outline:none;background-color:#EEE;}
    #markers .marker-add-delete {display:flex;flex-direction:row;justify-content:space-between;align-items:center}
    #markers .marker-add-delete span {color:#2271b1;}

    .map-form {display:flex;flex-direction: row-reverse;width:50%;justify-content: space-between;}
    #map-coordinates {display:flex;align-items: center;justify-content:right;}
    #map-coordinates span {padding:0 0.5rem;color: #2271b1;}
    #map-coordinates label {margin: 2px 5px;}
    #map-coordinates input {border:none;outline:none;background-color:#EEE;width:79px;cursor:copy;}
    #submit {height:fit-content;}

    .markers-list {overflow-y:scroll;/*height:38.3vh*/height:352px;padding-right:15px;}

    
    .toggler-wrapper {display: block;width: 45px;height: 25px;cursor: pointer;position: relative;margin-bottom: 7px;}
    .toggler-wrapper input[type="checkbox"] {display: none;}
    .toggler-wrapper .toggler-slider {background-color: #ccc;position: absolute;border-radius: 100px;top: 0;left: 0;width: 100%;height: 100%;-webkit-transition: all 300ms ease;transition: all 300ms ease;}
    .toggler-wrapper .toggler-knob {position: absolute;-webkit-transition: all 300ms ease;transition: all 300ms ease;}
    .toggler-wrapper input[type="checkbox"]:checked+.toggler-slider {background-color: white;}
    .toggler-wrapper input[type="checkbox"]:checked+.toggler-slider .toggler-knob {left: calc(100% - 19px - 3px);background-color: #2271b1;/* background-image: url(../img/check-fill.svg); */}
    .toggler-wrapper .toggler-slider {background-color: white;-webkit-box-shadow: 2px 4px 8px rgba(200, 200, 200, 0.5);box-shadow: 2px 4px 8px rgba(200, 200, 200, 0.5);border-radius: 5px;}
    .toggler-wrapper .toggler-knob {width: calc(25px - 6px);height: calc(25px - 6px);border-radius: 5px;left: 3px;top: 3px;background-color: #ccc;}
    .option {display:flex;flex-direction:row;gap:0.5rem;}

</style>
<?php

    //Test des variables post des formulaires editions pour marqueurs
    $edit_id = (isset($_POST['id-edit']) && $_POST['id-edit'] !== '') ? $_POST['id-edit'] : false;
    $edit_title = (isset($_POST['title-edit']) && $_POST['title-edit'] !== '') ? $_POST['title-edit'] : false;
    $edit_lat = (isset($_POST['latitude-edit']) && $_POST['latitude-edit'] !== '') ? $_POST['latitude-edit'] : false;
    $edit_lng = (isset($_POST['longitude-edit']) && $_POST['longitude-edit'] !== '') ? $_POST['longitude-edit'] : false;
    $edit_content = (isset($_POST['content-edit']) && $_POST['content-edit'] !== '') ? $_POST['content-edit'] : false;

    if (is_int($edit_id) || is_string($edit_title) || is_double($edit_lat) || is_double($edit_lng) || is_string($edit_content)) {
        Map::update_markers($edit_id, [
            "latitude" => $edit_lat,
            "longitude" => $edit_lng,
            "title" => Utils::clean_string($edit_title, true),
            "content" => Utils::clean_string($edit_content, true),
        ]);
    }

    //Test de la variable pour suppression marqueurs
    Map::delete_marker((isset($_POST['id-delete']) && $_POST['id-delete'] !== '') ? $_POST['id-delete'] : false);
    
    //Test des variables pour l'ajout des marqueurs
    $add_title = (isset($_POST['title-add']) && $_POST['title-add'] !== '') ? $_POST['title-add'] : false;
    $add_lat = (isset($_POST['latitude-add']) && $_POST['latitude-add'] !== '') ? $_POST['latitude-add'] : false;
    $add_lng = (isset($_POST['longitude-add']) && $_POST['longitude-add'] !== '') ? $_POST['longitude-add'] : false;
    $add_content = (isset($_POST['content-add']) && $_POST['content-add'] !== '') ? $_POST['content-add'] : false;

    if (is_string($add_title) && is_double($add_lat) && is_double($add_lng) && is_string($add_content)) {
        Map::add_marker([
            "title" => Utils::clean_string($add_title, true),
            "latitude" => $add_lat,
            "longitude" => $add_lng,
            "content" => Utils::clean_string($add_content, true)
        ]);
    }
    
    //Si la variable post de la carte existe on l'assigne, sinon on assigne rien, test du rien dans la fonction
    $update_lat = (isset($_POST['lat']) && Utils::is_float_from_string($_POST['lat'])) ? strip_tags($_POST['lat']) : false;
    $update_lng = (isset($_POST['lng']) && Utils::is_float_from_string($_POST['lng'])) ? strip_tags($_POST['lng']) : false;

    if (Utils::is_float_from_string($update_lat) && Utils:: is_float_from_string($update_lng)) {
        Map::update_map($update_lat, $update_lng);
    }
?>