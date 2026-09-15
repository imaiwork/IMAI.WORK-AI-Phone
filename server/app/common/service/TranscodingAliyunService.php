<?php

namespace app\common\service;

use AlibabaCloud\SDK\Mts\V20140618\Mts;
use AlibabaCloud\Credentials\Credential;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListRequest;
use AlibabaCloud\Credentials\Credential\Config as CredentialConfig;
use think\facade\Log;

class TranscodingAliyunService {

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }
    /**
     * 使用凭据初始化账号Client
     * @return Mts Client
     */
    public  function createClient(){
        $credConfig = new CredentialConfig([
            'type' => 'access_key',
            'accessKeyId' => $this->config['access_key'],
            'accessKeySecret' => $this->config['secret_key'],
        ]);

        $credClient = new Credential($credConfig);
        $str = $this->config['Location'];
        $prefix = 'oss-';
        $regin = str_replace($prefix, '', $str); // 移除前缀
        $config = new Config([
            // 使用 credential 配置凭证
            'credential' => $credClient,
            // 产品服务域名
            'endpoint' => 'mts.'.$regin.'.aliyuncs.com'
        ]);
        return new Mts($config);
    }

    /**
     * @param string[] $args
     * @return void
     */
    public  function main($params){
        $data['code'] = true;
        $client = self::createClient();
        $params['input']['Bucket'] = $this->config['bucket'] ;
        $params['input']['Location'] = $this->config['Location'];
        $video_url = FileService::setFileUrl($params['video_url']);
        $params['input']['Object'] = urlencode($video_url);
        $params['input'] = json_encode($params['input'],JSON_UNESCAPED_SLASHES);


        $params['outputs'][0]['OutputObject'] = urlencode($video_url) ;
        $params['outputs'][0]['TemplateId'] = $this->config['TemplateId'] ;
        $params['outputs'] =json_encode($params['outputs'],JSON_UNESCAPED_SLASHES);
        unset($params['video_url']);

        $submitJobsRequest = new SubmitJobsRequest([
            "input" =>  $params['input'],
            "outputs" =>   $params['outputs'],
            "outputBucket" => $this->config['bucket'],
            "pipelineId" => $this->config['PipelineId'],
            "outputLocation" =>  $this->config['Location']
        ]);
        $runtime = new RuntimeOptions([]);
        try {
            // 复制代码运行请自行打印 API 的返回值
            $resp = $client->submitJobsWithOptions($submitJobsRequest, $runtime);
            $resparray = $resp->toMap();
            if (isset($resparray['body']['JobResultList']['JobResult'][0]['Job']['JobId'])){
                $JobId = $resparray['body']['JobResultList']['JobResult'][0]['Job']['JobId'];
                $data['jobid'] = $JobId;
                return $data;
            }
            $data['code'] = false;
            $data['message'] = '转码失败';
            return  $data;

        }catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            $data['code'] = false;
            $data['message'] = $error->message;
            $data['errorurl'] = $error->data["Recommend"];
            Log::channel('shanjian')->write('视频转码失败' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return  $data;
        }
    }

    public  function search($request){
        $data['code'] = true;
        $client = self::createClient();
        $queryJobListRequest = new QueryJobListRequest([
            "jobIds" => $request['jobid'],
        ]);
        $runtime = new RuntimeOptions([]);
        try {
            // 复制代码运行请自行打印 API 的返回值
            $resp = $client->queryJobListWithOptions($queryJobListRequest, $runtime);
            $resparray = $resp->toMap();
            if (isset($resparray['body']['JobList']['Job'][0]['State'])){
                $State = $resparray['body']['JobList']['Job'][0]['State'];
                $data['state'] = $State;

                if ($State == 'TranscodeFail'){
                    $data['code'] = false;
                    $data['message'] = $resparray['body']['JobList']['Job'][0]['Message'];
                }
                return $data;
            }
            $data['state'] = 'TranscodeFail';
            $data['code'] = false;
            $data['message'] = '转码失败';
            return  $data;

        }
        catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            $data['code'] = false;
            $data['message'] = $error->message;
            $data['errorurl'] = $error->data["Recommend"];
            Log::channel('shanjian')->write('获取视频转码结果失败' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return  $data;
        }
    }
}
