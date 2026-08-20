<template>
    <popup
        ref="popupRef"
        width="520px"
        top="5vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="handleClose">
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Tickets" :size="18" />
                    </div>
                    <span class="text-gray-950 text-lg font-[1000] tracking-tight">视频顶部标题</span>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="handleClose">
                    <close-btn />
                </div>
            </div>

            <div class="px-6 py-5 flex flex-col gap-4">
                <div class="flex items-start gap-3 bg-[#EBF2FF] rounded-xl px-4 py-3">
                    <span class="flex-shrink-0">
                        <Icon name="el-icon-InfoFilled" color="#0065fb" :size="16" />
                    </span>
                    <span class="text-xs text-slate-500 leading-relaxed">
                        为视频添加顶部展示标题，多条标题将<strong class="text-slate-700">随机匹配</strong>使用。
                        每条标题最多 <strong class="text-slate-700">{{ MAX_LENGTH }}</strong> 个字，不能少于
                        <strong class="text-slate-700">{{ MIN_LENGTH }}</strong> 个字。
                    </span>
                </div>

                <div
                    class="bg-white rounded-2xl overflow-hidden border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.06)]">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                        <span class="text-[13px] font-black text-slate-500 uppercase tracking-wider">标题列表</span>
                        <span class="text-xs text-slate-400">
                            共
                            <strong class="text-primary">{{ localList.length }}</strong>
                            条
                        </span>
                    </div>

                    <div ref="scrollBodyRef" class="max-h-[340px] overflow-y-auto">
                        <TransitionGroup name="list-fade">
                            <div
                                v-for="(item, index) in localList"
                                :key="item._id"
                                :data-id="item._id"
                                class="flex items-start gap-3 px-5 py-4 border-b border-slate-100 last:border-b-0 group transition-colors duration-300"
                                :class="errorIds.has(item._id) ? 'bg-red-50/60' : ''">
                                <div
                                    class="w-6 h-6 rounded-full text-[11px] font-black flex items-center justify-center flex-shrink-0 mt-2 transition-colors duration-300"
                                    :class="
                                        errorIds.has(item._id)
                                            ? 'bg-red-100 text-red-400'
                                            : 'bg-[#0065fb]/10 text-primary'
                                    ">
                                    {{ index + 1 }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div
                                        class="rounded-[12px] border transition-all duration-200 overflow-hidden"
                                        :class="
                                            errorIds.has(item._id) && item.text.trim().length === 0
                                                ? 'border-red-400 bg-red-50'
                                                : item.text.length > MAX_LENGTH
                                                ? 'border-red-400 bg-red-50'
                                                : item.text.length >= MIN_LENGTH
                                                ? 'border-[#0065fb]/40 bg-white'
                                                : 'border-slate-200 bg-slate-50'
                                        ">
                                        <ElInput
                                            v-model="item.text"
                                            type="textarea"
                                            :rows="2"
                                            resize="none"
                                            :placeholder="`请输入第 ${index + 1} 条顶部标题…`"
                                            class="storyboard-title-input"
                                            @input="errorIds.delete(item._id)" />
                                        <div class="flex items-center justify-between px-3 pb-2">
                                            <span
                                                v-if="errorIds.has(item._id) && item.text.trim().length === 0"
                                                class="text-[11px] text-red-400 flex items-center gap-1">
                                                <Icon name="el-icon-WarnTriangleFilled" :size="11" />
                                                内容不能为空
                                            </span>
                                            <span
                                                v-else-if="item.text.length > MAX_LENGTH"
                                                class="text-[11px] text-red-400 flex items-center gap-1">
                                                <Icon name="el-icon-WarnTriangleFilled" :size="11" />
                                                超出 {{ MAX_LENGTH }} 字限制
                                            </span>
                                            <span
                                                v-else-if="item.text.length > 0 && item.text.length < MIN_LENGTH"
                                                class="text-[11px] text-amber-400 flex items-center gap-1">
                                                <Icon name="el-icon-WarnTriangleFilled" :size="11" />
                                                不能少于 {{ MIN_LENGTH }} 个字
                                            </span>
                                            <span v-else class="invisible text-[11px]">placeholder</span>
                                            <span
                                                class="text-[11px]"
                                                :class="
                                                    item.text.length > MAX_LENGTH
                                                        ? 'text-red-400 font-bold'
                                                        : 'text-slate-300'
                                                ">
                                                {{ item.text.length }} / {{ MAX_LENGTH }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    class="w-7 h-7 rounded-full bg-slate-100 hover:bg-red-50 flex items-center justify-center text-slate-300 hover:text-red-400 transition-all mt-1.5 flex-shrink-0 opacity-0 group-hover:opacity-100"
                                    @click="handleRemove(index)">
                                    <Icon name="el-icon-Close" :size="12" />
                                </button>
                            </div>
                        </TransitionGroup>

                        <div
                            v-if="localList.length === 0"
                            class="flex flex-col items-center justify-center py-10 text-slate-300">
                            <Icon name="el-icon-Tickets" :size="36" />
                            <span class="mt-3 text-[13px]">暂无标题，点击下方按钮添加</span>
                        </div>
                    </div>

                    <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/60">
                        <button
                            class="w-full h-9 rounded-[10px] border border-dashed border-slate-300 hover:border-primary hover:bg-[#0065fb]/5 flex items-center justify-center gap-1.5 text-slate-400 hover:text-primary transition-all duration-200"
                            @click="handleAdd">
                            <Icon name="el-icon-Plus" :size="13" />
                            <span class="text-[12px] font-medium">添加一条标题</span>
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="px-6 py-4 border-t border-slate-100 flex items-center justify-between shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <span class="text-xs text-slate-400">
                    共
                    <strong class="text-primary">{{ localList.length }}</strong>
                    条标题
                </span>
                <div class="flex items-center gap-2">
                    <button
                        class="px-6 h-10 rounded-xl border border-slate-200 text-slate-600 text-sm font-[800] hover:border-slate-300 hover:bg-slate-50 transition-all"
                        @click="handleClose">
                        取消
                    </button>
                    <button
                        class="px-8 h-10 rounded-xl bg-primary text-white text-sm font-[1000] shadow-lg shadow-[#0065fb]/20 transition-all hover:bg-[#0056d6] hover:scale-[1.02] active:scale-95"
                        @click="handleConfirm">
                        确认
                    </button>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const MAX_LENGTH = 50;
const MIN_LENGTH = 1;

const props = defineProps<{
    data?: any[];
}>();

const emit = defineEmits<{
    (e: "confirm", data: { text: string }[]): void;
    (e: "close"): void;
}>();

let _uid = 0;
const makeItem = (text = "") => ({ _id: ++_uid, text });

const popupRef = shallowRef();
const scrollBodyRef = ref<HTMLElement | null>(null);
const localList = ref<{ _id: number; text: string }[]>([]);
const errorIds = ref<Set<number>>(new Set());

const open = () => {
    localList.value = (props.data ?? []).map((item) =>
        makeItem(typeof item === "string" ? item : item?.text ?? item?.title ?? ""),
    );
    if (localList.value.length === 0) localList.value.push(makeItem());
    errorIds.value = new Set();
    popupRef.value?.open();
};

defineExpose({ open });

const isValid = computed(() => {
    const nonEmpty = localList.value.filter((i) => i.text.trim().length > 0);
    if (nonEmpty.length === 0) return true;
    return nonEmpty.every((i) => i.text.trim().length >= MIN_LENGTH && i.text.length <= MAX_LENGTH);
});

const handleAdd = async () => {
    localList.value.push(makeItem());
    await nextTick();
    if (scrollBodyRef.value) {
        scrollBodyRef.value.scrollTop = scrollBodyRef.value.scrollHeight;
    }
};

const handleRemove = (index: number) => {
    const id = localList.value[index]._id;
    errorIds.value.delete(id);
    localList.value.splice(index, 1);
};

// ── 核心修复：用 scrollIntoView 替代 offsetTop 计算 ──────────
const scrollToFirstError = async (firstId: number) => {
    await nextTick();
    // 直接在整个文档内查找，不依赖 scrollBodyRef
    const target = document.querySelector<HTMLElement>(`[data-id="${firstId}"]`);
    if (!target) return;
    target.scrollIntoView({ behavior: "smooth", block: "nearest" });
};

const handleConfirm = async () => {
    // 找出所有空内容条目
    const emptyItems = localList.value.filter((i) => i.text.trim().length === 0);

    if (emptyItems.length > 0) {
        // 标记全部空项
        errorIds.value = new Set(emptyItems.map((i) => i._id));
        // 滚动定位到第一条空项
        await scrollToFirstError(emptyItems[0]._id);
        return;
    }

    if (!isValid.value) return;

    const result = localList.value.filter((i) => i.text.trim().length > 0).map((i) => ({ text: i.text.trim() }));
    emit("confirm", result);
    popupRef.value?.close();
};

const handleClose = () => {
    emit("close");
    popupRef.value?.close();
};
</script>

<style lang="scss" scoped>
:deep(.storyboard-title-input) {
    .el-textarea__inner {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        padding: 10px 12px 4px !important;
        font-size: 13px;
        color: #1e293b;
        resize: none;
    }
}

.list-fade-enter-active,
.list-fade-leave-active {
    transition: all 0.2s ease;
}
.list-fade-enter-from,
.list-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
