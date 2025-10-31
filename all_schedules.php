<?php
namespace WiseEyesEnt\OnCall2;

require_once("./common.php");
require_once("./dbconn.php");
require_once("./employee.php");
require_once("./team.php");
require_once("./team_schedule.php");

require_once("./header.php");

if(empty($pdo)) { $pdo = dbConnect(); }
if(DEBUG > 2) { print("all_schedules.php: pdo = ".print_r($pdo, true)."<br/>\n"); }

if(!empty($teams)) { unset($teams); }
$teams = Team::getTeamNames($pdo);
if(DEBUG > 2) { print("all_schedules.php: teams = ".print_r($teams, true)."<br/>\n"); }

if(!empty($teamid)) { unset($teamid); }
$teamid = !empty($_REQUEST["teamid"]) ? (int) $_REQUEST["teamid"] : $teams[0]['id'];
if(DEBUG > 2) { print("all_schedules.php: teamid = $teamid<br/>\n"); }

$display_active = (isset($_REQUEST['active'])) ? (bool) $_REQUEST['active'] : true;
if(DEBUG > 2) { print("all_schedules.php: display_active = $display_active<br/>\n"); }

[$team_short, $team_select] = teamSelect($pdo=$pdo, $teamid=$teamid, $teams=$teams);
if(DEBUG > 2) { print("all_schedules.php: team_short = ".print_r($team_short, true)."<br/>\n"); }

$team = Team::getTeam($pdo, $team_short['id']);
if(DEBUG > 2) { print("all_schedules.php: team = ".print_r($team, true)."<br/>\n"); }

$team_employees = $team->getTeamEmployees($pdo, $active=true);
if(DEBUG > 1) { print("all_schedules.php: team_employees = ".print_r($team_employees, true)."<br/>\n"); }

$teamemployee_ids = array();
foreach($team_employees as $employee) { $teamemployee_ids[] = $employee->id; }
if(DEBUG > 1) { print("all_schedules.php: teamemployees_ids = ".print_r($teamemployee_ids, true)."<br/>\n"); }

$teamemployees_cnt = \count($team_employees);
if(DEBUG > 1) { print("all_schedules.php: teamemployees_cnt = $teamemployees_cnt<br/>\n"); }

//POST Request Processing
if($request_method == "POST") {
	if(DEBUG > 2) { print("all_schedules.php: POST = ".print_r($_POST, true)."<br/>\n"); }

	if(isset($_POST['q'])) {
		// q == search
		if(DEBUG > 1) { print("all_schedules.php::POST q = ".$_POST['q']."<br/>\n"); }

		$term = (string) $_POST['q'];
		if(DEBUG > 1) { print("all_schedules.php::POST.q term = $term<br/>\n"); }

		header("Location: all_schedules.php?teamid=$teamid&month=$term");
	} else if(isset($_POST['enable_schedule'])) {
		// enable_schedule == Activate disabled schedule
		if(DEBUG > 1) {
			print("all_schedules.php::POST enable_schedule = "
				.$_POST['enable_schedule']."<br/>\n");
		}

		$schedule = TeamSchedule::getTeamSchedule($pdo, (int) $_POST['enable_schedule']);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.enable_schedule PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		$schedule->active = true;
		$schedule->save($pdo);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.enable_schedule POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}
	} else if(isset($_POST['disable_schedule'])) {
		// disable_schedule == Deactivate enabled schedule
		if(DEBUG > 1) {
			print("all_schedules.php::POST disable_schedule = "
				.$_POST['disable_schedule']."<br/>\n");
		}

		$schedule = TeamSchedule::getTeamSchedule($pdo, (int) $_POST['disable_schedule']);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.disable_schedule PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		$schedule->active = false;
		$schedule->save($pdo);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.disable_schedule POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}
	} else if(isset($_POST['add_employee'])) {
		// add_employee == Adding employee to schedule
		if(DEBUG > 1) {
			print("all_schedules.php::POST add_employee = "
				.$_POST['add_employee']."<br/>\n");
		}

		$schedule = TeamSchedule::getTeamSchedule($pdo, (int) $_POST['schedule_id']);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.add_employee PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		$employee_id = (int) $_POST['add_employee'];
		$call_order = (int) $_POST['add_call_order'];
		if(DEBUG > 1) {
			print("all_schedules.php::POST.add_employee "
				."employee_id = $employee_id; "
				."call_order = $call_order<br/>\n");
		}

		$new_employees = $schedule->employees;
		$new_employees[] = array('employee' => $employee_id, 'call_order' => $call_order);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.add_employee new_employees = "
				.print_r($new_employees, true)."<br/>\n");
		}

		$schedule->employees = $new_employees;
		$schedule->save($pdo);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.add_employee POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}
	} else if(isset($_POST['remove_employee'])) {
		// remove_employee == Removing employee from schedule
		if(DEBUG > 1) {
			print("all_schedules.php::POST remove_employee = "
				.$_POST['remove_employee']."<br/>\n");
		}

		$employee_id = (int) $_POST['remove_employee'];
		if(DEBUG > 1) {
			print("all_schedules.php::POST.remove_employee employee_id = $employee_id<br/>\n");
		}

		$schedule = TeamSchedule::getTeamSchedule($pdo, (int) $_POST['id']);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.remove_employee PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		$new_employees = $schedule->employees;
		foreach($new_employees as $key => $new_employee) {
			if($employee_id == $new_employee['employee']) {
				$new_employees[$key]['call_order'] = 0;
			}
		}
		if(DEBUG > 1) {
			print("all_schedules.php::POST.remove_employee new_employee = "
				.print_r($new_employees, true)."<br/>\n");
		}

		$schedule->employees = $new_employees;
		$schedule->save($pdo);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.remove_employee POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}
	} else if(isset($_POST['update_schedule'])) {
		// update_schedule == I swear these are all self-explanatory
		if(DEBUG > 1) {
			print("all_schedules.php::POST update_schedule = ".$_POST['update_schedule']."<br/>\n");
		}

		$schedule_id = (int) $_POST['update_schedule'];
		if(DEBUG > 1) {
			print("all_schedules.php::POST.update_schedule "
				."schedule_id = $schedule_id<br/>\n");
		}

		$schedule = TeamSchedule::getTeamSchedule($pdo, $schedule_id);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.update_schedule PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		//Start Date
		$start_date = (isset($_POST['start_date'])) ? (string) $_POST['start_date'] :
				$schedule->start_date->format('Y-m-d H:i');
		$start_date = new \DateTime($start_date, new \DateTimeZone("UTC"));
		$schedule->start_date = $start_date;
		if(DEBUG > 1) {
			print("all_schedules.php::POST.update_schedule start_date = "
			       .print_r($start_date, true)."<br/>\n");	
		}

		//Employee Handling
		$old_employees = $schedule->employees;
		if(DEBUG > 1) {
			print("all_schedules.php::POST.update_schedule old_employees = "
				.print_r($old_employees, true)."<br/>\n");
		}

		$post_employees = array();
		$postemployee_ids = array();
		$iter = 0;
		foreach($_POST as $key => $value) {
			if(\str_starts_with($key, "employee")) {
				$employee_id = (int) $_POST["employee$iter"];
				$call_order = (int) $_POST["call_order$iter"];
				$post_employees[] = array('employee' => $employee_id,
							  'call_order' => $call_order);
				$postemployee_ids[] = $employee_id;
				$iter++;
				unset($employee_id);
				unset($call_order);
			}
		}//END foreach POST.employeeN
		if(DEBUG > 1) {
			print("all_schedules.php::POST.update_schedule post_employees = "
				.print_r($post_employees, true)."; postemployee_ids = "
				.print_r($postemployee_ids, true)."<br/>\n");
		}

		//Remove any employees that have been replaced via post
		foreach($old_employees as $key => $old_employee) {
			$employee_id = $old_employee['employee'];
			if(!\in_array($employee_id, $postemployee_ids)) {
				$post_employees[] = array('employee' =>  $employee_id,
							  'call_order' => 0);
			}
		}
		$schedule->employees = $post_employees;
		$schedule->save($pdo);
		if(DEBUG > 1) {
			print("all_schedules.php::POST.update_schedule POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}
	}//END POST METHOD
	unset($schedule);
}//END POST

//Month handling
if(isset($_GET['month'])) {
	$date_str = (string) $_GET['month'];
	if(DEBUG > 1) {
		print("all_schedules.php::GET.month date_str = $date_str<br/>\n");
	}

	if(\preg_match("/^[0-9]+-[0-9]{2}-[0-9]{2}$/", $date_str)) {
		$date_fmt = "Y-m-d";
	} else {
		if(\preg_match("/^[0-9]+-[0-9]{2}$/", $date_str)) {
			$date_fmt = "Y-m";
		} else if(\preg_match("/^[0-9]+$/", $date_str)) {
			$date_fmt = "Y";
		} else {
			if(DEBUG > 1) {
				print("all_schedules.php::GET.month ERROR! Format not recognized<br/>\n");
			}

			$request_url = \preg_replace("/\?.*/", "?teamid=$team->id&month_error=1", $request_url);
			header("Location: $request_url");
			exit;
		}
	}
	if(DEBUG > 1) {
		print("all_schedules.php::GET.month date_fmt = $date_fmt; date_str = $date_str<br/>\n");
	}
	$date = \DateTime::createFromFormat($date_fmt, $date_str, new \DateTimeZone("UTC"));
} else { $date = new \DateTime("now", new \DateTimeZone("UTC")); }
if(DEBUG > 1) {
	print("all_schedules.php::GET.month POST date = ".print_r($date, true)."<br/>\n");
}

//START PAGE OUTPUT (header already sent)
//Blockquote Header
if(!empty($data)) { unset($data); }
$data =  "<blockquote>\n";
if(isset($_GET['month'])) {
	$data .= "  <h4>".$date->format("F Y")." Schedules for ".$team_short['name']."</h4>\n";
} else { 
	$data .= "  <h4>All Schedules for ".$team_short['name']."</h4>\n";
}
$data .= "  <em style=\"text-align:left\">\n"
	."    NOTICE: Start Dates are in UTC, e.g., 12:00 PM corresponds to 08:00 AM EDT\n"
	."  </em>\n"
	."  <br/>\n"
	."  <pre>NOTE: Search is by date (YYYY[-MM[-DD]]) for the given month.\n"
	."EXAMPLE) 2023-11-30, 2023-11, 2023</pre>\n"
	."</blockquote>\n"
	.$team_select;

//Month handling
//Default behavior is per month for the current month, but only enforced by query string
if(isset($_GET['month_error'])) {
	if(DEBUG > 1) {
		print("all_schedules.php::GET month_error = ".$_GET['month_error']."<br/>\n");
	}

	$date = new \DateTime("now", new \DateTimeZone("UTC"));
	$data .= "<h1>ERROR! Invalid month entered</h1>\n";
}

if(isset($_GET['month'])) {
	if(DEBUG > 1) { print("all_schedules.php::GET month = ".$_GET['month']."<br/>\n"); }
	$schedules = $team->getMonthSchedules($pdo, $date, $active=false, $desc=true);
} else {
	//...old style, just displaying all schedules for this team in existence
	$schedules = $team->getTeamSchedules($pdo, $current=false, $active=false, $desc=true);
}
if(DEBUG > 1) {
	print("all_schedules.php: schedules = ".print_r($schedules, true)."<br/>\n");
}

$last_month = clone $date;
$last_month->modify("-1 month");
$next_month = clone $date;
$next_month->modify("+1 month");
$today = new \DateTime("now", new \DateTimeZone("UTC"));
if(DEBUG > 1) {
	print("all_schedules.php: last_month = ".print_r($last_month, true)."</br>\n");
	print("all_schedules.php: next_month = ".print_r($next_month, true)."</br>\n");
	print("all_schedules.php: today = ".print_r($today, true)."</br>\n");
}

//Navigation Buttons
//$data .= $team_select
$data .= "<br/>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"all_schedules.php?teamid=$teamid&month=".$last_month->format("Y-m-d")."\">"
		."&lt;</a>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"all_schedules.php?teamid=$teamid&month=".$today->format("Y-m-d")."\">Today</a>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"all_schedules.php?teamid=$teamid&month=".$next_month->format("Y-m-d")."\">"
		."&gt;</a>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"all_schedules.php?teamid=$teamid\">All</a>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"schedules.php?teamid=$teamid\">Current</a>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"templates.php?teamid=$teamid\">Templates</a>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"teams.php?teamid=$teamid\">Team</a>\n"
	."</div>\n"
	."<br/>\n";

//Table header
$data .= "<table style=\"width:100%;white-space:nowrap;\">\n"
	."<thead>\n"
	."  <tr>\n"
	//."    <th></th>\n"
	."    <th>ID</th>\n"
	."    <th>Start Date</th>\n"
	//."    <th></th>\n"
	."    <th>On-Call Person</th>\n"
	."    <th>Call Order</th>\n"
	."    <th></th><th></th>\n"
	."  </tr>\n"
	."</thead>\n"
	."<tbody>\n";

//Per schedule table population
//print("all_schedules.php: schedules = ".print_r($schedules, true)."<br/>\n");
foreach($schedules as $schedule) {
	if(DEBUG > 1) {
		print("all_schedules.php::foreach(schedules) schedule = ".print_r($schedule, true)."<br/>\n");
	}

	$is_old = $schedule->isOld($pdo);
	if(DEBUG > 1) {
		print("all_schedules.php::foreach(schedules) is_old = ".print_r($is_old, true)."<br/>\n");
	}

	//Gather Schedule Data
	$sid = $schedule->id;
	if(DEBUG > 1) {
		print("all_schedules.php::foreach(schedules) sid = $sid<br/>\n");
	}

	$schedule_employees = $schedule->employees;
	if(DEBUG > 1) {
		print("all_schedules.php::foreach(schedules) schedule_employees = "
			.print_r($schedule_employees, true)."<br/>\n");
	}

	$scheduleemployees_cnt = \count($schedule_employees);
	if(DEBUG > 1) {
		print("all_schedules.php::schedules scheduleemployees_cnt = $scheduleemployees_cnt<br/>\n");
	}

	$scheduleemployee_ids = array();
	foreach($schedule_employees as $schedule_employee) {
		$scheduleemployee_ids[] = $schedule_employee['employee'];
		unset($schedule_employee);
	}
	if(DEBUG > 1) {
		print("all_schedules.php::foreach(schedules) scheduleemployee_ids = "
			.print_r($scheduleemployee_ids, true)."<br/>\n");
	}

	$start_date = $schedule->start_date->format("Y-m-d H:i");
	if(DEBUG > 1) {
		print("all_schedules.php::foreach(schedules) start_date = "
			.print_r($start_date, true)."<br/>\n");
	}

	//Open the form
	if($schedule->active && !$is_old) { $data .= "  <tr>\n"; }
	else { $data .= "  <tr class=\"inactive\">\n"; }

	//Schedule ID & Start Date
	$data .= "    <td>\n"
		."      <a href=\"schedule.php?id=$sid\">$sid</a></td>\n"
		."    <td>$start_date</td>\n";

	//Schedule Employees
	$first_key = \array_key_first($schedule_employees);
	if(DEBUG > 1) {
		print("all_schedules.php::foreach(schedules) first_key = $first_key<br/>\n");
	}

	foreach($schedule_employees as $key => $schedule_employee) {
		if(DEBUG > 1) {
			print("all_schedules.php::foreach(schedules).foreach(schedule_employees) "
				."key = $key; schedule_employee = "
				.print_r($schedule_employee, true)."<br/>\n");
		}

		$semployee_id = $schedule_employee['employee'];
		$call_order = $schedule_employee['call_order'];
		$employee = Employee::getEmployee($pdo, $schedule_employee['employee']);
		$name = $employee->getEmployeeName();
		if(DEBUG > 1) {
			print("all_schedules.php::foreach(schedules).foreach(schedule_employees) "
				."employee_id = $employee_id; call_order = $call_order; employee = "
				.print_r($employee, true)."; name = $name<br/>\n");
		}

		if($key > $first_key) {
			if(!$is_old && $schedule->active) { $data .= "  <tr>\n"; }
			else { $data .= "  <tr class=\"inactive\">\n"; }
			$data .= "    <td colspan=\"2\"></td>\n";
		}
		$data .= "    <td>$name</td>\n"
			."    <td>$call_order</td>\n";
		if($key == $first_key && !$schedule->active && !$is_old) {
			$data .= "    <td>\n"
				."      <button type=\"button\" class=\"bimg\" "
					."onclick=\"enableSchedule($schedule->id)\" "
					."alt=\"Enable Schedule\" title=\"Enable schedule\">"
					."<img src=\"new-schedule.png\" />"
					."</button>\n"
				."    </td>\n";
		} else { $data .= "    <td></td>\n"; }
		if($scheduleemployees_cnt > 1) { $data .= "  </tr>\n"; }

		unset($call_order);
		unset($employee);
		unset($name);
	}//END foreach Schedule Employee
	$data .= "  </form>\n"
		."  </tr>\n";

	//Memory cleanup
	unset($id);
	unset($iter);
	unset($schedule_employees);
	unset($scheduleemployees_cnt);
	unset($start_date);
}//END foreach Team Schedule
unset($first_key);
unset($schedules);
$data .= "</table>\n";

//Add employee dialog
$schedule_id = (isset($schedule)) ? $schedule->id : 0;
if(DEBUG > 1) {
	print("all_schedules.php: schedule_id = $schedule_id<br/>\n");
}

$modal = "<dialog id=\"add_employee_dialog\" autofocus>\n"
	."<form id=\"add_employee_form\" formaction=\"$request_url\" method=\"post\">\n"
	."<input type=\"hidden\" name=\"schedule_id\" id=\"sid\" value=\"0\" />\n"
	."<h3 id=\"add_employee_title\">Add Employee (Schedule $schedule_id)</h3>\n"
	."<table>\n"
	."<thead>\n"
	."  <tr>\n"
	."    <th>Employee</th>\n"
	."    <th>Call Order</th>\n"
	."    <th></th>\n"
	."  </tr>\n"
	."</thead>\n"
	."<tbody>\n"
	."  <tr>\n"
	."    <td>\n"
	."      <select name=\"add_employee\">\n";
foreach($team_employees as $employee) {
	if(!$employee->active) { continue; }
	$modal .= "        <option value=\"$employee->id\">".$employee->getEmployeeName()."</option>\n";
}
$modal .= "      </select>\n"
	."    </td>\n"
	."    <td>\n"
	."      <input type=\"number\" name=\"add_call_order\" min=\"1\" value=\"1\" />\n"
	."    </td>\n"
	."    <td>\n"
	."      <input type=\"submit\" value=\"Save\" />\n"
	."      <button type=\"button\" onclick=\"closeAddEmployee()\">Close</button>\n"
	."    </td>\n"
	."  </tr>\n"
	."</table>\n"
	."</form>\n"
	."</dialog>\n";
$data .= $modal;

//Javascript
$data .= '
<script>
function disableSchedule(sid) {
	console.log(`DISABLE SCHEDULE ${sid}`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `disable_schedule=${sid}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
		//Redirect to GET for updated results
		console.log("Redirect to '.$request_url.'");
		location.href = "'.$request_url.'";
	});
}

function enableSchedule(sid) {
	console.log(`ENABLE SCHEDULE ${sid}`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `enable_schedule=${sid}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
		//Redirect to GET for updated results
		console.log("Redirect to '.$request_url.'");
		location.href = "'.$request_url.'";
	});
}

function addEmployee(sid) {
	console.log(`ADD EMPLOYEE ${sid}`);
	document.getElementById("add_employee_title").innerHTML = `Add Employee (Schedule ${sid})`;
	document.getElementById("sid").value = sid;
	document.getElementById("add_employee_dialog").showModal();
}

function closeAddEmployee() {
	console.log(`CANCEL ADD SCHEDULE EMPLOYEE`);
	document.getElementById("add_employee_form").reset();
	document.getElementById("add_employee_dialog").close();
}

function removeEmployee(sid, eid) {
	console.log(`REMOVE EMPLOYEE sid: ${sid} eid: ${eid}`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `id=${sid}&remove_employee=${eid}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
		//Redirect to GET for updated results
		console.log("Redirect to '.$request_url.'");
		location.href = "'.$request_url.'";
	});
}
</script>';

unset($schedule);
unset($team_employees);

print($data);
unset($data);
unset($pdo);
require_once("./footer.php");
?>
