<?php

namespace app\common\service\transcoding;


use app\common\service\FileService;
use \Exception;

use think\facade\Log;
use Qcloud\Cos\Client;
use Qcloud;

class QcloudService {

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }
    /**
     * 使用凭据初始化账号Client
     * @return Mts Client
     */


    /**
     * @param string[] $args
     * @return void
     */
    public  function main($params){


        $cosClient = new Client(
           [
                'region' =>  $this->config['region'],
                'schema' => 'https', //协议头部，默认为 http
                'credentials'=>   [
                    'secretId'  =>  $this->config['access_key'] ,
                    'secretKey' =>  $this->config['secret_key']]]);
        $data['code'] = true;
        $video_url = FileService::setFileUrl($params['video_url']);
        try {
            $resparray = $cosClient->createMediaTranscodeJobs([
                'Bucket' => $this->config['bucket'] , //存储桶名称，由 BucketName-Appid 组成，可以在 COS 控制台查看 https://console.cloud.tencent.com/cos5/bucket
                'Tag' => 'Transcode',
                'Input' =>[
                    'Object' => $video_url
                ],
                'Operation' => [
                    'TemplateId' => $this->config['TemplateId'],
                    'Output' => [
                        'Region' => $this->config['region'],
                        'Bucket' =>$this->config['bucket'] , //存储桶名称，由 BucketName-Appid 组成，可以在 COS 控制台查看 https://console.cloud.tencent.com/cos5/bucket
                        'Object' => $video_url,
                  ],
                ],
            ]);
            if (isset($resparray['Response']['JobsDetail']['JobId'])){
                $JobId = $resparray['Response']['JobsDetail']['JobId'];
                $data['jobid'] = $JobId;
                return $data;
            }
            $data['code'] = false;
            $data['message'] = '转码失败';
            return  $data;

        }catch (\Exception $e) {
            echo "$e\n";
        }
    }

    public  function search($request){
        $data['code'] = true;
        $cosClient = new Client(
            [
                'region' =>  $this->config['region'],
                'schema' => 'https', //协议头部，默认为 http
                'credentials'=>[
                    'secretId'  =>  $this->config['access_key'] ,
                    'secretKey' =>  $this->config['secret_key']]]);
        try {
            // 查询指定的任务
            $result = $cosClient->describeMediaJob([
                'Bucket' =>$this->config['bucket'] ,
                'Key' => $request['jobid']
            ]);

            if (isset($result['Response']['JobsDetail']['State'])){
                $State = $result['Response']['JobsDetail']['State'];
                $data['state'] = $State;

                if ($State == 'Failed'){
                    $data['code'] = false;
                    $data['state'] = 'TranscodeFail';
                    $data['message'] = $result['Response']['JobsDetail']['Message'];
                }
                return $data;
            }
            $data['state'] = 'TranscodeFail';
            $data['code'] = false;
            $data['message'] = '转码失败';
            return  $data;
        } catch (\Exception $e) {
            $data['state'] = 'TranscodeFail';
            $data['code'] = false;
            $data['message'] = '转码失败';
            Log::channel('shanjian')->write('获取视频转码结果失败' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return  $data;
        }
    }
}
