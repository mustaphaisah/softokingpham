<?php
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    requireAdmin();
    $deleteId = intval($_GET['delete']);
    $stmt = $pdo->prepare('DELETE FROM medicines WHERE id = ?');
    $stmt->execute([$deleteId]);
    flash('success', 'Medicine deleted successfully.');
    redirect('/pages/medicines.php');
}

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$whereClause = 'WHERE 1=1';
$params = [];
if ($search !== '') {
    $whereClause .= ' AND (name LIKE ? OR category LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($category !== '') {
    $whereClause .= ' AND category = ?';
    $params[] = $category;
}
$medicines = fetchAllRows("SELECT m.*, s.name AS supplier_name FROM medicines m LEFT JOIN suppliers s ON m.supplier_id = s.id $whereClause ORDER BY expiry_date ASC", $params);
$categories = fetchAllRows('SELECT DISTINCT category FROM medicines ORDER BY category ASC');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-2"><i class="fas fa-capsules me-2"></i>Medicines Inventory</h1>
        <p class="text-muted mb-0">Manage your medicines stock and monitor expiry dates</p>
    </div>
    <?php if ($userRole === 'admin'): ?>
        <a href="<?= BASE_URL ?>/pages/medicine_form.php" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Add Medicine
        </a>
    <?php endif; ?>
</div>

<?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-sm-5">
                <label class="form-label"><i class="fas fa-search me-2"></i>Search</label>
                <input type="search" name="search" class="form-control" placeholder="Medicine name or category" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-sm-5">
                <label class="form-label"><i class="fas fa-filter me-2"></i>Category</label>
                <select name="category" class="form-select">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Search
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <i class="fas fa-list me-2"></i>Medicines List
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
            <tr>
                <th><i class="fas fa-hashtag me-1"></i>ID</th>
                <th><i class="fas fa-pills me-1"></i>Name</th>
                <th><i class="fas fa-tag me-1"></i>Category</th>
                <th><i class="fas fa-boxes me-1"></i>Qty</th>
                <th><i class="fas fa-money-bill me-1"></i>Cost</th>
                <th><i class="fas fa-price-tag me-1"></i>Price</th>
                <th><i class="fas fa-calendar me-1"></i>Expiry</th>
                <th><i class="fas fa-minus me-1"></i>Min Stock</th>
                <th><i class="fas fa-truck me-1"></i>Supplier</th>
                <th><i class="fas fa-info-circle me-1"></i>Status</th>
                <?php if ($userRole === 'admin'): ?>
                    <th><i class="fas fa-cogs me-1"></i>Actions</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($medicines)): ?>
                <tr><td colspan="<?= $userRole === 'admin' ? '11' : '10' ?>" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x opacity-25 mb-2 d-block"></i>
                    No medicines found.
                </td></tr>
            <?php else: ?>
                <?php foreach ($medicines as $medicine): ?>
                    <?php
                    $expired = strtotime($medicine['expiry_date']) < time();
                    $lowStock = $medicine['quantity'] <= $medicine['min_stock'];
                    $daysToExpiry = calculateDaysToExpiry($medicine['expiry_date']);
                    $expiringSoon = $daysToExpiry >= 0 && $daysToExpiry <= 30;
                    ?>
                    <tr class="<?= $expired ? 'table-danger' : ($expiringSoon ? 'table-warning' : '') ?>">
                        <td><strong><?= $medicine['id'] ?></strong></td>
                        <td><strong><?= htmlspecialchars($medicine['name']) ?></strong></td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($medicine['category']) ?></span></td>
                        <td><?= $medicine['quantity'] ?></td>
                        <td>₦<?= number_format($medicine['cost_price'], 2) ?></td>
                        <td>₦<?= number_format($medicine['price'], 2) ?></td>
                        <td>
                            <small><?= htmlspecialchars($medicine['expiry_date']) ?></small>
                            <?php if ($daysToExpiry >= 0): ?>
                                <br><small class="text-muted">(<?= $daysToExpiry ?> days)</small>
                            <?php endif; ?>
                        </td>
                        <td><?= $medicine['min_stock'] ?></td>
                        <td><?= htmlspecialchars($medicine['supplier_name'] ?? 'N/A') ?></td>
                        <td>
                            <?php if ($expired): ?>
                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Expired</span>
                            <?php elseif ($expiringSoon): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-exclamation me-1"></i>Expiring</span>
                            <?php elseif ($lowStock): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-arrow-down me-1"></i>Low</span>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>OK</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($userRole === 'admin'): ?>
                            <td>
                                <a href="<?= BASE_URL ?>/pages/medicine_form.php?id=<?= $medicine['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/pages/medicines.php?delete=<?= $medicine['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this medicine?');" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
