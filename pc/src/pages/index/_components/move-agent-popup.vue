<template>
    <Teleport to="body">
        <Transition name="popup-fade">
            <div
                v-if="internalVisible"
                class="fixed inset-0 z-[9999] flex items-center justify-center"
                @click="handleMaskClick">
                <div class="absolute inset-0 bg-[#000000]/20 backdrop-blur-sm"></div>

                <Transition name="popup-scale">
                    <div
                        v-if="internalVisible"
                        ref="popupRef"
                        class="relative w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden"
                        @click.stop>
                        <div class="flex items-center justify-between p-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center">
                                    <Icon name="el-icon-FolderChecked" :size="18" color="white"></Icon>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-slate-800">移动智能体</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">选择目标分组</p>
                                </div>
                            </div>
                            <div class="w-8 h-8" @click="handleClose">
                                <close-btn />
                            </div>
                        </div>

                        <div
                            v-if="currentAgent"
                            class="p-4 bg-gradient-to-r from-blue-50 to-purple-50 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <ElImage
                                    :src="currentAgent.image"
                                    :alt="currentAgent.name"
                                    class="w-10 h-10 rounded-full border-2 border-white shadow-sm"
                                    fit="cover">
                                    <template #error>
                                        <div
                                            class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-400 text-white text-sm font-bold">
                                            {{ currentAgent.name.charAt(0) }}
                                        </div>
                                    </template>
                                </ElImage>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-slate-700 truncate">
                                        {{ currentAgent.name }}
                                    </div>
                                    <div class="text-xs text-slate-500 truncate">
                                        当前分组：{{ getCurrentGroupName() }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="max-h-64 overflow-y-auto">
                            <div class="p-2 space-y-2">
                                <div
                                    v-for="group in availableGroups"
                                    :key="group.id"
                                    class="group-item"
                                    :class="{
                                        'is-selected': selectedGroupId === group.id,
                                        'is-current':
                                            currentAgent?.group_id === group.id ||
                                            (currentAgent?.group_id === 0 && group.id === 0),
                                    }"
                                    @click="handleSelectGroup(group)">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            <Icon
                                                :name="group.id === 0 ? 'el-icon-House' : 'el-icon-Folder'"
                                                :size="14"
                                                :color="selectedGroupId === group.id ? '#3b82f6' : '#64748b'">
                                            </Icon>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-slate-700 truncate">
                                                {{ group.name }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ group.agents?.length || 0 }} 个智能体
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="
                                            currentAgent?.group_id === group.id ||
                                            (currentAgent?.group_id === 0 && group.id === 0)
                                        "
                                        class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-600 font-medium">
                                        当前
                                    </div>

                                    <div
                                        v-else-if="selectedGroupId === group.id"
                                        class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center">
                                        <Icon name="el-icon-Check" :size="12" color="white"></Icon>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border-t border-slate-100 bg-[#f8fafc]/50">
                            <div class="flex gap-2">
                                <ElButton class="flex-1 !h-10" @click="handleClose"> 取消 </ElButton>
                                <ElButton
                                    type="primary"
                                    class="flex-1 !h-10 modern-btn"
                                    :disabled="!selectedGroupId || isCurrentGroup"
                                    :loading="isMoving"
                                    @click="handleConfirm">
                                    {{ isMoving ? "移动中..." : "确认移动" }}
                                </ElButton>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
interface Agent {
    id: number;
    name: string;
    image: string;
    group_id: number;
    description?: string;
}

interface Group {
    id: number;
    name: string;
    agents?: Agent[];
}

interface Props {
    currentAgent?: Agent | null;
    groups?: Group[];
}

interface Emits {
    (e: "close"): void;
    (e: "confirm", data: { agent: Agent; targetGroupId: number }): void;
}

const props = withDefaults(defineProps<Props>(), {
    currentAgent: null,
    groups: () => [],
});

const emit = defineEmits<Emits>();

const popupRef = ref();
const internalVisible = ref(false);
const selectedGroupId = ref<number | null>(null);
const isMoving = ref(false);

// 可选择的分组
const availableGroups = computed(() => {
    return props.groups.filter((group) => group.id !== undefined);
});

// 是否选择了当前分组
const isCurrentGroup = computed(() => {
    if (!props.currentAgent || selectedGroupId.value === null) return false;
    const currentGroupId = props.currentAgent.group_id || 0;
    return selectedGroupId.value === currentGroupId;
});

// 获取当前分组名称
const getCurrentGroupName = () => {
    if (!props.currentAgent) return "";
    const currentGroupId = props.currentAgent.group_id || 0;
    const currentGroup = props.groups.find((g) => g.id === currentGroupId);
    return currentGroup?.name || "智能体";
};

// 选择分组
const handleSelectGroup = (group: Group) => {
    if (props.currentAgent?.group_id === group.id || (props.currentAgent?.group_id === 0 && group.id === 0)) {
        return; // 当前分组不可选
    }
    selectedGroupId.value = group.id;
};

// 确认移动
const handleConfirm = async () => {
    if (!props.currentAgent || selectedGroupId.value === null || isCurrentGroup.value) return;

    isMoving.value = true;
    try {
        emit("confirm", {
            agent: props.currentAgent,
            targetGroupId: selectedGroupId.value,
        });
    } catch (error) {
        console.error("移动失败:", error);
    } finally {
        isMoving.value = false;
        handleClose();
    }
};

// 点击遮罩关闭
const handleMaskClick = (e: MouseEvent) => {
    if (e.target === e.currentTarget) {
        handleClose();
    }
};

// 关闭弹窗
const handleClose = () => {
    internalVisible.value = false;
    selectedGroupId.value = null;
    isMoving.value = false;
    emit("close");
};

// 打开弹窗
const open = (options?: { agent?: Agent }) => {
    // 重置状态
    selectedGroupId.value = null;
    isMoving.value = false;

    // 显示弹窗
    internalVisible.value = true;

    // 阻止页面滚动
    document.body.style.overflow = "hidden";
};

// 关闭弹窗
const close = () => {
    handleClose();
};

// 监听键盘事件
const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === "Escape" && internalVisible.value) {
        handleClose();
    }
};

// 监听 visible 变化
watch(internalVisible, (newVal) => {
    if (!newVal) {
        selectedGroupId.value = null;
        isMoving.value = false;
        // 恢复页面滚动
        document.body.style.overflow = "";
        document.removeEventListener("keydown", handleKeydown);
    } else {
        document.addEventListener("keydown", handleKeydown);
    }
});

// 组件卸载时清理
onUnmounted(() => {
    document.body.style.overflow = "";
    document.removeEventListener("keydown", handleKeydown);
});

defineExpose({
    open,
    close,
});
</script>

<style scoped lang="scss">
// 弹窗淡入淡出动画
.popup-fade-enter-active,
.popup-fade-leave-active {
    transition: opacity 0.2s ease;
}

.popup-fade-enter-from,
.popup-fade-leave-to {
    opacity: 0;
}

// 弹窗缩放动画
.popup-scale-enter-active,
.popup-scale-leave-active {
    transition: all 0.2s ease;
}

.popup-scale-enter-from,
.popup-scale-leave-to {
    opacity: 0;
    transform: scale(0.9);
}

.group-item {
    @apply flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all;
    @apply border border-[transparent];

    &:hover:not(.is-current) {
        @apply bg-slate-50 border-slate-200;
    }

    &.is-selected {
        @apply bg-blue-50 border-blue-200;

        .text-slate-700 {
            @apply text-blue-700;
        }
    }

    &.is-current {
        @apply bg-slate-50 border-slate-200 cursor-not-allowed opacity-75;
    }
}

.modern-btn {
    border: none;
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);

    &:hover:not(:disabled) {
        filter: brightness(1.1);
        transform: translateY(-1px);
    }

    &:active:not(:disabled) {
        transform: translateY(0);
    }

    &:disabled {
        @apply opacity-50 cursor-not-allowed;
        background: #d1d5db;
    }
}
</style>
