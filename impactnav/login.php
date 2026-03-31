<?php
require_once __DIR__ . '/functions.php';
session_init($config['app']['session_name']);
if (is_logged_in()) redirect('/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Geçersiz istek.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        if (!$email || !$pass) {
            $error = 'E-posta ve şifre gereklidir.';
        } else {
            $pdo  = pdo();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($pass, $user['password_hash'])) {
                $_SESSION['user'] = ['id' => $user['id'], 'email' => $user['email'], 'role' => $user['role'], 'full_name' => $user['full_name']];
                redirect('/dashboard.php');
            } else {
                $error = 'E-posta veya şifre hatalı.';
            }
        }
    }
}
$pageTitle = 'Giriş Yap';
include __DIR__ . '/includes/head.php';
?>

<div class="min-h-screen flex">
  <!-- Left visual -->
  <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
    <img src="https://images.unsplash.com/photo-1559027615-cd4628902d4a?w=900&q=80&auto=format&fit=crop" alt="" class="w-full h-full object-cover opacity-30">
    <div class="absolute inset-0 bg-gradient-to-r from-surface-900/20 to-surface-900/80"></div>
    <div class="absolute inset-0 flex flex-col justify-between p-12">
      <a href="/" class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-violet-600 flex items-center justify-center shadow-glow-sm">
          <i data-lucide="zap" class="w-4 h-4 text-white"></i>
        </div>
        <span class="font-extrabold text-lg">impact<span class="text-violet-400">Nav</span></span>
      </a>
      <div>
        <blockquote class="text-xl font-bold text-white mb-3 leading-relaxed">"Gönüllülük, toplumun özüdür."</blockquote>
        <p class="text-slate-400 text-sm">Binlerce gönüllü bu platformda sosyal etki yaratıyor.</p>
      </div>
    </div>
  </div>

  <!-- Right form -->
  <div class="flex-1 flex flex-col justify-center items-center px-5 py-12 bg-surface-900">
    <!-- Mobile logo -->
    <a href="/" class="flex items-center gap-2 mb-10 lg:hidden">
      <div class="w-8 h-8 rounded-lg bg-violet-600 flex items-center justify-center">
        <i data-lucide="zap" class="w-4 h-4 text-white"></i>
      </div>
      <span class="font-extrabold text-lg">impact<span class="text-violet-400">Nav</span></span>
    </a>

    <div class="w-full max-w-sm">
      <h1 class="text-2xl font-extrabold text-white mb-1.5">Tekrar hoş geldin</h1>
      <p class="text-slate-400 text-sm mb-8">Hesabına giriş yap ve eşleşmelerini gör.</p>

      <?php if ($error): ?>
      <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-red-900/20 border border-red-800/40 text-red-400 text-sm mb-6">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i> <?= h($error) ?>
      </div>
      <?php endif; ?>

      <?php if ($msg = get_flash('success')): ?>
      <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-900/20 border border-emerald-800/40 text-emerald-400 text-sm mb-6">
        <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i> <?= h($msg) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="/login.php" class="space-y-4">
        <?= csrf_field() ?>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">E-posta</label>
          <div class="relative">
            <i data-lucide="mail" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>"
              class="w-full bg-surface-800 border border-slate-700 focus:border-violet-600 focus:ring-2 focus:ring-violet-600/20 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-4 py-3 text-sm outline-none transition-all"
              placeholder="ornek@eposta.com" required>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Şifre</label>
          <div class="relative">
            <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
            <input type="password" name="password" id="pwField"
              class="w-full bg-surface-800 border border-slate-700 focus:border-violet-600 focus:ring-2 focus:ring-violet-600/20 text-slate-100 placeholder-slate-600 rounded-xl pl-10 pr-11 py-3 text-sm outline-none transition-all"
              placeholder="••••••••" required>
            <button type="button" onclick="togglePw()" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
              <i data-lucide="eye" id="eyeIcon" class="w-4 h-4"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold py-3 rounded-xl transition-all shadow-glow-sm hover:-translate-y-0.5 mt-2">
          <i data-lucide="log-in" class="w-4 h-4"></i> Giriş Yap
        </button>
      </form>

      <p class="text-center text-sm text-slate-500 mt-6">
        Hesabın yok mu? <a href="/register.php" class="text-violet-400 hover:text-violet-300 font-semibold transition-colors">Üye ol</a>
      </p>
    </div>
  </div>
</div>

<script>
function togglePw() {
  const f = document.getElementById('pwField');
  const i = document.getElementById('eyeIcon');
  if (f.type === 'password') { f.type = 'text'; i.setAttribute('data-lucide','eye-off'); }
  else { f.type = 'password'; i.setAttribute('data-lucide','eye'); }
  lucide.createIcons();
}
lucide.createIcons();
</script>
</body>
</html>
