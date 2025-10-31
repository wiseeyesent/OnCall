<?php
namespace WiseEyesEnt\OnCall2;

require_once('./dbconn.php');
require_once('./common.php');
require_once('./header.php');

require_once('./employee.php');
require_once('./team.php');
require_once('./team_schedule.php');

if(empty($pdo)) { $pdo = dbConnect(); }

if(!empty($employees)) { unset($employee); }
$employees = array();

$teams = Team::getTeamNames($pdo);

$request_method = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$request_url = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "/employees.php";
//print("teams.php: request_method = $request_method<br/>\n");
//print("teams.php: request_url = $request_url<br/>\n");
//print("teams.php: REQUEST = ".print_r($_REQUEST, true)."<br/>\n");

$order = (isset($_REQUEST['order'])) ? (string) $_REQUEST['order'] : "last_name";
$desc = (isset($_REQUEST['desc'])) ? (bool) $_REQUEST['desc'] : false;
$active = (isset($_REQUEST['active'])) ? (bool) $_REQUEST['active'] : false; 
//print("teams.php: order = $order<br/>\n");
//print("teams.php: desc = $desc<br/>\n");
//print("teams.php: active = $active<br/>\n");

//Initialize data & set page title
if(!empty($data)) { unset($data); }
$data = "<blockquote>\n"
	."  <h4>Team Employees</h4>\n"
	."  <pre>NOTE: Search is by DB ID, User ID, or name\n"
	."EXAMPLES) 1, id123, \"Joshua Cripe\", \"Cripe, Joshua\"</pre>\n"
	."</blockquote>\n";

//POST requests
if($request_method == "POST") {
	if(isset($_POST['q'])) {
		$term = (int) $_POST['q'];
		if(empty($term)) { $term = (string) $_POST['q']; }
		if(is_int($term) || \preg_match("/^[a-zA-Z][0-9]+$/", $term)) {
			header("Location: contact.php?id=$term");
		} else {
			header("Location: contact.php?name=$term");
		}
	}
}//END POST

$data .= "<button type=\"button\" onclick=\"createEmployee()\">Create Employee</button>\n";

//Employees
$employees = Employee::getAllEmployees($pdo, $order=$order, $desc=$desc, $active=$active);
$data .= "\n"
	."<table style=\"width:100%;white-space:nowrap;\">\n"
	."<thead>\n"
	."  <tr>\n"
	."    <th>Name</th>\n"
//	."    <th>Active?</th>\n"
	."    <th>UserID</th>\n"
	."    <th>Audinet</th>\n"
	."    <th>Mobile</th>\n"
	."    <th>Text Message</th>\n"
	."  </tr>\n"
	."</thead>\n"
	."<tbody>\n";

//TODO Add pagination?
foreach($employees as $employee) {
	if($employee->active) { $data .= "  <tr>\n"; }
	else { $data .= "  <tr class=\"inactive\">\n"; }

	$data .= "    <td>\n"
		."      <a href=\"contact.php?id=$employee->id\">".$employee->getEmployeeName()."</a>\n"
		."    </td>\n";

//	if($employee->active) { $data .= "    <td>Yes</td>\n"; }
//	else { $data .= "    <td>No</td>\n"; }

	if(!empty($audinet)) { unset($audinet); }
	$audinet = $employee->getEmployeeAudinet();
	if(!empty($cell)) { unset($cell); }
	$cell = $employee->getEmployeeCell();

	$data .= "    <td>$employee->userid</td>\n"
		."    <td>$audinet</td>\n"
		."    <td>$cell</td>\n"
		."    <td>$employee->email_page_corp</td>\n"
		."  </tr>\n";
}//END foreach employee

$data .= "</tbody>\n"
	."</table>\n"; 

//Javascript
$data .= '
<script>
function createEmployee() {
	location.href="contact.php?add_employee=0";
}
</script>
';

print($data);
unset($data);

require_once('./footer.php');
?>
