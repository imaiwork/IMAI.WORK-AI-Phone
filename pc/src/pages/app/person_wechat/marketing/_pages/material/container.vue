<template>
    <div class="flex flex-col w-full h-full bg-white">
        <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-br-extra-light">
            <div class="flex items-center gap-3">
                <ElButton type="primary" class="!rounded-xl !h-[36px] !font-medium" @click="openUploadModal">
                    <Icon name="el-icon-Plus" />
                    <span class="ml-1">添加素材</span>
                </ElButton>
                <ElButton
                    v-if="selectIds.length"
                    type="danger"
                    plain
                    class="!rounded-xl !h-[36px] !font-medium"
                    @click="handleBatchDelete">
                    <Icon name="el-icon-Delete" />
                    <span class="ml-1">批量删除 ({{ selectIds.length }})</span>
                </ElButton>
            </div>

            <div class="flex items-center gap-2">
                <ElInput
                    v-model="queryParams.file_name"
                    placeholder="搜索素材名称..."
                    clearable
                    class="custom-input"
                    @clear="getLists"
                    @keyup.enter="getLists">
                    <template #prefix>
                        <Icon name="el-icon-Search" />
                    </template>
                </ElInput>
                <div class="refresh-btn shrink-0" @click="getLists">
                    <Icon name="el-icon-Refresh" :size="16" />
                </div>
            </div>
        </div>

        <div class="grow min-h-0 bg-[#f9f9f9]/30" v-loading="pager.loading">
            <template v-if="pager.lists.length">
                <ElScrollbar>
                    <div class="p-6">
                        <div class="grid gap-3 grid-cols-3">
                            <div v-for="item in pager.lists" :key="item.id" class="material-card-wrapper">
                                <div
                                    class="material-card group"
                                    :class="[
                                        getCardHeightClass(item.file_type),
                                        { 'is-selected': selectIds.includes(item.id) },
                                    ]">
                                    <div class="card-main">
                                        <div
                                            v-if="isVisualType(item.file_type)"
                                            class="visual-container"
                                            @click="toggleSelectItem(item)">
                                            <img
                                                v-if="item.file_type === MaterialTypeEnum.IMAGE"
                                                :src="item.file_url"
                                                class="media-content" />
                                            <div
                                                v-else-if="item.file_type === MaterialTypeEnum.VIDEO"
                                                class="relative h-full">
                                                <video :src="item.file_url" class="media-content" />
                                                <div class="video-play-icon">
                                                    <Icon name="el-icon-VideoPlay" :size="32" />
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="card-type-container" @click="toggleSelectItem(item)">
                                            <LinkCard
                                                v-if="item.file_type === MaterialTypeEnum.LINK"
                                                :title="item.file_name"
                                                :desc="item.ext_info.link_desc"
                                                :img="item.file_url" />
                                            <MiniProgramCard
                                                v-else-if="item.file_type === MaterialTypeEnum.MINI_PROGRAM"
                                                :title="item.file_name"
                                                :pic="item.file_url"
                                                :link="item.ext_info.mini_program_path" />
                                            <FileCard
                                                v-else-if="item.file_type === MaterialTypeEnum.FILE"
                                                :name="item.file_name"
                                                :url="item.file_url" />
                                        </div>

                                        <div class="card-overlay">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="action-icon-btn"
                                                    v-if="isVisualType(item.file_type)"
                                                    @click.stop="openPreviewModal(item.file_url, item.file_type)">
                                                    <Icon name="el-icon-View" :size="16" />
                                                </div>
                                                <div class="action-icon-btn" @click.stop="handleDelete(item.id)">
                                                    <Icon name="el-icon-Delete" :size="16" />
                                                </div>
                                                <div
                                                    class="action-icon-btn highlight"
                                                    @click.stop="toggleSelectItem(item)">
                                                    <Icon
                                                        :name="
                                                            selectIds.includes(item.id)
                                                                ? 'el-icon-CircleCheckFilled'
                                                                : 'el-icon-CircleCheck'
                                                        "
                                                        :size="18" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[13px] font-medium text-tx-primary truncate">
                                                {{ item.file_name }}
                                            </div>
                                            <div class="text-[11px] text-tx-placeholder mt-0.5">
                                                {{ item.create_time.split(" ")[0] }}
                                            </div>
                                        </div>

                                        <div class="footer-actions">
                                            <template v-if="isComplexType(item.file_type)">
                                                <div class="edit-btn" @click="openEditModal(item)">
                                                    <Icon name="el-icon-EditPen" />
                                                </div>
                                            </template>
                                            <popover-input
                                                v-else
                                                :value="item.file_name"
                                                @confirm="handleEditName(item, $event)">
                                                <div class="edit-btn"><Icon name="el-icon-EditPen" /></div>
                                            </popover-input>
                                        </div>
                                    </div>

                                    <div v-if="selectIds.includes(item.id)" class="selection-indicator">
                                        <Icon name="el-icon-SuccessFilled" :size="24" color="var(--color-primary)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </template>
            <div v-else class="h-full flex items-center justify-center">
                <ElEmpty description="这里还没有素材哦，快去添加吧" :image-size="120" />
            </div>
        </div>

        <div class="flex-shrink-0 px-6 py-4 border-t border-br-extra-light flex justify-between items-center bg-white">
            <div class="text-xs text-tx-placeholder">
                已选 {{ selectIds.length }} 项，本页共 {{ pager.lists.length }} 项
            </div>
            <pagination v-model="pager" @change="getLists" />
        </div>

        <material-upload
            ref="uploadModalRef"
            v-if="isUploadModalVisible"
            @close="isUploadModalVisible = false"
            @success="handleUploadSuccess" />
        <material-preview v-model="isPreviewModalVisible" :url="previewState.url" :type="previewState.type" />
    </div>
</template>
<script setup lang="ts">
import { ref, computed, nextTick, reactive } from "vue";
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
            return "h-[180px]";
        case MaterialTypeEnum.VIDEO:
            return "h-[225px]";
        case MaterialTypeEnum.MINI_PROGRAM:
            return "h-[250px]";
        case MaterialTypeEnum.LINK:
            return "h-[200px]";
        case MaterialTypeEnum.FILE:
            return "h-[160px]";
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
<style scoped lang="scss">
.refresh-btn {
    @apply w-[40px] h-[40px] flex items-center justify-center rounded-xl bg-gray-100 text-tx-secondary cursor-pointer hover:bg-gray-200 transition-all;
}

.material-card {
    @apply relative bg-white rounded-xl border border-br-extra-light overflow-hidden transition-all duration-300 shadow-light;

    &:hover {
        @apply shadow-light shadow-[#e3e3e3]/50 -translate-y-1 border-primary-light-8;
        .card-overlay {
            @apply opacity-100;
        }
    }

    &.is-selected {
        @apply border-primary ring-2 ring-[#0065FB]/10;
    }
}

.card-main {
    @apply relative overflow-hidden bg-[#f9f9f9] flex-1 h-[calc(100%-60px)];
}

.visual-container {
    @apply h-full w-full cursor-pointer overflow-hidden;
    .media-content {
        @apply w-full h-full object-cover transition-transform duration-500 group-hover:scale-110;
    }
}

.video-play-icon {
    @apply absolute inset-0 flex items-center justify-center text-[#ffffff]/80;
}

.card-type-container {
    @apply p-3 h-full cursor-pointer;
}

.card-overlay {
    @apply absolute inset-0 bg-[#000000]/40 flex items-center justify-center opacity-0 transition-opacity duration-300;
}

.action-icon-btn {
    @apply w-9 h-9 rounded-full bg-[#ffffff]/20 backdrop-blur-md text-white flex items-center justify-center cursor-pointer hover:bg-white hover:text-primary transition-all;
    &.highlight {
        @apply bg-[#0065FB]/80 text-white hover:bg-primary;
    }
}

.card-footer {
    @apply h-[60px] px-3 flex items-center justify-between bg-white border-t border-[#e3e3e3];
}

.edit-btn {
    @apply p-1.5 rounded-lg text-tx-placeholder hover:bg-gray-100 hover:text-primary cursor-pointer transition-all;
}

.selection-indicator {
    @apply absolute top-2 right-2 z-20 bg-white rounded-full shadow-light leading-[0];
}
</style>
