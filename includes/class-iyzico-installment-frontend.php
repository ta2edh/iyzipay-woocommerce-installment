<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * iyzico Installment Frontend
 */
class Iyzico_Installment_Frontend
{
    private $settings;
    private $api;

    public function __construct(Iyzico_Installment_Settings $settings, Iyzico_Installment_API $api)
    {
        $this->settings = $settings;
        $this->api = $api;

        add_shortcode('iyzico_installment', array($this, 'render_shortcode'));

        // Add hooks for direct integration
        if ($this->settings->show_product_tabs()) {
            add_filter('woocommerce_product_tabs', array($this, 'add_installment_tab'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
    }

    public function enqueue_scripts()
    {
        if (!$this->should_load_scripts()) {
            return;
        }

        wp_enqueue_style(
            'iyzico-installment',
            IYZI_INSTALLMENT_ASSETS_URL . '/css/iyzico-installment.css',
            array(),
            IYZI_INSTALLMENT_VERSION
        );

        wp_enqueue_script(
            'iyzico-installment',
            IYZI_INSTALLMENT_ASSETS_URL . '/js/iyzico-installment.js',
            array('jquery'),
            IYZI_INSTALLMENT_VERSION,
            true
        );

        wp_localize_script('iyzico-installment', 'iyzicoInstallment', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('iyzico_installment_nonce'),
            'integrationType' => $this->settings->get_integration_type(),
            'isProductPage' => is_product(),
            'productPrice' => $this->get_product_price(),
            'installmentText' => __('Taksit', 'iyzico-installment'),
            'totalText' => __('Toplam', 'iyzico-installment'),
            'currencySymbol' => get_woocommerce_currency_symbol(),
            'assetsUrl' => IYZI_INSTALLMENT_ASSETS_URL
        ));
        
    }

    private function should_load_scripts()
    {
        if (!$this->settings->has_credentials()) {
            return false;
        }

        if (is_product() && $this->settings->show_product_tabs()) {
            return true;
        }

        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'iyzico_installment')) {
            return true;
        }

        return false;
    }

    private function get_product_price()
    {
        if (!is_product()) {
            return 0;
        }

        global $post;
        $product = wc_get_product($post);

        if (!$product || !is_a($product, 'WC_Product')) {
            return 0;
        }

        return $product->get_price();
    }

    public function render_shortcode($atts)
    {
        if (!$this->settings->has_credentials()) {
            return '<p>' . esc_html__('API kimlik bilgileri yapılandırılmamış.', 'iyzico-installment') . '</p>';
        }

        $atts = shortcode_atts(array(
            'price' => $this->get_product_price(),
            'bin' => '',
        ), $atts, 'iyzico_installment');

        $price = floatval($atts['price']);
        $bin = sanitize_text_field($atts['bin']);

        if ($price <= 0) {
            return '<p>' . esc_html__('Geçerli bir fiyat belirtilmedi.', 'iyzico-installment') . '</p>';
        }

        // Apply VAT if enabled
        $price = $this->settings->calculate_price_with_vat($price);

        $installment_info = $this->api->get_installment_info($price, $bin);

        if (is_wp_error($installment_info)) {
            return '<p>' . esc_html($installment_info->get_error_message()) . '</p>';
        }

        // Enqueue assets (guarantee)
        wp_enqueue_style(
            'iyzico-installment',
            IYZI_INSTALLMENT_ASSETS_URL . '/css/iyzico-installment.css',
            array(),
            IYZI_INSTALLMENT_VERSION
        );

        wp_enqueue_script(
            'iyzico-installment',
            IYZI_INSTALLMENT_ASSETS_URL . '/js/iyzico-installment.js',
            array('jquery'),
            IYZI_INSTALLMENT_VERSION,
            true
        );

        wp_localize_script('iyzico-installment', 'iyzicoInstallment', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('iyzico_installment_nonce'),
            'integrationType' => $this->settings->get_integration_type(),
            'isProductPage' => is_product(),
            'productPrice' => $price,
            'installmentText' => __('Taksit', 'iyzico-installment'),
            'totalText' => __('Toplam', 'iyzico-installment'),
            'currencySymbol' => get_woocommerce_currency_symbol()
        ));

        return $this->render_installment_table($installment_info);
    }

    public function add_installment_tab($tabs)
    {
        if (!is_product() || !$this->settings->has_credentials()) {
            return $tabs;
        }

        $price = $this->get_product_price();
        if ($price <= 0) {
            return $tabs;
        }

        $tabs['iyzico_installment'] = array(
            'title'    => __('Taksit Seçenekleri', 'iyzico-installment'),
            'priority' => 25,
            'callback' => array($this, 'render_installment_tab')
        );

        return $tabs;
    }

    public function render_installment_tab()
    {
        $price = $this->get_product_price();
        
        // Apply VAT if enabled
        $price = $this->settings->calculate_price_with_vat($price);
        
        $installment_info = $this->api->get_installment_info($price);

        if (is_wp_error($installment_info)) {
            echo '<p>' . esc_html($installment_info->get_error_message()) . '</p>';
            return;
        }

        echo wp_kses_post($this->render_installment_table($installment_info));
    }

    private function render_installment_table($installment_info)
    {
        if (empty($installment_info['installmentDetails'])) {
            return '<p>' . esc_html__('Taksit seçeneği bulunamadı.', 'iyzico-installment') . '</p>';
        }

        ob_start();
        ?>
        <div class="iyzico-installment-container">
            <h3 class="iyzico-installment-title"><?php echo esc_html__('Taksit Seçenekleri', 'iyzico-installment'); ?></h3>

            <div class="iyzico-bank-grid">
                <?php foreach ($installment_info['installmentDetails'] as $bank): ?>
                    <div class="iyzico-bank-card" tabindex="0" aria-label="<?php echo esc_attr($bank['bankName'] . ' - ' . $bank['cardFamilyName']); ?>">
                        <div class="iyzico-bank-logo-top">
                            <?php echo $this->get_bank_logo($bank['bankName'], $bank['cardFamilyName']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>

                        <div class="table-area">
                            <table class="iyzico-installment-table" role="table" aria-label="<?php echo esc_attr($bank['bankName']); ?> taksit tablosu">
                                <thead>
                                    <tr>
                                        <th><?php echo esc_html__('Taksit Sayısı', 'iyzico-installment'); ?></th>
                                        <th class="amount"><?php echo esc_html__('Taksit Tutarı', 'iyzico-installment'); ?></th>
                                        <th class="amount total"><?php echo esc_html__('Toplam', 'iyzico-installment'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bank['installmentPrices'] as $installment): ?>
                                        <tr>
                                            <td><?php echo esc_html($installment['installmentNumber']); ?></td>
                                            <td class="amount"><?php echo wp_kses_post(wc_price($installment['installmentPrice'])); ?></td>
                                            <td class="amount total"><?php echo wp_kses_post(wc_price($installment['totalPrice'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_bank_logo($bank_name, $card_family)
    {
        $card_family_lower = strtolower(trim($card_family));
        if (strpos($card_family_lower, 'bonus') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Bonus.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } elseif (strpos($card_family_lower, 'axess') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Axess.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } elseif (strpos($card_family_lower, 'maximum') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Maximum.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } elseif (strpos($card_family_lower, 'paraf') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Paraf.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } elseif (strpos($card_family_lower, 'cardfinans') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Cardfinans.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } elseif (strpos($card_family_lower, 'advantage') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/Advantage.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } elseif (strpos($card_family_lower, 'world') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/World.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } elseif (strpos($card_family_lower, 'sağlam') !== false || strpos($card_family_lower, 'saglam') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/SaglamKart.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } elseif (strpos($card_family_lower, 'combo') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/BankkartCombo.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } elseif (strpos($card_family_lower, 'qnb') !== false || strpos($card_family_lower, 'cc') !== false) {
            return '<img src="' . IYZI_INSTALLMENT_ASSETS_URL . '/images/QNB-CC.png" alt="' . esc_attr($card_family) . '" class="bank-logo" title="' . esc_attr($card_family) . '">';
        } else {
            return '<div class="bank-logo-default" title="' . esc_attr($card_family) . '">💳</div>';
        }
    }
}
