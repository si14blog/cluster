<?php
function cache()
{
    header('Content-Type: text/plain');
    $num = 0;
    // sleep(1);

    // SQLite database file path
    $dbFilePath = 'db/rss_feed.db';

    // Create or open the SQLite database
    $db = new SQLite3($dbFilePath);

    // Create table if it doesn't exist for storing news items with an auto timestamp column
    $db->exec("CREATE TABLE IF NOT EXISTS rss_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    description TEXT,
    link TEXT,
    source TEXT,
    pubDate TEXT,
    created_at DATETIME DEFAULT (datetime('now')),
    group_uuid TEXT DEFAULT NULL
    )");

    $sizeBefore = filesize($dbFilePath);

    // Deletion and vacuum
    $deleted = $db->querySingle("
    SELECT COUNT(*) 
    FROM rss_items 
    WHERE group_uuid IS NULL 
      AND datetime(created_at) < datetime('now', '-1 day')
");

    $db->exec("
    DELETE FROM rss_items
    WHERE group_uuid IS NULL
      AND datetime(created_at) < datetime('now', '-1 day')
");
    echo "Deleted $deleted old ungrouped items.\n";

    $db->exec("VACUUM");

    // Refresh file stats to get accurate size
    clearstatcache();

    $sizeAfter = filesize($dbFilePath);
    echo "Database vacuumed.\n";
    echo "File size before: " . round($sizeBefore / 1024 / 1024, 2) . " MB\n";
    echo "File size after:  " . round($sizeAfter / 1024 / 1024, 2) . " MB\n";




    $linkList = [

        'اعتماد آنلاین' => [
            'https://www.etemadonline.com/feeds/',
        ],
        'ایران آنلاین' => [
            'https://inn.ir/news/rss'
        ],
        'فردانیوز' => [
            'https://www.fardanews.com/feeds/'
        ],
        'رجا نیوز' => [
            'https://www.rajanews.com/rss/all'
        ],

        'پارسینه' => [
            'https://www.parsine.com/feeds/'
        ],

        'اطلاعات' => [
            'https://ettelaat.com/fa/rss/allnews',

        ],
        'افکار نیوز' => [
            'https://www.afkarnews.com/feeds/',
        ],
        'اقتصاد ۱۰۰' => [
            'https://www.eghtesad100.ir/feeds/',
        ],
        'اقتصاد آنلاین' => [
            'https://www.eghtesadonline.com/rss/pl/279',
        ],
        'اقتصاد نیوز' => [
            'https://www.eghtesadnews.com/feeds/',
        ],
        'اکوایران' => [
            'https://ecoiran.com/feeds/',
        ],
        'اي-استخدام' => [
            'https://www.e-estekhdam.com/feed',
        ],
        'ایکنا' => [
            'https://iqna.ir/fa/rss/allnews',
        ],

        'بهداشت نیوز' => [
            'https://behdasht.news/rss-fa.xml',
        ],
        'خبرگزاری تسنیم' => [
            'https://www.tasnimnews.com/fa/rss/feed/0/8/0/%D8%A2%D8%AE%D8%B1%DB%8C%D9%86-%D8%AE%D8%A8%D8%B1%D9%87%D8%A7%DB%8C-%D8%B1%D9%88%D8%B2',

        ],

        'تیتر کوتاه' => [
            'https://www.titrekootah.ir/feeds',
        ],

        'خبرآنلاین' => [
            'https://www.khabaronline.ir/rss',
        ],

        'خبرگردون' => [
            'https://www.ghatreh.com/news/src-243-0-20.rss',

        ],

        'خبرورزشی' => [
            'https://www.khabarvarzeshi.com/rss',
        ],

        'دنیای اقتصاد' => [
            'https://donya-e-eqtesad.com/feeds/',
        ],

        'رکنا' => [
            'https://www.rokna.net/feeds/',
        ],


        'سلام نو' => [
            'https://www.salameno.com/rss',
        ],


        'صد آنلاین' => [
            'https://sadonline.ir/fa/rss/allnews',
        ],

        'فردای اقتصاد' => [
            'https://www.fardayeeghtesad.com/rss',
        ],
        'کارگر آنلاین' => [
            'https://kargaronline.ir/rss-fa.xml',
        ],

        'موبنا' => [
            'https://www.mobna.com/feed',
        ],

        'مشرق نیوز' => [
            'https://www.mashreghnews.ir/rss',
        ],

        'نامه نیوز' => [
            'https://www.namehnews.com/feeds/',
        ],

        'همشهری آنلاین' => [
            'https://www.hamshahrionline.ir/rss',
        ],

        'شهر' => [
            'https://shahr.ir/rss'
        ],

        'انتخاب' => [
            'https://www.entekhab.ir/fa/rss/allnews'
        ],
        'سپاه نیوز: سایت خبری روابط عمومی سپاه' => [
            'https://sepahnews.ir/fa/rss/allnews'
        ],

        'ورزش سه' => [
            'https://www.varzesh3.com/rss/all',
        ],

        'فارس نیوز' => [
            'https://www.farsnews.ir/rss',
        ],

        'مهر نیوز' => [
            'https://www.mehrnews.com/rss',
        ],

        'تابناک' => [
            'https://www.tabnak.ir/fa/rss/allnews',
        ],

        'ایرنا' => [
            'https://www.irna.ir/rss',
        ],

        'شمال نیوز' => [
            'https://www.shomalnews.com/index.php?rss&sid=1'
        ],

        'سارنا' => [
            'https://sarna.ir/service/noteRSS/2'
        ],

        'شیعه نیوز' => [
            'https://www.shia-news.com/fa/rss/allnews'
        ],

        'فرارو' => [
            'https://fararu.com/fa/rss/allnews',
        ],

        'صبح مجلس' => [
            'https://sobhemajles.ir/feed/'
        ],

        'بولتن نیوز' => [
            'https://www.bultannews.com/fa/rss/allnews'
        ],

        'آفتاب نیوز' => [
            'https://aftabnews.ir/fa/rss/allnews',
        ],


        'زومیت' => [
            'https://www.zoomit.ir/feed/',
        ],

        'ایسنا' => [
            'https://www.isna.ir/rss',
        ],

        'اسپاتنیک فارسی' => [
            'https://spnfa.ir/export/rss2/archive/index.xml',
        ],



        'سرپوش' => [
            'https://www.sarpoosh.com/rss/hotnews.xml',
        ],

        'برترین ها' => [
            'https://www.bartarinha.ir/fa/feeds/?p=Y2F0ZWdvcmllcz0yMA%2C%2C',
        ],

        'دیپلماسی ایرانی' => [
            'http://irdiplomacy.ir/fa/news/rss',
        ],

        'خبرگزاری موج' => [
            'https://www.mojnews.com/feeds/',
        ],

        'صاحب خبر' => [
            'https://sahebkhabar.ir/rss',
        ],

        'پارس فوتبال' => [
            'https://parsfootball.com/na/feed/',
        ],

        'تجارت نیوز' => [
            'https://tejaratnews.com/feeds/',
        ],



        'صنعت ماشین' => [
            'https://sanatemashin.com/fa/rss/allnews',
        ],


        'خبر خودرو' => [
            'http://khabarkhodro.com/fa/rss/allnews',
        ],


        'خبرگزاری صدا و سیما' => [
            'https://www.iribnews.ir/fa/rss/allnews',
        ],


        'تکنولایف' => [
            'https://www.technolife.ir/blog/feed/'
        ],

        'تکنا' => [
            'https://techna.news/feed'
        ],

        'گجت نیوز' => [
            'https://gadgetnews.net/feed/'
        ],

        'نیازمندیها' => [
            'https://niyazmandyha.ir/rss/newest'
        ],



        'برساد (زرتشتیان)' => [
            'https://berasad.com/?feed=rss2'
        ],

        'خبر فوری' => [
            'http://khabarfori.ir/fa/feeds/?p=ZGF0ZVJhbmdlJTVCc3RhcnQlNUQ9LTQzMjAw'
        ],

        'جمهوریت' => [
            'https://jomhouriat.net/feed'
        ],
        'آرتان پرس: اخبار بازار فولاد' => [
            'https://artanpress.ir/feed/'
        ],

        'منبع خبر' => [
            'https://www.manbaekhabar.ir/feed/'
        ],
        'ملّت بیدار' => [
            'https://melatebidaronline.ir/rss/latest-posts'
        ],
        'همراز نیوز' => [
            'https://www.hamraznews.ir/?format=feed&type=rss'
        ],
        'پارتیان امروز' => [
            'https://partianemrooz.ir/feed/'
        ],
        'صدا نیوز' => [
            'https://www.3danews.ir/feed'
        ],
        'خبرداغ' => [
            'https://khabaredagh.ir/fa/rss/allnews'
        ],
        'آقای خبر' => [
            'https://aghayekhabar.ir/rss/latest-posts'
        ],
        'عصر ایران' => [
            'https://www.asriran.com/fa/rss/allnews'
        ],
        'جهان نیوز' => [
            'https://cdn.jahannews.com/feeds/rssb5.-er48r6--4qhfle2m.puirug.r.xml'
        ],
        'نما' => [
            'https://namanews.com/RSS/'
        ],
        'سیتنا: اخبار فناوری ارتباطات و دیجیتال' => [
            'https://www.citna.ir/rss'
        ],
        'بورس نیوز' => [
            'https://www.boursenews.ir/fa/rss/allnews'
        ],
        'پایگاه خبری ربیع' => [
            'https://raby.ir/feed/'
        ],
        'اختبار' => [
            'https://www.ekhtebar.ir/feed/'
        ],
        'فرقه نیوز' => [
            'https://ferghe.ir/fa/rss/allnews'
        ],
        'پایگاه خبری تهران پردیس' => [
            'https://tehranpardis.ir/feed/'
        ],
        'اتاق نیوز' => [
            'http://www.otaghnews.com/rss/'
        ],
        'وطن' => [
            'https://www.watan.ir/feed/'
        ],
        'آی تی ایران' => [
            'https://itiran.com/feed/'
        ],
        'صراط' => [
            'https://www.seratnews.com/fa/rss/allnews'
        ],
        'اخبار MBA' => [
            'https://mbanews.ir/feed/'
        ],
        'جامع نیوز' => [
            'https://jamenews.ir/feed/'
        ],
        'خبرخوان همفکری' => [
            'https://ihamfekr.ir/feed/'
        ],
        'پایگاه تحلیلی خبری سرمایه گستران' => [
            'https://sarmayehgostaran.ir/feed/'
        ],
        'پایگاه خبری ملی' => [
            'https://mellee.ir/feed/'
        ],
        'نورنیوز' => [
            'https://nournews.ir/fa/rss/AllNews'
        ],
        'پایگاه خبری تئاتر و سینما' => [
            'https://teater.ir/rss.php'
        ],
        'پایگاه خبری انرژی پلاس' => [
            'https://energyplus24.ir/feed/'
        ],
        'وفاق سبز' => [
            'https://vefaghesabz.ir/rss/'
        ],
        'باشگاه خبرنگاران جوان' => [
            'https://www.yjc.ir/fa/rss/allnews'
        ],
        'ایسکا نیوز' => [
            'https://www.iscanews.ir/rss'
        ],
        'اکونیوز' => [
            'https://econews.ir/fa/news/feed/'
        ],
        'آخرین نیوز' => [
            'https://akharinnews.com/?option=com_k2&view=itemlist&format=feed&type=rss'
        ],
        'آفاق آنلاین' => [
            'https://afaghonline.ir/feed/'
        ],
        'پایگاه صلح خبر' => [
            'https://solhkhabar.ir/feed/'
        ],
        'خبرگزاری اقتصادی ایران اکونا' => [
            'http://www.iranecona.ir/rss/export/0/'
        ],
        'برق نیوز' => [
            'https://barghnews.com/fa/rss/allnews'
        ],
        'نواندیش' => [
            'https://noandish.com/fa/rss/allnews'
        ],
        'حرف رک' => [
            'https://harferok.ir/feed/'
        ],
        'فر نیوز' => [
            'https://farnews.ir/feed/'
        ],
        'رسانه خبری پرسون' => [
            'https://purson.ir/fa/rss/latest/latest'
        ],
        'اخبار پول' => [
            'http://poolpress.ir/feed/'
        ],
        'پایان تیتر' => [
            'https://payantitr.ir/feed/'
        ],
        'استناد نیوز' => [
            'https://estenadnews.ir/fa/?format=feed'
        ],
        'شهردوست' => [
            'https://shahrdost.ir/feed/'
        ],
        'سریر ایران' => [
            'https://sarireiran.ir/feed/'
        ],
        'کارنامه فردا' => [
            'https://karnameyefarda.ir/feed/'
        ],
        'خبرگزاری حوادث تیتنا' => [
            'https://www.titna.ir/feed/'
        ],
        'تجارت امروز' => [
            'https://tejaratemrouz.ir/feed/'
        ],
        'افق و اقتصاد' => [
            'https://ofoghoeghtesad.ir/feed/'
        ],
        'تین نیوز' => [
            'https://www.tinn.ir/feeds/'
        ],
        'تلنگر' => [
            'https://talaangor.ir/feed/'
        ],
        'پایگاه خبری تحلیلی بازباران' => [
            'https://bazbarannews.ir/feed/'
        ],
        'مهارت نیوز' => [
            'https://maharatnews.com/feed/'
        ],
        'پایگاه اطلاع رسانی بانکداری ایرانی' => [
            'https://bankdariirani.ir/fa/rss/allnews'
        ],
        'نوید تهران' => [
            'https://navidetehran.ir/feed/'
        ]





        // Add more keys and their associated links as needed
    ];

    foreach ($linkList as $sourceName => $links) {
        echo "Source >> $sourceName\n";

        foreach ($links as $link) {
            echo "Link >> $link\n";

            $context = stream_context_create([
                'http' => [
                    'timeout' => 3 // Timeout in seconds
                ]
            ]);

            $rssContent = @file_get_contents($link, false, $context);
            if ($rssContent === false) {
                echo "Failed to fetch content from $link\n";
                continue;
            }

            $rss = simplexml_load_string($rssContent);
            if ($rss === false) {
                echo "Failed to fetch content from $link\n";
                continue;
            }

            $items = $rss->channel->item;

            if ($items == null || empty($items)) {
                echo "items were empty or null from: $link\n";
                continue;
            }

            foreach ($items as $item) {
                $title = (string) $item->title;
                $description = (string) $item->description;
                $link = (string) $item->link;
                $pubDate = (string) $item->pubDate;



                // Parse description to extract text
                $dom = new DOMDocument('1.0', 'UTF-8');
                libxml_use_internal_errors(true);
                $dom->loadHTML('<?xml encoding="UTF-8">' . $description, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();

                $descriptionText = '';
                $divElements = $dom->getElementsByTagName('div');
                foreach ($divElements as $div) {
                    $descriptionText .= $div->textContent . PHP_EOL;
                }
                if (empty(trim($descriptionText))) {
                    $descriptionText = strip_tags($description);
                }
                $descriptionText = trim($descriptionText);

                // Check if item already exists based on title, description, and source OR link
                $stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM rss_items 
    WHERE title = :title 
      AND description = :description 
      AND (source = :source OR link = :link)
");
                $stmt->bindValue(':title', $title, SQLITE3_TEXT);
                $stmt->bindValue(':description', $descriptionText, SQLITE3_TEXT);
                $stmt->bindValue(':source', $sourceName, SQLITE3_TEXT);
                $stmt->bindValue(':link', $link, SQLITE3_TEXT);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);

                if ($row['count'] == 0) {
                    echo " => new Content found!\n";

                    // Insert the new item into the database
                    $stmt = $db->prepare("INSERT INTO rss_items (title, description, link, source, pubDate) VALUES (:title, :description, :link, :source, :pubDate)");
                    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
                    $stmt->bindValue(':description', $descriptionText, SQLITE3_TEXT);
                    $stmt->bindValue(':link', $link, SQLITE3_TEXT);
                    $stmt->bindValue(':source', $sourceName, SQLITE3_TEXT);
                    $stmt->bindValue(':pubDate', $pubDate, SQLITE3_TEXT);
                    $stmt->execute();

                    $num++;


                } else {
                    echo " => Content already exists!\n";
                }
            }
        }
    }

    $db->close();
    echo "Processed $num new items.\n";
}