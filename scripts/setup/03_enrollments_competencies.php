<?php
// Part 3: Enrolments + Competency Frameworks + Mapping + Learning Plans
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');

echo "===== PART 3: ENROLMENTS + COMPETENCIES =====\n\n";

$ids = json_decode(file_get_contents('/tmp/moodle_setup_ids.json'), true);
$teachers = $ids['teachers'];
$groups = $ids['groups'];
$courseids = $ids['courseids'];

// Map shortnames to IDs
$cid = [
    1 => $courseids['PHY101'],
    2 => $courseids['PHY102'],
    3 => $courseids['PHY201'],
    4 => $courseids['PHY202'],
    5 => $courseids['PHY301'],
    6 => $courseids['PHY302'],
];

// -------------------------------------------------------
// STEP 1: Ensure manual enrol plugin exists for each course
// -------------------------------------------------------
echo "--- Step 1: Enrolments ---\n";

$enrolplugin = enrol_get_plugin('manual');
$teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
$neteacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
$studentrole = $DB->get_record('role', ['shortname' => 'student']);

echo "  Roles: editingteacher={$teacherrole->id}, teacher={$neteacherrole->id}, student={$studentrole->id}\n";

// Ensure manual enrol instance exists for each course
foreach ($cid as $num => $courseid) {
    $instances = $DB->get_records('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
    if (empty($instances)) {
        $course = $DB->get_record('course', ['id' => $courseid]);
        $enrolplugin->add_instance($course);
        echo "  Added manual enrol to course $num (ID:$courseid)\n";
    }
}

// Helper to enrol a user
function enrol_user_to_course($userid, $courseid, $roleid) {
    global $DB, $enrolplugin;
    $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
    if (!$instance) return false;
    
    // Check if already enrolled
    $ue = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid]);
    if ($ue) return true;
    
    $enrolplugin->enrol_user($instance, $userid, $roleid, time() - 86400 * 30, 0);
    return true;
}

// Teacher enrolment mapping
// dr.james.whitfield: courses 1,2 (editing teacher)
// dr.sarah.pemberton: courses 3,4 (editing teacher)
// prof.richard.hartley: courses 5,6 (editing teacher)
// dr.emma.blackwood: courses 3,6 (editing teacher)
// dr.thomas.greenway: all 6 (non-editing teacher)
$teacher_mapping = [
    'dr.james.whitfield' => ['courses' => [1, 2], 'role' => $teacherrole->id],
    'dr.sarah.pemberton' => ['courses' => [3, 4], 'role' => $teacherrole->id],
    'prof.richard.hartley' => ['courses' => [5, 6], 'role' => $teacherrole->id],
    'dr.emma.blackwood' => ['courses' => [3, 6], 'role' => $teacherrole->id],
    'dr.thomas.greenway' => ['courses' => [1, 2, 3, 4, 5, 6], 'role' => $neteacherrole->id],
];

$tcount = 0;
foreach ($teacher_mapping as $username => $map) {
    $uid = $teachers[$username];
    foreach ($map['courses'] as $cnum) {
        enrol_user_to_course($uid, $cid[$cnum], $map['role']);
        $tcount++;
    }
    echo "  Teacher $username enrolled in " . count($map['courses']) . " courses\n";
}
echo "  Total teacher enrolments: $tcount\n\n";

// Student enrolment mapping
// Group A: all 6 courses
// Group B: courses 1-4
// Group C: courses 1-4
// Group D: courses 3-6
// Group E: all 6 courses
$student_mapping = [
    'A' => [1, 2, 3, 4, 5, 6],
    'B' => [1, 2, 3, 4],
    'C' => [1, 2, 3, 4],
    'D' => [3, 4, 5, 6],
    'E' => [1, 2, 3, 4, 5, 6],
];

$scount = 0;
foreach ($student_mapping as $grp => $cnums) {
    $studs = $groups[$grp];
    foreach ($studs as $uid) {
        foreach ($cnums as $cnum) {
            enrol_user_to_course($uid, $cid[$cnum], $studentrole->id);
            $scount++;
        }
    }
    echo "  Group $grp: " . count($studs) . " students × " . count($cnums) . " courses = " . (count($studs) * count($cnums)) . " enrolments\n";
}
echo "  Total student enrolments: $scount\n";

// -------------------------------------------------------
// STEP 2: Create Competency Frameworks
// -------------------------------------------------------
echo "\n--- Step 2: Competency Frameworks ---\n";

// Check if competency tables exist
$tables = $DB->get_tables();
if (!in_array('competency_framework', $tables)) {
    // Try 'competency_framework' table - Moodle 4+ uses this
    echo "  WARNING: competency_framework table not found. Checking alternatives...\n";
    if (in_array('comp_framework', $tables)) {
        echo "  Found comp_framework table\n";
    } else {
        echo "  Competency tables not available. Skipping competency setup.\n";
        echo "\nPart 3 enrolments complete!\n";
        $ids['student_mapping'] = $student_mapping;
        $ids['teacher_mapping_courses'] = [];
        foreach ($teacher_mapping as $u => $m) $ids['teacher_mapping_courses'][$u] = $m['courses'];
        file_put_contents('/tmp/moodle_setup_ids.json', json_encode($ids, JSON_PRETTY_PRINT));
        exit;
    }
}

// Framework 1: Clinical Competency Standards (Health Sciences)
$fw1 = $DB->get_record('competency_framework', ['shortname' => 'CCS-HS']);
if (!$fw1) {
    $fw = new stdClass();
    $fw->shortname = 'CCS-HS';
    $fw->idnumber = 'CCS-HS-2025';
    $fw->description = 'Clinical Competency Standards for Health Sciences programmes. These competencies define the expected outcomes for graduates in clinical and healthcare fields.';
    $fw->descriptionformat = 1;
    $fw->visible = 1;
    $fw->contextid = context_system::instance()->id;
    $fw->taxonomies = 'competency';
    $fw->scaleid = 2; // Default competency scale
    $fw->scaleconfiguration = '[{"scaleid":"2"},{"id":1,"scaledefault":1,"proficient":0},{"id":2,"scaledefault":0,"proficient":1}]';
    $fw->timecreated = time();
    $fw->timemodified = time();
    $fw->usermodified = 2;
    $fw1id = $DB->insert_record('competency_framework', $fw);
    $fw1 = $DB->get_record('competency_framework', ['id' => $fw1id]);
    echo "  Created Framework: Clinical Competency Standards (ID:$fw1id)\n";
} else {
    echo "  Exists: Clinical Competency Standards (ID:$fw1->id)\n";
}

// Framework 2: QAA Physics Benchmark
$fw2 = $DB->get_record('competency_framework', ['shortname' => 'QAA-PHY']);
if (!$fw2) {
    $fw = new stdClass();
    $fw->shortname = 'QAA-PHY';
    $fw->idnumber = 'QAA-PHY-2025';
    $fw->description = 'QAA Subject Benchmark Statement — Physics. These competencies align with the Quality Assurance Agency standards for BSc Physics programmes in the United Kingdom.';
    $fw->descriptionformat = 1;
    $fw->visible = 1;
    $fw->contextid = context_system::instance()->id;
    $fw->taxonomies = 'competency';
    $fw->scaleid = 2;
    $fw->scaleconfiguration = '[{"scaleid":"2"},{"id":1,"scaledefault":1,"proficient":0},{"id":2,"scaledefault":0,"proficient":1}]';
    $fw->timecreated = time();
    $fw->timemodified = time();
    $fw->usermodified = 2;
    $fw2id = $DB->insert_record('competency_framework', $fw);
    $fw2 = $DB->get_record('competency_framework', ['id' => $fw2id]);
    echo "  Created Framework: QAA Physics Benchmark (ID:$fw2id)\n";
} else {
    echo "  Exists: QAA Physics Benchmark (ID:$fw2->id)\n";
}

// -------------------------------------------------------
// STEP 3: Create Competencies
// -------------------------------------------------------
echo "\n--- Step 3: Competencies ---\n";

function create_competency($frameworkid, $shortname, $idnumber, $description, $parentid = 0, $sortorder = 0) {
    global $DB;
    $existing = $DB->get_record('competency', ['shortname' => $shortname, 'competencyframeworkid' => $frameworkid]);
    if ($existing) {
        echo "    Exists: $shortname (ID:$existing->id)\n";
        return $existing->id;
    }
    
    $comp = new stdClass();
    $comp->shortname = $shortname;
    $comp->idnumber = $idnumber;
    $comp->description = $description;
    $comp->descriptionformat = 1;
    $comp->competencyframeworkid = $frameworkid;
    $comp->parentid = $parentid;
    $comp->path = '/0/'; // will update
    $comp->sortorder = $sortorder;
    $comp->timecreated = time();
    $comp->timemodified = time();
    $comp->usermodified = 2;
    $comp->scaleid = null;
    $comp->scaleconfiguration = null;
    $comp->ruletype = null;
    $comp->ruleoutcome = 0;
    $comp->ruleconfig = null;
    $id = $DB->insert_record('competency', $comp);
    
    // Update path
    if ($parentid > 0) {
        $parent = $DB->get_record('competency', ['id' => $parentid]);
        $path = $parent->path . $id . '/';
    } else {
        $path = '/0/' . $id . '/';
    }
    $DB->set_field('competency', 'path', $path, ['id' => $id]);
    
    echo "    Created: $shortname (ID:$id)\n";
    return $id;
}

// Clinical Competency Standards (Health Sciences) - 6 competencies
echo "  Framework 1: Clinical Competency Standards\n";
$hs_comps = [];
$hs_comps[] = create_competency($fw1->id, 'Clinical Knowledge', 'CCS-CK', 'Demonstrate comprehensive understanding of biomedical sciences, clinical medicine, and evidence-based healthcare practice.', 0, 1);
$hs_comps[] = create_competency($fw1->id, 'Patient Assessment', 'CCS-PA', 'Perform systematic patient assessment including history-taking, physical examination, and diagnostic reasoning.', 0, 2);
$hs_comps[] = create_competency($fw1->id, 'Treatment Planning', 'CCS-TP', 'Develop appropriate management plans integrating pharmacological and non-pharmacological interventions.', 0, 3);
$hs_comps[] = create_competency($fw1->id, 'Professional Communication', 'CCS-PC', 'Communicate effectively with patients, families, and healthcare teams using appropriate verbal and written skills.', 0, 4);
$hs_comps[] = create_competency($fw1->id, 'Ethical Practice', 'CCS-EP', 'Apply ethical principles, maintain confidentiality, and practice within professional regulatory frameworks.', 0, 5);
$hs_comps[] = create_competency($fw1->id, 'Research & Evidence', 'CCS-RE', 'Critically appraise research evidence and apply findings to inform clinical decision-making.', 0, 6);

// QAA Physics Benchmark - 6 competencies with sub-competencies
echo "  Framework 2: QAA Physics Benchmark\n";
$ph_comps = [];

// 1. Mathematical Methods
$mm = create_competency($fw2->id, 'Mathematical Methods', 'QAA-MM', 'Apply mathematical techniques to formulate and solve physics problems, including calculus, linear algebra, and differential equations.', 0, 1);
$ph_comps[] = $mm;
create_competency($fw2->id, 'Calculus & Analysis', 'QAA-MM-CA', 'Use differential and integral calculus to model physical systems and solve equations of motion.', $mm, 1);
create_competency($fw2->id, 'Linear Algebra', 'QAA-MM-LA', 'Apply vector and matrix methods to multi-dimensional physical problems.', $mm, 2);

// 2. Classical Physics
$cp = create_competency($fw2->id, 'Classical Physics', 'QAA-CP', 'Understand and apply the principles of classical mechanics, electromagnetism, and thermodynamics.', 0, 2);
$ph_comps[] = $cp;
create_competency($fw2->id, 'Mechanics & Relativity', 'QAA-CP-MR', 'Apply Newtonian mechanics and special relativity to analyse motion and forces.', $cp, 1);
create_competency($fw2->id, 'Electromagnetism', 'QAA-CP-EM', 'Understand electric and magnetic fields, Maxwell\'s equations, and electromagnetic wave propagation.', $cp, 2);

// 3. Modern Physics
$mp = create_competency($fw2->id, 'Modern Physics', 'QAA-MP', 'Demonstrate understanding of quantum mechanics, nuclear physics, and contemporary theoretical frameworks.', 0, 3);
$ph_comps[] = $mp;
create_competency($fw2->id, 'Quantum Theory', 'QAA-MP-QT', 'Apply the principles of quantum mechanics including wave functions, operators, and measurement.', $mp, 1);
create_competency($fw2->id, 'Nuclear & Particle', 'QAA-MP-NP', 'Understand nuclear structure, radioactive decay, and the Standard Model of particle physics.', $mp, 2);

// 4. Experimental Skills
$es = create_competency($fw2->id, 'Experimental Skills', 'QAA-ES', 'Design, conduct, and analyse physics experiments using appropriate techniques and instrumentation.', 0, 4);
$ph_comps[] = $es;
create_competency($fw2->id, 'Data Analysis', 'QAA-ES-DA', 'Process experimental data using statistical methods, error analysis, and graphical techniques.', $es, 1);
create_competency($fw2->id, 'Laboratory Techniques', 'QAA-ES-LT', 'Safely operate laboratory equipment and follow experimental protocols.', $es, 2);

// 5. Scientific Communication
$sc = create_competency($fw2->id, 'Scientific Communication', 'QAA-SC', 'Present scientific ideas clearly through written reports, oral presentations, and technical discussions.', 0, 5);
$ph_comps[] = $sc;

// 6. Problem Solving & Critical Thinking
$ps = create_competency($fw2->id, 'Problem Solving', 'QAA-PS', 'Analyse complex problems, identify relevant physics principles, and develop systematic solutions.', 0, 6);
$ph_comps[] = $ps;
create_competency($fw2->id, 'Analytical Reasoning', 'QAA-PS-AR', 'Break down complex problems into components and apply appropriate physical and mathematical models.', $ps, 1);
create_competency($fw2->id, 'Critical Evaluation', 'QAA-PS-CE', 'Critically evaluate physical models, approximations, and experimental results.', $ps, 2);

// -------------------------------------------------------
// STEP 4: Map competencies to courses
// -------------------------------------------------------
echo "\n--- Step 4: Competency-Course Mapping ---\n";

// Map Physics competencies to courses
// PHY101: Classical Physics (Mechanics), Mathematical Methods, Problem Solving
// PHY102: Classical Physics (EM), Experimental Skills, Scientific Communication
// PHY201: Modern Physics (QT), Mathematical Methods, Problem Solving
// PHY202: Classical Physics, Experimental Skills, Scientific Communication
// PHY301: Modern Physics (NP), Experimental Skills, Problem Solving
// PHY302: Modern Physics, Scientific Communication, Problem Solving

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
    foreach ($compids as $compid) {
        $exists = $DB->get_record('competency_coursecomp', [
            'courseid' => $courseid,
            'competencyid' => $compid,
        ]);
        if (!$exists) {
            $cc = new stdClass();
            $cc->courseid = $courseid;
            $cc->competencyid = $compid;
            $cc->sortorder = 0;
            $cc->timecreated = time();
            $cc->timemodified = time();
            $cc->usermodified = 2;
            $DB->insert_record('competency_coursecomp', $cc);
        }
    }
    $comp = $DB->get_record('competency', ['id' => $compids[0]]);
    echo "  $shortname: " . count($compids) . " competencies mapped\n";
}

// Also map Health Sciences competencies to existing courses (2, 3)
$hs_course_map = [
    2 => [$hs_comps[0], $hs_comps[1], $hs_comps[2], $hs_comps[3], $hs_comps[4], $hs_comps[5]], // All 6 for Fellowship
    3 => [$hs_comps[0], $hs_comps[2], $hs_comps[3], $hs_comps[5]], // 4 for Certificate
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
// STEP 5: Create Learning Plans
// -------------------------------------------------------
echo "\n--- Step 5: Learning Plans ---\n";

// Learning Plan Template for Physics students
$template = $DB->get_record('competency_template', ['shortname' => 'BSC-PHY-LP']);
if (!$template) {
    $tp = new stdClass();
    $tp->shortname = 'BSC-PHY-LP';
    $tp->idnumber = 'BSC-PHY-LP-2025';
    $tp->description = 'Learning plan for BSc Physics students tracking progress across all QAA benchmark competencies over the three-year programme.';
    $tp->descriptionformat = 1;
    $tp->visible = 1;
    $tp->contextid = context_system::instance()->id;
    $tp->duedate = strtotime('2028-06-30');
    $tp->timecreated = time();
    $tp->timemodified = time();
    $tp->usermodified = 2;
    $tpid = $DB->insert_record('competency_template', $tp);
    $template = $DB->get_record('competency_template', ['id' => $tpid]);
    echo "  Created template: BSc Physics Learning Plan (ID:$tpid)\n";
} else {
    echo "  Exists template: BSc Physics Learning Plan (ID:$template->id)\n";
}

// Add all 6 top-level Physics competencies to the template
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
echo "  Added 6 competencies to template\n";

// Create individual learning plans for physics students (Groups A, B, C, D, E)
$physicsgroups = ['A', 'B', 'C', 'D', 'E'];
$plancount = 0;
foreach ($physicsgroups as $grp) {
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
    echo "  Group $grp: Learning plans created\n";
}
echo "  Total learning plans: $plancount\n";

// Save updated IDs
$ids['competency_frameworks'] = ['health_sciences' => $fw1->id, 'qaa_physics' => $fw2->id];
$ids['hs_competencies'] = $hs_comps;
$ids['ph_competencies'] = $ph_comps;
$ids['learning_plan_template'] = $template->id;
$ids['course_comp_map'] = $course_comp_map;
$ids['student_mapping'] = $student_mapping;
file_put_contents('/tmp/moodle_setup_ids.json', json_encode($ids, JSON_PRETTY_PRINT));

echo "\nPart 3 complete!\n";
