<?php
/*
Plugin Name: TSS Media View
Description: A better way to manage media items
Author: Treasury Shared Services
Version: 1.0
*/

namespace TSS;

class Media_View
{
	function __construct() {
		add_action( 'admin_menu', array( &$this, 'add_menu_pages' ) );
		add_action( 'init', array( &$this, 'register_taxonomies' ) );

		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_get_attachments_filtered', array( &$this, 'get_attachments_filtered' ) );
		add_action( 'wp_ajax_get_categories', array( &$this, 'get_categories' ) );
		add_action( 'wp_ajax_multi_select_update', array( &$this, 'multi_select_update' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'media-view', plugins_url( 'media-view.css', __FILE__ ) );
		wp_enqueue_script( 'wp-util' );
	}

	public function register_taxonomies() {
		// Use attachment_category and attachment_tag, because that's we've already got in quite a few sites, so let's re-use that

		if( false === taxonomy_exists( 'attachment_category' ) ) {
			$labels = array(
				'name' => _x( 'Att. Categories', 'taxonomy_name_plural', 'media-library-assistant' ),
				'singular_name' => _x( 'Att. Category', 'taxonomy_name_singular', 'media-library-assistant' ),
				'search_items' => __( 'Search Att. Categories', 'media-library-assistant' ),
				'all_items' => __( 'All Att. Categories', 'media-library-assistant' ),
				'parent_item' => __( 'Parent Att. Category', 'media-library-assistant' ),
				'parent_item_colon' => __( 'Parent Att. Category', 'media-library-assistant' ) . ':',
				'edit_item' => __( 'Edit Att. Category', 'media-library-assistant' ),
				'update_item' => __( 'Update Att. Category', 'media-library-assistant' ),
				'add_new_item' => sprintf( __( 'Add New %1$s', 'media-library-assistant' ), __( 'Att. Category', 'media-library-assistant' ) ),
				'new_item_name' => __( 'New Att. Category Name', 'media-library-assistant' ),
				'menu_name' => __( 'Att. Category', 'media-library-assistant' )
			);

			register_taxonomy(
				'attachment_category',
				array( 'attachment' ),
				array(
					'hierarchical' => true,
					'labels' => $labels,
					'show_ui' => true,
					'query_var' => true,
					'rewrite' => true,
					'update_count_callback' => '_update_generic_term_count'
				)
			);
		}

		if( false === taxonomy_exists( 'attachment_tag' ) ) {
			$labels = array(
				'name' => _x( 'Att. Tags', 'taxonomy_name_plural', 'media-library-assistant' ),
				'singular_name' => _x( 'Att. Tag', 'taxonomy_name_singular', 'media-library-assistant' ),
				'search_items' => __( 'Search Att. Tags', 'media-library-assistant' ),
				'all_items' => __( 'All Att. Tags', 'media-library-assistant' ),
				'parent_item' => __( 'Parent Att. Tag', 'media-library-assistant' ),
				'parent_item_colon' => __( 'Parent Att. Tag', 'media-library-assistant' ) . ':',
				'edit_item' => __( 'Edit Att. Tag', 'media-library-assistant' ),
				'update_item' => __( 'Update Att. Tag', 'media-library-assistant' ),
				'add_new_item' => sprintf( __( 'Add New %1$s', 'media-library-assistant' ), __( 'Att. Tag', 'media-library-assistant' ) ),
				'new_item_name' => __( 'New Att. Tag Name', 'media-library-assistant' ),
				'menu_name' => __( 'Att. Tag', 'media-library-assistant' )
			);

			register_taxonomy(
				'attachment_tag',
				array( 'attachment' ),
				array(
					'hierarchical' => false,
					'labels' => $labels,
					'show_ui' => true,
					'update_count_callback' => '_update_post_term_count',
					'query_var' => true,
					'rewrite' => true,
					'update_count_callback' => '_update_generic_term_count'
				)
			);
		}
	}


	public function add_menu_pages() {
		add_menu_page( 'Media View', 'Media View', 'edit_posts', 'media-view', array( &$this, 'media_view_page' ), 'dashicons-admin-media' );
	}


	public function media_view_page() {
		?>

		<h1>Media</h1>

		<div class="media-box">
			<div class="category-list">
				<!--<div class="column-heading"><span>Category</span></div>-->
				<ul class="list"></ul>
			</div>
			<div class="attachment-list">
				<!--<div class="column-heading"><span>Attachments</span></div>-->
				<ul class="list"></ul>
			</div>
			<div class="attachment-detail">
				<!--<div class="column-heading"><span>Attachment</span></div>-->
				<div class="detail"></div>
			</div>
		</div>

		<script type="text/html" id="tmpl-category-list">
			<# _.each( data.categories, function( c ) { #>
				<li><a href="#" class="filter-link" data-category="{{ c.term_id }}">{{ c.name }}</a></li>
				<!--<# _.each( data.tags, function( t ) { #>
					<li>
						<a href="#" class="filter-link" data-category="{{ c.term_id }}" data-tag="{{ t.term_id }}">{{ t.name }}</a>
					</li>
				<# }); #>-->
			<# }); #>
		</script>
		<script type="text/html" id="tmpl-attachment-list">
			<# _.each( data, function( a, index ) { #>
				<li>
					<input type="checkbox" data-id="{{ a.ID }}">
					<a href="#" class="detail-link" data-id="{{ index }}">{{ a.post_title }}</a>
				</li>
			<# }); #>
		</script>
		<script type="text/html" id="tmpl-attachment-detail">
			<form method="post">
				<h3>{{ data.post_title }}</h3>

				<div class="curtime">
					<span id="timestamp">Uploaded on: <b>{{ data.post_date }}</b></span>
				</div>

				<# if ( data.guid.substring(0,5) == "iaimage" ) { #>
					<div class="dashicons-before dashicons-admin-post">
						<span>{{ data.guid }}</span>
					</div>
				<# } #>

				<img src="{{ data.guid }}">
				<h4>Alt text</h4>
				<p>{{ data.alt_text }}</p>
				<h4>Caption</h4>
				<p>{{ data.post_excerpt }}</p>
				<h4>Description</h4>
				<p>{{ data.post_content }}</p>

				<input type="submit" class="button-primary" name="save" id="publish" value="Update">
			</form>
		</script>
		<script type="text/html" id="tmpl-multi-select">
			<form method="post">
				<input type="hidden" name="action" value="multi_select_update">
				<# _.each( data.selections, function( id ) { #>
					<input type="hidden" name="attachment_id[]" value="{{ id }}">
				<# }); #>

				<h3>Update multiple selections</h3>

				<div id="attachment_category-all" class="tabs-panel">
					<ul id="attachment_categorychecklist" data-wp-lists="list:attachment_category" class="categorychecklist form-no-clear">
						<# _.each( data.taxonomies.categories, function( c, index ) { #>
							<li id="attachment_category-{{ c.term_id }}" class="popular-category">
								<label class="selectit"><input value="{{ c.term_id }}" type="checkbox" name="tax_input[attachment_category][]" id="in-attachment_category-{{ c.term_id }}" > {{ c.name }}</label>
							</li>
						<# }); #>
					</ul>
				</div>

				<div id="attachment_tag-all" class="tabs-panel">
					<ul id="attachment_tagchecklist" data-wp-lists="list:attachment_category" class="categorychecklist form-no-clear">
						<# _.each( data.taxonomies.tags, function( c, index ) { #>
							<li id="attachment_tag-{{ c.term_id }}">
								<label class="selectit"><input value="{{ c.term_id }}" type="checkbox" name="tax_input[attachment_tag][]" id="in-attachment_tag-{{ c.term_id }}" > {{ c.name }}</label>
							</li>
						<# }); #>
					</ul>
				</div>

				<input type="submit" class="button-primary" name="save" id="publish" value="Update">
			</form>
		</script>
		<script>
		jQuery( document ).ready( function($) {
			var attachments;
			var single_view = '';
			var categories;

			$.post( ajaxurl, {'action': 'get_categories'}, function(res) {
				categories = $.parseJSON( res );
				var template = wp.template( 'category-list' );
				$('.category-list > .list').html( template( categories ) );
			});

			$('.media-box').on( 'submit', 'form', function(e) {
				e.preventDefault();

				var att = [];
				 $('input[name="attachment_id[]"]').each( function() {
					att.push( $(this).val() );
				});

				var tax = {'attachment_category': [], 'attachment_tag': []};
				 $('#attachment_categorychecklist input[type="checkbox"]:checked').each( function() {
					tax.attachment_category.push( $(this).val() );
				});
				 $('#attachment_tagchecklist input[type="checkbox"]:checked').each( function() {
					tax.attachment_tag.push( $(this).val() );
				});

				var post_data = {
					'action': $('input[name="action"]').val(),
					'attachments': att,
					'taxonomies': tax
				};
				$.post( ajaxurl, post_data, function(res) {
					console.log(res);
				});
				
				console.log( post_data );

				console.log("Submitted");
			});

			$('.category-list').on( 'click', 'a.filter-link', function(e) {
				var category = $(this).attr('data-category');
				var tag = $(this).attr('data-tag');

				$('a.filter-link').removeClass('active');
				$(this).addClass('active');

				var data = {
					'action': 'get_attachments_filtered',
					'category': category,
					'tag': tag
				};

				$.post( ajaxurl, data, function(res) {
					attachments = $.parseJSON( res );

					var template = wp.template( 'attachment-list' );
					$('.attachment-list > .list').html( template( attachments ) );
				});
				
				e.preventDefault();
			});

			$('.attachment-list').on( 'click', 'a.detail-link', function(e) {
				$('a.detail-link').removeClass('active');
				$(this).addClass('active');

				// Clear multi-selects if someone clicks on a single item
				$('.attachment-list input[type="checkbox"]:checked').each( function() { $(this).attr('checked', false); });
				single_view = '';

				var id = $(this).attr('data-id');
				var template = wp.template( 'attachment-detail' );
				$('.attachment-detail > .detail').html( template( attachments[id] ) );

				e.preventDefault();
			});
			
			$('.attachment-list').on( 'change', 'input[type="checkbox"]', function(e) {

				// Preserve the current view for when all checkboxes are unchecked
				if( single_view.length == 0 ) {
					single_view = $('.attachment-detail > .detail').html();
				}

				var selections = [];
				 $('.attachment-list input[type="checkbox"]:checked').each( function() {
					selections.push( $(this).attr('data-id') );
				});

				// If nothing selected, show the preserved single view
				// Otherwise, show the multi-select edit view
				if( selections.length == 0 ) {
					var html = single_view;
					single_view = '';
				} else {
					var data = {
						'selections': selections,
						'taxonomies': categories
					};
					var template = wp.template( 'multi-select' );
					var html = template( data );
				}
				$('.attachment-detail > .detail').html( html );
			});
		});
		</script>
		<?php
	}


	public function get_attachments_filtered() {
		$category = isset( $_POST['category'] ) ? $_POST['category'] : '';
		$tag = isset( $_POST['tag'] ) ? $_POST['tag'] : '';

		$tax_query = array(
			array(
				'taxonomy'	=> 'attachment_category',
				'field'		=> 'term_id',
				'terms'		=> sprintf( '%d', $category )
			)
		);
		
		if( false === empty( $tag ) ) {
			$tax_query['relation'] = 'AND';
			$tax_query[] = array(
				'taxonomy'	=> 'attachment_tag',
				'field'		=> 'term_id',
				'terms'		=> sprintf( '%d', $tag )
			);
		}

		$args = array(
			'post_type'	=> 'attachment',
			'post_status'	=> 'inherit',
			'posts_per_page'	=> -1,
			'tax_query'	=> $tax_query
		);
		$query = new \WP_Query( $args );
		echo json_encode( $query->get_posts() );
		wp_die();
	}

	public function get_categories() {
		$args = array(
			'hide_empty'	=> false,
			'childless'	=> true
		);
		$categories = get_terms( 'attachment_category', $args );
		$tags = get_terms( 'attachment_tag', $args );

		echo json_encode( 
			array( 
				'categories' => $categories,
				'tags' => $tags
			)
		);
		wp_die();
	}

	public function multi_select_update() {
		$attachment_ids = $_POST['attachments'];
		$tags = array();

		// Make sure that tags are passed as integers
		// See Notes on https://codex.wordpress.org/Function_Reference/wp_set_post_terms
		foreach( $_POST['taxonomies']['attachment_tag'] as $tag ) {
			$tags[] = intval( $tag );
		}

		$things = array(
			'attachment_category'	=> $_POST['taxonomies']['attachment_category'],
			'attachment_tag'	=> $tags
		);
		$return = array();
		
		foreach( $things as $taxonomy => $terms ) {
			foreach( $attachment_ids as $id ) {
				$result = wp_set_post_terms( $id, (array) $terms, $taxonomy, false );

				/*
				(array) An array of the terms affected if successful,
				(boolean) false if integer value of $post_id evaluates as false (if ( ! (int) $post_id )),
				(WP_Error) The WordPress Error object on invalid taxonomy ('invalid_taxonomy').
				(string) The first offending term if a term given in the $terms parameter is named incorrectly. (Invalid term ids are accepted and inserted).
				*/
				if( is_array( $result ) )
					$return[] = array( 'status' => 'success', 'message' => 'Attachment updated successfully' );
				if( is_wp_error( $result ) )
					$return[] = array( 'status' => 'error', 'message' => $result->get_error_message() );
				if( false === $result )
					$return[] = array( 'status' => 'error', 'message' => 'Attachment not found' );
			}
		}

		echo json_encode( $return );
		wp_die();
	}
}


$tss_media_view = new Media_View;
