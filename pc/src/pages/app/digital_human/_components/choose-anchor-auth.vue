<template>
    <popup
        ref="popupRef"
        width="780px"
        top="8vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col" style="max-height: 80vh">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-VideoCamera" :size="20" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none">选择授权视频</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1">
                            Select Authorized Video
                        </span>
                    </div>
                </div>
                <div class="w-9 h-9" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="flex-1 min-h-0 p-6 overflow-hidden flex flex-col gap-4">
                <div class="flex items-center justify-between px-1">
                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">视频库</span>
                    <span class="text-[11px] text-slate-300 font-bold tracking-tight">
                        共 {{ pager.count }} 个可用
                    </span>
                </div>

                <ElScrollbar class="flex-1" max-height="520px" :distance="20" @end-reached="load">
                    <div v-if="pager.lists.length > 0" class="grid grid-cols-4 gap-3">
                        <div
                            v-for="item in pager.lists"
                            :key="item.id"
                            @click="handleChoose(item)"
                            :class="[
                                'group aspect-[3/4] relative rounded-xl overflow-hidden cursor-pointer border-2 transition-all',
                                isChoose(item)
                                    ? 'border-primary shadow-[0_0_0_3px_rgba(0,101,251,0.15)]'
                                    : 'border-[transparent] hover:border-slate-200',
                            ]">
                            <ElImage :src="item.authorized_pic" class="w-full h-full" fit="cover">
                                <template #error>
                                    <div class="w-full h-full bg-slate-100 flex items-center justify-center">
                                        <Icon name="el-icon-Picture" :size="32" class="text-slate-300" />
                                    </div>
                                </template>
                            </ElImage>

                            <div
                                class="absolute inset-0 bg-[#000000]/30 flex items-center justify-center transition-opacity"
                                :class="isChoose(item) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"></div>

                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-10 h-10" @click.stop="handlePreviewVideo(item.authorized_url)">
                                    <play-btn />
                                </div>
                            </div>

                            <div
                                v-if="isChoose(item)"
                                class="absolute top-2 right-2 w-6 h-6 rounded-full bg-primary flex items-center justify-center">
                                <Icon name="el-icon-Check" color="#fff" :size="13" />
                            </div>

                            <div
                                class="absolute bottom-0 left-0 right-0 px-2 py-1.5 bg-gradient-to-t from-[#000000]/60 to-[transparent]">
                                <span class="text-white text-[11px] font-bold truncate block">
                                    {{ item.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="!pager.loading"
                        class="flex flex-col items-center justify-center py-16 text-slate-300">
                        <Icon name="el-icon-VideoCamera" :size="48" />
                        <span class="mt-3 text-sm font-bold">暂无授权视频</span>
                    </div>

                    <load-text :is-load="pager.isLoad"></load-text>
                </ElScrollbar>
            </div>

            <div
                class="px-8 py-6 border-t border-slate-50 flex items-center justify-between shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.01)]">
                <div class="text-[12px] font-black text-slate-400">
                    <span v-if="chooseVideo.id">
                        已选 <span class="text-primary">1</span> 个视频
                        <span class="text-slate-300 ml-2 font-normal">{{ chooseVideo.name }}</span>
                    </span>
                    <span v-else>未选择任何视频</span>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="close"
                        class="px-6 h-11 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
                        取消
                    </button>
                    <button
                        @click="handleConfirm"
                        :disabled="!chooseVideo.id"
                        :class="[
                            'px-10 h-11 rounded-xl text-white text-sm font-[1000] transition-all',
                            chooseVideo.id
                                ? 'bg-primary shadow-[#0065fb]/20 hover:bg-[#0056d6] hover:scale-[1.02] active:scale-95 cursor-pointer'
                                : 'bg-slate-200 cursor-not-allowed',
                        ]">
                        确定使用
                    </button>
                </div>
            </div>
        </div>
    </popup>
    <preview-video v-if="showVideoPreview" ref="previewVideoRef" @close="showVideoPreview = false" />
</template>

<script setup lang="ts">
import { shanjianAnchorAuthorizedList } from "@/api/digital_human";

const emit = defineEmits<{
    (e: "confirm", data: { url: string; pic: string; name: string }): void;
    (e: "close"): void;
}>();

const popupRef = shallowRef();
const chooseVideo = ref<any>({});

const previewVideoRef = shallowRef();
const showVideoPreview = ref(false);

const queryParams = reactive({
    page_no: 1,
});

const { getLists, pager, resetPage } = usePaging({
    fetchFun: shanjianAnchorAuthorizedList,
    params: queryParams,
    isScroll: true,
});

const load = async (e: string) => {
    if (e === "bottom" && pager.isLoad && !pager.loading) {
        queryParams.page_no++;
        await getLists();
    }
};

const isChoose = (item: any) => chooseVideo.value.id === item.id;

const handleChoose = (item: any) => {
    if (chooseVideo.value.id === item.id) {
        chooseVideo.value = {};
    } else {
        chooseVideo.value = item;
    }
};

const handlePreviewVideo = async (url: string) => {
    if (!url) return;
    showVideoPreview.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};

const open = () => {
    popupRef.value?.open();
    resetPage();
};

const close = () => {
    emit("close");
    popupRef.value?.close();
};

const handleConfirm = () => {
    if (!chooseVideo.value.id) return;
    emit("confirm", {
        url: chooseVideo.value.authorized_url,
        pic: chooseVideo.value.authorized_pic,
        name: chooseVideo.value.name,
    });
    close();
};

defineExpose({
    open,
    close,
    setSelected: (val: any) => {
        chooseVideo.value = val ?? {};
    },
});
</script>
