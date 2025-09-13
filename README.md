# iyzico Installment for WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-6.6+-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-9.3.3+-green.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4.33+-red.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

iyzico Installment eklentisi, WooCommerce ürün sayfalarında iyzico'nun taksit hesaplamasını kullanarak müşterilerinize taksit seçeneklerini gösterir. Bu eklenti sadece taksit bilgilerini görüntüler, ödeme işlemi yapmaz.

## 🚀 Özellikler

- **Ürün Sayfası Entegrasyonu**: WooCommerce ürün sayfalarında otomatik taksit gösterimi
- **Dinamik Taksit Hesaplama**: Varyasyonlu ürünlerde fiyat değişiminde anlık taksit güncelleme
- **iyzico API Entegrasyonu**: Gerçek zamanlı taksit hesaplama
- **Çoklu Entegrasyon Seçenekleri**: Shortcode, ürün sekmesi veya widget olarak kullanım
- **AJAX Desteği**: Dinamik taksit hesaplama ve güncelleme
- **Responsive Tasarım**: Mobil ve masaüstü uyumlu
- **Banka Logoları**: Kredi kartı familyalarına göre otomatik logo gösterimi
- **KDV Hesaplama**: Ürün fiyatlarına KDV dahil etme seçeneği
- **HPOS Uyumluluğu**: WooCommerce High-Performance Order Storage desteği
- **Çoklu Dil Desteği**: i18n entegrasyonu
- **Gelişmiş Loglama**: Detaylı hata takibi ve debug bilgileri

## 📋 Gereksinimler

- **WordPress**: 6.6.2 veya üzeri
- **WooCommerce**: 9.3.3 veya üzeri
- **PHP**: 7.4.33 veya üzeri
- **cURL Extension**: PHP cURL desteği
- **iyzico WooCommerce**: Ana ödeme eklentisi
- **iyzico Hesabı**: Taksit hesaplama için API erişimi

## 🛠️ Kurulum

### WordPress.org'dan Kurulum (Önerilen)

1. WordPress yönetici panelinde **Eklentiler > Yeni Ekle** sayfasına gidin
2. Arama kutusuna "iyzico Installment" yazın
3. Eklentiyi bulun ve **Kur** butonuna tıklayın
4. Kurulum tamamlandıktan sonra **Etkinleştir** butonuna tıklayın

### Manuel Kurulum

1. Eklenti ZIP dosyasını indirin
2. WordPress yönetici panelinde **Eklentiler > Yeni Ekle** sayfasına gidin
3. **Eklenti Yükle** butonuna tıklayın
4. İndirdiğiniz ZIP dosyasını seçin ve **Şimdi Yükle** butonuna tıklayın
5. Kurulum tamamlandıktan sonra **Eklentiyi Etkinleştir** butonuna tıklayın

## ⚙️ Yapılandırma

### 1. API Kimlik Bilgileri

Eklentiyi kullanabilmek için iyzico hesap bilgilerinizi girmeniz gerekir:

1. **iyzico Installment** sayfasına gidin
2. **API Key** ve **Secret Key** alanlarını doldurun
3. **Test Modu** veya **Canlı Mod** seçin
4. **Kaydet** butonuna tıklayın

### 2. Entegrasyon Türü

Eklenti üç farklı entegrasyon türü sunar:

- **Shortcode**: `[iyzico_installment]` kullanarak istediğiniz yerde gösterebilirsiniz
- **Ürün Sekmesi**: Ürün sayfalarında otomatik olarak taksit sekmesi ekler
- **Widget**: Sidebar veya footer'da taksit bilgilerini gösterir

### 3. Görünüm Ayarları

- **Taksit Sekmesi Gösterimi**: Ürün sayfalarında taksit sekmesi ekleme
- **Responsive Tasarım**: Mobil uyumlu görünüm

## 🔧 Kullanım

### Shortcode Kullanımı

Herhangi bir sayfa veya yazıda taksit bilgilerini göstermek için:

```php
[iyzico_installment]
```

### Dinamik Taksit Shortcode

Varyasyonlu ürünlerde anlık taksit güncelleme için:

```php
[dynamic_iyzico_installment]
```

### PHP Kod ile Kullanım

```php
// Taksit bilgilerini programatik olarak almak için
$installment_info = $GLOBALS['iyzico_api']->get_installment_info($product_price);

// Shortcode'u render etmek için
echo do_shortcode('[iyzico_installment]');
```

### Tema Entegrasyonu

`functions.php` dosyanıza ekleyerek otomatik entegrasyon:

```php
// Ürün sayfalarında otomatik taksit gösterimi
add_action('woocommerce_single_product_summary', function() {
    echo do_shortcode('[iyzico_installment]');
}, 25);
```

## 🏗️ Teknik Mimari

Eklenti modüler bir yapıya sahiptir:

```
iyzico-installment/
├── iyzico-installment.php          # Ana eklenti dosyası
├── includes/                        # Sınıf dosyaları
│   ├── class-iyzico-installment-settings.php    # Ayarlar yönetimi
│   ├── class-iyzico-installment-api.php         # API entegrasyonu
│   ├── class-iyzico-installment-frontend.php    # Frontend işlemleri
│   ├── class-iyzico-installment-dynamic.php     # Dinamik taksit sistemi
│   ├── class-iyzico-installment-logger.php      # Loglama sistemi
│   ├── class-iyzico-installment-hpos.php        # HPOS uyumluluğu
│   └── admin/                      # Yönetici paneli
├── assets/                         # CSS, JS ve görseller
│   ├── css/                        # Stil dosyaları
│   ├── js/                         # JavaScript dosyaları
│   └── images/                     # Banka logoları
├── i18n/                           # Dil dosyaları
└── logs/                           # Log dosyaları
```

### Sınıf Yapısı

- **Settings**: Eklenti ayarlarını yönetir
- **API**: iyzico API entegrasyonunu sağlar
- **Frontend**: Kullanıcı arayüzü ve shortcode işlemleri
- **Dynamic**: Varyasyonlu ürünlerde dinamik taksit hesaplama
- **Logger**: Hata takibi ve debug bilgileri
- **HPOS**: WooCommerce High-Performance Order Storage uyumluluğu
- **Admin**: Yönetici paneli ayarları

## 🔌 API Entegrasyonu

Eklenti iyzico'nun resmi PHP SDK'sını kullanır:

```php
use Iyzipay\Model\InstallmentInfo;
use Iyzipay\Options;
use Iyzipay\Request\RetrieveInstallmentInfoRequest;

// Taksit bilgilerini al
$request = new RetrieveInstallmentInfoRequest();
$request->setLocale('tr');
$request->setConversationId(uniqid('iyzico_installment_'));
$request->setPrice($product_price);
$request->setBinNumber($bin_number);

$response = InstallmentInfo::retrieve($request, $options);
```

## 🎨 Özelleştirme

### CSS Özelleştirme

`assets/css/iyzico-installment.css` dosyasını düzenleyerek görünümü özelleştirebilirsiniz:

```css
.iyzico-installment-table {
    border: 1px solid #ddd;
    border-radius: 4px;
}

.iyzico-installment-row:hover {
    background-color: #f9f9f9;
}
```

### JavaScript Özelleştirme

Dinamik taksit güncellemesi için JavaScript işlemleri:

```javascript
// Varyasyon değişikliklerini dinle
jQuery(document).on('found_variation', 'form.variations_form', function(event, variation) {
    if (variation && variation.display_price) {
        var finalPrice = variation.display_price;
        
        // KDV hesaplama
        if (window.installment_ajax.vat_enabled) {
            finalPrice = finalPrice * (1 + (window.installment_ajax.vat_rate / 100));
        }
        
        loadInstallments(finalPrice);
    }
});

// Taksit bilgilerini güncelle
function loadInstallments(price) {
    jQuery.ajax({
        url: window.installment_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'get_installment_options',
            price: price,
            product_id: window.installment_ajax.product_id,
            nonce: window.installment_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                jQuery('.dynamic-iyzico-installment').html(response.data);
            }
        }
    });
}
```

## 🐛 Sorun Giderme

### Yaygın Sorunlar

**Taksit bilgileri görünmüyor:**
- API kimlik bilgilerini kontrol edin
- WooCommerce'ın aktif olduğundan emin olun
- Dinamik taksit ayarının etkin olduğunu kontrol edin
- Log dosyalarını inceleyin

**Varyasyonlarda taksit güncellenmiyor:**
- JavaScript hatalarını kontrol edin (Browser Console)
- AJAX isteklerinin başarılı olduğunu kontrol edin
- Nonce değerinin doğru olduğundan emin olun

**API hatası alıyorsunuz:**
- API Key ve Secret Key'in doğru olduğunu kontrol edin
- Test/Canlı mod ayarını kontrol edin
- cURL extension'ın aktif olduğundan emin olun

## 📱 Responsive Tasarım

Eklenti tüm cihazlarda uyumlu çalışır:

- **Masaüstü**: Tam genişlik tablo görünümü
- **Tablet**: Orta boyut tablo görünümü
- **Mobil**: Dikey liste görünümü

## 🌐 Çoklu Dil Desteği

Eklenti i18n standartlarını kullanır:

- **Türkçe**: Varsayılan dil
- **İngilizce**: Çeviri dosyaları mevcut
- **Özel Çeviriler**: `languages/` klasöründe eklenebilir

## 🔒 Güvenlik

- **Nonce Kontrolü**: AJAX isteklerinde güvenlik
- **ABSPATH Kontrolü**: Doğrudan erişim engelleme
- **API Güvenliği**: iyzico'nun güvenli API protokolü
- **WordPress Standartları**: WordPress coding standards uyumlu

## 📊 Performans

- **Lazy Loading**: Sadece gerekli sayfalarda script yükleme
- **AJAX Caching**: Taksit bilgilerini önbellekleme
- **Database Optimization**: Veritabanı sorgularını optimize etme

## 🤝 Katkıda Bulunma

1. Bu repository'yi fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add amazing feature'`)
4. Branch'inizi push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## 📄 Lisans

Bu proje [GPL v2](https://www.gnu.org/licenses/gpl-2.0.html) lisansı altında lisanslanmıştır.

## 📞 Destek

- **Teknik Destek**: [iyzico Müşteri Hizmetleri](https://iyzico.com/iletisim)
- **Dokümantasyon**: [iyzico Developer Portal](https://docs.iyzico.com/)
- **GitHub Issues**: [Repository Issues](https://github.com/iyzico/iyzipay-woocommerce-installment/issues)

## 🔄 Güncellemeler

### v1.1.0
- **Dinamik Taksit Sistemi**: Varyasyonlu ürünlerde anlık taksit güncelleme
- **KDV Hesaplama**: Ürün fiyatlarına KDV dahil etme seçeneği
- **Banka Logo Desteği**: Kredi kartı familyalarına göre otomatik logo gösterimi
- **AJAX Güvenlik**: Nonce kontrolü ve güvenlik iyileştirmeleri
- **CSS Optimizasyonu**: Responsive tasarım iyileştirmeleri

### v1.0.0
- İlk sürüm
- WooCommerce ürün sayfası entegrasyonu
- iyzico taksit hesaplama entegrasyonu
- Taksit seçeneklerini görüntüleme
- Responsive tasarım
- HPOS uyumluluğu
- Gelişmiş loglama sistemi

## 📝 Changelog

Detaylı değişiklik listesi için [CHANGELOG.md](CHANGELOG.md) dosyasını inceleyin.

---

**iyzico Installment** - WooCommerce için profesyonel taksit çözümü

[![iyzico](https://img.shields.io/badge/iyzico-Official%20Plugin-orange.svg)](https://iyzico.com/)
