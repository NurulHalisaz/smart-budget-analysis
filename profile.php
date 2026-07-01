<?php
// profile.php
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$alert_type = '';
$alert_message = '';

// Fetch current user data
try {
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Update Profile Information
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($name) || empty($email)) {
            $alert_type = 'danger';
            $alert_message = 'Nama dan Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert_type = 'danger';
            $alert_message = 'Format email tidak valid.';
        } else {
            try {
                // Check if email is used by another user
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $check_stmt->execute([':email' => $email, ':id' => $user_id]);
                
                if ($check_stmt->rowCount() > 0) {
                    $alert_type = 'danger';
                    $alert_message = 'Email tersebut sudah digunakan oleh akun lain.';
                } else {
                    $update_stmt = $conn->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
                    $update_stmt->execute([':name' => $name, ':email' => $email, ':id' => $user_id]);
                    
                    // Update session variable for header
                    $_SESSION['user_name'] = $name;
                    $user['name'] = $name;
                    $user['email'] = $email;
                    
                    $alert_type = 'success';
                    $alert_message = 'Profil berhasil diperbarui.';
                }
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_message = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
    
    // Update Password
    elseif ($action === 'update_password') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $alert_type = 'danger';
            $alert_message = 'Semua field password wajib diisi.';
        } elseif ($new_password !== $confirm_password) {
            $alert_type = 'danger';
            $alert_message = 'Password baru dan konfirmasi password tidak cocok.';
        } elseif (strlen($new_password) < 6) {
            $alert_type = 'danger';
            $alert_message = 'Password baru minimal harus 6 karakter.';
        } else {
            try {
                // Fetch the current hashed password
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
                $stmt->execute([':id' => $user_id]);
                $current_hash = $stmt->fetchColumn();
                
                // Verify old password
                if (password_verify($old_password, $current_hash)) {
                    // Hash new password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $update_stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                    $update_stmt->execute([':password' => $new_hash, ':id' => $user_id]);
                    
                    $alert_type = 'success';
                    $alert_message = 'Password berhasil diubah.';
                } else {
                    $alert_type = 'danger';
                    $alert_message = 'Password lama tidak sesuai.';
                }
            } catch (PDOException $e) {
                $alert_type = 'danger';
                $alert_message = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-fluid px-0 animate-fade-in">
    <div class="row mb-4 align-items-center">
        <div class="col-12">
            <h3 class="fw-bold text-dark mb-0">Pengaturan Profil</h3>
            <p class="text-secondary mb-0 mt-1">Kelola informasi pribadi dan keamanan akun Anda.</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($alert_message)): ?>
        <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show border-0 shadow-sm auto-dismiss" role="alert">
            <?php if ($alert_type === 'success'): ?>
                <i class="bi bi-check-circle-fill me-2"></i>
            <?php else: ?>
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php endif; ?>
            <?= htmlspecialchars($alert_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Informasi Profil Form -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 d-flex align-items-center">
                        <i class="bi bi-person-badge text-primary me-2 fs-4"></i> Informasi Profil
                    </h5>
                    <form action="profile.php" method="POST" class="needs-validation">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-4 text-center">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=4f46e5&color=fff&size=100" alt="Profile" class="rounded-circle shadow-sm mb-3">
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label fw-medium text-secondary">Nama Lengkap</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="form-label fw-medium text-secondary">Alamat Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary px-4 btn-loading">
                            Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ubah Password Form -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 d-flex align-items-center">
                        <i class="bi bi-shield-lock text-success me-2 fs-4"></i> Keamanan Akun
                    </h5>
                    <form action="profile.php" method="POST" class="needs-validation">
                        <input type="hidden" name="action" value="update_password">
                        
                        <div class="mb-3">
                            <label for="old_password" class="form-label fw-medium text-secondary">Password Lama</label>
                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-medium text-secondary">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <div class="form-text">Minimal 6 karakter.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-medium text-secondary">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        
                        <button type="submit" class="btn btn-success px-4 btn-loading">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
