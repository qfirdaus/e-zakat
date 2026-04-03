<?php
// Ubah string jadi format title case
function to_title_case($string) {
    return ucwords(strtolower(trim($string)));
}

// Potong string ikut panjang dengan ellipsis
function truncate($string, $length = 100, $suffix = '...') {
    return strlen($string) > $length ? substr($string, 0, $length) . $suffix : $string;
}

// Tukar newline kepada <br>
function nl2br_html($string) {
    return nl2br(htmlspecialchars($string));
}
