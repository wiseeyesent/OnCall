<?php
namespace WiseEyesEnt\OnCall2;

require_once('./common.php');
require_once('./dbconn.php');
require_once('./team.php');

class TeamSchedule {
	public int $id;
	public int $team_id;
	//public Team $team;
	public \DateTime $start_date;
	public array $employees;
	public bool $active;

	function __construct($data = null) {
		//print("TeamSchedule::__construct() data: ".print_r($data, true)."<br/>\n");
		if(!empty($data)) {
			//NOTE: No ID means this is probably a new schedule
			if(!empty($data['id'])) { $this->id = $data['id']; }
				else { $this->id = 0; }
			//TODO update schedule->team handling
			if(!empty($data['team_id'])) { $this->team_id = $data['team_id']; }
			else { $this->team_id = 0; }
			//Set the rest to DB defaults
			if(!empty($data['start_date'])) {
				$this->start_date = new \DateTime($data['start_date'],
								  new \DateTimeZone('UTC')); 
			} else {
				$this->start_date = new \DateTime("0000-00-00 00:00:00",
								  new \DateTimeZone('UTC'));
			}
			if(!empty($data['employees'])) {
				$this->employees = (array) $data['employees'];
			} else {
				//$this->getTeamScheduleEmployees($pdo);
				$this->employees = array();
			}
			$this->active = (isset($data['active'])) ? (bool) $data['active'] : true;
		} else {
			//Create a new Team_Schedule using DB defaults
			//NOTE: team_id, start_date, & active MUST be set prior to $this->save()
			$this->id = 0;
			$this->team_id = 0;
			$this->start_date = new \DateTime("0000-00-00 00:00:00",
							  new \DateTimeZone("UTC"));
			$this->employees = array();
			$this->active = true;
		}  //END if(data) else

		//print("TeamSchedule::__construct() this = ".print_r($this, true)."<br/>\n");
		return $this;
	}
	
	function isOld($pdo) {
		if(empty($pdo)) { $pdo = dbConnect(); }
		//print("TeamSchedule::isOld() this.start_date = ".print_r($this->start_date, true)."<br/>\n");
		$this_date = $this->start_date->format("Y-m-d H:i:s");
		//print("TeamSchedule::isOld() this_date = ".print_r($this_date, true)."<br/>\n");

		$query = "SELECT start_date FROM team_schedule WHERE active=TRUE AND team_id=$this->team_id AND "
			."start_date <= UTC_TIMESTAMP ORDER BY start_date DESC LIMIT 1";
		//print("TeamSchedule::isOld() query = $query<br/>\n");
		$result = dbQuery($pdo, $query);
		//print("TeamSchedule::isOld() result = ".print_r($result, true)."<br/>\n");
		if(!empty($result)) {
			$sched_date = $result[0]['start_date'];
			//print("TeamSchedule::isOld() sched_date = ".print_r($sched_date, true)."<br/>\n");
			if($this_date < $sched_date) {
				return true;
			}
		}
		return false; 
	}

	function save($pdo) {
		if(empty($pdo)) { $pdo = dbConnect(); }
		
		if(!empty($query)) { unset($query); }
		if(!empty($result)) { unset($result); }

		if(!empty($active)) { unset($active); }
		$active = $this->active ? 1 : 0;
		if(!empty($start_date)) { unset($start_date); }
		$start_date = $this->start_date->format('Y-m-d H:i:s');
		//Probably updating an existing schedule
		if(!empty($this->id)) {
			$query = "UPDATE team_schedule SET "
				."team_id=$this->team_id, "
				."start_date='$start_date', "
				."active=$active "
				."WHERE id=$this->id";
		} else {
			//Probably creating new schedule
			$query = "INSERT INTO team_schedule (team_id, start_date, active) VALUES "
				."($this->team_id, '$start_date', $active) "
				."ON DUPLICATE KEY UPDATE "
				."team_id=VALUES(team_id), start_date=VALUES(start_date), active=VALUES(active)";
		}
		//print("TeamSchedule::save() query = $query<br/>\n");

		//edit_log
		unset($description);
		unset($edit_query);
		unset($edit_result);
		unset($log_query);
		unset($old_value);
		unset($timestamp);
		$timestamp = (new \DateTimeImmutable("now", new \DateTimeZone("UTC")))->format('Y-m-d H:i:s');
		$remote_addr = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
		$remote_user = !empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : "";

		$edit_query = "SELECT * FROM team_schedule WHERE id=$this->id";
		//print("TeamSchedule::save() edit_query = $edit_query<br/>\n");
		$edit_result = dbQuery($pdo, $edit_query);
		//print("TeamSchedule::save() edit_result = ".print_r($edit_result, true)."<br/>\n");
		if(!empty($edit_result)) {
			$old_value = new TeamSchedule($edit_result[0]);
			$old_value = str_replace(array("\r\n", "\r", "\n"),
						 "",
						 print_r($old_value, true));
			while(str_contains($old_value, "  ")) {
				$old_value = str_replace("  ", " ", $old_value);
			}

			$description = "Update Existing Team Schedule, id=$this->id team_id=$this->team_id";
		} else {
			$old_value = '';
			$description = "Create New Team Schedule, team_id=$this->team_id";
		}
		$log_query = str_replace(array("\"", "'", "`"), "\'", $query);

		//TODO Fix $_SERVER['REMOTE_USER']
		$edit_query = "INSERT INTO edit_log (timestamp, remote_addr, remote_user, "
				."query, description, old_value) "
				."VALUES "
				."('$timestamp', '$remote_addr', '$remote_user', "
				."'$log_query', '$description', '$old_value')";
		//print("TeamSchedule::save() edit_query = $edit_query<br/>\n");
		$edit_result = dbInsert($pdo, $edit_query);
		//print("TeamSchedule::save() edit_result = ".print_r($edit_result, true)."<br/>\n");

		$result = dbInsert($pdo, $query);
		//print("TeamSchedule::save() result = ".print_r($result, true)."<br/>\n");
		if(!$result) {
			throw new \Exception("ERROR! TeamSchedule::save() "
						."team_schedule INSERT or UPDATE failed!");
		}
		if(empty($this->id)) { //Created a new schedule, so let's refresh it...
			$start_date = $this->start_date->format("Y-m-d");
			//$start_time = Team::getTeam($pdo, $this->team_id)->start_time;
			$start_time = $this->start_date->format("H:i:s");
			if(empty($start_time)) { 
				$team = Team::getTeam($pdo, $this->team_id);
				$start_time = $team->start_time;
			}
			$start_date .= " $start_time";
			unset($start_time);
			unset($team);

			$query = "SELECT * FROM team_schedule WHERE team_id=$this->team_id AND "
				."start_date='$start_date'";
			$result = dbQuery($pdo, $query);
			//print("TeamSchedule::save() query = $query<br/>\n");
			//print("TeamSchedule::save() result = ".print_r($result, true)."<br/>\n");
			if(empty($result)) {
				throw new \Exception("TeamSchedule::save().refresh No schedule found!");
			} else if(\count($result) > 1) {
				throw new \Exception("TeamSchedule::save().refresh Multiple schedules found!");
			} else { 
				$result = $result[0];
				$this->id = $result['id'];
				$this->team_id = $result['team_id'];
				$this->start_date = \DateTime::createFromFormat("Y-m-d H:i:s",
										$result['start_date']);
				$this->active = (bool) $result['active'];
			}
			//print("TeamSchedule::save().refresh this = ".print_r($this, true)."<br/>\n");
		}

		//print("TeamSchedule::save() employees = ".print_r($this->employees, true)."<br/>\n");
		$count = \count($this->employees);
		foreach($this->employees as $key => $schedule_employee) {
			//print("TeamSchedule::save() key = $key schedule_employee = "
			//	.print_r($schedule_employee, true)."<br/>\n");
			$employee_id = $schedule_employee['employee'];
			//print("TeamSchedule::save().employees employee_id = $employee_id<br/>\n");
			$call_order = $schedule_employee['call_order'];
			//print("TeamSchedule::save().employees call_order = $call_order<br/>\n");
			unset($query);
			if($call_order <= 0) {
				//Decrement the count to indicate a reduction
				$count--;
				//If we have no employees left... 
				if($count == 0) {
					//Make sure to set call_order back to 1
					$query = "UPDATE teamschedule_employees SET "
						."call_order=1 WHERE "
						."teamschedule_id=$this->id AND employee_id=$employee_id";
					$result = dbQuery($pdo, $query);
					//print("TeamSchedule::save().employees Skipping last employee, "
					//	."$employee_id<br/>\n");
					//...skip further processing to preserve the last employee
					continue;
				}
				$query = "DELETE FROM teamschedule_employees WHERE "
					."teamschedule_id=$this->id AND employee_id=$employee_id";
			} else {
				$query = "INSERT INTO teamschedule_employees "
					."(teamschedule_id, employee_id, call_order) "
					."VALUES "
					."($this->id, $employee_id, $call_order) "
					."ON DUPLICATE KEY UPDATE "
					."teamschedule_id=VALUES(teamschedule_id), "
					."employee_id=VALUES(employee_id), "
					."call_order=VALUES(call_order)";
			}
			//print("teamSchedule::save().employees query = $query<br/>\n"); 

			$log_query = str_replace("'", "\'", $query);

			$edit_query = "SELECT * FROM teamschedule_employees WHERE "
					."teamschedule_id=$this->id AND "
					."employee_id=$employee_id";
			//print("TeamSchedule::save().employees edit_query = $edit_query<br/>\n");
			$edit_result = dbQuery($pdo, $edit_query);
			//print("TeamSchedule::save().employees edit_result = "
			//	.print_r($edit_result, true)."<br/>\n");
			if(!empty($edit_result)) {
				//print("TeamSchedule::save().employees edit_result = "
				//	.print_r($edit_result, true)."<br/>\n");
				$old_value = array("employee" => $edit_result[0]['employee_id'],
						   "call_order" => $edit_result[0]['call_order']);
				$old_value = \str_replace(array("\r\n", "\n", "\r"),
							"",
							print_r($old_value, true));
				while(\str_contains($old_value, "  ")) {
					$old_value = \str_replace("  ", " ", $old_value);
				}
				if($call_order == 0) {
					$description = "Remove Team Schedule Employee, "
							."teamschedule_id=$this->id "
							."employee_id=$employee_id";
				} else {
					$description = "Update Existing Team Schedule Employee, "
							."teamschedule_id=$this->id "
							."employee_id=$employee_id "
							."call_order=$call_order";
				}
			} else {
				//print("TeamSchedule::save().employees edit_result EMPTY, "
				//	."call_order = $call_order<br/>\n");
				$old_value = "";
				if($call_order == 0) {
					throw new \Exception("ERROR! TeamSchedule::save() "
							    ."No existing teamschedule_employee & "
							    ."call_order=0");

					$description = "Remove Team Schedule Employee, "
							."teamschedule_id=$this->id "
							."employee_id=$employee_id";
				} else {
					$old_value = "";
					$description = "Add New Team Schedule Employee, "
							."teamschedule_id=$this->id "
							."employee_id=$employee_id "
							."call_order=$call_order";
				}
			}
			//print("TeamSchedule::save().employees old_value = $old_value<br/>\n");
			//print("TeamSchedule::save().employees description = $description<br/>\n");
			$edit_query = "INSERT INTO edit_log (timestamp, remote_addr, remote_user, "
					."query, description, old_value) "
					." VALUES "
					."('$timestamp', '$remote_addr', '$remote_user', "
					."'$log_query', '$description', '$old_value')";
			//print("TeamSchedule::save().employees edit_query = $edit_query<br/>\n");
			$edit_result = dbInsert($pdo, $edit_query);
			//print("TeamSchedule::save().employees edit_result = "
			//	.print_r($edit_result, true)."<br/>\n");

			$result = dbInsert($pdo, $query);
			//print("TeamSchedule::save().employees result = ".print_r($result, true)."<br/>\n");
		}
		if($result) { $result = TeamSchedule::getTeamSchedule($pdo, $this->id); }
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
	}

	//Get current TeamSchedule by Schedule ID OR the last relevant one for a Team
	static function getTeamSchedule($pdo, int $id=0, int $teamid=0, string $name="", bool $active=true) {
		//print("TeamSchedule::getTeamSchedule() id = $id ; teamid = $teamid ; name = $name<br/>\n");
		if(empty($pdo)) { $pdo = dbConnect(); }
		unset($query);
		unset($result);
		unset($team_schedule);
		if(!empty($id)) {
			$query = "SELECT * FROM team_schedule WHERE id=$id";
		} else if(!empty($teamid)) {
			$query = "SELECT * FROM team_schedule WHERE team_id=$teamid "
				."AND start_date<=UTC_TIMESTAMP ";
			if($active) { $query .= "AND active=TRUE "; }
			$query .= "ORDER BY start_date DESC LIMIT 1";
		} else if(!empty($name)) {
			//print("TeamSchedule::getTeamSchedule() name = $name<br/>\n");
			$str_array = \explode(":", $name);
			//print("TeamSchedule::getTeamSchedule() str_array = ".print_r($str_array, true)."<br/>\n");
			$teamid = (int) $str_array[0];
			$start_date = (string) $str_array[1];
			//print("TeamSchedule::getTeamSchedule() teamid = $teamid start_date = $start_date<br/>\n");
			$query = "SELECT * FROM team_schedule WHERE team_id=$teamid AND "
				."start_date<='$start_date 23:59:59' "
				."ORDER BY start_date DESC LIMIT 1";
		} else {
			throw new \Exception("ERROR! TeamSchedule::getTeamSchedule() "
						."Must provide ID, TEAM ID, OR NAME (teamid:yyyy-mm-dd)");
		}
		//print("TeamSchedule::getTeamSchedule() query = $query<br/>\n");
		$result = dbQuery($pdo, $query);
		//print("TeamSchedule::getTeamSchedule() result = ".print_r($result, true)."<br/>\n");
		if(empty($result)) {
			throw new \Exception("ERROR! TeamSchedule::getTeamSchedule(id=$id, teamid=$teamid, name=$name) "
						."NO SCHEDULE FOUND");
		}
		$team_schedule = new TeamSchedule($data=$result[0]);
		//print("TeamSchedule::getTeamSchedule() team_schedule = ".print_r($team_schedule, true)
		//	."<br/>\n");

		$employees = $team_schedule->getTeamScheduleEmployees($pdo);
		//print("TeamSchedule::getTeamSchedule() team_schedule.employees = "
		//	.print_r($employees, true)."<br/>\n");
		if(empty($team_schedule->employees)) {
			throw new \Exception("ERROR! TeamSchedule::getTeamSchedule() MISSING EMPLOYEES!");
		}
		return $team_schedule; 
	}

	function getTeamScheduleEmployees($pdo) {
		if(empty($pdo)) { $pdo = dbConnect(); }

		unset($query);
		unset($result);
		unset($employees);
		$employees = array();
		$query = "SELECT * FROM teamschedule_employees WHERE teamschedule_id=$this->id "
			."ORDER BY call_order";
		//print("TeamSchedule::getTeamScheduleEmployees() query = $query<br/>\n");
		$result = dbQuery($pdo, $query);
		//print("TeamSchedule::getTeamScheduleEmployees() result = ".print_r($result, true)."<br/>\n");
		foreach ($result as $row) {
			$employees[] = array('employee' => $row['employee_id'],
						'call_order' => $row['call_order']);
		}
		unset($query);
		unset($result);
		//print("TeamSchedule::getTeamScheduleEmployees() employees = ".print_r($employees, true)
		//	."<br/>\n");
		$this->employees = $employees;
		return $this->employees;
	}

	function getActiveEmployees() {
		if(!empty($retEmployees)) { unset($retEmployees); }
		$retEmployees = array();

		foreach($this->employees as $employee) {
			if($employee['call_order'] > 0) { $retEmployees[] = $employee; }
		}
		return $retEmployees;
	}
}
