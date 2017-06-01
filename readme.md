# APS Framework

### 当前主要框架或语言的版本

1. Framework based on lumen core:  5.4
3. php 7.0.10
4. nginx 1.9.10
5. mysql 5.7.10

### 简介

提供Http Restful Api接口给 Android ,Ios, Web前端使用

### 使用姿势--unix*(建议本地测试 virtualbox + vagrant)

1. Nginx虚拟主机[配置文件][1]

2. php-fpm[配置文件][2]

3. make install

4. make build

5. curl aps.cg0.me/ping


[1]:https://github.com/hncg/conf/tree/master/nginx
[2]:https://github.com/hncg/conf/tree/master/php