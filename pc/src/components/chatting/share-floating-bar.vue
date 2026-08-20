<template>
    <Transition name="slide-up">
        <div v-if="show" class="share-floating-bar">
            <div class="share-glass-container">
                <!-- 左侧：全选 + 计数 -->
                <div class="flex items-center gap-3 shrink-0">
                    <div
                        class="flex items-center min-w-[36px] h-[36px] rounded-[10px] px-1.5 cursor-pointer"
                        style="background: #0065fb10"
                        @click="emit('select-all')">
                        <Icon
                            :name="isAllSelected ? 'local-icon-checkbox_s' : 'local-icon-checkbox'"
                            color="#0065fb"
                            :size="18" />
                        <span class="text-[9px] font-black text-slate-400 tracking-widest ml-2">全选</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-slate-400 tracking-widest">SELECTED</span>
                        <span class="text-[13px] font-black text-slate-800">
                            已选 <span class="text-[#0065fb]">{{ selectedCount }}</span> 条
                        </span>
                    </div>
                </div>

                <!-- 分割线 -->
                <div class="w-px h-7 bg-slate-100 shrink-0"></div>

                <!-- 右侧：操作按钮 -->
                <div class="flex items-center gap-2 shrink-0">
                    <button class="action-item-btn group" @click="emit('generate-image')">
                        <div
                            class="action-icon bg-orange-50 text-orange-600 group-hover:bg-orange-600 group-hover:text-white">
                            <Icon name="el-icon-Picture" :size="16" />
                        </div>
                        <span class="action-label">生成图片</span>
                    </button>

                    <button class="action-item-btn group" @click="emit('generate-pdf')">
                        <div class="action-icon bg-red-50 text-red-600 group-hover:bg-red-600 group-hover:text-white">
                            <Icon name="el-icon-Document" :size="16" />
                        </div>
                        <span class="action-label">导出 PDF</span>
                    </button>

                    <div class="w-2"></div>

                    <button
                        class="w-9 h-9 rounded-full flex items-center justify-center bg-slate-50 text-slate-400 transition-all hover:bg-red-500 hover:text-white"
                        @click="emit('cancel')"
                        title="取消选择">
                        <Icon name="el-icon-Close" :size="16" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
defineProps<{
    show: boolean;
    isAllSelected: boolean;
    selectedCount: number;
}>();

const emit = defineEmits<{
    (e: "select-all"): void;
    (e: "cancel"): void;
    (e: "generate-image"): void;
    (e: "generate-pdf"): void;
    (e: "generate-link"): void;
}>();
</script>

<style scoped lang="scss">
.share-floating-bar {
    position: absolute;
    bottom: 40px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2000;
}

.share-glass-container {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 10px 14px 10px 24px;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.action-item-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px 6px 6px;
    border-radius: 14px;
    transition: all 0.2s;
    border: 1px solid transparent;

    &:hover {
        background: #fff;
        border-color: #f1f5f9;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transform: translateY(-1px);
    }
    &:active {
        transform: translateY(0);
    }
}

.action-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.action-label {
    font-size: 13px;
    font-weight: 900;
    color: #475569;
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translate(-50%, 30px) scale(0.9);
}
</style>
