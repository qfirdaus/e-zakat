<?php
// Papar nilai dalam input dengan htmlspecialchars
function form_value($name, $default = '') {
    return isset($_POST[$name]) ? htmlspecialchars($_POST[$name]) : htmlspecialchars($default);
}

// Papar 'selected' jika value sama
function is_selected($a, $b) {
    return $a == $b ? 'selected' : '';
}

// Papar 'checked' jika value sama
function is_checked($a, $b) {
    return $a == $b ? 'checked' : '';
}
