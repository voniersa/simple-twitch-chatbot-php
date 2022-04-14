<?php
$server = "irc.twitch.tv";
$port = 6667;
$nickname = "xxxxxx"; //enter your nickname here
$password = "oauth:xxxxxxx"; //enter authentication here: https://twitchapps.com/tmi
$channels = array("#xxxxxx", "#xxxxxx"); //enter twitch channels here

$fp = fsockopen($server, $port, $errorCode, $errorMessage);

if(!$fp)
{
    echo "Error: $errorCode - $errorMessage\n"; exit;
}

fwrite($fp, "PASS ".$password."\r\n");
fwrite($fp, "NICK ".$nickname."\r\n");

$read = "";

while(!preg_match("/:\S+ 376 \S+ :.*/i", $read))
{
    $read = fgets($fp);
}

foreach($channels as $num => $chan)
{
    fwrite($fp, "JOIN $chan\r\n");
}

echo "Connected!\n";

while(TRUE)
{
    $read = fgets($fp);
    
    if(preg_match("/:(\S+)!\S+@\S+ JOIN (#\S+)/i", $read, $match))
    {
        userJoined($match[1], $match[2]);
    }
    
    if(preg_match("/:(\S+)!\S+@\S+ PART (#\S+)/i", $read, $match))
    {
        userParted($match[1], $match[2]);
    }
    
    if(preg_match("/:(\S+)!\S+@\S+ PRIVMSG (#\S+) :(.*)/i", $read, $match))
    {
        messageSent($match[1], $match[2], substr($match[3], 0, -1));
    }
    
    if(preg_match("/:jtv!jtv@\S+ PRIVMSG $nickname :(\S+)/i", $read, $match))
    {
        jtvError($match[1]);
    }
    
    if(preg_match("/PING :(.*)/i", $read, $match))
    {
        fwrite($fp, "PONG :$match[1]\r\n");
    }
}

function userJoined($nickname, $chan)
{
    global $users;
    $users[$chan][] = $nickname;
    echo "$nickname joined {$chan}.\n";
}

function userParted($nickname, $chan)
{
    global $users;
    $num = array_search($nickname, $users[$chan]);
    if($num !== FALSE)
    {
        unset($users[$chan][$num]);
    }
    echo "$nickname parted {$chan}.\n";
}

function messageSent($nickname, $chan, $msg)
{
	global $fp, $users;
	echo "$chan : <$nickname> $msg\n";
    
	if($msg === "!test")
    	{
        	$responseMessage = "Test response.";
        	echo "$chan : <$nickname> $responseMessage\n";
		fwrite($fp, "PRIVMSG $chan :$responseMessage\r\n");
	}
}

function jtvError($msg)
{
    echo "Message from jtv: $msg\n";
}
