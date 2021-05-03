<?php
// This file is designed by Bryant Baumgartner and Matthew Mukai
// as part of our capstone project for IT undergraduate.
//
// This program may be freely manipulated in any way, but
// original credit should be kept to the original design.

require_once("../../config.php");
require_once($CFG->dirroot.'/grade/querylib.php');
require_once($CFG->dirroot.'/grade/lib.php');

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
    echo "Number of students scoring greater than the baseline: <u>".$split[2]."</u> for <u>".$split[0]."</u>: <u>".$pass."</u><br><br>";
}

function gradereport($c, $DB) {
    $sql = 'select u.id from {user} as u join {user_enrolments} as e on e.userid=u.id join {enrol} as r on e.enrolid=r.id join {course} as c on c.id=r.courseid where c.id=?';
    $results = $DB->get_records_sql($sql, array($c));

    $passing = 0;
    foreach($results as $r) {
        $person = grade_get_course_grades($c, $r->id);
	$pass = $person->grademax;
	foreach($person->grades as $g){
            if (($g->grade / $pass) * 100 >= 70) {
	        $passing += 1;
	    }
	}
    }

    echo "Number of students passing the course: <u>".$passing."</u><br><br>";
}

//Run SQL on the database to get a list of students
$students = array();
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
echo "<center><p style='font-size:14px'><i>Report for Student Outcomes - ".date("m/d/y")."</i></p></center><br>";

echo "<center><p style='font-size:18px'>".$coursename."</p></center>";
for ($i = 0; $i < count($split) - 1; $i++) {
    objective(explode(',', $split[$i]));
}
echo "<br>";

//Print the course objective and the objectives (Add clause for multiple pages)
$object = $DB->get_record_sql('select content from {page} where course=?', array($course->id));
echo "<center><i>".$object->content."</i></center><br><br>";

//This part gets the editing teacher for the course (Add clause for multiple editing teachers)
$sql = 'select firstname,lastname from {user} as u join {role_assignments} as r on r.userid=u.id join {user_enrolments} as ue on ue.userid=u.id join {enrol} as e on e.id=ue.id join {course} as c on c.id=e.courseid where r.roleid=? and c.id=?';
$teacher = $DB->get_record_sql($sql, array(3,$course->id));

echo "Instructor Reporting: <u>".$teacher->firstname." ".$teacher->lastname."</u><br><br>";

//This part gets the term and the year of the course
$time = $DB->get_record_sql('select timecreated from {course} where id=?', array($course->id));
$month = date('M',$time->timecreated);
$term = "Summer";
if ($month<=3) { $term="Spring"; }
if ($month>=8) { $term="Fall"; }

echo "Year: <u>".date('Y',$time->timecreated)."</u>       Term: <u>".$term."</u><br><br>";

echo "Total number of students: <u>".(count($students) - 1)."</u><br><br>";
gradereport($cm->course, $DB);

//Print each objective and statistic
for ($i = 0; $i < count($split) - 1; $i++) {
    description(explode(',', $split[$i]), $DB);
}

echo "<br /><br />";
echo "</body>";
echo "</html>";