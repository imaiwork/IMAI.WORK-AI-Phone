<?php

namespace app\common\service\transcoding;


use app\common\service\FileService;
use app\common\service\storage\engine\Server;
use \Exception;

use think\facade\Log;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;
use Qiniu\Processing\PersistentFop;
use Qiniu\Config;
class QiniuService{

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }




    public function main($params){

        try {
            $auth = new Auth($this->config['access_key'], $this->config['secret_key']);
            $data['code'] = true;// 对已经上传到七牛的视频发起异步转码操作
            // 普通音视频转码参考文档：https://developer.qiniu.com/dora/api/1248/audio-and-video-transcoding-avthumb
            // 要转码的文件所在的空间和文件名。
            $bucket = $this->config['bucket'];
            $video_url = FileService::setFileUrl($params['video_url']);
            $key = $video_url;// 用户默认没有私有队列，需要在这里创建然后填写 https://portal.qiniu.com/dora/media-gate/pipeline
            $pipeline = $this->config['PipelineId'];// 当转码后的文件名与源文件名相同时，是否覆盖源文件
            $force = false;// 转码完成后通知到你的业务服务器（需要可以公网访问，并能够相应 200 OK）
            $config = new Config();
            $config->useHTTPS = true;// 视频处理完毕后保存到空间中的名称
            $saveasKey = $key;
            $pfop = new PersistentFop($auth, $config);// 进行视频转码操作
            $fops = "avthumb/mp4|saveas/" . \Qiniu\base64_urlSafeEncode("$bucket:$saveasKey");
            list($id, $err) = $pfop->execute($bucket, $key, $fops, $pipeline, '', $force);
            if ($err != null) {
                $data['code'] = false;
                $data['message'] = $err->message();
                return  $data;
            } else {
                $data['jobid'] = $id;
                return $data;
            }
        } catch (Exception $e) {
            $data['code'] = false;
            $data['message'] = $e->getMessage();
            return  $data;
        }
    }


    /**
     * 查询转码任务状态
     * @param array $request ['persistentId' => '...']
     * @return array
     */
    public function search($request){
        $data['code'] = true;
        $persistentId = $request['jobid'] ?? '';
        try {
            $auth = new Auth($this->config['access_key'], $this->config['secret_key']);
            $pfop = new PersistentFop($auth);
            list($ret, $err) = $pfop->status($persistentId);
            if ($err != null) {
                $data['code'] = false;
                $data['message'] = $err->message();
                return  $data;
            }

            switch ($ret['code']) {
                case 0:
                    $data['message'] = $ret['desc'];
                    break;
                case 3:
                    $data['code'] = false;
                    $data['state'] = 'TranscodeFail';
                    $data['message'] = $ret['desc'];
                    break;
              default:
                    $data['state'] = 'Processing';
                    $data['message'] = $ret['desc'];
                    break;
            }
            return  $data;

        } catch (\Exception $e) {
            return [
                'code' => false,
                'state' => 'TranscodeFail',
                'message' => '转码失败'
            ];
        }
    }


    public function fetch($url, $options = [])
    {
        // 实现 fetch 方法
        // 从远程 URL 抓取文件
    }



    public function delete($fileKey, $options = [])
    {
        // 实现 delete 方法
        // 删除文件
    }

    public function getFileName()
    {
        return $this->fileName;
    }
}
