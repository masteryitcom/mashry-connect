<?php
// API Authentication
if (!defined('ABSPATH')) {
    exit;
}

function mashry_connect_check_api_key(WP_REST_Request $request) {
    $auth_header = $request->get_header("Authorization");

    if ($auth_header && strpos($auth_header, "Bearer ") === 0) {
        $key = substr($auth_header, 7);
        if ($key === MASHRY_CONNECT_API_KEY) {
            return true;
        }
    }

    return new WP_Error(
        "rest_forbidden",
        "Invalid or missing Authorization header.",
        ["status" => 401]
    );
}