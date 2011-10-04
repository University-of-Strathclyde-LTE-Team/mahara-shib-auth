<?php

/**
 * Base class for registration's objects.
 * A registration's object is responsible to capture additional data, not provided by Mahara, but required by the registration's process.
 * See course_registation for a concrete example.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class registration_base {

    /**
     * Name of the registration's method.
     */
    public static function name() {
        return get_string(__CLASS__, 'auth.shibboleth');
    }

    protected $user_data;
    protected $institution_data;

    /**
     * Initialize object with required data.
     * @param $user_data			user's data
     * @param $institution_data		institution user's data
     */
    public function init($user_data, $institution_data) {
        $this->user_data = $user_data;
        $this->institution_data = $institution_data;
    }

    /**
     * Returns user's data with key $name.
     * @param string $name
     */
    public function user_data($name) {
        return $this->user_data->$name;
    }

    /**
     * Returns institution's data with key $name.
     * @param $name
     */
    public function institution_data($name) {
        return $this->institution_data->$name;
    }

    /**
     * Returns the message displayed to the end user upon successful registration.
     */
    public function user_confirmation() {
        return '';
    }

    /**
     * Returns the message send to the administrators of the user.
     */
    public function administrator_confirmation() {
        return '';
    }

    /**
     * Returns data captured by the registration's object
     */
    public function registration_data() {
        return new StdClass();
    }

    /**
     * Returns true if captured data are valid and if registration can process forward.
     */
    public function is_valid() {
        return false;
    }

    /**
     * Dispaly the registration's form.
     */
    public function display() {
        return false;
    }

    /**
     * Process registration. By default email administrators.
     */
    public function process($usr=null) {
        if (is_null($usr)) {
            global $USER;
            $usr = $USER;
        }

        $message = $this->administrator_confirmation();
        if (empty($message)) {
            return false;
        }
        $admins = array();
        if ($this->notify_institution_administrators()) {
            $admins = get_user_institution_administrators($usr);
        }
        if ($this->notify_site_administrators()) {
            $admins = array_merge($admins, get_site_administrators());
        }
        if (empty($admins)) {
            return false;
        }
        $name = $this->user_data('firstname') . ' ' . $this->user_data('lastname');
        $subject = get_string('mahara_account_creation_request_for', 'auth.shibboleth', $name);
        $this->send_message($admins, $subject, $message);
        return true;
    }

    /**
     * Send an email message.
     *
     * @param array $users		users to send the email to
     * @param string $subject	email's subject
     * @param string $text		email's text
     */
    public function send_message($users, $subject, $text) {
        $root = get_config('docroot');
        $plugin_path = "$root/notification/email/lib.php";
        if (!file_exists($plugin_path)) {
            return false;
        }
        require_once($plugin_path);

        $html = "<html><body>$text</body></html>";

        //ensure we send the message only once to each user
        $targets = array();
        foreach ($users as $user) {
            $u = new User();
            $u->find_by_id($user);
            $targets[strtolower($u->get('email'))] = $u;
        }

        foreach ($targets as $user) {
            $this->email_user($user, null, $subject, '', $html, null);
        }
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
     * If true notify site administators of new accounts' creation.
     */
    public function notify_site_administrators() {
        return $this->config(PluginAuthShibboleth::NOTIFY_SITE_ADMIN, true);
    }

    /**
     * If true notfiy institution adminstrators of new accounts' creation.
     */
    public function notify_institution_administrators() {
        return $this->config(PluginAuthShibboleth::NOTIFY_INSTITUTION_ADMIN, true);
    }

    /**
     * Copy of Mahara's email_user function. There is a when sending html/multicontent messages.
     * @param $userto
     * @param $userfrom
     * @param $subject
     * @param $messagetext
     * @param $messagehtml
     * @param $customheaders
     */
    protected function email_user($userto, $userfrom, $subject, $messagetext, $messagehtml='', $customheaders=null) {
        global $IDPJUMPURL;
        static $mnetjumps = array();

        if (!get_config('sendemail')) {
            // You can entirely disable Mahara from sending any e-mail via the
            // 'sendemail' configuration variable
            return true;
        }

        if (empty($userto)) {
            throw new InvalidArgumentException("empty user given to email_user");
        }

        // If the user is a remote xmlrpc user, trawl through the email text for URLs
        // to our wwwroot and modify the url to direct the user's browser to login at
        // their home site before hitting the link on this site
        if (!empty($userto->mnethostwwwroot) && !empty($userto->mnethostapp)) {
            require_once(get_config('docroot') . 'auth/xmlrpc/lib.php');

            // Form the request url to hit the idp's jump.php
            if (isset($mnetjumps[$userto->mnethostwwwroot])) {
                $IDPJUMPURL = $mnetjumps[$userto->mnethostwwwroot];
            } else {
                $mnetjumps[$userto->mnethostwwwroot] = $IDPJUMPURL = PluginAuthXmlrpc::get_jump_url_prefix($userto->mnethostwwwroot, $userto->mnethostapp);
            }

            $wwwroot = get_config('wwwroot');
            $messagetext = preg_replace_callback('%(' . $wwwroot . '([\w_:\?=#&@/;.~-]*))%',
                            'localurl_to_jumpurl',
                            $messagetext);
            $messagehtml = preg_replace_callback('%href=["\'`](' . $wwwroot . '([\w_:\?=#&@/;.~-]*))["\'`]%',
                            'localurl_to_jumpurl',
                            $messagehtml);
        }


        require_once('phpmailer/class.phpmailer.php');

        $mail = new phpmailer();

        // Leaving this commented out - there's no reason for people to know this
        //$mail->Version = 'Mahara ' . get_config('release');
        $mail->PluginDir = get_config('libroot') . 'phpmailer/';

        $mail->CharSet = 'UTF-8';

        $smtphosts = get_config('smtphosts');
        if ($smtphosts == 'qmail') {
            // use Qmail system
            $mail->IsQmail();
        } else if (empty($smtphosts)) {
            // use PHP mail() = sendmail
            $mail->IsMail();
        } else {
            $mail->IsSMTP();
            // use SMTP directly
            $mail->Host = get_config('smtphosts');
            if (get_config('smtpuser')) {
                // Use SMTP authentication
                $mail->SMTPAuth = true;
                $mail->Username = get_config('smtpuser');
                $mail->Password = get_config('smtppass');
            }
        }

        if (empty($userfrom) || $userfrom->email == get_config('noreplyaddress')) {
            $mail->Sender = get_config('noreplyaddress');
            $mail->From = $mail->Sender;
            $mail->FromName = (isset($userfrom->id)) ? display_name($userfrom, $userto) : get_config('sitename');
            $customheaders[] = 'Precedence: Bulk'; // Try to avoid pesky out of office responses
            //$messagetext .= "\n\n" . get_string('pleasedonotreplytothismessage') . "\n";
            if ($messagehtml) {
                //$messagehtml .= "<p>" . get_string('pleasedonotreplytothismessage') . "</p>\n";
            }
        } else {
            $mail->Sender = $userfrom->email;
            $mail->From = $mail->Sender;
            $mail->FromName = display_name($userfrom, $userto);
        }
        $replytoset = false;
        if (!empty($customheaders) && is_array($customheaders)) {
            foreach ($customheaders as $customheader) {
                $mail->AddCustomHeader($customheader);
                if (0 === stripos($customheader, 'reply-to')) {
                    $replytoset = true;
                }
            }
        }

        if (!$replytoset) {
            $mail->AddReplyTo($mail->From, $mail->FromName);
        }

        $mail->Subject = substr(stripslashes($subject), 0, 900);

        $mail->Username = 'opprecht';
        $mail->Password = 'Quertz_123';

        if ($to = get_config('sendallemailto')) {
            // Admins can configure the system to send all email to a given address
            // instead of whoever would receive it, useful for debugging.
            $mail->addAddress($to);
            $notice = get_string('debugemail', 'mahara', display_name($userto, $userto), $userto->email);
            $messagetext = $notice . "\n\n" . $messagetext;
            if ($messagehtml) {
                $messagehtml = '<p>' . hsc($notice) . '</p>' . $messagehtml;
            }
            $usertoname = display_name($userto, $userto, true) . ' (' . get_string('divertingemailto', 'mahara', $to) . ')';
        } else {
            $usertoname = display_name($userto, $userto);
            $mail->AddAddress($userto->email, $usertoname);
        }

        $mail->WordWrap = 79;

        if ($messagehtml) {
            $mail->IsHTML(true);
            $mail->Encoding = 'quoted-printable';
            $mail->Body = $messagehtml;
            $mail->AltBody = $messagetext;
        } else {
            $mail->IsHTML(false);
            $mail->Body = $messagetext;
        }

        $mail->AltBody = ''; //<-- BUG with multipart/html messages
        if ($mail->Send()) {
            return true;
        } else {
            return false;
        }
    }

}