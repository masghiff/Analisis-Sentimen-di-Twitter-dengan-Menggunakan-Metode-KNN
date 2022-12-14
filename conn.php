<?php

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 0);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$conn = new mysqli('localhost', 'root', '', 'tweets');
