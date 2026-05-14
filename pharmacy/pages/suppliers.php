<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = ?');
    $stmt->execute([$deleteId]);
    flash('success', 'Supplier deleted successfully.');
    redirect('/pages/suppliers.php');
}

$suppliers = fetchAllRows('SELECT * FROM suppliers ORDER BY name ASC');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1>Suppliers</h1>
        <p class="text-muted">Manage suppliers for medicines.</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/supplier_form.php" class="btn btn-success">Add Supplier</a>
</div>

<?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($suppliers)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No suppliers found.</td></tr>
            <?php else: ?>
                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><?= $supplier['id'] ?></td>
                        <td><?= htmlspecialchars($supplier['name']) ?></td>
                        <td><?= htmlspecialchars($supplier['contact']) ?></td>
                        <td><?= htmlspecialchars($supplier['created_at']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/pages/supplier_form.php?id=<?= $supplier['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="<?= BASE_URL ?>/pages/suppliers.php?delete=<?= $supplier['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this supplier?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
