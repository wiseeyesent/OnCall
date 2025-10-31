<?php
namespace WiseEyesEnt\OnCall2;

require_once('./dbconn.php');
require_once('./team_schedule.php');

require_once('./header.php');

if(empty($pdo)) { $pdo = dbConnect(); }

if(!empty($data)) { unset($data); }
if(!empty($schedule)) { unset($schedule); }
if(!empty($team)) { unset($team); }

//$post = null;
//if(!empty($request_method)) { unset($request_method); }
//$request_method = $_SERVER['REQUEST_METHOD'];
//if(!empty($request_url)) { unset($request_url); }
//$request_url = $_SERVER['REQUEST_URI'];
//print("schedule.php REQUEST_METHOD = $request_method; REQUEST_URI = $request_url<br/>\n");

$schedule_id = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$teamid = !empty($_REQUEST['teamid']) ? (int) $_REQUEST['teamid'] : 0;
$name = !empty($_REQUEST['name']) ? (string) $_REQUEST['name'] : "";

$schedule = TeamSchedule::getTeamSchedule($pdo=$pdo, $id=$schedule_id, $teamid=$teamid, $name=$name);
//if(empty($schedule)) { throw new \Exception("ERROR! schedule.php No schedule found"); }
//} catch (\Exception $e) {
if(empty($schedule)) {
	$data = "<h1>Please provide a team schedule ID or team ID.</h1><br/>\n";
	print($data);
	unset($data);
	unset($schedule);
	require_once('./footer.php');
}
//print("schedule.php: schedule = ".print_r($schedule, true)."<br/>\n");
if(empty($schedule_id)) { $schedule_id = $schedule->id; }
if(empty($teamid)) { $teamid = $schedule->team_id; }

if(!empty($team)) { unset($team); }
$team = Team::getTeam($pdo, $schedule->team_id);
//print("schedule.php: team = ".print_r($team, true)."<br/>\n");

//if(!empty($team_employee_ids)) { unset($team_employee_ids); }
//$team_employeeids = $team->employees;
//print("schedule.php: team_employeeids = ".print_r($team_employeeids, true)."<br/>\n");

if(!empty($team_employees)) { unset($team_employees); }
$team_employees = $team->getEmployeeNames($pdo, $active=true);
//print("schedule.php: team_employees = ".print_r($team_employees, true)."<br/>\n");

if(!empty($schedule_employees)) { unset($schedule_employees); }
$schedule_employees = $schedule->getActiveEmployees();

if(!empty($start_date)) { unset($start_date); }
$start_date = (string) \date_format($schedule->start_date, "Y-m-d H:i:s");
if(!empty($start_time)) { unset($start_time); }
$start_time = \date_format($schedule->start_date, "H:i:s");
//print("schedule.php: start_time = ".print_r($start_time, true)."<br/>\n");

if(!empty($schedule_employeeids)) { unset($schedule_employeeids); }
$schedule_employeeids = array();
foreach ($schedule_employees as $schedule_employee) {
	$schedule_employeeids[] = $schedule_employee['employee'];
}
//print("schedule.php: schedule_employeeids = ".print_r($schedule_employeeids, true)."<br/>\n");

//print("schedule.php: request_method = $request_method<br/>\n");
//print("schedule.php: REQUEST = ".print_r($_REQUEST, true)."<br/>\n");
if($request_method == "POST") {
	//print("schedule.php POST = ".print_r($_POST, true)."<br/>\n");
	if(isset($_POST['q'])) {
		//name format: [teamid:]YYYY-mm-dd[ H:i:s]
		$term = (\preg_match("/^[0-9]+$/", $_POST['q'])) ? (int) $_POST['q'] : (string) $_POST['q'];
		if(is_int($term)) {
			//integer == DB ID
			header("Location: schedule.php?id=$term");
		} else {
			//string == schedule name ([teamid]:YYYY-mm-dd H:i:s)
			if(!\preg_match("/^[0-9]+:[0-9]+-/", $term)) { $term = "$teamid:$term"; }
			header("Location: schedule.php?name=$term");
		}
	}

	if(!empty($_POST['start_date'])) { $start_date = $_POST['start_date']; }
	$new_start = new \DateTime($start_date, new \DateTimeZone("UTC"));
	//print("schedule.php start_date = ".print_r($new_start, true)."<br/>\n");

	//If a schedule already exists with matching team_id & start_date, let's just load that one instead
	$schedule_exists = $team->getTeamSchedule($pdo, $new_start);
	if(!empty($schedule_exists)) { $schedule = $schedule_exists; $request_url = "schedule.php?id=$schedule->id"; }
	$schedule->start_date = $new_start;

	if(isset($_POST['active'])) {
		//print("schedule.php::POST active = ".$_POST['active']."<br/>\n");
		$schedule->active = (bool) $_POST['active'];
		//print("schedule.php::POST schedule.active = ".$schedule->active."<br/>\n");
	}

	if(!empty($new_employees)) { unset($new_employees); }
	if(!empty($_POST['remove_employee'])) {
		$new_employees = $schedule->employees;
		foreach ($new_employees as $key => $employee) {
			if($employee['employee'] == $_POST['remove_employee']) {
				$new_employees[$key]['call_order'] = 0;
			}
		}
	} else if(!empty($_POST['new_call_order'])) {
		if(empty($_POST['new_employee'])) {
			throw new \Exception("ERROR! schedule.php::POST.ADD new_employee EMPTY");
			//print("schedule.php::POST.ADD new_employee EMPTY, reload the page<br/>\n");
			//header("Location: $request_url");
			//exit();
		}
		//print("schedule.php::POST.ADD new_employee = ".$_POST['new_employee']." "
		//	."new_call_order = ".$_POST['new_call_order']."<br/>\n");
		$new_employees = $schedule->employees;
		$new_employee = (int) $_POST["new_employee"];
		$new_employees[] = array("employee" => $new_employee,
					 "call_order" => (int) $_POST['new_call_order']);
	} else if(!empty($_POST['call_order0'])) {
		if(empty($_POST['employee0'])) {
			throw new \Exception("ERROR! schedule.php::POST.UPDATE employee0 EMPTY");
			//print("schedule.php::POST.UPDATE employee0 EMPTY, reload the page<br/>\n");
			//header("Location: $request_url");
			//exit();
		}
		//print("schedule.php::POST.UPDATE employee0 = ".$_POST['employee0']." "
		//	."call_order0 = ".$_POST['call_order0']."<br/>\n");
		$old_employees = $schedule->employees;
		$new_employees = array();
		$iter=0;
		while(!empty($_POST["employee$iter"])) {
			//print("schedule.php::POST.UPDATE POST[employee$iter] : POST[call_order$iter] = "
			//	.$_POST["employee$iter"]." : ".$_POST["call_order$iter"]."<br/>\n");
			unset($new_employee);
			unset($new_call_order);
			$new_employee = (int) $_POST["employee$iter"];
			//$new_employee = Employee::getEmployee($pdo, $_POST["employee$iter"]);
			$new_call_order = (int) $_POST["call_order$iter"];
			//print("schedule.php::POST.UPDATE new_employee = $new_employee<br/>\n");
			//print("schedule.php::POST.UPDATE new_call_order = $new_call_order<br/>\n");
			$new_employees[] = array("employee" => $new_employee, "call_order" => $new_call_order);
			$iter++;
		}
		$schedule_employeeids = array();
		foreach ($new_employees as $new_employee) {
			$schedule_employeeids[] = $new_employee['employee'];
		}
		//print("schedule.php::POST.UPDATE schedule_employeeids = "
		//	.print_r($schedule_employeeids, true)."<br/>\n");
		foreach ($old_employees as $old_employee) {
			//print("schedule.php::POST old_employee = "
			//	.print_r($old_employee, true)."<br/>\n");
			if(!\in_array($old_employee['employee'], $schedule_employeeids)) {
				$new_employees[] = array('call_order' => 0,
							 'employee' => $old_employee['employee']);
			}
		}
	}
	//TODO sort new_employees by []['call_order']
	//print("schedule.php: new_employees = ".print_r($new_employees, true)."<br/>\n");
	$schedule->employees = $new_employees;
	//print("schedule.php: schedule = ".print_r($schedule, true)."<br/>\n");
	$schedule->save($pdo);
	//print("schedule.php: schedule.save() = ".print_r($schedule, true)."<br/>\n");
	$schedule_employees = $schedule->getTeamScheduleEmployees($pdo);
	unset($schedule_employeeids);
	$schedule_employeeids = array();
	foreach ($schedule_employees as $key => $schedule_employee) {
		$schedule_employeeids[] = $schedule_employee['employee'];
	}
	//print("schedule.php::POST: schedule_employeeids = ".print_r($schedule_employeeids, true)."<br/>\n");
	header("Location: $request_url");
}

$data = "<script>\n"
.
'function add_employee() {
	console.log("add_employee()");
	var add_employee_form = `<form formaction="schedule.php" method="post">
<table>
  <tr><th>Add Employee</th></tr>
  <tr>
    <td>
      <label for="new_call_order">New Call Order</label>
      <input type="number" name="new_call_order" value="1" min="1" />
    </td>
    <td>
      <label for="new_employee">New Employee</label>
      <select name="new_employee">
';
//print("schedule.php: team_employeeids = ".print_r($team_employeeids, true)."<br/>\n");
//print("schedule.php: schedule_employeeids = ".print_r($schedule_employeeids, true)."<br/>\n");
foreach($team_employees as $id => $name) {
	//print("schedule.php: team_employee.id:name = $id:$name<br/>\n");
	$in_array = (bool) \in_array($id, $schedule_employeeids);
	//$in_array_str = $in_array ? "TRUE" : "FALSE";
	//print("schedule.php::form.add_employee: in_array = $in_array_str<br/>\n");
	if($in_array) {
		//print("schedule.php::form.add_employee: Skipping $id:$name<br/>\n");
		continue;
	}
	$option_str = "        <option value=\"$id\">$name</option>\n";
	$data .= $option_str;
	unset($option_str);
}
//print("schedule.php::form.add_employee: schedule_employeeids = "
//	.print_r($schedule_employeeids, true)."<br/>\n");
//print("schedule.php::form.add_employee: team_employeeids = ".print_r($team_employeeids, true)."<br/>\n");
$data .= 
'      </select>
    </td>
    <td>
      <input type="submit" value="Save" />
    </td>
  </tr>
</table>
</form>
`;
	document.getElementById("add_employee").innerHTML = add_employee_form;
}

function remove_employee(id) {
	console.log("remove_employee("+id+")");
	document.getElementById("add_employee").innerHTML = `<h3>REMOVE USER ID ${id}</h3>`;
	fetch("schedule.php?id='.$schedule->id.'", {
		method: "POST",
		body: "remove_employee="+id,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
		location.href = "'.$request_url.'";
		return response.text();
	});
	//.then((html) => {
	//	document.body.innerHTML = html;
	//});
	console.log("'.$request_url.'");
	//location.href = "'.$request_url.'";
}
</script>

<blockquote>
  <h1>On-Call Schedule '.$schedule_id.'</h1>
  <h3>'.$team->name.' ('.$team->id.':'.$schedule->start_date->format("Y-m-d").')</h3>
  <pre>NOTE: Search is by id, date, or "name" (teamid:YYYY-MM-DD[ HH:mm])
EXAMPLE) 25364, 2024-01-08, 11:2024-01-08, 11:2024-01-08 00:00</pre>
</blockquote>
';

//print("schedule.php: schedule = ".print_r($schedule, true)."<br/>\n");
$is_old = (bool) $schedule->isOld($pdo);
//$old_str = $is_old ? "TRUE" : "FALSE";
//print("schedule.php: schedule.is_old = $old_str<br/>\n");
if($is_old) { $data .= "<h3>Schedule is outdated and cannot be changed</h3>\n"; }
$data .= "<form formaction=\"schedule.php\" method=\"post\">\n"
	."<table>\n"
	."  <tr>\n"
	."    <th>Schedule ID</th><td>".$schedule->id."</td>\n"
	."  </tr>\n"
	."  <tr>\n"
	."    <th>Active</th>\n"
	."    <td>\n"
	."      <input type=\"radio\" name=\"active\" value=\"1\" ";
if($schedule->active) { $data .= "checked "; }
if($is_old) { $data .= "disabled "; }
$data .= "onchange=\"this.form.submit()\" />\n";
$data .= "    </td>\n"
	."  </tr>\n"
	."  <tr>\n"
	."    <th>Inactive</th>\n"
	."    <td>\n"
	."      <input type=\"radio\" name=\"active\" value=\"0\" ";
if(!$schedule->active) { $data .= "checked "; }
if($is_old) { $data .= "disabled "; }
$data .= "onchange=\"this.form.submit()\" />\n"
	."    </td>\n"
	."  </tr>\n"
	."  <tr>\n"
	."    <th>Start Date</th>\n"
	."    <td><input type=\"datetime-local\" name=\"start_date\" value=\"$start_date\" ";
if($is_old) { $data .= "disabled "; }
$today = new \DateTime("now", new \DateTimeZone("UTC"));
$data .=	"onchange=\"this.form.submit()\" />"
	."    </td>\n"
	."  </tr>\n"
	."  <tr>\n"
	."    <th>On-Call Team (ID)</th>\n"
	."    <td>\n"
	."      <a href=\"./teams.php?teamid=$team->id\">$team->name ($team->id)</a>\n"
	."    </td>\n"
	."  </tr>"
	."  <tr>\n"
	."    <th>Schedules</th>\n"
	."    <td>\n"
	."      <a href=\"./schedules.php?teamid=$team->id\">Current</a>&nbsp|"
	."      <a href=\"./all_schedules.php?teamid=$team->id&month=".$today->format("Y-m-d")."\">Month</a>\n"
	."    </td>\n"
	."  </tr>\n"
	."  <tr>\n"
	."    <th>Employees</th>\n";

if(!empty($iter)) { unset($iter); }
$iter = 0;
//print("schedule.php: schedule_employees = ".print_r($schedule_employees, true)."<br/>\n");
//print("schedule.php: schedule_employeeids = ".print_r($schedule_employeeids, true)."<br/>\n");
if(empty($schedule_employees)) {
	$data .= "    <td>\n"
		."      <button type=\"button\" onclick=\"add_employee()\">Add</button>\n"
		."    </td>\n"
		."  </tr>\n";
} else {
foreach ($schedule_employees as $key => $schedule_employee) {
	if($iter > 0) {
		$data .= "  <tr>\n"
			."    <td></td>\n";
	}

	if(!empty($employee)) { unset($employee); }
	$employee_id = $schedule_employee['employee'];
	$employee = Employee::getEmployee($pdo, $employee_id);
	if(!empty($call_order)) { unset($call_order); }
	$call_order = (int) $schedule_employee['call_order'];

	//if($call_order < 1) { continue; }

	$data .= "    <td>\n"
		."      <label for=\"call_order$key\">Call Order</label>"
			."<input type=\"number\" name=\"call_order$key\" class=\"call-order\" "
			."onchange=\"this.form.submit()\" ";
	if($is_old) { $data .= "disabled "; }
	$data .=	"value=\"$call_order\" min=\"1\" max=\"100\" />\n"
		."    </td>\n"
		."    <td>\n"
		."      <label for=\"employee$key\">Employee</label>\n"
		."      <select name=\"employee$key\" onchange=\"this.form.submit()\" ";
	if($is_old) { $data .= "disabled "; }
	$data .=	">\n"
		."        <option value=\"$employee_id\">".$employee->getEmployeeName()."</option>\n";
	foreach($team_employees as $id => $name) {
		//print("schedule.php: id = $id : name = $name<br/>\n");
		//$result = \in_array($employee_id, $schedule_employeeids) ? "TRUE" : "FALSE";
		//print("schedule.php: in_array(employee_id, schedule_employeeids) = $result<br/>\n");
		if(\in_array($id, $schedule_employeeids)) { continue; }
		$data .= "        <option value=\"$id\">$name</option>\n";
	}
	$data .= "      </select>\n"
		."    </td>\n"
		."    <td>\n";
	if(\count($schedule_employees) < \count($team_employees) && !$is_old) {
		$data .= "      <button type=\"button\" onclick=\"add_employee()\">Add</button>\n";
	}
	if(\count($schedule_employees) > 1 && !$is_old) {
		$data .= "      <button type=\"button\" "
				."onclick=\"remove_employee($employee_id)\">"
				."Remove</button>\n";
	}

	$data .= "    </td>\n"
		."  </tr>\n";
	
	$iter++;
	//print("schedule.php: schedule_employees.iter = $iter<br/>\n");
}//END foreach(schedule_employees)
}//END !empty(schedule_employees)

//$data .= "  <tr>\n"
//	."    <td>\n"
//	."      <input type=\"submit\" value=\"Update\" />\n"
//	."    </td>\n"
//	."  </tr>\n"
//	."</table>\n"
$data .= "</table>\n"
	."</form>\n"
	."<div id=\"add_employee\"></div>\n";

$iter++;
//$data .= $add_employee_form;


print($data);
unset($data);
unset($employee);
unset($employee_id);
unset($name);
unset($schedule);
unset($schedule_employees);
unset($team_employeeids);
require_once('./footer.php');
