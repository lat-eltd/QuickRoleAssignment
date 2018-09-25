<?php
require_once __DIR__ . "/../vendor/autoload.php";
use srag\DIC\DICTrait;
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

    use DICTrait;

    const PLUGIN_CLASS_NAME = ilQuickRoleAssignmentPlugin::class;

	const RELOAD_LANGUAGES = false;

    /**
     * ilQuickRoleAssignmentGUI constructor.
     */
    public function __construct() {
		if (self::RELOAD_LANGUAGES OR $_GET['rl'] == 'true') {
			self::plugin()->getPluginObject()->updateLanguages();
		}
	}


    /**
     * @return bool
     * @throws ilCtrlException
     */
    public function executeCommand() {
		self::dic()->template()->getStandardTemplate();

		self::dic()->template()->addCss(self::plugin()->getPluginObject()->getStyleSheetLocation("default/quick_role_assignment.css"));

		$next_class = self::dic()->ctrl()->getNextClass($this);
		if (!$this->accessCheck($next_class)) {
			ilUtil::sendFailure(self::dic()->language()->txt("no_permission"), true);
			ilUtil::redirect("");

			return false;
		}

		switch ($next_class) {
			case '':
			case 'srquickroleassignmentrolegui':
				$gui = new srQuickRoleAssignmentRoleGUI();
				self::dic()->ctrl()->forwardCommand($gui);
				break;
			default:
				require_once(self::dic()->ctrl()->lookupClassPath($next_class));
				$gui = new $next_class();
				self::dic()->ctrl()->forwardCommand($gui);
				break;
		}
		self::dic()->template()->show();

		return true;
	}


    /**
     * @param $next_class
     * @return bool
     */
    protected function accessCheck($next_class) {
		switch ($next_class) {
			case '':
			case 'srquickroleassignmentrolegui':
				return self::plugin()->getPluginObject()->getAccessManager()->hasCurrentUserViewPermission();
				break;
		}

		return false;
	}
}

?>