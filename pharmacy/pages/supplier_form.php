<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$supplier = [
    'name' => '',
    'contact' => '',
];
$errors = [];

if ($id) {
    $supplier = fetchRow('SELECT * FROM suppliers WHERE id = ?', [$id]);
    if (!$supplier) {
        flash('success', 'Supplier not found.');
        redirect('/pages/suppliers.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier['name'] = trim($_POST['name'] ?? '');
    $supplier['contact'] = trim($_POST['contact'] ?? '');

    if ($supplier['name'] === '') {
        $errors[] = 'Supplier name is required.';
    }
    if ($supplier['contact'] === '') {
        $errors[] = 'Contact is required.';
    }

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE suppliers SET name = ?, contact = ? WHERE id = ?');
            $stmt->execute([
                $supplier['name'],
                $supplier['contact'],
                $id,
            ]);
            flash('success', 'Supplier updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO suppliers (name, contact) VALUES (?, ?)');
            $stmt->execute([
                $supplier['name'],
                $supplier['contact'],
            ]);
            flash('success', 'Supplier added successfully.');
        }

        redirect('/pages/suppliers.php');
    }
}
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="mb-0"><?= $id ? 'Edit Supplier' : 'Add Supplier' ?></h2>
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
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($supplier['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact</label>
                        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($supplier['contact']) ?>" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/pages/suppliers.php" class="btn btn-outline-secondary">Back to list</a>
                        <button type="submit" class="btn btn-primary"><?= $id ? 'Update Supplier' : 'Add Supplier' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
