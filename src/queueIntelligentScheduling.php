<?php

namespace Zsy\Qis;

use Illuminate\Support\Facades\Queue;
/**
 * 基于laravel 8.x
 * 智能调度来消费队列
 */
class queueIntelligentScheduling {

    public $queueName;
    
    public $maxJobs=5;
    
    public $maxTime=1000;
    
    public $config=[];
    /**
     * 
     * @param string $queueName laravel队列名称
     * @param int $maxjobs  每个进程最大执行条数
     * @param int $maxtime  每个进程最长执行时间
     * @param array $config [1000=>1,5000=>2,10000=>5,,,,]  键：队列长度。 值：应该多少个进程执行
     */
    public function __construct(string $queueName, int $maxjobs, int $maxtime, array $config) {
        $this->queueName=$queueName;
        $this->maxJobs=$maxjobs;
        $this->maxTime=$maxtime;
        $this->config=$config;
    }

    public function intelligentScheduling() {
        
        $queueLen=Queue::size($this->queueName);
        $config=$this->config;
        
        if(!$queueLen){
            return;
        }
        if(!$config){
            return;
        }
        ksort($config);
        
        $processCount=$this->getProcessCount("queue=".$this->queueName);//获取当前laravel 队列消费进程的数量  windows下应该注释掉
//        $processCount=0;//windows下测试可设置一个默认值。

        $config_queueLen=array_keys($config);
        $config_processCount=array_values($config);
        /**
         * 根据队列长度计算本应该有多个进程执行
         */
        $shouleProcessCount=0;
        foreach ($config_queueLen as $key=>$value) {
            if($queueLen<$value&&$key==0){//比第一个还少
                $shouleProcessCount=$config_processCount[0];
                break;
            }elseif($queueLen<$value&&$key>0){
                $shouleProcessCount=$config_processCount[$key-1];
                break;
            }else{
                $shouleProcessCount=end($config_processCount);//取最大的一个值
            }
        }
        
        /**
         * 若进程不够就增加进程
         */
        if($processCount<$shouleProcessCount){
            $startCount=intval($shouleProcessCount)-intval($processCount);
            $this->foreachStartQueueProcess($startCount);
        }
    }
    
    
    private function foreachStartQueueProcess($startCount) {
        for($i=0;$i<$startCount;$i=$i+1){
            $this->startQueueProcess();
            sleep(1);
        }
    }
    
    private function startQueueProcess() {
        $path = base_path();
//        @exec("cd  {$path}  && php artisan queue:work  --queue={$this->queueName} --max-jobs={$this->maxJobs} --max-time={$this->maxTime}", $out);
        $proc=proc_open("cd  {$path}  && php artisan queue:work  --queue={$this->queueName} --max-jobs={$this->maxJobs} --max-time={$this->maxTime}", array(),$pipes);
//         $process = \proc_open(
//                "cd  {$path}  && php artisan queue:work  --queue={$this->queueName} --max-jobs={$this->maxJobs} --max-time={$this->maxTime}",
//                [
//                    1 => ['pipe', 'w'],
//                    2 => ['pipe', 'w']
//                ],
//                $pipes,
//                null,
//                null,
//                ['suppress_errors' => true]
//            );
//
//            if (\is_resource($process)) {
//                $info = \stream_get_contents($pipes[1]);
//
//                \fclose($pipes[1]);
//                \fclose($pipes[2]);
////                \proc_close($process);
//            }
//        proc_close($proc);
    }
    
    private function restartQueueProcess() {
        $path = base_path();
        @exec("cd  {$path}  && php artisan queue:restart", $out);
    }

    
    private function kill_process($pid) {
        return posix_kill($pid, SIGTERM);
    }
    
    private function getProcessCount($processName) {
        @exec("ps aux | grep -v grep | grep '{$processName}' | wc -l", $out);
        if(isset($out[0])){
            return intval(trim($out[0]));
        }else{
            return 0;
        }
    }
}
