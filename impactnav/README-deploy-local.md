Hızlı yerel çalışma talimatları

1) PHP built-in server (en hızlı, DB bağlı olmayan sayfalar için)

Terminalde proje kökünde çalıştır:

```zsh
cd /Users/sulesahin/Desktop/impactnav
php -S 127.0.0.1:8000 -t .
```

Ardından tarayıcıda: http://127.0.0.1:8000

Not: Eğer uygulaman DB gerektiriyorsa bazı sayfalar hata verebilir.

2) Docker ile PHP + MySQL (önerilir)

Gerekenler: Docker ve docker-compose kurulu olmalı.

```zsh
cd /Users/sulesahin/Desktop/impactnav
# İlk kez: build
docker-compose up --build -d
# Logları görmek için
docker-compose logs -f web
```

Web uygulaması: http://localhost:8080
MySQL bilerek: host=db, user=impactuser, password=impactpass, database=impactnav

3) GitHub push hatası (403 veya izin hatası)

- SSH anahtarı ekleyip remote'u SSH'ya çevir; veya
- GitHub Personal Access Token (PAT) ile HTTPS push yap.

SSH adımları (kısa):

```zsh
ssh-keygen -t ed25519 -C "your-email@example.com"
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
cat ~/.ssh/id_ed25519.pub  # bunu GitHub Settings -> SSH and GPG keys'e yapıştır

# sonra remote'u SSH'ye çevir
git remote set-url origin git@github.com:zehranurgolunc-z/impactNav-my-project.git

git push -u origin main
```

4) Vercel konusunda kritik not:

Bu repository bir PHP uygulaması. Vercel PHP'yi native olarak LAMP şeklinde desteklemez. Hızlı canlı yayın için önerim:

- Backend (PHP + MySQL) için Render / Railway / DigitalOcean kullan; repo GitHub'a push edildiğinde Render ile otomatik deploy yapabilirsin.
- Sadece statik frontend varsa onu Vercel'e koy. Tam PHP uygulamasını Vercel'e almak istersen Docker + ücretli/kurumsal ayarlar gerekebilir.

Eğer istersen ben GitHub push sonrasında Render deploy adımlarını da senin için yapmaya hazırım (veya adım adım rehber veririm).
