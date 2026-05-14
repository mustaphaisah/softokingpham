<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$period = $_GET['period'] ?? 'daily';
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

if ($period === 'monthly') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
} elseif ($period === 'yearly') {
    $startDate = date('Y-01-01');
    $endDate = date('Y-12-31');
}

$totalRevenue = countRows('SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?', [$startDate, $endDate]);
$totalProfit = countRows('SELECT COALESCE(SUM(total_profit), 0) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?', [$startDate, $endDate]);
$totalSales = countRows('SELECT COUNT(*) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?', [$startDate, $endDate]);

$bestSelling = fetchAllRows('
    SELECT m.name, SUM(si.quantity) AS total_sold
    FROM sale_items si
    JOIN medicines m ON si.medicine_id = m.id
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY m.id
    ORDER BY total_sold DESC
    LIMIT 10
', [$startDate, $endDate]);

$salesData = fetchAllRows('SELECT DATE(created_at) AS date, SUM(total_amount) AS revenue, SUM(total_profit) AS profit FROM sales WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date ASC', [$startDate, $endDate]);
?>
<div class="row">
    <div class="col-12 mb-4">
        <h1 class="mb-2"><i class="fas fa-chart-bar me-2"></i>Sales Reports & Analytics</h1>
        <p class="text-muted mb-0">Track revenue, profit, and best-selling medicines</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-filter me-2"></i>Report Filters
    </div>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-sm-3">
                <label class="form-label"><i class="fas fa-calendar me-2"></i>Period</label>
                <select name="period" class="form-select">
                    <option value="daily" <?= $period === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="monthly" <?= $period === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $period === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label"><i class="fas fa-arrow-right me-2"></i>From</label>
                <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label"><i class="fas fa-arrow-left me-2"></i>To</label>
                <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
            </div>
            <div class="col-sm-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Generate
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row gy-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-dollar-sign text-success me-2"></i>Total Revenue</h5>
                        <h2>₦<?= number_format($totalRevenue, 2) ?></h2>
                    </div>
                    <i class="fas fa-chart-line fa-2x text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-profit text-info me-2"></i>Total Profit</h5>
                        <h2>₦<?= number_format($totalProfit, 2) ?></h2>
                    </div>
                    <i class="fas fa-chart-pie fa-2x text-info opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-shopping-cart text-primary me-2"></i>Total Sales</h5>
                        <h2><?= $totalSales ?></h2>
                    </div>
                    <i class="fas fa-receipt fa-2x text-primary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row gy-4 mt-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                Best Selling Medicines
            </div>
            <div class="card-body">
                <?php if (empty($bestSelling)): ?>
                    <div class="alert alert-info mb-0">No sales data available for the selected period.</div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($bestSelling as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($item['name']) ?>
                                <span class="badge bg-primary rounded-pill"><?= $item['total_sold'] ?> sold</span>
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
                Sales Trend
            </div>
            <div class="card-body">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const salesData = <?= json_encode($salesData) ?>;
const labels = salesData.map(d => d.date);
const revenue = salesData.map(d => parseFloat(d.revenue));
const profit = salesData.map(d => parseFloat(d.profit));

const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Revenue',
            data: revenue,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }, {
            label: 'Profit',
            data: profit,
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
