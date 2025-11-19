<?php

if (!defined('ABSPATH')) {
    exit;
}

use Iyzipay\Model\InstallmentInfo;
use Iyzipay\Options;
use Iyzipay\Request\RetrieveInstallmentInfoRequest;

/**
 * iyzico Installment API
 */
class Iyzico_Installment_API
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
        
        // Register AJAX endpoints
        add_action('wp_ajax_iyzico_get_installment_info', array($this, 'ajax_get_installment_info'));
        add_action('wp_ajax_nopriv_iyzico_get_installment_info', array($this, 'ajax_get_installment_info'));
    }

    /**
     * Get iyzico options
     *
     * @return \Iyzipay\Options
     */
    private function get_options()
    {
        $options = new Options();
        $options->setApiKey($this->settings->get_api_key());
        $options->setSecretKey($this->settings->get_secret_key());
        $options->setBaseUrl($this->settings->get_api_url());
        
        return $options;
    }

    /**
     * Get installment info
     *
     * @param float $price Product price.
     * @param string $bin_number Credit card BIN number (optional).
     *
     * @return array|\WP_Error
     */
    public function get_installment_info($price, $bin_number = '')
    {
        if (!$this->settings->has_credentials()) {
            return new \WP_Error('missing_credentials', __('API kimlik bilgileri eksik.', 'iyzico-installment'));
        }
        
        try {
            $options = $this->get_options();
            
            $request = new RetrieveInstallmentInfoRequest();
            $request->setLocale('tr');
            $request->setConversationId(uniqid('iyzico_installment_'));
            $request->setPrice($price);
            $request->setBinNumber($bin_number);
            
            $response = InstallmentInfo::retrieve($request, $options);
            
            if ($response->getStatus() === 'success') {
                return $this->format_installment_response($response);
            } else {
                return new \WP_Error(
                    'api_error',
                    $response->getErrorMessage() ?: __('Taksit bilgileri alınırken bir hata oluştu.', 'iyzico-installment')
                );
            }
        } catch (\Exception $e) {
            return new \WP_Error('api_exception', $e->getMessage());
        }
    }

    /**
     * Format installment response
     *
     * @param \Iyzipay\Model\InstallmentInfo $response API response.
     *
     * @return array
     */
    private function format_installment_response($response)
    {
        return array(
            'status' => $response->getStatus(),
            'conversationId' => $response->getConversationId(),
            'installmentDetails' => $this->get_installment_details($response)
        );
    }

    /**
     * Get installment details
     *
     * @param \Iyzipay\Model\InstallmentInfo $response API response.
     *
     * @return array
     */
    private function get_installment_details($response)
    {
        $installmentDetails = $response->getInstallmentDetails();
        $result = array();
        
        if ($installmentDetails) {
            foreach ($installmentDetails as $detail) {
                $result[] = array(
                    'binNumber' => $detail->getBinNumber(),
                    'price' => $detail->getPrice(),
                    'cardType' => $detail->getCardType(),
                    'cardAssociation' => $detail->getCardAssociation(),
                    'cardFamilyName' => $detail->getCardFamilyName(),
                    'force3ds' => $detail->getForce3ds(),
                    'bankCode' => $detail->getBankCode(),
                    'bankName' => $detail->getBankName(),
                    'forceCvc' => $detail->getForceCvc(),
                    'installmentPrices' => $this->get_installment_prices($detail)
                );
            }
        }
        
        return $result;
    }

    /**
     * Get installment prices
     *
     * @param \Iyzipay\Model\InstallmentDetail $detail Installment detail.
     *
     * @return array
     */
    private function get_installment_prices($detail)
    {
        $installmentPrices = $detail->getInstallmentPrices();
        $result = array();
        
        if ($installmentPrices) {
            foreach ($installmentPrices as $price) {
                $result[] = array(
                    'installmentNumber' => $price->getInstallmentNumber(),
                    'totalPrice' => $price->getTotalPrice(),
                    'installmentPrice' => $price->getInstallmentPrice()
                );
            }
        }
        
        return $result;
    }

    /**
     * Ajax handler for getting installment info
     */
    public function ajax_get_installment_info()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'iyzico_installment_nonce')) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız.', 'iyzico-installment')));
        }
        
        $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
        $bin_number = isset($_POST['bin_number']) ? sanitize_text_field(wp_unslash($_POST['bin_number'])) : '';
        
        if ($price <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz fiyat.', 'iyzico-installment')));
        }
        
        $response = $this->get_installment_info($price, $bin_number);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        } else {
            wp_send_json_success($response);
        }
    }
} 