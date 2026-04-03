<?php
// Semak jika fail gambar wujud dan valid
function is_valid_image($path) {
    return file_exists($path) && exif_imagetype($path) !== false;
}

// Dapatkan dimensi gambar
function get_image_dimensions($path) {
    return file_exists($path) ? getimagesize($path) : [0, 0];
}

// Dapatkan saiz fail (dalam KB)
function get_file_size_kb($path) {
    return file_exists($path) ? round(filesize($path) / 1024, 2) : 0;
}
