<?php
// setting/helper/html_helper.php

if (defined('HTML_HELPER_INCLUDED')) return;
define('HTML_HELPER_INCLUDED', true);

/**
 * ✅ Escape HTML selamat (global)
 */
if (!function_exists('h')) {
  function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

/**
 * ✅ Alias untuk h()
 */
if (!function_exists('e')) {
  function e($v): string { return h($v); }
}

/**
 * ✅ Output tag <script> untuk JS helper dalam assets/js/helpers/
 *    Contoh: load_js_helper('swalert_helper');
 */
if (!function_exists('load_js_helper')) {
  function load_js_helper(string $name): void {
    echo '<script src="' . base_url("assets/js/helpers/{$name}.js") . '"></script>' . "\n";
  }
}

