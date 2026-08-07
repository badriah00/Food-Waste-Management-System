<?php
session_start();

require_once "connect_db.php";

// Fetch the latest available food donations
$donations = [];

$sql = "
    SELECT 
        fd.donation_id,
        fd.food_name,
        fd.category,
        fd.quantity,
        fd.quantity_unit,
        fd.description,
        fd.expiry_date,
        fd.pickup_location,
        fd.pickup_date,
        fd.pickup_time,
        d.full_name,
        d.donor_type
    FROM food_donations fd
    INNER JOIN donors d 
        ON fd.donor_id = d.donor_id
    WHERE fd.status = 'Available'
      AND fd.expiry_date >= CURDATE()
    ORDER BY fd.created_at DESC
    LIMIT 6
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $donations[] = $row;
    }
}

// Determine the dashboard link according to the logged-in user type
$dashboard_link = "";

if (isset($_SESSION["user_type"])) {
    if ($_SESSION["user_type"] === "donor") {
        $dashboard_link = "donor/dashboard.php";
    } elseif ($_SESSION["user_type"] === "charity") {
        $dashboard_link = "charity/dashboard.php";
    } elseif ($_SESSION["user_type"] === "admin") {
        $dashboard_link = "admin/dashboard.php";
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

    <title>Food Waste Management System</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="main-header">
    <div class="container navbar">

        <a href="index.php" class="logo">
            <span class="logo-icon">🌱</span>
            FoodSave
        </a>

        <nav class="nav-links">
            <a href="#home">Home</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#donations">Donations</a>

            <?php if (isset($_SESSION["user_id"]) && $dashboard_link !== ""): ?>

                <a href="<?php echo htmlspecialchars($dashboard_link); ?>">
                    Dashboard
                </a>

                <a href="logout.php" class="nav-button">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">Login</a>

                <a href="register.php" class="nav-button">
                    Register
                </a>

            <?php endif; ?>
        </nav>

        <button
            class="menu-button"
            id="menuButton"
            type="button"
            aria-label="Open navigation menu"
        >
            ☰
        </button>

    </div>
</header>

<main>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container hero-content">

            <div class="hero-text">
                <span class="section-label">
                    Reduce Waste. Support Communities.
                </span>

                <h1>
                    Share Surplus Food and Make a Difference
                </h1>

                <p>
                    FoodSave connects food donors with charitable organizations
                    to reduce food waste and help communities receive safe and
                    available surplus food.
                </p>

                <div class="hero-buttons">

                    <?php if (isset($_SESSION["user_id"]) && $dashboard_link !== ""): ?>

                        <a
                            href="<?php echo htmlspecialchars($dashboard_link); ?>"
                            class="primary-button"
                        >
                            Go to Dashboard
                        </a>

                    <?php else: ?>

                        <a href="register.php" class="primary-button">
                            Get Started
                        </a>

                        <a href="login.php" class="secondary-button">
                            Login
                        </a>

                    <?php endif; ?>

                </div>
            </div>

            <div class="hero-image">
                <div class="hero-card">
                    <div class="hero-card-icon">🥗</div>

                    <h2>Food Can Help</h2>

                    <p>
                        Instead of throwing edible food away, donate it to a
                        registered charitable organization.
                    </p>

                    <div class="hero-statistics">
                        <div>
                            <strong>Donate</strong>
                            <span>Surplus food</span>
                        </div>

                        <div>
                            <strong>Request</strong>
                            <span>Available food</span>
                        </div>

                        <div>
                            <strong>Support</strong>
                            <span>Local communities</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Statistics Section -->
    <section class="statistics-section">
        <div class="container statistics-grid">

            <div class="statistic-box">
                <span class="statistic-icon">🍱</span>
                <h3>Reduce Food Waste</h3>
                <p>
                    Give safe surplus food a second purpose instead of
                    disposing of it.
                </p>
            </div>

            <div class="statistic-box">
                <span class="statistic-icon">🤝</span>
                <h3>Connect Communities</h3>
                <p>
                    Connect donors directly with registered charitable
                    organizations.
                </p>
            </div>

            <div class="statistic-box">
                <span class="statistic-icon">🌍</span>
                <h3>Support Sustainability</h3>
                <p>
                    Promote responsible food consumption and environmental
                    sustainability.
                </p>
            </div>

        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-section" id="how-it-works">
        <div class="container">

            <div class="section-heading">
                <span class="section-label">Simple Process</span>
                <h2>How the System Works</h2>
                <p>
                    The Food Waste Management System makes food donation
                    simple, organized, and efficient.
                </p>
            </div>

            <div class="steps-grid">

                <div class="step-card">
                    <span class="step-number">01</span>
                    <div class="step-icon">👤</div>
                    <h3>Create an Account</h3>
                    <p>
                        Register as a donor or charitable organization using
                        one simple registration form.
                    </p>
                </div>

                <div class="step-card">
                    <span class="step-number">02</span>
                    <div class="step-icon">➕</div>
                    <h3>Add or Browse Donations</h3>
                    <p>
                        Donors add surplus food, while charities browse the
                        available donations.
                    </p>
                </div>

                <div class="step-card">
                    <span class="step-number">03</span>
                    <div class="step-icon">📩</div>
                    <h3>Submit a Request</h3>
                    <p>
                        A charity can request suitable food donations through
                        the system.
                    </p>
                </div>

                <div class="step-card">
                    <span class="step-number">04</span>
                    <div class="step-icon">✅</div>
                    <h3>Approve and Collect</h3>
                    <p>
                        The donor approves the request, and the charity
                        collects the donation.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- Available Donations Section -->
    <section class="donations-section" id="donations">
        <div class="container">

            <div class="section-heading">
                <span class="section-label">Latest Food</span>
                <h2>Available Donations</h2>
                <p>
                    Browse some of the latest available food donations.
                </p>
            </div>

            <?php if (!empty($donations)): ?>

                <div class="donations-grid">

                    <?php foreach ($donations as $donation): ?>

                        <article class="donation-card">

                            <div class="donation-card-header">
                                <span class="donation-category">
                                    <?php
                                    echo htmlspecialchars(
                                        $donation["category"]
                                    );
                                    ?>
                                </span>

                                <span class="available-status">
                                    Available
                                </span>
                            </div>

                            <div class="donation-icon">🍲</div>

                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $donation["food_name"]
                                );
                                ?>
                            </h3>

                            <p class="donation-description">
                                <?php
                                $description =
                                    $donation["description"] ??
                                    "No description provided.";

                                echo htmlspecialchars(
                                    mb_strimwidth(
                                        $description,
                                        0,
                                        100,
                                        "..."
                                    )
                                );
                                ?>
                            </p>

                            <div class="donation-information">

                                <p>
                                    <strong>Quantity:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $donation["quantity"]
                                    );
                                    ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $donation["quantity_unit"]
                                    );
                                    ?>
                                </p>

                                <p>
                                    <strong>Expiry Date:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $donation["expiry_date"]
                                            )
                                        )
                                    );
                                    ?>
                                </p>

                                <p>
                                    <strong>Location:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $donation["pickup_location"]
                                    );
                                    ?>
                                </p>

                                <p>
                                    <strong>Donor:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $donation["full_name"]
                                    );
                                    ?>
                                </p>

                            </div>

                            <?php
                            if (
                                isset($_SESSION["user_type"]) &&
                                $_SESSION["user_type"] === "charity"
                            ):
                            ?>

                                <a
                                    href="charity/donation_details.php?id=<?php
                                    echo (int) $donation["donation_id"];
                                    ?>"
                                    class="card-button"
                                >
                                    View Details
                                </a>

                            <?php elseif (!isset($_SESSION["user_id"])): ?>

                                <a href="login.php" class="card-button">
                                    Login to Request
                                </a>

                            <?php else: ?>

                                <span class="card-button disabled-button">
                                    Charity Access Only
                                </span>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>No Donations Available</h3>
                    <p>
                        There are currently no available food donations.
                        Please check again later.
                    </p>
                </div>

            <?php endif; ?>

        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container cta-content">

            <div>
                <h2>Do You Have Surplus Food?</h2>
                <p>
                    Register as a donor and share safe surplus food with
                    charitable organizations.
                </p>
            </div>

            <?php if (!isset($_SESSION["user_id"])): ?>

                <a href="register.php" class="cta-button">
                    Create an Account
                </a>

            <?php else: ?>

                <a
                    href="<?php echo htmlspecialchars($dashboard_link); ?>"
                    class="cta-button"
                >
                    Open Dashboard
                </a>

            <?php endif; ?>

        </div>
    </section>

</main>

<footer class="main-footer">
    <div class="container footer-content">

        <div>
            <a href="index.php" class="footer-logo">
                🌱 FoodSave
            </a>

            <p>
                A web-based platform for managing and redistributing surplus
                food.
            </p>
        </div>

        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#donations">Donations</a>
            <a href="login.php">Login</a>
        </div>

    </div>

    <div class="footer-bottom">
        <p>
            &copy; <?php echo date("Y"); ?>
            Food Waste Management System. All rights reserved.
        </p>
    </div>
</footer>

<script src="script.js"></script>

</body>
</html>