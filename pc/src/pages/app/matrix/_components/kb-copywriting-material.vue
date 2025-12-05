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
            <div class="text-white text-[20px] font-bold">口播文案库</div>
            <div class="flex justify-between gap-x-2 mt-[18px]">
                <div class="text-[#ffffff80] text-[11px]">
                    已选择<span class="text-white">（{{ count }}）</span>
                </div>
                <ElButton link class="!text-[#ffffff80] !text-[11px]" @click="handleViewSelected">{{
                    isViewSelected ? "隐藏已选择" : "查看已选择"
                }}</ElButton>
            </div>
            <div class="flex gap-x-[10px] mt-[18px]">
                <div class="flex-1 rounded-xl border border-app-border-1 bg-app-bg-2">
                    <div
                        class="flex items-center justify-between h-11 border-[0] border-b-[1px] border-app-border-1 px-[10px]">
                        <div class="text-white">文案列表</div>
                    </div>
                    <div class="h-[308px]">
                        <ElScrollbar class="h-full" v-if="copywritingLists.length > 0">
                            <div class="p-3 flex flex-col gap-y-1">
                                <div
                                    class="flex items-center gap-x-3 h-11 px-3 rounded-md text-white border border-app-border-2 text-xs cursor-pointer"
                                    v-for="(item, index) in copywritingLists"
                                    :key="index"
                                    :class="[currentCopywriting.id == item.id ? 'bg-app-bg-2' : 'bg-app-bg-1']"
                                    @click="chooseCopywriting(item)">
                                    <div class="flex-1">
                                        {{ item.name }}
                                    </div>
                                    <div class="w-4 h-4 rounded-full cursor-pointer">
                                        <Icon
                                            name="local-icon-success_fill"
                                            :size="16"
                                            :color="
                                                currentCopywriting.id == item.id ? 'var(--color-primary)' : '#ffffff1a'
                                            "></Icon>
                                    </div>
                                </div>
                            </div>
                        </ElScrollbar>
                        <div class="h-full flex items-center justify-center" v-else>
                            <ElEmpty description="暂无数据" :image-size="100"></ElEmpty>
                        </div>
                    </div>
                </div>
                <div class="flex-1 rounded-xl border border-app-border-1 bg-app-bg-2">
                    <div
                        class="flex items-center justify-between h-11 border-[0] border-b-[1px] border-app-border-1 px-[10px]">
                        <div class="text-white">标题</div>
                        <ElButton class="!border-app-border-2" color="#1f1f1f" size="small" @click="chooseAll()"
                            >全选</ElButton
                        >
                    </div>
                    <div class="h-[308px]">
                        <ElScrollbar class="h-full" v-if="getCopywritingLibraryContent.oral_copy.length > 0">
                            <div class="p-3 flex flex-col gap-y-2">
                                <div
                                    class="flex items-center gap-x-3"
                                    v-for="(item, index) in getCopywritingLibraryContent.oral_copy"
                                    :key="index">
                                    <div class="flex-1 rounded-md">
                                        <ElInput
                                            v-model="item.content"
                                            type="textarea"
                                            maxlength="800"
                                            show-word-limit
                                            input-style="font-size: 11px"
                                            :rows="6" />
                                    </div>
                                    <div class="w-4 h-4 rounded-full cursor-pointer" @click="choose(item)">
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
import { CopywritingTypeEnum } from "~/pages/app/matrix/_enums";

// 定义组件emit事件
const emit = defineEmits<{
    (e: "close"): void; // 关闭弹窗事件
    (e: "confirm", value: any): void; // 确认事件，携带选中的口播文案
}>();

// 响应式状态：存储所有口播文案库列表
const copywritingLists = ref<any[]>([]);
// 响应式状态：当前选中的文案库对象
const currentCopywriting = ref<any>({});

// 响应式状态：存储用户最终选中的口播文案
const chooseValue = ref<{
    oral_copy: any[];
}>({ oral_copy: [] });

// 计算属性：根据是否“查看已选择”来决定显示的内容
const getCopywritingLibraryContent = computed(() => {
    // 如果开启了“查看已选择”，则只显示已选中的内容
    if (isViewSelected.value) {
        return {
            oral_copy: chooseValue.value.oral_copy,
        };
    }
    // 否则，显示当前选中的文案库的口播文案
    const data = copywritingLists.value.find((item) => item.id == currentCopywriting.value.id) || {};
    return {
        oral_copy: data.oral_copy || [],
    };
});

// 计算属性：已选择的口播文案数量
const count = computed(() => {
    return chooseValue.value.oral_copy.length;
});

// 异步方法：获取口播文案库列表
const getCopywritingLibraryLists = async () => {
    const { lists } = await getCopywritingLibraryList({
        page_size: 9999,
        copywriting_type: CopywritingTypeEnum.CONTENT,
    });
    if (lists.length > 0) {
        // 为每个口播文案条目添加 `checked` 属性
        lists.forEach((item) => {
            item.oral_copy = item.oral_copy.map((c: any) => ({ ...c, checked: false }));
        });
        copywritingLists.value = lists;
        // 默认选中第一个文案库
        currentCopywriting.value = lists[0];
    }
};

// 响应式状态：是否只查看已选择的文案
const isViewSelected = ref(false);
// 切换“查看已选择”状态
const handleViewSelected = () => {
    isViewSelected.value = !isViewSelected.value;
};

// 选择一个文案库
const chooseCopywriting = (item: any) => {
    currentCopywriting.value = item;
    // 切换文案库时，退出“查看已选择”模式
    isViewSelected.value = false;
};

// 核心方法：更新所有选中项的集合
const setChooseValue = () => {
    // 从所有文案库中筛选出被选中的口播文案
    const allOralCopy = copywritingLists.value.flatMap((item) => item.oral_copy).filter((d) => d.checked);
    // 更新最终选中的值
    chooseValue.value = {
        oral_copy: allOralCopy,
    };
};

// 处理单个口播文案的选中/取消选中
const choose = (item: any) => {
    item.checked = !item.checked;
    // 每次选择后，重新计算所有选中的项
    setChooseValue();
};

// 处理“全选”操作
const chooseAll = () => {
    // 判断当前是全选还是全不选
    const shouldSelectAll = getCopywritingLibraryContent.value.oral_copy.some((item: any) => !item.checked);
    // 更新当前可视列表中的所有项的选中状态
    getCopywritingLibraryContent.value.oral_copy.forEach((item: any) => {
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
    chooseValue.value = { oral_copy: [] };
    currentCopywriting.value = {};
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
        lists: chooseValue.value.oral_copy.map((item) => ({
            content: item.content,
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
