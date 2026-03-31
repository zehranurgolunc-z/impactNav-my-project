<?php
$chatUser = current_user();
$chatName = $chatUser['full_name'] ?? 'Ziyaretçi';
?>
<!-- Role badge -->
<div class="fixed bottom-5 left-5 z-40 flex items-center gap-2 px-3 py-1.5 rounded-full glass border border-slate-700/60 text-xs text-slate-400 shadow-card">
  <i data-lucide="<?= ($chatUser['role'] ?? '') === 'org' ? 'building-2' : 'heart-handshake' ?>" class="w-3.5 h-3.5 text-violet-400"></i>
  <span class="font-medium"><?= ($chatUser['role'] ?? '') === 'org' ? 'Kurum' : 'Gönüllü' ?></span>
</div>

<!-- Chatbot trigger button -->
<button id="chatTrigger" class="fixed bottom-5 right-5 z-50 w-14 h-14 rounded-full bg-violet-600 hover:bg-violet-500 shadow-glow flex items-center justify-center transition-all hover:scale-110 active:scale-95">
  <i data-lucide="message-circle" id="chatBtnIcon" class="w-6 h-6 text-white"></i>
</button>

<!-- Chat panel -->
<div id="chatPanel" class="fixed bottom-24 right-5 z-40 w-[340px] h-[500px] rounded-2xl border border-slate-700/60 bg-surface-800 shadow-glow flex flex-col overflow-hidden transition-all duration-200 origin-bottom-right"
     style="opacity:0;transform:scale(0.9) translateY(10px);pointer-events:none;">

  <!-- Header -->
  <div class="flex items-center gap-3 px-4 py-3.5 bg-gradient-to-r from-violet-900/60 to-surface-800 border-b border-slate-700/50 flex-shrink-0">
    <div class="w-8 h-8 rounded-xl bg-violet-600 flex items-center justify-center flex-shrink-0">
      <i data-lucide="bot" class="w-4 h-4 text-white"></i>
    </div>
    <div class="flex-1 min-w-0">
      <p class="font-bold text-white text-sm">impactAI</p>
      <p class="text-xs text-emerald-400 flex items-center gap-1">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block animate-pulse-slow"></span> Çevrimiçi
      </p>
    </div>
    <button onclick="closeChat()" class="text-slate-500 hover:text-slate-300 transition-colors p-1">
      <i data-lucide="x" class="w-4 h-4"></i>
    </button>
  </div>

  <!-- Messages -->
  <div id="chatMessages" class="flex-1 overflow-y-auto px-4 py-3 space-y-2.5">
    <div class="flex gap-2 max-w-[88%]">
      <div class="w-6 h-6 rounded-lg bg-violet-900/50 border border-violet-800/30 flex items-center justify-center flex-shrink-0 mt-0.5">
        <i data-lucide="bot" class="w-3 h-3 text-violet-400"></i>
      </div>
      <div class="bg-surface-700 border border-slate-700/50 text-slate-200 text-xs rounded-2xl rounded-tl-sm px-3 py-2.5 leading-relaxed">
        Merhaba <?= h(explode(' ', $chatName)[0]) ?>! 👋 Ben impactAI. En uygun gönüllülük fırsatlarını bulmana yardımcı olabilirim. Nasıl yardımcı olabilirim?
      </div>
    </div>
  </div>

  <!-- Input -->
  <div class="px-3 py-3 border-t border-slate-700/50 flex gap-2 flex-shrink-0">
    <input type="text" id="chatInput"
      class="flex-1 bg-surface-700 border border-slate-700 focus:border-violet-600 text-slate-100 placeholder-slate-600 rounded-xl px-3 py-2 text-xs outline-none transition-all"
      placeholder="Mesajınızı yazın..." maxlength="500">
    <button id="chatSend" class="w-8 h-8 rounded-xl bg-violet-600 hover:bg-violet-500 flex items-center justify-center flex-shrink-0 transition-all">
      <i data-lucide="send" class="w-3.5 h-3.5 text-white"></i>
    </button>
  </div>
</div>

<script>
(function() {
  const trigger  = document.getElementById('chatTrigger');
  const panel    = document.getElementById('chatPanel');
  const input    = document.getElementById('chatInput');
  const send     = document.getElementById('chatSend');
  const msgs     = document.getElementById('chatMessages');
  const btnIcon  = document.getElementById('chatBtnIcon');
  let isOpen = false;

  function openChat()  {
    isOpen = true;
    panel.style.opacity = '1';
    panel.style.transform = 'scale(1) translateY(0)';
    panel.style.pointerEvents = 'auto';
    btnIcon.setAttribute('data-lucide', 'x');
    lucide.createIcons();
    input.focus();
    scrollBottom();
  }

  function closeChat() {
    isOpen = false;
    panel.style.opacity = '0';
    panel.style.transform = 'scale(0.9) translateY(10px)';
    panel.style.pointerEvents = 'none';
    btnIcon.setAttribute('data-lucide', 'message-circle');
    lucide.createIcons();
  }

  window.closeChat = closeChat;

  trigger.addEventListener('click', () => { isOpen ? closeChat() : openChat(); });

  function scrollBottom() { setTimeout(() => { msgs.scrollTop = msgs.scrollHeight; }, 50); }

  function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
  }

  function addMsg(text, who) {
    const wrap = document.createElement('div');
    wrap.className = who === 'user'
      ? 'flex justify-end'
      : 'flex gap-2 max-w-[88%]';

    if (who === 'bot') {
      wrap.innerHTML = `
        <div class="w-6 h-6 rounded-lg bg-violet-900/50 border border-violet-800/30 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="bot" class="w-3 h-3 text-violet-400"></i>
        </div>
        <div class="bg-surface-700 border border-slate-700/50 text-slate-200 text-xs rounded-2xl rounded-tl-sm px-3 py-2.5 leading-relaxed max-w-[85%]">${escHtml(text)}</div>`;
    } else {
      wrap.innerHTML = `<div class="bg-violet-600 text-white text-xs rounded-2xl rounded-tr-sm px-3 py-2.5 leading-relaxed max-w-[85%]">${escHtml(text)}</div>`;
    }

    msgs.appendChild(wrap);
    lucide.createIcons();
    scrollBottom();
  }

  function addTyping() {
    const wrap = document.createElement('div');
    wrap.id = 'typing-wrap';
    wrap.className = 'flex gap-2 max-w-[88%]';
    wrap.innerHTML = `
      <div class="w-6 h-6 rounded-lg bg-violet-900/50 border border-violet-800/30 flex items-center justify-center flex-shrink-0 mt-0.5">
        <i data-lucide="bot" class="w-3 h-3 text-violet-400"></i>
      </div>
      <div class="bg-surface-700 border border-slate-700/50 rounded-2xl rounded-tl-sm px-3 py-2.5 flex gap-1 items-center chat-typing">
        <span></span><span></span><span></span>
      </div>`;
    msgs.appendChild(wrap);
    lucide.createIcons();
    scrollBottom();
  }

  function addListings(listings) {
    const wrap = document.createElement('div');
    wrap.className = 'space-y-2';
    let html = `<p class="text-[10px] text-slate-500 mb-1.5 flex items-center gap-1"><i data-lucide="sparkles" class="w-3 h-3 text-violet-400"></i> Sana özel ilanlar</p>`;
    listings.forEach(l => {
      html += `
        <a href="/listing-view.php?id=${l.id}" class="block bg-surface-700/60 border border-slate-700/60 hover:border-violet-700/40 rounded-xl p-2.5 transition-colors">
          <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold text-white line-clamp-1">${escHtml(l.title)}</p>
            <span class="text-[10px] text-violet-300 font-bold flex-shrink-0">%${Math.round((l._score||0)*100)}</span>
          </div>
          <p class="text-[10px] text-slate-500 mt-0.5">${escHtml(l.org_name)}</p>
        </a>`;
    });
    wrap.innerHTML = html;
    msgs.appendChild(wrap);
    lucide.createIcons();
    scrollBottom();
  }

  async function sendMessage() {
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    addMsg(text, 'user');
    addTyping();
    send.disabled = true;

    try {
      const res  = await fetch('/api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ message: text })
      });
      const data = await res.json();
      document.getElementById('typing-wrap')?.remove();

      if (data.reply) addMsg(data.reply, 'bot');
      if (data.listings?.length) addListings(data.listings);
      if (data.error && !data.reply) addMsg('Bir hata oluştu, lütfen tekrar deneyin.', 'bot');
    } catch(e) {
      document.getElementById('typing-wrap')?.remove();
      addMsg('Bağlantı hatası oluştu.', 'bot');
    } finally {
      send.disabled = false;
      input.focus();
    }
  }

  send.addEventListener('click', sendMessage);
  input.addEventListener('keypress', e => { if (e.key === 'Enter') sendMessage(); });
})();
</script>
