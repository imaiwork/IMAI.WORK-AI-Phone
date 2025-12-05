<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvMaterial;

class SvMaterialLogic extends SvBaseLogic
{
    public static function addSvMaterial(array $params)
    {
        // 添加素材逻辑
        try {
            // 获取信息
            $account = self::accountInfo($params['account'], false);
            if (!$account) {
                self::setError('账号不存在');
                return false;
            }


            $contents = json_decode( $params['content'],true);
            $data =[];
            foreach ( $contents as  $content) {
                $content = json_encode($content,JSON_UNESCAPED_UNICODE);
                $res = SvMaterial::where('content', $content)
                    ->where('account',$params['account'])
                    ->where('m_type',$params['m_type'])
                    ->where('user_id', self::$uid)
                    ->find();
                if ($res){
                    continue;
                }
                $data[] = [
                    'user_id' => self::$uid,
                    'account' => $params['account'],
                    'content' =>  $content,
                    'sort' => $params['sort'] ?? 0,
                    'create_time' => time(),
                    'm_type'=> $params['m_type'],
                    'account_no'=>$params['account_no'],
                    'type'=>$params['type']

                ];
            }

            (new SvMaterial())->saveAll($data);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateSvMaterial(array $params)
    {
        // 更新素材逻辑
        try {
            $material = SvMaterial::where('id',$params['id'])->where('user_id', self::$uid)
                ->findOrEmpty();
            if (!$material) {
                self::setError('素材不存在');
                return false;
            }
            $material->update($params);
            self::$returnData = $material->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function deleteSvMaterial(int $id)
    {
        // 删除素材逻辑
        try {
            $material = SvMaterial::where('id',$id)->where('user_id', self::$uid)
                ->findOrEmpty();
            if (!$material) {
                self::setError('素材不存在');
                return false;
            }
            $material->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getSvMaterial(array $params)
    {
        $material = SvMaterial::where('id',$params['id'])->where('user_id', self::$uid)
            ->findOrEmpty();
        if (!$material) {
            self::setError('素材不存在');
            return false;
        }
        self::$returnData = $material->toArray();
        return true;
    }


}