<?php
/**
 * Feature Flags to display in the admin bar.
 *
 * This file can be copied and named "config.local.php" to make local adjustments.
 */

// WooCommerce PayPal Payments: 2.9.4
$wc_pp_feature_flags = [
	// Feature flags.
	'wcpp/applepay_enabled'              => [
		'label'   => 'Apple Pay',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.applepay_enabled',
		'default' => static fn() => getenv( 'PCP_APPLEPAY_ENABLED' ) !== '0',
	],
	'wcpp/googlepay_enabled'             => [
		'label'   => 'Google Pay',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.googlepay_enabled',
		'default' => static fn() => getenv( 'PCP_GOOGLEPAY_ENABLED' ) !== '0',
	],
	'wcpp/saved_payment_checker_enabled' => [
		'label'   => 'Saved Payment Checker',
		'filter'  => 'woocommerce.deprecated_flags.woocommerce_paypal_payments.saved_payment_checker_enabled',
		'default' => static fn() => getenv( 'PCP_SAVED_PAYMENT_CHECKER_ENABLED' ) === '1',
		'display' => false,
	],
	'wcpp/card_fields_enabled'           => [
		'label'   => 'Card Fields',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.card_fields_enabled',
		'default' => static fn() => getenv( 'PCP_CARD_FIELDS_ENABLED' ) !== '0',
	],
	'wcpp/save_payment_methods_enabled'  => [
		'label'   => 'Vaulting',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.save_payment_methods_enabled',
		'default' => static fn() => getenv( 'PCP_SAVE_PAYMENT_METHODS' ) !== '0',
	],
	'wcpp/paylater_configurator_enabled' => [
		'label'   => 'PayLater Configurator',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.paylater_configurator_enabled',
		'default' => static fn() => getenv( 'PCP_PAYLATER_CONFIGURATOR' ) !== '0'
	],
	'wcpp/paylater_block_enabled'        => [
		'label'   => 'PayLater Block',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.paylater_block_enabled',
		'default' => static fn() => getenv( 'PCP_PAYLATER_BLOCK' ) !== '0'
	],
	'wcpp/paylater_wc_blocks_enabled'    => [
		'label'   => 'PayLater WC Blocks',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.paylater_wc_blocks_enabled',
		'default' => static fn() => getenv( 'PCP_PAYLATER_WC_BLOCKS' ) !== '0'
	],
	'wcpp/paylater_wc_blocks_cart_enabled'    => [
		'label'   => 'PayLater WC Blocks (cart under totals)',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.paylater_wc_blocks_cart_under_totals_enabled',
		'default' => true,
	],
	'wcpp/axo_enabled'                   => [
		'label'   => 'Fastlane',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.axo_enabled',
		'default' => static fn() => getenv( 'PCP_AXO_ENABLED' ) !== '0',
	],
	'wcpp/settings_enabled'              => [
		'label'   => 'Settings UI',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.settings_enabled',
		'default' => static fn() => getenv( 'PCP_SETTINGS_ENABLED' ) === '1',
	],
	'wcpp/contact_module_enabled'        => [
		'label'   => 'Contact Module',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.contact_module_enabled',
		'default' => static fn() => getenv( 'PCP_CONTACT_MODULE_ENABLED' ) === '1',
	],
	'wcpp/pwc_enabled'                   => [
		'label'   => 'Pay with Crypto',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.pwc_enabled',
		'default' => static fn() => getenv( 'PCP_PWC_ENABLED' ) === '1',
	],
	'wcpp/agentic_commerce_enabled'      => [
		'label'   => 'Agentic Commerce',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.agentic_commerce_enabled',
		'default' => static fn() => getenv( 'PCP_AGENTIC_COMMERCE_ENABLED' ) === '1',
	],

	// Plugin settings.
	'wcpp/bcdc-override'                   => [
		'label'   => 'BCDC Override',
		'filter'  => 'woocommerce_paypal_payments_override_acdc_status_with_bcdc',
		'default' => false,
	],
	'wcpp/logging'                         => [
		'label'   => 'Logging',
		'filter'  => 'woocommerce_paypal_payments_is_logging_enabled',
		'default' => false,
	],
];

return $wc_pp_feature_flags;
