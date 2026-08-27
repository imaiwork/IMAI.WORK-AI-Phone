<template>
    <div
        class="bg-white border border-[#bfdbfe] rounded-xl p-5 mb-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between relative overflow-hidden">
        <div class="absolute top-0 left-0 w-1 h-full bg-[#3b82f6]"></div>
        <div class="flex items-center mb-4 md:mb-0 ml-3">
            <div class="w-12 h-12 bg-[#eff6ff] rounded-full flex items-center justify-center text-[#2563eb] mr-4">
                <el-icon :size="22"><Switch /></el-icon>
            </div>
            <div>
                <h2 class="text-base font-bold text-[#1f2937] mb-1">全局汇率 (人民币 ⇄ 算力)</h2>
                <p class="text-xs text-[#6b7280]">下方所有接口的"算力"将自动折算为"人民币"以计算利润。</p>
            </div>
        </div>
        <div class="flex items-center bg-[#f9fafb] border border-[#e5e7eb] rounded-lg px-4 py-3 shadow-inner">
            <span>平台进货汇率：</span>
            <span class="text-[#4b5563] font-bold">1 元 (RMB) =</span>
            <span class="text-[#4b5563] font-bold">
                {{ globalRate }}
                算力</span
            >
        </div>
    </div>
    <el-card class="!border-none" shadow="never">
        <el-tabs>
            <el-tab-pane label="通用">
                <ConfigTable v-model:globalRate="globalRate" :data="getCommonConfig" />
            </el-tab-pane>
            <el-tab-pane label="AI数字人">
                <ConfigTable v-model:globalRate="globalRate" :data="getAiPersonConfig" />
            </el-tab-pane>
            <el-tab-pane label="美工设计">
                <ConfigTable v-model:globalRate="globalRate" :data="getAiDrawConfig" />
            </el-tab-pane>
            <el-tab-pane label="会议纪要">
                <ConfigTable v-model:globalRate="globalRate" :data="getMeetingConfig" />
            </el-tab-pane>
            <el-tab-pane label="AI陪练">
                <ConfigTable v-model:globalRate="globalRate" :data="getAiTrainConfig" />
            </el-tab-pane>
            <el-tab-pane label="AI面试">
                <ConfigTable v-model:globalRate="globalRate" :data="getInterviewConfig" />
            </el-tab-pane>
            <el-tab-pane label="爆款仿写">
                <ConfigTable v-model:globalRate="globalRate" :data="getHotWriteConfig" />
            </el-tab-pane>
            <el-tab-pane label="热点追踪">
                <ConfigTable v-model:globalRate="globalRate" :data="getHotspotConfig" />
            </el-tab-pane>
            <el-tab-pane label="知识库">
                <ConfigTable v-model:globalRate="globalRate" :data="getKnbConfig" />
            </el-tab-pane>
            <el-tab-pane label="AI视频获客">
                <ConfigTable v-model:globalRate="globalRate" :data="getSphConfig" />
            </el-tab-pane>
            <el-tab-pane label="矩阵">
                <ConfigTable v-model:globalRate="globalRate" :data="getMatrixConfig" />
            </el-tab-pane>
            <el-tab-pane label="AI手机">
                <ConfigTable v-model:globalRate="globalRate" :data="getPhoneAutoConfig" />
            </el-tab-pane>
            <el-tab-pane label="其他">
                <ConfigTable v-model:globalRate="globalRate" :data="getOtherConfig" />
            </el-tab-pane>
        </el-tabs>
    </el-card>
    <footer-btns>
        <el-button
            v-perms="['finance.marketing.creditset/save']"
            type="primary"
            :loading="isLock"
            @click="lockSaveConfig">
            保存
        </el-button>
    </footer-btns>
</template>

<script setup lang="ts">
import { getCreditSet, setCreditSet } from "@/api/marketing/creditset";
import { useLockFn } from "@/hooks/useLockFn";
import ConfigTable from "./config-table.vue";

const formData = reactive<any>({});
const tableData = ref<any[]>([]);

// 全局汇率，所有 tab 共享同一个值
const globalRate = ref<number>(100);

const getCommonConfig = computed(() =>
    tableData.value.filter((item) => ["coze_agent_chat", "coze_workflow"].includes(item.scene)),
);

const getAiPersonConfig = computed(() =>
    tableData.value.filter((item) =>
        [
            "human_avatar_chanjing",
            "human_voice_chanjing",
            "human_video_chanjing",
            "human_avatar_shanjian",
            "human_voice_shanjian",
            "human_video_shanjian",
            "shanjian_realman_broadcast",
            "shanjian_broadcast_mixcut",
            "shanjian_news_mixcut",
            "ai_shanjian_authorized_video",
            "storyboard_video_create",
            "seedance2_480p_image2video_create",
            "seedance2_720p_image2video_create",
            "seedance2_480p_video2video_create",
            "seedance2_720p_video2video_create",
            "human_voice_minimax_hd",
            "human_voice_minimax_turbo",
            "human_audio_minimax_hd",
            "human_audio_minimax_turbo",
            "shanjian_ai_cover",
            "human_avatar_shanjian_pro",
        ].includes(item.scene),
    ),
);

const getAiDrawConfig = computed(() =>
    tableData.value.filter((item) =>
        ["ai_draw_video_prompt", "combined_picture_title", "combined_picture"].includes(item.scene),
    ),
);

const getMeetingConfig = computed(() => tableData.value.filter((item) => ["meeting"].includes(item.scene)));
const getMindMapConfig = computed(() => tableData.value.filter((item) => ["mind_map"].includes(item.scene)));
const getAiTrainConfig = computed(() => tableData.value.filter((item) => ["lianlian"].includes(item.scene)));
const getInterviewConfig = computed(() => tableData.value.filter((item) => ["interview_chat"].includes(item.scene)));
const getServiceConfig = computed(() =>
    tableData.value.filter((item) => ["ai_wechat", "ai_xhs", "ai_reply_like"].includes(item.scene)),
);
const getKnbConfig = computed(() =>
    tableData.value.filter((item) =>
        ["knowledge_create", "knowledge_chat", "create_vector_knowledge", "text_to_vector"].includes(item.scene),
    ),
);
const getMatrixConfig = computed(() =>
    tableData.value.filter((item) =>
        ["keyword_to_title", "keyword_to_subtitle", "keyword_to_copywriting"].includes(item.scene),
    ),
);
const getSphConfig = computed(() =>
    tableData.value.filter((item) =>
        [
            "sph_add_wechat",
            "sph_add_friends",
            "sph_private_chat",
            "sph_search_terms",
            "sph_ocr",
            "sph_local_ocr",
        ].includes(item.scene),
    ),
);
const getPhoneAutoConfig = computed(() =>
    tableData.value.filter((item: any) =>
        [
            "automation_social_media_released",
            "automation_shut_off_private_letter",
            "automation_friends_circle_comments",
            "automation_friends_circle_released",
            "automation_friends_circle_praise",
            "automation_wechat_add_friend",
            "automation_social_media_obtain",
            "automation_social_media_nursing",
            "automation_ocr_local",
            "automation_ocr_img",
            "automation_city_exposure",
            "automation_city_touch",
            "automation_group_buy",
            "automation_precise_clues",
        ].includes(item.scene),
    ),
);
const getHotWriteConfig = computed(() =>
    tableData.value.filter((item) => ["video_imitation_copywriting_parse"].includes(item.scene)),
);

const getHotspotConfig = computed(() => tableData.value.filter((item) => ["hotspot_insight"].includes(item.scene)));

const getOtherConfig = computed(() =>
    tableData.value.filter((item) =>
        [
            "video_clip",
            "matrix_copywriting",
            "ai_persona_analysis",
            "ai_persona_report",
            "coze_copywriting",
            "douyin_js",
            "coze_copywriting_senior",
            "grab_image",
            "grab_video",
            "get_hot_words",
            "extract_keywords",
            "map_chat_clues",
            "images_explosion_rewrite",
            "video_imitation_copywriting_parse",
            "material_slice_oss",
            "material_slice_local",
        ].includes(item.scene),
    ),
);

const getConfig = async () => {
    const data = await getCreditSet();
    tableData.value = data;
    Object.keys(data).forEach((key) => {
        formData[key] = (data as any)[key];
    });
};

const saveConfig = async () => {
    await setCreditSet(tableData.value);
    getConfig();
};

const { isLock, lockFn: lockSaveConfig } = useLockFn(saveConfig);

getConfig();
</script>

<style scoped lang="scss">
:deep(.rate-input) {
    .el-input__wrapper {
        box-shadow: none !important;
    }
    .el-input__inner {
        text-align: center;
        font-size: 1.2rem;
        font-weight: 900;
        color: #2563eb;
        border: none;
        border-bottom: 2px solid #3b82f6;
        border-radius: 0;
        background: transparent;
    }
}
</style>
