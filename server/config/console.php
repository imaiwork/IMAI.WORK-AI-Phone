<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        // GEO官网SEO定时发布
        'geo_site_publish'   => 'app\\common\\command\\GeoSitePublish',
        // GEO每日自动监测
        'geo_daily_monitor'  => 'app\\common\\command\\GeoDailyMonitor',
        // GEO发稿平台回执同步
        'geo_publish_sync'   => 'app\\common\\command\\GeoPublishSync',
        // GEO监测 cell 定时执行(一键诊断/每日监测)
        'geo_monitor_cron'   => 'app\\common\\command\\GeoMonitorCron',
        // 定时任务
        'crontab'            => 'app\common\command\Crontab',
        // 退款查询
        'query_refund'       => 'app\common\command\QueryRefund',
        // 图片生成状态
        'hd_status'          => 'app\common\command\HdStatus',
        // 扣除用户未使用的算力
        'change_user_tokens' => 'app\common\command\ChangeUserTokens',
        // 数字人任务
        'human_video_task_cron'        => 'app\common\command\HumanVideoTaskCron',
        // AI陪练分析
        'lianlian_analysis_cron'        => 'app\common\command\LianlianAnalysisCron',
        // AI微信消息推送
        'ai_wechat_cron'        => 'app\common\command\AiWechatCron',
        // AI微信sop消息推送
        'ai_wechat_sop_cron'        => 'app\common\command\AiWechatSopCron',
        'ws'                            => 'app\common\command\Ws',
        //知识库文档状态更新
        'file_status_cron'        => 'app\common\command\FileStatusCron',
        //知识库文档切片拉取
        'file_chunks_pull_cron'        => 'app\common\command\FileChunksPullCron',
        //待发布数据拉取
        'publish_detail_cron' => 'app\common\command\PublishDetailCron',
        //爆款图文图片改写状态轮询
        'viral_image_rewrite_cron' => 'app\common\command\ViralImageRewriteCron',
        //爆款复刻小红书图文改写
        'video_imitation_image_rewrite_cron' => 'app\common\command\VideoImitationImageRewriteCron',
        //爆款复刻图文提取超时标失败（超30分钟）
        'video_imitation_parse_recover_cron' => 'app\common\command\VideoImitationParseRecoverCron',
        //图文发布填坑（未使用库存按 id 升序）
        'viral_image_text_publish_fill_cron' => 'app\common\command\ViralImageTextPublishFillCron',
        //爆款手动导入解析
        'viral_manual_import_cron' => 'app\common\command\ViralManualImportCron',
        //文案查询
        'query_sv_copywriting_cron' => 'app\common\command\QuerySvCopywritingTaskCron',
        'workerman:server' => 'app\common\command\WorkermanServie',

        //oss迁移
        'oss_migration_cron' => 'app\common\command\OssMigrationCron',
        //即梦视频队列
        'draw_video_task' => 'app\common\command\DrawVideoTaskCron',
        //自动微信朋友圈点赞评论
        'ai_circle_reply_like' => 'app\common\command\AiCircleReplyLike',
        'device_rpa_cron' => 'app\common\command\DeviceRpaCron',
        'start_channel' => 'app\common\command\StartChannel',
        'note_publish_cron' => 'app\common\command\NotePublishCron',
        'single:server' => 'app\common\command\SingleWorkerService',
        'sph_clues_add_wechat' => 'app\common\command\SphCluesAddWechat',
        //闪剪视频合成
        'shanjian_video_task' => 'app\common\command\ShanjianVideoTaskCron',
        //闪剪中台单队列状态
        'shanjian_queue_status' => 'app\common\command\ShanjianQueueStatusCron',
        //手动加微信任务
        'crawling_manual_cron' => 'app\common\command\CrawlingManualCron',
        //设备任务调度
        'task:scheduler' => 'app\common\command\DeviceTaskScheduler',
        //替换cron表达式
        'replace_cron' => 'app\common\command\ReplaceCron',
        //验证微信用户
        'wechat_verify' => 'app\common\command\WechatVerifyCron',
        'hd_puzzle_cron' => 'app\common\command\HdPuzzleCron',
        //sora视频合成
        'sora_video_task' => 'app\common\command\SoraVideoTaskCron',
        //设备自动线索词生成
        'auto_device_create_cron' => 'app\common\command\AutoDeviceCreateCron',
        //设备自动加微任务生成
        'auto_device_frist_create_cron' => 'app\common\command\AutoDeviceFristCreateCron',
        //设备自动任务调度
        'auto_task:scheduler' => 'app\common\command\DeviceAutoTaskScheduler',
        //ai授权视频数字人形象任务
        'ai_digital_human_anchor_cron' => 'app\common\command\AiDigitalHumanAnchorCron',
        //文件处理
        'ffmpeg_cron' => 'app\common\command\FFmpegFileCron',
        //公共数字人形象任务
        'digital_human_anchor_cron' => 'app\common\command\DigitalHumanAnchorCron',
        'wechat_rpa_cron' => 'app\common\command\WechatRpaCron',
        'kb_cron' => 'app\common\command\KbCron',
        //分镜混剪视频合成
        'storyboard_video_task' => 'app\common\command\StoryboardVideoTaskCron',
        //【已合并】自动合成设备视频任务 -> 统一入口 auto_video_synthesis
        // 'auto_device_video_synthesis' => 'app\common\command\AutoDeviceVideoSynthesis',
        //自动合成视频任务（统一入口，按 ai_persona_synthesis_config.copywriting_source 分流）
        'auto_video_synthesis' => 'app\common\command\AutoVideoSynthesis',
        // 素材转码就绪门禁验证(仅用于人工验证,不进 cron)
        'material:readiness-verify' => 'app\common\command\MaterialReadinessVerify',
        // 素材转码卡住任务巡检恢复
        'media:transcode-recover' => 'app\common\command\MediaTranscodeRecoverCron',
        // 素材批量分割中断/超时回滚与退款
        'material:split-recover' => 'app\common\command\MaterialSliceRecoverCron',
        // 切割素材缺少封面时补生成 thumbnail_url
        'material:slice-thumb-backfill' => 'app\common\command\MaterialSliceThumbBackfill',
        //本地自动合成设备视频任务
        // 'local_video_synthesis' => 'app\common\command\LocalVideoSynthesis',
        // //企业自动合成设备视频任务
        // 'enterprise_video_synthesis' => 'app\common\command\EnterpriseVideoSynthesis',
        // 爆款仿写自动发布
        'video_imitation_publish' => 'app\common\command\VideoImitationPublishCron',
        // 微信本地自动合成设备视频任务
        'wechat_video_synthesis' => 'app\common\command\WechatVideoSynthesis',
        // 微信企业自动合成设备视频任务
        // 'wechat_enterprise_video_synthesis' => 'app\common\command\WechatEnterpriseVideoSynthesis',
        // // 微信本地自动合成设备视频任务
        // 'wechat_local_video_synthesis' => 'app\common\command\WechatLocalVideoSynthesis',
        // 重置合成视频任务
        'reset_video_synthesis' => 'app\common\command\ResetVideoSynthesis',
        // 【已合并】自动合成模仿视频任务 -> 统一入口 auto_video_synthesis
        // 'auto_imitation_video_synthesis' => 'app\common\command\AutoImitationVideoSynthesis',
        // 合成minimax音频，兼容闪剪任务
        'minimax_shanjian_cron' => 'app\common\command\MinimaxShanjianCron',
        // 初始化人物模板
        'init_persona_template' => 'app\common\command\InitPersonaTemplate',
        // 同步中台模型(对话+生图/生视频)
        'sync_models' => 'app\common\command\SyncModelsCron',
        // 同步中台设备CDK
        'sync_device_auth_codes' => 'app\common\command\SyncDeviceAuthCodesCron',
        // 同步默认公共形象母版到站长存储
        'sync_default_public_anchor_assets' => 'app\common\command\SyncDefaultPublicAnchorAssets',
        //会员周期算力发放
        'member_daily_grant' => 'app\common\command\MemberDailyGrantCron',
        //会员到期检查 + 软降级冻结
        'member_expire_check' => 'app\common\command\MemberExpireCheckCron',
        // 同步中台闪剪剪辑模板
        'sync_shanjian_clip_templates' => 'app\common\command\SyncShanjianClipTemplatesCron',
        // 团队OEM 计费/退费自检(仅人工验证,不进 cron)
        'team:oem-selfcheck' => 'app\common\command\TeamOemSelfCheck',
        // 团队扣费主体诊断(个人 vs 企业钱包)
        'team:billing-diagnose' => 'app\common\command\TeamBillingDiagnose',
        // 团队OEM 测试数据播种(测试环境用,--clean 可清理)
        'team:seed-test' => 'app\common\command\TeamSeedTest',
        // 清理过期设备日志
        'device_log_clean' => 'app\common\command\SvDeviceLogCleanCron',
    ],

    
];
