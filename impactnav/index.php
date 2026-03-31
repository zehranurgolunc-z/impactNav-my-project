<?php
require_once __DIR__ . '/functions.php';
session_init($config['app']['session_name']);

// Featured listings for homepage
$featuredListings = [];
try {
    $pdo = pdo();
    $stmt = $pdo->query('SELECT l.id, l.title, l.city, l.remote_ok, l.weekly_hours, o.org_name, o.org_type FROM listings l JOIN organizations o ON o.id = l.org_id WHERE l.status = \'active\' ORDER BY l.created_at DESC LIMIT 3');
    $featuredListings = $stmt->fetchAll();
} catch (Exception $e) {}

$pageTitle = 'Ana Sayfa';
include __DIR__ . '/includes/head.php';
?>

<!-- ═══════════════════════════════════════════════ NAVBAR -->
<nav class="fixed top-0 inset-x-0 z-50 glass border-b border-violet-900/20">
  <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 select-none">
      <div class="w-8 h-8 rounded-lg bg-violet-600 flex items-center justify-center shadow-glow-sm">
        <i data-lucide="zap" class="w-4 h-4 text-white"></i>
      </div>
      <span class="font-extrabold text-lg tracking-tight">impact<span class="text-violet-400">Nav</span></span>
    </a>
    <div class="flex items-center gap-3">
      <?php if (is_logged_in()): ?>
        <a href="/dashboard.php" class="flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-100 transition-colors">
          <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
        </a>
        <a href="/logout.php" class="flex items-center gap-1.5 text-sm px-4 py-2 rounded-lg border border-violet-800/50 text-violet-300 hover:bg-violet-900/30 transition-all">
          <i data-lucide="log-out" class="w-4 h-4"></i> Çıkış
        </a>
      <?php else: ?>
        <a href="/login.php" class="text-sm text-slate-400 hover:text-slate-100 transition-colors px-3 py-2">Giriş Yap</a>
        <a href="/register.php" class="flex items-center gap-1.5 text-sm px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white font-semibold transition-all shadow-glow-sm">
          <i data-lucide="sparkles" class="w-4 h-4"></i> Üye Ol
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ═══════════════════════════════════════════════ HERO -->
<section class="relative min-h-screen flex flex-col justify-center overflow-hidden pt-16">
  <!-- Background image -->
  <div class="absolute inset-0">
    <img src="https://images.unsplash.com/photo-1593113598332-cd59a0c3a9a4?w=1400&q=80&auto=format&fit=crop" alt="" class="w-full h-full object-cover opacity-20">
    <div class="absolute inset-0 bg-gradient-to-b from-surface-900 via-surface-900/80 to-surface-900"></div>
    <div class="absolute inset-0 bg-glow-violet"></div>
  </div>

  <!-- Glow orbs -->
  <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-violet-600/10 rounded-full blur-3xl pointer-events-none"></div>
  <div class="absolute bottom-1/3 right-1/4 w-80 h-80 bg-cyan-600/8 rounded-full blur-3xl pointer-events-none"></div>

  <div class="relative max-w-4xl mx-auto px-5 py-24 text-center">
    <!-- Badge -->
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-violet-700/50 bg-violet-900/20 text-violet-300 text-xs font-semibold tracking-wider uppercase mb-8">
      <i data-lucide="sparkles" class="w-3 h-3"></i>
      Yapay Zeka Destekli Eşleştirme
    </div>

    <!-- Logo badge -->
    <div class="inline-flex mb-6 gradient-border rounded-2xl">
      <div class="px-10 py-4 rounded-2xl bg-surface-800/80 backdrop-blur-xl">
        <span class="text-4xl font-black tracking-tight text-violet-300">impact</span><span class="text-4xl font-black tracking-tight text-cyan-300">Nav</span>
      </div>
    </div>

    <h1 class="text-4xl sm:text-5xl font-extrabold leading-tight mb-5 text-white">
      Sosyal etkini<br>
      <span class="gradient-text">birlikte büyüt.</span>
    </h1>
    <p class="text-slate-400 text-lg max-w-xl mx-auto mb-10 leading-relaxed">
      Gönüllüler ve STK'ları doğru yetenekler, yüksek motivasyon ve yapay zeka ile eşleştiren yeni nesil ekosistem.
    </p>

    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="/register.php?role=volunteer" class="w-full sm:w-auto flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold text-base transition-all shadow-glow hover:-translate-y-0.5">
        <i data-lucide="heart-handshake" class="w-5 h-5"></i> Hemen Gönüllü Ol
      </a>
      <a href="/register.php?role=org" class="w-full sm:w-auto flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl border border-slate-700 hover:border-violet-600/60 bg-surface-800/60 hover:bg-surface-700/60 text-slate-200 font-bold text-base transition-all hover:-translate-y-0.5">
        <i data-lucide="building-2" class="w-5 h-5"></i> Kurum Girişi Yap
      </a>
    </div>

    <!-- Stats row -->
    <div class="mt-16 grid grid-cols-3 gap-4 max-w-lg mx-auto">
      <?php foreach ([['8', 'Aktif STK'], ['14', 'Açık İlan'], ['100+', 'Gönüllü']] as [$n, $l]): ?>
      <div class="rounded-xl border border-slate-800 bg-surface-800/50 py-4">
        <div class="text-2xl font-extrabold text-violet-400"><?= $n ?></div>
        <div class="text-xs text-slate-500 mt-0.5"><?= $l ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Scroll indicator -->
  <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1 text-slate-600">
    <i data-lucide="chevrons-down" class="w-5 h-5 animate-bounce"></i>
  </div>
</section>

<!-- ═══════════════════════════════════════════════ PROBLEM -->
<section class="py-24 relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-b from-surface-900 to-surface-800"></div>
  <div class="relative max-w-4xl mx-auto px-5">
    <div class="text-center mb-14">
      <p class="text-violet-400 text-sm font-semibold uppercase tracking-widest mb-3">Sorun Ne?</p>
      <h2 class="text-3xl font-extrabold text-white mb-3">Gönüllülük neden bu kadar zor?</h2>
      <p class="text-slate-400">impactNav bu üç temel sorunu yapay zeka ile çözüyor.</p>
    </div>

    <div class="grid gap-5">
      <?php
      $problems = [
        ['search', 'bg-red-900/20 border-red-800/30 text-red-400', 'Doğru eşleşme yok', 'Gönüllüler yeteneklerine uygun projeler bulamıyor, STK\'lar doğru kişilere ulaşamıyor.', 'https://images.unsplash.com/photo-1573497491765-dccce02b29df?w=400&q=80&auto=format&fit=crop'],
        ['clock', 'bg-amber-900/20 border-amber-800/30 text-amber-400', 'Zaman kaybı', 'Manuel başvuru süreçleri hem gönüllülerin hem STK\'ların zamanını çalıyor.', 'https://images.unsplash.com/photo-1501139083538-0139583c060f?w=400&q=80&auto=format&fit=crop'],
        ['bar-chart-2', 'bg-pink-900/20 border-pink-800/30 text-pink-400', 'Etki ölçülemiyor', 'Gönüllüler gelişimlerini takip edemiyor, STK\'lar etkilerini raporlayamıyor.', 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&q=80&auto=format&fit=crop'],
      ];
      foreach ($problems as [$icon, $cls, $title, $desc, $img]):
      ?>
      <div class="flex gap-5 rounded-2xl border border-slate-800 bg-surface-800/50 p-5 hover:border-violet-800/40 transition-colors group">
        <div class="w-14 h-14 rounded-xl <?= explode(' ', $cls)[0] ?> <?= explode(' ', $cls)[1] ?> border flex items-center justify-center flex-shrink-0">
          <i data-lucide="<?= $icon ?>" class="w-6 h-6 <?= explode(' ', $cls)[2] ?>"></i>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="font-bold text-white mb-1"><?= $title ?></h3>
          <p class="text-slate-400 text-sm leading-relaxed"><?= $desc ?></p>
        </div>
        <div class="w-20 h-14 rounded-lg overflow-hidden flex-shrink-0 opacity-60 group-hover:opacity-90 transition-opacity hidden sm:block">
          <img src="<?= $img ?>" alt="" class="w-full h-full object-cover">
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════ AI FEATURES -->
<section class="py-24 relative overflow-hidden">
  <div class="absolute inset-0 bg-surface-900"></div>
  <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-px bg-gradient-to-r from-transparent via-violet-600/40 to-transparent"></div>

  <div class="relative max-w-4xl mx-auto px-5">
    <div class="text-center mb-14">
      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-violet-700/40 bg-violet-900/20 text-violet-300 text-xs font-semibold uppercase tracking-wider mb-4">
        <i data-lucide="cpu" class="w-3 h-3"></i> 4 Temel AI Özelliği
      </span>
      <h2 class="text-3xl font-extrabold text-white mb-3">Yapay zeka ile dönüştürüyoruz</h2>
      <p class="text-slate-400">Her özellik, gönüllülük deneyimini daha akıllı ve etkili hale getirir.</p>
    </div>

    <!-- Feature image + cards layout -->
    <div class="grid sm:grid-cols-2 gap-5">
      <?php
      $features = [
        ['sparkles', 'text-violet-400', 'bg-violet-900/30 border-violet-800/40', 'Semantik Eşleştirme', 'Yüzde uyum skoru ile en doğru projeleri OpenAI embedding gücüyle anında bulur.'],
        ['mic', 'text-cyan-400', 'bg-cyan-900/20 border-cyan-800/30', 'Akıllı Mülakatçı', 'Başvuru esnasında açık uçlu sorular sorar ve STK için uyum raporu hazırlar.'],
        ['message-circle', 'text-emerald-400', 'bg-emerald-900/20 border-emerald-800/30', 'impactAI Chatbot', '7/24 rehber asistanın ile aklındaki fikri anında en uygun ilanlara dönüştürür.'],
        ['trending-up', 'text-amber-400', 'bg-amber-900/20 border-amber-800/30', 'Etki Takibi', 'Gönüllü saatlerini, tamamlanan projeleri ve sosyal etkiyi otomatik raporlar.'],
      ];
      foreach ($features as [$icon, $iconCls, $bgCls, $title, $desc]):
      ?>
      <div class="rounded-2xl border <?= $bgCls ?> p-6 hover:scale-[1.01] transition-transform">
        <div class="w-11 h-11 rounded-xl bg-surface-900/80 border border-slate-800 flex items-center justify-center mb-4">
          <i data-lucide="<?= $icon ?>" class="w-5 h-5 <?= $iconCls ?>"></i>
        </div>
        <h3 class="font-bold text-white mb-2"><?= $title ?></h3>
        <p class="text-slate-400 text-sm leading-relaxed"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Visual proof image -->
    <div class="mt-8 rounded-2xl overflow-hidden border border-slate-800 relative h-48 sm:h-64">
      <img src="https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=1200&q=80&auto=format&fit=crop" alt="AI Eşleştirme" class="w-full h-full object-cover opacity-40">
      <div class="absolute inset-0 bg-gradient-to-r from-surface-900/90 via-surface-900/40 to-transparent flex items-center px-8">
        <div>
          <p class="text-violet-300 text-xs font-semibold uppercase tracking-wider mb-2">Powered by OpenAI</p>
          <p class="text-white text-xl font-bold max-w-xs">Semantik anlama ile doğru eşleşme</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════ HOW IT WORKS -->
<section class="py-24 bg-surface-800 relative overflow-hidden">
  <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-violet-700/30 to-transparent"></div>
  <div class="max-w-4xl mx-auto px-5">
    <div class="text-center mb-14">
      <p class="text-violet-400 text-sm font-semibold uppercase tracking-widest mb-3">Nasıl Çalışır?</p>
      <h2 class="text-3xl font-extrabold text-white">3 adımda başla</h2>
    </div>
    <div class="grid sm:grid-cols-3 gap-6">
      <?php foreach ([
        ['user-plus', 'Profil Oluştur', 'Becerilerini, ilgi alanlarını ve bulunduğun şehri gir.'],
        ['cpu', 'AI Eşleştir', 'Yapay zeka profilini analiz eder, en uygun ilanları listeler.'],
        ['handshake', 'Fark Yarat', 'Başvur, kabul al ve sosyal etki yaratmaya başla.'],
      ] as $i => [$icon, $title, $desc]): ?>
      <div class="relative text-center">
        <?php if ($i < 2): ?>
        <div class="hidden sm:block absolute top-6 left-[calc(50%+28px)] right-[-50%] h-px bg-gradient-to-r from-violet-700/50 to-transparent"></div>
        <?php endif; ?>
        <div class="w-12 h-12 rounded-2xl bg-violet-600/20 border border-violet-700/40 flex items-center justify-center mx-auto mb-4 relative z-10">
          <i data-lucide="<?= $icon ?>" class="w-6 h-6 text-violet-400"></i>
        </div>
        <div class="text-violet-500 text-xs font-bold mb-1">0<?= $i+1 ?></div>
        <h3 class="font-bold text-white mb-2"><?= $title ?></h3>
        <p class="text-slate-400 text-sm leading-relaxed"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════ FEATURED LISTINGS -->
<?php if (!empty($featuredListings)): ?>
<section class="py-24 bg-surface-900 relative overflow-hidden">
  <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-violet-700/30 to-transparent"></div>
  <div class="max-w-4xl mx-auto px-5">
    <div class="flex items-end justify-between mb-10">
      <div>
        <p class="text-violet-400 text-sm font-semibold uppercase tracking-widest mb-2">Güncel İlanlar</p>
        <h2 class="text-2xl font-extrabold text-white">Öne Çıkan Fırsatlar</h2>
      </div>
      <a href="/register.php?role=volunteer" class="text-sm text-violet-400 hover:text-violet-300 flex items-center gap-1 transition-colors">
        Tümünü gör <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>
    <div class="grid sm:grid-cols-3 gap-4">
      <?php foreach ($featuredListings as $l): ?>
      <a href="/listing-view.php?id=<?= $l['id'] ?>" class="group block rounded-2xl border border-slate-800 bg-surface-800/50 p-5 hover:border-violet-700/50 hover:bg-surface-750/80 transition-all">
        <div class="w-10 h-10 rounded-xl bg-violet-900/30 border border-violet-800/30 flex items-center justify-center mb-4">
          <i data-lucide="briefcase" class="w-5 h-5 text-violet-400"></i>
        </div>
        <h3 class="font-bold text-white text-sm mb-1 group-hover:text-violet-300 transition-colors line-clamp-2"><?= h($l['title']) ?></h3>
        <p class="text-slate-400 text-xs mb-3"><?= h($l['org_name']) ?></p>
        <div class="flex flex-wrap gap-1.5">
          <?php if ($l['city']): ?><span class="flex items-center gap-1 text-[10px] text-slate-500 bg-surface-900 px-2 py-0.5 rounded-md"><i data-lucide="map-pin" class="w-2.5 h-2.5"></i><?= h($l['city']) ?></span><?php endif; ?>
          <?php if ($l['remote_ok']): ?><span class="flex items-center gap-1 text-[10px] text-cyan-500 bg-cyan-900/20 px-2 py-0.5 rounded-md"><i data-lucide="wifi" class="w-2.5 h-2.5"></i>Uzaktan</span><?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════ ORGS STRIP -->
<section class="py-12 bg-surface-800/50 border-y border-slate-800">
  <div class="max-w-4xl mx-auto px-5">
    <p class="text-center text-slate-600 text-xs uppercase tracking-widest font-semibold mb-6">Platformdaki Kurumlar</p>
    <div class="flex flex-wrap justify-center gap-x-8 gap-y-3">
      <?php foreach (['Açık Kapı Derneği', 'Tohum Otizm Vakfı', 'TEMA Vakfı', 'Hayata Destek', 'Kodluyoruz', 'Mor Çatı Vakfı'] as $org): ?>
      <span class="text-slate-500 text-sm font-medium hover:text-slate-300 transition-colors cursor-default"><?= h($org) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════ CTA BOTTOM -->
<section class="py-24 relative overflow-hidden">
  <div class="absolute inset-0 bg-surface-900"></div>
  <div class="absolute inset-0 bg-glow-violet"></div>
  <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[300px] bg-violet-700/10 rounded-full blur-3xl"></div>

  <!-- Visual -->
  <div class="absolute inset-0 opacity-10">
    <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=1400&q=80&auto=format&fit=crop" alt="" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-surface-900/80"></div>
  </div>

  <div class="relative max-w-2xl mx-auto px-5 text-center">
    <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">Hemen başla, <span class="gradient-text">fark yarat.</span></h2>
    <p class="text-slate-400 mb-10">Binlerce gönüllü ve yüzlerce STK seni bekliyor.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/register.php?role=volunteer" class="flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold shadow-glow transition-all hover:-translate-y-0.5">
        <i data-lucide="heart-handshake" class="w-5 h-5"></i> Gönüllü Ol
      </a>
      <a href="/register.php?role=org" class="flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl border border-slate-700 hover:border-violet-700 bg-surface-800/50 text-slate-200 font-bold transition-all hover:-translate-y-0.5">
        <i data-lucide="building-2" class="w-5 h-5"></i> Kurum Kaydı
      </a>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════ FOOTER -->
<footer class="border-t border-slate-800 py-8 bg-surface-950">
  <div class="max-w-6xl mx-auto px-5 flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-2">
      <div class="w-6 h-6 rounded-md bg-violet-600 flex items-center justify-center">
        <i data-lucide="zap" class="w-3 h-3 text-white"></i>
      </div>
      <span class="font-bold text-sm">impact<span class="text-violet-400">Nav</span></span>
    </div>
    <p class="text-slate-600 text-xs">© 2026 impactNav. Sosyal etkiyi birlikte büyütüyoruz.</p>
    <div class="flex gap-4">
      <a href="/login.php" class="text-slate-600 hover:text-slate-400 text-xs transition-colors">Giriş</a>
      <a href="/register.php" class="text-slate-600 hover:text-slate-400 text-xs transition-colors">Kayıt</a>
    </div>
  </div>
</footer>

<?php include __DIR__ . '/includes/chatbot.php'; ?>
<script>lucide.createIcons();</script>
</body>
</html>
