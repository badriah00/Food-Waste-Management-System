<?php

// هذا يبدأ الجلسة
session_start();

// هذا يستدعي ملف الاتصال بقاعدة البيانات
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

// هذا يعالج عمليات تفعيل وتعليق وحذف الجمعيات
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["charity_id"], $_POST["action"])
) {
    // هذا ياخذ رقم الجمعية
    $charity_id = (int) $_POST["charity_id"];

    // هذا ياخذ نوع العملية
    $action = $_POST["action"];

    if ($charity_id <= 0) {
        $error_message = "Invalid charity account.";
    } elseif ($action === "activate") {
        // هذا يفعل حساب الجمعية
        $sql = "
            UPDATE charities
            SET account_status = 'Active'
            WHERE charity_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $charity_id);

        if ($stmt->execute()) {
            $success_message =
                "The charity account has been activated.";
        } else {
            $error_message =
                "Unable to activate the charity account.";
        }

        $stmt->close();
    } elseif ($action === "suspend") {
        // هذا يعلق حساب الجمعية
        $sql = "
            UPDATE charities
            SET account_status = 'Suspended'
            WHERE charity_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $charity_id);

        if ($stmt->execute()) {
            $success_message =
                "The charity account has been suspended.";
        } else {
            $error_message =
                "Unable to suspend the charity account.";
        }

        $stmt->close();
    } elseif ($action === "delete") {
        // هذا يتحقق إذا الجمعية عندها طلبات
        $check_sql = "
            SELECT COUNT(*) AS total
            FROM donation_requests
            WHERE charity_id = ?
        ";

        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $charity_id);
        $check_stmt->execute();

        $check_result =
            $check_stmt->get_result()->fetch_assoc();

        $request_count =
            (int) ($check_result["total"] ?? 0);

        $check_stmt->close();

        // هذا يمنع حذف الجمعية إذا عندها طلبات
        if ($request_count > 0) {
            $error_message =
                "This charity cannot be deleted because it has donation requests.";
        } else {
            // هذا يحذف حساب الجمعية
            $delete_sql = "
                DELETE FROM charities
                WHERE charity_id = ?
            ";

            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $charity_id);

            if ($delete_stmt->execute()) {
                $success_message =
                    "The charity account has been deleted.";
            } else {
                $error_message =
                    "Unable to delete the charity account.";
            }

            $delete_stmt->close();
        }
    }
}

// هذا ياخذ رقم الجمعية المطلوب عرضها
$view_id = (int) ($_GET["view"] ?? 0);

// هذا يحفظ تفاصيل الجمعية
$selected_charity = null;

// إذا فيه رقم عرض يجيب التفاصيل
if ($view_id > 0) {
    $details_sql = "
        SELECT
            c.*,
            (
                SELECT COUNT(*)
                FROM donation_requests dr
                WHERE dr.charity_id = c.charity_id
            ) AS total_requests
        FROM charities c
        WHERE c.charity_id = ?
        LIMIT 1
    ";

    $details_stmt = $conn->prepare($details_sql);
    $details_stmt->bind_param("i", $view_id);
    $details_stmt->execute();

    $selected_charity =
        $details_stmt->get_result()->fetch_assoc();

    $details_stmt->close();
}

// هذا يجيب جميع الجمعيات
$charities = [];

$sql = "
    SELECT
        charity_id,
        organization_name,
        registration_number,
        email,
        phone,
        address,
        account_status,
        created_at
    FROM charities
    ORDER BY created_at DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $charities[] = $row;
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

    <title>Manage Charities | FoodSave</title>

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
            <a href="manage_donors.php">Manage Donors</a>
            <a href="manage_charities.php" class="active">
                Manage Charities
            </a>
            <a href="manage_donations.php">Manage Donations</a>
            <a href="reports.php">Reports</a>
        </nav>
    </aside>

    <main class="dashboard-main">

        <div class="page-heading">
            <div>
                <h1>Manage Charities</h1>

                <p>
                    View and manage charity accounts.
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

        <?php if ($selected_charity): ?>

            <section class="admin-details-panel">

                <div class="panel-heading">
                    <h2>Charity Details</h2>

                    <a href="manage_charities.php">
                        Close
                    </a>
                </div>

                <div class="admin-details-grid">

                    <p>
                        <strong>Organization:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_charity["organization_name"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Registration Number:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_charity["registration_number"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_charity["email"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_charity["phone"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Address:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_charity["address"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Total Requests:</strong>
                        <?php
                        echo (int) $selected_charity["total_requests"];
                        ?>
                    </p>

                    <p>
                        <strong>Account Status:</strong>

                        <span class="status-badge status-<?php
                        echo strtolower(
                            $selected_charity["account_status"]
                        );
                        ?>">
                            <?php
                            echo htmlspecialchars(
                                $selected_charity["account_status"]
                            );
                            ?>
                        </span>
                    </p>

                    <p>
                        <strong>Created At:</strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_charity["created_at"]
                        );
                        ?>
                    </p>

                </div>

            </section>

        <?php endif; ?>

        <section class="dashboard-panel">

            <?php if (!empty($charities)): ?>

                <div class="table-wrapper">
                    <table class="dashboard-table">

                        <thead>
                        <tr>
                            <th>Organization</th>
                            <th>Registration Number</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($charities as $charity): ?>
                            <tr>
                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $charity["organization_name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $charity["registration_number"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $charity["email"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $charity["phone"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?php
                                    echo strtolower(
                                        $charity["account_status"]
                                    );
                                    ?>">
                                        <?php
                                        echo htmlspecialchars(
                                            $charity["account_status"]
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="table-actions">

                                        <a
                                            href="manage_charities.php?view=<?php
                                            echo (int) $charity["charity_id"];
                                            ?>"
                                            class="action-view"
                                        >
                                            View
                                        </a>

                                        <form
                                            method="POST"
                                            action="manage_charities.php"
                                        >
                                            <input
                                                type="hidden"
                                                name="charity_id"
                                                value="<?php
                                                echo (int) $charity["charity_id"];
                                                ?>"
                                            >

                                            <?php
                                            if (
                                                $charity["account_status"]
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
                                                onclick="return confirm('Delete this charity account?');"
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
                    <h3>No Charities Found</h3>
                    <p>There are no charity accounts.</p>
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>