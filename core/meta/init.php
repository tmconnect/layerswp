<?php /**
 * Post & Page Meta Initiation File
 *
 * This file is the source of the Custom Meta in the Layers theme
 *
 * @package Layers
 * @since Layers 1.0.0
 */

class Layers_Custom_Meta {

	private static $instance;

	var $custom_meta;

	/**
	*  Initiator
	*/

	public static function init(){
		return self::$instance;
	}

	/**
	*  Constructor
	*/

	public function __construct() {

		// Setup some folder variables
		$meta_dir = '/core/meta/';

		// Include Config file(s)
		require_once get_template_directory() . $meta_dir . 'config.php';


		// Instantiate meta config class
		$meta_config = new Layers_Meta_Config();

		// Get post meta
		$this->custom_meta = $meta_config->meta_data();

		// Enqueue Styles
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) , 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_print_styles' ) , 50 );

		// Page Builder Button
		add_action( 'edit_form_after_title', array( $this , 'page_builder_button' ) );
		add_action( 'wp_ajax_update_page_builder_meta' , array( $this , 'update_page_builder_meta' ) );
		// add_filter( 'layers_pointer_settings' , array( $this , 'page_builder_button_pointer' ) );
		add_action( 'page_row_actions' , array( $this , 'inline_page_builder_button' ), 10, 2 );

		// Custom Fields
		add_action( 'admin_menu', array( $this , 'register_post_meta' ) );
		add_action( 'save_post', array( $this , 'save_post_meta' ) );
		add_action( 'publish_post', array( $this , 'save_post_meta' ) );
	}

	/**
	*  Enqueue Widget Scripts
	*/

	public function admin_enqueue_scripts(){

		// Customizer general
		wp_enqueue_script(
			LAYERS_THEME_SLUG . '-admin-meta' ,
			get_template_directory_uri() . '/core/meta/js/meta.js' ,
			array(
				'backbone',
				'jquery',
				'wp-color-picker'
			),
			LAYERS_VERSION,
			true
		);

		// Localize Scripts
		wp_localize_script( LAYERS_THEME_SLUG . '-admin-meta' , "layers_meta_params", array( 'ajaxurl' => admin_url( "admin-ajax.php" ) , 'nonce' => wp_create_nonce( 'layers-customizer-actions' ) ) );
	}

	/**
	*  Enqueue Widget Styles
	*/

	public function admin_print_styles(){
		global $pagenow, $post;
		if ( 'post.php' === $pagenow && ( LAYERS_BUILDER_TEMPLATE == basename( get_page_template() ) ) ) : ?>
			<style> #postdivrich, #postbox-container-2, #postimagediv { display: none; }</style>
		<?php endif;
	}

	/**
	* Page Builder Button
	*/

	public function page_builder_button(){
		global $post;

		// This button is only used for pages
		if ( !in_array( $post->post_type, array( 'page' ) ) ) return;

		// Check if we're using the builder for this page

		$is_builder_used = ( 'builder.php' == basename( get_page_template() ) ) ? true : false; ?>

		<div id="layers_toggle_builder" class="<?php echo (true == $is_builder_used ? '' : 'layers-hide'); ?>">
					<div  class="postbox layers-push-top">
						<div class="layers-section-title layers-no-push-bottom layers-content-large">
							<div class="layers-heading">
								<?php _e( 'Your page is ready.', 'layerswp' ); ?>
							</div>
							<p class="layers-excerpt">
								<?php _e( 'You can drag and drop widgets, edit content and tweak the design. Click the button below to see your page come to life.', 'layerswp' ) ?>
							</p>
						</div>
						<div class="layers-button-well clearfix">
							<a href="<?php echo admin_url() . 'customize.php?url=' . esc_url( get_the_permalink() ) ?>" class="layers-button btn-massive btn-primary btn-full" id="<?php echo ( isset( $post->ID ) ? 'builder-button-' . $post->ID : 'builder-button-' . rand(0,1) ) ?>"><?php _e( 'Edit Your Page', 'layerswp' ) ?></a>
						</div>
					</div>

					<div class="layers-row">

						<div class="layers-column layers-span-4 postbox layers-content">
							<div class="layers-section-title layers-tiny">
								<h4 class="layers-heading"><?php _e( 'Export Layout', 'layerswp' ) ?></h4>
								<p class="layers-excerpt"><?php _e( 'Export your layout to a <code>.json</code> file which you can use to upload to another site.', 'layerswp' ) ?></p>
							</div>
							<a href="?post=<?php echo get_the_ID() ?>&amp;action=edit&amp;layers-export=1" class="layers-button"><?php _e( 'Export' , 'layerswp' ) ?></a>
						</div>

						<div class="layers-column layers-span-4 postbox layers-content">
							<div class="layers-section-title layers-tiny">
								<h4 class="layers-heading"><?php _e( 'Import Layout' , 'layerswp' ) ?></h4>
								<p class="layers-excerpt"><?php _e( 'Upload a layout file (eg. ', 'layerswp' ) ?><code><?php echo $post->post_name ?><?php _e( '.json', 'layerswp' ) ?></code><?php _e( ') by clicking the button below.' , 'layerswp' ) ?></p>
							</div>
							<button class="layers-button" id="layers-page-import-button" data-post-id="<?php echo get_the_ID(); ?>" data-title="Upload .json" data-button_text="Upload &amp; Import"><?php _e( 'Upload &amp; Import', 'layerswp' ) ?></button>
						</div>

						<div class="layers-column layers-span-4 postbox layers-content">
							<div class="layers-section-title layers-tiny">
								<h4 class="layers-heading"><?php _e( 'Duplicate Layout' , 'layerswp' ) ?></h4>
								<p class="layers-excerpt"><?php _e( 'Easily duplicate your layout, settings, text and images in order to get started quickly with a new page.' , 'layerswp' ) ?></p>
							</div>
							<button href="" class="layers-button" id="layers-page-duplicate-button" data-post-id="<?php echo get_the_ID(); ?>"><?php _e( 'Duplicate' , 'layerswp' ) ?></button>
						</div>

					</div>
				</div>
<?php
	}

	/**
	* Page Builder Inline Button
	*/

	function inline_page_builder_button($actions,$post) {

		// Set the post object
		$post_type_object = get_post_type_object( $post->post_type );

		// Set user capability
		$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $post->ID );

		// Add our button
		if ( $can_edit_post && 'builder.php' == get_page_template_slug( $post->ID ) ) {
			$actions['builder'] = '<a href="' . admin_url( 'customize.php?url=' . esc_url( get_the_permalink() ) ) . '" title="' . esc_attr( __( 'Edit Layout' , 'layerswp' ) ) . '">' . __( 'Edit Layout' , 'layerswp' ) . '</a>';
		}

		return $actions;
	}

	/**
	* Page Builder Button Pointer
	*/
	public function page_builder_button_pointer( $pointers ){
		global $post;

		// If we are not in the post edit screen, just return
		if( !isset( $post ) ) return;

		// This button is only used for pages
		if ( !in_array( $post->post_type, array( 'page' ) ) || 'publish' != get_post_status() ) return;

		// Add the pointer to the pointer config
		$pointers[ LAYERS_THEME_SLUG . '-builder-button-pointer-' . $post->ID ] = array(
					'selector' 	=> '#builder-button-' . $post->ID,
					'position'	=>  array(
									'edge' => 'right', // bottom / top/ right / left
									'align' => 'left' // left / center / right
								),
					'title'		=> __( 'Build Your Page' , 'layerswp' ),
					'content'	=> __( 'Use the' . LAYERS_THEME_TITLE . ' page builder to build a beautiful, dynamic page.' , 'layerswp' ),
				);

		return $pointers;
	}

	/**
	* Page Builder Meta Update
	*/

	public function update_page_builder_meta(){

		// Get the Post ID
		$post_id = $_POST['id'];

		if( isset($_POST[ 'template' ] ) && 'builder.php' == $_POST[ 'template' ] ){
			update_post_meta( $post_id , '_wp_page_template', $_POST[ 'template' ] );
		} else {
			delete_post_meta( $post_id , '_wp_page_template' );
		}
		die();
	}

	/**
	* Custom Meta Register
	*/

	public function register_post_meta(){
		// If we have not published the post, don't set a post ID
		if( isset( $_REQUEST[ 'post' ] ) ) {
			$post_id = $_REQUEST[ 'post' ];
		} else {
			$post_id = NULL;
		}

		// Loop over the custom meta
		foreach( $this->custom_meta as $meta_index => $custom_meta ){

			// If there is Post Meta,  register the metabox
			if( isset( $this->custom_meta[ $meta_index ] ) ){

				if( post_type_exists( $meta_index ) ) {
					/**
					* Add post meta for posts & other post types
					*/

					// Set the post type
					$post_type = $meta_index;

					$callback_args = array(
						'meta_index' =>$meta_index
					);
				} else {
					/**
					* Add post meta for page templates
					*/

					// Set the post type to 'page'
					$post_type = 'page';

					// Get the page template
					$page_template = get_post_meta( $post_id, '_wp_page_template' , true );

					// If there is no page template set, just return
					if( '' == $page_template ) return;

					// Now check to see that we've selected the right page template
					if( $meta_index != $page_template) return;

					$callback_args = array(
						'meta_index' => $meta_index
					);
				}

				// Add Meta Box
				add_meta_box(
					LAYERS_THEME_SLUG . '-' . $meta_index, // Slug
					$custom_meta[ 'title' ], // Title
					array( $this , 'display_post_meta' ) , // Interface
					$post_type , // Post Type
					$custom_meta[ 'position' ], // Position
					'high', // Priority
					$callback_args // Callback args
				);
			}
		}
	}

	/**
	* Custom Meta Interface
	*/

	public function display_post_meta( $post , $callback_args ){

		// Get post type
		$post_type = get_post_type( $post->ID );

		// Post Meta Value
		$post_meta = get_post_meta( $post->ID, LAYERS_THEME_SLUG, true );

		// Set the meta index ie. the array we will loop over for our options
		$meta_index = $callback_args[ 'args' ][ 'meta_index' ];

		// If there is no post meta to show, return
		if( !isset( $this->custom_meta[ $meta_index ] ) ) return;

		// Instantiate form elements
		$form_elements = new Layers_Form_Elements();

		// If there is Post Meta, loop over the tabs.
		if( isset( $this->custom_meta[ $meta_index ] ) ){ ?>
			<!-- Tabs -->
			<div class="layers-nav layers-nav-tabs">
				<ul class="layers-tabs clearfix">
					<?php foreach( $this->custom_meta[ $meta_index ]['custom-meta'] as $key => $meta_option ){ ?>
						<li <?php if( !isset( $inactive ) ) echo 'class="active"'; ?>><a href="#"><?php echo $meta_option[ 'title' ]; ?></a></li>
						<?php $inactive=1; ?>
					<?php } // foreach $this->custom_meta[ $post_type ]['custom-meta']  ?>
				</ul>
			</div>
			<!-- Tab Content -->
			<div class="layers-tab-content">
				<?php foreach( $this->custom_meta[ $meta_index ]['custom-meta'] as $key => $meta_option ){ ?>
					<section class="layers-accordion-section layers-content layers-tab-content <?php if( isset( $hide_tab ) ) echo 'layers-hide'; ?> customize-control"> <?php // @TODO: Remove .customizer-control class ?>
						<div class="layers-row clearfix">
							<?php if( isset( $meta_option[ 'elements' ] ) ) { ?>
								<fieldset>
									<?php foreach( $meta_option[ 'elements' ] as $input_key => $input ) { ?>
										<p class="layers-form-item">
											<label><?php echo $input[ 'label' ]; ?></label>
											<?php  echo $form_elements->input(
												array(
													'type' => $input[ 'type' ],
													'name' => LAYERS_THEME_SLUG . '[' . $input_key . ']',
													'id' => $input_key ,
													'default' => ( isset( $input[ 'default' ] ) ) ? $input[ 'default' ] : NULL ,
													'placeholder' => ( isset( $input[ 'placeholder' ] ) ) ? $input[ 'placeholder' ] : NULL ,
													'value' => ( isset( $post_meta[ $input_key ] ) ) ? $post_meta[ $input_key ] : ( ( isset( $input[ 'default' ] ) ) ? $input[ 'default' ] : NULL ), // Check for a value, then check for a default, then finally settle on NULL
													'options' =>  ( isset( $input[ 'options' ] ) ) ? $input[ 'options' ] : NULL,
													'class' => 'layers-' . $input[ 'type' ]

												)
											); ?>
										</p>
									<?php } // foreach $meta_option[ 'elements' ] ?>
								</fieldset>
							<?php } // if $meta_option[ 'elements' ] ?>
						</div>
					</section>
					<?php $hide_tab = 1; ?>
				<?php } // foreach $this->custom_meta[ $post_type ]['custom-meta'] ?>
			</div>
			<?php wp_nonce_field( LAYERS_THEME_SLUG . '-post-meta' , '_wp_nonce_' . LAYERS_THEME_SLUG ); ?>
		<?php } // if $this->custom_meta[ $post_type ] ?>
	<?php }

	/**
	* Custom Meta Interface
	*/

	public function save_post_meta( $post_id ){
		global $post;

		// Get post type
		$post_type = get_post_type( $post_id );

		// Verify our nonce
		$nonce_key = '_wp_nonce_' . LAYERS_THEME_SLUG;

		// If there is no nonce to use, can this function
		if( !isset( $_REQUEST[ $nonce_key ] ) ) return;

		$nonce = $_REQUEST[ $nonce_key ];

		// Form key
		$form_key = LAYERS_THEME_SLUG;

		// Do some nonce
		if ( wp_verify_nonce( $nonce, LAYERS_THEME_SLUG . '-post-meta' ) ) {
			if( isset( $_REQUEST[ $form_key ] ) ) {
				update_post_meta( $post_id, LAYERS_THEME_SLUG, $_REQUEST[ $form_key ] );
			} // if isset( $this->custom_meta[ $post_type ] )
		} // if nonce
	}
}

/**
 * Kicking this off with the 'custom_meta_init' hook
 */
function layers_custom_meta_init(){
	$layers_widget = new Layers_Custom_Meta();
	$layers_widget->init();
}
add_action( 'init' , 'layers_custom_meta_init' , 10 );