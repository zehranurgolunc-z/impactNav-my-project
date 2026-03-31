<?php
require_once __DIR__ . '/functions.php';
session_init($config['app']['session_name']);
require_login();

$user = current_user();
$pdo  = pdo();

if ($user['role'] === 'volunteer') {
    $stmt = $pdo->prepare('SELECT * FROM volunteer_profiles WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch();
    $volId   = $profile ? $profile['id'] : null;
    $matches = $volId ? get_volunteer_matches($volId, $pdo) : [];

    $applied = [];
    if ($volId) {
        $stmt = $pdo->prepare('SELECT a.status, a.applied_at, l.title, l.id AS listing_id, o.org_name FROM applications a JOIN listings l ON l.id = a.listing_id JOIN organizations o ON o.id = l.org_id WHERE a.volunteer_id = ? ORDER BY a.applied_at DESC');
        $stmt->execute([$volId]);
        $applied = $stmt->fetchAll();
    }
} else {
    $stmt = $pdo->prepare('SELECT * FROM organizations WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $org   = $stmt->fetch();
    $orgId = $org ? $org['id'] : null;
    $listings = [];
    if ($orgId) {
        $stmt = $pdo->prepare('SELECT l.*, (SELECT COUNT(*) FROM applications a WHERE a.listing_id = l.id) AS applicant_count FROM listings l WHERE l.org_id = ? ORDER BY l.created_at DESC');
        $stmt->execute([$orgId]);
        $listings = $stmt->fetchAll();
    }
}

$successMsg = get_flash('success');
$errorMsg   = get_flash('error');
$pageTitle  = 'Dashboard';
include __DIR__ . '/includes/head.php';
?>

<div class="min-h-screen bg-surface-900">

  <!-- Topbar -->
  <header class="glass border-b border-slate-800 sticky top-0 z-40">
    <div class="max-w-5xl mx-auto px-5 h-14 flex items-center justify-between">
      <a href="/" class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-md bg-violet-600 flex items-center justify-center">
          <i data-lucide="zap" class="w-3.5 h-3.5 text-white"></i>
        </div>
        <span class="font-extrabold text-sm">impact<span class="text-violet-400">Nav</span></span>
      </a>
      <div class="flex items-center gap-3">
        <span class="text-xs text-slate-500 hidden sm:block"><?= h($user['full_name']) ?></span>
        <?php if ($user['role'] === 'org'): ?>
        <a href="/listing-create.php" class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white font-semibold transition-all">
          <i data-lucide="plus" class="w-3.5 h-3.5"></i> Yeni İlan
        </a>
        <?php endif; ?>
        <a href="/logout.php" class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-slate-700 text-slate-400 hover:text-slate-200 hover:border-slate-600 transition-all">
          <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Çıkış
        </a>
      </div>
    </div>
  </header>

  <!-- Page header banner -->
  <div class="relative overflow-hidden border-b border-slate-800">
    <div class="absolute inset-0 bg-gradient-to-r from-violet-900/30 via-surface-800 to-surface-800"></div>
    <div class="absolute inset-0 bg-glow-violet opacity-50"></div>
    <?php if ($user['role'] === 'volunteer'): ?>
    <div class="absolute right-0 top-0 bottom-0 w-64 opacity-10 hidden sm:block">
      <img src="https://images.unsplash.com/photo-1531545514256-b1400bc00f31?w=400&q=80&auto=format&fit=crop" alt="" class="w-full h-full object-cover">
      <div class="absolute inset-0 bg-gradient-to-r from-surface-800 to-transparent"></div>
    </div>
    <?php endif; ?>
    <div class="relative max-w-5xl mx-auto px-5 py-8">
      <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 rounded-xl <?= $user['role'] === 'volunteer' ? 'bg-violet-600' : 'bg-cyan-700' ?> flex items-center justify-center">
          <i data-lucide="<?= $user['role'] === 'volunteer' ? 'heart-handshake' : 'building-2' ?>" class="w-5 h-5 text-white"></i>
        </div>
        <div>
          <h1 class="text-lg font-extrabold text-white">
            <?= $user['role'] === 'volunteer' ? 'Hoş geldin, ' . h($user['full_name']) . '!' : h($org['org_name'] ?? $user['full_name']) ?>
          </h1>
          <p class="text-slate-400 text-xs">
            <?= $user['role'] === 'volunteer' ? 'AI senin için en uygun fırsatları buluyor.' : 'İlanlarınızı yönetin ve başvuruları inceleyin.' ?>
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Content -->
  <main class="max-w-5xl mx-auto px-5 py-8">

    <?php if ($successMsg): ?>
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-900/20 border border-emerald-800/40 text-emerald-400 text-sm mb-6">
      <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> <?= h($successMsg) ?>
    </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-red-900/20 border border-red-800/40 text-red-400 text-sm mb-6">
      <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i> <?= h($errorMsg) ?>
    </div>
    <?php endif; ?>

    <?php if ($user['role'] === 'volunteer'): ?>
    <!-- ─── VOLUNTEER VIEW ─── -->

    <?php if (!$profile): ?>
      <div class="rounded-2xl border border-amber-800/40 bg-amber-900/10 p-6 flex gap-4">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5"></i>
        <div>
          <p class="text-amber-300 font-semibold mb-1">Profil tamamlanmamış</p>
          <p class="text-slate-400 text-sm mb-3">Eşleşme bulabilmek için profilinizi tamamlayın.</p>
          <a href="/register.php?role=volunteer" class="inline-flex items-center gap-1.5 text-sm text-violet-400 hover:text-violet-300 font-semibold">
            <i data-lucide="arrow-right" class="w-4 h-4"></i> Profili tamamla
          </a>
        </div>
      </div>

    <?php elseif (empty($matches)): ?>
      <div class="rounded-2xl border border-slate-800 bg-surface-800/50 p-10 text-center">
        <div class="w-14 h-14 rounded-2xl bg-violet-900/30 border border-violet-800/30 flex items-center justify-center mx-auto mb-4">
          <i data-lucide="search" class="w-7 h-7 text-violet-400"></i>
        </div>
        <p class="text-white font-bold mb-1.5">Henüz eşleşme yok</p>
        <p class="text-slate-400 text-sm mb-6 max-w-xs mx-auto">AI profilinizi analiz ediyor. Eşleşmeleri tetiklemek için aşağıdaki butona tıklayın.</p>
        <form method="POST" action="/api/match.php">
          <?= csrf_field() ?>
          <input type="hidden" name="volunteer_user_id" value="<?= $user['id'] ?>">
          <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-semibold text-sm transition-all shadow-glow-sm">
            <i data-lucide="sparkles" class="w-4 h-4"></i> Eşleşmeleri Analiz Et
          </button>
        </form>
      </div>

    <?php else: ?>
      <!-- Match header -->
      <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-white flex items-center gap-2">
          <i data-lucide="sparkles" class="w-4 h-4 text-violet-400"></i>
          AI Eşleşmelerin
          <span class="text-xs font-normal text-slate-500 bg-surface-800 border border-slate-700 px-2 py-0.5 rounded-full"><?= count($matches) ?></span>
        </h2>
        <form method="POST" action="/api/match.php">
          <?= csrf_field() ?>
          <input type="hidden" name="volunteer_user_id" value="<?= $user['id'] ?>">
          <button type="submit" class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-slate-700 text-slate-400 hover:text-violet-300 hover:border-violet-700/50 transition-all">
            <i data-lucide="refresh-cw" class="w-3 h-3"></i> Yenile
          </button>
        </form>
      </div>

      <div class="space-y-3">
        <?php foreach ($matches as $m): ?>
        <div class="rounded-2xl border border-slate-800 bg-surface-800/50 hover:border-violet-800/40 hover:bg-surface-750/60 transition-all p-5">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div class="flex gap-3 min-w-0">
              <div class="w-10 h-10 rounded-xl bg-violet-900/40 border border-violet-800/30 flex items-center justify-center flex-shrink-0">
                <i data-lucide="briefcase" class="w-4 h-4 text-violet-400"></i>
              </div>
              <div class="min-w-0">
                <h3 class="font-bold text-white text-sm leading-snug"><?= h($m['title']) ?></h3>
                <p class="text-slate-400 text-xs mt-0.5 flex items-center gap-1">
                  <i data-lucide="building-2" class="w-3 h-3"></i> <?= h($m['org_name']) ?>
                </p>
              </div>
            </div>
            <div class="flex-shrink-0 flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-violet-900/40 border border-violet-700/40 text-violet-300 text-xs font-bold">
              <i data-lucide="zap" class="w-3 h-3"></i>
              %<?= round($m['score'] * 100) ?>
            </div>
          </div>

          <?php if ($m['rationale']): ?>
          <div class="bg-surface-700/50 border-l-2 border-violet-600 rounded-r-lg px-3 py-2 mb-3">
            <p class="text-slate-400 text-xs leading-relaxed italic"><?= h($m['rationale']) ?></p>
          </div>
          <?php endif; ?>

          <div class="flex items-center justify-between">
            <div class="flex flex-wrap gap-1.5">
              <?php if ($m['city']): ?><span class="flex items-center gap-1 text-[10px] text-slate-500 bg-surface-900/80 border border-slate-800 px-2 py-0.5 rounded-md"><i data-lucide="map-pin" class="w-2.5 h-2.5"></i><?= h($m['city']) ?></span><?php endif; ?>
              <?php if ($m['remote_ok']): ?><span class="flex items-center gap-1 text-[10px] text-cyan-500 bg-cyan-900/20 border border-cyan-800/30 px-2 py-0.5 rounded-md"><i data-lucide="wifi" class="w-2.5 h-2.5"></i>Uzaktan</span><?php endif; ?>
              <?php if ($m['weekly_hours']): ?><span class="flex items-center gap-1 text-[10px] text-slate-500 bg-surface-900/80 border border-slate-800 px-2 py-0.5 rounded-md"><i data-lucide="clock" class="w-2.5 h-2.5"></i><?= h($m['weekly_hours']) ?> s/hafta</span><?php endif; ?>
            </div>
            <a href="/listing-view.php?id=<?= $m['listing_id'] ?>" class="flex items-center gap-1 text-xs text-violet-400 hover:text-violet-300 font-semibold transition-colors">
              Detay <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Applied listings -->
    <?php if (!empty($applied)): ?>
    <div class="mt-10">
      <h2 class="font-bold text-white flex items-center gap-2 mb-4">
        <i data-lucide="send" class="w-4 h-4 text-slate-400"></i> Başvurularım
        <span class="text-xs font-normal text-slate-500 bg-surface-800 border border-slate-700 px-2 py-0.5 rounded-full"><?= count($applied) ?></span>
      </h2>
      <div class="space-y-2">
        <?php foreach ($applied as $app):
          $statusInfo = ['pending' => ['clock', 'text-amber-400', 'bg-amber-900/20 border-amber-800/30', 'Bekliyor'], 'accepted' => ['check-circle', 'text-emerald-400', 'bg-emerald-900/20 border-emerald-800/30', 'Kabul'], 'rejected' => ['x-circle', 'text-red-400', 'bg-red-900/20 border-red-800/30', 'Reddedildi']];
          [$sIcon, $sColor, $sBg, $sLabel] = $statusInfo[$app['status']] ?? ['circle', 'text-slate-400', 'bg-slate-800 border-slate-700', '?'];
        ?>
        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-surface-800/30 px-4 py-3">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-white truncate"><?= h($app['title']) ?></p>
            <p class="text-xs text-slate-500 flex items-center gap-1"><i data-lucide="building-2" class="w-3 h-3"></i> <?= h($app['org_name']) ?></p>
          </div>
          <span class="flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border flex-shrink-0 <?= $sBg ?> <?= $sColor ?>">
            <i data-lucide="<?= $sIcon ?>" class="w-3 h-3"></i> <?= $sLabel ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- ─── ORG VIEW ─── -->

    <div class="flex items-center justify-between mb-5">
      <h2 class="font-bold text-white flex items-center gap-2">
        <i data-lucide="list" class="w-4 h-4 text-slate-400"></i> İlanlarım
        <span class="text-xs font-normal text-slate-500 bg-surface-800 border border-slate-700 px-2 py-0.5 rounded-full"><?= count($listings) ?></span>
      </h2>
      <a href="/listing-create.php" class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white font-semibold transition-all">
        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Yeni İlan
      </a>
    </div>

    <?php if (empty($listings)): ?>
      <div class="rounded-2xl border border-slate-800 bg-surface-800/50 p-10 text-center">
        <div class="w-14 h-14 rounded-2xl bg-surface-700 border border-slate-700 flex items-center justify-center mx-auto mb-4">
          <i data-lucide="file-plus" class="w-7 h-7 text-slate-500"></i>
        </div>
        <p class="text-white font-bold mb-1.5">Henüz ilan yok</p>
        <p class="text-slate-400 text-sm mb-5">İlk gönüllü ilanınızı oluşturun.</p>
        <a href="/listing-create.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-semibold text-sm transition-all">
          <i data-lucide="plus" class="w-4 h-4"></i> İlan Oluştur
        </a>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($listings as $l):
          $statusInfo = ['active' => ['circle-check', 'text-emerald-400', 'Aktif'], 'paused' => ['pause-circle', 'text-amber-400', 'Durduruldu'], 'closed' => ['circle-x', 'text-red-400', 'Kapandı']];
          [$stIcon, $stColor, $stLabel] = $statusInfo[$l['status']] ?? ['circle', 'text-slate-400', '?'];
        ?>
        <div class="rounded-2xl border border-slate-800 bg-surface-800/50 hover:border-violet-800/30 transition-all p-5">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <h3 class="font-bold text-white"><?= h($l['title']) ?></h3>
              <div class="flex items-center gap-3 mt-1">
                <?php if ($l['city']): ?><span class="flex items-center gap-1 text-xs text-slate-500"><i data-lucide="map-pin" class="w-3 h-3"></i><?= h($l['city']) ?></span><?php endif; ?>
                <?php if ($l['remote_ok']): ?><span class="flex items-center gap-1 text-xs text-cyan-500"><i data-lucide="wifi" class="w-3 h-3"></i>Uzaktan</span><?php endif; ?>
              </div>
            </div>
            <span class="flex items-center gap-1.5 text-xs font-semibold flex-shrink-0 <?= $stColor ?>">
              <i data-lucide="<?= $stIcon ?>" class="w-3.5 h-3.5"></i> <?= $stLabel ?>
            </span>
          </div>

          <p class="text-slate-500 text-xs leading-relaxed mb-4 line-clamp-2"><?= h($l['description']) ?></p>

          <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-violet-300 bg-violet-900/30 border border-violet-800/30 px-2.5 py-1 rounded-full">
              <i data-lucide="users" class="w-3 h-3"></i> <?= $l['applicant_count'] ?> başvuru
            </span>
            <a href="/listing-view.php?id=<?= $l['id'] ?>" class="text-xs text-slate-400 hover:text-slate-200 flex items-center gap-1 transition-colors">
              <i data-lucide="eye" class="w-3 h-3"></i> Önizle
            </a>
            <a href="/applications.php?listing_id=<?= $l['id'] ?>" class="text-xs text-violet-400 hover:text-violet-300 flex items-center gap-1 transition-colors font-semibold">
              <i data-lucide="inbox" class="w-3 h-3"></i> Başvurular
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php endif; ?>

  </main>
</div>

<?php include __DIR__ . '/includes/chatbot.php'; ?>
<script>lucide.createIcons();</script>
</body>
</html>
