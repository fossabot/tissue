# tissue

[![Latest Version on Packagist][ico-version]][link-packagist]
[![License][ico-license]](LICENSE)

Create Github issues from your ``catch {}`` blocks. I was heavily inspired by [ohCrash](https://ohcrash.com/).

When you call ``Tissue::create``, a Github issue is created in the repo of your choice and a "bug" label is automatically applied. Duplicates are detected, to a certain extent.

The name comes from "Throw ISSUE" — genius, I know.

## Install

``` bash
$ composer require bouiboui/tissue
```

Copy ``config/config.yaml.dist``, update it and save as ``config/config.yaml``

## Usage

``` php
# Not shown: include composer's autoload.php
use bouiboui\Tissue\Tissue;

try {

    throw new ErrorException('This is your issue title and message.');

} catch (\ErrorException $e) {

    $result = Tissue::create(
        $e->getMessage(),
        $e->getCode(),
        $e->getSeverity(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

}
```
Creates the following issue:

![Something like this](http://i.imgur.com/o5otoKU.png)

All parameters are optional. For security purposes, think twice before setting the `trace` parameter if your Github repository is public, unless you want strangers on the Internet to know the full path to your files on your server.

## Credits

- bouiboui — [Github](https://github.com/bouiboui) [Twitter](https://twitter.com/j_____________n) [Website](http://cod3.net)

## License

Unlicense. Public domain, basically. Please treat it kindly. See [License File](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/bouiboui/tissue.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-Unlicense-brightgreen.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/bouiboui/tissue
