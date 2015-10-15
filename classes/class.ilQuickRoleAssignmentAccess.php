<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/Config/class.srQuickRoleAssignmentConfig.php");

/**
 * ilQuickRoleAssignmentAccess
 *
 * @author Michael Herren <mh@studer-raimann.ch>
 * @version  1.0.0
*/

class ilQuickRoleAssignmentAccess {
	protected static $instance;

	public static function getInstance() {
		if(is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function hasCurrentUserViewPermission() {
		global $ilUser, $rbacreview;

		$required_role = srQuickRoleAssignmentConfig::get(srQuickRoleAssignmentConfig::F_ADMIN_ROLES);

		return $rbacreview->isAssignedToAtLeastOneGivenRole($ilUser->getId(), $required_role);
	}
	
}
