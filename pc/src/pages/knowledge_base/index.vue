<template>
    <div class="h-full flex flex-col min-w-[1000px] px-4 pb-4" v-if="!isCreate">
        <div
            class="h-[120px] rounded-[20px] bg-white border border-br px-10 flex items-center justify-between relative overflow-hidden">
            <div class="flex items-center gap-6">
                <img src="@/assets/images/kb.svg" class="w-20 h-20 mt-10" />
                <div>
                    <div class="text-[20px] font-[900] text-[#1E293B] mb-1">
                        {{ ToolEnumMap[ToolEnum.DATABASE] }}
                    </div>
                    <div class="text-base font-medium text-[#64748B]">
                        打通企业知识脉络，通过高精度的向量检索技术，让 AI 深入理解您的核心业务数据。
                    </div>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 flex flex-col bg-white rounded-[20px] border border-br overflow-hidden mt-4">
            <div class="px-6 bg-[#f8fafc]/50 border-b border-[#F1F5F9]">
                <ElTabs v-model="currentTab" @tab-click="handleTabClick" class="custom-tabs">
                    <ElTabPane v-for="item in tabs" :key="item.value" :label="item.label" :name="item.value" />
                </ElTabs>
            </div>

            <div class="grow min-h-0">
                <ElScrollbar :distance="20" @end-reached="load">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 p-8">
                        <div class="create-kb-card group" @click="handleCreate">
                            <div class="flex flex-col items-center justify-center h-full gap-4">
                                <div class="w-14 h-14 rounded-full bg-[#F1F5F9] flex items-center justify-center">
                                    <Icon name="el-icon-Plus" :size="24" />
                                </div>
                                <div class="text-center">
                                    <div class="text-[16px] font-black text-[#1E293B]">新建知识库</div>
                                    <div class="text-xs font-medium text-[#94A3B8] mt-1">只支持向量知识库</div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-for="(item, index) in pager.lists"
                            :key="index"
                            class="kb-item-card group"
                            @click="handleViewDetail(item)">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="w-14 h-14 rounded-xl bg-white border border-br p-1.5 flex-shrink-0">
                                        <img
                                            :src="item.image"
                                            class="w-full h-full object-cover rounded-lg"
                                            v-if="item.image" />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center text-[#94A3B8] bg-slate-50 rounded-lg">
                                            <Icon name="local-icon-windows2" :size="28" />
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <div
                                            class="text-[16px] font-black text-[#0F172A] truncate group-hover:text-primary transition-colors">
                                            {{ item.name }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-black bg-[#F1F5F9] text-[#475569] uppercase">
                                                {{ item.file_counts || 0 }} 文档
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="opacity-0 group-hover:opacity-100 transition-all transform scale-90"
                                    @click.stop>
                                    <handle-menu :data="item" :menu-list="handleMenuList" />
                                </div>
                            </div>

                            <div class="mt-4 grow min-h-0">
                                <p class="text-[13px] text-[#475569] font-medium leading-relaxed line-clamp-2">
                                    {{ item.intro || item.description || "暂无详细描述信息，点击进入管理详情..." }}
                                </p>
                            </div>

                            <div class="mt-4 pt-4 border-t border-[#F1F5F9] flex items-center justify-between">
                                <div class="flex items-center gap-1.5 text-[11px] font-medium text-[#64748B]">
                                    {{ item.create_time }}
                                </div>
                                <div
                                    class="flex items-center gap-1 text-[11px] font-black text-primary opacity-0 group-hover:opacity-100 transition-all translate-x-2 group-hover:translate-x-0">
                                    配置详情 <Icon name="el-icon-ArrowRight" :size="12" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <load-text :is-load="pager.isLoad"></load-text>
                </ElScrollbar>
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

onMounted(init);
</script>

<style scoped lang="scss">
.create-kb-card {
    @apply h-[210px] rounded-[20px] border-2 border-dashed border-[#CBD5E1] bg-white cursor-pointer transition-all duration-300;
    &:hover {
        @apply border-primary bg-[#F5F7FF] shadow-light shadow-[#0065FB]/5;
    }
}

.kb-item-card {
    @apply h-[210px] rounded-[24px] bg-white border border-br p-6 cursor-pointer flex flex-col transition-all duration-300;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);

    &:hover {
        @apply border-[#0065FB]/40;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        transform: translateY(-5px);
    }
}
</style>
