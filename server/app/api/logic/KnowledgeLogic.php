<?php


namespace app\api\logic;

use app\api\logic\service\TokenLogService;
use app\common\enum\FileEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotRecord;
use app\common\model\knowledge\Knowledge;
use app\common\model\knowledge\KnowledgeBind;
use app\common\model\knowledge\KnowledgeFile;
use app\common\model\knowledge\KnowledgeFileSlice;
use app\common\model\knowledge\KnowledgeRetrieve;
use app\common\model\knowledge\KnowledgeRetrieveSlice;
use app\common\model\knowledge\KnowledgeUseSceneRecord;
use app\common\model\user\User;
use app\common\service\FileService;
use app\common\service\UploadService;
use app\common\service\WordsService;
use Exception;

/**
 * index
 * Class IndexLogic
 * @package app\api\logic
 */
class KnowledgeLogic extends ApiLogic
{
    const KNOELEDGE_CREATE = 'knowledge_create'; //知识库创建
    const KNOELEDGE_RETRIEVE = 'knowledge_retrieve'; //知识库检索
    const KNOELEDGE_CHAT = 'knowledge_chat'; //知识库聊天
    const RERANK_MIN_SCORE = 0.2; //知识库检索最小分数
    const OPENAI_CHAT = 'openai_chat'; //openai聊天
    const GEMINI_CHAT = 'gemini_chat'; //gemini聊天
    const GPT_MODELS = [
        'gpt-4',
        'gpt-4o',
        'gpt-4o-mini',
        'gpt-4o-2024-08-06',
        'gpt-3.5-turbo',
    ];
    const GEMINI_MODELS = [
        'gemini-2.5-pro',
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemma-3-4b-it',
    ];
    /**
     * 知识库列表
     *
     * @param array $data
     * @return void
     */
    public static function getListData(array $params)
    {
        $page = $params['page_no'] ?? 1;
        $size = $params['page_size'] ?? 10;
        $params['name'] = $params['name'] ?? '';
        $result = Knowledge::where(['user_id' => self::$uid])
            ->when($params['name'], function ($query) use ($params) {
                $query->where('name', 'like', '%' . $params['name'] . '%');
            })
            ->limit(($page - 1) * $size, $size)
            ->select()
            ->each(function ($item) {
                $item->file_count = KnowledgeFile::where(['index_id' => $item['index_id']])->count();
                $item->file_counts = $item->file_count;
                $item->token_counts = KnowledgeUseSceneRecord::where(['index_id' => $item['index_id']])->sum('tokens');
                $item->request_counts = KnowledgeUseSceneRecord::where(['index_id' => $item['index_id']])->count();
            })
            ->toArray();

        $data = [
            'lists' => $result,
            'count' => Knowledge::where(['user_id' => self::$uid])
                ->when($params['name'], function ($query) use ($params) {
                    $query->where('name', 'like', '%' . $params['name'] . '%');
                })->count(),
            'page_no' => $params['page_no'] ?? 1,
            'page_size' => $params['page_size'] ?? 10,
        ];

        return $data;
    }


    /**
     * 知识库创建
     *
     * @param array $data
     * @return void
     */
    public static function add(array $params)
    {

        if (!isset($params['name']) || empty($params['name'])) {
            throw new \Exception('知识库名称不能为空');
        }

        if (mb_strlen($params['name'], "UTF-8") > 12) {
            throw new \Exception('知识名称不能超过12个字符');
        }

        if (mb_strlen($params['description'], "UTF-8") > 1000) {
            throw new \Exception('知识库描述不能超过1000个字符');
        }

        $find = Knowledge::where('name', $params['name'])->where('user_id', self::$uid)->limit(1)->find();
        if (!empty($find)) {
            message('知识库名称已存在');
        }

        if (strpos($params['description'], $_SERVER['HTTP_HOST']) === false) {
            $params['description'] =  $params['description'];
        }


        $params['site'] = $_SERVER['HTTP_HOST'];

        # 检查创建知识库的算力是否足够,不够则提示
        //计费
        $unit = TokenLogService::checkToken(self::$uid, self::KNOELEDGE_CREATE);
        # 1 创建分类
        $knowledgeNmae = $params['name'] . bin2hex(random_bytes(4));
        $cateRes = \app\common\service\ToolsService::Knowledge()->createCategory([
            'name' => $knowledgeNmae
        ]);

        if ((int)$cateRes['code'] === 10000) {
            if (isset($cateRes['data']['status']) && (int)$cateRes['data']['status'] === 1) {
                message($cateRes['data']['msg']);
                return false;
            }

            # 2 创建同名分类知识库
            $params['category_id'] = $cateRes['data']['CategoryId'];

            $params['task_id'] = generate_unique_task_id();
            $request = $params;
            $request['name'] = $knowledgeNmae;
            $request['description'] = $params['site'] . ' ' . $params['description'];
            $indexRes = self::requestUrl($request, self::KNOELEDGE_CREATE, self::$uid);
            if ($indexRes) {
                unset($params['task_id']);

                $addData = $params;
                $addData['user_id'] =  self::$uid;
                $addData['index_id'] = $indexRes['Id'];
                $addData['category_id'] = $cateRes['data']['CategoryId'];
                $addData['create_time'] = time();
                $addData['status'] = 1;
                $addData['is_bind'] = 0;
                $addData['rerank_min_score'] = $params['rerank_min_score'] ?? self::RERANK_MIN_SCORE;
                if (isset($params['image'])) {
                    $addData['image'] = FileService::setFileUrl($params['image']);
                }
                $detial =  new Knowledge();
                if ($detial->save($addData)) {
                    if (!empty($params['documents'])) {

                        $file_ids = array_column($params['documents'], 'file_id');
                        $addDocRes =  \app\common\service\ToolsService::Knowledge()->addDocJobIndex([
                            'indexid' => $indexRes['Id'],
                            'documentIds' => $file_ids,
                        ]);

                        if ((int)$addDocRes['code'] === 10000) {
                            $docs =  array_map(function ($doc) use ($detial, $params, $indexRes) {
                                $_tmp = array();
                                $_tmp['file_id'] = $doc['file_id'];
                                $_tmp['category_id'] = $doc['category_id'] ?? '';
                                $_tmp['status'] = $doc['status'] ?? 'PARSE_SUCCESS';
                                $_tmp['type'] = $doc['type'] ?? '';
                                $_tmp['name'] = $doc['name'];
                                $_tmp['size'] = $doc['size'] ?? 0;
                                $_tmp['parser'] = $doc['parser'] ?? 'DASHSCOPE_DOCMIND';
                                $_tmp['user_id'] =  self::$uid;
                                $_tmp['index_id'] = $indexRes['Id'];
                                $_tmp['kid'] = $detial->id;
                                $_tmp['file_url'] =  $doc['uri'] ?? ($doc['file_url'] ?? '');
                                $_tmp['create_time'] = time();
                                return $_tmp;
                            }, $params['documents']);

                            if (!empty($docs)) {
                                $detialFile = new KnowledgeFile();
                                $detialFile->saveAll($docs);
                            }
                        } else {
                            message($addDocRes['message']);
                        }
                    }
                    self::$returnData = $detial->toArray();
                    return true;
                }
            } else {
                message('知识库创建失败');
            }
        } else {
            message($cateRes['message']);
        }
    }

    public static function edit(array $params)
    {
        $find = Knowledge::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
        if ($find->isEmpty()) {
            message('知识库不存在');
        }
        if (mb_strlen($params['name'], "UTF-8") > 12) {
            throw new \Exception('知识名称不能超过12个字符');
        }

        if (mb_strlen($params['description'], "UTF-8") > 1000) {
            message('知识库描述不能超过1000个字符');
        }

        $row = Knowledge::where('name', $params['name'])->where('id', '<>', $params['id'])->where('user_id', self::$uid)->limit(1)->find();
        if (!empty($row)) {
            message('知识库名称重复');
        }

        // if($params['overlap_size'] > $params['chunk_size']){
        //     message('分段预估长度必须大于分段重叠长度');
        // }
        if (isset($params['image'])) {
            $addData['image'] = FileService::setFileUrl($params['image']);
        }
        $addData = $params;
        $addData['update_time'] = time();
        unset($addData['create_time']);

        if ($find->save($addData)) {
        }

        self::$returnData = $find->toArray();
        return true;
    }

    public static function edit1(array $params)
    {
        $find = Knowledge::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
        if ($find->isEmpty()) {
            message('知识库不存在');
        }
        if (strlen($params['description']) > 1000) {
            message('知识库描述不能超过1000个字符');
        }




        $addData = $params;
        $addData['update_time'] = time();
        unset($addData['create_time']);

        if ($find->save($addData)) {
            KnowledgeFile::where('user_id', '=', self::$uid)->where('kid', '=', $params['id'])->select()->delete();

            if (!empty($params['documents'])) {
                $file_ids = array_column($params['documents'], 'file_id');
                //先删除在添加
                $delDocRes =  \app\common\service\ToolsService::Knowledge()->deleteDocIndex([
                    'indexid' => $find['index_id'],
                    'documentids' => $file_ids,
                ]);
                if ((int)$delDocRes['code'] === 10000) {
                    $addDocRes =  \app\common\service\ToolsService::Knowledge()->addDocJobIndex([
                        'indexid' => $find['index_id'],
                        'documentIds' => $file_ids,
                    ]);
                    if ((int)$addDocRes['code'] !== 10000) {
                        message($addDocRes['message']);
                    }
                    $docs =  array_map(function ($doc) use ($find, $params) {

                        $_tmp['file_id'] = $doc['file_id'];
                        $_tmp['category_id'] = $doc['category_id'] ?? '';
                        $_tmp['status'] = $doc['status'] ?? 'PARSE_SUCCESS';
                        $_tmp['type'] = $doc['type'] ?? '';
                        $_tmp['name'] = $doc['name'];
                        $_tmp['size'] = $doc['size'] ?? 0;
                        $_tmp['parser'] = $doc['parser'] ?? 'DASHSCOPE_DOCMIND';
                        $_tmp['user_id'] =  self::$uid;
                        $_tmp['index_id'] = $find['index_id'];
                        $_tmp['kid'] = $find->id;
                        $_tmp['file_url'] = $doc['uri'] ?? ($doc['file_url'] ?? '');
                        $_tmp['create_time'] = time();

                        return $_tmp;
                    }, $params['documents']);

                    if (!empty($docs)) {
                        $detialFile = new KnowledgeFile();
                        $detialFile->saveAll($docs);
                    }
                } else {
                    message($delDocRes['message']);
                }
            }
        }

        self::$returnData = $find->toArray();
        return true;
    }

    public static function detail(array $param)
    {
        $find = Knowledge::where('id', $param['id'])->limit(1)->find();
        if (empty($find)) {
            message('知识库不存在');
        }

        $files = KnowledgeFile::field('*')->where(['user_id' => self::$uid])->where('kid', $find['id'])->select()
            ->toArray();

        if (!empty($files)) {


            $find->is_bind = 1;
            $find->update_time = time();
            $find->save();
        }
        $result = $find->toArray();
        $result['documents'] = $files;
        return $result;
    }


    /**
     * 知识库删除
     *
     * @param array $params
     * @return bool
     */
    public static function delete(array $params)
    {

        $find = Knowledge::where('id', $params['id'])->where('user_id', self::$uid)->fetchSql(false)->limit(1)->find();
        if (empty($find)) {
            message('知识库不存在');
        }

        try {

            // 请求查询接口
            $response = \app\common\service\ToolsService::Knowledge()->deleteIndex([
                'id' => $find['index_id'],
            ]);

            $cateRes = \app\common\service\ToolsService::Knowledge()->deleteCategory([
                'categoryid' => $find['category_id'],
            ]);


            if ((int)$response['code'] !== 10000) {
                message($response['message']);
            }

            Knowledge::destroy(['id' => $params['id'], 'user_id' => self::$uid]);
            KnowledgeFile::destroy(['kid' => $params['id'], 'user_id' => self::$uid]);

            // 删除机器人绑定关系
            $robot = (new KbRobot())->where('kb_ids', 'like', '%' . $params['id'] . '%')->select()->toArray();
            if ($robot) {
                foreach ($robot as $item) {
                    $item['kb_ids'] = explode(',', $item['kb_ids']);
                    foreach ($item['kb_ids'] as $key => $value) {
                        if ($value == $params['id']) {
                            unset($item['kb_ids'][$key]);
                        }
                    }
                    KbRobot::update([
                                        'kb_ids' => implode(',', $item['kb_ids'])
                                    ], ['id' => $item['id']]);
                }
                KnowledgeBind::where('kid', $params['id'])->delete();
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }


    /**
     * 知识库检索
     *
     * @param array $params
     * @return void
     */
    public static function retrieve(array $params)
    {

        if (!isset($params['prompt']) || empty($params['prompt'])) {
            message('提示词 不能为空');
        }
        try {
            // 请求查询接口
            $response = \app\common\service\ToolsService::Knowledge()->retrieveIndex($params);

            if ((int)$response['code'] !== 10000) {
                message($response['message']);
            }

            return self::__insertRetrieveData($params, $response['data']);
        } catch (\Throwable $e) {

            message($e->getMessage());
        }
    }

    private static function __insertRetrieveData(array $params, array $data)
    {
        $knowledge = Knowledge::where('index_id', $params['indexid'])->where('user_id', self::$uid)->fetchSql(false)->limit(1)->find();


        if (empty($knowledge)) {
            message('知识库不存在');
        }
        try {
            $retrieveData = [
                'user_id' => self::$uid,
                'kid' => $knowledge['id'],
                'index_id' => $params['indexid'],
                'rerank_min_score' => $params['rerank_min_score'] ?? self::RERANK_MIN_SCORE, // 默认为0
                'prompt' => $params['prompt'],
                'create_time' => time(),
            ];

            $_retrieve = new KnowledgeRetrieve();
            if ($_retrieve->save($retrieveData)) {
                if (isset($data['Nodes']) && !empty($data['Nodes'])) {
                    $items = array_map(function ($item) use ($_retrieve, $params) {
                        $hash = hash_hmac('sha256', $item['Text'], 'knowledge');
                        $_row = KnowledgeRetrieveSlice::field('content, hash')
                            ->where('index_id', $params['indexid'])
                            ->where('user_id', self::$uid)
                            ->where('rid',  $_retrieve->id)
                            ->where('hash', $hash)
                            ->limit(1)->find();

                        if (empty($_row)) {
                            $filename = KnowledgeFile::field('name')
                                ->where('file_id', $item['Metadata']['doc_id'])
                                ->where('index_id', $params['indexid'])
                                //->where('user_id', self::$uid)
                                ->limit(1)->value('name');
                            $tmp = [
                                'user_id' => self::$uid,
                                'rid' => $_retrieve->id,
                                'index_id' => $params['indexid'],
                                'content' => $item['Text'],
                                'score' => $item['Score'],
                                'hash' => $hash,
                                'metadata' => json_encode($item['Metadata'], JSON_UNESCAPED_UNICODE), // 假设Metadata是一个数组，你可以根据实际情况调整
                                'create_time' => time(),
                                'source' => $filename ?? $item['Metadata']['doc_name'],
                            ];
                            return $tmp;
                        }
                    }, $data['Nodes']);
                    $items = array_filter($items);
                    if (!empty($items)) {
                        $_slice = new KnowledgeRetrieveSlice();
                        $_slice->saveAll($items);
                    }


                    return $items;
                }
            }
            return [];
        } catch (\Exception $e) {

            message($e->getMessage());
        }
    }


    public static function historyTest(array $params)
    {
        $params['page_no'] = $params['page_no'] ?? 1;
        $params['page_size'] =  $params['page_size'] ?? 15;
        $pageSize = $params['page_size'];
        $pageNo = ($params['page_no'] - 1) * $params['page_size'];
        $keywords = $params['keywords'] ?? '';

        if (!isset($params['indexid']) || empty($params['indexid'])) {
            message('知识库ID 不能为空');
        }

        $list = KnowledgeRetrieve::where('user_id', self::$uid)
            ->where('index_id', $params['indexid'])
            ->when($keywords, function ($query) use ($keywords) {
                $query->where('prompt', 'like', '%' . $keywords . '%');
            })
            ->limit($pageNo, $pageSize)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
        $data = [
            'lists' => $list,
            'count' =>  KnowledgeRetrieve::where('user_id', self::$uid)
                ->where('index_id', $params['indexid'])
                ->when($keywords, function ($query) use ($keywords) {
                    $query->where('prompt', 'like', '%' . $keywords . '%');
                })->count(),
            'page_no' => $params['page_no'],
            'page_size' => $params['page_size'],
        ];
        return $data;
    }

    public static function testDetail(array $params)
    {
        $params['page_no'] = $params['page_no'] ?? 1;
        $params['page_size'] =  $params['page_size'] ?? 15;
        $pageSize = $params['page_size'];
        $pageNo = ($params['page_no'] - 1) * $params['page_size'];

        if (!isset($params['id']) || empty($params['id'])) {
            message('检索ID 不能为空');
        }

        $list = KnowledgeRetrieveSlice::where('user_id', self::$uid)
            ->where('rid', $params['id'])
            ->order('create_time', 'desc')
            ->limit($pageNo, $pageSize)
            ->select()
            ->toArray();

        $data = [
            'lists' => $list,
            'count' =>  KnowledgeRetrieveSlice::where('user_id', self::$uid)->where('rid', $params['id'])->count(),
            'page_no' => $params['page_no'],
            'page_size' => $params['page_size'],
        ];
        return $data;
    }

    /**
     * 知识库详情-文档列表
     *
     * @param array $params
     * @return void
     */
    public static function indexFileList(array $param)
    {

        $find = Knowledge::where('id', $param['id'])->limit(1)->find();

        if (empty($find)) {
            message('知识库不存在');
        }

        $result = KnowledgeFile::where(['user_id' => self::$uid])->where('index_id', $find['index_id'])->select()
            ->toArray();

        $data = [
            'lists' => $result,
            'count' => KnowledgeFile::where(['user_id' => self::$uid])->where('index_id', $find['index_id'])->count(),
            'page_no' => $params['page_no'] ?? 1,
            'page_size' => $params['page_size'] ?? 10,
        ];

        return $data;
    }
    /**
     * 知识库分片
     *
     * @param array $params
     * @return void
     */
    public static function chunkLists(array $params)
    {
        try {

            // 请求查询接口
            $response = \app\common\service\ToolsService::Knowledge()->chunkIndex($params);

            if ((int)$response['code'] !== 10000) {
                message($response['message']);
            }



            return $response['data'];
        } catch (\Throwable $e) {
            message($e->getMessage());
        }
    }



    public static function fileUpload(array $params)
    {

        if (!isset($params['indexid']) || empty($params['indexid'])) {
            message('请选择知识库');
        }
        $index = Knowledge::where('index_id', $params['indexid'])->limit(1)->find();
        if (empty($index)) {
            message('知识库不存在');
        }

        $file = request()->file('file');
        $exts = [
            'doc',
            'docx',
            'wps',
            'ppt',
            'pptx',
            'xls',
            'xlsx',
            'md',
            'txt',
            'pdf',
            'png',
            'jpg',
            'jpeg',
            'bmp',
            'gif',
            'aac',
            'amr',
            'flac',
            'flv',
            'm4a',
            'mp3',
            'mpeg',
            'ogg',
            'opus',
            'wav',
            'webm',
            'wma',
            'mp4',
            'mkv',
            'avi',
            'mov',
            'wmv'
        ];
        if ($file) {
            $ext = $file->getOriginalExtension();
            if (!in_array($ext, $exts)) {
                message('知识库暂时不支持csv文件');
            }
        }

        try {
            $result = UploadService::file(0, self::$uid, FileEnum::SOURCE_USER, 'uploads/file/knowledge');
            if (!empty($result)) {

                $response = \app\common\service\ToolsService::Knowledge()->createFile([
                    'file_url' => $result['uri'],
                    'category_id' => $index['category_id']
                ]);
                if ((int)$response['code'] === 10000) {
                    $fileinfo = \app\common\service\ToolsService::Knowledge()->infoFile([
                        'id' => $response['data']['FileId']
                    ]);
                    if ((int)$fileinfo['code'] === 10000) {
                        $result['category_id'] = $fileinfo['data']['CategoryId'];
                        $result['status'] = $fileinfo['data']['Status'];
                        $result['type'] = $fileinfo['data']['FileType'];
                        $result['name'] = $result['name'] ?? $fileinfo['data']['FileName'];
                        $result['size'] = $fileinfo['data']['SizeInBytes'];
                        $result['parser'] = $fileinfo['data']['Parser'];
                    }

                    $result['file_id'] = $response['data']['FileId'];
                    $result['parser'] = $response['data']['Parser'];
                    return $result;
                } else {
                    message($response['message']);
                }
            } else {
                message('文件上传失败');
            }
        } catch (\Exception $e) {
            message($e->getMessage());
        }
    }

    public static function fileLists(array $params)
    {

        $pageNo = ($params['page_no'] - 1) * $params['page_size'];
        $pageSize = $params['page_size'];

        $name = $params['name'] ?? '';
        $takeover_mode = $params['takeover_mode'] ?? '';
        $modes = array(
            0 => 'PARSING',
            1 => 'PARSE_SUCCESS',
            2 => 'PARSE_FAILED'
        );
        $status = $modes[$takeover_mode] ?? ''; // 默认为0，即未完成的任务，你可以根据需要修改这个值

        $result = KnowledgeFile::where(['user_id' => self::$uid])
            ->where('category_id', $params['category_id'])
            ->when($name, function ($query) use ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', '=', $status);
            })
            ->limit($pageNo, $pageSize)
            ->select()
            ->toArray();
        $data = [
            'lists' => $result,
            'count' => KnowledgeFile::where(['user_id' => self::$uid])
                ->when($name, function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })
                ->when($status, function ($query) use ($status) {
                    $query->where('status', '=', $status);
                })
                ->where('category_id', $params['category_id'])->count(),
            'page_no' => $params['page_no'],
            'page_size' => $params['page_size'],
        ];

        return $data;
    }


    public static function fileAdd(array $params)
    {
        $find = Knowledge::where('category_id', $params['category_id'])->limit(1)->find();
        if (empty($find)) {
            message('知识库不存在');
        }


        $addData = array(
            'description' => $params['description'] ?? $find['description'],
            'rerank_min_score' => $params['rerank_min_score'] ?? ($find['rerank_min_score'] ?? self::RERANK_MIN_SCORE), // 默认为0
            'separator' => $params['separator'] ?? $find['separator'],
            'chunk_size' => $params['chunk_size'] ?? $find['chunk_size'],
            'overlap_size' => $params['overlap_size'] ?? $find['overlap_size'],
            'structure_type' => $params['structure_type'] ?? $find['structure_type'],
            'source_type' => $params['source_type'] ?? $find['source_type'],
            'sink_type' => $params['sink_type'] ?? $find['sink_type'],
            'strategy' => $params['strategy'] ?? $find['strategy'],
            'is_bind' => !empty($params['documents']) ? 1 : 0,
            'site' => $params['site'] ?? $_SERVER['HTTP_HOST'],
            'update_time' => time(),
        );

        // if($addData['overlap_size'] > $addData['chunk_size']){
        //     message('分段预估长度必须大于分段重叠长度');
        // }

        if (!$find->save($addData)) {
            message('知识库信息更新失败');
        }


        if (!empty($params['documents'])) {
            foreach ($params['documents'] as $key => $val) {
                $file = KnowledgeFile::where('file_id', $val['file_id'])->where('user_id', self::$uid)->limit(1)->find();
                if (!empty($file)) {
                    unset($params['documents'][$key]);
                }
            }

            if (empty($params['documents'])) {
                message('请上传文件');
            }

            $fileids = array_column($params['documents'], 'file_id');
            $addDocRes =  \app\common\service\ToolsService::Knowledge()->addDocJobIndex([
                'indexid' => $find['index_id'],
                'documentIds' => $fileids
            ]);



            if ((int)$addDocRes['code'] === 10000) {
                if (isset($addDocRes['data']['status']) && (int)$addDocRes['data']['status'] === 400) {
                    $find->delete();
                    message('知识库数据异常:' . $addDocRes['data']['msg']);
                }

                $docs =  array_map(function ($doc) use ($find, $params) {
                    $_tmp = array();
                    $_tmp['file_id'] = $doc['file_id'];
                    $_tmp['category_id'] = $params['category_id'];
                    $_tmp['status'] = $doc['status'] ?? 'INIT';
                    $_tmp['type'] = $doc['type'] ?? '';
                    $_tmp['name'] = $doc['name'];
                    $_tmp['size'] = $doc['size'] ?? 0;
                    $_tmp['parser'] = $doc['parser'] ?? 'DASHSCOPE_DOCMIND';
                    $_tmp['user_id'] =  self::$uid;
                    $_tmp['index_id'] = $find['index_id'];
                    $_tmp['kid'] = $find->id;
                    $_tmp['file_url'] =  $doc['uri'] ?? ($doc['file_url'] ?? '');
                    $_tmp['create_time'] = time();
                    return $_tmp;
                }, $params['documents']);

                if (!empty($docs)) {
                    $detialFile = new KnowledgeFile();
                    $detialFile->saveAll($docs);
                }
                return true;
            } else {
                message($addDocRes['message']);
            }
        }

        return true;
    }


    public static function fileDetial(array $params)
    {

        $find = KnowledgeFile::where(['user_id' => self::$uid])->where('file_id', $params['file_id'])->limit(1)->find();

        if (empty($find)) {
            message('文件不存在');
        }
        if ($find['status'] !== 'PARSE_SUCCESS') {
            $fileinfo = \app\common\service\ToolsService::Knowledge()->infoFile([
                'id' => $find['file_id']
            ]);
            if ((int)$fileinfo['code'] === 10000) {

                $find['status'] = $fileinfo['data']['Status'];
                $find['update_time'] = time();
                $find['type'] = $fileinfo['data']['FileType'];
                $find['name'] = $fileinfo['data']['FileName'];
                $find['size'] = $fileinfo['data']['SizeInBytes'];
                $find['parser'] = $fileinfo['data']['Parser'];

                $find->save();
            }
        }



        return $find->toArray();
    }

    public static function fileDelete(array $params)
    {
        $find = KnowledgeFile::where('id', $params['id'])->where('user_id', self::$uid)->fetchSql(false)->limit(1)->find();
        if (empty($find)) {
            message('文档不存在');
        }
        KnowledgeFile::destroy(['id' => $params['id'], 'user_id' => self::$uid]);

        // 请求查询接口
        $response = \app\common\service\ToolsService::Knowledge()->deleteFile([
            'id' => $find['file_id']
        ]);
        // if((int)$response['code'] !== 10000){
        //     //throw new \Exception($response['message']);
        //     message($response['message']);
        // }
        return true;
    }

    /**
     * 知识库分片
     *
     * @param array $params
     * @return void
     */
    public static function fileChunkLists(array $params)
    {
        $find = KnowledgeFile::where('id', $params['id'])->where('user_id', self::$uid)->fetchSql(false)->limit(1)->find();
        if (empty($find)) {
            message('文档不存在');
        }
        $params['page_no'] = $params['page_no'] ?? 1;
        $params['page_size'] =  $params['page_size'] ?? 15;
        $pageSize = $params['page_size'];

        $pageNo = ($params['page_no'] - 1) * $params['page_size'];
        $keywords = $params['keywords'] ?? '';

        $result = KnowledgeFileSlice::where('file_id', $find['file_id'])
            ->where('index_id', $find['index_id'])
            ->where('user_id', self::$uid)
            ->when($keywords, function ($query) use ($keywords) {
                $query->where('content', 'like', '%' . $keywords . '%');
            })
            ->limit($pageNo, $pageSize)
            ->select()->toArray();
        if (empty($result)) {
            // 请求查询接口
            $response = \app\common\service\ToolsService::Knowledge()->chunkIndex([
                'indexid' => $find['index_id'],
                'fileid' => $find['file_id'],
                'pageNum' => 1,
                'pageSize' => 100,
            ]);
            if ((int)$response['code'] === 10000) {
                if (!isset($response['data']['Nodes'])) {
                    message('查询失败');
                }
                $nodes = $response['data']['Nodes'];
                if (!empty($nodes)) {
                    $items = array_map(function ($item) use ($find, $params) {
                        $hash = hash_hmac('sha256', $item['Text'], 'knowledge');
                        $_row = KnowledgeFileSlice::field('content, hash')
                            ->where('file_id', $find['file_id'])
                            ->where('index_id', $find['index_id'])
                            ->where('user_id', self::$uid)
                            ->where('hash', $hash)
                            ->limit(1)->find();
                        if (empty($_row)) {
                            $tmp = [
                                'user_id' => self::$uid,
                                'rid' => $find->id,
                                'index_id' => $find['index_id'],
                                'file_id' => $find['file_id'],
                                'content' => $item['Text'],
                                'score' => $item['Score'],
                                'hash' => $hash,
                                'metadata' => json_encode($item['Metadata'], JSON_UNESCAPED_UNICODE), // 假设Metadata是一个数组，你可以根据实际情况调整
                                'create_time' => time(),
                                'source' => $find['name'] ?? $item['Metadata']['doc_name']
                            ];
                            return $tmp;
                        }
                    }, $nodes);
                    $items = array_filter($items);
                    if (!empty($items)) {
                        $_slice = new KnowledgeFileSlice();
                        $_slice->saveAll($items);
                    }
                }
            }

            $result = KnowledgeFileSlice::where('user_id', self::$uid)
                ->where('index_id', $find['index_id'])
                ->where('file_id', $find['file_id'])
                ->when($keywords, function ($query) use ($keywords) {
                    $query->where('content', 'like', '%' . $keywords . '%');
                })
                ->limit($pageNo, $pageSize)
                ->select()
                ->each(function ($item) {
                    $item['metadata'] = json_decode($item['metadata'], true); // 假设metadata是一个JSON字符串
                    return $item;
                })
                ->toArray();
        }

        $data = [
            'lists' => $result,
            'count' => KnowledgeFileSlice::where('user_id', self::$uid)
                ->where('index_id', $find['index_id'])
                ->where('file_id', $find['file_id'])
                ->when($keywords, function ($query) use ($keywords) {
                    $query->where('content', 'like', '%' . $keywords . '%');
                })->count(),
            'page_no' => $params['page_no'],
            'page_size' => $params['page_size'],
        ];
        return $data;
    }

    public static function updateTagFile(array $params)
    {
        try {
            // 请求查询接口
            $response = \app\common\service\ToolsService::Knowledge()->updateTagFile($params);
            if ((int)$response['code'] !== 10000) {
                throw new \Exception($response['message']);
            }
            return $response['data'];
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 绑定知识库
     *
     * @param array $params
     * @param [type] $data
     * @return void
     */
    public static function bind(array $params, $data)
    {
        //删除原来的绑定 添加新的绑定信息
        KnowledgeBind::where('data_id', $data['id'])
            ->where('user_id', self::$uid)
            ->where('type', $params['type'])
            ->select()
            ->delete();

        if (isset($params['index_id']) && !empty($params['index_id'])) {
            $knowledge = Knowledge::where('index_id', $params['index_id'])->limit(1)->find();
            if (empty($knowledge)) {
                throw new \Exception('知识库不存在');
                return false;
            }

            //挂载知识库
            KnowledgeBind::create([
                'user_id' => self::$uid,
                'kid' => $knowledge['id'],
                'data_id' => $data['id'],
                'type' =>  $params['type'],
                'kb_type' => $params['kb_type'] ?? 1,
                'index_id' => $params['index_id'],
                'rerank_min_score' => $params['rerank_min_score'] ?? self::RERANK_MIN_SCORE,
                'create_time' => time(),
            ]);
        }

//        if ($params['kb_type'] == 2){
//            $knowledge = KbKnow::where('id', $params['kb_id'])->limit(1)->find();
//            //挂载向量知识库
//            KnowledgeBind::create([
//                                      'user_id' => self::$uid,
//                                      'kid' => $knowledge['id'],
//                                      'data_id' => $data['id'],
//                                      'type' =>  $params['kb_type'],
//                                      'index_id' => '',
//                                      'rerank_min_score' => $params['rerank_min_score'] ?? self::RERANK_MIN_SCORE,
//                                      'create_time' => time(),
//                                  ]);
//        }
    }

    /**
     * 新绑定知识库
     *
     * @param array $params
     * @param [type] $data
     * @param $kbType
     * @return void
     * @throws Exception
     */
    public static function newBind(array $params, $data, $kbType)
    {
        //删除原来的绑定 添加新的绑定信息
        KnowledgeBind::where('data_id', $data['id'])
            ->where('user_id', self::$uid)
            ->where('type', $params['type'])
            //->where('kb_type', $kbType)
            ->select()
            ->delete();

        if (isset($params['index_id']) && !empty($params['index_id']) && $kbType == 1) {
            $knowledge = Knowledge::where('index_id', $params['index_id'])->findOrEmpty();
        } else {
            $knowledge = KbKnow::where('id', $params['id'])->findOrEmpty();
        }


        if ($knowledge->isEmpty()) {
            throw new \Exception('知识库不存在');
            return false;
        }
        //挂载知识库
        KnowledgeBind::create([
            'user_id' => self::$uid,
            'kid' => $knowledge['id'],
            'data_id' => $data['id'],
            'type' =>  $params['type'],
            'kb_type' =>  $kbType,
            'index_id' => $params['index_id'] ?? '',
            'rerank_min_score' => $params['rerank_min_score'] ?? self::RERANK_MIN_SCORE,
            'create_time' => time(),
        ]);
    }

    /**
     * 将训练内容上传到知识库
     *
     * @param array $params
     * @return void
     */
    public static function ladderPlayerUpload(array $params)
    {
        # 1 根据已经选择的数据生成文本文件
        # 2 上传文件到分类
        # 3 文档追加到指定的知识库
        # 4 获取文件的信息

        if (!isset($params['ids']) || empty($params['ids'])) {
            message('请选择需要上传的记录');
        }
        $knowledge = Knowledge::where('index_id', $params['indexid'])->where('user_id', self::$uid)->limit(1)->find();
        if (empty($knowledge)) {
            message('知识库不存在');
        }
        # 提取文本信息
        $data = \app\common\model\lianlian\LlChat::alias('c')
            ->field('c.ask, c.reply, c.performance, c.speechcraft')
            ->join('ll_analysis l', 'l.id = c.analysis_id', 'left')
            ->where('l.id', 'in', $params['ids'])
            ->select()->toArray();


        try {

            $content = array();
            foreach ($data as $item) {
                $content[] = $item['ask'] . "\n" . $item['reply'] . "\n" . $item['performance'] . "\n" . $item['speechcraft'];
            }
            $content = implode("\n", $content);

            $uri = 'uploads/file/knowledge/' . date('Ymd') . '/话术1.txt';
            $filepath = root_path() . '/public/' . $uri;

            !is_dir(dirname($filepath)) && mkdir(dirname($filepath), 0777, true);
            $size = file_put_contents($filepath, $content);
            if ($size === false) {
                message('文件写入失败');
            }
            $uri =  config('app.app_host') . '/' . $uri;

            $fileRes = \app\common\service\ToolsService::Knowledge()->createFile([
                'file_url' => $uri,
                'category_id' => $knowledge['category_id']
            ]);

            if ((int)$fileRes['code'] === 10000) {
                $fileinfo = \app\common\service\ToolsService::Knowledge()->infoFile([
                    'id' => $fileRes['data']['FileId']
                ]);

                if ((int)$fileinfo['code'] === 10000) {

                    $addDocRes =  \app\common\service\ToolsService::Knowledge()->addDocJobIndex([
                        'indexid' => $knowledge['index_id'],
                        'documentIds' => [$fileRes['data']['FileId']]
                    ]);
                    if ((int)$addDocRes['code'] === 10000) {
                        $result = array();
                        $result['file_id'] =  $fileRes['data']['FileId'];
                        $result['category_id'] = $knowledge['category_id'];
                        $result['status'] = $fileinfo['data']['Status'];
                        $result['type'] = $fileinfo['data']['FileType'];
                        $result['name'] = $result['name'] ?? $fileinfo['data']['FileName'];
                        $result['size'] = $fileinfo['data']['SizeInBytes'];
                        $result['parser'] = $fileinfo['data']['Parser'];
                        $result['user_id'] =  self::$uid;
                        $result['index_id'] = $knowledge['index_id'];
                        $result['kid'] = $knowledge->id;
                        $result['file_url'] =  $uri;
                        $result['create_time'] = time();

                        $detialFile = new KnowledgeFile();
                        $detialFile->save($result);

                        self::$returnData = $result;
                        return true;
                    } else {
                        message('文件追加到知识库失败');
                    }
                } else {
                    message('获取文件信息失败');
                }
            } else {
                message('上传文件失败');
            }
        } catch (\Throwable $th) {

            message($th->getMessage());
        }
        return false;
    }


    /******************请求接口以及扣费************************ */

    /**
     * 请求上游接口与计费
     * @param array $request
     * @param string $scene
     * @param int $userId
     * @param string $taskId
     * @return array
     * @throws \Exception
     */
    private static function requestUrl(array $request, string $scene, int $userId, array $record = [], bool $isReturn = false): array

    {

        $requestService = \app\common\service\ToolsService::Knowledge();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::KNOELEDGE_CREATE => ['knowledge_create', AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CREATE],
            self::KNOELEDGE_RETRIEVE => ['knowledge_retrieve', AccountLogEnum::TOKENS_DEC_KNOWLEDGE_RETRIEVE],
            self::KNOELEDGE_CHAT => ['knowledge_chat', AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT],
            self::OPENAI_CHAT => ['openai_chat', AccountLogEnum::TOKENS_DEC_OPENAI_CHAT],
            self::GEMINI_CHAT => ['gemini_chat', AccountLogEnum::TOKENS_DEC_GEMINI_CHAT],
        };

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);

        switch ($scene) {
            case self::KNOELEDGE_CREATE:
                $response = $requestService->createIndex($request);
                break;
            case self::KNOELEDGE_RETRIEVE:
                $response = $requestService->retrievePrompt($request);
                break;
            case self::KNOELEDGE_CHAT:
                $response = $requestService->promptChat($request);
                break;
            case self::OPENAI_CHAT:
                $response = $requestService->openaiChat($request);
                break;
            case self::GEMINI_CHAT:
                $response = $requestService->geminiChat($request);
                break;
            default:
        }
        //print_r($response);die;

        if (in_array($scene, [self::KNOELEDGE_CHAT, self::OPENAI_CHAT, self::GEMINI_CHAT]) && $request['stream'] == true) {
            exit;
        }

        //clogger('response: '. json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {

            if ($scene === self::KNOELEDGE_CREATE) {
                $tokens = $unit;
                $points = $unit;
                $knowlwdge_tokens = $points;
            } else {
                $usage = $response['data']['usage'];
                $tokens = $usage['total_tokens'] + $request['knowledge_tokens'];
                //计算消耗tokens
                $points = $unit > 0 ? round($tokens / $unit, 2) : 0;
                $knowlwdge_tokens = $request['knowledge_tokens'];
            }

            //clogger($points.'|'.$userId);

            if ($points > 0) {

                $extra = ['总消耗tokens数' => $tokens, '知识库消耗tokens数' => $knowlwdge_tokens,  '算力单价' => $unit, '实际消耗算力' => $points];
                //clogger('extra: '. json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                //token扣除
                User::userTokensChange($userId, $points);
                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $request['task_id'], $extra);

                self::saveKnowledgeRecord($request, $response['data']);
            }
        }

        if ((int)$response['code'] === 10005) {
            if ($isReturn) {
                return $response;
            } else {
                message($response['message']);
            }
        }

        return $response['data'] ?? [];
    }

    public static function saveKnowledgeRecord(array $request, array $response)
    {
        try {
            $record = $request['knowledge_record'];
            $record['content'] = $response['content'] ?? ($response['choices'][0]['message']['content'] ?? '');
            $record['prompt_tokens'] =  $response['usage']['prompt_tokens'];
            $record['completion_tokens'] = $response['usage']['completion_tokens'];
            $record['total_tokens'] = $response['usage']['total_tokens'];
            $record['tokens'] =  $record['retrieve_tokens'] + $record['total_tokens'];
            $record['task_id'] = $request['task_id'];
            $record['create_time'] = time();
            $res = (new KnowledgeUseSceneRecord())->save($record);

            $knowlwdge = Knowledge::where('index_id', $request['indexid'])->where('user_id', $request['user_id'])->fetchSql(false)->limit(1)->find();
            //请求成功知识库调用+1
            $knowlwdge->inc('request_count', 1)->inc('tokens', $record['retrieve_tokens'])->save();
        } catch (\Throwable $th) {
            //clogger($th);

        }
    }


    /******************定时任务-文件状态************************ */
    public static function setFileStatus()
    {
        try {
            // clogger('setFileStatus ' . date('Y-m-d H:i:s', time()), 'cron');
            $files = KnowledgeFile::where('status', 'not in', ['PARSE_SUCCESS', 'PARSE_FAILED'])->order('id asc')->limit(10)->select();

            foreach ($files as $key => $file) {
                //clogger('开始处理文件：' . $file['file_id'], 'cron');
                $fileinfo = \app\common\service\ToolsService::Knowledge()->infoFile([
                    'id' => $file['file_id']
                ]);
                if ((int)$fileinfo['code'] === 10000) {
                    if (isset($fileinfo['data']['no_exist']) && (int)$fileinfo['data']['no_exist'] === 1) {
                        $file['status'] = 'PARSE_FAILED';
                        $file['remark'] = $fileinfo['data']['msg'];
                        //clogger('文件不存在：'. $file['file_id'], 'cron');
                    } else {
                        $file['status'] = $fileinfo['data']['Status'];
                    }

                    $file['update_time'] = time();
                    $file->save();
                }
                sleep(2);
            }
            return true;
        } catch (\Throwable $th) {
            //clogger('文件处理失败：'. $th->getMessage(), 'cron');
            return false;
        }
    }


    public static function fileChunksPull()
    {
        try {
            //clogger('fileChunksPull:' . date('Y-m-d H:i:s', time()), 'cron');
            $files = KnowledgeFile::where('status', 'PARSE_SUCCESS')
                ->where('is_completed', 0)
                ->order('update_time asc')->limit(5)->select();

            foreach ($files as $key => $file) {
                //clogger('开始处理文件：'. $file['file_id'], 'cron');
                # 首次拉取 
                # 按照分页拉取

                $totalRes = \app\common\service\ToolsService::Knowledge()->chunkIndex([
                    'indexid' => $file['index_id'],
                    'fileid' => $file['file_id'],
                    'pageNum' => 1,
                    'pageSize' => 1,
                ]);

                if ((int)$totalRes['code'] === 10000) {
                    if (isset($totalRes['data']['no_exist']) && (int)$totalRes['data']['no_exist'] === 1) {
                        $file['status'] = 'PARSE_FAILED';
                        //clogger('文件或知识库不存在：'. $file['file_id'].'||'. $file['index_id'], 'cron');
                        //删除本地文件
                        $file['status'] = 'PARSE_FAILED';
                        $file['remark'] = $totalRes['data']['msg'];
                        $file['is_completed'] = 1;
                        $file['update_time'] = time();
                        $file['delete_time'] = time();
                        $file->save();
                    } else {
                        $total = $totalRes['data']['Total'];

                        $totalPage = ceil($total / 100);
                        for ($page = 1; $page <= $totalPage; $page++) {
                            $response = \app\common\service\ToolsService::Knowledge()->chunkIndex([
                                'indexid' => $file['index_id'],
                                'fileid' => $file['file_id'],
                                'pageNum' => $page,
                                'pageSize' => 100,
                            ]);
                            if ((int)$response['code'] === 10000) {
                                $nodes = $response['data']['Nodes'];
                                if (!empty($nodes)) {
                                    $items = array_map(function ($item) use ($file) {
                                        $hash = hash_hmac('sha256', $item['Text'], 'knowledge');
                                        $_row = KnowledgeFileSlice::field('content, hash')
                                            ->where('file_id', $file['file_id'])
                                            ->where('index_id', $file['index_id'])
                                            ->where('user_id', $file['user_id'])
                                            ->where('hash', $hash)
                                            ->limit(1)->find();
                                        if (empty($_row)) {
                                            $tmp = [
                                                'user_id' => $file['user_id'],
                                                'rid' => $file->id,
                                                'index_id' => $file['index_id'],
                                                'file_id' => $file['file_id'],
                                                'content' => $item['Text'],
                                                'score' => $item['Score'],
                                                'hash' => $hash,
                                                'metadata' => json_encode($item['Metadata'], JSON_UNESCAPED_UNICODE), // 假设Metadata是一个数组，你可以根据实际情况调整
                                                'create_time' => time(),
                                                'source' => $file['name'] ?? $item['Metadata']['doc_name']
                                            ];
                                            return $tmp;
                                        }
                                    }, $nodes);
                                    $items = array_filter($items);
                                    if (!empty($items)) {
                                        $_slice = new KnowledgeFileSlice();
                                        $_slice->saveAll($items);
                                    }
                                }
                            }
                            sleep(2);
                        }
                        $file['is_completed'] = 1;
                        $file['slice_count'] = $total;
                        $file['update_time'] = time();
                        $file->save();
                    }
                }
                sleep(2);
            }
            return true;
        } catch (\Throwable $th) {
            //clogger('文件处理失败：'. $th->getMessage(), 'cron');
            return false;
        }
    }


    /******************chat************************ */
    public static function chat($params)
    {
        set_time_limit(0);
        if (!isset($params['message']) || empty($params['message'])) {
            message('提示词 不能为空');
        }
        WordsService::sensitive($params['message']);
        // 问题审核(百度)
        WordsService::askCensor($params['message']);
        $uid = $params['user_id'] ?? self::$uid;
        // 兼容发布聊天，取消user_id冗余条件
//        $knowlwdge = Knowledge::where('index_id', $params['indexid'])->where('user_id', $uid)->fetchSql(false)->limit(1)->find();
        $knowlwdge = Knowledge::where('index_id', $params['indexid'])->fetchSql(false)->limit(1)->find();
        if (empty($knowlwdge)) {
            message('知识库不存在');
        }
        $message = $params['message'];
        // 表单变量替换
        $message_ext = $params['message_ext'] ?? '';

        if ($message_ext) {
            $message_ext_text = self::parseMsg($message_ext, '');
            $message = $message_ext_text . $message;
        }


        $request = [
            'indexid' => $params['indexid'],
            'robot_id' => $params['robot_id'] ?? 0,
            'prompt' => $message,
            'rerank_min_score' => $params['rerank_min_score'] ?? ($knowledge['rerank_min_score'] ?? self::RERANK_MIN_SCORE), // 默认为0
            'stream' =>  (bool)$params['stream'] ?? true,
            'task_id' => $params['task_id'] ?? generate_unique_task_id(),
            'scene' => $params['scene'] ?? '未知聊天',
            'assistant_id' => $params['assistant_id'] ?? 0,
            'model' => $params['model'] ?? 'deepseek', // 默认为默认模型
            'temperature' => $params['temperature'] ?? 1.0, //温度
            'top_p'       => $params['top_p'] ?? 0.5, //多样性范围
            'presence_penalty' => $params['presence_penalty'] ?? 0.2, //避免重复力度
            'frequency_penalty' => $params['frequency_penalty'] ?? 0.3, //避免重复用词力度
            'max_tokens' => $params['max_tokens'] ?? 4096, //token上限
            'context_num' => $params['context_num'] ?? 3, //上下文数
            'file_info' => $params['file_info'] ?? [], //文件信息
            //            'channel' => $params['channel'] ?? 0, //应用渠道 1:个微 2:小红书 3:通用聊天
        ];

        if ($params['scene'] == '陪练聊天') {
            $request['voice'] = $params['voice'] ?? '';
            $request['emotion'] =  $params['emotion'] ?? '';
            $request['intensity'] =  $params['intensity'] ?? '';
        }

        if (isset($params['unique_id'])) {
            $request['unique_id'] = $params['unique_id'];
            $request['apiKey']    = $params['apiKey'];
            $request['identity']  = $params['identity'];
            $request['share_id']  = $params['share_id'];
            $request['question']  = $params['question'];
        }

        self::__getRequestData($request, $params);
        try {
            $record = array(
                'user_id' => $uid,
                'index_id' => $params['indexid'],
                'prompt' => $message,
                'rerank_min_score' => $params['rerank_min_score'] ?? self::RERANK_MIN_SCORE, // 默认为0
                'scene' => $params['scene'] ?? '未知聊天',
            );
            // 根据用户提示词检索
            $response = \app\common\service\ToolsService::Knowledge()->retrievePrompt($request);

            // 拼接切片内容
            if ((int)$response['code'] === 10000) {
                $texts = '';
                if (isset($params['robot_id']) && $params['robot_id'] != 0 && $params['robot_id'] != '0') {
                    $robot_set = KbRobot::where('id', $params['robot_id'])->value('roles_prompt');
                    if (!empty($robot_set)) {
                        $texts = "你的角色设定是：" . $robot_set . "\n";
                    }
                }
                if (isset($response['data']['Nodes'])) {
                    $texts .= implode("\n", array_column($response['data']['Nodes'], 'Text'));
                } else {
                    $texts .= '';
                }

                $textLength = mb_strlen($texts, 'utf-8');

                $record['retrieve_content'] = $texts;
                $record['retrieve_length'] = $textLength;
                $record['retrieve_tokens'] = ceil($textLength / 4); //2个字一个token

                $texts = str_replace('"', "'", $texts);
                $prompt = "请根据以下知识库内容回答问题：
                        {$texts}
                        问题：{$message}";

                $logs = [];
                //区分发布聊天
                if (!isset($params['unique_id'])) {
                    if (isset($params['task_id']) && $params['task_id']) {
                        $request['task_id'] = $params['task_id'];
                        // 对话记录
                        $logs = \app\api\logic\ChatLogic::chatLog($request['task_id'], $params['assistant_id'], $uid, $params['context_num']);
                        if (!$logs) {
                            message('对话记录ID错误');
                        }
                    } else {
                        $request['task_id'] = generate_unique_task_id();
                    }
                } else {
                    $ids = KbRobotRecord::where('unique_id', $params['unique_id'])
                                        ->column('id');
                    if (count($ids) > $params['context_num']) {
                        $ids = array_slice($ids, count($ids) - $params['context_num'], $params['context_num']);
                    }
                    KbRobotRecord::whereIn('id', $ids)
                                 ->order('id asc')
                                 ->select()
                                 ->each(function ($item) use (&$logs) {
                                     $logs[] = [
                                         'role'    => 'user',
                                         'content' => $item->ask
                                     ];
                                     $logs[] = [
                                         'role'    => 'assistant',
                                         'content' => $item->reply
                                     ];
                                 })
                                 ->toArray();
                    $request['task_id'] = $params['unique_id'];
                }

                if (isset($params['file_content']) && !empty($params['file_content'])) {
                    $logs[] = ['role' => 'user','content' => $params['file_content']];
                }

                if (isset($params['net_content']) && !empty($params['net_content'])) {
                    $logs[] = ['role' => 'user','content' => $params['net_content']];
                }

                $messages = array_merge($logs, [
                    ['role' => 'user','content' => $prompt]
                ]);

                if ($uid == 0 && isset($params['unique_id'])) {
                    $uid = KbRobot::where('id', $params['robot_id'])->value('user_id');
                }
                $request['user_id'] = $uid; // 替换为实际的用户ID
                $request['prompt'] = $prompt;
                $request['messages'] = $messages;
                $request['knowledge_tokens'] = ceil($textLength / 4);
                $request['chat_type'] = 9006;
                $request['now'] = time();
                $request['knowledge_record'] = $record;
                //print_r($request);die;
                if (in_array($request['model'],self::GPT_MODELS)){
                    $scene = self::OPENAI_CHAT;
                } else if (in_array($request['model'],self::GEMINI_MODELS)){
                    $scene = self::GEMINI_CHAT;
                } else {
                    $scene = self::KNOELEDGE_CHAT;
                }
                $result = self::requestUrl($request, $scene, $uid, $record);
                self::$returnData = $result;
                return $result;
            }
            return true;
        } catch (\Throwable $e) {
//            clogger($e->__toString());
            if ($params['scene'] !== 'socket') {
                message($e->getMessage());
            } else {
                self::$returnData = [];
                return false;
            }
        }
    }

    public static function commonVectorChat($params)
    {
        set_time_limit(0);
        if (!isset($params['message']) || empty($params['message'])) {
            message('提示词 不能为空');
        }
        WordsService::sensitive($params['message']);
        // 问题审核(百度)
        WordsService::askCensor($params['message']);

        try {
            $num = 0;
            $texts = '';
            $robotTexts = '';
            if (isset($params['robot_id']) && $params['robot_id'] != 0 && $params['robot_id'] != '0') {
                $robot_set = KbRobot::where('id', $params['robot_id'])->value('roles_prompt');
                if (!empty($robot_set)) {
                    $robotTexts = "你的角色设定是：" . $robot_set . "\n";
                }
            }
            if (is_array($params['kb_id']) && count($params['kb_id']) > 1) {

                $num   = 1;
                foreach ($params['kb_id'] as $kb_id) {
                    $texts .= '知识库' . $num . '：' . \app\api\logic\kb\KbKnowLogic::embAiChatSearch($kb_id, $params['message']);
                    $num++;
                }
            } else if (is_array($params['kb_id']) && count($params['kb_id']) == 1) {
                $texts = \app\api\logic\kb\KbKnowLogic::embAiChatSearch($params['kb_id'][0], $params['message']);
            } else {
                $texts = \app\api\logic\kb\KbKnowLogic::embAiChatSearch($params['kb_id'], $params['message']);
            }
            $num = $num - 1;
            if (($texts == '未找到匹配内容' || substr_count($texts, '未找到匹配内容') == $num) && isset($params['robot_id'])) {
                $robot = KbRobot::where('id', $params['robot_id'])->findOrEmpty();
                if (!$robot->isEmpty() && $robot['search_empty_type'] == 2 && $robot['search_empty_text']) {
                    $texts   = $robot['search_empty_text'];
                    $task_id = $params['task_id'] ?? uniqid('eq') . time();
                    if (isset($params['unique_id'])) {
                        // 发布聊天的task_id使用前端传过来的unique_id
                        $task_id            = $params['unique_id'];
                        $params['question'] = $params['message'];
                        $params['messages'] = [];
                    }
                    header('Content-type: text/event-stream');
                    header('Cache-Control: no-cache');
                    header('Connection: keep-alive');
                    header('X-Accel-Buffering: no');
                    $str1 = 'data:{"object":"loading","created":' . time() . ',"content":"' . $texts . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                    $str  = 'data:{"object":"finished","created":' . time() . ',"content":"' . $texts . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                    echo $str1;
                    ob_flush();
                    flush();
                    echo $str;
                    ob_flush();
                    flush();
                    //记录日志
                    ChatLogic::saveChatResponseLog($params, [
                        'reply'             => $texts ?? '',
                        'reasoning_content' => null,
                        'usage_tokens'      => 0,
                        'extra'             => [
                            'file' => [], //文件信息
                        ]
                    ]);
                    exit;
                }
            }
            $texts = str_replace('"', "'", $robotTexts . $texts);

            $textLength = mb_strlen($texts, 'utf-8');

            $record = array(
                'user_id' => self::$uid,
                'index_id' => $params['kb_id'] ?? '',
                'prompt' => $params['message'],
                'rerank_min_score' => $params['rerank_min_score'] ?? self::RERANK_MIN_SCORE, // 默认为0
                'scene' => $params['scene'] ?? '未知聊天',
            );
            $record['retrieve_content'] = $texts;
            $record['retrieve_length'] = $textLength;
            $record['retrieve_tokens'] = ceil($textLength / 4); //2个字一个token

            $prompt = "请根据以下知识库内容回答问题：
                    {$texts}
                    问题：{$params['message']}";
            $logs = [];
            if (!isset($params['unique_id'])) {
                if (isset($params['task_id']) && $params['task_id']) {
                    // 对话记录
                    $logs = \app\api\logic\ChatLogic::chatLog($params['task_id'], $params['assistant_id'], self::$uid, $params['context_num']);
                    if (!$logs) {
                        message('对话记录ID错误');
                    }
                } else {
                    $params['task_id'] = generate_unique_task_id();
                }
            } else {
                $ids = KbRobotRecord::where('unique_id', $params['unique_id'])
                                    ->column('id');
                if (count($ids) > $params['context_num']) {
                    $ids = array_slice($ids, count($ids) - $params['context_num'], $params['context_num']);
                }
                KbRobotRecord::whereIn('id', $ids)
                             ->order('id asc')
                             ->select()
                             ->each(function ($item) use (&$logs) {
                                 $logs[] = [
                                     'role'    => 'user',
                                     'content' => $item->ask
                                 ];
                                 $logs[] = [
                                     'role'    => 'assistant',
                                     'content' => $item->reply
                                 ];
                             })
                             ->toArray();
                $request['task_id'] = $params['unique_id'];
            }

            if (isset($params['file_content']) && !empty($params['file_content'])) {
                $logs[] = ['role' => 'user', 'content' => $params['file_content']];
            }

            if (isset($params['net_content']) && !empty($params['net_content'])) {
                $logs[] = [ 'role' => 'user', 'content' => $params['net_content']];
            }

            $messages = array_merge($logs, [
                ['role' => 'user','content' => $prompt]
            ]);

            $request = [
                'prompt' => $prompt,
                'messages' => $messages,
                'stream' =>  (bool)$params['stream'] ?? true,
                'robot_id' => $params['robot_id'] ?? 0,
                'task_id' => $params['task_id'] ?? generate_unique_task_id(),
                'scene' => $params['scene'] ?? '未知聊天',
                'user_id' => self::$uid,
                'knowledge_tokens' => ceil($textLength / 4),
                'chat_type' => 9006,
                'now' => time(),
                'knowledge_record' => $record,
                'model' => $params['model'] ?? 'deepseek', // 默认为默认模型
                'temperature' => $params['temperature'] ?? 1.0, //温度
                'top_p'       => $params['top_p'] ?? 0.5, //多样性范围
                'presence_penalty' => $params['presence_penalty'] ?? 0.2, //避免重复力度
                'frequency_penalty' => $params['frequency_penalty'] ?? 0.3, //避免重复用词力度
                'max_tokens' => $params['max_tokens'] ?? 4096, //token上限
                'context_num' => $params['context_num'] ?? 3, //上下文数
                'file_info' => $params['file_info'] ?? [], //文件信息
                //                'channel' => $params['channel'] ?? 0, // 应用渠道 1:个微 2:小红书 3:通用聊天
            ];
            if (isset($params['unique_id'])) {
                $request['unique_id'] = $params['unique_id'];
                $request['apiKey']    = $params['apiKey'];
                $request['identity']  = $params['identity'];
                $request['share_id']  = $params['share_id'];
                $request['question']  = $params['question'];
            }
            $uid    = self::$uid;
            if ($uid == 0 && isset($params['unique_id'])) {
                $uid = KbRobot::where('id', $params['robot_id'])->value('user_id');
            }
            if (in_array($request['model'],self::GPT_MODELS)){
                $scene = self::OPENAI_CHAT;
            } else if (in_array($request['model'],self::GEMINI_MODELS)){
                $scene = self::GEMINI_CHAT;
            } else {
                $scene = self::KNOELEDGE_CHAT;
            }
            $result = self::requestUrl($request, $scene, $uid, $record);
            self::$returnData = $result;
            return $result;
        } catch (\Throwable $e) {
            //            clogger($e);
            if ($params['scene'] !== 'socket') {
                message($e->getMessage());
            } else {
                self::$returnData = [];
                return false;
            }
        }
    }

    public static function socketChat($params)
    {
        set_time_limit(0);
        try {
            if (!isset($params['message']) || empty($params['message'])) {
                return [false, '提示词 不能为空'];
            }


            $uid = $params['user_id'] ?? self::$uid;
            if ($params['indexid'] != '') {
                $knowlwdge = Knowledge::where('index_id', $params['indexid'])->where('user_id', $uid)->fetchSql(false)->limit(1)->find();
                if (empty($knowlwdge)) {
                    return [false, '知识库不存在'];
                }
            }

            $message = $params['message'];
            // 表单变量替换
            $message_ext = $params['message_ext'] ?? '';

            if ($message_ext) {
                $message_ext_text = self::parseMsg($message_ext, '');
                $message = $message_ext_text . $message;
            }

            $request = [
                'indexid' => $params['indexid'] ?? '',
                'prompt' => $message,
                'rerank_min_score' => $params['rerank_min_score'] ?? ($knowledge['rerank_min_score'] ?? self::RERANK_MIN_SCORE), // 默认为0
                'stream' =>  (bool)$params['stream'] ?? true,
                'task_id' => $params['task_id'] ?? generate_unique_task_id(),
                'scene' => $params['scene'] ?? '未知聊天',
                'assistant_id' => $params['assistant_id'] ?? 0,
                'model' => $params['model'] ?? 'deepseek',

                'temperature' => $params['temperature'] ?? 1.0, //温度
                'top_p'       => $params['top_p'] ?? 0.5, //多样性范围
                'presence_penalty' => $params['presence_penalty'] ?? 0.2, //避免重复力度
                'frequency_penalty' => $params['frequency_penalty'] ?? 0.3, //避免重复用词力度
                'max_tokens' => $params['max_tokens'] ?? 4096, //token上限
                'context_num' => $params['context_num'] ?? 3, //上下文数
            ];

            self::__getRequestData($request, $params);

            $robot = $params['robot'] ?? null;
            $vector = '';
            $response = [];
            if ($robot != null && $robot['kb_type'] == 2) {
                $vector = \app\api\logic\kb\KbKnowLogic::embContentSearch($robot['id'], $message);
            } else {
                $record = array(
                    'user_id' => $uid,
                    'index_id' => $params['indexid'] ?? '',
                    'prompt' => $message,
                    'rerank_min_score' => $params['rerank_min_score'] ?? self::RERANK_MIN_SCORE, // 默认为0
                    'scene' => $params['scene'] ?? '未知聊天',
                );
                // 根据用户提示词检索
                $response = \app\common\service\ToolsService::Knowledge()->retrievePrompt($request);
            }
//            clogger($vector);

            // 拼接切片内容
            if ((isset($response['code']) && (int)$response['code'] === 10000) || $vector != '') {

                if (isset($response['data']['Nodes'])) {
                    $texts = implode("\n", array_column($response['data']['Nodes'], 'Text'));
                } else {
                    $texts = '';
                }
                if ($vector != '') {
                    $texts = $vector;
                }

                $textLength = mb_strlen($texts, 'utf-8');

                $record['retrieve_content'] = $texts;
                $record['retrieve_length'] = $textLength;
                $record['retrieve_tokens'] = ceil($textLength / 4); //2个字一个token

                $prompt = "请根据以下知识库内容回答问题：
                        {$texts}
                        问题：{$message}";

                $messages = $params['messages'] ?? [];
                if (isset($messages[0]['content'])) {
                    $messages[0]['content'] = str_replace(
                        ['相关知识库检索结果'],
                        [$texts],
                        $messages[0]['content']
                    );
                }

                if (empty($messages)) {
                    $messages = [
                        ['role' => 'user', 'content' => $message],
                    ];
                    //return [false, '知识库检索为空'];
                }

                $request['user_id'] = $uid; // 替换为实际的用户ID
                $request['prompt'] = $prompt;
                $request['knowledge_tokens'] = ceil($textLength / 4);
                $request['chat_type'] = 9006;
                $request['now'] = time();
                $request['knowledge_record'] = $record;
                $request['messages'] = $messages;

                if (in_array($request['model'],self::GPT_MODELS)){
                    $scene = self::OPENAI_CHAT;
                } else if (in_array($request['model'],self::GEMINI_MODELS)){
                    $scene = self::GEMINI_CHAT;
                } else {
                    $scene = self::KNOELEDGE_CHAT;
                }
                //clogger(json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                $result = self::requestUrl($request, $scene, $uid, $record, true);

                if (isset($result['code']) && (int)$result['code'] !== 10000) {
                    return [false, $result['message'] ?? '知识库检索失败'];
                }

                return [true, $result];
            } else {
                return [true, '知识库未检索到内容'];
            }
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }
    }

    public static function sceneChat($params)
    {
        set_time_limit(0);
        if (empty($params['message']) && empty($params['message_ext'])) {
            message('提示词 不能为空');
        }
        WordsService::sensitive($params['message']);
        // 问题审核(百度)
        WordsService::askCensor($params['message']);

        $assistant = \app\common\model\chat\Assistants::where('id', $params['assistant_id'])->findOrEmpty();
        if ($assistant->isEmpty()) {
            message('助手不存在');
        }

        $uid = $params['user_id'] ?? self::$uid;
        $knowlwdge = Knowledge::where('index_id', $params['indexid'])->where('user_id', $uid)->fetchSql(false)->limit(1)->find();
        if (empty($knowlwdge)) {
            message('知识库不存在');
        }

        $message = $params['message'];

        $request = [
            'indexid' => $params['indexid'],
            'prompt' => $message,
            'rerank_min_score' => $params['rerank_min_score'] ?? ($knowlwdge['rerank_min_score'] ?? self::RERANK_MIN_SCORE), // 默认为0
            'stream' =>  (bool)$params['stream'] ?? true,
            'task_id' => $params['task_id'] ?? generate_unique_task_id(),
            'scene' => $params['scene'] ?? '未知聊天',
            'assistant_id' => $params['assistant_id'] ?? 0
        ];
        //clogger(json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        if (mb_strlen(mb_trim($message), 'utf-8') == 0) {
            message('提示词 不能为空');
        }

        if ($params['scene'] == '陪练聊天') {
            $request['voice'] = $params['voice'] ?? '';
            $request['emotion'] =  $params['emotion'] ?? '';
            $request['intensity'] =  $params['intensity'] ?? '';
        }

        self::__getRequestData($request, $params);

        try {
            $record = array(
                'user_id' => $uid,
                'index_id' => $params['indexid'],
                'prompt' => $message,
                'rerank_min_score' => $request['rerank_min_score'], // 默认为0
                'scene' => $params['scene'] ?? '未知聊天',
            );

            // 根据用户提示词检索
            $response = \app\common\service\ToolsService::Knowledge()->retrievePrompt($request);

            // 拼接切片内容
            if ((int)$response['code'] === 10000) {
                if (isset($response['data']['Nodes'])) {
                    $texts = implode("\n", array_column($response['data']['Nodes'], 'Text'));
                } else {
                    $texts = '';
                }
                //clogger($texts);
                // 表单变量替换
                $message_ext = $params['message_ext'] ?? '';
                if ($message_ext) {
                    $message_ext_text = self::parseMsg($message_ext, $assistant['form_info']); // $assistant['form_info']
                    $texts = $message_ext_text . $texts;
                }
                $textLength = mb_strlen($texts, 'utf-8');

                $record['retrieve_content'] = $texts;
                $record['retrieve_length'] = $textLength;
                $record['retrieve_tokens'] = ceil($textLength / 4); //2个字一个token

                $prompt = "请根据以下知识库内容回答问题：
                        {$texts}
                        问题：{$message}";

                $request['user_id'] = $uid; // 替换为实际的用户ID
                $request['prompt'] = $prompt;
                $request['knowledge_tokens'] = ceil($textLength / 4);
                $request['chat_type'] = 9006;
                $request['now'] = time();
                $request['knowledge_record'] = $record;

                //clogger(json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

                $result = self::requestUrl($request, self::KNOELEDGE_CHAT, $uid, $record);
                self::$returnData = $result;
                return $result;
            }
            return true;
        } catch (\Throwable $e) {

            message($e->getMessage());
        }
    }

    public static function sceneVectorChat($params)
    {
        set_time_limit(0);
        if (empty($params['message']) && empty($params['message_ext'])) {
            message('提示词 不能为空');
        }
        WordsService::sensitive($params['message']);
        // 问题审核(百度)
        WordsService::askCensor($params['message']);

        $assistant = \app\common\model\chat\Assistants::where('id', $params['assistant_id'])->findOrEmpty();
        if ($assistant->isEmpty()) {
            message('助手不存在');
        }

        $uid = $params['user_id'] ?? self::$uid;


        $message = $params['message'];


        //clogger(json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        if (mb_strlen(mb_trim($message), 'utf-8') == 0) {
            message('提示词 不能为空');
        }

        try {
            $record = array(
                'user_id' => $uid,
                'index_id' => $params['kb_id'] ?? '',
                'prompt' => $message,
                'rerank_min_score' => 0, // 默认为0
                'scene' => $params['scene'] ?? '未知聊天',
            );
            $request = [
                'prompt' => $message,
                'stream' =>  (bool)$params['stream'] ?? true,
                'task_id' => $params['task_id'] ?? generate_unique_task_id(),
                'scene' => $params['scene'] ?? '未知聊天',
                'assistant_id' => $params['assistant_id'] ?? 0
            ];
            $texts =  \app\api\logic\kb\KbKnowLogic::embAiChatSearch($params['kb_id'], $params['message']);
            //clogger($texts);
            // 表单变量替换
            $message_ext = $params['message_ext'] ?? '';
            if ($message_ext) {
                $message_ext_text = self::parseMsg($message_ext, $assistant['form_info']); // $assistant['form_info']
                $texts = $message_ext_text . $texts;
            }
            $textLength = mb_strlen($texts, 'utf-8');

            $record['retrieve_content'] = $texts;
            $record['retrieve_length'] = $textLength;
            $record['retrieve_tokens'] = ceil($textLength / 4); //2个字一个token

            $prompt = "请根据以下知识库内容回答问题：
                    {$texts}
                    问题：{$message}";

            $request['user_id'] = $uid; // 替换为实际的用户ID
            $request['prompt'] = $prompt;
            $request['knowledge_tokens'] = ceil($textLength / 4);
            $request['chat_type'] = 9006;
            $request['now'] = time();
            $request['knowledge_record'] = $record;

            $result = self::requestUrl($request, self::KNOELEDGE_CHAT, $uid, $record);
            self::$returnData = $result;
            return $result;

            return true;
        } catch (\Throwable $e) {

            message($e->getMessage());
        }
    }



    public static function ladderPlayerChat($params)
    {
        set_time_limit(0);
        if (empty($params['message']) && empty($params['message_ext'])) {
            message('提示词 不能为空');
        }


        $uid = $params['user_id'] ?? self::$uid;
        $knowlwdge = Knowledge::where('index_id', $params['indexid'])->where('user_id', $uid)->fetchSql(false)->limit(1)->find();
        if (empty($knowlwdge)) {
            message('知识库不存在');
        }

        $message = $params['message'];

        $request = [
            'indexid' => $params['indexid'],
            'prompt' => $message,
            'rerank_min_score' => $params['rerank_min_score'] ?? ($knowlwdge['rerank_min_score'] ?? self::RERANK_MIN_SCORE), // 默认为0
            'stream' =>  (bool)$params['stream'] ?? true,
            'task_id' => $params['task_id'] ?? generate_unique_task_id(),
            'scene' => $params['scene'] ?? '未知聊天',
            'assistant_id' => $params['assistant_id'] ?? 0
        ];
        $request['rerank_min_score'] = self::RERANK_MIN_SCORE;

        $request['voice'] = $params['voice'] ?? '';
        $request['emotion'] =  $params['emotion'] ?? '';
        $request['intensity'] =  $params['intensity'] ?? '';

        self::__getRequestData($request, $params);

        try {
            $record = array(
                'user_id' => $uid,
                'index_id' => $params['indexid'],
                'prompt' => $message,
                'rerank_min_score' => $request['rerank_min_score'], // 默认为0
                'scene' => $params['scene'] ?? '未知聊天',
            );
            //clogger(json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'll');
            // 根据用户提示词检索
            $response = \app\common\service\ToolsService::Knowledge()->retrievePrompt($request);
            //clogger(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'll');
            // 拼接切片内容
            if ((int)$response['code'] === 10000) {
                if (isset($response['data']['Nodes'])) {
                    $texts = implode("\n", array_column($response['data']['Nodes'], 'Text'));
                } else {
                    $texts = '';
                }

                $textLength = mb_strlen($texts, 'utf-8');

                $record['retrieve_content'] = $texts;
                $record['retrieve_length'] = $textLength;
                $record['retrieve_tokens'] = ceil($textLength / 4); //2个字一个token

                $prompt = "请根据以下知识库内容回答问题：
                        {$texts}
                        问题：{$message}";

                $request['user_id'] = $uid; // 替换为实际的用户ID
                $request['prompt'] = $prompt;
                $request['knowledge_tokens'] = ceil($textLength / 4);
                $request['chat_type'] = 9006;
                $request['now'] = time();
                $request['knowledge_record'] = $record;

                $result = self::requestUrl($request, self::KNOELEDGE_CHAT, $uid, $record);
                self::$returnData = $result;
                return $result;
            }
            return true;
        } catch (\Throwable $e) {

            message($e->getMessage());
        }
    }

    public static function ladderPlayerVecTorChat($params)
    {
        set_time_limit(0);
        if (empty($params['message']) && empty($params['message_ext'])) {
            message('提示词 不能为空');
        }

        $uid = $params['user_id'] ?? self::$uid;

        $message = $params['message'];

        $request = [
            'prompt' => $message,
            'stream' =>  (bool)$params['stream'] ?? true,
            'task_id' => $params['task_id'] ?? generate_unique_task_id(),
            'scene' => $params['scene'] ?? '未知聊天',
            'assistant_id' => $params['assistant_id'] ?? 0
        ];
        $request['rerank_min_score'] = self::RERANK_MIN_SCORE;

        $request['voice'] = $params['voice'] ?? '';
        $request['emotion'] =  $params['emotion'] ?? '';
        $request['intensity'] =  $params['intensity'] ?? '';


        try {
            $texts =  \app\api\logic\kb\KbKnowLogic::embAiChatSearch($params['kb_id'], $params['message']);
            $record = array(
                'user_id' => $uid,
                'index_id' => $params['kb_id'],
                'prompt' => $message,
                'rerank_min_score' => 0, // 默认为0
                'scene' => $params['scene'] ?? '未知聊天',
            );
            $textLength = mb_strlen($texts, 'utf-8');

            $record['retrieve_content'] = $texts;
            $record['retrieve_length'] = $textLength;
            $record['retrieve_tokens'] = ceil($textLength / 4); //2个字一个token

            $prompt = "请根据以下知识库内容回答问题：
                    {$texts}
                    问题：{$message}";

            $request['user_id'] = $uid; // 替换为实际的用户ID
            $request['prompt'] = $prompt;
            $request['knowledge_tokens'] = ceil($textLength / 4);
            $request['chat_type'] = 9006;
            $request['now'] = time();
            $request['knowledge_record'] = $record;

            $result = self::requestUrl($request, self::KNOELEDGE_CHAT, $uid, $record);
            self::$returnData = $result;
            return $result;
        } catch (\Throwable $e) {

            message($e->getMessage());
        }
    }

    private static function parseMsg($message_ext, $form_info)
    {
        $message_ext = json_decode($message_ext, true);
        if (empty($message_ext)) {
            return '';
        }
        preg_match_all('/\${([^\}]+)}/u', $form_info, $matches);
        $keys = $matches[1];
        if (empty($keys)) {
            return '';
        }
        foreach ($message_ext as $key => $value) {
            foreach ($keys as $keyword) {
                if ($keyword == $key) {
                    if (!empty($value) && is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $form_info = str_replace('${' . $keyword . '}', $value, $form_info);
                }
            }
        }
        return $form_info;
    }


    private static function __getRequestData(array &$request, array $params)
    {
        if (isset($params['rerankTopN']) && !empty($params['rerankTopN'])) {
            $request['rerankTopN'] = $params['rerankTopN'];
        }

        if (isset($params['denseSimilarityTopK']) && !empty($params['denseSimilarityTopK'])) {
            $request['denseSimilarityTopK'] = $params['denseSimilarityTopK'];
        }

        if (isset($params['enableReranking']) && !empty($params['enableReranking'])) {
            $request['enableReranking'] = $params['enableReranking'];
        }
        if (isset($params['enableRewrite']) && !empty($params['enableRewrite'])) {
            $request['enableRewrite'] = $params['enableRewrite'];
        }

        if (isset($params['sparseSimilarityTopK']) && !empty($params['sparseSimilarityTopK'])) {
            $request['sparseSimilarityTopK'] = $params['sparseSimilarityTopK'];
        }
        if (isset($params['saveRetrieverHistory']) && !empty($params['saveRetrieverHistory'])) {
            $request['saveRetrieverHistory'] = $params['saveRetrieverHistory'];
        }
    }
}
