---
currentMenu: home
---
# Jaxion

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

Jaxion is a WordPress plugin framework for simplifying the use of a common set of object-oriented development patterns. 

## Install

Via Composer

``` bash
$ composer require intraxia/jaxion
```

## Usage

See the [documentation](/docs) for more information.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email jamesorodig@gmail.com instead of using the issue tracker.

## Credits

- [James DiGioia][link-author]

## Legacy Branch

A quick note about the `legacy-psr2` branch:

In a previous incarnation (before hitting a 0.1.0), Jaxion was developed using [PSR-2 coding standards]. Upon further reflection, in order to encourage uptake in the WordPress community, I rewrote the framework in the [WordPress coding standards]. This branch will exist until Jaxion hits a 1.0.0 for compatibility with Packagist, where it was registered, in case anyone had begun using it.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/intraxia/jaxion.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/intraxia/jaxion/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/intraxia/jaxion.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/intraxia/jaxion.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/intraxia/jaxion.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/intraxia/jaxion
[link-travis]: https://travis-ci.org/intraxia/jaxion
[link-scrutinizer]: https://scrutinizer-ci.com/g/intraxia/jaxion/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/intraxia/jaxion
[link-downloads]: https://packagist.org/packages/intraxia/jaxion
[link-author]: https://github.com/mAAdhaTTah

[PSR-2 coding standards]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[WordPress coding standards]: https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/