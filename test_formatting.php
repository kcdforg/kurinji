<?php
// Test the Indian number formatting functions
require 'config.php';

echo "Indian Number Formatting Examples:\n";
echo "==================================\n\n";

$testNumbers = [
    100,
    1000,
    10000,
    100000,
    1000000,
    10000000,
    100000000,
    1000000000,
    1234567.89,
    500000.50,
    50.99,
    0,
    -50000,
];

echo "Regular Numbers (formatIndian):\n";
foreach ($testNumbers as $num) {
    echo "$num => " . formatIndian($num) . "\n";
}

echo "\n\nMoney Format (₹):\n";
foreach ($testNumbers as $num) {
    echo "$num => " . money($num) . "\n";
}

echo "\n\nNumeric Format:\n";
foreach ($testNumbers as $num) {
    echo "$num => " . num($num, 2) . "\n";
}
