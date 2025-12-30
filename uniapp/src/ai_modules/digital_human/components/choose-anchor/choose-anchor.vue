<template>
    <popup-bottom v-model="show" title="请选择形象" custom-class="bg-[#F9FAFB]" :is-disabled-touch="true">
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="pagingRef"
                    v-model="dataLists"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="queryList">
                    <view class="py-[30rpx] px-[32rpx] grid grid-cols-3 gap-2">
                        <view
                            class="bg-[#E6E6E6] h-[288rpx] rounded-[24rpx] flex flex-col items-center justify-center"
                            @click="toClone">
                            <view
                                class="w-[32rpx] h-[32rpx] rounded-full flex items-center justify-center bg-[#00000080]">
                                <u-icon name="plus" color="#ffffff" size="20"></u-icon>
                            </view>
                            <view class="text-[#000000b3] mt-[18rpx]">去克隆</view>
                        </view>
                        <view
                            v-for="(item, index) in dataLists"
                            :key="index"
                            class="h-[288rpx] rounded-[24rpx] relative overflow-hidden card-gradient"
                            @click.stop="chooseAnchor(item)">
                            <image :src="item.pic" class="w-full h-full" mode="aspectFill"></image>
                            <view
                                v-if="item.status == 0"
                                class="z-[222] absolute top-0 left-0 w-full h-full flex items-center justify-center bg-[#00000080]">
                                <view class="bg-primary text-xs font-bold text-white rounded-[10rpx] px-2 py-1"
                                    >克隆中</view
                                >
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getPublicAnchorList } from "@/api/digital_human";
import { useAppStore } from "@/stores/app";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    activeIds: {
        type: Array,
        default: [],
    },
});
const emit = defineEmits(["update:modelValue", "confirm"]);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});

const appStore = useAppStore();
const modelChannel = computed(() => appStore.getDigitalHumanConfig?.channel || []);

const modelVersionMap = computed(() => {
    return modelChannel.value.reduce((acc: Record<string, any>, item: any) => {
        acc[item.id] = item.name;
        return acc;
    }, {});
});

const pagingRef = shallowRef();
const dataLists = ref<any[]>([]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getPublicAnchorList({
            page_no,
            page_size,
            status: 1,
            filter: 2,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const chooseAnchor = (item: any) => {
    emit("confirm", item);
};

const toClone = () => {
    show.value = false;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/anchor_create/anchor_create",
        params: {
            source: [DigitalHumanModelVersionEnum.CHANJING, DigitalHumanModelVersionEnum.STANDARD],
        },
    });
};
</script>

<style scoped></style>
