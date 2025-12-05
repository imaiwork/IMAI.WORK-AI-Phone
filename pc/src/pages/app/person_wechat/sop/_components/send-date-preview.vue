<template>
    <popup ref="popupRef" confirm-button-text="" cancel-button-text="" width="800px" @close="close">
        <template #header>
            <div class="flex items-center gap-2">
                <img src="@/assets/images/7_day.png" class="w-6 h-6" />
                <span>推送日期预览</span>
            </div>
        </template>
        <div>
            <send-date :date-list="dateList" @edit="emit('edit', $event)" />
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import SendDate from "./send-date.vue";

const props = defineProps<{
    dateList: any[];
}>();

const emit = defineEmits<{
    (event: "close"): void;
    (event: "edit", value: any): void;
}>();

const popupRef = ref<InstanceType<typeof Popup>>();

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped></style>
