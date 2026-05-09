<?php
// Part 5: Certificate template 2 + certificate activities + issue certificates
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
require_once($CFG->dirroot . '/course/lib.php');

echo "===== PART 5: CERTIFICATES =====\n\n";

$ids = json_decode(file_get_contents('/tmp/moodle_setup_ids.json'), true);
$groups = $ids['groups'];
$courseids = $ids['courseids'];

$cid = [
    1 => $courseids['PHY101'],
    2 => $courseids['PHY102'],
    3 => $courseids['PHY201'],
    4 => $courseids['PHY202'],
    5 => $courseids['PHY301'],
    6 => $courseids['PHY302'],
];

// -------------------------------------------------------
// STEP 1: Create Certificate Template 2
// -------------------------------------------------------
echo "--- Step 1: Certificate Template ---\n";

$template = $DB->get_record('tool_certificate_templates', ['name' => 'University Physics Programme Certificate']);
if (!$template) {
    $tp = new stdClass();
    $tp->name = 'University Physics Programme Certificate';
    $tp->contextid = context_system::instance()->id;
    $tp->timecreated = time();
    $tp->timemodified = time();

    // Check columns
    $columns = $DB->get_columns('tool_certificate_templates');
    if (isset($columns['shared'])) {
        $tp->shared = 1;
    }

    $tpid = $DB->insert_record('tool_certificate_templates', $tp);
    echo "  Created template: University Physics Programme Certificate (ID:$tpid)\n";

    // Create a page (landscape A4: 297x210)
    $page = new stdClass();
    $page->templateid = $tpid;
    $page->width = 297;
    $page->height = 210;
    $page->leftmargin = 0;
    $page->rightmargin = 0;
    $page->sequence = 1;
    $page->timecreated = time();
    $page->timemodified = time();
    $pageid = $DB->insert_record('tool_certificate_pages', $page);
    echo "  Created page (ID:$pageid) - A4 Landscape\n";

    // Add elements to the certificate
    $elements = [
        // "University of London" header
        ['element' => 'text', 'name' => 'University Name', 'data' => json_encode([
            'text' => 'University of London',
            'size' => 28,
            'colour' => '#1a3a5c',
            'alignment' => 'C',
            'bold' => 1,
        ]), 'posx' => 148, 'posy' => 20, 'width' => 260, 'refpoint' => 1, 'sequence' => 1],
        
        // "Faculty of Sciences — Department of Physics"
        ['element' => 'text', 'name' => 'Department', 'data' => json_encode([
            'text' => 'Faculty of Sciences — Department of Physics',
            'size' => 14,
            'colour' => '#666666',
            'alignment' => 'C',
        ]), 'posx' => 148, 'posy' => 35, 'width' => 260, 'refpoint' => 1, 'sequence' => 2],
        
        // "CERTIFICATE OF ACHIEVEMENT"
        ['element' => 'text', 'name' => 'Title', 'data' => json_encode([
            'text' => 'CERTIFICATE OF ACHIEVEMENT',
            'size' => 22,
            'colour' => '#333333',
            'alignment' => 'C',
            'bold' => 1,
        ]), 'posx' => 148, 'posy' => 55, 'width' => 260, 'refpoint' => 1, 'sequence' => 3],
        
        // "This is to certify that"
        ['element' => 'text', 'name' => 'Prefix', 'data' => json_encode([
            'text' => 'This is to certify that',
            'size' => 12,
            'colour' => '#666666',
            'alignment' => 'C',
        ]), 'posx' => 148, 'posy' => 75, 'width' => 260, 'refpoint' => 1, 'sequence' => 4],
        
        // User full name
        ['element' => 'userfield', 'name' => 'Student Name', 'data' => json_encode([
            'field' => 'fullname',
            'size' => 24,
            'colour' => '#1a3a5c',
            'alignment' => 'C',
            'bold' => 1,
        ]), 'posx' => 148, 'posy' => 88, 'width' => 260, 'refpoint' => 1, 'sequence' => 5],
        
        // "has successfully completed the course"
        ['element' => 'text', 'name' => 'Midtext', 'data' => json_encode([
            'text' => 'has successfully completed the course',
            'size' => 12,
            'colour' => '#666666',
            'alignment' => 'C',
        ]), 'posx' => 148, 'posy' => 104, 'width' => 260, 'refpoint' => 1, 'sequence' => 6],
        
        // Course full name
        ['element' => 'coursefield', 'name' => 'Course Name', 'data' => json_encode([
            'field' => 'fullname',
            'size' => 18,
            'colour' => '#333333',
            'alignment' => 'C',
            'bold' => 1,
        ]), 'posx' => 148, 'posy' => 116, 'width' => 260, 'refpoint' => 1, 'sequence' => 7],
        
        // "with the grade of"
        ['element' => 'text', 'name' => 'Grade prefix', 'data' => json_encode([
            'text' => 'Awarded on',
            'size' => 11,
            'colour' => '#666666',
            'alignment' => 'C',
        ]), 'posx' => 148, 'posy' => 135, 'width' => 260, 'refpoint' => 1, 'sequence' => 8],
        
        // Date issued
        ['element' => 'date', 'name' => 'Date', 'data' => json_encode([
            'dateitem' => 'issueddate',
            'dateformat' => 'strftimedatefull',
            'size' => 12,
            'colour' => '#333333',
            'alignment' => 'C',
        ]), 'posx' => 148, 'posy' => 145, 'width' => 260, 'refpoint' => 1, 'sequence' => 9],
        
        // QR code verification
        ['element' => 'code', 'name' => 'QR Code', 'data' => json_encode([
            'display' => 'qrcode',
            'encoding' => 'url',
            'size' => 10,
        ]), 'posx' => 260, 'posy' => 165, 'width' => 30, 'refpoint' => 0, 'sequence' => 10],
        
        // Signature line 1
        ['element' => 'text', 'name' => 'Signature', 'data' => json_encode([
            'text' => '____________________',
            'size' => 12,
            'colour' => '#333333',
            'alignment' => 'C',
        ]), 'posx' => 80, 'posy' => 170, 'width' => 120, 'refpoint' => 1, 'sequence' => 11],
        
        ['element' => 'text', 'name' => 'Signatory Name', 'data' => json_encode([
            'text' => 'Prof. Richard Hartley',
            'size' => 10,
            'colour' => '#333333',
            'alignment' => 'C',
        ]), 'posx' => 80, 'posy' => 180, 'width' => 120, 'refpoint' => 1, 'sequence' => 12],
        
        ['element' => 'text', 'name' => 'Signatory Title', 'data' => json_encode([
            'text' => 'Head of Physics',
            'size' => 9,
            'colour' => '#666666',
            'alignment' => 'C',
        ]), 'posx' => 80, 'posy' => 187, 'width' => 120, 'refpoint' => 1, 'sequence' => 13],
        
        // Second signatory
        ['element' => 'text', 'name' => 'Signature2', 'data' => json_encode([
            'text' => '____________________',
            'size' => 12,
            'colour' => '#333333',
            'alignment' => 'C',
        ]), 'posx' => 210, 'posy' => 170, 'width' => 120, 'refpoint' => 1, 'sequence' => 14],
        
        ['element' => 'text', 'name' => 'Dean Name', 'data' => json_encode([
            'text' => 'Dr. Sarah Pemberton',
            'size' => 10,
            'colour' => '#333333',
            'alignment' => 'C',
        ]), 'posx' => 210, 'posy' => 180, 'width' => 120, 'refpoint' => 1, 'sequence' => 15],
        
        ['element' => 'text', 'name' => 'Dean Title', 'data' => json_encode([
            'text' => 'Dean of Sciences',
            'size' => 9,
            'colour' => '#666666',
            'alignment' => 'C',
        ]), 'posx' => 210, 'posy' => 187, 'width' => 120, 'refpoint' => 1, 'sequence' => 16],
    ];

    foreach ($elements as $el) {
        $elem = new stdClass();
        $elem->pageid = $pageid;
        $elem->element = $el['element'];
        $elem->name = $el['name'];
        $elem->data = $el['data'];
        $elem->posx = $el['posx'];
        $elem->posy = $el['posy'];
        $elem->width = $el['width'];
        $elem->refpoint = $el['refpoint'];
        $elem->sequence = $el['sequence'];
        $elem->timecreated = time();
        $elem->timemodified = time();
        $DB->insert_record('tool_certificate_elements', $elem);
    }
    echo "  Added " . count($elements) . " elements to certificate template\n";
} else {
    $tpid = $template->id;
    echo "  Template exists (ID:$tpid)\n";
}

// -------------------------------------------------------
// STEP 2: Add certificate activities to each course
// -------------------------------------------------------
echo "\n--- Step 2: Certificate Activities ---\n";

$moduleid = $DB->get_field('modules', 'id', ['name' => 'coursecertificate']);
if (!$moduleid) {
    echo "  ERROR: mod_coursecertificate not installed!\n";
    exit(1);
}

foreach ($cid as $cnum => $courseid) {
    // Check if cert activity already exists
    $existing = $DB->get_records_sql(
        "SELECT cm.* FROM {course_modules} cm 
         JOIN {modules} m ON m.id = cm.module 
         WHERE cm.course = ? AND m.name = 'coursecertificate'",
        [$courseid]
    );
    
    if (!empty($existing)) {
        echo "  Course $cnum: certificate activity already exists\n";
        continue;
    }
    
    // Get course shortname
    $shortname = array_search($courseid, $courseids);
    
    // Create coursecertificate instance
    $cert = new stdClass();
    $cert->course = $courseid;
    $cert->name = 'Course Completion Certificate';
    $cert->intro = '<p>Download your certificate upon completing all course activities.</p>';
    $cert->introformat = 1;
    $cert->template = $tpid;
    $cert->automaticsend = 1;
    $cert->timecreated = time();
    $cert->timemodified = time();
    $certid = $DB->insert_record('coursecertificate', $cert);
    
    // Get the last section
    $lastsec = $DB->get_field_sql("SELECT MAX(section) FROM {course_sections} WHERE course = ?", [$courseid]);
    
    // Create course module
    $cm = new stdClass();
    $cm->course = $courseid;
    $cm->module = $moduleid;
    $cm->instance = $certid;
    $cm->section = 0;
    $cm->visible = 1;
    $cm->visibleoncoursepage = 1;
    $cm->added = time();
    $cm->completion = 0;
    $cmid = $DB->insert_record('course_modules', $cm);
    
    // Add to last section
    $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $lastsec]);
    if ($section) {
        $seq = trim($section->sequence);
        $section->sequence = $seq ? $seq . ',' . $cmid : (string)$cmid;
        $DB->update_record('course_sections', $section);
        $DB->set_field('course_modules', 'section', $section->id, ['id' => $cmid]);
    }
    
    context_module::instance($cmid);
    echo "  Course $cnum ($shortname): cert activity added (cm:$cmid, template:$tpid)\n";
}

// -------------------------------------------------------
// STEP 3: Issue certificates for completed students
// -------------------------------------------------------
echo "\n--- Step 3: Issue Certificates ---\n";

// Groups who completed which courses
$cert_groups = [
    'A' => [1, 2, 3, 4, 5, 6],
    'B' => [1, 2],
    'C' => [1, 2],
    'E' => [1, 2, 3, 4],
];

$issuedcount = 0;
foreach ($cert_groups as $grp => $cnums) {
    foreach ($groups[$grp] as $userid) {
        foreach ($cnums as $cnum) {
            $courseid = $cid[$cnum];
            
            // Check if already issued
            $exists = $DB->record_exists('tool_certificate_issues', [
                'userid' => $userid,
                'templateid' => $tpid,
                'courseid' => $courseid,
            ]);
            if ($exists) continue;
            
            // Generate unique code
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
            $user = $DB->get_record('user', ['id' => $userid]);
            $codeletters = strtoupper(substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1));
            $code = $code . $codeletters;
            
            $issue = new stdClass();
            $issue->userid = $userid;
            $issue->templateid = $tpid;
            $issue->code = $code;
            $issue->emailed = 0;
            $issue->timecreated = time() - rand(86400, 86400 * 7);
            $issue->expires = 0;
            $issue->data = json_encode(['courseid' => $courseid]);
            $issue->courseid = $courseid;
            
            // Check which columns exist
            $columns = $DB->get_columns('tool_certificate_issues');
            if (!isset($columns['courseid'])) {
                unset($issue->courseid);
            }
            
            $issueid = $DB->insert_record('tool_certificate_issues', $issue);
            $issuedcount++;
        }
    }
    echo "  Group $grp: certificates issued for courses " . implode(',', $cnums) . "\n";
}
echo "  Total certificates issued: $issuedcount\n";

// Rebuild caches
echo "\n--- Rebuilding caches ---\n";
foreach ($cid as $cnum => $courseid) {
    rebuild_course_cache($courseid, true);
}
echo "  All course caches rebuilt\n";

echo "\nPart 5 complete!\n";
