<?php
require_once __DIR__ . '/lang.php';

function admin_start(string $title, string $active = '', string $actions = ''): void {
    $user = current_user();
    $init = strtoupper(substr($user['name'] ?? 'A', 0, 1));
    $lang = admin_lang();
    $nav  = [
        ['href'=>'/admin',             'icon'=>'📊', 'label'=>t('nav_dashboard'),   'key'=>'dashboard'],
        ['href'=>'/admin/analytics',   'icon'=>'📈', 'label'=>t('nav_analytics'),   'key'=>'analytics'],
        ['href'=>'/admin/submissions', 'icon'=>'📋', 'label'=>t('nav_submissions'), 'key'=>'submissions'],
        ['href'=>'/admin/forms',       'icon'=>'📝', 'label'=>t('nav_forms'),       'key'=>'forms'],
        ['href'=>'/admin/content',     'icon'=>'✏️',  'label'=>t('nav_content'),     'key'=>'content'],
        ['href'=>'/admin/team',        'icon'=>'👥', 'label'=>t('nav_team'),        'key'=>'team'],
    ];
    $cur_path = strtok($_SERVER['REQUEST_URI'], '?');
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> — Admin</title>
<link rel="icon" type="image/png" href="/logo.png?v=4">
<style>
:root{--sb:#1B2E2B;--sb-t:rgba(255,255,255,.70);--sb-w:240px;--mint:#3DBFAF;--mint-d:#2A9485;--peach:#F5A87A;--bg:#EFF4F3;--card:#fff;--dark:#1E2D2B;--mid:#5A706B;--lite:#A8C4BF;--bdr:#E0EEEC;--red:#D94F4F;--green:#27AE7A;--amber:#D98A1A}
*{margin:0;padding:0;box-sizing:border-box} body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--dark);font-size:14px;line-height:1.5} a{color:inherit;text-decoration:none}
/* Layout */
.al{display:flex;min-height:100vh}
/* Sidebar */
.sb{width:var(--sb-w);background:var(--sb);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;overflow:hidden}
.sb-brand{padding:18px 18px 14px;border-bottom:1px solid rgba(255,255,255,.07);display:block}
.sb-mark{width:34px;height:34px;background:linear-gradient(135deg,var(--mint),var(--mint-d));border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:12px;margin-bottom:7px}
.sb-title{font-size:13px;font-weight:700;color:#fff;line-height:1.2}
.sb-sub{font-size:11px;color:var(--sb-t)}
.sb-nav{flex:1;padding:8px 0;overflow-y:auto}
.nav-sec{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.27);padding:13px 18px 4px}
.ni{display:flex;align-items:center;gap:9px;padding:9px 18px;color:var(--sb-t);font-size:13px;font-weight:500;transition:background .15s,color .15s;position:relative}
.ni:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.9)}
.ni.active{background:rgba(61,191,175,.14);color:var(--mint);font-weight:700}
.ni.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--mint);border-radius:0 2px 2px 0}
.ni-ic{font-size:14px;width:18px;text-align:center;flex-shrink:0}
.sb-foot{padding:14px 16px;border-top:1px solid rgba(255,255,255,.07)}
.sb-usr{display:flex;align-items:center;gap:9px}
.sb-av{width:30px;height:30px;background:var(--mint);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0}
.sb-un{font-size:12px;font-weight:600;color:rgba(255,255,255,.85)}
.sb-ur{font-size:10px;color:var(--sb-t);text-transform:capitalize}
.sb-lo{font-size:11px;color:rgba(255,255,255,.32);margin-top:7px;display:block;transition:color .15s}
.sb-lo:hover{color:var(--red)}
/* Language switcher */
.lang-sw{display:flex;gap:4px;margin-top:10px}
.ls-btn{font-size:11px;font-weight:700;padding:3px 9px;border-radius:5px;color:rgba(255,255,255,.38);transition:all .15s;letter-spacing:.03em}
.ls-btn.on{background:rgba(61,191,175,.22);color:var(--mint)}
.ls-btn:hover:not(.on){color:rgba(255,255,255,.7)}
/* Main */
.mc{margin-left:var(--sb-w);flex:1;display:flex;flex-direction:column;min-height:100vh}
.tb{background:#fff;height:58px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;padding:0 24px;position:sticky;top:0;z-index:50;gap:12px}
.tb-t{font-size:15px;font-weight:700;color:var(--dark);flex:1}
.tb-a{display:flex;align-items:center;gap:8px}
.pc{flex:1;padding:22px 24px}
/* Cards */
.card{background:var(--card);border-radius:13px;box-shadow:0 1px 3px rgba(0,0,0,.07);overflow:hidden;margin-bottom:16px}
.ch{padding:14px 18px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.ct{font-size:14px;font-weight:700;color:var(--dark)}
.cb{padding:18px}
/* Stat grid */
.sg{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:18px}
.sc{background:var(--card);border-radius:11px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.07)}
.sc-ic{font-size:20px;margin-bottom:7px}
.sc-v{font-size:24px;font-weight:900;color:var(--dark);line-height:1}
.sc-l{font-size:12px;color:var(--mid);margin-top:3px}
.sc-c{font-size:11px;margin-top:4px}
.sc-c.up{color:var(--green)} .sc-c.dn{color:var(--red)}
/* Tables */
.tw{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{background:#F7FAF9;padding:8px 12px;text-align:left;font-weight:700;color:var(--mid);font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;border-bottom:1px solid var(--bdr)}
tbody td{padding:10px 12px;border-bottom:1px solid #F2F7F6;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:#F9FCFB}
/* Badges */
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.b-g{background:#E6F8F1;color:var(--green)} .b-a{background:#FEF4E5;color:var(--amber)} .b-r{background:#FEE8E8;color:var(--red)} .b-b{background:#E5F6F4;color:var(--mint-d)} .b-gr{background:#F0F5F4;color:var(--mid)}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:700;border:none;cursor:pointer;font-family:inherit;white-space:nowrap;transition:opacity .15s,transform .1s}
.btn:hover{opacity:.88;transform:translateY(-1px)}
.btn-p{background:var(--mint);color:#fff} .btn-d{background:var(--red);color:#fff} .btn-g{background:#fff;color:var(--mid);border:1.5px solid var(--bdr)} .btn-a{background:var(--amber);color:#fff}
.btn-sm{padding:5px 11px;font-size:12px} .btn-xs{padding:3px 8px;font-size:11px;border-radius:6px}
/* Forms */
.fg{margin-bottom:14px} .fl{display:block;font-size:13px;font-weight:700;margin-bottom:4px;color:var(--dark)}
.fc{width:100%;padding:8px 11px;border:1.5px solid var(--bdr);border-radius:8px;font-size:13px;color:var(--dark);font-family:inherit;outline:none;background:#fff;transition:border-color .15s}
.fc:focus{border-color:var(--mint);box-shadow:0 0 0 3px rgba(61,191,175,.1)}
select.fc{cursor:pointer} textarea.fc{min-height:90px;resize:vertical;line-height:1.6}
/* Tabs */
.tabs{display:flex;border-bottom:2px solid var(--bdr);margin-bottom:18px;gap:0;flex-wrap:wrap}
.tab{padding:8px 16px;font-size:13px;font-weight:600;color:var(--mid);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s}
.tab:hover{color:var(--dark)} .tab.active{color:var(--mint-d);border-bottom-color:var(--mint)}
/* Alerts */
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px}
.al-ok{background:#E6F8F1;color:var(--green);border:1px solid #B8EDD6}
.al-err{background:#FEE8E8;color:var(--red);border:1px solid #F9C0C0}
.al-info{background:#E5F6F4;color:var(--mint-d);border:1px solid #C0E8E4}
/* Modal */
.mo{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center}
.mo.open{display:flex}
.md{background:#fff;border-radius:14px;padding:24px;max-width:460px;width:92%;max-height:85vh;overflow-y:auto}
.md-t{font-size:17px;font-weight:900;margin-bottom:14px;color:var(--dark)}
.md-f{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}
/* Charts */
.chart-wrap{position:relative;width:100%}
canvas{display:block;width:100%!important}
/* Empty */
.empty{text-align:center;padding:40px 20px;color:var(--mid)}
.empty-ic{font-size:34px;margin-bottom:9px}
.empty-t{font-size:15px;font-weight:700;color:var(--dark);margin-bottom:4px}
/* Utils */
.flex{display:flex} .ic{align-items:center} .jb{justify-content:space-between} .g8{gap:8px} .g12{gap:12px} .g16{gap:16px}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.mb8{margin-bottom:8px} .mb12{margin-bottom:12px} .mb16{margin-bottom:16px} .mb20{margin-bottom:20px} .mb24{margin-bottom:24px}
.tr{text-align:right} .tm{color:var(--mid)} .fw7{font-weight:700} .fw9{font-weight:900} .fs12{font-size:12px}
.sr{position:relative}
.si{padding:8px 11px 8px 32px;border:1.5px solid var(--bdr);border-radius:8px;font-size:13px;outline:none;background:#fff;font-family:inherit;min-width:220px;transition:border-color .15s}
.si:focus{border-color:var(--mint)}
.sic{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--lite);pointer-events:none}
@media(max-width:800px){.sb{display:none}.mc{margin-left:0}}
</style>
</head>
<body>
<div class="al">
  <nav class="sb">
    <a href="/admin" class="sb-brand">
      <div class="sb-mark">RC</div>
      <div class="sb-title">Robo Co-op</div>
      <div class="sb-sub">Shimane Admin</div>
    </a>
    <div class="sb-nav">
      <div class="nav-sec"><?= t('nav_section') ?></div>
      <?php foreach ($nav as $n): ?>
      <a href="<?= $n['href'] ?>" class="ni <?= $active === $n['key'] ? 'active' : '' ?>">
        <span class="ni-ic"><?= $n['icon'] ?></span><?= $n['label'] ?>
      </a>
      <?php endforeach; ?>
      <div class="nav-sec"><?= t('nav_live') ?></div>
      <a href="/" target="_blank" class="ni"><span class="ni-ic">🗾</span><?= t('nav_jp_page') ?></a>
      <a href="/en" target="_blank" class="ni"><span class="ni-ic">🌐</span><?= t('nav_en_page') ?></a>
      <a href="/apply" target="_blank" class="ni"><span class="ni-ic">📝</span><?= t('nav_apply') ?></a>
    </div>
    <div class="sb-foot">
      <div class="sb-usr">
        <div class="sb-av"><?= htmlspecialchars($init) ?></div>
        <div>
          <div class="sb-un"><?= htmlspecialchars($user['name'] ?? '') ?></div>
          <div class="sb-ur"><?= htmlspecialchars($user['role'] ?? '') ?></div>
        </div>
      </div>
      <a href="/admin/settings" class="sb-lo" style="color:rgba(255,255,255,.45)"><?= t('nav_settings') ?></a>
      <a href="/admin/logout" class="sb-lo"><?= t('sign_out') ?> →</a>
      <div class="lang-sw">
        <a href="<?= htmlspecialchars($cur_path) ?>?setlang=en" class="ls-btn <?= $lang==='en'?'on':'' ?>"><?= t('lang_en') ?></a>
        <a href="<?= htmlspecialchars($cur_path) ?>?setlang=ja" class="ls-btn <?= $lang==='ja'?'on':'' ?>"><?= t('lang_ja') ?> 日本語</a>
      </div>
    </div>
  </nav>
  <div class="mc">
    <div class="tb">
      <div class="tb-t"><?= htmlspecialchars($title) ?></div>
      <div class="tb-a"><?= $actions ?></div>
    </div>
    <div class="pc">
<?php
}

function admin_end(): void { ?>
    </div><!-- /pc -->
  </div><!-- /mc -->
</div><!-- /al -->
</body></html>
<?php }
