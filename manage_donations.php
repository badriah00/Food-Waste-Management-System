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
    // إذا مو متبرع يرجعه لتسجيل الدخول
    header("Location: ../login.php");
    exit();
}

// هذا ياخذ رقم المتبرع
$donor_id = (int) $_SESSION["user_id"];

// هذه رسائل الصفحة
$error_message = "";
$success_message = "";

// هذا يتحقق إذا المستخدم طلب حذف تبرع
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_donation_id"])
) {
    // هذا ياخذ رقم التبرع
    $donation_id =
        (int) $_POST["delete_donation_id"];

    // هذا يتحقق إن التبرع يتبع للمتبرع الحالي
    $check_sql = "
        SELECT status
        FROM food_donations
        WHERE donation_id = ?
          AND donor_id = ?
        LIMIT 1
    ";

    // هذا يجهز الاستعلام
    $check_stmt = $conn->prepare($check_sql);

    // هذا يربط رقم التبرع والمتبرع
    $check_stmt->bind_param(
        "ii",
        $donation_id,
        $donor_id
    );

    // هذا ينفذ الاستعلام
    $check_stmt->execute();

    // هذا يجيب التبرع
    $donation =
        $check_stmt->get_result()->fetch_assoc();

    // هذا يقفل الاستعلام
    $check_stmt->close();

    // هذا يتحقق إن التبرع موجود
    if (!$donation) {
        $error_message = "Donation not found.";
    } elseif ($donation["status"] === "Approved") {
        $error_message =
            "Approved donations cannot be deleted.";
    } else {
        // هذا يحذف الطلبات المرتبطة بالتبرع أول
        $delete_requests_sql = "
            DELETE FROM donation_requests
            WHERE donation_id = ?
        ";

        // هذا يجهز استعلام حذف الطلبات
        $delete_requests_stmt =
            $conn->prepare($delete_requests_sql);

        // هذا يربط رقم التبرع
        $delete_requests_stmt->bind_param(
            "i",
            $donation_id
        );

        // هذا ينفذ حذف الطلبات
        $delete_requests_stmt->execute();

        // هذا يقفل الاستعلام
        $delete_requests_stmt->close();

        // هذا يحذف التبرع
        $delete_sql = "
            DELETE FROM food_donations
            WHERE donation_id = ?
              AND donor_id = ?
        ";

        // هذا يجهز استعلام الحذف
        $delete_stmt = $conn->prepare($delete_sql);

        // هذا يربط الأرقام
        $delete_stmt->bind_param(
            "ii",
            $donation_id,
            $donor_id
        );

        // هذا ينفذ الحذف
        if ($delete_stmt->execute()) {
            $success_message =
                "The donation has been deleted successfully.";
        } else {
            $error_message =
                "Unable to delete the donation.";
        }

        // هذا يقفل الاستعلام
        $delete_stmt->close();
    }
}

// هذا يجيب جميع تبرعات المتبرع
$donations = [];

$sql = "
    SELECT
        donation_id,
        food_name,
        category,
        quantity,
        quantity_unit,
        expiry_date,
        pickup_date,
        status,
        created_at
    FROM food_donations
    WHERE donor_id = ?
    ORDER BY created_at DESC
";

// هذا يجهز الاستعلام
$stmt = $conn->prepare($sql);

// هذا يربط رقم المتبرع
$stmt->bind_param("i", $donor_id);

// هذا ينفذ الاستعلام
$stmt->execute();

// هذا يجيب النتائج
$result = $stmt->get_result();

// هذا يحفظ النتائج داخل مصفوفة
while ($row = $result->fetch_assoc()) {
    $donations[] = $row;
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

    <title>Manage Donations | FoodSave</title>

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
            <a href="manage_donations.php" class="active">
                Manage Donations
            </a>
            <a href="requests.php">Requests</a>
        </nav>
    </aside>

    <main class="dashboard-main">

        <div class="page-heading">
            <div>
                <h1>Manage Donations</h1>
                <p>View, edit, and delete your donations.</p>
            </div>

            <a href="add_donation.php" class="primary-button">
                Add Donation
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

            <?php if (!empty($donations)): ?>

                <div class="table-wrapper">
                    <table class="dashboard-table">
                        <thead>
                        <tr>
                            <th>Food Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Pickup Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($donations as $donation): ?>
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
                                    <?php
                                    echo htmlspecialchars(
                                        $donation["pickup_date"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?php
                                    echo strtolower(
                                        $donation["status"]
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
                                    <div class="table-actions">

                                        <a
                                            href="donation_details.php?id=<?php
                                            echo (int) $donation["donation_id"];
                                            ?>"
                                            class="action-view"
                                        >
                                            View
                                        </a>

                                        <?php
                                        if (
                                            $donation["status"] !== "Approved"
                                        ):
                                        ?>
                                            <a
                                                href="edit_donation.php?id=<?php
                                                echo (int) $donation["donation_id"];
                                                ?>"
                                                class="action-edit"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="manage_donations.php"
                                                onsubmit="return confirm('Are you sure you want to delete this donation?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="delete_donation_id"
                                                    value="<?php
                                                    echo (int) $donation["donation_id"];
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action-delete"
                                                >
                                                    Delete
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
                    <h3>No Donations Found</h3>
                    <p>You have not added any donations yet.</p>

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