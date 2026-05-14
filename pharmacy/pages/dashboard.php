<?php
require_once __DIR__ . '/../includes/header.php';

$totalMedicines = countRows('SELECT COUNT(*) FROM medicines');
$availableStock = countRows('SELECT COALESCE(SUM(quantity), 0) FROM medicines');
$lowStockCount = countRows('SELECT COUNT(*) FROM medicines WHERE quantity <= min_stock');
$expiringSoonCount = countRows('SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)');
$recentSales = fetchAllRows('SELECT * FROM sales ORDER BY created_at DESC LIMIT 5');
$lowStockItems = fetchAllRows('SELECT * FROM medicines WHERE quantity <= min_stock ORDER BY quantity ASC');
$expiringItems = fetchAllRows('SELECT * FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY expiry_date ASC');
$dailyRevenue = getDailyRevenue();
$dailyProfit = getDailyProfit();
$totalSalesToday = getTotalSalesToday();
$notifications = [];
if ($lowStockCount > 0) $notifications[] = "Low stock: $lowStockCount items";
if ($expiringSoonCount > 0) $notifications[] = "Expiring soon: $expiringSoonCount items";
?>
<div class="row gy-4">
    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
                <p class="text-muted mb-0">Welcome back, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'User') ?></strong> <span class="badge bg-primary ms-2"><?= ucfirst($userRole) ?></span></p>
            </div>
            <div class="text-end">
                <p class="text-muted small mb-0"><?= date('l, F d, Y') ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($notifications)): ?>
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-bell me-2"></i><strong>Alerts:</strong> <?= implode(' • ', $notifications) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-md-3 col-sm-6">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-cubes text-primary me-2"></i>Total Medicines</h5>
                        <h2><?= $totalMedicines ?></h2>
                    </div>
                    <i class="fas fa-capsules fa-2x text-primary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card shadow-sm border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-boxes text-success me-2"></i>Available Stock</h5>
                        <h2><?= $availableStock ?></h2>
                    </div>
                    <i class="fas fa-layer-group fa-2x text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card shadow-sm border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-exclamation-triangle text-warning me-2"></i>Low Stock</h5>
                        <h2><?= $lowStockCount ?></h2>
                    </div>
                    <i class="fas fa-chart-line fa-2x text-warning opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card shadow-sm border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-hourglass-end text-danger me-2"></i>Expiring Soon</h5>
                        <h2><?= $expiringSoonCount ?></h2>
                    </div>
                    <i class="fas fa-clock fa-2x text-danger opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card shadow-sm border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-dollar-sign text-info me-2"></i>Today's Revenue</h5>
                        <h2>₦<?= number_format($dailyRevenue, 2) ?></h2>
                    </div>
                    <i class="fas fa-money-bill fa-2x text-info opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card shadow-sm border-start border-4 border-secondary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-profit text-secondary me-2"></i>Today's Profit</h5>
                        <h2>₦<?= number_format($dailyProfit, 2) ?></h2>
                    </div>
                    <i class="fas fa-chart-pie fa-2x text-secondary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card shadow-sm border-start border-4 border-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-receipt text-dark me-2"></i>Sales Today</h5>
                        <h2><?= $totalSalesToday ?></h2>
                    </div>
                    <i class="fas fa-shopping-bag fa-2x text-dark opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row gy-4 mt-2">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-exclamation-circle me-2"></i>Low Stock Alerts
            </div>
            <div class="card-body">
                <?php if (empty($lowStockItems)): ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>No medicine is currently below minimum stock.
                    </div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($lowStockItems as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    <br><small class="text-muted">Suggest: <?= getRestockSuggestion($item['id']) ?> units</small>
                                </div>
                                <span class="badge bg-warning text-dark">Qty: <?= $item['quantity'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-calendar-times me-2"></i>Expiring Soon
            </div>
            <div class="card-body">
                <?php if (empty($expiringItems)): ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>No medicines are expiring within 30 days.
                    </div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($expiringItems as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($item['expiry_date']) ?></small>
                                </div>
                                <span class="badge bg-danger">In: <?= calculateDaysToExpiry($item['expiry_date']) ?> days</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row gy-4 mt-2">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-history me-2"></i>Recent Sales
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentSales)): ?>
                    <div class="p-4 text-muted text-center">
                        <i class="fas fa-inbox fa-3x opacity-25 mb-3 d-block"></i>
                        No sales records available yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                            <tr>
                                <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                <th><i class="fas fa-money-bill me-1"></i>Total</th>
                                <th><i class="fas fa-chart-line me-1"></i>Profit</th>
                                <th><i class="fas fa-credit-card me-1"></i>Payment</th>
                                <th><i class="fas fa-clock me-1"></i>Date</th>
                                <th><i class="fas fa-file me-1"></i>Receipt</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td><strong>#<?= $sale['id'] ?></strong></td>
                                    <td><span class="badge bg-success">₦<?= number_format($sale['total_amount'], 2) ?></span></td>
                                    <td><span class="badge bg-info">₦<?= number_format($sale['total_profit'], 2) ?></span></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($sale['payment_method']) ?></span></td>
                                    <td><small><?= htmlspecialchars($sale['created_at']) ?></small></td>
                                    <td><a href="<?= BASE_URL ?>/pages/receipt.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i>View</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
