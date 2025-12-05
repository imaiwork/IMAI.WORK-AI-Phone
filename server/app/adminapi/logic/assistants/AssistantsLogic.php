<?php

namespace app\adminapi\logic\assistants;

use app\common\logic\BaseLogic;
use app\common\model\chat\Assistants;
use app\common\model\chat\Scene;
use app\common\service\ConfigService;
use app\common\service\FileService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;

class AssistantsLogic extends BaseLogic
{
    /**
     * 添加
     * @param array $data
     * @return bool
     */
    public static function add(array $data): bool
    {
        try {
            // 检查助理名是否存在
            $assistant = Assistants::where('name', $data['name'])->findOrEmpty();
            if (!$assistant->isEmpty()) {
                self::setError('助理名称已存在');
                return false;
            }

            // 检查关联场景是否存在
            $scene = Scene::where('id', $data['scene_id'])->findOrEmpty();
            if ($scene->isEmpty()) {
                self::setError('场景分类不存在');
                return false;
            }

            if (isset($data['logo'])) {
                $data['logo'] = FileService::setFileUrl($data['logo']);
            }

            if (isset($data['preliminary_ask'])) {
                $data['preliminary_ask'] = json_encode(json_decode($data['preliminary_ask'], true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if (isset($data['template_info'])) {
                $data['template_info'] = json_encode(json_decode($data['template_info'], true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $assistant = Assistants::create($data);

            self::$returnData = $assistant->toArray();

            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @notes 助手详情
     * @param int $assistantId
     * @return array
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function detail(int $assistantId): array
    {
        $info = Assistants::where('id', $assistantId)->findOrEmpty()->toArray();

        $info['logo'] = FileService::getFileUrl($info['logo']);

        return $info;
    }

    /**
     * @notes 助手详情
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function edit(array $data): bool
    {
        try {
            $assistantInfo = Assistants::where('id', $data['id'])->findOrEmpty();

            if ($assistantInfo->isEmpty()) {
                self::setError('获取助理失败');
                return false;
            }

            if ($assistantInfo->name != $data['name']) {
                // 检查助理名是否存在
                $assistant = Assistants::where('name', $data['name'])->findOrEmpty();
                if (!$assistant->isEmpty()) {
                    self::setError('助理名称已存在');
                    return false;
                }
            }

            // 检查关联场景是否存在
            $scene = Scene::where('id', $data['scene_id'])->findOrEmpty();
            if ($scene->isEmpty()) {
                self::setError('场景分类不存在');
                return false;
            }

            if (isset($data['logo'])) {
                $data['logo'] = FileService::setFileUrl($data['logo']);
            }

            if (isset($data['preliminary_ask'])) {
                $data['preliminary_ask'] = json_encode(json_decode($data['preliminary_ask'], true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if (isset($data['template_info'])) {
                $data['template_info'] = json_encode(json_decode($data['template_info'], true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            Assistants::where('id', $assistantInfo->id)->update($data);

            self::$returnData = $assistantInfo->refresh()->toArray();

            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除助手
     * @param int $assistantId
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(int $assistantId): bool
    {
        try {
            $assistantInfo = Assistants::where('id', $assistantId)->findOrEmpty();
            if ($assistantInfo->isEmpty()) {
                throw new \Exception("助手查找异常");
            }

            Assistants::destroy(['id' => $assistantId]);
            return true;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 修改状态
     * @param int $id
     * @return bool
     * @author L
     * @data 2024/7/2 15:14
     */
    public static function changeStatus(int $id): bool
    {
        try {
            $assistantsInfo = Assistants::findOrEmpty($id);
            if ($assistantsInfo->isEmpty()) {
                throw new \Exception("助手查找异常");
            }
            $assistantsInfo->status = 1 - $assistantsInfo->status;
            $assistantsInfo->save();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @notes 通用聊天
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public static function chat()
    {
        $assistant = Assistants::where('id', 1)->findOrEmpty();

        if ($assistant->isEmpty()) {
            self::setError('助手查找异常');
            return false;
        }

        $preliminaryAsk = json_decode($assistant->preliminary_ask, true) ?? [];
        $extra          = json_decode($assistant->extra ?? '', true) ?? [];

        foreach ($preliminaryAsk as $key => $value) {

            if (isset($value['logo'])) {

                $preliminaryAsk[$key]['logo'] = FileService::getFileUrl($value['logo']);
            }
        }
        $image = ConfigService::get('website', 'chat_logo', '');
        $image = empty($image) ? $image : FileService::getFileUrl($image);
        $assistant->preliminary_ask     = $preliminaryAsk;
        $assistant->logo                = $image;
        $assistant->banner              = FileService::getFileUrl($extra['banner'] ?? '');
        $assistant->new_chat_prompt     = $extra['new_chat_prompt'] ?? '';
        $assistant->file_prompt         = $extra['file_prompt'] ?? '';
        $assistant->extra               = $extra;

        self::$returnData = $assistant->toArray();
        return true;
    }


    /**
     * @notes 更新通用聊天
     * @param array $post
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public static function updateChat(array $postData)
    {
        $assistant = Assistants::where('id', 1)->findOrEmpty();
        if ($assistant->isEmpty()) {
            self::setError('助手查找异常');
            return false;
        }

        $preliminaryAsk = $postData['preliminary_ask'] ?? [];

        foreach ($preliminaryAsk as $key => $value) {

            if (isset($value['logo'])) {

                $preliminaryAsk[$key]['logo'] = FileService::setFileUrl($value['logo']);
            }
        }

        $assistant->extra       = json_encode([
            'banner'            => FileService::setFileUrl($postData['banner']),
            'new_chat_prompt'   => $postData['new_chat_prompt'],
            'file_prompt'       => $postData['file_prompt'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $image = isset($postData['logo']) ? FileService::setFileUrl($postData['logo']) : '';
        $assistant->logo            = ConfigService::set('website', 'chat_logo', $image);
        $assistant->preliminary_ask = json_encode($preliminaryAsk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $assistant->save();
        self::$returnData = $assistant->toArray();
        return true;
    }

    public static function check(){
        $exists = Db::name('assistants')
            ->where('name', '社区关系建设方案')
            ->find();
        if ($exists){
            return false;
        }
        return true;
    }

    public static function import()
    {
        try {
            $inputFileName = public_path().'static/file/template/assistants.xlsx';
            if (!file_exists($inputFileName)) {
                throw new \Exception("文件不存在");
            }

            $spreadsheet = IOFactory::load($inputFileName);

            // 获取活动工作表内容
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            Db::startTrans();          // 事务
            $inserted = 0;

            foreach ($rows as $k => $row) {
                if ($k == 1) continue; // 跳过表头
                $number = random_int(1, 50);
                $logo =   'static/images/assistant/assistant' . $number . '.png';

                $level2 = $row['B'] != '' ? trim($row['B']) : '';
                $C =  $row['C'] != '' ? trim($row['C']) : '';
                $D =  $row['D'] != '' ? trim($row['D']) : '';
                $E =  $row['E'] != '' ? trim($row['E']) : '';
                $F =  $row['F'] != '' ? trim($row['F']) : '';
                $G =  $row['G'] != '' ? trim($row['G']) : '';
                $H =  $row['H'] != '' ? trim($row['H']) : '';
                $I =  $row['I'] != '' ? trim($row['I']) : '';
                $J =  $row['J'] != '' ? trim($row['J']) : '';

                /* ---------- 二级场景 ---------- */
                $exists = Db::name('scene')
                    ->where('name', $level2)
                    ->whereNull('delete_time')
                    ->find();

                if ($exists) {

                    $template_info = [
                        'form' => [
                            [
                                'name' => 'WidgetInput',
                                'title' => '单行文本',
                                'id' =>  '1',
                                'props' => [
                                    'field' => 'Input1',
                                    'title' => $G,
                                    'placeholder' => '',
                                    'maxlength' => 200,
                                    'isRequired' => false
                                ]
                            ],
                            [
                                'name' => 'WidgetInput',
                                'title' => '单行文本',
                                'id' => '2',
                                'props' => [
                                    'field' => 'Input2',
                                    'title' => $H,
                                    'placeholder' => '',
                                    'maxlength' => 200,
                                    'isRequired' => false
                                ]
                            ],
                            [
                                'name' => 'WidgetInput',
                                'title' => '单行文本',
                                'id' => '3',
                                'props' => [
                                    'field' => 'Input3',
                                    'title' => $I,
                                    'placeholder' => '',
                                    'maxlength' => 200,
                                    'isRequired' => false
                                ]
                            ],
                            [
                                'name' => 'WidgetInput',
                                'title' => '单行文本',
                                'id' => '4',
                                'props' => [
                                    'field' => 'Input4',
                                    'title' => $J,
                                    'placeholder' => '',
                                    'maxlength' => 200,
                                    'isRequired' => false
                                ]
                            ]
                        ]
                    ];

                    // 转换为JSON格式
                    $template_info_json = json_encode($template_info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                    Db::name('assistants')->insert([
                        'scene_id'         => $exists['id'],
                        'name'        => $C,
                        'description'        => $E,
                        'instructions' => $D,
                        'preliminary_ask' => '[{"value":""}]',
                        'template_info' => $template_info_json,
                        'form_info' =>  $F,

                        'logo'        => $logo,
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);
                    $inserted++;
                }
            }
            if ($inserted == 0){
                throw new \Exception("先导入助理分类");
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }
}
