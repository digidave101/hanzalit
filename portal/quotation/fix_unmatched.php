<?php
/**
 * fix_unmatched.php — Run via SSH:
 *   php fix_unmatched.php
 * Handles all unmatched combined PDFs by pattern-matching to DB model IDs
 */
set_time_limit(0);
require_once dirname(__FILE__) . '/db.php';
$db = getDB();

$ins = $db->prepare(
    "INSERT INTO product_documents (model_id, combined_pdf)
     VALUES (:m, :c)
     ON DUPLICATE KEY UPDATE combined_pdf=:c2"
);

function link(PDO $db, $stmt, string $model_id, string $combined_file): void {
    $path = 'combined/' . $combined_file;
    $stmt->execute([':m'=>$model_id, ':c'=>$path, ':c2'=>$path]);
    echo "  Linked: $model_id → $combined_file\n";
}

function findModels(PDO $db, string $prefix): array {
    $s = $db->prepare("SELECT model_id FROM products WHERE model_id LIKE :p");
    $s->execute([':p' => $prefix . '%']);
    return $s->fetchAll(PDO::FETCH_COLUMN);
}

$linked = 0;

// ── PATTERN 1: Dash-before-letter variants ────────────────────────────────
// 95-PM1-B → matches 95-PM1B, 95-PM1B-XEF etc (dash was added in filename)
echo "\n[Pattern 1] Dash-letter variants (95-PM1-B → 95-PM1B*)\n";
$dashVariants = [
    '95-PM1-B' => '95-PM1-B_combined.pdf',
    '95-PM1-C' => '95-PM1-C_combined.pdf',
    '95-PM1-D' => '95-PM1-D_combined.pdf',
    '95-PM1-E' => '95-PM1-E_combined.pdf',
    '95-PM1-F' => '95-PM1-F_combined.pdf',
    '95-PM1-G' => '95-PM1-G_combined.pdf',
    '95-PM1-H' => '95-PM1-H_combined.pdf',
];
foreach ($dashVariants as $dashModel => $file) {
    // Convert 95-PM1-B → 95-PM1B for DB lookup
    $noDash = preg_replace('/-([A-Z])$/', '$1', $dashModel);
    $matches = findModels($db, $noDash);
    if ($matches) {
        foreach ($matches as $m) { link($db, $ins, $m, $file); $linked++; }
    } else {
        echo "  STILL UNMATCHED: $file (tried: $noDash)\n";
    }
}

// ── PATTERN 2: F-suffix variants (990-DRV1F / 990-DRV1) ──────────────────
// These are international/standard versions — link both F and non-F to same PDF
echo "\n[Pattern 2] F-suffix pairs\n";
$fPairs = [
    '990-DRV1F_combined.pdf' => ['990-DRV1F', '990-DRV1'],
    '990-EC1F_combined.pdf'  => ['990-EC1F',  '990-EC1'],   // EC1F already linked, add EC1
    '990-MC1F_combined.pdf'  => ['990-MC1F',  '990-MC1'],
    '990-PC1F_combined.pdf'  => ['990-PC1F',  '990-PC1'],
    '990-PS712F_combined.pdf'=> ['990-PS712F','990-PS712'],
    '990-EC1_combined.pdf'   => ['990-EC1'],
    '990-DRV1_combined.pdf'  => ['990-DRV1'],
    '990-MC1_combined.pdf'   => ['990-MC1'],
    '990-PC1_combined.pdf'   => ['990-PC1'],
    '990-PS712_combined.pdf' => ['990-PS712'],
    '990-PAB53A_combined.pdf'=> ['990-PAB53A'],
];
foreach ($fPairs as $file => $models) {
    foreach ($models as $base) {
        $matches = findModels($db, $base);
        if ($matches) {
            foreach ($matches as $m) { link($db, $ins, $m, $file); $linked++; }
        } else {
            echo "  NOT IN DB: $base (from $file)\n";
        }
    }
}

// ── PATTERN 3: Multi-product files (96-CAD1B-96-CAD2B etc) ───────────────
echo "\n[Pattern 3] Multi-product combined files\n";
$multiFiles = [
    '85-PS7312-85-PS7315_combined.pdf'            => ['85-PS7312', '85-PS7315'],
    '96-CAD1B-96-CAD2B_combined.pdf'              => ['96-CAD1B',  '96-CAD2B'],
    '96-CNC1D-96-CNC2D_combined.pdf'              => ['96-CNC1D',  '96-CNC2D'],
    '96-PLS1T-96-PLS2_combined.pdf'               => ['96-PLS1T',  '96-PLS2'],
    '96-RSS1-96-RSS2_combined.pdf'                => ['96-RSS1',   '96-RSS2'],
    'T7031-T7032_combined.pdf'                    => ['T7031',     'T7032'],
    '87-MSSAB12-87-MSSAB53-87-MSSAB53A-87-MSSS7_combined.pdf'
                                                  => ['87-MSSAB12','87-MSSAB53','87-MSSAB53A','87-MSSS7'],
    '94-MT1-94-MT1A_combined.pdf'                 => ['94-MT1',    '94-MT1A'],
    '96-CAD1B-96-CAD2B_combined.pdf'              => ['96-CAD1B',  '96-CAD2B'],
];
foreach ($multiFiles as $file => $bases) {
    foreach ($bases as $base) {
        $matches = findModels($db, $base);
        if ($matches) {
            foreach ($matches as $m) { link($db, $ins, $m, $file); $linked++; }
        } else {
            echo "  NOT IN DB: $base (from $file)\n";
        }
    }
}

// ── PATTERN 4: Base models without voltage suffix ─────────────────────────
echo "\n[Pattern 4] Base models (link all voltage variants)\n";
$baseModels = [
    '85-GT1','85-MT101','85-MT102','85-MT5C','85-MT6BA','85-MT6BC','85-MT7-B',
    '850-AEC','850-AES','850-MT6B',
    '87-ENAB53A','87-MS8M60',
    '89-AS-AB5500','89-EN-AB5500','89-PVAB500','890-AB5500',
    '94-FMS1A',
    '95-GEO3','95-PAS2','95-PAS3','95-PAS4','95-PAS5','95-RGB2','95-RGB3',
    '950-ELF1','950-GEO2D','950-HTB1','950-PFS1','950-PS1',
    '950-SPF1','950-SPT1','950-STCL1','950-STF1','950-STOL1',
    '950-TEH1','950-TGC1','950-TNC1','950-WHS1','950-WT1',
    '96-ADE1','96-CAM1','96-CNC3D','96-ECS1','96-ES1',
    '96-HYD1','96-HYD2','96-HYD3','96-MPF2','96-TT1','96-TT2',
    '97-ME4B',
    'T5552','T5552F','T5552-FF1','T5553','T5554','T5555',
    'T7017A','T7082A','T7083','T7100','T7130','T7200',
];
foreach ($baseModels as $base) {
    $file = $base . '_combined.pdf';
    $matches = findModels($db, $base);
    if ($matches) {
        foreach ($matches as $m) { link($db, $ins, $m, $file); $linked++; }
    } else {
        echo "  NOT IN DB: $base\n";
    }
}

// ── SUMMARY ──────────────────────────────────────────────────────────────
$total = (int)$db->query("SELECT COUNT(*) FROM product_documents")->fetchColumn();
echo "\n════════════════════════════════\n";
echo "Newly linked: $linked entries\n";
echo "Total in product_documents: $total\n";
