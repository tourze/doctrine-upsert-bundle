# doctrine-upsert-bundle

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)

## 简介

doctrine-upsert-bundle 为 Doctrine ORM 提供高效的 UPSERT（插入或更新）能力，支持 MySQL 等主流数据库。通过自动生成 UPSERT SQL，实现实体的高性能批量写入和唯一约束下的数据同步。

## 功能特性
- 支持单条和批量 UPSERT 操作（INSERT ... ON DUPLICATE KEY UPDATE）
- 自动识别数据库平台，兼容 MySQL
- 自动根据实体唯一约束生成 UPSERT 语句
- 支持自定义 UPSERT Provider，便于扩展
- 错误处理友好，避免 EntityManager 关闭

## 安装说明
### 环境要求
- PHP >= 8.1
- doctrine/orm >= 2.20
- doctrine/dbal >= 3.7
- symfony >= 6.4

### Composer 安装
```bash
composer require tourze/doctrine-upsert-bundle
```

## 快速开始
### 配置
确保在 `config/bundles.php` 注册：
```php
return [
    Tourze\DoctrineUpsertBundle\DoctrineUpsertBundle::class => ['all' => true],
];
```

### 基本用法
```php
use App\Entity\YourEntity;
use Tourze\DoctrineUpsertBundle\Service\UpsertManager;

// 注入 UpsertManager
$entity = new YourEntity();
// 设置属性 ...
$upserted = $upsertManager->upsert($entity);
```

### 批量 UPSERT
```php
$data = [
    ['id' => 1, 'name' => 'foo'],
    ['id' => 2, 'name' => 'bar'],
];
$upsertManager->executeBatch($data, YourEntity::class);
```

## 配置项说明
- 支持通过实现 ProviderInterface 扩展不同数据库平台的 UPSERT
- 可自定义唯一约束字段处理逻辑

## 贡献指南
- 提交 Issue 或 PR 前请详细描述问题和改动
- 遵循 PSR-12 代码规范
- 保证新增功能具备测试用例

## 版权和许可
MIT License © tourze

## 更新日志
详见 [CHANGELOG.md] 或 Git 历史记录。
