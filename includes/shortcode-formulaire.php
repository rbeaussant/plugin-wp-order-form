<?php
<?php
/**
 * Shortcode [ce_tableau] – Formulaire CE avec vérification du code, étape de récapitulatif, et passage au paiement.
 */

function ce_render_tableau_formulaire() {
    if (!session_id()) session_start();

    $produits = get_option('ce_produits', []);
    $codes_ce = get_option('ce_codes', []);
    $ce_code = $_SESSION['ce_code_ok'] ?? null;
    $ce_nom = $_SESSION['ce_nom'] ?? null;

    ob_start();

    // 1. Validation du code CE
    if (!$ce_code && isset($_POST['code_ce'])) {
        $code = sanitize_text_field($_POST['code_ce']);
        foreach ($codes_ce as $row) {
            if ($row['code'] === $code) {
                $_SESSION['ce_code_ok'] = $row['code'];
                $_SESSION['ce_nom'] = $row['nom'];
                $ce_code = $row['code'];
                $ce_nom = $row['nom'];
                break;
            }
        }
        if (!$ce_code) {
            echo '<p style="color:red;">Code CE invalide.</p>';
        }
    }

    // 2. Si code CE non validé : formulaire de saisie
    if (!$ce_code) {
        ?>
        <form method="post">
            <label for="code_ce">Veuillez saisir votre code CE :</label>
            <input type="text" name="code_ce" id="code_ce" required>
            <button type="submit" class="button button-primary">Valider</button>
        </form>
        <?php
        return ob_get_clean();
    }

    echo '<p><strong>Code CE : ' . esc_html($ce_code) . ' / Entreprise : ' . esc_html($ce_nom) . '</strong></p>';

    // 3. Traitement de la commande uniquement lors de la soumission finale
    if (isset($_POST['confirmer_commande']) && $_POST['confirmer_commande'] == '1') {
        // Ici, place ton traitement de commande PHP habituel
        echo '<p style="color:green;">Votre commande a bien été enregistrée. Merci !</p>';
        // Tu peux rediriger ou afficher un récapitulatif PHP ici si besoin
        return ob_get_clean();
    }

    // 4. Formulaire principal
    ?>
    <div id="ce-formulaire">
    <form id="ce-order-form" method="post">
        <input type="hidden" name="ce_nom" value="<?php echo esc_attr($ce_nom); ?>">
        <input type="hidden" name="ce_code" value="<?php echo esc_attr($ce_code); ?>">
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix normal</th>
                    <th>Prix CE</th>
                    <th>Stock</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produits as $i => $p): ?>
                <tr>
                    <td><?php echo esc_html($p['nom']); ?></td>
                    <td><del><?php echo number_format($p['prix_normal'], 2, ',', ' '); ?> €</del></td>
                    <td><strong><?php echo number_format($p['prix_ce'], 2, ',', ' '); ?> €</strong></td>
                    <td><?php echo intval($p['stock']); ?></td>
                    <td>
                        <input type="number" name="qte[<?php echo $i; ?>]" class="qte-input" data-price="<?php echo esc_attr($p['prix_ce']); ?>" min="0" max="<?php echo intval($p['stock']); ?>">
                    </td>
                    <td class="line-total">0,00 €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><strong>Total général : <span id="ce-total">0,00 €</span></strong></p>
        <p>
            <label>Nom : <input type="text" name="nom" required></label>
            <label>Prénom : <input type="text" name="prenom" required></label>
            <label>Email : <input type="email" name="email" required></label>
            <label>Téléphone : <input type="text" name="telephone" required></label>
        </p>
        <button type="submit" id="valider-commande" class="button button-primary">Valider ma commande</button>
    </form>
    </div>

    <script>
    window.ceProduits = <?php echo json_encode($produits); ?>;
    </script>

    <div id="ce-recapitulatif" style="display:none;">
      <div id="recap-content"></div>
      <label style="display:block;margin:10px 0;">
        <input type="checkbox" id="test-commande" name="test_commande" value="1">
        Tester commande sans paiement
      </label>
      <button type="button" id="retour-formulaire" class="button">Modifier ma commande</button>
      <button type="button" id="confirmer-commande" class="button button-primary">Confirmer et payer</button>
    </div>

    <?php
    return ob_get_clean();
}

add_shortcode('ce_tableau', 'ce_render_tableau_formulaire');