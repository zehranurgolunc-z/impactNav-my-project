# user-flow.md

Kullanıcı akışı (basitleştirilmiş)

1. Geliştirici yerel değişiklikleri tamamlar.
2. Yerelde test: `php -S 127.0.0.1:8000 -t .` veya Docker ile `docker-compose up`.
3. GitHub'a push:
   - Tercih A: HTTPS + PAT (public repo için `public_repo` scope)
   - Tercih B: SSH anahtarı ekleyip push
4. CI/CD (Vercel/Render):
   - Eğer sadece statik içerik: Vercel ile otomatik deploy
   - Eğer tam PHP backend: Render/Railway ile deploy (db bağlantısı eklenir)
5. Test canlı ortamda: giriş, listeleme, chat API
