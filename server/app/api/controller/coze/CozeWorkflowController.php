<?php

namespace app\api\controller\coze;

use app\api\controller\BaseApiController;
use app\api\lists\coze\CozeAgentLists;
use app\api\logic\coze\CozeAgentLogic;
use app\api\logic\coze\CozeConfigLogic;
use app\api\logic\coze\CozeWorkflowLogic;
use app\api\validate\coze\CozeAgentValidate;
use think\exception\HttpResponseException;

class CozeWorkflowController extends BaseApiController
{
    public function run()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);
        try {
            $params = $this->request->post();
            $workflow = new CozeWorkflowLogic();
            $params['taskid'] = generate_unique_task_id();

            $result = $workflow->run($params);
            if ($result) {
                return $this->success(data: CozeWorkflowLogic::getReturnData());
            }

        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
