<?php
// index.php
require_once 'includes/header.php';

const TYPE_INCOME = 'pemasukan';
const TYPE_EXPENSE = 'pengeluaran';
require_once 'includes/dashboard_logic.php';
?>


<!-- Main Dashboard Container -->
<div class="container-fluid px-0">
    
    <!-- Page Header -->
    <div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h3 class="fw-bold text-dark">Dashboard</h3>
        <p class="text-secondary">
            Selamat datang kembali! Berikut ringkasan keuangan Anda bulan
            <?= date('F Y', strtotime($current_month)) ?>.
        </p>
    </div>
    <div class="col-md-4 text-md-end">

        <form method="GET">

            <select
                name="month"
                class="form-select"
                onchange="this.form.submit()">

                <?php
                for ($i = 0; $i < 12; $i++) {

                    $month = date('Y-m', strtotime("-$i month"));

                    $selected = ($month == $current_month)
                        ? 'selected'
                        : '';

                    echo "<option value='$month' $selected>"
                        . date('F Y', strtotime($month))
                        . "</option>";
                }
                ?>

            </select>

        </form>

    </div>

</div>

    <!-- Financial Summary Cards -->
    <div class="row g-4 mb-4">
        
        <!-- Total Income Card (Bulan Ini) -->
        <div class="col-12 col-md-4">
            <div class="card h-100 p-4 border-0 shadow-sm rounded-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary mb-0 fw-semibold">Pemasukan Bulan Ini</h6>
                    <div class="bg-success bg-opacity-10 p-2 rounded-circle d-flex align-items-center justify-content-center" class="... dashboard-icon">
                        <i class="bi bi-arrow-down-left text-success fs-4"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-success">Rp <?= number_format($total_income_month, 0, ',', '.') ?></h3>
            </div>
        </div>

        <!-- Total Expense Card (Bulan Ini) -->
        <div class="col-12 col-md-4">
            <div class="card h-100 p-4 border-0 shadow-sm rounded-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary mb-0 fw-semibold">Pengeluaran Bulan Ini</h6>
                    <div class="bg-danger bg-opacity-10 p-2 rounded-circle d-flex align-items-center justify-content-center" class="... dashboard-icon">
                        <i class="bi bi-arrow-up-right text-danger fs-4"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-danger">Rp <?= number_format($budget_terpakai, 0, ',', '.') ?></h3>
            </div>
        </div>

        <!-- Total Balance Card (All Time) -->
        <div class="col-12 col-md-4">
            <div class="card h-100 p-4 border-0 shadow-sm rounded-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary mb-0 fw-semibold">Total Saldo (Semua)</h6>
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle d-flex align-items-center justify-content-center" class="... dashboard-icon">
                        <i class="bi bi-wallet2 text-primary fs-4"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-primary">Rp <?= number_format($current_balance, 0, ',', '.') ?></h3>
            </div>
        </div>
        
    </div>

    <div class="row g-4 mb-4">
        <!-- Budget Overview Section with Smart Alert -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Ringkasan Budget Bulanan</h5>
                    <span class="badge <?= $progress_color ?> rounded-pill px-3 py-2 fs-6 shadow-sm"><?= $alert_status ?></span>
                </div>
                
                <?php if ($budget_bulanan > 0): ?>
                    <div class="row g-4 mb-4">
                        <div class="col-12 col-md-4">
                            <p class="text-secondary mb-1">Target Budget</p>
                            <h4 class="fw-bold">Rp <?= number_format($budget_bulanan, 0, ',', '.') ?></h4>
                        </div>
                        <div class="col-12 col-md-4">
                            <p class="text-secondary mb-1">Budget Terpakai</p>
                            <h4 class="fw-bold text-danger">Rp <?= number_format($budget_terpakai, 0, ',', '.') ?></h4>
                        </div>
                        <div class="col-12 col-md-4">
                            <p class="text-secondary mb-1">Sisa Budget</p>
                            <h4 class="fw-bold <?= $sisa_budget < 0 ? 'text-danger' : 'text-success' ?>">Rp <?= number_format($sisa_budget, 0, ',', '.') ?></h4>
                        </div>
                    </div>
                    
                    <!-- Smart Budget Progress Bar -->
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary fw-medium"><?= $alert_message ?></span>
                        <span class="fw-bold <?= $alert_text_color ?>"><?= $persentase_budget ?>%</span>
                    </div>
                    <div class="progress" class="progress budget-progress">
                        <div class="progress-bar <?= $progress_color ?> progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= min(100, $persentase_budget) ?>%;" aria-valuenow="<?= min(100, $persentase_budget) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-secondary mb-3">Anda belum menetapkan target budget untuk bulan ini.</p>
                        <a href="budgets.php" class="btn btn-primary rounded-pill px-4">Set Budget Sekarang</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats Card -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-light">
                <h5 class="fw-bold mb-4">Statistik Singkat</h5>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-receipt text-secondary me-2"></i>
                        <span class="text-secondary">Total Transaksi</span>
                    </div>
                    <span class="fw-bold"><?= $transaction_count ?></span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-graph-down-arrow text-danger me-2"></i>
                        <span class="text-secondary">Rata-rata Pengeluaran</span>
                    </div>
                    <span class="fw-bold">Rp <?= number_format($average_expense, 0, ',', '.') ?> /trx</span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-star-fill text-warning me-2"></i>
                        <span class="text-secondary">Kategori Terbesar</span>
                    </div>
                    <span class="fw-bold text-end">
                        <?= htmlspecialchars($largest_category_name) ?><br>
                        <small class="text-muted fw-normal">Rp <?= number_format($largest_category_amount, 0, ',', '.') ?></small>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        
        <!-- Bar Chart (Daily Income vs Expense) -->
        <div class="col-12 col-lg-8">
            <div class="card p-4 border-0 shadow-sm rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Arus Kas Harian</h5>
                </div>
                <div class="position-relative" class="position-relative chart-container">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Pie Chart (Expenses by Category) -->
        <div class="col-12 col-lg-4">
            <div class="card p-4 border-0 shadow-sm rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Distribusi Pengeluaran</h5>
                </div>
                <div class="position-relative d-flex justify-content-center align-items-center" class="position-relative chart-center">
                    <?php if (count($pie_values) > 0): ?>
                        <canvas id="pieChart"></canvas>
                    <?php else: ?>
                        <div class="text-center text-muted">Belum ada pengeluaran bulan ini.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Latest Transactions Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Transaksi Terakhir</h5>
                    <a href="transactions.php" class="btn btn-sm btn-primary px-3 rounded-pill">Lihat Semua</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 rounded-start">Tanggal</th>
                                <th class="border-0">Deskripsi</th>
                                <th class="border-0">Kategori</th>
                                <th class="border-0">Tipe</th>
                                <th class="border-0 text-end rounded-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($latest_transactions) > 0): ?>
                                <?php foreach ($latest_transactions as $txn): ?>
                                    <tr>
                                        <td class="text-secondary"><?= date('d M Y', strtotime($txn['transaction_date'])) ?></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($txn['title']) ?></td>
                                        <td>
                                            <?php if ($txn['category_type'] === TYPE_INCOME): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill"><?= htmlspecialchars($txn['category_name']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 rounded-pill"><?= htmlspecialchars($txn['category_name']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($txn['category_type'] === TYPE_INCOME): ?>
                                                <span class="text-success"><i class="bi bi-arrow-down-left me-1"></i>Pemasukan</span>
                                            <?php else: ?>
                                                <span class="text-danger"><i class="bi bi-arrow-up-right me-1"></i>Pengeluaran</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold <?= $txn['category_type'] === TYPE_INCOME ? 'text-success' : 'text-danger' ?>">
                                            <?= $txn['category_type'] === TYPE_INCOME ? '+' : '-' ?> Rp <?= number_format($txn['amount'], 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">Belum ada transaksi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Colors
    const primaryColor = '#0d6efd';
    const successColor = '#198754';
    const dangerColor = '#dc3545';
    const fontColor = '#6c757d';

    // Set Global Chart Settings
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = fontColor;
    
    // --- Bar Chart (Arus Kas Harian) ---
    const ctxBar = document.getElementById('barChart');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?= json_encode($bar_labels) ?>,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: <?= json_encode($bar_income) ?>,
                        backgroundColor: 'rgba(25, 135, 84, 0.8)', // Success
                        borderRadius: 4,
                    },
                    {
                        label: 'Pengeluaran',
                        data: <?= json_encode($bar_expense) ?>,
                        backgroundColor: 'rgba(220, 53, 69, 0.8)', // Danger
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#f3f4f6' },
                        ticks: {
                            callback: function(value) {
                                if (value === 0) return '0';
                                return 'Rp ' + (value / 1000) + 'k';
                            }
                        }
                    }
                },
                plugins: {
                    legend: { position: 'top', align: 'end' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // --- Pie Chart (Distribusi Pengeluaran) ---
    const ctxPie = document.getElementById('pieChart');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($pie_labels) ?>,
                datasets: [{
                    data: <?= json_encode($pie_values) ?>,
                    backgroundColor: <?= json_encode($pie_colors) ?>,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%', // Doughnut hole size
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php
// Include the shared footer template
require_once 'includes/footer.php';
?>
