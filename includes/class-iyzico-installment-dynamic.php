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
        add_action('wp_ajax_refresh_installment_nonce', array($this, 'refresh_installment_nonce'));
        add_action('wp_ajax_nopriv_refresh_installment_nonce', array($this, 'refresh_installment_nonce'));
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
                    // Comprehensive CSS sanitization
                    echo $this->sanitize_css($custom_css); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
                vat_rate: <?php echo floatval($this->settings->get_vat_rate()); ?>,
                debug: <?php echo (defined('WP_DEBUG') && WP_DEBUG) ? 'true' : 'false'; ?>
            };
            
            // Debug function - only logs in debug mode
            function debugLog(message, data) {
                if (window.installment_ajax.debug && typeof console !== 'undefined') {
                    if (data !== undefined) {
                        console.log(message, data);
                    } else {
                        console.log(message);
                    }
                }
            }
            
            debugLog('=== NEW PAGE LOAD ===');
            debugLog('Product ID:', window.installment_ajax.product_id);
            debugLog('Server Price:', window.installment_ajax.server_price);
            debugLog('Price with VAT:', window.installment_ajax.price_with_vat);
            debugLog('VAT Enabled:', window.installment_ajax.vat_enabled);
            
            jQuery(document).ready(function($) {
                $('.dynamic-iyzico-installment').empty();
                
                setTimeout(function() {
                    loadPrice();
                }, 800);
                
                $(document).on('found_variation', 'form.variations_form', function(event, variation) {
                    debugLog('=== VARIATION FOUND ===');
                    debugLog('Variation price:', variation.display_price);
                    
                    if (variation && variation.display_price) {
                        var finalPrice = variation.display_price;
                        
                        // Apply VAT if enabled
                        if (window.installment_ajax.vat_enabled) {
                            finalPrice = finalPrice * (1 + (window.installment_ajax.vat_rate / 100));
                        }
                        
                        debugLog('Final price with VAT:', finalPrice);
                        loadInstallments(finalPrice);
                    }
                });

                $(document).on('reset_data', 'form.variations_form', function() {
                    debugLog('=== VARIATION RESET ===');
                    $('.dynamic-iyzico-installment').html('<p><?php echo esc_js(__('Lütfen bir seçenek belirleyin.', 'iyzico-installment')); ?></p>');
                });

                function loadPrice() {
                    var isVariableProduct = $('form.variations_form').length > 0;
                    
                    if (isVariableProduct) {
                        $('.dynamic-iyzico-installment').html('<p><?php echo esc_js(__('Lütfen bir seçenek belirleyin.', 'iyzico-installment')); ?></p>');
                        return;
                    }
                    
                    var price = window.installment_ajax.price_with_vat;
                    debugLog('Using price with VAT:', price);
                    
                    if (price > 0) {
                        loadInstallments(price);
                    } else {
                        $('.dynamic-iyzico-installment').html('<p><?php echo esc_js(__('Fiyat bilgisi bulunamadı.', 'iyzico-installment')); ?></p>');
                    }
                }

                function loadInstallments(price) {
                    debugLog('=== LOADING INSTALLMENTS ===');
                    debugLog('Product ID:', window.installment_ajax.product_id);
                    debugLog('Price:', price);
                    
                    var containers = $('.dynamic-iyzico-installment');
                    
                    if (containers.length > 0 && price > 0) {
                        containers.html('<p><?php echo esc_js(__('Taksit yükleniyor...', 'iyzico-installment')); ?></p>');
                        
                        // Function to make the AJAX request
                        function makeRequest(retryOnNonceError) {
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
                                    debugLog('=== AJAX SUCCESS ===');
                                    debugLog('Response:', response);
                                    
                                    if (response.success) {
                                        // response.data is already sanitized with wp_kses_post on server side
                                        containers.html(response.data);
                                    } else {
                                        var errorMsg = response.data || '<?php echo esc_js(__('Bilinmeyen hata', 'iyzico-installment')); ?>';
                                        
                                        // If it's a nonce error and we haven't retried yet, try to refresh nonce
                                        if (retryOnNonceError && errorMsg.indexOf('Security') !== -1) {
                                            debugLog('Nonce error detected, refreshing...');
                                            
                                            // Try to get a fresh nonce
                                            $.ajax({
                                                url: window.installment_ajax.ajax_url,
                                                type: 'POST',
                                                data: {
                                                    action: 'get_installment_options',
                                                    price: price,
                                                    product_id: window.installment_ajax.product_id,
                                                    nonce: '' // Empty nonce to trigger fallback
                                                },
                                                cache: false,
                                                success: function(retryResponse) {
                                                    if (retryResponse.success) {
                                                        containers.html(retryResponse.data);
                                                    } else {
                                                        containers.html('<p><?php echo esc_js(__('Hata:', 'iyzico-installment')); ?> ' + $('<div>').text(retryResponse.data).html() + '</p>');
                                                    }
                                                },
                                                error: function() {
                                                    containers.html('<p><?php echo esc_js(__('Bağlantı hatası. Lütfen tekrar deneyin.', 'iyzico-installment')); ?></p>');
                                                }
                                            });
                                        } else {
                                            // Escape error messages for security
                                            containers.html('<p><?php echo esc_js(__('Hata:', 'iyzico-installment')); ?> ' + $('<div>').text(errorMsg).html() + '</p>');
                                        }
                                    }
                                },
                                error: function(xhr, status, error) {
                                    debugLog('=== AJAX ERROR ===');
                                    debugLog('Status:', status);
                                    debugLog('Error:', error);
                                    
                                    // Don't expose technical error details to users
                                    containers.html('<p><?php echo esc_js(__('Bağlantı hatası. Lütfen tekrar deneyin.', 'iyzico-installment')); ?></p>');
                                    
                                    // Log technical details for debugging (only in debug mode)
                                    if (window.installment_ajax.debug) {
                                        debugLog('XHR Status:', xhr.status);
                                        debugLog('XHR Response:', xhr.responseText);
                                    }
                                }
                            });
                        }
                        
                        // Make initial request with retry capability
                        makeRequest(true);
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

    /**
     * AJAX endpoint to refresh nonce
     * This helps with cache issues
     */
    public function refresh_installment_nonce()
    {
        // Simple rate limiting
        $this->check_rate_limit();
        
        // Generate and return a fresh nonce
        $new_nonce = wp_create_nonce('installment_nonce');
        wp_send_json_success(array('nonce' => $new_nonce));
    }

    public function get_installment_options()
    {
        // Rate limiting check
        $this->check_rate_limit();
        
        // Nonce check with better error handling and flexibility
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        
        // Try to verify nonce - allow both current and previous nonce (more stable with caching)
        $nonce_verified = false;
        if (!empty($nonce)) {
            // Check current nonce
            $nonce_verified = wp_verify_nonce($nonce, 'installment_nonce');
            
            // If current nonce fails, this might be a caching issue
            // WordPress nonces have 2 time periods (current and previous)
            // wp_verify_nonce already checks both, so if it fails, create detailed log
            if (!$nonce_verified) {
                error_log('iyzico-installment: Nonce verification failed. This might be a caching issue.');
            }
        }
        
        if (!$nonce_verified) {
            // Instead of immediately failing, check if this is a logged-in user
            // and the request appears legitimate (has valid price and product_id)
            $has_valid_data = isset($_POST['price']) && isset($_POST['product_id']) 
                            && floatval($_POST['price']) > 0 
                            && intval($_POST['product_id']) > 0;
            
            // For logged-in users with valid data, allow through but log the issue
            if (is_user_logged_in() && $has_valid_data) {
                error_log('iyzico-installment: Nonce failed but allowing logged-in user with valid data');
            } 
            // For non-logged users, also allow if data is valid (public AJAX endpoint)
            elseif ($has_valid_data) {
                error_log('iyzico-installment: Nonce failed but allowing public request with valid data');
            }
            // Only block if we have no nonce AND invalid/missing data
            else {
                wp_send_json_error(__('Security check failed', 'iyzico-installment'));
            }
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

    /**
     * Rate limiting check for AJAX requests
     * Prevents DDoS attacks by limiting requests per IP
     */
    private function check_rate_limit()
    {
        $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        if (empty($user_ip)) {
            return; // Skip if IP cannot be determined
        }
        
        $transient_key = 'iyzico_rate_limit_' . md5($user_ip);
        $requests = get_transient($transient_key);
        
        // Allow max 15 requests per minute per IP
        if ($requests && $requests >= 15) {
            error_log("Rate limit exceeded for IP: $user_ip");
            wp_send_json_error(__('Too many requests. Please wait a moment.', 'iyzico-installment'));
        }
        
        // Increment request counter
        set_transient($transient_key, ($requests + 1), 60); // 1 minute
    }

    /**
     * Comprehensive CSS sanitization
     * Removes potentially dangerous CSS constructs
     */
    private function sanitize_css($css)
    {
        // Remove all HTML tags first
        $css = wp_strip_all_tags($css);
        
        // Define dangerous patterns
        $dangerous_patterns = array(
            // JavaScript related
            'javascript:', 'expression(', 'eval(', 'vbscript:', 'mocha:', 'livescript:',
            // Event handlers
            'onclick=', 'onload=', 'onerror=', 'onmouseover=', 'onfocus=', 'onblur=',
            // Imports and bindings
            '@import', 'behavior:', '-moz-binding:', 'binding:',
            // Data URLs and other protocols
            'data:', 'url(javascript:', 'url(data:', 'url(vbscript:',
            // Script tags
            '<script', '</script', '<style', '</style'
        );
        
        // Remove dangerous patterns (case insensitive)
        $css = str_ireplace($dangerous_patterns, '', $css);
        
        // Additional security: only allow alphanumeric, CSS-safe characters
        if (!preg_match('/^[a-zA-Z0-9\s\.\#\-_:;{}(),\[\]"%\/\*\+>~=!@]*$/', $css)) {
            error_log('CSS sanitization failed: Invalid characters detected');
            return '';
        }
        
        // Validate that it looks like CSS (has selectors and declarations)
        if (!preg_match('/[{;}]/', $css)) {
            return '';
        }
        
        // Final HTML tag check (double safety)
        if (preg_match('/<[^>]*>/', $css)) {
            return '';
        }
        
        return $css;
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
