<style>
.option {
    display: flex;
    flex-direction: row;
}
.option span {
    margin-left: 10px;
}
#vgas-submit {
    cursor: pointer;
}
.toggler-wrapper {
	display: block;
	width: 45px;
	height: 25px;
	cursor: pointer;
	position: relative;
    margin-bottom: 7px;
}

.toggler-wrapper input[type="checkbox"] {
	display: none;
}

.toggler-wrapper input[type="checkbox"]:checked + .toggler-slider {
	background-color: #44cc66;
}

.toggler-wrapper .toggler-slider {
	background-color: #ccc;
	position: absolute;
	border-radius: 100px;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	-webkit-transition: all 300ms ease;
	transition: all 300ms ease;
}

.toggler-wrapper .toggler-knob {
	position: absolute;
	-webkit-transition: all 300ms ease;
	transition: all 300ms ease;
}
.toggler-wrapper input[type="checkbox"]:checked+.toggler-slider {
	background-color: white;
}

.toggler-wrapper input[type="checkbox"]:checked+.toggler-slider .toggler-knob {
	left: calc(100% - 19px - 3px);
	background-color: #3281ff;
	/* background-image: url(../img/check-fill.svg); */
}

.toggler-wrapper .toggler-slider {
	background-color: white;
	-webkit-box-shadow: 2px 4px 8px rgba(200, 200, 200, 0.5);
	box-shadow: 2px 4px 8px rgba(200, 200, 200, 0.5);
	border-radius: 50px;
}

.toggler-wrapper .toggler-knob {
	width: calc(25px - 6px);
	height: calc(25px - 6px);
	border-radius: 50px;
	left: 3px;
	top: 3px;
	background-color: #ccc;
}
input[type="checkbox"]:disabled + .toggler-slider {
	opacity: 0.2;
	cursor: not-allowed;
}
</style>
<?php echo "<h1>".VGA_PLUGIN_NAME."</h1>"; ?>
<p>Activez les modules que vous voulez utiliser lors de la création de contenu au sein de votre site internet 
	<br>
	<a href="https://code.destination-valdegaronne.com/guide-wordpress.pdf" download="Guide relatif à l'utilisation de votre site internet" title="En cliquant vous téléchargerez le Tutoriel PDF pour utiliser wordpress ">Télécharger le Guide d'utilisation de Wordpress réalisé par l'Agglomération</a>
</p>
<?php
// On n'execute la fonction que lorsque le formulaire est validé
(isset($_POST['submit'])) ? Modules::update_module() : null;

?> <form method="post" action="">
	<h3>Modules</h3>
<?php
	global $new_path;
	$desc = [
		"equipes" => "Block permettant de présenter un.e membre du conseil municipal",
		"carteinteractive" => "Permet d'afficher une carte ainsi que des marqueurs pour situé les points importants de votre commune",
		"touslesarticles" => "Affiche tous les articles ayant été postés sur votre site internet",
		"plandusite" => "Affiche toutes les catégories et les pages / articles y étant liées",
		"accordion" => "Présentation en accodéon",
		"version" => "Empêche la modification de code directement depuis l'interface administrateur (empêche l'ajout d'extensions)",
		"iframe" => "Empêche votre site d'être embarqué de manière malveillante",
		"bruteforce" => "Bloque un utilisateur pendant 5 minutes au bout de 3 tentatives de connexion",
		"token" => "Empêche la connexion simultané sur un même compte utilisateur sur plusieurs machines différentes",
		"hide" => "Cache l'emplacement de base de l'url de connexion pour empêcher les robots d'attaquer le site internet, la nouvelle adresse sera <a target='_blank' href='".home_url()."/".$new_path."'>celle-ci</a>",
	];

	// Différenciation car les modules commençant par vga sont déstinnés à être des blocs, tandis que les autres ne sont que des paramètres pour la sécurité
	foreach (Modules::get_modules() as $r) {
		echo "<div class='option'><label class='toggler-wrapper'><input type='checkbox' name='{$r->option_name}'".
		(($r->option_value == 'true') ? " checked" : null)." ".
		// Si le module possède l'option autoload sur on, on octtroie la possibilité à l'utilisateur de charger le module, sinon celui-ci est grisé (car pas fini)
		// On utilise yes & no pour se différencier des autoloads classique de wordpress pour ne pas surcharger le thread
		(($r->autoload == 'no') ? 'disabled' : null).
		"/><div class='toggler-slider'><div class='toggler-knob'></div></div></label><span> ".ucfirst(substr($r->option_name, 4))." : ".$desc[substr($r->option_name, 4)]."</span></div>";
	}

 ?> 
<h3>Sécurité</h3>
<?php
	foreach (Modules::get_module("sss-") as $r) { //sss pour la sécurité & pour maintenir 3 caractères
		echo "<div class='option'><label class='toggler-wrapper'><input type='checkbox' name='{$r->option_name}'".
		(($r->option_value == 'true') ? " checked" : null)." ".
		// Si le module possède l'option autoload sur on, on octtroie la possibilité à l'utilisateur de charger le module, sinon celui-ci est grisé (car pas fini)
		// On utilise yes & no pour se différencier des autoloads classique de wordpress pour ne pas surcharger le thread
		(($r->autoload == 'no') ? 'disabled' : null).
		"/><div class='toggler-slider'><div class='toggler-knob'></div></div></label><span> ".ucfirst(substr($r->option_name, 4))." : ".$desc[substr($r->option_name, 4)]."</span></div>";
	}
	echo get_submit_button("Mettre à jour les réglages", "primary large", "submit", false, ''); ?></form>