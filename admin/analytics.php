<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_auth('viewer');
$db = get_db();

$range = (int)($_GET['days'] ?? 30);
if (!in_array($range, [7,14,30,90])) $range = 30;

// Summary stats
$views   = $db->prepare("SELECT COUNT(*) FROM analytics_events WHERE event_type='pageview' AND created_at>=DATE('now',?)")->execute(["-{$range} days"]) ? 0 : 0;
$st = $db->prepare("SELECT COUNT(*) FROM analytics_events WHERE event_type='pageview' AND created_at>=DATE('now',?)");
$st->execute(["-{$range} days"]); $views = $st->fetchColumn();

$st2 = $db->prepare("SELECT COUNT(DISTINCT session_id) FROM analytics_events WHERE event_type='pageview' AND created_at>=DATE('now',?)");
$st2->execute(["-{$range} days"]); $unique = $st2->fetchColumn();

$st3 = $db->prepare("SELECT COUNT(*) FROM analytics_events WHERE event_type='apply_click' AND created_at>=DATE('now',?)");
$st3->execute(["-{$range} days"]); $apply_clicks = $st3->fetchColumn();

$st4 = $db->prepare("SELECT COUNT(*) FROM analytics_events WHERE event_type='pageview' AND lang='en' AND created_at>=DATE('now',?)");
$st4->execute(["-{$range} days"]); $en_views = $st4->fetchColumn();

$st5 = $db->prepare("SELECT COUNT(*) FROM analytics_events WHERE event_type='pageview' AND lang='ja' AND created_at>=DATE('now',?)");
$st5->execute(["-{$range} days"]); $ja_views = $st5->fetchColumn();

$st6 = $db->prepare("SELECT COUNT(*) FROM analytics_events WHERE event_type='click' AND created_at>=DATE('now',?)");
$st6->execute(["-{$range} days"]); $btn_clicks = $st6->fetchColumn();

// Daily views chart data
$daily_st = $db->prepare("
    SELECT DATE(created_at) as d, COUNT(*) as n
    FROM analytics_events WHERE event_type='pageview' AND created_at>=DATE('now',?)
    GROUP BY DATE(created_at) ORDER BY d
");
$daily_st->execute(["-{$range} days"]);
$daily_raw = $daily_st->fetchAll(PDO::FETCH_KEY_PAIR);

$daily = [];
for ($i = $range-1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $label = $range <= 14 ? date('M j', strtotime($d)) : ($i % 5 === 0 ? date('M j', strtotime($d)) : '');
    $daily[] = ['d' => $label, 'v' => (int)($daily_raw[$d] ?? 0)];
}

// Top pages
$top_pages = $db->prepare("
    SELECT page, COUNT(*) as n FROM analytics_events
    WHERE event_type='pageview' AND created_at>=DATE('now',?)
    GROUP BY page ORDER BY n DESC LIMIT 10
");
$top_pages->execute(["-{$range} days"]);
$pages = $top_pages->fetchAll();

// Event breakdown
$events_st = $db->prepare("
    SELECT event_type, COUNT(*) as n FROM analytics_events
    WHERE created_at>=DATE('now',?) GROUP BY event_type ORDER BY n DESC
");
$events_st->execute(["-{$range} days"]);
$events = $events_st->fetchAll();

admin_start('Analytics', 'analytics');
?>

<!-- Range selector -->
<div class="flex ic g8 mb20">
  <span class="fw7">Date range:</span>
  <?php foreach ([7=>'7 Days',14=>'14 Days',30=>'30 Days',90=>'3 Months'] as $d=>$l): ?>
  <a href="?days=<?=$d?>" class="btn btn-sm <?= $range==$d ? 'btn-p' : 'btn-g' ?>"><?=$l?></a>
  <?php endforeach; ?>
</div>

<!-- Stats -->
<div class="sg" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
  <div class="sc"><div class="sc-ic">👁️</div><div class="sc-v"><?= number_format($views) ?></div><div class="sc-l">Page Views</div></div>
  <div class="sc"><div class="sc-ic">👤</div><div class="sc-v"><?= number_format($unique) ?></div><div class="sc-l">Unique Visitors</div></div>
  <div class="sc"><div class="sc-ic">🖱️</div><div class="sc-v"><?= number_format($apply_clicks) ?></div><div class="sc-l">Apply Clicks</div></div>
  <div class="sc"><div class="sc-ic">🌐</div><div class="sc-v"><?= number_format($en_views) ?></div><div class="sc-l">English Visitors</div></div>
  <div class="sc"><div class="sc-ic">🗾</div><div class="sc-v"><?= number_format($ja_views) ?></div><div class="sc-l">Japanese Visitors</div></div>
  <div class="sc"><div class="sc-ic">👆</div><div class="sc-v"><?= number_format($btn_clicks) ?></div><div class="sc-l">Button Clicks</div></div>
</div>

<!-- Daily chart -->
<div class="card mb16">
  <div class="ch"><span class="ct">📈 Daily Page Views (Last <?= $range ?> days)</span></div>
  <div class="cb" style="padding-bottom:28px">
    <div class="chart-wrap" style="height:200px"><canvas id="dailyChart"></canvas></div>
  </div>
</div>

<div class="g2">
  <!-- Top pages -->
  <div class="card">
    <div class="ch"><span class="ct">📄 Top Pages</span></div>
    <div class="tw">
      <?php if ($pages): ?>
      <table>
        <thead><tr><th>Page</th><th class="tr">Views</th></tr></thead>
        <tbody>
        <?php
        $max_v = $pages[0]['n'] ?? 1;
        foreach ($pages as $p): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="height:6px;background:linear-gradient(90deg,#3DBFAF,rgba(61,191,175,.2));border-radius:3px;width:<?= round($p['n']/$max_v*100) ?>px;min-width:4px;max-width:120px"></div>
              <?= htmlspecialchars($p['page']) ?>
            </div>
          </td>
          <td class="tr fw7"><?= number_format($p['n']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><div class="empty"><div class="empty-ic">📊</div><p>No data yet for this period.</p></div><?php endif; ?>
    </div>
  </div>

  <!-- Language split + event types -->
  <div style="display:flex;flex-direction:column;gap:14px">
    <div class="card">
      <div class="ch"><span class="ct">🌍 Language Split</span></div>
      <div class="cb" style="padding-bottom:16px">
        <?php
        $total_lang = $en_views + $ja_views;
        $en_pct = $total_lang ? round($en_views / $total_lang * 100) : 0;
        $ja_pct = 100 - $en_pct;
        ?>
        <div style="display:flex;gap:12px;margin-bottom:10px">
          <div style="flex:1;text-align:center">
            <div class="sc-v" style="color:#3DBFAF"><?= $en_pct ?>%</div>
            <div class="sc-l">🌐 English</div>
          </div>
          <div style="flex:1;text-align:center">
            <div class="sc-v" style="color:#F5A87A"><?= $ja_pct ?>%</div>
            <div class="sc-l">🗾 Japanese</div>
          </div>
        </div>
        <div style="height:10px;background:#F0F5F4;border-radius:5px;overflow:hidden">
          <div style="height:100%;width:<?= $en_pct ?>%;background:linear-gradient(90deg,#3DBFAF,#2A9485);border-radius:5px;transition:width .5s"></div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="ch"><span class="ct">🎯 Event Breakdown</span></div>
      <div class="tw">
        <table>
          <thead><tr><th>Event</th><th class="tr">Count</th></tr></thead>
          <tbody>
          <?php foreach ($events as $e): ?>
          <tr><td><?= htmlspecialchars($e['event_type']) ?></td><td class="tr fw7"><?= number_format($e['n']) ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$events): ?><tr><td colspan="2" class="tm" style="text-align:center;padding:20px">No events yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const data = <?= json_encode($daily) ?>;
  const c = document.getElementById('dailyChart');
  if(!c) return;
  const ctx = c.getContext('2d');
  const W = c.width = c.offsetWidth;
  const H = c.height = c.offsetHeight || 200;
  const pad = {t:20,r:16,b:36,l:36};
  const cw = W-pad.l-pad.r, ch = H-pad.t-pad.b;
  const max = Math.max(...data.map(d=>d.v), 1);
  const n = data.length;

  ctx.fillStyle='#FAFCFB';ctx.fillRect(0,0,W,H);

  // Grid
  for(let i=0;i<=4;i++){
    const y=pad.t+ch-(i/4)*ch;
    ctx.strokeStyle='#E8F0EE';ctx.lineWidth=1;
    ctx.beginPath();ctx.moveTo(pad.l,y);ctx.lineTo(pad.l+cw,y);ctx.stroke();
    ctx.fillStyle='#A8C4BF';ctx.font='9px sans-serif';ctx.textAlign='right';
    ctx.fillText(Math.round((i/4)*max),pad.l-5,y+3);
  }

  // Area fill
  const grad=ctx.createLinearGradient(0,pad.t,0,pad.t+ch);
  grad.addColorStop(0,'rgba(61,191,175,.25)');grad.addColorStop(1,'rgba(61,191,175,0)');
  ctx.beginPath();
  data.forEach((d,i)=>{
    const x=pad.l+(i/(n-1))*cw;
    const y=pad.t+ch-(d.v/max)*ch;
    i===0?ctx.moveTo(x,y):ctx.lineTo(x,y);
  });
  ctx.lineTo(pad.l+cw,pad.t+ch);ctx.lineTo(pad.l,pad.t+ch);ctx.closePath();
  ctx.fillStyle=grad;ctx.fill();

  // Line
  ctx.strokeStyle='#3DBFAF';ctx.lineWidth=2;ctx.lineJoin='round';
  ctx.beginPath();
  data.forEach((d,i)=>{
    const x=pad.l+(i/(n-1))*cw;
    const y=pad.t+ch-(d.v/max)*ch;
    i===0?ctx.moveTo(x,y):ctx.lineTo(x,y);
  });
  ctx.stroke();

  // Dots + labels
  data.forEach((d,i)=>{
    const x=pad.l+(i/(n-1))*cw;
    const y=pad.t+ch-(d.v/max)*ch;
    if(d.v>0){
      ctx.fillStyle='#fff';ctx.strokeStyle='#3DBFAF';ctx.lineWidth=2;
      ctx.beginPath();ctx.arc(x,y,3,0,Math.PI*2);ctx.fill();ctx.stroke();
    }
    if(d.d){
      ctx.fillStyle='#5A706B';ctx.font='9px sans-serif';ctx.textAlign='center';
      ctx.fillText(d.d,x,H-6);
    }
  });
})();
</script>

<?php admin_end(); ?>
