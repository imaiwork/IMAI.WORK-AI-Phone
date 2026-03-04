<template>
    <popup
        ref="popupRef"
        width="650px"
        style="padding: 0"
        footer-class="!p-0"
        header-class="!p-0"
        cancel-button-text=""
        confirm-button-text=""
        :show-close="false"
        @close="close">
        <div class="rounded-[28px] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between h-[70px] px-6 border-b border-[#F1F5F9] bg-white">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <Icon name="local-icon-windows" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">素材资源库集</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Material Resources
                        </div>
                    </div>
                </div>
                <div class="w-8 h-8" @click="close">
                    <close-btn :icon-size="10" />
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-b border-[#F1F5F9]">
                <div
                    class="flex items-center rounded-full h-[52px] bg-white border border-br px-1.5 transition-all focus-within:border-[#0065fb]">
                    <ElInput
                        v-model="queryParams.name"
                        class="flex-1 search-input"
                        clearable
                        prefix-icon="el-icon-Search"
                        placeholder="搜索素材名称..."
                        @clear="search"
                        @keyup.enter="search">
                    </ElInput>
                    <ElButton
                        type="primary"
                        class="!rounded-full !w-[100px] !h-[42px] !font-medium !text-sm ! !shadow-[#0065fb]/20"
                        @click="search">
                        搜索
                    </ElButton>
                </div>

                <div class="mt-4 px-1" v-if="showTab">
                    <ElTabs v-model="currentTab" class="custom-tabs" @tab-click="handleTabClick">
                        <ElTabPane :name="TabTypeEnum.MATERIAL" label="通用素材"></ElTabPane>
                        <ElTabPane
                            :name="TabTypeEnum.DH"
                            label="数字人 V1"
                            v-if="props.type === MaterialTypeEnum.VIDEO"></ElTabPane>
                        <ElTabPane
                            :name="TabTypeEnum.DH_V2"
                            label="数字人 V2"
                            v-if="props.type === MaterialTypeEnum.VIDEO"></ElTabPane>
                    </ElTabs>
                </div>
            </div>

            <div class="h-[550px] bg-slate-50">
                <ElScrollbar :distance="20" @end-reached="load">
                    <div class="p-5" v-loading="pager.loading">
                        <div v-if="pager.lists.length > 0">
                            <div class="grid grid-cols-3 gap-4">
                                <div
                                    v-for="item in pager.lists"
                                    :key="item.id"
                                    @click="choose(item)"
                                    class="group relative flex flex-col bg-white rounded-2xl border transition-all cursor-pointer"
                                    :class="[
                                        isChoose(item)
                                            ? 'border-[#0065fb]  shadow-[#0065fb]/10 scale-[0.98]'
                                            : 'border-[transparent] ',
                                    ]">
                                    <div class="aspect-[3/4] relative overflow-hidden rounded-t-2xl bg-[#E2E8F0]">
                                        <ElImage
                                            v-if="props.type === MaterialTypeEnum.IMAGE"
                                            :src="item.content"
                                            class="w-full h-full transition-transform duration-500 group-hover:scale-110"
                                            fit="cover" />

                                        <template v-if="props.type === MaterialTypeEnum.VIDEO">
                                            <img
                                                v-if="item.pic"
                                                :src="item.pic"
                                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                                            <video
                                                v-else
                                                :src="item.content || item.video_result_url"
                                                class="w-full h-full object-cover" />

                                            <div
                                                class="absolute inset-0 flex items-center justify-center bg-black/10 group-hover:bg-black/30 transition-all">
                                                <div
                                                    class="w-10 h-10 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white scale-90 group-hover:scale-100 transition-all"
                                                    @click.stop="handlePreview(item)">
                                                    <Icon name="el-icon-CaretRight" :size="20"></Icon>
                                                </div>
                                            </div>
                                        </template>

                                        <div
                                            class="absolute top-2 right-2 z-30 w-6 h-6 rounded-full flex items-center justify-center transition-all"
                                            :class="[
                                                isChoose(item)
                                                    ? 'bg-primary '
                                                    : 'bg-black/20 backdrop-blur-sm opacity-0 group-hover:opacity-100',
                                            ]">
                                            <Icon name="el-icon-Check" :size="14" color="#ffffff"></Icon>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-white rounded-b-2xl">
                                        <div class="text-xs font-medium text-[#1E293B] truncate leading-tight">
                                            {{ item.name || "未命名素材" }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="py-6 text-center">
                                <span
                                    v-if="!pager.isLoad"
                                    class="text-[11px] text-[#94A3B8] font-medium uppercase tracking-widest"
                                    >End of Content</span
                                >
                                <div v-else class="flex items-center justify-center gap-2 text-primary">
                                    <Icon name="el-icon-Loading"></Icon>
                                    <span class="text-xs font-medium">正在加载更多...</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="h-[400px] flex flex-col items-center justify-center">
                            <div
                                class="w-24 h-24 bg-[#F1F5F9] rounded-full flex items-center justify-center mb-4 text-[#CBD5E1]">
                                <Icon name="el-icon-FolderOpened" :size="40"></Icon>
                            </div>
                            <div class="text-[#94A3B8] font-medium text-sm">此库中暂无素材内容</div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>

            <div class="p-4 bg-white border-t border-[#F1F5F9] flex justify-center" v-if="multiple">
                <ElButton
                    type="primary"
                    class="!rounded-full !w-[320px] !h-[50px] !text-[15px] !font-black !shadow-xl !shadow-[#0065fb]/20 active:scale-95 transition-all"
                    @click="handleConfirm">
                    确认选择素材
                </ElButton>
            </div>
        </div>
    </popup>

    <!-- 预览组件 -->
    <preview-video ref="previewVideoRef" v-if="showPreview" @close="showPreview = false"></preview-video>
    <ElImageViewer
        v-if="showPreview && props.type === MaterialTypeEnum.IMAGE"
        :initial-index="0"
        :url-list="previewImageUrl"
        @close="showPreview = false"></ElImageViewer>
</template>

<script setup lang="ts">
import { getMaterialLibraryList } from "@/api/material";
import { getDigitalHumanVideo } from "@/api/matrix";
import { getVideoList as getDigitalHumanVideoList } from "@/api/digital_human";
import Popup from "@/components/popup/index.vue";
import { MaterialTypeEnum } from "../_enums";
import feedback from "@/utils/feedback";

// ================================= 接口和枚举 =================================
/**
 * @description 统一的素材项目接口
 */
interface MaterialItem {
    id: number | string;
    name: string;
    content?: string; // 素材库的图片/视频URL
    pic?: string; // 封面图
    video_result_url?: string; // 数字人v1视频URL
    result_url?: string; // 数字人v2视频URL
    automatic_clip?: number; // 是否自动剪辑
    clip_result_url?: string; // 剪辑后的视频URL
}

/**
 * @description 标签页类型枚举
 */
enum TabTypeEnum {
    MATERIAL = "material", // 素材库
    DH = "dh", // 数字人v1
    DH_V2 = "dh_v2", // 数字人v2
}

// ================================= Props and Emits =================================
const props = withDefaults(
    defineProps<{
        type: MaterialTypeEnum; // 素材类型
        limit?: number; // 选择数量限制
        multiple?: boolean; // 是否可以多选
        showTab?: boolean; // 是否显示标签页
    }>(),
    {
        type: MaterialTypeEnum.IMAGE,
        multiple: true,
        limit: 9,
        showTab: true,
    }
);

const emit = defineEmits<{
    (e: "close"): void;
    (e: "confirm", lists: any[]): void;
}>();

// ================================= 响应式状态 =================================
/**
 * @description Popup组件实例
 */
const popupRef = ref<InstanceType<typeof Popup>>();
/**
 * @description 当前选中的标签页
 */
const currentTab = ref<TabTypeEnum>(TabTypeEnum.MATERIAL);
/**
 * @description 查询参数
 */
const queryParams = reactive({
    name: "",
    page_no: 1,
});
/**
 * @description 已选中的素材列表
 */
const chooseList = ref<MaterialItem[]>([]);
/**
 * @description 视频预览组件实例
 */
const previewVideoRef = shallowRef();
/**
 * @description 是否显示预览
 */
const showPreview = ref(false);
/**
 * @description 预览图片的URL列表
 */
const previewImageUrl = ref<string[]>([]);

// ================================= 计算属性 =================================
/**
 * @description 是否禁用无限滚动
 */
const isScrollDisabled = computed(() => !pager.isLoad || pager.loading);

/**
 * @description 根据当前tab动态选择对应的API请求函数
 */
const fetchFunctionMap = {
    [TabTypeEnum.MATERIAL]: (params: any) => getMaterialLibraryList({ ...params, m_type: props.type }),
    [TabTypeEnum.DH]: (params: any) => getDigitalHumanVideo({ ...params, status: 6 }),
    [TabTypeEnum.DH_V2]: (params: any) => getDigitalHumanVideoList({ ...params, type: 0, status: 1 }),
};

// ================================= 数据分页 =================================
const {
    pager,
    getLists,
    resetPage: resetPager,
} = usePaging({
    fetchFun: (params) => fetchFunctionMap[currentTab.value](params),
    params: queryParams,
    isScroll: true,
});

// ================================= 方法 =================================
/**
 * @description 切换标签页
 */
const handleTabClick = (tab: any) => {
    currentTab.value = tab.paneName as TabTypeEnum;
    chooseList.value = []; // 清空已选列表
    search(); // 重置搜索并加载数据
};

/**
 * @description 执行搜索
 */
const search = () => {
    queryParams.page_no = 1;
    resetPager();
};

/**
 * @description 检查项是否已被选中
 * @param item 素材项
 */
const isChoose = (item: MaterialItem) => {
    return chooseList.value.some((val) => val.id == item.id);
};

/**
 * @description 选中/取消选中素材
 * @param item 素材项
 */
const choose = (item: MaterialItem) => {
    if (isChoose(item)) {
        // 如果已选中，则取消选中
        chooseList.value = chooseList.value.filter((val) => val.id !== item.id);
    } else {
        // 如果未选中，则添加
        if (chooseList.value.length >= props.limit) {
            feedback.msgWarning(`最多只能选择${props.limit}个素材`);
            return;
        }
        chooseList.value.push(item);
    }

    // 如果是单选模式，则选择后直接确认
    if (!props.multiple) {
        handleConfirm();
    }
};

/**
 * @description 获取视频的最终URL
 * @param item 素材项
 */
const getItemVideoUrl = (item: MaterialItem): string => {
    const { automatic_clip, clip_result_url, result_url, video_result_url, content } = item;
    if (currentTab.value === TabTypeEnum.MATERIAL) {
        return content || "";
    }
    if (automatic_clip === 1) {
        return clip_result_url || "";
    }
    return currentTab.value === TabTypeEnum.DH_V2 ? result_url || "" : video_result_url || "";
};

/**
 * @description 确认选择
 */
const handleConfirm = () => {
    if (chooseList.value.length === 0) {
        feedback.msgError(`请选择素材`);
        return;
    }
    const result = chooseList.value.map((item) => ({
        url: props.type === MaterialTypeEnum.VIDEO ? getItemVideoUrl(item) : item.content,
        name: item.name,
        pic: item.pic,
    }));
    emit("confirm", result);
    close();
};

/**
 * @description 预览素材
 * @param item 素材项
 */
const handlePreview = async (item: MaterialItem) => {
    showPreview.value = true;

    if (props.type === MaterialTypeEnum.IMAGE) {
        previewImageUrl.value = [item.content!];
        return;
    }

    if (props.type === MaterialTypeEnum.VIDEO) {
        await nextTick();
        const videoUrl = getItemVideoUrl(item);
        if (videoUrl && previewVideoRef.value) {
            previewVideoRef.value.setUrl(videoUrl);
            previewVideoRef.value.open();
        } else {
            feedback.msgError("视频地址无效，无法预览");
            showPreview.value = false;
        }
    }
};

/**
 * @description 加载更多数据（无限滚动触发）
 */
const load = async (e: any) => {
    if (e == "bottom") {
        if (isScrollDisabled.value || pager.loading || !pager.isLoad) return;
        queryParams.page_no++;
        await getLists();
    }
};

/**
 * @description 打开弹窗
 */
const open = async () => {
    popupRef.value?.open();
    if (pager.lists.length === 0) {
        getLists();
    }
};

/**
 * @description 关闭弹窗
 */
const close = () => {
    emit("close");
};

// ================================= 暴露方法 =================================
defineExpose({
    open,
    close,
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";

:deep(.el-tabs) {
    --el-tabs-header-height: 50px;
    padding: 0 0;
}

:deep(.search-input) {
    .el-input__wrapper {
        background: transparent !important;
        box-shadow: none !important;
        padding-left: 15px;
    }
    .el-input__inner {
        font-weight: 600;
        color: #1e293b;
        &::placeholder {
            color: #94a3b8;
        }
    }
}

/* Tabs 美化 */
:deep(.custom-tabs) {
    .el-tabs__nav-wrap::after {
        display: none;
    }
    .el-tabs__active-bar {
        height: 3px;
        border-radius: 3px;
        background-color: #4f46e5;
    }
    .el-tabs__item {
        font-weight: 800;
        font-size: 14px;
        color: #94a3b8;
        &.is-active {
            color: #1e293b;
        }
    }
}
</style>
