<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$error = '';
$success = flash('success');
$editId = intval($_GET['edit_id'] ?? 0);
$editingUser = null;

if ($editId > 0) {
    $editingUser = fetchRow('SELECT id, username, role FROM users WHERE id = ?', [$editId]);
    if (!$editingUser || $editingUser['role'] === 'admin') {
        $editingUser = null;
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Username and password are required to create a cashier.';
        } elseif (countRows('SELECT COUNT(*) FROM users WHERE username = ?', [$username]) > 0) {
            $error = 'That username is already taken. Please choose another one.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
            $stmt->execute([$username, $passwordHash, 'cashier']);
            flash('success', 'Cashier account added successfully.');
            redirect('/pages/users.php');
        }
    }

    if ($action === 'edit_user') {
        $userId = intval($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $user = fetchRow('SELECT id, role FROM users WHERE id = ?', [$userId]);

        if (!$user) {
            $error = 'User not found.';
        } elseif ($user['role'] === 'admin') {
            $error = 'Admin accounts cannot be edited from this page.';
        } elseif ($username === '') {
            $error = 'Username is required.';
        } elseif (countRows('SELECT COUNT(*) FROM users WHERE username = ? AND id != ?', [$username, $userId]) > 0) {
            $error = 'That username is already taken. Please choose another one.';
        } else {
            if ($password !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ? WHERE id = ?');
                $stmt->execute([$username, $passwordHash, $userId]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
                $stmt->execute([$username, $userId]);
            }
            flash('success', 'Cashier profile updated successfully.');
            redirect('/pages/users.php');
        }

        $editingUser = ['id' => $userId, 'username' => $username];
        $editId = $userId;
    }

    if ($action === 'delete_user') {
        $userId = intval($_POST['user_id'] ?? 0);
        $user = fetchRow('SELECT id, role FROM users WHERE id = ?', [$userId]);

        if (!$user) {
            $error = 'User not found.';
        } elseif ($user['role'] === 'admin') {
            $error = 'Admin accounts cannot be removed from this page.';
        } elseif ($user['id'] === ($_SESSION['admin_id'] ?? 0)) {
            $error = 'You cannot remove your own account while logged in.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            flash('success', 'Cashier account removed successfully.');
            redirect('/pages/users.php');
        }
    }
}

$cashiers = fetchAllRows('SELECT id, username, role, created_at FROM users ORDER BY created_at DESC');
$totalCashiers = countRows('SELECT COUNT(*) FROM users WHERE role = ?', ['cashier']);
$totalAdmins = countRows('SELECT COUNT(*) FROM users WHERE role = ?', ['admin']);
?>

<div class="row">
    <div class="col-12 mb-4">
        <h1 class="mb-2"><i class="fas fa-users-cog me-2"></i>User </h1>
        <p class="text-muted mb-0">Admin can add and remove cashier accounts from here.</p>
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

<div class="row gy-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-user-friends text-primary me-2"></i>Cashiers</h5>
                        <h2><?= $totalCashiers ?></h2>
                    </div>
                    <i class="fas fa-users fa-2x text-primary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><i class="fas fa-user-shield text-info me-2"></i>Admins</h5>
                        <h2><?= $totalAdmins ?></h2>
                    </div>
                    <i class="fas fa-user-lock fa-2x text-info opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row gy-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-user-plus me-2"></i><?= $editingUser ? 'Edit Cashier' : 'Add Cashier' ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php if ($editingUser): ?>
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" value="<?= $editingUser['id'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add_user">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($editingUser['username'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password"><?= $editingUser ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?= $editingUser ? '' : 'required' ?> >
                    </div>
                    <button type="submit" class="btn <?= $editingUser ? 'btn-success' : 'btn-primary' ?> w-100">
                        <i class="fas <?= $editingUser ? 'fa-save' : 'fa-plus-circle' ?> me-2"></i><?= $editingUser ? 'Update Cashier' : 'Add Cashier' ?>
                    </button>
                    <?php if ($editingUser): ?>
                        <a href="<?= BASE_URL ?>/pages/users.php" class="btn btn-outline-secondary w-100 mt-3">
                            <i class="fas fa-times me-2"></i>Cancel Edit
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-user-edit me-2"></i>Existing Users
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cashiers as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td>
                                    <span class="badge <?= $user['role'] === 'admin' ? 'bg-info' : 'bg-primary' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td class="text-end">
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <a href="<?= BASE_URL ?>/pages/users.php?edit_id=<?= $user['id'] ?>" class="btn btn-outline-primary btn-sm me-2">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this cashier account?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';
