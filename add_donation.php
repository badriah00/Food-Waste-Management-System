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

// هذا يتحقق إذا المستخدم أرسل النموذج
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // هذا ياخذ بيانات التبرع
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

    // هذا يتحقق من الحقول المطلوبة
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
    } elseif ($pickup_date > $expiry_date) {
        $error_message =
            "Pickup date cannot be after the expiry date.";
    } else {
        // هذا استعلام إضافة التبرع
        $sql = "
            INSERT INTO food_donations (
                donor_id,
                food_name,
                category,
                quantity,
                quantity_unit,
                description,
                preparation_date,
                expiry_date,
                pickup_location,
                pickup_date,
                pickup_time,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available')
        ";

        // هذا يجهز الاستعلام
        $stmt = $conn->prepare($sql);

        // هذا يربط البيانات
        $stmt->bind_param(
            "issdsssssss",
            $donor_id,
            $food_name,
            $category,
            $quantity,
            $quantity_unit,
            $description,
            $preparation_date,
            $expiry_date,
            $pickup_location,
            $pickup_date,
            $pickup_time
        );

        // هذا ينفذ الإضافة
        if ($stmt->execute()) {
            // هذا يظهر رسالة نجاح
            $success_message =
                "The donation has been added successfully.";

            // هذا يفرغ البيانات بعد الإضافة
            $_POST = [];
        } else {
            // هذا يظهر رسالة خطأ
            $error_message =
                "Unable to add the donation.";
        }

        // هذا يقفل الاستعلام
        $stmt->close();
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

    <title>Add Donation | FoodSave</title>

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
            <a href="add_donation.php" class="active">Add Donation</a>
            <a href="manage_donations.php">Manage Donations</a>
            <a href="requests.php">Requests</a>
        </nav>
    </aside>

    <main class="dashboard-main">

        <div class="page-heading">
            <div>
                <h1>Add Food Donation</h1>
                <p>Enter the food and pickup information.</p>
            </div>
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

            <form method="POST" action="add_donation.php">

                <div class="form-row">

                    <div class="form-group">
                        <label for="food_name">Food Name</label>

                        <input
                            type="text"
                            id="food_name"
                            name="food_name"
                            value="<?php
                            echo htmlspecialchars(
                                $_POST["food_name"] ?? ""
                            );
                            ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>

                        <select
                            id="category"
                            name="category"
                            required
                        >
                            <option value="">Select category</option>
                            <option value="Cooked Meals">Cooked Meals</option>
                            <option value="Bakery">Bakery</option>
                            <option value="Fruits">Fruits</option>
                            <option value="Vegetables">Vegetables</option>
                            <option value="Dairy">Dairy</option>
                            <option value="Dry Food">Dry Food</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label for="quantity">Quantity</label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            min="0.01"
                            step="0.01"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="quantity_unit">
                            Quantity Unit
                        </label>

                        <select
                            id="quantity_unit"
                            name="quantity_unit"
                            required
                        >
                            <option value="">Select unit</option>
                            <option value="Meals">Meals</option>
                            <option value="Kilograms">Kilograms</option>
                            <option value="Boxes">Boxes</option>
                            <option value="Bags">Bags</option>
                            <option value="Pieces">Pieces</option>
                            <option value="Liters">Liters</option>
                        </select>
                    </div>

                </div>

                <div class="form-group">
                    <label for="description">Description</label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Enter additional food information"
                    ><?php
                    echo htmlspecialchars(
                        $_POST["description"] ?? ""
                    );
                    ?></textarea>
                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label for="preparation_date">
                            Preparation Date
                        </label>

                        <input
                            type="date"
                            id="preparation_date"
                            name="preparation_date"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>

                        <input
                            type="date"
                            id="expiry_date"
                            name="expiry_date"
                            required
                        >
                    </div>

                </div>

                <div class="form-group">
                    <label for="pickup_location">
                        Pickup Location
                    </label>

                    <input
                        type="text"
                        id="pickup_location"
                        name="pickup_location"
                        required
                    >
                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label for="pickup_date">Pickup Date</label>

                        <input
                            type="date"
                            id="pickup_date"
                            name="pickup_date"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="pickup_time">Pickup Time</label>

                        <input
                            type="time"
                            id="pickup_time"
                            name="pickup_time"
                            required
                        >
                    </div>

                </div>

                <button type="submit" class="submit-button">
                    Add Donation
                </button>

            </form>

        </section>

    </main>

</div>

</body>
</html>