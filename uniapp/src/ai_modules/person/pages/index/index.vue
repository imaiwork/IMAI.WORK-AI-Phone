<template>
    <view class="min-h-screen bg-[#F4F7FA] pb-[100rpx] relative">
        <view
            class="absolute top-0 left-0 right-0 h-[480rpx] rounded-b-[60rpx] z-0"
            style="background: linear-gradient(180deg, #0048ce 0%, #0065fb 50%, #4facfe 100%)"></view>

        <u-navbar
            :border-bottom="false"
            :background="{ background: 'transparent' }"
            :back-icon-color="navColor"
            :title-color="navColor">
        </u-navbar>

        <view class="relative z-10 px-[30rpx] pt-2 pb-6">
            <text class="text-[44rpx] font-extrabold text-white block tracking-wide">我的IP矩阵</text>
            <text class="text-[26rpx] text-[#ffffff]/80 mt-2 block font-medium">管理你的24h任务内容配置</text>
        </view>

        <view class="px-[30rpx] relative z-10">
            <view
                class="bg-white rounded-[40rpx] p-5 shadow-[0_16rpx_40rpx_rgba(0,101,251,0.08)] mb-8 border-[2rpx] border-white">
                <view class="flex items-center gap-2 mb-4">
                    <view class="w-[48rpx] h-[48rpx] rounded-full bg-[#E6F0FF] flex items-center justify-center">
                        <image src="@/ai_modules/person/static/icons/user_add.svg" class="w-[28rpx] h-[28rpx]"></image>
                    </view>
                    <text class="text-[30rpx] font-extrabold text-[#212121]">极速创建IP</text>
                </view>

                <view
                    class="flex items-center bg-[#f8f9fd] rounded-full p-1.5 mb-4 border border-solid border-[#e3e3e3]"
                    @click="handleCreate">
                    <view class="flex-1 px-4">
                        <text class="text-[#9CA3AF]">输入你行业或定位等</text>
                    </view>
                    <view
                        class="ripple-btn px-6 py-2.5 rounded-full shadow-[0_4rpx_12rpx_rgba(0,101,251,0.3)] bg-primary flex items-center justify-center">
                        <text class="text-white text-[26rpx] font-bold">立即创建</text>
                    </view>
                </view>
            </view>

            <view>
                <view class="flex items-center gap-2 mb-4 px-1">
                    <view class="w-1.5 h-4 bg-primary rounded-full"></view>
                    <text class="text-[30rpx] font-extrabold text-[#212121]">我的IP ({{ total }})</text>
                </view>

                <view
                    v-if="!loading && ipList.length === 0"
                    class="flex flex-col items-center justify-center py-16 gap-3">
                    <empty text="还没有创建任何IP，快去创建吧" />
                </view>

                <view class="flex flex-col gap-4">
                    <view
                        v-for="(item, index) in ipList"
                        :key="index"
                        class="bg-white rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)] flex items-center gap-4"
                        @click="handleIpClick(item)">
                        <view
                            class="w-[100rpx] h-[100rpx] rounded-[24rpx] flex-shrink-0"
                            style="background: linear-gradient(135deg, #f8f9fd 0%, #e6f0ff 100%)">
                            <image
                                :src="item.avatar_url"
                                class="w-full h-full rounded-[24rpx]"
                                mode="aspectFill"></image>
                        </view>

                        <view class="flex-1">
                            <text class="text-[30rpx] font-bold text-[#212121] mb-1.5 line-clamp-1 break-all">{{
                                item.persona_name
                            }}</text>
                            <view class="flex items-center gap-2">
                                <view
                                    class="px-2.5 py-0.5 rounded-full flex items-center justify-center"
                                    :style="getTagStyle(item.persona_type)">
                                    <text class="text-[22rpx] font-bold" :style="getTagTextStyle(item.persona_type)">{{
                                        PersonTypeMap[item.persona_type as PersonTypeEnum]
                                    }}</text>
                                </view>
                            </view>
                        </view>

                        <view class="flex justify-center gap-1.5">
                            <view v-if="item.is_configured === 1" class="flex items-center gap-1.5">
                                <view class="w-1.5 h-1.5 rounded-full bg-[#00C08E] shadow-[0_0_8px_#00C08E]"></view>
                                <text class="text-[24rpx] text-[#9ca3af]">
                                    关联设备: <text class="font-bold text-gray-700">{{ item.device_num }}</text>
                                </text>
                            </view>
                            <view v-else class="flex items-center gap-1 bg-[#FFF0F0] px-2 py-1 rounded-md">
                                <u-icon name="info-circle-fill" color="#FF4D4F" size="22"></u-icon>
                                <text class="text-[22rpx] text-[#FF4D4F] font-bold">未设置完善</text>
                            </view>
                            <u-icon name="arrow-right" color="#D1D5DB" size="24"></u-icon>
                        </view>
                    </view>
                </view>

                <view class="flex items-center justify-center py-6 gap-2">
                    <block v-if="loading">
                        <u-loading mode="circle" size="28" color="#0065fb"></u-loading>
                        <text class="text-[24rpx] text-[#9ca3af] ml-2">加载中...</text>
                    </block>
                    <block v-else-if="finished && ipList.length > 0">
                        <view class="h-[1px] w-16 bg-[#e5e7eb]"></view>
                        <text class="text-[24rpx] text-[#9ca3af] mx-3">已加载全部</text>
                        <view class="h-[1px] w-16 bg-[#e5e7eb]"></view>
                    </block>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getPersonList } from "@/api/person";
import { PersonTypeEnum, PersonTypeMap } from "@/enums/appEnums";

// ─── 列表数据 ─────────────────────────────────────────────────────
const ipList = ref<any[]>([]);
const total = ref(0);
const loading = ref(false);
const finished = ref(false);

const queryParams = reactive({
    page_no: 1,
    page_size: 10,
});

// ─── 请求列表（追加模式）─────────────────────────────────────────
const getLists = async () => {
    if (loading.value || finished.value) return;
    try {
        loading.value = true;
        const { lists, count } = await getPersonList(queryParams);
        ipList.value = [...ipList.value, ...lists];
        total.value = count;
        if (ipList.value.length >= count) {
            finished.value = true;
        }
    } catch (error) {
        finished.value = true;
    } finally {
        loading.value = false;
    }
};

// ─── 重置并重新加载（用于 onShow 刷新）───────────────────────────
const reset = () => {
    queryParams.page_no = 1;
    ipList.value = [];
    total.value = 0;
    finished.value = false;
    getLists();
};

// ─── 标签样式 ─────────────────────────────────────────────────────
const tagStyleMap: Record<string, { bg: string; color: string }> = {
    [PersonTypeEnum.PERSONAL_IP]: { bg: "#E6F0FF", color: "#0065FB" },
    [PersonTypeEnum.BUSINESS_SERVICE]: { bg: "#E6F8F3", color: "#00C08E" },
    [PersonTypeEnum.LOCAL_BUSINESS]: { bg: "#FFF5F0", color: "#FF8C00" },
};

const getTagStyle = (type: PersonTypeEnum) => {
    const s = tagStyleMap[type] || { bg: "#F3F4F6", color: "#6B7280" };
    return `background:${s.bg};`;
};

const getTagTextStyle = (type: PersonTypeEnum) => {
    const s = tagStyleMap[type] || { bg: "#F3F4F6", color: "#6B7280" };
    return `color:${s.color};`;
};

// ─── 事件处理 ─────────────────────────────────────────────────────
const handleCreate = () => {
    uni.navigateTo({
        url: `/ai_modules/person/pages/create/create?mode=add`,
    });
};

const handleIpClick = (item: any) => {
    uni.navigateTo({ url: `/ai_modules/person/pages/detail/detail?id=${item.id}&mode=edit` });
};

// ─── 导航栏颜色随滚动变化 ─────────────────────────────────────────
const navColor = ref("#ffffff");
onPageScroll(({ scrollTop }) => {
    navColor.value = scrollTop > 100 ? "#000000" : "#ffffff";
});

// ─── 触底加载更多 ─────────────────────────────────────────────────
onReachBottom(() => {
    if (finished.value || loading.value) return;
    queryParams.page_no += 1;
    getLists();
});

// ─── 页面显示时刷新 ───────────────────────────────────────────────
onShow(() => {
    reset();
});
</script>

<style lang="scss" scoped>
.ripple-btn {
    position: relative;
    animation: pulse-glow 2s infinite;
}

@keyframes pulse-glow {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 101, 251, 0.5);
    }
    70% {
        box-shadow: 0 0 0 16rpx rgba(0, 101, 251, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(0, 101, 251, 0);
    }
}
</style>
