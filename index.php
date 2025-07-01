<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kala-Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        header {
            background-color: #8B5E57;
            padding: 1.5rem 2rem;
        }

        .nav-links a {
            color: #fff;
            opacity: 0.9;
        }

        .nav-links a:hover {
            opacity: 1;
            color: #fff;
        }

        .logo {
            color: #fff;
        }

        .hero {
            display: flex;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
            gap: 4rem;
        }

        .hero-content {
            flex: 1;
        }

        .hero-image {
            flex: 1;
            border-radius: 8px;
            overflow: hidden;
        }

        .hero-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        h1 {
            color: #8B5E57;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            font-family: "Times New Roman", serif;
        }

        .hero p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .cta-button {
            display: inline-block;
            padding: 0.8rem 2rem;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1.1rem;
            transition: background-color 0.3s;
        }

        .cta-button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Kala-Ghar</div>
            <div class="nav-links">
            </div>
        </nav>
    </header>

    <main class="hero">
        <div class="hero-content">
            <h1>WELCOME TO KALA-GHAR</h1>
            <p>"Kala Ghar is a community space where passionate learners and skilled artisans connect to share and celebrate the beauty of handmade crafts."</p>
            <a href="login.php" class="cta-button">Get Started</a>
        </div>
        <div class="hero-image">
            <img src="photos/pottery.jpg" alt="Skilled artisan crafting pottery">
            
        </div>
    </main>
</body>
</html> 