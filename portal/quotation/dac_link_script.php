<?php
/**
 * dac_link_docs.php
 * Scans flyers/dac/, extracts model numbers from filenames,
 * matches to products table, inserts into product_documents.
 *
 * SSH: cd ~/public_html/portal/quotation && php dac_link_docs.php
 */
set_time_limit(0);
require_once __DIR__ . '/db.php';   // uses correct credentials + getDB()

$DAC_DIR  = __DIR__ . '/flyers/dac';
$WEB_BASE = 'flyers/dac/';          // relative path stored in DB — api.php prepends __DIR__/

// ── CONNECT ────────────────────────────────────────────────────────────────
$db = getDB();

// Ensure product_documents table exists (matches existing schema from link_docs.php)
$db->exec("CREATE TABLE IF NOT EXISTS product_documents (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_id     VARCHAR(100) NOT NULL,
    combined_pdf VARCHAR(300) NOT NULL,
    flyer_pdf    VARCHAR(300) DEFAULT NULL,
    curric_pdf   VARCHAR(300) DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_model (model_id),
    INDEX idx_model (model_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── LOAD ALL PDFs ──────────────────────────────────────────────────────────
$files = glob($DAC_DIR . '/*.pdf');
if (!$files) die("No PDFs found in $DAC_DIR\n");
sort($files);

echo str_repeat('=', 65) . "\n";
echo "DAC Worldwide PDF → Database Linker\n";
echo "Files found: " . count($files) . "\n";
echo str_repeat('=', 65) . "\n\n";

// ── BUILD DB MODEL INDEX (same approach as link_docs.php) ─────────────────
$dbModels = $db->query("SELECT model_id FROM products")->fetchAll(PDO::FETCH_COLUMN);
$dbIndex  = [];
foreach ($dbModels as $mid) {
    $key = strtoupper(preg_replace('/[^A-Z0-9\-]/i', '', $mid));
    $dbIndex[$key][] = $mid;
    // Also index base model (strip voltage suffix -XEF, -XAD etc.)
    $base = preg_replace('/-X[A-Z]{2,3}$/i', '', $key);
    if ($base !== $key) $dbIndex[$base][] = $mid;
}

// ── PREPARE STATEMENTS ────────────────────────────────────────────────────
$ins = $db->prepare(
    "INSERT INTO product_documents (model_id, combined_pdf, flyer_pdf)
     VALUES (:m, :c, :f)
     ON DUPLICATE KEY UPDATE combined_pdf=:c2, flyer_pdf=:f2, updated_at=CURRENT_TIMESTAMP"
);

// ── PROCESS EACH FILE ─────────────────────────────────────────────────────
$linked    = 0;
$notFound  = [];
$alreadyDone = 0;

foreach ($files as $filepath) {
    $filename = basename($filepath);
    $webPath  = $WEB_BASE . $filename;

    // Extract model number: everything before ' - ' in the filename
    // e.g. "200-2045 - spherical-roller-bearing..." → "200-2045"
    //      "204E-PAC - advanced-bearing..."          → "204E-PAC"
    //      "251K - gate-valve-dissectible.pdf"       → "251K"
    if (preg_match('/^([A-Z0-9][\w\-\.]*?)\s+-\s+/i', $filename, $m)) {
        $modelRaw = trim($m[1]);
    } else {
        $modelRaw = preg_replace('/\.pdf$/i', '', $filename);
    }
    $modelKey = strtoupper(preg_replace('/[^A-Z0-9\-]/i', '', $modelRaw));

    // ── MATCH LOGIC (mirrors link_docs.php approach) ──────────────────
    $matchedIds = $dbIndex[$modelKey] ?? [];

    // Strip voltage suffix and retry
    if (empty($matchedIds)) {
        $stripped   = preg_replace('/-X[A-Z]{2,3}$/i', '', $modelKey);
        $matchedIds = $dbIndex[$stripped] ?? [];
    }

    // Try prefix fallback (trim last dash segment progressively)
    if (empty($matchedIds)) {
        $parts = explode('-', $modelKey);
        for ($trim = count($parts) - 1; $trim >= 2; $trim--) {
            $prefix     = implode('-', array_slice($parts, 0, $trim));
            $matchedIds = $dbIndex[$prefix] ?? [];
            if (!empty($matchedIds)) break;
        }
    }

    // Try stripping trailing letters for suffix-only models: "251K" → "251"
    if (empty($matchedIds)) {
        $baseModel  = preg_replace('/[A-Z]+$/i', '', $modelKey);
        if ($baseModel !== $modelKey && strlen($baseModel) >= 3) {
            $matchedIds = $dbIndex[$baseModel] ?? [];
        }
    }

    if (!empty($matchedIds)) {
        $primaryId = $matchedIds[0];
        $note      = ($primaryId !== $modelRaw) ? " [matched as $primaryId]" : '';

        // Insert/update primary match
        $ins->execute([
            ':m'  => $primaryId,
            ':c'  => $webPath,
            ':f'  => $webPath,   // for DAC, flyer IS the combined doc (no curriculum)
            ':c2' => $webPath,
            ':f2' => $webPath,
        ]);
        $linked++;
        echo sprintf("  OK     %-20s → %s%s\n", $modelRaw, $filename, $note);

        // Insert voltage variants if multiple matches
        foreach (array_slice($matchedIds, 1) as $variantId) {
            try {
                $ins->execute([
                    ':m'  => $variantId,
                    ':c'  => $webPath,
                    ':f'  => $webPath,
                    ':c2' => $webPath,
                    ':f2' => $webPath,
                ]);
                $linked++;
                echo sprintf("  VAR    %-20s → %s\n", $variantId, $filename);
            } catch (Exception $e) { /* skip duplicate */ }
        }
    } else {
        $notFound[] = ['model' => $modelRaw, 'file' => $filename];
        echo sprintf("  MISS   %-20s (not in DB) — %s\n", $modelRaw, $filename);
    }
}

// ── SUMMARY ───────────────────────────────────────────────────────────────
$total = (int)$db->query("SELECT COUNT(*) FROM product_documents")->fetchColumn();

echo "\n" . str_repeat('=', 65) . "\n";
echo "Newly linked / updated : $linked\n";
echo "Not in DB              : " . count($notFound) . "\n";
echo "Total rows in product_documents: $total\n";
echo str_repeat('=', 65) . "\n";

if ($notFound) {
    echo "\nNOT FOUND IN DATABASE (" . count($notFound) . " files):\n";
    foreach ($notFound as $nf) {
        echo "  {$nf['model']}  ({$nf['file']})\n";
    }
    echo "\nThese DAC products are not in the products table yet.\n";
    echo "Paste the NOT FOUND list into chat and I'll write an INSERT script.\n";
}

