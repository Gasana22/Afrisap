<?php
declare(strict_types=1);

/**
 * Smoke test suite for Safarisap. Spins up an isolated test database and a
 * PHP dev server, exercises the public site and admin panel end-to-end
 * (including form submissions and access control), then tears both down.
 *
 * Usage: php tests/smoke.php
 * Requires: PHP CLI with curl + pdo_mysql, and a reachable MySQL/MariaDB
 * server the current user can create databases on (matches the app's own
 * requirements -- no extra dependencies).
 */

require __DIR__ . '/support.php';

$ADMIN_EMAIL = 'smoke-admin@safarisap.com';
$ADMIN_PASSWORD = 'SmokeAdminPass123!';

function smoke_seed_fixtures(string $adminEmail, string $adminPassword): array
{
    $db = smoke_db();

    $db->prepare('INSERT INTO admin_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
        ->execute(['Smoke Admin', $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), 'super_admin']);

    $ugandaId = (int) $db->query("SELECT id FROM countries WHERE name = 'Uganda'")->fetchColumn();
    $gorillaCatId = (int) $db->query("SELECT id FROM tour_categories WHERE slug = 'gorilla-trekking-safaris'")->fetchColumn();
    $culturalTypeId = (int) $db->query("SELECT id FROM experience_types WHERE slug = 'cultural-experience'")->fetchColumn();

    $db->prepare('INSERT INTO destinations (country_id, name, overview) VALUES (?, ?, ?)')
        ->execute([$ugandaId, 'Bwindi Impenetrable National Park', 'Home to mountain gorillas.']);
    $bwindiId = (int) $db->lastInsertId();

    $db->prepare('INSERT INTO destinations (country_id, name, overview) VALUES (?, ?, ?)')
        ->execute([$ugandaId, 'Queen Elizabeth National Park', 'Savannah and the Kazinga Channel.']);
    $qenpId = (int) $db->lastInsertId();

    $db->prepare('INSERT INTO tour_operators (company_name, phone, email) VALUES (?, ?, ?)')
        ->execute(['Smoke Test Operator', '+256700000000', 'ops@smoketest.com']);
    $operatorId = (int) $db->lastInsertId();

    $db->prepare("INSERT INTO tours (title, category_id, budget_type, price, discount_percent, days, min_pax, max_pax, short_overview, operator_id, status) VALUES (?, ?, 'Mid-Range', 2000, 0, 5, 2, 8, ?, ?, 'published')")
        ->execute(['Smoke Test Gorilla Safari', $gorillaCatId, 'A test tour used by the smoke suite.', $operatorId]);
    $tourId = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO tour_destinations (tour_id, destination_id) VALUES (?, ?), (?, ?)')
        ->execute([$tourId, $bwindiId, $tourId, $qenpId]);

    $tribeId = (int) $db->query("SELECT id FROM experience_destinations WHERE experience_type_id = $culturalTypeId AND name = 'Buganda'")->fetchColumn();
    $tribeId2 = (int) $db->query("SELECT id FROM experience_destinations WHERE experience_type_id = $culturalTypeId AND name = 'Ankole'")->fetchColumn();

    $db->prepare("INSERT INTO experience_tours (title, experience_type_id, price, days, min_pax, max_pax, short_overview, status) VALUES (?, ?, 400, 3, 2, 10, ?, 'published')")
        ->execute(['Smoke Test Cultural Tour', $culturalTypeId, 'A test experience tour.']);
    $experienceTourId = (int) $db->lastInsertId();
    $db->prepare('INSERT INTO experience_tour_destinations (experience_tour_id, experience_destination_id) VALUES (?, ?), (?, ?)')
        ->execute([$experienceTourId, $tribeId, $experienceTourId, $tribeId2]);

    $activityId = (int) $db->query("SELECT id FROM activities WHERE name = 'Nature Walks'")->fetchColumn();
    $db->prepare('INSERT INTO tour_activities (tour_id, activity_id, title, description, sort_order) VALUES (?, ?, ?, ?, 0)')
        ->execute([$tourId, $activityId, 'Nature Walk', 'A guided forest walk.']);

    return compact('bwindiId', 'qenpId', 'tourId', 'tribeId', 'experienceTourId', 'activityId');
}

// ---------- Setup ----------

smoke_setup_database();
$fixtures = smoke_seed_fixtures($ADMIN_EMAIL, $ADMIN_PASSWORD);
smoke_start_server();

register_shutdown_function(function () {
    smoke_stop_server();
    smoke_teardown_database();
});

// ---------- Public pages: no fatal errors, correct status ----------

smoke_section('Public pages load cleanly');

$publicPages = [
    '/index.php', '/tours.php', '/experiences.php', '/about.php', '/travel-tips.php',
    '/careers.php', '/blog.php', '/contact.php', '/gallery.php', '/agents.php',
    '/quote.php?type=safari', '/quote.php?type=experiential',
];
foreach ($publicPages as $path) {
    $res = smoke_request('GET', $path);
    smoke_check($res['status'] === 200, "GET $path -> 200");
    smoke_check(!str_contains($res['body'], 'Fatal error'), "GET $path has no fatal error");
}

$notFound = smoke_request('GET', '/404.php');
smoke_check($notFound['status'] === 404, 'GET /404.php -> 404 status');

// ---------- Fixture-dependent detail pages ----------

smoke_section('Detail pages render fixture content');

$tourPage = smoke_request('GET', '/tour.php?id=' . $fixtures['tourId']);
smoke_check($tourPage['status'] === 200, 'Tour detail page loads');
smoke_check(str_contains($tourPage['body'], 'Smoke Test Gorilla Safari'), 'Tour detail shows the tour title');
smoke_check(str_contains($tourPage['body'], 'Smoke Test Operator'), 'Tour detail shows the operator');

$destPage = smoke_request('GET', '/destination.php?id=' . $fixtures['bwindiId']);
smoke_check($destPage['status'] === 200, 'Destination detail page loads');
smoke_check(str_contains($destPage['body'], 'Bwindi Impenetrable'), 'Destination detail shows the name');
smoke_check(str_contains($destPage['body'], 'Smoke Test Gorilla Safari'), 'Destination detail lists the tour that visits it');

$catPage = smoke_request('GET', '/tours.php?category=gorilla-trekking-safaris');
smoke_check($catPage['status'] === 200, 'Category landing page loads');
smoke_check(str_contains($catPage['body'], 'Gorilla Trekking'), 'Category landing shows the category name');

$expTourPage = smoke_request('GET', '/experience-tour.php?id=' . $fixtures['experienceTourId']);
smoke_check($expTourPage['status'] === 200, 'Experience tour detail loads');
smoke_check(str_contains($expTourPage['body'], 'Smoke Test Cultural Tour'), 'Experience tour shows its title');

$expDestPage = smoke_request('GET', '/experience-destination.php?id=' . $fixtures['tribeId']);
smoke_check($expDestPage['status'] === 200, 'Experience destination detail loads');

$activityPage = smoke_request('GET', '/activity.php?id=' . $fixtures['activityId']);
smoke_check($activityPage['status'] === 200, 'Activity detail loads');
smoke_check(str_contains($activityPage['body'], 'Smoke Test Gorilla Safari'), 'Activity page cross-references the tour that features it');

// ---------- Public forms actually write to the database ----------

smoke_section('Public forms submit and persist');

$csrf = smoke_extract_csrf($tourPage['body']);
smoke_check($csrf !== null, 'Tour page contains a CSRF token');

$bookingRes = smoke_request('POST', '/book.php', [
    'tour_id' => $fixtures['tourId'],
    'csrf_token' => $csrf,
    'customer_name' => 'Smoke Test Customer',
    'customer_email' => 'customer@smoketest.com',
    'customer_phone' => '+15550100',
    'num_people' => 2,
    'message' => 'Automated smoke test booking.',
]);
smoke_check($bookingRes['status'] === 302, 'Booking submission redirects');
$bookingCount = (int) smoke_db()->query("SELECT COUNT(*) FROM bookings WHERE customer_email = 'customer@smoketest.com'")->fetchColumn();
smoke_check($bookingCount === 1, 'Booking row landed in the database');

$quotePage = smoke_request('GET', '/quote.php?type=safari');
$quoteCsrf = smoke_extract_csrf($quotePage['body']);
$quoteRes = smoke_request('POST', '/quote-submit.php', [
    'quote_type' => 'safari',
    'csrf_token' => $quoteCsrf,
    'full_name' => 'Smoke Test Quoter',
    'email' => 'quoter@smoketest.com',
    'details' => 'Automated smoke test quote request.',
]);
smoke_check($quoteRes['status'] === 302, 'Quote submission redirects');
$quoteCount = (int) smoke_db()->query("SELECT COUNT(*) FROM quote_requests WHERE email = 'quoter@smoketest.com'")->fetchColumn();
smoke_check($quoteCount === 1, 'Quote request row landed in the database');

$contactPage = smoke_request('GET', '/contact.php');
$contactCsrf = smoke_extract_csrf($contactPage['body']);
$contactRes = smoke_request('POST', '/contact-submit.php', [
    'csrf_token' => $contactCsrf,
    'name' => 'Smoke Test Contact',
    'email' => 'contact@smoketest.com',
    'message' => 'Automated smoke test message.',
]);
smoke_check($contactRes['status'] === 302, 'Contact submission redirects');
$contactCount = (int) smoke_db()->query("SELECT COUNT(*) FROM contact_messages WHERE email = 'contact@smoketest.com'")->fetchColumn();
smoke_check($contactCount === 1, 'Contact message row landed in the database');

// CSRF protection: the same booking request, minus the token, must be rejected.
$noCsrfRes = smoke_request('POST', '/book.php', [
    'tour_id' => $fixtures['tourId'],
    'customer_name' => 'Should Not Save',
    'customer_email' => 'nocsrf@smoketest.com',
    'num_people' => 1,
]);
smoke_check($noCsrfRes['status'] === 403, 'Booking without a CSRF token is rejected (403)');
$noCsrfCount = (int) smoke_db()->query("SELECT COUNT(*) FROM bookings WHERE customer_email = 'nocsrf@smoketest.com'")->fetchColumn();
smoke_check($noCsrfCount === 0, 'Booking without a CSRF token did not save');

// ---------- Admin auth & access control ----------

smoke_section('Admin authentication and access control');

$deniedDashboard = smoke_request('GET', '/admin/index.php');
smoke_check($deniedDashboard['status'] === 302, 'Dashboard redirects to login when logged out');

$loginPage = smoke_request('GET', '/admin/login.php');
$loginCsrf = smoke_extract_csrf($loginPage['body']);

$badLogin = smoke_request('POST', '/admin/login.php', [
    'csrf_token' => $loginCsrf,
    'email' => $ADMIN_EMAIL,
    'password' => 'wrong-password',
]);
smoke_check($badLogin['status'] === 200 && str_contains($badLogin['body'], 'match an admin account'), 'Wrong password is rejected with an error');

$goodLogin = smoke_request('POST', '/admin/login.php', [
    'csrf_token' => $loginCsrf,
    'email' => $ADMIN_EMAIL,
    'password' => $ADMIN_PASSWORD,
]);
smoke_check($goodLogin['status'] === 302, 'Correct credentials log in (redirect)');

$dashboard = smoke_request('GET', '/admin/index.php');
smoke_check($dashboard['status'] === 200 && str_contains($dashboard['body'], 'Dashboard'), 'Dashboard loads once authenticated');

// ---------- Admin CRUD round trip (Countries) ----------

smoke_section('Admin CRUD round trip');

$countryFormPage = smoke_request('GET', '/admin/countries/form.php');
$countryCsrf = smoke_extract_csrf($countryFormPage['body']);
$createCountry = smoke_request('POST', '/admin/countries/form.php', [
    'csrf_token' => $countryCsrf,
    'name' => 'Smoke Testistan',
]);
smoke_check($createCountry['status'] === 302, 'Creating a country redirects');
$newCountryId = (int) smoke_db()->query("SELECT id FROM countries WHERE name = 'Smoke Testistan'")->fetchColumn();
smoke_check($newCountryId > 0, 'New country exists in the database');

$editFormPage = smoke_request('GET', '/admin/countries/form.php?id=' . $newCountryId);
smoke_check(str_contains($editFormPage['body'], 'Smoke Testistan'), 'Edit form pre-fills the existing name');
$editCsrf = smoke_extract_csrf($editFormPage['body']);
smoke_request('POST', '/admin/countries/form.php?id=' . $newCountryId, [
    'csrf_token' => $editCsrf,
    'name' => 'Smoke Testistan (Updated)',
]);
$updatedName = smoke_db()->query("SELECT name FROM countries WHERE id = $newCountryId")->fetchColumn();
smoke_check($updatedName === 'Smoke Testistan (Updated)', 'Editing a country persists the change');

$deleteCsrf = smoke_extract_csrf(smoke_request('GET', '/admin/countries/index.php')['body']);
smoke_request('POST', '/admin/countries/delete.php', ['csrf_token' => $deleteCsrf, 'id' => $newCountryId]);
$stillExists = (int) smoke_db()->query("SELECT COUNT(*) FROM countries WHERE id = $newCountryId")->fetchColumn();
smoke_check($stillExists === 0, 'Deleting a country removes it from the database');

// ---------- Role-based access control ----------

smoke_section('Editor role is restricted from admin settings');

smoke_db()->prepare('INSERT INTO admin_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
    ->execute(['Smoke Editor', 'smoke-editor@safarisap.com', password_hash('EditorPass123!', PASSWORD_DEFAULT), 'editor']);

$editorLoginPage = smoke_request('GET', '/admin/logout.php');
$loginPage2 = smoke_request('GET', '/admin/login.php');
$loginCsrf2 = smoke_extract_csrf($loginPage2['body']);
smoke_request('POST', '/admin/login.php', [
    'csrf_token' => $loginCsrf2,
    'email' => 'smoke-editor@safarisap.com',
    'password' => 'EditorPass123!',
]);

$blockedUsersPage = smoke_request('GET', '/admin/users/index.php');
smoke_check($blockedUsersPage['status'] === 403, 'Editor role is blocked from Admin Users (403)');

// ---------- Done ----------

exit(smoke_summary());
