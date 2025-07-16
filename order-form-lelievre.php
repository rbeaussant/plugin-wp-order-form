<?php
/**
 * Plugin Name: CE Commande Privée
 * Description: Formulaire de commande réservé aux CE, avec prix spéciaux, gestion de stock et paiement.
 * Version: 1.0
 * Author: Votre Nom
 */

if (!defined('ABSPATH')) exit;

// Inclure les fichiers nécessaires
require_once plugin_dir_path(__FILE__) . 'includes/ce-options-metaboxes.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-formulaire.php';
require_once plugin_dir_path(__FILE__) . 'includes/commande-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/monetico-integration.php';

// Enregistrement styles et scripts
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('ce-style', plugin_dir_url(__FILE__) . 'assets/style.css');
    wp_enqueue_script('ce-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
});
