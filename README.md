<p align="center">
  <h1 align="center">✨ NovaCMS</h1>
  <p align="center">
    <strong>Enterprise-Ready, High-Performance, Modular PHP 8 Content Management System & Headless REST API</strong>
  </p>
  <p align="center">
    <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8+">
    <img src="https://img.shields.io/badge/Database-SQLite3-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="SQLite">
    <img src="https://img.shields.io/badge/Speed-0.005s%20Cache-brightgreen?style=for-the-badge&logo=speedtest" alt="0.005s Cache">
    <img src="https://img.shields.io/badge/Architecture-Plugin%20%26%20Hook-blueviolet?style=for-the-badge" alt="Plugin Architecture">
    <img src="https://img.shields.io/badge/AI-Assisted%20SEO-F59E0B?style=for-the-badge&logo=openai" alt="AI Assisted">
    <img src="https://img.shields.io/badge/API-RESTful%20JSON-FF5722?style=for-the-badge&logo=postman&logoColor=white" alt="REST API">
    <img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">
    <img src="https://img.shields.io/badge/CI%2FCD-GitHub%20Actions-2088FF?style=for-the-badge&logo=github-actions&logoColor=white" alt="CI/CD">
    <img src="https://img.shields.io/badge/License-MIT%20%7C%20GPL--3.0-blue?style=for-the-badge" alt="Dual License">
  </p>
</p>

---

## 📖 Hakkında (About)

**NovaCMS**, harici hiçbir veritabanı sunucusu veya paket yöneticisi bağımlılığı gerektirmeyen, **WordPress benzeri Kanca (Hook & Filter) ve ZIP ile Eklenti Yükleme Mimarisine** sahip, **PHP 8** ve dahili **PDO SQLite** gücüyle çalışan modern, minimal ve ultra hızlı bir İçerik Yönetim Sistemidir.

Geliştiriciler ve kullanıcılar, çekirdek koda dokunmadan diledikleri modülü `plugins/` klasörüne ekleyebilir veya yönetim panelinden `.zip` paketi olarak yükleyebilir.

---

## 🌟 Öne Çıkan Özellikler (Key Features)

- ⚡ **Ultra Hızlı Dahili Önbellek Motoru (0.005s Cache Engine):**
  - Dosya tabanlı (`/cache`) önbellekleme ile veritabanı sorgularını ve API yanıtlarını anında sunar.
- 📦 **ZIP ile Eklenti Yükleme & Modül Mimarisi (`/plugins`):**
  - İndirdiğiniz `.zip` paketlerini admin panelinden yükleyip otomatik kurma.
  - `Hook::addAction` ve `Hook::addFilter` kancalarıyla çekirdek koda dokunmadan sistemi genişletebilme.
- 🤖 **Yapay Zeka (AI) Destekli SEO & Etiket Asistanı:**
  - Yazı yazarken tek tıkla otomatik SEO meta açıklaması, anahtar kelimeler ve etiket önerileri üretme.
- 📊 **Dahili Gizlilik Dostu Analitik Paneli (`/admin/analytics.php`):**
  - Çerez gerektirmeyen, günlük tekil ziyaretçi, sayfa görüntüleme, tarayıcı dağılımı ve Chart.js grafikleri.
- 🗂️ **Medya Kütüphanesi & Dosya Yöneticisi (`/admin/media.php`):**
  - Yüklenen tüm görselleri grid galeri olarak görme, kopyalama ve yönetme.
- 🛡️ **Gelişmiş Güvenlik & Brute-Force Koruması:**
  - IP bazlı oturum açma sınırlaması (15 dakikada 5 başarısız denemede otomatik kilit).
- 📬 **E-Bülten / Newsletter Abonelik Sistemi:**
  - Ziyaretçiler için AJAX abonelik formu ve panelden **CSV dışa aktarma**.
- 💻 **Prism.js Kod Renklendirme:**
  - PHP, Python, JS, HTML, Bash ve JSON kod blokları için Tomorrow Night sözdizimi vurgulama.
- 🔗 **Sosyal Medya Paylaşım Butonları:**
  - X (Twitter), LinkedIn, WhatsApp, Telegram ve tek tıkla pano kopyalama.
- 🐳 **Docker & Docker Compose Desteği:**
  - `docker compose up -d` ile tek komutla container olarak çalıştırma.
- 🤖 **GitHub Actions CI/CD Pipeline:**
  - `.github/workflows/ci.yml` ile otomatik syntax kontrolü ve API testleri.
- 👥 **Rol Tabanlı Yetkilendirme (RBAC):** Admin, Editor, Author.
- 🚀 **Yerleşik RESTful JSON API (`/api/posts.php`).**
- ✍️ **Quill.js WYSIWYG Zengin Metin Editörü.**
- 🔍 **Ajax Canlı Arama (Instant Live Search).**
- 🏷️ **Etiket Sistemi (Tag Cloud) & İlgili Yazılar.**
- 📄 **Sayfalama (Pagination).**
- 🎨 **Modern Tasarım & Karanlık Mod (Dark Mode).**
- 💬 **İnteraktif Ziyaretçi Yorum & Beğeni (Like) Sistemi.**
- 📡 **Dinamik RSS 2.0 Feed ve XML Sitemap.**
- 💾 **SQLite DB ve JSON Yedekleme / Dışa Aktarma.**

---

## 🚀 Hızlı Başlangıç (Quick Start)

### Yöntem 1: Dahili PHP Sunucusu ile (Önerilen)

```bash
git clone https://github.com/aliede34/NOVA-CMS.git
cd novacms
php -S localhost:8000
```

- **Web Sitesi:** [http://localhost:8000](http://localhost:8000)
- **Yönetim Paneli:** [http://localhost:8000/admin/login.php](http://localhost:8000/admin/login.php)
- **Giriş Bilgileri:** `admin` / `admin123`

---

### Yöntem 2: Docker ile Çalıştırma

```bash
docker compose up -d
```
Container başladığında:
- Web Sitesi: [http://localhost:8080](http://localhost:8080)

---

## 🧩 Eklenti Geliştirme (Plugin API)

`plugins/benim-eklentim/plugin.php`:
```php
<?php
/**
 * Plugin Name: Canlı Destek Butonu
 * Description: Sitenin altına buton ekler.
 * Version: 1.0.0
 * Author: Adınız
 */

Hook::addAction('before_body_end', function() {
    echo '<a href="#" class="fixed bottom-6 right-6 p-4 bg-indigo-600 text-white rounded-full shadow-lg">Destek</a>';
});
```

---

## 📄 Lisans & Telif Hakkı (License)

Copyright (c) 2026 **Ali**. Tüm hakları saklıdır.

Bu proje **Çift Lisanslama (Dual-Licensing: MIT OR GPL-3.0-or-later)** modeli altında korunmaktadır:
1. **MIT Lisansı**: İsteyen herkes özgürce projelerinde kullanabilir, değiştirebilir ve geliştirebilir.
2. **GPL v3 Lisansı**: Kodların tamamen açık kaynak kalmasını ve türetilen projelerin de açık kalmasını güvence altına alır.

Detaylar için [LICENSE](LICENSE) dosyasına göz atabilirsiniz.
