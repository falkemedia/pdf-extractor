# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/robin7331/pdf-extractor.svg?style=flat-square)](https://packagist.org/packages/robin7331/pdf-extractor)
[![Build Status](https://img.shields.io/travis/robin7331/pdf-extractor/master.svg?style=flat-square)](https://travis-ci.org/robin7331/pdf-extractor)
[![Quality Score](https://img.shields.io/scrutinizer/g/robin7331/pdf-extractor.svg?style=flat-square)](https://scrutinizer-ci.com/g/robin7331/pdf-extractor)
[![Total Downloads](https://img.shields.io/packagist/dt/robin7331/pdf-extractor.svg?style=flat-square)](https://packagist.org/packages/robin7331/pdf-extractor)

This package lets you generate an SQLite Database used for a full-text search across a PDF document.
Also it generates thumbnails of every page of the pdf.

## Installation

You can install the package via composer:

```bash
composer require robin7331/pdf-extractor
```
This package requires the installation of ImageMagic and the **imagick** php extension.   
Instructions for macOS Catalina + PHP 7.3:   

```bash
brew install imagemagick 
pecl install imagick
```

If there are any errors I suggest [reading through this guide](https://medium.com/@girishkr/install-imagick-on-macos-catalina-php-7-3-64b4e8542ba2)
## Usage

``` php
// Usage description here
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

If you discover any security related issues, please email robin7331@gmail.com instead of using the issue tracker.

## Credits

- [Robin Reiter](https://github.com/robin7331)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).
