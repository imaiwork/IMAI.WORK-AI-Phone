<template>
    <popup-bottom v-model="show" title="创作历史" height="80%" :is-disabled-touch="true" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full flex flex-col">
                <scroll-view scroll-x scroll-with-animation class="flex-shrink-0 px-[30rpx] mt-2">
                    <view class="flex gap-x-2 py-1 px-1">
                        <view v-for="(item, index) in typeList" :key="index" @click="handleType(item.key)">
                            <view
                                class="px-[24rpx] py-[10rpx] rounded-[10rpx] whitespace-nowrap text-[24rpx]"
                                :class="[
                                    currentType === item.key
                                        ? 'bg-black text-white'
                                        : 'shadow-[0_0_0_2rpx_rgba(0,0,0,0.1)] text-[#374151]',
                                ]">
                                {{ item.name }}
                            </view>
                        </view>
                    </view>
                </scroll-view>

                <view class="text-xs text-[#00000080] mt-2 px-[30rpx]"> 已选：{{ chooseLists.length }} </view>

                <view class="grow min-h-0 mt-[20rpx]">
                    <z-paging
                        class="h-full"
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :auto="false"
                        :default-page-size="100"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="grid grid-cols-3 gap-2 px-[30rpx]">
                            <view
                                class="h-[288rpx] rounded-xl relative overflow-hidden"
                                v-for="(item, index) in dataLists"
                                :key="index"
                                @click="handleSelect(item)">
                                <image :src="item.pic" class="w-full h-full rounded-xl" mode="aspectFill"></image>
                                <view class="absolute top-0 left-0 w-full h-full bg-[#00000080]" v-if="isChoose(item)">
                                    <view class="absolute top-2 right-2">
                                        <image
                                            src="/static/images/icons/success.svg"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-2 right-2 w-[28rpx] h-[28rpx] rounded-full bg-white"
                                    v-else></view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>

                <!-- 底部操作栏 -->
                <view class="flex items-center justify-between gap-2 mt-[20rpx] mb-4 px-[30rpx]">
                    <view class="flex items-center gap-x-2" @click="toggleSelect" v-if="limit && limit > 1">
                        <view class="w-[32rpx] h-[32rpx]">
                            <image
                                v-if="chooseLists.length > 0 && chooseLists.length == dataLists.length"
                                src="/static/images/icons/success.svg"
                                class="w-full h-full"></image>
                            <view class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]" v-else> </view>
                        </view>
                        <view>全选</view>
                    </view>
                    <view
                        class="flex-1 text-white font-medium text-[30rpx] rounded-[20rpx] bg-primary h-[90rpx] w-[460rpx] flex items-center justify-center"
                        @click="confirm">
                        确定选择
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getVideoCreationRecord } from "@/api/app";

// ✅ 与 creation.vue 保持一致的枚举
enum VideoType {
    ALL = 0,
    DIGITAL_HUMAN = 1,
    ORAL_MIX = 2,
    TRUE_HUMAN = 3,
    MATERIAL_MIX = 4,
    NEWS = 5,
    SENTENCE = 6,
    MONTAGE_STORYBOARD = 7,
}

// ✅ 与 creation.vue 保持一致的分类列表
const typeList = [
    { name: "全部", key: VideoType.ALL },
    { name: "数字人口播", key: VideoType.DIGITAL_HUMAN },
    { name: "口播混剪", key: VideoType.ORAL_MIX },
    { name: "真人口播", key: VideoType.TRUE_HUMAN },
    { name: "素材混剪", key: VideoType.MATERIAL_MIX },
    { name: "新闻体", key: VideoType.NEWS },
    { name: "一句话生成", key: VideoType.SENTENCE },
    { name: "分镜混剪", key: VideoType.MONTAGE_STORYBOARD },
];

const props = withDefaults(defineProps<{ modelValue: boolean; type?: number; limit?: number }>(), {
    modelValue: false,
    limit: 9,
});

const emit = defineEmits<{ (e: "update:modelValue", value: boolean): void; (e: "select", value: any[]): void }>();

const show = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit("update:modelValue", value);
    },
});

const dataLists = ref<any[]>([]);
const pagingRef = ref<any>(null);
const chooseLists = ref<any[]>([]);

// ✅ 当前选中的分类，默认全部
const currentType = ref<VideoType>(VideoType.ALL);

// ✅ 切换分类：重置选中列表并重新加载
const handleType = (key: VideoType) => {
    if (currentType.value === key) return;
    currentType.value = key;
    chooseLists.value = [];
    pagingRef.value?.reload();
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getVideoCreationRecord({
            page_no,
            page_size,
            type: currentType.value === VideoType.ALL ? "" : currentType.value,
        });
        pagingRef.value?.complete(lists.filter((item: any) => getStatus(item) == 1));
    } catch (error) {
        console.error("查询历史记录失败:", error);
        pagingRef.value?.complete([]);
    }
};

const getStatus = (item: any) => {
    const { type, status } = item || {};
    if (type === 1) {
        if (status === 0 || status === 1 || status === 2) return status;
        return 3;
    } else {
        if (status === 0) return 0;
        if (status === 3) return 1;
        if (status === 2) return 2;
        return 3;
    }
};

const isChoose = (data: any) => {
    return chooseLists.value.some((item) => item.id === data.id);
};

const handleSelect = (data: any) => {
    const isSelected = isChoose(data);
    if (isSelected) {
        chooseLists.value = chooseLists.value.filter((item) => item.id !== data.id);
        return;
    }
    if (props.limit === 1) {
        chooseLists.value = [data];
        return;
    }
    if (chooseLists.value.length >= props.limit) {
        uni.$u.toast(`最多选择${props.limit}个视频`);
        return;
    }
    chooseLists.value.push(data);
};

const toggleSelect = () => {
    if (chooseLists.value.length == dataLists.value.length) {
        chooseLists.value = [];
    } else {
        chooseLists.value = dataLists.value.slice(0, props.limit || dataLists.value.length);
    }
};

const confirm = () => {
    if (chooseLists.value.length == 0) {
        uni.$u.toast("至少选择一个视频");
        return;
    }
    show.value = false;
    emit("select", chooseLists.value);
    chooseLists.value = [];
};

watch(
    () => props.modelValue,
    async (newVal) => {
        if (newVal) {
            // ✅ 每次打开弹窗重置分类到"全部"
            currentType.value = VideoType.ALL;
            chooseLists.value = [];
            await nextTick();
            pagingRef.value?.reload();
        }
    },
    { immediate: true }
);
</script>

<style scoped></style>
