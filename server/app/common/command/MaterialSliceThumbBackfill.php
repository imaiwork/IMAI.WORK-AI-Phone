<?php

namespace app\common\command;

use app\common\service\ffmpeg\MaterialSliceBatchService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class MaterialSliceThumbBackfill extends Command
{
    protected function configure()
    {
        $this->setName('material:slice-thumb-backfill')
            ->setDescription('为缺少封面的切割素材补生成 thumbnail_url')
            ->addOption('persona_id', null, Option::VALUE_OPTIONAL, '限定人设ID', 0)
            ->addOption('limit', null, Option::VALUE_OPTIONAL, '单次处理数量', 100);
    }

    protected function execute(Input $input, Output $output)
    {
        $personaId = (int)$input->getOption('persona_id');
        $limit = (int)$input->getOption('limit');
        $result = (new MaterialSliceBatchService())->backfillMissingThumbnails($personaId, $limit);
        $output->writeln(json_encode($result, JSON_UNESCAPED_UNICODE));
        return 0;
    }
}
