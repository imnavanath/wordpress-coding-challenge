<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types( [ 'public' => true ] );
		$class_name = $attributes['className'];

		ob_start();

		?>
			<div class="<?php echo esc_attr( $class_name ); ?>">
				<h2> <?php _e( 'Post Counts', 'site-counts' ); ?> </h2>

				<ul>
					<?php
						foreach ( $post_types as $post_type_slug ) :
							$post_type_object = get_post_type_object( $post_type_slug );
							$post_count = wp_count_posts( $post_type_slug );
							?>
								<li>
									<?php
										/* translators: 1: Translatable text, 2: Post count, 3: Post type name */
										printf(
											'%1$s %2$s %3$s',
											__( 'There are', 'site-counts' ),
											$post_count->publish,
											esc_html( $post_type_object->labels->name )
										);
									?>
								</li>
							<?php
						endforeach;
					?>
				</ul>

				<?php
					$post_id = ( isset( $_GET['post_id'] ) && absint( $_GET['post_id'] ) ) ? $_GET['post_id'] : get_the_ID();
					printf(
						'<p> %s </p>',
						esc_html( sprintf(
							/* translators: %d: A post ID. */
							__( 'The current post ID is %d.', 'site-counts' ),
							$post_id
						) )
					);

					$this->get_foo_baz_tax_posts( $post_id );
				?>
			</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Retrieve posts associated with taxonomy Foo and Baz.
	 *
	 * @param int $post_id - The post ID.
	 * @return void
	 */
	private function get_foo_baz_tax_posts( $post_id ) {
		$query = new WP_Query(
			[
				'post_type'				 => [ 'post', 'page' ],
				'post_status'			 => 'any',
				'date_query' => [
					[
						'hour'      => 9,
						'compare'   => '>=',
					],
					[
						'hour'		=> 17,
						'compare'	=> '<=',
					],
				],
				'tag'					 => 'foo',
				'category_name'			 => 'baz',
				'no_found_rows'			 => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		if ( $query->have_posts() ) :
			$post_count = 0;
			foreach ( $query->posts as $post ) {
				if ( $post->ID !== $post_id ) {
					$post_count++;
				}
			}
			?>
				<h2>
					<?php
						/* translators: 1: Post count, 2: Translatable text */
						printf( '%d %s',
							$post_count,
							__( 'posts with the tag of foo and the category of baz', 'site-counts' ),
						);
					?>
				</h2>

				<ul>
					<?php
						$count = 0;
						while ( $query->have_posts() ) :
							$query->the_post();
							$count++;
							if ( $post_id === the_ID() ) continue;
							?>
								<li> <?php the_title(); ?> </li>
							<?php
								if ( $count > 5 ) break;
						endwhile;
					?>
				</ul>
			<?php

			wp_reset_postdata();
		endif;
	}
}
