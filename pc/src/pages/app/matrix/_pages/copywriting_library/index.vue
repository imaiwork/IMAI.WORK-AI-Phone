<template>
    <div class="h-full flex flex-col bg-white rounded-[20px] overflow-hidden border border-br" v-if="!isCreate">
        <div class="flex-shrink-0 px-6 border-b border-br bg-white">
            <div class="flex items-center justify-between h-[80px]">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <Icon name="el-icon-Document" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">智能文案库</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            总文案: {{ pager.count }} 个
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <ElSelect
                        v-model="queryParams.copywriting_type"
                        class="!w-[120px] custom-select-pill"
                        clearable
                        placeholder="文案类型"
                        :show-arrow="false"
                        @change="resetPage()">
                        <ElOption label="全部类型" value=""></ElOption>
                        <ElOption label="口播文案" :value="CopywritingTypeEnum.CONTENT"></ElOption>
                        <ElOption label="内容文案" :value="CopywritingTypeEnum.TITLE"></ElOption>
                    </ElSelect>

                    <div
                        class="flex items-center rounded-full h-[40px] border border-br px-1 transition-all focus-within:border-[#0065fb]">
                        <ElInput
                            v-model="queryParams.name"
                            class="!w-[200px] search-input"
                            clearable
                            prefix-icon="el-icon-Search"
                            placeholder="搜索文案名称..."
                            @clear="resetPage()"
                            @keydown.enter="resetPage()">
                        </ElInput>
                        <ElButton
                            type="primary"
                            class="!rounded-full !h-[32px] !px-4 !text-xs !font-medium"
                            @click="resetPage()">
                            搜索
                        </ElButton>
                    </div>

                    <div class="w-[1px] h-6 bg-[#E2E8F0] mx-2"></div>
                    <ElPopover
                        placement="bottom-start"
                        :width="220"
                        trigger="hover"
                        popper-class="!rounded-2xl !p-2 !border-slate-100 "
                        :offset="12">
                        <template #reference>
                            <ElButton type="primary" class="!h-11 !px-6 !rounded-xl">
                                <div class="flex items-center">
                                    <Icon name="local-icon-add_circle" color="#ffffff" :size="16"></Icon>
                                    <span class="ml-2 tracking-wide">新建文案</span>
                                    <span class="ml-2 opacity-70 group-hover:rotate-180 transition-transform">
                                        <Icon name="el-icon-ArrowDown" :size="12"></Icon>
                                    </span>
                                </div>
                            </ElButton>
                        </template>

                        <div class="flex flex-col gap-1 p-1">
                            <div class="menu-item" @click="handleEdit(CopywritingTypeEnum.CONTENT)">
                                <div class="icon-wrapper bg-blue-50">
                                    <Icon name="el-icon-Microphone" color="var(--color-primary)" :size="14"></Icon>
                                </div>
                                <div class="flex flex-col">
                                    <span class="menu-title">口播文案</span>
                                    <span class="menu-desc">适用于短视频脚本</span>
                                </div>
                            </div>

                            <div class="menu-item" @click="handleEdit(CopywritingTypeEnum.TITLE)">
                                <div class="icon-wrapper bg-[#eef2ff]">
                                    <Icon name="el-icon-Document" color="#6366f1" :size="14"></Icon>
                                </div>
                                <div class="flex flex-col">
                                    <span class="menu-title">内容文案</span>
                                    <span class="menu-desc">适用于朋友圈、社群</span>
                                </div>
                            </div>
                        </div>
                    </ElPopover>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 bg-slate-50" v-spin="{ show: loading, text: '加载中...' }">
            <ElScrollbar :distance="20" @end-reached="load">
                <div class="p-6">
                    <template v-if="pager.lists.length > 0">
                        <div
                            v-if="pager.lists.length > 0"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            <div
                                v-for="item in pager.lists"
                                :key="item.id"
                                @click="handleEdit(item.copywriting_type, item.id)"
                                class="group relative bg-white rounded-[20px] p-5 border border-br transition-all hover:shadow-xl hover:shadow-[#0065fb]/10 hover:-translate-y-1 cursor-pointer flex flex-col h-[240px]">
                                <div class="flex justify-between items-start mb-4">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-slate-50 group-hover:bg-[#0065fb]/10 group-hover:text-primary flex items-center justify-center transition-colors">
                                        <Icon
                                            :name="
                                                item.copywriting_type === CopywritingTypeEnum.CONTENT
                                                    ? 'el-icon-Mic'
                                                    : 'el-icon-CollectionTag'
                                            "
                                            :size="20"></Icon>
                                    </div>
                                    <span
                                        class="text-[9px] px-2 py-0.5 rounded-full font-black uppercase tracking-wider"
                                        :class="
                                            item.copywriting_type === CopywritingTypeEnum.CONTENT
                                                ? 'bg-[#EEF2FF] text-primary'
                                                : 'bg-[#F0FDF4] text-[#22C55E]'
                                        ">
                                        {{
                                            item.copywriting_type === CopywritingTypeEnum.CONTENT
                                                ? "内容文案"
                                                : "口播文案"
                                        }}
                                    </span>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h4
                                        class="text-[15px] font-black text-[#1E293B] mb-2 line-clamp-1 group-hover:text-primary transition-colors">
                                        {{ item.name || "未命名文案" }}
                                    </h4>
                                    <p class="text-xs text-[#64748B] leading-relaxed line-clamp-3 italic opacity-80">
                                        “{{ getCopywritingContent(item) }}”
                                    </p>
                                </div>

                                <div class="mt-4 pt-4 border-t border-[#F1F5F9] flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[11px] text-[#94A3B8] font-medium">{{
                                            item.create_time
                                        }}</span>
                                    </div>
                                    <div class="w-6 h-6">
                                        <handle-menu horizontal :data="item" :menu-list="utilsMenuList" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="pager.isLoad"></load-text>
                    </template>
                    <div
                        v-if="pager.lists.length === 0 && !pager.loading"
                        class="h-[500px] flex flex-col items-center justify-center">
                        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4">
                            <Icon name="el-icon-DocumentDelete" :size="32" color="#CBD5E1"></Icon>
                        </div>
                        <p class="text-[#94A3B8] font-medium text-sm">文案库空空如也，快去创作吧</p>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <rename-pop
            v-if="showRenamePopup"
            ref="renamePopupRef"
            :fetch-fn="updateCopywritingLibrary"
            @close="showRenamePopup = false"
            @success="getUpdateCopywritingLibrary"></rename-pop>
    </div>
    <create-panel :type="copywritingType" v-else @back="back" />
</template>

<script setup lang="ts">
import { AppTypeEnum } from "@/enums/appEnums";
import { getCopywritingLibraryList, deleteCopywritingLibrary, updateCopywritingLibrary } from "@/api/matrix";
import { HandleMenuType } from "@/components/handle-menu/typings";
import { CopywritingTypeEnum, SidebarTypeEnum } from "@/pages/app/matrix/_enums";
import CreatePanel from "./_components/create-panel.vue";
const route = useRoute();

const loading = ref<boolean>(true);

const queryParams = reactive({
    name: "",
    page_no: 1,
    page_size: 10,
    type: AppTypeEnum.XHS,
    copywriting_type: "",
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getCopywritingLibraryList,
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

const showRenamePopup = ref(false);
const renamePopupRef = shallowRef();
const utilsMenuList: HandleMenuType[] = [
    {
        label: "重命名",
        icon: "local-icon-edit3",
        click: async (data) => {
            showRenamePopup.value = true;
            await nextTick();
            renamePopupRef.value?.open();
            renamePopupRef.value?.setFormData({ id: data.id, name: data.name });
        },
    },
    {
        label: "删除文案",
        icon: "local-icon-delete",
        click: ({ id }) => {
            useNuxtApp().$confirm({
                message: "确定删除该文案吗？",
                onConfirm: async () => {
                    try {
                        await deleteCopywritingLibrary({ id });
                        const index = pager.lists.findIndex((item) => item.id == id);
                        pager.lists.splice(index, 1);
                    } catch (error) {
                        feedback.msgWarning(error);
                    }
                },
            });
        },
    },
];

const isCreate = ref(route.query.is_create == "1");
const copywritingType = ref(Number(route.query.copywriting_type));
const handleEdit = (type: CopywritingTypeEnum, id?: string) => {
    isCreate.value = true;
    copywritingType.value = type;
    const query: any = {
        is_create: 1,
        copywriting_type: type,
    };
    if (id) {
        query.id = id;
    }
    replaceState(query);
};

const back = () => {
    isCreate.value = false;
    window.history.replaceState(null, null, `?type=${SidebarTypeEnum.COPYWRITING_LIBRARY}`);
    resetPage();
};

const getCopywritingContent = (data: any) => {
    const { copywriting_type, oral_copy, described } = data;

    const countStr = (key: string, arr: any[]) => {
        if (arr && arr.length > 0) {
            return arr.reduce(
                (acc: string, curr: any, index: number) =>
                    acc + `“${curr.content}”` + (index !== arr.length - 1 ? "、" : ""),
                ""
            );
        }
    };
    if (copywriting_type == CopywritingTypeEnum.TITLE) {
        return countStr("described", described);
    }
    if (copywriting_type == CopywritingTypeEnum.CONTENT) {
        return countStr("oral_copy", oral_copy);
    }
    return "暂无文案内容...";
};

const getUpdateCopywritingLibrary = (data: any) => {
    pager.lists.find((item) => item.id == data.id).name = data.name;
    showRenamePopup.value = false;
};

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
.material-item {
    @apply flex gap-x-4 h-[288px] relative overflow-hidden border border-[#ffffff33] rounded-xl cursor-pointer;
    &::after {
        @apply absolute top-0 left-0 w-full h-full;
        content: "";
        background: linear-gradient(180deg, rgba(0, 0, 0, 0) 50%, #000 100%);
        pointer-events: none;
    }
}
:deep(.search-input) {
    .el-input__wrapper {
        background: transparent !important;
        box-shadow: none !important;
        padding-left: 10px;
    }
    .el-input__inner {
        font-weight: 600;
        font-size: 13px;
        color: #1e293b;
        &::placeholder {
            color: #94a3b8;
        }
    }
}
.menu-item {
    @apply flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all duration-200 hover:bg-slate-50;

    .icon-wrapper {
        @apply w-8 h-8 rounded-lg flex items-center justify-center shrink-0;
    }

    .menu-title {
        @apply text-[14px] font-black text-slate-800 leading-none;
    }

    .menu-desc {
        @apply text-[11px] text-slate-400 font-medium mt-1;
    }

    &:hover {
        .menu-title {
            @apply text-primary;
        }
        .icon-wrapper {
            @apply bg-[#0065fb]/10;
        }
    }
}

:deep(.custom-select-pill) {
    .el-select__wrapper {
        border-radius: 99px !important;
        height: 40px !important;
        &.is-focus {
            box-shadow: 0 0 0 1px #4f46e5 inset !important;
        }
    }
}
</style>
