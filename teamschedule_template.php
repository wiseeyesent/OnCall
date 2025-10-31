<?php
namespace WiseEyesEnt\OnCall2;

require_once('./common.php');
require_once('./dbconn.php');
require_once('./team.php');

//$UTC = new \DateTimeZone("UTC");

class TeamScheduleTemplate {
	public int $id;
	public int $team_id;
	public int $days_offset;
	public array $employees;
	public \DateTime $start_time;
	public bool $active;

	function __construct($data = null) {
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::__construct() data: ".print_r($data, true)."<br/>\n");
		}
		if(!empty($data)) {
			//NOTE: No ID means this is probably a new schedule
			if(!empty($data['id'])) { $this->id = $data['id']; }
				else { $this->id = 0; }
			//TODO update schedule->team handling
			if(!empty($data['team_id'])) { $this->team_id = $data['team_id']; }
			else { $this->team_id = 0; }
			//Set the rest to DB defaults
			if(!empty($data['days_offset'])) {
				$this->days_offset = (int) $data['days_offset'];
			} else {
				$this->days_offset = 7;
			}
			if(!empty($data['employees'])) {
				$this->employees = (array) $data['employees'];
			} else {
				$this->employees = array();
			}
			if(!empty($data['start_time'])) {
				$this->start_time = new \DateTime("1969-12-31 ".$data['start_time'],
							new \DateTimeZone("UTC"));
			} else {
				$this->start_time = new \DateTime("1969-12-31 13:00:00",
							new \DateTimeZone("UTC"));
			}
			$this->active = (isset($data['active'])) ? (bool) $data['active'] : true;
		} else {
			//Create a new TeamScheduleTemplate using DB defaults
			//NOTE: team_id, days_offset, start_time, & active MUST be set prior to $this->save()
			$this->id = 0;
			$this->team_id = 0;
			$this->days_offset = 7;
			$this->employees = array();
			$this->start_time = new \DateTime("1969-12-31 13:00:00", new \DateTimeZone("UTC"));
			$this->active = true;
		}  //END if(data) else

		if(DEBUG > 2) {
			print("TeamScheduleTemplate::__construct() this = ".print_r($this, true)."<br/>\n");
		}
		return $this;
	}

	// TODO What format do we want here?
	// Current:
	// TeamScheduleTemplate[id:X,days_offset:N,start_time:HH:mm:ss,active:[0|1],employees[[employee_id:X,call_order:N][...]]]
	function __toString() {
		$retString = "TeamScheduleTemplate[id:$this->id,"
				."days_offset:$this->days_offset,"
				."start_time:".\date_format($this->start_time, "H:i:s").","
				."active:$this->active,"
				."employees:[";
		foreach($this->employees as $employee) {
			$retString = $retString."[employee_id:".$employee['employee_id'].","
				."call_order:".$employee['call_order']
				."]";
		}
		$retString = $retString."]]";
		return $retString;
	}
	
	function save($pdo) {
		if(DEBUG > 2) { print("TeamScheduleTemplate::save() this = ".print_r($this, true)."<br/>\n"); }
		if(empty($pdo)) { $pdo = dbConnect(); }
		if(DEBUG > 2) { print("TeamScheduleTemplate::save() pdo = ".print_r($pdo, true)."<br/>\n"); }
		
		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }

		if(!empty($active)) { unset($active); }
		$active = $this->active ? 1 : 0;
		if(!empty($team_id)) { unset($team_id); }
		$team_id = $this->team_id;
		if(!empty($days_offset)) { unset($days_offset); }
		$days_offset = $this->days_offset;
		if(!empty($start_time)) { unset($start_time); }
		$start_time = $this->start_time;
		//Probably updating an existing schedule
		if(!empty($this->id)) {
			$query = "UPDATE teamschedule_template SET "
				."team_id=$team_id, "
				."days_offset=$days_offset, "
				."start_time='".\date_format($start_time, "H:i:s")."', "
				."active=$active "
				."WHERE id=$this->id";
		} else {
			//Probably creating new schedule
			$query = "INSERT INTO teamschedule_template (team_id, days_offset, start_time, active) "
				."VALUES "
				."($this->team_id, $days_offset, '".\date_format($start_time, "H:i:s")."', $active) "
				."ON DUPLICATE KEY UPDATE "
				."team_id=VALUES(team_id), days_offset=VALUES(days_offset), "
				."start_time=VALUES(start_time), active=VALUES(active)";
		}
		if(DEBUG > 2) { print("TeamScheduleTemplate::save() query = $query<br/>\n"); }

		//edit_log
		unset($description);
		unset($edit_query);
		unset($edit_result);
		unset($log_query);
		unset($old_value);
		unset($timestamp);
		$timestamp = (new \DateTimeImmutable("now", new \DateTimeZone("UTC")))->format('Y-m-d H:i:s');
		$remote_addr = !empty($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : "";
		$remote_user = !empty($_SERVER['REMOTE_USER']) ? (string) $_SERVER['REMOTE_USER'] : "";

		$edit_query = "SELECT * FROM teamschedule_template WHERE id=$this->id";
		if(DEBUG > 2) { print("TeamScheduleTemplate::save() edit_query = $edit_query<br/>\n"); }
		$edit_result = dbQuery($pdo, $edit_query);
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::save() edit_result = ".print_r($edit_result, true)."<br/>\n");
		}
		if(!empty($edit_result)) {
			$old_value = new TeamScheduleTemplate($edit_result[0]);
			$old_value = str_replace(array("\r\n", "\r", "\n"),
						 "",
						 print_r($old_value, true));
			while(str_contains($old_value, "  ")) {
				$old_value = str_replace("  ", " ", $old_value);
			}

			$description = "Update Existing Team Schedule Template, id=$this->id team_id=$this->team_id";
		} else {
			$old_value = '';
			$description = "Create New Team Schedule Template, team_id=$this->team_id";
		}
		$log_query = str_replace(array("\"", "'", "`"), "\'", $query);

		//TODO Fix $_SERVER['REMOTE_USER']
		$edit_query = "INSERT INTO edit_log (timestamp, remote_addr, remote_user, "
				."query, description, old_value) "
				."VALUES "
				."('$timestamp', '$remote_addr', '$remote_user', "
				."'$log_query', '$description', '$old_value')";
		if(DEBUG > 2) { print("TeamScheduleTemplate::save() edit_query = $edit_query<br/>\n"); }
		$edit_result = dbInsert($pdo, $edit_query);
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::save() edit_result = ".print_r($edit_result, true)."<br/>\n");
		}

		$result = dbInsert($pdo, $query);
		if(DEBUG > 2) { print("TeamScheduleTemplate::save() result = ".print_r($result, true)."<br/>\n"); }
		if(!$result) {
			throw new \Exception("ERROR! TeamScheduleTemplate::save() "
						."teamschedule_template INSERT or UPDATE failed!");
		}
		if(empty($this->id)) { //Created a new schedule, so let's refresh it...
			$team = Team::getTeam($pdo, $this->team_id);
			if(empty($this->start_time)) { 
				$this->start_time = new \DateTime("1969-12-31 ".$team->start_time,
							new \DateTimeZone("UTC"));
			}
			if(empty($this->days_offset)) {
				$query = "SELECT * FROM teamschedule_template WHERE team_id=$this->team_id "
					."ORDER BY days_offset DESC LIMIT 1";
				$result = dbQuery($pdo, $query);
				if(empty($result)) { $this->days_offset = $team->ndays; }
				else {
					$result = $result[0];
					$this->days_offset = (int) $result['days_offset'] + $team->ndays;
				}
			}
			unset($query);
			unset($result);
			unset($team);

			$query = "SELECT * FROM teamschedule_template WHERE team_id=$this->team_id AND "
				."days_offset=$this->days_offset";
			$result = dbQuery($pdo, $query);
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save() query = $query<br/>\n");
				print("TeamScheduleTemplate::save() result = ".
					print_r($result, true)."<br/>\n");
			}
			if(empty($result)) {
				throw new \Exception("TeamScheduleTemplate::save().refresh No template found!");
			} else if(\count($result) > 1) {
				throw new \Exception("TeamScheduleTemplate::save().refresh "
							."Multiple templates found!");
			} else { 
				$result = $result[0];
				$this->id = $result['id'];
				$this->team_id = (int) $result['team_id'];
				$this->days_offset = (int) $result['days_offset'];
				$this->start_time = new \DateTime("1969-12-31 ".$result['start_time'],
							new \DateTimeZone("UTC"));
				$this->active = (bool) $result['active'];
			}
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save().refresh this = "
					.print_r($this, true)."<br/>\n");
			}
		}//END if(empty(template) id)

		if(DEBUG > 2) {
			print("TeamScheduleTemplate::save() employees = "
				.print_r($this->employees, true)."<br/>\n");
		}
		$count = \count($this->employees);
		foreach($this->employees as $key => $schedule_employee) {
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save() key = $key schedule_employee = "
					.print_r($schedule_employee, true)."<br/>\n");
			}
			$employee_id = $schedule_employee['employee'];
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save().employees employee_id = $employee_id<br/>\n");
			}
			$call_order = $schedule_employee['call_order'];
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save().employees call_order = $call_order<br/>\n");
			}
			unset($query);
			if($call_order <= 0) {
				//Decrement the count to indicate a reduction
				$count--;
				//If we have no employees left... 
				if($count <= 0) {
					//Make sure to set call_order back to 1
					$query = "UPDATE teamscheduletemplate_employees SET "
						."call_order=1 WHERE "
						."teamscheduletemplate_id=$this->id AND employee_id=$employee_id";
					$result = dbQuery($pdo, $query);
					if(DEBUG > 2) {
						print("TeamScheduleTemplate::save().employees: Skipping last "
							."employee ($employee_id)<br/>\n");
					}
					//...skip further processing to preserve the last employee
					continue;
				}
				$query = "DELETE FROM teamscheduletemplate_employees WHERE "
					."teamscheduletemplate_id=$this->id AND employee_id=$employee_id";
			} else {
				$query = "INSERT INTO teamscheduletemplate_employees "
					."(teamscheduletemplate_id, employee_id, call_order) "
					."VALUES "
					."($this->id, $employee_id, $call_order) "
					."ON DUPLICATE KEY UPDATE "
					."teamscheduletemplate_id=VALUES(teamscheduletemplate_id), "
					."employee_id=VALUES(employee_id), "
					."call_order=VALUES(call_order)";
			}
			if(DEBUG > 2) { print("TeamScheduleTemplate::save().employees query = $query<br/>\n"); }

			$log_query = str_replace("'", "\'", $query);

			$edit_query = "SELECT * FROM teamscheduletemplate_employees WHERE "
					."teamscheduletemplate_id=$this->id AND "
					."employee_id=$employee_id";
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save().employees edit_query = $edit_query<br/>\n");
			}
			$edit_result = dbQuery($pdo, $edit_query);
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save().employees edit_result = "
					.print_r($edit_result, true)."<br/>\n");
			}
			if(!empty($edit_result)) {
				if(DEBUG > 2) {
					print("TeamScheduleTemplate::save().employees edit_result = "
						.print_r($edit_result, true)."<br/>\n");
				}
				$old_value = array("employee" => $edit_result[0]['employee_id'],
						   "call_order" => $edit_result[0]['call_order']);
				$old_value = \str_replace(array("\r\n", "\n", "\r"),
							"",
							print_r($old_value, true));
				while(\str_contains($old_value, "  ")) {
					$old_value = \str_replace("  ", " ", $old_value);
				}
				if($call_order == 0) {
					$description = "Remove Team Schedule Template Employee, "
							."teamscheduletemplate_id=$this->id "
							."employee_id=$employee_id";
				} else {
					$description = "Update Existing Team Schedule Template Employee, "
							."teamscheduletemplate_id=$this->id "
							."employee_id=$employee_id "
							."call_order=$call_order";
				}
			} else {
				if(DEBUG > 2) {
					print("TeamScheduleTemplate::save().employees edit_result EMPTY, "
						."call_order = $call_order<br/>\n");
				}
				$old_value = "";
				if($call_order == 0) {
					throw new \Exception("ERROR! TeamScheduleTemplate::save() "
							    ."No existing teamscheduletemplate_employee & "
							    ."call_order=0");

					$description = "Remove Team Schedule Employee, "
							."teamschedule_id=$this->id "
							."employee_id=$employee_id";
				} else {
					$old_value = "";
					$description = "Add New Team Schedule Template Employee, "
							."teamscheduletemplate_id=$this->id "
							."employee_id=$employee_id "
							."call_order=$call_order";
				}
			}
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save().employees "
					."old_value = $old_value<br/>\n");
				print("TeamScheduleTemplate::save().employees "
					."description = $description<br/>\n");
			}
			$edit_query = "INSERT INTO edit_log (timestamp, remote_addr, remote_user, "
					."query, description, old_value) "
					." VALUES "
					."('$timestamp', '$remote_addr', '$remote_user', "
					."'$log_query', '$description', '$old_value')";
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save().employees edit_query = $edit_query<br/>\n");
			}
			$edit_result = dbInsert($pdo, $edit_query);
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save().employees edit_result = "
					.print_r($edit_result, true)."<br/>\n");
			}

			$result = dbInsert($pdo, $query);
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::save().employees result = "
					.print_r($result, true)."<br/>\n");
			}
		}
		if($result) { $result = TeamScheduleTemplate::getTeamScheduleTemplate($pdo, $this->id); }
		unset($description);
		unset($edit_query);
		unset($edit_result);
		unset($log_query);
		unset($old_value);
		unset($remote_addr);
		unset($remote_user);
		unset($query);
		unset($timestamp);
		return $result;
	} //END TeamScheduleTemplate.save()

	function getTemplateName() {
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTemplateName() this = ".print_r($this, true)."<br/>\n");
		}
		return "$this->team_id:$this->days_offset";
	}

	//Get current TeamScheduleTemplate by Database ID, Name (TeamID:Days_Offset),
	// OR the last relevant one for a Team
	static function getTeamScheduleTemplate($pdo, int $id=0, int $teamid=0, string $name="", bool $active=true) {
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTeamScheduleTemplate() "
				."id = $id ; teamid = $teamid ; name = $name<br/>\n");
		}
		if(empty($pdo)) { $pdo = dbConnect(); }
		unset($query);
		unset($result);
		unset($teamschedule_template);
		if(!empty($id)) {
			$query = "SELECT * FROM teamschedule_template WHERE id<=$id ORDER BY id DESC LIMIT 1";
		} else if(!empty($teamid)) {
			$query = "SELECT * FROM teamschedule_template WHERE team_id=$teamid ";
			if($active) { $query .= "AND active=TRUE "; }
			$query .= "ORDER BY days_offset DESC LIMIT 1";
		} else if(!empty($name)) {
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::getTeamScheduleTemplate() name = $name<br/>\n");
			}
			$str_array = \explode(":", $name);
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::getTeamScheduleTemplate() str_array = "
					.print_r($str_array, true)."<br/>\n");
			}
			$teamid = (int) $str_array[0];
			$days_offset = (int) $str_array[1];
			if(DEBUG > 2) {
				print("TeamScheduleTemplate::getTeamSchedule() "
					."teamid = $teamid; days_offset = $days_offset<br/>\n");
			}
			$query = "SELECT * FROM teamschedule_template WHERE team_id=$teamid AND "
				."days_offset<=$days_offset ORDER BY days_offset DESC LIMIT 1";
		} else {
			throw new \Exception("ERROR! TeamScheduleTemplate::getTeamScheduleTemplate() "
				."Must provide ID, TEAM ID, OR NAME (teamid:days_offset)",
				301);
		}
		if(DEBUG > 2) { print("TeamScheduleTemplate::getTeamScheduleTemplate() query = $query<br/>\n"); }
		$result = dbQuery($pdo, $query);
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTeamScheduleTemplate() result = "
				.print_r($result, true)."<br/>\n");
		}
		if(empty($result)) {
			throw new \Exception("ERROR! TeamScheduleTemplate::"
						."getTeamScheduleTemplate(id=$id, teamid=$teamid, name=$name) "
						."NO TEMPLATE FOUND", 301);
		}
		$teamschedule_template = new TeamScheduleTemplate($data=$result[0]);
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTeamScheduleTemplate() teamschedule_template = "
				.print_r($teamschedule_template, true)."<br/>\n");
			}

		$teamschedule_template->getTeamScheduleTemplateEmployees($pdo);
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTeamScheduleTemplate() "
				."teamschedule_template.employees = "
				.print_r($teamschedule_template->employees, true)."<br/>\n");
		}
		if(empty($teamschedule_template->employees)) {
			throw new \Exception("ERROR! TeamScheduleTemplate::getTeamSchedule() "
				."MISSING EMPLOYEES!", 311);
		}
		unset($id);
		unset($name);
		unset($query);
		unset($result);
		unset($teamid);
		return $teamschedule_template; 
	}

	//Get Employees assigned to current TeamScheduleTemplate
	//NOTE: Both updates this->employees & returns the resulting employees array
	function getTeamScheduleTemplateEmployees($pdo) {
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTeamScheduleTemplateEmployees() "
				."this = ".print_r($this, true)."<br/>\n");
		}
		if(empty($pdo)) { $pdo = dbConnect(); }
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTeamScheduleTemplateEmployees() "
				."pdo = ".print_r($pdo, true)."<br/>\n");
		}

		unset($query);
		unset($result);
		unset($employees);
		$employees = array();
		$query = "SELECT * FROM teamscheduletemplate_employees WHERE teamscheduletemplate_id=$this->id "
			."ORDER BY call_order";
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTeamScheduleTemplateEmployees() query = $query<br/>\n");
		}
		$result = dbQuery($pdo, $query);
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTeamScheduleTemplateEmployees() result = "
				.print_r($result, true)."<br/>\n");
		}
		foreach ($result as $row) {
			$employees[] = array('employee' => $row['employee_id'],
						'call_order' => $row['call_order']);
		}
		unset($query);
		unset($result);
		unset($row);
		if(DEBUG > 2) {
			print("TeamScheduleTemplate::getTeamScheduleTemplateEmployees() employees = "
				.print_r($employees, true)."<br/>\n");
		}
		$this->employees = $employees;
		return $this->employees;
	}

	//Returns array of only employees with call_order > 0 on this TeamScheduleTemplate
	function getActiveEmployees() {
		if(!empty($retEmployees)) { unset($retEmployees); }
		$retEmployees = array();

		foreach($this->employees as $employee) {
			if($employee['call_order'] > 0) { $retEmployees[] = $employee; }
		}
		return $retEmployees;
	}
}
