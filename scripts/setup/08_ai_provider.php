<?php
/**
 * Create DeepSeek AI provider instance properly via Manager API
 */
define('CLI_SCRIPT', true);
require('/var/www/html/moodle/config.php');

global $DB;

echo "=== CREATING DEEPSEEK PROVIDER VIA MANAGER API ===\n\n";

// Clean slate
$DB->delete_records('ai_providers');
echo "Cleared ai_providers table.\n";

$manager = \core\di::get(\core_ai\manager::class);

// The classname must be a string of the PHP class
$classname = 'aiprovider_deepseek\\provider';

// Action config uses full class paths as keys
$actionconfig = [
    'core_ai\\aiactions\\generate_text' => ['enabled' => 1],
    'core_ai\\aiactions\\summarise_text' => ['enabled' => 1],
    'core_ai\\aiactions\\explain_text' => ['enabled' => 1],
];

$config = [
    'apikey' => 'sk-7d61cea3934e40aa82e03dd32adf60d0',
];

try {
    $provider = $manager->create_provider_instance(
        classname: $classname,
        name: 'DeepSeek AI',
        enabled: true,
        config: $config,
        actionconfig: $actionconfig,
    );
    echo "Created provider instance successfully!\n";
    echo "  ID: " . $provider->id . "\n";
    echo "  Name: " . $provider->name . "\n";
    echo "  Enabled: " . ($provider->enabled ? 'yes' : 'no') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Verify
echo "\n=== VERIFICATION ===\n";
$records = $DB->get_records('ai_providers');
foreach ($records as $r) {
    echo "  ID={$r->id} name={$r->name} provider={$r->provider} enabled={$r->enabled}\n";
    // Mask API key
    $c = json_decode($r->config, true);
    if (isset($c['apikey'])) $c['apikey'] = 'sk-****' . substr($c['apikey'], -4);
    echo "  config: " . json_encode($c) . "\n";
    echo "  actionconfig: {$r->actionconfig}\n";
}

// Test supported actions
echo "\n=== SUPPORTED ACTIONS ===\n";
try {
    $supported = $manager->get_supported_actions();
    foreach ($supported as $action => $provList) {
        echo "  $action => " . count($provList) . " provider(s)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check providers for course assist
echo "\n=== PROVIDERS FOR ACTIONS ===\n";
try {
    $provForActions = $manager->get_providers_for_actions();
    foreach ($provForActions as $action => $provs) {
        echo "  $action:\n";
        foreach ($provs as $p) {
            echo "    - " . $p->name . " (enabled=" . ($p->enabled ? 'yes' : 'no') . ")\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check if course assist can find a provider
echo "\n=== COURSE ASSIST CHECK ===\n";
try {
    $available = $manager->is_action_available('core_ai\\aiactions\\generate_text', true);
    echo "generate_text available: " . ($available ? 'yes' : 'no') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $available2 = $manager->is_action_available('core_ai\\aiactions\\summarise_text', true);
    echo "summarise_text available: " . ($available2 ? 'yes' : 'no') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $available3 = $manager->is_action_available('core_ai\\aiactions\\explain_text', true);
    echo "explain_text available: " . ($available3 ? 'yes' : 'no') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

purge_all_caches();
echo "\nCaches purged. Done!\n";
