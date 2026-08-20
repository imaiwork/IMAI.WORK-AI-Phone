<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\VideoSliceLists;
use app\api\logic\aiPersona\VideoSliceLogic;
use app\api\validate\aiPersona\MaterialSliceValidate;
use think\exception\HttpResponseException;
use think\response\Json;

class VideoSliceController extends BaseApiController
{
    public function options(): Json
    {
        return $this->success('获取成功', VideoSliceLogic::sliceOptions());
    }

    public function quote(): Json
    {
        try {
            $params = (new MaterialSliceValidate())->post()->goCheck('quote');
            $result = VideoSliceLogic::sliceQuote($this->userId, $params);
            return $result ? $this->success('获取成功', VideoSliceLogic::getReturnData()) : $this->fail(VideoSliceLogic::getError(), VideoSliceLogic::getReturnData());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '参数错误');
        }
    }

    public function confirm(): Json
    {
        try {
            $params = (new MaterialSliceValidate())->post()->goCheck('confirm');
            $result = VideoSliceLogic::sliceConfirm($this->userId, $params);
            return $result ? $this->success('提交成功', VideoSliceLogic::getReturnData()) : $this->fail(VideoSliceLogic::getError(), VideoSliceLogic::getReturnData());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '参数错误');
        }
    }

    public function batchDetail(): Json
    {
        try {
            $params = (new MaterialSliceValidate())->get()->goCheck('batchDetail');
            $result = VideoSliceLogic::sliceBatchDetail($this->userId, (string)$params['batch_no']);
            return $result ? $this->success('获取成功', VideoSliceLogic::getReturnData()) : $this->fail(VideoSliceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '参数错误');
        }
    }

    public function activeBatch(): Json
    {
        $personaId = (int)$this->request->get('persona_id', 0);
        $scene = (string)$this->request->get('scene', 'persona');
        if ($personaId <= 0 || !in_array($scene, ['ai_creation', 'persona'], true)) {
            return $this->fail('参数错误');
        }
        $result = VideoSliceLogic::activeSliceBatch($this->userId, $personaId, $scene);
        return $result ? $this->success('获取成功', VideoSliceLogic::getReturnData()) : $this->fail(VideoSliceLogic::getError());
    }

    public function statistics(): Json
    {
        $params = $this->request->param();
        $personaId = (int)($params['persona_id'] ?? 0);
        $scene = (string)($params['scene'] ?? 'persona');
        if ($personaId <= 0) {
            return $this->fail('参数错误');
        }
        if (!in_array($scene, ['ai_creation', 'persona'], true)) {
            $scene = 'persona';
        }

        $fileIds = $this->normalizeVideoIds($params['file_ids'] ?? $params['original_video_ids'] ?? []);
        if (empty($fileIds)) {
            $single = (int)($params['file_id'] ?? $params['original_video_id'] ?? 0);
            if ($single > 0) {
                $fileIds = [$single];
            }
        }

        // 时间下限：默认今天 00:00:00，规避历史旧切割数据；可传 from_time / from_date 覆盖
        $fromTime = $this->resolveStatisticsFromTime($params);

        $result = VideoSliceLogic::statistics($this->userId, $personaId, $scene, $fileIds, $fromTime);
        if ($result === false) {
            return $this->fail(VideoSliceLogic::getError());
        }

        return $this->success('获取成功', VideoSliceLogic::getReturnData());
    }

    /**
     * 统计时间下限（unix）。
     * - from_time：unix 时间戳
     * - from_date：Y-m-d，取当天 00:00:00
     * - 都不传：默认今天 00:00:00
     */
    private function resolveStatisticsFromTime(array $params): int
    {
        if (array_key_exists('from_time', $params) && $params['from_time'] !== '' && $params['from_time'] !== null) {
            return max(0, (int)$params['from_time']);
        }
        if (!empty($params['from_date'])) {
            $ts = strtotime(trim((string)$params['from_date']) . ' 00:00:00');
            return $ts !== false ? (int)$ts : (int)strtotime('today');
        }

        return (int)strtotime('today');
    }

    private function normalizeVideoIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }

    public function lists(): Json
    {
        return $this->dataLists(new VideoSliceLists());
    }
}
