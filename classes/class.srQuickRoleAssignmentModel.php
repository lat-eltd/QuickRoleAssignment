<?php

require_once('./Services/Tracking/classes/class.ilLPCollections.php');
require_once('./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php');

/**
 * Class srQuickRoleAssignmentModel
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srQuickRoleAssignmentModel {
	static $user_cache = array();

	public static function getUsers(array $options = array()) {
		global $ilDB, $rbacsystem;

/*		ilUserQuery::getUserListData(
			ilUtil::stripSlashes($this->getOrderField()),
			ilUtil::stripSlashes($this->getOrderDirection()),
			ilUtil::stripSlashes($this->getOffset()),
			ilUtil::stripSlashes($this->getLimit()),
			$this->filter["query"],
			$this->filter["activation"],
			$this->filter["last_login"],
			$this->filter["limited_access"],
			$this->filter["no_courses"],
			$this->filter["course_group"],
			$this->filter["global_role"],
			$user_filter,
			$additional_fields,
			null,
			ilUtil::stripSlashes($_GET["letter"])
		);*/

		//SELECT usr_data.usr_id,usr_data.login,usr_data.firstname,usr_data.lastname,usr_data.email,usr_data.time_limit_until,usr_data.time_limit_unlimited,usr_data.time_limit_owner,usr_data.last_login,usr_data.active FROM usr_data WHERE usr_data.usr_id <> 13 AND usr_data.time_limit_owner IN (7) ORDER BY usr_data.login ASC

		if ($options['count']) {
			$sql = 'SELECT COUNT(usr_data.usr_id) as count ';
		} else {
			$sql = 'SELECT usr_data.usr_id,usr_data.login,usr_data.firstname,usr_data.lastname,usr_data.email,usr_data.time_limit_until,usr_data.time_limit_unlimited,usr_data.time_limit_owner,usr_data.last_login,usr_data.active ';
		}

		$sql .= "   FROM usr_data
					WHERE usr_data.time_limit_owner IN (7) ";


		if(strpos($options['filters']['login'], ',') !== false) {
			$options['filters']['login'] = explode(',', $options['filters']['login']);
		}

		// only parse the login filter!
		$sql .= self::parseWhereQuery($options['filters'], array('login'));
		$sql .= self::parseDefaultQueryOptions($options);

		$result = $ilDB->query($sql);
		if ($options['count']) {
			$rec = $ilDB->fetchAssoc($result);
			return $rec['count'];
		} else {
			$data = array();

			while($rec = $ilDB->fetchAssoc($result)) {
				$data[$rec['usr_id']] = $rec;
			}
			return $data;
		}
	}

	public static function getAvailableRoles() {
		$available_roles = srQuickRoleAssignmentConfig::get(srQuickRoleAssignmentConfig::F_ASSIGNABLE_ROLES);

		$role_labels = srQuickRoleAssignmentModel::getRolesByName(false);
		foreach($available_roles as $key=>$role_id) {
			$available_roles[$role_id] = array('obj_id'=>$role_id, 'title'=>$role_labels[$role_id]);
		}
		return $available_roles;
	}

	public static function getRoleIds() {
		global $rbacreview;

		$role_ids = array();
		foreach ($rbacreview->getRolesByFilter(ilRbacReview::FILTER_ALL) as $role) {
			$role_ids[] = $role['obj_id'];
		}
	}

	public static function getRolesByName($add_id = true) {
		global $rbacreview;

		$opt = array();
		foreach ($rbacreview->getRolesByFilter(ilRbacReview::FILTER_ALL) as $role) {
			$entry = $role['title'];
			$entry .= ($add_id)? ' (' . $role['obj_id'] . ')' : '';

			$opt[$role['obj_id']] = $entry;
		}
		return $opt;
	}

	public static function getUserAssignments($usr_ids) {
		global $ilDB;

		$role_arr = array();

		$query = "SELECT usr_id, rol_id FROM rbac_ua WHERE ".$ilDB->in("usr_id", $usr_ids,false, 'integer');

		$res = $ilDB->query($query);
		while($row = $ilDB->fetchObject($res))
		{
			$role_arr[$row->usr_id][$row->rol_id] = $row->rol_id;
		}
		return $role_arr;
	}

	public static function mergeDefaultOptions(array $options, array $defaults = array()) {

		$_options = (count($defaults) > 0)? $defaults : array(
			'filters' => array(),
			'permission_filters'=>array(),
			'sort' => array(),
			'limit' => array(),
			'count' => false,
		);
		return array_merge($_options, $options);
	}

	public static function parseWhereQuery($filters, $valid_params = false, $first = false, $op = "AND") {
		global $ilDB;

		// allow filtering with *
		$sql = "";
		foreach($filters as $key => $value) {
			if($value != null) {

				if(is_array($valid_params) && !in_array($key, $valid_params)) {
					continue;
				}

				// parse options as array
				if(is_array($value)) {
					$other_sql = '';
					$first = true;
					foreach($value as $split) {
						if($split != null && $split != '') {
							$other_sql .= ($first)? '' : ' OR ';
							if(!is_numeric($split) && !is_array($split)) {
								$other_sql .= $ilDB->like($key, 'text', "%".trim(str_replace("*","%",trim($split)), "%")."%");
							} else {
								$other_sql .= $key."=".$ilDB->quote($split, 'text');
							}
							$first = false;
						}
					}

					$sql .= $op.' ('.$other_sql.') ';

					if($other_sql != '') {
						$first = false;
					}
					continue;
				}

				$sql .= ($first)? '' : ' '.$op.' ';
				if(!is_numeric($value) && !is_array($value)) {
					$sql .= $ilDB->like($key, 'text', "%".trim(str_replace("*","%",trim($value)), "%")."%");
				} else {
					$sql .= $key."=".$ilDB->quote($value, 'text');
				}
				$first = false;
			}
		}
		return $sql;
	}

	public static function parseDefaultQueryOptions($options) {
		$sql = "";
		if (isset($options['sort']['field']) && isset($options['sort']['direction'])) {
			$sql .= " ORDER BY ".$options['sort']['field']." ".$options['sort']['direction'];
		}

		if (isset($options['limit']['start']) && isset($options['limit']['end'])) {
			$sql .= " LIMIT ".$options['limit']['start'].", ".$options['limit']['end'];
		}

		return $sql;
	}

}