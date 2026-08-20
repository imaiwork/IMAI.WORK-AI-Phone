<template>
    <view>
        <!-- 发布平台（单选，不同平台独立配置） -->
        <view class="flex items-center mb-[16rpx]">
            <text class="op-row-label">发布平台</text>
            <text class="op-sub-tip">（不同平台独立配置）</text>
        </view>
        <view class="op-plat-grid mb-[36rpx]">
            <view
                v-for="item in PUBLISH_PLATFORM_LIST"
                :key="item.platform"
                class="op-plat-btn"
                :class="{ active: activePlatform === item.platform }"
                @click.stop="emit('update:activePlatform', item.platform)">
                {{ item.label }}
            </view>
        </view>

        <!-- 发布内容类型：视频 / 图文 -->
        <view class="flex items-center justify-between mb-[36rpx]">
            <text class="op-row-label">发布内容类型</text>
            <view class="op-seg">
                <view
                    class="op-seg-btn"
                    :class="{ active: current.publish_media_type === PublishMediaTypeEnum.VIDEO }"
                    @click.stop="current.publish_media_type = PublishMediaTypeEnum.VIDEO">
                    视频
                </view>
                <view
                    v-if="activePlatform === PublishPlatformEnum.XIAOHONGSHU"
                    class="op-seg-btn"
                    :class="{ active: current.publish_media_type === PublishMediaTypeEnum.IMAGE }"
                    @click.stop="current.publish_media_type = PublishMediaTypeEnum.IMAGE">
                    图文
                </view>
            </view>
        </view>

        <!-- 发布文案生成方式：自动生成 / 素材库引用 -->
        <view class="flex items-center justify-between mb-[36rpx] gap-[20rpx]">
            <text class="op-row-label shrink-0">发布文案生成方式</text>
            <view class="op-seg shrink-0">
                <view
                    class="op-seg-btn"
                    :class="{ active: current.publish_copywriting_source === PublishCopySourceEnum.AUTO }"
                    @click.stop="current.publish_copywriting_source = PublishCopySourceEnum.AUTO">
                    自动生成
                </view>
                <view
                    class="op-seg-btn"
                    :class="{ active: current.publish_copywriting_source === PublishCopySourceEnum.LIBRARY }"
                    @click.stop="current.publish_copywriting_source = PublishCopySourceEnum.LIBRARY">
                    素材库引用
                </view>
            </view>
        </view>

        <!-- 自动生成面板 -->
        <view v-if="current.publish_copywriting_source === PublishCopySourceEnum.AUTO" class="space-y-[24rpx]">
            <view class="flex items-center justify-between">
                <text class="op-row-label">生成依据</text>
                <view class="op-seg">
                    <view
                        class="op-seg-btn"
                        :class="{ active: current.generate_basis === PublishBasisEnum.PERSONA }"
                        @click.stop="current.generate_basis = PublishBasisEnum.PERSONA">
                        根据人设
                    </view>
                    <view
                        class="op-seg-btn"
                        :class="{ active: current.generate_basis === PublishBasisEnum.CUSTOM }"
                        @click.stop="current.generate_basis = PublishBasisEnum.CUSTOM">
                        自定义方向
                    </view>
                </view>
            </view>
            <input
                v-if="current.generate_basis === PublishBasisEnum.CUSTOM"
                v-model="current.custom_direction"
                class="cfg-inp"
                placeholder="例：本期主题是换季护肤，重点突出保湿和修护…" />
        </view>

        <!-- 素材库引用面板 -->
        <view v-else class="space-y-[24rpx]">
            <view class="op-tip-box">
                直接从该人设「素材库 · 文案库 · 发布文案」中取用，已审过的内容直接发，无需 AI 生成。
            </view>
            <view class="flex items-center justify-between gap-[20rpx]">
                <text class="op-row-label shrink-0">使用方式</text>
                <view class="op-seg shrink-0">
                    <view
                        class="op-seg-btn"
                        :class="{ active: current.library_use_mode === PublishLibraryUseModeEnum.RANDOM }"
                        @click.stop="current.library_use_mode = PublishLibraryUseModeEnum.RANDOM">
                        随机使用
                    </view>
                    <view
                        class="op-seg-btn"
                        :class="{ active: current.library_use_mode === PublishLibraryUseModeEnum.SEQUENCE }"
                        @click.stop="current.library_use_mode = PublishLibraryUseModeEnum.SEQUENCE">
                        顺序使用
                    </view>
                </view>
            </view>
            <view v-if="current.library_use_mode === PublishLibraryUseModeEnum.RANDOM">
                <view class="flex items-center justify-between gap-[20rpx]">
                    <text class="op-row-label shrink-0">随机规则</text>
                    <view class="op-seg shrink-0">
                        <view
                            class="op-seg-btn"
                            :class="{ active: current.library_reuse_mode === PublishLibraryReuseModeEnum.ONCE }"
                            @click.stop="current.library_reuse_mode = PublishLibraryReuseModeEnum.ONCE">
                            每条只用一次
                        </view>
                        <view
                            class="op-seg-btn"
                            :class="{ active: current.library_reuse_mode === PublishLibraryReuseModeEnum.REPEAT }"
                            @click.stop="current.library_reuse_mode = PublishLibraryReuseModeEnum.REPEAT">
                            可重复使用
                        </view>
                    </view>
                </view>
                <text class="op-note">{{ libraryReuseNote }}</text>
            </view>
        </view>

        <!-- 发布内容定位 -->
        <view class="mt-[40rpx]">
            <view class="setting-block">
                <view>
                    <text class="block setting-title">发布内容定位</text>
                    <text class="block setting-desc">开启后每条内容自动附带地理位置</text>
                </view>
                <view class="shrink-0" @click.stop>
                    <u-switch v-model="contentLocationEnabledModel" :size="34" inactive-color="#E5E7EB" />
                </view>
            </view>
            <input
                v-if="current.is_content_location === 1"
                v-model="current.content_location"
                class="cfg-inp mt-[20rpx]"
                placeholder="输入定位地址，如：上海市 · 黄浦区" />
        </view>

        <!-- 抖音专属：挂载购物车 + 商家定位 -->
        <template v-if="activePlatform === PublishPlatformEnum.DOUYIN">
            <view class="setting-block mt-[40rpx]">
                <view>
                    <view class="flex items-center gap-[12rpx]">
                        <text class="setting-title">挂载购物车</text>
                        <text class="op-plat-tag">抖音专属</text>
                    </view>
                    <text class="block setting-desc">默认挂载前 6 个，若无商品则不生效</text>
                </view>
                <view class="shrink-0" @click.stop>
                    <u-switch v-model="cartEnabledModel" :size="34" inactive-color="#E5E7EB" />
                </view>
            </view>

            <view class="mt-[40rpx]">
                <view class="setting-block">
                    <view>
                        <view class="flex items-center gap-[12rpx]">
                            <text class="setting-title">商家定位</text>
                            <text class="op-plat-tag">抖音专属</text>
                        </view>
                        <text class="block setting-desc">开启后挂载对应的定位，需填写定位地址</text>
                    </view>
                    <view class="shrink-0" @click.stop>
                        <u-switch v-model="storeEnabledModel" :size="34" inactive-color="#E5E7EB" />
                    </view>
                </view>
                <input
                    v-if="storeEnabled"
                    v-model="storeTextModel"
                    class="cfg-inp mt-[20rpx]"
                    placeholder="输入商家定位地址，如：上海市 · 黄浦区 · XX 门店" />
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import {
    PUBLISH_PLATFORM_LIST,
    PublishPlatformEnum,
    PublishMediaTypeEnum,
    PublishCopySourceEnum,
    PublishBasisEnum,
    PublishLibraryUseModeEnum,
    PublishLibraryReuseModeEnum,
    getPublishPlatformDefault,
    type PlatformPublishConfig,
} from "@/ai_modules/person/enums";

const props = defineProps<{
    platformConfigs: Record<number, PlatformPublishConfig>;
    activePlatform: PublishPlatformEnum;
    cartEnabled: boolean;
    storeEnabled: boolean;
    storeText: string;
}>();

const emit = defineEmits<{
    (e: "update:activePlatform", v: PublishPlatformEnum): void;
    (e: "update:cartEnabled", v: boolean): void;
    (e: "update:storeEnabled", v: boolean): void;
    (e: "update:storeText", v: string): void;
}>();

// 当前平台配置：直接读取父层传入的 reactive 对象，输入项通过 v-model 就地写回
const current = computed(
    () => props.platformConfigs[props.activePlatform] || getPublishPlatformDefault(props.activePlatform),
);

const LIBRARY_REUSE_NOTE: Record<PublishLibraryReuseModeEnum, string> = {
    [PublishLibraryReuseModeEnum.ONCE]: "用完即跳过，避免平台查重。文案用完后该 AI 员工将自动暂停。",
    [PublishLibraryReuseModeEnum.REPEAT]: "所有文案均参与抽取，文案不会耗尽。",
};
const libraryReuseNote = computed(() => LIBRARY_REUSE_NOTE[current.value.library_reuse_mode]);

const contentLocationEnabledModel = computed({
    get: () => current.value.is_content_location === 1,
    set: (v: boolean) => {
        current.value.is_content_location = v ? 1 : 0;
    },
});
const cartEnabledModel = computed({
    get: () => props.cartEnabled,
    set: (v: boolean) => emit("update:cartEnabled", v),
});
const storeEnabledModel = computed({
    get: () => props.storeEnabled,
    set: (v: boolean) => emit("update:storeEnabled", v),
});
const storeTextModel = computed({
    get: () => props.storeText,
    set: (v: string) => emit("update:storeText", v),
});
</script>

<style scoped lang="scss">
// 与主文件保持一致的基础工具类（scoped 不串）
.cfg-inp {
    @apply w-full min-h-[88rpx] bg-[#F9FAFB] border-[3rpx] border-solid border-[transparent] rounded-[24rpx] px-[28rpx] text-[24rpx] text-[#1d2129] box-border;
}

.setting-block {
    @apply flex items-center justify-between gap-[24rpx];
}

.setting-title {
    @apply text-[26rpx] font-bold text-[#1d2129];
}

.setting-desc {
    @apply block text-[22rpx] text-[#9ca3af] mt-[4rpx];
}

.op-row-label {
    @apply text-[26rpx] font-bold text-[#4e5969];
}

.op-sub-tip {
    @apply text-[22rpx] text-[#9ca3af] ml-[8rpx];
}

.op-plat-grid {
    @apply grid grid-cols-4 gap-[16rpx];
}

.op-plat-btn {
    @apply h-[88rpx] rounded-[20rpx] bg-[#F5F6F8] border border-solid border-[#E9EDF4] flex items-center justify-center text-[24rpx] font-bold text-[#9CA3AF];

    &.active {
        @apply bg-[#EFF5FF] border-primary text-primary;
    }
}

.op-plat-tag {
    @apply text-[18rpx] font-semibold text-primary bg-[#EFF5FF] rounded-[8rpx] py-[2rpx] px-[10rpx];
}

.op-tip-box {
    @apply bg-[#F7F9FC] border border-solid border-[#EEF2F8] rounded-[20rpx] py-[18rpx] px-[22rpx] text-[22rpx] text-[#6B7280] leading-[1.6];
}

.op-note {
    @apply block text-[20rpx] text-[#9CA3AF] mt-[14rpx] leading-[1.6];
}

.op-seg {
    @apply inline-flex bg-[#f3f4f6] rounded-[24rpx] p-[6rpx] gap-[4rpx];
}

.op-seg-btn {
    @apply px-[24rpx] min-h-[56rpx] rounded-[20rpx] text-[24rpx] font-semibold text-[#9ca3af] flex items-center justify-center whitespace-nowrap;

    &.active {
        @apply bg-white text-[#1f2937];
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
    }
}
</style>
