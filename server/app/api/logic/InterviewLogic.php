<?php

namespace app\api\logic;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewCv;
use app\common\model\interview\InterviewDialog;
use app\common\model\interview\InterviewFeedback;
use app\common\model\interview\InterviewJob;
use app\common\model\interview\InterviewRecord;
use app\common\model\ModelConfig;
use app\common\model\user\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PhpOffice\PhpWord\IOFactory;
use think\Exception;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

class InterviewLogic extends BaseLogic
{
    /**
     * @desc 我的岗位
     * @param array $params
     * @return array
     * @date 2025/2/13 17:18
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public static function jobs(array $params)
    {

        if (!empty($params['job_id'])){
            $result = InterviewJob::where(['user_id' => $params['user_id'], 'status' => 1])->where('id', $params['job_id'])->select()->toArray();
        } else {
            $result = InterviewJob::where(['user_id' => $params['user_id'], 'status' => 1])->select()->toArray();
        }
        if(!$result){
            throw new Exception('岗位不存在!');
        }
        return $result;
    }

    /**
     * @desc 岗位详情
     * @param array $params
     * @return array
     * @date 2025/2/13 17:29
     * @throws Exception
     * @author dagouzi
     */
    public static function jobDetail(array $params)
    {
        $result = InterviewJob::where(['id' => $params['id'], 'status' => 1])->findOrEmpty()->toArray();
        if (empty($result)){
            throw new Exception('面试不存在或已取消!');
        }
        return $result;
    }

    /**
     * @desc 简历识别
     * @return true
     * @date 2025/2/13 17:29
     * @author dagouzi
     */
    public static function extractCv($params)
    {

        if(!isset($params['interview_job_id'])){
            throw new Exception('参数缺少!');
        }

        $user_id = InterviewJob::where(['id' => $params['interview_job_id']])->value('user_id');
        if(!$user_id){
            throw new Exception('岗位不存在!');
        }

        // Db::startTrans();
        try {

            //计费
            //$unit = TokenLogService::checkToken($user_id, 'interview_cv');
            $unit = 0;
            $url = $params['word'];
//            $urlData = parse_url($url);
//            $path = public_path() . $urlData['path'];
            $path = $url;
            $file = new \CURLFile($path);
            $response = \app\common\service\ToolsService::Interview()->cv([
                'file'  => $file,
                'action' => 'upload'
            ]);
          
            $file_id = $response['data']['file_id'] ?? '';

            if(empty($file_id)){
                throw new Exception('简历上传失败!');
            }

            $file_id = 'fileid://' . $file_id;
            $cv_content = [
                "role"=>"简历信息解析助手",
                "description"=>"从用户上传的简历文本中精准提取结构化信息，并以标准化JSON格式返回。",
                "instruction"=>
                    '请严格按照以下规则解析简历内容：
                        1. **提取字段**：
                           - 姓名（需全称，忽略昵称/英文名）
                           - 性别（若未明确标注则留空）
                           - 手机号（若未明确标注则留空）
                           - 年龄（优先取数字格式，若为出生日期则自动计算）
                           - 工作年限（优先取数字格式，若为工作年限则自动计算）
                           - 学历（最高学历，如：博士/硕士/本科）
                           - 毕业院校（最高学历对应院校，合并分校信息）
                           - 工作经历（按倒序排列，格式：["公司名 | 职位 | 时间段（起止年月） | 核心职责摘要"]）
                           - 项目经历（按倒序排列，格式：["项目名称 | 角色 | 技术栈/工具 | 成果量化描述"]）
                      
                        2. **处理规则**：
                           - 合并分散段落：若同一经历分多段描述，需合并为单一条目
                           - 清洗冗余词：去除「负责」、「参与」等非必要前缀
                           - 时间标准化：时间段统一为「YYYY.MM-YYYY.MM」格式
                           - 量化成果：项目成果需包含可量化的指标（如提升30%/节省100小时）
                      
                        3. **输出要求**：
                           - 严格使用JSON格式
                           - 空值字段保留为null
                           - 特殊符号转义处理
                        ,
                        "response_example": {
                          "name": "王小明",
                          "sex": "男",
                          "age": 28,
                          "work_years": 3,
                          "mobile": "13800138000",
                          "degree": "硕士",
                          "school": "清华大学计算机科学与技术系",
                          "work_ex": [
                            "阿里巴巴集团 | 高级后端开发工程师 | 2020.07-2023.05 | 主导支付系统重构，QPS从5k提升至12k",
                            "字节跳动 | Java开发工程师 | 2018.03-2020.06 | 搭建实时推荐系统，DAU提升15%"
                          ],
                          "project_ex": [
                            "分布式消息队列优化 | 技术负责人 | Kafka/Go/Prometheus | 降低端到端延迟从200ms至80ms",
                            "智能风控系统 | 核心开发者 | Spring Cloud/Redis/Elasticsearch | 拦截欺诈交易准确率达99.2%"
                          ]'
            ];

            $messages = [
                [
                    "role"=>"system",
                    "content"=> json_encode($cv_content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

                ],
                [
                    "role"=>"system",
                    "content"=> $file_id
                ],
                [
                    "role"=>"user",
                    "content"=>"请把这份简历文件给我JSON格式数据"
                ]
            ];

            $response = \app\common\service\ToolsService::Interview()->jx([
                'messages'  => $messages,
                'action' => 'qwen'
            ]);
            if (empty($response['data']['message']))
            {
                throw new Exception('简历分析失败!');
            }

            $message = $response['data']['message'];
            $json = format_json($message);
            $sex = [
                '男' => 1,
                '女' => 2
            ];

            foreach($json as $key => &$value){
                if(is_array($value)){
                    $value = json_encode($value,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                //性别替换
                if($key == 'sex'){
                    $value= $sex[$value] ?? 0;
                }
                if(empty($value)){
                    $value = '';
                }

            }
            $json['word_url'] =  $url ;
            $json['company_id'] =  $user_id;
            $json['type'] =  2;
            $json['interview_job_id'] =  $params['interview_job_id'];
            $json['user_id'] =  $params['user_id'];
            // $cvres = InterviewCv::create($json);
            // if($unit > 0){
            //     //扣除算力
            //     User::userTokensChange($user_id, $unit);
            //     $extra = [
            //         '解析简历数' => 1,
            //         "算力单价" => $unit,
            //         "实际消耗算力" => $unit
            //     ];
            //     //记录日志
            //     AccountLogLogic::recordUserTokensLog(true, $user_id, AccountLogEnum::TOKENS_DEC_AI_RESUME, $unit, $cvres->id, $extra);
            // }
            // Db::commit();  
            self::$returnData = $json;
            return true;
        } catch (\Exception $e) {
            Log::error('简历上传失败' . $e->getMessage());
            // Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    public static function formatJson($text)
    {
        $text = <<<EOT
                    {$text}
                EOT;

        // 使用正则表达式匹配JSON部分
        $jsonPattern = '/\{(?:[^{}]|(?R))*\}/';
        preg_match($jsonPattern,$text, $matches);

        // 输出匹配到的JSON字符串
        $jsonString = $matches[0] ?? 'No JSON found';

        $result = json_decode($jsonString, true);
        return $result;
    }

    public static function readWord($url = '')
    {
//        $url = 'http://ms.cc/1.docx';
        $array = parse_url($url);
        $path = public_path() . $array['path'];
        $res = self::getWord($path);
        return $res;
    }


    public static function getWord($filePath)
    {
        try {
            // 获取所有段落
            $phpWord = IOFactory::load($filePath);
            $sections =$phpWord->getSections();
            $textContent = '';

            foreach ($sections as$section) {
                $elements =$section->getElements();
                foreach ($elements as$element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        $textRunElements =$element->getElements();
                        foreach ($textRunElements as$textElement) {
                            if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                $textContent .=$textElement->getText();
                            }
                        }
                    }
                }
            }
            return $textContent;
        } catch (\PhpOffice\PhpWord\Exception\Exception $e) {
            // 捕获异常并输出错误信息
            die('Error loading DOCX file: ' . $e->getMessage());
        }

    }

    /**
     * @desc 保存简历
     * @return true
     * @date 2025/2/14 11:40
     * @author dagouzi
     */
    public static function saveCv(array $params)
    {
        if(!isset($params['interview_job_id'])){
            throw new Exception('参数缺少!');
        }

        $user_id = InterviewJob::where(['id' => $params['interview_job_id']])->value('user_id');
        if(!$user_id){
            throw new Exception('岗位不存在!');
        }
        $params['company_id'] = $user_id;
        $params['interview_job_id'] = $params['interview_job_id'];
        $cv = InterviewCv::where(['user_id' => $params['user_id'],'interview_job_id'=>$params['interview_job_id']])->findOrEmpty();


        foreach($params as $key => &$value){
            $value = trim($value);
            if($key == 'word_url'){
                continue;
            }
            if($key == 'work_url'){
                continue;
            }
            if(empty($value)){
                throw new Exception('参数不能为空');
            }
        }
        if(isset($params['work_url'])){
            unset($params['work_url']);
        }
        if ($cv->isEmpty())
        {
            InterviewCv::create($params);
        } else {

            InterviewCv::where(['id' => $cv->id])->save($params);
        }
        return true;
    }

    /**
     * @desc 开始面试
     * @param array $params
     * @return true
     * @date 2025/2/17 10:03
     * @author dagouzi
     */
    public static function start(array $params)
    {
        Db::startTrans(); // 开始事务
        try {
            $job = InterviewJob::where('id', $params['job_id'])->findOrEmpty()->toArray();
            if (empty($job)) {
                throw new Exception('岗位不存在!');
            }

            if ($job['status'] == 0) {
                throw new Exception('岗位已关闭!');
            }

            $interviewRecord = InterviewRecord::where(['user_id' => $params['user_id'], 'job_id' => $params['job_id']])->findOrEmpty()->toArray();
            if (empty($interviewRecord)) {
                $interviewCv = InterviewCv::where(['user_id' => $params['user_id'], 'interview_job_id' => $params['job_id']])->findOrEmpty()->toArray();
                if (empty($interviewCv)) {
                    throw new Exception('没有简历信息!');
                }
                $params['job_name'] = $job['name'];
                $params['interview_name'] = $interviewCv['name'];
                $params['degree'] = $interviewCv['degree'];
                $params['work_years'] = $interviewCv['work_years'];
                $params['start_time'] = time();
                $params['first_start_time'] = time();
                $params['last_end_time'] = $params['first_start_time'];
                $params['duration'] = 0;
                $interviewRecord = InterviewRecord::create($params)->toArray();

                $unit = TokenLogService::checkToken($job['user_id'], 'interview_chat');
                if ($unit > 0) {
                    User::userTokensChange($job['user_id'], $unit);
                    $extra = [
                        "AI面试次数" => 1,
                        "算力单价" => $unit,
                        "实际消耗算力" => $unit
                    ];

                    AccountLogLogic::recordUserTokensLog(true, $job['user_id'], AccountLogEnum::TOKENS_DEC_AI_INTERVIEW_CHAT, $unit, $interviewRecord['id'], $extra);

                }
            }

            $interview = Interview::where(['user_id' => $params['user_id'], 'job_id' => $params['job_id']])->order('id', 'desc')->findOrEmpty()->toArray();
            //没有面试邀约，生成一个新的
            if (empty($interview)) {
                self::$returnData = self::createInterviewAndDialog($params, $interviewRecord);
                Db::commit(); // 提交事务
                return true;
            }

            if ($interview['status'] == Interview::STATUS_RESTART) {
                self::$returnData = self::createInterviewAndDialog($params, $interviewRecord);
                InterviewRecord::update(['status' => Interview::STATUS_ONGOING], ['id' => $interviewRecord['id']]);
                Db::commit(); // 提交事务
                return true;
            }

            if (empty($interviewRecord)) {
                throw new \Exception('没有面试记录');
            }

            if ($interview['status'] == Interview::STATUS_COMPLETED) {
                $interview['msg'] = '面试已结束';
                self::$returnData = $interview;
                return true;
            }

            if ($interview['status'] == Interview::STATUS_ONGOING) {
                $interview['msg'] = '有个面试正在进行中';
                self::$returnData = $interview;
                return true;
            }

            //面试退出或者中断
            if (in_array($interview['status'], [Interview::STATUS_EXITED, Interview::STATUS_INTERRUPTED])) {
                $params['start_time'] = time();
                $params['interview_record_id'] = $interviewRecord['id'];
                $interviewnew = Interview::create($params)->toArray();
                $interviewnew['last_interview_id'] = $interview['id'];
                $interviewnew['prologue'] = '好，很高兴您能来参加本轮面试，我是你的AI面试官，先请您先做一个简单的自我介绍吧。!!';
                self::$returnData = $interviewnew;

                InterviewRecord::update(['status' => Interview::STATUS_ONGOING], ['id' => $interviewRecord['id']]);
                Db::commit(); // 提交事务
                return true;
            }

            if ($interview['status'] == Interview::STATUS_RESTART) {
                $params['start_time'] = time();
                $params['interview_record_id'] = $interviewRecord['id'];
                $interviewnew = Interview::create($params)->toArray();
                $interviewnew['prologue'] = '您好，很高兴您能来参加本轮面试，我是你的AI面试官，先请您先做一个简单的自我介绍吧。!';
                self::$returnData = $interviewnew;
                Db::commit(); // 提交事务
                return true;
            }

            throw new \Exception('本轮面试已结束');
        } catch (\Exception $e) {
            Db::rollback(); // 回滚事务
            throw new Exception($e->getMessage());
        }
    }

    public static function chat($params)
    {

        $user_id = $params['user_id'];
        $interview_id = $params['id'];
        $isEnd = 0;
        $endMessgae = '好的，大致情况我已经了解，本轮面试已结束，感谢您的配合，请提交面试过程并耐心等待通知。';
        $interview = Interview::where(['id' => $interview_id, 'user_id' => $user_id, 'status' => 0])->findOrEmpty();
        if ($interview->isEmpty())
        {
            self::$returnData = [
                'id' => $interview_id,
                'status' => 1,
                'end_message' => $endMessgae,
                'message' => '没有面试信息或已结束！'
            ];
            return true;
        }

        $interviewRecord = InterviewRecord::where(['id' => $interview->interview_record_id, 'user_id' => $user_id])
        ->whereIn('status', [0, 1])->findOrEmpty();
        if ($interviewRecord->isEmpty())
        {
            self::$returnData = [
                'id' => $interview_id,
                'status' => 1,
                'end_message' => $endMessgae,
                'message' => '没有面试信息或已结束！'
            ];
            return true;
        }

        $job = InterviewJob::findOrEmpty($interview->job_id);
        if ($job->isEmpty())
        {
            throw new Exception('没有岗位信息!');
        }

        // 1:文字 2:语音
        $dialogType = $job->type;

        $cv = InterviewCv::where(['user_id' => $user_id ,'interview_job_id' => $interview->job_id])->findOrEmpty();
        if ($cv->isEmpty())
        {
            throw new Exception('没有简历信息!');
        }

        // 修改对话的回复内容
        $curDialog = InterviewDialog::where(['interview_id' => $interview_id])->order('id DESC')->findOrEmpty();

        // 对话记录条数
        $dialogCount = InterviewDialog::where(['interview_id' => $interview_id])->count();

        if ($dialogType == 2)
        {
            $answer = self::stt($params['answer_url']);
            $curDialog->answer = $answer['message'];
            $curDialog->answer_url = $params['answer_url'];
            $curDialog->answer_duration = $answer['audio_duration'];
        } else {
            $curDialog->answer = $params['answer'];
        }
        $curDialog->save();

        // 判断当前对话类型
        $attentionArray = json_decode($job->attention, true);
        $attentionCount = count($attentionArray);   // 关注点个数

        // 检测是否已结束
        $chatTypeEnd = self::chatType($dialogCount + 1, $attentionCount);

        // coze工作流判断是否结束
        if ($chatTypeEnd != 0){
            $body = ['input'=>$curDialog->answer];
            $res = self::request($body);
            if ($res){
                $chatTypeEnd = 0;
            }
        }

        if ($chatTypeEnd == 0)
        {
            log::error('面试分析');
            // 访问通义获取评分和面试评价
            $res = Queue::push('app\common\Jobs\EndInterviewJob@handle',  $interview->id);
            log::error('面试分析'.$res);

            $interview->end_time = time();
            $interview->status = Interview::STATUS_ANALYZE;
            $interview->save();

            $duration = $interview->end_time -  $interviewRecord->first_start_time;
            $interviewRecord->duration = $duration;
            $interviewRecord->end_time = $interview->end_time;
            $interviewRecord->status = Interview::STATUS_ANALYZE;
            $interviewRecord->last_interview_id = $interview->id;
            $interviewRecord->save();


            $message = $endMessgae = '好的，大致情况我已经了解，本轮面试已结束，感谢您的配合，请提交面试过程并耐心等待通知。';
            self::$returnData = [
                'id' => $interview_id,
                'status' => 1,
                'end_message' => $endMessgae,
                'message' => $message
            ];
            return true;
        }

        $chatType = self::chatType($dialogCount, $attentionCount);
        // 1:大问题机器人 2:深入问题机器人 3:不带关注点的大问题机器人
        $message = $audio_url = '';
        $audio_duration = 0;
        if ($chatType == 1)
        {
            $dialogs = InterviewDialog::where('type', 'in', [1,4])->where('interview_id', $interview_id)->select()->toArray();
            $message = self::chat1($job, $cv, $dialogs);
            if (!empty($message))
            {
                $audioData = self::tts($message);
                $audio_url = $dialogType == 2 ? $audioData['audio_url'] : '';
                $audio_duration = $audioData['audio_duration'];
                // 新增对话记录
                InterviewDialog::create([
                    'interview_id' => $interview_id,
                    'type' => 1,
                    'question' => $message,
                    'question_url' => $audio_url,
                    'question_duration' => $audio_duration
                ]);
            }
        } elseif($chatType == 2) {
            $mainDialog = InterviewDialog
                ::where('type', 'in', [1,3])
                ->where('interview_id', $interview_id)
                ->order('id DESC')
                ->findOrEmpty();
            $dialogs = InterviewDialog::where('id', '>=', $mainDialog->id)->where('interview_id', $interview_id)->select()->toArray();

            $message = self::chat2($job, $cv, $dialogs);
            if (!empty($message))
            {
                $audioData = self::tts($message);
                $audio_url = $dialogType == 2 ? $audioData['audio_url'] : '';
                $audio_duration = $audioData['audio_duration'];
                // 新增对话记录
                InterviewDialog::create([
                    'interview_id' => $interview_id,
                    'type' => 2,
                    'question' => $message,
                    'question_url' => $audio_url,
                    'question_duration' => $audio_duration
                ]);
            }
        } elseif($chatType == 3) {
            $dialogs = InterviewDialog::where('type', 'in', [1,3])->where('interview_id', $interview_id)->select()->toArray();
            $message = self::chat3($job, $cv, $dialogs);
            if (!empty($message))
            {
                $audioData = self::tts($message);
                $audio_url = $dialogType == 2 ? $audioData['audio_url'] : '';
                $audio_duration = $audioData['audio_duration'];
                // 新增对话记录
                InterviewDialog::create([
                    'interview_id' => $interview_id,
                    'type' => 3,
                    'question' => $message,
                    'question_url' => $audio_url,
                    'question_duration' => $audio_duration
                ]);
            }
        } else {
            // 结束了
            self::$returnData = [
                'id' => $interview_id,
                'status' => 1,
                'end_message' => $endMessgae,
                'message' => '面试已结束！'
            ];
            return true;
        }
        self::$returnData = [
            'id' => $interview_id,
            'status' => $interview->status,
            'end_message' => $endMessgae,
            'message' => $message,
            'audio_url' => $audio_url,
            'audio_duration' => $audio_duration
        ];
        return true;
    }

    /**
     * @desc 判断当前对话类型
     * @param $dialogs
     * @return int
     * @date 2025/3/4 18:15
     * @author dagouzi
     */
    public static function chatType($dialogCount, $attentionCount, $deepTimes = 3)
    {
        $dialogCount = $dialogCount - 1;
        $deepStep = $deepTimes + 1;

        $total = $deepStep * $attentionCount + 4 * 5;
        if ($dialogCount - $total > -1)
        {
            return 0;
        }
        if ($dialogCount % $deepStep == 0)
        {
            return $dialogCount / $deepStep < $attentionCount ? 1 : 3;
        } else {
            return 2;
        }
    }

    /**
     * @desc 文字转语音
     * @return array
     * @date 2025/2/19 11:26
     * @author dagouzi
     */
    public static function tts($text)
    {
        $response = \app\common\service\ToolsService::Interview()->chat([
            'message'  => $text,
            'action' => 'tts'
        ]);
       
        if (empty($response) || empty($response['code']))
        {
            throw new Exception('语音转文字失败~');
        }
        if ($response['code'] == 10000)
        {
            return $response['data'];
        }
        throw new Exception('语音转文字失败~');
    }

    /**
     * @desc 语音转文字
     * @return array
     * @date 2025/2/19 11:27
     * @author dagouzi
     */
    public static function stt($voice)
    {
        $response = \app\common\service\ToolsService::Interview()->chat([
            'action' => 'stt',
            'audio_url'  => $voice
        ]);
      
        if (empty($response) || empty($response['code']))
        {
            throw new Exception('语音转文字失败~');
        }
        if ($response['code'] == 10000)
        {
            return $response['data'];
        }
        throw new Exception('语音转文字失败~');
    }

    /**
     * @desc 大问题(带关注点)
     * @param $job
     * @param $cv
     * @param $sex
     * @param $attention
     * @return mixed
     * @date 2025/2/18 10:09
     * @author dagouzi
     */
    public static function chat1($job, $cv, $dialogs)
    {
        $sex = $cv->sex == 2 ? '女' : '男';
        $attention = implode(";", json_decode($job->attention, true));
        $system = "
- 角色定位  
你是一位经验丰富的HR真人面试官，擅长通过自然交流了解候选人。你的提问风格要像真实HR一样：不能用括号、语气专业友好，问题结合候选人的具体经历，避免生硬模板化，让候选人感觉是在被“针对性了解”而非“机械提问”。

- 核心任务  
基于候选人的简历和岗位要求，生成1个面试问题。请严格遵循以下逻辑：  
-- 关键依据（必须结合）：  
1. 岗位核心信息：  
    岗位名称：{$job->name}  
    主要职责：{$job->desc}
    任职要求：{$job->jd}
    附加考察要求：{$job->extra} 
2. 候选人具体情况：
    姓名：{$cv->name} 
    性别：{$sex} 
    年龄：{$cv->age} 
    学历：{$cv->degree} 
    毕业院校：{$cv->school} 
    工作经历：{$cv->work_ex} 
    项目经历：{$cv->project_ex}
3. HR关注点：{$attention}

-- 提问要求（必须满足）：  
1.自然口语化：你是个真人HR，像日常对话一样对候选人进行提问，不能加说明，不能用括号、不能“请描述XX”“请说明XX”这种生硬开头，可用“你之前在XX公司做XX的时候，遇到过XX情况吗？”“我注意到你有XX项目经历，当时你是如何XX的？”等更自然的表达。  
2.针对性强：问题中要体现你“看过候选人简历”，比如提及具体公司、岗位、项目名称，不能泛泛而谈（例：不要问“你如何处理团队矛盾？”，而要结合简历问“你在XX公司担任XX岗位时，团队中如果出现意见分歧，你通常会怎么协调？”）。  
3.聚焦考察点：问题要让候选人的回答能直接体现HR关注点（如考察“抗压能力”，就结合其过往高压场景提问）。  
4.不重复/不解释：只说问题本身，要像真人对话一样、像真实面试中一样提问，不能用编号、不能用括号、不能说“这个问题考察了XXX”。  

- 示例（参考这种自然感和针对性）  
-- 候选人背景：
岗位：新媒体运营
候选人工作经历：有2年电商平台新媒体运营经验，负责过“618大促期间的用户评论管理”  
HR关注点：应急处理能力  

-- 输出提问：  
你之前在电商平台做新媒体运营时，618大促期间肯定遇到过用户集中投诉的情况吧？当时如果突然出现大量负面评论，你是怎么快速响应处理的？
";
        $message[] = [
            'role' => 'system',
            'content' => $system
        ];
        foreach ($dialogs as $dialog)
        {
            $message[] = [
                'role' => 'assistant',
                'content' => $dialog['question']
            ];
            $message[] = [
                'role' => 'user',
                'content' => $dialog['answer']
            ];
        }
        $response = \app\common\service\ToolsService::Interview()->chat([
            'action'    => 'chat',
            'messages'  => $message
        ]);

        if ($response['code'] == 10000 && !empty($response['data']['message']))
        {
            return $response['data']['message'];
        } else {
            throw new Exception('对话错误~');
        }
    }

    public static function chat2($job, $cv, $dialogs)
    {
        $sex = $cv->sex == 2 ? '女' : '男';
//        $curDialog = reset($dialogs);
        $curDialog = $dialogs[0];
        $system = "
- 角色定位  
你是一位经验丰富的HR真人面试官，擅长通过自然交流了解候选人。你的提问风格要像真实HR一样：不能用括号、语气专业友好，问题结合候选人的具体经历，避免生硬模板化，让候选人感觉是在被“针对性了解”而非“机械提问”。
你已经问过候选者一些问题，现在是深度追问，负责根据候选人回答递进挖掘细节。
- 核心任务  
基于候选人的简历和岗位要求，生成1个面试问题。请严格遵循以下逻辑：  
-- 关键依据（必须结合）：  
1. 岗位核心信息：  
    岗位名称：{$job->name}  
    主要职责：{$job->desc}
    任职要求：{$job->jd}
    附加考察要求：{$job->extra} 
2. 候选人具体情况：
    姓名：{$cv->name} 
    性别：{$sex} 
    年龄：{$cv->age} 
    学历：{$cv->degree} 
    毕业院校：{$cv->school} 
    工作经历：{$cv->work_ex} 
    项目经历：{$cv->project_ex}
3. 当前问题： {$curDialog['question']} 

-- 提问要求（必须满足）：  
1.自然口语化：你是个真人HR，像真人日常对话一样对候选人进行提问，不能加说明，不能用括号、不能“请描述XX”“请说明XX”这种生硬开头，可用“你之前在XX公司做XX的时候，遇到过XX情况吗？”“我注意到你有XX项目经历，当时你是如何XX的？”等更自然的表达。  
2.针对性强：问题中要体现你“看过候选人简历”，比如提及具体公司、岗位、项目名称，不能泛泛而谈（例：不要问“你如何处理团队矛盾？”，而要结合简历问“你在XX公司担任XX岗位时，团队中如果出现意见分歧，你通常会怎么协调？”）。  
3.聚焦考察点：问题要让候选人的回答能直接体现HR关注点（如考察“抗压能力”，就结合其过往高压场景提问）。  
4.不重复/不解释：只说问题本身，要像真人对话一样、像真实面试中一样提问，不能用编号、不能用括号、不能说“这个问题考察了XXX”。  

- 示例（参考这种自然感和针对性）  
-- 候选人背景：
岗位：新媒体运营
候选人工作经历：有2年电商平台新媒体运营经验，负责过“618大促期间的用户评论管理”  
HR关注点：应急处理能力  

-- 输出提问：  
你之前在电商平台做新媒体运营时，618大促期间肯定遇到过用户集中投诉的情况吧？当时如果突然出现大量负面评论，你是怎么快速响应处理的？
-- 当候选人的回答偏离问题时，输出提问：
请不要说与面试无关的事情，再胡乱聊天我只能结束这场面试了。主要聊聊你的经历，你之前在电商平台做新媒体运营时，当时如出现大量负面评论，你是怎么快速响应处理的？
";
        $message[] = [
            'role' => 'system',
            'content' => $system
        ];
        foreach ($dialogs as $dialog)
        {
            $message[] = [
                'role' => 'assistant',
                'content' => $dialog['question']
            ];
            $message[] = [
                'role' => 'user',
                'content' => $dialog['answer']
            ];
        }
      ;
        $response = \app\common\service\ToolsService::Interview()->chat([
            'action'    => 'chat',
            'messages'  => $message
        ]);
       
        if ($response['code'] == 10000 && !empty($response['data']['message']))
        {
            return $response['data']['message'];
        } else {
            throw new Exception('对话错误~');
        }
    }

    public static function chat3($job, $cv, $dialogs)
    {
        $sex = $cv->sex == 2 ? '女' : '男';
        $system = "
- 角色定位  
你是一位经验丰富的HR真人面试官，擅长通过自然交流了解候选人。你的提问风格要像真实HR一样：不能用括号、语气专业友好，问题结合候选人的具体经历，避免生硬模板化，让候选人感觉是在被“针对性了解”而非“机械提问”。
你已经问完了候选者大部分问题，基于整体表现提出针对性问题。
- 核心任务  
基于候选人的简历和岗位要求，生成1个面试问题。请严格遵循以下逻辑：  
-- 关键依据（必须结合）：  
1. 岗位核心信息：  
    岗位名称：{$job->name}  
    主要职责：{$job->desc}
    任职要求：{$job->jd}
    附加考察要求：{$job->extra} 
2. 候选人具体情况：
    姓名：{$cv->name} 
    性别：{$sex} 
    年龄：{$cv->age} 
    学历：{$cv->degree} 
    毕业院校：{$cv->school} 
    工作经历：{$cv->work_ex} 
    项目经历：{$cv->project_ex}

-- 提问要求（必须满足）：  
1.自然口语化：你是个真人HR，像日常对话一样对候选人进行提问，不能加说明，不能用括号、不能“请描述XX”“请说明XX”这种生硬开头，可用“你之前在XX公司做XX的时候，遇到过XX情况吗？”“我注意到你有XX项目经历，当时你是如何XX的？”等更自然的表达。  
2.针对性强：问题中要体现你“看过候选人简历”，比如提及具体公司、岗位、项目名称，不能泛泛而谈（例：不要问“你如何处理团队矛盾？”，而要结合简历问“你在XX公司担任XX岗位时，团队中如果出现意见分歧，你通常会怎么协调？”）。  
3.聚焦考察点：问题要让候选人的回答能直接体现HR关注点（如考察“抗压能力”，就结合其过往高压场景提问）。  
4.不重复/不解释：只说问题本身，要像真人对话一样、像真实面试中一样提问，不能用编号、不能用括号、不能说“这个问题考察了XXX”。  

- 示例（参考这种自然感和针对性）  
-- 候选人背景：
岗位：新媒体运营
候选人工作经历：有2年电商平台新媒体运营经验，负责过“618大促期间的用户评论管理”  
HR关注点：应急处理能力  

-- 输出总结：  
你的整体回答我很满意，提供你的联系方式，我们会尽快联系你进行线下面试。
-- 当候选人胡乱回答问题时，输出总结：
你的整体回答不符合我的预期，不太适合这个岗位，希望你能继续磨练自己，期待下次的面试。
";
        $message[] = [
            'role' => 'system',
            'content' => $system
        ];
        foreach ($dialogs as $dialog)
        {
            $message[] = [
                'role' => 'assistant',
                'content' => $dialog['question']
            ];
            $message[] = [
                'role' => 'user',
                'content' => $dialog['answer']
            ];
        }
        $response = \app\common\service\ToolsService::Interview()->chat([
            'action'    => 'chat',
            'messages'  => $message
        ]);
     
        if ($response['code'] == 10000 && !empty($response['data']['message']))
        {
            return $response['data']['message'];
        } else {
            throw new Exception('对话错误~');
        }
    }

    /**
     * @desc 深入问题机器人
     * @param $dialogData
     * @return mixed
     * @date 2025/2/18 10:57
     * @author dagouzi
     */
    public static function qwen($dialogs)
    {
        $qwenData = [];
        foreach ($dialogs as $item)
        {
            $qwenData[] = [
                'question' => $item['question'],
                'answer' => $item['answer'],
            ];
        }
        $messages = [
            [
                'role' => 'system',
                'content' => '{
                                 "role": "面试分析助手",
                                 "description": "你是一位专业的面试分析助手，专注于分析完整面试对话历史，并为本次面试提供总分（区间为1-100分）和详细评价，评价需公正客观且详细具体。",
                                 "interaction": {
                                  "instruction": "请根据提供的面试对话文本，为本次面试打分（总分区间为1-100分），并提供一段详细评价，评价需涵盖问题设计、追问深度、候选人表现、逻辑连贯性和整体效果等方面，且评价内容需公正客观、具体详细。",
                                  "scene_name": "AI面试总分与评价",
                                  "dialogue_text": "【面试对话内容】",
                                  "response_format": "JSON",
                                  "response_format_example": {
                                   "total_score": 0,
                                   "detailed_evaluation": ""
                                  }
                                 }
                                }'
            ],
            [
                'role' => 'user',
                'content' => "对话记录:" . json_encode($qwenData, JSON_UNESCAPED_UNICODE)
            ]
        ];

        $response = \app\common\service\ToolsService::Interview()->chat([
            'action'    => 'qwen',
            'messages'  => $messages
        ]);
        if (empty($response['data']['message']))
        {
            throw new Exception('评分失败~');
        }

        $result = format_json($response['data']['message']);
        if (empty($result['total_score']) || empty($result['detailed_evaluation']))
        {
            throw new Exception('评分失败~');
        }
        return $result;
    }

    public static function feedback(array $params)
    {
        InterviewFeedback::create($params);
        return true;
    }

    public static function getStt(array $params)
    {
        $message = self::stt($params['audio_url']);
        self::$returnData = ['message' => $message['message']];
        return true;
    }

    public static function checkInterview(array $params)
    {

        $interview = Interview::where(['user_id' => $params['user_id'], 'job_id' => $params['job_id']])
        ->whereIn('status', [2,3,4])->count();
        if($interview > 3){
            $data['type'] = 7;
            $data['msg'] = '当前面试已经超过三次！';
            self::$returnData = $data;
            return true;
        }

        $job = InterviewJob::where(['id' => $params['job_id']])->findOrEmpty()->toArray();
        if (empty($job)) {
            $data['type'] = 0;
            $data['msg'] = '岗位不存在';
            self::$returnData = $data;
            return true;
        }

        $userInfo = User::findOrEmpty($job['user_id'])->toArray();
        // if (empty($userInfo)) {
        //     $data['type'] = 8;
        //     $data['msg'] = '用户查询失败';
        //     self::$returnData = $data;
        //     return true;
        // }
        $use_token   = ModelConfig::where('scene', 'interview_chat')->value('score', 0);
        if ($userInfo['tokens'] < $use_token) {
            $data['type'] = 9;
            $data['msg'] = '当前岗位可用算力不足，请联系面试官！';
            self::$returnData = $data;
            return true;
        }
        $data['type'] = 0;
        try {


            $interviewRecord = InterviewRecord::where(['user_id' => $params['user_id'], 'job_id' => $params['job_id']])
                ->order('id', 'desc')
                ->findOrEmpty()
                ->toArray();
            if (empty($interviewRecord)) {
                // 检查是否上传了简历
                $interviewCv = InterviewCv::where(['user_id' => $params['user_id'],'interview_job_id' => $params['job_id']])
                    ->order('id', 'desc')
                    ->findOrEmpty()
                    ->toArray();
                    
                if (empty($interviewCv)) {
                    $data['type'] = 1;
                    $data['msg'] = '没有上传简历';
                    self::$returnData = $data;
                    return true;
                }
                $data['type'] = 2;
                $data['msg'] = '没有面试记录';
                self::$returnData = $data;
                return true;
            }

            $interview = Interview::where(['user_id' => $params['user_id'], 'job_id' => $params['job_id']])
                ->order('id', 'desc')
                ->findOrEmpty()
                ->toArray();
            
            if (empty($interview)) {
                $data['type'] = 5;
                $data['msg'] = '面试中断';
                self::$returnData = $data;
                return true;
            }

            if ($interview['status'] == 3) {
                $data['type'] = 4;
                $data['msg'] = '面试重新开始';
                $data['id'] = $interview['id'];
                $data['status'] = $interview['status'];
                self::$returnData = $data;
                return true;
            }

            if ($interview['status'] == 1) {
                $data['type'] = 6;
                $data['msg'] = '面试已完成';
                self::$returnData = $data;
                return true;
            }


            $data['msg'] = '上一轮面试,还没有面试完!!';
            $data['id'] = $interview['id'];
            $data['status'] = $interview['status'];
            $data['type'] = 3;
            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            // 捕获异常并设置错误信息
            throw new \Exception('系统错误: ' . $e->getMessage());
            return false;
        }
    }

    // 创建面试和插入对话开场白的通用方法
    private static function createInterviewAndDialog(array $params, array $interviewRecord): array
    {
        $params['start_time'] = time();
        $params['interview_record_id'] = $interviewRecord['id'];

        // 创建面试
        $interview = Interview::create($params)->toArray();
        $interview['prologue'] = '您好，很高兴您能来参加本轮面试，我是你的AI面试官，先请您先做一个简单的自我介绍吧。';

        // 获取岗位信息
        $job = InterviewJob::where('id', $params['job_id'])->findOrEmpty()->toArray();
        if ($job['type'] == 2) {
            $audioData = self::tts($interview['prologue']);
            $interview['audio_url'] = $audioData['audio_url'];
            $interview['audio_duration'] = $audioData['audio_duration'];
        }

        // 插入对话开场白
        InterviewDialog::create([
            'interview_id' => $interview['id'],
            'type' => 4,
            'question' => $interview['prologue'],
            'question_url' =>  $interview['audio_url'] ?? '',
            'question_duration' => $interview['audio_duration'] ?? 0,
        ]);

        return $interview;
    }

    /**
     * @throws GuzzleException
     */
    private static function request($params): bool
    {
        $interviewService = \app\common\service\ToolsService::Interview();
        $url              = $interviewService::URL;
        $workflow_id      = $interviewService::WORKFLOW_ID;
        $body             = [
            'workflow_id' => $workflow_id,
            'parameters'  => $params,
        ];
        $request          = [
            'headers' => [
                'Authorization' => 'Bearer ' . $interviewService::TOKEN,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body
        ];
        $client           = new Client(['timeout' => 6000, 'verify' => false]);
        $rsp              = $client->post($url, $request);
        $contents         = $rsp->getBody()->getContents();
        $data             = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        if (($data['code'] ?? -1) !== 0) {
            return false;
        }
        $data['data'] = json_decode($data['data'], true);
        if ($data['data']['continue'] == 1) {
            return true;
        }
        return false;
    }
}