=== iyzico Installment ===
Contributors: iyzico, tarikkamat
Tags: iyzico, woocommerce, installment, taksit, ürün-sayfası
Tested up to: 6.8
Stable tag: 1.1.0
Requires at least: 6.6
Requires PHP: 7.4.33
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

iyzico taksit hesaplama motoru ile ürün sayfalarında taksit seçeneklerini gösterin.

== Description ==

iyzico Installment eklentisi, WooCommerce ürün sayfalarında iyzico'nun taksit hesaplamasını kullanarak müşterilerinize taksit seçeneklerini gösterir.

**Ana Özellik:**

* Ürün sayfasında taksit seçeneklerini görüntüleme
* KDV dahil fiyat hesaplama seçeneği
* Admin arayüzünden özel CSS ekleme
* iyzico taksit hesaplama entegrasyonu
* WooCommerce ürün sayfalarına otomatik entegrasyon
* Responsive tasarım

**Ne Yapar:**

Bu eklenti sadece ürün sayfalarında taksit bilgilerini gösterir. Ödeme işlemi yapmaz, sadece taksit hesaplaması ve görüntüleme hizmeti sunar.

**Gereksinimler:**

* PHP 7.4.33 ve üzeri
* cURL extension
* WooCommerce 9.0.0 ve üzeri
* WordPress 6.6.2 ve üzeri
* iyzico WooCommerce (ödeme eklentisi)
* iyzico hesabı (taksit hesaplama için)

**Kullanım:**

Eklenti kurulduktan sonra, tüm WooCommerce ürün sayfalarında otomatik olarak taksit seçenekleri görüntülenir. Müşteriler ürün fiyatını ve mevcut taksit seçeneklerini görebilir.

== Installation ==

**Manuel Kurulum:**

1. Eklenti ZIP dosyasını indirin
2. WordPress yönetici panelinde **Eklentiler > Yeni Ekle** sayfasına gidin
3. **Eklenti Yükle** butonuna tıklayın
4. İndirdiğiniz ZIP dosyasını seçin ve **Şimdi Yükle** butonuna tıklayın
5. Kurulum tamamlandıktan sonra **Eklentiyi Etkinleştir** butonuna tıklayın

**WordPress.org'dan Kurulum:**

1. WordPress yönetici panelinde **Eklentiler > Yeni Ekle** sayfasına gidin
2. Arama kutusuna "iyzico Installment" yazın
3. Eklentiyi bulun ve **Kur** butonuna tıklayın
4. Kurulum tamamlandıktan sonra **Etkinleştir** butonuna tıklayın

**Kurulum Sonrası:**

1. **WooCommerce > Ayarlar > Genel** sayfasına gidin
2. iyzico Installment eklentisini etkinleştirin
3. iyzico hesap bilgilerinizi girin
4. Ürün sayfasında taksit seçeneklerinin görüntülendiğini kontrol edin

== Frequently Asked Questions ==

**= Bu eklenti ödeme alır mı? =**

Hayır, bu eklenti sadece ürün sayfalarında taksit seçeneklerini gösterir. Ödeme işlemi yapmaz.

**= Hangi sayfalarda taksit seçenekleri görünür? =**

Tüm WooCommerce ürün sayfalarında otomatik olarak görünür.

**= WooCommerce uyumluluğu nedir? =**

Eklenti WooCommerce 9.0.0 ve üzeri versiyonlarla uyumludur.

**= Taksit hesaplaması nasıl yapılır? =**

iyzico'nun taksit hesaplama motoru kullanılarak gerçek zamanlı hesaplama yapılır.

**= Destek alabilir miyim? =**

Evet, teknik destek için iyzico müşteri hizmetleri ile iletişime geçebilirsiniz.

== Screenshots ==

1. Ürün sayfası - Taksit seçenekleri
2. Yönetici paneli - Eklenti ayarları

== Changelog ==

= 1.1.0 =
* Dinamik taksit sistemi - Varyasyonlu ürünlerde anlık taksit güncelleme
* [dynamic_iyzico_installment] shortcode desteği
* KDV hesaplama seçeneği eklendi
* Admin panelinden özel CSS ekleme özelliği

= 1.0.0 =
* İlk sürüm
* WooCommerce ürün sayfası entegrasyonu
* iyzico taksit hesaplama motoru entegrasyonu
* Taksit seçeneklerini görüntüleme
* Responsive tasarım

== Upgrade Notice ==

= 1.1.0 =
Major özellik güncellemesi! Dinamik taksit sistemi ve özelleştirme seçenekleri eklendi. Güncelleme şiddetle önerilir.

= 1.0.0 =
Bu ilk sürümdür. Güvenlik ve performans iyileştirmeleri için güncel tutmanız önerilir.
