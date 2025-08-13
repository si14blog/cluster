<?php
require_once '../libs/utils.php';
date_default_timezone_set('Asia/Tehran');

$db = new SQLite3('../db/rss_feed.db');

$guuid = $_GET['q'] ?? '';

if (empty($guuid)) {
    echo '<li>شناسه گروه مشخص نشده است.</li>';
    exit;
}

// Validate group existence with at least 3 items
$checkStmt = $db->prepare("SELECT COUNT(*) as count FROM rss_items WHERE group_uuid = :guuid");
$checkStmt->bindValue(':guuid', $guuid, SQLITE3_TEXT);
$checkRes = $checkStmt->execute();
$checkRow = $checkRes->fetchArray(SQLITE3_ASSOC);

if (!$checkRow || $checkRow['count'] < 3) {
    echo '<li>موردی یافت نشد یا کمتر از ۳ آیتم دارد.</li>';
    exit;
}



// Get latest title, description, etc.
$latestStmt = $db->prepare("SELECT title, description, pubDate, source, link, created_at FROM rss_items WHERE group_uuid = :uuid ORDER BY datetime(created_at) DESC LIMIT 1");
$latestStmt->bindValue(':uuid', $guuid, SQLITE3_TEXT);
$latestRes = $latestStmt->execute();
$latestRow = $latestRes->fetchArray(SQLITE3_ASSOC);

if (!$latestRow) {
    echo '<li>اطلاعاتی یافت نشد.</li>';
    exit;
}


$latestLink = $latestRow['link'];
$latestSource = $latestRow['source'];





?>


<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($latestTitle) ?></title>
    <meta name="robots" content="noindex, nofollow">



    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="icon" type="image/png" href="res/favicon.png">



    <style>
        @import url('https://fonts.googleapis.com/css2?family=Reem+Kufi+Fun:wght@400..700&family=Vazirmatn:wght@100..900&display=swap');

        * {
            box-sizing: border-box;
            font-family: "Vazirmatn", sans-serif;
            padding: 0;
            margin: 0;
        }



        body {
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .main {

            text-align: center;
            padding: 2rem;
            border-radius: 2px;
            border: 1px solid rgb(225, 225, 225);
            margin: 1rem;
            background-color: white;
            z-index: +99999;

        }

        .img {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        img {
            display: block;

            max-width: 300px;
        }

        .rt1 {
            margin-bottom: 1rem;
            color: rgba(85, 85, 85, 1);
            font-weight: 400;
            font-size: 1.17em;
        }

        .rt2 {
            color: rgba(46, 46, 46, 1);
            font-size: 1.5em;
            font-weight: bold;
        }


        .loader {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
        }
    </style>
    <script type='text/javascript'>
        // Optional delay

        setTimeout(() => {
            window.location.href = <?php echo json_encode($latestLink); ?>;
        }, 500);

    </script>
</head>

<body>

    <div class="main" data-nosnippet>
        <div class="img">
            <img src="res/logo.png" alt="نسو">
        </div>

        <div class="rt1">در حال انتقال از نسو به خبرگزاری</div>
        <div class="rt2"><?php echo $latestSource; ?></div>

        <div class="loader">
            <img src="res/loader.gif" alt="loader">
        </div>
    </div>

</body>

</html>