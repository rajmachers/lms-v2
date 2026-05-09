<?php
// Part 4: Simulate completions, grades, and competency ratings
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');

echo "===== PART 4: SIMULATE COMPLETIONS & GRADES =====\n\n";

$ids = json_decode(file_get_contents('/tmp/moodle_setup_ids.json'), true);
$groups = $ids['groups'];
$courseids = $ids['courseids'];
$ph_comps = $ids['ph_competencies'];

$cid = [
    1 => $courseids['PHY101'],
    2 => $courseids['PHY102'],
    3 => $courseids['PHY201'],
    4 => $courseids['PHY202'],
    5 => $courseids['PHY301'],
    6 => $courseids['PHY302'],
];

// Completion plan per group:
// Group A: Complete ALL 6 courses (100%)
// Group B: Complete courses 1-2 (100%), courses 3-4 (50% activities)
// Group C: Complete courses 1-2 (100%), courses 3-4 (25% activities)
// Group D: Courses 3-4 (75% activities), courses 5-6 (0% - enrolled but not started)
// Group E: Complete courses 1-4 (100%), courses 5-6 (60% activities)

$completion_map = [
    'A' => [1 => 1.0, 2 => 1.0, 3 => 1.0, 4 => 1.0, 5 => 1.0, 6 => 1.0],
    'B' => [1 => 1.0, 2 => 1.0, 3 => 0.5, 4 => 0.5],
    'C' => [1 => 1.0, 2 => 1.0, 3 => 0.25, 4 => 0.25],
    'D' => [3 => 0.75, 4 => 0.75, 5 => 0.0, 6 => 0.0],
    'E' => [1 => 1.0, 2 => 1.0, 3 => 1.0, 4 => 1.0, 5 => 0.6, 6 => 0.6],
];

// Helper: mark activities complete for a user in a course
function simulate_completion($userid, $courseid, $fraction) {
    global $DB;
    
    if ($fraction <= 0) return ['completed' => 0, 'total' => 0];
    
    // Get all course modules for this course
    $cms = $DB->get_records('course_modules', ['course' => $courseid], 'id ASC');
    $total = count($cms);
    $to_complete = (int)round($total * $fraction);
    
    $done = 0;
    foreach ($cms as $cm) {
        if ($done >= $to_complete) break;
        
        // Check if already completed
        $existing = $DB->get_record('course_modules_completion', [
            'coursemoduleid' => $cm->id,
            'userid' => $userid,
        ]);
        if (!$existing) {
            $cmc = new stdClass();
            $cmc->coursemoduleid = $cm->id;
            $cmc->userid = $userid;
            $cmc->completionstate = 1; // Complete
            $cmc->viewed = 1;
            $cmc->overrideby = null;
            $cmc->timemodified = time() - rand(0, 86400 * 30);
            $DB->insert_record('course_modules_completion', $cmc);
        }
        $done++;
    }
    
    return ['completed' => $done, 'total' => $total];
}

// Helper: generate grade for a quiz or assignment
function simulate_grade($userid, $courseid, $modulename, $instanceid, $cmid, $mingrade = 45, $maxgrade = 95) {
    global $DB;
    
    $grade = rand($mingrade * 100, $maxgrade * 100) / 100;
    
    // Get grade item
    $gi = $DB->get_record('grade_items', [
        'courseid' => $courseid,
        'itemmodule' => $modulename,
        'iteminstance' => $instanceid,
    ]);
    if (!$gi) return $grade;
    
    // Check if grade already exists
    $existing = $DB->get_record('grade_grades', [
        'itemid' => $gi->id,
        'userid' => $userid,
    ]);
    if (!$existing) {
        $gg = new stdClass();
        $gg->itemid = $gi->id;
        $gg->userid = $userid;
        $gg->rawgrade = $grade;
        $gg->rawgrademax = 100;
        $gg->rawgrademin = 0;
        $gg->finalgrade = $grade;
        $gg->timecreated = time();
        $gg->timemodified = time();
        $gg->usermodified = 2;
        $DB->insert_record('grade_grades', $gg);
    }
    
    return $grade;
}

// Helper: mark course as complete
function mark_course_complete($userid, $courseid) {
    global $DB;
    
    $existing = $DB->get_record('course_completions', [
        'userid' => $userid,
        'course' => $courseid,
    ]);
    if (!$existing) {
        $cc = new stdClass();
        $cc->userid = $userid;
        $cc->course = $courseid;
        $cc->timeenrolled = time() - 86400 * 60;
        $cc->timestarted = time() - 86400 * 50;
        $cc->timecompleted = time() - rand(86400, 86400 * 10);
        $cc->reaggregate = 0;
        $DB->insert_record('course_completions', $cc);
    }
}

// -------------------------------------------------------
// Process completions per group
// -------------------------------------------------------
echo "--- Processing completions ---\n";

foreach ($completion_map as $grp => $courses) {
    echo "\nGroup $grp:\n";
    foreach ($groups[$grp] as $uidx => $userid) {
        foreach ($courses as $cnum => $fraction) {
            $courseid = $cid[$cnum];
            
            // Simulate activity completions
            $result = simulate_completion($userid, $courseid, $fraction);
            
            // Simulate grades for completed quizzes and assignments
            if ($fraction > 0) {
                $cms = $DB->get_records('course_modules', ['course' => $courseid], 'id ASC');
                $cmlist = array_values($cms);
                $to_grade = (int)round(count($cmlist) * $fraction);
                
                for ($i = 0; $i < $to_grade; $i++) {
                    $cm = $cmlist[$i];
                    $module = $DB->get_field('modules', 'name', ['id' => $cm->module]);
                    
                    if ($module === 'quiz') {
                        simulate_grade($userid, $courseid, 'quiz', $cm->instance, $cm->id, 45, 95);
                    } else if ($module === 'assign') {
                        simulate_grade($userid, $courseid, 'assign', $cm->instance, $cm->id, 50, 90);
                    }
                }
            }
            
            // Mark course complete if 100%
            if ($fraction >= 1.0) {
                mark_course_complete($userid, $courseid);
            }
        }
        
        // Print progress for first student in each group
        if ($uidx == 0) {
            $user = $DB->get_record('user', ['id' => $userid]);
            echo "  {$user->username}: ";
            foreach ($courses as $cnum => $fraction) {
                $pct = (int)($fraction * 100);
                echo "C$cnum={$pct}% ";
            }
            echo "(+9 more)\n";
        }
    }
}

// -------------------------------------------------------
// Set up course completion criteria (activity completion)
// -------------------------------------------------------
echo "\n--- Setting completion criteria ---\n";

foreach ($cid as $cnum => $courseid) {
    // Delete existing criteria
    $DB->delete_records('course_completion_criteria', ['course' => $courseid]);
    
    // Get all course module IDs
    $cms = $DB->get_records('course_modules', ['course' => $courseid], 'id ASC');
    
    foreach ($cms as $cm) {
        $criterion = new stdClass();
        $criterion->course = $courseid;
        $criterion->criteriatype = 4; // Activity completion
        $criterion->moduleinstance = $cm->id;
        $criterion->completionexpected = 0;
        $DB->insert_record('course_completion_criteria', $criterion);
    }
    
    // Set aggregation method to ALL
    $existing = $DB->get_record('course_completion_aggr_methd', [
        'course' => $courseid,
        'criteriatype' => 4,
    ]);
    if (!$existing) {
        $agg = new stdClass();
        $agg->course = $courseid;
        $agg->criteriatype = 4;
        $agg->method = 1; // ALL
        $agg->value = null;
        $DB->insert_record('course_completion_aggr_methd', $agg);
    }
    
    echo "  Course $cnum ($courseid): " . count($cms) . " activities as completion criteria\n";
}

// -------------------------------------------------------
// Set completion criteria met for fully completed students
// -------------------------------------------------------
echo "\n--- Marking criteria complete ---\n";

$fully_completed_groups = [
    'A' => [1, 2, 3, 4, 5, 6],
    'B' => [1, 2],
    'C' => [1, 2],
    'E' => [1, 2, 3, 4],
];

foreach ($fully_completed_groups as $grp => $cnums) {
    foreach ($groups[$grp] as $userid) {
        foreach ($cnums as $cnum) {
            $courseid = $cid[$cnum];
            $criteria = $DB->get_records('course_completion_criteria', ['course' => $courseid]);
            foreach ($criteria as $crit) {
                $exists = $DB->record_exists('course_completion_crit_compl', [
                    'userid' => $userid,
                    'course' => $courseid,
                    'criteriaid' => $crit->id,
                ]);
                if (!$exists) {
                    $cc = new stdClass();
                    $cc->userid = $userid;
                    $cc->course = $courseid;
                    $cc->criteriaid = $crit->id;
                    $cc->timecompleted = time() - rand(86400, 86400 * 10);
                    $DB->insert_record('course_completion_crit_compl', $cc);
                }
            }
        }
    }
    echo "  Group $grp: criteria marked for courses " . implode(',', $cnums) . "\n";
}

// -------------------------------------------------------
// Competency user ratings for completed courses
// -------------------------------------------------------
echo "\n--- Competency ratings ---\n";

$course_comp_map = [
    1 => [7, 10, 20],  // Math Methods, Classical Physics, Problem Solving
    2 => [10, 16, 19], // Classical, Experimental, Scientific Comm
    3 => [7, 13, 20],  // Math, Modern, Problem Solving
    4 => [10, 16, 19], // Classical, Experimental, Scientific Comm
    5 => [13, 16, 20], // Modern, Experimental, Problem Solving
    6 => [13, 19, 20], // Modern, Scientific Comm, Problem Solving
];

// Rate competencies for fully completed courses
foreach ($fully_completed_groups as $grp => $cnums) {
    $rated = 0;
    foreach ($groups[$grp] as $userid) {
        foreach ($cnums as $cnum) {
            $courseid = $cid[$cnum];
            if (!isset($course_comp_map[$cnum])) continue;
            
            foreach ($course_comp_map[$cnum] as $compid) {
                // User competency record
                $uc = $DB->get_record('competency_usercomp', [
                    'userid' => $userid,
                    'competencyid' => $compid,
                ]);
                if (!$uc) {
                    $uc = new stdClass();
                    $uc->userid = $userid;
                    $uc->competencyid = $compid;
                    $uc->status = 0; // Idle
                    $uc->reviewerid = null;
                    $uc->proficiency = 1; // Proficient
                    $uc->grade = 2; // Competent (scale id 2, value 2 = "Competent")
                    $uc->timecreated = time();
                    $uc->timemodified = time();
                    $uc->usermodified = 2;
                    $DB->insert_record('competency_usercomp', $uc);
                    $rated++;
                }
                
                // User competency in course
                $ucc = $DB->get_record('competency_usercompcourse', [
                    'userid' => $userid,
                    'competencyid' => $compid,
                    'courseid' => $courseid,
                ]);
                if (!$ucc) {
                    $ucc = new stdClass();
                    $ucc->userid = $userid;
                    $ucc->courseid = $courseid;
                    $ucc->competencyid = $compid;
                    $ucc->proficiency = 1;
                    $ucc->grade = 2;
                    $ucc->timecreated = time();
                    $ucc->timemodified = time();
                    $ucc->usermodified = 2;
                    $DB->insert_record('competency_usercompcourse', $ucc);
                }
            }
        }
    }
    echo "  Group $grp: $rated competency ratings\n";
}

// -------------------------------------------------------
// Ensure course grade totals exist
// -------------------------------------------------------
echo "\n--- Course grade totals ---\n";

foreach ($cid as $cnum => $courseid) {
    // Get or create course grade item
    $courseitem = $DB->get_record('grade_items', [
        'courseid' => $courseid,
        'itemtype' => 'course',
    ]);
    if (!$courseitem) {
        $gi = new stdClass();
        $gi->courseid = $courseid;
        $gi->itemtype = 'course';
        $gi->itemname = null;
        $gi->grademax = 100;
        $gi->grademin = 0;
        $gi->timecreated = time();
        $gi->timemodified = time();
        $gi->gradetype = 1;
        $gi->aggregationcoef = 0;
        $gi->aggregationcoef2 = 0;
        $giid = $DB->insert_record('grade_items', $gi);
        $courseitem = $DB->get_record('grade_items', ['id' => $giid]);
        echo "  Created course grade item for course $cnum\n";
    }
    
    // Calculate average grades for enrolled students with grades
    $graded_items = $DB->get_records('grade_items', [
        'courseid' => $courseid,
        'itemtype' => 'mod',
    ]);
    
    // Get all enrolled students
    $enrolled = $DB->get_records_sql(
        "SELECT DISTINCT ue.userid 
         FROM {user_enrolments} ue 
         JOIN {enrol} e ON e.id = ue.enrolid 
         WHERE e.courseid = ?",
        [$courseid]
    );
    
    $gradecount = 0;
    foreach ($enrolled as $eu) {
        $total = 0;
        $count = 0;
        foreach ($graded_items as $gi) {
            $gg = $DB->get_record('grade_grades', [
                'itemid' => $gi->id,
                'userid' => $eu->userid,
            ]);
            if ($gg && $gg->finalgrade !== null) {
                $total += $gg->finalgrade;
                $count++;
            }
        }
        
        if ($count > 0) {
            $avg = $total / $count;
            $existing = $DB->get_record('grade_grades', [
                'itemid' => $courseitem->id,
                'userid' => $eu->userid,
            ]);
            if (!$existing) {
                $gg = new stdClass();
                $gg->itemid = $courseitem->id;
                $gg->userid = $eu->userid;
                $gg->rawgrade = $avg;
                $gg->rawgrademax = 100;
                $gg->rawgrademin = 0;
                $gg->finalgrade = $avg;
                $gg->timecreated = time();
                $gg->timemodified = time();
                $gg->usermodified = 2;
                $DB->insert_record('grade_grades', $gg);
                $gradecount++;
            }
        }
    }
    echo "  Course $cnum: $gradecount course grades calculated\n";
}

// Rebuild caches for all courses
echo "\n--- Rebuilding caches ---\n";
foreach ($cid as $cnum => $courseid) {
    rebuild_course_cache($courseid, true);
    echo "  Course $cnum cache rebuilt\n";
}

echo "\nPart 4 complete!\n";
