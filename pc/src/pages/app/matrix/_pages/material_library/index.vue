<template>
    <DefineMaterialCard v-slot="{ item, showRemove = false, onRemove }">
        <div
            :class="[
                'group relative bg-white rounded-[20px] overflow-hidden border transition-all hover:shadow-[#0065fb]/10',
                batchMode ? 'cursor-pointer' : 'hover:-translate-y-1',
                selectedItems.includes(item.id) ? 'border-primary shadow-[#0065fb]/20' : 'border-br',
            ]"
            @click="batchMode ? toggleItemSelection(item.id) : null">
            <div v-if="batchMode && !showRemove" class="absolute top-0 left-0 right-0 bottom-0 z-[1001]">
                <div
                    class="absolute top-3 left-3 z-[1001] w-6 h-6 flex items-center justify-center rounded-full border cursor-pointer"
                    :class="
                        selectedItems.includes(item.id)
                            ? 'border-primary bg-primary text-white'
                            : 'border-gray-300 bg-white'
                    "
                    @click.stop="toggleItemSelection(item.id)">
                    <Icon v-if="selectedItems.includes(item.id)" name="el-icon-Check" :size="12" />
                </div>
            </div>

            <div
                v-if="showRemove"
                class="absolute top-2 right-2 z-[1001] w-6 h-6 rounded-full bg-red-500 hover:bg-red-600 flex items-center justify-center cursor-pointer transition-all"
                @click.stop="onRemove && onRemove(item.id)">
                <Icon name="el-icon-Close" color="#ffffff" :size="12" />
            </div>

            <div class="aspect-square relative overflow-hidden bg-[#F1F5F9]">
                <ElImage
                    v-if="MaterialTypeEnum.IMAGE == item.m_type"
                    class="w-full h-full"
                    fit="cover"
                    lazy
                    preview-teleported
                    :src="item.content"
                    :preview-src-list="[item.content]"></ElImage>
                <template v-if="MaterialTypeEnum.VIDEO == item.m_type || MaterialTypeEnum.MUSIC == item.m_type">
                    <template v-if="MaterialTypeEnum.VIDEO == item.m_type">
                        <img v-if="item.pic" :src="item.pic" class="w-full h-full object-cover" />
                        <video v-else :src="item.content" class="w-full h-full object-cover" />
                    </template>
                    <img
                        src="@/assets/images/audio_bg.png"
                        class="w-full h-full object-cover"
                        v-if="MaterialTypeEnum.MUSIC == item.m_type" />
                    <div
                        v-if="!batchMode && !showRemove"
                        class="absolute top-[50%] left-[50%] -translate-x-[50%] -translate-y-[50%] z-[1000]">
                        <div class="w-12 h-12" @click.stop="handlePlay(item)">
                            <play-btn />
                        </div>
                    </div>
                </template>

                <div
                    v-if="!batchMode && !showRemove"
                    class="absolute right-2 top-2 z-[1000] w-9 h-9 invisible group-hover:visible">
                    <handle-menu :data="item" :menu-list="utilsMenuList" />
                </div>
            </div>

            <div class="p-4 bg-white">
                <div class="flex items-center justify-between mb-1">
                    <div class="text-[13px] font-black text-[#1E293B] truncate flex-1 mr-2">
                        {{ item.name || "未命名素材" }}
                    </div>
                    <div
                        class="text-[9px] px-1.5 py-0.5 rounded bg-[#F1F5F9] text-[#64748B] font-black uppercase tracking-tighter">
                        {{
                            item.m_type == MaterialTypeEnum.VIDEO
                                ? "VIDEO"
                                : item.m_type == MaterialTypeEnum.IMAGE
                                ? "IMAGE"
                                : "AUDIO"
                        }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2" v-if="!showRemove">
                    <span class="text-[11px] text-[#94A3B8] font-medium">
                        {{ item.create_time }}
                    </span>
                    <div class="flex items-center gap-1.5 bg-slate-50 px-2 py-0.5 rounded-md border border-[#F1F5F9]">
                        <Icon name="el-icon-Files" :size="10"></Icon>
                        <span class="text-[10px] text-[#64748B] font-medium">
                            {{ formatFileSize(item.size) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </DefineMaterialCard>

    <div class="h-full flex flex-col bg-white rounded-[20px] overflow-hidden border border-br min-w-[1000px]">
        <div class="flex-shrink-0 px-6 border-b border-br bg-white">
            <div class="flex items-center justify-between h-[80px]">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <Icon name="el-icon-Folder" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">素材管理中心</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            总素材: {{ pager.count }} 个
                            <span v-if="batchMode && selectedItems.length > 0" class="text-primary ml-2">
                                | 已选择: {{ selectedItems.length }} 个
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <template v-if="batchMode">
                        <ElButton type="default" class="!rounded-full !h-10 !px-4" @click="selectAll">
                            {{ isAllSelected ? "取消全选" : "全选" }}
                        </ElButton>
                        <ElButton type="default" class="!rounded-full !h-10 !px-4" @click="cancelBatchMode">
                            取消
                        </ElButton>
                    </template>

                    <template v-if="!batchMode">
                        <ElSelect
                            v-model="queryParams.m_type"
                            class="!w-[110px] custom-select-pill"
                            clearable
                            placeholder="所有类型"
                            :empty-values="[null, undefined]"
                            :show-arrow="false"
                            @change="handleFilterChange">
                            <ElOption label="全部类型" value=""></ElOption>
                            <ElOption label="视频素材" :value="MaterialTypeEnum.VIDEO"></ElOption>
                            <ElOption label="图片素材" :value="MaterialTypeEnum.IMAGE"></ElOption>
                            <ElOption label="音频素材" :value="MaterialTypeEnum.MUSIC"></ElOption>
                        </ElSelect>
                        <ElSelect
                            v-model="fieldValue"
                            class="!w-[120px] custom-select-pill"
                            clearable
                            :show-arrow="false"
                            :empty-values="[null, undefined]"
                            @change="handleSortChange">
                            <ElOption label="全部" value=""></ElOption>
                            <ElOption label="最新开始排序" :value="1"></ElOption>
                            <ElOption label="最早开始排序" :value="2"></ElOption>
                            <ElOption label="文件从大到小" :value="3"></ElOption>
                            <ElOption label="文件从小到大" :value="4"></ElOption>
                        </ElSelect>
                        <div
                            class="flex items-center rounded-full h-[40px] border border-br px-1 transition-all focus-within:border-[#0065fb]">
                            <ElInput
                                v-model="queryParams.name"
                                class="!w-[150px] search-input"
                                clearable
                                prefix-icon="el-icon-Search"
                                placeholder="搜索素材名称..."
                                @clear="handleSearchClear"
                                @keyup.enter="handleSearch">
                            </ElInput>
                            <ElButton
                                type="primary"
                                class="!rounded-full !h-[32px] !px-4 !text-xs !font-medium"
                                @click="handleSearch">
                                搜索
                            </ElButton>
                        </div>
                        <div class="w-[1px] h-6 bg-[#E2E8F0] mx-2"></div>
                        <ElButton
                            type="default"
                            class="!rounded-full !h-10 !px-4 !border-[#E2E8F0] hover:!border-primary hover:!text-primary transition-all"
                            @click="toggleBatchMode">
                            <Icon name="el-icon-Operation" color="currentColor" :size="16"></Icon>
                            <span class="ml-2">批量操作</span>
                        </ElButton>

                        <upload
                            type="file"
                            :accept="accept"
                            show-progress
                            :data="{ ffmpeg: 1 }"
                            :max-size="200"
                            :show-file-list="false"
                            @change="handleUploadSuccess">
                            <ElButton type="primary" class="!rounded-full !h-10 !px-4">
                                <Icon name="local-icon-add_circle" color="#ffffff"></Icon>
                                <span class="ml-2">上传素材</span>
                            </ElButton>
                        </upload>
                    </template>
                </div>
            </div>
        </div>
        <div class="grow min-h-0 flex bg-slate-50" ref="materialLibraryRef">
            <div class="shrink-0 w-[240px] bg-white border-r border-br flex flex-col relative">
                <div class="grow min-h-0">
                    <ElScrollbar>
                        <div class="p-4 flex flex-col gap-1">
                            <div
                                v-for="group in groupLists"
                                :key="group.id"
                                :class="[
                                    'group flex items-center justify-between px-3 py-3 rounded-xl cursor-pointer transition-all relative overflow-hidden',
                                    queryParams.group_id === group.id
                                        ? 'bg-[#0065fb]/5 text-primary'
                                        : 'hover:bg-slate-50 text-slate-600',
                                ]"
                                @click="handleGroupClick(group)">
                                <div class="flex items-center gap-2.5 truncate z-10">
                                    <Icon
                                        :name="
                                            queryParams.group_id === group.id
                                                ? 'el-icon-FolderOpened'
                                                : 'el-icon-Folder'
                                        "
                                        :size="16" />
                                    <span class="text-[13px] font-medium truncate">{{ group.name }}</span>
                                </div>

                                <div class="flex items-center gap-2 z-10 relative min-w-0">
                                    <span
                                        :class="[
                                            'absolute right-0 top-1/2 -translate-y-1/2 text-[10px] px-1.5 py-0.5 rounded-md font-black transition-all tabular-nums',
                                            'group-hover:opacity-0 group-hover:pointer-events-none',
                                            queryParams.group_id === group.id
                                                ? 'bg-primary text-white'
                                                : 'bg-slate-100 text-slate-400',
                                        ]">
                                        {{ group.material_count || 0 }}
                                    </span>

                                    <div
                                        v-if="group.id"
                                        :class="[
                                            'absolute right-0 top-1/2 -translate-y-1/2 flex items-center gap-1.5 transition-all',
                                            'opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto',
                                        ]">
                                        <button
                                            class="p-1 hover:bg-[#0065fb]/10 rounded-md transition-colors"
                                            @click.stop="handleRenameGroup(group)">
                                            <Icon name="el-icon-Edit" :size="12" />
                                        </button>
                                        <button
                                            class="p-1 hover:bg-red-50 hover:text-red-500 rounded-md transition-colors"
                                            @click.stop="handleDeleteGroup(group)">
                                            <Icon name="el-icon-Delete" :size="12" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="groupPager.isLoad" v-if="groupLists.length > 10" />
                    </ElScrollbar>
                </div>

                <div class="p-4 border-t border-slate-50 bg-white relative">
                    <button @click="handleAddGroup" class="premium-add-btn group">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-primary to-[#3b82f6] opacity-90 group-hover:opacity-100 transition-opacity"></div>

                        <div class="relative flex items-center justify-center gap-2 py-2.5 w-full text-white">
                            <div
                                class="w-5 h-5 rounded-lg bg-[#ffffff]/20 flex items-center justify-center group-hover:rotate-90 transition-transform duration-300">
                                <Icon name="el-icon-Plus" :size="14" />
                            </div>
                            <span class="text-sm font-black tracking-wide">添加素材分组</span>
                        </div>
                    </button>
                </div>
            </div>

            <div class="h-full flex-1 relative" v-spin="{ show: materialLoading, text: loadingText }">
                <div class="relative h-full">
                    <ElScrollbar :distance="20" @end-reached="load" v-if="pager.lists.length > 0 || pager.loading">
                        <div class="p-6">
                            <div class="grid grid-cols-3 gap-4">
                                <ReuseMaterialCard v-for="item in pager.lists" :key="item.id" :item="item" />
                            </div>
                            <load-text :is-load="pager.isLoad" />
                        </div>
                    </ElScrollbar>

                    <div v-else-if="!materialLoading" class="h-full flex flex-col items-center justify-center">
                        <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-4">
                            <Icon name="el-icon-FolderOpened" :size="40" color="#CBD5E1"></Icon>
                        </div>
                        <p class="text-[#94A3B8] text-sm font-medium">暂无素材内容，点击右上角上传</p>
                    </div>
                </div>

                <div
                    v-if="batchMode && selectedItems.length > 0"
                    class="absolute bottom-0 left-0 right-0 bg-white border-t border-br p-4 flex items-center justify-between shadow-light z-[1002]">
                    <div class="flex items-center gap-2">
                        <Icon name="el-icon-Select" color="#0065fb" :size="16"></Icon>
                        <span class="text-sm font-medium text-[#1E293B]">
                            已选择 {{ selectedItems.length }} 个素材
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <ElButton type="default" class="!rounded-full !h-10 !px-4" @click="clearSelection">
                            清空选择
                        </ElButton>
                        <ElButton type="danger" class="!rounded-full !h-10 !px-4" @click="handleBatchDelete">
                            删除
                        </ElButton>
                        <ElButton type="primary" class="!rounded-full !h-10 !px-6" @click="showBatchTransferDialog">
                            <Icon name="el-icon-FolderAdd" color="#ffffff" :size="16"></Icon>
                            <span class="ml-2">转移到分组</span>
                        </ElButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <popup
        v-if="batchTransferVisible"
        ref="batchTransferPopupRef"
        width="620px"
        cancel-button-text=""
        confirm-button-text=""
        footer-class="!p-0"
        header-class="!p-0"
        style="padding: 0; overflow: hidden"
        :show-close="false"
        @close="batchTransferVisible = false">
        <div class="flex items-center justify-between h-[76px] px-4 bg-slate-50">
            <div class="text-[15px] font-black text-slate-800">转移素材至分组</div>
            <div class="w-8 h-8" @click="batchTransferVisible = false">
                <close-btn />
            </div>
        </div>
        <div class="p-4">
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-1 h-4 bg-primary rounded-full"></div>
                        <span class="text-[15px] font-black text-slate-800">
                            待转移素材
                            <span
                                class="ml-1 text-primary text-xs bg-[#0065fb]/10 px-2 py-0.5 rounded-full font-medium">
                                {{ selectedItems.length }}
                            </span>
                        </span>
                    </div>
                    <button
                        v-if="selectedItems.length > 0"
                        @click="clearSelection"
                        class="text-[11px] font-black text-slate-400 hover:text-red-500 flex items-center gap-1 transition-colors">
                        <Icon name="el-icon-Delete" :size="12" />
                        清空选择
                    </button>
                </div>
                <div class="bg-[#f8fafc]/50 rounded-[20px] border border-slate-100">
                    <ElScrollbar v-if="selectedItems.length > 0" max-height="320px">
                        <div class="grid grid-cols-3 gap-4 p-4">
                            <ReuseMaterialCard
                                v-for="item in selectedMaterials"
                                :key="item.id"
                                :item="item"
                                :show-remove="true"
                                :on-remove="handleRemoveMaterial" />
                        </div>
                    </ElScrollbar>

                    <div v-else class="py-12 flex flex-col items-center justify-center grayscale opacity-60">
                        <div class="w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center mb-3">
                            <Icon name="el-icon-FolderOpened" :size="32" color="#94A3B8" />
                        </div>
                        <p class="text-xs font-medium text-slate-400 uppercase tracking-widest">暂无素材</p>
                    </div>
                </div>
            </div>
            <div class="p-5 bg-white rounded-[20px] border border-slate-100 mb-4">
                <div class="flex items-center gap-2 mb-4">
                    <Icon name="el-icon-Position" color="#0065fb" :size="16" />
                    <span class="text-sm font-[1000] text-slate-700">选择目标分组</span>
                </div>

                <ElSelect
                    v-model="targetGroupId"
                    class="w-full"
                    placeholder="搜索或选择目标分组..."
                    filterable
                    clearable
                    :show-arrow="false">
                    <ElOption
                        v-for="group in groupPager.lists"
                        :key="group.id"
                        :label="group.name"
                        :value="group.id"
                        class="!h-11">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-2">
                                <Icon :name="group.id ? 'el-icon-Folder' : 'el-icon-Files'" :size="16" />
                                <span class="font-medium text-sm">{{ group.name }}</span>
                            </div>
                            <span class="text-[10px] text-slate-300 font-black">ID: {{ group.id || "N/A" }}</span>
                        </div>
                    </ElOption>
                </ElSelect>

                <p class="mt-3 text-[10px] text-slate-400 font-medium flex items-center gap-1">
                    <Icon name="el-icon-Warning" :size="12" />
                    转移后，原分组将不再保留这些素材。
                </p>
            </div>
            <div class="pt-2 flex justify-end gap-3">
                <ElButton class="px-8 !h-11 !rounded-xl !font-black !text-sm" @click="batchTransferVisible = false">
                    放弃
                </ElButton>
                <ElButton
                    type="primary"
                    :loading="transferLoading"
                    :disabled="selectedItems.length === 0 || !targetGroupId"
                    :class="['!px-10 !h-11 !rounded-xl !text-sm !font-black ']"
                    @click="confirmBatchTransfer">
                    {{ transferLoading ? "正在转移..." : "确认转移" }}
                </ElButton>
            </div>
        </div>
    </popup>

    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
    <preview-audio v-if="showPreviewAudio" ref="previewAudioRef" @close="showPreviewAudio = false" />
    <rename-pop
        v-if="showRenamePopup"
        ref="renamePopupRef"
        :fetch-fn="updateMaterialLibrary"
        @close="showRenamePopup = false"
        @success="getUpdatedMaterialLibrary" />
    <rename-pop
        v-if="showRenameGroupPopup"
        ref="renameGroupPopupRef"
        :title="isAddingGroup ? '添加分组' : '重命名分组'"
        :fetch-fn="isAddingGroup ? addMaterialLibraryGroup : updateMaterialLibraryGroup"
        @close="showRenameGroupPopup = false"
        @success="getUpdatedMaterialLibraryGroup" />
</template>

<script setup lang="ts">
import { createReusableTemplate } from "@vueuse/core";
import { uploadImage, videoTranscode } from "@/api/app";
import {
    getMaterialLibraryList,
    deleteMaterialLibrary,
    addMaterialLibrary,
    updateMaterialLibrary,
    getMaterialLibraryGroupList,
    updateMaterialLibraryGroup,
    addMaterialLibraryGroup,
    deleteMaterialLibraryGroup,
    batchUpdateMaterialToGroup,
} from "@/api/material";
import { AppTypeEnum } from "@/enums/appEnums";
import { HandleMenuType } from "@/components/handle-menu/typings";
import { MaterialTypeEnum } from "../../_enums";

// ==================== 模板复用 ====================
const [DefineMaterialCard, ReuseMaterialCard] = createReusableTemplate();

// ==================== 基础配置 ====================
const accept = "video/*,image/*,.mp3,.wav,.m4a";
const materialLibraryRef = shallowRef();

// ==================== 响应式状态 ====================
// 查询参数
const queryParams = reactive({
    name: "",
    page_no: 1,
    page_size: 20,
    m_type: "",
    field: "",
    order_by: "",
    group_id: "",
});

// UI 状态
const materialLoading = ref(false);
const loadingText = ref("正在加载素材...");
const fieldValue = ref("");

// 批量操作状态
const batchMode = ref(false);
const selectedItems = ref<number[]>([]);

// 弹窗状态
const batchTransferVisible = ref(false);
const targetGroupId = ref<string | number>();
const transferLoading = ref(false);
const batchTransferPopupRef = shallowRef();

// 重命名弹窗状态
const showRenamePopup = ref(false);
const renamePopupRef = shallowRef();
const showRenameGroupPopup = ref(false);
const renameGroupPopupRef = shallowRef();
const isAddingGroup = ref(false);

// 预览状态
const showPreviewVideo = ref(false);
const showPreviewAudio = ref(false);
const previewVideoRef = ref();
const previewAudioRef = ref();

// 上传锁定
const uploadLockTimer = ref<NodeJS.Timeout>();
const uploadLock = ref(false);

// ==================== 分页和数据获取 ====================
// 素材列表
const {
    pager,
    getLists: originalGetLists,
    resetPage: originalResetPage,
} = usePaging({
    fetchFun: getMaterialLibraryList,
    params: queryParams,
    isScroll: true,
});

// 分组列表
const groupParams = reactive({ page_no: 1, page_size: 25000 });
const { pager: groupPager, getLists: getGroupLists } = usePaging({
    fetchFun: getMaterialLibraryGroupList,
    params: groupParams,
});

// ==================== 计算属性 ====================
const groupLists = computed(() => [{ id: "", name: "全部", material_count: pager.count }, ...groupPager.lists]);

const selectedMaterials = computed(() => pager.lists.filter((item) => selectedItems.value.includes(item.id)));

const isAllSelected = computed(() => pager.lists.length > 0 && selectedItems.value.length === pager.lists.length);

// ==================== Loading 控制方法 ====================
const showLoading = (text: string) => {
    materialLoading.value = true;
    loadingText.value = text;
};

const hideLoading = () => {
    setTimeout(() => {
        materialLoading.value = false;
        loadingText.value = "";
    }, 300);
};

const getLists = async (showLoadingState = false, text = "正在加载素材...") => {
    if (showLoadingState) showLoading(text);
    try {
        await originalGetLists();
    } finally {
        if (showLoadingState) hideLoading();
    }
};

const resetPage = async (showLoadingState = false, text = "正在刷新素材...") => {
    if (showLoadingState) showLoading(text);
    try {
        await originalResetPage();
    } finally {
        if (showLoadingState) hideLoading();
    }
};

const refreshMaterials = (text = "正在刷新素材...") => resetPage(true, text);

// ==================== 事件处理方法 ====================
// 搜索和筛选
const handleSearch = () => refreshMaterials("正在搜索素材...");
const handleSearchClear = () => refreshMaterials("正在重置搜索...");
const handleFilterChange = () => refreshMaterials("正在筛选素材...");

const handleSortChange = (value: any) => {
    const sortConfig = {
        1: { order_by: "desc", field: "create_time" },
        2: { order_by: "asc", field: "create_time" },
        3: { order_by: "desc", field: "size" },
        4: { order_by: "asc", field: "size" },
    };

    const config = sortConfig[value as keyof typeof sortConfig];
    if (config) {
        Object.assign(queryParams, config);
    }
    refreshMaterials("正在重新排序...");
};

// 分组操作
const handleGroupClick = (group: any) => {
    queryParams.group_id = group.id;
    refreshMaterials("正在切换分组...");
    if (batchMode.value) cancelBatchMode();
};

// ==================== 批量操作方法 ====================
const toggleBatchMode = () => {
    batchMode.value = true;
    selectedItems.value = [];
};

const cancelBatchMode = () => {
    batchMode.value = false;
    selectedItems.value = [];
};

const toggleItemSelection = (itemId: number) => {
    const index = selectedItems.value.indexOf(itemId);
    if (index > -1) {
        selectedItems.value.splice(index, 1);
    } else {
        selectedItems.value.push(itemId);
    }
};

const selectAll = () => {
    selectedItems.value = isAllSelected.value ? [] : pager.lists.map((item) => item.id);
};

const clearSelection = () => {
    selectedItems.value = [];
};

// ==================== 删除操作 ====================
const confirmDelete = async (ids: number[]) => {
    try {
        await deleteMaterialLibrary({ id: ids });
        pager.lists = pager.lists.filter((item) => !ids.includes(item.id));
        feedback.msgSuccess("删除成功");
        await refreshMaterials("正在更新素材列表...");
    } catch (error) {
        feedback.msgWarning(error);
    }
};

const handleBatchDelete = () => {
    useNuxtApp().$confirm({
        message: `确定删除选中的 ${selectedItems.value.length} 个素材吗？`,
        onConfirm: () => confirmDelete(selectedItems.value),
    });
};

// ==================== 批量转移 ====================
const showBatchTransferDialog = async () => {
    targetGroupId.value = "";
    batchTransferVisible.value = true;
    await nextTick();
    batchTransferPopupRef.value.open();
};

const confirmBatchTransfer = async () => {
    if (!targetGroupId.value && targetGroupId.value !== "") {
        feedback.msgWarning("请选择目标分组");
        return;
    }

    transferLoading.value = true;
    try {
        await batchUpdateMaterialToGroup({
            ids: selectedItems.value,
            group_id: targetGroupId.value,
        });
        feedback.msgSuccess(`成功转移 ${selectedItems.value.length} 个素材`);
        await refreshMaterials("正在更新素材列表...");
        batchTransferVisible.value = false;
        cancelBatchMode();
    } catch (error) {
        feedback.msgError(error);
    } finally {
        transferLoading.value = false;
    }
};

const handleRemoveMaterial = (itemId: number) => {
    selectedItems.value = selectedItems.value.filter((item) => item !== itemId);
};

// ==================== 上传处理 ====================
const handleUploadSuccess = async (result: any) => {
    try {
        const {
            name,
            size,
            response,
            raw: { type },
        } = result;
        const { uri } = response.data;

        const isVideo = type.includes("video");
        const isImage = type.includes("image");
        const isAudio = type.includes("audio");

        const params = {
            name: name.split(".")[0],
            size,
            type: AppTypeEnum.XHS,
            sort: 0,
            pic: "",
            m_type: isImage ? MaterialTypeEnum.IMAGE : isAudio ? MaterialTypeEnum.MUSIC : MaterialTypeEnum.VIDEO,
            content: uri,
            duration: 0,
            group_id: queryParams.group_id,
        };

        if (isVideo) {
            try {
                const { duration, file } = await getVideoFirstFrame(uri);
                const res = await uploadImage({ file });
                params.duration = duration;
                params.pic = res.uri;
                videoTranscode({ uri: res.uri });
            } catch (error) {
                console.warn("视频处理失败:", error);
            }
        }

        await addMaterialLibrary(params);

        if (!uploadLock.value) {
            uploadLock.value = true;
            uploadLockTimer.value = setTimeout(() => {
                refreshMaterials("正在更新素材列表...");
                clearTimeout(uploadLockTimer.value);
                uploadLock.value = false;
            }, 500);
        }
    } catch (error) {
        feedback.msgError("上传失败");
    }
};

// ==================== 预览和播放 ====================
const handlePlay = async (data: any) => {
    const { m_type, content } = data;
    if (m_type === MaterialTypeEnum.VIDEO) {
        showPreviewVideo.value = true;
        await nextTick();
        previewVideoRef.value.open();
        previewVideoRef.value.setUrl(content);
    } else if (m_type === MaterialTypeEnum.MUSIC) {
        showPreviewAudio.value = true;
        await nextTick();
        previewAudioRef.value.open();
        previewAudioRef.value.setUrl(content);
    }
};

// ==================== 分组管理 ====================
const handleAddGroup = async () => {
    isAddingGroup.value = true;
    showRenameGroupPopup.value = true;
    await nextTick();
    renameGroupPopupRef.value.open();
    renameGroupPopupRef.value.setFormData({ id: undefined, name: "" });
};

const handleRenameGroup = async (group: any) => {
    isAddingGroup.value = false;
    showRenameGroupPopup.value = true;
    await nextTick();
    renameGroupPopupRef.value.open();
    renameGroupPopupRef.value.setFormData({ id: group.id, name: group.name });
};

const handleDeleteGroup = (group: any) => {
    useNuxtApp().$confirm({
        message: `确定删除该分组吗？`,
        onConfirm: async () => {
            try {
                await deleteMaterialLibraryGroup({ id: group.id });
                const index = groupPager.lists.findIndex((item) => item.id === group.id);
                if (queryParams.group_id === group.id) {
                    queryParams.group_id = "";
                }
                groupPager.lists.splice(index, 1);
                feedback.msgSuccess("删除成功");
                await refreshMaterials("正在更新素材列表...");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

// ==================== 更新回调 ====================
const getUpdatedMaterialLibrary = (data: any) => {
    const item = pager.lists.find((item) => item.id === data.id);
    if (item) item.name = data.name;
};

const getUpdatedMaterialLibraryGroup = (data: any) => {
    if (isAddingGroup.value) {
        groupPager.lists.push(data);
    } else {
        const item = groupPager.lists.find((item) => item.id === data.id);
        if (item) item.name = data.name;
    }
};

// ==================== 其他方法 ====================
const load = async (e: any) => {
    if (e === "bottom" && pager.isLoad && !pager.loading) {
        queryParams.page_no++;
        await getLists();
    }
};

// ==================== 菜单配置 ====================
const utilsMenuList: HandleMenuType[] = [
    {
        label: "重命名",
        icon: "local-icon-edit3",
        click: async (data) => {
            showRenamePopup.value = true;
            await nextTick();
            renamePopupRef.value.open();
            renamePopupRef.value.setFormData({ id: data.id, name: data.name });
        },
    },
    {
        label: "下载素材",
        icon: "local-icon-download",
        click: ({ content }) => downloadFile(content),
    },
    {
        label: "删除素材",
        icon: "local-icon-delete",
        click: ({ id }) => {
            useNuxtApp().$confirm({
                message: `确定删除该素材吗？`,
                onConfirm: () => confirmDelete([id]),
            });
        },
    },
];

// ==================== 初始化 ====================
onMounted(async () => {
    await Promise.all([getLists(true, "正在加载素材..."), getGroupLists()]);
});
</script>

<style scoped lang="scss">
:deep(.search-input) {
    .el-input__wrapper {
        background: transparent !important;
        box-shadow: none !important;
        padding-left: 10px;
    }
    .el-input__inner {
        font-weight: 600;
        font-size: 13px;
        color: #1e293b;
        &::placeholder {
            color: #94a3b8;
        }
    }
}

:deep(.custom-select-pill) {
    .el-select__wrapper {
        border-radius: 99px !important;
        height: 40px !important;
        box-shadow: 0 0 0 1px #e2e8f0 inset !important;
        &.is-focus {
            box-shadow: 0 0 0 1px #4f46e5 inset !important;
        }
    }
}

.premium-add-btn {
    @apply relative w-full rounded-xl overflow-hidden transition-all duration-300 shadow-light shadow-[#0065fb]/20 active:scale-[0.97];

    &:hover {
        @apply shadow-light shadow-[#0065fb]/30 -translate-y-0.5;
    }

    span {
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes bounce {
    0%,
    80%,
    100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-4px);
    }
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
