<?php
/**
 * Fix block_xp levelsdata format.
 *
 * The levelsdata was stored with a 'levels' key but the plugin expects 'xp' key
 * (as an array of XP thresholds), plus 'name' and 'desc' arrays keyed by level number.
 */

define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');

ob_implicit_flush(true);

function logmsg($msg) {
    $ts = date('Y-m-d H:i:s');
    echo "[$ts] $msg\n";
    file_put_contents('/tmp/fix_xp_levelsdata.log', "[$ts] $msg\n", FILE_APPEND);
}

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logmsg("FATAL: {$err['message']} in {$err['file']}:{$err['line']}");
    }
});

try {
    global $DB;

    // The correct levelsdata format (matching algo_levels_info::make_from_defaults)
    // 'xp' is 0-indexed array of XP thresholds for 5 levels
    // 'name' is keyed by 1-based level number
    // 'desc' is keyed by 1-based level number
    $correct_levelsdata = json_encode([
        'v' => 2,
        'algo' => [
            'method' => 'relative',
            'base' => 120,
            'coef' => 1.3,
            'incr' => 40,
        ],
        'xp' => [0, 120, 300, 550, 900],
        'name' => [
            1 => 'Beginner',
            2 => 'Learner',
            3 => 'Scholar',
            4 => 'Achiever',
            5 => 'Expert',
        ],
        'desc' => [
            1 => 'Just getting started',
            2 => 'Making progress',
            3 => 'Gaining knowledge',
            4 => 'Almost there',
            5 => 'Mastery achieved',
        ],
    ]);

    logmsg("Correct levelsdata JSON: $correct_levelsdata");

    // Get all block_xp_config records with non-empty levelsdata
    $configs = $DB->get_records_select('block_xp_config', "levelsdata IS NOT NULL AND levelsdata != ''");
    logmsg("Found " . count($configs) . " block_xp_config records with levelsdata");

    $fixed = 0;
    foreach ($configs as $config) {
        $current = json_decode($config->levelsdata, true);
        $needs_fix = false;

        if ($current === null) {
            logmsg("  Course {$config->courseid}: levelsdata is not valid JSON, will fix");
            $needs_fix = true;
        } elseif (!isset($current['xp'])) {
            logmsg("  Course {$config->courseid}: levelsdata missing 'xp' key (has keys: " . implode(',', array_keys($current)) . "), will fix");
            $needs_fix = true;
        } elseif (!is_array($current['xp'])) {
            logmsg("  Course {$config->courseid}: 'xp' is not an array, will fix");
            $needs_fix = true;
        }

        if ($needs_fix) {
            $DB->set_field('block_xp_config', 'levelsdata', $correct_levelsdata, ['courseid' => $config->courseid]);
            logmsg("  Course {$config->courseid}: FIXED");
            $fixed++;
        } else {
            logmsg("  Course {$config->courseid}: OK (already has 'xp' key)");
        }
    }

    logmsg("Fixed $fixed out of " . count($configs) . " records");

    // Purge caches
    purge_all_caches();
    logmsg("Caches purged");

    logmsg("=== DONE ===");

} catch (Throwable $e) {
    logmsg("ERROR: " . $e->getMessage());
    logmsg("TRACE: " . $e->getTraceAsString());
    exit(1);
}
