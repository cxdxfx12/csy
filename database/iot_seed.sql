-- ============================================
-- IoT 种子数据：协议、设备类型、设备实例、模拟数据
-- ============================================

-- ==============================
-- 通信协议 (13种)
-- ==============================
INSERT INTO `ds_iot_protocol` (`id`, `name`, `code`, `type`, `description`, `transport`, `port`, `frequency_band`, `data_rate`, `range`, `sort`) VALUES
(1, 'MQTT 3.1.1', 'mqtt', 'wired', '轻量级发布/订阅消息传输协议，广泛用于IoT低带宽场景', 'TCP', '1883', '', '256Kbps', '不限', 1),
(2, 'CoAP (RFC 7252)', 'coap', 'wired', '受限应用协议，基于UDP，类似HTTP的RESTful模型', 'UDP', '5683', '', '250Kbps', '不限', 2),
(3, 'HTTP/HTTPS REST', 'http', 'wired', '标准HTTP REST API，适合高带宽设备', 'TCP', '443', '', '100Mbps', '不限', 3),
(4, 'Modbus RTU/TCP', 'modbus', 'wired', '工业控制领域最广泛的串行通信协议', 'Serial/TCP', '502', '', '115200bps', '1.2km(RS485)', 4),
(5, 'BACnet/IP', 'bacnet', 'wired', '楼宇自控与HVAC系统标准协议', 'UDP', '47808', '', '100Mbps', '不限', 5),
(6, 'Zigbee 3.0', 'zigbee', 'wireless', '低功耗短距离无线mesh网络协议', '802.15.4', '', '2.4GHz', '250Kbps', '100m', 6),
(7, 'LoRaWAN 1.0.3', 'lorawan', 'wireless', '长距离低功耗广域网协议，适合传感器', 'LoRa', '', '470-510MHz / 868/915MHz', '0.3-50Kbps', '2-15km', 7),
(8, 'Bluetooth BLE 5.0', 'ble', 'wireless', '低功耗蓝牙，近距离设备通信', 'BLE', '', '2.4GHz', '2Mbps', '100m', 8),
(9, 'NB-IoT', 'nbiot', 'wireless', '窄带物联网，基于蜂窝网络的LPWAN技术', 'LTE', '', 'B5/B8/B20', '250Kbps', '10km+', 9),
(10, 'OPC UA', 'opcua', 'wired', '工业4.0互操作性标准，平台独立安全通信', 'TCP', '4840', '', '100Mbps', '不限', 10),
(11, 'Wi-Fi 6 (802.11ax)', 'wifi', 'wireless', '高速无线局域网，适合大带宽IoT设备', 'TCP/UDP', '', '2.4/5/6GHz', '9.6Gbps', '100m', 11),
(12, 'RS-485', 'rs485', 'wired', '工业差分串行总线，支持多点通信', 'Serial', '', '', '10Mbps', '1.2km', 12),
(13, '4G LTE Cat.1', 'lte_cat1', 'wireless', '中速蜂窝通信，适合视频监控类设备', 'LTE', '', 'B1/B3/B5/B8', '10Mbps', '2-5km', 13);

-- ==============================
-- 设备类型 (21种)
-- ==============================
INSERT INTO `ds_iot_device_type` (`id`, `code`, `name`, `category`, `icon`, `unit`, `normal_range`, `alarm_high`, `alarm_low`, `warning_high`, `warning_low`, `protocol_support`, `y_height`, `description`, `sort`) VALUES
(1, 'smoke', '烟感探测器', 'fire', 'smoke', 'ppm', '0-300ppm', 500.00, NULL, 300.00, NULL, 'lorawan,zigbee,nbiot', 2.80, '光电式烟雾浓度探测，用于火灾预警', 1),
(2, 'heat', '温感探测器', 'fire', 'heat', '°C', '10-55°C', 70.00, NULL, 57.00, NULL, 'lorawan,modbus,nbiot', 2.80, '定温/差温火灾探测，机房、厨房等高热区域', 2),
(3, 'gas', '燃气泄漏探测器', 'fire', 'gas', '%LEL', '0-5%LEL', 25.00, NULL, 10.00, NULL, 'zigbee,lorawan,nbiot', 0.60, '甲烷/天然气浓度检测，厨房防泄漏', 3),
(4, 'co', 'CO探测器', 'fire', 'co', 'ppm', '0-24ppm', 50.00, NULL, 35.00, NULL, 'zigbee,lorawan,nbiot', 1.60, '一氧化碳浓度检测，地下车库/锅炉房', 4),
(5, 'access', '门禁控制器', 'access', 'access', '次', '', NULL, NULL, NULL, NULL, 'http,wifi,mqtt,rs485', 1.20, '人脸/指纹/刷卡门禁，含出入计数', 5),
(6, 'meter_w', '智能水表', 'energy', 'water', '吨', '0-99999t', NULL, NULL, NULL, NULL, 'lorawan,nbiot,modbus,mqtt', 0.80, '超声波/电磁水表，远程抄表', 6),
(7, 'meter_e', '智能电表', 'energy', 'electric', 'kWh', '0-99999kWh', NULL, NULL, NULL, NULL, 'lorawan,nbiot,modbus,mqtt,rs485', 1.60, '三相/单相智能电表，支持远程通断', 7),
(8, 'meter_g', '智能燃气表', 'energy', 'gas_meter', 'm³', '0-99999m³', NULL, NULL, NULL, NULL, 'lorawan,nbiot,zigbee', 0.80, '超声波燃气表，远程抄表+阀控', 8),
(9, 'camera', '安防摄像头', 'camera', 'camera', '次', '', NULL, NULL, NULL, NULL, 'http,wifi,lte_cat1', 4.50, 'AI智能摄像头，人脸抓拍/区域入侵/车牌识别', 9),
(10, 'env', '温湿度传感器', 'env', 'env', '°C/%', '18-28°C/30-70%', 40.00, -10.00, 30.00, 15.00, 'lorawan,zigbee,ble,modbus', 2.00, '环境温湿度监测', 10),
(11, 'pm25', 'PM2.5传感器', 'env', 'pm25', 'μg/m³', '0-75μg/m³', 150.00, NULL, 75.00, NULL, 'lorawan,zigbee,modbus,wifi', 2.00, '激光散射式PM2.5/PM10检测', 11),
(12, 'noise', '噪音传感器', 'env', 'noise', 'dB', '30-60dB', 85.00, NULL, 70.00, NULL, 'lorawan,zigbee,modbus', 2.00, '环境噪声分贝检测', 12),
(13, 'flood', '水浸传感器', 'water', 'flood', '', '无水浸', NULL, NULL, NULL, NULL, 'lorawan,zigbee,nbiot,modbus', 0.20, '电极式/光电式水浸检测，机房/地下室', 13),
(14, 'level', '液位传感器', 'water', 'level', 'm', '0-5m', 4.50, 0.20, 4.00, 0.50, 'lorawan,modbus,rs485', 1.00, '超声波/雷达液位计，水箱/污水池', 14),
(15, 'pressure', '水压传感器', 'water', 'pressure', 'MPa', '0.2-0.6MPa', 0.80, 0.10, 0.60, 0.15, 'lorawan,modbus,rs485', 1.00, '管网水压监测，消防/给水系统', 15),
(16, 'elevator', '电梯运行监测', 'facility', 'elevator', '', '', NULL, NULL, NULL, NULL, 'http,mqtt,modbus,rs485,opcua', 3.00, '电梯运行状态、楼层、故障告警', 16),
(17, 'hydrant', '消防栓监测', 'fire', 'hydrant', 'MPa', '0.2-0.8MPa', 0.90, 0.10, 0.80, 0.20, 'lorawan,nbiot', 0.60, '室外消防栓水压+倾斜/撞击监测', 17),
(18, 'streetlight', '智能路灯', 'lighting', 'light', '%', '0-100%', NULL, NULL, NULL, NULL, 'lorawan,zigbee,nbiot,ble', 6.00, '智能调光/故障自检/能耗统计路灯', 18),
(19, 'parking', '地磁车位检测', 'parking', 'parking', '', '占用/空闲', NULL, NULL, NULL, NULL, 'lorawan,nbiot,ble', 0.05, '地磁/雷达车位占用检测', 19),
(20, 'trash', '垃圾满溢检测', 'env', 'trash', '%', '0-80%', 95.00, NULL, 80.00, NULL, 'lorawan,nbiot,zigbee', 1.50, '超声波/红外垃圾桶满溢检测', 20),
(21, 'manhole', '井盖位移监测', 'safety', 'manhole', '', '正常/异常', NULL, NULL, NULL, NULL, 'lorawan,nbiot', 0.10, '倾角+位移井盖安全监测，防盗窃/移位', 21);
