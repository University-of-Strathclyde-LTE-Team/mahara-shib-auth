<?php

/**
 * Empty registration. That is one that does nothing.
 * 
 * @copyright (c) 2010 University of Geneva 
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class empty_registration extends registration_base {

    public static function name() {
        return '(' . get_string(__CLASS__, 'auth.shibboleth') . ')';
    }

    public function user_confirmation() {
        return '';
    }

    public function administrator_confirmation() {
        return '';
    }

    public function registration_data() {
        return new StdClass();
    }

    public function is_valid() {
        return true;
    }

    public function display() {
        return false;
    }

}