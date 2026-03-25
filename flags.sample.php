<?php
/**
 * Sample flags override — copied to wp-content/wp-featureflags/flags.php on first load.
 *
 * Edit that copy to add your own flags. It persists across plugin updates.
 * Use WP_FEATUREFLAGS_DIR to reference the base config shipped with the plugin.
 */

$my_flags = array();

return array_merge( (array) require WP_FEATUREFLAGS_DIR . '/flags.php', $my_flags );
