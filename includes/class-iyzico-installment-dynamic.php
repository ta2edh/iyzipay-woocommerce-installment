<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dynamic Installment System for Variable Products
 */
class Iyzico_Installment_Dynamic
{
    private $settings;
    private $api;

    public function __construct(Iyzico_Installment_Settings $settings, Iyzico_Installment_API $api)
    {
        $this->settings = $settings;
        $this->api = $api;

        // Only initialize if dynamic installments are enabled
        if ($this->settings->is_dynamic_installments_enabled()) {
            $this->init_hooks();
        }
    }

    private function init_hooks()
    {
        add_action('wp_ajax_get_installment_options', array($this, 'get_installment_options'));
        add_action('wp_ajax_nopriv_get_installment_options', array($this, 'get_installment_options'));
        add_shortcode('dynamic_iyzico_installment', array($this, 'dynamic_installment_shortcode'));
        add_action('wp_footer', array($this, 'add_footer_script'));
        add_action('wp_head', array($this, 'add_installment_styles'));
    }

    public function add_installment_styles()
    {
        if (is_product()) {
            ?>
            <style>
                <?php
                // Add custom CSS if available
                $custom_css = $this->settings->get_custom_css();
                if (!empty($custom_css)) {
                    // Sanitize and output custom CSS
                    $custom_css = wp_strip_all_tags($custom_css);
                    $custom_css = str_replace(array(
                        '<script', '</script', 'javascript:', 'expression(', 'eval(', 
                        'onclick=', 'onload=', 'onerror=', 'onmouseover=', '@import',
                        'behavior:', '-moz-binding:', 'vbscript:', 'mocha:', 'livescript:'
                    ), '', $custom_css);
                    
                    // Only output if it looks like valid CSS - DON'T escape HTML here as it breaks CSS
                    if (preg_match('/[{;}]/', $custom_css) && !preg_match('/<[^>]*>/', $custom_css)) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $custom_css;
                    }
                }
                ?>
            </style>
            <?php
        }
    }

    public function add_footer_script()
    {
        if (is_product()) {
            global $product;
            $current_product_id = $product->get_id();
            $current_price = $product->get_price();
            
            // Apply VAT if enabled
            $price_with_vat = $this->settings->calculate_price_with_vat($current_price);
            ?>
            <script type="text/javascript">
            window.installment_ajax = {
                ajax_url: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
                nonce: <?php echo wp_json_encode(wp_create_nonce('installment_nonce')); ?>,
                product_id: <?php echo intval($current_product_id); ?>,
                server_price: <?php echo floatval($current_price); ?>,
                price_with_vat: <?php echo floatval($price_with_vat); ?>,
                vat_enabled: <?php echo $this->settings->is_vat_enabled() ? 'true' : 'false'; ?>,
                vat_rate: <?php echo floatval($this->settings->get_vat_rate()); ?>
            };
            
            console.log('=== NEW PAGE LOAD ===');
            console.log('Product ID:', window.installment_ajax.product_id);
            console.log('Server Price:', window.installment_ajax.server_price);
            console.log('Price with VAT:', window.installment_ajax.price_with_vat);
            console.log('VAT Enabled:', window.installment_ajax.vat_enabled);
            
            jQuery(document).ready(function($) {
                $('.dynamic-iyzico-installment').empty();
                
                setTimeout(function() {
                    loadPrice();
                }, 800);
                
                $(document).on('found_variation', 'form.variations_form', function(event, variation) {
                    console.log('=== VARIATION FOUND ===');
                    console.log('Variation price:', variation.display_price);
                    
                    if (variation && variation.display_price) {
                        var finalPrice = variation.display_price;
                        
                        // Apply VAT if enabled
                        if (window.installment_ajax.vat_enabled) {
                            finalPrice = finalPrice * (1 + (window.installment_ajax.vat_rate / 100));
                        }
                        
                        console.log('Final price with VAT:', finalPrice);
                        loadInstallments(finalPrice);
                    }
                });

                $(document).on('reset_data', 'form.variations_form', function() {
                    console.log('=== VARIATION RESET ===');
                    $('.dynamic-iyzico-installment').html('<p><?php echo esc_js(__('Lütfen bir seçenek belirleyin.', 'iyzico-installment')); ?></p>');
                });

                function loadPrice() {
                    var isVariableProduct = $('form.variations_form').length > 0;
                    
                    if (isVariableProduct) {
                        $('.dynamic-iyzico-installment').html('<p><?php echo esc_js(__('Lütfen bir seçenek belirleyin.', 'iyzico-installment')); ?></p>');
                        return;
                    }
                    
                    var price = window.installment_ajax.price_with_vat;
                    console.log('Using price with VAT:', price);
                    
                    if (price > 0) {
                        loadInstallments(price);
                    } else {
                        $('.dynamic-iyzico-installment').html('<p><?php echo esc_js(__('Fiyat bilgisi bulunamadı.', 'iyzico-installment')); ?></p>');
                    }
                }

                function loadInstallments(price) {
                    console.log('=== LOADING INSTALLMENTS ===');
                    console.log('Product ID:', window.installment_ajax.product_id);
                    console.log('Price:', price);
                    
                    var containers = $('.dynamic-iyzico-installment');
                    
                    if (containers.length > 0 && price > 0) {
                        containers.html('<p><?php echo esc_js(__('Taksit yükleniyor...', 'iyzico-installment')); ?></p>');
                        
                        $.ajax({
                            url: window.installment_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'get_installment_options',
                                price: price,
                                product_id: window.installment_ajax.product_id,
                                nonce: window.installment_ajax.nonce
                            },
                            cache: false,
                            success: function(response) {
                                console.log('=== AJAX SUCCESS ===');
                                console.log('Response:', response);
                                
                                if (response.success) {
                                    containers.html(response.data);
                                } else {
                                    containers.html('<p><?php echo esc_js(__('Hata:', 'iyzico-installment')); ?> ' + (response.data || '<?php echo esc_js(__('Bilinmeyen hata', 'iyzico-installment')); ?>') + '</p>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log('=== AJAX ERROR ===');
                                console.log('Error:', error);
                                containers.html('<p><?php echo esc_js(__('Bağlantı hatası', 'iyzico-installment')); ?></p>');
                            }
                        });
                    }
                }
            });
            </script>
            <?php
        }
    }

    public function dynamic_installment_shortcode($atts)
    {
        return '<div class="dynamic-iyzico-installment">' . esc_html__('Taksit seçenekleri yükleniyor...', 'iyzico-installment') . '</div>';
    }

    public function get_installment_options()
    {
        // Nonce check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'installment_nonce')) {
            wp_send_json_error(__('Security check failed', 'iyzico-installment'));
        }

        // Check if dynamic installments are enabled
        if (!$this->settings->is_dynamic_installments_enabled()) {
            wp_send_json_error(__('Dynamic installments are not enabled', 'iyzico-installment'));
        }

        // Check if API credentials are set
        if (!$this->settings->has_credentials()) {
            wp_send_json_error(__('API credentials are not configured', 'iyzico-installment'));
        }
        
        // Input validation ve sanitization
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        // Price validation
        if ($price <= 0 || $price > 1000000) {
            wp_send_json_error(__('Invalid price', 'iyzico-installment'));
        }
        
        // Product ID validation
        if ($product_id <= 0) {
            wp_send_json_error(__('Invalid product ID', 'iyzico-installment'));
        }
        
        // Check that the product actually exists
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Product not found', 'iyzico-installment'));
        }
        
        // Log 
        error_log("Dynamic installment request - Product ID: $product_id, Price: $price");
        
        // Cache busting
        nocache_headers();
        
        // Get installment info using the API
        $installment_info = $this->api->get_installment_info($price, '');
        
        if (is_wp_error($installment_info)) {
            wp_send_json_error($installment_info->get_error_message());
        }
        
        // Render the installment table
        $installment_html = $this->render_installment_table($installment_info);
        
        // Output sanitization
        wp_send_json_success(wp_kses_post($installment_html));
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
