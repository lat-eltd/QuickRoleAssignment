<?php
require_once __DIR__ . "/../vendor/autoload.php";
use srag\DIC\QuickRoleAssignment\DICTrait;

/**
 * Class ilQuickRoleAssignmentConfigGUI
 *
 * @author   Michael Herren <mh@studer-raimann.ch>
 * @version  1.0.0
 */
class ilQuickRoleAssignmentConfigGUI extends ilPluginConfigGUI {

    use DICTrait;

    const PLUGIN_CLASS_NAME = ilQuickRoleAssignmentPlugin::class;

	const CMD_DEFAULT = 'index';
	const CMD_SAVE = 'save';
	const CMD_CANCEL = 'cancel';

    /**
     * ilQuickRoleAssignmentConfigGUI constructor.
     */
    public function __construct() {

    }


    /**
     * @param $cmd
     */
    public function performCommand($cmd) {
		if ($cmd == 'configure') {
			$cmd = self::CMD_DEFAULT;
		}
		switch ($cmd) {
			case self::CMD_DEFAULT:
			case self::CMD_SAVE;
			case self::CMD_CANCEL;
				$this->{$cmd}();
				break;
		}
	}


    /**
     *
     */
    public function index() {
		$config_form_gui = $this->initForm();
		$config_form_gui->fillForm();

		self::dic()->ui()->mainTemplate()->setContent($config_form_gui->getHTML());
	}


    /**
     *
     */
    public function cancel() {
		self::dic()->ctrl()->redirect($this, self::CMD_DEFAULT);
	}


    /**
     * @return srQuickRoleAssignmentConfigFormGUI
     */
    protected function initForm() {
		return new srQuickRoleAssignmentConfigFormGUI($this);
	}


    /**
     *
     */
    protected function save() {
		$config_form_gui = $this->initForm();
		$config_form_gui->setValuesByPost();

		if ($config_form_gui->saveObject()) {
			ilUtil::sendSuccess(self::plugin()->translate("admin_form_saved_config"), true);
			self::dic()->ctrl()->redirect($this, self::CMD_DEFAULT);
		} else {
			ilUtil::sendFailure(self::plugin()->translate("admin_form_failed_config"));
		}

		self::dic()->ui()->mainTemplate()->setContent($config_form_gui->getHTML());
	}
}

?>