<?php
/*
* Copyright (c) 2017 Baidu.com, Inc. All Rights Reserved
*
* Licensed under the Apache License, Version 2.0 (the "License"); you may not
* use this file except in compliance with the License. You may obtain a copy of
* the License at
*
* Http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
* WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
* License for the specific language governing permissions and limitations under
* the License.
*/
namespace aip;

use aip\lib\AipBase;

require_once 'lib/AipBase.php';

/**
 * 黄反识别
 */
class AipContentCensor extends AipBase
{

    /**
     * @var string
     */
    private $imageCensorUserDefinedUrl = 'https://aip.baidubce.com/rest/2.0/solution/v1/img_censor/v2/user_defined';

    /**
     * @var string
     */
    private $textCensorUserDefinedUrl = 'https://aip.baidubce.com/rest/2.0/solution/v1/text_censor/v2/user_defined';

    /**
     * @var string
     */
    private $voiceCensorUserDefinedUrl = 'https://aip.baidubce.com/rest/2.0/solution/v1/voice_censor/v3/user_defined';

    /**
     * @var string
     */
    private $videoCensorUserDefinedUrl = 'https://aip.baidubce.com/rest/2.0/solution/v1/video_censor/v2/user_defined';

    private $liveSaveV1Url = 'https://aip.baidubce.com/rest/2.0/solution/v1/live/v1/config/save';
    private $liveStopV1Url = 'https://aip.baidubce.com/rest/2.0/solution/v1/live/v1/config/stop';
    private $liveViewV1Url = 'https://aip.baidubce.com/rest/2.0/solution/v1/live/v1/config/view';
    private $livePullV1Url = 'https://aip.baidubce.com/rest/2.0/solution/v1/live/v1/audit/pull';
    private $videoCensorSubmitV1Url = 'https://aip.baidubce.com/rest/2.0/solution/v1/video_censor/v1/video/submit';
    private $videoCensorPullV1Url = 'https://aip.baidubce.com/rest/2.0/solution/v1/video_censor/v1/video/pull';
    private $asyncVoiceSubmitV1Url = 'https://aip.baidubce.com/rest/2.0/solution/v1/async_voice/submit';
    private $asyncVoicePullV1Url = 'https://aip.baidubce.com/rest/2.0/solution/v1/async_voice/pull';


    /**
     * @param string $image 图像
     * @return array
     */
    public function imageCensorUserDefined($image)
    {

        $data = array();

        $isUrl = substr(trim($image), 0, 4) === 'http';
        if (!$isUrl) {
            $data['image'] = base64_encode($image);
        } else {
            $data['imgUrl'] = $image;
        }

        return $this->request($this->imageCensorUserDefinedUrl, $data);
    }

    /**
     * @param string $text
     * @return array
     */
    public function textCensorUserDefined($text)
    {

        $data = array();

        $data['text'] = $text;

        return $this->request($this->textCensorUserDefinedUrl, $data);
    }

    /**
     * @param string $voice
     * @param string $rate
     * @param string $fmt
     * @return array
     */
    public function voiceCensorUserDefined($voice, $rate, $fmt, $options = array())
    {

        $data = array();

        $data['base64'] = base64_encode($voice);
        $data['fmt'] = $fmt;
        $data['rate'] = $rate;
        $data = array_merge($data, $options);
        return $this->request($this->voiceCensorUserDefinedUrl, $data);
    }

    /**
     * @param string $url
     * @param string $rate
     * @param string $fmt
     * @return array
     */
    public function voiceUrlCensorUserDefined($url, $rate, $fmt, $options = array())
    {

        $data = array();
        $data['url'] = $url;
        $data['fmt'] = $fmt;
        $data['rate'] = $rate;
        $data = array_merge($data, $options);
        return $this->request($this->voiceCensorUserDefinedUrl, $data);
    }

    /**
     * @param string $name
     * @param string $videoUrl
     * @param string $extId
     * @return array
     */
    public function videoCensorUserDefined($name, $videoUrl, $extId, $options = array())
    {

        $data = array();

        $data['name'] = $name;
        $data['videoUrl'] = $videoUrl;
        $data['extId'] = $extId;
        $data = array_merge($data, $options);
        return $this->request($this->videoCensorUserDefinedUrl, $data);
    }

    /**
     * 内容审核平台-直播流（新增任务）
     * 接口使用文档链接: https://ai.baidu.com/ai-doc/ANTIPORN/mkxlraoz5
     */
    public function liveSaveV1($streamUrl, $streamType, $extId, $startTime, $endTime, $streamName, $options = array())
    {
        $data = array();
        $data['streamUrl'] = $streamUrl;
        $data['streamType'] = $streamType;
        $data['extId'] = $extId;
        $data['startTime'] = $startTime;
        $data['endTime'] = $endTime;
        $data['streamName'] = $streamName;

        $data = array_merge($data, $options);
        return $this->request($this->liveSaveV1Url, $data);
    }

    /**
     * 内容审核平台-直播流（删除任务）
     * 接口使用文档链接: https://ai.baidu.com/ai-doc/ANTIPORN/Ckxls2owb
     */
    public function liveStopV1($taskId, $options = array())
    {
        $data = array();
        $data['taskId'] = $taskId;

        $data = array_merge($data, $options);
        return $this->request($this->liveStopV1Url, $data);
    }

    /**
     * 内容审核平台-直播流（查看配置）
     * 接口使用文档链接: https://ai.baidu.com/ai-doc/ANTIPORN/ckxls6tl1
     */
    public function liveViewV1($taskId, $options = array())
    {
        $data = array();
        $data['taskId'] = $taskId;

        $data = array_merge($data, $options);
        return $this->request($this->liveViewV1Url, $data);
    }

    /**
     * 内容审核平台-直播流（获取结果）
     * 接口使用文档链接: https://ai.baidu.com/ai-doc/ANTIPORN/Pkxlshd1s
     */
    public function livePullV1($taskId, $options = array())
    {
        $data = array();
        $data['taskId'] = $taskId;

        $data = array_merge($data, $options);
        return $this->request($this->livePullV1Url, $data);
    }

    /**
     * 内容审核平台-长视频（提交任务）
     * 接口使用文档链接: https://ai.baidu.com/ai-doc/ANTIPORN/bksy7ak30
     */
    public function longVideoCensorSubmit($url, $extId, $options = array())
    {
        $data = array();
        $data['url'] = $url;
        $data['extId'] = $extId;

        $data = array_merge($data, $options);
        return $this->request($this->videoCensorSubmitV1Url, $data);
    }

    /**
     * 内容审核平台-长视频（获取结果）
     * 接口使用文档链接: https://ai.baidu.com/ai-doc/ANTIPORN/jksy7j3jv
     */
    public function longVideoCensorPull($taskId, $options = array())
    {
        $data = array();
        $data['taskId'] = $taskId;

        $data = array_merge($data, $options);
        return $this->request($this->videoCensorPullV1Url, $data);
    }

    /**
     * 音频文件异步审核
     * 接口使用文档链接: https://ai.baidu.com/ai-doc/ANTIPORN/akxlple3t
     */
    public function asyncVoiceCensorSubmit($url, $fmt, $rate, $options = array())
    {
        $data = array();
        $data['url'] = $url;
        $data['fmt'] = $fmt;
        $data['rate'] = $rate;

        $data = array_merge($data, $options);
        return $this->request($this->asyncVoiceSubmitV1Url, $data);
    }

    /**
     * 音频文件异步审核-查询
     * 接口使用文档链接: https://ai.baidu.com/ai-doc/ANTIPORN/jkxlpxllo
     */
    public function asyncVoiceCensorPullByTaskId($taskId, $options = array())
    {
        $data = array();
        $data['taskId'] = $taskId;

        $data = array_merge($data, $options);
        return $this->request($this->asyncVoicePullV1Url, $data);
    }

    /**
     * 音频文件异步审核-查询
     * 接口使用文档链接: https://ai.baidu.com/ai-doc/ANTIPORN/jkxlpxllo
     */
    public function asyncVoiceCensorPullByAudioId($audioId, $options = array())
    {
        $data = array();
        $data['audioId'] = $audioId;

        $data = array_merge($data, $options);
        return $this->request($this->asyncVoicePullV1Url, $data);
    }
} 
