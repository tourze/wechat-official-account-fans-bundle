# WeChat Official Account Fans Bundle

[English](README.md) | [中文](README.zh-CN.md)

## 📝 项目简介

本Bundle为微信公众号粉丝管理系统，完整对接微信公众号用户管理API，提供粉丝信息同步、标签管理、黑名单管理等功能。

### 🎯 核心功能

- **粉丝管理**：同步粉丝列表、用户信息、关注状态
- **标签管理**：创建、编辑、删除标签，批量打标签/取消标签
- **黑名单管理**：同步黑名单列表，批量拉黑/取消拉黑
- **自动同步**：定时任务自动同步数据，确保数据一致性

### 📊 数据模型

```
Account (公众号)
    ↓
Fan (粉丝) ←→ FanTag (关系) ←→ Tag (标签)
```

## 🚀 快速开始

### 安装依赖

```bash
composer require tourze/wechat-official-account-fans-bundle
```

### 注册Bundle

在 `config/bundles.php` 中添加：

```php
return [
    // ...
    Tourze\WechatOfficialAccountFansBundle\WechatOfficialAccountFansBundle::class => ['all' => true],
];
```

## 🔧 API接口对应

### 标签管理API (8个)

| API名称 | Request类 | 说明 |
|---------|----------|------|
| 获取标签 | `GetTagsRequest` | 获取公众号已创建的标签 |
| 创建标签 | `CreateTagRequest` | 创建标签 |
| 编辑标签 | `UpdateTagRequest` | 编辑标签名 |
| 删除标签 | `DeleteTagRequest` | 删除标签 |
| 获取标签下粉丝 | `GetTagUsersRequest` | 获取标签下粉丝列表 |
| 批量打标签 | `BatchTagUsersRequest` | 为用户批量打标签 |
| 批量取消标签 | `BatchUntagUsersRequest` | 为用户批量取消标签 |
| 获取用户标签 | `GetUserTagsRequest` | 获取用户身上的标签列表 |

### 用户信息API (7个)

| API名称 | Request类 | 说明 |
|---------|----------|------|
| 获取用户信息 | `GetUserInfoRequest` | 获取用户基本信息 |
| 批量获取用户信息 | `BatchGetUserInfoRequest` | 批量获取用户基本信息 |
| 获取用户列表 | `GetFollowersListRequest` | 获取关注用户列表 |
| 设置用户备注 | `SetUserRemarkRequest` | 设置用户备注名 |
| 获取黑名单 | `GetBlacklistRequest` | 获取公众号黑名单列表 |
| 拉黑用户 | `BatchBlacklistRequest` | 拉黑用户 |
| 取消拉黑 | `BatchUnblacklistRequest` | 取消拉黑用户 |

## ⚡ 同步策略

### 自动同步任务

| Command | 调度时间 | 策略 | 说明 |
|---------|----------|------|------|
| `SyncTagsCommand` | 02:05 | 轻量全量 | 同步标签列表 |
| `SyncFollowersCommand` | 02:10 | 重量分页 | 同步粉丝OpenID列表 |
| `SyncUserInfoCommand` | 02:30 | 重量批量 | 同步粉丝详细信息 |
| `SyncBlacklistCommand` | 02:50 | 轻量全量 | 同步黑名单列表 |

### 手动执行

```bash
# 同步标签
php bin/console wechat:official-account:sync-tags

# 同步粉丝列表
php bin/console wechat:official-account:sync-followers

# 同步用户详细信息
php bin/console wechat:official-account:sync-user-info

# 同步黑名单
php bin/console wechat:official-account:sync-blacklist
```

## 🏗️ 架构设计

### 核心实体

Entity类中已包含完整的ORM注解，会自动生成对应的数据表：

```php
// 粉丝实体
class Fan {
    private string $openid;        // 微信OpenID
    private ?string $unionid;      // UnionID  
    private ?string $nickname;     // 昵称
    private FanStatus $status;     // 状态：已关注/已取消/已拉黑
    // ... 更多字段
}

// 标签实体
class Tag {
    private int $tagid;           // 微信标签ID
    private string $name;         // 标签名称
    private int $count;           // 粉丝数量
}

// 粉丝标签关系
class FanTag {
    private Fan $fan;
    private Tag $tag;
}
```

### 数据库表结构

Entity会自动生成以下数据表：
- `wechat_official_account_fan` - 粉丝表
- `wechat_official_account_tag` - 标签表  
- `wechat_official_account_fan_tag` - 粉丝标签关系表

## 📈 使用示例

### 获取粉丝列表

```php
$fanRepository = $this->get(FanRepository::class);
$fans = $fanRepository->findSubscribedByAccount($account);
```

### 查找标签下的粉丝

```php
$fans = $fanRepository->findByAccountAndTagId($account, $tagId);
```

### 获取用户的标签

```php
$tags = $tagRepository->findByAccountAndFanOpenid($account, $openid);
```

## 🛡️ 安全特性

- **API频率限制**：内置延迟机制，避免触发微信API频次限制
- **批量处理**：遵循微信API限制，批量操作最多20个用户
- **错误隔离**：单个Account出错不影响其他Account同步
- **幂等性**：重复执行同步命令不会产生副作用

## 📋 系统要求

- PHP 8.1+
- Symfony 7.3+
- MySQL 8.0+
- 依赖 `tourze/wechat-official-account-bundle`

## 🔧 配置

### 日志配置

在 `config/packages/monolog.yaml` 中添加：

```yaml
monolog:
    channels: ['wechat_official_account_fans']
    handlers:
        wechat_fans:
            type: stream
            path: '%kernel.logs_dir%/wechat_fans.log'
            level: info
            channels: ['wechat_official_account_fans']
```

### 定时任务

确保系统运行cron服务以执行自动同步任务。

## ⚠️ 注意事项

1. **微信API限制**：
    - 批量操作最多20个用户
    - 有调用频次限制
    - 需要access_token认证

2. **数据同步**：
    - 首次同步可能需要较长时间
    - 用户昵称/头像受微信隐私政策限制
    - 取关用户信息可能无法获取

3. **性能优化**：
    - 大量粉丝时建议分批同步
    - 监控同步任务执行时间
    - 定期清理无效数据

## 🐛 故障排除

### 常见问题

1. **同步失败**：检查access_token是否有效
2. **部分数据缺失**：检查微信API权限设置  
3. **性能问题**：调整批量处理大小

### 日志检查

```bash
tail -f var/log/wechat_fans.log
```

## 📄 许可证

MIT License

## 🤝 贡献

欢迎提交Issue和PR！

---

> 🎯 **设计原则**：简单实用，数据结构优先，消除特殊情况
> 
> 💡 **架构思想**：基于Linus Torvalds的"好品味"工程理念设计