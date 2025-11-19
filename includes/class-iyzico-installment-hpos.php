<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * iyzico Installment Hpos Compatibility
 */
class Iyzico_Installment_Hpos
{
    public static function init()
	{
		add_action('before_woocommerce_init', [self::class, 'woocommerce_hpos_compatibility']);
	}

	public static function woocommerce_hpos_compatibility()
	{
		if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', IYZI_INSTALLMENT_FILE, true);
		}
	}
}