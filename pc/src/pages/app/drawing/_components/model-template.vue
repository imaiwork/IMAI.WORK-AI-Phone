<template>
    <popup
        ref="goodsCasePopRef"
        width="770px"
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        style="padding: 0; background-color: var(--app-bg-color-1)">
        <div class="py-[18px] -my-4">
            <div class="absolute top-[18px] right-[18px] w-6 h-6" @click="close">
                <close-btn></close-btn>
            </div>
            <div class="font-bold text-[20px] text-white px-[18px]">优秀案例</div>
            <div class="mt-5">
                <div class="h-[40rem] overflow-y-auto dynamic-scroller">
                    <div
                        class="grid grid-cols-4 gap-[10px] px-3"
                        v-infinite-scroll="load"
                        :infinite-scroll-disabled="!pager.isLoad"
                        :infinite-scroll-immediate="false"
                        :infinite-scroll-distance="10">
                        <div v-for="item in pager.lists" :key="item.id" class="cursor-pointer" @click="choose(item)">
                            <ElImage :src="item.result_image" class="w-full rounded-xl" lazy />
                        </div>
                    </div>
                    <div v-if="!pager.isLoad" class="text-white text-center text-xs w-full mt-4">暂无更多了~</div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getCaseLists } from "@/api/drawing";

const emit = defineEmits<{
    (event: "close"): void;
    (event: "choose", value: { case_type: number; images: string[]; text?: string }): void;
}>();

const goodsCasePopRef = shallowRef();

const queryParams = reactive({
    page_no: 1,
    page_size: 20,
    case_type: 4,
    user_type: 1,
});

const { pager, getLists } = usePaging({
    fetchFun: getCaseLists,
    params: queryParams,
    isScroll: true,
});

const load = () => {
    queryParams.page_no += 1;
    getLists();
};

const choose = (item: any) => {
    const {
        case_type,
        params: { images, text },
        result_image,
    } = item;
    emit("choose", { case_type, images, text });
    close();
};

const open = () => {
    goodsCasePopRef.value.open();
};

const close = () => {
    emit("close");
    goodsCasePopRef.value.close();
};

onMounted(() => {
    getLists();
});

defineExpose({
    open,
});
</script>

<style scoped></style>
