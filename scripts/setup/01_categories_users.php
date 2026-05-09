<?php
// Part 1: Categories, Users (teachers + 50 students)
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

echo "===== PART 1: CATEGORIES + USERS =====\n\n";

// -------------------------------------------------------
// STEP 1: Create category hierarchy
// -------------------------------------------------------
echo "--- Step 1: Categories ---\n";

// Create "Health Sciences" sub-cat under "Distance Education Unit" (ID:1)
$healthsci = $DB->get_record('course_categories', ['name' => 'Health Sciences', 'parent' => 1]);
if (!$healthsci) {
    $cat = new stdClass();
    $cat->name = 'Health Sciences';
    $cat->parent = 1;
    $cat->description = 'Clinical and medical education programmes delivered through distance learning.';
    $cat->descriptionformat = 1;
    $cat->sortorder = 10000;
    $cat->timemodified = time();
    $cat->depth = 2;
    $catid = $DB->insert_record('course_categories', $cat);
    $DB->set_field('course_categories', 'path', '/1/' . $catid, ['id' => $catid]);
    $healthsci = $DB->get_record('course_categories', ['id' => $catid]);
    // Create context
    context_coursecat::instance($catid);
    echo "  Created: Health Sciences (ID:$catid) under Distance Education Unit\n";
} else {
    echo "  Exists: Health Sciences (ID:$healthsci->id)\n";
}

// Move existing courses to Health Sciences
foreach ([2, 3] as $cid) {
    $DB->set_field('course', 'category', $healthsci->id, ['id' => $cid]);
    echo "  Moved course $cid to Health Sciences\n";
}
// Update course counts
$DB->set_field('course_categories', 'coursecount', 0, ['id' => 1]);
$DB->set_field('course_categories', 'coursecount', 2, ['id' => $healthsci->id]);

// Create "Faculty of Sciences" top-level
$facsciences = $DB->get_record('course_categories', ['name' => 'Faculty of Sciences', 'parent' => 0]);
if (!$facsciences) {
    $cat = new stdClass();
    $cat->name = 'Faculty of Sciences';
    $cat->parent = 0;
    $cat->description = 'Science programmes including Physics, Chemistry, and Mathematics.';
    $cat->descriptionformat = 1;
    $cat->sortorder = 20000;
    $cat->timemodified = time();
    $cat->depth = 1;
    $catid = $DB->insert_record('course_categories', $cat);
    $DB->set_field('course_categories', 'path', '/' . $catid, ['id' => $catid]);
    $facsciences = $DB->get_record('course_categories', ['id' => $catid]);
    context_coursecat::instance($catid);
    echo "  Created: Faculty of Sciences (ID:$catid)\n";
} else {
    echo "  Exists: Faculty of Sciences (ID:$facsciences->id)\n";
}

// Create "Physics" under Faculty of Sciences
$physics = $DB->get_record('course_categories', ['name' => 'Physics', 'parent' => $facsciences->id]);
if (!$physics) {
    $cat = new stdClass();
    $cat->name = 'Physics';
    $cat->parent = $facsciences->id;
    $cat->description = 'BSc Physics programme aligned with QAA Subject Benchmark standards.';
    $cat->descriptionformat = 1;
    $cat->sortorder = 20100;
    $cat->timemodified = time();
    $cat->depth = 2;
    $catid = $DB->insert_record('course_categories', $cat);
    $DB->set_field('course_categories', 'path', $facsciences->path . '/' . $catid, ['id' => $catid]);
    $physics = $DB->get_record('course_categories', ['id' => $catid]);
    context_coursecat::instance($catid);
    echo "  Created: Physics (ID:$catid)\n";
} else {
    echo "  Exists: Physics (ID:$physics->id)\n";
}

// Create Year 1, 2, 3 under Physics
$yearids = [];
foreach ([1, 2, 3] as $yr) {
    $yrname = "Year $yr";
    $existing = $DB->get_record('course_categories', ['name' => $yrname, 'parent' => $physics->id]);
    if (!$existing) {
        $cat = new stdClass();
        $cat->name = $yrname;
        $cat->parent = $physics->id;
        $cat->description = "Year $yr Physics modules.";
        $cat->descriptionformat = 1;
        $cat->sortorder = 20100 + ($yr * 10);
        $cat->timemodified = time();
        $cat->depth = 3;
        $catid = $DB->insert_record('course_categories', $cat);
        $DB->set_field('course_categories', 'path', $physics->path . '/' . $catid, ['id' => $catid]);
        context_coursecat::instance($catid);
        $yearids[$yr] = $catid;
        echo "  Created: $yrname (ID:$catid)\n";
    } else {
        $yearids[$yr] = $existing->id;
        echo "  Exists: $yrname (ID:$existing->id)\n";
    }
}

// Fix category counts for parent categories
fix_course_sortorder();

echo "\nCategory IDs: Health Sciences=$healthsci->id, Faculty=$facsciences->id, Physics=$physics->id, Y1=$yearids[1], Y2=$yearids[2], Y3=$yearids[3]\n";

// -------------------------------------------------------
// STEP 2: Create teachers
// -------------------------------------------------------
echo "\n--- Step 2: Teachers ---\n";

$teachers = [
    ['username' => 'dr.james.whitfield', 'firstname' => 'James', 'lastname' => 'Whitfield', 'email' => 'j.whitfield@university.ac.uk'],
    ['username' => 'dr.sarah.pemberton', 'firstname' => 'Sarah', 'lastname' => 'Pemberton', 'email' => 's.pemberton@university.ac.uk'],
    ['username' => 'prof.richard.hartley', 'firstname' => 'Richard', 'lastname' => 'Hartley', 'email' => 'r.hartley@university.ac.uk'],
    ['username' => 'dr.emma.blackwood', 'firstname' => 'Emma', 'lastname' => 'Blackwood', 'email' => 'e.blackwood@university.ac.uk'],
    ['username' => 'dr.thomas.greenway', 'firstname' => 'Thomas', 'lastname' => 'Greenway', 'email' => 't.greenway@university.ac.uk'],
];

$teacherids = [];
foreach ($teachers as $t) {
    $existing = $DB->get_record('user', ['username' => $t['username']]);
    if ($existing) {
        $teacherids[$t['username']] = $existing->id;
        echo "  Exists: {$t['username']} (ID:{$existing->id})\n";
        continue;
    }
    $user = new stdClass();
    $user->username = $t['username'];
    $user->firstname = $t['firstname'];
    $user->lastname = $t['lastname'];
    $user->email = $t['email'];
    $user->password = hash_internal_user_password('Teacher@2026');
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->timecreated = time();
    $user->timemodified = time();
    $user->city = 'London';
    $user->country = 'GB';
    $user->lang = 'en';
    $uid = $DB->insert_record('user', $user);
    $teacherids[$t['username']] = $uid;
    context_user::instance($uid);
    echo "  Created: {$t['username']} (ID:$uid)\n";
}

// -------------------------------------------------------
// STEP 3: Create 50 students
// -------------------------------------------------------
echo "\n--- Step 3: Students (50) ---\n";

$students = [
    // Group A (10) - will complete all 6 courses
    ['oliver.smith', 'Oliver', 'Smith', 'A'],
    ['amelia.jones', 'Amelia', 'Jones', 'A'],
    ['george.williams', 'George', 'Williams', 'A'],
    ['isla.brown', 'Isla', 'Brown', 'A'],
    ['harry.taylor', 'Harry', 'Taylor', 'A'],
    ['olivia.davies', 'Olivia', 'Davies', 'A'],
    ['jack.wilson', 'Jack', 'Wilson', 'A'],
    ['sophia.evans', 'Sophia', 'Evans', 'A'],
    ['charlie.thomas', 'Charlie', 'Thomas', 'A'],
    ['emily.roberts', 'Emily', 'Roberts', 'A'],
    // Group B (10) - complete courses 1-2, in progress 3-4
    ['freddie.johnson', 'Freddie', 'Johnson', 'B'],
    ['poppy.walker', 'Poppy', 'Walker', 'B'],
    ['alfie.robinson', 'Alfie', 'Robinson', 'B'],
    ['lily.wright', 'Lily', 'Wright', 'B'],
    ['oscar.thompson', 'Oscar', 'Thompson', 'B'],
    ['mia.white', 'Mia', 'White', 'B'],
    ['leo.hughes', 'Leo', 'Hughes', 'B'],
    ['grace.green', 'Grace', 'Green', 'B'],
    ['arthur.hall', 'Arthur', 'Hall', 'B'],
    ['chloe.wood', 'Chloe', 'Wood', 'B'],
    // Group C (10) - complete courses 1-2, started 3-4
    ['henry.lewis', 'Henry', 'Lewis', 'C'],
    ['ava.harris', 'Ava', 'Harris', 'C'],
    ['william.clark', 'William', 'Clark', 'C'],
    ['ella.morton', 'Ella', 'Morton', 'C'],
    ['james.patel', 'James', 'Patel', 'C'],
    ['jessica.martin', 'Jessica', 'Martin', 'C'],
    ['noah.jackson', 'Noah', 'Jackson', 'C'],
    ['ruby.moore', 'Ruby', 'Moore', 'C'],
    ['ethan.king', 'Ethan', 'King', 'C'],
    ['florence.lee', 'Florence', 'Lee', 'C'],
    // Group D (10) - in progress courses 3-4, not started 5-6
    ['lucas.scott', 'Lucas', 'Scott', 'D'],
    ['evie.turner', 'Evie', 'Turner', 'D'],
    ['jacob.bennett', 'Jacob', 'Bennett', 'D'],
    ['daisy.baker', 'Daisy', 'Baker', 'D'],
    ['edward.phillips', 'Edward', 'Phillips', 'D'],
    ['rosie.campbell', 'Rosie', 'Campbell', 'D'],
    ['max.parker', 'Max', 'Parker', 'D'],
    ['millie.cooper', 'Millie', 'Cooper', 'D'],
    ['samuel.mitchell', 'Samuel', 'Mitchell', 'D'],
    ['isabella.reed', 'Isabella', 'Reed', 'D'],
    // Group E (10) - complete courses 1-4, in progress 5-6
    ['benjamin.morris', 'Benjamin', 'Morris', 'E'],
    ['sienna.rogers', 'Sienna', 'Rogers', 'E'],
    ['alexander.cook', 'Alexander', 'Cook', 'E'],
    ['freya.morgan', 'Freya', 'Morgan', 'E'],
    ['daniel.bailey', 'Daniel', 'Bailey', 'E'],
    ['phoebe.price', 'Phoebe', 'Price', 'E'],
    ['joseph.griffiths', 'Joseph', 'Griffiths', 'E'],
    ['willow.ward', 'Willow', 'Ward', 'E'],
    ['sebastian.fox', 'Sebastian', 'Fox', 'E'],
    ['elsie.russell', 'Elsie', 'Russell', 'E'],
];

$studentids = []; // username => [id, group]
$groupmap = ['A' => [], 'B' => [], 'C' => [], 'D' => [], 'E' => []];
$cities = ['London', 'Manchester', 'Birmingham', 'Bristol', 'Leeds', 'Edinburgh', 'Glasgow', 'Liverpool', 'Oxford', 'Cambridge'];

foreach ($students as $idx => $s) {
    $username = $s[0];
    $existing = $DB->get_record('user', ['username' => $username]);
    if ($existing) {
        $studentids[$username] = ['id' => $existing->id, 'group' => $s[3]];
        $groupmap[$s[3]][] = $existing->id;
        echo "  Exists: $username (ID:{$existing->id}) Group:{$s[3]}\n";
        continue;
    }
    $user = new stdClass();
    $user->username = $username;
    $user->firstname = $s[1];
    $user->lastname = $s[2];
    $user->email = $username . '@student.university.ac.uk';
    $user->password = hash_internal_user_password('Student@2026');
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->timecreated = time();
    $user->timemodified = time();
    $user->city = $cities[$idx % count($cities)];
    $user->country = 'GB';
    $user->lang = 'en';
    $uid = $DB->insert_record('user', $user);
    context_user::instance($uid);
    $studentids[$username] = ['id' => $uid, 'group' => $s[3]];
    $groupmap[$s[3]][] = $uid;
    echo "  Created: $username (ID:$uid) Group:{$s[3]}\n";
}

echo "\n--- Summary ---\n";
echo "Teachers: " . count($teacherids) . "\n";
echo "Students: " . count($studentids) . "\n";
foreach ($groupmap as $g => $ids) {
    echo "  Group $g: " . count($ids) . " students\n";
}

// Save IDs to temp file for next scripts
$data = [
    'categories' => [
        'healthsci' => $healthsci->id,
        'facsciences' => $facsciences->id,
        'physics' => $physics->id,
        'year1' => $yearids[1],
        'year2' => $yearids[2],
        'year3' => $yearids[3],
    ],
    'teachers' => $teacherids,
    'students' => $studentids,
    'groups' => $groupmap,
];
file_put_contents('/tmp/moodle_setup_ids.json', json_encode($data, JSON_PRETTY_PRINT));
echo "\nSaved IDs to /tmp/moodle_setup_ids.json\n";
echo "\nPart 1 complete!\n";
