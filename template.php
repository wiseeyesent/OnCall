<?php
namespace WiseEyesEnt\OnCall2;

require_once('./common.php');
require_once('./dbconn.php');
require_once('./teamschedule_template.php');

require_once('./header.php');

if(empty($pdo)) { $pdo = dbConnect(); }
if(DEBUG > 1) { print("template.php: pdo = ".print_r($pdo, true)."<br/>\n"); }

if(!empty($data)) { unset($data); }
if(!empty($template)) { unset($template); }
if(!empty($team)) { unset($team); }

if(!empty($_REQUEST['q'])) {
	if(DEBUG > 1) { print("template.php: POST q = ".print_r($_POST['q'], true)."<br/>\n"); }
	$term = $_REQUEST['q'];
	if(DEBUG > 1) { print("template.php::POST.q term = ".print_r($term, true)."<br/>\n"); }

	if(\preg_match("/^[0-9]+$/", $term)) {
		$template_id = (int) $term;
		$template_name = "";
		$template_teamid = 0;
	} else if(\preg_match("/^[0-9]+:[0-9]+$/", $term)) {
		$template_id = 0;
		$template_name = (string) $term;
		$str_array = \explode(":", $term);
		$template_teamid = (int) $str_array[0];
		$days_offset = (int) $str_array[1];
	} else {
		$data = "<h1 class=\"error-msg\">ERROR! Invalid search term provided.</h1>\n";
		print($data);
		require_once('./footer.php');
		unset($data);
		unset($term);
		die(300);
	}
	if(DEBUG > 1) {
		print("template.php::POST.q template_id = $template_id; template_name = $template_name; "
			."template_teamid = $template_teamid; days_offset = $days_offset;\n"
			."str_array = ".print_r($str_array, true)."<br/>\n");
	}

	//if(!empty($url)) { unset($url); }
	//if(!empty($template_id)) {
	//	$url = "template.php?id=$template_id";
	//} else if(!empty($name)) {
	//	$template = TeamScheduleTemplate::getTeamScheduleTemplate($pdo, $id=0, $teamid=0, 
	//			$name=$name, $active=FALSE);
	//	if(!empty($template)) {
	//		$url = "template.php?id=$template->id";
	//	}
	//}
	//if(DEBUG > 1) { print("template.php::POST.q url = $url<br/>\n"); }
	//if(!empty($url)) { header("Location: $url"); exit; }

	if(!empty($template_id)) {
		$url = "template.php?id=$template_id";
	} else if(!empty($template_name)) {
		$url = "template.php?name=$template_name";
	} else {
		$data = "<h1 class=\"error-msg\">ERROR! Invalid search term provided.</h1>\n";
		print($data);
		require_once('./footer.php');
		unset($data);
		die(300);
	}

	if(DEBUG > 1) { print("template.php::q url = $url<br/>\n"); }
	header("Location: $url");
	exit();
}
//$template_id = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
//$teamid = !empty($_REQUEST['teamid']) ? (int) $_REQUEST['teamid'] : 0;
//$name = !empty($_REQUEST['name']) ? (string) $_REQUEST['name'] : "";

if(empty($template_id)) {
	$template_id = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
}
if(empty($template_teamid)) {
	$template_teamid = !empty($_REQUEST['teamid']) ? (int) $_REQUEST['teamid'] : 0;
}
if(empty($template_name)) {
	$template_name = !empty($_REQUEST['name']) ? (string) $_REQUEST['name'] : "";
}
if(DEBUG > 1) {
	print("template.php: template_id = $template_id; "
		."template_name = $template_name; template_teamid = $template_teamid<br/>\n");
}

if(!empty($template)) { unset($template); }
while(empty($template)) {
	try {
		if(empty($template_id) && empty($template_name)) {
			throw new \Exception("template.php: ERROR! "
				."template_id (REQUEST[id]) AND name (REQUEST[name]) EMPTY!", 300);
		}	
		$template = TeamScheduleTemplate::getTeamScheduleTemplate($pdo=$pdo,
				$id=$template_id, $teamid=0, $name=$template_name, $active=FALSE);
	} catch(\Exception $e) {
		if($e->getCode() == 301) {
			$str_array = \explode(":", $name);
			$name = $str_array[0].":";
			//$str_array[1] += 1;
			$name .= ++$str_array[1];
		} else {
			if(DEBUG > 1) {
				print("template.php: Exception = ".print_r($e, true)."<br/>\n");
			}
			$data = displayError($e->getCode());
			print($data);
			require_once('./footer.php');
			unset($data);
			unset($days_offset);
			unset($name);
			unset($str_array);
			unset($team);
			unset($template);
			unset($template_id);
			die($e->getCode());
		}
	}

	if(empty($template)) {
		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }

		$query = "SELECT count(*) FROM teamschedule_template WHERE team_id=$teamid";
		if(DEBUG > 1) {
			print("template.php::getTeamScheduleTemplate query = $query<br/>\n");
		}
		$result = dbQuery($pdo, $query);
		if(DEBUG > 1) {
			print("template.php::getTeamScheduleTemplate result = "
				.print_r($result, true)."<br/>\n");
		}
		if($result[0][0] <= 0) {
			$data = "<h1>No Templates Available For Team<h1/>\n";
			print($data);
			require_once('./footer.php');
			unset($data);
			unset($query);
			unset($result);
			die(300);
		}
	}
}

if(empty($template)) {
	$data = "<h1>Please provide a template ID or template name (teamID:days_offset).</h1><br/>\n";
	print($data);
	require_once('./footer.php');
	unset($data);
	exit(404);
}

if(DEBUG > 1) { print("template.php: template = ".print_r($template, true)."<br/>\n"); }
$template_id = $template->id;
$template_name = $template->getTemplateName();
$template_teamid = $template->team_id;

if(!empty($team)) { unset($team); }
$team = Team::getTeam($pdo, $template_teamid);
if(DEBUG > 1) { print("template.php: team = ".print_r($team, true)."<br/>\n"); }

if(!empty($team_employeeids)) { unset($team_employeeids); }
$team_employeeids = $team->employees;
if(DEBUG > 1) { print("template.php: team_employeeids = ".print_r($team_employeeids, true)."<br/>\n"); }

if(!empty($team_employees)) { unset($team_employees); }
$team_employees = $team->getEmployeeNames($pdo, $active=true);
if(DEBUG > 1) { print("template.php: team_employees = ".print_r($team_employees, true)."<br/>\n"); }

if(!empty($template_employees)) { unset($template_employees); }
$template_employees = $template->getActiveEmployees();
if(DEBUG > 1) { print("template.php: template_employees = ".print_r($template_employees, true)."<br/>\n"); }

if(!empty($days_offset)) { unset($days_offset); }
$days_offset = (int) $template->days_offset;
if(DEBUG > 1) { print("template.php: days_offset = $days_offset<br/>\n"); }

if(!empty($start_time)) { unset($start_time); }
$start_time = $template->start_time;
if(DEBUG > 1) { print("template.php: start_time = ".print_r($start_time, true)."<br/>\n"); }

if(!empty($template_employeeids)) { unset($template_employeeids); }
$template_employeeids = array();
foreach ($template_employees as $template_employee) {
	$template_employeeids[] = $template_employee['employee'];
}
if(DEBUG > 1) { print("template.php: template_employeeids = ".print_r($template_employeeids, true)."<br/>\n"); }

if(DEBUG > 1) {
	print("template.php: request_method = $request_method<br/>\n");
	print("template.php: REQUEST = ".print_r($_REQUEST, true)."<br/>\n");
}
if($request_method == "POST") {
	if(DEBUG > 1) { print("template.php POST = ".print_r($_POST, true)."<br/>\n"); }
	if(isset($_POST['q'])) {
		//name format: [ID|teamID[:days_offset]]
		$term = (\preg_match("/^[0-9]+$/", $_POST['q'])) ? (int) $_POST['q'] : (string) $_POST['q'];
		if(is_int($term)) {
			//integer == DB ID
			if(DEBUG > 1) { print("template.php::POST.q term IS_INT<br/>\n"); }
			header("Location: template.php?id=$term");
		} else {
			//string == template name (teamid:days_offset)
			if(!\preg_match("/^[0-9]+:[0-9]+$/", $term)) { $term = "$teamid:$term"; }
			if(DEBUG > 1) { print("template.php::POST.q term = $term<br/>\n"); }
			header("Location: template.php?name=$term");
		}
		exit();
	}

	if(!empty($_POST['days_offset'])) { $days_offset = (int) $_POST['days_offset']; }
	if(!empty($_POST['start_time'])) {
		$start_time = new \DateTime("1969-12-31 ".$_POST['start_time'], new \DateTimeZone("UTC"));
	}
	if(DEBUG > 1) {
		print("template.php::POST: days_offset = $days_offset; start_time = "
			.print_r($start_time, true)."<br/>\n");
	}

	//If a template already exists with matching team_id & days_offset, let's just load that one instead
	$template_exists = $team->getTeamScheduleTemplate($pdo, $days_offset);
	if(!empty($template_exists)) { $request_url = "template.php?id=$template_exists->id"; }
	if(!empty($teamid)) { $request_url .= "&teamid=$teamid"; }

	$template->days_offset = $days_offset;
	$template->start_time = $start_time;

	if(isset($_POST['active'])) {
		if(DEBUG > 1) { print("template.php::POST active = ".$_POST['active']."<br/>\n"); }
		$template->active = (bool) $_POST['active'];
		if(DEBUG > 1) { print("template.php::POST template.active = ".$template->active."<br/>\n"); }
	}

	if(!empty($new_employees)) { unset($new_employees); }
	if(!empty($_POST['remove_employee'])) {
		$new_employees = $template->employees;
		foreach ($new_employees as $key => $employee) {
			if($employee['employee'] == $_POST['remove_employee']) {
				$new_employees[$key]['call_order'] = 0;
			}
		}
	} else if(!empty($_POST['new_call_order'])) {
		if(empty($_POST['new_employee'])) {
			throw new \Exception("ERROR! template.php::POST.ADD new_employee EMPTY");
			//print("template.php::POST.ADD new_employee EMPTY, reload the page<br/>\n");
			//header("Location: $request_url");
			//exit();
		}
		if(DEBUG > 1) {
			print("template.php::POST.ADD new_employee = ".$_POST['new_employee']." "
				."new_call_order = ".$_POST['new_call_order']."<br/>\n");
		}
		$new_employees = $template->employees;
		$new_employee = (int) $_POST["new_employee"];
		$new_employees[] = array("employee" => $new_employee,
					 "call_order" => (int) $_POST['new_call_order']);
	} else if(!empty($_POST['call_order0'])) {
		if(empty($_POST['employee0'])) {
			throw new \Exception("ERROR! template.php::POST.UPDATE employee0 EMPTY");
			//print("template.php::POST.UPDATE employee0 EMPTY, reload the page<br/>\n");
			//header("Location: $request_url");
			//exit();
		}
		if(DEBUG > 1) {
			print("template.php::POST.UPDATE employee0 = ".$_POST['employee0']." "
				."call_order0 = ".$_POST['call_order0']."<br/>\n");
		}
		$old_employees = $template->employees;
		$new_employees = array();
		$iter=0;
		while(!empty($_POST["employee$iter"])) {
			if(DEBUG > 1) {
				print("template.php::POST.UPDATE POST[employee$iter] : POST[call_order$iter] = "
					.$_POST["employee$iter"]." : ".$_POST["call_order$iter"]."<br/>\n");
			}
			unset($new_employee);
			unset($new_call_order);
			$new_employee = (int) $_POST["employee$iter"];
			//$new_employee = Employee::getEmployee($pdo, $_POST["employee$iter"]);
			$new_call_order = (int) $_POST["call_order$iter"];
			//print("template.php::POST.UPDATE new_employee = $new_employee<br/>\n");
			//print("template.php::POST.UPDATE new_call_order = $new_call_order<br/>\n");
			if(DEBUG > 1) {
				print("template.php::POST.UPDATE new_employee = $new_employee; "
					."new_call_order = $new_call_order<br/>\n");
			}
			$new_employees[] = array("employee" => $new_employee, "call_order" => $new_call_order);
			$iter++;
		}
		$template_employeeids = array();
		foreach ($new_employees as $new_employee) {
			$template_employeeids[] = $new_employee['employee'];
		}
		if(DEBUG > 1) {
			print("template.php::POST.UPDATE template_employeeids = "
				.print_r($template_employeeids, true)."<br/>\n");
		}
		foreach ($old_employees as $old_employee) {
			if(DEBUG > 1) {
				print("template.php::POST old_employee = "
					.print_r($old_employee, true)."<br/>\n");
			}
			if(!\in_array($old_employee['employee'], $template_employeeids)) {
				$new_employees[] = array('call_order' => 0,
							 'employee' => $old_employee['employee']);
			}
		}
	}
	//TODO sort new_employees by []['call_order']
	if(DEBUG > 1) { print("template.php: new_employees = ".print_r($new_employees, true)."<br/>\n"); }
	$template->employees = $new_employees;
	if(DEBUG > 1) { print("template.php: template = ".print_r($template, true)."<br/>\n"); }
	$template->save($pdo);
	if(DEBUG > 1) { print("template.php: template.save() = ".print_r($template, true)."<br/>\n"); }
	$template_employees = $template->getTeamScheduleTemplateEmployees($pdo);
	unset($template_employeeids);
	$template_employeeids = array();
	foreach ($template_employees as $key => $template_employee) {
		$template_employeeids[] = $template_employee['employee'];
	}
	if(DEBUG > 1) {
		print("template.php::POST: template_employeeids = "
			.print_r($template_employeeids, true)."<br/>\n");
	}
	header("Location: $request_url");
}

$data = "<script>\n"
.
'function add_employee() {
	console.log("add_employee()");
	var add_employee_form = `<form formaction="template.php" method="post">
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
if(DEBUG > 1) { print("template.php: team_employeeids = ".print_r($team_employeeids, true)."<br/>\n"); }
if(DEBUG > 1) { print("template.php: template_employeeids = ".print_r($template_employeeids, true)."<br/>\n"); }
foreach($team_employees as $id => $name) {
	if(DEBUG > 1) { print("template.php: team_employee.id:name = $id:$name<br/>\n"); }
	$in_array = (bool) \in_array($id, $template_employeeids);
	if(DEBUG > 1) {
		$in_array_str = $in_array ? "TRUE" : "FALSE";
		print("template.php::form.add_employee: in_array = $in_array_str<br/>\n");
	}
	if($in_array) {
		if(DEBUG > 1) { print("template.php::form.add_employee: Skipping $id:$name<br/>\n"); }
		continue;
	}
	$option_str = "        <option value=\"$id\">$name</option>\n";
	$data .= $option_str;
	unset($option_str);
}
if(DEBUG > 1) { 
	print("template.php::form.add_employee: template_employeeids = "
		.print_r($template_employeeids, true)."<br/>\n");
	print("template.php::form.add_employee: team_employeeids = "
		.print_r($team_employeeids, true)."<br/>\n");
}
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
	fetch("template.php?id='.$template->id.'", {
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
  <h1>On-Call Template '.$template_name.'</h1>
  <h3>'.$team->name.' ('.$template_id.')</h3>
  <pre>NOTE: Search is by id or "name" (teamid:days_offset)
EXAMPLE) 57, 1:91</pre>
</blockquote>
';

if(DEBUG > 1) { print("template.php: template = ".print_r($template, true)."<br/>\n"); }

$data .= "<form formaction=\"template.php\" method=\"post\">\n"
	."<table>\n"
	."  <tr>\n"
	."    <th>Template ID</th><td>".$template->id."</td>\n"
	."  </tr>\n"
// TODO There's a bug in Template active/inactive management
//	."  <tr>\n"
//	."    <th>Active</th>\n"
//	."    <td>\n"
//	."      <input type=\"radio\" name=\"active\" value=\"1\" ";
//if($template->active) { $data .= "checked "; }
//$data .= "onchange=\"this.form.submit()\" />\n"
//	."    </td>\n"
//	."  </tr>\n"
//	."  <tr>\n"
//	."    <th>Inactive</th>\n"
//	."    <td>\n"
//	."      <input type=\"radio\" name=\"active\" value=\"0\" ";
//if(!$template->active) { $data .= "checked "; }
//$data .= "onchange=\"this.form.submit()\" />\n"
//	."    </td>\n"
//	."  </tr>\n"
	."  <tr>\n"
	."    <th>Days Offset</th>\n"
	."    <td><input type=\"number\" name=\"days_offset\" value=\"$days_offset\" "
		."onchange=\"this.form.submit()\" step=\"1\" required />"
	."    </td>\n"
	."  </tr>\n"
	."  <tr>\n"
	."    <th>Start Time</th>\n"
	."    <td><input type=\"time\" name=\"start_time\" value=\"".$start_time->format("H:i")."\" "
		."onchange=\"this.form.submit()\" step=\"60\" required />"
	."    </td>\n"
	."  </tr>\n"
	."  <tr>\n"
	."    <th>On-Call Team (ID)</th>\n"
	."    <td>\n"
	."      <a href=\"./teams.php?teamid=$team->id\">$team->name ($team->id)</a> |\n"
	."      <a href=\"./templates.php?teamid=$team->id\">Templates</a> |\n"
	."      <a href=\"./schedules.php?teamid=$team->id\">Schedules</a>\n"
	."    </td>\n"
	."  </tr>"
	."  <tr>\n"
	."    <th>Employees</th>\n";

if(!empty($iter)) { unset($iter); }
$iter = 0;
if(DEBUG > 1) {
	print("template.php: template_employees = ".print_r($template_employees, true)."<br/>\n");
	print("template.php: template_employeeids = ".print_r($template_employeeids, true)."<br/>\n");
}
if(empty($template_employees)) {
	$data .= "    <td>\n"
		."      <button type=\"button\" onclick=\"add_employee()\">Add</button>\n"
		."    </td>\n"
		."  </tr>\n";
} else {
foreach ($template_employees as $key => $template_employee) {
	if($iter > 0) {
		$data .= "  <tr>\n"
			."    <td></td>\n";
	}

	if(!empty($employee)) { unset($employee); }
	$employee_id = $template_employee['employee'];
	$employee = Employee::getEmployee($pdo, $employee_id);
	if(!empty($call_order)) { unset($call_order); }
	$call_order = (int) $template_employee['call_order'];

	//if($call_order < 1) { continue; }

	$data .= "    <td>\n"
		."      <label for=\"call_order$key\">Call Order</label>"
			."<input type=\"number\" name=\"call_order$key\" class=\"call-order\" "
			."onchange=\"this.form.submit()\" ";
	//if($is_old) { $data .= "disabled "; }
	$data .=	"value=\"$call_order\" min=\"1\" max=\"100\" />\n"
		."    </td>\n"
		."    <td>\n"
		."      <label for=\"employee$key\">Employee</label>\n"
		."      <select name=\"employee$key\" onchange=\"this.form.submit()\" ";
	//if($is_old) { $data .= "disabled "; }
	$data .=	">\n"
		."        <option value=\"$employee_id\">".$employee->getEmployeeName()."</option>\n";
	foreach($team_employees as $id => $name) {
		//print("template.php: id = $id : name = $name<br/>\n");
		//$result = \in_array($employee_id, $template_employeeids) ? "TRUE" : "FALSE";
		//print("template.php: in_array(employee_id, template_employeeids) = $result<br/>\n");
		if(\in_array($id, $template_employeeids)) { continue; }
		$data .= "        <option value=\"$id\">$name</option>\n";
	}
	$data .= "      </select>\n"
		."    </td>\n"
		."    <td>\n";
	if(\count($template_employees) < \count($team_employees)) {  //&& !$is_old) {
		$data .= "      <button type=\"button\" class=\"bimg\" onclick=\"add_employee()\" "
				."alt=\"Add Backup\" title=\"Add backup\" \">"
			."<img src=\"add-user.png\" style=\"margin:0\" /></button>\n";
	}
	if(\count($template_employees) > 1) { //&& !$is_old) {
		$data .= "      <button type=\"button\" class=\"bimg\" onclick=\"remove_employee($employee_id)\">"
				."<img src=\"remove-user.png\" style=\"margin:0\" /></button>\n";
	}

	$data .= "    </td>\n"
		."  </tr>\n";
	
	$iter++;
	if(DEBUG > 1) { print("template.php: template_employees.iter = $iter<br/>\n"); }
}//END foreach(template_employees)
}//END !empty(template_employees)

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
unset($template);
unset($template_employees);
unset($team_employeeids);
require_once('./footer.php');
