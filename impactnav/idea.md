# idea.md

Problem tanımı

impactNav projesi, kullanıcıların listeler oluşturduğu, kayıt/giriş, chat API gibi işlevleri olan küçük bir web uygulamasıdır. Mevcut problem: geliştirici yerel dosyaları GitHub'a pushlarken kimlik doğrulama hatası alıyor (403 veya SSH publickey hatası). Ayrıca proje PHP+MySQL tabanlı olduğundan Vercel'e doğrudan tam işlevsel deploy sorunlu.

Kullanıcı (target)

- Geliştirici: hızlıca projeyi GitHub'a push edip canlıya almak istiyor. Zaman kısıtlı.

AI'nın rolü

- Geliştiriciye adım adım push ve deploy süreçlerini otomatikleştirmek, güvenli alternatifler sunmak (PAT veya SSH), ve canlı yayına almak için Render/ Railway önerileri sunmak.
