# react-wp-ssr

Server-side rendering for React-based WordPress plugins and themes.


## Requirements

* PHP 7.0+
* [v8js Extension for PHP](https://github.com/phpv8/v8js)

react-wp-ssr works best when used with [react-wp-scripts](https://github.com/humanmade/react-wp-scripts/) to handle the various build processes.

For local development, we recommend the [v8js extension for Chassis](https://github.com/Chassis/v8js).


## Quick Start

You should already have a React-based WP project ready to adapt, using [react-wp-scripts](https://github.com/humanmade/react-wp-scripts/).


### Step 1: Add backend library

Add this repository to your project, either with git submodules or Composer. You'll then need to load it into your project:

```php
require_once __DIR__ . '/vendor/react-wp-ssr/namespace.php';
```


### Step 2: Add backend render call

In PHP, call [`ReactWPSSR\render()`](docs/api-php.md) wherever you want to render your app. (Do not include the container yourself.)

For themes, the best practice is to create a minimal `index.php`:

```php
<?php

get_header();

ReactWPSSR\render( get_stylesheet_directory() );

get_footer();
```


### Step 3: Add frontend library

Add `react-wp-ssr` to your node modules:

```sh
npm install --save react-wp-ssr
```


### Step 4: Replace frontend render call

In your JavaScript file, replace your ReactDOM.render call with a call to `react-wp-ssr`'s [`render`](docs/api-js.md):

```js
import React from 'react';
import render from 'react-wp-ssr';

import App from './App';

render( () => <App /> );
```


## Developing with react-wp-ssr

By default, react-wp-ssr does not render on the server during development (i.e. with `WP_DEBUG` set to true), as it uses your built script; during development, your built script will tend to be behind your live development script, and this will cause hydration errors.

When you do want to test, there are two constants you can use to control react-wp-ssr:

```php
// Define as `true` to render on the server, even during development.
define( 'SSR_DEBUG_ENABLE', false );

// Define as `true` to only render on the server and skip loading the script.
// Useful to check the server is correctly rendering.
define( 'SSR_DEBUG_SERVER_ONLY', false );
```


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
