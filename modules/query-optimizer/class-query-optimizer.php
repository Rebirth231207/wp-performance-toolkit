<?php
namespace WPPT\Modules;

use WPPT\Includes\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced Query Optimizer Module
 * 
 * Optimizes database performance by disabling expensive SQL_CALC_FOUND_ROWS 
 * and refining search and revision queries.
 */
class Query_Optimizer extends Abstract_Module {

	protected $id = 'query-optimizer';
	protected $name = 'Advanced Query Optimizer';
	protected $description = 'Optimizes SQL queries by disabling redundant pagination counts and refining search parameters.';

	/**
	 * Module entry point.
	 */
	public function run(): void {
		// Only run optimizations on the frontend.
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// Main WP_Query modifications.
		add_action( 'pre_get_posts', [ $this, 'optimize_main_queries' ], 1 );
		
		// Revision fetching optimization.
		add_filter( 'wp_revisions_to_keep', [ $this, 'limit_revisions_on_frontend' ], 10, 2 );

		// General performance tweaks for any WP_Query.
		add_filter( 'posts_pre_query', [ $this, 'stop_unnecessary_counts' ], 10, 2 );
	}

	/**
	 * Modifies the WP_Query object before the SQL is generated.
	 * 
	 * @param \WP_Query $query The query object.
	 */
	public function optimize_main_queries( $query ): void {
		if ( ! $query->is_main_query() ) {
			return;
		}

		// 1. Disable SQL_CALC_FOUND_ROWS on singular pages.
		// Since there is only one post, calculating the total count of posts is a waste of resources.
		if ( $query->is_singular() ) {
			$query->set( 'no_found_rows', true );
		}

		// 2. Optimize Search Queries.
		if ( $query->is_search() ) {
			$post_types = [ 'post', 'page' ];

			// Include WooCommerce products if active.
			if ( class_exists( 'WooCommerce' ) ) {
				$post_types[] = 'product';
			}

			$query->set( 'post_type', $post_types );
			
			// Disable meta/term updates for searches to speed up initial response.
			$query->set( 'update_post_meta_cache', false );
			$query->set( 'update_post_term_cache', false );
		}
	}

	/**
	 * Stops the database from performing a total count query when it's not needed.
	 * 
	 * @param array|null $posts Pre-queried posts.
	 * @param \WP_Query  $query The query object.
	 * @return array|null Original value.
	 */
	public function stop_unnecessary_counts( $posts, $query ) {
		// If we are on a page where pagination doesn't exist, kill the count.
		if ( $query->is_singular() ) {
			$query->set( 'no_found_rows', true );
		}

		return $posts;
	}

	/**
	 * Ensures that the frontend never attempts to fetch or process deep 
	 * revision histories.
	 * 
	 * @param int      $num  Number of revisions to keep.
	 * @param \WP_Post $post The post object.
	 * @return int Modified number of revisions.
	 */
	public function limit_revisions_on_frontend( int $num, $post ): int {
		// On the frontend, we effectively tell WP there are no revisions to 
		// process unless we are in a preview state.
		if ( ! is_preview() ) {
			return 0;
		}

		return $num;
	}
}