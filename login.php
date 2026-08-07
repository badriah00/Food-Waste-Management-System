<?php
session_start();

require_once "connect_db.php";

// Redirect logged-in users to their dashboards
if (isset($_SESSION["user_id"], $_SESSION["user_type"])) {
    if ($_SESSION["user_type"] === "donor") {
        header("Location: donor/dashboard.php");
        exit();
    }

    if ($_SESSION["user_type"] === "charity") {
        header("Location: charity/dashboard.php");
        exit();
    }

    if ($_SESSION["user_type"] === "admin") {
        header("Location: admin/dashboard.php");
        exit();
    }
}

$error_message = "";
$email = "";
$account_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $account_type = $_POST["account_type"] ?? "";

    if ($email === "" || $password === "" || $account_type === "") {
        $error_message = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        if ($account_type === "donor") {
            $sql = "
                SELECT
                    donor_id AS user_id,
                    full_name AS user_name,
                    email,
                    password,
                    account_status
                FROM donors
                WHERE email = ?
                LIMIT 1
            ";
        } elseif ($account_type === "charity") {
            $sql = "
                SELECT
                    charity_id AS user_id,
                    organization_name AS user_name,
                    email,
                    password,
                    account_status
                FROM charities
                WHERE email = ?
                LIMIT 1
            ";
        } elseif ($account_type === "admin") {
            $sql = "
                SELECT
                    admin_id AS user_id,
                    full_name AS user_name,
                    email,
                    password,
                    'Active' AS account_status
                FROM admins
                WHERE email = ?
                LIMIT 1
            ";
        } else {
            $sql = "";
            $error_message = "Invalid account type selected.";
        }

        if ($sql !== "") {
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                $error_message = "Unable to process your request.";
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();

                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if (!$user || !password_verify($password, $user["password"])) {
                    $error_message = "Incorrect email, password, or account type.";
                } elseif ($user["account_status"] !== "Active") {
                    $error_message = "Your account has been suspended. Please contact the administrator.";
                } else {
                    session_regenerate_id(true);

                    $_SESSION["user_id"] = (int) $user["user_id"];
                    $_SESSION["user_name"] = $user["user_name"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["user_type"] = $account_type;

                    if ($account_type === "donor") {
                        header("Location: donor/dashboard.php");
                    } elseif ($account_type === "charity") {
                        header("Location: charity/dashboard.php");
                    } else {
                        header("Location: admin/dashboard.php");
                    }

                    exit();
                }

                $stmt->close();
            }
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

    <title>Login | FoodSave</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-body">

<header class="main-header">
    <div class="container navbar">

        <a href="index.php" class="logo">
            <span class="logo-icon">🌱</span>
            FoodSave
        </a>

        <nav class="nav-links auth-navigation">
            <a href="index.php">Home</a>

            <a href="register.php" class="nav-button">
                Register
            </a>
        </nav>

    </div>
</header>

<main class="auth-page">

    <section class="auth-container auth-small">

        <div class="auth-heading">
            <span class="auth-icon">🔐</span>

            <h1>Welcome Back</h1>

            <p>
                Log in to manage food donations and requests.
            </p>
        </div>

        <?php if ($error_message !== ""): ?>

            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">

            <div class="form-group">
                <label for="account_type">Account Type</label>

                <select
                    id="account_type"
                    name="account_type"
                    required
                >
                    <option value="">Select account type</option>

                    <option
                        value="donor"
                        <?php echo $account_type === "donor" ? "selected" : ""; ?>
                    >
                        Donor
                    </option>

                    <option
                        value="charity"
                        <?php echo $account_type === "charity" ? "selected" : ""; ?>
                    >
                        Charity
                    </option>

                    <option
                        value="admin"
                        <?php echo $account_type === "admin" ? "selected" : ""; ?>
                    >
                        Administrator
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    placeholder="Enter your email address"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <div class="password-field">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-target="password"
                        aria-label="Show or hide password"
                    >
                        Show
                    </button>
                </div>
            </div>

            <button type="submit" class="submit-button">
                Login
            </button>

        </form>

        <div class="auth-footer">
            <p>
                Do not have an account?
                <a href="register.php">Create an account</a>
            </p>

            <a href="index.php" class="back-home-link">
                ← Return to Home
            </a>
        </div>

    </section>

</main>

<script>
document.querySelectorAll("[data-password-target]").forEach(function (button) {
    button.addEventListener("click", function () {
        const input = document.getElementById(button.dataset.passwordTarget);

        if (input.type === "password") {
            input.type = "text";
            button.textContent = "Hide";
        } else {
            input.type = "password";
            button.textContent = "Show";
        }
    });
});
</script>

</body>
</html>