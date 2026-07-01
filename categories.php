<?php
// categories.php
require_once 'includes/header.php'; // This also requires db and enforces login

$user_id = $_SESSION['user_id'];

// Initialize variables for alerts
$alert_type = '';
$alert_message = '';

// Handle POST actions for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'pengeluaran';
        $category_id = $_POST['category_id'] ?? null;
        
        // Validation: Category required and minimum 3 characters
        if (empty($name)) {
            $alert_type = 'danger';
            $alert_message = 'Nama kategori wajib diisi.';
        } elseif (strlen($name) < 3) {
            $alert_type = 'danger';
            $alert_message = 'Nama kategori minimal 3 karakter.';
        } else {
            // Check for duplicate category name for this user
            try {
                if ($action === 'add') {
                    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE user_id = :user_id AND name = :name");
                } else {
                    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE user_id = :user_id AND name = :name AND id != :id");
                    $check_stmt->bindParam(':id', $category_id);
                }
                
                $check_stmt->bindParam(':user_id', $user_id);
                $check_stmt->bindParam(':name', $name);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $alert_type = 'danger';
                    $alert_message = 'Kategori dengan nama tersebut sudah ada.';
                } else {
                    // Proceed with Insert or Update
                    if ($action === 'add') {
                        $stmt = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (:user_id, :name, :type)");
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':name', $name);
                        $stmt->bindParam(':type', $type);
                        $stmt->execute();
                        $alert_type = 'success';
                        $alert_message = 'Kategori berhasil ditambahkan.';
                    } elseif ($action === 'edit' && $category_id) {
                        $stmt = $conn->prepare("UPDATE categories SET name = :name, type = :type WHERE id = :id AND user_id = :user_id");
                        $stmt->bindParam(':name', $name);
                        $stmt->bindParam(':type', $type);
                        $stmt->bindParam(':id', $category_id);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        $alert_type = 'success';
                        $alert_message = 'Kategori berhasil diperbarui.';
                    }
                }
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_message = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $category_id = $_POST['category_id'] ?? null;
        if ($category_id) {
            try {
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':id', $category_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $alert_type = 'success';
                $alert_message = 'Kategori berhasil dihapus.';
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_message = 'Gagal menghapus: Kategori mungkin sedang digunakan dalam transaksi.';
            }
        }
    }
}

// Search and Pagination Logic
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5; // Categories per page
$offset = ($page - 1) * $limit;

try {
    // Build query with search
    $query = "SELECT * FROM categories WHERE user_id = :user_id";
    if (!empty($search)) {
        $query .= " AND name LIKE :search";
    }
    
    // Get total rows for pagination
    $count_stmt = $conn->prepare($query);
    $count_stmt->bindParam(':user_id', $user_id);
    if (!empty($search)) {
        $search_term = "%$search%";
        $count_stmt->bindParam(':search', $search_term);
    }
    $count_stmt->execute();
    $total_rows = $count_stmt->rowCount();
    $total_pages = ceil($total_rows / $limit);
    
    // Add Order and Limit for actual data fetching
    $query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    if (!empty($search)) {
        $stmt->bindParam(':search', $search_term);
    }
    // PDO requires explicit integer binding for LIMIT/OFFSET
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container-fluid px-0">
    <div class="row mb-4 align-items-center">
        <div class="col-12 col-md-6">
            <h3 class="fw-bold text-dark mb-0">Kelola Kategori</h3>
            <p class="text-secondary mb-0 mt-1">Atur kategori pemasukan dan pengeluaran Anda.</p>
        </div>
        <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
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
            
            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <form action="categories.php" method="GET" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0 bg-light" placeholder="Cari kategori..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="categories.php" class="btn btn-light border">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="table-responsive mb-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 rounded-start px-4">Nama Kategori</th>
                            <th class="border-0">Tipe</th>
                            <th class="border-0 text-end rounded-end px-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="px-4 fw-medium text-dark"><?= htmlspecialchars($cat['name']) ?></td>
                                    <td>
                                        <?php if ($cat['type'] === 'pemasukan'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="bi bi-arrow-down-left me-1"></i>Pemasukan</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill"><i class="bi bi-arrow-up-right me-1"></i>Pengeluaran</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end px-4">
                                        <button class="btn btn-sm btn-light border text-primary me-2 edit-btn" 
                                            data-id="<?= $cat['id'] ?>" 
                                            data-name="<?= htmlspecialchars($cat['name']) ?>" 
                                            data-type="<?= $cat['type'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border text-danger delete-btn"
                                            data-id="<?= $cat['id'] ?>" 
                                            data-name="<?= htmlspecialchars($cat['name']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteCategoryModal">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-secondary">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 text-muted opacity-50"></i>
                                    Belum ada kategori yang ditemukan.
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
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Sebelumnya</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Selanjutnya</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="addCategoryModalLabel">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="categories.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="add_name" class="form-label fw-medium text-secondary">Nama Kategori</label>
                        <input type="text" class="form-control" id="add_name" name="name" required minlength="3" placeholder="Contoh: Gaji, Makan, Listrik">
                        <div class="form-text">Minimal 3 karakter.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_type" class="form-label fw-medium text-secondary">Tipe Kategori</label>
                        <select class="form-select" id="add_type" name="type" required>
                            <option value="pengeluaran">Pengeluaran</option>
                            <option value="pemasukan">Pemasukan</option>
                        </select>
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

<!-- Modal Edit Kategori -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="editCategoryModalLabel">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="categories.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label fw-medium text-secondary">Nama Kategori</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required minlength="3">
                        <div class="form-text">Minimal 3 karakter.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_type" class="form-label fw-medium text-secondary">Tipe Kategori</label>
                        <select class="form-select" id="edit_type" name="type" required>
                            <option value="pengeluaran">Pengeluaran</option>
                            <option value="pemasukan">Pemasukan</option>
                        </select>
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

<!-- Modal Hapus Kategori -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteCategoryModalLabel">Hapus Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="categories.php" method="POST">
                <div class="modal-body pb-2">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" id="delete_category_id">
                    <p>Apakah Anda yakin ingin menghapus kategori <strong id="delete_category_name" class="text-dark"></strong>?</p>
                    <div class="alert alert-warning mb-0 border-0 bg-warning bg-opacity-10 py-2">
                        <small><i class="bi bi-info-circle me-1"></i> Data transaksi yang menggunakan kategori ini mungkin akan terpengaruh jika tidak ditangani dengan benar oleh sistem (dibatasi oleh foreign key constraint jika ada).</small>
                    </div>
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
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const type = this.getAttribute('data-type');
            
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_type').value = type;
        });
    });

    // Populate Delete Modal
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = name;
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
