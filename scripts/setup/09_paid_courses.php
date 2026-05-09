<?php
/**
 * Part 1: Enable payment infrastructure, create Professional Development category,
 * create 6 paid courses, enable BigBlueButton, configure fee enrollment.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/lib/datalib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
global $DB, $CFG;

$admin = $DB->get_record('user', ['username' => 'admin']);
\core\session\manager::set_user($admin);

echo "======================================\n";
echo " PHASE 1: ENABLE PLUGINS\n";
echo "======================================\n\n";

// 1. Enable enrol_fee (Enrolment on payment).
$enabled_enrols = explode(',', $CFG->enrol_plugins_enabled ?? 'manual,guest,self,cohort');
if (!in_array('fee', $enabled_enrols)) {
    $enabled_enrols[] = 'fee';
    set_config('enrol_plugins_enabled', implode(',', array_filter($enabled_enrols)));
    echo "[OK] Enabled enrol_fee (Enrolment on payment)\n";
} else {
    echo "[SKIP] enrol_fee already enabled\n";
}

// 2. Enable PayPal payment gateway.
// PayPal gateway config for sandbox mode.
set_config('clientid', 'sb-sandbox-demo-client-id', 'paygw_paypal');
set_config('secret', 'sb-sandbox-demo-secret-key', 'paygw_paypal');
set_config('environment', 'sandbox', 'paygw_paypal');
set_config('brandname', 'UK International University', 'paygw_paypal');
echo "[OK] Configured PayPal gateway (sandbox mode)\n";

// Ensure PayPal gateway is enabled.
$enabledgateways = get_config('core', 'paygw_plugins_enabled');
if ($enabledgateways === false || strpos($enabledgateways, 'paypal') === false) {
    set_config('paygw_plugins_enabled', 'paypal');
}

// 3. Enable BigBlueButton module.
$bbb = $DB->get_record('modules', ['name' => 'bigbluebuttonbn']);
if ($bbb && !$bbb->visible) {
    $DB->set_field('modules', 'visible', 1, ['id' => $bbb->id]);
    echo "[OK] Enabled BigBlueButton module\n";
} elseif ($bbb && $bbb->visible) {
    echo "[SKIP] BigBlueButton already enabled\n";
} else {
    echo "[WARN] BigBlueButton module not installed\n";
}

// Set some basic BBB config (use test server).
set_config('bigbluebuttonbn_server_url', 'https://test-install.blindsidenetworks.com/bigbluebutton/');
set_config('bigbluebuttonbn_shared_secret', '8cd8ef52e8e101574e400365b55e11a6');
echo "[OK] Configured BBB test server\n";

echo "\n======================================\n";
echo " PHASE 2: CREATE CATEGORY & COURSES\n";
echo "======================================\n\n";

// 4. Create "Professional Development" category.
$catname = 'Professional Development';
$cat = $DB->get_record('course_categories', ['name' => $catname]);
if (!$cat) {
    $catdata = new \stdClass();
    $catdata->name = $catname;
    $catdata->description = 'Short courses and professional certifications available for individual purchase. Enhance your career with industry-relevant skills.';
    $catdata->descriptionformat = FORMAT_HTML;
    $catdata->parent = 0;
    $catdata->sortorder = 999;
    $catdata->visible = 1;
    $cat = \core_course_category::create($catdata);
    $catid = $cat->id;
    echo "[OK] Created category: $catname (id=$catid)\n";
} else {
    $catid = $cat->id;
    echo "[SKIP] Category '$catname' already exists (id=$catid)\n";
}

// Also create "Open Courses" subcategory (free taster courses).
$opencatname = 'Open Courses (Free)';
$opencat = $DB->get_record('course_categories', ['name' => $opencatname]);
if (!$opencat) {
    $opencatdata = new \stdClass();
    $opencatdata->name = $opencatname;
    $opencatdata->description = 'Free introductory courses open to all learners worldwide.';
    $opencatdata->descriptionformat = FORMAT_HTML;
    $opencatdata->parent = 0;
    $opencatdata->visible = 1;
    $opencat = \core_course_category::create($opencatdata);
    $opencatid = $opencat->id;
    echo "[OK] Created category: $opencatname (id=$opencatid)\n";
} else {
    $opencatid = $opencat->id;
    echo "[SKIP] Category '$opencatname' already exists (id=$opencatid)\n";
}

// 5. Define 6 paid courses + 2 free open courses.
$paidcourses = [
    [
        'fullname'  => 'Research Methods & Academic Writing',
        'shortname' => 'PD-RES01',
        'summary'   => 'Master the fundamentals of research methodology, literature review, and academic writing. This course covers quantitative and qualitative approaches, ethical considerations, and publishing strategies for international journals.',
        'price'     => '49.00',
        'currency'  => 'GBP',
        'category'  => $catid,
        'numsections' => 8,
    ],
    [
        'fullname'  => 'Data Science for Healthcare',
        'shortname' => 'PD-DS01',
        'summary'   => 'Apply data science techniques to healthcare challenges. Learn Python-based analytics, machine learning for clinical prediction, electronic health records analysis, and data visualisation for medical research.',
        'price'     => '99.00',
        'currency'  => 'GBP',
        'category'  => $catid,
        'numsections' => 10,
    ],
    [
        'fullname'  => 'Clinical Trial Design',
        'shortname' => 'PD-CTD01',
        'summary'   => 'Comprehensive training in designing, conducting, and analyzing clinical trials. Covers Phase I-IV trials, randomisation, blinding, regulatory requirements (EMA/FDA), and Good Clinical Practice (GCP).',
        'price'     => '149.00',
        'currency'  => 'GBP',
        'category'  => $catid,
        'numsections' => 12,
    ],
    [
        'fullname'  => 'AI & Machine Learning Foundations',
        'shortname' => 'PD-AI01',
        'summary'   => 'An accessible introduction to artificial intelligence and machine learning. Explore neural networks, natural language processing, computer vision, and ethical AI — with hands-on projects using real-world datasets.',
        'price'     => '99.00',
        'currency'  => 'GBP',
        'category'  => $catid,
        'numsections' => 10,
    ],
    [
        'fullname'  => 'Global Health Policy',
        'shortname' => 'PD-GHP01',
        'summary'   => 'Analyse global health challenges through a policy lens. Topics include universal health coverage, pandemic preparedness, WHO frameworks, health equity, and the Sustainable Development Goals (SDGs).',
        'price'     => '79.00',
        'currency'  => 'GBP',
        'category'  => $catid,
        'numsections' => 8,
    ],
    [
        'fullname'  => 'Advanced Statistical Analysis',
        'shortname' => 'PD-STAT01',
        'summary'   => 'Go beyond basic statistics with multivariate analysis, Bayesian methods, survival analysis, and structural equation modelling. Hands-on exercises using R and SPSS. Essential for doctoral researchers and data professionals.',
        'price'     => '69.00',
        'currency'  => 'GBP',
        'category'  => $catid,
        'numsections' => 10,
    ],
];

$freecourses = [
    [
        'fullname'  => 'Introduction to University Study Skills',
        'shortname' => 'OPEN-STUDY01',
        'summary'   => 'A free introductory course for prospective students. Learn time management, note-taking strategies, critical thinking, and how to succeed in a university environment.',
        'category'  => $opencatid,
        'numsections' => 5,
    ],
    [
        'fullname'  => 'English for Academic Purposes',
        'shortname' => 'OPEN-EAP01',
        'summary'   => 'Improve your academic English skills for university-level study. Covers academic vocabulary, essay structure, referencing, and seminar discussion skills. Free for all international applicants.',
        'category'  => $opencatid,
        'numsections' => 6,
    ],
];

$createdPaid = [];
$createdFree = [];

foreach ($paidcourses as $cdata) {
    $existing = $DB->get_record('course', ['shortname' => $cdata['shortname']]);
    if ($existing) {
        echo "[SKIP] Course '{$cdata['shortname']}' already exists (id={$existing->id})\n";
        $createdPaid[] = ['id' => $existing->id, 'price' => $cdata['price'], 'currency' => $cdata['currency']];
        continue;
    }
    $courseobj = new \stdClass();
    $courseobj->fullname = $cdata['fullname'];
    $courseobj->shortname = $cdata['shortname'];
    $courseobj->summary = $cdata['summary'];
    $courseobj->summaryformat = FORMAT_HTML;
    $courseobj->category = $cdata['category'];
    $courseobj->numsections = $cdata['numsections'];
    $courseobj->format = 'topics';
    $courseobj->visible = 1;
    $courseobj->enablecompletion = 1;
    $courseobj->showgrades = 1;
    $courseobj->showreports = 1;
    $courseobj->showactivitydates = 1;
    $courseobj->showcompletionconditions = 1;
    $courseobj->startdate = time();
    $courseobj->enddate = time() + (180 * 86400); // 6 months.

    $course = create_course($courseobj);
    echo "[OK] Created paid course: {$cdata['fullname']} (id={$course->id}, £{$cdata['price']})\n";
    $createdPaid[] = ['id' => $course->id, 'price' => $cdata['price'], 'currency' => $cdata['currency']];
}

foreach ($freecourses as $cdata) {
    $existing = $DB->get_record('course', ['shortname' => $cdata['shortname']]);
    if ($existing) {
        echo "[SKIP] Course '{$cdata['shortname']}' already exists (id={$existing->id})\n";
        $createdFree[] = $existing->id;
        continue;
    }
    $courseobj = new \stdClass();
    $courseobj->fullname = $cdata['fullname'];
    $courseobj->shortname = $cdata['shortname'];
    $courseobj->summary = $cdata['summary'];
    $courseobj->summaryformat = FORMAT_HTML;
    $courseobj->category = $cdata['category'];
    $courseobj->numsections = $cdata['numsections'];
    $courseobj->format = 'topics';
    $courseobj->visible = 1;
    $courseobj->enablecompletion = 1;
    $courseobj->showgrades = 1;
    $courseobj->showreports = 1;
    $courseobj->showactivitydates = 1;
    $courseobj->showcompletionconditions = 1;
    $courseobj->startdate = time();

    $course = create_course($courseobj);
    echo "[OK] Created free course: {$cdata['fullname']} (id={$course->id})\n";
    $createdFree[] = $course->id;
}

echo "\n======================================\n";
echo " PHASE 3: CONFIGURE FEE ENROLLMENT\n";
echo "======================================\n\n";

// 6. Create a payment account.
$payaccount = $DB->get_record('payment_accounts', ['name' => 'University PayPal']);
if (!$payaccount) {
    $payaccount = new \stdClass();
    $payaccount->name = 'University PayPal';
    $payaccount->enabled = 1;
    $payaccount->archived = 0;
    $payaccount->contextid = \context_system::instance()->id;
    $payaccount->timecreated = time();
    $payaccount->timemodified = time();
    $payaccountid = $DB->insert_record('payment_accounts', $payaccount);
    echo "[OK] Created payment account 'University PayPal' (id=$payaccountid)\n";
} else {
    $payaccountid = $payaccount->id;
    echo "[SKIP] Payment account 'University PayPal' already exists (id=$payaccountid)\n";
}

// Configure PayPal gateway for this account.
$gwconfig = $DB->get_record('payment_gateways', ['accountid' => $payaccountid, 'gateway' => 'paypal']);
if (!$gwconfig) {
    $gw = new \stdClass();
    $gw->accountid = $payaccountid;
    $gw->gateway = 'paypal';
    $gw->enabled = 1;
    $gw->config = json_encode([
        'clientid' => 'sb-sandbox-demo-client-id',
        'secret' => 'sb-sandbox-demo-secret-key',
        'environment' => 'sandbox',
        'brandname' => 'UK International University',
    ]);
    $gw->timecreated = time();
    $gw->timemodified = time();
    $DB->insert_record('payment_gateways', $gw);
    echo "[OK] Configured PayPal gateway for payment account\n";
} else {
    echo "[SKIP] PayPal gateway already configured\n";
}

// 7. Add fee enrollment to each paid course.
$feeplugin = enrol_get_plugin('fee');
foreach ($createdPaid as $pc) {
    $courseid = $pc['id'];
    $price = $pc['price'];
    $currency = $pc['currency'];

    // Check if fee enrol already exists.
    $existing = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'fee']);
    if ($existing) {
        echo "[SKIP] Fee enrollment already exists for course $courseid\n";
        continue;
    }

    // Add fee enrollment instance.
    $fields = [
        'status'          => ENROL_INSTANCE_ENABLED,
        'cost'            => $price,
        'currency'        => $currency,
        'roleid'          => $DB->get_field('role', 'id', ['shortname' => 'student']),
        'customint1'      => $payaccountid, // Payment account ID.
        'enrolperiod'     => 180 * 86400,   // 6 months access.
    ];
    $feeplugin->add_instance($DB->get_record('course', ['id' => $courseid]), $fields);
    echo "[OK] Added fee enrollment: course $courseid @ £$price\n";
}

// 8. Add self-enrollment to free open courses.
$selfplugin = enrol_get_plugin('self');
foreach ($createdFree as $courseid) {
    $existing = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'self']);
    if ($existing) {
        echo "[SKIP] Self enrollment already exists for course $courseid\n";
        continue;
    }
    $selfplugin->add_instance($DB->get_record('course', ['id' => $courseid]), [
        'status' => ENROL_INSTANCE_ENABLED,
        'roleid' => $DB->get_field('role', 'id', ['shortname' => 'student']),
    ]);
    echo "[OK] Added self-enrollment for free course $courseid\n";
}

// Also ensure guest access is enabled for browsing paid course descriptions.
$guestplugin = enrol_get_plugin('guest');
foreach ($createdPaid as $pc) {
    $courseid = $pc['id'];
    $existing = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'guest']);
    if (!$existing) {
        $guestplugin->add_instance($DB->get_record('course', ['id' => $courseid]), [
            'status' => ENROL_INSTANCE_ENABLED,
        ]);
    }
}
echo "[OK] Guest access enabled on paid courses (browse only)\n";

echo "\n======================================\n";
echo " PHASE 1 COMPLETE\n";
echo "======================================\n";
echo "Created: " . count($createdPaid) . " paid courses, " . count($createdFree) . " free courses\n";
echo "Payment: PayPal sandbox configured\n";
echo "Enrollment: fee + guest on paid, self on free\n";

// Store course IDs for part 2.
$allnew = array_merge(array_column($createdPaid, 'id'), $createdFree);
file_put_contents('/tmp/new_course_ids.json', json_encode([
    'paid' => $createdPaid,
    'free' => $createdFree,
    'catid' => $catid,
    'opencatid' => $opencatid,
]));
echo "\nCourse IDs saved to /tmp/new_course_ids.json for Part 2.\n";
