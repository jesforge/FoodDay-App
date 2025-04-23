<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

$db = new SQLite3('app.db');

$users = $db->query("SELECT u.name, p.name AS product, p.price
    FROM users u
    JOIN user_products up ON u.id = up.user_id
    JOIN products p ON up.product_id = p.id
    ORDER BY u.name");

$userData = [];
$totalSum = 0;
while ($row = $users->fetchArray(SQLITE3_ASSOC)) {
    $userData[$row['name']][] = [
        'product' => $row['product'],
        'price' => $row['price']
    ];
    $totalSum += $row['price'];
}

$html = "<h1 style='text-align:center;'>Produkt-Report</h1>";

foreach ($userData as $user => $products) {
    $html .= "<h3>$user</h3>";
    $html .= "<table width='100%' border='1' cellspacing='0' cellpadding='5'>";
    $html .= "<tr><th width='5%'>✓</th><th width='70%'>Produkt</th><th width='25%'>Preis</th></tr>";
    $sum = 0;
    foreach ($products as $p) {
        $html .= "<tr><td></td><td>{$p['product']}</td><td>" . number_format($p['price'], 2) . " €</td></tr>";
        $sum += $p['price'];
    }
    $html .= "<tr><td colspan='2'><strong>Summe</strong></td><td><strong>" . number_format($sum, 2) . " €</strong></td></tr>";
    $html .= "</table><br>";
}

$html .= "<h2>Gesamtsumme: " . number_format($totalSum, 2) . " €</h2>";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("report.pdf", ["Attachment" => false]);
