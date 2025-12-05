<template>
    <popup
        ref="popupRef"
        width="628px"
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
            <div class="flex items-center justify-between h-[50px] px-4">
                <div class="flex items-center gap-x-2">
                    <div class="w-6 h-6 flex items-center justify-center rounded-md border border-[#ffffff1a]">
                        <Icon name="local-icon-windows" :size="14"></Icon>
                    </div>
                    <div class="text-[20px] text-white font-bold">视频列表</div>
                </div>
                <div class="w-6 h-6" @click="close">
                    <close-btn />
                </div>
            </div>
            <div
                class="h-[600px] overflow-y-auto relative dynamic-scroller"
                :infinite-scroll-immediate="false"
                :infinite-scroll-disabled="!pager.isLoad"
                :infinite-scroll-distance="10"
                v-infinite-scroll="load">
                <div class="h-full p-4" v-loading="pager.loading">
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
                        <div v-if="!pager.isLoad" class="text-white text-center text-xs w-full py-4">暂无更多了~</div>
                    </div>

                    <div v-else class="h-full flex items-center justify-center">
                        <ElEmpty description="暂无数据"></ElEmpty>
                    </div>
                </div>
            </div>
        </div>
    </popup>
    <edit-popup v-if="showEditPopup" ref="editPopupRef" @close="showEditPopup = false" @success="resetPage()" />
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false" />
</template>

<script setup lang="ts">
import { AppTypeEnum } from "@/enums/appEnums";
import { getDigitalHumanVideo, deleteDigitalHumanVideo } from "@/api/redbook";
import Popup from "@/components/popup/index.vue";
import VideoCard from "../../../_components/dh-video-card.vue";
import EditPopup from "../../generate_video/_components/edit.vue";

const emit = defineEmits<{
    (e: "close"): void;
}>();

const popupRef = ref<InstanceType<typeof Popup>>();

const queryParams = reactive<Record<string, any>>({
    type: AppTypeEnum.XHS,
    page_no: 1,
    page_size: 20,
    video_setting_id: "",
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getDigitalHumanVideo,
    params: queryParams,
    isScroll: true,
});

const load = async () => {
    queryParams.page_no += 1;
    await getLists();
};

const open = async (id: string) => {
    popupRef.value?.open();
    queryParams.video_setting_id = id;
    getLists();
};

const close = () => {
    emit("close");
};

const showEditPopup = ref(false);
const editPopupRef = ref<InstanceType<typeof EditPopup>>();

const handleEdit = async (item: any) => {
    showEditPopup.value = true;
    await nextTick();
    editPopupRef.value.open();
    editPopupRef.value.setFormData(item);
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
