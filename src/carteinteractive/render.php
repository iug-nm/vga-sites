<!-- <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script> -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link media="all" rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<!-- tentative de lazy loading sur les scripts  -->
<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

    /*
        On est obligé de reproduire toute la manœuvre d'affichage de la carte comme nous le faison sur le panel administrateur
        TODO: Pourquoi ne pas directement intégré cette manœuvre dans les paramètres du bloc React ? quelles seraient les contraintes
    */
    
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

foreach($markers_list as $marker) {
    array_push($my_json_array['markers'], [
         "id" => $marker['id'],
         "latitude" => $marker['latitude'],
         "longitude" => $marker['longitude'],
         "title" => $marker['title'],
         "content" => $marker['content'],
    ]);
}
$json = json_encode($my_json_array, JSON_FORCE_OBJECT);
?>
<div id="map-bg"></div>
<script type="text/javascript">
    var json_obj = jQuery.parseJSON('<?php echo $json; ?>');

    const markers_test = Object.entries(json_obj.markers);
    const tileLayer = "https://tile.openstreetmap.org/{z}/{x}/{y}.png";

    var map = L.map('map-bg').setView([json_obj.map.latitude, json_obj.map.longitude], 14);

    for (let i = 0; i < markers_test.length; i++) {
        try {
            let m = L.marker(
                [
                    markers_test[i][1].latitude,
                    markers_test[i][1].longitude,
                ], {
                    title: markers_test[i][1].title,
                    alt: "Marqueur indiquant : " + markers_test[i][1].title
                }
            )
            .bindPopup(
                "<h4>" 
                + markers_test[i][1].title
                + "</h4><p>"
                + markers_test[i][1].content
                + "</p>"
            )
            .addTo(map);
        } catch(e) {
            console.warn(e);
        }
    }

    // Ajout d'un lazy load sur toutes les tiles de la map, pour éviter de les charger si les utilisateurs ne descendent pas en bas
    L.tileLayer(tileLayer, {
        maxZoom: 91,
        attribution: '&copy; <a target="_blank" href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).on('tileloadstart', (event) => {
        event.tile.setAttribute('loading', 'lazy');
    }).addTo(map);

    setInterval(() => {
        map.invalidateSize();
    }, 100);
</script>
<style> .leaflet-marker-shadow, .leaflet-marker-icon {background: transparent !important;} #main-content {padding-bottom: 1rem;} #map-bg {height:400px;border-radius:10px;z-index:0;} </style>