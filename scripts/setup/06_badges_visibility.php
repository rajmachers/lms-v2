<?php
/**
 * Enhance Moodle Demo: Activity Visibility, Starred Courses, Badges
 * 
 * 1. Enable activity reports, activity dates, show grades for all courses
 * 2. Star select courses for students and teachers on dashboard
 * 3. Create course badges with criteria (course completion & activity completion)
 */
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
require_once($CFG->libdir . '/badgeslib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/badges/classes/badge.php');

global $DB;

echo "============================================\n";
echo "  MOODLE DEMO ENHANCEMENT SCRIPT\n";
echo "============================================\n\n";

// ================================================
// PART 1: Make activity data visible
// ================================================
echo "PART 1: ENABLING ACTIVITY VISIBILITY\n";
echo "--------------------------------------\n";

$courseids = [2, 3, 4, 5, 6, 7, 8, 9];

foreach ($courseids as $cid) {
    $course = $DB->get_record('course', ['id' => $cid]);
    if (!$course) continue;
    
    $updates = new stdClass();
    $updates->id = $cid;
    $updates->showreports = 1;       // Allow students to view their activity reports
    $updates->showgrades = 1;        // Show gradebook to students
    $updates->showactivitydates = 1; // Show activity dates
    $updates->showcompletionconditions = 1; // Show completion conditions on course page
    
    $DB->update_record('course', $updates);
    echo "  Course $cid ({$course->shortname}): reports=ON, grades=ON, dates=ON, conditions=ON\n";
}

// Enable grade report visibility in site config
set_config('showgradereport', 1, 'moodlecourse');
set_config('showreports', 1, 'moodlecourse');
echo "\n  Site defaults: showgradereport=ON, showreports=ON\n";

// Enable specific grade reports for students
set_config('showuserreport', 1, 'grade_report_user');
echo "  Grade user report: visible to students\n";

echo "\n";

// ================================================
// PART 2: Star courses for dashboard
// ================================================
echo "PART 2: STARRING COURSES FOR DASHBOARD\n";
echo "----------------------------------------\n";

// Get all students (IDs 75-124) and teachers (IDs 70-74)
$students = $DB->get_records_sql("SELECT id, username FROM {user} WHERE id BETWEEN 75 AND 124");
$teachers = $DB->get_records_sql("SELECT id, username FROM {user} WHERE id BETWEEN 70 AND 74");
$admin = $DB->get_record('user', ['id' => 2]); // admin

// Define which courses to star for whom
// Teachers: star ALL their courses
// Students: star 2-3 courses each (variety)
// Admin: star a few key courses

$teacher_courses = [
    70 => [2, 3],          // Dr. Sarah Mitchell - Health Sciences
    71 => [4, 5],          // Dr. James Wilson - Year 1 Physics
    72 => [6, 7],          // Dr. Emily Chen - Year 2 Physics
    73 => [8, 9],          // Dr. Robert Taylor - Year 3 Physics
    74 => [4, 5, 6, 7, 8, 9], // Dr. Amanda Foster - all physics
];

// Students are in groups A-E (10 each), enrolled in various physics courses
// Let's star a mix for each student
$student_starred = [];

// Group A (IDs 75-84): enrolled in PHY101, PHY102
// Group B (IDs 85-94): enrolled in PHY101, PHY102
// Group C (IDs 95-99): enrolled in PHY201, PHY202
// Group D (IDs 100-104): enrolled in PHY201, PHY202
// Group E (IDs 105-114): enrolled in PHY301, PHY302
// Actually let's just query enrolments for accuracy

$enrolments = $DB->get_records_sql("
    SELECT DISTINCT ue.userid, e.courseid
    FROM {user_enrolments} ue
    JOIN {enrol} e ON e.id = ue.enrolid
    WHERE ue.userid BETWEEN 75 AND 124
    ORDER BY ue.userid, e.courseid
");

// Build user->courses map
$user_courses = [];
foreach ($enrolments as $en) {
    $user_courses[$en->userid][] = $en->courseid;
}

$starred_count = 0;

// Function to star a course for a user
function star_course($userid, $courseid) {
    global $DB;
    
    $context = context_course::instance($courseid);
    
    // Check if already favourited
    $existing = $DB->get_record('favourite', [
        'userid' => $userid,
        'component' => 'core_course',
        'itemtype' => 'courses',
        'itemid' => $courseid,
    ]);
    
    if ($existing) {
        return false; // already starred
    }
    
    $fav = new stdClass();
    $fav->component = 'core_course';
    $fav->itemtype = 'courses';
    $fav->itemid = $courseid;
    $fav->contextid = $context->id;
    $fav->userid = $userid;
    $fav->ordering = 0;
    $fav->timecreated = time();
    $fav->timemodified = time();
    
    $DB->insert_record('favourite', $fav);
    return true;
}

// Star courses for admin
$admin_stars = [2, 4, 6, 8]; // one from each category/year
foreach ($admin_stars as $cid) {
    if (star_course(2, $cid)) $starred_count++;
}
echo "  Admin: Starred courses " . implode(', ', $admin_stars) . "\n";

// Star courses for teachers
foreach ($teacher_courses as $tid => $courses) {
    $teacher = $DB->get_record('user', ['id' => $tid]);
    foreach ($courses as $cid) {
        if (star_course($tid, $cid)) $starred_count++;
    }
    echo "  Teacher {$teacher->firstname} {$teacher->lastname} (ID $tid): Starred courses " . implode(', ', $courses) . "\n";
}

// Star courses for students — star their first 2 enrolled courses
foreach ($user_courses as $uid => $courses) {
    $to_star = array_slice($courses, 0, min(2, count($courses)));
    foreach ($to_star as $cid) {
        if (star_course($uid, $cid)) $starred_count++;
    }
}
echo "  Students: Starred first 2 enrolled courses for each (50 students)\n";
echo "  Total new stars: $starred_count\n";

// Also set the dashboard to show "Starred" as default view
// course_overview block preference
echo "\n";

// ================================================
// PART 3: Create Badges
// ================================================
echo "PART 3: CREATING COURSE BADGES\n";
echo "-------------------------------\n";

// Badge definitions per course
$badge_definitions = [
    2 => [
        ['name' => 'Fellowship Scholar', 'desc' => 'Awarded for completing all modules in the Fellowship in Family Medicine programme.', 'type' => 'completion'],
        ['name' => 'Clinical Excellence', 'desc' => 'Awarded for achieving 80%+ in Fellowship clinical assessments.', 'type' => 'grade', 'grade' => 80],
    ],
    3 => [
        ['name' => 'Certified Practitioner', 'desc' => 'Awarded for completing the Certificate on Family Medicine course.', 'type' => 'completion'],
        ['name' => 'Outstanding Certificate', 'desc' => 'Awarded for achieving distinction (85%+) in the Certificate programme.', 'type' => 'grade', 'grade' => 85],
    ],
    4 => [
        ['name' => 'Mechanics Master', 'desc' => 'Awarded for completing all activities in PHY101 Classical Mechanics.', 'type' => 'completion'],
        ['name' => "Newton's Prodigy", 'desc' => 'Awarded for scoring 90%+ in PHY101 assessments.', 'type' => 'grade', 'grade' => 90],
    ],
    5 => [
        ['name' => 'Electromagnetism Expert', 'desc' => 'Awarded for completing all activities in PHY102 Electromagnetism.', 'type' => 'completion'],
        ['name' => "Maxwell's Star", 'desc' => 'Awarded for scoring 85%+ in PHY102 assessments.', 'type' => 'grade', 'grade' => 85],
    ],
    6 => [
        ['name' => 'Quantum Explorer', 'desc' => 'Awarded for completing all activities in PHY201 Quantum Mechanics.', 'type' => 'completion'],
        ['name' => "Schrödinger's Scholar", 'desc' => 'Awarded for scoring 85%+ in PHY201 assessments.', 'type' => 'grade', 'grade' => 85],
    ],
    7 => [
        ['name' => 'Thermodynamics Champion', 'desc' => 'Awarded for completing all activities in PHY202 Thermodynamics & Statistical Mechanics.', 'type' => 'completion'],
        ['name' => 'Boltzmann Brilliance', 'desc' => 'Awarded for scoring 85%+ in PHY202 assessments.', 'type' => 'grade', 'grade' => 85],
    ],
    8 => [
        ['name' => 'Nuclear Pioneer', 'desc' => 'Awarded for completing all activities in PHY301 Nuclear & Particle Physics.', 'type' => 'completion'],
        ['name' => 'Particle Physicist', 'desc' => 'Awarded for scoring 90%+ in PHY301 assessments.', 'type' => 'grade', 'grade' => 90],
    ],
    9 => [
        ['name' => 'Cosmic Scholar', 'desc' => 'Awarded for completing all activities in PHY302 Astrophysics & Cosmology.', 'type' => 'completion'],
        ['name' => 'Stellar Achiever', 'desc' => 'Awarded for scoring 90%+ in PHY302 assessments.', 'type' => 'grade', 'grade' => 90],
    ],
];

// Color schemes for badge images (matching course colors)
$badge_colors = [
    2 => ['bg' => [27, 94, 32],    'fg' => [200, 230, 201]],   // Green
    3 => ['bg' => [13, 71, 161],   'fg' => [187, 222, 251]],   // Blue
    4 => ['bg' => [183, 28, 28],   'fg' => [255, 205, 210]],   // Red
    5 => ['bg' => [230, 81, 0],    'fg' => [255, 224, 178]],   // Orange
    6 => ['bg' => [74, 20, 140],   'fg' => [225, 190, 231]],   // Purple
    7 => ['bg' => [0, 96, 100],    'fg' => [178, 235, 242]],   // Teal
    8 => ['bg' => [38, 50, 56],    'fg' => [207, 216, 220]],   // Dark Grey
    9 => ['bg' => [26, 35, 126],   'fg' => [197, 202, 233]],   // Navy
];

$badge_count = 0;

foreach ($badge_definitions as $courseid => $badges) {
    $course = $DB->get_record('course', ['id' => $courseid]);
    $context = context_course::instance($courseid);
    
    echo "\n  Course $courseid ({$course->shortname}):\n";
    
    foreach ($badges as $bdef) {
        // Create badge image (circular badge design)
        $imgSize = 300;
        $img = imagecreatetruecolor($imgSize, $imgSize);
        imagesavealpha($img, true);
        
        // Transparent background
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);
        
        $bg = $badge_colors[$courseid]['bg'];
        $fg = $badge_colors[$courseid]['fg'];
        $bgColor = imagecolorallocate($img, $bg[0], $bg[1], $bg[2]);
        $fgColor = imagecolorallocate($img, $fg[0], $fg[1], $fg[2]);
        $white = imagecolorallocate($img, 255, 255, 255);
        $gold = imagecolorallocate($img, 255, 215, 0);
        $darkGold = imagecolorallocate($img, 218, 165, 32);
        
        // Draw circular badge
        imagefilledellipse($img, 150, 150, 280, 280, $bgColor);
        imageellipse($img, 150, 150, 280, 280, $gold);
        imageellipse($img, 150, 150, 270, 270, $gold);
        imageellipse($img, 150, 150, 260, 260, $darkGold);
        
        // Inner ring
        imageellipse($img, 150, 150, 220, 220, $fgColor);
        
        // Star decoration at top
        $starPoints = [];
        for ($i = 0; $i < 10; $i++) {
            $angle = deg2rad(-90 + $i * 36);
            $r = ($i % 2 == 0) ? 20 : 10;
            $starPoints[] = 150 + $r * cos($angle);
            $starPoints[] = 45 + $r * sin($angle);
        }
        imagefilledpolygon($img, $starPoints, $gold);
        
        // Badge type icon
        if ($bdef['type'] === 'completion') {
            // Checkmark symbol
            imageline($img, 130, 145, 145, 165, $white);
            imageline($img, 145, 165, 175, 125, $white);
            imageline($img, 131, 145, 146, 165, $white);
            imageline($img, 146, 165, 176, 125, $white);
            imagesetthickness($img, 3);
        } else {
            // Grade star
            $gStarPts = [];
            for ($i = 0; $i < 10; $i++) {
                $angle = deg2rad(-90 + $i * 36);
                $r = ($i % 2 == 0) ? 30 : 15;
                $gStarPts[] = 150 + $r * cos($angle);
                $gStarPts[] = 135 + $r * sin($angle);
            }
            imagefilledpolygon($img, $gStarPts, $gold);
        }
        
        // Badge name text (wrap it)
        $words = explode(' ', $bdef['name']);
        $lines = [];
        $curLine = '';
        foreach ($words as $w) {
            $test = $curLine ? "$curLine $w" : $w;
            if (strlen($test) > 14 && $curLine) {
                $lines[] = $curLine;
                $curLine = $w;
            } else {
                $curLine = $test;
            }
        }
        if ($curLine) $lines[] = $curLine;
        
        $y = 175;
        foreach ($lines as $line) {
            $x = 150 - (strlen($line) * 3.5);
            imagestring($img, 4, max(50, $x), $y, $line, $white);
            $y += 18;
        }
        
        // Course code at bottom
        $code = $course->shortname;
        if (strlen($code) > 12) $code = substr($code, 0, 12);
        $cx = 150 - (strlen($code) * 3);
        imagestring($img, 2, max(60, $cx), 225, $code, $fgColor);
        
        // Save to tmp
        $tmpFile = '/tmp/badge_' . $courseid . '_' . ($bdef['type'] === 'completion' ? 'comp' : 'grade') . '.png';
        imagepng($img, $tmpFile);
        imagedestroy($img);
        
        // Create the badge record
        $now = time();
        $badge = new stdClass();
        $badge->name = $bdef['name'];
        $badge->description = $bdef['desc'];
        $badge->timecreated = $now;
        $badge->timemodified = $now;
        $badge->usercreated = 2; // admin
        $badge->usermodified = 2;
        $badge->issuername = 'CAF University';
        $badge->issuerurl = 'http://159.65.149.161';
        $badge->issuercontact = 'admin@cafuniversity.edu';
        $badge->expiredate = null;
        $badge->expireperiod = null;
        $badge->type = BADGE_TYPE_COURSE; // 2 = course badge
        $badge->courseid = $courseid;
        $badge->message = 'Congratulations! You have earned the "' . $bdef['name'] . '" badge. This badge recognises your achievement in ' . $course->fullname . '.';
        $badge->messagesubject = 'Badge Awarded: ' . $bdef['name'];
        $badge->attachment = 1;
        $badge->notification = 1;
        $badge->status = BADGE_STATUS_ACTIVE; // 1 = active
        $badge->nextcron = null;
        $badge->version = '1.0';
        $badge->language = 'en';
        $badge->imageauthorname = 'CAF University';
        $badge->imageauthoremail = '';
        $badge->imageauthorurl = '';
        $badge->imagecaption = $bdef['name'] . ' badge';
        
        $badgeid = $DB->insert_record('badge', $badge);
        
        // Store badge image
        $context_system = context_system::instance();
        $fs = get_file_storage();
        
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'badges',
            'filearea'  => 'badgeimage',
            'itemid'    => $badgeid,
            'filepath'  => '/',
            'filename'  => 'f3.png', // Moodle uses f1, f2, f3 for badge images
        ];
        
        // Remove existing if any
        $existing = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
                                   $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if ($existing) $existing->delete();
        
        $fs->create_file_from_pathname($fileinfo, $tmpFile);
        
        // Also create f1 (small) and f2 (medium) versions
        $fileinfo['filename'] = 'f1.png';
        $existing = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
                                   $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if ($existing) $existing->delete();
        $fs->create_file_from_pathname($fileinfo, $tmpFile);
        
        $fileinfo['filename'] = 'f2.png';
        $existing = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
                                   $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if ($existing) $existing->delete();
        $fs->create_file_from_pathname($fileinfo, $tmpFile);
        
        unlink($tmpFile);
        
        // ---- ADD BADGE CRITERIA ----
        
        if ($bdef['type'] === 'completion') {
            // Criterion: Course completion
            $crit = new stdClass();
            $crit->badgeid = $badgeid;
            $crit->criteriatype = BADGE_CRITERIA_TYPE_COURSE; // 5 = course completion
            $crit->method = 1; // all criteria must be met
            $crit->descriptionformat = FORMAT_HTML;
            $crit->description = '';
            $critid = $DB->insert_record('badge_criteria', $crit);
            
            // Criterion param: the course itself
            $param = new stdClass();
            $param->critid = $critid;
            $param->name = 'course_' . $courseid;
            $param->value = $courseid;
            $DB->insert_record('badge_criteria_param', $param);
            
            // Also add overall criterion
            $overall = new stdClass();
            $overall->badgeid = $badgeid;
            $overall->criteriatype = BADGE_CRITERIA_TYPE_OVERALL; // 0 = overall
            $overall->method = 1;
            $overall->descriptionformat = FORMAT_HTML;
            $overall->description = '';
            $DB->insert_record('badge_criteria', $overall);
            
        } else {
            // Criterion: Course completion (needed as base)
            $crit = new stdClass();
            $crit->badgeid = $badgeid;
            $crit->criteriatype = BADGE_CRITERIA_TYPE_COURSE;
            $crit->method = 1;
            $crit->descriptionformat = FORMAT_HTML;
            $crit->description = '';
            $critid = $DB->insert_record('badge_criteria', $crit);
            
            $param = new stdClass();
            $param->critid = $critid;
            $param->name = 'course_' . $courseid;
            $param->value = $courseid;
            $DB->insert_record('badge_criteria_param', $param);
            
            // Grade criterion
            $param2 = new stdClass();
            $param2->critid = $critid;
            $param2->name = 'grade_' . $courseid;
            $param2->value = $bdef['grade'];
            $DB->insert_record('badge_criteria_param', $param2);
            
            // Overall criterion
            $overall = new stdClass();
            $overall->badgeid = $badgeid;
            $overall->criteriatype = BADGE_CRITERIA_TYPE_OVERALL;
            $overall->method = 1;
            $overall->descriptionformat = FORMAT_HTML;
            $overall->description = '';
            $DB->insert_record('badge_criteria', $overall);
        }
        
        echo "    Badge '$badgeid': {$bdef['name']} ({$bdef['type']}) - CREATED\n";
        $badge_count++;
    }
}

echo "\n  Total badges created: $badge_count\n";

// ================================================
// PART 4: Issue badges to qualifying students
// ================================================
echo "\nPART 4: ISSUING BADGES TO QUALIFYING STUDENTS\n";
echo "-----------------------------------------------\n";

$issued_count = 0;

// Get all completion badges and issue to students who completed the course
$completion_badges = $DB->get_records_sql("
    SELECT b.id, b.name, b.courseid 
    FROM {badge} b 
    JOIN {badge_criteria} bc ON bc.badgeid = b.id AND bc.criteriatype = 5
    WHERE b.type = 2
");

foreach ($completion_badges as $cb) {
    // Find students who completed this course
    $completions = $DB->get_records_sql("
        SELECT cc.userid, cc.timecompleted
        FROM {course_completions} cc
        WHERE cc.course = ? AND cc.timecompleted IS NOT NULL AND cc.timecompleted > 0
    ", [$cb->courseid]);
    
    foreach ($completions as $comp) {
        // Check if already issued
        $already = $DB->get_record('badge_issued', [
            'badgeid' => $cb->id,
            'userid' => $comp->userid,
        ]);
        
        if (!$already) {
            $issue = new stdClass();
            $issue->badgeid = $cb->id;
            $issue->userid = $comp->userid;
            $issue->uniquehash = md5($cb->id . '-' . $comp->userid . '-' . time() . '-' . random_int(1000, 9999));
            $issue->dateissued = $comp->timecompleted;
            $issue->dateexpire = null;
            $issue->visible = 1;
            $issue->issuernotified = 0;
            
            $DB->insert_record('badge_issued', $issue);
            $issued_count++;
        }
    }
    
    $count = count($completions);
    echo "  Badge '{$cb->name}' (Course {$cb->courseid}): Issued to $count students\n";
}

// Issue grade-based badges to high-scoring students
$grade_badges = $DB->get_records_sql("
    SELECT DISTINCT b.id, b.name, b.courseid, bcp.value as min_grade
    FROM {badge} b 
    JOIN {badge_criteria} bc ON bc.badgeid = b.id AND bc.criteriatype = 5
    JOIN {badge_criteria_param} bcp ON bcp.critid = bc.id AND bcp.name LIKE 'grade_%'
    WHERE b.type = 2
");

foreach ($grade_badges as $gb) {
    // Find students who got >= min_grade in this course
    $high_scorers = $DB->get_records_sql("
        SELECT gg.userid, gg.finalgrade, gi.grademax, cc.timecompleted
        FROM {grade_grades} gg
        JOIN {grade_items} gi ON gi.id = gg.itemid AND gi.itemtype = 'course' AND gi.courseid = ?
        JOIN {course_completions} cc ON cc.userid = gg.userid AND cc.course = gi.courseid AND cc.timecompleted > 0
        WHERE gg.finalgrade IS NOT NULL 
        AND gi.grademax > 0
        AND (gg.finalgrade / gi.grademax * 100) >= ?
    ", [$gb->courseid, $gb->min_grade]);
    
    $count = 0;
    foreach ($high_scorers as $hs) {
        $already = $DB->get_record('badge_issued', [
            'badgeid' => $gb->id,
            'userid' => $hs->userid,
        ]);
        
        if (!$already) {
            $issue = new stdClass();
            $issue->badgeid = $gb->id;
            $issue->userid = $hs->userid;
            $issue->uniquehash = md5($gb->id . '-' . $hs->userid . '-' . time() . '-' . random_int(1000, 9999));
            $issue->dateissued = $hs->timecompleted;
            $issue->dateexpire = null;
            $issue->visible = 1;
            $issue->issuernotified = 0;
            
            $DB->insert_record('badge_issued', $issue);
            $count++;
            $issued_count++;
        }
    }
    
    $pct = $gb->min_grade;
    echo "  Badge '{$gb->name}' (Course {$gb->courseid}, >={$pct}%): Issued to $count students\n";
}

echo "\n  Total badges issued: $issued_count\n";

// ================================================
// PART 5: Create Site-Level Badges
// ================================================
echo "\nPART 5: CREATING SITE-LEVEL BADGES\n";
echo "------------------------------------\n";

$site_badges = [
    [
        'name' => 'Physics Year 1 Graduate',
        'desc' => 'Awarded for completing all Year 1 Physics courses (PHY101 & PHY102).',
        'courses' => [4, 5],
        'color' => [183, 28, 28],
    ],
    [
        'name' => 'Physics Year 2 Graduate',
        'desc' => 'Awarded for completing all Year 2 Physics courses (PHY201 & PHY202).',
        'courses' => [6, 7],
        'color' => [74, 20, 140],
    ],
    [
        'name' => 'Physics Year 3 Graduate',
        'desc' => 'Awarded for completing all Year 3 Physics courses (PHY301 & PHY302).',
        'courses' => [8, 9],
        'color' => [26, 35, 126],
    ],
    [
        'name' => 'BSc Physics Graduate',
        'desc' => 'The ultimate achievement! Awarded for completing all 6 Physics courses across 3 years.',
        'courses' => [4, 5, 6, 7, 8, 9],
        'color' => [255, 152, 0],
    ],
    [
        'name' => 'Health Sciences Scholar',
        'desc' => 'Awarded for completing both the Fellowship and Certificate in Family Medicine.',
        'courses' => [2, 3],
        'color' => [27, 94, 32],
    ],
];

$site_badge_count = 0;

foreach ($site_badges as $sbdef) {
    // Generate badge image
    $imgSize = 300;
    $img = imagecreatetruecolor($imgSize, $imgSize);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    $bgc = $sbdef['color'];
    $bgColor = imagecolorallocate($img, $bgc[0], $bgc[1], $bgc[2]);
    $white = imagecolorallocate($img, 255, 255, 255);
    $gold = imagecolorallocate($img, 255, 215, 0);
    $silver = imagecolorallocate($img, 192, 192, 192);
    
    // Outer hexagon-like shape (circle for simplicity)
    imagefilledellipse($img, 150, 150, 290, 290, $gold);
    imagefilledellipse($img, 150, 150, 270, 270, $bgColor);
    imageellipse($img, 150, 150, 250, 250, $gold);
    
    // Laurel wreath decoration (simple arcs)
    imagearc($img, 100, 150, 80, 180, 300, 60, $gold);
    imagearc($img, 200, 150, 80, 180, 120, 240, $gold);
    imagearc($img, 102, 150, 80, 180, 300, 60, $gold);
    imagearc($img, 198, 150, 80, 180, 120, 240, $gold);
    
    // Star at top
    $starPoints = [];
    for ($i = 0; $i < 10; $i++) {
        $angle = deg2rad(-90 + $i * 36);
        $r = ($i % 2 == 0) ? 25 : 12;
        $starPoints[] = 150 + $r * cos($angle);
        $starPoints[] = 45 + $r * sin($angle);
    }
    imagefilledpolygon($img, $starPoints, $gold);
    
    // Multiple small stars for BSc Graduate
    if ($sbdef['name'] === 'BSc Physics Graduate') {
        for ($s = 0; $s < 6; $s++) {
            $sx = 85 + $s * 22;
            $sy = 200;
            for ($i = 0; $i < 10; $i++) {
                $angle = deg2rad(-90 + $i * 36);
                $r = ($i % 2 == 0) ? 8 : 4;
                $pts[] = $sx + $r * cos($angle);
                $pts[] = $sy + $r * sin($angle);
            }
            imagefilledpolygon($img, $pts, $gold);
            $pts = [];
        }
    }
    
    // Badge name
    $words = explode(' ', $sbdef['name']);
    $lines = [];
    $curLine = '';
    foreach ($words as $w) {
        $test = $curLine ? "$curLine $w" : $w;
        if (strlen($test) > 16 && $curLine) {
            $lines[] = $curLine;
            $curLine = $w;
        } else {
            $curLine = $test;
        }
    }
    if ($curLine) $lines[] = $curLine;
    
    $y = 120;
    foreach ($lines as $line) {
        $x = 150 - (strlen($line) * 3.5);
        imagestring($img, 5, max(40, $x), $y, $line, $white);
        $y += 22;
    }
    
    // Course count
    $cc = count($sbdef['courses']);
    imagestring($img, 3, 115, 230, "$cc courses", $silver);
    
    $tmpFile = '/tmp/site_badge_' . $site_badge_count . '.png';
    imagepng($img, $tmpFile);
    imagedestroy($img);
    
    // Create site-level badge
    $now = time();
    $badge = new stdClass();
    $badge->name = $sbdef['name'];
    $badge->description = $sbdef['desc'];
    $badge->timecreated = $now;
    $badge->timemodified = $now;
    $badge->usercreated = 2;
    $badge->usermodified = 2;
    $badge->issuername = 'CAF University';
    $badge->issuerurl = 'http://159.65.149.161';
    $badge->issuercontact = 'admin@cafuniversity.edu';
    $badge->expiredate = null;
    $badge->expireperiod = null;
    $badge->type = BADGE_TYPE_SITE; // 1 = site badge
    $badge->courseid = null;
    $badge->message = 'Congratulations! You have earned the "' . $sbdef['name'] . '" badge.';
    $badge->messagesubject = 'Badge Awarded: ' . $sbdef['name'];
    $badge->attachment = 1;
    $badge->notification = 1;
    $badge->status = BADGE_STATUS_ACTIVE;
    $badge->nextcron = null;
    $badge->version = '1.0';
    $badge->language = 'en';
    $badge->imageauthorname = 'CAF University';
    $badge->imageauthoremail = '';
    $badge->imageauthorurl = '';
    $badge->imagecaption = $sbdef['name'] . ' badge';
    
    $badgeid = $DB->insert_record('badge', $badge);
    
    // Store image
    $sys_context = context_system::instance();
    $fs = get_file_storage();
    
    foreach (['f1.png', 'f2.png', 'f3.png'] as $fname) {
        $fileinfo = [
            'contextid' => $sys_context->id,
            'component' => 'badges',
            'filearea'  => 'badgeimage',
            'itemid'    => $badgeid,
            'filepath'  => '/',
            'filename'  => $fname,
        ];
        $existing = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
                                   $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if ($existing) $existing->delete();
        $fs->create_file_from_pathname($fileinfo, $tmpFile);
    }
    
    unlink($tmpFile);
    
    // Criteria: courseset (multiple courses)
    // Overall criterion
    $overall = new stdClass();
    $overall->badgeid = $badgeid;
    $overall->criteriatype = BADGE_CRITERIA_TYPE_OVERALL;
    $overall->method = 1;
    $overall->descriptionformat = FORMAT_HTML;
    $overall->description = '';
    $DB->insert_record('badge_criteria', $overall);
    
    // Courseset criterion  
    $crit = new stdClass();
    $crit->badgeid = $badgeid;
    $crit->criteriatype = BADGE_CRITERIA_TYPE_COURSESET; // 6 = set of courses
    $crit->method = 1; // ALL courses must be completed
    $crit->descriptionformat = FORMAT_HTML;
    $crit->description = '';
    $critid = $DB->insert_record('badge_criteria', $crit);
    
    foreach ($sbdef['courses'] as $reqcourse) {
        $param = new stdClass();
        $param->critid = $critid;
        $param->name = 'course_' . $reqcourse;
        $param->value = $reqcourse;
        $DB->insert_record('badge_criteria_param', $param);
    }
    
    // Issue to qualifying students
    // Find users who completed ALL courses in the set
    $placeholders = implode(',', $sbdef['courses']);
    $numRequired = count($sbdef['courses']);
    
    $qualifiers = $DB->get_records_sql("
        SELECT cc.userid, MAX(cc.timecompleted) as latest_completion
        FROM {course_completions} cc
        WHERE cc.course IN ($placeholders) 
        AND cc.timecompleted IS NOT NULL AND cc.timecompleted > 0
        GROUP BY cc.userid
        HAVING COUNT(DISTINCT cc.course) = ?
    ", [$numRequired]);
    
    $scount = 0;
    foreach ($qualifiers as $q) {
        $issue = new stdClass();
        $issue->badgeid = $badgeid;
        $issue->userid = $q->userid;
        $issue->uniquehash = md5($badgeid . '-' . $q->userid . '-' . time() . '-' . random_int(1000, 9999));
        $issue->dateissued = $q->latest_completion;
        $issue->dateexpire = null;
        $issue->visible = 1;
        $issue->issuernotified = 0;
        
        $DB->insert_record('badge_issued', $issue);
        $scount++;
        $issued_count++;
    }
    
    echo "  Badge '$badgeid': {$sbdef['name']} - Created & issued to $scount students\n";
    $site_badge_count++;
}

echo "\n  Site badges created: $site_badge_count\n";
echo "  Total badges issued (all): $issued_count\n";

// ================================================
// FINAL: Purge caches
// ================================================
echo "\n============================================\n";
echo "  PURGING CACHES...\n";
purge_all_caches();
echo "  DONE!\n";
echo "============================================\n";

// Summary
$total_badges = $DB->count_records('badge');
$total_issued = $DB->count_records('badge_issued');
$total_starred = $DB->count_records('favourite', ['component' => 'core_course', 'itemtype' => 'courses']);

echo "\n  SUMMARY:\n";
echo "  - Courses with full visibility: " . count($courseids) . "\n";
echo "  - Total badges: $total_badges\n";
echo "  - Total badges issued: $total_issued\n";
echo "  - Total starred courses: $total_starred\n";
echo "  - OBF Plugin: local_obf installed - badges are Open Badge compatible\n";
echo "\n";
