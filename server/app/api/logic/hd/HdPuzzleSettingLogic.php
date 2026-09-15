<?php

namespace app\api\logic\hd;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\model\hd\HdPuzzle;
use app\common\model\hd\HdPuzzleSetting;
use think\facade\Db;

/**
 * 拼图设置逻辑
 */
class HdPuzzleSettingLogic extends ApiLogic
{
    /**
     * 新增拼图设置
     * @param array $params
     * @return bool
     */
    public static function addPuzzleSetting(array $params)
    {
        try {
            $params['user_id'] = self::$uid;
            $jsonFields = ['copywriting', 'material', 'extra'];
            $decodedData = [];

            // 解析JSON数据
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            // 获取素材和文案数据
            $materialArr = $decodedData['material'] ?? [];
            $copyArr = $decodedData['copywriting'] ?? [];
            $materialCount = count($materialArr);
            $copyCount = count($copyArr);

            // 计算生成数量
            $result_count = isset($params['result_count']) ? (int)$params['result_count'] : 0;
            if ($result_count < 2) {
                self::setError('生成数量必须大于2');
                return false;
            }

            if ($materialCount < 2) {
                self::setError('素材数量必须大于等于2');
                return false;
            }
            // 计算各素材数量的组合数和cycle_num
            $combinations = self::calculateCombinations($materialCount);
            // var_dump($combinations);
            $materialLimit = $combinations['puzzle_num_total'];
            $resultMaxCount = $materialLimit * $copyCount;

            if ($resultMaxCount < $result_count) {
                self::setError("生成数量不足，当前素材数量为{$materialCount}，文案数量为{$copyCount}，实际最多可生成{$resultMaxCount}张，达不到要求{$result_count}张，");
                return false;
            }
            Db::startTrans();
            try {
                $params['task_id'] = $params['task_id'] ?? generate_unique_task_id();
                $setting = HdPuzzleSetting::create($params);

                // 只有当状态为1且需要生成任务时才创建任务
                // 生成任务
                $tasks = [];
                $usedCombinations = [];
                $puzzleKeyArray = []; // 存储已使用的puzzleKey，避免重复
                $maxAttempts = 200; // 增加最大尝试次数
                $attempt = 0;

                // 获取可用的模板类型
                $availableTypes = self::getAvailableTemplateTypes($materialCount);
                // 创建cycle_num映射表
                $cycleNums = [
                    9 => $combinations['cycle_num9'],
                    6 => $combinations['cycle_num6'],
                    5 => $combinations['cycle_num5'],
                    4 => $combinations['cycle_num4'],
                    3 => $combinations['cycle_num3'],
                    2 => $combinations['cycle_num2']
                ];

                // var_dump($cycleNums);
                // 模板类型与素材数量的对应关系
                $typeMaterialMap = [
                    5 => 9,
                    4 => 6,
                    3 => 5,
                    2 => 4,
                    1 => 3 // type=1默认使用3张素材
                ];

                $typePriority = [1, 2, 3, 4, 5];
                $puzzle_num = 0;
                $attempt = 0;
                $maxAttempts = 200;

                // 第一步：优先遍历所有type，生成素材组合
                $materialCombinations = [];
                $typeIndex = 0;
                
                while (count($materialCombinations) < $result_count && $attempt < $maxAttempts) {
                    $currentType = $typePriority[$typeIndex];
                    $taskGenerated = false;

                    // 确定当前类型对应的素材数量
                    $materialSize = $typeMaterialMap[$currentType];
                    $materialSizeForType1 = null;
                    
                    // 检查当前类型是否可用
                    if (!in_array($currentType, $availableTypes)) {
                        $typeIndex = ($typeIndex + 1) % count($typePriority);
                        continue;
                    }
                    
                    $attempt++;

                    // 对于type=1，先尝试3个元素的组合，再尝试2个元素的组合
                    if ($currentType == 1) {
                        // 先检查3个元素的组合是否还有剩余
                        if ($cycleNums[3] > 0) {
                            $materialSize = 3;
                        } elseif ($cycleNums[2] > 0) {
                            // 3个元素的组合用完了，尝试2个元素的组合
                            $materialSize = 2;
                        } else {
                            // 所有组合都用完了，切换到下一个类型
                            $typeIndex = ($typeIndex + 1) % count($typePriority);
                            continue;
                        }
                        $materialSizeForType1 = $materialSize;
                    } else {
                        // 检查当前类型的素材数量是否可用
                        if (!isset($cycleNums[$materialSize]) || $cycleNums[$materialSize] <= 0) {
                            $typeIndex = ($typeIndex + 1) % count($typePriority);
                            continue;
                        }
                    }

                    // 确定当前类型需要的素材数量
                    $neededMaterialCount = $materialSize;

                    // 尝试生成唯一组合
                    $selectedMaterial = [];
                    $maxMaterialAttempts = 50;
                    $materialAttempt = 0;

                    // 生成所有可能的排列（如果素材数量较少）
                    $allPossiblePermutations = [];
                    if ($materialCount <= 10) {
                        $allPossiblePermutations = self::getAllPermutations($materialArr, $neededMaterialCount);
                        shuffle($allPossiblePermutations);
                    }

                    // 尝试生成唯一组合
                    while (empty($selectedMaterial) && $materialAttempt < $maxMaterialAttempts) {
                        $materialAttempt++;

                        // 如果有预生成的排列，使用它们
                        if (!empty($allPossiblePermutations)) {
                            $selectedMaterial = array_pop($allPossiblePermutations);
                        } else {
                            // 随机选择素材并打乱顺序（排列考虑顺序）
                            $shuffledMaterial = $materialArr;
                            $selectedMaterial = array_slice($shuffledMaterial, 0, $neededMaterialCount);
                        }

                        // 检查组合是否重复（只检查素材组合）
                        $sortedSelected = $selectedMaterial;
                        sort($sortedSelected);
                        $materialCombinationStr = serialize($sortedSelected);

                        // 检查该素材组合是否已被使用
                        $materialUsed = false;
                        foreach ($materialCombinations as $existingComb) {
                            if (serialize($existingComb['material']) == $materialCombinationStr) {
                                $materialUsed = true;
                                break;
                            }
                        }

                        if ($materialUsed) {
                            $selectedMaterial = [];
                        }
                    }

                    // 如果3个元素的组合尝试失败，且type=1，尝试2个元素的组合
                    if ($currentType == 1 && empty($selectedMaterial) && $neededMaterialCount == 3 && $cycleNums[2] > 0) {
                        $neededMaterialCount = 2;
                        $materialAttempt = 0;

                        // 生成所有可能的2元素排列（如果素材数量较少）
                        $allPossible2Permutations = [];
                        if ($materialCount <= 10) {
                            $allPossible2Permutations = self::getAllPermutations($materialArr, $neededMaterialCount);
                        }

                        while (empty($selectedMaterial) && $materialAttempt < $maxMaterialAttempts) {
                            $materialAttempt++;

                            // 如果有预生成的排列，使用它们
                            if (!empty($allPossible2Permutations)) {
                                $selectedMaterial = array_pop($allPossible2Permutations);
                            } else {
                                // 随机选择素材并打乱顺序（排列考虑顺序）
                                $shuffledMaterial = $materialArr;
                                $selectedMaterial = array_slice($shuffledMaterial, 0, $neededMaterialCount);
                            }

                            // 检查组合是否重复（只检查素材组合）
                            $sortedSelected = $selectedMaterial;
                            sort($sortedSelected);
                            $materialCombinationStr = serialize($sortedSelected);

                            // 检查该素材组合是否已被使用
                            $materialUsed = false;
                            foreach ($materialCombinations as $existingComb) {
                                if (serialize($existingComb['material']) == $materialCombinationStr) {
                                    $materialUsed = true;
                                    break;
                                }
                            }

                            if ($materialUsed) {
                                $selectedMaterial = [];
                            }
                        }

                        if (!empty($selectedMaterial)) {
                            $materialSize = 2;
                            $materialSizeForType1 = 2;
                        }
                    }

                    // 如果找到合适的组合，记录下来
                    if (!empty($selectedMaterial)) {
                        // 生成素材下标与文案下标的组合字符串
                        $materialIndices = [];
                        foreach ($selectedMaterial as $materialItem) {
                            $index = array_search($materialItem, $materialArr);
                            if ($index !== false) {
                                $materialIndices[] = $index;
                            }
                        }
                        $puzzleKey = 'puzzle_' . implode('_', $materialIndices);

                        // 检查puzzleKey是否已存在，如果存在则跳过
                        if (in_array($puzzleKey, $puzzleKeyArray)) {
                            // 重置选中的素材，继续下一次尝试
                            $selectedMaterial = [];
                            continue;
                        }

                        // 将新的素材组合添加到数组中
                        $materialCombinations[] = [
                            'type' => $currentType,
                            'material' => $selectedMaterial,
                            'puzzleKey' => $puzzleKey,
                            'materialSize' => $materialSize
                        ];

                        // 将新的puzzleKey添加到数组中
                        $puzzleKeyArray[] = $puzzleKey;

                        // 减少对应的cycle_num
                        $cycleNums[$materialSize]--;
                        $taskGenerated = true;

                        // 切换到下一个类型
                        $typeIndex = ($typeIndex + 1) % count($typePriority);
                    } else {
                        // 生成失败，切换到下一个类型
                        $typeIndex = ($typeIndex + 1) % count($typePriority);
                    }
                }

                // 第二步：按用户要求的顺序生成任务：title1对应type1-5，title2对应type1-5，以此类推
                $usedCombinations = [];
                
                // 先将素材组合按type分组
                $materialCombinationsByType = [];
                foreach ($materialCombinations as $materialComb) {
                    $type = $materialComb['type'];
                    if (!isset($materialCombinationsByType[$type])) {
                        $materialCombinationsByType[$type] = [];
                    }
                    $materialCombinationsByType[$type][] = $materialComb;
                }
                
                // 遍历title数组（外层循环）
                foreach ($copyArr as $copyIndex => $copyItem) {
                    // 遍历type1-5（内层循环）
                    foreach ($typePriority as $currentType) {
                        if (!isset($materialCombinationsByType[$currentType]) || empty($materialCombinationsByType[$currentType])) {
                            continue;
                        }
                        
                        // 获取当前type的第一个素材组合
                        $materialComb = array_shift($materialCombinationsByType[$currentType]);
                        $selectedMaterial = $materialComb['material'];
                        $puzzleKey = $materialComb['puzzleKey'];
                        $materialSize = $materialComb['materialSize'];
                        
                        // 计算生成的拼图数量
                        $selectedMaterialNum = count($selectedMaterial);
                        if ($selectedMaterialNum > 2) {
                            $puzzle_num = $puzzle_num + 4;
                        } else {
                            $puzzle_num = $puzzle_num + 2;
                        }
                        
                        // 生成任务
                        $tasks[] = [
                            'user_id' => self::$uid,
                            'puzzle_setting_id' => $setting->id,
                            'name' => ($params['name'] ?? '拼图') . '_' . (count($tasks) + 1),
                            'task_id' => generate_unique_task_id(),
                            'status' => 0,
                            'type' => $currentType,
                            'title' => json_encode($copyItem['title'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'material' => json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'extra' => json_encode(['key' => $puzzleKey], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'create_time' => time(),
                            'update_time' => time()
                        ];
                        
                        // 记录已使用的组合（素材+文案）
                        $usedCombinations[] = [
                            'material' => serialize(array_values($selectedMaterial)),
                            'copy' => serialize($copyItem)
                        ];
                        
                        // 如果已经生成了足够的任务，跳出循环
                        if ($puzzle_num >= $result_count) {
                            break 2; // 跳出两层循环
                        }
                    }
                }

                if (!empty($tasks)) {
                    TokenLogService::checkToken(self::$uid, 'combined_picture', $puzzle_num);

                    (new HdPuzzle())->saveAll($tasks);
                    $id = $setting->id;
                    $tasksnum = count($tasks);
                    HdPuzzleSetting::where('id', $id)->update(['task_count' => $tasksnum]);
                }

                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 计算最终生成数量
     * @param int $materialCount 素材数量
     * @param int $copyCount 文案数量
     * @param int $userResultCount 用户自定义数量
     * @return int 最终生成数量
     */
    private static function calculateResultCount($materialCount, $copyCount, $userResultCount)
    {
        // 获取素材上限
        $materialLimit = self::getMaterialLimit($materialCount);
        // 计算文案上限
        $copyLimit = $copyCount * 4;
        // 基础生成数量为素材上限和文案上限的最小值
        $baseResultCount = min($materialLimit, $copyLimit);

        // 如果用户指定了数量，使用用户指定的数量（允许超过上限，系统会自动多流循环生成）
        if ($userResultCount > 0) {
            $baseResultCount = $userResultCount;
        }

        return $baseResultCount;
    }

    /**
     * 根据素材数量获取单次可生成上限
     * @param int $materialCount 素材数量
     * @return int 单次可生成上限
     */
    private static function getMaterialLimit($materialCount)
    {
        // 计算所有可能的组合数
        $combinations = self::calculateCombinations($materialCount);
        return $combinations['puzzle_num_total'];
    }

    // 计算排列数的函数
    private static function permutation($n, $k)
    {
        if ($k > $n) {
            return 0;
        }
        // 计算排列数 P(n,k) = n!/(n-k)!
        $result = 1;

        // 直接计算 n * (n-1) * (n-2) * ... * (n-k+1)
        for ($i = 0; $i < $k; $i++) {
            $result *= ($n - $i);
        }

        return (int)$result;
    }

    /**
     * 生成所有可能的排列
     * @param array $arr 原始数组
     * @param int $size 排列大小
     * @return array
     */
    public static function getAllPermutations($arr, $size)
    {
        $result = [];

        // 如果排列大小为0或大于数组大小，返回空数组
        if ($size == 0 || $size > count($arr)) {
            return $result;
        }

        // 如果排列大小等于数组大小，返回整个数组的所有排列
        if ($size == count($arr)) {
            $perms = [];
            self::generatePermutations($arr, 0, $perms);
            return $perms;
        }

        // 如果排列大小为1，返回每个元素的数组
        if ($size == 1) {
            foreach ($arr as $element) {
                $result[] = [$element];
            }
            return $result;
        }

        // 递归生成排列
        foreach ($arr as $key => $element) {
            // 从原始数组中移除当前元素，生成剩余元素的排列
            $remainingArr = $arr;
            unset($remainingArr[$key]);
            $remainingArr = array_values($remainingArr);

            // 生成剩余元素的排列
            $subPermutations = self::getAllPermutations($remainingArr, $size - 1);

            // 将当前元素添加到每个子排列的开头
            foreach ($subPermutations as $subPermutation) {
                array_unshift($subPermutation, $element);
                $result[] = $subPermutation;
            }
        }

        return $result;
    }

    /**
     * 辅助函数：生成数组的所有全排列
     * @param array $arr 原始数组
     * @param int $start 开始索引
     * @param array &$result 结果数组
     */
    private static function generatePermutations(&$arr, $start, &$result)
    {
        $count = count($arr);

        if ($start == $count - 1) {
            $result[] = $arr;
            return;
        }

        for ($i = $start; $i < $count; $i++) {
            // 交换元素
            self::swap($arr, $start, $i);

            // 递归生成排列
            self::generatePermutations($arr, $start + 1, $result);

            // 恢复数组
            self::swap($arr, $start, $i);
        }
    }

    /**
     * 辅助函数：交换数组中的两个元素
     * @param array &$arr 数组
     * @param int $i 第一个索引
     * @param int $j 第二个索引
     */
    private static function swap(&$arr, $i, $j)
    {
        $temp = $arr[$i];
        $arr[$i] = $arr[$j];
        $arr[$j] = $temp;
    }

    // 主函数，计算给定数字数组的组合数
    private static function calculateCombinations($totalNumbers)
    {
        $groupSizes = [2, 3, 4, 5, 6, 9];
        $result = [];
        $cycleNums = [];
        $puzzleNums = [];

        foreach ($groupSizes as $size) {
            $cycleNum = self::permutation($totalNumbers, $size);
            $cycleNums[] = $cycleNum;
            $result["cycle_num$size"] = $cycleNum;

            // 根据素材数量确定每个组合可以生成的拼图数量
            // 对于type=1（3张或2张图），统一使用4个变体
            if ($size == 2 && $totalNumbers < 3) {
                // 只有2张素材时，type=1只能使用2张，生成2个变体
                $puzzleNum = $cycleNum * 2;
            } else {
                // 其他情况（包括type=1使用3张或2张素材）生成4个变体
                $puzzleNum = $cycleNum * 4;
            }

            $puzzleNums[] = $puzzleNum;
            $result["puzzle_num$size"] = $puzzleNum;
        }

        // 计算type=1的总组合数（包括3个元素和2个元素的情况）
        $type1Combinations = ($result['cycle_num3'] ?? 0) + ($result['cycle_num2'] ?? 0);
        $result['type1_combinations'] = $type1Combinations;

        // 计算type=1的总拼图数量
        $type1Puzzles = (($result['puzzle_num3'] ?? 0) + ($result['puzzle_num2'] ?? 0));
        $result['type1_puzzles'] = $type1Puzzles;

        $result['cycle_num_total'] = array_sum($cycleNums);
        $result['puzzle_num_total'] = array_sum($puzzleNums);
        return $result;
    }

    /**
     * 根据素材数量选择合适的模板类型
     * @param int $materialCount 素材数量
     * @return int 模板类型
     */
    private static function getTemplateType($materialCount)
    {
        // 优先选择9张图，然后是6张图，5张图，4张图，3张图，2张图
        if ($materialCount >= 9) {
            return 5; // 9张图
        } elseif ($materialCount >= 6) {
            return 4; // 6张图
        } elseif ($materialCount >= 5) {
            return 3; // 5张图
        } elseif ($materialCount >= 4) {
            return 2; // 4张图
        } else {
            return 1; // 2-3张图
        }
    }

    /**
     * 根据素材数量获取所有可用的模板类型
     * @param int $materialCount 素材数量
     * @return array 可用的模板类型数组
     */
    private static function getAvailableTemplateTypes($materialCount)
    {
        $availableTypes = [];

        // 根据素材数量确定可用的模板类型
        if ($materialCount >= 9) {
            $availableTypes = [5, 4, 3, 2, 1];
        } elseif ($materialCount >= 6) {
            $availableTypes = [4, 3, 2, 1];
        } elseif ($materialCount >= 5) {
            $availableTypes = [3, 2, 1];
        } elseif ($materialCount >= 4) {
            $availableTypes = [2, 1];
        } else {
            $availableTypes = [1];
        }

        return $availableTypes;
    }

    /**
     * 根据模板类型获取需要的素材数量
     * @param int $type 模板类型
     * @param int $actualMaterialCount 实际可用素材数量
     * @return int 需要的素材数量（不超过实际可用数量）
     */
    private static function getNeededMaterialCount($type, $actualMaterialCount)
    {
        $baseCount = 0;

        switch ($type) {
            case 5: // 9张图
                $baseCount = 9;
                break;
            case 4: // 6张图
                $baseCount = 6;
                break;
            case 3: // 5张图
                $baseCount = 5;
                break;
            case 2: // 4张图
                $baseCount = 4;
                break;
            case 1: // 2-3张图，优先3张
                if ($actualMaterialCount >= 3) {
                    $baseCount = 3;
                } else {
                    $baseCount = 2;
                }
                break;
        }

        // 返回不超过实际可用素材数量的值
        return min($baseCount, $actualMaterialCount);
    }

    /**
     * 获取详情
     * @param array $params
     * @return bool
     */
    public static function detailPuzzleSetting(array $params)
    {
        try {
            $setting = HdPuzzleSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$setting) {
                self::setError('拼图设置不存在');
                return false;
            }

            foreach (['copywriting', 'material', 'extra'] as $field) {
                $setting[$field] = !empty($setting[$field]) ? json_decode($setting[$field], true) : [];
            }

            self::$returnData = $setting;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新拼图设置
     * @param array $params
     * @return bool
     */
    public static function updatePuzzleSetting(array $params)
    {
        try {
            $setting = HdPuzzleSetting::where('id', $params['id'])->where('user_id', self::$uid)->find();
            if (!$setting) {
                self::setError('拼图设置不存在');
                return false;
            }

            $status = isset($params['status']) ? (int)$params['status'] : $setting->status;
            if (!in_array($status, [0, 1, 2, 3, 4, 5], true)) {
                self::setError('状态值非法');
                return false;
            }

            $jsonFields = ['copywriting', 'material', 'extra'];
            foreach ($jsonFields as $field) {
                if (array_key_exists($field, $params)) {
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                }
            }

            $resultCount = isset($params['result_count']) ? (int)$params['result_count'] : null;

            Db::startTrans();
            try {
                $setting->save($params);

                // 如果仍然需要生成并且之前没有任务，补充任务
                if ($status === 1 && HdPuzzle::where('puzzle_setting_id', $setting->id)->count() === 0) {
                    $copyArr = !empty($params['copywriting']) ? json_decode($params['copywriting'], true) : (json_decode($setting['copywriting'], true) ?: []);
                    $materialArr = !empty($params['material']) ? json_decode($params['material'], true) : (json_decode($setting['material'], true) ?: []);
                    $copyCount = count($copyArr);
                    $materialCount = count($materialArr);
                    $resultTotal = $resultCount ?? $setting->result_count;

                    $tasks = [];
                    for ($i = 0; $i < $resultTotal; $i++) {
                        $materialItem = $materialArr[$i % max(1, $materialCount)] ?? [];
                        $titleItem = $copyArr[$i % max(1, $copyCount)] ?? [];
                        $tasks[] = [
                            'user_id' => self::$uid,
                            'puzzle_setting_id' => $setting->id,
                            'name' => ($setting['name'] ?? '拼图') . '_' . ($i + 1),
                            'task_id' => generate_unique_task_id(),
                            'status' => 0,
                            'type' => isset($materialItem['type']) ? (int)$materialItem['type'] : (int)($params['type'] ?? 1),
                            'title' => json_encode($titleItem, JSON_UNESCAPED_UNICODE),
                            'material' => json_encode($materialItem, JSON_UNESCAPED_UNICODE),
                            'extra' => json_encode([], JSON_UNESCAPED_UNICODE),
                            'create_time' => time(),
                            'update_time' => time()
                        ];
                    }

                    if (!empty($tasks)) {
                        (new HdPuzzle())->saveAll($tasks);
                    }
                }

                Db::commit();
                self::$returnData = $setting->refresh()->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除拼图设置及其任务
     * @param array $params
     * @return bool
     */
    public static function deletePuzzleSetting($id)
    {
        try {
            Db::startTrans();
            try {
                if (is_string($id)) {
                    HdPuzzle::where('puzzle_setting_id', $id)->select()->delete();
                    HdPuzzleSetting::destroy($id);
                } else {
                    HdPuzzle::whereIn('puzzle_setting_id', $id)->select()->delete();
                    HdPuzzleSetting::whereIn('id', $id)->select()->delete();
                }
                Db::commit();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
