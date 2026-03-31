#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(pwd)"
REPO_OWNER="zehranurgolunc-z"
REPO_NAME="impactNav-my-project"
REMOTE_HTTPS="https://github.com/${REPO_OWNER}/${REPO_NAME}.git"

echo
echo "== impactNav otomatik push + Vercel deploy betiği =="
echo "Çalışma dizini: ${ROOT_DIR}"
echo

# 1) Remote'u HTTPS yap
echo "1) Remote'u HTTPS'ye çeviriyorum..."
git remote set-url origin "${REMOTE_HTTPS}" || true
git branch -M main || true

# 2) credential helper (macOS)
echo "2) macOS credential helper'ı aktif ediyorum (osxkeychain)..."
git config --global credential.helper osxkeychain || true

# 3) GitHub kimlik bilgisi al
read -r -p "GitHub kullanıcı adınız (ör: zehranurgolunc-z): " GITHUB_USER
echo
read -r -s -p "GitHub Personal Access Token (PAT) (gizli, yapıştır): " GITHUB_PAT
echo
if [ -z "$GITHUB_PAT" ]; then
  echo "Hata: PAT boş. İşlem iptal edildi."
  exit 1
fi

# 4) PAT'ı tek seferlik Keychain'e kaydet (store)
echo "3) PAT'ı Keychain'e tek seferlik kaydediyorum..."
printf "protocol=https\nhost=github.com\nusername=%s\npassword=%s\n" "$GITHUB_USER" "$GITHUB_PAT" | git credential-osxkeychain store

# 5) Push dene
echo "4) Git push yapılıyor (origin main)..."
if git push -u origin main; then
  echo
  echo "✅ Git push başarılı."
else
  echo
  echo "❌ Git push başarısız. Aşağıdakileri kontrol et:"
  echo " - Kullanıcı adı ve PAT doğru mu?"
  echo " - Token için 'public_repo' veya 'repo' scope verildi mi?"
  echo " - Repo sahibi (owner) ile aynı GitHub hesabı mısınız? (izinler)"
  # Temizle
  printf "protocol=https\nhost=github.com\n" | git credential-osxkeychain erase || true
  exit 1
fi

# 6) Credential temizle
echo "5) Keychain'deki geçici credential temizleniyor..."
printf "protocol=https\nhost=github.com\n" | git credential-osxkeychain erase || true

# 7) Vercel deploy (yerelden prod; interaktif auth gerekirse tarayıcı açılabilir)
echo
echo "6) Vercel CLI ile prod deploy tetikleniyor (yerelden)."
if command -v vercel >/dev/null 2>&1; then
  # non-interactive prod deploy; --confirm kullanarak otomatik diyalogları atla
  vercel --prod --confirm || {
    echo "Vercel deploy başarısız veya interaktif izin gerektirdi. Lütfen 'vercel login' ile oturum açın ve yeniden deneyin."
    exit 1
  }
  echo "✅ Vercel deploy komutu çalıştırıldı. Vercel panelinde deploy loglarını kontrol edin."
else
  echo "Vercel CLI bulunamadı. Eğer istersen 'npm i -g vercel' ile yükleyip tekrar çalıştırabilirsin."
  echo "Not: Sen zaten Vercel ile oturum açmışsan basit 'vercel --prod --confirm' komutunu çalıştır."
fi

echo
echo "Tamamlandı. Aşağıdaki kontrolleri yapınız:"
echo " - GitHub repo: https://github.com/${REPO_OWNER}/${REPO_NAME}"
echo " - Vercel dashboard: https://vercel.com (Projects -> impactnav)"
echo
echo "Eğer repo private ise GitHub'dan public yapın: Settings -> Change visibility -> Public"
echo
echo "Güvenlik önerisi: Bu işlemi bitirdikten sonra GitHub'da kullandığınız PAT'ı iptal edebilirsiniz (Settings -> Developer settings -> Personal access tokens)."
