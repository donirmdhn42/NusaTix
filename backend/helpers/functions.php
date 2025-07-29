<?php
if (!function_exists('getInitials')) {
 
    function getInitials($name)
    {
        $words = explode(" ", trim($name));
        $initials = "";
        if (count($words) >= 2) {
            $initials .= strtoupper(substr($words[0], 0, 1));
            $initials .= strtoupper(substr($words[count($words) - 1], 0, 1));
        } elseif (count($words) == 1 && strlen($words[0]) > 0) {
            $initials .= strtoupper(substr($words[0], 0, 1));
        }
        return $initials ?: 'U'; 
    }
}