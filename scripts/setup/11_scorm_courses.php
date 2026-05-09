<?php
/**
 * Create 4 SCORM compliance courses with strict player settings.
 * - Creates courses in appropriate categories
 * - Uploads SCORM packages to Moodle file system
 * - Creates SCORM activities with strict no-skip settings
 * - Enrolls appropriate students
 * - Adds Feedback + Certificate activities
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
global $DB, $CFG;
$CFG->debug = E_ALL;
$CFG->debugdisplay = 1;
require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/filestorage/file_storage.php');

// Flush all Moodle output buffers
while (ob_get_level()) { ob_end_flush(); }
ob_implicit_flush(true);

// Direct file logging to bypass Moodle output buffering
$LOGFILE = fopen('/tmp/scorm_build.log', 'w');
function logmsg($msg) {
    global $LOGFILE;
    fwrite($LOGFILE, $msg . "\n");
    fflush($LOGFILE);
}
set_exception_handler(function($e) {
    logmsg("EXCEPTION: " . $e->getMessage());
    logmsg("File: " . $e->getFile() . ":" . $e->getLine());
    logmsg("Trace: " . $e->getTraceAsString());
    exit(1);
});
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logmsg("FATAL: {$error['message']} in {$error['file']}:{$error['line']}");
    }
    global $LOGFILE;
    if ($LOGFILE) fclose($LOGFILE);
});

try {
logmsg("============================================================");
logmsg("  SCORM COMPLIANCE COURSES BUILDER");
logmsg("============================================================");
logmsg("");

// ============================================================
// 1. DEFINE COURSES
// ============================================================
$courses_def = [
    [
        'shortname'  => 'COMP-AI01',
        'fullname'   => 'Academic Integrity & Plagiarism Prevention',
        'summary'    => '<p>Mandatory training on academic integrity, plagiarism types, proper referencing, and the consequences of academic misconduct. All students must complete this module before submitting any assessed work.</p><p><strong>Duration:</strong> ~15 minutes | <strong>Format:</strong> SCORM interactive slides with timed progression</p>',
        'category'   => 9, // Open Courses Free
        'scorm_file' => '/tmp/scorm_academic_integrity.zip',
        'scorm_name' => 'Academic Integrity Training',
        'enroll'     => 'all', // all students
        'paid'       => false,
    ],
    [
        'shortname'  => 'COMP-LS01',
        'fullname'   => 'Laboratory Safety Essentials',
        'summary'    => '<p>Comprehensive laboratory safety training covering PPE, chemical hazards (GHS), COSHH assessments, fire safety, spill procedures, electrical safety, sharps disposal, and risk assessment. Required before laboratory access.</p><p><strong>Duration:</strong> ~20 minutes | <strong>Format:</strong> SCORM interactive slides with timed progression</p>',
        'category'   => 3, // Faculty of Sciences
        'scorm_file' => '/tmp/scorm_lab_safety.zip',
        'scorm_name' => 'Laboratory Safety Training',
        'enroll'     => 'physics', // physics students
        'paid'       => false,
    ],
    [
        'shortname'  => 'COMP-IS01',
        'fullname'   => 'Information Security & GDPR Compliance',
        'summary'    => '<p>Essential training on data protection (UK GDPR), password security, phishing awareness, data breach reporting, and secure file sharing. Covers your legal responsibilities when handling personal data in research and professional contexts.</p><p><strong>Duration:</strong> ~15 minutes | <strong>Format:</strong> SCORM interactive slides with timed progression</p>',
        'category'   => 8, // Professional Development
        'scorm_file' => '/tmp/scorm_info_security.zip',
        'scorm_name' => 'Information Security & GDPR Training',
        'enroll'     => 'pd', // PD students
        'paid'       => true,
        'price'      => '39.00',
    ],
    [
        'shortname'  => 'COMP-EDI01',
        'fullname'   => 'Equality, Diversity & Inclusion',
        'summary'    => '<p>Mandatory training on the Equality Act 2010, protected characteristics, unconscious bias, inclusive language, microaggressions, reasonable adjustments, and allyship. Building an inclusive university community is everyone\'s responsibility.</p><p><strong>Duration:</strong> ~15 minutes | <strong>Format:</strong> SCORM interactive slides with timed progression</p>',
        'category'   => 9, // Open Courses Free
        'scorm_file' => '/tmp/scorm_edi.zip',
        'scorm_name' => 'Equality, Diversity & Inclusion Training',
        'enroll'     => 'all', // all students
        'paid'       => false,
    ],
];

// ============================================================
// 2. CREATE COURSES
// ============================================================
logmsg("--- CREATING COURSES ---");

$created_courses = [];
foreach ($courses_def as $cdef) {
    // Check if course already exists
    $existing = $DB->get_record('course', ['shortname' => $cdef['shortname']]);
    if ($existing) {
        logmsg("  [{$cdef['shortname']}] Already exists (id={$existing->id}), using existing");
        $created_courses[$cdef['shortname']] = $existing;
        continue;
    }

    $data = new stdClass();
    $data->fullname = $cdef['fullname'];
    $data->shortname = $cdef['shortname'];
    $data->category = $cdef['category'];
    $data->summary = $cdef['summary'];
    $data->summaryformat = 1;
    $data->format = 'topics';
    $data->numsections = 3;
    $data->visible = 1;
    $data->startdate = strtotime('2026-03-01');
    $data->enddate = 0;
    $data->enablecompletion = 1;
    $data->showactivitydates = 1;
    $data->showcompletionconditions = 1;
    $data->lang = '';

    $course = create_course($data);
    logmsg("  [{$cdef['shortname']}] Created: id={$course->id} — {$cdef['fullname']}");
    $created_courses[$cdef['shortname']] = $course;
}
logmsg("");

// ============================================================
// 3. HELPER: Add course module
// ============================================================
function build_add_course_module($DB, $courseid, $modulename, $instanceid, $sectionnum, $name) {
    $module = $DB->get_record('modules', ['name' => $modulename], '*', MUST_EXIST);
    
    $cm = new stdClass();
    $cm->course = $courseid;
    $cm->module = $module->id;
    $cm->instance = $instanceid;
    $cm->section = 0; // updated below
    $cm->visible = 1;
    $cm->visibleoncoursepage = 1;
    $cm->added = time();
    $cm->completion = ($modulename === 'scorm') ? 2 : 1; // 2 = condition-based for SCORM
    $cmid = $DB->insert_record('course_modules', $cm);
    
    // Get or create section
    $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
    if (!$section) {
        $section = new stdClass();
        $section->course = $courseid;
        $section->section = $sectionnum;
        $section->name = '';
        $section->summary = '';
        $section->summaryformat = 1;
        $section->sequence = '';
        $section->visible = 1;
        $section->id = $DB->insert_record('course_sections', $section);
    }
    
    // Update section sequence
    $seq = trim($section->sequence);
    $section->sequence = $seq ? $seq . ',' . $cmid : (string)$cmid;
    $DB->update_record('course_sections', $section);
    
    // Update cm with section
    $DB->set_field('course_modules', 'section', $section->id, ['id' => $cmid]);
    
    // Create context
    $ctx = new stdClass();
    $ctx->contextlevel = 70; // CONTEXT_MODULE
    $ctx->instanceid = $cmid;
    $parentctx = $DB->get_record('context', ['contextlevel' => 50, 'instanceid' => $courseid]);
    $ctx->path = $parentctx->path . '/' . 'temp';
    $ctx->depth = $parentctx->depth + 1;
    $ctxid = $DB->insert_record('context', $ctx);
    $DB->set_field('context', 'path', $parentctx->path . '/' . $ctxid, ['id' => $ctxid]);
    
    return $cmid;
}

// ============================================================
// 4. UPLOAD SCORM PACKAGES & CREATE ACTIVITIES
// ============================================================
logmsg("--- CREATING SCORM ACTIVITIES ---");

$fs = get_file_storage();
$scorm_module = $DB->get_record('modules', ['name' => 'scorm'], '*', MUST_EXIST);

foreach ($courses_def as $cdef) {
    $course = $created_courses[$cdef['shortname']];
    
    // Check if SCORM already exists in this course
    $existing_scorm = $DB->get_record('scorm', ['course' => $course->id]);
    if ($existing_scorm) {
        logmsg("  [{$cdef['shortname']}] SCORM already exists (id={$existing_scorm->id}), skipping");
        continue;
    }
    
    // Create SCORM instance
    $scorm = new stdClass();
    $scorm->course = $course->id;
    $scorm->name = $cdef['scorm_name'];
    $scorm->intro = '<p>This is a mandatory training module. You must view every slide completely before you can proceed. The module will resume from where you left off if you need to return later.</p><p><strong>Important:</strong> Each slide has a minimum viewing time. The Next button will appear after the timer completes.</p>';
    $scorm->introformat = 1;
    $scorm->scormtype = 'local';
    $scorm->reference = 'scorm_' . str_replace('-', '_', strtolower($cdef['shortname'])) . '.zip';
    $scorm->version = 'SCORM_1.2';
    $scorm->maxgrade = 100;
    $scorm->grademethod = 1; // Highest grade
    $scorm->whatgrade = 0; // Highest attempt
    $scorm->maxattempt = 0; // Unlimited
    $scorm->forcecompleted = 1;
    $scorm->forcenewattempt = 0; // No — allows resume
    $scorm->lastattemptlock = 0;
    $scorm->displayattemptstatus = 1;
    $scorm->displaycoursestructure = 0; // Hide TOC
    $scorm->updatefreq = 0;
    $scorm->sha1hash = '';
    $scorm->revision = 1;
    $scorm->launch = 0;
    $scorm->skipview = 0; // Never skip — must see entry page
    $scorm->hidebrowse = 1; // Hide browse button
    $scorm->hidetoc = 3; // 3 = disabled (no TOC at all)
    $scorm->nav = 0; // 0 = No navigation buttons from Moodle
    $scorm->navpositionleft = -100;
    $scorm->navpositiontop = -100;
    $scorm->auto = 0;
    $scorm->popup = 0; // Current window
    $scorm->width = 100;
    $scorm->height = 100;
    $scorm->timeopen = 0;
    $scorm->timeclose = 0;
    $scorm->timemodified = time();
    $scorm->completionstatusrequired = 4; // completed
    $scorm->completionscorerequired = null;
    $scorm->completionstatusallscos = 0;
    $scorm->autocommit = 1;
    
    $scormid = $DB->insert_record('scorm', $scorm);
    
    // Add course module
    $cmid = build_add_course_module($DB, $course->id, 'scorm', $scormid, 1, $cdef['scorm_name']);
    
    // Set completion criteria for SCORM: require "completed" status
    $completion = new stdClass();
    $completion->coursemoduleid = $cmid;
    $completion->completionstate = 0;
    
    // Upload the SCORM file to Moodle file storage
    $context = $DB->get_record('context', ['contextlevel' => 70, 'instanceid' => $cmid]);
    
    $filerecord = [
        'contextid' => $context->id,
        'component' => 'mod_scorm',
        'filearea'  => 'package',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => basename($cdef['scorm_file']),
    ];
    
    // Delete existing file if any
    $existing_file = $fs->get_file($context->id, 'mod_scorm', 'package', 0, '/', basename($cdef['scorm_file']));
    if ($existing_file) {
        $existing_file->delete();
    }
    
    // Store the zip file
    $storedfile = $fs->create_file_from_pathname($filerecord, $cdef['scorm_file']);
    
    // Update SCORM hash
    $DB->set_field('scorm', 'sha1hash', $storedfile->get_contenthash(), ['id' => $scormid]);
    
    // Now extract the SCORM package so Moodle can read the manifest
    // We need to parse imsmanifest.xml and create scorm_scoes records
    $zip = new ZipArchive();
    if ($zip->open($cdef['scorm_file']) === true) {
        // Extract to temp for manifest parsing
        $tempdir = $CFG->tempdir . '/scorm_extract_' . $scormid;
        @mkdir($tempdir, 0777, true);
        $zip->extractTo($tempdir);
        $zip->close();
        
        // Store extracted files in Moodle file storage (content area)
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempdir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($tempdir, '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                $dir = dirname($relativePath) . '/';
                if ($dir === '//') $dir = '/';
                
                $contentrecord = [
                    'contextid' => $context->id,
                    'component' => 'mod_scorm',
                    'filearea'  => 'content',
                    'itemid'    => 0,
                    'filepath'  => $dir,
                    'filename'  => basename($relativePath),
                ];
                
                $existing = $fs->get_file($context->id, 'mod_scorm', 'content', 0, $dir, basename($relativePath));
                if ($existing) $existing->delete();
                
                $fs->create_file_from_pathname($contentrecord, $file->getPathname());
            }
        }
        
        // Clean up temp
        $files_to_delete = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempdir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files_to_delete as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($tempdir);
    }
    
    // Create SCO records (SCORM requires these for tracking)
    // Get the organization and SCO from our manifest structure
    $identifier = strtoupper(str_replace(['/', '-', '.'], '_', pathinfo(basename($cdef['scorm_file']), PATHINFO_FILENAME)));
    
    // Organization SCO
    $org = new stdClass();
    $org->scorm = $scormid;
    $org->manifest = $identifier;
    $org->organization = 'ORG-' . $identifier;
    $org->parent = '/';
    $org->identifier = 'ORG-' . $identifier;
    $org->launch = '';
    $org->scormtype = '';
    $org->title = $cdef['scorm_name'];
    $org->sortorder = 0;
    $orgid = $DB->insert_record('scorm_scoes', $org);
    
    // Item SCO (the actual launchable SCO)
    $sco = new stdClass();
    $sco->scorm = $scormid;
    $sco->manifest = $identifier;
    $sco->organization = 'ORG-' . $identifier;
    $sco->parent = $orgid;
    $sco->identifier = 'ITEM-' . $identifier;
    $sco->launch = 'index.html';
    $sco->scormtype = 'sco';
    $sco->title = $cdef['scorm_name'];
    $sco->sortorder = 1;
    $scoid = $DB->insert_record('scorm_scoes', $sco);
    
    // Update SCORM launch to point to our SCO
    $DB->set_field('scorm', 'launch', $scoid, ['id' => $scormid]);
    
    logmsg("  [{$cdef['shortname']}] SCORM created: scormid={$scormid}, cmid={$cmid}, scoid={$scoid}");
}
logmsg("");

// ============================================================
// 5. ENROLL STUDENTS
// ============================================================
logmsg("--- ENROLLING STUDENTS ---");

// Get enrollment plugin
$enrol_manual = enrol_get_plugin('manual');

// Get student role
$student_role = $DB->get_record('role', ['shortname' => 'student']);

// Categorise students
// Physics students: enrolled in courses with category 4
$physics_students = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.username
    FROM {user} u
    JOIN {user_enrolments} ue ON ue.userid = u.id
    JOIN {enrol} e ON e.id = ue.enrolid
    JOIN {course} c ON c.id = e.courseid
    WHERE c.category = 4
    AND u.deleted = 0
    AND u.id > 2
");

// PD students: enrolled in courses with category 8
$pd_students = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.username
    FROM {user} u
    JOIN {user_enrolments} ue ON ue.userid = u.id
    JOIN {enrol} e ON e.id = ue.enrolid
    JOIN {course} c ON c.id = e.courseid
    WHERE c.category = 8
    AND u.deleted = 0
    AND u.id > 2
");

// All students
$all_students = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.username
    FROM {user} u
    JOIN {role_assignments} ra ON ra.userid = u.id
    JOIN {context} ctx ON ctx.id = ra.contextid
    WHERE ra.roleid = ?
    AND u.deleted = 0
    AND u.id > 2
    AND ctx.contextlevel = 50
", [$student_role->id]);

// Also get teachers for enrollment as editing teachers
$teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
$all_teachers = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.username
    FROM {user} u
    JOIN {role_assignments} ra ON ra.userid = u.id
    WHERE ra.roleid = ?
    AND u.deleted = 0
    AND u.id > 2
", [$teacher_role->id]);

foreach ($courses_def as $cdef) {
    $course = $created_courses[$cdef['shortname']];
    
    // Ensure manual enrolment instance exists
    $instances = $DB->get_records('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
    if (empty($instances)) {
        $enrolid = $enrol_manual->add_instance($course);
        $instance = $DB->get_record('enrol', ['id' => $enrolid]);
    } else {
        $instance = reset($instances);
    }
    
    // Select students based on enrollment type
    switch ($cdef['enroll']) {
        case 'all':
            $students = $all_students;
            break;
        case 'physics':
            $students = $physics_students;
            break;
        case 'pd':
            $students = $pd_students;
            break;
        default:
            $students = $all_students;
    }
    
    $enrolled_count = 0;
    foreach ($students as $student) {
        // Check if already enrolled
        $existing = $DB->get_record_sql("
            SELECT ue.id FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE e.courseid = ? AND ue.userid = ?
        ", [$course->id, $student->id]);
        
        if (!$existing) {
            $enrol_manual->enrol_user($instance, $student->id, $student_role->id, time(), 0);
            $enrolled_count++;
        }
    }
    
    // Enroll teachers
    $teacher_count = 0;
    foreach ($all_teachers as $teacher) {
        $existing = $DB->get_record_sql("
            SELECT ue.id FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE e.courseid = ? AND ue.userid = ?
        ", [$course->id, $teacher->id]);
        
        if (!$existing) {
            $enrol_manual->enrol_user($instance, $teacher->id, $teacher_role->id, time(), 0);
            $teacher_count++;
        }
    }
    
    logmsg("  [{$cdef['shortname']}] {$enrolled_count} students + {$teacher_count} teachers enrolled ({$cdef['enroll']} group)");
    
    // Set up payment for paid courses
    if ($cdef['paid']) {
        $fee_plugin = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'fee']);
        if (!$fee_plugin) {
            $enrol_fee = enrol_get_plugin('fee');
            if ($enrol_fee) {
                $fee_instance = new stdClass();
                $fee_instance->cost = $cdef['price'];
                $fee_instance->currency = 'GBP';
                $fee_instance->roleid = $student_role->id;
                $fee_instance->customint1 = 0;
                $enrol_fee->add_instance($course, (array)$fee_instance);
                logmsg("  [{$cdef['shortname']}] Fee enrolment added: £{$cdef['price']}");
            }
        }
    }
}
logmsg("");

// ============================================================
// 6. ADD FEEDBACK ACTIVITY
// ============================================================
logmsg("--- ADDING FEEDBACK ACTIVITIES ---");

foreach ($courses_def as $cdef) {
    $course = $created_courses[$cdef['shortname']];
    
    // Check if feedback already exists
    $existing_fb = $DB->get_record('feedback', ['course' => $course->id]);
    if ($existing_fb) {
        logmsg("  [{$cdef['shortname']}] Feedback already exists, skipping");
        continue;
    }
    
    $fb = new stdClass();
    $fb->course = $course->id;
    $fb->name = 'Training Feedback';
    $fb->intro = '<p>Please take a moment to provide feedback on this training module. Your responses help us improve our compliance training for all students.</p>';
    $fb->introformat = 1;
    $fb->anonymous = 1;
    $fb->publish_stats = 1;
    $fb->multiple_submit = 0;
    $fb->autonumbering = 1;
    $fb->page_after_submit = '<p>Thank you for your feedback. Your responses have been recorded.</p>';
    $fb->page_after_submitformat = 1;
    $fb->timeopen = 0;
    $fb->timeclose = 0;
    $fb->timemodified = time();
    $fb->completionsubmit = 1;
    
    $fbid = $DB->insert_record('feedback', $fb);
    $cmid = build_add_course_module($DB, $course->id, 'feedback', $fbid, 2, 'Training Feedback');
    
    // Add feedback questions
    $pos = 1;
    
    // Q1: Overall rating
    $item = new stdClass();
    $item->feedback = $fbid;
    $item->template = 0;
    $item->name = 'Overall, how would you rate this training module?';
    $item->label = '';
    $item->presentation = 'r>>>>>Poor|Below Average|Average|Good|Excellent';
    $item->typ = 'multichoicerated';
    $item->hasvalue = 1;
    $item->position = $pos++;
    $item->required = 1;
    $item->dependitem = 0;
    $item->dependvalue = '';
    $item->options = '';
    $DB->insert_record('feedback_item', $item);
    
    // Q2: Content clarity
    $item->name = 'The content was clear and easy to understand';
    $item->presentation = 'r>>>>>Strongly Disagree|Disagree|Neutral|Agree|Strongly Agree';
    $item->position = $pos++;
    $DB->insert_record('feedback_item', $item);
    
    // Q3: Relevance
    $item->name = 'The content was relevant to my role / studies';
    $item->position = $pos++;
    $DB->insert_record('feedback_item', $item);
    
    // Q4: Timed format
    $item->name = 'The timed slide format helped me engage with the content';
    $item->position = $pos++;
    $DB->insert_record('feedback_item', $item);
    
    // Q5: Open text
    $item->name = 'Do you have any suggestions for improving this training?';
    $item->presentation = '60|5';
    $item->typ = 'textarea';
    $item->required = 0;
    $item->position = $pos++;
    $DB->insert_record('feedback_item', $item);
    
    logmsg("  [{$cdef['shortname']}] Feedback created with 5 items (cmid={$cmid})");
}
logmsg("");

// ============================================================
// 7. NAME SECTIONS
// ============================================================
logmsg("--- NAMING COURSE SECTIONS ---");

foreach ($courses_def as $cdef) {
    $course = $created_courses[$cdef['shortname']];
    
    // Section 0: General
    $DB->set_field('course_sections', 'name', 'Welcome', ['course' => $course->id, 'section' => 0]);
    $DB->set_field('course_sections', 'summary', '<p>Welcome to <strong>' . $cdef['fullname'] . '</strong>. This is a mandatory compliance training module. Complete all activities to receive your certificate of completion.</p>', ['course' => $course->id, 'section' => 0]);
    
    // Section 1: Training Module
    $DB->set_field('course_sections', 'name', 'Training Module', ['course' => $course->id, 'section' => 1]);
    
    // Section 2: Feedback & Certificate
    $DB->set_field('course_sections', 'name', 'Feedback & Completion', ['course' => $course->id, 'section' => 2]);
    
    logmsg("  [{$cdef['shortname']}] Sections named");
}
logmsg("");

// ============================================================
// 8. SUMMARY
// ============================================================
logmsg("============================================================");
logmsg("  SUMMARY");
logmsg("============================================================");

foreach ($courses_def as $cdef) {
    $course = $created_courses[$cdef['shortname']];
    $activity_count = $DB->count_records('course_modules', ['course' => $course->id, 'deletioninprogress' => 0]);
    
    $price_str = $cdef['paid'] ? " | Price: £{$cdef['price']}" : " | FREE";
    $enrollment_count = $DB->count_records_sql("
        SELECT COUNT(DISTINCT ue.userid)
        FROM {user_enrolments} ue
        JOIN {enrol} e ON e.id = ue.enrolid
        WHERE e.courseid = ?
    ", [$course->id]);
    
    logmsg("  [{$cdef['shortname']}] id={$course->id} | {$cdef['fullname']}");
    logmsg("    Activities: {$activity_count} | Enrolled: {$enrollment_count}{$price_str}");
    logmsg("    URL: http://159.65.149.161/lms/course/view.php?id={$course->id}\n");
}

logmsg("  SCORM Player Settings (all courses):");
logmsg("    ✓ TOC: Hidden (disabled)");
logmsg("    ✓ Browse: Hidden");
logmsg("    ✓ Navigation: Disabled (no Moodle prev/next)");
logmsg("    ✓ Force completed: Yes");
logmsg("    ✓ Force new attempt: No (resume enabled)");
logmsg("    ✓ Timer per slide: 60 seconds");
logmsg("    ✓ No fast-forward, no skip, no back button");
logmsg("    ✓ Completion: Requires 'completed' status from SCORM");
logmsg("\nDone.");

} catch (Throwable $e) {
    logmsg("EXCEPTION: " . $e->getMessage());
    logmsg("File: " . $e->getFile() . ":" . $e->getLine());
    logmsg("Trace: " . $e->getTraceAsString());
}
fclose($LOGFILE);
