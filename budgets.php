<?php
// budgets.php
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Initialize variables for alerts
$alert_type = '';
$alert_message = '';

// Handle POST actions for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $month = $_POST['month'] ?? ''; // Format: YYYY-MM
        $amount = str_replace(['Rp', '.', ',', ' '], '', $_POST['amount'] ?? '');
        $budget_id = $_POST['budget_id'] ?? null;
        
        // Validation
        if (empty($month) || empty($amount)) {
            $alert_type = 'danger';
            $alert_message = 'Bulan dan Jumlah Budget wajib diisi.';
        } elseif (!is_numeric($amount) || $amount <= 0) {
            $alert_type = 'danger';
            $alert_message = 'Jumlah (Amount) harus berupa angka yang lebih dari 0.';
        } else {
            try {
                // Check if a budget already exists for this month and user
                if ($action === 'add') {
                    $check_stmt = $conn->prepare("SELECT id FROM budgets WHERE user_id = :user_id AND month = :month");
                } else {
                    $check_stmt = $conn->prepare("SELECT id FROM budgets WHERE user_id = :user_id AND month = :month AND id != :id");
                    $check_stmt->bindParam(':id', $budget_id);
                }
                
                $check_stmt->bindParam(':user_id', $user_id);
                $check_stmt->bindParam(':month', $month);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $alert_type = 'danger';
                    $alert_message = 'Budget untuk bulan tersebut sudah ada. Hanya boleh satu budget per bulan.';
                } else {
                    // Proceed with Insert or Update
                    if ($action === 'add') {
                        $stmt = $conn->prepare("INSERT INTO budgets (user_id, month, amount) VALUES (:user_id, :month, :amount)");
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':month', $month);
                        $stmt->bindParam(':amount', $amount);
                        $stmt->execute();
                        $alert_type = 'success';
                        $alert_message = 'Budget berhasil ditambahkan.';
                    } elseif ($action === 'edit' && $budget_id) {
                        $stmt = $conn->prepare("UPDATE budgets SET month = :month, amount = :amount WHERE id = :id AND user_id = :user_id");
                        $stmt->bindParam(':month', $month);
                        $stmt->bindParam(':amount', $amount);
                        $stmt->bindParam(':id', $budget_id);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        $alert_type = 'success';
                        $alert_message = 'Budget berhasil diperbarui.';
                    }
                }
            } catch (PDOException $e) {
                // Catch unique constraint violation just in case
                if ($e->getCode() == 23000) {
                    $alert_type = 'danger';
                    $alert_message = 'Budget untuk bulan tersebut sudah ada.';
                } else {
                    $alert_type = 'danger';
                    $alert_message = 'Terjadi kesalahan sistem: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'delete') {
        $budget_id = $_POST['budget_id'] ?? null;
        if ($budget_id) {
            try {
                $stmt = $conn->prepare("DELETE FROM budgets WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':id', $budget_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $alert_type = 'success';
                $alert_message = 'Budget berhasil dihapus.';
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_message = 'Gagal menghapus budget: ' . $e->getMessage();
            }
        }
    }
}

// Pagination Logic
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Budgets per page
$offset = ($page - 1) * $limit;

try {
    $query = "SELECT * FROM budgets WHERE user_id = :user_id";
    
    // Get total rows for pagination
    $count_stmt = $conn->prepare($query);
    $count_stmt->bindParam(':user_id', $user_id);
    $count_stmt->execute();
    $total_rows = $count_stmt->rowCount();
    $total_pages = ceil($total_rows / $limit);
    
    // Add Order and Limit for actual data fetching (order by month descending)
    $query .= " ORDER BY month DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $budgets = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container-fluid px-0">
    <div class="row mb-4 align-items-center">
        <div class="col-12 col-md-6">
            <h3 class="fw-bold text-dark mb-0">Budget Bulanan</h3>
            <p class="text-secondary mb-0 mt-1">Atur target pengeluaran Anda untuk setiap bulannya.</p>
        </div>
        <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                <i class="bi bi-plus-lg me-1"></i> Buat Target Budget
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
            
            <!-- Budgets Table -->
            <div class="table-responsive mb-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 rounded-start px-4">Bulan</th>
                            <th class="border-0">Total Target Budget</th>
                            <th class="border-0 text-end rounded-end px-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($budgets) > 0): ?>
                            <?php foreach ($budgets as $budget): ?>
                                <tr>
                                    <td class="px-4 fw-medium text-dark">
                                        <!-- Convert YYYY-MM to readable format -->
                                        <?= date('F Y', strtotime($budget['month'] . '-01')) ?>
                                    </td>
                                    <td class="fw-bold text-primary">
                                        Rp <?= number_format($budget['amount'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-end px-4">
                                        <button class="btn btn-sm btn-light border text-primary me-2 edit-btn" 
                                            data-id="<?= $budget['id'] ?>" 
                                            data-month="<?= $budget['month'] ?>" 
                                            data-amount="<?= $budget['amount'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editBudgetModal">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border text-danger delete-btn"
                                            data-id="<?= $budget['id'] ?>" 
                                            data-month="<?= date('F Y', strtotime($budget['month'] . '-01')) ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteBudgetModal">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-secondary">
                                    <i class="bi bi-pie-chart fs-1 d-block mb-3 text-muted opacity-50"></i>
                                    Belum ada history budget yang ditemukan.
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
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Sebelumnya</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Selanjutnya</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal Tambah Budget -->
<div class="modal fade" id="addBudgetModal" tabindex="-1" aria-labelledby="addBudgetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="addBudgetModalLabel">Set Target Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="budgets.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="add_month" class="form-label fw-medium text-secondary">Bulan <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="add_month" name="month" required value="<?= date('Y-m') ?>">
                        <div class="form-text">Pilih bulan dan tahun target budget.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_amount" class="form-label fw-medium text-secondary">Total Budget (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_amount" name="amount" required min="1" step="1" placeholder="Misal: 5000000">
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

<!-- Modal Edit Budget -->
<div class="modal fade" id="editBudgetModal" tabindex="-1" aria-labelledby="editBudgetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="editBudgetModalLabel">Edit Target Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="budgets.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="budget_id" id="edit_budget_id">
                    <div class="mb-3">
                        <label for="edit_month" class="form-label fw-medium text-secondary">Bulan <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="edit_month" name="month" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_amount" class="form-label fw-medium text-secondary">Total Budget (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_amount" name="amount" required min="1" step="1">
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

<!-- Modal Hapus Budget -->
<div class="modal fade" id="deleteBudgetModal" tabindex="-1" aria-labelledby="deleteBudgetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteBudgetModalLabel">Hapus Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="budgets.php" method="POST">
                <div class="modal-body pb-2">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="budget_id" id="delete_budget_id">
                    <p>Apakah Anda yakin ingin menghapus budget untuk bulan <strong id="delete_budget_month" class="text-dark"></strong>?</p>
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
            document.getElementById('edit_budget_id').value = this.getAttribute('data-id');
            document.getElementById('edit_month').value = this.getAttribute('data-month');
            document.getElementById('edit_amount').value = Math.floor(this.getAttribute('data-amount')); // Keep integer format
        });
    });

    // Populate Delete Modal
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('delete_budget_id').value = this.getAttribute('data-id');
            document.getElementById('delete_budget_month').textContent = this.getAttribute('data-month');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
