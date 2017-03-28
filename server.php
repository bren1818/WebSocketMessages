<?php
include "include.php";
// prevent the server from timing out

$lock_file = fopen('server.pid', 'c');
$got_lock = flock($lock_file, LOCK_EX | LOCK_NB, $wouldblock);
if ($lock_file === false || (!$got_lock && !$wouldblock)) {
    throw new Exception(
        "Unexpected error opening or locking lock file. Perhaps you " .
        "don't  have permission to write to the lock file or its " .
        "containing directory?"
    );
}
else if (!$got_lock && $wouldblock) {
    exit("Another instance is already running; terminating.\n");
}

// Lock acquired; let's write our PID to the lock file for the convenience
// of humans who may wish to terminate the script.
ftruncate($lock_file, 0);
fwrite($lock_file, getmypid() . "\n");


set_time_limit(0);

// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.PHPWebSocket.php';

// when a client sends data to the server
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	// check if message length is 0
	if ($messageLength == 0) {
		$Server->wsClose($clientID);
		return;
	}

	//The speaker is the only person in the room. Don't let them feel lonely.
	if ( sizeof($Server->wsClients) == 1 ){
		$Server->wsSend($clientID, "There isn't anyone else in the room");
	}else{
		//Send the message to everyone but the person who said it
		foreach ( $Server->wsClients as $id => $client ){
			//not the server ip
			if( ($ip != IP) && !ALLOW_NON_SERVER_MESSAGES ){
				//reply to just the client
				if ( $id = $clientID ){
					$Server->wsSend($id, "You cannot send a message");
				}
			}else{
				//server Ip, broadcast the message.
				if ( $id != $clientID ){
					if( SHOW_REMOTE_IP_INFO ){
						$Server->wsSend($id, "$clientID ($ip) $message");
					}else{
						$Server->wsSend($id, "$message");
					}
				}
			}
		}
	}
}

// when a client connects
function wsOnOpen($clientID)
{
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has connected." );

	//Send a join notice to everyone but the person who joined
	if( SHOW_SERVER_REMOTE_JOIN_MESSAGES ){
		foreach ( $Server->wsClients as $id => $client ){
			if ( $id != $clientID ){
				if( SHOW_REMOTE_IP_INFO ){
					$Server->wsSend($id, "Visitor $clientID ($ip) has joined the room.");
				}else{
					$Server->wsSend($id, "Visitor has joined the room.");
				}
			}
		}
	}
}

// when a client closes or lost connection
function wsOnClose($clientID, $status) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has disconnected." );

	if( SHOW_SERVER_REMOTE_LEAVE_MESSAGES ){
		//Send a user left notice to everyone in the room
		foreach ( $Server->wsClients as $id => $client ){
			if( SHOW_REMOTE_IP_INFO ){
				$Server->wsSend($id, "Visitor $clientID ($ip) has left the room.");
			}else{
				$Server->wsSend($id, "Visitor has left the room.");
			}
		}
	}
}

// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer(IP, PORT);

ftruncate($lock_file, 0);
flock($lock_file, LOCK_UN);
exit;
?>