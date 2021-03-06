# PDF Extractor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/falkemedia/pdf-extractor.svg?style=flat-square)](https://packagist.org/packages/falkemedia/pdf-extractor)
[![Total Downloads](https://img.shields.io/packagist/dt/falkemedia/pdf-extractor.svg?style=flat-square)](https://packagist.org/packages/falkemedia/pdf-extractor)

This package automates the generation of an SQLite database that you can use to do a full-text search on a PDF.
Meaning you take your PDF, use this tool to generate a database and then query the database and not the PDF for any text search.

This tool also generates thumbnails that you can use to display your search results however you like.

This is heavily inspired [spatie/pdf-to-image](https://github.com/spatie/pdf-to-image)   
and has a dependency of [spatie/pdf-to-text](https://github.com/spatie/pdf-to-text)   
 
  
## Installation

You can install the package via composer:

```bash
composer require falkemedia/pdf-extractor
```
This package requires the installation of ImageMagic and the **imagick** php extension.   
Instructions for macOS Catalina + PHP 7.3:   

```bash
brew install imagemagick 
pecl install imagick
```

> If there are any errors with imagemagic I suggest [reading through this guide](https://medium.com/@girishkr/install-imagick-on-macos-catalina-php-7-3-64b4e8542ba2)
   
Also, behind the scenes this package leverages [pdftotext](https://en.wikipedia.org/wiki/Pdftotext). 
On a mac you can install the binary using brew

```bash
brew install poppler
```

## Usage
examples/extract_pdf_data.php
``` php
<?php

namespace falkemedia\PdfExtractor\Examples;

use falkemedia\PdfExtractor\Extractor;

require 'vendor/autoload.php';

// Load PDF
$extractor = new Extractor();
$extractor->load('/path/to/a/pdf/file.pdf');

// Generate thumbnails
$extractor
    ->setMaxThumbnailHeight(600)
    ->setMaxThumbnailWidth(480)
    ->setQuality(75)
    ->generateThumbnails();

// Store Fulltext infos
$extractor->generateTextDatabase();

```

If you have a saved sqlite database you can do full-text queries like for example:

```sqlite
SELECT*FROM pages WHERE body MATCH "*YOUR_SEARCH_QUERY*"
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email tg@falkemedia.de instead of using the issue tracker.

## Credits

- [falkemedia](https://github.com/falkemedia)
- [Robin Reiter](https://github.com/robin7331)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).
