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


		add_action( 'wp_ajax_get_attachments_filtered', array( &$this, 'get_attachments_filtered' ) );
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

		add_filter( 'posts_clauses', array( &$this, 'add_taxonomy_terms_to_attachments' ) );

		echo '<h1>Media</h1>';

		echo '<div class="sidebar">';

		$args = array(
			'hide_empty'	=> false,
			'childless'	=> true
		);
		$categories = get_terms( 'attachment_category', $args );
		$tags = get_terms( 'attachment_tag', $args );


		echo '<ul>';
		foreach( $categories as $category ) {
			printf( '<li><a href="#" class="filter-link" data-category="%s">%s</a>', $category->term_id, $category->name );

			printf( '<ul>' );
			foreach( $tags as $tag ) {
				printf( '<li><a href="#" class="filter-link" data-category="%s" data-tag="%s">%s</a></li>', $category->term_id, $tag->term_id, $tag->name );
			}
			printf( '</ul>' );
			printf( '</li>' );
		}
		printf( '</ul>' );

		printf( '</div><div class="attachment_list"><h2>Attachments</h2>' );

		printf( '<div class="attachment_container"></div>' );

		printf( '</div>' );

		?>
		<script type="text/html" id="tmpl-attachment-list">
			<p>Att</p>
			<ul>
			</ul>
		</script>
		<script type="text/html" id="tmpl-attachment-detail">
		</script>
		<script>
		jQuery( document ).ready( function($) {
			var attachments;
			var my_template = wp.template( 'attachment_list' );
			console.log( wp );

			$('.sidebar').on( 'click', 'a.filter-link', function(e) {
				var category = $(this).attr('data-category');
				var tag = $(this).attr('data-tag');

				$('a.filter-link').removeClass('selected');
				$(this).addClass('selected');

				$('.attachment_container').html('');

				var data = {
					'action': 'get_attachments_filtered',
					'category': category,
					'tag': tag
				};

				$.post( ajaxurl, data, function(res) {
					attachments = $.parseJSON(res);


					var attachment_list = my_template( attachments );

					/*if( attachments.length > 0 ) {
						var attachment_list = '';
						$(attachments).each( function( i ) {
							console.log( attachments[i] );
							attachment_list = attachment_list + '<li>' + attachments[i].ID + '</li>';
						});
					} else {
						var attachment_list = '<p>No attachments found in category: ' + category;
						if( undefined != tag )
							attachment_list = attachment_list + ' and tag: ' + tag;
						attachment_list = attachment_list + '</p>';
					}*/
					$('.attachment_container').html(attachment_list);
				});
				
				e.preventDefault();
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
}


$tss_media_view = new Media_View;
