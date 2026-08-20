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
            <text class="text-[#ffffff]/80 mt-2 block font-medium">管理你的24h任务内容配置</text>
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
                        <text class="text-white font-bold">立即创建</text>
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
                        class="bg-white rounded-[32rpx] p-4 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)] flex items-center gap-3 active:bg-[#F9FAFB] transition-colors"
                        @click="handleIpClick(item)">
                        <view
                            class="w-[96rpx] h-[96rpx] rounded-[24rpx] flex-shrink-0"
                            style="background: linear-gradient(135deg, #f8f9fd 0%, #e6f0ff 100%)">
                            <image
                                :src="item.avatar_url"
                                class="w-full h-full rounded-[24rpx]"
                                mode="aspectFill"></image>
                        </view>

                        <view class="flex-1 min-w-0 py-1">
                            <text class="text-[30rpx] font-bold text-[#212121] mb-1.5 line-clamp-1 break-all block">{{
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

                        <view class="flex items-center gap-3 flex-shrink-0">
                            <view v-if="item.is_configured === 1" class="flex flex-col items-end justify-center">
                                <view class="flex items-center gap-1.5">
                                    <view class="w-1.5 h-1.5 rounded-full bg-[#00C08E] shadow-[0_0_8px_#00C08E]"></view>
                                    <text class="text-[22rpx] text-[#9ca3af]">已关联</text>
                                </view>
                                <text class="text-xs font-bold text-gray-700 mt-0.5">{{ item.device_num }} 设备</text>
                            </view>
                            <view v-else class="flex items-center gap-1 bg-[#FFF0F0] px-2 py-1 rounded-md">
                                <text class="text-[22rpx] text-[#FF4D4F] font-bold">未完善</text>
                            </view>

                            <view
                                class="w-[64rpx] h-[64rpx] rounded-full bg-[#F4F7FA] flex items-center justify-center active:bg-[#E5E7EB] transition-colors ml-1"
                                @click.stop="handleMoreClick(item)">
                                <view class="flex gap-[6rpx]">
                                    <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#9CA3AF]"></view>
                                    <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#9CA3AF]"></view>
                                    <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#9CA3AF]"></view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="flex items-center justify-center py-6 gap-2">
                    <block v-if="loading">
                        <u-loading mode="circle" size="28" color="#0065fb"></u-loading>
                        <text class="text-xs text-[#9ca3af] ml-2">加载中...</text>
                    </block>
                    <block v-else-if="finished && ipList.length > 0">
                        <view class="h-[1px] w-16 bg-[#e5e7eb]"></view>
                        <text class="text-xs text-[#9ca3af] mx-3">已加载全部</text>
                        <view class="h-[1px] w-16 bg-[#e5e7eb]"></view>
                    </block>
                </view>
            </view>
        </view>

        <view
            v-if="showActionSheet"
            class="fixed inset-0 z-50 flex flex-col justify-end"
            @click.self="closeActionSheet">
            <view class="absolute inset-0 bg-[#000000]/40 transition-opacity" @click="closeActionSheet"></view>

            <view
                class="relative z-10 bg-white rounded-t-[40rpx] pb-[env(safe-area-inset-bottom)] overflow-hidden action-sheet-enter">
                <view class="flex items-center gap-3 px-6 py-5 border-b border-solid border-[#F3F4F6]">
                    <view
                        class="w-[80rpx] h-[80rpx] rounded-[20rpx] flex-shrink-0"
                        style="background: linear-gradient(135deg, #f8f9fd 0%, #e6f0ff 100%)">
                        <image
                            :src="currentItem?.avatar_url"
                            class="w-full h-full rounded-[20rpx]"
                            mode="aspectFill"></image>
                    </view>
                    <view class="flex-1">
                        <text class="text-[28rpx] font-bold text-[#212121] block line-clamp-1">{{
                            currentItem?.persona_name
                        }}</text>
                        <view
                            class="mt-1 px-2.5 py-0.5 rounded-full inline-flex items-center"
                            :style="getTagStyle(currentItem?.persona_type)">
                            <text class="text-[22rpx] font-bold" :style="getTagTextStyle(currentItem?.persona_type)">
                                {{ PersonTypeMap[currentItem?.persona_type as PersonTypeEnum] }}
                            </text>
                        </view>
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center active:bg-[#E5E7EB]"
                        @click="closeActionSheet">
                        <u-icon name="close" color="#9CA3AF" size="28"></u-icon>
                    </view>
                </view>

                <view class="px-4 py-3">
                    <view
                        class="flex items-center gap-4 px-4 py-4 rounded-[24rpx] active:bg-[#F3F4F6] mb-2 transition-colors"
                        @click="handleEdit">
                        <view
                            class="w-[72rpx] h-[72rpx] rounded-[20rpx] bg-[#E6F0FF] flex items-center justify-center flex-shrink-0">
                            <u-icon name="edit-pen" color="#0065FB" size="36"></u-icon>
                        </view>
                        <view class="flex-1">
                            <text class="text-[28rpx] font-bold text-[#212121] block">编辑IP</text>
                            <text class="text-[22rpx] text-[#9CA3AF]">修改IP信息与配置</text>
                        </view>
                        <u-icon name="arrow-right" color="#D1D5DB" size="24"></u-icon>
                    </view>

                    <view class="h-[1px] bg-[#F3F4F6] mx-4 my-1"></view>

                    <view
                        class="flex items-center gap-4 px-4 py-4 rounded-[24rpx] active:bg-[#FFF0F0] transition-colors"
                        @click="handleDeleteConfirm">
                        <view
                            class="w-[72rpx] h-[72rpx] rounded-[20rpx] bg-[#FFF0F0] flex items-center justify-center flex-shrink-0">
                            <u-icon name="trash" color="#FF4D4F" size="36"></u-icon>
                        </view>
                        <view class="flex-1">
                            <text class="text-[28rpx] font-bold text-[#FF4D4F] block">删除IP</text>
                            <text class="text-[22rpx] text-[#9CA3AF]">删除后数据不可恢复</text>
                        </view>
                        <u-icon name="arrow-right" color="#D1D5DB" size="24"></u-icon>
                    </view>
                </view>

                <view class="px-4 pb-4">
                    <view
                        class="w-full py-4 bg-[#F3F4F6] rounded-[24rpx] flex items-center justify-center active:bg-[#E5E7EB] transition-colors"
                        @click="closeActionSheet">
                        <text class="text-[28rpx] font-bold text-[#6B7280]">取消</text>
                    </view>
                </view>
            </view>
        </view>

        <view v-if="showDeleteModal" class="fixed inset-0 z-[60] flex items-center justify-center px-8">
            <view class="absolute inset-0 bg-[#000000]/50 transition-opacity" @click="showDeleteModal = false"></view>

            <view class="relative z-10 bg-white rounded-[40rpx] w-full overflow-hidden delete-modal-enter">
                <view class="flex flex-col items-center pt-8 pb-4 px-6">
                    <view class="w-[120rpx] h-[120rpx] rounded-full bg-[#FFF0F0] flex items-center justify-center mb-4">
                        <u-icon name="trash" color="#FF4D4F" size="56"></u-icon>
                    </view>
                    <text class="text-[32rpx] font-extrabold text-[#212121] block mb-2">确认删除IP？</text>
                    <text class="text-xs text-[#9CA3AF] text-center leading-relaxed block">
                        即将删除
                        <text class="text-[#212121] font-bold">「{{ currentItem?.persona_name }}」</text>，
                        删除后该IP下所有配置数据将永久清除，无法恢复。
                    </text>
                </view>

                <view class="h-[1px] bg-[#F3F4F6] mx-6 my-3"></view>

                <view class="flex gap-3 px-6 pb-8 pt-2">
                    <view
                        class="flex-1 py-3.5 bg-[#F3F4F6] rounded-[24rpx] flex items-center justify-center active:bg-[#E5E7EB] transition-colors"
                        @click="showDeleteModal = false">
                        <text class="text-[28rpx] font-bold text-[#6B7280]">取消</text>
                    </view>
                    <view
                        class="flex-1 py-3.5 bg-[#FF4D4F] rounded-[24rpx] flex items-center justify-center active:bg-[#E03E40] transition-colors shadow-[0_4rpx_16rpx_rgba(255,77,79,0.4)]"
                        :class="{ 'opacity-70': deleteLoading }"
                        @click="handleDelete">
                        <u-loading v-if="deleteLoading" mode="circle" size="24" color="#ffffff"></u-loading>
                        <text v-else class="text-[28rpx] font-bold text-white">确认删除</text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getPersonList, deletePerson } from "@/api/person";
import { getTeamInfo } from "@/api/team";
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

const getLists = async () => {
    if (loading.value || finished.value) return;
    try {
        loading.value = true;
        const { lists, count } = await getPersonList(queryParams);
        ipList.value = [...ipList.value, ...lists];
        total.value = count;
        if (ipList.value.length >= count) finished.value = true;
    } catch {
        finished.value = true;
    } finally {
        loading.value = false;
    }
};

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

// ─── 删除 & 菜单状态 ──────────────────────────────────────────────
const showActionSheet = ref(false);
const showDeleteModal = ref(false);
const deleteLoading = ref(false);
const currentItem = ref<any>(null);

const closeActionSheet = () => {
    showActionSheet.value = false;
};

// 点击 ··· 按钮
const handleMoreClick = (item: any) => {
    currentItem.value = item;
    showActionSheet.value = true;
};

// 菜单 → 编辑
const handleEdit = () => {
    closeActionSheet();
    uni.navigateTo({
        url: `/ai_modules/person/pages/detail/detail?id=${currentItem.value.id}&mode=edit`,
    });
};

// 菜单 → 删除（弹确认框）
const handleDeleteConfirm = () => {
    showActionSheet.value = false;
    setTimeout(() => {
        showDeleteModal.value = true;
    }, 300);
};

// 确认删除
const handleDelete = async () => {
    if (deleteLoading.value) return;
    try {
        deleteLoading.value = true;
        await deletePerson({ id: currentItem.value.id });

        const idx = ipList.value.findIndex((i) => i.id === currentItem.value.id);
        if (idx !== -1) {
            ipList.value.splice(idx, 1);
            total.value = Math.max(0, total.value - 1);
        }

        showDeleteModal.value = false;
        uni.showToast({ title: "删除成功", icon: "success", duration: 1500 });
    } catch (error: any) {
        uni.showToast({ title: error?.message || "删除失败，请重试", icon: "none", duration: 2000 });
    } finally {
        deleteLoading.value = false;
    }
};

// ─── 其他事件 ─────────────────────────────────────────────────────
const handleCreate = async () => {
    try {
        const info: any = await getTeamInfo();
        if (Number(info?.in_team) === 1 && Number(info?.expired) === 1) {
            uni.$u.toast("当前空间成员资格已过期，无法创建IP");
            return;
        }
    } catch {
        // 团队信息失败不阻断个人空间创建;后端仍会硬拦截
    }
    uni.navigateTo({ url: `/ai_modules/person/pages/create/create?mode=add` });
};

const handleIpClick = (item: any) => {
    uni.navigateTo({ url: `/ai_modules/person/pages/detail/detail?id=${item.id}&mode=edit` });
};

const navColor = ref("#ffffff");
onPageScroll(({ scrollTop }) => {
    navColor.value = scrollTop > 100 ? "#000000" : "#ffffff";
});

onReachBottom(() => {
    if (finished.value || loading.value) return;
    queryParams.page_no += 1;
    getLists();
});

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

.action-sheet-enter {
    animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.delete-modal-enter {
    animation: scaleIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes scaleIn {
    from {
        transform: scale(0.85);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
