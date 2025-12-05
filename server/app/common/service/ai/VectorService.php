<?php


namespace app\common\service\ai;

use app\api\logic\service\TokenLogService;
use app\common\enum\ChatEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\chat\Models;
use app\common\model\user\User;
use app\common\service\ToolsService;
use Exception;

/**
 * AI向量化训练服务类
 */
class VectorService
{
    private int $mainModelId;
    private array $configs;

    public function __construct(int $mainModelId)
    {
        $this->mainModelId = $mainModelId;
        $this->configs = [];
    }

    /**
     * @notes 转向量
     * @param string $channel (模型通道)
     * @param string $model (模型名称)
     * @param string $document (要转向量的内容)
     * @param int $userId
     * @param bool $isReturnStr (是否以字符串返回向量)
     * @return array|string
     * @throws Exception
     */
    public function toEmbedding(string $channel, string $model, string $document, int $userId, bool $isReturnStr=false): array|string
    {
//        $this->document = $document;
//        $doubaoApi = 'https://ark.cn-beijing.volces.com/api/v3/embeddings';
//        return match (strtolower($channel)) {
//            'openai', 'doubao' => $this->textOpenAi($model, $document, $isReturnStr),
//            'qwen'             => $this->textQwen($model, $document, $isReturnStr),
//            default            => [],
//        };
        //TODO 暂时不收费
//        $unit = TokenLogService::checkToken($userId, 'text_to_vector');
        $params['model'] = $model;
        $params['document'] = $document;
        $result = ToolsService::VectorKnowledge()->text2vector($params);
//        if ($result['code']==10000){
//            $points = ceil($result['data']['usage'] / $unit);
//            $extra = ['总消耗tokens数' => $result['data']['usage'], '算力单价' => '1算力/10000tokens', '实际消耗算力' => $points];
//            User::userTokensChange($userId, $points);
//            //扣费记录
//            AccountLogLogic::recordUserTokensLog(true, $userId, 11002, $points, '', $extra);
//        }
        return $result['data']['document'];
    }

    /**
     * @notes OpenAI转向量
     * @param string $model
     * @param string $document
     * @param bool $isReturnStr
     * @return bool|array|string
     * @throws Exception
     * @author kb
     */
    public function textOpenAi(string $model, string $document, bool $isReturnStr=false): bool|array|string
    {
        try {
            $apiBase = 'https://ark.cn-beijing.volces.com/api/v3/embeddings';
            // 验证密钥
            $apiAiKey = '92fca52c-696e-4c7e-a35e-2dd90a4c9978';

            // 请求参数
            $header[] = 'Authorization: Bearer ' . $apiAiKey;

            $reqResults = VectorService::curlPost($apiBase, [
                'model' => 'doubao-embedding-text-240715',
                'input' => [$document],
                'encoding_format' => 'base64'],
                $header
            );

            $results = json_decode($reqResults, true);
            if (!empty($results['error'])) {
                throw new Exception('向量模型: '. $results['error']['message']);
            }

            if (empty($results['data'][0]['embedding']) and !empty($results['detail'])) {
                throw new Exception('向量模型: '.$results['detail']);
            }

            // 提取内容
            $base64 = $results['data'][0]['embedding'] ?? '';
            if (!$base64) {
                throw new Exception('向量模型: 解析问题失败了!');
            }

            $this->usage = $results['usage'];

            // 数组长度: 1536
            if (is_string($base64)) {
                $embedding = base64_decode($base64);
                $floatArray = unpack('f*', $embedding);
                $embArray = array_values($floatArray);
            } else {
                $embArray = $base64;
            }

            // 返回字符串
            if (!$isReturnStr) {
                return $embArray;
            }

            return '[' . implode(',', $embArray) . ']';
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @notes 通义千问转向量
     * @param string $model
     * @param string $document
     * @param bool $isReturnStr
     * @return mixed|string
     * @throws Exception
     */
    public function textQwen(string $model, string $document, bool $isReturnStr=false): string|array
    {
        $aiType = 'qwen';
        $apiBase = 'https://dashscope.aliyuncs.com';
        $keyPoolCache = new KeyPoolCache($this->mainModelId, ChatEnum::MODEL_TYPE_EMB, $aiType);

        try {
            // 代理地址
            if (!empty($this->configs['agency_api'])) {
                $apiBase = $this->configs['agency_api'];
            }

            // 验证密钥
            $apiAiKey = $keyPoolCache->getKey();
            if (!$apiAiKey) {
                throw new Exception('请管理员配置向量密钥: ' . $model);
            }

            // 请求参数
            $header[] = 'Authorization: Bearer ' . $apiAiKey;
            $apiBase .= '/api/v1/services/embeddings/text-embedding/text-embedding';
            $reqResults = VectorService::curlPost($apiBase, [
                'model'=>$model,
                'input' => ['texts'=>[$document]],
                'parameters' => [
                    'text_type' => 'query'
                ]
            ], $header);

            $results = json_decode($reqResults, true);

            if (!empty($results['error'])) {
                $keyPoolCache->takeDownKey($results['error']['message'], $apiBase);
                throw new Exception('向量模型: ' . $results['error']['message']);
            }

            if (empty($results['output']['embeddings'][0]['embedding'])) {
                $keyPoolCache->takeDownKey($results['message'], $apiBase);
                throw new Exception('向量模型: ' . $results['message']);
            }

            // 提取内容 (维度 1536)
            $embArray = $results['output']['embeddings'][0]['embedding'];

            // 返回字符串
            if (!$isReturnStr) {
                return $embArray;
            }

            return '[' . implode(',', $embArray) . ']';
        } catch (Exception $e) {
            $error = $keyPoolCache->takeDownKey($e->getMessage(), $apiBase);
            throw new Exception($error);
        }
    }

    /**
     * @notes 发起POST请求
     * @param string $url
     * @param array $data
     * @param array $header
     * @return bool|string
     * @throws @Exception
     * @author kb
     */
    public static function curlPost(string $url, array $data, array $header = []): bool|string
    {
        $headers  = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        $headers = array_merge($headers, $header);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // 执行CURL请求
        $response = curl_exec($curl);

        // 检查是否有错误发生
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            throw new Exception($error);
        }

        // 关闭 cURL 句柄
        curl_close($curl);
        return $response;
    }

    /**
     * @notes 获取计费信息
     * @return array
     * @author kb
     */
    public function getUsage(): array
    {
        if (!$this->usage) {
            $tokens = gpt_tokenizer_count($this->document);
            $this->usage = [
                'prompt_tokens' => $tokens,
                'total_tokens'  => $tokens
            ];
        }

        return  [
            'prompt_tokens'     => $this->usage['prompt_tokens'],
            'total_tokens'      => $this->usage['total_tokens'],
            'completion_tokens' => $this->usage['total_tokens'] - $this->usage['prompt_tokens'],
            'str_length'        => mb_strlen($this->document)
        ]??[];
    }
}