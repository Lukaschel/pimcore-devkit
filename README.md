# PimcoreDevkitBundle

With this bundle it is possible to generate pimcore bundles, controllers, areabricks, event subscribers...
You can find a complete command list in the usage part.

[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![PhpStan](https://img.shields.io/badge/PHPStan-level%204-brightgreen.svg?style=flat-square)](#)

## Installation

```json
"require" : {
    "lukaschel/pimcore-devkit" : "~1.0.0"
}
```

## Usage
Here you can see the available symfony cli commands by using bin/console

```shell script
devkit:generate:bundle
devkit:generate:controller
devkit:generate:areabrick
devkit:generate:event_subscriber
devkit:generate:command
devkit:generate:twig_extension
```

## Overwriting Templates
Symfony allows to override every view and so does this Bundle.

[Documentation](https://symfony.com/doc/current/bundles/override.html#templates) 

## Copyright and license
For licensing details please visit [LICENSE.md](LICENSE.md)
