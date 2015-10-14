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

	/*public function getDevisionIdsOfCurrentUserWithSkillManagementPermission() {
		global $ilUser, $rbacreview;
		$arr_dev_id = array();

		$arr_global_role_id = $rbacreview->assignedGlobalRoles($ilUser->getId());
		foreach($arr_global_role_id as $role_id) {
			//Prüfen ob Role ID und direkt zuweisen zu $role_dev_id
			if($role_dev_id = emDevision::getDevisionIdBySkillManagerRoleId($role_id)) {
				$arr_dev_id[] = $role_dev_id;
			}
		}
		return $arr_dev_id;
	}

	public function hasCurrentUserSkillManagementPermission() {

        //Reporting Access to Employees?
        if(count(self::getDevisionIdsOfCurrentUserWithSkillManagementPermission()) > 0) {
            return true;
        }

		return false;
	}

	public function isCourseRearrangementInProgress() {
		return (ilTrainingProgramConfig::get(ilTrainingProgramConfig::F_CHANGE_LOCK) == 1);
	}

	public function hasCurrentUserEditUserProgramPermission() {
		global $rbacreview, $ilUser;

		$ilEducationManagementAccess = new ilEducationManagementAccess();
		//Vorgesetzte
		if($ilEducationManagementAccess->isCurrentUserSuperior()) {
			return true;
		}
		//Lokale Kursadmininstratoren
		if($ilEducationManagementAccess->hasCurrentUserLocalCrsAdminPermission()) {
			return true;
		}
		//Zertitikatsadministratoren
		$ilCourseCertificateAccess = new ilCourseCertificateAccess();
		if($ilCourseCertificateAccess->hasCurrentUserAdministratePermission()) {
			return true;
		}
		//Supperadministratoren
		$global_roles = $rbacreview->assignedGlobalRoles($ilUser->getId());
		if(in_array(2,$global_roles)) {
			return true;
		}

		return false;
	}*/
}
