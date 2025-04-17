<!DOCTYPE html>
<html>
<head>
    <title>Discovery</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
        }
        nav {
            margin-bottom: 20px;
        }
        nav a {
            margin-right: 20px;
            text-decoration: none;
            font-weight: bold;
            color: #333;
        }
        nav a:hover {
            text-decoration: underline;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border: 1px solid #ccc;
            overflow: auto;
        }
    </style>
</head>
<body>

    <nav>
        <a href="discovery.php">Discovery</a>
        <a href="devices.php">Devices</a>
    </nav>

    <h1>Discovery</h1>

    <form method="post">
        <button type="submit" name="start">Start Discovery</button>
    </form>

    <?php if (isset($_POST['start'])): ?>
        <h2>Scan Output:</h2>
        <pre><?php include('discover.php'); ?></pre>
    <?php endif; ?>

</body>
</html>
