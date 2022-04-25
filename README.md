*Convert images with PHP*

This library enables you to do images conversion with PHP. It supports an abundance of methods for converting and automatically selects the most capable of these that is available on the system.

The library can convert using the following methods:
- using [Imagick extension](https://github.com/Imagick/imagick)
- executing [imagemagick](https://imagemagick.org/index.php) binary using an `exec` call
- using [Vips PHP extension](https://github.com/libvips/php-vips-ext))
- executing [ffmpeg](https://ffmpeg.org/) binary using an `exec` call
- using [Gmagick extension](https://www.php.net/manual/en/book.gmagick.php)
- executing [graphicsmagick](http://www.graphicsmagick.org/) binary using an `exec` call
- using the [Gd extension](https://www.php.net/manual/en/book.image.php))
- executing [cwebp](https://developers.google.com/speed/webp/docs/cwebp) binary using an `exec` call (only used for converting images to webp)
- using the [ewww](https://ewww.io/plans/) cloud converter

## Installation
Require the library with *Composer*, like this:

```text
composer require rosell-dk/image-convert
```

## Converting images
Here is a minimal example of converting using the *ImageConvert::convert* method:

```php
// Initialise your autoloader (this example is using Composer)
require 'vendor/autoload.php';

use ImageConvert\ImageConvert;

$source = __DIR__ . '/logo.jpg';
$destination = $source . '.webp';
$options = [];
ImageConvert::convert($source, $destination, $options);
```

## Work in progress...

The library is under development. It works, but it is not production-ready.


## Do you like what I do?
Perhaps you want to support my work, so I can continue doing it :)

- [Become a backer or sponsor on Patreon](https://www.patreon.com/rosell).
- [Buy me a Coffee](https://ko-fi.com/rosell)
