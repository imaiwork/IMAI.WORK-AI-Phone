<template>
    <view class="h-screen relative flex flex-col bg-[#f4f8ff]">
        <view class="w-full h-[440rpx] absolute top-0 left-0 bg-primary">
            <view
                class="absolute bottom-0 left-0 w-full h-[200rpx]"
                style="background: linear-gradient(to bottom, transparent, #f4f8ff)"></view>
        </view>

        <u-navbar
            :is-fixed="false"
            back-icon-color="#ffffff"
            :border-bottom="false"
            :background="{ background: 'transparent' }">
        </u-navbar>

        <view class="relative grow min-h-0 flex flex-col">
            <view class="px-[40rpx] pt-[10rpx] pb-[24rpx] flex items-center justify-between">
                <view>
                    <view class="text-[44rpx] font-bold text-white">代理管理</view>
                    <view class="text-[24rpx] mt-[8rpx] text-[#ffffff]/60">管理你的下级用户和激活码</view>
                </view>
                <view
                    class="h-[72rpx] px-[32rpx] rounded-[100rpx] flex items-center justify-center gap-[8rpx] bg-[#ffffff]/20 border border-solid border-[#ffffff]/30"
                    @click="handleSetting">
                    <u-icon name="setting" size="28" color="#ffffff"></u-icon>
                    <text class="font-semibold text-white">配置</text>
                </view>
            </view>

            <view class="px-[26rpx]">
                <view
                    class="rounded-[28rpx] px-[40rpx] py-[32rpx] bg-white"
                    style="box-shadow: 0 8px 32px rgba(0, 101, 251, 0.12)">
                    <view class="flex items-center justify-between">
                        <view class="px-[24rpx] py-[10rpx] rounded-[100rpx] border border-solid border-[#e0ecff]">
                            <text class="text-[24rpx] font-bold text-primary">
                                {{ agentUserInfo.level_name }}
                            </text>
                        </view>
                        <view
                            class="shrink-0 rounded-[100rpx] text-[26rpx] font-semibold px-[30rpx] py-[12rpx] text-primary border border-solid border-[#e0ecff] bg-[#f4f8ff]"
                            @click="handleInvite">
                            邀请好友
                        </view>
                    </view>
                    <view class="w-full h-[1rpx] bg-[#f0f5ff] my-[28rpx]"></view>
                    <view>
                        <view class="text-[24rpx] text-[#adc0d8] mb-[10rpx]">我的算力点数</view>
                        <view class="flex items-center gap-[10rpx]">
                            <image src="@/packages/static/icons/shandian.svg" class="w-[32rpx] h-[32rpx]"></image>
                            <text class="font-bold text-primary text-[56rpx] leading-none">{{ userTokens }}</text>
                            <text class="text-[26rpx] text-[#adc0d8] self-end mb-[4rpx]">点</text>
                        </view>
                    </view>
                </view>
            </view>

            <view class="mt-[28rpx] px-[26rpx]">
                <view class="bg-white rounded-[20rpx] p-[6rpx]" style="box-shadow: 0 2px 12px rgba(0, 101, 251, 0.06)">
                    <view class="w-full grid grid-cols-2 relative h-[76rpx]">
                        <view
                            v-for="(item, index) in tabsList"
                            :key="index"
                            class="rounded-[14rpx] font-medium flex items-center justify-center z-10 text-[28rpx] transition-colors duration-300"
                            :class="current === index ? 'text-white' : 'text-[#adc0d8]'"
                            @click="handleTabChange(index)">
                            {{ item.name }}
                        </view>
                        <view class="tab-slider" :style="{ transform: `translateX(${current * 100}%)` }"></view>
                    </view>
                </view>

                <view v-if="current == 1" class="mt-[16rpx]">
                    <view
                        class="rounded-[20rpx] px-[28rpx] py-[18rpx] flex items-center justify-between bg-white"
                        style="box-shadow: 0 2px 12px rgba(0, 101, 251, 0.06)">
                        <text class="text-[26rpx] font-medium text-[#adc0d8]"
                            >共 <text class="text-primary font-bold">{{ total }}</text> 张卡密</text
                        >
                        <view
                            class="flex items-center gap-[8rpx] rounded-[100rpx] px-[28rpx] py-[12rpx] bg-primary"
                            @click="showAddCardPopup = true">
                            <text class="text-[24rpx] font-bold text-white">+ 批量生成</text>
                        </view>
                    </view>
                </view>

                <view
                    class="mt-[16rpx] rounded-[20rpx] py-[8rpx] px-[16rpx] flex items-center gap-[12rpx] bg-white"
                    style="box-shadow: 0 2px 12px rgba(0, 101, 251, 0.06)">
                    <view class="flex-1">
                        <u-search
                            v-model="searchValue"
                            bg-color="transparent"
                            search-icon-color="#adc0d8"
                            placeholder-color="#adc0d8"
                            clearable
                            :placeholder="current == 0 ? '搜索昵称或手机号' : '搜索卡密'"
                            :show-action="false"
                            @clear="clearSearch"
                            @search="confirmSearch">
                        </u-search>
                    </view>
                    <view
                        class="flex items-center justify-center rounded-[14rpx] shrink-0 w-[120rpx] h-[68rpx] bg-primary"
                        @click="confirmSearch">
                        <text class="text-[26rpx] font-bold text-white">搜索</text>
                    </view>
                </view>
            </view>

            <view class="grow min-h-0 mt-[20rpx]">
                <z-paging ref="pagingRef" v-model="dataList" :fixed="false" @query="queryList">
                    <view class="px-[26rpx] flex flex-col gap-[16rpx] pb-[40rpx]">
                        <template v-if="current == 0">
                            <view
                                v-for="(item, index) in dataList"
                                :key="index"
                                class="rounded-[24rpx] bg-white overflow-hidden"
                                style="box-shadow: 0 2px 16px rgba(0, 101, 251, 0.07)">
                                <view class="p-[28rpx]">
                                    <view class="flex items-center justify-between gap-x-[16rpx]">
                                        <view class="flex items-center flex-1 min-w-0">
                                            <image
                                                :src="item.avatar"
                                                class="w-[88rpx] h-[88rpx] rounded-full shrink-0"></image>
                                            <view class="ml-[20rpx] flex-1 min-w-0">
                                                <view class="text-[30rpx] font-bold line-clamp-1 text-[#0a1f44]">
                                                    {{ item.nickname }}
                                                </view>
                                                <view class="text-[24rpx] mt-[6rpx] text-[#adc0d8]">
                                                    {{ item.mobile }}
                                                </view>
                                            </view>
                                        </view>
                                        <view
                                            class="shrink-0 rounded-[100rpx] flex items-center justify-center px-[28rpx] h-[58rpx] bg-[#0065fb]"
                                            @click="handleSubSetting(item)">
                                            <text class="text-[24rpx] font-bold text-white">设置</text>
                                        </view>
                                    </view>
                                    <view class="mt-[22rpx] rounded-[16rpx] flex items-center py-[20rpx] bg-[#f4f8ff]">
                                        <view class="flex-1 text-center">
                                            <view class="text-[22rpx] mb-[6rpx] text-[#adc0d8]">代理等级</view>
                                            <view class="font-bold text-[28rpx] text-[#0a1f44]">
                                                {{
                                                    agentLevel.find((row: any) => row.level == item.level)?.name ||
                                                    "普通用户"
                                                }}
                                            </view>
                                        </view>
                                        <view class="w-[1rpx] h-[40rpx] bg-[#e0ecff]"></view>
                                        <view class="flex-1 text-center">
                                            <view class="text-[22rpx] mb-[6rpx] text-[#adc0d8]">算力点数</view>
                                            <view class="font-bold text-[28rpx] text-[#0065fb]">{{ item.tokens }}</view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </template>

                        <template v-if="current == 1">
                            <view
                                v-for="(item, index) in dataList"
                                :key="index"
                                class="rounded-[24rpx] bg-white overflow-hidden"
                                style="box-shadow: 0 2px 16px rgba(0, 101, 251, 0.07)">
                                <view class="p-[28rpx]">
                                    <view class="flex items-center justify-between gap-x-[16rpx]">
                                        <view class="flex-1 min-w-0">
                                            <view class="flex items-center gap-[10rpx]">
                                                <view class="rounded-[8rpx] px-[12rpx] py-[5rpx] shrink-0 bg-[#f4f8ff]">
                                                    <text class="text-[20rpx] font-bold text-[#0065fb]">卡密</text>
                                                </view>
                                                <text class="text-[28rpx] font-bold line-clamp-1 text-[#0a1f44]">
                                                    {{ item.card_code }}
                                                </text>
                                                <view
                                                    class="text-[20rpx] text-[#0065fb] border border-solid border-[#e0ecff] px-[12rpx] py-[5rpx] rounded-[8rpx]"
                                                    @click="copy(item.card_code)"
                                                    >复制</view
                                                >
                                            </view>
                                            <view class="text-[22rpx] mt-[10rpx] text-[#adc0d8]">
                                                {{ item.package_name }} · {{ item.tokens }}点
                                            </view>
                                        </view>
                                        <view
                                            class="shrink-0 rounded-[100rpx] flex items-center justify-center px-[24rpx] h-[56rpx] border border-solid border-[#ffe0e0]"
                                            @click="handleDeleteCard(item.id)">
                                            <text class="text-[24rpx] font-medium text-[#ff6b6b]">删除</text>
                                        </view>
                                    </view>
                                    <view
                                        class="flex items-center justify-between mt-[20rpx] pt-[18rpx] border-[0] border-t border-solid border-[#f4f8ff]">
                                        <text class="text-[22rpx] text-[#adc0d8]"
                                            >使用: {{ item.used_num }} / {{ item.card_num }}</text
                                        >
                                        <text class="text-[22rpx] text-[#c8daf0]">{{ item.create_time }}</text>
                                    </view>
                                </view>
                            </view>
                        </template>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </view>
    </view>

    <popup-bottom v-if="showSettingPopup" v-model="showSettingPopup" title="代理联系方式" height="60%">
        <template #content>
            <view class="h-full px-[42rpx] py-[32rpx] flex flex-col">
                <view class="grow min-h-0">
                    <view class="rounded-[16rpx] px-[24rpx] py-[20rpx] flex items-start gap-[12rpx] bg-[#f4f8ff]">
                        <u-icon name="info-circle" size="28" color="#0065fb" class="shrink-0 mt-[2rpx]"></u-icon>
                        <text class="text-[24rpx] leading-relaxed text-[#adc0d8]">
                            设置你的联系方式后，通过您的链接注册的用户可以看到这些联系信息
                        </text>
                    </view>
                    <view class="mt-[48rpx]">
                        <view class="text-[30rpx] font-bold text-[#0a1f44] mb-[20rpx]">微信二维码</view>
                        <view
                            v-if="!agentUserInfo.qr_code"
                            class="w-[240rpx] h-[240rpx] rounded-[20rpx] flex flex-col items-center justify-center gap-[16rpx] bg-[#f4f8ff] border-2 border-dashed border-[#b3d1ff]"
                            @click="uploadAndProcessFiles('image')">
                            <u-icon name="plus-circle-fill" size="48" color="#0065fb"></u-icon>
                            <text class="text-[24rpx] font-medium text-primary">点击上传图片</text>
                        </view>
                        <view
                            v-else
                            class="w-[240rpx] h-[240rpx] rounded-[20rpx] relative overflow-hidden"
                            style="box-shadow: 0 4px 20px rgba(0, 101, 251, 0.12)">
                            <image :src="agentUserInfo.qr_code" class="w-full h-full" mode="aspectFill"></image>
                            <view
                                class="absolute top-[12rpx] right-[12rpx] w-[52rpx] h-[52rpx] rounded-full flex items-center justify-center bg-black/40"
                                @click="agentUserInfo.qr_code = ''">
                                <u-icon name="close" size="22" color="#FFFFFF"></u-icon>
                            </view>
                        </view>
                    </view>
                </view>
                <view class="flex items-center gap-[20rpx] mt-[24rpx]">
                    <view
                        class="w-[220rpx] h-[92rpx] rounded-[20rpx] flex items-center justify-center font-bold text-[28rpx] bg-[#f4f8ff] text-[#adc0d8]"
                        @click="showSettingPopup = false">
                        取消
                    </view>
                    <view
                        class="flex-1 h-[92rpx] rounded-[20rpx] flex items-center justify-center font-bold text-[28rpx] bg-[#0065fb] text-white"
                        @click="handleConfirmSetting">
                        确定保存
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <popup-bottom v-if="showAddCardPopup" v-model="showAddCardPopup" title="批量生成卡密">
        <template #content>
            <view class="h-full px-[42rpx] py-4 flex flex-col">
                <view class="grow min-h-0 space-y-[44rpx]">
                    <view>
                        <view class="text-[28rpx] font-bold text-[#0a1f44] mb-[12rpx]">选择套餐</view>
                        <picker :range="packageList" range-key="name" @change="handlePackageChange">
                            <view
                                class="h-[90rpx] flex items-center justify-between bg-[#f4f8ff] rounded-[16rpx] px-[24rpx] border border-solid border-[#e0ecff]">
                                <text
                                    class="font-medium"
                                    :class="[cardFormData.package ? 'text-[#0a1f44]' : 'text-[#adc0d8]']">
                                    {{ getPackageName }}
                                </text>
                                <u-icon name="arrow-down" size="24" color="#adc0d8"></u-icon>
                            </view>
                        </picker>
                    </view>
                    <view>
                        <view class="text-[28rpx] font-bold text-[#0a1f44] mb-[12rpx]">生成数量</view>
                        <view
                            class="h-[90rpx] flex items-center bg-[#f4f8ff] rounded-[16rpx] px-[24rpx] border border-solid border-[#e0ecff]">
                            <u-input v-model="cardFormData.quantity" type="digit" placeholder="请输入生成数量" />
                        </view>
                    </view>
                    <view>
                        <view class="text-[28rpx] font-bold text-[#0a1f44] mb-[12rpx]">每张卡密可使用的次数</view>
                        <view
                            class="h-[90rpx] flex items-center bg-[#f4f8ff] rounded-[16rpx] px-[24rpx] border border-solid border-[#e0ecff]">
                            <u-input v-model="cardFormData.use_times" type="digit" placeholder="请输入使用次数" />
                        </view>
                    </view>
                </view>
                <view class="flex items-center gap-[20rpx] mt-[24rpx]">
                    <view
                        class="w-[220rpx] h-[92rpx] rounded-[20rpx] flex items-center justify-center font-bold text-[28rpx] bg-[#f4f8ff] text-[#adc0d8]"
                        @click="showAddCardPopup = false">
                        取消
                    </view>
                    <view
                        class="flex-1 h-[92rpx] rounded-[20rpx] flex items-center justify-center font-bold text-[28rpx] bg-primary text-white"
                        @click="handleConfirmAddCard">
                        确定生成
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <popup-bottom v-if="showGiftTokensPopup" v-model="showGiftTokensPopup" title="赠送点数">
        <template #content>
            <view class="h-full px-[42rpx] py-4 flex flex-col">
                <view class="grow min-h-0 space-y-[44rpx]">
                    <view>
                        <view class="text-[28rpx] font-bold text-[#0a1f44] mb-[12rpx]">赠送用户</view>
                        <view class="flex items-center gap-[16rpx] p-[20rpx] bg-[#f4f8ff] rounded-[16rpx]">
                            <image
                                :src="giftTokensFormData.avatar"
                                class="w-[80rpx] h-[80rpx] rounded-full shrink-0"></image>
                            <view class="flex-1 min-w-0">
                                <view class="text-[28rpx] font-bold text-[#0a1f44]">{{
                                    giftTokensFormData.nickname
                                }}</view>
                                <view class="text-[24rpx] mt-[6rpx] text-[#adc0d8]">{{
                                    giftTokensFormData.mobile
                                }}</view>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="text-[28rpx] font-bold text-[#0a1f44] mb-[12rpx]">赠送数量</view>
                        <view
                            class="h-[90rpx] flex items-center bg-[#f4f8ff] rounded-[16rpx] px-[24rpx] border border-solid border-[#e0ecff]">
                            <view class="flex-1">
                                <u-input
                                    v-model="giftTokensFormData.quantity"
                                    type="digit"
                                    :max="userTokens"
                                    placeholder="请输入赠送数量" />
                            </view>
                            <text class="text-[24rpx] text-[#adc0d8] shrink-0">剩余 {{ userTokens }} 点</text>
                        </view>
                    </view>
                </view>
                <view class="flex items-center gap-[20rpx] mt-[24rpx]">
                    <view
                        class="w-[220rpx] h-[92rpx] rounded-[20rpx] flex items-center justify-center font-bold text-[28rpx] bg-[#f4f8ff] text-[#adc0d8]"
                        @click="
                            showGiftTokensPopup = false;
                            giftTokensFormData.quantity = 1;
                        ">
                        取消
                    </view>
                    <view
                        class="flex-1 h-[92rpx] rounded-[20rpx] flex items-center justify-center font-bold text-[28rpx] bg-primary text-white"
                        @click="handleConfirmGiftTokens">
                        确定赠送
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <upload-progress v-if="showUploadProgress" v-model="showUploadProgress" :upload-list="uploadMaterialList" />
</template>

<script setup lang="ts">
import {
    getAgentSubList,
    getAgentCardList,
    generateAgentCard,
    getAgentCardPackageList,
    deleteAgentCard,
    deleteAgentSub,
    getAgentUserInfo,
    setAgentUserContactQrcode,
    agentGiftTokens,
    getAgentLevel,
    setAgentLevel,
} from "@/api/user";
import { useUserStore } from "@/stores/user";
import useUpload from "@/hooks/useUpload";
import { useCopy } from "@/hooks/useCopy";

const userStore = useUserStore();
const { userTokens } = storeToRefs(userStore);

const tabsList = [
    { name: "用户管理", value: 1 },
    { name: "卡密管理", value: 2 },
];
const current = ref(0);
const searchValue = ref("");
const showSettingPopup = ref(false);
const showAddCardPopup = ref(false);
const cardFormData = reactive<{
    package: number | null;
    quantity: number;
    use_times: number;
}>({
    package: null,
    quantity: 1,
    use_times: 1,
});
const packageList = ref<any[]>([]);

const getPackageName = computed(() => {
    const currentPackage = packageList.value.find((item) => item.id === cardFormData.package);
    if (currentPackage) {
        return `${currentPackage.name}`;
    }
    return "选择生成套餐";
});

const handleTabChange = (index: number) => {
    current.value = index;
    searchValue.value = "";
    pagingRef.value.reload();
};

const clearSearch = () => {
    searchValue.value = "";
    pagingRef.value.reload();
};
const confirmSearch = () => {
    pagingRef.value.reload();
};

const pagingRef = ref();
const dataList = ref<any[]>([]);
const total = ref(0);
const queryList = async (page_no: number, page_size: number) => {
    try {
        const api = current.value == 0 ? getAgentSubList : getAgentCardList;
        const { lists, count } = await api({
            page_no: page_no,
            page_size: page_size,
            [current.value == 0 ? "user_keyword" : "sn"]: searchValue.value,
        });
        total.value = count;
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const { copy } = useCopy();

const handleDeleteCard = (id: number) => {
    uni.showModal({
        title: "删除卡密",
        content: "确定要删除这张卡密吗？",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    await deleteAgentCard({ id });
                    pagingRef.value.reload();
                    userStore.getUser();
                    uni.hideLoading();
                    uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({ title: error || "删除失败", icon: "none", duration: 3000 });
                }
            }
        },
    });
};

const showGiftTokensPopup = ref(false);
const giftTokensFormData = reactive<{
    quantity: number;
    avatar: string;
    mobile: string;
    nickname: string;
    user_id: number;
}>({
    quantity: 1,
    avatar: "",
    mobile: "",
    nickname: "",
    user_id: 0,
});

const handleSubSetting = (item: any) => {
    const canAdjustLevel =
        item.level == 0 && agentUserInfo.value.level != 3
            ? agentUserInfo.value.level > item.level
            : 1000 - (agentUserInfo.value.level || 0) > 1000 - item.level;

    const itemList: string[] = [];
    if (canAdjustLevel) {
        itemList.push("调整等级");
    }
    itemList.push("赠送点数");
    if (item.level != 0) {
        itemList.push("移除代理");
    }

    uni.showActionSheet({
        itemList: itemList,
        success: async (res) => {
            const action = itemList[res.tapIndex];

            if (action === "调整等级") {
                const levelList = agentLevel.value.filter(
                    (row: any) => 1000 - (agentUserInfo.value.level || 0) > 1000 - row.level && row.level != item.level
                );
                if (item.level !== 0) {
                    levelList.unshift({ name: "普通用户", level: 0 });
                }
                uni.showActionSheet({
                    itemList: levelList.map((item) => `${item.name}`),
                    success: async (res) => {
                        const currentLevel = levelList[res.tapIndex];
                        uni.showLoading({ title: "调整中...", mask: true });
                        try {
                            await setAgentLevel({ user_id: item.user_id, level: currentLevel.level });
                            uni.hideLoading();
                            uni.showToast({ title: "调整成功", icon: "none", duration: 3000 });
                            pagingRef.value.reload();
                        } catch (error: any) {
                            uni.showToast({ title: error || "调整失败", icon: "none", duration: 3000 });
                            uni.hideLoading();
                        }
                    },
                });
            } else if (action === "赠送点数") {
                giftTokensFormData.avatar = item.avatar;
                giftTokensFormData.mobile = item.mobile;
                giftTokensFormData.nickname = item.nickname;
                giftTokensFormData.user_id = item.user_id;
                showGiftTokensPopup.value = true;
            } else if (action === "移除代理") {
                uni.showModal({
                    title: "移除代理",
                    content: "确定要移除这个代理吗？",
                    success: async (res) => {
                        if (res.confirm) {
                            uni.showLoading({ title: "移除中...", mask: true });
                            try {
                                await deleteAgentSub({ user_id: item.user_id });
                                uni.hideLoading();
                                uni.showToast({ title: "移除成功", icon: "none", duration: 3000 });
                                pagingRef.value.reload();
                            } catch (error: any) {
                                uni.hideLoading();
                                uni.showToast({ title: error || "移除失败", icon: "none", duration: 3000 });
                            }
                        }
                    },
                });
            }
        },
    });
};

const handleConfirmGiftTokens = async () => {
    if (giftTokensFormData.quantity > userTokens.value || giftTokensFormData.quantity < 1) {
        uni.$u.toast(`赠送数量必须在1-${userTokens.value}之间`);
        return;
    }
    uni.showLoading({ title: "赠送中...", mask: true });
    try {
        await agentGiftTokens({ user_id: giftTokensFormData.user_id, tokens: giftTokensFormData.quantity });
        uni.hideLoading();
        uni.showToast({ title: "赠送成功", icon: "none", duration: 3000 });
        showGiftTokensPopup.value = false;
        giftTokensFormData.quantity = 1;
        pagingRef.value.reload();
        userStore.getUser();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "赠送失败", icon: "none", duration: 3000 });
    }
};

const handleConfirmAddCard = async () => {
    if (cardFormData.quantity > 100 || cardFormData.quantity < 1) {
        uni.$u.toast("生成数量必须在1-100之间");
        return;
    }
    if (cardFormData.use_times > 100 || cardFormData.use_times < 1) {
        uni.$u.toast("使用次数必须在1-100之间");
        return;
    }
    uni.showLoading({ title: "生成中...", mask: true });
    try {
        await generateAgentCard({
            package_id: cardFormData.package,
            count: cardFormData.quantity,
            card_num: cardFormData.use_times,
        });
        uni.hideLoading();
        showAddCardPopup.value = false;
        pagingRef.value.reload();
        userStore.getUser();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "生成失败", icon: "none", duration: 3000 });
    }
};

const handleConfirmSetting = async () => {
    if (!agentUserInfo.value.qr_code) {
        uni.$u.toast("请上传微信二维码");
        return;
    }
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        await setAgentUserContactQrcode({ qr_code: agentUserInfo.value.qr_code });
        uni.hideLoading();
        showSettingPopup.value = false;
        uni.showToast({ title: "保存成功", icon: "none", duration: 2000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "保存失败", icon: "none", duration: 3000 });
    }
};

const handlePackageChange = (e: any) => {
    const { value } = e.detail;
    cardFormData.package = packageList.value[value].id;
};

const handleInvite = () => {
    uni.$u.route({
        url: "/packages/pages/agent_invite_poster/agent_invite_poster",
    });
};

const handleSetting = () => {
    showSettingPopup.value = true;
    getAgentInfo();
};

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    count: 1,
    imageAccept: ["jpg", "png", "jpeg"],
    imageSize: 20,
    sourceType: ["album", "camera"],
    onSuccess: (res: any) => {
        if (res.length > 0) {
            agentUserInfo.value.qr_code = res[0].url;
        }
    },
});

const getPackageList = async () => {
    const { lists } = await getAgentCardPackageList({ page_size: 1000 });
    packageList.value = lists.map((item: any) => ({
        ...item,
        name: `${item.name} · ${item.tokens}点`,
    }));
};

const agentUserInfo = ref<any>({});
const getAgentInfo = async () => {
    const res = await getAgentUserInfo();
    agentUserInfo.value = res;
};

const agentLevel = ref<any[]>([]);
const fetchAgentLevel = async () => {
    const res = await getAgentLevel();
    agentLevel.value = res;
};

getAgentInfo();
getPackageList();
fetchAgentLevel();
</script>

<style lang="scss" scoped>
picker {
    width: 100%;
    height: 100%;
}
.tab-slider {
    @apply h-[calc(100%-8rpx)] w-[50%] bg-primary rounded-[14rpx] absolute top-[4rpx] left-0 transition-all duration-300;
}
</style>
