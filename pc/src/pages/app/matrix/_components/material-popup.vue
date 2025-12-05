<template>
    <popup
        ref="popupRef"
        width="528px"
        style="
            padding: 0;
            background-color: var(--app-bg-color-2);
            box-shadow: 0px 0px 0px 1px var(--app-border-color-2);
        "
        cancel-button-text=""
        confirm-button-text=""
        :show-close="false"
        @close="close">
        <div class="rounded-xl overflow-hidden flex flex-col -my-2">
            <!-- 弹窗头部 -->
            <div class="flex items-center justify-between h-[50px] px-4">
                <div class="flex items-center gap-x-2">
                    <div class="w-6 h-6 flex items-center justify-center rounded-md border border-[#ffffff1a]">
                        <Icon name="local-icon-windows" :size="14"></Icon>
                    </div>
                    <div class="text-[20px] text-white font-bold">素材库</div>
                </div>
                <div class="w-6 h-6 cursor-pointer" @click="close">
                    <close-btn :theme="ThemeEnum.DARK" />
                </div>
            </div>

            <!-- 搜索栏 -->
            <div class="px-4 my-4">
                <div class="flex items-center rounded-full h-[50px] border border-[#2a2a2a] px-[5px]">
                    <ElInput
                        v-model="queryParams.name"
                        class="flex-1 search-input"
                        clearable
                        prefix-icon="el-icon-Search"
                        placeholder="请输入素材名称"
                        input-style="color: #ffffff"
                        @clear="search"
                        @keyup.enter="search"></ElInput>
                    <ElButton type="primary" class="!text-white !rounded-full !w-[116px] !h-10" @click="search">
                        搜索
                    </ElButton>
                </div>
            </div>

            <!-- 标签页 -->
            <div class="px-4" v-if="showTab">
                <ElTabs v-model="currentTab" class="!text-white" @tab-click="handleTabClick">
                    <ElTabPane :name="TabTypeEnum.MATERIAL" label="素材库"></ElTabPane>
                    <ElTabPane
                        :name="TabTypeEnum.DH"
                        label="数字人v1"
                        v-if="props.type === MaterialTypeEnum.VIDEO"></ElTabPane>
                    <ElTabPane
                        :name="TabTypeEnum.DH_V2"
                        label="数字人v2"
                        v-if="props.type === MaterialTypeEnum.VIDEO"></ElTabPane>
                </ElTabs>
            </div>

            <!-- 素材列表 -->
            <div
                class="h-[600px] overflow-y-auto relative dynamic-scroller"
                v-infinite-scroll="load"
                :infinite-scroll-immediate="false"
                :infinite-scroll-disabled="isScrollDisabled"
                :infinite-scroll-distance="10">
                <div class="h-full" v-loading="pager.loading">
                    <div v-if="pager.lists.length > 0">
                        <div class="grid grid-cols-3 gap-2 p-2">
                            <div v-for="item in pager.lists" :key="item.id" @click="choose(item)">
                                <div
                                    class="card-gradient cursor-pointer bg-black w-full relative h-[210px] flex flex-col overflow-hidden rounded-xl">
                                    <div class="w-full px-3 absolute z-[22] top-2 pr-[50px]">
                                        <ElTooltip :content="item.name">
                                            <div class="line-clamp-1 text-white break-all">
                                                {{ item.name }}
                                            </div>
                                        </ElTooltip>
                                    </div>

                                    <!-- 图片类型 -->
                                    <ElImage
                                        v-if="props.type === MaterialTypeEnum.IMAGE"
                                        :src="item.content"
                                        class="w-full h-full rounded-xl"
                                        preview-teleported
                                        fit="cover" />
                                    <!-- 视频类型 -->
                                    <template v-if="props.type === MaterialTypeEnum.VIDEO">
                                        <img
                                            v-if="item.pic"
                                            :src="item.pic"
                                            class="w-full h-full rounded-xl object-cover" />
                                        <video
                                            v-else
                                            :src="item.content || item.video_result_url"
                                            class="w-full h-full rounded-xl object-cover" />
                                    </template>

                                    <!-- 选中状态 -->
                                    <div class="absolute top-2 right-2 z-[1000] w-6 h-6 rounded-full">
                                        <Icon
                                            name="local-icon-success_fill"
                                            :size="20"
                                            :color="isChoose(item) ? 'var(--color-primary)' : '#ffffff1a'"></Icon>
                                    </div>
                                    <!-- 视频播放按钮 -->
                                    <div
                                        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
                                        v-if="props.type === MaterialTypeEnum.VIDEO">
                                        <div @click.stop="handlePreview(item)">
                                            <play-btn />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!pager.isLoad" class="text-white text-center text-xs w-full py-4">暂无更多了~</div>
                    </div>
                    <!-- 空状态 -->
                    <div v-else class="h-full flex items-center justify-center">
                        <ElEmpty description="暂无数据"></ElEmpty>
                    </div>
                </div>
            </div>

            <!-- 底部确认按钮 -->
            <div class="flex justify-center my-2 px-2" v-if="multiple">
                <ElButton type="primary" class="!rounded-full w-[318px] !h-[50px]" @click="handleConfirm">
                    确定
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
import { getMaterialLibraryList, getDigitalHumanVideo } from "@/api/redbook";
import { getVideoList as getDigitalHumanVideoList } from "@/api/digital_human";
import { ThemeEnum } from "@/enums/appEnums";
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
const load = async () => {
    if (isScrollDisabled.value) return;
    queryParams.page_no += 1;
    await getLists();
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
        background-color: transparent;
        box-shadow: none;
        &::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }
    }
}
</style>
