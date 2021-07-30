<?php
/**
 * tenf functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package tenf
 */

/**
 * For cache-busting. Always starts at 1.0.0 for a new project.
 */
define( 'TENF_VERSION', '1.0.0' );

function tenf_version() {
  if ( WP_DEBUG )
    return time();

  return TENF_VERSION;
}

if ( ! function_exists( 'tenf_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function tenf_setup() {

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'menu-1' => esc_html__( 'Primary', 'tenf' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'tenf_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		) );

    add_image_size( 'extra-large', 1200, 1200 );
    add_image_size( 'hero', 1920, 1920 );
	}
endif;
add_action( 'after_setup_theme', 'tenf_setup' );

/**
 * Pretty-print function for objects.
 */
function tenf_r( $data ) {
	echo '<pre>';
	var_dump( $data );
	echo '</pre>';
}

/**
 * Enqueue scripts and styles.
 */
function tenf_scripts() {
	wp_enqueue_style( 'bootstrap-grid', get_stylesheet_directory_uri() . '/bootstrap-grid.css', array(), tenf_version() );
	wp_enqueue_style( 'tenf-style', get_stylesheet_uri(), array(), tenf_version() );
	wp_enqueue_style( 'tenf-style-mobile', get_stylesheet_directory_uri() . '/style-mobile.css', array(), tenf_version() );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'tenf_scripts' );

function tenf_get_image_url( $image_id, $size=false ) {
    $image_src = wp_get_attachment_image_src( $image_id, $size );
    return $image_src ? $image_src[0] : '';
}

// Pass in sizes like this: 
//   tenf_image($id, 'large', 'md-4');
//   tenf_image($id, 'large', 'md-6 lg-4');
//   tenf_image($id, 'large', 'md-6 lg-4 sm-3');
//   tenf_image($id, 'large', true); // < this one is full width (100vw)
// or whatever
function tenf_image($image_id, $size='medium', $bootstrap_classes=false) {
	if (empty($image_id)) {
		return "";
	}
	$srcset = wp_get_attachment_image_srcset($image_id, $size);
	$alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);
	$image_src = wp_get_attachment_image_src($image_id, $size)[0];
	$sizes = false;

	// this array is _ordered_
	$size_array = array(
		// 'class' => array(screen_size, container_size),
		'sm' => array(576, 540),
		'md' => array(768, 720),
		'lg' => array(992, 960),
		'xl' => array(1200, 1140),
		'xxl' => array(1400, 1320),
	);

	// this is appended to and then used to contruct the sizes attribute
	$output_array = array();

	if (is_string($bootstrap_classes)) {
		$sizes = '';
		$class_array = explode(' ', $bootstrap_classes);
		foreach($class_array as $class) {
			if (empty($class)) {
				continue;
			}
			$matches = array();
			if (!preg_match('/(\w*)-(\d*)/', $class, $matches)) {
				tenf_r("Error in tenf_image - size doesn\'t conform to format: \"$class\"");
				$sizes = false;
				break;
			};
			$class = $matches[1];
			$width = intval($matches[2]);
			$output_array[$class] = $width;
		}
		$width = false;
		foreach ($size_array as $size_key => $size_value) {
			if (array_key_exists($size_key, $output_array)) {
				$width = $output_array[$size_key];
			}
			if ($width) {
				$val = $size_value[1] * $width / 12;
				$sizes = "(min-width: {$size_value[0]}px) {$val}px, $sizes";
			}
		}
		$sizes .= 'calc(100vw - 1.5rem)'; // adjusted for padding in extra container small sizes
	} elseif ($bootstrap_classes == true) {
		$sizes = '100vw';
	}
	// construct img tag
	$output = '<img ';
	if ($sizes) {
		$output .= 'srcset="' . $srcset . '" ';
		$output .= 'sizes="' . $sizes . '" ';
	}
	$output .= 'src="' . $image_src . '" ';
	$output .= 'alt="' . $alt . '">';
	return $output;
}

// used to determine if the active user has a given role or capability
function user_has_role($role_or_cap) {
  $u = wp_get_current_user();
  $roles_and_caps = $u->get_role_caps();

  if( isset ( $roles_and_caps[$role_or_cap] ) and $roles_and_caps[$role_or_cap] === true ) {
  	return true;
	}
}