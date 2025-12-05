<template>
    <popup ref="popupRef" title="添加AI智能设备" async width="500px" confirm-button-text="" @close="close">
        <div class="flex flex-col gap-y-4">
            <div class="rounded-[10px] bg-white p-5 flex flex-col items-center">
                <div class="text-xl font-bold">设备绑定码</div>
                <div class="text-xl text-[#0000004d] mt-[10px]">请勿与任何人分享此代码</div>
                <div class="w-[250px] h-[250px] mt-[28px]" v-loading="loading">
                    <img :src="qrcode" class="w-full h-full rounded-[10px]" v-if="qrcode" />
                </div>
                <div class="font-bold mt-5">请前往RPA启用摄像头扫描此二维码</div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getRpaQrcode, getRpaQrcodeStatus } from "@/api/user";

const props = defineProps<{
    bindLoading: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "confirm"): void;
}>();

const loading = ref(false);

const popupRef = ref<any>(null);
const qrcode = ref<string>("");

const { start, end } = usePolling(
    async () => {
        const data = await getRpaQrcodeStatus();
        if (data.status == 1) {
            end();
            emit("confirm");
            feedback.msgSuccess("绑定成功");
        }
    },
    {
        time: 4500,
    }
);

const getRpaQrcodeData = async () => {
    loading.value = true;
    try {
        const data = await getRpaQrcode();
        qrcode.value = data.url;
        start();
    } finally {
        loading.value = false;
    }
};

const open = () => {
    popupRef.value.open();
    getRpaQrcodeData();
};

const close = () => {
    emit("close");
    end();
};

onUnmounted(() => end());

defineExpose({
    open,
});
</script>

<style scoped></style>
