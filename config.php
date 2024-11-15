<?php
/**
 * Feature Flags to display in the admin bar.
 * 
 * This file can be copied and named "config.local.php" to make local adjustments.
 */

// WooCommerce PayPal Payments: 2.9.4
$wc_pp_feature_flags = [
	'wcpp/applepay_enabled'              => [
		'label'   => 'Apple Pay',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.applepay_enabled',
		'default' => fn() => getenv( 'PCP_APPLEPAY_ENABLED' ) !== '0',
	],
	'wcpp/googlepay_enabled'             => [
		'label'   => 'Google Pay',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.googlepay_enabled',
		'default' => fn() => getenv( 'PCP_GOOGLEPAY_ENABLED' ) !== '0',
	],
	'wcpp/saved_payment_checker_enabled' => [
		'label'   => 'Saved Payment Checker',
		'filter'  => 'woocommerce.deprecated_flags.woocommerce_paypal_payments.saved_payment_checker_enabled',
		'default' => fn() => getenv( 'PCP_SAVED_PAYMENT_CHECKER_ENABLED' ) === '1',
		'display' => false,
	],
	'wcpp/card_fields_enabled'           => [
		'label'   => 'Card Fields',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.card_fields_enabled',
		'default' => fn() => getenv( 'PCP_CARD_FIELDS_ENABLED' ) !== '0',
	],
	'wcpp/save_payment_methods_enabled'  => [
		'label'   => 'Vaulting',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.save_payment_methods_enabled',
		'default' => fn() => getenv( 'PCP_SAVE_PAYMENT_METHODS' ) !== '0',
	],
	'wcpp/axo_enabled'                   => [
		'label'   => 'Fastlane',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.axo_enabled',
		'default' => fn() => getenv( 'PCP_AXO_ENABLED' ) !== '0',
	],
	'wcpp/settings_enabled'              => [
		'label'   => 'Settings UI',
		'filter'  => 'woocommerce.feature-flags.woocommerce_paypal_payments.settings_enabled',
		'default' => fn() => getenv( 'PCP_SETTINGS_ENABLED' ) === '1',
	],
];

return $wc_pp_feature_flags;
