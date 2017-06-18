-- 增加一个字段openid
alter table user add openid varchar(256);
-- 添加openid的索引
alter table user add index openid_index(openid);
-- 添加openid的注释
ALTER TABLE `user` CHANGE `openid` `openid` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '用户的openid,用来辨别用户身份';
-- 添加商品表
CREATE TABLE `goods` (
 `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品的id',
 `title` varchar(256) COLLATE utf8_unicode_ci NOT NULL COMMENT '商品的标题',
 `description` varchar(256) COLLATE utf8_unicode_ci NOT NULL COMMENT '商品的描述',
 `origin_price` decimal(10,2) NOT NULL COMMENT '商品的原价',
 `price` decimal(10,2) NOT NULL COMMENT '商品的现价',
 `start_time` int(11) NOT NULL COMMENT '商品开卖的时间',
 `end_time` int(11) NOT NULL COMMENT '商品的结束时间',
 `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '当前时间',
 `detail` text COLLATE utf8_unicode_ci NOT NULL COMMENT '图文详情',
 `goods_img` varchar(256) COLLATE utf8_unicode_ci NOT NULL COMMENT '商品的图片链接',
 `classes_id` tinyint(4) NOT NULL DEFAULT '0' COMMENT '商品分类',
 PRIMARY KEY (`id`),
 KEY `start_time` (`start_time`),
 KEY `end_time` (`end_time`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='首页的商品信息表';
-- 添加商品分类表
CREATE TABLE `aps`.`goods_classes` ( `id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(256) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT = '商品分类表';
-- 添加商品轮播图片表
CREATE TABLE `aps`.`goods_img` ( `id` INT NOT NULL AUTO_INCREMENT , `goods_id` INT NOT NULL , `goods_img` VARCHAR(256) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT = '商品轮播图片表';
-- 增添state字段到商品分类表
ALTER TABLE `goods_classes` ADD `state` TINYINT NOT NULL DEFAULT '1' COMMENT '1正常0过期' AFTER `name`;
-- 修改商品表classes字段的名为classes_id
ALTER TABLE `goods` CHANGE `classes` `classes_id` TINYINT(4) NOT NULL COMMENT '商品分类';
--允许账号密码为空
ALTER TABLE `user` CHANGE `username` `username` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_bin NULL COMMENT '账号';
ALTER TABLE `user` CHANGE `passwd` `passwd` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL COMMENT '密码';
--添加订单表
CREATE TABLE `orders` (
 `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
 `order_num` varchar(256) COLLATE utf8_unicode_ci NOT NULL COMMENT '订单编号',
 `addr_id` int(11) NOT NULL COMMENT '收货地址ID',
 `send_time` int(11) NOT NULL COMMENT '预计发货时间',
 `time_space` int(11) NOT NULL COMMENT '预计几天到货',
 `send_price` int(11) NOT NULL COMMENT '配送费用',
 `coupon_id` int(11) DEFAULT NULL COMMENT '优惠券ID',
 `agent_id` int(11) NOT NULL COMMENT '代理ID',
 `pay_status` int(11) NOT NULL COMMENT '支付状态；已支付和未支付',
 `pay_by` int(11) NOT NULL COMMENT '支付方式',
 `pay_time` int(11) NOT NULL COMMENT '支付时间',
 `order_status` int(11) NOT NULL COMMENT '订单状态。包含待付款，未发货，待收获，已取消，已删除',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='订单表';
--添加购物车表
CREATE TABLE `goods_car` (
 `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '购物车ID',
 `Goods_id` int(11) NOT NULL COMMENT '商品ID',
 `Goods_num` int(11) NOT NULL COMMENT '商品数量',
 `state` int(2) NOT NULL DEFAULT '0' COMMENT '购物车状态',
 PRIMARY KEY (`id`),
 KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='购物车表';
--添加收获地址表
CREATE TABLE `address` (
 `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '收货地址id',
 `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT '收货人姓名',
 `sex` int(1) NOT NULL DEFAULT '0' COMMENT '收货人性别',
 `tel` int(15) NOT NULL COMMENT '收货人的电话',
 `location` varchar(256) COLLATE utf8_unicode_ci NOT NULL COMMENT '收货人的详细地址',
 `openid` varchar(256) COLLATE utf8_unicode_ci NOT NULL COMMENT '用户的openid',
 `province_id` int(11) NOT NULL COMMENT '省ID',
 `city_id` int(11) NOT NULL COMMENT '市ID',
 `area_id` int(11) NOT NULL COMMENT '县ID',
 `state` int(2) NOT NULL DEFAULT '0' COMMENT '收货地址的状态',
 PRIMARY KEY (`id`),
 KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='收货地址表';
--添加省级表
CREATE TABLE `aps`.`province` ( `id` INT NOT NULL AUTO_INCREMENT COMMENT '省ID' , `name` VARCHAR(127) NOT NULL COMMENT '省名称' , PRIMARY KEY (`id`)) ENGINE = MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT = '省级表';
--添加市级表
CREATE TABLE `aps`.`city` ( `id` INT NOT NULL AUTO_INCREMENT COMMENT '市ID' , `name` VARCHAR(127) NOT NULL COMMENT '市名称' , PRIMARY KEY (`id`)) ENGINE = MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT = '市级表';
--添加县或区级表
CREATE TABLE `aps`.`area` ( `id` INT NOT NULL AUTO_INCREMENT COMMENT '县或区ID' , `name` VARCHAR(127) NOT NULL COMMENT '县或区名称' , PRIMARY KEY (`id`)) ENGINE = MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT = '县或区表';
--添加代理用户表
CREATE TABLE `aps`.`agent` ( `id` INT NOT NULL AUTO_INCREMENT COMMENT '代理人ID' , `username` VARCHAR(32) NOT NULL COMMENT '用户名' , `password` VARCHAR(256) NOT NULL COMMENT '密码' , `login_count` INT NOT NULL DEFAULT '0' COMMENT '登录次数' , `last_ip` VARCHAR(32) NOT NULL COMMENT '登录的IP' , `last_login_time` INT NOT NULL COMMENT '最后一次登录时间' , `token` VARCHAR(256) NOT NULL COMMENT 'token' , `token_expired` INT NOT NULL COMMENT 'token过期时间' , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT = '代理的用户表';
--添加订单购物车联系表
CREATE TABLE `aps`.`oder_goods` ( `id` INT NOT NULL AUTO_INCREMENT COMMENT '订单购物车联系ID' , `order_id` INT NOT NULL COMMENT '订单ID' , `goods_car_id` INT NOT NULL COMMENT '购物车ID' , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT = '订单购物车联系表';

--添加字段单位和预计发货时间到商品表
ALTER TABLE `goods` ADD `unit` VARCHAR(16) NOT NULL COMMENT '价格的单位' AFTER `classes_id`;
ALTER TABLE `goods` ADD `send_time` VARCHAR(256) NOT NULL COMMENT '预计发货时间' AFTER `unit`;