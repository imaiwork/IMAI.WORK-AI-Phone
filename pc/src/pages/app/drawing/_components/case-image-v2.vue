<template>
    <popup
        ref="goodsCasePopRef"
        width="820px"
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0">
        <div class="rounded-[28px] overflow-hidden bg-white shadow-2xl relative">
            <div class="flex items-center justify-between h-[72px] px-8 border-b border-[#F1F5F9] bg-white">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <Icon name="el-icon-Collection" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">灵感案例库</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Premium Templates
                        </div>
                    </div>
                </div>

                <div class="w-8 h-8" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="h-[650px] bg-slate-50">
                <ElScrollbar :distance="20" @end-reached="load">
                    <div class="p-6">
                        <div class="grid grid-cols-4 gap-5">
                            <template v-for="item in pager.lists" :key="item.id">
                                <div
                                    v-if="['goods', 'model'].includes(type)"
                                    class="group relative cursor-pointer overflow-hidden rounded-2xl bg-white border border-br transition-all hover:shadow-xl hover:shadow-[#0065fb]/10 hover:-translate-y-1"
                                    @click="choose(item)">
                                    <ElImage
                                        :src="item.result_image"
                                        class="w-full h-full block transition-transform duration-500 group-hover:scale-105"
                                        lazy />
                                    <div
                                        class="absolute inset-0 bg-[#0065fb]/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <div class="px-4 py-2 bg-white rounded-full text-primary text-xs font-black">
                                            使用此案例
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="type == 'fashion'"
                                    class="group relative cursor-pointer overflow-hidden rounded-2xl bg-white border border-br transition-all hover:shadow-xl hover:shadow-[#0065fb]/10 hover:-translate-y-1"
                                    @click="choose(item)">
                                    <ElImage :src="item.result_image" class="w-full h-auto block" lazy />

                                    <div class="absolute bottom-3 left-0 w-full px-3">
                                        <div
                                            class="flex justify-center gap-1.5 p-2 rounded-xl bg-white/40 backdrop-blur-md border border-br">
                                            <template v-for="(img, idx) in item.params.images.slice(0, 3)" :key="idx">
                                                <div
                                                    class="w-10 h-10 rounded-lg overflow-hidden border border-br"
                                                    v-if="img">
                                                    <ElImage :src="img" fit="cover" class="w-full h-full" lazy />
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div
                                        class="absolute inset-0 bg-[#0065fb]/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <div
                                            class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-primary shadow-xl">
                                            <Icon name="el-icon-Check" :size="24"></Icon>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <load-text :is-load="pager.isLoad"></load-text>
                    </div>
                </ElScrollbar>
                <div
                    v-if="!pager.loading && pager.lists.length === 0"
                    class="h-[400px] flex flex-col items-center justify-center text-[#94A3B8]">
                    <Icon name="el-icon-FolderOpened" :size="48" class="mb-4 opacity-20"></Icon>
                    <p class="font-medium text-sm">库中暂无素材</p>
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

const load = async (e: any) => {
    if (e == "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no++;
        await getLists();
    }
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
