<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
$user = require_auth('viewer');
$db   = get_db();

// Stats
$total_subs    = $db->query("SELECT COUNT(*) FROM form_submissions")->fetchColumn();
$total_drafts  = $db->query("SELECT COUNT(*) FROM form_drafts WHERE completed=0")->fetchColumn();
$today_views   = $db->query("SELECT COUNT(*) FROM analytics_events WHERE event_type='pageview' AND created_at::date = CURRENT_DATE")->fetchColumn();
$apply_clicks  = $db->query("SELECT COUNT(*) FROM analytics_events WHERE event_type='apply_click'")->fetchColumn();
$complete_rate = $total_subs + $total_drafts > 0
    ? round($total_subs / ($total_subs + $total_drafts) * 100) : 0;

// Recent submissions
$recent = $db->query("SELECT id,name,email,lang,status,submitted_at FROM form_submissions ORDER BY submitted_at DESC LIMIT 8")->fetchAll();

// Recent drafts (incomplete)
$pending = $db->query("SELECT id,name,email,lang,step_reached,created_at FROM form_drafts WHERE completed=0 ORDER BY updated_at DESC LIMIT 5")->fetchAll();

// Weekly views data for spark chart
$weekly = $db->query("
    SELECT created_at::date AS d, COUNT(*) AS n
    FROM analytics_events WHERE event_type='pageview'
      AND created_at >= NOW() - INTERVAL '6 days'
    GROUP BY created_at::date ORDER BY d
")->fetchAll(PDO::FETCH_KEY_PAIR);
$days_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $days_data[] = ['label' => date('D', strtotime($d)), 'val' => (int)($weekly[$d] ?? 0)];
}

admin_start('Dashboard', 'dashboard',
    '<a href="/admin/submissions" class="btn btn-p btn-sm">📋 All Submissions</a>'
);
?>

<!-- Stat cards -->
<div class="sg">
  <div class="sc"><div class="sc-ic">📋</div><div class="sc-v"><?= $total_subs ?></div><div class="sc-l">Total Applications</div></div>
  <div class="sc"><div class="sc-ic">⏳</div><div class="sc-v"><?= $total_drafts ?></div><div class="sc-l">Incomplete Drafts</div></div>
  <div class="sc"><div class="sc-ic">📈</div><div class="sc-v"><?= $today_views ?></div><div class="sc-l">Page Views Today</div></div>
  <div class="sc"><div class="sc-ic">🖱️</div><div class="sc-v"><?= $apply_clicks ?></div><div class="sc-l">Apply Button Clicks</div></div>
  <div class="sc"><div class="sc-ic">✅</div><div class="sc-v"><?= $complete_rate ?>%</div><div class="sc-l">Completion Rate</div></div>
</div>

<div class="g2" style="gap:16px">
  <!-- Weekly views chart -->
  <div class="card">
    <div class="ch"><span class="ct">📊 Page Views — Last 7 Days</span></div>
    <div class="cb" style="padding-bottom:24px">
      <div class="chart-wrap" style="height:160px"><canvas id="weekChart"></canvas></div>
    </div>
  </div>

  <!-- Quick links -->
  <div class="card">
    <div class="ch"><span class="ct">⚡ Quick Actions</span></div>
    <div class="cb" style="display:flex;flex-direction:column;gap:10px">
      <a href="/admin/submissions" class="btn btn-g" style="justify-content:flex-start">📋 Review Submissions</a>
      <a href="/admin/submissions?tab=drafts" class="btn btn-g" style="justify-content:flex-start">⏳ View Incomplete Drafts</a>
      <a href="/admin/analytics" class="btn btn-g" style="justify-content:flex-start">📈 Full Analytics</a>
      <a href="/admin/team" class="btn btn-g" style="justify-content:flex-start">👥 Manage Team</a>
      <a href="/admin/content" class="btn btn-g" style="justify-content:flex-start">✏️ Edit Content</a>
    </div>
  </div>
</div>

<!-- Recent submissions -->
<div class="card">
  <div class="ch">
    <span class="ct">🆕 Recent Applications</span>
    <a href="/admin/submissions" class="btn btn-g btn-sm">View all</a>
  </div>
  <div class="tw">
    <?php if ($recent): ?>
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Language</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
      <tr>
        <td class="fw7"><?= htmlspecialchars($r['name'] ?: '—') ?></td>
        <td class="tm"><?= htmlspecialchars($r['email'] ?: '—') ?></td>
        <td><span class="badge b-b"><?= strtoupper($r['lang']) ?></span></td>
        <td><?php
          $s = $r['status'];
          $cls = $s==='new' ? 'b-a' : ($s==='reviewed' ? 'b-b' : ($s==='accepted' ? 'b-g' : 'b-gr'));
          echo '<span class="badge '.$cls.'">'.htmlspecialchars($s).'</span>';
        ?></td>
        <td class="tm fs12"><?= date('M j, Y', strtotime($r['submitted_at'])) ?></td>
        <td><a href="/admin/submission-edit?id=<?= $r['id'] ?>" class="btn btn-g btn-xs">View</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty"><div class="empty-ic">📭</div><div class="empty-t">No applications yet</div><p>Applications will appear here once people start submitting the form.</p></div>
    <?php endif; ?>
  </div>
</div>

<!-- Pending drafts -->
<?php if ($pending): ?>
<div class="card">
  <div class="ch">
    <span class="ct">⏳ Incomplete Forms</span>
    <a href="/admin/submissions?tab=drafts" class="btn btn-g btn-sm">View all</a>
  </div>
  <div class="tw">
    <table>
      <thead><tr><th>Name / Email</th><th>Language</th><th>Step Reached</th><th>Started</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pending as $d): ?>
      <tr>
        <td>
          <div class="fw7"><?= htmlspecialchars($d['name'] ?: 'Unknown') ?></div>
          <div class="tm fs12"><?= htmlspecialchars($d['email'] ?: '—') ?></div>
        </td>
        <td><span class="badge b-b"><?= strtoupper($d['lang']) ?></span></td>
        <td><span class="badge b-a">Step <?= $d['step_reached'] ?> of 3</span></td>
        <td class="tm fs12"><?= date('M j', strtotime($d['created_at'])) ?></td>
        <td><a href="/admin/submissions?tab=drafts&highlight=<?= $d['id'] ?>" class="btn btn-g btn-xs">View</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  const data = <?= json_encode($days_data) ?>;
  const c = document.getElementById('weekChart');
  if(!c) return;
  const ctx = c.getContext('2d');
  const W = c.width = c.offsetWidth;
  const H = c.height = c.offsetHeight || 160;
  const pad = {t:16,r:12,b:32,l:32};
  const cw = W-pad.l-pad.r, ch = H-pad.t-pad.b;
  const max = Math.max(...data.map(d=>d.val), 1);
  const bw = (cw/data.length)*0.55;
  const bg = (cw/data.length)*0.45;
  ctx.fillStyle='#F7FAF9';
  ctx.fillRect(0,0,W,H);
  // Grid
  for(let i=0;i<=4;i++){
    const y=pad.t+ch-(i/4)*ch;
    ctx.strokeStyle='#E0EEEC';ctx.lineWidth=1;
    ctx.beginPath();ctx.moveTo(pad.l,y);ctx.lineTo(pad.l+cw,y);ctx.stroke();
    ctx.fillStyle='#A8C4BF';ctx.font='9px sans-serif';ctx.textAlign='right';
    ctx.fillText(Math.round((i/4)*max),pad.l-4,y+3);
  }
  // Bars
  data.forEach((d,i)=>{
    const x=pad.l+i*(cw/data.length)+bg/2;
    const bh=Math.max((d.val/max)*ch,2);
    const y=pad.t+ch-bh;
    const grad=ctx.createLinearGradient(0,y,0,y+bh);
    grad.addColorStop(0,'#3DBFAF');grad.addColorStop(1,'rgba(61,191,175,.4)');
    ctx.fillStyle=grad;
    ctx.beginPath();
    if(ctx.roundRect) ctx.roundRect(x,y,bw,bh,3); else ctx.rect(x,y,bw,bh);
    ctx.fill();
    // Value
    if(d.val>0){ctx.fillStyle='#1E2D2B';ctx.font='bold 10px sans-serif';ctx.textAlign='center';ctx.fillText(d.val,x+bw/2,y-3);}
    // Label
    ctx.fillStyle='#5A706B';ctx.font='9px sans-serif';ctx.textAlign='center';
    ctx.fillText(d.label,x+bw/2,H-6);
  });
})();
</script>

<?php admin_end(); ?>
