<?php
require_once __DIR__ . '/../includes/header.php';

$saleId = intval($_GET['id'] ?? 0);
$sale = fetchRow('SELECT * FROM sales WHERE id = ?', [$saleId]);
if (!$sale) {
    flash('success', 'Receipt not found.');
    redirect('/pages/dashboard.php');
}

$saleItems = fetchAllRows('SELECT si.*, m.name FROM sale_items si JOIN medicines m ON si.medicine_id = m.id WHERE si.sale_id = ?', [$saleId]);
?>
<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>Receipt</h1>
            <p class="text-muted">Sale #<?= $sale['id'] ?> completed on <?= htmlspecialchars($sale['created_at']) ?>.</p>
        </div>
        <div>
            <button class="btn btn-secondary" onclick="window.print()">Print Receipt</button>
            <a href="<?= BASE_URL ?>/pages/sales.php" class="btn btn-primary">New Sale</a>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="mb-4">
            <p><strong>Payment method:</strong> <?= htmlspecialchars($sale['payment_method']) ?></p>
            <p><strong>Total paid:</strong> ₦<?= number_format($sale['total_amount'], 2) ?></p>
            <p><strong>Total profit:</strong> ₦<?= number_format($sale['total_profit'], 2) ?></p>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($saleItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>₦<?= number_format($item['price'], 2) ?></td>
                        <td>₦<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
