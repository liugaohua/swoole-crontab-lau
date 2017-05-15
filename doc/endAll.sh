#!/bin/sh
set -x
if [ "$1" = '' ];then
    exit
fi
if [ "$1" != 1 ];then
    exit
fi
echo '=======kill before======='
processName=ydCron
ps axu| grep ${processName} 
pids=`ps axu| grep ${processName} | grep -v grep | awk '{print $2}'`
ids=''
for pid in ${pids}
do
	ids=${ids}" "$pid
done
kill -9 ${ids}
echo '======kill after====='
ps axu| grep ${processName}
