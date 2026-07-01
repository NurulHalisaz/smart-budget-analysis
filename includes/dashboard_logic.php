<?php
$user_id = $_SESSION['user_id'];
$current_month = $_GET['month'] ?? date('Y-m');
$current_year = date('Y', strtotime($current_month));
$current_month_number = date('m', strtotime($current_month));
$days_in_month = date('t', strtotime($current_month));
try {
    // 1. All Time Balance (Total Income - Total Expense)
   $stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN c.type = :income THEN t.amount END),0) AS total_income,
        COALESCE(SUM(CASE WHEN c.type = :expense THEN t.amount END),0) AS total_expense
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = :user_id
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':income' => TYPE_INCOME,
        ':expense' => TYPE_EXPENSE
    ]);

    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_income_all_time = $summary['total_income'];
    $total_expense_all_time = $summary['total_expense'];

    $current_balance = $total_income_all_time - $total_expense_all_time;

    // 2. Current Month Budget
    $stmt = $conn->prepare("SELECT amount FROM budgets WHERE user_id = :user_id AND month = :month");
    $stmt->execute([':user_id' => $user_id, ':month' => $current_month]);
    $budget_bulanan = $stmt->fetchColumn() ?: 0;

    // 3. Current Month Expense (Budget Used) & Current Month Income
    $stmt = $conn->prepare("
    SELECT

    COALESCE(
    SUM(
    CASE
    WHEN c.type = :income
    THEN t.amount
    END
    ),0) AS total_income,

    COALESCE(
    SUM(
    CASE
    WHEN c.type = :expense
    THEN t.amount
    END
    ),0) AS total_expense

    FROM transactions t

    JOIN categories c
    ON t.category_id = c.id

    WHERE t.user_id = :user_id
    AND DATE_FORMAT(t.transaction_date,'%Y-%m') = :month
    ");

    $stmt->execute([
        ':user_id'=>$user_id,
        ':month'=>$current_month,
        ':income'=>TYPE_INCOME,
        ':expense'=>TYPE_EXPENSE
    ]);

    $monthlySummary = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_income_month = $monthlySummary['total_income'];
    $budget_terpakai = $monthlySummary['total_expense'];
    $sisa_budget = $budget_bulanan - $budget_terpakai;
    $persentase_budget =
    $budget_bulanan > 0
    ? min(100, round(($budget_terpakai/$budget_bulanan)*100))
    : 0;

    // 5. Monthly Transaction Count
    $stmt = $conn->prepare("SELECT COUNT(id) FROM transactions WHERE user_id = :user_id AND DATE_FORMAT(transaction_date, '%Y-%m') = :month");
    $stmt->execute([':user_id' => $user_id, ':month' => $current_month]);
    $transaction_count = $stmt->fetchColumn() ?: 0;

    // 6. Largest Expense Category this month
    $stmt = $conn->prepare("
        SELECT
            c.name,
            SUM(t.amount) AS total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = :user_id
          AND c.type = :expense
          AND DATE_FORMAT(t.transaction_date, '%Y-%m') = :month
        GROUP BY t.category_id, c.name
        ORDER BY total DESC
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':month' => $current_month,
        ':expense' => TYPE_EXPENSE
    ]);

    $largest_category = $stmt->fetch(PDO::FETCH_ASSOC);

    $largest_category_name = $largest_category
        ? $largest_category['name']
        : 'Belum Ada';

    $largest_category_amount = $largest_category
        ? $largest_category['total']
        : 0;


    // 7. Average Expense per Transaction this month
    $stmt = $conn->prepare("
        SELECT COUNT(t.id) AS total_transaction
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = :user_id
          AND c.type = :expense
          AND DATE_FORMAT(t.transaction_date,'%Y-%m') = :month
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':month' => $current_month,
        ':expense' => TYPE_EXPENSE
    ]);

    $expense_count = $stmt->fetchColumn() ?: 0;

    $average_expense = $expense_count > 0
        ? $budget_terpakai / $expense_count
        : 0;


    // 8. Latest 5 Transactions
    $stmt = $conn->prepare("
        SELECT
            t.*,
            c.name AS category_name,
            c.type AS category_type
        FROM transactions t
        JOIN categories c
            ON t.category_id = c.id
        WHERE t.user_id = :user_id
        ORDER BY
            t.transaction_date DESC,
            t.id DESC
        LIMIT 5
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $latest_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // 9. Pie Chart Data (Expenses by Category for current month)
    $stmt = $conn->prepare("
        SELECT
            c.name,
            SUM(t.amount) AS total
        FROM transactions t
        JOIN categories c
            ON t.category_id = c.id
        WHERE t.user_id = :user_id
          AND c.type = :expense
          AND DATE_FORMAT(t.transaction_date,'%Y-%m') = :month
        GROUP BY
            t.category_id,
            c.name
        ORDER BY total DESC
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':month' => $current_month,
        ':expense' => TYPE_EXPENSE
    ]);

    $pie_data_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pie_labels = [];
    $pie_values = [];
    $pie_colors = [
        '#f87171',
        '#fb923c',
        '#fbbf24',
        '#a3e635',
        '#4ade80',
        '#2dd4bf',
        '#38bdf8',
        '#818cf8',
        '#c084fc',
        '#f472b6'
    ];

    foreach ($pie_data_raw as $row) {
        $pie_labels[] = $row['name'];
        $pie_values[] = $row['total'];
    }


    // 10. Bar Chart Data (Daily Income vs Expense for current month)
    $stmt = $conn->prepare("
        SELECT
            DAY(t.transaction_date) AS day,
            c.type,
            SUM(t.amount) AS total
        FROM transactions t
        JOIN categories c
            ON t.category_id = c.id
        WHERE t.user_id = :user_id
          AND DATE_FORMAT(t.transaction_date,'%Y-%m') = :month
        GROUP BY
            day,
            c.type
        ORDER BY day
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':month' => $current_month
    ]);

    $bar_data_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bar_labels = range(1, $days_in_month);
    $bar_income = array_fill(0, $days_in_month, 0);
    $bar_expense = array_fill(0, $days_in_month, 0);

    foreach ($bar_data_raw as $row) {

        $day_index = $row['day'] - 1;

        if ($row['type'] === TYPE_INCOME) {
            $bar_income[$day_index] = $row['total'];
        } else {
            $bar_expense[$day_index] = $row['total'];
        }
    }
    } catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Smart Budget Alert Logic
$progress_color = 'bg-success'; // Safe (0-50%)
$alert_text_color = 'text-success';
$alert_status = 'Aman';
$alert_message = 'Pengeluaran Anda sangat terkendali.';

if ($persentase_budget > 100) {
    $progress_color = 'bg-danger'; // Over Budget
    $alert_text_color = 'text-danger';
    $alert_status = 'Over Budget!';
    $alert_message = 'Peringatan keras! Anda telah melewati batas budget.';
} elseif ($persentase_budget > 80) {
    $progress_color = 'bg-orange'; // Critical (81-100%) - Will use custom hex or Bootstrap warning modifier
    $alert_text_color = 'text-orange'; // Custom class needed
    $alert_status = 'Kritis';
    $alert_message = 'Hati-hati! Budget Anda hampir habis.';
    $progress_color = 'bg-danger'; // fallback to danger if orange is not defined in bootstrap standardly, we will use style directly
} elseif ($persentase_budget > 50) {
    $progress_color = 'bg-warning'; // Warning (51-80%)
    $alert_text_color = 'text-warning';
    $alert_status = 'Peringatan';
    $alert_message = 'Pengeluaran Anda mulai mendekati batas wajar.';
}
?>
