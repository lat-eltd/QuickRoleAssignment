<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/class.ilQuickRoleAssignmentPlugin.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/Role/class.srQuickRoleAssignmentRoleGUI.php");

/**
 * GUI-Class ilQuickRoleAssignmentGUI
 *
 * @author            Michael Herren <mh@studer-raimann.ch>
 * @version           1.0.0
 *
 * @ilCtrl_IsCalledBy ilQuickRoleAssignmentGUI: ilRouterGUI, ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilQuickRoleAssignmentGUI: srQuickRoleAssignmentRoleGUI
 */
class ilQuickRoleAssignmentGUI {

	const RELOAD_LANGUAGES = false;
	protected $tpl;
	protected $ctrl;
	protected $tabs;
	protected $lng;
	protected $access;


	public function __construct() {
		global $tpl, $ilCtrl, $ilTabs, $lng;
		/**
		 * @var $tpl    ilTemplate
		 * @var $ilCtrl ilCtrl
		 * @var $ilTabs ilTabsGUI
		 */
		$this->tpl = $tpl;
		$this->pl = ilQuickRoleAssignmentPlugin::getInstance();
		$this->ctrl = $ilCtrl;
		$this->tabs = $ilTabs;
		$this->lng = $lng;
		$this->access = $this->pl->getAccessManager();
		if (self::RELOAD_LANGUAGES OR $_GET['rl'] == 'true') {
			$this->pl->updateLanguages();
		}
	}


	public function executeCommand() {
		$this->tpl->getStandardTemplate();

		$this->tpl->addCss($this->pl->getStyleSheetLocation("default/quick_role_assignment.css"));

		$next_class = $this->ctrl->getNextClass($this);
		if (!$this->accessCheck($next_class)) {
			ilUtil::sendFailure($this->lng->txt("no_permission"), true);
			ilUtil::redirect("");

			return false;
		}

		switch ($next_class) {
			case '':
			case 'srquickroleassignmentrolegui':
				$gui = new srQuickRoleAssignmentRoleGUI();
				$this->ctrl->forwardCommand($gui);
				break;
			default:
				require_once($this->ctrl->lookupClassPath($next_class));
				$gui = new $next_class();
				$this->ctrl->forwardCommand($gui);
				break;
		}
		$this->tpl->show();

		return true;
	}


	protected function accessCheck($next_class) {
		switch ($next_class) {
			case '':
			case 'srquickroleassignmentrolegui':
				return $this->access->hasCurrentUserViewPermission();
				break;
		}

		return false;
	}
}

?>