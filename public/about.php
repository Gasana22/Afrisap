<?php
require_once __DIR__ . '/../includes/bootstrap.php';

render_header(['title' => 'About Us', 'context' => 'public']);
render_public_navbar();
?>
<section class="hero-section py-5">
  <div class="container text-center">
    <h1 class="fw-bold">About Smart Farm Platform</h1>
    <p class="lead">Helping agribusinesses grow smarter, one farm at a time.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <h2 class="fw-bold mb-3">Our Story</h2>
        <p class="text-muted">Smart Farm Platform started with a simple observation: farm operators were running
          serious agribusinesses on paper notebooks, WhatsApp groups and disconnected spreadsheets. We set out to
          build a single, affordable, multi-tenant platform that gives every farm &mdash; from a smallholder
          cooperative to a multi-site commercial operation &mdash; the same tools that large agribusinesses use to
          plan, track and prove where their produce comes from.</p>
        <p class="text-muted">Today, farm managers across the region use our platform to manage crop cycles,
          livestock, workers, inventory, and finances, and to give their customers full farm-to-shelf
          traceability with a single QR code scan.</p>
      </div>
      <div class="col-lg-6 text-center">
        <i class="bi bi-tree" style="font-size: 12rem; color: var(--sfp-primary); opacity: .25;"></i>
      </div>
    </div>
  </div>
</section>

<section class="py-5 bg-light">
  <div class="container">
    <div class="section-heading">
      <h2 class="fw-bold">Our Mission</h2>
      <p class="text-muted">To make world-class farm management and traceability tools accessible to every
        agribusiness, regardless of size, so that better data leads to better harvests, better livelihoods, and
        safer food on every table.</p>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="section-heading">
      <h2 class="fw-bold">What We Value</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card text-center">
          <div class="feature-icon mx-auto"><i class="bi bi-shield-check"></i></div>
          <h5 class="fw-semibold">Trust &amp; Transparency</h5>
          <p class="text-muted mb-0">Every batch we help trace is a promise kept to the end consumer.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center">
          <div class="feature-icon mx-auto"><i class="bi bi-lightbulb"></i></div>
          <h5 class="fw-semibold">Practical Innovation</h5>
          <p class="text-muted mb-0">We build features farm teams actually use in the field, not just on a slide deck.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center">
          <div class="feature-icon mx-auto"><i class="bi bi-people"></i></div>
          <h5 class="fw-semibold">Farmers First</h5>
          <p class="text-muted mb-0">Our roadmap is shaped by the agronomists, managers and workers who use it daily.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php render_footer(['context' => 'public']); ?>
