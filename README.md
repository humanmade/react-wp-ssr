# react-wp-ssr

Server-side rendering for React-based WordPress plugins and themes.


## Requirements

* PHP 7.0+
* [v8js Extension for PHP](https://github.com/phpv8/v8js)


## Quick Start

We highly recommend using [react-wp-scripts](https://github.com/humanmade/react-wp-scripts/) to handle the various build processes.

1. Add this repository to your project.
2. Add `react-wp-ssr` to your node modules: `npm install --save react-wp-ssr`
3. In PHP, call [`ReactWPSSR\render()`](docs/api-php.md) wherever you want to render your app.
4. In JS, call `react-wp-ssr`'s [`render`](docs/api-js.md) function instead of calling `ReactDOM.render` function.

For themes, the best practice is to create a minimal `index.php`:

```php
<?php

get_header();

ReactWPSSR\render( get_stylesheet_directory() );

get_footer();
```

In your JavaScript file, replace your ReactDOM.render call with a call to `react-wp-ssr`'s render:

```js
import React from 'react';
import render from 'react-wp-ssr';

import App from './App';

render( () => <App /> );
```


## Developing with react-wp-ssr

By default, react-wp-ssr does not render on the server during development (i.e. with `WP_DEBUG` set to true), as it uses your built script; during development, your built script will tend to be behind your live development script, and this will cause hydration errors.

When you do want to test, there are two constants you can use to control react-wp-ssr:

* `SSR_DEBUG_ENABLE` (boolean, default `false`) - Define as `true` to override the development checks.
* `SSR_DEBUG_SERVER_ONLY` (boolean, default `false`) - Define as `true` to only render on the server and skip loading the script. Useful to check the server is correctly rendering.


## Detecting the Environment

The callback you pass to `render` will receive the [current environment](docs/api-js.md#constants) as a parameter, allowing you to change what you render when you need to:

```js
import { BrowserRouter, StaticRouter } from 'react-router-dom';

render( environment => {
	const Router = environment === 'server' ? StaticRouter : BrowserRouter;
	const routerProps = environment === 'server' ? { location: window.location } : {};

	return <Router { ...routerProps }>
		<App />
	</Router>;
} );
```

(Note that this should be used sparingly, as [React's hydration](https://reactjs.org/docs/react-dom.html#hydrate) will complain if the HTML does not match what it expects.)

You can also use the [`onFrontend` and `onBackend` functions](docs/api-js.md) to run callbacks only in a single environment if you need.
