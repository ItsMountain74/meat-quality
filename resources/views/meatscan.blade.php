<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>MeatScan — Meat Freshness Detection</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,600&display=swap" rel="stylesheet">
  <style>
  :root {
    --red: #C0392B; --red-dark: #7B1818; --red-bright: #E74C3C;
    --cream: #F5EFE6; --charcoal: #1A1210; --muted: #8C6B65; --white: #FDFAF7;
    --green: #2ecc71; --amber: #f39c12;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  html { scroll-behavior:smooth; }
  body { background:var(--charcoal); color:var(--cream); font-family:'DM Sans',sans-serif; overflow-x:hidden; }

  /* NAV */
  nav {
    position:fixed; top:0; left:0; right:0; z-index:100;
    display:flex; align-items:center; justify-content:space-between;
    padding:1.1rem 3rem;
    background:rgba(26,18,16,0.92); backdrop-filter:blur(16px);
    border-bottom:1px solid rgba(192,57,43,0.2);
  }
  .nav-logo { font-family:'Bebas Neue',sans-serif; font-size:1.7rem; letter-spacing:4px; color:var(--red-bright); }
  .nav-logo span { color:var(--cream); }
  .nav-links { display:flex; gap:2rem; list-style:none; }
  .nav-links a { color:var(--muted); text-decoration:none; font-size:0.78rem; letter-spacing:2px; text-transform:uppercase; transition:color 0.3s; }
  .nav-links a:hover { color:var(--cream); }

  /* ══════════════════════════
     SCREEN 1 — SPLASH
  ══════════════════════════ */
  .splash {
    height:100vh;
    display:grid; grid-template-columns:1fr 1fr;
    align-items:center;
    position:relative; overflow:hidden;
    background:var(--charcoal);
    padding-top:70px;
  }
  .splash-text {
    padding:4rem 3rem 4rem 5rem;
    opacity:0; animation:fadeUp 1s 0.4s forwards;
    position:relative; z-index:2;
  }
  .splash-eyebrow {
    display:inline-flex; align-items:center; gap:0.6rem;
    font-size:0.68rem; letter-spacing:3px; text-transform:uppercase;
    color:var(--red-bright); margin-bottom:1.8rem;
  }
  .splash-eyebrow::before { content:''; width:24px; height:1px; background:var(--red-bright); }
  .splash-bigname {
    font-family:'Bebas Neue',sans-serif;
    font-size:clamp(5rem,9vw,9rem);
    line-height:0.88; letter-spacing:4px;
  }
  .splash-bigname .n1 { color:var(--red-bright); display:block; }
  .splash-bigname .n2 { color:var(--cream); display:block; }
  .splash-sub {
    margin-top:1.8rem;
    font-size:0.85rem; color:var(--muted); letter-spacing:1px; line-height:1.7;
    max-width:420px;
  }
  .splash-badges {
    display:flex; gap:0.6rem; flex-wrap:wrap; margin-top:1.8rem;
  }
  .badge {
    background:rgba(192,57,43,0.1); border:1px solid rgba(192,57,43,0.3);
    border-radius:2px; padding:0.35rem 0.8rem;
    font-size:0.65rem; letter-spacing:1.5px; text-transform:uppercase; color:var(--muted);
  }
  .splash-mockup {
    position:relative; height:100%;
    opacity:0; animation:riseUp 1.1s 0.2s cubic-bezier(0.16,1,0.3,1) forwards;
    overflow:hidden;
  }
  .meat-img {
    width:100%; height:100%;
    object-fit:cover; object-position:center;
    display:block;
  }
  .splash-mockup::before {
    content:'';
    position:absolute; inset:0; z-index:1;
    background:linear-gradient(90deg, var(--charcoal) 0%, rgba(26,18,16,0.3) 30%, transparent 60%);
  }
  .splash-mockup::after {
    content:'';
    position:absolute; top:0; left:0; right:0; height:2px; z-index:2;
    background:linear-gradient(90deg, transparent 5%, var(--red-bright) 50%, transparent 95%);
    animation:bigScan 3s ease-in-out infinite;
    box-shadow:0 0 16px rgba(231,76,60,0.6);
  }
  @keyframes bigScan { 0%{top:0;opacity:1} 100%{top:100%;opacity:0.3} }
  .scan-corner { position:absolute; width:24px; height:24px; z-index:3; }
  .scan-corner.tl { top:20px; left:20px; border-top:2px solid var(--red-bright); border-left:2px solid var(--red-bright); }
  .scan-corner.tr { top:20px; right:20px; border-top:2px solid var(--red-bright); border-right:2px solid var(--red-bright); }
  .scan-corner.bl { bottom:20px; left:20px; border-bottom:2px solid var(--red-bright); border-left:2px solid var(--red-bright); }
  .scan-corner.br { bottom:20px; right:20px; border-bottom:2px solid var(--red-bright); border-right:2px solid var(--red-bright); }
  .scan-result {
    position:absolute; bottom:2.5rem; right:2rem; z-index:4;
    background:rgba(26,18,16,0.92); border:1px solid rgba(46,204,113,0.4);
    border-radius:10px; padding:0.8rem 1.2rem;
    backdrop-filter:blur(12px);
    display:flex; flex-direction:column; gap:0.3rem;
    animation:floatCard 4s ease-in-out infinite;
  }
  @keyframes floatCard { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
  .sr-label { font-size:0.55rem; color:var(--muted); letter-spacing:2px; text-transform:uppercase; }
  .sr-value { font-family:'Bebas Neue',sans-serif; font-size:1.3rem; letter-spacing:3px; color:#2ecc71; display:flex; align-items:center; gap:8px; }
  .sr-dot { width:7px; height:7px; border-radius:50%; background:#2ecc71; box-shadow:0 0 8px rgba(46,204,113,0.7); animation:blink 1.5s infinite; }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
  .scan-conf {
    position:absolute; top:2.5rem; right:2rem; z-index:4;
    background:rgba(26,18,16,0.92); border:1px solid rgba(192,57,43,0.3);
    border-radius:10px; padding:0.8rem 1.2rem;
    backdrop-filter:blur(12px);
    animation:floatCard 4s 2s ease-in-out infinite;
  }
  .sc-label { font-size:0.55rem; color:var(--muted); letter-spacing:2px; text-transform:uppercase; margin-bottom:0.2rem; }
  .sc-value { font-family:'Bebas Neue',sans-serif; font-size:1.3rem; color:var(--cream); letter-spacing:2px; }
  .sc-value span { color:var(--red-bright); }
  .scroll-hint {
    position:absolute; bottom:2rem; left:50%; transform:translateX(-50%);
    display:flex; flex-direction:column; align-items:center; gap:0.4rem;
    opacity:0; animation:fadeIn 1s 1.6s forwards; z-index:5;
  }
  .scroll-hint span { font-size:0.6rem; letter-spacing:3px; text-transform:uppercase; color:var(--muted); }
  .scroll-line { width:1px; height:36px; background:linear-gradient(180deg,var(--red-bright),transparent); animation:pulse 2s ease-in-out infinite; }
  @keyframes pulse { 0%,100%{opacity:0.3} 50%{opacity:1} }

  /* Shared section styles */
  .section-eyebrow {
    display:inline-flex; align-items:center; gap:0.8rem;
    font-size:0.7rem; letter-spacing:3px; text-transform:uppercase;
    color:var(--red-bright); margin-bottom:1.5rem;
  }
  .section-eyebrow::before { content:''; width:30px; height:1px; background:var(--red-bright); }
  .section-label { font-size:0.7rem; letter-spacing:3px; text-transform:uppercase; color:var(--red-bright); margin-bottom:1rem; }
  .section-title { font-family:'Bebas Neue',sans-serif; font-size:clamp(2.5rem,5vw,4.5rem); line-height:1; letter-spacing:1px; margin-bottom:2.5rem; }

  /* SCREEN 2 — ABOUT */
  .about {
    min-height:100vh; display:flex; flex-direction:column; justify-content:center;
    padding:8rem 3rem;
    background:linear-gradient(180deg,#1f0e0e 0%,var(--charcoal) 100%);
    border-top:1px solid rgba(192,57,43,0.15);
    position:relative; overflow:hidden;
  }
  .about::before {
    content:''; position:absolute; top:-200px; right:-200px;
    width:600px; height:600px; border-radius:50%;
    background:radial-gradient(circle,rgba(192,57,43,0.08) 0%,transparent 70%);
    pointer-events:none;
  }
  .about-inner { max-width:900px; margin:0 auto; width:100%; }
  .about-title {
    font-family:'Bebas Neue',sans-serif;
    font-size:clamp(3rem,6vw,5.5rem);
    letter-spacing:2px; line-height:0.95; margin-bottom:2.5rem;
  }
  .about-title span { color:var(--red-bright); }
  .about-body { display:grid; grid-template-columns:1.2fr 1fr; gap:4rem; align-items:start; }
  .about-text p { color:var(--muted); font-size:1.05rem; line-height:1.9; font-weight:300; margin-bottom:1.2rem; }
  .about-text p strong { color:var(--cream); font-weight:600; }
  .about-stats { display:flex; flex-direction:column; gap:1.2rem; }
  .astat {
    padding:1.4rem 1.6rem;
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(192,57,43,0.15); border-left:3px solid var(--red);
    border-radius:4px; transition:background 0.3s;
  }
  .astat:hover { background:rgba(192,57,43,0.05); }
  .astat-num { font-family:'Bebas Neue',sans-serif; font-size:2.4rem; color:var(--cream); letter-spacing:1px; display:block; }
  .astat-label { font-size:0.75rem; color:var(--muted); letter-spacing:1.5px; text-transform:uppercase; }

  /* SCREEN 3 — DATASET */
  .dataset {
    background:linear-gradient(180deg,var(--charcoal) 0%,#1f0e0e 100%);
    border-top:1px solid rgba(192,57,43,0.15);
    padding:7rem 3rem;
  }
  .dataset-inner { max-width:1000px; margin:0 auto; }
  .dataset-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1.5rem; margin-bottom:2.5rem; }
  .dataset-stat {
    background:rgba(192,57,43,0.07); border:1px solid rgba(192,57,43,0.2);
    border-radius:8px; padding:2rem 1.5rem; text-align:center;
  }
  .dataset-stat-num { font-family:'Bebas Neue',sans-serif; font-size:3rem; color:var(--red-bright); display:block; letter-spacing:2px; }
  .dataset-stat-label { font-size:0.8rem; color:var(--muted); letter-spacing:1.5px; text-transform:uppercase; margin-top:0.3rem; }
  .dataset-info { display:grid; grid-template-columns:1fr 1fr; gap:2rem; }
  .info-label { font-size:0.7rem; letter-spacing:2px; text-transform:uppercase; color:var(--red-bright); margin-bottom:0.5rem; }
  .info-text { color:var(--muted); font-size:0.95rem; line-height:1.7; }

  /* SCREEN 4 — METHODOLOGY */
  .method {
    background:var(--charcoal);
    border-top:1px solid rgba(192,57,43,0.15);
    padding:7rem 3rem;
  }
  .method-inner { max-width:1000px; margin:0 auto; }
  .method-steps { display:flex; gap:1.6rem; position:relative; }
  .method-steps::before { content:''; position:absolute; left:18px; top:28px; bottom:28px; width:1px; background:rgba(192,57,43,0.2); }
  .step {
    flex:1;
    background:rgba(255,255,255,0.02); border:1px solid rgba(192,57,43,0.12);
    border-radius:10px; padding:1.5rem 1.4rem;
    position:relative;
  }
  .step-num {
    width:38px; height:38px; border-radius:10px;
    background:rgba(192,57,43,0.12); border:1px solid rgba(192,57,43,0.2);
    display:flex; align-items:center; justify-content:center;
    font-family:'Bebas Neue',sans-serif; letter-spacing:2px; color:var(--red-bright);
    margin-bottom:1rem;
  }
  .step-title { font-family:'Bebas Neue',sans-serif; letter-spacing:2px; font-size:1.3rem; margin-bottom:0.6rem; }
  .step-text { color:var(--muted); font-size:0.92rem; line-height:1.7; font-weight:300; }

  /* SCREEN 5 — SCANNER */
  .scanner-section {
    background:linear-gradient(180deg,#1f0e0e 0%,var(--charcoal) 100%);
    border-top:1px solid rgba(192,57,43,0.15);
    padding:7rem 3rem;
    position:relative; overflow:hidden;
  }
  .scanner-section::before {
    content:''; position:absolute; bottom:-280px; left:-280px;
    width:700px; height:700px; border-radius:50%;
    background:radial-gradient(circle,rgba(192,57,43,0.10) 0%,transparent 70%);
    pointer-events:none;
  }
  .scanner-inner { max-width:760px; width:100%; text-align:center; position:relative; z-index:1; margin:0 auto; }
  .scanner-header { margin-bottom:2.5rem; }
  .upload-zone {
    width:100%; aspect-ratio:4/3; max-height:380px;
    border:2px dashed rgba(192,57,43,0.35); border-radius:16px;
    background:rgba(192,57,43,0.04);
    position:relative; overflow:hidden;
    display:flex; flex-direction:column; align-items:center; justify-content:center; gap:1rem;
    cursor:pointer; transition:border-color 0.3s, background 0.3s;
  }
  .upload-zone:hover { border-color:rgba(192,57,43,0.7); background:rgba(192,57,43,0.08); }
  .upload-zone.dragover { border-color:var(--red-bright); background:rgba(192,57,43,0.12); }
  .upload-zone .scan-line {
    position:absolute; top:0; left:0; right:0; height:2px;
    background:linear-gradient(90deg,transparent,var(--red-bright),transparent);
    animation:scanZone 3s ease-in-out infinite; opacity:0.4;
  }
  @keyframes scanZone { 0%{top:0} 100%{top:100%} }
  .uz-corner { position:absolute; width:24px; height:24px; }
  .uz-corner.tl { top:12px;left:12px; border-top:2px solid var(--red-bright); border-left:2px solid var(--red-bright); }
  .uz-corner.tr { top:12px;right:12px; border-top:2px solid var(--red-bright); border-right:2px solid var(--red-bright); }
  .uz-corner.bl { bottom:12px;left:12px; border-bottom:2px solid var(--red-bright); border-left:2px solid var(--red-bright); }
  .uz-corner.br { bottom:12px;right:12px; border-bottom:2px solid var(--red-bright); border-right:2px solid var(--red-bright); }
  .upload-icon { font-size:2.8rem; opacity:0.5; }
  .upload-label { font-size:0.9rem; color:var(--muted); }
  .upload-label strong { color:var(--cream); display:block; font-size:1rem; margin-bottom:0.3rem; }
  .upload-sub { font-size:0.7rem; color:rgba(140,107,101,0.5); letter-spacing:2px; text-transform:uppercase; }
  #fileInput { display:none; }
  #previewImg { display:none; width:100%; height:100%; object-fit:cover; position:absolute; inset:0; border-radius:14px; }

  .result-badge {
    display:none; margin-top:1.5rem; padding:1.1rem 2rem;
    border-radius:8px; font-family:'Bebas Neue',sans-serif;
    font-size:1.6rem; letter-spacing:3px; text-align:center;
    animation:fadeUp 0.5s forwards;
  }
  .result-badge.fresh { background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.35); color:var(--green); }
  .result-badge.spoiled { background:rgba(192,57,43,0.1); border:1px solid rgba(192,57,43,0.35); color:var(--red-bright); }
  .result-badge.uncertain { background:rgba(243,156,18,0.10); border:1px solid rgba(243,156,18,0.35); color:var(--amber); }

  .result-panel {
    display:none;
    margin-top:1.2rem;
    text-align:left;
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(192,57,43,0.12);
    border-radius:14px;
    padding:1.4rem 1.4rem;
    animation:fadeUp 0.45s forwards;
  }
  .rp-row { display:flex; flex-wrap:wrap; gap:0.8rem; align-items:flex-start; justify-content:space-between; }
  .rp-title {
    font-family:'Bebas Neue',sans-serif;
    font-size:2.0rem;
    letter-spacing:2px;
    line-height:1;
  }
  .rp-meta { display:flex; gap:0.7rem; flex-wrap:wrap; }
  .rp-pill {
    font-size:0.65rem; letter-spacing:2px; text-transform:uppercase;
    padding:0.35rem 0.7rem;
    border-radius:999px;
    border:1px solid rgba(245,239,230,0.12);
    color:var(--muted);
  }
  .rp-pill strong { color:var(--cream); font-weight:600; letter-spacing:1px; }
  .rp-body { margin-top:1rem; display:grid; grid-template-columns:1fr; gap:1rem; }
  .rp-block { padding:1rem 1rem; background:rgba(192,57,43,0.04); border:1px solid rgba(192,57,43,0.10); border-radius:12px; }
  .rp-h { font-size:0.65rem; letter-spacing:2px; text-transform:uppercase; color:var(--red-bright); margin-bottom:0.5rem; }
  .rp-p { color:var(--muted); font-size:0.92rem; line-height:1.7; font-weight:300; }
  .rp-list { margin:0.4rem 0 0 1rem; }
  .rp-list li { color:var(--muted); font-size:0.90rem; line-height:1.7; font-weight:300; margin:0.2rem 0; }

  .scan-btn {
    display:none; margin-top:1.5rem; width:100%;
    padding:1.1rem 2rem; background:var(--red); color:var(--white);
    border:none; border-radius:8px;
    font-family:'Bebas Neue',sans-serif; font-size:1.3rem; letter-spacing:4px;
    cursor:pointer; transition:background 0.3s, transform 0.2s, box-shadow 0.3s;
    box-shadow:0 4px 20px rgba(192,57,43,0.3);
  }
  .scan-btn:hover { background:var(--red-bright); transform:translateY(-2px); box-shadow:0 8px 28px rgba(192,57,43,0.5); }
  .scan-btn.visible { display:block; }
  .scan-btn:disabled { opacity:0.7; cursor:not-allowed; transform:none; box-shadow:0 4px 20px rgba(192,57,43,0.2); }

  .reset-btn {
    display:none; margin-top:0.8rem; width:100%;
    background:none; border:1px solid rgba(245,239,230,0.12);
    color:var(--muted); border-radius:6px;
    padding:0.7rem 1.5rem;
    font-family:'DM Sans',sans-serif; font-size:0.8rem; letter-spacing:1.5px; text-transform:uppercase;
    cursor:pointer; transition:border-color 0.3s;
  }
  .reset-btn:hover { border-color:var(--muted); }
  .reset-btn.visible { display:block; }

  /* FEATURES */
  .features {
    background:linear-gradient(180deg,var(--charcoal) 0%,#1f0e0e 100%);
    border-top:1px solid rgba(192,57,43,0.15);
    padding:7rem 3rem;
  }
  .features-inner { max-width:1000px; margin:0 auto; }
  .feature-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1.2rem; }
  .feature-card {
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(192,57,43,0.12);
    border-radius:14px;
    padding:1.6rem 1.4rem;
    transition:transform 0.25s, background 0.25s, border-color 0.25s;
  }
  .feature-card:hover { transform:translateY(-4px); background:rgba(192,57,43,0.04); border-color:rgba(192,57,43,0.22); }
  .feature-kicker { font-size:0.65rem; letter-spacing:2px; text-transform:uppercase; color:var(--red-bright); margin-bottom:0.6rem; }
  .feature-title { font-family:'Bebas Neue',sans-serif; letter-spacing:2px; font-size:1.6rem; margin-bottom:0.6rem; }
  .feature-text { color:var(--muted); font-size:0.92rem; line-height:1.7; font-weight:300; }

  /* STATS */
  .stats {
    background:var(--charcoal);
    border-top:1px solid rgba(192,57,43,0.15);
    padding:7rem 3rem;
  }
  .stats-inner { max-width:1000px; margin:0 auto; }
  .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1.2rem; }
  .stat {
    background:rgba(192,57,43,0.05);
    border:1px solid rgba(192,57,43,0.12);
    border-radius:14px;
    padding:1.4rem 1.2rem;
  }
  .stat-num { font-family:'Bebas Neue',sans-serif; font-size:2.8rem; letter-spacing:2px; color:var(--cream); display:block; }
  .stat-num span { color:var(--red-bright); }
  .stat-lbl { font-size:0.65rem; letter-spacing:2px; text-transform:uppercase; color:var(--muted); margin-top:0.25rem; }

  /* FAQ */
  .faq {
    background:linear-gradient(180deg,#1f0e0e 0%,var(--charcoal) 100%);
    border-top:1px solid rgba(192,57,43,0.15);
    padding:7rem 3rem;
  }
  .faq-inner { max-width:900px; margin:0 auto; }
  .faq-list { display:flex; flex-direction:column; gap:0.8rem; }
  .faq-item {
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(192,57,43,0.12);
    border-radius:14px;
    overflow:hidden;
  }
  .faq-q {
    width:100%;
    display:flex; align-items:center; justify-content:space-between;
    gap:1rem;
    padding:1.1rem 1.2rem;
    background:transparent;
    border:none;
    color:var(--cream);
    cursor:pointer;
    text-align:left;
  }
  .faq-q span {
    font-family:'Bebas Neue',sans-serif;
    font-size:1.4rem;
    letter-spacing:2px;
  }
  .faq-toggle { color:var(--muted); font-size:1.1rem; }
  .faq-a {
    display:none;
    padding:0 1.2rem 1.2rem;
    color:var(--muted);
    font-size:0.95rem;
    line-height:1.8;
    font-weight:300;
  }
  .faq-item.open .faq-a { display:block; animation:fadeIn 0.25s ease-out; }

  /* CONTACT */
  .contact {
    background:var(--charcoal);
    border-top:1px solid rgba(192,57,43,0.15);
    padding:7rem 3rem;
  }
  .contact-inner { max-width:1000px; margin:0 auto; display:grid; grid-template-columns:1.2fr 1fr; gap:2rem; align-items:start; }
  .contact-card {
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(192,57,43,0.12);
    border-radius:14px;
    padding:1.6rem 1.4rem;
  }
  .contact-line { color:var(--muted); font-size:0.95rem; line-height:1.8; font-weight:300; margin-top:0.4rem; }
  .contact-form { display:flex; flex-direction:column; gap:0.9rem; }
  .contact-form input, .contact-form textarea {
    width:100%;
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(192,57,43,0.2);
    border-radius:10px;
    padding:0.8rem 0.9rem;
    color:var(--cream);
    outline:none;
    font-family:'DM Sans',sans-serif;
    font-size:0.92rem;
  }
  .contact-form textarea { min-height:130px; resize:vertical; }
  .contact-form input:focus, .contact-form textarea:focus { border-color:rgba(192,57,43,0.6); }
  .contact-btn {
    width:100%;
    background:var(--red);
    border:none;
    border-radius:10px;
    padding:0.9rem 1.0rem;
    color:var(--white);
    font-family:'Bebas Neue',sans-serif;
    font-size:1.2rem;
    letter-spacing:3px;
    cursor:pointer;
    transition:background 0.2s, transform 0.2s;
  }
  .contact-btn:hover { background:var(--red-bright); transform:translateY(-1px); }

  /* FOOTER */
  footer {
    background:#0e0808; border-top:1px solid rgba(192,57,43,0.15);
    padding:2.5rem 3rem;
    display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;
  }
  .footer-logo { font-family:'Bebas Neue',sans-serif; font-size:1.4rem; letter-spacing:4px; color:var(--red-bright); }
  .footer-logo span { color:var(--muted); }
  .footer-meta { font-size:0.75rem; color:var(--muted); letter-spacing:1px; }

  /* ANIMATIONS */
  @keyframes riseUp { from{opacity:0;transform:translateY(60px) scale(0.95)} to{opacity:1;transform:translateY(0) scale(1)} }
  @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
  @keyframes fadeIn { from{opacity:0} to{opacity:1} }
  .reveal { opacity:0; transform:translateY(40px); transition:opacity 0.9s, transform 0.9s; }
  .reveal.visible { opacity:1; transform:translateY(0); }

  /* ══ AUTH STYLES (from design, kept) ══ */
  .nav-login-btn {
    background:rgba(192,57,43,0.15); border:1px solid rgba(192,57,43,0.4);
    color:var(--cream); font-family:'DM Sans',sans-serif;
    font-size:0.7rem; letter-spacing:2px; text-transform:uppercase;
    padding:0.45rem 1.1rem; border-radius:3px; cursor:pointer; transition:all 0.3s;
  }
  .nav-login-btn:hover { background:rgba(192,57,43,0.3); }
  .overlay-backdrop {
    position:fixed; inset:0; z-index:999;
    background:rgba(10,4,4,0.85); backdrop-filter:blur(8px);
    display:none; align-items:center; justify-content:center;
  }
  .auth-card {
    background:#130806; border:1px solid rgba(192,57,43,0.25);
    border-radius:16px; padding:2.5rem; width:min(420px,90vw);
    position:relative; box-shadow:0 40px 80px rgba(0,0,0,0.6);
  }
  .auth-close {
    position:absolute; top:1rem; right:1rem;
    background:transparent; border:none; color:var(--muted);
    font-size:1.2rem; cursor:pointer; transition:color 0.2s;
  }
  .auth-close:hover { color:var(--cream); }
  .auth-logo { font-family:'Bebas Neue',sans-serif; font-size:1.6rem; letter-spacing:4px; margin-bottom:0.3rem; }
  .auth-logo span { color:var(--red-bright); }
  .auth-title { font-family:'Bebas Neue',sans-serif; font-size:2rem; letter-spacing:2px; color:var(--cream); margin-bottom:0.3rem; }
  .auth-sub { font-size:0.78rem; color:var(--muted); margin-bottom:1.8rem; letter-spacing:0.5px; }
  .auth-page { display:none; }
  .auth-page.active { display:block; }
  .auth-field { margin-bottom:1rem; }
  .auth-field label { display:block; font-size:0.65rem; letter-spacing:2px; text-transform:uppercase; color:var(--muted); margin-bottom:0.4rem; }
  .auth-field input {
    width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(192,57,43,0.2);
    border-radius:6px; padding:0.65rem 0.9rem;
    color:var(--cream); font-family:'DM Sans',sans-serif; font-size:0.88rem;
    outline:none; transition:border-color 0.2s;
  }
  .auth-field input:focus { border-color:rgba(192,57,43,0.6); }
  .auth-btn {
    width:100%; background:var(--red); border:none; border-radius:6px;
    padding:0.75rem; color:var(--cream); font-family:'Bebas Neue',sans-serif;
    font-size:1.1rem; letter-spacing:3px; cursor:pointer; transition:background 0.2s; margin-top:0.5rem;
  }
  .auth-btn:hover { background:var(--red-bright); }
  .auth-link { font-size:0.75rem; color:var(--muted); margin-top:1rem; text-align:center; }
  .auth-link a { color:var(--red-bright); cursor:pointer; text-decoration:none; }
  .auth-link a:hover { color:var(--cream); }
  .auth-error { font-size:0.75rem; display:none; margin-bottom:0.8rem; border-radius:4px; padding:0.4rem 0.6rem; background:rgba(231,76,60,0.08); }
  .auth-divider { border:none; border-top:1px solid rgba(192,57,43,0.1); margin:1.5rem 0; }

  /* Toast */
  .toast {
    position:fixed; bottom:2rem; left:50%; transform:translateX(-50%) translateY(20px);
    background:#1a1210; border:1px solid rgba(192,57,43,0.3);
    color:var(--cream); padding:0.7rem 1.4rem; border-radius:8px;
    font-size:0.8rem; letter-spacing:0.5px; opacity:0; transition:all 0.4s;
    z-index:2000; white-space:nowrap;
  }
  .toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

  /* RESPONSIVE */
  @media (max-width:900px) {
    nav { padding:1rem 1.5rem; }
    .nav-links { display:none; }
    .splash { grid-template-columns:1fr; }
    .splash-text { padding:6rem 1.5rem 2rem; }
    .splash-badges { justify-content:flex-start; }
    .splash-mockup { height:50vw; min-height:260px; }
    .scan-conf, .scan-result { padding:0.5rem 0.8rem; }
    .about,.dataset,.method,.scanner-section,.features,.stats,.faq,.contact { padding:5rem 1.5rem; }
    .about-body { grid-template-columns:1fr; gap:2rem; }
    .dataset-grid { grid-template-columns:1fr 1fr; }
    .dataset-info { grid-template-columns:1fr; }
    .method-steps { flex-direction:column; gap:1rem; }
    .method-steps::before { display:none; }
    .feature-grid { grid-template-columns:1fr; }
    .stats-grid { grid-template-columns:1fr 1fr; }
    .contact-inner { grid-template-columns:1fr; }
    footer { padding:2rem 1.5rem; flex-direction:column; text-align:center; }
  }
  </style>
</head>
<body>

<!-- NAV -->
<nav>
  <div class="nav-logo">Meat<span>Scan</span></div>
  <ul class="nav-links">
    <li><a href="#splash">Home</a></li>
    <li><a href="#about">About</a></li>
    <li><a href="#dataset">Dataset</a></li>
    <li><a href="#method">How It Works</a></li>
    <li><a href="#scanner">Scanner</a></li>
    <li><a href="#features">Features</a></li>
    <li><a href="#stats">Statistics</a></li>
    <li><a href="#faq">FAQ</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
  <a href="#scanner" class="nav-login-btn" id="navCtaBtn">Scan Now</a>
</nav>

<!-- ══ SCREEN 1 — SPLASH ══ -->
<section class="splash" id="splash">
  <div class="splash-text">
    <div class="splash-eyebrow">AI Vision Scanner</div>
    <div class="splash-bigname">
      <span class="n1">Meat</span>
      <span class="n2">Scan</span>
    </div>
    <p class="splash-sub">AI-powered meat freshness detection. Upload an image and get an instant result with confidence, explanation, and recommendations.</p>
    <div class="splash-badges">
      <div class="badge">Computer Vision</div>
      <div class="badge">Food Safety</div>
      <div class="badge">API‑First</div>
      <div class="badge">AI‑Ready</div>
    </div>
  </div>
  <div class="splash-mockup" aria-hidden="true">
    <img class="meat-img" alt="Meat scan mockup" src="data:image/png;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAGQAlgDASIAAhEBAxEB/8QAHQAAAQUBAQEBAAAAAAAAAAAABAIDBQYHAQgACf/EAEoQAAIBAgQEAwYDBgQFAwMCBwECAwQRAAUhBhIxQVFhByJxgZEHFDKhsSNCUpKx0SRTYnKS4RUzNENygsLC8AdDY4PhF//EABoBAQEBAQEBAQAAAAAAAAAAAAABAgMEBQb/xAA3EQEBAAIBAwIEBQQDAQEAAAAAAQIRAyEEEjFBUQUTImFxgZGhsTJC0eHwFDNS4fEVcoKS/9oADAMBAAIRAxEAPwD2KiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigD//Z">
    <div class="scan-corner tl"></div>
    <div class="scan-corner tr"></div>
    <div class="scan-corner bl"></div>
    <div class="scan-corner br"></div>
    <div class="scan-conf">
      <div class="sc-label">Confidence</div>
      <div class="sc-value"><span id="heroConf">96.2</span>%</div>
    </div>
    <div class="scan-result">
      <div class="sr-label">Result</div>
      <div class="sr-value"><span class="sr-dot"></span><span id="heroLabel">FRESH</span></div>
    </div>
    <div class="scroll-hint">
      <span>Scroll</span>
      <div class="scroll-line"></div>
    </div>
  </div>
</section>

<!-- ══ SCREEN 2 — ABOUT ══ -->
<section class="about" id="about">
  <div class="about-inner">
    <div class="section-eyebrow">About</div>
    <h2 class="about-title">What is<br><span>MeatScan?</span></h2>
    <div class="about-body">
      <div class="about-text reveal">
        <p><strong>MeatScan</strong> is an AI-ready platform that classifies meat freshness as <strong>fresh</strong>, <strong>spoiled</strong>, or <strong>uncertain</strong> from a single photo.</p>
        <p>It’s built with an <strong>API-first</strong> backend for mobile apps and future ML integrations (TensorFlow, FastAPI, OpenAI Vision, and custom models).</p>
        <p>Today, MeatScan runs a <strong>mock detector</strong> to validate the full workflow end-to-end — ready to swap in real AI when your model is available.</p>
      </div>
      <div class="about-stats reveal">
        <div class="astat">
          <span class="astat-num">3</span>
          <span class="astat-label">Outcome Labels</span>
        </div>
        <div class="astat">
          <span class="astat-num">API</span>
          <span class="astat-label">Flutter Ready</span>
        </div>
        <div class="astat">
          <span class="astat-num">AI</span>
          <span class="astat-label">Swap-in Architecture</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ SCREEN 3 — DATASET ══ -->
<section class="dataset" id="dataset">
  <div class="dataset-inner">
    <div class="section-label">Dataset</div>
    <h2 class="section-title">Real-World Images</h2>
    <div class="dataset-grid reveal">
      <div class="dataset-stat">
        <span class="dataset-stat-num">RGB</span>
        <div class="dataset-stat-label">Image Input</div>
      </div>
      <div class="dataset-stat">
        <span class="dataset-stat-num">3</span>
        <div class="dataset-stat-label">Classes</div>
      </div>
      <div class="dataset-stat">
        <span class="dataset-stat-num">API</span>
        <div class="dataset-stat-label">Stored Scans</div>
      </div>
    </div>
    <div class="dataset-info reveal">
      <div>
        <div class="info-label">Goal</div>
        <div class="info-text">Enable low-friction freshness screening using a simple photo, while keeping the architecture ready for verified ML models.</div>
      </div>
      <div>
        <div class="info-label">Future Integrations</div>
        <div class="info-text">TensorFlow / FastAPI microservice, OpenAI Vision, computer vision APIs, or your own trained CNN/ViT model.</div>
      </div>
    </div>
  </div>
</section>

<!-- ══ SCREEN 4 — METHODOLOGY ══ -->
<section class="method" id="method">
  <div class="method-inner">
    <div class="section-label">Methodology</div>
    <h2 class="section-title">How It Works</h2>
    <div class="method-steps reveal">
      <div class="step">
        <div class="step-num">01</div>
        <div class="step-title">Capture / Upload</div>
        <div class="step-text">Upload a clear image (JPG/PNG/WEBP) of the meat surface.</div>
      </div>
      <div class="step">
        <div class="step-num">02</div>
        <div class="step-title">Preprocess</div>
        <div class="step-text">The service prepares the image for detection (resizing/normalization in real AI mode).</div>
      </div>
      <div class="step">
        <div class="step-num">03</div>
        <div class="step-title">Detect</div>
        <div class="step-text">Mock detector today; model inference later via swappable detector service.</div>
      </div>
      <div class="step">
        <div class="step-num">04</div>
        <div class="step-title">Explain</div>
        <div class="step-text">Return label, confidence, explanation, recommendations, and timestamp.</div>
      </div>
      <div class="step">
        <div class="step-num">05</div>
        <div class="step-title">Store</div>
        <div class="step-text">Persist scan records for user history, analytics, and admin reporting.</div>
      </div>
    </div>
  </div>
</section>

<!-- ══ SCREEN 5 — SCANNER ══ -->
<section class="scanner-section" id="scanner">
  <div class="scanner-inner">
    <div class="scanner-header reveal">
      <div class="section-eyebrow" style="justify-content:center">Scan Tool</div>
      <h2 style="font-family:'Bebas Neue',sans-serif;font-size:clamp(3rem,6vw,5rem);letter-spacing:2px;line-height:1;margin-bottom:0.8rem;">Detect Freshness</h2>
      <p style="color:var(--muted);font-size:0.95rem;font-weight:300;">Upload a meat image and the system will classify it instantly.</p>
    </div>

    <div class="upload-zone reveal" id="uploadZone">
      <div class="scan-line"></div>
      <div class="uz-corner tl"></div><div class="uz-corner tr"></div>
      <div class="uz-corner bl"></div><div class="uz-corner br"></div>
      <img id="previewImg" alt="Preview">
      <div class="upload-icon" id="uploadIcon">📷</div>
      <div class="upload-label" id="uploadLabel">
        <strong>Drop an image here</strong>
        or click to browse
      </div>
      <div class="upload-sub" id="uploadSub">JPG · PNG · WEBP</div>
    </div>

    <input type="file" id="fileInput" accept="image/*">
    <button class="scan-btn" id="scanBtn">⟶ Run Scan</button>
    <button class="reset-btn" id="resetBtn">↺ Scan Another Image</button>
    <div class="result-badge" id="resultBadge"></div>

    <div class="result-panel" id="resultPanel">
      <div class="rp-row">
        <div>
          <div class="rp-title" id="rpTitle">Result</div>
        </div>
        <div class="rp-meta">
          <div class="rp-pill">Confidence <strong id="rpConf">—</strong>%</div>
          <div class="rp-pill">Scanned <strong id="rpTime">—</strong></div>
        </div>
      </div>
      <div class="rp-body">
        <div class="rp-block">
          <div class="rp-h">Explanation</div>
          <div class="rp-p" id="rpExplain">—</div>
        </div>
        <div class="rp-block">
          <div class="rp-h">Recommendations</div>
          <ul class="rp-list" id="rpRecs"></ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="features" id="features">
  <div class="features-inner">
    <div class="section-label">Features</div>
    <h2 class="section-title">Built For Production</h2>
    <div class="feature-grid reveal">
      <div class="feature-card">
        <div class="feature-kicker">API-First</div>
        <div class="feature-title">Flutter Ready</div>
        <div class="feature-text">Stable `/api/v1` endpoints with consistent response envelopes for fast mobile integration.</div>
      </div>
      <div class="feature-card">
        <div class="feature-kicker">Auth</div>
        <div class="feature-title">Sanctum Tokens</div>
        <div class="feature-text">Register, login, logout, password reset, and profile management with token-based security.</div>
      </div>
      <div class="feature-card">
        <div class="feature-kicker">AI</div>
        <div class="feature-title">Detector Abstraction</div>
        <div class="feature-text">Swap mock detection with TensorFlow/FastAPI/OpenAI Vision without changing controllers or API contracts.</div>
      </div>
      <div class="feature-card">
        <div class="feature-kicker">Storage</div>
        <div class="feature-title">Image Archiving</div>
        <div class="feature-text">Uploads stored via Laravel Storage with scan history endpoints for user dashboards and admin tools.</div>
      </div>
      <div class="feature-card">
        <div class="feature-kicker">Architecture</div>
        <div class="feature-title">Service Layer</div>
        <div class="feature-text">Clean services + DTOs + Resources + FormRequests to keep the codebase maintainable and scalable.</div>
      </div>
      <div class="feature-card">
        <div class="feature-kicker">Queue Ready</div>
        <div class="feature-title">Async Inference</div>
        <div class="feature-text">Designed to push heavy AI inference to queues later (jobs table already present) without breaking APIs.</div>
      </div>
    </div>
  </div>
</section>

<!-- STATISTICS -->
<section class="stats" id="stats">
  <div class="stats-inner">
    <div class="section-label">Statistics</div>
    <h2 class="section-title">Platform Snapshot</h2>
    <div class="stats-grid reveal">
      <div class="stat"><span class="stat-num"><span>12</span>.58</span><div class="stat-lbl">Laravel Version</div></div>
      <div class="stat"><span class="stat-num"><span>4</span>.3</span><div class="stat-lbl">Sanctum Version</div></div>
      <div class="stat"><span class="stat-num"><span>3</span></span><div class="stat-lbl">Outcome Labels</div></div>
      <div class="stat"><span class="stat-num"><span>v</span>1</span><div class="stat-lbl">API Namespace</div></div>
    </div>
    <p class="info-text" style="margin-top:1.6rem;">These are platform-level indicators. Model performance metrics (accuracy, precision, recall, F1) will be displayed once the real ML model is connected.</p>
  </div>
</section>

<!-- FAQ -->
<section class="faq" id="faq">
  <div class="faq-inner">
    <div class="section-label">FAQ</div>
    <h2 class="section-title">Common Questions</h2>
    <div class="faq-list reveal">
      <div class="faq-item">
        <button class="faq-q" type="button"><span>Is the AI real right now?</span><span class="faq-toggle">+</span></button>
        <div class="faq-a">Right now MeatScan uses a mock detector to validate the full workflow. The backend is designed so you can plug in a real TensorFlow/FastAPI/OpenAI Vision detector without changing the public API contract.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q" type="button"><span>Do I need an account to scan?</span><span class="faq-toggle">+</span></button>
        <div class="faq-a">Yes. Scans are tied to your account so you can view scan history securely. The web UI will prompt you to login/register before scanning.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q" type="button"><span>What image types are supported?</span><span class="faq-toggle">+</span></button>
        <div class="faq-a">JPG, PNG, and WEBP are supported. Keep lighting even and the surface in focus for best results.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q" type="button"><span>Do I need an account to scan?</span><span class="faq-toggle">+</span></button>
        <div class="faq-a">No. Scanning is available without authentication. Scan history endpoints return a public feed of recent scans (ready to be locked down later if needed).</div>
      </div>
    </div>
  </div>
</section>

<!-- CONTACT -->
<section class="contact" id="contact">
  <div class="contact-inner">
    <div class="contact-card reveal">
      <div class="section-label">Contact</div>
      <h2 class="section-title" style="margin-bottom:1.2rem;">Let’s Build the Model</h2>
      <div class="contact-line">Want to connect a real AI model, add admin analytics, or ship the Flutter app? MeatScan is built to scale — we can integrate inference services, queues, and monitoring as the next step.</div>
      <div class="contact-line" style="margin-top:1rem;">Tip: if images don’t load from `image_url`, run `php artisan storage:link` to enable public storage URLs.</div>
    </div>
    <div class="contact-card reveal">
      <div class="feature-kicker">Message</div>
      <div class="feature-title">Send a Note</div>
      <form class="contact-form" id="contactForm">
        <input name="name" placeholder="Your name" required>
        <input name="email" type="email" placeholder="Your email" required>
        <textarea name="message" placeholder="What would you like to build next?" required></textarea>
        <button class="contact-btn" type="submit">Send</button>
      </form>
      <div class="contact-line" id="contactStatus" style="display:none;"></div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-logo">Meat<span>Scan</span></div>
  <div class="footer-meta">Deep Learning · Food Safety · Computer Vision · 2026</div>
</footer>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<script>
  window.__MEATSCAN_API_BASE__ = @json(url('/api/v1'));
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const API_BASE = window.__MEATSCAN_API_BASE__ || '/api/v1';

  async function apiFetch(path, options = {}) {
    const headers = new Headers(options.headers || {});
    headers.set('Accept', 'application/json');

    // Only set JSON content-type when body is plain object
    let body = options.body;
    if (body && !(body instanceof FormData) && typeof body === 'object' && !(body instanceof Blob)) {
      headers.set('Content-Type', 'application/json');
      body = JSON.stringify(body);
    }

    const res = await fetch(API_BASE + path, { ...options, headers, body });
    const json = await res.json().catch(() => null);
    if (!res.ok) {
      const message = json?.message || 'Request failed.';
      const error = new Error(message);
      error.status = res.status;
      error.payload = json;
      throw error;
    }
    return json;
  }

  // Reveal animations
  const reveals = document.querySelectorAll('.reveal');
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.1 });
  reveals.forEach(el => obs.observe(el));

  // FAQ accordion
  document.querySelectorAll('.faq-item .faq-q').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      const open = item.classList.toggle('open');
      btn.querySelector('.faq-toggle').textContent = open ? '−' : '+';
    });
  });

  // Toast
  function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  // Scanner controls
  const zone = document.getElementById('uploadZone');
  const fileInput = document.getElementById('fileInput');
  const preview = document.getElementById('previewImg');
  const icon = document.getElementById('uploadIcon');
  const label = document.getElementById('uploadLabel');
  const sub = document.getElementById('uploadSub');
  const scanBtn = document.getElementById('scanBtn');
  const resetBtn = document.getElementById('resetBtn');
  const result = document.getElementById('resultBadge');
  const resultPanel = document.getElementById('resultPanel');
  const rpTitle = document.getElementById('rpTitle');
  const rpConf = document.getElementById('rpConf');
  const rpTime = document.getElementById('rpTime');
  const rpExplain = document.getElementById('rpExplain');
  const rpRecs = document.getElementById('rpRecs');

  zone.addEventListener('click', () => fileInput.click());
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('dragover'); if (e.dataTransfer.files[0]) loadImage(e.dataTransfer.files[0]); });
  fileInput.addEventListener('change', () => { if (fileInput.files[0]) loadImage(fileInput.files[0]); });

  function loadImage(file) {
    const reader = new FileReader();
    reader.onload = ev => {
      preview.src = ev.target.result;
      preview.style.display = 'block';
      icon.style.display = 'none';
      label.style.display = 'none';
      sub.style.display = 'none';
      scanBtn.classList.add('visible');
      result.style.display = 'none';
      resultPanel.style.display = 'none';
      resetBtn.classList.remove('visible');
    };
    reader.readAsDataURL(file);
  }

  function labelToUi(label) {
    const normalized = (label || '').toLowerCase();
    if (normalized === 'fresh') return { cls: 'fresh', text: '✓  FRESH' };
    if (normalized === 'spoiled') return { cls: 'spoiled', text: '✗  SPOILED' };
    return { cls: 'uncertain', text: '…  UNCERTAIN' };
  }

  scanBtn.addEventListener('click', async () => {
    if (!fileInput.files[0]) {
      showToast('Please upload an image first.');
      return;
    }

    scanBtn.textContent = 'Scanning…';
    scanBtn.disabled = true;
    result.style.display = 'none';
    resultPanel.style.display = 'none';

    try {
      const fd = new FormData();
      fd.append('image', fileInput.files[0]);
      const res = await apiFetch('/meat-scans', { method:'POST', body: fd });
      const data = res.data;

      const ui = labelToUi(data.label);
      result.className = 'result-badge ' + ui.cls;
      result.textContent = ui.text + '  —  ' + Number(data.confidence).toFixed(1) + '% confidence';
      result.style.display = 'block';

      rpTitle.textContent = ui.text.replace(/^[^A-Z]*\s*/, '').trim();
      rpConf.textContent = Number(data.confidence).toFixed(1);
      rpTime.textContent = (data.scanned_at || '').replace('T', ' ').replace('Z', '');
      rpExplain.textContent = data.explanation || '—';
      rpRecs.innerHTML = '';
      (data.recommendations || []).forEach(r => {
        const li = document.createElement('li');
        li.textContent = r;
        rpRecs.appendChild(li);
      });
      resultPanel.style.display = 'block';

      scanBtn.classList.remove('visible');
      resetBtn.classList.add('visible');
    } catch (e) {
      showToast(e.message || 'Scan failed.');
    } finally {
      scanBtn.disabled = false;
      scanBtn.textContent = '⟶ Run Scan';
    }
  });

  resetBtn.addEventListener('click', () => {
    preview.style.display = 'none'; preview.src = '';
    icon.style.display = 'block'; label.style.display = 'block'; sub.style.display = 'block';
    scanBtn.classList.remove('visible'); resetBtn.classList.remove('visible');
    result.style.display = 'none'; resultPanel.style.display = 'none';
    fileInput.value = '';
  });

  // Contact form (no backend endpoint yet — show toast only)
  document.getElementById('contactForm').addEventListener('submit', (e) => {
    e.preventDefault();
    showToast('Message captured (hook backend next).');
    e.target.reset();
  });

  // Hero loop animation
  const heroLabel = document.getElementById('heroLabel');
  const heroConf = document.getElementById('heroConf');
  const heroLoop = [
    { label: 'FRESH', conf: '96.2' },
    { label: 'SPOILED', conf: '92.4' },
    { label: 'UNCERTAIN', conf: '78.3' }
  ];
  let heroIdx = 0;
  setInterval(() => {
    heroIdx = (heroIdx + 1) % heroLoop.length;
    heroLabel.textContent = heroLoop[heroIdx].label;
    heroConf.textContent = heroLoop[heroIdx].conf;
  }, 4200);

});
</script>
</body>
</html>

