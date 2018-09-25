<?php
use srag\DIC\DICTrait;
/**
 * Class srQuickRoleAssignmentModel
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srQuickRoleAssignmentModel {

	static $user_cache = array();


    /**
     * @param array $options
     * @return array
     */
    public static function getUsers(array $options = array()) {
		global $ilDB;

		if ($options['count']) {
			$sql = 'SELECT COUNT(usr_data.usr_id) as count ';
		} else {
			$sql = 'SELECT usr_data.usr_id,usr_data.login,usr_data.firstname,usr_data.lastname,usr_data.email,usr_data.time_limit_until,usr_data.time_limit_unlimited,usr_data.time_limit_owner,usr_data.last_login,usr_data.active ';
		}

		// show only global users
		$sql .= "   FROM usr_data
					WHERE usr_data.time_limit_owner = 7 ";

		// allow comma-separated search with wildcards
		if (strpos($options['filters']['login'], ',') !== false) {
			$options['filters']['login'] = explode(',', $options['filters']['login']);
		}

		// only parse the login filter!
		$sql .= self::parseWhereQuery($options['filters'], array( 'login' ));
		$sql .= self::parseDefaultQueryOptions($options);

		$result = $ilDB->query($sql);
		if ($options['count']) {
			$rec = $ilDB->fetchAssoc($result);

			return $rec['count'];
		} else {
			$data = array();

			while ($rec = $ilDB->fetchAssoc($result)) {
				$data[$rec['usr_id']] = $rec;
			}

			return $data;
		}
	}


    /**
     * @param bool $show_role_description
     * @return array
     */
    public static function getAvailableRoles($show_role_description = false) {
		$available_roles_config = srQuickRoleAssignmentConfig::getConfig(srQuickRoleAssignmentConfig::F_ASSIGNABLE_ROLES);

		// do not allow admin role
		$role_labels = srQuickRoleAssignmentModel::getRoleDefinitions(false);

		$available_roles = array();
		foreach ($available_roles_config as $key => $role_id) {
			$available_roles[$role_id] = array( 'obj_id'      => $role_id,
			                                    'title'       => $role_labels[$role_id]['title'],
			                                    'description' => $role_labels[$role_id]['description'],
			);
		}

		return $available_roles;
	}


    /**
     * @param bool $add_id
     * @param bool $remove_admin_role
     * @return array
     */
    public static function getRoleDefinitions($add_id = true, $remove_admin_role = true) {
		$opt = array();
		foreach (self::getRoles() as $role) {
			if (!$remove_admin_role || $role['obj_id'] != SYSTEM_ROLE_ID) {
				$entry = $role['title'];
				$entry .= ($add_id) ? ' (' . $role['obj_id'] . ')' : '';

				$opt[$role['obj_id']] = array( 'title' => $entry, 'description' => $role['desc'] );
			}
		}

		return $opt;
	}


    /**
     * @param bool $add_role_id
     * @param bool $remove_admin_role
     * @return array
     */
    public static function getRoleNames($add_role_id = true, $remove_admin_role = true) {
		$roles = self::getRoleDefinitions($add_role_id, $remove_admin_role);
		$out = array();
		foreach ($roles as $role_id => $role_definition) {
			$out[$role_id] = $role_definition['title'];
		}

		return $out;
	}


    /**
     * @return array
     */
    public static function getRoles() {
		global $rbacreview;

		$roles = $rbacreview->getRolesByFilter(ilRbacReview::FILTER_ALL_GLOBAL);

		return array_merge($roles, $rbacreview->getRolesByFilter(ilRbacReview::FILTER_NOT_INTERNAL));
	}


    /**
     * @param $usr_ids
     * @return array
     */
    public static function getUserAssignments($usr_ids) {
		global $ilDB;

		$role_arr = array();

		$query = "SELECT usr_id, rol_id FROM rbac_ua WHERE " . $ilDB->in("usr_id", $usr_ids, false, 'integer');

		$res = $ilDB->query($query);
		while ($role = $ilDB->fetchAssoc($res)) {
			// only non admin roles
			if ($role['rol_id'] != SYSTEM_ROLE_ID) {
				$role_arr[$role['usr_id']][$role['rol_id']] = $role['rol_id'];
			}
		}

		return $role_arr;
	}


	// HELPERS

    /**
     * @param array $options
     * @param array $defaults
     * @return array
     */
    public static function mergeDefaultOptions(array $options, array $defaults = array()) {

		$_options = (count($defaults) > 0) ? $defaults : array(
			'filters'            => array(),
			'permission_filters' => array(),
			'sort'               => array(),
			'limit'              => array(),
			'count'              => false,
		);

		return array_merge($_options, $options);
	}


    /**
     * @param $filters
     * @param bool $valid_params
     * @param bool $first
     * @param string $op
     * @return string
     */
    public static function parseWhereQuery($filters, $valid_params = false, $first = false, $op = "AND") {
		global $ilDB;

		// allow filtering with *
		$sql = "";
		foreach ($filters as $key => $value) {
			if ($value != null) {

				if (is_array($valid_params) && !in_array($key, $valid_params)) {
					continue;
				}

				// parse options as array
				if (is_array($value)) {
					$other_sql = '';
					$first = true;
					foreach ($value as $split) {
						if ($split != null && $split != '') {
							$other_sql .= ($first) ? '' : ' OR ';
							if (!is_numeric($split) && !is_array($split)) {
								$other_sql .= $ilDB->like($key, 'text', "%" . trim(str_replace("*", "%", trim($split)), "%") . "%");
							} else {
								$other_sql .= $key . "=" . $ilDB->quote($split, 'text');
							}
							$first = false;
						}
					}

					$sql .= $op . ' (' . $other_sql . ') ';

					if ($other_sql != '') {
						$first = false;
					}
					continue;
				}

				$sql .= ($first) ? '' : ' ' . $op . ' ';
				if (!is_numeric($value) && !is_array($value)) {
					$sql .= $ilDB->like($key, 'text', "%" . trim(str_replace("*", "%", trim($value)), "%") . "%");
				} else {
					$sql .= $key . "=" . $ilDB->quote($value, 'text');
				}
				$first = false;
			}
		}

		return $sql;
	}


    /**
     * @param $options
     * @return string
     */
    public static function parseDefaultQueryOptions($options) {
		$sql = "";
		if (isset($options['sort']['field']) && isset($options['sort']['direction'])) {
			$sql .= " ORDER BY " . $options['sort']['field'] . " " . $options['sort']['direction'];
		}

		if (isset($options['limit']['start']) && isset($options['limit']['end'])) {
			$sql .= " LIMIT " . $options['limit']['start'] . ", " . $options['limit']['end'];
		}

		return $sql;
	}
}