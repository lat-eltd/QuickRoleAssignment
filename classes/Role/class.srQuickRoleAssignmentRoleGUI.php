<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/class.ilQuickRoleAssignmentPlugin.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/Role/class.srQuickRoleAssignmentRoleTableGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/class.srQuickRoleAssignmentModel.php");

/**
 * GUI-Class srQuickRoleAssignmentRoleGUI
 *
 * @author            Michael Herren <mh@studer-raimann.ch>
 * @version           1.0.0
 *
 * @ilCtrl_IsCalledBy srQuickRoleAssignmentRoleGUI: ilQuickRoleAssignmentGUI
 */
class srQuickRoleAssignmentRoleGUI {

	const CMD_DEFAULT = 'index';
	const CMD_RESET_FILTER = 'resetFilter';
	const CMD_APPLY_FILTER = 'applyFilter';
	const CMD_SAVE_ASSIGNMENT = 'assignRoles';
	/**
	 * @var  ilTable2GUI
	 */
	protected $table;
	protected $tpl;
	protected $ctrl;
	protected $pl;
	protected $toolbar;
	protected $tabs;
	protected $access;


	function __construct() {
		global $tpl, $ilCtrl, $ilAccess, $lng, $ilToolbar, $ilTabs;
		/**
		 * @var ilTemplate $tpl
		 * @var ilCtrl $ilCtrl
		 * @var ilAccessHandler $ilAccess
		 */
		$this->pl = ilQuickRoleAssignmentPlugin::getInstance();
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->toolbar = $ilToolbar;
		$this->tabs = $ilTabs;
		$this->access = $this->pl->getAccessManager();

		$this->tpl->setTitle($this->pl->txt('plugin_title'));
	}


	protected function checkAccessOrFail() {
		if ($this->access->hasCurrentUserViewPermission()) {
			return true;
		}

		throw new ilException("You have no permission to access this GUI!");
	}


	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();

		$this->checkAccessOrFail();

		$this->tpl->getStandardTemplate();
		//$this->tabs->addTab("course_gui", $this->pl->txt('title_search_course'), $this->ctrl->getLinkTarget($this));

		switch ($cmd) {
			case self::CMD_RESET_FILTER:
			case self::CMD_APPLY_FILTER:
			case self::CMD_SAVE_ASSIGNMENT:
				$this->$cmd();
				break;
			default:
				$this->index();
				break;
		}

		$content = $this->table->getHTML();

		$this->tpl->setContent($content);
	}


	public function index() {
		$this->table = new srQuickRoleAssignmentRoleTableGUI($this);
		$this->tpl->setContent($this->table->getHTML());
	}


	public function applyFilter() {
		$this->table = new srQuickRoleAssignmentRoleTableGUI($this, self::CMD_APPLY_FILTER);
		$this->table->writeFilterToSession();
		$this->table->resetOffset();
		$this->index();
	}


	public function resetFilter() {
		$this->table = new srQuickRoleAssignmentRoleTableGUI($this, self::CMD_RESET_FILTER);
		$this->table->resetOffset();
		$this->table->resetFilter();
		$this->index();
	}


	public function assignRoles() {
		global $rbacreview, $rbacadmin;

		if (!isset($_POST['id']) && !isset($_POST['user_id'])) {
			throw new ilException("Error in role assigning request!");
		}

		$current_roles = srQuickRoleAssignmentModel::getUserAssignments($_POST['user_id']);
		$allowed_roles = array_keys(srQuickRoleAssignmentModel::getAvailableRoles());

		$deassigned_roles = $current_roles;
		$role_changes = (is_array($_POST['id'])) ? $_POST['id'] : array();
		$changes_made = false;

		// add roles
		foreach ($role_changes as $user_id => $roles) {
			foreach ($roles as $role_id) {
				// only assign allowed roles!
				if (!in_array($role_id, $allowed_roles)) {
					throw new ilException("You try to set not an allowed role!");
				}

				// assign only new users
				if (!isset($current_roles[$user_id]) || !array_key_exists($role_id, $current_roles[$user_id])) {
					$rbacadmin->assignUser($role_id, $user_id);
					$changes_made = true;
					continue;
				} else {
					if (isset($current_roles[$user_id]) && array_key_exists($role_id, $current_roles[$user_id])) {
						// if assignment exists do not deassign
						unset($deassigned_roles[$user_id][$role_id]);
						if (count($deassigned_roles[$user_id]) == 0) {
							unset($deassigned_roles[$user_id]);
						}
					}
				}
			}
		}

		// remove roles
		foreach ($deassigned_roles as $user_id => $roles) {
			foreach ($roles as $role_id) {
				// only deassign allowed roles!
				if (in_array($role_id, $allowed_roles)) {
					$rbacadmin->deassignUser($role_id, $user_id);
					$changes_made = true;
				}
			}
		}

		if ($changes_made) {
			ilUtil::sendSuccess($this->pl->txt('message_roles_assigned'), true);
		} else {
			ilUtil::sendInfo($this->pl->txt('message_no_changes'), true);
		}

		$this->ctrl->redirect($this);
	}


	public function getRoleMultiCommands() {
		$cmds = array(
			'assignRoles' => $this->pl->txt('table_command_assign_roles'),
		);

		return $cmds;
	}


	public function cancel() {
		$this->ctrl->redirect($this);
	}
}

?>
