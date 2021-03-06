<html>
<body>
<?php

$inputcsv = "orgs/h1_2017-11-04-21-03-26_fixes.csv";
$inputretrocsv = "orgs/h1_2017-11-04-21-03-23_retro.csv";
$outputcsv = "h1-output.csv";

// load all of the retro data into an array
$retro = Array();
if (($handle = fopen($inputretrocsv, "r")) !== FALSE) {

	fgetcsv($handle); // Advance the string by one to ignore the headers

    while (($row = fgetcsv($handle)) !== FALSE) {
    	$retro[$row[0]] = $row;
    }
}




// load all of the point information into an Array
$points = Array();
if (($inputhandle = fopen($inputcsv, "r")) !== FALSE) {
    while (($row = fgetcsv($inputhandle)) !== FALSE) {
    	array_push($points, $row);
    }
}
$headers = array_shift($points);
$points = array_reverse($points);
array_unshift($points, $headers);



// Add the new headers we needed
array_push($points[0], "0.2 Alpha");
array_push($points[0], "0.5 Alpha");
array_push($points[0], "0.2 Background");
array_push($points[0], "0.5 Background");
array_push($points[0], "Barcode");
array_push($points[0], "Support");
array_push($points[0], "Support Condition");



// processing the data
$previous_location = '';
$previous_row = Array();

$output = Array();
foreach($points as $index => &$row) {

	// Ignore the row with the Headers
	if($index == 0) {
		array_push($output, $row);
		continue;
	}

	// ****************************************************
	// Fill in value mods / substitutions / macros logic here
	// ****************************************************	
	// Use same location as previous entry if marked as 0
	if(trim($row[6]) == "0") {
		$row[6] = $previous_location;
	}
	else {
		$previous_location = $row[6];
	}


	// Convert shorthand to a proper sign name
	//$row[7] = convertShorthand($row[7]);
	//$row[9] = convertConditionShorthand($row[9]);
	//$row[10] = convertSizeShorthand($row[10], $row);

    // Add new fields from the retro database
	$entries = explode(" ", $row[7]);

    if(sizeof($entries) == 1)
    {
    	// If I left a name completely blank, it means to draw from the previous entry
    	if ($entries[0] == '') {
    		// Draw the needed entries from the previous row
    		$row[6] = $previous_row[6];
    		$row[7] = $previous_row[7];
    		$row[8] = $previous_row[8];
    		$row[9] = $previous_row[9];
    		$row[10] = $previous_row[10];
    		$row[11] = $previous_row[11];
    		$row[12] = $previous_row[12];
    		$row[14] = $previous_row[14];
    	}


		// SUPPORT
		// Add support conditions by spliting the value of [8] into distinct fields
		if($row[8] != '') {
			$regex = preg_match('/(2|u|h|l|s|m|w|o|t|s4|4s|s6|6s|d4|4d|d6|6d)([vgflp])/', strtolower($row[8]), $matches);
				if(!isset($matches[1]))
					var_dump($row[0]);
			$row[29] = convertSupportShorthand($matches[1]);
			$row[30] = $regex === 1 ? convertConditionShorthand($matches[2]) : '';
		}

		// Sign Stats
		$row[7] = convertShorthand($row[7]);
		$row[9] = convertConditionShorthand($row[9]);
		$row[10] = convertSizeShorthand($row[10], $row);


    	if(isset($retro[$row[13]])) {
    		$row[24] = isset($retro[$row[13]][1]) ? trim($retro[$row[13]][1]) : '';
    		$row[25] = isset($retro[$row[13]][2]) ? trim($retro[$row[13]][2]) : '';
    		$row[26] = isset($retro[$row[13]][3]) ? trim($retro[$row[13]][3]) : '';
    		$row[27] = isset($retro[$row[13]][4]) ? trim($retro[$row[13]][4]) : '';
    		$row[28] = isset($retro[$row[13]][9]) ? trim($retro[$row[13]][9]) : '';
    	}
    	else
    	{
    		$row[24] = '';
    		$row[25] = '';
    		$row[26] = '';
    		$row[27] = '';
    		$row[28] = '';
    	}
    	ksort($row);
    	array_push($output, $row);


		// Store the previous row in case we need to draw from it later
		$previous_row = $row;
    }
    else // deal with multiple entries 
    {
    	$supports = explode(" ", strtolower(trim($row[8])));
    	$conditions = str_split(str_replace(" ", "", $row[9]));
    	$facings =  str_split(str_replace(" ", "", $row[11]));
    	$sizes =  explode(" ", $row[10]);
    	$heights = explode(" ", $row[12]);
    	$retroIDs = explode(" ", $row[13]);

		$current_support = Array();

    	foreach($entries as $index => $name) {
    		if(trim($name) == "") {
    			continue;
    		}

    		$temprow = $row;

    		// modify the rows per each entry
    		$temprow[7]  = convertShorthand($name);	// name

			// Add support conditions by spliting the value of [8] into distinct fields
			if(isset($supports[$index])) {
				$regex = preg_match('/(2|u|h|l|s|m|w|o|t|s4|4s|s6|6s|d4|4d|d6|6d)([vgflp])/', $supports[$index], $matches);
				if(!isset($matches[1])) {
					var_dump($row[0]);
					var_dump($row);
				}
				$temprow[29] = convertSupportShorthand($matches[1]);
				$temprow[30] = convertConditionShorthand( $regex == 1 ? $matches[2] : '' );
				$current_support = Array($temprow[29], $temprow[30]);
			}
			else {
				$temprow[29] = $current_support[0];
				$temprow[30] = $current_support[1];
			}

    		$temprow[9]  = isset($conditions[$index]) ? convertConditionShorthand($conditions[$index]) : convertConditionShorthand('');
    		$temprow[10] = isset($sizes[$index]) ? convertSizeShorthand($sizes[$index], $row) : convertSizeShorthand('', $row);
    		$temprow[11] = isset($facings[$index]) ? $facings[$index] : '';
    		$temprow[12] = isset($heights[$index]) ? $heights[$index] : '';
    		$temprow[13] = isset($retroIDs[$index]) ? $retroIDs[$index] : '';

        	// Add new fields from the retro database
        	if(isset($retro[$temprow[13]])) {
        		$temprow[24] = isset($temprow[13][1]) ? trim($retro[$temprow[13]][1]) : '';
        		$temprow[25] = isset($temprow[13][2]) ? trim($retro[$temprow[13]][2]) : '';
        		$temprow[26] = isset($temprow[13][3]) ? trim($retro[$temprow[13]][3]) : '';
        		$temprow[27] = isset($temprow[13][4]) ? trim($retro[$temprow[13]][4]) : '';
        		$temprow[28] = isset($temprow[13][9]) ? trim($retro[$temprow[13]][9]) : '';
        	}
        	else
        	{
        		$temprow[24] = '';
        		$temprow[25] = '';
        		$temprow[26] = '';
        		$temprow[27] = '';
        		$temprow[28] = '';
        	}

        	ksort($temprow);
    		array_push($output, $temprow);
    	}

		$previous_row = $temprow;
    }
}

echo "<h1>Complete</h1>";
echo "<p>Contents:</p>";
echo nl2br(print_r($output, true));

if (($outputhandle = fopen($outputcsv, "w")) !== FALSE) {
	foreach($output as $row) {
		fputcsv($outputhandle, $row);
	}
}


function convertShorthand($str) {
	switch(trim(strtolower($str))) {
		case 'n':
			return 'name';
		case 's':
			return 'stop';
	}

	return $str;
}

function convertSupportShorthand($str) {
	switch(trim(strtolower($str))) {
		case 'h':
			return 'hydropole';
		case 'l':
			return 'lightpost';
		case 'u':
			return 'u-channel';
		case 'w':
			return 'wood';
		case '2':
			return '2in metal post';
		case 's':
			return 'signal pole';
		case 'm':
			return 'mast';
		case 'd4':
		case '4d':
			return 'dual 4x4';
		case '6d':
		case 'd6':
			return 'dual 6x6';
		case 's6':
		case '6s':
			return 'single 6x6';
		case 's4':
		case '4s':
			return 'single 4x4';
	}
	return $str;
}

function convertConditionShorthand($str) {
	switch(trim(strtolower($str))) {
		case '':
		case 'v':
			return 'Very Good';
		case 'g':
			return 'Good';
		case 'f':
			return 'Fair';
		case 'l':
			return 'Less Poor';
		case 'p':
			return 'Poor';
	}
	return $str;
}

function convertSizeShorthand($str, $row) {
	switch(trim(strtolower($str))) {
		case '':
		case 'd':
			return 'Default';

//		case 'sm':
//			if
//			return 'Good';
	}
	return $str;
}

?>
</body>
</html>