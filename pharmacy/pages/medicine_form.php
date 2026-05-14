<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$medicine = [
    'name' => '',
    'category' => '',
    'quantity' => '',
    'cost_price' => '',
    'price' => '',
    'expiry_date' => '',
    'min_stock' => '',
    'supplier_id' => '',
];
$errors = [];

if ($id) {
    $medicine = fetchRow('SELECT * FROM medicines WHERE id = ?', [$id]);
    if (!$medicine) {
        flash('success', 'Medicine not found.');
        redirect('/pages/medicines.php');
    }
}

$suppliers = fetchAllRows('SELECT * FROM suppliers ORDER BY name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine['name'] = trim($_POST['name'] ?? '');
    $medicine['category'] = trim($_POST['category'] ?? '');
    $medicine['quantity'] = trim($_POST['quantity'] ?? '');
    $medicine['cost_price'] = trim($_POST['cost_price'] ?? '');
    $medicine['price'] = trim($_POST['price'] ?? '');
    $medicine['expiry_date'] = trim($_POST['expiry_date'] ?? '');
    $medicine['min_stock'] = trim($_POST['min_stock'] ?? '');
    $medicine['supplier_id'] = trim($_POST['supplier_id'] ?? '');

    if ($medicine['name'] === '') {
        $errors[] = 'Medicine name is required.';
    }
    if ($medicine['category'] === '') {
        $errors[] = 'Category is required.';
    }
    if (!is_numeric($medicine['quantity']) || intval($medicine['quantity']) < 0) {
        $errors[] = 'Quantity must be a valid non-negative number.';
    }
    if (!is_numeric($medicine['cost_price']) || floatval($medicine['cost_price']) < 0) {
        $errors[] = 'Cost price must be a valid non-negative number.';
    }
    if (!is_numeric($medicine['price']) || floatval($medicine['price']) < 0) {
        $errors[] = 'Selling price must be a valid non-negative number.';
    }
    if ($medicine['expiry_date'] === '') {
        $errors[] = 'Expiry date is required.';
    }
    if (!is_numeric($medicine['min_stock']) || intval($medicine['min_stock']) < 0) {
        $errors[] = 'Minimum stock must be a valid non-negative number.';
    }

    if (empty($errors)) {
        $oldQuantity = $id ? $medicine['quantity'] : 0;
        if ($id) {
            $stmt = $pdo->prepare('UPDATE medicines SET name = ?, category = ?, quantity = ?, cost_price = ?, price = ?, expiry_date = ?, min_stock = ?, supplier_id = ? WHERE id = ?');
            $stmt->execute([
                $medicine['name'],
                $medicine['category'],
                intval($medicine['quantity']),
                floatval($medicine['cost_price']),
                floatval($medicine['price']),
                $medicine['expiry_date'],
                intval($medicine['min_stock']),
                $medicine['supplier_id'] ?: null,
                $id,
            ]);
            flash('success', 'Medicine updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO medicines (name, category, quantity, cost_price, price, expiry_date, min_stock, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $medicine['name'],
                $medicine['category'],
                intval($medicine['quantity']),
                floatval($medicine['cost_price']),
                floatval($medicine['price']),
                $medicine['expiry_date'],
                intval($medicine['min_stock']),
                $medicine['supplier_id'] ?: null,
            ]);
            $id = $pdo->lastInsertId();
            flash('success', 'Medicine added successfully.');
        }

        // Log stock change
        $newQuantity = intval($medicine['quantity']);
        $change = $newQuantity - $oldQuantity;
        if ($change != 0) {
            $stmtHistory = $pdo->prepare('INSERT INTO stock_history (medicine_id, change_type, quantity) VALUES (?, ?, ?)');
            $stmtHistory->execute([$id, $change > 0 ? 'IN' : 'OUT', $change]);
        }

        redirect('/pages/medicines.php');
    }
}
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="mb-0"><?= $id ? 'Edit Medicine' : 'Add Medicine' ?></h2>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($medicine['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($medicine['category']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" min="0" class="form-control" value="<?= htmlspecialchars($medicine['quantity']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cost Price</label>
                            <input type="number" name="cost_price" min="0" step="0.01" class="form-control" value="<?= htmlspecialchars($medicine['cost_price']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Selling Price</label>
                            <input type="number" name="price" min="0" step="0.01" class="form-control" value="<?= htmlspecialchars($medicine['price']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Min Stock</label>
                            <input type="number" name="min_stock" min="0" class="form-control" value="<?= htmlspecialchars($medicine['min_stock']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" value="<?= htmlspecialchars($medicine['expiry_date']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">Select supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['id'] ?>" <?= $medicine['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($supplier['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/pages/medicines.php" class="btn btn-outline-secondary">Back to list</a>
                            <button type="submit" class="btn btn-primary"><?= $id ? 'Update Medicine' : 'Add Medicine' ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
