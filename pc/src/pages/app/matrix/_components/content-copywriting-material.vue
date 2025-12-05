<template>
    <popup
        ref="popupRef"
        width="718px"
        confirm-button-text=""
        cancel-button-text=""
        style="background-color: var(--app-bg-color-3); border: 1px solid var(--app-border-color-1)"
        :show-close="false">
        <div class="-my-4">
            <div class="absolute w-6 h-6 top-4 right-4" @click="close">
                <close-btn></close-btn>
            </div>
            <div class="text-white text-[20px] font-bold">内容文案库</div>
            <div class="mt-[17px]">
                <ElSelect
                    v-model="selectCopywriting"
                    class="!w-[314px] !h-11"
                    placeholder="请选择"
                    popper-class="dark-select-popper"
                    clearable
                    filterable
                    :show-arrow="false"
                    @change="handleChangeCopywriting">
                    <ElOption v-for="item in copywritingLists" :label="item.name" :value="item.id" :key="item.id">
                    </ElOption>
                </ElSelect>
            </div>
            <div class="flex justify-between items-center gap-x-2 mt-4">
                <div class="flex gap-x-2">
                    <div class="text-[#ffffff80] text-[11px]">
                        已选择标题<span class="text-white">（{{ titleCount }}）</span>
                    </div>
                    <div class="text-[#ffffff80] text-[11px]">
                        已选择正文<span class="text-white">（{{ contentCount }}）</span>
                    </div>
                </div>
                <ElButton link class="!text-[#ffffff80] !text-[11px]" @click="handleViewSelected">{{
                    isViewSelected ? "隐藏已选择" : "查看已选择"
                }}</ElButton>
            </div>
            <div class="flex gap-x-[10px] mt-[18px]">
                <div class="basis-[45%] flex-shrink-0 rounded-xl border border-app-border-1 bg-app-bg-2">
                    <div
                        class="flex items-center justify-between h-11 border-[0] border-b-[1px] border-app-border-1 px-[10px]">
                        <div class="text-white">标题</div>
                        <ElButton
                            class="!border-app-border-2"
                            color="#1f1f1f"
                            size="small"
                            @click="chooseAll(CopywritingType.Title)"
                            >全选</ElButton
                        >
                    </div>
                    <div class="h-[350px]">
                        <ElScrollbar class="h-full" v-if="getCopywritingLibraryContent.title.length > 0">
                            <div class="p-3 flex flex-col gap-y-2">
                                <div
                                    class="flex items-center gap-x-3"
                                    v-for="(item, index) in getCopywritingLibraryContent.title"
                                    :key="index">
                                    <div class="flex-1">
                                        <ElInput
                                            v-model="item.content"
                                            class="!h-11"
                                            clearable
                                            maxlength="20"
                                            show-word-limit
                                            placeholder="-"
                                            input-style="font-size: 11px" />
                                    </div>
                                    <div
                                        class="w-4 h-4 rounded-full cursor-pointer"
                                        @click="choose(CopywritingType.Title, item)">
                                        <Icon
                                            name="local-icon-success_fill"
                                            :size="16"
                                            :color="item.checked ? 'var(--color-primary)' : '#ffffff1a'"></Icon>
                                    </div>
                                </div>
                            </div>
                        </ElScrollbar>
                        <div class="h-full flex items-center justify-center" v-else>
                            <ElEmpty description="暂无数据" :image-size="100"></ElEmpty>
                        </div>
                    </div>
                </div>
                <div class="flex-auto rounded-xl border border-app-border-1 bg-app-bg-2">
                    <div
                        class="flex items-center justify-between h-11 border-[0] border-b-[1px] border-app-border-1 px-[10px]">
                        <div class="text-white">正文描述</div>
                        <ElButton
                            class="!border-app-border-2"
                            color="#1f1f1f"
                            size="small"
                            @click="chooseAll(CopywritingType.Described)"
                            >全选</ElButton
                        >
                    </div>
                    <div class="h-[350px]">
                        <ElScrollbar class="h-full" v-if="getCopywritingLibraryContent.described.length > 0">
                            <div class="p-3 flex flex-col gap-y-3">
                                <div
                                    class="flex items-center gap-x-3"
                                    v-for="(item, index) in getCopywritingLibraryContent.described"
                                    :key="index">
                                    <div class="flex-1">
                                        <div>
                                            <ElInput
                                                v-model="item.content"
                                                type="textarea"
                                                maxlength="800"
                                                show-word-limit
                                                :rows="6" />
                                        </div>
                                        <div class="relative mt-4 flex gap-x-2 items-end">
                                            <div class="flex-1 flex flex-wrap gap-2">
                                                <div
                                                    v-for="(topic, t_index) in item.topic"
                                                    :key="t_index"
                                                    class="relative text-[11px] rounded-md shadow-[0_0_0_1px_rgba(255,255,255,0.1)]"
                                                    :class="{ 'bg-app-bg-4': topic }">
                                                    <ElInput
                                                        v-model="item.topic[t_index]"
                                                        class="h-[26px] !w-[78px]"
                                                        placeholder="#请输入话题"
                                                        input-style="font-size: 11px;height: 100%"></ElInput>
                                                    <div
                                                        class="absolute -top-2 -right-2 w-4 h-4 rounded-full flex items-center justify-center bg-[#FF3C26] cursor-pointer"
                                                        @click="handleDeleteTopic(index, t_index)">
                                                        <Icon name="local-icon-close" color="#ffffff"></Icon>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="">
                                                <ElButton
                                                    type="primary"
                                                    class="!h-[26px]"
                                                    @click="handleAddTopic(index)"
                                                    >添加话题</ElButton
                                                >
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="w-4 h-4 rounded-full cursor-pointer"
                                        @click="choose(CopywritingType.Described, item)">
                                        <Icon
                                            name="local-icon-success_fill"
                                            :size="16"
                                            :color="item.checked ? 'var(--color-primary)' : '#ffffff1a'"></Icon>
                                    </div>
                                </div>
                            </div>
                        </ElScrollbar>
                        <div class="h-full flex items-center justify-center" v-else>
                            <ElEmpty description="暂无数据" :image-size="100"></ElEmpty>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-[18px] flex justify-center">
                <ElButton type="primary" class="!rounded-full w-[246px] !h-[50px]" @click="confirm">确定</ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getCopywritingLibraryList } from "@/api/redbook";
import { CopywritingTypeEnum } from "@/pages/app/matrix/_enums";

// 定义组件emit事件
const emit = defineEmits<{
    (e: "close"): void; // 关闭弹窗事件
    (e: "confirm", value: any): void; // 确认事件，携带选中的文案
}>();

// 定义文案类型枚举
enum CopywritingType {
    Title = "title", // 标题
    Described = "described", // 正文描述
}

// 响应式状态：存储所有文案库列表
const copywritingLists = ref<any[]>([]);

// 响应式状态：存储用户最终选中的标题和正文
const chooseValue = ref<{
    title: any[];
    described: any[];
}>({
    title: [],
    described: [],
});

// 响应式状态：当前在下拉框中选中的文案库ID
const selectCopywriting = ref();

// 计算属性：根据是否“查看已选择”来决定显示的内容
const getCopywritingLibraryContent = computed(() => {
    // 如果开启了“查看已选择”，则只显示已选中的内容
    if (isViewSelected.value) {
        return {
            title: chooseValue.value.title,
            described: chooseValue.value.described,
        };
    }
    // 否则，显示当前选中的文案库的完整内容
    const data = copywritingLists.value.find((item) => item.id == selectCopywriting.value) || {};
    return {
        title: data.title || [],
        described: data.described || [],
    };
});

// 计算属性：已选择的标题数量
const titleCount = computed(() => {
    return chooseValue.value.title.length;
});

// 计算属性：已选择的正文数量
const contentCount = computed(() => {
    return chooseValue.value.described.length;
});

// 处理文案库下拉框变化事件
const handleChangeCopywriting = () => {
    // 切换文案库时，退出“查看已选择”模式
    isViewSelected.value = false;
};

// 异步方法：获取内容文案库列表
const getCopywritingLibraryLists = async () => {
    const { lists } = await getCopywritingLibraryList({ page_size: 9999, copywriting_type: CopywritingTypeEnum.TITLE });
    if (lists.length > 0) {
        // 为每个文案条目添加 `checked` 属性，用于标记选中状态
        lists.forEach((item) => {
            item.title = item.title.map((t: any) => ({ ...t, checked: false }));
            item.described = item.described.map((d: any) => ({ ...d, checked: false }));
        });
        copywritingLists.value = lists;
        // 默认选中第一个文案库
        selectCopywriting.value = lists[0].id;
    }
};

// 处理添加话题
const handleAddTopic = (index: number) => {
    const { described } = getCopywritingLibraryContent.value;
    if (!described[index].topic) {
        described[index].topic = [];
    }
    described[index].topic.push("");
};

// 处理删除话题
const handleDeleteTopic = (index: number, t_index: number) => {
    const { described } = getCopywritingLibraryContent.value;
    if (described[index].topic) {
        described[index].topic.splice(t_index, 1);
    }
};

// 响应式状态：是否只查看已选择的文案
const isViewSelected = ref(false);
// 切换“查看已选择”状态
const handleViewSelected = () => {
    isViewSelected.value = !isViewSelected.value;
};

// 核心方法：更新所有选中项的集合
const setChooseValue = () => {
    // 从所有文案库中筛选出被选中的标题
    const allTitle = copywritingLists.value.flatMap((item) => item.title).filter((t) => t.checked);
    // 从所有文案库中筛选出被选中的正文
    const allDesc = copywritingLists.value.flatMap((item) => item.described).filter((d) => d.checked);

    // 更新最终选中的值
    chooseValue.value = {
        title: allTitle,
        described: allDesc,
    };
};

// 处理单个文案项的选中/取消选中
const choose = (type: CopywritingType, item: any) => {
    item.checked = !item.checked;
    // 每次选择后，重新计算所有选中的项
    setChooseValue();
};

// 处理“全选”操作
const chooseAll = (type: CopywritingType) => {
    // 判断当前是全选还是全不选
    const shouldSelectAll = getCopywritingLibraryContent.value[type].some((item: any) => !item.checked);
    // 更新当前可视列表中的所有项的选中状态
    getCopywritingLibraryContent.value[type].forEach((item: any) => {
        item.checked = shouldSelectAll;
    });
    // 每次全选操作后，重新计算所有选中的项
    setChooseValue();
};

// 弹窗组件引用
const popupRef = ref();
// 公开方法：打开弹窗
const open = () => {
    popupRef.value.open();
    // 打开时清空旧数据并重新获取
    copywritingLists.value = [];
    chooseValue.value = { title: [], described: [] };
    selectCopywriting.value = undefined;
    isViewSelected.value = false;
    getCopywritingLibraryLists();
};

// 关闭弹窗
const close = () => {
    emit("close");
    popupRef.value.close();
};

// 确认选择
const confirm = () => {
    // 触发confirm事件，并传递格式化后的选中数据
    emit("confirm", {
        titleList: chooseValue.value.title.map((item) => ({
            content: item.content,
        })),
        contentList: chooseValue.value.described.map((item) => ({
            content: item.content,
            topic: item.topic,
        })),
    });
    close();
};

// 暴露open方法给父组件
defineExpose({
    open,
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
:deep(.el-select__wrapper) {
    background-color: var(--app-bg-color-1) !important;
}
:deep(.el-input) {
    .el-input__wrapper {
        background-color: transparent;
    }
}
</style>
