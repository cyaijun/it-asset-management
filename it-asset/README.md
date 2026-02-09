# IT 资产管理系统

一个功能完善的IT资产管理系统,基于PHP + MySQL开发,支持资产全生命周期管理。

## 主要功能

### 资产管理
- 资产入库、编辑、删除
- 资产领用/归还流程
- 资产维修记录
- 资产报废处理
- 资产状态追踪(在库、已领用、维修中、已报废、已丢失)
- 资产二维码生成和扫描
- 资产搜索和筛选

### 用户管理
- 用户创建和编辑
- 角色权限管理(管理员/普通用户)
- 用户状态管理(启用/禁用)
- 用户搜索功能

### 数据统计
- 资产总览统计
- 状态分布图表
- 类别分布统计
- 用户统计
- 最近交易记录

### 安全特性
- 登录认证系统
- 密码哈希存储
- CSRF防护
- 角色权限控制
- 审计日志记录

## 技术栈

- **后端**: PHP 7.4+
- **数据库**: MySQL 5.7+ / MariaDB
- **前端**: Bootstrap 5.3 + Bootstrap Icons
- **二维码**: PHP QR Code library

## 部署步骤

### 1. 解压文件

将项目文件解压到Web服务器目录:
```
C:\inetpub\wwwroot\it-asset\
```

### 2. 配置数据库

编辑 `.env` 文件,设置数据库连接信息:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=it_asset_db
DB_USER=root
DB_PASS=your_password_here
```

### 3. 创建数据库

在MySQL中执行数据库初始化脚本:

**首次安装:**
```bash
mysql -u root -p < scripts/create_db.sql
```

**更新现有数据库:**
```bash
mysql -u root -p < scripts/update_db.sql
```

### 4. 访问系统

打开浏览器访问:
- 首页: `http://yourserver/it-asset/`
- 登录页: `http://yourserver/it-asset/?p=login`

### 5. 默认账号

系统预置管理员账号,请首次登录后立即修改密码:
- 用户名: `admin`
- 密码: `admin123`

## 数据库表结构

### 核心表
- **Users** - 用户表
- **Assets** - 资产表
- **AssetTransactions** - 资产交易记录

### 扩展表
- **Departments** - 部门表
- **Maintenance** - 维修记录表
- **Disposals** - 报废记录表
- **Reservations** - 预约记录表
- **AuditLog** - 审计日志表

## 安全建议

1. 修改默认管理员密码
2. 使用强密码策略
3. 启用HTTPS
4. 定期备份数据库
5. 限制数据库用户权限
6. 不要将`.env`文件提交到版本控制
7. 定期更新PHP和MySQL版本

## 目录结构

```
it-asset/
├── .env                    # 环境配置文件
├── db.php                  # 数据库连接
├── functions.php           # 辅助函数
├── index.php              # 路由入口
├── lib/
│   ├── auth.php           # 认证和权限
│   ├── config.php         # 配置文件
│   ├── csrf.php           # CSRF防护
│   ├── phpqrcode.php      # 二维码生成库
│   └── validation.php     # 输入验证
├── pages/                 # 页面文件
│   ├── login.php          # 登录页
│   ├── assets_*.php       # 资产相关页面
│   ├── users_*.php        # 用户相关页面
│   ├── scan.php          # 扫码页面
│   └── statistics.php     # 统计页面
├── templates/
│   ├── header.php         # 页头模板
│   └── footer.php        # 页脚模板
└── scripts/
    ├── create_db.sql      # 初始数据库脚本
    └── update_db.sql      # 数据库更新脚本
```

## 资产状态说明

| 状态 | 说明 |
|------|------|
| InStock | 在库,可供领用 |
| Assigned | 已领用,由用户使用中 |
| Maintenance | 维修中,暂时不可用 |
| Disposed | 已报废,不再使用 |
| Lost | 已丢失 |

## 用户角色说明

| 角色 | 权限 |
|------|------|
| admin | 管理员,拥有所有权限 |
| user | 普通用户,可查看和操作资产 |

## 常见问题

### Q: 如何批量导入资产?
A: 当前版本暂不支持批量导入,建议使用API或Excel插件。

### Q: 如何备份数据?
A: 使用mysqldump命令: `mysqldump -u root -p it_asset_db > backup.sql`

### Q: 如何重置管理员密码?
A: 执行SQL: `UPDATE Users SET Password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE Username = 'admin';`

### Q: 支持IIS部署吗?
A: 支持,项目已包含web.config文件,可直接部署到IIS。

## 许可证

MIT License

## 贡献

欢迎提交Issue和Pull Request!

## 更新日志

### v2.0.0 (2026)
- ✨ 添加登录认证系统
- ✨ 添加角色权限管理
- ✨ 添加CSRF防护
- ✨ 添加资产编辑/删除功能
- ✨ 添加资产维修记录
- ✨ 添加资产报废功能
- ✨ 添加搜索和筛选功能
- ✨ 添加统计报表
- ✨ 添加审计日志
- 🎨 UI全面升级(Bootstrap 5 + Icons)
- 🔒 安全性增强

### v1.0.0
- 基础资产管理功能
- 用户管理
- 二维码生成
- 移动端扫码
