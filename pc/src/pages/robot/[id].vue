<template>
    <div
        class="flex flex-col h-full bg-page overflow-y-auto robot-page"
        :infinite-scroll-immediate="false"
        :infinite-scroll-disabled="!pager.isLoad"
        :infinite-scroll-distance="10"
        v-infinite-scroll="load">
        <div class="text-2xl font-bold p-6">{{ route.query.name }}</div>
        <div class="grow flex flex-col min-h-0">
            <div class="px-6 pb-6">
                <div
                    class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6"
                    v-if="pager.lists.length">
                    <ElCard
                        class="cursor-pointer !rounded-xl bg-white relative hover:scale-[1.02] !p-0"
                        shadow="never"
                        v-for="(item, index) in pager.lists"
                        :key="index"
                        @click="handleRobot(item)">
                        <div class="flex flex-col bg-white rounded-lg w-full flex-grow gap-4">
                            <div class="h-12 w-12 flex-shrink-0">
                                <div class="overflow-hidden rounded-full" v-if="item.logo">
                                    <img
                                        :src="item.logo"
                                        class="h-full w-full bg-token-main-surface-secondary"
                                        width="80"
                                        height="80" />
                                </div>
                                <div class="w-full h-full rounded-full" v-else>
                                    <default-icon :icon-size="30"></default-icon>
                                </div>
                            </div>
                            <div class="overflow-hidden text-ellipsis break-words">
                                <span class="line-clamp-2 text-sm font-semibold leading-tight">{{ item.name }}</span>
                                <div class="line-clamp-3 text-xs mt-1">
                                    {{ item.description }}
                                </div>
                                <div
                                    class="mt-2 flex items-center gap-1 text-ellipsis whitespace-nowrap pr-1 text-xs text-token-text-tertiary">
                                    <div class="mt-1 flex flex-row items-center space-x-1">
                                        <div class="text-token-text-tertiary text-xs">
                                            创建者：{{ item.user_id ? "自己" : "后台" }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ElCard>
                </div>
                <div v-else class="mt-20">
                    <ElEmpty />
                </div>
            </div>
            <div v-if="!pager.isLoad" class="text-center py-4 text-gray-500">暂无更多了</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { robotLists } from "@/api/robot";

const router = useRouter();
const route = useRoute();

const queryParams = reactive({
    page_no: 1,
    page_size: 15,
    scene_id: route.params.id,
    type: 3,
});

const { getLists, pager } = usePaging({
    fetchFun: robotLists,
    params: queryParams,
    isScroll: true,
});

const isFinish = ref(false);
const load = async () => {
    queryParams.page_no += 1;
    const data = await getLists();
};

const handleRobot = (data: any) => {
    router.push(`/robot/chat/${data.assistants_id}?id=${data.id}`);
};

onMounted(async () => {
    await getLists();
});
</script>
