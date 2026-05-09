<?php
/**
 * Build a rich, demo-worthy Question Bank for Dr. Wilson's PD-CTD01 (Clinical Trial Design).
 * Creates categorised question categories with diverse question types:
 * - multichoice, truefalse, shortanswer, matching, description
 */
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
global $DB;

$courseid = 12; // PD-CTD01
$ctx = $DB->get_record('context', ['contextlevel' => 50, 'instanceid' => $courseid]);

echo "============================================================\n";
echo " BUILDING QUESTION BANK FOR PD-CTD01 (Course 12)\n";
echo " Context: {$ctx->id}\n";
echo "============================================================\n";

// Helper function to create a question category
function create_qcat($DB, $contextid, $name, $info, $parentid = 0) {
    $cat = new stdClass();
    $cat->name = $name;
    $cat->contextid = $contextid;
    $cat->info = $info;
    $cat->infoformat = 1;
    $cat->stamp = make_unique_id_code();
    $cat->parent = $parentid;
    $cat->sortorder = 999;
    $cat->id = $DB->insert_record('question_categories', $cat);
    return $cat->id;
}

// Helper: create question + bank entry + version
function create_question($DB, $catid, $qtype, $name, $text, $generalfeedback = '') {
    // Question
    $q = new stdClass();
    $q->name = $name;
    $q->questiontext = $text;
    $q->questiontextformat = 1;
    $q->generalfeedback = $generalfeedback;
    $q->generalfeedbackformat = 1;
    $q->defaultmark = 1.0;
    $q->penalty = 0.3333333;
    $q->qtype = $qtype;
    $q->length = 1;
    $q->stamp = make_unique_id_code();
    $q->timecreated = time();
    $q->timemodified = time();
    $q->createdby = 143; // Dr. Wilson
    $q->modifiedby = 143;
    $q->id = $DB->insert_record('question', $q);
    
    // Bank entry
    $be = new stdClass();
    $be->questioncategoryid = $catid;
    $be->idnumber = null;
    $be->ownerid = 143;
    $be->id = $DB->insert_record('question_bank_entries', $be);
    
    // Version
    $qv = new stdClass();
    $qv->questionbankentryid = $be->id;
    $qv->questionid = $q->id;
    $qv->version = 1;
    $qv->status = 'ready';
    $DB->insert_record('question_versions', $qv);
    
    return $q->id;
}

// Helper: add multichoice answers
function add_multichoice($DB, $qid, $answers, $single = 1) {
    foreach ($answers as $ans) {
        $a = new stdClass();
        $a->question = $qid;
        $a->answer = $ans['text'];
        $a->answerformat = 1;
        $a->fraction = $ans['fraction'];
        $a->feedback = isset($ans['feedback']) ? $ans['feedback'] : '';
        $a->feedbackformat = 1;
        $DB->insert_record('question_answers', $a);
    }
    $mc = new stdClass();
    $mc->questionid = $qid;
    $mc->layout = 0;
    $mc->single = $single;
    $mc->shuffleanswers = 1;
    $mc->correctfeedback = 'Correct!';
    $mc->correctfeedbackformat = 1;
    $mc->partiallycorrectfeedback = 'Partially correct.';
    $mc->partiallycorrectfeedbackformat = 1;
    $mc->incorrectfeedback = 'Incorrect.';
    $mc->incorrectfeedbackformat = 1;
    $mc->answernumbering = 'abc';
    $mc->shownumcorrect = 1;
    $mc->showstandardinstruction = 0;
    $DB->insert_record('qtype_multichoice_options', $mc);
}

// Helper: add truefalse
function add_truefalse($DB, $qid, $correct_is_true) {
    $true_frac = $correct_is_true ? 1.0 : 0.0;
    $false_frac = $correct_is_true ? 0.0 : 1.0;
    
    $ta = new stdClass();
    $ta->question = $qid;
    $ta->answer = 'True';
    $ta->answerformat = 0;
    $ta->fraction = $true_frac;
    $ta->feedback = $correct_is_true ? 'Correct!' : 'Incorrect.';
    $ta->feedbackformat = 1;
    $trueid = $DB->insert_record('question_answers', $ta);
    
    $fa = new stdClass();
    $fa->question = $qid;
    $fa->answer = 'False';
    $fa->answerformat = 0;
    $fa->fraction = $false_frac;
    $fa->feedback = $correct_is_true ? 'Incorrect.' : 'Correct!';
    $fa->feedbackformat = 1;
    $falseid = $DB->insert_record('question_answers', $fa);
    
    $tf = new stdClass();
    $tf->question = $qid;
    $tf->trueanswer = $trueid;
    $tf->falseanswer = $falseid;
    $DB->insert_record('question_truefalse', $tf);
}

// Helper: add shortanswer
function add_shortanswer($DB, $qid, $answers) {
    foreach ($answers as $ans) {
        $a = new stdClass();
        $a->question = $qid;
        $a->answer = $ans['text'];
        $a->answerformat = 0;
        $a->fraction = $ans['fraction'];
        $a->feedback = isset($ans['feedback']) ? $ans['feedback'] : '';
        $a->feedbackformat = 1;
        $DB->insert_record('question_answers', $a);
    }
    $sa = new stdClass();
    $sa->questionid = $qid;
    $sa->usecase = 0;
    $DB->insert_record('qtype_shortanswer_options', $sa);
}

// Helper: add matching
function add_matching($DB, $qid, $subquestions) {
    foreach ($subquestions as $sq) {
        $s = new stdClass();
        $s->questionid = $qid;
        $s->questiontext = $sq['question'];
        $s->questiontextformat = 1;
        $s->answertext = $sq['answer'];
        $DB->insert_record('qtype_match_subquestions', $s);
    }
    $mo = new stdClass();
    $mo->questionid = $qid;
    $mo->shuffleanswers = 1;
    $mo->correctfeedback = 'Correct!';
    $mo->correctfeedbackformat = 1;
    $mo->partiallycorrectfeedback = 'Partially correct.';
    $mo->partiallycorrectfeedbackformat = 1;
    $mo->incorrectfeedback = 'Incorrect.';
    $mo->incorrectfeedbackformat = 1;
    $mo->shownumcorrect = 1;
    $DB->insert_record('qtype_match_options', $mo);
}

$created = 0;

// ============================================================
// CATEGORY 1: Regulatory Foundations
// ============================================================
$cat1 = create_qcat($DB, $ctx->id, 'Regulatory Foundations', 'Questions on GCP, ICH, and regulatory bodies');
echo "\nCategory: Regulatory Foundations (id={$cat1})\n";

// MC 1
$qid = create_question($DB, $cat1, 'multichoice', 'ICH-GCP Purpose',
    '<p>What is the primary purpose of ICH-GCP guidelines?</p>',
    'ICH-GCP provides a unified standard to protect trial subjects and ensure data integrity.');
add_multichoice($DB, $qid, [
    ['text' => 'To protect trial subjects\' rights and ensure data integrity', 'fraction' => 1.0, 'feedback' => 'Correct! This is the fundamental purpose.'],
    ['text' => 'To reduce the cost of clinical trials', 'fraction' => 0.0],
    ['text' => 'To speed up the drug approval process', 'fraction' => 0.0],
    ['text' => 'To standardise laboratory procedures only', 'fraction' => 0.0],
]);
echo "  [multichoice] ICH-GCP Purpose\n"; $created++;

// MC 2
$qid = create_question($DB, $cat1, 'multichoice', 'Regulatory Authority Role',
    '<p>Which regulatory authority oversees clinical trials in the United Kingdom?</p>');
add_multichoice($DB, $qid, [
    ['text' => 'MHRA (Medicines and Healthcare products Regulatory Agency)', 'fraction' => 1.0],
    ['text' => 'FDA (Food and Drug Administration)', 'fraction' => 0.0],
    ['text' => 'EMA (European Medicines Agency)', 'fraction' => 0.0],
    ['text' => 'TGA (Therapeutic Goods Administration)', 'fraction' => 0.0],
]);
echo "  [multichoice] Regulatory Authority Role\n"; $created++;

// TF 1
$qid = create_question($DB, $cat1, 'truefalse', 'Helsinki Declaration',
    '<p>The Declaration of Helsinki was first adopted in 1964 and outlines ethical principles for medical research involving human subjects.</p>');
add_truefalse($DB, $qid, true);
echo "  [truefalse] Helsinki Declaration\n"; $created++;

// TF 2
$qid = create_question($DB, $cat1, 'truefalse', 'GCP Monitoring Requirement',
    '<p>Under GCP guidelines, a sponsor is NOT required to monitor clinical trial sites.</p>');
add_truefalse($DB, $qid, false);
echo "  [truefalse] GCP Monitoring Requirement\n"; $created++;

// SA 1
$qid = create_question($DB, $cat1, 'shortanswer', 'ICH Abbreviation',
    '<p>What does ICH stand for? (Write the full name)</p>');
add_shortanswer($DB, $qid, [
    ['text' => 'International Council for Harmonisation', 'fraction' => 1.0],
    ['text' => 'International Conference on Harmonisation', 'fraction' => 1.0],
    ['text' => 'international council for harmonisation', 'fraction' => 1.0],
]);
echo "  [shortanswer] ICH Abbreviation\n"; $created++;

// ============================================================
// CATEGORY 2: Study Design & Methodology
// ============================================================
$cat2 = create_qcat($DB, $ctx->id, 'Study Design & Methodology', 'Questions on trial phases, randomisation, blinding, endpoints');
echo "\nCategory: Study Design & Methodology (id={$cat2})\n";

// MC 3
$qid = create_question($DB, $cat2, 'multichoice', 'Phase I Trial Objective',
    '<p>What is the primary objective of a Phase I clinical trial?</p>');
add_multichoice($DB, $qid, [
    ['text' => 'To assess safety, tolerability, and pharmacokinetics in healthy volunteers', 'fraction' => 1.0],
    ['text' => 'To determine efficacy in a large patient population', 'fraction' => 0.0],
    ['text' => 'To compare treatment with standard of care', 'fraction' => 0.0],
    ['text' => 'To monitor long-term side effects post-marketing', 'fraction' => 0.0],
]);
echo "  [multichoice] Phase I Trial Objective\n"; $created++;

// MC 4
$qid = create_question($DB, $cat2, 'multichoice', 'Double-Blind Design',
    '<p>In a double-blind clinical trial, who is unaware of the treatment assignment?</p>');
add_multichoice($DB, $qid, [
    ['text' => 'Both the investigator and the participant', 'fraction' => 1.0],
    ['text' => 'Only the participant', 'fraction' => 0.0],
    ['text' => 'Only the investigator', 'fraction' => 0.0],
    ['text' => 'The sponsor and the ethics committee', 'fraction' => 0.0],
]);
echo "  [multichoice] Double-Blind Design\n"; $created++;

// MC 5
$qid = create_question($DB, $cat2, 'multichoice', 'Intention-to-Treat Analysis',
    '<p>Intention-to-Treat (ITT) analysis includes:</p>');
add_multichoice($DB, $qid, [
    ['text' => 'All randomised participants regardless of compliance or withdrawal', 'fraction' => 1.0],
    ['text' => 'Only participants who completed the trial per protocol', 'fraction' => 0.0],
    ['text' => 'Only participants with no adverse events', 'fraction' => 0.0],
    ['text' => 'Participants selected by the principal investigator', 'fraction' => 0.0],
]);
echo "  [multichoice] Intention-to-Treat Analysis\n"; $created++;

// Matching 1
$qid = create_question($DB, $cat2, 'match', 'Match Trial Phases',
    '<p>Match each clinical trial phase with its primary purpose.</p>');
add_matching($DB, $qid, [
    ['question' => 'Phase I', 'answer' => 'Safety and dosage in small groups'],
    ['question' => 'Phase II', 'answer' => 'Efficacy and side effects'],
    ['question' => 'Phase III', 'answer' => 'Large-scale efficacy and monitoring'],
    ['question' => 'Phase IV', 'answer' => 'Post-marketing surveillance'],
    ['question' => '', 'answer' => 'Preclinical animal studies'],  // distractor
]);
echo "  [matching] Match Trial Phases\n"; $created++;

// TF
$qid = create_question($DB, $cat2, 'truefalse', 'Randomisation Purpose',
    '<p>Randomisation in clinical trials is used to minimise selection bias and ensure comparability between groups.</p>');
add_truefalse($DB, $qid, true);
echo "  [truefalse] Randomisation Purpose\n"; $created++;

// SA
$qid = create_question($DB, $cat2, 'shortanswer', 'Control Group Type',
    '<p>What type of control uses an inactive substance that looks identical to the active treatment?</p>');
add_shortanswer($DB, $qid, [
    ['text' => 'placebo', 'fraction' => 1.0],
    ['text' => 'Placebo', 'fraction' => 1.0],
    ['text' => 'placebo control', 'fraction' => 1.0],
]);
echo "  [shortanswer] Control Group Type\n"; $created++;

// ============================================================
// CATEGORY 3: Informed Consent & Ethics
// ============================================================
$cat3 = create_qcat($DB, $ctx->id, 'Informed Consent & Ethics', 'Questions on consent processes, ethics committees, vulnerable populations');
echo "\nCategory: Informed Consent & Ethics (id={$cat3})\n";

// MC
$qid = create_question($DB, $cat3, 'multichoice', 'Informed Consent Elements',
    '<p>Which of the following MUST be included in the informed consent document?</p>');
add_multichoice($DB, $qid, [
    ['text' => 'All of the below', 'fraction' => 1.0],
    ['text' => 'Description of potential risks and benefits', 'fraction' => 0.0],
    ['text' => 'Statement that participation is voluntary', 'fraction' => 0.0],
    ['text' => 'Information about alternative treatments', 'fraction' => 0.0],
]);
echo "  [multichoice] Informed Consent Elements\n"; $created++;

$qid = create_question($DB, $cat3, 'multichoice', 'Ethics Committee Purpose',
    '<p>The primary role of an Ethics Committee / IRB in clinical research is to:</p>');
add_multichoice($DB, $qid, [
    ['text' => 'Protect the rights, safety, and well-being of trial participants', 'fraction' => 1.0],
    ['text' => 'Ensure the trial meets its recruitment targets', 'fraction' => 0.0],
    ['text' => 'Approve the marketing of the investigational product', 'fraction' => 0.0],
    ['text' => 'Manage the trial budget', 'fraction' => 0.0],
]);
echo "  [multichoice] Ethics Committee Purpose\n"; $created++;

// TF
$qid = create_question($DB, $cat3, 'truefalse', 'Consent Withdrawal',
    '<p>A participant may withdraw their consent at any time without giving a reason and without penalty.</p>');
add_truefalse($DB, $qid, true);
echo "  [truefalse] Consent Withdrawal\n"; $created++;

// TF
$qid = create_question($DB, $cat3, 'truefalse', 'Vulnerable Populations',
    '<p>Children and pregnant women can never participate in clinical trials under any circumstances.</p>');
add_truefalse($DB, $qid, false);
echo "  [truefalse] Vulnerable Populations\n"; $created++;

// Matching
$qid = create_question($DB, $cat3, 'match', 'Match Ethics Documents',
    '<p>Match each ethics document or guideline with its description.</p>');
add_matching($DB, $qid, [
    ['question' => 'Declaration of Helsinki', 'answer' => 'Ethical principles for medical research on humans'],
    ['question' => 'Belmont Report', 'answer' => 'Respect for persons, beneficence, and justice'],
    ['question' => 'Nuremberg Code', 'answer' => 'First international code for human experimentation'],
    ['question' => 'ICH-E6 (GCP)', 'answer' => 'International standard for clinical trial conduct'],
]);
echo "  [matching] Match Ethics Documents\n"; $created++;

// ============================================================
// CATEGORY 4: Biostatistics & Sample Size
// ============================================================
$cat4 = create_qcat($DB, $ctx->id, 'Biostatistics & Sample Size', 'Questions on statistical concepts in trial design');
echo "\nCategory: Biostatistics & Sample Size (id={$cat4})\n";

$qid = create_question($DB, $cat4, 'multichoice', 'Type I Error',
    '<p>A Type I error in a clinical trial is:</p>');
add_multichoice($DB, $qid, [
    ['text' => 'Incorrectly rejecting the null hypothesis (false positive)', 'fraction' => 1.0],
    ['text' => 'Failing to reject a false null hypothesis (false negative)', 'fraction' => 0.0],
    ['text' => 'A calculation error in the sample size', 'fraction' => 0.0],
    ['text' => 'A protocol deviation', 'fraction' => 0.0],
]);
echo "  [multichoice] Type I Error\n"; $created++;

$qid = create_question($DB, $cat4, 'multichoice', 'Sample Size Factors',
    '<p>Which factors affect the required sample size in a clinical trial? (Select all that apply)</p>');
add_multichoice($DB, $qid, [
    ['text' => 'Expected effect size', 'fraction' => 0.25],
    ['text' => 'Significance level (alpha)', 'fraction' => 0.25],
    ['text' => 'Desired statistical power', 'fraction' => 0.25],
    ['text' => 'Variability in the outcome measure', 'fraction' => 0.25],
    ['text' => 'The principal investigator\'s preference', 'fraction' => 0.0],
], 0); // multi-select
echo "  [multichoice-multi] Sample Size Factors\n"; $created++;

$qid = create_question($DB, $cat4, 'truefalse', 'Statistical Power',
    '<p>Statistical power of 80% means there is an 80% chance of detecting a true treatment effect if it exists.</p>');
add_truefalse($DB, $qid, true);
echo "  [truefalse] Statistical Power\n"; $created++;

$qid = create_question($DB, $cat4, 'shortanswer', 'Significance Level',
    '<p>What is the conventional significance level (alpha) used in most clinical trials? Express as a decimal.</p>');
add_shortanswer($DB, $qid, [
    ['text' => '0.05', 'fraction' => 1.0],
    ['text' => '.05', 'fraction' => 1.0],
    ['text' => '5%', 'fraction' => 0.5],
]);
echo "  [shortanswer] Significance Level\n"; $created++;

// Matching
$qid = create_question($DB, $cat4, 'match', 'Match Statistical Concepts',
    '<p>Match each statistical concept with its definition.</p>');
add_matching($DB, $qid, [
    ['question' => 'p-value', 'answer' => 'Probability of results assuming null hypothesis is true'],
    ['question' => 'Confidence interval', 'answer' => 'Range likely containing the true parameter value'],
    ['question' => 'Statistical power', 'answer' => 'Probability of correctly rejecting a false null hypothesis'],
    ['question' => 'Effect size', 'answer' => 'Magnitude of difference between groups'],
    ['question' => '', 'answer' => 'Number of participants needed'],  // distractor
]);
echo "  [matching] Match Statistical Concepts\n"; $created++;

// ============================================================
// CATEGORY 5: Safety & Adverse Events
// ============================================================
$cat5 = create_qcat($DB, $ctx->id, 'Safety & Adverse Events', 'Questions on safety reporting, SAEs, pharmacovigilance');
echo "\nCategory: Safety & Adverse Events (id={$cat5})\n";

$qid = create_question($DB, $cat5, 'multichoice', 'SAE Definition',
    '<p>A Serious Adverse Event (SAE) is defined as any event that:</p>');
add_multichoice($DB, $qid, [
    ['text' => 'Results in death, hospitalisation, disability, or is life-threatening', 'fraction' => 1.0],
    ['text' => 'Causes mild discomfort to the participant', 'fraction' => 0.0],
    ['text' => 'Is expected based on the drug\'s known profile', 'fraction' => 0.0],
    ['text' => 'Occurs only in the control group', 'fraction' => 0.0],
]);
echo "  [multichoice] SAE Definition\n"; $created++;

$qid = create_question($DB, $cat5, 'multichoice', 'SAE Reporting Timeline',
    '<p>Serious Adverse Events must be reported to the sponsor within:</p>');
add_multichoice($DB, $qid, [
    ['text' => '24 hours of the investigator becoming aware', 'fraction' => 1.0],
    ['text' => '7 calendar days', 'fraction' => 0.0],
    ['text' => '30 calendar days', 'fraction' => 0.0],
    ['text' => 'At the next scheduled monitoring visit', 'fraction' => 0.0],
]);
echo "  [multichoice] SAE Reporting Timeline\n"; $created++;

$qid = create_question($DB, $cat5, 'truefalse', 'SUSAR Reporting',
    '<p>A SUSAR (Suspected Unexpected Serious Adverse Reaction) must be reported to the regulatory authority even if the trial has ended.</p>');
add_truefalse($DB, $qid, true);
echo "  [truefalse] SUSAR Reporting\n"; $created++;

$qid = create_question($DB, $cat5, 'shortanswer', 'SAE Abbreviation',
    '<p>What does SAE stand for in clinical trial safety reporting?</p>');
add_shortanswer($DB, $qid, [
    ['text' => 'Serious Adverse Event', 'fraction' => 1.0],
    ['text' => 'serious adverse event', 'fraction' => 1.0],
]);
echo "  [shortanswer] SAE Abbreviation\n"; $created++;

// Matching
$qid = create_question($DB, $cat5, 'match', 'Match AE Severity',
    '<p>Match each adverse event classification with its description.</p>');
add_matching($DB, $qid, [
    ['question' => 'Mild (Grade 1)', 'answer' => 'Awareness of symptoms but easily tolerated'],
    ['question' => 'Moderate (Grade 2)', 'answer' => 'Discomfort enough to interfere with usual activity'],
    ['question' => 'Severe (Grade 3)', 'answer' => 'Incapacitating; unable to perform usual activities'],
    ['question' => 'Life-threatening (Grade 4)', 'answer' => 'Immediate risk of death'],
    ['question' => '', 'answer' => 'No symptoms reported'],  // distractor
]);
echo "  [matching] Match AE Severity\n"; $created++;

echo "\n============================================================\n";
echo " SUMMARY\n";
echo "============================================================\n";
echo "  Categories created: 5\n";
echo "  Questions created: {$created}\n";
echo "  Question types: multichoice, truefalse, shortanswer, matching\n";

// Final stats
$total = $DB->count_records_sql("
    SELECT COUNT(DISTINCT q.id) FROM {question} q
    JOIN {question_versions} qv ON qv.questionid = q.id
    JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
    JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
    WHERE qc.contextid = ?
", [$ctx->id]);
echo "  Total questions in PD-CTD01 bank: {$total}\n";

$all_cats = $DB->get_records('question_categories', ['contextid' => $ctx->id], 'id ASC');
echo "\n  Category breakdown:\n";
foreach ($all_cats as $ac) {
    $cnt = $DB->count_records_sql("
        SELECT COUNT(*) FROM {question} q
        JOIN {question_versions} qv ON qv.questionid = q.id
        JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
        WHERE qbe.questioncategoryid = ?
    ", [$ac->id]);
    echo "    '{$ac->name}': {$cnt} questions\n";
}

echo "\nDone.\n";
