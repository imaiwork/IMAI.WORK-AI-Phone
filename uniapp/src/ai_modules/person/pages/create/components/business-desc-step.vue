<template>
    <view>
        <view
            class="rounded-[32rpx] px-[28rpx] py-[24rpx] mb-[24rpx] flex items-start border border-solid border-[#BAD4FF]"
            style="background: linear-gradient(135deg, #ebf3ff, #f0f7ff)">
            <view
                class="w-[56rpx] h-[56rpx] rounded-[20rpx] bg-[#2F73F6] flex items-center justify-center mr-[20rpx] shrink-0">
                <u-icon name="info-circle" color="#ffffff" size="28"></u-icon>
            </view>
            <view class="flex-1">
                <text class="block text-[24rpx] leading-[1.4] font-bold text-[#1D2129]">
                    填得越准确，AI 内容越好用
                </text>
                <text class="block text-[22rpx] text-[#5C7ECC] leading-[1.6] mt-[8rpx]">
                    用自己的话说就行，不用写得多好，AI 会根据你说的内容帮你生成视频文案和回复话术。
                </text>
            </view>
        </view>

        <view
            class="bg-white rounded-[40rpx] px-[32rpx] pt-[40rpx] pb-[48rpx] mb-[28rpx] shadow-[0_4rpx_32rpx_rgba(47,115,246,0.07)]">
            <view class="flex items-start mb-[40rpx]">
                <view
                    class="w-[64rpx] h-[64rpx] rounded-[24rpx] bg-[#FFF1E6] flex items-center justify-center mr-[20rpx] shrink-0">
                    <u-icon name="file-text" color="#FF7D00" size="30"></u-icon>
                </view>
                <view class="flex-1 pt-[2rpx]">
                    <text class="block text-[32rpx] leading-[1.4] font-bold text-[#1D2129]"> 业务描述 </text>
                    <text class="block text-[22rpx] leading-[1.4] text-[#86909C] mt-[4rpx]">
                        用大白话说清楚，AI 帮你写出更好的内容
                    </text>
                </view>
            </view>

            <view v-for="(field, index) in bizFields" :key="field.key">
                <view class="mb-[32rpx]">
                    <view class="flex items-center mb-[14rpx]">
                        <view
                            class="w-[52rpx] h-[52rpx] rounded-[16rpx] flex items-center justify-center mr-[14rpx] shrink-0"
                            :class="field.iconClass">
                            <u-icon :name="field.icon" :color="field.iconColor" size="26"></u-icon>
                        </view>
                        <text class="text-[26rpx] leading-[1.35] font-semibold text-[#1D2129] flex-1">
                            {{ field.title }}
                        </text>
                        <text
                            class="ml-[10rpx] px-[14rpx] h-[36rpx] rounded-[10rpx] leading-[36rpx] text-[20rpx] font-semibold text-[#FF4D4F] bg-[#FFF1F0] border border-solid border-[#FFCCC7] shrink-0">
                            必填
                        </text>
                        <text
                            class="ml-[10rpx] px-[16rpx] h-[40rpx] rounded-full leading-[40rpx] text-[20rpx] text-primary bg-[#EBF3FF] shrink-0"
                            @click="toggleHint(field.key)">
                            {{ openHints[field.key] ? "收起示例" : "查看示例" }}
                        </text>
                    </view>

                    <view
                        v-if="openHints[field.key]"
                        class="mb-[16rpx] px-[24rpx] py-[18rpx] rounded-[24rpx] bg-[#FFFBEB]">
                        <text
                            v-for="line in field.example"
                            :key="line"
                            class="block text-[22rpx] leading-[1.65] text-[#92400E]">
                            {{ line }}
                        </text>
                    </view>

                    <view
                        class="rounded-[24rpx] bg-[#F3F7FC] px-[24rpx] py-[20rpx] border-[3rpx] border-solid border-[transparent]">
                        <textarea
                            v-model="currentSubForm[field.modelKey]"
                            :maxlength="1000"
                            :placeholder="field.placeholder"
                            placeholder-style="color:#C2C9D1;font-size:26rpx;font-weight:400;line-height:1.65;"
                            class="w-full"
                            style="height: 156rpx; font-size: 26rpx; line-height: 1.65; color: #1d2129; font-weight: 400" />
                    </view>
                    <view class="flex justify-end mt-[8rpx]">
                        <text class="text-[20rpx] leading-[1.4] text-[#C0C8D8]">
                            {{ (currentSubForm[field.modelKey] || "").length }} 字
                        </text>
                    </view>
                </view>

                <view v-if="index < bizFields.length - 1" class="h-[2rpx] bg-[#F3F7FC] mb-[32rpx]"></view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, reactive } from "vue";

interface BizField {
    key: string;
    modelKey: string;
    icon: string;
    iconColor: string;
    iconClass: string;
    title: string;
    placeholder: string;
    example: string[];
}

defineProps<{
    isAddMode: boolean;
    analysisTokenScore: number;
    currentSubForm: Record<string, any>;
}>();

const emit = defineEmits<{
    (event: "open-voice"): void;
}>();

const bizFields = computed<BizField[]>(() => [
    {
        key: "product",
        modelKey: "main_business",
        icon: "gift",
        iconColor: "#F97316",
        iconClass: "bg-[#FFF3E6]",
        title: "你在做什么 / 卖什么？",
        placeholder: "例：我在做宠物洗护，也卖生骨肉猫粮；或：我拍敏感肌护肤测评视频",
        example: ['💡 例："猫咪洗护 · 生骨肉定制 · 宠物寄养"', '"敏感肌护肤推荐 · 成分科普 · 爆款好物种草"'],
    },
    {
        key: "audience",
        modelKey: "target_pain_points",
        icon: "account",
        iconColor: "#2F73F6",
        iconClass: "bg-[#EBF3FF]",
        title: "你想卖给谁 / 给谁看？",
        placeholder: "例：深圳养猫的宝妈，30岁左右，担心猫咪吃不好；或：有敏感肌的上班族女生",
        example: ['💡 例："深圳铲屎官 · 养猫宝妈 · 注重健康的年轻人"', '"18-35岁都市女性，有敏感肌困扰，注重护肤品质"'],
    },
    {
        key: "advantage",
        modelKey: "conversion_hook",
        icon: "zhuanfa",
        iconColor: "#7C3AED",
        iconClass: "bg-[#F0EEFF]",
        title: "你比别人好在哪？",
        placeholder: "例：食材零添加、洗护不哭闹；或：产品全部亲测，不接广告，真实推荐",
        example: ['💡 例："零添加食材 · 温柔洗护无哭声"', '"所有产品亲测不踩雷，拒绝广告硬植入，干货丰富"'],
    },
]);

const openHints = reactive<Record<string, boolean>>({});

const toggleHint = (key: string): void => {
    openHints[key] = !openHints[key];
};
</script>
