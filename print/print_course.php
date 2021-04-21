<?php
// This file is designed by Bryant Baumgartner and Matthew Mukai
// as part of our capstone project for IT undergraduate.
//
// This program may be freely manipulated in any way, but
// original credit should be kept to the original design.

require_once("../../config.php");

$students = array();

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('print', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_login($course, true, $cm);
$PAGE->set_url('/mod/print/print_course.php', array('id' => $cm->id));

//Function for declaring each objective
function objective($split) {
    if ($split[1] != "") {
	echo "<center><p style='font-size:18px'><br>";
        echo $split[1]."<br>";
        echo "As Means of Assessment and Grading Criterion<br>";
        echo "for ".strtoupper($split[0]);
	echo "</p></center>";
    }
}

//Function for printing out the results of each objective
function description($split, $DB) {
    $grade = $DB->get_records_sql('select g.grade as grade, a.grade as total from {assign_grades} as g join {assign} as a on a.id=g.assignment where a.name=?', array(trim($split[1])));

    $pass = 0;
    foreach ($grade as $g) {
        if (($g->grade / $g->total) > (intval(trim(explode('%', $split[2])[0])) / 100)) {
	    $pass++;
	}
    }
    echo "Number of students scoring greater than the baseline: ".$split[2]." for ".$split[0].": ".$pass."<br><br>";
}

//Run SQL on the database to get a list of users
$sql = 'select u.id,firstname,lastname from {user} as u join {user_enrolments} as e on e.userid=u.id join {enrol} as r on e.enrolid=r.id join {course} as c on c.id=r.courseid where c.id=?';
$results = $DB->get_records_sql($sql, array($course->id));

foreach($results as $r) {
    array_push($students, $r->lastname.", ".$r->firstname);
}

//This part handles getting important information regarding modules
$print = $DB->get_records_sql('select * from {print} where course=?', array($course->id));
$p = $print[0]; foreach ($print as $g){ $p = $g; break; }
$split = explode("<br>", $p->content);

//This part handles creating the file based on ABET objectives
$coursename = $course->fullname;

header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment;Filename=ABET Objective:".$coursename.".doc");
echo "<html>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=Windows-1252\">";
echo "<body>";

//Main bulk for the Word file
echo "<center><p style='font-size:14px'><i>Report for student outcomes - ".date("m/d/y")."</i></p></center><br>";
echo "<center><p style='font-size:18px'>".$coursename."</p></center>";
for ($i = 0; $i < count($split) - 1; $i++) {
    objective(explode(',', $split[$i]));
}
echo "<br><br>";

echo "Instructor Reporting: TBD<br><br>";
echo "Year: TBD       Term: TBD<br><br>";

echo "Total number of students: ".(count($students) - 1)."<br><br>";
echo "Number of students passing the course: TBD<br><br>";
for ($i = 0; $i < count($split) - 1; $i++) {
    description(explode(',', $split[$i]), $DB);
}

echo "<br /><br />";


echo "</body>";
echo "</html>";


