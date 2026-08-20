<template>
    <view class="h-screen bg-[#F2F4FA] flex flex-col">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#F2F4FA' }"
            title="分段管理"
            title-bold
            back-icon-color="#1D2129" />

        <view class="px-[32rpx] pt-[8rpx] pb-[20rpx] flex items-center gap-x-[16rpx]">
            <view class="flex-1 min-w-0 flex items-center bg-white rounded-[16rpx] px-[20rpx] h-[72rpx]">
                <u-icon name="search" color="#C0C8D8" :size="28" />
                <input
                    v-model="keyword"
                    class="flex-1 text-[26rpx] text-[#1D2129] ml-[10rpx]"
                    placeholder="搜索分段内容..."
                    @confirm="handleSearch" />
            </view>
            <view v-if="kbCanManage" class="add-btn" @click="handleAdd">
                <u-icon name="plus" color="#ffffff" :size="26" />
                <text class="text-white text-[26rpx] font-bold ml-[4rpx]">新增</text>
            </view>
        </view>

        <view class="flex-1 min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="list"
                :fixed="false"
                :default-page-size="100"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[32rpx] flex flex-col gap-y-[16rpx] pt-[4rpx]">
                    <view v-for="(item, index) in list" :key="index" class="seg-card">
                        <view class="flex items-center justify-between mb-[12rpx]">
                            <view class="flex items-center gap-x-[12rpx] min-w-0">
                                <text class="seg-no">#{{ item.index || index + 1 }}</text>
                                <text v-if="item.status_msg" class="seg-status">{{ item.status_msg }}</text>
                            </view>
                            <view v-if="kbCanManage" class="flex items-center gap-x-[24rpx] flex-shrink-0">
                                <text class="text-[24rpx] font-semibold text-[#2563EB]" @click="handleEdit(item)"
                                    >编辑</text
                                >
                                <text class="text-[24rpx] font-semibold text-[#EF4444]" @click="handleDelete(item)"
                                    >删除</text
                                >
                            </view>
                        </view>
                        <text class="text-[26rpx] text-[#475569] leading-relaxed line-clamp-4">
                            {{ item.content || item.question }}
                        </text>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>

        <segment-edit-popup
            v-model="showEdit"
            :kb-id="kbId"
            :fd-id="fdId"
            :edit-uuid="editUuid"
            @success="handleSaved" />
    </view>
</template>

<script lang="ts" setup>
import { vectorKnowledgeBaseChunkLists, vectorKnowledgeBaseChunkDelete } from "@/api/knowledge_base";
import SegmentEditPopup from "./components/segment-edit-popup.vue";

const kbId = ref<string | number>("");
const fdId = ref<string | number>("");
const keyword = ref("");
const list = ref<any[]>([]);
const pagingRef = ref<any>(null);
const kbCanManage = ref(true);

const showEdit = ref(false);
const editUuid = ref("");

onLoad((options: any) => {
    kbId.value = options?.kb_id || "";
    fdId.value = options?.fd_id || "";
    kbCanManage.value = String(options?.readonly || "0") !== "1";
});

const queryList = async (page_no: number, page_size: number) => {
    try {
        const res: any = await vectorKnowledgeBaseChunkLists({
            kb_id: kbId.value,
            fd_id: fdId.value,
            keyword: keyword.value || "",
            page_no,
            page_size,
        });
        // 该接口直接返回分段数组（无 { lists } 包裹）
        const lists = Array.isArray(res) ? res : res.lists || [];
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete(false);
    }
};

const handleSearch = () => pagingRef.value?.reload();

const handleAdd = () => {
    if (!kbCanManage.value) return;
    editUuid.value = "";
    showEdit.value = true;
};

const handleEdit = (item: any) => {
    if (!kbCanManage.value) return;
    editUuid.value = item.uuid;
    showEdit.value = true;
};

const handleSaved = () => pagingRef.value?.reload();

const handleDelete = (item: any) => {
    if (!kbCanManage.value) return;
    uni.showModal({
        title: "提示",
        content: "确定删除该分段吗？",
        success: async (res) => {
            if (!res.confirm) return;
            uni.showLoading({ title: "删除中...", mask: true });
            try {
                await vectorKnowledgeBaseChunkDelete({ kb_id: kbId.value, uuids: [item.uuid] });
                uni.hideLoading();
                uni.showToast({ title: "删除成功", icon: "none" });
                pagingRef.value?.reload();
            } catch (error: any) {
                uni.hideLoading();
                uni.$u.toast(typeof error === "string" ? error : "删除失败");
            }
        },
    });
};
</script>

<style lang="scss" scoped>
.add-btn {
    @apply flex-shrink-0 flex items-center justify-center px-[24rpx] h-[72rpx] rounded-[16rpx] active:opacity-85;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
}
.seg-card {
    @apply bg-white rounded-[24rpx] p-[28rpx];
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
.seg-no {
    @apply flex-shrink-0 text-[22rpx] font-bold text-[#2563EB] bg-[#EBF2FF] px-[14rpx] py-[2rpx] rounded-[8rpx];
}
.seg-status {
    @apply truncate text-[20rpx] text-[#94A3B8] bg-[#F1F5F9] px-[12rpx] py-[2rpx] rounded-full;
}
</style>
