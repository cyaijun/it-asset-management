-- 创建资产丢失记录表
CREATE TABLE IF NOT EXISTS LossRecords (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    AssetId INT NOT NULL,
    LossType VARCHAR(50) NOT NULL COMMENT '丢失类型：遗失、被盗、其他',
    Reason TEXT NOT NULL COMMENT '丢失原因',
    Location VARCHAR(255) COMMENT '丢失地点',
    LostAt DATETIME NOT NULL COMMENT '丢失时间',
    OperatorId INT NOT NULL COMMENT '操作人ID',
    Notes TEXT COMMENT '备注说明',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (AssetId) REFERENCES Assets(Id) ON DELETE CASCADE,
    FOREIGN KEY (OperatorId) REFERENCES Users(Id),
    INDEX idx_asset (AssetId),
    INDEX idx_lost_at (LostAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='资产丢失记录表';
