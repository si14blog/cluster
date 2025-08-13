<?php
require_once '../libs/utils.php';


?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسو - تیتر مهمترین اخبار فارسی</title>
    <meta name="description" content="تیتر مهمترین اخبار فارسی ایران و جهان از سراسر خبرگزاری های معتبر">
    <meta name="keywords" content="اخبار, فارسی, مهمترین اخبار ایران و جهان, اخبار فارسی">
    <meta name="author" content="نسو">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://gate.ns0.ir/">
    <meta property="og:title" content="نسو - تیتر مهمترین اخبار فارسی">
    <meta property="og:description" content="تیتر مهمترین اخبار فارسی ایران و جهان از سراسر خبرگزاری های معتبر">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://gate.ns0.ir/">
    <meta property="og:locale" content="fa_IR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="نسو - تیتر مهمترین اخبار فارسی">
    <meta name="twitter:description" content="تیتر مهمترین اخبار فارسی ایران و جهان از سراسر خبرگزاری های معتبر">
    <link rel="alternate" type="application/rss+xml" title="RSS Feed" href="https://gate.ns0.ir/rss.php">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="https://gate.ns0.ir/sitemap.php">
    <link rel="icon" type="image/png" href="res/favicon.png">

    <meta property="og:image" content="https://gate.ns0.ir/res/logo.png">
    <meta name="twitter:image" content="https://gate.ns0.ir/res/logo.png">


    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script type="application/ld+json">{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "url": "https://www.gate.ns0.ir/",
    "potentialAction": {
        "@type": "SearchAction",
        "target": "https://www.gate.ns0.ir/?search={search_term_string}",
        "query-input": "required name=search_term_string"
    }
}</script>
</head>

<body>
    <nav>
        <div class="nav">

            <div class="middle"><?php echo today_date(); ?></div>

            <div class="left">
                <div class="logo">
                    <a href="/"><img src="res/logo.png" alt=""></a>
                </div>
            </div>
            <div class="right">
                <div class="navicons">
                    <a href="/rss.php"><span class="material-symbols-outlined">
                            rss_feed
                        </span></a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <div class="banner">
            <img src="res/banner.png" alt="">
            <div class="csearchcont">
                <form action="/" method="get">
                    <div class="form">
                        <input type="text" name="search" placeholder="جستجو بین مهمترین اخبار"
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button type="submit"><span class="material-symbols-outlined">
                                search
                            </span></button>
                    </div>
                </form>
            </div>

        </div>
        <br>
        <div class="main">
            <ul>
                <?php
                $db = new SQLite3('../db/rss_feed.db');

                $searchTerm = $_GET['search'] ?? '';
                $perPage = 30;

                $query = "
            SELECT group_uuid, COUNT(*) as count
            FROM rss_items
            WHERE group_uuid IS NOT NULL
            GROUP BY group_uuid
            HAVING count >= 3
            ORDER BY count DESC
            LIMIT $perPage
        ";
                $result = $db->query($query);
                $groups = [];

                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $groupUuid = $row['group_uuid'];
                    $latestTitle = '[عنوان نامشخص]';
                    $imagePath = 'res/noimg.png'; // default image
                
                    // 1. Get latest title & description
                    $latestStmt = $db->prepare("SELECT title, description, pubDate, source, link FROM rss_items WHERE group_uuid = :uuid ORDER BY datetime(created_at) DESC LIMIT 1");
                    $latestStmt->bindValue(':uuid', $groupUuid, SQLITE3_TEXT);
                    $latestRes = $latestStmt->execute();
                    $latestRow = $latestRes->fetchArray(SQLITE3_ASSOC);

                    if ($latestRow) {
                        $latestTitle = html_entity_decode($latestRow['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $latest_pubdate = $latestRow['pubDate'];
                        $latest_source = $latestRow['source'];
                        $latest_link = $latestRow['link'];
                        //   $latest_link = getBaseUrl($latest_link);
                        $latestDescription = html_entity_decode($latestRow['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $latestTitle = preg_replace('/\s+/u', ' ', $latestTitle);
                        $latestDescription = preg_replace('/\s+/u', ' ', $latestDescription);
                    } else {
                        $latestDescription = '';
                    }


                    // Filter if search term provided
                    if ($searchTerm) {
                        $searchWords = explode(' ', $searchTerm);
                        $match = true;
                        foreach ($searchWords as $word) {
                            if (stripos($latestTitle, $word) === false) {
                                $match = false;
                                break;
                            }
                        }
                        if (!$match)
                            continue;
                    }

                    $groups[] = [
                        'uuid' => $groupUuid,
                        'count' => $row['count'],
                        'title' => $latestTitle,
                        'description' => $latestDescription,
                        'pubDate' => $latest_pubdate,
                        'source' => $latest_source,
                        'link' => $latest_link
                    ];
                }

                if (count($groups) === 0) {
                    echo '<li>موردی یافت نشد.</li>';
                } else {





                    // Show the rest normally
                    echo '<div class="rest-group">';
                    for ($i = 0; $i < count($groups); $i++) {
                        $group = $groups[$i];
                        echo '<li><article>';

                        echo '<a href="/c.php?q=' . htmlspecialchars($group['uuid']) . '">';
                        echo '<div class="strong">';
                        $title = htmlspecialchars($group['title']);
                        $shortTitle = $title;
                        echo '<div class="th"><h2>' . $shortTitle . '</h2><div class="sharecont" onclick="shareGroup(event, this)" data-url="https://gate.ns0.ir/c.php?q=' . htmlspecialchars($group['uuid']) . '"><span class="material-symbols-outlined">share</span></div></div>';



                        echo '<div class="titlemeta">';
                        echo '<div class="metacont">' . '<span class="material-symbols-outlined">local_fire_department</span>' . (int) $group['count'] . '</div>';
                        echo '<div class="metacont">' . '<span class="material-symbols-outlined">nest_clock_farsight_analog</span>' . time_ago($group['pubDate']) . '</div>';
                        echo '<div class="metacont">' . '<span class="material-symbols-outlined">web</span>' . ($group['source']) . '</div>';

                        echo '</div>';
                        echo '<p>' . htmlspecialchars(string: truncateAtWord($group['description'], 200)) . '</p>';
                        echo '</div>';
                        echo '</a>';
                        echo '</article></li>';
                    }
                    echo '</div>';


                }
                ?>


            </ul>
            <div class="adside">
                <div class="adcont">
                    <a href="https://hub.iranserver.com/aff.php?aff=9758"><img src="" alt=""></a>

                </div>
                <br>

            </div>

        </div>
    </main>





    <footer>
        <div class="footer">
            <p><a href="/">صفحه اصلی</a> | <a href="/rss.php">RSS</a></p>
            <p>© ۲۰۲۵ نسو | تمامی حقوق محفوظ است.</p>

        </div>
    </footer>

</body>

</html>

<script>
    function shareGroup(event, elem) {
        event.stopPropagation();
        event.preventDefault();

        const url = elem.getAttribute('data-url');

        // Try navigator.share() directly only if definitely supported
        if (navigator.share) {
            // Wrap in try-catch in case of sync failure
            try {
                navigator.share({
                    title: 'اشتراک‌گذاری گروه',
                    text: 'لینک گروه را ببینید:',
                    url: url
                }).catch(() => {
                    // Catch async rejection (e.g. user cancels)
                    fallbackClipboard(url);
                });
            } catch (e) {
                // Catch sync errors (e.g. bad config)
                fallbackClipboard(url);
            }
        } else {
            fallbackClipboard(url);
        }
    }

    function fallbackClipboard(url) {
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('لینک کپی شد ✔');
                }).catch(() => {
                    manualCopyPrompt(url);
                });
            } else {
                manualCopyPrompt(url);
            }
        } catch (e) {
            manualCopyPrompt(url);
        }
    }

    function manualCopyPrompt(url) {
        prompt('لطفاً لینک را دستی کپی کنید:', url);
    }
</script>