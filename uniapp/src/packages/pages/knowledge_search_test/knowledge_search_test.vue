<template>
    <view class="min-h-screen bg-[#F2F4FA] flex flex-col">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#F2F4FA' }"
            title="搜索测试"
            title-bold
            back-icon-color="#1D2129" />

        <scroll-view scroll-y class="flex-1 min-h-0">
            <text class="block px-[32rpx] py-[24rpx] text-[24rpx] text-[#94A3B8] leading-relaxed">
                根据给定的查询文本测试知识的搜索效果，调优检索参数。
            </text>

            <!-- 源文本测试 -->
            <view class="card">
                <view class="flex items-center justify-between mb-[20rpx]">
                    <text class="text-[28rpx] font-bold text-[#1D2129]">源文本测试</text>
                    <view class="cfg-btn" @click="showConfig = true">
                        <u-icon name="setting" color="#2563EB" :size="24" />
                        <text class="text-[24rpx] font-semibold text-[#2563EB] ml-[6rpx]">参数配置</text>
                    </view>
                </view>
                <view class="src-ta-wrap">
                    <textarea
                        v-model="sourceText"
                        class="src-ta"
                        maxlength="200"
                        placeholder="输入要测试的查询文本…" />
                </view>
                <view class="text-right text-[22rpx] text-[#94A3B8] mt-[8rpx]">{{ sourceText.length }} / 200</view>
                <view class="src-cta" :class="{ 'src-cta--disabled': isTesting }" @click="lockFn">
                    <u-icon name="arrow-right" color="#ffffff" :size="26" />
                    <text class="text-white text-[28rpx] font-bold ml-[6rpx]">
                        {{ isTesting ? "召回测试中..." : "开始召回测试" }}
                    </text>
                </view>
            </view>

            <!-- 召回结果明细 -->
            <view v-if="resultList.length" class="card">
                <view class="flex items-center gap-x-[12rpx] mb-[20rpx]">
                    <text class="text-[28rpx] font-bold text-[#1D2129]">召回结果明细</text>
                    <text class="res-badge">{{ resultList.length }} 段落</text>
                </view>
                <view class="flex flex-col gap-y-[16rpx]">
                    <view v-for="(item, index) in resultList" :key="index" class="res-item">
                        <view class="flex items-center gap-x-[10rpx] mb-[16rpx]">
                            <text class="res-no">#{{ index + 1 }}</text>
                            <text class="flex-1 min-w-0 text-[24rpx] font-medium text-[#64748B] truncate">
                                {{ item.source || item.name || "-" }}
                            </text>
                            <view class="source-btn" @click="handleOpenSource(item)">
                                <text class="text-[24rpx] font-semibold text-[#2563EB]">查看源文</text>
                                <u-icon name="arrow-right" color="#2563EB" :size="22" />
                            </view>
                        </view>
                        <view class="qa-block qa-block--q">
                            <text class="qa-label">问</text>
                            <text class="qa-content">{{ item.content || item.question || "-" }}</text>
                        </view>
                        <view class="qa-block qa-block--a mt-[12rpx]">
                            <text class="qa-label qa-label--a">答</text>
                            <text class="qa-content">{{ item.answer || "-" }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 测试记录 -->
            <view v-if="recordList.length" class="card">
                <text class="block text-[28rpx] font-bold text-[#1D2129] mb-[12rpx]">测试记录</text>
                <view
                    v-for="(item, index) in recordList"
                    :key="index"
                    class="rec-item"
                    @click="handleRecord(item)">
                    <view class="rec-icon">
                        <u-icon name="clock" color="#94A3B8" :size="26" />
                    </view>
                    <text class="flex-1 min-w-0 text-[26rpx] text-[#1D2129] truncate">{{ item.ask }}</text>
                    <text class="text-[22rpx] text-[#94A3B8] flex-shrink-0">{{ item.create_time }}</text>
                </view>
            </view>
            <view class="h-[40rpx]"></view>
        </scroll-view>

        <search-config-popup v-model="showConfig" :params="config" @confirm="handleConfigConfirm" />
    </view>
</template>

<script lang="ts" setup>
import {
    vectorKnowledgeBaseHitTest,
    vectorKnowledgeBaseHitTestRecords,
    vectorKnowledgeBaseHitTestRecordDetail,
} from "@/api/knowledge_base";
import { useLockFn } from "@/hooks/useLockFn";
import SearchConfigPopup from "./components/search-config-popup.vue";

const kbId = ref<string | number>("");
const sourceText = ref("");
const resultList = ref<any[]>([]);
const recordList = ref<any[]>([]);
const showConfig = ref(false);

const config = reactive({
    search_mode: "similar",
    search_tokens: 8000,
    search_similar: 0,
    ranking_status: 0,
    ranking_score: 0,
});

onLoad((options: any) => {
    kbId.value = options?.id || "";
    queryRecords();
});

const queryRecords = async () => {
    if (!kbId.value) return;
    try {
        const res: any = await vectorKnowledgeBaseHitTestRecords({
            kb_id: kbId.value,
            page_no: 1,
            page_size: 20,
        });
        recordList.value = res.lists || [];
    } catch (error) {
        // 记录列表失败不阻断测试
    }
};

const handleConfigConfirm = (payload: Record<string, any>) => {
    Object.assign(config, { search_similar: 0 }, payload);
};

const handleTest = async () => {
    if (!sourceText.value.trim()) {
        uni.$u.toast("请输入测试文本");
        return;
    }
    uni.showLoading({ title: "召回测试中...", mask: true });
    try {
        const data: any = await vectorKnowledgeBaseHitTest({
            kb_id: kbId.value,
            question: sourceText.value,
            ...config,
        });
        resultList.value = Array.isArray(data) ? data : data?.lists || [];
        uni.hideLoading();
        queryRecords();
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(typeof error === "string" ? error : "测试失败");
    }
};

const { lockFn, isLock: isTesting } = useLockFn(handleTest);

const WEBVIEW_PAGE = "/packages/pages/webview/webview";
const OPEN_DOC_EXTS = ["doc", "docx", "xls", "xlsx", "ppt", "pptx", "pdf"];

const handleOpenSource = async (item: any) => {
    const url = item.source_path || item.path || item.url;
    if (!url) {
        uni.$u.toast("文件路径不存在");
        return;
    }
    // #ifdef H5
    window.open(url, "_blank");
    // #endif
    // #ifndef H5
    const ext = (String(url).split("?")[0].split(".").pop() || "").toLowerCase();
    if (OPEN_DOC_EXTS.includes(ext)) {
        uni.showLoading({ title: "打开中...", mask: true });
        try {
            const { tempFilePath } = await uni.downloadFile({ url });
            await uni.openDocument({ filePath: tempFilePath, showMenu: true });
        } catch (error) {
            uni.$u.toast("打开失败");
        } finally {
            uni.hideLoading();
        }
        return;
    }
    uni.navigateTo({
        url: `${WEBVIEW_PAGE}?url=${encodeURIComponent(url)}&title=${encodeURIComponent(item.source || "源文")}`,
    });
    // #endif
};

const handleRecord = async (item: any) => {
    uni.showLoading({ title: "加载中...", mask: true });
    try {
        const data: any = await vectorKnowledgeBaseHitTestRecordDetail({ tr_id: item.id });
        resultList.value = Array.isArray(data) ? data : data?.lists || [];
        sourceText.value = item.ask || "";
        uni.hideLoading();
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(typeof error === "string" ? error : "加载失败");
    }
};
</script>

<style lang="scss" scoped>
.card {
    @apply bg-white rounded-[28rpx] mx-[32rpx] mb-[28rpx] p-[32rpx];
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
.cfg-btn {
    @apply inline-flex items-center bg-[#EBF2FF] rounded-full px-[20rpx] py-[10rpx] active:opacity-80;
}
.src-ta-wrap {
    @apply bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[20rpx] border border-solid border-[#E5EAF3];
}
.src-ta {
    @apply w-full text-[28rpx] text-[#1D2129] leading-relaxed min-h-[140rpx];
}
.src-cta {
    @apply w-full h-[88rpx] rounded-[20rpx] flex items-center justify-center mt-[24rpx] active:opacity-85;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
    box-shadow: 0 8rpx 28rpx rgba(47, 115, 246, 0.32);
    &--disabled {
        @apply opacity-60 pointer-events-none;
    }
}
.res-badge {
    @apply text-[22rpx] font-bold text-[#2563EB] bg-[#EBF2FF] px-[16rpx] py-[4rpx] rounded-full;
}
.res-item {
    @apply bg-[#F7FAFE] rounded-[20rpx] p-[24rpx] border border-solid border-[#E0E9F8];
}
.res-no {
    @apply flex-shrink-0 text-[22rpx] font-bold text-[#2563EB] bg-[#DCEAFE] px-[12rpx] py-[2rpx] rounded-[6rpx];
}
.source-btn {
    @apply flex-shrink-0 flex items-center gap-x-[2rpx] active:opacity-70;
}
.qa-block {
    @apply flex gap-x-[12rpx] rounded-[12rpx] p-[16rpx];
}
.qa-block--q {
    @apply bg-[#EBF2FF];
}
.qa-block--a {
    @apply bg-white border border-solid border-[#E5EAF3];
}
.qa-label {
    @apply flex-shrink-0 text-[22rpx] font-extrabold text-[#2563EB];
}
.qa-label--a {
    @apply text-[#94A3B8];
}
.qa-content {
    @apply flex-1 min-w-0 text-[24rpx] text-[#475569] leading-relaxed break-all;
}
.rec-item {
    @apply flex items-center gap-x-[20rpx] py-[24rpx] border-0 border-b border-solid border-[#F0F2F7] active:opacity-70;
    &:last-child {
        @apply border-b-0;
    }
}
.rec-icon {
    @apply w-[56rpx] h-[56rpx] rounded-[14rpx] bg-[#F1F5F9] flex items-center justify-center flex-shrink-0;
}
</style>
