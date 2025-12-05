<template>
    <div class="flex flex-col w-full h-full">
        <!-- 顶部操作区 -->
        <div class="flex-shrink-0 flex items-center justify-between p-4">
            <div>
                <ElButton type="primary" @click="openUploadModal">添加素材</ElButton>
                <ElButton :disabled="selectIds.length === 0" @click="handleBatchDelete">批量删除</ElButton>
            </div>
            <div class="flex items-center gap-x-2">
                <ElInput
                    v-model="queryParams.file_name"
                    class="h-[34px]"
                    placeholder="请输入素材名称"
                    clearable
                    @clear="getLists"
                    @keyup.enter="getLists">
                    <template #append>
                        <ElButton @click="getLists">
                            <Icon name="el-icon-Search" />
                        </ElButton>
                    </template>
                </ElInput>
                <ElButton @click="getLists">
                    <Icon name="el-icon-Refresh" />
                </ElButton>
            </div>
        </div>

        <!-- 内容网格 -->
        <div class="grow min-h-0" v-loading="pager.loading">
            <template v-if="pager.lists.length">
                <ElScrollbar>
                    <div class="grid gap-4 px-4" :class="gridClasses">
                        <div v-for="item in pager.lists" :key="item.id" class="relative">
                            <div
                                class="flex flex-col bg-primary-light-9 rounded-xl overflow-hidden"
                                :class="getCardHeightClass(item.file_type)">
                                <!-- 卡片核心内容 -->
                                <div class="grow min-h-0 overflow-hidden relative group">
                                    <!-- 不同类型素材的展示 -->
                                    <div
                                        v-if="isVisualType(item.file_type)"
                                        class="h-full hover:scale-105 transition-all duration-300 cursor-pointer"
                                        @click="openPreviewModal(item.file_url, item.file_type)">
                                        <img
                                            v-if="item.file_type === MaterialTypeEnum.IMAGE"
                                            class="w-full h-full object-cover"
                                            :src="item.file_url"
                                            alt="素材图片" />
                                        <video
                                            v-else-if="item.file_type === MaterialTypeEnum.VIDEO"
                                            :src="item.file_url"
                                            class="w-full h-full" />
                                    </div>
                                    <div v-else-if="item.file_type === MaterialTypeEnum.LINK" class="m-2 h-full">
                                        <LinkCard
                                            :title="item.file_name"
                                            :desc="item.ext_info.link_desc"
                                            :img="item.file_url" />
                                    </div>
                                    <div
                                        v-else-if="item.file_type === MaterialTypeEnum.MINI_PROGRAM"
                                        class="h-full m-2">
                                        <MiniProgramCard
                                            :title="item.file_name"
                                            :pic="item.file_url"
                                            :link="item.ext_info.mini_program_path" />
                                    </div>
                                    <div v-else-if="item.file_type === MaterialTypeEnum.FILE" class="h-full m-2">
                                        <FileCard :name="item.file_name" :url="item.file_url" />
                                    </div>
                                    <!-- 悬浮操作层 -->
                                    <div
                                        class="absolute inset-0 invisible group-hover:visible z-10 flex items-center justify-center gap-2 bg-[var(--el-overlay-color-lighter)]">
                                        <ElTooltip content="预览" v-if="isVisualType(item.file_type)">
                                            <div
                                                class="cursor-pointer"
                                                @click="openPreviewModal(item.file_url, item.file_type)">
                                                <Icon name="el-icon-ZoomIn" color="#ffffff" :size="18" />
                                            </div>
                                        </ElTooltip>
                                        <ElTooltip content="删除">
                                            <div class="cursor-pointer" @click="handleDelete(item.id)">
                                                <Icon name="el-icon-Delete" color="#ffffff" :size="18" />
                                            </div>
                                        </ElTooltip>
                                        <ElTooltip
                                            content="下载"
                                            v-if="
                                                [MaterialTypeEnum.IMAGE, MaterialTypeEnum.VIDEO].includes(
                                                    item.file_type
                                                )
                                            ">
                                            <div class="cursor-pointer" @click="handleDownload(item.file_url)">
                                                <Icon name="el-icon-Download" color="#ffffff" :size="18" />
                                            </div>
                                        </ElTooltip>
                                        <ElTooltip content="选择">
                                            <div class="cursor-pointer" @click="toggleSelectItem(item)">
                                                <Icon name="el-icon-Check" color="#ffffff" :size="18" />
                                            </div>
                                        </ElTooltip>
                                    </div>
                                </div>

                                <!-- 卡片底部 -->
                                <div class="flex-shrink-0 gap-x-1 flex items-center justify-between p-2">
                                    <ElTooltip :content="item.file_name" v-if="isVisualType(item.file_type)">
                                        <div class="line-clamp-1">{{ item.file_name }}</div>
                                    </ElTooltip>
                                    <div v-else class="text-xs">{{ item.create_time }}</div>

                                    <div class="flex items-center">
                                        <!-- 链接与小程序可编辑复杂信息 -->
                                        <template v-if="isComplexType(item.file_type)">
                                            <ElTooltip content="编辑">
                                                <ElButton link :icon="Edit" @click="openEditModal(item)" />
                                            </ElTooltip>
                                        </template>
                                        <!-- 其他类型只可编辑名称 -->
                                        <popover-input
                                            v-else
                                            :value="item.file_name"
                                            size="default"
                                            width="400px"
                                            show-limit
                                            teleported
                                            :limit="50"
                                            @confirm="handleEditName(item, $event)">
                                            <ElTooltip content="编辑">
                                                <ElButton link :icon="Edit" />
                                            </ElTooltip>
                                        </popover-input>
                                    </div>
                                </div>

                                <!-- 选中状态遮罩 -->
                                <div
                                    v-if="selectIds.includes(item.id)"
                                    class="absolute inset-0 rounded-xl w-full h-full bg-black/5 z-20 flex items-center justify-center"
                                    @click="toggleSelectItem(item)">
                                    <ElTooltip content="取消选择">
                                        <div>
                                            <Icon name="el-icon-Close" :size="24" color="#ffffff" />
                                        </div>
                                    </ElTooltip>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </template>
            <!-- 空状态 -->
            <div v-else class="flex justify-center items-center h-full">
                <ElEmpty description="暂无数据" />
            </div>
        </div>

        <!-- 分页 -->
        <div class="p-4 flex justify-end">
            <pagination v-model="pager" @change="getLists" />
        </div>

        <!-- 上传/编辑弹窗 -->
        <material-upload
            ref="uploadModalRef"
            v-if="isUploadModalVisible"
            @close="isUploadModalVisible = false"
            @success="handleUploadSuccess" />
        <!-- 预览弹窗 -->
        <material-preview
            v-model="isPreviewModalVisible"
            :url="previewState.url"
            :type="previewState.type"
            @close="isPreviewModalVisible = false" />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, reactive } from "vue";
import { Edit } from "@element-plus/icons-vue";
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import { useCate, useFile } from "../../_hooks/useMaterial";
import MaterialUpload from "./upload.vue";
import MaterialPreview from "./preview.vue";
import MiniProgramCard from "../../../_components/mini-program-card.vue";
import LinkCard from "../../../_components/link-card.vue";
import FileCard from "../../../_components/file-card.vue";

const props = withDefaults(
    defineProps<{
        mode?: "page" | "popup";
        limit?: number;
    }>(),
    {
        limit: 9,
        mode: "popup",
    }
);

const emit = defineEmits<{
    (e: "update:select", value: any[]): void;
}>();

// --- 核心逻辑 ---
const { getCateLists } = useCate();
const {
    currentCate,
    pager,
    queryParams,
    selectItem,
    getLists,
    handleDeleteMaterial,
    handleEditMaterial,
    handleDownload,
} = useFile();

// --- 视图计算属性 ---

// 判断是否为图片或视频类型
const isVisualType = (type: number) => [MaterialTypeEnum.IMAGE, MaterialTypeEnum.VIDEO].includes(type);

// 判断是否为链接或小程序等复杂类型
const isComplexType = (type: number) => [MaterialTypeEnum.LINK, MaterialTypeEnum.MINI_PROGRAM].includes(type);

// 动态计算网格布局
const gridClasses = computed(() => {
    const cate = currentCate.value;
    if ([MaterialTypeEnum.IMAGE, MaterialTypeEnum.VIDEO].includes(cate)) {
        return "grid-cols-3 xl:grid-cols-4";
    }
    if ([MaterialTypeEnum.LINK, MaterialTypeEnum.MINI_PROGRAM, MaterialTypeEnum.FILE].includes(cate)) {
        return "grid-cols-3";
    }
    return "";
});

// 动态获取卡片高度
const getCardHeightClass = (type: number) => {
    switch (type) {
        case MaterialTypeEnum.IMAGE:
        case MaterialTypeEnum.VIDEO:
            return "h-[225px]";
        case MaterialTypeEnum.MINI_PROGRAM:
            return "h-[250px]";
        case MaterialTypeEnum.LINK:
            return "h-[170px]";
        case MaterialTypeEnum.FILE:
            return "h-[120px]";
        default:
            return "h-[225px]";
    }
};

// --- 选择逻辑 ---
const selectIds = computed(() => selectItem.value.map((item: any) => item.id));

const toggleSelectItem = (item: any) => {
    const index = selectIds.value.indexOf(item.id);
    if (props.limit != 1) {
        if (index > -1) {
            selectItem.value.splice(index, 1);
        } else {
            selectItem.value.push(item);
        }
    } else {
        selectItem.value = index > -1 ? [] : [item];
    }
    emit("update:select", selectItem.value);
};

// --- 预览弹窗 ---
const isPreviewModalVisible = ref(false);
const previewState = reactive({
    url: "",
    type: MaterialTypeEnum.IMAGE as number,
});

const openPreviewModal = (url: string, type: number) => {
    previewState.url = url;
    previewState.type = type;
    isPreviewModalVisible.value = true;
};

// --- 上传/编辑弹窗 ---
const uploadModalRef = ref<InstanceType<typeof MaterialUpload>>();
const isUploadModalVisible = ref(false);

// 打开新增弹窗
const openUploadModal = async () => {
    isUploadModalVisible.value = true;
    await nextTick();
    uploadModalRef.value?.open();
};

// 打开编辑弹窗 (仅链接/小程序)
const openEditModal = async (data: any) => {
    isUploadModalVisible.value = true;
    await nextTick();
    uploadModalRef.value?.open();

    const formData: Record<string, any> = {
        id: data.id,
        group_ids: data.group_ids,
    };

    if (data.file_type === MaterialTypeEnum.LINK) {
        Object.assign(formData, {
            link: data.ext_info.link,
            link_title: data.file_name,
            link_desc: data.ext_info.link_desc,
            pic: data.file_url,
        });
    } else if (data.file_type === MaterialTypeEnum.MINI_PROGRAM) {
        Object.assign(formData, {
            mini_program_name: data.file_name,
            mini_program_appid: data.ext_info.mini_program_appid,
            mini_program_path: data.ext_info.mini_program_path,
            pic: data.file_url,
        });
    }
    uploadModalRef.value?.setFormData(formData);
};

// 处理名称修改
const handleEditName = (item: any, newName: string) => {
    handleEditMaterial({
        id: item.id,
        file_name: newName,
        ext_info: item.ext_info,
        file_type: item.file_type,
        group_ids: item.group_ids,
        file_url: item.file_url,
    });
};

const handleUploadSuccess = () => {
    isUploadModalVisible.value = false;
    getLists(); // 刷新列表
    getCateLists(); // 刷新分类
};

// --- 删除逻辑 ---

const handleDelete = async (id: number) => {
    await handleDeleteMaterial([id]);
    getCateLists();
};

const handleBatchDelete = async () => {
    await handleDeleteMaterial(selectIds.value);
    getCateLists();
};

// --- 初始化 ---
getLists();
</script>

<style scoped>
/* 样式保持不变 */
</style>
