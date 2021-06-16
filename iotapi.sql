-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 15 Haz 2021, 10:15:22
-- Sunucu sürümü: 10.4.8-MariaDB
-- PHP Sürümü: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `iotplatform`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_devices`
--

CREATE TABLE `kr_devices` (
  `deviceID` int(11) UNSIGNED NOT NULL,
  `deviceCode` varchar(75) NOT NULL DEFAULT '0',
  `device_modemID` int(10) NOT NULL DEFAULT 0,
  `deviceName` varchar(255) NOT NULL,
  `deviceDescription` varchar(50) DEFAULT NULL,
  `deviceBrand` varchar(50) DEFAULT NULL,
  `device_groupID` int(11) UNSIGNED NOT NULL,
  `deviceCreated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deviceUpdated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deviceDeleted_at` datetime DEFAULT NULL,
  `deviceStatus` int(1) DEFAULT 1,
  `deviceTags` varchar(255) DEFAULT NULL,
  `device_productID` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'If Possible Product ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Tablo döküm verisi `kr_devices`
--

INSERT INTO `kr_devices` (`deviceID`, `deviceCode`, `device_modemID`, `deviceName`, `deviceDescription`, `deviceBrand`, `device_groupID`, `deviceCreated_at`, `deviceUpdated_at`, `deviceDeleted_at`, `deviceStatus`, `deviceTags`, `device_productID`) VALUES
(8, '191Test8', 19, 'Test', 'Test Cihazı', 'ANTCOM', 1, '2021-06-07 15:49:54', '2021-06-07 15:37:05', NULL, 1, NULL, 1),
(11, '12010112', 9, 'Benim Cihazım', 'Bu cihaz enerji izlemeyi sağlar.', 'Antcom', 1, '2021-06-08 11:03:16', '2021-06-08 12:49:36', NULL, 1, 'analizör, enerji, antcom210', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_device_table`
--

CREATE TABLE `kr_device_table` (
  `tableID` int(11) UNSIGNED NOT NULL,
  `tableName` varchar(255) NOT NULL,
  `tableCode` varchar(255) DEFAULT NULL,
  `table_label` varchar(112) NOT NULL,
  `table_labelID` int(11) NOT NULL,
  `tableType` int(1) DEFAULT 1 COMMENT '1 Read / 2 Write',
  `tableProtocol` varchar(50) DEFAULT 'RS485' COMMENT 'RS485/RS232/WiFi/Bluetoth/IO/I2C/SPI',
  `tableSubProtocol` varchar(50) DEFAULT 'MODBUS' COMMENT 'MODBUS/CANBUS/MQTT',
  `tableAddress` varchar(50) DEFAULT '1' COMMENT 'Protocol Address',
  `tableSubAddress` varchar(50) DEFAULT '123' COMMENT 'Protocol Sub Address',
  `tableDataType` varchar(50) DEFAULT 'INT' COMMENT 'String/ Char / Double / Float / INT32 / INT64 ',
  `table_isFunction` int(1) NOT NULL DEFAULT 0 COMMENT '1 Function / 0 Register',
  `tableFactor` int(11) NOT NULL COMMENT 'Katsayı / Factor Number',
  `tableFactorSymbol` varchar(1) NOT NULL DEFAULT '*' COMMENT '[Çarpım *] [Bölüm /] [Topla +] [Çıkar -] [Modül %] [Üst ^]',
  `tableFunction` text DEFAULT NULL COMMENT 'Function Equation',
  `tableFunctionText` text DEFAULT NULL,
  `table_isIndex` int(1) DEFAULT 1 COMMENT '1 Index / 2 Instant',
  `tableMinValue` int(11) DEFAULT 1,
  `tableMaxValue` int(11) DEFAULT 1,
  `tableUnit` varchar(50) DEFAULT NULL COMMENT 'kWh, Value, A, Watt, Celcius, ',
  `table_deviceID` int(10) UNSIGNED NOT NULL,
  `tablePeriod` int(11) DEFAULT 30 COMMENT 'Every Seconds',
  `tableDeleteMonth` int(11) DEFAULT 12 COMMENT 'Month Later Delete',
  `table_modemID` int(11) NOT NULL,
  `tableNotes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Tablo döküm verisi `kr_device_table`
--

INSERT INTO `kr_device_table` (`tableID`, `tableName`, `tableCode`, `table_label`, `table_labelID`, `tableType`, `tableProtocol`, `tableSubProtocol`, `tableAddress`, `tableSubAddress`, `tableDataType`, `table_isFunction`, `tableFactor`, `tableFactorSymbol`, `tableFunction`, `tableFunctionText`, `table_isIndex`, `tableMinValue`, `tableMaxValue`, `tableUnit`, `table_deviceID`, `tablePeriod`, `tableDeleteMonth`, `table_modemID`, `tableNotes`) VALUES
(10, 'Aktif', '123123sad', 'Test12', 2, 1, 'RS485', 'MODBUS', '1', '123', 'INT', 0, 0, '*', NULL, NULL, 1, 1, 1, 'kWh', 11, 30, 12, 1, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_groups`
--

CREATE TABLE `kr_groups` (
  `groupID` int(11) UNSIGNED NOT NULL,
  `groupName` varchar(155) NOT NULL,
  `groupDescription` varchar(255) DEFAULT NULL,
  `groupNote` text DEFAULT NULL,
  `groupOwnerID` int(11) NOT NULL DEFAULT 0,
  `groupCreated_at` datetime NOT NULL,
  `groupUpdated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `groupDeleted_at` datetime DEFAULT NULL,
  `groupStatus` int(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `kr_groups`
--

INSERT INTO `kr_groups` (`groupID`, `groupName`, `groupDescription`, `groupNote`, `groupOwnerID`, `groupCreated_at`, `groupUpdated_at`, `groupDeleted_at`, `groupStatus`) VALUES
(1, 'Genel Grup', NULL, NULL, 0, '2021-06-03 11:58:49', '2021-06-03 12:40:20', NULL, 1),
(2, 'Antcom Enerji', 'Antcom Enerji Grubu', NULL, 0, '2021-06-03 11:58:49', '2021-06-03 12:40:19', NULL, 1),
(5, 'Fark Endüstri', 'Fark Firmalar', '', 0, '2021-06-03 12:06:06', '2021-06-03 10:16:49', '2021-06-03 13:16:49', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_group_has_user`
--

CREATE TABLE `kr_group_has_user` (
  `group_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `kr_group_has_user`
--

INSERT INTO `kr_group_has_user` (`group_id`, `user_id`) VALUES
(1, 1),
(1, 2);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_labels`
--

CREATE TABLE `kr_labels` (
  `labelID` int(11) NOT NULL,
  `labelName` varchar(112) NOT NULL,
  `labelCode` varchar(112) NOT NULL,
  `labelUnit` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `kr_labels`
--

INSERT INTO `kr_labels` (`labelID`, `labelName`, `labelCode`, `labelUnit`) VALUES
(1, 'NULL', 'null', 'NULL'),
(2, 'Tüketim', 'tuketim', 'kWh');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_modems`
--

CREATE TABLE `kr_modems` (
  `modemID` int(11) NOT NULL,
  `modemCode` varchar(255) NOT NULL,
  `modemName` varchar(255) DEFAULT NULL,
  `modemStatus` varchar(255) DEFAULT '1',
  `modem_groupID` int(11) UNSIGNED NOT NULL DEFAULT 1,
  `modemLat` varchar(50) NOT NULL DEFAULT '',
  `modemLong` varchar(50) NOT NULL DEFAULT '',
  `modemCreated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `modemUpdated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modemDeleted_at` datetime DEFAULT NULL,
  `modemHost` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `kr_modems`
--

INSERT INTO `kr_modems` (`modemID`, `modemCode`, `modemName`, `modemStatus`, `modem_groupID`, `modemLat`, `modemLong`, `modemCreated_at`, `modemUpdated_at`, `modemDeleted_at`, `modemHost`) VALUES
(4, '12020011', 'Yeni Modem', '1', 1, '28.123', '21.454', '2021-06-04 11:23:05', '2021-06-07 07:18:38', NULL, NULL),
(5, '12123', 'Modem2', '1', 5, '', '', '2021-06-04 12:06:09', '2021-06-04 15:16:14', NULL, NULL),
(6, '123123', 'Modem3', '1', 5, '', '', '2021-06-04 13:11:12', '2021-06-04 15:16:16', NULL, NULL),
(7, '1231232', 'Modem4', '1', 5, '', '', '2021-06-04 13:11:21', '2021-06-04 15:16:18', NULL, NULL),
(8, 'asdasd', 'Modem5', '1', 5, '', '', '2021-06-04 13:15:46', '2021-06-04 15:16:20', NULL, NULL),
(9, '120120012', 'Yeni Modem', '1', 1, '28.123', '21.454', '2021-06-04 15:51:07', '2021-06-04 12:51:07', NULL, NULL),
(16, '12012s0012', 'Yeni Modem', '1', 1, '28.123', '21.454', '2021-06-04 15:57:46', '2021-06-04 12:57:46', NULL, NULL),
(17, 'z', 'Yeni Modem', '1', 1, '28.123', '21.454', '2021-06-04 15:59:14', '2021-06-04 12:59:14', NULL, NULL),
(19, '12010012', 'Yeni Modem', '1', 1, '28.123', '21.454', '2021-06-04 15:59:42', '2021-06-04 12:59:42', NULL, NULL),
(21, 'DEL-120200148', 'Yeni Modem', '0', 1, '28.1123', '21.454', '2021-06-07 09:55:54', '2021-06-07 09:39:42', '2021-06-07 12:39:42', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_permission`
--

CREATE TABLE `kr_permission` (
  `permissionID` int(11) UNSIGNED NOT NULL,
  `permissionName` varchar(50) DEFAULT NULL,
  `permissionDescription` varchar(50) DEFAULT NULL,
  `permissionCreated_at` timestamp NULL DEFAULT current_timestamp(),
  `permissionUpdate_at` timestamp NULL DEFAULT current_timestamp(),
  `permissionDeleted_at` timestamp NULL DEFAULT NULL,
  `permissionNotes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `kr_permission`
--

INSERT INTO `kr_permission` (`permissionID`, `permissionName`, `permissionDescription`, `permissionCreated_at`, `permissionUpdate_at`, `permissionDeleted_at`, `permissionNotes`) VALUES
(1, 'all.permissions', 'Tüm yetkilere sahip kullanıcıdır.', '2021-05-24 13:27:43', '2021-05-24 13:27:41', NULL, NULL),
(2, 'users.index', 'Kullanıcıları listeleme', '2021-05-25 10:07:31', '2021-05-25 10:07:32', NULL, NULL),
(3, 'users.show', 'Kullanıcı bilgilerini görüntüleme', '2021-05-28 07:30:41', '2021-05-28 07:30:42', NULL, NULL),
(4, 'users.delete', 'Kullanıcıyı silme', '2021-05-28 07:30:56', '2021-05-28 07:30:57', NULL, NULL),
(5, 'users.update', 'Kullanıcı bilgilerini güncelleme', '2021-05-28 08:31:05', '2021-05-28 08:31:06', NULL, NULL),
(6, 'users.add', 'Yeni kullanıcı ekleme', '2021-05-28 08:31:21', '2021-05-28 08:31:22', NULL, NULL),
(7, 'groups.index', 'Grup Gruplarını Listeleme', '2021-06-01 13:50:03', '2021-06-01 13:50:04', NULL, NULL),
(8, 'groups.add', 'Kullanıcı Grubu Oluştur', '2021-06-03 08:53:47', '2021-06-03 08:53:48', NULL, NULL),
(9, 'groups.show', 'Grup Detaylarını Görüntüleme', '2021-06-03 09:16:20', '2021-06-03 09:16:21', NULL, NULL),
(10, 'groups.update', 'Grup Bilgilerini Güncelleme', '2021-06-03 09:20:08', '2021-06-03 09:21:50', NULL, NULL),
(11, 'groups.delete', 'Grubu Silme', '2021-06-03 09:54:31', '2021-06-03 09:54:33', NULL, NULL),
(12, 'modems.index', 'Modemleri Listeleme', '2021-06-04 08:09:50', '2021-06-04 08:09:51', NULL, NULL),
(13, 'modems.update', 'Modem Bilgilerini Güncelleme', '2021-06-07 07:00:37', '2021-06-07 07:00:38', NULL, NULL),
(14, 'modems.delete', 'Modem Silme', '2021-06-07 12:42:25', '2021-06-07 12:42:09', NULL, NULL),
(15, 'modems.add', 'Yeni Modem Ekleme', '2021-06-07 12:42:20', '2021-06-07 12:42:21', NULL, NULL),
(16, 'devices.index', 'Cihazları Listeleme', '2021-06-07 12:42:59', '2021-06-07 12:42:59', NULL, NULL),
(17, 'devices.add', 'Cihaz Ekleme', '2021-06-07 16:09:43', '2021-06-07 16:09:43', NULL, NULL),
(18, 'devices.update', 'Cihaz Güncelleme', '2021-06-08 08:07:06', '2021-06-08 08:07:07', NULL, NULL),
(19, 'devices.delete', 'Cihaz Silme', '2021-06-08 10:18:54', '2021-06-08 10:18:54', NULL, NULL),
(20, 'products.index', 'Ürünleri Listeleme', '2021-06-08 13:32:07', '2021-06-08 13:32:08', NULL, NULL),
(21, 'products.add', 'Ürün Ekleme', '2021-06-08 13:32:27', '2021-06-08 13:32:27', NULL, NULL),
(22, 'products.update', 'Ürün Güncelleme', '2021-06-08 13:32:35', '2021-06-08 13:32:35', NULL, NULL),
(23, 'products.delete', 'Ürün Silme', '2021-06-08 13:32:44', '2021-06-08 13:32:44', NULL, NULL),
(24, 'products.groups.index', 'Ürün Grubu Listeleme', '2021-06-09 11:20:02', '2021-06-09 11:20:02', NULL, NULL),
(25, 'products.groups.add', 'Ürün Grubu Ekleme', '2021-06-09 11:20:59', '2021-06-09 11:21:00', NULL, NULL),
(26, 'products.groups.update', 'Ürün Grubu Güncelleme', '2021-06-09 11:44:19', '2021-06-09 11:44:20', NULL, NULL),
(27, 'products.groups.delete', 'Ürün Grubu Silme', '2021-06-09 12:30:52', '2021-06-09 12:30:53', NULL, NULL),
(28, 'labels.index', 'Ürün Etiketleri Listeleme', '2021-06-09 14:42:51', '2021-06-09 14:42:52', NULL, NULL),
(29, 'labels.add', 'Ürün Etiketi Ekleme', '2021-06-09 14:43:07', '2021-06-09 14:43:10', NULL, NULL),
(30, 'labels.update', 'Ürün Etiketi Güncelleme', '2021-06-09 14:43:21', '2021-06-09 14:43:22', NULL, NULL),
(31, 'labels.delete', 'Ürün Etiketi Silme', '2021-06-09 14:43:37', '2021-06-09 14:43:37', NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_products`
--

CREATE TABLE `kr_products` (
  `productID` int(11) UNSIGNED NOT NULL,
  `productName` varchar(255) NOT NULL,
  `productDescription` varchar(50) DEFAULT NULL,
  `productBrand` varchar(50) DEFAULT NULL,
  `product_groupID` int(11) UNSIGNED NOT NULL,
  `productCreated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `productUpdated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `productDeleted_at` datetime DEFAULT NULL,
  `productStatus` int(1) DEFAULT 1,
  `productTags` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `kr_products`
--

INSERT INTO `kr_products` (`productID`, `productName`, `productDescription`, `productBrand`, `product_groupID`, `productCreated_at`, `productUpdated_at`, `productDeleted_at`, `productStatus`, `productTags`) VALUES
(1, 'IoT Cihazı', 'IoT Test Cihazı', 'ANTCOM', 1, '2021-06-07 18:35:38', '2021-06-07 15:36:09', NULL, 1, 'TEST'),
(2, 'Benim Cihazım', '0', 'Antcom', 1, '2021-06-08 17:34:16', '2021-06-08 15:03:50', '2021-06-08 18:03:50', 0, 'analizör, enerji, antcom210'),
(3, 'Benim Cihazım 2', '0', 'Antcom', 1, '2021-06-08 17:58:35', '2021-06-08 14:58:43', NULL, 1, 'analizör, enerji, antcom210');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_product_groups`
--

CREATE TABLE `kr_product_groups` (
  `groupID` int(11) UNSIGNED NOT NULL,
  `groupName` varchar(255) DEFAULT NULL,
  `groupStatus` int(1) DEFAULT NULL,
  `group_ownerID` int(11) UNSIGNED DEFAULT 0,
  `groupCreated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `groupUpdated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `groupDeleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `kr_product_groups`
--

INSERT INTO `kr_product_groups` (`groupID`, `groupName`, `groupStatus`, `group_ownerID`, `groupCreated_at`, `groupUpdated_at`, `groupDeleted_at`) VALUES
(1, 'IoT', 1, 0, '2021-06-09 15:34:03', '2021-06-09 12:34:16', NULL),
(6, 'Kompanyazson', 6, 0, '2021-06-09 15:34:00', '2021-06-09 12:36:20', '2021-06-09 15:36:20'),
(7, 'Enerji Analizörü', 1, 6, '2021-06-09 15:34:00', '2021-06-09 12:34:31', NULL),
(8, '100A Enerji Analizör', 1, 7, '2021-06-09 15:34:00', '2021-06-09 12:34:39', NULL),
(9, '25A Enerji Analizörü', 1, 7, '2021-06-09 15:34:00', '2021-06-09 12:34:44', NULL),
(10, 'Reaktif Rölem', 1, 0, '2021-06-09 15:34:00', '2021-06-09 12:34:51', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_product_table`
--

CREATE TABLE `kr_product_table` (
  `tableID` int(11) UNSIGNED NOT NULL,
  `tableName` varchar(255) NOT NULL,
  `table_label` varchar(112) NOT NULL,
  `table_labelID` int(11) NOT NULL,
  `tableType` int(1) DEFAULT 1 COMMENT '1 Read / 2 Write',
  `tableProtocol` varchar(50) DEFAULT 'RS485' COMMENT 'RS485/RS232/WiFi/Bluetoth/IO/I2C/SPI',
  `tableSubProtocol` varchar(50) DEFAULT 'MODBUS' COMMENT 'MODBUS/CANBUS/MQTT',
  `tableAddress` varchar(50) DEFAULT '1' COMMENT 'Protocol Address',
  `tableSubAddress` varchar(50) DEFAULT '123' COMMENT 'Protocol Sub Address',
  `tableDataType` varchar(50) DEFAULT 'INT' COMMENT 'String/ Char / Double / Float / INT32 / INT64 ',
  `table_isFunction` int(1) NOT NULL DEFAULT 0 COMMENT '1 Function / 0 Register',
  `tableFactor` int(11) NOT NULL COMMENT 'Katsayı / Factor Number',
  `tableFactorSymbol` varchar(50) NOT NULL DEFAULT '*' COMMENT '[Çarpım *] [Bölüm /] [Topla +] [Çıkar -] [Modül %] [Üst ^]',
  `tableFunction` text DEFAULT NULL COMMENT 'Function Equation',
  `tableFunctionText` text DEFAULT NULL,
  `table_isIndex` int(1) DEFAULT 1 COMMENT '1 Index / 2 Instant',
  `tableUnit` varchar(50) DEFAULT NULL COMMENT 'kWh, Value, A, Watt, Celcius, ',
  `tableMinValue` int(11) DEFAULT NULL,
  `tableMaxValue` int(11) DEFAULT NULL,
  `table_productID` int(10) UNSIGNED NOT NULL,
  `tablePeriod` int(11) DEFAULT 30 COMMENT 'Every Seconds',
  `tableDeleteMonth` int(11) DEFAULT 12 COMMENT 'Month Later Delete',
  `tableNotes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Tablo döküm verisi `kr_product_table`
--

INSERT INTO `kr_product_table` (`tableID`, `tableName`, `table_label`, `table_labelID`, `tableType`, `tableProtocol`, `tableSubProtocol`, `tableAddress`, `tableSubAddress`, `tableDataType`, `table_isFunction`, `tableFactor`, `tableFactorSymbol`, `tableFunction`, `tableFunctionText`, `table_isIndex`, `tableUnit`, `tableMinValue`, `tableMaxValue`, `table_productID`, `tablePeriod`, `tableDeleteMonth`, `tableNotes`) VALUES
(3, 'Test', 'NULL', 1, 1, 'RS485', 'MODBUS', '1', '123', 'INT', 0, 1, '*', NULL, NULL, 1, 'kWh', 0, 99999999, 1, 30, 12, NULL),
(5, 'gerilim', 'NULL', 1, 1, 'RS485', 'Modbus', '1', '200', 'INT32', 0, 1, '*', '', '', 1, 'kWh', 0, 350, 3, 60, 12, '{\"baudrate\":\"9600\"}');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_role_has_permission`
--

CREATE TABLE `kr_role_has_permission` (
  `role_id` int(11) UNSIGNED NOT NULL,
  `permission_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `kr_role_has_permission`
--

INSERT INTO `kr_role_has_permission` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 3),
(1, 4),
(1, 7);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_users`
--

CREATE TABLE `kr_users` (
  `userID` int(11) UNSIGNED NOT NULL,
  `userName` varchar(50) NOT NULL,
  `userSurname` varchar(50) NOT NULL,
  `userEmail` varchar(50) NOT NULL,
  `userPassword` varchar(255) NOT NULL,
  `userPhone` varchar(15) NOT NULL,
  `userStatus` int(1) NOT NULL DEFAULT 1,
  `user_created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_delete_at` datetime DEFAULT NULL,
  `user_roleID` int(11) DEFAULT NULL,
  `user_parentID` int(11) DEFAULT NULL,
  `userEmailNotify` int(1) DEFAULT 1 COMMENT 'If Email Notify Active Set 1 else 0',
  `userSMSNotify` int(1) DEFAULT 1 COMMENT 'If SMS Notify Active Set 1 else 0',
  `userLastLogin` datetime DEFAULT NULL,
  `userNotes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='User Details';

--
-- Tablo döküm verisi `kr_users`
--

INSERT INTO `kr_users` (`userID`, `userName`, `userSurname`, `userEmail`, `userPassword`, `userPhone`, `userStatus`, `user_created_at`, `user_update_at`, `user_delete_at`, `user_roleID`, `user_parentID`, `userEmailNotify`, `userSMSNotify`, `userLastLogin`, `userNotes`) VALUES
(1, 'Kürşad', 'Altan', 'iletisim@kursadaltan.com', '$2y$10$CCIAUo6EPty/V9Zsytb7oOw0ARpeHd7a4kHvwX96MOW0du7iWcRZ6', '905393088779', 1, '2021-05-28 08:48:04', '2021-05-28 08:48:04', NULL, 1, 0, 1, 1, NULL, NULL),
(2, 'Kürşad', 'Altan', 'kursadaltan@antom.com.tr', '$2y$10$i9u8m7EwIpiiVMwR3fhuR.HG4LNizF.sIMHMgpX/4ug/BhxXrHgNy', '905393088779', 1, '2021-05-31 09:27:32', '2021-05-31 09:27:32', '2021-05-31 10:55:20', NULL, NULL, 1, 1, NULL, 'User deleted from Kürşad(1)'),
(6, 'Kürşad', 'Altan', 'kursad@farkendustri.com.tr', '$2y$10$xuD3SmK3UIrboXBEuJra7O/Dtrg5L/TfkohLyE0cm77W4w6MlZV0e', '905393088779', 1, '2021-06-01 12:20:26', '2021-06-01 12:20:26', NULL, NULL, NULL, 1, 1, NULL, NULL),
(7, 'Kürşad', 'Altan', 'kursad@antcom.com.tr', '$2y$10$gGRWiIuNS54DgIhEpEYybePepi41kq8rwyls/HrR48WYUdJC7D9IK', '905393088779', 1, '2021-06-04 08:03:07', '2021-06-04 08:03:07', NULL, NULL, NULL, 1, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kr_user_roles`
--

CREATE TABLE `kr_user_roles` (
  `roleID` int(11) UNSIGNED NOT NULL,
  `roleName` varchar(255) DEFAULT NULL,
  `roleCreated_at` timestamp NULL DEFAULT NULL,
  `roleUpdated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `kr_user_roles`
--

INSERT INTO `kr_user_roles` (`roleID`, `roleName`, `roleCreated_at`, `roleUpdated_at`) VALUES
(1, 'Admin', NULL, '2021-05-24 13:26:50');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `kr_devices`
--
ALTER TABLE `kr_devices`
  ADD PRIMARY KEY (`deviceID`) USING BTREE,
  ADD UNIQUE KEY `deviceCode` (`deviceCode`),
  ADD KEY `groupId` (`device_groupID`) USING BTREE,
  ADD KEY `device_product` (`device_productID`),
  ADD KEY `device_modem` (`device_modemID`);

--
-- Tablo için indeksler `kr_device_table`
--
ALTER TABLE `kr_device_table`
  ADD PRIMARY KEY (`tableID`) USING BTREE,
  ADD UNIQUE KEY `tableCode` (`tableCode`),
  ADD KEY `labelName` (`table_label`) USING BTREE,
  ADD KEY `labelID` (`table_labelID`) USING BTREE,
  ADD KEY `product` (`table_deviceID`) USING BTREE,
  ADD KEY `table_modem` (`table_modemID`);

--
-- Tablo için indeksler `kr_groups`
--
ALTER TABLE `kr_groups`
  ADD PRIMARY KEY (`groupID`),
  ADD KEY `groupOwnerID` (`groupOwnerID`);

--
-- Tablo için indeksler `kr_group_has_user`
--
ALTER TABLE `kr_group_has_user`
  ADD PRIMARY KEY (`group_id`,`user_id`),
  ADD KEY `user_id_from_users` (`user_id`);

--
-- Tablo için indeksler `kr_labels`
--
ALTER TABLE `kr_labels`
  ADD PRIMARY KEY (`labelID`) USING BTREE,
  ADD UNIQUE KEY `tagName` (`labelName`) USING BTREE,
  ADD UNIQUE KEY `labelCode` (`labelCode`) USING BTREE;

--
-- Tablo için indeksler `kr_modems`
--
ALTER TABLE `kr_modems`
  ADD PRIMARY KEY (`modemID`),
  ADD UNIQUE KEY `modemCode` (`modemCode`),
  ADD KEY `modem_groupID` (`modem_groupID`),
  ADD KEY `keyModemCode` (`modemCode`);

--
-- Tablo için indeksler `kr_permission`
--
ALTER TABLE `kr_permission`
  ADD PRIMARY KEY (`permissionID`);

--
-- Tablo için indeksler `kr_products`
--
ALTER TABLE `kr_products`
  ADD PRIMARY KEY (`productID`),
  ADD KEY `groupId` (`product_groupID`);

--
-- Tablo için indeksler `kr_product_groups`
--
ALTER TABLE `kr_product_groups`
  ADD PRIMARY KEY (`groupID`) USING BTREE,
  ADD UNIQUE KEY `groupName` (`groupName`),
  ADD KEY `upID` (`group_ownerID`),
  ADD KEY `groupStatus` (`groupStatus`);

--
-- Tablo için indeksler `kr_product_table`
--
ALTER TABLE `kr_product_table`
  ADD PRIMARY KEY (`tableID`) USING BTREE,
  ADD KEY `labelName` (`table_label`) USING BTREE,
  ADD KEY `labelID` (`table_labelID`) USING BTREE,
  ADD KEY `product` (`table_productID`) USING BTREE;

--
-- Tablo için indeksler `kr_role_has_permission`
--
ALTER TABLE `kr_role_has_permission`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `roleKey` (`role_id`),
  ADD KEY `permission_id_from_permission_table` (`permission_id`);

--
-- Tablo için indeksler `kr_users`
--
ALTER TABLE `kr_users`
  ADD PRIMARY KEY (`userID`),
  ADD KEY `userEmail` (`userEmail`);

--
-- Tablo için indeksler `kr_user_roles`
--
ALTER TABLE `kr_user_roles`
  ADD PRIMARY KEY (`roleID`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `kr_devices`
--
ALTER TABLE `kr_devices`
  MODIFY `deviceID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `kr_device_table`
--
ALTER TABLE `kr_device_table`
  MODIFY `tableID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `kr_groups`
--
ALTER TABLE `kr_groups`
  MODIFY `groupID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `kr_labels`
--
ALTER TABLE `kr_labels`
  MODIFY `labelID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `kr_modems`
--
ALTER TABLE `kr_modems`
  MODIFY `modemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Tablo için AUTO_INCREMENT değeri `kr_permission`
--
ALTER TABLE `kr_permission`
  MODIFY `permissionID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Tablo için AUTO_INCREMENT değeri `kr_products`
--
ALTER TABLE `kr_products`
  MODIFY `productID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `kr_product_groups`
--
ALTER TABLE `kr_product_groups`
  MODIFY `groupID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `kr_product_table`
--
ALTER TABLE `kr_product_table`
  MODIFY `tableID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `kr_users`
--
ALTER TABLE `kr_users`
  MODIFY `userID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `kr_user_roles`
--
ALTER TABLE `kr_user_roles`
  MODIFY `roleID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `kr_devices`
--
ALTER TABLE `kr_devices`
  ADD CONSTRAINT `device_group` FOREIGN KEY (`device_groupID`) REFERENCES `kr_product_groups` (`groupID`),
  ADD CONSTRAINT `device_modem` FOREIGN KEY (`device_modemID`) REFERENCES `kr_modems` (`modemID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `device_product` FOREIGN KEY (`device_productID`) REFERENCES `kr_products` (`productID`);

--
-- Tablo kısıtlamaları `kr_device_table`
--
ALTER TABLE `kr_device_table`
  ADD CONSTRAINT `device_table_label_id` FOREIGN KEY (`table_labelID`) REFERENCES `kr_labels` (`labelID`),
  ADD CONSTRAINT `table_device` FOREIGN KEY (`table_deviceID`) REFERENCES `kr_devices` (`deviceID`);

--
-- Tablo kısıtlamaları `kr_group_has_user`
--
ALTER TABLE `kr_group_has_user`
  ADD CONSTRAINT `group_id_from_groups` FOREIGN KEY (`group_id`) REFERENCES `kr_groups` (`groupID`),
  ADD CONSTRAINT `user_id_from_users` FOREIGN KEY (`user_id`) REFERENCES `kr_users` (`userID`);

--
-- Tablo kısıtlamaları `kr_modems`
--
ALTER TABLE `kr_modems`
  ADD CONSTRAINT `modem_group` FOREIGN KEY (`modem_groupID`) REFERENCES `kr_groups` (`groupID`);

--
-- Tablo kısıtlamaları `kr_products`
--
ALTER TABLE `kr_products`
  ADD CONSTRAINT `groupId` FOREIGN KEY (`product_groupID`) REFERENCES `kr_product_groups` (`groupID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Tablo kısıtlamaları `kr_product_table`
--
ALTER TABLE `kr_product_table`
  ADD CONSTRAINT `labelID` FOREIGN KEY (`table_labelID`) REFERENCES `kr_labels` (`labelID`),
  ADD CONSTRAINT `table_product` FOREIGN KEY (`table_productID`) REFERENCES `kr_products` (`productID`);

--
-- Tablo kısıtlamaları `kr_role_has_permission`
--
ALTER TABLE `kr_role_has_permission`
  ADD CONSTRAINT `permission_id_from_permission_table` FOREIGN KEY (`permission_id`) REFERENCES `kr_permission` (`permissionID`),
  ADD CONSTRAINT `role_id_from_role_table` FOREIGN KEY (`role_id`) REFERENCES `kr_user_roles` (`roleID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
