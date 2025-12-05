<template>
    <popup-bottom v-model:show="showPopup" title="算力消耗规则说明" custom-class="bg-[#F9FAFB]">
        <template #content>
            <scroll-view class="h-full" scroll-y>
                <view class="px-[32rpx] pt-[30rpx]">
                    <view class="flex flex-col gap-y-[24rpx]">
                        <view v-for="(item, index) in modelChannel" :key="index" class="box">
                            <view class="flex items-center mb-[16rpx] gap-x-[24rpx] mt-[16rpx]">
                                <view class="flex-shrink-0 leading-[0]">
                                    <image class="w-[72rpx] h-[72rpx]" :src="item.icon"></image>
                                </view>
                                <view class="flex-1">
                                    <view class="text-[26rpx]">
                                        {{ item.name }}
                                    </view>
                                    <view class="text-[#B2B2B2] text-[22rpx] mt-[16rpx]">
                                        {{ item.described }}
                                    </view>
                                </view>
                            </view>
                            <view class="container">
                                <view class="cell">
                                    <view class="text-[#7D7D7D]">视频合成</view>
                                    <view>{{ item.video_create_model }}</view>
                                </view>
                                <view class="cell">
                                    <view>音色克隆</view>
                                    <view>{{ item.tone_clone }}</view>
                                </view>
                                <view class="cell">
                                    <view>形象克隆</view>
                                    <view>{{ item.anchor_clone }}</view>
                                </view>
                                <view class="cell">
                                    <view>声音克隆费用</view>
                                    <view>{{ item.voice_token || "免费" }}</view>
                                </view>
                                <view class="cell">
                                    <view> 形象克隆费用 </view>
                                    <view>
                                        {{ item.anchor_token || "免费" }}
                                    </view>
                                </view>
                                <view class="cell">
                                    <view> 音频合成费用 </view>
                                    <view>
                                        {{ item.audio_token || "免费" }}
                                    </view>
                                </view>
                                <view class="cell">
                                    <view> 视频合成费用 </view>
                                    <view>
                                        {{ item.video_token || "免费" }}
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[60rpx]">
                        <view class="text-[30rpx] font-bold">常见问题</view>
                        <view class="mt-[40rpx] flex flex-col gap-y-[40rpx]">
                            <view>
                                <view class="text-[26rpx] font-bold">
                                    Q：为什么每次生成视频后，总会退款一部分算力?
                                </view>
                                <view class="text-[#7D7D7E] text-[26rpx] mt-[20rpx] leading-5">
                                    1.当您开始生成视频时，系统会按照对应计费标准来预估该视频消耗的算力值(可能存在细微偏差)。当视频生成完成后，系统会退回实际消耗算力与预估消耗算力的偏差部分，即多扣的算力值。
                                </view>
                            </view>
                            <view>
                                <view class="text-[26rpx] font-bold"> Q：视频生成失败了，还会扣我的算力吗 ? </view>
                                <view class="text-[#7D7D7E] text-[26rpx] mt-[20rpx] leading-5">
                                    1.生成失败的视频，会退回对应预扣的算力。
                                </view>
                            </view>
                            <view>
                                <view class="text-[26rpx] font-bold"> Q：声音克隆什么时候扣费 ? </view>
                                <view class="text-[#7D7D7E] text-[26rpx] mt-[20rpx] leading-5">
                                    1.如果是单独克隆声音，只有在最终保存声音时才会扣除。保存后的声音将可以在数字人视频制作中使用。对于数字人定制，则会在提交所有内容时统一扣费。
                                </view>
                            </view>
                            <view>
                                <view class="text-[26rpx] font-bold"> Q：为什么数字人定制失败后只退回部分算力 ? </view>
                                <view class="text-[#7D7D7E] text-[26rpx] mt-[20rpx] leading-5">
                                    1.数字人定制包括形象定制和声音定制。如果形象定制失败但声音定制成功，仅退回与形象定制相关的算力。重新提交时，只需重新训练形象，无需再次训练声音，系统默认会选中“不克隆的声音”。
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { DigitalHumanModelVersionEnum } from "../../enums";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});
const emit = defineEmits(["update:show"]);

const showPopup = computed({
    get() {
        return props.show;
    },
    set(val) {
        emit("update:show", val);
    },
});
const appStore = useAppStore();
const userStore = useUserStore();

const getTokenByScene = (key: string) => userStore.getTokenByScene(key);

const modelChannel = computed(() => {
    const { channel } = appStore.getDigitalHumanConfig;
    if (!channel?.length) return [];

    const modelConfigs = {
        // [DigitalHumanModelVersionEnum.STANDARD]: {
        //     video_create_model: "微聚V3",
        //     tone_clone: "Fish Audio",
        //     anchor_clone: "微聚V3",
        //     tokens: {
        //         voice: TokensSceneEnum.HUMAN_VOICE,
        //         anchor: TokensSceneEnum.HUMAN_AVATAR,
        //         audio: TokensSceneEnum.HUMAN_AUDIO,
        //         video: TokensSceneEnum.HUMAN_VIDEO,
        //     },
        // },
        // [DigitalHumanModelVersionEnum.SUPER]: {
        //     video_create_model: "阿里云",
        //     tone_clone: "Fish Audio",
        //     anchor_clone: "阿里云",
        //     tokens: {
        //         voice: TokensSceneEnum.HUMAN_VOICE_PRO,
        //         anchor: TokensSceneEnum.HUMAN_AVATAR_PRO,
        //         audio: TokensSceneEnum.HUMAN_AUDIO_PRO,
        //         video: TokensSceneEnum.HUMAN_VIDEO_PRO,
        //     },
        // },
        // [DigitalHumanModelVersionEnum.ADVANCED]: {
        //     video_create_model: "优秘V5",
        //     tone_clone: "优秘V5",
        //     anchor_clone: "优秘V5",
        //     tokens: {
        //         voice: TokensSceneEnum.HUMAN_VOICE_ADVANCED,
        //         anchor: TokensSceneEnum.HUMAN_AVATAR_ADVANCED,
        //         audio: TokensSceneEnum.HUMAN_AUDIO_ADVANCED,
        //         video: TokensSceneEnum.HUMAN_VIDEO_ADVANCED,
        //     },
        // },
        // [DigitalHumanModelVersionEnum.ELITE]: {
        //     video_create_model: "优秘V7",
        //     tone_clone: "优秘V7",
        //     anchor_clone: "优秘V7",
        //     tokens: {
        //         voice: TokensSceneEnum.HUMAN_VOICE_ELITE,
        //         anchor: TokensSceneEnum.HUMAN_AVATAR_ELITE,
        //         audio: TokensSceneEnum.HUMAN_AUDIO_ELITE,
        //         video: TokensSceneEnum.HUMAN_VIDEO_ELITE,
        //     },
        // },
        [DigitalHumanModelVersionEnum.CHANJING]: {
            video_create_model: "蝉镜V1",
            tone_clone: "蝉镜V1",
            anchor_clone: "蝉镜V1",
            tokens: {
                voice: TokensSceneEnum.HUMAN_VOICE_CHANJING,
                anchor: TokensSceneEnum.HUMAN_AVATAR_CHANJING,
                audio: TokensSceneEnum.HUMAN_AUDIO_CHANJING,
                video: TokensSceneEnum.HUMAN_VIDEO_CHANJING,
            },
        },
    };

    return channel
        .filter((item: any) => item.status == 1 && DigitalHumanModelVersionEnum.CHANJING == item.id)
        .map((item: any) => {
            // @ts-ignore
            const config = modelConfigs[item.id];
            if (!config) return item;

            const formatToken = (scene: string) => {
                const value = getTokenByScene(scene);
                return value.score ? `${value.score}${value.unit}` : 0;
            };

            return {
                ...item,
                ...config,
                voice_token: formatToken(config.tokens.voice),
                anchor_token: formatToken(config.tokens.anchor),
                audio_token: formatToken(config.tokens.audio),
                video_token: formatToken(config.tokens.video),
            };
        });
});
</script>

<style scoped lang="scss">
.box {
    @apply bg-white rounded-[48rpx] p-[16rpx];
    box-shadow: 0px 6px 12px 0px rgba(0, 0, 0, 0.05);
}
.container {
    @apply rounded-[48rpx] px-[24rpx] mt-[32rpx];
    background: linear-gradient(180deg, #f9f9f9 0%, rgba(0, 0, 0, 0) 100%);
    .cell {
        @apply flex items-center justify-between h-[100rpx] text-[26rpx];
        border-bottom: 1rpx solid #e5e5e5;
        &:last-child {
            border-bottom: none;
        }
    }
}
</style>
