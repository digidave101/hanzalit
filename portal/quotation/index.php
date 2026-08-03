<?php // ti-kitmeer.com/portal/quotation/index.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quotation Engine — TI-Kitmeer</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<style>
:root{
  --bd:#0d2137;--bm:#1a6fa0;--bb:#1a9ad4;
  --teal:#0db8a8;--tl:#2dd4a0;
  --bg:#eef4f9;--wh:#ffffff;--tx:#0d2137;--sub:#4a6a82;
  --bdr:#c8dde8;--gold:#c9a227;
  --sh:0 2px 12px rgba(13,33,55,.10);
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--tx);min-height:100vh;display:flex;flex-direction:column;}
.header{background:var(--bd);padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:64px;border-bottom:3px solid var(--teal);}
.logos{display:flex;align-items:center;gap:16px;}
.ldiv{width:1px;height:40px;background:rgba(255,255,255,.15);}
.hdr-r{display:flex;align-items:center;gap:10px;}
.back-btn{font-size:11px;color:#a8c8d8;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.07);padding:5px 11px;border-radius:5px;text-decoration:none;font-weight:600;}
.badge{font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--tl);font-weight:700;background:rgba(45,212,160,.1);border:1px solid rgba(45,212,160,.25);padding:3px 9px;border-radius:4px;}
.qnd{font-size:10px;color:#7aaac8;}
.app{display:grid;grid-template-columns:1fr 1fr;flex:1;height:calc(100vh - 64px);overflow:hidden;}

/* LEFT */
.sp{display:flex;flex-direction:column;border-right:1px solid var(--bdr);overflow:hidden;background:var(--wh);}
.ph{padding:12px 14px 10px;background:#f4f8fc;border-bottom:1px solid var(--bdr);}
.ph h2{font-size:10px;text-transform:uppercase;letter-spacing:2px;color:var(--sub);margin-bottom:8px;font-weight:700;}
.sr{display:flex;gap:6px;margin-bottom:6px;}
input,select{padding:7px 10px;background:var(--wh);border:1.5px solid var(--bdr);color:var(--tx);border-radius:6px;font-size:.78rem;font-family:inherit;transition:border-color .15s;}
input:focus,select:focus{outline:none;border-color:var(--bb);}
input::placeholder{color:#8aaabb;}
#si{flex:1;}
.rc{font-size:.63rem;color:var(--sub);margin-top:2px;}
.ps{flex:1;overflow-y:auto;padding:5px 7px;}
.ps::-webkit-scrollbar{width:4px;}
.ps::-webkit-scrollbar-thumb{background:var(--bdr);border-radius:2px;}
.pr{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:6px;margin-bottom:3px;cursor:pointer;border:1px solid transparent;transition:all .1s;}
.pr:hover{background:#f4f8fc;border-color:var(--bdr);}
.pr.iq{border-color:var(--teal);background:rgba(13,184,168,.06);}
.pm{font-size:.68rem;font-weight:700;color:var(--bm);min-width:88px;line-height:1.4;}
.pc{font-size:.57rem;color:var(--sub);background:#eef4f9;padding:1px 4px;border-radius:2px;margin-top:2px;}
.pt{font-size:.74rem;flex:1;line-height:1.35;}
.pmf{font-size:.59rem;color:var(--sub);margin-top:1px;}
.pp{text-align:right;min-width:72px;font-size:.7rem;color:var(--bd);font-weight:700;}
.pa{background:linear-gradient(135deg,var(--bm),var(--teal));color:#fff;border:none;border-radius:4px;padding:4px 10px;font-size:.67rem;font-weight:700;cursor:pointer;white-space:nowrap;}
.pa:hover{opacity:.82;}
.pa.added{background:#e8f5e9;color:#1a9a6a;border:1px solid #a8d5bb;cursor:pointer;font-size:.6rem;}
.pdf-btn{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;background:rgba(13,184,168,.12);border:1px solid rgba(13,184,168,.35);border-radius:3px;color:var(--teal);font-size:.6rem;font-weight:700;cursor:pointer;text-decoration:none;white-space:nowrap;}
.pdf-btn:hover{background:rgba(13,184,168,.22);color:var(--teal);}
.pdf-badge{font-size:.58rem;color:var(--teal);margin-left:3px;opacity:.8;}

/* RIGHT */
.qp{display:flex;flex-direction:column;overflow:hidden;background:var(--wh);}
.qph{padding:10px 14px;background:#f4f8fc;border-bottom:1px solid var(--bdr);}
.qph h2{font-size:10px;text-transform:uppercase;letter-spacing:2px;color:var(--sub);margin-bottom:8px;font-weight:700;}
.mr{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px;align-items:center;}
.mr label{font-size:.67rem;color:var(--sub);white-space:nowrap;}
.ir{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:6px;}
.ir label{font-size:.67rem;color:var(--sub);white-space:nowrap;}
.ilw{display:flex;gap:4px;align-items:center;flex:1;}
#incoLocation{flex:1;}
.alb{font-size:.65rem;color:var(--bm);background:none;border:1px solid var(--bm);border-radius:4px;padding:3px 7px;cursor:pointer;white-space:nowrap;}
.dr{display:flex;align-items:center;gap:10px;font-size:.71rem;margin-bottom:6px;color:var(--sub);}
.dr input{width:72px;text-align:center;}
.dp{font-size:.67rem;color:var(--bm);background:#eef4f9;padding:3px 8px;border-radius:4px;}
.qa{display:flex;gap:6px;flex-wrap:wrap;}
.btn{padding:5px 13px;font-size:.67rem;font-weight:700;border:none;border-radius:5px;cursor:pointer;text-transform:uppercase;letter-spacing:.05em;transition:all .1s;white-space:nowrap;font-family:inherit;}
.bg{background:linear-gradient(135deg,#1a9a6a,var(--tl));color:#fff;}
.bg:hover{opacity:.85;}
.bb2{background:linear-gradient(135deg,var(--bm),var(--bb));color:#fff;}
.bb2:hover{opacity:.85;}
.bo{background:transparent;color:var(--bm);border:1.5px solid var(--bm);}
.bo:hover{background:var(--bm);color:#fff;}
.br{background:#fff0f0;color:#c04040;border:1px solid #f0c0c0;}
.br:hover{background:#ffe0e0;}
.bsm{padding:3px 8px;font-size:.62rem;}

/* QUOTE BODY */
.qb{flex:1;overflow-y:auto;padding:6px 8px;}
.qb::-webkit-scrollbar{width:4px;}
.qb::-webkit-scrollbar-thumb{background:var(--bdr);border-radius:2px;}
.eq{display:flex;flex-direction:column;align-items:center;justify-content:center;height:160px;color:var(--sub);font-size:.8rem;gap:8px;}

/* SECTION */
.sb{margin-bottom:10px;}
.sh{display:flex;align-items:center;gap:6px;padding:6px 8px;background:linear-gradient(90deg,var(--bd),var(--bm));border-radius:6px 6px 0 0;color:#fff;}
.sh input{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:4px;padding:3px 8px;font-size:.75rem;font-weight:700;flex:1;font-family:inherit;}
.sh input::placeholder{color:rgba(255,255,255,.5);}
.sh input:focus{outline:none;background:rgba(255,255,255,.2);}
.st{font-size:.67rem;color:var(--tl);white-space:nowrap;}
.sd{background:none;border:none;color:rgba(255,255,255,.5);cursor:pointer;font-size:.85rem;}
.sd:hover{color:#fff;}
.si2{border:1px solid var(--bdr);border-top:none;border-radius:0 0 6px 6px;padding:4px;}
.asi{width:100%;padding:5px;background:#f4f8fc;border:1px dashed var(--bdr);border-radius:4px;color:var(--sub);font-size:.67rem;cursor:pointer;margin-top:4px;font-family:inherit;}
.asi:hover{border-color:var(--teal);color:var(--teal);}

/* QUOTE ITEM */
.qi{display:grid;grid-template-columns:18px 90px 1fr 52px 80px 80px 24px;gap:4px;align-items:start;padding:6px;background:var(--wh);border:1px solid var(--bdr);border-radius:5px;margin-bottom:3px;}
.qi.sub{margin-left:14px;border-left:3px solid var(--teal);background:#f9fdfd;}
.qn2{font-size:.63rem;color:var(--sub);font-weight:700;padding-top:2px;}
.qn2-input{font-size:.72rem;font-weight:700;color:var(--bd);text-align:center;width:46px;border:1px solid transparent;background:transparent;padding:1px 2px;border-radius:3px;cursor:text;display:block;}
.qn2-input:hover{border-color:var(--teal);background:#f0fafa;}
.qn2-input:focus{outline:none;border-color:var(--teal);background:#fff;box-shadow:0 0 0 2px rgba(10,123,138,.15);}
.qm{font-size:.67rem;font-weight:700;color:var(--bm);line-height:1.4;}
.qtl{font-size:.7rem;line-height:1.35;}
.qtl-desc{font-size:.63rem;color:var(--sub);margin-top:2px;line-height:1.45;white-space:pre-wrap;max-height:3em;overflow:hidden;}
.qdw{font-size:.58rem;color:#d07a00;margin-top:2px;grid-column:1/-1;}
.qq input{width:44px;padding:3px 5px;background:#f4f8fc;border:1.5px solid var(--bdr);color:var(--tx);border-radius:4px;font-size:.71rem;text-align:center;font-family:inherit;}
.qq input:focus{outline:none;border-color:var(--bb);}
.qc{font-size:.67rem;color:var(--sub);text-align:right;padding-top:2px;line-height:1.4;}
.qs{font-size:.73rem;font-weight:700;color:var(--bd);text-align:right;padding-top:2px;}
.qs.inc{font-size:.63rem;color:var(--sub);font-weight:400;font-style:italic;}
.qd{background:none;border:none;color:var(--sub);cursor:pointer;font-size:.9rem;text-align:center;}
.qd:hover{color:#c04040;}
.qso{grid-column:1/-1;display:flex;gap:8px;align-items:center;font-size:.62rem;color:var(--sub);margin-top:2px;flex-wrap:wrap;}
.qso label{display:flex;align-items:center;gap:3px;cursor:pointer;}
.qi.opt{border-left:3px solid #c06000;background:#fffdf5;}
.req-modal{display:none;position:fixed;inset:0;background:rgba(13,33,55,.45);z-index:500;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.req-modal.open{display:flex;}
.req-mbox{background:var(--wh);border-radius:12px;width:540px;max-width:96vw;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 8px 40px rgba(13,33,55,.22);border-top:3px solid var(--teal);}
.req-mhead{padding:14px 18px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;justify-content:space-between;}
.req-mhead h3{font-size:.82rem;font-weight:700;color:var(--bd);}
.req-mbody{padding:14px 18px;flex:1;overflow-y:auto;}
.req-search{width:100%;background:#f4f8fc;border:1.5px solid var(--bdr);border-radius:7px;padding:8px 11px;font-size:.75rem;font-family:inherit;outline:none;margin-bottom:10px;}
.req-search:focus{border-color:var(--bb);background:#fff;}
.req-result{display:flex;align-items:center;gap:10px;padding:7px 10px;border:1px solid var(--bdr);border-radius:7px;margin-bottom:5px;cursor:pointer;transition:all .12s;background:var(--wh);}
.req-result:hover{border-color:var(--teal);background:#f4fbfa;}
.req-result .rm{font-size:.65rem;font-weight:700;color:var(--teal);min-width:70px;}
.req-result .rt{flex:1;font-size:.7rem;color:var(--tx);}
.req-result .rc{font-size:.63rem;color:var(--sub);}
.req-result .rp{font-size:.7rem;font-weight:600;color:var(--bd);white-space:nowrap;}
.req-none{font-size:.72rem;color:var(--sub);text-align:center;padding:18px 0;}
.ma-sug{display:flex;align-items:center;gap:8px;padding:5px 9px;border:1px solid var(--bdr);border-radius:6px;margin-bottom:3px;cursor:pointer;font-size:.68rem;background:var(--wh);transition:all .1s;}
.ma-sug:hover{border-color:var(--teal);background:#f4fbfa;}
.ma-sug .ms-mid{font-weight:700;color:var(--teal);min-width:68px;}
.ma-sug .ms-t{flex:1;color:var(--tx);}
.ma-sug .ms-c{color:var(--sub);font-size:.62rem;}
.ma-tag{display:inline-flex;align-items:center;gap:5px;background:rgba(26,111,160,.08);border:1px solid rgba(26,111,160,.25);border-radius:12px;padding:2px 8px;font-size:.65rem;font-weight:600;color:var(--bm);}
.ma-tag .mt-rm{cursor:pointer;color:#c04040;font-size:.72rem;margin-left:2px;}
.ma-tag .mt-rm:hover{color:#900;}
.req-mfoot{padding:10px 18px;border-top:1px solid var(--bdr);display:flex;justify-content:flex-end;gap:8px;}
.ship-row{display:flex;gap:6px;align-items:center;margin-bottom:4px;}
.ship-row input[type=text]{flex:1;font-size:.72rem;}
.ship-row input[type=number]{width:110px;text-align:right;font-size:.72rem;}
.ship-total{display:flex;justify-content:space-between;font-size:.74rem;font-weight:700;color:var(--bd);padding:4px 0;border-top:1px solid var(--bdr);}

/* FOOTER */
.qf{background:#f4f8fc;border-top:2px solid var(--teal);padding:10px 14px;}
.tg{display:grid;grid-template-columns:1fr 1fr;gap:4px;}
.tl{display:flex;justify-content:space-between;align-items:center;font-size:.73rem;padding:1px 0;}
.tl.big{font-size:.88rem;font-weight:700;color:var(--bd);margin-top:3px;border-top:1px solid var(--bdr);padding-top:4px;}
.grn{color:#1a9a6a;}

/* TOAST */
/* Dep modal item list */
.dep-item{display:flex;align-items:flex-start;gap:9px;padding:8px 10px;border:1px solid var(--bdr);border-radius:6px;margin-bottom:6px;background:#f9fdfd;}
.dep-item input[type=checkbox]{margin-top:2px;accent-color:var(--teal);width:14px;height:14px;flex-shrink:0;}
.dep-item-info{flex:1;}
.dep-mid{font-size:.71rem;font-weight:700;color:var(--bm);}
.dep-title{font-size:.72rem;color:var(--tx);line-height:1.35;margin-top:1px;}
.dep-class{font-size:.62rem;color:var(--sub);margin-top:2px;}
.dep-price{font-size:.68rem;font-weight:700;color:var(--bd);white-space:nowrap;}
.rec-item{display:flex;align-items:flex-start;gap:9px;padding:8px 10px;border:1px solid rgba(201,162,39,.3);border-radius:6px;margin-bottom:6px;background:#fffdf5;}
.rec-item input[type=checkbox]{margin-top:2px;accent-color:#c9a227;width:14px;height:14px;flex-shrink:0;}
.rec-badge{display:inline-block;font-size:.58rem;font-weight:700;color:#c9a227;background:rgba(201,162,39,.12);border:1px solid rgba(201,162,39,.3);border-radius:3px;padding:1px 5px;margin-left:4px;vertical-align:middle;}
/* Quote search */
.qsearch{padding:5px 9px;background:var(--wh);border:1.5px solid var(--bdr);color:var(--tx);border-radius:6px;font-size:.75rem;font-family:inherit;}
.qsearch:focus{outline:none;border-color:var(--bb);}

/* MODALS */
.overlay{display:none;position:fixed;inset:0;background:rgba(13,33,55,.55);z-index:300;align-items:center;justify-content:center;}
.overlay.open{display:flex;}
.mbox{background:var(--wh);border-radius:10px;padding:18px;max-width:400px;width:95%;box-shadow:var(--sh);}
.mbox h3{font-size:.85rem;color:var(--bd);margin-bottom:12px;}
.mbox.wide{max-width:580px;max-height:85vh;overflow-y:auto;}
.mbox.wide h3{font-size:.92rem;}
.mg{display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:.73rem;}
.mrow{display:flex;flex-direction:column;gap:1px;}
.mrow span:first-child{color:var(--sub);font-size:.62rem;text-transform:uppercase;letter-spacing:.05em;}
.sl{max-height:220px;overflow-y:auto;}
.so{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:5px;border:1px solid var(--bdr);margin-bottom:4px;cursor:pointer;font-size:.74rem;}
.so:hover{background:#f4f8fc;border-color:var(--bb);}

#loadBar{position:fixed;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--bb),var(--tl));transform:scaleX(0);transform-origin:left;transition:transform .3s ease;z-index:600;}
.pv{cursor:pointer;padding:2px 4px;border-radius:3px;transition:background .1s;}
.ml{font-size:.62rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px;font-weight:700;}
.pv:hover{background:rgba(26,111,160,.1);text-decoration:underline;}
.pv.price-edited{color:#c04040!important;font-weight:700;}
.pe-flag{color:#c04040;font-size:.6rem;vertical-align:super;cursor:pointer;}
.price-edited-note{font-size:.58rem;color:#c04040;display:block;cursor:pointer;}
.div-chip{display:flex;align-items:center;gap:3px;background:#f4f8fc;border:1px solid var(--bdr);border-radius:5px;padding:2px 5px;}
.div-chip .div-badge{font-size:.58rem;font-weight:700;color:#fff;padding:1px 5px;border-radius:3px;cursor:default;}
.div-chip .div-name{border:none;background:transparent;font-size:.7rem;color:var(--tx);outline:none;font-family:inherit;}
.div-chip .div-val{border:1px solid var(--bdr);border-radius:3px;background:var(--wh);font-size:.72rem;padding:2px 4px;color:var(--bd);font-weight:700;}
.div-chip .div-val:focus{outline:none;border-color:var(--bb);}
.div-chip .div-del{background:none;border:none;color:#ccc;cursor:pointer;font-size:.8rem;padding:0 2px;}
.div-chip .div-del:hover{color:var(--red);}
.div-sel{font-size:.6rem;padding:2px 4px;border:1px solid var(--bdr);border-radius:3px;background:#f4f8fc;color:var(--sub);cursor:pointer;}
.div-sel:focus{outline:none;border-color:var(--teal);}
/* Move up/down item buttons */
.mv-btn{background:none;border:1px solid var(--bdr);color:var(--sub);border-radius:3px;cursor:pointer;font-size:.6rem;padding:0 3px;line-height:1.5;}
.mv-btn:hover{background:#e8f0f8;color:var(--bd);}
.mv-wrap{display:flex;flex-direction:column;gap:1px;align-items:center;justify-content:center;min-width:16px;}
/* Section restart-numbering toggle */
.sec-restart-btn{background:none;border:1px solid rgba(255,255,255,.3);color:rgba(255,255,255,.6);border-radius:3px;cursor:pointer;font-size:.58rem;padding:2px 5px;white-space:nowrap;font-family:inherit;margin-right:2px;}
.sec-restart-btn.on{background:rgba(13,184,168,.35);color:#fff;border-color:var(--teal);}
.sec-restart-btn:hover{background:rgba(255,255,255,.15);}
/* Synthetic parent item */
.qi.synth{background:linear-gradient(135deg,#eef4f9,#e4eef8);border:1.5px solid var(--bb);border-left:4px solid var(--bm);}
.qi.synth .qs{color:var(--bm);font-weight:800;font-size:.82rem;}
.synth-title-input{background:transparent;border:none;border-bottom:1px dashed var(--bm);font-size:.78rem;font-weight:700;color:var(--bd);outline:none;width:200px;font-family:inherit;}
.synth-desc-input{background:transparent;border:none;font-size:.63rem;color:var(--sub);outline:none;width:100%;font-family:inherit;margin-top:2px;}
</style>
</head>
<body>
<!-- ══ TI-KITMEER: MODULE INSTRUCTIONS TAB (auto-injected) ══ -->
<style>
.ti-help-ribbon{position:fixed;top:0;left:50%;transform:translateX(-50%);z-index:2147483000;display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#0d2137,#1a6fa0);color:#fff !important;font:700 11.5px/1 'Segoe UI',Arial,sans-serif;padding:6px 16px 8px;border-radius:0 0 10px 10px;text-decoration:none;box-shadow:0 3px 10px rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.22);border-top:none;opacity:.93;transition:all .18s ease;white-space:nowrap}
.ti-help-ribbon:hover{opacity:1;padding-top:9px;padding-bottom:10px;box-shadow:0 5px 16px rgba(0,0,0,.4);color:#fff}
.ti-help-ribbon .thr-ic{font-size:13px}
.ti-help-ribbon .thr-gold{color:#e8c040;font-weight:800}
@media print{.ti-help-ribbon{display:none !important}}
@media(max-width:560px){.ti-help-ribbon .thr-mod{display:none}}
</style>
<a class="ti-help-ribbon" href="/portal/help/quotation.html" target="_blank" title="Open the illustrated instructions for this module"><span class="thr-ic">📖</span><span class="thr-gold">Instructions</span><span class="thr-mod">· Quotation Engine</span></a>
<!-- ══ END INSTRUCTIONS TAB ══ -->

<div id="loadBar"></div>

<div class="header">
  <div class="logos">
    <a href="https://www.tieducational.com" target="_blank"><img src="/TIlogo.png" alt="TI" style="height:48px;width:auto;mix-blend-mode:lighten;"></a>
    <div class="ldiv"></div>
    <a href="https://www.kitmeer.com" target="_blank"><img src="/KitmeerLogo.png" alt="Kitmeer" style="height:34px;width:auto;mix-blend-mode:lighten;"></a>
  </div>
  <div class="hdr-r">
    <div class="badge">Quotation Engine</div>
    <div id="qnd" class="qnd"></div>
    <div id="catCnt" style="font-size:.68rem;color:#7aaac8;"></div>
    <a class="back-btn" href="/portal/admin/">← Dashboard</a>
  </div>
</div>

<div class="app">
  <!-- LEFT: Search -->
  <div class="sp">
    <div class="ph">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
        <h2>Product Catalog</h2>
        <button class="btn bg bsm" onclick="openManualAdd()" title="Manually add a custom item to the catalog" style="font-size:.62rem;padding:3px 9px">+ Add Item</button>
      </div>
      <div class="sr"><input type="search" id="si" placeholder="Search model, title, topic…"></div>
      <div class="sr">
        <select id="mf" style="flex:1"><option value="">All Manufacturers</option></select>
        <select id="cf" style="flex:1"><option value="">All Classes</option></select>
      </div>
      <div class="rc" id="rc">&nbsp;</div>
    </div>
    <div class="ps" id="rl"><div class="eq">Loading catalog…</div></div>
  </div>

  <!-- RIGHT: Quote -->
  <div class="qp">
    <div class="qph">
      <h2>Quote Builder</h2>

      <!-- Row 1: Client + Country (auto from client DB) -->
      <div class="mr">
        <label>Client:</label>
        <select id="cs" style="width:200px"><option value="">— Select Client —</option></select>
        <button class="alb" onclick="openAddClientModal()" title="Add a new client" style="white-space:nowrap;padding:5px 10px">+ New Client</button>
        <button class="alb" id="agentsBtn" onclick="openAgentsModal()" title="Manage local agents for this client" style="display:none;white-space:nowrap;padding:5px 10px;border-color:#0db8a8;color:#0db8a8">👤 Agents</button>
        <label style="margin-left:6px">Country:</label>
        <input type="text" id="country" placeholder="Auto from client…" style="width:150px">
      </div>

      <!-- Row 2: Quote selector (new or load) + search + quote number + date + currency -->
      <div class="mr">
        <label>Quote:</label>
        <input type="search" id="savedQuoteSearch" class="qsearch" placeholder="Search quotes…" style="width:140px">
        <select id="quoteSelect" style="width:250px;max-height:160px" size="1">
          <option value="new">— New Quote —</option>
        </select>
        <input type="text" id="qn" placeholder="Quote #" style="width:130px" title="Quote number — edit if needed">
        <input type="date" id="qdate" style="width:120px">
        <select id="cur" style="width:85px">
          <option value="USD">USD $</option><option value="SAR">SAR ﷼</option>
          <option value="AED">AED د.إ</option><option value="EUR">EUR €</option><option value="GBP">GBP £</option>
        </select>
      </div>

      <!-- Row 3: Incoterm + Location -->
      <div class="ir">
        <label>Incoterm:</label>
        <select id="inco" style="width:190px" onchange="onIncotermChange()">
          <option value="">— Select Incoterm —</option>
          <option>EXW (Ex Works)</option><option>FCA (Free Carrier)</option>
          <option>FAS (Free Alongside Ship)</option><option>FOB (Free On Board)</option>
          <option>CFR (Cost and Freight)</option><option>CIF (Cost, Insurance and Freight)</option>
          <option>CPT (Carriage Paid To)</option><option>CIP (Carriage and Insurance Paid To)</option>
          <option>DAP (Delivered at Place)</option><option>DPU (Delivered at Place Unloaded)</option>
          <option>DDP (Delivered Duty Paid)</option>
        </select>
        <label>Location:</label>
        <div class="ilw">
          <select id="incoLocation">
            <option value="">— Select Location —</option>
            <option>JEFFERSONVILLE, IN, USA</option>
            <option>SWEDESBORO, NJ, USA</option>
            <option>LA PORTE, TX, USA</option>
          </select>
          <button class="alb" onclick="openLocModal()">+ Add</button>
        </div>
      </div>

      <!-- Row 3b: Payment Terms -->
      <div style="margin-bottom:8px;padding:10px 12px;background:#f4f8fc;border:1px solid var(--bdr);border-radius:8px">
        <div style="font-size:.65rem;text-transform:uppercase;letter-spacing:1px;color:var(--sub);font-weight:700;margin-bottom:8px">Payment Terms</div>
        <div class="ir" style="margin-bottom:8px;align-items:flex-start">
          <label style="margin-top:8px">Wire / Method:</label>
          <select id="payWire" style="flex:1;min-width:220px" onchange="onPaymentTermsChange()">
            <option value="30_70">Wire Transfer — 30% at order / 70% 2 weeks prior to shipment</option>
            <option value="50_50">Wire Transfer — 50% at order / 50% 2 weeks prior to shipment</option>
            <option value="100">Wire Transfer — 100% at time order is placed</option>
            <option value="none">Letter of Credit only</option>
          </select>
        </div>
        <label id="payLcWrap" style="display:flex;align-items:flex-start;gap:8px;font-size:.72rem;color:var(--tx);margin-bottom:8px;cursor:pointer">
          <input type="checkbox" id="payIncludeLc" onchange="onPaymentTermsChange()" style="margin-top:2px;accent-color:var(--teal)">
          <span>Also include Letter of Credit as a second payment option</span>
        </label>
        <div id="payLcBox" style="display:none">
          <label style="display:block;font-size:.62rem;text-transform:uppercase;letter-spacing:1px;color:var(--sub);font-weight:700;margin-bottom:4px">L/C Terms and Conditions</label>
          <textarea id="payLcTerms" rows="8" style="width:100%;font-size:.72rem;padding:8px 10px;border:1.5px solid var(--bdr);border-radius:6px;background:var(--wh);color:var(--tx);font-family:inherit;line-height:1.45;resize:vertical"></textarea>
          <button type="button" class="alb" onclick="resetLcTerms()" style="margin-top:6px">Reset to default L/C text</button>
        </div>
      </div>

      <!-- Row 4: Divisors + Disc% -->
      <div class="dr">
        <label>Divisors:</label>
        <div id="divList" style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;flex:1">
          <div class="div-chip" data-dk="D1">
            <span class="div-badge" style="background:#0db8a8">D1</span>
            <input class="div-name" type="text" data-dk="D1" value="Standard" placeholder="Name" style="width:72px">
            <input class="div-val" id="div" type="number" data-dk="D1" value="0.65" min="0.001" max="99" step="0.01" style="width:56px;text-align:center">
            <span class="dp" id="dp" style="font-size:.62rem;color:var(--sub);white-space:nowrap">$1,538</span>
          </div>
        </div>
        <button class="alb" onclick="addDivisor()" title="Add another divisor" style="padding:3px 8px;font-size:.63rem;white-space:nowrap">+ Divisor</button>
        <label style="margin-left:8px">Disc %:</label>
        <input type="number" id="disc" value="0" min="0" max="100" step="0.5" style="width:58px;text-align:center;">
      </div>

      <!-- Row 4b: Shipping Estimates -->
      <div id="shipEstimatesBlock" style="margin-bottom:5px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
          <span style="font-size:.65rem;text-transform:uppercase;letter-spacing:1px;color:var(--sub);font-weight:700">Shipping Estimates</span>
          <button class="alb" onclick="addShipRow()">+ Add</button>
        </div>
        <div id="shipRows"></div>
        <div class="ship-total"><span>Est. Shipping Total:</span><span id="shipTotal">$0.00</span></div>
      </div>
      <div id="exwShipNote" style="display:none;margin-bottom:8px;padding:8px 10px;background:#eef8f6;border:1px solid rgba(13,184,168,.35);border-radius:6px;font-size:.72rem;color:var(--tx);line-height:1.45">
        <strong>EXW (Ex Works) selected.</strong> Shipment terms will be stated as Ex Works. Estimated ocean freight will <em>not</em> be listed on the quote (including TBD).
      </div>

      <!-- Installation & Commissioning -->
      <div style="margin-bottom:5px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
          <span style="font-size:.65rem;text-transform:uppercase;letter-spacing:1px;color:var(--sub);font-weight:700">Installation &amp; Commissioning</span>
        </div>
        <div style="display:flex;gap:6px;align-items:center">
          <input type="text" id="installLabel" value="INSTALLATION AND COMMISSIONING" placeholder="Label" style="flex:2;font-size:.72rem;padding:5px 8px;border:1.5px solid var(--bdr);border-radius:5px;background:var(--wh);color:var(--tx)">
          <input type="number" id="installAmt" value="" min="0" step="500" placeholder="Amount (leave blank if N/A)" style="flex:1;font-size:.72rem;padding:5px 8px;border:1.5px solid var(--bdr);border-radius:5px;background:var(--wh);color:var(--tx)">
        </div>
        <p style="font-size:.62rem;color:var(--sub);margin:3px 0 0">Optional. Only included in totals table if an amount is entered.</p>
      </div>

      <!-- Row 5: Action buttons -->
      <div class="qa">
        <button class="btn bb2 bsm" id="addSecBtn">+ Section</button>
        <button class="btn bsm" onclick="openBundlePicker()" style="background:linear-gradient(135deg,#c9a227,#e8c040);color:#fff" title="Insert a pre-configured system (main item + all sub-items)">⚡ Add System</button>
        <button class="btn bg bsm" id="saveQuoteBtn" onclick="saveQuote()">💾 Save Quote</button>
        <button class="btn bo bsm" id="saveRevBtn" style="display:none" onclick="openRevModal()">📋 Save as R1</button>
        <button class="btn bg bsm" onclick="exportXLSX()">⬇ Excel</button>
        <span style="display:inline-flex;border-radius:6px;overflow:hidden;box-shadow:0 1px 4px rgba(13,33,55,.12)">
          <button class="btn bg bsm" onclick="exportProposalPDF('combined')" title="Full proposal: cover, TOC, financial table, datasheets" style="background:linear-gradient(135deg,#0db8a8,#1a6fa0);border-radius:6px 0 0 6px;border-right:1px solid rgba(255,255,255,.2)">📄 Full PDF</button>
          <button class="btn bg bsm" onclick="exportProposalPDF('commercial')" title="Commercial package: cover, TOC, financial table only (no datasheets)" style="background:linear-gradient(135deg,#1a6fa0,#0d2137);border-radius:0;border-right:1px solid rgba(255,255,255,.2)">💰 Commercial</button>
          <button class="btn bg bsm" onclick="exportProposalPDF('literature')" title="Literature package: cover, TOC, datasheets only (no financial table)" style="background:linear-gradient(135deg,#7c4ad4,#4a2a9a);border-radius:0 6px 6px 0">📚 Literature</button>
        </span>
        <button class="btn bo bsm" onclick="exportCSV()">⬇ CSV</button>
        <button class="btn bo bsm" onclick="window.print()">🖨 Print</button>
        <button class="btn bo bsm" onclick="openImportModal()" title="Import old quotes from JSON">📂 Import</button>
        <button class="btn bo bsm" onclick="openVendorImportModal()" title="Import vendor quote (Amatrol Excel, Lucas-Nülle PDF, Aramco Spec DOCX)" style="border-color:var(--teal);color:var(--teal)">📥 Vendor Import</button>
        <button class="btn bo bsm" onclick="openItemNumberModal()" title="Customize item numbers for this proposal" style="border-color:#a07a00;color:#a07a00">🔢 Item #s</button>
        <button class="btn br bsm" onclick="clearQuote()">✕ Clear</button>
      </div>
    </div>
    <div style="padding:4px 8px;background:#f4f8fc;border-bottom:1px solid var(--bdr);display:flex;align-items:center;gap:6px">
      <input type="search" id="quoteSearch" placeholder="🔍 Search quote items…" oninput="filterQuote(this.value)"
        style="flex:1;padding:5px 8px;background:var(--wh);border:1.5px solid var(--bdr);border-radius:5px;font-size:.72rem;color:var(--tx)" autocomplete="off">
      <span id="quoteSearchCount" style="font-size:.62rem;color:var(--sub);white-space:nowrap"></span>
    </div>
    <div class="qb" id="qbody"><div class="eq">📋<div>Click "+ Section" to start building your quote</div></div></div>
    <div class="qf">
      <div class="tg">
        <div>
          <div class="tl"><span>Total Net Cost:</span><span id="tNet">$0.00</span></div>
          <div class="tl"><span>Discount:</span><span id="tDisc" style="color:#c04040;">-$0.00</span></div>
          <div class="tl"><span>Line Items:</span><span id="tItems">0</span></div>
        </div>
        <div>
          <div class="tl"><span>Est. Margin:</span><span class="grn" id="tMargin">$0.00</span></div>
          <div class="tl"><span>Margin %:</span><span class="grn" id="tMarginPct">0%</span></div>
          <div class="tl big"><span>QUOTE TOTAL:</span><span id="tQuote">$0.00</span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Required Items Modal -->
<div class="overlay" id="depModal" onclick="if(event.target===this)closeDepModal()">
  <div class="mbox" style="max-width:500px">
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px">
      <div>
        <h3 style="font-size:.88rem;color:var(--bd)">⚠ Required Accessories</h3>
        <div style="font-size:.7rem;color:var(--sub);margin-top:3px"><b id="depModalParent" style="color:var(--bm)"></b> requires these items to operate</div>
      </div>
      <button class="btn bo bsm" onclick="closeDepModal()">✕</button>
    </div>
    <div id="depModalItems" style="max-height:260px;overflow-y:auto;margin-bottom:12px"></div>
    <div style="display:flex;gap:7px;flex-wrap:wrap;align-items:center">
      <button class="btn bg bsm" onclick="addSelectedDeps()">✓ Add Selected as Sub-items</button>
      <button class="btn bo bsm" onclick="depSelectAll(true)">Select All</button>
      <button class="btn bo bsm" onclick="depSelectAll(false)">None</button>
      <button class="btn br bsm" onclick="closeDepModal()">Skip</button>
    </div>
  </div>
</div>

<!-- ── MAKE PARENT OF MODAL ── -->
<div class="overlay" id="mkpModal" onclick="if(event.target===this)closeMkpModal()">
  <div class="mbox" style="max-width:500px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <div>
        <h3 style="font-size:.88rem;color:var(--bd)">&#8615; Make Parent Of</h3>
        <div style="font-size:.7rem;color:var(--sub);margin-top:2px">Select items that should become sub-items of <b id="mkpParentLabel" style="color:#7c4ad4"></b></div>
      </div>
      <button class="btn bo bsm" onclick="closeMkpModal()">&#215;</button>
    </div>
    <div id="mkpItemList" style="max-height:300px;overflow-y:auto;margin-bottom:12px"></div>
    <div style="font-size:.65rem;color:var(--sub);margin-bottom:10px">Checked items will be moved under this parent with <em>Included in parent</em> pricing. You can change pricing after.</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <button class="btn bg bsm" onclick="confirmMakeParentOf()" style="background:linear-gradient(135deg,#7c4ad4,#5a3aaa)">&#10003; Assign as Children</button>
      <button class="btn bo bsm" onclick="mkpSelectAll(true)">All</button>
      <button class="btn bo bsm" onclick="mkpSelectAll(false)">None</button>
      <button class="btn br bsm" onclick="closeMkpModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- ── ADD REQUIRED ITEM MODAL ── -->
<div class="req-modal" id="reqModal">
  <div class="req-mbox">
    <div class="req-mhead">
      <h3>&#43; Add Required Item to <span id="reqModalParentLabel" style="color:var(--teal)"></span></h3>
      <button class="btn bo bsm" onclick="closeReqModal()" style="padding:3px 9px">&#215;</button>
    </div>
    <div class="req-mbody">
      <input class="req-search" id="reqSearchInput" placeholder="Search model number or title…" oninput="reqSearch(this.value)" autocomplete="off" onkeydown="if(event.key==='Escape')closeReqModal()">
      <div id="reqResults"><p class="req-none">Type to search the catalog…</p></div>
    </div>
    <div class="req-mfoot">
      <span style="font-size:.65rem;color:var(--sub);margin-right:auto;align-self:center">Click any result to add it as a sub-item of the parent</span>
      <button class="btn bo bsm" onclick="closeReqModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Recommended Items Modal -->
<div class="overlay" id="recModal" onclick="if(event.target===this)closeRecModal()">
  <div class="mbox" style="max-width:500px">
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px">
      <div>
        <h3 style="font-size:.88rem;color:#a07a10">💡 Recommended Items</h3>
        <div style="font-size:.7rem;color:var(--sub);margin-top:3px">
          These items are <b style="color:#c9a227">strongly recommended</b> for use with
          <b id="recModalParent" style="color:var(--bm)"></b>
        </div>
      </div>
      <button class="btn bo bsm" onclick="closeRecModal()">✕</button>
    </div>
    <div id="recModalItems" style="max-height:260px;overflow-y:auto;margin-bottom:12px"></div>
    <div style="display:flex;gap:7px;flex-wrap:wrap;align-items:center">
      <button class="btn bg bsm" onclick="addSelectedRecs()" style="background:linear-gradient(135deg,#c9a227,#a07a10)">✓ Add Selected</button>
      <button class="btn bo bsm" onclick="recSelectAll(true)">Select All</button>
      <button class="btn bo bsm" onclick="recSelectAll(false)">None</button>
      <button class="btn br bsm" onclick="closeRecModal()">Skip</button>
    </div>
  </div>
</div>

<!-- Proposal PDF Options Modal -->
<div class="overlay" id="proposalModal" onclick="if(event.target===this)document.getElementById('proposalModal').classList.remove('open')">
  <div class="mbox" style="max-width:480px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h3 id="proposalModalTitle">📄 Export Proposal PDF</h3>
      <button class="btn bo bsm" onclick="document.getElementById('proposalModal').classList.remove('open')">✕</button>
    </div>
    <p style="font-size:.72rem;color:var(--sub);margin-bottom:14px">
      Generates a complete branded proposal with cover page, table of contents, section dividers, and all product datasheets in quote order.
    </p>
    <!-- Logo selection -->
    <div style="margin-bottom:14px">
      <label style="font-size:.72rem;font-weight:700;color:var(--bd);display:block;margin-bottom:6px">Client / Project Logo (optional)</label>
      <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
        <select id="proposalLogoSelect" onchange="if(this.value){showLogoPreview(this.value,this.options[this.selectedIndex].text);localStorage.setItem('ti_last_logo_path',this.value);localStorage.setItem('ti_last_logo_name',this.options[this.selectedIndex].text);}else{document.getElementById('logoPreview').style.display='none';}" style="flex:1;font-size:.73rem;padding:6px 9px;background:var(--wh);border:1.5px solid var(--bdr);border-radius:5px;color:var(--tx)">
          <option value="">— No client logo —</option>
        </select>
        <button class="btn bo bsm" onclick="document.getElementById('logoUploadInput').click()">⬆ Upload</button>
        <input type="file" id="logoUploadInput" accept="image/png,image/jpeg,image/gif,image/webp" style="display:none" onchange="uploadProposalLogo(this)">
      </div>
      <div id="logoPreview" style="display:none;text-align:center;margin-top:8px">
        <img id="logoPreviewImg" style="max-height:60px;max-width:200px;border:1px solid var(--bdr);border-radius:4px;padding:4px">
        <div style="font-size:.65rem;color:var(--sub);margin-top:3px" id="logoPreviewName"></div>
      </div>
      <p style="font-size:.65rem;color:var(--sub);margin:0">
        Upload the client or project logo to display on the cover page. PNG recommended. Stays saved for next time.
      </p>
    </div>
    <!-- RFQ Comparison Document Upload (optional) -->
    <div style="margin-bottom:14px;border-top:1px solid var(--bdr);padding-top:14px">
      <label style="font-size:.72rem;font-weight:700;color:var(--bd);display:block;margin-bottom:4px">
        Competitive Comparison to RFQ <span style="font-weight:400;color:var(--sub)">(optional)</span>
      </label>
      <p style="font-size:.65rem;color:var(--sub);margin:0 0 8px 0">
        Upload a PDF to append as a final section — it will appear in the TOC and at the end of the proposal.
      </p>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="btn bo bsm" onclick="document.getElementById('rfqDocInput').click()">⬆ Upload RFQ PDF</button>
        <input type="file" id="rfqDocInput" accept=".pdf" style="display:none" onchange="uploadRfqDoc(this)">
        <span id="rfqDocStatus" style="font-size:.68rem;color:var(--sub)">No document uploaded</span>
        <button id="rfqDocClear" onclick="clearRfqDoc()" style="display:none;background:none;border:none;color:#c04040;cursor:pointer;font-size:.7rem;padding:0">✕ Remove</button>
      </div>
    </div>
    <!-- Required Tender Documents Upload (optional, persistent per quote number) -->
    <div style="margin-bottom:14px;border-top:1px solid var(--bdr);padding-top:14px">
      <label style="font-size:.72rem;font-weight:700;color:var(--bd);display:block;margin-bottom:4px">
        Required Tender Documents <span style="font-weight:400;color:var(--sub)">(optional)</span>
      </label>
      <p style="font-size:.65rem;color:var(--sub);margin:0 0 8px 0">
        Upload authorization letters and other required tender documents (PDF). They are appended as a final section, appear in the TOC, and stay saved with this quotation number for every future export.
      </p>
      <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
        <button class="btn bo bsm" onclick="document.getElementById('tenderDocInput').click()">⬆ Upload Tender PDF(s)</button>
        <input type="file" id="tenderDocInput" accept=".pdf" multiple style="display:none" onchange="uploadTenderDocs(this)">
        <span id="tenderDocStatus" style="font-size:.68rem;color:var(--sub)">No documents uploaded</span>
      </div>
      <div id="tenderDocList" style="display:none;flex-direction:column;gap:4px"></div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn bg" id="proposalExportBtn" onclick="doExportProposalPDF()" style="flex:1">📄 Generate Proposal PDF</button>
      <button class="btn bo bsm" onclick="document.getElementById('proposalModal').classList.remove('open')">Cancel</button>
    </div>
  </div>
</div>

<!-- Import Projects Modal -->
<div class="overlay" id="importModal" onclick="if(event.target===this)closeImportModal()">
  <div class="mbox" style="max-width:600px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <h3>📂 Import Old Quotes</h3>
      <button class="btn bo bsm" onclick="closeImportModal()">✕</button>
    </div>
    <p style="font-size:.72rem;color:var(--sub);margin-bottom:12px">
      Upload <b>one or more</b> files. Accepts:
      <b style="color:var(--bm)">.xlsx</b> (individual quote exports) or
      <b style="color:var(--bm)">.json</b> (projects.json batch file).
      <br>Files are read in your browser — nothing is uploaded to a server until you click Import.
    </p>
    <div style="margin-bottom:10px;padding:10px;background:#f4f8fc;border:1.5px dashed var(--bdr);border-radius:6px;text-align:center">
      <input type="file" id="importFile" accept=".json,.xlsx,.xls" multiple style="font-size:.75rem">
      <div style="font-size:.65rem;color:var(--sub);margin-top:5px">Tip: hold Ctrl/Cmd to select multiple Excel files at once</div>
    </div>
    <div id="importPreview" style="font-size:.71rem;color:var(--sub);margin-bottom:10px;max-height:220px;overflow-y:auto;border:1px solid var(--bdr);border-radius:5px;padding:8px;display:none"></div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <button class="btn bg bsm" id="importRunBtn" onclick="runImport()" style="display:none">⬆ Import</button>
      <span id="importStatus" style="font-size:.7rem;color:var(--sub)"></span>
      <button class="btn bo bsm" onclick="closeImportModal()" style="margin-left:auto">Cancel</button>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════
     VENDOR IMPORT MODAL
     Supports: Amatrol Excel, Lucas-Nülle PDF, Aramco Spec DOCX
     Steps: 1=Select vendor/file  2=Review items + DB comparison  3=Choose import mode
     ══════════════════════════════════════════════════════════════════ -->
<div class="overlay" id="vendorImportModal" onclick="if(event.target===this)closeVendorImportModal()">
  <div class="mbox" style="max-width:820px;width:97vw;max-height:92vh;display:flex;flex-direction:column;padding:0;overflow:hidden;border-radius:12px;border-top:3px solid var(--teal);">

    <!-- Header -->
    <div style="padding:14px 20px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
      <div>
        <h3 style="font-size:.92rem;color:var(--bd);margin-bottom:2px">📥 Vendor Quote Import</h3>
        <div id="viStepIndicator" style="font-size:.63rem;color:var(--sub);letter-spacing:.5px">STEP 1 OF 3 — SELECT VENDOR &amp; FILE</div>
      </div>
      <button class="btn bo bsm" onclick="closeVendorImportModal()">✕ Close</button>
    </div>

    <!-- Step 1: Vendor selection + file upload -->
    <div id="viStep1" style="padding:18px 20px;overflow-y:auto;flex:1">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:16px">
        <!-- Amatrol card -->
        <div class="vi-vendor-card" id="viCard_amatrol" onclick="selectVendorCard('amatrol')" style="border:2px solid var(--bdr);border-radius:8px;padding:12px;cursor:pointer;transition:all .15s;text-align:center">
          <div style="font-size:1.4rem;margin-bottom:5px">🏭</div>
          <div style="font-size:.78rem;font-weight:700;color:var(--bd)">Amatrol, Inc.</div>
          <div style="font-size:.62rem;color:var(--sub);margin-top:3px">.xlsx / .xls</div>
          <div style="font-size:.6rem;color:var(--sub);margin-top:2px;font-style:italic">International distributor quote</div>
        </div>
        <!-- Lucas-Nülle card -->
        <div class="vi-vendor-card" id="viCard_lucas_nuelle" onclick="selectVendorCard('lucas_nuelle')" style="border:2px solid var(--bdr);border-radius:8px;padding:12px;cursor:pointer;transition:all .15s;text-align:center">
          <div style="font-size:1.4rem;margin-bottom:5px">🔬</div>
          <div style="font-size:.78rem;font-weight:700;color:var(--bd)">Lucas-Nülle</div>
          <div style="font-size:.62rem;color:var(--sub);margin-top:3px">.pdf / .xlsx / .csv</div>
          <div style="font-size:.6rem;color:var(--sub);margin-top:2px;font-style:italic">Order No. / Pos. table format</div>
        </div>
        <!-- Aramco Spec card -->
        <div class="vi-vendor-card" id="viCard_aramco_spec" onclick="selectVendorCard('aramco_spec')" style="border:2px solid var(--bdr);border-radius:8px;padding:12px;cursor:pointer;transition:all .15s;text-align:center">
          <div style="font-size:1.4rem;margin-bottom:5px">📋</div>
          <div style="font-size:.78rem;font-weight:700;color:var(--bd)">Aramco Spec</div>
          <div style="font-size:.62rem;color:var(--sub);margin-top:3px">.docx / .txt</div>
          <div style="font-size:.6rem;color:var(--sub);margin-top:2px;font-style:italic">ITEM (N) spec document</div>
        </div>
      </div>

      <!-- File drop zone -->
      <div id="viDropZone"
        onclick="document.getElementById('viFileInput').click()"
        ondragover="event.preventDefault();this.style.borderColor='var(--teal)'"
        ondragleave="this.style.borderColor='var(--bdr)'"
        ondrop="viHandleDrop(event)"
        style="border:2px dashed var(--bdr);border-radius:8px;padding:24px;text-align:center;cursor:pointer;transition:border-color .15s;background:#f9fbfd;margin-bottom:12px">
        <div style="font-size:2rem;margin-bottom:6px">📄</div>
        <div style="font-size:.78rem;color:var(--sub)">Click or drag &amp; drop vendor quote file here</div>
        <div id="viFileLabel" style="font-size:.7rem;color:var(--teal);margin-top:6px;font-weight:700"></div>
      </div>
      <input type="file" id="viFileInput" style="display:none" onchange="viFileChosen(this)">

      <!-- Optional metadata overrides -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:4px">
        <div>
          <label style="font-size:.62rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px">End Client (optional override)</label>
          <input type="text" id="viClientName" placeholder="e.g. Saudi Aramco" style="width:100%;padding:6px 8px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.75rem">
        </div>
        <div>
          <label style="font-size:.62rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px">Country</label>
          <input type="text" id="viCountry" placeholder="e.g. Saudi Arabia" style="width:100%;padding:6px 8px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.75rem">
        </div>
      </div>
    </div>

    <!-- Step 2: Item comparison table (hidden until parse completes) -->
    <div id="viStep2" style="display:none;flex:1;overflow-y:auto;padding:0">
      <!-- Summary bar -->
      <div id="viSummaryBar" style="padding:10px 20px;background:#f4f8fc;border-bottom:1px solid var(--bdr);display:flex;gap:16px;align-items:center;flex-wrap:wrap;flex-shrink:0">
        <span id="viSum_new"      style="font-size:.72rem;font-weight:700;color:#1a9a6a">● 0 New</span>
        <span id="viSum_match"    style="font-size:.72rem;font-weight:700;color:#1a6fa0">● 0 Match</span>
        <span id="viSum_conflict" style="font-size:.72rem;font-weight:700;color:#c06000">● 0 Conflict</span>
        <span id="viSum_nomodel"  style="font-size:.72rem;color:var(--sub)">0 No model#</span>
        <span id="viSum_total"    style="font-size:.72rem;color:var(--sub);margin-left:auto">0 items total</span>
        <input type="search" id="viSearchItems" placeholder="Filter items…" oninput="viFilterItems(this.value)"
          style="padding:4px 8px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.72rem;width:140px">
      </div>

      <!-- Item table -->
      <div style="padding:12px 20px;overflow-y:auto;max-height:340px">
        <table style="width:100%;border-collapse:collapse;font-size:.72rem" id="viItemTable">
          <thead>
            <tr style="background:#f4f8fc;border-bottom:2px solid var(--bdr)">
              <th style="padding:5px 8px;text-align:left;color:var(--sub);font-size:.62rem;letter-spacing:.5px;white-space:nowrap">STATUS</th>
              <th style="padding:5px 8px;text-align:left;color:var(--sub);font-size:.62rem;letter-spacing:.5px">MODEL #</th>
              <th style="padding:5px 8px;text-align:left;color:var(--sub);font-size:.62rem;letter-spacing:.5px">TITLE (IMPORT)</th>
              <th style="padding:5px 8px;text-align:left;color:var(--sub);font-size:.62rem;letter-spacing:.5px">DB TITLE</th>
              <th style="padding:5px 8px;text-align:right;color:var(--sub);font-size:.62rem;letter-spacing:.5px">QTY</th>
              <th style="padding:5px 8px;text-align:right;color:var(--sub);font-size:.62rem;letter-spacing:.5px">VENDOR PRICE</th>
              <th style="padding:5px 8px;text-align:left;color:var(--sub);font-size:.62rem;letter-spacing:.5px;width:90px">ACTION</th>
              <th style="padding:5px 8px;text-align:left;color:var(--sub);font-size:.62rem;letter-spacing:.5px;width:72px">ITEM #</th>
            </tr>
          </thead>
          <tbody id="viItemBody"></tbody>
        </table>
      </div>
    </div>

    <!-- Step 3: Import mode selection (hidden until step 2 complete) -->
    <div id="viStep3" style="display:none;padding:16px 20px;border-top:1px solid var(--bdr);flex-shrink:0;background:#f9fbfd">
      <div style="font-size:.75rem;font-weight:700;color:var(--bd);margin-bottom:10px">Choose Import Mode:</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
        <label class="vi-mode-opt" id="viMode_catalog" style="border:2px solid var(--bdr);border-radius:7px;padding:10px;cursor:pointer;transition:all .12s">
          <input type="radio" name="viMode" value="catalog" style="display:none" onchange="viModeChanged(this)">
          <div style="font-size:.95rem;margin-bottom:4px">📚</div>
          <div style="font-size:.73rem;font-weight:700;color:var(--bd)">A — Update Catalog</div>
          <div style="font-size:.62rem;color:var(--sub);margin-top:3px">Add new items to the products database. Conflicts kept as-is.</div>
        </label>
        <label class="vi-mode-opt" id="viMode_quote" style="border:2px solid var(--bdr);border-radius:7px;padding:10px;cursor:pointer;transition:all .12s">
          <input type="radio" name="viMode" value="quote" style="display:none" onchange="viModeChanged(this)">
          <div style="font-size:.95rem;margin-bottom:4px">📝</div>
          <div style="font-size:.73rem;font-weight:700;color:var(--bd)">B — Create Quote</div>
          <div style="font-size:.62rem;color:var(--sub);margin-top:3px">Pre-load a new quote with these items, ready to edit &amp; price.</div>
        </label>
        <label class="vi-mode-opt" id="viMode_both" style="border:2px solid var(--bdr);border-radius:7px;padding:10px;cursor:pointer;transition:all .12s">
          <input type="radio" name="viMode" value="both" style="display:none" onchange="viModeChanged(this)">
          <div style="font-size:.95rem;margin-bottom:4px">⚡</div>
          <div style="font-size:.73rem;font-weight:700;color:var(--bd)">A + B — Both</div>
          <div style="font-size:.62rem;color:var(--sub);margin-top:3px">Update catalog AND create a new quote in one step.</div>
        </label>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="btn bg" id="viRunBtn" onclick="viRunImport()" disabled style="padding:7px 20px;font-size:.75rem">
          ▶ Run Import
        </button>
        <button class="btn bo bsm" onclick="viBackToStep1()">← Back</button>
        <span id="viRunStatus" style="font-size:.72rem;color:var(--sub);margin-left:8px"></span>
      </div>
    </div>

    <!-- Footer: parse button (step 1) -->
    <div id="viParseBar" style="padding:10px 20px;border-top:1px solid var(--bdr);display:flex;gap:8px;align-items:center;flex-shrink:0">
      <button class="btn bg bsm" id="viParseBtn" onclick="viParseDoc()" disabled>🔍 Parse Document</button>
      <span id="viParseStatus" style="font-size:.7rem;color:var(--sub)">Select a vendor and upload a file to begin.</span>
    </div>
  </div>
</div>

<style>
.vi-vendor-card:hover { border-color: var(--teal)!important; background: #f0fbfa; }
.vi-vendor-card.selected { border-color: var(--teal)!important; background: rgba(13,184,168,.07); }
.vi-mode-opt:hover { border-color: var(--teal)!important; }
.vi-mode-opt.selected { border-color: var(--teal)!important; background: rgba(13,184,168,.06); }
.vi-status-new      { color:#1a9a6a; font-weight:700; font-size:.65rem; }
.vi-status-match    { color:#1a6fa0; font-weight:700; font-size:.65rem; }
.vi-status-conflict { color:#c06000; font-weight:700; font-size:.65rem; }
.vi-status-no_model { color:#888;    font-weight:400; font-size:.65rem; }
#viItemTable tbody tr:hover td { background: #f4fbfa; }
#viItemTable td { padding:4px 8px; border-bottom:1px solid #eef4f9; vertical-align:top; }
.vi-action-sel { font-size:.65rem; padding:2px 4px; border:1px solid var(--bdr); border-radius:3px; background:#f4f8fc; }
.vi-custom-num { width:60px; font-size:.68rem; padding:2px 4px; border:1px solid var(--bdr); border-radius:3px; text-align:center; }
.vi-conflict-badge { font-size:.58rem; background:rgba(192,96,0,.12); color:#c06000; border:1px solid rgba(192,96,0,.25); border-radius:3px; padding:1px 5px; cursor:help; }
</style>

<!-- Section chooser -->

<div class="overlay" id="secModal" onclick="if(event.target===this)closeSecModal()">
  <div class="mbox">
    <h3>Add to which section?</h3>
    <div class="sl" id="secList"></div>
    <div style="margin-top:10px"><button class="btn bo bsm" onclick="closeSecModal()">Cancel</button></div>
  </div>
</div>

<!-- Detail modal -->
<div class="overlay" id="detModal" onclick="if(event.target===this)closeModal()">
  <div class="mbox wide" id="detBox"></div>
</div>

<!-- Quick-Add Client Modal -->
<div class="overlay" id="addClientModal">
  <div class="mbox" style="max-width:440px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h3>👥 Add New Client</h3>
      <button class="btn bo bsm" onclick="closeAddClientModal()">✕</button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
      <div style="grid-column:1/-1">
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Company Name *</label>
        <input type="text" id="acName" placeholder="e.g. Saudi Aramco" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px" oninput="acNameCheck()">
      </div>

      <!-- Company Logo -->
      <div style="grid-column:1/-1">
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:1px">Company Logo</label>
        <div style="display:flex;gap:10px;align-items:flex-start">
          <!-- Drop zone -->
          <div id="acLogoDrop"
            onclick="document.getElementById('acLogoFile').click()"
            ondragover="event.preventDefault();this.style.borderColor='var(--bb)'"
            ondragleave="this.style.borderColor='var(--bdr)'"
            ondrop="acLogoDrop(event)"
            style="flex:1;border:2px dashed var(--bdr);border-radius:7px;padding:12px 10px;text-align:center;cursor:pointer;transition:border-color .15s;background:#f9fbfd">
            <div style="font-size:1.3rem">🖼</div>
            <div style="font-size:.68rem;color:var(--sub);margin-top:4px">Click or drag &amp; drop</div>
            <div style="font-size:.61rem;color:#aaa;margin-top:2px">PNG · JPG · SVG · WEBP</div>
          </div>
          <!-- Preview -->
          <div id="acLogoPreview" style="display:none;flex-direction:column;align-items:center;gap:6px;min-width:100px">
            <div style="width:100px;height:60px;border:1px solid var(--bdr);border-radius:6px;display:flex;align-items:center;justify-content:center;background:#fff;overflow:hidden;padding:4px">
              <img id="acLogoImg" style="max-width:92px;max-height:52px;object-fit:contain">
            </div>
            <div id="acLogoFileName" style="font-size:.6rem;color:var(--sub);text-align:center;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div>
            <button type="button" onclick="acLogoClear()" style="font-size:.6rem;color:#c04040;background:none;border:none;cursor:pointer">✕ Remove</button>
          </div>
        </div>
        <input type="file" id="acLogoFile" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml" style="display:none" onchange="acLogoFileChosen(this)">
        <div style="font-size:.61rem;color:var(--sub);margin-top:5px">Logo will be saved as the company name and used automatically on proposals and quotations for this client.</div>
      </div>

      <div style="grid-column:1/-1">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
          <label style="font-size:.64rem;color:var(--sub);text-transform:uppercase;letter-spacing:1px">Arabic Name</label>
          <button type="button" id="arLookupBtn" onclick="lookupArabicName()" class="alb"
            style="font-size:.62rem;padding:3px 9px;border-color:var(--teal);color:var(--teal)">
            🔍 Look Up Arabic Name
          </button>
        </div>
        <input type="text" id="acNameAr" placeholder="الاسم بالعربية — type or click Look Up"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px;direction:rtl;font-size:.85rem" dir="rtl">
        <!-- Result source tag -->
        <div id="arSourceTag" style="display:none;margin-top:5px;display:none;align-items:center;gap:6px;flex-wrap:wrap">
          <span id="arSourceBadge" style="font-size:.61rem;font-weight:700;padding:2px 8px;border-radius:10px"></span>
          <span id="arSourceNote"  style="font-size:.61rem;color:var(--sub)"></span>
          <a id="arSourceLink" href="#" target="_blank" rel="noopener"
            style="font-size:.61rem;color:var(--bm);display:none">↗ View source</a>
        </div>
        <!-- Results list when multiple matches found -->
        <div id="arResultsList" style="display:none;margin-top:6px;border:1px solid var(--bdr);border-radius:6px;overflow:hidden;background:#fff"></div>
      </div>
      <div>
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Country</label>
        <select id="acCountry" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
          <option value="">— Select —</option>
          <option>The Kingdom of Saudi Arabia (KSA)</option><option>UAE</option><option>Qatar</option>
          <option>Kuwait</option><option>Bahrain</option><option>Oman</option>
          <option>Egypt</option><option>Jordan</option><option>Iraq</option>
          <option>Libya</option><option>Algeria</option><option>Morocco</option>
          <option>United States</option><option>Other</option>
        </select>
      </div>
      <div>
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Sector</label>
        <select id="acSector" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
          <option value="">— Select —</option>
          <option>Oil &amp; Gas</option><option>Petrochemical</option><option>Power Generation</option>
          <option>Water/Wastewater</option><option>Education / TVET</option>
          <option>Defense / Military</option><option>Mining</option>
          <option>Manufacturing</option><option>Government</option><option>Other</option>
        </select>
      </div>
      <div>
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Website</label>
        <input type="text" id="acWebsite" placeholder="www.example.com" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>

      <!-- ── PHONE NUMBERS ── -->
      <div style="grid-column:1/-1;border-top:1px solid var(--bdr);padding-top:10px;margin-top:2px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <label style="font-size:.64rem;color:var(--sub);text-transform:uppercase;letter-spacing:1px;font-weight:700">Phone Numbers</label>
          <button type="button" onclick="acAddPhone()" class="alb" style="font-size:.62rem;padding:3px 8px">+ Add Number</button>
        </div>
        <div id="acPhoneList"></div>
      </div>

      <!-- ── PHYSICAL ADDRESS ── -->
      <div style="grid-column:1/-1;border-top:1px solid var(--bdr);padding-top:10px;margin-top:2px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <label style="font-size:.64rem;color:var(--sub);text-transform:uppercase;letter-spacing:1px;font-weight:700">Physical Address</label>
          <button type="button" onclick="acAddAddrLine()" class="alb" style="font-size:.62rem;padding:3px 8px">+ Add Line</button>
        </div>
        <!-- Google Places search -->
        <div id="acPlacesSearchWrap" style="position:relative;margin-bottom:8px">
          <div style="display:flex;gap:6px;align-items:center">
            <div style="position:relative;flex:1">
              <input type="text" id="acPlacesInput"
                placeholder="&#128205; Search address or company on Google Maps..."
                autocomplete="off"
                style="width:100%;padding:7px 9px 7px 30px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.75rem"
                oninput="acPlacesSearch(this.value)"
                onfocus="if(this.value.length>=3)acPlacesSearch(this.value)">
              <span style="position:absolute;left:9px;top:50%;transform:translateY(-50%);font-size:.75rem;pointer-events:none">&#128205;</span>
            </div>
            <div id="acPlacesSpinner" style="display:none;font-size:.8rem;color:var(--sub)">&#9203;</div>
          </div>
          <div id="acPlacesDrop" style="display:none;position:absolute;left:0;right:0;top:calc(100% + 2px);background:#fff;border:1px solid var(--bdr);border-radius:7px;box-shadow:0 4px 18px rgba(13,33,55,.12);z-index:999;overflow:hidden;max-height:260px;overflow-y:auto"></div>
          <div id="acPlacesNoKey" style="display:none;font-size:.65rem;color:#d07a00;background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.25);border-radius:5px;padding:5px 9px;margin-top:5px">
            &#9888; Google Places key not configured. Add it to <code>portal/quotation/api.php</code> line&nbsp;35. Manual entry works fine below.
          </div>
        </div>
        <div id="acAddrLines"></div>
        <div style="font-size:.61rem;color:var(--sub);margin-top:4px">Google search auto-fills lines. Each line prints on its own row. Edit freely or add more lines.</div>
      </div>

      <div style="grid-column:1/-1">
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Notes</label>
        <input type="text" id="acNotes" placeholder="Optional notes…" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <!-- Primary Contact -->
      <div style="grid-column:1/-1;border-top:1px solid var(--bdr);padding-top:8px;margin-top:2px">
        <div style="font-size:.64rem;font-weight:700;color:var(--bd);margin-bottom:6px;text-transform:uppercase;letter-spacing:1px">Primary Contact (optional)</div>
      </div>
      <div>
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:3px">Contact Name</label>
        <input type="text" id="acContactName" placeholder="Full name" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div>
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:3px">Title</label>
        <input type="text" id="acContactTitle" placeholder="e.g. Procurement Manager" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div>
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:3px">Email</label>
        <input type="email" id="acContactEmail" placeholder="email@company.com" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div>
        <label style="font-size:.64rem;color:var(--sub);display:block;margin-bottom:3px">Mobile</label>
        <input type="text" id="acContactMobile" placeholder="+966 5x xxx xxxx" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
    </div>
    <div id="acErr" style="font-size:.7rem;color:#c04040;background:#fff0f0;border:1px solid #f0c0c0;border-radius:5px;padding:7px 10px;display:none;margin-bottom:8px"></div>
    <div style="display:flex;gap:8px">
      <button class="btn bg" id="acSaveBtn" onclick="saveNewClient()" disabled style="flex:1">✓ Add Client</button>
      <button class="btn bo bsm" onclick="closeAddClientModal()">Cancel</button>
    </div>
  </div>
</div>



<!-- ═══ MANUAL ADD ITEM MODAL ═══ -->
<div class="overlay" id="manualAddModal">
  <div class="mbox" style="max-width:560px;max-height:92vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3 id="maTitle">➕ Add Custom Item to Catalog</h3>
      <button class="btn bo bsm" onclick="closeManualAdd()">✕</button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">

      <div style="grid-column:1/-1">
        <label class="ml">Model / Item Number *</label>
        <input type="text" id="maModelId" placeholder="e.g. CUSTOM-001 or leave blank to auto-generate"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div style="grid-column:1/-1">
        <label class="ml">Title / Short Description *</label>
        <input type="text" id="maTitle2" placeholder="Brief item name shown in quotes"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div style="grid-column:1/-1">
        <label class="ml">Full Description</label>
        <textarea id="maDesc" rows="5" placeholder="Paste description here — line breaks and formatting preserved…"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px;resize:vertical;font-family:inherit;font-size:.78rem;white-space:pre-wrap;line-height:1.6"></textarea>
      </div>

      <div>
        <label class="ml">Manufacturer</label>
        <input type="text" id="maMfr" placeholder="e.g. Amatrol"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div>
        <label class="ml">Product Class</label>
        <select id="maCls" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
          <option value="">— Select —</option>
          <option>Learning System</option><option>Equipment</option><option>Equipment Option</option>
          <option>Publication</option><option>Multimedia</option><option>Cutaway</option>
          <option>Activity Kit</option><option>Furniture</option><option>Working Demonstrator</option>
          <option>Project Kit</option><option>Custom</option><option>Other</option>
        </select>
      </div>
      <div>
        <label class="ml">Net Cost to TI (USD)</label>
        <input type="number" id="maNet" step="0.01" min="0" placeholder="0.00"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div>
        <label class="ml">List / Market Price (USD)</label>
        <input type="number" id="maList" step="0.01" min="0" placeholder="0.00"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div>
        <label class="ml">Lead Time</label>
        <input type="text" id="maLead" placeholder="e.g. 6-8 weeks"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div>
        <label class="ml">Dimensions</label>
        <input type="text" id="maDims" placeholder='e.g. 24"W x 36"H x 18"D'
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div style="grid-column:1/-1">
        <label class="ml">Notes / Reason for Manual Entry</label>
        <input type="text" id="maNotes" placeholder="e.g. Updated pricing 05/2026 per Amatrol email"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>

      <!-- PDF Upload -->
      <div style="grid-column:1/-1;border-top:1px solid var(--bdr);padding-top:10px;margin-top:4px">
        <label class="ml">Attach PDF / Brochure (optional)</label>
        <div style="display:flex;gap:8px;align-items:center;margin-top:4px">
          <input type="file" id="maPdfFile" accept=".pdf"
            style="flex:1;padding:5px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.72rem"
            onchange="maPreviewPdf(this)">
          <span id="maPdfName" style="font-size:.65rem;color:var(--teal)"></span>
        </div>
      </div>

      <div style="grid-column:1/-1;background:#eef4f9;border-radius:6px;padding:7px 10px;font-size:.65rem;color:var(--sub)">
        <strong>Added on:</strong> <span id="maDateStamp"></span> &nbsp;·&nbsp; Item will be saved to the catalog database and available in all future sessions.
      </div>

      <!-- Required Items -->
      <div style="grid-column:1/-1;border-top:1px solid var(--bdr);padding-top:11px;margin-top:2px">
        <label class="ml">🔗 Required Items <span style="font-weight:400;color:var(--sub);font-size:.63rem">— accessories this item cannot operate without (triggers automatic popup when added to quote)</span></label>
        <div style="display:flex;gap:6px;margin:5px 0">
          <input id="maReqSearch" class="linput" style="flex:1;padding:5px 9px;font-size:.72rem;margin-bottom:0" placeholder="Type model ID or search by name…" oninput="maSearchDeps('req',this.value)" autocomplete="off"
            onkeydown="if(event.key==='Enter'){event.preventDefault();maAddTagManual('req');}">
          <button class="btn bo bsm" onclick="maAddTagManual('req')" title="Add the typed model ID">+ Add</button>
        </div>
        <div id="maReqSuggestions" style="max-height:110px;overflow-y:auto;margin-bottom:5px"></div>
        <div id="maReqTags" style="display:flex;flex-wrap:wrap;gap:5px;min-height:20px"></div>
      </div>

      <!-- Recommended Items -->
      <div style="grid-column:1/-1;border-top:1px solid var(--bdr);padding-top:11px;margin-top:4px">
        <label class="ml">💡 Recommended Items <span style="font-weight:400;color:var(--sub);font-size:.63rem">— optional add-ons that work well with this item</span></label>
        <div style="display:flex;gap:6px;margin:5px 0">
          <input id="maRecSearch" class="linput" style="flex:1;padding:5px 9px;font-size:.72rem;margin-bottom:0" placeholder="Type model ID or search by name…" oninput="maSearchDeps('rec',this.value)" autocomplete="off"
            onkeydown="if(event.key==='Enter'){event.preventDefault();maAddTagManual('rec');}">
          <button class="btn bo bsm" onclick="maAddTagManual('rec')" title="Add the typed model ID">+ Add</button>
        </div>
        <div id="maRecSuggestions" style="max-height:110px;overflow-y:auto;margin-bottom:5px"></div>
        <div id="maRecTags" style="display:flex;flex-wrap:wrap;gap:5px;min-height:20px"></div>
      </div>

    </div>
    <div id="maErr" style="font-size:.7rem;color:#c04040;background:#fff0f0;border:1px solid #f0c0c0;border-radius:5px;padding:7px 10px;display:none;margin-bottom:8px"></div>
    <div style="display:flex;gap:8px">
      <button class="btn bg" id="maSaveBtn" onclick="saveManualItem()" style="flex:1">✓ Save to Catalog</button>
      <button class="btn bo bsm" onclick="closeManualAdd()">Cancel</button>
    </div>
  </div>
</div>

<!-- ═══ PDF UPLOAD MODAL ═══ -->
<div class="overlay" id="pdfUploadModal">
  <div class="mbox" style="max-width:420px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>📄 Add / Update PDF</h3>
      <button class="btn bo bsm" onclick="closePdfUpload()">✕</button>
    </div>
    <div id="puModelLabel" style="font-size:.82rem;font-weight:700;color:var(--bd);margin-bottom:6px"></div>
    <!-- Existing PDF indicator -->
    <div id="puExistingRow" style="display:none;background:#e8f5e9;border:1px solid #c8e6c9;border-radius:6px;padding:7px 10px;font-size:.72rem;color:#2e7d32;margin-bottom:10px">
      ✅ This item already has a PDF attached.
      <a id="puExistingLink" href="#" target="_blank" style="margin-left:6px;color:#1a6fa0;font-size:.7rem">View current PDF</a>
    </div>
    <!-- Merge/Replace radio — only shown when existing PDF exists -->
    <div id="puModeRow" style="display:none;margin-bottom:10px;background:#f4f8fc;border:1px solid var(--bdr);border-radius:6px;padding:8px 10px">
      <div style="font-size:.62rem;color:var(--sub);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px">Upload mode</div>
      <label style="display:flex;align-items:center;gap:7px;font-size:.74rem;cursor:pointer;margin-bottom:5px">
        <input type="radio" name="puMode" id="puModeReplace" value="replace" checked> Replace existing PDF with the new file
      </label>
      <label style="display:flex;align-items:center;gap:7px;font-size:.74rem;cursor:pointer">
        <input type="radio" name="puMode" id="puModeMerge" value="merge"> Merge: append new pages after existing PDF
      </label>
    </div>
    <div style="margin-bottom:10px">
      <label style="font-size:.62rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Select PDF File *</label>
      <input type="file" id="puFile" accept=".pdf"
        style="width:100%;padding:6px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.75rem"
        onchange="puPreview(this)">
    </div>
    <div id="puPreview" style="display:none;background:#f4f8fc;border:1px solid var(--bdr);border-radius:6px;padding:8px 10px;font-size:.72rem;color:var(--bd);margin-bottom:10px">
      📄 <span id="puFileName"></span> — <span id="puFileSize"></span>
    </div>
    <div id="puProgress" style="display:none;font-size:.72rem;padding:7px 10px;border-radius:5px;margin-bottom:8px"></div>
    <div style="display:flex;gap:8px">
      <button class="btn bg" id="puSaveBtn" onclick="submitPdfUpload()" style="flex:1;background:linear-gradient(135deg,var(--teal),#1a9a6a)">⬆ Upload PDF</button>
      <button class="btn bo bsm" onclick="closePdfUpload()">Cancel</button>
    </div>
  </div>
</div>


<!-- Revision Picker Modal -->
<div class="overlay" id="revModal">
  <div class="mbox" style="max-width:480px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>📋 Save Revision</h3>
      <button class="btn bo bsm" onclick="closeRevModal()">✕</button>
    </div>
    <p style="font-size:.74rem;color:var(--sub);margin-bottom:14px">
      Choose which base quote to branch this revision from, then click Save.
    </p>
    <!-- Base quote selector -->
    <div style="margin-bottom:12px">
      <label style="font-size:.62rem;color:var(--sub);text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px">Branch from</label>
      <select id="revBaseSelect" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.76rem" onchange="updateRevPreview()">
      </select>
    </div>
    <!-- Next revision preview -->
    <div id="revPreviewRow" style="background:#f4f8fc;border:1px solid var(--bdr);border-radius:6px;padding:8px 12px;font-size:.74rem;margin-bottom:14px">
      New revision will be saved as: <strong id="revPreviewNum">—</strong>
    </div>
    <div id="revModalErr" style="font-size:.7rem;color:#c04040;background:#fff0f0;border:1px solid #f0c0c0;border-radius:5px;padding:7px 10px;display:none;margin-bottom:8px"></div>
    <div style="display:flex;gap:8px">
      <button class="btn bg" onclick="saveRevisionFromModal()" style="flex:1">📋 Save Revision</button>
      <button class="btn bo bsm" onclick="closeRevModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Create Parent Modal -->
<div class="overlay" id="cparModal">
  <div class="mbox" style="max-width:440px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>&#8853; Create Parent System</h3>
      <button class="btn bo bsm" onclick="closeCpar()">&#10005;</button>
    </div>
    <p style="font-size:.72rem;color:var(--sub);margin-bottom:12px">Creates a named parent above <strong id="cparChildLabel"></strong>. That item and any others you assign become sub-items whose extended prices are summed into the parent total.</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
      <div style="grid-column:1/-1">
        <label class="ml">System Name *</label>
        <input type="text" id="cparName" placeholder="e.g. Complete Mechatronic Training System" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div style="grid-column:1/-1">
        <label class="ml">Description (shown in quote)</label>
        <input type="text" id="cparDesc" placeholder="e.g. Includes the items listed below" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div>
        <label class="ml">System Quantity</label>
        <input type="number" id="cparQty" value="1" min="1" style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div style="display:flex;align-items:flex-end;padding-bottom:2px;gap:8px">
        <input type="checkbox" id="cparMoveAll" style="width:16px;height:16px">
        <label for="cparMoveAll" style="font-size:.72rem;cursor:pointer">Also include all other <em>standalone</em> items in this section as sub-items</label>
      </div>
    </div>
    <div id="cparErr" style="font-size:.7rem;color:#c04040;background:#fff0f0;border:1px solid #f0c0c0;border-radius:5px;padding:7px 10px;display:none;margin-bottom:8px"></div>
    <div style="display:flex;gap:8px">
      <button class="btn bg" onclick="saveCpar()" style="flex:1">&#10003; Create Parent</button>
      <button class="btn bo bsm" onclick="closeCpar()">Cancel</button>
    </div>
  </div>
</div>

<!-- Price Edit Modal -->
<div class="overlay" id="priceEditModal">
  <div class="mbox" style="max-width:420px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>✏️ Adjust Net Price</h3>
      <button class="btn bo bsm" onclick="closePriceEdit()">✕</button>
    </div>
    <div style="background:#fff8f0;border:1px solid #f0c060;border-radius:6px;padding:8px 12px;margin-bottom:12px;font-size:.72rem;color:#8a5a00">
      ⚠ This adjusts the <strong>net cost</strong> used when calculating your selling price. Changes affect this session unless marked permanent.
    </div>
    <div id="peModelLabel" style="font-size:.8rem;font-weight:700;color:var(--bd);margin-bottom:8px"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
      <div>
        <label style="font-size:.62rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Current Net Price</label>
        <div id="peCurrentPrice" style="font-size:.85rem;font-weight:700;color:var(--sub);padding:6px 0"></div>
      </div>
      <div>
        <label style="font-size:.62rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">New Net Price (USD) *</label>
        <input type="number" id="peNewPrice" step="0.01" min="0" placeholder="0.00"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.85rem">
      </div>
      <div style="grid-column:1/-1">
        <label style="font-size:.62rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Reason for Change *</label>
        <input type="text" id="peReason" placeholder="e.g. Updated pricing from Amatrol email 05/2026"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div>
        <label style="font-size:.62rem;color:var(--sub);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Applies to Quote(s)</label>
        <input type="text" id="peQuotes" placeholder="e.g. INTL2026-329 or All"
          style="width:100%;padding:7px 9px;border:1.5px solid var(--bdr);border-radius:5px">
      </div>
      <div style="display:flex;align-items:center;gap:8px;padding-top:18px">
        <input type="checkbox" id="pePermanent" style="width:16px;height:16px;cursor:pointer">
        <label for="pePermanent" style="font-size:.75rem;color:var(--bd);cursor:pointer;font-weight:600">Make Permanent<br><span style="font-weight:400;color:var(--sub);font-size:.65rem">Updates database &amp; marks with ★</span></label>
      </div>
    </div>
    <div id="peErr" style="font-size:.7rem;color:#c04040;background:#fff0f0;border:1px solid #f0c0c0;border-radius:5px;padding:6px 10px;display:none;margin-bottom:8px"></div>
    <div style="display:flex;gap:8px">
      <button class="btn bg" onclick="applyPriceEdit()" style="flex:1">✓ Apply Price</button>
      <button class="btn bo bsm" onclick="closePriceEdit()">Cancel</button>
    </div>
  </div>
</div>

<!-- Add location modal -->
<div class="overlay" id="locModal" onclick="if(event.target===this)closeLocModal()">

<!-- ── LOCAL AGENTS MODAL ── -->
<div class="overlay" id="agentsModal" onclick="if(event.target===this)closeAgentsModal()">
  <div class="mbox" style="max-width:560px;max-height:88vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <div>
        <h3>👤 Local Agents</h3>
        <div id="agentsClientLabel" style="font-size:.7rem;color:var(--sub);margin-top:2px"></div>
      </div>
      <button class="btn bo bsm" onclick="closeAgentsModal()">✕</button>
    </div>

    <!-- Existing agents list -->
    <div id="agentsList" style="margin-bottom:14px"></div>

    <!-- Add new agent form -->
    <div style="background:#f4f8fc;border:1px solid var(--bdr);border-radius:8px;padding:12px 14px">
      <div style="font-size:.68rem;font-weight:700;color:var(--bd);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px">
        ＋ Add Local Agent
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div style="grid-column:1/-1">
          <label style="font-size:.63rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px">Agent Company Name *</label>
          <input type="text" id="agCompany" placeholder="e.g. Al-Rashid Trading Co." style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
        </div>
        <div>
          <label style="font-size:.63rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px">Contact Person</label>
          <input type="text" id="agContact" placeholder="Full name" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
        </div>
        <div>
          <label style="font-size:.63rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px">Title / Role</label>
          <input type="text" id="agTitle" placeholder="e.g. Sales Director" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
        </div>
        <div>
          <label style="font-size:.63rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px">Country</label>
          <select id="agCountry" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
            <option value="">— Same as client —</option>
            <option>The Kingdom of Saudi Arabia (KSA)</option><option>UAE</option><option>Qatar</option>
            <option>Kuwait</option><option>Bahrain</option><option>Oman</option>
            <option>Egypt</option><option>Jordan</option><option>Iraq</option>
            <option>Libya</option><option>Algeria</option><option>Morocco</option>
            <option>United States</option><option>Other</option>
          </select>
        </div>
        <div>
          <label style="font-size:.63rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px">Phone</label>
          <input type="text" id="agPhone" placeholder="+966 11 …" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
        </div>
        <div>
          <label style="font-size:.63rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px">Mobile</label>
          <input type="text" id="agMobile" placeholder="+966 5x …" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
        </div>
        <div>
          <label style="font-size:.63rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px">Email</label>
          <input type="email" id="agEmail" placeholder="agent@company.com" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
        </div>
        <div>
          <label style="font-size:.63rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px">Commission %</label>
          <input type="number" id="agComm" placeholder="e.g. 5" min="0" max="100" step="0.5" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
        </div>
        <div style="grid-column:1/-1">
          <label style="font-size:.63rem;color:var(--sub);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px">Notes</label>
          <input type="text" id="agNotes" placeholder="Optional notes…" style="width:100%;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px">
        </div>
      </div>
      <div id="agErr" style="font-size:.7rem;color:#c04040;background:#fff0f0;border:1px solid #f0c0c0;border-radius:5px;padding:6px 9px;display:none;margin-top:8px"></div>
      <div style="margin-top:10px;display:flex;gap:8px">
        <button class="btn bg bsm" id="agSaveBtn" onclick="saveAgent()" style="background:linear-gradient(135deg,#0db8a8,#1a6fa0)">＋ Add Agent</button>
        <button class="btn bo bsm" onclick="clearAgentForm()">Clear</button>
      </div>
    </div>
  </div>
</div>
  <div class="mbox" style="max-width:320px">
    <h3>Add Shipping Location</h3>
    <input type="text" id="newLoc" placeholder="e.g. Port of Miami, FL USA" style="width:100%;margin-bottom:10px;">
    <div style="display:flex;gap:8px">
      <button class="btn bg bsm" onclick="saveNewLoc()">Add</button>
      <button class="btn bo bsm" onclick="closeLocModal()">Cancel</button>
    </div>
  </div>
</div>

<script>
var API = 'api.php';
var sections = [], secCtr = 0;
var shipEstimates = []; // [{desc:'Sea Freight',amt:5000},...]
var DEFAULT_LC_TERMS =
  'Letter of Credit due 10 days from the time an order is placed for 100% of the value of the order. Corporate Policy requires the Letter of Credit to contain or include the following:\n\n'
  + 'i.     Irrevocable Letter of Credit, TRANSFERABLE\n'
  + 'ii.    Payment at Sight\n'
  + 'iii.   Confirmed\n'
  + 'iv.    Place of expiry is United States\n'
  + 'v.     Issued in USD\n'
  + 'vi.    Presentation of documents restricted to the counters of TD BANK USA';

function isExWorksSelected(){
  var v = (document.getElementById('inco').value || '').toUpperCase();
  return v.indexOf('EXW') === 0 || v.indexOf('EX WORKS') >= 0;
}
function onIncotermChange(){
  var exw = isExWorksSelected();
  var block = document.getElementById('shipEstimatesBlock');
  var note = document.getElementById('exwShipNote');
  if(block) block.style.display = exw ? 'none' : '';
  if(note) note.style.display = exw ? '' : 'none';
}
function resetLcTerms(){
  var ta = document.getElementById('payLcTerms');
  if(ta) ta.value = DEFAULT_LC_TERMS;
}
function onPaymentTermsChange(){
  var wire = document.getElementById('payWire');
  var lcChk = document.getElementById('payIncludeLc');
  var lcWrap = document.getElementById('payLcWrap');
  var lcBox = document.getElementById('payLcBox');
  var lcOnly = wire && wire.value === 'none';
  if(lcOnly){
    if(lcChk){ lcChk.checked = true; lcChk.disabled = true; }
    if(lcWrap) lcWrap.style.opacity = '0.75';
  } else {
    if(lcChk) lcChk.disabled = false;
    if(lcWrap) lcWrap.style.opacity = '1';
  }
  var showLc = lcOnly || (lcChk && lcChk.checked);
  if(lcBox) lcBox.style.display = showLc ? '' : 'none';
  var ta = document.getElementById('payLcTerms');
  if(showLc && ta && !ta.value.trim()) ta.value = DEFAULT_LC_TERMS;
}
function getPaymentPayload(){
  var wireEl = document.getElementById('payWire');
  var lcChk = document.getElementById('payIncludeLc');
  var ta = document.getElementById('payLcTerms');
  var wire = wireEl ? wireEl.value : '30_70';
  var includeLc = wire === 'none' || !!(lcChk && lcChk.checked);
  var lcTerms = ta ? ta.value.trim() : '';
  if(includeLc && !lcTerms) lcTerms = DEFAULT_LC_TERMS;
  return {
    payment_wire: wire,
    payment_include_lc: includeLc,
    payment_lc_terms: lcTerms
  };
}
function applyPaymentFromQuote(q){
  var wireEl = document.getElementById('payWire');
  var lcChk = document.getElementById('payIncludeLc');
  var ta = document.getElementById('payLcTerms');
  if(wireEl) wireEl.value = q.payment_wire || '30_70';
  if(lcChk) lcChk.checked = !!q.payment_include_lc || (q.payment_wire === 'none');
  if(ta) ta.value = q.payment_lc_terms || DEFAULT_LC_TERMS;
  onPaymentTermsChange();
}
var divisors = [{key:'D1', name:'Standard', value:0.65}];
var divColors = ['#0db8a8','#1a6fa0','#c9a227','#c04040','#8a4ad4','#1a9a6a'];

var cache = {}, prodMap = {};
var docMap = {}; // model_id → true if combined PDF exists
var pendingDeps = [], pendingDepSec = null;
var pendingProd = null;
var stimer = null;

/* ── INIT ── */
var currentQuoteId = null;

(function(){
  var d = new Date();
  document.getElementById('qdate').value = d.toISOString().split('T')[0];

  // Events
  document.getElementById('si').addEventListener('input', function(){ clearTimeout(stimer); stimer = setTimeout(search, 280); });
  document.getElementById('mf').addEventListener('change', search);
  document.getElementById('cf').addEventListener('change', search);
  try{ initDivisors(); }catch(e){ console.warn('initDivisors error:',e); }
  // #div listener handled by rebuildDivisorUI's div-val event delegation
  document.getElementById('disc').addEventListener('input', recalc);
  document.getElementById('qn').addEventListener('input', syncQN);
  document.getElementById('addSecBtn').addEventListener('click', function(){ addSection(); });

  // Client select → auto-fill country
  document.getElementById('cs').addEventListener('change', function(){
    var opt = this.options[this.selectedIndex];
    if(opt && opt.dataset.country){
      document.getElementById('country').value = opt.dataset.country;
    } else {
      document.getElementById('country').value = '';
    }
    var ab = document.getElementById('agentsBtn');
    if(ab) ab.style.display = (this.value && this.value !== '') ? '' : 'none';
    // Auto-select this client's logo in the proposal logo picker
    if(opt && opt.dataset.logo){
      acApplyLogoToProposal(opt.dataset.logo, opt.dataset.name || opt.textContent);
    }
  });

  // Quote selector
  document.getElementById('quoteSelect').addEventListener('change', onQuoteSelect);

  // Quote search — live-filters the dropdown options
  document.getElementById('savedQuoteSearch').addEventListener('input', function(){
    renderQuoteSelect(this.value.toLowerCase().trim());
  });

  // Load filters
  apiFetch('action=filters').then(function(r){
    if(!r.ok) return;
    r.manufacturers.forEach(function(m){ addOpt('mf', m); });
    r.classes.forEach(function(c){ addOpt('cf', c); });
    document.getElementById('catCnt').textContent = r.total.toLocaleString() + ' products';
  });

  // Load clients
  apiFetch('action=clients').then(function(r){
    if(!r.ok) return;
    (r.clients||[]).forEach(function(c){
      var o = document.createElement('option');
      o.value = c.id;
      o.textContent = c.company_name + (c.country ? ' (' + c.country + ')' : '');
      o.dataset.name    = c.company_name || '';
      o.dataset.country = c.country || '';
      o.dataset.logo    = c.logo    || '';  // logo path stored for auto-select
      document.getElementById('cs').appendChild(o);
    });
  }).catch(function(){});

  // Load quote list and auto-generate new quote number
  loadQuoteList(true);

  search();
  updDP();
  renderShipRows();
  resetLcTerms();
  onPaymentTermsChange();
  onIncotermChange();
  setTimeout(function(){
    var ls=document.getElementById('proposalLogoSelect');
    if(ls) ls.addEventListener('change',function(){
      if(this.value) showLogoPreview(this.value,this.options[this.selectedIndex].text);
      else { var p=document.getElementById('logoPreview'); if(p) p.style.display='none'; }
    });
  },100);
})();

/* ── QUOTE LIST & LOAD ── */
var _allQuotes = []; // cached quote list for search filtering

function loadQuoteList(initNew){
  apiFetch('action=list_quotes').then(function(r){
    if(!r.ok) return;
    _allQuotes = r.quotes || [];
    renderQuoteSelect('');
    if(initNew) generateNewQuoteNum();
  }).catch(function(){ if(initNew) generateNewQuoteNum(); });
}

function renderQuoteSelect(filter){
  var sel = document.getElementById('quoteSelect');
  var currentVal = sel.value;
  sel.innerHTML = '<option value="new">— New Quote —</option>';

  // Group by base_seq
  var byBase = {};
  var baseOrder = [];
  _allQuotes.forEach(function(q){
    // Apply search filter: match on quote_number, client_name, country, date
    if(filter){
      var haystack = (q.quote_number + ' ' + (q.client_name||'') + ' ' + (q.country||'') + ' ' + (q.quote_date||'')).toLowerCase();
      if(!haystack.includes(filter)) return;
    }
    var key = q.year + '_' + q.base_seq;
    if(!byBase[key]){ byBase[key]=[]; baseOrder.push(key); }
    byBase[key].push(q);
  });

  // Sort base groups newest first
  baseOrder.sort(function(a,b){
    var pa=a.split('_'), pb=b.split('_');
    if(pb[0]!==pa[0]) return parseInt(pb[0])-parseInt(pa[0]);
    return parseInt(pb[1])-parseInt(pa[1]);
  });

  baseOrder.forEach(function(key){
    var grp = byBase[key];
    // Sort: base quote (revision=0) first, then R1, R2...
    grp.sort(function(a,b){ return a.revision - b.revision; });
    var base = grp[0];
    var baseLabel = base.quote_number + ' — ' + (base.client_name||'No client') + (base.country ? ' ('+base.country+')' : '');

    if(grp.length === 1){
      // Single quote — no grouping needed
      var o = document.createElement('option');
      o.value = base.id;
      o.textContent = baseLabel + ' — ' + (base.quote_date||'');
      sel.appendChild(o);
    } else {
      // Multiple revisions — use optgroup
      var og = document.createElement('optgroup');
      og.label = '▼ ' + baseLabel; // ▼ prefix shows it's collapsible
      grp.forEach(function(q){
        var o = document.createElement('option');
        o.value = q.id;
        var revLabel = q.revision === 0 ? 'Original' : 'Revision R' + q.revision;
        o.textContent = '    ' + q.quote_number + ' (' + revLabel + ') — ' + (q.quote_date||'');
        og.appendChild(o);
      });
      sel.appendChild(og);
    }
  });

  // Restore selection if still present
  if(currentVal && currentVal !== 'new'){
    sel.value = currentVal;
    if(!sel.value) sel.value = 'new';
  }
}

function generateNewQuoteNum(){
  apiFetch('action=next_quote_num').then(function(r){
    if(r.ok){
      document.getElementById('qn').value = r.quote_number;
      syncQN();
    }
  });
}

function onQuoteSelect(){
  var val = document.getElementById('quoteSelect').value;
  if(val === 'new'){
    // Reset to a fresh new quote
    currentQuoteId = null;
    sections = []; secCtr = 0;
    document.getElementById('saveRevBtn').style.display = 'none';
    generateNewQuoteNum();
    document.getElementById('cs').value = '';
    document.getElementById('country').value = '';
    document.getElementById('inco').value = '';
    document.getElementById('div').value = '0.65';
    document.getElementById('disc').value = '0';
    var payWire = document.getElementById('payWire');
    var payLc = document.getElementById('payIncludeLc');
    if(payWire) payWire.value = '30_70';
    if(payLc){ payLc.checked = false; }
    resetLcTerms();
    onPaymentTermsChange();
    onIncotermChange();
    renderQuote(); cache={}; search();
  } else {
    apiFetch('action=load_quote&id='+encodeURIComponent(val)).then(function(r){
      if(!r.ok){ alert('Could not load quote: '+(r.error||'')); return; }
      restoreQuote(r.quote);
    });
  }
}

function restoreQuote(q){
  currentQuoteId = q.id;
  document.getElementById('qn').value = q.quote_number || '';
  if(typeof loadQuoteAttachments === 'function') loadQuoteAttachments();
  document.getElementById('qdate').value = q.quote_date || new Date().toISOString().split('T')[0];
  document.getElementById('cur').value = q.currency || 'USD';
  // Restore divisors — do this AFTER sections to prevent any error blocking the load
  if(q.divisors && q.divisors.length){ divisors=q.divisors; } else { divisors=[{key:'D1',name:'Standard',value:q.divisor||0.65}]; }
  var divEl = document.getElementById('div');
  if(divEl) divEl.value = divisors[0].value;
  document.getElementById('disc').value = q.discount_pct || 0;
  document.getElementById('inco').value = q.incoterm || '';
  document.getElementById('country').value = q.country || '';
  applyPaymentFromQuote(q);

  // Restore inco_location (add option if not present)
  var locSel = document.getElementById('incoLocation');
  var il = q.inco_location || '';
  if(il){
    var exists = false;
    for(var i=0;i<locSel.options.length;i++){if(locSel.options[i].value===il){exists=true;break;}}
    if(!exists){var o=document.createElement('option');o.value=il;o.textContent=il;locSel.appendChild(o);}
    locSel.value = il;
  }

  // Restore client — match by id first, then by name, add temp option if needed
  var csEl = document.getElementById('cs');
  var clientRestored = false;
  if(q.client_id){
    csEl.value = q.client_id;
    if(csEl.value === q.client_id) clientRestored = true;
  }
  if(!clientRestored && q.client_name){
    for(var oi=0; oi<csEl.options.length; oi++){
      if((csEl.options[oi].dataset.name||'') === q.client_name){ csEl.selectedIndex=oi; clientRestored=true; break; }
    }
  }
  if(!clientRestored && q.client_name){
    var tempOpt = document.createElement('option');
    tempOpt.value = q.client_id || ('_tmp_'+Date.now());
    tempOpt.textContent = q.client_name + (q.country ? ' (' + q.country + ')' : '');
    tempOpt.dataset.name = q.client_name;
    tempOpt.dataset.country = q.country || '';
    csEl.appendChild(tempOpt);
    csEl.value = tempOpt.value;
  }

  // Restore sections from stored JSON
  var data = q.sections_json;
  if(data && data.sections){
    secCtr = 0;
    sections = data.sections.map(function(sec){
      secCtr++;
      return {
        id: sec.id || ('s'+secCtr),
        name: sec.name || ('Section '+secCtr),
        restartNum: sec.restartNum || false,
        items: (sec.items||[]).map(function(item){
          // Rebuild the product object from stored data
          var prod = {
            model_id:    item.model_id,
            title_only:  item.title_only || item.model_id,
            title_description: item.title_description || '',
            description: item.description || '',
            isSynthetic: item.isSynthetic || false,
            intl_dist_net: item.intl_dist_net || null,
            manufacturer:  item.manufacturer || '',
            product_class: item.product_class || '',
            key_topic:     item.key_topic || '',
            intl_market_price_note: item.intl_market_price_note || null,
            requires_models: item.requires_models || [],
            mfr_lead_time:   item.mfr_lead_time || ''
          };
          prodMap[prod.model_id] = prod;
          return {
            product:    prod,
            qty:        item.qty || 1,
            isSubOf:    item.isSubOf || null,
            subPricing: item.subPricing || 'included',
            isOptional: item.isOptional || false,
            divisorKey: item.divisorKey || null,
            lid:        item.lid || (Date.now().toString(36)+Math.random().toString(36).slice(2,6)),
            custom_num: item.custom_num || null,
            spec_item_num: item.spec_item_num || null
          };
        })
      };
    });
  } else {
    sections = []; secCtr = 0;
  }

  // Re-apply any session price overrides — stored JSON may have old prices
  if(Object.keys(priceOverrides).length){
    allItems().forEach(function(line){
      var ov = priceOverrides[line.product.model_id];
      if(ov){
        line.product.intl_dist_net  = ov.price;
        line.product._priceOverride = ov.price;
        line.product._priceNote     = ov.note;
      }
    });
    // Also refresh prodMap entries
    Object.keys(priceOverrides).forEach(function(mid){
      if(prodMap[mid]){
        prodMap[mid].intl_dist_net  = priceOverrides[mid].price;
        prodMap[mid]._priceOverride = priceOverrides[mid].price;
        prodMap[mid]._priceNote     = priceOverrides[mid].note;
      }
    });
  }

  // Restore shipping estimates
  shipEstimates = q.ship_estimates || [];
  // Restore install/commissioning
  var instAmt = document.getElementById('installAmt');
  var instLbl = document.getElementById('installLabel');
  if(instAmt && q.install_train_amt) instAmt.value = q.install_train_amt;
  if(instLbl && q.install_train_label) instLbl.value = q.install_train_label;
  renderShipRows();
  onIncotermChange();

  // Show revision button with correct next revision label
  var nextRev = (q.revision||0) + 1;
  var revBtn = document.getElementById('saveRevBtn');
  revBtn.style.display = '';
  revBtn.textContent = '📋 Save as R' + nextRev;

  // Rebuild divisor UI now that everything is set — AFTER sections so any error can't block load
  try{ initDivisors(); }catch(e){ console.warn('initDivisors error:',e); }

  renderQuote(); cache={}; search();
  syncQN(); updDP();

  // Populate docMap for all items in this quote so PDF indicators show correctly
  var qIds = allItems().map(function(l){ return l.product.model_id; });
  if(qIds.length){
    apiPost({action:'check_documents', ids:qIds}).then(function(dr){
      if(dr.ok && dr.has_doc && dr.has_doc.length){
        dr.has_doc.forEach(function(mid){ docMap[mid]=true; });
        renderQuote(); // Re-render with correct PDF badges
      }
    }).catch(function(){});
  }
}

/* ── SAVE QUOTE ── */
function buildSavePayload(){
  var cOpt = document.getElementById('cs').options[document.getElementById('cs').selectedIndex];
  var pay = getPaymentPayload();
  return {
    client_id:    document.getElementById('cs').value || null,
    client_name:  cOpt ? (cOpt.dataset.name || cOpt.textContent.replace(/\s*\(.*\)\s*$/,'').trim()) : '',
    country:      document.getElementById('country').value.trim(),
    quote_date:   document.getElementById('qdate').value,
    currency:     document.getElementById('cur').value,
    incoterm:     document.getElementById('inco').value,
    inco_location:document.getElementById('incoLocation').value,
    divisor:      parseFloat(document.getElementById('div').value) || 0.65,
    discount_pct: parseFloat(document.getElementById('disc').value) || 0,
    divisors: divisors,
    ship_estimates: getShipPayload(),
    install_train_amt:   parseFloat(document.getElementById('installAmt').value)||0,
    install_train_label: document.getElementById('installLabel').value||'INSTALLATION AND COMMISSIONING',
    payment_wire: pay.payment_wire,
    payment_include_lc: pay.payment_include_lc,
    payment_lc_terms: pay.payment_lc_terms,
    sections: sections.map(function(sec){
      return {
        id: sec.id,
        name: sec.name,
        restartNum: sec.restartNum || false,
        items: sec.items.map(function(line){
          // Store full product data so we can restore without re-querying
          return {
            model_id:              line.product.model_id,
            title_only:            line.product.title_only,
            title_description:     line.product.title_description || '',
            description:           line.product.description || '',
            isSynthetic:           line.product.isSynthetic || false,
            intl_dist_net:         line.product.intl_dist_net,
            manufacturer:          line.product.manufacturer || '',
            product_class:         line.product.product_class || '',
            key_topic:             line.product.key_topic || '',
            intl_market_price_note:line.product.intl_market_price_note || null,
            requires_models:       line.product.requires_models || [],
            mfr_lead_time:         line.product.mfr_lead_time || '',
            qty:        line.qty,
            isSubOf:    line.isSubOf || null,
            subPricing: line.subPricing || 'included',
            isOptional: line.isOptional || false,
            divisorKey: line.divisorKey || null,
            lid:        line.lid || null,
            custom_num: line.custom_num || null,
            spec_item_num: line.spec_item_num || null
          };
        })
      };
    })
  };
}

function saveQuote(){
  if(!allItems().length){ alert('Add items before saving.'); return; }
  var payload = buildSavePayload();
  if(currentQuoteId){
    payload.action = 'update_quote';
    payload.id = currentQuoteId;
  } else {
    payload.action = 'save_quote';
  }
  apiPost(payload).then(function(r){
    if(!r.ok){ alert('Save failed: '+(r.error||'Unknown error')); return; }
    currentQuoteId = r.id;
    document.getElementById('qn').value = r.quote_number;
    syncQN();
    loadQuoteList(false);
    // Show revision button
    var revBtn = document.getElementById('saveRevBtn');
    revBtn.style.display = '';
    revBtn.textContent = '📋 Save as R1';
    alert('✓ Saved: ' + r.quote_number);
  }).catch(function(e){ alert('Error: '+e.message); });
}

function openRevModal(){
  if(!currentQuoteId){ alert('Save the quote first, then create a revision.'); return; }
  if(!allItems().length){ alert('No items to save.'); return; }
  // Build the base select from _allQuotes — find all quotes in the same family
  var sel = document.getElementById('revBaseSelect');
  sel.innerHTML = '';
  // Group quotes by base_seq+year to find this quote's family
  var curQ = _allQuotes.find(function(q){ return q.id === currentQuoteId; });
  var familyKey = curQ ? (curQ.year+'_'+curQ.base_seq) : null;
  // Add current quote and all its revision siblings as options
  var family = familyKey ? _allQuotes.filter(function(q){ return (q.year+'_'+q.base_seq)===familyKey; }) : [];
  family.sort(function(a,b){ return a.revision-b.revision; });
  if(!family.length && curQ) family = [curQ];
  family.forEach(function(q){
    var o = document.createElement('option');
    o.value = q.id;
    o.dataset.rev = q.revision;
    o.dataset.baseSeq = q.base_seq;
    o.dataset.year = q.year;
    var label = q.revision===0 ? q.quote_number+' (Original)' : q.quote_number+' (R'+q.revision+')';
    o.textContent = label + ' — ' + (q.quote_date||'');
    if(q.id===currentQuoteId) o.selected = true;
    sel.appendChild(o);
  });
  // Also offer other quote families in a separator group
  var others = _allQuotes.filter(function(q){ return (q.year+'_'+q.base_seq)!==familyKey; });
  if(others.length){
    var og = document.createElement('optgroup');
    og.label = '— Other quotes —';
    others.sort(function(a,b){ return b.id-a.id; });
    others.forEach(function(q){
      var o = document.createElement('option');
      o.value = q.id;
      o.dataset.rev = q.revision;
      o.dataset.baseSeq = q.base_seq;
      o.dataset.year = q.year;
      o.textContent = q.quote_number + ' — ' + (q.client_name||'') + ' — ' + (q.quote_date||'');
      og.appendChild(o);
    });
    sel.appendChild(og);
  }
  updateRevPreview();
  document.getElementById('revModalErr').style.display='none';
  document.getElementById('revModal').classList.add('open');
}
function closeRevModal(){ document.getElementById('revModal').classList.remove('open'); }
function updateRevPreview(){
  var sel = document.getElementById('revBaseSelect');
  var opt = sel.options[sel.selectedIndex];
  if(!opt){ document.getElementById('revPreviewNum').textContent='—'; return; }
  var baseSeq = opt.dataset.baseSeq;
  var year = opt.dataset.year;
  // Find max revision for this base
  var maxRev = 0;
  _allQuotes.forEach(function(q){
    if(String(q.base_seq)===String(baseSeq)&&String(q.year)===String(year)) maxRev=Math.max(maxRev,q.revision);
  });
  var nextRev = maxRev + 1;
  document.getElementById('revPreviewNum').textContent = 'INTL'+year+'-'+baseSeq+'-R'+nextRev;
}
function saveRevisionFromModal(){
  var sel = document.getElementById('revBaseSelect');
  var baseId = parseInt(sel.value);
  if(!baseId){ var e=document.getElementById('revModalErr');e.textContent='Select a base quote.';e.style.display='block';return; }
  var payload = buildSavePayload();
  payload.action = 'save_revision';
  payload.base_id = baseId;
  var btn = document.querySelector('#revModal .btn.bg');
  btn.disabled=true; btn.textContent='Saving…';
  apiPost(payload).then(function(r){
    btn.disabled=false; btn.textContent='📋 Save Revision';
    if(!r.ok){ var el=document.getElementById('revModalErr');el.textContent='Error: '+(r.error||'');el.style.display='block';return; }
    currentQuoteId = r.id;
    document.getElementById('qn').value = r.quote_number;
    syncQN();
    loadQuoteList(false);
    var nextRev = (r.revision||1) + 1;
    var revBtn = document.getElementById('saveRevBtn');
    revBtn.textContent = '📋 Save as R' + nextRev;
    closeRevModal();
    // Brief confirmation in button
    revBtn.textContent = '✓ Saved '+r.quote_number;
    setTimeout(function(){ revBtn.textContent = '📋 Save as R'+nextRev; },3000);
  }).catch(function(err){ btn.disabled=false; btn.textContent='📋 Save Revision'; var el=document.getElementById('revModalErr');el.textContent='Error: '+err.message;el.style.display='block'; });
}
function saveRevision(){ openRevModal(); } // keep old name as alias

function addOpt(id, val){
  var o = document.createElement('option');
  o.value = val; o.textContent = val;
  document.getElementById(id).appendChild(o);
}

/* ── API ── */
function apiFetch(qs, method, body){
  load(true);
  var url = API + '?' + qs;
  var opts = { method: method || 'GET', headers: {'Content-Type':'application/json'} };
  if(body) opts.body = JSON.stringify(body);
  return fetch(url, opts).then(function(r){ return r.json(); }).finally(function(){ load(false); });
}

function apiPost(body){
  load(true);
  return fetch(API, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(body)
  }).then(function(r){ return r.json(); }).finally(function(){ load(false); });
}

function load(on){ document.getElementById('loadBar').style.transform = on ? 'scaleX(.6)' : 'scaleX(0)'; }

/* ── SEARCH ── */
function search(){
  var q = document.getElementById('si').value.trim();
  var mfr = document.getElementById('mf').value;
  var cls = document.getElementById('cf').value;
  var key = q + '|' + mfr + '|' + cls;
  if(cache[key]){ renderList(cache[key]); return; }
  var p = new URLSearchParams({action:'search', q:q, mfr:mfr, cls:cls, limit:150});
  apiFetch(p.toString()).then(function(r){
    if(!r.ok) return;
    r.results.forEach(function(p){ prodMap[p.model_id] = p; });
    // Re-apply session price overrides — server returns old prices
    Object.keys(priceOverrides).forEach(function(mid){
      if(prodMap[mid]){
        prodMap[mid]._priceOverride = priceOverrides[mid].price;
        prodMap[mid].intl_dist_net  = priceOverrides[mid].price;
        prodMap[mid]._priceNote     = priceOverrides[mid].note;
      }
    });
    cache[key] = r.results;
    document.getElementById('rc').textContent = r.count + (r.count >= 150 ? '+' : '') + ' results';
    // Render immediately — don't block on check_documents
    renderList(r.results);
    // Then async check for PDF datasheets and re-render if any found
    var ids = r.results.map(function(p){ return p.model_id; });
    apiPost({action:'check_documents', ids:ids}).then(function(dr){
      if(dr.ok && dr.has_doc && dr.has_doc.length){
        dr.has_doc.forEach(function(mid){ docMap[mid]=true; });
        renderList(r.results); // Re-render with PDF badges
      }
    }).catch(function(){}); // Silent — PDF badges optional
  }).catch(function(err){ console.warn('Search error:', err); });
}

function renderList(results){
  var inQ = new Set(allItems().map(function(l){ return l.product.model_id; }));
  var el = document.getElementById('rl');
  if(!results.length){ el.innerHTML = '<div class="eq">No products found</div>'; return; }
  el.innerHTML = results.map(function(p){
    var added = inQ.has(p.model_id);
    var np = (p._priceOverride || p.intl_dist_net) ? '$' + fmt(p._priceOverride || p.intl_dist_net) : (p.intl_market_price_note ? 'See Amatrol' : 'N/A');
    var di = (p.requires_models && p.requires_models.length) ? '🔗 ' : '';
    var ri = (p.recommended_models && p.recommended_models.length) ? '<span class="rec-badge">💡 rec</span>' : '';
    var mfr = (p.manufacturer||'').replace('DACW All','DAC Worldwide').replace('DACW International Only','DAC Intl');
    var mid = e(p.model_id);
    var hasPdf = docMap[p.model_id] || false;
    var pdfLink = hasPdf
      ? '<a class="pdf-btn" href="api.php?action=get_document&mid='+encodeURIComponent(p.model_id)+'" target="_blank" onclick="event.stopPropagation()" title="Download Datasheet">📄</a>'
        + '<button class="pdf-btn" style="margin-left:2px" onclick="event.stopPropagation();openPdfUpload(\''+mid+'\')" title="Update PDF">↻</button>'
      : '<button class="pdf-btn" onclick="event.stopPropagation();openPdfUpload(\''+mid+'\')" title="Add PDF/Brochure" style="background:rgba(201,162,39,.1);border-color:rgba(201,162,39,.4);color:#8a6a00">+ PDF</button>';
    return '<div class="pr' + (added ? ' iq' : '') + '" onclick="detail(\'' + mid + '\')">'
      + '<div><div class="pm">' + di + mid + ri + (hasPdf ? '<span class="pdf-badge">📄</span>' : '') + '</div><div class="pc">' + e(p.product_class||'') + '</div></div>'
      + '<div><div class="pt">' + e(p.title_only||p.model_id) + '</div><div class="pmf">' + e(mfr) + '</div></div>'
      + '<div class="pp"><span class="pv' + (p._priceOverride?' price-edited':'') + '" onclick="event.stopPropagation();openPriceEdit(\'' + mid + '\')" title="Click to adjust price for this quote">' + np + '</span></div>'
      + pdfLink
      + '<button class="pdf-btn" onclick="event.stopPropagation();openEditItem(\''+mid+'\')" title="Edit item details, required items &amp; recommended items" style="padding:1px 5px;font-size:.58rem;background:rgba(26,111,160,.08);border-color:rgba(26,111,160,.3);color:var(--bm)">✏</button>'
      + '<button class="pa' + (added ? ' added' : '') + '" data-mid="' + mid + '" title="' + (added ? 'Already in quote — click to add again' : 'Add to quote') + '">' + (added ? '✓ +' : '+ Add') + '</button>'
      + '</div>';
  }).join('');
  // Attach events to add buttons
  el.querySelectorAll('.pa').forEach(function(btn){
    btn.addEventListener('click', function(ev){
      ev.stopPropagation();
      var p = prodMap[this.dataset.mid];
      if(p) initiateAdd(p);
    });
  });
}

/* ── SECTIONS ── */
function addSection(name, restartNum){
  secCtr++;
  sections.push({id:'s'+secCtr, name:name||'Section '+secCtr, items:[], restartNum:!!restartNum});
  renderQuote();
}
function removeSection(sid){
  if(!confirm('Remove this section and all its items?')) return;
  sections = sections.filter(function(s){ return s.id !== sid; });
  renderQuote();
}
function updateSecName(sid, val){
  var s = sections.find(function(s){ return s.id === sid; });
  if(s) s.name = val;
  recalc();
}

/* ── ADD ITEM ── */
function initiateAdd(p){
  if(sections.length === 0){ addSection('Section 1'); }
  if(sections.length === 1){ addToSec(sections[0].id, p); return; }
  pendingProd = p;
  var list = document.getElementById('secList');
  list.innerHTML = sections.map(function(s){
    return '<div class="so" data-sid="' + s.id + '">'
      + '<span style="flex:1">' + e(s.name) + '</span>'
      + '<span style="font-size:.62rem;color:var(--sub)">' + s.items.length + ' items</span>'
      + '</div>';
  }).join('');
  list.querySelectorAll('.so').forEach(function(el){
    el.addEventListener('click', function(){ pickSection(this.dataset.sid); });
  });
  document.getElementById('secModal').classList.add('open');
}
function pickSection(sid){
  closeSecModal();
  if(pendingProd){ addToSec(sid, pendingProd); pendingProd = null; }
}
function closeSecModal(){ document.getElementById('secModal').classList.remove('open'); }

/* Strip voltage/electrical suffix (-XEF, -XAD etc.) to get base model for matching */
function baseModelId(mid){
  return String(mid||'').replace(/-X[A-Z]{2,3}$/i,'').trim();
}

function addToSec(sid, p){
  var sec = sections.find(function(s){ return s.id === sid; });
  if(!sec) return;
  // Always add a new line — the same item can appear multiple times in a section
  // (e.g. 17539 oscilloscope needed by both 85-MT2C and 990-ELE1)

  var reqs = p.requires_models || [];
  var recs = p.recommended_models || [];
  var autoParent = null;

  // Filter out voltage/electrical specs that were incorrectly parsed as model IDs
  // Valid model IDs: alphanumeric+dash, must contain letters, not pure voltage/freq specs
  function isValidModelId(id){
    if(!id || typeof id !== 'string') return false;
    var s = id.trim().toUpperCase();
    if(s.length < 2) return false;
    // Block electrical/voltage/frequency specs — digits optionally with dashes/slashes, ending in a unit
    // Handles: 220V, 380V, 100-240V, 50HZ, 60HZ, 50-60HZ, 50/60HZ, 2KW, 5KVA, 1PH, 3PHASE, 15A, 500W
    // Also handles combined: 100-240V/50-60HZ, 220V/60HZ
    if(/^\d[\d\/\-]*(V|HZ|KW|KVA|PH|PHASE|A|W)(\/\d[\d\/\-]*(V|HZ|KW|KVA|PH|PHASE|A|W))*$/.test(s)) return false;
    return true;
  }
  reqs = reqs.filter(isValidModelId);
  recs = recs.filter(isValidModelId);

  // Items are always added as standalone — user manually assigns parents via "Make sub-item of" dropdown
  // (Auto-link was removed: it incorrectly attached items to the wrong Learning System parent)

  sec.items.push({product:p, qty:1, isSubOf:null, subPricing:'included', isOptional:false, lid:Date.now().toString(36)+Math.random().toString(36).slice(2,6)});
  _scrollAfterRender = true;
  // If we don't know this item's PDF status yet, check now
  if(!(p.model_id in docMap)){
    apiPost({action:'check_documents', ids:[p.model_id]}).then(function(dr){
      if(dr.ok && dr.has_doc && dr.has_doc.length){
        dr.has_doc.forEach(function(mid){ docMap[mid]=true; });
        renderQuote();
      }
    }).catch(function(){});
  }
  renderQuote();
  // Mark just this item as added in the search list — no full re-fetch
  markAdded(p.model_id);

  // After required items handled, check recommended
  var afterRequired = function(){
    if(recs.length){
      var missingRecs = recs.filter(function(id){
        return !allItems().find(function(l){ return l.product.model_id===id||baseModelId(l.product.model_id)===id; });
      });
      if(missingRecs.length) checkRecommendedItems(p.model_id, missingRecs, sid);
    }
  };

  // Check for required accessories not already attached to THIS parent in THIS section.
  // An item that exists elsewhere (e.g. as sub of a different parent) still needs to be
  // offered so it can be added as a sub-item of THIS parent too.
  if(reqs.length){
    var sec2 = sections.find(function(s){ return s.id===sid; });
    var missing = reqs.filter(function(id){
      // Only skip if already sub-item of this exact parent in this section
      if(sec2 && sec2.items.find(function(l){
        return (l.product.model_id===id||baseModelId(l.product.model_id)===id) && l.isSubOf===p.model_id;
      })) return false;
      return true; // offer it — even if it exists elsewhere in the quote
    });
    if(missing.length){
      checkRequiredItems(p.model_id, missing, sid, afterRequired);
    } else {
      afterRequired();
    }
  } else {
    afterRequired();
  }
}
function allItems(){ return sections.reduce(function(a,s){ return a.concat(s.items); }, []); }

/* ── MOVE ITEM UP / DOWN ── */
function moveItem(sid, lid, dir){
  // dir: -1 = up, +1 = down
  var sec = sections.find(function(s){ return s.id===sid; });
  if(!sec) return;
  var idx = sec.items.findIndex(function(l){ return (l.lid||l.product.model_id)===lid; });
  if(idx<0) return;
  var swapIdx = idx + dir;
  if(swapIdx<0 || swapIdx>=sec.items.length) return;
  // Swap the items
  var tmp = sec.items[idx];
  sec.items[idx] = sec.items[swapIdx];
  sec.items[swapIdx] = tmp;
  _scrollAfterRender = false;
  renderQuote();
}

/* ── MAKE PARENT OF ── */
var _mkpSid = null, _mkpParentMid = null;

function openMakeParentOf(sid, parentMid){
  _mkpSid = sid;
  _mkpParentMid = parentMid;
  var sec = sections.find(function(s){ return s.id===sid; });
  if(!sec) return;

  var parentLine = sec.items.find(function(l){ return l.product.model_id===parentMid; });
  document.getElementById('mkpParentLabel').textContent =
    parentMid + (parentLine && parentLine.product.title_only ? ' — ' + parentLine.product.title_only.slice(0,40) : '');

  // Build checklist of all standalone items (not already sub-items, not synthetic, not the parent itself)
  var candidates = sec.items.filter(function(l){
    return !l.isSubOf && !l.product.isSynthetic && l.product.model_id !== parentMid;
  });

  if(!candidates.length){
    document.getElementById('mkpItemList').innerHTML =
      '<div style="text-align:center;padding:20px;font-size:.72rem;color:var(--sub)">No other standalone items in this section.</div>';
  } else {
    document.getElementById('mkpItemList').innerHTML = candidates.map(function(l){
      var price = l.product.intl_dist_net ? '$'+fmt(parseFloat(l.product.intl_dist_net)) : '—';
      return '<div style="display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid var(--bdr);border-radius:7px;margin-bottom:5px">'        +'<input type="checkbox" class="mkp-chk" value="'+e(l.product.model_id)+'" checked>'        +'<div style="flex:1">'          +'<div style="font-size:.72rem;font-weight:700;color:#7c4ad4">'+e(l.product.model_id)+'</div>'          +'<div style="font-size:.68rem;color:var(--tx)">'+e(l.product.title_only||'')+'</div>'          +'<div style="font-size:.62rem;color:var(--sub)">'+e(l.product.product_class||'')+'</div>'        +'</div>'        +'<div style="font-size:.7rem;font-weight:600;color:var(--bd)">'+price+'</div>'        +'</div>';
    }).join('');
  }
  document.getElementById('mkpModal').classList.add('open');
}

function mkpSelectAll(on){
  document.querySelectorAll('#mkpItemList .mkp-chk').forEach(function(cb){ cb.checked=on; });
}

function confirmMakeParentOf(){
  var checked = document.querySelectorAll('#mkpItemList .mkp-chk:checked');
  var selectedMids = Array.from(checked).map(function(cb){ return cb.value; });
  if(!selectedMids.length){ closeMkpModal(); return; }

  var sec = sections.find(function(s){ return s.id===_mkpSid; });
  if(!sec){ closeMkpModal(); return; }

  // Step 1: Assign isSubOf on the selected items
  sec.items.forEach(function(line){
    if(selectedMids.indexOf(line.product.model_id) >= 0 && !line.isSubOf){
      line.isSubOf    = _mkpParentMid;
      line.subPricing = 'included';
    }
  });

  // Step 2: Reorder items so the parent sits immediately before its new children
  // Find the earliest position among all newly assigned children (and their existing sub-items)
  var childSet = new Set(selectedMids);
  var earliestChildIdx = sec.items.length;
  sec.items.forEach(function(line, idx){
    if(childSet.has(line.product.model_id)){
      if(idx < earliestChildIdx) earliestChildIdx = idx;
    }
  });

  // Find and extract the parent item from its current position
  var parentIdx = sec.items.findIndex(function(l){ return l.product.model_id === _mkpParentMid; });
  if(parentIdx < 0){ closeMkpModal(); renderQuote(); return; }
  var parentLine = sec.items.splice(parentIdx, 1)[0];

  // After removal the earliest child index may shift by 1 if parent was before it
  if(parentIdx < earliestChildIdx) earliestChildIdx--;

  // Step 3: Also collect any existing sub-items of this parent that need to follow it
  // Extract them too so we can reinsert as a complete group
  var existingSubs = [];
  sec.items = sec.items.filter(function(line){
    if(line.isSubOf === _mkpParentMid && selectedMids.indexOf(line.product.model_id) < 0){
      // This was already a sub-item before (not one we just assigned) — collect it
      existingSubs.push(line);
      return false;
    }
    return true;
  });
  // Adjust earliestChildIdx for removed existing subs that were before it
  // (they were already sub-items so they would have been before the children in many cases;
  //  simplest approach: just find earliest child position again after all extractions)
  earliestChildIdx = sec.items.length;
  sec.items.forEach(function(line, idx){
    if(childSet.has(line.product.model_id)){
      if(idx < earliestChildIdx) earliestChildIdx = idx;
    }
  });

  // Step 4: Also extract the newly assigned children and any sub-items they already had,
  // so we can insert them in a clean group: [parent, existingSubs..., child1, child1subs..., child2, child2subs...]
  var childGroup = [];
  var usedMids = new Set();
  // Rebuild the order: preserve original relative order of selected children
  // by walking sec.items and pulling out children + their existing sub-items
  var remaining = [];
  sec.items.forEach(function(line){
    if(childSet.has(line.product.model_id)){
      childGroup.push(line);
      usedMids.add(line.product.model_id);
    } else if(childGroup.length && line.isSubOf && usedMids.has(line.isSubOf)){
      // sub-item of one of the new children — attach it too
      childGroup.push(line);
    } else {
      remaining.push(line);
    }
  });

  // Step 5: Find insertion point in remaining (items that are NOT the parent or children)
  // Insert at the earliest former child position (now in remaining array)
  // The cleanest approach: find the first item in remaining that originally came after
  // the earliest child. Since remaining is already stripped of children, we insert at
  // the index where children used to be — which is now the first gap.
  // Simple: insert the group at earliestChildIdx clamped to remaining.length
  var insertAt = Math.min(earliestChildIdx, remaining.length);

  // But we want the parent group to go where the FIRST child was originally.
  // Since we stripped children from remaining, we need to find the right spot.
  // Walk remaining and find the first item whose original index was > earliestChildIdx.
  // Actually simplest: just insert at insertAt which is already correct.

  var group = [parentLine].concat(existingSubs).concat(childGroup);
  Array.prototype.splice.apply(remaining, [insertAt, 0].concat(group));
  sec.items = remaining;

  closeMkpModal();
  renderQuote();
}

function closeMkpModal(){
  document.getElementById('mkpModal').classList.remove('open');
  _mkpSid = null; _mkpParentMid = null;
}

/* ── ADD REQUIRED ITEM MODAL ── */
var _reqSid=null, _reqLid=null, _reqParentMid=null, _reqSearchTimer=null;

function openReqModal(sid, lid, parentMid){
  _reqSid = sid;
  _reqLid = lid;
  _reqParentMid = parentMid;
  document.getElementById('reqModalParentLabel').textContent = parentMid;
  document.getElementById('reqSearchInput').value = '';
  document.getElementById('reqResults').innerHTML = '<p class="req-none">Type to search the catalog…</p>';
  document.getElementById('reqModal').classList.add('open');
  setTimeout(function(){ document.getElementById('reqSearchInput').focus(); }, 80);
}

function closeReqModal(){
  document.getElementById('reqModal').classList.remove('open');
  _reqSid = _reqLid = _reqParentMid = null;
}

function reqSearch(q){
  clearTimeout(_reqSearchTimer);
  q = q.trim();
  if(!q){ document.getElementById('reqResults').innerHTML = '<p class="req-none">Type to search the catalog…</p>'; return; }
  if(q.length < 2) return;
  document.getElementById('reqResults').innerHTML = '<p class="req-none">Searching…</p>';
  _reqSearchTimer = setTimeout(function(){
    apiPost({action:'search', q:q, limit:12}).then(function(r){
      var products = (r.ok && r.products) ? r.products : [];
      if(!products.length){
        document.getElementById('reqResults').innerHTML = '<p class="req-none">No products found for "'+e(q)+'"</p>';
        return;
      }
      var html = products.map(function(p){
        var price = p.intl_dist_net ? '$'+fmt(parseFloat(p.intl_dist_net)) : (p.intl_market_price_note||'—');
        return '<div class="req-result" onclick="addReqItem('+JSON.stringify(p)+')">'          +'<div class="rm">'+e(p.model_id)+'</div>'          +'<div class="rt">'+e(p.title_only||'')+'<div class="rc">'+e(p.product_class||'')+(p.manufacturer?' · '+e(p.manufacturer):'')+'</div></div>'          +'<div class="rp">'+price+'</div>'          +'</div>';
      }).join('');
      document.getElementById('reqResults').innerHTML = html;
    }).catch(function(){ document.getElementById('reqResults').innerHTML = '<p class="req-none">Search failed — please try again</p>'; });
  }, 280);
}

function addReqItem(p){
  var sec = sections.find(function(s){ return s.id===_reqSid; });
  if(!sec){ closeReqModal(); return; }
  var parentMid = _reqParentMid;
  var sid = _reqSid;
  closeReqModal();

  // Check if already a sub-item of this parent — increment qty if so
  var existing = sec.items.find(function(l){
    return l.product.model_id===p.model_id && l.isSubOf===parentMid;
  });
  if(existing){
    existing.qty = (existing.qty||1) + 1;
    renderQuote();
    return;
  }

  // Add the item as a sub-item of the parent, then run the full
  // required/recommended dep chain exactly like addToSec does
  sec.items.push({
    product:    p,
    qty:        1,
    isSubOf:    parentMid,
    subPricing: 'included',
    isOptional: false,
    divisorKey: null,
    lid:        Date.now().toString(36)+Math.random().toString(36).slice(2,6)
  });
  _scrollAfterRender = true;
  renderQuote();
  markAdded(p.model_id);

  // Run the same dep/rec chain as addToSec
  var reqs = (p.requires_models||[]).filter(function(id){ return id && typeof id==='string' && id.trim().length>=2; });
  var recs = (p.recommended_models||[]).filter(function(id){ return id && typeof id==='string' && id.trim().length>=2; });

  var afterRequired = function(){
    if(recs.length){
      var missingRecs = recs.filter(function(id){
        return !allItems().find(function(l){ return l.product.model_id===id||baseModelId(l.product.model_id)===id; });
      });
      if(missingRecs.length) checkRecommendedItems(parentMid, missingRecs, sid);
    }
  };

  if(reqs.length){
    var sec2 = sections.find(function(s){ return s.id===sid; });
    var missing = reqs.filter(function(id){
      if(sec2 && sec2.items.find(function(l){
        return (l.product.model_id===id||baseModelId(l.product.model_id)===id) && l.isSubOf===parentMid;
      })) return false;
      return true;
    });
    if(missing.length) checkRequiredItems(parentMid, missing, sid, afterRequired);
    else afterRequired();
  } else {
    afterRequired();
  }
}

// Close on backdrop click
document.addEventListener('click', function(e){
  var modal = document.getElementById('reqModal');
  if(modal && modal.classList.contains('open') && e.target===modal) closeReqModal();
});

/* ── REQUIRED ITEMS — Smart dependency logic ── */
var pendingDepParent = null;
var pendingDepCallback = null;

function checkRequiredItems(pid, missingIds, secId, callback){
  pendingDepCallback = callback || null;
  apiPost({action:'get_required', ids:missingIds}).then(function(r){
    var products = r.ok ? r.products : missingIds.map(function(id){ return {model_id:id,title_only:'Unknown',product_class:''}; });
    // KEY RULE: Only offer items that are NOT Learning Systems.
    var accessories = products.filter(function(p){
      return (p.product_class||'').toLowerCase().indexOf('learning system') === -1;
    });
    if(!accessories.length){
      // Nothing to offer — fire callback immediately
      var cb = pendingDepCallback; pendingDepCallback = null;
      if(cb) cb();
      return;
    }
    pendingDeps = accessories;
    pendingDepSec = secId;
    pendingDepParent = pid;
    showDepModal(pid, accessories);
  });
}

function showDepModal(pid, items){
  document.getElementById('depModalParent').textContent = pid;
  var html = items.map(function(p){
    var price = p.intl_dist_net ? '$'+fmt(parseFloat(p.intl_dist_net)) : 'See Amatrol';
    return '<div class="dep-item">'
      +'<input type="checkbox" class="dep-chk" value="'+e(p.model_id)+'" checked>'
      +'<div class="dep-item-info">'
      +'<div class="dep-mid">'+e(p.model_id)+'</div>'
      +'<div class="dep-title">'+e(p.title_only||'')+'</div>'
      +'<div class="dep-class">'+e(p.product_class||'')+(p.mfr_lead_time?' · '+e(p.mfr_lead_time):'')+'</div>'
      +'</div>'
      +'<div class="dep-price">'+price+'</div>'
      +'</div>';
  }).join('');
  document.getElementById('depModalItems').innerHTML = html;
  document.getElementById('depModal').classList.add('open');
}

function addSelectedDeps(){
  var checked = document.querySelectorAll('#depModalItems .dep-chk:checked');
  var selectedIds = Array.from(checked).map(function(cb){ return cb.value; });
  if(!selectedIds.length){ closeDepModal(); return; }
  var sid = pendingDepSec || (sections.length ? sections[sections.length-1].id : null);
  var sec = sections.find(function(s){ return s.id===sid; });
  if(sec && pendingDepParent){
    selectedIds.forEach(function(mid){
      var prod = pendingDeps.find(function(p){ return p.model_id===mid; });
      if(prod){
        // Only skip if this exact item is ALREADY a sub-item of THIS parent in THIS section.
        // Allow adding it as a sub-item of a DIFFERENT parent (e.g. 17539 under 990-ELE1
        // even if it's already under 85-MT2C elsewhere).
        var alreadyUnderThisParent = sec.items.find(function(l){
          return l.product.model_id===mid && l.isSubOf===pendingDepParent;
        });
        if(!alreadyUnderThisParent){
          sec.items.push({product:prod, qty:1, isSubOf:pendingDepParent, subPricing:'included', lid:Date.now().toString(36)+Math.random().toString(36).slice(2,6)});
        }
      }
    });
  }
  var cb = pendingDepCallback; pendingDepCallback = null;
  _scrollAfterRender = true;
  renderQuote(); cache={}; search();
  document.getElementById('depModal').classList.remove('open');
  pendingDeps=[]; pendingDepSec=null; pendingDepParent=null;
  if(cb) cb();
}

function depSelectAll(on){
  document.querySelectorAll('#depModalItems .dep-chk').forEach(function(cb){ cb.checked=on; });
}

function closeDepModal(){
  document.getElementById('depModal').classList.remove('open');
  pendingDeps=[]; pendingDepSec=null; pendingDepParent=null;
  var cb = pendingDepCallback; pendingDepCallback = null;
  if(cb) cb();
}

/* ── RECOMMENDED ITEMS ── */
var pendingRecs = [], pendingRecSec = null, pendingRecParent = null;

function checkRecommendedItems(pid, recIds, secId){
  apiPost({action:'get_recommended', ids:recIds}).then(function(r){
    var products = r.ok ? r.products : recIds.map(function(id){ return {model_id:id,title_only:'Unknown',product_class:''}; });
    var recItems = products.filter(function(p){
      return (p.product_class||'').toLowerCase().indexOf('learning system') === -1;
    });
    if(!recItems.length) return;
    pendingRecs = recItems;
    pendingRecSec = secId;
    pendingRecParent = pid;
    showRecModal(pid, recItems);
  });
}

function showRecModal(pid, items){
  document.getElementById('recModalParent').textContent = pid;
  var html = items.map(function(p){
    var price = p.intl_dist_net ? '$'+fmt(parseFloat(p.intl_dist_net)) : 'See Amatrol';
    return '<div class="rec-item">'
      +'<input type="checkbox" class="rec-chk" value="'+e(p.model_id)+'">'
      +'<div class="dep-item-info">'
      +'<div class="dep-mid">'+e(p.model_id)+'</div>'
      +'<div class="dep-title">'+e(p.title_only||'')+'</div>'
      +'<div class="dep-class">'+e(p.product_class||'')+(p.mfr_lead_time?' · '+e(p.mfr_lead_time):'')+'</div>'
      +'</div>'
      +'<div class="dep-price">'+price+'</div>'
      +'</div>';
  }).join('');
  document.getElementById('recModalItems').innerHTML = html;
  document.getElementById('recModal').classList.add('open');
}

function addSelectedRecs(){
  var checked = document.querySelectorAll('#recModalItems .rec-chk:checked');
  var selectedIds = Array.from(checked).map(function(cb){ return cb.value; });
  if(!selectedIds.length){ closeRecModal(); return; }
  var sid = pendingRecSec || (sections.length ? sections[sections.length-1].id : null);
  var sec = sections.find(function(s){ return s.id===sid; });
  if(sec){
    selectedIds.forEach(function(mid){
      var prod = pendingRecs.find(function(p){ return p.model_id===mid; });
      if(prod && !allItems().find(function(l){ return l.product.model_id===mid; })){
        // Recommended items added as standalone optional items (user can manually make sub-items)
        sec.items.push({product:prod, qty:1, isSubOf:null, subPricing:'included', isOptional:true, lid:Date.now().toString(36)+Math.random().toString(36).slice(2,6)});
      }
    });
  }
  _scrollAfterRender = true;
  renderQuote(); cache={}; search();
  closeRecModal();
}

function recSelectAll(on){
  document.querySelectorAll('#recModalItems .rec-chk').forEach(function(cb){ cb.checked=on; });
}

function closeRecModal(){
  document.getElementById('recModal').classList.remove('open');
  pendingRecs=[]; pendingRecSec=null; pendingRecParent=null;
}

/* ── RFQ COMPARISON DOCUMENT + REQUIRED TENDER DOCUMENTS (persistent per quote number) ── */
window._rfqDocPath = '';
window._rfqDocId   = 0;
window._tenderDocs = [];   // [{id, path, filename}]

function _curQuoteNum(){
  var el = document.getElementById('qn');
  return el ? el.value.trim() : '';
}

function uploadRfqDoc(inp){
  var f = inp.files[0];
  if(!f) return;
  var fd = new FormData();
  fd.append('rfq_doc', f);
  fd.append('kind', 'rfq');
  fd.append('quote_num', _curQuoteNum());
  var btn = inp.previousElementSibling;
  var orig = btn ? btn.textContent : '';
  if(btn){ btn.textContent='Uploading…'; btn.disabled=true; }
  fetch('api.php?action=upload_rfq_doc', {method:'POST', body:fd})
    .then(function(r){ return r.json(); })
    .then(function(r){
      if(btn){ btn.textContent=orig; btn.disabled=false; }
      if(r.ok){
        window._rfqDocPath = r.path;
        window._rfqDocId   = r.id || 0;
        document.getElementById('rfqDocStatus').textContent = '📄 '+f.name;
        document.getElementById('rfqDocStatus').style.color = 'var(--teal)';
        document.getElementById('rfqDocClear').style.display = '';
      } else {
        alert('RFQ upload failed: '+(r.error||''));
      }
    }).catch(function(e){
      if(btn){ btn.textContent=orig; btn.disabled=false; }
      alert('Upload error: '+e.message);
    });
  inp.value = '';
}
function clearRfqDoc(){
  if(window._rfqDocId){
    fetch('api.php?action=delete_quote_attachment', {method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id:window._rfqDocId})}).catch(function(){});
  }
  window._rfqDocPath = '';
  window._rfqDocId   = 0;
  document.getElementById('rfqDocStatus').textContent = 'No document uploaded';
  document.getElementById('rfqDocStatus').style.color = 'var(--sub)';
  document.getElementById('rfqDocClear').style.display = 'none';
}

function uploadTenderDocs(inp){
  var files = Array.from(inp.files||[]);
  if(!files.length) return;
  var btn = inp.previousElementSibling;
  var orig = btn ? btn.textContent : '';
  if(btn){ btn.textContent='Uploading…'; btn.disabled=true; }
  var done = 0, errs = [];
  files.forEach(function(f){
    var fd = new FormData();
    fd.append('rfq_doc', f);
    fd.append('kind', 'tender');
    fd.append('quote_num', _curQuoteNum());
    fetch('api.php?action=upload_rfq_doc', {method:'POST', body:fd})
      .then(function(r){ return r.json(); })
      .then(function(r){
        if(r.ok){ window._tenderDocs.push({id:r.id||0, path:r.path, filename:f.name}); }
        else { errs.push(f.name+': '+(r.error||'failed')); }
      })
      .catch(function(e){ errs.push(f.name+': '+e.message); })
      .finally(function(){
        done++;
        if(done === files.length){
          if(btn){ btn.textContent=orig; btn.disabled=false; }
          renderTenderDocList();
          if(errs.length) alert('Some uploads failed:\n'+errs.join('\n'));
        }
      });
  });
  inp.value = '';
}
function renderTenderDocList(){
  var list = document.getElementById('tenderDocList');
  var status = document.getElementById('tenderDocStatus');
  if(!list || !status) return;
  if(!window._tenderDocs.length){
    list.style.display = 'none'; list.innerHTML = '';
    status.textContent = 'No documents uploaded';
    status.style.color = 'var(--sub)';
    return;
  }
  status.textContent = window._tenderDocs.length + ' document' + (window._tenderDocs.length>1?'s':'') + ' saved with this quote';
  status.style.color = 'var(--teal)';
  list.style.display = 'flex';
  list.innerHTML = window._tenderDocs.map(function(t, i){
    return '<div style="display:flex;align-items:center;gap:6px;font-size:.68rem;background:var(--bgl,#f4f9fc);border:1px solid var(--bdr);border-radius:4px;padding:4px 8px">'
      + '<span style="color:var(--teal)">📄</span>'
      + '<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="'+t.filename.replace(/"/g,'&quot;')+'">'+t.filename.replace(/</g,'&lt;')+'</span>'
      + '<button onclick="removeTenderDoc('+i+')" style="background:none;border:none;color:#c04040;cursor:pointer;font-size:.7rem;padding:0">✕</button>'
      + '</div>';
  }).join('');
}
function removeTenderDoc(idx){
  var t = window._tenderDocs[idx];
  if(!t) return;
  if(t.id){
    fetch('api.php?action=delete_quote_attachment', {method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id:t.id})}).catch(function(){});
  }
  window._tenderDocs.splice(idx,1);
  renderTenderDocList();
}

function loadQuoteAttachments(callback){
  var qnum = _curQuoteNum();
  // Reset UI state first
  window._rfqDocPath=''; window._rfqDocId=0; window._tenderDocs=[];
  if(!qnum){ _applyAttachmentsUI(); if(typeof callback==='function')callback(); return; }
  fetch('api.php?action=list_quote_attachments&quote_num='+encodeURIComponent(qnum))
    .then(function(r){ return r.json(); })
    .then(function(r){
      if(r.ok){
        (r.attachments||[]).forEach(function(a){
          if(a.kind==='rfq'){ window._rfqDocPath=a.path; window._rfqDocId=a.id; }
          else if(a.kind==='tender'){ window._tenderDocs.push({id:a.id, path:a.path, filename:a.filename}); }
        });
      }
      _applyAttachmentsUI();
      if(typeof callback==='function')callback();
    })
    .catch(function(){ _applyAttachmentsUI(); if(typeof callback==='function')callback(); });
}
function _applyAttachmentsUI(){
  var st = document.getElementById('rfqDocStatus');
  var cl = document.getElementById('rfqDocClear');
  if(st && cl){
    if(window._rfqDocPath){
      st.textContent = '📄 Saved with this quote';
      st.style.color = 'var(--teal)';
      cl.style.display = '';
    } else {
      st.textContent = 'No document uploaded';
      st.style.color = 'var(--sub)';
      cl.style.display = 'none';
    }
  }
  renderTenderDocList();
}

/* ── PROPOSAL LOGO UPLOAD ── */
var savedProposalLogos = {};

function uploadProposalLogo(input){
  var file = input.files[0]; if(!file) return;
  // Ask for a friendly name — defaults to the original filename without extension
  var defaultName = file.name.replace(/\.[^.]+$/, '').replace(/[-_]/g,' ');
  var friendlyName = prompt('Enter a name for this logo (so you can find it easily next time):', defaultName);
  if(friendlyName === null) return; // user cancelled
  friendlyName = friendlyName.trim() || defaultName;
  var fd = new FormData();
  fd.append('logo', file);
  // Use friendly name as client_id (sanitised) so the file is saved with that name
  var safeName = friendlyName.replace(/[^A-Za-z0-9 _-]/g,'').trim().replace(/\s+/g,'_').substring(0,40);
  fd.append('client_id', safeName || ('upload_'+Date.now()));
  fd.append('friendly_name', friendlyName);
  fetch('api.php?action=upload_proposal_logo', {method:'POST', body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(!d.ok){ alert('Upload error: '+(d.error||'?')); return; }
      // Use friendly name as display label
      var displayName = friendlyName || d.filename;
      var sel = document.getElementById('proposalLogoSelect');
      var existing = Array.from(sel.options).find(function(o){return o.value===d.path;});
      if(!existing){
        var o=document.createElement('option'); o.value=d.path; o.textContent=displayName;
        sel.appendChild(o);
      } else { existing.textContent = displayName; }
      sel.value = d.path;
      showLogoPreview(d.path, displayName);
      localStorage.setItem('ti_last_logo_path', d.path);
      localStorage.setItem('ti_last_logo_name', displayName);
      localStorage.setItem('ti_logo_name_'+d.path, displayName);
    }).catch(function(e){ alert('Upload failed: '+e.message); });
}

function showLogoPreview(path, name){
  var prev=document.getElementById('logoPreview');
  var img=document.getElementById('logoPreviewImg');
  var lbl=document.getElementById('logoPreviewName');
  if(!prev) return;
  img.src=path; lbl.textContent=name; prev.style.display='block';
}

/* ══════════════════════════════════════════════════════════════
   VENDOR IMPORT MODULE
   Three-step wizard: Select vendor → Parse & review → Import
   ══════════════════════════════════════════════════════════════ */
var _vi = {
  vendor:      null,   // selected vendor key
  file:        null,   // selected File object
  parsedData:  null,   // result from parse_vendor_doc
  mode:        null,   // 'catalog'|'quote'|'both'
  itemActions: {},     // {model_id: 'add'|'skip'|'use_db'|'overwrite'}
  customNums:  {},     // {model_id: custom_display_number}
};

function openVendorImportModal(){
  _vi = { vendor:null, file:null, parsedData:null, mode:null, itemActions:{}, customNums:{} };
  viResetToStep1();
  document.getElementById('vendorImportModal').classList.add('open');
}
function closeVendorImportModal(){
  document.getElementById('vendorImportModal').classList.remove('open');
}

function viResetToStep1(){
  document.getElementById('viStep1').style.display    = '';
  document.getElementById('viStep2').style.display    = 'none';
  document.getElementById('viStep3').style.display    = 'none';
  document.getElementById('viParseBar').style.display = '';
  document.getElementById('viFileLabel').textContent  = '';
  document.getElementById('viParseStatus').textContent= 'Select a vendor and upload a file to begin.';
  document.getElementById('viParseBtn').disabled      = true;
  document.getElementById('viRunBtn').disabled        = true;
  document.getElementById('viRunStatus').textContent  = '';
  document.getElementById('viStepIndicator').textContent = 'STEP 1 OF 3 — SELECT VENDOR & FILE';
  // Clear vendor card selection
  document.querySelectorAll('.vi-vendor-card').forEach(function(c){ c.classList.remove('selected'); });
  document.querySelectorAll('.vi-mode-opt').forEach(function(c){ c.classList.remove('selected'); });
  document.getElementById('viFileInput').value = '';
  _vi.file = null;
  _vi.vendor = null;
}

function viBackToStep1(){
  document.getElementById('viStep2').style.display    = 'none';
  document.getElementById('viStep3').style.display    = 'none';
  document.getElementById('viStep1').style.display    = '';
  document.getElementById('viParseBar').style.display = '';
  document.getElementById('viStepIndicator').textContent = 'STEP 1 OF 3 — SELECT VENDOR & FILE';
}

function selectVendorCard(key){
  _vi.vendor = key;
  document.querySelectorAll('.vi-vendor-card').forEach(function(c){ c.classList.remove('selected'); });
  var card = document.getElementById('viCard_' + key);
  if(card) card.classList.add('selected');
  viCheckReady();
}

function viFileChosen(input){
  _vi.file = input.files[0] || null;
  document.getElementById('viFileLabel').textContent = _vi.file ? ('📎 ' + _vi.file.name) : '';
  viCheckReady();
}
function viHandleDrop(e){
  e.preventDefault();
  document.getElementById('viDropZone').style.borderColor = 'var(--bdr)';
  var f = e.dataTransfer.files[0];
  if(!f) return;
  _vi.file = f;
  document.getElementById('viFileLabel').textContent = '📎 ' + f.name;
  // Try to auto-detect vendor from filename
  var fn = f.name.toLowerCase();
  if(!_vi.vendor){
    if(fn.indexOf('amatrol')>=0||fn.match(/intl\d{4,}/i)) selectVendorCard('amatrol');
    else if(fn.match(/\d{4}hks/i)||fn.indexOf('lucas')>=0||fn.indexOf('ln')>=0) selectVendorCard('lucas_nuelle');
    else if(fn.indexOf('rfq')>=0||fn.indexOf('aramco')>=0||fn.indexOf('spec')>=0) selectVendorCard('aramco_spec');
  }
  viCheckReady();
}

function viCheckReady(){
  var ready = _vi.vendor && _vi.file;
  document.getElementById('viParseBtn').disabled = !ready;
  if(ready) document.getElementById('viParseStatus').textContent = 'Ready to parse. Click Parse Document.';
}

function viModeChanged(radio){
  _vi.mode = radio.value;
  document.querySelectorAll('.vi-mode-opt').forEach(function(el){ el.classList.remove('selected'); });
  var lbl = radio.closest('label'); if(lbl) lbl.classList.add('selected');
  document.getElementById('viRunBtn').disabled = false;
}

/* ── STEP 2: Parse document ─────────────────────────────────── */
function viParseDoc(){
  if(!_vi.vendor || !_vi.file){ alert('Select a vendor and file first.'); return; }
  var btn = document.getElementById('viParseBtn');
  btn.disabled = true;
  document.getElementById('viParseStatus').textContent = '⏳ Parsing document…';

  var fd = new FormData();
  fd.append('action', 'parse_vendor_doc');
  fd.append('vendor', _vi.vendor);
  fd.append('file',   _vi.file, _vi.file.name);
  var clientName = document.getElementById('viClientName').value.trim();
  var country    = document.getElementById('viCountry').value.trim();
  if(clientName) fd.append('client_name', clientName);
  if(country)    fd.append('country', country);

  fetch('vendor_import.php?action=parse_vendor_doc', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
      btn.disabled = false;
      if(!d.ok){ document.getElementById('viParseStatus').textContent = '✗ ' + (d.error||'Parse failed'); return; }
      _vi.parsedData = d;
      // Merge client/country from meta if not overridden
      if(!clientName && d.meta && d.meta.end_user) document.getElementById('viClientName').value = d.meta.end_user;
      if(!country    && d.meta && d.meta.country)  document.getElementById('viCountry').value    = d.meta.country;
      viShowStep2(d);
    })
    .catch(function(err){
      btn.disabled = false;
      document.getElementById('viParseStatus').textContent = '✗ Error: ' + err.message;
    });
}

function viShowStep2(data){
  document.getElementById('viStep1').style.display    = 'none';
  document.getElementById('viStep2').style.display    = '';
  document.getElementById('viStep3').style.display    = '';
  document.getElementById('viParseBar').style.display = 'none';
  document.getElementById('viStepIndicator').textContent = 'STEP 2 OF 3 — REVIEW & STEP 3 — CHOOSE MODE';

  var s = data.summary || {};
  document.getElementById('viSum_new').textContent      = '● ' + (s.new||0)      + ' New';
  document.getElementById('viSum_match').textContent    = '● ' + (s.match||0)    + ' Match';
  document.getElementById('viSum_conflict').textContent = '● ' + (s.conflict||0) + ' Conflict';
  document.getElementById('viSum_nomodel').textContent  = (s.no_model||0)        + ' No model#';
  document.getElementById('viSum_total').textContent    = (data.items||[]).length + ' items total';

  viRenderTable(data.items || []);
}

function viRenderTable(items){
  var tbody = document.getElementById('viItemBody');
  _vi.itemActions = {};
  _vi.customNums  = {};

  tbody.innerHTML = items.map(function(it, idx){
    var st     = it.import_status || 'new';
    var mid    = it.model_id || '—';
    var title  = e(it.title_only || '');
    var dbT    = it.db_record ? e(it.db_record.title_only || '') : '<span style="color:#aaa;font-style:italic">—</span>';
    var qty    = it.qty || 1;
    var price  = it.vendor_price ? ((it.vendor_currency||'$')==='USD'?'$':it.vendor_currency+' ') + parseFloat(it.vendor_price).toFixed(2) : '—';
    var specNum= it.spec_item_num || '';

    // Default action per status
    var defAction = st === 'conflict' ? 'use_db' : (st === 'match' ? 'skip' : 'add');
    _vi.itemActions[mid] = defAction;
    if(specNum) _vi.customNums[mid] = specNum;

    var stClass = 'vi-status-' + st;
    var stLabel = st === 'new' ? '⊕ New' : st === 'match' ? '✓ Match' : st === 'conflict' ? '⚠ Conflict' : '— No #';

    // Conflict tooltip
    var conflictBadge = '';
    if(st === 'conflict' && it.conflict_fields && it.conflict_fields.length){
      var tip = it.conflict_fields.map(function(cf){
        return cf.field + ': DB has "' + (cf.database||'').substring(0,60) + '"';
      }).join('; ');
      conflictBadge = ' <span class="vi-conflict-badge" title="'+e(tip)+'">?</span>';
    }

    // Action select
    var actionOpts = '';
    if(st === 'new')      actionOpts = '<option value="add" selected>Add to DB</option><option value="skip">Skip</option>';
    else if(st==='match') actionOpts = '<option value="skip" selected>Keep (no change)</option><option value="overwrite">Overwrite DB</option>';
    else if(st==='conflict') actionOpts = '<option value="use_db" selected>Use DB version</option><option value="overwrite">Use import version</option><option value="skip">Skip entirely</option>';
    else                  actionOpts = '<option value="skip" selected>Skip</option>';

    return '<tr data-mid="'+e(mid)+'" data-status="'+st+'">'
      + '<td><span class="'+stClass+'">'+stLabel+'</span>'+conflictBadge+'</td>'
      + '<td style="font-weight:700;color:var(--bm);font-size:.68rem">'+e(mid)+'</td>'
      + '<td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="'+title+'">'+title+'</td>'
      + '<td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--sub)" title="">'+dbT+'</td>'
      + '<td style="text-align:right">'+qty+'</td>'
      + '<td style="text-align:right;font-weight:700">'+price+'</td>'
      + '<td><select class="vi-action-sel" data-mid="'+e(mid)+'" onchange="_vi.itemActions[this.dataset.mid]=this.value">'+actionOpts+'</select></td>'
      + '<td><input class="vi-custom-num" data-mid="'+e(mid)+'" type="text" value="'+e(specNum)+'" placeholder="—" oninput="_vi.customNums[this.dataset.mid]=this.value" title="Custom item number for this proposal"></td>'
      + '</tr>';
  }).join('');

  // Bind select change
  tbody.querySelectorAll('.vi-action-sel').forEach(function(sel){
    sel.addEventListener('change', function(){ _vi.itemActions[this.dataset.mid] = this.value; });
  });
}

function viFilterItems(q){
  var rows = document.querySelectorAll('#viItemBody tr');
  q = q.toLowerCase();
  rows.forEach(function(row){
    var text = row.textContent.toLowerCase();
    row.style.display = (!q || text.indexOf(q)>=0) ? '' : 'none';
  });
}

/* ── STEP 3: Run import ─────────────────────────────────────── */
function viRunImport(){
  if(!_vi.mode){ alert('Choose an import mode first.'); return; }
  if(!_vi.parsedData){ alert('No parsed data available.'); return; }

  var btn = document.getElementById('viRunBtn');
  btn.disabled = true;
  document.getElementById('viRunStatus').textContent = '⏳ Running import…';

  var data = _vi.parsedData;
  var clientName = document.getElementById('viClientName').value.trim();
  var country    = document.getElementById('viCountry').value.trim();

  // Annotate items with chosen actions and custom numbers
  var annotatedItems = (data.items || []).map(function(it){
    var mid = it.model_id || '';
    return Object.assign({}, it, {
      import_action: _vi.itemActions[mid] || 'add',
      custom_num:    _vi.customNums[mid]  || null,
    });
  });

  var payload = {
    vendor_key:   _vi.vendor,
    vendor_name:  data.vendor || _vi.vendor,
    currency:     data.currency || 'USD',
    client_name:  clientName || (data.meta && data.meta.end_user) || '',
    country:      country    || (data.meta && data.meta.country)  || '',
    meta:         data.meta  || {},
    items:        annotatedItems,
    sections:     data.sections || [],
    custom_item_nums: _vi.customNums,
  };

  var actionMap = { catalog:'import_to_catalog', quote:'import_to_quote', both:'import_both' };
  var endpoint  = actionMap[_vi.mode] || 'import_to_catalog';

  fetch('vendor_import.php?action=' + endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  .then(function(r){ return r.json(); })
  .then(function(d){
    btn.disabled = false;
    if(!d.ok){ document.getElementById('viRunStatus').textContent = '✗ ' + (d.error||'Import failed'); return; }

    var msg = '';
    if(_vi.mode === 'catalog' || _vi.mode === 'both'){
      var cat = d.catalog || d;
      msg += '✓ Catalog: ' + (cat.added||0) + ' added, ' + (cat.updated||0) + ' updated, ' + (cat.skipped||0) + ' skipped. ';
    }
    if(_vi.mode === 'quote' || _vi.mode === 'both'){
      var qt = d.quote || d;
      msg += '✓ Quote ' + (qt.quote_number||'') + ' created (id ' + (qt.id||'?') + '). ';
    }
    document.getElementById('viRunStatus').textContent = msg;

    // If a quote was created, load it in the quote builder
    var qt2 = d.quote || (_vi.mode === 'quote' ? d : null);
    if(qt2 && qt2.id){
      if(confirm('Import complete! Load the new quote "' + (qt2.quote_number||'') + '" now?')){
        closeVendorImportModal();
        loadQuote(qt2.id);
      }
    } else {
      // Just reload product catalog if catalog was updated
      if(_vi.mode === 'catalog' || _vi.mode === 'both') loadCatalog();
      setTimeout(function(){ if(confirm('Import complete. Close this dialog?')) closeVendorImportModal(); }, 400);
    }
  })
  .catch(function(err){
    btn.disabled = false;
    document.getElementById('viRunStatus').textContent = '✗ Error: ' + err.message;
  });
}

/* ── ITEM NUMBER CUSTOMIZATION (for any open quote) ─────────── */
// Opens a compact modal to re-number items in the current quote
function openItemNumberModal(){
  if(!sections.length){ alert('No items in quote.'); return; }
  var html = '<div style="max-width:460px"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px"><h3 style="font-size:.85rem;color:var(--bd)">🔢 Customize Item Numbers</h3><button class="btn bo bsm" onclick="closeItemNumModal()">✕</button></div>';
  html += '<p style="font-size:.68rem;color:var(--sub);margin-bottom:10px">Edit the display number for each item. Leave blank to use auto-numbering. Changes apply to all exports (PDF, XLSX).</p>';
  html += '<div style="max-height:360px;overflow-y:auto">';
  var allIt = allItems();
  allIt.forEach(function(line){
    var mid     = line.product.model_id;
    var title   = line.product.title_only || mid;
    var cur     = line.custom_num || line.spec_item_num || '';
    html += '<div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #eef4f9">'
      + '<input type="text" class="vi-custom-num" data-mid="'+e(mid)+'" data-lid="'+(line.lid||'')+'" value="'+e(cur)+'" placeholder="auto" style="width:70px">'
      + '<span style="font-size:.68rem;font-weight:700;color:var(--bm);min-width:80px">'+e(mid)+'</span>'
      + '<span style="font-size:.7rem;color:var(--tx);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1" title="'+e(title)+'">'+e(title)+'</span>'
      + '</div>';
  });
  html += '</div>';
  html += '<div style="margin-top:12px;display:flex;gap:8px"><button class="btn bg bsm" onclick="saveItemNumbers()">💾 Save Numbers</button><button class="btn bo bsm" onclick="closeItemNumModal()">Cancel</button></div></div>';
  document.getElementById('detBox').innerHTML = html;
  document.getElementById('detModal').classList.add('open');
}

function closeItemNumModal(){ document.getElementById('detModal').classList.remove('open'); }

function saveItemNumbers(){
  var inputs  = document.querySelectorAll('#detBox .vi-custom-num');
  var numMap  = {};
  inputs.forEach(function(inp){
    var mid = inp.dataset.mid;
    var lid = inp.dataset.lid;
    var val = inp.value.trim();
    if(mid) numMap[mid] = val;
    if(lid) numMap[lid] = val;
    // Also update in-memory sections
    sections.forEach(function(sec){
      sec.items.forEach(function(line){
        if((line.product.model_id===mid)||(line.lid===lid)){ line.custom_num = val || null; }
      });
    });
  });
  renderQuote(); recalc();
  closeItemNumModal();
  toast('Item numbers saved.');
}



/* ── IMPORT (JSON + XLSX) ── */
var importData = null;

function openImportModal(){ document.getElementById('importModal').classList.add('open'); }
function closeImportModal(){
  document.getElementById('importModal').classList.remove('open');
  importData=null;
  document.getElementById('importPreview').innerHTML='';
  document.getElementById('importPreview').style.display='none';
  document.getElementById('importRunBtn').style.display='none';
  document.getElementById('importStatus').textContent='';
  document.getElementById('importFile').value='';
}

/* Load SheetJS from CDN on demand */
function loadSheetJS(cb){
  if(window.XLSX){ cb(); return; }
  var s=document.createElement('script');
  s.src='https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
  s.onload=cb;
  document.head.appendChild(s);
}

/* Parse a single XLSX/XLS ArrayBuffer → quote object */
function parseXlsxToQuote(ab, filename){
  var wb=XLSX.read(ab,{type:'array'});
  var ws=wb.Sheets[wb.SheetNames[0]];
  var all=XLSX.utils.sheet_to_json(ws,{header:1,defval:''});

  var qnum='',client='',country='',date='',incoterm='';
  var items=[];

  // Scan header rows (first 15) for metadata
  for(var i=0;i<Math.min(15,all.length);i++){
    var row=all[i].map(function(c){return String(c||'').trim();});
    var rowStr=row.join(' ').toUpperCase();

    // CLIENT NAME: <value>
    if(rowStr.indexOf('CLIENT NAME')>=0){
      for(var j=0;j<row.length;j++){
        if(row[j].toUpperCase().indexOf('CLIENT NAME')>=0 && row[j+1]) client=row[j+1]; break;
      }
      // Also check for merged — value in next non-empty cell
      if(!client){var flat=row.join(' ');var m=flat.match(/CLIENT NAME[:\s]+([^C]+?)(?:COUNTRY|QUOTATION|$)/i);if(m)client=m[1].trim();}
    }
    if(rowStr.indexOf('COUNTRY')>=0 && rowStr.indexOf('CLIENT')<0){
      for(var j=0;j<row.length;j++){
        if(row[j].toUpperCase().indexOf('COUNTRY')>=0 && row[j+1]){country=row[j+1];break;}
      }
    }
    if(rowStr.indexOf('QUOTATION NUMBER')>=0||rowStr.indexOf('QUOTE #')>=0){
      for(var j=0;j<row.length;j++){
        if((row[j].toUpperCase().indexOf('QUOTATION NUMBER')>=0||row[j].toUpperCase().indexOf('QUOTE #')>=0) && row[j+1]){qnum=row[j+1];break;}
      }
    }
    if(rowStr.indexOf('DATE')>=0 && !date){
      for(var j=0;j<row.length;j++){
        if(row[j].toUpperCase()==='DATE:' && row[j+1]){date=row[j+1];break;}
      }
    }
    if(rowStr.indexOf('INCOTERM')>=0){
      for(var j=0;j<row.length;j++){
        if(row[j].toUpperCase().indexOf('INCOTERM')>=0 && row[j+1]){incoterm=row[j+1];break;}
      }
    }
  }

  // If no quote number found, derive from filename
  if(!qnum){
    var fm=filename.replace(/\.xlsx?$/i,'').replace(/_+/g,' ');
    var nm=fm.match(/(INTL[-\s]?\d{4}[-\s]?\d+[-\w]*)/i);
    qnum=nm?nm[1].replace(/\s/g,'-'):fm.substring(0,40);
  }

  // Find the column header row (contains "Model Number" or "MODEL NUMBER")
  var headerRow=-1, colMap={pkg:-1,item:-1,model:-1,desc:-1,qty:-1,unit:-1,total:-1};
  for(var i=0;i<all.length;i++){
    var r=all[i].map(function(c){return String(c||'').toUpperCase().replace(/\n.*$/,'').trim();});
    if(r.join('').indexOf('MODEL NUMBER')>=0||r.join('').indexOf('MODEL ID')>=0){
      headerRow=i;
      for(var j=0;j<r.length;j++){
        if(r[j].indexOf('PACKAGE')>=0)     colMap.pkg=j;
        else if(r[j].indexOf('ITEM')>=0)   colMap.item=j;
        else if(r[j].indexOf('MODEL')>=0)  colMap.model=j;
        else if(r[j].indexOf('DESC')>=0)   colMap.desc=j;
        else if(r[j]==='QTY'||r[j].indexOf('QUANTITY')>=0) colMap.qty=j;
        else if(r[j].indexOf('UNIT')>=0)   colMap.unit=j;
        else if(r[j].indexOf('TOTAL')>=0)  colMap.total=j;
      }
      break;
    }
  }

  // Read item rows
  if(headerRow>=0){
    for(var i=headerRow+1;i<all.length;i++){
      var row=all[i];
      var modelId=String(row[colMap.model>=0?colMap.model:2]||'').trim();
      var desc=String(row[colMap.desc>=0?colMap.desc:3]||'').trim();
      // Skip blank rows, section headers, total rows
      if(!modelId||modelId.toUpperCase().indexOf('TOTAL')>=0) continue;
      if(modelId.toUpperCase().indexOf('SECTION')>=0) continue;
      if(modelId.toUpperCase().indexOf('SHIPPING')>=0) continue;
      if(modelId.toUpperCase().indexOf('GRAND')>=0) continue;
      if(modelId==='LEGACY') continue;
      var qty=parseInt(row[colMap.qty>=0?colMap.qty:4])||1;
      var unitPrice=parseFloat(String(row[colMap.unit>=0?colMap.unit:5]).replace(/[,$]/g,''))||null;
      items.push({model_id:modelId,title_only:desc,qty:qty,unit_price:unitPrice,isSubOf:null,subPricing:'included'});
    }
  }

  return {
    quoteNum: qnum.trim(),
    clientName: client.trim(),
    country: country.trim(),
    date: date.trim()||new Date().toISOString().split('T')[0],
    incoterm: incoterm.trim(),
    equipment: items.map(function(it){return it.model_id;}).join(', '),
    currency: 'USD',
    status: 'Imported',
    revisions: [],
    _items: items  // carry full items for richer import
  };
}

document.addEventListener('DOMContentLoaded',function(){
  document.getElementById('importFile').addEventListener('change',function(){
    var files=Array.from(this.files); if(!files.length)return;
    importData=[];
    var prev=document.getElementById('importPreview');
    prev.innerHTML='<i style="color:var(--sub)">Reading files…</i>';
    prev.style.display='block';

    function processNext(idx){
      if(idx>=files.length){
        // All done — show preview
        if(!importData.length){
          prev.innerHTML='<span style="color:#c04040">No valid quotes found in the selected files.</span>';
          document.getElementById('importRunBtn').style.display='none';
          return;
        }
        var html='<b style="color:var(--bd)">'+importData.length+' quote'+(importData.length>1?'s':'')+' ready to import:</b><br><br>';
        importData.forEach(function(q,i){
          html+='<span style="color:var(--bm);font-weight:700">'+e(q.quoteNum||'?')+'</span>'
              +' — '+e(q.clientName||'(no client)')
              +(q.country?' <span style="color:var(--sub)">('+e(q.country)+')</span>':'')
              +(q._items&&q._items.length?' · <b>'+q._items.length+' item'+(q._items.length>1?'s':'')+'</b>':'')
              +'<br>';
        });
        prev.innerHTML=html;
        var btn=document.getElementById('importRunBtn');
        btn.style.display='';
        btn.textContent='⬆ Import '+importData.length+' Quote'+(importData.length>1?'s':'');
        return;
      }

      var file=files[idx];
      var ext=file.name.split('.').pop().toLowerCase();

      if(ext==='json'){
        var reader=new FileReader();
        reader.onload=function(ev){
          try{
            var parsed=JSON.parse(ev.target.result);
            if(Array.isArray(parsed)) importData=importData.concat(parsed);
            else if(parsed.quoteNum) importData.push(parsed);
          }catch(err){
            prev.innerHTML='<span style="color:#c04040">'+e(file.name)+': Invalid JSON — '+e(err.message)+'</span>';
          }
          processNext(idx+1);
        };
        reader.readAsText(file);
      } else if(ext==='xlsx'||ext==='xls'){
        loadSheetJS(function(){
          var reader=new FileReader();
          reader.onload=function(ev){
            try{
              var q=parseXlsxToQuote(ev.target.result,file.name);
              if(q.quoteNum) importData.push(q);
            }catch(err){
              console.warn('Could not parse',file.name,err);
            }
            processNext(idx+1);
          };
          reader.readAsArrayBuffer(file);
        });
      } else {
        processNext(idx+1);
      }
    }
    processNext(0);
  });
});

function runImport(){
  if(!importData||!importData.length){alert('No data to import.');return;}
  var btn=document.getElementById('importRunBtn');
  btn.disabled=true; btn.textContent='Importing…';
  document.getElementById('importStatus').textContent='';

  // Build payload — convert _items to sections format
  var payload = importData.map(function(q){
    var items=(q._items||[]).map(function(it){
      return {
        model_id:    it.model_id,
        title_only:  it.title_only||it.model_id,
        intl_dist_net: null,
        qty:         it.qty||1,
        isSubOf:     it.isSubOf||null,
        subPricing:  it.subPricing||'included',
        manufacturer:'', product_class:'', key_topic:'',
        intl_market_price_note:null,requires_models:[],mfr_lead_time:''
      };
    });
    var sections = items.length
      ? [{id:'s1',name:'Equipment',items:items}]
      : (q.equipment ? [{id:'s1',name:'Equipment',items:[{
            model_id:'LEGACY',title_only:q.equipment,intl_dist_net:null,
            qty:1,isSubOf:null,subPricing:'included',manufacturer:'',
            product_class:'',key_topic:'',intl_market_price_note:null,
            requires_models:[],mfr_lead_time:''
          }]}] : []);
    return {
      quoteNum:   q.quoteNum,
      clientName: q.clientName||'',
      country:    q.country||'',
      date:       q.date||new Date().toISOString().split('T')[0],
      currency:   q.currency||'USD',
      incoterm:   q.incoterm||'',
      equipment:  q.equipment||'',
      status:     q.status||'Imported',
      revisions:  q.revisions||[],
      sections:   sections
    };
  });

  apiPost({action:'import_projects',projects:payload}).then(function(r){
    if(!r.ok){alert('Import error: '+(r.error||'Unknown'));return;}
    document.getElementById('importStatus').textContent = '✓ Done: '+r.imported+' imported, '+r.skipped+' skipped';
    loadQuoteList(false);
    setTimeout(closeImportModal, 1800);
  }).catch(function(err){alert('Error: '+err.message);})
  .finally(function(){ btn.disabled=false; });
}

/* ── RENDER QUOTE ── */
var _scrollAfterRender = false;
function renderQuote(){
  var body = document.getElementById('qbody');
  if(!sections.length){ body.innerHTML='<div class="eq">📋<div>Click "+ Section" to start building your quote</div></div>'; recalc(); return; }
  var div = getDiv(), disc = getDisc();
  var html = '';
  var globalMi = 0; // continuous item counter across all sections
  sections.forEach(function(sec){
    // Reset item counter if this section is set to restart numbering
    if(sec.restartNum) globalMi = 0;
    var secTot = secTotal(sec, div, disc);
    html += '<div class="sb">'
      + '<div class="sh">'
      + '<input type="text" value="' + e(sec.name) + '" placeholder="Section Name…" data-sid="' + sec.id + '">'
      + '<button class="sec-restart-btn'+(sec.restartNum?' on':'')+'" data-sid="' + sec.id + '" title="'+(sec.restartNum?'Numbering restarts at 1 for this section (click to continue from previous)':'Continue numbering from previous section (click to restart at 1)')+'">'+(sec.restartNum?'↺ #1':'↺')+'</button>'
      + '<span class="st">' + fmtC(secTot) + '</span>'
      + '<button class="sd" data-sid="' + sec.id + '" title="Remove section">✕</button>'
      + '</div><div class="si2">';
    if(!sec.items.length){
      html += '<div style="padding:8px;font-size:.7rem;color:var(--sub);text-align:center">No items yet</div>';
    } else {
      var mi = 0, sc2 = {};
      // Pass 1: assign _mi to all main (standalone) items so sub-items can reference them
      sec.items.forEach(function(line){
        if(!line.isSubOf){ mi++; globalMi++; line._mi=globalMi; sc2[globalMi]=0; }
      });
      mi = 0; // reset local counter (used below for display only)
      sec.items.forEach(function(line){
        var p = line.product, isMain = !line.isSubOf, ns;
        if(isMain){ ns=line._mi+'.0'; }
        else{
          var pl = sec.items.find(function(l){ return l.product.model_id===line.isSubOf; });
          var pi = pl && pl._mi ? pl._mi : null;
          if(pi !== null){ sc2[pi] = (sc2[pi]||0)+1; ns = pi+'.'+sc2[pi]; }
          else { ns = '?.'+( (sc2['?']=(sc2['?']||0)+1) ); } // orphan — parent not found
        }
        var net = p.intl_dist_net ? parseFloat(p.intl_dist_net) : null;
        var sell = net ? (net*(1-disc)/lineDiv(line,sec)) : null;
        var isIncl = line.isSubOf && line.subPricing==='included';
        var isOpt  = line.isOptional || false;
        var parentNum = '';
        if(line.isSubOf){
          var pLine = sec.items.find(function(l){ return l.product.model_id===line.isSubOf; });
          if(pLine && pLine._mi) parentNum = 'Item '+pLine._mi+'.0';
          else parentNum = 'Item '+line.isSubOf;
        }
        // For main items: fold included sub-item costs into unit sell price
        // Works even when parent has no price (e.g. custom bundle parent TI-TT-SM1)
        var sellPerUnit = sell || 0;
        var hasIncludedSubs = false;
        if(!line.isSubOf){
          sec.items.forEach(function(sub){
            if(sub.isSubOf===line.product.model_id && sub.subPricing==='included' && !sub.isOptional){
              var sn=sub.product.intl_dist_net?parseFloat(sub.product.intl_dist_net):null;
              if(sn){sellPerUnit+=sn*(1-disc)/lineDiv(sub,sec)*(sub.qty||1);hasIncludedSubs=true;}
            }
          });
        }
        var tot = (!isIncl && !isOpt && sellPerUnit) ? sellPerUnit*line.qty : null;
        // Only count in total if not included sub-item
        var reqM = (p.requires_models||[]).filter(function(id){
          if(!id || typeof id !== 'string') return false;
          var s = id.trim().toUpperCase();
          if(s.length < 2) return false;
          if(/^\d[\d\/\-]*(V|HZ|KW|KVA|PH|PHASE|A|W)(\/\d[\d\/\-]*(V|HZ|KW|KVA|PH|PHASE|A|W))*$/.test(s)) return false;
          return !allItems().find(function(l){ return l.product.model_id===id||baseModelId(l.product.model_id)===id; });
        });
        var nsDisplay = isOpt ? ('<span style="color:#c06000;font-weight:700">'+ns+'-OPT</span>') : ns;
        var qHasPdf = docMap[p.model_id]||false;
        // Synthetic parent: editable title, derived price from sub-items
        if(p.isSynthetic){
          var synthSubs=sec.items.filter(function(s){return s.isSubOf===p.model_id&&!s.isOptional;});
          var synthUnit=0;
          synthSubs.forEach(function(s){var sn=s.product.intl_dist_net?parseFloat(s.product.intl_dist_net):null;if(sn)synthUnit+=sn*(1-getDisc())/itemDiv(s)*(s.qty||1);});
          var synthTot=synthUnit*(line.qty||1);
          // Move buttons for synthetic parent
          var _synthIdx = sec.items.findIndex(function(l){return (l.lid||l.product.model_id)===(line.lid||p.model_id);});
          var _synthMvHtml = '<div class="mv-wrap">'
            + (_synthIdx>0?'<button class="mv-btn mv-up" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" title="Move up">&#9650;</button>':'<span style="display:block;height:13px"></span>')
            + (_synthIdx<sec.items.length-1?'<button class="mv-btn mv-dn" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" title="Move down">&#9660;</button>':'<span style="display:block;height:13px"></span>')
            + '</div>';
          html+='<div class="qi synth">';
          html+=_synthMvHtml;
          html+='<div style="display:flex;flex-direction:column;gap:1px">'            +'<input class="qn2-input" type="text" value="'+e(line.custom_num||ns)+'" placeholder="'+e(ns)+'" title="Click to edit item number" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" data-auto="'+e(ns)+'" style="width:72px;font-size:.7rem;font-weight:700;color:var(--bd);text-align:center;border:1px solid transparent;background:transparent;padding:1px 2px;border-radius:3px;cursor:text;">'            +'<div class="qm" style="color:var(--bm);font-size:.6rem">SYSTEM</div></div>';
          html+='<div><input class="synth-title-input" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" value="'+e(p.title_only||p.model_id)+'" placeholder="System name…"><br><input class="synth-desc-input" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" value="'+e(p.description||'')+'" placeholder="Description (optional)…"></div>';
          html+='<div class="qq"><input type="number" min="1" value="'+(line.qty||1)+'" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'"></div>';
          html+='<div class="qc"><div style="font-size:.62rem;color:var(--sub)">Per system</div><div style="font-size:.72rem">Unit: $'+fmt(synthUnit)+'</div><div style="font-size:.59rem;color:var(--teal)">('+synthSubs.length+' items)</div></div>';
          html+='<div class="qs" style="color:var(--bm);font-weight:800">$'+fmt(synthTot)+'</div>';
          html+='<button class="qd" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'">&#215;</button>';
          if(divisors.length>1){html+='<div class="qso" style="border-top:1px solid var(--bdr);padding-top:3px"><label style="font-size:.6rem;color:var(--sub)">Divisor:</label><select class="div-sel div-item-sel" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'">';divisors.forEach(function(dv){var sel=(line.divisorKey===dv.key)||(!line.divisorKey&&dv.key==='D1');html+='<option value="'+e(dv.key)+'"'+(sel?' selected':'')+'>'+e(dv.name||dv.key)+' ÷'+dv.value+'</option>';});html+='</select></div>';}
          html+='</div>';
          return;
        }

        // Build move-up/move-down buttons
        var canMoveUp = false, canMoveDown = false;
        var _myIdx = sec.items.findIndex(function(l){return (l.lid||l.product.model_id)===(line.lid||p.model_id);});
        if(_myIdx > 0) canMoveUp = true;
        if(_myIdx < sec.items.length-1) canMoveDown = true;
        var mvHtml = '<div class="mv-wrap">'
          + (canMoveUp?'<button class="mv-btn mv-up" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" title="Move up">&#9650;</button>':'<span style="display:block;height:13px"></span>')
          + (canMoveDown?'<button class="mv-btn mv-dn" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" title="Move down">&#9660;</button>':'<span style="display:block;height:13px"></span>')
          + '</div>';
        html += '<div class="qi' + (line.isSubOf?' sub':'') + (isOpt?' opt':'') + '">'
          + mvHtml
          + '<div style="display:flex;flex-direction:column;gap:1px">'          + '<input class="qn2-input" type="text" value="'+e(line.custom_num||ns)+'" placeholder="'+e(ns)+'" title="Click to edit item number" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" data-auto="'+e(ns)+'" style="width:72px;font-size:.7rem;font-weight:700;color:var(--bd);text-align:center;border:1px solid transparent;background:transparent;padding:1px 2px;border-radius:3px;cursor:text;">'          + '<div class="qm">'+e(p.model_id)+(qHasPdf
              ?'<a class="pdf-badge" href="api.php?action=get_document&mid='+encodeURIComponent(p.model_id)+'" target="_blank" onclick="event.stopPropagation()" title="View datasheet" style="margin-left:3px;text-decoration:none">📄</a>'
               +'<button class="pdf-btn" onclick="event.stopPropagation();openPdfUpload(\'' +e(p.model_id)+ '\')" title="Update PDF" style="margin-left:1px;padding:1px 4px;font-size:.58rem">&#8635;</button>'
              :'<button class="pdf-btn" onclick="event.stopPropagation();openPdfUpload(\'' +e(p.model_id)+ '\')" title="Add PDF/Brochure to this item" style="background:rgba(201,162,39,.1);border-color:rgba(201,162,39,.4);color:#8a6a00;padding:1px 5px;font-size:.58rem">+PDF</button>'
            )+'</div></div>'
          + '<div><div class="qtl">'+e(p.title_only||p.model_id)+'</div>'+(p.title_description?'<div style="font-size:.63rem;color:var(--sub);margin-top:2px;line-height:1.45;white-space:pre-wrap;max-height:3.2em;overflow:hidden">'+e(p.title_description)+'</div>':'')+(reqM.length&&!line.isSubOf?'<div class="qdw">⚠ Missing: '+reqM.map(e).join(', ')+'</div>':'')+(line.isSubOf&&!allItems().find(function(l){return l.product.model_id===line.isSubOf;})?'<div class="qdw" style="color:var(--teal)">↳ Sub of: '+e(line.isSubOf)+'</div>':'')+(isOpt?'<div style="font-size:.58rem;color:#c06000;margin-top:3px;line-height:1.4;font-style:italic;border-top:1px dashed rgba(192,96,0,.25);padding-top:3px">ITEM PRICED, WITH ZERO (0) QTY AND NOT INCLUDED IN THE ABOVE ITEM, IF YOU WISH TO ADD THIS ITEM THE PRICE IS DISPLAYED AND FOR AN UPDATED QUOTE WITH IT INCLUDED PLEASE CONTACT YOUR SALES REPRESENTATIVE</div>':'')   +'</div>'
          + '<div class="qq"><input type="number" min="'+(isOpt?'0':'1')+'" value="'+line.qty+'" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'"></div>'
          + '<div class="qc">'
            + (net?'<div style="font-size:.62rem;color:var(--sub)">Net: $'+fmt(net)+'</div>':'')
            + (isOpt&&sell?'<div style="font-size:.75rem;font-weight:700;color:#c06000">$'+fmt(sell||0)+'</div>':((!isIncl||isOpt)&&sellPerUnit&&!line.product.isSynthetic?'<div style="font-size:.72rem">Unit: $'+fmt(sellPerUnit||0)+'</div>':''))
            + (hasIncludedSubs?'<div style="font-size:.59rem;color:var(--teal)">(+ included sub-items)</div>':'')
            + (isIncl&&!isOpt&&parentNum?'<div style="font-size:.62rem;color:var(--teal)">Incl. in '+e(parentNum)+'</div>':'')

            + '</div>'
          + '<div class="qs'+(isIncl&&!isOpt?' inc':'')+'">'+( isIncl&&!isOpt ? ('Incl. in '+(parentNum||'parent')) : (isOpt ? '' : (tot?'$'+fmt(tot):(p.intl_market_price_note?'See Amatrol':'N/A'))))+'</div>'
          + '<button class="qd" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'">×</button>';
        // Bottom control row for standalone items
        if(!line.isSubOf){
          if(!line.product.isSynthetic){
            // Controls row: Divisor selector + Sub of selector
            html += '<div class="qso" style="border-top:1px solid var(--bdr);padding-top:3px;flex-wrap:wrap;gap:4px">';
            if(divisors.length > 1){
              html += '<label style="font-size:.6rem;color:var(--sub)">Divisor:</label>';
              html += '<select class="div-sel div-item-sel" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'">';
              divisors.forEach(function(dv){
                var sel=(line.divisorKey===dv.key)||(!line.divisorKey&&dv.key==='D1');
                html += '<option value="'+e(dv.key)+'"'+(sel?' selected':'')+'>'+e(dv.name||dv.key)+' ÷'+dv.value+'</option>';
              });
              html += '</select>';
            }
            var otherMains=sec.items.filter(function(l){return !l.isSubOf&&l.product.model_id!==p.model_id;});
            if(otherMains.length>0){
              html += '<label style="font-size:.62rem;color:var(--sub)">&#8627; Sub of:</label>';
              html += '<select class="par-sel" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" style="font-size:.65rem;padding:2px 4px;border-radius:4px;border:1px solid var(--bdr);background:var(--wh)">';
              html += '<option value="">&#8212; no parent &#8212;</option>';
              otherMains.forEach(function(l){ 
                var selected = (line.isSubOf===l.product.model_id) ? ' selected' : '';
                html += '<option value="'+e(l.product.model_id)+'"'+selected+'>'+e(l.product.model_id)+(l.product.title_only?' — '+e(l.product.title_only.slice(0,30)):'' )+'</option>'; 
              });
              html += '</select>';
            }
            // Create Parent always on its OWN row so it is never pushed off-screen
            html += '<button class="alb cpar-btn" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" style="font-size:.6rem;padding:2px 8px;border-color:var(--bm);color:var(--bm);margin-left:auto" title="Wrap this item in a new named parent system">&#8853; Create Parent</button>';
            // Only show Make Parent Of if there are other standalone items to assign
            var otherStandalone = sec.items.filter(function(l){ return !l.isSubOf && !l.product.isSynthetic && l.product.model_id!==p.model_id; });
            if(otherStandalone.length > 0){
              html += '<button class="alb mkp-btn" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" data-mid="'+e(p.model_id)+'" style="font-size:.6rem;padding:2px 8px;border-color:#7c4ad4;color:#7c4ad4" title="Make this item the parent of other items in this section">&#8615; Make Parent Of…</button>';
            }
            html += '<button class="alb req-add-btn" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" data-mid="'+e(p.model_id)+'" style="font-size:.6rem;padding:2px 8px;border-color:var(--teal);color:var(--teal)" title="Search and add a required accessory as a sub-item of this item">&#43; Add Required</button>';
            html += '<label style="margin-left:8px;font-size:.62rem;color:#c06000;display:flex;align-items:center;gap:3px" title="Mark as optional — item shows in proposal with qty 0, no extended price"><input type="checkbox" class="opt-chk" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'"'+(isOpt?' checked':'')+' style="accent-color:#c06000"> Optional</label>';
            html += '</div>';
          } else {
            // Synthetic parent: just divisor selector
            if(divisors.length > 1){
              html += '<div class="qso" style="border-top:1px solid var(--bdr);padding-top:3px">';
              html += '<label style="font-size:.6rem;color:var(--sub)">Divisor:</label>';
              html += '<select class="div-sel div-item-sel" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'">';
              divisors.forEach(function(dv){
                var sel=(line.divisorKey===dv.key)||(!line.divisorKey&&dv.key==='D1');
                html += '<option value="'+e(dv.key)+'"'+(sel?' selected':'')+'>'+e(dv.name||dv.key)+' ÷'+dv.value+'</option>';
              });
              html += '</select></div>';
            }
          }
        }
        if(line.isSubOf){
          // Build reassign dropdown — all standalone items in the section except this item
          var allMains2=sec.items.filter(function(l){return !l.isSubOf&&l.product.model_id!==p.model_id;});
          var reassignSel='';
          if(allMains2.length>0){
            reassignSel='<select class="par-sel" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" style="font-size:.65rem;padding:2px 4px;border-radius:4px;border:1px solid var(--bdr);background:var(--wh);max-width:200px">'
              +'<option value="">&mdash; no parent &mdash;</option>';
            allMains2.forEach(function(l){
              var isSel=(line.isSubOf===l.product.model_id)?' selected':'';
              reassignSel+='<option value="'+e(l.product.model_id)+'"'+isSel+'>'+e(l.product.model_id)+(l.product.title_only?' — '+e(l.product.title_only.slice(0,25)):'' )+'</option>';
            });
            reassignSel+='</select>';
          }
          html += '<div class="qso">'
            + '<label><input type="radio" name="sp_'+sec.id+'_'+e(p.model_id)+'" value="included"'+(line.subPricing==='included'?' checked':'')+' data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'"> Incl. in '+e(line.isSubOf)+'</label>'
            + '<label><input type="radio" name="sp_'+sec.id+'_'+e(p.model_id)+'" value="itemized"'+(line.subPricing==='itemized'?' checked':'')+' data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'"> Price separately</label>'
            + (reassignSel?'<label style="font-size:.6rem;color:var(--sub)">&#8627;</label>'+reassignSel:'')
            + '<button class="alb rem-parent" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'" style="margin-left:auto;font-size:.6rem;color:#c04040;border-color:#f0c0c0">✕ Remove parent</button>'
            + '<label style="margin-left:auto"><input type="checkbox" class="opt-chk" data-sid="'+sec.id+'" data-lid="'+(line.lid||e(p.model_id))+'"'+(isOpt?' checked':'')+'> Optional add-on</label>'
            + '</div>';
        }
        html += '</div>';
      });
    }
    html += '<button class="asi" data-focus="si">+ Search and add product to this section</button>';
    html += '</div></div>';
  });
  body.innerHTML = html;

  // Attach events
  body.querySelectorAll('.sh input[data-sid]').forEach(function(el){
    el.addEventListener('input', function(){ updateSecName(this.dataset.sid, this.value); });
  });
  body.querySelectorAll('.sd[data-sid]').forEach(function(el){
    el.addEventListener('click', function(){ removeSection(this.dataset.sid); });
  });
  body.querySelectorAll('.sec-restart-btn').forEach(function(el){
    el.addEventListener('click', function(){
      var sid=this.dataset.sid;
      var sec=sections.find(function(s){return s.id===sid;});
      if(sec){ sec.restartNum=!sec.restartNum; renderQuote(); }
    });
  });
  body.querySelectorAll('.mv-up').forEach(function(el){
    el.addEventListener('click', function(ev){
      ev.stopPropagation();
      moveItem(this.dataset.sid, this.dataset.lid, -1);
    });
  });
  body.querySelectorAll('.mv-dn').forEach(function(el){
    el.addEventListener('click', function(ev){
      ev.stopPropagation();
      moveItem(this.dataset.sid, this.dataset.lid, 1);
    });
  });
  body.querySelectorAll('.qq input').forEach(function(el){
    el.addEventListener('change', function(){ updateQty(this.dataset.sid, this.dataset.lid||this.dataset.mid, this.value); });
  });
  // Inline item number editing
  body.querySelectorAll('.qn2-input').forEach(function(el){
    el.addEventListener('focus', function(){ this.select(); });
    el.addEventListener('change', function(){
      var sid = this.dataset.sid, lid = this.dataset.lid;
      var val = this.value.trim(), autoNum = this.dataset.auto;
      sections.forEach(function(sec){
        if(sec.id !== sid) return;
        sec.items.forEach(function(line){
          if((line.lid||line.product.model_id) === lid){
            line.custom_num = (val && val !== autoNum) ? val : null;
          }
        });
      });
      markDirty(); recalc();
    });
    el.addEventListener('keydown', function(ev){
      if(ev.key === 'Enter'){ this.blur(); ev.preventDefault(); }
      if(ev.key === 'Escape'){ this.value = this.dataset.auto; this.blur(); ev.preventDefault(); }
    });
  });
  body.querySelectorAll('.qd[data-sid]').forEach(function(el){
    el.addEventListener('click', function(){ removeItem(this.dataset.sid, this.dataset.lid||this.dataset.mid); });
  });
  body.querySelectorAll('.qso input[type=radio]').forEach(function(el){
    el.addEventListener('change', function(){ setSubPricing(this.dataset.sid, this.dataset.lid||this.dataset.mid, this.value); });
  });
  body.querySelectorAll('.par-sel').forEach(function(el){
    el.addEventListener('change', function(){
      var parentMid = this.value;
      var sid=this.dataset.sid, lid=this.dataset.lid||this.dataset.mid;
      var sec=sections.find(function(s){return s.id===sid;});
      if(!sec)return;
      var lineIdx=sec.items.findIndex(function(l){return (l.lid||l.product.model_id)===lid;});
      if(lineIdx<0)return;
      var line=sec.items[lineIdx];
      line.isSubOf = parentMid||null;
      line.subPricing = 'included';
      // Physically move item to appear immediately after its new parent
      if(parentMid){
        var parentIdx=sec.items.findIndex(function(l){return l.product.model_id===parentMid;});
        if(parentIdx>=0){
          // Remove from current position
          sec.items.splice(lineIdx,1);
          // Find where to insert: after the parent and all its existing sub-items
          var insertAt = sec.items.findIndex(function(l){ return l.product.model_id===parentMid; }) + 1;
          // Skip over existing sub-items of this parent
          while(insertAt < sec.items.length && sec.items[insertAt].isSubOf===parentMid){ insertAt++; }
          sec.items.splice(insertAt,0,line);
        }
      }
      renderQuote(); cache={}; search();
    });
  });
  body.querySelectorAll('.opt-chk').forEach(function(el){
    el.addEventListener('change', function(){ setOptional(this.dataset.sid, this.dataset.lid||this.dataset.mid, this.checked); });
  });
  body.querySelectorAll('.rem-parent').forEach(function(el){
    el.addEventListener('click', function(){
      var sid=this.dataset.sid, lid=this.dataset.lid||this.dataset.mid;
      var sec=sections.find(function(s){return s.id===sid;});
      if(!sec)return;
      var line=sec.items.find(function(l){return (l.lid||l.product.model_id)===lid;});
      if(line){ line.isSubOf=null; line.subPricing='included'; renderQuote(); cache={}; search(); }
    });
  });
  // Create Parent button
  body.querySelectorAll('.mkp-btn').forEach(function(el){
    el.addEventListener('click', function(e){
      e.stopPropagation();
      openMakeParentOf(this.dataset.sid, this.dataset.mid);
    });
  });
  body.querySelectorAll('.req-add-btn').forEach(function(el){
    el.addEventListener('click', function(e){
      e.stopPropagation();
      openReqModal(this.dataset.sid, this.dataset.lid||this.dataset.mid, this.dataset.mid);
    });
  });
  body.querySelectorAll('.cpar-btn').forEach(function(el){
    el.addEventListener('click', function(ev){
      ev.stopPropagation();
      openCpar(this.dataset.sid, this.dataset.lid||this.dataset.mid);
    });
  });
  // Per-item divisor selector
  body.querySelectorAll('.div-item-sel').forEach(function(el){
    el.addEventListener('change', function(){
      var sec=sections.find(function(s){return s.id===this.dataset.sid;}.bind(this));
      if(!sec)return;
      var lid2=this.dataset.lid||this.dataset.mid; var line=sec.items.find(function(l){return (l.lid||l.product.model_id)===lid2;});
      if(line){ line.divisorKey=this.value; recalc(); }
    });
  });
  // Synthetic parent: editable title and description
  body.querySelectorAll('.synth-title-input').forEach(function(el){
    el.addEventListener('input', function(){
      var sec=sections.find(function(s){return s.id===this.dataset.sid;}.bind(this));
      if(!sec)return;
      var lid2=this.dataset.lid||this.dataset.mid; var line=sec.items.find(function(l){return (l.lid||l.product.model_id)===lid2;});
      if(line) line.product.title_only=this.value;
    });
  });
  body.querySelectorAll('.synth-desc-input').forEach(function(el){
    el.addEventListener('input', function(){
      var sec=sections.find(function(s){return s.id===this.dataset.sid;}.bind(this));
      if(!sec)return;
      var lid2=this.dataset.lid||this.dataset.mid; var line=sec.items.find(function(l){return (l.lid||l.product.model_id)===lid2;});
      if(line) line.product.description=this.value;
    });
  });

  body.querySelectorAll('.asi').forEach(function(el){
    el.addEventListener('click', function(){ document.getElementById('si').focus(); });
  });

  recalc();
  // Auto-scroll to bottom when item was just added
  if(_scrollAfterRender){ 
    _scrollAfterRender = false;
    // Double requestAnimationFrame ensures DOM paint is complete before scrolling
    requestAnimationFrame(function(){
      requestAnimationFrame(function(){
        var b = document.getElementById('qbody');
        if(b) b.scrollTop = b.scrollHeight + 9999;
      });
    });
  }
}

function secTotal(sec, div, disc){
  var total = 0;
  sec.items.forEach(function(line){
    if(line.isSubOf && line.subPricing==='included') return; // counted via parent
    if(line.isOptional) return;
    var p = line.product;
    var qty = line.qty || 1;

    // Synthetic parent: price = sum of direct sub extended prices × parent qty
    if(p.isSynthetic){
      var synthUnit=0;
      sec.items.forEach(function(s){
        if(s.isSubOf===p.model_id && !s.isOptional){
          var sn=s.product.intl_dist_net?parseFloat(s.product.intl_dist_net):null;
          if(sn) synthUnit += sn*(1-disc)/lineDiv(s,sec) * (s.qty||1);
        }
      });
      total += synthUnit * qty;
      return;
    }

    var net = p.intl_dist_net ? parseFloat(p.intl_dist_net) : null;
    var itemDivisor = lineDiv(line,sec);
    var sellPerUnit = net ? net*(1-disc)/itemDivisor : 0;
    // Add included sub-item costs per parent unit (works even when parent has no price)
    if(!line.isSubOf){
      sec.items.forEach(function(sub){
        if(sub.isSubOf===p.model_id && sub.subPricing==='included' && !sub.isOptional){
          var subNet=sub.product.intl_dist_net?parseFloat(sub.product.intl_dist_net):null;
          if(subNet) sellPerUnit += subNet*(1-disc)/lineDiv(sub,sec) * (sub.qty||1);
        }
      });
    }
    if(!sellPerUnit) return; // nothing to count
    total += sellPerUnit * qty;
  });
  return total;
}

/* ── ITEM MANIPULATION ── */
function removeItem(sid, lid){
  var sec = sections.find(function(s){ return s.id===sid; });
  if(!sec) return;
  // Find the line being removed to get its model_id (needed to remove its sub-items)
  var removed = sec.items.find(function(l){ return (l.lid||l.product.model_id)===lid; });
  var removedMid = removed ? removed.product.model_id : lid;
  var removedLid = removed ? (removed.lid||removedMid) : lid;
  sec.items = sec.items.filter(function(l){
    if((l.lid||l.product.model_id)===removedLid) return false; // remove the line
    if(l.isSubOf===removedMid && removed && !removed.isSubOf) return false; // remove its children if it was a standalone parent
    return true;
  });
  renderQuote(); cache={}; search();
}
function updateQty(sid, lid, val){
  var sec = sections.find(function(s){ return s.id===sid; });
  if(!sec) return;
  var l = sec.items.find(function(l){ return (l.lid||l.product.model_id)===lid; });
  if(l){ l.qty = Math.max(1, parseInt(val)||1); }
  recalc();
}
function setSubPricing(sid, lid, val){
  var sec = sections.find(function(s){ return s.id===sid; });
  if(!sec) return;
  var l = sec.items.find(function(l){ return (l.lid||l.product.model_id)===lid; });
  if(l){ l.subPricing = val; }
  recalc();
}
function setOptional(sid, lid, val){
  var sec = sections.find(function(s){ return s.id===sid; });
  if(!sec) return;
  var l = sec.items.find(function(l){ return (l.lid||l.product.model_id)===lid; });
  if(l){ l.isOptional = val; if(val) l.qty=0; else if(l.qty<1) l.qty=1; }
  renderQuote();
}
function clearQuote(){
  if(allItems().length && !confirm('Clear all sections and items?')) return;
  sections=[]; secCtr=0; renderQuote(); cache={}; search();
}

/* ── RECALC ── */
function recalc(){
  var div=getDiv(), disc=getDisc(), tNet=0, tSell=0, tItems=0;
  sections.forEach(function(sec){
    sec.items.forEach(function(line){
      tItems++;
      var net = line.product.intl_dist_net ? parseFloat(line.product.intl_dist_net) : null;
      if(!net) return;
      tNet += net * line.qty;
      if(!line.isSubOf || line.subPricing==='itemized')
        tSell += (net*(1-disc)/div)*line.qty;
    });
  });
  var margin = tSell - tNet;
  var mPct = tSell > 0 ? (margin/tSell*100) : 0;
  document.getElementById('tNet').textContent = fmtC(tNet);
  document.getElementById('tDisc').textContent = '-' + fmtC(tNet*disc);
  document.getElementById('tItems').textContent = tItems;
  document.getElementById('tMargin').textContent = fmtC(margin);
  document.getElementById('tMarginPct').textContent = mPct.toFixed(1)+'%';
  document.getElementById('tQuote').textContent = fmtC(tSell);
  updDP();
}

/* ── DETAIL MODAL ── */
function detail(mid){
  load(true);
  fetch(API + '?action=product&id=' + encodeURIComponent(mid))
    .then(function(r){ return r.json(); })
    .then(function(r){
      load(false);
      if(!r.ok) return;
      var p = r.product;
      var div = getDiv(), disc = getDisc();
      var net = p.intl_dist_net ? parseFloat(p.intl_dist_net) : null;
      var sell = net ? (net*(1-disc)/div) : null;
      var fields = [
        ['Model ID',p.model_id],['Manufacturer',p.manufacturer],['Product Class',p.product_class],
        ['Key Topic',p.key_topic],
        ['Net to Amatrol', net ? '$'+fmt(net) : (p.intl_market_price_note||'N/A')],
        ['Sell @ div '+div, sell ? '$'+fmt(sell) : 'N/A'],
        ['Lead Time',p.mfr_lead_time],['Dimensions',p.dimensions]
      ].filter(function(f){ return f[1]; });
      var reqFiltered = (p.requires_models||[]).filter(function(id){
        if(!id || typeof id !== 'string') return false;
        var s = id.trim().toUpperCase();
        if(s.length < 2) return false;
        if(/^\d[\d\/\-]*(V|HZ|KW|KVA|PH|PHASE|A|W)(\/\d[\d\/\-]*(V|HZ|KW|KVA|PH|PHASE|A|W))*$/.test(s)) return false;
        return true;
      });
      var reqs = reqFiltered.length ? '<div style="margin-top:10px;font-size:.71rem"><span style="color:#a06000">🔗 Requires:</span> '+reqFiltered.map(e).join(', ')+'</div>' : '';
      var desc = p.title_description ? '<div style="margin:10px 0;font-size:.72rem;color:var(--sub);line-height:1.55;border-top:1px solid var(--bdr);padding-top:10px;white-space:pre-wrap">'+e(p.title_description)+'</div>' : '';
      document.getElementById('detBox').innerHTML =
        '<div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">'
        + '<h3>'+e(p.model_id)+' — '+e(p.title_only||'')+'</h3>'
        + '<button class="btn bo bsm" id="mClose">✕</button></div>'
        + '<div class="mg">'+fields.map(function(f){ return '<div class="mrow"><span>'+f[0]+'</span><span>'+e(String(f[1]))+'</span></div>'; }).join('')+'</div>'
        + reqs + desc
        + '<div style="margin-top:12px;display:flex;gap:8px;align-items:center">'
        + '<button class="btn bg" id="mAdd">+ Add to Quote</button>'
        + '<a class="pdf-btn" id="mPdfBtn" href="api.php?action=get_document&mid='+e(p.model_id)+'" target="_blank" style="display:none;padding:6px 14px;font-size:.72rem">📄 Download Datasheet</a>'
        + '</div>';
      document.getElementById('mClose').addEventListener('click', closeModal);
      document.getElementById('mAdd').addEventListener('click', function(){
        closeModal();
        setTimeout(function(){ initiateAdd(p); }, 60);
      });
      // Add PDF button if datasheet exists
      apiPost({action:'doc_for_model',ids:[p.model_id]}).catch(function(){});
      fetch('api.php?action=doc_for_model&mid='+encodeURIComponent(p.model_id))
        .then(function(r){return r.json();})
        .then(function(dr){
          if(dr.ok){
            var pdfBtn = document.getElementById('mPdfBtn');
            if(pdfBtn) pdfBtn.style.display='inline-flex';
          }
        }).catch(function(){});
      document.getElementById('detModal').classList.add('open');
    }).catch(function(){ load(false); });
}
function closeModal(){ document.getElementById('detModal').classList.remove('open'); }

/* ── LOCATION MODAL ── */
function openLocModal(){ document.getElementById('locModal').classList.add('open'); document.getElementById('newLoc').focus(); }
function closeLocModal(){ document.getElementById('locModal').classList.remove('open'); }
function saveNewLoc(){
  var v = document.getElementById('newLoc').value.trim();
  if(!v){ alert('Enter a location.'); return; }
  var sel = document.getElementById('incoLocation');
  var o = document.createElement('option'); o.value=v; o.textContent=v;
  sel.appendChild(o); sel.value=v;
  closeLocModal(); document.getElementById('newLoc').value='';
}

/* ── SHIPPING ESTIMATES ── */
function addShipRow(desc, amt){
  shipEstimates.push({desc:desc||'',amt:amt||0});
  renderShipRows();
}
function renderShipRows(){
  var el = document.getElementById('shipRows');
  if(!shipEstimates.length){
    el.innerHTML = '<div style="font-size:.68rem;color:var(--sub);font-style:italic;padding:2px 0">No estimates added. Click + Add to add a shipping line.</div>';
    document.getElementById('shipTotal').textContent = '$0.00';
    return;
  }
  el.innerHTML = shipEstimates.map(function(s,i){
    return '<div class="ship-row">'
      +'<input type="text" class="sr-desc" data-i="'+i+'" value="'+e(s.desc)+'" placeholder="Description (e.g. Sea Freight to Jeddah)">'
      +'<input type="number" class="sr-amt" data-i="'+i+'" value="'+s.amt+'" min="0" step="100" placeholder="Amount">'
      +'<button class="btn br bsm" data-i="'+i+'" onclick="removeShipRow('+i+')">✕</button>'
      +'</div>';
  }).join('');
  el.querySelectorAll('.sr-desc').forEach(function(inp){
    inp.addEventListener('input',function(){ shipEstimates[+this.dataset.i].desc=this.value; });
  });
  el.querySelectorAll('.sr-amt').forEach(function(inp){
    inp.addEventListener('input',function(){ shipEstimates[+this.dataset.i].amt=parseFloat(this.value)||0; recalcShip(); });
  });
  recalcShip();
}
function removeShipRow(i){ shipEstimates.splice(i,1); renderShipRows(); }
function recalcShip(){
  var total=shipEstimates.reduce(function(t,s){return t+(parseFloat(s.amt)||0);},0);
  document.getElementById('shipTotal').textContent='$'+fmt(total);
}
function getShipPayload(){
  return shipEstimates.filter(function(s){return s.amt>0||s.desc;});
}

/* ── EXPORT CSV ── */
function exportCSV(){
  if(!allItems().length){ alert('No items in quote.'); return; }
  var div=getDiv(), disc=getDisc(), cur=document.getElementById('cur').value;
  var inco=document.getElementById('inco').value, loc=document.getElementById('incoLocation').value;
  var ship=(inco&&loc)?inco+' '+loc:(inco||'');
  var cOpt=document.getElementById('cs').options[document.getElementById('cs').selectedIndex];
  var cName=cOpt?cOpt.dataset.name||cOpt.textContent.replace(/\s*\(.*\)\s*$/,'').trim():'';
  var country=document.getElementById('country').value||(cOpt?cOpt.dataset.country||'':'');
  var rows=[];
  rows.push(['"TECHNOLOGIES INTERNATIONAL LLC"','','','','','','','']);
  rows.push(['"Authorized MENA Representative — Amatrol · DAC Worldwide · Bayport"','','','','','','','']);
  rows.push(['','','','','','','','']);
  rows.push(['"Client:"',q(cName),'','"Country:"',q(country),'','"Quote #:"',q(document.getElementById('qn').value)]);
  rows.push(['"Date:"',q(document.getElementById('qdate').value),'','"Shipping:"',q(ship),'','','']);
  rows.push(['','','','','','','','']);
  sections.forEach(function(sec){
    rows.push([q('SECTION: '+sec.name),'','','','','','','']);
    rows.push(['"#"','"Model"','"Description"','"Qty"','"Net Cost ('+cur+')"','"Sell Price ('+cur+')"','"Line Total"','"Notes"']);
    var mi=0,sc2={};
    sec.items.forEach(function(line){
      var p=line.product,isMain=!line.isSubOf,ns;
      if(isMain){mi++;sc2[mi]=0;line._mi=mi;ns=mi+'.0';}
      else{var pl=sec.items.find(function(l){return l.product.model_id===line.isSubOf;});var pi=pl?pl._mi:mi;sc2[pi]=(sc2[pi]||0)+1;ns=pi+'.'+sc2[pi];}
      if(line.custom_num) ns=line.custom_num;
      var net=p.intl_dist_net?parseFloat(p.intl_dist_net):null;
      var sell=net?(net*(1-disc)/lineDiv(line,sec)):null;
      var isIncl=line.isSubOf&&line.subPricing==='included';
      // For main items, fold included sub costs into unit price
      var sellPerUnit=sell||0;
      if(isMain&&sell!==null){
        sec.items.forEach(function(sub){
          if(sub.isSubOf===p.model_id&&sub.subPricing==='included'&&!sub.isOptional){
            var sn=sub.product.intl_dist_net?parseFloat(sub.product.intl_dist_net):null;
            if(sn)sellPerUnit+=sn*(1-disc)/lineDiv(sub,sec)*(sub.qty||1);
          }
        });
      }
      var tot=(!isIncl&&sellPerUnit)?sellPerUnit*line.qty:null;
      rows.push([q(ns),q(p.model_id),q(p.title_only||''),line.qty,
        net?net.toFixed(2):'N/A',
        isIncl?'"Included in parent"':(sellPerUnit?sellPerUnit.toFixed(2):'"See Amatrol"'),
        isIncl?'"Included in parent"':(tot?tot.toFixed(2):'"TBD"'),
        q(p.intl_market_price_note||'')]);
    });
    var st=secTotal(sec,div,disc);
    rows.push(['','','','','','"Section Total:"',st.toFixed(2),'']);
    rows.push(['','','','','','','','']);
  });
  var grand=sections.reduce(function(t,s){return t+secTotal(s,div,disc);},0);
  rows.push(['','','','','','"GRAND TOTAL:"',grand.toFixed(2)+' '+cur,'']);
  var csv=rows.map(function(r){return r.join(',');}).join('\r\n');
  dlBlob('\xEF\xBB\xBF'+csv, document.getElementById('qn').value+'.csv','text/csv');
}

/* ── EXPORT PROPOSAL PDF ── */
function exportProposalPDF(mode){
  window._pdfExportMode = mode || 'combined';
  if(!allItems().length){ alert('Add items to the quote first.'); return; }
  // Update modal title to reflect mode
  var titles={combined:'📄 Full Proposal PDF',commercial:'💰 Commercial Package (Financial Only)',literature:'📚 Literature Package (Datasheets Only)'};
  loadSavedLogos(function(){
    loadQuoteAttachments(function(){
      document.getElementById('proposalModal').classList.add('open');
      var mt = document.getElementById('proposalModalTitle');
      if(mt) mt.textContent = titles[window._pdfExportMode]||'Proposal PDF';
    });
  });
}

function loadSavedLogos(callback){
  fetch('api.php?action=list_proposal_logos')
    .then(function(r){ return r.json(); })
    .then(function(d){
      var sel = document.getElementById('proposalLogoSelect');
      while(sel.options.length > 1) sel.remove(1);
      (d.logos || []).forEach(function(logo){
        var o = document.createElement('option');
        o.value = logo.path;
        // Use stored friendly name if available, otherwise filename without extension
        var storedName = localStorage.getItem('ti_logo_name_'+logo.path);
        o.textContent = storedName || logo.filename.replace(/\.[^.]+$/,'').replace(/[-_]/g,' ');
        sel.appendChild(o);
      });
      // Auto-restore last used logo
      var lastPath = localStorage.getItem('ti_last_logo_path');
      var lastName = localStorage.getItem('ti_last_logo_name');
      if(lastPath){
        var match = Array.from(sel.options).find(function(o){ return o.value === lastPath; });
        if(match){
          sel.value = lastPath;
          showLogoPreview(lastPath, lastName || match.textContent);
        }
      }
      if(typeof callback === 'function') callback();
    })
    .catch(function(){ if(typeof callback === 'function') callback(); });
}

function doExportProposalPDF(){
  document.getElementById('proposalModal').classList.remove('open');
  if(!allItems().length){ alert('Add items to the quote first.'); return; }
  var cOpt=document.getElementById('cs').options[document.getElementById('cs').selectedIndex];
  var btn=document.getElementById('proposalExportBtn');
  var origText=btn?btn.textContent:'';
  if(btn){ btn.textContent='⏳ Building…'; btn.disabled=true; }
  var pay=getPaymentPayload();
  var payload={
    action:        'export_proposal_pdf',
    pdf_mode:      window._pdfExportMode || 'combined',
    client_name:   cOpt?cOpt.dataset.name||cOpt.textContent.replace(/\s*\(.*\)\s*$/,'').trim():'',
    country:       document.getElementById('country').value,
    quote_num:     document.getElementById('qn').value,
    date:          document.getElementById('qdate').value,
        client_logo:         document.getElementById('proposalLogoSelect').value,
        install_train_amt:   parseFloat(document.getElementById('installAmt').value)||0,
        install_train_label: document.getElementById('installLabel').value||'INSTALLATION AND COMMISSIONING',
    divisor:       parseFloat(document.getElementById('div').value)||0.65,
    discount_pct:  parseFloat(document.getElementById('disc').value)||0,
    divisors:     divisors,
    ship_estimates: isExWorksSelected() ? [] : getShipPayload(),
    install_train_amt:   parseFloat(document.getElementById('installAmt').value)||0,
    install_train_label: document.getElementById('installLabel').value||'INSTALLATION AND COMMISSIONING',
    incoterm:      document.getElementById('inco').value,
    inco_location: document.getElementById('incoLocation').value,
    payment_wire:  pay.payment_wire,
    payment_include_lc: pay.payment_include_lc,
    payment_lc_terms: pay.payment_lc_terms,
    rfq_doc_path:  window._rfqDocPath || '',
    tender_doc_paths: (window._tenderDocs||[]).map(function(t){ return t.path; }),
    sections: sections.map(function(sec){ return {
      id:   sec.id,
      name: sec.name,
      items: sec.items.map(function(l){ return {
        model_id:     l.product.model_id,
        title_only:   l.product.title_only||l.product.model_id,
        intl_dist_net:l.product.intl_dist_net||null,
        isSynthetic:  l.product.isSynthetic||false,
        description:  l.product.description||'',
        product_class:l.product.product_class||'',
        qty:          l.qty,
        isSubOf:      l.isSubOf||null,
        subPricing:   l.subPricing||'included',
        isOptional:   l.isOptional||false,
        divisorKey:   l.divisorKey||null,
        lid:          l.lid||null,
        custom_num:   l.custom_num||null
      }; })
    }; })
  };
  apiPost(payload).then(function(r){
    if(!r.ok){ alert('Proposal PDF error: '+(r.error||'Unknown')); return; }
    // Download the PDF
    var bin=atob(r.pdf), arr=new Uint8Array(bin.length);
    for(var i=0;i<bin.length;i++) arr[i]=bin.charCodeAt(i);
    dlBlob(arr, r.filename, 'application/pdf');
  }).catch(function(err){ alert('Error: '+err.message); })
  .finally(function(){
    if(btn){ btn.textContent=origText; btn.disabled=false; }
  });
}

/* ── EXPORT XLSX ── */
function exportXLSX(){
  if(!allItems().length){ alert('No items in quote.'); return; }
  var cOpt=document.getElementById('cs').options[document.getElementById('cs').selectedIndex];
  var pay=getPaymentPayload();
  var payload={
    action:'export_xlsx',
    client_name: cOpt?cOpt.dataset.name||cOpt.textContent.replace(/\s*\(.*\)\s*$/,'').trim():'',
    country: document.getElementById('country').value||(cOpt?cOpt.dataset.country||'':''),
    quote_num: document.getElementById('qn').value,
    date: document.getElementById('qdate').value,
    currency: document.getElementById('cur').value,
    incoterm: document.getElementById('inco').value,
    inco_location: document.getElementById('incoLocation').value,
    divisors: divisors,
    divisor: getDiv(),
    discount_pct: getDisc()*100,
    ship_estimates: isExWorksSelected() ? [] : getShipPayload(),
    install_train_amt:   parseFloat(document.getElementById('installAmt').value)||0,
    install_train_label: document.getElementById('installLabel').value||'INSTALLATION AND COMMISSIONING',
    payment_wire: pay.payment_wire,
    payment_include_lc: pay.payment_include_lc,
    payment_lc_terms: pay.payment_lc_terms,
    sections: sections.map(function(sec){ return {
      id:sec.id, name:sec.name,
      items:sec.items.map(function(l){ return {
        model_id:      l.product.model_id,
        qty:           l.qty,
        isSubOf:       l.isSubOf||null,
        subPricing:    l.subPricing||'included',
        divisorKey:    l.divisorKey||null,
        intl_dist_net: l.product ? (l.product.intl_dist_net||null) : null,
        isSynthetic:   l.product ? (l.product.isSynthetic||false) : false,
        custom_num:    l.custom_num||null
      }; })
    };})
  };
  apiPost(payload).then(function(r){
    if(!r.ok){ alert('Export error: '+(r.error||'Unknown')); return; }
    var bin=atob(r.xlsx), arr=new Uint8Array(bin.length);
    for(var i=0;i<bin.length;i++) arr[i]=bin.charCodeAt(i);
    dlBlob(arr, r.filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  }).catch(function(err){ alert('Export failed: '+err.message); });
}

/* ── LOCAL AGENTS ── */
var currentAgentClientId = null;

function openAgentsModal(){
  var sel = document.getElementById('cs');
  var opt = sel.options[sel.selectedIndex];
  if(!sel.value || !opt){ return; }
  currentAgentClientId = sel.value;
  var cName = opt.dataset.name || opt.textContent.replace(/\s*\(.*\)\s*$/, '').trim();
  document.getElementById('agentsClientLabel').textContent = cName;
  clearAgentForm();
  document.getElementById('agErr').style.display = 'none';
  loadAgents();
  document.getElementById('agentsModal').classList.add('open');
}
function closeAgentsModal(){
  document.getElementById('agentsModal').classList.remove('open');
  currentAgentClientId = null;
}

function loadAgents(){
  var auth = getAuth();
  document.getElementById('agentsList').innerHTML = '<div style="font-size:.72rem;color:var(--sub);padding:8px 0">Loading…</div>';
  fetch('/portal/admin/clients-db-api.php',{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'list_agents', user:auth.user, pass:auth.pass, id:currentAgentClientId})
  }).then(function(r){return r.json();}).then(function(r){
    renderAgentsList(r.ok ? (r.agents||[]) : []);
  }).catch(function(){ renderAgentsList([]); });
}

function renderAgentsList(agents){
  var el = document.getElementById('agentsList');
  if(!agents.length){
    el.innerHTML = '<div style="font-size:.73rem;color:var(--sub);padding:10px 0;text-align:center;border:1px dashed var(--bdr);border-radius:6px">No agents assigned yet. Add the first one below.</div>';
    return;
  }
  el.innerHTML = agents.map(function(ag, idx){
    var country = ag.country ? '<span style="color:var(--sub);font-size:.65rem"> · '+e(ag.country)+'</span>' : '';
    var comm    = ag.commission_pct ? '<span style="color:#1a9a6a;font-size:.65rem;font-weight:700"> · '+ag.commission_pct+'% comm</span>' : '';
    var phone   = ag.phone   ? '<span style="color:var(--sub)">📞 '+e(ag.phone)+'</span>' : '';
    var mobile  = ag.mobile  ? '<span style="color:var(--sub)">📱 '+e(ag.mobile)+'</span>' : '';
    var email   = ag.email   ? '<span style="color:var(--bm)">✉ '+e(ag.email)+'</span>' : '';
    var notes   = ag.notes   ? '<div style="font-size:.65rem;color:var(--sub);margin-top:3px;font-style:italic">'+e(ag.notes)+'</div>' : '';
    return '<div style="background:#fff;border:1px solid var(--bdr);border-radius:7px;padding:10px 12px;margin-bottom:7px;display:flex;align-items:flex-start;gap:10px">'
      + '<div style="background:rgba(13,184,168,.1);border:1px solid rgba(13,184,168,.3);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#0db8a8;flex-shrink:0">'+(idx+1)+'</div>'
      + '<div style="flex:1;min-width:0">'
      + '<div style="font-size:.8rem;font-weight:700;color:var(--bd)">'+e(ag.company)+country+comm+'</div>'
      + (ag.contact_name ? '<div style="font-size:.72rem;color:var(--tx);margin-top:2px">'+e(ag.contact_name)+(ag.title?' <span style="color:var(--sub)">— '+e(ag.title)+'</span>':'')+'</div>' : '')
      + '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;font-size:.68rem">'+[phone,mobile,email].filter(Boolean).join('')+'</div>'
      + notes
      + '</div>'
      + '<button onclick="removeAgent(\''+e(ag.id)+'\')" style="background:none;border:none;color:#ccc;cursor:pointer;font-size:.85rem;flex-shrink:0;padding:2px 4px" title="Remove agent">🗑</button>'
      + '</div>';
  }).join('');
}

function saveAgent(){
  if(!currentAgentClientId){ return; }
  var company = document.getElementById('agCompany').value.trim();
  if(!company){ showAgErr('Agent company name is required.'); return; }
  var auth = getAuth();
  var btn = document.getElementById('agSaveBtn');
  btn.disabled = true; btn.textContent = 'Saving…';
  document.getElementById('agErr').style.display = 'none';
  var country = document.getElementById('agCountry').value ||
    (document.getElementById('country').value.trim()) || '';
  var agent = {
    id:        'ag' + Date.now(),
    company:   company,
    contact_name: document.getElementById('agContact').value.trim(),
    title:     document.getElementById('agTitle').value.trim(),
    country:   country,
    phone:     document.getElementById('agPhone').value.trim(),
    mobile:    document.getElementById('agMobile').value.trim(),
    email:     document.getElementById('agEmail').value.trim(),
    commission_pct: parseFloat(document.getElementById('agComm').value) || '',
    notes:     document.getElementById('agNotes').value.trim()
  };
  fetch('/portal/admin/clients-db-api.php',{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'add_agent', user:auth.user, pass:auth.pass, id:currentAgentClientId, agent:agent})
  }).then(function(r){return r.json();}).then(function(r){
    if(!r.ok){ showAgErr('Error: '+(r.error||'Could not save.')); btn.disabled=false; btn.textContent='＋ Add Agent'; return; }
    clearAgentForm();
    loadAgents();
    btn.disabled = false; btn.textContent = '＋ Add Agent';
  }).catch(function(err){ showAgErr('Error: '+err.message); btn.disabled=false; btn.textContent='＋ Add Agent'; });
}

function removeAgent(agentId){
  if(!currentAgentClientId || !confirm('Remove this agent?')) return;
  var auth = getAuth();
  fetch('/portal/admin/clients-db-api.php',{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'remove_agent', user:auth.user, pass:auth.pass, id:currentAgentClientId, agent_id:agentId})
  }).then(function(r){return r.json();}).then(function(r){
    if(r.ok) loadAgents();
  }).catch(function(){});
}

function clearAgentForm(){
  ['agCompany','agContact','agTitle','agPhone','agMobile','agEmail','agComm','agNotes'].forEach(function(id){
    var el = document.getElementById(id); if(el) el.value = '';
  });
  document.getElementById('agCountry').value = '';
}
function showAgErr(msg){ var el=document.getElementById('agErr'); el.textContent=msg; el.style.display='block'; }

function getAuth(){
  var auth = {user:'dhanzal', pass:'aA292199'};
  try{ var s=sessionStorage.getItem('tikitmeer_auth'); if(s){var a=JSON.parse(s);if(a.user)auth={user:a.user,pass:a.pass};} }catch(e){}
  return auth;
}

/* ── ARABIC NAME LOOKUP ── */
async function lookupArabicName(){
  var name = document.getElementById('acName').value.trim();
  if(!name){ alert('Enter the company name first.'); return; }

  var btn  = document.getElementById('arLookupBtn');
  var inp  = document.getElementById('acNameAr');
  var srcTag  = document.getElementById('arSourceTag');
  var srcBadge= document.getElementById('arSourceBadge');
  var srcNote = document.getElementById('arSourceNote');
  var srcLink = document.getElementById('arSourceLink');
  var resList = document.getElementById('arResultsList');

  btn.disabled = true;
  btn.textContent = '⏳ Searching…';
  srcTag.style.display  = 'none';
  resList.style.display = 'none';

  // ── STEP 1: Wikipedia page search ──────────────────────────────────────
  // Find pages matching the company name, then get their Arabic interlanguage link
  try {
    var searchUrl = 'https://en.wikipedia.org/w/api.php?action=query'
      + '&list=search&srsearch=' + encodeURIComponent(name)
      + '&srlimit=5&format=json&origin=*';
    var sRes  = await fetch(searchUrl);
    var sData = await sRes.json();
    var hits  = (sData.query && sData.query.search) ? sData.query.search : [];

    if(hits.length){
      // Fetch Arabic langlinks for the top 3 hits in one request
      var titles = hits.slice(0,3).map(function(h){ return h.title; }).join('|');
      var llUrl  = 'https://en.wikipedia.org/w/api.php?action=query'
        + '&prop=langlinks&lllang=ar&llprop=langname'
        + '&titles=' + encodeURIComponent(titles)
        + '&format=json&origin=*';
      var llRes  = await fetch(llUrl);
      var llData = await llRes.json();
      var pages  = (llData.query && llData.query.pages) ? llData.query.pages : {};

      // Collect all results that have an Arabic link
      var matches = [];
      Object.values(pages).forEach(function(page){
        if(!page.langlinks) return;
        page.langlinks.forEach(function(ll){
          if(ll.lang === 'ar' && ll['*']){
            matches.push({
              enTitle:  page.title,
              arName:   ll['*'],
              wikiSlug: page.title.replace(/ /g,'_')
            });
          }
        });
      });

      if(matches.length === 1){
        // Exactly one match — use it directly
        var m = matches[0];
        inp.value = m.arName;
        showArSource('wikipedia', 'Wikipedia — ' + m.enTitle, m.wikiSlug);
        resetLookupBtn(btn);
        return;
      }

      if(matches.length > 1){
        // Multiple results — show a pick list
        showArResultsList(matches, btn);
        return;
      }
    }
  } catch(e){ console.warn('Wikipedia lookup failed:', e); }

  // ── STEP 2: Website Arabic text scan ───────────────────────────────────
  // If a website was entered, try a server-side fetch via our API proxy
  var website = document.getElementById('acWebsite').value.trim();
  if(website){
    try{
      var wsRes = await fetch('api.php?action=fetch_arabic_name&url='+encodeURIComponent(website)+'&company='+encodeURIComponent(name));
      var wsData = await wsRes.json();
      if(wsData.ok && wsData.arabic_name){
        inp.value = wsData.arabic_name;
        showArSource('website', 'Found on company website', null, website.startsWith('http')?website:'https://'+website);
        resetLookupBtn(btn);
        return;
      }
    }catch(e){ console.warn('Website scan failed:', e); }
  }

  // ── STEP 3: MyMemory free translation API ───────────────────────────────
  // Gives Arabic phonetic transliteration/translation as a last resort
  try{
    var mmUrl = 'https://api.mymemory.translated.net/get?q='
      + encodeURIComponent(name) + '&langpair=en|ar&mt=1';
    var mmRes  = await fetch(mmUrl);
    var mmData = await mmRes.json();
    if(mmData.responseStatus === 200 && mmData.responseData && mmData.responseData.translatedText){
      var translated = mmData.responseData.translatedText;
      // MyMemory sometimes returns English — verify result contains Arabic chars
      if(/[\u0600-\u06FF]/.test(translated)){
        inp.value = translated;
        showArSource('translation',
          'Auto-translated — verify this is the official name',
          null, null, true);
        resetLookupBtn(btn);
        return;
      }
    }
  }catch(e){ console.warn('Translation API failed:', e); }

  // ── STEP 4: Nothing found ───────────────────────────────────────────────
  showArSource('notfound',
    'No Arabic name found automatically — please enter manually',
    null, null, false, true);
  resetLookupBtn(btn);
}

function showArResultsList(matches, btn){
  var resList = document.getElementById('arResultsList');
  resList.innerHTML = matches.map(function(m, i){
    return '<div class="ar-result-opt" data-ar="'+encodeURIComponent(m.arName)+'" data-en="'+encodeURIComponent(m.enTitle)+'" data-slug="'+encodeURIComponent(m.wikiSlug)+'"'
      + ' style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--bdr);'+(i===matches.length-1?'border-bottom:none':'')+';transition:background .1s"'
      + ' onmouseover="this.style.background=\'#f4f8fc\'" onmouseout="this.style.background=\'#fff\'"'
      + ' onclick="pickArResult(this)">'
      + '<div>'
      + '<div style="font-size:.75rem;color:var(--bd);font-weight:600">'+e(m.enTitle)+'</div>'
      + '<div style="font-size:.82rem;color:var(--tx);direction:rtl;text-align:right;margin-top:2px">'+e(m.arName)+'</div>'
      + '</div>'
      + '<div style="font-size:.65rem;color:var(--bm);font-weight:700;white-space:nowrap;margin-left:12px">Select ›</div>'
      + '</div>';
  }).join('');
  resList.style.display = 'block';
  resetLookupBtn(btn);
}

function pickArResult(el){
  var arName  = decodeURIComponent(el.dataset.ar);
  var enTitle = decodeURIComponent(el.dataset.en);
  var slug    = decodeURIComponent(el.dataset.slug);
  document.getElementById('acNameAr').value = arName;
  document.getElementById('arResultsList').style.display = 'none';
  showArSource('wikipedia', 'Wikipedia — '+enTitle, slug);
}

function showArSource(type, note, wikiSlug, url, warn, notfound){
  var tag    = document.getElementById('arSourceTag');
  var badge  = document.getElementById('arSourceBadge');
  var noteEl = document.getElementById('arSourceNote');
  var link   = document.getElementById('arSourceLink');

  var styles = {
    wikipedia:   {bg:'rgba(26,111,160,.12)',  color:'#1a6fa0', label:'📖 Wikipedia'},
    website:     {bg:'rgba(13,184,168,.1)',    color:'#0db8a8', label:'🌐 Website'},
    translation: {bg:'rgba(201,162,39,.1)',    color:'#a07a00', label:'🔤 Translated'},
    notfound:    {bg:'rgba(200,60,60,.08)',    color:'#c04040', label:'❌ Not Found'},
  };
  var s = styles[type] || styles.notfound;
  badge.textContent    = s.label;
  badge.style.cssText  = 'font-size:.61rem;font-weight:700;padding:2px 8px;border-radius:10px;background:'+s.bg+';color:'+s.color;
  noteEl.textContent   = note || '';
  link.style.display   = 'none';
  if(wikiSlug){
    link.href = 'https://en.wikipedia.org/wiki/'+wikiSlug;
    link.textContent = '↗ Wikipedia';
    link.style.display = 'inline';
  } else if(url){
    link.href = url;
    link.textContent = '↗ Website';
    link.style.display = 'inline';
  }
  tag.style.display = 'flex';
}

function resetLookupBtn(btn){
  btn.disabled    = false;
  btn.textContent = '🔍 Look Up Again';
}

/* ── CLIENT LOGO HELPERS ── */
function acLogoFileChosen(input){
  var file = input.files[0];
  if(!file) return;
  acLogoShowPreview(file);
}
function acLogoDrop(e){
  e.preventDefault();
  document.getElementById('acLogoDrop').style.borderColor = 'var(--bdr)';
  var file = e.dataTransfer.files[0];
  if(!file || !file.type.match(/^image\//)) return;
  document.getElementById('acLogoFile').files; // can't assign, use DataTransfer trick
  // Store file on the input via DataTransfer
  try{
    var dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('acLogoFile').files = dt.files;
  }catch(ex){}
  acLogoShowPreview(file);
}
function acLogoShowPreview(file){
  var reader = new FileReader();
  reader.onload = function(ev){
    document.getElementById('acLogoImg').src = ev.target.result;
    document.getElementById('acLogoFileName').textContent = file.name;
    document.getElementById('acLogoPreview').style.display = 'flex';
    document.getElementById('acLogoDrop').style.display = 'none';
  };
  reader.readAsDataURL(file);
}
function acLogoClear(){
  document.getElementById('acLogoFile').value = '';
  document.getElementById('acLogoImg').src = '';
  document.getElementById('acLogoFileName').textContent = '';
  document.getElementById('acLogoPreview').style.display = 'none';
  var drop = document.getElementById('acLogoDrop');
  if(drop) drop.style.display = '';
}

// Apply a logo path to the proposal logo selector and preview
function acApplyLogoToProposal(logoPath, companyName){
  if(!logoPath) return;
  var sel = document.getElementById('proposalLogoSelect');
  if(!sel) return;
  // Add option if not already there
  var found = false;
  for(var i=0; i<sel.options.length; i++){
    if(sel.options[i].value === logoPath){ found=true; sel.selectedIndex=i; break; }
  }
  if(!found){
    var o = document.createElement('option');
    o.value = logoPath;
    o.textContent = companyName || logoPath;
    sel.appendChild(o);
    sel.value = logoPath;
  }
  // Store for next proposal open
  localStorage.setItem('ti_last_logo_path', logoPath);
  localStorage.setItem('ti_last_logo_name', companyName || logoPath);
}

/* ── QUICK-ADD CLIENT ── */
function openAddClientModal(){
  ['acName','acNameAr','acWebsite','acNotes',
   'acContactName','acContactTitle','acContactEmail','acContactMobile'].forEach(function(id){
    var el=document.getElementById(id); if(el) el.value='';
  });
  document.getElementById('acCountry').value='';
  document.getElementById('acSector').value='';
  document.getElementById('acErr').style.display='none';
  document.getElementById('acSaveBtn').disabled=true;
  acLogoClear();
  // Reset Arabic lookup UI
  var arSrc=document.getElementById('arSourceTag'); if(arSrc) arSrc.style.display='none';
  var arRes=document.getElementById('arResultsList'); if(arRes) arRes.style.display='none';
  var arBtn=document.getElementById('arLookupBtn'); if(arBtn){arBtn.disabled=false;arBtn.textContent='\uD83D\uDD0D Look Up Arabic Name';}
  // Init dynamic phone list with one empty row
  document.getElementById('acPhoneList').innerHTML='';
  acPhoneCount=0;
  acAddPhone();
  // Reset Google Places search
  var _pi=document.getElementById('acPlacesInput'); if(_pi) _pi.value='';
  var _pd=document.getElementById('acPlacesDrop'); if(_pd) _pd.style.display='none';
  var _nk=document.getElementById('acPlacesNoKey'); if(_nk) _nk.style.display='none';
  // Init address lines
  document.getElementById('acAddrLines').innerHTML='';
  acAddrCount=0;
  acAddAddrLine();
  acAddAddrLine();
  document.getElementById('addClientModal').classList.add('open');
  setTimeout(function(){ document.getElementById('acName').focus(); },80);
}
function closeAddClientModal(){
  document.getElementById('addClientModal').classList.remove('open');
}
function acNameCheck(){
  document.getElementById('acSaveBtn').disabled =
    document.getElementById('acName').value.trim().length < 2;
}
function saveNewClient(){
  var name = document.getElementById('acName').value.trim();
  if(!name){ showAcErr('Company name is required.'); return; }

  // Collect phones
  var phones = [];
  document.querySelectorAll('#acPhoneList .ac-phone-row').forEach(function(row){
    var type = row.querySelector('.ac-phone-type').value;
    var num  = row.querySelector('.ac-phone-num').value.trim();
    if(num) phones.push({type:type, number:num});
  });

  // Collect address lines
  var addrLines = [];
  document.querySelectorAll('#acAddrLines .ac-addr-input').forEach(function(inp){
    var v = inp.value.trim(); if(v) addrLines.push(v);
  });

  var contacts=[];
  var cName=document.getElementById('acContactName').value.trim();
  if(cName){
    contacts.push({
      id:'cc'+Date.now(), name:cName,
      title:document.getElementById('acContactTitle').value.trim(),
      email:document.getElementById('acContactEmail').value.trim(),
      mobile:document.getElementById('acContactMobile').value.trim(),
      direct:'', isPrimary:true, notes:''
    });
  }
  var auth = getAuth();
  var btn=document.getElementById('acSaveBtn');
  btn.disabled=true; btn.textContent='Saving…';
  fetch('/portal/admin/clients-db-api.php',{
    method:'POST', headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      action:'add_client', user:auth.user, pass:auth.pass,
      name:name,
      nameAr:document.getElementById('acNameAr').value.trim(),
      country:document.getElementById('acCountry').value,
      sector:document.getElementById('acSector').value,
      phones:phones,
      address_lines:addrLines,
      website:document.getElementById('acWebsite').value.trim(),
      notes:document.getElementById('acNotes').value.trim(),
      address:'', contacts:contacts
    })
  })
  .then(function(r){return r.json();})
  .then(function(r){
    if(!r.ok){ showAcErr('Error: '+(r.error||'Could not save.')); btn.disabled=false; btn.textContent='✓ Add Client'; return; }
    var c=r.client;
    var sel=document.getElementById('cs');
    var o=document.createElement('option');
    o.value=c.id;
    o.textContent=c.name+(c.country?' ('+c.country+')':'');
    o.dataset.name=c.name;
    o.dataset.country=c.country||'';
    sel.appendChild(o);
    sel.value=c.id;
    var ab=document.getElementById('agentsBtn');
    if(ab) ab.style.display='';
    if(c.country) document.getElementById('country').value=c.country;
    // Upload logo if one was selected
    var logoFile = document.getElementById('acLogoFile').files[0];
    if(logoFile && c.id){
      var auth2 = getAuth();
      var fd = new FormData();
      fd.append('action',       'upload_client_logo');
      fd.append('user',         auth2.user);
      fd.append('pass',         auth2.pass);
      fd.append('client_id',    c.id);
      fd.append('company_name', c.name);
      fd.append('logo',         logoFile);
      fetch('/portal/admin/clients-db-api.php', {method:'POST', body:fd})
        .then(function(r2){return r2.json();})
        .then(function(r2){
          if(r2.ok && r2.path){
            o.dataset.logo = r2.path;
            // Pre-select in proposal logo picker if it exists
            acApplyLogoToProposal(r2.path, c.name);
          }
        }).catch(function(){});
    }
    closeAddClientModal();
  })
  .catch(function(err){ showAcErr('Connection error: '+err.message); btn.disabled=false; btn.textContent='✓ Add Client'; });
}
/* ── GOOGLE PLACES ADDRESS LOOKUP ── */
var acPlacesTimer=null,acPlacesLastQ='';
function acPlacesSearch(q){
  q=q.trim();clearTimeout(acPlacesTimer);
  var drop=document.getElementById('acPlacesDrop');
  if(q.length<3){drop.style.display='none';return;}
  if(q===acPlacesLastQ)return;
  acPlacesTimer=setTimeout(function(){acPlacesFetch(q);},320);
}
function acPlacesFetch(q){
  acPlacesLastQ=q;
  var spin=document.getElementById('acPlacesSpinner');
  var drop=document.getElementById('acPlacesDrop');
  var noKey=document.getElementById('acPlacesNoKey');
  spin.style.display='block';drop.style.display='none';
  fetch('api.php?action=places_search&q='+encodeURIComponent(q))
    .then(function(r){return r.json();})
    .then(function(d){
      spin.style.display='none';
      if(d.error==='no_key'){if(noKey)noKey.style.display='block';return;}
      if(noKey)noKey.style.display='none';
      if(!d.predictions||!d.predictions.length){
        drop.innerHTML='<div style="padding:10px 12px;font-size:.72rem;color:var(--sub)">No results found</div>';
        drop.style.display='block';return;
      }
      drop.innerHTML=d.predictions.map(function(p){
        var main=e(p.structured_formatting?p.structured_formatting.main_text:p.description);
        var sec=e(p.structured_formatting?p.structured_formatting.secondary_text:'');
        var pid=e(p.place_id); return '<div style="padding:9px 12px;cursor:pointer;border-bottom:1px solid #f0f4f8;transition:background .1s" onmouseover="this.style.background=\"#f4f8fc\"" onmouseout="this.style.background=\"#fff\"" onclick="acPlacePick(\"'+pid+'\")">'
          +'<div style="font-size:.76rem;font-weight:600;color:var(--bd)">&#128205; '+main+'</div>'
          +(sec?'<div style="font-size:.67rem;color:var(--sub);margin-top:1px">'+sec+'</div>':'')
          +'</div>';
      }).join('')+'<div style="padding:5px 10px;font-size:.58rem;color:#bbb;text-align:right">Powered by Google</div>';
      drop.style.display='block';
    })
    .catch(function(){spin.style.display='none';});
}
function acPlacePick(placeId){
  var drop=document.getElementById('acPlacesDrop');
  var spin=document.getElementById('acPlacesSpinner');
  drop.style.display='none';spin.style.display='block';
  fetch('api.php?action=places_detail&place_id='+encodeURIComponent(placeId))
    .then(function(r){return r.json();})
    .then(function(d){
      spin.style.display='none';
      if(!d.ok||!d.result){alert('Could not get address details.');return;}
      var res=d.result;
      var comp={};
      (res.address_components||[]).forEach(function(c){
        c.types.forEach(function(t){comp[t]=c.long_name;});
        if(c.types.indexOf('country')>=0)comp.country_code=c.short_name;
      });
      var lines=[];
      var l1=[comp.subpremise,comp.premise,comp.street_number,comp.route].filter(Boolean);
      if(l1.length)lines.push(l1.join(', '));
      var district=comp.sublocality_level_1||comp.sublocality||comp.neighborhood;
      if(district)lines.push(district);
      var city=comp.locality||comp.postal_town;
      if(city)lines.push(city);
      var state=comp.administrative_area_level_1;
      if(state&&state!==city)lines.push(state);
      if(comp.postal_code)lines.push(comp.postal_code);
      if(comp.country)lines.push(comp.country);
      if(!lines.length&&res.formatted_address){
        lines=res.formatted_address.split(',').map(function(s){return s.trim();});
      }
      document.getElementById('acAddrLines').innerHTML='';
      acAddrCount=0;
      lines.forEach(function(ln){acAddAddrLine(ln);});
      if(!lines.length)acAddAddrLine();
      var pi=document.getElementById('acPlacesInput');
      if(pi)pi.value=res.name||res.formatted_address||'';
      var ws=document.getElementById('acWebsite');
      if(ws&&!ws.value.trim()&&res.website)ws.value=res.website.replace(/^https?:\/\//,'').replace(/\/$/,'');
      if(comp.country){
        var cSel=document.getElementById('acCountry');
        if(cSel){
          var matched=false;
          for(var i=0;i<cSel.options.length;i++){
            if(cSel.options[i].value.toLowerCase().indexOf(comp.country.toLowerCase())>=0){
              cSel.selectedIndex=i;matched=true;break;
            }
          }
          if(!matched){var o=document.createElement('option');o.value=comp.country;o.textContent=comp.country;cSel.appendChild(o);cSel.value=comp.country;}
        }
      }
    })
    .catch(function(err){spin.style.display='none';alert('Address error: '+err.message);});
}
document.addEventListener('click',function(ev){
  var wrap=document.getElementById('acPlacesSearchWrap');
  var drop=document.getElementById('acPlacesDrop');
  if(wrap&&drop&&!wrap.contains(ev.target))drop.style.display='none';
});

// ── PHONE NUMBER DYNAMIC ROWS ──────────────────────────────────────────────
var acPhoneCount = 0;
var AC_PHONE_TYPES = ['Office','Mobile','Fax','Direct','WhatsApp','Toll-Free','Other'];

function acAddPhone(type, number){
  acPhoneCount++;
  var n = acPhoneCount;
  var typeOpts = AC_PHONE_TYPES.map(function(t){
    return '<option value="'+t+'"'+(type===t?' selected':'')+'>'+t+'</option>';
  }).join('');
  var row = document.createElement('div');
  row.className = 'ac-phone-row';
  row.style.cssText = 'display:flex;gap:6px;margin-bottom:6px;align-items:center';
  row.innerHTML =
    '<select class="ac-phone-type" style="width:100px;padding:6px 7px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.72rem">'+typeOpts+'</select>'
    +'<input type="tel" class="ac-phone-num" placeholder="e.g. +966 11 234 5678" value="'+(number||'')+'" style="flex:1;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.72rem">'
    +'<button type="button" onclick="this.closest(\'.ac-phone-row\').remove()" style="background:none;border:none;color:#ccc;cursor:pointer;font-size:.88rem;padding:2px 4px" title="Remove">✕</button>';
  document.getElementById('acPhoneList').appendChild(row);
}

// ── ADDRESS LINE DYNAMIC ROWS ───────────────────────────────────────────────
var acAddrCount = 0;
var AC_ADDR_PLACEHOLDERS = [
  'Building / Floor / Suite',
  'Street / Road / District',
  'City',
  'P.O. Box / Postal Code',
  'Province / Region',
  'Country'
];

function acAddAddrLine(value){
  var idx = document.querySelectorAll('#acAddrLines .ac-addr-row').length;
  acAddrCount++;
  var placeholder = AC_ADDR_PLACEHOLDERS[idx] || 'Address line '+(idx+1);
  var row = document.createElement('div');
  row.className = 'ac-addr-row';
  row.style.cssText = 'display:flex;gap:6px;margin-bottom:6px;align-items:center';
  row.innerHTML =
    '<input type="text" class="ac-addr-input" placeholder="'+placeholder+'" value="'+(value||'')+'" style="flex:1;padding:6px 9px;border:1.5px solid var(--bdr);border-radius:5px;font-size:.72rem">'
    +'<button type="button" onclick="this.closest(\'.ac-addr-row\').remove()" style="background:none;border:none;color:#ccc;cursor:pointer;font-size:.88rem;padding:2px 4px" title="Remove line">✕</button>';
  document.getElementById('acAddrLines').appendChild(row);
}

function showAcErr(msg){
  var el=document.getElementById('acErr');
  el.textContent=msg; el.style.display='block';
}

/* ── MARK ADDED (instant UI update without re-fetching) ── */
function markAdded(modelId){
  var rl = document.getElementById('rl');
  if(!rl) return;
  rl.querySelectorAll('.pa').forEach(function(btn){
    if(btn.dataset.mid === modelId){
      btn.classList.add('added');
      btn.textContent = '✓';
    }
  });
  rl.querySelectorAll('.pr').forEach(function(row){
    var b = row.querySelector('.pa');
    if(b && b.dataset.mid === modelId) row.classList.add('iq');
  });
}


/* ── QUOTE SEARCH FILTER ── */
function filterQuote(q){
  q = q.trim().toLowerCase();
  var body = document.getElementById('qbody');
  var countEl = document.getElementById('quoteSearchCount');
  if(!q){ 
    // Show all
    body.querySelectorAll('.qi,.sb').forEach(function(el){ el.style.display=''; });
    if(countEl) countEl.textContent='';
    return;
  }
  var matched = 0;
  // Hide/show individual items within sections
  body.querySelectorAll('.sb').forEach(function(sec){
    var anyVisible = false;
    sec.querySelectorAll('.qi').forEach(function(item){
      var mid  = (item.querySelector('.qm')||{}).textContent||'';
      var titl = (item.querySelector('.qtl')||{}).textContent||'';
      var hit  = mid.toLowerCase().includes(q) || titl.toLowerCase().includes(q);
      item.style.display = hit ? '' : 'none';
      if(hit){ anyVisible=true; matched++; }
    });
    sec.style.display = anyVisible ? '' : 'none';
  });
  if(countEl) countEl.textContent = matched + ' match' + (matched===1?'':'es');
}

/* ── PRICE EDIT ── */
var priceEditMid = null;
var priceOverrides = {}; // session-only overrides: {model_id: {price, reason, quotes, ts}}

function openPriceEdit(mid){
  var p = prodMap[mid];
  if(!p) return;
  priceEditMid = mid;
  var cur = p._priceOverride || p.intl_dist_net;
  document.getElementById('peModelLabel').textContent = mid + (p.title_only ? '  —  ' + p.title_only : '');
  document.getElementById('peCurrentPrice').textContent = cur ? '$' + fmt(parseFloat(cur)) : 'N/A';
  document.getElementById('peNewPrice').value = cur ? parseFloat(cur).toFixed(2) : '';
  document.getElementById('peReason').value = p._priceNote ? p._priceNote.reason : '';
  document.getElementById('peQuotes').value = p._priceNote ? p._priceNote.quotes : document.getElementById('qn').value || '';
  document.getElementById('pePermanent').checked = false;
  document.getElementById('peErr').style.display = 'none';
  document.getElementById('priceEditModal').classList.add('open');
  setTimeout(function(){ document.getElementById('peNewPrice').select(); }, 80);
}
function closePriceEdit(){
  document.getElementById('priceEditModal').classList.remove('open');
  priceEditMid = null;
}
function applyPriceEdit(){
  var mid = priceEditMid;
  if(!mid) return;
  var newP = parseFloat(document.getElementById('peNewPrice').value);
  var reason = document.getElementById('peReason').value.trim();
  var quotes = document.getElementById('peQuotes').value.trim();
  var perm = document.getElementById('pePermanent').checked;
  if(isNaN(newP) || newP < 0){ showPeErr('Enter a valid price.'); return; }
  if(!reason){ showPeErr('Please enter a reason for this change.'); return; }

  var p = prodMap[mid];
  if(!p) return;

  var ts = new Date().toISOString().slice(0,10);
  var note = {reason:reason, quotes:quotes, ts:ts, permanent:perm};

  // Apply to prodMap (session)
  p._priceOverride = newP;
  p.intl_dist_net  = newP;   // keep in sync so renderQuote sees new price immediately
  p._priceNote = note;
  priceOverrides[mid] = {price:newP, note:note};

  // Also update any existing quote items using this product
  allItems().forEach(function(line){
    if(line.product.model_id === mid){
      line.product.intl_dist_net = newP;
      line.product._priceOverride = newP;
      line.product._priceNote = note;
    }
  });

  if(perm){
    // Save to database
    apiPost({action:'update_product_price', model_id:mid, intl_dist_net:newP, reason:reason, quotes:quotes})
    .then(function(r){
      if(r.ok){ console.log('Price saved permanently for '+mid); }
      else { console.warn('DB save failed:', r.error); }
    });
  }

  closePriceEdit();
  // Re-render search and quote
  cache = {};
  search();
  renderQuote();
}
function showPeErr(msg){
  var el = document.getElementById('peErr');
  el.textContent = msg; el.style.display = 'block';
}

/* ── MANUAL ITEM ADD ── */
function openManualAdd(){
  ['maModelId','maTitle2','maDesc','maMfr','maNet','maList','maLead','maDims','maNotes'].forEach(function(id){
    var el=document.getElementById(id); if(el) el.value='';
  });
  document.getElementById('maCls').value='';
  document.getElementById('maPdfFile').value='';
  document.getElementById('maPdfName').textContent='';
  document.getElementById('maErr').style.display='none';
  document.getElementById('maDateStamp').textContent=new Date().toISOString().slice(0,10);
  // Reset req/rec
  _maTags = {req:[], rec:[]};
  maRenderTags('req'); maRenderTags('rec');
  document.getElementById('maReqSearch').value='';
  document.getElementById('maRecSearch').value='';
  document.getElementById('maReqSuggestions').innerHTML='';
  document.getElementById('maRecSuggestions').innerHTML='';
  document.getElementById('manualAddModal').classList.add('open');
  setTimeout(function(){ document.getElementById('maModelId').focus(); },80);
}

function openEditItem(mid){
  var p = prodMap[mid];
  if(!p){ alert('Product data not loaded — search for it first.'); return; }
  // Pre-fill all fields
  document.getElementById('maModelId').value = p.model_id || '';
  document.getElementById('maTitle2').value  = p.title_only || '';
  document.getElementById('maDesc').value    = p.title_description || p.description || '';
  document.getElementById('maMfr').value     = p.manufacturer || '';
  document.getElementById('maCls').value     = p.product_class || '';
  document.getElementById('maNet').value     = p.intl_dist_net || '';
  document.getElementById('maList').value    = p.intl_market_price || '';
  document.getElementById('maLead').value    = p.mfr_lead_time || '';
  document.getElementById('maDims').value    = p.dimensions || '';
  document.getElementById('maNotes').value   = '';
  document.getElementById('maPdfFile').value = '';
  document.getElementById('maPdfName').textContent = '';
  document.getElementById('maErr').style.display = 'none';
  document.getElementById('maDateStamp').textContent = new Date().toISOString().slice(0,10);
  // Pre-fill req/rec tags from product data
  _maTags = {
    req: (p.requires_models   || []).slice(),
    rec: (p.recommended_models|| []).slice()
  };
  maRenderTags('req'); maRenderTags('rec');
  document.getElementById('maReqSearch').value = '';
  document.getElementById('maRecSearch').value = '';
  document.getElementById('maReqSuggestions').innerHTML = '';
  document.getElementById('maRecSuggestions').innerHTML = '';
  document.getElementById('manualAddModal').classList.add('open');
  setTimeout(function(){ document.getElementById('maTitle2').focus(); },80);
}
function closeManualAdd(){
  document.getElementById('manualAddModal').classList.remove('open');
}
function maPreviewPdf(inp){
  var n = inp.files[0] ? inp.files[0].name : '';
  document.getElementById('maPdfName').textContent = n ? '✓ '+n : '';
}
/* ── REQUIRED / RECOMMENDED TAG MANAGEMENT ── */
var _maTags = {req:[], rec:[]};
var _maSearchTimers = {req:null, rec:null};

function maSearchDeps(type, q){
  clearTimeout(_maSearchTimers[type]);
  var el = document.getElementById(type==='req'?'maReqSuggestions':'maRecSuggestions');
  q = q.trim();
  if(!q){ el.innerHTML=''; return; }
  el.innerHTML='<div class="ma-sug" style="cursor:default;color:var(--sub)">Searching…</div>';
  _maSearchTimers[type] = setTimeout(function(){
    apiPost({action:'search', q:q, limit:8}).then(function(r){
      var prods = r.ok ? r.products : [];
      if(!prods.length){ el.innerHTML='<div class="ma-sug" style="cursor:default;color:var(--sub)">No results</div>'; return; }
      el.innerHTML = prods.map(function(p){
        return '<div class="ma-sug" onclick="maAddTag(\''+type+'\',\''+e(p.model_id)+'\',true)">'
          +'<span class="ms-mid">'+e(p.model_id)+'</span>'
          +'<span class="ms-t">'+e(p.title_only||'')+'<span class="ms-c"> · '+e(p.product_class||'')+'</span></span>'
          +'</div>';
      }).join('');
    }).catch(function(){ el.innerHTML=''; });
  }, 260);
}

function maAddTagManual(type){
  var inp = document.getElementById(type==='req'?'maReqSearch':'maRecSearch');
  var mid = inp.value.trim();
  if(!mid) return;
  maAddTag(type, mid, false);
  inp.value='';
  document.getElementById(type==='req'?'maReqSuggestions':'maRecSuggestions').innerHTML='';
}

function maAddTag(type, mid, clearInput){
  mid = mid.trim();
  if(!mid) return;
  if(_maTags[type].indexOf(mid)<0) _maTags[type].push(mid);
  maRenderTags(type);
  if(clearInput){
    var inp = document.getElementById(type==='req'?'maReqSearch':'maRecSearch');
    if(inp) inp.value='';
    var sug = document.getElementById(type==='req'?'maReqSuggestions':'maRecSuggestions');
    if(sug) sug.innerHTML='';
  }
}

function maRemoveTag(type, mid){
  _maTags[type] = _maTags[type].filter(function(m){ return m!==mid; });
  maRenderTags(type);
}

function maRenderTags(type){
  var el = document.getElementById(type==='req'?'maReqTags':'maRecTags');
  if(!_maTags[type].length){ el.innerHTML='<span style="font-size:.63rem;color:var(--sub)">None added</span>'; return; }
  el.innerHTML = _maTags[type].map(function(mid){
    return '<span class="ma-tag">'+e(mid)+'<span class="mt-rm" onclick="maRemoveTag(\''+type+'\',\''+e(mid)+'\')">&#215;</span></span>';
  }).join('');
}

function saveManualItem(){
  var title = document.getElementById('maTitle2').value.trim();
  if(!title){ showMaErr('Title is required.'); return; }

  var mid = document.getElementById('maModelId').value.trim();
  if(!mid){
    var d=new Date(); mid='CUSTOM-'+d.toISOString().slice(0,10).replace(/-/g,'')+'-'+String(Math.floor(Math.random()*900)+100);
    document.getElementById('maModelId').value=mid;
  }

  var payload={
    model_id:          mid,
    title_only:        title,
    title_description: document.getElementById('maDesc').value.trim(),
    manufacturer:      document.getElementById('maMfr').value.trim(),
    product_class:     document.getElementById('maCls').value,
    intl_dist_net:     parseFloat(document.getElementById('maNet').value)||null,
    intl_market_price: parseFloat(document.getElementById('maList').value)||null,
    mfr_lead_time:     document.getElementById('maLead').value.trim(),
    dimensions:        document.getElementById('maDims').value.trim(),
    notes:             document.getElementById('maNotes').value.trim() || ('Manual entry '+new Date().toISOString().slice(0,10)),
    requires_models:   _maTags.req,
    recommended_models:_maTags.rec,
  };

  var btn=document.getElementById('maSaveBtn');
  btn.disabled=true; btn.textContent='Saving…';
  document.getElementById('maErr').style.display='none';

  payload.action = 'add_manual_product';
  apiPost(payload)
  .then(function(r){
    if(!r.ok){ showMaErr('Error: '+(r.error||'Save failed.')); btn.disabled=false; btn.textContent='✓ Save to Catalog'; return; }
    // Update prodMap immediately so the item reflects new req/rec without needing a search refresh
    if(prodMap[mid]){
      prodMap[mid].requires_models    = payload.requires_models;
      prodMap[mid].recommended_models = payload.recommended_models;
      prodMap[mid].title_only         = payload.title_only;
    }
    // If PDF file selected, upload it
    var pdfFile = document.getElementById('maPdfFile').files[0];
    if(pdfFile){
      uploadProductPdf(mid, pdfFile, 'replace', function(pr){
        closeManualAdd();
        cache={}; search();
        btn.disabled=false; btn.textContent='✓ Save to Catalog';
      });
    } else {
      closeManualAdd();
      cache={}; search();
      btn.disabled=false; btn.textContent='✓ Save to Catalog';
    }
  })
  .catch(function(err){ showMaErr('Error: '+err.message); btn.disabled=false; btn.textContent='✓ Save to Catalog'; });
}
function showMaErr(msg){ var el=document.getElementById('maErr'); el.textContent=msg; el.style.display='block'; }

/* ── PDF UPLOAD ── */
var pdfUploadMid = null;

function openPdfUpload(mid){
  pdfUploadMid = mid;
  var p = prodMap[mid] || {model_id:mid, title_only:''};
  document.getElementById('puModelLabel').textContent = mid + (p.title_only?' — '+p.title_only:'');
  document.getElementById('puFile').value='';
  document.getElementById('puPreview').style.display='none';
  document.getElementById('puProgress').style.display='none';
  document.getElementById('puSaveBtn').disabled=false;
  document.getElementById('puSaveBtn').textContent='⬆ Upload PDF';
  // Show/hide existing PDF indicator and merge/replace options
  var hasPdf = !!(docMap[mid]);
  var existRow = document.getElementById('puExistingRow');
  var modeRow  = document.getElementById('puModeRow');
  var existLink = document.getElementById('puExistingLink');
  existRow.style.display = hasPdf ? '' : 'none';
  modeRow.style.display  = hasPdf ? '' : 'none';
  if(hasPdf && existLink){
    existLink.href = 'api.php?action=get_document&mid='+encodeURIComponent(mid);
  }
  document.getElementById('puModeReplace').checked = true; // default to replace
  document.getElementById('pdfUploadModal').classList.add('open');
}
function closePdfUpload(){
  document.getElementById('pdfUploadModal').classList.remove('open');
  pdfUploadMid=null;
}
function puPreview(inp){
  var f=inp.files[0];
  if(!f){document.getElementById('puPreview').style.display='none';return;}
  document.getElementById('puFileName').textContent=f.name;
  document.getElementById('puFileSize').textContent=(f.size/1024).toFixed(1)+' KB';
  document.getElementById('puPreview').style.display='block';
}
function submitPdfUpload(){
  if(!pdfUploadMid) return;
  var f=document.getElementById('puFile').files[0];
  if(!f){alert('Select a PDF file first.');return;}
  var btn=document.getElementById('puSaveBtn');
  btn.disabled=true; btn.textContent='Uploading…';
  var modeEl = document.querySelector('input[name="puMode"]:checked');
  var mode = modeEl ? modeEl.value : 'replace';
  uploadProductPdf(pdfUploadMid, f, mode, function(r){
    var pr=document.getElementById('puProgress');
    if(r && r.ok){
      pr.textContent='✓ PDF '+(mode==='merge'?'merged':'uploaded')+' successfully!';
      pr.style.cssText='display:block;background:#e8f5e9;border:1px solid #c8e6c9;border-radius:5px;padding:8px 10px;font-size:.72rem;color:#2e7d32;margin-bottom:8px';
      docMap[pdfUploadMid]=true;
      cache={}; search();
      setTimeout(function(){ closePdfUpload(); },1500);
    } else {
      pr.textContent='✗ Upload failed: '+(r?r.error:'Connection error');
      pr.style.cssText='display:block;background:#fff0f0;border:1px solid #f0c0c0;border-radius:5px;padding:8px 10px;font-size:.72rem;color:#c04040;margin-bottom:8px';
      btn.disabled=false; btn.textContent='⬆ Upload PDF';
    }
  });
}
function uploadProductPdf(mid, file, mode, callback){
  var fd=new FormData();
  fd.append('action','upload_product_pdf');
  fd.append('model_id',mid);
  fd.append('pdf',file);
  fd.append('mode', mode || 'replace');
  fetch('api.php?action=upload_product_pdf',{method:'POST',body:fd})
    .then(function(r){
      var ct = r.headers.get('content-type')||'';
      if(ct.indexOf('application/json')>=0) return r.json();
      // Server returned non-JSON (PHP error page) — capture text for diagnostics
      return r.text().then(function(t){ return {ok:false, error:'Server error: '+t.substring(0,200)}; });
    })
    .then(function(r){ if(callback) callback(r); })
    .catch(function(err){ if(callback) callback({ok:false,error:err.message}); });
}

/* ── MULTIPLE DIVISORS ── */

function initDivisors(){
  // Sync first divisor value from the existing #div input
  var v=parseFloat(document.getElementById('div').value)||0.65;
  divisors[0].value=v;
  rebuildDivisorUI();
}

function addDivisor(){
  var n=divisors.length+1;
  divisors.push({key:'D'+n, name:'Divisor '+n, value:0.65});
  rebuildDivisorUI();
  renderQuote();
}

function removeDivisor(key){
  if(divisors.length<=1) return;
  divisors=divisors.filter(function(d){return d.key!==key;});
  // Reset any items using this divisor back to D1
  allItems().forEach(function(l){ if(l.divisorKey===key) l.divisorKey='D1'; });
  rebuildDivisorUI();
  renderQuote();
}

function rebuildDivisorUI(){
  var el=document.getElementById('divList');
  if(!el) return;
  el.innerHTML=divisors.map(function(dv,i){
    var col=divColors[i%divColors.length];
    return '<div class="div-chip" data-dk="'+dv.key+'">'
      +'<span class="div-badge" style="background:'+col+'">'+dv.key+'</span>'
      +'<input class="div-name" type="text" data-dk="'+dv.key+'" value="'+e(dv.name)+'" placeholder="Name" style="width:90px;font-weight:600">'
      +'<input class="div-val"'+(i===0?' id="div"':'')+' type="number" data-dk="'+dv.key+'" value="'+dv.value+'" min="0.001" max="99" step="0.01" style="width:56px;text-align:center">'
      +(i===0?'<span class="dp" id="dp" style="font-size:.62rem;color:var(--sub);white-space:nowrap">$'+fmt(1000/dv.value)+'</span>':'')
      +(divisors.length>1?'<button class="div-del" data-dk="'+dv.key+'" title="Remove divisor '+dv.key+'">&times;</button>':'')
      +'</div>';
  }).join('');
  // Attach change events — use closure variable for the dk, avoiding 'this' binding pitfalls
  el.querySelectorAll('.div-name').forEach(function(inp){
    var dk = inp.dataset.dk;
    inp.addEventListener('input',function(){
      var dv=divisors.find(function(d){return d.key===dk;});
      if(dv){dv.name=this.value; renderQuote();}
    });
  });
  el.querySelectorAll('.div-val').forEach(function(inp){
    var dk = inp.dataset.dk;
    inp.addEventListener('input',function(){
      var v=parseFloat(this.value)||0.65;
      var dv=divisors.find(function(d){return d.key===dk;});
      if(dv) dv.value=Math.max(0.001,v);
      if(dk==='D1' && document.getElementById('div')) document.getElementById('div').value=v;
      updDP(); recalc(); renderQuote();
    });
  });
  el.querySelectorAll('.div-del').forEach(function(btn){
    var dk = btn.dataset.dk;
    btn.addEventListener('click',function(){ removeDivisor(dk); });
  });
}

function itemDiv(line){
  // Return the divisor value for a specific line item
  var dk = line.divisorKey || 'D1';
  var dv = divisors.find(function(d){return d.key===dk;});
  return dv ? Math.max(0.001, dv.value) : getDiv();
}
// lineDiv: like itemDiv but sub-items with no explicit divisorKey inherit their parent's divisor
function lineDiv(line, sec){
  if(line.divisorKey){
    var dv=divisors.find(function(d){return d.key===line.divisorKey;});
    if(dv) return Math.max(0.001,dv.value);
  }
  if(line.isSubOf && sec){
    var parent=sec.items.find(function(l){return l.product.model_id===line.isSubOf;});
    if(parent) return lineDiv(parent,sec);
  }
  return getDiv();
}

/* ── CREATE PARENT SYSTEM ── */
var cparSid=null, cparMid=null;

function openCpar(sid,lid){
  cparSid=sid; cparMid=lid;
  var sec=sections.find(function(s){return s.id===sid;});
  var line=sec?sec.items.find(function(l){return (l.lid||l.product.model_id)===lid;}):null;
  var displayMid = line ? line.product.model_id : lid;
  document.getElementById('cparChildLabel').textContent=displayMid+(line&&line.product.title_only?' — '+line.product.title_only:'');
  document.getElementById('cparName').value='';
  document.getElementById('cparDesc').value='Includes the items listed below';
  document.getElementById('cparQty').value='1';
  document.getElementById('cparMoveAll').checked=false;
  document.getElementById('cparErr').style.display='none';
  document.getElementById('cparModal').classList.add('open');
  setTimeout(function(){document.getElementById('cparName').focus();},80);
}
function closeCpar(){document.getElementById('cparModal').classList.remove('open');cparSid=null;cparMid=null;}

/* Track how many TI- system IDs we've generated this session for uniqueness */
var _tiSysCtr = 0;
function genSynMid(name){
  // Extract initials from capitalized words (or words >= 3 chars as fallback)
  var words = name.split(/\s+/);
  var initials = words.map(function(w){ return /^[A-Z]/.test(w) ? w[0] : ''; }).join('');
  if(!initials) initials = words.map(function(w){ return w[0]||''; }).join('').toUpperCase();
  initials = initials.replace(/[^A-Z]/g,'').slice(0,6) || 'SYS';
  _tiSysCtr++;
  // Pad counter to 3 digits so IDs sort cleanly
  var n = String(_tiSysCtr).padStart(3,'0');
  return 'TI-'+initials+'-'+n;
}

function saveCpar(){
  var name=document.getElementById('cparName').value.trim();
  if(!name){var el=document.getElementById('cparErr');el.textContent='System name is required.';el.style.display='block';return;}
  var sec=sections.find(function(s){return s.id===cparSid;});
  if(!sec){closeCpar();return;}
  var moveAll=document.getElementById('cparMoveAll').checked;
  var qty=Math.max(1,parseInt(document.getElementById('cparQty').value)||1);
  var desc=document.getElementById('cparDesc').value.trim();

  // Generate TI-{INITIALS}-{seq} model ID
  var synMid = genSynMid(name);
  // Ensure no collision with existing items (rare but possible across sessions)
  while(allItems().find(function(l){return l.product.model_id===synMid;})){
    _tiSysCtr++; synMid = genSynMid(name);
  }

  // Find the index of the target item in the section
  var targetIdx=sec.items.findIndex(function(l){return (l.lid||l.product.model_id)===cparMid;});
  if(targetIdx<0){closeCpar();return;}

  // Create the synthetic parent product object
  var synProduct={
    model_id:     synMid,
    title_only:   name,
    description:  desc,
    intl_dist_net: null,
    isSynthetic:  true,
    manufacturer: '',
    product_class:'System',
    requires_models:[], recommended_models:[]
  };

  // Insert synthetic parent at the target item's position
  sec.items.splice(targetIdx, 0, {product:synProduct, qty:qty, isSubOf:null, subPricing:'included', isOptional:false});

  // Make the target item (already found via lid as targetIdx) a sub-item of the new parent
  var targetLine=sec.items[targetIdx]; // already at correct position after splice
  var cparModelId = targetLine ? targetLine.product.model_id : cparMid; // real model_id for isSubOf refs
  if(targetLine){targetLine.isSubOf=synMid; targetLine.subPricing='included';}

  // ALWAYS re-point any existing sub-items of the clicked item to the new parent
  // isSubOf stores model_id (not lid), so compare against cparModelId
  sec.items.forEach(function(l){
    if(l.isSubOf===cparModelId && l!==targetLine){
      l.isSubOf=synMid;
    }
  });

  // Optionally move all other standalone items in section as sub-items too
  if(moveAll){
    sec.items.forEach(function(l){
      if(l.product.model_id!==synMid && (l.lid||l.product.model_id)!==cparMid && !l.isSubOf && !l.product.isSynthetic){
        l.isSubOf=synMid; l.subPricing='included';
      }
    });
  }

  closeCpar();
  _scrollAfterRender=false; // Don't auto-scroll, stay at the new parent
  renderQuote();
}

/* ── HELPERS ── */
function getDiv(){ return divisors.length?Math.max(0.001,divisors[0].value):Math.max(0.01, parseFloat(document.getElementById('div').value)||0.65); }
function getDisc(){ return Math.min(1,Math.max(0,(parseFloat(document.getElementById('disc').value)||0)/100)); }
function syncQN(){ document.getElementById('qnd').textContent='Quote: '+document.getElementById('qn').value; }
function updDP(){ var d=getDiv(); document.getElementById('dp').textContent='e.g. $1,000 ÷ '+d+' = $'+fmt(1000/d); }
function fmt(n){ return parseFloat(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function fmtC(n){ var s={USD:'$',SAR:'﷼',AED:'د.إ',EUR:'€',GBP:'£'};var cur=document.getElementById('cur')?document.getElementById('cur').value:'USD';return (s[cur]||'$')+fmt(n); }
function e(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function q(s){ return '"'+String(s||'').replace(/"/g,'""')+'"'; }
function dlBlob(data,name,type){ var b=new Blob([data],{type});var u=URL.createObjectURL(b);var a=document.createElement('a');a.href=u;a.download=name;document.body.appendChild(a);a.click();document.body.removeChild(a);URL.revokeObjectURL(u); }
</script>

<!-- ═══════════════════════════════════════════════════════════════
     BUNDLE PICKER MODAL — Insert a pre-configured system into quote
     ═══════════════════════════════════════════════════════════════ -->
<div class="overlay" id="bundlePickerModal" style="display:none">
  <div style="background:#fff;border-radius:10px;width:560px;max-width:96vw;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 40px rgba(13,33,55,.22)">
    <div style="background:linear-gradient(135deg,var(--bd),var(--bm));padding:14px 18px;display:flex;align-items:center;justify-content:space-between">
      <div>
        <div style="color:#fff;font-weight:700;font-size:.95rem">⚡ Add System to Quote</div>
        <div style="color:rgba(255,255,255,.6);font-size:.68rem;margin-top:2px">Select a pre-configured system to insert all items at once</div>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <button onclick="openBundleManager()" style="font-size:.65rem;color:var(--tl);background:rgba(45,212,160,.12);border:1px solid rgba(45,212,160,.3);border-radius:4px;padding:4px 10px;cursor:pointer;font-family:inherit">⚙ Manage Systems</button>
        <button onclick="closeBundlePicker()" style="background:none;border:none;color:rgba(255,255,255,.6);font-size:1.2rem;cursor:pointer;line-height:1">×</button>
      </div>
    </div>
    <div style="padding:12px 16px;border-bottom:1px solid var(--bdr);background:#f4f8fc">
      <input type="search" id="bundlePickerSearch" placeholder="🔍 Search systems…" oninput="filterBundlePicker(this.value)"
        style="width:100%;padding:7px 10px;border:1.5px solid var(--bdr);border-radius:6px;font-size:.78rem;background:#fff">
    </div>
    <div id="bundlePickerList" style="flex:1;overflow-y:auto;padding:8px 12px;min-height:200px">
      <div style="text-align:center;padding:30px;color:var(--sub);font-size:.8rem">Loading systems…</div>
    </div>
    <div style="padding:10px 16px;border-top:1px solid var(--bdr);background:#f4f8fc;display:flex;justify-content:space-between;align-items:center">
      <span id="bundlePickerCount" style="font-size:.67rem;color:var(--sub)"></span>
      <div style="display:flex;gap:6px;align-items:center">
        <label style="font-size:.67rem;color:var(--sub)">Insert into section:</label>
        <select id="bundleTargetSec" style="font-size:.72rem;padding:4px 8px;border-radius:5px;border:1.5px solid var(--bdr)"></select>
        <button onclick="closeBundlePicker()" style="font-size:.67rem;padding:5px 12px;background:transparent;border:1.5px solid var(--bdr);border-radius:5px;cursor:pointer;font-family:inherit">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     BUNDLE MANAGER MODAL — Create / edit / delete systems
     ═══════════════════════════════════════════════════════════════ -->
<div class="overlay" id="bundleManagerModal" style="display:none">
  <div style="background:#fff;border-radius:10px;width:700px;max-width:96vw;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 40px rgba(13,33,55,.22)">
    <div style="background:linear-gradient(135deg,var(--bd),var(--bm));padding:14px 18px;display:flex;align-items:center;justify-content:space-between">
      <div style="color:#fff;font-weight:700;font-size:.95rem">⚙ System Bundle Manager</div>
      <button onclick="closeBundleManager()" style="background:none;border:none;color:rgba(255,255,255,.6);font-size:1.2rem;cursor:pointer;line-height:1">×</button>
    </div>

    <!-- List view -->
    <div id="bmListView" style="flex:1;display:flex;flex-direction:column;overflow:hidden">
      <div style="padding:10px 14px;border-bottom:1px solid var(--bdr);display:flex;justify-content:space-between;align-items:center;background:#f4f8fc">
        <span style="font-size:.72rem;color:var(--sub)">Manage pre-configured systems. Each system defines a main item and all required/included sub-items.</span>
        <button onclick="openBundleEditor(null)" class="btn bb2 bsm" style="white-space:nowrap">+ New System</button>
      </div>
      <div id="bmListBody" style="flex:1;overflow-y:auto;padding:8px 12px">
        <div style="text-align:center;padding:30px;color:var(--sub);font-size:.8rem">Loading…</div>
      </div>
    </div>

    <!-- Editor view -->
    <div id="bmEditorView" style="flex:1;display:none;flex-direction:column;overflow:hidden">
      <div style="padding:10px 14px;border-bottom:1px solid var(--bdr);background:#f4f8fc;display:flex;align-items:center;gap:8px">
        <button onclick="showBmList()" style="font-size:.67rem;padding:4px 10px;background:transparent;border:1.5px solid var(--bdr);border-radius:5px;cursor:pointer;font-family:inherit">← Back</button>
        <span id="bmEditorTitle" style="font-size:.82rem;font-weight:700;color:var(--bd)">New System</span>
      </div>
      <div style="flex:1;overflow-y:auto;padding:14px 16px">
        <input type="hidden" id="bmEditId">
        <div style="margin-bottom:10px">
          <label style="font-size:.67rem;text-transform:uppercase;letter-spacing:1px;color:var(--sub);display:block;margin-bottom:4px">System Name *</label>
          <input id="bmEditName" type="text" placeholder="e.g. Tabletop Mechatronics – Siemens S7-1500"
            style="width:100%;padding:8px 10px;border:1.5px solid var(--bdr);border-radius:6px;font-size:.82rem">
        </div>
        <div style="margin-bottom:12px">
          <label style="font-size:.67rem;text-transform:uppercase;letter-spacing:1px;color:var(--sub);display:block;margin-bottom:4px">Description (optional)</label>
          <textarea id="bmEditDesc" rows="2" placeholder="Brief description shown in the picker"
            style="width:100%;padding:8px 10px;border:1.5px solid var(--bdr);border-radius:6px;font-size:.78rem;font-family:inherit;resize:vertical"></textarea>
        </div>
        <div style="margin-bottom:8px;display:flex;align-items:center;justify-content:space-between">
          <label style="font-size:.67rem;text-transform:uppercase;letter-spacing:1px;color:var(--sub);font-weight:700">Items in this System</label>
          <div style="display:flex;gap:6px">
            <button onclick="triggerBmCsvImport()" style="font-size:.65rem;padding:4px 10px;background:#eef4f9;color:var(--bd);border:1.5px solid var(--bdr);border-radius:4px;cursor:pointer;font-family:inherit">📥 Import CSV</button>
            <button onclick="addBmItem()" style="font-size:.65rem;padding:4px 10px;background:var(--teal);color:#fff;border:none;border-radius:4px;cursor:pointer;font-family:inherit">+ Add Item</button>
          </div>
          <input type="file" id="bmCsvInput" accept=".csv" style="display:none" onchange="handleBmCsvFile(event)">
        </div>
        <div id="bmItemsList" style="border:1.5px solid var(--bdr);border-radius:6px;overflow:hidden;margin-bottom:12px">
          <div style="padding:20px;text-align:center;color:var(--sub);font-size:.78rem">No items yet. Click + Add Item.</div>
        </div>
        <div id="bmCsvReview" style="display:none;border:1.5px solid var(--bdr);border-radius:6px;overflow:hidden;margin-bottom:12px"></div>
        <div id="bmEditorError" style="display:none;background:#fff0f0;border:1px solid #f0c0c0;border-radius:5px;padding:8px 12px;font-size:.75rem;color:#c04040;margin-bottom:8px"></div>
      </div>
      <div style="padding:10px 16px;border-top:1px solid var(--bdr);background:#f4f8fc;display:flex;justify-content:flex-end;gap:8px">
        <button onclick="showBmList()" style="font-size:.72rem;padding:6px 16px;background:transparent;border:1.5px solid var(--bdr);border-radius:5px;cursor:pointer;font-family:inherit">Cancel</button>
        <button onclick="saveBundleEditor()" class="btn bb2 bsm" style="font-size:.72rem;padding:6px 16px">💾 Save System</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     CSV REPLACE PICKER — find a catalog match for a flagged model_id
     (e.g. base number entered but the catalog item has an extra suffix)
     ═══════════════════════════════════════════════════════════════ -->
<div class="overlay" id="csvReplaceModal" style="display:none">
  <div style="background:#fff;border-radius:10px;width:460px;max-width:94vw;max-height:80vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 40px rgba(13,33,55,.22)">
    <div style="background:linear-gradient(135deg,var(--bd),var(--bm));padding:12px 16px;display:flex;align-items:center;justify-content:space-between">
      <div id="csvReplaceTitle" style="color:#fff;font-weight:700;font-size:.85rem">Find replacement</div>
      <button onclick="closeCsvReplace()" style="background:none;border:none;color:rgba(255,255,255,.6);font-size:1.2rem;cursor:pointer;line-height:1">×</button>
    </div>
    <div style="padding:12px 14px;border-bottom:1px solid var(--bdr)">
      <input id="csvReplaceInput" type="text" placeholder="Search model number or title…"
        oninput="csvReplaceSearch(this.value)"
        style="width:100%;padding:8px 10px;border:1.5px solid var(--bdr);border-radius:6px;font-size:.82rem">
      <div style="font-size:.65rem;color:var(--sub);margin-top:4px">Often the same number with an extra suffix, e.g. 373-510 → 373-510-XAD.</div>
    </div>
    <div id="csvReplaceResults" style="flex:1;overflow-y:auto;padding:6px 10px">
      <p style="text-align:center;color:var(--sub);font-size:.78rem;padding:16px 0">Type to search the catalog…</p>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════════════════════
//  SYSTEM BUNDLE PICKER
// ════════════════════════════════════════════════════════════════
var _allBundles = [];

function openBundlePicker(){
  // Populate section selector
  var sel = document.getElementById('bundleTargetSec');
  sel.innerHTML = '';
  if(!sections.length){
    // Create a default section if none exist
    addSection('Section 1');
  }
  sections.forEach(function(s){
    var o = document.createElement('option');
    o.value = s.id;
    o.textContent = s.name || ('Section '+s.id);
    sel.appendChild(o);
  });
  document.getElementById('bundlePickerSearch').value = '';
  document.getElementById('bundlePickerModal').style.display = 'flex';
  loadBundlesForPicker();
}

function closeBundlePicker(){
  document.getElementById('bundlePickerModal').style.display = 'none';
}

function loadBundlesForPicker(){
  apiPost({action:'list_bundles'}).then(function(r){
    if(r.ok){ _allBundles = r.bundles || []; renderBundlePicker(_allBundles); }
    else { document.getElementById('bundlePickerList').innerHTML = '<div style="padding:20px;color:#c04040;font-size:.78rem">Error loading systems: '+(r.error||'unknown')+'</div>'; }
  });
}

function filterBundlePicker(q){
  if(!q.trim()){ renderBundlePicker(_allBundles); return; }
  var lq = q.toLowerCase();
  renderBundlePicker(_allBundles.filter(function(b){ return b.name.toLowerCase().includes(lq)||(b.description||'').toLowerCase().includes(lq); }));
}

function renderBundlePicker(bundles){
  var list = document.getElementById('bundlePickerList');
  document.getElementById('bundlePickerCount').textContent = bundles.length + ' system' + (bundles.length===1?'':'s');
  if(!bundles.length){
    list.innerHTML = '<div style="text-align:center;padding:30px;color:var(--sub);font-size:.8rem">No systems found.<br><a href="#" onclick="closeBundlePicker();openBundleManager();return false" style="color:var(--bm)">Create your first system →</a></div>';
    return;
  }
  list.innerHTML = bundles.map(function(b){
    var itemCount = (b.items||[]).length;
    var mainItems = (b.items||[]).filter(function(i){return !i.isSubOf;}).length;
    return '<div style="border:1.5px solid var(--bdr);border-radius:7px;padding:10px 12px;margin-bottom:6px;cursor:pointer;transition:all .12s" '
      + 'onmouseenter="this.style.borderColor=\'var(--teal)\';this.style.background=\'rgba(13,184,168,.04)\'" '
      + 'onmouseleave="this.style.borderColor=\'var(--bdr)\';this.style.background=\'\'" '
      + 'onclick="insertBundle('+b.id+')">'
      + '<div style="display:flex;align-items:center;justify-content:space-between">'
      + '<span style="font-weight:700;font-size:.82rem;color:var(--bd)">'+e(b.name)+'</span>'
      + '<span style="font-size:.65rem;color:var(--sub);background:#eef4f9;padding:2px 7px;border-radius:3px">'+itemCount+' item'+(itemCount===1?'':'s')+'</span>'
      + '</div>'
      + (b.description ? '<div style="font-size:.72rem;color:var(--sub);margin-top:3px">'+e(b.description)+'</div>' : '')
      + '</div>';
  }).join('');
}

function insertBundle(bundleId){
  var bundle = _allBundles.find(function(b){ return b.id === bundleId; });
  if(!bundle){ alert('Bundle not found'); return; }
  var sid = document.getElementById('bundleTargetSec').value;
  var sec = sections.find(function(s){ return s.id === sid; });
  if(!sec){ alert('Please select a section first'); return; }

  // Collect all model IDs we need to look up
  var mids = (bundle.items||[]).map(function(i){ return i.model_id; });
  if(!mids.length){ alert('This system has no items configured'); return; }

  apiPost({action:'get_bundle_products', model_ids: mids}).then(function(r){
    if(!r.ok){ alert('Error loading products: '+(r.error||'unknown')); return; }
    var prodMap = r.products || {};
    var missing = mids.filter(function(m){ return !prodMap[m]; });
    if(missing.length){
      if(!confirm('Warning: '+missing.length+' item(s) not found in catalog: '+missing.join(', ')+'\n\nInsert anyway with placeholder entries?')) return;
    }

    // Insert items in the order defined in the bundle, preserving isSubOf relationships
    var insertedLids = {};
    (bundle.items||[]).forEach(function(bi){
      var prod = prodMap[bi.model_id];
      if(!prod){
        // Create a placeholder product for missing items
        prod = {model_id: bi.model_id, title: bi.model_id+' (not found in catalog)', description:'', intl_dist_net:null, manufacturer:'', requires_models:[], recommended_models:[]};
      }
      var lid = Date.now().toString(36)+Math.random().toString(36).slice(2,6);
      insertedLids[bi.model_id] = lid;
      // Resolve isSubOf to the LID of the parent (if inserted in this batch)
      var resolvedParent = bi.isSubOf ? (insertedLids[bi.isSubOf] ? bi.isSubOf : null) : null;
      sec.items.push({
        product:     prod,
        qty:         bi.qty || 1,
        isSubOf:     resolvedParent,
        subPricing:  bi.subPricing || 'included',
        isOptional:  !!bi.isOptional,
        lid:         lid
      });
    });

    _scrollAfterRender = true;
    renderQuote();
    cache = {};
    closeBundlePicker();

    // Check PDF availability for all inserted items
    apiPost({action:'check_documents', ids: mids}).then(function(dr){
      if(dr.ok && dr.has_doc && dr.has_doc.length){
        dr.has_doc.forEach(function(mid){ docMap[mid]=true; });
        renderQuote();
      }
    });
  });
}

// ════════════════════════════════════════════════════════════════
//  BUNDLE MANAGER
// ════════════════════════════════════════════════════════════════
var _bmItems = []; // working copy of items in editor

function openBundleManager(){
  closeBundlePicker();
  document.getElementById('bundleManagerModal').style.display = 'flex';
  showBmList();
  loadBmList();
}

function closeBundleManager(){
  document.getElementById('bundleManagerModal').style.display = 'none';
}

function showBmList(){
  document.getElementById('bmListView').style.display = 'flex';
  document.getElementById('bmEditorView').style.display = 'none';
}

function showBmEditor(){
  document.getElementById('bmListView').style.display = 'none';
  document.getElementById('bmEditorView').style.display = 'flex';
}

function loadBmList(){
  apiPost({action:'list_bundles'}).then(function(r){
    _allBundles = r.bundles || [];
    var body = document.getElementById('bmListBody');
    if(!_allBundles.length){
      body.innerHTML = '<div style="text-align:center;padding:30px;color:var(--sub);font-size:.8rem">No systems yet.<br>Click <strong>+ New System</strong> to create one.</div>';
      return;
    }
    body.innerHTML = _allBundles.map(function(b){
      return '<div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1.5px solid var(--bdr);border-radius:6px;margin-bottom:5px">'
        + '<div style="flex:1">'
        + '<div style="font-weight:700;font-size:.8rem;color:var(--bd)">'+e(b.name)+'</div>'
        + (b.description?'<div style="font-size:.68rem;color:var(--sub);margin-top:1px">'+e(b.description)+'</div>':'')
        + '<div style="font-size:.63rem;color:var(--sub);margin-top:2px">'+(b.items||[]).length+' items · updated '+((b.updated_at||'').substring(0,10))+'</div>'
        + '</div>'
        + '<button onclick="openBundleEditor('+b.id+')" style="font-size:.65rem;padding:4px 10px;background:#eef4f9;border:1.5px solid var(--bdr);border-radius:4px;cursor:pointer;font-family:inherit">✏ Edit</button>'
        + '<button onclick="deleteBundle('+b.id+',\''+e(b.name).replace(/'/g,"\\'")+'\')" style="font-size:.65rem;padding:4px 10px;background:#fff0f0;border:1.5px solid #f0c0c0;border-radius:4px;cursor:pointer;font-family:inherit;color:#c04040">✕</button>'
        + '</div>';
    }).join('');
  });
}

function openBundleEditor(id){
  _bmItems = [];
  document.getElementById('bmEditId').value = id || '';
  document.getElementById('bmEditorError').style.display = 'none';
  document.getElementById('bmCsvReview').style.display = 'none';
  document.getElementById('bmCsvReview').innerHTML = '';
  document.getElementById('bmItemsList').style.display = 'block';
  if(id){
    var b = _allBundles.find(function(x){ return x.id===id; });
    if(b){
      document.getElementById('bmEditorTitle').textContent = 'Edit: '+b.name;
      document.getElementById('bmEditName').value = b.name;
      document.getElementById('bmEditDesc').value = b.description || '';
      _bmItems = (b.items||[]).map(function(i){ return Object.assign({},i); });
    }
  } else {
    document.getElementById('bmEditorTitle').textContent = 'New System';
    document.getElementById('bmEditName').value = '';
    document.getElementById('bmEditDesc').value = '';
  }
  renderBmItems();
  showBmEditor();
}

function renderBmItems(){
  var container = document.getElementById('bmItemsList');
  if(!_bmItems.length){
    container.innerHTML = '<div style="padding:20px;text-align:center;color:var(--sub);font-size:.78rem">No items yet. Click + Add Item.</div>';
    return;
  }
  // Build parent options for isSubOf selector
  var mainItems = _bmItems.filter(function(i){ return !i.isSubOf; });
  container.innerHTML = _bmItems.map(function(item, idx){
    var isMain = !item.isSubOf;
    var parentOpts = '<option value="">— Top-level item —</option>'
      + mainItems.filter(function(m){ return m.model_id !== item.model_id; }).map(function(m){
          return '<option value="'+e(m.model_id)+'"'+(item.isSubOf===m.model_id?' selected':'')+'>Sub of: '+e(m.model_id)+'</option>';
        }).join('');
    return '<div style="display:flex;align-items:center;gap:6px;padding:6px 8px;border-bottom:1px solid var(--bdr);'+(isMain?'background:#f9fbfd;':'')+'">'
      + '<span style="font-size:.65rem;color:var(--sub);min-width:16px">'+(isMain?'●':'⮡')+'</span>'
      + '<input type="text" value="'+e(item.model_id)+'" placeholder="Model ID" data-bmidx="'+idx+'" data-field="model_id"'
      + ' style="width:130px;padding:4px 7px;font-size:.72rem;border:1.5px solid var(--bdr);border-radius:4px;font-family:inherit"'
      + ' oninput="updateBmItem('+idx+',\'model_id\',this.value)">'
      + '<input type="number" value="'+(item.qty||1)+'" min="1" step="1" placeholder="Qty"'
      + ' style="width:52px;padding:4px 6px;font-size:.72rem;border:1.5px solid var(--bdr);border-radius:4px;font-family:inherit;text-align:center"'
      + ' oninput="updateBmItem('+idx+',\'qty\',parseInt(this.value)||1)">'
      + '<select onchange="updateBmItem('+idx+',\'isSubOf\',this.value||null)" style="font-size:.68rem;padding:3px 6px;border:1.5px solid var(--bdr);border-radius:4px">'+parentOpts+'</select>'
      + '<label style="font-size:.65rem;color:var(--sub);white-space:nowrap;display:flex;align-items:center;gap:3px">'
      + '<input type="checkbox" '+(item.isOptional?'checked':'')+' onchange="updateBmItem('+idx+',\'isOptional\',this.checked)"> Optional</label>'
      + '<button onclick="removeBmItem('+idx+')" style="margin-left:auto;font-size:.7rem;padding:2px 7px;background:#fff0f0;border:1px solid #f0c0c0;border-radius:3px;cursor:pointer;color:#c04040;font-family:inherit">✕</button>'
      + '</div>';
  }).join('');
}

function addBmItem(){
  _bmItems.push({model_id:'', qty:1, isSubOf:null, subPricing:'included', isOptional:false});
  renderBmItems();
  // Focus the new model_id input
  var inputs = document.querySelectorAll('#bmItemsList input[data-field="model_id"]');
  if(inputs.length) inputs[inputs.length-1].focus();
}

function removeBmItem(idx){
  var mid = _bmItems[idx].model_id;
  // Remove children of this item too
  _bmItems = _bmItems.filter(function(i,n){ return n!==idx && i.isSubOf!==mid; });
  renderBmItems();
}

function updateBmItem(idx, field, val){
  if(!_bmItems[idx]) return;
  _bmItems[idx][field] = val;
  if(field === 'isSubOf'){ _bmItems[idx].subPricing = val ? 'included' : 'included'; }
  // Re-render only for isSubOf changes (to update parent dropdowns)
  if(field === 'isSubOf') renderBmItems();
}

// ────────────────────────────────────────────────────────────────
//  CSV IMPORT — bulk-load system items from a spreadsheet export
//  Expected columns (header row optional): model_id, qty, parent_model_id, optional
//  Leave parent_model_id blank for a top-level item.
// ────────────────────────────────────────────────────────────────
var _csvParsedRows = [];   // working rows from the last CSV parse
var _csvFoundProducts = {}; // model_id -> product row, from get_bundle_products

function triggerBmCsvImport(){
  document.getElementById('bmCsvInput').value = '';
  document.getElementById('bmCsvInput').click();
}

function parseCsvLineCells(line){
  // Proper quoted-field CSV split, handles commas inside quoted description/notes fields
  var cells = []; var cur = ''; var inQuotes = false;
  for(var i=0; i<line.length; i++){
    var ch = line[i];
    if(ch === '"'){
      if(inQuotes && line[i+1] === '"'){ cur += '"'; i++; }
      else inQuotes = !inQuotes;
    } else if(ch === ',' && !inQuotes){
      cells.push(cur); cur = '';
    } else {
      cur += ch;
    }
  }
  cells.push(cur);
  return cells.map(function(c){ return c.trim(); });
}

function _findCol(headerLower, candidates){
  for(var i=0; i<headerLower.length; i++){
    for(var j=0; j<candidates.length; j++){
      if(headerLower[i].indexOf(candidates[j]) !== -1) return i;
    }
  }
  return -1;
}

var _csvHasParentCol = false;

function parseCsvText(text){
  var lines = text.split(/\r\n|\n|\r/).filter(function(l){ return l.trim().length; });
  if(!lines.length) return [];

  var firstCells = parseCsvLineCells(lines[0]);
  var headerLower = firstCells.map(function(h){ return h.toLowerCase(); });

  var colModel = _findCol(headerLower, ['model_id','model id','model/item','model','item number','item_number','sku','part number','part_number']);
  var colQty   = _findCol(headerLower, ['qty','quantity']);
  var colParent= _findCol(headerLower, ['parent_model_id','parent model','parent','sub of','sub_of','subof','included with']);
  var colOpt   = _findCol(headerLower, ['optional']);
  _csvHasParentCol = (colParent !== -1);

  // A header row exists if we found at least a model-ish column by name (not just position 0)
  var hasHeaderRow = colModel !== -1;
  var dataLines = hasHeaderRow ? lines.slice(1) : lines;
  if(!hasHeaderRow) colModel = 0; // no recognizable header — assume col 0 is the model number

  // IMPORTANT: only trust colQty/colParent/colOpt if a matching header was actually found.
  // Never fall back to a guessed column position for these — a plain catalog export
  // (title, description, manufacturer, price...) has no parent/qty concept at all, and
  // mis-reading a description column as "parent_model_id" produces false flags on every row.
  return dataLines.map(function(line, i){
    var cells = parseCsvLineCells(line);
    var modelId = (cells[colModel]||'').trim();
    var qty = (colQty>=0) ? (parseInt(cells[colQty],10)||1) : 1;
    var parent = (colParent>=0) ? (cells[colParent]||'').trim() : '';
    var optRaw = (colOpt>=0) ? (cells[colOpt]||'').trim().toLowerCase() : '';
    var optional = ['1','true','yes','y'].indexOf(optRaw) !== -1;
    return { rowNum: i+1, model_id: modelId, qty: qty, parent_model_id: parent||null, optional: optional };
  }).filter(function(r){ return r.model_id; });
}

function handleBmCsvFile(evt){
  var file = evt.target.files[0];
  if(!file) return;
  var reader = new FileReader();
  reader.onload = function(e){
    var rows = parseCsvText(e.target.result);
    if(!rows.length){ alert('No valid rows found in that CSV.'); return; }
    _csvParsedRows = rows;
    // Gather every model_id we need to check: row items + parent references + anything already in the editor
    var idsToCheck = [];
    rows.forEach(function(r){
      idsToCheck.push(r.model_id);
      if(r.parent_model_id) idsToCheck.push(r.parent_model_id);
    });
    idsToCheck = Array.from(new Set(idsToCheck));
    apiPost({action:'get_bundle_products', model_ids: idsToCheck}).then(function(res){
      _csvFoundProducts = (res && res.ok) ? (res.products||{}) : {};
      renderCsvReviewPanel();
    });
  };
  reader.readAsText(file);
}

function _csvRowStatus(row){
  var existingModelIds = new Set(_bmItems.map(function(i){ return i.model_id; }));
  var csvModelIds = new Set(_csvParsedRows.map(function(r){ return r.model_id; }));
  var issues = [];
  if(!_csvFoundProducts[row.model_id]) issues.push('Model not found in catalog');
  if(row.parent_model_id && !csvModelIds.has(row.parent_model_id) && !existingModelIds.has(row.parent_model_id)){
    issues.push('Parent "'+row.parent_model_id+'" not in this CSV or system');
  }
  return issues;
}

function renderCsvReviewPanel(){
  var panel = document.getElementById('bmCsvReview');
  var okCount = 0;
  var rowsHtml = _csvParsedRows.map(function(row, idx){
    var issues = _csvRowStatus(row);
    var clean = issues.length === 0;
    if(clean) okCount++;
    var checkedAttr = clean ? 'checked' : '';
    var rowBg = clean ? '' : 'background:#fff8e8;';
    var prodTitle = _csvFoundProducts[row.model_id] ? (_csvFoundProducts[row.model_id].title||'') : '';
    return '<div style="display:flex;align-items:flex-start;gap:8px;padding:6px 8px;border-bottom:1px solid var(--bdr);'+rowBg+'" data-csvidx="'+idx+'">'
      + '<input type="checkbox" '+checkedAttr+' onchange="_csvRows_toggleInclude('+idx+',this.checked)" style="margin-top:3px">'
      + '<div style="flex:1;min-width:0">'
      + '<div style="font-size:.75rem;font-weight:700;color:var(--bd)">'+e(row.model_id)+' <span style="font-weight:400;color:var(--sub)">x'+row.qty+(row.parent_model_id?' · sub of '+e(row.parent_model_id):'')+(row.optional?' · optional':'')+'</span></div>'
      + (prodTitle ? '<div style="font-size:.68rem;color:var(--sub)">'+e(prodTitle)+'</div>' : '')
      + (issues.length ? issues.map(function(msg){
          if(msg.indexOf('Parent') === 0){
            return '<div style="font-size:.68rem;color:#a06a00;margin-top:2px">⚠ '+e(msg)+'. '
              + '<a href="#" onclick="_csvRows_makeTopLevel('+idx+');return false" style="color:var(--bm)">Make top-level instead</a></div>';
          }
          if(msg.indexOf('Model not found') === 0){
            return '<div style="font-size:.68rem;color:#c04040;margin-top:2px">⚠ '+e(msg)+'. '
              + '<a href="#" onclick="openCsvReplace('+idx+');return false" style="color:var(--bm)">🔍 Find match in catalog</a></div>';
          }
          return '<div style="font-size:.68rem;color:#c04040;margin-top:2px">⚠ '+e(msg)+'. Row will be skipped unless you check it manually.</div>';
        }).join('') : '')
      + '</div></div>';
  }).join('');

  panel.innerHTML = '<div style="padding:8px 10px;background:#f4f8fc;border-bottom:1px solid var(--bdr);font-size:.72rem;color:var(--sub)">'
    + '<strong style="color:var(--bd)">Review before importing</strong> — '+okCount+' of '+_csvParsedRows.length+' rows look clean. '
    + 'Uncheck any row you don\'t want, or fix issues below. Pricing is never imported, it always comes live from the catalog.'
    + (_csvHasParentCol ? '' : '<br><em>No parent/sub-item column detected in this file — every row will import as a top-level item.</em>')
    + '</div>'
    + '<div style="max-height:280px;overflow-y:auto">'+rowsHtml+'</div>'
    + '<div style="padding:8px 10px;background:#f4f8fc;border-top:1px solid var(--bdr);display:flex;justify-content:flex-end;gap:8px">'
    + '<button onclick="cancelCsvImport()" style="font-size:.7rem;padding:5px 12px;background:transparent;border:1.5px solid var(--bdr);border-radius:5px;cursor:pointer;font-family:inherit">Cancel</button>'
    + '<button onclick="confirmCsvImport()" class="btn bb2 bsm" style="font-size:.7rem;padding:5px 14px">Import Checked Rows</button>'
    + '</div>';

  document.getElementById('bmItemsList').style.display = 'none';
  panel.style.display = 'block';
}

function _csvRows_toggleInclude(idx, checked){
  _csvParsedRows[idx]._include = checked;
}

function _csvRows_makeTopLevel(idx){
  _csvParsedRows[idx].parent_model_id = null;
  renderCsvReviewPanel();
}

function cancelCsvImport(){
  _csvParsedRows = [];
  _csvFoundProducts = {};
  closeCsvReplace();
  document.getElementById('bmCsvReview').style.display = 'none';
  document.getElementById('bmCsvReview').innerHTML = '';
  document.getElementById('bmItemsList').style.display = 'block';
}

function confirmCsvImport(){
  var panel = document.getElementById('bmCsvReview');
  var checkedIdx = Array.from(panel.querySelectorAll('input[type=checkbox]'))
    .map(function(cb, i){ return cb.checked ? i : null; })
    .filter(function(i){ return i !== null; });

  if(!checkedIdx.length){ alert('No rows checked to import.'); return; }

  checkedIdx.forEach(function(idx){
    var row = _csvParsedRows[idx];
    // Only keep parent reference if it still resolves after any manual fixes
    var existingModelIds = new Set(_bmItems.map(function(i){ return i.model_id; }));
    var csvModelIds = new Set(_csvParsedRows.map(function(r){ return r.model_id; }));
    var parent = row.parent_model_id;
    if(parent && !csvModelIds.has(parent) && !existingModelIds.has(parent)) parent = null;
    _bmItems.push({
      model_id: row.model_id,
      qty: row.qty,
      isSubOf: parent,
      subPricing: 'included',
      isOptional: !!row.optional
    });
  });

  cancelCsvImport();
  renderBmItems();
}

// ────────────────────────────────────────────────────────────────
//  FIND / SELECT / REPLACE — for flagged rows where the part number
//  entered doesn't match the catalog exactly (usually a missing suffix,
//  e.g. 373-510 typed but the catalog has it as 373-510-XAD)
// ────────────────────────────────────────────────────────────────
var _csvReplaceIdx = null;
var _csvReplaceTimer = null;
var _csvReplaceResultsData = [];

function openCsvReplace(idx){
  _csvReplaceIdx = idx;
  var row = _csvParsedRows[idx];
  document.getElementById('csvReplaceTitle').textContent = 'Find replacement for "'+row.model_id+'"';
  var inp = document.getElementById('csvReplaceInput');
  inp.value = row.model_id;
  document.getElementById('csvReplaceModal').style.display = 'flex';
  csvReplaceSearch(row.model_id);
  inp.focus();
  inp.select();
}

function closeCsvReplace(){
  document.getElementById('csvReplaceModal').style.display = 'none';
  _csvReplaceIdx = null;
}

function csvReplaceSearch(q){
  clearTimeout(_csvReplaceTimer);
  q = q.trim();
  var out = document.getElementById('csvReplaceResults');
  if(q.length < 2){ out.innerHTML = '<p style="text-align:center;color:var(--sub);font-size:.78rem;padding:16px 0">Type at least 2 characters…</p>'; return; }
  out.innerHTML = '<p style="text-align:center;color:var(--sub);font-size:.78rem;padding:16px 0">Searching…</p>';
  _csvReplaceTimer = setTimeout(function(){
    var qs = new URLSearchParams({action:'search', q:q, limit:10}).toString();
    apiFetch(qs).then(function(r){
      var results = (r && r.ok && r.results) ? r.results : [];
      _csvReplaceResultsData = results;
      if(!results.length){
        out.innerHTML = '<p style="text-align:center;color:var(--sub);font-size:.78rem;padding:16px 0">No matches found. Try fewer characters, e.g. just the base number.</p>';
        return;
      }
      out.innerHTML = results.map(function(p, i){
        var price = p.intl_dist_net ? '$'+parseFloat(p.intl_dist_net).toFixed(2) : (p.intl_market_price_note||'—');
        return '<div onclick="selectCsvReplace('+i+')" style="padding:8px 10px;border-bottom:1px solid var(--bdr);cursor:pointer" onmouseover="this.style.background=\'#f4f8fc\'" onmouseout="this.style.background=\'\'">'
          + '<div style="font-size:.78rem;font-weight:700;color:var(--bd)">'+e(p.model_id)+'<span style="float:right;font-weight:400;color:var(--sub)">'+price+'</span></div>'
          + '<div style="font-size:.7rem;color:var(--sub)">'+e(p.title_only||'')+(p.manufacturer?' · '+e(p.manufacturer):'')+'</div>'
          + '</div>';
      }).join('');
    });
  }, 260);
}

function selectCsvReplace(resultIdx){
  if(_csvReplaceIdx === null) return;
  var picked = _csvReplaceResultsData[resultIdx];
  if(!picked) return;
  var oldId = _csvParsedRows[_csvReplaceIdx].model_id;
  var newId = picked.model_id;

  _csvParsedRows[_csvReplaceIdx].model_id = newId;
  // Cascade — any other row that pointed at the old (unmatched) number as its
  // parent should now point at the corrected model_id
  _csvParsedRows.forEach(function(r){ if(r.parent_model_id === oldId) r.parent_model_id = newId; });

  // We already have the full product record from search, normalize the field
  // name to match what get_bundle_products returns (title vs title_only)
  picked.title = picked.title_only || '';
  _csvFoundProducts[newId] = picked;

  closeCsvReplace();
  renderCsvReviewPanel();
}

function saveBundleEditor(){
  var name = document.getElementById('bmEditName').value.trim();
  var desc = document.getElementById('bmEditDesc').value.trim();
  var id   = document.getElementById('bmEditId').value;
  var errEl = document.getElementById('bmEditorError');
  errEl.style.display = 'none';

  if(!name){ errEl.textContent='System name is required.'; errEl.style.display='block'; return; }
  var items = _bmItems.filter(function(i){ return i.model_id.trim(); });
  if(!items.length){ errEl.textContent='Add at least one item before saving.'; errEl.style.display='block'; return; }

  var payload = {action:'save_bundle', name:name, description:desc, items:items};
  if(id) payload.id = parseInt(id);

  apiPost(payload).then(function(r){
    if(r.ok){
      showBmList();
      loadBmList();
      // Also refresh the picker cache
      apiPost({action:'list_bundles'}).then(function(r2){ if(r2.ok) _allBundles = r2.bundles||[]; });
    } else {
      errEl.textContent = r.error || 'Save failed';
      errEl.style.display = 'block';
    }
  });
}

function deleteBundle(id, name){
  if(!confirm('Delete system "'+name+'"?\n\nThis cannot be undone.')) return;
  apiPost({action:'delete_bundle', id:id}).then(function(r){
    if(r.ok){ loadBmList(); apiPost({action:'list_bundles'}).then(function(r2){ if(r2.ok) _allBundles=r2.bundles||[]; }); }
    else alert('Delete failed: '+(r.error||'unknown'));
  });
}
</script>

</body>
</html>
