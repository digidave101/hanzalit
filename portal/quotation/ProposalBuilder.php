<?php
// Load FPDI PDF-Parser if available (handles PDF 1.5+ compressed streams for free)
// Install with: composer require setasign/fpdi-pdf-parser
// Or see: https://github.com/Setasign/FPDI-PDF-Parser
if (file_exists(__DIR__ . '/vendor/setasign/fpdi-pdf-parser/src/autoload.php')) {
    require_once __DIR__ . '/vendor/setasign/fpdi-pdf-parser/src/autoload.php';
}
/**
 * ProposalBuilder.php — TI / Kitmeer  |  mPDF v8  |  R31
 *
 * ARCHITECTURE: Everything rendered in ONE mPDF instance.
 * This is the only way internal <a href="#anchor"> hyperlinks work in the PDF.
 * The cover page uses named blank header/footer — no separate mPDF instance needed.
 *
 * DOCUMENT STRUCTURE:
 *  Page 1  : Cover          (no header/footer — named blank)
 *  Page 2  : TOC            (logo header, tagline footer)
 *  Page 3  : Financial & Technical Specifications divider
 *  Page 4+ : Financial offer/pricing content (all sections)
 *  Page N  : "Literature & Technical Datasheets" divider (no section number)
 *  For each section:
 *    Page  : Section divider (named from quotation engine)
 *    Pages : Datasheets for that section (FPDI merged in)
 *
 * FIXES vs R30:
 *  - ONE mPDF instance → internal hyperlinks now work
 *  - Cover: removed www.kitmeer.com, white background behind logos
 *  - Dividers: white background behind client logo
 *  - Section naming: no "Section 1:" prefix on financial section
 *  - Document order matches user specification
 *  - Shipping/installation shown in financial output except EXW (no ocean freight / TBD)
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/payment_terms.php';

define('PDF_SKY',  '#DCF2FA');
define('PDF_DEEP', '#0D2137');
define('PDF_TEAL', '#0DB8A8');
define('PDF_GOLD', '#C9A227');
define('PDF_MED',  '#5A8CB4');
define('PDF_BGLT', '#EEF4F9');
define('PDF_LTEAL','#CCF0EC');

// ── Resolve net cost from a line item, with fallbacks ────────────────────────
// Priority: intl_dist_net → net_override → back-calculate from sell_price
// Returns float|null
function _resolveNet(array $line): ?float {
    // 1. Primary: intl_dist_net from database
    if(isset($line['intl_dist_net']) && $line['intl_dist_net']) return (float)$line['intl_dist_net'];
    // 2. Manual net override stored on line
    if(isset($line['net_override'])  && $line['net_override'])  return (float)$line['net_override'];
    return null;
}

// Resolve sell price for a line item, using divisor calculation when possible,
// falling back to a stored sell_price when the database net is missing.
function _resolveSell(array $line, array $sec, float $divisor, array $divisors, float $disc): ?float {
    $net = _resolveNet($line);
    if($net !== null) return round($net * (1-$disc) / _lineDiv($line,$sec,$divisor,$divisors), 2);
    // Fallback: stored sell_price is the already-calculated sell price
    if(isset($line['sell_price']) && $line['sell_price']) return (float)$line['sell_price'];
    if(isset($line['priceOverride']) && $line['priceOverride']) return (float)$line['priceOverride'];
    return null;
}

// ═══════════════════════════════════════════════════════════════════════════════
function buildProposalPDF(
    array  $sections,
    string $clientName,
    string $country,
    string $quoteNum,
    string $quoteDate,
    string $clientLogoPath,
    float  $divisor,
    float  $discountPct,
    array  $shipEstimates,
    float  $installAmt   = 0.0,
    string $installLabel = 'INSTALLATION AND COMMISSIONING',
    array  $divisors     = [],
    string $rfqDocPath   = '',
    string $pdfMode      = 'combined',   // 'combined' | 'commercial' | 'literature'
    array  $tenderDocPaths = [],         // Required Tender Documents (authorization letters etc.)
    string $incoterm     = '',
    array  $paymentTerms = []            // wire / include_lc / lc_terms
): string|false {
    $paymentTerms = tiNormalizePaymentTerms($paymentTerms ?: ['payment_wire'=>'30_70']);
    $shipEstimates = tiShipEstimatesForExport($shipEstimates, $incoterm);

    $includeFinancial = ($pdfMode !== 'literature');
    $includeLiterature= ($pdfMode !== 'commercial');

    @set_time_limit(300);
    @ini_set('memory_limit','512M');

    require_once __DIR__ . '/db.php';
    $db = getDB();
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true); // allow repeated named params & non-sequential key arrays
    if (!empty($sections)) $sections[0]['_isFinancial'] = true;

    // Fetch product data
    $allIds=[];
    foreach($sections as $sec) foreach($sec['items'] as $line) $allIds[]=$line['model_id'];
    $allIds=array_unique(array_filter($allIds,fn($x)=>$x&&$x!=='LEGACY'));
    $dbP=[];
    if(!empty($allIds)){
        $ph=implode(',',array_fill(0,count($allIds),'?'));
        $stmt=$db->prepare("SELECT model_id,title_only,COALESCE(title_description,'') AS td FROM products WHERE model_id IN ($ph)");
        $stmt->execute(array_values($allIds));
        foreach($stmt->fetchAll() as $p) $dbP[$p['model_id']]=$p;
    }
    foreach($sections as &$sec) foreach($sec['items'] as &$line){
        if($p=$dbP[$line['model_id']]??null){
            if(empty($line['title_only']))$line['title_only']=$p['title_only'];
            $line['title_description']=$p['td']??'';
        }
    } unset($sec,$line);

    // Fetch TI logo
    $tiLogoPath=_fetchTempImg('https://ti-kitmeer.com/images/Proposal%20Combined.png');
    $amatrolLogoPath  = _fetchTempImg('https://ti-kitmeer.com/images/Amatrol-logo.png');
    $dacLogoPath      = _fetchTempImg('https://ti-kitmeer.com/images/logo-dac-worldwide.png');
    $bayportLogoPath  = _fetchTempImg('https://ti-kitmeer.com/images/BayportTechnical_RGB_500px-wide.png');
    $tmpDir=sys_get_temp_dir().'/mpdf_ti';
    if(!is_dir($tmpDir))@mkdir($tmpDir,0755,true);

    // Collect datasheets per section — NO deduplication: each item gets its own datasheet
    // even if the same physical file would appear twice. Better to show duplicate than miss one.
    $sectionDocs=[];
    $seenExact=[];  // only block truly identical (same model_id) within same section
    // Collect datasheets for literature and combined modes only
    foreach($sections as $si=>$sec){
        $sectionDocs[$si]=[];
        $seenExact[$si]=[];
        if(!$includeLiterature) continue; // commercial-only: no datasheets needed
        foreach($sec['items'] as $line){
            $mid=$line['model_id']??'';
            if(!$mid) continue;

            // Skip if this exact model_id already added in this section (true duplicate)
            if(isset($seenExact[$si][$mid])) continue;

            // Resolve to a real filesystem path using multiple strategies:
            $resolvedPath = null;

            // Strategy 1: DB lookup via getDocPathForModel (now always returns absolute path)
            $doc = getDocPathForModel($mid);
            if($doc && file_exists($doc)){
                $resolvedPath = $doc;
            }

            // Strategy 2: Filesystem fallback — try all model_id variants
            if(!$resolvedPath){
                foreach(_getModelVariants($mid) as $mv){
                    if(strlen($mv)<2) continue;
                    $safeMv = preg_replace('/[^A-Za-z0-9_\-]/', '', $mv);
                    if(!$safeMv) continue;
                    $tryPath = __DIR__.'/product_docs/'.$safeMv.'.pdf';
                    if(file_exists($tryPath)){ $resolvedPath=$tryPath; break; }
                }
            }

            if($resolvedPath){
                $sectionDocs[$si][] = $resolvedPath;
                $seenExact[$si][$mid] = true; // mark this exact model_id as done in this section
            }
        }
    }

    // ── TWO-PASS: count financial pages first so TOC shows correct page numbers ──
    $financialPageCount = ($includeFinancial) ? 1 : 0; // 0 = no financial section
    if($includeFinancial){
        try {
            $testMpdf=new \Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4','margin_left'=>10,'margin_right'=>10,'margin_top'=>22,'margin_bottom'=>28,'margin_header'=>5,'margin_footer'=>5,'tempDir'=>$tmpDir,'default_font'=>'dejavusans']);
            $testMpdf->WriteHTML(_css(),\Mpdf\HTMLParserMode::HEADER_CSS);
            $testMpdf->AddPage();
            $testMpdf->WriteHTML(_financial($sections,$divisor,$discountPct,$shipEstimates,$installAmt,$installLabel,$country,$divisors,$incoterm,$paymentTerms));
            $financialPageCount = max(1, $testMpdf->page);
            unset($testMpdf);
        } catch(\Throwable $e){ $financialPageCount=1; }
    }

    // Count datasheet pages per section (needed for accurate TOC dest pages)
    $secDocPageCounts=[];
    if(!$includeLiterature){ // skip entirely for commercial-only PDF
        foreach($sections as $si=>$sec) $secDocPageCounts[$si]=0;
    } else
    foreach($sections as $si=>$sec){
        $secDocPageCounts[$si]=0;
        if(!empty($sectionDocs[$si])){
            $secDocPageCounts[$si]++; // divider page
            foreach($sectionDocs[$si] as $sp){
                try{
                    $fc=_makeFpdi();
                    $secDocPageCounts[$si]+=$fc->setSourceFile($sp);
                }catch(\Throwable $e){
                    // FPDI can't parse this PDF (likely PDF 1.5+ compressed xref).
                    // Estimate 1 page rather than crashing.
                    $secDocPageCounts[$si]++;
                }
            }
        }
    }

    // Calculate absolute destination page numbers:
    // Page 1=Cover, Page 2=TOC, Page 3=Financial divider,
    // Pages 4..(3+financialPageCount)=Financial content,
    // Pages (4+financialPageCount)+= Section dividers + datasheets (no Literature divider)
    // No literature divider page — sections follow immediately after financial content
    $secDestPage = []; // si => absolute page number of that section's divider
    // Page layout: cover(1) + TOC(1) + optional financial divider(1) + financial content + sections
    $runningPage = 2 + ($includeFinancial ? 1 + $financialPageCount : 0);
    foreach($sections as $si=>$sec){
        if($includeLiterature && !empty($sectionDocs[$si])){
            $runningPage++;
            $secDestPage[$si] = $runningPage;
            $runningPage += max(0, $secDocPageCounts[$si] - 1);
        }
    }
    // RFQ doc page count
    $rfqDocPageCount = 0;
    $rfqDocResolved  = '';
    if($rfqDocPath && file_exists($rfqDocPath)){
        try{
            $rfqFc = _makeFpdi();
            $rfqDocPageCount = $rfqFc->setSourceFile($rfqDocPath);
            $rfqDocResolved  = $rfqDocPath;
        }catch(\Throwable $e){
            // FPDI can't parse RFQ PDF — estimate page count from file size
            $fsize = filesize($rfqDocPath);
            $rfqDocPageCount = max(1, (int)round($fsize / 80000));
            $rfqDocResolved  = $rfqDocPath;
        }
    }

    // Required Tender Documents page counts (one merged section, multiple PDFs)
    $tenderResolved = [];      // valid file paths in upload order
    $tenderPageTotal = 0;
    foreach($tenderDocPaths as $tdp){
        if(!$tdp || !file_exists($tdp)) continue;
        try{
            $tFc = _makeFpdi();
            $tenderPageTotal += $tFc->setSourceFile($tdp);
        }catch(\Throwable $e){
            $tenderPageTotal += max(1, (int)round(filesize($tdp) / 80000));
        }
        $tenderResolved[] = $tdp;
    }

    $tocPages = [
        'financial' => $includeFinancial ? 3 : 0,
        'sections'  => $secDestPage,
        'rfq_page'  => ($rfqDocPageCount > 0) ? ($runningPage + 1) : 0,
        'tender_page' => ($tenderPageTotal > 0)
            ? ($runningPage + (($rfqDocPageCount > 0) ? 1 + $rfqDocPageCount : 0) + 1)
            : 0,
    ];

    // ── Build everything in ONE mPDF instance ─────────────────────────────────
    try{
        $mpdf=new \Mpdf\Mpdf([
            'mode'          =>'utf-8',
            'format'        =>'A4',
            'margin_left'   =>10,
            'margin_right'  =>10,
            'margin_top'    =>22,
            'margin_bottom' =>28,
            'margin_header' =>5,
            'margin_footer' =>5,
            'tempDir'       =>$tmpDir,
            'default_font'  =>'dejavusans',
        ]);
        $mpdf->SetTitle($quoteNum.' — '.$clientName);
        $mpdf->SetAuthor('Technologies International LLC / Kitmeer');
        $mpdf->WriteHTML(_css(),\Mpdf\HTMLParserMode::HEADER_CSS);

        // ── Define named headers/footers ─────────────────────────────────────
        $mpdf->DefHTMLHeaderByName('blank',' ');
        $mpdf->DefHTMLFooterByName('blank',' ');
        $mpdf->DefHTMLHeaderByName('toc_hdr',_toc_header($clientName,$quoteDate,$tiLogoPath));
        $mpdf->DefHTMLFooterByName('toc_ftr',_toc_footer());
        $mpdf->DefHTMLHeaderByName('std_hdr',_header($clientName,$quoteDate));
        $mpdf->DefHTMLFooterByName('std_ftr',_footer($quoteNum,$quoteDate));

        // ── PAGE 1: COVER (no header/footer, zero margins) ───────────────────
        $mpdf->AddPageByArray([
            'ohname'=>'blank','ehname'=>'blank',
            'ofname'=>'blank','efname'=>'blank',
            'margin-top'=>0,'margin-bottom'=>0,'margin-left'=>0,'margin-right'=>0,
        ]);
        $mpdf->WriteHTML(_cover($clientName,$country,$quoteNum,$quoteDate,$clientLogoPath,$tiLogoPath,$amatrolLogoPath,$dacLogoPath,$bayportLogoPath));

        // ── PAGE 2: TABLE OF CONTENTS ─────────────────────────────────────────
        $mpdf->AddPageByArray([
            'ohname'=>'toc_hdr','ehname'=>'toc_hdr',
            'ofname'=>'toc_ftr','efname'=>'toc_ftr',
            'margin-top'=>48,'margin-bottom'=>22,
            'margin-left'=>10,'margin-right'=>10,
            'margin_header'=>5,'margin_footer'=>5,
            'resetpagenum'=>2,
        ]);
        $mpdf->WriteHTML(_toc($sections,$clientName,$quoteDate,$tiLogoPath,$tocPages,$includeFinancial,$includeLiterature));

        // ── Restore standard header/footer for all content pages ──────────────
        $mpdf->SetHTMLHeader(_header($clientName,$quoteDate));
        $mpdf->SetHTMLFooter(_footer($quoteNum,$quoteDate));

        // ── PAGE 3: FINANCIAL & TECHNICAL SPECIFICATIONS (skipped for literature-only)
        if($includeFinancial){
            $mpdf->AddPageByArray([
                'ohname'=>'std_hdr','ehname'=>'std_hdr',
                'ofname'=>'std_ftr','efname'=>'std_ftr',
                'margin-top'=>0,'margin-bottom'=>0,'margin-left'=>0,'margin-right'=>0,
            ]);
            $mpdf->WriteHTML(_divider(0,'Financial &amp; Technical Specifications',true,$clientLogoPath,$tiLogoPath??''));
            $mpdf->AddPageByArray([
                'ohname'=>'std_hdr','ehname'=>'std_hdr',
                'ofname'=>'std_ftr','efname'=>'std_ftr',
                'margin-top'=>22,'margin-bottom'=>28,'margin-left'=>10,'margin-right'=>10,
            ]);
            $mpdf->WriteHTML(_financial($sections,$divisor,$discountPct,$shipEstimates,$installAmt,$installLabel,$country,$divisors,$incoterm,$paymentTerms));
        }

        // ── PER-SECTION DIVIDERS + DATASHEETS ────────────────────────────────
        // Save mPDF output so far, then FPDI-merge datasheets section by section
        $mainBytes=$mpdf->Output('','S');

    }catch(\Throwable $e){
        if($tiLogoPath)@unlink($tiLogoPath);
        throw $e;
    }
    // Keep $tiLogoPath alive for FPDI section divider rendering — unlinked below after merge
    if($amatrolLogoPath)@unlink($amatrolLogoPath);
    if($dacLogoPath)@unlink($dacLogoPath);
    if($bayportLogoPath)@unlink($bayportLogoPath);

    // ── FPDI: merge main mPDF output + per-section dividers + datasheets ──────
    // We need to re-render section dividers as separate mPDF pages to add them.
    // We'll create per-section divider PDFs and interleave them with datasheets.

    // Build per-section mini-PDFs (divider page for each section)
    $sectionDividerBytes=[];
    foreach($sections as $si=>$sec){
        if(!$includeLiterature) continue;        // commercial-only: never show section dividers
        if(empty($sectionDocs[$si])) continue;       // no datasheets found for this section — skip
        try{
            // Section divider mini-doc: zero margins so 210×297 table fills the page
            $dm=new \Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4','margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,'margin_header'=>0,'margin_footer'=>0,'tempDir'=>$tmpDir,'default_font'=>'dejavusans']);
            $dm->WriteHTML(_css(),\Mpdf\HTMLParserMode::HEADER_CSS);
            $dm->AddPage();
            $secName=htmlspecialchars($sec['name']??'Section '.($si+1),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
            $dm->WriteHTML(_divider($si+1,$secName,false,$clientLogoPath,$tiLogoPath??''));
            $sectionDividerBytes[$si]=$dm->Output('','S');
        }catch(\Throwable $e){ $sectionDividerBytes[$si]=null; }
    }

    // Count total pages for Page X of Y
    if(!$mainBytes||strlen($mainBytes)<100)return false;
    $t1=tempnam(sys_get_temp_dir(),'p_c_').'.pdf';
    file_put_contents($t1,$mainBytes);
    try{$counter=_makeFpdi();$mainCount=$counter->setSourceFile($t1);}
    catch(\Throwable $e){@unlink($t1);return $mainBytes;}
    @unlink($t1);
    $totalExtra=0;
    if($rfqDocResolved && $rfqDocPageCount>0) $totalExtra += 1 + $rfqDocPageCount; // 1 divider + RFQ pages
    if(!empty($tenderResolved) && $tenderPageTotal>0) $totalExtra += 1 + $tenderPageTotal; // 1 divider + tender doc pages
    foreach($sections as $si=>$sec){
        if(isset($sectionDividerBytes[$si])&&$sectionDividerBytes[$si]) $totalExtra++;
        foreach($sectionDocs[$si] as $sp){
            try{ $fc=_makeFpdi(); $totalExtra+=$fc->setSourceFile($sp); }
            catch(\Throwable $e){ $totalExtra++; } // FPDI can't parse — estimate 1 page
        }
    }
    $totalPages=$mainCount+$totalExtra;

    // Now actually merge
    try{
        $tmpMain=tempnam(sys_get_temp_dir(),'prop_m_').'.pdf';
        file_put_contents($tmpMain,$mainBytes);

        $merger=new \setasign\Fpdi\Fpdi('P','mm','A4');
        $merger->SetAutoPageBreak(false);

        // ── Define FPDF internal link destinations (re-add nav after FPDI strips annotations) ──
        // IMPORTANT: AddLink() creates the handle; SetLink() is called DURING the loop when
        // we are actually ON the destination page (page=-1 default uses current page).
        // This is more reliable than pre-setting page numbers before AddPage() is called.
        $tocNavId       = $merger->AddLink(); // → page 2 (TOC), set below when p==2
        $financialNavId = $merger->AddLink(); // → page 3 (Financial divider), set when p==3
        $secNavIds = [];
        foreach($secDestPage as $si=>$_) $secNavIds[$si] = $merger->AddLink(); // set in section loop
        $rfqNavId = ($rfqDocResolved) ? $merger->AddLink() : 0; // RFQ section — set when we add its pages
        $tenderNavId = (!empty($tenderResolved)) ? $merger->AddLink() : 0; // Tender Documents section

        // Import main mPDF pages
        $mc=$merger->setSourceFile($tmpMain);
        for($p=1;$p<=$mc;$p++){
            $tpl=$merger->importPage($p);$sz=$merger->getTemplateSize($tpl);
            $merger->AddPage(($sz['width']>$sz['height'])?'L':'P',[$sz['width'],$sz['height']]);
            $merger->useTemplate($tpl,0,0,$sz['width'],$sz['height']);

            // ── Set destinations when we're ON the target page ────────────────
            if($p==2)              $merger->SetLink($tocNavId);        // TOC = current page
            if($p==3)              $merger->SetLink($financialNavId);   // Financial divider

            // ── Add navigation links ──────────────────────────────────────────
            // ^ TOC link in header on all pages except cover (1) and TOC itself (2)
            // Position matches _overlayDsHeader button: $w-21, width 21, height 10
            if($p > 2) $merger->Link($sz['width']-21, 1, 21, 10, $tocNavId);
            // TOC entry row click areas on the TOC page
            if($p==2)  _addTocLinks($merger, $sz['width'], $tocNavId, $financialNavId, $secNavIds, count($sections), $rfqNavId??0, $tenderNavId??0, !empty($tiLogoPath));

            // Page numbers (skip cover)
            if($p > 1) _writePgNum($merger,$sz['width'],$sz['height'],$p,$totalPages);
        }
        @unlink($tmpMain);

        $absPage=$mc;

        // Per-section: divider page then datasheets
        foreach($sections as $si=>$sec){
            if(empty($sectionDocs[$si])) continue;

            // Section divider page
            if(!empty($sectionDividerBytes[$si])){
                $absPage++;
                $tmpDiv=tempnam(sys_get_temp_dir(),'div_').'.pdf';
                file_put_contents($tmpDiv,$sectionDividerBytes[$si]);
                try{
                    $dc=$merger->setSourceFile($tmpDiv);
                    for($dp=1;$dp<=$dc;$dp++){
                        $tpl=$merger->importPage($dp);$sz=$merger->getTemplateSize($tpl);
                        $merger->AddPage(($sz['width']>$sz['height'])?'L':'P',[$sz['width'],$sz['height']]);
                        $merger->useTemplate($tpl,0,0,$sz['width'],$sz['height']);
                        // Set section destination on first page of its divider
                        if($dp==1 && isset($secNavIds[$si])) $merger->SetLink($secNavIds[$si]);
                        _overlayDsHeader($merger,$sz['width'],$sz['height'],$clientName,$quoteDate,$tocNavId);
                        _overlayDsFooter($merger,$sz['width'],$sz['height'],$absPage+($dp-1),$totalPages,$quoteNum);
                    }
                }catch(\Throwable $e){}
                @unlink($tmpDiv);
            }

            // Datasheets for this section
            foreach($sectionDocs[$si] as $sp){
                // Try FPDI direct (PDF 1.4 / uncompressed xref)
                try{ $cnt=$merger->setSourceFile($sp); }
                catch(\Throwable $fpdiErr){
                    // FPDI failed — PDF is 1.5+ compressed.
                    // exec() is disabled on this host. Try Imagick, then placeholder.
                    $converted = false;

                    if(extension_loaded('imagick')){
                        $imgs=[];
                        try{
                            $all=new \Imagick();
                            $all->setResolution(150,150);
                            $all->setBackgroundColor('white');
                            $all->readImage($sp);
                            $pgN=$all->getNumberImages();
                            for($pi=0;$pi<$pgN;$pi++){
                                try{
                                    $all->setIteratorIndex($pi);
                                    $pg=$all->getImage();
                                    $pg->setImageFormat('png');
                                    $pg->setBackgroundColor('white');
                                    $pg->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                                    $ti=tempnam(sys_get_temp_dir(),'ds_').'.png';
                                    $pg->writeImage($ti); $pg->destroy(); $imgs[]=$ti;
                                }catch(\Throwable $pe){}
                            }
                            $all->destroy();
                        }catch(\Throwable $ie){}
                        if(!empty($imgs)){
                            try{
                                $cm=new \Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4',
                                    'margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,
                                    'margin_header'=>0,'margin_footer'=>0,'tempDir'=>$tmpDir]);
                                $n=0;
                                foreach($imgs as $img){
                                    if(!file_exists($img)) continue;
                                    list($iw,$ih)=@getimagesize($img)?:[1190,1684];
                                    $wm=max(10,$iw/150*25.4); $hm=max(10,$ih/150*25.4);
                                    $cm->AddPage($wm>$hm?'L':'P',[$wm,$hm]);
                                    $cm->Image($img,0,0,$wm,$hm,'png'); $n++;
                                }
                                if($n>0){
                                    $cb=$cm->Output('','S');
                                    if($cb&&strlen($cb)>200){
                                        $tc=tempnam(sys_get_temp_dir(),'cv_').'.pdf';
                                        file_put_contents($tc,$cb);
                                        try{
                                            $cc=$merger->setSourceFile($tc);
                                            for($cp=1;$cp<=$cc;$cp++){
                                                $ct=$merger->importPage($cp); $cs=$merger->getTemplateSize($ct);
                                                $absPage++;
                                                $merger->AddPage($cs['width']>$cs['height']?'L':'P',[$cs['width'],$cs['height']]);
                                                $merger->useTemplate($ct,0,0,$cs['width'],$cs['height']);
                                                _overlayDsHeader($merger,$cs['width'],$cs['height'],$clientName,$quoteDate,$tocNavId);
                                                _overlayDsFooter($merger,$cs['width'],$cs['height'],$absPage,$totalPages,$quoteNum);
                                            }
                                            $converted=true;
                                        }catch(\Throwable $e){}
                                        @unlink($tc);
                                    }
                                }
                            }catch(\Throwable $e){}
                            foreach($imgs as $i) @unlink($i);
                        }
                    }

                    // Always add placeholder if conversion failed — never silently skip
                    if(!$converted){
                        $absPage++;
                        $merger->AddPage('P',[210,297]);
                        $merger->SetFillColor(248,252,255); $merger->Rect(0,0,210,297,'F');
                        $merger->SetFont('Helvetica','',9); $merger->SetTextColor(100,140,180);
                        $merger->SetXY(20,140);
                        $merger->Cell(170,8,'[Datasheet: '.basename($sp).' - Please attach separately]',0,0,'C');
                        _overlayDsHeader($merger,210,297,$clientName,$quoteDate,$tocNavId);
                        _overlayDsFooter($merger,210,297,$absPage,$totalPages,$quoteNum);
                    }
                    continue;
                }

                // FPDI succeeded — import page by page
                for($p=1;$p<=$cnt;$p++){
                    try{
                        $merger->setSourceFile($sp);
                        $tpl=$merger->importPage($p); $sz=$merger->getTemplateSize($tpl);
                        $absPage++;
                        $merger->AddPage(($sz['width']>$sz['height'])?'L':'P',[$sz['width'],$sz['height']]);
                        $merger->useTemplate($tpl,0,0,$sz['width'],$sz['height']);
                        _overlayDsHeader($merger,$sz['width'],$sz['height'],$clientName,$quoteDate,$tocNavId);
                        _overlayDsFooter($merger,$sz['width'],$sz['height'],$absPage,$totalPages,$quoteNum);
                    }catch(\Throwable $e){
                        $absPage++;
                        $merger->AddPage('P',[210,297]);
                        $merger->SetFillColor(248,252,255); $merger->Rect(0,0,210,297,'F');
                        $merger->SetFont('Helvetica','',9); $merger->SetTextColor(100,140,180);
                        $merger->SetXY(20,140);
                        $merger->Cell(170,8,'[Datasheet page could not be rendered]',0,0,'C');
                        _overlayDsHeader($merger,$sz['width']??210,$sz['height']??297,$clientName,$quoteDate,$tocNavId);
                        _overlayDsFooter($merger,$sz['width']??210,$sz['height']??297,$absPage,$totalPages,$quoteNum);
                    }
                }
            }
        }

        // ── Append RFQ Comparison document ──────────────────────────────────
        if($rfqDocResolved && $rfqDocPageCount > 0){
            // RFQ divider page
            try{
                $rfqDivM = new \Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4','margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,'margin_header'=>0,'margin_footer'=>0,'tempDir'=>$tmpDir,'default_font'=>'dejavusans']);
                $rfqDivM->WriteHTML(_css(),\Mpdf\HTMLParserMode::HEADER_CSS);
                $rfqDivM->AddPage();
                $rfqDivM->WriteHTML(_divider(count($sections)+1,'Competitive Comparison to Request For Quotation (RFQ)',false,$clientLogoPath,$tiLogoPath??''));
                $rfqDivBytes = $rfqDivM->Output('','S');
                if($rfqDivBytes){
                    $absPage++;
                    $tmpRfqDiv = tempnam(sys_get_temp_dir(),'rdiv_').'.pdf';
                    file_put_contents($tmpRfqDiv,$rfqDivBytes);
                    try{
                        $dc=$merger->setSourceFile($tmpRfqDiv);
                        for($dp=1;$dp<=$dc;$dp++){
                            $tpl=$merger->importPage($dp);$sz=$merger->getTemplateSize($tpl);
                            $merger->AddPage(($sz['width']>$sz['height'])?'L':'P',[$sz['width'],$sz['height']]);
                            $merger->useTemplate($tpl,0,0,$sz['width'],$sz['height']);
                            if($dp==1 && $rfqNavId) $merger->SetLink($rfqNavId); // set TOC destination
                            _overlayDsHeader($merger,$sz['width'],$sz['height'],$clientName,$quoteDate,$tocNavId);
                            _overlayDsFooter($merger,$sz['width'],$sz['height'],$absPage+($dp-1),$totalPages,$quoteNum);
                        }
                    }catch(\Throwable $e){}
                    @unlink($tmpRfqDiv);
                }
            }catch(\Throwable $e){}

            // RFQ document pages
            try{
                $rfqCnt=$merger->setSourceFile($rfqDocResolved);
                for($rp=1;$rp<=$rfqCnt;$rp++){
                    $absPage++;
                    $tpl=$merger->importPage($rp);$sz=$merger->getTemplateSize($tpl);
                    $merger->AddPage(($sz['width']>$sz['height'])?'L':'P',[$sz['width'],$sz['height']]);
                    $merger->useTemplate($tpl,0,0,$sz['width'],$sz['height']);
                    _overlayDsHeader($merger,$sz['width'],$sz['height'],$clientName,$quoteDate,$tocNavId);
                    _overlayDsFooter($merger,$sz['width'],$sz['height'],$absPage,$totalPages,$quoteNum);
                }
            }catch(\Throwable $e){}
            // Clean up temp RFQ file if it was a pdftk conversion
            if($rfqDocResolved !== $rfqDocPath) @unlink($rfqDocResolved);
        }

        // ── Append Required Tender Documents (authorization letters etc.) ────
        if(!empty($tenderResolved)){
            // Tender divider page
            $tenderDivNum = count($sections) + 1 + (($rfqDocResolved && $rfqDocPageCount>0) ? 1 : 0);
            try{
                $tDivM = new \Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4','margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,'margin_header'=>0,'margin_footer'=>0,'tempDir'=>$tmpDir,'default_font'=>'dejavusans']);
                $tDivM->WriteHTML(_css(),\Mpdf\HTMLParserMode::HEADER_CSS);
                $tDivM->AddPage();
                $tDivM->WriteHTML(_divider($tenderDivNum,'Required Tender Documents',false,$clientLogoPath,$tiLogoPath??''));
                $tDivBytes = $tDivM->Output('','S');
                if($tDivBytes){
                    $absPage++;
                    $tmpTDiv = tempnam(sys_get_temp_dir(),'tdiv_').'.pdf';
                    file_put_contents($tmpTDiv,$tDivBytes);
                    try{
                        $dc=$merger->setSourceFile($tmpTDiv);
                        for($dp=1;$dp<=$dc;$dp++){
                            $tpl=$merger->importPage($dp);$sz=$merger->getTemplateSize($tpl);
                            $merger->AddPage(($sz['width']>$sz['height'])?'L':'P',[$sz['width'],$sz['height']]);
                            $merger->useTemplate($tpl,0,0,$sz['width'],$sz['height']);
                            if($dp==1 && $tenderNavId) $merger->SetLink($tenderNavId); // set TOC destination
                            _overlayDsHeader($merger,$sz['width'],$sz['height'],$clientName,$quoteDate,$tocNavId);
                            _overlayDsFooter($merger,$sz['width'],$sz['height'],$absPage+($dp-1),$totalPages,$quoteNum);
                        }
                    }catch(\Throwable $e){}
                    @unlink($tmpTDiv);
                }
            }catch(\Throwable $e){}

            // Tender document pages — FPDI direct, Imagick fallback, placeholder as last resort
            foreach($tenderResolved as $tdp){
                try{
                    $tCnt=$merger->setSourceFile($tdp);
                    for($tp2=1;$tp2<=$tCnt;$tp2++){
                        $absPage++;
                        $tpl=$merger->importPage($tp2);$sz=$merger->getTemplateSize($tpl);
                        $merger->AddPage(($sz['width']>$sz['height'])?'L':'P',[$sz['width'],$sz['height']]);
                        $merger->useTemplate($tpl,0,0,$sz['width'],$sz['height']);
                        _overlayDsHeader($merger,$sz['width'],$sz['height'],$clientName,$quoteDate,$tocNavId);
                        _overlayDsFooter($merger,$sz['width'],$sz['height'],$absPage,$totalPages,$quoteNum);
                    }
                }catch(\Throwable $fpdiErr){
                    // FPDI failed — PDF is 1.5+ compressed. Try Imagick rasterization.
                    $converted = false;
                    if(extension_loaded('imagick')){
                        $imgs=[];
                        try{
                            $all=new \Imagick();
                            $all->setResolution(150,150);
                            $all->setBackgroundColor('white');
                            $all->readImage($tdp);
                            $pgN=$all->getNumberImages();
                            for($pi2=0;$pi2<$pgN;$pi2++){
                                try{
                                    $all->setIteratorIndex($pi2);
                                    $pg=$all->getImage();
                                    $pg->setImageFormat('png');
                                    $pg->setBackgroundColor('white');
                                    $pg->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                                    $ti=tempnam(sys_get_temp_dir(),'td_').'.png';
                                    $pg->writeImage($ti); $pg->destroy(); $imgs[]=$ti;
                                }catch(\Throwable $pe){}
                            }
                            $all->destroy();
                        }catch(\Throwable $ie){}
                        if(!empty($imgs)){
                            try{
                                $cm=new \Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4',
                                    'margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,
                                    'margin_header'=>0,'margin_footer'=>0,'tempDir'=>$tmpDir]);
                                $n=0;
                                foreach($imgs as $img){
                                    if(!file_exists($img)) continue;
                                    list($iw,$ih)=@getimagesize($img)?:[1190,1684];
                                    $wm=max(10,$iw/150*25.4); $hm=max(10,$ih/150*25.4);
                                    $cm->AddPage($wm>$hm?'L':'P',[$wm,$hm]);
                                    $cm->Image($img,0,0,$wm,$hm,'png'); $n++;
                                }
                                if($n>0){
                                    $cb=$cm->Output('','S');
                                    if($cb&&strlen($cb)>200){
                                        $tc=tempnam(sys_get_temp_dir(),'tcv_').'.pdf';
                                        file_put_contents($tc,$cb);
                                        try{
                                            $cc=$merger->setSourceFile($tc);
                                            for($cp=1;$cp<=$cc;$cp++){
                                                $ct=$merger->importPage($cp); $cs=$merger->getTemplateSize($ct);
                                                $absPage++;
                                                $merger->AddPage($cs['width']>$cs['height']?'L':'P',[$cs['width'],$cs['height']]);
                                                $merger->useTemplate($ct,0,0,$cs['width'],$cs['height']);
                                                _overlayDsHeader($merger,$cs['width'],$cs['height'],$clientName,$quoteDate,$tocNavId);
                                                _overlayDsFooter($merger,$cs['width'],$cs['height'],$absPage,$totalPages,$quoteNum);
                                            }
                                            $converted=true;
                                        }catch(\Throwable $e){}
                                        @unlink($tc);
                                    }
                                }
                            }catch(\Throwable $e){}
                            foreach($imgs as $i2) @unlink($i2);
                        }
                    }
                    if(!$converted){
                        $absPage++;
                        $merger->AddPage('P',[210,297]);
                        $merger->SetFillColor(248,252,255); $merger->Rect(0,0,210,297,'F');
                        $merger->SetFont('Helvetica','',9); $merger->SetTextColor(100,140,180);
                        $merger->SetXY(20,140);
                        $merger->Cell(170,8,'[Tender document: '.basename($tdp).' - Please attach separately]',0,0,'C');
                        _overlayDsHeader($merger,210,297,$clientName,$quoteDate,$tocNavId);
                        _overlayDsFooter($merger,210,297,$absPage,$totalPages,$quoteNum);
                    }
                }
            }
        }

        $merged=$merger->Output('','S');
        if($tiLogoPath)@unlink($tiLogoPath); // safe to unlink now — dividers all built
        return($merged&&strlen($merged)>100)?$merged:($mainBytes?:false);

    }catch(\Throwable $e){
        @unlink($tmpMain??'');
        if($tiLogoPath)@unlink($tiLogoPath);
        return $mainBytes?:false;
    }
}

// ── FPDI overlay helpers ──────────────────────────────────────────────────────
function _writePgNum(\setasign\Fpdi\Fpdi $m,float $w,float $h,int $pg,int $tot):void{
    // Place page number in the footer zone right column
    // mPDF footer starts ~28mm from bottom; row 1 of footer table is ~5-10mm up
    // White-out the right column area first then write the number
    $m->SetFillColor(255,255,255);
    $m->Rect($w-42,$h-27,42,10,'F');
    $m->SetFont('Helvetica','B',8);$m->SetTextColor(13,33,55);
    $m->SetXY($w-42,$h-24);$m->Cell(37,5,'Page '.$pg.' of '.$tot,0,0,'R');
}

function _overlayDsHeader(\setasign\Fpdi\Fpdi $m,float $w,float $h,string $cn,string $date,int $tocNavId=0):void{
    // FPDI Cell() uses Latin-1 internally; convert from UTF-8 to avoid â€" garbling
    $cnL1 = @iconv('UTF-8','ISO-8859-1//TRANSLIT//IGNORE',$cn) ?: preg_replace('/[^\x20-\x7E]/',' ',$cn);
    $m->SetFillColor(255,255,255);$m->Rect(0,0,$w,14,'F');
    $m->SetDrawColor(13,184,168);$m->SetLineWidth(0.3);$m->Line(5,13.8,$w-5,13.8);
    $m->SetFont('Helvetica','',7);$m->SetTextColor(90,140,180);
    $m->SetXY(5,4);$m->Cell(68,4,'Technologies International, LLC & Kitmeer, Fz',0,0,'L');
    $m->SetFont('Helvetica','B',7);$m->SetXY(73,4);$m->Cell($w-118,4,$cnL1,0,0,'C');
    $m->SetFont('Helvetica','',7);$m->SetXY($w-58,4);$m->Cell(35,4,$date,0,0,'R');
    $m->SetFillColor(13,184,168);$m->SetTextColor(255,255,255);$m->SetFont('Helvetica','B',6.5);
    $m->Rect($w-19,1.5,17,8,'F');$m->SetXY($w-19,3);$m->Cell(17,5,'^ TOC',0,0,'C');
    if($tocNavId>0) $m->Link($w-21,1,21,10,$tocNavId);
}

function _overlayDsFooter(\setasign\Fpdi\Fpdi $m,float $w,float $h,int $pg,int $tot,string $qn):void{
    $m->SetFillColor(255,255,255);$m->Rect(0,$h-13,$w,13,'F');
    $m->SetDrawColor(13,184,168);$m->SetLineWidth(0.3);$m->Line(5,$h-13.2,$w-5,$h-13.2);
    $m->SetFont('Helvetica','',6);$m->SetTextColor(90,140,180);
    $m->SetXY(5,$h-12);$m->Cell(22,3.5,date('Y/m/d'),0,0,'L');
    $m->SetFont('Helvetica','B',6);$m->SetTextColor(13,184,168);
    $m->SetXY(27,$h-12.5);$m->Cell($w-54,3.5,'Technologies International (TI) / Kitmeer',0,0,'C');
    $m->SetFont('Helvetica','',5.5);$m->SetXY(27,$h-9);
    $m->Cell($w-54,3.5,'North America: 3149 Broadway, STE 16, New York, NY 10027 USA  Ph: +1 646.216.9043     Middle East: P.O. Box 500211, Dubai Internet City, UAE  Ph: +971.4391.0970',0,0,'C');
    $m->SetFont('Helvetica','B',7);$m->SetTextColor(13,33,55);
    $m->SetXY($w-33,$h-11);$m->Cell(28,4,'Page '.$pg.' of '.$tot,0,0,'R');
}

// ── CSS ───────────────────────────────────────────────────────────────────────
function _css():string{return '<style>
body       {font-family:dejavusans,sans-serif;font-size:9pt;color:'.PDF_DEEP.';}
table      {border-collapse:collapse;width:100%;}
thead th   {background:'.PDF_TEAL.';color:#fff;font-size:7.5pt;padding:2mm 1.5mm;text-align:center;border:0.15mm solid #fff;line-height:1.3;}
th         {background:'.PDF_TEAL.';color:#fff;font-size:7.5pt;padding:2mm 1.5mm;text-align:center;border:0.15mm solid #fff;line-height:1.3;}
td         {font-size:8pt;padding:1.5mm 2mm;vertical-align:top;border:0.15mm solid #dde8f0;}
.sec-hdr   {background:'.PDF_TEAL.';color:#fff;font-weight:bold;font-size:8.5pt;padding:2mm 3mm;border:0.15mm solid #fff;}
.row-alt   {background:'.PDF_BGLT.';}
.sub-total {background:'.PDF_LTEAL.';font-weight:bold;}
.grand     {background:'.PDF_MED.';color:#fff;font-weight:bold;font-size:10pt;}
.pay-lbl   {font-weight:bold;font-size:8.5pt;margin:3mm 0 1mm;}
.nonneg    {background:#D44040;color:#fff;font-weight:bold;text-align:center;padding:2.5mm;font-size:8pt;}
.voltage   {background:'.PDF_MED.';color:#fff;font-size:7.5pt;padding:2.5mm 4mm;margin-top:2mm;line-height:1.5;}
.tot-hdr   {background:'.PDF_TEAL.';color:#fff;font-weight:bold;}
.tot-row   {background:'.PDF_BGLT.';font-weight:bold;}
.tot-sub   {background:'.PDF_LTEAL.';font-weight:bold;}
.tot-grand {background:'.PDF_TEAL.';color:'.PDF_DEEP.';font-weight:bold;font-size:9.5pt;}
</style>';}

// ── TOC header: PRESENTED BY + logo ──────────────────────────────────────────
function _toc_header(string $cn,string $date,?string $tiLogoPath):string{
    $logo=($tiLogoPath&&file_exists($tiLogoPath))
        ?'<div style="font-size:5.5pt;color:'.PDF_MED.';font-style:italic;margin-bottom:1mm;">PRESENTED BY:</div>'
         .'<img src="'.h($tiLogoPath).'" style="max-width:56mm;max-height:18mm;">'
        :'<b style="color:'.PDF_TEAL.';">Technologies International LLC</b>';
    return '<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:0.5mm solid '.PDF_TEAL.';">
<tr>
  <td width="37%" valign="bottom" style="padding-bottom:2mm;">'.$logo.'</td>
  <td width="30%" align="center" valign="bottom" style="font-size:7.5pt;font-weight:bold;color:'.PDF_MED.';padding-bottom:2mm;">'.h($cn).'</td>
  <td width="21%" align="right" valign="bottom" style="font-size:7.5pt;color:'.PDF_MED.';padding-bottom:2mm;padding-right:2mm;">'.h($date).'</td>
  <td width="12%" align="right" valign="bottom" style="padding-bottom:2mm;white-space:nowrap;">
    <a href="#toc" style="background:'.PDF_TEAL.';color:#fff;padding:1.5mm 3mm;font-size:6.5pt;text-decoration:none;font-weight:bold;display:inline-block;">^ TOC</a>
  </td>
</tr>
</table>';}

function _toc_footer():string{return '<div style="text-align:center;font-size:7pt;color:'.PDF_MED.';border-top:0.3mm solid '.PDF_TEAL.';padding-top:1.5mm;font-style:italic;">Technologies International LLC &middot; Authorized MENA Representative for Amatrol &middot; DAC Worldwide &middot; Bayport</div>';}

// ── Standard header/footer ────────────────────────────────────────────────────
function _header(string $cn,string $date):string{return '
<table width="100%" style="font-size:7.5pt;color:'.PDF_MED.';border-bottom:0.4mm solid '.PDF_TEAL.';">
<tr>
  <td width="37%">Technologies International, LLC &amp; Kitmeer, Fz</td>
  <td width="30%" align="center" style="font-weight:bold;">'.h($cn).'</td>
  <td width="21%" align="right" style="padding-right:2mm;">'.h($date).'</td>
  <td width="12%" align="right" style="white-space:nowrap;">
    <a href="#toc" style="background:'.PDF_TEAL.';color:#fff;padding:1.5mm 3mm;font-size:6.5pt;text-decoration:none;font-weight:bold;display:inline-block;">^ TOC</a>
  </td>
</tr>
</table>';}

function _footer(string $qn,string $date):string{return '
<table width="100%" cellpadding="0" cellspacing="0" style="font-size:6pt;color:'.PDF_MED.';border-top:0.3mm solid '.PDF_TEAL.';">
<tr>
  <td width="11%">'.h($date).'</td>
  <td width="68%" align="center" style="color:'.PDF_TEAL.';font-size:6.5pt;font-weight:bold;">Technologies International (TI) / Kitmeer</td>
  <td width="21%" align="right" style="font-size:7.5pt;font-weight:bold;color:'.PDF_DEEP.';"></td>
</tr>
<tr>
  <td style="font-size:5.5pt;">'.h($qn).'</td>
  <td align="center" style="color:'.PDF_TEAL.';font-size:5.5pt;">North America: 3149 Broadway, STE 16, New York, NY 10027 USA &nbsp; Ph: +1 646.216.9043 &nbsp;&nbsp;&nbsp; Middle East: P.O. Box 500211, Dubai Internet City, UAE &nbsp; Ph: +971.4391.0970</td>
  <td align="right" style="font-size:5.5pt;">This offer expires in 60 days unless otherwise specified in writing.</td>
</tr>
</table>';}

// ── COVER: full-height, white background behind both logos, no www.kitmeer.com ─
function _cover(string $cn,string $co,string $qn,string $qd,string $lp,?string $tip,?string $amatrolLp=null,?string $dacLp=null,?string $bayportLp=null):string{
    // TI logo with explicit white background
    $tiImg=($tip&&file_exists($tip))
        ?'<div style="background:#ffffff;padding:3mm;display:inline-block;margin-bottom:4mm;">
            <img src="'.h($tip).'" style="max-width:56mm;max-height:20mm;display:block;">
          </div>'
        :'<b style="color:'.PDF_TEAL.';">Technologies International LLC</b>';

    // Client logo with white background
    $cLogo=($lp&&file_exists($lp))
        ?'<div style="background:#ffffff;padding:4mm;display:inline-block;">
            <img src="'.h($lp).'" style="max-width:66mm;max-height:52mm;display:block;">
          </div>'
        :'';

    // Vendor logos — same fixed height so they appear uniform
    // Vendor logos: stacked vertically, 100% larger (90mm wide, 32mm tall), ~25mm gap
    $vendorRows='';
    $vLogoPaths=['Amatrol'=>$amatrolLp,'DAC Worldwide'=>$dacLp,'Bayport'=>$bayportLp];
    foreach($vLogoPaths as $vName=>$vPath){
        if($vPath&&file_exists($vPath)){
            $vendorRows.='<tr><td style="text-align:center;padding:6mm 4mm 6mm;background:#ffffff;">'
                .'<img src="'.h($vPath).'" style="max-width:90mm;height:32mm;object-fit:contain;display:inline-block;">'
                .'</td></tr>'
                .'<tr><td style="height:8mm;background:'.PDF_SKY.';"></td></tr>';
        }
    }
    $vendorBar=$vendorRows
        ?'<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'.$vendorRows.'</table>'
        :'';

    return '
<table style="width:210mm;height:297mm;border-collapse:collapse;" cellpadding="0" cellspacing="0">
<tr style="height:297mm;">

  <!-- LEFT PANEL: white, full height -->
  <td style="width:68mm;height:297mm;background:#ffffff;vertical-align:top;padding:0;border-right:2mm solid '.PDF_TEAL.';">
    <table style="width:100%;height:297mm;border-collapse:collapse;" cellpadding="0" cellspacing="0">
      <tr style="height:10mm;"><td></td></tr>
      <tr style="height:30mm;"><td style="padding:0 6mm;vertical-align:middle;">'.$tiImg.'</td></tr>
      <tr style="height:8mm;"><td></td></tr>
      <tr style="height:22mm;"><td style="padding:0 6mm;vertical-align:top;">
        <div style="font-size:8pt;font-weight:bold;color:'.PDF_DEEP.';">TECHNOLOGIES INTERNATIONAL LLC</div>
        <div style="font-size:7pt;color:'.PDF_MED.';margin-top:1mm;line-height:1.6;">Authorized MENA Representative<br>Amatrol &middot; DAC Worldwide &middot; Bayport</div>
      </td></tr>
      <tr style="height:25mm;"><td></td></tr>
      <tr style="height:30mm;"><td style="padding:0 6mm;vertical-align:top;">
        <div style="font-size:7.5pt;font-weight:bold;color:'.PDF_TEAL.';margin-bottom:2mm;">North America:</div>
        <div style="font-size:7.5pt;color:'.PDF_MED.';line-height:1.65;">3149 Broadway, Suite 16<br>New York, NY 10027<br>Ph: +1 646.216.9043</div>
      </td></tr>
      <tr style="height:20mm;"><td></td></tr>
      <tr style="height:30mm;"><td style="padding:0 6mm;vertical-align:top;">
        <div style="font-size:7.5pt;font-weight:bold;color:'.PDF_TEAL.';margin-bottom:2mm;">Middle East:</div>
        <div style="font-size:7.5pt;color:'.PDF_MED.';line-height:1.65;">P.O. Box 500211<br>Dubai Internet City, UAE<br>Ph: +971.4391.0970</div>
      </td></tr>
      <tr style="height:22mm;"><td></td></tr>
      <tr><td></td></tr>
    </table>
  </td>

  <!-- RIGHT PANEL: sky blue, full height -->
  <td style="background:'.PDF_SKY.';vertical-align:top;padding:0;">
    <table style="width:100%;height:297mm;border-collapse:collapse;" cellpadding="0" cellspacing="0">
      <tr style="height:10mm;"><td></td></tr>
      <tr style="height:10mm;"><td style="padding:0 10mm;">
        <div style="font-size:7pt;color:'.PDF_TEAL.';letter-spacing:0.5mm;">TECHNICAL SPECIFICATIONS AND FINANCIAL OFFER</div>
      </td></tr>
      <tr style="height:3mm;"><td style="padding:0 10mm;"><div style="height:0.5mm;background:'.PDF_TEAL.';"></div></td></tr>
      <tr style="height:8mm;"><td></td></tr>
      <tr style="height:36mm;"><td style="padding:0 10mm;vertical-align:top;">
        <div style="font-size:34pt;font-weight:bold;color:'.PDF_DEEP.';line-height:1.05;">'.h($cn).'</div>
      </td></tr>
      <tr style="height:14mm;"><td style="padding:0 10mm;vertical-align:top;">
        <div style="font-size:14pt;color:'.PDF_TEAL.';">'.h($co).'</div>
      </td></tr>
      <tr style="height:12mm;"><td></td></tr>
      <tr style="height:62mm;"><td align="center" style="vertical-align:middle;">'.$cLogo.'</td></tr>
      <tr style="height:12mm;"><td></td></tr>
      <tr style="height:20mm;"><td style="padding:0 10mm;vertical-align:top;">
        <div style="font-size:6.5pt;color:'.PDF_MED.';margin-bottom:1mm;">QUOTATION NUMBER</div>
        <div style="font-size:13pt;font-weight:bold;color:'.PDF_DEEP.';">'.h($qn).'</div>
      </td></tr>
      <tr style="height:6mm;"><td></td></tr>
      <tr style="height:18mm;"><td style="padding:0 10mm;vertical-align:top;">
        <div style="font-size:6.5pt;color:'.PDF_MED.';margin-bottom:1mm;">DATE</div>
        <div style="font-size:13pt;font-weight:bold;color:'.PDF_DEEP.';">'.h($qd).'</div>
      </td></tr>
      <tr style="height:6mm;"><td></td></tr>
      <tr style="height:10mm;"><td style="padding:0 10mm;vertical-align:top;">
        <div style="font-size:7.5pt;font-style:italic;color:'.PDF_MED.';">This offer expires in 60 days unless otherwise specified in writing.</div>
      </td></tr>
      <tr style="height:6mm;"><td></td></tr>
      <tr><td style="padding:0 6mm;text-align:center;vertical-align:top;">'.$vendorBar.'</td></tr>
      <tr><td></td></tr>
    </table>
  </td>
</tr>
</table>';}

// ── TABLE OF CONTENTS ─────────────────────────────────────────────────────────
// Structure: Financial & Technical Specs (no section#), then Section 1, Section 2...,
// then Literature & Technical Datasheets (no section#)
// ── _addTocLinks: re-add FPDI Link() annotations on the TOC page (page 2) ─────
// All coordinates are mm from top-left of the physical PDF page (FPDI / FPDF space).
//
// TOC page layout breakdown (margin_top=48mm, content starts at Y=48mm):
//   48mm  │ content area begins
//   +40mm │ logo div: padding-top(4) + img max-height(28) + img margin-bottom(6) + padding-bot(2)
//   +14.5 │ "TABLE OF CONTENTS" teal bar: padding(4+4) + 18pt text (~6.4mm)
//   +20.6 │ "Click on any section" div: padding(2+2) + text + margin-bottom(14)
//   + 0.6 │ outer table top border
//   + 8.5 │ "SECTIONS" teal header: padding(2.5+2.5) + 9pt text (~3.2mm)
//   + 2.0 │ spacer row before first entry
//   ──────┤
//  ~134mm │ first entry row visual top  ← $entryY (with logo)
//   ~94mm │ first entry row visual top  ← $entryY (without logo, subtract 40mm logo block)
//
// rowGap=16.4mm measured from rendered PDF (rasterized at 200 DPI, May 2026):
//   Financial PAGE-button top: 136.7mm, Sec1: 153.0mm, Sec2: 169.5mm, Sec3: 185.9mm,
//   Sec4: 202.4mm, RFQ: 218.8mm → gaps alternate 16.38/16.51mm, avg 16.4mm.
//
// $rowH = $rowGap → seamless tiling: no dead pixels between rows, entire row area clickable.
// $w = full page width (210mm) → link spans page edge to page edge (covers text + PAGE button).
//
// To re-verify after layout changes: rasterize page 2 at 200 DPI with pdftoppm,
// then scan x≈1500px for teal bands; convert px→mm via scale = 200/25.4 px/mm.
//
// $secNavIds  : keyed by section index (0..N-1), populated only for sections WITH datasheets.
// $totalSections: total section count (with or without datasheets) — drives row count.
function _addTocLinks(\setasign\Fpdi\Fpdi $m, float $w, int $tocNavId, int $financialNavId, array $secNavIds, int $totalSections, int $rfqNavId=0, int $tenderNavId=0, bool $hasLogo=true): void {
    $entryY = $hasLogo ? 135.0 : 95.5;  // mm from page top to first entry row (see layout above)
    $rowGap = 16.4;   // mm between consecutive row top edges (measured)
    $rowH   = $rowGap; // seamless tiling: height = gap so every pixel is covered by exactly one link

    // Row 0: Financial & Technical Specifications
    $m->Link(0, $entryY, $w, $rowH, $financialNavId);

    // Rows 1..N: one per section in order
    for($i = 0; $i < $totalSections; $i++){
        $y = $entryY + $rowGap * ($i + 1);
        $m->Link(0, $y, $w, $rowH, isset($secNavIds[$i]) ? $secNavIds[$i] : $financialNavId);
    }

    // RFQ Comparison row (only present when an RFQ doc was uploaded)
    if($rfqNavId > 0){
        $m->Link(0, $entryY + $rowGap * ($totalSections + 1), $w, $rowH, $rfqNavId);
    }

    // Required Tender Documents row (after the RFQ row when both exist)
    if($tenderNavId > 0){
        $tRow = $totalSections + 1 + ($rfqNavId > 0 ? 1 : 0);
        $m->Link(0, $entryY + $rowGap * $tRow, $w, $rowH, $tenderNavId);
    }
}

function _toc(array $sections, string $cn, string $date, ?string $tip, array $tocPages=[], bool $showFinancial=true, bool $showSections=true): string {
    $rows='';
    $financialPg = $tocPages['financial'] ?? 3;

    // Financial entry
    if($showFinancial) $rows.=_tocRow('Financial &amp; Technical Specifications','financial',$financialPg,PDF_BGLT);

    // Quote section entries (suppressed for commercial-only mode)
    if($showSections) foreach($sections as $i=>$sec){
        $label   = 'Section '.($i+1).':&nbsp;&nbsp;'.h($sec['name']??'Section '.($i+1));
        $anchor  = 'sec'.($i+1);
        $pg      = $tocPages['sections'][$i] ?? ($financialPg > 0 ? $financialPg + 1 + $i : 3 + $i);
        $bg      = ($i%2===0)?'#f4fbfe':PDF_BGLT;
        $rows   .= _tocRow($label,$anchor,$pg,$bg);
    }

    // Literature entry removed per user request

    // RFQ Comparison section (optional - only shows if page > 0)
    $rfqPg = $tocPages['rfq_page'] ?? 0;
    if($rfqPg > 0){
        $rows .= _tocRow('Competitive Comparison to RFQ', 'rfq', $rfqPg, '#f0faf9');
    }

    // Required Tender Documents section (optional - only shows if page > 0)
    $tenderPg = $tocPages['tender_page'] ?? 0;
    if($tenderPg > 0){
        $rows .= _tocRow('Required Tender Documents', 'tender', $tenderPg, '#f4fbfe');
    }

    // Build logo img tag if available
    $logoHtml = ($tip && file_exists($tip))
        ? '<img src="'.$tip.'" style="max-height:28mm;max-width:80mm;display:block;margin:0 auto 6mm auto;">'
        : '';
    return '
<a name="toc"></a>
'.($logoHtml ? '<div style="text-align:center;padding:4mm 0 2mm 0;">'.$logoHtml.'</div>' : '').'
<table cellpadding="0" cellspacing="0" style="width:100%;margin-bottom:0;">
<tr><td style="background:'.PDF_TEAL.';padding:4mm 6mm;border:none;">
  <span style="font-size:18pt;font-weight:bold;color:#fff;">TABLE OF CONTENTS</span>
</td></tr>
</table>
<div style="background:#f0faf8;padding:2mm 6mm;margin-bottom:14mm;font-size:7.5pt;font-style:italic;color:'.PDF_MED.';">
  Click on any section below to jump directly to that page
</div>
<table cellpadding="0" cellspacing="0" style="border:0.6mm solid '.PDF_TEAL.';width:100%;">
  <tr><td colspan="2" style="background:'.PDF_TEAL.';padding:2.5mm 6mm;border:none;">
    <span style="font-size:9pt;font-weight:bold;color:#fff;letter-spacing:1mm;">SECTIONS</span>
  </td></tr>
  <tr><td colspan="2" style="height:2mm;border:none;background:#fff;"></td></tr>
  '.$rows.'
  <tr><td colspan="2" style="height:2mm;border:none;background:#fff;"></td></tr>
</table>';}

function _tocRow(string $label,string $anchor,int $pg,string $bg):string{
    return '
<tr style="background:'.$bg.';">
  <td style="padding:4.5mm 3mm 4.5mm 8mm;border:none;vertical-align:middle;">
    <span style="color:'.PDF_TEAL.';font-size:9pt;">&bull;</span>&nbsp;
    <span style="font-weight:bold;font-size:10.5pt;color:'.PDF_DEEP.';">'.$label.'</span>
  </td>
  <td style="width:32mm;background:'.PDF_TEAL.';text-align:center;padding:4.5mm 2mm;border:none;vertical-align:middle;">
    <span style="color:#fff;font-weight:bold;font-size:8.5pt;">PAGE '.$pg.'</span>
  </td>
</tr>
<tr><td colspan="2" style="height:2mm;border:none;background:#fff;"></td></tr>';}

// ── SECTION DIVIDER ───────────────────────────────────────────────────────────
// $num = 0 → Financial (gold bar), -1 → Literature (teal bar, no number), >0 → Section N (teal bar)
// White background added behind client logo
function _divider(int $num,string $name,bool $isFinancial,string $lp,string $tiLp=''):string{
    $accent = $isFinancial ? PDF_GOLD : PDF_TEAL;
    $watermark = $isFinancial ? '$' : ($num > 0 ? (string)$num : '');
    $sublabel  = $isFinancial ? 'FINANCIAL SUMMARY'
                : ($num > 0  ? 'S E C T I O N &nbsp; '.$num
                :               'L I T E R A T U R E');
    // Anchor assignment
    if($isFinancial)       $anchor='financial';
    elseif($num < 0)       $anchor='lit';
    else                   $anchor='sec'.$num;

    // Split long title
    $decoded=html_entity_decode($name,ENT_QUOTES,'UTF-8');
    $words=explode(' ',strtoupper(trim($decoded)));
    $l1='';$l2='';
    foreach($words as $w){if(strlen($l1)+strlen($w)<22||!$l1)$l1.=($l1?' ':'').$w;else $l2.=($l2?' ':'').$w;}
    $title=h($l1).($l2?'<br>'.h($l2):'');

    // Client logo with WHITE background
    $logo=($lp&&file_exists($lp))
        ?'<div style="text-align:center;margin-top:6mm;">
            <div style="background:#ffffff;display:inline-block;padding:4mm;">
              <img src="'.h($lp).'" style="max-width:54mm;max-height:34mm;display:block;">
            </div>
          </div>'
        :'';

    $dots='<span style="font-size:16pt;color:'.PDF_GOLD.';">&bull;</span>&nbsp;'
         .'<span style="font-size:16pt;color:'.PDF_TEAL.';">&bull;</span>&nbsp;'
         .'<span style="font-size:16pt;color:'.PDF_GOLD.';">&bull;</span>';

    return '
<a name="'.$anchor.'"></a>
<table style="width:210mm;height:297mm;border-collapse:collapse;" cellpadding="0" cellspacing="0">
<tr style="height:297mm;">
  <td style="width:8mm;height:297mm;background:'.$accent.';vertical-align:top;padding:0;"></td>
  <td style="height:297mm;background:'.PDF_SKY.';text-align:center;vertical-align:middle;padding:0 12mm;">
    <table style="width:100%;border-collapse:collapse;" cellpadding="0" cellspacing="0">
      <tr><td style="text-align:center;padding-top:8mm;">
        '.($tiLp&&file_exists($tiLp)
            ?'<div style="margin-bottom:5mm;"><img src="'.h($tiLp).'" style="max-height:20mm;max-width:65mm;display:inline-block;"></div>'
            :'').'
    '.($watermark?'<div style="font-size:88pt;font-weight:bold;color:#B8E2F2;line-height:1;">'.h($watermark).'</div>':'').'
      </td></tr>
      <tr><td style="text-align:center;padding:0 8mm;">
        <div style="background:#ffffff;padding:10mm 14mm 12mm;text-align:center;">
          <div style="font-size:8pt;color:'.$accent.';letter-spacing:1.5mm;margin-bottom:4mm;">'.$sublabel.'</div>
          <div style="font-size:22pt;font-weight:bold;color:'.PDF_DEEP.';line-height:1.25;">'.$title.'</div>
          <div style="margin:6mm 0 3mm;">'.$dots.'</div>
          '.$logo.'
        </div>
      </td></tr>
    </table>
  </td>
</tr>
</table>';} 

// ── FINANCIAL OFFER ───────────────────────────────────────────────────────────
// Returns the effective divisor value for a line, inheriting from parent if no explicit divisorKey set
function _lineDiv(array $line, array $sec, float $defaultDiv, array $divisors): float {
    if(!empty($line['divisorKey'])){
        foreach($divisors as $dv){
            if(($dv['key']??null)===$line['divisorKey']) return max(0.001,(float)($dv['value']??$defaultDiv));
        }
    }
    if(!empty($line['isSubOf'])){
        foreach($sec['items'] as $parent){
            if(($parent['model_id']??null)===$line['isSubOf']) return _lineDiv($parent,$sec,$defaultDiv,$divisors);
        }
    }
    return $defaultDiv;
}

function _financial(array $sections,float $divisor,float $discountPct,array $shipEstimates,float $installAmt,string $installLabel,string $country,array $divisors=[],string $incoterm='',array $paymentTerms=[]):string{
    $disc=$discountPct/100;
    $paymentTerms = $paymentTerms ?: tiNormalizePaymentTerms(['payment_wire'=>'30_70']);
    $isExw = tiIsExWorks($incoterm);
    $shipEstimates = tiShipEstimatesForExport($shipEstimates, $incoterm);
    $SHIP='Estimated Ocean Freight as of Quotation Date (Subject to Change Due to Market Conditions, Including Geopolitical Factors, Until Booking Confirmation)';
    $out='<h2 style="margin-bottom:3mm;color:'.PDF_DEEP.';">FINANCIAL OFFER — PRICING SUMMARY</h2>';
    $cols=[
        ['PKG#','رقم الحزمة','8mm','center'],
        ['ITEM#','رقم الصنف','15mm','center'],
        ['MODEL#','رقم الموديل','20mm','center'],
        ['DESCRIPTION','اسم الصنف','62mm','left'],
        ['QTY','الكمية','8mm','center'],
        ['UNIT PRICE','سعر الوحدة','22mm','right'],
        ['TOTAL PRICE','السعر الإجمالي','21mm','right'],
    ];
    $out.='<table cellpadding="0" cellspacing="0"><thead><tr>';
    foreach($cols as[$en,$ar,$w,$a])$out.='<th style="width:'.$w.';text-align:'.$a.';">'.$en.'<br><span dir="rtl" style="font-size:7pt;font-weight:normal;">'.$ar.'</span></th>';
    $out.='</tr></thead><tbody>';

    $grand=0;$pkg=0;
    $mi=0;$sc2=[]; // item counter persists across ALL sections for continuous numbering
    foreach($sections as $sec){
        $pkg++;
        // Section header: financial section uses its name directly, others use "SECTION N: name"
        $isFinSec=!empty($sec['_isFinancial']);
        $hdrLabel=$isFinSec?h(strtoupper($sec['name']??'Financial Offer and Technical Specifications')):'SECTION '.($pkg-1).': '.h(strtoupper($sec['name']??''));
        $out.='<tr><td colspan="7" class="sec-hdr">'.$hdrLabel.'</td></tr>';

        foreach($sec['items'] as &$line){
            if(empty($line['isSubOf'])){$mi++;$sc2[$mi]=0;$line['_mi']=$mi;$line['_ns']=(string)$mi;}
            else{$pi=0;foreach($sec['items'] as $ll)if(($ll['model_id']??'')===$line['isSubOf']&&isset($ll['_mi'])){$pi=$ll['_mi'];break;}if(!$pi)$pi=$mi;$sc2[$pi]=($sc2[$pi]??0)+1;$line['_ns']=$pi.'.'.$sc2[$pi];$line['_pi']=$pi;}
            // Honor user-edited item numbers from the quote builder verbatim (e.g. "A1 2.1.3.3")
            if(!empty($line['custom_num'])){$line['_ns']=(string)$line['custom_num'];$line['_custom']=true;}
        }unset($line);

        $sT=0;$ri=0;
        foreach($sec['items'] as $line){
            $isMain=empty($line['isSubOf']);$isOpt=!empty($line['isOptional']);
            $isIncl=!$isMain&&($line['subPricing']??'included')==='included';
            $isSynth=!empty($line['isSynthetic']??false);

            // ── Synthetic parent: derive price from included sub-items ──────
            if($isSynth && $isMain){
                $synthUnit=0.0;
                foreach($sec['items'] as $subItem){
                    if(($subItem['isSubOf']??null)===$line['model_id']&&($subItem['subPricing']??'included')==='included'&&empty($subItem['isOptional'])){
                        $sn=_resolveSell($subItem,$sec,$divisor,$divisors,$disc);
                        if($sn) $synthUnit+=round($sn,2)*max(1,(int)($subItem['qty']??1));
                    }
                }
                $qty=max(1,(int)($line['qty']??1));
                $tot=round($synthUnit*$qty,2);
                if($tot>0)$sT+=$tot;
                $bg=($ri++%2===0)?'':' class="row-alt"';
                $desc=h($line['description']??$line['product']['description']??'');
                $full='<strong>'.h($line['title_only']??$line['model_id']??'').'</strong>';
                if($desc) $full.='<br><span style="font-size:6.5pt;color:'.PDF_MED.';line-height:1.55;">'.nl2br($desc).'</span>';
                $uC='$'.number_format($synthUnit,2);
                $tC='$'.number_format($tot,2);
                $out.='<tr'.$bg.' style="background:#eef4f9;"><td style="text-align:center;">'.$pkg.'</td><td style="text-align:center;color:'.PDF_TEAL.';">'.$line['_ns'].'</td><td style="text-align:center;font-weight:bold;color:'.PDF_TEAL.';">'.h($line['model_id']??'').'</td><td>'.$full.'</td><td style="text-align:center;">'.$qty.'</td><td style="text-align:right;">'.$uC.'</td><td style="text-align:right;font-weight:bold;">'.$tC.'</td></tr>';
                continue;
            }

            $net=_resolveNet($line);
            $sell=_resolveSell($line,$sec,$divisor,$divisors,$disc);
            $qty=max(1,(int)($line['qty']??1));
            // For main items, fold included sub-item costs into unit sell price
            // This works even when parent has no price of its own (e.g. custom bundle parent with null intl_dist_net)
            $sellPerUnit=$sell??0;
            if($isMain){
                foreach($sec['items'] as $subItem){
                    if(($subItem['isSubOf']??null)===$line['model_id']&&($subItem['subPricing']??'included')==='included'&&empty($subItem['isOptional'])){
                        $sn=_resolveSell($subItem,$sec,$divisor,$divisors,$disc);
                        if($sn)$sellPerUnit+=round($sn,2)*max(1,(int)($subItem['qty']??1));
                    }
                }
            }
            $tot=(!$isIncl&&!$isOpt&&$sellPerUnit)?round($sellPerUnit*$qty,2):null;
            if($tot!==null)$sT+=$tot;
            if(!$isOpt&&$sell!==null&&$sell==0&&!$sellPerUnit)continue; // skip zero-price only if no sub-items folded in
            $bg=($ri++%2===0)?'':' class="row-alt"';
            $full=h($line['title_only']??$line['model_id']??'');
            if($d=nl2br(h($line['title_description']??'')))$full.='<br><span style="font-size:6.5pt;color:'.PDF_MED.';line-height:1.55;">'.$d.'</span>';
            $iN=(!empty($line['_custom'])) ? $line['_ns'] : ($isOpt ? $line['_ns'].'-OPT' : $line['_ns']);
            // Optional items: add disclaimer to description regardless of incl/standalone
            if($isOpt){
                $optDisclaimer='<br><span style="font-size:5.8pt;color:#c06000;font-style:italic;">ITEM PRICED, WITH ZERO (0) QTY AND NOT INCLUDED IN THE ABOVE ITEM, IF YOU WISH TO ADD THIS ITEM THE PRICE IS DISPLAYED AND FOR AN UPDATED QUOTE WITH IT INCLUDED PLEASE CONTACT YOUR SALES REPRESENTATIVE</span>';
                $full.=$optDisclaimer;
                $uC=$sell?'<span style="color:#c06000;font-weight:bold;">$'.number_format($sell,2).'</span>':'<span style="color:'.PDF_MED.';">See Amatrol</span>';
                $tC='';  // optional items: qty=0, no extended price
            }elseif($isIncl){
                // Reference the parent by its DISPLAYED number (custom number if the user set one)
                $pDisp=(string)($line['_pi']??0);
                foreach($sec['items'] as $plRef){
                    if(($plRef['model_id']??'')===$line['isSubOf']&&isset($plRef['_ns'])){$pDisp=$plRef['_ns'];break;}
                }
                $uC='<i style="font-size:7pt;color:'.PDF_MED.';">Included with<br>Item '.h($pDisp).'</i>';
                $tC='';
            }else{
                // Main items show combined unit price (includes all included subs per unit)
                $uC=$sellPerUnit?'$'.number_format($sellPerUnit,2):'<span style="color:'.PDF_MED.';">See Amatrol</span>';
                $tC=$tot?'$'.number_format($tot,2):'';
            }
            $optStyle=$isOpt?' style="color:#c06000;"':'';
            $out.='<tr'.$bg.'><td style="text-align:center;">'.$pkg.'</td><td style="text-align:center;font-weight:bold"'.$optStyle.'>'.h($iN).'</td><td style="text-align:center;font-weight:bold;">'.h($line['model_id']??'').'</td><td>'.$full.'</td><td style="text-align:center;">'.($isOpt?'0':$qty).'</td><td style="text-align:right;">'.$uC.'</td><td style="text-align:right;font-weight:bold;">'.$tC.'</td></tr>';
        }
        $grand+=$sT;
        $out.='<tr class="sub-total"><td colspan="6" style="text-align:right;padding:2mm 3mm;">TOTAL '.h(strtoupper($sec['name']??'')).':</td><td style="text-align:right;">$'.number_format($sT,2).'</td></tr>';
        $out.='<tr><td colspan="7" style="height:3mm;border:none;"></td></tr>';
    }

    // Shipping — never list Estimated Ocean Freight / TBD for EXW (Ex Works)
    $shipTotal=0;
    if(!$isExw){
        if(!empty($shipEstimates)){
            foreach($shipEstimates as $sh){
                $amt=(float)($sh['amt']??0);$shipTotal+=$amt;
                $lbl=strtoupper($sh['desc']??$SHIP);
                $out.='<tr><td colspan="6" style="text-align:right;font-style:italic;padding:1.5mm 3mm;font-size:7.5pt;">'.h($lbl).':</td><td style="text-align:right;">'.($amt?'$'.number_format($amt,2):'TBD').'</td></tr>';
            }
        } else {
            $out.='<tr><td colspan="6" style="text-align:right;font-style:italic;padding:1.5mm 3mm;font-size:7.5pt;">'.h(strtoupper($SHIP)).':</td><td style="text-align:right;">TBD</td></tr>';
        }
    }

    // Installation & Commissioning — always show if > 0
    if($installAmt>0){
        $out.='<tr><td colspan="6" style="text-align:right;font-style:italic;padding:1.5mm 3mm;font-size:7.5pt;">'.h(strtoupper($installLabel)).':</td><td style="text-align:right;">$'.number_format($installAmt,2).'</td></tr>';
        $shipTotal+=$installAmt;
    }

    $out.='<tr class="grand"><td colspan="6" style="text-align:right;padding:2.5mm 3mm;">GRAND TOTAL (USD):</td><td style="text-align:right;">$'.number_format($grand+$shipTotal,2).'</td></tr></tbody></table>';

    // Quotation totals summary
    $out.='<div style="height:5mm;"></div>';
    $out.='<table style="margin-left:auto;width:145mm;" cellpadding="0" cellspacing="0">';
    $out.='<tr class="tot-hdr"><td style="padding:2.5mm 4mm;">QUOTATION TOTALS:</td><td style="width:34mm;text-align:right;padding:2.5mm 4mm;">TOTALS</td></tr>';
    $sub=0;
    foreach($sections as $si=>$sec){
        $st=0;
        foreach($sec['items'] as $line){
            if(!empty($line['isOptional']))continue;
            if(!empty($line['isSubOf'])&&($line['subPricing']??'included')==='included')continue; // counted via parent
            if(empty($line['isSubOf'])){
                // Synthetic parent: sum sub extended prices
                if(!empty($line['isSynthetic']??false)){
                    $synthU=0.0;
                    foreach($sec['items'] as $subItem){
                        if(($subItem['isSubOf']??null)===$line['model_id']&&($subItem['subPricing']??'included')==='included'&&empty($subItem['isOptional'])){
                            $sn=_resolveSell($subItem,$sec,$divisor,$divisors,$disc);
                            if($sn) $synthU+=round($sn,2)*max(1,(int)($subItem['qty']??1));
                        }
                    }
                    $st+=round($synthU*max(1,(int)($line['qty']??1)),2);
                    continue;
                }
                $net=_resolveNet($line);
                $sell=_resolveSell($line,$sec,$divisor,$divisors,$disc);
                // Fold sub-item prices into parent even when parent has no price of its own
                // (e.g. custom bundle parent like TI-TT-SM1 with null intl_dist_net)
                $spU=$sell??0;
                foreach($sec['items'] as $subItem){
                    if(($subItem['isSubOf']??null)===$line['model_id']&&($subItem['subPricing']??'included')==='included'&&empty($subItem['isOptional'])){
                        $sn=_resolveSell($subItem,$sec,$divisor,$divisors,$disc);
                        if($sn)$spU+=round($sn,2)*max(1,(int)($subItem['qty']??1));
                    }
                }
                if($spU) $st+=round($spU*max(1,(int)($line['qty']??1)),2);
            }
        }
        $sub+=$st;
        $lbl=empty($sec['_isFinancial'])?'SECTION '.($si).': '.h(strtoupper($sec['name']??'')):h(strtoupper($sec['name']??''));
        $out.='<tr class="tot-row"><td style="padding:2mm 4mm;">'.$lbl.'</td><td style="text-align:right;padding:2mm 4mm;">$'.number_format($st,2).'</td></tr>';
    }
    $out.='<tr class="tot-sub"><td style="text-align:right;padding:2mm 4mm;">SUBTOTAL:</td><td style="text-align:right;padding:2mm 4mm;">$'.number_format($sub,2).'</td></tr>';

    // Shipping in totals — omit for EXW
    if(!$isExw){
        if(!empty($shipEstimates)){
            foreach($shipEstimates as $sh){$amt=(float)($sh['amt']??0);$out.='<tr class="tot-row"><td style="text-align:right;padding:2mm 4mm;font-size:7.5pt;">'.h(strtoupper($sh['desc']??$SHIP)).':</td><td style="text-align:right;padding:2mm 4mm;">'.($amt?'$'.number_format($amt,2):'TBD').'</td></tr>';}
        } else {
            $out.='<tr class="tot-row"><td style="text-align:right;padding:2mm 4mm;font-size:7.5pt;">'.h(strtoupper($SHIP)).':</td><td style="text-align:right;padding:2mm 4mm;">TBD</td></tr>';
        }
    }
    if($installAmt>0){
        $out.='<tr class="tot-row"><td style="text-align:right;padding:2mm 4mm;">'.h(strtoupper($installLabel)).':</td><td style="text-align:right;padding:2mm 4mm;">$'.number_format($installAmt,2).'</td></tr>';
    }
    $total=$sub+$shipTotal;
    $out.='<tr class="tot-grand"><td style="text-align:right;padding:2.5mm 4mm;">TOTAL QUOTATION (USD):</td><td style="text-align:right;padding:2.5mm 4mm;">$'.number_format($total,2).'</td></tr></table>';

    // Shipment terms (always state explicitly; critical for EXW)
    $shipTermsLabel = trim($incoterm) !== '' ? strtoupper(trim($incoterm)) : 'EXW (EX WORKS)';
    $out.='<div style="height:4mm;"></div>';
    $out.='<p class="pay-lbl">SHIPMENT TERMS: '.h($shipTermsLabel).'</p>';
    if($isExw){
        $out.='<p style="font-size:8pt;margin:1mm 0 2mm;">Shipment terms are Ex Works. Estimated ocean freight is not included and is not listed on this quotation.</p>';
    }

    // Payment terms
    $out.='<div style="height:3mm;"></div>';
    $out.='<p class="pay-lbl">INTERNATIONAL PAYMENT OPTIONS</p>';
    $wireText = tiWirePaymentText($paymentTerms['wire'] ?? '30_70');
    if($wireText !== ''){
        $out.='<p class="pay-lbl" style="font-size:8pt;margin-top:2mm;">Payment via Wire Transfer:</p>';
        $out.='<p style="font-size:8pt;margin:0 0 2mm;">'.h($wireText).'</p>';
        $out.='<p class="pay-lbl" style="font-size:8pt;">Incoming International Wire Payment Instructions:</p>';
        foreach(['Bank SWIFT Code: NRTHUS33','ABA# 026013673','For Further credit to: TD BANK NA, Wilmington DE','In Favor of Beneficiary Account Number: 4247908415','In Favor of Beneficiary: Technologies International, LLC','3149 Broadway, Suite 16, New York, NY 10027 &nbsp;&nbsp; Ph: 646.216.9043'] as $wl)$out.='<div style="font-size:8pt;margin:0.5mm 0;">'.$wl.'</div>';
    }
    if(!empty($paymentTerms['include_lc'])){
        $out.='<p class="pay-lbl" style="font-size:8pt;margin-top:3mm;">Payment via Letter of Credit:</p>';
        $lcBody = trim((string)($paymentTerms['lc_terms'] ?? '')) ?: TI_DEFAULT_LC_TERMS;
        foreach(preg_split("/\r\n|\n|\r/", $lcBody) as $lcLine){
            $out.='<div style="font-size:8pt;margin:0.5mm 0;">'.h($lcLine === '' ? ' ' : $lcLine).'</div>';
        }
    }
    $out.='<div style="height:3mm;"></div><div class="nonneg">**** THESE TERMS ARE NON-NEGOTIABLE ****</div>';
    $cc=strtolower(trim($country));$ksa=in_array($cc,['saudi arabia','ksa','kingdom of saudi arabia']);
    $hz=$ksa?'60HZ':'50HZ';$cu=$ksa?'KINGDOM OF SAUDI ARABIA (KSA)':strtoupper($country?:'DESTINATION COUNTRY');
    $out.='<div class="voltage">**** ALL MATERIALS WILL BE IN AMERICAN ENGLISH UNLESS OTHERWISE SPECIFIED<br>EQUIPMENT WILL BE SUPPLIED IN '.h($cu).' REQUIRED VOLTAGES, 220 VOLT / SINGLE PHASE / '.$hz.' OR 380 VOLT / 3 PHASE / '.$hz.'</div>';
    $out.='<div class="voltage" style="margin-top:2mm;">SHIPPING WILL BE 100-150 DAYS FROM DATE OF PURCHASE. INCREASED DELIVERY TIME IS DUE TO CURRENT LEAD TIME ON RAW MATERIALS AND MANUFACTURED MATERIALS AS CORE COMPONENTS OF OUR LEARNING SYSTEMS.</div>';
    return $out;
}

// ── helpers ───────────────────────────────────────────────────────────────────
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}

function _fetchTempImg(string $url):?string{
    $ctx=stream_context_create(['http'=>['timeout'=>8,'ignore_errors'=>true]]);
    $data=@file_get_contents($url,false,$ctx);
    if(!$data||strlen($data)<100)return null;
    $tmp=tempnam(sys_get_temp_dir(),'ti_logo_').'.png';
    file_put_contents($tmp,$data);return $tmp;
}

function _makeFpdi(): \setasign\Fpdi\Fpdi {
    // If fpdi-pdf-parser is installed, use the PDF 1.5+ compatible version
    if (class_exists('\\setasign\\FpdiPdfParser\\PdfParser\\StreamReader')) {
        return new \setasign\Fpdi\Fpdi();  // will auto-use the parser
    }
    return new \setasign\Fpdi\Fpdi();
}

function _getModelVariants(string $modelId): array {
    $parts = explode('-', $modelId);
    $v = [$modelId];
    // v2: strip trailing alpha after last hyphen e.g. "95-PM1-A" → "95-PM1"
    if(preg_match('/^(.+?)-([A-Za-z]{1,2})$/', $modelId, $m)) $v[]=$m[1];
    // v2b: strip trailing alpha without hyphen e.g. "82-610W"→"82-610", "95-PM1A"→"95-PM1"
    if(preg_match('/^(.*?[0-9])([A-Za-z]{1,2})$/', $modelId, $m2) && $m2[1]!==''){
        $v[]=$m2[1];
        $v[]=$m2[1].'-'.$m2[2]; // hyphenated form e.g. "95-PM1-A"
    }
    // v3: strip -XVAR suffix
    $v3=preg_replace('/-X[A-Z]{2,4}$/i','',$modelId);
    if($v3!==$modelId) $v[]=$v3;
    // v4+v5: strip trailing hyphen segments
    if(count($parts)>=2) $v[]=implode('-',array_slice($parts,0,-1));
    if(count($parts)>=3) $v[]=implode('-',array_slice($parts,0,-2));
    return array_values(array_unique(array_filter($v)));
}

// Convert any stored path (relative or absolute) to an absolute filesystem path
function _absDocPath(string $stored): string {
    // Already absolute
    if($stored[0]==='/' || (strlen($stored)>2 && $stored[1]===':')) return $stored;
    // Relative — resolve from this file's directory
    return __DIR__.'/'.$stored;
}

function getDocPathForModel(string $modelId):?string{
    static $cache=[];
    if(array_key_exists($modelId,$cache))return $cache[$modelId];
    try{
        require_once __DIR__.'/db.php';$db=getDB();
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES,true);

        $variants = _getModelVariants($modelId);

        // Exact/variant match — ORDER BY LENGTH DESC so most specific wins
        $ph=implode(',',array_fill(0,count($variants),'?'));
        $stmt=$db->prepare("SELECT combined_pdf FROM product_documents WHERE model_id IN ($ph) ORDER BY LENGTH(model_id) DESC LIMIT 1");
        $stmt->execute($variants);
        $row=$stmt->fetch(\PDO::FETCH_ASSOC);
        if($row && $row['combined_pdf']){
            // Normalize: try stored path as-is first, then relative, then just basename in product_docs/
            $stored = $row['combined_pdf'];
            foreach([
                _absDocPath($stored),
                $stored,
                __DIR__.'/product_docs/'.basename($stored),
            ] as $cand){
                if($cand && file_exists($cand)) return $cache[$modelId]=$cand;
            }
            // Path in DB but file missing — fall through to filesystem scan
        }

        // LIKE fallback
        foreach($variants as $vv){
            if(strlen($vv)<3) continue;
            $stmt2=$db->prepare("SELECT combined_pdf FROM product_documents WHERE model_id LIKE ? ORDER BY LENGTH(model_id) DESC LIMIT 1");
            $stmt2->execute([$vv.'%']);
            $row2=$stmt2->fetch(\PDO::FETCH_ASSOC);
            if($row2 && $row2['combined_pdf']){
                $stored2=$row2['combined_pdf'];
                foreach([
                    _absDocPath($stored2),
                    $stored2,
                    __DIR__.'/product_docs/'.basename($stored2),
                ] as $cand2){
                    if($cand2 && file_exists($cand2)) return $cache[$modelId]=$cand2;
                }
            }
        }
        // Final fallback: check product_docs/ filesystem for any variant
        foreach($variants as $vv){
            if(strlen($vv)<2) continue;
            $safeV = preg_replace('/[^A-Za-z0-9_\-]/', '', $vv);
            if(!$safeV) continue;
            $tryPath = __DIR__.'/product_docs/'.$safeV.'.pdf';
            if(file_exists($tryPath)) return $cache[$modelId]=$tryPath;
        }
        return $cache[$modelId]=null;
    }catch(\Throwable $e){return $cache[$modelId]=null;}
}
