-- Create database and tables for IT Asset Management
CREATE DATABASE IF NOT EXISTS it_asset_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_asset_db;

CREATE TABLE IF NOT EXISTS `Users` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `Username` VARCHAR(100) NOT NULL UNIQUE,
  `FullName` VARCHAR(200),
  `Email` VARCHAR(200),
  `Department` VARCHAR(200),
  `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `Assets` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `Tag` VARCHAR(100) NOT NULL UNIQUE,
  `Name` VARCHAR(200) NOT NULL,
  `Category` VARCHAR(100),
  `Model` VARCHAR(100),
  `SerialNumber` VARCHAR(200),
  `Status` VARCHAR(50) DEFAULT 'InStock',
  `Location` VARCHAR(200),
  `Supplier` VARCHAR(200),
  `PurchaseDate` DATE,
  `OwnerId` INT,
  `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`OwnerId`) REFERENCES `Users`(`Id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `AssetTransactions` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `AssetId` INT NOT NULL,
  `UserId` INT,
  `TransactionType` VARCHAR(50) NOT NULL,
  `Notes` TEXT,
  `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`AssetId`) REFERENCES `Assets`(`Id`) ON DELETE CASCADE,
  FOREIGN KEY (`UserId`) REFERENCES `Users`(`Id`) ON DELETE SET NULL
);
