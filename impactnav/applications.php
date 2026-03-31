<?php
require_once __DIR__ . '/functions.php';
session_init($config['app']['session_name']);
require_role('org');

$pdo = pdo(); $user = current_user();
$listingId = (int)($_GET['listing_id'] ?? 0);
$stmt = $pdo->prepare('SELECT l.* FROM listings l JOIN organizations o ON o.id = l.org_id WHERE l.id = ? AND o.user_id = ?');
$stmt->execute([$listingId, $user['id']]);
$listing = $stmt->fetch();
if (!$listing) { flash('error','İlan bulunamadı.'); redirect('/dashboard.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $appId = (int)($_POST['app_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['accepted','rejected','pending']) ? $_POST['status'] : null;
    if ($appId && $status) { $stmt = $pdo->prepare('UPDATE applications SET status = ? WHERE id = ? AND listing_id = ?'); $stmt->execute([$status, $appId, $listingId]); }
    redirect("/applications.php?listing_id=$listingId");
}

$stmt = $pdo->prepare('SELECT a.id, a.status, a.message, a.applied_at, u.full_name, u.email, vp.city, vp.district, vp.remote_ok, vp.skills, vp.bio, ms.score, ms.rationale FROM applications a JOIN volunteer_profiles vp ON vp.id = a.volunteer_id JOIN users u ON u.id = vp.user_id LEFT JOIN match_scores ms ON ms.volunteer_id = vp.id AND ms.listing_id = a.listing_id WHERE a.listing_id = ? ORDER BY ms.score DESC, a.applied_at ASC');
$stmt->execute([$listingId]);
$applications = $stmt->fetchAll();

$pageTitle = 'Başvurular';
include __DIR__ . '/includes/head.php';
?>

<div class="min-h-screen bg-surface-900">
  <header class="glass border-b border-slate-800 sticky top-0 z-40">
    <div class="max-w-3xl mx-auto px-5 h-14 flex items-center justify-between">
      <a href="/dashboard.php" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-sm">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Dashboard
      </a>
      <span class="text-sm font-semibold text-white truncate max-w-xs"><?= h($listing['title']) ?></span>
      <a href="/logout.php" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">Çıkış</a>
    </div>
  </header>

  <div class="max-w-3xl mx-auto px-5 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-extrabold text-white flex items-center gap-2">
          <i data-lucide="inbox" class="w-5 h-5 text-violet-400"></i> Başvurular
        </h1>
        <p class="text-slate-400 text-sm mt-0.5"><?= h($listing['title']) ?> · <span class="text-violet-400"><?= count($applications) ?> başvuru</span></p>
      </div>
    </div>

    <?php if (empty($applications)): ?>
    <div class="rounded-2xl border border-slate-800 bg-surface-800/50 p-12 text-center">
      <div class="w-14 h-14 rounded-2xl bg-surface-700 border border-slate-700 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="inbox" class="w-7 h-7 text-slate-600"></i>
      </div>
      <p class="text-white font-bold mb-1.5">Henüz başvuru yok</p>
      <p class="text-slate-500 text-sm">İlanınız yayında, gönüllüler görecek.</p>
    </div>

    <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($applications as $app):
        $si = ['pending' => ['clock','text-amber-400','bg-amber-900/20 border-amber-800/30','Bekliyor'], 'accepted' => ['check-circle','text-emerald-400','bg-emerald-900/20 border-emerald-800/30','Kabul Edildi'], 'rejected' => ['x-circle','text-red-400','bg-red-900/20 border-red-800/30','Reddedildi']];
        [$sIcon,$sColor,$sBg,$sLabel] = $si[$app['status']] ?? ['circle','text-slate-400','bg-surface-700 border-slate-700','?'];
      ?>
      <div class="rounded-2xl border border-slate-700/50 bg-surface-800 p-5">
        <!-- Applicant header -->
        <div class="flex items-start justify-between gap-3 mb-3">
          <div class="flex gap-3 min-w-0">
            <div class="w-10 h-10 rounded-xl bg-surface-700 border border-slate-700 flex items-center justify-center flex-shrink-0 text-slate-400 font-bold text-sm">
              <?= mb_strtoupper(mb_substr($app['full_name'], 0, 1)) ?>
            </div>
            <div class="min-w-0">
              <p class="font-bold text-white text-sm"><?= h($app['full_name']) ?></p>
              <p class="text-xs text-slate-500"><?= h($app['email']) ?></p>
              <?php if ($app['city']): ?>
              <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                <i data-lucide="map-pin" class="w-3 h-3"></i> <?= h($app['city']) ?><?= $app['district'] ? ', '.h($app['district']) : '' ?><?= $app['remote_ok'] ? ' · <span class="text-cyan-500">Uzaktan</span>' : '' ?>
              </p>
              <?php endif; ?>
            </div>
          </div>
          <div class="flex flex-col items-end gap-2 flex-shrink-0">
            <?php if ($app['score'] !== null): ?>
            <span class="flex items-center gap-1 text-xs font-bold text-violet-300 bg-violet-900/30 border border-violet-700/40 px-2.5 py-1 rounded-full">
              <i data-lucide="zap" class="w-3 h-3"></i> %<?= round($app['score'] * 100) ?>
            </span>
            <?php endif; ?>
            <span class="flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full border <?= $sBg ?> <?= $sColor ?>">
              <i data-lucide="<?= $sIcon ?>" class="w-3 h-3"></i> <?= $sLabel ?>
            </span>
          </div>
        </div>

        <?php if ($app['skills']): ?>
        <div class="flex flex-wrap gap-1.5 mb-3">
          <?php foreach (explode(',', $app['skills']) as $sk): ?>
          <span class="text-[10px] text-slate-500 bg-surface-700 border border-slate-700 px-2 py-0.5 rounded-md"><?= h(trim($sk)) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($app['rationale']): ?>
        <div class="border-l-2 border-violet-600 bg-surface-700/50 rounded-r-lg px-3 py-2 mb-3">
          <p class="text-xs text-slate-400 italic leading-relaxed"><?= h($app['rationale']) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($app['message']): ?>
        <div class="bg-surface-700/40 border border-slate-700/60 rounded-lg px-3 py-2 mb-3">
          <p class="text-xs text-slate-400 italic">"<?= h($app['message']) ?>"</p>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <form method="POST" action="/applications.php?listing_id=<?= $listingId ?>" class="flex gap-2 flex-wrap">
          <?= csrf_field() ?>
          <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
          <?php if ($app['status'] !== 'accepted'): ?>
          <button type="submit" name="status" value="accepted"
            class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-emerald-900/30 border border-emerald-700/40 text-emerald-400 hover:bg-emerald-800/40 font-semibold transition-all">
            <i data-lucide="check" class="w-3.5 h-3.5"></i> Kabul Et
          </button>
          <?php endif; ?>
          <?php if ($app['status'] !== 'rejected'): ?>
          <button type="submit" name="status" value="rejected"
            class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-red-900/20 border border-red-800/30 text-red-400 hover:bg-red-900/30 font-semibold transition-all">
            <i data-lucide="x" class="w-3.5 h-3.5"></i> Reddet
          </button>
          <?php endif; ?>
          <?php if ($app['status'] !== 'pending'): ?>
          <button type="submit" name="status" value="pending"
            class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-slate-700 text-slate-400 hover:border-slate-600 transition-all">
            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Sıfırla
          </button>
          <?php endif; ?>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/chatbot.php'; ?>
<script>lucide.createIcons();</script>
</body>
</html>
