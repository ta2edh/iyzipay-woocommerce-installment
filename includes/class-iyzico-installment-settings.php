<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * iyzico Installment Settings
 */
class Iyzico_Installment_Settings
{
    /**
     * Option key in the database
     */
    const OPTION_KEY = 'iyzico_installment_settings';

    /**
     * Default settings
     *
     * @var array
     */
    private $defaults = array(
        'api_key' => '',
        'secret_key' => '',
        'integration_type' => 'shortcode',
        'mode' => 'sandbox',
        'enable_vat' => false,
        'vat_rate' => 20,
        'enable_dynamic_installments' => false
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize settings
        $this->initialize_settings();
    }

    /**
     * Initialize settings
     */
    private function initialize_settings()
    {
        $settings = get_option(self::OPTION_KEY, array());
        
        if (empty($settings)) {
            update_option(self::OPTION_KEY, $this->defaults);
        }
    }

    /**
     * Get all settings
     *
     * @return array
     */
    public function get_all()
    {
        $settings = get_option(self::OPTION_KEY, $this->defaults);
        return wp_parse_args($settings, $this->defaults);
    }

    /**
     * Get single setting
     *
     * @param string $key Setting key.
     * @param mixed $default Default value.
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $settings = $this->get_all();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Update single setting
     *
     * @param string $key Setting key.
     * @param mixed $value Setting value.
     * @return bool
     */
    public function update($key, $value)
    {
        $settings = $this->get_all();
        $settings[$key] = $value;
        return update_option(self::OPTION_KEY, $settings);
    }

    /**
     * Update multiple settings
     *
     * @param array $new_settings New settings.
     * @return bool
     */
    public function update_multiple($new_settings)
    {
        $settings = $this->get_all();
        $settings = wp_parse_args($new_settings, $settings);
        return update_option(self::OPTION_KEY, $settings);
    }

    /**
     * Delete settings
     *
     * @return bool
     */
    public function delete()
    {
        return delete_option(self::OPTION_KEY);
    }

    /**
     * Check if API credentials are set
     *
     * @return bool
     */
    public function has_credentials()
    {
        $settings = $this->get_all();
        return !empty($settings['api_key']) && !empty($settings['secret_key']);
    }

    /**
     * Get API key
     *
     * @return string
     */
    public function get_api_key()
    {
        return $this->get('api_key', '');
    }

    /**
     * Get secret key
     *
     * @return string
     */
    public function get_secret_key()
    {
        return $this->get('secret_key', '');
    }

    /**
     * Get API URL based on mode
     *
     * @return string
     */
    public function get_api_url()
    {
        $mode = $this->get('mode', 'sandbox');
        return ($mode === 'live') ? 'https://api.iyzipay.com' : 'https://sandbox-api.iyzipay.com';
    }

    /**
     * Get integration type
     *
     * @return string
     */
    public function get_integration_type()
    {
        return $this->get('integration_type', 'shortcode');
    }

    /**
     * Check if direct integration is enabled
     *
     * @return bool
     */
    public function is_direct_integration()
    {
        return $this->get_integration_type() === 'direct';
    }

    /**
     * Check if tabs should be displayed on product page
     * Alias for is_direct_integration for better readability
     *
     * @return bool
     */
    public function show_product_tabs()
    {
        return $this->is_direct_integration();
    }

    /**
     * Check if VAT is enabled
     *
     * @return bool
     */
    public function is_vat_enabled()
    {
        return $this->get('enable_vat', false);
    }

    /**
     * Get VAT rate
     *
     * @return float
     */
    public function get_vat_rate()
    {
        return floatval($this->get('vat_rate', 20));
    }

    /**
     * Check if dynamic installments are enabled
     *
     * @return bool
     */
    public function is_dynamic_installments_enabled()
    {
        return $this->get('enable_dynamic_installments', false);
    }

    /**
     * Calculate price with VAT
     *
     * @param float $price Base price
     * @return float
     */
    public function calculate_price_with_vat($price)
    {
        if (!$this->is_vat_enabled()) {
            return $price;
        }
        
        $vat_rate = $this->get_vat_rate();
        return $price * (1 + ($vat_rate / 100));
    }
} 