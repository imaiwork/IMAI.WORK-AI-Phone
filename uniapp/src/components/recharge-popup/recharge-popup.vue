<template>
    <u-popup v-model="show" mode="center" border-radius="24" width="90%" :closeable="false">
        <view
            class="p-[28rpx] relative bg-center bg-no-repeat"
            :style="{
                backgroundImage: `url(${config.baseUrl}static/images/recharge_popup_bg.png)`,
            }">
            <view class="absolute top-4 right-4" @click="close()">
                <image src="/static/images/icons/close.svg" class="w-[48rpx] h-[48rpx]"></image>
            </view>
            <view class="pt-[100rpx]">
                <view class="shop-name-text">
                    <view>
                        {{ webSiteConfig.shop_name }}
                    </view>
                    <view> 引擎驱动未来智能，AI从此高效运转。 </view>
                </view>
                <view class="text-[26rpx] mt-[32rpx]">
                    解锁更强大、更敏捷、更智慧的人工智能体验，始于核心算力的每一次跃升。
                </view>
                <view class="mt-[52rpx]">
                    <u-button
                        type="primary"
                        shape="circle"
                        :custom-style="{ height: '90rpx', fontSize: '26rpx' }"
                        @click="toRecharge"
                        >立即充值</u-button
                    >
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import config from "@/config";
import { useAppStore } from "@/stores/app";

const emit = defineEmits<{
    (e: "close"): void;
}>();

const webSiteConfig = computed(() => useAppStore().getWebsiteConfig);

const show = ref(false);

const toRecharge = () => {
    close();
    uni.$u.route({
        url: "/packages/pages/recharge/recharge",
    });
};

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
.shop-name-text {
    @apply text-[48rpx] font-bold;
    background: linear-gradient(180deg, #0065fb 0%, #cde3ef 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>
