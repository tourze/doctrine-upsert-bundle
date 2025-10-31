# doctrine-upsert-bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-upsert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-upsert-bundle)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/doctrine-upsert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-upsert-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/doctrine-upsert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-upsert-bundle)
[![Build Status](https://github.com/tourze/doctrine-upsert-bundle/workflows/CI/badge.svg)](https://github.com/tourze/doctrine-upsert-bundle/actions)
[![Code Coverage](https://codecov.io/gh/tourze/doctrine-upsert-bundle/branch/master/graph/badge.svg)](https://codecov.io/gh/tourze/doctrine-upsert-bundle)


Efficient UPSERT (insert or update) capabilities for Doctrine ORM with automatic SQL 
generation and multi-database support.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
  - [Configuration](#configuration)
  - [Basic Usage](#basic-usage)
  - [Batch UPSERT](#batch-upsert)
  - [Low-Level UPSERT](#low-level-upsert)
- [Entity Requirements](#entity-requirements)
- [Advanced Usage](#advanced-usage)
  - [Custom Update Time Handling](#custom-update-time-handling)
  - [Extending with Custom Providers](#extending-with-custom-providers)
- [API Reference](#api-reference)
  - [UpsertManager](#upsertmanager)
  - [Supported Platforms](#supported-platforms)
- [Contributing](#contributing)
- [License](#license)
- [Changelog](#changelog)

## Features

- **Single and Batch UPSERT Operations**
  - MySQL: `INSERT ... ON DUPLICATE KEY UPDATE`
  - SQLite: `INSERT ... ON CONFLICT ... DO UPDATE SET`
- **Automatic Database Platform Detection** - Works seamlessly with MySQL and SQLite
- **Smart Unique Constraint Detection** - Automatically identifies unique constraints 
  from entity metadata
- **Extensible Provider System** - Custom UPSERT providers via `ProviderInterface`
- **Safe Error Handling** - Prevents EntityManager closure on errors
- **Automatic Timestamp Management** - Handles `create_time` and `update_time` fields 
  intelligently

## Requirements

- PHP >= 8.1
- doctrine/orm >= 3.0
- doctrine/dbal >= 4.0
- symfony >= 6.4

## Installation

```bash
composer require tourze/doctrine-upsert-bundle
```

## Quick Start

### Configuration

Register the bundle in `config/bundles.php`:

```php
return [
    Tourze\DoctrineUpsertBundle\DoctrineUpsertBundle::class => ['all' => true],
];
```

### Basic Usage

```php
use App\Entity\User;
use Tourze\DoctrineUpsertBundle\Service\UpsertManager;

// Inject UpsertManager (or use dependency injection)
public function __construct(
    private UpsertManager $upsertManager
) {}

// Single entity upsert
$user = new User();
$user->setEmail('user@example.com');
$user->setName('John Doe');

// Returns the persisted entity (may be different from input)
$persistedUser = $this->upsertManager->upsert($user);
```

### Batch UPSERT

```php
// Batch upsert with array data
$userData = [
    ['email' => 'user1@example.com', 'name' => 'User 1'],
    ['email' => 'user2@example.com', 'name' => 'User 2'],
];

$affectedRows = $this->upsertManager->executeBatch($userData, User::class);
```

### Low-Level UPSERT

```php
// Direct SQL execution
$insertData = ['email' => 'user@example.com', 'name' => 'John'];
$updateData = ['name' => 'John Doe Updated']; // Optional

$affectedRows = $this->upsertManager->execute('users', $insertData, $updateData);
```

## Entity Requirements

Your entities must have unique constraints defined for UPSERT operations:

```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'user_email_unique', columns: ['email'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $name;

    // Or use individual unique columns
    // #[ORM\Column(type: 'string', unique: true)]
    // private string $username;
}
```

## Advanced Usage

### Custom Update Time Handling

The bundle automatically handles `update_time` fields:

```php
class User
{
    #[ORM\Column(type: 'datetime')]
    private \DateTime $createTime;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updateTime;

    public function setUpdateTime(\DateTime $updateTime): self
    {
        $this->updateTime = $updateTime;
        return $this;
    }
}
```

### Extending with Custom Providers

Create custom database platform support:

```php
use Tourze\DoctrineUpsertBundle\Service\ProviderInterface;

class PostgreSQLUpsertProvider implements ProviderInterface
{
    public function supports(string $platform): bool
    {
        return $platform === 'postgresql';
    }

    public function getUpsertQuery(string $table, array $insertData, array $updateData): string
    {
        // Implementation for PostgreSQL UPSERT
        return "INSERT INTO {$table} ... ON CONFLICT ... DO UPDATE SET ...";
    }
}
```

## API Reference

### UpsertManager

- `upsert(object $entity, bool $fetchAgain = true): object` - Upsert single entity
- `execute(string $table, array $insertData, array $updateData = []): int` - Execute raw upsert
- `executeBatch(array $data, string $repositoryClass): int` - Batch upsert operation

### Supported Platforms

- **MySQL**: Uses `INSERT ... ON DUPLICATE KEY UPDATE`
- **SQLite**: Uses `INSERT ... ON CONFLICT ... DO UPDATE SET`

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow PSR-12 coding standards
4. Add tests for new features
5. Submit a pull request

## License

MIT License © tourze

## Changelog

See [CHANGELOG.md] or Git history for version updates and changes.
