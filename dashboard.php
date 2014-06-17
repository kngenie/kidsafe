<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

dashboard.php - The dashboard for managing kidsafe
**/


/*
This file is part of kidsafe.

kidsafe is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

kidsafe is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with kidsafe.  If not, see <http://www.gnu.org/licenses/>.
*/

include ('kidsafe-config.php');		// configuration (eg. mysql login)


// autoload any classes as required
function __autoload($class_name) 
{
    include 'inc/'.$class_name.'.php';
}


/*** Connect to database ***/
$db = new Database($dbsettings);
$kdb = new KidsafeDB($db);

if ($db->getStatus() != 1) {die ("Unable to connect to the database");}

// used to set messages to provide to the user (eg. 'proxy not disabled for local network');
// including <br> on the end of each message will keep the messages separate for the user
$user_messages = '';




/** Check for login - or redirect to login.php **/
$session = new DashboardSession();
// are we logged in already?
if ($session->getUsername() == '') 
{
	//If not redirect to login page - then redirect here
	header("Location: dashboardlogin.php?redirect=dashboard.php");
	exit (0);
}

$parms = new Parameters();
// valid messages
// newpass, nopermission, parameter
if ($parms->getParm('message') == 'newpass')
{
	$user_messages .= "Password successfully changed\n";
}
elseif ($parms->getParm('message') == 'nopermission')
{
	$user_messages .= "Insufficient permission\n";
}
elseif ($parms->getParm('message') == 'parameter')
{
	$user_messages .= "Missing or invalid parameter\n";
}

// create user object
$user = $kdb->getUserUsername($session->getUsername());
// check we have valid user
if ($user == null) 
{
	header("Location: dashboardlogin.php?redirect=dashboard.php&message=notuser");
	exit (0);
}

// Username used to display back to user
$username = $user->getUsername();

// don't need to be admin - but limited features for normal users (eg. change password)


// generate relevant html to embed within page
$html_sections = "<ul>\n";

// some available to everyone - others only if $user->isAdmin() or $user->isSupervisor()

$html_sections .= "<li><a href=\"password.php\">Change password</a></li>\n";
if ($user->isAdmin() || $user->isSupervisor())
{
	$html_sections .= "<li><a href=\"listrules.php\">List rules</a></li>\n";
	$html_sections .= "<li><a href=\"listusers.php\">List users</a></li>\n";
	$html_sections .= "<li><a href=\"viewlog.php\">Log viewer</a></li>\n";
}
	
$html_sections .= "</ul>\n";


print <<< EOT
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Kidsafe dashboard</title>
<link href="kidsafe.css" rel="stylesheet" type="text/css">
</head>
<body>
<h1>Kidsafe dashboard</h1>

<p style="float:right"><a href="dashboardlogout.php">Logout $username</a></p>

<p>$user_messages</p>

<div id="intro">
	<p>Kidsafe configuration dashboard</p>
	$html_sections
</div>


</div>
</body>
</html>
EOT;
?>



