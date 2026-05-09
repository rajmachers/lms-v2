<?php
// Part 3c: Look up competency IDs from DB, do mapping + learning plans
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');

echo "===== PART 3C: COMPETENCY MAPPING + LEARNING PLANS =====\n\n";

$ids = json_decode(file_get_contents('/tmp/moodle_setup_ids.json'), true);
$courseids = $ids['courseids'];
$groups = $ids['groups'];

// Look up competency IDs from DB by shortname
function get_comp($shortname) {
    global $DB;
    $rec = $DB->get_record('competency', ['shortname' => $shortname]);
    if (!$rec) { echo "  WARNING: Competency '$shortname' not found!\n"; return 0; }
    return (int)$rec->id;
}

// Health Sciences competencies
$hs_comps = [
    get_comp('Clinical Knowledge'),
    get_comp('Patient Assessment'),
    get_comp('Treatment Planning'),
    get_comp('Professional Communication'),
    get_comp('Ethical Practice'),
    get_comp('Research & Evidence'),
];
echo "Health Sciences competency IDs: " . implode(', ', $hs_comps) . "\n";

// Physics top-level competencies
$ph_comps = [
    get_comp('Mathematical Methods'),   // 0
    get_comp('Classical Physics'),       // 1
    get_comp('Modern Physics'),          // 2
    get_comp('Experimental Skills'),     // 3
    get_comp('Scientific Communication'),// 4
    get_comp('Problem Solving'),         // 5
];
echo "Physics competency IDs: " . implode(', ', $ph_comps) . "\n\n";

// -------------------------------------------------------
// Competency-Course Mapping
// -------------------------------------------------------
echo "--- Competency-Course Mapping ---\n";

$course_comp_map = [
    'PHY101' => [$ph_comps[0], $ph_comps[1], $ph_comps[5]], // Math, Classical, Problem Solving
    'PHY102' => [$ph_comps[1], $ph_comps[3], $ph_comps[4]], // Classical, Experimental, Scientific Comm
    'PHY201' => [$ph_comps[0], $ph_comps[2], $ph_comps[5]], // Math, Modern, Problem Solving
    'PHY202' => [$ph_comps[1], $ph_comps[3], $ph_comps[4]], // Classical, Experimental, Scientific Comm
    'PHY301' => [$ph_comps[2], $ph_comps[3], $ph_comps[5]], // Modern, Experimental, Problem Solving
    'PHY302' => [$ph_comps[2], $ph_comps[4], $ph_comps[5]], // Modern, Scientific Comm, Problem Solving
];

foreach ($course_comp_map as $shortname => $compids) {
    $courseid = $courseids[$shortname];
    $count = 0;
    foreach ($compids as $compid) {
        if ($compid == 0) continue;
        $exists = $DB->record_exists('competency_coursecomp', [
            'courseid' => $courseid,
            'competencyid' => $compid,
        ]);
        if (!$exists) {
            $cc = new stdClass();
            $cc->courseid = $courseid;
            $cc->competencyid = $compid;
            $cc->ruleoutcome = 1;
            $cc->sortorder = $count;
            $cc->timecreated = time();
            $cc->timemodified = time();
            $cc->usermodified = 2;
            $DB->insert_record('competency_coursecomp', $cc);
            $count++;
        }
    }
    echo "  $shortname (course $courseid): " . count($compids) . " competencies mapped\n";
}

// Health Sciences competencies to existing courses
$hs_course_map = [
    2 => $hs_comps,
    3 => [$hs_comps[0], $hs_comps[2], $hs_comps[3], $hs_comps[5]],
];
foreach ($hs_course_map as $courseid => $compids) {
    $count = 0;
    foreach ($compids as $compid) {
        if ($compid == 0) continue;
        $exists = $DB->record_exists('competency_coursecomp', [
            'courseid' => $courseid,
            'competencyid' => $compid,
        ]);
        if (!$exists) {
            $cc = new stdClass();
            $cc->courseid = $courseid;
            $cc->competencyid = $compid;
            $cc->ruleoutcome = 1;
            $cc->sortorder = $count;
            $cc->timecreated = time();
            $cc->timemodified = time();
            $cc->usermodified = 2;
            $DB->insert_record('competency_coursecomp', $cc);
            $count++;
        }
    }
    echo "  Course $courseid (Health Sciences): " . count($compids) . " competencies\n";
}

// -------------------------------------------------------
// Learning Plan Template
// -------------------------------------------------------
echo "\n--- Learning Plan Template ---\n";

$template = $DB->get_record('competency_template', ['shortname' => 'BSC-PHY-LP']);
if (!$template) {
    $tp = new stdClass();
    $tp->shortname = 'BSC-PHY-LP';
    $tp->idnumber = 'BSC-PHY-LP-2025';
    $tp->description = 'Learning plan for BSc Physics students tracking progress across all QAA benchmark competencies.';
    $tp->descriptionformat = 1;
    $tp->visible = 1;
    $tp->contextid = context_system::instance()->id;
    $tp->duedate = strtotime('2028-06-30');
    $tp->timecreated = time();
    $tp->timemodified = time();
    $tp->usermodified = 2;
    $tpid = $DB->insert_record('competency_template', $tp);
    $template = $DB->get_record('competency_template', ['id' => $tpid]);
    echo "  Created template (ID:$tpid)\n";
} else {
    echo "  Template exists (ID:$template->id)\n";
}

// Add competencies to template
foreach ($ph_comps as $idx => $compid) {
    if ($compid == 0) continue;
    $exists = $DB->record_exists('competency_templatecomp', [
        'templateid' => $template->id,
        'competencyid' => $compid,
    ]);
    if (!$exists) {
        $tc = new stdClass();
        $tc->templateid = $template->id;
        $tc->competencyid = $compid;
        $tc->sortorder = $idx;
        $tc->timecreated = time();
        $tc->timemodified = time();
        $tc->usermodified = 2;
        $DB->insert_record('competency_templatecomp', $tc);
    }
}
echo "  6 competencies in template\n";

// -------------------------------------------------------
// Learning Plans for all 50 students
// -------------------------------------------------------
echo "\n--- Learning Plans ---\n";

$plancount = 0;
foreach (['A', 'B', 'C', 'D', 'E'] as $grp) {
    foreach ($groups[$grp] as $uid) {
        $exists = $DB->get_record('competency_plan', [
            'userid' => $uid,
            'templateid' => $template->id,
        ]);
        if (!$exists) {
            $plan = new stdClass();
            $plan->name = 'BSc Physics Learning Plan';
            $plan->description = '';
            $plan->descriptionformat = 1;
            $plan->userid = $uid;
            $plan->templateid = $template->id;
            $plan->status = 1;
            $plan->duedate = strtotime('2028-06-30');
            $plan->reviewerid = null;
            $plan->timecreated = time();
            $plan->timemodified = time();
            $plan->usermodified = 2;
            $planid = $DB->insert_record('competency_plan', $plan);
            
            foreach ($ph_comps as $compid) {
                if ($compid == 0) continue;
                $pc = new stdClass();
                $pc->planid = $planid;
                $pc->competencyid = $compid;
                $pc->sortorder = 0;
                $pc->timecreated = time();
                $pc->timemodified = time();
                $pc->usermodified = 2;
                $DB->insert_record('competency_plancomp', $pc);
            }
            $plancount++;
        }
    }
    echo "  Group $grp: done (" . count($groups[$grp]) . " students)\n";
}
echo "  Total learning plans: $plancount\n";

// Save updated IDs
$ids['ph_competencies'] = $ph_comps;
$ids['hs_competencies'] = $hs_comps;
$ids['learning_plan_template'] = $template->id;
file_put_contents('/tmp/moodle_setup_ids.json', json_encode($ids, JSON_PRETTY_PRINT));

echo "\nPart 3c complete!\n";
