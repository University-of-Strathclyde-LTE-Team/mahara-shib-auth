<?php

/**
 * DB access library
 */
defined('INTERNAL') || die();
/**
 * Returns the configuration for an authentication plugin.
 *
 * @param $plugin
 */
function auth_config_get($plugin) {
    $result = new StdClass();
    if ($records = get_records_array('auth_config', 'plugin', $plugin)) {
        foreach ($records as $record) {
            $field = $record->field;
            $result->$field = $record->value;
        }
    }
    return $result;
}

/**
 * Returns all authentication instances using the shibboleth method
 *
 */
function auth_instance_get_shibboleth_records() {
    $result = get_records_select_array('auth_instance', "authname = 'shibboleth'");
    $result = empty($result) ? array() : $result;
    return $result;
}

/**
 * Returns the configuration for an authentication intance.
 *
 * @param $instance
 */
function auth_instance_config_get($instance) {
    $result = new StdClass();
    $id = is_object($instance) ? $instance->id : $instance;
    if ($records = get_records_array('auth_instance_config', 'instance', $id)) {
        foreach ($records as $record) {
            $field = $record->field;
            $result->$field = $record->value;
        }
    }
    return $result;
}

/**
 * Update/insert data as required into the "auth_instance" table;
 *
 * @param $id
 * @param $instancename
 * @param $priority
 * @param $institution
 * @param $authname
 */
function auth_instance_save($id, $instancename, $institution, $authname, $priority=null) {
    $record = new stdClass();
    $record->id = $id;
    $record->instancename = $instancename;
    $record->institution = $institution;
    $record->authname = $authname;

    $isnew = empty($id) || !record_exists('auth_instance', 'id', $id);
    if ($isnew && empty($priority)) {
        $instances = get_records_array('auth_instance', 'institution', $institution, 'priority DESC', '*', '0', '1');
        $record->priority = ($instances == false) ? 0 : $instances[0]->priority + 1;
        $id = insert_record('auth_instance', $record, 'id', true);
    } else {
        if (!empty($priority)) {
            $record->priority = $priority;
        }
        update_record('auth_instance', $record, array('id' => $id));
    }

    return $id;
}

/**
 * Update/insert an instance configuration as required.
 *
 * @param $instance numeric instance id
 * @param $values associative array of key values to store for this instance
 */
function auth_instance_config_save($instance, $values) {
    delete_records('auth_instance_config', 'instance', $instance);
    foreach ($values as $key => $value) {
        $record = new stdClass();
        $record->instance = $instance;
        $record->field = $key;
        $record->value = $value;

        insert_record('auth_instance_config', $record);
    }
}

function get_user_institution_administrators($user) {
    $result = array();

    $memberships = $user->get('institutions');
    foreach ($memberships as $membership) {
        $institution = $membership->institution;
        $sql = 'SELECT ui.usr
    			FROM {usr_institution} ui
    			LEFT JOIN  {usr} u ON ui.usr = u.id
			    WHERE ui.admin = 1
				    AND ui.institution = ?
				    AND u.deleted = 0';

        $admins = get_column_sql($sql, array($institution));
        $result = array_merge($result, $admins);
    }

    return $result;
}

function get_site_administrators() {
    $sql = 'SELECT u.id FROM {usr} u WHERE u.admin = 1';
    $admins = get_column_sql($sql);
    if (is_array($admins)) {
        $result = $admins;
    } else if ($admins === false) {
        $result = array();
    } else {
        $result = array($admins);
    }
    return $result;
}

function user_exists($username) {
    if (empty($username)) {
        return false;
    }
    $sql = 'SELECT * FROM {usr} WHERE username = ?';
    $user = get_record_sql($sql, array($username));
    return $user !== false;
}

?>