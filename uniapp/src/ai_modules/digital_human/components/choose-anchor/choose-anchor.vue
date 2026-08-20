<template>
    <popup-bottom v-model="show" title="请选择形象" custom-class="bg-[#F4F6F9]" :is-disabled-touch="true">
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="pagingRef"
                    v-model="dataLists"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="queryList">
                    <view class="py-[24rpx] px-[28rpx] grid grid-cols-3 gap-[20rpx]">
                        <view
                            class="h-[288rpx] rounded-[24rpx] flex flex-col items-center justify-center relative overflow-hidden border-[2rpx] border-dashed border-[#CBD5E1] bg-white active:scale-95 transition-transform"
                            @click="toClone">
                            <view
                                class="w-[80rpx] h-[80rpx] rounded-full flex items-center justify-center mb-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.2)]"
                                style="background: linear-gradient(135deg, #0065fb, #3b82f6)">
                                <u-icon name="plus" color="#ffffff" size="24" />
                            </view>
                            <text class="text-[26rpx] font-bold text-[#1E293B]">去克隆</text>
                            <text class="text-[22rpx] text-[#64748B] mt-[4rpx]">定制专属形象</text>
                        </view>

                        <view
                            v-for="(item, index) in dataLists"
                            :key="item.id || index"
                            class="h-[288rpx] rounded-[24rpx] relative overflow-hidden bg-white shadow-[0_4rpx_16rpx_rgba(0,0,0,0.04)] active:scale-95 transition-all duration-300"
                            :class="
                                activeIds?.includes(item.id)
                                    ? 'shadow-[0_0_0_3rpx_rgba(0,101,251,0.35)] scale-[1.02]'
                                    : ''
                            "
                            @click.stop="chooseAnchor(item)">
                            <image :src="item.pic" class="w-full h-full" mode="aspectFill" />

                            <view
                                class="absolute bottom-0 left-0 w-full h-[80rpx] pointer-events-none"
                                style="background: linear-gradient(to top, rgba(0, 0, 0, 0.35), transparent)" />

                            <view
                                v-if="activeIds?.includes(item.id)"
                                class="absolute inset-0 rounded-[24rpx] border-[4rpx] border-solid border-primary pointer-events-none" />

                            <view
                                v-if="getAnchorStatus(item.status, item.source_type) == 1"
                                class="absolute top-[12rpx] right-[12rpx] w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center transition-all duration-300"
                                :class="
                                    activeIds?.includes(item.id)
                                        ? 'bg-primary'
                                        : 'bg-[#000000]/30 border-[2rpx] border-solid border-[#ffffff]/50'
                                ">
                                <u-icon
                                    v-if="activeIds?.includes(item.id)"
                                    name="checkmark"
                                    color="#fff"
                                    size="14"
                                    font-weight="bold" />
                            </view>

                            <view
                                v-if="getAnchorStatus(item.status, item.source_type) == 0"
                                class="absolute inset-0 bg-[#000000]/55 flex flex-col items-center justify-center gap-[12rpx]">
                                <view class="relative w-[56rpx] h-[56rpx] flex items-center justify-center">
                                    <view
                                        class="absolute inset-0 border-[4rpx] border-solid border-[#ffffff]/20 rounded-full" />
                                    <view
                                        class="absolute inset-0 border-[4rpx] border-solid border-white rounded-full border-t-[transparent] rotate-anim" />
                                    <u-icon name="clock" color="#fff" size="20" />
                                </view>
                                <view
                                    class="bg-[#0065fb]/90 px-[20rpx] py-[6rpx] rounded-full shadow-[0_4rpx_12rpx_rgba(0,101,251,0.3)]">
                                    <text class="text-[22rpx] font-bold text-white tracking-wide">克隆中</text>
                                </view>
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
import { CloneModeEnum, cloneModeToIsPro } from "@/ai_modules/digital_human/enums";
import usePolling from "@/hooks/usePolling"; // 按项目实际路径调整

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    activeIds: {
        type: Array,
        default: () => [],
    },
    cloneMode: {
        type: Number,
        default: CloneModeEnum.FAST,
    },
});
const emit = defineEmits(["update:modelValue", "select"]);

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

/**
 * 统一的状态映射
 * 返回值含义：0 = 克隆中，1 = 已完成可用，2 = 其他（失败等）
 */
const getAnchorStatus = (status: number, source_type: string) => {
    const anchorStatusMapping: Record<string, any> = {
        human_anchor: {
            1: 1,
            2: 2,
            default: 0,
        },
        public_anchor: {
            1: 0,
            2: 1,
            3: 2,
            default: 0,
        },
    };
    return anchorStatusMapping[source_type]?.[status] ?? anchorStatusMapping[source_type]?.["default"] ?? 0;
};

// 列表查询：拉取所有状态，克隆中的也展示
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getPublicAnchorList({
            page_no,
            page_size,
            status: 1,
            filter: 2,
            is_pro: cloneModeToIsPro(props.cloneMode as CloneModeEnum),
        });
        pagingRef.value?.complete(lists);
        checkPolling();
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const chooseAnchor = (item: any) => {
    const realStatus = getAnchorStatus(item.status, item.source_type);
    if (realStatus == 0) {
        uni.showToast({ title: "形象克隆中，请稍候", icon: "none" });
        return;
    }
    if (realStatus == 2) {
        uni.showToast({ title: "该形象不可用", icon: "none" });
        return;
    }
    emit("select", item);
};

const toClone = () => {
    show.value = false;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/anchor_create/anchor_create",
    });
};

/* ============== 克隆中轮询逻辑 ============== */

const refreshCloningStatus = async () => {
    try {
        const { lists } = await getPublicAnchorList({
            page_no: 1,
            page_size: 50,
            status: 1,
            filter: 2,
            is_pro: cloneModeToIsPro(props.cloneMode as CloneModeEnum),
        });

        if (!lists || !lists.length) return;

        const merged = dataLists.value.map((old) => {
            const fresh = lists.find((n: any) => n.id === old.id);
            return fresh ? { ...old, ...fresh } : old;
        });

        for (const newItem of lists) {
            if (!merged.find((m: any) => m.id === newItem.id)) {
                merged.unshift(newItem);
            }
        }

        dataLists.value = merged;

        const stillCloning = merged.some((item: any) => getAnchorStatus(item.status, item.source_type) == 0);
        if (!stillCloning) {
            end();
        }
    } catch (e) {}
};

const { start, end } = usePolling(refreshCloningStatus, {
    time: 3000,
});

const checkPolling = () => {
    const hasCloning = dataLists.value.some((item) => getAnchorStatus(item.status, item.source_type) == 0);
    if (hasCloning) {
        start();
    } else {
        end();
    }
};

watch(show, (val) => {
    if (val) {
        // 打开时强制按当前 cloneMode 重拉，避免关着弹窗切「优质版」仍展示标准版缓存
        nextTick(() => {
            pagingRef.value?.reload();
            checkPolling();
        });
    } else {
        end();
    }
});

watch(
    () => props.cloneMode,
    () => {
        // 弹窗关闭时也清空，防止下次打开先闪旧列表
        dataLists.value = [];
        if (show.value) {
            pagingRef.value?.reload();
        }
    },
);

onUnmounted(() => {
    end();
});
</script>

<style scoped lang="scss">
.rotate-anim {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
