<?php

namespace ReactWPSSR;

use V8Js;
use V8JsScriptException;

/**
 * Get data for the script.
 *
 * @param string $handle Script to get data for.
 * @return array|null Data registered for the script.
 */
function get_script_data( $handle ) {
	$scripts = wp_scripts();
	$data = $scripts->get_data( $handle, 'data' );
	return $data;
}

/**
 * Get data to load into the `window` object in JS.
 *
 * @return object `window`-compatible object.
 */
function get_window_object() {
	list( $path ) = explode( '?', $_SERVER['REQUEST_URI'] );
	$port = $_SERVER['SERVER_PORT'];
	$port = $port !== '80' && $port !== '443' ? (int) $port : '';
	$query = $_SERVER['QUERY_STRING'];
	return [
		'document' => null,
		'location' => [
			'hash'     => '',
			'host'     => $port ? $_SERVER['HTTP_HOST'] . ':' . $port : $_SERVER['HTTP_HOST'],
			'hostname' => $_SERVER['HTTP_HOST'],
			'pathname' => $path,
			'port'     => $port,
			'protocol' => is_ssl() ? 'https:' : 'http:',
			'search'   => $query ? '?' . $query : '',
		],
	];
}

/**
 * Render a JS bundle into a container.
 *
 * @param string $directory Root directory to load from.
 * @param array $options {
 *     Additional options and overrides.
 *
 *     @type string $handle Script handle. Defaults to basename of the directory.
 *     @type string $container ID for the container div. "root" by default.
 *     @type boolean $async Should we load the script asynchronously on the frontend?
 * }
 */
function render( $directory, $options = [] ) {
	$options = wp_parse_args( $options, [
		'handle'    => basename( $directory ),
		'container' => 'root',
		'async'     => true,
	] );
	$handle = $options['handle'];

	// Ensure the live script also receives the container.
	add_filter( 'script_loader_tag', function ( $tag, $script_handle ) use ( $handle, $options ) {
		if ( $script_handle !== $handle ) {
			return $tag;
		}

		// Allow disabling frontend rendering for debugging.
		if ( defined( 'SSR_DEBUG_SERVER_ONLY' ) && SSR_DEBUG_SERVER_ONLY ) {
			return '';
		}

		$new_tag = sprintf( '<script data-container="%s" ', esc_attr( $options['container'] ) );
		if ( $options['async'] ) {
			$new_tag .= 'async ';
		}
		return str_replace( '<script ', $new_tag, $tag );
	}, 10, 2 );

	// In development, don't render on the server unless enabled.
	if ( WP_DEBUG && ( ! defined( 'SSR_DEBUG_ENABLE' ) || SSR_DEBUG_ENABLE === false ) ) {
		$message = 'Skipping server-side render in development.';
		$message .= "\n\nRendering in development may cause hydration errors,";
		$message .= ' as the server renders from your built bundle, not from';
		$message .= ' your development script.';
		$message .= "\n\nAdd `define( 'SSR_DEBUG_ENABLE', true )` to your";
		$message .= ' wp-config to enable in development';
		wp_add_inline_script( $handle, 'console.info(' . wp_json_encode( $message ) . ');' );

		printf( '<div id="%s"></div>', esc_attr( $options['container'] ) );
		return;
	}

	$manifest_path = $directory . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'asset-manifest.json';
	$manifest = json_decode( file_get_contents( $manifest_path ), true );

	$data = get_script_data( $handle );

	// Create stubs.
	$window = wp_json_encode( get_window_object() );
	$setup = <<<END
// Set up browser-compatible APIs.
var window = this;
Object.assign( window, $window );
var console = {
	warn: print,
	error: print,
	log: ( print => it => print( JSON.stringify( it ) ) )( print )
};

// Expose more globals we might want.
var global = global || this,
	self = self || this;
var isSSR = true;

// Remove default top-level APIs.
delete exit;
delete var_dump;
delete require;
delete sleep;
END;

	$v8 = new V8Js();

	/**
	 * Filter functions available to the server-side rendering.
	 *
	 * @param array $functions Map of function name => callback. Exposed on the global `PHP` object.
	 * @param string $directory Root directory used for rendering.
	 * @param array $options Options passed to render.
	 */
	$functions = apply_filters( 'reactwpssr.functions', [], $directory, $options );
	foreach ( $functions as $name => $function ) {
		$v8->$name = $function;
	}

	// Load the app source.
	$source = file_get_contents( $directory . '/build/' . $manifest['main.js'] );

	try {
		// Run the setup.
		$v8->executeString( $setup, 'ssrBootstrap' );
		if ( $data ) {
			$v8->executeString( $data, 'ssrDataInjection' );
		}

		// Then, execute the script.
		ob_start();
		$v8->executeString( $source, './build/' . $manifest['main.js'] );
		$output = ob_get_clean();

		printf(
			'<div id="%s" data-rendered="">%s</div>',
			esc_attr( $options['container'] ),
			$output
		);
	} catch ( V8JsScriptException $e ) {
		if ( WP_DEBUG ) {
			$offsets = [
				'header' => $header_offset,
				'data'   => $data_offset,
			];
			handle_exception( $e, './build/' . $manifest['main.js'] );
		} else {
			// Trigger a warning, but otherwise do nothing.
			trigger_error( 'SSR error: '. $e->getMessage(), E_USER_WARNING );
		}

		// Error, so render an empty container.
		printf( '<div id="%s"></div>', esc_attr( $options['container'] ) );
	}
}

/**
 * Render JS exception handler.
 *
 * @param V8JsScriptException $e Exception to handle.
 */
function handle_exception( V8JsScriptException $e ) {
	$file = $e->getJsFileName();
	?>
	<style><?php echo file_get_contents( __DIR__ . '/error-overlay.css' ) ?></style>
	<div class="error-overlay"><div class="wrapper"><div class="overlay">
		<div class="header">Failed to render</div>
		<pre class="preStyle"><code class="codeStyle"><?php
			echo esc_html( $file ) . "\n";

			$trace = $e->getJsTrace();
			if ( $trace ) {
				$trace_lines = $error = explode( "\n", $e->getJsTrace() );
				echo esc_html( $trace_lines[0] ) . "\n\n";
			} else {
				echo $e->getMessage() . "\n\n";
			}

			// Replace tabs with tab character.
			$prefix = '> ' . (int) $e->getJsLineNumber() . ' | ';
			echo $prefix . str_replace(
				"\t",
				'<span class="tab">→</span>',
				esc_html( $e->getJsSourceLine() )
			) . "\n";
			echo str_repeat( " ", strlen( $prefix ) + $e->getJsStartColumn() );
			echo str_repeat( "^", $e->getJsEndColumn() - $e->getJsStartColumn() ) . "\n";
			?></code></pre>
		<div class="footer">
			<p>This error occurred during server-side rendering and cannot be dismissed.</p>
			<?php if ( $file === 'ssrBootstrap' ): ?>
				<p>This appears to be an internal error in SSR. Please report it on GitHub.</p>
			<?php elseif ( $file === 'ssrDataInjection' ): ?>
				<p>This appears to be an error in your script's data. Check that your data is valid.</p>
			<?php endif ?>
		</div>
	</div></div></div>
	<?php
}
