<template>
    <popup
        ref="popupRef"
        width="500px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        :append-to-body="true"
        :show-close="false">
        <div class="p-6">
            <div
                class="absolute w-8 h-8 top-5 right-5 z-[22] flex items-center justify-center cursor-pointer hover:bg-slate-100 rounded-full transition-colors"
                @click="close">
                <close-btn />
            </div>

            <div class="flex items-center gap-3 mb-6">
                <div
                    class="w-1.5 h-6 rounded-full transition-all duration-300"
                    :class="
                        activeTab === 'errors'
                            ? 'bg-rose-500 shadow-[0_0_12px_rgba(244,63,94,0.4)]'
                            : 'bg-amber-500 shadow-[0_0_12px_rgba(245,158,11,0.4)]'
                    "></div>
                <span class="text-xl font-[1000] text-slate-900 tracking-tight">
                    {{ activeTab === "errors" ? "任务存在错误" : "时间排期冲突" }}
                </span>
            </div>

            <div class="flex flex-col gap-5">
                <div v-if="showTabs" class="flex gap-2 p-1 bg-slate-100 rounded-2xl">
                    <button
                        class="flex-1 flex items-center justify-center gap-2 h-9 rounded-xl text-sm font-bold transition-all duration-200"
                        :class="
                            activeTab === 'messages'
                                ? 'bg-white text-amber-600 shadow-sm'
                                : 'text-slate-400 hover:text-slate-600'
                        "
                        @click="activeTab = 'messages'">
                        <Icon name="el-icon-Warning" :size="15" />
                        <span>冲突警告</span>
                        <span
                            class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[11px] text-white font-black transition-colors"
                            :class="activeTab === 'messages' ? 'bg-amber-500' : 'bg-slate-300'">
                            {{ messages.length }}
                        </span>
                    </button>
                    <button
                        class="flex-1 flex items-center justify-center gap-2 h-9 rounded-xl text-sm font-bold transition-all duration-200"
                        :class="
                            activeTab === 'errors' ? 'bg-white text-rose-600 ' : 'text-slate-400 hover:text-slate-600'
                        "
                        @click="activeTab = 'errors'">
                        <Icon name="el-icon-CircleClose" :size="15" />
                        <span>错误信息</span>
                        <span
                            class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[11px] text-white font-black transition-colors"
                            :class="activeTab === 'errors' ? 'bg-rose-500' : 'bg-slate-300'">
                            {{ errors.length }}
                        </span>
                    </button>
                </div>

                <div
                    v-if="activeTab === 'messages'"
                    class="relative overflow-hidden bg-[#fffbeb]/40 border border-amber-100 rounded-[24px] p-5">
                    <div class="relative z-10 flex gap-4">
                        <div
                            class="shrink-0 w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                            <Icon name="el-icon-Warning" :size="20" />
                        </div>
                        <div class="flex-1">
                            <ul class="space-y-2">
                                <li
                                    v-for="(msg, index) in messages"
                                    :key="index"
                                    class="text-amber-800 text-sm font-bold leading-snug flex items-start gap-2">
                                    <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                                    {{ msg }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div
                    v-if="activeTab === 'errors'"
                    class="relative overflow-hidden bg-[#fff1f2]/40 border border-rose-100 rounded-[24px] p-5">
                    <div class="relative z-10 flex gap-4">
                        <div
                            class="shrink-0 w-10 h-10 bg-rose-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                            <Icon name="el-icon-CircleClose" :size="20" />
                        </div>
                        <div class="flex-1">
                            <ul class="space-y-2">
                                <li
                                    v-for="(err, index) in errors"
                                    :key="index"
                                    class="text-rose-800 text-sm font-bold leading-snug flex items-start gap-2">
                                    <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span>
                                    {{ err }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="px-2">
                    <template v-if="hasErrors">
                        <p class="text-slate-500 text-sm font-bold tracking-tight">
                            系统检测到当前任务存在错误，无法完成创建操作。
                        </p>
                        <p class="text-slate-900 text-[15px] font-[1000] mt-1.5">请返回修改后重试。</p>
                    </template>
                    <template v-else>
                        <p class="text-slate-500 text-sm font-bold tracking-tight">
                            系统检测到当前排期与已有任务存在重叠，这可能会导致执行队列阻塞或资源分配失败。
                        </p>
                        <p class="text-slate-900 text-[15px] font-[1000] mt-1.5">您确定要继续创建该任务吗？</p>
                    </template>
                </div>

                <div class="mt-2 flex flex-col gap-3">
                    <button
                        v-if="!hasErrors"
                        @click="handleConfirmCreateTask"
                        :disabled="isLock"
                        class="w-full h-[52px] bg-slate-900 text-white text-sm font-[1000] rounded-2xl hover:bg-slate-800 transition-all active:scale-[0.98] flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        确认强行创建
                    </button>
                    <button
                        @click="close"
                        class="w-full h-[52px] text-sm font-black rounded-2xl transition-all"
                        :class="
                            hasErrors
                                ? 'bg-slate-900 text-white hover:bg-slate-800 active:scale-[0.98]'
                                : 'bg-[transparent] text-slate-400 hover:bg-slate-50'
                        ">
                        返回重新修改
                    </button>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const props = defineProps({
    messages: {
        type: Array as PropType<string[]>,
        default: () => [],
    },
    errors: {
        type: Array as PropType<string[]>,
        default: () => [],
    },
});

const emit = defineEmits(["close", "confirm"]);
const popupRef = ref();
const isLock = ref(false);

// errors 是否有数据
const hasErrors = computed(() => props.errors.length > 0);

// 是否同时存在两种数据 → 显示 Tab
const showTabs = computed(() => props.messages.length > 0 && props.errors.length > 0);

// 当前激活的 Tab
const activeTab = ref<"messages" | "errors">(props.messages.length > 0 ? "messages" : "errors");

// props 变化时重置 activeTab
watch(
    () => [props.messages.length, props.errors.length],
    () => {
        activeTab.value = props.messages.length > 0 ? "messages" : "errors";
    }
);

const open = () => {
    popupRef.value.open();
};

const close = () => {
    popupRef.value.close();
    emit("close");
};

const handleConfirmCreateTask = async () => {
    isLock.value = true;
    try {
        emit("confirm");
    } finally {
        isLock.value = false;
    }
};

defineExpose({ open, close });
</script>
