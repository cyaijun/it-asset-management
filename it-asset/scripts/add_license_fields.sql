-- 添加 License 资产相关字段
USE it_asset_db;

-- 为 Assets 表添加是否License和到期时间字段
ALTER TABLE Assets
ADD COLUMN IsLicense TINYINT(1) DEFAULT 0 COMMENT '是否为License资产(0=否,1=是)',
ADD COLUMN LicenseExpiry DATE COMMENT 'License到期时间';

-- 为 License 资产添加索引
CREATE INDEX idx_is_license ON Assets(IsLicense);
CREATE INDEX idx_license_expiry ON Assets(LicenseExpiry);
