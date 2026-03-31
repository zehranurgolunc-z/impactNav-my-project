<?php
require_once __DIR__ . '/functions.php';
session_init($config['app']['session_name']);
require_role('org');

$pdo    = pdo();
$user   = current_user();
$errors = [];

$stmt = $pdo->prepare('SELECT * FROM organizations WHERE user_id = ?');
$stmt->execute([$user['id']]);
$org = $stmt->fetch();
if (!$org) { flash('error', 'Kurum profili bulunamadı.'); redirect('/dashboard.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Geçersiz istek.'; }
    else {
        $title  = trim($_POST['title'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $skills = trim($_POST['required_skills'] ?? '');
        $city   = trim($_POST['city'] ?? '');
        $remote = isset($_POST['remote_ok']) ? 1 : 0;
        $hours  = (int)($_POST['weekly_hours'] ?? 0);
        $cats   = $_POST['categories'] ?? [];
        if (!$title) $errors[] = 'İlan başlığı gerekli.';
        if (!$desc)  $errors[] = 'İlan açıklaması gerekli.';
        if (empty($errors)) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO listings (org_id, title, description, required_skills, city, remote_ok, weekly_hours) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$org['id'], $title, $desc, $skills, $city, $remote, $hours ?: null]);
                $listingId = (int)$pdo->lastInsertId();
                if ($cats) { $stmt = $pdo->prepare('INSERT IGNORE INTO listing_categories (listing_id, category_id) VALUES (?,?)'); foreach ((array)$cats as $cId) $stmt->execute([$listingId, (int)$cId]); }
                if (!empty($config['openai']['api_key'])) {
                    $stmt2 = $pdo->prepare('SELECT c.label FROM listing_categories lc JOIN categories c ON c.id = lc.category_id WHERE lc.listing_id = ?');
                    $stmt2->execute([$listingId]);
                    $catLabels = array_column($stmt2->fetchAll(), 'label');
                    $listingRow = ['title' => $title, 'description' => $desc, 'required_skills' => $skills, 'city' => $city, 'remote_ok' => $remote];
                    try { $embed = openai_embedding(build_listing_text($listingRow, $org['org_name'], $catLabels), $config['openai']); $stmt3 = $pdo->prepare('UPDATE listings SET embedding = ?, embedding_at = NOW() WHERE id = ?'); $stmt3->execute([json_encode($embed), $listingId]); } catch (Exception $e) {}
                }
                $pdo->commit();
                flash('success', 'İlan başarıyla yayınlandı!');
                redirect('/dashboard.php');
            } catch (Exception $e) { $pdo->rollBack(); $errors[] = $e->getMessage(); }
        }
    }
}

$categories = all_categories($pdo);
$pageTitle  = 'Yeni İlan Oluştur';
include __DIR__ . '/includes/head.php';
?>

<div class="min-h-screen bg-surface-900">
  <header class="glass border-b border-slate-800 sticky top-0 z-40">
    <div class="max-w-3xl mx-auto px-5 h-14 flex items-center justify-between">
      <a href="/dashboard.php" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-sm">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Dashboard
      </a>
      <span class="text-sm font-semibold text-white">Yeni İlan Oluştur</span>
      <a href="/" class="flex items-center gap-1.5">
        <div class="w-6 h-6 rounded-md bg-violet-600 flex items-center justify-center">
          <i data-lucide="zap" class="w-3 h-3 text-white"></i>
        </div>
      </a>
    </div>
  </header>

  <!-- Banner image -->
  <div class="relative h-32 overflow-hidden">
    <img src="https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?w=1200&q=80&auto=format&fit=crop" alt="" class="w-full h-full object-cover opacity-20">
    <div class="absolute inset-0 bg-gradient-to-b from-transparent to-surface-900"></div>
    <div class="absolute inset-0 flex items-end pb-5 px-5 max-w-3xl mx-auto">
      <div>
        <h1 class="text-xl font-extrabold text-white">Gönüllü İlanı Oluştur</h1>
        <p class="text-slate-400 text-xs">İlanınız AI ile gönüllülere otomatik eşleştirilecek.</p>
      </div>
    </div>
  </div>

  <div class="max-w-3xl mx-auto px-5 py-8">
    <?php foreach ($errors as $err): ?>
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-red-900/20 border border-red-800/40 text-red-400 text-sm mb-4">
      <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i> <?= h($err) ?>
    </div>
    <?php endforeach; ?>

    <form method="POST" action="/listing-create.php" class="space-y-5">
      <?= csrf_field() ?>

      <!-- Title -->
      <div class="rounded-2xl border border-slate-700/50 bg-surface-800 p-5">
        <label class="block text-xs font-semibold text-slate-400 mb-2">İlan Başlığı *</label>
        <input type="text" name="title" value="<?= h($_POST['title'] ?? '') ?>"
          class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 focus:ring-2 focus:ring-violet-600/20 text-slate-100 placeholder-slate-600 rounded-xl px-4 py-3 text-sm outline-none transition-all"
          placeholder="Örn: Sosyal Medya Gönüllüsü Aranıyor" required>
      </div>

      <!-- Description -->
      <div class="rounded-2xl border border-slate-700/50 bg-surface-800 p-5">
        <label class="block text-xs font-semibold text-slate-400 mb-2">İlan Açıklaması *</label>
        <textarea name="description" rows="5"
          class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 focus:ring-2 focus:ring-violet-600/20 text-slate-100 placeholder-slate-600 rounded-xl px-4 py-3 text-sm outline-none transition-all resize-none"
          placeholder="Gönüllünün yapacağı işler, beklentiler ve katkı alanlarını açıklayın..."><?= h($_POST['description'] ?? '') ?></textarea>
      </div>

      <!-- Skills + Location -->
      <div class="rounded-2xl border border-slate-700/50 bg-surface-800 p-5 space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-2">Aranan Beceriler <span class="font-normal text-slate-600">(virgülle ayırın)</span></label>
          <div class="relative">
            <i data-lucide="wrench" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input type="text" name="required_skills" value="<?= h($_POST['required_skills'] ?? '') ?>"
              class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm outline-none transition-all"
              placeholder="Sosyal medya, Canva, İletişim">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-2">Şehir</label>
            <div class="relative">
              <i data-lucide="map-pin" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
              <input type="text" name="city" value="<?= h($_POST['city'] ?? '') ?>"
                class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl pl-9 pr-3 py-3 text-sm outline-none transition-all"
                placeholder="İstanbul">
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-2">Haftalık Saat</label>
            <div class="relative">
              <i data-lucide="clock" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
              <input type="number" name="weekly_hours" value="<?= h($_POST['weekly_hours'] ?? '') ?>" min="1" max="40"
                class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl pl-9 pr-3 py-3 text-sm outline-none transition-all"
                placeholder="5">
            </div>
          </div>
        </div>

        <label class="flex items-center gap-3 cursor-pointer">
          <div class="relative">
            <input type="checkbox" name="remote_ok" value="1" class="sr-only peer" <?= !empty($_POST['remote_ok']) ? 'checked' : '' ?>>
            <div class="w-10 h-5 bg-slate-700 rounded-full peer-checked:bg-violet-600 transition-colors"></div>
            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
          </div>
          <span class="text-sm text-slate-300">Uzaktan (Remote) çalışılabilir</span>
        </label>
      </div>

      <!-- Categories -->
      <?php if ($categories): ?>
      <div class="rounded-2xl border border-slate-700/50 bg-surface-800 p-5">
        <label class="block text-xs font-semibold text-slate-400 mb-3">Kategoriler</label>
        <div class="grid grid-cols-2 gap-2">
          <?php foreach ($categories as $cat): ?>
          <label class="flex items-center gap-2.5 cursor-pointer p-2.5 rounded-lg border border-slate-700/60 hover:border-violet-700/50 transition-colors has-[:checked]:border-violet-600 has-[:checked]:bg-violet-900/20 group">
            <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
              class="w-3.5 h-3.5 accent-violet-600 rounded"
              <?= in_array($cat['id'], (array)($_POST['categories'] ?? [])) ? 'checked' : '' ?>>
            <span class="text-xs text-slate-400 group-has-[:checked]:text-violet-300 transition-colors"><?= h($cat['label']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <button type="submit" class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-glow hover:-translate-y-0.5">
        <i data-lucide="send" class="w-4 h-4"></i> İlanı Yayınla
      </button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/chatbot.php'; ?>
<script>lucide.createIcons();</script>
</body>
</html>
