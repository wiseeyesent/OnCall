<?php
namespace WiseEyesEnt\OnCall2;

require_once('./dbconn.php');
require_once('./employee.php');
require_once('./team_schedule.php');
require_once('./teamschedule_template.php');

class Team {
	public int $id;
	public string $name;
	public string $start_time;
	public int $ndays;
	public bool $active;
	public string $description;
	public array $employees;

	function __construct(array $data=null) {
		if(DEBUG > 2) { print("Team::__construct() data = ".print_r($data, true)."<br/>\n"); }
		if(!empty($data)) {
			//var_dump($data);
			//If id=0, this is probably a new team
			if(!empty($data['id'])) { $this->id = (int) $data['id']; }
				else { $id = 0; }
			//Use DB Defaults where no data is provided
			if(!empty($data['name'])) { $this->name = (string) $data['name']; }
				else { $this->name = ""; }
			//Use DB defaults for the rest
			if(!empty($data['start_time'])) { $this->start_time = (string) $data['start_time']; }
				else { $this->start_time = '12:00:00'; }
			if(!empty($data['ndays'])) { $this->ndays = (int) $data['ndays']; }
				else { $this->ndays = 7; }
			if(!empty($data['active'])) { $this->active = (bool) $data['active']; }
				else { $this->active = true; }
			if(!empty($data['description'])) { $this->description = (string) $data['description']; }
				else { $this->description = ''; }
			if(!empty($data['employees'])) { $this->employees = (array) $data['employees']; }
				else { $this->employees = array(); }
		} else {
			//Initialize with DB defaults
			$this->id = 0;
			$this->name = '';
			$this->start_time = '12:00:00';
			$this->ndays = 7;
			$this->active = true;
			$this->description = '';
			$this->employees = array();
		}

		if(DEBUG > 2) { print("Team::__construct() this = ".print_r($this, true)."<br/>\n"); }
		return $this;
	}

	function applyTeamScheduleTemplates($pdo) {
		if(DEBUG > 2) {
			print("Team::applyTeamScheduleTemplates this = ".print_r($this, true)."<br/>\n");
		}

		if(empty($pdo)) { $pdo = dbConnect(); }
		if(DEBUG > 2) {
			print("Team::applyTeamScheduleTemplates() pdo = ".print_r($pdo, true)."<br/>\n");
		}

		if(!empty($last_schedule)) { unset($last_schedule); }
		$last_schedule = $this->getLastTeamSchedule($pdo);
		if(DEBUG > 2) {
			print("Team::applyTeamScheduleTemplates() last_schedule = "
				.print_r($last_schedule, true)."<br/>\n");
		}

		$templates = $this->getTeamScheduleTemplates($pdo);
		if(DEBUG > 2) {
			print("Team::applyTeamScheduleTemplates() templates = "
				.print_r($templates, true)."<br/>\n");
		}

		foreach($templates as $template) {
			if(DEBUG > 2) {
				print("Team::applyTeamScheduleTemplates().foreach template = "
					.print_r($template, true)."<br/>\n");
			}

			if(DEBUG > 2) {
				print("Team::applyTeamScheduleTemplates().foreach(template) "
					."last_schedule = ".print_r($last_schedule, true)."<br/>\n");
			}

			$days_offset = $template->days_offset;
			if(DEBUG > 2) {
				print("Team::applyTeamScheduleTemplates().foreach(template) "
					."days_offset = $days_offset<br/>\n");
			}

			if(!empty($start_date)) { unset($start_date); }
			$start_date = clone $last_schedule->start_date;
			$start_date = $start_date->modify("+$days_offset days");
			if(DEBUG > 2) {
				print("Team::applyTeamScheduleTemplates().foreach(template) start_date = "
					.print_r($start_date, true)."<br/>\n");
			}

			if(empty($template->employees)) {
				$template->getTeamScheduleTemplateEmployees($pdo);
			}
			$employees = $template->employees;

			if(!empty($data)) { unset($data); }
			$data = [ "team_id" => $template->team_id,
				"start_date" => $start_date->format("Y-m-d H:i"),
				"employees" => $employees,
				"active" => TRUE 
			];
			if(DEBUG > 2) {
				print("Team::applyTeamScheduleTemplates().foreach(template) data = "
					.print_r($data, true)."<br/>\n");
			}

			$schedule = new TeamSchedule($data);
			$result = $schedule->save($pdo);
			if(empty($result)) {
				throw new \Exception($message="Team::applyTeamScheduleTemplates()::"
					."TeamSchedule::save() "
					."ERROR! Failed to save new schedule! "
					."schedule = ".print_r($schedule, true),
					$code=321);
			} else { $result = true; }

			if(DEBUG > 2) {
				print("Team::applyTeamScheduleTemplates().foreach schedule = "
					.print_r($schedule, true)."<br/>\n");
			}

			if(!empty($schedule)) { unset($schedule); }
			if(!empty($days_offset)) { unset($days_offset); }
			if(!empty($start_date)) { unset($start_date); }
			if(!empty($employees)) { unset($employee); }
		}

		if(DEBUG > 2) { print("Team::applyTeamScheduleTemplates() result = $result<br/>\n"); }
		return $result;
	}

	//NOTE Assumes ALL values are set
	function save($pdo) {
		unset($query);
		$active = ($this->active) ? 1 : 0;
		$name = \str_replace(array("\"", "'", "`"), "", $this->name);

		//If we have an ID, we're probably updating an existing team
		if(!empty($this->id)) {
			$query = "INSERT INTO team (id, name, start_time, ndays, active, description) VALUES "
				."($this->id, '$name', "
				."'$this->start_time', $this->ndays, $active, '$this->description') "
				."ON DUPLICATE KEY UPDATE name=VALUES(name), "
				."start_time=VALUES(start_time), ndays=VALUES(ndays), "
				."active=VALUES(active), description=VALUES(description)";
		} else {
		//No ID means this is probably a new entry
			$query = "INSERT INTO team (name, start_time, ndays, active, description) VALUES "
				."('$name', '$this->start_time', $this->ndays, "
				."$active, '$this->description') "
				."ON DUPLICATE KEY UPDATE name=VALUES(name), "
				."start_time=VALUES(start_time), ndays=VALUES(ndays), "
				."active=VALUES(active), description=VALUES(description)";
		}
		if(DEBUG > 2) { print("Team::save() query = $query<br/>\n"); }

		//edit_log
		unset($edit_description);
		unset($edit_query);
		unset($edit_result);
		unset($log_query);
		unset($old_value);
		unset($remote_addr);
		unset($remote_user);
		unset($timestamp);
		$edit_query = "SELECT * FROM team WHERE id=$this->id OR name='$name'";
		$edit_result = dbQuery($pdo=$pdo, $edit_query);
		if(DEBUG > 2) {
			print("Team::save() edit_query = $edit_query<br/>\n");
			print("Team::save() edit_result = ".print_r($edit_result, true)."<br/>\n");
		}
		if(!empty($edit_result)) {
			$old_value = print_r(new Team($edit_result[0]), true);
			$old_value = \str_replace(array("\r\n", "\r", "\n",),
						 " ",
						 $old_value);
			while(\str_contains($old_value, "  ")) {
				$old_value = \str_replace("  ", " ", $old_value);
			}
			$edit_description = "Update Existing Team, id=$this->id name=$name";
		} else {
			$old_value = "";
			$edit_description = "Create New Team, name=$name";
		}
		if(DEBUG > 2) {
			print("Team::save() old_value = ".print_r($old_value, true)."<br/>\n");
			print("Team::save() edit_description = $edit_description<br/>\n");
		}
		$log_query = str_replace(array("'", "\"", "`"), "\'", $query);
		$remote_addr = !empty($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : "";
		$remote_user = !empty($_SERVER['REMOTE_USER']) ? (string) $_SERVER['REMOTE_USER'] : "";
		//$timestamp = \date_format(\date("now", new \DateTimeZone("UTC")), 'Y-m-d H:i:s');
		$date = new \DateTimeImmutable("now", new \DateTimeZone("UTC"));
		$timestamp = $date->format("Y-m-d H:i:s");
		unset($date);
		if(DEBUG > 2) {
			print("Team::save() log_query = $log_query<br/>\n");
			print("Team::save() remote_addr = $remote_addr<br/>\n");
			print("Team::save() remote_user = $remote_user<br/>\n");
			print("Team::save() timestamp = $timestamp<br/>\n");
		}
		$edit_query = "INSERT INTO edit_log (timestamp, remote_addr, remote_user, "
				."query, description, old_value) VALUES "
				."('$timestamp', '$remote_addr', '$remote_user', "
				."'$log_query', '$edit_description', '$old_value')";
		if(DEBUG > 2) { print("Team::save():INSERT edit_query = $edit_query<br/>\n"); }
		$edit_result = dbInsert($pdo, $edit_query);
		if(DEBUG > 2) {
			print("Team::save():INSERT edit_result = "
				.print_r($edit_result, true)."<br/>\n");
		}
		$retVal = dbInsert($pdo, $query);
		if(DEBUG > 2) { print("Team::save():INSERT retVal = ".print_r($retVal, true)."<br/>\n"); }
		unset($query);
		unset($result);

		//if($retVal && !empty($this->employees)) {
		if($retVal) {
			$del_employees = array();
			$add_employees = array();

			$edit_query = "SELECT employee_id FROM team_employees WHERE team_id=$this->id "
					."ORDER BY employee_id";
			$edit_result = dbQuery($pdo, $edit_query);

			foreach($this->employees as $employee_id) {
				if(!\in_array($employee_id, $edit_result)) {
					$add_employees[] = $employee_id;
				}
			}//END add_employees

			if(!empty($edit_result)) {
				foreach($edit_result as $row) {
					if(!\in_array($row['employee_id'], $this->employees)) {
						$del_employees[] = $row['employee_id'];
					}
				}
			}//END del_employees

			if(!empty($add_employees)) {
				$old_value = '';
				$edit_description = "Adding Team Employees, team_id=$this->id, employee_id=[";
				$query = "INSERT IGNORE INTO team_employees (team_id, employee_id) VALUES ";
				foreach($add_employees as $employee_id) {
					$edit_description .= "$employee_id, ";
					$query .= "($this->id, $employee_id), ";
				}
				$edit_description = \preg_replace("/, $/", "]", $edit_description);
				$query = \preg_replace("/, $/", "", $query);

				$log_query = \str_replace(array("\"", "'", "`"), "\'", $query);
				while(\str_contains($log_query, "  ")) {
					$log_query = \str_replace("  ", " ", $log_query);
				}

				$edit_query = "INSERT INTO edit_log (timestamp, remote_addr, remote_user, "
						."query, description, old_value) VALUE "
						."('$timestamp', '$remote_addr', '$remote_user', "
						."'$log_query', '$edit_description', '$old_value')";
				$edit_result = dbInsert($pdo, $edit_query);
				if(DEBUG > 2) {
					print("Team::save():team_employees.INSERT edit_result = "
						.print_r($edit_result, true)."<br/>\n");
				}
				if($edit_result) { $retVal = dbInsert($pdo, $query); }
				if(DEBUG > 2) {
					print("Team::save():team_employees.INSERT retVal = "
						.print_r($retVal, true)."<br/>\n");
				}
			}//END add_employees
			unset($add_employees);
			unset($employee_id);

			if(!empty($del_employees)) {
				$old_value = '';
				$edit_description = "Removing Team Employees, team_id=$this->id, employee_id=[";
				$query = "DELETE FROM team_employees WHERE team_id=$this->id AND ";
				if(\count($del_employees) > 1) { $query .= "("; }
				foreach($del_employees as $employee_id) {
					$edit_description .= "$employee_id, ";
					$query .= "employee_id=$employee_id OR ";
				}
				$edit_description = \preg_replace("/, $/", "]", $edit_description);
				if(\count($del_employees) > 1) { $query = \preg_replace("/ OR $/", ")", $query); }
				else { $query = \preg_replace("/ OR $/", "", $query); }

				$log_query = \str_replace(array("\"", "'", "`"),
							  "\'",
							  $query);
				while(\str_contains($log_query, "  ")) {
					$log_query = \str_replace("  ", " ", $log_query);
				}
				$edit_query = "INSERT INTO edit_log (timestamp, remote_addr, remote_user, "
						."query, description, old_value) VALUE "
						."('$timestamp', '$remote_addr', '$remote_user', "
						."'$log_query', '$edit_description', '$old_value')";
				if(DEBUG > 2) {
					print("Team::save().employees.DELETE edit_query = $edit_query<br/>\n");
				}
				$edit_result = dbInsert($pdo, $edit_query);
				if(DEBUG > 2) {
					print("Team::save().team_employees.DELETE edit_result = "
						.print_r($edit_result, true)."<br/>\n");
				}
				if($edit_result) {
					$retVal = dbInsert($pdo, $query);
					if(DEBUG > 2) {
						print("Team::save().team_employees DELETE retVal = "
							.print_r($retVal, true)."<br/>\n");
					}
				}
			}//END del_employees
			unset($del_employees);
			unset($employee_id);
		}//END if SAVED && NOT EMPTY employees

		unset($edit_description);
		unset($edit_query);
		unset($edit_result);
		unset($log_query);
		unset($old_val);
		unset($remote_addr);
		unset($remote_user);
		unset($query);
		unset($timestamp);
		return $retVal;
	}

	function getCurrentTeamSchedule($pdo) {
		if(empty($pdo)) {
			$pdo = dbConnect();
		}

		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }
		if(!empty($schedule)) { unset($schedule); }

		$query = "SELECT id FROM team_schedule WHERE team_id=$this->id "
			."AND start_date <= CURRENT DATE AND active=TRUE ORDER BY start_date DESC LIMIT 1";
		$result = dbQuery($pdo, $query)[0];
		$schedule = OncallSchedule::getOncallSchedule($pdo=$pdo, $id=$result['id']);
		unset($query);
		unset($result);
		return $schedule;
	}

	function getMonthSchedules($pdo, \DateTime $date=null, bool $active=false, bool $desc=true) {
		if(empty($pdo)) { $pdo = dbConnect(); }
		if(empty($date)) { $date = new \DateTime("now", new \DateTimeZone("UTC")); }
		$retVal = array();
		
		$start = clone $date;
		$end = clone $date;
		$start->setDate($start->format("Y"), $start->format("m"), $day=1);
		$start->setTime(0, 0, 0, 0);
		$end->modify("+1 month");
		$end->setDate($end->format("Y"), $end->format("m"), $day=1);
		$end->setTime(0, 0, 0, 0);
		if(DEBUG > 2) {
			print("Team::getMonthSchedules() start = ".print_r($start, true)."</br>\n");
			print("Team::getMonthSchedules() end = ".print_r($end, true)."</br>\n");
		}

		$query = "SELECT * FROM team_schedule WHERE team_id=$this->id AND ";
		if($active) { $query .= "active=1 AND "; }
		$query .= "start_date >= '".$start->format("Y-m-d H:i")."' AND "
			."start_date < '".$end->format("Y-m-d H:i")."' "
			."ORDER BY start_date";
		if($desc) { $query .= " DESC"; }
		if(DEBUG > 2) { print("Team::getMonthSchedules() query = $query</br>\n"); }
		$results = dbQuery($pdo, $query);
		if(DEBUG > 2) {
			print("Team::getMonthSchedules() results = "
				.print_r($results, true)."<br/>\n");
		}

		if(!empty($results)) {
			foreach($results as $key => $row) {
				$retVal[$key] = new TeamSchedule($row);
				$retVal[$key]->getTeamScheduleEmployees($pdo);
			}
		}
		unset($date);
		unset($start);
		unset($end);
		unset($query);
		unset($results);
		unset($row);
		return $retVal;
	}

	function getLastTeamSchedule($pdo, bool $active=true) {
		if(DEBUG > 2) {
			print("Team::getLastTeamSchedule($active) this = ".print_r($this, true)."<br/>\n");
		}

		if(empty($pdo)) { $pdo = dbConnect(); }
		if(DEBUG > 2) {
			print("Team::getLastTeamSchedule() pdo = "
				.print_r($pdo, true)."<br/>\n");
		}

		$query = "SELECT * FROM team_schedule WHERE team_id=$this->id ";
		if($active) { $query .= "AND active=TRUE "; }
		$query .= "ORDER BY start_date DESC LIMIT 1";
		if(DEBUG > 2) { print("Team::getLastTeamSchedule() query = $query<br/>\n"); }

		if(!empty($results)) { unset($results); }
		$results = dbQuery($pdo, $query);
		if(DEBUG > 2) {
			print("Team::getLastTeamSchedule() results = ".print_r($results, true)."<br/>\n");
		}

		if(!empty($schedule)) { unset($schedule); }
		if(!empty($results)) {
			$schedule = new TeamSchedule($results[0]);
		}
		if(empty($schedule->employees)) {
			$schedule->employees = $schedule->getTeamScheduleEmployees($pdo);
		}

		if(DEBUG > 2) {
			print("Team::getLastTeamSchedule() schedule = ".print_r($schedule, true)."<br/>\n");
		}

		return $schedule;
	}

	function getTeamSchedule($pdo, \DateTime $date) {
		if(empty($pdo)) { $pdo = dbConnect(); }
		$schedule = null;
		$query = "SELECT * FROM team_schedule WHERE team_id=$this->id AND "
			."start_date='".$date->format("Y-m-d H:i")."'";
		$result = dbQuery($pdo, $query);
		if(!empty($result)) {
			$schedule = new TeamSchedule($result[0]);
			$schedule->getTeamScheduleEmployees($pdo);
		}
		return $schedule;
	}

	function getTeamSchedules($pdo, bool $current=true, bool $active=true, bool $desc=false) {
		//TODO Handle active vs inactive schedules
		if(empty($pdo)) { $pdo = dbConnect(); }

		if(DEBUG > 2) {
			print("Team::getTeamSchedules(current=$current, active=$active, desc=$desc)<br/>\n");
		}
		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }
		if(!empty($team_schedules)) { unset($team_schedules); }
		$team_schedules = array();

		if($current) {
			$query = "SELECT id FROM team_schedule WHERE team_id=$this->id "
				."AND start_date <= UTC_TIMESTAMP ";
			if($active) { $query .= "AND active=TRUE "; }
			$query .= "ORDER BY start_date DESC LIMIT 1";
			if(DEBUG > 2) { print("Team::getTeamSchedules().current query = $query<br/>\n"); }
			$result = dbQuery($pdo, $query);
			if(DEBUG > 2) {
				print("Team::getTeamSchedules().current result = "
					.print_r($result, true)."<br/>\n");
			}
			if(!empty($result)) {
				$result = $result[0];
				$team_schedules[] = TeamSchedule::getTeamSchedule($pdo=$pdo, $id=$result['id']);
			}

			$query = "SELECT id FROM team_schedule WHERE team_id=$this->id "
				."AND start_date > UTC_TIMESTAMP ";
			if($active) { $query .= "AND active=TRUE "; }
			$query .= "ORDER BY start_date";
			if($desc) { $query .= " DESC"; }
		} else {
			$query = "SELECT id FROM team_schedule WHERE team_id=$this->id ";
			if($active) { $query .= "AND active=TRUE "; }
			$query .= "ORDER BY start_date";
			if($desc) { $query .= " DESC"; }
		}

		$result = dbQuery($pdo, $query);
		if(DEBUG > 2) {
			print("Team::getTeamSchedules() query = $query<br/>\n"
				."\tresult = ".print_r($result, true)."<br/>\n");
		}
		foreach ($result as $row) {
			$team_schedule = TeamSchedule::getTeamSchedule($pdo=$pdo, $id=$row['id']);
			if(DEBUG > 2) {
				print("Team::getTeamSchedules() team_schedule = "
					.print_r($team_schedule, true)."<br/>\n");
			}
			if(!empty($team_schedule)) { $team_schedules[] = $team_schedule; }
		}
		if(DEBUG > 2) {
			print("Team::getTeamSchedules() team_schedules = "
				.print_r($team_schedules, true)."<br/>\n");
		}
		return $team_schedules;
	}

	function getTeamScheduleTemplate($pdo, int $id=0, int $days_offset=0) {
		if(empty($pdo)) { $pdo = dbConnect(); }
		$scheduleTemplate = null;
		$query = "SELECT * FROM teamschedule_template WHERE team_id=$this->id AND "
			."days_offset=$days_offset";
		$result = dbQuery($pdo, $query);
		if(!empty($result)) {
			$scheduleTemplate = new TeamScheduleTemplate($result[0]);
			$scheduleTemplate->getTeamScheduleTemplateEmployees($pdo);
		}
		return $scheduleTemplate;
	}

	function getTeamScheduleTemplates($pdo, bool $desc=false) {
		if(DEBUG > 2) {
			print("Team::getTeamScheduleTemplates() this = "
				.print_r($this, true)."<br/>\n");
		}

		if(empty($pdo)) { $pdo = dbConnect(); }
		if(DEBUG > 2) {
			print("Team::getTeamScheduleTemplates() pdo = "
				.print_r($pdo, true)."<br/>\n");
		}

		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }
		if(!empty($teamschedule_templates)) { unset($teamschedule_templates); }
		$teamschedule_templates = array();
		
		$query = "SELECT id FROM teamschedule_template WHERE team_id=$this->id";
		$query .= " ORDER BY days_offset";
		if($desc) { $query .= " DESC"; }

		$result = dbQuery($pdo, $query);
		if(DEBUG > 2) {
			print("Team::getTeamScheduleTemplates() query = $query<br/>\n"
				."\tresult = ".print_r($result, true)."<br/>\n");
		}

		foreach ($result as $row) {
			$teamschedule_template =
				TeamScheduleTemplate::getTeamScheduleTemplate($pdo=$pdo, $id=$row['id']);
			if(DEBUG > 2) {
				print("Team::getTeamScheduleTemplates() teamschedule_template = "
					.print_r($teamschedule_template, true)."<br/>\n");
			}

			if(!empty($teamschedule_template)) {
				$teamschedule_templates[] = $teamschedule_template; }
		}

		if(DEBUG > 2) {
			print("Team::getTeamScheduleTemplates() teamschedule_templates = "
				.print_r($teamschedule_templates, true)."<br/>\n");
		}
		return $teamschedule_templates;
	}

	function getTeamEmployees($pdo, bool $active=true) {
		if(empty($pdo)) { $pdo = dbConnect(); }

		if(!empty($team_employees)) { unset($team_employees); }
		$team_employees = array();

		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }

		if($active) {
			$query = "SELECT te.employee_id FROM team_employees te "
				."INNER JOIN employee e ON te.employee_id=e.id "
				."WHERE te.team_id=$this->id AND "
				."e.active=TRUE "
				."ORDER BY e.last_name";
		} else {
			$query = "SELECT employee_id FROM team_employees WHERE team_id=$this->id";
		}
		if(DEBUG > 2) { print("Team::getTeamEmployees() query = $query<br/>\n"); }
		$result = dbQuery($pdo, $query);
		if(DEBUG > 2) { print("Team::getTeamEmployees() result = ".print_r($result, true)."<br/>\n"); }
		if(!empty($result)) {
			if(!empty($employee_ids)) { unset($employee_ids); }
			$employee_ids = array();
			foreach($result as $row) {
				$employee_ids[] = $row['employee_id'];
			}
			$team_employees = Employee::getEmployees($pdo, $employee_ids);
			unset($employee_ids);
			//$query = "SELECT * FROM employee WHERE id IN (";
			//foreach($result as $row) {
			//	$query .= $row['employee_id'].", ";
			//}
			//$query = \preg_replace("/, $/", ")", $query);
			////print("Team::getTeamEmployees() query = $query<br/>\n");
			//$result = dbQuery($pdo, $query);
			////print("Team::getTeamEmployees() result = ".print_r($result, true)."<br/>\n");
			//if(empty($result)) { return $team_employees; }
			//foreach($result as $row) {
			//	$team_employees[] = new Employee($row);
			//}
		}
		if(DEBUG > 2) {
			print("Team::getTeamEmployees() team_employees = "
				.print_r($team_employees, true)."<br/>\n");
		}

		unset($query);
		unset($result);
		unset($row);
		return $team_employees;
	}

	function getEmployeeNames($pdo, bool $active=false) {
		if(empty($pdo)) { $pdo = dbConnect(); }

		if(!empty($team_employeeids)) { unset($team_employeeids); }
		$team_employeeids = array();

		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }

		$query = "SELECT te.employee_id,e.first_name,e.nickname,e.last_name FROM "
			."team_employees te "
			."LEFT JOIN employee e ON te.employee_id=e.id "
			."WHERE team_id=$this->id ";
		if($active) { $query .= "AND e.active=TRUE "; }
		$query .= "ORDER BY te.employee_id";
		$result = dbQuery($pdo, $query);

		foreach($result as $row) {
			$id = $row['employee_id'];
			$name = !empty($row['nickname']) ? $row['nickname']." ".$row['last_name']
					: $row['first_name']." ".$row['last_name'];
			$team_employeeids[$id] = $name;
		}

		unset($name);
		unset($query);
		unset($result);
		unset($row);
		return $team_employeeids;
	}

	function getTeamUserIds($pdo) {
		if(empty($pdo)) { $pdo = dbConnect(); }

		if(!empty($team_userids)) { unset($team_userids); }
		$team_userids = array();

		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }

		$query = "SELECT te.employee_id,e.userid,e.first_name,e.nickname,e.last_name FROM "
			."team_employees te "
			."LEFT JOIN employee e ON te.employee_id=e.id "
			."WHERE team_id=$this->id "
			."ORDER BY e.userid";
		$result = dbQuery($pdo, $query);

		foreach($result as $row) {
			$temp = array("id" => $row['employee_id'],
					"userid" => $row['userid']);
			$name = !empty($row['nickname']) ? $row['nickname']." ".$row['last_name']
					: $row['first_name']." ".$row['last_name'];
			$temp['name'] = $name;
			$team_userids[] = $temp;
		}

		unset($name);
		unset($query);
		unset($result);
		unset($row);
		unset($temp);
		return $team_userids;
	}

	static function getTeam($pdo, int $id=0, string $name='') {
		if(empty($pdo)) { $pdo = dbConnect(); }

		if(DEBUG > 2) { print("Team::getTeam(id=$id,name=$name)<br/>\n"); }
		unset($query);
		unset($result);
		unset($team);
		$query = "SELECT * FROM team WHERE ";
		if(!empty($id)) {
			$query .= "id=$id";
		} else if(!empty($name)) {
			$name = \str_replace(array("'", "\"", "`"), "", $name);
			if (!empty($name)) { $query .= "name='$name'"; }
		} else {
			throw new \Exception($message="ERROR! Team::getTeam(id=$id,name=$name) "
				."Must provide valid ID or Name",
				$code=200);
		}
		if(DEBUG > 2) { print("Team::getTeam() query = $query<br/>\n"); }

		$result = dbQuery($pdo, $query);
		if(DEBUG > 2) { print("Team::getTeam() result = ".print_r($result, true)."<br/>\n"); }

		if(empty($result)) {
			throw new \Exception($message="ERROR! Team::getTeam() No results found! "
				."QUERY = $query",
				$code=201);
		} else if(\count($result) > 1) {
			throw new \Exception($message="ERROR! Team::getTeam() Multiple results found! "
				."QUERY = $query",
				$code=202);
		} else {
			$team = new Team($data=$result[0]);
		}

		$id = $team->id;
		if(DEBUG > 2) { print("Team::getTeam() id = $id; team = ".print_r($team, true)."<br/>\n"); }

		//Associate Employees
		if(!empty($employees)) { unset($employees); }
		$employees = array();
		$query = "SELECT employee_id FROM team_employees WHERE team_id=$id ORDER BY employee_id";
		$result = dbQuery($pdo, $query);
		if(DEBUG > 2) { print("Team::getTeam().employees result = ".print_r($result, true)."<br/>\n"); }
		if(!empty($result)) {
			foreach($result as $row) { $employees[] = $row['employee_id']; }
		}
		$team->employees = $employees;
		if(DEBUG > 2) {
			print("Team::getTeam(id=$id,name=$name) team = ".print_r($team, true)."<br/>\n");
		}

		unset($query);
		unset($result);
		return $team;
	}

	// Returns a simple array of team.id,team.name ordered by name
	// Used by common.php::teamSelect() & a number of other places
	static function getTeamNames($pdo, bool $active=true) {
		if(DEBUG > 2) {
			$active_str = ($active) ? "TRUE" : "FALSE";
			print("Team::getTeamNames(active=\"$active_str\")<br/>\n");
			unset($active_str);
		}

		if(empty($pdo)) {
			$pdo = dbConnect();
		}
		unset($query);
		unset($results);
		unset($teams);

		$query = 'SELECT id,name FROM team';
		if($active) { $query .= ' WHERE active=TRUE'; }
		$query .= ' ORDER BY name';
		if(DEBUG > 2) { print("Team::getTeamNames() query = $query<br/>\n"); }

		$results = dbQuery($pdo, $query);
		if(DEBUG > 2) { print("Team::getTeamNames() results = ".print_r($results, true)."<br/>\n"); }

		$teams = array();
		foreach ($results as $row) {
			$teams[] = array("id" => $row['id'],
					 "name" => $row['name']);
		}
		if(DEBUG > 2) { print("Team::getTeamNames() result = ".print_r($teams, true)."<br/>\n"); }
		return $teams;
	}
}
