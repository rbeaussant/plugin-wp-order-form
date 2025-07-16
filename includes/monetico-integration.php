<?php
/**
 * Monetico (CIC) – Intégration simplifiée pour commandes CE
 * Page /paiement-ce : génère le formulaire de paiement et auto-soumet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CE_Monetico {

    // ———————————————————————————  PARAMÈTRES TPE  ———————————————————————————
    const TPE           = '2770078';
    const CLE_HMAC      = 'B92C6AD97A1E2695E49812405E1596E05E7FF992';
    const CODE_SOCIETE  = 'LELIEVREVD';
    const VERSION       = '2.0'; // Version du protocole Monetico
    const LANGUE        = 'FR';

    // const CGI_URL_TEST  = 'https://p.monetico-services.com/test/paiement.cgi';
    const CGI_URL_PRODUCTION = 'https://p.monetico-services.com/paiement.cgi';

    public function __construct() {
        add_action( 'init', [ $this, 'register_shortcode' ] );
    }

    public function register_shortcode() {
        add_shortcode( 'paiement_ce', [ $this, 'render_paiement_page' ] );
    }

    /**
     * Affiche le formulaire Monetico (auto-submit) à partir de la commande en session
     */
    public function render_paiement_page() {
        // Vérifie que WooCommerce et la session sont disponibles
        if ( ! function_exists('WC') || ! WC()->session ) {
            return '<p>Erreur : WooCommerce ou la session n’est pas disponible.</p>';
        }

        // Récupérer les données commande stockées en session par commande-handler.php
        $commande = WC()->session->get( 'ce_commande' );
        $total    = WC()->session->get( 'ce_total' );

        if ( ! $commande || ! $total ) {
            return '<p>Aucune commande en attente.</p>';
        }

        // Construire les champs obligatoires
        $reference = 'CE-' . time();
        $date      = gmdate( 'd/m/Y:H:i:s' ); // Heure GMT requise
        $montant   = number_format( $total, 2, ',', '' ) . 'EUR';
        $texte_l   = 'Commande CE';

        // Nouvel URL de retour (WooCommerce Monetico Gateway)
        $url_retour_ok  = 'https://vins-lelievre.com/?wc-api=WC_Gateway_Monetico';
        $url_retour_err = 'https://vins-lelievre.com/?wc-api=WC_Gateway_Monetico';

        // Chaîne à signer (cf. guide Monetico – v3)
        $data = implode( '*', [
            self::TPE,
            $date,
            $montant,
            $reference,
            $texte_l,
            self::VERSION,
            self::LANGUE,
            self::CODE_SOCIETE,
            $url_retour_ok, // url_retour
            $url_retour_ok, // url_retour_ok
            $url_retour_err // url_retour_err
        ] );


        
        // Calcul HMAC SHA1 (clé fournie par la banque déjà au format hex)
        $mac = strtoupper( hash_hmac( 'sha1', $data, pack( 'H*', self::CLE_HMAC ) ) );

        ob_start();
        ?>
        <h2>Redirection vers le paiement sécurisé…</h2>
        <form id="monetico_form" method="POST" action="<?php echo esc_url( self::CGI_URL_PRODUCTION ); ?>">
            <input type="hidden" name="TPE" value="<?php echo esc_attr( self::TPE ); ?>">
            <input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
            <input type="hidden" name="montant" value="<?php echo esc_attr( $montant ); ?>">
            <input type="hidden" name="reference" value="<?php echo esc_attr( $reference ); ?>">
            <input type="hidden" name="MAC" value="<?php echo esc_attr( $mac ); ?>">
            <input type="hidden" name="url_retour" value="<?php echo esc_url( $url_retour_ok ); ?>">
            <input type="hidden" name="url_retour_ok" value="<?php echo esc_url( $url_retour_ok ); ?>">
            <input type="hidden" name="url_retour_err" value="<?php echo esc_url( $url_retour_err ); ?>">
            <input type="hidden" name="lgue" value="<?php echo esc_attr( self::LANGUE ); ?>">
            <input type="hidden" name="societe" value="<?php echo esc_attr( self::CODE_SOCIETE ); ?>">
            <input type="hidden" name="version" value="<?php echo esc_attr( self::VERSION ); ?>">
            <input type="hidden" name="texte-libre" value="<?php echo esc_attr( $texte_l ); ?>">
            <noscript>
                <p>Merci de cliquer sur le bouton pour payer :</p>
                <button type="submit">Payer</button>
            </noscript>
        </form>
        
        <?php if ( ! isset( $_GET['test'] ) ) : ?>
        <script>
            document.getElementById('monetico_form').submit();
        </script>
        <?php else : ?>
        <p style="color:#c00;font-weight:bold">MODE TEST : le formulaire n’est pas envoyé à Monetico.<br>
        Vérifie le récapitulatif ci-dessus puis ferme simplement la page.</p>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }
}

new CE_Monetico();
