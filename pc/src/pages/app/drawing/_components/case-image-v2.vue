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
                        <template v-if="['goods', 'model'].includes(type)">
                            <div
                                v-for="item in pager.lists"
                                :key="item.id"
                                class="cursor-pointer"
                                @click="choose(item)">
                                <ElImage :src="item.result_image" class="w-full rounded-xl" lazy />
                            </div>
                        </template>
                        <template v-if="type == 'fashion'">
                            <div
                                v-for="item in pager.lists"
                                :key="item.id"
                                class="relative cursor-pointer"
                                @click="choose(item)">
                                <ElImage :src="item.result_image" class="w-full rounded-xl" lazy />
                                <div class="absolute bottom-2 left-0 w-full p-2">
                                    <div class="flex justify-around gap-2">
                                        <template v-for="img in item.params.images">
                                            <div
                                                class="w-12 h-12 rounded-md"
                                                style="
                                                    backdrop-filter: blur(6px);
                                                    box-shadow: 0px 6px 12px 0px rgba(0, 0, 0, 0.24);
                                                "
                                                v-if="img">
                                                <ElImage :src="img" fit="cover" class="w-full h-full rounded-md" lazy />
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div v-if="!pager.isLoad" class="text-white text-center text-xs w-full mt-4">暂无更多了~</div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getCaseLists } from "@/api/drawing";

const props = defineProps<{
    type: "goods" | "fashion" | "model";
}>();

const emit = defineEmits<{
    (event: "close"): void;
    (event: "choose", value: { case_type: number; data: any; text?: string }): void;
}>();

const goodsCasePopRef = shallowRef();

const queryParams = reactive({
    page_no: 1,
    page_size: 20,
    case_type: undefined,
    user_type: undefined,
});

const { pager, getLists, resetPage } = usePaging({
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
        params: { text },
    } = item;
    emit("choose", {
        case_type,
        data: item,
        text,
    });
    close();
};

const open = () => {
    goodsCasePopRef.value.open();
    if (props.type == "goods") {
        queryParams.case_type = "3";
    } else if (props.type == "model") {
        queryParams.case_type = "4";
        queryParams.user_type = "1";
    } else {
        queryParams.case_type = "0,1";
    }
    resetPage();
};

const close = () => {
    emit("close");
    goodsCasePopRef.value.close();
};

onMounted(() => {});

defineExpose({
    open,
});
</script>

<style scoped></style>
