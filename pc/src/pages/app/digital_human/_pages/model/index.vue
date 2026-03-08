<template>
    <div class="h-full flex flex-col bg-white rounded-[32px] overflow-hidden border border-br min-w-[1000px]">
        <div class="flex-shrink-0 px-6 border-b border-br">
            <div class="flex items-center justify-between h-[80px]">
                <div class="flex items-center gap-x-3">
                    <div class="w-12 h-12 flex items-center justify-center rounded-[16px] bg-[#0065FB]/5 text-primary">
                        <Icon name="el-icon-Monitor" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[16px] text-[#1E293B] font-black tracking-tight">形象管理</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Model Management
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-x-3">
                    <div class="flex items-center">
                        <div v-if="isDelete" class="flex items-center gap-3">
                            <div class="px-3 py-1 rounded-lg bg-[#0065FB]/5 text-primary text-xs font-black">
                                已选择 {{ selectIndex.length }} 项
                            </div>
                            <div class="w-[1px] h-4 bg-[#E2E8F0]"></div>
                            <ElCheckbox v-model="isAllSelect" @change="handleAllSelect" class="custom-checkbox">
                                <span class="text-[#64748B] font-medium text-sm">全选所有素材</span>
                            </ElCheckbox>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <template v-if="!isDelete">
                            <ElButton
                                class="!h-[40px] !px-6 !rounded-full !border-br !text-[#64748B] !font-black hover:!text-primary hover:!border-primary hover:!bg-[#F5F7FF] transition-all"
                                @click="openBatchManage">
                                <div class="flex items-center gap-2">
                                    <Icon name="el-icon-Operation" :size="16"></Icon>
                                    <span>批量管理</span>
                                </div>
                            </ElButton>
                        </template>

                        <template v-else>
                            <div class="flex items-center gap-3 animate-in fade-in slide-in-from-right-4">
                                <ElButton
                                    link
                                    class="!text-[#94A3B8] hover:!text-[#64748B] !font-medium !text-sm mr-2"
                                    @click="handleExitDelete">
                                    <div class="flex items-center gap-1">
                                        <Icon name="el-icon-Close" :size="14"></Icon>
                                        <span>退出管理</span>
                                    </div>
                                </ElButton>

                                <ElButton
                                    type="danger"
                                    class="!h-[40px] !px-8 !rounded-full !font-black !shadow-[#EF4444]/20 !border-none bg-[#EF4444] hover:bg-[#DC2626] transition-all"
                                    :disabled="selectIndex.length === 0"
                                    @click="handleDelete()">
                                    <div class="flex items-center gap-2">
                                        <Icon name="el-icon-Delete" :size="16"></Icon>
                                        <span>彻底删除</span>
                                    </div>
                                </ElButton>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <div class="grow min-h-0" v-spin="{ show: loading, text: '加载中...' }">
            <ElScrollbar :distance="20" @end-reached="load">
                <div class="p-4">
                    <template v-if="pager.lists.length">
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 pb-4">
                            <div
                                v-for="(item, index) in pager.lists"
                                class="h-[295px] relative cursor-pointer overflow-hidden"
                                :key="index"
                                @click="handleChoose(index)">
                                <anchor-video
                                    :item="{
                                        id: item.id,
                                        name: item.name,
                                        pic: item.pic,
                                        status: item.status,
                                        url: item.result_url,
                                        remark: item.remark,
                                        source_type: item.source_type,
                                    }"
                                    @delete="handleDelete(item.id, item.source_type)" />
                                <div
                                    class="absolute top-0 right-0 z-[1000] w-full h-full bg-black/5 flex justify-end p-2 rounded-xl"
                                    v-if="isDelete">
                                    <div class="w-6 h-6 rounded-full">
                                        <Icon
                                            name="local-icon-success_fill"
                                            :size="20"
                                            :color="
                                                selectIndex.includes(index) ? 'var(--el-color-error)' : '#ffffff1a'
                                            "></Icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="pager.isLoad"></load-text>
                    </template>
                    <div class="h-full flex items-center justify-center" v-else>
                        <ElEmpty />
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between border-t border-br">
            <div class="text-xs font-medium text-[#CBD5E1]">共计 {{ pager.count }} 条形象数据</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getPublicAnchorList, deleteAnchor, deleteShanjianAnchor, deletePublicAnchor } from "@/api/digital_human";
import AnchorVideo from "@/pages/app/_components/anchor-video.vue";

const nuxtApp = useNuxtApp();

const loading = ref<boolean>(true);

const isDelete = ref<boolean>(false);
const isAllSelect = ref<boolean>(false);
const selectIndex = ref<number[]>([]);
const queryParams = reactive({
    page_no: 1,
    page_size: 20,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getPublicAnchorList,
    params: queryParams,
    isScroll: true,
});

const openBatchManage = () => {
    if (pager.lists.length == 0) {
        feedback.msgError("暂无数据，无法进行批量管理");
        return;
    }
    isDelete.value = true;
};

const handleExitDelete = () => {
    isDelete.value = false;
    selectIndex.value = [];
};

const handleChoose = (id: number) => {
    if (selectIndex.value.includes(id)) {
        selectIndex.value = selectIndex.value.filter((item) => item !== id);
    } else {
        selectIndex.value.push(id);
    }
    if (selectIndex.value.length == pager.lists.length) {
        isAllSelect.value = true;
    } else {
        isAllSelect.value = false;
    }
};

const handleAllSelect = () => {
    if (isAllSelect.value) {
        selectIndex.value = pager.lists.map((item, index) => index);
    } else {
        selectIndex.value = [];
    }
};

const load = async (e: any) => {
    if (e == "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no++;
        await getLists();
    }
};

// const handleRetry = async (id: number) => {
//     nuxtApp.$confirm({
//         message: "确定重试改形象吗？",
//         theme: "dark",
//         onConfirm: async () => {
//             try {
//                 feedback.loading("重试中...");
//                 await retryAnchor({ anchor_id: id });
//                 resetPage();
//                 feedback.msgSuccess("重试成功");
//             } catch (error) {
//                 feedback.msgError(error || "重试失败");
//             } finally {
//                 feedback.closeLoading();
//             }
//         },
//     });
// };

const handleDelete = async (id?: number, source_type?: string) => {
    nuxtApp.$confirm({
        title: "提示",
        message: "确定删除吗？",
        onConfirm: async () => {
            try {
                if (id) {
                    let deleteFunc =
                        source_type === "human_anchor"
                            ? deleteAnchor
                            : source_type === "shanjian_anchor"
                            ? deleteShanjianAnchor
                            : deletePublicAnchor;
                    await deleteFunc({ id });
                } else {
                    await deleteBySourceType("human_anchor", deleteAnchor);
                    await deleteBySourceType("shanjian_anchor", deleteShanjianAnchor);
                    await deleteBySourceType("public_anchor", deletePublicAnchor);
                }
                if (id) {
                    pager.lists = pager.lists.filter((item) => item.id !== id);
                } else {
                    pager.lists = pager.lists.filter((item, index) => !selectIndex.value.includes(index));
                }
                selectIndex.value = [];
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError("删除失败");
            }
        },
    });
};

async function deleteBySourceType(sourceType: string, deleteFunction: Function) {
    const ids = pager.lists
        .filter((item, index) => selectIndex.value.includes(index) && item.source_type == sourceType)
        .map((item) => item.id);
    if (ids.length === 0) return;

    await deleteFunction({ id: ids });
}

const init = async () => {
    try {
        await getLists();
    } finally {
        loading.value = false;
    }
};

init();
</script>

<style scoped lang="scss">
:deep(.el-checkbox) {
    .el-checkbox__inner {
        background-color: transparent;
        border-color: var(--el-color-danger);
        &::after {
            border-color: var(--el-color-danger);
        }
    }
    .el-checkbox__label {
        font-size: var(--el-font-size-base);
    }
}
</style>
