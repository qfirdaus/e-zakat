<?php

/**
 * ✅ Ambil terjemahan berdasarkan key
 * Contoh: __('login.title')
 */
function __($key): string {
    $lang = get_current_lang();
    static $lines = [];

    if (!isset($lines[$lang])) {
        $file = __DIR__ . '/../../lang/' . $lang . '.php';

        if (file_exists($file)) {
            $loaded = include $file;
            $lines[$lang] = is_array($loaded) ? $loaded : [];
        } else {
            $lines[$lang] = [];
        }
    }

    $text = $lines[$lang][$key] ?? $key;

    // Normalize encoding and fix common mojibake sequences
    if (!function_exists('fix_mojibake')) {
        function fix_mojibake(string $s): string {
            if ($s === '') return $s;

            // If not valid UTF-8, assume it's CP1252/ISO-8859-1 bytes and convert
            if (!mb_check_encoding($s, 'UTF-8')) {
                $s = mb_convert_encoding($s, 'UTF-8', 'CP1252');
            } else {
                // Try a CP1252 round-trip if it produces a different (likely corrected) string
                $try = mb_convert_encoding($s, 'UTF-8', 'CP1252');
                if ($try !== $s) {
                    $s = $try;
                }
            }

            // Common mojibake replacements that sometimes remain
            $map = [
                'â€“' => '–',
                'â€”' => '—',
                'â€˜' => '‘',
                'â€™' => '’',
                'â€œ' => '“',
                'â€'  => '”',
                'â€¦' => '…',
                'Ã©'  => 'é',
                'Ã¨'  => 'è',
                'Ãà'  => 'à',
                'Ãª'  => 'ê',
                'Ã¶'  => 'ö',
                'Ã¼'  => 'ü',
                'Ã±'  => 'ñ',
                'Ã—'  => '×',
                'â‰¥'  => '≥',
                'â‰¤'  => '≤',
            ];

            return strtr($s, $map);
        }
    }

    return fix_mojibake($text);
}

/**
 * ✅ Semak jika key bahasa wujud
 * Contoh: lang_exists('login.title')
 */
function lang_exists(string $key): bool {
    $lang = get_current_lang();
    static $lines = [];

    if (!isset($lines[$lang])) {
        $file = __DIR__ . '/../../lang/' . $lang . '.php';

        if (file_exists($file)) {
            $loaded = include $file;
            $lines[$lang] = is_array($loaded) ? $loaded : [];
        } else {
            $lines[$lang] = [];
        }
    }

    return array_key_exists($key, $lines[$lang]);
}

/**
 * ✅ Return kod bahasa semasa, default 'ms'
 */
function get_current_lang(): string {
    return $_SESSION['lang'] ?? 'ms';
}

/**
 * ✅ Dapatkan semua terjemahan bahasa sekarang
 */
function get_all_lang_lines(): array {
    $lang = get_current_lang();
    $file = __DIR__ . '/../../lang/' . $lang . '.php';

    if (file_exists($file)) {
        $loaded = include $file;
        return is_array($loaded) ? $loaded : [];
    }

    return [];
}

/**
 * Compatibility wrapper used across views: tr(key, fallback)
 * Returns translated string if available, otherwise returns fallback or key.
 */
if (!function_exists('tr')) {
    function tr(string $key, ?string $fallback = null): string {
        $t = __($key);
        if ($t === $key) {
            return $fallback ?? $key;
        }
        return $t;
    }
}
