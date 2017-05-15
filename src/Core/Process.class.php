<?php

/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 14-12-27
 * Time: 下午10:39
 */
class Process
{
    public $task;

    /**
     * 创建一个子进程
     * @param $task
     */
    public function create_process($id, $task)
    {
        if( count( Crontab::$serv->table ) >= ( $maxCount = Main::getConfig( 'maxProcessCount' ) ) )
        {
            Main::masterLog( '当前任务进程数,已经达到最大值' . $maxCount );
            return;
        }
        $this->task = $task;
        $process = new swoole_process( array( $this, "run" ), false );
        if (!($pid = $process->start())) {

        }
        else
        {
            Main::cronLog( "cronId:$id, pid:$pid, starting.." );
        }

        //记录当前任务
		if(1):
        Crontab::$task_list[$pid] = array(
            "start" => microtime(true),
            "id" => $id,
            "task" => $task,
            "type" => "crontab",
            "process" =>$process,
        );
		endif;
		Crontab::$serv->table->set( "$pid",
			array(
                'id' => (int)$id,
                'pid' => $pid,
                'startTime' => microtime( true ),
			)
		);
//        swoole_event_add($process->pipe, function ($pipe) use ($process) {
//            $task = $process->read();
//            list($pid, $sec) = explode(",", $task);
//            if (isset(Crontab::$task_list[$pid])) {
//                $tasklist = Crontab::$task_list[$pid];
//                Crontab::$delay[$pid] = array("start"=>time() + $sec,"task"=>$tasklist["task"]);
//                $process->write($task);
//            }
//        });
    }


    /**
     * 子进程执行的入口
     * @param $worker
     */
    public function run($worker)
    {
        $class = $this->task["execute"];
        Crontab::set_process_name( Crontab::$task_process_name );
        $this->autoload($class);
        $c = new $class;
        $c->worker = $worker;
        $c->run($this->task["args"]);
        self::_exit($worker);
        #swoole_event_exit();
        return ;
    }

    private function _exit($worker)
    {
//        $worker->exit(0);
    }

    /**
     * 子进程 自动载入需要运行的工作类
     * @param $class
     */
    public function autoload( $class )
    {
        include( ROOT_PATH . 'Plugin' . DS . 'PluginBase.class.php' );
        $file = ROOT_PATH . 'Plugin' . DS . $class . '.class.php';
        if( file_exists( $file ) )
        {
            include( $file );
        }
        else
        {
            Main::masterLog( "处理类不存在" );
        }
    }
}

