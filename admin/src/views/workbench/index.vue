<template>
    <div class="gap-4">
        <div class="w-full flex flex-wrap gap-4">
            <el-card class="!border-none flex-1" shadow="never">
                <template #header>
                    <div class="flex justify-between items-center">
                        <span class="card-title">版本信息</span>
                        <!-- <el-popover width="200">
                            <template #reference>
                                <el-button type="primary">技术支持</el-button>
                            </template>
                            <div class="flex justify-center items-center">
                                <img class="w-[150px] h-[150px]" src="@/assets/images/support.png" />
                            </div>
                        </el-popover> -->
                    </div>
                </template>
                <div class="relative">
                    <div class="absolute top-2 right-0">
                        <el-button link @click="refreshData">
                            <Icon name="el-icon-Refresh" :size="20"></Icon>
                            <span>刷新</span>
                        </el-button>
                    </div>
                    <div class="flex leading-9 items-center">
                        <div class="w-20">平台名称</div>
                        <span> {{ getWebName }}</span>
                        <div class="ml-2 leading-[0]">
                            <router-link :to="getRoutePath('setting.web.web_setting/getWebsite')">
                                <Icon name="el-icon-Edit"></Icon>
                            </router-link>
                        </div>
                    </div>
                    <div class="flex leading-9">
                        <div class="w-20">更新时间</div>
                        <span> {{ workbenchData.version.update_time }}</span>
                    </div>
                    <div class="flex leading-9">
                        <div class="w-20">安装时间</div>
                        <span> {{ workbenchData.version.install_time }}</span>
                    </div>
                    <div class="flex leading-9">
                        <div class="w-20">授权状态</div>
                        <span>
                            <el-tag type="success" v-if="config.is_auth == '1'">已授权</el-tag>
                            <el-tag type="danger" v-else>未授权</el-tag>
                        </span>
                        <router-link to="/setting/system/update" v-if="workbenchData.is_update">
                            <el-button class="ml-2" type="primary"> 有新版本更新 </el-button>
                        </router-link>
                    </div>
                    <div class="flex leading-9">
                        <div class="w-20">当前版本</div>
                        <span> {{ workbenchData.version.version_name }}</span>
                    </div>
                    <div class="flex leading-9 items-center">
                        <div class="w-20">技术标识</div>
                        <span> {{ config.by_name }}</span>
                        <div class="ml-2 leading-[0]">
                            <router-link :to="getRoutePath('setting.setting/activate')">
                                <Icon name="el-icon-Edit"></Icon>
                            </router-link>
                        </div>
                    </div>
                </div>
            </el-card>
            <el-card class="!border-none flex-1" shadow="never">
                <template #header>
                    <span>用户统计</span>
                </template>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white p-4 rounded-lg shadow-lighter flex justify-between">
                        <div
                            class="w-[28px] h-[28px] flex justify-center items-center bg-[#F59B22] rounded-full flex-shrink-0">
                            <Icon name="el-icon-User" color="#ffffff" :size="20"></Icon>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold leading-6">
                                <span class="text-5xl"> {{ workbenchData.members?.total_members || 0 }} </span>人
                            </div>
                            <div class="text-lg text-info mt-3">总用户</div>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-lighter flex justify-between">
                        <div
                            class="w-[28px] h-[28px] flex justify-center items-center bg-[#3D3CDD] rounded-full flex-shrink-0">
                            <Icon name="el-icon-User" color="#ffffff" :size="20"></Icon>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold leading-6">
                                <span class="text-5xl"> {{ workbenchData.members?.today_members || 0 }} </span>人
                            </div>
                            <div class="text-lg text-info mt-3">今日新增用户数</div>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-lighter flex justify-between">
                        <div
                            class="w-[28px] h-[28px] flex justify-center items-center bg-[#38C66B] rounded-full flex-shrink-0">
                            <Icon name="el-icon-User" color="#ffffff" :size="20"></Icon>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold leading-6">
                                <span class="text-5xl"> {{ workbenchData.members?.recharge_members || 0 }} </span>人
                            </div>
                            <div class="text-lg text-info mt-3">充值用户</div>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-lighter flex justify-between">
                        <div
                            class="w-[28px] h-[28px] flex justify-center items-center bg-[#E9E305] rounded-full flex-shrink-0">
                            <Icon name="el-icon-User" color="#ffffff" :size="20"></Icon>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold leading-6">
                                <span class="text-5xl"> {{ workbenchData.members?.active_members || 0 }} </span>人
                            </div>
                            <div class="text-lg text-info mt-3">当前在线用户数</div>
                        </div>
                    </div>
                </div>
            </el-card>
            <el-card class="!border-none w-full xl:flex-1" shadow="never">
                <template #header>
                    <span>财务数据</span>
                </template>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-white bg-[#F59B22] p-4 rounded-lg flex justify-between gap-2">
                        <Icon name="el-icon-Money" :size="20"></Icon>
                        <div class="text-right">
                            <div class="text-5xl font-bold leading-6">
                                {{ workbenchData.finance?.total_income || 0 }}
                            </div>
                            <div class="text-lg mt-3">总收入</div>
                        </div>
                    </div>
                    <div class="text-white bg-[#3D3CDD] p-4 rounded-lg flex justify-between gap-2">
                        <Icon name="el-icon-Tickets" :size="20"></Icon>
                        <div class="text-right">
                            <div class="text-5xl font-bold leading-6">
                                {{ workbenchData.finance?.total_orders || 0 }}
                            </div>
                            <div class="text-lg mt-3">订单数</div>
                        </div>
                    </div>
                    <div class="text-white bg-[#38C66B] p-4 rounded-lg flex justify-between gap-2">
                        <Icon name="el-icon-Money" :size="20"></Icon>
                        <div class="text-right">
                            <div class="text-5xl font-bold leading-6">
                                {{ workbenchData.finance?.today_income || 0 }}
                            </div>
                            <div class="text-lg mt-3">今日收入</div>
                        </div>
                    </div>
                    <div class="text-white bg-[#E9E305] p-4 rounded-lg flex justify-between gap-2">
                        <Icon name="el-icon-Tickets" :size="20"></Icon>
                        <div class="text-right">
                            <div class="text-5xl font-bold leading-6">
                                {{ workbenchData.finance?.today_orders || 0 }}
                            </div>
                            <div class="text-lg mt-3">今日订单数</div>
                        </div>
                    </div>
                </div>
            </el-card>
        </div>
        <div class="grow min-h-0 flex flex-col gap-4 mt-4">
            <el-card class="!border-none" shadow="never">
                <template #header>
                    <div class="flex justify-between items-center">
                        <span class="card-title">算力信息</span>
                        <el-button type="primary" @click="handleRecharge"> 充值算力 </el-button>
                    </div>
                </template>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="leading-10">今日使用量</div>
                        <div class="text-6xl">
                            {{ workbenchData.tokens_info?.today_use || 0 }}
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="leading-10">已使用算力</div>
                        <div class="text-6xl">
                            {{ workbenchData.tokens_info?.total_use || 0 }}
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="leading-10">可用额度</div>
                        <div class="text-6xl">
                            {{ workbenchData.tokens_info?.total_balance || 0 }}
                        </div>
                    </div>
                </div>
            </el-card>
            <ConfigTable title="通用聊天" :data="getCommonConfig" />
            <ConfigTable title="AI数字人" :data="getAiPersonConfig" />
            <ConfigTable title="美工设计" :data="getAiDrawConfig" />
            <ConfigTable title="思维导图" :data="getMindMapConfig" />
            <ConfigTable title="会议纪要" :data="getMeetingConfig" />
            <ConfigTable title="AI陪练" :data="getAiTrainConfig" />
            <ConfigTable title="AI客服" :data="getServiceConfig" />
            <ConfigTable title="AI面试" :data="getInterviewConfig" />
            <ConfigTable title="知识库" :data="getKnbConfig" />
            <ConfigTable title="AI视频获客" :data="getSphConfig" />
            <ConfigTable title="小红书" :data="getRedbookConfig" />
            <ConfigTable title="其他" :data="getOtherConfig" />
        </div>
    </div>
    <popup ref="rechargePopup" title="充值算力" width="500px" async cancel-button-text="" confirm-button-text="">
        <el-form :model="rechargeForm" :rules="rechargeRules" ref="rechargeFormRef">
            <el-form-item label="兑换CDK" prop="cdkey">
                <div class="w-full">
                    <el-input v-model="rechargeForm.cdkey" placeholder="请输入兑换CDK" />
                    <!-- <div class="text-primary flex justify-end">
                        <el-popover trigger="click">
                            <template #reference>
                                <span class="cursor-pointer"> 未有CDK？点此前往购买 </span>
                            </template>
                            <div>
                                <img src="@/assets/images/cdk_qrcode.jpg" />
                            </div>
                        </el-popover>
                    </div> -->
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
        })
        .catch((err: any) => {
            console.log("err", err);
        });
};

const getCommonConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        ["common_chat", "scene_chat", "coze_agent_chat", "coze_workflow", "gemini_chat"].includes(item.scene)
    );
});

const getAiPersonConfig = computed(() => {
    return workbenchData.tokens_lists.filter((item: any) =>
        [
            "human_copywriting",
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
        ["ai_wechat", "openai_chat", "ai_reply_like", "ai_xhs"].includes(item.scene)
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
