<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-lg font-medium">搜索测试</div>
                    <div class="text-sm text-tx-secondary mt-1">
                        {{ kbName ? `当前知识库：${kbName}` : "根据查询文本测试知识库召回效果" }}
                    </div>
                </div>
                <el-button @click="router.back()">返回</el-button>
            </div>
        </el-card>

        <div class="mt-4 grid grid-cols-[420px_minmax(0,1fr)] gap-4">
            <div class="flex flex-col gap-4">
                <el-card class="!border-none" shadow="never">
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-medium">源文本测试</span>
                        <el-button type="primary" link @click="openSetting">参数配置</el-button>
                    </div>
                    <el-input
                        v-model="sourceText"
                        type="textarea"
                        resize="none"
                        :rows="6"
                        maxlength="200"
                        show-word-limit
                        placeholder="请输入测试文本" />
                    <el-button
                        type="primary"
                        class="w-full mt-4"
                        :loading="isTestLock"
                        :disabled="!kbId"
                        @click="testLockFn">
                        开始召回测试
                    </el-button>
                </el-card>

                <el-card class="!border-none" shadow="never">
                    <template #header>
                        <span>测试记录</span>
                    </template>
                    <el-table
                        :data="historyPager.lists"
                        v-loading="historyPager.loading"
                        height="420"
                        row-key="id"
                        :row-class-name="getHistoryRowClass"
                        @row-click="handleHistoryTestItem">
                        <el-table-column label="测试文本" min-width="180" show-overflow-tooltip>
                            <template #default="{ row }">
                                {{ row.ask || row.prompt }}
                            </template>
                        </el-table-column>
                        <el-table-column label="时间" prop="create_time" width="160" show-overflow-tooltip />
                    </el-table>
                    <div class="flex justify-end mt-4">
                        <pagination v-model="historyPager" @change="getHistoryLists" />
                    </div>
                </el-card>
            </div>

            <el-card class="!border-none" shadow="never" v-loading="hitTestListLoading">
                <template #header>
                    <div class="flex items-center justify-between">
                        <span>召回结果明细</span>
                        <el-tag type="primary">{{ hitTestList.length }} 段落</el-tag>
                    </div>
                </template>
                <el-scrollbar height="650px" v-if="hitTestList.length">
                    <div class="flex flex-col gap-4 pr-2">
                        <div v-for="(item, index) in hitTestList" :key="index" class="hit-card">
                            <div class="flex items-center justify-between mb-3">
                                <div class="font-medium text-primary">#{{ index + 1 }}</div>
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="text-xs text-tx-secondary truncate max-w-[360px]">
                                        {{ item.source || item.name || "-" }}
                                    </span>
                                    <el-button type="primary" link @click="handleOpenFile(item)">查看源文</el-button>
                                </div>
                            </div>
                            <div class="qa-block is-question">
                                <span class="label">问</span>
                                <span class="content">{{ item.question || item.content }}</span>
                            </div>
                            <div class="qa-block mt-2">
                                <span class="label">答</span>
                                <span class="content">{{ item.answer || "-" }}</span>
                            </div>
                        </div>
                    </div>
                </el-scrollbar>
                <el-empty v-else description="暂无召回结果，请先发起测试" />
            </el-card>
        </div>

        <search-setting
            v-if="showSettingPopup"
            ref="settingPopupRef"
            @close="showSettingPopup = false"
            @confirm="handleSettingConfirm" />
    </div>
</template>

<script setup lang="ts">
import { usePaging } from "@/hooks/usePaging";
import { useLockFn } from "@/hooks/useLockFn";
import feedback from "@/utils/feedback";
import {
    knowKnowledgeVectorHitTest,
    knowKnowledgeVectorHitTestHistoryDetail,
    knowKnowledgeVectorHitTestHistoryList,
} from "@/api/ai_application/knowledge_base/files";
import SearchSetting from "./components/search-setting.vue";

const route = useRoute();
const router = useRouter();
const kbId = computed(() => route.query.id as string);
const kbName = computed(() => (route.query.name as string) || "");
const sourceText = ref("");
const vectorSettingParams = ref<Record<string, any>>({});
const showSettingPopup = ref(false);
const settingPopupRef = shallowRef<InstanceType<typeof SearchSetting>>();
const hitTestList = ref<any[]>([]);
const hitTestListLoading = ref(false);
const currentTestItem = ref<any>(null);

const queryParams = reactive({});

const {
    pager: historyPager,
    getLists: getHistoryLists,
    resetPage: resetHistoryPage,
} = usePaging({
    fetchFun: (params: any) =>
        knowKnowledgeVectorHitTestHistoryList({
            kb_id: kbId.value,
            ...params,
        }),
    params: queryParams,
});

const openSetting = async () => {
    showSettingPopup.value = true;
    await nextTick();
    settingPopupRef.value?.open();
    settingPopupRef.value?.setFormData(vectorSettingParams.value);
};

const handleSettingConfirm = (formData: Record<string, any>) => {
    vectorSettingParams.value = formData;
    showSettingPopup.value = false;
};

const handleTest = async () => {
    if (!kbId.value) return feedback.msgError("缺少知识库ID");
    if (!sourceText.value) return feedback.msgWarning("请输入源文本");
    hitTestListLoading.value = true;
    try {
        const data = await knowKnowledgeVectorHitTest({
            kb_id: kbId.value,
            question: sourceText.value,
            ...vectorSettingParams.value,
        });
        hitTestList.value = data || [];
        currentTestItem.value = null;
        resetHistoryPage();
    } catch (error) {
        feedback.msgError(error as string);
    } finally {
        hitTestListLoading.value = false;
    }
};

const { isLock: isTestLock, lockFn: testLockFn } = useLockFn(handleTest);

const handleHistoryTestItem = async (item: any) => {
    if (currentTestItem.value?.id === item.id) return;
    currentTestItem.value = item;
    hitTestListLoading.value = true;
    try {
        const data = await knowKnowledgeVectorHitTestHistoryDetail({
            tr_id: item.id,
        });
        hitTestList.value = data || [];
        sourceText.value = item.ask || item.prompt || "";
    } catch (error) {
        feedback.msgError(error as string);
    } finally {
        hitTestListLoading.value = false;
    }
};

const getHistoryRowClass = ({ row }: { row: any }) => {
    return currentTestItem.value?.id === row.id ? "current-history-row" : "";
};

const handleOpenFile = (item: any) => {
    const url = item.source_path || item.path || item.url;
    if (!url) return feedback.msgError("文件路径不存在");
    window.open(url, "_blank");
};

onMounted(() => {
    if (kbId.value) getHistoryLists();
});
</script>

<style scoped lang="scss">
.hit-card {
    @apply border border-br rounded p-4 bg-white;
}

.qa-block {
    @apply flex gap-2 rounded bg-page p-3 text-sm leading-6;

    &.is-question {
        @apply bg-primary-light-9;
    }

    .label {
        @apply flex-shrink-0 font-medium text-primary;
    }

    .content {
        @apply whitespace-pre-line break-all;
    }
}

:deep(.current-history-row) {
    td.el-table__cell {
        @apply bg-primary-light-9;
    }
}
</style>
