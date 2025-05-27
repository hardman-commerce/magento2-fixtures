# TddWizard Fixture library

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Code Climate](https://img.shields.io/codeclimate/maintainability/tddwizard/magento2-fixtures?style=flat-square)](https://codeclimate.com/github/tddwizard/magento2-fixtures)

---

Forked from [Magento 2 Fixtures by Fabian Schmengler](https://tddwizard.com/).  
Updated with code from [Klevu Test Fixtures](https://github.com/klevu/module-m2-test-fixtures)

## What is it?

An alternative to the procedural script-based fixtures in Magento 2 integration tests.

It aims to be:

- extensible
- expressive
- easy to use

## Installation

Install it into your Magento 2 project with composer:

    composer require --dev hardman-commerce/magento2-fixtures

## Requirements

- Magento 2.4.4+
- PHP 8.1+

---

## Documentation:

### [Catalog/Attribute](./src/Catalog/Attribute/README.md)

Fixtures and Traits to build catalog attributes, including store scope settings.  
Useful when an attribute is required to create configurable products.

### [Catalog/Category](./src/Catalog/Category/README.md)

Fixtures and Traits to build categories, including store scope settings.

### [Catalog/Product](./src/Catalog/Product/README.md)

Fixtures and Traits to build products, including store scope settings.  
Extended TDDWizard fixtures to add

* Tier Prices
* Images

The following product types are covered

* Simple
* Virtual
* Downloadable
* Grouped
* Configurable

Todo:

* Bundle
* Gift Card

### [Catalog/Rule](./src/Catalog/Rule/README.md)

Fixtures and Traits to build catalog price rules.

### [Checkout](./src/Checkout/README.md)

Fixtures and Traits to build Cart and perform a customer checkout.

Add the following product types to the cart:

* Simple
* Configurable
* Grouped

### [CMS](./src/Cms/README.md)

Fixtures and Traits to build CMS pages.

Todo:

* Blocks
* Widgets

### [Core](./src/Core/README.md)

Fixtures to create config settings.

### [Customer](./src/Customer/README.md)

Fixtures and Traits to build:

* Customer Addresses
* Customers
* Customer Groups

### [Sales](./src/Sales/README.md)

### [Store](./src/Store/README.md)

Fixtures and Traits to build:

* Stores
* Store Groups
* Websites

### [Tax](./src/Tax/README.md)

Fixtures and Traits to build

* Tax Classes
* Tax Rates
* Tax Rules

### Fixture pools

To manage multiple fixtures, **fixture pools** have been introduced for all entities:

Usage demonstrated with the `ProductFixturePool`:

```
protected function setUp()
{
    $this->productFixtures = new ProductFixturePool;
}

protected function tearDown()
{
    $this->productFixtures->rollback();
}

public function testSomethingWithMultipleProducts()
{
    $this->productFixtures->add(ProductBuilder::aSimpleProduct()->build());
    $this->productFixtures->add(ProductBuilder::aSimpleProduct()->build(), 'foo');
    $this->productFixtures->add(ProductBuilder::aSimpleProduct()->build());

    $this->productFixtures->get();      // returns ProductFixture object for last added product
    $this->productFixtures->get('foo'); // returns ProductFixture object for product added with specific key 'foo'
    $this->productFixtures->get(0);     // returns ProductFixture object for first product added without specific key (numeric array index)
}

```

---

## Credits

- [Fabian Schmengler][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.txt) for more information.

[ico-version]: https://img.shields.io/packagist/v/tddwizard/magento2-fixtures.svg?style=flat-square

[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square

[ico-travis]: https://img.shields.io/travis/tddwizard/magento2-fixtures/master.svg?style=flat-square

[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/tddwizard/magento2-fixtures?style=flat-square

[ico-code-quality]: https://img.shields.io/scrutinizer/g/tddwizard/magento2-fixtures.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/tddwizard/magento2-fixtures

[link-travis]: https://travis-ci.org/tddwizard/magento2-fixtures

[link-scrutinizer]: https://scrutinizer-ci.com/g/tddwizard/magento2-fixtures/code-structure

[link-code-quality]: https://scrutinizer-ci.com/g/tddwizard/magento2-fixtures

[link-author]: https://github.com/schmengler

[link-contributors]: ../../contributors

