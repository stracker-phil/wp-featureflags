<?php
/**
 * Sample actions override — copied to wp-content/wp-featureflags/actions.php on first load.
 *
 * Edit that copy to add your own actions. It persists across plugin updates.
 * Use WP_FEATUREFLAGS_DIR to reference the base config shipped with the plugin.
 */

$my_actions = array();

return array_merge( (array) require WP_FEATUREFLAGS_DIR . '/actions.php', $my_actions );
