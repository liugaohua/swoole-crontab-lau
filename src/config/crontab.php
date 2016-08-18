<?php
return array(


//    'taskid1' =>
//        array(
//            'taskname' => 'errTest',  //任务名称
//			#'rule' => '*/2 * * * * *',//定时规则
//            "unique" => 1, //排他数量，如果已经有这么多任务在执行，即使到了下一次执行时间，也不执行
//            'execute'  => 'Cmd',//命令处理类
//            'args' =>
//                array(
//                    'cmd'    => '/usr/local/bin/php /root/CYZSStat/Web/cron.php method=cron.errTest inner=1 err=1',//命令
//                    #'cmd'    => '/usr/local/bin/php /root/CYZSStat/Web/cron.php method=cron.test  inner=1 err=1',//命令
//                    'ext' => '',//附加属性
//                ),
//   附加属性     ),

    'taskid2' =>
        array(
            'taskname' => 'test',  //任务名称
            'rule' => array("21:23","21:29:58","21:24:36"),
            "unique" => 1, //排他数量，如果已经有这么多任务在执行，即使到了下一次执行时间，也不执行
            "execute" =>"Gather",
            'args' =>
                array(
                    'cmd'    => 'gather',//命令
                    'ext' => '',//附加属性
                ),
        ),
);
