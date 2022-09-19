<?php

namespace AgriLife\PeopleAPI;

class Data {

	// Call the webservice for units or people
	public function call( $method, $data ){

	    $function = null;
     $url = 'https://agrilifepeople.tamu.edu/api/';

	    switch ($method){

        case "units" :
          $data = array_merge( ['limit_to_active' =>  0, 'entity_id' => null, 'parent_unit_id' => null, 'search_string' => null, 'limited_units' => null, 'exclude_units' => null], $data );
          break;

        case "people" :
          $data = array_merge( ['person_active_status' => null, 'restrict_to_public_only' => 1, 'search_specializations' => null, 'limited_units' => null, 'limited_entity' => null, 'limited_personnel' => null, 'limited_roles' => null, 'include_directory_profile' => 0, 'include_specializations' => 1, 'include_affiliated' => 0], $data );
          break;

	        default:
            exit("$function is not defined in the switch statement");
	    }

	    $url .= $method;

	    $url = sprintf("%s?%s", $url, http_build_query($data));

	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	    $curl_response = curl_exec($curl);
	    if ($curl_response === false) {
        $info = curl_getinfo($curl);
        curl_close($curl);

        echo "<pre>Error occurred during curl exec.<br/>Additional info:<br/>";
        echo "Curl Response:<br/>";
        unset($curl_response['url']);
        print_r($curl_response);
        echo "Info:<br/>";
        print_r($info);
        die('</pre>');
	    }

	    $response = ['url' => $url, 'json' => json_decode($curl_response, true, 512, JSON_THROW_ON_ERROR), 'raw' => $curl_response];

	    curl_close($curl);

	    return $response;
	}

	/**
	 * Obfuscates email addresses
	 *
	 * @since 1.0
	 *
	 * @param string $email Email to obfuscate
	 *
	 * @return string $link Obfuscated email
	 */
	public function obfuscate( $email ) {

    $link = '';

    // Convert each letter in $email to ASCII
    foreach ( str_split( $email ) as $letter ) {
      $link .= '&#' . ord( $letter ) . ';';
    }

    return $link;
	}

	public function array_orderby(...$args){

		$data = array_shift($args);
    foreach ($args as $n => $field) {
      if (is_string($field)) {
        $tmp = [];
        foreach ($data as $key => $row)
          $tmp[$key] = $row[$field];
      	$args[$n] = $tmp;
      }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);

	}

}
