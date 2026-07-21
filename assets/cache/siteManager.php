<?php

/**
 * Legacy manager-directory fallback retained for compatibility with old Extras.
 *
 * @deprecated 3.5.8
 * @since 1.3.0
 *
 * Use MGR_DIR and EVO_MANAGER_PATH from core/includes/define.inc.php instead.
 *
 * @todo [remove@3.7] Remove in Evolution CMS 3.7.
 */
if (!defined('MGR_DIR')) {
    define('MGR_DIR', 'manager');
}