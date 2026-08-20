<template>
    <view>
        <view class="bg-white rounded-[30rpx] px-[28rpx] py-[30rpx] mb-[24rpx] flex items-center">
            <avatar-upload
                :avatar="avatarUrl"
                :size="96"
                :icon-size="32"
                @update:avatar="emit('update:avatar', $event)" />
            <view class="flex-1 ml-[28rpx]">
                <view class="flex items-center">
                    <view class="flex-1">
                        <input
                            v-model="formData.ai_name"
                            :maxlength="30"
                            placeholder="请填写 AI 名字"
                            placeholder-style="color:#111827;font-size:30rpx;font-weight:900;"
                            class="w-full"
                            style="font-size: 30rpx; font-weight: 900; color: #111827" />
                    </view>
                    <u-icon name="edit-pen" color="#B8C3D6" size="26"></u-icon>
                </view>
                <view class="mt-[8rpx] flex items-center">
                    <view class="flex-1">
                        <input
                            v-model="formData.intro"
                            :maxlength="80"
                            placeholder="填写简介（选填）..."
                            placeholder-style="color:#B8C3D6;font-size:26rpx;"
                            class="w-full"
                            style="font-size: 26rpx; color: #7b8798" />
                    </view>
                    <u-icon name="edit-pen" color="#B8C3D6" size="26"></u-icon>
                </view>
            </view>
        </view>

        <view class="bg-white rounded-[30rpx] px-[28rpx] py-[26rpx] mb-[24rpx]">
            <text class="block text-[24rpx] font-bold text-[#86909C] mb-[20rpx]"> 账号类型 </text>
            <view class="flex gap-[16rpx]">
                <view
                    v-for="item in accountTypeOptions"
                    :key="item.value"
                    class="flex-1 h-[72rpx] rounded-[20rpx] border-[3rpx] border-solid flex items-center justify-center active:opacity-80"
                    :class="
                        formData.persona_type === item.value
                            ? 'bg-[#EBF3FF] border-primary'
                            : 'bg-[#F6F8FC] border-[#E5EBF8]'
                    "
                    @click="handleSelectPersonaType(item.value)">
                    <text class="text-[24rpx] mr-[8rpx]">{{ item.icon }}</text>
                    <text
                        class="text-[24rpx] font-bold whitespace-nowrap"
                        :class="formData.persona_type === item.value ? 'text-primary' : 'text-[#86909C]'">
                        {{ item.label }}
                    </text>
                </view>
            </view>
        </view>

        <view class="bg-white rounded-[30rpx] px-[28rpx] py-[30rpx] mb-[24rpx]">
            <view class="flex items-center justify-between mb-[30rpx]">
                <view class="flex items-center">
                    <view
                        class="w-[54rpx] h-[54rpx] rounded-[18rpx] bg-[#EAF2FF] flex items-center justify-center mr-[18rpx]">
                        <u-icon name="order" color="#2F73F6" size="26"></u-icon>
                    </view>
                    <view>
                        <text class="block text-[30rpx] font-black text-[#111827]">告诉 AI 你是谁</text>
                        <text class="block text-[22rpx] text-[#7B8798] font-semibold mt-[2rpx]"
                            >填得越准确，生成内容越好用</text
                        >
                    </view>
                </view>
                <view
                    class="h-[48rpx] px-[18rpx] rounded-full flex items-center"
                    :class="isInfoComplete ? 'bg-[#E4F8ED]' : 'bg-[#F2F6FC]'">
                    <u-icon
                        :name="isInfoComplete ? 'checkmark-circle' : 'more-circle'"
                        :color="isInfoComplete ? '#10B981' : '#9CA3AF'"
                        size="26"></u-icon>
                    <text
                        class="ml-[8rpx] text-[22rpx] font-bold"
                        :class="isInfoComplete ? 'text-[#10B981]' : 'text-[#9CA3AF]'">
                        {{ isInfoComplete ? "已完成" : "待完善" }}
                    </text>
                </view>
            </view>

            <view class="pb-[30rpx] mb-[30rpx] border-0 border-b border-solid border-[#EDF2F8]">
                <view class="flex items-start mb-[16rpx]">
                    <view
                        class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center mr-[14rpx] shrink-0">
                        <text class="text-white text-[22rpx] font-bold">1</text>
                    </view>
                    <view class="flex-1">
                        <text class="block font-black text-[#111827]">你是做什么的？</text>
                        <text class="block text-[22rpx] text-[#86909C] mt-[2rpx]">选择你的内容方向（最多 3 项）</text>
                    </view>
                </view>
                <view
                    class="min-h-[82rpx] rounded-[22rpx] bg-[#F4F7FC] px-[24rpx] py-[16rpx] flex items-center justify-between"
                    :class="industryDirectionText ? 'border-[2rpx] border-[#D9E8FF]' : ''"
                    @click="showIndustrySheet = true">
                    <text
                        class="text-xs leading-[36rpx] flex-1 line-clamp-2 mr-[12rpx]"
                        :class="industryDirectionText ? 'text-[#111827]' : 'text-[#B8C3D6]'">
                        {{ industryDirectionText || "点击选择行业方向（可多选）" }}
                    </text>
                    <u-icon name="arrow-right" color="#B8C3D6" size="26"></u-icon>
                </view>
            </view>

            <view class="pb-[30rpx] mb-[30rpx] border-0 border-b border-solid border-[#EDF2F8]">
                <view class="flex items-start mb-[16rpx]">
                    <view
                        class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center mr-[14rpx] shrink-0">
                        <text class="text-white text-[22rpx] font-bold">2</text>
                    </view>
                    <view class="flex-1">
                        <text class="block font-black text-[#111827]">你主要分享什么？</text>
                        <text class="block text-[22rpx] text-[#86909C] mt-[2rpx]">写你平时最想发的内容</text>
                    </view>
                </view>
                <view class="rounded-[22rpx] bg-[#F4F7FC] px-[24rpx] py-[18rpx]">
                    <textarea
                        v-model="formData.content_direction"
                        :maxlength="300"
                        placeholder="示例：分享敏感肌护肤知识、护肤品测评和护肤避坑"
                        placeholder-style="color:#B8C3D6;font-size:24rpx;"
                        class="w-full"
                        style="height: 112rpx; font-size: 24rpx; color: #111827" />
                </view>
            </view>

            <view class="pb-[30rpx] mb-[30rpx] border-0 border-b border-solid border-[#EDF2F8]">
                <view class="flex items-start mb-[16rpx]">
                    <view
                        class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center mr-[14rpx] shrink-0">
                        <text class="text-white text-[22rpx] font-bold">3</text>
                    </view>
                    <view class="flex-1">
                        <text class="block font-black text-[#111827]">你想给谁看？</text>
                        <text class="block text-[22rpx] text-[#86909C] mt-[2rpx]">写你的主要目标人群</text>
                    </view>
                </view>
                <view class="rounded-[22rpx] bg-[#F4F7FC] px-[24rpx] py-[18rpx]">
                    <textarea
                        v-model="formData.target_audience"
                        :maxlength="300"
                        placeholder="示例：18-35岁女生、敏感肌人群、宝妈、上班族"
                        placeholder-style="color:#B8C3D6;font-size:24rpx;"
                        class="w-full"
                        style="height: 112rpx; font-size: 24rpx; color: #111827" />
                </view>
            </view>

            <view class="pb-[30rpx] mb-[30rpx] border-0 border-b border-solid border-[#EDF2F8]">
                <view class="flex items-start mb-[16rpx]">
                    <view
                        class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center mr-[14rpx] shrink-0">
                        <text class="text-white text-[22rpx] font-bold">4</text>
                    </view>
                    <view class="flex-1">
                        <text class="block font-black text-[#111827]">你希望账号是什么感觉？</text>
                        <text class="block text-[22rpx] text-[#86909C] mt-[2rpx]"
                            >影响 AI 生成文案的语气风格和私信回复话术</text
                        >
                    </view>
                </view>
                <view
                    class="h-[82rpx] rounded-[22rpx] bg-[#F4F7FC] px-[24rpx] flex items-center justify-between"
                    :class="formData.account_style ? 'border-[2rpx] border-[#D9E8FF]' : ''"
                    @click="showStyleSheet = true">
                    <text class="text-xs" :class="formData.account_style ? 'text-[#111827]' : 'text-[#B8C3D6]'">
                        {{ formData.account_style || "点击选择账号风格" }}
                    </text>
                    <u-icon name="arrow-right" color="#B8C3D6" size="26"></u-icon>
                </view>
            </view>

            <view class="pb-[30rpx] mb-[30rpx] border-0 border-b border-solid border-[#EDF2F8]">
                <view class="flex items-start mb-[16rpx]">
                    <view
                        class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center mr-[14rpx] shrink-0">
                        <text class="text-white text-[22rpx] font-bold">5</text>
                    </view>
                    <view class="flex-1">
                        <view class="flex items-center">
                            <text class="block font-black text-[#111827]">在哪个城市 / 地区？</text>
                            <text class="ml-auto text-[22rpx] text-[#9CA3AF]">选填</text>
                        </view>
                        <text class="block text-[22rpx] text-[#86909C] mt-[2rpx]"
                            >如果你做本地内容，建议填写，精准到门牌号</text
                        >
                    </view>
                </view>
                <view
                    class="rounded-[22rpx] bg-[#F4F7FC] px-[24rpx] py-[18rpx] mb-[16rpx] flex items-center justify-between"
                    @click="formData.auto_location = !formData.auto_location">
                    <view class="pr-[20rpx]">
                        <text class="block font-black text-[#111827]">短视频自动挂载本地定位</text>
                        <text class="block text-[22rpx] text-[#86909C] mt-[4rpx]">开启后发布时自动附上门店位置</text>
                    </view>
                    <view
                        class="w-[88rpx] h-[48rpx] rounded-full p-[4rpx] flex items-center"
                        :class="formData.auto_location ? 'bg-primary justify-end' : 'bg-[#CDD6E4] justify-start'">
                        <view class="w-[40rpx] h-[40rpx] rounded-full bg-white shadow"></view>
                    </view>
                </view>
                <view class="rounded-[22rpx] bg-[#F4F7FC] px-[24rpx] py-[6rpx] flex items-center">
                    <u-icon name="map" color="#86909C" size="24"></u-icon>
                    <view class="flex-1 ml-[12rpx]">
                        <input
                            v-model="formData.city_position"
                            :maxlength="120"
                            placeholder="示例：深圳市南山区科技园 XX 大厦 1F"
                            placeholder-style="color:#B8C3D6;font-size:24rpx;"
                            class="w-full"
                            style="font-size: 24rpx; color: #111827" />
                    </view>
                </view>
            </view>

            <view class="pb-0 mb-0">
                <view class="flex items-start mb-[16rpx]">
                    <view
                        class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center mr-[14rpx] shrink-0">
                        <text class="text-white text-[22rpx] font-bold">6</text>
                    </view>
                    <view class="flex-1">
                        <text class="block text-[26rpx] font-black text-[#111827]">你希望大家看完后做什么？</text>
                        <text class="block text-[22rpx] text-[#86909C] mt-[2rpx]"
                            >决定 AI 在内容结尾如何引导用户完成行动</text
                        >
                    </view>
                </view>
                <view
                    class="h-[82rpx] rounded-[22rpx] bg-[#F4F7FC] px-[24rpx] flex items-center justify-between"
                    :class="formData.action_goal ? 'border-[2rpx] border-[#D9E8FF]' : ''"
                    @click="showGoalSheet = true">
                    <text class="text-xs" :class="formData.action_goal ? 'text-[#111827]' : 'text-[#B8C3D6]'">
                        {{ formData.action_goal || "点击选择行动目标" }}
                    </text>
                    <u-icon name="arrow-right" color="#B8C3D6" size="26"></u-icon>
                </view>
            </view>
        </view>
    </view>

    <option-select-popup
        v-model="showIndustrySheet"
        v-model:selected-value="formData.industry_direction"
        title="选择你的行业方向（最多 3 项）"
        height="72%"
        mode="grid"
        multiple
        :max-selected="3"
        :options="industryDirectionOptions"
        confirm-text="确认"
        custom-enabled
        custom-toggle-text="没找到？手动填写"
        custom-placeholder="最多 10 字，例：摄影工作室"
        :custom-maxlength="10" />

    <option-select-popup
        v-model="showStyleSheet"
        v-model:selected-value="formData.account_style"
        title="你希望账号是什么感觉？"
        height="78%"
        mode="list"
        :options="accountStyleOptions"
        custom-enabled
        custom-desc="自己描述风格，AI 按你要求生成文案和私信回复话术"
        custom-placeholder="例：像老朋友一样，接地气但又有干货…"
        :custom-maxlength="80" />

    <option-select-popup
        v-model="showGoalSheet"
        v-model:selected-value="formData.action_goal"
        title="你希望大家看完后做什么？"
        height="68%"
        mode="list"
        :options="actionGoalOptions" />
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import AvatarUpload from "@/ai_modules/person/components/avatar-upload/avatar-upload.vue";
import { PersonTypeEnum } from "@/ai_modules/person/enums";
import {
    ACCOUNT_STYLE_OPTIONS,
    ACTION_GOAL_OPTIONS,
    INDUSTRY_DIRECTION_OPTIONS,
    type BasicFormData,
} from "@/ai_modules/person/hooks/usePersonFormCore";
import OptionSelectPopup from "./option-select-popup.vue";

const props = defineProps<{
    basicForm: BasicFormData;
    avatarUrl: string;
}>();
const formData = props.basicForm;
const emit = defineEmits<{
    (event: "update:avatar", value: string): void;
    (event: "popup-visible-change", value: boolean): void;
}>();

const showIndustrySheet = ref(false);
const showStyleSheet = ref(false);
const showGoalSheet = ref(false);
const popupVisible = computed(() => showIndustrySheet.value || showStyleSheet.value || showGoalSheet.value);

const accountTypeOptions = [
    { label: "个人 IP", icon: "👤", value: PersonTypeEnum.PERSONAL_IP },
    { label: "本地商家", icon: "🏪", value: PersonTypeEnum.LOCAL_BUSINESS },
    { label: "企业服务", icon: "🏢", value: PersonTypeEnum.BUSINESS_SERVICE },
];

const industryDirectionOptions = INDUSTRY_DIRECTION_OPTIONS;
const accountStyleOptions = ACCOUNT_STYLE_OPTIONS;
const actionGoalOptions = ACTION_GOAL_OPTIONS;

// 兼容历史数据可能是字符串 / 逗号分隔字符串 / 空值的情况，统一归一为 string[]
const normalizeIndustryDirection = (value: unknown): string[] => {
    if (Array.isArray(value)) return value.map((item) => String(item ?? "").trim()).filter(Boolean);
    if (typeof value === "string") {
        return value
            .split(/[,，、]/)
            .map((item) => item.trim())
            .filter(Boolean);
    }
    return [];
};

const industryDirectionList = computed(() => normalizeIndustryDirection(formData.industry_direction));
const industryDirectionText = computed(() => industryDirectionList.value.join("、"));

const isInfoComplete = computed(() => {
    return Boolean(
        formData.persona_type && industryDirectionList.value.length && formData.account_style && formData.action_goal,
    );
});

// 兜底：若历史/接口数据以字符串形式回填，自动转回数组，避免后续 popup 与 join 触发崩溃
watch(
    () => formData.industry_direction as unknown,
    (value) => {
        if (!Array.isArray(value)) {
            formData.industry_direction = normalizeIndustryDirection(value);
        }
    },
    { immediate: true },
);

watch(
    popupVisible,
    (visible) => {
        emit("popup-visible-change", visible);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    emit("popup-visible-change", false);
});

const closePopups = (): void => {
    showIndustrySheet.value = false;
    showStyleSheet.value = false;
    showGoalSheet.value = false;
};

defineExpose({ closePopups });

const handleSelectPersonaType = (type: PersonTypeEnum) => {
    formData.persona_type = type;
};
</script>
