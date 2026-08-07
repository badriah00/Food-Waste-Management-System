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

// هذا ياخذ كلمة البحث من الرابط
$search = trim($_GET["search"] ?? "");

// هذا ياخذ التصنيف من الرابط
$category = trim($_GET["category"] ?? "");

// هذي التصنيفات المسموحة
$allowed_categories = [
    "Cooked Meals",
    "Bakery",
    "Fruits",
    "Vegetables",
    "Dairy",
    "Dry Food",
    "Other"
];

// إذا التصنيف مو صحيح نخليه فاضي
if (
    $category !== "" &&
    !in_array($category, $allowed_categories, true)
) {
    $category = "";
}

// هذا يبدأ الاستعلام الأساسي
$sql = "
    SELECT
        fd.donation_id,
        fd.food_name,
        fd.category,
        fd.quantity,
        fd.quantity_unit,
        fd.expiry_date,
        fd.pickup_location,
        d.full_name AS donor_name,
        CASE
            WHEN dr.request_id IS NULL THEN 0
            ELSE 1
        END AS already_requested
    FROM food_donations fd
    INNER JOIN donors d
        ON fd.donor_id = d.donor_id
    LEFT JOIN donation_requests dr
        ON fd.donation_id = dr.donation_id
        AND dr.charity_id = ?
    WHERE fd.status = 'Available'
      AND fd.expiry_date >= CURDATE()
";

// هذي أنواع البيانات اللي بنربطها
$types = "i";

// هذي القيم اللي بنربطها بالاستعلام
$parameters = [$charity_id];

// إذا فيه كلمة بحث نضيفها للاستعلام
if ($search !== "") {
    $sql .= " AND fd.food_name LIKE ?";

    $types .= "s";

    $search_value = "%" . $search . "%";

    $parameters[] = $search_value;
}

// إذا فيه تصنيف نضيفه للاستعلام
if ($category !== "") {
    $sql .= " AND fd.category = ?";

    $types .= "s";

    $parameters[] = $category;
}

// هذا يرتب التبرعات من الأقرب انتهاء
$sql .= " ORDER BY fd.expiry_date ASC, fd.created_at DESC";

// هذا يجهز الاستعلام
$stmt = $conn->prepare($sql);

// هذا يربط القيم بالاستعلام
$stmt->bind_param($types, ...$parameters);

// هذا ينفذ الاستعلام
$stmt->execute();

// هذا يجيب النتائج
$result = $stmt->get_result();

// هذي المصفوفة بنحفظ فيها التبرعات
$donations = [];

// هذا يضيف كل تبرع داخل المصفوفة
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

    <title>Available Donations | FoodSave</title>

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
            <a href="donations.php" class="active">
                Available Donations
            </a>
            <a href="my_requests.php">My Requests</a>
        </nav>
    </aside>

    <main class="dashboard-main">

        <div class="page-heading">
            <div>
                <h1>Available Donations</h1>

                <p>
                    Search and request available food donations.
                </p>
            </div>
        </div>

        <section class="dashboard-panel donation-search-panel">

            <form
                method="GET"
                action="donations.php"
                class="donation-filter-form"
            >

                <div class="form-group">
                    <label for="search">Search by Food Name</label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Enter food name"
                    >
                </div>

                <div class="form-group">
                    <label for="category">Category</label>

                    <select id="category" name="category">
                        <option value="">All Categories</option>

                        <?php foreach ($allowed_categories as $item): ?>
                            <option
                                value="<?php echo htmlspecialchars($item); ?>"
                                <?php
                                echo $category === $item
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                <?php echo htmlspecialchars($item); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="primary-button">
                        Search
                    </button>

                    <a
                        href="donations.php"
                        class="secondary-button"
                    >
                        Reset
                    </a>
                </div>

            </form>

        </section>

        <section class="available-donations-grid">

            <?php if (!empty($donations)): ?>

                <?php foreach ($donations as $donation): ?>
                    <article class="available-donation-card">

                        <div class="donation-card-top">
                            <span class="donation-category">
                                <?php
                                echo htmlspecialchars(
                                    $donation["category"]
                                );
                                ?>
                            </span>

                            <span class="status-badge status-available">
                                Available
                            </span>
                        </div>

                        <h2>
                            <?php
                            echo htmlspecialchars(
                                $donation["food_name"]
                            );
                            ?>
                        </h2>

                        <p class="donor-name">
                            Donor:
                            <?php
                            echo htmlspecialchars(
                                $donation["donor_name"]
                            );
                            ?>
                        </p>

                        <div class="donation-card-information">

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

                        </div>

                        <div class="donation-card-actions">

                            <a
                                href="donation_details.php?id=<?php
                                echo (int) $donation["donation_id"];
                                ?>"
                                class="secondary-button"
                            >
                                View Details
                            </a>

                            <?php if ((int) $donation["already_requested"] === 0): ?>

                                <a
                                    href="donation_details.php?id=<?php
                                    echo (int) $donation["donation_id"];
                                    ?>#request-form"
                                    class="primary-button"
                                >
                                    Request Donation
                                </a>

                            <?php else: ?>

                                <span class="requested-label">
                                    Request Submitted
                                </span>

                            <?php endif; ?>

                        </div>

                    </article>
                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state dashboard-empty">
                    <h3>No Donations Found</h3>

                    <p>
                        No available donations match your search.
                    </p>
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>