<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = flash('success');
$medicines = fetchAllRows('SELECT * FROM medicines ORDER BY name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartData = trim($_POST['cart_data'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? '');

    if ($cartData === '') {
        $error = 'Please add at least one item to the cart.';
    } elseif ($paymentMethod === '') {
        $error = 'Please select a payment method.';
    } else {
        $cartItems = json_decode($cartData, true);
        if (!is_array($cartItems) || empty($cartItems)) {
            $error = 'Cart data is invalid. Please refresh the page and try again.';
        } else {
            try {
                $pdo->beginTransaction();
                $totalAmount = 0.0;
                $totalProfit = 0.0;
                $stmtSale = $pdo->prepare('INSERT INTO sales (total_amount, total_profit, payment_method) VALUES (?, ?, ?)');
                $stmtSale->execute([0, 0, $paymentMethod]);
                $saleId = $pdo->lastInsertId();

                $stmtItem = $pdo->prepare('INSERT INTO sale_items (sale_id, medicine_id, quantity, cost_price, price) VALUES (?, ?, ?, ?, ?)');
                $stmtStock = $pdo->prepare('SELECT quantity, cost_price, price, name FROM medicines WHERE id = ? FOR UPDATE');
                $stmtUpdate = $pdo->prepare('UPDATE medicines SET quantity = quantity - ? WHERE id = ?');
                $stmtHistory = $pdo->prepare('INSERT INTO stock_history (medicine_id, change_type, quantity) VALUES (?, ?, ?)');

                foreach ($cartItems as $item) {
                    $medicineId = intval($item['id'] ?? 0);
                    $quantity = intval($item['quantity'] ?? 0);
                    if ($medicineId <= 0 || $quantity <= 0) {
                        throw new Exception('Invalid cart item detected.');
                    }

                    $stmtStock->execute([$medicineId]);
                    $medicine = $stmtStock->fetch();
                    if (!$medicine) {
                        throw new Exception('Medicine not found in the cart.');
                    }
                    if ($quantity > $medicine['quantity']) {
                        throw new Exception('Insufficient stock for ' . $medicine['name'] . '.');
                    }

                    $linePrice = floatval($medicine['price']);
                    $lineCost = floatval($medicine['cost_price']);
                    $lineTotal = $linePrice * $quantity;
                    $lineProfit = ($linePrice - $lineCost) * $quantity;
                    $totalAmount += $lineTotal;
                    $totalProfit += $lineProfit;

                    $stmtItem->execute([$saleId, $medicineId, $quantity, $lineCost, $linePrice]);
                    $stmtUpdate->execute([$quantity, $medicineId]);
                    $stmtHistory->execute([$medicineId, 'OUT', -$quantity]);
                }

                $stmtUpdateSale = $pdo->prepare('UPDATE sales SET total_amount = ?, total_profit = ? WHERE id = ?');
                $stmtUpdateSale->execute([$totalAmount, $totalProfit, $saleId]);
                $pdo->commit();

                redirect('/pages/receipt.php?id=' . $saleId);
            } catch (Exception $exception) {
                $pdo->rollBack();
                $error = $exception->getMessage();
            }
        }
    }
}
?>
<div class="row">
    <div class="col-12 mb-4">
        <h1 class="mb-2"><i class="fas fa-shopping-cart me-2"></i>Point of Sale (POS)</h1>
        <p class="text-muted mb-0">Select medicines, manage cart, and process payments</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-plus-circle me-2"></i>Select Medicine
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-5">
                <label class="form-label"><i class="fas fa-pills me-2"></i>Medicine</label>
                <select id="medicineSelect" class="form-select">
                    <option value="">Choose a medicine</option>
                    <?php foreach ($medicines as $medicine): ?>
                        <option value="<?= $medicine['id'] ?>" data-name="<?= htmlspecialchars($medicine['name']) ?>" data-price="<?= $medicine['price'] ?>" data-stock="<?= $medicine['quantity'] ?>">
                            <?= htmlspecialchars($medicine['name']) ?> (<?= htmlspecialchars($medicine['category']) ?>) - ₦<?= number_format($medicine['price'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i>Quantity</label>
                <input type="number" id="medicineQty" class="form-control" min="1" value="1">
            </div>
            <div class="col-lg-4 d-flex align-items-end">
                <button type="button" id="addToCartBtn" class="btn btn-primary w-100">
                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row gy-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Shopping Cart
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0" id="cartTable">
                        <thead>
                        <tr>
                            <th><i class="fas fa-pills me-1"></i>Medicine</th>
                            <th><i class="fas fa-sort-numeric-up me-1"></i>Qty</th>
                            <th><i class="fas fa-money-bill me-1"></i>Unit Price</th>
                            <th><i class="fas fa-dollar-sign me-1"></i>Amount</th>
                            <th><i class="fas fa-trash me-1"></i>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr><td colspan="5" class="text-center text-muted py-4">Cart is empty.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-receipt me-2"></i>Cart Summary
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><strong>Items in Cart</strong></span>
                    <span class="badge bg-primary" id="cartCount">0</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="h5 mb-0"><strong>Total Amount</strong></span>
                    <span class="h4 mb-0 text-success" id="cartTotal">$0.00</span>
                </div>
            </div>
        </div>

        <form method="post" onsubmit="return prepareCartForm();" class="mt-4">
            <input type="hidden" name="cart_data" id="cartData">
            
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <i class="fas fa-credit-card me-2"></i>Payment Method
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="Cash" required>
                        <label class="form-check-label" for="payment_cash">
                            <i class="fas fa-money-bill me-2"></i>Cash
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="payment_transfer" value="Transfer" required>
                        <label class="form-check-label" for="payment_transfer">
                            <i class="fas fa-bank me-2"></i>Transfer
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="payment_pos" value="POS" required>
                        <label class="form-check-label" for="payment_pos">
                            <i class="fas fa-credit-card me-2"></i>POS Card
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success w-100 btn-lg">
                <i class="fas fa-check-circle me-2"></i>Complete Payment
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
