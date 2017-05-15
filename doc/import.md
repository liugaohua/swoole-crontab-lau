+ php back.php action=import err=1 auth=1 status=0停用,1正常,2删除
+ php back.php action=setStatus status=1 taskId=10690253380
+ {"code":0,"msg":"","action":"setStatus","data":{"result":"UPDATE `crontab` SET `status` = 1 WHERE `taskid` = 1069025338"}}
+ php back.php action=dump err=1
