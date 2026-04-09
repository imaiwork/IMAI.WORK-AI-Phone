<template>
    <view class="min-h-screen bg-[#F7F9FC] pb-[220rpx]">
        <u-navbar :border-bottom="false" :background="{ background: '#F7F9FC' }" title="绑定人设" title-bold />

        <view class="px-[30rpx] pt-2 flex flex-col gap-3">
            <template v-if="loading">
                <view class="bg-white rounded-[24rpx] p-5 flex flex-col gap-2 animate-pulse">
                    <view class="h-[24rpx] w-[120rpx] bg-[#F0F0F0] rounded-full" />
                    <view class="h-[36rpx] w-[200rpx] bg-[#F0F0F0] rounded-full" />
                </view>

                <view class="flex flex-col gap-3">
                    <view class="flex items-center justify-between mb-1">
                        <view class="h-[36rpx] w-[180rpx] bg-[#F0F0F0] rounded-full animate-pulse" />
                        <view class="h-[48rpx] w-[100rpx] bg-[#F0F0F0] rounded-full animate-pulse" />
                    </view>
                    <view class="flex gap-3 pb-2">
                        <view
                            v-for="i in 3"
                            :key="i"
                            class="shrink-0 w-[220rpx] h-[260rpx] rounded-[32rpx] bg-[#F0F0F0] animate-pulse" />
                    </view>
                </view>

                <view class="bg-white rounded-[32rpx] p-5 flex flex-col gap-6 animate-pulse">
                    <view class="h-[36rpx] w-[200rpx] bg-[#F0F0F0] rounded-full" />
                    <view v-for="j in 3" :key="j" class="flex flex-col gap-3">
                        <view class="flex items-center justify-between">
                            <view class="h-[30rpx] w-[150rpx] bg-[#F0F0F0] rounded-full" />
                            <view class="h-[28rpx] w-[80rpx] bg-[#F0F0F0] rounded-full" />
                        </view>
                        <view class="flex gap-2.5">
                            <view v-for="k in 3" :key="k" class="w-[160rpx] h-[214rpx] rounded-[20rpx] bg-[#F0F0F0]" />
                        </view>
                    </view>
                </view>
            </template>

            <template v-else>
                <view
                    class="bg-white rounded-[24rpx] p-5 shadow-[0_4rpx_16rpx_rgba(0,0,0,0.02)] border border-solid border-white flex flex-col gap-1.5">
                    <text class="text-[24rpx] text-[#999999] font-medium">关联设备</text>
                    <text class="text-[32rpx] font-extrabold text-[#1A1A1A]">{{ deviceName }}</text>
                </view>

                <view class="flex flex-col gap-3">
                    <view class="flex items-center justify-between mb-1">
                        <text class="text-[32rpx] font-extrabold text-[#1A1A1A]">选择IP人设</text>
                        <view
                            class="px-3 py-1.5 rounded-full bg-[#E6F0FF] text-primary flex items-center gap-1"
                            @click="handleMorePerson">
                            <u-icon name="plus" size="20" />
                            <text class="text-[24rpx] font-bold">更多</text>
                        </view>
                    </view>

                    <scroll-view v-if="personList.length > 0" scroll-x class="w-full">
                        <view class="flex gap-3 pb-2 whitespace-nowrap">
                            <view
                                v-for="item in personList"
                                :key="item.id"
                                class="shrink-0 relative inline-flex flex-col items-center justify-center w-[220rpx] h-[260rpx] rounded-[32rpx] border-[3rpx] transition-all duration-300 overflow-hidden"
                                :class="[
                                    currentPersonId === item.id
                                        ? 'bg-[#F0F5FF] border-primary shadow-[0_4rpx_16rpx_rgba(0,101,251,0.12)]'
                                        : 'bg-white border-[transparent] shadow-[0_4rpx_16rpx_rgba(0,0,0,0.02)]',
                                    !item.is_configured ? 'opacity-75' : '',
                                ]"
                                @click="selectIp(item.id)">
                                <view
                                    v-if="currentPersonId === item.id"
                                    class="absolute top-0 right-0 w-[64rpx] h-[64rpx] bg-primary rounded-bl-[32rpx] flex items-start justify-end p-1.5 z-10">
                                    <u-icon name="checkbox-mark" color="#ffffff" size="28" />
                                </view>

                                <view class="absolute top-[16rpx] left-[16rpx] z-20">
                                    <view
                                        v-if="item.is_configured"
                                        class="flex items-center gap-[4rpx] bg-[#ECFDF5] border border-[#A7F3D0] px-[10rpx] py-[4rpx] rounded-full">
                                        <view
                                            class="w-[10rpx] h-[10rpx] rounded-full bg-[#10B981] flex-shrink-0"></view>
                                        <text class="text-[18rpx] text-[#059669] font-medium">已配置</text>
                                    </view>
                                    <view
                                        v-else
                                        class="flex items-center gap-[4rpx] bg-[#F3F4F6] border border-[#E5E7EB] px-[10rpx] py-[4rpx] rounded-full">
                                        <view
                                            class="w-[10rpx] h-[10rpx] rounded-full bg-[#D1D5DB] flex-shrink-0"></view>
                                        <text class="text-[18rpx] text-[#9CA3AF] font-medium">未配置</text>
                                    </view>
                                </view>

                                <view
                                    class="w-[100rpx] h-[100rpx] rounded-full overflow-hidden border-2 border-solid border-white shadow-[0_4rpx_12rpx_rgba(0,0,0,0.05)] mb-3">
                                    <image :src="item.avatar_url" class="w-full h-full" mode="aspectFill" />
                                </view>

                                <text
                                    class="text-[28rpx] font-bold text-[#1A1A1A] mb-1.5 w-full text-center truncate px-2">
                                    {{ item.persona_name }}
                                </text>

                                <view
                                    class="px-2.5 py-0.5 rounded-full border border-solid border-[#E5E7EB] bg-white text-[20rpx] text-[#666666]">
                                    {{ PersonTypeMap[item.persona_type as PersonTypeKey] }}
                                </view>

                                <view
                                    v-if="item.is_configured === 0 && currentPersonId == item.id"
                                    class="absolute bottom-0 left-0 right-0 h-[64rpx] flex items-center justify-center gap-[6rpx] bg-[#F9FAFB] border-t border-[#F0F0F0]"
                                    @click="handleEditPerson(item.id)">
                                    <u-icon name="edit-pen" color="#9CA3AF" size="20" />
                                    <text class="text-[20rpx] text-[#9CA3AF]">去完善信息</text>
                                </view>
                            </view>
                        </view>
                    </scroll-view>

                    <view
                        v-else
                        class="bg-white rounded-[32rpx] p-4 shadow-[0_4rpx_24rpx_rgba(0,0,0,0.02)] flex items-center">
                        <view
                            class="w-[240rpx] h-[260rpx] bg-[#F4F5F7] rounded-[24rpx] flex flex-col items-center justify-center gap-2"
                            @click="handleCreatePerson">
                            <view
                                class="w-[64rpx] h-[64rpx] bg-[#DCDFE6] rounded-[16rpx] flex items-center justify-center">
                                <u-icon name="plus" color="#ffffff" size="32" />
                            </view>
                            <text class="text-[26rpx] text-[#666666] font-medium">创建人设</text>
                        </view>
                    </view>
                </view>

                <view
                    v-if="personList.length > 0"
                    class="bg-white rounded-[32rpx] p-5 shadow-[0_4rpx_24rpx_rgba(0,0,0,0.02)] flex flex-col gap-6">
                    <view class="flex items-center pb-2 border-b border-[#F4F5F7]">
                        <text class="text-[28rpx] text-[#1A1A1A] font-bold">素材库：</text>
                        <text class="text-[30rpx] font-extrabold text-primary">{{ currentPerson?.persona_name }}</text>
                    </view>

                    <material-section
                        title="视频素材"
                        :count="videoMaterials.length"
                        :list="videoMaterials"
                        @play="handlePlay($event)"
                        @add="handleMore('video')"
                        @more="handleMore('video')" />

                    <material-section
                        title="数字人素材"
                        :count="digitalMaterials.length"
                        :list="digitalMaterials"
                        @add="handleMore('digital')"
                        @more="handleMore('digital')" />

                    <material-section
                        title="图片素材"
                        :count="imageMaterials.length"
                        :list="imageMaterials"
                        :is-video="false"
                        @add="handleMore('image')"
                        @more="handleMore('image')" />
                </view>
            </template>
        </view>

        <view
            class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] shadow-[0_-8rpx_30rpx_rgba(0,0,0,0.04)] z-50 flex items-center justify-between gap-x-2">
            <view class="flex flex-col">
                <text class="text-[22rpx] text-[#999999] mb-0.5">当前人设</text>
                <text
                    class="text-[30rpx] font-extrabold line-clamp-1"
                    :class="currentPerson ? 'text-primary' : 'text-[#999999]'">
                    {{ currentPerson?.persona_name || "暂无人设" }}
                </text>
            </view>
            <view class="basis-1/2 shrink-0">
                <u-button
                    type="primary"
                    shape="circle"
                    :ripple="true"
                    :loading="confirming"
                    :disabled="loading"
                    :custom-style="{
                        height: '96rpx',
                        fontSize: '30rpx',
                        fontWeight: '900',
                        color: '#ffffff',
                        border: 'none',
                        boxShadow: isPersonSelected ? '0 10rpx 30rpx rgba(0, 101, 251, 0.3)' : 'none',
                    }"
                    @click="handleConfirm">
                    确认绑定
                </u-button>
            </view>
        </view>
    </view>

    <choose-person
        v-if="showChoosePerson"
        v-model="showChoosePerson"
        ref="choosePersonRef"
        :limit="1"
        :is-config="false"
        @select="handleSelectPerson" />
    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false" />
</template>

<script setup lang="ts">
import { getDeviceDetail as getDeviceDetailApi, updateDevice } from "@/api/device";
import {
    getPersonList as getPersonListApi,
    getMaterialLibraryList as getMaterialLibraryListApi,
    getAvatarList as getAvatarListApi,
} from "@/api/person";
import { PersonTypeMap } from "@/enums/appEnums";
import MaterialSection from "./components/material-section.vue";

// ─── 类型定义 ─────────────────────────────────────────────────────

type PersonTypeKey = keyof typeof PersonTypeMap;

interface PersonItem {
    id: number;
    persona_name: string;
    persona_type: PersonTypeKey;
    avatar_url: string;
    publish_mode: number;
    is_configured: number;
}

interface MaterialItem {
    id: number;
    file_url: string;
    pic: string;
    material_type?: number;
}

interface ChoosePersonRef {
    setChooseLists: (list: PersonItem[]) => void;
}

// ─── 页面状态 ─────────────────────────────────────────────────────
const initialized = ref(false);
const loading = ref<boolean>(true);
const confirming = ref<boolean>(false);

const deviceName = ref<string>("");
const deviceCode = ref<string>("");
const deviceDetail = ref<any>({});
const personList = ref<PersonItem[]>([]);
const videoMaterials = ref<MaterialItem[]>([]);
const digitalMaterials = ref<MaterialItem[]>([]);
const imageMaterials = ref<MaterialItem[]>([]);

// 用 null 表示"未选中"，语义比 -1 更清晰
const currentPersonId = ref<number | null>(null);

const currentPerson = computed<PersonItem | null>(
    () => personList.value.find((p) => p.id === currentPersonId.value) ?? null
);

/** 是否已选中人设，用于模板和保存校验 */
const isPersonSelected = computed<boolean>(() => currentPersonId.value !== null);

// ─── 弹窗状态 ─────────────────────────────────────────────────────

const showChoosePerson = ref<boolean>(false);
const choosePersonRef = ref<ChoosePersonRef | null>(null);
const showVideoPreview = ref<boolean>(false);
const playItem = ref<{ url: string; pic: string }>({ url: "", pic: "" });

// ─── 人设操作 ─────────────────────────────────────────────────────

const selectIp = (id: number): void => {
    if (currentPersonId.value === id) return;
    currentPersonId.value = id;
    refreshMaterials();
};

const handleEditPerson = (id: number): void => {
    uni.navigateTo({ url: `/ai_modules/person/pages/create/create?id=${id}&mode=edit&source=back` });
};

const handleMorePerson = (): void => {
    showChoosePerson.value = true;
    const selected = personList.value.filter((item) => item.id === currentPersonId.value);
    choosePersonRef.value?.setChooseLists(selected);
};

/** 空状态下创建人设，跳转到人设创建页 */
const handleCreatePerson = (): void => {
    uni.navigateTo({ url: "/ai_modules/person/pages/create/create" });
};

const handleSelectPerson = (item: PersonItem): void => {
    if (currentPersonId.value === item.id) return;
    currentPersonId.value = item.id;

    personList.value = personList.value.filter((p) => p.id !== item.id);
    personList.value.unshift(item);

    refreshMaterials();
};

// ─── 素材操作 ─────────────────────────────────────────────────────

const handlePlay = (item: MaterialItem): void => {
    playItem.value = { url: item.file_url, pic: item.pic };
    showVideoPreview.value = true;
};

/**
 * 添加和查看更多跳转同一页面，handleAdd 直接复用 handleMore
 * 原 handleAdd 是无意义套壳，已删除
 */
const handleMore = (type: "video" | "image" | "digital"): void => {
    if (type === "digital") {
        uni.navigateTo({
            url: `/ai_modules/person/pages/digital_human_config/digital_human_config?id=${currentPersonId.value}`,
        });
    } else {
        uni.navigateTo({
            url: `/ai_modules/person/pages/material_library/material_library?id=${currentPersonId.value}`,
        });
    }
};

// ─── 确认绑定 ─────────────────────────────────────────────────────

const handleConfirm = async (): Promise<void> => {
    if (!isPersonSelected.value) {
        uni.showToast({ title: "请先选择或创建人设", icon: "none" });
        return;
    }
    if (videoMaterials.value.length === 0 || digitalMaterials.value.length === 0 || imageMaterials.value.length === 0) {
        uni.showToast({ title: "请先添加相关素材后再绑定", icon: "none" });
        return;
    }
    confirming.value = true;
    try {
        uni.showLoading({ title: "绑定中...", mask: true });
        await updateDevice({
            device_code: deviceCode.value,
            persona_id: currentPersonId.value!,
        });
        uni.showToast({ title: "绑定成功", icon: "none", duration: 2000 });
        setTimeout(
            () => uni.redirectTo({ url: `/ai_modules/device/pages/detail/detail?device_code=${deviceCode.value}` }),
            1500
        );
    } catch (error: unknown) {
        const msg = typeof error === "string" ? error : "绑定失败，请重试";
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
        confirming.value = false;
    }
};

// ─── 数据获取 ─────────────────────────────────────────────────────

/** 并行刷新当前人设的素材库和数字人列表 */
const refreshMaterials = async (): Promise<void> => {
    if (currentPersonId.value === null) return;
    await Promise.all([getMaterialLibraryList(), getAvatarList()]);
};

const getMaterialLibraryList = async (): Promise<void> => {
    const { lists } = await getMaterialLibraryListApi({
        page_size: 25000,
        persona_id: currentPersonId.value,
        publish_mode: currentPerson.value?.publish_mode,
    });
    const mapped: MaterialItem[] = (lists ?? []).map((item: any) => ({
        ...item,
        pic: item.thumbnail_url,
    }));
    videoMaterials.value = mapped.filter((item) => item.material_type === 1);
    imageMaterials.value = mapped.filter((item) => item.material_type === 2);
};

const getAvatarList = async (): Promise<void> => {
    const { lists } = await getAvatarListApi({
        persona_id: currentPersonId.value,
        page_size: 25000,
    });
    digitalMaterials.value = (lists ?? []).map((item: any) => ({
        id: item.id,
        file_url: item.video_url,
        pic: item.cover_url,
    }));
};

const getPersonList = async (): Promise<void> => {
    const { lists } = await getPersonListApi({ page: 1, page_size: 2500 });
    const allList: PersonItem[] = lists ?? [];

    if (currentPersonId.value !== null) {
        const selectedIndex = allList.findIndex((p) => p.id === currentPersonId.value);

        if (selectedIndex !== -1) {
            const [selectedItem] = allList.splice(selectedIndex, 1);
            allList.unshift(selectedItem);
        }
    } else {
        currentPersonId.value = allList[0].id;
    }

    personList.value = allList.slice(0, 3);

    await refreshMaterials();
};

const getDeviceDetail = async (): Promise<void> => {
    const res = await getDeviceDetailApi({ device_code: deviceCode.value });
    deviceName.value = res.device_name ?? "";
    deviceDetail.value = res;
};

const init = async (): Promise<void> => {
    loading.value = true;
    try {
        await getDeviceDetail();
        await getPersonList();
    } finally {
        loading.value = false;
        initialized.value = true; // 标记初始化完成
    }
};

// ─── 生命周期 ─────────────────────────────────────────────────────

onLoad((options: any) => {
    deviceCode.value = options.device_code ?? "";
    const pid = Number(options.person_id);
    currentPersonId.value = pid > 0 ? pid : null;
    init();
});

onShow(() => {
    if (!initialized.value) return;
    if (currentPersonId.value !== null) {
        refreshMaterials();
    }
    getPersonList();
});
</script>
