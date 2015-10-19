<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Form/classes/class.ilMultiSelectInputGUI.php');
require_once('./Services/Form/classes/class.ilRoleAutoCompleteInputGUI.php');
require_once('class.srQuickRoleAssignmentConfig.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/class.srQuickRoleAssignmentModel.php');


/**
 * Class srQuickRoleAssignmentConfigFormGUI
 *
 * @author Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srQuickRoleAssignmentConfigFormGUI extends ilPropertyFormGUI {

    /**
     * @var
     */
    protected $parent_gui;
    /**
     * @var  ilCtrl
     */
    protected $ctrl;

    /**
     * @param  $parent_gui
     */
    public function __construct($parent_gui) {
        global $ilCtrl;
        $this->parent_gui = $parent_gui;
        $this->ctrl = $ilCtrl;
        $this->pl = ilQuickRoleAssignmentPlugin::getInstance();
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->initForm();
    }


    /**
     * @param $field
     *
     * @return string
     */
    public function txt($field) {
        return $this->pl->txt('admin_form_' . $field);
    }


    protected function initForm() {
        global $rbacreview, $ilUser;

        $this->setTitle($this->txt('title'));

        $se = new ilMultiSelectInputGUI($this->txt('config_allowed_change_roles'), srQuickRoleAssignmentConfig::F_ADMIN_ROLES);
        $se->setWidth(400);
        $se->setOptions(srQuickRoleAssignmentModel::getRoleNames(true, false));
        $this->addItem($se);

        $se = new ilMultiSelectInputGUI($this->txt('config_assignable_roles'), srQuickRoleAssignmentConfig::F_ASSIGNABLE_ROLES);
        $se->setWidth(400);
        $se->setOptions(srQuickRoleAssignmentModel::getRoleNames());
        $this->addItem($se);

	    $this->addCommandButtons();
    }


    public function fillForm() {
        $array = array();
        foreach ($this->getItems() as $item) {
            $this->getValuesForItem($item, $array);
        }
        $this->setValuesByArray($array);
    }

    /**
     * @param ilFormPropertyGUI $item
     * @param                   $array
     *
     * @internal param $key
     */
    private function getValuesForItem($item, &$array) {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            $array[$key] = srQuickRoleAssignmentConfig::get($key);
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->getValuesForItem($subitem, $array);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function saveObject() {
        if (!$this->checkInput()) {
            return false;
        }
        foreach ($this->getItems() as $item) {
            $this->saveValueForItem($item);
        }

        return true;
    }


    /**
     * @param  ilFormPropertyGUI $item
     */
    private function saveValueForItem($item) {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();

            srQuickRoleAssignmentConfig::set($key, $this->getInput($key));

            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->saveValueForItem($subitem);
                }
            }
        }
    }


    /**
     * @param $item
     *
     * @return bool
     */
    public static function checkForSubItem($item) {
        return !$item instanceof ilFormSectionHeaderGUI AND !$item instanceof ilMultiSelectInputGUI;
    }


    /**
     * @param $item
     *
     * @return bool
     */
    public static function checkItem($item) {
        return !$item instanceof ilFormSectionHeaderGUI;
    }


    protected function addCommandButtons() {
        $this->addCommandButton('save', $this->pl->txt('admin_form_button_save'));
        $this->addCommandButton('cancel', $this->pl->txt('admin_form_button_cancel'));
    }
}