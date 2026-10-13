<template>
    <view class="h-full">
        <z-paging
            ref="pagingRef"
            v-model="list"
            :fixed="false"
            :safe-area-inset-bottom="true"
            @query="queryList">
            <view class="flex flex-col gap-[16rpx] px-[32rpx] pt-[24rpx]">
                <view
                    v-for="(item, index) in list"
                    :key="index"
                    class="bg-white rounded-[28rpx] p-[28rpx] flex gap-[24rpx] active:opacity-90 relative"
                    @click="handleDetail(item)">
                    <view class="kb-avatar">
                        <image
                            v-if="item.image"
                            :src="item.image"
                            class="w-full h-full rounded-[20rpx]"
                            mode="aspectFill" />
                    </view>
                    <view class="flex-1 min-w-0">
                        <text class="text-[30rpx] font-bold text-[#1D2129] line-clamp-1">
                            {{ item.name }}
                        </text>
                        <text class="text-[22rpx] text-[#9CA3AF] line-clamp-2 leading-relaxed mt-[8rpx] block">
                            {{
                                item.intro ||
                                item.description ||
                                (isKbOwner(item)
                                    ? "暂无详细描述信息，点击进入管理详情…"
                                    : "团队共享知识库，点击查看与搜索测试…")
                            }}
                        </text>
                        <view class="flex items-center gap-x-[16rpx] mt-[16rpx]">
                            <text class="kb-doc-tag">{{ item.file_counts || 0 }} 文档</text>
                            <text class="text-[20rpx] text-[#C0C8D8]">{{ item.create_time }}</text>
                        </view>
                    </view>
                    <view
                        v-if="isKbOwner(item)"
                        class="absolute top-[20rpx] right-[20rpx] w-[48rpx] h-[48rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center active:opacity-70 z-[2]"
                        @click.stop="handleMore(item)">
                        <u-icon name="more-dot-fill" :size="22" color="#94A3B8" />
                    </view>
                </view>
            </view>
            <template #empty>
                <empty />
            </template>
        </z-paging>
    </view>
</template>

<script lang="ts" setup>
import { vectorKnowledgeBaseDelete, vectorKnowledgeBaseLists } from "@/api/knowledge_base";

const pagingRef = ref<any>(null);
const list = ref<any[]>([]);

/** 团队共享知识库:仅创建者可删除 */
const isKbOwner = (item: any) => Number(item.is_super) === 1 || Number(item.is_owner) === 1;

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await vectorKnowledgeBaseLists({ page_no, page_size });
        pagingRef.value?.complete(lists || []);
    } catch (error) {
        pagingRef.value?.complete(false);
    }
};

const handleDetail = (item: any) => {
    uni.$u.route({
        url: "/packages/pages/knowledge_detail/knowledge_detail",
        params: { id: item.id, name: item.name },
    });
};

const handleMore = (item: any) => {
    if (!isKbOwner(item)) return;
    uni.showActionSheet({
        itemList: ["删除"],
        success: (res) => {
            if (res.tapIndex !== 0) return;
            uni.showModal({
                title: "提示",
                content: "确定删除该知识库吗？",
                success: async (modalRes) => {
                    if (!modalRes.confirm) return;
                    uni.showLoading({ title: "删除中...", mask: true });
                    try {
                        await vectorKnowledgeBaseDelete({ id: item.id });
                        uni.hideLoading();
                        uni.showToast({ title: "删除成功", icon: "none" });
                        list.value = list.value.filter((row) => row.id != item.id);
                        uni.$emit("knowledgeUpdated");
                    } catch (error: any) {
                        uni.hideLoading();
                        uni.$u.toast(typeof error === "string" ? error : "删除失败");
                    }
                },
            });
        },
    });
};

const reload = () => pagingRef.value?.reload();
// knowledgeUpdated：新建/编辑知识库；knowledgeDocUpdated：文档增删后刷新 file_counts
onMounted(() => {
    uni.$on("knowledgeUpdated", reload);
    uni.$on("knowledgeDocUpdated", reload);
});
onUnmounted(() => {
    uni.$off("knowledgeUpdated", reload);
    uni.$off("knowledgeDocUpdated", reload);
});

defineExpose({ reload });
</script>

<style lang="scss" scoped>
.kb-avatar {
    @apply w-[96rpx] h-[96rpx] rounded-[20rpx] flex-shrink-0 flex items-center justify-center overflow-hidden;
}
.kb-doc-tag {
    @apply text-[20rpx] text-[#94A3B8] bg-[#F1F5F9] px-[14rpx] py-[4rpx] rounded-full;
}
</style>
