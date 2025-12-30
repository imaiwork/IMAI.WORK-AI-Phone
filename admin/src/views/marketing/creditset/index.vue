<template>
    <el-card class="!border-none" shadow="never">
        <el-tabs>
            <el-tab-pane label="通用">
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
            <el-tab-pane label="其他">
                <ConfigTable :data="getOtherConfig" />
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

const getCommonConfig = computed(() => {
    return tableData.value.filter((item) =>
        ["common_chat", "scene_chat", "coze_agent_chat", "coze_workflow", "gemini_chat", "openai_chat"].includes(
            item.scene
        )
    );
});

const getAiPersonConfig = computed(() => {
    return tableData.value.filter((item) =>
        [
            "human_copywriting",
            "human_avatar",
            "human_voice",
            "human_video",
            "human_audio",
            "human_video_ym",
            "human_avatar_ym",
            "human_audio_ym",
            "human_video_ymt",
            "human_avatar_ymt",
            "human_audio_ymt",
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
    return tableData.value.filter((item) =>
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
    return tableData.value.filter((item) => ["meeting"].includes(item.scene));
});

const getMindMapConfig = computed(() => {
    return tableData.value.filter((item) => ["mind_map"].includes(item.scene));
});

const getAiTrainConfig = computed(() => {
    return tableData.value.filter((item) => ["lianlian"].includes(item.scene));
});

const getInterviewConfig = computed(() => {
    return tableData.value.filter((item) => ["interview_chat"].includes(item.scene));
});

const getServiceConfig = computed(() => {
    return tableData.value.filter((item) => ["ai_wechat", "ai_xhs", "ai_reply_like"].includes(item.scene));
});

const getKnbConfig = computed(() => {
    return tableData.value.filter((item) =>
        ["knowledge_create", "knowledge_chat", "create_vector_knowledge", "text_to_vector"].includes(item.scene)
    );
});

const getRedbookConfig = computed(() => {
    return tableData.value.filter((item) =>
        ["keyword_to_title", "keyword_to_subtitle", "keyword_to_copywriting"].includes(item.scene)
    );
});

const getSphConfig = computed(() => {
    return tableData.value.filter((item) =>
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
    return tableData.value.filter((item) => ["video_clip", "matrix_copywriting"].includes(item.scene));
});

const getConfig = async () => {
    const data = await getCreditSet();
    tableData.value = data;
    Object.keys(data).forEach((key) => {
        //@ts-ignore
        formData[key] = data[key];
    });
};

const saveConfig = async () => {
    await setCreditSet(tableData.value);
    getConfig();
};

const { isLock, lockFn: lockSaveConfig } = useLockFn(saveConfig);

getConfig();
</script>

<style scoped></style>
