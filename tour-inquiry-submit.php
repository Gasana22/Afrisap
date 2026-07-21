<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site_bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tours.php');
}

csrf_verify();

$tourId = (int) ($_POST['tour_id'] ?? 0);
$name = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['customer_email'] ?? '');
$whatsapp = trim($_POST['whatsapp_number'] ?? '');
$numVisitors = max(1, (int) ($_POST['num_visitors'] ?? 1));
$country = trim($_POST['country'] ?? '');
$travelDate = trim($_POST['travel_date'] ?? '') ?: null;
$message = trim($_POST['message'] ?? '');
$experienceTourIds = array_values(array_unique(array_filter(array_map('intval', $_POST['experience_tour_ids'] ?? []))));

if (!$tourId || $name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/tour.php?id=' . $tourId);
}

$stmt = db()->prepare("SELECT t.id, t.title, t.price, t.discount_percent, t.budget_type, o.company_name AS operator_name, o.email AS operator_email
    FROM tours t LEFT JOIN tour_operators o ON o.id = t.operator_id
    WHERE t.id = ? AND t.status = 'published'");
$stmt->execute([$tourId]);
$tour = $stmt->fetch();
if (!$tour) {
    redirect('/tours.php');
}

$itineraryNo = 'SS-' . str_pad((string) $tour['id'], 4, '0', STR_PAD_LEFT);
$netPrice = (float) $tour['price'] * (1 - (float) $tour['discount_percent'] / 100);

$insertInquiry = db()->prepare('INSERT INTO tour_itinerary_inquiries
    (tour_id, itinerary_no, tour_title, tour_price, operator_name, budget_type, customer_name, customer_email, whatsapp_number, num_visitors, country, travel_date, message, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$insertInquiry->execute([
    $tourId, $itineraryNo, $tour['title'], $netPrice, $tour['operator_name'], $tour['budget_type'],
    $name, $email, $whatsapp ?: null, $numVisitors, $country ?: null, $travelDate, $message, 'new',
]);
$inquiryId = (int) db()->lastInsertId();

// Optional add-on Experiential tours picked alongside this Safari enquiry --
// only ones that are still actually published get attached.
$addOnTitles = [];
if ($experienceTourIds) {
    $placeholders = implode(',', array_fill(0, count($experienceTourIds), '?'));
    $expStmt = db()->prepare("SELECT id, title FROM experience_tours WHERE status = 'published' AND id IN ($placeholders)");
    $expStmt->execute($experienceTourIds);
    $addOnExperiences = $expStmt->fetchAll();

    $insertAddon = db()->prepare('INSERT INTO tour_itinerary_inquiry_addons (inquiry_id, experience_tour_id, experience_tour_title) VALUES (?, ?, ?)');
    foreach ($addOnExperiences as $addOn) {
        $insertAddon->execute([$inquiryId, $addOn['id'], $addOn['title']]);
        $addOnTitles[] = $addOn['title'];
    }
}
$addOnSummary = $addOnTitles ? "\nAlso interested in: " . implode(', ', $addOnTitles) : '';

send_email(
    $email,
    'We received your enquiry — ' . $tour['title'],
    "Hi $name,\n\nThanks for your enquiry about \"{$tour['title']}\" (Itinerary No. $itineraryNo).\n\nWe'll be in touch shortly to confirm dates and availability.\n\nYour details:\nVisitors: $numVisitors\nExpected travel date: " . ($travelDate ?: 'Not specified') . "$addOnSummary\n\n— Safarisap"
);

$notifyBody = "New tour itinerary enquiry\n\nItinerary No: $itineraryNo\nTour: {$tour['title']}\nPrice: $" . number_format($netPrice, 0) . "\nOperator: " . ($tour['operator_name'] ?: 'Not assigned') . "\nBudget type: {$tour['budget_type']}\n\nName: $name\nEmail: $email\nWhatsApp: " . ($whatsapp ?: 'Not provided') . "\nVisitors: $numVisitors\nCountry: " . ($country ?: 'Not provided') . "\nExpected travel date: " . ($travelDate ?: 'Not specified') . "\nMessage: $message$addOnSummary";
notify_admin('New tour itinerary enquiry: ' . $tour['title'], $notifyBody);
if ($tour['operator_email']) {
    send_email($tour['operator_email'], 'New enquiry for your tour: ' . $tour['title'], $notifyBody, $email);
}

redirect('/tour.php?id=' . $tourId . '&sent=1');
