<?php
namespace WiseEyesEnt\OnCall2;

require_once('OnCall2.conf.php');

function dbConnect(string $host=null, string $database=null, string $user=null, string $pass=null) {
	if(empty($host)) { $host = DB_HOST; }
	if(empty($database)) { $database = DB_NAME; }
	if(empty($user)) { $user = DB_USER; }
	if(empty($pass)) { $pass = DB_PASS; }

	try {
		$pdo = new \PDO("mysql:host=$host;dbname=$database", "$user", "$pass");
		return $pdo;
	} catch(\Exception $e) {
		die("ERROR! Connect failed: ".$e->getMessage());
	}
}

function dbQuery($pdo, $query) {
	try {
		$statement = $pdo->query($query);
		#var_dump($statement);
		$row = $statement->fetchAll();
		#var_dump($row);
		unset($statement);
		return $row;
	} catch(\Exception $e) {
		die("ERROR! Transaction failed: ".$e->getMessage());
	}
}

function dbInsert($pdo, $query) {
	try {
		$pdo->beginTransaction();
		$pdo->exec($query);
		return $pdo->commit();
	} catch(\Exception $e) {
		$pdo->rollBack();
		die("Error! Insert failed: ".$e->getMessage());
	}
}

function fetchObject($pdo, string $query, string $class) {
	$results = false;
	try {
		$statement = $pdo->query($query, $fetchMode = \PDO::FETCH_CLASS, $classname = $class);
		$results = array();
		foreach ($statement as $row) { $results[] = $row; }
	} catch(\Exception $e) {
		die("ERROR! Transaction failed: ".$e->getMessage());
	}
	return $results;
}

#class OC2DBConn
#{
#	public static $oc2DBConn;
#
#	public function dbConnect($host, $database, $user, $pass) {
#		if(\is_null(OC2DBConn::$oc2DBConn)) {
#			OC2DBConn::$oc2DBConn = new \PDO("mysql:host=$host;dbname=$database", "$user", "$pass");
#		}
#		return OC2DBConn::$oc2DBConn;
#	}
#
#	public function dbQuery($query) {
#	        #print(gettype($oc2DBConn));
#	        $statement = OC2DBConn::$oc2DBConn->query($query);
#	        $row = $statement->fetchAll();
#		unset($statement);
#	        return $row;
#	}
#
#}
?>
