<template>
    <popup-bottom v-model="show" title="算力消耗说明" height="70%">
        <template #content>
            <view class="h-full pt-4">
                <scroll-view class="h-full" scroll-y>
                    <view class="shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.05)] p-4 rounded-[32rpx] mx-4">
                        <view class="flex items-center gap-x-3">
                            <image
                                src="@/ai_modules/digital_human/static/images/common/icon.png"
                                class="w-[72rpx] h-[72rpx]"></image>
                            <view>
                                <view>{{ getScene.title }}</view>
                                <view class="text-[#0000004d]">{{ getScene.desc }}</view>
                            </view>
                        </view>
                        <view class="rounded-[32rpx] mt-4 px-4 bg-[#F6F6F6]">
                            <view
                                v-if="type === 1"
                                class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]">
                                <text class="text-[#00000080]">声音克隆费用</text>
                                <text
                                    >{{ getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN).score
                                    }}{{ getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN).unit }}</text
                                >
                            </view>
                            <view
                                v-if="type === 1"
                                class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]">
                                <text class="text-[#00000080]">形象克隆费用</text>
                                <text
                                    >{{ getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN).score
                                    }}{{ getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN).unit }}</text
                                >
                            </view>
                            <view
                                class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]"
                                v-if="!Array.isArray(getScene.key)">
                                <text class="text-[#00000080]">视频合成费用</text>
                                <text
                                    >{{ getTokenByScene(getScene.key).score
                                    }}{{ getTokenByScene(getScene.key).unit }}</text
                                >
                            </view>
                            <view v-if="Array.isArray(getScene.key)">
                                <view
                                    v-for="(item, index) in getScene.key"
                                    :key="index"
                                    class="h-[100rpx] flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#0000000d] text-[26rpx]">
                                    <text class="text-[#00000080]">视频合成费用({{ item.name }})</text>
                                    <text
                                        >{{ getTokenByScene(item.key).score }}{{ getTokenByScene(item.key).unit }}</text
                                    >
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        type: 1 | 2 | 3 | 4 | 5 | 6; // 1: 数字人口播混剪, 2: 真人口播智剪, 3: 素材混剪, 4: 新闻体视频, 5: 一句话生成视频,
    }>(),
    {
        type: 1,
        modelVersion: false,
    }
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

// 获取对应扣费场景
const getScene = computed(() => {
    const descriptions: Record<number, { title: string; desc: string; key: string | { name: string; key: string }[] }> =
        {
            1: {
                title: "数字人口播混剪",
                desc: "数字人+文案+素材智能混剪",
                key: TokensSceneEnum.HUMAN_VIDEO_SHANJIAN,
            },
            2: {
                title: "真人口播智剪",
                desc: "上传真人口播视频，输出网感口播视频",
                key: TokensSceneEnum.SHANJIAN_CLIP_VIDEO,
            },
            3: {
                title: "素材混剪",
                desc: "上传素材文案，自动生成视频",
                key: TokensSceneEnum.SHANJIAN_MATERIAL_VIDEO,
            },
            4: {
                title: "新闻体视频",
                desc: "自动生成新闻体混剪视频",
                key: TokensSceneEnum.SHANJIAN_NEWS_VIDEO,
            },
            5: {
                title: "一句话生成视频",
                desc: "一段话或根据场景生成视频",
                key: [
                    { name: "普通版", key: TokensSceneEnum.SORA_VIDEO },
                    { name: "PRO版", key: TokensSceneEnum.SORA_PRO_VIDEO },
                ],
            },
        };
    return descriptions[props.type];
});

const getTokenByScene = (key: string) => userStore.getTokenByScene(key);
</script>

<style scoped></style>
