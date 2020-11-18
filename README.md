# DedeCMSV6升级程序

本程序用于将SP1、SP2系统（GBK、UTF8）升级到[DedeCMSV6](https://www.dedebiz.com/download)系统。

由于DedeCMSV6之后只提供UTF8编码格式的版本，所以GBK编码在升级时候会被转码。

## 升级步骤

1.将当前系统进行备份，备份分为2个部分：文件、数据库，数据库可以采用后台【系统设置】中【数据库备份/还原】功能或者采用PHPMyadmin等第三方工具，来备份数据库，文件直接通过[S]FTP相关工具来备份到本地；

2.解压升级程序到系统根目录下，例如您的站点域名是`https://www.dedebiz.com`，解压后能够通过`https://www.dedebiz.com/tov6/index.php`访问升级程序；

3.根据提示信息执行升级程序；

4.下载DedeCMSV6系统，选择性覆盖对应文件（如果原系统已经被更改，建议建立git库，然后差异修改，以保证代码统一）；

5.删除`/tov6`升级程序目录，完成升级；

## 版权信息

详细参考：[DedeCMSV6站点授权协议](https://www.dedebiz.com/license)

## 相关资源

- [DedeCMSV6](https://www.dedebiz.com)

- [帮助中心](https://www.dedebiz.com/help)

- [DedeBIZ商业支持](https://www.dedebiz.com)

- [代码托管](https://www.dedebiz.com/git)

- 微信公众号：穆云智能

![微信公众号：穆云智能](static/img/muyun_wechat_qr.png)

- 邮箱：support#dedebiz.com