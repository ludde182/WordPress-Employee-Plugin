<?php

function employee_alphabet($employees){
	$alphabet = "abcdefghijklmnopqrstuvwxyz";
	$letters = array();

	for($x = 0; $x < strlen($alphabet); $x++) {
		$current_char = substr($alphabet, $x, 1);
		$letters[$x]["letter"] = $current_char;
		$letters[$x]["link_exists"] = FALSE;
	}
	
	$topletter="";
	foreach($employees as $emp) {
		$meta = get_post_meta( $emp->ID);
		$name_post_type = 'employee';
		$emp_first_letter = substr($meta [$name_post_type.'name'][0], 0, 1);
		if ($topletter !== $emp_first_letter) {
			$topletter = $emp_first_letter;			
			$index = array_search(strtolower($topletter), array_column($letters, 'letter'));

			if (!is_null($index)) {
				$letters[$index]['link_exists'] = TRUE;
			}
		} 	
	}

	return $letters;

}


?>