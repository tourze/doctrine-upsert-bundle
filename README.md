# doctrine-upsert-bundle

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)

## Introduction

doctrine-upsert-bundle provides efficient UPSERT (insert or update) support for Doctrine ORM, compatible with popular databases such as MySQL. It automatically generates UPSERT SQL for high-performance batch writes and data synchronization under unique constraints.

## Features
- Supports single and batch UPSERT operations (INSERT ... ON DUPLICATE KEY UPDATE)
- Auto-detects database platform, compatible with MySQL
- Automatically generates UPSERT SQL based on entity unique constraints
- Extensible via custom UPSERT Provider
- Friendly error handling, prevents EntityManager from closing unexpectedly

## Installation
### Requirements
- PHP >= 8.1
- doctrine/orm >= 2.20
- doctrine/dbal >= 3.7
- symfony >= 6.4

### Composer
```bash
composer require tourze/doctrine-upsert-bundle
```

## Quick Start
### Configuration
Ensure registration in `config/bundles.php`:
```php
return [
    Tourze\DoctrineUpsertBundle\DoctrineUpsertBundle::class => ['all' => true],
];
```

### Basic Usage
```php
use App\Entity\YourEntity;
use Tourze\DoctrineUpsertBundle\Service\UpsertManager;

// Inject UpsertManager
$entity = new YourEntity();
// Set properties ...
$upserted = $upsertManager->upsert($entity);
```

### Batch UPSERT
```php
$data = [
    ['id' => 1, 'name' => 'foo'],
    ['id' => 2, 'name' => 'bar'],
];
$upsertManager->executeBatch($data, YourEntity::class);
```

## Configuration
- Extend different database platforms by implementing ProviderInterface
- Custom logic for handling unique constraint fields is supported

## Contributing
- Please describe issues and PRs in detail
- Follow PSR-12 coding standards
- Ensure new features have test coverage

## License
MIT License © tourze

## Changelog
See [CHANGELOG.md] or Git history for details.
