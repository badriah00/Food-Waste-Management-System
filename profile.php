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

// هذه متغيرات رسائل الصفحة
$error_message = "";
$success_message = "";

// هذا يجيب بيانات المتبرع الحالية
$sql = "
    SELECT
        full_name,
        donor_type,
        email,
        phone,
        address
    FROM donors
    WHERE donor_id = ?
    LIMIT 1
";

// هذا يجهز الاستعلام
$stmt = $conn->prepare($sql);

// هذا يربط رقم المتبرع
$stmt->bind_param("i", $donor_id);

// هذا ينفذ الاستعلام
$stmt->execute();

// هذا يحفظ بيانات المتبرع
$donor = $stmt->get_result()->fetch_assoc();

// هذا يقفل الاستعلام
$stmt->close();

// هذا يتحقق إذا المستخدم ضغط تحديث
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // هذا ياخذ البيانات من النموذج
    $full_name = trim($_POST["full_name"] ?? "");
    $donor_type = $_POST["donor_type"] ?? "";
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";

    // هذه أنواع المتبرعين المسموحة
    $allowed_types = [
        "Individual",
        "Restaurant",
        "Hotel",
        "Supermarket",
        "Other"
    ];

    // هذا يتحقق من الحقول المطلوبة
    if (
        $full_name === "" ||
        $email === "" ||
        $phone === "" ||
        $address === ""
    ) {
        $error_message = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (!in_array($donor_type, $allowed_types, true)) {
        $error_message = "Please select a valid donor type.";
    } elseif ($password !== "" && strlen($password) < 8) {
        $error_message = "The password must contain at least 8 characters.";
    } else {
        // هذا يتحقق إن البريد مو مستخدم من متبرع ثاني
        $check_sql = "
            SELECT donor_id
            FROM donors
            WHERE email = ?
              AND donor_id != ?
            LIMIT 1
        ";

        // هذا يجهز استعلام التحقق
        $check_stmt = $conn->prepare($check_sql);

        // هذا يربط البريد ورقم المتبرع
        $check_stmt->bind_param("si", $email, $donor_id);

        // هذا ينفذ الاستعلام
        $check_stmt->execute();

        // هذا يتحقق إذا البريد موجود
        $email_exists =
            $check_stmt->get_result()->num_rows > 0;

        // هذا يقفل الاستعلام
        $check_stmt->close();

        // إذا البريد مستخدم يظهر رسالة
        if ($email_exists) {
            $error_message = "This email address is already registered.";
        } else {
            // إذا كتب كلمة مرور جديدة يتم تحديثها
            if ($password !== "") {
                // هذا يشفر كلمة المرور
                $password_hash =
                    password_hash($password, PASSWORD_DEFAULT);

                // هذا استعلام تحديث البيانات مع كلمة المرور
                $update_sql = "
                    UPDATE donors
                    SET
                        full_name = ?,
                        donor_type = ?,
                        email = ?,
                        phone = ?,
                        address = ?,
                        password = ?
                    WHERE donor_id = ?
                ";

                // هذا يجهز الاستعلام
                $update_stmt = $conn->prepare($update_sql);

                // هذا يربط البيانات
                $update_stmt->bind_param(
                    "ssssssi",
                    $full_name,
                    $donor_type,
                    $email,
                    $phone,
                    $address,
                    $password_hash,
                    $donor_id
                );
            } else {
                // هذا استعلام تحديث البيانات بدون كلمة المرور
                $update_sql = "
                    UPDATE donors
                    SET
                        full_name = ?,
                        donor_type = ?,
                        email = ?,
                        phone = ?,
                        address = ?
                    WHERE donor_id = ?
                ";

                // هذا يجهز الاستعلام
                $update_stmt = $conn->prepare($update_sql);

                // هذا يربط البيانات
                $update_stmt->bind_param(
                    "sssssi",
                    $full_name,
                    $donor_type,
                    $email,
                    $phone,
                    $address,
                    $donor_id
                );
            }

            // هذا ينفذ التحديث
            if ($update_stmt->execute()) {
                // هذا يحدث الاسم والبريد داخل الجلسة
                $_SESSION["user_name"] = $full_name;
                $_SESSION["user_email"] = $email;

                // هذا يظهر رسالة نجاح
                $success_message =
                    "Your profile has been updated successfully.";

                // هذا يحدث البيانات المعروضة
                $donor["full_name"] = $full_name;
                $donor["donor_type"] = $donor_type;
                $donor["email"] = $email;
                $donor["phone"] = $phone;
                $donor["address"] = $address;
            } else {
                // هذا يظهر رسالة إذا صار خطأ
                $error_message =
                    "Unable to update your profile.";
            }

            // هذا يقفل استعلام التحديث
            $update_stmt->close();
        }
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

    <title>My Profile | FoodSave</title>

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
            <a href="profile.php" class="active">My Profile</a>
            <a href="add_donation.php">Add Donation</a>
            <a href="manage_donations.php">Manage Donations</a>
            <a href="requests.php">Requests</a>
        </nav>
    </aside>

    <main class="dashboard-main">

        <div class="page-heading">
            <div>
                <h1>My Profile</h1>
                <p>View and update your account information.</p>
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

            <form method="POST" action="profile.php">

                <div class="form-row">

                    <div class="form-group">
                        <label for="full_name">Full Name</label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="<?php
                            echo htmlspecialchars(
                                $donor["full_name"]
                            );
                            ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="donor_type">Donor Type</label>

                        <select
                            id="donor_type"
                            name="donor_type"
                            required
                        >
                            <?php
                            $types = [
                                "Individual",
                                "Restaurant",
                                "Hotel",
                                "Supermarket",
                                "Other"
                            ];

                            foreach ($types as $type):
                            ?>
                                <option
                                    value="<?php
                                    echo htmlspecialchars($type);
                                    ?>"
                                    <?php
                                    echo $donor["donor_type"] === $type
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    <?php
                                    echo htmlspecialchars($type);
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label for="email">Email Address</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php
                            echo htmlspecialchars($donor["email"]);
                            ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>

                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="<?php
                            echo htmlspecialchars($donor["phone"]);
                            ?>"
                            required
                        >
                    </div>

                </div>

                <div class="form-group">
                    <label for="address">Address</label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="<?php
                        echo htmlspecialchars($donor["address"]);
                        ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Leave empty to keep the current password"
                    >
                </div>

                <button type="submit" class="submit-button">
                    Update Profile
                </button>

            </form>

        </section>

    </main>

</div>

</body>
</html>