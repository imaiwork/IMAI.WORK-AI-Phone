<template>
    <div class="h-full flex flex-col p-4">
        <header class="tab-header-card">
            <div class="px-[40px]">
                <ElTabs v-model="currentTab" class="custom-nav-tabs">
                    <ElTabPane v-for="tab in tabs" :key="tab.name" :label="tab.label" :name="tab.name" />
                </ElTabs>
            </div>
        </header>

        <main class="grow min-h-[0] mt-[16px] flex gap-[16px]">
            <section class="flex-1 flex flex-col bg-[#FFFFFF] rounded-[24px] overflow-hidden">
                <div class="toolbar">
                    <div
                        class="group flex items-center gap-2 cursor-pointer text-[#64748B] hover:text-primary transition-all"
                        @click="emit('back')">
                        <div
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 group-hover:bg-[#F0F6FF]">
                            <Icon name="el-icon-ArrowLeft" :size="16" />
                        </div>
                        <span class="text-[14px] font-black">退出编辑</span>
                    </div>

                    <div v-if="![TabName.Skill, TabName.Publish, TabName.Call].includes(currentTab)">
                        <ElButton type="primary" class="save-btn" :loading="isLock" @click="lockFn">
                            立即保存
                        </ElButton>
                    </div>
                </div>

                <div class="grow min-h-[0] relative">
                    <component ref="formRef" v-model="formData" :is="currentComponent" :agent-id="agentId" />
                </div>
            </section>

            <aside
                v-if="currentTab !== TabName.Publish"
                class="w-[420px] flex flex-col bg-[#FFFFFF] rounded-[24px] overflow-hidden shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] border-[1px] border-[transparent]">
                <div class="debug-header">
                    <div class="flex items-center gap-[10px] overflow-hidden">
                        <div class="w-[8px] h-[8px] rounded-full bg-[#10B981] animate-pulse"></div>
                        <span class="font-[900] text-[15px] truncate text-[#0F172A]">{{
                            formData.name || "预览窗口"
                        }}</span>
                    </div>
                    <ElButton class="new-chat-btn" @click="startNewChat">
                        <Icon name="el-icon-Refresh" />
                        <span class="ml-1">清空对话</span>
                    </ElButton>
                </div>

                <div class="grow min-h-[0] bg-[#FDFDFE]">
                    <Chat ref="chatRef" v-model="formData" :agent-id="agentId" />
                </div>
            </aside>
        </main>
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
    kb_type: KnbTypeEnum.VECTOR,
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
    max_tokens: 4096,
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
.tab-header-card {
    @apply bg-[#FFFFFF] rounded-[20px] shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] border-[transparent];
}

.custom-nav-tabs {
    :deep(.el-tabs__header) {
        @apply m-[0] border-[transparent];

        .el-tabs__nav-wrap {
            @apply after:bg-[transparent]; // 隐藏底部原生长线条

            .el-tabs__nav {
                @apply h-[68px];

                .el-tabs__item {
                    @apply h-full flex items-center px-[24px] text-[15px] font-[900] text-[#94A3B8] transition-all;

                    &.is-active {
                        @apply text-[var(--el-color-primary)];
                    }
                    &:hover {
                        @apply text-[var(--el-color-primary)] opacity-[0.8];
                    }
                }

                .el-tabs__active-bar {
                    @apply h-[3px] rounded-[full];
                }
            }
        }
    }
}

.toolbar {
    @apply h-[72px] px-[24px] flex items-center justify-between border-b-[1px] border-[#F1F5F9] shrink-0;
}

.back-action {
    @apply flex items-center gap-[8px] cursor-pointer text-[#64748B] hover:text-[#0F172A] transition-colors;
}

.debug-header {
    @apply h-[72px] px-[20px] flex items-center justify-between border-b-[1px] border-[#F1F5F9] shrink-0;
}

.new-chat-btn {
    @apply rounded-[10px] h-[36px] px-[12px] text-xs font-[900] bg-slate-50 border-[transparent] text-[#64748B] transition-all;

    &:hover {
        @apply bg-[#F1F5F9] text-[#0F172A];
    }
}
</style>
