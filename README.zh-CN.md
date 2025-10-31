# doctrine-upsert-bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-upsert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-upsert-bundle)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/doctrine-upsert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-upsert-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/doctrine-upsert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-upsert-bundle)
[![Build Status](https://github.com/tourze/doctrine-upsert-bundle/workflows/CI/badge.svg)](https://github.com/tourze/doctrine-upsert-bundle/actions)
[![Code Coverage](https://codecov.io/gh/tourze/doctrine-upsert-bundle/branch/master/graph/badge.svg)](https://codecov.io/gh/tourze/doctrine-upsert-bundle)


为 Doctrine ORM 提供高效的 UPSERT（插入或更新）能力，支持自动 SQL 生成和多数据库平台。

## 目录

- [功能特性](#功能特性)
- [环境要求](#环境要求)
- [安装说明](#安装说明)
- [快速开始](#快速开始)
  - [配置](#配置)
  - [基本用法](#基本用法)
  - [批量 UPSERT](#批量-upsert)
  - [底层 UPSERT](#底层-upsert)
- [实体要求](#实体要求)
- [高级用法](#高级用法)
  - [自定义更新时间处理](#自定义更新时间处理)
  - [使用自定义提供者扩展](#使用自定义提供者扩展)
- [API 参考](#api-参考)
  - [UpsertManager](#upsertmanager)
  - [支持的平台](#支持的平台)
- [贡献指南](#贡献指南)
- [许可证](#许可证)
- [更新日志](#更新日志)

## 功能特性

- **单条和批量 UPSERT 操作**
  - MySQL: `INSERT ... ON DUPLICATE KEY UPDATE`
  - SQLite: `INSERT ... ON CONFLICT ... DO UPDATE SET`
- **自动数据库平台识别** - 无缝支持 MySQL 和 SQLite
- **智能唯一约束检测** - 自动从实体元数据识别唯一约束
- **可扩展的提供者系统** - 通过 `ProviderInterface` 支持自定义 UPSERT 提供者
- **安全错误处理** - 避免出错时 EntityManager 关闭
- **自动时间戳管理** - 智能处理 `create_time` 和 `update_time` 字段

## 环境要求

- PHP >= 8.1
- doctrine/orm >= 3.0
- doctrine/dbal >= 4.0
- symfony >= 6.4

## 安装说明

```bash
composer require tourze/doctrine-upsert-bundle
```

## 快速开始

### 配置

在 `config/bundles.php` 中注册 Bundle：

```php
return [
    Tourze\DoctrineUpsertBundle\DoctrineUpsertBundle::class => ['all' => true],
];
```

### 基本用法

```php
use App\Entity\User;
use Tourze\DoctrineUpsertBundle\Service\UpsertManager;

// 注入 UpsertManager（或使用依赖注入）
public function __construct(
    private UpsertManager $upsertManager
) {}

// 单个实体 upsert
$user = new User();
$user->setEmail('user@example.com');
$user->setName('John Doe');

// 返回持久化的实体（可能与输入不同）
$persistedUser = $this->upsertManager->upsert($user);
```

### 批量 UPSERT

```php
// 使用数组数据进行批量 upsert
$userData = [
    ['email' => 'user1@example.com', 'name' => 'User 1'],
    ['email' => 'user2@example.com', 'name' => 'User 2'],
];

$affectedRows = $this->upsertManager->executeBatch($userData, User::class);
```

### 底层 UPSERT

```php
// 直接执行 SQL
$insertData = ['email' => 'user@example.com', 'name' => 'John'];
$updateData = ['name' => 'John Doe Updated']; // 可选

$affectedRows = $this->upsertManager->execute('users', $insertData, $updateData);
```

## 实体要求

您的实体必须定义唯一约束才能进行 UPSERT 操作：

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

    // 或者使用单独的唯一字段
    // #[ORM\Column(type: 'string', unique: true)]
    // private string $username;
}
```

## 高级用法

### 自定义更新时间处理

Bundle 会自动处理 `update_time` 字段：

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

### 使用自定义提供者扩展

创建自定义数据库平台支持：

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
        // PostgreSQL UPSERT 实现
        return "INSERT INTO {$table} ... ON CONFLICT ... DO UPDATE SET ...";
    }
}
```

## API 参考

### UpsertManager

- `upsert(object $entity, bool $fetchAgain = true): object` - 单个实体 upsert
- `execute(string $table, array $insertData, array $updateData = []): int` - 执行原始 upsert
- `executeBatch(array $data, string $repositoryClass): int` - 批量 upsert 操作

### 支持的平台

- **MySQL**: 使用 `INSERT ... ON DUPLICATE KEY UPDATE`
- **SQLite**: 使用 `INSERT ... ON CONFLICT ... DO UPDATE SET`

## 贡献指南

1. Fork 仓库
2. 创建功能分支
3. 遵循 PSR-12 代码规范
4. 为新功能添加测试
5. 提交 Pull Request

## 许可证

MIT License © tourze

## 更新日志

详见 [CHANGELOG.md] 或 Git 历史记录获取版本更新和变更信息。
