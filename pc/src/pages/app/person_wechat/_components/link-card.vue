<template>
    <div class="link-card-container">
        <div class="link-body">
            <div class="link-desc-wrapper">
                <div class="link-title">
                    {{ title || "未命名链接内容" }}
                </div>
                <span class="link-desc">
                    {{ desc || "暂无描述信息..." }}
                </span>
            </div>
            <div class="link-image-box">
                <img v-if="img" :src="img" class="link-img" alt="cover" />
                <div v-else class="link-img-placeholder">
                    <Icon name="el-icon-Link" :size="20" />
                </div>
            </div>
        </div>

        <div class="link-footer">
            <Icon name="el-icon-Share" :size="12" />
            <span class="ml-1 opacity-60">网页链接</span>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title?: string;
    desc?: string;
    img?: string;
}>();
</script>

<style scoped lang="scss">
.link-card-container {
    @apply p-3 bg-white h-full rounded-xl flex flex-col transition-all duration-300;
    border: 1px solid #f0f0f0;

    &:hover {
        @apply shadow-light border-primary-light-8;
    }
}

.link-title {
    @apply font-[900] text-tx-primary line-clamp-1 break-all;
}

.link-body {
    @apply flex gap-3 w-full grow min-h-0;
}

.link-desc-wrapper {
    @apply flex-1  overflow-hidden;
}

.link-desc {
    @apply line-clamp-2 text-xs text-tx-secondary leading-[1.6] break-all;
    opacity: 0.85;
}

.link-image-box {
    @apply flex-shrink-0 w-[52px] h-[52px] rounded-lg overflow-hidden bg-gray-50 border border-gray-100 flex items-center justify-center;
}

.link-img {
    @apply w-full h-full object-cover transition-transform duration-500;
}

.link-img-placeholder {
    @apply text-tx-placeholder;
}

.link-footer {
    @apply mt-2 pt-2 border-t border-gray-50 flex items-center text-[10px] text-tx-placeholder uppercase tracking-wider font-medium;
}
</style>
