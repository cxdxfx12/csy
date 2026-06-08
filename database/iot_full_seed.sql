-- ============================================
-- IoT 设备实例 + 实时数据 SQL (自动生成)
-- ============================================

DELETE FROM `ds_iot_device_data`;
DELETE FROM `ds_iot_device`;

INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 9, '[feicui] smoke #1', 'DEV_FEICUI_SMOKE_001', -20, 2.8, 18, 'J栋 31层', 31, 'J栋', '2024-06-10', NOW(), 'v3.7.8', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 6, '[feicui] smoke #2', 'DEV_FEICUI_SMOKE_002', 0, 2.8, 22, 'D栋 23层', 23, 'D栋', '2024-08-24', NOW(), 'v3.4.8', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 7, '[feicui] smoke #3', 'DEV_FEICUI_SMOKE_003', 18, 2.8, 16, 'C栋 22层', 22, 'C栋', '2024-02-21', NOW(), 'v3.4.2', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 9, '[feicui] smoke #4', 'DEV_FEICUI_SMOKE_004', -18, 2.8, 0, 'U栋 20层', 20, 'U栋', '2024-03-26', NOW(), 'v2.9.0', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 7, '[feicui] smoke #5', 'DEV_FEICUI_SMOKE_005', 0, 2.8, 4, 'N栋 25层', 25, 'N栋', '2024-04-21', NOW(), 'v3.2.6', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 7, '[feicui] smoke #6', 'DEV_FEICUI_SMOKE_006', 20, 2.8, -2, 'J栋 28层', 28, 'J栋', '2024-05-28', NOW(), 'v3.1.3', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 6, '[feicui] smoke #7', 'DEV_FEICUI_SMOKE_007', -22, 2.8, -20, 'J栋 2层', 2, 'J栋', '2024-04-19', NOW(), 'v1.3.5', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 6, '[feicui] smoke #8', 'DEV_FEICUI_SMOKE_008', -2, 2.8, -24, 'H栋 26层', 26, 'H栋', '2024-08-16', NOW(), 'v1.2.9', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 7, '[feicui] smoke #9', 'DEV_FEICUI_SMOKE_009', 22, 2.8, -18, 'B栋 12层', 12, 'B栋', '2024-03-27', NOW(), 'v2.9.3', 92, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 7, '[feicui] smoke #10', 'DEV_FEICUI_SMOKE_010', -10, 2.8, 10, 'K栋 32层', 32, 'K栋', '2024-06-13', NOW(), 'v1.2.3', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 9, '[feicui] smoke #11', 'DEV_FEICUI_SMOKE_011', 8, 2.8, 14, 'Y栋 29层', 29, 'Y栋', '2024-01-27', NOW(), 'v2.3.1', 91, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 9, '[feicui] smoke #12', 'DEV_FEICUI_SMOKE_012', -8, 2.8, -10, 'F栋 23层', 23, 'F栋', '2024-01-27', NOW(), 'v1.4.6', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 7, '[feicui] smoke #13', 'DEV_FEICUI_SMOKE_013', 10, 2.8, -14, 'J栋 16层', 16, 'J栋', '2024-07-15', NOW(), 'v2.5.3', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 6, '[feicui] smoke #14', 'DEV_FEICUI_SMOKE_014', 24, 2.8, 12, 'N栋 22层', 22, 'N栋', '2024-09-20', NOW(), 'v1.7.0', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 4, '[feicui] access #1', 'DEV_FEICUI_ACCESS_001', -41, 1.2, 0, 'T栋 32层', 32, 'T栋', '2024-08-12', NOW(), 'v3.4.0', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 12, '[feicui] access #2', 'DEV_FEICUI_ACCESS_002', -41, 1.2, 10, 'L栋 16层', 16, 'L栋', '2024-03-21', NOW(), 'v2.7.6', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 3, '[feicui] access #3', 'DEV_FEICUI_ACCESS_003', -41, 1.2, -10, 'D栋 27层', 27, 'D栋', '2024-07-14', NOW(), 'v3.7.1', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 3, '[feicui] access #4', 'DEV_FEICUI_ACCESS_004', 0, 1.2, 41, 'U栋 2层', 2, 'U栋', '2024-06-20', NOW(), 'v3.2.8', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 3, '[feicui] access #5', 'DEV_FEICUI_ACCESS_005', 15, 1.2, 41, 'O栋 27层', 27, 'O栋', '2024-02-27', NOW(), 'v3.4.6', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 12, '[feicui] access #6', 'DEV_FEICUI_ACCESS_006', -15, 1.2, 41, 'M栋 13层', 13, 'M栋', '2024-09-14', NOW(), 'v1.6.4', 74, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 3, '[feicui] access #7', 'DEV_FEICUI_ACCESS_007', 41, 1.2, 0, 'D栋 1层', 1, 'D栋', '2024-02-10', NOW(), 'v2.3.3', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 11, '[feicui] access #8', 'DEV_FEICUI_ACCESS_008', 41, 1.2, -10, 'H栋 8层', 8, 'H栋', '2024-04-15', NOW(), 'v2.4.0', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 11, '[feicui] access #9', 'DEV_FEICUI_ACCESS_009', 0, 1.2, -41, 'W栋 8层', 8, 'W栋', '2024-02-20', NOW(), 'v3.7.3', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 1, '[feicui] meter_w #1', 'DEV_FEICUI_METER_W_001', -18, 0.8, 18, 'T栋 22层', 22, 'T栋', '2024-04-25', NOW(), 'v3.9.7', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 7, '[feicui] meter_w #2', 'DEV_FEICUI_METER_W_002', 0, 0.8, 22, 'U栋 16层', 16, 'U栋', '2024-04-11', NOW(), 'v3.0.5', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 1, '[feicui] meter_w #3', 'DEV_FEICUI_METER_W_003', 18, 0.8, 16, 'P栋 5层', 5, 'P栋', '2024-06-20', NOW(), 'v2.5.1', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 7, '[feicui] meter_w #4', 'DEV_FEICUI_METER_W_004', -18, 0.8, 0, 'L栋 4层', 4, 'L栋', '2024-05-27', NOW(), 'v1.7.0', 87, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 4, '[feicui] meter_w #5', 'DEV_FEICUI_METER_W_005', 0, 0.8, 4, 'S栋 10层', 10, 'S栋', '2024-08-27', NOW(), 'v1.6.1', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 4, '[feicui] meter_w #6', 'DEV_FEICUI_METER_W_006', 20, 0.8, -2, 'R栋 19层', 19, 'R栋', '2024-01-28', NOW(), 'v3.8.7', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 4, '[feicui] meter_w #7', 'DEV_FEICUI_METER_W_007', -22, 0.8, -20, 'E栋 17层', 17, 'E栋', '2024-05-26', NOW(), 'v2.8.7', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 7, '[feicui] meter_w #8', 'DEV_FEICUI_METER_W_008', -2, 0.8, -24, 'W栋 31层', 31, 'W栋', '2024-01-10', NOW(), 'v3.7.6', 87, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 7, '[feicui] meter_w #9', 'DEV_FEICUI_METER_W_009', 22, 0.8, -18, 'T栋 2层', 2, 'T栋', '2024-02-23', NOW(), 'v3.1.1', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 7, '[feicui] meter_e #1', 'DEV_FEICUI_METER_E_001', -18, 1.6, 18, 'L栋 25层', 25, 'L栋', '2024-03-16', NOW(), 'v2.6.7', 92, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 9, '[feicui] meter_e #2', 'DEV_FEICUI_METER_E_002', 0, 1.6, 22, 'O栋 14层', 14, 'O栋', '2024-06-25', NOW(), 'v2.4.2', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 7, '[feicui] meter_e #3', 'DEV_FEICUI_METER_E_003', 18, 1.6, 16, 'M栋 15层', 15, 'M栋', '2024-05-25', NOW(), 'v2.1.2', 94, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 1, '[feicui] meter_e #4', 'DEV_FEICUI_METER_E_004', -18, 1.6, 0, 'H栋 19层', 19, 'H栋', '2024-01-12', NOW(), 'v1.1.6', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 4, '[feicui] meter_e #5', 'DEV_FEICUI_METER_E_005', 0, 1.6, 4, 'M栋 1层', 1, 'M栋', '2024-07-26', NOW(), 'v2.1.2', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 12, '[feicui] meter_e #6', 'DEV_FEICUI_METER_E_006', 20, 1.6, -2, 'O栋 2层', 2, 'O栋', '2024-04-26', NOW(), 'v2.6.4', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 9, '[feicui] meter_e #7', 'DEV_FEICUI_METER_E_007', -22, 1.6, -20, 'E栋 2层', 2, 'E栋', '2024-02-18', NOW(), 'v2.9.1', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 9, '[feicui] meter_e #8', 'DEV_FEICUI_METER_E_008', -2, 1.6, -24, 'U栋 30层', 30, 'U栋', '2024-03-24', NOW(), 'v3.7.1', 74, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 9, '[feicui] meter_e #9', 'DEV_FEICUI_METER_E_009', 22, 1.6, -18, 'W栋 27层', 27, 'W栋', '2024-08-17', NOW(), 'v3.3.6', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 11, '[feicui] camera #1', 'DEV_FEICUI_CAMERA_001', -42, 4.5, -5, 'B栋 25层', 25, 'B栋', '2024-06-27', NOW(), 'v3.1.4', 89, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 3, '[feicui] camera #2', 'DEV_FEICUI_CAMERA_002', -5, 4.5, -42, 'R栋 31层', 31, 'R栋', '2024-07-28', NOW(), 'v1.0.6', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 3, '[feicui] camera #3', 'DEV_FEICUI_CAMERA_003', 5, 4.5, 43, 'Z栋 7层', 7, 'Z栋', '2024-06-20', NOW(), 'v2.8.8', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 13, '[feicui] camera #4', 'DEV_FEICUI_CAMERA_004', 43, 4.5, 8, 'Z栋 2层', 2, 'Z栋', '2024-03-12', NOW(), 'v1.7.2', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 13, '[feicui] camera #5', 'DEV_FEICUI_CAMERA_005', -42, 4.5, 15, 'Y栋 32层', 32, 'Y栋', '2024-05-24', NOW(), 'v3.4.4', 93, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 11, '[feicui] camera #6', 'DEV_FEICUI_CAMERA_006', 43, 4.5, -15, 'R栋 5层', 5, 'R栋', '2024-01-16', NOW(), 'v2.6.2', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 11, '[feicui] camera #7', 'DEV_FEICUI_CAMERA_007', 0, 4.5, -42, 'V栋 30层', 30, 'V栋', '2024-01-28', NOW(), 'v1.8.2', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 11, '[feicui] camera #8', 'DEV_FEICUI_CAMERA_008', 42, 4.5, 0, 'J栋 12层', 12, 'J栋', '2024-05-13', NOW(), 'v1.7.8', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 7, '[feicui] env #1', 'DEV_FEICUI_ENV_001', -25, 2, 25, 'J栋 3层', 3, 'J栋', '2024-02-26', NOW(), 'v2.2.5', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 4, '[feicui] env #2', 'DEV_FEICUI_ENV_002', 25, 2, 25, 'L栋 19层', 19, 'L栋', '2024-01-16', NOW(), 'v1.9.3', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 4, '[feicui] env #3', 'DEV_FEICUI_ENV_003', 0, 2, 0, 'P栋 26层', 26, 'P栋', '2024-05-15', NOW(), 'v2.0.0', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 6, '[feicui] env #4', 'DEV_FEICUI_ENV_004', 25, 2, -25, 'P栋 21层', 21, 'P栋', '2024-06-19', NOW(), 'v1.5.6', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 6, '[feicui] env #5', 'DEV_FEICUI_ENV_005', -25, 2, -25, 'V栋 18层', 18, 'V栋', '2024-06-18', NOW(), 'v1.8.6', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 9, '[feicui] parking #1', 'DEV_FEICUI_PARKING_001', -35, 0.05, -35, 'C栋 7层', 7, 'C栋', '2024-06-10', NOW(), 'v2.1.6', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 8, '[feicui] parking #2', 'DEV_FEICUI_PARKING_002', -20, 0.05, -35, 'I栋 32层', 32, 'I栋', '2024-01-15', NOW(), 'v1.0.4', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 8, '[feicui] parking #3', 'DEV_FEICUI_PARKING_003', -5, 0.05, -35, 'S栋 11层', 11, 'S栋', '2024-04-26', NOW(), 'v1.4.4', 85, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 8, '[feicui] parking #4', 'DEV_FEICUI_PARKING_004', 10, 0.05, -35, 'E栋 28层', 28, 'E栋', '2024-09-27', NOW(), 'v1.9.3', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 9, '[feicui] parking #5', 'DEV_FEICUI_PARKING_005', 25, 0.05, -35, 'N栋 17层', 17, 'N栋', '2024-07-19', NOW(), 'v2.6.7', 79, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 7, '[feicui] parking #6', 'DEV_FEICUI_PARKING_006', 35, 0.05, -35, 'Q栋 17层', 17, 'Q栋', '2024-03-12', NOW(), 'v1.3.1', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 9, '[feicui] parking #7', 'DEV_FEICUI_PARKING_007', -35, 0.05, 30, 'Y栋 30层', 30, 'Y栋', '2024-04-28', NOW(), 'v3.6.0', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 8, '[feicui] parking #8', 'DEV_FEICUI_PARKING_008', -20, 0.05, 30, 'V栋 13层', 13, 'V栋', '2024-06-16', NOW(), 'v2.3.7', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 7, '[feicui] parking #9', 'DEV_FEICUI_PARKING_009', -5, 0.05, 30, 'A栋 22层', 22, 'A栋', '2024-06-22', NOW(), 'v2.9.1', 74, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 9, '[feicui] parking #10', 'DEV_FEICUI_PARKING_010', 10, 0.05, 30, 'W栋 3层', 3, 'W栋', '2024-06-13', NOW(), 'v2.0.6', 91, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 8, '[feicui] parking #11', 'DEV_FEICUI_PARKING_011', 25, 0.05, 30, 'H栋 12层', 12, 'H栋', '2024-08-21', NOW(), 'v2.4.8', 87, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 8, '[feicui] parking #12', 'DEV_FEICUI_PARKING_012', 35, 0.05, 30, 'U栋 21层', 21, 'U栋', '2024-06-28', NOW(), 'v2.1.9', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 9, '[feicui] streetlight #1', 'DEV_FEICUI_STREETLIGHT_001', -40, 6, -30, 'N栋 26层', 26, 'N栋', '2024-09-24', NOW(), 'v2.5.4', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 6, '[feicui] streetlight #2', 'DEV_FEICUI_STREETLIGHT_002', 0, 6, -30, 'H栋 11层', 11, 'H栋', '2024-03-24', NOW(), 'v1.8.4', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 9, '[feicui] streetlight #3', 'DEV_FEICUI_STREETLIGHT_003', 40, 6, -30, 'A栋 25层', 25, 'A栋', '2024-09-19', NOW(), 'v3.5.7', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 6, '[feicui] streetlight #4', 'DEV_FEICUI_STREETLIGHT_004', -40, 6, 0, 'Z栋 25层', 25, 'Z栋', '2024-01-20', NOW(), 'v2.2.8', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 8, '[feicui] streetlight #5', 'DEV_FEICUI_STREETLIGHT_005', 40, 6, 0, 'I栋 17层', 17, 'I栋', '2024-07-11', NOW(), 'v3.9.8', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 6, '[feicui] streetlight #6', 'DEV_FEICUI_STREETLIGHT_006', -40, 6, 30, 'T栋 25层', 25, 'T栋', '2024-08-11', NOW(), 'v2.1.6', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 8, '[feicui] streetlight #7', 'DEV_FEICUI_STREETLIGHT_007', 0, 6, 30, 'C栋 26层', 26, 'C栋', '2024-09-18', NOW(), 'v3.6.6', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 8, '[feicui] streetlight #8', 'DEV_FEICUI_STREETLIGHT_008', 40, 6, 30, 'Y栋 7层', 7, 'Y栋', '2024-05-24', NOW(), 'v3.5.7', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 7, '[feicui] gas #1', 'DEV_FEICUI_GAS_001', -20, 0.6, 18, 'A栋 27层', 27, 'A栋', '2024-04-18', NOW(), 'v3.0.8', 74, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 9, '[feicui] gas #2', 'DEV_FEICUI_GAS_002', 0, 0.6, 22, 'X栋 25层', 25, 'X栋', '2024-07-27', NOW(), 'v1.4.4', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 6, '[feicui] gas #3', 'DEV_FEICUI_GAS_003', 18, 0.6, 16, 'K栋 23层', 23, 'K栋', '2024-07-22', NOW(), 'v2.6.2', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 6, '[feicui] gas #4', 'DEV_FEICUI_GAS_004', 0, 0.6, 4, 'Z栋 3层', 3, 'Z栋', '2024-01-12', NOW(), 'v3.0.4', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 6, '[feicui] gas #5', 'DEV_FEICUI_GAS_005', 20, 0.6, -2, 'P栋 9层', 9, 'P栋', '2024-07-24', NOW(), 'v3.0.8', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 9, '[feicui] gas #6', 'DEV_FEICUI_GAS_006', -2, 0.6, -24, 'O栋 5层', 5, 'O栋', '2024-08-17', NOW(), 'v1.4.0', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 9, '[feicui] gas #7', 'DEV_FEICUI_GAS_007', 22, 0.6, -18, 'I栋 27层', 27, 'I栋', '2024-02-23', NOW(), 'v3.8.1', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 7, '[feicui] gas #8', 'DEV_FEICUI_GAS_008', -10, 0.6, 10, 'M栋 31层', 31, 'M栋', '2024-02-28', NOW(), 'v2.0.9', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 9, '[feicui] gas #9', 'DEV_FEICUI_GAS_009', 8, 0.6, 14, 'C栋 3层', 3, 'C栋', '2024-09-11', NOW(), 'v3.4.6', 87, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 9, '[feicui] hydrant #1', 'DEV_FEICUI_HYDRANT_001', -41, 0.6, -35, 'J栋 5层', 5, 'J栋', '2024-03-25', NOW(), 'v3.9.9', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 7, '[feicui] hydrant #2', 'DEV_FEICUI_HYDRANT_002', 41, 0.6, -35, 'I栋 4层', 4, 'I栋', '2024-01-10', NOW(), 'v1.1.7', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 9, '[feicui] hydrant #3', 'DEV_FEICUI_HYDRANT_003', -41, 0.6, 25, 'S栋 9层', 9, 'S栋', '2024-09-17', NOW(), 'v1.5.2', 85, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 7, '[feicui] hydrant #4', 'DEV_FEICUI_HYDRANT_004', 41, 0.6, 25, 'O栋 12层', 12, 'O栋', '2024-09-16', NOW(), 'v2.2.8', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 9, '[feicui] hydrant #5', 'DEV_FEICUI_HYDRANT_005', 0, 0.6, -35, 'E栋 8层', 8, 'E栋', '2024-07-22', NOW(), 'v3.8.0', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 7, '[feicui] hydrant #6', 'DEV_FEICUI_HYDRANT_006', 0, 0.6, 25, 'Q栋 6层', 6, 'Q栋', '2024-07-12', NOW(), 'v3.5.4', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 3, '[feicui] elevator #1', 'DEV_FEICUI_ELEVATOR_001', -20, 3, 18, 'Y栋 22层', 22, 'Y栋', '2024-09-19', NOW(), 'v1.1.2', 91, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 1, '[feicui] elevator #2', 'DEV_FEICUI_ELEVATOR_002', 0, 3, 22, 'C栋 16层', 16, 'C栋', '2024-02-24', NOW(), 'v1.3.9', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 10, '[feicui] elevator #3', 'DEV_FEICUI_ELEVATOR_003', 18, 3, 16, 'A栋 21层', 21, 'A栋', '2024-02-23', NOW(), 'v2.3.5', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 3, '[feicui] elevator #4', 'DEV_FEICUI_ELEVATOR_004', 20, 3, -2, 'C栋 8层', 8, 'C栋', '2024-09-12', NOW(), 'v1.3.0', 96, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 4, '[feicui] elevator #5', 'DEV_FEICUI_ELEVATOR_005', -22, 3, -20, 'L栋 3层', 3, 'L栋', '2024-05-18', NOW(), 'v2.7.3', 85, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 4, '[feicui] elevator #6', 'DEV_FEICUI_ELEVATOR_006', -2, 3, -24, 'Y栋 22层', 22, 'Y栋', '2024-08-27', NOW(), 'v1.1.5', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 4, '[feicui] elevator #7', 'DEV_FEICUI_ELEVATOR_007', 22, 3, -18, 'O栋 12层', 12, 'O栋', '2024-05-25', NOW(), 'v2.6.6', 85, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 20, 6, '[feicui] trash #1', 'DEV_FEICUI_TRASH_001', -38, 1.5, -28, 'R栋 17层', 17, 'R栋', '2024-09-15', NOW(), 'v3.5.7', 94, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 20, 9, '[feicui] trash #2', 'DEV_FEICUI_TRASH_002', 20, 1.5, -28, 'R栋 4层', 4, 'R栋', '2024-05-28', NOW(), 'v2.7.9', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 20, 7, '[feicui] trash #3', 'DEV_FEICUI_TRASH_003', -38, 1.5, 20, 'I栋 17层', 17, 'I栋', '2024-09-11', NOW(), 'v1.2.5', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 20, 9, '[feicui] trash #4', 'DEV_FEICUI_TRASH_004', 20, 1.5, 20, 'T栋 3层', 3, 'T栋', '2024-06-21', NOW(), 'v3.8.7', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 9, '[yunqi] smoke #1', 'DEV_YUNQI_SMOKE_001', -44, 2.8, -30, 'Z栋 23层', 23, 'Z栋', '2024-06-10', NOW(), 'v1.9.5', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 7, '[yunqi] smoke #2', 'DEV_YUNQI_SMOKE_002', -28, 2.8, -30, 'D栋 24层', 24, 'D栋', '2024-03-11', NOW(), 'v3.0.0', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 9, '[yunqi] smoke #3', 'DEV_YUNQI_SMOKE_003', -12, 2.8, -30, 'N栋 26层', 26, 'N栋', '2024-08-24', NOW(), 'v2.0.5', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 9, '[yunqi] smoke #4', 'DEV_YUNQI_SMOKE_004', 4, 2.8, -30, 'G栋 29层', 29, 'G栋', '2024-06-20', NOW(), 'v2.5.5', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 6, '[yunqi] smoke #5', 'DEV_YUNQI_SMOKE_005', 20, 2.8, -30, 'X栋 11层', 11, 'X栋', '2024-03-22', NOW(), 'v2.3.1', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 7, '[yunqi] smoke #6', 'DEV_YUNQI_SMOKE_006', 36, 2.8, -30, 'S栋 19层', 19, 'S栋', '2024-05-18', NOW(), 'v2.6.9', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 7, '[yunqi] smoke #7', 'DEV_YUNQI_SMOKE_007', -44, 2.8, 25, 'A栋 1层', 1, 'A栋', '2024-06-13', NOW(), 'v2.8.3', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 9, '[yunqi] smoke #8', 'DEV_YUNQI_SMOKE_008', -28, 2.8, 25, 'A栋 5层', 5, 'A栋', '2024-05-12', NOW(), 'v2.3.1', 85, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 7, '[yunqi] smoke #9', 'DEV_YUNQI_SMOKE_009', -12, 2.8, 25, 'M栋 7层', 7, 'M栋', '2024-07-16', NOW(), 'v1.0.0', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 9, '[yunqi] smoke #10', 'DEV_YUNQI_SMOKE_010', 4, 2.8, 25, 'J栋 15层', 15, 'J栋', '2024-02-19', NOW(), 'v1.8.1', 97, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 9, '[yunqi] smoke #11', 'DEV_YUNQI_SMOKE_011', 20, 2.8, 25, 'U栋 27层', 27, 'U栋', '2024-08-24', NOW(), 'v3.2.5', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 1, 9, '[yunqi] smoke #12', 'DEV_YUNQI_SMOKE_012', 36, 2.8, 25, 'I栋 6层', 6, 'I栋', '2024-03-10', NOW(), 'v3.8.6', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 5, 11, '[yunqi] access #1', 'DEV_YUNQI_ACCESS_001', 0, 1.2, -47, 'O栋 4层', 4, 'O栋', '2024-02-16', NOW(), 'v1.5.8', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 5, 11, '[yunqi] access #2', 'DEV_YUNQI_ACCESS_002', 0, 1.2, 47, 'K栋 3层', 3, 'K栋', '2024-03-13', NOW(), 'v1.7.9', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 5, 12, '[yunqi] access #3', 'DEV_YUNQI_ACCESS_003', -47, 1.2, 0, 'I栋 3层', 3, 'I栋', '2024-03-18', NOW(), 'v2.8.1', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 5, 3, '[yunqi] access #4', 'DEV_YUNQI_ACCESS_004', 47, 1.2, 0, 'S栋 15层', 15, 'S栋', '2024-01-15', NOW(), 'v1.7.2', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 6, 1, '[yunqi] meter_w #1', 'DEV_YUNQI_METER_W_001', -44, 0.8, -30, 'A栋 22层', 22, 'A栋', '2024-05-13', NOW(), 'v2.0.0', 89, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 6, 1, '[yunqi] meter_w #2', 'DEV_YUNQI_METER_W_002', -28, 0.8, -30, 'H栋 29层', 29, 'H栋', '2024-09-13', NOW(), 'v2.7.9', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 6, 7, '[yunqi] meter_w #3', 'DEV_YUNQI_METER_W_003', -12, 0.8, -30, 'W栋 18层', 18, 'W栋', '2024-08-11', NOW(), 'v1.8.5', 74, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 6, 7, '[yunqi] meter_w #4', 'DEV_YUNQI_METER_W_004', 4, 0.8, -30, 'S栋 14层', 14, 'S栋', '2024-07-14', NOW(), 'v2.2.8', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 6, 7, '[yunqi] meter_w #5', 'DEV_YUNQI_METER_W_005', 20, 0.8, -30, 'W栋 7层', 7, 'W栋', '2024-06-18', NOW(), 'v3.1.2', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 6, 9, '[yunqi] meter_w #6', 'DEV_YUNQI_METER_W_006', 36, 0.8, -30, 'S栋 30层', 30, 'S栋', '2024-01-24', NOW(), 'v1.2.2', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 7, 7, '[yunqi] meter_e #1', 'DEV_YUNQI_METER_E_001', -44, 1.6, 25, 'V栋 28层', 28, 'V栋', '2024-01-19', NOW(), 'v3.6.3', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 7, 1, '[yunqi] meter_e #2', 'DEV_YUNQI_METER_E_002', -28, 1.6, 25, 'W栋 7层', 7, 'W栋', '2024-06-27', NOW(), 'v2.5.0', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 7, 4, '[yunqi] meter_e #3', 'DEV_YUNQI_METER_E_003', -12, 1.6, 25, 'Z栋 31层', 31, 'Z栋', '2024-06-25', NOW(), 'v3.0.2', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 7, 1, '[yunqi] meter_e #4', 'DEV_YUNQI_METER_E_004', 4, 1.6, 25, 'C栋 16层', 16, 'C栋', '2024-09-26', NOW(), 'v1.1.7', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 7, 1, '[yunqi] meter_e #5', 'DEV_YUNQI_METER_E_005', 20, 1.6, 25, 'R栋 10层', 10, 'R栋', '2024-01-14', NOW(), 'v1.2.2', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 7, 1, '[yunqi] meter_e #6', 'DEV_YUNQI_METER_E_006', 36, 1.6, 25, 'J栋 15层', 15, 'J栋', '2024-03-16', NOW(), 'v2.2.2', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 9, 13, '[yunqi] camera #1', 'DEV_YUNQI_CAMERA_001', -48, 4.5, -10, 'X栋 5层', 5, 'X栋', '2024-05-27', NOW(), 'v2.3.5', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 9, 13, '[yunqi] camera #2', 'DEV_YUNQI_CAMERA_002', -48, 4.5, 15, 'K栋 20层', 20, 'K栋', '2024-04-14', NOW(), 'v3.2.2', 92, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 9, 13, '[yunqi] camera #3', 'DEV_YUNQI_CAMERA_003', 48, 4.5, -10, 'Q栋 4层', 4, 'Q栋', '2024-02-19', NOW(), 'v1.1.1', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 9, 11, '[yunqi] camera #4', 'DEV_YUNQI_CAMERA_004', 48, 4.5, 10, 'N栋 2层', 2, 'N栋', '2024-08-13', NOW(), 'v1.6.7', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 9, 3, '[yunqi] camera #5', 'DEV_YUNQI_CAMERA_005', 0, 4.5, -48, 'V栋 25层', 25, 'V栋', '2024-05-26', NOW(), 'v2.4.2', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 9, 11, '[yunqi] camera #6', 'DEV_YUNQI_CAMERA_006', 0, 4.5, 48, 'F栋 32层', 32, 'F栋', '2024-07-19', NOW(), 'v1.7.2', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 10, 4, '[yunqi] env #1', 'DEV_YUNQI_ENV_001', -30, 2, -20, 'A栋 21层', 21, 'A栋', '2024-07-20', NOW(), 'v2.3.6', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 10, 6, '[yunqi] env #2', 'DEV_YUNQI_ENV_002', 30, 2, -20, 'C栋 29层', 29, 'C栋', '2024-09-19', NOW(), 'v1.1.8', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 10, 8, '[yunqi] env #3', 'DEV_YUNQI_ENV_003', 0, 2, 0, 'O栋 19层', 19, 'O栋', '2024-04-12', NOW(), 'v3.3.7', 91, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 10, 4, '[yunqi] env #4', 'DEV_YUNQI_ENV_004', -30, 2, 20, 'U栋 23层', 23, 'U栋', '2024-07-12', NOW(), 'v2.8.9', 96, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 10, 4, '[yunqi] env #5', 'DEV_YUNQI_ENV_005', 30, 2, 20, 'L栋 31层', 31, 'L栋', '2024-02-18', NOW(), 'v2.5.3', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 9, '[yunqi] parking #1', 'DEV_YUNQI_PARKING_001', -45, 0.05, -40, 'O栋 7层', 7, 'O栋', '2024-05-19', NOW(), 'v1.9.1', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 9, '[yunqi] parking #2', 'DEV_YUNQI_PARKING_002', -25, 0.05, -40, 'X栋 10层', 10, 'X栋', '2024-05-21', NOW(), 'v3.6.2', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 8, '[yunqi] parking #3', 'DEV_YUNQI_PARKING_003', 0, 0.05, -40, 'A栋 20层', 20, 'A栋', '2024-04-27', NOW(), 'v3.7.7', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 8, '[yunqi] parking #4', 'DEV_YUNQI_PARKING_004', 25, 0.05, -40, 'B栋 28层', 28, 'B栋', '2024-07-12', NOW(), 'v1.4.5', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 8, '[yunqi] parking #5', 'DEV_YUNQI_PARKING_005', 45, 0.05, -40, 'O栋 30层', 30, 'O栋', '2024-06-18', NOW(), 'v1.0.8', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 8, '[yunqi] parking #6', 'DEV_YUNQI_PARKING_006', -45, 0.05, 35, 'F栋 1层', 1, 'F栋', '2024-05-10', NOW(), 'v3.4.4', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 7, '[yunqi] parking #7', 'DEV_YUNQI_PARKING_007', -25, 0.05, 35, 'W栋 2层', 2, 'W栋', '2024-06-22', NOW(), 'v1.5.5', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 8, '[yunqi] parking #8', 'DEV_YUNQI_PARKING_008', 0, 0.05, 35, 'T栋 32层', 32, 'T栋', '2024-09-17', NOW(), 'v1.9.4', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 7, '[yunqi] parking #9', 'DEV_YUNQI_PARKING_009', 25, 0.05, 35, 'N栋 32层', 32, 'N栋', '2024-07-24', NOW(), 'v2.0.9', 93, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 19, 8, '[yunqi] parking #10', 'DEV_YUNQI_PARKING_010', 45, 0.05, 35, 'K栋 13层', 13, 'K栋', '2024-02-18', NOW(), 'v1.9.3', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 18, 7, '[yunqi] streetlight #1', 'DEV_YUNQI_STREETLIGHT_001', -47, 6, -35, 'Y栋 13层', 13, 'Y栋', '2024-09-12', NOW(), 'v3.5.3', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 18, 7, '[yunqi] streetlight #2', 'DEV_YUNQI_STREETLIGHT_002', 0, 6, -35, 'O栋 18层', 18, 'O栋', '2024-04-19', NOW(), 'v2.7.3', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 18, 9, '[yunqi] streetlight #3', 'DEV_YUNQI_STREETLIGHT_003', 47, 6, -35, 'M栋 24层', 24, 'M栋', '2024-05-27', NOW(), 'v2.9.5', 97, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 18, 6, '[yunqi] streetlight #4', 'DEV_YUNQI_STREETLIGHT_004', -47, 6, 35, 'K栋 13层', 13, 'K栋', '2024-07-22', NOW(), 'v3.1.4', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 18, 7, '[yunqi] streetlight #5', 'DEV_YUNQI_STREETLIGHT_005', 47, 6, 35, 'R栋 24层', 24, 'R栋', '2024-09-17', NOW(), 'v1.3.3', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 3, 6, '[yunqi] gas #1', 'DEV_YUNQI_GAS_001', -44, 0.6, -30, 'J栋 30层', 30, 'J栋', '2024-03-18', NOW(), 'v1.3.5', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 3, 9, '[yunqi] gas #2', 'DEV_YUNQI_GAS_002', -12, 0.6, -30, 'F栋 5层', 5, 'F栋', '2024-05-21', NOW(), 'v1.0.9', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 3, 9, '[yunqi] gas #3', 'DEV_YUNQI_GAS_003', 20, 0.6, -30, 'K栋 30层', 30, 'K栋', '2024-04-12', NOW(), 'v2.5.6', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 3, 7, '[yunqi] gas #4', 'DEV_YUNQI_GAS_004', -44, 0.6, 25, 'A栋 9层', 9, 'A栋', '2024-04-23', NOW(), 'v3.7.8', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 3, 9, '[yunqi] gas #5', 'DEV_YUNQI_GAS_005', -12, 0.6, 25, 'I栋 29层', 29, 'I栋', '2024-04-24', NOW(), 'v2.9.3', 85, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 3, 7, '[yunqi] gas #6', 'DEV_YUNQI_GAS_006', 20, 0.6, 25, 'S栋 2层', 2, 'S栋', '2024-09-12', NOW(), 'v2.5.1', 87, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 17, 7, '[yunqi] hydrant #1', 'DEV_YUNQI_HYDRANT_001', -47, 0.6, -35, 'O栋 5层', 5, 'O栋', '2024-04-27', NOW(), 'v1.2.9', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 17, 7, '[yunqi] hydrant #2', 'DEV_YUNQI_HYDRANT_002', 47, 0.6, -35, 'Q栋 3层', 3, 'Q栋', '2024-09-13', NOW(), 'v1.1.1', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 17, 9, '[yunqi] hydrant #3', 'DEV_YUNQI_HYDRANT_003', -47, 0.6, 35, 'N栋 13层', 13, 'N栋', '2024-07-14', NOW(), 'v3.7.4', 92, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 17, 9, '[yunqi] hydrant #4', 'DEV_YUNQI_HYDRANT_004', 47, 0.6, 35, 'C栋 14层', 14, 'C栋', '2024-07-27', NOW(), 'v2.2.6', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 16, 1, '[yunqi] elevator #1', 'DEV_YUNQI_ELEVATOR_001', -44, 3, -30, 'G栋 10层', 10, 'G栋', '2024-04-17', NOW(), 'v1.8.1', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 16, 10, '[yunqi] elevator #2', 'DEV_YUNQI_ELEVATOR_002', -28, 3, -30, 'G栋 8层', 8, 'G栋', '2024-08-27', NOW(), 'v3.2.7', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 16, 3, '[yunqi] elevator #3', 'DEV_YUNQI_ELEVATOR_003', 4, 3, -30, 'Z栋 3层', 3, 'Z栋', '2024-01-23', NOW(), 'v3.7.3', 97, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 16, 10, '[yunqi] elevator #4', 'DEV_YUNQI_ELEVATOR_004', 20, 3, -30, 'L栋 30层', 30, 'L栋', '2024-02-23', NOW(), 'v2.3.3', 93, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 16, 4, '[yunqi] elevator #5', 'DEV_YUNQI_ELEVATOR_005', -44, 3, 25, 'K栋 6层', 6, 'K栋', '2024-06-14', NOW(), 'v3.8.0', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 16, 3, '[yunqi] elevator #6', 'DEV_YUNQI_ELEVATOR_006', 4, 3, 25, 'O栋 26层', 26, 'O栋', '2024-03-24', NOW(), 'v3.2.1', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 20, 9, '[yunqi] trash #1', 'DEV_YUNQI_TRASH_001', -45, 1.5, -38, 'W栋 4层', 4, 'W栋', '2024-07-27', NOW(), 'v1.8.2', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 20, 6, '[yunqi] trash #2', 'DEV_YUNQI_TRASH_002', 25, 1.5, -38, 'P栋 27层', 27, 'P栋', '2024-08-17', NOW(), 'v2.4.3', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 20, 6, '[yunqi] trash #3', 'DEV_YUNQI_TRASH_003', -45, 1.5, 28, 'R栋 13层', 13, 'R栋', '2024-07-25', NOW(), 'v1.4.6', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (9, 20, 7, '[yunqi] trash #4', 'DEV_YUNQI_TRASH_004', 25, 1.5, 28, 'X栋 27层', 27, 'X栋', '2024-02-21', NOW(), 'v2.0.4', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 1, 7, '[zhongliang] smoke #1', 'DEV_ZHONGLIANG_SMOKE_001', -28, 2.8, -15, 'H栋 31层', 31, 'H栋', '2024-09-24', NOW(), 'v2.3.0', 96, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 1, 7, '[zhongliang] smoke #2', 'DEV_ZHONGLIANG_SMOKE_002', 0, 2.8, -12, 'V栋 13层', 13, 'V栋', '2024-07-27', NOW(), 'v2.2.0', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 1, 7, '[zhongliang] smoke #3', 'DEV_ZHONGLIANG_SMOKE_003', 30, 2.8, -18, 'D栋 25层', 25, 'D栋', '2024-09-23', NOW(), 'v3.8.4', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 1, 7, '[zhongliang] smoke #4', 'DEV_ZHONGLIANG_SMOKE_004', -28, 2.8, 10, 'E栋 1层', 1, 'E栋', '2024-01-18', NOW(), 'v1.3.5', 94, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 1, 9, '[zhongliang] smoke #5', 'DEV_ZHONGLIANG_SMOKE_005', 5, 2.8, 12, 'W栋 11层', 11, 'W栋', '2024-08-25', NOW(), 'v1.4.9', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 1, 7, '[zhongliang] smoke #6', 'DEV_ZHONGLIANG_SMOKE_006', 30, 2.8, 8, 'G栋 21层', 21, 'G栋', '2024-06-23', NOW(), 'v1.3.0', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 5, 11, '[zhongliang] access #1', 'DEV_ZHONGLIANG_ACCESS_001', -40, 1.2, -20, 'Z栋 32层', 32, 'Z栋', '2024-07-28', NOW(), 'v2.8.0', 87, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 5, 11, '[zhongliang] access #2', 'DEV_ZHONGLIANG_ACCESS_002', -40, 1.2, 15, 'U栋 5层', 5, 'U栋', '2024-02-26', NOW(), 'v2.9.3', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 5, 12, '[zhongliang] access #3', 'DEV_ZHONGLIANG_ACCESS_003', 43, 1.2, -18, 'H栋 6层', 6, 'H栋', '2024-02-20', NOW(), 'v2.2.6', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 5, 11, '[zhongliang] access #4', 'DEV_ZHONGLIANG_ACCESS_004', 43, 1.2, 12, 'E栋 29层', 29, 'E栋', '2024-06-25', NOW(), 'v2.7.6', 92, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 5, 4, '[zhongliang] access #5', 'DEV_ZHONGLIANG_ACCESS_005', -5, 1.2, -22, 'G栋 10层', 10, 'G栋', '2024-07-26', NOW(), 'v3.0.9', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 5, 12, '[zhongliang] access #6', 'DEV_ZHONGLIANG_ACCESS_006', -5, 1.2, 22, 'V栋 30层', 30, 'V栋', '2024-05-12', NOW(), 'v3.9.7', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 6, 7, '[zhongliang] meter_w #1', 'DEV_ZHONGLIANG_METER_W_001', -28, 0.8, -15, 'Y栋 14层', 14, 'Y栋', '2024-04-27', NOW(), 'v3.6.6', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 6, 7, '[zhongliang] meter_w #2', 'DEV_ZHONGLIANG_METER_W_002', 0, 0.8, -12, 'P栋 17层', 17, 'P栋', '2024-05-23', NOW(), 'v2.1.8', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 6, 4, '[zhongliang] meter_w #3', 'DEV_ZHONGLIANG_METER_W_003', 30, 0.8, -18, 'U栋 4层', 4, 'U栋', '2024-01-14', NOW(), 'v1.1.1', 79, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 6, 7, '[zhongliang] meter_w #4', 'DEV_ZHONGLIANG_METER_W_004', -28, 0.8, 10, 'C栋 25层', 25, 'C栋', '2024-09-21', NOW(), 'v2.8.7', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 6, 4, '[zhongliang] meter_w #5', 'DEV_ZHONGLIANG_METER_W_005', 5, 0.8, 12, 'Y栋 14层', 14, 'Y栋', '2024-04-23', NOW(), 'v2.9.5', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 6, 1, '[zhongliang] meter_w #6', 'DEV_ZHONGLIANG_METER_W_006', 30, 0.8, 8, 'J栋 10层', 10, 'J栋', '2024-03-25', NOW(), 'v3.9.3', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 7, 9, '[zhongliang] meter_e #1', 'DEV_ZHONGLIANG_METER_E_001', -28, 1.6, -15, 'J栋 2层', 2, 'J栋', '2024-06-18', NOW(), 'v1.3.2', 94, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 7, 9, '[zhongliang] meter_e #2', 'DEV_ZHONGLIANG_METER_E_002', 0, 1.6, -12, 'M栋 16层', 16, 'M栋', '2024-04-25', NOW(), 'v1.2.5', 87, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 7, 4, '[zhongliang] meter_e #3', 'DEV_ZHONGLIANG_METER_E_003', 30, 1.6, -18, 'W栋 27层', 27, 'W栋', '2024-02-20', NOW(), 'v2.2.1', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 7, 9, '[zhongliang] meter_e #4', 'DEV_ZHONGLIANG_METER_E_004', -28, 1.6, 10, 'B栋 9层', 9, 'B栋', '2024-05-16', NOW(), 'v1.3.9', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 7, 12, '[zhongliang] meter_e #5', 'DEV_ZHONGLIANG_METER_E_005', 5, 1.6, 12, 'C栋 32层', 32, 'C栋', '2024-02-13', NOW(), 'v2.6.6', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 7, 12, '[zhongliang] meter_e #6', 'DEV_ZHONGLIANG_METER_E_006', 30, 1.6, 8, 'E栋 3层', 3, 'E栋', '2024-03-15', NOW(), 'v3.6.1', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 9, 13, '[zhongliang] camera #1', 'DEV_ZHONGLIANG_CAMERA_001', -42, 4.5, -5, 'O栋 5层', 5, 'O栋', '2024-05-12', NOW(), 'v1.1.5', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 9, 3, '[zhongliang] camera #2', 'DEV_ZHONGLIANG_CAMERA_002', -42, 4.5, 5, 'M栋 31层', 31, 'M栋', '2024-08-21', NOW(), 'v3.2.5', 94, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 9, 13, '[zhongliang] camera #3', 'DEV_ZHONGLIANG_CAMERA_003', 45, 4.5, 0, 'C栋 6层', 6, 'C栋', '2024-08-23', NOW(), 'v1.6.1', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 9, 13, '[zhongliang] camera #4', 'DEV_ZHONGLIANG_CAMERA_004', 0, 4.5, -24, 'O栋 32层', 32, 'O栋', '2024-08-28', NOW(), 'v3.3.8', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 9, 3, '[zhongliang] camera #5', 'DEV_ZHONGLIANG_CAMERA_005', 0, 4.5, 24, 'A栋 13层', 13, 'A栋', '2024-01-25', NOW(), 'v2.2.8', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 10, 4, '[zhongliang] env #1', 'DEV_ZHONGLIANG_ENV_001', -15, 2, -5, 'P栋 12层', 12, 'P栋', '2024-02-15', NOW(), 'v1.5.0', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 10, 8, '[zhongliang] env #2', 'DEV_ZHONGLIANG_ENV_002', 15, 2, -5, 'G栋 7层', 7, 'G栋', '2024-09-25', NOW(), 'v2.8.1', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 10, 7, '[zhongliang] env #3', 'DEV_ZHONGLIANG_ENV_003', 0, 2, 0, 'J栋 28层', 28, 'J栋', '2024-04-25', NOW(), 'v2.1.7', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 10, 7, '[zhongliang] env #4', 'DEV_ZHONGLIANG_ENV_004', -15, 2, 10, 'G栋 31层', 31, 'G栋', '2024-04-16', NOW(), 'v1.7.7', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 10, 8, '[zhongliang] env #5', 'DEV_ZHONGLIANG_ENV_005', 15, 2, 10, 'A栋 27层', 27, 'A栋', '2024-04-19', NOW(), 'v1.7.0', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 9, '[zhongliang] parking #1', 'DEV_ZHONGLIANG_PARKING_001', -35, 0.05, -25, 'V栋 6层', 6, 'V栋', '2024-06-27', NOW(), 'v3.8.6', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 9, '[zhongliang] parking #2', 'DEV_ZHONGLIANG_PARKING_002', -15, 0.05, -25, 'S栋 10层', 10, 'S栋', '2024-09-19', NOW(), 'v1.7.6', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 9, '[zhongliang] parking #3', 'DEV_ZHONGLIANG_PARKING_003', 5, 0.05, -25, 'S栋 17层', 17, 'S栋', '2024-08-19', NOW(), 'v3.4.6', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 9, '[zhongliang] parking #4', 'DEV_ZHONGLIANG_PARKING_004', 25, 0.05, -25, 'O栋 11层', 11, 'O栋', '2024-04-11', NOW(), 'v3.2.7', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 8, '[zhongliang] parking #5', 'DEV_ZHONGLIANG_PARKING_005', 35, 0.05, -25, 'Z栋 14层', 14, 'Z栋', '2024-04-11', NOW(), 'v2.7.5', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 7, '[zhongliang] parking #6', 'DEV_ZHONGLIANG_PARKING_006', -35, 0.05, 20, 'C栋 14层', 14, 'C栋', '2024-08-15', NOW(), 'v3.2.1', 96, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 8, '[zhongliang] parking #7', 'DEV_ZHONGLIANG_PARKING_007', -15, 0.05, 20, 'N栋 27层', 27, 'N栋', '2024-03-25', NOW(), 'v2.1.1', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 9, '[zhongliang] parking #8', 'DEV_ZHONGLIANG_PARKING_008', 5, 0.05, 20, 'H栋 29层', 29, 'H栋', '2024-07-24', NOW(), 'v3.7.6', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 9, '[zhongliang] parking #9', 'DEV_ZHONGLIANG_PARKING_009', 25, 0.05, 20, 'I栋 7层', 7, 'I栋', '2024-03-15', NOW(), 'v3.1.2', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 19, 8, '[zhongliang] parking #10', 'DEV_ZHONGLIANG_PARKING_010', 35, 0.05, 20, 'H栋 13层', 13, 'H栋', '2024-09-21', NOW(), 'v3.7.7', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 18, 8, '[zhongliang] streetlight #1', 'DEV_ZHONGLIANG_STREETLIGHT_001', -38, 6, -22, 'L栋 11层', 11, 'L栋', '2024-06-22', NOW(), 'v1.2.9', 97, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 18, 8, '[zhongliang] streetlight #2', 'DEV_ZHONGLIANG_STREETLIGHT_002', 0, 6, -22, 'L栋 2层', 2, 'L栋', '2024-01-10', NOW(), 'v3.5.2', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 18, 9, '[zhongliang] streetlight #3', 'DEV_ZHONGLIANG_STREETLIGHT_003', 40, 6, -22, 'O栋 29层', 29, 'O栋', '2024-02-17', NOW(), 'v3.8.6', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 18, 8, '[zhongliang] streetlight #4', 'DEV_ZHONGLIANG_STREETLIGHT_004', -38, 6, 18, 'C栋 26层', 26, 'C栋', '2024-08-22', NOW(), 'v1.2.4', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 18, 6, '[zhongliang] streetlight #5', 'DEV_ZHONGLIANG_STREETLIGHT_005', 40, 6, 18, 'Y栋 11层', 11, 'Y栋', '2024-09-17', NOW(), 'v2.5.0', 91, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 3, 7, '[zhongliang] gas #1', 'DEV_ZHONGLIANG_GAS_001', -28, 0.6, -15, 'J栋 9层', 9, 'J栋', '2024-01-22', NOW(), 'v2.9.9', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 3, 6, '[zhongliang] gas #2', 'DEV_ZHONGLIANG_GAS_002', 0, 0.6, -12, 'C栋 5层', 5, 'C栋', '2024-01-16', NOW(), 'v3.4.9', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 3, 6, '[zhongliang] gas #3', 'DEV_ZHONGLIANG_GAS_003', 30, 0.6, -18, 'B栋 24层', 24, 'B栋', '2024-06-19', NOW(), 'v3.6.4', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 3, 9, '[zhongliang] gas #4', 'DEV_ZHONGLIANG_GAS_004', -28, 0.6, 10, 'M栋 8层', 8, 'M栋', '2024-06-28', NOW(), 'v3.5.2', 93, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 3, 9, '[zhongliang] gas #5', 'DEV_ZHONGLIANG_GAS_005', 5, 0.6, 12, 'K栋 24层', 24, 'K栋', '2024-06-15', NOW(), 'v3.4.3', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 3, 9, '[zhongliang] gas #6', 'DEV_ZHONGLIANG_GAS_006', 30, 0.6, 8, 'V栋 15层', 15, 'V栋', '2024-01-13', NOW(), 'v1.0.6', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 17, 9, '[zhongliang] hydrant #1', 'DEV_ZHONGLIANG_HYDRANT_001', -38, 0.6, -22, 'D栋 19层', 19, 'D栋', '2024-03-21', NOW(), 'v1.5.6', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 17, 9, '[zhongliang] hydrant #2', 'DEV_ZHONGLIANG_HYDRANT_002', 40, 0.6, -22, 'V栋 4层', 4, 'V栋', '2024-09-11', NOW(), 'v1.4.8', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 17, 7, '[zhongliang] hydrant #3', 'DEV_ZHONGLIANG_HYDRANT_003', -38, 0.6, 18, 'U栋 15层', 15, 'U栋', '2024-09-25', NOW(), 'v1.3.8', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 17, 7, '[zhongliang] hydrant #4', 'DEV_ZHONGLIANG_HYDRANT_004', 40, 0.6, 18, 'X栋 26层', 26, 'X栋', '2024-05-14', NOW(), 'v2.6.2', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 16, 4, '[zhongliang] elevator #1', 'DEV_ZHONGLIANG_ELEVATOR_001', -28, 3, -15, 'M栋 25层', 25, 'M栋', '2024-09-14', NOW(), 'v1.3.5', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 16, 4, '[zhongliang] elevator #2', 'DEV_ZHONGLIANG_ELEVATOR_002', 0, 3, -12, 'A栋 21层', 21, 'A栋', '2024-02-27', NOW(), 'v1.9.4', 93, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 16, 3, '[zhongliang] elevator #3', 'DEV_ZHONGLIANG_ELEVATOR_003', -28, 3, 10, 'Y栋 29层', 29, 'Y栋', '2024-09-28', NOW(), 'v1.7.0', 91, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 16, 10, '[zhongliang] elevator #4', 'DEV_ZHONGLIANG_ELEVATOR_004', 30, 3, 8, 'E栋 20层', 20, 'E栋', '2024-03-24', NOW(), 'v3.8.4', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 20, 7, '[zhongliang] trash #1', 'DEV_ZHONGLIANG_TRASH_001', -35, 1.5, -22, 'Q栋 15层', 15, 'Q栋', '2024-09-10', NOW(), 'v1.3.8', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 20, 6, '[zhongliang] trash #2', 'DEV_ZHONGLIANG_TRASH_002', 20, 1.5, -22, 'U栋 27层', 27, 'U栋', '2024-01-24', NOW(), 'v3.7.1', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 20, 9, '[zhongliang] trash #3', 'DEV_ZHONGLIANG_TRASH_003', -35, 1.5, 15, 'D栋 32层', 32, 'D栋', '2024-06-25', NOW(), 'v3.7.5', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (11, 20, 9, '[zhongliang] trash #4', 'DEV_ZHONGLIANG_TRASH_004', 20, 1.5, 15, 'J栋 25层', 25, 'J栋', '2024-01-17', NOW(), 'v2.4.2', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 7, '[shanshui] smoke #1', 'DEV_SHANSHUI_SMOKE_001', -30, 2.8, -20, 'S栋 31层', 31, 'S栋', '2024-03-25', NOW(), 'v2.5.9', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 9, '[shanshui] smoke #2', 'DEV_SHANSHUI_SMOKE_002', -10, 2.8, -18, 'H栋 4层', 4, 'H栋', '2024-02-19', NOW(), 'v1.9.3', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 6, '[shanshui] smoke #3', 'DEV_SHANSHUI_SMOKE_003', 10, 2.8, -22, 'P栋 19层', 19, 'P栋', '2024-09-20', NOW(), 'v1.0.2', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 9, '[shanshui] smoke #4', 'DEV_SHANSHUI_SMOKE_004', 30, 2.8, -15, 'Z栋 21层', 21, 'Z栋', '2024-02-12', NOW(), 'v1.6.8', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 6, '[shanshui] smoke #5', 'DEV_SHANSHUI_SMOKE_005', -20, 2.8, 10, 'F栋 5层', 5, 'F栋', '2024-08-18', NOW(), 'v3.5.2', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 6, '[shanshui] smoke #6', 'DEV_SHANSHUI_SMOKE_006', 0, 2.8, 8, 'A栋 7层', 7, 'A栋', '2024-02-16', NOW(), 'v1.3.0', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 7, '[shanshui] smoke #7', 'DEV_SHANSHUI_SMOKE_007', 20, 2.8, 12, 'H栋 10层', 10, 'H栋', '2024-01-11', NOW(), 'v1.4.1', 94, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 9, '[shanshui] smoke #8', 'DEV_SHANSHUI_SMOKE_008', -30, 2.8, 25, 'H栋 10层', 10, 'H栋', '2024-07-11', NOW(), 'v1.4.4', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 9, '[shanshui] smoke #9', 'DEV_SHANSHUI_SMOKE_009', -10, 2.8, 28, 'D栋 7层', 7, 'D栋', '2024-09-20', NOW(), 'v1.3.5', 96, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 9, '[shanshui] smoke #10', 'DEV_SHANSHUI_SMOKE_010', 10, 2.8, 22, 'F栋 32层', 32, 'F栋', '2024-05-24', NOW(), 'v3.1.3', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 1, 6, '[shanshui] smoke #11', 'DEV_SHANSHUI_SMOKE_011', 30, 2.8, 20, 'M栋 16层', 16, 'M栋', '2024-08-12', NOW(), 'v2.3.9', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 5, 4, '[shanshui] access #1', 'DEV_SHANSHUI_ACCESS_001', 0, 1.2, -41, 'Q栋 7层', 7, 'Q栋', '2024-01-11', NOW(), 'v2.8.9', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 5, 12, '[shanshui] access #2', 'DEV_SHANSHUI_ACCESS_002', 0, 1.2, 41, 'M栋 32层', 32, 'M栋', '2024-01-11', NOW(), 'v3.6.4', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 5, 11, '[shanshui] access #3', 'DEV_SHANSHUI_ACCESS_003', -41, 1.2, 0, 'H栋 23层', 23, 'H栋', '2024-05-11', NOW(), 'v2.5.2', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 6, 4, '[shanshui] meter_w #1', 'DEV_SHANSHUI_METER_W_001', -30, 0.8, -20, 'O栋 2层', 2, 'O栋', '2024-06-27', NOW(), 'v3.1.7', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 6, 4, '[shanshui] meter_w #2', 'DEV_SHANSHUI_METER_W_002', -10, 0.8, -18, 'Y栋 30层', 30, 'Y栋', '2024-03-12', NOW(), 'v1.7.7', 89, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 6, 7, '[shanshui] meter_w #3', 'DEV_SHANSHUI_METER_W_003', 10, 0.8, -22, 'E栋 18层', 18, 'E栋', '2024-04-28', NOW(), 'v1.6.3', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 6, 7, '[shanshui] meter_w #4', 'DEV_SHANSHUI_METER_W_004', 30, 0.8, -15, 'X栋 19层', 19, 'X栋', '2024-05-13', NOW(), 'v3.1.5', 79, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 6, 4, '[shanshui] meter_w #5', 'DEV_SHANSHUI_METER_W_005', -20, 0.8, 10, 'K栋 26层', 26, 'K栋', '2024-08-19', NOW(), 'v1.7.3', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 6, 9, '[shanshui] meter_w #6', 'DEV_SHANSHUI_METER_W_006', 0, 0.8, 8, 'R栋 5层', 5, 'R栋', '2024-04-16', NOW(), 'v2.4.3', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 6, 9, '[shanshui] meter_w #7', 'DEV_SHANSHUI_METER_W_007', 20, 0.8, 12, 'X栋 26层', 26, 'X栋', '2024-05-18', NOW(), 'v2.2.3', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 7, 12, '[shanshui] meter_e #1', 'DEV_SHANSHUI_METER_E_001', -30, 1.6, 25, 'W栋 17层', 17, 'W栋', '2024-06-15', NOW(), 'v2.8.8', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 7, 7, '[shanshui] meter_e #2', 'DEV_SHANSHUI_METER_E_002', -10, 1.6, 28, 'R栋 23层', 23, 'R栋', '2024-01-11', NOW(), 'v2.9.0', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 7, 9, '[shanshui] meter_e #3', 'DEV_SHANSHUI_METER_E_003', 10, 1.6, 22, 'X栋 13层', 13, 'X栋', '2024-08-12', NOW(), 'v1.8.8', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 7, 7, '[shanshui] meter_e #4', 'DEV_SHANSHUI_METER_E_004', 30, 1.6, 20, 'N栋 25层', 25, 'N栋', '2024-09-25', NOW(), 'v2.6.6', 85, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 9, 11, '[shanshui] camera #1', 'DEV_SHANSHUI_CAMERA_001', -42, 4.5, 0, 'M栋 2层', 2, 'M栋', '2024-06-21', NOW(), 'v3.7.6', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 9, 11, '[shanshui] camera #2', 'DEV_SHANSHUI_CAMERA_002', 0, 4.5, -42, 'T栋 21层', 21, 'T栋', '2024-05-12', NOW(), 'v3.3.2', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 9, 13, '[shanshui] camera #3', 'DEV_SHANSHUI_CAMERA_003', 42, 4.5, 0, 'U栋 8层', 8, 'U栋', '2024-03-25', NOW(), 'v1.7.2', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 9, 11, '[shanshui] camera #4', 'DEV_SHANSHUI_CAMERA_004', 0, 4.5, 42, 'O栋 9层', 9, 'O栋', '2024-09-22', NOW(), 'v2.5.1', 92, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 9, 13, '[shanshui] camera #5', 'DEV_SHANSHUI_CAMERA_005', -20, 4.5, -20, 'U栋 7层', 7, 'U栋', '2024-07-22', NOW(), 'v2.5.9', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 9, 13, '[shanshui] camera #6', 'DEV_SHANSHUI_CAMERA_006', 20, 4.5, 20, 'F栋 29层', 29, 'F栋', '2024-06-26', NOW(), 'v3.6.4', 92, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 10, 8, '[shanshui] env #1', 'DEV_SHANSHUI_ENV_001', -20, 2, 0, 'Q栋 23层', 23, 'Q栋', '2024-09-28', NOW(), 'v2.9.0', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 10, 8, '[shanshui] env #2', 'DEV_SHANSHUI_ENV_002', 20, 2, 0, 'E栋 18层', 18, 'E栋', '2024-03-10', NOW(), 'v1.3.4', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 10, 8, '[shanshui] env #3', 'DEV_SHANSHUI_ENV_003', 0, 2, -20, 'F栋 6层', 6, 'F栋', '2024-05-25', NOW(), 'v3.7.0', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 10, 8, '[shanshui] env #4', 'DEV_SHANSHUI_ENV_004', 0, 2, 20, 'H栋 4层', 4, 'H栋', '2024-07-12', NOW(), 'v1.2.6', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 10, 4, '[shanshui] env #5', 'DEV_SHANSHUI_ENV_005', 0, 2, 0, 'X栋 2层', 2, 'X栋', '2024-06-20', NOW(), 'v2.8.5', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 19, 7, '[shanshui] parking #1', 'DEV_SHANSHUI_PARKING_001', -35, 0.05, -35, 'B栋 12层', 12, 'B栋', '2024-01-23', NOW(), 'v2.6.3', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 19, 7, '[shanshui] parking #2', 'DEV_SHANSHUI_PARKING_002', -15, 0.05, -35, 'Y栋 7层', 7, 'Y栋', '2024-03-20', NOW(), 'v3.2.5', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 19, 9, '[shanshui] parking #3', 'DEV_SHANSHUI_PARKING_003', 5, 0.05, -35, 'P栋 13层', 13, 'P栋', '2024-01-28', NOW(), 'v3.7.5', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 19, 7, '[shanshui] parking #4', 'DEV_SHANSHUI_PARKING_004', 25, 0.05, -35, 'R栋 22层', 22, 'R栋', '2024-06-11', NOW(), 'v2.0.7', 79, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 19, 9, '[shanshui] parking #5', 'DEV_SHANSHUI_PARKING_005', -35, 0.05, 30, 'O栋 14层', 14, 'O栋', '2024-05-23', NOW(), 'v1.7.7', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 19, 9, '[shanshui] parking #6', 'DEV_SHANSHUI_PARKING_006', -15, 0.05, 30, 'D栋 17层', 17, 'D栋', '2024-04-19', NOW(), 'v3.9.2', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 19, 8, '[shanshui] parking #7', 'DEV_SHANSHUI_PARKING_007', 5, 0.05, 30, 'Y栋 20层', 20, 'Y栋', '2024-07-25', NOW(), 'v2.7.9', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 19, 7, '[shanshui] parking #8', 'DEV_SHANSHUI_PARKING_008', 25, 0.05, 30, 'C栋 32层', 32, 'C栋', '2024-07-13', NOW(), 'v2.5.1', 92, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 18, 8, '[shanshui] streetlight #1', 'DEV_SHANSHUI_STREETLIGHT_001', -40, 6, -35, 'D栋 8层', 8, 'D栋', '2024-03-26', NOW(), 'v2.5.4', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 18, 9, '[shanshui] streetlight #2', 'DEV_SHANSHUI_STREETLIGHT_002', 0, 6, -35, 'U栋 28层', 28, 'U栋', '2024-08-21', NOW(), 'v3.4.7', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 18, 6, '[shanshui] streetlight #3', 'DEV_SHANSHUI_STREETLIGHT_003', 40, 6, -35, 'B栋 14层', 14, 'B栋', '2024-05-25', NOW(), 'v1.9.0', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 18, 6, '[shanshui] streetlight #4', 'DEV_SHANSHUI_STREETLIGHT_004', -40, 6, 30, 'B栋 15层', 15, 'B栋', '2024-04-13', NOW(), 'v2.5.9', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 18, 6, '[shanshui] streetlight #5', 'DEV_SHANSHUI_STREETLIGHT_005', 40, 6, 30, 'U栋 23层', 23, 'U栋', '2024-08-21', NOW(), 'v1.5.1', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 3, 9, '[shanshui] gas #1', 'DEV_SHANSHUI_GAS_001', -30, 0.6, -20, 'J栋 25层', 25, 'J栋', '2024-03-15', NOW(), 'v3.5.5', 79, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 3, 6, '[shanshui] gas #2', 'DEV_SHANSHUI_GAS_002', 10, 0.6, -22, 'O栋 23层', 23, 'O栋', '2024-07-14', NOW(), 'v1.7.7', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 3, 7, '[shanshui] gas #3', 'DEV_SHANSHUI_GAS_003', -20, 0.6, 10, 'R栋 30层', 30, 'R栋', '2024-05-13', NOW(), 'v1.9.1', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 3, 9, '[shanshui] gas #4', 'DEV_SHANSHUI_GAS_004', 0, 0.6, 8, 'W栋 29层', 29, 'W栋', '2024-04-26', NOW(), 'v3.2.3', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 3, 7, '[shanshui] gas #5', 'DEV_SHANSHUI_GAS_005', 20, 0.6, 12, 'J栋 9层', 9, 'J栋', '2024-05-24', NOW(), 'v3.3.5', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 3, 7, '[shanshui] gas #6', 'DEV_SHANSHUI_GAS_006', -30, 0.6, 25, 'W栋 10层', 10, 'W栋', '2024-01-24', NOW(), 'v2.9.2', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 3, 6, '[shanshui] gas #7', 'DEV_SHANSHUI_GAS_007', 10, 0.6, 22, 'H栋 7层', 7, 'H栋', '2024-05-15', NOW(), 'v2.8.6', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 17, 9, '[shanshui] hydrant #1', 'DEV_SHANSHUI_HYDRANT_001', -40, 0.6, -35, 'N栋 10层', 10, 'N栋', '2024-02-17', NOW(), 'v2.5.4', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 17, 9, '[shanshui] hydrant #2', 'DEV_SHANSHUI_HYDRANT_002', 40, 0.6, -35, 'G栋 13层', 13, 'G栋', '2024-03-22', NOW(), 'v2.2.1', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 17, 7, '[shanshui] hydrant #3', 'DEV_SHANSHUI_HYDRANT_003', -40, 0.6, 30, 'T栋 16层', 16, 'T栋', '2024-09-20', NOW(), 'v1.9.1', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 17, 7, '[shanshui] hydrant #4', 'DEV_SHANSHUI_HYDRANT_004', 40, 0.6, 30, 'I栋 19层', 19, 'I栋', '2024-02-23', NOW(), 'v3.5.2', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 16, 4, '[shanshui] elevator #1', 'DEV_SHANSHUI_ELEVATOR_001', -30, 3, -20, 'P栋 23层', 23, 'P栋', '2024-09-19', NOW(), 'v3.2.1', 91, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 16, 4, '[shanshui] elevator #2', 'DEV_SHANSHUI_ELEVATOR_002', -10, 3, -18, 'X栋 16层', 16, 'X栋', '2024-07-20', NOW(), 'v3.3.9', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 16, 4, '[shanshui] elevator #3', 'DEV_SHANSHUI_ELEVATOR_003', -20, 3, 10, 'S栋 29层', 29, 'S栋', '2024-06-15', NOW(), 'v2.6.6', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 16, 3, '[shanshui] elevator #4', 'DEV_SHANSHUI_ELEVATOR_004', 0, 3, 8, 'F栋 18层', 18, 'F栋', '2024-01-17', NOW(), 'v2.7.4', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 16, 4, '[shanshui] elevator #5', 'DEV_SHANSHUI_ELEVATOR_005', -30, 3, 25, 'P栋 20层', 20, 'P栋', '2024-06-26', NOW(), 'v2.0.6', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 16, 10, '[shanshui] elevator #6', 'DEV_SHANSHUI_ELEVATOR_006', -10, 3, 28, 'V栋 19层', 19, 'V栋', '2024-02-23', NOW(), 'v3.4.7', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 20, 9, '[shanshui] trash #1', 'DEV_SHANSHUI_TRASH_001', -38, 1.5, -32, 'N栋 12层', 12, 'N栋', '2024-09-18', NOW(), 'v2.4.8', 74, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 20, 9, '[shanshui] trash #2', 'DEV_SHANSHUI_TRASH_002', 18, 1.5, -32, 'F栋 27层', 27, 'F栋', '2024-07-10', NOW(), 'v1.6.3', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 20, 7, '[shanshui] trash #3', 'DEV_SHANSHUI_TRASH_003', -38, 1.5, 22, 'C栋 16层', 16, 'C栋', '2024-04-16', NOW(), 'v1.3.0', 85, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (10, 20, 6, '[shanshui] trash #4', 'DEV_SHANSHUI_TRASH_004', 18, 1.5, 22, 'Q栋 32层', 32, 'Q栋', '2024-05-14', NOW(), 'v3.4.1', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 9, '[yifeng] smoke #1', 'DEV_YIFENG_SMOKE_001', -32, 2.8, -22, 'E栋 24层', 24, 'E栋', '2024-04-23', NOW(), 'v3.2.1', 79, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 6, '[yifeng] smoke #2', 'DEV_YIFENG_SMOKE_002', -5, 2.8, -20, 'C栋 26层', 26, 'C栋', '2024-08-10', NOW(), 'v1.2.7', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 7, '[yifeng] smoke #3', 'DEV_YIFENG_SMOKE_003', 26, 2.8, -18, 'R栋 29层', 29, 'R栋', '2024-09-14', NOW(), 'v2.5.3', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 7, '[yifeng] smoke #4', 'DEV_YIFENG_SMOKE_004', -36, 2.8, 2, 'T栋 26层', 26, 'T栋', '2024-07-25', NOW(), 'v1.7.6', 84, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 7, '[yifeng] smoke #5', 'DEV_YIFENG_SMOKE_005', 28, 2.8, 5, 'V栋 27层', 27, 'V栋', '2024-02-13', NOW(), 'v3.2.2', 79, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 9, '[yifeng] smoke #6', 'DEV_YIFENG_SMOKE_006', 44, 2.8, 0, 'O栋 21层', 21, 'O栋', '2024-01-10', NOW(), 'v1.8.6', 86, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 9, '[yifeng] smoke #7', 'DEV_YIFENG_SMOKE_007', -34, 2.8, 28, 'E栋 22层', 22, 'E栋', '2024-06-20', NOW(), 'v3.2.4', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 9, '[yifeng] smoke #8', 'DEV_YIFENG_SMOKE_008', -5, 2.8, 30, 'O栋 24层', 24, 'O栋', '2024-04-16', NOW(), 'v2.1.8', 97, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 1, 6, '[yifeng] smoke #9', 'DEV_YIFENG_SMOKE_009', 28, 2.8, 32, 'N栋 6层', 6, 'N栋', '2024-09-11', NOW(), 'v2.9.6', 93, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 12, '[yifeng] access #1', 'DEV_YIFENG_ACCESS_001', -56, 1.2, 5, 'J栋 22层', 22, 'J栋', '2024-01-27', NOW(), 'v1.5.5', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 4, '[yifeng] access #2', 'DEV_YIFENG_ACCESS_002', 46, 1.2, 5, 'K栋 11层', 11, 'K栋', '2024-04-22', NOW(), 'v1.9.7', 92, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 4, '[yifeng] access #3', 'DEV_YIFENG_ACCESS_003', 0, 1.2, 48, 'W栋 9层', 9, 'W栋', '2024-08-17', NOW(), 'v3.4.1', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 5, 3, '[yifeng] access #4', 'DEV_YIFENG_ACCESS_004', 0, 1.2, -38, 'S栋 31层', 31, 'S栋', '2024-07-26', NOW(), 'v1.0.3', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 7, '[yifeng] meter_w #1', 'DEV_YIFENG_METER_W_001', -32, 0.8, -22, 'B栋 4层', 4, 'B栋', '2024-05-22', NOW(), 'v1.3.1', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 4, '[yifeng] meter_w #2', 'DEV_YIFENG_METER_W_002', -5, 0.8, -20, 'X栋 26层', 26, 'X栋', '2024-05-28', NOW(), 'v2.3.1', 67, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 7, '[yifeng] meter_w #3', 'DEV_YIFENG_METER_W_003', 26, 0.8, -18, 'X栋 30层', 30, 'X栋', '2024-07-22', NOW(), 'v3.2.7', 76, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 7, '[yifeng] meter_w #4', 'DEV_YIFENG_METER_W_004', -36, 0.8, 2, 'K栋 15层', 15, 'K栋', '2024-07-11', NOW(), 'v1.9.7', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 7, '[yifeng] meter_w #5', 'DEV_YIFENG_METER_W_005', 28, 0.8, 5, 'J栋 10层', 10, 'J栋', '2024-05-28', NOW(), 'v2.6.8', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 9, '[yifeng] meter_w #6', 'DEV_YIFENG_METER_W_006', 44, 0.8, 0, 'U栋 10层', 10, 'U栋', '2024-05-10', NOW(), 'v3.4.5', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 1, '[yifeng] meter_w #7', 'DEV_YIFENG_METER_W_007', -34, 0.8, 28, 'A栋 20层', 20, 'A栋', '2024-03-21', NOW(), 'v2.3.7', 83, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 4, '[yifeng] meter_w #8', 'DEV_YIFENG_METER_W_008', -5, 0.8, 30, 'G栋 21层', 21, 'G栋', '2024-01-23', NOW(), 'v2.7.7', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 6, 9, '[yifeng] meter_w #9', 'DEV_YIFENG_METER_W_009', 28, 0.8, 32, 'L栋 15层', 15, 'L栋', '2024-03-16', NOW(), 'v1.7.1', 97, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 9, '[yifeng] meter_e #1', 'DEV_YIFENG_METER_E_001', -32, 1.6, -22, 'Y栋 27层', 27, 'Y栋', '2024-01-21', NOW(), 'v2.4.5', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 4, '[yifeng] meter_e #2', 'DEV_YIFENG_METER_E_002', -5, 1.6, -20, 'M栋 23层', 23, 'M栋', '2024-04-22', NOW(), 'v1.7.7', 85, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 7, '[yifeng] meter_e #3', 'DEV_YIFENG_METER_E_003', 26, 1.6, -18, 'R栋 32层', 32, 'R栋', '2024-01-23', NOW(), 'v3.7.2', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 7, '[yifeng] meter_e #4', 'DEV_YIFENG_METER_E_004', -36, 1.6, 2, 'R栋 23层', 23, 'R栋', '2024-02-14', NOW(), 'v3.0.3', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 12, '[yifeng] meter_e #5', 'DEV_YIFENG_METER_E_005', 28, 1.6, 5, 'Q栋 1层', 1, 'Q栋', '2024-02-24', NOW(), 'v3.2.9', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 9, '[yifeng] meter_e #6', 'DEV_YIFENG_METER_E_006', 44, 1.6, 0, 'M栋 1层', 1, 'M栋', '2024-05-17', NOW(), 'v1.7.0', 91, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 9, '[yifeng] meter_e #7', 'DEV_YIFENG_METER_E_007', -34, 1.6, 28, 'T栋 12层', 12, 'T栋', '2024-09-25', NOW(), 'v2.6.6', 82, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 12, '[yifeng] meter_e #8', 'DEV_YIFENG_METER_E_008', -5, 1.6, 30, 'L栋 3层', 3, 'L栋', '2024-09-27', NOW(), 'v3.0.9', 89, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 7, 12, '[yifeng] meter_e #9', 'DEV_YIFENG_METER_E_009', 28, 1.6, 32, 'D栋 3层', 3, 'D栋', '2024-03-19', NOW(), 'v2.4.0', 79, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 3, '[yifeng] camera #1', 'DEV_YIFENG_CAMERA_001', -58, 4.5, 5, 'K栋 5层', 5, 'K栋', '2024-04-16', NOW(), 'v3.8.2', 91, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 3, '[yifeng] camera #2', 'DEV_YIFENG_CAMERA_002', 50, 4.5, 5, 'O栋 19层', 19, 'O栋', '2024-04-27', NOW(), 'v2.8.5', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 13, '[yifeng] camera #3', 'DEV_YIFENG_CAMERA_003', -5, 4.5, 50, 'N栋 2层', 2, 'N栋', '2024-04-17', NOW(), 'v1.8.8', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 13, '[yifeng] camera #4', 'DEV_YIFENG_CAMERA_004', -5, 4.5, -42, 'T栋 3层', 3, 'T栋', '2024-08-20', NOW(), 'v1.3.1', 72, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 11, '[yifeng] camera #5', 'DEV_YIFENG_CAMERA_005', -5, 4.5, 5, 'J栋 11层', 11, 'J栋', '2024-04-22', NOW(), 'v1.5.1', 77, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 11, '[yifeng] camera #6', 'DEV_YIFENG_CAMERA_006', -30, 4.5, 5, 'Y栋 10层', 10, 'Y栋', '2024-03-26', NOW(), 'v1.2.1', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 9, 13, '[yifeng] camera #7', 'DEV_YIFENG_CAMERA_007', 25, 4.5, 5, 'S栋 28层', 28, 'S栋', '2024-08-24', NOW(), 'v3.8.4', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 8, '[yifeng] env #1', 'DEV_YIFENG_ENV_001', -30, 2, -30, 'W栋 11层', 11, 'W栋', '2024-04-20', NOW(), 'v2.8.8', 96, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 7, '[yifeng] env #2', 'DEV_YIFENG_ENV_002', 30, 2, -30, 'K栋 5层', 5, 'K栋', '2024-05-24', NOW(), 'v3.5.4', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 6, '[yifeng] env #3', 'DEV_YIFENG_ENV_003', 0, 2, 0, 'R栋 21层', 21, 'R栋', '2024-05-13', NOW(), 'v1.1.3', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 8, '[yifeng] env #4', 'DEV_YIFENG_ENV_004', -30, 2, 35, 'V栋 22层', 22, 'V栋', '2024-04-26', NOW(), 'v1.3.0', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 4, '[yifeng] env #5', 'DEV_YIFENG_ENV_005', 30, 2, 35, 'L栋 29层', 29, 'L栋', '2024-02-12', NOW(), 'v2.3.4', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 10, 7, '[yifeng] env #6', 'DEV_YIFENG_ENV_006', -5, 2, 5, 'X栋 30层', 30, 'X栋', '2024-09-19', NOW(), 'v1.0.1', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 9, '[yifeng] parking #1', 'DEV_YIFENG_PARKING_001', -40, 0.05, -35, 'G栋 2层', 2, 'G栋', '2024-04-20', NOW(), 'v2.4.9', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 8, '[yifeng] parking #2', 'DEV_YIFENG_PARKING_002', -15, 0.05, -35, 'H栋 15层', 15, 'H栋', '2024-02-23', NOW(), 'v2.8.2', 75, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 9, '[yifeng] parking #3', 'DEV_YIFENG_PARKING_003', 10, 0.05, -35, 'S栋 7层', 7, 'S栋', '2024-09-13', NOW(), 'v1.0.4', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 9, '[yifeng] parking #4', 'DEV_YIFENG_PARKING_004', 30, 0.05, -35, 'T栋 11层', 11, 'T栋', '2024-08-27', NOW(), 'v1.0.3', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 7, '[yifeng] parking #5', 'DEV_YIFENG_PARKING_005', -40, 0.05, 32, 'E栋 18层', 18, 'E栋', '2024-01-28', NOW(), 'v3.9.5', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 7, '[yifeng] parking #6', 'DEV_YIFENG_PARKING_006', -15, 0.05, 32, 'Z栋 25层', 25, 'Z栋', '2024-04-18', NOW(), 'v3.6.9', 78, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 8, '[yifeng] parking #7', 'DEV_YIFENG_PARKING_007', 10, 0.05, 32, 'G栋 29层', 29, 'G栋', '2024-04-16', NOW(), 'v3.7.2', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 19, 9, '[yifeng] parking #8', 'DEV_YIFENG_PARKING_008', 30, 0.05, 32, 'M栋 24层', 24, 'M栋', '2024-03-24', NOW(), 'v3.2.0', 74, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 9, '[yifeng] streetlight #1', 'DEV_YIFENG_STREETLIGHT_001', -50, 6, -35, 'K栋 25层', 25, 'K栋', '2024-02-25', NOW(), 'v1.0.0', 87, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 9, '[yifeng] streetlight #2', 'DEV_YIFENG_STREETLIGHT_002', 0, 6, -35, 'B栋 1层', 1, 'B栋', '2024-06-22', NOW(), 'v1.9.6', 96, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 8, '[yifeng] streetlight #3', 'DEV_YIFENG_STREETLIGHT_003', 42, 6, -35, 'H栋 18层', 18, 'H栋', '2024-09-15', NOW(), 'v1.0.6', 87, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 7, '[yifeng] streetlight #4', 'DEV_YIFENG_STREETLIGHT_004', -50, 6, 32, 'R栋 23层', 23, 'R栋', '2024-07-13', NOW(), 'v3.0.0', 74, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 18, 9, '[yifeng] streetlight #5', 'DEV_YIFENG_STREETLIGHT_005', 42, 6, 32, 'V栋 5层', 5, 'V栋', '2024-08-19', NOW(), 'v3.2.6', 97, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 7, '[yifeng] gas #1', 'DEV_YIFENG_GAS_001', -32, 0.6, -22, 'G栋 25层', 25, 'G栋', '2024-07-18', NOW(), 'v1.4.7', 68, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 7, '[yifeng] gas #2', 'DEV_YIFENG_GAS_002', -5, 0.6, -20, 'H栋 23层', 23, 'H栋', '2024-09-20', NOW(), 'v1.3.8', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 9, '[yifeng] gas #3', 'DEV_YIFENG_GAS_003', 26, 0.6, -18, 'Y栋 23层', 23, 'Y栋', '2024-04-16', NOW(), 'v1.0.7', 69, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 6, '[yifeng] gas #4', 'DEV_YIFENG_GAS_004', -36, 0.6, 2, 'B栋 23层', 23, 'B栋', '2024-06-14', NOW(), 'v2.5.0', 94, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 9, '[yifeng] gas #5', 'DEV_YIFENG_GAS_005', 28, 0.6, 5, 'A栋 32层', 32, 'A栋', '2024-02-25', NOW(), 'v3.5.8', 66, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 7, '[yifeng] gas #6', 'DEV_YIFENG_GAS_006', -34, 0.6, 28, 'P栋 10层', 10, 'P栋', '2024-09-18', NOW(), 'v3.9.0', 98, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 9, '[yifeng] gas #7', 'DEV_YIFENG_GAS_007', -5, 0.6, 30, 'K栋 23层', 23, 'K栋', '2024-05-27', NOW(), 'v1.0.2', 80, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 3, 9, '[yifeng] gas #8', 'DEV_YIFENG_GAS_008', 28, 0.6, 32, 'U栋 25层', 25, 'U栋', '2024-03-13', NOW(), 'v2.7.2', 96, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 7, '[yifeng] hydrant #1', 'DEV_YIFENG_HYDRANT_001', -50, 0.6, -35, 'B栋 8层', 8, 'B栋', '2024-06-26', NOW(), 'v1.2.2', 96, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 7, '[yifeng] hydrant #2', 'DEV_YIFENG_HYDRANT_002', 42, 0.6, -35, 'J栋 13层', 13, 'J栋', '2024-05-14', NOW(), 'v3.2.2', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 9, '[yifeng] hydrant #3', 'DEV_YIFENG_HYDRANT_003', -50, 0.6, 32, 'V栋 3层', 3, 'V栋', '2024-07-11', NOW(), 'v2.2.8', 88, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 17, 7, '[yifeng] hydrant #4', 'DEV_YIFENG_HYDRANT_004', 42, 0.6, 32, 'A栋 29层', 29, 'A栋', '2024-05-12', NOW(), 'v2.2.0', 97, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 4, '[yifeng] elevator #1', 'DEV_YIFENG_ELEVATOR_001', -32, 3, -22, 'P栋 13层', 13, 'P栋', '2024-02-17', NOW(), 'v2.7.7', 81, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 10, '[yifeng] elevator #2', 'DEV_YIFENG_ELEVATOR_002', -5, 3, -20, 'N栋 15层', 15, 'N栋', '2024-03-19', NOW(), 'v1.8.1', 70, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 1, '[yifeng] elevator #3', 'DEV_YIFENG_ELEVATOR_003', -36, 3, 2, 'W栋 18层', 18, 'W栋', '2024-02-25', NOW(), 'v3.9.1', 100, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 4, '[yifeng] elevator #4', 'DEV_YIFENG_ELEVATOR_004', 28, 3, 5, 'U栋 17层', 17, 'U栋', '2024-04-16', NOW(), 'v2.3.3', 65, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 10, '[yifeng] elevator #5', 'DEV_YIFENG_ELEVATOR_005', -34, 3, 28, 'H栋 13层', 13, 'H栋', '2024-07-10', NOW(), 'v2.8.1', 71, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 16, 4, '[yifeng] elevator #6', 'DEV_YIFENG_ELEVATOR_006', -5, 3, 30, 'T栋 30层', 30, 'T栋', '2024-04-19', NOW(), 'v2.9.5', 73, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 20, 7, '[yifeng] trash #1', 'DEV_YIFENG_TRASH_001', -48, 1.5, -32, 'Q栋 4层', 4, 'Q栋', '2024-01-13', NOW(), 'v2.2.5', 99, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 20, 9, '[yifeng] trash #2', 'DEV_YIFENG_TRASH_002', 20, 1.5, -32, 'D栋 16层', 16, 'D栋', '2024-08-21', NOW(), 'v1.2.1', 95, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 20, 7, '[yifeng] trash #3', 'DEV_YIFENG_TRASH_003', -48, 1.5, 25, 'C栋 3层', 3, 'C栋', '2024-06-17', NOW(), 'v1.7.2', 90, 1);
INSERT INTO `ds_iot_device` (community_id, device_type_id, protocol_id, name, code, x, y, z, install_location, floor, building, install_date, last_online, firmware_ver, battery_level, status) VALUES (8, 20, 6, '[yifeng] trash #4', 'DEV_YIFENG_TRASH_004', 20, 1.5, 25, 'K栋 15层', 15, 'K栋', '2024-03-24', NOW(), 'v1.8.4', 100, 1);

-- 实时数据 (每批500条)
INSERT INTO `ds_iot_device_data` (device_id, value, raw_value, unit, is_online, device_status, alarm_msg, data_time) VALUES
(1, 123, '123 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(2, 154, '154 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(3, 137, '137 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(4, 190, '190 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(5, 193, '193 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(6, 185, '185 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(7, 184, '184 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(8, 80, '80 ppm', 'ppm', 0, 'offline', '', '2026-06-08 12:46:15'),
(9, 109, '109 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(10, 82, '82 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(11, 151, '151 ppm', 'ppm', 1, 'warning', '', '2026-06-08 12:46:15'),
(12, 167, '167 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(13, 57, '57 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(14, 113, '113 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(15, 323, '正常 · 323次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(16, 135, '正常 · 135次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(17, 147, '正常 · 147次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(18, 240, '正常 · 240次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(19, 445, '正常 · 445次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(20, 342, '正常 · 342次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(21, 321, '正常 · 321次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(22, 480, '正常 · 480次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(23, 365, '正常 · 365次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(24, 38.7, '38.7 吨', '吨', 1, 'alarm', '管道泄漏疑似', '2026-06-08 12:46:15'),
(25, 76.3, '76.3 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(26, 119.5, '119.5 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(27, 115.3, '115.3 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(28, 81.2, '81.2 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(29, 125.2, '125.2 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(30, 103.3, '103.3 吨', '吨', 0, 'offline', '', '2026-06-08 12:46:15'),
(31, 101.1, '101.1 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(32, 52.5, '52.5 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(33, 81.6, '81.6 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(34, 139.1, '139.1 kWh', 'kWh', 1, 'alarm', '线路异常', '2026-06-08 12:46:15'),
(35, 107.6, '107.6 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(36, 252.0, '252.0 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(37, 236.3, '236.3 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(38, 96.1, '96.1 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(39, 167.3, '167.3 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(40, 303.4, '303.4 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(41, 95.1, '95.1 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(42, 907, '在线 · 人脸抓拍907次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(43, 2926, '在线 · 人脸抓拍2926次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(44, 1461, '在线 · 人脸抓拍1461次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(45, 3007, '在线 · 人脸抓拍3007次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(46, 824, '在线 · 人脸抓拍824次', '', 0, 'offline', '', '2026-06-08 12:46:15'),
(47, 2900, '在线 · 人脸抓拍2900次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(48, 3139, '在线 · 人脸抓拍3139次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(49, 1320, '在线 · 人脸抓拍1320次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(50, 20, '20°C / 76%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(51, 35, '35°C / 58%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(52, 19, '19°C / 48%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(53, 19, '19°C / 65%', '°C/%', 1, 'warning', '', '2026-06-08 12:46:15'),
(54, 30, '30°C / 60%', '°C/%', 1, 'warning', '', '2026-06-08 12:46:15'),
(55, 316, '占用 · 316分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(56, 230, '空闲 · 230分钟', '', 1, 'alarm', '车牌识别失败', '2026-06-08 12:46:15'),
(57, 455, '占用 · 455分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(58, 154, '空闲 · 154分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(59, 167, '空闲 · 167分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(60, 38, '占用 · 38分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(61, 92, '占用 · 92分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(62, 350, '空闲 · 350分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(63, 277, '空闲 · 277分钟', '', 1, 'alarm', '车位占用异常', '2026-06-08 12:46:15'),
(64, 177, '占用 · 177分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(65, 216, '空闲 · 216分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(66, 171, '空闲 · 171分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(67, 37, '亮度37% · 10.9W', '%', 1, 'warning', '', '2026-06-08 12:46:15'),
(68, 32, '亮度32% · 24.9W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(69, 56, '亮度56% · 17.9W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(70, 83, '亮度83% · 14.2W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(71, 84, '亮度84% · 32.2W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(72, 53, '亮度53% · 29.5W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(73, 49, '亮度49% · 35.8W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(74, 32, '亮度32% · 31.6W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(75, 79.2, '79.2 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(76, 64.5, '64.5 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(77, 81.0, '81.0 m³', 'm³', 1, 'warning', '', '2026-06-08 12:46:15'),
(78, 23.5, '23.5 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(79, 48.7, '48.7 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(80, 22.2, '22.2 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(81, 54.6, '54.6 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(82, 58.4, '58.4 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(83, 84.4, '84.4 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(84, 0.67, '0.67 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(85, 0.39, '0.39 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(86, 0.25, '0.25 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(87, 0.27, '0.27 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(88, 0.50, '0.50 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(89, 0.35, '0.35 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(90, 26, '26层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(91, 25, '25层 · 等待维护', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(92, 14, '14层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(93, 3, '3层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(94, 25, '25层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(95, 15, '15层 · 运行正常', '', 0, 'offline', '', '2026-06-08 12:46:15'),
(96, 1, '1层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(97, 38, '38% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(98, 31, '31% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(99, 87, '87% · 正常', '%', 1, 'alarm', '垃圾桶满溢告警', '2026-06-08 12:46:15'),
(100, 80, '80% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(101, 52, '52 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(102, 120, '120 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(103, 77, '77 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(104, 90, '90 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(105, 93, '93 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(106, 177, '177 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(107, 167, '167 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(108, 99, '99 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(109, 143, '143 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(110, 196, '196 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(111, 105, '105 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(112, 182, '182 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(113, 504, '正常 · 504次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(114, 373, '正常 · 373次通行', '', 1, 'warning', '', '2026-06-08 12:46:15'),
(115, 271, '正常 · 271次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(116, 358, '正常 · 358次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(117, 49.7, '49.7 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(118, 66.7, '66.7 吨', '吨', 1, 'warning', '', '2026-06-08 12:46:15'),
(119, 58.9, '58.9 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(120, 72.5, '72.5 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(121, 62.6, '62.6 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(122, 91.8, '91.8 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(123, 241.1, '241.1 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(124, 153.7, '153.7 kWh', 'kWh', 1, 'warning', '', '2026-06-08 12:46:15'),
(125, 226.9, '226.9 kWh', 'kWh', 1, 'alarm', '用电负荷过载', '2026-06-08 12:46:15'),
(126, 199.9, '199.9 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(127, 153.4, '153.4 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(128, 266.7, '266.7 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(129, 1979, '在线 · 人脸抓拍1979次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(130, 3007, '在线 · 人脸抓拍3007次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(131, 2051, '在线 · 人脸抓拍2051次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(132, 1516, '在线 · 人脸抓拍1516次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(133, 975, '在线 · 人脸抓拍975次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(134, 2979, '在线 · 人脸抓拍2979次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(135, 24, '24°C / 63%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(136, 22, '22°C / 41%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(137, 22, '22°C / 57%', '°C/%', 1, 'alarm', '温度异常偏高', '2026-06-08 12:46:15'),
(138, 35, '35°C / 64%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(139, 32, '32°C / 61%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(140, 153, '占用 · 153分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(141, 84, '占用 · 84分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(142, 187, '占用 · 187分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(143, 153, '占用 · 153分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(144, 152, '占用 · 152分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(145, 257, '占用 · 257分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(146, 154, '占用 · 154分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(147, 148, '占用 · 148分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(148, 393, '空闲 · 393分钟', '', 0, 'offline', '', '2026-06-08 12:46:15'),
(149, 190, '占用 · 190分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(150, 97, '亮度97% · 27.5W', '%', 1, 'alarm', '灯具故障', '2026-06-08 12:46:15'),
(151, 37, '亮度37% · 9.4W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(152, 89, '亮度89% · 12.7W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(153, 55, '亮度55% · 12.7W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(154, 32, '亮度32% · 38.8W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(155, 31.2, '31.2 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(156, 44.0, '44.0 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(157, 70.2, '70.2 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(158, 84.3, '84.3 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(159, 20.0, '20.0 m³', 'm³', 0, 'offline', '', '2026-06-08 12:46:15'),
(160, 63.9, '63.9 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(161, 0.65, '0.65 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(162, 0.36, '0.36 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(163, 0.60, '0.60 MPa', 'MPa', 1, 'alarm', '消防栓异常位移', '2026-06-08 12:46:15'),
(164, 0.54, '0.54 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(165, 15, '15层 · 等待维护', '', 1, 'warning', '', '2026-06-08 12:46:15'),
(166, 5, '5层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(167, 10, '10层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(168, 23, '23层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(169, 12, '12层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(170, 17, '17层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(171, 63, '63% · 正常', '%', 0, 'offline', '', '2026-06-08 12:46:15'),
(172, 46, '46% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(173, 26, '26% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(174, 89, '89% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(175, 140, '140 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(176, 191, '191 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(177, 160, '160 ppm', 'ppm', 1, 'warning', '', '2026-06-08 12:46:15'),
(178, 194, '194 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(179, 56, '56 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(180, 75, '75 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(181, 183, '正常 · 183次通行', '', 1, 'alarm', '门禁异常开启', '2026-06-08 12:46:15'),
(182, 200, '正常 · 200次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(183, 516, '正常 · 516次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(184, 182, '正常 · 182次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(185, 540, '正常 · 540次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(186, 338, '正常 · 338次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(187, 61.6, '61.6 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(188, 116.4, '116.4 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(189, 66.8, '66.8 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(190, 114.8, '114.8 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(191, 80.2, '80.2 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(192, 86.3, '86.3 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(193, 186.7, '186.7 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(194, 126.6, '126.6 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(195, 118.3, '118.3 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(196, 177.0, '177.0 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(197, 233.8, '233.8 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(198, 86.1, '86.1 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(199, 1418, '在线 · 人脸抓拍1418次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(200, 2589, '在线 · 人脸抓拍2589次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(201, 3149, '在线 · 人脸抓拍3149次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(202, 1971, '在线 · 人脸抓拍1971次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(203, 652, '在线 · 人脸抓拍652次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(204, 34, '34°C / 47%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(205, 28, '28°C / 67%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(206, 33, '33°C / 75%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(207, 26, '26°C / 43%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(208, 34, '34°C / 78%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(209, 369, '空闲 · 369分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(210, 214, '占用 · 214分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(211, 388, '占用 · 388分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(212, 459, '占用 · 459分钟', '', 1, 'alarm', '车牌识别失败', '2026-06-08 12:46:15'),
(213, 337, '占用 · 337分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(214, 146, '占用 · 146分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(215, 144, '占用 · 144分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(216, 315, '空闲 · 315分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(217, 72, '占用 · 72分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(218, 378, '占用 · 378分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(219, 57, '亮度57% · 18.0W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(220, 57, '亮度57% · 34.7W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(221, 82, '亮度82% · 32.2W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(222, 66, '亮度66% · 23.0W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(223, 51, '亮度51% · 16.5W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(224, 78.4, '78.4 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(225, 43.4, '43.4 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(226, 32.2, '32.2 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(227, 39.1, '39.1 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(228, 70.1, '70.1 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(229, 44.0, '44.0 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(230, 0.71, '0.71 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(231, 0.25, '0.25 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(232, 0.55, '0.55 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(233, 0.31, '0.31 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(234, 15, '15层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(235, 5, '5层 · 运行正常', '', 0, 'offline', '', '2026-06-08 12:46:15'),
(236, 13, '13层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(237, 22, '22层 · 运行正常', '', 1, 'warning', '', '2026-06-08 12:46:15'),
(238, 58, '58% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(239, 43, '43% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(240, 27, '27% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(241, 43, '43% · 即将满溢', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(242, 154, '154 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(243, 89, '89 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(244, 163, '163 ppm', 'ppm', 0, 'offline', '', '2026-06-08 12:46:15'),
(245, 178, '178 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(246, 80, '80 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(247, 122, '122 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(248, 78, '78 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(249, 172, '172 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(250, 87, '87 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(251, 150, '150 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(252, 143, '143 ppm', 'ppm', 1, 'alarm', '探测器触发预警', '2026-06-08 12:46:15'),
(253, 207, '正常 · 207次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(254, 236, '正常 · 236次通行', '', 1, 'alarm', '陌生人尾随告警', '2026-06-08 12:46:15'),
(255, 334, '正常 · 334次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(256, 45.1, '45.1 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(257, 98.8, '98.8 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(258, 103.6, '103.6 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(259, 122.1, '122.1 吨', '吨', 1, 'warning', '', '2026-06-08 12:46:15'),
(260, 31.9, '31.9 吨', '吨', 1, 'warning', '', '2026-06-08 12:46:15'),
(261, 74.6, '74.6 吨', '吨', 1, 'warning', '', '2026-06-08 12:46:15'),
(262, 58.0, '58.0 吨', '吨', 1, 'warning', '', '2026-06-08 12:46:15'),
(263, 118.0, '118.0 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(264, 184.2, '184.2 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(265, 149.3, '149.3 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(266, 194.9, '194.9 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(267, 1093, '在线 · 人脸抓拍1093次', '', 1, 'alarm', '移动侦测告警', '2026-06-08 12:46:15'),
(268, 1861, '在线 · 人脸抓拍1861次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(269, 1125, '在线 · 人脸抓拍1125次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(270, 2681, '在线 · 人脸抓拍2681次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(271, 823, '在线 · 人脸抓拍823次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(272, 666, '在线 · 人脸抓拍666次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(273, 32, '32°C / 70%', '°C/%', 0, 'offline', '', '2026-06-08 12:46:15'),
(274, 34, '34°C / 79%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(275, 27, '27°C / 71%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(276, 19, '19°C / 40%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(277, 20, '20°C / 66%', '°C/%', 1, 'warning', '', '2026-06-08 12:46:15'),
(278, 241, '占用 · 241分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(279, 294, '占用 · 294分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(280, 72, '空闲 · 72分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(281, 460, '占用 · 460分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(282, 287, '空闲 · 287分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(283, 141, '占用 · 141分钟', '', 1, 'warning', '', '2026-06-08 12:46:15'),
(284, 275, '占用 · 275分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(285, 129, '占用 · 129分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(286, 99, '亮度99% · 34.5W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(287, 37, '亮度37% · 26.6W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(288, 60, '亮度60% · 9.2W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(289, 40, '亮度40% · 31.7W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(290, 98, '亮度98% · 22.1W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(291, 15.7, '15.7 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(292, 53.3, '53.3 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(293, 78.2, '78.2 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(294, 28.0, '28.0 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(295, 30.1, '30.1 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(296, 41.9, '41.9 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(297, 26.8, '26.8 m³', 'm³', 1, 'warning', '', '2026-06-08 12:46:15'),
(298, 0.61, '0.61 MPa', 'MPa', 0, 'offline', '', '2026-06-08 12:46:15'),
(299, 0.26, '0.26 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(300, 0.54, '0.54 MPa', 'MPa', 0, 'offline', '', '2026-06-08 12:46:15'),
(301, 0.33, '0.33 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(302, 31, '31层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(303, 22, '22层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(304, 12, '12层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(305, 13, '13层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(306, 12, '12层 · 等待维护', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(307, 10, '10层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(308, 66, '66% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(309, 38, '38% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(310, 60, '60% · 即将满溢', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(311, 58, '58% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(312, 197, '197 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(313, 63, '63 ppm', 'ppm', 0, 'offline', '', '2026-06-08 12:46:15'),
(314, 92, '92 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(315, 103, '103 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(316, 111, '111 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(317, 91, '91 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(318, 100, '100 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(319, 83, '83 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(320, 82, '82 ppm', 'ppm', 1, 'normal', '', '2026-06-08 12:46:15'),
(321, 163, '正常 · 163次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(322, 188, '正常 · 188次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(323, 338, '正常 · 338次通行', '', 1, 'alarm', '门禁异常开启', '2026-06-08 12:46:15'),
(324, 299, '正常 · 299次通行', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(325, 35.0, '35.0 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(326, 90.2, '90.2 吨', '吨', 1, 'warning', '', '2026-06-08 12:46:15'),
(327, 123.5, '123.5 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(328, 48.7, '48.7 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(329, 90.6, '90.6 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(330, 28.1, '28.1 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(331, 120.3, '120.3 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(332, 105.5, '105.5 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(333, 80.3, '80.3 吨', '吨', 1, 'normal', '', '2026-06-08 12:46:15'),
(334, 205.4, '205.4 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(335, 52.4, '52.4 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(336, 170.8, '170.8 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(337, 268.1, '268.1 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(338, 238.9, '238.9 kWh', 'kWh', 1, 'warning', '', '2026-06-08 12:46:15'),
(339, 274.3, '274.3 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(340, 77.3, '77.3 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(341, 66.3, '66.3 kWh', 'kWh', 0, 'offline', '', '2026-06-08 12:46:15'),
(342, 214.4, '214.4 kWh', 'kWh', 1, 'normal', '', '2026-06-08 12:46:15'),
(343, 3170, '在线 · 人脸抓拍3170次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(344, 2142, '在线 · 人脸抓拍2142次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(345, 1628, '在线 · 人脸抓拍1628次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(346, 2744, '在线 · 人脸抓拍2744次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(347, 1258, '在线 · 人脸抓拍1258次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(348, 1239, '在线 · 人脸抓拍1239次', '', 0, 'offline', '', '2026-06-08 12:46:15'),
(349, 2284, '在线 · 人脸抓拍2284次', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(350, 26, '26°C / 54%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(351, 32, '32°C / 72%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(352, 22, '22°C / 82%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(353, 21, '21°C / 81%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(354, 19, '19°C / 54%', '°C/%', 1, 'normal', '', '2026-06-08 12:46:15'),
(355, 25, '25°C / 62%', '°C/%', 1, 'warning', '', '2026-06-08 12:46:15'),
(356, 145, '空闲 · 145分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(357, 217, '空闲 · 217分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(358, 219, '占用 · 219分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(359, 90, '占用 · 90分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(360, 106, '占用 · 106分钟', '', 0, 'offline', '', '2026-06-08 12:46:15'),
(361, 294, '占用 · 294分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(362, 373, '占用 · 373分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(363, 198, '占用 · 198分钟', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(364, 38, '亮度38% · 30.1W', '%', 0, 'offline', '', '2026-06-08 12:46:15'),
(365, 55, '亮度55% · 11.5W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(366, 58, '亮度58% · 31.1W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(367, 86, '亮度86% · 11.6W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(368, 78, '亮度78% · 19.6W', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(369, 81.2, '81.2 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(370, 48.1, '48.1 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(371, 54.9, '54.9 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(372, 79.6, '79.6 m³', 'm³', 0, 'offline', '', '2026-06-08 12:46:15'),
(373, 33.4, '33.4 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(374, 15.9, '15.9 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(375, 48.3, '48.3 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(376, 49.8, '49.8 m³', 'm³', 1, 'normal', '', '2026-06-08 12:46:15'),
(377, 0.28, '0.28 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(378, 0.69, '0.69 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(379, 0.71, '0.71 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(380, 0.60, '0.60 MPa', 'MPa', 1, 'normal', '', '2026-06-08 12:46:15'),
(381, 25, '25层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(382, 20, '20层 · 运行正常', '', 1, 'warning', '', '2026-06-08 12:46:15'),
(383, 6, '6层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(384, 30, '30层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(385, 10, '10层 · 运行正常', '', 1, 'normal', '', '2026-06-08 12:46:15'),
(386, 13, '13层 · 运行正常', '', 0, 'offline', '', '2026-06-08 12:46:15'),
(387, 51, '51% · 即将满溢', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(388, 66, '66% · 即将满溢', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(389, 58, '58% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15'),
(390, 79, '79% · 正常', '%', 1, 'normal', '', '2026-06-08 12:46:15');
