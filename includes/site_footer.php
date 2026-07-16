<?php
// Static reference data (typical daytime temperature, not live weather) --
// client asked for coordinates + temperature for at least 6 national parks
// in the footer. No `destinations` rows exist yet to source this from, so
// it's a fixed list here rather than a DB-backed feature.
$footerParks = [
    ['name' => 'Bwindi Impenetrable Forest', 'coords' => "01&deg;04'S, 29&deg;40'E", 'temp' => '18&deg;C'],
    ['name' => 'Queen Elizabeth NP', 'coords' => "00&deg;12'S, 29&deg;54'E", 'temp' => '27&deg;C'],
    ['name' => 'Murchison Falls NP', 'coords' => "02&deg;15'N, 31&deg;48'E", 'temp' => '29&deg;C'],
    ['name' => 'Serengeti NP', 'coords' => "02&deg;20'S, 34&deg;50'E", 'temp' => '26&deg;C'],
    ['name' => 'Maasai Mara Reserve', 'coords' => "01&deg;30'S, 35&deg;08'E", 'temp' => '24&deg;C'],
    ['name' => 'Volcanoes NP', 'coords' => "01&deg;30'S, 29&deg;30'E", 'temp' => '15&deg;C'],
];
?>
<footer class="site-footer">
  <div class="wrap">
    <div class="footer-parks">
      <p class="footer-parks__label">National Parks &mdash; Typical Daytime Temp</p>
      <div class="footer-parks__row">
        <?php foreach ($footerParks as $park): ?>
          <div class="footer-parks__item">
            <span class="footer-parks__name"><?= h($park['name']) ?></span>
            <span class="footer-parks__meta"><?= $park['coords'] ?> &middot; <?= $park['temp'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="footer-grid">
      <div>
        <div class="footer-brand">Safari<span>sap</span></div>
        <div class="footer-tagline">Explore. Experience. Belong.</div>
        <div class="footer-product-of">Safarisap is a service of <a href="https://afrisap.com" target="_blank" rel="noopener">AFRI-SAP LIMITED</a></div>
      </div>
      <div class="footer-col">
        <p class="footer-col__title">Safaris</p>
        <a href="<?= h(url('/tours.php?category=gorilla-trekking-safaris')) ?>">Gorilla Trekking</a>
        <a href="<?= h(url('/tours.php?category=chimpanzee-trekking-safaris')) ?>">Chimpanzee Trekking</a>
        <a href="<?= h(url('/tours.php?category=wildlife-game-drives-safaris')) ?>">Wildlife &amp; Game Drives</a>
        <a href="<?= h(url('/tours.php?category=birding-safaris')) ?>">Birding Safaris</a>
        <a href="<?= h(url('/car-rental.php')) ?>">Car Rental</a>
        <a href="<?= h(url('/flight-booking.php')) ?>">Flight Booking</a>
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
      <span>&copy; <?= date('Y') ?> Safarisap. Branches in Kampala &amp; Kigali.</span>
      <span>Uganda &middot; Kenya &middot; Tanzania &middot; Rwanda &middot; Burundi &middot; South Sudan &middot; DR Congo</span>
    </div>
  </div>
</footer>
<script src="<?= h(assetUrl('js/site.js')) ?>"></script>
</body>
</html>
