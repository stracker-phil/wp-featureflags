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
		add_action( 'init', [ $this, 'processSetFeatureState' ] );
		
		$this->addFeatureFilters();
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

		$adminBar->add_menu( [
			'id'    => 'wp-feature-flags',
			'title' => 'Feature Flags',
			'href'  => '#',
			'meta'  => [ 'title' => 'Feature Flag Manager' ],
		] );

		foreach ( $this->featureFlags as $id => $flag ) {
			$state = $this->getFeatureState( $id );

			if ( 'default' === $state ) {
				$icon         = 'minus';
				$defaultValue = null;
				$stateLabel   = 'Default';

				if ( is_callable( $flag['default'] ) ) {
					$defaultValue = call_user_func( $flag['default'] );
				} elseif ( is_bool( $flag['default'] ) ) {
					$defaultValue = $flag['default'];
				}
				if ( null !== $defaultValue ) {
					$stateLabel .= $defaultValue ? ': On' : ': Off';
				}
			} else {
				$icon       = 'on' === $state ? 'yes' : 'no';
				$stateLabel = 'on' === $state ? 'On' : 'Off';
			}

			$title = sprintf(
				'<i class="dashicons dashicons-%s"></i> %s%s',
				esc_attr( $icon ),
				esc_html( $flag['label'] ),
				'<span class="feature-state">' . esc_html( $stateLabel ) . '</span>'
			);

			$adminBar->add_node( [
				'parent' => 'wp-feature-flags',
				'id'     => 'wp-flag-' . sanitize_key( $id ),
				'title'  => $title,
				'href'   => add_query_arg( [
					'wp_toggle_flag' => urlencode( $id ),
					'wp_nonce'       => wp_create_nonce( 'wp_toggle_flag' ),
				] ),
				'meta'   => [ 'class' => 'wp-feature-flag-item' ],
			] );
		}

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

	public function processSetFeatureState() : void {
		if ( ! isset( $_GET['wp_toggle_flag'], $_GET['wp_nonce'] ) ||
			! current_user_can( 'manage_options' ) ||
			! wp_verify_nonce( $_GET['wp_nonce'], 'wp_toggle_flag' )
		) {
			return;
		}

		$id = sanitize_text_field( urldecode( $_GET['wp_toggle_flag'] ) );
		if ( ! array_key_exists( $id, $this->featureFlags ) ) {
			wp_die( 'Invalid feature flag' );
		}

		$this->setFeatureState(
			$id,
			$this->getNextState( $this->getFeatureState( $id ) )
		);

		wp_safe_redirect( remove_query_arg( [ 'wp_toggle_flag', 'wp_nonce' ] ) );
		exit;
	}

	protected function addFeatureFilters() : void {
		foreach ( $this->featureFlags as $id => $flag ) {
			$state = $this->getFeatureState( $id );

			if ( 'default' === $state ) {
				continue;
			}

			add_filter( $flag['filter'], fn() => 'on' === $state, 99999 );
		}
	}

	protected function getFeatureState( string $id ) : string {
		$states = get_option( self::OPTION_NAME, [] );

		return $states[ $id ] ?? 'default';
	}

	protected function setFeatureState( string $id, string $state ) : void {
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
	}

	return $Instance;
}

init();
