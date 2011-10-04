<?php

/**
 * Authtentication class required by Mahara. Not used since authentication is performed by the web server.
 * Returns always false on authentication requests. 
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class AuthShibboleth extends Auth {

    public function __construct($id = null) {
        $this->type = 'shibboleth';
        if ($id) {
            $this->init($id);
        }
        return true;
    }

    public function can_auto_create_users() {
        return false;
    }

    public function request_user_authorise($attributes) {
        return false;
    }

    public function logout() {
        
    }

}

?>