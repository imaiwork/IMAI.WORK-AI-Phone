<template>
    <popup
        ref="materialPopRef"
        width="476px"
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        style="padding: 0; background-color: var(--app-bg-color-1)">
        <div class="py-[18px] -my-4">
            <div class="absolute top-[18px] right-[18px] w-6 h-6 cursor-pointer" @click="close">
                <close-btn></close-btn>
            </div>
            <div class="font-bold text-[20px] text-white px-[18px]">优秀案例</div>
            <div class="mt-5">
                <div class="h-[40rem]">
                    <template v-if="isColumn">
                        <ElScrollbar>
                            <div
                                class="grid grid-cols-3 gap-[10px] px-3"
                                v-infinite-scroll="columnLoad"
                                :infinite-scroll-disabled="columnFinished"
                                :infinite-scroll-immediate="false"
                                :infinite-scroll-distance="10">
                                <div
                                    class="flex flex-col gap-[10px]"
                                    v-for="(value, index) in columnLists"
                                    :key="index">
                                    <div class="relative overflow-hidden rounded-lg" v-for="item in value">
                                        <ElImage :src="item.pic" class="w-full h-full min-h-[100px]" lazy> </ElImage>
                                        <ElTooltip popper-class="max-w-[300px]" :content="item.title">
                                            <div class="absolute top-2 right-2 cursor-pointer">
                                                <Icon name="local-icon-tips" color="#ffffff"></Icon>
                                            </div>
                                        </ElTooltip>
                                        <div class="absolute bottom-2 left-0 w-full flex justify-center">
                                            <button class="copy-btn" @click="handleCopy(item.title)">复制同款</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="columnFinished" class="text-white text-center text-xs w-full mt-4">
                                暂无更多了~
                            </div>
                        </ElScrollbar>
                    </template>
                    <div v-else class="flex items-center justify-center w-full h-full">
                        <ElEmpty description="空空如也"></ElEmpty>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts" name="MaterialImage">
import { getImagePromptList } from "@/api/drawing";
import { chunkArray } from "@/utils/util";

const emit = defineEmits<{
    (event: "close"): void;
    (event: "choose", value: string): void;
}>();

const materialPopRef = shallowRef();
const { copy } = useCopy();

const isColumn = computed(() => {
    const show = new Set(...columnLists.value);
    return show.size > 0;
});

const columnLists = ref<any[]>([]);
const categoryVal = ref<number>(0);
const columnLoading = ref<boolean>(false);
const columnFinished = ref<boolean>(false);
const columnParams = reactive({
    page_no: 1,
    page_size: 20,
});

const getColumnLists = async () => {
    try {
        columnLoading.value = true;
        const { lists } = await getImagePromptList({
            cid: categoryVal.value,
            ...columnParams,
        });
        columnFinished.value = lists.length < columnParams.page_size;
        columnLists.value = columnLists.value.concat(chunkArray(lists, 3));
        columnLoading.value = false;
    } catch (error) {
        columnLoading.value = false;
    }
};

const columnLoad = () => {
    if (columnLoading.value || columnFinished.value) return;
    columnParams.page_no += 1;
    getColumnLists();
};

const handleCopy = (title: string) => {
    copy(title);
    close();
    emit("choose", title);
};

const open = () => {
    materialPopRef.value.open();
};

const close = () => {
    emit("close");
    materialPopRef.value.close();
};

onMounted(() => {
    getColumnLists();
});

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
.copy-btn {
    @apply px-5 h-[34px] flex items-center justify-center rounded-full text-white;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.3) 0%, rgba(56, 56, 56, 0.3) 100%);
    box-shadow: 0px 6px 12px 0px rgba(0, 0, 0, 0.24);
    backdrop-filter: blur(6px);
}
</style>
