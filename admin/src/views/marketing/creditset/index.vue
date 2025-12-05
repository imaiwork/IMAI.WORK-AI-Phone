<template>
    <div>
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
        ["common_chat", "scene_chat", "coze_agent_chat", "coze_workflow", "gemini_chat"].includes(item.scene)
    );
});

const getAiPersonConfig = computed(() => {
    return tableData.value.filter((item) =>
        [
            "human_copywriting",
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
    return tableData.value.filter((item) =>
        ["ai_wechat", "ai_xhs", "openai_chat", "ai_reply_like"].includes(item.scene)
    );
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
