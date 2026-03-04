<template>
    <popup
        ref="popupRef"
        width="850px"
        top="10vh"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false">
        <div class="p-4">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-[22px] font-black text-[#1E293B] tracking-tight">内容文案库</h2>
                    <p class="text-xs text-[#94A3B8] font-medium mt-1">从库中挑选优质标题与正文描述</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex bg-[#F1F5F9] p-1 rounded-xl">
                        <button
                            @click="isViewSelected = false"
                            :class="!isViewSelected ? 'bg-white  text-[#6366F1]' : 'text-[#64748B]'"
                            class="px-4 py-1.5 rounded-lg text-xs font-medium transition-all">
                            全部素材
                        </button>
                        <button
                            @click="handleViewSelected"
                            :class="isViewSelected ? 'bg-white  text-[#6366F1]' : 'text-[#64748B]'"
                            class="px-4 py-1.5 rounded-lg text-xs font-medium transition-all">
                            已选 ({{ titleCount + contentCount }})
                        </button>
                    </div>
                    <div
                        class="w-8 h-8 rounded-full bg-[#F1F5F9] flex items-center justify-center cursor-pointer hover:bg-[#E2E8F0] transition-colors"
                        @click="close">
                        <close-btn class="w-4 h-4 text-[#64748B]"></close-btn>
                    </div>
                </div>
            </div>

            <div class="mb-6" v-show="!isViewSelected">
                <ElSelect
                    v-model="selectCopywriting"
                    class="custom-select !w-[320px]"
                    placeholder="请选择文案库分类"
                    @change="handleChangeCopywriting">
                    <ElOption v-for="item in copywritingLists" :label="item.name" :value="item.id" :key="item.id">
                        <div class="flex justify-between items-center">
                            <span>{{ item.name }}</span>
                            <span class="text-[10px] text-[#94A3B8]">{{ item.title?.length || 0 }}条</span>
                        </div>
                    </ElOption>
                </ElSelect>
            </div>

            <div class="flex gap-x-6 h-[380px]">
                <div class="flex-[2] flex flex-col bg-white border border-br rounded-[24px] overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#F1F5F9] flex justify-between items-center bg-slate-50">
                        <span class="font-medium text-[#334155] text-sm flex items-center gap-2">
                            <span class="w-1.5 h-4 bg-[#6366F1] rounded-full"></span> 备选标题
                        </span>
                        <ElButton
                            link
                            type="primary"
                            class="!text-xs font-medium"
                            @click="chooseAll(CopywritingType.Title)"
                            >全选</ElButton
                        >
                    </div>
                    <ElScrollbar class="flex-1">
                        <div class="p-4 flex flex-col gap-y-3" v-if="getCopywritingLibraryContent.title.length > 0">
                            <div
                                v-for="(item, index) in getCopywritingLibraryContent.title"
                                :key="index"
                                :class="
                                    item.checked ? 'bg-[#EEF2FF] border-[#6366F1]' : 'bg-slate-50 border-transparent'
                                "
                                class="group flex items-center gap-x-3 px-2 py-1 rounded-xl border border-br transition-all hover:border-[#6366F1]/30 cursor-pointer"
                                @click="choose(CopywritingType.Title, item)">
                                <div class="flex-1">
                                    <ElInput
                                        v-model="item.content"
                                        maxlength="20"
                                        placeholder="请输入标题"
                                        clearable
                                        @click.stop />
                                </div>
                                <div
                                    class="w-5 h-5 flex items-center justify-center transition-transform group-active:scale-90">
                                    <Icon
                                        name="local-icon-success_fill"
                                        :size="20"
                                        :color="item.checked ? '#6366F1' : '#CBD5E1'"></Icon>
                                </div>
                            </div>
                        </div>
                        <div class="h-full flex items-center justify-center pt-20" v-else>
                            <ElEmpty description="暂无标题" :image-size="80"></ElEmpty>
                        </div>
                    </ElScrollbar>
                </div>

                <div class="flex-[3] flex flex-col bg-white border border-br rounded-[24px] overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#F1F5F9] flex justify-between items-center bg-slate-50">
                        <span class="font-medium text-[#334155] text-sm flex items-center gap-2">
                            <span class="w-1.5 h-4 bg-[#10B981] rounded-full"></span> 正文描述
                        </span>
                        <ElButton
                            link
                            type="primary"
                            class="!text-xs font-medium"
                            @click="chooseAll(CopywritingType.Described)"
                            >全选</ElButton
                        >
                    </div>
                    <ElScrollbar class="flex-1">
                        <div class="p-4 flex flex-col gap-y-4" v-if="getCopywritingLibraryContent.described.length > 0">
                            <div
                                v-for="(item, index) in getCopywritingLibraryContent.described"
                                :key="index"
                                :class="
                                    item.checked ? 'bg-[#EEF2FF] border-[#6366F1]' : 'bg-slate-50 border-transparent'
                                "
                                class="group relative flex flex-col gap-y-3 p-4 rounded-2xl border border-br transition-all"
                                @click="choose(CopywritingType.Described, item)">
                                <div class="absolute right-1 top-1 transition-transform group-active:scale-90 z-[888]">
                                    <Icon
                                        name="local-icon-success_fill"
                                        :size="20"
                                        :color="item.checked ? '#6366F1' : '#cbd5e1'"></Icon>
                                </div>

                                <ElInput
                                    v-model="item.content"
                                    class="custom-textarea-input"
                                    type="textarea"
                                    placeholder="请输入正文描述"
                                    resize="none"
                                    maxlength="800"
                                    show-word-limit
                                    :rows="6"
                                    @click.stop></ElInput>

                                <div class="flex flex-wrap gap-2 pt-2 border-t border-[#00000005]">
                                    <div
                                        v-for="(topic, t_index) in item.topic"
                                        :key="t_index"
                                        class="group/tag relative flex items-center bg-white border border-br px-2 py-1 rounded-lg">
                                        <span class="text-[#6366F1] font-medium text-xs mr-1">#</span>
                                        <input
                                            v-model="item.topic[t_index]"
                                            class="bg-transparent border-none outline-none text-[11px] w-[70px] text-[#64748B]"
                                            placeholder="输入话题"
                                            @click.stop />
                                        <div
                                            class="ml-1 w-3 h-3 bg-[#94A3B8] text-white rounded-full flex items-center justify-center cursor-pointer opacity-0 group-hover/tag:opacity-100 transition-opacity"
                                            @click.stop="handleDeleteTopic(index, t_index)">
                                            <Icon name="local-icon-close" :size="8"></Icon>
                                        </div>
                                    </div>
                                    <button
                                        v-if="item.topic?.length < 5"
                                        class="px-3 py-1 rounded-lg border border-dashed border-[#CBD5E1] text-[#94A3B8] text-[11px] hover:border-[#6366F1] hover:text-[#6366F1] transition-all"
                                        @click.stop="handleAddTopic(index)">
                                        + 添加话题
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="h-full flex items-center justify-center pt-20" v-else>
                            <ElEmpty description="暂无正文" :image-size="80"></ElEmpty>
                        </div>
                    </ElScrollbar>
                </div>
            </div>

            <div class="mt-8 flex justify-center">
                <ElButton type="primary" class="w-[280px] !h-[52px] !rounded-xl" @click="confirm">
                    <span class="font-medium">确定选择材料</span>
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
/* ... 脚本部分保持逻辑不变，仅确保引用了相应的 API 和 Enums ... */
import { getCopywritingLibraryList } from "@/api/matrix";
import { CopywritingTypeEnum } from "@/pages/app/matrix/_enums";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "confirm", value: any): void;
}>();

enum CopywritingType {
    Title = "title",
    Described = "described",
}

const copywritingLists = ref<any[]>([]);
const chooseValue = ref<{ title: any[]; described: any[] }>({ title: [], described: [] });
const selectCopywriting = ref();
const isViewSelected = ref(false);

const getCopywritingLibraryContent = computed(() => {
    if (isViewSelected.value) return chooseValue.value;
    const data = copywritingLists.value.find((item) => item.id == selectCopywriting.value) || {};
    return { title: data.title || [], described: data.described || [] };
});

const titleCount = computed(() => chooseValue.value.title.length);
const contentCount = computed(() => chooseValue.value.described.length);

const handleChangeCopywriting = () => {
    isViewSelected.value = false;
};

const getCopywritingLibraryLists = async () => {
    const { lists } = await getCopywritingLibraryList({ page_size: 9999, copywriting_type: CopywritingTypeEnum.TITLE });
    if (lists.length > 0) {
        lists.forEach((item) => {
            item.title = item.title.map((t: any) => ({ ...t, checked: false }));
            item.described = item.described.map((d: any) => ({ ...d, checked: false }));
        });
        copywritingLists.value = lists;
        selectCopywriting.value = lists[0].id;
    }
};

const handleAddTopic = (index: number) => {
    const { described } = getCopywritingLibraryContent.value;
    if (!described[index].topic) described[index].topic = [];
    described[index].topic.push("");
};

const handleDeleteTopic = (index: number, t_index: number) => {
    const { described } = getCopywritingLibraryContent.value;
    if (described[index].topic) described[index].topic.splice(t_index, 1);
};

const handleViewSelected = () => {
    isViewSelected.value = true;
};

const setChooseValue = () => {
    const allTitle = copywritingLists.value.flatMap((item) => item.title).filter((t) => t.checked);
    const allDesc = copywritingLists.value.flatMap((item) => item.described).filter((d) => d.checked);
    chooseValue.value = { title: allTitle, described: allDesc };
};

const choose = (type: CopywritingType, item: any) => {
    item.checked = !item.checked;
    setChooseValue();
};

const chooseAll = (type: CopywritingType) => {
    const targetList = getCopywritingLibraryContent.value[type];
    const shouldSelectAll = targetList.some((item: any) => !item.checked);
    targetList.forEach((item: any) => {
        item.checked = shouldSelectAll;
    });
    setChooseValue();
};

const popupRef = ref();
const open = () => {
    popupRef.value.open();
    copywritingLists.value = [];
    chooseValue.value = { title: [], described: [] };
    selectCopywriting.value = undefined;
    isViewSelected.value = false;
    getCopywritingLibraryLists();
};

const close = () => {
    emit("close");
    popupRef.value.close();
};

const confirm = () => {
    emit("confirm", {
        titleList: chooseValue.value.title.map((item) => ({ content: item.content })),
        contentList: chooseValue.value.described.map((item) => ({ content: item.content, topic: item.topic })),
    });
    close();
};

defineExpose({ open });
</script>

<style scoped lang="scss">
/* 深度自定义 Element 样式 */
:deep(.custom-select) {
    .el-input__wrapper {
        background-color: #f1f5f9;
        box-shadow: none !important;
        border-radius: 14px;
        padding: 4px 12px;
    }
    .el-input__inner {
        font-weight: 600;
        color: #1e293b;
    }
}

/* 自定义滚动条样式 */
:deep(.el-scrollbar__bar.is-vertical) {
    width: 4px;
}
:deep(.el-scrollbar__thumb) {
    background-color: #cbd5e1 !important;
}

:deep(.el-input__wrapper) {
    box-shadow: none;
    background-color: transparent;
}

:deep(.custom-textarea-input) {
    .el-textarea__inner {
        background-color: #f9fafb;
        border: 1px solid #f3f4f6;
        border-radius: 16px;
        padding: 16px;
        font-size: 13px;
        line-height: 1.6;
        color: #374151;
        box-shadow: none;
        transition: all 0.3s;
        &:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.05);
        }
    }
    .el-input__count {
        background: transparent;
        font-weight: bold;
        bottom: 12px;
        right: 16px;
    }
}
</style>
