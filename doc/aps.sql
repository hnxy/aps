USE aps;
ALTER TABLE `goods` MODIFY COLUMN price int(6) not null;
ALTER TABLE `goods` MODIFY COLUMN origin_price int(6) not null;
ALTER TABLE `order` MODIFY COLUMN order_price int(10) not null;
UPDATE `order` o, goods g SET o.order_price = o.goods_num * g.price where o.goods_id = g.id;