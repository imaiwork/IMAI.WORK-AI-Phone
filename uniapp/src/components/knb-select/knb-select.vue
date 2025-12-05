<template>
    <popup-bottom v-model:show="show" height="85%" :is-disabled-touch="true" show-close-btn custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="flex flex-col h-full">
                <view class="px-4">
                    <u-tabs
                        ref="tabsRef"
                        :list="getKnbTypes"
                        :current="currentKnb"
                        bg-color="transparent"
                        font-size="26"
                        :active-item-style="{ fontWeight: '400' }"
                        :show-bar="false"
                        @change="handleKnbTypeChange"></u-tabs>
                    <u-search
                        v-model="search"
                        placeholder="搜索相关知识库"
                        height="80"
                        bg-color="#ffffff"
                        search-icon-color="#0000001a"
                        placeholder-color="#0000001a"
                        :show-action="false"
                        @search="handleSearch"
                        @clear="clearSearch" />
                </view>
                <view class="grow min-h-0">
                    <z-paging
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :auto="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="flex flex-col gap-4 mx-4 pb-10 pt-4">
                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                :class="[
                                    'flex items-center gap-4 justify-between  p-4 rounded-lg bg-white',
                                    isChoose(item) ? 'shadow-[0_0_0_1px_rgba(0,101,251,1)]' : '',
                                ]"
                                @click="handleSelect(item)">
                                <view class="flex items-center gap-4">
                                    <image
                                        src="/static/images/common/kn_logo_active.svg"
                                        class="w-8 h-8"
                                        v-if="isChoose(item)"></image>
                                    <image src="/static/images/common/kn_logo.svg" class="w-8 h-8" v-else></image>
                                    <view>
                                        <view class="text-[26rpx]">{{ item.name }}</view>
                                        <view class="text-[22rpx] text-[#0000004d] mt-1"
                                            >知识数：{{ item.file_count || item.file_counts }}</view
                                        >
                                    </view>
                                </view>
                                <view>
                                    <radio
                                        :checked="isChoose(item)"
                                        color="#0065FB"
                                        style="transform: scale(0.7)"
                                        @click.stop="handleSelect(item)"></radio>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { knowledgeBaseLists, vectorKnowledgeBaseLists } from "@/api/knowledge_base";
import { KnbTypeEnum } from "@/enums/appEnums";
const emit = defineEmits(["close", "confirm"]);

const props = withDefaults(
    defineProps<{
        showVector: boolean;
        showRag: boolean;
    }>(),
    {
        showVector: true,
        showRag: true,
    }
);

const knbTypes = ref<any[]>([
    {
        id: 0,
        name: "向量知识库",
        value: KnbTypeEnum.VECTOR,
    },
    {
        id: 1,
        name: "RAG知识库",
        value: KnbTypeEnum.RAG,
    },
]);
const getKnbTypes = computed(() => {
    if (props.showVector && props.showRag) {
        return knbTypes.value;
    } else if (props.showVector) {
        return knbTypes.value.filter((item) => item.value == KnbTypeEnum.VECTOR);
    } else {
        return knbTypes.value.filter((item) => item.value == KnbTypeEnum.RAG);
    }
});

const currentKnb = ref<number>(0);

const show = ref(false);

const dataLists = ref<any[]>([]);

const pagingRef = ref();
const search = ref("");
const queryList = async (page_no: number, page_size: number) => {
    try {
        const params = { page_no, page_size, name: search.value };
        const currId = getKnbTypes.value[currentKnb.value].id;
        const { lists } = await (currId == 0 ? vectorKnowledgeBaseLists : knowledgeBaseLists)(params);
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const isChoose = (item: any) => {
    if (currentKnb.value == 0) {
        return activeKnb.value.id == item.id;
    } else {
        return activeKnb.value.index_id == item.index_id;
    }
};

const handleKnbTypeChange = (index: number) => {
    currentKnb.value = index;
    dataLists.value = [];
    pagingRef.value?.reload();
};

const handleSearch = () => {
    pagingRef.value?.reload();
};

const clearSearch = () => {
    search.value = "";
    handleSearch();
};

const activeKnb = ref<any>({});
const handleSelect = (item: any) => {
    const currId = getKnbTypes.value[currentKnb.value].id;
    if (currId == 0) {
        if (activeKnb.value.id == item.id) {
            activeKnb.value = {};
        } else {
            activeKnb.value = item;
        }
    } else {
        if (activeKnb.value.index_id == item.index_id) {
            activeKnb.value = {};
        } else {
            activeKnb.value = item;
        }
    }
    show.value = false;
    emit("confirm", {
        data: activeKnb.value,
        type: currId == 0 ? KnbTypeEnum.VECTOR : KnbTypeEnum.RAG,
    });
};

const tabsRef = ref();
const open = async (data: any) => {
    show.value = true;
    await nextTick();
    await pagingRef.value?.reload();
    activeKnb.value = data;
    await nextTick();
    tabsRef.value.currentIndex = currentKnb.value;
};

const close = () => {
    show.value = false;
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped></style>
