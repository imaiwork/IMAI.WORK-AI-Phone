<template>
    <div class="h-full">
        <div class="">
            <ElScrollbar>
                <div class="flex items-center gap-2 pb-4">
                    <div
                        v-for="(item, index) in categoryLists"
                        class="px-2 py-1 rounded-md cursor-pointer whitespace-nowrap"
                        :class="[
                            sceneIndex == index
                                ? 'text-[#000000] font-bold bg-[rgba(120,96,254,.08)]'
                                : 'bg-[rgba(139,95,95,0.04)]',
                        ]"
                        @click="handleSceneType(index)">
                        <span class="text-base">
                            {{ item.name }}
                        </span>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div
            class="mt-2"
            :infinite-scroll-distance="10"
            :infinite-scroll-immediate="false"
            :infinite-scroll-disabled="!pager.isLoad"
            v-infinite-scroll="load">
            <div class="pb-6" v-loading="pager.loading">
                <template v-if="pager.lists.length">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6">
                        <ElCard
                            class="cursor-pointer !rounded-xl relative hover:scale-[1.02] !p-0"
                            shadow="never"
                            v-for="(item, index) in pager.lists"
                            :key="index"
                            @click="handleRecord(item)">
                            <div class="flex flex-col rounded-lg w-full flex-grow gap-4 group relative h-[130px]">
                                <div class="font-bold">
                                    {{ item.scene_name }}
                                </div>
                                <div class="line-clamp-3 grow">
                                    {{ item.message || item.file_info.name }}
                                </div>
                                <div class="text-tx-primary text-sm">
                                    {{ item.create_time }}
                                </div>
                                <div
                                    class="ml-2 h-full invisible group-hover:visible absolute -right-2 -top-1"
                                    :class="[activeRecord == item.task_id ? '!visible' : '']"
                                    @click.stop>
                                    <ElPopover
                                        :show-arrow="false"
                                        popper-class="!w-[120px] !min-w-[120px] !p-[6px] !rounded-xl"
                                        @show="visibleChange(true, item.task_id)"
                                        @hide="visibleChange(false, item.task_id)">
                                        <template #reference>
                                            <div class="rotate-90 origin-center p-1">
                                                <Icon name="el-icon-MoreFilled"></Icon>
                                            </div>
                                        </template>
                                        <div class="flex flex-col gap-2">
                                            <div
                                                class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                                @click="handleDelete(item.task_id, index)">
                                                <ElButton link icon="el-icon-Delete" class="w-full !justify-start"
                                                    >删除</ElButton
                                                >
                                            </div>
                                            <div
                                                class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-1.5"
                                                @click="handleTrain(item)">
                                                <ElButton link icon="el-icon-DocumentAdd" class="w-full !justify-start"
                                                    >训练知识库</ElButton
                                                >
                                            </div>
                                        </div>
                                    </ElPopover>
                                </div>
                            </div>
                        </ElCard>
                    </div>
                    <div v-if="!pager.isLoad" class="text-center py-4 text-gray-500">暂无更多了</div>
                </template>
                <template v-else>
                    <div v-if="!pager.loading" class="mt-20">
                        <ElEmpty />
                    </div>
                </template>
            </div>
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
    if (!flag) {
        activeRecord.value = "";
    } else {
        activeRecord.value = id;
    }
};

const handleRecord = (row: any) => {
    const { assistant_id, task_id, robot_id, robot_name } = row;
    if (assistant_id == 0) {
        router.push(`/?task_id=${task_id}&agent_id=${robot_id}&agent_name=${robot_name}`);
    } else {
        feedback.msgWarning("相关助理已经暂停使用啦~");
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

const load = async () => {
    queryParams.page_no += 1;
    await getLists();
};

onMounted(async () => {
    getLists();
});
</script>

<style scoped lang="scss">
:deep(.el-card__body) {
    @apply p-3;
}
</style>
