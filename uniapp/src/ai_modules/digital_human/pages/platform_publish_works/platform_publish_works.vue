<template>
    <view class="h-screen flex flex-col">
        <view class="flex items-center justify-between py-4 px-4">
            <text class="text-xl font-medium text-[#1a1a1a]">发布管理</text>
        </view>
        <view class="grow min-h-0 mt-2">
            <z-paging ref="pagingRef" v-model="list" :fixed="false" @query="queryList">
                <view class="px-4 space-y-4">
                    <view
                        v-for="(item, index) in list"
                        :key="index"
                        class="bg-white rounded-[24rpx] shadow-sm border border-solid border-[#000000]/5 overflow-hidden active:scale-[0.98] transition-all duration-200">
                        <view class="p-4 flex items-center justify-between">
                            <view class="flex items-center gap-2">
                                <view class="w-1.5 h-4 bg-primary rounded-full"></view>
                                <text class="text-[#333] font-medium text-lg line-clamp-1">{{ item.name }}</text>
                            </view>
                            <view class="p-2 bg-[#f9f9f9] rounded-xl" @click.stop="handleEdit(item)">
                                <image src="/static/images/icons/edit_pen.svg" class="w-4 h-4" />
                            </view>
                        </view>

                        <view class="px-4 pb-4">
                            <view
                                class="bg-[#f9f9f9]/50 rounded-2xl p-3 flex items-center justify-between border border-solid border-[#ececec]">
                                <view class="flex items-center gap-3">
                                    <view class="relative">
                                        <image
                                            src="/static/images/common/douyin_s.png"
                                            class="w-10 h-10 rounded-full shadow-sm" />
                                        <view
                                            class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-[#22c55e] rounded-full border-2 border-solid border-white"></view>
                                    </view>
                                    <view>
                                        <text class="text-[#b4b4b4] text-xs block">投放平台：DOU音</text>
                                        <view class="flex items-baseline gap-1">
                                            <text class="text-primary font-medium text-xl">{{
                                                item.media_url?.length || 0
                                            }}</text>
                                            <text class="text-[#9b9b9b] text-xs font-medium">个视频已就绪</text>
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view class="flex gap-3 mt-5">
                                <view
                                    class="flex-1 flex items-center justify-center gap-2 py-3.5 rounded-2xl bg-primary shadow-[0_8px_16px_-4px_rgba(0,101,251,0.3)] active:scale-[0.98] transition-all"
                                    @click.stop="scanPublish(item)">
                                    <text class="text-lg">📱</text>
                                    <text class="text-white font-medium tracking-wide">立即扫码发布</text>
                                </view>
                            </view>
                        </view>

                        <view
                            class="px-4 py-3 bg-[#f9f9f9]/30 border-t border-[#f9f9f9] flex items-center justify-between">
                            <text class="text-[#b4b4b4] text-xs"> 创建于 {{ item.create_time }} </text>
                            <view class="flex items-center gap-4">
                                <text class="text-[#f87171] text-xs font-medium" @click.stop="handleDelete(item.id)"
                                    >删除</text
                                >
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
    </view>
    <name-edit ref="nameEditRef" v-model="showNameEdit" title="项目名称" :maxlength="100" @confirm="confirmNameEdit" />
</template>
<script setup lang="ts">
import { getManualPublishList, editManualPublish, deleteManualPublish } from "@/api/device";
import NameEdit from "@/ai_modules/digital_human/components/keywords-edit/keywords-edit.vue";

const pagingRef = shallowRef();

const list = ref<any[]>([]);

const showNameEdit = ref(false);
const editItem = ref<any>({});
const nameEditRef = shallowRef<InstanceType<typeof NameEdit>>();

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getManualPublishList({ page_no, page_size });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleEdit = async (item: any) => {
    showNameEdit.value = true;
    editItem.value = item;
    await nextTick();
    nameEditRef.value?.setFormData(item.name);
};

const confirmNameEdit = async (name: string) => {
    showNameEdit.value = false;
    uni.showLoading({
        title: "编辑中...",
        mask: true,
    });
    try {
        await editManualPublish({
            id: editItem.value?.id,
            name: name,
        });
        uni.hideLoading();
        uni.showToast({
            title: "编辑成功",
            icon: "none",
            duration: 3000,
        });
        list.value.forEach((item: any) => {
            if (item.id === editItem.value?.id) {
                item.name = name;
            }
        });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const viewVideos = (item: any) => {
    console.log("查看视频:", item);
};

const viewRecords = (item: any) => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/platform_publish_record/platform_publish_record",
        params: {
            id: item.id,
        },
    });
};

const scanPublish = (item: any) => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/platform_publish_video/platform_publish_video",
        params: {
            id: item.id,
        },
    });
};

const handleDelete = (id: string) => {
    uni.showModal({
        title: "提示",
        content: "确定删除该项目吗？",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "删除中...",
                    mask: true,
                });
                try {
                    await deleteManualPublish({ id });
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 3000,
                    });
                    list.value = list.value.filter((item: any) => item.id !== id);
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error,
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
        },
    });
};
</script>
<style scoped>
.btn-action {
    @apply flex flex-col items-center justify-center p-3 rounded-2xl border border-solid border-[#ececec] transition-all active:opacity-70;
    background: #ffffff;
}
</style>
