<?php

/**
 * Course registration. Requires two additional fields:
 *
 * 		- course's name
 * 		- request's reason
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class course_registration extends registration_base {

    /**
     * Name of the registration's method.
     */
    public static function name() {
        return get_string(__CLASS__, 'auth.shibboleth');
    }

    public function user_confirmation() {
        return get_string('your_request_has_been_sent_message', 'auth.shibboleth');
    }

    public function administrator_confirmation() {
        $result = '';
        $data = $this->registration_data();

        $title = 'MAHARA: ' . get_string('creation_of_account', 'auth.shibboleth');
        $username_title = get_string('username', 'auth.shibboleth');
        $username = $data->username;
        $firstname_title = get_string('firstname', 'auth.shibboleth');
        $firstname = $data->firstname;
        $lastname_title = get_string('lastname', 'auth.shibboleth');
        $lastname = $data->lastname;
        $email_title = get_string('email', 'auth.shibboleth');
        $email = $data->email;
        $course_title = get_string('course', 'auth.shibboleth');
        $course = $data->course;
        $reason_title = get_string('reason', 'auth.shibboleth');
        $reason = format_whitespace($data->reason);
        $date_title = get_string('date', 'auth.shibboleth');
        $date = date('Y.m.d H:i', time());

        $user = new User();
        $user->find_by_username($this->user_data('username'));
        $user_id = $user->get('id');

        $wwwroot = get_config('wwwroot');
        global $cfg;
        $url = $wwwroot . 'admin/users/edit.php?id=' . $user_id;

        //$result .= '<img alt="Mahara" src="'.$wwwroot.'theme/default/static/images/site-logo.png"></br>';


        $result .= '<p><h2 style="color:#4C711D;">' . $title . '</h2>';
        $result .= '<table><tbody><tr><td>';
        $result .= "<b class=\"width:220px\">$username_title:</b></td><td>" . '<a href="' . $url . '">' . $username . '</a>';
        $result .='</td></tr><tr><td>';
        $result .= "<b class=\"width:220px\">$firstname_title:</b></td><td>$firstname";
        $result .='</td></tr><tr><td>';
        $result .= "<b class=\"width:220px\">$lastname_title:</b></td><td>$lastname";
        $result .='</td></tr><tr><td>';
        $result .= "<b class=\"width:220px\">$email_title:</b></td><td>$email";
        $result .='</td></tr><tr><td>';
        $result .= "<b class=\"width:220px\">$course_title:</b></td><td>$course";
        $result .='</td></tr><tr><td>';
        $result .= "<b class=\"width:220px\">$reason_title:</b></td><td>$reason";
        $result .='</td></tr><tr><td>';
        $result .= "<b class=\"width:220px\">$date_title:</b></td><td>$date";
        $result .='</td></tr>';
        $result .= '</tbody></table></p>';

        //$result .= '<div style="background-color:#939393;color:white;height:25px:font-size:smaller;width:100%"><a href="'.$wwwroot.'terms.php">'.get_string('termsandconditions').'</a> |';
        //$result .= '<a href="'.$wwwroot.'privacy.php">'.get_string('privacystatement').'</a> |';
        //$result .= '<a href="'.$wwwroot.'about.php">'.get_string('about').'</a> |';
        //$result .= '<a href="'.$wwwroot.'contact.php">'.get_string('contactus').'</a></div>';



        return $result;
    }

    public function registration_data() {
        global $_REQUEST;
        $result = new StdClass();
        $result->username = isset($_REQUEST['user_username']) ? $_REQUEST['user_username'] : $this->user_data('username');
        $result->firstname = isset($_REQUEST['user_firstname']) ? $_REQUEST['user_firstname'] : $this->user_data('firstname');
        $result->lastname = isset($_REQUEST['user_lastname']) ? $_REQUEST['user_lastname'] : $this->user_data('lastname');
        $result->email = isset($_REQUEST['user_email']) ? $_REQUEST['user_email'] : $this->user_data('email');
        $result->course = isset($_REQUEST['user_course']) ? $_REQUEST['user_course'] : '';
        $result->reason = isset($_REQUEST['user_reason']) ? $_REQUEST['user_reason'] : '';
        $result->action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        return $result;
    }

    public function is_valid() {
        $data = $this->registration_data();

        $result = !(empty($data->username) ||
                empty($data->firstname) ||
                empty($data->lastname) ||
                empty($data->email) ||
                empty($data->course) ||
                empty($data->reason));
        return $result;
    }

    public function display() {
        $data = $this->registration_data();
        $username_title = get_string('username', 'auth.shibboleth');
        $username = $data->username;
        $firstname_title = get_string('firstname', 'auth.shibboleth');
        $firstname = $data->firstname;
        $lastname_title = get_string('lastname', 'auth.shibboleth');
        $lastname = $data->lastname;
        $email_title = get_string('email', 'auth.shibboleth');
        $email = $data->email;
        $course_title = get_string('course', 'auth.shibboleth');
        $course = $data->course;
        $reason_title = get_string('reason', 'auth.shibboleth');
        $reason = $data->reason;
        $send_request_title = get_string('send_request', 'auth.shibboleth');
        $required = $data->action == 'register_new_user' ? '<span class="requiredmarker"> ' . get_string('this_field_is_required', 'auth.shibboleth') . '</span>' : '';

        $sep = '<span class="requiredmarker"> *</span></th><td>';

        $form = '';
        $form .= '<form id="new_user" method="post">';
        $form .= '<input type="hidden" name="action" value="register_new_user" />';

        //$form .= '<input type="hidden" name="shibboleth_fields" value="'.$shibboleth_fields.'" />';

        $form .= '<table><tbody><tr class="required"><th>';
        $form .= '<label for="user_username" class="required">' . $username_title . '</label>' . $sep . '<input type="text" class="text" name="user_username" size="50" value="' . $username . '" ' . (empty($username) ? '' : 'readonly="readonly"') . '/>' . (empty($username) ? $required : '');
        $form .= '</td></tr><tr class="required"><th>';
        $form .= '<label for="user_firstname" class="required">' . $firstname_title . '</label>' . $sep . '<input type="text" class="text" name="user_firstname" size="50" value="' . $firstname . '" ' . (empty($firstname) ? '' : 'readonly="readonly"') . '/>' . (empty($firstname) ? $required : '');
        $form .= '</td></tr><tr class="required"><th>';
        $form .= '<label for="user_lastname">' . $lastname_title . '</label>' . $sep . '<input type="text" class="text" name="user_lastname" size="50" value="' . $lastname . '" ' . (empty($lastname) ? '' : 'readonly="readonly"') . '/>' . (empty($lastname) ? $required : '');
        $form .= '</td></tr><tr class="required"><th>';
        $form .= '<label for="user_email">' . $email_title . '</label>' . $sep . '<input type="text" class="text" name="user_email" size="50" value="' . $email . '" ' . (empty($email) ? '' : 'readonly="readonly"') . '/>' . (empty($email) ? $required : '');
        $form .= '</td></tr><tr class="required"><th>';
        $form .= '<label for="user_course">' . $course_title . '</label>' . $sep . '<input type="text" class="text" name="user_course" size="50" value="' . $course . '" />' . (empty($course) ? $required : '');
        $form .= '</td></tr><tr class="required"><th>';
        $form .= '<label for="user_reason">' . $reason_title . '</label>' . $sep . '<textarea class="text" name="user_reason" cols="50" rows="4">' . $reason . '</textarea>' . (empty($reason) ? $required : '');
        $form .= '</td></tr><tr><th>';
        $form .= '</th><td><input type="submit" class="submit" name="submit_new_user" value="' . $send_request_title . '"/>';
        $form .= '</td></tr></tbody></table>';
        $form .= '</form>';
        $smarty = smarty();
        $smarty->assign('form', $form);
        $smarty->display('requiredfields.tpl');
    }

}