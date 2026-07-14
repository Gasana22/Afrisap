</main>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">Safari<span style="color:var(--ember);">sap</span></div>
        <div class="footer-tagline">Explore. Experience. Belong.</div>
      </div>
      <div class="footer-col">
        <p class="footer-col__title">Safaris</p>
        <a href="<?= h(url('/tours.php?category=gorilla-trekking-safaris')) ?>">Gorilla Trekking</a>
        <a href="<?= h(url('/tours.php?category=chimpanzee-trekking-safaris')) ?>">Chimpanzee Trekking</a>
        <a href="<?= h(url('/tours.php?category=wildlife-game-drives-safaris')) ?>">Wildlife &amp; Game Drives</a>
        <a href="<?= h(url('/tours.php?category=birding-safaris')) ?>">Birding Safaris</a>
      </div>
      <div class="footer-col">
        <p class="footer-col__title">Experiential</p>
        <a href="<?= h(url('/experiences.php?type=cultural-experience')) ?>">Cultural Experience</a>
        <a href="<?= h(url('/experiences.php?type=farm-experience')) ?>">Farm Experience</a>
        <a href="<?= h(url('/experiences.php?type=sports-experience')) ?>">Sports Experience</a>
        <a href="<?= h(url('/experiences.php?type=ghetto-experience')) ?>">Ghetto Experience</a>
      </div>
      <div class="footer-col">
        <p class="footer-col__title">About</p>
        <a href="<?= h(url('/about.php')) ?>">About Us</a>
        <a href="<?= h(url('/careers.php')) ?>">Careers</a>
        <a href="<?= h(url('/blog.php')) ?>">Blogs</a>
        <a href="<?= h(url('/agents.php')) ?>">Join the Agent Pool</a>
      </div>
      <div class="footer-col">
        <p class="footer-col__title">Contact</p>
        <a href="tel:+256393246926">+256 393 246 926</a>
        <a href="https://wa.me/256775328952">WhatsApp us</a>
        <a href="mailto:info@safarisap.com">info@safarisap.com</a>
        <a href="<?= h(url('/contact.php')) ?>">Contact form</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> Safarisap. Branches in Kampala, Nairobi, Addis Ababa &amp; London.</span>
      <span>Uganda &middot; Kenya &middot; Tanzania &middot; Rwanda &middot; Burundi &middot; South Sudan &middot; DR Congo</span>
    </div>
  </div>
</footer>
<script src="<?= h(assetUrl('js/site.js')) ?>"></script>
</body>
</html>
