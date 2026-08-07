<?php
// هذا يبدأ الجلسة عشان نقدر نعرف المستخدم المسجل
session_start();

// هذا يستدعي ملف الاتصال بقاعدة البيانات
require_once "../connect_db.php";

// هذا يتحقق إن المستخدم مسجل دخول ونوعه متبرع
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_type"]) ||
    $_SESSION["user_type"] !== "donor"
) {
    // إذا المستخدم مو متبرع يرجعه لصفحة تسجيل الدخول
    header("Location: ../login.php");
    exit();
}

// هذا ياخذ رقم المتبرع من الجلسة
$donor_id = (int) $_SESSION["user_id"];

// هذا يحط القيم الافتراضية للإحصائيات
$total_donations = 0;
$available_donations = 0;
$requested_donations = 0;
$approved_donations = 0;
$new_requests = 0;

// هذا الاستعلام يحسب إحصائيات تبرعات المتبرع
$sql = "
    SELECT
        COUNT(*) AS total_donations,
        SUM(status = 'Available') AS available_donations,
        SUM(status = 'Requested') AS requested_donations,
        SUM(status = 'Approved') AS approved_donations
    FROM food_donations
    WHERE donor_id = ?
";

// هذا يجهز الاستعلام بطريقة آمنة
$stmt = $conn->prepare($sql);

// هذا يتحقق إن الاستعلام تجهز بشكل صحيح
if ($stmt) {
    // هذا يربط رقم المتبرع بالاستعلام
    $stmt->bind_param("i", $donor_id);

    // هذا ينفذ الاستعلام
    $stmt->execute();

    // هذا يجيب نتيجة الاستعلام
    $result = $stmt->get_result();

    // هذا يحول النتيجة إلى مصفوفة
    $statistics = $result->fetch_assoc();

    // هذا يحفظ الإحصائيات في المتغيرات
    $total_donations = (int) ($statistics["total_donations"] ?? 0);
    $available_donations = (int) ($statistics["available_donations"] ?? 0);
    $requested_donations = (int) ($statistics["requested_donations"] ?? 0);
    $approved_donations = (int) ($statistics["approved_donations"] ?? 0);

    // هذا يقفل الاستعلام بعد الانتهاء
    $stmt->close();
}

// هذا الاستعلام يحسب الطلبات الجديدة المعلقة
$request_sql = "
    SELECT COUNT(*) AS new_requests
    FROM donation_requests dr
    INNER JOIN food_donations fd
        ON dr.donation_id = fd.donation_id
    WHERE fd.donor_id = ?
      AND dr.status = 'Pending'
";

// هذا يجهز استعلام الطلبات
$request_stmt = $conn->prepare($request_sql);

// هذا يتحقق إن الاستعلام تجهز
if ($request_stmt) {
    // هذا يربط رقم المتبرع
    $request_stmt->bind_param("i", $donor_id);

    // هذا ينفذ الاستعلام
    $request_stmt->execute();

    // هذا يجيب النتيجة
    $request_result = $request_stmt->get_result()->fetch_assoc();

    // هذا يحفظ عدد الطلبات الجديدة
    $new_requests = (int) ($request_result["new_requests"] ?? 0);

    // هذا يقفل الاستعلام
    $request_stmt->close();
}

// هذا الاستعلام يجيب آخر التبرعات
$latest_donations = [];

$latest_sql = "
    SELECT
        donation_id,
        food_name,
        category,
        quantity,
        quantity_unit,
        status,
        expiry_date
    FROM food_donations
    WHERE donor_id = ?
    ORDER BY created_at DESC
    LIMIT 5
";

// هذا يجهز استعلام آخر التبرعات
$latest_stmt = $conn->prepare($latest_sql);

// هذا يتحقق إن الاستعلام تجهز
if ($latest_stmt) {
    // هذا يربط رقم المتبرع
    $latest_stmt->bind_param("i", $donor_id);

    // هذا ينفذ الاستعلام
    $latest_stmt->execute();

    // هذا يجيب النتائج
    $latest_result = $latest_stmt->get_result();

    // هذا يضيف كل تبرع داخل المصفوفة
    while ($row = $latest_result->fetch_assoc()) {
        $latest_donations[] = $row;
    }

    // هذا يقفل الاستعلام
    $latest_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Donor Dashboard | FoodSave</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body class="dashboard-body">

<header class="dashboard-header">
    <div class="dashboard-header-content">

        <a href="../index.php" class="dashboard-logo">
            🌱 FoodSave
        </a>

        <div class="dashboard-user">
            <span>
                Welcome,
                <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
            </span>

            <a href="../logout.php" class="logout-button">
                Logout
            </a>
        </div>

    </div>
</header>

<div class="dashboard-layout">

    <aside class="dashboard-sidebar">
        <nav>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="profile.php">My Profile</a>
            <a href="add_donation.php">Add Donation</a>
            <a href="manage_donations.php">Manage Donations</a>
            <a href="requests.php">
                Requests
                <?php if ($new_requests > 0): ?>
                    <span class="sidebar-count">
                        <?php echo $new_requests; ?>
                    </span>
                <?php endif; ?>
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">

        <div class="page-heading">
            <div>
                <h1>Donor Dashboard</h1>
                <p>
                    Manage your donations and charity requests.
                </p>
            </div>

            <a href="add_donation.php" class="primary-button">
                Add New Donation
            </a>
        </div>

        <section class="dashboard-cards">

            <div class="dashboard-card">
                <span class="dashboard-card-icon">🍱</span>
                <div>
                    <h3><?php echo $total_donations; ?></h3>
                    <p>Total Donations</p>
                </div>
            </div>

            <div class="dashboard-card">
                <span class="dashboard-card-icon">✅</span>
                <div>
                    <h3><?php echo $available_donations; ?></h3>
                    <p>Available Donations</p>
                </div>
            </div>

            <div class="dashboard-card">
                <span class="dashboard-card-icon">📩</span>
                <div>
                    <h3><?php echo $requested_donations; ?></h3>
                    <p>Requested Donations</p>
                </div>
            </div>

            <div class="dashboard-card">
                <span class="dashboard-card-icon">🤝</span>
                <div>
                    <h3><?php echo $approved_donations; ?></h3>
                    <p>Approved Donations</p>
                </div>
            </div>

            <div class="dashboard-card">
                <span class="dashboard-card-icon">🔔</span>
                <div>
                    <h3><?php echo $new_requests; ?></h3>
                    <p>New Requests</p>
                </div>
            </div>

        </section>

        <section class="dashboard-panel">

            <div class="panel-heading">
                <h2>Latest Donations</h2>

                <a href="manage_donations.php">
                    View All
                </a>
            </div>

            <?php if (!empty($latest_donations)): ?>

                <div class="table-wrapper">
                    <table class="dashboard-table">
                        <thead>
                        <tr>
                            <th>Food Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($latest_donations as $donation): ?>
                            <tr>
                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $donation["food_name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $donation["category"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $donation["quantity"] .
                                        " " .
                                        $donation["quantity_unit"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $donation["expiry_date"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?php
                                    echo strtolower(
                                        htmlspecialchars(
                                            $donation["status"]
                                        )
                                    );
                                    ?>">
                                        <?php
                                        echo htmlspecialchars(
                                            $donation["status"]
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="donation_details.php?id=<?php
                                        echo (int) $donation["donation_id"];
                                        ?>"
                                        class="table-link"
                                    >
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="empty-state dashboard-empty">
                    <h3>No Donations Yet</h3>
                    <p>
                        Add your first food donation to start helping charities.
                    </p>

                    <a href="add_donation.php" class="primary-button">
                        Add Donation
                    </a>
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>