# PHP API

## `render( string $directory, array $options = [] ): void`

Render a React app into static HTML.

`$directory` specifies the root directory to load scripts from. This directory must contain your `build` directory.

You can specify the following options:

* `$handle` (`string`): Directory to load scripts from. This will default to the last part of the directory.
* `$container` (`string`, default `root`): ID for the container div. "root" by default.
* `$async` (`boolean`, default `true`): Should we load the script asynchronously on the frontend?

