-- 更新类别表，添加编号规则字段
USE it_asset_db;

ALTER TABLE assetcategories
ADD COLUMN CodeRule VARCHAR(100) COMMENT '编号规则，如: PC-{NUM:4}',
ADD COLUMN NextCode INT DEFAULT 1 COMMENT '下一个编号';

-- 为现有类别添加默认规则
UPDATE assetcategories SET CodeRule = 'CAT{Id}-{NUM:4}' WHERE CodeRule IS NULL;
