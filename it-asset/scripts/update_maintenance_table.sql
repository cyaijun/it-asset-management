-- 更新维修表，添加原状态字段
USE it_asset_db;

ALTER TABLE Maintenance
ADD COLUMN OriginalStatus VARCHAR(50) DEFAULT 'InStock' COMMENT '维修前的原始状态',
ADD COLUMN CompletedAt DATETIME COMMENT '完成时间';
