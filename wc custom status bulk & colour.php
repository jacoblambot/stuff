<?php
/** 
 * Register new status
**/
function register_imported_order_status() {
    register_post_status( 'wc-imported', array(
        'label'                     => 'Imported',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Imported <span class="count">(%s)</span>', 'Imported <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'register_imported_order_status' );

// Add to list of WC Order statuses
function add_imported_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-imported'] = 'Imported';
        }
    }

    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_imported_to_order_statuses' );


/*
 * Add your custom bulk action in dropdown
 * @since 3.5.0
 */
add_filter( 'bulk_actions-edit-shop_order', 'misha_register_bulk_action' ); // edit-shop_order is the screen ID of the orders page
 
function misha_register_bulk_action( $bulk_actions ) {
 
    $bulk_actions['mark_imported'] = 'Mark Imported'; // <option value="mark_imported">Mark Imported</option>
    return $bulk_actions;
 
}
/*
 * Bulk action handler
 * Make sure that "action name" in the hook is the same like the option value from the above function
 */
add_action( 'admin_action_mark_imported', 'misha_bulk_process_custom_status' ); // admin_action_{action name}
 
function misha_bulk_process_custom_status() {
 
    // if an array with order IDs is not presented, exit the function
    if( !isset( $_REQUEST['post'] ) && !is_array( $_REQUEST['post'] ) )
        return;
 
    foreach( $_REQUEST['post'] as $order_id ) {
 
        $order = new WC_Order( $order_id );
        $order_note = 'That\'s what happened by bulk edit:';
        $order->update_status( 'imported', $order_note, true ); // 
 
    }
 
    //using add_query_arg() is not required, you can build your URL inline
    $location = add_query_arg( array(
            'post_type' => 'shop_order',
        'marked_imported' => 1, // markED_imported=1 is  the $_GET variable for notices
        'changed' => count( $_REQUEST['post'] ), // number of changed orders
        'ids' => join( $_REQUEST['post'], ',' ),
        'post_status' => 'all'
    ), 'edit.php' );
 
    wp_redirect( admin_url( $location ) );
    exit;
 
}
 
/*
 * Notices
 */
add_action('admin_notices', 'misha_custom_order_status_notices');
 
function misha_custom_order_status_notices() {
 
    global $pagenow, $typenow;
 
    if( $typenow == 'shop_order' 
     && $pagenow == 'edit.php'
     && isset( $_REQUEST['marked_imported'] )
     && $_REQUEST['marked_imported'] == 1
     && isset( $_REQUEST['changed'] ) ) {
 
        $message = sprintf( _n( 'Order status changed.', '%s order statuses changed.', $_REQUEST['changed'] ), number_format_i18n( $_REQUEST['changed'] ) );
        echo "<div class=\"updated\"><p>{$message}</p></div>";
 
    }
 
}

/*
 * Change WC button colour.
 */
add_action('admin_head', 'styling_admin_order_list' );
function styling_admin_order_list() {
    global $pagenow, $post;

    if( $pagenow != 'edit.php') return; // Exit
    if( get_post_type($post->ID) != 'shop_order' ) return; // Exit

    // HERE we set your custom status
    $order_status = 'imported'; // <==== HERE
    ?>
    <style>
        .order-status.status-<?php echo sanitize_title( $order_status ); ?> {
            background: #ffccff;
            color: #0d0d0d;
        }
    </style>
    <?php
}
