<?php
/**
 * Fix: Issue course-level badges to qualifying students
 */
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
global $DB;

echo "ISSUING COURSE-LEVEL BADGES\n";
echo "============================\n\n";

$total_issued = 0;

// Get all course-level badges (type=2 means course badge)
$badges = $DB->get_records('badge', ['type' => 2]);

foreach ($badges as $badge) {
    $courseid = $badge->courseid;
    
    // Check if this is a completion or grade badge by looking at criteria params
    $criteria = $DB->get_records('badge_criteria', ['badgeid' => $badge->id]);
    
    $is_grade_badge = false;
    $min_grade = 0;
    
    foreach ($criteria as $crit) {
        $params = $DB->get_records('badge_criteria_param', ['critid' => $crit->id]);
        foreach ($params as $p) {
            if (strpos($p->name, 'grade_') === 0) {
                $is_grade_badge = true;
                $min_grade = $p->value;
            }
        }
    }
    
    if ($is_grade_badge) {
        // Issue to students with course completion AND grade >= min_grade
        $qualifiers = $DB->get_records_sql("
            SELECT cc.userid, cc.timecompleted, 
                   (gg.finalgrade / gi.grademax * 100) as grade_pct
            FROM {course_completions} cc
            JOIN {grade_grades} gg ON gg.userid = cc.userid
            JOIN {grade_items} gi ON gi.id = gg.itemid 
                AND gi.itemtype = 'course' AND gi.courseid = cc.course
            WHERE cc.course = ? 
            AND cc.timecompleted IS NOT NULL AND cc.timecompleted > 0
            AND gg.finalgrade IS NOT NULL AND gi.grademax > 0
            AND (gg.finalgrade / gi.grademax * 100) >= ?
        ", [$courseid, $min_grade]);
        
        $count = 0;
        foreach ($qualifiers as $q) {
            $existing = $DB->get_record('badge_issued', ['badgeid' => $badge->id, 'userid' => $q->userid]);
            if (!$existing) {
                $issue = new stdClass();
                $issue->badgeid = $badge->id;
                $issue->userid = $q->userid;
                $issue->uniquehash = md5($badge->id . '-' . $q->userid . '-' . time() . '-' . random_int(1000, 9999));
                $issue->dateissued = $q->timecompleted;
                $issue->dateexpire = null;
                $issue->visible = 1;
                $issue->issuernotified = 0;
                $DB->insert_record('badge_issued', $issue);
                $count++;
                $total_issued++;
            }
        }
        echo "  Badge {$badge->id} '{$badge->name}' (Course $courseid, grade>={$min_grade}%): issued to $count students\n";
    } else {
        // Issue to all students who completed the course
        $completions = $DB->get_records_sql("
            SELECT userid, timecompleted 
            FROM {course_completions} 
            WHERE course = ? AND timecompleted IS NOT NULL AND timecompleted > 0
        ", [$courseid]);
        
        $count = 0;
        foreach ($completions as $comp) {
            $existing = $DB->get_record('badge_issued', ['badgeid' => $badge->id, 'userid' => $comp->userid]);
            if (!$existing) {
                $issue = new stdClass();
                $issue->badgeid = $badge->id;
                $issue->userid = $comp->userid;
                $issue->uniquehash = md5($badge->id . '-' . $comp->userid . '-' . time() . '-' . random_int(1000, 9999));
                $issue->dateissued = $comp->timecompleted;
                $issue->dateexpire = null;
                $issue->visible = 1;
                $issue->issuernotified = 0;
                $DB->insert_record('badge_issued', $issue);
                $count++;
                $total_issued++;
            }
        }
        echo "  Badge {$badge->id} '{$badge->name}' (Course $courseid, completion): issued to $count students\n";
    }
}

echo "\nTotal new badges issued: $total_issued\n";

// Final summary
$total = $DB->count_records('badge_issued');
echo "Grand total badge_issued records: $total\n";

// Per-badge breakdown
echo "\nBADGE SUMMARY:\n";
$summary = $DB->get_records_sql("
    SELECT b.id, b.name, b.courseid, b.type, COUNT(bi.id) as issued_count
    FROM {badge} b
    LEFT JOIN {badge_issued} bi ON bi.badgeid = b.id
    GROUP BY b.id, b.name, b.courseid, b.type
    ORDER BY b.id
");
foreach ($summary as $s) {
    $type = $s->type == 1 ? 'SITE' : "Course {$s->courseid}";
    echo "  #{$s->id} {$s->name} ($type): {$s->issued_count} issued\n";
}

purge_all_caches();
echo "\nCaches purged. Done!\n";
