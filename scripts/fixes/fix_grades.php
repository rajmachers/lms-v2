<?php
// Fix grade structure: link all grade items to their course root category
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
require_once($CFG->libdir . '/gradelib.php');

echo "===== FIX GRADE STRUCTURE =====\n\n";

$physics_courses = [4, 5, 6, 7, 8, 9];

foreach ($physics_courses as $courseid) {
    $course = $DB->get_record('course', ['id' => $courseid]);
    echo "--- {$course->shortname} (ID:$courseid) ---\n";

    // Get or verify root grade category
    $rootcat = $DB->get_record('grade_categories', [
        'courseid' => $courseid,
        'depth' => 1,
    ]);

    if (!$rootcat) {
        echo "  Creating root grade category...\n";
        $rootcat = new stdClass();
        $rootcat->courseid = $courseid;
        $rootcat->fullname = '?';
        $rootcat->parent = null;
        $rootcat->depth = 1;
        $rootcat->aggregation = 13; // Natural
        $rootcat->timecreated = time();
        $rootcat->timemodified = time();
        $rootcatid = $DB->insert_record('grade_categories', $rootcat);
        $DB->set_field('grade_categories', 'path', '/' . $rootcatid . '/', ['id' => $rootcatid]);
        $rootcat = $DB->get_record('grade_categories', ['id' => $rootcatid]);
    }
    echo "  Root category: ID={$rootcat->id}, path={$rootcat->path}\n";

    // Find the correct course-type grade item (the one Moodle auto-created with iteminstance = rootcat id)
    $course_items = $DB->get_records('grade_items', [
        'courseid' => $courseid,
        'itemtype' => 'course',
    ]);

    $keep_item = null;
    $remove_items = [];

    foreach ($course_items as $ci) {
        if ($ci->iteminstance == $rootcat->id) {
            $keep_item = $ci;
        } else {
            $remove_items[] = $ci;
        }
    }

    // If we don't have the proper course grade item, use the first one and fix it
    if (!$keep_item && !empty($course_items)) {
        $keep_item = array_shift($course_items);
        $remove_items = array_values($course_items);
    }

    if (!$keep_item) {
        // Create course grade item
        $gi = new stdClass();
        $gi->courseid = $courseid;
        $gi->itemtype = 'course';
        $gi->iteminstance = $rootcat->id;
        $gi->categoryid = null; // course items don't have categoryid in some versions
        $gi->grademax = 100;
        $gi->grademin = 0;
        $gi->gradetype = 1;
        $gi->aggregationcoef = 0;
        $gi->aggregationcoef2 = 0;
        $gi->timecreated = time();
        $gi->timemodified = time();
        $giid = $DB->insert_record('grade_items', $gi);
        $keep_item = $DB->get_record('grade_items', ['id' => $giid]);
        echo "  Created course grade item (ID:$giid)\n";
    }

    // Fix the kept course grade item
    $DB->set_field('grade_items', 'iteminstance', $rootcat->id, ['id' => $keep_item->id]);
    echo "  Course grade item ID:{$keep_item->id} -> iteminstance={$rootcat->id}\n";

    // Move grades from duplicate items to the kept one, then delete duplicates
    foreach ($remove_items as $ri) {
        // Move grades
        $grades = $DB->get_records('grade_grades', ['itemid' => $ri->id]);
        foreach ($grades as $g) {
            $exists = $DB->get_record('grade_grades', [
                'itemid' => $keep_item->id,
                'userid' => $g->userid,
            ]);
            if (!$exists) {
                $DB->set_field('grade_grades', 'itemid', $keep_item->id, ['id' => $g->id]);
            } else {
                // Keep the one with data, delete duplicate
                $DB->delete_records('grade_grades', ['id' => $g->id]);
            }
        }
        $DB->delete_records('grade_items', ['id' => $ri->id]);
        echo "  Removed duplicate course grade item ID:{$ri->id}\n";
    }

    // Fix all mod-type grade items: set categoryid to root category
    $mod_items = $DB->get_records_select('grade_items',
        "courseid = ? AND itemtype = 'mod'",
        [$courseid]
    );
    $fixed = 0;
    foreach ($mod_items as $mi) {
        if (empty($mi->categoryid) || $mi->categoryid != $rootcat->id) {
            $DB->set_field('grade_items', 'categoryid', $rootcat->id, ['id' => $mi->id]);
            $fixed++;
        }
    }
    echo "  Fixed categoryid on $fixed grade items -> category {$rootcat->id}\n";

    // Set sortorder on grade items to avoid display issues
    $all_items = $DB->get_records('grade_items', ['courseid' => $courseid], 'id ASC');
    $sort = 1;
    foreach ($all_items as $item) {
        $DB->set_field('grade_items', 'sortorder', $sort, ['id' => $item->id]);
        $sort++;
    }
    echo "  Set sortorder on " . count($all_items) . " items\n";

    echo "\n";
}

// Also fix Health Sciences courses (2, 3) if they have issues
foreach ([2, 3] as $courseid) {
    $course = $DB->get_record('course', ['id' => $courseid]);
    $rootcat = $DB->get_record('grade_categories', ['courseid' => $courseid, 'depth' => 1]);
    if ($rootcat) {
        $fixed = $DB->execute(
            "UPDATE {grade_items} SET categoryid = ? WHERE courseid = ? AND itemtype = 'mod' AND (categoryid IS NULL OR categoryid = 0)",
            [$rootcat->id, $courseid]
        );
        echo "--- {$course->shortname} (ID:$courseid): fixed mod items categoryid -> {$rootcat->id}\n";
    }
}

// Now run grade regrade for all courses
echo "\n--- Regrading all courses ---\n";
foreach (array_merge($physics_courses, [2, 3]) as $courseid) {
    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) continue;
    
    // Force regrade
    $DB->set_field('grade_items', 'needsupdate', 1, ['courseid' => $courseid]);
    
    // Use grade_regrade_final_grades
    grade_regrade_final_grades($courseid);
    echo "  Regraded course $courseid ({$course->shortname})\n";
}

// Clear any stuck ad hoc tasks
echo "\n--- Clearing stuck regrade tasks ---\n";
$stuck = $DB->get_records_select('task_adhoc', "classname LIKE '%regrade%'");
if ($stuck) {
    foreach ($stuck as $task) {
        $DB->delete_records('task_adhoc', ['id' => $task->id]);
    }
    echo "  Cleared " . count($stuck) . " stuck regrade tasks\n";
} else {
    echo "  No stuck regrade tasks found\n";
}

// Run cron regrade
echo "\n--- Final verification ---\n";
foreach ($physics_courses as $courseid) {
    $rootcat = $DB->get_record('grade_categories', ['courseid' => $courseid, 'depth' => 1]);
    $items = $DB->get_records('grade_items', ['courseid' => $courseid]);
    $orphans = 0;
    foreach ($items as $i) {
        if ($i->itemtype === 'mod' && (empty($i->categoryid) || $i->categoryid == 0)) {
            $orphans++;
        }
    }
    $course = $DB->get_record('course', ['id' => $courseid]);
    echo "  {$course->shortname}: root_cat={$rootcat->id}, items=" . count($items) . ", orphans=$orphans\n";
}

echo "\nGrade fix complete!\n";
