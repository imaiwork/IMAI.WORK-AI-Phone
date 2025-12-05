<?php


declare(strict_types=1);

namespace app\common\validate;

use app\common\service\JsonService;
use think\Validate;

class BaseValidate extends Validate
{
    public string $method = 'GET';

    /**
     * @notes 设置请求方式
     * @author 令狐冲
     * @date 2021/12/27 14:13
     */
    public function post($pc = 0)
    {
        if (!$this->request->isPost() && !$pc) {
            JsonService::throw('请求方式错误，请使用post请求方式');
        } else if (!$this->request->isPost() && $pc) {
            JsonService::throw('请求方式错误，请使用post请求方式', [], 0, 0);
        }
        $this->method = 'POST';
        return $this;
    }

    /**
     * @notes 设置请求方式
     * @author 令狐冲
     * @date 2021/12/27 14:13
     */
    public function get($pc = 0)
    {
        if (!$this->request->isGet() && !$pc) {
            JsonService::throw('请求方式错误，请使用get请求方式');
        } else if (!$this->request->isGet() && $pc) {
            JsonService::throw('请求方式错误，请使用get请求方式', [], 0, 0);
        }
        return $this;
    }


    /**
     * @notes 切面验证接收到的参数
     * @param null $scene 场景验证
     * @param array $validateData 验证参数，可追加和覆盖掉接收的参数
     * @param int $pc
     * @return array
     * @author 令狐冲
     * @date 2021/12/27 14:13
     */
    public function goCheck($scene = null, array $validateData = [], int $pc = 0): array
    {
        //接收参数
        if ($this->method == 'GET') {
            $params = request()->get();
        } else {
            $params = request()->post();
        }
        //合并验证参数
        $params = array_merge($params, $validateData);

        //场景
        if ($scene) {
            $result = $this->scene($scene)->check($params);
        } else {
            $result = $this->check($params);
        }

        if (!$result) {
            $exception = is_array($this->error) ? implode(';', $this->error) : $this->error;
            if ($pc) {
                JsonService::pc_throw($exception);
            } else {
                JsonService::throw($exception);
            }
        }
        // 3.成功返回数据
        return $params;
    }
}
