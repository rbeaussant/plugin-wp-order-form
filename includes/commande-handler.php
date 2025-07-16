<?php
/**
 * Gestion de la soumission du formulaire CE
 */

function ce_is_valid_ce_code($code) {
    $codes = get_option('ce_codes', []);
    $today = date('Y-m-d');

    foreach ($codes as $c) {
        if (strtoupper(trim($c['code'])) === strtoupper(trim($code))) {
            if (empty($c['validite']) || $c['validite'] >= $today) {
                return $c; // renvoie tout le tableau (nom, code, validité)
            }
            return false; // code trouvé mais expiré
        }
    }
    return false; // code non trouvé
}

// CPT Commandes CE
function ce_register_commande_cpt() {
    register_post_type('commande_ce', [
        'labels' => [
            'name' => 'Commandes CE',
            'singular_name' => 'Commande CE'
        ],
        'public' => false,
        'show_ui' => true,
        'supports' => ['title', 'editor'],
        'menu_position' => 59,
        'menu_icon' => 'dashicons-cart'
    ]);
}
add_action('init', 'ce_register_commande_cpt');

// Colonnes personnalisées dans l'admin
add_filter('manage_commande_ce_posts_columns', function($columns) {
    $columns['ce'] = 'CE';
    $columns['total'] = 'Total (€)';
    return $columns;
});

add_action('manage_commande_ce_posts_custom_column', function($column, $post_id) {
    if ($column === 'ce') {
        $content = get_post_field('post_content', $post_id);
        if (preg_match('/CE\s*:\s*(.+)/', $content, $m)) {
            $ce_nom = trim(preg_replace('/\s+/', ' ', strtok($m[1], "\n")));
            echo esc_html($ce_nom);
        }
    } elseif ($column === 'total') {
        $content = get_post_field('post_content', $post_id);
        preg_match_all('/=\s*([\d,.]+)\s*€/u', $content, $matches);
        $total = array_sum(array_map(function($v) {
            return floatval(str_replace(',', '.', str_replace(' ', '', $v)));
        }, $matches[1]));
        echo number_format($total, 2, ',', ' ') . ' €';
    }
}, 10, 2);

// Traitement du formulaire CE
add_action('init', function() {
    if (isset($_POST['confirmer_commande']) && !empty($_POST['nom']) && !empty($_POST['qte'])) {

        $ce_nom = sanitize_text_field($_POST['ce_nom'] ?? 'Inconnu');
        $nom = sanitize_text_field($_POST['nom']);
        $prenom = sanitize_text_field($_POST['prenom']);
        $email = sanitize_email($_POST['email']);
        $telephone = sanitize_text_field($_POST['telephone']);

        $produits = get_option('ce_produits', []);
        $qtes = $_POST['qte'];
        $contenu = "CE : $ce_nom\n";
        $contenu .= "Client : $prenom $nom ($email)\nTéléphone : $telephone\n\nProduits commandés :\n";
        $total = 0;

        foreach ($qtes as $i => $qte) {
            $qte = intval($qte);
            if ($qte > 0 && isset($produits[$i])) {
                $p = $produits[$i];
                $total_ligne = floatval($p['prix_ce']) * $qte;
                $total += $total_ligne;
                $contenu .= "- {$p['nom']} x $qte = " . number_format($total_ligne, 2, ',', ' ') . " €\n";
            }
        }
        $contenu .= "\nTotal : " . number_format($total, 2, ',', ' ') . " €";

        // Enregistrement CPT
        $post_id = wp_insert_post([
            'post_type'   => 'commande_ce',
            'post_title'  => "Commande de $prenom $nom (CE $ce_nom)",
            'post_content'=> $contenu,
            'post_status' => 'publish'
        ]);

        // Envoi d'email
        $to = get_option('admin_email');
        $subject = "Nouvelle commande CE de $prenom $nom";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        wp_mail($to, $subject, $contenu, $headers);

        // Confirmation directe si test, sinon redirection Monetico
        if (!empty($_POST['test_commande'])) {
            // Affiche confirmation directe
            wp_redirect(add_query_arg('commande', 'ok', $_SERVER['REQUEST_URI']));
            exit;
        } else {
            // Stocke la commande en session pour Monetico
            // Stocker en WC()->session SI WooCommerce actif
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('ce_commande', $contenu);
                WC()->session->set('ce_total', $total);
            }

            // Stocker également en $_SESSION pour fallback
            if (!session_id()) session_start();
            $_SESSION['ce_commande'] = $contenu;
            $_SESSION['ce_total'] = $total;

            wp_redirect(home_url('/paiement-ce'));
            exit;
        }
    }
});
