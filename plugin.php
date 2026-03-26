<?php
/**
 * @formatter:off
 * Plugin Name: Feature-Flag Manager
 * Plugin URI:  https://github.com/stracker-phil/wp-featureflags
 * Description: Development utility that allows toggling feature flags and running quick actions via the WP admin bar
 * Author:      Philipp Stracker
 * Version:     1.2.0
 * @formatter:on
 */

namespace WpFeatureFlags;

use Exception;

defined( 'WP_FEATUREFLAGS_DIR' ) || define( 'WP_FEATUREFLAGS_DIR', __DIR__ );
defined( 'WP_FEATUREFLAGS_CONFIG_DIR' ) || define( 'WP_FEATUREFLAGS_CONFIG_DIR', WP_CONTENT_DIR . '/' . basename( __DIR__ ) );

// Ensure this plugin loads before all others so feature-flag filters are in place
// before any plugin evaluates them during plugins_loaded.
register_activation_hook( __FILE__, static function ( bool $network_wide = false ): void {
	$plugin = plugin_basename( __FILE__ );

	if ( $network_wide && is_multisite() ) {
		$plugins = get_site_option( 'active_sitewide_plugins', [] );

		if ( ! isset( $plugins[ $plugin ] ) ) {
			return;
		}

		// active_sitewide_plugins is keyed by basename; loading order follows key order.
		unset( $plugins[ $plugin ] );
		$plugins = [ $plugin => time() ] + $plugins;
		update_site_option( 'active_sitewide_plugins', $plugins );

		return;
	}

	$plugins = get_option( 'active_plugins', [] );
	$key     = array_search( $plugin, $plugins, true );

	// Already first plugin, or loaded as mu-plugin. No change needed.
	if ( false === $key || 0 === $key ) {
		return;
	}

	unset( $plugins[ $key ] );
	array_unshift( $plugins, $plugin );
	update_option( 'active_plugins', array_values( $plugins ) );
} );

class ConfigLoader {
	private string $id;
	private string $fileName;
	private array $searchPaths;
	private ?array $data = null;

	public function __construct( string $id, string $fileName, array $searchPaths = [] ) {
		$this->id          = $id;
		$this->fileName    = $fileName;
		$this->searchPaths = $searchPaths ?: [ __DIR__ ];
	}

	public function load(): array {
		if ( is_array( $this->data ) ) {
			return $this->data;

		}
		$this->data = [];

		foreach ( $this->searchPaths as $dir ) {
			$path = $dir . '/' . $this->fileName;
			if ( file_exists( $path ) ) {
				$this->data = (array) require $path;
				break;
			}
		}

		$this->data = apply_filters( "wp_feature_flags/load_config/{$this->id}", $this->data );

		return $this->data;
	}
}

abstract class AdminBarMenu {
	protected string $menuId;
	protected string $menuTitle;

	protected function addTopLevelMenu( $adminBar ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$adminBar->add_menu( [
			'id'    => $this->menuId,
			'title' => $this->menuTitle,
			'href'  => '#',
			'meta'  => [ 'title' => $this->menuTitle ],
		] );

		return true;
	}

	protected function isGroupHeading( array $item ): bool {
		return ! empty( $item['label'] )
			&& count( $item ) === 1;
	}

	protected function isDivider( array $item ): bool {
		return preg_match( '/^-+$/', $item['label'] );
	}

	protected function addGroupHeading( $adminBar, string $id, array $item ): void {
		$isDivider = $this->isDivider( $item );

		$adminBar->add_node( [
			'parent' => $this->menuId,
			'id'     => $this->menuId . '_heading_' . sanitize_key( $id ),
			'title'  => $isDivider ? '<hr>' : esc_html( $item['label'] ),
			'href'   => false,
			'meta'   => [ 'class' => $isDivider ? 'wp-feature-group-divider' : 'wp-feature-group-heading' ],
		] );
	}

	abstract public function addAdminBarItems( $adminBar ): void;
}

class FeatureFlags extends AdminBarMenu {
	private const OPTION_NAME = 'wp_feature_flags';

	private array $featureFlags = [];

	public function __construct( array $featureFlags ) {
		$this->menuId       = 'wp-feature-flags';
		$this->menuTitle    = 'Feature Flags';
		$this->featureFlags = $this->sanitizeFeatureFlags( $featureFlags );

		if ( ! $this->featureFlags ) {
			return;
		}

		add_action( 'admin_bar_menu', [ $this, 'addAdminBarItems' ], 100 );
		add_action( 'init', [ $this, 'handleToggle' ] );
		add_action( 'plugins_loaded', [ $this, 'addFeatureFilters' ] );
	}

	protected function sanitizeFeatureFlags( array $featureFlags ): array {
		$validFlags = [];

		foreach ( $featureFlags as $id => $flag ) {
			if ( is_string( $flag ) ) {
				$flag = [ 'label' => $flag ];
			}

			if ( empty( $flag ) || ! is_array( $flag ) || empty( $flag['label'] ) ) {
				continue;
			}

			// Group heading: label-only item.
			if ( count( $flag ) === 1 ) {
				$validFlags[ $id ] = $flag;
				continue;
			}

			if (
				empty( $flag['filter'] )
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

	public function addAdminBarItems( $adminBar ): void {
		if ( ! $this->addTopLevelMenu( $adminBar ) ) {
			return;
		}

		foreach ( $this->featureFlags as $id => $flag ) {
			if ( $this->isGroupHeading( $flag ) ) {
				$this->addGroupHeading( $adminBar, $id, $flag );
				continue;
			}

			$featureId    = $this->menuId . '_' . sanitize_key( $id );
			$state        = $this->getFeatureState( $id );
			$defaultValue = $this->getDefaultValue( $flag );

			$adminBar->add_node( [
				'parent' => $this->menuId,
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
		?>
		<style>
			#wp-admin-bar-wp-feature-flags .wp-feature-group-heading > .ab-item {
				opacity: 0.6;
				text-transform: uppercase;
				font-size: 11px !important;
				pointer-events: none;
				cursor: default;
			}

			#wp-admin-bar-wp-feature-flags .wp-feature-group-divider > .ab-item {
				pointer-events: none;
				cursor: default;
				height: 0 !important;
				padding: 0 !important;
			}

			#wp-admin-bar-wp-feature-flags .wp-feature-group-divider hr {
				margin: 4px 8px;
				border: none;
				border-top: 1px solid rgba(255, 255, 255, 0.2);
			}

			#wp-admin-bar-wp-feature-flags .wp-feature-flag-item > .ab-item {
				font-weight: bold;
			}

			#wp-admin-bar-wp-feature-flags .wp-feature-flag-state {
				padding-left: 10px !important;
			}

			#wp-admin-bar-wp-feature-flags .wp-feature-flag-item[data-current-state="default"],
			#wp-admin-bar-wp-feature-flags .wp-feature-flag-state[data-flag-state="default"] {
				--feature-accent: #fff;
			}

			#wp-admin-bar-wp-feature-flags .wp-feature-flag-item[data-current-state="on"],
			#wp-admin-bar-wp-feature-flags .wp-feature-flag-state[data-flag-state="on"] {
				--feature-accent: #46b450;
			}

			#wp-admin-bar-wp-feature-flags .wp-feature-flag-item[data-current-state="off"],
			#wp-admin-bar-wp-feature-flags .wp-feature-flag-state[data-flag-state="off"] {
				--feature-accent: #cc0000;
			}

			#wp-admin-bar-wp-feature-flags .wp-feature-flag-item > a .dashicons,
			#wp-admin-bar-wp-feature-flags .wp-feature-flag-state.active a .dashicons {
				color: var(--feature-accent, #fff);
			}

			#wpadminbar #wp-admin-bar-wp-feature-flags .wp-feature-flag-state.active a strong {
				text-decoration: underline;
			}

			#wp-admin-bar-wp-feature-flags .dashicons {
				font: normal 20px/1 dashicons;
				vertical-align: middle;
				margin-right: 4px;
			}
		</style>
		<?php
	}

	private function addAdminBarScript() {
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const featureItems = document.querySelectorAll('.wp-feature-flag-item');

				function initFeature(featureParent) {
					const stateItems = featureParent.querySelectorAll('.wp-feature-flag-state');
					stateItems.forEach(initFeatureChild);

					setFeatureState(featureParent);
				}

				function initFeatureChild(child) {
					const link = child.querySelector('a[onclick]');
					const data = JSON.parse(link.getAttribute('onclick'));
					child.dataset.flagId = data.flagId;
					child.dataset.flagState = data.flagState;
					link.removeAttribute('onclick');

					child.addEventListener('click', function(event) {
						event.preventDefault();
						wpToggleFeatureFlag(child);
					});
				}

				featureItems.forEach(initFeature);
			});

			function wpToggleFeatureFlag(item) {
				const id = item.dataset.flagId;
				const state = item.dataset.flagState;

				const xhr = new XMLHttpRequest();
				xhr.open('POST', '<?php echo admin_url( 'admin-ajax.php' ); ?>', true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

				xhr.onload = function() {
					if (xhr.status === 200) {
						const response = JSON.parse(xhr.responseText);
						if (response.success) {
							updateAdminBarItem(item, state);
						}
					}
				};
				xhr.send('action=wp_toggle_feature_flag&id=' + encodeURIComponent(id) + '&state=' + encodeURIComponent(
					state) + '&nonce=<?php echo wp_create_nonce( 'wp_toggle_feature_flag' ); ?>');
			}

			function updateAdminBarItem(item, newState) {
				const featureParent = item.closest('.wp-feature-flag-item');
				const stateItems = featureParent.querySelectorAll('.wp-feature-flag-state');

				stateItems.forEach(function(child) {
					if (child.dataset.flagState === newState) {
						child.classList.add('active');
					} else {
						child.classList.remove('active');
					}
				});

				setFeatureState(featureParent, newState);
			}

			function setFeatureState(featureParent) {
				const stateItems = featureParent.querySelectorAll('.wp-feature-flag-state');
				const activeItem = Array.from(stateItems)
					.find((child) => child.classList.contains('active'));

				featureParent.dataset.currentState = activeItem?.dataset.flagState ?? 'default';

				// Update the parent link's dashicon
				const parentLink = featureParent.querySelector('.ab-item');
				const activeDashicon = activeItem ? activeItem.querySelector('.dashicons') : null;

				if (parentLink && activeDashicon) {
					const existingIcon = parentLink.querySelector('.dashicons');
					if (existingIcon) {
						existingIcon.remove();
					}

					if (activeDashicon) {
						const newIcon = activeDashicon.cloneNode(true);
						parentLink.insertBefore(newIcon, parentLink.firstChild);
					}
				}
			}
		</script>
		<?php
	}

	public function handleToggle(): void {
		$valid_states = [ 'default', 'on', 'off' ];

		if ( ! isset( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'wp_toggle_feature_flag' ) {
			return;
		}

		if (
			! isset( $_REQUEST['id'], $_REQUEST['state'], $_REQUEST['nonce'] )
			|| ! current_user_can( 'manage_options' )
			|| ! wp_verify_nonce( $_REQUEST['nonce'], 'wp_toggle_feature_flag' )
		) {
			wp_send_json_error( 'Invalid request' );

			return;
		}

		$id    = sanitize_text_field( $_REQUEST['id'] );
		$state = sanitize_text_field( $_REQUEST['state'] );

		if (
			! array_key_exists( $id, $this->featureFlags )
			|| ! in_array( $state, $valid_states, true )
		) {
			wp_send_json_error( 'Invalid feature flag or state' );

			return;
		}

		$this->setFeatureState( $id, $state );
		wp_send_json_success( [ 'id' => $id, 'state' => $state ] );
	}

	public function addFeatureFilters(): void {
		foreach ( $this->featureFlags as $id => $flag ) {
			$state = $this->getFeatureState( $id );

			if ( 'default' === $state ) {
				continue;
			}

			add_filter( $flag['filter'], static fn() => 'on' === $state, 99999 );
		}
	}

	private function getFeatureState( string $id ): string {
		$states = get_option( self::OPTION_NAME, [] );

		return $states[ $id ] ?? 'default';
	}

	private function setFeatureState( string $id, string $state ): void {
		$states = get_option( self::OPTION_NAME, [] );

		if ( 'default' === $state ) {
			unset( $states[ $id ] );
		} else {
			$states[ $id ] = $state;
		}

		update_option( self::OPTION_NAME, $states );

		do_action( "wp_feature_flags/updated/$id", $state );
		do_action( 'wp_feature_flags/updated', $id, $state );
	}
}

class FeatureActions extends AdminBarMenu {
	private const NONCE_ACTION = 'wp_feature_action';

	private array $actions;

	public function __construct( array $actions ) {
		$this->menuId    = 'wp-feature-actions';
		$this->menuTitle = 'Feature Actions';
		$this->actions   = $this->sanitizeActions( $actions );

		if ( ! $this->actions ) {
			return;
		}

		add_action( 'admin_bar_menu', [ $this, 'addAdminBarItems' ], 101 );
	}

	private function sanitizeActions( array $actions ): array {
		$valid = [];

		foreach ( $actions as $id => $action ) {
			if ( is_string( $action ) ) {
				$action = [ 'label' => $action ];
			}

			if ( empty( $action ) || ! is_array( $action ) || empty( $action['label'] ) ) {
				continue;
			}

			// Group heading: label-only item.
			if ( count( $action ) === 1 ) {
				$valid[ $id ] = $action;
				continue;
			}

			if ( empty( $action['changes'] ) || ! is_array( $action['changes'] ) ) {
				continue;
			}

			$validChanges = [];
			foreach ( $action['changes'] as $change ) {
				if ( ! is_array( $change ) || empty( $change[0] ) ) {
					continue;
				}

				$type = $change[0];
				if ( 'set_option' === $type && isset( $change[1], $change[2] ) ) {
					$validChanges[] = $change;
				} elseif ( 'set_option_key' === $type && isset( $change[1], $change[2], $change[3] ) ) {
					$validChanges[] = $change;
				} elseif ( 'delete_option' === $type && isset( $change[1] ) ) {
					$validChanges[] = $change;
				} elseif ( 'do_action' === $type && isset( $change[1] ) ) {
					$validChanges[] = $change;
				}
			}

			if ( ! $validChanges ) {
				continue;
			}

			$action['changes'] = $validChanges;
			$valid[ $id ]      = $action;
		}

		return $valid;
	}

	public function addAdminBarItems( $adminBar ): void {
		if ( ! $this->addTopLevelMenu( $adminBar ) ) {
			return;
		}

		foreach ( $this->actions as $id => $action ) {
			if ( $this->isGroupHeading( $action ) ) {
				$this->addGroupHeading( $adminBar, $id, $action );
				continue;
			}

			$nodeId = $this->menuId . '_' . sanitize_key( $id );

			$adminBar->add_node( [
				'parent' => $this->menuId,
				'id'     => $nodeId,
				'title'  => esc_html( $action['label'] ),
				'href'   => '#',
				'meta'   => [
					'class'   => 'wp-feature-action-item',
					'onclick' => wp_json_encode( [
						'actionId' => esc_attr( $id ),
					] ),
				],
			] );
		}

		$this->addAdminBarStyles();
		$this->addAdminBarScript();
	}

	private function addAdminBarStyles() {
		?>
		<style>
			#wp-admin-bar-wp-feature-actions .wp-feature-group-heading > .ab-item {
				opacity: 0.6;
				text-transform: uppercase;
				font-size: 11px !important;
				pointer-events: none;
				cursor: default;
			}

			#wp-admin-bar-wp-feature-actions .wp-feature-group-divider > .ab-item {
				pointer-events: none;
				cursor: default;
				height: 0 !important;
				padding: 0 !important;
			}

			#wp-admin-bar-wp-feature-actions .wp-feature-group-divider hr {
				margin: 4px 8px;
				border: none;
				border-top: 1px solid rgba(255, 255, 255, 0.2);
			}

			#wp-admin-bar-wp-feature-actions .wp-feature-action-item > .ab-item {
				cursor: pointer;
			}

			#wp-admin-bar-wp-feature-actions .wp-feature-action-item.running > .ab-item {
				opacity: 0.5;
				pointer-events: none;
			}

			#wp-admin-bar-wp-feature-actions .wp-feature-action-item.done > .ab-item {
				color: #46b450 !important;
			}

			#wp-admin-bar-wp-feature-actions .wp-feature-action-item.error > .ab-item {
				color: #cc0000 !important;
			}
		</style>
		<?php
	}

	private function addAdminBarScript() {
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const actionItems = document.querySelectorAll('.wp-feature-action-item');

				actionItems.forEach(function(item) {
					const link = item.querySelector('a[onclick]');
					const data = JSON.parse(link.getAttribute('onclick'));
					item.dataset.actionId = data.actionId;
					link.removeAttribute('onclick');

					item.addEventListener('click', function(event) {
						event.preventDefault();
						wpRunFeatureAction(item);
					});
				});
			});

			function wpRunFeatureAction(item) {
				if (item.classList.contains('running')) {
					return;
				}

				const id = item.dataset.actionId;

				item.classList.remove('done', 'error');
				item.classList.add('running');

				const xhr = new XMLHttpRequest();
				xhr.open('POST', '<?php echo admin_url( 'admin-ajax.php' ); ?>', true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

				xhr.onload = function() {
					item.classList.remove('running');
					if (xhr.status === 200) {
						const response = JSON.parse(xhr.responseText);
						if (response.success) {
							item.classList.add('done');
							window.location.reload();
						} else {
							item.classList.add('error');
						}
					} else {
						item.classList.add('error');
					}
				};

				xhr.onerror = function() {
					item.classList.remove('running');
					item.classList.add('error');
				};

				xhr.send([
					'action=wp_run_feature_action',
					'id=' + encodeURIComponent(id),
					'nonce=<?php echo wp_create_nonce( self::NONCE_ACTION ); ?>',
				].join('&'));
			}
		</script>
		<?php
	}

	public function handleAction(): void {
		if (
			! isset( $_REQUEST['id'], $_REQUEST['nonce'] )
			|| ! current_user_can( 'manage_options' )
			|| ! wp_verify_nonce( $_REQUEST['nonce'], self::NONCE_ACTION )
		) {
			wp_send_json_error( 'Invalid request' );

			return;
		}

		$id = sanitize_text_field( $_REQUEST['id'] );

		if ( ! array_key_exists( $id, $this->actions ) ) {
			wp_send_json_error( 'Unknown action' );

			return;
		}

		$action = $this->actions[ $id ];

		do_action( "wp_feature_flags/before_action/$id" );
		do_action( 'wp_feature_flags/before_action', $id );

		foreach ( $action['changes'] as $change ) {
			$type = $change[0];

			if ( 'set_option' === $type ) {
				update_option( $change[1], $change[2] );
			} elseif ( 'set_option_key' === $type ) {
				$option               = get_option( $change[1], [] );
				$option[ $change[2] ] = $change[3];
				update_option( $change[1], $option );
			} elseif ( 'delete_option' === $type ) {
				delete_option( $change[1] );
			} elseif ( 'do_action' === $type ) {
				try {
					do_action( ...array_slice( $change, 1 ) );
				} catch ( Exception $e ) {
					error_log( "Action failed: $change[0]", $e->getMessage() );
				}
			}

			wp_cache_delete( 'alloptions', 'options' );
		}

		do_action( "wp_feature_flags/action/$id" );
		do_action( 'wp_feature_flags/action', $id );

		wp_send_json_success( [ 'id' => $id ] );
	}
}

add_action( 'plugins_loaded', static function (): void {
	static $initialized = false;

	if ( $initialized ) {
		return;
	}
	$initialized = true;

	// Bootstrap external config dir with sample files on first load.
	if ( ! is_dir( WP_FEATUREFLAGS_CONFIG_DIR ) ) {
		wp_mkdir_p( WP_FEATUREFLAGS_CONFIG_DIR );
	}
	foreach ( [ 'flags', 'actions', 'snippets' ] as $name ) {
		$target = WP_FEATUREFLAGS_CONFIG_DIR . '/' . $name . '.php';
		$source = WP_FEATUREFLAGS_DIR . '/' . $name . '.sample.php';

		if ( ! file_exists( $target ) && file_exists( $source ) ) {
			copy( $source, $target );
		}
	}

	$searchPaths = [ WP_FEATUREFLAGS_CONFIG_DIR, WP_FEATUREFLAGS_DIR ];

	$flagsLoader   = new ConfigLoader( 'flags', 'flags.php', $searchPaths );
	$actionsLoader = new ConfigLoader( 'actions', 'actions.php', $searchPaths );

	$featureFlags = new FeatureFlags( $flagsLoader->load() );
	add_action( 'wp_ajax_wp_toggle_feature_flag', [ $featureFlags, 'handleToggle' ] );

	$featureActions = new FeatureActions( $actionsLoader->load() );
	add_action( 'wp_ajax_wp_run_feature_action', [ $featureActions, 'handleAction' ] );

	$snippet_file = WP_FEATUREFLAGS_CONFIG_DIR . '/snippets.php';
	if ( file_exists( $snippet_file ) ) {
		require_once $snippet_file;
	}

	$clean_up = static function () {
		global $wpdb;

		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '\_transient\_%'
			;"
		);

		wp_cache_flush();
	};

	add_action( 'wp_feature_flags/updated', $clean_up );
	add_action( 'wp_feature_flags/action', $clean_up );
}, - 100 );
