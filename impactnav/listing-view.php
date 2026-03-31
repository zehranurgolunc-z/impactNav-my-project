<?php
require_once __DIR__ . '/functions.php';
session_init($config['app']['session_name']);

$pdo = pdo();
$id  = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT l.*, o.org_name, o.org_type, o.city AS org_city, o.website, o.description AS org_desc FROM listings l JOIN organizations o ON o.id = l.org_id WHERE l.id = ? AND l.status = \'active\'');
$stmt->execute([$id]);
$listing = $stmt->fetch();
if (!$listing) { http_response_code(404); echo '<h1 style="font-family:sans-serif;padding:2rem">İlan bulunamadı.</h1>'; exit; }

$stmt = $pdo->prepare('SELECT c.label FROM listing_categories lc JOIN categories c ON c.id = lc.category_id WHERE lc.listing_id = ?');
$stmt->execute([$id]);
$cats = array_column($stmt->fetchAll(), 'label');

$alreadyApplied = false; $volId = null; $user = current_user();
if ($user && $user['role'] === 'volunteer') {
    $stmt = $pdo->prepare('SELECT id FROM volunteer_profiles WHERE user_id = ?'); $stmt->execute([$user['id']]); $vp = $stmt->fetch();
    if ($vp) { $volId = $vp['id']; $stmt = $pdo->prepare('SELECT id FROM applications WHERE volunteer_id = ? AND listing_id = ?'); $stmt->execute([$volId, $id]); $alreadyApplied = (bool)$stmt->fetch(); }
}

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    if (!is_logged_in()) redirect('/login.php');
    if (!csrf_verify()) { $error = 'Geçersiz istek.'; }
    elseif ($alreadyApplied) { $error = 'Bu ilana zaten başvurdunuz.'; }
    elseif ($volId) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO applications (volunteer_id, listing_id, message) VALUES (?,?,?)');
        $stmt->execute([$volId, $id, trim($_POST['message'] ?? '')]);
        $alreadyApplied = true; $success = 'Başvurunuz alındı! STK tarafınızla iletişime geçecek.';
    }
}

$pageTitle = $listing['title'];
include __DIR__ . '/includes/head.php';
?>

<div class="min-h-screen bg-surface-900">
  <header class="glass border-b border-slate-800 sticky top-0 z-40">
    <div class="max-w-3xl mx-auto px-5 h-14 flex items-center justify-between">
      <a href="<?= is_logged_in() ? '/dashboard.php' : '/' ?>" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-sm">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Geri
      </a>
      <a href="/" class="flex items-center gap-1.5">
        <div class="w-6 h-6 rounded-md bg-violet-600 flex items-center justify-center">
          <i data-lucide="zap" class="w-3 h-3 text-white"></i>
        </div>
        <span class="text-sm font-extrabold">impact<span class="text-violet-400">Nav</span></span>
      </a>
      <?php if (is_logged_in()): ?>
      <a href="/logout.php" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">Çıkış</a>
      <?php else: ?>
      <a href="/login.php" class="text-xs text-violet-400 hover:text-violet-300 font-semibold transition-colors">Giriş Yap</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Hero image -->
  <div class="relative h-40 overflow-hidden">
    <img src="https://images.unsplash.com/photo-1559027615-cd4628902d4a?w=1200&q=80&auto=format&fit=crop" alt="" class="w-full h-full object-cover opacity-25">
    <div class="absolute inset-0 bg-gradient-to-b from-transparent to-surface-900"></div>
  </div>

  <div class="max-w-3xl mx-auto px-5 -mt-6 pb-16">

    <?php if ($error): ?>
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-red-900/20 border border-red-800/40 text-red-400 text-sm mb-4">
      <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i> <?= h($error) ?>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-900/20 border border-emerald-800/40 text-emerald-400 text-sm mb-4">
      <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> <?= h($success) ?>
    </div>
    <?php endif; ?>

    <!-- Main card -->
    <div class="rounded-2xl border border-slate-700/50 bg-surface-800 p-6 mb-4">
      <!-- Tags -->
      <div class="flex flex-wrap gap-2 mb-4">
        <?php if ($listing['city']): ?><span class="flex items-center gap-1 text-xs text-slate-400 bg-surface-700 border border-slate-700 px-2.5 py-1 rounded-lg"><i data-lucide="map-pin" class="w-3 h-3"></i> <?= h($listing['city']) ?></span><?php endif; ?>
        <?php if ($listing['remote_ok']): ?><span class="flex items-center gap-1 text-xs text-cyan-400 bg-cyan-900/20 border border-cyan-800/30 px-2.5 py-1 rounded-lg"><i data-lucide="wifi" class="w-3 h-3"></i> Uzaktan</span><?php endif; ?>
        <?php if ($listing['weekly_hours']): ?><span class="flex items-center gap-1 text-xs text-slate-400 bg-surface-700 border border-slate-700 px-2.5 py-1 rounded-lg"><i data-lucide="clock" class="w-3 h-3"></i> <?= h($listing['weekly_hours']) ?> s/hafta</span><?php endif; ?>
        <?php foreach ($cats as $c): ?><span class="text-xs text-violet-400 bg-violet-900/20 border border-violet-800/30 px-2.5 py-1 rounded-lg"><?= h($c) ?></span><?php endforeach; ?>
      </div>

      <h1 class="text-xl font-extrabold text-white mb-1.5"><?= h($listing['title']) ?></h1>
      <p class="text-violet-400 text-sm font-semibold mb-5 flex items-center gap-1.5">
        <i data-lucide="building-2" class="w-4 h-4"></i> <?= h($listing['org_name']) ?>
        <?php if ($listing['org_type']): ?><span class="text-slate-600">·</span><span class="text-slate-500 font-normal"><?= h($listing['org_type']) ?></span><?php endif; ?>
      </p>

      <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">İlan Açıklaması</h2>
      <p class="text-slate-300 text-sm leading-relaxed whitespace-pre-wrap mb-6"><?= h($listing['description']) ?></p>

      <?php if ($listing['required_skills']): ?>
      <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Aranan Beceriler</h2>
      <div class="flex flex-wrap gap-2">
        <?php foreach (explode(',', $listing['required_skills']) as $sk): ?>
        <span class="text-xs text-violet-300 bg-violet-900/25 border border-violet-800/30 px-3 py-1 rounded-full"><?= h(trim($sk)) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Org info -->
    <?php if ($listing['org_desc'] || $listing['website']): ?>
    <div class="rounded-2xl border border-slate-700/50 bg-surface-800 p-5 mb-4">
      <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
        <i data-lucide="building-2" class="w-3.5 h-3.5"></i> Kurum Hakkında
      </h2>
      <p class="font-bold text-white mb-1"><?= h($listing['org_name']) ?></p>
      <?php if ($listing['org_desc']): ?><p class="text-slate-400 text-sm leading-relaxed mb-3"><?= h(mb_substr($listing['org_desc'], 0, 280)) ?>...</p><?php endif; ?>
      <?php if ($listing['website']): ?><a href="<?= h($listing['website']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-sm text-violet-400 hover:text-violet-300 font-semibold transition-colors"><i data-lucide="external-link" class="w-3.5 h-3.5"></i> Web Sitesini Ziyaret Et</a><?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Apply box -->
    <?php if (!$user): ?>
    <div class="rounded-2xl border border-slate-700/50 bg-surface-800 p-6 text-center">
      <i data-lucide="lock" class="w-8 h-8 text-slate-600 mx-auto mb-3"></i>
      <p class="text-slate-400 text-sm mb-4">Başvurmak için giriş yapın.</p>
      <a href="/login.php" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-semibold text-sm transition-all">
        <i data-lucide="log-in" class="w-4 h-4"></i> Giriş Yap
      </a>
    </div>

    <?php elseif ($user['role'] === 'volunteer' && !$alreadyApplied): ?>
    <div class="rounded-2xl border border-violet-800/40 bg-violet-900/10 p-6">
      <h2 class="font-bold text-white mb-1 flex items-center gap-2">
        <i data-lucide="send" class="w-4 h-4 text-violet-400"></i> Başvur
      </h2>
      <p class="text-slate-400 text-xs mb-4">Motivasyon mesajınız STK'ya iletilecek.</p>
      <form method="POST" action="/listing-view.php?id=<?= $id ?>">
        <?= csrf_field() ?>
        <textarea name="message" rows="3"
          class="w-full bg-surface-800 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl px-4 py-3 text-sm outline-none transition-all resize-none mb-3"
          placeholder="Neden bu projede gönüllü olmak istiyorsunuz? (isteğe bağlı)"><?= h($_POST['message'] ?? '') ?></textarea>
        <button type="submit" name="apply" value="1" class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl transition-all">
          <i data-lucide="check" class="w-4 h-4"></i> Başvur
        </button>
      </form>
    </div>

    <?php elseif ($user['role'] === 'volunteer' && $alreadyApplied): ?>
    <div class="rounded-2xl border border-emerald-800/40 bg-emerald-900/10 p-5 flex items-center gap-3">
      <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 flex-shrink-0"></i>
      <p class="text-emerald-300 text-sm font-semibold">Bu ilana başvurdunuz. STK sizinle iletişime geçecek.</p>
    </div>

    <?php elseif ($user['role'] === 'org'): ?>
    <div class="rounded-2xl border border-slate-700/50 bg-surface-800 p-4 flex items-center gap-3">
      <i data-lucide="info" class="w-4 h-4 text-slate-500 flex-shrink-0"></i>
      <p class="text-slate-500 text-sm">Bu ilan kendi ilanınız.</p>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/includes/chatbot.php'; ?>
<script>lucide.createIcons();</script>
</body>
</html>
