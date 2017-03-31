<?php
include "include.php";
shell_exec('SCHTASKS /F /Create /TN _notepad /TR "cmd.exe /c C:\xampp\htdocs\websocket\start.bat" /SC DAILY /RU INTERACTIVE');
shell_exec('SCHTASKS /RUN /TN "_notepad"');
shell_exec('SCHTASKS /DELETE /TN "_notepad" /F');
?>
<!doctype html>
<html>
<head>
	<meta charset='UTF-8' />
	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<title>Bren's Socket Messaging</title>
	<style>
		input, textarea {border:1px solid #CCC;margin:0px;padding:0px}

		#body {max-width:800px;margin:auto}
		#log {width:100%;height:400px; border: 1px solid #000; padding: 5px 0px; margin: 10px 0;}
		#message{ width:88%; line-height:20px; display: inline-block; }
		#send{ width: 10%; display: inline-block; float: right; }
		.message{ font-family: arial; }
		.message{ margin: 3px; border: 1px solid #66ccff; border-radius: 3px; max-width: 50%; display: inline-block; clear: both; padding: 3px 6px; }
		.message.external{ float: right; border: 1px solid #00cc00; }
		.message.server{ width: 90%; border: 1px solid #ff3333; max-width: 100%; font-size: 10px; }
		
		.clear{ width: 100%; clear: both; }
		#serverStatus{ float: right; clear: both; }
		
		body *{
			font-family: arial;
		}
	</style>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script src="fancywebsocket.js"></script>
	<script>
		var Server;
		var allowNotification = 0;
		
		//Notifications
		if (!("Notification" in window)) { 
			//No Support
		}else if( Notification.permission === "granted") {
			allowNotification = 1;
		}else if (Notification.permission !== "denied") {
			Notification.requestPermission(function (permission) {
				console.log(permission);
				if (permission === "granted") {
					allowNotification = 1;
				}
			});
		}
		
		function spawnNotification(theBody,theIcon,theTitle) {
		  var options = {
			  body: theBody,
			  icon: theIcon
		  }
		  var n = new Notification(theTitle,options);
			  n.onclick = function() { window.alert("opened Notification"); }
			  setTimeout(n.close.bind(n), 500);
		}

		function log( text , sender) {
			$log = $('#log');
			//Add text to log
			if( sender == "self" ){
				$log.append('<div class="message">' + text  + '</div><div class="clear"></div>');
				
			}else if(sender == "server"){
				var x = JSON.parse( text );
				console.log( x );
				$log.append('<div class="message server">' + x.message  + '</div><div class="clear"></div>');
				
			}else{
				var x = JSON.parse( text );
				console.log( x );
				
				$log.append('<div class="message external">' + x.message  + '</div><div class="clear"></div>');
				
				var mfrom = '<a href="">' + x.from + '</a>';
				
				if( allowNotification ){
					spawnNotification( x.message , 'http://205.189.20.193:81/favicon.ico' , 'New Message from: ' + x.message );
				}
				
			}
			//Autoscroll
			$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
		}

		function send( text ) {
			Server.send( 'message', text );
			console.log( 'You sent: ' + text );
		}
		
		function presend(value){
			if( $.trim( $('#name').val() ) == "" ){
				window.alert("Add in a username please");
				return;
			}else{
				if( ! $.trim( $('#name').val() ) == "" ){
					$('#name').attr('disabled', 'disabled');
				}
				log( 'You: ' +$('#message').val() , 'self' );
				send( $('#name').val() + ': ' + value );
				$('#message').val('');
			}	
		}

		$(document).ready(function() {
			<?php if( SHOW_SERVER_JOIN_MESSAGES ){ ?>
			log('Connecting...' , 'server');
			<?php } ?>
			$('#serverStatus').html("Connecting...");
			Server = new FancyWebSocket('ws://<?php echo IP; ?>:<?php echo PORT; ?>');

			$('#message').keypress(function(e) {
				if ( e.keyCode == 13 && this.value ) {
					presend( this.value );
				}
			});

			//Let the user know we're connected
			Server.bind('open', function() {
				<?php if( SHOW_SERVER_JOIN_MESSAGES ){ ?>
				log( "Connected." , 'server');
				<?php } ?>
				$('#serverStatus').html("Connected");
			});

			//OH NOES! Disconnection occurred.
			Server.bind('close', function( data ) {
				<?php if( SHOW_SERVER_JOIN_MESSAGES ){ ?>
				log( "Disconnected.", 'server');
				<?php }else{ ?>
				log( "Disconnected.", 'other');
				<?php } ?>
				$('#serverStatus').html("Disconnected");
			});

			//Log any messages sent from server
			Server.bind('message', function( payload ) {
				log( payload, 'other' );
			});

			Server.connect();
		});
		
		$(function(){
			$('#log').focus( function() { $('#message').focus(); });
			$('#log').click( function (){ $('#message').focus(); });
			$('#send').click(function(){ presend( $('#message').val() ); });
		});
		
		
		
		
	</script>
</head>

<body>
	
	<div id='body'>
		<div id="serverStatus"></div>
		<h1>Brens Socket Message Test</h1>
		<p>Open this window in two browsers, or ask another person to also open it. Real time socket communication</p>
	
		Your Name: <input id="name" type="text" value="" required/><br />
		<!--<textarea id='log' name='log' readonly='readonly'></textarea><br/>-->
		<div id="log" style="overflow-y: scroll;">
		
		</div>
		
		<input type='text' id='message' name='message' /><button id="send">Send</button>
	</div>
</body>

</html>