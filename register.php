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
$success_message = "";

$account_type = $_POST["account_type"] ?? "donor";
$full_name = trim($_POST["full_name"] ?? "");
$donor_type = $_POST["donor_type"] ?? "Individual";
$organization_name = trim($_POST["organization_name"] ?? "");
$registration_number = trim($_POST["registration_number"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$address = trim($_POST["address"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (!in_array($account_type, ["donor", "charity"], true)) {
        $error_message = "Please select a valid account type.";
    } elseif (
        $email === "" ||
        $phone === "" ||
        $address === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {
        $error_message = "Please complete all required fields.";
    } elseif ($account_type === "donor" && $full_name === "") {
        $error_message = "Please enter the donor's full name.";
    } elseif ($account_type === "charity" && $organization_name === "") {
        $error_message = "Please enter the charity organization name.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error_message = "The password must contain at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $error_message = "The passwords do not match.";
    } else {
        // Prevent one email from being registered in more than one user table
        $email_exists = false;

        $email_tables = [
            ["table" => "donors", "column" => "email"],
            ["table" => "charities", "column" => "email"],
            ["table" => "admins", "column" => "email"]
        ];

        foreach ($email_tables as $email_table) {
            $check_sql = "
                SELECT 1
                FROM {$email_table["table"]}
                WHERE {$email_table["column"]} = ?
                LIMIT 1
            ";

            $check_stmt = $conn->prepare($check_sql);

            if ($check_stmt) {
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();

                if ($check_stmt->get_result()->num_rows > 0) {
                    $email_exists = true;
                }

                $check_stmt->close();
            }

            if ($email_exists) {
                break;
            }
        }

        if ($email_exists) {
            $error_message = "This email address is already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            if ($account_type === "donor") {
                $allowed_donor_types = [
                    "Individual",
                    "Restaurant",
                    "Hotel",
                    "Supermarket",
                    "Other"
                ];

                if (!in_array($donor_type, $allowed_donor_types, true)) {
                    $donor_type = "Individual";
                }

                $sql = "
                    INSERT INTO donors (
                        full_name,
                        donor_type,
                        email,
                        password,
                        phone,
                        address
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param(
                        "ssssss",
                        $full_name,
                        $donor_type,
                        $email,
                        $password_hash,
                        $phone,
                        $address
                    );
                }
            } else {
                $sql = "
                    INSERT INTO charities (
                        organization_name,
                        email,
                        password,
                        phone,
                        address,
                        registration_number
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param(
                        "ssssss",
                        $organization_name,
                        $email,
                        $password_hash,
                        $phone,
                        $address,
                        $registration_number
                    );
                }
            }

            if (!isset($stmt) || !$stmt) {
                $error_message = "Unable to create the account.";
            } elseif ($stmt->execute()) {
                $success_message =
                    "Your account has been created successfully. You can now log in.";

                $account_type = "donor";
                $full_name = "";
                $donor_type = "Individual";
                $organization_name = "";
                $registration_number = "";
                $email = "";
                $phone = "";
                $address = "";
            } else {
                $error_message = "Unable to create the account. Please try again.";
            }

            if (isset($stmt) && $stmt) {
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

    <title>Register | FoodSave</title>

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

            <a href="login.php" class="nav-button">
                Login
            </a>
        </nav>

    </div>
</header>

<main class="auth-page">

    <section class="auth-container">

        <div class="auth-heading">
            <span class="auth-icon">👤</span>

            <h1>Create an Account</h1>

            <p>
                Register as a donor or charitable organization.
            </p>
        </div>

        <?php if ($error_message !== ""): ?>

            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>

        <?php endif; ?>

        <?php if ($success_message !== ""): ?>

            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>

                <a href="login.php" class="alert-link">
                    Login now
                </a>
            </div>

        <?php endif; ?>

        <form method="POST" action="register.php" class="auth-form">

            <div class="form-group">
                <label for="account_type">Account Type</label>

                <select
                    id="account_type"
                    name="account_type"
                    required
                >
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
                </select>
            </div>

            <div id="donorFields">

                <div class="form-group">
                    <label for="full_name">Full Name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php echo htmlspecialchars($full_name); ?>"
                        placeholder="Enter your full name"
                    >
                </div>

                <div class="form-group">
                    <label for="donor_type">Donor Type</label>

                    <select id="donor_type" name="donor_type">
                        <?php
                        $donor_types = [
                            "Individual",
                            "Restaurant",
                            "Hotel",
                            "Supermarket",
                            "Other"
                        ];

                        foreach ($donor_types as $type):
                        ?>
                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php echo $donor_type === $type ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div id="charityFields">

                <div class="form-group">
                    <label for="organization_name">
                        Organization Name
                    </label>

                    <input
                        type="text"
                        id="organization_name"
                        name="organization_name"
                        value="<?php echo htmlspecialchars($organization_name); ?>"
                        placeholder="Enter the charity name"
                    >
                </div>

                <div class="form-group">
                    <label for="registration_number">
                        Registration Number
                    </label>

                    <input
                        type="text"
                        id="registration_number"
                        name="registration_number"
                        value="<?php echo htmlspecialchars($registration_number); ?>"
                        placeholder="Enter the registration number"
                    >
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="Enter your email"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?php echo htmlspecialchars($phone); ?>"
                        placeholder="Enter your phone number"
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
                    value="<?php echo htmlspecialchars($address); ?>"
                    placeholder="Enter your address"
                    required
                >
            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="password-field">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="At least 8 characters"
                            autocomplete="new-password"
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

                <div class="form-group">
                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <div class="password-field">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Re-enter your password"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-target="confirm_password"
                            aria-label="Show or hide password"
                        >
                            Show
                        </button>
                    </div>
                </div>

            </div>

            <button type="submit" class="submit-button">
                Create Account
            </button>

        </form>

        <div class="auth-footer">
            <p>
                Already have an account?
                <a href="login.php">Login here</a>
            </p>

            <a href="index.php" class="back-home-link">
                ← Return to Home
            </a>
        </div>

    </section>

</main>

<script>
const accountType = document.getElementById("account_type");
const donorFields = document.getElementById("donorFields");
const charityFields = document.getElementById("charityFields");

const fullName = document.getElementById("full_name");
const donorType = document.getElementById("donor_type");
const organizationName = document.getElementById("organization_name");

function updateRegistrationFields() {
    if (accountType.value === "charity") {
        donorFields.style.display = "none";
        charityFields.style.display = "block";

        fullName.required = false;
        donorType.required = false;
        organizationName.required = true;
    } else {
        donorFields.style.display = "block";
        charityFields.style.display = "none";

        fullName.required = true;
        donorType.required = true;
        organizationName.required = false;
    }
}

accountType.addEventListener("change", updateRegistrationFields);
updateRegistrationFields();

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