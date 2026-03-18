<?php
/**
 * Your personal feature flags. Review the "flags.php" for the syntax.
 */

$local_flags = array();

return array_merge( (array) require 'flags.php', $local_flags );
