<?php
/**
 * Full audit of activities, modules, and course sections for the international university build.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');
global $DB;

echo "============================================================\n";
echo "  ACTIVITY & PLUGIN AUDIT\n";
echo "============================================================\n";

// 1. Module availability
echo "\n--- MODULE STATUS ---\n";
$modules = $DB->get_records('modules', [], 'name ASC');
foreach ($modules as $m) {
    $count = $DB->count_records('course_modules', ['module' => $m->id, 'deletioninprogress' => 0]);
    $vis = $m->visible ? 'ON' : 'OFF';
    if (in_array($m->name, ['bigbluebuttonbn','h5pactivity','workshop','glossary','lesson','wiki','feedback','quiz','assign','forum','resource','url','page','label','chat','choice','data','book','lti','scorm','survey','folder'])) {
        echo sprintf("  %-20s  %s  instances: %d\n", $m->name, $vis, $count);
    }
}

// 2. Courses overview
echo "\n--- COURSES ---\n";
$courses = $DB->get_records_sql("SELECT c.id, c.shortname, c.fullname, cc.name as catname, c.category
    FROM {course} c JOIN {course_categories} cc ON cc.id = c.category
    WHERE c.id > 1 ORDER BY c.id");
foreach ($courses as $c) {
    echo sprintf("  [%d] %-12s %-50s  (%s)\n", $c->id, $c->shortname, $c->fullname, $c->catname);
}

// 3. Per-course activity breakdown
echo "\n--- ACTIVITY BREAKDOWN PER COURSE ---\n";
foreach ($courses as $c) {
    $acts = $DB->get_records_sql("
        SELECT m.name, COUNT(*) as cnt
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        WHERE cm.course = ? AND cm.deletioninprogress = 0
        GROUP BY m.name ORDER BY m.name
    ", [$c->id]);
    $summary = [];
    foreach ($acts as $a) {
        $summary[] = "{$a->name}:{$a->cnt}";
    }
    echo sprintf("  [%d] %-12s  %s\n", $c->id, $c->shortname, implode(', ', $summary));
}

// 4. Sections per course (first 8 sections only to keep output manageable)
echo "\n--- SECTIONS PER COURSE (first 8) ---\n";
foreach ($courses as $c) {
    $sections = $DB->get_records_sql("
        SELECT cs.id, cs.section, cs.name, cs.sequence
        FROM {course_sections} cs
        WHERE cs.course = ? ORDER BY cs.section LIMIT 8
    ", [$c->id]);
    echo sprintf("\n  [%d] %s:\n", $c->id, $c->shortname);
    foreach ($sections as $s) {
        $name = $s->name ?: '(unnamed)';
        $mods = $s->sequence ? count(explode(',', $s->sequence)) : 0;
        echo sprintf("    S%d: %-40s  mods:%d\n", $s->section, $name, $mods);
    }
}

// 5. Existing instances of target modules
echo "\n\n--- EXISTING TARGET MODULE INSTANCES ---\n";
$target_mods = ['bigbluebuttonbn','h5pactivity','workshop','glossary','lesson','wiki','feedback'];
foreach ($target_mods as $tmod) {
    $mid = $DB->get_field('modules', 'id', ['name' => $tmod]);
    if (!$mid) { echo "  {$tmod}: MODULE NOT INSTALLED\n"; continue; }
    $instances = $DB->get_records_sql("
        SELECT cm.id cmid, cm.course, cm.section, cm.visible, c.shortname
        FROM {course_modules} cm
        JOIN {course} c ON c.id = cm.course
        WHERE cm.module = ? AND cm.deletioninprogress = 0
        ORDER BY cm.course
    ", [$mid]);
    echo "\n  {$tmod}: " . count($instances) . " total\n";
    foreach ($instances as $inst) {
        $rec = $DB->get_record($tmod, ['id' => $DB->get_field('course_modules', 'instance', ['id' => $inst->cmid])]);
        $iname = $rec ? $rec->name : '?';
        echo "    cmid={$inst->cmid} [{$inst->shortname}] \"{$iname}\"\n";
    }
}

// 6. BBB config
echo "\n--- BBB MODULE CONFIG ---\n";
$bbb = $DB->get_record('modules', ['name' => 'bigbluebuttonbn']);
if ($bbb) {
    echo "  module id={$bbb->id}, visible={$bbb->visible}\n";
    $configs = $DB->get_records_sql("SELECT * FROM {config_plugins} WHERE plugin = 'mod_bigbluebuttonbn' ORDER BY name LIMIT 20");
    foreach ($configs as $cfg) {
        echo "  {$cfg->name} = " . substr($cfg->value, 0, 80) . "\n";
    }
}

// 7. Which H5P content types are installed
echo "\n--- H5P CONTENT TYPES ---\n";
$h5ptypes = $DB->get_records_sql("SELECT id, machinename, title, majorversion, minorversion FROM {h5p_libraries} WHERE runnable = 1 ORDER BY title LIMIT 30");
foreach ($h5ptypes as $ht) {
    echo "  [{$ht->id}] {$ht->machinename} v{$ht->majorversion}.{$ht->minorversion} - {$ht->title}\n";
}
if (empty($h5ptypes)) echo "  (none installed)\n";

echo "\nDone.\n";
