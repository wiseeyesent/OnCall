<?php
namespace WiseEyesEnt\OnCall2;

require_once('./dbconn.php');
require_once('./common.php');
require_once('./header.php');

require_once('./employee.php');
require_once('./team.php');
require_once('./team_schedule.php');

if(empty($pdo)) { $pdo = dbConnect(); }

if(!empty($employee)) { unset($employee); }
if(!empty($team)) { unset($team); }
if(!empty($teams)) { unset($teams); }
if(!empty($display_active)) { unset($display_active); }

$display_active = (isset($_REQUEST['active'])) ? (bool) $_REQUEST['active'] : true;
if(DEBUG > 1) { print("teams.php: display_active = ".print_r($display_active, true)."<br/>\n"); }

$teams = Team::getTeamNames($pdo, $active=$display_active);
if(DEBUG > 1) { print("teams.php: teams = ".print_r($teams, true)."<br/>\n"); }

if(isset($_REQUEST['q'])) {
	if(DEBUG > 1) { print("teams.php: REQUEST[q] = ".$_REQUEST['q']."<br/>\n"); }

	$term = !empty((int)$_REQUEST['q']) ? (int) $_REQUEST['q'] : (string) $_REQUEST['q'];
	if(DEBUG > 1) { print("teams.php::q term = ".print_r($term, true)."<br/>\n"); }

	if(is_int($term)) {
		$url = "teams.php?teamid=$term";
	} else {
		$url = "teams.php?name=$term";
	}
	if(DEBUG > 1) { print("teams.php::q url = $url<br/>\n"); }

	header("Location: $url");
	unset($display_active);
	unset($employee);
	unset($team);
	unset($teams);
	unset($term);
	exit();
}

$teamid = 0;
$name = (isset($_GET['name'])) ? (string) $_GET['name'] : "";

if(!empty($name)) {
	try {
		$team = Team::getTeam($pdo, $id=$teamid, $name=$name, $active=false);
		$teamid = $team->id;
	} catch(\Exception $e) {
		print(displayError($e->getCode()));
		require_once('./footer.php');
		unset($display_active);
		unset($name);
		unset($pdo);
		unset($team);
		unset($teamid);
		unset($teams);
		die($e->getCode());
	}
} else if(isset($_GET['team_exists'])) {
	$teamid = (int) $_GET['team_exists'];
} else if(isset($_GET['teamid'])) { $teamid = (int) $_GET['teamid']; }

if(empty($teamid)) {
	$teamid = $teams[0]['id'];
}
if(DEBUG > 1) { print("teams.php: teamid = $teamid; name = $name<br/>\n"); }

if(!empty($data)) { unset($data); }
if(!empty($team_select)) { unset($team_select); }
$data = '
';

$data .= "<blockquote>\n"
	."  <h4>On-Call Teams for Team</h4>\n"
	."  <pre>NOTE: Search is for employee by DB ID, User ID, or name\n"
	."EXAMPLES: 1, id123, \"Joshua Cripe\", \"Cripe, Joshua\"</pre>\n"
	."</blockquote>\n";

//ERROR HANDLING
if(isset($_GET['error'])) {
	if(DEBUG > 1) { print("teams.php::GET error = ".$_GET['error']."<br/>\n"); }

	$error_code = (int) $_GET['error'];
	if(DEBUG > 1) { print("teams.php::GET.error error_code = $error_code<br/>\n"); }

	$data .= displayError($error_code);
}//END if GET.error

[$team_short, $team_select] = teamSelect($pdo=$pdo, $teamid=$teamid, $teams=$teams);
if(DEBUG > 2) { print("teams.php: team_short = ".print_r($team_short, true)."<br/>\n"); }

if(empty($teamid)) { $teamid = $team_short['id']; }
if(DEBUG > 1) { print("teams.php: teamid = $teamid<br/>\n"); }

$data .= $team_select;
unset($team_select);

try {
	$team = Team::getTeam($pdo=$pdo, $id=$teamid);
	if(DEBUG > 1) { print("teams.php: team = ".print_r($team, true)."<br/>\n"); }
} catch(\Exception $e) {
	if(DEBUG > 1) { print("teams.php::getTeam() Exception = ".print_r($e, true)."<br/>\n"); }
	$data .= displayError($e->getCode());
	print($data);
	require_once('./footer.php');
	unset($data);
	unset($display_active);
	unset($team);
	unset($teamid);
	unset($teams);
	unset($team_select);
	unset($team_short);
	die($e->getCode());
}

if($request_method == "POST") {
	if(DEBUG > 2) {
		print("teams.php::POST = ".print_r($_POST, true)."<br/>\n");
		print("teams.php::POST team = ".print_r($team, true)."<br/>\n");
	}

	if(isset($_POST['q'])) {
		//q == Search
		if(DEBUG > 1) { print("teams.php::POST q = ".$_POST['q']."<br/>\n"); }

		$term = (int) $_POST['q'];
		if(empty($term)) { $term = (string) $_POST['q']; }
		if(DEBUG > 1) { print("teams.php::POST.q term = $term<br/>\n"); }

		if(is_int($term) || \preg_match("/^[a-zA-Z][0-9]+$/", $term)) {
			header("Location: contact.php?id=$term");
		} else {
			header("Location: contact.php?name=$term");
		}
		exit();
	} else if(isset($_POST['enable'])) {
		//enable == Activate disabled team
		if(DEBUG > 1) {
			print("teams.php::POST enable = ".$_POST['enable']."<br/>\n");
			print("teams.php::POST.enable PRE team = "
				.print_r($team, true)."<br/>\n");
		}

		try {
			$enable_id = (int) $_POST['enable'];
			if(DEBUG > 1) { print("teams.php::POST.enable enable_id = $enable_id<br/>\n"); }

			if(empty($enable_id)) {
				throw new \Exception($message="teams.php::POST.enable ERROR! "
					."`enable` is invalid",
					$code=200);
				exit(200);
			}

			$team->active = true;
			$result = $team->save($pdo);
			if(empty($result)) {
				throw new \Exception($message="teams.php::POST.enable ERROR! "
					."Failed to save team",
					$code=221);
				exit(221);
			}
			if(DEBUG > 1) {
				print("teams.php::POST.enable POST team = "
					.print_r($team, true)."<br/>\n");
			}
		} catch(\Exception $e) {
			if(DEBUG > 1) {
				print("teams.php::POST.enable ERROR! Exception = "
					.print_r($e, true)."<br/>\n");
				print("teams.php::POST.enable ERROR! team = "
					.print_r($team, true)."<br/>\n");
				exit($e->getMessage());
			} else {
				header("Location: teams.php?teamid=$teamid&error=".$e->getCode());
				exit($e->getMessage());
			}
		}//END try/catch

		unset($enable_id);
		unset($result);
		unset($team);

		header("Location: teams.php?teamid=$teamid");
		exit();
	} else if(isset($_POST['disable'])) {
		//disable == De-activate enabled team
		if(DEBUG > 1) { print("teams.php::POST disable = ".$_POST['disable']."<br/>\n"); }

		try {
			$disable_id = (int) $_POST['disable'];
			if(DEBUG > 1) { print("teams.php::POST.disable disable_id = $disable_id<br/>\n"); }

			if(empty($disable_id)) {
				throw new \Exception($message="teams.php::POST.disable ERROR! "
					."`disable` is invalid",
					$code=200);
				exit(200);
			}

			$team->active = false;
			$result = $team->save($pdo);
			if(empty($result)) {
				throw new \Exception($message="teams.php::POST.disable ERROR! "
					."Failed to save team",
					$code=221);
				exit(221);
			}
			if(DEBUG > 1) {
				print("teams.php::POST.disable POST team = ".print_r($team, true)."<br/>\n");
			}
		} catch(\Exception $e) {
			if(DEBUG > 1) {
				print("teams.php::POST.disable ERROR! Exception = "
					.print_r($e, true)."<br/>\n");
				print("teams.php::POST.disable ERROR! team = "
					.print_r($team, true)."<br/>\n");
				exit($e->getMessage());
			} else {
				header("Location: teams.php?teamid=$teamid&error=".$e->getCode());
				exit($e->getMessage());
			}
		}//END try/catch

		unset($disable_id);
		unset($result);
		unset($team);

		header("Location: teams.php?teamid=$teamid");
	} else if(isset($_POST['add_employee'])) {
		//add_employee == Adding employee to team, can be inactive
		if(DEBUG > 1) {
			print("teams.php::POST add_employee = ".$_POST['add_employee']."<br/>\n");
			print("teams.php::POST.add_employee PRE team = ".print_r($team, true)."<br/>\n");
		}

		//We've made it this far, but still aren't sure we have a valid ID
		try {
			//If it's an integer, that'll be a DB ID
			$id = (int) $_POST['add_employee'];
			if(empty($id)) {
				//Not an int, could it be anything other than a string?
				$id = (string) $_POST['add_employee'];
			}

			if(empty($id)) {
				//Couldn't find an id, probably enterred incorrectly
				if(DEBUG > 1) {
					throw new \Exception($message="teams.php::POST.add_employee "
						."ERROR! Must provide valid employee ID, userID, or Name",
						$code=100);
					exit(100);
				} else {
					//Originally we silentyly failed by reloading the page...
					header("Location: teams.php?teamid=$teamid&error=100");
					exit(100);
				}
			}
			if(DEBUG > 1) { print("teams.php::POST.add_employee id = $id<br/>\n"); }

			//If ID is NOT db ID or User ID (s Number)
			if(!is_int($id) && !\preg_match("/^[a-zA-Z][0-9]+[a-z]?$/", $id)) {
				//then it's probably (hopefully) a name
				$id = Employee::getIdByName($pdo, $id);
				if(DEBUG > 1) { print("teams.php::POST.add_employee id = $id<br/>\n"); }
			}

			$employee = Employee::getEmployee($pdo, $id);
			if(DEBUG > 1) {
				print("teams.php::POST.add_employee employee = "
					.print_r($employee, true)."<br/>\n");
			}

			$team->employees[] = $employee->id;
			$result = $team->save($pdo);
			if(empty($result)) {
				throw new \Exception($message="teams.php::POST.add_employee "
					."ERROR! Failed to save team",
					$code=221);
			}
			if(DEBUG > 1) {
				print("teams.php::POST.add_employee POST team = "
					.print_r($team, true)."<br/>\n");
			}
		} catch(\Exception $e) {
			if(DEBUG > 1) {
				print("teams.php::POST.add_employee ERROR! Exception = "
					.print_r($e, true)."<br/>\n");
				print("teams.php::POST.add_employee ERROR! team = "
					.print_r($team, true)."<br/>\n");
				exit($e->getMessage());
			} else {
				//TODO Failed to add employee, what do now?
				//	for now, I guess we're silently passing w/ a simple reload
				header("Location: teams.php?teamid=$teamid&error=".$e->getCode());
				exit($e->getMessage());
			}
		}//END try/catch

		unset($employee);
		unset($id);
		unset($result);
		unset($team);

		//If we made it this far, we must have succeeded
		//Reload the page for changes...
		header("Location: teams.php?teamid=$teamid");
		exit();
	} else if(isset($_POST['remove_employee'])) {
		//remove_employee == Removing employee from team
		if(DEBUG > 1) {
			print("teams.php::POST remove_employee = ".$_POST['remove_employee']."<br/>\n");
			print("teams.php::POST.remove_employee PRE team = ".print_r($team, true)."<br/>\n");
		}

		$remove_id = (int) $_POST['remove_employee'];
		if(DEBUG > 1) { print("teams.php::POST.remove_employee $remove_id = $remove_id<br/>\n"); }

		try {
			foreach($team->employees as $key => $employee_id) {
				if(DEBUG > 2) {
					print("teams.php::POST.remove_employee.foreach(team->employees) "
						."key = $key; employee_id = $employee_id<br/>\n");
				}

				if($employee_id == $remove_id) {
					unset($team->employees[$key]);
				}
			}

			$result = $team->save($pdo);
			if(empty($result)) {
				throw new \Exception($message="teams.php::POST.remove_employee ERROR! "
					."Failed to save team",
					$code=221);
				exit(221);
			}
		} catch(\Exception $e) {
			if(DEBUG > 1) {
				print("teams.php::POST.remove_employee ERROR! Exception = "
					.print_r($e, true)."<br/>\n");
				print("teams.php::POST.remove_employee ERROR! team = "
					.print_r($team, true)."<br/>\n");
				exit($e->getMessage());
			} else {
				header("Location: teams.php?teamid=$teamid&error=".$e->getCode());
				exit($e->getMessage());
			}
		}//END try/catch

		//Reload the page for changes
		header("Location: teams.php?teamid=$teamid");
		exit();
	} else if(isset($_POST['create_team'])) {
		//create_team == Creating a new team
		if(DEBUG > 1) {
			print("teams.php::POST create_team = ".$_POST['create_team']."<br/>\n");
			print("teams.php::POST.create_team PRE team = ".print_r($team, true)."<br/>\n");
		}

		$name = (string) $_POST['name'];
		$start_time = (string) $_POST['start_time'];
		$ndays = (int) $_POST['ndays'] ? (int) $_POST['ndays'] : 7;
		$description = (string) $_POST['description'] ? (string) $_POST['description'] : '';

		//If name matches, redirect to the existing team;
		try { $team_exists = Team::getTeam($pdo, $id=0, $name=$name); }
		catch(\Exception $e) {
			//NOTE: We are expecting this to fail as the team should not exist
			if(DEBUG > 1) {
				print("teams.php::POST.create_team team_exists ERROR Exception = "
					.print_r($e, true)."<br/>\n");
				print("teams.php::POST.create_team team_exists = "
					.print_r($team_exists, true)."<br/>\n");
			}
		}//END try/catch team_exists

		if(!empty($team_exists)) {
			if(DEBUG > 1) {
				print("teams.php::POST.create_team team_exists = "
					.print_r($team_exists, true)."<br/>\n");
			}
			header("Location: teams.php?team_exists=$team_exists->id&active=0");
			exit();
		}

		try {
			$new_team = new Team();
			$new_team->name = $name;
			$new_team->start_time = $start_time;
			$new_team->ndays = $ndays;
			$new_team->description = $description;
			if(DEBUG > 1) {
				print("teams.php::POST.create_team new_team = "
					.print_r($new_team, true)."<br/>\n");
			}

			$result = $new_team->save($pdo);
			if(empty($result)) {
				throw new \Exception($message="teams.php::POST.create_team ERROR! "
					."Failed to save team",
					$code=221);
				exit(221);
			}
			if(DEBUG > 1) {
				print("teams.php::POST.create_team POST result = "
					.print_r($result, true)."<br/>\n");
			}
		} catch(\Exception $e) {
			if(DEBUG > 1) {
				print("teams.php::POST.create_team ERROR! Exception = "
					.print_r($e, true)."<br/>\n");
				print("teams.php::POST.create_team result ERROR = "
					.print_r($result, true)."<br/>\n");
				exit($e->getMessage());
			} else {
				header("Location: teams.php?teamid=$teamid&error=".$e->getCode());
				exit($e->getMessage());
			}
		}//END try/catch
		$team = $new_team;
		$teamid = $team->id;

		unset($description);
		unset($name);
		unset($new_team);
		unset($ndays);
		unset($result);
		unset($start_time);
		unset($team);
		unset($team_exists);

		//Load the new team
		header("Location: teams.php?teamid=$teamid");
		exit();
	} else if(isset($_POST['team_id'])) {
		//team_id == Updating the current team
		if(DEBUG > 1) {
			print("teams.php::POST team_id = ".$_POST['team_id']."<br/>\n");
			print("teams.php::POST.team_id PRE team = ".print_r($team, true)."<br/>\n");
		}

		if($team->id != (int) $_POST['team_id']) {
			throw new \Exception($message="teams.php::POST.team_id != team->id. "
				."What's going on here?",
				$code=200);
			exit(200);
		}

		$name = (string) $_POST['name'];
		if($name != $team->name) {
			try { $team_exists = Team::getTeam($pdo, $id=0, $name=$name); }
			catch(\Exception $e) {
				if(DEBUG > 1) {
					print("teams.php::POST.team_id.team_exists Exception = "
						.print_r($e, true)."<br/>\n");
				}
			}//END try/catch team_exists
		}

		if(!empty($team_exists) && $team->id != $team_exists->id) {
			header("Location: teams.php?team_exists=$team_exists->id&active=0");
			exit;
		}

		try {
			$team->name = (string) $_POST['name'];
			$team->start_time = (string) $_POST['start_time'];
			$team->ndays = (int) $_POST['ndays'];
			$team->description = (string) $_POST['description'];
			$result = $team->save($pdo);
			if(empty($result)) {
				if(DEBUG > 1) {
					print("teams.php::POST.team_id(UPDATE) ERROR! Failed to save team");
					print("teams.php::POST.team_id(UPDATE) ERROR! result = "
						.print_r($result, true)."<br/>\n");
				}
				throw new \Exception($message="teams.php::POST.team_id(UPDATE) ERROR! "
					."Failed to save team",
					$code=221);
				exit(221);
			}
		} catch(\Exception $e) {
			if(DEBUG > 1) {
				print("teams.php::POST.team_id(UPDATE) ERROR! Exception = "
					.print_r($e, true)."<br/>\n");
				exit($e->getMessage());
			} else {
				header("Location: teams.php?teamid=$teamid&error=".$e->getCode());
				exit($e->getMessage());
			}
		}//END try/catch

		if(DEBUG > 1) {
			print("teams.php::POST.team_id(UPDATE) POST team = ".print_r($team, true)."<br/>\n");
		}

		// Successful result, so redirect to team page
		header("Location: teams.php?teamid=$teamid");
	}//END if/else POST.team_id
	exit();
}//END if POST

if(isset($_GET['team_exists'])) { $data .= "<h3>Team Exists!</h3>\n"; }

//Team Data
$today = new \DateTime("now", new \DateTimeZone("UTC"));
$data .= "<table><!-- Team Details -->\n"
	."<thead>\n"
	."  <tr>\n"
	."    <th>DB ID</th>\n"
	."    <th colspan=\"2\">Name</th>\n"
	."    <th>Start Time (UTC)</th>\n"
	."    <th>Days</th>\n"
	."    <th align=\"right\">\n"
	."      <button type=\"button\" onclick=\"createTeam()\">Create Team</button>\n"
	."    </th>\n"
	."  </tr>\n"
	."</thead>\n"
	."<tbody>\n"
	."<form action=\"$request_url\" method=\"post\"><!-- Team Edit Form -->\n"
	."<input type=\"hidden\" name=\"team_id\" value=\"$team->id\" />\n"
	."  <tr>\n"
	."    <td>$team->id</td>\n"
	."    <td colspan=\"2\"><input type=\"text\" name=\"name\" value=\"$team->name\" "
		."onchange=\"this.form.submit()\" /></td>\n"
	."    <td><input type=\"time\" name=\"start_time\" value=\"$team->start_time\" "
		."onchange=\"this.form.submit()\" /></td>\n"
	."    <td><input type=\"number\" name=\"ndays\" min=\"1\" value=\"$team->ndays\" "
		."style=\"width:3em\" "
		."onchange=\"this.form.submit()\" /></td>\n"
	."    <td align=\"right\">"//<!-- Schedules / Templates --></th>\n"
	."      <a href=\"schedules.php?teamid=$team->id\">Schedules</a> | "
	."      <a href=\"templates.php?teamid=$team->id\">Templates</a>\n"
	."    </td>\n"
	."  </tr>\n"
	."  <tr>\n"
	."    <td align=\"left\">\n"
	."      Description\n"
	."    </td>\n"
	."    <td colspan=\"5\">\n"
	."      <input type=\"text\" class=\"text-tag\" name=\"description\" style=\"width:100%\" "
		."value=\"".$team->description."\" onchange=\"this.form.submit()\" \"></input>\n"
	."    </td>\n"
	."</form> <!-- Close Team Edit Form -->\n"
	."  </tr>\n"
	."</tbody>\n"
	."</table><!-- End Team Details -->\n"
	."<div><hr /></div>\n";

//Active||All Employees
$data .= "<table><!-- Employees Header Table -->\n"
	."  <tr>\n"
	."    <td><b>Employees</b></td>\n"
	."    <td colspan=\"5\" align=\"right\">\n"
	."      <form action=\"$request_url\" method=\"post\"><!-- Add Employee Form -->\n"
	."        <input type=\"search\" name=\"add_employee\" placeholder=\"Employee ID or Name...\" />\n"
	."        <input type=\"submit\" value=\"Add Employee\" />\n"
	."      </form><!-- Close Add Employee Form -->\n"
	."    </td>\n"
	."  </tr>\n"
	."  <tr>\n"
	."    <td>\n"
	."    <form action=\"$request_url\" method=\"get\">\n"
	."      <input type=\"hidden\" name=\"teamid\" value=\"$teamid\" />\n"
	."      Active <input type=\"radio\" id=\"active\" name=\"active\" value=\"1\" ";
if($display_active) { $data .= "checked "; }
$data .= 	"onchange=\"this.form.submit();\" />\n"
	."      All <input type=\"radio\" id=\"all\" name=\"active\" value=\"0\" ";
if(!$display_active) { $data .= "checked "; }
$data .= 	"onchange=\"this.form.submit();\" />\n"
	."    </form>\n"
	."    </td>\n"
	."  </tr>\n"
	."</table><!-- End Employees Header Table -->\n";

//Employees
$data .= "<table cellspacing=\"2\" style=\"width:100%;white-space:nowrap;\"><!-- Employees List Table -->\n"
	."<thead>\n"
	."  <tr>\n"
	."    <th>Name</th>\n"
	."    <th>UserID</th>\n"
	."    <th>Audinet</th>\n"
	."    <th>Mobile</th>\n"
	."    <th>Text Message</th>\n"
	."    <th><!-- Actions --></th>\n"
	."  </tr>\n"
	."</thead>\n"
	."<tbody>\n";

if(!empty($team->employees)) { $employees = Employee::getEmployees($pdo, $team->employees); }
else { $employees = array(); }
if(DEBUG > 1) { print("teams.php: employees = ".print_r($employees, true)."<br/>\n"); }

foreach ($employees as $employee) {
	//print("teams.php: employee_id = ".print_r($employee_id, true)."<br/>\n");
	//$employee = Employee::getEmployee($pdo, $id=$employee_id);
	//print("teams.php: employee_id = $employee_id, employee = ".print_r($employee, true)."<br/>\n");
	if(DEBUG > 2) { print("teams.php: foreach(employees) employee = ".print_r($employee, true)."<br/>\n"); }
	if($display_active && !$employee->active) {
		if(DEBUG > 1) { print("teams.php: Skipping inactive employee: $employee->userid<br/>\n"); }
		continue;
	}
	if(!empty($audinet)) { unset($audinet); }
	$audinet = $employee->getEmployeeAudinet();
	if(!empty($cell)) { unset($cell); }
	$cell = $employee->getEmployeeCell();
	if(!empty($name)) { unset($name); }
	$name = $employee->getEmployeeName();
	if($employee->active) { $data .= "  <tr>\n"; }
	else { $data .= "  <tr class=\"inactive\">\n"; }
	
	$data .= "    <td><a href=\"contact.php?id=$employee->id\">$name</a></td>\n"
		."    <td>$employee->userid</td>\n"
		."    <td>$audinet</td>\n"
		."    <td>$cell</td>\n"
		."    <td>$employee->email_page_corp</td>\n"
		."    <td align=\"right\">\n"
		."      <button type=\"button\" class=\"bimg\" "
			."alt=\"Remove Employee\" title=\"Remove employee\" "
			."onclick=\"removeEmployee($employee->id)\">\n"
		."        <img src=\"remove-user.png\" />\n"
		."      </button>\n"
		."    </td>\n"
		."    <td>\n"
		."    </td>\n"
		."  </tr>\n";
}

$data .= "</tbody>\n"
	."</table><!-- End Employees List Table -->\n";

//Create Team Modal
$data .= '
<dialog id="create_team_dialog" autofocus>
<form id="create_team_form" formaction="'.$request_url.'" method="post"><!-- Create Team Form -->
  <input type="hidden" name="create_team" value="1" />
  <h3>Create Team</h3>
  <p class="mandatory-text">All values are required.</p>
  <table><!-- Create Team Table -->
  <thead>
    <tr>
      <th>Name</th>
      <th>Description</th>
      <th>Start Time</th>
      <th>Days</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><input type="text" name="name" class="mandatory" required /></td>
      <td><input type="text" name="description" class="mandatory" required /></td>
      <td><input type="time" name="start_time" class="mandatory" required /></td>
      <td><input type="number" name="ndays" class="mandatory" required min="1" style="width:3em"sytle="width:3em"  /></td>
    </tr>
    <tr>
      <td colspan="4" align="right">
        <input type="submit" value="Save" />
        <input type="button" onclick="closeCreateDialog()" value="Cancel" />
      </td>
    </tr>
  </tbody>
  </table><!-- End Create Team Table -->
</form><!-- Close Create Team Form -->
';

//Javascript
$data .= '
<script>
function createTeam() {
	console.log("CREATE TEAM");
	document.getElementById("create_team_dialog").show();
}

function closeCreateDialog() {
	console.log("CLOSE CREATE TEAM");
	document.getElementById("create_team_form").reset();
	document.getElementById("create_team_dialog").close();
}

function disableTeam(id) {
	console.log("DISABLE "+id);
	fetch("'.$request_url.'", {
		method: "POST",
		body: "disable="+id,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
		console.log("Redirect to '.$request_url.'");
		location.href = "'.$request_url.'";
		return response.text();
	});
}

function enableTeam(id) {
	console.log("ENABLE "+id);
	fetch("'.$request_url.'", {
		method: "POST",
		body: "enable="+id,
		headers: { "Content-Type": "application/x-www-form-urlencoded", },
	})
	.then((response) => {
		console.log(response);
		console.log("Redirect to '.$request_url.'");
		location.href = "'.$request_url.'";
		return response.text();
	});
}

function addEmployee(tid) {
	console.log(`ADD EMPLOYEE ${tid}`);
	console.log(`DEPRECATED! Not currently defined`);
}

function removeEmployee(eid) {
	console.log(`REMOVE EMPLOYEE ${eid}`);
	fetch("'.$request_url.'", {
		"method": "POST",
		"body": `remove_employee=${eid}`,
		"headers": {
			"Content-Type": "application/x-www-form-urlencoded",
		},
	})
	.then((response) => {
		console.log(response);
		console.log("Redirect to '.$request_url.'");
		location.href = response.url;
		return response.text();
	});
}
</script>
';
print($data);

unset($data);
unset($display_active);
unset($employee);
unset($name);
unset($pdo);
unset($team);
unset($teamid);
unset($teams);

require_once('./footer.php');
?>
