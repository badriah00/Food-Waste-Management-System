<?php

// هذا يبدأ الجلسة
session_start();

// هذا يستدعي ملف الاتصال بقاعدة البيانات
require_once "../connect_db.php";

// هذا يتحقق إن المستخدم جمعية
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_type"]) ||
    $_SESSION["user_type"] !== "charity"
) {
    // إذا المستخدم مو جمعية يرجعه لتسجيل الدخول
    header("Location: ../login.php");
    exit();
}

// هذا ياخذ رقم الجمعية
$charity_id = (int) $_SESSION["user_id"];

// هذي رسائل الصفحة
$error_message = "";
$success_message = "";

// هذا يتحقق إذا الجمعية طلبت إلغاء طلب
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["cancel_request_id"])
) {
    // هذا ياخذ رقم الطلب
    $request_id =
        (int) $_POST["cancel_request_id"];

    // هذا يتحقق إن الطلب تابع للجمعية وحالته معلقة
    $check_sql = "
        SELECT
            request_id,
            donation_id,
            status
        FROM donation_requests
        WHERE request_id = ?
          AND charity_id = ?
        LIMIT 1
    ";

    // هذا يجهز الاستعلام
    $check_stmt = $conn->prepare($check_sql);

    // هذا يربط رقم الطلب والجمعية
    $check_stmt->bind_param(
        "ii",
        $request_id,
        $charity_id
    );

    // هذا ينفذ الاستعلام
    $check_stmt->execute();

    // هذا يحفظ بيانات الطلب
    $request =
        $check_stmt->get_result()->fetch_assoc();

    // هذا يقفل الاستعلام
    $check_stmt->close();

    if (!$request) {
        // إذا الطلب مو موجود يظهر رسالة
        $error_message = "Request not found.";
    } elseif ($request["status"] !== "Pending") {
        // هذا يمنع إلغاء الطلب بعد معالجته
        $error_message =
            "Only pending requests can be cancelled.";
    } else {
        // هذا يحذف الطلب المعلق
        $delete_sql = "
            DELETE FROM donation_requests
            WHERE request_id = ?
              AND charity_id = ?
              AND status = 'Pending'
        ";

        // هذا يجهز استعلام الحذف
        $delete_stmt = $conn->prepare($delete_sql);

        // هذا يربط رقم الطلب والجمعية
        $delete_stmt->bind_param(
            "ii",
            $request_id,
            $charity_id
        );

        // هذا ينفذ حذف الطلب
        if ($delete_stmt->execute()) {
            // هذا يظهر رسالة نجاح
            $success_message =
                "The request has been cancelled successfully.";
        } else {
            // هذا يظهر رسالة إذا فشل الحذف
            $error_message =
                "Unable to cancel the request.";
        }

        // هذا يقفل الاستعلام
        $delete_stmt->close();
    }
}

// هذي المصفوفة بنحفظ فيها الطلبات
$requests = [];

// هذا يجيب كل طلبات الجمعية
$sql = "
    SELECT
        dr.request_id,
        dr.request_message,
        dr.request_date,
        dr.status,
        dr.donor_response,
        fd.donation_id,
        fd.food_name,
        fd.quantity,
        fd.quantity_unit,
        fd.pickup_date,
        fd.pickup_time,
        d.full_name AS donor_name
    FROM donation_requests dr
    INNER JOIN food_donations fd
        ON dr.donation_id = fd.donation_id
    INNER JOIN donors d
        ON fd.donor_id = d.donor_id
    WHERE dr.charity_id = ?
    ORDER BY dr.request_date DESC
";

// هذا يجهز الاستعلام
$stmt = $conn->prepare($sql);

// هذا يربط رقم الجمعية
$stmt->bind_param("i", $charity_id);

// هذا ينفذ الاستعلام
$stmt->execute();

// هذا يجيب النتائج
$result = $stmt->get_result();

// هذا يضيف كل طلب داخل المصفوفة
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

// هذا يقفل الاستعلام
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Requests | FoodSave</title>

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
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">My Profile</a>
            <a href="donations.php">Available Donations</a>
            <a href="my_requests.php" class="active">
                My Requests
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">

        <div class="page-heading">
            <div>
                <h1>My Requests</h1>

                <p>
                    View and manage your donation requests.
                </p>
            </div>

            <a href="donations.php" class="primary-button">
                Browse Donations
            </a>
        </div>

        <?php if ($error_message !== ""): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message !== ""): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <section class="dashboard-panel">

            <?php if (!empty($requests)): ?>

                <div class="table-wrapper">
                    <table class="dashboard-table">

                        <thead>
                        <tr>
                            <th>Food Name</th>
                            <th>Donor Name</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Donor Response</th>
                            <th>Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $request["food_name"]
                                        );
                                        ?>
                                    </strong>

                                    <small class="table-subtext">
                                        <?php
                                        echo htmlspecialchars(
                                            $request["quantity"] .
                                            " " .
                                            $request["quantity_unit"]
                                        );
                                        ?>
                                    </small>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $request["donor_name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $request["request_date"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?php
                                    echo strtolower($request["status"]);
                                    ?>">
                                        <?php
                                        echo htmlspecialchars(
                                            $request["status"]
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $request["donor_response"]
                                        ?: "No response yet"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <div class="table-actions">

                                        <a
                                            href="donation_details.php?id=<?php
                                            echo (int) $request["donation_id"];
                                            ?>"
                                            class="action-view"
                                        >
                                            View
                                        </a>

                                        <?php
                                        if (
                                            $request["status"] === "Pending"
                                        ):
                                        ?>
                                            <form
                                                method="POST"
                                                action="my_requests.php"
                                                onsubmit="return confirm('Are you sure you want to cancel this request?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="cancel_request_id"
                                                    value="<?php
                                                    echo (int) $request["request_id"];
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action-delete"
                                                >
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            <?php else: ?>

                <div class="empty-state dashboard-empty">
                    <h3>No Requests Found</h3>

                    <p>
                        You have not submitted any donation requests yet.
                    </p>

                    <a href="donations.php" class="primary-button">
                        Browse Donations
                    </a>
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>