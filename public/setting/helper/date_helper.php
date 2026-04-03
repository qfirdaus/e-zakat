<?php
function now_datetime() {
    date_default_timezone_set('Asia/Kuala_Lumpur');
    return date('Y-m-d H:i:s');
}

function current_year() {
    date_default_timezone_set('Asia/Kuala_Lumpur');
    return date('Y');
}
