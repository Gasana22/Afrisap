<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/media.php';
require_login();

$page_title = 'Dashboard';
$page_eyebrow = 'Overview';
$active_nav = 'dashboard';

$stats = [
    'Countries' => ['value' => (int) db()->query('SELECT COUNT(*) FROM countries')->fetchColumn(), 'icon' => 'pin', 'variant' => 'turquoise'],
    'Tour categories' => ['value' => (int) db()->query('SELECT COUNT(*) FROM tour_categories')->fetchColumn(), 'icon' => 'tag', 'variant' => 'olive'],
    'Destinations' => ['value' => (int) db()->query('SELECT COUNT(*) FROM destinations')->fetchColumn(), 'icon' => 'pin', 'variant' => 'emerald'],
    'Published tours' => ['value' => (int) db()->query("SELECT COUNT(*) FROM tours WHERE status = 'published'")->fetchColumn(), 'icon' => 'compass', 'variant' => 'hero'],
];

$inboxStats = [
    ['label' => 'Pending bookings', 'value' => (int) db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(), 'href' => '/admin/bookings/index.php', 'icon' => 'calendar'],
    ['label' => 'New quote requests', 'value' => (int) db()->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'new'")->fetchColumn(), 'href' => '/admin/quotes/index.php', 'icon' => 'tag'],
    ['label' => 'New custom tour requests', 'value' => (int) db()->query("SELECT COUNT(*) FROM custom_tour_requests WHERE status = 'new'")->fetchColumn(), 'href' => '/admin/custom-tours/index.php', 'icon' => 'sliders'],
    ['label' => 'New agent applications', 'value' => (int) db()->query("SELECT COUNT(*) FROM agents WHERE status = 'new'")->fetchColumn(), 'href' => '/admin/agents/index.php', 'icon' => 'users'],
    ['label' => 'Career applications', 'value' => (int) db()->query('SELECT COUNT(*) FROM career_applications')->fetchColumn(), 'href' => '/admin/career-applications/index.php', 'icon' => 'briefcase'],
    ['label' => 'New messages', 'value' => (int) db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(), 'href' => '/admin/messages/index.php', 'icon' => 'mail'],
];

// ---- Enquiries trend: bookings vs quote requests, last 6 months ----
$trendMonthKeys = [];
$trendMonthLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("-{$i} months");
    $trendMonthKeys[] = date('Y-m', $ts);
    $trendMonthLabels[] = date('M', $ts);
}

$bookingsByMonth = array_fill_keys($trendMonthKeys, 0);
foreach (db()->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c FROM bookings WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym")->fetchAll() as $row) {
    if (isset($bookingsByMonth[$row['ym']])) {
        $bookingsByMonth[$row['ym']] = (int) $row['c'];
    }
}

$quotesByMonth = array_fill_keys($trendMonthKeys, 0);
foreach (db()->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c FROM quote_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym")->fetchAll() as $row) {
    if (isset($quotesByMonth[$row['ym']])) {
        $quotesByMonth[$row['ym']] = (int) $row['c'];
    }
}

$trendMax = max(1, max($bookingsByMonth), max($quotesByMonth));

// ---- Recent activity: latest enquiries across every public form, merged ----
$activityFeed = [];
foreach (db()->query('SELECT customer_name AS label, created_at FROM bookings ORDER BY created_at DESC LIMIT 5')->fetchAll() as $row) {
    $activityFeed[] = ['icon' => 'calendar', 'variant' => 'emerald', 'text' => h($row['label']) . ' made a booking enquiry', 'created_at' => $row['created_at'], 'href' => '/admin/bookings/index.php'];
}
foreach (db()->query("SELECT full_name AS label, quote_type, created_at FROM quote_requests ORDER BY created_at DESC LIMIT 5")->fetchAll() as $row) {
    $activityFeed[] = ['icon' => 'tag', 'variant' => 'turquoise', 'text' => h($row['label']) . ' requested a ' . h($row['quote_type']) . ' quote', 'created_at' => $row['created_at'], 'href' => '/admin/quotes/index.php'];
}
foreach (db()->query('SELECT name AS label, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5')->fetchAll() as $row) {
    $activityFeed[] = ['icon' => 'mail', 'variant' => '', 'text' => h($row['label']) . ' sent a message', 'created_at' => $row['created_at'], 'href' => '/admin/messages/index.php'];
}
foreach (db()->query('SELECT full_name AS label, created_at FROM custom_tour_requests ORDER BY created_at DESC LIMIT 5')->fetchAll() as $row) {
    $activityFeed[] = ['icon' => 'sliders', 'variant' => 'olive', 'text' => h($row['label']) . ' requested a custom tour', 'created_at' => $row['created_at'], 'href' => '/admin/custom-tours/index.php'];
}
foreach (db()->query('SELECT full_name AS label, created_at FROM agents ORDER BY created_at DESC LIMIT 5')->fetchAll() as $row) {
    $activityFeed[] = ['icon' => 'users', 'variant' => '', 'text' => h($row['label']) . ' applied to join the agent pool', 'created_at' => $row['created_at'], 'href' => '/admin/agents/index.php'];
}
usort($activityFeed, fn (array $a, array $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
$activityFeed = array_slice($activityFeed, 0, 8);

// ---- Recent bookings (mirrors admin/bookings/index.php's query, capped) ----
$recentBookings = db()->query("SELECT b.*,
        CASE b.bookable_type WHEN 'tour' THEN t.title ELSE et.title END AS item_title
    FROM bookings b
    LEFT JOIN tours t ON t.id = b.bookable_id AND b.bookable_type = 'tour'
    LEFT JOIN experience_tours et ON et.id = b.bookable_id AND b.bookable_type = 'experience_tour'
    ORDER BY b.created_at DESC
    LIMIT 5")->fetchAll();
foreach ($recentBookings as &$booking) {
    $booking['cover'] = get_cover_image($booking['bookable_type'], (int) $booking['bookable_id']);
}
unset($booking);

// ---- Upcoming scheduled departures ----
$upcomingTours = db()->query("SELECT id, title, scheduled_date, price, days
    FROM tours
    WHERE status = 'published' AND scheduled_date IS NOT NULL AND scheduled_date >= CURDATE()
    ORDER BY scheduled_date ASC
    LIMIT 5")->fetchAll();
foreach ($upcomingTours as &$tour) {
    $tour['cover'] = get_cover_image('tour', (int) $tour['id']);
}
unset($tour);

// ---- Published tours by category (donut breakdown, top 4 + Other) ----
$categoryRows = db()->query("SELECT c.name, COUNT(*) AS cnt
    FROM tours t
    JOIN tour_categories c ON c.id = t.category_id
    WHERE t.status = 'published'
    GROUP BY c.id, c.name
    ORDER BY cnt DESC")->fetchAll();

$categoryBreakdown = array_slice($categoryRows, 0, 4);
$otherCount = array_sum(array_column(array_slice($categoryRows, 4), 'cnt'));
if ($otherCount > 0) {
    $categoryBreakdown[] = ['name' => 'Other', 'cnt' => $otherCount];
}
$categoryTotal = max(1, array_sum(array_column($categoryBreakdown, 'cnt')));
$donutColors = ['#2eaf7d', '#3fd0c9', '#449342', '#b5502b', '#a97f2e'];

$donutStops = [];
$cursor = 0;
foreach ($categoryBreakdown as $i => $row) {
    $pct = $row['cnt'] / $categoryTotal * 100;
    $donutStops[] = $donutColors[$i % count($donutColors)] . ' ' . round($cursor, 2) . '% ' . round($cursor + $pct, 2) . '%';
    $cursor += $pct;
}
$donutGradient = implode(', ', $donutStops);

require __DIR__ . '/includes/header.php';
?>

<div class="stat-grid">
  <?php foreach ($stats as $label => $stat): ?>
    <div class="stat-tile<?= $stat['variant'] === 'hero' ? ' stat-tile--hero' : '' ?>">
      <div class="stat-tile__icon<?= $stat['variant'] !== 'hero' ? ' stat-tile__icon--' . $stat['variant'] : '' ?>"><?= render_nav_glyph($stat['icon']) ?></div>
      <div class="stat-tile__body">
        <div class="stat-tile__label"><?= h($label) ?></div>
        <div class="stat-tile__value"><?= $stat['value'] ?></div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="stat-tile stat-tile--spark">
    <div class="stat-tile__top">
      <div class="stat-tile__icon stat-tile__icon--emerald"><?= render_nav_glyph('calendar') ?></div>
      <div class="stat-tile__body">
        <div class="stat-tile__label">Bookings this month</div>
        <div class="stat-tile__value"><?= (int) end($bookingsByMonth) ?></div>
      </div>
    </div>
    <div class="stat-tile__spark"><?= render_sparkline(array_values($bookingsByMonth), '#2eaf7d') ?></div>
  </div>

  <div class="stat-tile stat-tile--spark">
    <div class="stat-tile__top">
      <div class="stat-tile__icon stat-tile__icon--turquoise"><?= render_nav_glyph('tag') ?></div>
      <div class="stat-tile__body">
        <div class="stat-tile__label">Quote requests this month</div>
        <div class="stat-tile__value"><?= (int) end($quotesByMonth) ?></div>
      </div>
    </div>
    <div class="stat-tile__spark"><?= render_sparkline(array_values($quotesByMonth), '#3fd0c9') ?></div>
  </div>
</div>

<div class="dash-grid">
  <div class="panel">
    <div class="panel__header">
      <div class="panel__title">Enquiries trend</div>
      <div class="chart-legend">
        <span class="chart-legend__item"><i class="chart-legend__dot chart-legend__dot--bookings"></i>Bookings</span>
        <span class="chart-legend__item"><i class="chart-legend__dot chart-legend__dot--quotes"></i>Quote requests</span>
      </div>
    </div>
    <div class="panel__body">
      <?php if (array_sum($bookingsByMonth) === 0 && array_sum($quotesByMonth) === 0): ?>
        <div class="empty-state">
          <div class="empty-state__title">No enquiries yet</div>
          <div class="empty-state__body">Bookings and quote requests from the public site will chart here.</div>
        </div>
      <?php else: ?>
        <div class="bar-chart">
          <?php foreach ($trendMonthKeys as $i => $key): ?>
            <div class="bar-chart__col">
              <div class="bar-chart__bars">
                <div class="bar-chart__bar bar-chart__bar--bookings" style="height:<?= max(4, (int) round($bookingsByMonth[$key] / $trendMax * 130)) ?>px" title="<?= (int) $bookingsByMonth[$key] ?> bookings in <?= h($trendMonthLabels[$i]) ?>"></div>
                <div class="bar-chart__bar bar-chart__bar--quotes" style="height:<?= max(4, (int) round($quotesByMonth[$key] / $trendMax * 130)) ?>px" title="<?= (int) $quotesByMonth[$key] ?> quote requests in <?= h($trendMonthLabels[$i]) ?>"></div>
              </div>
              <div class="bar-chart__label"><?= h($trendMonthLabels[$i]) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="panel">
    <div class="panel__header">
      <div class="panel__title">Recent activity</div>
    </div>
    <div class="panel__body">
      <?php if (!$activityFeed): ?>
        <div class="empty-state">
          <div class="empty-state__title">Nothing yet</div>
          <div class="empty-state__body">New enquiries will show up here as they arrive.</div>
        </div>
      <?php else: ?>
        <div class="timeline">
          <?php foreach ($activityFeed as $item): ?>
            <a class="timeline-item" href="<?= h(url($item['href'])) ?>">
              <span class="timeline-item__dot<?= $item['variant'] ? ' timeline-item__dot--' . $item['variant'] : '' ?>"></span>
              <div class="timeline-item__body">
                <div class="timeline-item__text"><?= $item['text'] ?></div>
                <div class="timeline-item__time"><?= h(time_ago($item['created_at'])) ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="dash-grid">
  <div class="panel">
    <div class="panel__header">
      <div class="panel__title">Needs attention</div>
    </div>
    <div class="panel__body">
      <div class="sub-list">
        <?php foreach ($inboxStats as $item): ?>
          <div class="sub-row<?= $item['value'] > 0 ? ' sub-row--flagged' : '' ?>">
            <div class="sub-row__body">
              <div class="sub-row__icon"><?= render_nav_glyph($item['icon']) ?></div>
              <div class="sub-row__title"><?= h($item['label']) ?></div>
            </div>
            <a class="btn btn--<?= $item['value'] > 0 ? 'primary' : 'ghost' ?> btn--sm" href="<?= h(url($item['href'])) ?>"><?= $item['value'] ?> &rarr;</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel__header">
      <div class="panel__title">Tours by category</div>
    </div>
    <div class="panel__body">
      <?php if (!$categoryBreakdown): ?>
        <div class="empty-state">
          <div class="empty-state__title">No published tours yet</div>
          <div class="empty-state__body">The category mix will chart here once tours are published.</div>
        </div>
      <?php else: ?>
        <div class="donut-row">
          <div class="donut" style="background:conic-gradient(<?= $donutGradient ?>)">
            <div class="donut__center">
              <div class="donut__value"><?= $categoryTotal ?></div>
              <div class="donut__label">Tours</div>
            </div>
          </div>
          <div class="donut-legend">
            <?php foreach ($categoryBreakdown as $i => $row): ?>
              <span class="donut-legend__item">
                <i class="donut-legend__dot" style="background:<?= h($donutColors[$i % count($donutColors)]) ?>"></i>
                <?= h($row['name']) ?>
                <span class="segment-legend__count"><?= (int) $row['cnt'] ?></span>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="dash-grid">
  <div class="panel">
    <div class="panel__header">
      <div class="panel__title">Recent bookings</div>
      <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/bookings/index.php')) ?>">View all &rarr;</a>
    </div>
    <?php if (!$recentBookings): ?>
      <div class="empty-state">
        <div class="empty-state__title">No bookings here yet</div>
        <div class="empty-state__body">Enquiries submitted from a tour or experience page will show up here.</div>
      </div>
    <?php else: ?>
      <table class="table table--compact">
        <thead>
          <tr><th>Customer</th><th>Item</th><th>Received</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentBookings as $booking): ?>
            <tr>
              <td><?= h($booking['customer_name']) ?></td>
              <td>
                <div class="table-item">
                  <?php if ($booking['cover']): ?>
                    <img class="table-item__thumb" src="<?= h(url('/' . $booking['cover'])) ?>" alt="">
                  <?php endif; ?>
                  <span><?= h($booking['item_title'] ?: 'Deleted') ?></span>
                </div>
              </td>
              <td class="table__meta"><?= h(time_ago($booking['created_at'])) ?></td>
              <td><span class="status-pill status-pill--<?= h($booking['status']) ?>"><?= h(ucfirst($booking['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="panel">
    <div class="panel__header">
      <div class="panel__title">Upcoming departures</div>
      <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/tours/index.php')) ?>">Manage tours &rarr;</a>
    </div>
    <div class="panel__body">
      <?php if (!$upcomingTours): ?>
        <div class="empty-state">
          <div class="empty-state__title">Nothing scheduled</div>
          <div class="empty-state__body">Set a scheduled date on a published tour to see it here.</div>
        </div>
      <?php else: ?>
        <div class="sub-list">
          <?php foreach ($upcomingTours as $tour): ?>
            <a class="sub-row" href="<?= h(url('/admin/tours/manage.php?id=' . $tour['id'])) ?>">
              <div class="sub-row__body">
                <?php if ($tour['cover']): ?>
                  <img class="sub-row__thumb" src="<?= h(url('/' . $tour['cover'])) ?>" alt="">
                <?php else: ?>
                  <div class="sub-row__icon"><?= render_nav_glyph('calendar') ?></div>
                <?php endif; ?>
                <div>
                  <div class="sub-row__title"><?= h($tour['title']) ?></div>
                  <div class="sub-row__meta"><?= h(formatDate($tour['scheduled_date'], 'M j, Y')) ?> &middot; <?= (int) $tour['days'] ?> days</div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel__header">
    <div class="panel__title">Getting started</div>
  </div>
  <div class="panel__body">
    <p style="margin:0 0 10px;">Content builds in order: enter <strong>Countries</strong>, then <strong>Tour Categories</strong>, then <strong>Destinations</strong> (which belong to a country). Tours, Activities and Experiential content build on top of these.</p>
    <p style="margin:0; opacity:0.7;">Signed in as <?= h(current_admin()['name']) ?>.</p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
