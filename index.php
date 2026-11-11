
<?php
require_once "config/db.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Home Builders Choice</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        /* About Us Section */

        .about {
            padding: 60px 20px;
            background: #f4f6f9;
        }

        .about-container {
            max-width: 1100px;
            margin: auto;
            text-align: center;
        }

        .about-container h2 {
            font-size: 30px;
            color: #263746;
            margin-bottom: 15px;
        }

        .about-intro {
            font-size: 18px;
            font-weight: bold;
            color: #2878c7;
            margin-bottom: 25px;
        }

        .about-container > p:not(.about-intro) {
            color: #555;
            font-size: 16px;
            line-height: 1.8;
            max-width: 850px;
            margin: 12px auto;
        }

        .about-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 35px;
        }

        .about-card {
            background: white;
            padding: 25px 20px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .about-card h3 {
            color: #263746;
            margin-bottom: 12px;
        }

        .about-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Welcome Section */

        .welcome {
            padding: 45px 20px;
            text-align: center;
        }

        /* Footer */

        footer {
            background: #263746;
            color: white;
            padding: 25px 20px;
            text-align: center;
        }

        footer a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }

        footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 700px) {

            .about-features {
                grid-template-columns: 1fr;
            }

            .about-container h2 {
                font-size: 25px;
            }

        }

    </style>

</head>

<body>

<!-- Header and Navigation -->

<header>

    <div class="logo">Home Builders Choice</div>

    <nav>

        <a href="index.php">Home</a>

        <a href="customer/products.php">Products</a>

        <a href="#about">About Us</a>

        <a href="contact.php">Contact</a>

        <a href="customer/login.php">Login</a>

        <a href="customer/register.php">Register</a>

    </nav>

</header>


<!-- Hero Section -->

<section class="hero">

    <div class="hero-content">

        <h1>Everything You Need to Build Your Dream</h1>

        <p>
            Find construction tools and materials for your building projects.
        </p>

        <a href="customer/products.php" class="shop-btn">
            Explore Products
        </a>

    </div>

</section>


<!-- Welcome Section -->

<section class="welcome">

    <h2>Welcome to Home Builders Choice</h2>

    <p>
        We provide a wide range of construction tools and materials
        available for rent at affordable prices.
    </p>

</section>


<!-- About Us Section -->

<section class="about" id="about">

    <div class="about-container">

        <h2>About Home Builders Choice</h2>

        <p class="about-intro">
            Your trusted partner for construction equipment rentals.
        </p>

        <p>
            Home Builders Choice is a construction equipment rental
            platform designed to make essential building tools and
            equipment easily accessible to customers.
        </p>

        <p>
            Our platform allows customers to explore available
            construction equipment, compare rental prices, select
            rental durations and place rental orders conveniently.
        </p>

        <p>
            We aim to make the equipment rental process simple,
            affordable and convenient for individuals, builders
            and construction professionals.
        </p>

        <div class="about-features">

            <div class="about-card">

                <h3>Wide Range of Products</h3>

                <p>
                    Explore different construction tools and equipment
                    for your building projects.
                </p>

            </div>


            <div class="about-card">

                <h3>Affordable Rentals</h3>

                <p>
                    Choose daily, weekly and monthly rental options
                    according to your needs.
                </p>

            </div>


            <div class="about-card">

                <h3>Easy Booking</h3>

                <p>
                    Browse products, place rental orders and
                    conveniently track your bookings.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- Footer -->

<footer>

    <p>
        &copy; 2026 Home Builders Choice. All Rights Reserved.
    </p>

    <p>
        <a href="index.php">Home</a> |
        <a href="customer/products.php">Products</a> |
        <a href="#about">About Us</a> |
        <a href="contact.php">Contact Us</a>
    </p>

</footer>

</body>
</html>
