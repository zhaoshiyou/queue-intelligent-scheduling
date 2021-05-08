#这里就不扯什么英文了。 一个简单的功能


这个包是基于的laravel 8.x   智能调度来消费队列 命名空间Zsy（zhaoshiyou的首字母缩写）
只适用于Linux环境。
windows下的话可以测试，但需要修改一下代码，类queueIntelligentScheduling 第46-47行 

调用示例：

use Zsy\Qis\queueIntelligentScheduling;

class TestController extends Controller {

    public function check() {
        
        $a=new queueIntelligentScheduling('message',10,15,[1000=>1,5000=>2,8000=>5,20000=>10,50000=>15]);
        //第一个参数为laravel的队列名，
        //第二个参数为一个消费进程执行的多少条就自动退出，第三个参数是一个消费进程执行多长时间就自动退出，两者先到为准（都是必填项，且需要认真配置）
        //第三个参数是数组配置，解释一下：队列长度超过5000以内起2个消费进程，超过8000起5个进程消费，超过50000起15个进程。
        
        $a->intelligentScheduling();

    }    
}

然后在服务器或者用laravel自带的任务调度，定时去执行check方法。比如每5分钟去执行一下check()。就会实现根据laravel队列的长度，智能的去起消费进程，而且进程也会根据你的配置自动退出。

