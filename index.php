<?php
namespace WiseEyesEnt\OnCall2;

//spl_autoload_register(function ($class_name) {
//	include $class_name . '.php';
//});

include_once("./dbconn.php");
include_once("./employee.php");
require_once("./team.php");
require_once("./team_schedule.php");

include("./header.php");

if(empty($pdo)) { $pdo = dbConnect(); }
if(DEBUG > 1) { print("index.php: pdo = ".print_r($pdo, true)."<br/>\n"); }

unset($data);
$data = "<blockquote>\n"
	."  <h4>On-Call for Teams</h4>\n"
	."  <pre>NOTE: Search is by type:id\n"
	."TYPES) employee, schedule, team\n"
	."EXAMPLES)\n"
	."	employee:1, employee:id123, employee:Joshua Cripe\n"
	."	schedule:25364, schedule:11:2024-01-08, schedule:11:2024-01-08 00:00\n"
	."	team:1, team:team</pre>\n"
	."  <div style=\"text-align:right\"><em>last check: "
		.date('Y-m-d H:i')." UTC</em></div>\n"
	."</blockquote>\n"
	."<table>\n";

//Labels refers to the DB columns that we want to display on this page
unset($labels);
//$labels = $employee->getLabels();
$labels = array(
	"call_order" => "Call Order",
	"name" => "Name",
	"tel_audinet" => "Audinet",
	"tel_direct" => "Direct Dial",
	"tel_cell_corp" => "Primary Cell",
	"tel_cell_other" => "Other Cell",
	"email_corp" => "Corp Email",
	"email_page_corp" => "Text Message",
	"email_other" => "Other Email"
);
//$labels = Employee::LABELS;
if(DEBUG > 1) { print("index.php: labels = ".print_r($labels, true)."<br/>\n"); }

//All currently active teams
unset($teams);
//NOTE: Only returns list of [id, name]
// SQL: SELECT id,name FROM team WHERE active=TRUE
$teams = Team::getTeamNames($pdo=$pdo, $active=true);
if(DEBUG > 1) { print("index.php: teams = ".print_r($teams, true)."<br/>\n"); }

if($request_method == "POST") {
	if(DEBUG > 1) { print("index.php: POST = ".print_r($_POST, true)."<br/>\n"); }

	if(isset($_POST['q'])) {
		if(DEBUG > 1) { print("index.php::POST q = ".$_POST['q']."<br/>\n"); }

		$term = (string) $_POST['q'];
		$type = \substr($term, 0, \strpos($term, ":"));
		$id = \substr($term, (\strpos($term, ":")+1));
		if(DEBUG > 1) {
			print("index.php::POST.q term = $term<br/>\n");
			print("index.php::POST.q type = $type<br/>\n");
			print("index.php::POST.q id = $id<br/>\n");
		}

		switch($type) {
		case "employee":
			try {
				if(!empty((int)$id)) { $id = (int) $id; }
				else if(\preg_match("/^[a-zA-Z][0-9]+$/", $id)) {
					$id = (string) $id;
				} else { $id = Employee::getIdByName($pdo, $id); }
			} catch(\Exception $e) {
				print(displayError($e->getCode));
				die($e->getCode());
			}
			header("Location: contact.php?id=$id");
			break;
		case "schedule":
			if(\str_contains($id, ":")) {
				header("Location: schedule.php?name=$id");
			} else {
				header("Location: schedule.php?id=$id");
			}
			break;
		case "team":
			$id = (!empty((int)$id)) ? (int) $id : (string) $id;
			if(is_int($id)) {
				header("Location: teams.php?teamid=$id");
			} else {
				header("Location: teams.php?name=$id");
			}
			break;
		}
		unset($data);
		unset($id);
		unset($pdo);
		unset($request_method);
		unset($teams);
		unset($term);
		unset($type);
		exit();
	}//END q (Search)
}//END POST

$teams_last_key = \array_key_last($teams);
foreach ($teams as $teams_key => $team) {
	//print("index.php::teams team = ".print_r($team, true)."<br/>\n");
	//Current schedule for the team
	// SQL: SELECT * FROM team_schedule WHERE team_id=team->id AND start_date <= TODAY
	//	ORDER BY start_date LIMIT 1
	// 	SELECT * FROM teamschedule_employees WHERE teamschedule_id=schedule->id
	unset($schedule);
	try {
		$schedule = TeamSchedule::getTeamSchedule($pdo=$pdo, $id=0, $teamid=$team['id']);
	}catch(\Exception $e) {
		continue;
	}
	//print("index.php::teams schedule = ".print_r($schedule, true)."<br/>\n");

	$data .= "<thead>\n"
		."  <tr>\n"
		."    <th style=\"font-size:20px;\">\n"
		."      <a href=\"teams.php?teamid=".$team['id']."\">".$team['name']."</a>\n"
		."    </th>\n"
		."    <td>\n"
		//."      <a href=\"./schedule.php?sid=$schedule->id\">Edit Schedule</a>&nbsp\n"
		."      <a href=\"./schedules.php?teamid=".$team['id']."\">Schedules</a>\n"
		."        |\n"
		."      <a href=\"./templates.php?teamid=".$team['id']."\">Templates</a>\n"
		."    </td>\n"
		."  </tr>\n"
		."</thead>\n"
		."<tbody>\n";

	unset($schedule_employees);
	$schedule_employees = $schedule->getTeamScheduleEmployees($pdo);
	//print("index.php::teams schedule_employees = ".print_r($schedule_employees, true)."<br/>\n");

	//Count of Employees on the schedule, determines if we need to display call_order
	$schedemps_cnt = \count($schedule_employees);
	$schedemps_last_key = \array_key_last($schedule_employees);

	$contact_instructions = "";
	foreach ($schedule_employees as $key => $schedule_employee) {
		//print("index.php:employees schedule_employee = ".print_r($schedule_employee, true)."<br/>\n");
		//We only want to display Employees w/ a call_order >= 1
		//  call_order <= 0 will be deleted from the schedule
		$call_order = $schedule_employee['call_order'];
		//if($call_order <= 0) { continue; }
		if($call_order > 0) { $employee_id = $schedule_employee['employee']; }
		else { continue; }

		//$employee = $employees[$key];
		//print("index.php::team.schedule_employees employee = ".print_r($employee, true)."<br/>\n");

		// SQL: SELECT * FROM employee WHERE id=employee_id
		// No further invocation for teams, schedules, etc.
		//NOTE: Running a for loop of each employee on a schedule for each team
		//	Required, because collecting employees in bulk does not respect call order
		$employee = Employee::getEmployee($pdo, $employee_id);
		//print("index.php:employees employee = ".print_r($employee, true)."<br/>\n");

		//If the last employee doesn't have contact instructions, lets display the last one that does
		if(!empty($employee->contact_instructions)) {
			$contact_instructions = $employee->contact_instructions;
		}

		unset($name);
		$name = $employee->getEmployeeName();
		//$data .= "  <tr>\n"
		//	."    <th>Name</th>\n"
		//	."    <td>\n"
		//	."      <b><a href=\"contact.php?id=$employee->id\">$name</a></b>\n"
		//	."    </td>\n"
		//	."  </tr>\n";
		foreach ($labels as $label => $description) {
			if(DEBUG > 1) {
				print("index.php::teams.schedule_employees.labels label = $label : "
					."description = $description<br/>\n");
				print("index.php::teams.schedule_employees.labels employee.label = "
					.$employee->$label."<br/>\n");
			}
			if($label == 'call_order') {
				//We want call order to display "nice", i.e. 1st, 2nd, 23rd, 54th
				$call_order = (string) $call_order;
				if(\str_ends_with($call_order, "1")) { $call_order .= "st"; }
				else if(\str_ends_with($call_order, "2")) { $call_order .= "nd"; }
				else if(\str_ends_with($call_order, "3")) { $call_order .= "rd"; }
				else { $call_order .= "th"; }

				//But we only need it if there's more than one employee on the schedule
				if($schedemps_cnt > 1) {
					$data .= "  <tr>\n"
						."    <th>$description</th>\n"
						."    <td>$call_order</td>\n"
						."  </tr>\n";
				} //END if >1 schedule employee
			} else if($label == 'name') {
				$data .= "  <tr>\n"
					."    <th>Name</th>\n"
					."    <td>\n"
					."      <b><a href=\"contact.php?id=$employee->id\">$name</a></b>\n"
					."    </td>\n"
					."  </tr>\n";
			} else if(!empty($employee->$label)) {
				if(!empty($value)) { unset($value); }
				switch($label) {
				case("tel_audinet"):
					$value = $employee->getEmployeeAudinet();
					break;
				case("tel_direct"):
				case("tel_cell_corp"):
				case("tel_cell_other"):
				case("tel_home_other"):
					$value = formatPhoneNumber((int)$employee->$label);
					break;
				default:
					$value = $employee->$label;
				}
				//We're only going to display a column if it's populated with data
				$data .= "  <tr>\n"
					."    <th>$description</th>\n"
					."    <td>$value</td>\n"
					."  </tr>\n";
			}
		} //END foreach(label)
		if($key == $schedemps_last_key) { 
			$data .= "  <tr>\n"
				."    <td colspan=\"2\">$contact_instructions</td>\n"
				."  </tr>\n";
		} else {
			$data .= "  <tr><td></td><td><hr></td></tr>\n";
		} //END If last employee on schedule
	} //END foreach(employee)
	//Memory cleanup
	unset($contact_instructions);
	unset($description);
	unset($employee);
	unset($employees);
	unset($key);
	unset($label);
	unset($name);
	unset($schedule);
	unset($schedule_employee);
	unset($schedule_employees);
	unset($team);
	unset($value);
	$data .= "</tbody>\n";
	if($teams_key != $teams_last_key) { $data .= "<tbody><tr><td colspan=\"2\"><hr></td></tr></tbody>\n"; }
} //END foreach(oncall_group)

$data .= "</table>\n"
	."</main>\n";

print($data);

unset($data);
unset($labels);
unset($pdo);
unset($query);
unset($result);
unset($teams);

include("./footer.php");
?>
