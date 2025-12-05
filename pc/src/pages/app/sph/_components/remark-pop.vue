<template>
    <popup
        ref="popupRef"
        confirm-button-text=""
        cancel-button-text=""
        style="
            padding: 18px;
            border-radius: 20px;
            background-color: var(--app-bg-color-3);
            box-shadow: 0 0 0 1px #2a2a2a;
        "
        :append-to-body="false"
        :show-close="false">
        <div class="-my-4">
            <div class="absolute w-6 h-6 top-[18px] right-[18px] z-[22]" @click="close">
                <close-btn />
            </div>
            <div class="text-white text-lg font-bold">加好友备注文案</div>
            <div class="mt-6">
                <ElInput
                    v-model="remark"
                    type="textarea"
                    placeholder="请输入备注内容"
                    resize="none"
                    maxlength="100"
                    show-word-limit
                    :rows="6" />
            </div>
            <div class="mt-4">
                <ElButton class="!h-[50px] w-full" round type="primary" @click="confirm">立即保存</ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const emit = defineEmits(["close", "confirm"]);

const popupRef = shallowRef();
const remark = ref("");

const open = (value?: string) => {
    remark.value = value;
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const confirm = () => {
    if (!remark.value) {
        feedback.msgWarning("请输入备注内容");
        return;
    }
    emit("confirm", remark.value);
};

defineExpose({
    open,
});
</script>

<style scoped></style>
