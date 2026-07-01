<?php
// transactions.php
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Initialize variables for alerts
$alert_type = '';
$alert_message = '';

// Handle POST actions for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $amount = str_replace(['Rp', '.', ',', ' '], '', $_POST['amount'] ?? '');
        $transaction_date = $_POST['transaction_date'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        $transaction_id = $_POST['transaction_id'] ?? null;
        
        $today = date('Y-m-d');
        
        // Validation
        if (empty($title) || empty($category_id) || empty($amount) || empty($transaction_date)) {
            $alert_type = 'danger';
            $alert_message = 'Semua field yang bertanda * wajib diisi.';
        } elseif (!is_numeric($amount) || $amount <= 0) {
            $alert_type = 'danger';
            $alert_message = 'Jumlah (Amount) harus berupa angka yang lebih dari 0.';
        } elseif ($transaction_date > $today) {
            $alert_type = 'danger';
            $alert_message = 'Tanggal transaksi tidak boleh melebihi hari ini.';
        } else {
            try {
                // Proceed with Insert or Update
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO transactions (user_id, category_id, title, amount, transaction_date, notes) VALUES (:user_id, :category_id, :title, :amount, :transaction_date, :notes)");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':category_id', $category_id);
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':amount', $amount);
                    $stmt->bindParam(':transaction_date', $transaction_date);
                    $stmt->bindParam(':notes', $notes);
                    $stmt->execute();
                    $alert_type = 'success';
                    $alert_message = 'Transaksi berhasil ditambahkan.';
                } elseif ($action === 'edit' && $transaction_id) {
                    $stmt = $conn->prepare("UPDATE transactions SET category_id = :category_id, title = :title, amount = :amount, transaction_date = :transaction_date, notes = :notes WHERE id = :id AND user_id = :user_id");
                    $stmt->bindParam(':category_id', $category_id);
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':amount', $amount);
                    $stmt->bindParam(':transaction_date', $transaction_date);
                    $stmt->bindParam(':notes', $notes);
                    $stmt->bindParam(':id', $transaction_id);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $alert_type = 'success';
                    $alert_message = 'Transaksi berhasil diperbarui.';
                }
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_message = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $transaction_id = $_POST['transaction_id'] ?? null;
        if ($transaction_id) {
            try {
                $stmt = $conn->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':id', $transaction_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $alert_type = 'success';
                $alert_message = 'Transaksi berhasil dihapus.';
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_message = 'Gagal menghapus transaksi: ' . $e->getMessage();
            }
        }
    }
}

// Fetch user's categories for the dropdowns
try {
    $cat_stmt = $conn->prepare("SELECT id, name, type FROM categories WHERE user_id = :user_id ORDER BY name ASC");
    $cat_stmt->bindParam(':user_id', $user_id);
    $cat_stmt->execute();
    $all_categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Search, Filter and Pagination Logic
$search = $_GET['search'] ?? '';
$filter_month = $_GET['month'] ?? ''; // Format: YYYY-MM
$filter_category = $_GET['category_id'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Transactions per page
$offset = ($page - 1) * $limit;

try {
    // Build query with search and filters
    // We join with categories to display the category name and type
    $query = "SELECT t.*, c.name as category_name, c.type as category_type 
              FROM transactions t 
              JOIN categories c ON t.category_id = c.id 
              WHERE t.user_id = :user_id";
    
    $params = [':user_id' => $user_id];

    if (!empty($search)) {
        $query .= " AND (t.title LIKE :search OR t.notes LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($filter_month)) {
        $query .= " AND DATE_FORMAT(t.transaction_date, '%Y-%m') = :filter_month";
        $params[':filter_month'] = $filter_month;
    }

    if (!empty($filter_category)) {
        $query .= " AND t.category_id = :filter_category";
        $params[':filter_category'] = $filter_category;
    }
    
    // Get total rows for pagination
    $count_stmt = $conn->prepare($query);
    foreach ($params as $key => $val) {
        $count_stmt->bindValue($key, $val);
    }
    $count_stmt->execute();
    $total_rows = $count_stmt->rowCount();
    $total_pages = ceil($total_rows / $limit);
    
    // Add Order and Limit for actual data fetching
    $query .= " ORDER BY t.transaction_date DESC, t.id DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    // PDO requires explicit integer binding for LIMIT/OFFSET
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container-fluid px-0">
    <div class="row mb-4 align-items-center">
        <div class="col-12 col-md-6">
            <h3 class="fw-bold text-dark mb-0">Catatan Transaksi</h3>
            <p class="text-secondary mb-0 mt-1">Kelola seluruh riwayat pemasukan dan pengeluaran Anda.</p>
        </div>
        <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Transaksi
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($alert_message)): ?>
        <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <?php if ($alert_type === 'success'): ?>
                <i class="bi bi-check-circle-fill me-2"></i>
            <?php else: ?>
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php endif; ?>
            <?= htmlspecialchars($alert_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            
            <!-- Filters & Search Bar -->
            <form action="transactions.php" method="GET" class="mb-4 bg-light p-3 rounded-4 border">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label text-secondary small fw-medium mb-1">Cari Kata Kunci</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0 bg-white" placeholder="Cari judul/catatan..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label text-secondary small fw-medium mb-1">Filter Bulan</label>
                        <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($filter_month) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label text-secondary small fw-medium mb-1">Filter Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $filter_category == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                    </div>
                </div>
                <?php if (!empty($search) || !empty($filter_month) || !empty($filter_category)): ?>
                    <div class="row mt-2">
                        <div class="col-12 text-end">
                            <a href="transactions.php" class="text-decoration-none small text-danger"><i class="bi bi-x-circle me-1"></i>Reset Filter</a>
                        </div>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Transactions Table -->
            <div class="table-responsive mb-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 rounded-start px-4">Tanggal</th>
                            <th class="border-0">Judul</th>
                            <th class="border-0">Kategori</th>
                            <th class="border-0 text-end">Jumlah</th>
                            <th class="border-0 text-end rounded-end px-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td class="px-4 text-secondary">
                                        <?= date('d M Y', strtotime($txn['transaction_date'])) ?>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark"><?= htmlspecialchars($txn['title']) ?></div>
                                        <?php if (!empty($txn['notes'])): ?>
                                            <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;"><?= htmlspecialchars($txn['notes']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($txn['category_type'] === 'pemasukan'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill"><?= htmlspecialchars($txn['category_name']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 rounded-pill"><?= htmlspecialchars($txn['category_name']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold <?= $txn['category_type'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                        <?= $txn['category_type'] === 'pemasukan' ? '+' : '-' ?> Rp <?= number_format($txn['amount'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-end px-4">
                                        <button class="btn btn-sm btn-light border text-primary me-2 edit-btn" 
                                            data-id="<?= $txn['id'] ?>" 
                                            data-title="<?= htmlspecialchars($txn['title']) ?>" 
                                            data-category="<?= $txn['category_id'] ?>"
                                            data-amount="<?= $txn['amount'] ?>"
                                            data-date="<?= $txn['transaction_date'] ?>"
                                            data-notes="<?= htmlspecialchars($txn['notes']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#editTransactionModal">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border text-danger delete-btn"
                                            data-id="<?= $txn['id'] ?>" 
                                            data-title="<?= htmlspecialchars($txn['title']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteTransactionModal">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-secondary">
                                    <i class="bi bi-receipt fs-1 d-block mb-3 text-muted opacity-50"></i>
                                    Belum ada transaksi yang ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0 mt-4">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&month=<?= urlencode($filter_month) ?>&category_id=<?= urlencode($filter_category) ?>">Sebelumnya</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&month=<?= urlencode($filter_month) ?>&category_id=<?= urlencode($filter_category) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&month=<?= urlencode($filter_month) ?>&category_id=<?= urlencode($filter_category) ?>">Selanjutnya</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal Tambah Transaksi -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="addTransactionModalLabel">Tambah Transaksi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="transactions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="add_title" class="form-label fw-medium text-secondary">Judul Transaksi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_title" name="title" required placeholder="Contoh: Beli Makan Siang">
                    </div>
                    <div class="mb-3">
                        <label for="add_category" class="form-label fw-medium text-secondary">Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" id="add_category" name="category_id" required>
                            <option value="">Pilih Kategori...</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add_amount" class="form-label fw-medium text-secondary">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_amount" name="amount" required min="1" step="1" placeholder="50000">
                    </div>
                    <div class="mb-3">
                        <label for="add_date" class="form-label fw-medium text-secondary">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="add_date" name="transaction_date" required max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="add_notes" class="form-label fw-medium text-secondary">Catatan (Opsional)</label>
                        <textarea class="form-control" id="add_notes" name="notes" rows="2" placeholder="Tambahkan catatan jika perlu..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Transaksi -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="editTransactionModalLabel">Edit Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="transactions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="transaction_id" id="edit_transaction_id">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label fw-medium text-secondary">Judul Transaksi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category" class="form-label fw-medium text-secondary">Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_category" name="category_id" required>
                            <option value="">Pilih Kategori...</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_amount" class="form-label fw-medium text-secondary">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_amount" name="amount" required min="1" step="1">
                    </div>
                    <div class="mb-3">
                        <label for="edit_date" class="form-label fw-medium text-secondary">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_date" name="transaction_date" required max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label fw-medium text-secondary">Catatan (Opsional)</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Transaksi -->
<div class="modal fade" id="deleteTransactionModal" tabindex="-1" aria-labelledby="deleteTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteTransactionModalLabel">Hapus Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="transactions.php" method="POST">
                <div class="modal-body pb-2">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="transaction_id" id="delete_transaction_id">
                    <p>Apakah Anda yakin ingin menghapus transaksi <strong id="delete_transaction_title" class="text-dark"></strong>?</p>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript to populate Modals -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Populate Edit Modal
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_transaction_id').value = this.getAttribute('data-id');
            document.getElementById('edit_title').value = this.getAttribute('data-title');
            document.getElementById('edit_category').value = this.getAttribute('data-category');
            document.getElementById('edit_amount').value = Math.floor(this.getAttribute('data-amount')); // Ensure integer format for the number input
            document.getElementById('edit_date').value = this.getAttribute('data-date');
            document.getElementById('edit_notes').value = this.getAttribute('data-notes');
        });
    });

    // Populate Delete Modal
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('delete_transaction_id').value = this.getAttribute('data-id');
            document.getElementById('delete_transaction_title').textContent = this.getAttribute('data-title');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
