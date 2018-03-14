# Limitations

Server-side rendering is not a cure-all, so it's important to be mindful of the limitations to this approach. There are alternative approaches that might be better suited, so be sure to investigate all the options first.


## No Asynchronous Rendering

While some asynchronous operations (including promises) will work, React only does a single render pass when rending on the server. This means that any asynchronous rendering won't work; e.g. showing a loading screen before loading content.

Only the [componentWillMount](https://reactjs.org/docs/react-component.html#componentwillmount) lifecycle hook is called by React on the server. Ensure that any data manipulation you need to do is handled here.


## No External Requests

react-wp-ssr does not expose an way for your app to trigger external requests. These are usually handled asynchronously in JavaScript, so they aren't a good match for the synchronous fetches in PHP.

In most cases, you should supply the data statically via the usual `wp_localize_script` API. This ensures the data is also available for the initial React render as well.

If you do want to allow this, you can [expose a function to JS](api-php.md) to allow it to be used, but take care to sync carefully between the server and frontend.


## Limited Environment

react-wp-ssr uses [the v8js PHP extension](https://github.com/phpv8/v8js) to execute your JavaScript. While this is the full V8 (Chrome) JavaScript engine, it only contains a minimal environment (i.e. global variables). react-wp-ssr attempts to reimplement some of this functionality, but not all of it.

While most React apps will simply work straight out of the box (since they need to run in the browser anyway), it is important to keep this in mind if doing advanced operations. If you are used to working in a Node environment, you may find yourself severely limited.
