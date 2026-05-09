<?php
/**
 * Fix grade badge thresholds and issue them
 */
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
global $DB;

echo "ADJUSTING GRADE BADGE THRESHOLDS\n";
echo "==================================\n\n";

// New thresholds based on actual grade data
$badge_fixes = [
    2 => ['name' => 'Clinical Excellence', 'old' => 80, 'new' => 70, 'desc' => 'Awarded for achieving 70%+ in Fellowship clinical assessments.'],
    4 => ['name' => 'Outstanding Certificate', 'old' => 85, 'new' => 65, 'desc' => 'Awarded for achieving distinction (65%+) in the Certificate programme.'],
    6 => ['name' => "Newton's Prodigy", 'old' => 90, 'new' => 65, 'desc' => 'Awarded for scoring 65%+ in PHY101 assessments.'],
    8 => ['name' => "Maxwell's Star", 'old' => 85, 'new' => 65, 'desc' => 'Awarded for scoring 65%+ in PHY102 assessments.'],
    10 => ['name' => "Schrödinger's Scholar", 'old' => 85, 'new' => 45, 'desc' => 'Awarded for scoring 45%+ in PHY201 assessments.'],
    12 => ['name' => 'Boltzmann Brilliance', 'old' => 85, 'new' => 45, 'desc' => 'Awarded for scoring 45%+ in PHY202 assessments.'],
    14 => ['name' => 'Particle Physicist', 'old' => 90, 'new' => 50, 'desc' => 'Awarded for scoring 50%+ in PHY301 assessments.'],
    16 => ['name' => 'Stellar Achiever', 'old' => 90, 'new' => 50, 'desc' => 'Awarded for scoring 50%+ in PHY302 assessments.'],
];

$total_issued = 0;

foreach ($badge_fixes as $badgeid => $fix) {
    // Update badge description
    $DB->set_field('badge', 'description', $fix['desc'], ['id' => $badgeid]);
    
    // Get the criteria and update the grade param
    $criteria = $DB->get_records('badge_criteria', ['badgeid' => $badgeid]);
    foreach ($criteria as $crit) {
        $params = $DB->get_records('badge_criteria_param', ['critid' => $crit->id]);
        foreach ($params as $p) {
            if (strpos($p->name, 'grade_') === 0) {
                $DB->set_field('badge_criteria_param', 'value', $fix['new'], ['id' => $p->id]);
                echo "  Badge $badgeid '{$fix['name']}': threshold {$fix['old']}% -> {$fix['new']}%\n";
            }
        }
    }
    
    // Now issue to qualifying students
    $badge = $DB->get_record('badge', ['id' => $badgeid]);
    $courseid = $badge->courseid;
    
    $qualifiers = $DB->get_records_sql("
        SELECT cc.userid, cc.timecompleted
        FROM {course_completions} cc
        JOIN {grade_grades} gg ON gg.userid = cc.userid
        JOIN {grade_items} gi ON gi.id = gg.itemid 
            AND gi.itemtype = 'course' AND gi.courseid = cc.course
        WHERE cc.course = ? 
        AND cc.timecompleted IS NOT NULL AND cc.timecompleted > 0
        AND gg.finalgrade IS NOT NULL AND gi.grademax > 0
        AND (gg.finalgrade / gi.grademax * 100) >= ?
    ", [$courseid, $fix['new']]);
    
    $count = 0;
    foreach ($qualifiers as $q) {
        $existing = $DB->get_record('badge_issued', ['badgeid' => $badgeid, 'userid' => $q->userid]);
        if (!$existing) {
            $issue = new stdClass();
            $issue->badgeid = $badgeid;
            $issue->userid = $q->userid;
            $issue->uniquehash = md5($badgeid . '-' . $q->userid . '-' . time() . '-' . random_int(1000, 9999));
            $issue->dateissued = $q->timecompleted;
            $issue->dateexpire = null;
            $issue->visible = 1;
            $issue->issuernotified = 0;
            $DB->insert_record('badge_issued', $issue);
            $count++;
            $total_issued++;
        }
    }
    echo "    -> Issued to $count students\n";
}

echo "\nTotal new grade badges issued: $total_issued\n";

// Final summary
echo "\nFINAL BADGE SUMMARY:\n";
$summary = $DB->get_records_sql("
    SELECT b.id, b.name, b.courseid, b.type, COUNT(bi.id) as issued_count
    FROM {badge} b
    LEFT JOIN {badge_issued} bi ON bi.badgeid = b.id
    GROUP BY b.id, b.name, b.courseid, b.type
    ORDER BY b.id
");
foreach ($summary as $s) {
    $type = $s->type == 1 ? 'SITE' : "Course {$s->courseid}";
    $emoji = $s->issued_count > 0 ? '✓' : ' ';
    echo "  [$emoji] #{$s->id} {$s->name} ($type): {$s->issued_count} issued\n";
}

$grand = $DB->count_records('badge_issued');
echo "\nGrand total: $grand badges issued\n";

purge_all_caches();
echo "Caches purged. Done!\n";
