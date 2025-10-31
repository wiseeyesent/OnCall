<?php
require_once('./OnCall2.conf.php');

$header = "<!DOCTYPE html>
<html>
<head>
<meta charset=\"utf-8\" />
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />
<title>On-Call $ONCALL_VERSION</title>
<link rel=\"stylesheet\" href=\"css/sakura.css\" type=\"text/css\" />
<link rel=\"stylesheet\" href=\"css/oncall2.css\" type=\"text/css\" />
<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/oncall/apple-touch-icon.png\">
<link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"favicon-32x32.png\">
<link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"favicon-16x16.png\">
<link rel=\"manifest\" href=\"site.webmanifest\">
</head>
<body style=\"max-width:50em;\">
<main>
<div class=\"container\"><!-- Start Body Content -->
  <header class=\"d-flex flex-wrap justify-content-center py-3 mb-4 border-bottom\">\n";

if(DEBUG > 1) { print("header.php: DEBUG = ".print_r(DEBUG, true)."<br/>\n"); }

$request_url = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];
if(DEBUG > 3) {
	print("header.php: request_url = $request_url<br/>\n");
	print("header.php: request_method = $request_method<br/>\n");
	print("header.php: REQUEST = ".print_r($_REQUEST, true)."<br/>\n");
	print("header.php: REFERER = ".$_SERVER['referer']."<br/>\n");
}

$teamstr = (isset($_REQUEST['teamid'])) ? "&teamid=".(int) $_REQUEST['teamid'] : "";
$activestr = (isset($_REQUEST['active'])) ? "&active=".(int) $_REQUEST['active'] : "";
if(DEBUG > 3) {
	print("header.php: teamstr = $teamstr<br/>\n");
	print("header.php: activestr = $activestr<br/>\n");
}

$schedules_link = "schedules.php";
$templates_link = "templates.php";
$teams_link = "teams.php";

if(!empty($teamstr)) {
	$schedules_link .= $teamstr;
	$templates_link .= $teamstr;
	$teams_link .= $teamstr;
}
if(!empty($activestr)) {
	//$schedules_link .= $activestr;
	$teams_link .= $activestr;
}
$schedules_link = \preg_replace("/&/", "?", $schedules_link, $limit=1);
$templates_link = \preg_replace("/&/", "?", $templates_link, $limit=1);
$teams_link = \preg_replace("/&/", "?", $teams_link, $limit=1);
if(DEBUG > 3) {
	print("header.php: schedules_link = $schedules_link : "
		."templates_link = $templates_link; "
		."teams_link = $teams_link<br/>\n");
}

$nav_items = [
	"Current On-Call" => "/oncall/",
	"Schedules" => $schedules_link,
	"Templates" => $templates_link,
	"Teams" => $teams_link,
	"Employees" => "employees.php"
];
if(DEBUG > 3) { print("header.php: nav_items = ".print_r($nav_items, true)."<br/>\n"); }

$last_key = \array_key_last($nav_items);
$header .= "  <div class=\"header-links\">\n";
foreach ($nav_items as $key => $value) {
	$link = "    <a href=\"$value\" class=\"nav-link\" aria-current=\"page\">$key</a>";
	if($key != $last_key) { $link .= "&nbsp|\n"; }
	else { $link .= "\n  </div>\n"; }
	$header = $header . $link;
}

$header .= "  <div class=\"header-search\">\n"
	  ."  <form action=\"$request_url\" method=\"post\">\n"
	  ."    <input type=\"search\" name=\"q\" id=\"search\" />\n"
	  ."    <input type=\"submit\" value=\"Search\" />\n"
	  ."  </form>\n"
	  ."  </div>\n" //END header-search
	  ."</div>\n"; //END container

unset($key);
unset($last_key);
unset($nav_items);
unset($schedules_link);
unset($teamid);
unset($teams_link);
unset($value);

$header .= "".
"</header>
<div class=\"container\">
<hr>\n";
print($header);
unset($header);
?>
