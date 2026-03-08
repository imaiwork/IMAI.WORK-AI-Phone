<template>
    <div class="h-full flex flex-col bg-white rounded-[24px] overflow-hidden border border-br min-w-[1000px]">
        <div class="flex-shrink-0 px-6 border-b border-br bg-white">
            <div class="flex items-center justify-between h-[80px]">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <Icon name="el-icon-Folder" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">数字人视频</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            总数字人视频: {{ pager.count }} 个
                        </div>
                    </div>
                </div>
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
                        class="!rounded-full !h-[32px] !px-4 !text-xs !font-medium"
                        @click="resetPage">
                        搜索
                    </ElButton>
                </div>
            </div>
        </div>
        <div class="grow min-h-0 bg-slate-50" v-spin="{ show: loading, text: '加载中...' }">
            <ElScrollbar :distance="20" @end-reached="load">
                <div class="h-full p-6">
                    <div v-if="pager.lists.length">
                        <div class="grid grid-cols-4 gap-4">
                            <div v-for="(item, index) in pager.lists" class="material-item group" :key="index">
                                <video-card
                                    :item="item"
                                    @edit="handleEdit"
                                    @delete="handleDelete"
                                    @preview="handlePreviewVideo"></video-card>
                            </div>
                        </div>
                        <load-text :is-load="pager.isLoad" />
                    </div>
                    <div v-else class="h-[600px] flex flex-col items-center justify-center">
                        <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-4">
                            <Icon name="el-icon-FolderOpened" :size="40" color="#CBD5E1"></Icon>
                        </div>
                        <p class="text-[#94A3B8] text-sm font-medium">暂无数字人视频</p>
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <rename-pop
        v-if="showRenamePopup"
        ref="renamePopupRef"
        :fetch-fn="updateDigitalHumanVideo"
        @close="showRenamePopup = false"
        @success="getUpdatedDigitalHumanVideo" />
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
</template>

<script setup lang="ts">
import { getDigitalHumanVideo, deleteDigitalHumanVideo, updateDigitalHumanVideo } from "@/api/matrix";
import VideoCard from "../../_components/dh-video-card.vue";

const loading = ref<boolean>(true);

const queryParams = reactive({
    name: "",
    page_no: 1,
    auto_type: 0,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getDigitalHumanVideo,
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

const getUpdatedDigitalHumanVideo = async (data: any) => {
    pager.lists.forEach((item) => {
        if (item.id === data.id) {
            item.name = data.name;
        }
    });
};

const showRenamePopup = ref(false);
const renamePopupRef = shallowRef();

const handleEdit = async (item: any) => {
    showRenamePopup.value = true;
    await nextTick();
    renamePopupRef.value?.open();
    renamePopupRef.value?.setFormData({ id: item.id, name: item.name });
};

const handleDelete = async ({ id }: any) => {
    try {
        await deleteDigitalHumanVideo({ id });
        const index = pager.lists.findIndex((item) => item.id === id);
        pager.lists.splice(index, 1);
    } catch (error) {
        feedback.msgWarning(error);
    }
};

const previewVideoRef = ref();
const showPreviewVideo = ref(false);
const handlePreviewVideo = async (url: string) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};

const init = async () => {
    try {
        await getLists();
    } finally {
        loading.value = false;
    }
};

init();
</script>

<style scoped lang="scss">
.material-item {
    @apply flex gap-x-4 aspect-[3/4] relative overflow-hidden border border-[#ffffff33] rounded-xl cursor-pointer;
    &::after {
        @apply absolute top-0 left-0 w-full h-full;
        content: "";
        background: linear-gradient(180deg, rgba(0, 0, 0, 0) 50%, #000 100%);
        pointer-events: none;
    }
}
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
</style>
