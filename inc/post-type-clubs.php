function leaguemanager_register_clubs() {
    $labels = array(
                    'name'               => __( 'Clubs', 'post type general name', 'leaguemanager' ),
                    'singular_name'      => __( 'Club', 'post type singular name', 'leaguemanager' ),
                    'menu_name'          => __( 'Club', 'admin menu', 'leaguemanager' ),
                    'add_new'            => __( 'Add New Club', 'club', 'leaguemanager' ),
                    'add_new_item'       => __( 'Add New Club', 'leaguemanager' ),
                    'new_item'           => __( 'New Club', 'leaguemanager' ),
                    'edit_item'          => __( 'Edit Club', 'leaguemanager' ),
                    'view_item'          => __( 'View Club', 'leaguemanager' ),
                    'all_items'          => __( 'All Clubs', 'leaguemanager' ),
                    'search_items'       => __( 'Search Clubs', 'leaguemanager' ),
                    'not_found'          => __( 'No clubs found.', 'leaguemanager' ),
                    'not_found_in_trash' => __( 'No clubs found in Trash.', 'leaguemanager' )
                    );
    $wpclubs_args = array(
                          'labels' => $labels,
                          'public' => true,
                          'menu_icon' => 'dashicons-networking',
                          'publicly_queryable' => true,
                          'show_ui' => true,
                          'query_var' => true,
                          'rewrite' => true,
                          'capability_type' => 'post',
                          'hierarchical' => false,
                          'menu_position' => null,
                          'has_archive' => true,
                          'supports' => array('title','editor','thumbnail', 'page-attributes')
                          );
    
    register_post_type('wpclubs',$wpclubs_args);

}
add_action('init', 'leaguemanager_register_clubs');
