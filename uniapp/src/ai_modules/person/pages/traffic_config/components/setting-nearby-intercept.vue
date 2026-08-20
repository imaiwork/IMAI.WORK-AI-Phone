<template>
    <config-card
        title="同城视频评论截流"
        desc="挖掘同城视频评论，精准截流引客"
        icon-name="home"
        icon-color="#0065FB"
        icon-bg="#E6F0FF">
        <view>
            <view class="flex items-center justify-between h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                <view class="flex items-center gap-[10rpx]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">互动与触达动作</text>
                </view>
            </view>
            <view class="pt-[24rpx] flex flex-col gap-[20rpx]">
                <view class="flex gap-[12rpx]">
                    <view
                        v-for="action in GROUPON_FREE_ACTION_LIST"
                        :key="action.value"
                        class="flex-1 relative flex flex-col items-center justify-center py-[24rpx] rounded-[20rpx] border-2 border-solid transition-all duration-200"
                        :class="
                            configData.cityActions.includes(action.value)
                                ? 'border-primary bg-[#EBF2FF]'
                                : 'border-[#F0F2F5] bg-[#F7F9FC]'
                        "
                        @click="toggleFreeAction(action.value)">
                        <view
                            class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center mb-[8rpx]"
                            :class="configData.cityActions.includes(action.value) ? 'bg-[#0065fb]/10' : 'bg-[#F0F2F5]'">
                            <u-icon
                                :name="action.icon"
                                :color="configData.cityActions.includes(action.value) ? '#0065fb' : '#9CA3AF'"
                                size="32" />
                        </view>
                        <text
                            class="text-[22rpx] font-semibold"
                            :class="configData.cityActions.includes(action.value) ? 'text-primary' : 'text-[#9CA3AF]'">
                            {{ action.label }}
                        </text>
                        <view
                            v-if="configData.cityActions.includes(action.value)"
                            class="absolute top-[8rpx] right-[8rpx] w-[28rpx] h-[28rpx] rounded-full bg-primary flex items-center justify-center">
                            <u-icon name="checkmark" color="#fff" size="14" />
                        </view>
                    </view>

                    <view
                        class="flex-1 relative rounded-[20rpx] border-2 border-solid transition-all duration-200 overflow-hidden"
                        :class="hasMutexSelected ? 'border-primary' : 'border-[#F0F2F5]'">
                        <view
                            class="absolute top-0 left-0 right-0 flex justify-center"
                            style="z-index: 1; margin-top: -1rpx">
                            <view
                                class="px-[16rpx] h-[36rpx] flex items-center rounded-b-[12rpx] transition-all duration-200"
                                :class="hasMutexSelected ? 'bg-primary' : 'bg-[#E5E9F0]'">
                                <text
                                    class="text-[18rpx] font-semibold"
                                    :class="hasMutexSelected ? 'text-white' : 'text-[#9CA3AF]'">
                                    二选一
                                </text>
                            </view>
                        </view>
                        <view class="flex h-full pt-[36rpx]">
                            <view
                                v-for="(action, idx) in GROUPON_MUTEX_ACTION_LIST"
                                :key="action.value"
                                class="flex-1 flex flex-col items-center justify-center py-[24rpx] transition-all duration-200 relative"
                                :class="[
                                    configData.cityActions.includes(action.value) ? 'bg-[#EBF2FF]' : 'bg-[#F7F9FC]',
                                    idx === 0 ? 'border-[0] border-r border-solid border-[#E5E9F0]' : '',
                                ]"
                                @click="toggleMutexAction(action.value)">
                                <view
                                    class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center mb-[8rpx]"
                                    :class="
                                        configData.cityActions.includes(action.value)
                                            ? 'bg-[#0065fb]/10'
                                            : 'bg-[#F0F2F5]'
                                    ">
                                    <u-icon
                                        :name="action.icon"
                                        :color="configData.cityActions.includes(action.value) ? '#0065fb' : '#9CA3AF'"
                                        size="32" />
                                </view>
                                <text
                                    class="text-[22rpx] font-semibold"
                                    :class="
                                        configData.cityActions.includes(action.value)
                                            ? 'text-primary'
                                            : 'text-[#9CA3AF]'
                                    ">
                                    {{ action.label }}
                                </text>
                                <view
                                    v-if="configData.cityActions.includes(action.value)"
                                    class="absolute top-[8rpx] right-[8rpx] w-[28rpx] h-[28rpx] rounded-full bg-primary flex items-center justify-center">
                                    <u-icon name="checkmark" color="#fff" size="14" />
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="flex gap-[16rpx]">
                    <view class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]">观看视频（秒）</text>
                        <input
                            v-model="configData.cityWatchSeconds"
                            type="digit"
                            class="font-bold text-[#0D1117] h-[40rpx]" />
                    </view>
                    <view class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]">触达间隔（秒）</text>
                        <input
                            v-model="configData.cityReachInterval"
                            type="digit"
                            class="font-bold text-[#0D1117] h-[40rpx]" />
                    </view>
                </view>
            </view>
        </view>

        <view class="mt-5">
            <view class="flex items-center justify-between h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                <view class="flex items-center gap-[10rpx]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">评论者画像</text>
                </view>
                <view
                    class="bg-[#EBF2FF] rounded-[12rpx] px-[16rpx] h-[48rpx] flex items-center border border-solid border-[#BFDBFE]">
                    <text class="text-xs font-bold text-primary">
                        {{ configData.cityDistance == 0 ? "全城" : configData.cityDistance + "公里内" }}
                    </text>
                </view>
            </view>
            <view class="pt-[24rpx] flex flex-col gap-[20rpx]">
                <view>
                    <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[14rpx]">附近距离范围</text>
                    <view class="flex flex-wrap gap-[12rpx]">
                        <view
                            v-for="item in DISTANCE_LIST"
                            :key="item.value"
                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                            :class="
                                !isCustomDistance && configData.cityDistance == item.value
                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                    : 'bg-[#F0F2F5]'
                            "
                            @click="handleSelectDistance(item.value)">
                            <text
                                class="font-bold"
                                :class="
                                    !isCustomDistance && configData.cityDistance == item.value
                                        ? 'text-primary'
                                        : 'text-[#9CA3AF]'
                                ">
                                {{ item.label }}
                            </text>
                        </view>
                        <view
                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] border border-solid transition-all"
                            :class="
                                isCustomDistance ? 'bg-[#EBF2FF] border-primary' : 'bg-[#F0F2F5] border-[transparent]'
                            "
                            @click="handleSelectCustomDistance">
                            <text class="font-bold" :class="isCustomDistance ? 'text-primary' : 'text-[#9CA3AF]'">
                                自定义
                            </text>
                        </view>
                    </view>
                    <view v-if="isCustomDistance" class="mt-[16rpx]">
                        <view
                            class="flex items-center gap-[12rpx] bg-[#F5F5F5] rounded-[16rpx] px-[20rpx] h-[80rpx] border border-solid border-[#E5E9F0]">
                            <input
                                v-model="customDistanceInput"
                                type="digit"
                                placeholder="请输入距离数值"
                                placeholder-style="color:#BBBBBB;font-size:26rpx;"
                                class="flex-1 text-[28rpx] font-bold text-[#212121]"
                                @blur="handleCustomDistanceBlur" />
                            <text class="text-[#888888] flex-shrink-0">公里</text>
                        </view>
                        <text v-if="customDistanceError" class="text-[22rpx] text-red-500 mt-[8rpx] block">
                            {{ customDistanceError }}
                        </text>
                    </view>
                </view>

                <view class="h-[1rpx] bg-[#F0F2F5]" />

                <view class="flex gap-[32rpx]">
                    <view class="flex-1 flex flex-col">
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]">性别要求</text>
                        <view
                            class="flex flex-1 bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0]">
                            <view
                                v-for="item in GENDER_LIST"
                                :key="item.value"
                                class="flex-1 py-[12rpx] flex items-center justify-center rounded-[10rpx] transition-all duration-200"
                                :class="
                                    configData.cityGenderFilter == item.value
                                        ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                        : ''
                                "
                                @click="configData.cityGenderFilter = item.value">
                                <text
                                    class="font-semibold"
                                    :class="
                                        configData.cityGenderFilter == item.value ? 'text-[#0D1117]' : 'text-[#9CA3AF]'
                                    ">
                                    {{ item.label }}
                                </text>
                            </view>
                        </view>
                    </view>

                    <view class="flex-1 flex flex-col">
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]">年龄范围</text>
                        <view
                            class="flex flex-1 items-center bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0] gap-[6rpx]">
                            <view class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                <input
                                    v-model="configData.cityAgeMin"
                                    type="digit"
                                    class="font-bold text-center text-[#0D1117]" />
                            </view>
                            <text class="text-[#9CA3AF] text-xs">-</text>
                            <view class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                <input
                                    v-model="configData.cityAgeMax"
                                    type="digit"
                                    class="font-bold text-center text-[#0D1117]" />
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view class="mt-5">
            <view class="flex items-center gap-[10rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                <text class="text-[28rpx] font-extrabold text-[#0D1117]">作品与账号过滤</text>
            </view>
            <view class="pt-[24rpx] flex flex-col gap-[20rpx]">
                <view class="flex gap-[32rpx]">
                    <view class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                        <view class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]"
                            >视频作品满足（赞）</view
                        >
                        <view class="flex items-center gap-[8rpx]">
                            <input
                                v-model="configData.cityVideoMatchNum"
                                class="font-bold text-[32rpx] text-center text-[#0D1117] h-[40rpx]"
                                type="digit" />
                            <text class="text-[22rpx] text-[#9CA3AF] whitespace-nowrap">以上</text>
                        </view>
                    </view>
                    <view class="flex-1 bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[8rpx]">视频评论数不大于</text>
                        <view class="flex items-center gap-[8rpx]">
                            <input
                                v-model="configData.cityVideoCommentNum"
                                type="digit"
                                class="font-bold text-[32rpx] text-center text-[#0D1117] h-[40rpx]" />
                            <text class="text-[22rpx] text-[#9CA3AF] whitespace-nowrap">条</text>
                        </view>
                    </view>
                </view>
                <view class="flex gap-[32rpx]">
                    <view>
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]">目标评论粉丝数量</text>
                        <view
                            class="flex items-center bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0] gap-[6rpx]">
                            <view class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                <input
                                    v-model="configData.cityCommentFansMin"
                                    type="digit"
                                    class="font-bold text-center text-[#0D1117] h-[40rpx]" />
                            </view>
                            <text class="text-[#9CA3AF] text-xs">-</text>
                            <view class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                <input
                                    v-model="configData.cityCommentFansMax"
                                    type="digit"
                                    class="font-bold text-center text-[#0D1117] h-[40rpx]" />
                            </view>
                        </view>
                    </view>

                    <view>
                        <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[12rpx]">目标评论关注数量</text>
                        <view
                            class="flex items-center bg-[#F7F9FC] rounded-[14rpx] p-[6rpx] border border-solid border-[#E5E9F0] gap-[6rpx]">
                            <view class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                <input
                                    v-model="configData.cityFollowMin"
                                    type="digit"
                                    class="font-bold text-center text-[#0D1117] h-[40rpx]" />
                            </view>
                            <text class="text-[#9CA3AF] text-xs">-</text>
                            <view class="flex-1 bg-white rounded-[10rpx] shadow-sm px-[10rpx] py-[12rpx]">
                                <input
                                    v-model="configData.cityFollowMax"
                                    type="digit"
                                    class="font-bold text-center text-[#0D1117] h-[40rpx]" />
                            </view>
                        </view>
                    </view>
                </view>

                <view class="h-[1rpx] bg-[#F0F2F5]" />

                <view>
                    <text class="text-[22rpx] font-semibold text-[#9CA3AF] block mb-[14rpx]"
                        >对方昵称不包含（防误触）</text
                    >
                    <view class="bg-[#F7F9FC] rounded-[20rpx] p-[20rpx] border border-solid border-[#E5E9F0]">
                        <view class="flex flex-wrap gap-[12rpx] mb-[16rpx]">
                            <view
                                v-for="(name, idx) in configData.cityNicknameFilters"
                                :key="idx"
                                class="flex items-center gap-[8rpx] bg-white rounded-full px-[16rpx] h-[52rpx] border border-solid border-[#E5E9F0]"
                                @click="emit('edit-nickname-filter', idx)">
                                <text class="text-xs text-[#4B5563]">{{ name }}</text>
                                <view
                                    class="w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                    @click.stop="configData.cityNicknameFilters.splice(idx, 1)">
                                    <u-icon name="close" size="14" color="#9CA3AF" />
                                </view>
                            </view>
                            <view
                                v-if="configData.cityNicknameFilters.length === 0"
                                class="w-full flex justify-center py-[8rpx]">
                                <text class="text-xs text-[#C0C4CC]">暂无排除词</text>
                            </view>
                        </view>
                        <view class="flex gap-[12rpx]">
                            <view
                                class="flex-1 bg-white rounded-[14rpx] px-[20rpx] py-[6rpx] border border-solid border-[#E5E9F0]">
                                <u-input
                                    v-model="cityNicknameFilter"
                                    placeholder="如：店长、主播..."
                                    placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                    @confirm="handleAddNicknameFilter" />
                            </view>
                            <view
                                class="px-[28rpx] flex items-center justify-center rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                @click="handleAddNicknameFilter">
                                <text class="font-semibold text-white">添加</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </config-card>
</template>

<script setup lang="ts">
import { ConfigData, GrouponAction, GROUPON_ACTION_LIST, DISTANCE_LIST, GENDER_LIST } from "./type";
import ConfigCard from "./config-card.vue";

const props = defineProps<{
    configData: ConfigData;
}>();

const emit = defineEmits<{
    (e: "add", key: string): void;
    (e: "edit", key: string, value: any): void;
    (e: "edit-nickname-filter", idx: number): void;
    (e: "remove", key: string, value: any): void;
    (e: "input", key: string, value: any): void;
}>();

const GROUPON_FREE_ACTION_LIST = GROUPON_ACTION_LIST.filter(
    (a) => ![GrouponAction.Comment, GrouponAction.Dm].includes(a.value),
);

const GROUPON_MUTEX_ACTION_LIST = GROUPON_ACTION_LIST.filter((a) =>
    [GrouponAction.Comment, GrouponAction.Dm].includes(a.value),
);

const hasMutexSelected = computed(() =>
    GROUPON_MUTEX_ACTION_LIST.some((a) => props.configData.cityActions.includes(a.value)),
);

const customDistanceInput = ref<number | null>();
const customDistanceError = ref<string>("");
const isCustomDistance = ref(false);

const cityNicknameFilter = ref<string>("");

const toggleFreeAction = (key: number) => {
    const idx = props.configData.cityActions.indexOf(key);
    if (idx === -1) props.configData.cityActions.push(key);
    else props.configData.cityActions.splice(idx, 1);
};

const toggleMutexAction = (key: number) => {
    const idx = props.configData.cityActions.indexOf(key);
    if (idx !== -1) {
        props.configData.cityActions.splice(idx, 1);
    } else {
        GROUPON_MUTEX_ACTION_LIST.forEach(({ value }) => {
            const i = props.configData.cityActions.indexOf(value);
            if (i !== -1) props.configData.cityActions.splice(i, 1);
        });
        props.configData.cityActions.push(key);
    }
};

const handleSelectDistance = (value: number): void => {
    props.configData.cityDistance = value;
    isCustomDistance.value = false;
    customDistanceError.value = "";
};

const handleSelectCustomDistance = (): void => {
    customDistanceError.value = "";
    isCustomDistance.value = true;
};

const handleCustomDistanceBlur = (): void => {
    const raw = customDistanceInput.value?.toString().trim();
    if (raw === "") {
        customDistanceError.value = "";
        props.configData.cityDistance = 0;
        return;
    }
    const num = Number(raw);
    if (isNaN(num) || !Number.isInteger(num) || num <= 0) {
        customDistanceError.value = "请输入大于 0 的整数（单位：公里）";
        return;
    }
    customDistanceError.value = "";
    props.configData.cityDistance = num;
};

const handleAddNicknameFilter = () => {
    const name = cityNicknameFilter.value.trim();
    if (!name) return;
    if (!props.configData.cityNicknameFilters.includes(name)) {
        props.configData.cityNicknameFilters.push(name);
    }
    cityNicknameFilter.value = "";
};
</script>

<style scoped></style>
