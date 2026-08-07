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

// هذا ياخذ رقم التبرع من الرابط
$donation_id = (int) ($_GET["id"] ?? 0);

// هذه رسائل الصفحة
$error_message = "";
$success_message = "";

// هذا يتحقق إن رقم التبرع صحيح
if ($donation_id <= 0) {
    header("Location: manage_donations.php");
    exit();
}

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

// هذا يمنع تعديل التبرع المعتمد
if ($donation["status"] === "Approved") {
    $error_message =
        "Approved donations cannot be edited.";
}

// هذا يتحقق إذا المستخدم أرسل التعديل
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    $donation["status"] !== "Approved"
) {
    // هذا ياخذ البيانات الجديدة
    $food_name = trim($_POST["food_name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $quantity = (float) ($_POST["quantity"] ?? 0);
    $quantity_unit = trim($_POST["quantity_unit"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $preparation_date = $_POST["preparation_date"] ?? "";
    $expiry_date = $_POST["expiry_date"] ?? "";
    $pickup_location = trim($_POST["pickup_location"] ?? "");
    $pickup_date = $_POST["pickup_date"] ?? "";
    $pickup_time = $_POST["pickup_time"] ?? "";

    // هذا يتحقق من البيانات
    if (
        $food_name === "" ||
        $category === "" ||
        $quantity <= 0 ||
        $quantity_unit === "" ||
        $preparation_date === "" ||
        $expiry_date === "" ||
        $pickup_location === "" ||
        $pickup_date === "" ||
        $pickup_time === ""
    ) {
        $error_message =
            "Please complete all required fields.";
    } elseif ($expiry_date < $preparation_date) {
        $error_message =
            "Expiry date cannot be before preparation date.";
    } else {
        // هذا يحدث بيانات التبرع
        $update_sql = "
            UPDATE food_donations
            SET
                food_name = ?,
                category = ?,
                quantity = ?,
                quantity_unit = ?,
                description = ?,
                preparation_date = ?,
                expiry_date = ?,
                pickup_location = ?,
                pickup_date = ?,
                pickup_time = ?
            WHERE donation_id = ?
              AND donor_id = ?
        ";

        // هذا يجهز الاستعلام
        $update_stmt = $conn->prepare($update_sql);

        // هذا يربط البيانات
        $update_stmt->bind_param(
            "ssdsssssssii",
            $food_name,
            $category,
            $quantity,
            $quantity_unit,
            $description,
            $preparation_date,
            $expiry_date,
            $pickup_location,
            $pickup_date,
            $pickup_time,
            $donation_id,
            $donor_id
        );

        // هذا ينفذ التحديث
        if ($update_stmt->execute()) {
            $success_message =
                "The donation has been updated successfully.";

            // هذا يحدث بيانات التبرع المعروضة
            $donation = array_merge(
                $donation,
                $_POST
            );
        } else {
            $error_message =
                "Unable to update the donation.";
        }

        // هذا يقفل الاستعلام
        $update_stmt->close();
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

    <title>Edit Donation | FoodSave</title>

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
                <h1>Edit Donation</h1>
                <p>Update the selected food donation.</p>
            </div>

            <a
                href="manage_donations.php"
                class="secondary-button"
            >
                Back
            </a>
        </div>

        <section class="dashboard-form-panel">

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

            <form
                method="POST"
                action="edit_donation.php?id=<?php echo $donation_id; ?>"
            >

                <div class="form-row">
                    <div class="form-group">
                        <label>Food Name</label>

                        <input
                            type="text"
                            name="food_name"
                            value="<?php
                            echo htmlspecialchars(
                                $donation["food_name"]
                            );
                            ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Category</label>

                        <input
                            type="text"
                            name="category"
                            value="<?php
                            echo htmlspecialchars(
                                $donation["category"]
                            );
                            ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Quantity</label>

                        <input
                            type="number"
                            name="quantity"
                            min="0.01"
                            step="0.01"
                            value="<?php
                            echo htmlspecialchars(
                                $donation["quantity"]
                            );
                            ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Quantity Unit</label>

                        <input
                            type="text"
                            name="quantity_unit"
                            value="<?php
                            echo htmlspecialchars(
                                $donation["quantity_unit"]
                            );
                            ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>

                    <textarea name="description"><?php
                    echo htmlspecialchars(
                        $donation["description"]
                    );
                    ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Preparation Date</label>

                        <input
                            type="date"
                            name="preparation_date"
                            value="<?php
                            echo htmlspecialchars(
                                $donation["preparation_date"]
                            );
                            ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Expiry Date</label>

                        <input
                            type="date"
                            name="expiry_date"
                            value="<?php
                            echo htmlspecialchars(
                                $donation["expiry_date"]
                            );
                            ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Pickup Location</label>

                    <input
                        type="text"
                        name="pickup_location"
                        value="<?php
                        echo htmlspecialchars(
                            $donation["pickup_location"]
                        );
                        ?>"
                        required
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Pickup Date</label>

                        <input
                            type="date"
                            name="pickup_date"
                            value="<?php
                            echo htmlspecialchars(
                                $donation["pickup_date"]
                            );
                            ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Pickup Time</label>

                        <input
                            type="time"
                            name="pickup_time"
                            value="<?php
                            echo htmlspecialchars(
                                $donation["pickup_time"]
                            );
                            ?>"
                            required
                        >
                    </div>
                </div>

                <?php if ($donation["status"] !== "Approved"): ?>
                    <button type="submit" class="submit-button">
                        Update Donation
                    </button>
                <?php endif; ?>

            </form>

        </section>

    </main>

</div>

</body>
</html>