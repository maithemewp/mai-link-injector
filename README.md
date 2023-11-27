# Mai Link Injector
A plugin to automatically link keywords to any url. Settings page requires Mai Theme or ACF Pro.

## How it works
If a post has any keywords you set (case-insensitive) it will automatically be linked. If "broccoli" was your keyword, use this sentence as an example:

"Broccoli is my favorite vegetable. My favorite way to eat broccoli is with salt and butter."

Mai Link Injector will dynamically add links to the text, keeping the case as-is:

"<a href="https://example.com">Broccoli</a> is my favorite vegetable. My favorite way to eat <a href="https://example.com">broccoli</a> is with salt and butter."

## Usage
1. Visit Dashboard > Mai Theme > Link Injector (if using Mai Theme) or Dashboard > Settings > Mai Link Injector.
2. Add links and optionally modify settings.
3. That's it!

## Programmatic Usage

Programmatically add links via the `mai_link_injector_links` filter.
```
/**
 * Adds links to Mai Link Injector.
 *
 * @param array $options
 *
 * @return array
 */
add_filter( 'mai_link_injector', function( $options ) {
	$links = [
		'some keywords' => 'https://bizbudding.com',
		'mai theme'     => 'https://bizbudding.com/mai-theme',
		'maple syrup'   => 'https://sugarmakers.org/',
	];

	// Optionally use conditional tags to conditionally load links.

	// Add new links.
	$options['links'] = array_merge( $options['links'], $links );

	return $options;
});
```

## Programmatic Usage - Dynamic example
This example automatically gets all of the post categories and uses the category name as the keyword(s) and the category archive url as the url. Any post with a same text as a category name will automatically have a link to that category archive.

```
/**
 * Conditionally adds category archive links to Mai Link Injector.
 *
 * @param array $options
 *
 * @return array
 */
add_filter( 'mai_link_injector', function( $options ) {
	// Bail if not a single post.
	if ( ! is_singular( 'post' ) ) {
		return $options;
	}

	// Set taxonomy name.
	$taxonomy = 'category';

	// Get all terms.
	$terms = get_terms(
		[
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		]
	);

	// Bail if no terms.
	if ( ! $terms || is_wp_error( $terms ) ) {
		return $options;
	}

	// Create array of keywords => links.
	$links = [];

	foreach ( $terms as $term ) {
		$links[ $term->name ] = get_term_link( $term, $taxonomy );
	}

	// Bail if no links.
	if ( ! $links ) {
		return $options;
	}

	$options['singles'][] = 'post'; // Make sure posts are set to inject links.
	$options['singles']   = array_unique( $options['singles'] ); // Remove duplicates.
	$options['links']     = array_merge( $options['links'], $links ); // Adds new links.

	return $options;
});
```

## Limiting elements
Text within the following elements will not have links created:
```
h1
h2
h3
h4
h5
h6
a
blockquote
button
input
select
submit
textarea
```
If you'd like to add or remove these elements, the following filter is available:
```
$invalid = apply_filters( 'mai_link_injector_invalid_elements', $invalid );
```
