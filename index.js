import ReactDOM from 'react-dom';
import ReactDOMServer from 'react-dom/server';

export const ENV_BROWSER = 'browser';
export const ENV_SERVER = 'server';

// Store current script reference in case we need it later, as currentScript
// is only available on the first (synchronous) run.
let renderedScript = false;
try {
	renderedScript = document && document.currentScript;
} catch ( err ) {
	// No-op; not defined in Node.
}

export function getEnvironment() {
	return typeof global.isSSR === 'undefined' ? ENV_BROWSER : ENV_SERVER;
}

export const onFrontend = callback => getEnvironment() === ENV_BROWSER && callback();
export const onBackend = callback => getEnvironment() === ENV_SERVER && callback();

export default function render( getComponent, onClientRender ) {
	const environment = getEnvironment();
	const component = getComponent( environment );

	switch ( environment ) {
		case ENV_SERVER:
			global.print( ReactDOMServer.renderToString( component ) );
			break;

		case ENV_BROWSER: {
			const container = document.getElementById( renderedScript.dataset.container );
			const didRender = 'rendered' in container.dataset;

			if ( didRender ) {
				ReactDOM.hydrate(
					component,
					container,
					onClientRender
				);
			} else {
				ReactDOM.render(
					component,
					container,
					onClientRender
				);
			}
			break;
		}

		default:
			throw new Error( `Unknown environment "${ environment }"` );
	}
}
