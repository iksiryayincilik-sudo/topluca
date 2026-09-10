</main>
<section class="trust-strip">
  <div class="container trust-grid">
    <div><b>⚡ Ankara Aynı Gün</b><span>Uygun ürün, adres, saat ve kapasitede hızlı teslimat</span></div>
    <div><b>🚚 Güçlü Kargo Ağı</b><span>Sepette adresinize uygun kargo seçenekleri</span></div>
    <div><b>🔒 Güvenli Alışveriş</b><span>Güvenli oturum, stok ve sipariş kontrolü</span></div>
    <div><b>↩ Sipariş Takibi</b><span>Benzersiz sipariş koduyla durum takibi</span></div>
  </div>
</section>
<footer class="footer">
  <div class="container footer-grid">
    <div class="footer-about">
      <img src="<?=h($logoSrc)?>" alt="TOPLUCA">
      <p><?=h(setting('footer_description','Kırtasiye, kitap, teknoloji ve ofis ihtiyaçlarını tek sepette buluşturan alışveriş platformu.'))?></p>
      <?php if(trim((string)setting('company_phone',''))!==''):?><a href="tel:<?=h(preg_replace('/[^0-9+]/','',(string)setting('company_phone','')))?>"><?=h(setting('company_phone',''))?></a><?php endif;?>
      <?php if(trim((string)setting('company_email',''))!==''):?><a href="mailto:<?=h(setting('company_email',''))?>"><?=h(setting('company_email',''))?></a><?php endif;?>
    </div>
    <div><h4>Alışveriş</h4><?php foreach(array_slice(main_categories(),0,7) as $c):?><a href="<?=app_url('category.php?slug='.urlencode($c['slug']))?>"><?=h($c['name'])?></a><?php endforeach;?></div>
    <div><h4>Müşteri</h4><a href="<?=app_url('account.php')?>">Hesabım</a><a href="<?=app_url('favorites.php')?>">Favoriler</a><a href="<?=app_url('order-track.php')?>">Sipariş Takibi</a><a href="<?=app_url('contact.php')?>">İletişim</a></div>
    <div><h4>TOPLUCA</h4><a href="<?=app_url('page.php?slug=hakkimizda')?>">Hakkımızda</a><a href="<?=app_url('page.php?slug=kvkk')?>">KVKK</a><a href="<?=app_url('page.php?slug=gizlilik')?>">Gizlilik</a><a href="<?=app_url('page.php?slug=mesafeli-satis')?>">Mesafeli Satış</a></div>
  </div>
  <div class="container footer-bottom"><span>© <?=date('Y')?> TOPLUCA. Tüm hakları saklıdır.</span><span><?=h(setting('company_name','Yıldız Ofis Kırtasiye'))?></span></div>
</footer>
<script>window.TOPLUCA={baseUrl:<?=json_encode(app_url(),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>,bannerAutoplay:<?=setting('banner_autoplay','1')==='1'?'true':'false'?>,bannerInterval:<?=(int)setting('banner_interval','5500')?>};</script>
<script src="<?=app_url('assets/js/app.js')?>?v=5.1.0"></script>
</body></html>