<template>
    <div class="flex items-center gap-x-2">
        <ElCheckbox v-model="isActive"></ElCheckbox>
        <div class="text-xs flex items-center gap-x-1 text-[rgba(0, 0, 0, 0.80)]">
            已阅读并同意<router-link :to="`/policy/${PolicyAgreementEnum.SERVICE}`" target="_blank" class="text-primary"
                >服务协议</router-link
            >和<router-link :to="`/policy/${PolicyAgreementEnum.PRIVACY}`" target="_blank" class="text-primary"
                >隐私协议</router-link
            >
        </div>
    </div>
    <popup
        ref="popupRef"
        v-if="showConfirmPopup"
        width="343px"
        cancel-button-text=""
        confirm-button-text=""
        :show-close="false"
        @close="showConfirmPopup = false">
        <div class="h-[100px] text-center -mb-4">
            <div class="text-lg font-bold">温馨提示</div>
            <div class="text-[rgba(0,0,0,0.5)] mt-4 w-[70%] mx-auto text-base">
                请勾选已阅读并同意《服务协议》和《隐私协议》
            </div>
            <ElButton
                type="primary"
                class="w-full mt-6 !h-[46px] !rounded-[48px] shadow-[0_6px_12px_0_rgba(0,101,251,0.20)]"
                @click="confirm"
                >确定</ElButton
            >
        </div>
    </popup>
</template>

<script setup lang="ts">
import { PolicyAgreementEnum } from "@/enums/appEnums";

const isActive = ref(false);

const popupRef = shallowRef();
const showConfirmPopup = ref(false);

const checkAgreement = async () => {
    if (!isActive.value) {
        showConfirmPopup.value = true;
        await nextTick();
        popupRef.value.open();
    }
    return isActive.value;
};

const confirm = () => {
    isActive.value = true;
    showConfirmPopup.value = false;
};

defineExpose({
    checkAgreement,
});
</script>

<style scoped lang="scss">
:deep(.el-checkbox__inner) {
    @apply rounded-full;
}
</style>
