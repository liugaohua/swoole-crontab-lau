[root@yourdream:src]#php main.php -s restart --tasktype mysql --worker -d
[root@yourdream:src]#php main.php

帮助信息:
Usage: /path/to/php main.php [options] -- [args...]

-h [--help]        显示帮助信息
-p [--pid]         指定pid文件位置(默认pid文件保存在当前目录)
-s start           启动进程
-s stop            停止进程
-s restart         重启进程
-l [--log]         log文件夹的位置
-c [--config]      config文件的位置
-d [--daemon]      是否后台运行
-r [--reload]      重新载入配置文件
-m [--monitor]     监控进程是否在运行,如果在运行则不管,未运行则启动进程
--worker           开启worker
--tasktype         task任务获取类型,[file|mysql] 默认是file
--checktime        默认精确对时(如果精确对时,程序则会延时到分钟开始0秒启动) 值为false则不精确对时
