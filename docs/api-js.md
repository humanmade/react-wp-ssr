# JavaScript API

The `react-wp-ssr` package provides one default export (`render`), and a bunch of utility functions and constants.

## Constants

`import { ENV_BROWSER, ENV_SERVER } from 'react-wp-ssr`

These constants are available if you want to use typehinting instead of checking arbitrary strings.

* `ENV_BROWSER = 'browser'`
* `ENV_SERVER = 'server'`


## `render( getComponent: environment => React.Element, onClientRender: () => void ): void`

`import render from 'react-wp-ssr'`

Render a React component on the frontend and backend.

`getComponent` receives the current environment as a parameter (one of `ENV_SERVER` or `ENV_BROWSER`) and should return a React element to be rendered or mounted to the DOM.

`onClientRender` is an optional callback. This is only called on the frontend after `ReactDOM.hydrate` or `ReactDOM.render` has completed.


## `getEnvironment(): string`

`import { getEnvironment } from 'react-wp-ssr'`

Get the current environment being rendered. One of `ENV_SERVER` or `ENV_BROWSER`.


## `onFrontend( () => void ): void`

`import { onFrontend } from 'react-wp-ssr'`

Run a callback only on the frontend (browser).


## `onBackend( () => void ): void`

`import { onBackend } from 'react-wp-ssr'`

Run a callback only on the backend (server).