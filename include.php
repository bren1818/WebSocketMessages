<?php
	define('IP', '205.189.20.193');
	define('PORT', '9300');
	define('SHOW_SERVER_JOIN_MESSAGES', 0); // true or false
	define('SHOW_SERVER_REMOTE_JOIN_MESSAGES', 0 );
	define('SHOW_SERVER_REMOTE_LEAVE_MESSAGES', 1 );
	define('SHOW_REMOTE_IP_INFO', 1 );
	define('ALLOW_NON_SERVER_MESSAGES', 1 );
	define('PRIVATE_KEY', substr(str_shuffle(MD5(microtime())), 0, 20) ); //random string
?>