<?php
// index.php
$db = new SQLite3('app.db');

// Tabellen erstellen
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY, name TEXT, price REAL)");
$db->exec("CREATE TABLE IF NOT EXISTS user_products (id INTEGER PRIMARY KEY, user_id INTEGER, product_id INTEGER)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $stmt = $db->prepare("INSERT INTO users (name) VALUES (:name)");
        $stmt->bindValue(':name', $_POST['username'], SQLITE3_TEXT);
        $stmt->execute();
    }
    if (isset($_POST['add_product'])) {
        $stmt = $db->prepare("INSERT INTO products (name, price) VALUES (:name, :price)");
        $stmt->bindValue(':name', $_POST['productname'], SQLITE3_TEXT);
        $stmt->bindValue(':price', $_POST['price'], SQLITE3_FLOAT);
        $stmt->execute();
    }
    if (isset($_POST['assign_product'])) {
        $stmt = $db->prepare("INSERT INTO user_products (user_id, product_id) VALUES (:uid, :pid)");
        $stmt->bindValue(':uid', $_POST['user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':pid', $_POST['product_id'], SQLITE3_INTEGER);
        $stmt->execute();
    }
    if (isset($_POST['delete_product'])) {
        $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
        $stmt->bindValue(':id', $_POST['product_id'], SQLITE3_INTEGER);
        $stmt->execute();
    }
    if (isset($_POST['delete_user'])) {
        $stmt = $db->prepare("DELETE FROM user_products WHERE user_id = :uid");
        $stmt->bindValue(':uid', $_POST['user_id'], SQLITE3_INTEGER);
        $stmt->execute();

        $stmt = $db->prepare("DELETE FROM users WHERE id = :uid");
        $stmt->bindValue(':uid', $_POST['user_id'], SQLITE3_INTEGER);
        $stmt->execute();
    }
}

$users = $db->query("SELECT * FROM users");
$products = $db->query("SELECT * FROM products");
$allUsers = $db->query("SELECT * FROM users");
$userList = $db->query("SELECT u.id, u.name, SUM(p.price) AS total
                        FROM users u
                        LEFT JOIN user_products up ON u.id = up.user_id
                        LEFT JOIN products p ON up.product_id = p.id
                        GROUP BY u.id");
$totalSum = $db->querySingle("SELECT SUM(p.price) FROM user_products up JOIN products p ON up.product_id = p.id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FoodDay App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h1 class="mb-4">Produktzuweisung</h1>

    <form method="post" class="mb-3">
        <input name="username" placeholder="Name" class="form-control mb-2" required>
        <button name="add_user" class="btn btn-primary">Benutzer hinzufügen</button>
    </form>

    <form method="post" class="mb-3">
        <input name="productname" placeholder="Produkt" class="form-control mb-2" required>
        <input name="price" placeholder="Preis" type="number" step="0.01" class="form-control mb-2" required>
        <button name="add_product" class="btn btn-success">Produkt hinzufügen</button>
    </form>

    <form method="post" class="mb-3">
        <select name="user_id" class="form-select mb-2">
            <?php while ($u = $users->fetchArray()) echo "<option value='{$u['id']}'>{$u['name']}</option>"; ?>
        </select>
        <select name="product_id" class="form-select mb-2">
            <?php while ($p = $products->fetchArray()) echo "<option value='{$p['id']}'>{$p['name']} ({$p['price']}€)</option>"; ?>
        </select>
        <button name="assign_product" class="btn btn-warning">Produkt zuweisen</button>
    </form>

    <form method="post" class="mb-3">
        <select name="product_id" class="form-select mb-2">
            <?php while ($p = $products->fetchArray()) echo "<option value='{$p['id']}'>{$p['name']} ({$p['price']}€)</option>"; ?>
        </select>
        <button name="delete_product" class="btn btn-danger">Produkt löschen</button>
    </form>

    <form method="post" class="mb-3">
        <select name="user_id" class="form-select mb-2">
            <?php while ($u = $allUsers->fetchArray()) echo "<option value='{$u['id']}'>{$u['name']}</option>"; ?>
        </select>
        <button name="delete_user" class="btn btn-outline-danger">Benutzer löschen</button>
    </form>

    <h3 class="mt-5">Übersicht</h3>
    <ul class="list-group mb-3">
        <?php while ($u = $userList->fetchArray()) echo "<li class='list-group-item'>{$u['name']}: " . number_format($u['total'] ?? 0, 2) . "€</li>"; ?>
    </ul>
    <h4>Gesamtsumme: <?php echo number_format($totalSum ?? 0, 2); ?>€</h4>

    <a href="export.php" class="btn btn-outline-secondary mt-3">PDF Export</a>
</body>
</html>