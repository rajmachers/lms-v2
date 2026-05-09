<?php
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/course/lib.php');

echo "=== Setting up certificates for all courses ===\n\n";

// -------------------------------------------------------
// STEP 1: Set course completion criteria for both courses
// Criteria type 8 = COMPLETION_CRITERIA_TYPE_ACTIVITY
// -------------------------------------------------------
echo "--- Step 1: Setting course completion criteria ---\n";

foreach ([2, 3] as $courseid) {
    $course = $DB->get_record('course', ['id' => $courseid]);
    echo "\nCourse $courseid: $course->fullname\n";

    // Delete existing criteria
    $DB->delete_records('course_completion_criteria', ['course' => $courseid]);
    $DB->delete_records('course_completion_aggr_methd', ['course' => $courseid]);

    // Get all activities except the certificate itself
    $activities = $DB->get_records_sql(
        "SELECT cm.id, cm.module, cm.instance, m.name as modname
         FROM {course_modules} cm
         JOIN {modules} m ON m.id = cm.module
         WHERE cm.course = ? AND cm.deletioninprogress = 0
           AND m.name != 'coursecertificate'
         ORDER BY cm.id",
        [$courseid]
    );

    $critcount = 0;
    foreach ($activities as $act) {
        // Ensure activity completion is enabled
        $DB->set_field('course_modules', 'completion', 1, ['id' => $act->id]);

        // Add completion criterion
        $criterion = new stdClass();
        $criterion->course = $courseid;
        $criterion->criteriatype = 8; // COMPLETION_CRITERIA_TYPE_ACTIVITY
        $criterion->module = $act->modname;
        $criterion->moduleinstance = $act->id;
        $criterion->gradepass = null;
        $criterion->role = null;
        $criterion->timeend = null;
        $criterion->enrolperiod = null;
        $DB->insert_record('course_completion_criteria', $criterion);
        $critcount++;
    }

    // Set aggregation method: ALL activities must be complete
    $aggr = new stdClass();
    $aggr->course = $courseid;
    $aggr->criteriatype = 8; // activity type
    $aggr->method = 1; // 1 = ALL
    $DB->insert_record('course_completion_aggr_methd', $aggr);

    // Also set overall aggregation
    $aggr2 = new stdClass();
    $aggr2->course = $courseid;
    $aggr2->criteriatype = null;
    $aggr2->method = 1; // ALL
    $DB->insert_record('course_completion_aggr_methd', $aggr2);

    echo "  Added $critcount activity completion criteria (ALL required)\n";
}

// -------------------------------------------------------
// STEP 2: Fix existing certificate in Course 2
// -------------------------------------------------------
echo "\n--- Step 2: Fixing certificate in Course 2 ---\n";

$existingcert = $DB->get_record_sql(
    "SELECT cm.id as cmid, cc.id as ccid, cc.name, cc.template
     FROM {coursecertificate} cc
     JOIN {course_modules} cm ON cm.instance = cc.id
     JOIN {modules} m ON m.id = cm.module AND m.name = 'coursecertificate'
     WHERE cm.course = 2 AND cm.deletioninprogress = 0"
);

if ($existingcert) {
    echo "  Found: cm:" . $existingcert->cmid . " [" . $existingcert->name . "] template:" . $existingcert->template . "\n";

    // Enable completion tracking on the cert activity
    $DB->set_field('course_modules', 'completion', 0, ['id' => $existingcert->cmid]);

    // Set access restriction: course completion required
    // availability JSON: require ALL other activities to be complete (use course completion)
    // Actually, simpler: restrict by completion of the last regular activity
    // Best approach: use the Moodle availability system with "completion" condition
    $lastactivity = $DB->get_record_sql(
        "SELECT cm.id FROM {course_modules} cm
         JOIN {modules} m ON m.id = cm.module
         WHERE cm.course = 2 AND cm.deletioninprogress = 0 AND m.name != 'coursecertificate'
         ORDER BY cm.id DESC LIMIT 1"
    );

    if ($lastactivity) {
        $availability = json_encode([
            'op' => '&',
            'c' => [
                [
                    'type' => 'completion',
                    'cm' => (int)$lastactivity->id,
                    'e' => 1 // must be complete
                ]
            ],
            'showc' => [true]
        ]);
        $DB->set_field('course_modules', 'availability', $availability, ['id' => $existingcert->cmid]);
        echo "  Set restriction: requires cm:" . $lastactivity->id . " to be complete\n";
    }

    // Ensure automaticsend is enabled (auto-issue)
    $DB->set_field('coursecertificate', 'automaticsend', 1, ['id' => $existingcert->ccid]);
    echo "  Enabled automatic send\n";
} else {
    echo "  No existing certificate found in Course 2\n";
}

// -------------------------------------------------------
// STEP 3: Add certificate activity to Course 3
// -------------------------------------------------------
echo "\n--- Step 3: Adding certificate to Course 3 ---\n";

$existingcert3 = $DB->get_record_sql(
    "SELECT cm.id FROM {coursecertificate} cc
     JOIN {course_modules} cm ON cm.instance = cc.id
     JOIN {modules} m ON m.id = cm.module AND m.name = 'coursecertificate'
     WHERE cm.course = 3 AND cm.deletioninprogress = 0"
);

if ($existingcert3) {
    echo "  Certificate already exists in Course 3, skipping.\n";
} else {
    $moduleid = $DB->get_field('modules', 'id', ['name' => 'coursecertificate']);

    // Create the coursecertificate instance
    $certinstance = new stdClass();
    $certinstance->course = 3;
    $certinstance->name = 'Course Completion Certificate';
    $certinstance->intro = '<p>Congratulations on completing the Certificate on Family Medicine program! Download your certificate below.</p>';
    $certinstance->introformat = 1;
    $certinstance->template = 1; // Template ID 1 = "Certificate demo template"
    $certinstance->automaticsend = 1;
    $certinstance->timemodified = time();
    $certinstanceid = $DB->insert_record('coursecertificate', $certinstance);
    echo "  Created coursecertificate instance: $certinstanceid\n";

    // Find the last section of Course 3
    $lastsection = $DB->get_record_sql(
        "SELECT id, section, sequence FROM {course_sections}
         WHERE course = 3 ORDER BY section DESC LIMIT 1"
    );

    // Create course_module record
    $cm = new stdClass();
    $cm->course = 3;
    $cm->module = $moduleid;
    $cm->instance = $certinstanceid;
    $cm->section = $lastsection->id;
    $cm->added = time();
    $cm->visible = 1;
    $cm->visibleoncoursepage = 1;
    $cm->groupmode = 0;
    $cm->completion = 0; // No completion tracking on the cert itself
    $cm->deletioninprogress = 0;

    // Restriction: last activity in course must be complete
    $lastactivity3 = $DB->get_record_sql(
        "SELECT cm.id FROM {course_modules} cm
         JOIN {modules} m ON m.id = cm.module
         WHERE cm.course = 3 AND cm.deletioninprogress = 0 AND m.name != 'coursecertificate'
         ORDER BY cm.id DESC LIMIT 1"
    );
    if ($lastactivity3) {
        $cm->availability = json_encode([
            'op' => '&',
            'c' => [
                [
                    'type' => 'completion',
                    'cm' => (int)$lastactivity3->id,
                    'e' => 1
                ]
            ],
            'showc' => [true]
        ]);
    }

    $cmid = $DB->insert_record('course_modules', $cm);
    echo "  Created course_module: $cmid\n";

    // Add to the section sequence
    $newseq = $lastsection->sequence ? $lastsection->sequence . ',' . $cmid : (string)$cmid;
    $DB->set_field('course_sections', 'sequence', $newseq, ['id' => $lastsection->id]);
    echo "  Added to section " . $lastsection->section . " (id:" . $lastsection->id . ")\n";

    // Create context for the module
    context_module::instance($cmid);
    echo "  Created module context\n";

    if ($lastactivity3) {
        echo "  Set restriction: requires cm:" . $lastactivity3->id . " to be complete\n";
    }
}

// -------------------------------------------------------
// STEP 4: Rebuild course caches
// -------------------------------------------------------
echo "\n--- Step 4: Rebuilding course caches ---\n";
rebuild_course_cache(2, true);
rebuild_course_cache(3, true);
echo "  Course caches rebuilt.\n";

// -------------------------------------------------------
// STEP 5: Summary
// -------------------------------------------------------
echo "\n=== SUMMARY ===\n";
$certs = $DB->get_records_sql(
    "SELECT cm.id as cmid, cm.course, cc.name, cc.template, cc.automaticsend, cm.availability
     FROM {coursecertificate} cc
     JOIN {course_modules} cm ON cm.instance = cc.id
     JOIN {modules} m ON m.id = cm.module AND m.name = 'coursecertificate'
     WHERE cm.deletioninprogress = 0
     ORDER BY cm.course"
);
foreach ($certs as $c) {
    echo "Course " . $c->course . ": [" . $c->name . "] template:" . $c->template
        . " autosend:" . $c->automaticsend
        . " restriction:" . ($c->availability ?: 'none') . "\n";
}

$critcount = $DB->count_records('course_completion_criteria');
echo "\nTotal completion criteria: $critcount\n";
echo "\nDone! Students can now:\n";
echo "1. Complete all course activities\n";
echo "2. Access the certificate activity (unlocks after last activity complete)\n";
echo "3. Download their PDF certificate\n";
echo "4. Verify certificates using QR code\n";
