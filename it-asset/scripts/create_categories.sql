-- 创建资产类别表
USE it_asset_db;

CREATE TABLE IF NOT EXISTS `AssetCategories` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(100) NOT NULL UNIQUE COMMENT '类别名称',
  `Description` TEXT COMMENT '类别描述',
  `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX idx_name (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入常用类别
INSERT INTO AssetCategories (Name, Description) VALUES
('笔记本电脑', '各类笔记本电脑设备'),
('台式电脑', '台式机主机和显示器'),
('服务器', '服务器设备'),
('网络设备', '路由器、交换机等网络设备'),
('打印机', '打印机和复印机'),
('手机', '公司配发的手机设备'),
('平板电脑', '各类平板设备'),
('外设配件', '键盘、鼠标、耳机等外设'),
('软件许可', '各类软件许可证'),
('办公设备', '桌椅、文件柜等办公用品');
