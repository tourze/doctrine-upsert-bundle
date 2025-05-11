# doctrine-upsert-bundle

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)

## Introduction

doctrine-upsert-bundle provides efficient UPSERT (insert or update) capabilities for Doctrine ORM, supporting major databases like MySQL and SQLite. It automatically generates UPSERT SQL to achieve high-performance batch writing and data synchronization under unique constraints.

## Features

- Support for single and batch UPSERT operations
  - MySQL: INSERT ... ON DUPLICATE KEY UPDATE
  - SQLite: INSERT ... ON CONFLICT ... DO UPDATE SET
- Automatic database platform detection, compatible with MySQL and SQLite
- Automatic generation of UPSERT statements based on entity unique constraints
- Support for custom UPSERT Providers for extensibility
- Friendly error handling, avoiding EntityManager closure

## Installation

### Requirements

- PHP >= 8.1
- doctrine/orm >= 2.20
- doctrine/dbal >= 3.7
- symfony >= 6.4

### Composer Installation

```bash
composer require tourze/doctrine-upsert-bundle
```

## Quick Start

### Configuration

Make sure to register in `config/bundles.php`:

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

## Configuration Options

- Support for extending different database platform UPSERTs by implementing ProviderInterface
- Supported database platforms:
  - MySQL (MySQLUpsertProvider)
  - SQLite (SQLiteUpsertProvider)
- Custom unique constraint field handling logic

## Contribution Guidelines

- Describe issues and changes in detail before submitting Issues or PRs
- Follow PSR-12 coding standards
- Ensure new features have test cases

## Copyright and License

MIT License © tourze

## Update Log

See [CHANGELOG.md] or Git history.
