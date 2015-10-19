<?php
require_once('./Services/Table/classes/class.ilTable2GUI.php');

require_once('./Services/Form/classes/class.ilTextInputGUI.php');
require_once('./Services/Form/classes/class.ilSelectInputGUI.php');
require_once('./Services/Form/classes/class.ilDateTimeInputGUI.php');
require_once("./Services/Form/classes/class.ilCombinationInputGUI.php");

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/class.ilQuickRoleAssignmentPlugin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/QuickRoleAssignment/classes/Role/class.srQuickRoleAssignmentRoleTableGUI.php');

/**
 * Class srQuickRoleAssignmentRoleTableGUI
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srQuickRoleAssignmentRoleTableGUI extends ilTable2GUI {

	/**
	 * @var ilCtrl $ctrl
	 */
	protected $ctrl;
	/** @var  array $filter */
	protected $filter = array();
	protected $access;

	protected $ignored_cols;

	/** @var bool  */
	protected  $show_default_filter = false;

	/** @var array  */
	protected  $numeric_fields = array("course_id");


	/**
	 * @param srQuickRoleAssignmentRoleGUI  $parent_obj
	 * @param string                        $parent_cmd
	 */
	public function __construct($parent_obj, $parent_cmd = "index") {
		/** @var $ilCtrl ilCtrl */
		/** @var ilToolbarGUI $ilToolbar */
		global $ilCtrl, $ilToolbar;

		$this->ctrl = $ilCtrl;
		$this->pl = ilQuickRoleAssignmentPlugin::getInstance();
		$this->access = $this->pl->getAccessManager();
		$this->toolbar = $ilToolbar;

		if(!$ilCtrl->getCmd()) {
			$this->setShowDefaultFilter(true);
		}

		$this->setPrefix('sr_xqra_role_');
		$this->setId('xqra_courses');

		parent::__construct($parent_obj, $parent_cmd, '');

		$this->setFormName('sr_xqra_role');
		$this->setRowTemplate('tpl.default_row.html', $this->pl->getDirectory());
		$this->setFormAction($this->ctrl->getFormAction($parent_obj));
		//$this->setDefaultOrderField('Datetime');
		$this->setDefaultOrderDirection('desc');
		$this->setShowRowsSelector(false);

		$this->setEnableTitle(true);
		$this->setEnableHeader(false);
		$this->setDisableFilterHiding(true);
		$this->setEnableNumInfo(true);

		$this->setIgnoredCols(array(''));
		$this->setTitle($this->pl->txt('title_search_user'));

		$this->setSelectAllCheckbox("id[]");
		$this->setTopCommands(true);
		$this->setEnableAllCommand(false);
		$this->setSelectAllCheckbox('');


		$cmds = $parent_obj->getRoleMultiCommands();
		foreach($cmds as $cmd => $caption)
		{
			$this->addMultiCommand($cmd, $caption);
		}
		$this->addCommandButton('cancel', $this->pl->txt('table_command_cancel'));

		$this->initFilter();
		$this->addColumns();
        if (!in_array($parent_cmd, array('applyFilter', 'resetFilter'))) {
            $this->parseData();
        }
	}


	protected function parseData() {
		global $ilUser;
		$this->setExternalSorting(true);
		$this->setExternalSegmentation(true);
		$this->setDefaultOrderField($this->columns[0]);

		$this->determineLimit();
		$this->determineOffsetAndOrder();

		$options = array(
			'filters' => $this->filter,
			'limit' => array(),
			'count' => true,
			'sort' => array( 'field' => $this->getOrderField(), 'direction' => $this->getOrderDirection() ),
		);
		$count = srQuickRoleAssignmentModel::getUsers($options);

		$options['limit'] = array( 'start' => (int)$this->getOffset(), 'end' => (int)$this->getLimit() );
		$options['count'] = false;
		$data = srQuickRoleAssignmentModel::getUsers($options);

		$user_assignments = srQuickRoleAssignmentModel::getUserAssignments(array_keys($data));
		$available_roles = srQuickRoleAssignmentModel::getAvailableRoles(true);

		$this->setMaxCount($count);

		$rows = array();
		/** @var $roleRecord */
		foreach ($data as $roleRecord) {
			$row = array();
			// data-parsing
			$row = $roleRecord;
			$row['roles'] = $available_roles;
			$row['role_assignments'] = $user_assignments[$roleRecord['usr_id']];

			$rows[] = $row;
		}
		$this->setData($rows);
	}

	public function initFilter() {
		// Login
		$item = new ilTextInputGUI($this->pl->txt('filter_label_login'), 'login');
		$this->addFilterItem($item);
		$item->readFromSession();
		$this->filter['login'] = $item->getValue();
	}

	public function getTableColumns() {
		$cols = array();

		$cols['id'] = array( 'txt' => '', 'default' => true, 'width' => '5');
		$cols['role'] = array( 'txt' => null, 'default' => true, 'width' => 'auto');

		return $cols;
	}


	/**
	 * @return array
	 */
	public function getSelectableColumns() {
		return array();
	}


	private function addColumns() {
		foreach ($this->getTableColumns() as $k => $v) {
			if (isset($v['sort_field'])) {
				$sort = $v['sort_field'];
			} else {
				$sort = NULL;
			}
			$this->addColumn($v['txt'], $sort, $v['width']);
		}
	}

	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		$this->tpl->setVariable('USER', sprintf($this->pl->txt('user_status_line'), $a_set['login'], $a_set['firstname'], $a_set['lastname']));

		$this->tpl->setCurrentBlock('user_tr');
		$this->tpl->setVariable('CSS_CLASS', 'status_line');
		$this->tpl->setVariable('ROLE', $this->pl->txt('table_label_role'));
		$this->tpl->parseCurrentBlock();

		if(count($a_set['roles']) > 0) {
			$odd = true;
			foreach ($a_set['roles'] as $key => $role) {
				$this->tpl->setCurrentBlock('user_tr');

				$css_class = "status_list_entry ";
				$css_class .= ($odd) ? "odd " : "even ";
				$this->tpl->setVariable('CSS_CLASS', $css_class);

				$this->tpl->setVariable('ROLE', $role['title'].'<div class="role_description">'.$role['description'].'</div>');

				$user_assigned = isset($a_set['role_assignments'][$role['obj_id']]);

				$hidden_user_input = '<input type="hidden" name="user_id[]" value="'.htmlspecialchars($a_set['usr_id']).'" />';

				$check_box = '<input type="checkbox" name="id['.htmlspecialchars($a_set['usr_id']).'][]" value="'.htmlspecialchars($role['obj_id']).'" ';
				$check_box .= ($user_assigned)? 'checked="checked" ' : '';
				$check_box .= ' />';

				$this->tpl->setVariable('FORM_DATA', $check_box.$hidden_user_input);

				$this->tpl->parseCurrentBlock();
				$odd = !$odd;
			}
		} else {
			$this->tpl->setCurrentBlock('user_tr');
			$css_class = "status_list_entry ";
			$this->tpl->setVariable('CSS_CLASS', $css_class);
			$this->tpl->setVariable('ROLE', $this->pl->txt('no_roles_available'));
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param array $formats
	 */
	public function setExportFormats(array $formats) {

		parent::setExportFormats($formats);

		$custom_fields = array_diff($formats, $this->export_formats);

		foreach ($custom_fields as $format_key) {
			if (isset($this->custom_export_formats[$format_key])) {
				$this->export_formats[$format_key] = $this->pl->getPrefix() . "_" . $this->custom_export_formats[$format_key];
			}
		}
	}


	public function exportData($format, $send = false) {
		if (array_key_exists($format, $this->custom_export_formats)) {
			if ($this->dataExists()) {

				foreach ($this->custom_export_generators as $export_format => $generator_config) {
					if ($this->getExportMode() == $export_format) {
						$generator_config['generator']->generate();
					}
				}
			}
		} else {
			parent::exportData($format, $send);
		}
	}

	/**
	 * @param object $a_worksheet
	 * @param int    $a_row
	 * @param array  $a_set
	 */
	protected function fillRowExcel($a_worksheet, &$a_row, $a_set) {
		$col = 0;

		foreach ($this->getSelectableColumns() as $k => $v) {
			if ($this->isColumnSelected($k)) {
				if (is_array($a_set[$k])) {
					$a_set[$k] = implode(', ', $a_set[$k]);
				}
				$a_worksheet->writeString($a_row, $col, strip_tags($a_set[$k]));
				$col ++;
			}
		}
	}

	protected function fillHeaderExcel($worksheet, &$a_row)
	{
		$col = 0;
		foreach ($this->getSelectableColumns() as $column_key => $column)
		{
			$title = strip_tags($column["txt"]);
			if(!in_array($column_key, $this->getIgnoredCols()) && $title != '')
			{
				if ($this->isColumnSelected($column_key)) {
					$worksheet->write($a_row, $col, $title);
					$col++;
				}
			}
		}
		$a_row++;
	}

	/**
	 * @param object $a_csv
	 * @param array  $a_set
	 */
	protected function fillRowCSV($a_csv, $a_set) {

		foreach ($this->getSelectableColumns() as $k => $v) {
			if ($this->isColumnSelected($k)) {
				if (is_array($a_set[$k])) {
					$a_set[$k] = implode(', ', $a_set[$k]);
				}
				$a_csv->addColumn(strip_tags($a_set[$k]));
			}
		}
		$a_csv->addRow();
	}


	/**
	 * @param array $custom_export_generators
	 */
	public function addCustomExportGenerator($export_format_key, $custom_export_generators, $params = array()) {
		$this->custom_export_generators[$export_format_key] = array( 'generator' => $custom_export_generators, 'params' => $params );
	}


	/**
	 * @param array $custom_export_formats
	 */
	public function addCustomExportFormat($custom_export_format_key, $custom_export_format_label) {
		$this->custom_export_formats[$custom_export_format_key] = $custom_export_format_label;
	}


	/**
	 * @return bool
	 */
	public function numericOrdering($sort_field) {
		return in_array($sort_field, array());
	}


	/**
	 * @param array $ignored_cols
	 */
	public function setIgnoredCols($ignored_cols) {
		$this->ignored_cols = $ignored_cols;
	}


	/**
	 * @return array
	 */
	public function getIgnoredCols() {
		return $this->ignored_cols;
	}

	/**
	 * @param boolean $default_filter
	 */
	public function setShowDefaultFilter($show_default_filter) {
		$this->show_default_filter = $show_default_filter;
	}


	/**
	 * @return boolean
	 */
	public function getShowDefaultFilter() {
		return $this->show_default_filter;
	}
} 