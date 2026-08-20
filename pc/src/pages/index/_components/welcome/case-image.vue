<template>
    <ElDialog
        v-model="visible"
        width="720px"
        append-to-body
        :show-close="false"
        :close-on-click-modal="true"
        class="welcome-case-image-dialog"
        style="padding: 0">
        <div class="rounded-[28px] overflow-hidden bg-white relative">
            <div class="flex items-center justify-between h-[72px] px-8 border-b border-[#F1F5F9] bg-white">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#F59E0B]/10 text-[#F59E0B]">
                        <Icon name="el-icon-Star" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">优秀案例库</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Inspiration Gallery
                        </div>
                    </div>
                </div>
                <div class="w-8 h-8 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="h-[650px] bg-slate-50">
                <ElScrollbar :distance="20" @end-reached="columnLoad">
                    <div class="case-grid-pad">
                        <template v-if="isColumn">
                            <div class="grid grid-cols-3 gap-4">
                                <div
                                    class="flex flex-col gap-4"
                                    v-for="(column, colIndex) in columnLists"
                                    :key="colIndex">
                                    <div
                                        v-for="item in column"
                                        :key="item.id"
                                        class="group relative overflow-hidden rounded-[16px] bg-white border border-br transition-all hover:shadow-xl hover:shadow-[#0065fb]/10 hover:-translate-y-1">
                                        <div class="relative w-full overflow-hidden">
                                            <ElImage
                                                :src="item.pic"
                                                class="w-full h-auto block transition-transform duration-500 group-hover:scale-110"
                                                lazy>
                                            </ElImage>

                                            <div
                                                class="absolute inset-0 bg-[#000000]/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center p-4">
                                                <button type="button" class="copy-pill-btn" @click="handleChoose(item)">
                                                    <Icon name="el-icon-Check"></Icon>
                                                    <span class="ml-1.5">使用此案例</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="p-3" v-if="item.title">
                                            <p
                                                class="text-[11px] text-[#64748B] line-clamp-2 leading-relaxed font-medium">
                                                {{ item.title }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <load-text :is-load="!columnFinished"></load-text>
                        </template>

                        <div
                            v-if="!columnLoading && !isColumn"
                            class="h-[400px] flex flex-col items-center justify-center">
                            <div
                                class="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4 border border-[#F1F5F9]">
                                <Icon name="el-icon-Picture" :size="24" color="#CBD5E1"></Icon>
                            </div>
                            <p class="text-[#94A3B8] font-medium text-sm">暂无案例内容</p>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
        </div>
    </ElDialog>
</template>

<script setup lang="ts" name="WelcomeCaseImage">
import { getImagePromptList } from "@/api/drawing";
import { chunkArray } from "@/utils/util";

export type CaseImageItem = {
    id?: number | string;
    title?: string;
    pic?: string;
};

const emit = defineEmits<{
    (event: "close"): void;
    (event: "choose", value: { title: string; pic: string }): void;
}>();

const visible = ref(false);
const columnLists = ref<CaseImageItem[][]>([]);
const categoryVal = ref<number>(0);
const columnLoading = ref<boolean>(false);
const columnFinished = ref<boolean>(false);
const columnParams = reactive({
    page_no: 1,
    page_size: 20,
});
const loadedOnce = ref(false);

const isColumn = computed(() => columnLists.value.some((col) => col.length > 0));

const getColumnLists = async () => {
    try {
        columnLoading.value = true;
        const { lists } = await getImagePromptList({
            cid: categoryVal.value,
            ...columnParams,
        });
        const rows = Array.isArray(lists) ? lists : [];
        columnFinished.value = rows.length < columnParams.page_size;
        columnLists.value = columnLists.value.concat(chunkArray(rows, 3));
    } catch {
        /* ignore */
    } finally {
        columnLoading.value = false;
    }
};

const columnLoad = async (e: any) => {
    if (e == "bottom") {
        if (columnFinished.value || columnLoading.value) return;
        columnParams.page_no++;
        await getColumnLists();
    }
};

const handleChoose = (item: CaseImageItem) => {
    emit("choose", {
        title: (item.title || "").trim(),
        pic: (item.pic || "").trim(),
    });
    close();
};

const open = async () => {
    visible.value = true;
    if (!loadedOnce.value) {
        loadedOnce.value = true;
        columnParams.page_no = 1;
        columnLists.value = [];
        columnFinished.value = false;
        await getColumnLists();
    }
};

const close = () => {
    visible.value = false;
    emit("close");
};

defineExpose({
    open,
    close,
});
</script>

<style scoped lang="scss">
.case-grid-pad {
    padding: 24px 32px 32px;
    box-sizing: border-box;
}

.copy-pill-btn {
    @apply flex items-center justify-center px-4 py-2 rounded-full bg-white text-primary text-xs font-black transform translate-y-2 group-hover:translate-y-0 transition-all duration-300;
    border: none;
    cursor: pointer;
    &:hover {
        @apply bg-primary text-white;
    }
}
</style>

<style lang="scss">
.welcome-case-image-dialog.el-dialog {
    border-radius: 28px;
    overflow: hidden;
    padding: 0 !important;

    .el-dialog__header {
        display: none;
        padding: 0;
        margin: 0;
    }
    .el-dialog__body {
        padding: 0 !important;
    }
}
</style>
