<?php
require_once __DIR__ . '/bootstrap.php';
require_method('POST');

session_unset();
session_destroy();
json_response(['ok'=>true, 'auth'=>false]);
