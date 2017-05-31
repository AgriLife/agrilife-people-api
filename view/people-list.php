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

        echo '<div class="row">';

        // Alphabetize
        $people = $api->array_orderby($dataObj, 'last_name', SORT_ASC,SORT_NATURAL|SORT_FLAG_CASE);

        // Output
        foreach ( $people as $key => $item ) {

            // Determine code to output
            $entry = '
            <div class="column small-12 medium-6 large-3">
                <h3>%s</h3>%s
                <div class="summary"><p><strong>%s</strong><br>%s<br><a href="mailto:%s">%s</a><br>%s</p>%s</div>
            </div>';

            $name = '';

            if(!empty($item['prefix']))
                $name = $item['prefix'] . ' ';

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
                $name,
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
