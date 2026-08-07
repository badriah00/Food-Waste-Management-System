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

// هذا ياخذ رقم التبرع
$donation_id = (int) ($_GET["id"] ?? 0);

// هذا يجيب بيانات التبرع
$sql = "
    SELECT *
    FROM food_donations
    WHERE donation_id = ?
      AND donor_id = ?
    LIMIT 1
";

// هذا يجهز الاستعلام
$stmt = $conn->prepare($sql);

// هذا يربط الأرقام
$stmt->bind_param("ii", $donation_id, $donor_id);

// هذا ينفذ الاستعلام
$stmt->execute();

// هذا يحفظ بيانات التبرع
$donation = $stmt->get_result()->fetch_assoc();

// هذا يقفل الاستعلام
$stmt->close();

// إذا التبرع مو موجود يرجعه
if (!$donation) {
    header("Location: manage_donations.php");
    exit();
}

// هذا يجيب طلبات الجمعيات على التبرع
$requests = [];

$request_sql = "
    SELECT
        dr.request_id,
        dr.request_date,
        dr.status,
        c.organization_name,
        c.email,
        c.phone,
        c.address
    FROM donation_requests dr
    INNER JOIN charities c
        ON dr.charity_id = c.charity_id
    WHERE dr.donation_id = ?
    ORDER BY dr.request_date DESC
";

// هذا يجهز الاستعلام
$request_stmt = $conn->prepare($request_sql);

// هذا يربط رقم التبرع
$request_stmt->bind_param("i", $donation_id);

// هذا ينفذ الاستعلام
$request_stmt->execute();

// هذا يجيب النتائج
$request_result = $request_stmt->get_result();

// هذا يحفظ الطلبات
while ($row = $request_result->fetch_assoc()) {
    $requests[] = $row;
}

// هذا يقفل الاستعلام
$request_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Donation Details | FoodSave</title>

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
                <h1>Donation Details</h1>
                <p>Complete information about the donation.</p>
            </div>

            <a
                href="manage_donations.php"
                class="secondary-button"
            >
                Back
            </a>
        </div>

        <section class="details-grid">

            <div class="details-card">
                <h2>
                    <?php
                    echo htmlspecialchars($donation["food_name"]);
                    ?>
                </h2>

                <span class="status-badge status-<?php
                echo strtolower($donation["status"]);
                ?>">
                    <?php
                    echo htmlspecialchars($donation["status"]);
                    ?>
                </span>

                <div class="details-list">
                    <p>
                        <strong>Category:</strong>
                        <?php
                        echo htmlspecialchars(
                            $donation["category"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Quantity:</strong>
                        <?php
                        echo htmlspecialchars(
                            $donation["quantity"] .
                            " " .
                            $donation["quantity_unit"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Description:</strong>
                        <?php
                        echo htmlspecialchars(
                            $donation["description"] ?: "No description"
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Preparation Date:</strong>
                        <?php
                        echo htmlspecialchars(
                            $donation["preparation_date"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Expiry Date:</strong>
                        <?php
                        echo htmlspecialchars(
                            $donation["expiry_date"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Pickup Location:</strong>
                        <?php
                        echo htmlspecialchars(
                            $donation["pickup_location"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Pickup Date:</strong>
                        <?php
                        echo htmlspecialchars(
                            $donation["pickup_date"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Pickup Time:</strong>
                        <?php
                        echo htmlspecialchars(
                            $donation["pickup_time"]
                        );
                        ?>
                    </p>
                </div>
            </div>

            <div class="details-card">
                <h2>Charity Requests</h2>

                <?php if (!empty($requests)): ?>

                    <?php foreach ($requests as $request): ?>
                        <div class="request-summary-card">
                            <div class="request-card-header">
                                <h3>
                                    <?php
                                    echo htmlspecialchars(
                                        $request["organization_name"]
                                    );
                                    ?>
                                </h3>

                                <span class="status-badge status-<?php
                                echo strtolower($request["status"]);
                                ?>">
                                    <?php
                                    echo htmlspecialchars(
                                        $request["status"]
                                    );
                                    ?>
                                </span>
                            </div>

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

                            <a
                                href="requests.php"
                                class="table-link"
                            >
                                Manage Request
                            </a>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty-state dashboard-empty">
                        <h3>No Requests</h3>
                        <p>
                            No charity has requested this donation yet.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

</body>
</html>