<?php
namespace WiseEyesEnt\OnCall2;

include_once('common.php');
include_once('dbconn.php');
include_once('employee.php');
include_once('team.php');

include_once('header.php');

if(empty($pdo)) { $pdo = dbConnect(); }
if(DEBUG > 1) { print("contact.php: pdo = ".print_r($pdo, true)."<br/>\n"); }

$referer = (isset($_SERVER['HTTP_REFERER'])) ? (string) $_SERVER['HTTP_REFERER'] : "";
if(DEBUG > 1) { print("contact.php: referer = ".print_r($referer, true)."<br/>\n"); }

if(isset($_REQUEST['q'])) {
	if(DEBUG > 1) { print("contact.php: REQUEST[q] = ".$_REQUEST['q']."<br/>\n"); }

	$q = $_REQUEST['q'];
	if(DEBUG > 1) { print("contact.php: q = ".print_r($q, true)."<br/>\n"); }

	if(is_int($q) || \preg_match("/^[a-z][0-9]{3,}$/", $q)) {
		$url = "contact.php?id=$q";
	} else { $url = "contact.php?name=$q"; }
	if(DEBUG > 1) { print("contact.php::q url = $url<br/>\n"); }

	header("Location: $url");
	unset($pdo);
	unset($q);
	unset($referer);
	exit();
}

if(!empty($id)) { unset($id); }
if(isset($_REQUEST['id'])) {
	$id = (int) $_REQUEST['id'];
	if(empty($id)) { $id = (string) $_REQUEST['id']; }
} else if(isset($_REQUEST['name'])) {
	try { $id = (int) Employee::getIdByName($pdo, (string) $_REQUEST['name']); }
	catch(\Exception $e) {
		if(DEBUG > 1) { print("contact.php::id Exception = ".print_r($e, true)."<br/>\n"); }
		print(displayError($e->getCode()));
		unset($id);
		unset($pdo);
		unset($referer);
		require_once('./footer.php');
		die($e->getCode());
	}
} else { $id = 0; }
if(DEBUG > 1) { print("contact.php: id = $id<br/>\n"); }

if(!empty($employee)) { unset($employee); }
if(!empty($id)) {
	try {
		$employee = Employee::getEmployee($pdo=$pdo, $id=$id);
	} catch(\Exception $e) {
		if(DEBUG > 1) { print("contact.php::employee Exception = ".print_r($e, true)."<br/>\n"); }
		print(displayError($e->getCode()));
		unset($data);
		unset($employee);
		require_once('./footer.php');
		die($e->getCode());
	}
	unset($add_employee);
} else if(isset($_REQUEST['add_employee'])) {
	$add_employee = (int) $_REQUEST['add_employee'];
	if(DEBUG > 1) { print("contact.php::employee add_employee = $add_employee<br/>\n"); }
	$employee = new Employee();
}
if(DEBUG > 1) { print("contact.php: employee = ".print_r($employee, true)."<br/>\n"); }

if(!empty($data)) { unset($data); }
$data = "<blockquote>\n";
if(isset($_GET['add_employee'])) {
	$data .= "<h4>New Employee</h4>\n"
		."<em>User ID & Name (\"[First] [Last]\") must be unique.</em>\n";
} else {
	$data .= "<h4>".$employee->getEmployeeName()." ($employee->userid)</h4>\n";
}
$data .= "<pre>NOTE: Search is for employees by DB ID, userID, or name\n"
	."EXAMPLES) 1, id123, \"Joshua Cripe\", \"Cripe, Joshua\"</pre>\n"
	."</blockquote>\n"
	."<br/>";

//if(!isset($add_employee)) {
//	$data .= "<button type=\"button\" onclick=\"createEmployee()\">Create Employee</button><br/>\n";
//}

if(!empty($active_teams)) { unset($active_teams); }
$active_teams = Team::getTeamNames($pdo, $active=true);
if(!empty($active_teamids)) { unset($active_teamids); }
$active_teamids = array();
if(!empty($team)) { unset($team); }
foreach($active_teams as $team) {
	$active_teamids[] = $team['id'];
}	

if(!empty($labels)) { unset($labels); }
$labels = array(
	'id' => 'Database ID',
	'userid' => 'User ID',
	'type_id' => 'Employee Type',
	'first_name' => 'First Name',
	'nickname' => 'Nickname',
	'last_name' => 'Last Name',
	'tel_audinet' => 'Audinet',
	'tel_direct' => 'Direct Dial',
	'tel_cell_corp' => 'Primary Cell',
	'tel_cell_other' => 'Other Cell',
	'tel_home_other' => 'Home Phone',
	'email_corp' => 'Corp Email',
	'email_page_corp' => 'Text Message',
	'email_other' => 'Other Email',
	//'schedule' => 'Schedule',
	'contact_instructions' => 'Contact Instructions',
	'active' => 'Active'
);

//TODO FIX OVERWRITE OF EXISTING EMPLOYEE
if($request_method == 'POST') {
	if(DEBUG > 1) { print("contact.php: POST = ".print_r($_POST, true)."<br/>\n"); }

	//Executing a search via DB ID, userid, or name
	//	see also: Employee::getEmployee()
	if(isset($_POST['q'])) {
		if(DEBUG > 1) { print("contact.php::POST q = ".$_POST['q']."<br/>\n"); }

		//Test if we have an integer input, integer == DB ID
		$term = (int) $_POST['q'];
		if(empty($term)) { $term = (string) $_POST['q']; }
		if(DEBUG > 1) { print("contact.php::POST.q term = $term<br/>\n"); }

		//If it's not an integer, maybe it's an employee ID?
		if(is_int($term) || \preg_match("/^[a-zA-Z][0-9]+$/", $term)) {
			$url = "contact.php?id=$term";
		} else {
			//Otherwise it's got to be a name
			$url = "contact.php?name=$term";
		}
		if(DEBUG > 1) { print("contact.php::POST.q url = $url<br/>\n"); }

		header("Location: $url");
		exit();
	} else if(isset($_REQUEST['add_employee'])) {
		if(DEBUG > 1) { print("contact.php::POST add_employee = ".$_POST['add_employee']."<br/>\n"); }

		//FIRST Check if the given userid OR name exists so we don't overwrite an existing employee
		//print("contact.php::POST add_employee = ".$_REQUEST['add_employee']
		//	." : userid = ".$_REQUEST['userid']."<br/>\n");
		$userid = (string) $_POST['userid'];
		$name = (string) $_POST['last_name'].", ".(string) $_POST['first_name'];
		if(DEBUG > 1) {
			print("contact.php::POST.add_employee($add_employee) "
				."userid = $userid ; name = $name<br/>\n");
		}

		try {
			$employee = Employee::getEmployee($pdo, $id=$userid, $name=$name);
		} catch(\Exception $e) {
			if(DEBUG > 1) {
				print("contact.php::POST.add_employee Exception = "
					.print_r($e, true)."<br/>\n");
			}

			if($e->getCode() == 102) {
				//throw new \Exception("contact.php::POST.add_employee "
				//	."MULTIPLE EMPLOYEES FOUND!", 102);
				header("Location: $request_url&exists=1");
				exit;
			} else if($e->getCode() == 101) {
				$employee = null;
			} else { throw $e; }
		}
		if(DEBUG > 1) {
			print("contact.php::POST.add_employee (employee check) employee = "
				.print_r($employee, true)."<br/>\n");
		}

		//If it does exist, let's just redirect to a GET
		//if(!empty($employee)) {
		//	$request_url = \preg_replace("/\?.*$/", "?id=$employee->id&exists=1", $request_url);
		//	//print("contact.php::POST.add_employee.REDIRECT request_url = $request_url<br/>\n");
		//	header("Location: $request_url");
		//	exit("contact.php::POST.add_employee EMPLOYEE EXISTS");
		//	//exit;
		//} else {
		//If it doesn't exist...
		if(empty($employee)) {
			//...we can update & add the new employee
			if(DEBUG > 1) { print("contact.php::POST.add_employee New Employee<br/>\n"); }

			$employee = new Employee();
			if(!empty($post_teamids)) { unset($post_teamids); }
			$post_teamids = array();
			foreach(array_keys($_POST) as $key) {
				if(DEBUG > 1) {
					print("contact.php::POST.add_employee.foreach(array_keys.POST) "
						."key[value] = ".$key."[".$_POST[$key]."]<br/>\n");
				}

				if(\str_starts_with($key, "team")) {
					//If it's a team, set the team
					$post_teamids[] = (int) $_POST[$key];
				} else if(\str_starts_with($key, "tel_")) {
					//If it's a phone number, we only want numbers
					$value = (string) $_POST[$key];
					$value = \preg_replace("/[^0-9]+/", "", $value);
					//...Plus strip any leading 1's
					if(\str_starts_with($value, "1")) { $value = \substr($value, 1); }
					$employee->$key = (int) $value;
				} else {
					//...otherwise, we can just update the employee
					$value = $_POST[$key];
					$value = \str_replace(array("'", "\"", "`"), "", $value);
					$employee->$key = $value;
				}
				unset($value);
			}//END foreach POST

			if(DEBUG > 1) {
				print("contact.php: POST: employee = ".print_r($employee, true)."<br/>\n");
			}
			$retVal = $employee->save($pdo=$pdo);
			if(DEBUG > 1) {
				print("contact.php::POST.employee->save() RETURN: "
					.print_r($retVal, true)."<br/>\n");
			}
			if($retVal) {
				try { $employee = Employee::getEmployee($pdo, $employee->userid); }
				catch(\Exception $e) {
					if(DEBUG > 1) {
						print("contact.php::POST.add_employee.save "
							."Exception = ".print_r($e, true)."<br/>\n");
					}
					throw $e;
				}
			}
			else {
				throw new \Exception("ERROR! contact.php::POST.add_employee "
					."Failed to save to database", 121);
			}

			if(DEBUG > 1) {
				print("contact.php::POST.add_employee employee->save() POST = "
					.print_r($employee, true)."<br/>\n");
				print("contact.php::POST.add_employee post_teamids = "
					.print_r($post_teamids, true)."<br/>\n");
			}
			$employee->setEmployeeTeams($pdo, $post_teamids);
			unset($post_teamids);

			$teams = $employee->getEmployeeTeamNames($pdo);
			if(DEBUG > 1) {
				print("contact.php::POST.add_employee teams = "
					.print_r($teams, true)."<br/>\n");
			}

			$team_ids = array();
			if(!empty($teams)) {
				foreach($teams as $team) { $team_ids[] = $team['id']; }
			}
			unset($team);
			if(DEBUG > 1) {
				print("contact.php::POST.team_ids = ".print_r($team_ids, true)."<br/>\n");
			}

			if(isset($add_employee)) {
				$request_url = \preg_replace("/\?.*/", "?id=$employee->id", $request_url);
				if(DEBUG > 1) {
					print("contact.php::POST.add_employee request_url = "
						."$request_url<br/>\n");
				}
				header("Location: $request_url");
				exit();
			}
		}//END employee check
	} else {
		//Performing an employee update
		if(DEBUG > 1) { print("contact.php::POST update<br/>\n"); }

		if(!empty($post_teamids)) { unset($post_teamids); }
		$post_teamids = array();
		foreach(array_keys($_POST) as $key) {
			if(DEBUG > 1) {
				print("contact.php::POST.update.foreach(array_keys(POST)) "
					."key[value] = ".$key."[".$_POST[$key]."]<br/>\n");
			}

			if(\str_starts_with($key, "team")) {
				//If it's a team, set the team
				$post_teamids[] = (int) $_POST[$key];
			} else if(\str_starts_with($key, "tel_")) {
				//If it's a phone number, we only want numbers
				$value = (string) $_POST[$key];
				$value = \preg_replace("/[^0-9]+/", "", $value);
				//...Plus strip any leading 1's
				if(\str_starts_with($value, "1")) { $value = \substr($value, 1); }
				$employee->$key = (int) $value;
			} else {
				//...otherwise, we can update the employee
				$value = $_POST[$key];
				$value = \str_replace(array("'", "\"", "`"), "", $value);
				$employee->$key = $value;
			}
			unset($value);
		}
		if(DEBUG > 1) {
			print("contact.php::POST.update post_teamids = "
				.print_r($post_teamids, true)."<br/>\n");
			print("contact.php: POST: employee = ".print_r($employee, true)."<br/>\n");
		}

		$retVal = $employee->save($pdo=$pdo);
		if(DEBUG > 1) {
			print("contact.php::POST.employee->save() RETURN: ".print_r($retVal, true)."<br/>\n");
		}
		if($retVal) { $employee = Employee::getEmployee($pdo, $employee->userid); }
		else {
			throw new \Exception("ERROR! contact.php::POST.add_employee "
				."Failed to save to database", 121);
		}
		if(DEBUG > 1) {
			print("contact.php::POST.employee->save() employee = "
				.print_r($employee, true)."<br/>\n");
			print("contact.php::POST.post_teamids = "
				.print_r($post_teamids, true)."<br/>\n");
		}

		$employee->setEmployeeTeams($pdo, $post_teamids);
		unset($post_teamids);

		$teams = $employee->getEmployeeTeamNames($pdo);
		if(DEBUG > 1) {
			print("contact.php::POST.update teams = ".print_r($teams, true)."<br/>\n");
		}

		$team_ids = array();
		if(!empty($teams)) {
			foreach($teams as $team) { $team_ids[] = $team['id']; }
		}
		unset($team);
		if(DEBUG > 1) { print("contact.php::POST.team_ids = ".print_r($team_ids, true)."<br/>\n"); }
	}//END if/else POST method
}//END if POST

if(!empty($employee)) {
	$teams = $employee->getEmployeeTeamNames($pdo);
	if(DEBUG > 1) { print("contact.php::employee teams = ".print_r($teams, true)."<br/>\n"); }

	$team_ids = array();
	if(!empty($teams)) {
		foreach($teams as $team) {
			if(DEBUG > 1) {
				print("contact.php::employee.teams team = ".print_r($team, true)."<br/>\n");
			}
			$team_ids[] = $team['id'];
		}
	}
	unset($team);
	if(DEBUG > 1) { print("contact.php::employee team_ids = ".print_r($team_ids, true)."<br/>\n"); }

	//if(!empty($name)) { unset($name); }
	//$name = $employee->getEmployeeName();
	
	if(isset($_GET['exists']) && $request_method == 'GET') {
		$data .= "<h1>Employee Exists!</h1>\n";
	} else if(isset($_SERVER['HTTP_REFERER']) && \str_contains($_SERVER['HTTP_REFERER'], 'add_employee')) {
		$data .= "<h1>Employee Created</h1>\n";
	}
	
	$data .= "<form action=\"$request_url\">\n"
		."<table>\n"
		."<tbody>\n";

	foreach ($labels as $label => $description) {
		if(!empty($value)) { unset($value); }
		$value = (empty($employee->$label)) ? '' : $employee->$label;
		$value = \str_replace(array("\"", "'", "`"), "", $value);
		$data .= "  <tr>\n"
			."    <th>$description</th>\n";
		if($label == "id") {
			if(isset($add_employee)) {
				$data .= "    <td>New Employee</td>\n";
			} else {
				$data .= "    <td>$value</td>\n";
			}
		} else if($label == "userid") {
			if(isset($add_employee)) {
				$data .= "    <td><input type=\"text\" name=\"$label\" class=\"mandatory\" "
						."required />"
					."<em class=\"mandatory-text\"> * Required</em></td>\n";
			} else { $data .= "    <td>$value</td>\n"; }
		} else if($label == 'type_id') {
			switch($value) {
				case 1:
					$value = "Employee";
					break;
				case 2:
					$value = "Contractor";
					break;
				case 3:
					$value = "Vendor";
					break;
				case 4:
					$value = "Position";
					break;
				case 5:
					$value = "Group";
					break;
				default:
					$value = "UNSET";
			}
			$data .= "    <td>\n";
			foreach (Employee::EMPLOYEE_TYPE as $key => $id) {
				if($id == 0) { continue; }
				$data .= "      <input type=\"radio\" name=\"type_id\" value=\"$id\" ";
				if($employee->type_id == $id) { $data .= "checked "; }
				$data .= 	"/> $key\n";
				//if($key != \array_key_last(Employee::EMPLOYEE_TYPE)) {
				//	$data .= " | ";
				//}
			}
			$data .= "    </td>\n";
		} else if($label == "first_name" || $label == "last_name") {
			$data .= "    <td>\n";
			if(isset($add_employee)) {
				$data .= "      <input type=\"text\" name=\"$label\" value=\"".$value."\" "
						."class=\"mandatory\" required />\n"
					."      <em class=\"mandatory-text\"> * Required</em>\n";
			} else {
				$data .= "      <input type=\"text\" name=\"$label\" value=\"$value\" disabled />\n";
			}
			$data .= "    </td>\n";
		} else if($label == 'active') {
			$data .= "    <td>\n"
				."      <input type=\"radio\" name=\"active\" id=\"active\" value=\"1\" ";
			if($employee->active) {
				$data .= "checked />\n";
			} else {
				$data .= " />\n";
			}
			$data .= "    </td>\n"
				."  </tr>\n"
				."  <tr>\n"
				."    <th>Inactive</th>\n"
				."    </th>\n"
				."    <td>\n"
				."      <input type=\"radio\" name=\"active\" id=\"inactive\" value=\"0\" ";
			if(!$employee->active) {
				$data .= " checked />\n";
			} else {
				$data .= " />\n";
			}
			$data .= "    </td>\n"
				."  </tr>\n";
		} else if($label == "contact_instructions") {
			$data .= "    <td><input name=\"$label\" class=\"contact-info\" value=\"$value\" /></td>\n";
		} else if(strncmp($label, "tel_", 4) === 0) {
			if(!empty($value)) {
				switch($label) {
				case("tel_audinet"):
					$value = $employee->getEmployeeAudinet();
					break;
				default:
					$value = formatPhoneNumber((int)$value);
				}
			} else { $value = ""; }
			$data .= "    <td>\n";
			if($label == "tel_cell_corp") {
				$data .= "      <input name=\"$label\" value=\"$value\" class=\"mandatory\" required />\n"
					."      <em class=\"mandatory-text\">* Required</em>\n";
			} else {
				$data .= "      <input name=\"$label\" value=\"$value\" />\n";
			}
				$data .= "    </td>\n";
		} else {
			$data .= "    <td><input name=\"$label\" value=\"$value\" /></td>\n";
		}
		$data .= "  </tr>\n";
	}//END labels

	//Teams
	if(DEBUG > 1) { print("contact.php: active_teams = ".print_r($active_teams, true)."<br/>\n"); }
	$data .= "  <tr>\n"
		."    <th>Teams</th>\n"
		."    <td>\n";
	$iter = 0;
	foreach ($active_teams as $team) {
		$data .= "      <input type=\"checkbox\" value=\"".$team['id']."\" name=\"team$iter\" ";
			//."onchange=\"enableUpdate()\"";
		if(\in_array($team['id'], $team_ids) ||
			(isset($add_employee) && $add_employee == $team['id'])) {
			$data .= " checked";
		}// else if(isset($add_employee)) {
		//	if($add_employee == $team['id']) {
		//		$data .= " checked";
		//	}
		//}
		$data .= ">&nbsp;<a href=\"teams.php?teamid=".$team['id']."\">"
			.$team['name']."</a><br/>\n";
		$iter++;
	}
	unset($active_teams);
	unset($iter);
	unset($team);
	$data .= "    </td>\n"
		."  </tr>\n";

	$data .= "</table>\n<br/>\n"
		."<input type=\"submit\" id=\"update\" value=\"Save\" formmethod=\"post\" />\n"
		."</form>\n";
} else {
	if(isset($_REQUEST['add_employee'])) {
		$data .= "<h1>Add Employee REQUEST.add_employee = "
			.print_r($_REQUEST['add_employee'], true)."</h1>\n";
	} else {
		$data .= displayError(101);
	}
}//END If(employee)/Else

//Javascript
$data .= "".
'
<script>
function enableUpdate() {
	document.getElementById("update").disabled = false;
}

function createEmployee() {
	location.href = "contact.php?add_employee=0";
}
</script>
';

print($data);

unset($name);
unset($data);
unset($employee);

include_once('footer.php');
?>
