<template>
    <Popup
        ref="popupRef"
        async
        title="选择素材库资源"
        width="1000px"
        top="10vh"
        @confirm="handleConfirm"
        @close="close">
        <div class="material-picker-content" v-loading="!visible" element-loading-text="加载素材库...">
            <div v-if="visible">
                <div class="flex items-center text-xs text-tx-placeholder font-normal mb-4">
                    <span class="text-primary opacity-70 leading-[0]">
                        <Icon name="el-icon-InfoFilled" />
                    </span>
                    <span class="ml-1">最多可选 {{ limit }} 个素材</span>
                </div>
                <div class="h-[700px]">
                    <Material :mode="mode" :type="type" :limit="limit" @update:select="getSelect" />
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between w-full px-4">
                <div class="flex items-center gap-2">
                    <span class="text-[13px] text-tx-secondary">已选择</span>
                    <span class="text-[14px] font-[900] text-primary bg-blue-50 px-2 py-0.5 rounded-md">
                        {{ selectItem?.length || 0 }}
                    </span>
                    <span class="text-[13px] text-tx-placeholder">/ {{ limit }}</span>
                </div>

                <div class="flex gap-3">
                    <ElButton @click="popupRef?.close()">取消</ElButton>
                    <ElButton
                        type="primary"
                        class="confirm-btn-custom"
                        :disabled="!selectItem || selectItem.length === 0"
                        @click="handleConfirm">
                        确认选择
                    </ElButton>
                </div>
            </div>
        </template>
    </Popup>
</template>
<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import Material from "../marketing/_pages/material/index.vue";

const props = withDefaults(
    defineProps<{
        type: MaterialTypeEnum;
        mode?: "page" | "popup";
        limit?: number;
    }>(),
    {
        mode: "popup",
        limit: 9,
    }
);

const popupRef = ref<InstanceType<typeof Popup>>();
const emit = defineEmits<{
    (e: "close"): void;
    (e: "select", value: any[]): void;
}>();

const selectItem = ref<any>(null);
const getSelect = (value: any[]) => {
    selectItem.value = value;
};

const handleConfirm = () => {
    if (selectItem.value) {
        emit("select", selectItem.value);
        emit("close");
    } else {
        feedback.msgError("请选择素材");
    }
};

const visible = ref(false);

const open = () => {
    popupRef.value?.open();
    visible.value = true;
};

const close = () => {
    emit("close");
    visible.value = false;
};

defineExpose({
    open,
});
</script>
