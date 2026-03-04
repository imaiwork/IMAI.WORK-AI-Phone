<template>
    <div class="h-full min-w-[1000px] border border-br rounded-[20px] bg-white">
        <ElScrollbar :distance="20" @end-reached="load">
            <div class="flex flex-col p-6">
                <div class="flex items-center justify-between mb-6 px-2">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-6 bg-primary rounded-full"></div>
                        <h2 class="text-[20px] font-[900] text-[#1E293B]">我的思维导图</h2>
                    </div>
                    <div class="text-[13px] text-[#94A3B8] font-medium">共 {{ pager.count || 0 }} 份文档</div>
                </div>
                <div class="grid grid-cols-2 gap-6 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                    <div
                        class="group h-[320px] bg-white rounded-[24px] border-2 border-dashed border-br hover:border-[#0065fb] hover:bg-[#F5F7FF] transition-all duration-300 flex flex-col items-center justify-center cursor-pointer"
                        @click="$router.push('/app/internal/_mind_map/editor')">
                        <div
                            class="w-14 h-14 rounded-full bg-primary text-white flex items-center justify-center shadow-[#0065fb]/20 group-hover:scale-110 transition-transform">
                            <Icon name="el-icon-Plus" :size="28"></Icon>
                        </div>
                        <span class="mt-4 text-primary font-black text-sm tracking-wide">创建新导图</span>
                    </div>

                    <div
                        v-for="(item, index) in pager.lists"
                        :key="item.id"
                        class="group relative h-[320px] bg-white rounded-[24px] border border-[#F1F5F9] hover:shadow-[#0065fb]/10 hover:-translate-y-1 transition-all duration-300 flex flex-col overflow-hidden">
                        <div
                            class="h-[200px] bg-slate-50 relative overflow-hidden flex items-center justify-center border-b border-[#F1F5F9] cursor-pointer"
                            @click="$router.push(`/app/internal/_mind_map/editor?id=${item.id}`)">
                            <div
                                class="absolute inset-0 opacity-[0.03]"
                                style="
                                    background-image: radial-gradient(#000 1px, transparent 1px);
                                    background-size: 20px 20px;
                                "></div>

                            <svg ref="mindMapContainer" class="w-full h-full p-4 pointer-events-none"></svg>

                            <div
                                class="absolute inset-0 bg-[#0065fb]/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-t-[24px]">
                                <div
                                    class="px-4 py-1.5 bg-white/90 backdrop-blur rounded-full text-primary text-[11px] font-black">
                                    继续编辑
                                </div>
                            </div>
                        </div>

                        <div class="p-4 flex flex-col justify-between grow">
                            <div>
                                <h3
                                    class="line-clamp-1 font-black text-[#1E293B] text-[15px] mb-1 group-hover:text-primary transition-colors">
                                    {{ item.ask }}
                                </h3>
                                <div class="flex items-center gap-1.5 text-xs text-[#94A3B8] font-medium">
                                    <Icon name="el-icon-Clock" :size="12"></Icon>
                                    {{ item.create_time }}
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-[#F8FAFC]">
                                <div class="flex gap-1">
                                    <ElTooltip content="导出图片">
                                        <div class="action-btn" @click.stop="handleExport(index)">
                                            <Icon name="el-icon-Download"></Icon>
                                        </div>
                                    </ElTooltip>
                                </div>
                                <ElTooltip content="删除">
                                    <div
                                        class="action-btn hover:!bg-[#FEE2E2] hover:!text-[#EF4444]"
                                        @click.stop="handleDelete(item.id, index)">
                                        <Icon name="el-icon-Delete"></Icon>
                                    </div>
                                </ElTooltip>
                            </div>
                        </div>
                    </div>
                </div>
                <load-text :is-load="pager.isLoad"></load-text>
            </div>
        </ElScrollbar>
        <div
            v-if="!pager.loading && pager.lists.length === 0"
            class="h-[500px] flex flex-col items-center justify-center">
            <div
                class="w-20 h-20 bg-white rounded-[32px] flex items-center justify-center border border-[#F1F5F9] mb-4">
                <Icon name="el-icon-Document" :size="32" color="#CBD5E1"></Icon>
            </div>
            <p class="text-[#94A3B8] font-medium">暂无思维导图文档</p>
        </div>
    </div>

    <div class="fixed top-[-9999px] left-[-9999px] opacity-0" v-if="showExport">
        <div ref="exportContainerRef">
            <svg ref="exportSvg" class="w-[1200px] h-[800px]"></svg>
        </div>
    </div>

    <KnbBind ref="knbBindRef" v-if="showKnbBind" @close="showKnbBind = false" />
</template>

<script setup lang="ts">
import { Transformer } from "markmap-lib";
import { Markmap } from "markmap-view";
import { mindMapLists, mindMapDelete } from "@/api/mind_map";
import KnbBind from "@/components/knb-bind/index.vue";

const nuxtApp = useNuxtApp();
const queryParams = reactive({ page_no: 1, page_size: 15 });

const { getLists, pager } = usePaging({
    fetchFun: mindMapLists,
    params: queryParams,
    isScroll: true,
});

// 知识库绑定相关
const knbBindRef = ref<InstanceType<typeof KnbBind>>(null);
const showKnbBind = ref(false);
const handleKnbBind = async (item: any) => {
    showKnbBind.value = true;
    await nextTick();
    knbBindRef.value?.open();
    knbBindRef.value?.setFormData({
        type: "txt",
        fileName: item.ask,
        content: item.reply,
    });
};

// 删除逻辑
const handleDelete = async (id: number | string, index: number) => {
    await nuxtApp.$confirm({
        message: "确定要永久删除这份思维导图吗？",
        theme: "light",
        onConfirm: async () => {
            await mindMapDelete({ id });
            feedback.msgSuccess("已成功删除");
            pager.lists.splice(index, 1);
        },
    });
};

// Markmap 渲染核心逻辑
const mindMapContainer = shallowRef<SVGSVGElement[]>([]);
const { createCanvasPng } = useMindMap();
const showExport = ref(false);
const exportSvg = ref<SVGSVGElement>(null);
const exportContainerRef = ref<HTMLElement>(null);

const createMindMap = async (dom: SVGSVGElement, value?: string) => {
    if (!dom || !value) return;
    let markmap = Markmap.create(dom);
    const transformer = new Transformer();
    const { root } = transformer.transform(value);
    markmap.setData(root);
    setTimeout(() => markmap.fit(), 100);
    return markmap;
};

const initMindMap = async () => {
    await nextTick();
    pager.lists.forEach((item, index) => {
        if (mindMapContainer.value[index]) {
            createMindMap(mindMapContainer.value[index], item.reply);
        }
    });
};

const handleExport = async (index: number) => {
    showExport.value = true;
    feedback.loading("正在导出高清图片...");
    await nextTick();
    const mm = await createMindMap(exportSvg.value, pager.lists[index].reply);
    mm.fit();
    setTimeout(() => {
        createCanvasPng(exportContainerRef.value);
        showExport.value = false;
        feedback.closeLoading();
    }, 1000);
};

const load = async (e: any) => {
    if (e == "bottom" && pager.isLoad && !pager.loading) {
        queryParams.page_no++;
        await getLists();
        initMindMap();
    }
};

onMounted(async () => {
    await getLists();
    initMindMap();
});
</script>

<style scoped lang="scss">
/* 操作按钮微交互 */
.action-btn {
    @apply w-8 h-8 flex items-center justify-center rounded-lg text-[#94A3B8] hover:bg-[#F5F7FF] hover:text-primary transition-all cursor-pointer;
}

/* 隐藏 markmap 默认自带的控制条（如果预览不需要的话） */
:deep(.markmap-control-group) {
    display: none !important;
}

/* 覆盖滚动条背景 */
:deep(.el-scrollbar__view) {
    background-color: transparent;
}
</style>
