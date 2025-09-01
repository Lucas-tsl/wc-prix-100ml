<?php
/**
 * Plugin Name: Prix au 100 ml (WooCommerce)
 * Plugin URI: https://github.com/your-repo/wc-prix-100ml
 * Description: Affiche le prix par 100 ml pour les produits simples et variables (avec ACF ou attributs). Compatible WooCommerce Blocks & template classique.
 * Version: 1.0.0
 * Author: Lucas
 * Author URI: https://github.com/Lucas-tsl
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-prix-100ml
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

final class WC_Prix_100ml {
    private static $instance = null;
    private $rendered = false;

    public static function instance() {
        return self::$instance ?: self::$instance = new self();
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('render_block', [$this, 'inject_blocks'], 10, 2);
        add_action('woocommerce_single_product_summary', [$this, 'inject_classic'], 11);
        add_action('woocommerce_before_single_variation', [$this, 'inject_variation_container']);
    }

    public function enqueue_assets() {
        if (!is_product()) return;

        wp_enqueue_style(
            'wc-prix-100ml',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/style.css') // Version dynamique
        );

        // Debug: Vérifiez si le CSS est chargé
        error_log('WC_Prix_100ml: CSS chargé.');
        
        global $product;
        if ($product && $product->is_type('variable')) {
            wp_enqueue_script(
                'wc-prix-100ml',
                plugin_dir_url(__FILE__) . 'assets/prix-100ml.js',
                ['jquery'],
                '1.0.0',
                true
            );

            wp_localize_script('wc-prix-100ml', 'PRIX100ML', [
                'choisir' => '', // Removed the default message
                'label'   => 'Prix par 100 ml :',
                'erreur'  => 'Impossible de calculer le prix au 100 ml.',
                'devise'  => get_woocommerce_currency_symbol(),
            ]);
        }
    }

    public function inject_blocks($content, $block) {
        if (!is_product()) return $content;

        if (!empty($block['blockName']) && $block['blockName'] === 'woocommerce/product-price') {
            $html = $this->get_html();
            if ($html) {
                $this->rendered = true;
                $content .= $html;
            }
        }
        return $content;
    }

    public function inject_classic() {
        if (!is_product() || $this->rendered) return; // Vérifie si déjà rendu
        $this->rendered = true; // Marque comme rendu
        echo $this->get_html();
    }

    public function inject_variation_container() {
        if (!is_product()) return;
        global $product;
        if ($product && $product->is_type('variable')) {
            echo '<div id="prix-100ml" class="prix-100ml"></div>'; // Removed the default message
        }
    }

    private function get_html() {
        global $product;
        if (!$product || !is_a($product, 'WC_Product') || $product->is_type('variable')) return '';

        $price = (float) wc_get_price_to_display($product);
        if ($price <= 0) return '';

        // 1) Essayer le champ ACF "product_capacity"
        $contenance = function_exists('get_field')
            ? get_field('product_capacity', $product->get_id())
            : get_post_meta($product->get_id(), 'product_capacity', true);

        $contenance = (float) preg_replace('/[^0-9\.]/', '', (string)$contenance);

        // 2) Si pas de contenance en ACF, essayer de lire un attribut "xx ml"
        if ($contenance <= 0) {
            $attributes = $product->get_attributes();
            foreach ($attributes as $attribute) {
                foreach ($attribute->get_options() as $opt) {
                    if ($attribute->is_taxonomy()) {
                        $term = get_term(is_numeric($opt) ? (int)$opt : 0);
                        $txt = ($term && !is_wp_error($term)) ? $term->name : $opt;
                    } else {
                        $txt = $opt;
                    }
                    if (preg_match('/([\d\.,]+)\s*ml/i', $txt, $m)) {
                        $contenance = (float) str_replace(',', '.', $m[1]);
                        break 2;
                    }
                }
            }
        }

        if ($contenance <= 0) return '';

        $price100 = $price * (100 / $contenance);

        return '<div class="prix-100ml">Prix par 100 ml : ' . wc_price($price100) . '</div>';
    }
}

add_action('plugins_loaded', function () {
    if (class_exists('WooCommerce')) {
        WC_Prix_100ml::instance();
    }
});

// --------------------------------------------------
// Afficher le prix par 100 ml pour les produits simples
// avec des slugs spécifiques et les variations de produits
// --------------------------------------------------

add_action('woocommerce_single_product_summary', 'display_price_per_100ml_for_simple_products', 25);

function display_price_per_100ml_for_simple_products() {
    global $product;

    if ($product->is_type('simple')) {
        $attributes = $product->get_attributes();

        // Vérifier si un attribut contient les slugs 15ml ou 100ml
        foreach ($attributes as $attribute) {
            $options = $attribute->get_options();

            foreach ($options as $option) {
                if (strpos($option, '15ml') !== false || strpos($option, '100ml') !== false) {
                    $volume = floatval(str_replace('ml', '', $option)); // Extraire le volume (15 ou 100)
                    $price  = floatval($product->get_price());          // Obtenir le prix du produit

                    if (!is_nan($volume) && $volume > 0) {
                        if ($volume === 100) {
                            // Si la contenance est de 100 ml, afficher directement le prix
                            echo '<p class="price-per-unit">' 
                               . __('Prix par 100 ml : ', 'textdomain') 
                               . wc_price($price) 
                               . '</p>';
                        } else {
                            // Sinon, calculer le prix par 100 ml
                            $price_per_100ml = ($price / $volume) * 100;
                            echo '<p class="price-per-unit">' 
                               . __('Prix par 100 ml : ', 'textdomain') 
                               . wc_price($price_per_100ml) 
                               . '</p>';
                        }
                    } else {
                        echo '<p class="price-per-unit">' 
                           . __('Impossible de calculer le prix par 100 ml.', 'textdomain') 
                           . '</p>';
                    }

                    return; // Arrêter après avoir trouvé un slug correspondant
                }
            }
        }
    }
}

// --------------------------------------------------
// Afficher le prix par 100 ml pour les variations de produits
// --------------------------------------------------

add_action('woocommerce_before_single_variation', 'display_price_per_100ml_for_variations');
function display_price_per_100ml_for_variations() {
    echo '<div id="price-per-100ml"><p class="price-per-unit"></p></div>';
}

add_action('wp_footer', 'update_price_per_100ml_js');
function update_price_per_100ml_js() {
    if (is_product()) : ?>
        <script>
        jQuery(document).ready(function($) {
            function getLangFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('lang') || 'fr';
            }

            const translations = {
                fr: {
                    selectSize: 'Choisissez une taille pour voir le prix par 100 ml.',
                    pricePer100ml: 'Prix par 100 ml :',
                    unableToCalculate: 'Impossible de calculer le prix par 100 ml.',
                    currency: '&euro;'
                },
                en: {
                    selectSize: 'Select a size to see the price per 100 ml.',
                    pricePer100ml: 'Price per 100 ml:',
                    unableToCalculate: 'Unable to calculate the price per 100 ml.',
                    currency: '&euro;'
                }
            };

            const currentLang = getLangFromUrl();

            function parsePrice(priceText) {
                return parseFloat(priceText.replace(/[^\d,.-]/g, '').replace(',', '.'));
            }

            function updatePricePer100ml() {
                var selectedVar   = $('.product-var-cust[selected="selected"]');
                console.log("Selected variation:", selectedVar);

                if (selectedVar.length === 0) {
                    console.log("Aucune variation sélectionnée au chargement.");
                    return;
                }

                var variationVolume = selectedVar.attr('id');
                var variationPrice  = selectedVar.find('.woocommerce-Price-amount bdi').text();

                console.log("Volume détecté :", variationVolume);
                console.log("Prix détecté :", variationPrice);

                if (variationPrice && variationVolume) {
                    var price  = parsePrice(variationPrice);
                    var volume = parseFloat(variationVolume.replace('ml', '').trim());

                    if (!isNaN(price) && !isNaN(volume) && volume > 0) {
                        if (volume === 100) {
                            // Si la contenance est de 100 ml, afficher directement le prix
                            $('#price-per-100ml').html(
                                '<p class="price-per-unit">' 
                                + translations[currentLang].pricePer100ml 
                                + ' ' + price.toFixed(2) 
                                + ' ' + translations[currentLang].currency 
                                + '</p>'
                            ).show();
                        } else {
                            // Sinon, calculer le prix par 100 ml
                            var pricePer100ml = (price / volume) * 100;
                            $('#price-per-100ml').html(
                                '<p class="price-per-unit">' 
                                + translations[currentLang].pricePer100ml 
                                + ' ' + pricePer100ml.toFixed(2) 
                                + ' ' + translations[currentLang].currency 
                                + '</p>'
                            ).show();
                        }
                    } else {
                        $('#price-per-100ml').html(
                            '<p class="price-per-unit">' 
                            + translations[currentLang].unableToCalculate 
                            + '</p>'
                        ).show();
                    }
                } else {
                    $('#price-per-100ml').html(
                        '<p class="price-per-unit">' 
                        + translations[currentLang].selectSize 
                        + '</p>'
                    ).show();
                }
            }

            setTimeout(updatePricePer100ml, 500);

            $('.product-var-cust').on('click', function() {
                $('.product-var-cust').removeAttr('selected');
                $(this).attr('selected', 'selected');
                updatePricePer100ml();
            });
        });
        </script>
    <?php endif;
}

// --------------------------------------------------
// ACF : Affiche "Prix au 100 ml" à partir d'un champ personnalisé
// --------------------------------------------------

// Empêche le double rendu (Blocks + hook)
$GLOBALS['lsg_price_100ml_rendered'] = false;

/**
 * Récupère la contenance (ml) depuis ACF.
 */
function lsg_get_volume_ml($product_id) {
    // Nom du champ ACF ; adapte-le si différent
    $raw = function_exists('get_field') ? get_field('product_capacity', $product_id) : '';

    if (!$raw) {
        // fallback éventuel depuis la méta (si ACF stocke en meta)
        $raw = get_post_meta($product_id, 'product_capacity', true);
    }

    if (!$raw) return 0;

    // Garde uniquement chiffres et point (permet "200 ml", "200.0")
    $raw = is_string($raw) ? preg_replace('/[^0-9\.]/', '', $raw) : $raw;
    $ml  = (float) $raw;

    return $ml > 0 ? $ml : 0;
}

/**
 * Construit le HTML du bloc "Prix au 100 ml".
 */
function lsg_get_price_100ml_html() {
    if (!function_exists('is_product') || !is_product()) return '';

    global $product;
    if (!$product || !is_a($product, 'WC_Product')) return '';

    // Prix d'affichage (respecte les réglages TTC/HT de WooCommerce)
    $display_price = (float) wc_get_price_to_display($product);
    if ($display_price <= 0) return '';

    // Contenance en ml depuis ACF
    $volume_ml = lsg_get_volume_ml($product->get_id());
    if ($volume_ml <= 0) return ''; // si pas de contenance, on ne montre rien

    // Calcul : prix pour 100 ml
    // ex: 17€ pour 200 ml => 17 * (100/200) = 8.5€
    $price_per_100 = $display_price * (100 / $volume_ml);

    // Sécurité : valeurs aberrantes
    if (!is_finite($price_per_100) || $price_per_100 <= 0) return '';

    // HTML (style uniquement via CSS)
    return '<div class="lsg-price-100ml">'
         . '<strong>Prix au 100&nbsp;ml&nbsp;:</strong> '
         . wc_price($price_per_100)
         . '</div>';
}

/**
 * WooCommerce Blocks : injecte juste après le bloc prix.
 */
add_filter('render_block', function ($content, $block) {
    if (!is_product()) return $content;

    if (!empty($block['blockName']) && $block['blockName'] === 'woocommerce/product-price') {
        if (!empty($GLOBALS['lsg_price_100ml_rendered'])) return $content; // Évite le double rendu
        $addon = lsg_get_price_100ml_html();

        if ($addon) {
            $GLOBALS['lsg_price_100ml_rendered'] = true; // Marque comme rendu
            $content .= $addon;
        }
    }

    return $content;
}, 10, 2);

/**
 * Template classique : ajoute après le prix (priorité > 10),
 * seulement si non déjà rendu par Blocks.
 */
add_action('woocommerce_single_product_summary', function () {
    if (!empty($GLOBALS['lsg_price_100ml_rendered'])) return; // Évite le double rendu

    $html = lsg_get_price_100ml_html();
    if ($html) {
        $GLOBALS['lsg_price_100ml_rendered'] = true; // Marque comme rendu
        echo $html;
    }
}, 11);

