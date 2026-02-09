-- 更新数据库结构以支持新功能

USE it_asset_db;

-- 更新 Users 表,添加角色和密码字段
ALTER TABLE Users
ADD COLUMN Password VARCHAR(255) COMMENT '密码哈希',
ADD COLUMN Role ENUM('admin', 'user') DEFAULT 'user' COMMENT '角色',
ADD COLUMN Status ENUM('active', 'disabled') DEFAULT 'active' COMMENT '状态',
ADD COLUMN LastLoginAt DATETIME COMMENT '最后登录时间';

-- 更新 Assets 表,添加更多字段
ALTER TABLE Assets
ADD COLUMN WarrantyExpiry DATE COMMENT '保修到期日',
ADD COLUMN PurchasePrice DECIMAL(10,2) COMMENT '采购价格',
ADD COLUMN ImagePath VARCHAR(500) COMMENT '图片路径',
ADD COLUMN Notes TEXT COMMENT '备注';

-- 更新 AssetTransactions 表,添加操作员字段
ALTER TABLE AssetTransactions
ADD COLUMN OperatorId INT COMMENT '操作员ID',
ADD FOREIGN KEY (OperatorId) REFERENCES Users(Id) ON DELETE SET NULL;

-- 创建部门表
CREATE TABLE IF NOT EXISTS Departments (
  Id INT AUTO_INCREMENT PRIMARY KEY,
  Name VARCHAR(100) NOT NULL UNIQUE,
  Description TEXT,
  CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 创建维修记录表
CREATE TABLE IF NOT EXISTS Maintenance (
  Id INT AUTO_INCREMENT PRIMARY KEY,
  AssetId INT NOT NULL,
  Type VARCHAR(50) NOT NULL COMMENT '维修类型',
  Description TEXT COMMENT '故障描述',
  Cost DECIMAL(10,2) COMMENT '维修费用',
  StartedAt DATETIME COMMENT '开始时间',
  CompletedAt DATETIME COMMENT '完成时间',
  Status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
  Notes TEXT,
  CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (AssetId) REFERENCES Assets(Id) ON DELETE CASCADE
);

-- 创建报废记录表
CREATE TABLE IF NOT EXISTS Disposals (
  Id INT AUTO_INCREMENT PRIMARY KEY,
  AssetId INT NOT NULL,
  Reason TEXT COMMENT '报废原因',
  DisposedAt DATETIME NOT NULL,
  OperatorId INT COMMENT '操作员ID',
  Notes TEXT,
  CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (AssetId) REFERENCES Assets(Id) ON DELETE CASCADE,
  FOREIGN KEY (OperatorId) REFERENCES Users(Id) ON DELETE SET NULL
);

-- 创建预约记录表
CREATE TABLE IF NOT EXISTS Reservations (
  Id INT AUTO_INCREMENT PRIMARY KEY,
  AssetId INT NOT NULL,
  UserId INT NOT NULL,
  ReservedAt DATETIME NOT NULL COMMENT '预约日期',
  ExpectedReturnDate DATE COMMENT '预计归还日期',
  Status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
  ApprovedBy INT COMMENT '审批人ID',
  ApprovedAt DATETIME COMMENT '审批时间',
  Notes TEXT,
  CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (AssetId) REFERENCES Assets(Id) ON DELETE CASCADE,
  FOREIGN KEY (UserId) REFERENCES Users(Id) ON DELETE CASCADE,
  FOREIGN KEY (ApprovedBy) REFERENCES Users(Id) ON DELETE SET NULL
);

-- 创建审计日志表
CREATE TABLE IF NOT EXISTS AuditLog (
  Id INT AUTO_INCREMENT PRIMARY KEY,
  UserId INT COMMENT '操作用户ID',
  Action VARCHAR(50) NOT NULL COMMENT '操作类型',
  ResourceType VARCHAR(50) COMMENT '资源类型',
  ResourceId INT COMMENT '资源ID',
  Details TEXT COMMENT '详细信息',
  IpAddress VARCHAR(45) COMMENT 'IP地址',
  UserAgent TEXT COMMENT '用户代理',
  CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (UserId) REFERENCES Users(Id) ON DELETE SET NULL,
  INDEX idx_user_id (UserId),
  INDEX idx_action (Action),
  INDEX idx_created_at (CreatedAt)
);

-- 更新默认管理员密码 (密码: admin123)
UPDATE Users SET Password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', Role = 'admin', Status = 'active' WHERE Username = 'admin';

-- 如果没有admin用户,则创建
INSERT IGNORE INTO Users (Username, FullName, Email, Password, Role, Status)
VALUES ('admin', '系统管理员', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
