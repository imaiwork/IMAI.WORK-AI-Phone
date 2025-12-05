<template>
    <div class="h-full flex flex-col bg-app-bg-2 rounded-[20px]">
        <div class="flex-shrink-0 px-[14px] border-[0] border-b-[1px] border-app-border-1">
            <ElScrollbar>
                <div class="flex items-center justify-end h-[88px]">
                    <div class="flex items-center gap-[14px]">
                        <ElSelect
                            v-model="queryParams.m_type"
                            class="!w-[100px]"
                            popper-class="dark-select-popper"
                            clearable
                            :show-arrow="false"
                            :empty-values="[null, undefined]"
                            :value-on-clear="null"
                            @change="resetPage">
                            <ElOption label="全部" value=""></ElOption>
                            <ElOption label="视频" :value="MaterialTypeEnum.VIDEO"></ElOption>
                            <ElOption label="图片" :value="MaterialTypeEnum.IMAGE"></ElOption>
                            <ElOption label="音频" :value="MaterialTypeEnum.MUSIC"></ElOption>
                        </ElSelect>
                        <ElSelect
                            v-model="fieldValue"
                            class="!w-[140px]"
                            popper-class="dark-select-popper"
                            clearable
                            :show-arrow="false"
                            :empty-values="[null, undefined]"
                            :value-on-clear="null"
                            @change="changeField">
                            <ElOption label="全部" value=""></ElOption>
                            <ElOption label="最新开始排序" value="1"></ElOption>
                            <ElOption label="最早开始排序" value="2"></ElOption>
                            <ElOption label="文件从大到小" value="3"></ElOption>
                            <ElOption label="文件从小到大" value="4"></ElOption>
                        </ElSelect>
                        <ElInput
                            v-model="queryParams.name"
                            prefix-icon="el-icon-Search"
                            class="!w-[240px] search-name-input"
                            placeholder="请输入任务名称"
                            clearable
                            @clear="resetPage()"
                            @keydown.enter="resetPage()">
                            <template #append>
                                <ElButton text @click="resetPage()"> 搜索 </ElButton>
                            </template>
                        </ElInput>
                        <upload
                            type="file"
                            accept="video/*,image/*,.mp3,.wav,.m4a"
                            show-progress
                            :show-file-list="false"
                            @change="handleUploadSuccess">
                            <ElButton type="primary" class="!rounded-full !h-10 !px-4">
                                <Icon name="local-icon-add_circle" color="#ffffff"></Icon>
                                <span class="ml-2">上传素材</span>
                            </ElButton>
                        </upload>
                        <ElTooltip content="刷新">
                            <ElButton
                                circle
                                color="#1f1f1f"
                                icon="el-icon-Refresh"
                                class="!w-10 !h-10"
                                @click="resetPage()"></ElButton>
                        </ElTooltip>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div
            class="grow min-h-0 overflow-y-auto flex flex-col dynamic-scroller"
            :infinite-scroll-immediate="false"
            :infinite-scroll-disabled="!pager.isLoad"
            :infinite-scroll-distance="10"
            v-infinite-scroll="load">
            <div class="h-full p-4" v-loading="pager.loading">
                <div v-if="pager.lists.length">
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 cursor-pointer">
                        <div
                            v-for="(item, index) in pager.lists"
                            class="material-item card-gradient group"
                            :key="index">
                            <div class="w-full px-3 absolute z-[22] top-2">
                                <div class="line-clamp-1 text-white">
                                    {{ item.name }}
                                </div>
                            </div>
                            <div class="absolute bottom-2 w-full text-center px-3 text-[#ffffff80] line-clamp-1 z-[22]">
                                {{ item.create_time }}
                            </div>
                            <ElImage
                                v-if="MaterialTypeEnum.IMAGE == item.m_type"
                                class="w-full h-full"
                                fit="cover"
                                lazy
                                preview-teleported
                                :src="item.content"
                                :preview-src-list="[item.content]"></ElImage>
                            <template
                                v-if="MaterialTypeEnum.VIDEO == item.m_type || MaterialTypeEnum.MUSIC == item.m_type">
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
                                <handle-menu :theme="ThemeEnum.DARK" :data="item" :menu-list="utilsMenuList" />
                            </div>
                        </div>
                    </div>
                    <div v-if="!pager.isLoad" class="text-white text-center text-xs w-full py-4">暂无更多了~</div>
                </div>
                <div class="h-full flex items-center justify-center" v-else>
                    <upload
                        type="file"
                        accept="video/*,image/*"
                        show-progress
                        :show-file-list="false"
                        @change="handleUploadSuccess">
                        <Empty btn-text="上传素材" msg="快去上传你的素材吧" />
                    </upload>
                </div>
            </div>
        </div>
    </div>
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
    <preview-audio v-if="showPreviewAudio" ref="previewAudioRef" @close="showPreviewAudio = false" />
    <edit-popup v-if="showEditPopup" ref="editPopupRef" @close="showEditPopup = false" @success="resetPage()" />
</template>

<script setup lang="ts">
import { uploadImage } from "@/api/app";
import { AppTypeEnum, ThemeEnum } from "@/enums/appEnums";
import { HandleMenuType } from "@/components/handle-menu/typings";
import { getMaterialLibraryList, deleteMaterialLibrary, addMaterialLibrary } from "@/api/redbook";
import Empty from "@/pages/app/matrix/_components/empty.vue";
import EditPopup from "./_components/edit.vue";
import { MaterialTypeEnum } from "../../_enums";

const queryParams = reactive({
    name: "",
    page_no: 1,
    page_size: 20,
    m_type: "",
    field: "",
    order_by: "",
});

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

const load = () => {
    queryParams.page_no++;
    getLists();
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
    } catch (error) {
        feedback.msgError(error);
    }
};

const showEditPopup = ref(false);
const editPopupRef = ref<InstanceType<typeof EditPopup>>();

const utilsMenuList: HandleMenuType[] = [
    {
        label: "重命名",
        icon: "local-icon-edit3",
        click: async (data) => {
            showEditPopup.value = true;
            await nextTick();
            editPopupRef.value.open();
            editPopupRef.value.setFormData(data);
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
                theme: "dark",
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
    } else {
        showPreviewAudio.value = true;
        await nextTick();
        previewAudioRef.value.open();
        previewAudioRef.value.setUrl(content);
    }
};

getLists();
</script>

<style scoped lang="scss">
.material-item {
    @apply flex gap-x-4 h-[288px] relative overflow-hidden border border-[#ffffff33] rounded-xl;
    &::after {
        @apply absolute top-0 left-0 w-full h-full;
        content: "";
        background: linear-gradient(180deg, rgba(0, 0, 0, 0) 50%, #000 100%);
        pointer-events: none;
    }
}
</style>
