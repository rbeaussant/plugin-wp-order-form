<?php
/**
 * Options & metaboxes CE (sans ACF)
 * - Produits CE (prix spéciaux, stock, URL).
 * - Codes CE (entreprise, code secret, validité).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CE_Options_Metaboxes {

    const OPTION_PRODUITS = 'ce_produits';
    const OPTION_CODES   = 'ce_codes';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_post_save_ce_options', [ $this, 'save_options' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /*--------------------------------------------------------------*/
    /*  Admin menu                                                  */
    /*--------------------------------------------------------------*/
    public function add_menu() {
        add_menu_page(
            'Options CE',
            'Options CE',
            'manage_options',
            'ce-options',
            '',
            'dashicons-groups',
            58
        );

        add_submenu_page(
            'ce-options',
            'Produits CE',
            'Produits CE',
            'manage_options',
            'ce-produits',
            [ $this, 'render_produits_page' ]
        );

        add_submenu_page(
            'ce-options',
            'Codes CE',
            'Codes CE',
            'manage_options',
            'ce-codes',
            [ $this, 'render_codes_page' ]
        );
    }

    /*--------------------------------------------------------------*/
    /*  Assets                                                      */
    /*--------------------------------------------------------------*/
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'ce-produits' ) !== false || strpos( $hook, 'ce-codes' ) !== false ) {
            wp_enqueue_style( 'ce-options-admin', plugin_dir_url( __DIR__ ) . 'assets/admin.css', [], '1.0' );
            wp_enqueue_script( 'ce-options-admin', plugin_dir_url( __DIR__ ) . 'assets/admin.js', [ 'jquery' ], '1.0', true );
        }
    }

    /*--------------------------------------------------------------*/
    /*  Render pages                                                */
    /*--------------------------------------------------------------*/
    private function header( $title ) {
        echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'save_ce_options' );
    }

    private function footer() {
        submit_button();
        echo '</form></div>';
    }

    public function render_produits_page() {
        $items = get_option( self::OPTION_PRODUITS, [] );

        $this->header( 'Produits CE' );
        echo '<input type="hidden" name="action" value="save_ce_options">';
        echo '<input type="hidden" name="tab" value="produits">';
        ?>
        <table class="widefat fixed" id="ce-produits-table">
            <thead>
                <tr>
                    <th>Nom</th><th>Prix normal €</th><th>Prix CE €</th><th>Stock</th><th>URL produit</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( $items ) : foreach ( $items as $i => $item ) : ?>
                <tr>
                    <td><input type="text" name="items[<?php echo $i; ?>][nom]" value="<?php echo esc_attr( $item['nom'] ?? '' ); ?>" required></td>
                    <td><input type="number" step="0.01" name="items[<?php echo $i; ?>][prix_normal]" value="<?php echo esc_attr( $item['prix_normal'] ?? '' ); ?>" required></td>
                    <td><input type="number" step="0.01" name="items[<?php echo $i; ?>][prix_ce]" value="<?php echo esc_attr( $item['prix_ce'] ?? '' ); ?>" required></td>
                    <td><input type="number" name="items[<?php echo $i; ?>][stock]" value="<?php echo esc_attr( $item['stock'] ?? '' ); ?>" required></td>
                    <td><input type="url" name="items[<?php echo $i; ?>][url]" value="<?php echo esc_attr( $item['url'] ?? '' ); ?>"></td>
                    <td><button class="button remove-row">×</button></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <p><button type="button" class="button" id="add-produit-row">+ Ajouter un produit</button></p>
        <?php
        $this->footer();
    }

    public function render_codes_page() {
        $codes = get_option( self::OPTION_CODES, [] );

        $this->header( 'Codes CE' );
        echo '<input type="hidden" name="action" value="save_ce_options">';
        echo '<input type="hidden" name="tab" value="codes">';
        ?>
        <table class="widefat fixed" id="ce-codes-table">
            <thead>
                <tr><th>Nom entreprise</th><th>Code secret</th><th>Validité (YYYY-MM-DD)</th><th></th></tr>
            </thead>
            <tbody>
            <?php if ( $codes ) : foreach ( $codes as $i => $row ) : ?>
                <tr>
                    <td><input type="text" name="codes[<?php echo $i; ?>][nom]" value="<?php echo esc_attr( $row['nom'] ?? '' ); ?>" required></td>
                    <td><input type="text" name="codes[<?php echo $i; ?>][code]" value="<?php echo esc_attr( $row['code'] ?? '' ); ?>" required></td>
                    <td><input type="date" name="codes[<?php echo $i; ?>][validite]" value="<?php echo esc_attr( $row['validite'] ?? '' ); ?>" required></td>
                    <td><button class="button remove-row">×</button></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <p><button type="button" class="button" id="add-code-row">+ Ajouter un CE</button></p>
        <?php
        $this->footer();
    }

    /*--------------------------------------------------------------*/
    /*  Save                                                        */
    /*--------------------------------------------------------------*/
    public function save_options() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'save_ce_options' ) ) {
            wp_die( 'Accès refusé' );
        }

        $tab = sanitize_text_field( $_POST['tab'] ?? '' );

        if ( 'produits' === $tab ) {
            $items = array_values( array_filter( $_POST['items'] ?? [], function ( $row ) {
                return ! empty( $row['nom'] ) && ! empty( $row['prix_ce'] );
            } ) );

            // Force les prix à float
            foreach ( $items as &$item ) {
                $item['prix_normal'] = isset($item['prix_normal']) ? floatval($item['prix_normal']) : 0;
                $item['prix_ce'] = isset($item['prix_ce']) ? floatval($item['prix_ce']) : 0;
                $item['stock'] = isset($item['stock']) ? intval($item['stock']) : 0;
            }

            update_option( self::OPTION_PRODUITS, $items );
        }
        } elseif ( 'codes' === $tab ) {
            $codes = array_values( array_filter( $_POST['codes'] ?? [], function ( $row ) {
                return ! empty( $row['nom'] ) && ! empty( $row['code'] );
            } ) );
            update_option( self::OPTION_CODES, $codes );
        }

        wp_redirect( wp_get_referer() );
        exit;
    }
}

new CE_Options_Metaboxes();
