<template>
    <popup
        ref="popupRef"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        :append-to-body="false"
        :show-close="false">
        <div class="relative px-2 py-1">
            <div class="absolute -top-2 -right-2 w-8 h-8 z-[22]" @click="close">
                <close-btn />
            </div>

            <div class="mb-6">
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-1.5 h-4 bg-primary rounded-full"></span>
                    <h3 class="text-lg font-[1000] text-slate-800 tracking-tight">编辑验证备注</h3>
                </div>
                <p class="text-[11px] text-slate-400 font-medium uppercase tracking-widest ml-3.5">
                    Edit Validation Message
                </p>
            </div>

            <div class="relative group">
                <ElInput
                    v-model="remark"
                    type="textarea"
                    placeholder="请输入加好友申请的备注内容..."
                    resize="none"
                    maxlength="100"
                    show-word-limit
                    :rows="6"
                    class="custom-textarea" />
            </div>

            <div class="mt-8 flex flex-col gap-3">
                <button
                    @click="confirm"
                    class="w-full h-[52px] bg-primary rounded-[18px] text-white font-[1000] text-base shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <Icon name="el-icon-Check" :size="18" />
                    立即保存内容
                </button>

                <p class="text-center text-[10px] text-slate-400 font-medium">提示：优质的备注内容能显著提高通过率</p>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const emit = defineEmits(["close", "confirm"]);
const popupRef = shallowRef();
const remark = ref("");

const open = (value?: string) => {
    remark.value = value || "";
    popupRef.value?.open();
};

const close = () => {
    popupRef.value?.close();
    emit("close");
};

const confirm = () => {
    if (!remark.value.trim()) {
        feedback.msgWarning("请输入备注内容");
        return;
    }
    emit("confirm", remark.value);
};

defineExpose({ open });
</script>
