<?php
require('../../config.php');
$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('print', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$id = "?id=".$cm->id;

require_login($course, true, $cm);
$PAGE->set_url('/mod/print/view.php', array('id' => $cm->id));
$PAGE->set_title('ABET Course Module');

$print = $DB->get_records_sql('select * from {print} where course=?', array($course->id));
$p = $print[0];
foreach ($print as $g){ $p = $g; break; }

echo $OUTPUT->header();

echo '<h1>'.$p->name.'</h1><br>';
//echo '<p1>Automate the objectives of '.$course->shortname.' into a printable formatted file.<p1><br /><br />';
echo $p->content."<br><br>";

echo '<form method="post" action="../print/print_course.php'.$id.'">';
echo '<button type="submit">Print '.$COURSE->shortname.' Objectives</button>';
echo '</form>';

echo $OUTPUT->footer();
