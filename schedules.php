<?php
namespace WiseEyesEnt\OnCall2;

require_once("./common.php");
require_once("./dbconn.php");
require_once("./employee.php");
require_once("./team.php");
require_once("./team_schedule.php");

require_once("./header.php");

if(empty($pdo)) { $pdo = dbConnect(); }
if(DEBUG > 2) { print("schedules.php: pdo = ".print_r($pdo, true)."<br/>\n"); }

if(!empty($teams)) { unset($teams); }
$teams = Team::getTeamNames($pdo);
if(DEBUG > 2) { print("schedules.php: teams = ".print_r($teams, true)."<br/>\n"); }

if(!empty($teamid)) { unset($teamid); }
$teamid = !empty($_REQUEST["teamid"]) ? (int) $_REQUEST["teamid"] : $teams[0]['id'];
if(DEBUG > 2) { print("schedules.php: teamid = $teamid<br/>\n"); }

if(!\str_contains($request_url, "teamid=")) {
	if(DEBUG > 2) {
		print("schedules.php: Query string missing teamid, redirecting...<br/>\n");
	}
	header("Location: schedules.php?teamid=$teamid");
	exit;
}

$display_active = (isset($_REQUEST['active'])) ? (bool) $_REQUEST['active'] : true;
if(DEBUG > 2) { print("schedules.php: display_active = ".print_r($display_active, true)."<br/>\n"); }

[$team_short, $team_select] = teamSelect($pdo=$pdo, $teamid=$teamid, $teams=$teams);
if(DEBUG > 2) { print("schedules.php: team_short = ".print_r($team_short, true)."<br/>\n"); }

if(!empty($team)) { unset($team); }
$team = Team::getTeam($pdo, $name=$team_short['id']);
if(DEBUG > 1) { print("schedules.php: team = ".print_r($team, true)."<br/>\n"); }

$team_employees = $team->getTeamEmployees($pdo);
if(DEBUG > 1) { print("schedules.php: team_employees = ".print_r($team_employees, true)."<br/>\n"); }

$team_schedules = $team->getTeamSchedules($pdo=$pdo, $current=true, $active=$display_active);
if(DEBUG > 1) { print("schedules.php: team_schedules = ".print_r($team_schedules, true)."<br/>\n"); }

$js_start = 0;
if(!empty($team_schedules)) {
	$new_start_date = clone $team_schedules[\array_key_last($team_schedules)]->start_date;
	$new_start_date = $new_start_date->modify("+$team->ndays day");
} else {
	$new_start_date = new \DateTime("now", new \DateTimeZone("UTC"));
	$new_start_date = $new_start_date->modify("-1 day");
	$new_start_date = $new_start_date->setTime(\substr($team->start_time, 0, 2),
						   \substr($team->start_time, 3, 2));
}
$js_start = $new_start_date->format("U");
if(DEBUG > 2) {
	print("schedules.php::js_start js_start = $js_start; new_start_date = "
		.print_r($new_start_date, true)."<br/>\n");
}

if(!empty($data)) { unset($data); }
//Javascript
$data = '
<script>
function insertSchedule(new_start_date, employee) {
	fetch("'.$request_url.'", {
		method: "POST",
		body: `insert_schedule=${new_start_date}&employee=${employee}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		location.href = response.url;
	});
	//.then((response) => {
	//	console.log(`RESPONSE.URL ${response.url}`);
	//});
}

function addSchedule(new_start_date) {
	console.log(`addSchedule(${new_start_date})`);
	var start_date = new Date(new_start_date * 1000).toISOString();
	var date = start_date.substring(0, start_date.indexOf("T"));
	var time = start_date.substring(start_date.indexOf("T")+1, start_date.indexOf("T")+6);
	start_date = `${date} ${time}`;
	console.log(start_date);
	var add_schedule_form = `<h3>Add Schedule</h3>
<form formaction="schedules.php" method="post" id="add_schedule_form">
<input type="hidden" name="add_schedule" value="1" />
<table>
  <tr>
    <td>
      <input type="datetime-local" name="new_start_date" value="${start_date}" />
    </td>
    <td>
      <select name="new_employee">
';
foreach($team_employees as $employee) {
	if(DEBUG > 2) {
		print("schedules.php::foreach(team_employees) employee = "
			.print_r($employee, true)."<br/>\n");
	}
	if($employee->active) {
		$data .= "        <option value=\"".$employee->id."\">"
			.$employee->getEmployeeName()."</option>\n";
	}
	unset($employee);
}
$data .=
'      </select>
    </td>
    <td>
      <input type="number" name="new_call_order" class="call-order" value="1" min="1" />
    </td>
  </tr>
  <tr>
    <td colspan="3" align="right">
      <input type="submit" value="Save" />
      <button type="button" onclick="closeAddSchedule()">Close</button>
    </td>
  </tr>
</table>
</form>
`;
	add_schedule_dialog = document.getElementById("add_schedule");
	add_schedule_dialog.innerHTML = add_schedule_form;
	add_schedule_dialog.show();
}

function closeAddSchedule() {
	document.getElementById("add_schedule_form").reset();
	document.getElementById("add_schedule").close();
}

function disableSchedule(sid) {
	console.log(`DISABLE SCHEDULE ${sid}`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `disable_schedule=${sid}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
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
		console.log("Redirect to '.$request_url.'");
		location.href = "'.$request_url.'";
	});
}

function addEmployee(sid) {
	console.log(`ADD SCHEDULE EMPLOYEE ${sid}`);
	document.getElementById("sid").value = sid;
	document.getElementById("add_employee_dialog").showModal();
}

function closeAddEmployee() {
	console.log(`CANCEL ADD SCHEDULE EMPLOYEE`);
	document.getElementById("add_employee_form").reset();
	document.getElementById("add_employee_dialog").close();
}

function removeEmployee(sid, eid) {
	console.log(`REMOVE SCHEDULE EMPLOYEE sid: ${sid} eid: ${eid}`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `remove_employee=${eid}&id=${sid}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
		console.log("Redirect to '.$request_url.'");
		location.href = "'.$request_url.'";
		return response.text();
	});
}
</script>
';

//HTML
$data .= "<blockquote>\n"
	."  <h4>On-Call Schedules for ".$team_short['name']."</h4>\n"
	."  <em style=\"text-align:left\">\n"
	."    NOTICE: Start Dates are in UTC, e.g., 12:00 PM corresponds to 08:00 AM EDT\n"
	."  </em>\n"
	."  <pre>NOTE: Search is for schedule by ID, date, or \"name\"\n"
	."NAME: teamid:YYYY-mm-dd[ HH:mm]\n"
	."EXAMPLES: 25364, 2024-01-08, 11:2024-01-08, 11:2024-01-08 00:00</pre>\n" 
	."</blockquote>\n";

$today = new \DateTime("now", new \DateTimeZone("UTC"));
$data .= $team_select
	."<br/>\n"
	."<div style=\"display:inline-block\">\n"
	."  <button type=\"button\" name=\"add_schedule\" onclick=\"addSchedule($js_start)\">"
		."Add Schedule"
	."  </button>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"all_schedules.php?teamid=$teamid&month=".$today->format("Y-m-d")."\">Monthly</a>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"templates.php?teamid=$teamid\">Templates</a>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"teams.php?teamid=$teamid\">Team</a>\n"
	."</div>\n";

//new_start_date, new_employee, & new_call_order indicate a new schedule
//POST handling...
if($request_method == "POST") {
	if(DEBUG > 2) { print("schedules.php: POST = ".print_r($_POST, true)."<br/>\n"); }

	if(isset($_POST['q'])) {
		//q is a search term
		if(DEBUG > 1) { print("schedules.php::POST q = ".$_POST['q']."<br/>\n"); }

		$term = (\preg_match("/^[0-9]+$/", $_POST['q'])) ? (int) $_POST['q'] : (string) $_POST['q'];
		if(DEBUG > 1) { print("schedules.php::POST term = ".print_r($term, true)."<br/>\n"); }

		if(is_int($term)) {
			header("Location: schedule.php?id=$term");
		} else if(\preg_match("/^[0-9]+:[0-9]+-/", $term)) {
			header("Location: schedule.php?name=$term");
		} else {
			header("Location: schedule.php?name=$team->id:$term");
		}
	} else if(isset($_POST['insert_schedule'])) {
		//Inserting a new schedule anywhere in the current schedules (including appends)
		if(DEBUG > 1) {
			print("schedules.php::POST insert_schedule = "
				.$_POST['insert_schedule']."<br/>\n");
		}

		$employee_id = (int) $_POST['employee'];
		$start_date = \DateTime::createFromFormat("U", (int) $_POST['insert_schedule']);
		if(DEBUG > 1) {
			print("schedules.php::POST.insert_schedule employee_id = $employee_id; "
				." start_date = ".print_r($start_date, true)."<br/>\n");
		}

		$schedule = new TeamSchedule();
		$schedule->team_id = $teamid;
		$schedule->start_date = $start_date;
		$schedule->employees = array(array('call_order' => 1, 'employee' => $employee_id));
		$schedule->active = true;
		$result = $schedule->save($pdo);
		if(empty($result)) {
			throw new \Exception("schedules.php::POST.insert_schedule "
				."ERROR! Schedule failed to save");
		}
		if(DEBUG > 1) {
			print("schedules.php::POST.insert_schedule POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		unset($employee_id);
		unset($result);
		unset($schedule);
		unset($start_date);

		//Reload page to display new schedule
		header("Location: $request_url");
	} else if(!empty($_POST['disable_schedule'])) {
		//Disabling a current schedule
		if(DEBUG > 1) {
			print("schedules.php::POST disable_schedule = "
				.$_POST['disable_schedule']."<br/>\n");
		}

		$schedule_id = (int) $_POST['disable_schedule'];
		if(DEBUG > 1) {
			print("schedules.php::POST.disable_schedule schedule_id = $schedule_id<br/>\n");
		}

		$schedule = TeamSchedule::getTeamSchedule($pdo, $schedule_id);
		if(DEBUG > 1) {
			print("schedules.php::POST.disable_schedule PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}
		$schedule->active = false;
		$result = $schedule->save($pdo);
		if(empty($result)) {
			throw new \Exception("schedules.php::POST.disable_schedule "
				."ERROR! Schedule failed to save");
		}
		if(DEBUG > 1) {
			print("schedules.php::POST.disable_schedule POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		unset($result);
		unset($schedule);
		unset($schedule_id);
	} else if(!empty($_POST['enable_schedule'])) {
		//TODO Deprecated: No longer relevant as we moved inactive schedules to all_schedules.php
		//Enabling a disabled current schedule
		if(DEBUG > 1) {
			print("schedules.php::POST enable_schedule = ".$_POST['enable_schedule']."<br/>\n");
		}

		$schedule_id = (int) $_POST['enable_schedule'];
		if(DEBUG > 1) {
			print("schedules.php::POST.enable_schedule schedule_id = $schedule_id<br/>\n");
		}

		$schedule = TeamSchedule::getTeamSchedule($pdo, $schedule_id);
		if(DEBUG > 1) {
			print("schedules.php::POST.enable_schedule PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}
		$schedule->active = true;
		$result = $schedule->save($pdo);
		if(empty($result)) {
			throw new \Exception("schedules.php::POST.enable_schedule "
				."ERROR! Schedule failed to save");
		}
		if(DEBUG > 1) {
			print("schedules.php::POST.enable_schedule POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		unset($result);
		unset($schedule);
		unset($schedule_id);
	} else if(!empty($_POST['add_employee'])) {
		//Adding an employee/call_order to an existing schedule
		if(DEBUG > 2) {
			print("schedules.php::POST add_employee = ".$_POST['add_employee']."<br/>\n");
		}

		$schedule_id = (int) $_POST['schedule_id'];
		if(DEBUG > 1) {
			print("schedules.php::POST.add_employee schedule_id = $schedule_id<br/>\n");
		}

		$schedule = TeamSchedule::getTeamSchedule($pdo, $schedule_id);
		if(DEBUG > 1) {
			print("schedules.php::POST.add_employee PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		$employee_id = (int) $_POST['add_employee'];
		$call_order = (int) $_POST['add_call_order'];
		if(DEBUG > 1) {
			print("schedules.php::POST.add_employee employee_id = $employee_id; "
				."call_order = $call_order<br/>\n");
		}

		$new_employees = $schedule->employees;
		$new_employees[] = array('employee' => $employee_id, 'call_order' => $call_order);
		if(DEBUG > 1) {
			print("schedules.php::POST.add_employee new_employees = "
				.print_r($new_employees, true)."<br/>\n");
		}

		$schedule->employees = $new_employees;
		$result = $schedule->save($pdo);
		if(empty($result)) {
			throw new \Exception("schedules.php::POST.add_employee "
				."ERROR! Failed to save schedule");
		}
		if(DEBUG > 1) {
			print("schedules.php::POST.add_employee POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		unset($call_order);
		unset($employee_id);
		unset($new_employees);
		unset($result);
		unset($schedule);
	} else if(!empty($_POST['remove_employee'])) {
		//Removing an employee/call_order from an existing schedule
		if(DEBUG > 1) {
			print("schedules.php::POST remove_employee = "
				.$_POST['remove_employee']."<br/>\n");
		}

		if(empty($_POST['id'])) {
			throw new \Exception("ERROR! schedules.php::POST.remove_employee "
					    ."(schedule) id is EMPTY");
		}
		
		$employee_id = (int) $_POST['remove_employee'];
		$schedule_id = (int) $_POST['id'];
		if(DEBUG > 1) {
			print("schedules.php::POST remove_employee = employee_id = $employee_id : "
				."schedule_id = $schedule_id<br/>\n");
		}

		$schedule = TeamSchedule::getTeamSchedule($pdo, $schedule_id);
		if(DEBUG > 1) {
			print("schedules.php::POST.remove_employee schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		$schedule_employees = $schedule->employees;
		if(DEBUG > 1) {
			print("schedules.php::POST.remove_employee schedule_employees = "
				.print_r($schedule_employees, true)."<br/>\n");
		}

		foreach($schedule_employees as $key => $schedule_employee) {
			if(DEBUG > 1) {
				print("schedules.php::POST.remove_employee key = $key, "
					."schedule_employee = ".print_r($schedule_employee, true)."<br/>\n");
				print("schedules.php::POST.remove_employee schedule_employee[employee] = "
					.$schedule_employee['employee']."<br/>\n");
			}

			if($schedule_employee['employee'] == $employee_id) {
				$schedule_employees[$key]['call_order'] = 0;
			}
		}
		unset($key);
		if(DEBUG > 1) {
			print("schedules.php::POST.remove_employee POST schedule_employees = "
				.print_r($schedule_employees, true)."<br/>\n");
		}

		$schedule->employees = $schedule_employees;
		$result = $schedule->save($pdo);
		if(empty($result)) {
			throw new \Exception("schedules.php::POST.remove_employee "
				."ERROR! Schedule failed to save");
		}
		if(DEBUG > 1) {
			print("schedules.php::POST.remove_employee POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		//Free the memory
		unset($employee_id);
		unset($result);
		unset($schedule);
		unset($schedule_id);
		unset($schedule_employees);
	} else if(!empty($_POST['add_schedule'])) {
		//Creating a new schedule
		if(DEBUG > 1) { print("teams.php::POST add_schedule = ".$_POST['add_schedule']."<br/>\n"); }

		$start_date = \str_replace("T", " ", (string) $_POST['new_start_date']);
		if(DEBUG > 1) { print("teams.php::POST.add_schedule (string) start_date = $start_date<br/>\n"); }

		$start_date = \DateTime::createFromFormat("Y-m-d H:i", $start_date, new \DateTimeZone("UTC"));
		$employee = (int) $_POST['new_employee'];
		$call_order = (int) $_POST['new_call_order'];
		$schedule_employees = array("0" => array('employee' => $employee, 'call_order' => $call_order));
		$teamid = (int) $_REQUEST['teamid'];
		if(DEBUG > 1) {
			print("teams.php::POST.add_schedule start_date = ".print_r($start_date, true)."<br/>\n");
			print("teams.php::POST.add_schedule new_employee = $employee<br/>\n");
			print("teams.php::POST.add_schedule new_call_order = $call_order<br/>\n");
			print("teams.php::POST.add_schedule schedule_employees = "
				.print_r($schedule_employees, true)."<br/>\n");
			print("teams.php::POST.add_schedule teamid = $teamid<br/>\n");
		}

		//Let's check to see if a schedule already exists...
		//	DB.team_schedule UNIQUE KEY (team_id, start_date)
		$schedule = $team->getTeamSchedule($pdo, $start_date);

		//...otherwise, we can make a new one
		if(empty($schedule)) { $schedule = new TeamSchedule(); }
		if(DEBUG > 1) {
			print("schedules.php::POST.add_schedule PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		//NOTE: If a schedule exists, we're overwriting it with the provided data
		//	This will reset the active & employee/call_order stuff
		//	but team_id & start_date were already set anyways
		$schedule->team_id = $teamid;
		$schedule->start_date = $start_date;
		$schedule->employees = $schedule_employees;
		$schedule->active = true;
		$result = $schedule->save($pdo);
		if(empty($result)) {
			throw new \Exception("schedules.php::POST.add_schedule "
				."ERROR! Failed to save schedule");
		}
		if(DEBUG > 1) {
			print("schedule.php::POST.add_schedule POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		//Free the memory
		unset($call_order);
		unset($employee);
		unset($result);
		unset($schedule);
		unset($schedule_employees);
		unset($start_date);
		unset($teamid);

		if(DEBUG > 1) {
			print("schedules.php::POST.add_schedule Redirecting to reload");
		}

		//Reload the page to display the new schedule
		header("Location: $request_url");
		exit();
	} else if(!empty($_POST['sid'])) {
		//Otherwise, sid indicates modifying an existing schedule
		if(DEBUG > 1) { print("schedules.php::POST sid = ".$_POST['sid']."<br/>\n"); }
		if(!empty($schedule)) { unset($schedule); }

		$schedule_id = (int) $_POST['sid'];
		if(DEBUG > 1) { print("schedules.php::POST.sid(UPDATE) schedule_id = $schedule_id<br/>\n"); }

		$start_date = \str_replace("T", " ", (string) $_POST['start_date']);
		$start_date = \DateTime::createFromFormat("Y-m-d H:i",
				$start_date, new \DateTimeZone("UTC"));
		if(DEBUG > 1) {
			print("schedules.php::POST.sid(UPDATE) start_date = "
				.print_r($start_date, true)."<br/>\n"); 
		}

		$schedule = $team->getTeamSchedule($pdo, $start_date);
		if(empty($schedule)) { $schedule = TeamSchedule::getTeamSchedule($pdo, $schedule_id); }
		if(DEBUG > 1) {
			print("schedules.php::POST.sid(UPDATE) PRE schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		$schedule->start_date = $start_date;

		//Employee updates...
		// get the current schedule employees so we can trim as needed
		$schedule_employees = $schedule->employees;
		if(DEBUG > 1) {
			print("schedules.php::POST.sid(UPDATE) schedule_employees = "
				.print_r($schedule_employees, true)."<br/>\n");
		}

		// gather the POST employeeN & call_orderN values
		$post_employees = array();
		$iter = 0;
		foreach(\array_keys($_POST) as $key) {
			if(\str_starts_with($key, "employee")) {
				$employee_id = (int) $_POST["employee$iter"];
				$call_order = (int) $_POST["call_order$iter"];
				$post_employees[] = array("employee" => $employee_id,
							  "call_order" => $call_order);

				$iter++;
				unset($call_order);
				unset($employee_id);
			}
		}
		unset($key);
		unset($iter);
		if(DEBUG > 1) {
			print("schedules.php::POST.sid(UPDATE) post_employees = "
				.print_r($post_employees, true)."<br/>\n");
		}

		// list of post employee IDs for quick search
		//TODO Good application of \array_walk?
		$post_employeeids = array();
		foreach($post_employees as $schedule_employee) {
			$post_employeeids[] = $schedule_employee['employee'];
		}
		unset($schedule_employee);
		if(DEBUG > 1) {
			print("schedules.php::POST.sid(UPDATE) post_employeeids = "
				.print_r($post_employeeids, true)."<br/>\n");
		}

		//Check the existing schedule employees
		foreach($schedule_employees as $schedule_employee) {
			//If they're not in the POST, they're not on the schedule...
			if(!\in_array($schedule_employee['employee'], $post_employeeids)) {
				//Set call_order == 0 to delete when saved
				$post_employees[] = array("employee" => $schedule_employee['employee'],
							  "call_order" => 0);
			}
		}
		if(DEBUG > 1) {
			print("schedules.php::POST post_employees = "
				.print_r($post_employees, true)."<br/>\n");
			print("schedules.php::POST post_employeeids = "
				.print_r($post_employeeids, true)."<br/>\n");
		}
		$schedule->employees = $post_employees;

		//Save the changes...
		$result = $schedule->save($pdo);
		if(empty($result)) {
			throw new \Exception("schedules.php::POST.sid(UPDATE) "
				."ERROR! Failed to save schedule");
		}

		//Refresh the schedule (employee changes)
		$schedule = TeamSchedule::getTeamSchedule($pdo, $schedule->id);
		if(DEBUG > 1) {
			print("schedules.php::POST.sid(UPDATE) POST schedule = "
				.print_r($schedule, true)."<br/>\n");
		}

		//Free the memory
		unset($post_employees);
		unset($post_employeeids);
		unset($result);
		unset($schedule);
		unset($schedule_employees);
		unset($schedule_employee);
		unset($start_date);

		if(DEBUG > 1) {
			print("schedules.php::POST.sid(UPDATE) Redirecting to refresh changes<br/>\n");
		}
		header("Location: $request_url");
	} //END POST Method
}//END POST

$data .= "<table style=\"width:100%;white-space:nowrap;\">\n<thead>\n";
$data .= "  <tr>\n"
	."    <th>Start Date</th>\n"
	."    <th>On-Call Person</th>\n"
	."    <th>Call Order</th>\n"
	."    <th><!-- Actions --></th>\n"
	."  </tr>\n"
	."</thead>\n"
	."<tbody>\n";

if(!empty($query)) { unset($query); }
if(!empty($employees)) { unset($employees); }
if(!empty($employee)) { unset($employee); }
if(!empty($schedule)) { unset($schedule); }
if(!empty($team_userids)) { unset($team_userids); }

//If we don't have any schedules, let's just close the table out & display the page
if(empty($team_schedules)) {
	if(DEBUG > 2) {
		print("schedules.php: team_schedules EMPTY! Closing page");
	}

	$data .= "</tbody>\n"
		."</table>\n"
		."<dialog id=\"add_schedule\" type=\"modulo\"></dialog>";
	print($data);
	require_once('./footer.php');
	exit;
}

$schedules_count = \count($team_schedules);
if(DEBUG > 1) { print("schedules.php: schedules_count = $schedules_count<br/>\n"); }

foreach ($team_schedules as $schedule) {
	if(DEBUG > 2) {
		print("schedules.php::foreach(team_schedules) schedule = "
			.print_r($schedule, true)."<br/>\n");
	}
	$employees = $schedule->getTeamScheduleEmployees($pdo);
	if(DEBUG > 2) {
		print("schedules.php::foreach(team_schedules) employees = "
			.print_r($employees, true)."<br/>\n");
	}

	$schedule_employeeids = array();
	foreach($employees as $schedule_employee) {
		if(DEBUG > 2) {
			print("schedules.php::foreach(team_schedules).foreach(employees) "
				."schedule_employee = ".print_r($schedule_employee, true)."<br/>\n");
		}
		$schedule_employeeids[] = $schedule_employee['employee'];
	}
	if(DEBUG > 2) {
		print("schedules.php: schedule_employeeids = ".print_r($schedule_employeeids, true)."<br/>\n");
	}

	$start_date = $schedule->start_date->format("Y-m-d H:i:s");
	if(DEBUG > 2) { print("schedules.php::foreach(team_schedules) start_date = $start_date<br/>\n"); }

	$data .= "  <form action=\"$request_url\" method=\"post\">\n"
		."  <input type=\"hidden\" name=\"sid\" value=\"$schedule->id\" />\n"
		."  <tr>\n"
		."    <td>\n"
		."      <input name=\"start_date\" onchange=\"this.form.submit()\" "
				."type=\"datetime-local\" "
				."value=\"$start_date\" />\n"
		."    </td>\n";

	foreach ($employees as $key => $schedule_employee) {
		if(DEBUG > 2) {
			print("schedules.php::foreach(team_schedules).foreach(employees) "
				."key = $key; schedule_employee = "
				.print_r($schedule_employee, true)."<br/>\n");
		}

		if($key > 0) {
			$data .= "    <tr>\n"
				."      <td><!-- Start Date Buffer --></td>\n";
		}

		$employee_id = $schedule_employee['employee'];
		$call_order = $schedule_employee['call_order'];
		if(DEBUG > 2) {
			print("schedules.php::foreach(team_schedules).foreach(employees) "
				."employee_id = $employee_id; call_order = $call_order<br/>\n");
		}

		$employee = Employee::getEmployee($pdo, $employee_id);
		if(DEBUG > 2) {
			print("schedules.php::foreach(team_schedules).foreach(employees) "
				."employee = ".print_r($employee, true)."<br/>\n");
		}

		$name = $employee->getEmployeeName();
		if(DEBUG > 2) {
			print("schedules.php::foreach(team_schedules).foreach(employees) "
				."name = $name<br/>\n");
		}

		$data .= "    <td>\n"
			."      <select name=\"employee$key\" onchange=\"this.form.submit()\">\n"
			."        <option value=\"$employee->id\" selected=\"selected\">$name</option>\n";

		foreach ($team_employees as $team_employee) {
			if(DEBUG > 2) {
				print("schedules.php::foreach(team_schedules)"
					.".foreach(employees)"
					.".foreach(team_employees) "
					."team_employee = ".print_r($team_employee, true)."<br/>\n");
			}

			if($team_employee->active && !\in_array($team_employee->id, $schedule_employeeids)) {
				$data .= "        <option value=\"$team_employee->id\">"
						.$team_employee->getEmployeeName()."</option>\n";
			} else { continue; }
		}

		$data .= "      </select>\n"
			."    </td>\n"
			."    <td>\n"
			."      <input type=\"number\" class=\"call-order\" onchange=\"this.form.submit()\" "
				."name=\"call_order$key\" min=\"1\" max=\"100\" value=\"$call_order\" />\n"
			."    </td>\n"
			."    <td align=\"right\">\n";

		//Attribution required:
		//User icons created by Freepik - Flaticon
		//	https://www.flaticon.com/free-icon/new-user_72648
		//	https://www.flaticon.com/free-icon/remove-user_72830
		//Time and date icons created by Irfansusanto20 - Flaticon
		//	https://www.flaticon.com/free-icon/calendar_4218765
		//	https://www.flaticon.com/free-icon/calendar_4218858
		$js_start = (int) ($schedule->start_date->format("U") + 86400);
		$eid = $employees[0]['employee'];
		if(DEBUG > 2) {
			print("schedules.php::foreach(team_schedules) js_start = $js_start; "
				."eid = $eid<br/>\n");
		}

		$employees_cnt = \count($employees);
		$teamemployees_cnt = \count($team_employees);
		if(DEBUG > 2) {
			print("schedules.php::foreach(team_schedules) employees_cnt = $employees_cnt; "
				."teamemployees_cnt = $teamemployees_cnt<br/>\n");
		}
		if($key == \array_key_first($employees)) {
			$data .= "        <button type=\"button\" class=\"bimg\" "
					."onclick=\"insertSchedule($js_start, $eid)\" "
					."alt=\"Insert Schedule\" title=\"Insert a new schedule\">"
					."<img src=\"new-schedule.png\" style=\"margin:0\" /></button>\n";
		}
		if($key == \array_key_first($employees) && $schedules_count > 1) {
			$data .= "        <button type=\"button\" class=\"bimg\" "
					."onclick=\"disableSchedule($schedule->id)\" "
					."alt=\"Disable Schedule\" title=\"Disable schedule\">"
					."<img src=\"disable-schedule.png\" style=\"margin:0\" /></button>\n";
		}
		if($employees_cnt < $teamemployees_cnt) {
			$data .= "        <button type=\"button\" class=\"bimg\" "
					."onclick=\"addEmployee($schedule->id)\" "
					."alt=\"Add Backup\" title=\"Add backup\">"
					."<img src=\"add-user.png\" style=\"margin:0\" /></button>\n";
		}
		if($employees_cnt > 1) {
			$data .= "        <button type=\"button\" class=\"bimg\" "
					."onclick=\"removeEmployee($schedule->id, $employee->id)\" "
					."alt=\"Remove Backup\" title=\"Remove backup\">"
					."<img src=\"remove-user.png\" style=\"margin:0\" /></button>\n";
		}
		$data .= "    </td>\n"
			."  </tr>\n";
	}//END foreach(employees > schedule_employee)
	$data .= "  </form>\n";
}//END foreach(schedules)

if(DEBUG > 1) { print("schedules.php: new_start_date = ".print_r($new_start_date, true)."<br/>\n"); }
$data .= "</table>\n\n"
	."<dialog id=\"add_schedule\" type=\"modulo\"></dialog>";

//TODO Fix Add Employee
//Add Employee Modal
$modal = "<dialog id=\"add_employee_dialog\" autofocus>\n"
	."<form id=\"add_employee_form\" formaction=\"$request_url\" method=\"post\">\n"
	."<input type=\"hidden\" name=\"schedule_id\" id=\"sid\" value=\"0\" />\n"
	."<h3>Add Employee (Schedule $schedule->id.$teamid:".$schedule->start_date->format("Y-m-d").")</h3>\n"
	."<table>\n"
	."<thead>\n"
	."  <tr>\n"
	."    <th>Employee</th>\n"
	."    <th>Call Order</th>\n"
	."    <th></th>\n"
	."  <tr>\n"
	."    <td>\n"
	."      <select name=\"add_employee\">\n";
foreach($team_employees as $employee) {
	if(DEBUG > 2) {
		print("schedules.php::add_employee_dialog.foreach(team_employees) employee = "
			.print_r($employee, true)."<br/>\n");
	}

	//if($employee->active && !\in_array($employee->id, $schedule_employeeids)) {
	if($employee->active) {
		$modal .= "        <option value=\"$employee->id\">".$employee->getEmployeeName()."</option>\n";
	}
}
$modal .= "      </select>\n"
	."    </td>\n"
	."    <td>\n"
	."      <input type=\"number\" name=\"add_call_order\" value=\"1\" min=\"1\" style=\"width:3em\" />\n"
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

print($data);

unset($data);
unset($display_active);
unset($js_start);
unset($pdo);
unset($team);
unset($teams);
unset($team_employees);
unset($team_id);
unset($team_schedulees);
unset($team_select);
unset($team_short);

require_once("./footer.php");
?>
