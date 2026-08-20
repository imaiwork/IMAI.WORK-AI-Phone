<template>
    <view class="h-screen bg-[#F2F4FA] flex flex-col">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#F2F4FA' }"
            :title="kbName"
            title-bold
            back-icon-color="#1D2129" />

        <!-- 标题区 -->
        <view class="px-[32rpx] pt-[24rpx] pb-[8rpx] flex items-center gap-x-[12rpx]">
            <text class="text-[34rpx] font-bold text-[#0F172A]">文档内容</text>
            <text class="vector-tag">VECTOR DB</text>
        </view>
        <text class="px-[32rpx] pb-[24rpx] text-[24rpx] text-[#94A3B8] leading-relaxed">
            管理知识库文件，系统将根据这些文档进行语义索引与对话回复。
        </text>

        <!-- 快捷入口 -->
        <view class="flex gap-x-[20rpx] px-[32rpx] pb-[24rpx]">
            <view class="quick-card" @click="handleHitTest">
                <view class="quick-icon bg-[#EBF2FF]">
                    <u-icon name="search" color="#2563EB" :size="30" />
                </view>
                <view class="flex-1 min-w-0">
                    <text class="quick-name">搜索测试</text>
                    <text class="quick-sub">测试检索效果与参数</text>
                </view>
            </view>
            <view v-if="kbCanManage" class="quick-card" @click="handleSetting">
                <view class="quick-icon bg-[#FFF7ED]">
                    <u-icon name="setting" color="#EA580C" :size="30" />
                </view>
                <view class="flex-1 min-w-0">
                    <text class="quick-name">设置</text>
                    <text class="quick-sub">基础信息与索引模式</text>
                </view>
            </view>
        </view>

        <!-- 文档列表 -->
        <view class="flex-1 min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="docList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <template #top>
                    <view v-if="showDocToolbar" class="flex items-center gap-x-[16rpx] px-[32rpx] pb-[20rpx]">
                        <view class="count-pill">所有文档 ({{ docTotal }})</view>
                        <view class="search-input">
                            <u-icon name="search" color="#C0C8D8" :size="26" />
                            <input
                                v-model="keyword"
                                class="flex-1 text-[26rpx] text-[#1D2129] ml-[8rpx]"
                                placeholder="快速检索文档..."
                                @confirm="handleSearch" />
                            <view
                                v-if="isSearching"
                                class="w-[40rpx] h-[40rpx] flex items-center justify-center flex-shrink-0"
                                @click="handleClearSearch">
                                <u-icon name="close-circle-fill" color="#C0C8D8" :size="28" />
                            </view>
                        </view>
                    </view>
                </template>

                <view class="px-[32rpx] flex flex-col gap-y-[16rpx]">
                    <view v-for="(item, index) in docList" :key="index" class="doc-card" @click="handleEditDoc(item)">
                        <view class="doc-icon">
                            <u-icon name="file-text" color="#ffffff" :size="34" />
                        </view>
                        <view class="flex-1 min-w-0">
                            <view class="flex items-center gap-x-[12rpx]">
                                <text class="doc-name flex-1 min-w-0">{{ item.name }}</text>
                                <text v-if="item.status" class="doc-status" :class="getStatusClass(item.status)">
                                    {{ getStatusText(item.status) }}
                                </text>
                            </view>
                            <view
                                class="flex items-center flex-wrap gap-x-[10rpx] mt-[6rpx] text-[22rpx] text-[#94A3B8]">
                                <text class="doc-fmt">{{ (item.type || "FILE").toUpperCase() }}</text>
                                <text>·</text>
                                <text>{{ formatFileSize(item.size) }}</text>
                                <text>·</text>
                                <text>{{ item.create_time }}</text>
                            </view>
                            <view class="flex items-center gap-x-[28rpx] mt-[16rpx]">
                                <text class="text-[24rpx] font-semibold text-[#2563EB]" @click="handleEditDoc(item)">
                                    {{ kbCanManage ? "编辑" : "查看" }}
                                </text>
                                <text
                                    v-if="kbCanManage"
                                    class="text-[24rpx] font-semibold text-[#EF4444]"
                                    @click.stop="handleDeleteDoc(item)">
                                    删除
                                </text>
                            </view>
                        </view>
                    </view>
                </view>

                <template #empty>
                    <view class="empty-box">
                        <view class="empty-illu">
                            <image src="/static/images/icons/doc_empty.svg" class="w-[84rpx] h-[84rpx]" />
                        </view>
                        <template v-if="isSearching">
                            <text class="empty-title">未找到相关文档</text>
                            <text class="empty-desc">试试其他关键词，或清空搜索查看全部</text>
                            <view class="clear-search-cta" @click="handleClearSearch">
                                <text class="text-[#2563EB] text-[26rpx] font-bold">清空搜索</text>
                            </view>
                        </template>
                        <template v-else>
                            <text class="empty-title">暂无文档</text>
                            <text class="empty-desc">上传文档后，AI 将根据它们进行语义索引与对话回复</text>
                            <view v-if="kbCanManage" class="upload-cta" @click="handleUpload">
                                <u-icon name="arrow-upward" color="#ffffff" :size="26" />
                                <text class="text-white text-[26rpx] font-bold ml-[8rpx]">上传文档</text>
                            </view>
                        </template>
                    </view>
                </template>
            </z-paging>
        </view>

        <!-- 底部 CTA：有文档或搜索中时显示，避免搜索无结果时丢失添加入口 -->
        <view v-if="showDocToolbar && kbCanManage" class="bottom-cta">
            <view class="cta-btn" @click="handleUpload">
                <u-icon name="plus" color="#ffffff" :size="30" />
                <text class="text-white font-bold text-[32rpx] ml-[8rpx]">添加新文件</text>
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import {
    vectorKnowledgeBaseDetail,
    vectorKnowledgeBaseFileLists,
    vectorKnowledgeBaseFileDelete,
} from "@/api/knowledge_base";
import { formatFileSize } from "@/utils/util";

const kbId = ref<string | number>("");
const kbName = ref("知识库详情");
const indexId = ref<any>("");
const categoryId = ref<any>("");
/** 团队共享知识库:非创建者仅可查看/检索 */
const kbCanManage = ref(true);

const pagingRef = ref<any>(null);
const docList = ref<any[]>([]);
const docTotal = ref(0);
const keyword = ref("");
const isSearching = computed(() => !!keyword.value.trim());
// 有文档或正在搜索时保留工具栏，避免搜索无结果后搜不到入口
const showDocToolbar = computed(() => docTotal.value > 0 || isSearching.value);

onLoad(async (options: any) => {
    kbId.value = options?.id || "";
    if (options?.name) {
        // 不同跳转方式对 query 编码不一致，做防御性解码
        try {
            kbName.value = decodeURIComponent(options.name);
        } catch {
            kbName.value = options.name;
        }
    }
    await loadDetail();
});

const loadDetail = async () => {
    if (!kbId.value) return;
    try {
        const data: any = await vectorKnowledgeBaseDetail({ id: kbId.value });
        kbName.value = data.name || kbName.value;
        indexId.value = data.index_id ?? "";
        categoryId.value = data.category_id ?? "";
        const power = Number(data.power);
        kbCanManage.value =
            Number(data.owned) === 1 || Number(data.is_owner) === 1 || (power > 0 && power < 3);
    } catch (error) {
        // 详情拉取失败不阻断文档列表
        kbCanManage.value = false;
    }
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const res: any = await vectorKnowledgeBaseFileLists({
            kb_id: kbId.value,
            indexid: indexId.value || "",
            category_id: categoryId.value || "",
            keyword: keyword.value || "",
            page_no,
            page_size,
        });
        docTotal.value = res.count ?? res.lists?.length ?? 0;
        pagingRef.value?.complete(res.lists || []);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleSearch = () => {
    pagingRef.value?.reload();
};

const handleClearSearch = () => {
    keyword.value = "";
    pagingRef.value?.reload();
};

const handleDeleteDoc = (item: any) => {
    if (!kbCanManage.value) return;
    uni.showModal({
        title: "提示",
        content: "确定删除该文档吗？",
        success: async (res) => {
            if (!res.confirm) return;
            uni.showLoading({ title: "删除中...", mask: true });
            try {
                await vectorKnowledgeBaseFileDelete({ fd_id: item.id });
                uni.hideLoading();
                uni.showToast({ title: "删除成功", icon: "none" });
                pagingRef.value?.reload();
                // 通知知识库列表刷新文件数量等统计
                uni.$emit("knowledgeDocUpdated");
            } catch (error: any) {
                uni.hideLoading();
                uni.$u.toast(typeof error === "string" ? error : "删除失败");
            }
        },
    });
};

const handleUpload = () => {
    if (!kbCanManage.value) return;
    uni.$u.route({
        url: "/packages/pages/knowledge_upload/knowledge_upload",
        params: { id: kbId.value },
    });
};

// 上传成功时本页在栈底会 onHide，立即 reload 可能无效；标记 dirty，回页 onShow 再刷
const docsDirty = ref(false);
const markDocsDirty = () => {
    docsDirty.value = true;
};
const reloadDetail = () => loadDetail();
onMounted(() => {
    uni.$on("knowledgeDocUpdated", markDocsDirty);
    uni.$on("knowledgeUpdated", reloadDetail);
});
onUnmounted(() => {
    uni.$off("knowledgeDocUpdated", markDocsDirty);
    uni.$off("knowledgeUpdated", reloadDetail);
});
onShow(() => {
    if (!docsDirty.value) return;
    docsDirty.value = false;
    pagingRef.value?.reload();
});

const handleEditDoc = (item: any) => {
    uni.$u.route({
        url: "/packages/pages/knowledge_doc_segments/knowledge_doc_segments",
        params: {
            kb_id: kbId.value,
            fd_id: item.id,
            // 透传只读,避免非创建者在分段页改写
            readonly: kbCanManage.value ? "0" : "1",
        },
    });
};

const handleHitTest = () => {
    uni.$u.route({
        url: "/packages/pages/knowledge_search_test/knowledge_search_test",
        params: { id: kbId.value },
    });
};

// 文档解析状态文案
const STATUS_TEXT: Record<string, string> = {
    INIT: "待解析",
    PARSING: "解析中",
    PARSE_SUCCESS: "已完成",
};
const getStatusText = (status: string) => STATUS_TEXT[status] || "解析失败";
const getStatusClass = (status: string) => {
    if (status === "PARSE_SUCCESS") return "doc-status--success";
    if (status === "INIT" || status === "PARSING") return "doc-status--pending";
    return "doc-status--fail";
};

const handleSetting = () => {
    if (!kbCanManage.value) {
        uni.$u.toast("仅创建者可修改设置");
        return;
    }
    uni.$u.route({
        url: "/packages/pages/knowledge_setting/knowledge_setting",
        params: { id: kbId.value },
    });
};
</script>

<style lang="scss" scoped>
.vector-tag {
    @apply text-[20rpx] font-bold text-[#2563EB] bg-[#EBF2FF] px-[12rpx] py-[4rpx] rounded-[8rpx];
    letter-spacing: 1rpx;
}
.quick-card {
    @apply flex-1 bg-white rounded-[24rpx] p-[24rpx] flex items-center gap-x-[16rpx] active:opacity-90;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
.quick-icon {
    @apply w-[60rpx] h-[60rpx] rounded-[16rpx] flex items-center justify-center flex-shrink-0;
}
.quick-name {
    @apply block text-[26rpx] font-bold text-[#1D2129];
}
.quick-sub {
    @apply block text-[20rpx] text-[#94A3B8] mt-[4rpx];
}
.count-pill {
    @apply flex-shrink-0 text-[26rpx] font-bold text-[#1D2129] px-[24rpx] py-[14rpx] bg-white rounded-[16rpx];
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
.search-input {
    @apply flex-1 min-w-0 flex items-center bg-white rounded-[16rpx] px-[20rpx] h-[68rpx];
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
.doc-card {
    @apply bg-white rounded-[24rpx] p-[24rpx] flex items-start gap-x-[20rpx];
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
.doc-icon {
    @apply w-[72rpx] h-[72rpx] rounded-[18rpx] flex items-center justify-center flex-shrink-0;
    background: linear-gradient(135deg, #60a5fa, #2563eb);
}
.doc-name {
    @apply text-[28rpx] font-bold text-[#1D2129] leading-snug break-all;
}
.doc-fmt {
    @apply text-[20rpx] font-bold text-[#2563EB] bg-[#EBF2FF] px-[10rpx] py-[2rpx] rounded-[6rpx];
}
.doc-status {
    @apply flex-shrink-0 text-[20rpx] font-semibold px-[12rpx] py-[2rpx] rounded-full;
}
.doc-status--success {
    @apply text-[#16A34A] bg-[#DCFCE7];
}
.doc-status--pending {
    @apply text-[#2563EB] bg-[#EBF2FF];
}
.doc-status--fail {
    @apply text-[#EF4444] bg-[#FEE2E2];
}
.empty-box {
    @apply mx-[32rpx] mt-[28rpx] bg-white rounded-[36rpx] pt-[80rpx] pb-[80rpx] px-[48rpx] flex flex-col items-center;
}
.empty-illu {
    @apply w-[180rpx] h-[180rpx] rounded-full flex items-center justify-center mb-[32rpx];
    background: linear-gradient(135deg, #ebf2ff, #f7f9fc);
}
.empty-title {
    @apply text-[30rpx] font-bold text-[#1D2129];
}
.empty-desc {
    @apply text-[24rpx] text-[#94A3B8] leading-relaxed text-center mt-[12rpx] mb-[40rpx];
}
.upload-cta {
    @apply inline-flex items-center justify-center px-[44rpx] py-[22rpx] rounded-full active:opacity-85;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
    box-shadow: 0 8rpx 28rpx rgba(47, 115, 246, 0.32);
}
.clear-search-cta {
    @apply inline-flex items-center justify-center px-[44rpx] py-[22rpx] rounded-full bg-[#EBF2FF] active:opacity-85;
}
.bottom-cta {
    @apply bg-white px-[32rpx] pt-[20rpx] flex-shrink-0;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    border-top: 1rpx solid #eaeef5;
}
.cta-btn {
    @apply w-full h-[96rpx] rounded-[28rpx] flex items-center justify-center active:opacity-85;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
    box-shadow: 0 12rpx 40rpx rgba(47, 115, 246, 0.28);
}
:deep(.zp-empty-view-center) {
    @apply justify-start;
}
</style>
