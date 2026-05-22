<?php
function lang($key)
{
    global $translations;
    return $translations[$key] ?? $key;
}
?>