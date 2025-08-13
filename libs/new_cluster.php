<?php
// Preprocess the title: convert to lowercase, remove special characters, and trim extra spaces.
function preprocessTitle($title)
{
    // Convert to lowercase while preserving Farsi characters
    $title = mb_strtolower($title, 'UTF-8');

    // Keep Farsi characters, numbers, and spaces, remove other special characters
    // Extended Farsi character range including more special characters
    $title = preg_replace('/[^\x{0600}-\x{06FF}\x{FB8A}\x{067E}\x{0686}\x{06AF}\x{06A9}\x{06BE}\x{06C0}-\x{06C2}\x{06D5}\x{06D6}\x{06DC}\x{06DD}\x{06DF}-\x{06E8}\x{06EA}-\x{06ED}\x{06F0}-\x{06F9}\p{N}\s]/u', '', $title);

    // Normalize spaces
    $title = preg_replace('/\s+/', ' ', trim($title));

    return $title;
}

// Tokenize text into words, handling Farsi text properly
function tokenize($text)
{
    // Split on spaces while preserving Farsi characters
    $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    // Filter out empty tokens and very short tokens (less than 2 characters)
    return array_filter($tokens, function ($token) {
        return mb_strlen($token, 'UTF-8') >= 2;
    });
}

// Calculate word overlap similarity between two sets of words
function wordOverlapSimilarity($wordsA, $wordsB)
{
    if (empty($wordsA) || empty($wordsB)) {
        return 0;
    }

    $intersection = array_intersect($wordsA, $wordsB);
    $union = array_unique(array_merge($wordsA, $wordsB));

    if (empty($union)) {
        return 0;
    }
    return count($intersection) / count($union);
}

// Compute TF-IDF vectors for each title with improved weighting
function computeTFIDF($titles)
{
    $wordFreq = [];
    $docFreq = [];
    $totalDocs = count($titles);

    // Calculate term frequencies and document frequencies
    foreach ($titles as $title) {
        $tokens = tokenize($title);
        $uniqueTokens = array_unique($tokens);

        foreach ($tokens as $token) {
            $wordFreq[$title][$token] = ($wordFreq[$title][$token] ?? 0) + 1;
        }
        foreach ($uniqueTokens as $token) {
            $docFreq[$token] = ($docFreq[$token] ?? 0) + 1;
        }
    }

    // Build the TF-IDF vectors with improved weighting
    $tfidf = [];
    foreach ($wordFreq as $title => $tokens) {
        if (empty($tokens)) {
            $tfidf[$title] = [];
            continue;
        }

        $maxFreq = max($tokens);
        foreach ($tokens as $word => $freq) {
            // Use normalized term frequency
            $tf = $freq / $maxFreq;
            // Use smoothed IDF to handle rare terms better
            $idf = log(($totalDocs + 1) / ($docFreq[$word] + 1));
            $tfidf[$title][$word] = $tf * $idf;
        }
    }
    return $tfidf;
}

// Compute cosine similarity between two TF-IDF vectors
function cosineSimilarity($vecA, $vecB)
{
    if (empty($vecA) || empty($vecB)) {
        return 0;
    }

    $dotProduct = 0;
    $normA = 0;
    $normB = 0;

    foreach ($vecA as $word => $weightA) {
        $weightB = $vecB[$word] ?? 0;
        $dotProduct += $weightA * $weightB;
        $normA += $weightA ** 2;
    }
    foreach ($vecB as $word => $weightB) {
        if (!isset($vecA[$word])) {
            $normB += $weightB ** 2;
        }
    }

    $normA = sqrt($normA);
    $normB = sqrt($normB);

    if ($normA == 0 || $normB == 0) {
        return 0;
    }

    return $dotProduct / ($normA * $normB);
}

// Compute combined similarity between two titles
function computeCombinedSimilarity($titleA, $titleB, $tfidfVectors)
{
    if (!isset($tfidfVectors[$titleA]) || !isset($tfidfVectors[$titleB])) {
        return 0;
    }

    // Get word sets
    $wordsA = array_keys($tfidfVectors[$titleA]);
    $wordsB = array_keys($tfidfVectors[$titleB]);

    // Calculate word overlap similarity
    $overlapSim = wordOverlapSimilarity($wordsA, $wordsB);

    // Calculate cosine similarity
    $cosineSim = cosineSimilarity($tfidfVectors[$titleA], $tfidfVectors[$titleB]);

    // Combine similarities with weights
    return (0.6 * $cosineSim) + (0.4 * $overlapSim);
}

function generateRepresentativeTitle($group, $tfidfVectors, $idMapping)
{
    if (empty($group)) {
        return '';
    }

    $centroid = [];
    $numTitles = count($group);

    foreach ($group as $processedTitle) {
        if (!isset($tfidfVectors[$processedTitle])) {
            continue;
        }
        foreach ($tfidfVectors[$processedTitle] as $word => $weight) {
            $centroid[$word] = ($centroid[$word] ?? 0) + $weight;
        }
    }

    if (empty($centroid)) {
        return $idMapping[$group[0]]['title'] ?? '';
    }

    foreach ($centroid as $word => $totalWeight) {
        $centroid[$word] = $totalWeight / $numTitles;
    }

    $bestTitle = '';
    $bestSim = -1;
    foreach ($group as $processedTitle) {
        if (!isset($tfidfVectors[$processedTitle])) {
            continue;
        }
        $similarity = computeCombinedSimilarity($processedTitle, $group[0], $tfidfVectors);
        if ($similarity > $bestSim) {
            $bestSim = $similarity;
            $bestTitle = $processedTitle;
        }
    }

    return $idMapping[$bestTitle]['id'] ?? '';
}


function generateRepresentativeTitle2($group, $tfidfVectors, $idMapping)
{
    if (empty($group)) {
        return '';
    }

    $centroid = [];
    $numTitles = 0;

    // Step 1: Build the centroid vector
    foreach ($group as $processedTitle) {
        if (!isset($tfidfVectors[$processedTitle])) {
            continue;
        }

        $numTitles++;
        foreach ($tfidfVectors[$processedTitle] as $word => $weight) {
            $centroid[$word] = ($centroid[$word] ?? 0) + $weight;
        }
    }

    if ($numTitles === 0 || empty($centroid)) {
        return $group[0]; // fallback to first item
    }

    // Step 2: Normalize centroid
    foreach ($centroid as $word => $totalWeight) {
        $centroid[$word] = $totalWeight / $numTitles;
    }

    // Step 3: Find the title most similar to the centroid
    $bestTitle = '';
    $bestSim = -1;
    foreach ($group as $processedTitle) {
        if (!isset($tfidfVectors[$processedTitle])) {
            continue;
        }

        $similarity = cosineSimilarity($tfidfVectors[$processedTitle], $centroid);
        if ($similarity > $bestSim) {
            $bestSim = $similarity;
            $bestTitle = $processedTitle;
        }
    }

    return $bestTitle; // ← directly return the processed title
}



// Main function to group similar news items with improved similarity calculation
function groupSimilarNews($threshold = 0.90)
{
    // opening db
    $dbFilePath = 'db/rss_feed.db';
    if (!file_exists($dbFilePath)) {
        echo ("Database file not found: " . $dbFilePath);
        exit;
    }

    try {

        echo 'Grouping Started @ ' . date('Y-m-d H:i:s') . "\n";
        // reading db
        echo "reading db \n";
        $db = new SQLite3($dbFilePath);

        // Select news items from the last 1 day and grouped title of all time (including description for grouping)
        $query = "SELECT id, title, description, link, source, pubDate, group_uuid FROM rss_items WHERE (created_at >= datetime('now', '-1 day') AND group_uuid IS NULL) OR (group_uuid IS NOT NULL)";

        $result = $db->query($query);

        if (!$result) {
            echo ("Database query failed: " . $db->lastErrorMsg());
            exit;
        }


        $items = [];

        // we have queried items of groups and today and we are checking for them ehre
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (empty($row['title'])) {
                continue;
            }

            $items[] = $row;

        }

        if (empty($items)) {
            echo 'no items to process !!!' . "\n";
            $db->close();
            exit;
        }




    } catch (Exception $e) {
        echo ("Error in groupSimilarNews: " . $e->getMessage());
        $db->close();
        exit;
    } finally {

        echo 'Grouping Successful @ ' . date('Y-m-d H:i:s') . "\n";
        if (isset($db)) {
            $db->close();
        }
    }
}
