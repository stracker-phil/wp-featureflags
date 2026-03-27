<?php
/**
 * Sample quick-links override — copied to wp-content/wp-featureflags/links.php on first load.
 *
 * Edit that copy to add your own links. It persists across plugin updates.
 *
 * Return an array of link entries. If the array is empty, no admin-bar menu is shown.
 *
 * Supported formats:
 *
 *   // HR separator
 *   '---',
 *
 *   // Shorthand link: [ label, href ]
 *   [ 'Settings', '/wp-admin/options-general.php' ],
 *
 *   // Named link (equivalent to shorthand)
 *   [ 'label' => 'Settings', 'href' => '/wp-admin/options-general.php' ],
 *
 *   // Group heading with sub-links (any format works inside the group)
 *   'PayPal Plugin' => [
 *       [ 'Settings', '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=ppcp-gateway' ],
 *       '---',
 *       [ 'label' => 'Logs', 'href' => '/wp-admin/admin.php?page=wc-status&tab=logs' ],
 *   ],
 *
 * Full example:
 *
 *   return [
 *       'WooCommerce' => [
 *           [ 'Settings', '/wp-admin/admin.php?page=wc-settings' ],
 *           [ 'Status',   '/wp-admin/admin.php?page=wc-status' ],
 *       ],
 *       '---',
 *       [ 'Plugin Editor', '/wp-admin/plugin-editor.php' ],
 *   ];
 */

return array();
