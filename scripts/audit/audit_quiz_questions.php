<?php
/**
 * Audit all quizzes: check question slots, question types, and identify problems.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
global $DB;

echo "=== ALL QUIZZES & THEIR QUESTIONS ===\n\n";

$quizzes = $DB->get_records_sql(
    "SELECT q.id, q.name, q.course, c.shortname
     FROM {quiz} q
     JOIN {course} c ON c.id = q.course
     ORDER BY q.course, q.id"
);

// Get installed question types
$qtypes = $DB->get_records('question_categories', [], '', 'DISTINCT id');
echo "--- Installed question types (qtype_*) ---\n";
$installedTypes = [];
$pluginDir = '/var/www/moodle/public/question/type/';
if (is_dir($pluginDir)) {
    $dirs = scandir($pluginDir);
    foreach ($dirs as $d) {
        if ($d !== '.' && $d !== '..' && is_dir($pluginDir . $d)) {
            $installedTypes[] = $d;
        }
    }
}
echo "Installed: " . implode(', ', $installedTypes) . "\n\n";

$totalProblems = 0;
foreach ($quizzes as $quiz) {
    echo "--- Quiz {$quiz->id}: {$quiz->name} (Course {$quiz->course}/{$quiz->shortname}) ---\n";

    // Get quiz slots
    $slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id], 'slot ASC');
    echo "  Slots: " . count($slots) . "\n";

    if (empty($slots)) {
        echo "  [WARN] No questions in this quiz!\n\n";
        continue;
    }

    foreach ($slots as $slot) {
        // Get the question reference
        $qref = $DB->get_record_sql(
            "SELECT qr.*, qbe.id as bankentryid
             FROM {question_references} qr
             JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
             WHERE qr.component = 'mod_quiz'
               AND qr.questionarea = 'slot'
               AND qr.itemid = ?",
            [$slot->id]
        );

        if (!$qref) {
            echo "  Slot {$slot->slot}: NO QUESTION REFERENCE FOUND\n";
            $totalProblems++;
            continue;
        }

        // Get the question version
        $qv = $DB->get_record_sql(
            "SELECT qv.*, q.id as questionid, q.qtype, q.name as qname
             FROM {question_versions} qv
             JOIN {question} q ON q.id = qv.questionid
             WHERE qv.questionbankentryid = ?
             ORDER BY qv.version DESC
             LIMIT 1",
            [$qref->questionbankentryid]
        );

        if (!$qv) {
            echo "  Slot {$slot->slot}: QUESTION VERSION NOT FOUND (bankentry={$qref->questionbankentryid})\n";
            $totalProblems++;
            continue;
        }

        $typeOk = in_array($qv->qtype, $installedTypes) ? 'OK' : 'INVALID';
        if ($typeOk !== 'OK') $totalProblems++;
        echo "  Slot {$slot->slot}: q={$qv->questionid} type={$qv->qtype} [{$typeOk}] \"{$qv->qname}\"\n";
    }
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Total quizzes: " . count($quizzes) . "\n";
echo "Total problems: {$totalProblems}\n";

// Also check for orphaned question references
echo "\n=== QUESTION TYPE DISTRIBUTION ===\n";
$typeDist = $DB->get_records_sql(
    "SELECT q.qtype, COUNT(*) as cnt
     FROM {question} q
     GROUP BY q.qtype
     ORDER BY cnt DESC"
);
foreach ($typeDist as $td) {
    $installed = in_array($td->qtype, $installedTypes) ? '' : ' [NOT INSTALLED]';
    echo "  {$td->qtype}: {$td->cnt}{$installed}\n";
}
