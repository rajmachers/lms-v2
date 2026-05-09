<?php
// Part 3b: Competency-Course Mapping + Learning Plans (fix ruleoutcome)
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');

echo "===== PART 3B: COMPETENCY MAPPING + LEARNING PLANS =====\n\n";

$ids = json_decode(file_get_contents('/tmp/moodle_setup_ids.json'), true);
$courseids = $ids['courseids'];
$groups = $ids['groups'];
$ph_comps = $ids['ph_competencies'];
$hs_comps = $ids['hs_competencies'];

// Map Physics competencies to courses
$course_comp_map = [
    'PHY101' => [$ph_comps[0], $ph_comps[1], $ph_comps[5]], // Math, Classical, Problem Solving
    'PHY102' => [$ph_comps[1], $ph_comps[3], $ph_comps[4]], // Classical, Experimental, Scientific Comm
    'PHY201' => [$ph_comps[0], $ph_comps[2], $ph_comps[5]], // Math, Modern, Problem Solving
    'PHY202' => [$ph_comps[1], $ph_comps[3], $ph_comps[4]], // Classical, Experimental, Scientific Comm
    'PHY301' => [$ph_comps[2], $ph_comps[3], $ph_comps[5]], // Modern, Experimental, Problem Solving
    'PHY302' => [$ph_comps[2], $ph_comps[4], $ph_comps[5]], // Modern, Scientific Comm, Problem Solving
];

echo "--- Competency-Course Mapping ---\n";
foreach ($course_comp_map as $shortname => $compids) {
    $courseid = $courseids[$shortname];
    foreach ($compids as $compid) {
        $exists = $DB->get_record('competency_coursecomp', [
            'courseid' => $courseid,
            'competencyid' => $compid,
        ]);
        if (!$exists) {
            $cc = new stdClass();
            $cc->courseid = $courseid;
            $cc->competencyid = $compid;
            $cc->ruleoutcome = 1; // Evidence -> recommend competency completion
            $cc->sortorder = 0;
            $cc->timecreated = time();
            $cc->timemodified = time();
            $cc->usermodified = 2;
            $DB->insert_record('competency_coursecomp', $cc);
        }
    }
    echo "  $shortname: " . count($compids) . " competencies mapped\n";
}

// Health Sciences competencies to existing courses
$hs_course_map = [
    2 => $hs_comps,
    3 => [$hs_comps[0], $hs_comps[2], $hs_comps[3], $hs_comps[5]],
];
foreach ($hs_course_map as $courseid => $compids) {
    foreach ($compids as $compid) {
        $exists = $DB->get_record('competency_coursecomp', [
            'courseid' => $courseid,
            'competencyid' => $compid,
        ]);
        if (!$exists) {
            $cc = new stdClass();
            $cc->courseid = $courseid;
            $cc->competencyid = $compid;
            $cc->ruleoutcome = 1;
            $cc->sortorder = 0;
            $cc->timecreated = time();
            $cc->timemodified = time();
            $cc->usermodified = 2;
            $DB->insert_record('competency_coursecomp', $cc);
        }
    }
    echo "  Course $courseid (Health Sciences): " . count($compids) . " competencies mapped\n";
}

// -------------------------------------------------------
// Learning Plan Template
// -------------------------------------------------------
echo "\n--- Learning Plans ---\n";

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
    $exists = $DB->get_record('competency_templatecomp', [
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
echo "  6 competencies added to template\n";

// Create learning plans for all 50 students
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
            $plan->status = 1; // Active
            $plan->duedate = strtotime('2028-06-30');
            $plan->reviewerid = null;
            $plan->timecreated = time();
            $plan->timemodified = time();
            $plan->usermodified = 2;
            $planid = $DB->insert_record('competency_plan', $plan);
            
            // Add competencies to plan
            foreach ($ph_comps as $compid) {
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
    echo "  Group $grp: done\n";
}
echo "  Total learning plans: $plancount\n";

// Save
$ids['learning_plan_template'] = $template->id;
$ids['course_comp_map'] = $course_comp_map;
file_put_contents('/tmp/moodle_setup_ids.json', json_encode($ids, JSON_PRETTY_PRINT));

echo "\nPart 3b complete!\n";
