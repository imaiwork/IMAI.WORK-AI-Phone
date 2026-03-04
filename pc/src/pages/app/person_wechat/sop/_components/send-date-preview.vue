<template>
    <popup
        ref="popupRef"
        confirm-button-text=""
        cancel-button-text=""
        width="650px"
        custom-class="preview-popup"
        @close="close">
        <div class="flex items-center justify-between w-full pr-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 flex items-center justify-center shadow-light">
                    <img src="@/assets/images/7_day.png" class="w-6 h-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-[18px] font-medium text-slate-800 tracking-tight">推送日期全景预览</span>
                    <span class="text-[11px] text-primary font-medium uppercase tracking-[0.2em] opacity-70"
                        >Push Schedule Panorama</span
                    >
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    <span class="text-xs text-slate-400 font-medium">已编排 {{ dateList.length }} 天</span>
                </div>
            </div>
        </div>
        <div class="preview-content-container">
            <div class="bg-[#f8fafc]/50 rounded-[24px] p-6 border border-slate-100">
                <send-date :date-list="dateList" class="!grid-cols-7 gap-4" @edit="handleEdit" />
            </div>

            <div class="mt-6 flex justify-center">
                <div
                    class="px-4 py-2 rounded-full bg-slate-100 text-slate-400 text-xs font-medium flex items-center gap-2">
                    <Icon name="el-icon-InfoFilled" />
                    提示：点击具体时间点可快速跳转编辑该素材
                </div>
            </div>
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

const handleEdit = (value: any) => {
    // 全屏预览时编辑，先关闭预览弹窗再跳转
    close();
    popupRef.value?.close();
    emit("edit", value);
};

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
:deep(.preview-popup) {
    @apply rounded-[32px] overflow-hidden border-none shadow-light;

    .el-dialog__header {
        @apply bg-white border-b border-slate-50 py-5 px-8;
    }

    .el-dialog__body {
        @apply bg-[#FBFCFD] p-8;
    }
}

.preview-content-container {
    @apply min-h-[400px] flex flex-col mt-4;
}
</style>
