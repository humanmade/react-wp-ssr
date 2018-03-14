# PHP API

## `render( string $directory, array $options = [] ): void`

Render a React app into static HTML.

`$directory` specifies the root directory to load scripts from. This directory must contain your `build` directory.

You can specify the following options:

* `$handle` (`string`): Directory to load scripts from. This will default to the last part of the directory.
* `$container` (`string`, default `root`): ID for the container div. "root" by default.
* `$async` (`boolean`, default `true`): Should we load the script asynchronously on the frontend?


## `apply_filters( 'reactwpssr.functions', array $functions, string $directory, array $options )`

Filter functions available to the server-side rendering.

If you want to expose additional functions to your React app when rendering on the server-side, you can add them to this array.

`$functions` is a map of function name => callback. These functions will be exposed on `global.PHP`.

`$directory` and `$options` are the original parameters passed to `render()`.

