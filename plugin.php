<?php
/**
 * @formatter:off
 * Plugin Name: Feature-Flag Manager
 * Plugin URI:  https://github.com/stracker-phil/wp-featureflags
 * Description: Development utility that allows toggling feature flags via WP filters
 * Author:      Philipp Stracker (Syde)
 * Version:     1.0.0
 * @formatter:on
 */

namespace Syde\WpFeatureFlags;

function getFeatureFlags() : array {
	$featureFlags  = [];
	$configSources = [
		__DIR__ . '/config.local.php',
		__DIR__ . '/config.php',
	];

	foreach ( $configSources as $path ) {
		if ( ! file_exists( $path ) ) {
			continue;
		}

		$featureFlags = require $path;
		break;
	}

	return (array) $featureFlags;
}

class FeatureFlags {
	/**
	 * @var string Option name to store all feature flags
	 */
	private const OPTION_NAME = 'wp_feature_flags';

	/**
	 * Stores the effectively used feature flags.
	 */
	private array $featureFlags = [];

	public function __construct( array $featureFlags ) {
		$this->featureFlags = $this->sanitizeFeatureFlags( $featureFlags );

		// No active/valid flags defined. We can stop here.
		if ( ! $this->featureFlags ) {
			return;
		}

		add_action( 'admin_bar_menu', [ $this, 'addAdminBarItems' ], 100 );
		add_action( 'init', [ $this, 'handleToggle' ] );
		add_action( 'plugins_loaded', [ $this, 'addFeatureFilters' ] );
	}

	protected function sanitizeFeatureFlags( array $featureFlags ) : array {
		$validFlags = [];

		foreach ( $featureFlags as $id => $flag ) {
			if (
				empty( $flag )
				|| ! is_array( $flag )
				|| empty( $flag['label'] )
				|| empty( $flag['filter'] )
				|| false === ( $flag['display'] ?? true )
			) {
				continue;
			}

			if ( empty( $flag['default'] ) ) {
				$flag['default'] = null;
			}

			$validFlags[ $id ] = $flag;
		}

		return $validFlags;
	}

	public function addAdminBarItems( $adminBar ) : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$menuId = 'wp-feature-flags';

		$adminBar->add_menu( [
			'id'    => $menuId,
			'title' => 'Feature Flags',
			'href'  => '#',
			'meta'  => [ 'title' => 'Feature Flag Manager' ],
		] );

		foreach ( $this->featureFlags as $id => $flag ) {
			$featureId    = $menuId . '_' . sanitize_key( $id );
			$state        = $this->getFeatureState( $id );
			$defaultValue = $this->getDefaultValue( $flag );

			$adminBar->add_node( [
				'parent' => $menuId,
				'id'     => $featureId,
				'title'  => esc_html( $flag['label'] ),
				'href'   => '#',
				'meta'   => [ 'class' => 'wp-feature-flag-item' ],
			] );

			$states = [
				'default' => 'Default' . ( $defaultValue !== null
						? ': ' . ( $defaultValue
							? 'On'
							: 'Off' )
						: '' ),
				'on'      => 'Force: <strong>On</strong>',
				'off'     => 'Force: <strong>Off</strong>',
			];

			foreach ( $states as $stateKey => $stateLabel ) {
				$icon    = $this->getStateIcon( $stateKey, $state );
				$stateId = $featureId . '_' . sanitize_key( $stateKey );

				$adminBar->add_node( [
					'parent' => $featureId,
					'id'     => $stateId,
					'title'  => $icon . ' ' . $stateLabel,
					'href'   => '#',
					'meta'   => [
						'class'   => 'wp-feature-flag-state' . ( $state === $stateKey
								? ' active'
								: '' ),
						// Very dirty hack.
						'onclick' => wp_json_encode( [
							'flagId'    => esc_attr( $id ),
							'flagState' => esc_attr( $stateKey ),
						] ),
					],
				] );
			}
		}

		$this->addAdminBarStyles();
		$this->addAdminBarScript();
	}

	private function getStateIcon( $stateKey, $currentState ) {
		if ( $stateKey === 'on' ) {
			return '<i class="dashicons dashicons-yes"></i>';
		}
		if ( $stateKey === 'off' ) {
			return '<i class="dashicons dashicons-no"></i>';
		}

		return '<i class="dashicons dashicons-minus"></i>';
	}

	private function getDefaultValue( $flag ) {
		if ( is_callable( $flag['default'] ) ) {
			return call_user_func( $flag['default'] );
		}
		if ( is_bool( $flag['default'] ) ) {
			return $flag['default'];
		}

		return null;
	}

	private function addAdminBarStyles() {
		echo '<style>
            #wp-admin-bar-wp-feature-flags ul a.ab-item {
            	display: flex;

	            .dashicons {
	                font: normal 20px/1 dashicons;
	                vertical-align: middle;
	                margin-right: 4px;

	                &:before {
	                	vertical-align: middle;
	                }
	                &.dashicons-yes {
	                    color: #46b450;
	                }
	                &.dashicons-no {
	                    color: #cc0000;
	                }
                }

                .feature-state {
			        justify-content: flex-end;
			        flex: auto;
			        display: flex;
			        margin-left: 10px;
                	font-size: 0.85em;
           		}
            }
        </style>';
	}

	private function addAdminBarScript() {
	}

	public function handleToggle() : void {
		if ( ! isset( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'wp_toggle_feature_flag' ) {
			return;
		}

		if ( ! isset( $_REQUEST['id'], $_REQUEST['state'], $_REQUEST['nonce'] )
			|| ! current_user_can( 'manage_options' )
			|| ! wp_verify_nonce( $_REQUEST['nonce'], 'wp_toggle_feature_flag' )
		) {
			wp_send_json_error( 'Invalid request' );

			return;
		}

		$id    = sanitize_text_field( $_REQUEST['id'] );
		$state = sanitize_text_field( $_REQUEST['state'] );

		if ( ! array_key_exists( $id, $this->featureFlags )
			|| ! in_array( $state, [
				'default',
				'on',
				'off',
			] ) ) {
			wp_send_json_error( 'Invalid feature flag or state' );

			return;
		}

		$this->setFeatureState( $id, $state );
		wp_send_json_success( [ 'id' => $id, 'state' => $state ] );
	}

	public function addFeatureFilters() : void {
		foreach ( $this->featureFlags as $id => $flag ) {
			$state = $this->getFeatureState( $id );

			if ( 'default' === $state ) {
				continue;
			}

			add_filter( $flag['filter'], static fn() => 'on' === $state, 99999 );
		}
	}

	private function getFeatureState( string $id ) : string {
		$states = get_option( self::OPTION_NAME, [] );

		return $states[ $id ] ?? 'default';
	}

	private function setFeatureState( string $id, string $state ) : void {
		$states = get_option( self::OPTION_NAME, [] );

		if ( 'default' === $state ) {
			unset( $states[ $id ] );
		} else {
			$states[ $id ] = $state;
		}

		update_option( self::OPTION_NAME, $states );
	}

	protected function getNextState( string $currentState ) : string {
		$states = [ 'default', 'on', 'off' ];

		$currentIndex = array_search( $currentState, $states );
		$nextIndex    = ( $currentIndex + 1 ) % count( $states );

		return $states[ $nextIndex ];
	}
}

function init() : FeatureFlags {
	static $Instance = null;

	if ( null === $Instance ) {
		$Instance = new FeatureFlags( getFeatureFlags() );
		add_action( 'wp_ajax_wp_toggle_feature_flag', [ $Instance, 'handleToggle' ] );
	}

	return $Instance;
}

init();
