<?php
/**
 * Template Name: People
 * Description: Show a list of people from the api
 */

add_action( 'wp_enqueue_scripts', 'apa_register_public_styles' );
add_action( 'wp_enqueue_scripts', 'apa_enqueue_public_styles' );

/**
 * Registers all styles used within the theme
 * @since 1.0.0
 * @return void
 */
function apa_register_public_styles() {

    wp_register_style(
        'apa-template-styles',
        AG_PEOPLEAPI_DIR_URL . 'css/styles.css',
        array(),
        '',
        'screen'
    );

}

/**
 * Enqueues styles used globally
 * @since 1.0.0
 * @global $wp_styles
 * @return void
 */
function apa_enqueue_public_styles() {

    wp_enqueue_style( 'apa-template-styles' );

}


/**
 * Grabs the county office info from AG IT's API and
 * echos it out.
 */
function people_list() {

    if( empty(get_option('options_research_center_name')) )
        return;

    $single_person = get_query_var( 'single_person', 'false' );

    $api = new AgriLife\PeopleAPI\Data();

    $transientname = 'county_office';

    $transient = get_transient( $transientname );

    if(!$transient){
        $method = 'people';
        $applicationID = 3;
        $county = get_option('options_research_center_name');
        $data = array(
          'site_id' => $applicationID,
          'entity_id' => 2,
          'limited_units' => strval($county),
          'limit_to_active' => 0,
          'include_directory_profile' => 1,
          'validation_key' => base64_encode( md5( $applicationID . AGRILIFE_API_KEY, true ) ),
        );
        set_transient( $transientname, $api->call( $method, $data ), DAY_IN_SECONDS );
        $transient = get_transient( $transientname );
    }

    $results = $transient['json'];

    if( $results['status'] == 200 ){
        $dataObj = $results['people'];

        if($single_person == 'true'){

            echo '<div class="row">';

            $id = get_query_var( 'person_id', '0' );
            $people = $transient['json']['people'];
            $index = array_search($id, array_column($people, 'person_id'));
            $person = $people[$index];

            $name = !empty($person['prefix']) ? $person['prefix'] . ' ' : '';
            $mailname = $name;

            $name .= $person['preferred_name'] . ' ';
            $mailname = substr($person['preferred_name'], 0, 1) . '. ';

            if(!empty($person['middle_initial']))
                $name .= $person['middle_initial'] . '. ';

            $name .= $person['last_name'];
            $mailname .= $person['last_name'];

            echo sprintf('<div class="column small-12 medium-8 large-8"><h2>%s<br />%s</h2><p><strong>%s</strong><br />%s<br />%s</p>',
                $name,
                $person['positions'][0]['position_title'],
                $mailname,
                $api->obfuscate( $person['email_address'] ),
                $person['phone_number']
            );

            $bio = $person['directory_profile']['general_bio'];
            if(!empty($bio))
                $bio = '<p>' . $bio . '</p>';

            $publications = $person['directory_profile']['publications'];
            if(!empty($publications))
                $publications = '<p>' . $publications . '</p>';

            $research = $person['directory_profile']['research'];
            if(!empty($research))
                $research = '<p>' . $research . '</p>';

            $education = $person['directory_profile']['education'];
            if(!empty($education))
                $education = '<p>' . $education . '</p>';

            $honors = $person['directory_profile']['honors'];
            if(!empty($honors))
                $honors = '<p>' . $honors . '</p>';

            echo sprintf('<p>%s</p>%s%s%s%s</div>',
                $general_bio,
                $publications,
                $research,
                $education,
                $honors
            );

            $photo = '';
            if( array_key_exists('_links', $person['directory_profile']) && array_key_exists('picture', $person['directory_profile']['_links']) ){
                $photo = sprintf( '<div class="photo-container"><img class="img-responsive" src="%s" alt="%s"></div>', $person['directory_profile']['_links']['picture']['href'], $name );
            }

            $courses = $person['directory_profile']['courses'];
            if(!empty($courses))
                $courses = '<p>' . $courses . '</p>';

            echo sprintf('<div class="column small-12 medium-4 large-4">%s%s</div>',
                $photo,
                $courses
            );

            echo '</div>';

        } else {

            echo '<div class="row">';

            // Alphabetize
            $people = $api->array_orderby($dataObj, 'last_name', SORT_ASC,SORT_NATURAL|SORT_FLAG_CASE);

            // Output
            foreach ( $people as $key => $item ) {

                // Determine code to output
                $entry = '
                <div class="column small-12 medium-6 large-3">
                    <h3>%s%s%s</h3>%s
                    <div class="summary"><p><strong>%s</strong><br>%s<br><a href="mailto:%s">%s</a><br>%s</p>%s</div>
                </div>';

                $linkopen = sprintf('<a href="%s">', get_permalink() . '?single_person=true&person_id=' . $item['person_id']);
                $linkclose = '</a>';

                $name = !empty($item['prefix']) ? $item['prefix'] . ' ' : '';

                $name .= $item['preferred_name'] . ' ';

                if(!empty($item['middle_initial']))
                    $name .= $item['middle_initial'] . '. ';

                $name .= $item['last_name'];

                $photo = '';
                if( array_key_exists('_links', $item['directory_profile']) && array_key_exists('picture', $item['directory_profile']['_links']) ){
                    $photo = sprintf( '
                    <div class="photo-container"><img class="img-responsive" src="%s" alt="%s"></div>', $item['directory_profile']['_links']['picture']['href'], $name );
                }

                $publications = '';
                if( array_key_exists('publications', $item['directory_profile']) && !empty($item['directory_profile']['publications']) ){
                    $disallowed = array(
                        '/<(iframe|script|audio|video|svg)[^<]+<\/(iframe|script|audio|video|svg)>/',
                        '/<(iframe|img|script|audio|video|svg)[^>]*>/'
                    );
                    $publications = preg_replace( $disallowed, '', $item['directory_profile']['publications'] );
                }

                // Add code to page
                if($key > 0 && $key % 4 == 0){
                    echo '
            </div>
            <div class="row">';
                }

                echo sprintf( $entry,
                    $linkopen,
                    $name,
                    $linkclose,
                    $photo,
                    $item['positions'][0]['position_title'],
                    $item['positions'][0]['entity_name'],
                    $api->obfuscate( $item['email_address'] ),
                    $api->obfuscate( $item['email_address'] ),
                    $item['phone_number'],
                    $publications
                );

            }
            echo '</div>';
        }
    } else {
        $return = '<h2>Error</h2><pre>' . $err . '</pre>';
    }
}
get_header(); ?>

<div id="wrap">
    <div id="content" role="main">
        <?php people_list(); ?>
    </div>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
