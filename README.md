[![Build Status](https://travis-ci.com/commnetivity/pureHTML.svg?branch=master)](https://travis-ci.com/commnetivity/pureHTML) (actually, this class works, but the builds fail because there are no PHP Unit tests and instructions set up yet for Travis-CI)

PureHTML DOM Processor
======

This library leverages the speed and flexibility in using PHP's native DOM processor to let you parse, strip, splice and even beautify your HTML pages.

```php
<?php

$buffer = new PureHTML();

$template = file_get_contents("template.html");

$html->scan($template);  // Every call to scan() will build up $buffer with seen JS and CSS assets.

$html = $buffer->scrub($template); // Scrub the template of JS and CSS assets

/* Load up your dynamic content via DOM (optional, you can also use use a string of HTML) */
ob_start();
echo "Hello, world. I am to be spliced into the html div container with the id of \"content\".";
echo '<link href="helloworld.css" />';
$content = ob_get_contents();

$domOfDynamic = new DOMDocument(); // Create DOM object
$domOfDynamic->loadHTML($content); // Load DOM object with content
$frag = $domOfDynamic->saveHTML(); // Save the DOM into a string.

/* Scan fragment for JS and CSS */
$buffer->scan($frag); // If you want the buffer to contain JS and CSS resources in the order they are collected, then you must call this on each new HTML fragment or template you want to include.

$scrubbed_dynamic = $buffer->scrub($frag); // Now we scrub the fragment of JS and CSS
$template = $buffer->splice($template, $scrubbed_dynamic, "content"); // Now we splice the new fragment into templates. The "content" is the id of an html tag found in the template. In this case, it's <div id="content"></div>

$html = $buffer->rebuild($template); // When ready may now reassemble the compiled template.

echo $buffer->beautifyDOM($html); // Optionally use this to format the output to be a lot more human readable.

```

### Installation

The easiest way to get started is using [Composer](http://getcomposer.org) to install [commnetivity/purehtml](https://packagist.org/packages/commnetivity/purehtml):

```js
{
    "require": {
        "commnetivity/purehtml": "dev-master*"
    }
}
```

```php
<?php

require("vendor/autoload.php");

... your script ...

```

# Beautify DOM function, explained:

This is a PHP function that beautifies the output of an HTML document by adding indentation and line breaks to make it more readable. Here is a breakdown of how it works:

1. The function takes two parameters: `$doc`, which is the HTML document as a `DOMDocument` object, and `$depth`, which is the current depth of the recursion (default value is 1).
2. It creates a new `DOMXPath` object to query the HTML document.
3. It loops through all the text nodes in the HTML document using XPath, and removes any leading or trailing whitespace (including newlines, carriage returns, and spaces) from their values. If the resulting value is an empty string, it removes the node entirely.
4. It defines a recursive function called `$format` that takes three parameters: `$dom`, which is the `DOMDocument` object representing the HTML document, `$currentNode`, which is the current node being formatted, and `$depth`, which is the current depth of the recursion.
5. If `$currentNode` is false, it removes the first child node of `$dom` (which is typically the `<!DOCTYPE>` declaration) and sets `$currentNode` to `$dom` itself.
6. It determines whether the current node should be indented by checking if it is a text node and its parent only has one child node. If so, it sets `$indentCurrent` to false; otherwise, it sets it to true.
7. If `$indentCurrent` is true and `$depth` is greater than 1, it creates a new text node with a newline and `$depth` spaces and inserts it before `$currentNode`.
8. It loops through all the child nodes of `$currentNode` and recursively calls `$format` on each one, passing in `$dom`, the child node, and `$depth+1`.
9. If any of the child nodes was indented, it sets `$indentClosingTag` to true.
10. If `$indentClosingTag` is true, it creates a new text node with a newline and `$depth` spaces (unless the current node is the `<html>` tag), and appends it to `$currentNode`.
11. The function calls `$format` with `$doc` as the first argument and no second argument (which sets `$currentNode` to false), effectively formatting the entire HTML document.
12. The function returns the formatted HTML document with a `<!DOCTYPE>` declaration at the beginning.

