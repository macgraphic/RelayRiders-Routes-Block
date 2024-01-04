<?php
wp_reset_postdata();
/**
 * Relay Routes Timetable
 * 
 * @param array $block The block settings and attributes.
 **/
?>

<?php
$counter = 1;
$total_miles = 0;
?>

<div id="routesTable">
    <?php if (have_rows('relayleg')) : ?>
        <table class="routesTable">
            <thead>
                <tr>
                    <td>Leg</td>
                    <td>Rider</td>
                    <td>Date</td>
                    <td>Departure Time</td>
                    <td>Pick-up Point</td>
                    <td>Arrival Time</td>
                    <td>Handover Point</td>
                    <td>Miles</td>
                    <td>Travel Time</td>
                    <td>Google Map Link</td>
                    <td>Route Image</td>
                    <td>GPX File</td>
                </tr>
            </thead>
            <tbody>
                <?php while (have_rows('relayleg')) : the_row(); ?>
                    <?php
                    $rider              = get_sub_field('rider_name');
                    $date               = get_sub_field('date');
                    $depart             = get_sub_field('departure_time');
                    $collect            = get_sub_field('pick_up_point');
                    $arrive             = get_sub_field('arrival_time');
                    $handover           = get_sub_field('handover_point');
                    $miles              = get_sub_field('miles');
                    $travel             = get_sub_field('travel_time');
                    $image              = get_sub_field('route_image');
                    $thumb              = $image['url'];
                    $size               = 'small';
                    $thumb              = $image['sizes'][$size];
                    $full               = $image['url'];
                    $total_miles        += $miles;
                    
                    $map_url            = get_sub_field('google_map_link');
                    $map_key            = 'XXXXXX';
                    $gmaplink           = $map_url . '&key=' . $map_key;
                    $waypoints          = extract_waypoints_from_map_url($gmaplink);
                    $gpx_file           = generate_gpx_from_waypoints($waypoints, $counter++);
                    ?>
                    <tr>
                        <td><?php echo esc_attr($counter - 1); ?></td>
                        <td><?php echo esc_attr($rider); ?></td>
                        <td><?php echo esc_attr($date); ?></td>
                        <td><?php echo esc_attr($depart); ?></td>
                        <td><?php echo esc_attr($collect); ?></td>
                        <td><?php echo esc_attr($arrive); ?></td>
                        <td><?php echo esc_attr($handover); ?></td>
                        <td><?php echo esc_attr($miles); ?></td>
                        <td><?php echo esc_attr($travel); ?></td>
                        <td>
                            <a href="<?php echo esc_url($gmaplink); ?>" target="_blank">Google Map</a>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($full); ?>" title="<?php echo esc_attr($title); ?>">
                                <img class="routeImg" src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($alt); ?>" />
                            </a>
                        </td>
                        <td>
                            <?php if ($gpx_file) : ?>
                                <a class="gpxlinkbtn" href="<?php echo esc_url(WP_CONTENT_URL . '/uploads/gpx-files/' . $gpx_file); ?>">GPX File</a>
                            <?php else :
                                echo 'Failed to generate GPX file';
                            endif; ?>
                        </td>

                        <td>
                            <?php var_dump( $gmaplink ); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7">Total Mileage: </td>
                    <td><?php echo $total_miles; ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>

<?php
wp_reset_query();
wp_reset_postdata();
?>

<?php
// Get the waypoints
function extract_waypoints_from_map_url($gmaplink) {
    $waypoints = array();
    $url_parts = parse_url($gmaplink);
    if (isset($url_parts['query'])) {
        parse_str($url_parts['query'], $query_params);
        if (isset($query_params['dir']) && isset($query_params['to'])) {
            $origin = explode(',', $query_params['dir']);
            $destination = explode(',', $query_params['to']);

            // Debug: Print extracted coordinates
            echo 'Origin: Latitude ' . $origin[0] . ', Longitude ' . $origin[1] . '<br>';
            echo 'Destination: Latitude ' . $destination[0] . ', Longitude ' . $destination[1] . '<br>';

            $waypoints[] = array('latitude' => $origin[0], 'longitude' => $origin[1]);
            $waypoints[] = array('latitude' => $destination[0], 'longitude' => $destination[1]);
            return $waypoints;
        }
    }
    return $waypoints;
}

function generate_gpx_from_waypoints($waypoints, $counter) {
    $file_name = 'leg_' . $counter . '.gpx';
    $gpx_content = '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
                    <gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1" creator="YourAppName">';
    foreach ($waypoints as $waypoint) {
        $gpx_content .= '
            <wpt lat="' . $waypoint['latitude'] . '" lon="' . $waypoint['longitude'] . '">
                <name>Waypoint</name>
            </wpt>';
    }
    $gpx_content .= '</gpx>';
    $gpx_file_path = WP_CONTENT_DIR . '/uploads/gpx-files/' . $file_name;
    file_put_contents($gpx_file_path, $gpx_content);

    // Debug: Print the generated GPX content
    echo 'Generated GPX content:<br>' . htmlentities($gpx_content) . '<br>';

    return $file_name;
}
?>

