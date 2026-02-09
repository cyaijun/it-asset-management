-- 创建资产凭证表（用于安全存储敏感信息）
USE it_asset_db;

CREATE TABLE IF NOT EXISTS AssetCredentials (
  Id INT AUTO_INCREMENT PRIMARY KEY,
  AssetId INT NOT NULL COMMENT '关联资产ID',
  CredentialType VARCHAR(50) NOT NULL COMMENT '凭证类型：账号/密码/API密钥/License Key等',
  CredentialName VARCHAR(100) COMMENT '凭证名称/标识',
  EncryptedValue TEXT NOT NULL COMMENT '加密后的凭证值',
  Username VARCHAR(200) COMMENT '关联的用户名（如果有）',
  Description TEXT COMMENT '凭证描述',
  AccessCount INT DEFAULT 0 COMMENT '访问次数',
  LastAccessAt DATETIME COMMENT '最后访问时间',
  LastAccessBy INT COMMENT '最后访问的用户ID',
  CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  FOREIGN KEY (AssetId) REFERENCES Assets(Id) ON DELETE CASCADE,
  FOREIGN KEY (LastAccessBy) REFERENCES Users(Id) ON DELETE SET NULL,
  INDEX idx_asset_id (AssetId),
  INDEX idx_credential_type (CredentialType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='资产凭证表';
