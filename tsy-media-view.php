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
					<li><a href="#" class="filter-link" data-category="{{ c.term_id }}" data-tag="{{ t.term_id }}">{{ t.name }}</a></li>
				<# }); #>-->
			<# }); #>
		</script>
		<script type="text/html" id="tmpl-attachment-list">
			<# _.each( data, function( a, index ) { #>
				<li><a href="#" class="detail-link" data-id="{{ index }}">{{ a.post_title }}</a></li>
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
		<script>
		jQuery( document ).ready( function($) {
			var attachments;

			$.post( ajaxurl, {'action': 'get_categories'}, function(res) {
				var categories = $.parseJSON( res );
				var template = wp.template( 'category-list' );
				$('.category-list > .list').html( template( categories ) );
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

				var id = $(this).attr('data-id');
				var template = wp.template( 'attachment-detail' );
				$('.attachment-detail > .detail').html( template( attachments[id] ) );

				console.log( attachments[id] );

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
}


$tss_media_view = new Media_View;
