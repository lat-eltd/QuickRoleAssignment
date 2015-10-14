<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/class.ilQuickRoleAssignmentPlugin.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/Role/class.srQuickRoleAssignmentRoleTableGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/class.srQuickRoleAssignmentModel.php");

/**
 * GUI-Class srQuickRoleAssignmentRoleGUI
 *
 * @author Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
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
		 * @var ilTemplate      $tpl
		 * @var ilCtrl          $ilCtrl
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
		if($this->access->hasCurrentUserViewPermission()) {
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
		//$this->toolbar->addButton($this->pl->txt('new_role'), $this->ctrl->getLinkTargetByClass("ilTrainingProgramRoleGUI", 'newRole'));
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

		if(!isset($_POST['id']) && !is_array($_POST['id'])) {
			throw new ilException("Error in role assigning request!");
		}

		/*$current_roles = srQuickRoleAssignmentModel::getUserAssignments(array_keys($_POST['id']));
		$allowed_roles = array_keys(srQuickRoleAssignmentModel::getAvailableRoles());

		$deassigned_roles = array();
		var_dump("----");
		var_dump($current_roles);
		foreach($_POST['id'] as $user_id=>$roles) {
			foreach($roles as $role_id=>$role_value) {
				if(!in_array($role_id, $allowed_roles)) {
					throw new ilException("You try to set not an allowed role!");
				}

				// assign only new users
				if(!isset($current_roles[$user_id]) || !array_key_exists($role_id, $current_roles[$user_id])) {
					$rbacadmin->assignUser($role_id, $user_id);
					var_dump("ASSIGN!");
					continue;
				} else if(isset($current_roles[$user_id]) && array_key_exists($role_id, $current_roles[$user_id])){
					$deassigned_roles[$user_id][$role_id] = true;
				}
			}
		}

		var_dump("deassigne roles:");
		var_dump($deassigned_roles);

		foreach($deassigned_roles as $user_id=>$roles) {
			foreach($roles as $role_id=>$role_value) {
				// deasign only new users
				$rbacadmin->deassignUser($role_id, $user_id);
			}
		}

		die();*/
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
