<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --mint:      #3DBFAF;
      --mint-dark: #2A9485;
      --mint-pale: #E8F8F6;
      --peach:     #F5A87A;
      --peach-pale:#FFF3EC;
      --sage:      #7DB89A;
      --cream:     #FDFAF6;
      --warm-dark: #2E3D3B;
      --warm-mid:  #5A706B;
      --warm-light:#A8C4BF;
    }

    body {
      font-family: <?php echo (isset($lang) && $lang === 'en')
        ? "'-apple-system', BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif"
        : "'Hiragino Kaku Gothic ProN', 'Meiryo', 'Yu Gothic', sans-serif"; ?>;
      color: var(--warm-dark);
      line-height: 1.8;
      background: white;
    }

    /* ─── HEADER ─── */
    header {
      background: white;
      padding: 14px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #EEF3F2;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .logo { display: flex; align-items: center; gap: 10px; }
    .logo-mark {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, var(--mint), var(--sage));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-weight: 900; color: white; font-size: 13px;
    }
    .logo-text { font-size: 15px; font-weight: 700; color: var(--warm-dark); line-height: 1.2; }
    .logo-sub  { font-size: 10px; color: var(--warm-light); }
    .header-right { display: flex; align-items: center; gap: 10px; }
    .lang-switch {
      font-size: 12px; font-weight: 700;
      color: var(--warm-mid);
      border: 1.5px solid var(--warm-light);
      border-radius: 20px;
      padding: 5px 13px;
      text-decoration: none;
      transition: all .2s;
      white-space: nowrap;
    }
    .lang-switch:hover { border-color: var(--mint); color: var(--mint-dark); background: var(--mint-pale); }
    .header-cta {
      background: var(--peach);
      color: white;
      border: none;
      padding: 10px 22px;
      border-radius: 24px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: opacity .2s;
      white-space: nowrap;
    }
    .header-cta:hover { opacity: .85; }

    /* ─── HERO ─── */
    .hero {
      background: linear-gradient(160deg, #E5F6F4 0%, #F8F2EE 60%, #EBF5F0 100%);
      padding: 72px 28px 80px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(61,191,175,.12) 0%, transparent 70%);
      top: -100px; right: -100px;
      pointer-events: none;
    }
    .hero::after {
      content: '';
      position: absolute;
      width: 300px; height: 300px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(245,168,122,.10) 0%, transparent 70%);
      bottom: -80px; left: -60px;
      pointer-events: none;
    }
    .hero-label {
      display: inline-block;
      background: white;
      color: var(--mint-dark);
      border: 1.5px solid var(--mint);
      border-radius: 20px;
      padding: 6px 20px;
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 24px;
    }
    .hero h1 {
      font-size: clamp(26px, 5vw, 42px);
      font-weight: 900;
      line-height: 1.4;
      color: var(--warm-dark);
      margin-bottom: 20px;
    }
    .hero h1 em {
      font-style: normal;
      color: var(--mint-dark);
      text-decoration: underline;
      text-decoration-color: var(--mint);
      text-decoration-thickness: 3px;
      text-underline-offset: 4px;
    }
    .hero-lead {
      font-size: clamp(15px, 2.5vw, 18px);
      color: var(--warm-mid);
      max-width: 560px;
      margin: 0 auto 40px;
    }
    .hero-card-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-template-rows: auto auto;
      gap: 12px;
      max-width: 640px;
      margin: 0 auto 44px;
    }
    .hc-stat {
      background: white;
      border-radius: 18px;
      padding: 20px 16px;
      text-align: center;
      box-shadow: 0 4px 16px rgba(61,191,175,.13);
    }
    .hc-stat .num {
      font-size: 34px; font-weight: 900;
      color: var(--mint-dark); line-height: 1;
    }
    .hc-stat .unit { font-size: 14px; font-weight: 700; color: var(--mint-dark); }
    .hc-stat .lbl  { font-size: 11px; color: var(--warm-mid); margin-top: 5px; }
    .hc-phase {
      grid-column: span 1;
      border-radius: 18px;
      padding: 22px 20px;
      text-align: left;
      display: flex; flex-direction: column; gap: 6px;
    }
    .hc-phase-row {
      grid-column: 1 / -1;
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      gap: 0;
      align-items: stretch;
    }
    .hc-phase-a { background: linear-gradient(145deg, #C6EEE9 0%, #AAE4DD 100%); border-radius: 18px 0 0 18px; }
    .hc-phase-b { background: linear-gradient(145deg, #FDDEC8 0%, #F9C5A0 100%); border-radius: 0 18px 18px 0; }
    .hc-phase-divider {
      width: 32px;
      background: linear-gradient(to right, #AAE4DD, #FDDEC8);
      display: flex; align-items: center; justify-content: center;
      color: rgba(255,255,255,.85); font-size: 22px; flex-shrink: 0;
    }
    @media (max-width: 480px) {
      .hc-phase-row { grid-template-columns: 1fr; }
      .hc-phase-a { border-radius: 18px 18px 0 0; }
      .hc-phase-b { border-radius: 0 0 18px 18px; }
      .hc-phase-divider { width: 100%; height: 28px; }
    }
    .hc-phase-eyebrow {
      display: inline-flex; align-items: baseline; gap: 3px;
      font-size: 11px; font-weight: 700;
      background: rgba(255,255,255,.55);
      border-radius: 99px; padding: 3px 10px;
      color: var(--warm-mid);
    }
    .hc-phase-eyebrow strong { font-size: 16px; font-weight: 900; color: var(--mint-dark); }
    .hc-phase-b .hc-phase-eyebrow strong { color: #B84F18; }
    .hc-phase-title { font-size: 17px; font-weight: 900; color: var(--warm-dark); line-height: 1.4; }
    .hc-phase-desc  { font-size: 12px; color: var(--warm-mid); line-height: 1.7; }
    .hero-btn {
      display: inline-block;
      background: linear-gradient(135deg, var(--peach), #E8834A);
      color: white;
      padding: 18px 52px;
      border-radius: 40px;
      font-size: 18px; font-weight: 900;
      text-decoration: none;
      box-shadow: 0 6px 24px rgba(245,168,122,.35);
      transition: transform .2s, box-shadow .2s;
      position: relative; z-index: 1;
    }
    .hero-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 32px rgba(245,168,122,.45); }
    .hero-note { font-size: 12px; color: var(--warm-mid); margin-top: 14px; }

    /* ─── SECTION SHARED ─── */
    section { padding: 68px 28px; }
    .inner { max-width: 880px; margin: 0 auto; }
    .section-label {
      display: inline-block;
      background: var(--mint-pale);
      color: var(--mint-dark);
      font-size: 12px; font-weight: 700;
      padding: 5px 16px;
      border-radius: 20px;
      margin-bottom: 12px;
      letter-spacing: .05em;
    }
    h2 {
      font-size: clamp(22px, 4vw, 30px);
      font-weight: 900; color: var(--warm-dark);
      line-height: 1.4; margin-bottom: 12px;
    }
    h2 em { font-style: normal; color: var(--mint-dark); }
    .section-desc { font-size: 15px; color: var(--warm-mid); margin-bottom: 40px; max-width: 640px; }

    /* ─── FOR WHOM ─── */
    .forwhom { background: var(--cream); }
    .who-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 16px; margin-bottom: 28px;
    }
    .who-card {
      background: white; border-radius: 16px; padding: 20px 22px;
      display: flex; align-items: flex-start; gap: 14px;
      box-shadow: 0 2px 10px rgba(0,0,0,.05);
    }
    .who-icon { font-size: 28px; flex-shrink: 0; line-height: 1; margin-top: 2px; }
    .who-card h3 { font-size: 14px; font-weight: 700; color: var(--warm-dark); margin-bottom: 4px; }
    .who-card p  { font-size: 13px; color: var(--warm-mid); }
    .warm-note {
      background: var(--peach-pale);
      border-left: 4px solid var(--peach);
      border-radius: 0 12px 12px 0;
      padding: 16px 20px;
      font-size: 14px; color: #7A4A2A; line-height: 1.7;
    }

    /* ─── PILLARS ─── */
    .pillars { background: white; }
    .pillar-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }
    .pillar { border-radius: 20px; padding: 32px 26px; position: relative; overflow: hidden; }
    .pillar-a { background: linear-gradient(145deg, #E5F6F4, #C8EEE9); }
    .pillar-b { background: linear-gradient(145deg, #F0F8F4, #D0EDE0); }
    .pillar-c { background: linear-gradient(145deg, #FFF3EC, #FFE0CC); }
    .pillar-icon { font-size: 36px; margin-bottom: 12px; display: block; }
    .pillar h3 { font-size: 19px; font-weight: 900; color: var(--warm-dark); margin-bottom: 4px; }
    .pillar-sub { font-size: 13px; color: var(--warm-mid); margin-bottom: 16px; }
    .pillar ul { list-style: none; }
    .pillar li { font-size: 14px; color: var(--warm-dark); padding: 5px 0 5px 22px; position: relative; line-height: 1.6; }
    .pillar-a li::before { content: '✿'; position: absolute; left: 0; color: var(--mint); }
    .pillar-b li::before { content: '✿'; position: absolute; left: 0; color: var(--sage); }
    .pillar-c li::before { content: '✿'; position: absolute; left: 0; color: var(--peach); }

    /* ─── SUPPORT ─── */
    .support { background: var(--cream); }
    .support-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 16px; margin-bottom: 24px;
    }
    .support-card {
      background: white; border-radius: 18px; padding: 24px 18px;
      text-align: center; box-shadow: 0 2px 14px rgba(0,0,0,.05);
      transition: transform .2s;
    }
    .support-card:nth-child(1) { grid-column: span 2; }
    .support-card:nth-child(2) { grid-column: span 2; }
    .support-card:nth-child(3) { grid-column: span 2; }
    .support-card:nth-child(4) { grid-column: 2 / span 2; }
    .support-card:nth-child(5) { grid-column: 4 / span 2; }
    @media (max-width: 640px) {
      .support-grid { grid-template-columns: 1fr 1fr; }
      .support-card:nth-child(n) { grid-column: span 1; }
      .support-card:nth-child(5) { grid-column: 1 / span 2; }
    }
    .support-card:hover { transform: translateY(-4px); }
    .sup-icon { font-size: 34px; margin-bottom: 10px; }
    .support-card h3 { font-size: 14px; font-weight: 700; color: var(--warm-dark); margin-bottom: 6px; }
    .support-card p  { font-size: 12px; color: var(--warm-mid); line-height: 1.65; }
    .spt-items { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 14px; }
    @media (max-width: 500px) { .spt-items { grid-template-columns: 1fr; } }
    .spt-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--warm-mid); }
    .spt-item span { font-size: 16px; flex-shrink: 0; }

    /* ─── SCHEDULE ─── */
    .schedule { background: white; }
    .timeline { padding-left: 28px; position: relative; }
    .timeline::before {
      content: '';
      position: absolute;
      left: 10px; top: 8px; bottom: 8px;
      width: 2px;
      background: linear-gradient(to bottom, var(--mint), var(--peach));
      border-radius: 2px;
    }
    .tl-item { position: relative; margin-bottom: 32px; padding-left: 8px; }
    .tl-dot {
      position: absolute; left: -24px; top: 5px;
      width: 16px; height: 16px; border-radius: 50%;
      background: white; border: 3px solid var(--mint);
      box-shadow: 0 0 0 3px var(--mint-pale);
    }
    .tl-item.now .tl-dot { background: var(--peach); border-color: var(--peach); box-shadow: 0 0 0 3px var(--peach-pale); }
    .tl-tag {
      display: inline-block; font-size: 12px; font-weight: 700;
      color: var(--mint-dark); background: var(--mint-pale);
      padding: 2px 12px; border-radius: 10px; margin-bottom: 6px;
    }
    .tl-item.now .tl-tag { color: #9A4A1A; background: var(--peach-pale); }
    .tl-title { font-size: 16px; font-weight: 700; color: var(--warm-dark); }
    .tl-desc  { font-size: 13px; color: var(--warm-mid); margin-top: 4px; }
    .now-badge {
      display: inline-block; background: var(--peach); color: white;
      font-size: 11px; font-weight: 700; padding: 2px 10px;
      border-radius: 8px; margin-left: 10px; vertical-align: middle;
    }

    /* ─── APPLY STEPS ─── */
    .apply { background: linear-gradient(155deg, #F0FAF8 0%, #FFF5F0 100%); }
    .steps-row { display: flex; flex-wrap: nowrap; align-items: center; gap: 0; }
    .steps-row .step-wrap { flex: 1 1 0; min-width: 0; }
    .step-wrap { display: flex; align-items: center; gap: 0; }
    .step-box {
      background: white; border-radius: 18px; padding: 24px 16px;
      text-align: center; flex: 1; box-shadow: 0 2px 12px rgba(0,0,0,.05);
    }
    .step-num {
      width: 40px; height: 40px;
      background: linear-gradient(135deg, var(--mint), var(--sage));
      color: white; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 900; font-size: 17px; margin: 0 auto 10px;
    }
    .step-box h4 { font-size: 14px; font-weight: 700; color: var(--warm-dark); }
    .step-box p  { font-size: 12px; color: var(--warm-mid); margin-top: 6px; line-height: 1.5; }
    .step-arrow { font-size: 20px; color: var(--warm-light); padding: 0 4px; flex-shrink: 0; }
    @media (max-width:600px) { .step-arrow { display: none; } }

    /* ─── FAQ ─── */
    .faq { background: var(--cream); }
    .faq-list { list-style: none; }
    .faq-item { border-bottom: 1px solid #E5EDEC; padding: 22px 0; }
    .faq-q {
      font-size: 15px; font-weight: 700; color: var(--warm-dark);
      padding-left: 34px; position: relative; margin-bottom: 10px;
    }
    .faq-q::before {
      content: 'Q'; position: absolute; left: 0; top: 0;
      width: 24px; height: 24px;
      background: linear-gradient(135deg, var(--mint), var(--sage));
      color: white; border-radius: 6px; font-size: 13px; font-weight: 900;
      display: flex; align-items: center; justify-content: center;
      line-height: 1; text-align: center;
    }
    .faq-a { font-size: 14px; color: var(--warm-mid); padding-left: 34px; line-height: 1.75; }

    /* ─── AWARDS ─── */
    .awards { background: white; }
    .awards-wrap { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
    .award-pill {
      display: flex; align-items: center; gap: 8px;
      background: var(--mint-pale); border-radius: 10px;
      padding: 10px 18px; font-size: 13px; font-weight: 600; color: var(--warm-dark);
    }
    .award-pill .aw { font-size: 18px; }

    /* ─── FINAL CTA ─── */
    .cta-section {
      background: linear-gradient(155deg, #D6F2EE 0%, #F9E8DC 100%);
      text-align: center; padding: 72px 28px;
    }
    .cta-section .section-label { background: white; color: var(--mint-dark); }
    .cta-section h2 { margin-bottom: 14px; }
    .cta-section .section-desc { margin: 0 auto 36px; }
    .cta-btns { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
    .btn-main {
      display: inline-block;
      background: linear-gradient(135deg, var(--peach), #E07840);
      color: white; padding: 18px 48px; border-radius: 40px;
      font-size: 17px; font-weight: 900; text-decoration: none;
      box-shadow: 0 6px 24px rgba(245,168,122,.35);
      transition: transform .2s, box-shadow .2s;
    }
    .btn-main:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(245,168,122,.45); }
    .btn-sub {
      display: inline-block; background: white; color: var(--mint-dark);
      border: 2px solid var(--mint); padding: 18px 40px; border-radius: 40px;
      font-size: 17px; font-weight: 700; text-decoration: none; transition: background .2s;
    }
    .btn-sub:hover { background: var(--mint-pale); }
    .cta-note { margin-top: 22px; font-size: 13px; color: var(--warm-mid); line-height: 1.8; }

    /* ─── FOOTER ─── */
    footer {
      background: var(--warm-dark); color: rgba(255,255,255,.55);
      text-align: center; padding: 36px 24px; font-size: 13px;
    }
    footer .ft-name { color: white; font-weight: 700; font-size: 16px; margin-bottom: 8px; }
    footer a { color: rgba(255,255,255,.55); }
  </style>
