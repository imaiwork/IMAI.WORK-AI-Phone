<template>
    <popup
        ref="popupRef"
        width="528px"
        class="choose-anchor-popup"
        style="
            padding: 0;
            background-color: var(--app-bg-color-2);
            box-shadow: 0px 0px 0px 1px var(--app-border-color-2);
        "
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        @close="close"
        @confirm="handleConfirm">
        <div class="">
            <div class="flex items-center justify-between h-[50px] px-4">
                <div class="flex items-center gap-x-2">
                    <div
                        class="w-6 h-6 flex items-center justify-center rounded-md border border-[rgba(255,255,255,0.1)]">
                        <Icon name="local-icon-windows" :size="14"></Icon>
                    </div>
                    <div class="text-[20px] text-white font-bold">从创建的历史形象中选择</div>
                </div>
                <div class="w-6 h-6" @click="close">
                    <close-btn />
                </div>
            </div>
            <div class="px-4 my-4">
                <div class="flex items-center rounded-full h-[50px] border border-[#2a2a2a] px-[5px]">
                    <ElInput
                        v-model="queryParams.name"
                        class="flex-1 search-input"
                        prefix-icon="el-icon-Search"
                        clearable
                        placeholder="请输入形象名称"
                        input-style="color: #ffffff"
                        @clear="resetPage"
                        @keyup.enter="resetPage"></ElInput>
                    <ElButton type="primary" class="!text-white !rounded-full !w-[116px] !h-10">搜索</ElButton>
                </div>
            </div>
            <div
                class="h-[600px] overflow-y-auto relative dynamic-scroller"
                :infinite-scroll-immediate="false"
                :infinite-scroll-disabled="!pager.isLoad"
                :infinite-scroll-distance="10"
                v-infinite-scroll="load">
                <div class="grid grid-cols-3 gap-2 p-2" v-if="pager.lists.length > 0">
                    <div v-for="item in pager.lists" :key="item.id" @click="choose(item)">
                        <div class="cursor-pointer bg-black rounded-lg w-full relative h-[210px] flex flex-col">
                            <video-item
                                :item="{
                                    id: item.id,
                                    name: item.name,
                                    pic: item.pic,
                                    status: item.status,
                                    video_url: item.url,
                                    model_version: item.model_version,
                                    remark: item.remark,
                                    create_time: item.create_time,
                                }" />
                            <div class="absolute top-2 right-2 z-[1000] w-6 h-6 rounded-full">
                                <Icon
                                    name="local-icon-success_fill"
                                    :size="20"
                                    :color="isChoose(item.id) ? 'var(--color-primary)' : '#ffffff1a'"></Icon>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="h-full flex items-center justify-center">
                    <ElEmpty description="暂无数据"></ElEmpty>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getAnchorList } from "@/api/digital_human";
import Popup from "@/components/popup/index.vue";
import VideoItem from "@/pages/app/_components/video-item.vue";

const emit = defineEmits(["close", "confirm"]);

const popupRef = ref<InstanceType<typeof Popup>>();

const queryParams = reactive({
    name: "",
    status: 1,
    page_no: 1,
    type: 0,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getAnchorList,
    params: queryParams,
    isScroll: true,
});

const currAnchor = ref<any>({});

const isChoose = (id: number) => {
    return currAnchor.value.id === id;
};

const choose = (item: any) => {
    if (isChoose(item.id) || (!item.is_vanish && item.status != 1)) {
        currAnchor.value = {};
    } else {
        currAnchor.value = item;
    }
    handleConfirm();
};

const setChooseAnchor = (item: any) => {
    currAnchor.value = item;
};

const load = async () => {
    queryParams.page_no += 1;
    await getLists();
};

const handleConfirm = () => {
    emit("confirm", currAnchor.value);
    close();
};

const open = () => {
    popupRef.value?.open();
    getLists();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
    setChooseAnchor,
});
</script>

<style lang="scss" scoped>
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

<style scoped>
.choose-anchor-popup {
    :deep() {
        .el-dialog__header,
        .el-dialog__footer {
            display: none;
            padding: 0;
        }
    }
}
</style>
