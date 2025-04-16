<!DOCTYPE html>
<html>
<head>
    <title>Discovery</title>
</head>
<body>
    <h1>Discovery</h1>
    <form method="post">
        <button type="submit" name="start">Start Discovery</button>
    </form>

    <div>
        <?php
        if (isset($_POST['start'])) {
            include('discover.php');
        }
        ?>
    </div>
</body>
</html>
