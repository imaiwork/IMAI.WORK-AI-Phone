<template>
    <popup ref="popupRef" async title="选择素材" width="1200px" @confirm="handleConfirm" @close="close">
        <div class="h-[800px] bg-[#f6f7f8] p-4 rounded-xl">
            <Material v-if="visible" :mode="mode" :type="type" :limit="limit" @update:select="getSelect" />
        </div>
    </popup>
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

<style scoped></style>
