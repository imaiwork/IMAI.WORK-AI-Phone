<template>
    <popup
        ref="popupRef"
        width="628px"
        style="padding: 0; background-color: #f6f6f6"
        footer-class="!p-0"
        header-class="!p-0"
        cancel-button-text=""
        confirm-button-text=""
        :show-close="false"
        @close="close">
        <div class="rounded-[28px] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between h-[72px] px-8 border-b border-[#F1F5F9] bg-white">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <Icon name="local-icon-windows" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">已生成视频列表</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Video Production Results
                        </div>
                    </div>
                </div>
                <div class="w-8 h-8" @click="close">
                    <close-btn :icon-size="10" />
                </div>
            </div>
            <div class="h-[600px] bg-slate-50">
                <ElScrollbar :distance="20" @end-reached="load">
                    <div class="h-full p-4">
                        <div v-if="pager.lists.length > 0">
                            <div class="grid grid-cols-3 gap-2 p-2">
                                <div v-for="item in pager.lists" :key="item.id">
                                    <div
                                        class="cursor-pointer bg-black w-full relative h-[248px] flex flex-col overflow-hidden rounded-xl group">
                                        <video-card
                                            :item="item"
                                            @edit="handleEdit"
                                            @delete="handleDelete"
                                            @preview="handlePreviewVideo"></video-card>
                                    </div>
                                </div>
                            </div>
                            <load-text :is-load="pager.isLoad" />
                        </div>
                        <div v-else class="h-[500px] flex flex-col items-center justify-center">
                            <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-4">
                                <Icon name="el-icon-VideoCamera" :size="40" color="#CBD5E1"></Icon>
                            </div>
                            <p class="text-[#94A3B8] text-sm font-medium">暂无生成的视频记录</p>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
        </div>
    </popup>
    <rename-pop
        v-if="showRenamePopup"
        ref="renamePopupRef"
        :fetch-fn="updateDigitalHumanVideo"
        @close="showRenamePopup = false"
        @success="resetPage()" />
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
</template>

<script setup lang="ts">
import { AppTypeEnum } from "@/enums/appEnums";
import { getDigitalHumanVideo, deleteDigitalHumanVideo, updateDigitalHumanVideo } from "@/api/matrix";
import Popup from "@/components/popup/index.vue";
import VideoCard from "../../../_components/dh-video-card.vue";

const emit = defineEmits<{
    (e: "close"): void;
}>();

const popupRef = ref<InstanceType<typeof Popup>>();

const queryParams = reactive<Record<string, any>>({
    type: AppTypeEnum.XHS,
    page_no: 1,
    page_size: 20,
    video_setting_id: "",
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

const open = async ({ id, auto_type }: { id: string; auto_type: number }) => {
    popupRef.value?.open();
    queryParams.video_setting_id = id;
    queryParams.auto_type = auto_type;
    getLists();
};

const close = () => {
    emit("close");
};

const showRenamePopup = ref(false);
const renamePopupRef = shallowRef();

const handleEdit = async (item: any) => {
    showRenamePopup.value = true;
    await nextTick();
    renamePopupRef.value?.open();
    renamePopupRef.value?.setFormData(item);
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

const showPreviewVideo = ref(false);
const previewVideoRef = shallowRef();
const handlePreviewVideo = async (url: string) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};

defineExpose({
    open,
    close,
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
