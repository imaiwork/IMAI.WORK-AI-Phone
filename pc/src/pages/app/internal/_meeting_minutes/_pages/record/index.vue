<template>
    <div class="h-full flex flex-col bg-white rounded-[20px] border border-br overflow-hidden">
        <div class="flex-shrink-0 px-8 h-[88px] flex items-center justify-between border-b border-br-extra-light">
            <div class="flex flex-col">
                <h1 class="text-xl font-[900] text-gray-950">会议文件库</h1>
                <p class="text-xs text-tx-placeholder font-medium mt-0.5">管理及回顾您的所有语音会议记录</p>
            </div>

            <div class="flex items-center gap-4">
                <ElInput
                    v-model="queryParams.name"
                    class="custom-input !w-[320px]"
                    placeholder="按会议主题搜索..."
                    clearable
                    @keyup.enter="getLists"
                    @clear="getLists">
                    <template #prefix>
                        <Icon name="el-icon-Search" :size="16" color="#94a3b8" />
                    </template>
                </ElInput>
            </div>
        </div>

        <div class="grow min-h-0 bg-gray-50/30 overflow-hidden" v-spin="{ show: loading }">
            <div v-if="pager.lists && pager.lists.length" class="h-full flex flex-col">
                <div class="grow min-h-0">
                    <ElScrollbar>
                        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 p-8">
                            <RecordCard
                                :item="item"
                                v-for="(item, index) in pager.lists"
                                :key="index"
                                @delete="handleDelete"
                                @again="handleAgain"
                                @train="handleTrain"
                                class="transition-all hover:scale-[1.02] hover:shadow-md" />
                        </div>
                    </ElScrollbar>
                </div>
            </div>

            <div v-else class="h-full flex flex-col items-center justify-center">
                <ElEmpty description="未发现相关会议记录">
                    <template #image>
                        <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mx-auto">
                            <Icon name="el-icon-Document" :size="48" color="#CBD5E1" />
                        </div>
                    </template>
                </ElEmpty>
            </div>
        </div>

        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between border-t border-br-extra-light bg-white">
            <div class="text-xs font-medium text-[#CBD5E1]">当前显示共 {{ pager.count }} 个会议文件</div>
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists" />
        </div>
    </div>

    <KnbBind ref="knbBindRef" v-if="showKnbBind" @close="showKnbBind = false" />
</template>

<script setup lang="ts">
import { meetingMinutesLists } from "@/api/meeting_minutes";
import RecordCard from "../../_components/record-card.vue";
import useHandleApi from "../../_hooks/useHandleApi";
import KnbBind from "@/components/knb-bind/index.vue";

const loading = ref<boolean>(true);

const queryParams = reactive({
    name: "",
});

const { pager, getLists } = usePaging({
    fetchFun: meetingMinutesLists,
    params: queryParams,
});

const { handleAgain, handleDelete } = useHandleApi({
    onSuccess: () => {
        getLists();
    },
});

const knbBindRef = ref<InstanceType<typeof KnbBind>>(null);
const showKnbBind = ref(false);

const handleTrain = async (result: any) => {
    showKnbBind.value = true;
    await nextTick();
    knbBindRef.value?.open();
    knbBindRef.value?.setFormData(result);
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
