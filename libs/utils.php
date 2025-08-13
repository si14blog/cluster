<?php
function time_ago($pubdate)
{
    // Detect Jalali format: e.g. 1403/05/06 or 1403-5-6
    if (preg_match('/^(13|14)\d{2}[\/\-]\d{1,2}[\/\-]\d{1,2}/', $pubdate)) {
        // Normalize separator
        $pubdate = str_replace('-', '/', $pubdate);

        // Extract year, month, day
        [$jy, $jm, $jd] = explode('/', explode(' ', $pubdate)[0]);

        // Convert Jalali to Gregorian
        list($gy, $gm, $gd) = jalali_to_gregorian((int) $jy, (int) $jm, (int) $jd);

        // Build Gregorian date string
        $pubdate = "$gy-$gm-$gd";
    }

    try {
        $pubDateTime = new DateTime($pubdate);
    } catch (Exception $e) {
        return 'تاریخ نامعتبر';
    }

    $now = new DateTime();
    $diff = $now->diff($pubDateTime);

    if ($diff->y > 0) {
        return $diff->y . ' سال پیش';
    } elseif ($diff->m > 0) {
        return $diff->m . ' ماه پیش';
    } elseif ($diff->d > 0) {
        return $diff->d . ' روز پیش';
    } elseif ($diff->h > 0) {
        return $diff->h . ' ساعت پیش';
    } elseif ($diff->i > 0) {
        return $diff->i . ' دقیقه پیش';
    } else {
        return 'همین الان';
    }
}

function jalali_to_gregorian($jy, $jm, $jd)
{
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + (intdiv((($jy % 33) + 3), 4));
    $days += ($jm < 7) ? (($jm - 1) * 31) : ((($jm - 7) * 30) + 186);
    $days += $jd - 1;

    $gy = 400 * intdiv($days, 146097);
    $days %= 146097;

    if ($days > 36524) {
        $gy += 100 * intdiv(--$days, 36524);
        $days %= 36524;

        if ($days >= 365) {
            $days++;
        }
    }

    $gy += 4 * intdiv($days, 1461);
    $days %= 1461;

    if ($days > 365) {
        $gy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }

    $gd = $days + 1;
    $gm = 0;
    $g_d_m = [0, 31, ($gy % 4 == 0 && $gy % 100 != 0 || $gy % 400 == 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    for ($i = 1; $i <= 12 && $gd > $g_d_m[$i]; $i++) {
        $gd -= $g_d_m[$i];
    }
    $gm = $i;

    return [$gy, str_pad($gm, 2, '0', STR_PAD_LEFT), str_pad($gd, 2, '0', STR_PAD_LEFT)];
}
function truncateAtWord($text, $limit = 80)
{
    $text = trim($text);
    if (mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }

    $truncated = mb_substr($text, 0, $limit, 'UTF-8');

    // Find last space to avoid breaking a word
    $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
    if ($lastSpace !== false) {
        $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
    }

    return $truncated . '...';
}

// Convert English numbers to Persian numbers
function en2faNumber($enNumber)
{
    $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace($en, $fa, $enNumber);
}

// Jalali date conversion function
function gregorianToJalali($gy, $gm, $gd)
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    if ($gy > 1600) {
        $jy = 979;
        $gy -= 1600;
    } else {
        $jy = 0;
        $gy -= 621;
    }
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + (int) (($gy2 + 3) / 4) - (int) (($gy2 + 99) / 100) + (int) (($gy2 + 399) / 400) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * (int) ($days / 12053);
    $days %= 12053;
    $jy += 4 * (int) ($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += (int) (($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + (int) ($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + (int) (($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function today_date()
{// Get Persian weekday names (starting with Saturday)
    $weekDays = [
        'شنبه',      // Saturday
        'یکشنبه',    // Sunday
        'دوشنبه',    // Monday
        'سه‌شنبه',   // Tuesday
        'چهارشنبه',  // Wednesday
        'پنجشنبه',   // Thursday
        'جمعه'       // Friday
    ];

    // Get Persian month names
    $months = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];
    // Get today Gregorian date
    $gy = (int) date('Y');
    $gm = (int) date('m');
    $gd = (int) date('d');

    // Convert to Jalali
    list($jy, $jm, $jd) = gregorianToJalali($gy, $gm, $gd);

    // Get Persian weekday name
// PHP weekday: 0 (Sun) to 6 (Sat)
// We want Saturday=0, so:
    $phpWeekDay = (int) date('w'); // Sunday=0 ... Saturday=6
// Adjust index for Persian weekday starting Saturday:
    $persianWeekDayIndex = ($phpWeekDay + 1) % 7;
    $weekdayName = $weekDays[$persianWeekDayIndex];

    // Format day with Persian digits
    $dayPersian = en2faNumber($jd);

    // Get Persian month name
    $monthName = $months[$jm];

    // Compose final string
    return "{$weekdayName} {$dayPersian} {$monthName}";
}

