 <?php
include "includes/db.php";
include "includes/session.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trusted Fundi</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f1ea; /* cream beige */
            color: #5c4b43;
        }

        /* NAVBAR */
        .navbar {
            background: #f3d6d2; /* soft pink */
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a {
            text-decoration: none;
            color: #5c4b43;
            margin-left: 15px;
            font-weight: bold;
        }

        /* HERO */
        .hero {
            text-align: center;
            padding: 70px 20px;
            background: #fffaf5; /* cream white */
        }

        .hero h1 {
            font-size: 42px;
            margin-bottom: 10px;
            color: #6b4e45;
        }

        .hero p {
            font-size: 18px;
            color: #7a6a62;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #e8b7b0; /* soft pink button */
            color: white;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn:hover {
            background: #d9a39b;
        }

        /* FEATURES */
        .features {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 40px;
            flex-wrap: wrap;
        }

        .card {
            background: #fffaf5;
            width: 250px;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            border: 1px solid #f0e2dc;
        }

        .card h3 {
            color: #6b4e45;
        }

        /* FOOTER */
        .footer {
            text-align: center;
            padding: 20px;
            background: #f3d6d2;
            color: #5c4b43;
            margin-top: 40px;
        }
    </style>

</head>

<body>

<!-- NAVBAR -->
<div class="navbar">
    <div><strong>Trusted Fundi 🔧</strong></div>
    <div>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </div>
</div>

<!-- HERO -->
<div class="hero">
    <h1>Trusted Fundi</h1>
    <p>Connecting you to skilled, affordable and reliable fundis near you.</p>

    <a href="register.php" class="btn">Get Started</a>
</div>

<!-- FEATURES -->
<div class="features">

    <div class="card">
        <h3>🔧 Skilled Fundis</h3>
        <p>Verified professionals you can trust.</p>
    </div>

    <div class="card">
        <h3>💰 Affordable</h3>
        <p>Fair and transparent pricing.</p>
    </div>

    <div class="card">
        <h3>⚡ Fast Service</h3>
        <p>Get help quickly when you need it.</p>
    </div>

</div>

<!-- FOOTER -->
<div class="footer">
    &copy; 2026 Trusted Fundi | Built with care
</div>

</body>
</html>