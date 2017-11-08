<?php
/*
	Plugin Name: List Votes
	Plugin URI: 
	Plugin Description: Lists all votes made in your forum
	Plugin Version: 0.1
	Plugin Date: 2014-07-29
	Plugin Author: q2apro.com
	Plugin Author URI: http://www.q2apro.com/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: 

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html
	
*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}

// page
qa_register_plugin_module('page', 'q2apro-list-votes-page.php', 'q2apro_list_votes_page', 'q2apro List Votes Page');

// language file
qa_register_plugin_phrases('q2apro-list-votes-*.php', 'q2apro_list_votes_lang');

// widget
// qa_register_plugin_module('widget', 'qa-new-users-widget.php', 'qa_new_users_widget', 'New Users Widget');

// change default users page, add subnavigation "newest users"
// qa_register_plugin_layer('qa-new-users-layer.php', 'New-Users-Subnav');



/*
	Omit PHP closing tag to help avoid accidental output
*/