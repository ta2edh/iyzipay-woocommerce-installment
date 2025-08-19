<?php
/**
 * Plugin Name: iyzico Installment
 * Description: iyzico Installment for WooCommerce.
 * Version: 1.0.0
 * Requires at least: 6.6
 * WC requires at least: 9.3.3
 * Requires PHP: 7.4.33
 * Author: iyzico
 * Author URI: https://iyzico.com
 * Text Domain: iyzico-installment
 * Domain Path: /i18n/languages/
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins: woocommerce, iyzico-woocommerce
 *
 * Tested up to: 6.8
 * WC tested up to: 9.7.1
 * WC_HPOS_Compatibility: true
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('IYZI_INSTALLMENT_VERSION', '1.0.0');
define('IYZI_INSTALLMENT_FILE', __FILE__);
define('IYZI_INSTALLMENT_PATH', plugin_dir_path(__FILE__));
define('IYZI_INSTALLMENT_URL', plugin_dir_url(__FILE__));
define('IYZI_INSTALLMENT_ASSETS_URL', plugin_dir_url(__FILE__) . 'assets');

// Step 1: Load logger
require_once IYZI_INSTALLMENT_PATH . 'includes/class-iyzico-installment-logger.php';

// Initialize logger
$GLOBALS['iyzico_logger'] = new Iyzico_Installment_Logger();
$GLOBALS['iyzico_logger']->info('iyzico Installment plugin loading - Step 2 with logger and settings');

// Step 2: Load settings class
require_once IYZI_INSTALLMENT_PATH . 'includes/class-iyzico-installment-settings.php';
$GLOBALS['iyzico_settings'] = new Iyzico_Installment_Settings();
$GLOBALS['iyzico_logger']->info('Settings class loaded successfully');

// Step 3: Load API class
require_once IYZI_INSTALLMENT_PATH . 'includes/class-iyzico-installment-api.php';
$GLOBALS['iyzico_api'] = new Iyzico_Installment_API($GLOBALS['iyzico_settings']);
$GLOBALS['iyzico_logger']->info('API class loaded successfully');

// Step 4: Load Frontend class
require_once IYZI_INSTALLMENT_PATH . 'includes/class-iyzico-installment-frontend.php';
$GLOBALS['iyzico_frontend'] = new Iyzico_Installment_Frontend($GLOBALS['iyzico_settings'], $GLOBALS['iyzico_api']);
$GLOBALS['iyzico_logger']->info('Frontend class loaded successfully');

// Step 5: Load Hpos class
require_once IYZI_INSTALLMENT_PATH . 'includes/class-iyzico-installment-hpos.php';
Iyzico_Installment_Hpos::init();
$GLOBALS['iyzico_logger']->info('Hpos class loaded successfully');

// Step 6: Load Admin class
require_once IYZI_INSTALLMENT_PATH . 'includes/admin/class-iyzico-installment-admin.php';
$GLOBALS['iyzico_admin'] = new Iyzico_Installment_Admin($GLOBALS['iyzico_settings']);
$GLOBALS['iyzico_logger']->info('Admin class loaded successfully');