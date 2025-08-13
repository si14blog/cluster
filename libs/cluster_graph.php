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
    $dbFilePath = 'db/rss_feed.db';
    if (!file_exists($dbFilePath)) {
        echo ("Database file not found: " . $dbFilePath);
        exit;
    }

    try {
        echo 'Grouping Started @ ' . date('Y-m-d H:i:s') . "\n";
        echo "reading db \n";
        $db = new SQLite3($dbFilePath);

        $query = "SELECT id, title, description, link, source, pubDate, group_uuid FROM rss_items WHERE (created_at >= datetime('now', '-1 day') AND group_uuid IS NULL) OR (group_uuid IS NOT NULL)";
        $result = $db->query($query);

        if (!$result) {
            echo ("Database query failed: " . $db->lastErrorMsg());
            exit;
        }

        $titles = [];
        $idMapping = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (empty($row['title']))
                continue;
            $description = $row['description'] ?? '';
            $processedTitle = preprocessTitle($row['title'] . ' ' . $description);
            if (!empty($processedTitle)) {
                $titles[] = $processedTitle;
                $idMapping[$processedTitle] = $row;
            }
        }

        if (empty($titles)) {
            echo "No Titles To Process. !!!\n";
            $db->close();
            exit;
        }

        echo "computing tfidf \n";
        $tfidfVectors = computeTFIDF($titles);

        $groupedTitles = [];
        $ungroupedTitles = [];

        foreach ($titles as $title) {
            if ($idMapping[$title]['group_uuid']) {
                $groupedTitles[] = $title;
            } else {
                $ungroupedTitles[] = $title;
            }
        }

        $groupedMap = [];
        foreach ($groupedTitles as $title) {
            $uuid = $idMapping[$title]['group_uuid'];
            if (!isset($groupedMap[$uuid]))
                $groupedMap[$uuid] = [];
            $groupedMap[$uuid][] = $title;
        }

        $used = [];

        // Try to match ungrouped to existing groups
        foreach ($ungroupedTitles as $index => $item) {
            if (in_array($index, $used))
                continue;

            $bestSimilarity = 0;
            $bestGroupUuid = null;

            foreach ($groupedMap as $groupUuid => $groupItems) {
                $representative = generateRepresentativeTitle2($groupItems, $tfidfVectors, $idMapping);
                $similarity = computeCombinedSimilarity($item, $representative, $tfidfVectors);

                if ($similarity > $bestSimilarity) {
                    $bestSimilarity = $similarity;
                    $bestGroupUuid = $groupUuid;
                }
            }

            if ($bestSimilarity >= $threshold) {
                $matchedId = $idMapping[$item]['id'];
                $stmt = $db->prepare("UPDATE rss_items SET group_uuid = :uuid WHERE id = :id");
                $stmt->bindValue(':uuid', $bestGroupUuid, SQLITE3_TEXT);
                $stmt->bindValue(':id', $matchedId, SQLITE3_INTEGER);
                $result = $stmt->execute();

                if ($result) {
                    echo "🆗 group_uuid '$bestGroupUuid' (best match) updated successfully for ID $matchedId\n";
                } else {
                    echo "❌ Failed to update group_uuid for ID $matchedId\n";
                }

                $used[] = $index;
            }
        }

        // 🧠 Now perform Graph-Based Clustering on remaining ungrouped
        $remaining = [];
        foreach ($ungroupedTitles as $index => $title) {
            if (!in_array($index, $used)) {
                $remaining[$index] = $title;
            }
        }

        echo "🚀 Performing Graph-Based Clustering on " . count($remaining) . " ungrouped items\n";

        // Step 1: Build graph (adjacency list)
        $graph = [];
        $keys = array_keys($remaining);

        foreach ($keys as $i) {
            $graph[$i] = [];
            for ($j = $i + 1; $j < count($keys); $j++) {
                $a = $remaining[$i];
                $b = $remaining[$keys[$j]];
                $sim = computeCombinedSimilarity($a, $b, $tfidfVectors);
                if ($sim >= $threshold) {
                    $graph[$i][] = $keys[$j];
                    $graph[$keys[$j]][] = $i;
                }
            }
        }

        // Step 2: Find connected components (DFS)
        $visited = [];
        $components = [];

        foreach ($graph as $node => $_) {
            if (in_array($node, $visited))
                continue;

            $stack = [$node];
            $component = [];

            while (!empty($stack)) {
                $current = array_pop($stack);
                if (in_array($current, $visited))
                    continue;
                $visited[] = $current;
                $component[] = $current;

                foreach ($graph[$current] as $neighbor) {
                    if (!in_array($neighbor, $visited)) {
                        $stack[] = $neighbor;
                    }
                }
            }

            if (count($component) > 1) {
                $components[] = $component;
            }
        }

        // Step 3: Assign new group_uuid to each component
        foreach ($components as $component) {
            $guuid = uniqid();
            foreach ($component as $index) {
                $title = $remaining[$index];
                if (!isset($idMapping[$title]))
                    continue;
                $id = $idMapping[$title]['id'];

                $stmt = $db->prepare("UPDATE rss_items SET group_uuid = :uuid WHERE id = :id");
                $stmt->bindValue(':uuid', $guuid, SQLITE3_TEXT);
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $result = $stmt->execute();

                if ($result) {
                    echo "✅ New graph-based group_uuid '$guuid' set for ID $id\n";
                } else {
                    echo "❌ Failed to update group_uuid for ID $id\n";
                }
            }
        }

        // Step 4: Print items that didn’t match anywhere
        foreach ($remaining as $index => $title) {
            if (!in_array($index, $visited)) {
                echo "❌ Title (ID {$idMapping[$title]['id']}) => No match found\n";
            }
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
