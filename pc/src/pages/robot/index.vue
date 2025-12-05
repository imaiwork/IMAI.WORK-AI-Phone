<template>
    <div class="flex flex-col h-full p-4">
        <div class="rounded-lg px-[30px] bg-white">
            <ElScrollbar>
                <div class="flex gap-8 whitespace-nowrap py-1">
                    <div
                        v-for="(tab, index) in appStore.menuList"
                        class="flex items-center gap-2 py-4 flex-shrink-0 cursor-pointer"
                        :key="index"
                        :class="index === sceneIndex ? 'text-primary' : ''"
                        @click="handleSceneTab(index)">
                        <img :src="tab.logo" alt="logo" class="w-[20px] h-[20px] rounded-full" />
                        <div class="text-lg font-bold">{{ tab.name }}({{ tab.sub_list.length }})</div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div class="mt-4 bg-white grow min-h-0 flex flex-col rounded-xl">
            <div class="px-[30px]">
                <ElTabs v-model="sceneSubIndex" @tab-click="handleSceneSubTab">
                    <ElTabPane
                        v-for="(tab, index) in sceneSubList"
                        :key="index"
                        :label="tab.name"
                        :name="index"></ElTabPane>
                </ElTabs>
            </div>
            <div class="grow min-h-0 overflow-y-auto dynamic-scroller">
                <div
                    v-loading="pager.loading"
                    :infinite-scroll-immediate="false"
                    :infinite-scroll-disabled="!pager.isLoad"
                    :infinite-scroll-distance="10"
                    v-infinite-scroll="load">
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 px-[30px]"
                        v-if="pager.lists.length">
                        <router-link
                            v-for="(item, index) in pager.lists"
                            :key="index"
                            :to="`/robot/chat?ppid=${getSceneId}&pid=${queryParams.scene_id}&id=${item.id}`"
                            class="bg-white p-4 rounded-lg cursor-pointer hover:scale-105 transition-all duration-300 mt-2 border border-[#E0E0E0]">
                            <div class="flex items-center gap-2">
                                <img :src="item.logo" alt="logo" class="w-[50px] h-[50px] rounded-lg" />
                                <div class="font-bold line-clamp-1">
                                    {{ item.name }}
                                </div>
                            </div>
                            <div class="text-xs text-[#666666] mt-2 line-clamp-2 h-[38px]">
                                {{ item.description }}
                            </div>
                            <div class="text-xs text-[#999999] mt-3">创建时间：{{ item.create_time }}</div>
                        </router-link>
                    </div>
                    <div v-else>
                        <ElEmpty />
                    </div>
                </div>
                <div v-if="!pager.isLoad" class="text-center py-4 text-gray-500">暂无更多了</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { robotLists } from "@/api/robot";
import { ToolEnumMap, ToolEnum } from "@/enums/appEnums";
const appStore = useAppStore();

const sceneIndex = ref<number>(0);

const sceneSubList = ref<any[]>([]);
const sceneSubIndex = ref<number>(0);

const handleSceneTab = (index: number) => {
    sceneIndex.value = index;
    sceneSubIndex.value = 0;
    getSceneSubList();
};

const getScene = computed(() => {
    return appStore.menuList[sceneIndex.value] || {};
});

const getSceneId = computed(() => {
    return getScene.value?.id;
});

const getSceneSubList = () => {
    sceneSubList.value = [{ name: "全部", id: "" }].concat(getScene.value?.sub_list);
    sceneSubIndex.value == 0
        ? (queryParams.scene_id = getSceneId.value)
        : (queryParams.scene_id = sceneSubList.value?.[sceneSubIndex.value]?.id);
    pager.lists = [];
    resetPage();
};

const handleSceneSubTab = (e: any) => {
    const { index } = e;
    sceneSubIndex.value = parseInt(index);
    getSceneSubList();
};

const queryParams = reactive<any>({
    type: 3,
    scene_id: "",
    page_no: 1,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: robotLists,
    params: queryParams,
    isScroll: true,
    size: 40,
});

const load = async () => {
    queryParams.page_no += 1;
    await getLists();
};

onMounted(() => {
    getSceneSubList();
});
</script>

<style scoped lang="scss">
:deep(.el-input__wrapper) {
    @apply rounded-full;
}
:deep(.el-icon) {
    width: 18px;
    height: 18px;
    svg {
        width: 18px;
        height: 18px;
    }
}
:deep(.el-tabs__nav-wrap) {
    &::after {
        height: 1px;
    }
}

:deep(.el-tabs) {
    --el-tabs-header-height: 60px;
}
</style>
