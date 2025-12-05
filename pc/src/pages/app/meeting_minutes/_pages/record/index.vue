<template>
    <div class="history-page h-full py-6 px-[95px] flex flex-col">
        <div class="mx-6">
            <ElInput
                v-model="queryParams.name"
                :prefix-icon="Search"
                class="!max-w-[343px]"
                placeholder="搜索您的会议文件"
                clearable
                @keyup.enter="getLists"
                @clear="getLists" />
        </div>
        <div class="grow min-h-0 mt-6" v-loading="pager.loading">
            <div v-if="pager.lists && pager.lists.length" class="h-full flex flex-col">
                <div class="grow min-h-0">
                    <ElScrollbar>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 px-6">
                            <RecordCard
                                :item="item"
                                v-for="(item, index) in pager.lists"
                                :key="index"
                                @delete="handleDelete"
                                @again="handleAgain"
                                @train="handleTrain" />
                        </div>
                    </ElScrollbar>
                </div>
            </div>
            <div v-else class="text-center py-4 text-gray-500">
                <ElEmpty description="暂无数据"></ElEmpty>
            </div>
        </div>
        <div class="flex justify-end p-4">
            <pagination v-model="pager" @change="getLists"></pagination>
        </div>
    </div>
    <KnbBind ref="knbBindRef" v-if="showKnbBind" @close="showKnbBind = false" />
</template>

<script setup lang="ts">
import { meetingMinutesLists, meetingMinutesRetry, meetingMinutesDelete } from "@/api/meeting_minutes";
import { Search } from "@element-plus/icons-vue";
import RecordCard from "../../_components/record-card.vue";
import useHandleApi from "../../_hooks/useHandleApi";
import KnbBind from "@/components/knb-bind/index.vue";
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

getLists();
</script>

<style scoped lang="scss">
.history-page {
    background: linear-gradient(90deg, #f2fcff 0%, #f6faff 36.12%, #faf9ff 72.61%, #fef6ff 100%);
}
</style>
