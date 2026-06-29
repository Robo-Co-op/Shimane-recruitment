<?php
// Apply-prompt popup — included just before </body> on both homepages.
// Shows after 7 s to users who haven't applied and haven't dismissed recently.

// ── Application deadline ─────────────────────────────────────────────────────
// Update this date when the recruitment period ends.
// The popup is completely hidden (no HTML output) after this date.
define('POPUP_DEADLINE', '2026-07-31');
if (date('Y-m-d') > POPUP_DEADLINE) return;
// ─────────────────────────────────────────────────────────────────────────────

$_is_ja = ($lang ?? 'ja') === 'ja';
$_apply_url  = $_is_ja ? '/apply/ja' : '/apply';
?>
<!-- ── Apply Popup ──────────────────────────────────────────────────────────── -->
<div id="apply-popup" role="dialog" aria-modal="true" aria-labelledby="popup-title"
     style="display:none;position:fixed;inset:0;background:rgba(10,30,28,0.55);
            z-index:9999;align-items:center;justify-content:center;padding:20px;
            backdrop-filter:blur(3px)">

  <div style="background:#fff;border-radius:20px;max-width:400px;width:100%;
              overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,0.22);
              animation:popup-in .35s cubic-bezier(.22,.68,0,1.2) both">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#3DBFAF,#2A9485);padding:22px 26px 20px;position:relative">
      <button onclick="closeApplyPopup(false)" aria-label="Close"
              style="position:absolute;top:10px;right:10px;background:rgba(255,255,255,.18);
                     border:none;width:30px;height:30px;border-radius:50%;color:#fff;
                     font-size:18px;line-height:1;cursor:pointer;display:flex;
                     align-items:center;justify-content:center">&times;</button>
      <div style="color:rgba(255,255,255,.8);font-size:11px;font-weight:700;
                  letter-spacing:.06em;text-transform:uppercase;margin-bottom:5px">
        <?= $_is_ja ? '島根県 × Robo Co-op' : 'Shimane Prefecture × Robo Co-op' ?>
      </div>
      <div id="popup-title" style="color:#fff;font-size:17px;font-weight:900;line-height:1.35">
        <?= $_is_ja
          ? "令和8年度 デジタル人材育成研修<br>受講生を募集しています"
          : "FY2026 Digital Talent<br>Development Program — Now Open" ?>
      </div>
    </div>

    <!-- Body -->
    <div style="padding:22px 26px 20px">
      <?php if ($_is_ja): ?>
      <p style="font-size:14px;color:#4A6560;line-height:1.75;margin-bottom:18px">
        受講料は<strong>無料</strong>。生活費サポート枠（月20万円 × 3ヶ月）もあり。<br>
        定員10名のため、お早めにお申し込みください。
      </p>
      <?php else: ?>
      <p style="font-size:14px;color:#4A6560;line-height:1.75;margin-bottom:18px">
        <strong>Completely free</strong> — no tuition fees.<br>
        Living support available (¥200k/month × 3 months).<br>
        Only 10 spots. Apply before it closes!
      </p>
      <?php endif; ?>

      <div style="display:flex;gap:8px;flex-direction:column">
        <a href="<?= $_apply_url ?>"
           style="display:block;text-align:center;background:linear-gradient(135deg,#3DBFAF,#2A9485);
                  color:#fff;font-weight:800;font-size:15px;padding:14px 20px;
                  border-radius:40px;text-decoration:none;
                  box-shadow:0 4px 18px rgba(61,191,175,.35);
                  transition:opacity .2s,transform .2s"
           onmouseover="this.style.opacity='.9';this.style.transform='translateY(-1px)'"
           onmouseout="this.style.opacity='1';this.style.transform='translateY(0)'">
          <?= $_is_ja ? '📝 今すぐ応募する →' : '📝 Apply Now →' ?>
        </a>
        <button onclick="closeApplyPopup(true)"
                style="background:none;border:none;color:#A8C4BF;font-size:12px;
                       cursor:pointer;padding:4px;text-align:center">
          <?= $_is_ja ? 'あとで確認する' : 'Maybe later' ?>
        </button>
      </div>
    </div>
  </div>
</div>

<style>
@keyframes popup-in {
  from { opacity:0; transform:scale(.88) translateY(24px); }
  to   { opacity:1; transform:scale(1)   translateY(0); }
}
</style>

<script>
(function () {
  if (localStorage.getItem('shimane_applied')) return;
  var dismissed = localStorage.getItem('shimane_popup_dismissed');
  if (dismissed && Date.now() - parseInt(dismissed) < 3 * 86400000) return;
  setTimeout(function () {
    var el = document.getElementById('apply-popup');
    if (el) el.style.display = 'flex';
  }, 7000);
})();

function closeApplyPopup(remember) {
  var el = document.getElementById('apply-popup');
  if (el) el.style.display = 'none';
  if (remember) localStorage.setItem('shimane_popup_dismissed', Date.now().toString());
}

// Close on backdrop click
document.getElementById('apply-popup').addEventListener('click', function(e) {
  if (e.target === this) closeApplyPopup(true);
});
</script>
