<template>
    <div
        class="flex flex-col h-full overflow-y-auto"
        :infinite-scroll-immediate="false"
        :infinite-scroll-disabled="!pager.isLoad"
        :infinite-scroll-distance="10"
        v-infinite-scroll="load">
        <div class="text-[20px] font-bold p-4">思维导图</div>
        <div class="grow flex flex-col min-h-0">
            <div class="px-4 pb-4">
                <div
                    class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5"
                    v-loading="pager.loading">
                    <ElCard
                        shadow="never"
                        class="!rounded-xl bg-white relative hover:scale-[1.02] hover:shadow-[0_14px_24px_0_rgba(0,0,0,0.05)]">
                        <router-link
                            to="/app/mind_map/editor"
                            class="flex w-full h-72 items-center justify-center flex-col gap-4 cursor-pointer">
                            <Icon name="el-icon-Plus" color="var(--color-primary)" :size="32"></Icon>
                            <div class="text-primary">新增思维导图</div>
                        </router-link>
                    </ElCard>
                    <ElCard
                        shadow="never"
                        class="cursor-pointer !rounded-xl bg-white relative hover:scale-[1.02] hover:shadow-[0_14px_24px_0_rgba(0,0,0,0.05)] !p-0 group"
                        v-for="(item, index) in pager.lists"
                        :class="[activeMindMap == item.id ? 'shadow-[0_14px_24px_0_rgba(0,0,0,0.05)]' : '']"
                        :key="index">
                        <router-link
                            :to="`/app/mind_map/editor?id=${item.id}`"
                            class="w-full flex-grow h-72 group flex flex-col">
                            <div class="bg-[#F2F2F2] grow w-full relative">
                                <svg ref="mindMapContainer" class="w-full h-full"></svg>
                                <div class="absolute w-full h-full z-20 top-0 left-0"></div>
                            </div>
                            <div class="p-3">
                                <div class="line-clamp-1 font-semibold pr-3 flex-shrink-0">
                                    {{ item.ask }}
                                </div>
                                <div class="text-tx-primary text-sm">
                                    {{ item.create_time }}
                                </div>
                            </div>
                        </router-link>
                        <div
                            class="h-full absolute top-4 right-2 z-50 invisible group-hover:visible"
                            :class="[activeMindMap == item.id ? '!visible' : '']">
                            <ElPopover
                                :show-arrow="false"
                                popper-class="!w-[120px] !min-w-[120px] !p-[6px] !rounded-xl"
                                @show="visibleChange(true, item.id)"
                                @hide="visibleChange(false, item.id)">
                                <template #reference>
                                    <div class="rotate-90 origin-center p-1">
                                        <Icon name="el-icon-MoreFilled"></Icon>
                                    </div>
                                </template>
                                <div class="flex flex-col gap-2">
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg"
                                        @click="handleExport(index)">
                                        <ElButton link :icon="Download"> 导出文件 </ElButton>
                                    </div>
                                    <div class="px-2 py-1 hover:bg-primary-light-9 rounded-lg">
                                        <ElButton link :icon="DocumentAdd" @click="handleKnbBind(item)">
                                            训练知识库
                                        </ElButton>
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg"
                                        @click="handleDelete(item.id, index)">
                                        <ElButton link :icon="Delete"> 删除 </ElButton>
                                    </div>
                                </div>
                            </ElPopover>
                        </div>
                    </ElCard>
                </div>
            </div>
            <div v-if="!pager.isLoad" class="text-center py-4 text-gray-500">暂无更多了</div>
        </div>
        <FixedBtns :show-add="queryParams.page_no > 1" @add="handleAdd" @back="handleBack" @refresh="handleRefresh" />
    </div>
    <KnbBind ref="knbBindRef" v-if="showKnbBind" @close="showKnbBind = false" />
    <div class="w-full h-full fixed top-0 left-[9000px] opacity-0" v-if="showExport">
        <svg ref="exportSvg" class="w-full h-full"></svg>
    </div>
</template>

<script setup lang="ts">
import { Transformer } from "markmap-lib";
import { Markmap } from "markmap-view";
import { mindMapLists, mindMapDelete } from "@/api/mind_map";
import { Delete, DocumentAdd, Download } from "@element-plus/icons-vue";
import FixedBtns from "../_components/fixed-btns.vue";
import KnbBind from "@/components/knb-bind/index.vue";

const router = useRouter();
const nuxtApp = useNuxtApp();

const activeMindMap = ref<number | undefined>();

const queryParams = reactive({
    page_no: 1,
    page_size: 15,
});

const { getLists, pager } = usePaging({
    fetchFun: mindMapLists,
    params: queryParams,
    isScroll: true,
});

const handleAdd = () => {
    router.push("/app/mind_map/editor");
};

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

const handleBack = () => {
    router.back();
};

const handleRefresh = async () => {
    pager.lists = [];
    await getLists();
    await nextTick();
    initMindMap();
};

const visibleChange = (flag: boolean, id: number) => {
    if (!flag) {
        activeMindMap.value = undefined;
    } else {
        activeMindMap.value = id;
    }
};

const handleDelete = async (id: number | string, index: number) => {
    await nuxtApp.$confirm({
        message: "确定删除此思维导图吗？",
        onConfirm: async () => {
            await mindMapDelete({ id });
            feedback.msgSuccess("删除成功");
            pager.lists.splice(index, 1);
        },
    });
};

const mindMapContainer = shallowRef<SVGSVGElement[]>([]);
const { createCanvasPng } = useMindMap();

const showExport = ref(false);
const exportSvg = ref<SVGSVGElement>(null);

const handleExport = async (index: number) => {
    showExport.value = true;
    feedback.loading("生成中...");
    await nextTick();
    const mm = await createMindMap(exportSvg.value, pager.lists[index].reply);
    mm.fit();
    setTimeout(() => {
        createCanvasPng(exportSvg.value);
        showExport.value = false;
        feedback.closeLoading();
    }, 1000);
};

const createMindMap = async (dom: SVGSVGElement, value?: string) => {
    let markmap = Markmap.create(dom);
    await nextTick();
    const transformer = new Transformer();
    const { root } = transformer.transform(value);
    markmap.setData(root);
    setTimeout(() => {
        markmap.fit();
    }, 100);
    return markmap;
};

const initMindMap = async (value?: string) => {
    pager.lists.forEach(async (item, index) => {
        const markmap = await createMindMap(mindMapContainer.value[index], item.reply);
    });
};

const isFinish = ref(false);
const load = async () => {
    queryParams.page_no += 1;
    const data = await getLists();
};

onMounted(async () => {
    await getLists();
    await nextTick();
    initMindMap();
});
</script>

<style scoped lang="scss">
.search {
    :deep(.el-input__wrapper) {
        @apply pl-10 py-2;
    }
}

:deep(.el-card__body) {
    padding: 0;
}
</style>
