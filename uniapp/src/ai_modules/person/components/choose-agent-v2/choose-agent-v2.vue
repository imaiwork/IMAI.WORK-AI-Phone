<template>
    <popup-bottom
        v-model="show"
        title="选择评论智能体"
        custom-class="bg-[#F4F7FA]"
        :is-disabled-touch="true"
        :z-index="zIndex">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="flex items-center justify-between px-[30rpx] mt-3 mb-1" v-if="limit > 1">
                    <text class="text-[#666666] font-medium">请选择评论智能体</text>
                    <view class="text-xs text-[#999999]">
                        已选
                        <text class="text-primary font-bold mx-0.5">{{ chooseLists.length }}</text>
                        / {{ limit }}
                    </view>
                </view>

                <view class="flex-1 min-h-0 relative">
                    <z-paging
                        ref="pagingRef"
                        v-model="robotList"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="px-[30rpx] py-4 flex flex-col gap-y-3.5">
                            <view
                                v-for="(item, index) in robotList"
                                :key="index"
                                class="rounded-[24rpx] p-4 flex items-center gap-x-3.5 relative transition-all duration-300 border-[2rpx] border-solid"
                                :class="
                                    isDisabled(item)
                                        ? 'bg-[#F5F5F5] border-[transparent] shadow-none opacity-60'
                                        : isChoose(item)
                                        ? 'bg-[#F5F9FF] border-primary shadow-[0_8rpx_20rpx_rgba(0,101,251,0.12)] active:scale-[0.98]'
                                        : 'bg-white border-[transparent] shadow-[0_4rpx_16rpx_rgba(0,0,0,0.02)] active:scale-[0.98]'
                                "
                                @click="handleSelect(item)">
                                <view
                                    class="relative w-[96rpx] h-[96rpx] rounded-full flex-shrink-0 bg-[#F8F9FD] border border-gray-100 overflow-hidden">
                                    <image :src="item.image" class="w-full h-full object-cover"></image>
                                </view>

                                <view class="flex-1 min-w-0 flex flex-col justify-center">
                                    <view class="agent-title-row">
                                        <text
                                            class="agent-title-text"
                                            :class="isDisabled(item) ? 'text-[#BBBBBB]' : 'text-[#1A1A1A]'">
                                            {{ item.name }}
                                        </text>
                                        <text
                                            v-if="shouldShowAgentAccessTag(item)"
                                            class="agent-access-tag"
                                            :class="getAgentAccessTagClass(item)">
                                            {{ getAgentAccessTagText(item) }}
                                        </text>
                                    </view>
                                    <text
                                        class="text-xs truncate mb-1.5"
                                        :class="isDisabled(item) ? 'text-[#CCCCCC]' : 'text-[#999999]'">
                                        {{ item.intro || "暂无简介" }}
                                    </text>

                                    <view class="flex items-center">
                                        <view
                                            class="px-2 py-0.5 rounded-md"
                                            :class="isDisabled(item) ? 'bg-[#F0F0F0]' : 'bg-[#ececec]/80'">
                                            <text
                                                class="text-[20rpx]"
                                                :class="isDisabled(item) ? 'text-[#CCCCCC]' : 'text-[#666666]'">
                                                {{
                                                    isDisabled(item)
                                                        ? getDisabledText(item)
                                                        : `创建人：${item.source_text || "系统"}`
                                                }}
                                            </text>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    class="w-[44rpx] h-[44rpx] rounded-full border-[3rpx] flex items-center justify-center transition-colors duration-300 flex-shrink-0"
                                    :class="
                                        isDisabled(item)
                                            ? 'border-[#E5E7EB] bg-[#F0F0F0]'
                                            : isChoose(item)
                                            ? 'border-primary bg-primary'
                                            : 'border-[#E5E7EB] bg-[transparent]'
                                    ">
                                    <u-icon
                                        v-if="isChoose(item) && !isDisabled(item)"
                                        name="checkmark"
                                        color="#ffffff"
                                        size="24"></u-icon>
                                </view>
                            </view>
                        </view>

                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>

                <view
                    class="bg-[#ffffff]/90 flex-shrink-0 pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] px-5 shadow-[0_-8rpx_30rpx_rgba(0,0,0,0.04)] z-50 flex items-center gap-4">
                    <view
                        v-if="limit > 1"
                        class="flex items-center gap-2 active:opacity-70 transition-opacity py-2 flex-shrink-0"
                        @click="toggleSelectAll">
                        <view
                            class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center transition-colors"
                            :class="isAllSelected ? 'bg-primary' : 'border-[3rpx] border-[#D1D5DB] bg-[#F9FAFB]'">
                            <u-icon v-if="isAllSelected" name="checkmark" color="#ffffff" size="22"></u-icon>
                        </view>
                        <text class="text-[28rpx] text-[#333333] font-medium">全选</text>
                    </view>

                    <view class="flex-1">
                        <u-button
                            type="primary"
                            shape="circle"
                            :ripple="true"
                            :custom-style="{
                                height: '96rpx',
                                fontSize: '30rpx',
                                fontWeight: '900',
                                backgroundColor: '#0065fb',
                                border: 'none',
                                boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                            }"
                            @click="confirm">
                            确定保存{{ limit > 1 && chooseLists.length > 0 ? `(${chooseLists.length})` : "" }}
                        </u-button>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { computed, ref, toRefs } from "vue";
import { getAgentList } from "@/api/agent";
import { useUserStore } from "@/stores/user";
import {
    AGENT_UNAVAILABLE_TIP,
    canUseAgent,
    getAgentAccessStatus,
    getAgentAccessTagText as getAgentPermissionTagText,
    shouldShowAgentAccessTag,
} from "@/utils/agentPermission";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        limit?: number;
        zIndex?: number;
    }>(),
    {
        modelValue: false,
        limit: 1,
        zIndex: 1000,
    },
);

const emit = defineEmits<{
    (e: "confirm", agent: any): void;
    (e: "update:modelValue", value: boolean): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const robotList = ref<any[]>([]);
const pagingRef = ref<any>(null);
const chooseLists = ref<any[]>([]);
const disabledLists = ref<any[]>([]);
const userStore = useUserStore();
const { userInfo } = toRefs(userStore);

const isUsedAgent = (item: any) => disabledLists.value.some((d) => d.id == item.id);
const isAgentUnavailable = (item: any) => !canUseAgent(item, userInfo.value);
const isDisabled = (item: any) => isUsedAgent(item) || isAgentUnavailable(item);
const isChoose = (item: any) => chooseLists.value.some((c) => c.id == item.id);

const getDisabledText = (item: any) => (isUsedAgent(item) ? "已使用" : "仅会员可用");

const getAgentAccessTagText = (item: any) => getAgentPermissionTagText(item, userInfo.value);

const getAgentAccessTagClass = (item: any) =>
    getAgentAccessStatus(item, userInfo.value) === "free" ? "agent-access-tag--free" : "agent-access-tag--member";

// 全选仅针对可选项
const selectableLists = computed(() => robotList.value.filter((item) => !isDisabled(item)));
const isAllSelected = computed(
    () => selectableLists.value.length > 0 && chooseLists.value.length === selectableLists.value.length,
);

const handleSelect = (item: any) => {
    if (isUsedAgent(item)) {
        uni.$u.toast("该智能体已被使用，无法选择");
        return;
    }
    if (isAgentUnavailable(item)) {
        uni.$u.toast(AGENT_UNAVAILABLE_TIP);
        return;
    }
    const index = chooseLists.value.findIndex((c) => c.id == item.id);

    if (index > -1) {
        chooseLists.value.splice(index, 1);
    } else {
        if (props.limit === 1) {
            chooseLists.value = [item];
        } else {
            if (chooseLists.value.length >= props.limit) {
                uni.$u.toast(`最多只能选择 ${props.limit} 个智能体`);
            } else {
                chooseLists.value.push(item);
            }
        }
    }
};

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        chooseLists.value = [];
    } else {
        const all = [...selectableLists.value];
        if (all.length > props.limit) {
            uni.$u.toast(`最多只能选择 ${props.limit} 个智能体`);
            chooseLists.value = all.slice(0, props.limit);
        } else {
            chooseLists.value = all;
        }
    }
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getAgentList({ page_no, page_size });
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const confirm = () => {
    if (chooseLists.value.length === 0 && disabledLists.value.length === 0) {
        uni.$u.toast("请选择评论智能体");
        return;
    }
    const completedList = chooseLists.value.map((chosen) => {
        const fullItem = robotList.value.find((r) => r.id == chosen.id);
        return fullItem ?? chosen;
    });

    const hasIncomplete = completedList.some((item) => !item.name);
    if (hasIncomplete) {
        uni.$u.toast("数据加载中，请稍后再试");
        return;
    }
    if (completedList.some((item) => isAgentUnavailable(item))) {
        uni.$u.toast(AGENT_UNAVAILABLE_TIP);
        return;
    }

    show.value = false;
    emit("confirm", props.limit === 1 ? completedList[0] : completedList);
};

defineExpose({
    setChooseLists: (lists: any[]) => {
        chooseLists.value = JSON.parse(JSON.stringify(lists));
    },
    setDisabledLists: (lists: any[]) => {
        disabledLists.value = JSON.parse(JSON.stringify(lists));
    },
});
</script>

<style lang="scss" scoped>
.agent-title-row {
    @apply mb-1 flex min-w-0 items-center gap-x-[8rpx];
}

.agent-title-text {
    @apply min-w-0 flex-1 truncate text-[30rpx] font-extrabold;
}

.agent-access-tag {
    @apply shrink-0 rounded-full border border-solid px-[12rpx] py-[4rpx] text-[20rpx] font-semibold leading-none;
}

.agent-access-tag--free {
    @apply border-[#BBF7D0] bg-[#F0FDF4] text-[#16A34A];
}

.agent-access-tag--member {
    @apply border-[#DDD6FE] bg-[#F5F3FF] text-[#8B5CF6];
}
</style>
