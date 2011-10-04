<?php

require_once dirname(__FILE__) . '/registration_base.class.php';

/**
 * Login manager. The login manager is responsible to coordinate the various activities and responses during the login process.
 * The main - entry - method is "process" which dispatch the work to helper methods as needed.
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class login_manager {

    /**
     * Returns an array of registration objects. Loaded dynamically from ./registration/ folder
     */
    public static function get_registrations() {
        $result = array();
        $dir = dirname(__FILE__) . '/registration/';
        $files = scandir($dir);
        $files = array_diff($files, array('.', '..'));
        foreach ($files as $file) {
            $path = "$dir/$file";
            $class = str_replace('.class.php', '', $file);
            include_once($path);
            $registration = new $class();
            $result[$registration->name()] = $registration;
        }
        ksort($result);
        return $result;
    }

    /**
     * Returns the registration method with the corresponding $name name.
     * If the methods doesn't exist returns empty registration
     *
     * @param string $name
     * @return registration_base
     */
    public static function registation_factory($name) {
        $registrations = self::get_registrations();
        if (isset($registrations[$name])) {
            return $registrations[$name];
        } else {
            return new empty_registration();
        }
    }

    protected $registration = null;

    public function __construct() {
        $this->registration = self::registation_factory($this->config(PluginAuthShibboleth::REGISTRATION));
        $this->registration->init($this->user_data(), $this->institution_data());
    }

    /**
     * Returns the shibboleth user's user name.
     */
    public function username() {
        $data = $this->user_data();
        return isset($data->username) ? $data->username : '';
    }

    /**
     * If true,  user account data are updated on each login. That is first name, last name, email
     * are overwritten by the shibboleth's data.
     * If false, user account data are taken from the shibboleths fields only during account creation.
     */
    public function update_user_data() {
        return $this->config(PluginAuthShibboleth::UPDATE_USER_DATA, true);
    }

    /**
     * If true, new user accounts are made active upon creation. If false they are made inactive.
     */
    public function create_active() {
        return $this->config(PluginAuthShibboleth::CREATE_ACTIVE, false);
    }

    /**
     * Returns true if the shibboleth user is a new user. False otherwise.
     */
    public function is_new_user() {
        return!user_exists($this->username());
    }

    /**
     * Returns true if the shibboleth user is active. False otherwise.
     */
    public function is_active() {
        $user = new user();
        try {
            $user->find_by_username($this->username());
        } catch (Exception $e) {
            return true;
        }
        return (bool) $user->get('active');
    }

    /**
     * Returns true if the shibboleth user is site administrator. False otherwise.
     */
    public function is_admin() {
        $user = new user();
        try {
            $user->find_by_username($this->username());
        } catch (Exception $e) {
            return false;
        }
        return (bool) $user->get('admin');
    }

    /**
     * Returns a plugin config value.
     * @param string $name 		the name of the plugin value to read
     * @param mixed $default 	the default value to return if the plugin value is not set
     */
    public function config($name = '', $default = '') {
        static $config = null;
        if (is_null($config)) {
            $config = auth_config_get(PluginAuthShibboleth::NAME);
        }
        if (empty($name)) {
            $result = $config;
        } else if (isset($config->$name)) {
            $result = $config->$name;
        } else {
            $result = $default;
        }
        return $result;
    }

    /**
     * Returns the first shibboleth authoring instance that accept the shibboleth user.
     */
    public function auth_instance() {
        $instances = auth_instance_get_shibboleth_records();
        foreach ($instances as $instance) {
            $config = auth_instance_config_get($instance->id);
            $field = isset($config->shibboleth_institution_field) ? $config->shibboleth_institution_field : '';
            $is_regex = isset($config->shibboleth_institution_regex) ? $config->shibboleth_institution_regex : false;
            $value = strtolower(isset($config->shibboleth_institution_value) ? $config->shibboleth_institution_value : '');
            if ($this->shibboleth_match($field, $value, $is_regex, false)) {
                return $instance;
            }
        }
        return false;
    }

    /**
     * If instance is not empty read the institution user data from $SERVER and returns them in an object. Otherwise returns false.
     *
     * @param $instance the instance the user is associated with.
     * @return boolean|StdClass false if instance is empty or an object made of user instance data.
     * @return
     */
    public function institution_data($instance=null) {
        if (is_null($instance)) {
            return $instance = $this->auth_instance();
        }
        $result = new StdClass();
        $config = auth_instance_config_get($instance);

        $field = isset($config->{PluginAuthShibboleth::INSTITUTION_IS_ADMIN_FIELD}) ? $config->{PluginAuthShibboleth::INSTITUTION_IS_ADMIN_FIELD} : '';
        $value = isset($config->{PluginAuthShibboleth::INSTITUTION_IS_ADMIN_VALUE}) ? $config->{PluginAuthShibboleth::INSTITUTION_IS_ADMIN_VALUE} : '';
        $is_regex = isset($config->{PluginAuthShibboleth::INSTITUTION_IS_ADMIN_REGEX}) ? $config->{PluginAuthShibboleth::INSTITUTION_IS_ADMIN_REGEX} : false;
        if (!empty($field)) {
            $result->admin = $this->shibboleth_match($field, $value, $is_regex, false);
        }

        $field = isset($config->{PluginAuthShibboleth::INSTITUTION_IS_STAFF_FIELD}) ? $config->{PluginAuthShibboleth::INSTITUTION_IS_STAFF_FIELD} : '';
        $value = isset($config->{PluginAuthShibboleth::INSTITUTION_IS_STAFF_VALUE}) ? $config->{PluginAuthShibboleth::INSTITUTION_IS_STAFF_VALUE} : '';
        $is_regex = isset($config->{PluginAuthShibboleth::INSTITUTION_IS_STAFF_REGEX}) ? $config->{PluginAuthShibboleth::INSTITUTION_IS_STAFF_REGEX} : '';
        if (!empty($field)) {
            $result->staff = $this->shibboleth_match($field, $value, $is_regex, false);
        }

        $result->institution = $instance->institution;

        return $result;
    }

    /**
     * Returns true if the posted $field match $value. Otherwise returns $default.
     *
     * @param $field
     * @param $value
     * @param $is_regex
     * @param $default
     */
    protected function shibboleth_match($field, $value, $is_regex, $default=false) {
        $shib_fields = $this->get_shibboleth_fields();
        if (!empty($field) && isset($shib_fields[$field])) {
            $current_value = strtolower($shib_fields[$field]);
            return ($is_regex && preg_match($value, $current_value)) || (!$is_regex && $current_value == $value);
        } else {
            return $default;
        }
    }

    /**
     * Read the user data from $SERVER as specified by the Shibboelth plugin config.
     */
    public function user_data($name='') {
        static $result = null;
        if (!is_null($result)) {
            if (empty($name)) {
                return $result;
            } else {
                return isset($result->$name) ? $result->$name : '';
            }
        }

        $result = new StdClass();
        $config = auth_config_get(PluginAuthShibboleth::NAME);

        $map = array(PluginAuthShibboleth::FIRSTNAME => 'firstname',
            PluginAuthShibboleth::LASTNAME => 'lastname',
            PluginAuthShibboleth::EMAIL => 'email',
            PluginAuthShibboleth::USERNAME => 'username',
            PluginAuthShibboleth::STUDENT_ID => 'studentid');

        $shib_fields = $this->get_shibboleth_fields();
        foreach ($map as $key => $user_field_name) {
            $field_name = $config->$key;
            if (isset($shib_fields[$field_name])) {
                $result->$user_field_name = $shib_fields[$field_name];
            }
        }

        $field = $this->config(PluginAuthShibboleth::IS_ADMIN_FIELD, '');
        $value = $this->config(PluginAuthShibboleth::IS_ADMIN_VALUE, '');
        $is_regex = $this->config(PluginAuthShibboleth::IS_ADMIN_REGEX, false);
        if (!empty($field)) {
            $result->admin = $this->shibboleth_match($field, $value, $is_regex, false);
        }

        $field = $this->config(PluginAuthShibboleth::IS_STAFF_FIELD, '');
        $value = $this->config(PluginAuthShibboleth::IS_STAFF_VALUE, '');
        $is_regex = $this->config(PluginAuthShibboleth::IS_STAFF_REGEX, false);
        if (!empty($field)) {
            $result->staff = $this->shibboleth_match($field, $value, $is_regex, false);
        }

        /**
         * Default preferredname to "Firstname Lastname" to avoid displaying the shibboleth user id
         */
        if(!isset($result->preferredname)){
            $preferredname = isset($result->firstname ) ? ucfirst($result->firstname) : '';
            $preferredname .= isset($result->lastname ) ? ' ' . ucfirst($result->lastname) : '';
            $result->preferredname = $preferredname;
        }

        return $result;
    }

    /**
     * Returns true if $SERVER contains shibboleth data. False otherwise.
     */
    public function has_shibboleth_fields() {
        $fields = $this->get_shibboleth_fields();
        return!empty($fields);
    }

    /**
     * Returns the shibboleths fiels available from $SERVER.
     */
    protected function get_shibboleth_fields() {
        $result = array();
        foreach ($_SERVER as $key => $value) {
            if ($this->is_shibboleth_field($key, $value)) {
                $result[$key] = $value;
            }
        }
        /*
          if(isset($_POST['shibboleth_fields'])){
          $shibboleth_fields =  $_POST['shibboleth_fields'];
          $shibboleth_fields = str_replace('$_$', '"', $shibboleth_fields);
          $shibboleth_fields = unserialize($shibboleth_fields);
          foreach($shibboleth_fields as $key=>$val){
          $result[$key] = $val;
          }
          } */
        return $result;
    }

    /**
     * Returns true if $key is the name of a shibboleth's field. False otherwise.
     *
     * @param string $key		shibboleth field's name
     * @param string $value		shibboleth field's value
     */
    protected function is_shibboleth_field($key, $value) {
        $head = 'shib';
        $head_length = strlen($head);
        if (strlen($key) < $head_length) {
            return false;
        }
        return strtolower(substr($key, 0, $head_length)) == $head;
    }

    /**
     * Main method. Dispatch work to helper methods as needed.
     */
    public function process() {
        global $SESSION;

        if (!$this->has_shibboleth_fields()) {
            $SESSION->add_error_msg(get_string('error_no_shibboleth_fields', 'auth.shibboleth'));
            redirect();
        } else if ($this->is_new_user()) {
            $this->register();
        } else if (!$this->is_active()) {
            $SESSION->add_error_msg(get_string('account_inactive_message', 'auth.shibboleth'));
            redirect();
        } else if ($this->login()) {
            redirect();
        } else {
            $SESSION->add_error_msg(get_string('error_internal_login_failed', 'auth.shibboleth'));
            redirect();
        }
    }

    /**
     * Register new users. Create the account. Process the selected registration's method. Inactive the account if required. Notify administrators and user.
     */
    protected function register() {
        global $SESSION, $USER;
        $form = $this->registration;
        if (!$form->is_valid()) {
            $form->display();
            return;
        }

        if ($this->login()) {
            $form->process($USER);
            if (!$this->create_active()) {
                $message = $form->user_confirmation();
                $message = empty($message) ? get_string('your_request_has_been_sent_message', 'auth.shibboleth') : $message;
                $this->suspend_user();
                $SESSION->add_ok_msg($message);
            }
            redirect();
        } else {
            $SESSION->add_error_msg(get_string('error_internal_login_failed', 'auth.shibboleth'));
            redirect();
        }
    }

    /**
     * Log in the shibboleth's user.
     */
    public function login() {
        $instance = $this->auth_instance();
        $institution_data = $this->institution_data();
        $userdata = $this->user_data();
        $username = $this->username();
        $update_user_data = $this->update_user_data();

        $result = shibboleth_login($username, $userdata, $instance, $institution_data, $update_user_data);
        return $result;
    }

    /**
     * Suspend $USER
     * @param string $reason	reason for susending the user. Defaults to "creation of account".
     */
    public function suspend_user($reason = '') {
        global $USER;

        if (!$this->create_active()) {
            $reason = empty($reason) ? get_string('creation_of_account', 'auth.shibboleth') : $reason;
            suspend_user($USER->get('id'), $reason);
            $USER->logout(); //logout after suspenstion !!
        }

        return true;
    }

}

