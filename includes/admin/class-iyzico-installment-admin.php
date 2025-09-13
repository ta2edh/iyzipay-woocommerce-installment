<?php

if (!defined('ABSPATH')) {
    exit;
}

use Iyzipay\Model\ApiTest;
use Iyzipay\Options;

/**
 * iyzico Installment Admin
 */
class Iyzico_Installment_Admin
{
    /**
     * Settings instance
     *
     * @var Iyzico_Installment_Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct(Iyzico_Installment_Settings $settings)
    {
        $this->settings = $settings;
        
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Ajax handlers
        add_action('wp_ajax_iyzico_test_api', array($this, 'ajax_test_api'));
    }

    /**
     * Register admin menu
     */
    public function register_menu()
    {
        add_menu_page(
            __('iyzico Taksit Seçenekleri', 'iyzico-installment'),
            __('iyzico Taksit', 'iyzico-installment'),
            'manage_options',
            'iyzico_installment',
            array($this, 'render_settings_page'),
            'dashicons-money-alt',
            30
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting(
            'iyzico_installment_options', 
            Iyzico_Installment_Settings::OPTION_KEY,
            array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input Settings input.
     * @return array
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }

        if (isset($input['secret_key'])) {
            $sanitized['secret_key'] = sanitize_text_field($input['secret_key']);
        }

        if (isset($input['integration_type'])) {
            $sanitized['integration_type'] = sanitize_text_field($input['integration_type']);
        }

        if (isset($input['mode'])) {
            $sanitized['mode'] = in_array($input['mode'], ['sandbox', 'live']) ? $input['mode'] : 'sandbox';
        }

        if (isset($input['enable_vat'])) {
            $sanitized['enable_vat'] = (bool) $input['enable_vat'];
        }

        if (isset($input['vat_rate'])) {
            $sanitized['vat_rate'] = floatval($input['vat_rate']);
        }

        if (isset($input['enable_dynamic_installments'])) {
            $sanitized['enable_dynamic_installments'] = (bool) $input['enable_dynamic_installments'];
        }

        return $sanitized;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook)
    {
        if ('toplevel_page_iyzico_installment' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'iyzico-installment-admin',
            IYZI_INSTALLMENT_ASSETS_URL . '/css/iyzico-installment-admin.css',
            array(),
            IYZI_INSTALLMENT_VERSION
        );

        wp_enqueue_script(
            'iyzico-installment-admin',
            IYZI_INSTALLMENT_ASSETS_URL . '/js/iyzico-installment.js',
            array('jquery'),
            IYZI_INSTALLMENT_VERSION,
            true
        );

        wp_localize_script('iyzico-installment-admin', 'iyzicoInstallment', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('iyzico_installment_nonce'),
            'copySuccess' => __('Kopyalandı!', 'iyzico-installment'),
            'copyError' => __('Kopyalanamadı!', 'iyzico-installment'),
            'emptyCredentials' => __('API Key ve Secret Key alanları boş olamaz.', 'iyzico-installment'),
            'testing' => __('Test ediliyor...', 'iyzico-installment'),
            'connected' => __('Bağlantı Başarılı!', 'iyzico-installment'),
            'disconnected' => __('Bağlantı Başarısız!', 'iyzico-installment'),
            'connectionSuccess' => __('API bağlantısı başarılı.', 'iyzico-installment'),
            'connectionError' => __('API bağlantısı başarısız.', 'iyzico-installment')
        ));
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        $settings = $this->settings->get_all();
        ?>
        <div class="wrap iyzico-settings-wrapper">
            <h1><?php echo esc_html__('iyzico Taksit Seçenekleri', 'iyzico-installment'); ?></h1>

            <div class="iyzico-admin-container">
                <div class="iyzico-admin-header">
                    <div class="iyzico-logo-container">
                        <?php 
                        // Display logo using WordPress functions
                        if (function_exists('wp_get_attachment_image')) {
                            // Check if logo exists in media library
                            $logo_attachment_id = attachment_url_to_postid(IYZI_INSTALLMENT_ASSETS_URL . '/images/iyzico-logo.svg');
                            
                            if ($logo_attachment_id) {
                                echo wp_get_attachment_image($logo_attachment_id, 'full', false, array(
                                    'class' => 'iyzico-logo',
                                    'alt' => esc_attr__('iyzico', 'iyzico-installment'),
                                ));
                            } else {
                                // Use SVG directly with proper wrapper for better accessibility
                                $svg_url = esc_url(IYZI_INSTALLMENT_ASSETS_URL . '/images/iyzico-logo.svg');
                                ?>
                                <div class="iyzico-logo-wrapper">
                                    <object type="image/svg+xml" data="<?php echo esc_url($svg_url); ?>" class="iyzico-logo">
                                        <?php esc_html_e('iyzico', 'iyzico-installment'); ?>
                                    </object>
                                </div>
                                <?php
                            }
                        } else {
                            // Use SVG directly with proper wrapper for better accessibility
                            $svg_url = esc_url(IYZI_INSTALLMENT_ASSETS_URL . '/images/iyzico-logo.svg');
                            ?>
                            <div class="iyzico-logo-wrapper">
                                <object type="image/svg+xml" data="<?php echo esc_url($svg_url); ?>" class="iyzico-logo">
                                    <?php esc_html_e('iyzico', 'iyzico-installment'); ?>
                                </object>
                            </div>
                            <?php
                        }
                        ?>
                        <span class="iyzico-version">v<?php echo esc_html(IYZI_INSTALLMENT_VERSION); ?></span>
                    </div>
                </div>

                <div class="iyzico-admin-content">
                    <form method="post" action="options.php">
                        <?php settings_fields('iyzico_installment_options'); ?>

                        <div class="iyzico-settings-section">
                            <h2><?php echo esc_html__('API Ayarları', 'iyzico-installment'); ?></h2>
                            
                            <div class="iyzico-form-group">
                                <label for="iyzico_mode"><?php echo esc_html__('Ortam', 'iyzico-installment'); ?></label>
                                <select id="iyzico_mode" name="<?php echo esc_attr(Iyzico_Installment_Settings::OPTION_KEY); ?>[mode]" class="iyzico-form-control">
                                    <option value="sandbox" <?php selected($settings['mode'], 'sandbox'); ?>>
                                        <?php echo esc_html__('Test (Sandbox)', 'iyzico-installment'); ?>
                                    </option>
                                    <option value="live" <?php selected($settings['mode'], 'live'); ?>>
                                        <?php echo esc_html__('Canlı (Live)', 'iyzico-installment'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php echo esc_html__('Test ortamında gerçek kartlar ile işlem yapamazsınız.', 'iyzico-installment'); ?>
                                </p>
                            </div>

                            <div class="iyzico-form-group">
                                <label><?php echo esc_html__('API Bağlantı Durumu', 'iyzico-installment'); ?></label>
                                <div class="iyzico-api-status <?php echo esc_attr($this->get_connection_status_class()); ?>">
                                    <?php echo esc_html($this->get_connection_status_text()); ?>
                                </div>
                                <p class="description">
                                    <?php echo esc_html__('API bağlantı durumunu gösterir.', 'iyzico-installment'); ?>
                                </p>
                            </div>
                            
                            <div class="iyzico-form-group">
                                <label for="iyzico_api_key"><?php echo esc_html__('API Key', 'iyzico-installment'); ?></label>
                                <input type="text" id="iyzico_api_key" name="<?php echo esc_attr(Iyzico_Installment_Settings::OPTION_KEY); ?>[api_key]" 
                                    value="<?php echo esc_attr($settings['api_key']); ?>" class="regular-text iyzico-form-control">
                            </div>

                            <div class="iyzico-form-group">
                                <label for="iyzico_secret_key"><?php echo esc_html__('Secret Key', 'iyzico-installment'); ?></label>
                                <input type="password" id="iyzico_secret_key" name="<?php echo esc_attr(Iyzico_Installment_Settings::OPTION_KEY); ?>[secret_key]" 
                                    value="<?php echo esc_attr($settings['secret_key']); ?>" class="regular-text iyzico-form-control">
                            </div>

                            <div class="iyzico-form-group">
                                <button type="button" id="iyzico-test-api" class="button button-secondary">
                                    <?php echo esc_html__('API Bağlantısını Test Et', 'iyzico-installment'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="iyzico-settings-section">
                            <h2><?php echo esc_html__('Görüntüleme Ayarları', 'iyzico-installment'); ?></h2>
                            
                            <div class="iyzico-form-group">
                                <label for="iyzico_integration_type"><?php echo esc_html__('Çalışma Şekli', 'iyzico-installment'); ?></label>
                                <select id="iyzico_integration_type" name="<?php echo esc_attr(Iyzico_Installment_Settings::OPTION_KEY); ?>[integration_type]" class="iyzico-form-control">
                                    <option value="shortcode" <?php selected($settings['integration_type'], 'shortcode'); ?>>
                                        <?php echo esc_html__('Sadece Shortcode Kullan (Ürün Sayfasına Ekleme)', 'iyzico-installment'); ?>
                                    </option>
                                    <option value="direct" <?php selected($settings['integration_type'], 'direct'); ?>>
                                        <?php echo esc_html__('Shortcode + Ürün Sayfasında Tab Olarak Göster', 'iyzico-installment'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php echo esc_html__('Shortcode her zaman çalışır. Bu ayar ürün sayfasında taksit tabını gösterip göstermemeyi belirler.', 'iyzico-installment'); ?>
                                </p>
                            </div>

                            <div class="iyzico-form-group">
                                <label><?php echo esc_html__('Shortcode', 'iyzico-installment'); ?></label>
                                <div class="iyzico-shortcode-box">
                                    <code id="iyzico-shortcode">[iyzico_installment price="1000" bin=""]</code>
                                    <button type="button" class="iyzico-copy-shortcode button">
                                        <?php echo esc_html__('Kopyala', 'iyzico-installment'); ?>
                                    </button>
                                </div>
                                <p class="description">
                                    <?php echo esc_html__('Bu shortcode\'u taksit bilgilerini göstermek istediğiniz yere ekleyin.', 'iyzico-installment'); ?>
                                </p>
                            </div>
                        </div>

                        <div class="iyzico-settings-section">
                            <h2><?php echo esc_html__('KDV ve Dinamik Taksit Ayarları', 'iyzico-installment'); ?></h2>
                            
                            <div class="iyzico-form-group">
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr(Iyzico_Installment_Settings::OPTION_KEY); ?>[enable_vat]" value="1" <?php checked($settings['enable_vat'], true); ?>>
                                    <?php echo esc_html__('KDV Dahil Fiyat Hesapla', 'iyzico-installment'); ?>
                                </label>
                                <p class="description">
                                    <?php echo esc_html__('Bu seçenek aktif edildiğinde, taksit hesaplamaları KDV dahil fiyat üzerinden yapılır.', 'iyzico-installment'); ?>
                                </p>
                            </div>

                            <div class="iyzico-form-group">
                                <label for="iyzico_vat_rate"><?php echo esc_html__('KDV Oranı (%)', 'iyzico-installment'); ?></label>
                                <input type="number" id="iyzico_vat_rate" name="<?php echo esc_attr(Iyzico_Installment_Settings::OPTION_KEY); ?>[vat_rate]" 
                                    value="<?php echo esc_attr($settings['vat_rate']); ?>" min="0" max="100" step="0.01" class="small-text iyzico-form-control">
                                <p class="description">
                                    <?php echo esc_html__('KDV oranını yüzde olarak girin. Örnek: 20', 'iyzico-installment'); ?>
                                </p>
                            </div>

                            <div class="iyzico-form-group">
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr(Iyzico_Installment_Settings::OPTION_KEY); ?>[enable_dynamic_installments]" value="1" <?php checked($settings['enable_dynamic_installments'], true); ?>>
                                    <?php echo esc_html__('Dinamik Taksit Sistemi Aktif', 'iyzico-installment'); ?>
                                </label>
                                <p class="description">
                                    <?php echo esc_html__('Bu seçenek aktif edildiğinde, varyasyonlu ürünlerde dinamik taksit sistemi çalışır.', 'iyzico-installment'); ?>
                                </p>
                            </div>

                            <div class="iyzico-form-group">
                                <label><?php echo esc_html__('Dinamik Taksit Shortcode', 'iyzico-installment'); ?></label>
                                <div class="iyzico-shortcode-box">
                                    <code id="iyzico-dynamic-shortcode">[dynamic_iyzico_installment]</code>
                                    <button type="button" class="iyzico-copy-shortcode button" data-target="iyzico-dynamic-shortcode">
                                        <?php echo esc_html__('Kopyala', 'iyzico-installment'); ?>
                                    </button>
                                </div>
                                <p class="description">
                                    <?php echo esc_html__('Bu shortcode varyasyonlu ürünlerde dinamik taksit gösterimi için kullanılır.', 'iyzico-installment'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php submit_button(esc_html__('Ayarları Kaydet', 'iyzico-installment'), 'primary', 'submit', true); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get connection status class
     *
     * @return string
     */
    private function get_connection_status_class()
    {
        if (!$this->settings->has_credentials()) {
            return 'disconnected';
        }

        $status = get_transient('iyzico_installment_api_status');
        return $status ? 'connected' : 'disconnected';
    }

    /**
     * Get connection status text
     *
     * @return string
     */
    private function get_connection_status_text()
    {
        if (!$this->settings->has_credentials()) {
            return __('Bağlantı Yapılandırılmadı', 'iyzico-installment');
        }

        $status = get_transient('iyzico_installment_api_status');
        return $status ? __('Bağlantı Başarılı!', 'iyzico-installment') : __('Bağlantı Başarısız!', 'iyzico-installment');
    }

    /**
     * Ajax handler for testing API connection
     */
    public function ajax_test_api()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'iyzico_installment_nonce')) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız.', 'iyzico-installment')));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok.', 'iyzico-installment')));
        }

        // Get credentials
        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
        $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';

        if (empty($api_key) || empty($secret_key)) {
            wp_send_json_error(array('message' => __('API Key ve Secret Key alanları boş olamaz.', 'iyzico-installment')));
        }

        // Test API
        try {
            $options = new Options();
            $options->setApiKey($api_key);
            $options->setSecretKey($secret_key);

            // Ortam URL'si
            $mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'sandbox';
            $base_url = ($mode === 'live') ? 'https://api.iyzipay.com' : 'https://sandbox-api.iyzipay.com';
            $options->setBaseUrl($base_url);

            $test = ApiTest::retrieve($options);

            if ($test->getStatus() === 'success') {
                // Update settings
                $this->settings->update_multiple(array(
                    'api_key' => $api_key,
                    'secret_key' => $secret_key,
                    'mode' => $mode
                ));

                // Store connection status
                set_transient('iyzico_installment_api_status', true, DAY_IN_SECONDS);

                wp_send_json_success();
            } else {
                delete_transient('iyzico_installment_api_status');
                wp_send_json_error(array('message' => $test->getErrorMessage()));
            }
        } catch (\Exception $e) {
            delete_transient('iyzico_installment_api_status');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
} 