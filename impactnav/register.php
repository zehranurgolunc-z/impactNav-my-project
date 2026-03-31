<?php
require_once __DIR__ . '/functions.php';
session_init($config['app']['session_name']);
if (is_logged_in()) redirect('/dashboard.php');

$role   = in_array($_GET['role'] ?? '', ['volunteer', 'org']) ? $_GET['role'] : 'volunteer';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Geçersiz istek.'; }
    else {
        $role      = in_array($_POST['role'] ?? '', ['volunteer', 'org']) ? $_POST['role'] : 'volunteer';
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        if (!$full_name) $errors[] = 'Ad soyad gerekli.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
        if (strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';

        if (empty($errors)) {
            $pdo = pdo();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) { $errors[] = 'Bu e-posta adresi zaten kayıtlı.'; }
            else {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, full_name) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $role, $full_name]);
                    $userId = (int)$pdo->lastInsertId();
                    if ($role === 'volunteer') {
                        $city = trim($_POST['city'] ?? ''); $district = trim($_POST['district'] ?? '');
                        $remote = isset($_POST['remote_ok']) ? 1 : 0;
                        $skills = trim($_POST['skills'] ?? ''); $interests = trim($_POST['interests'] ?? '');
                        $bio = trim($_POST['bio'] ?? ''); $cats = $_POST['categories'] ?? [];
                        $stmt = $pdo->prepare('INSERT INTO volunteer_profiles (user_id, city, district, remote_ok, skills, interests, bio) VALUES (?,?,?,?,?,?,?)');
                        $stmt->execute([$userId, $city, $district, $remote, $skills, $interests, $bio]);
                        $volId = (int)$pdo->lastInsertId();
                        if ($cats) { $stmt = $pdo->prepare('INSERT IGNORE INTO volunteer_categories (volunteer_id, category_id) VALUES (?, ?)'); foreach ((array)$cats as $cId) $stmt->execute([$volId, (int)$cId]); }
                    } else {
                        $org_name = trim($_POST['org_name'] ?? '');
                        if (!$org_name) throw new Exception('Kurum adı gerekli.');
                        $stmt = $pdo->prepare('INSERT INTO organizations (user_id, org_name, org_type, city, website, description) VALUES (?,?,?,?,?,?)');
                        $stmt->execute([$userId, $org_name, trim($_POST['org_type'] ?? ''), trim($_POST['city'] ?? ''), trim($_POST['website'] ?? ''), trim($_POST['description'] ?? '')]);
                    }
                    $pdo->commit();
                    $_SESSION['user'] = ['id' => $userId, 'email' => $email, 'role' => $role, 'full_name' => $full_name];
                    redirect('/dashboard.php');
                } catch (Exception $e) { $pdo->rollBack(); $errors[] = $e->getMessage(); }
            }
        }
    }
}

$categories = [];
try { $categories = all_categories(pdo()); } catch (Exception $e) {}
$pageTitle = $role === 'org' ? 'Kurum Kaydı' : 'Gönüllü Kaydı';
include __DIR__ . '/includes/head.php';
?>

<div class="min-h-screen bg-surface-900 py-10 px-4">
  <!-- Header -->
  <div class="max-w-lg mx-auto mb-8 text-center">
    <a href="/" class="inline-flex items-center gap-2 mb-6">
      <div class="w-8 h-8 rounded-lg bg-violet-600 flex items-center justify-center shadow-glow-sm">
        <i data-lucide="zap" class="w-4 h-4 text-white"></i>
      </div>
      <span class="font-extrabold text-lg">impact<span class="text-violet-400">Nav</span></span>
    </a>

    <!-- Role toggle -->
    <div class="inline-flex p-1 rounded-xl bg-surface-800 border border-slate-700/50 gap-1">
      <a href="/register.php?role=volunteer" class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition-all <?= $role === 'volunteer' ? 'bg-violet-600 text-white shadow-glow-sm' : 'text-slate-400 hover:text-slate-200' ?>">
        <i data-lucide="heart-handshake" class="w-4 h-4"></i> Gönüllüyüm
      </a>
      <a href="/register.php?role=org" class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition-all <?= $role === 'org' ? 'bg-violet-600 text-white shadow-glow-sm' : 'text-slate-400 hover:text-slate-200' ?>">
        <i data-lucide="building-2" class="w-4 h-4"></i> Kurumum
      </a>
    </div>
  </div>

  <div class="max-w-lg mx-auto rounded-2xl border border-slate-700/50 bg-surface-800 p-8 shadow-card">
    <h1 class="text-xl font-extrabold text-white mb-1"><?= $role === 'org' ? 'Kurum Kaydı' : 'Gönüllü Profili Oluştur' ?></h1>
    <p class="text-slate-400 text-sm mb-7"><?= $role === 'org' ? 'Kurumunuzu kaydedin, gönüllülerle buluşun.' : 'Profilinizi oluşturun, AI ile en uygun ilanları bulun.' ?></p>

    <?php foreach ($errors as $err): ?>
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-red-900/20 border border-red-800/40 text-red-400 text-sm mb-4">
      <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i> <?= h($err) ?>
    </div>
    <?php endforeach; ?>

    <form method="POST" action="/register.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="role" value="<?= h($role) ?>">

      <!-- Temel bilgiler -->
      <div>
        <label class="block text-xs font-semibold text-slate-400 mb-1.5"><?= $role === 'org' ? 'Yetkili Ad Soyad' : 'Ad Soyad' ?></label>
        <div class="relative">
          <i data-lucide="user" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
          <input type="text" name="full_name" value="<?= h($_POST['full_name'] ?? '') ?>"
            class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 focus:ring-2 focus:ring-violet-600/20 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm outline-none transition-all"
            placeholder="Örn: Ayşe Kaya" required>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-400 mb-1.5">E-posta</label>
        <div class="relative">
          <i data-lucide="mail" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
          <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>"
            class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 focus:ring-2 focus:ring-violet-600/20 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm outline-none transition-all"
            placeholder="ornek@eposta.com" required>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Şifre</label>
        <div class="relative">
          <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
          <input type="password" name="password"
            class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 focus:ring-2 focus:ring-violet-600/20 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm outline-none transition-all"
            placeholder="En az 8 karakter" required>
        </div>
      </div>

      <?php if ($role === 'org'): ?>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Kurum Adı</label>
          <div class="relative">
            <i data-lucide="building-2" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input type="text" name="org_name" value="<?= h($_POST['org_name'] ?? '') ?>"
              class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 focus:ring-2 focus:ring-violet-600/20 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm outline-none transition-all"
              placeholder="Örn: Açık Kapı Derneği" required>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Kurum Türü</label>
            <select name="org_type" class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 rounded-xl px-3 py-3 text-sm outline-none transition-all appearance-none">
              <option value="">Seçiniz</option>
              <?php foreach (['Dernek','Vakıf','Kooperatif','Sosyal Girişim','Diğer'] as $t): ?>
              <option value="<?= $t ?>" <?= (($_POST['org_type'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Şehir</label>
            <div class="relative">
              <i data-lucide="map-pin" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
              <input type="text" name="city" value="<?= h($_POST['city'] ?? '') ?>"
                class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl pl-9 pr-3 py-3 text-sm outline-none transition-all"
                placeholder="İstanbul">
            </div>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Web Sitesi <span class="text-slate-600 font-normal">(isteğe bağlı)</span></label>
          <div class="relative">
            <i data-lucide="globe" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input type="url" name="website" value="<?= h($_POST['website'] ?? '') ?>"
              class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm outline-none transition-all"
              placeholder="https://kurumunuz.org">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Kurum Hakkında</label>
          <textarea name="description" rows="3"
            class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 focus:ring-2 focus:ring-violet-600/20 text-slate-100 placeholder-slate-600 rounded-xl px-4 py-3 text-sm outline-none transition-all resize-none"
            placeholder="Misyonunuz ve çalışmalarınız..."><?= h($_POST['description'] ?? '') ?></textarea>
        </div>

      <?php else: ?>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Şehir</label>
            <div class="relative">
              <i data-lucide="map-pin" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
              <input type="text" name="city" value="<?= h($_POST['city'] ?? '') ?>"
                class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl pl-9 pr-3 py-3 text-sm outline-none transition-all"
                placeholder="İstanbul">
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1.5">İlçe</label>
            <input type="text" name="district" value="<?= h($_POST['district'] ?? '') ?>"
              class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl px-4 py-3 text-sm outline-none transition-all"
              placeholder="Beşiktaş">
          </div>
        </div>

        <label class="flex items-center gap-3 cursor-pointer">
          <div class="relative">
            <input type="checkbox" name="remote_ok" value="1" class="sr-only peer" <?= !empty($_POST['remote_ok']) ? 'checked' : '' ?>>
            <div class="w-10 h-5 bg-slate-700 rounded-full peer-checked:bg-violet-600 transition-colors"></div>
            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
          </div>
          <span class="text-sm text-slate-300">Uzaktan (Remote) çalışabilirim</span>
        </label>

        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Beceriler <span class="text-slate-600 font-normal">(virgülle ayırın)</span></label>
          <div class="relative">
            <i data-lucide="wrench" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input type="text" name="skills" value="<?= h($_POST['skills'] ?? '') ?>"
              class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm outline-none transition-all"
              placeholder="iletişim, planlama, kodlama">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">İlgi Alanları <span class="text-slate-600 font-normal">(virgülle ayırın)</span></label>
          <div class="relative">
            <i data-lucide="heart" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input type="text" name="interests" value="<?= h($_POST['interests'] ?? '') ?>"
              class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm outline-none transition-all"
              placeholder="eğitim, çevre, topluluk">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Kendinizi Tanıtın <span class="text-slate-600 font-normal">(isteğe bağlı)</span></label>
          <textarea name="bio" rows="2"
            class="w-full bg-surface-750 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl px-4 py-3 text-sm outline-none transition-all resize-none"
            placeholder="Neden gönüllü olmak istiyorsunuz?"><?= h($_POST['bio'] ?? '') ?></textarea>
        </div>

        <?php if ($categories): ?>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-3">İlgilendiğiniz Kategoriler</label>
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
      <?php endif; ?>

      <button type="submit" class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-glow-sm hover:-translate-y-0.5 mt-2">
        <i data-lucide="sparkles" class="w-4 h-4"></i> Devam Et
      </button>
    </form>

    <p class="text-center text-sm text-slate-500 mt-5">
      Zaten hesabın var mı? <a href="/login.php" class="text-violet-400 hover:text-violet-300 font-semibold transition-colors">Giriş yap</a>
    </p>
  </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
