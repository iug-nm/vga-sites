# Extension Wordpress VGA Sites

Cette extension est développée pour être optimisée selon les besoins des usagers des sites internets développés en parallèle.
Elle n'embarque que le minimum syndical pour permettre à ceux-ci de créer du contenu en toute sérénité 
sans être importuné par des temps de chargements désagréables.

# Installer cette extension
Pour pouvoir utiliser cette extension en local pensez tout d'abord à : 
  - télécharger tous les fichiers se trouvant sur ce repertoire
  - Ouvrir votre terminal est exécutez les commandes :
      - `npm install` pour installer les dependances
      - `npm run build` pour compiler les fichiers de l'extension
      - `npm run plugin-zip` pour rendre l'extension dans sa version finale
      
Si vous souhaitez en revanche l'installer sur votre wordpress distant zippez là et importez là directement depuis le site internet !

> Attention pensez à bien inclure `vga-sites.php` dans le zip sinon wordpress ne reconnaitra pas l'extension

# Blocks embarqués par l'extension
En vous procurant cette extension vous aurez par la suite le choix d'activer ou non plusieurs modules indépendants.
Ces modules correspondent aux fonctionnalités suivantes :

- [x] **Accordion** : Contenu révélé lorsque l'on clique sur le titre du block
- [x] **Carte Interactive** : Espace où un administrateur (ou editeur) peut créer une carte qu'il peut centrer sur sa commune, et y ajouter des marqueurs pour en indiquer les points les plus importants
- [x] **Equipes** : Block ayant été pensé pour facilement présenter un(e) membre du conseil municipal
- [x] **Plan du site** : Block affichant toutes les relations parents-enfants des pages et articles du site, ainsi que leurs catégories
- [x] **Tous les articles** : Block possédant plusieurs options permettant d'afficher tous les articles ayant été publiés sur le site

# Fonctionnalités de sécurité proposées par l'extension
L'extension intègre aussi quelques options de sécurité assez utiles que vous pouvez désactiver à tout moment.

- [ ] **Version** : Édition de code depuis le dashboard wordpress
- [x] **Iframe** : Empêche la mise en iframe du site par n'importe quels utilisateurs & serveurs
- [x] **Bruteforce** : Blockage d'une ip utilisateur si celui-ci dépasse le nombre de tentatives max
- [ ] **Sessions** : Empêche la connexion simultannée sur un même compte utilisateur sur différentes machine
- [x] **Hide** : Permet de changer l'url de connexion
