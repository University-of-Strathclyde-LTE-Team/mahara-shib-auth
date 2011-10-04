<?php

/**
 * Shibboleth plugin.
 * 
 * @copyright (c) University of Geneva 
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class PluginAuthShibboleth extends PluginAuth {

    const NAME = 'shibboleth'; //Plugin name
    //fields name for plugin configuration.
    //const LINK_NAME = 'shibboleth_link_name';
    const USERNAME = 'shibboleth_username';
    const FIRSTNAME = 'shibboleth_firstname';
    const LASTNAME = 'shibboleth_lastname';
    const EMAIL = 'shibboleth_email';
    const STUDENT_ID = 'shibboleth_student_id';
    const CREATE_ACTIVE = 'shibboleth_create_active';

    const IS_ADMIN_FIELD = 'shibboleth_is_admin_field';
    const IS_ADMIN_VALUE = 'shibboleth_is_admin_value';
    const IS_ADMIN_REGEX = 'shibboleth_is_admin_regex';

    const IS_STAFF_FIELD = 'shibboleth_is_staff_field';
    const IS_STAFF_VALUE = 'shibboleth_is_staff_value';
    const IS_STAFF_REGEX = 'shibboleth_is_staff_regex';

    //fields name for instance configuration. I.e. institution configuration.
    const INSTITUTION_FIELD = 'shibboleth_institution_field';
    const INSTITUTION_VALUE = 'shibboleth_institution_value';
    const INSTITUTION_REGEX = 'shibboleth_institution_regex';

    const INSTITUTION_IS_ADMIN_FIELD = 'shibboleth_institution_is_admin_field';
    const INSTITUTION_IS_ADMIN_VALUE = 'shibboleth_institution_is_admin_value';
    const INSTITUTION_IS_ADMIN_REGEX = 'shibboleth_institution_is_admin_regex';

    const INSTITUTION_IS_STAFF_FIELD = 'shibboleth_institution_is_staff_field';
    const INSTITUTION_IS_STAFF_VALUE = 'shibboleth_institution_is_staff_value';
    const INSTITUTION_IS_STAFF_REGEX = 'shibboleth_institution_is_staff_regex';

    const UPDATE_USER_DATA = 'shibboleth_update_user_data';

    const REGISTRATION = 'shibboleth_registration';

    const NOTIFY_SITE_ADMIN = 'shibboleth_notify_site_administrators';
    const NOTIFY_INSTITUTION_ADMIN = 'shibboleth_notify_institution_administrators';

    /*
      public static function get_login_link_ext(){
      DebugUtil::disable();
      $link_name = get_config_plugin('auth', self::NAME, self::LINK_NAME);
      if(empty($link_name)){
      $result = '';
      }else{
      $path = get_config('wwwroot') . 'auth/shibboleth/login/login.php';
      $result = '<h3 style="margin-top:3px; margin-bottom:3px;"><a href="' . $path .'">'. $link_name .'</a></h3>';
      }
      DebugUtil::enable();
      return $result;
      } */
    public static function has_config() {
        return true;
    }

    public static function get_config_options() {
        $registrations = login_manager::get_registrations();
        $registration_options = array();
        foreach ($registrations as $registration) {
            $registration_options[$registration->name()] = $registration->name();
        }

        $elements = array(
            'authname' => array(
                'type' => 'hidden',
                'value' => self::NAME,
            ),
            'authglobalconfig' => array(
                'type' => 'hidden',
                'value' => self::NAME,
            ),
            self::USERNAME => array(
                'type' => 'text',
                'size' => 50,
                'title' => get_string(self::USERNAME, 'auth.shibboleth'),
                'rules' => array(
                    'required' => true,
                ),
                'defaultvalue' => self::def(self::USERNAME, 'Shib-SwissEP-UniqueID'),
                'help' => true,
            ),
            self::FIRSTNAME => array(
                'type' => 'text',
                'size' => 50,
                'title' => get_string(self::FIRSTNAME, 'auth.shibboleth'),
                'rules' => array(
                    'required' => true,
                ),
                'defaultvalue' => self::def(self::FIRSTNAME, 'Shib-InetOrgPerson-givenName'),
                'help' => true,
            ),
            self::LASTNAME => array(
                'type' => 'text',
                'size' => 50,
                'title' => get_string(self::LASTNAME, 'auth.shibboleth'),
                'rules' => array(
                    'required' => true,
                ),
                'defaultvalue' => self::def(self::LASTNAME, 'Shib-Person-surname'),
                'help' => true,
            ),
            self::EMAIL => array(
                'type' => 'text',
                'size' => 50,
                'title' => get_string(self::EMAIL, 'auth.shibboleth'),
                'defaultvalue' => self::def(self::EMAIL, 'Shib-InetOrgPerson-mail'),
                'help' => true,
            ),
            self::STUDENT_ID => array(
                'type' => 'text',
                'size' => 50,
                'title' => get_string(self::STUDENT_ID, 'auth.shibboleth'),
                'defaultvalue' => self::def(self::STUDENT_ID, 'Shib-SwissEP-MatriculationNumber'),
                'help' => true,
            ),
            'fieldset_is_admin' => array(
                'type' => 'fieldset',
                'collapsible' => true,
                'collapsed' => false,
                'legend' => get_string(PluginAuthShibboleth::IS_ADMIN_FIELD . '_title', 'auth.shibboleth'),
                'elements' => array(
                    self::IS_ADMIN_FIELD => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::IS_ADMIN_FIELD, 'auth.shibboleth'),
                        'defaultvalue' => '', //self::def(self::IS_ADMIN_FIELD, 'Shib-SwissEP-swissEduPersonStaffCategory'),
                        'help' => true,
                    ),
                    self::IS_ADMIN_VALUE => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::IS_ADMIN_VALUE, 'auth.shibboleth'),
                        'defaultvalue' => '', //self::def(self::IS_ADMIN_VALUE, '300'),
                        'help' => true,
                    ),
                    self::IS_ADMIN_REGEX => array(
                        'type' => 'checkbox',
                        'title' => get_string(self::IS_ADMIN_REGEX, 'auth.shibboleth'),
                        'rules' => array(),
                        'defaultvalue' => self::def(self::IS_ADMIN_REGEX, false),
                        'help' => true,
                    ),
                )
            ),
            'fieldset_is_staff' => array(
                'type' => 'fieldset',
                'collapsible' => true,
                'collapsed' => false,
                'legend' => get_string(PluginAuthShibboleth::IS_STAFF_FIELD . '_title', 'auth.shibboleth'),
                'elements' => array(
                    self::IS_STAFF_FIELD => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::IS_STAFF_FIELD, 'auth.shibboleth'),
                        'defaultvalue' => '', //self::def(self::IS_STAFF_FIELD, 'Shib-SwissEP-swissEduPersonStaffCategory'),
                        'help' => true,
                    ),
                    self::IS_STAFF_VALUE => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::IS_STAFF_VALUE, 'auth.shibboleth'),
                        'defaultvalue' => '', //self::def(self::IS_STAFF_VALUE, '300'),
                        'help' => true,
                    ),
                    self::IS_STAFF_REGEX => array(
                        'type' => 'checkbox',
                        'title' => get_string(self::IS_STAFF_REGEX, 'auth.shibboleth'),
                        'rules' => array(),
                        'defaultvalue' => self::def(self::IS_STAFF_REGEX, false),
                        'help' => true,
                    )
                )
            ),
            'fieldset_new_users' => array(
                'type' => 'fieldset',
                'collapsible' => true,
                'collapsed' => false,
                'legend' => get_string('new_users', 'auth.shibboleth'),
                'elements' => array(
                    self::CREATE_ACTIVE => array(
                        'type' => 'checkbox',
                        'title' => get_string(self::CREATE_ACTIVE, 'auth.shibboleth'),
                        'defaultvalue' => self::def(self::CREATE_ACTIVE, false),
                        'help' => true,
                    ),
                    self::REGISTRATION => array(
                        'type' => 'select',
                        'title' => get_string(self::REGISTRATION, 'auth.shibboleth'),
                        'rules' => array(),
                        'options' => $registration_options,
                        'defaultvalue' => self::def(self::REGISTRATION, true),
                        'help' => true,
                    ),
                    self::NOTIFY_SITE_ADMIN => array(
                        'type' => 'checkbox',
                        'title' => get_string(self::NOTIFY_SITE_ADMIN, 'auth.shibboleth'),
                        'rules' => array(),
                        'defaultvalue' => self::def(self::NOTIFY_SITE_ADMIN, true),
                        'help' => true,
                    ),
                    self::NOTIFY_INSTITUTION_ADMIN => array(
                        'type' => 'checkbox',
                        'title' => get_string(self::NOTIFY_INSTITUTION_ADMIN, 'auth.shibboleth'),
                        'rules' => array(),
                        'defaultvalue' => self::def(self::NOTIFY_INSTITUTION_ADMIN, true),
                        'help' => true,
                    )
                )
            ),
            self::UPDATE_USER_DATA => array(
                'type' => 'checkbox',
                'title' => get_string(self::UPDATE_USER_DATA, 'auth.shibboleth'),
                'rules' => array(),
                'defaultvalue' => self::def(self::UPDATE_USER_DATA, true),
                'help' => true,
                ));

        return array(
            'elements' => $elements,
            'renderer' => 'table'
        );
    }

    public static function has_instance_config() {
        return true;
    }

    public static function is_usable() {
        return true;
    }

    public static function get_instance_config_options($institution, $instance = 0) {
        if ($instance > 0) {
            $current_config = get_records_menu('auth_instance_config', 'instance', $instance, '', 'field, value');
            $current_config = ($current_config === false) ? array() : $current_config;
        } else {
            $current_config = array();
        }
        if (empty($current_config)) {
            $current_config[self::INSTITUTION_FIELD] = 'Shib-SwissEP-HomeOrganization';
            $current_config[self::INSTITUTION_VALUE] = 'unige.ch';
            $current_config[self::INSTITUTION_REGEX] = false;
            $current_config[self::INSTITUTION_IS_ADMIN_FIELD] = ''; //Shib-SwissEP-swissEduPersonStaffCategory';
            $current_config[self::INSTITUTION_IS_ADMIN_VALUE] = '';
            $current_config[self::INSTITUTION_IS_ADMIN_REGEX] = false;
            $current_config[self::INSTITUTION_IS_STAFF_FIELD] = ''; //Shib-SwissEP-swissEduPersonStaffCategory';
            $current_config[self::INSTITUTION_IS_STAFF_VALUE] = '';
            $current_config[self::INSTITUTION_IS_STAFF_REGEX] = false;
        }

        $elements = array(
            'instance' => array(
                'type' => 'hidden',
                'value' => $instance,
            ),
            'institution' => array(
                'type' => 'hidden',
                'value' => $institution,
            ),
            'authname' => array(
                'type' => 'hidden',
                'value' => 'shibboleth',
            ),
            'fieldset_institution' => array(
                'type' => 'fieldset',
                'collapsible' => true,
                'collapsed' => false,
                'legend' => get_string(PluginAuthShibboleth::INSTITUTION_FIELD . '_title', 'auth.shibboleth'),
                'elements' => array(
                    self::INSTITUTION_FIELD => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::INSTITUTION_FIELD, 'auth.shibboleth'),
                        'rules' => array(
                            'required' => true,
                        ),
                        'defaultvalue' => $current_config[self::INSTITUTION_FIELD],
                        'help' => true,
                    ),
                    self::INSTITUTION_VALUE => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::INSTITUTION_VALUE, 'auth.shibboleth'),
                        'rules' => array(
                            'required' => true,
                        ),
                        'defaultvalue' => $current_config[self::INSTITUTION_VALUE],
                        'help' => true,
                    ),
                    self::INSTITUTION_REGEX => array(
                        'type' => 'checkbox',
                        'title' => get_string(self::INSTITUTION_REGEX, 'auth.shibboleth'),
                        'rules' => array(),
                        'defaultvalue' => $current_config[self::INSTITUTION_REGEX],
                        'help' => true,
                    )
                )
            ),
            'fieldset_is_admin' => array(
                'type' => 'fieldset',
                'collapsible' => true,
                'collapsed' => false,
                'legend' => get_string(PluginAuthShibboleth::INSTITUTION_IS_ADMIN_FIELD . '_title', 'auth.shibboleth'),
                'elements' => array(
                    self::INSTITUTION_IS_ADMIN_FIELD => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::INSTITUTION_IS_ADMIN_FIELD, 'auth.shibboleth'),
                        'defaultvalue' => $current_config[self::INSTITUTION_IS_ADMIN_FIELD],
                        'help' => true,
                    ),
                    self::INSTITUTION_IS_ADMIN_VALUE => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::INSTITUTION_IS_ADMIN_VALUE, 'auth.shibboleth'),
                        'defaultvalue' => $current_config[self::INSTITUTION_IS_ADMIN_VALUE],
                        'help' => true,
                    ),
                    self::INSTITUTION_IS_ADMIN_REGEX => array(
                        'type' => 'checkbox',
                        'title' => get_string(self::INSTITUTION_IS_ADMIN_REGEX, 'auth.shibboleth'),
                        'rules' => array(),
                        'defaultvalue' => $current_config[self::INSTITUTION_IS_ADMIN_REGEX],
                        'help' => true,
                    ),
                )
            ),
            'fieldset_is_staff' => array(
                'type' => 'fieldset',
                'collapsible' => true,
                'collapsed' => false,
                'legend' => get_string(PluginAuthShibboleth::INSTITUTION_IS_STAFF_FIELD . '_title', 'auth.shibboleth'),
                'elements' => array(
                    self::INSTITUTION_IS_STAFF_FIELD => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::INSTITUTION_IS_STAFF_FIELD, 'auth.shibboleth'),
                        'defaultvalue' => $current_config[self::INSTITUTION_IS_STAFF_FIELD],
                        'help' => true,
                    ),
                    self::INSTITUTION_IS_STAFF_VALUE => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string(self::INSTITUTION_IS_STAFF_VALUE, 'auth.shibboleth'),
                        'defaultvalue' => $current_config[self::INSTITUTION_IS_STAFF_VALUE],
                        'help' => true,
                    ),
                    self::INSTITUTION_IS_STAFF_REGEX => array(
                        'type' => 'checkbox',
                        'title' => get_string(self::INSTITUTION_IS_STAFF_REGEX, 'auth.shibboleth'),
                        'rules' => array(),
                        'defaultvalue' => $current_config[self::INSTITUTION_IS_STAFF_REGEX],
                        'help' => true,
                    )
                )
            )
        );

        return array(
            'elements' => $elements,
            'renderer' => 'table'
        );
    }

    public static function validate_config_options($values, $form) {
        //
    }

    public static function save_config_options($values, $form) {
        if (isset($values['authglobalconfig'])) {
            self::save_config_option_plugin($values, $form);
        } else {
            self::save_config_option_instance($values, $form);
        }
        return $values;
    }

    protected static function save_config_option_plugin($values, $form) {
        $keys = array(self::USERNAME, self::FIRSTNAME, self::LASTNAME, self::EMAIL, self::STUDENT_ID, self::CREATE_ACTIVE,
            self::IS_STAFF_FIELD, self::IS_STAFF_REGEX, self::IS_STAFF_VALUE, self::IS_ADMIN_FIELD, self::IS_ADMIN_REGEX,
            self::IS_ADMIN_VALUE, self::UPDATE_USER_DATA, self::REGISTRATION, self::NOTIFY_SITE_ADMIN, self::NOTIFY_INSTITUTION_ADMIN);

        foreach ($keys as $key) {
            set_config_plugin('auth', self::NAME, $key, $values[$key]);
        }
    }

    protected static function save_config_option_instance($values, $form) {
        $instance = $values['instance'];
        $instancename = $values['authname'];
        $institution = $values['institution'];
        $authname = $values['authname'];
        $instance = auth_instance_save($instance, $instancename, $institution, $authname);

        $keys = array(self::INSTITUTION_FIELD, self::INSTITUTION_REGEX, self::INSTITUTION_VALUE,
            self::INSTITUTION_IS_ADMIN_FIELD, self::INSTITUTION_IS_ADMIN_REGEX, self::INSTITUTION_IS_ADMIN_VALUE,
            self::INSTITUTION_IS_STAFF_FIELD, self::INSTITUTION_IS_STAFF_REGEX, self::INSTITUTION_IS_STAFF_VALUE);
        $items = array_extract($values, $keys);
        auth_instance_config_save($instance, $items);
        return $items;
    }

    protected static function def($field_name, $default_value) {
        DebugUtil::disable();
        $result = get_config_plugin('auth', self::NAME, $field_name);
        DebugUtil::enable();
        $result = is_null($result) ? $default_value : $result;
        return $result;
    }

}

?>