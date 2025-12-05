<template>
    <div class="h-full w-full p-4 flex flex-col" v-if="!isCreate">
        <div
            class="rounded-[20px] flex items-center gap-3 px-[30px]"
            style="
                background: linear-gradient(152deg, rgba(0, 101, 251, 0.88) -42.44%, rgba(255, 255, 255, 0) 12.19%)
                    rgb(255, 255, 255);
            ">
            <img src="@/assets/images/kb.svg" class="w-11 mt-7" />
            <div>
                <div class="text-[#000000cc]">
                    {{ ToolEnumMap[ToolEnum.DATABASE] }}
                </div>
                <div class="text-[#00000080]">打通企业知识脉络，智能检索、主动推送让知识流转如呼吸般自然简单。</div>
            </div>
        </div>
        <div class="grow min-h-0 flex flex-col bg-white rounded-[20px] mt-4">
            <ElTabs v-model="currentTab" @tab-click="handleTabClick">
                <ElTabPane v-for="item in tabs" :key="item.value" :label="item.label" :name="item.value"> </ElTabPane>
            </ElTabs>
            <div
                class="grow min-h-0 flex flex-col overflow-y-auto"
                :infinite-scroll-immediate="false"
                :infinite-scroll-disabled="!pager.isLoad"
                :infinite-scroll-distance="10"
                v-infinite-scroll="load">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-[18px] py-4 px-[20px]">
                    <div class="kb-item">
                        <div class="flex justify-between gap-x-2">
                            <div class="flex items-center gap-x-2">
                                <div
                                    class="flex-shrink-0 w-[50px] h-[50px] flex items-center justify-center rounded-md border border-[#0000001a]">
                                    <Icon name="local-icon-windows2" :size="24"></Icon>
                                </div>
                                <div>
                                    <div>知识库</div>
                                    <div class="text-[#00000080]">立即创建知识库</div>
                                </div>
                            </div>
                            <div>
                                <ElButton type="primary" class="!h-[30px]" @click="handleCreate">
                                    <span class="text-[11px]">立即创建</span>
                                </ElButton>
                            </div>
                        </div>
                        <div class="mt-3 text-[#00000080] h-[50px]">
                            导入相关文件数据，知识库将被集成到各项应用中作为上下文,或可以创建为独立的调用库使用
                        </div>
                    </div>
                    <div
                        v-for="(item, index) in pager.lists"
                        :key="index"
                        class="kb-item group"
                        @click="handleViewDetail(item)">
                        <div class="flex items-center gap-x-2">
                            <div
                                class="w-[50px] h-[50px] flex items-center justify-center rounded-md border border-[#0000001a] p-[2px]">
                                <img
                                    :src="item.image"
                                    class="w-full h-full object-cover rounded-md"
                                    v-if="item.image" />
                                <Icon name="local-icon-windows2" :size="24" v-else />
                            </div>
                            <div>
                                <div>{{ item.name }}</div>
                                <div class="text-[#00000080]">{{ item.file_counts || 0 }}文档</div>
                            </div>
                        </div>
                        <ElTooltip
                            popper-class="max-w-[400px]"
                            :content="item.intro || item.description"
                            v-if="item.is_ellipsis">
                            <div class="text-[#00000080] h-[40px] overflow-hidden mt-3 break-all flex-shrink-0">
                                <div ref="contentRef" class="line-clamp-2">
                                    {{ item.intro || item.description }}
                                </div>
                            </div>
                        </ElTooltip>
                        <div class="text-[#00000080] h-[40px] overflow-hidden mt-3 break-all flex-shrink-0" v-else>
                            <div ref="contentRef">
                                {{ item.intro || item.description }}
                            </div>
                        </div>
                        <div class="text-[10px] flex items-center justify-between text-[#AAA6B9] mt-3">
                            <div>
                                {{ item.create_time }}
                                创建
                            </div>
                        </div>
                        <div class="absolute right-2 top-2 z-[1000] invisible group-hover:visible">
                            <handle-menu :data="item" :menu-list="handleMenuList" />
                        </div>
                    </div>
                </div>
                <div v-if="pager.isLoad" class="text-center py-4 text-gray-500">暂无更多了</div>
            </div>
        </div>
    </div>
    <create-panel v-if="isCreate" ref="createPanelRef" @back="back" />
</template>

<script setup lang="ts">
import {
    knowledgeBaseLists,
    knowledgeBaseDelete,
    vectorKnowledgeBaseLists,
    vectorKnowledgeBaseDelete,
} from "@/api/knowledge_base";
import { HandleMenuType } from "@/components/handle-menu/typings";
import { ToolEnumMap, ToolEnum } from "@/enums/appEnums";
import { useElementSize } from "@vueuse/core";
import { KnTypeEnum } from "./_enums";
import CreatePanel from "./_components/create-panel.vue";

const router = useRouter();
const route = useRoute();
const nuxtApp = useNuxtApp();

const currentTab = ref<KnTypeEnum>(KnTypeEnum.VECTOR);
const tabs: { label: string; value: KnTypeEnum }[] = [
    {
        label: "向量知识库",
        value: KnTypeEnum.VECTOR,
    },
    {
        label: "RAG知识库",
        value: KnTypeEnum.RAG,
    },
];

const queryParams = reactive({
    page_no: 1,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: (params: any) =>
        currentTab.value === KnTypeEnum.VECTOR ? vectorKnowledgeBaseLists(params) : knowledgeBaseLists(params),
    params: queryParams,
    isScroll: true,
});

const contentRef = ref<HTMLElement>();

const handleTabClick = (tab: any) => {
    if (currentTab.value == tab.paneName) return;
    currentTab.value = tab.paneName as KnTypeEnum;
    resetPage();
};

const handleMenuList: HandleMenuType[] = [
    {
        label: "删除知识库",
        icon: "local-icon-delete",
        click: ({ id }: any) => {
            nuxtApp.$confirm({
                message: "确定删除该知识库吗？",
                onConfirm: async () => {
                    try {
                        currentTab.value === KnTypeEnum.VECTOR
                            ? await vectorKnowledgeBaseDelete({ id })
                            : await knowledgeBaseDelete({
                                  id,
                              });

                        const index = pager.lists.findIndex((item) => item.id == id);
                        if (index !== -1) {
                            pager.lists.splice(index, 1);
                        }
                        feedback.msgSuccess("删除成功");
                    } catch (error) {
                        feedback.msgError(error);
                    }
                },
            });
        },
    },
];

const isCreate = ref(route.query.is_create == "1");

const handleCreate = async () => {
    isCreate.value = true;
    router.push({
        query: {
            is_create: 1,
            type: currentTab.value,
        },
    });
};

const handleViewDetail = (item: any) => {
    router.push({
        path: `/knowledge_base/detail/${item.id}`,
        query: {
            kn_type: currentTab.value,
            index_id: item.index_id || undefined,
            category_id: item.category_id || undefined,
            kn_name: item.name,
        },
    });
};

const load = () => {
    queryParams.page_no++;
    getLists();
};

const back = () => {
    isCreate.value = false;
    router.replace({
        query: {
            is_create: undefined,
            type: undefined,
        },
    });
    resetPage();
};

const init = () => {
    if (!isCreate.value) {
        getLists();
    }
};

watch(
    () => pager.lists,
    async () => {
        if (pager.lists.length) {
            await nextTick();
            pager.lists.forEach((item, index) => {
                const dom = contentRef.value[index];
                if (dom) {
                    const { height } = useElementSize(dom);
                    item.is_ellipsis = height.value > 40;
                }
            });
        }
    }
);

onMounted(init);
</script>

<style scoped lang="scss">
:deep(.el-tabs) {
    --el-border-color-light: #0000000d;
    .el-tabs__header {
        margin-bottom: 0;
        .el-tabs__nav {
            height: 62px;
            padding: 0 20px;
            .el-tabs__item {
                height: 62px;
                padding: 0 20px;
                font-weight: bold;
                color: rgba(0, 0, 0, 0.3);
                &.is-active {
                    color: var(--el-color-black);
                }
            }
            .el-tabs__nav-next,
            .el-tabs__nav-prev {
                line-height: 62px;
            }
        }
        .el-tabs__active-bar {
            height: 1px;
        }
        .el-tabs__nav-wrap::after {
            height: 1px;
        }
    }
}

.kb-item {
    @apply rounded-xl bg-[#F6F6F6] p-[16px] border border-[#EFEFEF]  relative cursor-pointer flex flex-col hover:scale-[1.02] transition-all duration-300;
}
</style>
