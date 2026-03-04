<template>
    <div class="h-full flex flex-col gap-3 min-w-[1000px]">
        <div class="flex-shrink-0 bg-white rounded-2xl px-6 py-4 border border-br">
            <h2 class="text-lg font-medium text-[#1E293B]">搜索测试</h2>
            <p class="text-sm text-[#94A3B8] mt-1">根据给定的查询文本测试知识的搜索效果，调优检索参数。</p>
        </div>

        <div class="grow min-h-0 flex gap-3 overflow-hidden">
            <div class="w-[420px] flex flex-col gap-3 h-full">
                <div class="bg-white rounded-2xl border border-br p-5 flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-[#475569] flex items-center gap-2">
                            <Icon name="el-icon-EditPen" />
                            <span class="text-xs font-medium ml-1">源文本测试</span>
                        </span>
                        <ElButton v-if="!isRag" type="primary" link class="!font-medium" @click="openVectorSetting">
                            <Icon name="el-icon-Setting" />
                            <span class="text-xs font-medium ml-1">参数配置</span>
                        </ElButton>
                    </div>

                    <div class="relative">
                        <ElInput
                            v-model="sourceText"
                            placeholder="请输入文本，建议使用简短的陈述句..."
                            type="textarea"
                            resize="none"
                            :maxlength="sourceTextMaxlength"
                            :rows="5"
                            class="custom-textarea" />
                        <div class="absolute bottom-2 right-3 text-[11px] text-[#CBD5E1] font-medium">
                            {{ sourceText.length }} / {{ sourceTextMaxlength }}
                        </div>
                    </div>

                    <div v-if="isRag" class="bg-slate-50 rounded-xl p-4 border border-[#F1F5F9]">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-[#64748B] flex items-center gap-1">
                                相似度阈值
                                <ElTooltip content="设定最低分数标准，只有超过阈值的结果会被召回">
                                    <div class="text-[#CBD5E1]">
                                        <Icon name="el-icon-QuestionFilled" />
                                    </div>
                                </ElTooltip>
                            </span>
                            <span class="text-xs font-black text-primary">{{ rerank_min_score }}</span>
                        </div>
                        <ElSlider v-model="rerank_min_score" :min="0" :max="1" :step="0.01" />
                    </div>

                    <ElButton
                        type="primary"
                        class="!rounded-xl !h-11 !font-black !text-[15px] search-btn"
                        :loading="isTestLock"
                        @click="testLockFn">
                        开始召回测试
                    </ElButton>
                </div>

                <div class="grow min-h-0 bg-white rounded-2xl border border-br flex flex-col overflow-hidden">
                    <div
                        class="px-5 py-4 border-b border-[#F1F5F9] font-medium text-[#475569] flex items-center gap-2 bg-[#f8fafc]/50">
                        <Icon name="el-icon-History" /> 测试记录
                    </div>
                    <div class="grow min-h-0">
                        <ElScrollbar v-if="historyPager.lists.length" @end-reached="handleRecordScrollEndReached">
                            <div class="p-2 flex flex-col gap-1">
                                <div
                                    v-for="(item, index) in historyPager.lists"
                                    :key="index"
                                    class="history-item"
                                    :class="{ 'is-active': currentTestItem?.id === item.id }"
                                    @click="handleHistoryTestItem(item)">
                                    <div class="truncate text-[13px] font-medium text-[#1E293B]">
                                        {{ item.prompt || item.ask }}
                                    </div>
                                    <div class="text-[11px] text-[#94A3B8] mt-1">{{ item.create_time }}</div>
                                </div>
                            </div>
                        </ElScrollbar>
                        <div v-else class="h-full flex items-center justify-center opacity-40">
                            <ElEmpty description="暂无历史" :image-size="60" />
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex-1 min-h-0 bg-white rounded-2xl border border-br flex flex-col overflow-hidden"
                v-loading="hitTestListLoading">
                <div class="px-6 py-4 border-b border-[#F1F5F9] flex items-center justify-between">
                    <div class="flex items-center gap-2 font-black text-[#1E293B]">
                        召回结果明细
                        <span class="px-2 py-0.5 rounded-full bg-[#0065fb]/10 text-primary text-[11px]">
                            {{ hitTestList.length }} 段落
                        </span>
                    </div>
                </div>

                <div class="grow min-h-0 bg-[#f8fafc]/50">
                    <ElScrollbar v-if="hitTestList.length">
                        <div class="p-5 flex flex-col gap-4">
                            <div v-for="(item, index) in hitTestList" :key="index" class="hit-card">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded bg-[#0065fb]/10 flex items-center justify-center text-primary text-[10px] font-black">
                                            #{{ index + 1 }}
                                        </div>
                                        <span class="text-xs font-medium text-[#64748B] truncate max-w-[300px]">
                                            {{ item.source }}
                                        </span>
                                    </div>
                                    <ElButton type="primary" link class="!text-xs" @click="handleOpenFile(item)">
                                        <span class="mr-1">查看源文</span> <Icon name="el-icon-Right" />
                                    </ElButton>
                                </div>

                                <div class="space-y-2">
                                    <div class="qa-item is-q">
                                        <span class="label">问</span>
                                        <span class="content">{{ item.content || item.question }}</span>
                                    </div>
                                    <div class="qa-item is-a">
                                        <span class="label">答</span>
                                        <span class="content">{{ item.answer || "-" }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                    <div v-else class="h-full flex flex-col items-center justify-center grayscale opacity-50">
                        <Icon name="local-icon-empty" :size="80" />
                        <p class="text-sm font-medium text-[#94A3B8] mt-4">暂无召回结果，请在左侧发起测试</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <setting-popup
        v-if="showSettingPopup"
        ref="settingPopupRef"
        @close="showSettingPopup = false"
        @confirm="handleVectorSettingConfirm" />
</template>

<script setup lang="ts">
import {
    knowledgeBaseHitTest,
    knowledgeBaseHitTestHistoryLists,
    knowledgeBaseHitTestHistoryDetail,
    vectorKnowledgeBaseHitTest,
    vectorKnowledgeBaseHitTestHistoryLists,
    vectorKnowledgeBaseHitTestHistoryDetail,
} from "@/api/knowledge_base";
import { KnTypeEnum } from "@/pages/knowledge_base/_enums";
import SettingPopup from "./_components/setting-popup.vue";

const route = useRoute();
const kbId = route.params.id;
const { kn_type, index_id } = toRefs(route.query);

const isRag = computed(() => {
    return kn_type.value == KnTypeEnum.RAG;
});

const isVector = computed(() => kn_type.value == KnTypeEnum.VECTOR);

const sourceText = ref("");
const rerank_min_score = ref(0.5);
const sourceTextMaxlength = 200;

const vectorSettingParams = ref({});

const queryParams = reactive({
    indexid: index_id?.value,
    page_no: 1,
});

const {
    pager: historyPager,
    getLists: getHistoryLists,
    resetPage: resetHistoryPage,
} = usePaging({
    fetchFun: (params: any) =>
        isRag.value
            ? knowledgeBaseHitTestHistoryLists(params)
            : vectorKnowledgeBaseHitTestHistoryLists({ kb_id: kbId, ...params }),
    params: queryParams,
    isScroll: true,
});

const showSettingPopup = ref(false);
const settingPopupRef = shallowRef<InstanceType<typeof SettingPopup>>();

const openVectorSetting = async () => {
    showSettingPopup.value = true;
    await nextTick();
    settingPopupRef.value.open();
    settingPopupRef.value.setFormData(vectorSettingParams.value);
};

const handleVectorSettingConfirm = (formData: any) => {
    vectorSettingParams.value = formData;
    showSettingPopup.value = false;
};

const hitTestList = ref([]);
const hitTestListLoading = ref(false);
const handleTest = async () => {
    if (sourceText.value.length === 0) {
        feedback.msgWarning("请输入源文本");
        return;
    }
    hitTestListLoading.value = true;
    try {
        if (isRag.value) {
            const data = await knowledgeBaseHitTest({
                prompt: sourceText.value,
                indexid: index_id.value,
                rerank_min_score: rerank_min_score.value,
            });
            hitTestList.value = data;
            resetHistoryPage();
        } else {
            const data = await vectorKnowledgeBaseHitTest({
                kb_id: kbId,
                question: sourceText.value,
                ...vectorSettingParams.value,
            });
            hitTestList.value = data;
            resetHistoryPage();
        }
    } catch (error) {
        feedback.msgError(error);
    } finally {
        hitTestListLoading.value = false;
    }
};

const { isLock: isTestLock, lockFn: testLockFn } = useLockFn(handleTest);

const currentTestItem = ref<any>(null);
const handleHistoryTestItem = async (item: any) => {
    if (currentTestItem.value?.id == item.id) return;
    currentTestItem.value = item;
    try {
        hitTestListLoading.value = true;
        if (isRag.value) {
            const data = await knowledgeBaseHitTestHistoryDetail({ id: item.id, page_type: 0 });
            hitTestList.value = data.lists;
            sourceText.value = item.prompt;
        }
        if (isVector.value) {
            const data = await vectorKnowledgeBaseHitTestHistoryDetail({
                tr_id: item.id,
            });
            hitTestList.value = data;
            sourceText.value = item.ask;
        }
    } catch (error) {
        feedback.msgError(error);
    } finally {
        hitTestListLoading.value = false;
    }
};

const handleRecordScrollEndReached = async (e: any) => {
    if (e == "bottom") {
        if (!historyPager.isLoad || historyPager.loading) return;
        queryParams.page_no++;
        await getHistoryLists();
    }
};

const handleOpenFile = (item: any) => {
    if (isRag.value) {
        const { metadata } = item;
        const { file_path } = isJson(metadata) ? JSON.parse(metadata) : {};
        if (file_path) {
            window.open(file_path, "_blank");
        } else {
            feedback.msgError("文件路径不存在");
        }
    } else {
        const { source_path } = item;
        if (source_path) {
            window.open(source_path, "_blank");
        } else {
            feedback.msgError("文件路径不存在");
        }
    }
};

getHistoryLists();
</script>

<style scoped lang="scss">
.search-btn {
    @apply bg-primary border-[none] transition-all;
    &:hover {
        @apply opacity-90  shadow-[0_8px_20px_-6px_rgba(#0065fb,0.4)];
    }
}

.history-item {
    @apply p-3 rounded-xl border border-[transparent] cursor-pointer transition-all;
    &:hover {
        @apply bg-[#F1F5F9];
    }
    &.is-active {
        @apply bg-[#0065fb]/[0.02] border-[#0065fb]/[0.02];
    }
}

.hit-card {
    @apply bg-white p-4 rounded-2xl border border-br transition-all;
    &:hover {
        @apply border-primary;
        @apply shadow-[0_4px_12px_rgba(0,0,0,0.03)];
    }
}

.qa-item {
    @apply flex gap-2 p-3 rounded-lg;
    &.is-q {
        @apply bg-[#F0F6FF];
        .label {
            @apply text-primary font-black;
        }
    }
    &.is-a {
        @apply bg-slate-50 border border-[#F1F5F9];
        .label {
            @apply text-[#94A3B8] font-black;
        }
    }
    .content {
        @apply text-xs leading-5 text-[#475569] break-all;
    }
}

:deep(.el-scrollbar__thumb) {
    @apply bg-[#e2e8f0];
}
</style>
