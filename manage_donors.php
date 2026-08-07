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

// هذي رسائل الصفحة
$error_message = "";
$success_message = "";

// هذا يتحقق إذا المدير ضغط تفعيل أو تعليق أو حذف
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["donor_id"], $_POST["action"])
) {
    // هذا ياخذ رقم المتبرع
    $donor_id = (int) $_POST["donor_id"];

    // هذا ياخذ نوع العملية
    $action = $_POST["action"];

    // هذا يتأكد إن رقم المتبرع صحيح
    if ($donor_id <= 0) {
        $error_message = "Invalid donor account.";
    } elseif ($action === "activate") {
        // هذا يفعل حساب المتبرع
        $sql = "
            UPDATE donors
            SET account_status = 'Active'
            WHERE donor_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $donor_id);

        if ($stmt->execute()) {
            $success_message =
                "The donor account has been activated.";
        } else {
            $error_message =
                "Unable to activate the donor account.";
        }

        $stmt->close();
    } elseif ($action === "suspend") {
        // هذا يعلق حساب المتبرع
        $sql = "
            UPDATE donors
            SET account_status = 'Suspended'
            WHERE donor_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $donor_id);

        if ($stmt->execute()) {
            $success_message =
                "The donor account has been suspended.";
        } else {
            $error_message =
                "Unable to suspend the donor account.";
        }

        $stmt->close();
    } elseif ($action === "delete") {
        // هذا يتحقق إذا المتبرع عنده تبرعات
        $check_sql = "
            SELECT COUNT(*) AS total
            FROM food_donations
            WHERE donor_id = ?
        ";

        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $donor_id);
        $check_stmt->execute();

        $check_result =
            $check_stmt->get_result()->fetch_assoc();

        $donation_count =
            (int) ($check_result["total"] ?? 0);

        $check_stmt->close();

        // هذا يمنع حذف المتبرع إذا عنده تبرعات
        if ($donation_count > 0) {
            $error_message =
                "This donor cannot be deleted because they have donations.";
        } else {
            // هذا يحذف حساب المتبرع
            $delete_sql = "
                DELETE FROM donors
                WHERE donor_id = ?
            ";

            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $donor_id);

            if ($delete_stmt->execute()) {
                $success_message =
                    "The donor account has been deleted.";
            } else {
                $error_message =
                    "Unable to delete the donor account.";
            }

            $delete_stmt->close();
        }
    }
}

// هذا ياخذ رقم المتبرع المطلوب عرضه
$view_id = (int) ($_GET["view"] ?? 0);

// هذا يحفظ تفاصيل المتبرع
$selected_donor = null;

// إذا فيه رقم عرض يجيب تفاصيل المتبرع
if ($view_id > 0) {
    $details_sql = "
        SELECT
            d.*,
            (
                SELECT COUNT(*)
                FROM food_donations fd
                WHERE fd.donor_id = d.donor_id
            ) AS total_donations
        FROM donors d
        WHERE d.donor_id = ?
        LIMIT 1
    ";

    $details_stmt = $conn->prepare($details_sql);
    $details_stmt->bind_param("i", $view_id);
    $details_stmt->execute();

    $selected_donor =
        $details_stmt->get_result()->fetch_assoc();

    $details_stmt->close();
}

// هذا يجيب جميع المتبرعين
$donors = [];

$sql = "
    SELECT
        donor_id,
        full_name,
        donor_type,
        email,
        phone,
        address,
        account_status,
        created_at
    FROM donors
    ORDER BY created_at DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $donors[] = $row;
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

    <title>Manage Donors | FoodSave</title>

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

    <aside class="dashboard-sidebar admin-sidebar">
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_donors.php" class="active">
                Manage Donors
            </a>
            <a href="manage_charities.php">Manage Charities</a>
            <a href="manage_donations.php">Manage Donations</a>
            <a href="reports.php">Reports</a>
        </nav>
    </aside>

    <main class="dashboard-main">

        <div class="page-heading">
            <div>
                <h1>Manage Donors</h1>

                <p>
                    View and manage donor accounts.
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

        <?php if ($selected_donor): ?>

            <section class="admin-details-panel">

                <div class="panel-heading">
                    <h2>Donor Details</h2>

                    <a href="manage_donors.php">
                        Close
                    </a>
                </div>

                <div class="admin-details-grid">

                    <p>
                        <strong>Full Name:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_donor["full_name"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Donor Type:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_donor["donor_type"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_donor["email"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_donor["phone"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Address:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_donor["address"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Total Donations:</strong>
                        <?php
                        echo (int) $selected_donor["total_donations"];
                        ?>
                    </p>

                    <p>
                        <strong>Account Status:</strong>

                        <span class="status-badge status-<?php
                        echo strtolower(
                            $selected_donor["account_status"]
                        );
                        ?>">
                            <?php
                            echo htmlspecialchars(
                                $selected_donor["account_status"]
                            );
                            ?>
                        </span>
                    </p>

                    <p>
                        <strong>Created At:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_donor["created_at"]
                        );
                        ?>
                    </p>

                </div>

            </section>

        <?php endif; ?>

        <section class="dashboard-panel">

            <?php if (!empty($donors)): ?>

                <div class="table-wrapper">
                    <table class="dashboard-table">

                        <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Donor Type</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($donors as $donor): ?>
                            <tr>
                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $donor["full_name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $donor["donor_type"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $donor["email"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $donor["phone"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?php
                                    echo strtolower(
                                        $donor["account_status"]
                                    );
                                    ?>">
                                        <?php
                                        echo htmlspecialchars(
                                            $donor["account_status"]
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="table-actions">

                                        <a
                                            href="manage_donors.php?view=<?php
                                            echo (int) $donor["donor_id"];
                                            ?>"
                                            class="action-view"
                                        >
                                            View
                                        </a>

                                        <form
                                            method="POST"
                                            action="manage_donors.php"
                                        >
                                            <input
                                                type="hidden"
                                                name="donor_id"
                                                value="<?php
                                                echo (int) $donor["donor_id"];
                                                ?>"
                                            >

                                            <?php
                                            if (
                                                $donor["account_status"]
                                                === "Active"
                                            ):
                                            ?>
                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="suspend"
                                                    class="action-suspend"
                                                >
                                                    Suspend
                                                </button>
                                            <?php else: ?>
                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="activate"
                                                    class="action-activate"
                                                >
                                                    Activate
                                                </button>
                                            <?php endif; ?>

                                            <button
                                                type="submit"
                                                name="action"
                                                value="delete"
                                                class="action-delete"
                                                onclick="return confirm('Delete this donor account?');"
                                            >
                                                Delete
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            <?php else: ?>

                <div class="empty-state dashboard-empty">
                    <h3>No Donors Found</h3>
                    <p>There are no donor accounts.</p>
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>