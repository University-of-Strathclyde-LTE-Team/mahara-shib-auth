<?php

/**
 * Generic library
 */
defined('INTERNAL') || die();

require_once(get_config('docroot') . 'auth/lib.php');

require_once(dirname(__FILE__) . '/util/debug_util.class.php');
require_once(dirname(__FILE__) . '/util/util.php');

require_once(dirname(__FILE__) . '/db.php');
require_once(dirname(__FILE__) . '/plugin_auth_shibboleth.class.php');
require_once(dirname(__FILE__) . '/auth_shibboleth.class.php');
require_once(dirname(__FILE__) . '/manager/login_manager.class.php');
/**
 * Create or update as required a user with $userdata information.
 *
 * @param $username
 * @param $userdata
 * @param $autoring_instance
 * @return LiveUser|string
 * @return
 */
function shibboleth_ensure_user($username, $userdata, $autoring_instance=null, $update_user_data) {
    if (empty($username)) {
        return false;
    }
    $user = new LiveUser();
    try {
        $user->find_by_username($username);
        $isnew = false;
    } catch (AuthUnknownUserException $e) {
        $isnew = true;
        $user->username = $username;
        $user->staff = 0;
        $user->admin = 0;
        $user->password = sha1(uniqid('', true));
        $user->authinstance = null;
        DebugUtil::disable();
        $user->quota_init();
        DebugUtil::enable();
    }

    if ($update_user_data || $isnew) {
        $keys = array('firstname', 'lastname', 'email', 'admin', 'staff', 'preferredname', 'studentid');
        foreach ($keys as $key) {
            if (isset($userdata->$key)) {
                if (($key != 'firstname' && $key != 'lastname' && $key != 'email') || !empty($userdata->$key)) {
                    $user->$key = $userdata->$key;
                }
            }
        }
    }

    $user->authinstance = isset($autoring_instance->id) ? $autoring_instance->id : 1;

    try {
        if ($isnew) {
            $user->id = create_user($user, array(), null, isset($instance->institution) ? $instance->institution : null);
        } else {
            $user->commit();
        }
    } catch (Exception $e) {
        db_rollback();
        return false;
    }


    $id = $user->id;
    $studentid = $user->studentid;
    if (!empty($id) && !empty($studentid)) {
        include_once get_config('docroot') . 'artefact/lib.php';
        include_once get_config('docroot') . 'artefact/internal/lib.php';
        $profile = new ArtefactTypeStudentid(0, array('owner' => $id));
        $profile->set('title', $studentid);
        $profile->commit();
    }

    /*
     * If we have a preffered name we need to update both the usr table and the artifact table.
     * The usr table is used to read the value for login blocks, etc.
     * The artifact table is used when displaying the user preference such as in the update profile form.
     */
    $preferredname = $user->preferredname;
    if (!empty($id) && !empty($preferredname)) {
        include_once get_config('docroot') . 'artefact/lib.php';
        include_once get_config('docroot') . 'artefact/internal/lib.php';
        $profile = new ArtefactTypePreferredname(0, array('owner' => $id));
        $profile->set('title', $preferredname);
        $profile->commit();
    }
    
    return $user;
}

/**
 * Ensure a user is made a member of the corresponsding institution with proper data (for staff and admin).
 *
 * @param $user
 * @param $institution_data
 */
function shibboleth_ensure_user_institution(User $user, $institution_data, $update_user_data) {
    if (empty($institution_data) || $user == false) {
        return false;
    }
    $isnew = !$user->in_institution($institution_data->institution);
    if ($isnew) {
        $user->join_institution($institution_data->institution);
    }
    if (($update_user_data || $isnew) && (isset($institution_data->staff) || isset($institution_data->admin))) {
        $result = update_record('usr_institution', $institution_data, array('usr' => $user->id, 'institution' => $institution_data->institution));
    }
}

/**
 * Create a user if required. Update user with $userdata if user already exists.
 * Log user. Returns true on success false otherwise.
 *
 * @param $username
 * @param $userdata
 * @param $authoring_instance
 * @param $institution_data
 */
function shibboleth_login($username, $userdata, $authoring_instance, $institution_data, $update_user_data) {
    if (empty($username) || empty($userdata)) {
        return false;
    }

    global $SESSION, $USER;
    $SESSION->destroy_session();
    $USER = shibboleth_ensure_user($username, $userdata, $authoring_instance, $update_user_data);
    shibboleth_ensure_user_institution($USER, $institution_data, $update_user_data);
    $result = $USER->reanimate($USER->id, isset($authoring_instance->id) ? $authoring_instance->id : 1);

    // Only admins in the admin section!
    if (!$USER->get('admin') &&
            (defined('ADMIN') || defined('INSTITUTIONALADMIN') && !$USER->is_institutional_admin())) {
        $SESSION->add_error_msg(get_string('accessforbiddentoadminsection'));
        redirect();
    }

    // User is allowed to log in
    DebugUtil::disable();
    auth_check_required_fields();
    DebugUtil::enable();

    return true;
}

?>