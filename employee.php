<?php
namespace WiseEyesEnt\OnCall2;

require_once("./OnCall2.conf.php");
require_once("./dbconn.php");

class Employee {

	//DB.employee column labels w/ nice names
	public const LABELS = array(
		"id" => "Database ID",
		"type_id" => "Employee Type",
		"userid" => "Employee ID",
		"last_name" => "Surname",
		"first_name" => "Forename",
		"nickname" => "Sobriquet",
		"tel_audi_net" => "Audinet",
		"tel_direct" => "Direct Dial",
		"tel_cell_corp" => "Primary Cell",
		"tel_cell_other" => "Other Cell",
		"tel_home_other" => "Home Phone",
		"email_corp" => "Corp Email",
		"email_page_corp" => "Text Message",
		"email_other" => "Other Email",
		"schedule" => "Schedule (DEPRECATED)", #DEPRECATED
		"active" => "Active",
		"contact_instructions" => "Contact Instructions"
	);

	//DB employee.type_id=employee_type.id
	//NOTE: Values here represent name, id
	public const EMPLOYEE_TYPE = array(
		0 => null,	//Null value as a placeholder, should not be used
		"Employee"	=> 1,
		"Contractor"	=> 2,
		"Vendor"	=> 3,
		"Position"	=> 4,
		"Group"		=> 5
	);

	# Do we need the DB ID? I don't know that we do...
	public int $id;				# unsigned int, DB ID
	public int $type_id;			# tinyint, DB employee_type.id
	public string $userid;			# tinytext, Corp
	public string $last_name;		# tinytext
	public string $first_name;		# tinytext
	public string $nickname;		# tinytext
	public int $tel_audinet;		# tinytext, Corp Phone #
	public int $tel_direct;			# tinytext, Corp Phone External #
	public int $tel_cell_corp;		# tinytext, Corp Cell, v2.1.5, Updating to "Primary Cell"
	public int $tel_cell_other;		# tinytext, Personal Cell, Displayed as "Other Cell"
	public int $tel_home_other;		# tinytext, Home Phone #
	public string $email_corp;		# tinytext, Corp E-mail
	public string $email_page_corp;		# tinytext, Cell# email, i.e. 1234567890@messaging.sprintpcs.com
	public string $email_other;		# tinytext, Personal Email
	public string $schedule;		# tinytext, i.e. M-F 0800-1700CDT DEFUNCT
	public bool $active;			# bool, Default true, set to false for "deleted" employees
	public string $contact_instructions;	# text, i.e. Backup contact
	//public array $teams;			# Which teams does this employee belong to?

	function __construct($data = null) {
		//$data represents DB results, where columns are missing, we use the DB table defaults
		if(!empty($data)) {
			if(DEBUG > 2) { print("Employee::__construct() data = ".print_r($data, true)."<br/>\n"); }

			//id=0 & userid="" can ONLY be a new employee not yet saved in the DB
			if(!empty($data["id"])) { $this->id = (int) $data["id"]; }
			else { $this->id = 0; }
			if(DEBUG > 2) { print("Employee::__construct() id = $this->id<br/>\n"); }

			//Default "Employee" : 1
			if(!empty($data["type_id"])) {
				if(\is_string($data["type_id"])) {
					$this->type_id = (int) Employee::EMPLOYEE_TYPE[$data["type_id"]];
				} else {
					$key = \array_search($data["type_id"], Employee::EMPLOYEE_TYPE);
					$this->type_id = (int) Employee::EMPLOYEE_TYPE[$key];
				}
			} else { $this->type_id = (int) Employee::EMPLOYEE_TYPE["Employee"]; }
			if(DEBUG > 2) {
				print("Employee::__construct() type_id = "
					.print_r($this->type_id, true)."<br/>\n");
			}

			//NOT NULL-->
			if(!empty($data["userid"])) { $this->userid = (string) $data["userid"]; }
			else { $this->userid = ""; }
			if(DEBUG > 2) {
				print("Employee::__construct() userid = $this->userid<br/>\n");
			}

			if(!empty($data["last_name"])) { $this->last_name = (string) $data["last_name"]; }
			else { $this->last_name = ""; }
			if(DEBUG > 2) {
				print("Employee::__construct() last_name = $this->last_name<br/>\n");
			}

			if(!empty($data["first_name"])) { $this->first_name = (string) $data["first_name"]; }
			else { $this->first_name = ""; }
			if(DEBUG > 2) {
				print("Employee::__construct() first_name = $this->first_name<br/>\n");
			}
			//<-- NOT NULL

			//Default NULL -->
			if(!empty($data["nickname"])) { $this->nickname = (string) $data["nickname"]; }
			else { $this->nickname = ""; }
			if(DEBUG > 2) {
				print("Employee::__construct() nickname = $this->nickname<br/>\n");
			}

			if(!empty($data["tel_audinet"])) { $this->tel_audinet = (int) $data["tel_audinet"]; }
			else { $this->tel_audinet = 0; }
			if(DEBUG > 2) {
				print("Employee::__construct() tel_audinet = ".print_r($this->tel_audinet, true)."<br/>\n");
			}

			if(!empty($data["tel_direct"])) {
				$this->tel_direct = (int) $data["tel_direct"];
			} else { $this->tel_direct = 0; }
			if(DEBUG > 2) {
				print("Employee::__construct() tel_direct = ".print_r($this->tel_direct, true)."<br/>\n");
			}

			if(!empty($data["tel_cell_corp"])) {
				$this->tel_cell_corp = (int) $data["tel_cell_corp"];
			} else { $this->tel_cell_corp = 0; }
			if(DEBUG > 2) {
				print("Employee::__construct() tel_cell_corp = ".print_r($this->tel_cell_corp, true)."<br/>\n");
			}

			if(!empty($data["tel_cell_other"])) {
				$this->tel_cell_other = (int) $data["tel_cell_other"];
			} else { $this->tel_cell_other = 0; }
			if(DEBUG > 2) {
				print("Employee::__construct() tel_cell_other = ".print_r($this->tel_cell_other, true)."<br/>\n");
			}

			if(!empty($data["tel_home_other"])) {
				$this->tel_home_other = (int) $data["tel_home_other"];
			} else { $this->tel_home_other = 0; }
			if(DEBUG > 2) {
				print("Employee::__construct() tel_home_other = ".print_r($this->tel_home_other, true)."<br/>\n");
			}

			if(!empty($data["email_corp"])) {
				$this->email_corp = (string) $data["email_corp"];
			} else { $this->email_corp = ""; }
			if(DEBUG > 2) {
				print("Employee::__construct() email_corp = $this->email_corp<br/>\n");
			}

			if(!empty($data["email_page_corp"])) {
				$this->email_page_corp = (string) $data["email_page_corp"];
			} else { $this->email_page_corp = ""; }
			if(DEBUG > 2) {
				print("Employee::__construct() email_page_corp = $this->email_page_corp<br/>\n");
			}

			if(!empty($data['email_other'])) {
				$this->email_other = (string) $data['email_other'];
			} else { $this->email_other = ""; }
			if(DEBUG > 2) {
				print("Employee::__construct() email_other = $this->email_other<br/>\n");
			}

			if(!empty($data["schedule"])) {
				$this->schedule = (string) $data["schedule"];
			} else { $this->schedule = ""; }
			if(DEBUG > 2) {
				print("Employee::__construct() schedule = $this->schedule<br/>\n");
			}
			//<-- Default NULL

			//Default TRUE (1)
			if(!empty($data['active'])) {
				//if($data['active'] === "1" || $data['active'] == 1) { $this->active = true; }
				$this->active = ((string) $data['active'] === "1") ? true : false;
			}
			if(empty($this->active)) { $this->active = false; }
			if(DEBUG > 2) {
				print("Employee::__construct() active = ".((string) $this->active)."<br/>\n");
			}

			//Default NULL
			if(!empty($data["contact_instructions"])) {
				$this->contact_instructions = (string) $data["contact_instructions"];
			} else { $this->contact_instructions = ""; }
			if(DEBUG > 2) {
				print("Employee::__construct() contact_instructions = "
					."$this->contact_instructions<br/>\n");
			}
		} else {
			//Initialize default values for a new employee
 			//Will be overwritten by the DB add
			$this->id = 0;
			//DB Defaults
			$this->type_id = (int) Employee::EMPLOYEE_TYPE['Employee'];
			$this->active = true;
			//Needs to be initialized before being saved
			$this->userid = ''; 
			$this->last_name = '';
			$this->first_name = '';
			$this->nickname = '';
			$this->tel_audinet = 0;
			$this->tel_cell_corp = 0;
			$this->tel_cell_other = 0;
			$this->tel_home_other = 0;
			$this->email_corp = '';
			$this->email_page_corp = '';
			$this->email_other = '';
			$this->schedule = '';
			$this->contact_instructions = '';
		}
		if(DEBUG > 2) {
			print("Employee::__construct() this = ".print_r($this, true)."<br/>\n");
		}

		return $this;
	}

	static function getAllEmployees($pdo, $order="", $desc=false, $active=false) {
		//print("Employee::getAllEmployees() order=$order, desc=$desc, active=$active<br/>\n");
		if(empty($pdo)) { $pdo = dbConnect(); }
		$employees = array();
		unset($query);
		unset($result);
                // 2025-02-03 JFE: DO NOT display inactive employees in the web UI.
		$query = "SELECT * FROM employee WHERE active!=0";
		//if($active) { $query .= " WHERE active=TRUE"; }
		if(!empty($order)) { $query .= " ORDER BY $order"; }
		if($desc) { $query .= " DESC"; }
		//print("Employee::getAllEmployees() query = $query<br/>\n");
		$result = dbQuery($pdo, $query);
		if(!empty($result)) {
			foreach($result as $row) { $employees[] = new Employee($row); }
		}
		return $employees;
	}

	static function getEmployee($pdo, $id=0, $name="") {
		if(DEBUG > 2) { print("Employee::getEmployee() id = $id, name = $name<br/>\n"); }
		if(empty($pdo)) { $pdo = dbConnect(); }
		unset($result);
		unset($employee);
		if(!empty($id)) {
			$id_query = "SELECT * FROM employee WHERE ";
			$dbid = !empty((int) $id) ? (int) $id : (string) $id;
			if(DEBUG > 2) {
				print("Employee::getEmployee($id) dbid = $dbid; is_int? "
					.\is_int($dbid)."; is_string? ".\is_string($dbid)."<br/>\n");
			}

			if(\is_int($dbid)) {
				$id_query .= "id=$dbid";
			} else if(\is_string($dbid)) {
				$id_query .= "userid='$dbid'";
			} else {
				throw new \Exception($message="ERROR! Employee::getEmployee() Given invalid id",
					$code=101);
			}

			if(DEBUG > 2) { print("Employee::getEmployee() id_query = $id_query<br/>\n"); }
			$id_result = dbQuery($pdo, $id_query);
			if(DEBUG > 2) {
				print("Employee::getEmployee() id_result = ".print_r($id_result, true)."<br/>\n");
			}
			unset($id_query);
		}
		if(!empty($name)) {
			$name_query = "SELECT * FROM employee WHERE CONCAT(last_name,', ',first_name)='$name'";
			if(DEBUG > 2) { print("Employee::getEmployee() name_query = $name_query<br/>\n"); }
			$name_result = dbQuery($pdo, $name_query);
			if(DEBUG > 2) {
				print("Employee::getEmployee() name_result = "
					.print_r($name_result, true)."<br/>\n");
			}
			unset($name_query);
		}

		$result = array();
		if(!empty($id_result)) {
			$result = $id_result;
		}
		if(!empty($name_result)) {
			if(!empty($result)) {
				if($result[0]['id'] != $name_result[0]['id']) {
					$result = \array_merge($result, $name_result);
				}
			} else { $result = $name_result; }
		}
		if(DEBUG > 2) { print("Employee::getEmployee() result = ".print_r($result, true)."<br/>\n"); }

		if(empty($result)) {
			throw new \Exception($message="ERROR! Employee::getEmployee() No result found",
				$code=101);
		} else if(\count($result) > 1) {
			throw new \Exception($message="ERROR! Employee::getEmployee() Multiple results",
				$code=102);
		} else { $result = $result[0]; }
		if(DEBUG > 2) {
			print("Employee::getEmployee(id=$id, name=$name): "
				."result = ".print_r($result, true)."<br/>\n");
		}

		if(empty($result)) { return null; }
		$employee = new Employee($result);
		if(DEBUG > 2) {
			print("Employee::getEmployee() employee = ".print_r($employee, true)."<br/>\n");
		}

		unset($dbid);
		unset($query);
		unset($result);
		unset($id_result);
		unset($name_result);
		return $employee;
	}

	static function getEmployees($pdo, array $ids) {
		if(empty($pdo)) { $pdo = dbConnect(); }
		unset($query);
		unset($result);
		unset($employees);
		$employees = array();
		$query = "SELECT * FROM employee WHERE (";
		foreach($ids as $id) { $query .= "id=$id OR "; }
		$query = \preg_replace("/ OR $/", ") ORDER BY last_name", $query);
		//print("Employee::getEmployees() query = $query<br/>\n");
		$result = dbQuery($pdo, $query);
		//print("Employee::getEmployees() result = ".print_r($result, true)."<br/>\n");
		foreach($result as $row) { $employees[] = new Employee($row); }
		//print("Employee::getEmployees() employees = ".print_r($employees, true)."<br/>\n");
		unset($query);
		unset($result);
		unset($row);
		return $employees;
	}

	static function getIdByName($pdo, $name) {
		if(DEBUG > 2) { print("Employee::getIdByName() name = $name<br/>\n"); }

		$retVal = 0;
		$query = "SELECT id FROM employee "
			."WHERE userid = '$name' "
			."OR first_name LIKE '%$name%' "
			."OR last_name LIKE '%$name%' "
			."OR nickname LIKE '%$name%' "
			."OR CONCAT(last_name,', ',first_name) = '$name' "
			."OR CONCAT(last_name,', ',nickname) = '$name' "
			."OR CONCAT(first_name,' ',last_name) = '$name' "
			."OR CONCAT(nickname,' ',last_name) = '$name'";
		if(DEBUG > 2) { print("Employee::getIdByName() query = $query<br/>\n"); }

		$result = dbQuery($pdo, $query);
		if(DEBUG > 2) { print("Employee::getIdByName() result = ".print_r($result, true)."<br/>\n"); }

		if(empty($result)) {
			throw new \Exception($message="ERROR! Employee::getIdByName($name) No results found",
				$code=101);
		} else if(\count($result) > 1) {
			throw new \Exception($message="ERROR! Employee::getIdByName($name) Multiple results",
				$code=102);
		} else { $retVal = (int) $result[0]["id"]; }
		if(DEBUG > 2) { print("Employee::getIdByName() retVal = $retVal<br/>\n"); }

		unset($query);
		unset($result);
		return $retVal;
	}

	function save($pdo) {
		if(empty($pdo)) { $pdo = dbConnect(); }

		if(!empty($active)) { unset($active); }
		if($this->active) { $active = 1; }
		else { $active = 0; }

		if(empty($this->userid) || empty($this->last_name) || empty($this->first_name)) {
			throw new \Exception($message="ERROR! Employee::save() Missing required data!"
				.print_r($this, true),
				$code=121);
		}

		//UPDATE 2.1.5 CHANGED PHONE NUMBERS FROM TEXT TO BIGINT UNSIGNED
		$tel_audinet = (empty($this->tel_audinet)) ? "NULL" : (int) $this->tel_audinet;
		$tel_direct = (empty($this->tel_direct)) ? "NULL" : (int) $this->tel_direct;
		$tel_cell_corp = (empty($this->tel_cell_corp)) ? "NULL" : (int) $this->tel_cell_corp;
		$tel_cell_other = (empty($this->tel_cell_other)) ? "NULL" : (int) $this->tel_cell_other;
		$tel_home_other = (empty($this->tel_home_other)) ? "NULL" : (int) $this->tel_home_other;

		unset($query);
		unset($result);
		//An existing ID means we are likely updating an existing employee
		if(!empty($this->id)) {
			$query = "UPDATE employee SET "
				."type_id=$this->type_id, "
				."last_name='$this->last_name', first_name='$this->first_name', "
				."nickname='$this->nickname', "
				."tel_audinet=$tel_audinet, tel_direct=$tel_direct, "
				."tel_cell_corp=$tel_cell_corp, tel_cell_other=$tel_cell_other, "
				."tel_home_other=$tel_home_other, "
				."email_corp='$this->email_corp', email_page_corp='$this->email_page_corp', "
				."email_other='$this->email_other', "
				."schedule='$this->schedule', active=$active, "
				."contact_instructions='$this->contact_instructions' "
				."WHERE id=$this->id";

			//$query = "INSERT IGNORE INTO employee (id, type_id, userid, last_name, first_name, nickname, "
			//	."tel_audinet, tel_direct, tel_cell_corp, tel_cell_other, tel_home_other, "
			//	."email_corp, email_page_corp, email_other, "
			//	."schedule, active, contact_instructions) "
			//	."VALUES "
			//	."($this->id, ".$this->type_id.", '$this->userid', "
			//	."'$this->last_name', '$this->first_name', '$this->nickname', "
			//	."'$this->tel_audinet', '$this->tel_direct', '$this->tel_cell_corp', "
			//	."'$this->tel_cell_other', '$this->tel_home_other', "
			//	."'$this->email_corp', '$this->email_page_corp', '$this->email_other', "
			//	."'$this->schedule', $active, '$this->contact_instructions')";
			//	//NOTE: Changed to INSERT IGNORE to prevent overwriting existing users
			//	//."ON DUPLICATE KEY UPDATE type_id=VALUES(type_id), "
			//	//."nickname=VALUES(nickname), "
			//	//."tel_audinet=VALUES(tel_audinet), tel_direct=VALUES(tel_direct), "
			//	//."tel_cell_corp=VALUES(tel_cell_corp), tel_cell_other=VALUES(tel_cell_other), "
			//	//."tel_home_other=VALUES(tel_home_other), "
			//	//."email_corp=VALUES(email_corp), email_page_corp=VALUES(email_page_corp), "
			//	//."email_other=VALUES(email_other), "
			//	//."schedule=VALUES(schedule), active=VALUES(active), "
			//	//."contact_instructions=VALUES(contact_instructions)";
		} else {
			//...otherwise, we're likely creating a new employee
			$query = "INSERT INTO employee (type_id, userid, last_name, first_name, nickname, "
				."tel_audinet, tel_direct, tel_cell_corp, tel_cell_other, tel_home_other, "
				."email_corp, email_page_corp, email_other, "
				."schedule, active, contact_instructions) "
				."VALUES "
				."(".$this->type_id.", '$this->userid', "
				."'$this->last_name', '$this->first_name', '$this->nickname', "
				."$tel_audinet, $tel_direct, $tel_cell_corp, "
				."$tel_cell_other, $tel_home_other, "
				."'$this->email_corp', '$this->email_page_corp', '$this->email_other', "
				."'$this->schedule', $active, '$this->contact_instructions') "
				."ON DUPLICATE KEY UPDATE "
				."type_id=VALUES(type_id), nickname=VALUES(nickname), "
				."tel_audinet=VALUES(tel_audinet), tel_direct=VALUES(tel_direct), "
				."tel_cell_corp=VALUES(tel_cell_corp), tel_cell_other=VALUES(tel_cell_other), "
				."tel_home_other=VALUES(tel_home_other), "
				."email_corp=VALUES(email_corp), email_page_corp=VALUES(email_page_corp), "
				."email_other=VALUES(email_other), "
				."schedule=VALUES(schedule), active=VALUES(active), "
				."contact_instructions=VALUES(contact_instructions)";
		} //END if ID
 
		//edit_log
		if(!empty($date)) { unset($date); }
		if(!empty($description)) { unset($description); }
                if(!empty($edit_query)) { unset($edit_query); }
                if(!empty($edit_result)) { unset($result); }
		if(!empty($log_query)) { unset($log_query); }
                if(!empty($old_value)) { unset($old_value); }
                if(!empty($remote_addr)) { unset($remote_addr); }
                if(!empty($remote_user)) { unset($remote_user); }
                if(!empty($timestamp)) { unset($timestamp); }

		//Check if employee exists, relevant to old_value & description
                $edit_query = "SELECT * FROM employee WHERE id=$this->id OR "
				."userid='$this->userid'";
		//print("Employee::save() edit_query = $edit_query<br/>\n");
                $edit_result = dbQuery($pdo, $edit_query);
		//print("Employee::save() edit_result = ".print_r($edit_result, true)."<br/>\n");

		//TODO Fix REMOTE_USER
		$remote_addr = (!empty($_SERVER['REMOTE_ADDR'])) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		$remote_user = (!empty($_SERVER['REMOTE_USER'])) ? (string) $_SERVER['REMOTE_USER'] : '';

		//Prep that timestamp...
		unset($date);
		unset($timestamp);
		$date = new \DateTimeImmutable("now", new \DateTimeZone("UTC"));
		$timestamp = $date->format("Y-m-d H:i:s");

		//edit_log.query value
		//NOTE: We have to escape quotes to preserve valid SQL syntax
		$log_query = \str_replace(array("'", "\"", "`"), "\'", $query);
		while(\str_contains($log_query, "  ")) {
			$log_query = \str_replace("  ", " ", $log_query);
		}

		if(!empty($edit_result)) {
			$old_value = new Employee($edit_result[0]);
			$old_value = \str_replace(array("\r\n", "\r", "\n"),
						 "",
						 print_r($old_value, true));
			while(\str_contains($old_value, "  ")) {
				$old_value = \str_replace("  ", " ", $old_value);
			}
			$description = 'Update Existing Employee, id='.$this->id.', '
					.'userid='.$this->userid;
		} else {
			$old_value = '';
			$description = 'Create New Employee, userid='.$this->userid;
		}

		$edit_query = "INSERT INTO edit_log (timestamp, remote_addr, remote_user, "
				."query, description, old_value) VALUES "
				."('$timestamp', '$remote_addr', '$remote_user', "
				."'$log_query', '$description', '$old_value')";
		//print("Employee::save() edit_query = $edit_query<br/>\n");
		$edit_result = dbInsert($pdo, $edit_query);
		//print("Employee::save() edit_result = ".print_r($edit_result, true)."<br/>\n");
		//print("Employee::save() query = $query<br/>\n");
		if($edit_result) { $result = dbInsert($pdo, $query); }
		else {
			throw new \Exception($message="ERROR! Employee::save() edit_log FAILED",
				$code=123);
		}
		//print("Employe::save() result = ".print_r($result, true)."<br/>\n");

		//If a new employee, lets update that ID
		if($result && empty($this->id)) {
			$edit_query = "SELECT id FROM employee WHERE userid='$this->userid'";
			//print("Employee::save() edit_query = $edit_query<br/>\n");
			$edit_result = dbQuery($pdo, $query);
			//print("Employee::save() edit_result = ".print_r($edit_result, true)."<br/>\n");
			if(!empty($edit_result)) { $this->id = $edit_result[0]['id']; }
		}
		unset($date);
		unset($description);
		unset($edit_query);
		unset($edit_result);
		unset($old_val);
		unset($log_query);
		unset($query);
		unset($remote_addr);
		unset($remote_user);
		unset($timestamp);
		return $result;
	}

	function getEmployeeName() {
		unset($name);
		if(!empty($this->nickname)) {
			$name = $this->nickname." ".$this->last_name;
		} else {
			$name = $this->first_name." ".$this->last_name;
		}
		return $name;
	}

	function getEmployeeAudinet() {
		if(DEBUG > 2) {
			print("employee.php::getEmployeeAudinet() this = ".print_r($this, true)."<br/>\n");
		}
		if(!empty($audinet)) { unset($audinet); }
		$audinet = (int) $this->tel_audinet;
		if(!empty($audinet)) {
			if(\strlen($audinet) < 8) { $audinet = \str_pad((string)$audinet, 8, "0", STR_PAD_LEFT); }
			$audinet = \sprintf("%s-%s-%s",
					\substr($audinet, 0, 1),
					\substr($audinet, 1, 3),
					\substr($audinet, 4));
		} else { $audinet = ""; }
		if(DEBUG > 2) {
			print("employee.php::getEmployeeAudinet() audinet = $audinet<br/>\n");
		}
		return $audinet;
	}
	
	function getEmployeeCell() {
		if(DEBUG > 2) {
			print("employee.php::getEmployeeCell() this = ".print_r($this, true)."<br/>\n");
		}

		if(!empty($cell)) { unset($cell); }
		$cell = "";
		if(!empty($this->tel_cell_corp)) {
			$cell = (int) $this->tel_cell_corp;
		} else if(!empty($this->tel_cell_other)) {
			$cell = (int) $this->tel_cell_other;
		}
		if(!empty($cell)) {
			$cell = formatPhoneNumber((int)$cell);
		} else { $cell = ""; }
		if(DEBUG > 2) {
			print("employee.php::getEmployeeCell() cell = $cell<br/>\n");
		}
		return $cell;
	}

	function getEmployeeTeamNames($pdo) {
		if(empty($pdo)) { $pdo = dbConnect(); }

		if(!empty($teams)) { unset($teams); }
		$teams = array();

		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }

		$query = "SELECT te.team_id,t.name FROM team_employees te "
			."LEFT JOIN team t ON te.team_id=t.id "
			."WHERE employee_id='$this->id' "
			."ORDER BY team_id";
		$result = dbQuery($pdo, $query);
		if(!empty($result)) {
			foreach($result as $row) {
				$teams[] = array("id" => $row['team_id'],
						 "name" => $row['name']);
			}
		}
		unset($query);
		unset($result);
		unset($row);
		return $teams;
	}

	function setEmployeeTeams($pdo, array $team_ids) {
		if(empty($pdo)) { $pdo = dbConnect(); }
		//print("Employee::setEmployeeTeams() team_ids = ".print_r($team_ids, true)."<br/>\n");

		$timestamp = (new \DateTimeImmutable("now", new \DateTimeZone("UTC")))->format("Y-m-d H:i:s");

		$remote_addr = !empty($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		$remote_user = !empty($_SERVER['REMOTE_USER']) ? (string) $_SERVER['REMOTE_USER'] : '';

		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }
		if(!empty($edit_query)) { unset($edit_query); }
		if(!empty($edit_result)) { unset($edit_result); }
		if(!empty($old_teams)) { unset($old_teams); }

		$add_query = "";
		if(!empty($team_ids)) {
			$add_query = "INSERT IGNORE INTO team_employees (team_id, employee_id) "
				."VALUES ";
			foreach($team_ids as $team_id) {
				$add_query .= "($team_id, $this->id), ";
			}
			$add_query = \preg_replace("/, $/", "", $add_query);
		} else { $add_query = ""; }

		$edit_query = "SELECT team_id FROM team_employees WHERE "
				."employee_id=$this->id "
				."ORDER BY team_id";
		//print("Employee::setEmployeeTeams().edit_log edit_query = $edit_query<br/>\n");
		$edit_result = dbQuery($pdo, $edit_query);
		//print("Employee::setEmployeeTeams().edit_log edit_result = ".print_r($edit_result, true)."<br/>\n");
		if(!empty($edit_result)) {
			$old_teams = array();
			foreach($edit_result as $row) { $old_teams[] = $row['team_id']; }
			//print("Employee::setEmployeeTeams() old_teams = "
			//	.print_r($old_teams, true)."<br/>\n");
			$description = "Update Existing Employee Teams, employee_id=$this->id, "
					."teams=[";
			$old_value = "teams=[";
			foreach($old_teams as $team_id) {
				$old_value .= "$team_id, ";
			}
			$old_value = \preg_replace("/, $/", "]", $old_value);
			//unset($iter);
			//unset($team_id);
		} else {
			$old_value = "";
			$description = "Add New Teams for Employee, employee_id=$this->id, teams=[";
		}
		//print("Employee::setEmployeeTeams().edit_log old_value = $old_value<br/>\n");
		foreach($team_ids as $team_id) {
			$description .= "$team_id, ";
		}
		$description = \preg_replace("/, $/", "]", $description);
		unset($team_id);
		//print("Employee::setEmployeeTeams().edit_log description = $description<br/>\n");

		$log_query = \str_replace(array("\"", "'", "`"), "\'", $add_query);

		//Remove old teams...
		//print("Employee::setEmployeeTeams().DELETE old_teams = "
		//	.print_r($old_teams, true)."<br/>\n");
		$delete_teams = false;
		if(!empty($old_teams)) {
			$del_query = "DELETE FROM team_employees WHERE employee_id=$this->id AND (";
			foreach($old_teams as $oldteam_id) {
				if(!\in_array($oldteam_id, $team_ids)) {
					$delete_teams = true;
					//print("Employee::setEmployeeTeams().DELETE team_id = $team_id<br/>\n");
					$del_query .= "team_id=$oldteam_id OR ";
				}
			}
			if($delete_teams) { $del_query = \preg_replace("/ OR $/", ")", $del_query); }
			else { $del_query = ""; }
		}
		unset($old_teams);
		unset($oldteam_id);

		$edit_query = "INSERT INTO edit_log (timestamp, remote_addr, remote_user, "
				."query, description, old_value) "
				."VALUE "
				."('$timestamp', '$remote_addr', '$remote_user', "
				."'$log_query', '$description', '$old_value')"; 
		//print("Employee::setEmployeeTeams().INSERT edit_query = $edit_query<br/>\n");
		$edit_result = dbInsert($pdo, $edit_query);
		//print("Employee::setEmployeeTeams().INSERT edit_result = ".print_r($edit_result, true)."<br/>\n");

		//Free up memory
		unset($old_teams);
		unset($oldteam_id);
		unset($delete_teams);
		unset($description);
		unset($edit_query);
		unset($log_query);
		unset($old_value);
		unset($remote_addr);
		unset($remote_user);
		unset($timestamp);
		
		//If edit_log failed, quit
		if(!$edit_result) { return false; }
		else { $result = $edit_result; }
		//...otherwise, If we have changes, attempt them
		if(!empty($add_query)) {
			//print("Employee::setEmployeeTeams() add_query = $add_query<br/>\n");
			$result = dbInsert($pdo, $add_query);
			//print("Employee::setEmployeeTeams() result = ".print_r($result, true)."<br/>\n");
		}
		if(!$result) { return $result; } // If adding new teams failed, quit

		if(!empty($del_query)) {
			$result = dbQuery($pdo, $del_query);
		} else { $result = true; }

		unset($add_query);
		unset($del_query);
		unset($edit_result);
		unset($query);
		return $result;
	}

	function getLabels() {
		unset($labels);
		$labels = array(
			"id" => "Database ID",
			"type_id" => "Employee Type",
			"userid" => "Employee ID",
			"last_name" => "Surname",
			"first_name" => "Forename",
			"nickname" => "Sobriquet",
			"tel_audi_net" => "Audinet",
			"tel_direct" => "Direct Dial",
			"tel_cell_corp" => "Primary Cell",
			"tel_cell_other" => "Other Cell",
			"tel_home_other" => "Home Phone",
			"email_corp" => "Corp Email",
			"email_page_corp" => "Text Message",
			"email_other" => "Other Email",
			//"schedule" => "Schedule (DEPRECATED)", #DEPRECATED
			"active" => "Active",
			"contact_instructions" => "Contact Instructions"
		);
		return $labels;
	}
}
