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
