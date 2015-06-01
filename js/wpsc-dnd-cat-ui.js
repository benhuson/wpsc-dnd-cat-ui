
jQuery(document).ready(function() {

	if ( pagenow == 'wpsc-product_page_wpsc_dnd_cat_ui' ) {

		jQuery( '.sortable-posts' ).sortable( {
			update      : function( event, ui ) {
				ui.item.find( '.loader' ).show();
				var data = {
					action      : 'dragndrop_save_product_order',
					category_id : jQuery( 'select#wpsc_product_category option:selected' ).val(),
					post        : jQuery( '.sortable-posts' ).sortable( 'toArray' )
				};
				jQuery.post( ajaxurl, data, function( response ) {
					jQuery( '.sortable-posts .loader' ).hide();
				} );
			},
			items       : '.post',
			axis        : 'y',
			containment : '.sortable-posts',
			cursor      : 'move',
		} );

	}

} );
