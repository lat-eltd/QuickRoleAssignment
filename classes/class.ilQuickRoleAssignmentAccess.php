<?php
require_once __DIR__ . "/../vendor/autoload.php";
/**
 * ilQuickRoleAssignmentAccess
 *
 * @author   Michael Herren <mh@studer-raimann.ch>
 * @version  1.0.0
 */
class ilQuickRoleAssignmentAccess {

	protected static $instance;


    /**
     * @return ilQuickRoleAssignmentAccess
     */
    public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


    /**
     * @return bool
     */
    public function hasCurrentUserViewPermission() {
		global $DIC;
		$required_role = srQuickRoleAssignmentConfig::getConfig(srQuickRoleAssignmentConfig::F_ADMIN_ROLES);
		return $DIC->rbac()->review()->isAssignedToAtLeastOneGivenRole($DIC->user()->getId(), $required_role);
	}
}
