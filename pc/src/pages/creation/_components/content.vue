<template>
    <div class="flex flex-col h-full bg-slate-50">
        <div class="sticky top-0 z-10 bg-slate-50/80 backdrop-blur-md px-6 py-4">
            <ElScrollbar>
                <div class="flex items-center gap-3 pb-3">
                    <div
                        v-for="(item, index) in categoryLists"
                        :key="index"
                        class="px-5 py-2 rounded-full cursor-pointer whitespace-nowrap text-[13px] font-black transition-all duration-300"
                        :class="[
                            sceneIndex == index
                                ? 'bg-primary text-white  shadow-[#0065fb]/20'
                                : 'bg-white text-[#94A3B8] border border-[#F1F5F9] hover:text-primary hover:border-[#0065fb]/30',
                        ]"
                        @click="handleSceneType(index)">
                        {{ item.name }}
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div class="grow min-h-0">
            <ElScrollbar :distance="20" @end-reached="load">
                <div class="px-4 py-2">
                    <template v-if="pager.lists.length">
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
                            <div
                                v-for="(item, index) in pager.lists"
                                :key="index"
                                class="record-card group"
                                @click="handleRecord(item)">
                                <div class="flex items-start justify-between mb-3">
                                    <div
                                        class="px-2 py-0.5 bg-[#F1F5F9] rounded text-[10px] font-medium text-[#64748B] truncate max-w-[120px]">
                                        {{ item.scene_name }}
                                    </div>
                                    <div class="absolute top-2 right-2" @click.stop>
                                        <ElPopover
                                            :show-arrow="false"
                                            placement="bottom-end"
                                            popper-class="!p-1.5 !rounded-xl !border-[#F1F5F9] !min-w-[120px]"
                                            @show="visibleChange(true, item.task_id)"
                                            @hide="visibleChange(false, item.task_id)">
                                            <template #reference>
                                                <div
                                                    class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-[#F1F5F9] text-[#94A3B8] transition-colors">
                                                    <Icon name="el-icon-MoreFilled" :size="16"></Icon>
                                                </div>
                                            </template>

                                            <div class="flex flex-col gap-1">
                                                <div
                                                    class="pop-item hover:!text-danger hover:bg-[#FDE6E8]"
                                                    @click="handleDelete(item.task_id, index)">
                                                    <Icon name="el-icon-Delete" :size="14"></Icon>
                                                    <span>删除记录</span>
                                                </div>
                                            </div>
                                        </ElPopover>
                                    </div>
                                </div>

                                <div class="space-y-2 grow min-h-0">
                                    <div
                                        class="text-[14px] font-[900] text-[#1E293B] line-clamp-1 group-hover:text-primary transition-colors">
                                        {{ item.message || item.file_info?.name || "未命名会话" }}
                                    </div>
                                    <div class="text-xs text-[#64748B] leading-relaxed line-clamp-3">
                                        {{ item.reply || "查看详情内容..." }}
                                    </div>
                                </div>

                                <div
                                    class="mt-4 pt-4 border-t border-[#F8FAFC] flex items-center justify-between text-[11px] font-medium text-[#CBD5E1]">
                                    <span class="flex items-center gap-1">
                                        <Icon name="el-icon-Clock" :size="12"></Icon>
                                        {{ item.create_time }}
                                    </span>
                                    <div
                                        class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center text-primary">
                                        <span>继续对话</span>
                                        <Icon name="el-icon-ArrowRight" :size="12"></Icon>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <load-text :is-load="pager.isLoad"></load-text>
                    </template>

                    <template v-else>
                        <div v-if="!pager.loading" class="flex flex-col items-center justify-center py-32 space-y-4">
                            <div
                                class="w-20 h-20 bg-white rounded-[24px] flex items-center justify-center border border-[#F1F5F9]">
                                <Icon name="el-icon-FolderOpened" :size="40" color="#CBD5E1"></Icon>
                            </div>
                            <span class="text-[13px] font-black text-[#94A3B8]">暂无历史记录</span>
                        </div>
                    </template>
                </div>
            </ElScrollbar>
        </div>
    </div>

    <KnbBind ref="knbBindRef" v-if="showKnbBind" @close="showKnbBind = false" />
</template>

<script setup lang="ts">
import { getChatRecord, deleteChatRecord } from "@/api/chat";
import { useAppStore } from "@/stores/app";
import KnbBind from "@/components/knb-bind/index.vue";
import { dayjs } from "element-plus";

const router = useRouter();
const nuxtApp = useNuxtApp();
const appStore = useAppStore();

const sceneIndex = ref<number>(0);
const activeRecord = ref<any>("");

const categoryLists = computed(() => [{ name: "全部", id: "" }].concat(appStore.menuList));
const queryParams = reactive({
    page_no: 1,
    scene_id: categoryLists.value[sceneIndex.value]?.id,
});

const { pager, getLists, resetPage } = usePaging({
    size: 25,
    fetchFun: getChatRecord,
    params: queryParams,
    isScroll: true,
});

const handleSceneType = (index: number) => {
    if (index == sceneIndex.value) return;
    sceneIndex.value = index;
    queryParams.page_no = 1;
    queryParams.scene_id = categoryLists.value[sceneIndex.value]?.id;
    resetPage();
};

const visibleChange = (flag: boolean, id: number) => {
    activeRecord.value = flag ? id : "";
};

const handleRecord = (row: any) => {
    const { assistant_id, task_id, robot_id, robot_name } = row;
    if (assistant_id == 0) {
        if (Number(robot_id) > 0) {
            router.push(`/?task_id=${task_id}&agent_id=${robot_id}&agent_name=${robot_name}`);
        } else {
            router.push(`/?task_id=${task_id}`);
        }
    } else {
        router.push(`/robot/chat?id=${assistant_id}&task_id=${task_id}`);
    }
};

const handleDelete = async (task_id: number, index: number) => {
    await nuxtApp.$confirm({
        message: "确定删除此记录吗？",
        onConfirm: async () => {
            try {
                await deleteChatRecord({ task_id });
                feedback.msgSuccess("删除成功");
                pager.lists.splice(index, 1);
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

const showKnbBind = ref(false);
const knbBindRef = ref<InstanceType<typeof KnbBind>>();
const handleTrain = async (item: any) => {
    showKnbBind.value = true;
    await nextTick();
    knbBindRef.value?.open();
    knbBindRef.value?.setFormData({
        type: "txt",
        fileName: `${dayjs().format("YYYYMMDDHHmmss")}`,
        content: item.message,
    });
};

const load = async (e: string) => {
    if (e == "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no += 1;
        await getLists();
    }
};

onMounted(() => {
    getLists();
});
</script>

<style scoped lang="scss">
.record-card {
    @apply relative bg-white rounded-[24px] p-5 border border-[#F1F5F9] cursor-pointer transition-all duration-300 flex flex-col h-full overflow-hidden;

    &:hover {
        @apply shadow-[0_20px_40px_-12px_rgba(79,70,229,0.08)] -translate-y-1 border-[#0065fb]/20;
    }
}

.pop-item {
    @apply flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-[#64748B] cursor-pointer transition-all;
}
</style>
