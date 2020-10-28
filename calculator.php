<?php

require './gerald.php';

// node: console.log
// python: print
// java: System.out.println
// php: echo
$widthIndex = array_search("--width", $argv);
$lengthIndex = array_search("--length", $argv);

$width = $argv[ $widthIndex + 1 ];
$length = $argv[ $lengthIndex + 1 ];
$inches = array_search("--inches", $argv);

$response = calculateHouseRequirements( $width, $length, $inches );

var_dump( $response );