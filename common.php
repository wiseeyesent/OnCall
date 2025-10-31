<?php
namespace WiseEyesEnt\OnCall2;

require_once("OnCall2.conf.php");

\date_default_timezone_set('UTC');
$ONCALL_VERSION = "2.1.5.3";

if(DEBUG > 0) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}

function getEmployeeName($employee) {
	unset($name);
	if(isset($employee["nickname"]) && !empty($employee["nickname"])) {
		$name = $employee["nickname"]." ".$employee["last_name"];
	} else {
		$name = $employee["first_name"]." ".$employee["last_name"];
	}
	return $name;
}

function formatPhoneNumber(int $phone_number) {
	if(DEBUG > 2) {
		print("common.php: formatPhoneNumber($phone_number)<br/>\n");
	}
	if(empty($phone_number)) { return ""; }

	$retval = \str_pad((string)$phone_number, 10, "0", STR_PAD_LEFT);
	$retval = \sprintf("+1 (%s) %s-%s",
			\substr($retval, 0, 3),
			\substr($retval, 3, 3),
			\substr($retval, 6));
	if(DEBUG > 2) {
		print("common.php::formatPhoneNumber() retval = $retval<br/>\n");
	}
	return $retval;
}

// Returns an error string for displaying in the webview
function displayError($error_code=0) {
	$data = "<h1 class=\"error-msg\">ERROR! ";
	switch($error_code) {
	case 100:
		$data .= "Invalid Employee ID";
		break;
	case 101:
		$data .= "Employee Not Found";
		break;
	case 102:
		$data .= "Multiple Employees Found";
		break;
	case 120:
		$data .= "Employee Save Missing Required Data";
		break;
	case 121:
		$data .= "Employee Failed To Save";
		break;
	case 122:
		$data .= "Employee Save edit_log Failed";
		break;
	case 200:
		$data .= "Invalid Team ID Or Name";
		break;
	case 201:
		$data .= "Team Not Found";
		break;
	case 202:
		$data .= "Multiple Teams Found";
		break;
	case 210:
		$data .= "Invalid Team Employee ID, UserID, Or Name Provided";
		break;
	case 211:
		$data .= "Team Employee Not Found";
		break;
	case 212:
		$data .= "Multiple Matches For Team Employee";
		break;
	case 220:
		$data .= "Team Save Missing Required Data";
		break;
	case 221:
		$data .= "Team Failed To Save";
		break;
	case 222:
		$data .= "Team Save edit_log Failed";
		break;
	case 300:
		$data .= "Invalid Team Schedule ID Or Name";
		break;
	case 301:
		$data .= "Team Schedule Not Found";
		break;
	case 302:
		$data .= "Multiple Team Schedules Found";
		break;
	case 310:
		$data .= "Invalid Team Schedule Employee ID, UserID, Or Name Provided";
		break;
	case 311:
		$data .= "Team Schedule Employee Not Found";
		break;
	case 312:
		$data .= "Multiple Team Schedule Employees Found";
		break;
	case 320:
		$data .= "Team Schedule Save Missing Required Data";
		break;
	case 321:
		$data .= "Team Schedule Failed To Save";
		break;
	case 322:
		$data .= "Team Schedule Save edit_log Failed";
		break;
	default:
		$data .= "Unknown Error Received: $error_code";
	}
	$data .= "</h1>\n";
	return $data;
}

function teamSelect($pdo=null, int $teamid=0, array $teams=null) {
	if(DEBUG > 2) { print("common.php: teamSelect() SERVER = ".print_r($_SERVER, true)."<br/>\n"); }

	if(!empty($return_team)) { unset($return_team); }

	if(empty($pdo)) { $pdo = dbConnect(); }
	if(empty($teams)) { $teams = Team::getTeamNames($pdo); }
	if(DEBUG > 2) { print("common.php: teamSelect(): teams = ".print_r($teams, true)."<br/>\n"); }

	if(empty($teamid)) {
		$teamid = !empty($_GET['teamid']) ?
			(int) $_GET['teamid'] :
			(int) $teams[0]['id'];
	}
	if(DEBUG > 2) { print("common.php: teamSelect(): teamid = $teamid<br/>\n"); }

	$active = (isset($_GET['active'])) ? (bool) $_GET['active'] : true;
	$active_int = ($active) ? 1 : 0;

	if(!empty($data)) { unset($data); }
	if(!empty($team)) { unset($team); }

	$data = "\n<div class=\"float\" float=\"left\">\n"
		."<form action=\"".$_SERVER['REQUEST_URI']."\" method=\"get\">\n";
	if(isset($_GET['active'])) {
		$data .= "  <input type=\"hidden\" name=\"active\" value=\"$active_int\" />\n";
	}
	$data .= "  <label for=\"teamid\">On-Call Team:</label>\n"
		."  <select name=\"teamid\" id=\"teamid\" onchange=\"this.form.submit();\">\n";
	
	foreach ($teams as $team) {
		$data .= "    <option value=\"".$team['id']."\"";
		if($teamid == $team['id']) { $data .= " selected"; $return_team = $team; }
		$data .= ">".$team['name']."</option>\n";
	}
	
	$data .= "  </select>\n"
		."</form>\n"
		."</div>\n\n";
	if(empty($return_team)) { $return_team = $teams[0]; }
	return [$return_team, $data];
}
