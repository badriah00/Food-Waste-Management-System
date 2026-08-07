<?php

// هذا يبدأ الجلسة
session_start();

// هذا يستدعي الاتصال بقاعدة البيانات
require_once "../connect_db.php";

// هذا يتحقق إن المستخدم مدير
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_type"]) ||
    $_SESSION["user_type"] !== "admin"
) {
    header("Location: ../login.php");
    exit();
}

// هذا ياخذ رقم المدير
$admin_id = (int) $_SESSION["user_id"];

// هذي رسائل الصفحة
$error_message = "";
$success_message = "";

// هذي أنواع التقارير المسموحة
$allowed_report_types = [
    "System Overview",
    "Donations Report",
    "Requests Report",
    "Users Report"
];

// هذا ياخذ نوع التقرير
$report_type =
    $_GET["report_type"]
    ?? $_POST["report_type"]
    ?? "System Overview";

// هذا ياخذ تاريخ البداية
$start_date =
    $_GET["start_date"]
    ?? $_POST["start_date"]
    ?? "";

// هذا ياخذ تاريخ النهاية
$end_date =
    $_GET["end_date"]
    ?? $_POST["end_date"]
    ?? "";

// هذا يتحقق إن نوع التقرير صحيح
if (!in_array($report_type, $allowed_report_types, true)) {
    $report_type = "System Overview";
}

// هذا يتحقق إن تاريخ البداية صحيح
if (
    $start_date !== "" &&
    !preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date)
) {
    $start_date = "";
}

// هذا يتحقق إن تاريخ النهاية صحيح
if (
    $end_date !== "" &&
    !preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date)
) {
    $end_date = "";
}

// هذا يتحقق إن تاريخ النهاية مو قبل البداية
if (
    $start_date !== "" &&
    $end_date !== "" &&
    $end_date < $start_date
) {
    $error_message =
        "End date cannot be before start date.";
}

// هذا يبني شرط التاريخ للتبرعات
$donation_date_condition = "";

// هذا يبني شرط التاريخ للطلبات
$request_date_condition = "";

// هذا يبني شرط التاريخ للمستخدمين
$user_date_condition = "";

// إذا فيه تاريخ بداية يضيفه للشروط
if ($start_date !== "") {
    $safe_start =
        $conn->real_escape_string($start_date);

    $donation_date_condition .=
        " AND DATE(created_at) >= '$safe_start'";

    $request_date_condition .=
        " AND DATE(request_date) >= '$safe_start'";

    $user_date_condition .=
        " AND DATE(created_at) >= '$safe_start'";
}

// إذا فيه تاريخ نهاية يضيفه للشروط
if ($end_date !== "") {
    $safe_end =
        $conn->real_escape_string($end_date);

    $donation_date_condition .=
        " AND DATE(created_at) <= '$safe_end'";

    $request_date_condition .=
        " AND DATE(request_date) <= '$safe_end'";

    $user_date_condition .=
        " AND DATE(created_at) <= '$safe_end'";
}

// هذي إحصائيات التقرير
$report_statistics = [
    "total_donors" => 0,
    "total_charities" => 0,
    "total_donations" => 0,
    "available_donations" => 0,
    "approved_donations" => 0,
    "completed_donations" => 0,
    "total_requests" => 0,
    "pending_requests" => 0,
    "approved_requests" => 0,
    "rejected_requests" => 0
];

// هذا يحسب المتبرعين
$donors_sql = "
    SELECT COUNT(*) AS total
    FROM donors
    WHERE 1 = 1
    $user_date_condition
";

$donors_result = $conn->query($donors_sql);

if ($donors_result) {
    $row = $donors_result->fetch_assoc();

    $report_statistics["total_donors"] =
        (int) ($row["total"] ?? 0);
}

// هذا يحسب الجمعيات
$charities_sql = "
    SELECT COUNT(*) AS total
    FROM charities
    WHERE 1 = 1
    $user_date_condition
";

$charities_result = $conn->query($charities_sql);

if ($charities_result) {
    $row = $charities_result->fetch_assoc();

    $report_statistics["total_charities"] =
        (int) ($row["total"] ?? 0);
}

// هذا يحسب إحصائيات التبرعات
$donations_sql = "
    SELECT
        COUNT(*) AS total_donations,
        SUM(status = 'Available') AS available_donations,
        SUM(status = 'Approved') AS approved_donations,
        SUM(
            status IN ('Completed', 'Collected')
        ) AS completed_donations
    FROM food_donations
    WHERE 1 = 1
    $donation_date_condition
";

$donations_result = $conn->query($donations_sql);

if ($donations_result) {
    $row = $donations_result->fetch_assoc();

    $report_statistics["total_donations"] =
        (int) ($row["total_donations"] ?? 0);

    $report_statistics["available_donations"] =
        (int) ($row["available_donations"] ?? 0);

    $report_statistics["approved_donations"] =
        (int) ($row["approved_donations"] ?? 0);

    $report_statistics["completed_donations"] =
        (int) ($row["completed_donations"] ?? 0);
}

// هذا يحسب إحصائيات الطلبات
$requests_sql = "
    SELECT
        COUNT(*) AS total_requests,
        SUM(status = 'Pending') AS pending_requests,
        SUM(status = 'Approved') AS approved_requests,
        SUM(status = 'Rejected') AS rejected_requests
    FROM donation_requests
    WHERE 1 = 1
    $request_date_condition
";

$requests_result = $conn->query($requests_sql);

if ($requests_result) {
    $row = $requests_result->fetch_assoc();

    $report_statistics["total_requests"] =
        (int) ($row["total_requests"] ?? 0);

    $report_statistics["pending_requests"] =
        (int) ($row["pending_requests"] ?? 0);

    $report_statistics["approved_requests"] =
        (int) ($row["approved_requests"] ?? 0);

    $report_statistics["rejected_requests"] =
        (int) ($row["rejected_requests"] ?? 0);
}

// هذا يتحقق إذا المدير ضغط حفظ التقرير
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["save_report"]) &&
    $error_message === ""
) {
    // هذا يحول الإحصائيات إلى JSON عشان نحفظها
    $report_data = json_encode(
        $report_statistics,
        JSON_UNESCAPED_UNICODE
    );

    // هذا يحول التاريخ الفاضي إلى NULL
    $saved_start_date =
        $start_date !== ""
            ? $start_date
            : null;

    $saved_end_date =
        $end_date !== ""
            ? $end_date
            : null;

    // هذا يحفظ التقرير في قاعدة البيانات
    $save_sql = "
        INSERT INTO reports (
            admin_id,
            report_type,
            start_date,
            end_date,
            report_data
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $save_stmt = $conn->prepare($save_sql);

    $save_stmt->bind_param(
        "issss",
        $admin_id,
        $report_type,
        $saved_start_date,
        $saved_end_date,
        $report_data
    );

    if ($save_stmt->execute()) {
        $success_message =
            "The report has been saved successfully.";
    } else {
        $error_message =
            "Unable to save the report.";
    }

    $save_stmt->close();
}

// هذا يجيب التقارير المحفوظة
$saved_reports = [];

$saved_sql = "
    SELECT
        r.report_id,
        r.report_type,
        r.start_date,
        r.end_date,
        r.created_at,
        a.full_name AS admin_name
    FROM reports r
    INNER JOIN admins a
        ON r.admin_id = a.admin_id
    ORDER BY r.created_at DESC
    LIMIT 20
";

$saved_result = $conn->query($saved_sql);

if ($saved_result) {
    while ($row = $saved_result->fetch_assoc()) {
        $saved_reports[] = $row;
    }
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

    <title>Reports | FoodSave</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body class="dashboard-body">

<header class="dashboard-header no-print">
    <div class="dashboard-header-content">

        <a href="../index.php" class="dashboard-logo">
            🌱 FoodSave
        </a>

        <div class="dashboard-user">
            <span>
                Administrator:
                <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
            </span>

            <a href="../logout.php" class="logout-button">
                Logout
            </a>
        </div>

    </div>
</header>

<div class="dashboard-layout">

    <aside class="dashboard-sidebar admin-sidebar no-print">
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_donors.php">Manage Donors</a>
            <a href="manage_charities.php">Manage Charities</a>
            <a href="manage_donations.php">Manage Donations</a>
            <a href="reports.php" class="active">Reports</a>
        </nav>
    </aside>

    <main class="dashboard-main report-main">

        <div class="page-heading">
            <div>
                <h1>System Reports</h1>

                <p>
                    Generate, save, and print system reports.
                </p>
            </div>

            <button
                type="button"
                class="primary-button no-print"
                onclick="window.print();"
            >
                Print Report
            </button>
        </div>

        <?php if ($error_message !== ""): ?>
            <div class="alert alert-error no-print">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message !== ""): ?>
            <div class="alert alert-success no-print">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <section class="dashboard-panel report-filter-panel no-print">

            <form
                method="GET"
                action="reports.php"
                class="report-filter-form"
            >

                <div class="form-group">
                    <label for="report_type">
                        Report Type
                    </label>

                    <select
                        id="report_type"
                        name="report_type"
                    >
                        <?php
                        foreach (
                            $allowed_report_types as $type
                        ):
                        ?>
                            <option
                                value="<?php
                                echo htmlspecialchars($type);
                                ?>"
                                <?php
                                echo $report_type === $type
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_date">
                        Start Date
                    </label>

                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?php
                        echo htmlspecialchars($start_date);
                        ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="end_date">
                        End Date
                    </label>

                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="<?php
                        echo htmlspecialchars($end_date);
                        ?>"
                    >
                </div>

                <button type="submit" class="primary-button">
                    Generate
                </button>

            </form>

        </section>

        <section class="print-report">

            <div class="report-heading">
                <div>
                    <h2>
                        <?php echo htmlspecialchars($report_type); ?>
                    </h2>

                    <p>
                        Period:
                        <?php
                        echo $start_date !== ""
                            ? htmlspecialchars($start_date)
                            : "Beginning";
                        ?>

                        to

                        <?php
                        echo $end_date !== ""
                            ? htmlspecialchars($end_date)
                            : date("Y-m-d");
                        ?>
                    </p>
                </div>

                <span>
                    Generated:
                    <?php echo date("Y-m-d H:i"); ?>
                </span>
            </div>

            <div class="report-statistics-grid">

                <?php
                if (
                    $report_type === "System Overview" ||
                    $report_type === "Users Report"
                ):
                ?>
                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["total_donors"];
                            ?>
                        </h3>
                        <p>Total Donors</p>
                    </div>

                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["total_charities"];
                            ?>
                        </h3>
                        <p>Total Charities</p>
                    </div>
                <?php endif; ?>

                <?php
                if (
                    $report_type === "System Overview" ||
                    $report_type === "Donations Report"
                ):
                ?>
                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["total_donations"];
                            ?>
                        </h3>
                        <p>Total Donations</p>
                    </div>

                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["available_donations"];
                            ?>
                        </h3>
                        <p>Available Donations</p>
                    </div>

                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["approved_donations"];
                            ?>
                        </h3>
                        <p>Approved Donations</p>
                    </div>

                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["completed_donations"];
                            ?>
                        </h3>
                        <p>Completed Donations</p>
                    </div>
                <?php endif; ?>

                <?php
                if (
                    $report_type === "System Overview" ||
                    $report_type === "Requests Report"
                ):
                ?>
                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["total_requests"];
                            ?>
                        </h3>
                        <p>Total Requests</p>
                    </div>

                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["pending_requests"];
                            ?>
                        </h3>
                        <p>Pending Requests</p>
                    </div>

                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["approved_requests"];
                            ?>
                        </h3>
                        <p>Approved Requests</p>
                    </div>

                    <div class="report-stat-card">
                        <h3>
                            <?php
                            echo $report_statistics["rejected_requests"];
                            ?>
                        </h3>
                        <p>Rejected Requests</p>
                    </div>
                <?php endif; ?>

            </div>

        </section>

        <form
            method="POST"
            action="reports.php"
            class="save-report-form no-print"
        >
            <input
                type="hidden"
                name="report_type"
                value="<?php echo htmlspecialchars($report_type); ?>"
            >

            <input
                type="hidden"
                name="start_date"
                value="<?php echo htmlspecialchars($start_date); ?>"
            >

            <input
                type="hidden"
                name="end_date"
                value="<?php echo htmlspecialchars($end_date); ?>"
            >

            <button
                type="submit"
                name="save_report"
                class="submit-button"
            >
                Save Report
            </button>
        </form>

        <section class="dashboard-panel saved-reports-panel no-print">

            <div class="panel-heading">
                <h2>Saved Reports</h2>
            </div>

            <?php if (!empty($saved_reports)): ?>

                <div class="table-wrapper">
                    <table class="dashboard-table">

                        <thead>
                        <tr>
                            <th>Report Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Generated By</th>
                            <th>Saved Date</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($saved_reports as $report): ?>
                            <tr>
                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $report["report_type"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $report["start_date"]
                                        ?: "Beginning"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $report["end_date"]
                                        ?: "Current"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $report["admin_name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $report["created_at"]
                                    );
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            <?php else: ?>

                <div class="empty-state dashboard-empty">
                    <h3>No Saved Reports</h3>

                    <p>
                        Saved reports will appear here.
                    </p>
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>