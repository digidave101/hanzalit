<?php
/**
 * dac_add_missing.php
 * Inserts 35 missing DAC Worldwide products into the products table,
 * then links their PDFs in product_documents.
 *
 * SSH: cd ~/public_html/portal/quotation && php dac_add_missing.php
 */
set_time_limit(0);
require_once __DIR__ . '/db.php';
$db = getDB();

$WEB_BASE = 'flyers/dac/';

// ── MISSING DAC PRODUCTS ──────────────────────────────────────────────────
// Fields: model_id, title_only, product_class, key_topic, flyer_filename
$products = [
    // ── MECHANICAL ──────────────────────────────────────────────────────
    ['202-000',    'Fan Balancing Trainer',
     'Learning System', 'Predictive Maintenance / Vibration Analysis',
     '202-000 - fan-balancing-trainer.pdf'],

    ['212-000',    'Motorized Multi-Turn Valve Actuator Training System',
     'Learning System', 'Valves / Actuators',
     '212-000 - motorized-multi-turn-valve-actuator-training-system.pdf'],

    ['220-000',    'Brake/Clutch Trainer',
     'Learning System', 'Mechanical Drive Systems',
     '220-000 - brake-clutch-trainer.pdf'],

    ['225-000',    'Flange Bolt Torquing Trainer',
     'Learning System', 'Mechanical Maintenance',
     '225-000 - flange-bolt-torquing-trainer.pdf'],

    ['241-000',    'Rotary Kiln Maintenance Training System',
     'Learning System', 'Mechanical Maintenance',
     '241-000 - rotary-kiln-maintenance-training-system.pdf'],

    ['277-000',    'Centrifugal Pump Fundamentals Trainer',
     'Learning System', 'Pumps / Fluid Handling',
     '277-000 - centrifugal-pump-fundamentals-trainer.pdf'],

    // ── ELECTRICAL / MOTORS ──────────────────────────────────────────────
    ['408-000',    'Transformer Wiring Training System',
     'Learning System', 'Electrical / Transformers',
     '408-000 - transformer-wiring-training-system.pdf'],

    ['410-000',    'Split Phase/Capacitor Start AC Motor Training System',
     'Learning System', 'Electrical / AC Motors',
     '410-000 - split-phase-capacitor-start-ac-motor-training-system.pdf'],

    ['412-000',    'Three Phase Squirrel Cage Rotor AC Motor Trainer',
     'Learning System', 'Electrical / AC Motors',
     '412-000 - three-phase-squirrel-cage-rotor-ac-motor-trainer.pdf'],

    ['415-000',    'Shunt Wound DC Motor Training System',
     'Learning System', 'Electrical / DC Motors',
     '415-000 - shunt-wound-dc-motor-training-system.pdf'],

    ['416-000',    'Compound Cumulatively Wound DC Motor Training System',
     'Learning System', 'Electrical / DC Motors',
     '416-000 - compound-cumulatively-wound-dc-motor-training-system.pdf'],

    ['417-000',    'Permanent Magnet DC Motor Training System',
     'Learning System', 'Electrical / DC Motors',
     '417-000 - permanent-magnet-dc-motor-training-system.pdf'],

    ['419-000',    'Motor Load Option',
     'Accessory', 'Electrical / Motors',
     '419-000 - motor-load-option.pdf'],

    // ── MOTOR CONTROL ────────────────────────────────────────────────────
    ['420-000',    '1-Phase Motor Control Training System with Manual Starter',
     'Learning System', 'Electrical / Motor Control',
     '420-000 - 1-phase-motor-control-training-system-with-manual-starter.pdf'],

    ['421-000',    '1-Phase Motor Control Training System with Magnetic Starter',
     'Learning System', 'Electrical / Motor Control',
     '421-000 - 1-phase-motor-control-training-system-with-magnetic-starter.pdf'],

    ['422-000',    'Three Phase Motor Control Training System',
     'Learning System', 'Electrical / Motor Control',
     '422-000 - three-phase-motor-control-training-system.pdf'],

    ['423-000',    '3-Phase Motor Control Training System with Magnetic Starter',
     'Learning System', 'Electrical / Motor Control',
     '423-000 - 3-phase-motor-control-training-system-with-magnetic-starter.pdf'],

    ['424-000',    'Low Voltage Magnetic Motor Starter Training',
     'Learning System', 'Electrical / Motor Control',
     '424-000 - low-voltage-magnetic-motor-starter-training.pdf'],

    ['426-000',    'DC Permanent Magnet Motor Control Training',
     'Learning System', 'Electrical / Motor Control',
     '426-000 - dc-permanent-magnet-motor-control-training.pdf'],

    // ── PLC ──────────────────────────────────────────────────────────────
    ['461-000',    'Basic PLC Training System',
     'Learning System', 'Automation / PLC',
     '461-000 - basic-plc-training-system.pdf'],

    ['464-100',    'Traffic Light PLC Application Panel',
     'Accessory', 'Automation / PLC',
     '464-100 - traffic-light-plc-application-panel.pdf'],

    ['464-200',    'Electro-Pneumatic PLC Application Panel',
     'Accessory', 'Automation / PLC',
     '464-200 - electro-pneumatic-plc-application-panel.pdf'],

    ['464-300',    'Electro-Mechanical PLC Application Panel',
     'Accessory', 'Automation / PLC',
     '464-300 - electro-mechanical-plc-application-panel.pdf'],

    ['464-400',    'Analog Temperature Control PLC Application Panel',
     'Accessory', 'Automation / PLC',
     '464-400 - analog-temperature-control-plc-application-panel.pdf'],

    ['491-000',    'Transformer Connections Training System',
     'Learning System', 'Electrical / Transformers',
     '491-000 - transformer-connections-training-system.pdf'],

    // ── PROCESS CONTROL ──────────────────────────────────────────────────
    ['602-000EH',  'Temperature Process Control Training System',
     'Learning System', 'Process Control / Instrumentation',
     '602-000EH - temperature-process-control-training-system.pdf'],

    ['603-SP',     'Smart Process Plant Training System',
     'Learning System', 'Process Control / Instrumentation',
     '603-SP - smart-process-plant-training-system.pdf'],

    ['605-000',    'Analytic Process Control Training System',
     'Learning System', 'Process Control / Instrumentation',
     '605-000 - analytic-process-control-training-system.pdf'],

    ['608-000',    'PID Controller Trainer — Level Control',
     'Learning System', 'Process Control / Instrumentation',
     '608-000 - pid-controller-trainer-level-control.pdf'],

    ['610-000',    'PID Controller Simulator Training System — Level Control',
     'Learning System', 'Process Control / Instrumentation',
     '610-000 - pid-controller-simulator-training-system-level-control.pdf'],

    ['617-000',    'Level Measurement Trainer',
     'Learning System', 'Process Control / Instrumentation',
     '617-000 - level-measurement-trainer.pdf'],

    ['618-000',    'Control Valve Characteristics Training System',
     'Learning System', 'Process Control / Instrumentation',
     '618-000 - control-valve-characteristics-training-system.pdf'],

    ['619-000',    'Flow Measurement Training System',
     'Learning System', 'Process Control / Instrumentation',
     '619-000 - flow-measurement-training-system.pdf'],

    // ── SAFETY ───────────────────────────────────────────────────────────
    ['810-000',    'Electrical Lockout/Tagout Learning System',
     'Learning System', 'Safety / LOTO',
     '810-000 - electrical-lockout-tagout-learning-system.pdf'],

    ['811-000',    'Lock-Out Tag-Out Training System',
     'Learning System', 'Safety / LOTO',
     '811-000 - lock-out-tag-out-training-system.pdf'],
];

// ── INSERT INTO products ──────────────────────────────────────────────────
$insProduct = $db->prepare("
    INSERT INTO products
        (model_id, title_only, manufacturer, product_class, key_topic,
         intl_market_price_note, requires_models)
    VALUES
        (:mid, :title, 'DACW All', :cls, :topic,
         'See Amatrol for Pricing', '[]')
    ON DUPLICATE KEY UPDATE
        title_only    = VALUES(title_only),
        product_class = VALUES(product_class),
        key_topic     = VALUES(key_topic)
");

// ── INSERT INTO product_documents ─────────────────────────────────────────
$insDoc = $db->prepare("
    INSERT INTO product_documents (model_id, combined_pdf, flyer_pdf)
    VALUES (:m, :c, :f)
    ON DUPLICATE KEY UPDATE
        combined_pdf = VALUES(combined_pdf),
        flyer_pdf    = VALUES(flyer_pdf),
        updated_at   = CURRENT_TIMESTAMP
");

echo str_repeat('=', 65) . "\n";
echo "DAC Missing Products — Insert & Link\n";
echo "Products to add: " . count($products) . "\n";
echo str_repeat('=', 65) . "\n\n";

$addedProd = 0;
$linkedDoc = 0;
$errors    = [];

foreach ($products as $p) {
    [$modelId, $title, $cls, $topic, $flyer] = $p;
    $webPath = $WEB_BASE . $flyer;

    // Verify the PDF actually exists on disk before linking
    $diskPath = __DIR__ . '/flyers/dac/' . $flyer;
    $exists   = file_exists($diskPath) ? '✓' : '✗ FILE MISSING';

    // Insert product
    try {
        $insProduct->execute([
            ':mid'   => $modelId,
            ':title' => $title,
            ':cls'   => $cls,
            ':topic' => $topic,
        ]);
        $addedProd++;
    } catch (Exception $e) {
        $errors[] = "PRODUCT $modelId: " . $e->getMessage();
        echo "  ERROR  $modelId — " . $e->getMessage() . "\n";
        continue;
    }

    // Link document
    try {
        $insDoc->execute([
            ':m' => $modelId,
            ':c' => $webPath,
            ':f' => $webPath,
        ]);
        $linkedDoc++;
        echo sprintf("  OK     %-15s %-45s %s\n", $modelId, $title, $exists);
    } catch (Exception $e) {
        $errors[] = "DOC $modelId: " . $e->getMessage();
        echo "  DOC ERR $modelId — " . $e->getMessage() . "\n";
    }
}

// ── SUMMARY ───────────────────────────────────────────────────────────────
$totalProds = (int)$db->query("SELECT COUNT(*) FROM products WHERE manufacturer LIKE 'DACW%'")->fetchColumn();
$totalDocs  = (int)$db->query("SELECT COUNT(*) FROM product_documents")->fetchColumn();

echo "\n" . str_repeat('=', 65) . "\n";
echo "Products inserted/updated : $addedProd\n";
echo "Documents linked          : $linkedDoc\n";
echo "Errors                    : " . count($errors) . "\n";
echo "Total DAC products in DB  : $totalProds\n";
echo "Total product_documents   : $totalDocs\n";
echo str_repeat('=', 65) . "\n";

if ($errors) {
    echo "\nErrors:\n";
    foreach ($errors as $e) echo "  - $e\n";
}

echo "\nDone! All DAC PDFs are now linked.\n";
echo "The quotation engine will now show 📄 PDF buttons for all DAC products.\n";
