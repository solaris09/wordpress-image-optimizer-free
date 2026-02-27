# Image Optimizer

![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Free](https://img.shields.io/badge/free-yes-brightgreen.svg)
![Formats](https://img.shields.io/badge/formats-PNG%20%7C%20JPEG%20%7C%20WebP%20%7C%20GIF%20%7C%20BMP%20%7C%20TIFF-orange.svg)

A powerful WordPress plugin for automatic image optimization. Supports **PNG, JPEG, WebP, GIF, BMP and TIFF** formats with lossless/lossy compression, bulk processing, progressive JPEG and WebP conversion. Works with both Imagick and GD libraries.

**Author:** CEMAL HEKIMOGLU

---

## 📋 Table of Contents

- [English](#english)
- [Türkçe](#türkçe)
- [Deutsch](#deutsch)
- [Español](#español)
- [日本語](#日本語)
- [中文](#中文)
- [العربية](#العربية)

---

## English

### Supported Formats

| Format | Compression | Special Features |
|--------|-------------|-----------------|
| **PNG** | Lossless (level 0–9) | Metadata stripping, alpha transparency preserved |
| **JPEG** | Lossy (quality 10–95) | Progressive JPEG, metadata stripping, sRGB colorspace |
| **WebP** | Lossy (quality 1–100) | Imagick + GD support |
| **GIF** | Lossless | Animation layer optimization |
| **BMP** | Lossless | Metadata stripping |
| **TIFF** | Lossy (quality 10–95) | JPEG compression inside TIFF |

### Features

✨ **Auto-Optimize on Upload** - All supported images are compressed immediately on upload
🗜️ **PNG Lossless Compression** - Level 0–9, zero visual quality loss
📷 **JPEG Quality Control** - Adjustable quality from 10 to 95
⚡ **Progressive JPEG** - Images load top-to-bottom progressively in browsers
🌐 **WebP Conversion** - Generate .webp files alongside originals
🎞️ **GIF Optimization** - Optimize animated GIF layers
💾 **Backup Originals** - Keep .optimizer-backup copy before processing
🚀 **Bulk Processing** - Optimize entire media library at once
📊 **Statistics Dashboard** - Track total savings and reduction percentage
🎯 **No External Dependencies** - Works with standard PHP extensions (GD/Imagick)
🔌 **Ayarlar Link** - Quick settings access from the Plugins list

### Installation

1. Download the plugin folder and place it in `wp-content/plugins/`
2. Activate from WordPress Plugins panel
3. Go to **Media → Image Optimizer** to configure

### Settings

| Setting | Description |
|---------|-------------|
| Auto-Optimize on Upload | Compress images automatically when uploaded |
| PNG Compression Level | 0 (fastest) to 9 (smallest file), lossless |
| JPEG Quality | 10–95, recommended 75–85 |
| Progressive JPEG | Enables progressive scan for faster perceived loading |
| Convert to WebP | Generate .webp alongside each image |
| WebP Quality | 1–100, recommended 80 |
| Backup Originals | Save .optimizer-backup before overwriting |

### Requirements

- WordPress 5.0+
- PHP 7.2+
- GD Library or Imagick (recommended)

### License

MIT License — Free to use, modify and distribute. See LICENSE file.

---

## Türkçe

### Desteklenen Formatlar

| Format | Sıkıştırma | Özel Özellikler |
|--------|-----------|----------------|
| **PNG** | Lossless (seviye 0–9) | Metadata çıkarma, alfa saydamlığı korunur |
| **JPEG** | Lossy (kalite 10–95) | Progressive JPEG, metadata çıkarma, sRGB renk uzayı |
| **WebP** | Lossy (kalite 1–100) | Imagick + GD desteği |
| **GIF** | Lossless | Animasyon katmanı optimizasyonu |
| **BMP** | Lossless | Metadata çıkarma |
| **TIFF** | Lossy (kalite 10–95) | TIFF içinde JPEG sıkıştırma |

### Özellikler

✨ **Yüklemede Otomatik Optimize** - Desteklenen tüm görseller yüklendiğinde anında sıkıştırılır
🗜️ **PNG Lossless Sıkıştırma** - 0–9 seviyesi, görsel kalite hiç kaybolmaz
📷 **JPEG Kalite Kontrolü** - 10'dan 95'e kadar ayarlanabilir kalite
⚡ **Progressive JPEG** - Görseller tarayıcıda üstten alta kademeli yüklenir
🌐 **WebP Dönüştürme** - Orijinalin yanında .webp dosyaları oluşturur
🎞️ **GIF Optimizasyonu** - Animasyonlu GIF katmanlarını optimize eder
💾 **Orijinal Yedekleme** - İşlem öncesi .optimizer-backup kopyası saklar
🚀 **Toplu İşleme** - Tüm medya kütüphanesini tek seferde optimize eder
📊 **İstatistik Paneli** - Toplam tasarruf ve küçülme yüzdesini takip eder
🎯 **Harici Bağımlılık Yok** - Standart PHP uzantılarıyla çalışır (GD/Imagick)
🔌 **Ayarlar Linki** - Eklentiler listesinden hızlı erişim

### Kurulum

1. Plugin klasörünü `wp-content/plugins/` dizinine yerleştirin
2. WordPress Eklentiler panelinden aktive edin
3. **Medya → Image Optimizer** sayfasına giderek yapılandırın

### Ayarlar

| Ayar | Açıklama |
|------|----------|
| Yüklemede Otomatik Optimize | Görsel yüklendiğinde otomatik sıkıştır |
| PNG Sıkıştırma Seviyesi | 0 (en hızlı) ile 9 (en küçük), lossless |
| JPEG Kalitesi | 10–95, önerilen 75–85 |
| Progressive JPEG | Daha hızlı algılanan yükleme için progressive tarama |
| WebP'ye Dönüştür | Her görselin yanına .webp oluşturur |
| WebP Kalitesi | 1–100, önerilen 80 |
| Orijinali Yedekle | Üzerine yazmadan önce .optimizer-backup kaydeder |

### Gereksinimler

- WordPress 5.0+
- PHP 7.2+
- GD Library veya Imagick (önerilir)

### Lisans

MIT Lisansı — Özgürce kullanın, değiştirin ve dağıtın. Ayrıntılar için LICENSE dosyasına bakın.

---

## Deutsch

### Unterstützte Formate

| Format | Komprimierung | Besondere Funktionen |
|--------|--------------|---------------------|
| **PNG** | Verlustfrei (Stufe 0–9) | Metadaten entfernen, Alpha-Transparenz erhalten |
| **JPEG** | Verlustbehaftet (Qualität 10–95) | Progressives JPEG, Metadaten entfernen, sRGB-Farbraum |
| **WebP** | Verlustbehaftet (Qualität 1–100) | Imagick + GD-Unterstützung |
| **GIF** | Verlustfrei | Animationsebenen-Optimierung |
| **BMP** | Verlustfrei | Metadaten entfernen |
| **TIFF** | Verlustbehaftet (Qualität 10–95) | JPEG-Komprimierung innerhalb von TIFF |

### Funktionen

✨ **Auto-Optimierung beim Upload** - Alle unterstützten Bilder werden beim Upload sofort komprimiert
🗜️ **PNG Verlustfreie Komprimierung** - Stufe 0–9, kein Qualitätsverlust
📷 **JPEG Qualitätskontrolle** - Einstellbare Qualität von 10 bis 95
⚡ **Progressives JPEG** - Bilder laden im Browser von oben nach unten schrittweise
🌐 **WebP-Konvertierung** - .webp-Dateien neben den Originalen generieren
🎞️ **GIF-Optimierung** - Animierte GIF-Ebenen optimieren
💾 **Originale sichern** - .optimizer-backup-Kopie vor der Verarbeitung behalten
🚀 **Batch-Verarbeitung** - Gesamte Medienbibliothek auf einmal optimieren
📊 **Statistik-Dashboard** - Gesamteinsparungen und Reduktionsprozentsatz verfolgen
🎯 **Keine externen Abhängigkeiten** - Funktioniert mit Standard-PHP-Erweiterungen (GD/Imagick)

### Installation

1. Plugin-Ordner in `wp-content/plugins/` ablegen
2. Im WordPress-Plugin-Panel aktivieren
3. Zu **Medien → Image Optimizer** gehen und konfigurieren

### Anforderungen

- WordPress 5.0+
- PHP 7.2+
- GD Library oder Imagick (empfohlen)

### Lizenz

MIT-Lizenz — Kostenlos zu verwenden, zu modifizieren und zu verteilen.

---

## Español

### Formatos Compatibles

| Formato | Compresión | Características Especiales |
|---------|-----------|---------------------------|
| **PNG** | Sin pérdida (nivel 0–9) | Eliminación de metadatos, transparencia alfa preservada |
| **JPEG** | Con pérdida (calidad 10–95) | JPEG progresivo, eliminación de metadatos, espacio de color sRGB |
| **WebP** | Con pérdida (calidad 1–100) | Soporte Imagick + GD |
| **GIF** | Sin pérdida | Optimización de capas de animación |
| **BMP** | Sin pérdida | Eliminación de metadatos |
| **TIFF** | Con pérdida (calidad 10–95) | Compresión JPEG dentro de TIFF |

### Características

✨ **Auto-Optimizar al Subir** - Todas las imágenes compatibles se comprimen al instante al subir
🗜️ **Compresión PNG Sin Pérdida** - Nivel 0–9, cero pérdida de calidad visual
📷 **Control de Calidad JPEG** - Calidad ajustable de 10 a 95
⚡ **JPEG Progresivo** - Las imágenes se cargan de arriba a abajo progresivamente en los navegadores
🌐 **Conversión a WebP** - Generar archivos .webp junto a los originales
🎞️ **Optimización GIF** - Optimizar capas de GIF animados
💾 **Copia de Seguridad** - Guardar copia .optimizer-backup antes de procesar
🚀 **Procesamiento por Lotes** - Optimizar toda la biblioteca de medios a la vez
📊 **Panel de Estadísticas** - Seguimiento de ahorros totales y porcentaje de reducción
🎯 **Sin Dependencias Externas** - Funciona con extensiones PHP estándar (GD/Imagick)

### Instalación

1. Colocar la carpeta del plugin en `wp-content/plugins/`
2. Activar desde el panel de Plugins de WordPress
3. Ir a **Medios → Image Optimizer** para configurar

### Requisitos

- WordPress 5.0+
- PHP 7.2+
- Biblioteca GD o Imagick (recomendado)

### Licencia

Licencia MIT — Libre de usar, modificar y distribuir.

---

## 日本語

### 対応フォーマット

| フォーマット | 圧縮方式 | 特別な機能 |
|------------|---------|-----------|
| **PNG** | 可逆（レベル0–9） | メタデータ削除、アルファ透明度保持 |
| **JPEG** | 非可逆（品質10–95） | プログレッシブJPEG、メタデータ削除、sRGB色空間 |
| **WebP** | 非可逆（品質1–100） | Imagick + GD対応 |
| **GIF** | 可逆 | アニメーションレイヤー最適化 |
| **BMP** | 可逆 | メタデータ削除 |
| **TIFF** | 非可逆（品質10–95） | TIFF内のJPEG圧縮 |

### 機能

✨ **アップロード時自動最適化** - 対応する全画像がアップロード時に即座に圧縮
🗜️ **PNG可逆圧縮** - レベル0–9、視覚的品質損失ゼロ
📷 **JPEG品質コントロール** - 10から95まで調整可能な品質
⚡ **プログレッシブJPEG** - ブラウザで画像が上から下へ段階的に読み込まれる
🌐 **WebP変換** - オリジナルの隣に.webpファイルを生成
🎞️ **GIF最適化** - アニメーションGIFレイヤーを最適化
💾 **オリジナルバックアップ** - 処理前に.optimizer-backupコピーを保持
🚀 **一括処理** - メディアライブラリ全体を一度に最適化
📊 **統計ダッシュボード** - 合計節約量と削減率を追跡
🎯 **外部依存関係なし** - 標準PHP拡張機能で動作（GD/Imagick）

### インストール

1. プラグインフォルダを `wp-content/plugins/` に配置
2. WordPressプラグインパネルから有効化
3. **メディア → Image Optimizer** に移動して設定

### 要件

- WordPress 5.0以上
- PHP 7.2以上
- GDライブラリまたはImagick（推奨）

### ライセンス

MITライセンス — 自由に使用、修正、配布可能。

---

## 中文

### 支持的格式

| 格式 | 压缩方式 | 特殊功能 |
|------|---------|---------|
| **PNG** | 无损（级别0–9） | 去除元数据，保留Alpha透明度 |
| **JPEG** | 有损（质量10–95） | 渐进式JPEG，去除元数据，sRGB色彩空间 |
| **WebP** | 有损（质量1–100） | Imagick + GD支持 |
| **GIF** | 无损 | 动画图层优化 |
| **BMP** | 无损 | 去除元数据 |
| **TIFF** | 有损（质量10–95） | TIFF内的JPEG压缩 |

### 功能特性

✨ **上传时自动优化** - 所有支持的图像在上传时立即压缩
🗜️ **PNG无损压缩** - 级别0–9，零视觉质量损失
📷 **JPEG质量控制** - 从10到95可调质量
⚡ **渐进式JPEG** - 图像在浏览器中从上到下逐渐加载
🌐 **WebP转换** - 在原图旁边生成.webp文件
🎞️ **GIF优化** - 优化动画GIF图层
💾 **备份原件** - 处理前保留.optimizer-backup副本
🚀 **批量处理** - 一次优化整个媒体库
📊 **统计仪表板** - 跟踪总节省量和减少百分比
🎯 **无外部依赖** - 使用标准PHP扩展工作（GD/Imagick）

### 安装

1. 将插件文件夹放置在 `wp-content/plugins/` 中
2. 从WordPress插件面板激活
3. 转到 **媒体 → Image Optimizer** 进行配置

### 要求

- WordPress 5.0+
- PHP 7.2+
- GD库或Imagick（推荐）

### 许可证

MIT许可证 — 自由使用、修改和分发。

---

## العربية

### الصيغ المدعومة

| الصيغة | الضغط | ميزات خاصة |
|--------|------|------------|
| **PNG** | بدون فقدان (مستوى 0–9) | إزالة البيانات الوصفية، الحفاظ على شفافية ألفا |
| **JPEG** | مع فقدان (جودة 10–95) | JPEG تدريجي، إزالة البيانات الوصفية، فضاء لون sRGB |
| **WebP** | مع فقدان (جودة 1–100) | دعم Imagick + GD |
| **GIF** | بدون فقدان | تحسين طبقات الرسوم المتحركة |
| **BMP** | بدون فقدان | إزالة البيانات الوصفية |
| **TIFF** | مع فقدان (جودة 10–95) | ضغط JPEG داخل TIFF |

### المميزات

✨ **التحسين التلقائي عند التحميل** - يتم ضغط جميع الصور المدعومة فوراً عند التحميل
🗜️ **ضغط PNG بدون فقدان** - المستوى 0–9، صفر خسارة في الجودة البصرية
📷 **التحكم في جودة JPEG** - جودة قابلة للضبط من 10 إلى 95
⚡ **JPEG التدريجي** - تحميل الصور تدريجياً من الأعلى إلى الأسفل في المتصفحات
🌐 **التحويل إلى WebP** - إنشاء ملفات .webp بجانب الأصليات
🎞️ **تحسين GIF** - تحسين طبقات GIF المتحركة
💾 **نسخ احتياطية للأصليات** - الاحتفاظ بنسخة .optimizer-backup قبل المعالجة
🚀 **المعالجة الجماعية** - تحسين مكتبة الوسائط بالكامل دفعة واحدة
📊 **لوحة الإحصائيات** - تتبع إجمالي المدخرات ونسبة التخفيض
🎯 **بدون تبعيات خارجية** - يعمل مع ملحقات PHP القياسية (GD/Imagick)

### التثبيت

1. ضع مجلد البرنامج الإضافي في `wp-content/plugins/`
2. فعّله من لوحة البرامج الإضافية في WordPress
3. انتقل إلى **الوسائط → Image Optimizer** للتكوين

### المتطلبات

- WordPress 5.0+
- PHP 7.2+
- مكتبة GD أو Imagick (موصى به)

### الترخيص

ترخيص MIT — مجاني للاستخدام والتعديل والتوزيع. راجع ملف LICENSE للتفاصيل.

---

## 🤝 Contributing

Contributions are welcome! Feel free to fork, modify, and submit pull requests.

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

**Free Software · Open Source · MIT License**

---

**Made with ❤️ by CEMAL HEKIMOGLU**
