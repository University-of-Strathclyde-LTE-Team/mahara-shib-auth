<?php
/*
 * Landing page for loging in Mahara through Shibboleth. 
 * 
 * Note that the current file do not provide a user interface to enter login information.
 * It relies on the Web Server authentication mecanism to do this part of the job. 
 *
 * Access to this file must be secured at the Web Server level using the Shibboleth module.
 * The Shibboleth security module is responsible to redirect the user to a single sign on page.
 * If the user is authentified the module provides user data on through the $_SERVER variable.
 * This page is never reached if the user is not authentified.
 *
 */

define('INTERNAL', 1);
define('PUBLIC', 1);

require_once dirname(__FILE__) .'/../../../init.php';
require_once dirname(__FILE__) .'/../lib.php';

//@for test remove !!!
//$_SERVER['Shib-SwissEP-UniqueID'] = 'usr' . time() ;
//$_SERVER['Shib-InetOrgPerson-givenName'] = 'Joe';
//$_SERVER['Shib-Person-surname'] = 'Smith';

$manager = new login_manager();
$manager->process();



?>