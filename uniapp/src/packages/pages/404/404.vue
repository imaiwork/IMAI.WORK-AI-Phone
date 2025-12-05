<template>
    <view class="h-screen flex flex-col justify-center items-center">
        <view>
            <u-empty text="对不起，您访问的页面不存在" mode="data"></u-empty>
        </view>
        <view class="w-full px-[100rpx] mt-[40rpx]">
            <router-navigate
                class="bg-primary rounded-full text-btn-text leading-[80rpx] text-center"
                to="/"
                nav-type="reLaunch">
                返回首页
            </router-navigate>
        </view>
    </view>
</template>

<script setup lang="ts"></script>
