<?php
// هذا يبدأ الجلسة
session_start();

// هذا يستدعي الاتصال بقاعدة البيانات
require_once "../connect_db.php";

// هذا يتحقق إن المستخدم متبرع
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_type"]) ||
    $_SESSION["user_type"] !== "donor"
) {
    header("Location: ../login.php");
    exit();
}

// هذا ياخذ رقم المتبرع
$donor_id = (int) $_SESSION["user_id"];

// هذه رسائل الصفحة
$error_message = "";
$success_message = "";

// هذا يتحقق إذا المستخدم وافق أو رفض طلب
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["request_id"], $_POST["action"])
) {
    // هذا ياخذ رقم الطلب
    $request_id = (int) $_POST["request_id"];

    // هذا ياخذ نوع العملية
    $action = $_POST["action"];

    // هذا يجيب بيانات الطلب ويتأكد إنه تابع للمتبرع
    $check_sql = "
        SELECT
            dr.request_id,
            dr.donation_id,
            dr.status
        FROM donation_requests dr
        INNER JOIN food_donations fd
            ON dr.donation_id = fd.donation_id
        WHERE dr.request_id = ?
          AND fd.donor_id = ?
        LIMIT 1
    ";

    // هذا يجهز الاستعلام
    $check_stmt = $conn->prepare($check_sql);

    // هذا يربط رقم الطلب والمتبرع
    $check_stmt->bind_param(
        "ii",
        $request_id,
        $donor_id
    );

    // هذا ينفذ الاستعلام
    $check_stmt->execute();

    // هذا يحفظ بيانات الطلب
    $request =
        $check_stmt->get_result()->fetch_assoc();

    // هذا يقفل الاستعلام
    $check_stmt->close();

    // هذا يتحقق إن الطلب موجود ومعلق
    if (!$request) {
        $error_message = "Request not found.";
    } elseif ($request["status"] !== "Pending") {
        $error_message =
            "This request has already been processed.";
    } elseif ($action === "approve") {
        // هذا يبدأ معاملة قاعدة البيانات
        $conn->begin_transaction();

        try {
            // هذا يحدث الطلب المحدد إلى معتمد
            $approve_sql = "
                UPDATE donation_requests
                SET status = 'Approved'
                WHERE request_id = ?
            ";

            // هذا يجهز استعلام الموافقة
            $approve_stmt = $conn->prepare($approve_sql);

            // هذا يربط رقم الطلب
            $approve_stmt->bind_param(
                "i",
                $request_id
            );

            // هذا ينفذ الموافقة
            $approve_stmt->execute();

            // هذا يقفل الاستعلام
            $approve_stmt->close();

            // هذا يحدث حالة التبرع إلى معتمد
            $donation_sql = "
                UPDATE food_donations
                SET status = 'Approved'
                WHERE donation_id = ?
                  AND donor_id = ?
            ";

            // هذا يجهز استعلام تحديث التبرع
            $donation_stmt =
                $conn->prepare($donation_sql);

            // هذا يربط رقم التبرع والمتبرع
            $donation_stmt->bind_param(
                "ii",
                $request["donation_id"],
                $donor_id
            );

            // هذا ينفذ التحديث
            $donation_stmt->execute();

            // هذا يقفل الاستعلام
            $donation_stmt->close();

            // هذا يرفض باقي الطلبات المعلقة لنفس التبرع
            $reject_others_sql = "
                UPDATE donation_requests
                SET status = 'Rejected'
                WHERE donation_id = ?
                  AND request_id != ?
                  AND status = 'Pending'
            ";

            // هذا يجهز الاستعلام
            $reject_others_stmt =
                $conn->prepare($reject_others_sql);

            // هذا يربط رقم التبرع والطلب
            $reject_others_stmt->bind_param(
                "ii",
                $request["donation_id"],
                $request_id
            );

            // هذا ينفذ رفض باقي الطلبات
            $reject_others_stmt->execute();

            // هذا يقفل الاستعلام
            $reject_others_stmt->close();

            // هذا يعتمد جميع التغييرات
            $conn->commit();

            // هذا يظهر رسالة نجاح
            $success_message =
                "The request has been approved successfully.";
        } catch (Throwable $error) {
            // هذا يلغي التغييرات إذا صار خطأ
            $conn->rollback();

            // هذا يظهر رسالة خطأ
            $error_message =
                "Unable to approve the request.";
        }
    } elseif ($action === "reject") {
        // هذا يحدث الطلب إلى مرفوض
        $reject_sql = "
            UPDATE donation_requests
            SET status = 'Rejected'
            WHERE request_id = ?
        ";

        // هذا يجهز الاستعلام
        $reject_stmt = $conn->prepare($reject_sql);

        // هذا يربط رقم الطلب
        $reject_stmt->bind_param(
            "i",
            $request_id
        );

        // هذا ينفذ الرفض
        if ($reject_stmt->execute()) {
            $success_message =
                "The request has been rejected successfully.";
        } else {
            $error_message =
                "Unable to reject the request.";
        }

        // هذا يقفل الاستعلام
        $reject_stmt->close();
    }
}

// هذا يجيب جميع طلبات الجمعيات
$requests = [];

$sql = "
    SELECT
        dr.request_id,
        dr.request_date,
        dr.status AS request_status,
        fd.donation_id,
        fd.food_name,
        fd.quantity,
        fd.quantity_unit,
        fd.pickup_date,
        fd.pickup_time,
        c.organization_name,
        c.email,
        c.phone,
        c.address
    FROM donation_requests dr
    INNER JOIN food_donations fd
        ON dr.donation_id = fd.donation_id
    INNER JOIN charities c
        ON dr.charity_id = c.charity_id
    WHERE fd.donor_id = ?
    ORDER BY
        CASE
            WHEN dr.status = 'Pending' THEN 1
            WHEN dr.status = 'Approved' THEN 2
            ELSE 3
        END,
        dr.request_date DESC
";

// هذا يجهز الاستعلام
$stmt = $conn->prepare($sql);

// هذا يربط رقم المتبرع
$stmt->bind_param("i", $donor_id);

// هذا ينفذ الاستعلام
$stmt->execute();

// هذا يجيب النتائج
$result = $stmt->get_result();

// هذا يحفظ الطلبات داخل المصفوفة
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

    <title>Donation Requests | FoodSave</title>

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
            <a href="add_donation.php">Add Donation</a>
            <a href="manage_donations.php">Manage Donations</a>
            <a href="requests.php" class="active">Requests</a>
        </nav>
    </aside>

    <main class="dashboard-main">

        <div class="page-heading">
            <div>
                <h1>Donation Requests</h1>
                <p>
                    Review requests submitted by charities.
                </p>
            </div>
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

        <section class="requests-grid">

            <?php if (!empty($requests)): ?>

                <?php foreach ($requests as $request): ?>
                    <article class="request-card">

                        <div class="request-card-header">
                            <div>
                                <h2>
                                    <?php
                                    echo htmlspecialchars(
                                        $request["organization_name"]
                                    );
                                    ?>
                                </h2>

                                <p>
                                    Requested:
                                    <?php
                                    echo htmlspecialchars(
                                        $request["food_name"]
                                    );
                                    ?>
                                </p>
                            </div>

                            <span class="status-badge status-<?php
                            echo strtolower(
                                $request["request_status"]
                            );
                            ?>">
                                <?php
                                echo htmlspecialchars(
                                    $request["request_status"]
                                );
                                ?>
                            </span>
                        </div>

                        <div class="request-information">
                            <p>
                                <strong>Quantity:</strong>
                                <?php
                                echo htmlspecialchars(
                                    $request["quantity"] .
                                    " " .
                                    $request["quantity_unit"]
                                );
                                ?>
                            </p>

                            <p>
                                <strong>Email:</strong>
                                <?php
                                echo htmlspecialchars(
                                    $request["email"]
                                );
                                ?>
                            </p>

                            <p>
                                <strong>Phone:</strong>
                                <?php
                                echo htmlspecialchars(
                                    $request["phone"]
                                );
                                ?>
                            </p>

                            <p>
                                <strong>Address:</strong>
                                <?php
                                echo htmlspecialchars(
                                    $request["address"]
                                );
                                ?>
                            </p>

                            <p>
                                <strong>Request Date:</strong>
                                <?php
                                echo htmlspecialchars(
                                    $request["request_date"]
                                );
                                ?>
                            </p>

                            <p>
                                <strong>Pickup:</strong>
                                <?php
                                echo htmlspecialchars(
                                    $request["pickup_date"] .
                                    " at " .
                                    $request["pickup_time"]
                                );
                                ?>
                            </p>
                        </div>

                        <div class="request-actions">

                            <a
                                href="donation_details.php?id=<?php
                                echo (int) $request["donation_id"];
                                ?>"
                                class="action-view"
                            >
                                View Donation
                            </a>

                            <?php
                            if (
                                $request["request_status"] === "Pending"
                            ):
                            ?>
                                <form
                                    method="POST"
                                    action="requests.php"
                                >
                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?php
                                        echo (int) $request["request_id"];
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="action"
                                        value="approve"
                                        class="approve-button"
                                    >
                                        Approve
                                    </button>

                                    <button
                                        type="submit"
                                        name="action"
                                        value="reject"
                                        class="reject-button"
                                    >
                                        Reject
                                    </button>
                                </form>
                            <?php endif; ?>

                        </div>

                    </article>
                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state dashboard-empty">
                    <h3>No Requests Found</h3>
                    <p>
                        No charity has requested your donations yet.
                    </p>
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>