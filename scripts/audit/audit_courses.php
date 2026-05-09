<?php
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');

// Check course completion criteria
echo "=== Course Completion Criteria ===\n";
$criteria = $DB->get_records_sql("SELECT cc.id, cc.course, cc.criteriatype, cc.module, cc.moduleinstance
    FROM {course_completion_criteria} cc ORDER BY cc.course, cc.criteriatype");
if (empty($criteria)) {
    echo "  (none set)\n";
} else {
    foreach ($criteria as $cr) {
        echo "  Course:" . $cr->course . " type:" . $cr->criteriatype . " module:" . $cr->module . " instance:" . $cr->moduleinstance . "\n";
    }
}

// Check existing activities in both courses
foreach ([2, 3] as $cid) {
    echo "\n=== Activities in Course $cid ===\n";
    $mods = $DB->get_records_sql("SELECT cm.id, cm.module, cm.instance, cm.section, cm.completion, m.name as modname
        FROM {course_modules} cm JOIN {modules} m ON m.id = cm.module
        WHERE cm.course = ? AND cm.deletioninprogress = 0 ORDER BY cm.section, cm.id", [$cid]);
    foreach ($mods as $m) {
        $actname = $DB->get_field($m->modname, 'name', ['id' => $m->instance]);
        echo "  cm:" . $m->id . " " . $m->modname . " [" . $actname . "] section:" . $m->section . " completion:" . $m->completion . "\n";
    }

    echo "\n=== Sections Course $cid ===\n";
    $secs = $DB->get_records('course_sections', ['course' => $cid], 'section', 'id, section, name, sequence');
    foreach ($secs as $s) {
        echo "  Section " . $s->section . " [" . $s->name . "] id:" . $s->id . " seq:" . $s->sequence . "\n";
    }
}

// Check mod_coursecertificate module ID
echo "\n=== Module IDs ===\n";
$modid = $DB->get_record('modules', ['name' => 'coursecertificate']);
echo "coursecertificate module_id: " . ($modid ? $modid->id : "NOT FOUND") . "\n";

// Check enrolled users
echo "\n=== Enrolled Users ===\n";
foreach ([2, 3] as $cid) {
    $ctx = context_course::instance($cid);
    $users = get_enrolled_users($ctx, '', 0, 'u.id, u.username, u.firstname, u.lastname');
    echo "Course $cid: " . count($users) . " users\n";
    foreach ($users as $u) {
        echo "  " . $u->username . " (" . $u->firstname . " " . $u->lastname . ")\n";
    }
}
