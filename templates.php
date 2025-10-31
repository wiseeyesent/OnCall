<?php
namespace WiseEyesEnt\OnCall2;

require_once("./common.php");
require_once("./dbconn.php");
require_once("./employee.php");
require_once("./team.php");
require_once("./teamschedule_template.php");

require_once("./header.php");

if(DEBUG > 1) { print("templates.php:  DEBUG = ".print_r(DEBUG, true)."<br/>\n"); }

if(empty($pdo)) { $pdo = dbConnect(); }
if(DEBUG > 1) { print("templates.php: pdo = ".print_r($pdo, true)."<br/>\n"); }

if(!empty($teams)) { unset($teams); }
$teams = Team::getTeamNames($pdo);
if(DEBUG > 1) { print("templates.php: teams = ".print_r($teams, true)."<br/>\n"); }

if(!empty($teamid)) { unset($teamid); }
$teamid = !empty($_REQUEST["teamid"]) ? (int) $_REQUEST["teamid"] : $teams[0]['id'];

if(!\str_contains($request_url, "teamid=")) {
	header("Location: templates.php?teamid=$teamid");
	exit;
}

//$display_active = (isset($_REQUEST['active'])) ? (bool) $_REQUEST['active'] : true;
//if(DEBUG > 1) { print("templates.php: display_active = ".print_r($display_active, true)."<br/>\n"); }

[$team_short, $team_select] = teamSelect($pdo=$pdo, $teamid=$teamid, $teams=$teams);
if(DEBUG > 1) { print("templates.php: team_short = ".print_r($team_short, true)."<br/>\n"); }

if(!empty($team)) { unset($team); }
$team = Team::getTeam($pdo, $teamid);
if(DEBUG > 1) { print("templates.php: team = ".print_r($team, true)."<br/>\n"); }

$team_employees = $team->getTeamEmployees($pdo);
if(DEBUG > 1) { print("templates.php: team_employees = ".print_r($team_employees, true)."<br/>\n"); }

$teamschedule_templates = $team->getTeamScheduleTemplates($pdo=$pdo, $desc=false);
if(DEBUG > 1) { print("templates.php: teamschedule_templates = ".print_r($teamschedule_templates, true)."<br/>\n"); }

if(!empty($teamschedule_templates)) {
	$new_days_offset = $teamschedule_templates[\array_key_last($teamschedule_templates)]
				->days_offset + $team->ndays;
	$new_start_time = $teamschedule_templates[\array_key_last($teamschedule_templates)]
				->start_time;
} else {
	$new_days_offset = (!empty($team->ndays)) ? $team->ndays : 7; 
	$new_start_time = (!empty($team->start_time)) ? 
		new \DateTime("1969-12-31 $team->start_time", new \DateTimeZone("UTC")) : 
		new \DateTime("1969-12-31 13:00", new \DateTimeZone("UTC"));
}
$js_daysOffset = $new_days_offset;
$js_startTime = $new_start_time->format("H:i");
if(DEBUG > 1) { print("templates.php: js_daysOffset = $js_daysOffset; js_startTime = $js_startTime<br/>\n"); }

if(!empty($data)) { unset($data); }
//Javascript
$data = '
<script>
function applyTeamScheduleTemplates(stid) {
	console.log(`applyTeamScheduleTemplates(${stid})`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `applyTeamScheduleTemplates=${stid}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		//console.log(response);
		location.href = "schedules.php?teamid='.$teamid.'";
	});
}

function insertScheduleTemplate(new_days_offset, new_start_time, employee) {
	console.log(`insertScheduleTemplate(${new_days_offset}, ${new_start_time}, ${employee})`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `insertScheduleTemplate=${new_days_offset}&new_start_time=${new_start_time}&employee=${employee}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		location.href = response.url;
	});
	//.then((response) => {
	//	console.log(`RESPONSE.URL ${response.url}`);
	//});
}

function addScheduleTemplate(new_days_offset, new_start_time) {
	console.log(`addScheduleTemplate(${new_days_offset}, ${new_start_time})`);
	var addScheduleTemplate_form = `<h3>Append Template</h3>
<form formaction="templates.php" method="post" id="addScheduleTemplate_form">
<input type="hidden" name="addScheduleTemplate" value="1" />
<table>
  <tr>
    <th>Days Offset</th>
    <th>Start Time</th>
    <th>Employee</th>
    <th>Call Order</th>
  </tr>
  <tr>
    <td>
      <input type="number" name="new_days_offset" value="${new_days_offset}" min="1" step="1" required />
    </td>
    <td>
      <input type="time" name="new_start_time" value="${new_start_time}" step=\"60\" required />
    <td>
      <select name="new_employee" required>
';
foreach($team_employees as $employee) {
	if($employee->active) {
		$data .= "        <option value=\"".$employee->id."\">"
			.$employee->getEmployeeName()."</option>\n";
	}
}
$data .=
'      </select>
    </td>
    <td>
      <input type="number" name="new_call_order" class="call-order" value="1" min="1" step="1" required />
    </td>
  </tr>
  <tr>
    <td colspan="4" align="right">
      <input type="submit" value="Save" />
      <button type="button" onclick="closeAddScheduleTemplate()">Close</button>
    </td>
  </tr>
</table>
</form>
`;
	addScheduleTemplate_dialog = document.getElementById("addScheduleTemplate_dialog");
	addScheduleTemplate_dialog.innerHTML = addScheduleTemplate_form;
	addScheduleTemplate_dialog.show();
}

function closeAddScheduleTemplate() {
	document.getElementById("addScheduleTemplate_form").reset();
	document.getElementById("addScheduleTemplate_dialog").close();
}

function disableScheduleTemplate(stid) {
	console.log(`DISABLE SCHEDULE TEMPLATE ${stid}`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `disable_scheduleTemplate=${stid}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
		console.log("Redirect to '.$request_url.'");
		location.href = "'.$request_url.'";
	});
}

function enableScheduleTemplate(stid) {
	console.log(`ENABLE SCHEDULE TEMPLATE ${stid}`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `enable_scheduleTemplate=${stid}`,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
		console.log("Redirect to '.$request_url.'");
		location.href = "'.$request_url.'";
	});
}

function addEmployee(stid) {
	console.log(`ADD SCHEDULE TEMPLATE EMPLOYEE ${stid}`);
	document.getElementById("stid").value = stid;
	document.getElementById("add_employee_dialog").showModal();
}

function closeAddEmployee() {
	console.log(`CANCEL ADD SCHEDULE TEMPLATE EMPLOYEE`);
	document.getElementById("add_employee_form").reset();
	document.getElementById("add_employee_dialog").close();
}

function removeEmployee(stid, eid) {
	console.log(`REMOVE SCHEDULE TEMPLATE EMPLOYEE stid: ${stid} eid: ${eid}`);
	fetch("'.$request_url.'", {
		method: "POST",
		body: `remove_employee=${eid}&stid=${stid}`,
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
	."  <h4>On-Call Templates for ".$team->name."</h4>\n"
	."  <pre>NOTE: Search is for schedule template by ID or \"name\" (teamid:days_offset)\n"
	."EXAMPLES) 57, 1:91</pre>\n" 
	."</blockquote>\n";

//$today = new \DateTime("now", new \DateTimeZone("UTC"));
$data .= $team_select
	."<br/>\n"
	."<div style=\"display:inline-block\">\n"
	."  <button type=\"button\" name=\"addScheduleTemplate_btn\" "
		."onclick=\"addScheduleTemplate($js_daysOffset, '$js_startTime')\">"
		."Append Template"
	."  </button>\n"
	."</div>\n"
	."<div style=\"display:inline-block\">\n"
	."  <button type=\"button\" name=\"applyTeamScheduleTemplates_btn\" "
		."onclick=\"applyTeamScheduleTemplates($teamid)\">"
		."Apply Templates"
	."  </button>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"schedules.php?teamid=$teamid\">Schedules</a>\n"
	."</div>\n"
	."<div class=\"btn\">\n"
	."  <a href=\"teams.php?teamid=$teamid\">Team</a>\n"
	."</div>\n";

//$data .= "<div>\n"
//        ."<form action=\"$request_url\" method=\"get\">\n"
//        ."  <input type=\"hidden\" name=\"teamid\" value=\"$teamid\" />\n"
//        ."  Active <input type=\"radio\" id=\"active\" name=\"active\" value=\"1\" ";
//if($display_active) { $data .= "checked "; }
//$data .= "onchange=\"this.form.submit();\" />\n"
//        ."  All <input type=\"radio\" id=\"all\" name=\"active\" value=\"0\" ";
//if(!$display_active) { $data .= "checked "; }
//$data .= "onchange=\"this.form.submit();\" />\n"
//        ."</form>"
//        ."</div>\n";

//POST handling...
if($request_method == "POST") {
	if(DEBUG > 2) { print("templates.php: POST = ".print_r($_POST, true)."<br/>\n"); }

	if(isset($_POST['q'])) {
		//q is a search term
		if(DEBUG > 1) { print("templates.php::POST q = ".$_POST['q']."<br/>\n"); }
		$term = (\preg_match("/^[0-9]+$/", $_POST['q'])) ? (int) $_POST['q'] : (string) $_POST['q'];
		if(DEBUG > 1) { print("templates.php::POST term = ".print_r($term, true)."<br/>\n"); }
		if(is_int($term)) {
			header("Location: template.php?id=$term");
		} else if(\preg_match("/^[0-9]+:[0-9]+$/", $term)) {
			header("Location: template.php?name=$term");
		} else {
			//header("Location: template.php?teamid=$team->id&name=$team->id:$term");
			throw new \Exception("templates.php ERROR! Invalid search term provided.", 300);
		}
	} else if(isset($_POST['applyTeamScheduleTemplates'])) {
		if(DEBUG > 1) {
			print("templates.php::POST applyTeamScheduleTemplates = "
				.$_POST['applyTeamScheduleTemplates']."<br/>\n");
		}

		$apply = (int) $_POST['applyTeamScheduleTemplates'];

		if($apply != $team->id || $apply != $teamid) {
			throw new \Exception("templates.php::POST "
				."applyTeamScheduleTemplate[$apply] != team.id[$team->id] "
				."|| != teamid[$teamid]");
		}

		try {
			$team->applyTeamScheduleTemplates($pdo);
		} catch(\Exception $e) {
			$data .= displayError($e->getCode());
			print($data);
			require_once('./footer.php');
			die($e->getCode());
		}
	} else if(isset($_POST['insertScheduleTemplate'])) {
		//Inserting a new schedule template anywhere in the current schedule templates, including appends
		$days_offset = (int) $_POST['insertScheduleTemplate'];
		$start_time = (!empty($_POST['new_start_time'])) ? 
			new \DateTime("1969-12-31 ".$_POST['new_start_time'], new \DateTimeZone("UTC")) :
			new \DateTime("1969-12-31 ".$team->start_time, new \DateTimeZone("UTC"));
		$employee_id = (int) $_POST['employee'];
		if(DEBUG > 1) {
			print("templates.php::POST.insertScheduleTemplate: days_offset = $days_offset<br/>\n");
			print("templates.php::POST.insertScheduleTemplate: start_time = "
				.print_r($start_time, true)."<br/>\n");
			print("templates.php::POST.insertScheduleTemplate: employee_id = $employee_id<br/>\n");
		}
		$scheduleTemplate = new TeamScheduleTemplate();
		$scheduleTemplate->team_id = $teamid;
		$scheduleTemplate->days_offset = $days_offset;
		$scheduleTemplate->start_time = $start_time;
		$scheduleTemplate->employees = array(array('call_order' => 1, 'employee' => $employee_id));
		$scheduleTemplate->active = true;
		$scheduleTemplate->save($pdo);
		header("Location: $request_url");
	} else if(!empty($_POST['disable_scheduleTemplate'])) {
		//Disabling a current schedule template
		$scheduleTemplate_id = (int) $_POST['disable_scheduleTemplate'];
		if(DEBUG > 1) {
			print("templates.php::POST disable_scheduleTemplate = $scheduleTemplate_id<br/>\n");
		}
		$scheduleTemplate = TeamScheduleTemplate::getTeamScheduleTemplate($pdo, $scheduleTemplate_id);
		if(DEBUG > 1) {
			print("templates.php::POST.disable_scheduleTemplate scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		$scheduleTemplate->active = false;
		$scheduleTemplate->save($pdo);
		if(DEBUG > 1) {
			print("templates.php::POST.disable_scheduleTemplate scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		unset($scheduleTemplate);
		unset($scheduleTemplate_id);
	} else if(!empty($_POST['enable_scheduleTemplate'])) {
		//TODO Deprecated: No longer relevant as we moved inactive schedules to the all_templates.php page
		//Enabling a disabled current schedule
		$scheduleTemplate_id = (int) $_POST['enable_scheduleTemplate'];
		if(DEBUG > 1) { print("templates.php::POST enable_scheduleTemplate = $scheduleTemplate_id<br/>\n"); }
		$scheduleTemplate = TeamScheduleTemplate::getTeamScheduleTemplate($pdo, $scheduleTemplate_id);
		if(DEBUG > 1) {
			print("templates.php::POST.enable_scheduleTemplate scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		$scheduleTemplate->active = true;
		$scheduleTemplate->save($pdo);
		if(DEBUG > 1) {
			print("templates.php::POST.enable_scheduleTemplate scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		unset($scheduleTemplate);
		unset($scheduleTemplate_id);
	} else if(!empty($_POST['add_employee'])) {
		//Adding an employee/call_order to an existing scheduleTemplate
		$scheduleTemplate_id = (int) $_POST['template_id'];
		if(DEBUG > 1) {
			print("templates.php::POST add_employee template_id = $scheduleTemplate_id<br/>\n");
		}
		$scheduleTemplate = TeamScheduleTemplate::getTeamScheduleTemplate($pdo, $scheduleTemplate_id);
		$employee_id = (int) $_POST['add_employee'];
		$call_order = (int) $_POST['add_call_order'];
		$new_employees = $scheduleTemplate->employees;
		$new_employees[] = array('employee' => $employee_id, 'call_order' => $call_order);
		$scheduleTemplate->employees = $new_employees;
		$scheduleTemplate->save($pdo);
	} else if(!empty($_POST['remove_employee'])) {
		//Removing an employee/call_order from an existing schedule
		if(empty($_POST['stid'])) {
			throw new \Exception("ERROR! templates.php::POST.remove_employee "
					    ."(scheduleTemplate) id is EMPTY");
		}//END if empty POST[(scheduleTemplate) id]
		
		$employee_id = (int) $_POST['remove_employee'];
		$scheduleTemplate_id = (int) $_POST['stid'];
		if(DEBUG > 1) {
			print("templates.php::POST remove_employee = employee_id = $employee_id : "
				."scheduleTemplate_id = $scheduleTemplate_id<br/>\n");
		}

		$scheduleTemplate = TeamScheduleTemplate::getTeamScheduleTemplate($pdo, $scheduleTemplate_id);
		if(DEBUG > 1) {
			print("templates.php::POST.remove_employee scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		$scheduleTemplate_employees = $scheduleTemplate->employees;
		if(DEBUG > 1) {
			print("templates.php::POST.remove_employee scheduleTemplate_employees = "
				.print_r($scheduleTemplate_employees, true)."<br/>\n");
		}
		foreach($scheduleTemplate_employees as $key => $scheduleTemplate_employee) {
			if(DEBUG > 1) {
				print("templates.php::POST.remove_employee key = $key, "
					."scheduleTemplate_employee = "
					.print_r($scheduleTemplate_employee, true)."<br/>\n");
				print("templates.php::POST.remove_employee scheduleTemplate_employee[employee] = "
					.$scheduleTemplate_employee['employee']."<br/>\n");
			}
			if($scheduleTemplate_employee['employee'] == $employee_id) {
				$scheduleTemplate_employees[$key]['call_order'] = 0;
			}
		}
		unset($key);
		if(DEBUG > 1) {
			print("templates.php::POST.remove_employee schedule_employees = "
				.print_r($schedule_employees, true)."<br/>\n");
		}
		$scheduleTemplate->employees = $scheduleTemplate_employees;
		
		if(DEBUG > 1) {
			print("templates.php::POST.remove_employee scheduleTemplate PRE SAVE = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		$scheduleTemplate = $scheduleTemplate->save($pdo);
		if(DEBUG > 1) {
			print("templates.php::POST.remove_employee scheduleTemplate POST SAVE = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		$scheduleTemplate = TeamScheduleTemplate::getTeamScheduleTemplate($pdo, $scheduleTemplate->id);
		$scheduleTemplate_employees = $scheduleTemplate->employees;
		//$scheduleTemplate->getTeamScheduleTemplateEmployees();
		if(DEBUG > 1) {
			print("templates.php::POST.remove_employee scheduleTemplate_employees = "
				.print_r($scheduleTemplate_employees, true)."<br/>\n");
		}

		//Free the memory
		unset($employee_id);
		unset($scheduleTemplate);
		unset($scheduleTemplate_id);
		unset($scheduleTemplate_employees);
	} else if(!empty($_POST['addScheduleTemplate'])) {
		//Appending a new schedule template (Last template.days_offset + days_offset)
		if(DEBUG > 1) {
			print("templates.php::POST addScheduleTemplate = "
				.print_r($_POST['addScheduleTemplate'], true)."<br/>\n");
		}

		$days_offset = (!empty($_POST['new_days_offset'])) ? 
			(int) $_POST['new_days_offset'] :
			(int) $new_days_offset;
		$start_time = (!empty($_POST['new_start_time'])) ? 
			new \DateTime("1969-12-31 ".$_POST['new_start_time'], new \DateTimeZone("UTC")) :
			new \DateTime("1969-12-31 ".$team->start_time, new \DateTimeZone("UTC"));
		//$employee = Employee::getEmployee($pdo, $_POST['new_employee']);
		$employee = (int) $_POST['new_employee'];
		$call_order = (int) $_POST['new_call_order'];
		$scheduleTemplate_employees = array("0" => array('employee' => $employee,
								'call_order' => $call_order));
		$teamid = (int) $_REQUEST['teamid'];
		if(DEBUG > 1) {
			print("templates.php::POST.addScheduleTemplate days_offset = $days_offset<br/>\n");
			print("templates.php::POST.addScheduleTemplate start_time = "
				.print_r($start_time, true)."<br/>\n");
			print("templates.php::POST.addScheduleTemplate new_employee = $employee<br/>\n");
			print("templates.php::POST.addScheduleTemplate new_call_order = $call_order<br/>\n");
			print("templates.php::POST.addScheduleTemplate scheduleTemplate_employees = "
				.print_r($scheduleTemplate_employees, true)."<br/>\n");
			print("templates.php::POST.addScheduleTemplate teamid = $teamid<br/>\n");
		}

		//Let's check to see if a schedule template already exists...
		//	DB.teamschedule_template UNIQUE KEY (team_id, days_offset)
		$scheduleTemplate = $team->getTeamScheduleTemplate($pdo, $days_offset);
		//...otherwise, we can make a new one
		if(empty($scheduleTemplate)) { $scheduleTemplate = new TeamScheduleTemplate(); }
		if(DEBUG > 1) {
			print("templates.php::POST.addScheduleTemplate: scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}

		//NOTE: If a schedule template exists, we're overwriting it with the provided data
		//	This will reset the active & employee/call_order stuff
		//	but team_id & days_offset were already set anyways
		$scheduleTemplate->team_id = $teamid;
		$scheduleTemplate->days_offset = $days_offset;
		$scheduleTemplate->start_time = $start_time;
		$scheduleTemplate->employees = $scheduleTemplate_employees;
		$scheduleTemplate->active = true;
		if(DEBUG > 1) {
			print("templates.php::POST.addScheduleTemplate: scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		$scheduleTemplate->save($pdo);
		if(DEBUG > 2) {
			print("templates.php::POST.addScheduleTemplate: Post scheduleTemplate->save() = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}

		//Free the memory
		unset($call_order);
		unset($employee);
		unset($days_offset);
		unset($start_time);
		unset($scheduleTemplate);
		unset($scheduleTemplate_employees);
		unset($teamid);

		if(DEBUG > 2) {
			$templates = $team->getTeamScheduleTemplates($pdo=$pdo);
			print("templates.php::POST.add_ScheduleTemplate: team->getTeamScheduleTemplates = "
				.print_r($templates, true)."<br/>\n");
			unset($templates);
		}

		//Reload the page to display the new schedule
		header("Location: $request_url");
		exit();
	} else if(!empty($_POST['stid'])) {
		//Otherwise, stid indicates modifying an existing schedule
		if(!empty($scheduleTemplate)) { unset($scheduleTemplate); }
		$stid = (int) $_POST['stid'];
		if(DEBUG > 1) { print("templates.php::POST.stid(Modify): stid = $stid<br/>\n"); }

		$days_offset = (int) $_POST['days_offset'];
		$start_time = (string) $_POST['start_time'];
		if(DEBUG > 1) {
			print("templates.php::POST.stid(Modify): "
				."days_offset = $days_offset; start_time = $start_time<br/>\n");
		}
		$scheduleTemplate = $team->getTeamScheduleTemplate($pdo, $days_offset);
		if(empty($scheduleTemplate)) {
			$scheduleTemplate = TeamScheduleTemplate::getTeamScheduleTemplate($pdo, $stid);
		}
		if(DEBUG > 1) {
			print("templates.php::POST.stid(Modify.Pre): scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		$scheduleTemplate->days_offset = $days_offset;
		$scheduleTemplate->start_time = new \DateTime("1969-12-31 $start_time", new \DateTimeZone("UTC"));

		//Employee updates...
		// get the current schedule employees so we can trim as needed
		$scheduleTemplate_employees = $scheduleTemplate->employees;
		if(DEBUG > 1) {
			print("templates.php::POST.stid(Mdodify.Pre): scheduleTemplate_employees = "
				.print_r($scheduleTemplate_employees, true)."<br/>\n");
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
				//$employee = Employee::getEmployee($pdo, $employee_id);
				//$post_employees[] = array("employee" => $employee,
				//			    "call_order" => $call_order);
				$iter++;
				unset($call_order);
				unset($employee_id);
			}
		}
		unset($key);
		unset($iter);
		if(DEBUG > 1) {
			print("templates.php::POST.stid(Modify.Pre): post_employees = "
				.print_r($post_employees, true)."<br/>\n");
		}

		// list of post employee IDs for quick search
		//TODO Good application of \array_walk?
		$post_employeeids = array();
		foreach($post_employees as $key => $template_employee) {
			$post_employeeids[] = $template_employee['employee'];
		}
		unset($key);
		unset($template_employee);

		//Check the existing schedule employees
		foreach($scheduleTemplate_employees as $scheduleTemplate_employee) {
			//If they're not in the POST, they're not on the schedule template...
			if(!\in_array($scheduleTemplate_employee['employee'], $post_employeeids)) {
				//Set call_order == 0 to delete when saved
				$post_employees[] = array("employee" => $scheduleTemplate_employee['employee'],
							  "call_order" => 0);
			}
		}
		unset($scheduleTemplate_employee);
		if(DEBUG > 1) {
			print("templates.php::POST.stid(Modify.Pre): post_employees = "
				.print_r($post_employees, true)."<br/>\n");
			print("templates.php::POST.stid(Modify.Pre): post_employeeids = "
				.print_r($post_employeeids, true)."<br/>\n");
		}
		$scheduleTemplate->employees = $post_employees;

		if(DEBUG > 1) {
			print("templates.php::POST.stid(Modify.Post): scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}

		//Save the changes...
		$scheduleTemplate->save($pdo);
		//Refresh the schedule template (employee changes)
		$scheduleTemplate = TeamScheduleTemplate::getTeamScheduleTemplate($pdo, $scheduleTemplate->id);
		if(DEBUG > 1) {
			print("templates.php::POST.stid(Modify.Post): UPDATE Refresh scheduleTemplate = "
				.print_r($scheduleTemplate, true)."<br/>\n");
		}
		//Free the memory
		unset($post_employees);
		unset($post_employeeids);
		unset($scheduleTemplate);
		unset($scheduleTemplate_employees);
		unset($days_offset);
		unset($start_time);
		header("Location: $request_url");
	} //END POST Method
}//END POST

$data .= "<table style=\"width:100%;white-space:nowrap;\">\n"
	."<thead>\n"
	."  <tr>\n"
	//."    <th style=\"width:5em\">ID</th>\n"
	."    <th>Days Offset</th>\n"
	."    <th>Start Time</th>\n"
	//."    <th>Actions</th>\n"
	."    <th>On-Call Person</th>\n"
	."    <th>Call Order</th>\n"
	."    <th><!-- Action Buttons --></th>\n"
	."  </tr>\n"
	."</thead>\n"
	."<tbody>\n";

if(!empty($employees)) { unset($employees); }
if(!empty($employee)) { unset($employee); }
if(!empty($template)) { unset($template); }
if(!empty($team_userids)) { unset($team_userids); }

//If we don't have any templates, let's just close the table out & display the page
if(empty($teamschedule_templates)) {
	$data .= "</tbody>\n"
		."</table>\n"
		."<dialog id=\"addScheduleTemplate_dialog\" type=\"modulo\"></dialog>";
	print($data);
	require_once('./footer.php');
	exit;
}

$templates_count = \count($teamschedule_templates);
foreach ($teamschedule_templates as $template) {
	if(DEBUG > 1) { print("templates.php: template = ".print_r($template, true)."<br/>\n"); }
	$employees = $template->getTeamScheduleTemplateEmployees($pdo);
	if(DEBUG > 1) { print("templates.php: employees = ".print_r($employees, true)."<br/>\n"); }

	$template_employeeids = array();
	foreach($employees as $template_employee) {
		$template_employeeids[] = $template_employee['employee'];
	}
	if(DEBUG > 1) {
		print("templates.php: template_employeeids = ".print_r($template_employeeids, true)."<br/>\n");
	}

	$days_offset = $template->days_offset;
	if(DEBUG > 1) { print("templates.php: days_offset = $days_offset<br/>\n"); }

	$data .= "  <form action=\"$request_url\" method=\"post\">\n"
		."  <input type=\"hidden\" name=\"stid\" value=\"$template->id\" />\n"
		."  <tr>\n"
		//."    <td><a href=\"template.php?id=$template->id\">$template->id</a></td>\n"
		."    <td>\n"
		."      <input name=\"days_offset\" onchange=\"this.form.submit()\" "
				."type=\"number\" step=\"1\" "
				."value=\"$days_offset\" required />\n"
		."    </td>\n";

	$start_time = $template->start_time->format("H:i");
	if(DEBUG > 1) { print("templates.php: start_time = $start_time<br/>\n"); }

	$data .= "    <td>\n"
		."      <input name=\"start_time\" onchange=\"this.form.submit()\" "
				."type=\"time\" step=\"60\" "
				."value=\"$start_time\" required />\n"
		."    </td>\n";

	//$data .= "    <td>\n";
	//if($template->active && $templates_count > 1) {
	//	$data .= "      <button type=\"button\" onclick=\"disableScheduleTemplate($template->id)\">"
	//			."Disable</button>\n";
	//} else if(!$template->active) {
	//	$data .= "      <button type=\"button\" onclick=\"enableScheduleTemplate($template->id)\">"
	//			."Enable</button>\n";
	//}
	//$data .= "    </td>\n";
	foreach ($employees as $key => $template_employee) {
		if($key > 0) {
			$data .= "    <tr>\n"
				."      <td colspan=\"2\"></td>\n";
		}
		$employee_id = $template_employee['employee'];
		$employee = Employee::getEmployee($pdo, $employee_id);
		$call_order = $template_employee['call_order'];
		$name = $employee->getEmployeeName();
		$data .= "    <td>\n"
			."      <select name=\"employee$key\" onchange=\"this.form.submit()\">\n"
			."        <option value=\"$employee->id\" selected=\"selected\">$name</option>\n";

		foreach ($team_employees as $team_employee) {
			if($team_employee->active && !\in_array($team_employee->id, $template_employeeids)) {
				$data .= "        <option value=\"$team_employee->id\">"
						.$team_employee->getEmployeeName()."</option>\n";
			} else { continue; }
		}

		$data .= "      </select>\n"
			."    </td>\n"
			."    <td>\n"
			."      <input type=\"number\" class=\"call-order\" onchange=\"this.form.submit()\" "
				."name=\"call_order$key\" min=\"1\" max=\"100\" value=\"$call_order\" "
				."step=\"1\" required />\n"
			."    </td>\n"
			."    <td align=\"right\">\n";

		//Attribution required:
		//User icons created by Freepik - Flaticon
		//	https://www.flaticon.com/free-icon/new-user_72648
		//	https://www.flaticon.com/free-icon/remove-user_72830
		//Time and date icons created by Irfansusanto20 - Flaticon
		//	https://www.flaticon.com/free-icon/calendar_4218765
		//	https://www.flaticon.com/free-icon/calendar_4218858
		$js_daysOffset = ((int) $template->days_offset + 1);
		$js_startTime = (string) $template->start_time->format("H:i");
		$eid = $employees[0]['employee'];
		if(DEBUG > 1) {
			print("templates.php: js_daysOffset = $js_daysOffset; "
				."js_startTime = $js_startTime; "
				."eid = $eid<br/>\n");
		}
		if($key == \array_key_first($employees)) {
			$data .= "        <button type=\"button\" class=\"bimg\" "
						."onclick=\"insertScheduleTemplate($js_daysOffset, "
										."'$js_startTime', "
										."$eid)\" "
						."alt=\"Insert Template\" title=\"Insert a new template\">"
					."<img src=\"new-schedule.png\" style=\"margin:0\" /></button>\n";
		}
		//TODO There's a bug here somewhere... Disable doesn't appear to work
		//if($key == \array_key_first($employees) && $templates_count > 1) {
		//	$data .= "        <button type=\"button\" class=\"bimg\" "
		//				."onclick=\"disableScheduleTemplate($template->id)\" "
		//				."alt=\"Disable Template\" title=\"Disable template\">"
		//			."<img src=\"disable-schedule.png\" style=\"margin:0\" /></button>\n";
		//}
		if(\count($employees) < \count($team_employees)) {
			$data .= "        <button type=\"button\" class=\"bimg\" "
						."onclick=\"addEmployee($template->id)\" "
						."alt=\"Add Backup\" title=\"Add backup\">"
					."<img src=\"add-user.png\" style=\"margin:0\" /></button>\n";
		}
		if(\count($employees) > 1) {
			$data .= "        <button type=\"button\" class=\"bimg\" "
						."onclick=\"removeEmployee($template->id, $employee->id)\" "
						."alt=\"Remove Backup\" title=\"Remove backup\">"
					."<img src=\"remove-user.png\" style=\"margin:0\" /></button>\n";
		}
		$data .= "    </td>\n"
			."  </tr>\n";
	}
	$data .= "  </form>\n";
}//END foreach(teamschedule_templates)

$data .= "</table>\n\n"
	."<dialog id=\"addScheduleTemplate_dialog\" type=\"modulo\"></dialog>";

//$team = Team::getTeam($pdo, $schedule->team_id);
//$team_employees = Employee::getEmployees($pdo, $team->employees);
//print("templates.php: team_employees = ".print_r($team_employees, true)."<br/>\n");

//TODO Fix Add Employee
//Add Employee Modal
$modal = "<dialog id=\"add_employee_dialog\" autofocus>\n"
	."<form id=\"add_employee_form\" formaction=\"$request_url\" method=\"post\">\n"
	."<input type=\"hidden\" name=\"template_id\" id=\"stid\" value=\"0\" />\n"
	."<h3>Add Employee (Template $template->id)</h3>\n"
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
	if($employee->active){ // && !\in_array($employee->id, $template_employeeids)) {
		if(DEBUG > 1) {
			print("templates.php::add_employee_dialog employee = "
				.print_r($employee, true)."<br/>\n");
		}
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
unset($pdo);
require_once("./footer.php");
?>
