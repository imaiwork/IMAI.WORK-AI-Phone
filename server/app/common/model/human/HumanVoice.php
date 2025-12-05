<?php


namespace app\common\model\human;

use app\common\model\BaseModel;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * HumanVoice
 * @desc 数字人音色
 * @author dagouzi
 */
class HumanVoice extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public static function getStatus($status)
    {
        // initialized 初始化；sent 发送给算法；pending 算法排队； processing 算法开始处理； completed 成功； failed 失败；
        $data = [
            0 => '初始化',
            1 => '成功',
            2 => '失败',
            3 => '发送给算法',
            4 => '算法排队',
            5 => '算法开始处理'
        ];
        return $data[$status] ?? '未知';
    }

    public static function transferStatus($status)
    {
        $data = [
            'initialized' => 0,
            'sent' => 3,
            'pending' => 4,
            'processing' => 5,
            'completed' => 1,
            'failed' => 2
        ];
        return $data[$status] ?? 2;
    }

    public static function getBuiltInVoice($code,$model){
        $data = [
            '1' => [
                '10000'=>'39067',
                '10001'=>'39068',
                '10002'=>'39069',
                '10003'=>'39070',
                '10004'=>'39071',
                '10005'=>'39072',
                '10006'=>'39073',
                '10007'=>'39074',
                '10008'=>'39075',
                '10009'=>'39077',
            ],
            '2' => [
                '10000'=>'085e249c475e41efb518888d7bdbaca8',
                '10001'=>'9564332b7ab849949a9f214c5fd8ca8e',
                '10002'=>'55cf399b34aa43e8943a2217603a221e',
                '10003'=>'220f88345f84483bb42372ad13482b09',
                '10004'=>'90f9ed6823644e92958c700df0c05974',
                '10005'=>'fe58827d36af4d02b9a07616bca747ef',
                '10006'=>'75ba5f6c40ad42fb93d9bc4187e4ab28',
                '10007'=>'1ba7c4ad12fc4286b4e5190607063a1e',
                '10008'=>'ea8fc1cd3a754f12a1ad91c4f3909754',
                '10009'=>'e10bb9e089be4427960ab65b3c6daeee',
            ],
            '4' => [
                '10000'=>'197948',//智小敏(女)
                '10001'=>'197954',//智小柔(女)
                '10002'=>'197955',//智小满(女)
                '10003'=>'197960',//爱小芊(女)
                '10004'=>'197963',//爱小静(女)
                '10005'=>'197966',//千嶂(男)
                '10006'=>'197969',//智皓(男)
                '10007'=>'197973',//爱小杭(男)
                '10008'=>'197979',//爱小辰(男)
                '10009'=>'197983',//飞镜(男)
            ],
            '6' => [
                '10000'=>'197950',//智小敏(女)
                '10001'=>'197953',//智小柔(女)
                '10002'=>'197956',//智小满(女)
                '10003'=>'197961',//爱小芊(女)
                '10004'=>'197964',//爱小静(女)
                '10005'=>'197965',//千嶂(男)
                '10006'=>'197968',//智皓(男)
                '10007'=>'197971',//爱小杭(男)
                '10008'=>'197978',//爱小辰(男)
                '10009'=>'197982',//飞镜(男)
            ],
             '7' => [
                '10000'=>'C-Audio-3327413b0693466c987b9db05a65e2f9',//智小敏(女)
                '10001'=>'C-Audio-65d3f6047c964ef5a6d00a30765116b3',//智小柔(女)
                '10002'=>'C-Audio-41a0d443dcd942c1aed649ca7a90d17c',//智小满(女)
                '10003'=>'C-Audio-6db46e9d33cd4e58aca40b850560462b',//爱小芊(女)
                '10004'=>'C-Audio-bcd472c1339349d095399e732635a6b8',//爱小静(女)
                '10005'=>'C-Audio-05d14d65c16d4f06a7c3e0017a91fcdc',//千嶂(男)
                '10006'=>'C-Audio-6c9d88135bdd41a9ac7365509b08b546',//智皓(男)
                '10007'=>'C-Audio-752df747b1414fda8f52ace1fb624401',//爱小杭(男)
                '10008'=>'C-Audio-804a07540fdf44429f4986e1172aedf7',//爱小辰(男)
                '10009'=>'C-Audio-375e688a8af0446caad3f6e7bbad719a',//飞镜(男)
            ]
        ];

        return $data[$model][$code] ?? '00000';

    }

    public static function getBuiltInVoiceList($model){
        $data = [
            '1' => [
                '39067',
                '39068',
                '39069',
                '39070',
                '39071',
                '39072',
                '39073',
                '39074',
                '39075',
                '39077',
            ],
            '2' => [
                '085e249c475e41efb518888d7bdbaca8',
                '9564332b7ab849949a9f214c5fd8ca8e',
                '55cf399b34aa43e8943a2217603a221e',
                '220f88345f84483bb42372ad13482b09',
                '90f9ed6823644e92958c700df0c05974',
                'fe58827d36af4d02b9a07616bca747ef',
                '75ba5f6c40ad42fb93d9bc4187e4ab28',
                '1ba7c4ad12fc4286b4e5190607063a1e',
                'ea8fc1cd3a754f12a1ad91c4f3909754',
                'e10bb9e089be4427960ab65b3c6daeee',
            ],
            '4' => [
                '197948',//智小敏(女)
                '197954',//智小柔(女)
                '197955',//智小满(女)
                '197960',//爱小芊(女)
                '197963',//爱小静(女)
                '197966',//千嶂(男)
                '197969',//智皓(男)
                '197973',//爱小杭(男)
                '197979',//爱小辰(男)
                '197983',//飞镜(男)
            ],
            '6' => [
                '197950',//智小敏(女)
                '197953',//智小柔(女)
                '197956',//智小满(女)
                '197961',//爱小芊(女)
                '197964',//爱小静(女)
                '197965',//千嶂(男)
                '197968',//智皓(男)
                '197971',//爱小杭(男)
                '197978',//爱小辰(男)
                '197982',//飞镜(男)
            ],
            '7' => [
                'C-Audio-3327413b0693466c987b9db05a65e2f9',//智小敏(女)
                'C-Audio-65d3f6047c964ef5a6d00a30765116b3',//智小柔(女)
                'C-Audio-41a0d443dcd942c1aed649ca7a90d17c',//智小满(女)
                'C-Audio-6db46e9d33cd4e58aca40b850560462b',//爱小芊(女)
                'C-Audio-bcd472c1339349d095399e732635a6b8',//爱小静(女)
                'C-Audio-05d14d65c16d4f06a7c3e0017a91fcdc',//千嶂(男)
                'C-Audio-6c9d88135bdd41a9ac7365509b08b546',//智皓(男)
                'C-Audio-752df747b1414fda8f52ace1fb624401',//爱小杭(男)
                'C-Audio-804a07540fdf44429f4986e1172aedf7',//爱小辰(男)
                'C-Audio-375e688a8af0446caad3f6e7bbad719a',//飞镜(男)
            ]
        ];

        return $data[$model] ?? '00000';

    }
       /**
     * @desc 获取模型列表
     * @return array
     * @date 2024/12/30 10:18
     * @author dagouzi
     */
    public static function getModelList()
    {
        $info =  ConfigService::get('model', 'list', []);
        $channel = $info['channel'] ?? [];
        foreach ($channel as $key => $value) {
            if (trim($value['icon']) != ''){
                $channel[$key]['icon'] = FileService::getFileUrl($value['icon']);
            }
            if ($value['status'] != 1) {

                unset($channel[$key]);
            }

        }
        $info['channel'] = array_values($channel);

        $voice = $info['voice'] ?? [];

        foreach ($voice as $key => $value) {

            if ($value['status'] != 1) {

                unset($voice[$key]);
            }
        }

        $info['voice'] = array_values($voice);

        return $info;
    }
}
