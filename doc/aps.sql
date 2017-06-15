-- 增加一个字段openid
alter table user add openid varchar(256);
-- 添加openid的索引
alter table user add index openid_index(openid);
-- 添加openid的注释
ALTER TABLE `user` CHANGE `openid` `openid` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '用户的openid,用来辨别用户身份';
-- 添加商品表
CREATE TABLE `aps`.`goods` ( `id` INT NOT NULL AUTO_INCREMENT COMMENT '商品的id' , `title` VARCHAR(256) NOT NULL COMMENT '商品的标题' , `description` VARCHAR(256) NOT NULL COMMENT '商品的描述' , `origin_price` DECIMAL(10,2) NOT NULL COMMENT '商品的原价' , `price` DECIMAL(10,2) NOT NULL COMMENT '商品的现价' , `start_time` INT NOT NULL COMMENT '商品开卖的时间' , `end_time` INT NOT NULL COMMENT '商品的结束时间' , `create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '当前时间' , `detail` TEXT NOT NULL COMMENT '图文详情' , `goods_img` VARCHAR(256) NOT NULL COMMENT '商品的图片链接' , `classes` TINYINT NOT NULL COMMENT '商品分类' , PRIMARY KEY (`id`), INDEX (`start_time`), INDEX (`end_time`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT = '首页的商品信息表';
-- 添加商品分类表
CREATE TABLE `aps`.`classes` ( `id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(256) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT = '商品分类表';
-- 添加商品轮播图片表
CREATE TABLE `aps`.`goods_img` ( `id` INT NOT NULL AUTO_INCREMENT , `goods_id` INT NOT NULL , `goods_img` VARCHAR(256) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT = '商品轮播图片表';