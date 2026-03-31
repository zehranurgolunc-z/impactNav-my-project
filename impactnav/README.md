# impactNav

Proje: impactNav — basit PHP/MySQL tabanlı bir uygulama (kayıt, giriş, listeleme, chat API gibi bileşenler).

## Problem (doğrudan paylaşıldığı şekilde)

1) GitHub'a push sırasında kimlik doğrulama hatası alındı:

```
remote: Permission to zehranurgolunc-z/impactNav-my-project.git denied to hisuleeo.
fatal: unable to access 'https://github.com/zehranurgolunc-z/impactNav-my-project.git/': The requested URL returned error: 403
```

veya SSH ile denendiğinde:

```
git@github.com: Permission denied (publickey).
fatal: Could not read from remote repository.

Please make sure you have the correct access rights
and the repository exists.
```

2) Vercel üzerine doğrudan deploy edilmiş olsa da proje PHP + MySQL tipinde dinamik bir uygulama olduğu için Vercel üzerinde tam fonksiyonel (LAMP) çalışması beklenmemelidir. Vercel genellikle statik/Jamstack ve serverless fonksiyonlar için uygundur.

## Çözüm (doğrudan paylaşıldığı şekilde)

Özetle alınan karar ve adımlar:

- SSH istemiyorsak (senin tercih): HTTPS + Personal Access Token (PAT) ile push yapılmalı. macOS kullanıyorsan `git config --global credential.helper osxkeychain` ile credential helper etkinleştirilebilir.
- Alternatif olarak SSH anahtarı oluşturup GitHub hesabına public key eklenirse push sorunsuz olur.
- Vercel tek başına PHP+MySQL (LAMP) uygulamasını çalıştırmak için uygun değildir. Gerçek dinamik işlevsellik gerekiyorsa backend (PHP) Render, Railway, DigitalOcean App Platform veya bir VPS üzerinde çalıştırılmalı; Vercel yalnızca statik frontend için kullanılabilir.
- Acil durum için hızlı bir geçici çözüm: Lokal siteyi statik snapshot (wget ile mirror) alıp Vercel'e deploy et. Bu yöntem login/chat gibi sunucu tarafı işlevleri çalıştırmaz ama hızlıca canlı bir görünüm sağlar.

## Nasıl çalıştırılır (lokal)

Yerel geliştirme ve hızlı test için iki seçenek bulunur:

1) PHP built-in server (en hızlı):

```zsh
cd /Users/sulesahin/Desktop/impactnav
php -S 127.0.0.1:8000 -t .
# tarayıcı: http://127.0.0.1:8000
```

2) Docker (tavsiye, DB ile birlikte):

```zsh
cd /Users/sulesahin/Desktop/impactnav
docker-compose up --build -d
# web: http://localhost:8080
```

MySQL bilgileri (docker-compose ile oluşturulmuşsa):

- host: `db`
- database: `impactnav`
- user: `impactuser`
- password: `impactpass`

## Deploy (kısa)

- GitHub push: HTTPS + PAT veya SSH ile repo'yu pushlayın.
- Tam dinamik deploy: Render/Railway kullanın. Start command örneği: `php -S 0.0.0.0:$PORT -t .`
- Hızlı statik deploy (geçici): localden `wget --mirror` ile snapshot alıp Vercel CLI ile `vercel --prod` çalıştırın.

## Canlı (mevcut)

Not: Lokalden Vercel CLI ile yapılan deploy sonucu proje kısmi olarak canlı hale getirildi. Dinamik özelliklerin çalışıp çalışmadığını kontrol edin: https://impactnav.vercel.app

## Dosyalar

Bu repo içinde aşağıdaki ek dosyalar bulunmaktadır:
- `README-deploy-local.md` — yerel ve docker çalıştırma talimatları
- `Dockerfile`, `docker-compose.yml` — geliştirme amaçlı docker konfigürasyonları

## Güvenlik

- `.env` ve gizli bilgileri asla GitHub'a pushlamayın.
- Kullandıktan sonra PAT'ı iptal edin ve gerekiyorsa yeni, sınırlı izinli token oluşturun.

---
Bu README, gönderilen problem ve çözüm metinlerinin aynısını içerir ve proje hakkında temel çalıştırma/deploy talimatlarını sunar.
