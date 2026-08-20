<template>
    <popup-bottom v-model="show" title="算力消耗说明" height="70%">
        <template #content>
            <view class="h-full flex flex-col">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-[24rpx] pt-[16rpx] pb-[40rpx] flex flex-col gap-[16rpx]">
                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center gap-[20rpx] px-[28rpx] py-[28rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view
                                    class="w-[80rpx] h-[80rpx] rounded-[20rpx] overflow-hidden flex-shrink-0 shadow-[0_4rpx_12rpx_rgba(0,0,0,0.10)]">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/icon.png"
                                        class="w-full h-full"
                                        mode="aspectFill" />
                                </view>
                                <view class="flex-1 min-w-0">
                                    <text class="text-[30rpx] font-extrabold text-[#0D1117] block">{{
                                        getScene.title
                                    }}</text>
                                    <text class="text-xs text-[#9CA3AF] mt-[6rpx] block">{{ getScene.desc }}</text>
                                </view>
                            </view>

                            <view class="px-[28rpx]">
                                <view
                                    v-if="type === 1"
                                    class="flex items-center justify-between h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                    <view class="flex items-center gap-[12rpx]">
                                        <view class="w-[8rpx] h-[8rpx] rounded-full bg-[#0EA5E9]" />
                                        <text class="text-[#4B5563]">声音克隆费用</text>
                                    </view>
                                    <view class="flex items-center gap-[6rpx]">
                                        <text class="text-[28rpx] font-extrabold text-primary">
                                            {{ getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN).score }}
                                        </text>
                                        <text class="text-[22rpx] text-[#9CA3AF]">
                                            {{ getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN).unit }}
                                        </text>
                                    </view>
                                </view>

                                <view
                                    v-if="type === 1"
                                    class="flex items-center justify-between h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                    <view class="flex items-center gap-[12rpx]">
                                        <view class="w-[8rpx] h-[8rpx] rounded-full bg-[#8B5CF6]" />
                                        <text class="text-[#4B5563]">形象克隆费用</text>
                                    </view>
                                    <view class="flex items-center gap-[6rpx]">
                                        <text class="text-[28rpx] font-extrabold text-primary">
                                            {{ getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN).score }}
                                        </text>
                                        <text class="text-[22rpx] text-[#9CA3AF]">
                                            {{ getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN).unit }}
                                        </text>
                                    </view>
                                </view>

                                <view
                                    v-if="!Array.isArray(getScene.key)"
                                    class="flex items-center justify-between h-[96rpx]">
                                    <view class="flex items-center gap-[12rpx]">
                                        <view class="w-[8rpx] h-[8rpx] rounded-full bg-primary" />
                                        <text class="text-[#4B5563]">视频合成费用</text>
                                    </view>
                                    <view class="flex items-center gap-[6rpx]">
                                        <text class="text-[28rpx] font-extrabold text-primary">
                                            {{ getTokenByScene(getScene.key).score }}
                                        </text>
                                        <text class="text-[22rpx] text-[#9CA3AF]">
                                            {{ getTokenByScene(getScene.key).unit }}
                                        </text>
                                    </view>
                                </view>

                                <view v-if="Array.isArray(getScene.key)">
                                    <view
                                        v-for="(item, index) in getScene.key"
                                        :key="index"
                                        class="flex items-center justify-between h-[96rpx]"
                                        :class="
                                            index < getScene.key.length - 1
                                                ? 'border-[0] border-b border-solid border-[#F0F2F5]'
                                                : ''
                                        ">
                                        <view class="flex items-center gap-[12rpx]">
                                            <view class="w-[8rpx] h-[8rpx] rounded-full bg-primary" />
                                            <text class="text-[#4B5563]">{{ item.name }}</text>
                                        </view>
                                        <view class="flex items-center gap-[6rpx]">
                                            <text class="text-[28rpx] font-extrabold text-primary">
                                                {{ getTokenByScene(item.key).score }}
                                            </text>
                                            <text class="text-[22rpx] text-[#9CA3AF]">
                                                {{ getTokenByScene(item.key).unit }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="flex items-start gap-[12rpx] bg-[#EBF2FF] rounded-[20rpx] px-[24rpx] py-[20rpx]">
                            <u-icon name="info-circle" color="#0065fb" size="28" class="flex-shrink-0 mt-[2rpx]" />
                            <text class="text-xs text-[#4B5563] leading-relaxed flex-1">
                                以上为单次任务消耗算力，实际扣费以任务完成时为准。算力不足时请前往充值中心补充。
                            </text>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
// script 完全不变
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { MontageTypeEnum } from "@/ai_modules/digital_human/enums";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        type: MontageTypeEnum;
    }>(),
    {
        type: 1,
        modelVersion: false,
    },
);

const emit = defineEmits(["update:modelValue"]);
const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const userStore = useUserStore();

const getScene = computed(() => {
    const descriptions: Record<number, { title: string; desc: string; key: string | { name: string; key: string }[] }> =
        {
            [MontageTypeEnum.REAL_PERSON_MIX]: {
                title: "数字人口播混剪",
                desc: "数字人+文案+素材智能混剪",
                key: TokensSceneEnum.HUMAN_VIDEO_SHANJIAN,
            },
            [MontageTypeEnum.REAL_PERSON_AI]: {
                title: "真人口播智剪",
                desc: "上传真人口播视频，输出网感口播视频",
                key: TokensSceneEnum.SHANJIAN_CLIP_VIDEO,
            },
            [MontageTypeEnum.MATERIAL_MIX]: {
                title: "素材混剪",
                desc: "上传素材文案，自动生成视频",
                key: TokensSceneEnum.SHANJIAN_MATERIAL_VIDEO,
            },
            [MontageTypeEnum.NEWS_BODY]: {
                title: "新闻体视频",
                desc: "自动生成新闻体混剪视频",
                key: TokensSceneEnum.SHANJIAN_NEWS_VIDEO,
            },
            [MontageTypeEnum.SORA_VIDEO]: {
                title: "一句话生成视频",
                desc: "一段话或根据场景生成视频",
                key: [
                    { name: "480P图片转视频版", key: TokensSceneEnum.SEEDANCE2_480P_IMAGE2VIDEO },
                    { name: "480P视频转视频版", key: TokensSceneEnum.SEEDANCE2_480P_VIDEO2VIDEO },
                    { name: "720P图片转视频版", key: TokensSceneEnum.SEEDANCE2_720P_IMAGE2VIDEO },
                    { name: "720P视频转视频版", key: TokensSceneEnum.SEEDANCE2_720P_VIDEO2VIDEO },
                ],
            },
            [MontageTypeEnum.STORYBOARD_MIX]: {
                title: "分镜混剪",
                desc: "分镜混剪",
                key: TokensSceneEnum.STORYBOARD_MIX,
            },
        };
    return descriptions[props.type];
});

const getTokenByScene = (key: string) => userStore.getTokenByScene(key);
</script>

<style scoped></style>
