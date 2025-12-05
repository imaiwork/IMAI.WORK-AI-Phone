<template>
    <div class="h-full flex flex-col bg-app-bg-2 rounded-[20px]" v-if="!isCreate">
        <div class="flex-shrink-0 px-[14px] border-[0] border-b-[1px] border-app-border-1">
            <ElScrollbar>
                <div class="flex items-center justify-end h-[88px]">
                    <div class="flex items-center gap-[14px]">
                        <ElSelect
                            v-model="queryParams.copywriting_type"
                            class="!w-[100px]"
                            popper-class="dark-select-popper"
                            clearable
                            :show-arrow="false"
                            :empty-values="[null, undefined]"
                            :value-on-clear="null"
                            @change="resetPage()">
                            <ElOption label="全部" value=""></ElOption>
                            <ElOption label="口播文案" :value="CopywritingTypeEnum.CONTENT"></ElOption>
                            <ElOption label="内容文案" :value="CopywritingTypeEnum.TITLE"></ElOption>
                        </ElSelect>
                        <ElInput
                            v-model="queryParams.name"
                            prefix-icon="el-icon-Search"
                            class="!w-[240px] search-name-input"
                            placeholder="请输入素材名称"
                            clearable
                            @clear="resetPage()"
                            @keydown.enter="resetPage()">
                            <template #append>
                                <ElButton text @click="resetPage()"> 搜索 </ElButton>
                            </template>
                        </ElInput>
                        <ElButton
                            type="primary"
                            class="!rounded-full !h-10 !px-4"
                            @click="handleEdit(CopywritingTypeEnum.CONTENT)">
                            <Icon name="local-icon-add_circle" color="#ffffff"></Icon>
                            <span class="ml-2">新增口播文案</span>
                        </ElButton>
                        <div>
                            <ElButton
                                type="primary"
                                class="!rounded-full !h-10 !px-4"
                                @click="handleEdit(CopywritingTypeEnum.TITLE)">
                                <Icon name="local-icon-add_circle" color="#ffffff"></Icon>
                                <span class="ml-2">新增内容文案</span>
                            </ElButton>
                        </div>
                        <ElTooltip content="刷新">
                            <ElButton
                                circle
                                color="#1f1f1f"
                                icon="el-icon-Refresh"
                                class="!w-10 !h-10"
                                @click="resetPage()"></ElButton>
                        </ElTooltip>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div
            class="grow min-h-0 overflow-y-auto flex flex-col dynamic-scroller"
            :infinite-scroll-immediate="false"
            :infinite-scroll-disabled="!pager.isLoad"
            :infinite-scroll-distance="10"
            v-infinite-scroll="load">
            <div class="h-full p-4" v-loading="pager.loading">
                <div v-if="pager.lists.length">
                    <div
                        v-if="pager.lists.length"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
                        <div
                            v-for="(item, index) in pager.lists"
                            class="material-item group"
                            :key="index"
                            @click="handleEdit(item.copywriting_type, item.id)">
                            <div class="w-full px-3 absolute z-[22] top-2">
                                <div class="line-clamp-1 text-white">
                                    {{ item.name }}
                                </div>
                            </div>
                            <div
                                class="absolute w-full h-full left-0 top-0 px-3 z-[22] flex flex-col items-center justify-center">
                                <div class="title-text">
                                    {{ copywritingTypeEnumMap[item.copywriting_type] || "文案" }}
                                </div>
                                <div class="text-[#ffffff80] mt-[14px]">
                                    {{ item.create_time }}
                                </div>
                            </div>
                            <div class="absolute right-2 top-2 z-[1000] w-9 h-9 invisible group-hover:visible">
                                <handle-menu :theme="ThemeEnum.DARK" :data="item" :menu-list="utilsMenuList" />
                            </div>
                            <img src="../../../_assets/images/copywriting_bg.png" class="w-full h-full object-cover" />
                        </div>
                    </div>
                    <div v-if="!pager.isLoad" class="text-white text-center text-xs w-full py-4">暂无更多了~</div>
                </div>
                <div class="h-full flex items-center justify-center" v-else>
                    <Empty
                        btn-text="创建文案"
                        msg="快去创建你的文案吧"
                        :custom-click="() => handleEdit(CopywritingTypeEnum.TITLE)" />
                </div>
            </div>
        </div>
        <edit-popup v-if="showEditPopup" ref="editPopupRef" @close="showEditPopup = false" @success="resetPage()" />
    </div>
    <create-panel :type="copywritingType" v-else @back="back" />
</template>

<script setup lang="ts">
import { ThemeEnum, AppTypeEnum } from "@/enums/appEnums";
import { getCopywritingLibraryList, deleteCopywritingLibrary } from "@/api/redbook";
import { HandleMenuType } from "@/components/handle-menu/typings";
import { CopywritingTypeEnum } from "~/pages/app/matrix/_enums";
import Empty from "@/pages/app/matrix/_components/empty.vue";
import CreatePanel from "./_components/create-panel.vue";
import EditPopup from "./_components/edit.vue";
const route = useRoute();

const copywritingTypeEnumMap = {
    [CopywritingTypeEnum.TITLE]: "内容文案",
    [CopywritingTypeEnum.CONTENT]: "口播文案",
};

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

const load = () => {
    queryParams.page_no++;
    getLists();
};

const showEditPopup = ref(false);
const editPopupRef = ref<InstanceType<typeof EditPopup>>();
const utilsMenuList: HandleMenuType[] = [
    {
        label: "重命名",
        icon: "local-icon-edit3",
        click: async (data) => {
            showEditPopup.value = true;
            await nextTick();
            editPopupRef.value.open();
            editPopupRef.value.setFormData(data);
        },
    },
    {
        label: "删除素材",
        icon: "local-icon-delete",
        click: ({ id }) => {
            useNuxtApp().$confirm({
                message: "确定删除该视频吗？",
                theme: "dark",
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
    window.history.replaceState(null, null, `?type=${AppTypeEnum.XHS}`);
    resetPage();
};

getLists();
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
.title-text {
    padding: 8px 22px;
    border-radius: 1000px;
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.3) 0%, rgba(56, 56, 56, 0.3) 100%);
    box-shadow: 0px 6px 12px 0px rgba(0, 0, 0, 0.24);
    backdrop-filter: blur(6px);
}
</style>
