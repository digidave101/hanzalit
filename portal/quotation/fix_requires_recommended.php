<?php
/**
 * fix_requires_recommended.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Run via browser: https://ti-kitmeer.com/portal/quotation/fix_requires_recommended.php
 * Run via SSH:     php ~/public_html/portal/quotation/fix_requires_recommended.php
 *
 * What this script does:
 *  1. Adds a `recommended_models` TEXT column to products (if not already there)
 *  2. Fixes the backwards entry: 41213 currently has ["950-PM1"] in requires_models — CLEARED
 *  3. Parses title_description for ALL products where requires_models IS NULL
 *     → Extracts model IDs mentioned after "Requires" keyword
 *     → Writes them into requires_models as JSON array
 *  4. Parses title_description for ALL products for "recommended" mentions
 *     → Writes extracted model IDs into recommended_models as JSON array
 *  5. Reports a full summary of what was changed
 * ─────────────────────────────────────────────────────────────────────────────
 */

$CLI = (php_sapi_name() === 'cli');

if (!$CLI) {
    session_start();
    $ADMINS = ['dhanzal'=>'aA292199','fdegheidy'=>'Winner#1'];
    if (isset($_POST['u'])) {
        if (isset($ADMINS[$_POST['u']]) && $ADMINS[$_POST['u']] === $_POST['p'])
            $_SESSION['frr_auth'] = true;
        else header('Location: ?err=1');
    }
    if (empty($_SESSION['frr_auth'])) { showLogin($_GET['err']??''); exit; }
    echo '<!DOCTYPE html><html><head><title>Fix Requires / Recommended</title>
    <style>
      body{font-family:monospace;background:#060b12;color:#c8dde8;padding:24px;white-space:pre-wrap;font-size:.82rem;line-height:1.6;}
      .ok{color:#2dd4a0}.warn{color:#d4a020}.err{color:#e08080}.head{color:#5ab0e8;font-weight:bold}.dim{color:#4a6a82}
    </style></head><body>';
}

define('BASE', dirname(__FILE__) . '/');
require_once BASE . 'db.php';
set_time_limit(0);

function p(string $msg, string $cls = '') {
    global $CLI;
    if ($CLI) echo strip_tags($msg) . "\n";
    else echo '<span' . ($cls ? " class=\"$cls\"" : '') . '>' . htmlspecialchars($msg) . '</span>' . "\n";
    if (!$CLI) { ob_flush(); flush(); }
}

$db = getDB();

// ── STEP 1: Add recommended_models column if missing ─────────────────────
p("=== STEP 1: Schema — add recommended_models column ===", 'head');
$cols = $db->query("SHOW COLUMNS FROM products LIKE 'recommended_models'")->fetchAll();
if (empty($cols)) {
    $db->exec("ALTER TABLE products ADD COLUMN recommended_models TEXT DEFAULT NULL AFTER requires_models");
    p("✓ Added recommended_models column", 'ok');
} else {
    p("✓ recommended_models column already exists — skipped", 'dim');
}

// ── STEP 2: Fix backwards entry for 41213 ────────────────────────────────
p("\n=== STEP 2: Fix backwards requires entry for 41213 ===", 'head');
$stmt = $db->prepare("SELECT requires_models FROM products WHERE model_id = '41213'");
$stmt->execute();
$row41213 = $stmt->fetch();
if ($row41213 && $row41213['requires_models']) {
    $current = json_decode($row41213['requires_models'], true);
    p("  Current requires_models for 41213: " . json_encode($current), 'warn');
    // 41213 is a Hand Tool Package — it doesn't require the pump system; the pump requires IT
    $db->exec("UPDATE products SET requires_models = NULL WHERE model_id = '41213'");
    p("✓ Cleared requires_models for 41213 (it is the required item, not the parent)", 'ok');
} else {
    p("  41213 requires_models is already NULL — skipped", 'dim');
}

// ── STEP 3: Parse requires from title_description ────────────────────────
p("\n=== STEP 3: Parse 'Requires' from title_description ===", 'head');

/**
 * Extract model IDs following "Requires" keyword in product description text.
 * Handles patterns like:
 *   "Requires 41213 Hand Tool Package"
 *   "Requires 17539 Oscilloscope or equivalent"
 *   "Requires table 82-610 Mobile Technology Workstation"
 *   "Requires 41213 and 82-610"
 *   "41213 strongly recommended"
 *   "Requires: 41213, 17539"
 */
function extractRequiresModels(string $text): array {
    $models = [];
    // Match "Requires" (case-insensitive) followed by optional colon/space then model IDs
    // Model IDs are alphanumeric with dashes, at least 4 chars, but skip pure words like "table", "a"
    // Pattern: word boundary, digits or alphanum-dash pattern starting with digit
    if (preg_match_all('/\bRequires[\s:,]+([^\n.]+)/i', $text, $sections)) {
        foreach ($sections[1] as $section) {
            // Extract things that look like model numbers: start with digit, contain only alnum/dash, 4+ chars
            if (preg_match_all('/\b(\d[A-Z0-9\-]{3,})\b/i', $section, $found)) {
                foreach ($found[1] as $candidate) {
                    // Exclude things that look like years (4 digit numbers 1900-2099)
                    if (preg_match('/^(19|20)\d{2}$/', $candidate)) continue;
                    // Exclude pure numbers less than 4 digits
                    if (preg_match('/^\d+$/', $candidate) && strlen($candidate) < 4) continue;
                    $models[] = strtoupper(trim($candidate, '-'));
                }
            }
        }
    }
    return array_values(array_unique($models));
}

function extractRecommendedModels(string $text): array {
    $models = [];
    // Match "recommended" mentions with model IDs nearby
    // Patterns: "82-610 strongly recommended", "Recommended: 41213", "17539 ... recommended"
    if (preg_match_all('/\b(?:strongly\s+)?recommended[\s:,]+([^\n.]+)/i', $text, $sections)) {
        foreach ($sections[1] as $section) {
            if (preg_match_all('/\b(\d[A-Z0-9\-]{3,})\b/i', $section, $found)) {
                foreach ($found[1] as $c) {
                    if (preg_match('/^(19|20)\d{2}$/', $c)) continue;
                    $models[] = strtoupper(trim($c, '-'));
                }
            }
        }
    }
    // Also catch "[model_id] strongly recommended" (model before keyword)
    if (preg_match_all('/\b(\d[A-Z0-9\-]{3,})\b[^.]*\bstrongly\s+recommended\b/i', $text, $found)) {
        foreach ($found[1] as $c) {
            if (!preg_match('/^(19|20)\d{2}$/', $c)) $models[] = strtoupper(trim($c, '-'));
        }
    }
    return array_values(array_unique($models));
}

// Load all products where requires_models is NULL but description has "Requires"
$stmt = $db->query(
    "SELECT model_id, title_description, requires_models 
     FROM products 
     WHERE requires_models IS NULL 
       AND title_description LIKE '%Requires%'"
);
$toProcess = $stmt->fetchAll();
p("Found " . count($toProcess) . " products with NULL requires_models but 'Requires' in description");

$reqUpdated = 0; $reqSkipped = 0; $reqExamples = [];
$updateReq = $db->prepare("UPDATE products SET requires_models = :rm WHERE model_id = :mid");

foreach ($toProcess as $row) {
    $models = extractRequiresModels($row['title_description'] ?? '');
    if (empty($models)) {
        $reqSkipped++;
        continue;
    }
    $updateReq->execute([':rm' => json_encode($models), ':mid' => $row['model_id']]);
    $reqUpdated++;
    if (count($reqExamples) < 10) {
        $reqExamples[] = "  " . $row['model_id'] . " → " . json_encode($models);
    }
}

p("✓ Updated requires_models for $reqUpdated products", 'ok');
p("  Skipped $reqSkipped (no parseable model IDs found in Requires text)", 'dim');
if ($reqExamples) {
    p("\n  Sample updates (first 10):", 'dim');
    foreach ($reqExamples as $ex) p($ex, 'ok');
}

// ── STEP 4: Parse recommended from ALL products ───────────────────────────
p("\n=== STEP 4: Parse 'Recommended' from title_description ===", 'head');

$stmt = $db->query(
    "SELECT model_id, title_description 
     FROM products 
     WHERE title_description LIKE '%recommended%'"
);
$recRows = $stmt->fetchAll();
p("Found " . count($recRows) . " products with 'recommended' in description");

$recUpdated = 0; $recSkipped = 0; $recExamples = [];
$updateRec = $db->prepare("UPDATE products SET recommended_models = :rm WHERE model_id = :mid");

foreach ($recRows as $row) {
    $models = extractRecommendedModels($row['title_description'] ?? '');
    if (empty($models)) { $recSkipped++; continue; }
    $updateRec->execute([':rm' => json_encode($models), ':mid' => $row['model_id']]);
    $recUpdated++;
    if (count($recExamples) < 10) {
        $recExamples[] = "  " . $row['model_id'] . " → " . json_encode($models);
    }
}

p("✓ Updated recommended_models for $recUpdated products", 'ok');
p("  Skipped $recSkipped (no parseable model IDs in recommended text)", 'dim');
if ($recExamples) {
    p("\n  Sample updates (first 10):", 'dim');
    foreach ($recExamples as $ex) p($ex, 'ok');
}

// ── STEP 5: Verify the two products from the original bug report ──────────
p("\n=== STEP 5: Verification — original bug report products ===", 'head');
$verify = $db->query(
    "SELECT model_id, requires_models, recommended_models 
     FROM products 
     WHERE model_id IN ('990-ELE1','950-PM1-XEF','17539','41213')"
)->fetchAll();
foreach ($verify as $r) {
    p("  " . $r['model_id'] . ":", 'head');
    p("    requires_models    = " . ($r['requires_models'] ?? 'NULL'), $r['requires_models'] ? 'ok' : 'warn');
    p("    recommended_models = " . ($r['recommended_models'] ?? 'NULL'), $r['recommended_models'] ? 'ok' : 'dim');
}

// ── STEP 6: Overall stats ─────────────────────────────────────────────────
p("\n=== FINAL STATS ===", 'head');
$stats = $db->query(
    "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN requires_models IS NOT NULL THEN 1 ELSE 0 END) as has_requires,
        SUM(CASE WHEN recommended_models IS NOT NULL THEN 1 ELSE 0 END) as has_recommended,
        SUM(CASE WHEN requires_models IS NULL AND title_description LIKE '%Requires%' THEN 1 ELSE 0 END) as still_unparsed
     FROM products"
)->fetch();
p("Total products:              " . $stats['total'], 'ok');
p("Has requires_models:         " . $stats['has_requires'], 'ok');
p("Has recommended_models:      " . $stats['has_recommended'], 'ok');
p("Still unparsed (Requires):   " . $stats['still_unparsed'], $stats['still_unparsed'] > 0 ? 'warn' : 'ok');

if ((int)$stats['still_unparsed'] > 0) {
    p("\n  Products with 'Requires' in text but still no requires_models — likely free-text without model numbers:", 'warn');
    $remaining = $db->query(
        "SELECT model_id, SUBSTRING(title_description,1,200) as snip
         FROM products 
         WHERE requires_models IS NULL AND title_description LIKE '%Requires%'
         LIMIT 5"
    )->fetchAll();
    foreach ($remaining as $r) {
        p("  " . $r['model_id'] . ": " . $r['snip'], 'dim');
    }
}

p("\n=== DONE ===", 'head');
p("Next step: the API and frontend now need to be updated to serve recommended_models and show the 'Recommended Items' prompt. See the companion patch files.", 'ok');
if (!$CLI) echo '</body></html>';

// ── LOGIN FORM ─────────────────────────────────────────────────────────────
function showLogin(string $err) { ?>
<!DOCTYPE html><html><head><title>Fix Requires/Rec</title>
<style>body{background:#060b12;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;font-family:sans-serif}
.b{background:#0d2137;padding:28px 32px;border-radius:10px;border:1px solid #1a4060;width:300px}
h2{color:#2dd4a0;margin:0 0 18px;font-size:1rem}
input{width:100%;padding:8px 11px;background:#060c14;border:1px solid #1a3a5a;border-radius:5px;color:#c8dde8;margin-bottom:10px;font-size:.9rem;box-sizing:border-box}
button{width:100%;padding:9px;background:#1a6fa0;border:none;border-radius:5px;color:#fff;font-weight:700;cursor:pointer}
.e{color:#e08080;font-size:.8rem;margin-bottom:8px}</style></head>
<body><div class="b"><h2>Fix Requires / Recommended</h2>
<?php if($err) echo '<div class="e">Invalid credentials</div>'; ?>
<form method="post"><input name="u" placeholder="Username"><input name="p" type="password" placeholder="Password"><button>Run Fix</button></form>
</div></body></html><?php }
