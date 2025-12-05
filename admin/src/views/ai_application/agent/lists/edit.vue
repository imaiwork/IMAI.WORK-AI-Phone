<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="flex justify-between items-center">
                <el-page-header @back="$router.back()" />
            </div>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-tabs v-model="currentTab">
                <el-tab-pane v-for="tab in tabs" :key="tab.name" :label="tab.label" :name="tab.name"> </el-tab-pane>
            </el-tabs>
            <div>
                <component ref="formRef" :is="currentComponent" v-model="formData" :agent-id="agentId" />
            </div>
        </el-card>
        <footer-btns v-if="![TabName.Skill, TabName.Publish, TabName.Call].includes(currentTab)">
            <el-button type="primary" :loading="isLock" @click="lockFn">保存</el-button>
        </footer-btns>
    </div>
</template>

<script setup lang="ts">
import { getAgentDetail, addAgent, updateAgent } from "@/api/ai_application/agent";
import BaseSetting from "./components/base-setting/index.vue";
import KnbSetting from "./components/knb-setting/index.vue";
import HumanizeSetting from "./components/humanize-setting/index.vue";
import InterfaceSetting from "./components/interface-setting/index.vue";
import SkillSetting from "./components/skill-setting/index.vue";
import ReplySetting from "./components/reply-setting/index.vue";
import { useLockFn } from "@/hooks/useLockFn";
import { setFormData } from "@/utils/util";
import { ModeTypeEnum, type Agent } from "./components/enums";
import { KnbTypeEnum } from "@/enums/appEnums";

enum TabName {
    Base = "base",
    Knb = "knb",
    Humanize = "humanize",
    Interface = "interface",
    Publish = "publish",
    Skill = "skill",
    Call = "call",
}

const route = useRoute();

const agentId = ref(route.query.id as string);

const currentTab = ref(TabName.Base);
const tabs = ref([
    {
        label: "人设",
        name: TabName.Base,
        component: shallowRef(BaseSetting),
    },
    {
        label: "知识库",
        name: TabName.Knb,
        component: shallowRef(KnbSetting),
    },
    {
        label: "拟人化",
        name: TabName.Humanize,
        component: shallowRef(HumanizeSetting),
    },
    {
        label: "界面配置",
        name: TabName.Interface,
        component: shallowRef(InterfaceSetting),
    },

    {
        label: "技能",
        name: TabName.Skill,
        component: shallowRef(SkillSetting),
    },
    {
        label: "调用设置",
        name: TabName.Call,
        component: shallowRef(ReplySetting),
    },
]);

const currentComponent = computed(() => tabs.value.find((tab) => tab.name === currentTab.value)?.component);

// 智能体表单数据
const formData = reactive<Agent>({
    id: agentId.value,
    cate_id: "",
    kb_type: 1,
    kb_ids: "",
    icons: "",
    image: "",
    bg_image: "",
    name: "",
    intro: "",
    model_id: "",
    model_sub_id: "",
    roles_prompt: "",
    search_mode: "similar",
    search_tokens: 3000,
    search_similar: 0.4,
    ranking_status: 0,
    ranking_score: 0.5,
    context_num: 3,
    is_public: 0,
    is_enable: 1,
    optimize_ask: 0,
    optimize_m_id: "",
    optimize_s_id: "",
    search_empty_type: 1,
    search_empty_text: "",
    top_p: 0.5,
    temperature: 1,
    presence_penalty: 0.1,
    frequency_penalty: 2,
    top_logprobs: 10,
    logprobs: 0,
    welcome_introducer: "",
    copyright: "",
    menus: [],
    flow_status: 0,
    flow_config: {
        workflow_id: "",
        bot_id: "",
        app_id: "",
        api_token: "",
    },
    threshold: 0.5,
    mode_type: ModeTypeEnum.CUSTOM,
});
const formRef = ref();

/**
 * @description 获取智能体详情
 */
const getDetail = async () => {
    if (!agentId.value) return;
    try {
        const data = await getAgentDetail({ id: agentId.value });
        setFormData(data, formData);
        if (formData.kb_type == KnbTypeEnum.RAG && data.kb_ids.length > 0) {
            formData.kb_ids = data.kb_ids[0];
        }
        // 确保部分字段为数字类型
        formData.presence_penalty = Number(formData.presence_penalty);
        formData.frequency_penalty = Number(formData.frequency_penalty);
        formData.top_p = Number(formData.top_p);
        formData.temperature = Number(formData.temperature);
    } catch (error) {
        console.error("获取智能体详情失败:", error);
    }
};

/**
 * @description 提交保存智能体数据
 * @param isAutoSave - 是否为自动保存，用于区分手动和自动，以决定是否显示提示
 */
const handleSubmit = async () => {
    await formRef.value?.validate?.();
    const params = {
        ...formData,
        kb_ids:
            formData.kb_type == KnbTypeEnum.RAG
                ? typeof formData.kb_ids === "string"
                    ? [formData.kb_ids]
                    : formData.kb_ids
                : formData.kb_ids,
    };
    formData.id ? await updateAgent(params) : await addAgent(params);
};

// 手动保存 (带锁定)
const { lockFn, isLock } = useLockFn(() => handleSubmit());

onMounted(() => {
    getDetail();
});
</script>

<style scoped></style>
