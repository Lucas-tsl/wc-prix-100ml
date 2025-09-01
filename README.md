# Prix au 100 ml (WooCommerce)

## Description
Le plugin **Prix au 100 ml** pour WooCommerce permet d'afficher automatiquement le prix par 100 ml pour les produits simples et variables. Il est compatible avec les champs personnalisés ACF, les attributs de produits WooCommerce, les blocs WooCommerce et les templates classiques.

## Fonctionnalités
- **Affichage automatique** : Affiche le prix par 100 ml sur les pages produits.
- **Compatibilité avec les produits simples et variables**.
- **Support des champs personnalisés ACF** : Utilise le champ `product_capacity` pour calculer le prix.
- **Lecture des attributs produits** : Analyse les attributs contenant des volumes (ex. "15 ml", "100 ml").
- **Traductions dynamiques** : Supporte plusieurs langues (ex. français, anglais).
- **Style personnalisable** : Inclut un fichier CSS pour personnaliser l'apparence.

## Prérequis
- **WordPress** : Version 5.0 ou supérieure.
- **WooCommerce** : Version 4.0 ou supérieure.
- **PHP** : Version 7.4 ou supérieure.
- **ACF (Advanced Custom Fields)** : Optionnel, pour utiliser le champ `product_capacity`.

## Installation
1. Téléchargez le plugin.
2. Décompressez le fichier ZIP dans le dossier `wp-content/plugins`.
3. Activez le plugin depuis le menu **Extensions** dans WordPress.
4. Assurez-vous que WooCommerce est activé.

## Utilisation
- Ajoutez un champ personnalisé `product_capacity` (en ml) à vos produits via ACF, ou utilisez des attributs produits contenant des volumes (ex. "15 ml").
- Le prix par 100 ml sera automatiquement affiché sur les pages produits.

## Personnalisation
- Modifiez le fichier CSS situé dans `assets/style.css` pour personnaliser l'apparence.
- Les traductions peuvent être ajustées dans le fichier JavaScript `assets/prix-100ml.js`.

## Débogage
- Vérifiez les erreurs dans le fichier de log WordPress si le plugin ne fonctionne pas comme prévu.
- Assurez-vous que les fichiers CSS et JS sont correctement chargés.

## Support
Pour toute question ou problème, contactez l'auteur via le dépôt GitHub ou par email.

## Auteur
- **Nom** : Lucas
- **Version** : 1.0.0
- **Licence** : GPL-2.0+# wc-prix-100ml
