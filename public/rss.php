<?php
header("Content-Type: application/rss+xml; charset=UTF-8");

require_once '../libs/utils.php';

$db = new SQLite3('../db/rss_feed.db');
$siteUrl = "https://gate.ns0.ir";

// Fetch groups with at least 3 items
$query = "
    SELECT group_uuid, COUNT(*) as count
    FROM rss_items
    WHERE group_uuid IS NOT NULL
    GROUP BY group_uuid
    HAVING count >= 3
    ORDER BY count DESC
    LIMIT 30
";


$result = $db->query($query);
$items = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $uuid = $row['group_uuid'];

    // Get latest item in group
    $stmt = $db->prepare("SELECT title, description, pubDate, link, source FROM rss_items WHERE group_uuid = :uuid ORDER BY datetime(created_at) DESC LIMIT 1");
    $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
    $res = $stmt->execute();
    $data = $res->fetchArray(SQLITE3_ASSOC);

    if ($data) {
        $title = html_entity_decode($data['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = html_entity_decode($data['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $link = $siteUrl . '/c.php?q=' . urlencode($uuid);
        $pubDate = date(DATE_RSS, strtotime($data['pubDate']));
        $source = $data['source'] ?? '';

        $items[] = [
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'pubDate' => $pubDate,
            'source' => $source
        ];
    }
}

// Begin RSS XML
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
?>
<rss version="2.0">
    <channel>
        <title>نسو - مهم‌ترین تیترهای خبری</title>
        <link><?= $siteUrl ?></link>
        <description>خلاصه‌ای از مهمترین تیترهای خبری از منابع معتبر فارسی</description>
        <language>fa-ir</language>
        <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>

        <?php foreach ($items as $item): ?>
            <item>
                <title><![CDATA[<?= $item['title'] ?>]]></title>
                <link><?= htmlspecialchars($item['link']) ?></link>
                <guid><?= htmlspecialchars($item['link']) ?></guid>
                <pubDate><?= $item['pubDate'] ?></pubDate>
                <description><![CDATA[<?= truncateAtWord($item['description'], 300) ?>]]></description>
                <source><?= htmlspecialchars($item['source']) ?></source>
            </item>
        <?php endforeach; ?>
    </channel>
</rss>