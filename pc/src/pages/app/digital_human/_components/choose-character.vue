<template>
    <popup
        ref="popupRef"
        width="560px"
        top="10vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-User" :size="18" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight">历史人设</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1"
                            >Select History Character</span
                        >
                    </div>
                </div>
                <div class="w-9 h-9" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="flex-1 min-h-0 overflow-hidden p-6">
                <div v-if="pager.loading && pager.lists.length === 0" class="flex flex-col gap-3">
                    <div v-for="i in 5" :key="i" class="h-[80px] rounded-2xl bg-slate-100 animate-pulse" />
                </div>

                <div
                    v-else-if="!pager.loading && pager.lists.length === 0"
                    class="flex flex-col items-center justify-center py-16 gap-4">
                    <div
                        class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100">
                        <Icon name="el-icon-User" color="#cbd5e1" :size="32" />
                    </div>
                    <span class="text-sm font-black text-slate-300 uppercase tracking-wider">暂无历史人设</span>
                </div>

                <ElScrollbar v-else class="h-full" max-height="520px" :distance="20" @end-reached="loadMore">
                    <div class="flex flex-col gap-3 pb-2 pr-1">
                        <div
                            v-for="(item, index) in pager.lists"
                            :key="item.id ?? index"
                            class="group relative flex flex-col gap-1.5 p-4 rounded-2xl border-2 border-slate-50 bg-white hover:border-[#0065fb]/30 hover:shadow-md hover:shadow-[#0065fb]/5 transition-all cursor-pointer"
                            @click="handleSelect(item)">
                            <div class="flex items-center justify-between pr-6">
                                <span class="text-sm font-[1000] text-slate-800 truncate">{{ item.name }}</span>
                                <div class="inline-flex items-center px-2 py-0.5 bg-[#0065fb]/8 rounded-full shrink-0">
                                    <span class="text-[10px] font-black text-primary uppercase tracking-wider"
                                        >人设</span
                                    >
                                </div>
                            </div>

                            <p class="text-xs text-slate-400 font-medium leading-relaxed line-clamp-2 pr-2">
                                {{ item.introduced || "暂无简介" }}
                            </p>

                            <button
                                class="absolute top-3 right-3 w-6 h-6 rounded-full bg-slate-100 hover:bg-red-50 hover:text-red-400 text-slate-300 flex items-center justify-center transition-all opacity-0 group-hover:opacity-100"
                                @click.stop="handleDelete(item.id)">
                                <Icon name="el-icon-Close" :size="12" />
                            </button>
                        </div>
                    </div>

                    <load-text :is-load="pager.isLoad" />
                </ElScrollbar>
            </div>

            <div class="px-6 py-4 border-t border-slate-50 flex items-center justify-between shrink-0 bg-white">
                <span class="text-[12px] font-black text-slate-300 uppercase tracking-wider">
                    共 {{ pager.count ?? pager.lists.length }} 条人设
                </span>
                <button
                    @click="close"
                    class="px-6 h-10 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
                    关闭
                </button>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getShanjianPersonList, deleteShanjianPerson } from "@/api/digital_human";

const emit = defineEmits<{
    (e: "select", value: any): void;
}>();

const popupRef = shallowRef();

const commonParams = reactive({ page_no: 1, page_size: 15 });

const { getLists, pager } = usePaging({
    fetchFun: (params: any) => getShanjianPersonList(params),
    params: commonParams,
    isScroll: true,
});

const loadMore = (e: string) => {
    if (e === "bottom" && pager.isLoad && !pager.loading) {
        commonParams.page_no++;
        getLists();
    }
};

const handleSelect = (item: any) => {
    emit("select", item);
    close();
};

const handleDelete = async (id: number) => {
    useNuxtApp().$confirm({
        message: "确定删除该人设吗？",
        onConfirm: async () => {
            try {
                await deleteShanjianPerson({ id });
                const idx = pager.lists.findIndex((i: any) => i.id === id);
                if (idx > -1) pager.lists.splice(idx, 1);
            } catch (e) {
                console.error("删除人设失败:", e);
            }
        },
    });
};

const open = () => {
    popupRef.value?.open();
    getLists();
};

const close = () => {
    popupRef.value?.close();
};

defineExpose({ open, close });
</script>
