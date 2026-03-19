<?php
/**
 * Default feature actions, displayed in the admin bar.
 *
 * Each action has a 'label' and a list of 'changes' to apply.
 * Supported change types:
 *   ['set_option', 'option_name', $value]            - Sets an option to the given value
 *   ['set_option_key', 'option_name', 'key', $value] - Changes a single element of an option-array
 *   ['delete_option', 'option_name']                 - Deletes an option
 *   ['do_action', 'action_name']                     - Fires a WordPress action hook
 *
 * This configuration can be modified via the "actions.local.php" file.
 */

return [
	'wcpp/header'              => [
		'label' => 'PayPal Payments',
	],
	'wcpp/run_migration'       => [
		'label'   => 'Run Update Actions',
		'changes' => [
			[ 'do_action', 'woocommerce_paypal_payments_gateway_migrate', '2.9.5' ],
		],
	],
	'wcpp/path_branded_only'   => [
		'label'   => 'Enter Branded-Only Experience',
		'changes' => [
			[
				'set_option_key',
				'woocommerce-ppcp-data-common',
				'wc_installation_path',
				'core-profiler',
			],
		],
	],
	'wcpp/path_whitelabel'     => [
		'label'   => 'Enter Whitelabel Experience',
		'changes' => [
			[
				'set_option_key',
				'woocommerce-ppcp-data-common',
				'wc_installation_path',
				'direct',
			],
		],
	],
	'wcpp/add_bcdc_override'   => [
		'label'   => 'Add BCDC Override Flag',
		'changes' => [
			[
				'set_option',
				'woocommerce_paypal_payments_bcdc_migration_override',
				'1',
			],
			[
				'do_action',
				'woocommerce_paypal_payments_clear_apm_product_status',
			],
		],
	],
	'wcpp/clear_bcdc_override' => [
		'label'   => 'Clear BCDC Override Flag',
		'changes' => [
			[
				'delete_option',
				'woocommerce_paypal_payments_bcdc_migration_override',
			],
			[
				'do_action',
				'woocommerce_paypal_payments_clear_apm_product_status',
			],
		],
	],
];
