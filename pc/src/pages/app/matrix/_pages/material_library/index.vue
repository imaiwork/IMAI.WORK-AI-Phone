<template>
    <div class="h-full flex flex-col bg-white rounded-[20px] overflow-hidden border border-br min-w-[1000px]">
        <div class="flex-shrink-0 px-6 border-b border-br bg-white">
            <div class="flex items-center justify-between h-[80px]">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <Icon name="el-icon-Folder" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">素材管理中心</div>
                        <div class="text-[10px] text-[#94A3B8] font-bold uppercase tracking-widest">
                            Total: {{ pager.count }} Assets
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <ElSelect
                        v-model="queryParams.m_type"
                        class="!w-[110px] custom-select-pill"
                        clearable
                        placeholder="所有类型"
                        :empty-values="[null, undefined]"
                        :show-arrow="false"
                        @change="resetPage">
                        <ElOption label="全部类型" value=""></ElOption>
                        <ElOption label="视频素材" :value="MaterialTypeEnum.VIDEO"></ElOption>
                        <ElOption label="图片素材" :value="MaterialTypeEnum.IMAGE"></ElOption>
                        <ElOption label="音频素材" :value="MaterialTypeEnum.MUSIC"></ElOption>
                    </ElSelect>
                    <ElSelect
                        v-model="fieldValue"
                        class="!w-[140px] custom-select-pill"
                        clearable
                        :show-arrow="false"
                        :empty-values="[null, undefined]"
                        @change="changeField">
                        <ElOption label="全部" value=""></ElOption>
                        <ElOption label="最新开始排序" value="1"></ElOption>
                        <ElOption label="最早开始排序" value="2"></ElOption>
                        <ElOption label="文件从大到小" value="3"></ElOption>
                        <ElOption label="文件从小到大" value="4"></ElOption>
                    </ElSelect>
                    <div
                        class="flex items-center rounded-full h-[40px] border border-br px-1 transition-all focus-within:border-[#0065fb]">
                        <ElInput
                            v-model="queryParams.name"
                            class="!w-[200px] search-input"
                            clearable
                            prefix-icon="el-icon-Search"
                            placeholder="搜索素材名称..."
                            @clear="resetPage"
                            @keyup.enter="resetPage">
                        </ElInput>
                        <ElButton
                            type="primary"
                            class="!rounded-full !h-[32px] !px-4 !text-xs !font-bold"
                            @click="resetPage">
                            搜索
                        </ElButton>
                    </div>

                    <div class="w-[1px] h-6 bg-[#E2E8F0] mx-2"></div>

                    <upload
                        type="file"
                        :accept="accept"
                        show-progress
                        :max-size="200"
                        :show-file-list="false"
                        @change="handleUploadSuccess">
                        <ElButton type="primary" class="!rounded-full !h-10 !px-4">
                            <Icon name="local-icon-add_circle" color="#ffffff"></Icon>
                            <span class="ml-2">上传素材</span>
                        </ElButton>
                    </upload>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 bg-[#F8FAFC]">
            <ElScrollbar :distance="20" @end-reached="load">
                <div class="p-6">
                    <template v-if="pager.lists.length > 0">
                        <div class="grid grid-cols-4 gap-4">
                            <div
                                v-for="item in pager.lists"
                                :key="item.id"
                                class="group relative bg-white rounded-[20px] overflow-hidden border border-br transition-all hover:shadow-xl hover:shadow-[#0065fb]/10 hover:-translate-y-1">
                                <div class="aspect-video relative overflow-hidden bg-[#F1F5F9]">
                                    <ElImage
                                        v-if="MaterialTypeEnum.IMAGE == item.m_type"
                                        class="w-full h-full"
                                        fit="cover"
                                        lazy
                                        preview-teleported
                                        :src="item.content"
                                        :preview-src-list="[item.content]"></ElImage>
                                    <template
                                        v-if="
                                            MaterialTypeEnum.VIDEO == item.m_type ||
                                            MaterialTypeEnum.MUSIC == item.m_type
                                        ">
                                        <template v-if="MaterialTypeEnum.VIDEO == item.m_type">
                                            <img v-if="item.pic" :src="item.pic" class="w-full h-full object-cover" />
                                            <video v-else :src="item.content" class="w-full h-full object-cover" />
                                        </template>
                                        <img
                                            src="@/assets/images/audio_bg.png"
                                            class="w-full h-full object-cover"
                                            v-if="MaterialTypeEnum.MUSIC == item.m_type" />
                                        <div
                                            class="absolute top-[50%] left-[50%] -translate-x-[50%] -translate-y-[50%] z-[1000]">
                                            <div class="w-12 h-12" @click="handlePlay(item)">
                                                <play-btn />
                                            </div>
                                        </div>
                                    </template>

                                    <div class="absolute right-2 top-2 z-[1000] w-9 h-9 invisible group-hover:visible">
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
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] text-[#94A3B8] font-medium">
                                            {{ item.create_time }}
                                        </span>
                                        <div
                                            class="flex items-center gap-1.5 bg-[#F8FAFC] px-2 py-0.5 rounded-md border border-[#F1F5F9]">
                                            <Icon name="el-icon-Files" :size="10"></Icon>
                                            <span class="text-[10px] text-[#64748B] font-bold">
                                                {{ formatFileSize(item.size) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="pager.isLoad" />
                    </template>
                    <div v-else class="h-[600px] flex flex-col items-center justify-center">
                        <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-4">
                            <Icon name="el-icon-FolderOpened" :size="40" color="#CBD5E1"></Icon>
                        </div>
                        <p class="text-[#94A3B8] text-sm font-bold">暂无素材内容，点击右上角上传</p>
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
    <preview-audio v-if="showPreviewAudio" ref="previewAudioRef" @close="showPreviewAudio = false" />
    <rename-pop
        v-if="showRenamePopup"
        ref="renamePopupRef"
        :fetch-fn="updateMaterialLibrary"
        @close="showRenamePopup = false"
        @success="getUpdatedMaterialLibrary" />
</template>

<script setup lang="ts">
import { uploadImage } from "@/api/app";
import { AppTypeEnum } from "@/enums/appEnums";
import { HandleMenuType } from "@/components/handle-menu/typings";
import { getMaterialLibraryList, deleteMaterialLibrary, addMaterialLibrary, updateMaterialLibrary } from "@/api/matrix";
import { MaterialTypeEnum } from "../../_enums";

const queryParams = reactive({
    name: "",
    page_no: 1,
    page_size: 20,
    m_type: "",
    field: "",
    order_by: "",
});

const accept = "video/*,image/*,.mp3,.wav,.m4a";
const fieldValue = ref("");
const changeField = (data: any) => {
    if (data == 1) {
        queryParams.order_by = "desc";
        queryParams.field = "create_time";
    } else if (data == 2) {
        queryParams.order_by = "asc";
        queryParams.field = "create_time";
    } else if (data == 3) {
        queryParams.order_by = "asc";
        queryParams.field = "size";
    } else {
        queryParams.order_by = "desc";
        queryParams.field = "size";
    }
    resetPage();
};

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getMaterialLibraryList,
    params: queryParams,
    isScroll: true,
});

const load = async (e: any) => {
    if (e == "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no++;
        await getLists();
    }
};

const getUpdatedMaterialLibrary = async (data: any) => {
    pager.lists.forEach((item) => {
        if (item.id === data.id) {
            item.name = data.name;
        }
    });
};

const uploadLockTimer = ref<NodeJS.Timeout>();
const uploadLock = ref(false);

const handleUploadSuccess = async (result: any) => {
    try {
        const {
            name,
            size,
            response,
            raw: { type },
        } = result;
        const { uri } = response.data;
        // 根据名字判断是视频还是图片
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
        };
        if (isVideo) {
            try {
                const { duration, file } = await getVideoFirstFrame(uri);
                const res = await uploadImage({ file });
                params.duration = duration;
                params.pic = res.uri;
            } catch (error) {}
        }
        await addMaterialLibrary(params);
        if (uploadLock.value) return;
        uploadLock.value = true;
        uploadLockTimer.value = setTimeout(() => {
            resetPage();
            clearTimeout(uploadLockTimer.value);
            uploadLock.value = false;
        }, 500);
    } catch (error) {}
};

const showRenamePopup = ref(false);
const renamePopupRef = shallowRef();

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
        click: ({ content }) => {
            downloadFile(content);
        },
    },
    {
        label: "删除素材",
        icon: "local-icon-delete",
        click: ({ id }) => {
            useNuxtApp().$confirm({
                message: `确定删除该素材吗？`,
                onConfirm: async () => {
                    try {
                        await deleteMaterialLibrary({ id });
                        const index = pager.lists.findIndex((item) => item.id == id);
                        pager.lists.splice(index, 1);
                    } catch (error) {
                        feedback.msgWarning(error);
                    }
                },
            });
        },
    },
];

const showPreviewVideo = ref(false);
const showPreviewAudio = ref(false);
const previewVideoRef = ref();
const previewAudioRef = ref();
const handlePlay = async (data: any) => {
    const { m_type, content } = data;
    if (m_type == MaterialTypeEnum.VIDEO) {
        showPreviewVideo.value = true;
        await nextTick();
        previewVideoRef.value.open();
        previewVideoRef.value.setUrl(content);
    } else if (m_type == MaterialTypeEnum.MUSIC) {
        showPreviewAudio.value = true;
        await nextTick();
        previewAudioRef.value.open();
        previewAudioRef.value.setUrl(content);
    }
};

getLists();
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
</style>
