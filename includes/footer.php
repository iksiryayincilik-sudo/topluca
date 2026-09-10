</main>
<section class="trust-strip">
  <div class="shell trust-strip__grid">
    <div class="trust-item"><span>🚚</span><div><b>Aynı Gün Teslimat</b><small>Ankara'da <?=h((string)setting('same_day_cutoff','15:00'))?>'a kadar</small></div></div>
    <div class="trust-item"><span>🛡</span><div><b>Güvenli Ödeme</b><small>Güvenli bağlantı ve ödeme altyapısı</small></div></div>
    <div class="trust-item"><span>📦</span><div><b>Kolay İade</b><small>İade sürecinizi kolayca takip edin</small></div></div>
    <div class="trust-item"><span>🎧</span><div><b>Müşteri Desteği</b><small>Hafta içi 09:00 - 18:00</small></div></div>
  </div>
</section>
<footer class="site-footer">
  <div class="shell footer-grid">
    <div class="footer-brand">
      <?php $logoClass='footer-logo';require __DIR__.'/logo.php';?>
      <p><?=h((string)setting('site_tagline','İhtiyacın olan ne varsa, Topluca.'))?></p>
      <div class="socials"><a href="#" aria-label="Instagram">◎</a><a href="#" aria-label="Facebook">f</a><a href="#" aria-label="Youtube">▶</a><a href="#" aria-label="LinkedIn">in</a></div>
    </div>
    <div><h4>Kategoriler</h4><?php foreach(main_categories() as $cat):?><a href="<?=url('category.php?slug='.urlencode($cat['slug']))?>"><?=h($cat['name'])?></a><?php endforeach;?></div>
    <div><h4>Müşteri Hizmetleri</h4><a href="<?=url('orders.php')?>">Sipariş Takibi</a><a href="<?=url('account.php')?>">Hesabım</a><a href="<?=url('favorites.php')?>">Favorilerim</a><a href="<?=url('contact.php')?>">Sıkça Sorulan Sorular</a><a href="<?=url('contact.php')?>">Yardım</a><a href="<?=url('contact.php')?>">İletişim</a></div>
    <div><h4>Kurumsal</h4><a href="<?=url('contact.php')?>">Hakkımızda</a><a href="<?=url('contact.php')?>">Kariyer</a><a href="<?=url('contact.php')?>">KVKK</a><a href="<?=url('contact.php')?>">Kullanım Koşulları</a><a href="<?=url('contact.php')?>">Gizlilik Politikası</a></div>
    <div><h4>İletişim</h4><p>📍 <?=h((string)setting('company_city','Ankara'))?>, Türkiye</p><p>☎ <?=h((string)setting('support_phone','0850 302 0 850'))?></p><p>✉ <?=h((string)setting('support_email','destek@topluca.net'))?></p><p>◷ Hafta içi 09:00 - 18:00</p></div>
  </div>
  <div class="shell footer-bottom"><span>© <?=date('Y')?> TOPLUCA. Tüm hakları saklıdır.</span><span>Türkiye'nin Alışveriş Platformu</span></div>
</footer>
<script src="<?=asset('js/app.js')?>?v=3.0.0"></script>
</body></html>
