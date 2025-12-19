<template>
    <popup-bottom
        v-model:show="showPopup"
        title="请选择音色"
        custom-class="bg-[#F9FAFB]"
        :is-disabled-touch="true"
        show-close-btn>
        <template #content>
            <view class="h-full flex flex-col">
                <navigator
                    :url="`/ai_modules/digital_human/pages/tone_clone/tone_clone?model_version=${modelVersion}`"
                    hover-class="none"
                    class="flex items-center justify-center gap-x-2 mx-[32rpx] bg-white rounded-[24rpx] h-[140rpx] mt-2">
                    <image src="@/ai_modules/digital_human/static/icons/add.svg" class="w-[32rpx] h-[32rpx]"></image>
                    <text class="text-[30rpx] font-bold">去克隆音色</text>
                </navigator>
                <view class="grow min-h-0">
                    <z-paging
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :auto="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="py-[30rpx] px-[32rpx]">
                            <template v-if="showFreeTone">
                                <view
                                    v-for="(item, index) in systemToneLists"
                                    class="flex items-center mb-[16rpx] gap-x-[24rpx] bg-white rounded-[24rpx] p-[32rpx]"
                                    :class="{
                                        '!bg-[#F0F7FF] shadow-[0px_0px_0px_1px_rgba(0,101,251,1)]':
                                            activeTone == item.voice_id,
                                    }"
                                    :key="index"
                                    @click="chooseTone(item, item.type)">
                                    <view class="flex-shrink-0 leading-[0]">
                                        <image
                                            class="w-[72rpx] h-[72rpx]"
                                            src="@/ai_modules/digital_human/static/images/common/system_tone.svg"></image>
                                    </view>
                                    <view class="flex-1 text-[26rpx]"> {{ item.name }} </view>
                                    <view
                                        class="flex-shrink-0 px-[24rpx] h-[60rpx] flex items-center justify-center rounded-full text-[26rpx] bg-primary-light-9 text-primary">
                                        {{ item.voice_id === -1 ? "当前视频" : "免费" }}
                                    </view>
                                </view>
                            </template>
                            <view
                                v-for="(item, index) in dataLists"
                                class="flex items-center mb-[16rpx] gap-x-[24rpx] bg-white rounded-[24rpx] p-[32rpx]"
                                :class="{
                                    '!bg-[#F0F7FF] shadow-[0px_0px_0px_1px_rgba(0,101,251,1)]':
                                        activeTone == item.voice_id,
                                }"
                                :key="index"
                                @click="chooseTone(item, 1)">
                                <view class="flex-shrink-0 leading-[0]">
                                    <image
                                        class="w-[72rpx] h-[72rpx]"
                                        src="@/ai_modules/digital_human/static/images/common/user_tone.svg"></image>
                                </view>
                                <view class="flex-1 text-[26rpx]">
                                    <text class="line-clamp-1 break-all">{{ item.name }}</text>
                                </view>
                                <view
                                    class="flex-shrink-0 px-[24rpx] h-[60rpx] flex items-center justify-center rounded-full text-[26rpx] bg-[#F2F2F2] text-[#A9A9A9]">
                                    用户音色(免费)
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
import { getVoiceList } from "@/api/digital_human";
import { useAppStore } from "@/stores/app";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    modelVersion: {
        type: [String, Number],
        default: "",
    },
    activeTone: {
        type: [String, Number],
        default: "",
    },
    showOriginalTone: {
        type: Boolean,
        default: false,
    },
    showFreeTone: {
        type: Boolean,
        default: true,
    },
});
const emit = defineEmits(["update:modelValue", "confirm"]);

const showPopup = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});

const appStore = useAppStore();
const systemToneLists = computed(() => {
    const list = [
        ...(appStore.getDigitalHumanConfig?.voice || [])
            .filter((item: any) => item.status == "1")
            .map((item: any) => ({ ...item, voice_id: item.code, type: 0 })),
    ];
    if (props.showOriginalTone) {
        list.unshift({ voice_id: -1, name: "视频原音", type: 1 });
    }
    return list;
});

const pagingRef = shallowRef();
const dataLists = ref<any[]>([]);
const queryParams = reactive<any>({
    name: "",
    model_version: props.modelVersion,
    status: 1,
    builtin: 1,
});
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getVoiceList({
            page_no,
            page_size,
            ...queryParams,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const chooseTone = (item: any, type: number) => {
    emit("confirm", { ...item, type });
};

watch(
    () => props.modelValue,
    async (val) => {
        queryParams.model_version = props.modelVersion;
        await nextTick();
        pagingRef.value?.reload();
    }
);
</script>

<style scoped></style>
