<template>
    <div class="h-full flex flex-col p-4">
        <!-- 顶部Tabs导航 -->
        <div class="bg-white rounded-[20px] px-[50px]">
            <ElTabs v-model="currentTab">
                <ElTabPane v-for="tab in tabs" :key="tab.name" :label="tab.label" :name="tab.name"> </ElTabPane>
            </ElTabs>
        </div>
        <!-- 主内容区 -->
        <div class="grow min-h-0 bg-white rounded-[20px] mt-4 flex">
            <!-- 左侧表单设置区域 -->
            <div class="flex-1 flex flex-col w-full">
                <div
                    class="px-[14px] h-[72px] flex items-center justify-between flex-shrink-0 border-b-[1px] border-[#0000000d]">
                    <div class="flex items-center gap-2 cursor-pointer" @click="emit('back')">
                        <Icon name="el-icon-ArrowLeft"></Icon>
                        <div>返回</div>
                    </div>
                    <!-- 非特定Tab下显示保存按钮 -->
                    <div v-if="![TabName.Skill, TabName.Publish, TabName.Call].includes(currentTab)">
                        <ElButton type="primary" class="!rounded-full !w-[68px]" :loading="isLock" @click="lockFn">
                            保存
                        </ElButton>
                    </div>
                </div>
                <!-- 动态组件渲染 -->
                <div class="grow min-h-0">
                    <component ref="formRef" v-model="formData" :is="currentComponent" :agent-id="agentId" />
                </div>
            </div>
            <!-- 右侧聊天调试窗口 -->
            <div
                class="w-[400px] flex flex-col border-l-[1px] border-[#0000000d]"
                v-if="![TabName.Publish].includes(currentTab)">
                <div
                    class="h-[72px] flex items-center gap-x-2 justify-between flex-shrink-0 border-b-[1px] border-[#0000000d] px-5">
                    <div class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap">{{ formData.name }}</div>
                    <ElButton @click="startNewChat">新建对话</ElButton>
                </div>
                <div class="grow min-h-0">
                    <Chat ref="chatRef" v-model="formData" :agent-id="agentId" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getAgentDetail, updateAgent, addAgent } from "@/api/agent";
import { KnbTypeEnum } from "@/pages/knowledge_base/_enums";
import { useDebounceFn } from "@vueuse/core";
import { Agent, ModeTypeEnum } from "../_enums";
import BaseSetting from "../_components/base-setting/index.vue";
import KnbSetting from "../_components/knb-setting/index.vue";
import HumanizeSetting from "../_components/humanize-setting/index.vue";
import InterfaceSetting from "../_components/interface-setting/index.vue";
import PublishSetting from "../_components/publish-setting/index.vue";
import SkillSetting from "../_components/skill-setting/index.vue";
import ReplySetting from "../_components/reply-setting/index.vue";
import Chat from "../_components/chat/index.vue";

/**
 * @description 智能体编辑页面
 * @summary 集成了多个配置项Tab，并提供实时聊天调试功能。
 */

const emit = defineEmits<{ (e: "back"): void }>();
const route = useRoute();

const agentId = Number(route.query.id);

// Tab定义
enum TabName {
    Base = "base",
    Knb = "knb",
    Humanize = "humanize",
    Interface = "interface",
    Publish = "publish",
    Skill = "skill",
    Call = "call",
}

const currentTab = ref(TabName.Base);
const tabs = ref([
    { label: "人设", name: TabName.Base, component: markRaw(BaseSetting) },
    { label: "知识库", name: TabName.Knb, component: markRaw(KnbSetting) },
    { label: "拟人化", name: TabName.Humanize, component: markRaw(HumanizeSetting) },
    { label: "界面配置", name: TabName.Interface, component: markRaw(InterfaceSetting) },
    { label: "发布", name: TabName.Publish, component: markRaw(PublishSetting) },
    { label: "技能", name: TabName.Skill, component: markRaw(SkillSetting) },
    { label: "调用设置", name: TabName.Call, component: markRaw(ReplySetting) },
]);

// 智能体表单数据
const formData = reactive<Agent>({
    id: agentId,
    kb_type: KnbTypeEnum.RAG,
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
    top_p: 0.9,
    temperature: 0.6,
    presence_penalty: 0.2,
    frequency_penalty: 0.2,
    logprobs: 0,
    top_logprobs: 10,
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
    mode_type: ModeTypeEnum.BALANCE,
});

const formRef = ref();
const chatRef = shallowRef<InstanceType<typeof Chat>>();
const currentComponent = computed(() => tabs.value.find((tab) => tab.name === currentTab.value)?.component);

/**
 * @description 在聊天窗口开始一个新对话
 */
const startNewChat = async () => {
    await nextTick();
    chatRef.value?.startNewChat();
};

/**
 * @description 获取智能体详情
 */
const getDetail = async () => {
    if (!agentId) return;
    try {
        const data = await getAgentDetail({ id: agentId });
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
const handleSubmit = async (isAutoSave?: boolean) => {
    try {
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
        if (!isAutoSave) {
            feedback.msgSuccess(`${formData.id ? "编辑" : "添加"}成功`);
        }
    } catch (error: any) {
        // 自动保存时，不提示错误
        if (!isAutoSave) {
            feedback.msgError(typeof error === "string" ? error : "请填写相关信息");
        }
    }
};

// 防抖自动保存 (300ms)
const throttleSave = useDebounceFn(() => handleSubmit(true), 300);

// 手动保存 (带锁定)
const { lockFn, isLock } = useLockFn(() => handleSubmit(false));

// 侦听表单数据变化，触发自动保存
watch(
    formData,
    () => {
        throttleSave();
    },
    {
        deep: true, // 深度侦听
    }
);

onMounted(() => {
    getDetail();
});
</script>

<style scoped lang="scss">
:deep(.el-tabs) {
    .el-tabs__header {
        margin-bottom: 0;
        .el-tabs__nav-wrap {
            .el-tabs__nav {
                @apply h-[68px];
                .el-tabs__item {
                    @apply h-full;
                }
            }
            &::after {
                display: none;
            }
        }
    }
}
</style>
