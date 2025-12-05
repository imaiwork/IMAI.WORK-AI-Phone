<?php

namespace app\common\service;

use think\facade\Log;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class FSWebhookService
{

    /**
     * 飞书webhook地址
     * @var string
     */
    protected string $webhookUrl;


    public function __construct()
    {

        $this->webhookUrl = config('ai.lark.webhook_url');
    }

    /**
     * 发送消息
     * @param string $msg
     * @return void
     */
    public function sendMessage(string|array $messages): void
    {

        try {
            $messages = is_array($messages) ? $this->formatData($messages) : $messages;

            $params = [
                "msg_type" => "text", // 消息类型
                "content" => [
                    "text" => $messages
                ]
            ];

            if (str_contains(request()->route()->uri(), 'assistant')) {

                file_put_contents('a.log', $messages . "\n", FILE_APPEND);
            } else {
                try {
                    $loginStatus = (new Client())->post($this->webhookUrl, [
                        'verify'  => false,
                        'headers' => [
                            "Content-Type" => "application/json",
                        ],
                        'json'    =>  $params
                    ]);
                    $response = json_decode($loginStatus->getBody()->getContents(), true);
                    if ($response['status_code'] !== 200) {

                        throw new \Exception('飞书消息发送失败');
                    }
                } catch (GuzzleException $exception) {
                    throw new \Exception("远程服务异常");
                }
            }
        } catch (\Exception $e) {

            Log::write('请求异常: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 格式化消息内容
     *
     * @param array $data 数据内容
     * @return string
     */
    private function formatData(array $data): string
    {
        $formatted = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $formatted .= "$key:\n" . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
            } else {
                $formatted .= "$key: $value\n";
            }
        }
        return $formatted;
    }
}
