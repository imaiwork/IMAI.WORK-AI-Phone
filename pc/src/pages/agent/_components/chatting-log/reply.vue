<template>
    <popup ref="popupRef" title="回复内容" width="600px" confirm-button-text="" cancel-button-text="" @close="close">
        <div>
            <div class="flex flex-col gap-4">
                <div>
                    <div class="text-[14px] font-bold">用户提问内容：</div>
                    <div class="text-black border border-primary p-2 rounded-md mt-2">
                        {{ formData.message }}
                    </div>
                </div>
                <div>
                    <div class="text-[14px] font-bold">回复内容：</div>
                    <div class="mt-2">
                        <Markdown :content="formData.reply" />
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const popupRef = ref<any>();

const formData = reactive({
    message: "",
    reply: "",
});

const open = () => {
    popupRef.value.open();
};

const close = () => {
    popupRef.value.close();
};

defineExpose({
    open,
    close,
    setFormData: (data) => setFormData(data, formData),
});
</script>

<style scoped></style>
