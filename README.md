# Mai Link Injector
A programmatic plugin to automatically link keywords to any url.

## How it works
If a post has any keywords you set (case-insensitive) it will automatically be linked. If "broccoli" was your key word, these sentences:

```
Broccoli is my favorite vegetable. My favorite way to eat broccoli is with salt and butter.
```

Mai Link Injector will dynamically add links to the text, keeping the case as-is:

```
<a href="https://example.com">Broccoli</a> is my favorite vegetable. My favorite way to eat <a href="https://example.com">broccoli</a> is with salt and butter.
```

## Usage

Use the `Mai_Link_Injector` class to setup links and any conditions you want via:
```
// Sets up links.
$links = [
	'some keywords' => 'https://bizbudding.com',
	'mai theme'     => 'https://bizbudding.com/mai-theme',
	'maple syrup'   => 'https://sugarmakers.org/',
];

// Instantiates class.
$class = new Mai_Link_Injector( $links );

// Optionally limits tthe amount of instances specific keywords gets linked per-page.
// Use zero or don't add this to link all instances.
$class->set_limit( 2 );

// Run. This must be before the main content.
$class->run();
```

## Basic example
```
/**
 * Sets up Mai Link Injector.
 *
 * @return void
 */
add_action( 'wp_head', function() {
	// Bail if Mai Link Injector is not active.
	if ( ! class_exists( 'Mai_Link_Injector' ) ) {
		return;
	}

	// Bail if not on a single post/page/cpt.
	if ( ! is_singular() ) {
		return;
	}

	$links = [
		'some keywords' => 'https://bizbudding.com',
		'mai theme'     => 'https://bizbudding.com/mai-theme',
		'maple syrup'   => 'https://sugarmakers.org/',
	];

	$class = new Mai_Link_Injector( $links );
	$class->set_limit( 4 );
	$class->run();
});
```

## Dynamic example
This example automatically gets all of the post categories and uses the category name as the keyword(s) and the category archive url as the url. Any post with a same text as a category name will automatically have a link to that category archive.

```
/**
 * Get is started.
 *
 * @return void
 */
add_action( 'genesis_before_loop', function() {
	// Bail if Mai Link Injector is not active.
	if ( ! class_exists( 'Mai_Link_Injector' ) ) {
		return;
	}

	// Bail if not on a single post/page/cpt.
	if ( ! is_singular() ) {
		return;
	}

	// Set taxonomy name.
	$taxonomy = 'category';

	// Get all terms.
	$terms    = get_terms(
		[
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		]
	);

	// Bail if no terms.
	if ( ! $terms || is_wp_error( $terms ) ) {
		return;
	}

	// Create array of keywords => links.
	$links = [];

	foreach ( $terms as $term ) {
		$links[ $term->name ] = get_term_link( $term, $taxonomy );
	}

	// Bail if no links.
	if ( ! $links ) {
		return;
	}

	// Run.
	$class = new Mai_Link_Injector( $links );
	$class->run();
});
```
