<?php

define('BEAM_WIDTH', 3.5);
define('BOARD_LENGTH', ( 8 * 12 ));
define('WASTE_MULTIPLIER', 0.1);
define('STUDS_OFFSET', 16);

// beams are required every 20 feet at minimum
define('BEAMS_REQUIRED_EVERY_INCHES', (20 * 12));
define('FULL_BOARDS_IN_SECTION', floor( BEAMS_REQUIRED_EVERY_INCHES / BOARD_LENGTH ));
define('FULL_BOARD_SECTION_SIZE', FULL_BOARDS_IN_SECTION * BOARD_LENGTH);

function convertFeetToInches( $feet ){

    return $feet * 12;

}

function getPlatesInLength( $inches ){

    // devide the length by 96 inches (8 feet) and round up
    // multiply by two because we're doing the top and bottom in one calculation
    return ceil( $inches / BOARD_LENGTH ) * 2;

}

function getStudsInLength( $inches ){

    // calculate the studs across
    // round up to account for the last one
    $studs = ceil( $inches / STUDS_OFFSET );

    // make sure we add an end piece if we have a perfect multiple of 16
    $isNotPerfectWidth = min( $inches % STUDS_OFFSET, 1 );
    $perfectWidthExtension = ($isNotPerfectWidth * -1) + 1;
    return $studs + $perfectWidthExtension;

}

function getBoardsInLength( $inches ) {

    $plates = getPlatesInLength( $inches );
    $studs = getStudsInLength( $inches );

    return $plates + $studs;

}

function getRequiredBeamsInLength( $inches ){

    // for every 20 feet, we need one beam
    // we know our wall is at least 20 feet, so calculate the required beams for the REST of the wall
    // if our wall is under 20 feet, this will return zero
    $wallLengthOverMinRequired = getWallLengthOverMinimumRequiredBeforeBeam( $inches );
    $wallLengthPlusBeam = BEAMS_REQUIRED_EVERY_INCHES + BEAM_WIDTH;
    $requiredBeams = ceil( $wallLengthOverMinRequired / $wallLengthPlusBeam );

    return $requiredBeams;

}

function getWallLengthOverMinimumRequiredBeforeBeam( $inches ) {
    return max( $inches - BEAMS_REQUIRED_EVERY_INCHES, 0);
}

// any number of inches past BEAMS_REQUIRED_EVERY_INCHES will return 1
// any number of inches below or equal to BEAMS_REQUIRED_EVERY_INCHES return 0
function isBeamRequired( $inches ) {
    
    // negative numbers are zero
    $wallLengthOverMinRequired = max($inches - BEAMS_REQUIRED_EVERY_INCHES, 0);

    // remove decimals
    $wholeNumber = ceil( $wallLengthOverMinRequired );

    // returns 1 (at least one beam required ) or 0 (no beams required)
    $isBeamRequired = min( $wholeNumber, 1 );

    return $isBeamRequired;

}

function getFullSections( $inches, $beams ){

    // how many inches will we remove from a section between beams to get to the last full board
    $inchesReducedPerSection = BEAMS_REQUIRED_EVERY_INCHES - FULL_BOARD_SECTION_SIZE;

    // how big is the last section if all beams are at BEAMS_REQUIRED_EVERY_INCHES
    $lastSectionSize = $inches - ( $beams * ( BEAMS_REQUIRED_EVERY_INCHES + BEAM_WIDTH ));

    // how many inches of boards can we add to the last section before it will add an additional beam to the structure
    $remainingBeforeNewBeam = BEAMS_REQUIRED_EVERY_INCHES - $lastSectionSize;

    // how many complete portions of the inchesReducedPerSection can we move to the last section
    $fullSections = floor( $remainingBeforeNewBeam / $inchesReducedPerSection );

    // even if we can FIT fullSections moved into the last portion, we might not HAVE them in our length
    $fullSections = min( $fullSections, $beams );

    // safeguard inches not requiring a beam and return value
    $fullSections = $fullSections * isBeamRequired( $inches );

    return $fullSections;

}

function getLastSectionSize( $inches, $beams ){

    $fullSections = getFullSections( $inches, $beams );
    $lastSectionSize = $inches - ( $beams * BEAM_WIDTH ) - ( $fullSections * FULL_BOARD_SECTION_SIZE );

    return $lastSectionSize;

}

function buildWall( $inches ){

    // get required beams
    $requiredBeams = getRequiredBeamsInLength( $inches );
    $fullSections = getFullSections( $inches, $requiredBeams );
    $lastSectionSize =  getLastSectionSize( $inches, $requiredBeams );
    $studs = getBoardsInLength( FULL_BOARD_SECTION_SIZE ) * $fullSections + getBoardsInLength( $lastSectionSize );

    $wall = new stdClass();
    $wall->studs = $studs;
    $wall->beams = $requiredBeams;

    return $wall;

}

function accountForWaste( $items ) {

    return ceil( $items * WASTE_MULTIPLIER ) + $items;

}

function calculateHouseRequirements( $width, $length, $inches=false ){

    // convert feet to inches
    $outerWidthOfHouse = $inches ? $width : convertFeetToInches( $width );
    $outerLengthOfHouse = $inches ? $length : convertFeetToInches( $length );
    
    // calculate the space inbetween corner beams
    $innerWidthOfHouse = $outerWidthOfHouse - ( BEAM_WIDTH * 2 );
    $innerLengthOfHouse = $outerLengthOfHouse - ( BEAM_WIDTH * 2 );

    $wall1 = buildWall( $innerWidthOfHouse );
    $wall2 = buildWall( $innerLengthOfHouse );

    $studs = accountForWaste(( $wall1->studs + $wall2->studs ) * 2);
    $beams = accountForWaste((( $wall1->beams + $wall2->beams ) * 2) + 4);

    $response = new stdClass();
    $response->width = $outerWidthOfHouse;
    $response->length = $outerLengthOfHouse;
    $response->studs = $studs;
    $response->beams = $beams;

    return $response;

}