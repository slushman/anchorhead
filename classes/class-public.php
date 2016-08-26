<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://slushman.com
 * @since      1.0.0
 *
 * @package    Anchorhead
 * @subpackage Anchorhead/classes
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Anchorhead
 * @subpackage Anchorhead/classes
 * @author     Slushman <chris@slushman.com>
 */
class Anchorhead_Public {

	/**
	 * An array of headings on pages.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $headings    An array of headings on pages.
	 */
	private $headings;

	/**
	 * The plugin options.
	 *
	 * @since 		1.0.0
	 * @access 		private
	 * @var 		string 			$options 		The plugin options.
	 */
	private $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->headings = array();
		$this->set_options();

	} // __construct()

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( ANCHORHEAD_SLUG . '-public', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/anchorhead-public.css', array(), ANCHORHEAD_VERSION, 'all' );

	} // enqueue_styles()

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( ANCHORHEAD_SLUG . 'smooth-scroll', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/anchorhead-public.min.js', array(), ANCHORHEAD_VERSION, false );

	} // enqueue_scripts()

	/**
	 * Adds anchor tags after each heading in the content.
	 *
	 * @param 	mixed 		$content 		The current content
	 *
	 * @return  mixed 						Content with the anchor links
	 */
	public function add_anchors( $content ) {

		if ( '' === trim( $content ) ) { return $content; }
		if ( ! in_the_loop() ) { return $content; } // exit if content is empty
		if ( empty( $this->headings ) ) { return $content; } // exit if headings is empty

		$thispageID = get_the_ID();

		if ( in_array( $thispageID, $this->headings ) ) { return $content; } // exit if the page we're on isn't in the headings array

		$offset = 0;

		foreach ( $this->headings[$thispageID]['h2'] as $heading ) {

			/**
			 * @todo: Add to the threshhold as the menu gets longer...
			 */

			if ( $this->options['top-link-threshhold'] > $heading['textbegin'] ) {

				$new_content 	= ' class="inline-heading"';
				$start 			= $heading['textbegin'] -1 + $offset;

			} else {

				$new_content 	= '<span id="' . $heading['anchortext'] . '"></span><a class="ah-top" data-scroll href="#" role="link">Back to top</a></h2>';
				$start 			= $heading['closetag'] + $offset;

			}

			$content 	= substr_replace( $content, $new_content, $start, 0 );
			$offset 	= strlen( $new_content ) + $offset;

			unset( $new_content );

		} // foreach

		return $content;

	} // add_anchors()

	/**
	 * Adds linsk to the anchor tags in the content.
	 *
	 * @param 	mixed 		$content 		The current content
	 *
	 * @return  mixed 						Content with the anchor links
	 */
	public function add_menu( $content ) {

		if ( '' === trim( $content ) ) { return $content; }
		if ( ! in_the_loop() ) { return $content; } // exit if content is empty
		if ( empty( $this->headings ) ) { return $content; } // exit if headings is empty

		$thispageID = get_the_ID();

		if ( in_array( $thispageID, $this->headings ) ) { return $content; } // exit if the page we're on isn't in the headings array

		$float = get_theme_mod( 'float_picker' );
		$title = get_theme_mod( 'toc_title' );

		$return = '';
		$return .= '<ul class="ah-menu" data-float="' . esc_attr( $float ) . '">';
		$return .= '<h3 class="toc-title">';

		if ( ! empty( $title ) ) {

			$return .= esc_html( $title );

		}

		$return .= '</h3>';

		foreach ( $this->headings[$thispageID]['h2'] as $heading ) {

			$return .= '<li>';
			$return .= '<a data-scroll href="#' . $heading['anchortext'] . '">';
			$return .= $heading['text'];
			$return .= '</a>';
			$return .= '</li>';

		} // foreach

		$return .= '</ul>';

		return $return . $content;

	} // add_menu()

	/**
	 * Populates the headings class variable with headings from the content.
	 *
	 * @param 	mixed 		$content 		The current content
	 *
	 * @return 	mixed 		$content 		The current content
	 */
	public function find_headings( $content ) {

		if ( '' === trim( $content ) ) { return $content; }
		if ( ! in_the_loop() ) { return $content; }

		global $post;

		$meta = get_post_custom( $post->ID );

		if ( array_key_exists( 'show-anchors', $meta ) && empty( $meta['show-anchors'][0] ) ) { return $content; }

		$dom 	= new DOMDocument();
		$h2arr 	= array();
		$pageID = get_the_ID();

		$dom->loadHTML( $content );

		$h2arr = $dom->getElementsByTagName( 'h2' );

		if ( 0 >= $h2arr->length ) { return $content; }

		foreach ( $h2arr as $item ) {

			$text 				= esc_html( trim( $item->nodeValue ) ); // the text used in the heading
			$textlength 		= strlen( $text ); // the length of the text
			$textbegin 			= strpos( $content, $text . '</h2>' ); // heading beginning position within the content string

			if ( ! $textbegin && 0 >= $textlength ) { continue; }

			$h2_args['anchortext']  = sanitize_title( $text );
			$h2_args['text'] 		= $text;
			$h2_args['textbegin'] 	= $textbegin;
			$h2_args['closetag'] 	= $textbegin + $textlength;
			$h2_args['textend'] 	= $textbegin + $textlength + 5; // string position of the end of the closing heading tag
			$h2_args['textlength'] 	= $textlength;

			$this->headings[$pageID]['h2'][] = $h2_args;

		} // foreach

		unset( $dom );

		return $content;

	} // find_headings()

	public function get_headings( $page = '' ) {

		if ( empty( $page ) ) {

			return $this->headings;

		} else {

			return $this->headings[$page];

		}

	} // get_headings()

	/**
	 * Sets the class variable $options
	 */
	private function set_options() {

		$this->options = get_option( ANCHORHEAD_SLUG . '-options' );

	} // set_options()

	/**
	 * Initiates smooth-scroll.js
	 * @return 		mixed 		Script ouptut
	 */
	public function start_smooth_scroll() {

		if ( ! isset( $this->options['scroll-type'] ) || ! isset( $this->options['scroll-speed'] ) ) { return; }

		?><script>smoothScroll.init({
			easing: '<?php echo esc_html( $this->options['scroll-type'] ); ?>',
			speed: '<?php echo esc_html( $this->options['scroll-speed'] ); ?>'
		});</script><?php

	} // start_smooth_scroll()

} // class
