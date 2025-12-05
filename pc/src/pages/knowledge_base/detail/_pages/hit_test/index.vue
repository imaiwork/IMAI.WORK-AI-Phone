<template>
    <div class="h-full bg-white rounded-[20px] px-[30px] flex flex-col">
        <div class="flex-shrink-0 mt-[30px]">
            <div>搜索测试</div>
            <div class="text-[#00000080] mt-[6px]">根据给定的查询文本测试知识的搜索效果。</div>
        </div>
        <div class="grow min-h-0 mt-[30px] overflow-x-scroll dynamic-scroller pb-5">
            <div class="flex gap-x-5 min-w-[800px] h-full">
                <div class="h-full flex flex-col flex-1 gap-y-6">
                    <div class="px-[14px] bg-[#F6F6F6] border border-[#E5E5E5] rounded-xl">
                        <div class="flex items-center justify-between h-[50px] border-b border-[#0000000d]">
                            <div>源文本</div>
                            <ElButton
                                color="#F6F6F6"
                                class="!border-[#0000001a]"
                                v-if="!isRag"
                                @click="openVectorSetting"
                                >向量检索</ElButton
                            >
                        </div>
                        <div class="py-[14px]">
                            <div>
                                <ElInput
                                    v-model="sourceText"
                                    placeholder="请输入文本，建议使用简短的陈述句"
                                    type="textarea"
                                    resize="none"
                                    :maxlength="sourceTextMaxlength"
                                    :rows="6" />
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="text-[#00000080]">{{ sourceText.length }} / {{ sourceTextMaxlength }}</div>
                                <ElButton type="primary" class="w-[84px]" :loading="isTestLock" @click="testLockFn"
                                    >测试</ElButton
                                >
                            </div>
                        </div>
                    </div>
                    <div v-if="isRag" class="px-4">
                        <div>相似度阈值</div>
                        <ElSlider v-model="rerank_min_score" :min="0" :max="1" :step="0.01" size="small" show-input />
                        <div class="absolute -top-[33px] left-[90px]">
                            <ElTooltip popper-class="w-[200px]">
                                <div class="absolute top-0 right-0">
                                    <Icon name="el-icon-QuestionFilled" color="#858585" />
                                </div>
                                <template #content>
                                    <div>
                                        设定最低分数标准，只有达到或超过这个阈值的检索结果才会被考虑用于后续的排序和生成过程
                                    </div>
                                </template>
                            </ElTooltip>
                        </div>
                    </div>
                    <div class="grow min-h-0 flex flex-col">
                        <div class="font-bold">记录</div>
                        <div class="grow min-h-0 mt-6 bg-[#F6F6F6] border border-[#E5E5E5] rounded-xl flex flex-col">
                            <div
                                class="flex-shrink-0 h-[50px] border-b border-[#0000000d] flex justify-between items-center px-[14px]">
                                <span>测试文本</span>
                                <span>时间</span>
                            </div>
                            <div class="grow min-h-0">
                                <ElScrollbar
                                    @end-reached="handleRecordScrollEndReached"
                                    v-if="historyPager.lists.length">
                                    <div>
                                        <div
                                            class="flex justify-between items-center px-[14px] h-[50px] border-b border-[#0000000d] cursor-pointer active:bg-primary-light-9"
                                            :class="{ 'bg-[rgba(255,255,255,0.3)]': index % 2 === 0 }"
                                            v-for="(item, index) in historyPager.lists"
                                            :key="item"
                                            @click="handleHistoryTestItem(item)">
                                            <div class="line-clamp-1">{{ item.prompt || item.ask }}</div>
                                            <div class="flex-shrink-0">{{ item.create_time }}</div>
                                        </div>
                                    </div>
                                </ElScrollbar>
                                <div v-else class="flex items-center justify-center h-full">
                                    <ElEmpty description="暂无历史数据" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="flex-1 bg-[#F6F6F6] rounded-xl border border-[#E5E5E5] flex flex-col py-[15px]"
                    v-loading="hitTestListLoading">
                    <div class="px-[15px]">{{ hitTestList.length }} 个段落搜索</div>
                    <div class="grow min-h-0 mt-[15px]">
                        <ElScrollbar v-if="hitTestList.length">
                            <div class="flex flex-col gap-y-3 px-[15px]">
                                <div
                                    class="bg-white rounded-md border border-[#efefef]"
                                    v-for="(item, index) in hitTestList"
                                    :key="index">
                                    <div class="px-[14px] py-[12px]">
                                        <div
                                            class="line-clamp-4 text-[11px] leading-5 mt-[6px] break-all text-[#00000080]">
                                            问：{{ item.content || item.question }}
                                        </div>
                                        <div
                                            class="line-clamp-4 text-[11px] leading-5 mt-[6px] break-all text-[#00000080]">
                                            答：{{ item.answer || "-" }}
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center gap-x-3 px-[13px] border-t border-[#0000000d] h-[50px]">
                                        <div class="w-5 h-5 rounded bg-[#0000000d] flex items-center justify-center">
                                            <Icon name="local-icon-upload2"></Icon>
                                        </div>
                                        <div class="flex-1 line-clamp-1">{{ item.source }}</div>
                                        <ElButton type="primary" class="w-[80px]" @click="handleOpenFile(item)"
                                            >打开</ElButton
                                        >
                                    </div>
                                </div>
                            </div>
                        </ElScrollbar>
                        <div v-else class="flex items-center justify-center h-full">
                            <ElEmpty description="暂无召回结果" />
                        </div>
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

const handleRecordScrollEndReached = (e: any) => {
    if (e == "bottom" && historyPager.isLoad && !historyPager.loading) {
        queryParams.page_no++;
        getHistoryLists();
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
:deep(.el-textarea__inner) {
    background-color: transparent;
    box-shadow: none;
}
</style>
