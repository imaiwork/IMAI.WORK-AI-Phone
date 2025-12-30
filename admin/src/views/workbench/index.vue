<template>
    <div class="space-y-5">
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">
            <el-card class="!border-none !rounded-xl shadow-lg" shadow="never">
                <template #header>
                    <div class="flex justify-between items-center">
                        <span class="card-title">版本信息</span>
                        <el-button link @click="refreshData">
                            <Icon name="el-icon-Refresh" :size="16" />
                            <span class="ml-1">刷新</span>
                        </el-button>
                    </div>
                </template>
                <div>
                    <div class="flex leading-9 items-center">
                        <div class="w-20 flex-shrink-0" style="color: #6b7280">平台名称</div>
                        <span class="font-medium"> {{ getWebName }}</span>
                        <div class="ml-2 leading-[0]">
                            <router-link :to="getRoutePath('setting.web.web_setting/getWebsite')">
                                <Icon name="el-icon-Edit" />
                            </router-link>
                        </div>
                    </div>
                    <div class="flex leading-9 items-center">
                        <div class="w-20 flex-shrink-0" style="color: #6b7280">当前版本</div>
                        <span class="font-medium"> {{ workbenchData.version.version_name }}</span>
                    </div>
                    <div class="flex leading-9 items-center">
                        <div class="w-20 flex-shrink-0" style="color: #6b7280">更新时间</div>
                        <span class="font-medium"> {{ workbenchData.version.update_time }}</span>
                    </div>
                    <div class="flex leading-9 items-center">
                        <div class="w-20 flex-shrink-0" style="color: #6b7280">安装时间</div>
                        <span class="font-medium"> {{ workbenchData.version.install_time }}</span>
                    </div>

                    <div class="flex leading-9 items-center">
                        <div class="w-20 flex-shrink-0" style="color: #6b7280">技术标识</div>
                        <span class="font-medium"> {{ config.by_name }}</span>
                        <div class="ml-2 leading-[0]">
                            <router-link :to="getRoutePath('setting.setting/activate')">
                                <Icon name="el-icon-Edit" />
                            </router-link>
                        </div>
                    </div>
                    <div class="flex leading-9 items-center">
                        <div class="w-20 flex-shrink-0" style="color: #6b7280">授权状态</div>
                        <span>
                            <el-tag type="success" v-if="config.is_auth == '1'">已授权</el-tag>
                            <el-tag type="danger" v-else>未授权</el-tag>
                        </span>
                        <router-link to="/setting/system/update" v-if="workbenchData.is_update">
                            <el-button class="ml-2" type="primary" size="small"> 有新版本更新 </el-button>
                        </router-link>
                    </div>
                </div>
            </el-card>
            <el-card class="!border-none !rounded-xl shadow-lg" shadow="never">
                <template #header>
                    <span class="card-title">用户统计</span>
                </template>
                <div class="grid grid-cols-2 gap-4">
                    <div
                        class="relative p-4 rounded-xl overflow-hidden text-white"
                        style="background: linear-gradient(to bottom right, #3b82f6, #1d4ed8)">
                        <Icon name="el-icon-User" class="absolute -right-4 -bottom-4 opacity-20" :size="80" />
                        <div class="text-sm">总用户</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ workbenchData.members?.total_members || 0 }}
                        </div>
                    </div>
                    <div
                        class="relative p-4 rounded-xl overflow-hidden text-white"
                        style="background: linear-gradient(to bottom right, #38bdf8, #0284c7)">
                        <Icon name="el-icon-User" class="absolute -right-4 -bottom-4 opacity-20" :size="80" />
                        <div class="text-sm">今日新增</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ workbenchData.members?.today_members || 0 }}
                        </div>
                    </div>
                    <div
                        class="relative p-4 rounded-xl overflow-hidden text-white"
                        style="background: linear-gradient(to bottom right, #8b5cf6, #6d28d9)">
                        <Icon name="el-icon-User" class="absolute -right-4 -bottom-4 opacity-20" :size="80" />
                        <div class="text-sm">充值用户</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ workbenchData.members?.recharge_members || 0 }}
                        </div>
                    </div>
                    <div
                        class="relative p-4 rounded-xl overflow-hidden text-white"
                        style="background: linear-gradient(to bottom right, #818cf8, #4f46e5)">
                        <Icon name="el-icon-User" class="absolute -right-4 -bottom-4 opacity-20" :size="80" />
                        <div class="text-sm">当前在线</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ workbenchData.members?.active_members || 0 }}
                        </div>
                    </div>
                </div>
            </el-card>
            <el-card class="!border-none !rounded-xl shadow-lg" shadow="never">
                <template #header>
                    <span class="card-title">财务数据</span>
                </template>
                <div class="grid grid-cols-2 gap-4">
                    <div
                        class="relative p-4 rounded-xl overflow-hidden text-white"
                        style="background: linear-gradient(to bottom right, #10b981, #047857)">
                        <Icon name="el-icon-Money" class="absolute -right-4 -bottom-4 opacity-20" :size="80" />
                        <div class="text-sm">总收入</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ workbenchData.finance?.total_income || 0 }}
                        </div>
                    </div>
                    <div
                        class="relative p-4 rounded-xl overflow-hidden text-white"
                        style="background: linear-gradient(to bottom right, #4ade80, #16a34a)">
                        <Icon name="el-icon-Money" class="absolute -right-4 -bottom-4 opacity-20" :size="80" />
                        <div class="text-sm">今日收入</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ workbenchData.finance?.today_income || 0 }}
                        </div>
                    </div>
                    <div
                        class="relative p-4 rounded-xl overflow-hidden text-white"
                        style="background: linear-gradient(to bottom right, #14b8a6, #0f766e)">
                        <Icon name="el-icon-Tickets" class="absolute -right-4 -bottom-4 opacity-20" :size="80" />
                        <div class="text-sm">总订单数</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ workbenchData.finance?.total_orders || 0 }}
                        </div>
                    </div>
                    <div
                        class="relative p-4 rounded-xl overflow-hidden text-white"
                        style="background: linear-gradient(to bottom right, #22d3ee, #0891b2)">
                        <Icon name="el-icon-Tickets" class="absolute -right-4 -bottom-4 opacity-20" :size="80" />
                        <div class="text-sm">今日订单数</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ workbenchData.finance?.today_orders || 0 }}
                        </div>
                    </div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none !rounded-xl shadow-lg" shadow="never">
            <template #header>
                <div class="flex justify-between items-center">
                    <span class="card-title">算力信息</span>
                    <el-button type="primary" @click="handleRecharge"> 充值算力 </el-button>
                </div>
            </template>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-sm" style="color: #6b7280">今日使用量</div>
                    <div class="text-4xl mt-2 font-semibold">
                        {{ workbenchData.tokens_info?.today_use || 0 }}
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm" style="color: #6b7280">已使用算力</div>
                    <div class="text-4xl mt-2 font-semibold">
                        {{ workbenchData.tokens_info?.total_use || 0 }}
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm" style="color: #6b7280">可用额度</div>
                    <div class="text-4xl mt-2 font-semibold">
                        {{ workbenchData.tokens_info?.total_balance || 0 }}
                    </div>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none !rounded-xl shadow-lg" shadow="never">
            <template #header>
                <span class="card-title">模型算力配置</span>
            </template>
            <el-tabs>
                <el-tab-pane label="聊天">
                    <ConfigTable :data="getCommonConfig" />
                </el-tab-pane>
                <el-tab-pane label="AI数字人">
                    <ConfigTable :data="getAiPersonConfig" />
                </el-tab-pane>
                <el-tab-pane label="美工设计">
                    <ConfigTable :data="getAiDrawConfig" />
                </el-tab-pane>
                <el-tab-pane label="思维导图">
                    <ConfigTable :data="getMindMapConfig" />
                </el-tab-pane>
                <el-tab-pane label="会议纪要">
                    <ConfigTable :data="getMeetingConfig" />
                </el-tab-pane>
                <el-tab-pane label="AI陪练">
                    <ConfigTable :data="getAiTrainConfig" />
                </el-tab-pane>
                <el-tab-pane label="AI客服">
                    <ConfigTable :data="getServiceConfig" />
                </el-tab-pane>
                <el-tab-pane label="AI面试">
                    <ConfigTable :data="getInterviewConfig" />
                </el-tab-pane>
                <el-tab-pane label="知识库">
                    <ConfigTable :data="getKnbConfig" />
                </el-tab-pane>
                <el-tab-pane label="AI视频获客">
                    <ConfigTable :data="getSphConfig" />
                </el-tab-pane>
                <el-tab-pane label="小红书">
                    <ConfigTable :data="getRedbookConfig" />
                </el-tab-pane>
                <el-tab-pane label="AI手机">
                    <div class="bg-danger px-10 py-1 rounded-lg mb-2 w-fit text-white text-center">
                        限时内侧体验，暂不收费 <br />
                        (2026-01-01至 2026-03-01)
                    </div>
                    <div class="text-[#F29A3B] my-3 font-bold text-lg">到期后暂定按以下标准执行</div>
                    <ConfigTable :data="getPhoneAutoConfig" />
                </el-tab-pane>
                <el-tab-pane label="其他">
                    <ConfigTable :data="getOtherConfig" />
                </el-tab-pane>
            </el-tabs>
        </el-card>
    </div>
    <popup ref="rechargePopup" title="充值算力" width="500px" async cancel-button-text="" confirm-button-text="">
        <el-form :model="rechargeForm" :rules="rechargeRules" ref="rechargeFormRef">
            <el-form-item label="兑换CDK" prop="cdkey">
                <div class="w-full">
                    <el-input v-model="rechargeForm.cdkey" placeholder="请输入兑换CDK" />
                </div>
            </el-form-item>
        </el-form>
        <div class="flex justify-end -mb-6">
            <el-button @click="handleRechargeClose">取消</el-button>
            <el-button type="primary" :loading="rechargeConfirmLock" @click="rechargeConfirm"> 确认 </el-button>
        </div>
    </popup>
</template>

<script lang="ts" setup name="workbench">
import { getWorkbench } from "@/api/app";
import { upgradeCheck } from "@/api/setting/update";
import { rechargeCDK } from "@/api/marketing/recharge";
import useAppStore from "@/stores/modules/app";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";
import { getRoutePath } from "@/router";

import ConfigTable from "./config-table.vue";
const appStore = useAppStore();
const { config } = toRefs(appStore);

// 表单数据
const workbenchData: any = reactive({
    version: {
        version: "", // 版本号
        website: "", // 官网
    },
    members: {}, // 今日数据
    finance: {},
    tokens_info: {}, // 算力信息
    tokens_lists: [], // 算力列表
    is_update: false, // 是否需要更新
});

const rechargePopup = ref();
const rechargeForm = reactive({
    cdkey: "",
});
const rechargeRules = reactive({
    cdkey: [{ required: true, message: "请输入兑换CDK", trigger: "blur" }],
});

const getWebName = computed(() => config.value.web_name);

const getUpdate = async () => {
    const result = await upgradeCheck({
        version: config.value.version_number,
    });
    workbenchData.is_update = result.is_update;
};

// 获取工作台主页数据
const getData = () => {
    getWorkbench()
        .then((res: any) => {
            workbenchData.version = res.version;
            workbenchData.members = res.members;
            workbenchData.visitor = res.visitor;
            workbenchData.finance = res.finance;
            workbenchData.tokens_info = res.tokens_info;
            workbenchData.tokens_lists = res.tokens_lists;
            workbenchData.tokens_lists.push(
                {
                    scene: "auto_phone_sph_add_wechat",
                    name: "视频号获客",
                    description: " 按照识别执行线索数量进行扣费",
                    cast_price: "1",
                    price: "2",
                    unit: "算力/次",
                    times: 999,
                },
                {
                    scene: "auto_phone_matrix_publish",
                    name: "社媒平台发布",
                    description: "按照内容的发布条数进行扣费",
                    cast_price: "10",
                    price: "20",
                    unit: "算力/条",
                    times: 999,
                },
                {
                    scene: "auto_phone_intercept",
                    name: "截流私信/评论",
                    description: "按照主动私信/评论时产生的token进行扣费",
                    cast_price: "1",
                    price: "2",
                    unit: "算力/1000token",
                    times: 999,
                },
                {
                    scene: "auto_phone_touch_reach",
                    name: "截流触达",
                    description: "按照找到的用户数进行扣费",
                    cast_price: "1",
                    price: "2",
                    unit: "算力/个",
                    times: 999,
                },
                {
                    scene: "auto_phone_comment",
                    name: "朋友圈评论",
                    description: "按照评论朋友圈时产生的token进行扣费",
                    cast_price: "1",
                    price: "2",
                    unit: "算力/1000token",
                    times: 999,
                },
                {
                    scene: "auto_phone_circle_publish",
                    name: "朋友圈发布",
                    description: "按照内容的发布条数进行扣费",
                    cast_price: "1",
                    price: "2",
                    unit: "算力/条",
                    times: 999,
                },
                {
                    scene: "auto_phone_matrix_publish",
                    name: "朋友圈点赞",
                    description: "按点赞朋友圈的次数进行扣费",
                    cast_price: "1",
                    price: "2",
                    unit: "算力/次",
                    times: 999,
                },
                {
                    scene: "auto_phone_add_wechat",
                    name: "自动加微",
                    description: "按发送好友申请的次数进行扣费",
                    cast_price: "1",
                    price: "2",
                    unit: "算力/个",
                    times: 999,
                },
                {
                    scene: "auto_phone_reply",
                    name: "私信接管(社媒平台)",
                    description: "按照自动回复时产生的token进行扣费）",
                    cast_price: "1",
                    price: "2",
                    unit: "算力/1000token",
                    times: 999,
                },
                {
                    scene: "auto_phone_matrix_publish",
                    name: "自动养号(社媒平台)",
                    description: "按照自动养号运行时长进行扣费",
                    cast_price: "1",
                    price: "2",
                    unit: "算力/分钟",
                    times: 999,
                }
            );
        })
        .catch((err: any) => {
            console.log("err", err);
        });
};

const getCommonConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        ["common_chat", "scene_chat", "coze_agent_chat", "coze_workflow", "gemini_chat", "openai_chat"].includes(
            item.scene
        )
    );
});

const getAiPersonConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        [
            "human_copywriting",
            "human_avatar",
            "human_voice",
            "human_video",
            "human_audio",
            "human_video_ym",
            "human_audio_ym",
            "human_avatar_ym",
            "human_video_ymt",
            "human_audio_ymt",
            "human_avatar_ymt",
            "human_avatar_chanjing",
            "human_voice_chanjing",
            "human_video_chanjing",
            "human_avatar_shanjian",
            "human_voice_shanjian",
            "human_video_shanjian",
            "shanjian_copywriting_create",
            "shanjian_realman_broadcast",
            "shanjian_broadcast_mixcut",
            "shanjian_news_mixcut",
            "news_mixcut_title",
            "sora_video_create",
            "sora_pro_video_create",
            "sora_copywriting_create",
            "human_avatar_sora",
        ].includes(item.scene)
    );
});

const getAiDrawConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        [
            "text_to_image",
            "image_to_image",
            "goods_image",
            "model_image",
            "image_prompt",
            "txt_to_posterimg",
            "volc_txt_to_img",
            "volc_txt_to_posterimg",
            "volc_txt_to_posterimg_v2",
            "volc_text_to_video",
            "volc_image_to_video",
            "volc_img_to_img_v2",
            "volc_txt_to_img_v2",
            "doubao_txt_to_video",
            "doubao_img_to_video",
            "ai_draw_video_prompt",
            "combined_picture_title",
            "combined_picture",
        ].includes(item.scene)
    );
});

const getMeetingConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) => ["meeting"].includes(item.scene));
});

const getMindMapConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) => ["mind_map"].includes(item.scene));
});

const getAiTrainConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) => ["lianlian"].includes(item.scene));
});

const getInterviewConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) => ["interview_chat"].includes(item.scene));
});

const getServiceConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        ["ai_wechat", "ai_reply_like", "ai_xhs"].includes(item.scene)
    );
});

const getKnbConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        ["knowledge_create", "knowledge_chat", "create_vector_knowledge", "text_to_vector"].includes(item.scene)
    );
});

const getRedbookConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        ["keyword_to_title", "keyword_to_subtitle", "keyword_to_copywriting"].includes(item.scene)
    );
});

const getSphConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        [
            "sph_add_wechat",
            "sph_add_friends",
            "sph_private_chat",
            "sph_search_terms",
            "sph_ocr",
            "sph_local_ocr",
        ].includes(item.scene)
    );
});

const getOtherConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) => ["video_clip", "matrix_copywriting"].includes(item.scene));
});

const getPhoneAutoConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        [
            "auto_phone_sph_add_wechat",
            "auto_phone_reply",
            "auto_phone_matrix_publish",
            "auto_phone_circle_publish",
            "auto_phone_comment",
            "auto_phone_add_wechat",
            "auto_phone_intercept",
            "auto_phone_touch_reach",
            "auto_phone_circle_publish",
        ].includes(item.scene)
    );
});

const refreshData = async () => {
    await getData();
    getUpdate();
    feedback.msgSuccess("刷新成功");
};

const handleRecharge = () => {
    rechargePopup.value.open();
};

const handleRechargeClose = () => {
    rechargePopup.value.close();
};

const handleRechargeConfirm = async () => {
    if (!rechargeForm.cdkey) {
        feedback.msgError("请输入兑换CDK");
        return;
    }
    await rechargeCDK({
        cdkey: rechargeForm.cdkey,
    });
    getData();
    handleRechargeClose();
};

const { lockFn: rechargeConfirm, isLock: rechargeConfirmLock } = useLockFn(handleRechargeConfirm);

onMounted(() => {
    getData();
    getUpdate();
});
</script>
