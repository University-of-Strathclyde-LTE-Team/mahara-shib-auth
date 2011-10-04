*************************************************************************************************

			Shibboleth Authentication Plugin for Mahara.

*************************************************************************************************

@copyright 	(c) 2010 University of Geneva
@license 	GNU General Public License - http://www.gnu.org/copyleft/gpl.html
@author 	laurent.opprecht@unige.ch

Requires
--------

	Shibboleth has to be installed on the web server in order to provide authentication
	
How it works
------------
The Shibboleth plugin provides an alternate login url which is mahara/auth/shibboleth/login/login.php. 
Authentication is not provided by the Shibboleth plugin but by the web server Shibboleth authentication module.
For this to work the Shibboleth plugin login page has to be protected at the web server level by a security directive in the configuration file.
When a user tries to access the login page he is redirected by the Shibboleth module to the identity provider single sign on page.
On successful authentication the Shibboleth module grant access to the page with the $_SERVER variable containing user data.
As a result, the login page is reached after authentication took place and do not contain any user interface.
At this point, the Shibboleth plugin reads the user data from the $_SERVER variable, either creates or updates the Mahara user profile with shibboleth data and log him in.
If login is not possible. For example because required data are missing. The plugin redirect the user to the front page without providing access.
Note that access will not be granted if the login page has not been secured.  
  
Technical data
--------------
Note that the AuthShibboleth class is provided for compatibility with the Mahara architecture but do not peforms any work since authentication is 
provided by the web server. The AuthShibboleth class always returns false on authentication requests

Plugin directories:

	- lang: contains user interface/messages translations
	- login: contains the login page
	- admin: provide access to the standard Mahara login procedure for site administrators. This is a failover in case other access methods fail.
	- util: generic libraries not related either to Mahara or Shibboleth
	- manager: 	contain the login manager and helping classes. The login manager is responsible to coordinate the various activities/user responses
				that happens during login. 
				Contains too the various registration methods for new users. 
	
Installation
------------
Installation is a multi steps process:

	1. Install and configure the Shibboleth Mahara plugin
	2. Install and configure the Shibboleth software - if not already present
	3. Protect the Shibboth Plugin login page: mahara/auth/shibboleth/login/login.php with a web server configuration entry
	4. Modify the user inteface to provide a link to the login page 

1. Plugin installation

	- Copy the "auth" and "switch" directories contained in the package under your mahara root  
		
	- Enable the plugin: 
		Log in Mahara as an administrator. 
		Go to "Site Administration" > "Administer Extentions"
		Enable the Shibboleth plugin
		Refresh the screen to see the configure plugin link
		
	-  Configure the plugin
		In "Site Administration" > "Administer Extentions" click on "config" for Shibboleth
		Enter the Shibboleth fields' name for each field you want/must to map to - username, first name, etc.
		For Administration and Staff membership enters the both the Shibboleth field's name and the value you want to compare to.
		Tick the checkbox if you want the value to be interpreted as a regular expression.
		
	- Configure each institution you want to authenticate with the Shibboleth plugin
		in "Site Administration" > "Manage Institutions" click on "Edit" in turn for each institution you want to configure
		under "Authentication plugin" select "Shibboleth" and click "Add"
		enter Shibboleth fields' name and values as required
		click submit

	- If needed copy the non english translations contained in the plugin lang folder to the mahara lang folder. That is copy

            mahara/auth/shibboleth/lang/fr-utf8

            to

            maharadata/langpacks/auth/shibboleth/lang/fr-utf8

          If you want to use the french translation.

2. Shibboleth installation and configuration
	Install Shibboleth on your web server. See http://shibboleth.internet2.edu/ for details on how to install Shibboleth
	
3. Protect the Mahara Shibboleth login page
	The Shibboleth login page is located in
	
		mahara/auth/shibboleth/login/login.php
		
	For Apache add a protection directive in the httpd.conf file or in the directory .httpaccess file. 
	To accept all users that have successfully loged in add the following directive:

	<Directory  /path_to_mahara/auth/shibboleth/login/login.php>
        AuthType shibboleth
        ShibRequireSession On
        require valid-user
	</Directory>
	
	You can have a look at
	
		http://www.switch.ch/aai/support/serviceproviders/sp-access-rules.html
	
	for additional examples. 

4. Modify user inteface to provide (a) link(s) to the login page
	You can modify the user interface in several ways:
		
		1. Modify the home page and add a link pointing mahara/auth/shibboleth/login/login.php
			Site Administration > Edit Site Pages 
			
		2. Add an public link(s) 
			Site Administration > Links and Resource Menu
			
		3. changes the theme to provide an alternative login
		
	A theme is provided for SWITCH AAI. This theme can be further customized or can serve as an 
	example.
	
	Copy the Switch theme directory to /mahara/theme/ 
	Go to
	 
		Site Administration > Site Options
		
	Set the "Switch" theme as the default theme.

	Check host name, sso and target in

		mahara/theme/switch/templates/sideblocks/login_switch_links_XXXX.tpl

	By default the theme is configure to take into account the host name and expects the pathes to be the defaults:

		mahara: 		hostname/mahara/
		shibboleth SSO: 	https://hostname/Shibboleth.sso/DS

	If mahara/shibboleth have been configured differently this needs to be updated accordingly. 
	For example if mahara is installed at the root the following lines in login_switch_links_XXXX.tpl must be updated.
	 
		$host = $_SERVER['HTTP_HOST'];
		$sso = "https://$host/Shibboleth.sso/DS";
		$target = 'http%3A%2F%2F' . $host .'%2Fmahara%2Fauth%2Fshibboleth%2Flogin%2Flogin.php';

	to

		$host = $_SERVER['HTTP_HOST'];
		$sso = "https://$host/Shibboleth.sso/DS";
		$target = 'http%3A%2F%2F' . $host .'%2Fauth%2Fshibboleth%2Flogin%2Flogin.php';

	
	If required, change/add URLs as needed. Go to 
	
			mahara/theme/switch/templates/sideblocks/login_switch_links_XXXX.tpl
			
	and modify the links to fit your needs. You can go to
		
			http://www.switch.ch/aai/support/serviceproviders/sp-compose-login-url.html
			
	to help you create/modify links.
	
	Note: the SWITCH page above helps you create links to specific identity providers.
	If you want to make use of the standard login procedure and do not want to provide direct access
	to home institutions you need only one link that points to
	
		mahara/auth/shibboleth/login/login.php 
	
	Additional configuration:
	
	1. Remove internal/mahara login
		The SWITCH theme provides two separate logins: internal login using the standard Mahara architecture
		and the Shibboleth login. If you want to remove the standard Mahara login box go to
		
			mahara/theme/switch/templates/sideblocks/login.tpl
			mahara/theme/switch/templates/login.tpl
		  
		and remove/comment the Mahara login parts.
		
		Note that the Shibboleth module provides an alternate login page to the standard Mahara login
		procedure at
		
			mahara/auth/shibboleth/admin/login.php
			
		This is intended to be used by administrator when the Shibboleth module is not available.
		If 
		
	2. Changes the parent theme.
		By default the Switch theme inherits its properties from the default theme. If you want to change that
		and inherit from another theme. Go to
	
			mahara/theme/switch/themeconfig.php
	
		and changes $theme->parent to the theme's name you want to inherit from.
	
	3. Add/changes links
		By default the Switch theme provide links to preconfigured entity provider. If you need to modify/add links
		Go to 
	
			mahara/theme/switch/templates/sideblocks/login_switch_links_XXXX.tpl
	
		and modify the links to fit your needs. You can go to
		
			http://www.switch.ch/aai/support/serviceproviders/sp-compose-login-url.html
			
		to help you create/modify links.

	4. Modify the registration method
		By default the system creates user accounts from the Shibboleth's fields. If additional data are
		required to process user registration you can specify a different registration method in 
			
			administer site > administer extentions > Shibboleth > Config

		The system ships with two predefined methods: empty and course registration. 
		The empty method does nothing. 
		The course registration method requests two additional fields from the new user:
		the course for which access is granted and the reason for requesting access. Those informations are emailed 
		to the institution and site administrators who are then responsible to grant access. Note that the course method does not 
		create inactive account by itself. You must set the "Activate new accounts" flag to false in plugin configuration for that.
		
		If additional methods are required you can add new classes in 
		
			mahara/auth/shibboleth/manager/registration/
		   





