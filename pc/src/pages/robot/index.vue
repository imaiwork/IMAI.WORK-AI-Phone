<template>
    <div class="flex flex-col h-full min-w-[1000px] px-4 pb-4">
        <div class="bg-white rounded-[20px] px-6 border border-br mb-4">
            <ElScrollbar>
                <div class="flex items-center gap-3 whitespace-nowrap py-4">
                    <div
                        v-for="(tab, index) in appStore.menuList"
                        :key="index"
                        class="group flex items-center gap-2.5 px-5 py-2.5 rounded-2xl cursor-pointer transition-all duration-300 border-2"
                        :class="
                            index === sceneIndex
                                ? 'bg-[#0065fb]/10 border-primary text-primary'
                                : 'bg-white border-[transparent] hover:bg-[#F1F5F9] text-[#64748B]'
                        "
                        @click="handleSceneTab(index)">
                        <img
                            :src="tab.logo"
                            class="w-5 h-5 rounded-full object-cover transition-transform group-hover:scale-110" />
                        <span class="text-[15px] font-black">{{ tab.name }}</span>
                        <span
                            class="text-[10px] px-1.5 py-0.5 rounded-md font-medium"
                            :class="
                                index === sceneIndex
                                    ? 'bg-primary text-white'
                                    : 'bg-[#F1F5F9] text-[#94A3B8] group-hover:bg-white'
                            ">
                            {{ tab.sub_list.length }}
                        </span>
                    </div>
                </div>
            </ElScrollbar>
        </div>

        <div class="grow min-h-0 bg-white rounded-[20px] flex flex-col border border-br overflow-hidden">
            <div class="px-6 bg-[#f8fafc]/50 border-b border-[#F1F5F9]">
                <ElTabs v-model="sceneSubIndex" class="custom-tabs" @tab-click="handleSceneSubTab">
                    <ElTabPane
                        v-for="(tab, index) in sceneSubList"
                        :key="index"
                        :label="tab.name"
                        :name="index"></ElTabPane>
                </ElTabs>
            </div>

            <div class="grow min-h-0">
                <ElScrollbar :distance="20" @end-reached="load">
                    <template v-if="pager.lists.length">
                        <div class="grid grid-cols-4 gap-3 p-4">
                            <router-link
                                v-for="(item, index) in pager.lists"
                                :key="index"
                                :to="`/robot/chat?ppid=${getSceneId}&pid=${queryParams.scene_id}&id=${item.id}`"
                                class="robot-card group">
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="relative flex-shrink-0">
                                        <img
                                            :src="item.logo"
                                            class="w-14 h-14 rounded-[18px] object-cover border border-[#F1F5F9]" />
                                        <div
                                            class="absolute -right-1 -bottom-1 w-5 h-5 bg-white rounded-full flex items-center justify-center text-primary shadow-light">
                                            <Icon name="el-icon-Cpu" :size="12" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="text-[15px] font-[900] text-[#1E293B] line-clamp-1 group-hover:text-primary transition-colors">
                                            {{ item.name }}
                                        </div>
                                        <div class="flex items-center gap-1 mt-1">
                                            <span
                                                class="text-[10px] font-black text-[#94A3B8] bg-slate-50 px-1.5 py-0.5 rounded uppercase tracking-wider"
                                                >AI Agent</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs font-medium text-[#64748B] leading-[1.6] line-clamp-2 h-[38px] mb-4">
                                    {{ item.description || "暂无描述信息..." }}
                                </p>

                                <div class="pt-4 border-t border-[#F8FAFC] flex items-center justify-between">
                                    <span class="text-[10px] font-medium text-[#CBD5E1]">{{ item.create_time }}</span>
                                    <div
                                        class="opacity-0 group-hover:opacity-100 transition-all translate-x-2 group-hover:translate-x-0 text-primary">
                                        <Icon name="el-icon-Right" />
                                    </div>
                                </div>
                            </router-link>
                        </div>
                        <load-text :is-load="pager.isLoad"></load-text>
                    </template>
                    <div v-else class="grow flex flex-col items-center justify-center py-20">
                        <ElEmpty description="该分类下暂无应用" />
                    </div>
                </ElScrollbar>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { robotLists } from "@/api/robot";
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

const load = async (e: string) => {
    if (e === "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no += 1;
        await getLists();
    }
};

onMounted(() => {
    getSceneSubList();
});
</script>

<style scoped lang="scss">
.robot-card {
    @apply bg-white p-5 rounded-[20px] border border-br transition-all duration-300 relative flex flex-col;

    &:hover {
        @apply border-[#0065FB]/30 translate-y-[-4px];
        box-shadow: 0 12px 24px -8px rgba(var(--el-primary-color), 0.12);
    }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-[#E2E8F0] rounded-full;
}
</style>
